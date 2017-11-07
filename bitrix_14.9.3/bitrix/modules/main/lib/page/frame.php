<?php
namespace Bitrix\Main\Page;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

final class Frame
{
	private static $instance;
	private static $isEnabled = false;
	private static $isAjaxRequest = null;
	private static $useHTMLCache = false;
	private static $onBeforeHandleKey = false;
	private static $onHandleKey = false;
	private static $onRestartBufferHandleKey = false;
	private $dynamicIDs = array();
	private $dynamicData = array();
	private $containers = array();
	private $curDynamicId = false;
	private $injectedJS = false;

	public $arDynamicData = array();

	private function __construct()
	{
		//use self::getInstance()
	}

	private function __clone()
	{
		//you can't clone it
	}

	/**
	 * Singleton instance.
	 *
	 * @return Frame
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = new Frame();
		}

		return self::$instance;
	}

	/**
	 * Gets ids of the dynamic blocks.
	 *
	 * @return array
	 */
	public function getDynamicIDs()
	{
		return array_keys($this->dynamicIDs);
	}

	/**
	 * Returns the identifier of current dynamic area.
	 *
	 * @return string|false
	*/
	public function getCurrentDynamicId()
	{
		return $this->curDynamicId;
	}

	/**
	 * Adds dynamic data to be sent to the client.
	 *
	 * @param string $ID Unique identifier of the block.
	 * @param string $content Dynamic part html.
	 * @param string $stub Html to use as stub.
	 * @param string $containerId Identifier of the html container.
	 * @param boolean $useBrowserStorage Use browser storage for caching or not.
	 * @param boolean $autoUpdate Automatically or manually update block contents.
	 * @param boolean $useAnimation Animation flag.
	 *
	 * @return void
	 */
	public function addDynamicData($ID, $content, $stub = "", $containerId = null, $useBrowserStorage = false, $autoUpdate = true, $useAnimation = false)
	{
		$this->dynamicIDs[$ID] = array(
			"stub" => $stub,
			"use_browser_storage" => $useBrowserStorage,
			"auto_update" => $autoUpdate,
			"use_animation" => $useAnimation,
		);
		$this->dynamicData[$ID] = $content;
		if ($containerId !== null)
			$this->containers[$ID] = $containerId;
	}

	/**
	 * Sets isEnable property value and attaches needed handlers.
	 *
	 * @param bool $isEnabled Mode control flag.
	 *
	 * @return void
	 */
	public static function setEnable($isEnabled = true)
	{
		if ($isEnabled && !self::$isEnabled)
		{
			self::$onBeforeHandleKey = AddEventHandler("main", "OnBeforeEndBufferContent", array(self::getInstance(), "OnBeforeEndBufferContent"));
			self::$onHandleKey = AddEventHandler("main", "OnEndBufferContent", array(self::getInstance(), "OnEndBufferContent"));
			self::$onRestartBufferHandleKey = AddEventHandler("main", "OnBeforeRestartBuffer", array(self::getInstance(), "OnBeforeRestartBuffer"));
			self::$isEnabled = true;
			\CJSCore::init(array("fc"), false);
		}
		elseif (!$isEnabled && self::$isEnabled)
		{
			if (self::$onBeforeHandleKey >= 0)
			{
				RemoveEventHandler("main", "OnBeforeEndBufferContent", self::$onBeforeHandleKey);
			}

			if (self::$onBeforeHandleKey >= 0)
			{
				RemoveEventHandler("main", "OnEndBufferContent", self::$onHandleKey);
			}

			if (self::$onRestartBufferHandleKey >= 0)
			{
				RemoveEventHandler("main", "OnBeforeRestartBuffer", self::$onRestartBufferHandleKey);
			}

			self::$isEnabled = false;
		}
	}

	/**
	 * Gets isEnabled property.
	 *
	 * @return boolean
	 */
	public static function isEnabled()
	{
		return self::$isEnabled;
	}

	/**
	 * Marks start of a dynamic block.
	 *
	 * @param integer $ID Unique identifier of the block.
	 *
	 * @return boolean
	 */
	public function startDynamicWithID($ID)
	{
		if (isset($this->dynamicIDs[$ID])
			|| $ID == $this->curDynamicId
			|| ($this->curDynamicId && !isset($this->dynamicIDs[$this->curDynamicId]))
		)
		{
			return false;
		}

		echo '<!--\'start_frame_cache_'.$ID.'\'-->';

		$this->curDynamicId = $ID;

		return true;
	}

	/**
	 * Marks end of the dynamic block if it's the current dynamic block
	 * and its start was being marked early.
	 *
	 * @param string $ID Unique identifier of the block.
	 * @param string $stub Html to use as stub.
	 * @param string $containerId Identifier of the html container.
	 * @param boolean $useBrowserStorage Use browser storage for caching or not.
	 * @param boolean $autoUpdate Automatically or manually update block contents.
	 * @param boolean $useAnimation Animation flag.
	 *
	 * @return boolean
	 */
	public function finishDynamicWithID($ID, $stub = "", $containerId = null, $useBrowserStorage = false, $autoUpdate = true, $useAnimation = false)
	{
		if ($this->curDynamicId !== $ID)
		{
			return false;
		}

		echo '<!--\'end_frame_cache_'.$ID.'\'-->';

		$this->curDynamicId = false;
		$this->dynamicIDs[$ID] = array(
			"stub" => $stub,
			"use_browser_storage" => $useBrowserStorage,
			"auto_update" => $autoUpdate,
			"use_animation" => $useAnimation,
		);
		if ($containerId !== null)
			$this->containers[$ID] = $containerId;

		return true;
	}

	/**
	 * This method returns the divided content.
	 * The content is divided by two parts - static and dynamic.
	 * Example of returned value:
	 * <code>
	 * array(
	 *    "static"=>"Hello World!"
	 *    "dynamic"=>array(
	 *        array("ID"=>"someID","CONTENT"=>"someDynamicContent", "HASH"=>"md5ofDynamicContent")),
	 *        array("ID"=>"someID2","CONTENT"=>"someDynamicContent2", "HASH"=>"md5ofDynamicContent2"))
	 * );
	 * </code>
	 *
	 * @param string $content Html page content.
	 *
	 * @return array
	 */
	public function getDividedPageData($content)
	{
		$data = array(
			"dynamic" => array(),
			"static"  => "",
			"md5"     => "",
		);

		if ($this->dynamicIDs) //Do we have any dynamic blocks?
		{
			$dynamicKeys = implode('|', array_keys($this->dynamicIDs));
			$match = array();
			$regexp = '/<!--\'start_frame_cache_('.$dynamicKeys.')\'-->(.+?)<!--\'end_frame_cache_(?:'.$dynamicKeys.')\'-->/is';
			if (preg_match_all($regexp, $content, $match))
			{
				/*
					Notes:
					$match[0] -	an array of dynamic blocks with macros'
					$match[1] - ids of dynamic blocks
					$match[2] - array of dynamic blocks
				*/
				$replacedArray = array();
				foreach ($match[1] as $i => $id)
				{
					$data["dynamic"][] = $this->arDynamicData[] = array(
						"ID" => isset($this->containers[$id]) ? $this->containers[$id] : "bxdynamic_".$id,
						"CONTENT" => isset($this->dynamicData[$id]) ? $this->dynamicData[$id] : $match[2][$i],
						"HASH" => md5(isset($this->dynamicData[$id]) ? $this->dynamicData[$id] : $match[2][$i]),
						"PROPS"=> array(
							"USE_BROWSER_STORAGE" => $this->dynamicIDs[$id]["use_browser_storage"],
							"AUTO_UPDATE" => $this->dynamicIDs[$id]["auto_update"],
							"USE_ANIMATION" => $this->dynamicIDs[$id]["use_animation"]
						)
					);

					if (isset($this->containers[$id]))
					{
						$replacedArray[] = $this->dynamicIDs[$id]["stub"];
					}
					else
					{
						$replacedArray[] = '<div id="bxdynamic_'.$id.'">'.$this->dynamicIDs[$id]["stub"].'</div>';
					}
				}

				$data["static"] = str_replace($match[0], $replacedArray, $content);
			}
			else
			{
				$data["static"] = $content;
			}
		}
		else
		{
			$data["static"] = $content;
		}


		$methodInvocations = bitrix_sessid_post("sessid", true);
		if ($methodInvocations > 0)
		{
			$data["static"] = str_replace("value=\"".bitrix_sessid()."\"", "value=\"\"", $data["static"]);
		}

		$data["md5"] = md5($data["static"]);

		return $data;
	}

	/**
	 * OnBeforeEndBufferContent handler.
	 * Prepares the stage for composite mode handler.
	 *
	 * @return void
	 */
	public function onBeforeEndBufferContent()
	{
		global $APPLICATION;
		$frame = self::getInstance();
		$params = array();

		if ($frame->getUseAppCache())
		{
			$manifest = \Bitrix\Main\Data\AppCacheManifest::getInstance();
			$params = $manifest->OnBeforeEndBufferContent();
			$params["CACHE_MODE"] = "APPCACHE";
			$params["PAGE_URL"] = \Bitrix\Main\Context::getCurrent()->getServer()->getRequestUri();
		}
		elseif ($frame->getUseHTMLCache())
		{
			$staticHTMLCache = \Bitrix\Main\Data\StaticHtmlCache::getInstance();

			if ($staticHTMLCache->isCacheable())
			{
				$params["CACHE_MODE"] = "HTMLCACHE";

				if (\Bitrix\Main\Config\Option::get("main", "~show_composite_banner", "Y") == "Y")
				{
					$options = \CHTMLPagesCache::GetOptions();
					$params["banner"] = array(
						"url" => GetMessage("COMPOSITE_BANNER_URL"),
						"text" => GetMessage("COMPOSITE_BANNER_TEXT"),
						"bgcolor" => isset($options["BANNER_BGCOLOR"]) ? $options["BANNER_BGCOLOR"] : "",
						"style" => isset($options["BANNER_STYLE"]) ? $options["BANNER_STYLE"] : ""
					);
				}
			}
			else
			{
				return;
			}
		}

		$params["storageBlocks"] = array();
		foreach ($frame->dynamicIDs as $id => $dynamicData)
		{
			if ($dynamicData["use_browser_storage"])
			{
				$realId = isset($this->containers[$id]) ? $this->containers[$id] : "bxdynamic_".$id;
				$params["storageBlocks"][] = $realId;
			}
		}

		$frame->injectedJS = $frame->getInjectedJs($params);
		$APPLICATION->AddHeadString($this->injectedJS["start"], false, "BEFORE_CSS");

		//When dynamic hit we'll throw spread cookies away
		if ($frame->getUseHTMLCache() && $staticHTMLCache->isCacheable())
		{
			$APPLICATION->HoldSpreadCookieHTML(true);
			\CJSCore::SetCompositeMode(true);
		}
	}

	/**
	 * OnEndBufferContent handler
	 * There are two variants of content's modification in this method.
	 * The first one:
	 * If it's ajax-hit the content will be replaced by json data with dynamic blocks,
	 * javascript files and etc. - dynamic part
	 *
	 * The second one:
	 * If it's simple hit the content will be modified also,
	 * all dynamic blocks will be cutted out of the content - static part.
	 *
	 * @param string &$content Html page content.
	 *
	 * @return void
	 */
	public function onEndBufferContent(&$content)
	{
		global $APPLICATION;

		$dividedData = self::getInstance()->getDividedPageData($content);
		$htmlCacheChanged = false;

		if (self::getUseHTMLCache())
		{
			$isLicenseExpired = self::isLicenseExpired();
			$staticHTMLCache = \Bitrix\Main\Data\StaticHtmlCache::getInstance();
			if ($staticHTMLCache->isCacheable())
			{
				$cacheExists = $staticHTMLCache->exists();
				if (
					(!$cacheExists || $staticHTMLCache->getMd5() !== $dividedData["md5"])
					&& (!\CHTMLPagesCache::isIE9()) //Temporary compatibility checking. If nginx doesn't skip IE9
				)
				{
					if ($cacheExists)
					{
						$staticHTMLCache->delete();
					}

					if (!$isLicenseExpired)
					{
						$staticHTMLCache->write($dividedData["static"], $dividedData["md5"]);
					}
				}
				else if ($isLicenseExpired)
				{
					$staticHTMLCache->delete();
				}

				$frame = self::getInstance();

				$ids = $frame->getDynamicIDs();
				foreach ($ids as $i => $id)
				{
					if (isset($frame->containers[$id]))
						unset($ids[$i]);
				}

				$dividedData["static"] = preg_replace(
					array(
						'/<!--\'start_frame_cache_('.implode("|", $ids).')\'-->/',
						'/<!--\'end_frame_cache_('.implode("|", $ids).')\'-->/',
					),
					array(
						'<div id="bxdynamic_\1">',
						'</div>',
					),
					$content
				);

				if ($frame->injectedJS && isset($frame->injectedJS["start"]))
				{
					$dividedData["static"] = str_replace($frame->injectedJS["start"], "", $dividedData["static"]);
				}
			}
			else
			{
				//TODO: If it's ajax request a browser will receive html page instead of JSON
				$staticHTMLCache->delete();
				return;
			}
		}

		if (self::getUseAppCache() == true) //Do we use html5 application cache?
		{
			\Bitrix\Main\Data\AppCacheManifest::getInstance()->generate($dividedData["static"]);
		}
		else
		{
			\Bitrix\Main\Data\AppCacheManifest::checkObsoleteManifest();
		}

		if (self::isAjaxRequest()) //Is it a check request?
		{
			$bxRandom = \CHTMLPagesCache::getAjaxRandom();
			if ($bxRandom !== false)
			{
				header("BX-RAND: ".$bxRandom);
			}

			header("Content-Type: application/x-javascript; charset=".SITE_CHARSET);
			$content = array(
				"js"                => $APPLICATION->arHeadScripts,
				"additional_js"     => $APPLICATION->arAdditionalJS,
				"lang"              => \CJSCore::GetCoreMessages(),
				"css"               => $APPLICATION->GetCSSArray(),
				"htmlCacheChanged"  => $htmlCacheChanged,
				"isManifestUpdated" => \Bitrix\Main\Data\AppCacheManifest::getInstance()->getIsModified(),
				"dynamicBlocks"     => $dividedData["dynamic"],
				"spread"            => array_map(array("CUtil", "JSEscape"), $APPLICATION->GetSpreadCookieUrls()),
			);

			$content = \CUtil::PhpToJSObject($content);
		}
		else
		{
			$content = $dividedData["static"];
		}
	}

	/**
	 * OnBeforeRestartBuffer event handler.
	 * Disables composite mode when called.
	 *
	 * @return void
	 */
	public function OnBeforeRestartBuffer()
	{
		$this->setEnable(false);
		if (defined("BX_COMPOSITE_DEBUG"))
		{
			AddMessage2Log(
				"RestartBuffer method was invoked\n".
				"Request URI: ".$_SERVER["REQUEST_URI"]."\n".
				"Script: ".(isset($_SERVER["REAL_FILE_PATH"]) ? $_SERVER["REAL_FILE_PATH"] : $_SERVER["SCRIPT_NAME"]),
				"composite"
			);
		}
	}
	/**
	 * Sets useAppCache property.
	 *
	 * @param boolean $useAppCache AppCache mode control flag.
	 *
	 * @return void
	 */
	public function setUseAppCache($useAppCache = true)
	{
		if (self::getUseAppCache())
			self::getInstance()->setUseHTMLCache(false);
		$appCache = \Bitrix\Main\Data\AppCacheManifest::getInstance();
		$appCache->setEnabled($useAppCache);
	}

	/**
	 * Gets useAppCache property.
	 *
	 * @return boolean
	 */
	public function getUseAppCache()
	{
		$appCache = \Bitrix\Main\Data\AppCacheManifest::getInstance();
		return $appCache->isEnabled();
	}

	/**
	 * Sets useHTMLCache property.
	 *
	 * @param boolean $useHTMLCache Composite mode control flag.
	 *
	 * @return void
	 */
	public static function setUseHTMLCache($useHTMLCache = true)
	{
		self::$useHTMLCache = $useHTMLCache;
		self::setEnable();
	}

	/**
	 * Gets useHTMLCache property.
	 *
	 * @return boolean
	 */
	public static function getUseHTMLCache()
	{
		return self::$useHTMLCache;
	}

	/**
	 * Returns true if current request was initiated by Ajax.
	 *
	 * @return boolean
	 */
	public static function isAjaxRequest()
	{
		if (self::$isAjaxRequest == null)
		{
			$actionType = \Bitrix\Main\Context::getCurrent()->getServer()->get("HTTP_BX_ACTION_TYPE");
			self::$isAjaxRequest = (
				$actionType == "get_dynamic"
				|| (
					defined("actionType")
					&& constant("actionType") == "get_dynamic"
				)
			);
		}

		return self::$isAjaxRequest;
	}

	/**
	 * Returns JS minified code that will do dynamic hit to the server.
	 * The code is returned in the 'start' key of the array.
	 *
	 * @return array[string]string
	 */
	protected function getInjectedJs($params = array())
	{
		$vars = \CUtil::PhpToJSObject($params);
		$inlineJS = <<<JS
			(function(w) {

			var v = w.frameCacheVars = $vars;
			var r = w.XMLHttpRequest ? new XMLHttpRequest() : (w.ActiveXObject ? new w.ActiveXObject("Microsoft.XMLHTTP") : null);
			if (!r) { return; }

			w.frameRequestStart = true;
			var m = v.CACHE_MODE; var l = w.location; var x = new Date().getTime();
			var q = "?bxrand=" + x + (l.search.length > 0 ? "&" + l.search.substring(1) : "");
			var u = l.protocol + "//" + l.host + l.pathname + q;

			r.open("GET", u, true);
			r.setRequestHeader("BX-ACTION-TYPE", "get_dynamic");
			r.setRequestHeader("BX-REF", document.referrer || "");
			r.setRequestHeader("BX-CACHE-MODE", m);

			if (m === "APPCACHE")
			{
				r.setRequestHeader("BX-APPCACHE-PARAMS", JSON.stringify(v.PARAMS));
				r.setRequestHeader("BX-APPCACHE-URL", v.PAGE_URL ? v.PAGE_URL : "");
			}

			r.onreadystatechange = function() {
				if (r.readyState != 4) { return; }
				var a = r.getResponseHeader("BX-RAND");
				var b = w.BX && w.BX.frameCache ? w.BX.frameCache : false;
				if (a != x || !((r.status >= 200 && r.status < 300) || r.status === 304 || r.status === 1223 || r.status === 0))
				{
					if (w.BX)
					{
						BX.ready(function() { BX.onCustomEvent("onFrameDataRequestFail"); });
					}
					else
					{
						w.frameRequestFail = false;
					}
					return;
				}

				if (b)
				{
					b.onFrameDataReceived(r.responseText);
					if (!w.frameUpdateInvoked)
					{
						b.update(false);
					}
					w.frameUpdateInvoked  = true;
				}
				else
				{
					w.frameDataString = r.responseText;
				}
			};
			r.send();

			})(window);
JS;

		return array(
			"start" => "<style>".str_replace(array("\n", "\t"), "", self::getInjectedCSS())."</style>\n".
						"<script>".str_replace(array("\n", "\t"), "", $inlineJS)."</script>"
		);
	}

	/**
	 * Returns css string to be injected.
	 *
	 * @return string
	 */
	public static function getInjectedCSS()
	{
		return <<<CSS

			.bx-composite-btn {
				background: url(/bitrix/images/main/composite/bx-white-logo.png) no-repeat right 5px #e94524;
				border-radius: 15px;
				color: #ffffff !important;
				display: inline-block;
				line-height: 30px;
				font-family: "Helvetica Neue", Helvetica, Arial, sans-serif !important;
				font-size: 12px !important;
				font-weight: bold !important;
				height: 31px !important;
				padding: 0 42px 0 17px !important;
				vertical-align: middle !important;
				text-decoration: none !important;
			}

			.bx-composite-btn-fixed {
				position: absolute;
				top: -45px;
				right: 15px;
				z-index: 10;
			}

			.bx-btn-white {
				background-image: url(/bitrix/images/main/composite/bx-white-logo.png);
				color: #fff !important;
			}

			.bx-btn-black {
				background-image: url(/bitrix/images/main/composite/bx-black-logo.png);
				color: #000 !important;
			}

			.bx-btn-grey {
				background-image: url(/bitrix/images/main/composite/bx-grey-logo.png);
				color: #657b89 !important;
			}

			.bx-btn-red {
				background-image: url(/bitrix/images/main/composite/bx-red-logo.png);
				color: #555 !important;
			}

			.bx-btn-border {
				border: 1px solid #d4d4d4;
				background-position: right 5px;
				height: 29px !important;
				line-height: 29px !important;
			}
CSS;
	}

	/**
	 * Checks whether HTML Cache should be enabled.
	 *
	 * @return void
	 */
	public static function shouldBeEnabled()
	{
		if(defined("USE_HTML_STATIC_CACHE") && USE_HTML_STATIC_CACHE === true)
		{
			if(!defined("BX_SKIP_SESSION_EXPAND") && (!defined("ADMIN_SECTION") || (defined("ADMIN_SECTION") && ADMIN_SECTION != "Y")))
			{
				self::setUseHTMLCache();
				define("BX_SKIP_SESSION_EXPAND", true);
			}
		}
		elseif (
			(defined("ENABLE_HTML_STATIC_CACHE_JS") && ENABLE_HTML_STATIC_CACHE_JS === true) &&
			(!defined("ADMIN_SECTION") || ADMIN_SECTION !== true))
		{
			\CJSCore::init(array("fc")); //to warm up localStorage
		}
	}

	/**
	 * Checks if admin panel will be shown or not.
	 * Disables itself if panel will be show.
	 *
	 * @return void
	 */
	public static function checkAdminPanel()
	{
		if ($GLOBALS["APPLICATION"]->showPanelWasInvoked === true
			&& self::getUseHTMLCache()
			&& !self::isAjaxRequest()
			&& \CTopPanel::shouldShowPanel()
		)
		{
			self::setEnable(false);
		}
	}

	/**
	 * Returns true if composite mode is allowed by checking update system parameters.
	 *
	 * @return boolean
	 */
	public static function isLicenseExpired()
	{
		$finishDate = \Bitrix\Main\Config\Option::get("main", "~support_finish_date", "");
		$composite = \Bitrix\Main\Config\Option::get("main", "~PARAM_COMPOSITE", "N");
		if ($composite == "Y" || $finishDate == "")
		{
			return false;
		}

		$finishDate = new \Bitrix\Main\Type\Date($finishDate, "Y-m-d");
		return $finishDate->getTimestamp() < time();
	}
}
