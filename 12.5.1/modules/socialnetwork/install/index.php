<?
global $MESS;
$strPath2Lang = str_replace("\\", "/", __FILE__);
$strPath2Lang = substr($strPath2Lang, 0, strlen($strPath2Lang)-strlen("/install/index.php"));
include(GetLangFileName($strPath2Lang."/lang/", "/install/index.php"));

Class socialnetwork extends CModule
{
	var $MODULE_ID = "socialnetwork";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $MODULE_GROUP_RIGHTS = "Y";

	function socialnetwork()
	{
		$arModuleVersion = array();

		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
		include($path."/version.php");

		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		}
		else
		{
			$this->MODULE_VERSION = SONET_VERSION;
			$this->MODULE_VERSION_DATE = SONET_VERSION_DATE;
		}

		$this->MODULE_NAME = GetMessage("SONET_INSTALL_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("SONET_INSTALL_DESCRIPTION");
	}

	function __SetLogFilter($site_id = false)
	{
		$arValue = array(
			array(
				"ID" => "work",
				"SORT" => 100,
				"NAME" => "#WORK#",
				"FILTER" => array(
					"EVENT_ID" => array("tasks", "timeman_entry", "report")
				)
			),
			array(
				"ID" => "favorites",
				"SORT" => 200,
				"NAME" => "#FAVORITES#",
				"FILTER" => array(
					"FAVORITES_USER_ID" => "Y"
				)
			),
			array(
				"ID" => "my",
				"SORT" => 300,
				"NAME" => "#MY#",
				"FILTER" => array(
					"CREATED_BY_ID" => "#CURRENT_USER_ID#"
				)
			)
		);

		if (strlen($site_id) > 0)
			$arFilter = array("ID" => $site_id);
		else
			$arFilter = array();

		$dbSites = CSite::GetList(($b = ""), ($o = ""), $arFilter);
		while ($arSite = $dbSites->Fetch())
			CUserOptions::SetOption("socialnetwork", "~log_filter_".$arSite["ID"], $arValue, true, false);
	}

	function InstallDB($install_wizard = true)
	{
		global $DB, $DBType, $APPLICATION, $install_smiles;

		if (!$DB->Query("SELECT 'x' FROM b_sonet_group", true))
		{
			$errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/install/db/".$DBType."/install.sql");
		}

		if (!empty($errors))
		{
			$APPLICATION->ThrowException(implode("", $errors));
			return false;
		}

		RegisterModule("socialnetwork");
		RegisterModuleDependences("search", "OnBeforeFullReindexClear", "socialnetwork", "CSocNetSearchReindex", "OnBeforeFullReindexClear");
		RegisterModuleDependences("search", "OnBeforeIndexDelete", "socialnetwork", "CSocNetSearchReindex", "OnBeforeIndexDelete");
		RegisterModuleDependences("search", "OnReindex", "socialnetwork", "CSocNetSearch", "OnSearchReindex");
		RegisterModuleDependences("search", "OnSearchCheckPermissions", "socialnetwork", "CSocNetSearch", "OnSearchCheckPermissions");
		RegisterModuleDependences("search", "OnBeforeIndexUpdate", "socialnetwork", "CSocNetSearch", "OnBeforeIndexUpdate");
		RegisterModuleDependences("search", "OnAfterIndexAdd", "socialnetwork", "CSocNetSearch", "OnAfterIndexAdd");
		RegisterModuleDependences("search", "OnSearchPrepareFilter", "socialnetwork", "CSocNetSearch", "OnSearchPrepareFilter");
		RegisterModuleDependences("main", "OnUserDelete", "socialnetwork", "CSocNetUser", "OnUserDelete");
		RegisterModuleDependences("main", "OnBeforeUserUpdate", "socialnetwork", "CSocNetUser", "OnBeforeUserUpdate");
		RegisterModuleDependences("main", "OnAfterUserUpdate", "socialnetwork", "CSocNetUser", "OnAfterUserUpdate");
		RegisterModuleDependences("main", "OnAfterUserAdd", "socialnetwork", "CSocNetUser", "OnAfterUserAdd");
		RegisterModuleDependences("main", "OnAfterUserLogout", "socialnetwork", "CSocNetUser", "OnAfterUserLogout");
		RegisterModuleDependences("main", "OnBeforeProlog", "main", "", "", 100, "/modules/socialnetwork/prolog_before.php");
		RegisterModuleDependences("main", "OnBeforeLangDelete", "socialnetwork", "CSocNetGroup", "OnBeforeLangDelete");
		RegisterModuleDependences("socialnetwork", "OnSocNetLogFormatEvent", "socialnetwork", "CSocNetLog", "OnSocNetLogFormatEvent");
		RegisterModuleDependences("photogallery", "OnAfterUpload", "socialnetwork", "CSocNetLogTools", "OnAfterPhotoUpload");
		RegisterModuleDependences("photogallery", "OnAfterPhotoDrop", "socialnetwork", "CSocNetLogTools", "OnAfterPhotoDrop");
		RegisterModuleDependences("photogallery", "OnBeforeSectionDrop", "socialnetwork", "CSocNetLogTools", "OnBeforeSectionDrop");
		RegisterModuleDependences("photogallery", "OnAfterSectionDrop", "socialnetwork", "CSocNetLogTools", "OnAfterSectionDrop");
		RegisterModuleDependences("photogallery", "OnAfterSectionEdit", "socialnetwork", "CSocNetLogTools", "OnAfterSectionEdit");
		RegisterModuleDependences("main", "OnAuthProvidersBuildList", "socialnetwork", "CSocNetGroupAuthProvider", "GetProviders");
		RegisterModuleDependences("im", "OnBeforeConfirmNotify", "socialnetwork", "CSocNetUserToGroup", "OnBeforeConfirmNotify");
		RegisterModuleDependences("im", "OnBeforeConfirmNotify", "socialnetwork", "CSocNetUserRelations", "OnBeforeConfirmNotify");
		RegisterModuleDependences("im", "OnGetNotifySchema", "socialnetwork", "CSocNetNotifySchema", "OnGetNotifySchema");
		RegisterModuleDependences("pull", "OnGetDependentModule", "socialnetwork", "CSocNetPullSchema", "OnGetDependentModule");
		RegisterModuleDependences("extranet", "OnUserInitialize", "socialnetwork", "CSocNetUser", "OnUserInitialize");
		RegisterModuleDependences("blog", "OnBlogDelete", "socialnetwork", "CSocNetLogComments", "OnBlogDelete", 100);
		RegisterModuleDependences("blog", "OnBlogDelete", "socialnetwork", "CSocNetLog", "OnBlogDelete", 200);
		RegisterModuleDependences("blog", "OnBlogPostMentionNotifyIm", "socialnetwork", "CSocNetLogFollow", "OnBlogPostMentionNotifyIm");

		CAgent::AddAgent("CSocNetMessages::SendEventAgent();", "socialnetwork", "N", 600);
		CAgent::AddAgent("CSocNetLog::ClearOldAgent();", "socialnetwork", "N", 43200);

		$arUserOptions = CUserOptions::GetOption("intranet", "~gadgets_sonet_user", false, 0);
		if (!is_array($arUserOptions) || count($arUserOptions) <= 0)
		{
			$sOptions = 'a:1:{s:7:"GADGETS";a:10:{s:18:"SONET_USER_LINKS@1";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:0;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:20:"SONET_USER_FRIENDS@2";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:1;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:21:"SONET_USER_BIRTHDAY@3";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:2;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:19:"SONET_USER_GROUPS@4";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:3;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:17:"SONET_USER_HEAD@5";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:4;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:19:"SONET_USER_HONOUR@6";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:5;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:20:"SONET_USER_ABSENCE@7";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:6;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:17:"SONET_USER_DESC@8";a:4:{s:6:"COLUMN";i:1;s:3:"ROW";i:0;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:22:"SONET_USER_ACTIVITY@21";a:3:{s:6:"COLUMN";i:1;s:3:"ROW";i:1;s:4:"HIDE";s:1:"N";}s:7:"TASKS@9";a:4:{s:6:"COLUMN";i:1;s:3:"ROW";i:2;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}}}';
			$arOptions = unserialize($sOptions);
			CUserOptions::SetOption("intranet", "~gadgets_sonet_user", $arOptions, false, 0);

			$sOptions = 'a:1:{s:7:"GADGETS";a:7:{s:18:"SONET_GROUP_DESC@1";a:3:{s:6:"COLUMN";i:0;s:3:"ROW";i:0;s:4:"HIDE";s:1:"N";}s:16:"UPDATES_ENTITY@9";a:3:{s:6:"COLUMN";i:0;s:3:"ROW";i:1;s:4:"HIDE";s:1:"N";}s:7:"TASKS@4";a:3:{s:6:"COLUMN";i:0;s:3:"ROW";i:2;s:4:"HIDE";s:1:"N";}s:18:"SONET_GROUP_TAGS@5";a:3:{s:6:"COLUMN";i:0;s:3:"ROW";i:3;s:4:"HIDE";s:1:"N";}s:19:"SONET_GROUP_LINKS@6";a:3:{s:6:"COLUMN";i:1;s:3:"ROW";i:0;s:4:"HIDE";s:1:"N";}s:19:"SONET_GROUP_USERS@7";a:3:{s:6:"COLUMN";i:1;s:3:"ROW";i:1;s:4:"HIDE";s:1:"N";}s:18:"SONET_GROUP_MODS@8";a:3:{s:6:"COLUMN";i:1;s:3:"ROW";i:2;s:4:"HIDE";s:1:"N";}}}';
			$arOptions = unserialize($sOptions);
			CUserOptions::SetOption("intranet", "~gadgets_sonet_group", $arOptions, false, 0);
		}

		$this->__SetLogFilter();

		CModule::IncludeModule("socialnetwork");
		if (CModule::IncludeModule("search"))
			CSearch::ReIndexModule("socialnetwork");

		if($install_smiles == "Y" || $install_wizard)
		{
			$dbSmile = CSocNetSmile::GetList();
			if(!($dbSmile->Fetch()))
			{
				$arSmile = Array(
					Array(
						"TYPING" => ":D :-D",
						"IMAGE" => "icon_biggrin.gif",
						"FICON_SMILE" => "FICON_BIGGRIN",
					),
					Array(
						"TYPING" => ":) :-)",
						"IMAGE" => "icon_smile.gif",
						"FICON_SMILE" => "FICON_SMILE",
					),
					Array(
						"TYPING" => ":( :-(",
						"IMAGE" => "icon_sad.gif",
						"FICON_SMILE" => "FICON_SAD",
					),
					Array(
						"TYPING" => ":o :-o :shock:",
						"IMAGE" => "icon_eek.gif",
						"FICON_SMILE" => "FICON_EEK",
					),
					Array(
						"TYPING" => "8) 8-)",
						"IMAGE" => "icon_cool.gif",
						"FICON_SMILE" => "FICON_COOL",
					),
					Array(
						"TYPING" => ":{} :-{}",
						"IMAGE" => "icon_kiss.gif",
						"FICON_SMILE" => "FICON_KISS",
					),
					Array(
						"TYPING" => ":oops:",
						"IMAGE" => "icon_redface.gif",
						"FICON_SMILE" => "FICON_REDFACE",
					),
					Array(
						"TYPING" => ":cry: :~(",
						"IMAGE" => "icon_cry.gif",
						"FICON_SMILE" => "FICON_CRY",
					),
					Array(
						"TYPING" => ":evil: >:-<",
						"IMAGE" => "icon_evil.gif",
						"FICON_SMILE" => "FICON_EVIL",
					),
					Array(
						"TYPING" => ";) ;-)",
						"IMAGE" => "icon_wink.gif",
						"FICON_SMILE" => "FICON_WINK",
					),
					Array(
						"TYPING" => ":!:",
						"IMAGE" => "icon_exclaim.gif",
						"FICON_SMILE" => "FICON_EXCLAIM",
					),
					Array(
						"TYPING" => ":?:",
						"IMAGE" => "icon_question.gif",
						"FICON_SMILE" => "FICON_QUESTION",
					),
					Array(
						"TYPING" => ":idea:",
						"IMAGE" => "icon_idea.gif",
						"FICON_SMILE" => "FICON_IDEA",
					),
					Array(
						"TYPING" => ":| :-|",
						"IMAGE" => "icon_neutral.gif",
						"FICON_SMILE" => "FICON_NEUTRAL",
					),
				);
				$arLang = Array();
				$dbLangs = CLanguage::GetList(($b = ""), ($o = ""), array("ACTIVE" => "Y"));
				while ($arLangs = $dbLangs->Fetch())
				{
					IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/install/smiles.php", $arLangs["LID"]);

					foreach($arSmile as $key => $val)
					{
						$arSmile[$key]["LANG"][] = Array("LID" => $arLangs["LID"], "NAME" => GetMessage($val["FICON_SMILE"]));
					}
				}

				foreach($arSmile as $val)
				{
					$val["SMILE_TYPE"] = "S";
					$val["CLICKABLE"] = "Y";
					$val["SORT"] = 150;
					$val["IMAGE_WIDTH"] = 16;
					$val["IMAGE_HEIGHT"] = 16;
					$id = CSocNetSmile::Add($val);
				}
			}
		}

		return true;
	}

	function UnInstallDB($arParams = Array())
	{
		if (CModule::IncludeModule("search"))
			CSearch::DeleteIndex("socialnetwork");

		global $DB, $DBType, $APPLICATION;
		if(array_key_exists("savedata", $arParams) && $arParams["savedata"] != "Y")
		{
			$errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/install/db/".$DBType."/uninstall.sql");

			if (!empty($errors))
			{
				$APPLICATION->ThrowException(implode("", $errors));
				return false;
			}
		}

		CAgent::RemoveAgent("CSocNetMessages::SendEventAgent();", "socialnetwork");
		CAgent::RemoveAgent("CSocNetLog::ClearOldAgent();", "socialnetwork");

		UnRegisterModuleDependences("main", "OnBeforeProlog", "main", "", "", "/modules/socialnetwork/prolog_before.php");
		UnRegisterModuleDependences("search", "OnBeforeFullReindexClear", "socialnetwork", "CSocNetSearchReindex", "OnBeforeFullReindexClear");
		UnRegisterModuleDependences("search", "OnBeforeIndexDelete", "socialnetwork", "CSocNetSearchReindex", "OnBeforeIndexDelete");
		UnRegisterModuleDependences("search", "OnReindex", "socialnetwork", "CSocNetSearch", "OnSearchReindex");
		UnRegisterModuleDependences("search", "OnSearchCheckPermissions", "socialnetwork", "CSocNetSearch", "OnSearchCheckPermissions");
		UnRegisterModuleDependences("search", "OnBeforeIndexUpdate", "socialnetwork", "CSocNetSearch", "OnBeforeIndexUpdate");
		UnRegisterModuleDependences("search", "OnAfterIndexAdd", "socialnetwork", "CSocNetSearch", "OnAfterIndexAdd");
		UnRegisterModuleDependences("search", "OnSearchPrepareFilter", "socialnetwork", "CSocNetSearch", "OnSearchPrepareFilter");
		UnRegisterModuleDependences("main", "OnUserDelete", "socialnetwork", "CSocNetUser", "OnUserDelete");
		UnRegisterModuleDependences("main", "OnBeforeUserUpdate", "socialnetwork", "CSocNetUser", "OnBeforeUserUpdate");
		UnRegisterModuleDependences("main", "OnAfterUserUpdate", "socialnetwork", "CSocNetUser", "OnAfterUserUpdate");
		UnRegisterModuleDependences("main", "OnAfterUserAdd", "socialnetwork", "CSocNetUser", "OnAfterUserAdd");
		UnRegisterModuleDependences("main", "OnBeforeLangDelete", "socialnetwork", "CSocNetGroup", "OnBeforeLangDelete");
		UnRegisterModuleDependences("socialnetwork", "OnSocNetLogFormatEvent", "socialnetwork", "CSocNetLog", "OnSocNetLogFormatEvent");
		UnRegisterModuleDependences("photogallery", "OnAfterUpload", "socialnetwork", "CSocNetLogTools", "OnAfterPhotoUpload");
		UnRegisterModuleDependences("photogallery", "OnAfterPhotoDrop", "socialnetwork", "CSocNetLogTools", "OnAfterPhotoDrop");
		UnRegisterModuleDependences("photogallery", "OnAfterSectionDrop", "socialnetwork", "CSocNetLogTools", "OnAfterSectionDrop");
		UnRegisterModuleDependences("photogallery", "OnBeforeSectionDrop", "socialnetwork", "CSocNetLogTools", "OnBeforeSectionDrop");
		UnRegisterModuleDependences("photogallery", "OnAfterSectionEdit", "socialnetwork", "CSocNetLogTools", "OnAfterSectionEdit");
		UnRegisterModuleDependences("main", "OnAuthProvidersBuildList", "socialnetwork", "CSocNetGroupAuthProvider", "GetProviders");
		UnRegisterModuleDependences("im", "OnBeforeConfirmNotify", "socialnetwork", "CSocNetUserToGroup", "OnBeforeConfirmNotify");
		UnRegisterModuleDependences("im", "OnBeforeConfirmNotify", "socialnetwork", "CSocNetUserRelations", "OnBeforeConfirmNotify");
		UnRegisterModuleDependences("im", "OnGetNotifySchema", "socialnetwork", "CSocNetNotifySchema", "OnGetNotifySchema");
		UnRegisterModuleDependences("pull", "OnGetDependentModule", "socialnetwork", "CSocNetPullSchema", "OnGetDependentModule");
		UnRegisterModuleDependences("extranet", "OnUserInitialize", "socialnetwork", "CSocNetUser", "OnUserInitialize");
		UnRegisterModuleDependences("blog", "OnBlogDelete", "socialnetwork", "CSocNetLog", "OnBlogDelete");
		UnRegisterModuleDependences("blog", "OnBlogDelete", "socialnetwork", "CSocNetLogComments", "OnBlogDelete");
		UnRegisterModuleDependences("blog", "OnBlogPostMentionNotifyIm", "socialnetwork", "CSocNetLogFollow", "OnBlogPostMentionNotifyIm");
		UnRegisterModule("socialnetwork");

		return true;
	}

	function InstallEvents()
	{
		global $DB;

		$sIn = "'SONET_NEW_MESSAGE', 'SONET_INVITE_FRIEND', 'SONET_INVITE_GROUP', 'SONET_AGREE_FRIEND', 'SONET_BAN_FRIEND', 'SONET_NEW_EVENT_GROUP', 'SONET_NEW_EVENT_USER'";
		$rs = $DB->Query("SELECT count(*) C FROM b_event_type WHERE EVENT_NAME IN (".$sIn.") ", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$ar = $rs->Fetch();
		if($ar["C"] <= 0)
		{
			$pathInMessage = (array_key_exists("public_path", $_REQUEST) ? $_REQUEST["public_path"] : "");
			if (strlen($pathInMessage) <= 0)
			{
				if (file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet"))
					$pathInMessage = "/company/personal/";
				else
					$pathInMessage = "/club/";
			}
			include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/install/events/set_events.php");
		}
		return true;
	}

	function UnInstallEvents()
	{
		global $DB;
		include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/install/events/del_events.php");
		return true;
	}

	function InstallFiles()
	{
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/install/admin", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin", true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/install/images",  $_SERVER["DOCUMENT_ROOT"]."/bitrix/images/socialnetwork", true, True);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/install/sounds",  $_SERVER["DOCUMENT_ROOT"]."/bitrix/sounds", true, True);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/install/themes", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes", true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/install/components", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/install/gadgets", $_SERVER["DOCUMENT_ROOT"]."/bitrix/gadgets", true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/install/tools/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/tools", true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/install/js/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js", true, true);

		return true;
	}

	function InstallPublic()
	{
		$arSite = array();
		$arSites = array();
		$arInstallParams = array();

		//getting params from $_REQUEST
		$dbSites = CSite::GetList(($b = ""), ($o = ""), Array("ACTIVE" => "Y"));
		while ($arSite = $dbSites->GetNext())
		{
			if (strlen($_REQUEST{"install_site_id_".$arSite["ID"]}) > 0)
			{
				$arInstallParams[$arSite["ID"]]["install_site_id"] = $_REQUEST{"install_site_id_".$arSite["ID"]};
				$arInstallParams[$arSite["ID"]]["installPath"] = $_REQUEST{"public_path_".$arSite["ID"]};
				$arInstallParams[$arSite["ID"]]["install404"] = (($_REQUEST{"is404_".$arSite["ID"]} == "Y") ? true : false);
				$arInstallParams[$arSite["ID"]]["installRewrite"] = (($_REQUEST{"public_rewrite_".$arSite["ID"]} == "Y") ? true : false);
				$arSites[] = $arSite;
			}
		}

		//running installation script for each site
		foreach ($arSites as $site)
		{
			$installSiteID = $arInstallParams[$site["ID"]]["install_site_id"];
			$installPath = $arInstallParams[$site["ID"]]["installPath"];
			$install404 = $arInstallParams[$site["ID"]]["install404"];
			$installRewrite = $arInstallParams[$site["ID"]]["installRewrite"];

			include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/install/install_demo.php");
		}
	}

	function UnInstallFiles()
	{
		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/install/admin", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/install/themes/.default/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes/.default");//css
		DeleteDirFilesEx("/bitrix/themes/.default/icons/socialnetwork/");//icons
		DeleteDirFilesEx("/bitrix/images/socialnetwork/");//images
		DeleteDirFilesEx("/bitrix/sounds/socialnetwork/");//sounds

		return true;
	}

	function DoInstall()
	{
		global $APPLICATION, $step;
		$step = IntVal($step);
		if ($step < 2)
			$APPLICATION->IncludeAdminFile(GetMessage("SONET_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/install/step1.php");
		elseif($step==2)
		{
			$this->InstallFiles();
			$this->InstallDB(false);
			$this->InstallEvents();
			$this->InstallPublic();
			$GLOBALS["errors"] = $this->errors;

			$APPLICATION->IncludeAdminFile(GetMessage("SONET_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/install/step2.php");
		}
	}

	function DoUninstall()
	{
		global $APPLICATION, $step;
		$step = IntVal($step);
		if($step<2)
			$APPLICATION->IncludeAdminFile(GetMessage("SONET_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/install/unstep1.php");
		elseif($step==2)
		{
			$this->UnInstallDB(array(
				"savedata" => $_REQUEST["savedata"],
			));
			$this->UnInstallFiles();

			if($_REQUEST["saveemails"] != "Y")
				$this->UnInstallEvents();

			$GLOBALS["errors"] = $this->errors;

			$APPLICATION->IncludeAdminFile(GetMessage("SONET_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/install/unstep2.php");
		}
	}

	function GetModuleRightList()
	{
		$arr = array(
			"reference_id" => array("D", "K", "R", "W"),
			"reference" => array(
					"[D] ".GetMessage("SONETP_PERM_D"),
					"[K] ".GetMessage("SONETP_PERM_K"),
					"[R] ".GetMessage("SONETP_PERM_R"),
					"[W] ".GetMessage("SONETP_PERM_W")
				),
			"use_site" => array("K", "W")
			);
		return $arr;
	}
}
?>