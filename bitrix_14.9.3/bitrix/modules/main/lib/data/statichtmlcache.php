<?php
namespace Bitrix\Main\Data;

use Bitrix\Main;

/**
 * Class StaticHtmlCache
 *
 * <code>
 * $staticHtmlCache = \Bitrix\Main\Data\StaticHtmlCache::getInstance();
 *
 * if ($staticHtmlCache->exists())
 * &#123;
 * 	$staticHtmlCache->read();
 * 	die();
 * &#125;
 *
 * if ($staticHtmlCache->isCacheable())
 * &#123;
 * 	$staticHtmlCache->write($content, md5($content));
 * &#125;
 * else
 * &#123;
 * 	$staticHtmlCache->delete();
 * &#125;
 *
 * if ($staticHtmlCache->isCacheable() && $staticHtmlCache->exists())
 * &#123;
 * 	if (md5($content) !== $staticHtmlCache->getMd5())
 * 		$staticHtmlCache->write($content, md5($content)); //update cache
 * 	//send Json
 * &#125;
 * </code>
 *
 * @package Bitrix\Main\Data
 */
class StaticHtmlCache
{
	/**
	 * @var StaticHtmlCache
	 */
	protected static $instance = null;
	/**
	 * @var string
	 */
	private $cacheKey = null;
	/**
	 * @var bool
	 */
	private $canCache = true;

	/**
	 * @var StaticHtmlStorage
	 */
	private $storage = null;

	/**
	 * Creates new cache manager instance.
	 */
	public function __construct()
	{
	}

	/**
	 * Returns current instance of the StaticHtmlCache.
	 *
	 * @return StaticHtmlCache
	 */
	public static function getInstance()
	{
		if (!isset(static::$instance))
		{
			static::$instance = new static();
			static::$instance->init();
		}

		return static::$instance;
	}

	/**
	 * Initializes an instance.
	 *
	 * @return void
	 */
	public function init()
	{
		$server = Main\Context::getCurrent()->getServer();
		$this->cacheKey = \CHTMLPagesCache::convertUriToPath($server->getRequestUri(), $server->getHttpHost());

		if ($this->cacheKey)
		{
			$this->storage = $this->getStaticHtmlStorage($this->cacheKey);
		}
	}

	/**
	 * Converts request uri into path safe file with .html extention.
	 * Returns empty string if fails.
	 * @deprecated
	 * @param string $uri Uri.
	 * @param string $host Host name.
	 * @return string
	 */
	public static function convertUriToPath($uri, $host = "")
	{
		return \CHTMLPagesCache::convertUriToPath($uri, $host);
	}

	/**
	 * Writes the content to the storage
	 * @param string $content the string that is to be written
	 * @param string $md5 the content hash
	 *
	 * @return bool
	 */
	public function write($content, $md5)
	{
		if ($this->storage === null)
		{
			return false;
		}

		$this->writeDebug();

		$written = $this->storage->write($content, $md5);
		if ($written !== false)
		{
			\CHTMLPagesCache::writeStatistic(0, 1, 0, 0, $written);
		}

		return $written;
	}

	/**
	 * Returns html content from the cache
	 *
	 * @return string
	 */
	public function read()
	{
		if ($this->storage !== null)
		{
			return $this->storage->read();
		}

		return false;
	}

	/**
	 * Deletes the cache
	 *
	 * @return bool|int
	 */
	public function delete()
	{
		if ($this->storage === null)
		{
			return false;
		}

		$this->writeDebug();

		$deletedSize = $this->storage->delete();
		if ($deletedSize !== false)
		{
			\CHTMLPagesCache::writeStatistic(0, 0, 0, 0, -$deletedSize);
		}

		return $deletedSize;
	}

	/**
	 * Deletes all cache data
	 * @return bool
	 */
	public function deleteAll()
	{
		if ($this->storage === null)
		{
			return false;
		}

		$this->storage->deleteAll();
		\CHTMLPagesCache::writeStatistic(0, 0, 0, 0, false);

		return true;
	}

	/**
	 * Returns true if the cache exists
	 *
	 * @return boolean
	 */
	public function exists()
	{
		if ($this->storage !== null)
		{
			return $this->storage->exists();
		}

		return false;
	}

	/**
	 * Returns hash of the cache
	 * @return string|false
	 */
	public function getMd5()
	{
		if ($this->storage !== null)
		{
			return $this->storage->getMd5();
		}

		return false;
	}

	/**
	 * Returns true if we can cache current request
	 *
	 * @return bool
	 */
	public function isCacheable()
	{
		if ($this->storage === null)
		{
			return false;
		}

		if (isset($_SESSION["SESS_SHOW_TIME_EXEC"]) && ($_SESSION["SESS_SHOW_TIME_EXEC"] == 'Y'))
		{
			return false;
		}
		elseif (isset($_SESSION["SHOW_SQL_STAT"]) && ($_SESSION["SHOW_SQL_STAT"] == 'Y'))
		{
			return false;
		}
		elseif (isset($_SESSION["SHOW_CACHE_STAT"]) && ($_SESSION["SHOW_CACHE_STAT"] == 'Y'))
		{
			return false;
		}

		$httpStatus = intval(\CHTTP::GetLastStatus());
		if ($httpStatus == 200 || $httpStatus === 0)
		{
			return $this->canCache;
		}

		return false;
	}

	/**
	 * Marks current page as non cacheable.
	 *
	 * @return void
	 */
	public function markNonCacheable()
	{
		$this->canCache = false;
	}

	/**
	 * Returns the instance of the StaticHtmlStorage
	 * @param string $cacheKey unique cache identifier
	 *
	 * @return StaticHtmlStorage|null
	 */
	public static function getStaticHtmlStorage($cacheKey)
	{
		$configuration = array();
		$htmlCacheOptions = \CHTMLPagesCache::GetOptions();
		$storage = isset($htmlCacheOptions["STORAGE"]) ? $htmlCacheOptions["STORAGE"] : false;

		if (in_array($storage, array("memcached", "memcached_cluster")))
		{
			if (extension_loaded("memcache"))
			{
				return new StaticHtmlMemcachedStorage($cacheKey, $configuration, $htmlCacheOptions);
			}
			else
			{
				return null;
			}
		}
		else
		{
			return new StaticHtmlFileStorage($cacheKey, $configuration, $htmlCacheOptions);
		}
	}

	/**
	 * Writes a debug information in a log file
	 */
	private function writeDebug()
	{
		if (!defined("BX_COMPOSITE_DEBUG") || BX_COMPOSITE_DEBUG !== true || !$this->storage->exists())
		{
			return;
		}

		if (\CHTMLPagesCache::checkQuota())
		{
			//temporary check
			if ($this->storage instanceof StaticHtmlFileStorage)
			{
				$cacheFile = $this->storage->getCacheFile();
				$backupName = $cacheFile->getPath().".delete.".microtime(true);
				AddMessage2Log($backupName, "composite");
				$backupFile = new Main\IO\File($backupName);
				$backupFile->putContents($cacheFile->getContents());
				\CHTMLPagesCache::writeStatistic(0, 0, 0, 0, $cacheFile->getSize());
			}
			else
			{
				AddMessage2Log($this->cacheKey." was deleted", "composite");
			}
		}
		else
		{
			AddMessage2Log($this->cacheKey."(quota exceeded)", "composite");
		}
	}

	/**
	 * Checks component frame mode
	 * @param string $context
	 */
	public static function applyComponentFrameMode($context = "")
	{
		if (
			defined("USE_HTML_STATIC_CACHE")
			&& USE_HTML_STATIC_CACHE === true
			&& \Bitrix\Main\Page\Frame::getInstance()->getCurrentDynamicId() === false
		)
		{
			$staticHtmlCache = static::getInstance();
			$staticHtmlCache->markNonCacheable();

			if (defined("BX_COMPOSITE_DEBUG") && BX_COMPOSITE_DEBUG === true)
			{
				AddMessage2Log(
					"Reason: ".$context."\n".
					"Request URI: ".$_SERVER["REQUEST_URI"]."\n".
					"Script: ".(isset($_SERVER["REAL_FILE_PATH"]) ? $_SERVER["REAL_FILE_PATH"] : $_SERVER["SCRIPT_NAME"]),
					"Composite was rejected"
				);
			}
		}

	}
}
