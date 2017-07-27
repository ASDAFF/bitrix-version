<?
/*. require_module 'standard'; .*/
/*. require_module 'session'; .*/
/*. require_module 'zlib'; .*/
/*. require_module 'pcre'; .*/
use Bitrix\Main\IO;
use Bitrix\Main\Application;
use Bitrix\Main;

class CHTMLPagesCache
{
	private static $options = array();
	private static $isIE9 = null;
	private static $isAjaxRequest = null;
	private static $ajaxRandom = null;

	/**
	 * Checks many conditions to enable HTML Cache
	 *
	 * @return void
	 */
	public static function startCaching()
	{
		self::$ajaxRandom = self::removeRandParam();

		if (
			isset($_SERVER["HTTP_BX_AJAX"])
			|| isset($_GET["bxajaxid"])
			|| isset($_GET["ncc"])
			|| self::isHttps()
			|| self::isBitrixFolder()
			|| (
					self::isIE9() &&
					!self::isAjaxRequest() //Temporary compatibility checking. If nginx doesn't skip IE9
				)
			|| (preg_match("#^/index_controller\\.php#", $_SERVER["REQUEST_URI"]) > 0)
		)
		{
			return;
		}

		$arHTMLPagesOptions = self::getOptions();

		$useCompositeCache = isset($arHTMLPagesOptions["COMPOSITE"]) && $arHTMLPagesOptions["COMPOSITE"] === "Y";
		if ($useCompositeCache)
		{
			//to warm up localStorage
			define("ENABLE_HTML_STATIC_CACHE_JS", true);
		}

		if ($_SERVER["REQUEST_METHOD"] !== "GET" || isset($_GET["sessid"]))
		{
			return;
		}

		if ($useCompositeCache)
		{
			if (isset($_SERVER["HTTP_BX_REF"]))
			{
				$_SERVER["HTTP_REFERER"] = $_SERVER["HTTP_BX_REF"];
			}
		}

		if(
			$useCompositeCache
			&& (
				isset($arHTMLPagesOptions["COOKIE_NCC"])
				&& array_key_exists($arHTMLPagesOptions["COOKIE_NCC"], $_COOKIE)
				&& $_COOKIE[$arHTMLPagesOptions["COOKIE_NCC"]] === "Y"
			)
		)
		{
			return;
		}

		if(
			!$useCompositeCache
			&& (
				array_key_exists(session_name(), $_COOKIE)
				|| array_key_exists(session_name(), $_REQUEST)
			)
		)
		{
			return;
		}

		//Check for stored authorization
		if(
			isset($arHTMLPagesOptions["STORE_PASSWORD"]) && $arHTMLPagesOptions["STORE_PASSWORD"] == "Y"
			&& isset($_COOKIE[$arHTMLPagesOptions["COOKIE_LOGIN"]]) && $_COOKIE[$arHTMLPagesOptions["COOKIE_LOGIN"]] <> ''
			&& isset($_COOKIE[$arHTMLPagesOptions["COOKIE_PASS"]]) && $_COOKIE[$arHTMLPagesOptions["COOKIE_PASS"]] <> ''
		)
		{
			if (
				!$useCompositeCache
				|| !isset($arHTMLPagesOptions["COOKIE_CC"])
				|| !array_key_exists($arHTMLPagesOptions["COOKIE_CC"], $_COOKIE)
				|| $_COOKIE[$arHTMLPagesOptions["COOKIE_CC"]] !== "Y"
			)
			{
				return;
			}
		}

		//Check for masks
		$p = strpos($_SERVER["REQUEST_URI"], "?");
		if($p === false)
		{
			$PAGES_FILE = $_SERVER["REQUEST_URI"];
		}
		else
		{
			$PAGES_FILE = substr($_SERVER["REQUEST_URI"], 0, $p);
		}

		if (isset($arHTMLPagesOptions["~EXCLUDE_MASK"]) && is_array($arHTMLPagesOptions["~EXCLUDE_MASK"]))
		{
			foreach($arHTMLPagesOptions["~EXCLUDE_MASK"] as $mask)
			{
				if(preg_match($mask, $PAGES_FILE) > 0)
				{
					return;
				}
			}
		}

		if (isset($arHTMLPagesOptions["~EXCLUDE_PARAMS"]) && is_array($arHTMLPagesOptions["~EXCLUDE_PARAMS"]))
		{
			foreach ($arHTMLPagesOptions["~EXCLUDE_PARAMS"] as $param)
			{
				if (array_key_exists($param, $_GET))
				{
					return;
				}
			}
		}

		if (isset($arHTMLPagesOptions["~INCLUDE_MASK"]) && is_array($arHTMLPagesOptions["~INCLUDE_MASK"]))
		{
			foreach($arHTMLPagesOptions["~INCLUDE_MASK"] as $mask)
			{
				if(preg_match($mask, $PAGES_FILE) > 0)
				{
					$PAGES_FILE = "*";
					break;
				}
			}
		}

		if ($PAGES_FILE !== "*")
		{
			return;
		}

		$host = "";
		if ($useCompositeCache)
		{
			$host = self::getHttpHost();
			if (!in_array($host, self::getDomains()))
			{
				return;
			}

			if (self::isIndexOnlyMode($arHTMLPagesOptions))
			{
				return;
			}
		}

		if (self::isAjaxRequest())
		{
			define("USE_HTML_STATIC_CACHE", true);
			return;
		}

		self::setErrorHandler();

		$cacheKey = self::getCacheKey($host);
		$cache = self::getHtmlCacheResponse($cacheKey, $arHTMLPagesOptions);
		if ($cache !== null && $cache->exists())
		{
			//Update statistic
			self::writeStatistic(1);

			$etag = $cache->getEtag();
			$lastModified = $cache->getLastModified();
			if ($etag !== false)
			{
				if (array_key_exists("HTTP_IF_NONE_MATCH", $_SERVER) && $_SERVER["HTTP_IF_NONE_MATCH"] === $etag)
				{
					self::setStatus("304 Not Modified");
					self::setHeaders($etag, false, "304");
					die();
				}
			}

			if ($lastModified !== false)
			{
				$utc = gmdate("D, d M Y H:i:s", $lastModified)." GMT";
				if (array_key_exists("HTTP_IF_MODIFIED_SINCE", $_SERVER) && $_SERVER["HTTP_IF_MODIFIED_SINCE"] === $utc)
				{
					self::setStatus("304 Not Modified");
					self::setHeaders($etag, false, "304");
					die();
				}
			}

			$contents = $cache->getContents();
			if (self::isCacheConsistent($contents))
			{
				self::setHeaders($etag, $lastModified, "200");

				$contentType = $cache->getContentType();
				if ($contentType === false)
				{
					//Try to parse charset encoding
					$head_end = strpos($contents, "</head>");
					if ($head_end !== false)
					{
						if (preg_match("#<meta\\s+http-equiv\\s*=\\s*(['\"])Content-Type(\\1)\\s+content\\s*=\\s*(['\"])(.*?)(\\3)#im", substr($contents, 0, $head_end), $arMatch))
						{
							header("Content-type: ".$arMatch[4]);
						}
					}
				}
				else
				{
					header("Content-type: ".$contentType);
				}


				//compression support
				$compress = "";
				if ($arHTMLPagesOptions["COMPRESS"] && isset($_SERVER["HTTP_ACCEPT_ENCODING"]))
				{
					if (strpos($_SERVER["HTTP_ACCEPT_ENCODING"], "x-gzip") !== false)
					{
						$compress = "x-gzip";
					}
					elseif (strpos($_SERVER["HTTP_ACCEPT_ENCODING"], "gzip") !== false)
					{
						$compress = "gzip";
					}
				}

				if ($compress)
				{
					if (isset($_SERVER["HTTP_USER_AGENT"]))
					{
						$userAgent = $_SERVER["HTTP_USER_AGENT"];
						if ( (strpos($userAgent, "MSIE 5")>0 || strpos($userAgent, "MSIE 6.0") > 0) && strpos($userAgent, "Opera") === false)
						{
							$contents = str_repeat(" ", 2048)."\r\n".$contents;
						}
					}
					$size = function_exists("mb_strlen")? mb_strlen($contents, "latin1"): strlen($contents);
					$crc = crc32($contents);
					$contents = gzcompress($contents, 4);
					$contents = function_exists("mb_substr")? mb_substr($contents, 0, -4, "latin1"): substr($contents, 0, -4);

					header("Content-Encoding: $compress");
					echo "\x1f\x8b\x08\x00\x00\x00\x00\x00", $contents, pack("V", $crc), pack("V", $size);
				}
				else
				{
					$length = function_exists("mb_strlen")? mb_strlen($contents, "latin1"): strlen($contents);
					header("Content-Length: ".$length);
					echo $contents;
				}

				die();
			}
		}

		if ($useCompositeCache)
		{
			if ($cache !== null && $cache->shouldCountQuota() && !self::checkQuota())
			{
				self::writeStatistic(0, 0, 1);
			}
			elseif (!defined("USE_HTML_STATIC_CACHE"))
			{
				define("USE_HTML_STATIC_CACHE", true);
			}
		}
		else
		{
			$cacheFile = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages"
						.self::convertUriToPath($_SERVER["REQUEST_URI"], "");
			define("HTML_PAGES_FILE", $cacheFile);
		}

		self::restoreErrorHandler();
	}

	private static function getCacheKey($host)
	{
		$userPrivateKey = self::getUserPrivateKey();
		return self::convertUriToPath(self::getRequestUri(), $host, self::getRealPrivateKey($userPrivateKey));
	}

	private static function isCacheConsistent($contents)
	{
		if ($contents === false || strlen($contents) < 2500)
		{
			return false;
		}

		return preg_match("/^[a-f0-9]{32}$/", substr($contents, -35, 32));
	}

	public static function getRequestUri()
	{
		if (self::isSpaMode())
		{
			return isset($options["SPA_REQUEST_URI"]) ? $options["SPA_REQUEST_URI"] : "/";
		}
		else
		{
			return $_SERVER["REQUEST_URI"];
		}
	}

	public static function getHttpHost($host = null)
	{
		return preg_replace("/:(80|443)$/", "", $host === null ? $_SERVER["HTTP_HOST"] : $host);
	}

	public static function getDomains()
	{
		$options = self::getOptions();
		$domains = array();
		if (isset($options["DOMAINS"]) && is_array($options["DOMAINS"]))
		{
			$domains = array_values($options["DOMAINS"]);
		}

		return array_map(array(__CLASS__, "getHttpHost"), $domains);
	}

	public static function getSpaPostfixByUri($requestUri)
	{
		$options = self::getOptions();
		$requestUri = ($p = strpos($requestUri, "?")) === false ? $requestUri : substr($requestUri, 0, $p);

		if (isset($options["SPA_MAP"]) && is_array($options["SPA_MAP"]))
		{
			foreach ($options["SPA_MAP"] as $mask => $postfix)
			{
				if (preg_match($mask, $requestUri))
				{
					return $postfix;
				}
			}
		}

		return null;
	}

	public static function getSpaPostfix()
	{
		$options = self::getOptions();
		if (isset($options["SPA_MAP"]) && is_array($options["SPA_MAP"]))
		{
			return array_values($options["SPA_MAP"]);
		}

		return array();
	}

	public static function getRealPrivateKey($privateKey = null, $postfix = null)
	{
		if (self::isSpaMode())
		{
			$postfix = $postfix === null ? self::getSpaPostfixByUri($_SERVER["REQUEST_URI"]) : $postfix;
			if ($postfix !== null)
			{
				$privateKey .= $postfix;
			}
		}

		return $privateKey;
	}

	public static function getUserPrivateKey()
	{
		$options = self::getOptions();
		if (isset($options["COOKIE_PK"]) && array_key_exists($options["COOKIE_PK"], $_COOKIE))
		{
			return $_COOKIE[$options["COOKIE_PK"]];
		}

		return null;
	}

	public static function setUserPrivateKey($prefix, $expire = 0)
	{
		$options = self::getOptions();
		if (isset($options["COOKIE_PK"]) && strlen($options["COOKIE_PK"]) > 0)
		{
			setcookie($options["COOKIE_PK"], $prefix, $expire, "/", false, false, true);
		}
	}

	public static function deleleUserPrivateKey()
	{
		$options = self::getOptions();
		if (isset($options["COOKIE_PK"]) && strlen($options["COOKIE_PK"]) > 0)
		{
			setcookie($options["COOKIE_PK"], "", 0, "/");
		}
	}
	/**
	 * Returns true if https has been detected.
	 *
	 * @return bool
	 */
	public static function isHttps()
	{
		$options = self::getOptions();
		if (isset($options["ALLOW_HTTPS"]) && $options["ALLOW_HTTPS"] === "Y")
		{
			return false;
		}

		if (isset($_SERVER["HTTPS"]) && (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"]!="off"))
		{
			return true;
		}

		if (isset($_SERVER["HTTP_FORWARDED"]) && $_SERVER["HTTP_FORWARDED"]=="SSL")
		{
			return true;
		}

		if (isset($_SERVER["HTTP_X_FORWARDED_PORT"]) && $_SERVER["HTTP_X_FORWARDED_PORT"]=="443")
		{
			return true;
		}

		return false;
	}

	/**
	 * Returns true if the current request was initiated by Ajax.
	 *
	 * @return bool
	 */
	public static function isAjaxRequest()
	{
		if (self::$isAjaxRequest === null)
		{
			self::$isAjaxRequest = (
				(isset($_SERVER["HTTP_BX_CACHE_MODE"]) && $_SERVER["HTTP_BX_CACHE_MODE"] === "HTMLCACHE")
				||
				(defined("CACHE_MODE") && constant("CACHE_MODE") === "HTMLCACHE")
			);
		}

		return self::$isAjaxRequest;
	}

	/**
	 * Returns true if the current request was sent by IE9 and above
	 *
	 * @return bool
	 */
	public static function isIE9()
	{
		if (self::$isIE9 === null)
		{
			self::$isIE9 = false;
			$userAgent = isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : false;
			if ($userAgent
				&& strpos($userAgent, "Opera") === false
				&& preg_match('#(MSIE|Internet Explorer) ([0-9]+)\\.([0-9]+)#', $userAgent, $version)
			)
			{
				if (intval($version[2]) > 0 && doubleval($version[2].".".$version[3]) < 10)
				{
					self::$isIE9 = true;
				}
			}
		}

		return self::$isIE9;
	}
	/**
	 * Returns true if the current request URI has bitrix folder
	 *
	 * @return bool
	 */
	public static function isBitrixFolder()
	{
		$folders = array(BX_ROOT, BX_PERSONAL_ROOT);
		$requestUri = "/".ltrim($_SERVER["REQUEST_URI"], "/");
		foreach ($folders as $folder)
		{
			$folder = rtrim($folder, "/")."/";
			if (strncmp($requestUri, $folder, strlen($folder)) == 0)
			{
				return true;
			}
		}

		return false;
	}

	public static function isSpaMode()
	{
		$options = self::getOptions();
		return isset($options["SPA_MODE"]) || $options["SPA_MODE"] === "Y";
	}

	/**
	 * Removes bxrand parameter from the current request and returns its value
	 *
	 * @return string|false
	 */
	public static function removeRandParam()
	{
		if (!array_key_exists("bxrand", $_GET) || !preg_match("/^[0-9]+$/", $_GET["bxrand"]))
		{
			return false;
		}

		$randValue = $_GET["bxrand"];

		unset($_GET["bxrand"]);
		unset($_REQUEST["bxrand"]);

		if (isset($_SERVER["REQUEST_URI"]))
		{
			$_SERVER["REQUEST_URI"] = preg_replace("/((?<=\\?)bxrand=\\d+&?|&bxrand=\\d+\$)/", "", $_SERVER["REQUEST_URI"]);
			$_SERVER["REQUEST_URI"] = rtrim($_SERVER["REQUEST_URI"], "?&");
		}

		if (isset($_SERVER["QUERY_STRING"]))
		{
			$_SERVER["QUERY_STRING"] = preg_replace("/[?&]?bxrand=[0-9]+/", "", $_SERVER["QUERY_STRING"]);
			$_SERVER["QUERY_STRING"] = trim($_SERVER["QUERY_STRING"], "&");
			if (isset($GLOBALS["QUERY_STRING"]))
			{
				$GLOBALS["QUERY_STRING"] = $_SERVER["QUERY_STRING"];
			}
		}

		return $randValue;
	}

	private static function isIndexOnlyMode($arHTMLPagesOptions)
	{
		if (!isset($arHTMLPagesOptions["INDEX_ONLY"]) || !$arHTMLPagesOptions["INDEX_ONLY"])
		{
			return false;
		}

		$queryParams = self::getQueryParams($_SERVER["REQUEST_URI"]);
		if (empty($queryParams))
		{
			return false;
		}

		if (isset($arHTMLPagesOptions["~GET"])
			&& !empty($arHTMLPagesOptions["~GET"])
			&& count(array_diff(array_keys($queryParams), $arHTMLPagesOptions["~GET"])) === 0)
		{
			return false;
		}

		return true;
	}

	private static function getQueryParams($requestUri)
	{
		$params = array();

		if (isset($requestUri) && ($position = strpos($requestUri, "?")) !== false)
		{
			$queryString = substr($requestUri, $position + 1);
			parse_str($queryString, $params);
		}

		return $params;
	}
	/**
	 * Returns bxrand value
	 *
	 * @return string|false
	 */
	public static function getAjaxRandom()
	{
		if (self::$ajaxRandom === null)
		{
			self::$ajaxRandom = self::removeRandParam();
		}

		return self::$ajaxRandom;
	}

	/**
	 * Returns the instance of the StaticHtmlFileResponse
	 * @param string $cacheKey unique cache identifier
	 * @param array $htmlCacheOptions html cache options
	 * @return StaticHtmlFileResponse|null
	 */
	private static function getHtmlCacheResponse($cacheKey, array $htmlCacheOptions)
	{
		$configuration = array();
		$storage = isset($htmlCacheOptions["STORAGE"]) ? $htmlCacheOptions["STORAGE"] : false;
		if (in_array($storage, array("memcached", "memcached_cluster")))
		{
			if (extension_loaded("memcache"))
			{
				return new StaticHtmlMemcachedResponse($cacheKey, $configuration, $htmlCacheOptions);
			}
			else
			{
				return null;
			}
		}
		else
		{
			return new StaticHtmlFileResponse($cacheKey, $configuration, $htmlCacheOptions);
		}
	}

	private static function setHeaders($etag, $lastModified, $compositeHeader = false)
	{
		if ($etag !== false)
		{
			header("ETag: ".$etag);
		}

		header("Expires: Fri, 07 Jun 1974 04:00:00 GMT");

		if ($lastModified !== false)
		{
			$utc = gmdate("D, d M Y H:i:s", $lastModified)." GMT";
			header("Last-Modified: ".$utc);
		}

		if ($compositeHeader !== false)
		{
			header("X-Bitrix-Composite: Cache (".$compositeHeader.")");
		}
	}

	private static function setStatus($status)
	{
		$bCgi = (stristr(php_sapi_name(), "cgi") !== false);
		$bFastCgi = ($bCgi && (array_key_exists('FCGI_ROLE', $_SERVER) || array_key_exists('FCGI_ROLE', $_ENV)));
		if($bCgi && !$bFastCgi)
			header("Status: ".$status);
		else
			header($_SERVER["SERVER_PROTOCOL"]." ".$status);
	}

	/**
	 * Converts URI to a cache key (file path)
	 * / => /index.html
	 * /index.php => /index.html
	 * /aa/bb/ => /aa/bb/index.html
	 * /aa/bb/index.php => /aa/bb/index.html
	 * /?a=b&b=c => /index@a=b&b=c.html
	 * @param string $uri
	 * @param string $host
	 * @param string $privateKey
	 * @return string
	 */
	public static function convertUriToPath($uri, $host = null, $privateKey = null)
	{
		$uri = "/".trim($uri, "/");
		$parts = explode("?", $uri, 2);

		$uriPath = $parts[0];
		$uriPath = preg_replace("~/index\\.(php|html)$~i", "", $uriPath);
		$uriPath = rtrim(str_replace("..", "__", $uriPath), "/");
		$uriPath .= "/index";

		$queryString = isset($parts[1]) ? $parts[1] : "";
		$queryString = str_replace(".", "_", $queryString);

		$host = self::getHttpHost($host);
		if (strlen($host) > 0)
		{
			$host = "/".$host;
			$host = preg_replace("/:(\\d+)\$/", "-\\1", $host);
		}

		$privateKey = preg_replace("~[^a-z0-9/_]~i", "", $privateKey);
		if (strlen($privateKey) > 0)
		{
			$privateKey = "/".trim($privateKey, "/");
		}

		$cacheKey = $host.$uriPath."@".$queryString.$privateKey.".html";
		return str_replace(array("?", "*"), "_", $cacheKey);
	}

	/**
	 * @deprecated
	 */
	public static function CleanAll()
	{
		$bytes = \Bitrix\Main\Data\StaticHtmlFileStorage::deleteRecursive("/");

		if (class_exists("cdiskquota"))
		{
			CDiskQuota::updateDiskQuota("file", $bytes, "delete");
		}

		self::updateQuota(-$bytes);
	}

	/**
	 * Creates cache file
	 * Old Html Cache
	 * @param string $file_name
	 * @param string $content
	 */
	public static function writeFile($file_name, $content)
	{
		global $USER;
		if(is_object($USER) && $USER->IsAuthorized())
			return;

		$content_len = function_exists('mb_strlen')? mb_strlen($content, 'latin1'): strlen($content);
		if($content_len <= 0)
			return;

		$arHTMLPagesOptions = self::getOptions();

		//Let's be pessimists
		$bQuota = false;

		if(class_exists("cdiskquota"))
		{
			$quota = new CDiskQuota();
			if($quota->checkDiskQuota(array("FILE_SIZE" => $content_len)))
				$bQuota = true;
		}
		else
		{
			$bQuota = true;
		}

		$arStat = self::readStatistic();
		if($arStat)
			$cached_size = $arStat["FILE_SIZE"];
		else
			$cached_size = 0.0;

		$cache_quota = doubleval($arHTMLPagesOptions["~FILE_QUOTA"]);
		if($bQuota && ($cache_quota > 0.0))
		{
			if($cache_quota  < ($cached_size + $content_len))
				$bQuota = false;
		}

		if($bQuota)
		{
			CheckDirPath($file_name);
			$written = 0;
			$tmp_filename = $file_name.md5(mt_rand()).".tmp";
			$file = @fopen($tmp_filename, "wb");
			if($file !== false)
			{
				$written = fwrite($file, $content);
				if($written == $content_len)
				{
					fclose($file);
					if(file_exists($file_name))
						unlink($file_name);
					rename($tmp_filename, $file_name);
					@chmod($file_name, defined("BX_FILE_PERMISSIONS")? BX_FILE_PERMISSIONS: 0664);
					if(class_exists("cdiskquota"))
					{
						CDiskQuota::updateDiskQuota("file", $content_len, "copy");
					}
				}
				else
				{
					$written = 0;
					fclose($file);
					if(file_exists($file_name))
						unlink($file_name);
					if(file_exists($tmp_filename))
						unlink($tmp_filename);
				}
			}

			self::writeStatistic(
				0, //hit
				1, //miss
				0, //quota
				0, //posts
				$written //files
			);
		}
		else
		{
			//Fire cleanup
			$bytes = \Bitrix\Main\Data\StaticHtmlFileStorage::deleteRecursive("/");
			if (class_exists("cdiskquota"))
			{
				CDiskQuota::updateDiskQuota("file", $bytes, "delete");
			}

			self::writeStatistic(0, 0, 1, 0, false);
		}
	}

	/**
	 * Return true if html cache is on
	 * @return bool
	 */
	public static function IsOn()
	{
		return file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages/.enabled");
	}

	/**
	 * Return true if composite mode is enabled
	 * @return bool
	 */
	public static function IsCompositeEnabled()
	{
		if (!self::IsOn())
		{
			return false;
		}

		$options = self::getOptions();
		return isset($options["COMPOSITE"]) && $options["COMPOSITE"] === "Y";
	}

	public static function setEnabled($status, $setDefaults = true)
	{
		$file_name  = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages/.enabled";
		if ($status)
		{
			RegisterModuleDependences("main", "OnEpilog", "main", "CHTMLPagesCache", "OnEpilog");
			RegisterModuleDependences("main", "OnLocalRedirect", "main", "CHTMLPagesCache", "OnEpilog");
			RegisterModuleDependences("main", "OnChangeFile", "main", "CHTMLPagesCache", "OnChangeFile");

			//For very first run we have to fall into defaults
			if ($setDefaults === true)
			{
				self::setOptions();
			}

			if (!file_exists($file_name))
			{
				$f = fopen($file_name, "w");
				fwrite($f, "0,0,0,0,0");
				fclose($f);
				@chmod($file_name, defined("BX_FILE_PERMISSIONS")? BX_FILE_PERMISSIONS: 0664);
			}
		}
		else
		{
			UnRegisterModuleDependences("main", "OnEpilog", "main", "CHTMLPagesCache", "OnEpilog");
			UnRegisterModuleDependences("main", "OnLocalRedirect", "main", "CHTMLPagesCache", "OnEpilog");
			UnRegisterModuleDependences("main", "OnChangeFile", "main", "CHTMLPagesCache", "OnChangeFile");

			if (file_exists($file_name))
			{
				unlink($file_name);
			}
		}
	}

	/**
	 * Saves cache options
	 * @param array $arOptions
	 * @return void
	 */
	public static function setOptions($arOptions = array())
	{
		$arOptions = array_merge(self::getOptions(), $arOptions);
		self::compileOptions($arOptions);

		$file_name = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages/.config.php";
		$tmp_filename = $file_name.md5(mt_rand()).".tmp";
		CheckDirPath($file_name);

		$fh = fopen($tmp_filename, "wb");
		if($fh !== false)
		{
			$content = "<?\n\$arHTMLPagesOptions = array(\n";
			foreach($arOptions as $key => $value)
			{
				if (is_integer($key))
					$phpKey = $key;
				else
					$phpKey = "\"".EscapePHPString($key)."\"";

				if(is_array($value))
				{
					$content .= "\t".$phpKey." => array(\n";
					foreach($value as $key2 => $val)
					{
						if (is_integer($key2))
							$phpKey2 = $key2;
						else
							$phpKey2 = "\"".EscapePHPString($key2)."\"";

						$content .= "\t\t".$phpKey2." => \"".EscapePHPString($val)."\",\n";
					}
					$content .= "\t),\n";
				}
				else
				{
					$content .= "\t".$phpKey." => \"".EscapePHPString($value)."\",\n";
				}
			}
			$content .= ");\n?>";
			$written = fwrite($fh, $content);
			$len = function_exists('mb_strlen')? mb_strlen($content, 'latin1'): strlen($content);
			if($written === $len)
			{
				fclose($fh);
				if(file_exists($file_name))
					unlink($file_name);
				rename($tmp_filename, $file_name);
				@chmod($file_name, defined("BX_FILE_PERMISSIONS")? BX_FILE_PERMISSIONS: 0664);
			}
			else
			{
				fclose($fh);
				if(file_exists($tmp_filename))
					unlink($tmp_filename);
			}

			self::$options = array();
		}
	}

	/**
	 * Returns an array with cache options.
	 * @return array
	 */
	public static function getOptions()
	{
		if (!empty(self::$options))
		{
			return self::$options;
		}

		$arHTMLPagesOptions = array();
		$file_name = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages/.config.php";
		if (file_exists($file_name))
		{
			include($file_name);
		}

		$compile = count(array_diff(self::getCompiledOptions(), array_keys($arHTMLPagesOptions))) > 0;
		$arHTMLPagesOptions = $arHTMLPagesOptions + self::getDefaultOptions();
		if ($compile)
		{
			self::compileOptions($arHTMLPagesOptions);
		}

		self::$options = $arHTMLPagesOptions;
		return self::$options;
	}

	public static function resetOptions()
	{
		self::setOptions(self::getDefaultOptions());
	}

	private static function getDefaultOptions()
	{
		return array(
			"INCLUDE_MASK" => "*.php;*/",
			"EXCLUDE_MASK" => "/bitrix/*;/404.php",
			"FILE_QUOTA" => 100,
			"COMPOSITE" => "N",
			"BANNER_BGCOLOR" => "#E94524",
			"BANNER_STYLE" => "white",
			"STORAGE" => "files",
			"ONLY_PARAMETERS" => "referrer1;r1;referrer2;r2;referrer3;r3;utm_source;utm_medium;utm_campaign;utm_content;fb_action_ids",
			"WRITE_STATISTIC" => "Y",
			"ALLOW_HTTPS" => "N",
		);
	}

	private static function getCompiledOptions()
	{
		return array(
			"INCLUDE_MASK",
			"~INCLUDE_MASK",
			"EXCLUDE_MASK",
			"~EXCLUDE_MASK",
			"FILE_QUOTA",
			"~FILE_QUOTA",
			"~GET",
			"ONLY_PARAMETERS",
			"INDEX_ONLY",
		);
	}

	public static function compileOptions(&$arOptions)
	{
		$arOptions["~INCLUDE_MASK"] = array();
		$inc = str_replace(
			array("\\", ".",  "?", "*",   "'"),
			array("/",  "\\.", ".", ".*?", "\\'"),
			$arOptions["INCLUDE_MASK"]
		);
		$arIncTmp = explode(";", $inc);
		foreach($arIncTmp as $mask)
		{
			$mask = trim($mask);
			if(strlen($mask) > 0)
			{
				$arOptions["~INCLUDE_MASK"][] = "'^".$mask."$'";
			}
		}

		$arOptions["~EXCLUDE_MASK"] = array();
		$exc = str_replace(
			array("\\", ".",  "?", "*",   "'"),
			array("/",  "\\.", ".", ".*?", "\\'"),
			$arOptions["EXCLUDE_MASK"]
		);
		$arExcTmp = explode(";", $exc);
		foreach($arExcTmp as $mask)
		{
			$mask = trim($mask);
			if(strlen($mask) > 0)
			{
				$arOptions["~EXCLUDE_MASK"][] = "'^".$mask."$'";
			}
		}

		if(intval($arOptions["FILE_QUOTA"]) > 0)
		{
			$arOptions["~FILE_QUOTA"] = doubleval($arOptions["FILE_QUOTA"]) * 1024.0 * 1024.0;
		}
		else
		{
			$arOptions["~FILE_QUOTA"] = 0.0;
		}

		$arOptions["INDEX_ONLY"] = isset($arOptions["NO_PARAMETERS"]) && ($arOptions["NO_PARAMETERS"] === "Y");
		$arOptions["~GET"] = array();
		$arTmp = explode(";", $arOptions["ONLY_PARAMETERS"]);
		foreach($arTmp as $str)
		{
			$str = trim($str);
			if(strlen($str) > 0)
			{
				$arOptions["~GET"][] = $str;
			}
		}

		if (function_exists("IsModuleInstalled"))
		{
			$arOptions["COMPRESS"] = IsModuleInstalled('compression');
			$arOptions["STORE_PASSWORD"] = COption::GetOptionString("main", "store_password", "Y");
			$cookie_prefix = COption::GetOptionString('main', 'cookie_name', 'BITRIX_SM');
			$arOptions["COOKIE_LOGIN"] = $cookie_prefix.'_LOGIN';
			$arOptions["COOKIE_PASS"]  = $cookie_prefix.'_UIDH';
			$arOptions["COOKIE_NCC"]  = $cookie_prefix.'_NCC';
			$arOptions["COOKIE_CC"]  = $cookie_prefix.'_CC';
			$arOptions["COOKIE_PK"]  = $cookie_prefix.'_PK';
		}
	}

	/**
	 * Returns array with cache statistics data.
	 * Returns an empty array in case of disabled html cache.
	 *
	 * @return array
	 */
	public static function readStatistic()
	{
		$result = false;
		$fileName = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages/.enabled";
		if (file_exists($fileName) && ($contents = file_get_contents($fileName)) !== false)
		{
			$fileValues = explode(",", $contents);
			$result = array(
				"HITS" => intval($fileValues[0]),
				"MISSES" => intval($fileValues[1]),
				"QUOTA" => intval($fileValues[2]),
				"POSTS" => intval($fileValues[3]),
				"FILE_SIZE" => doubleval($fileValues[4]),
			);
		}

		return $result;
	}

	/**
	 * Updates cache usage statistics.
	 * Each of parameters is added to appropriate existing stats.
	 *
	 * @param integer|false $hits Number of cache hits.
	 * @param integer|false $writings Number of cache writing.
	 * @param integer|false $quota Quota change in bytes.
	 * @param integer|false $posts Number of POST requests.
	 * @param float|false $files File size in bytes.
	 *
	 * @return void
	 */
	public static function writeStatistic($hits = 0, $writings = 0, $quota = 0, $posts = 0, $files = 0.0)
	{
		$options = self::getOptions();
		if ($options["WRITE_STATISTIC"] !== "Y")
		{
			return;
		}

		$fileName = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages/.enabled";
		if (!file_exists($fileName) || ($fp = @fopen($fileName, "r+")) === false)
		{
			return;
		}

		if (@flock($fp, LOCK_EX))
		{
			$fileValues = explode(",", fgets($fp));
			$cacheSize = (isset($fileValues[4]) ? doubleval($fileValues[4]) + doubleval($files) : doubleval($files));
			$newFileValues = array(
				$hits      === false ? 0 : (isset($fileValues[0]) ? intval($fileValues[0]) + $hits     : $hits),
				$writings  === false ? 0 : (isset($fileValues[1]) ? intval($fileValues[1]) + $writings : $writings),
				$quota     === false ? 0 : (isset($fileValues[2]) ? intval($fileValues[2]) + $quota    : $quota),
				$posts     === false ? 0 : (isset($fileValues[3]) ? intval($fileValues[3]) + $posts    : $posts),
				$files     === false ? 0 : $cacheSize > 0 ? $cacheSize : 0,
			);

			fseek($fp, 0);
			ftruncate($fp, 0);
			fwrite($fp, implode(",", $newFileValues));
			flock($fp, LOCK_UN);
		}

		fclose($fp);
	}

	/**
	 * Checks disk quota.
	 * Returns true if quota is not exceeded.
	 *
	 * @return bool
	 */
	public static function checkQuota()
	{
		$arHTMLPagesOptions = self::getOptions();
		$cacheQuota = doubleval($arHTMLPagesOptions["~FILE_QUOTA"]);
		$statistic = self::readStatistic();
		if (count($statistic) > 0)
		{
			$cachedSize = $statistic["FILE_SIZE"];
		}
		else
		{
			$cachedSize = 0.0;
		}

		return ($cachedSize < $cacheQuota);
	}

	/**
	 * Updates disk quota and cache statistic
	 * @param float $bytes positive or negative value
	 */
	public static function updateQuota($bytes)
	{
		if ($bytes == 0.0)
		{
			return;
		}

		self::writeStatistic(0, 0, 0, 0, $bytes);
	}

	public static function setNCC()
	{
		global $APPLICATION;
		$APPLICATION->set_cookie("NCC", "Y");
		$APPLICATION->set_cookie("CC", "", 0);
		self::deleleUserPrivateKey();
	}

	public static function setCC()
	{
		global $APPLICATION;
		$APPLICATION->set_cookie("CC", "Y");
		$APPLICATION->set_cookie("NCC", "", 0);

		$staticHTMLCache = \Bitrix\Main\Data\StaticHtmlCache::getInstance();
		$staticHTMLCache->setUserPrivateKey();
	}

	public static function deleteCompositeCookies()
	{
		global $APPLICATION;
		$APPLICATION->set_cookie("NCC", "", 0);
		$APPLICATION->set_cookie("CC", "", 0);
		self::deleleUserPrivateKey();
	}

	/**
	 * OnUserLogin Event Handler
	 */
	public static function OnUserLogin()
	{
		if (!self::IsOn())
		{
			return;
		}

		if (self::isCurrentUserCC())
		{
			self::setCC();
		}
		else
		{
			self::setNCC();
		}
	}

	public static function isCurrentUserCC()
	{
		global $USER;
		$options = self::getOptions();

		$groups = isset($options["GROUPS"]) && is_array($options["GROUPS"]) ? $options["GROUPS"] : array();
		$groups[] = "2";

		$diff = array_diff($USER->GetUserGroupArray(), $groups);
		return count($diff) === 0;
	}

	/**
	 * OnUserLogout Event Handler
	 */
	public static function OnUserLogout()
	{
		if (self::IsOn())
		{
			self::deleteCompositeCookies();
		}
	}

	/**
	 * OnEpilog Event Handler
	 * @return void
	 */
	public static function OnEpilog()
	{
		if (!self::IsOn())
		{
			return;
		}

		if (self::isCompositeEnabled())
		{
			self::onEpilogComposite();
		}
		else
		{
			self::onEpilogHtmlCache();
		}
	}

	private static function onEpilogComposite()
	{
		global $USER, $APPLICATION;

		if (is_object($USER) && $USER->IsAuthorized())
		{
			if (self::isCurrentUserCC())
			{
				if ($APPLICATION->get_cookie("CC") !== "Y" || $APPLICATION->get_cookie("NCC") === "Y")
				{
					self::setCC();
				}
			}
			else
			{
				if ($APPLICATION->get_cookie("NCC") !== "Y" || $APPLICATION->get_cookie("CC") === "Y")
				{
					self::setNCC();
				}
			}
		}
		else
		{
			if ($APPLICATION->get_cookie("NCC") === "Y" || $APPLICATION->get_cookie("CC") === "Y")
			{
				self::deleteCompositeCookies();
			}
		}

		if (Main\Data\Cache::shouldClearCache())
		{
			$server = Main\Context::getCurrent()->getServer();

			$queryString = DeleteParam(array("clear_cache", "clear_cache_session"));
			$uri = new Bitrix\Main\Web\Uri($server->getRequestUri());
			$refinedUri = $queryString != "" ? $uri->getPath()."?".$queryString : $uri->getPath();

			$cachedFile = self::convertUriToPath($refinedUri, self::getHttpHost());

			$cacheStorage = Bitrix\Main\Data\StaticHtmlCache::getStaticHtmlStorage($cachedFile);
			if ($cacheStorage !== null)
			{
				$bytes = $cacheStorage->delete();
				if ($bytes !== false && $cacheStorage->shouldCountQuota())
				{
					self::updateQuota(-$bytes);
				}
			}
		}
	}

	private static function onEpilogHtmlCache()
	{
		global $USER;

		$bAutorized = is_object($USER) && $USER->IsAuthorized();
		if(!$bAutorized && defined("HTML_PAGES_FILE"))
		{
			@setcookie(session_name(), "", time()-360000, "/");
		}

		$bExcludeByFile = $_SERVER["SCRIPT_NAME"] == "/bitrix/admin/get_start_menu.php";

		$posts = 0;
		$bytes = 0.0;
		$all_clean = false;

		//Check if modifyng action happend
		if(($_SERVER["REQUEST_METHOD"] === "POST") || ($bAutorized && check_bitrix_sessid() && !$bExcludeByFile))
		{
			//if it was admin post
			if(strncmp($_SERVER["REQUEST_URI"], "/bitrix/", 8) === 0)
			{
				//Then will clean all the cache
				$bytes = \Bitrix\Main\Data\StaticHtmlFileStorage::deleteRecursive("/");
				$all_clean = true;
			}
			//check if it was SEF post
			elseif(array_key_exists("SEF_APPLICATION_CUR_PAGE_URL", $_REQUEST) && file_exists($_SERVER['DOCUMENT_ROOT']."/urlrewrite.php"))
			{
				$arUrlRewrite = array();
				include($_SERVER['DOCUMENT_ROOT']."/urlrewrite.php");
				foreach($arUrlRewrite as $val)
				{
					if(preg_match($val["CONDITION"], $_SERVER["REQUEST_URI"]) > 0)
					{
						if (strlen($val["RULE"]) > 0)
							$url = preg_replace($val["CONDITION"], (StrLen($val["PATH"]) > 0 ? $val["PATH"]."?" : "").$val["RULE"], $_SERVER["REQUEST_URI"]);
						else
							$url = $val["PATH"];

						$pos=strpos($url, "?");
						if($pos !== false)
						{
							$url = substr($url, 0, $pos);
						}
						$url = substr($url, 0, strrpos($url, "/")+1);
						$bytes = \Bitrix\Main\Data\StaticHtmlFileStorage::deleteRecursive($url);
						break;
					}
				}
			}
			//public page post
			else
			{
				$folder = substr($_SERVER["REQUEST_URI"], 0, strrpos($_SERVER["REQUEST_URI"], "/"));
				$bytes = \Bitrix\Main\Data\StaticHtmlFileStorage::deleteRecursive($folder);
			}
			$posts++;
		}

		if($bytes > 0.0 && class_exists("cdiskquota"))
		{
			CDiskQuota::updateDiskQuota("file", $bytes, "delete");
		}

		if($posts || $bytes)
		{
			self::writeStatistic(
				0, //hit
				0, //miss
				0, //quota
				$posts, //posts
				($all_clean? false: -$bytes) //files
			);
		}
	}

	/**
	 * OnChangeFile Event Handler
	 * @param $path
	 * @param $site
	 */
	public static function OnChangeFile($path, $site)
	{
		$domains = self::getDomains();
		$bytes = 0.0;
		foreach ($domains as $domain)
		{
			$cachedFile = self::convertUriToPath($path, $domain);
			$cacheStorage = Bitrix\Main\Data\StaticHtmlCache::getStaticHtmlStorage($cachedFile);
			if ($cacheStorage !== null)
			{
				$result = $cacheStorage->delete();
				if ($result !== false && $cacheStorage->shouldCountQuota())
				{
					$bytes += $result;
				}
			}
		}

		self::updateQuota(-$bytes);
	}

	private static function setErrorHandler()
	{
		set_error_handler(array(__CLASS__, "handleError"));
	}

	private static function restoreErrorHandler()
	{
		restore_error_handler();
	}

	public static function handleError($code, $message, $file, $line)
	{
		return true;
	}
}

/**
 * Represents interface for the html cache response
 * Class StaticHtmlCacheResponse
 */
abstract class StaticHtmlCacheResponse
{
	protected $cacheKey = null;
	protected $configuration = array();
	protected $htmlCacheOptions = array();

	/**
	 * @param string $cacheKey unique cache identifier
	 * @param array $configuration storage configuration
	 * @param array $htmlCacheOptions html cache options
	 */
	public function __construct($cacheKey, array $configuration, array $htmlCacheOptions)
	{
		$this->cacheKey = $cacheKey;
		$this->configuration = $configuration;
		$this->htmlCacheOptions = $htmlCacheOptions;
	}

	/**
	 * Returns the cache contents
	 * @return string|false
	 */
	abstract public function getContents();

	/**
	 * Returns the time the cache was last modified
	 * @return int|false
	 */
	abstract public function getLastModified();

	/**
	 * Returns the Entity Tag of the cache
	 * @return string|int
	 */
	abstract public function getEtag();

	/**
	 * Returns the content type of the cache
	 * @return string|false
	 */
	abstract public function getContentType();

	/**
	 * Checks whether the cache exists
	 *
	 * @return bool
	 */
	abstract public function exists();

	/**
	 * Should we count a quota limit
	 * @return bool
	 */
	abstract public function shouldCountQuota();
}

final class StaticHtmlFileResponse extends StaticHtmlCacheResponse
{
	private $cacheFile = null;
	private $lastModified = null;

	public function __construct($cacheKey, array $configuration, array $htmlCacheOptions)
	{
		parent::__construct($cacheKey, $configuration, $htmlCacheOptions);
		$pagesPath = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages";

		if (file_exists($pagesPath.$this->cacheKey))
		{
			$this->cacheFile = $pagesPath.$this->cacheKey;
		}
	}

	public function getContents()
	{
		if ($this->cacheFile === null)
		{
			return false;
		}

		return file_get_contents($this->cacheFile);
	}

	public function getLastModified()
	{
		if ($this->cacheFile === null)
		{
			return false;
		}

		if ($this->lastModified === null)
		{
			$this->lastModified = filemtime($this->cacheFile);
		}

		return $this->lastModified;

	}

	public function getEtag()
	{
		if ($this->cacheFile === null)
		{
			return false;
		}

		return md5(
			$this->cacheFile.
			filesize($this->cacheFile).
			$this->getLastModified()
		);
	}

	public function getContentType()
	{
		return false;
	}

	public function exists()
	{
		return $this->cacheFile !== null;
	}

	/**
	 * Should we count a quota limit
	 * @return bool
	 */
	public function shouldCountQuota()
	{
		return true;
	}
}

final class StaticHtmlMemcachedResponse extends StaticHtmlCacheResponse
{
	/**
	 * @var stdClass
	 */
	private $props = null;

	/**
	 * @var \Memcache
	 */
	private static $memcached = null;
	private static $connected = null;

	public function __construct($cacheKey, array $configuration, array $htmlCacheOptions)
	{
		parent::__construct($cacheKey, $configuration, $htmlCacheOptions);
		self::getConnection($configuration, $htmlCacheOptions);
	}

	public function getContents()
	{
		if (self::$memcached !== null)
		{
			return self::$memcached->get($this->cacheKey);
		}

		return false;
	}

	public function getLastModified()
	{
		return $this->getProp("mtime");
	}

	public function getEtag()
	{
		return $this->getProp("etag");
	}

	public function getContentType()
	{
		return $this->getProp("type");
	}

	public function exists()
	{
		return $this->getProps() !== false;
	}

	/**
	 * Should we count a quota limit
	 * @return bool
	 */
	public function shouldCountQuota()
	{
		return false;
	}

	/**
	 * @param array $htmlCacheOptions html cache options
	 * @return array
	 */
	private static function getServers(array $htmlCacheOptions)
	{
		$arServers = array();
		if ($htmlCacheOptions["STORAGE"] === "memcached_cluster")
		{
			$groupId = isset($htmlCacheOptions["MEMCACHED_CLUSTER_GROUP"]) ? $htmlCacheOptions["MEMCACHED_CLUSTER_GROUP"] : 1;
			$arServers = self::getClusterServers($groupId);
		}
		elseif (isset($htmlCacheOptions["MEMCACHED_HOST"]) && isset($htmlCacheOptions["MEMCACHED_PORT"]))
		{
			$arServers[] = array(
				"HOST" => $htmlCacheOptions["MEMCACHED_HOST"],
				"PORT" => $htmlCacheOptions["MEMCACHED_PORT"]
			);
		}

		return $arServers;
	}

	/**
	 * Gets clusters settings
	 * @param int $groupId
	 * @return array
	 */
	private static function getClusterServers($groupId)
	{
		$arServers = array();

		$arList = false;
		if (file_exists($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/cluster/memcache.php"))
		{
			include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/cluster/memcache.php");
		}

		if (defined("BX_MEMCACHE_CLUSTER") && is_array($arList))
		{
			foreach ($arList as $arServer)
			{
				if ($arServer["STATUS"] === "ONLINE" && $arServer["GROUP_ID"] == $groupId)
				{
					$arServers[] = $arServer;
				}
			}
		}

		return $arServers;
	}

	/**
	 * Returns the object that represents the connection to the memcached server
	 * @param array $configuration memcached configuration
	 * @param array $htmlCacheOptions html cache options
	 * @return Memcache|false
	 */
	public static function getConnection(array $configuration, array $htmlCacheOptions)
	{
		if (self::$memcached === null && self::$connected === null)
		{
			$arServers = self::getServers($htmlCacheOptions);
			$memcached = new \Memcache;
			if (count($arServers) === 1)
			{
				if ($memcached->connect($arServers[0]["HOST"], $arServers[0]["PORT"]))
				{
					self::$connected = true;
					self::$memcached = $memcached;
					register_shutdown_function(array(__CLASS__, "close"));
				}
				else
				{
					self::$connected = false;
				}
			}
			elseif (count($arServers) > 1)
			{
				self::$memcached = $memcached;
				foreach ($arServers as $arServer)
				{
					self::$memcached->addServer(
						$arServer["HOST"],
						$arServer["PORT"],
						true, //persistent
						($arServer["WEIGHT"] > 0? $arServer["WEIGHT"]: 1),
						1 //timeout
					);
				}
			}
			else
			{
				self::$connected = false;
			}
		}

		return self::$memcached;
	}

	/**
	 * Closes connection to the memcached server
	 */
	public static function close()
	{
		if (self::$memcached !== null)
		{
			self::$memcached->close();
			self::$memcached = null;
		}
	}

	/**
	 * Returns an array of the cache properties
	 *
	 * @return \stdClass|false
	 */
	public function getProps()
	{
		if ($this->props === null)
		{
			if (self::$memcached !== null)
			{
				$props = self::$memcached->get("~".$this->cacheKey);
				$this->props = is_object($props) ? $props : false;
			}
			else
			{
				$this->props = false;
			}
		}

		return $this->props;
	}

	/**
	 * Returns the $property value
	 * @param string $property the property name
	 *
	 * @return string|false
	 */
	public function getProp($property)
	{
		$props = $this->getProps();
		if ($props !== false && isset($props->{$property}))
		{
			return $props->{$property};
		}
		return false;
	}
}