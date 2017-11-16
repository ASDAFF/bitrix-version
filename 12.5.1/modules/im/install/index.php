<?
global $MESS;
$PathInstall = str_replace("\\", "/", __FILE__);
$PathInstall = substr($PathInstall, 0, strlen($PathInstall)-strlen("/index.php"));

IncludeModuleLangFile($PathInstall."/install.php");

if(class_exists("im")) return;

Class im extends CModule
{
	var $MODULE_ID = "im";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_GROUP_RIGHTS = "Y";

	function im()
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
			$this->MODULE_VERSION = IM_VERSION;
			$this->MODULE_VERSION_DATE = IM_VERSION_DATE;
		}

		$this->MODULE_NAME = GetMessage("IM_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("IM_MODULE_DESCRIPTION");
	}

	function DoInstall()
	{
		$this->InstallFiles();
		$this->InstallDB();
		$this->InstallEvents();
		$GLOBALS['APPLICATION']->IncludeAdminFile(GetMessage("IM_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/im/install/step1.php");
	}

	function InstallDB()
	{
		global $DB, $APPLICATION;

		$this->errors = false;
		if(!$DB->Query("SELECT 'x' FROM b_im_chat", true))
			$this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/im/install/db/".strtolower($DB->type)."/install.sql");

		if($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("", $this->errors));
			return false;
		}

		RegisterModule("im");
		RegisterModuleDependences('main', 'OnAddRatingVote', 'im', 'CIMEvent', 'OnAddRatingVote');
		RegisterModuleDependences('main', 'OnCancelRatingVote', 'im', 'CIMEvent', 'OnCancelRatingVote');
		RegisterModuleDependences('main', 'OnAfterUserUpdate', 'im', 'CIMEvent', 'OnAfterUserUpdate');
		RegisterModuleDependences("pull", "OnGetDependentModule", "im", "CIMEvent", "OnGetDependentModule");
		RegisterModuleDependences("main", "OnProlog", "main", "", "", 3, "/modules/im/ajax_hit.php");
		RegisterModuleDependences("perfmon", "OnGetTableSchema", "im", "CIMTableSchema", "OnGetTableSchema");
		RegisterModuleDependences("im", "OnGetNotifySchema", "im", "CIMNotifySchema", "OnGetNotifySchema");

		CAgent::AddAgent("CIMMail::MailNotifyAgent();", "im", "N", 600);
		CAgent::AddAgent("CIMMail::MailMessageAgent();", "im", "N", 600);


		$solution = COption::GetOptionString("main", "wizard_solution", false);
		if ($solution == 'community')
		{
			COption::SetOptionString("im", "path_to_user_profile",'/people/user/#user_id#/');
			COption::SetOptionString("im", "path_to_user_lf", '/people/log/');
		}

		CModule::IncludeModule("im");

		if (CIMConvert::ConvertCount() > 0)
		{
			Cmodule::IncludeModule("im");
			CAdminNotify::Add(Array(
				"MESSAGE" => GetMessage("IM_CONVERT_MESSAGE", Array("#A_TAG_START#" => '<a href="/bitrix/admin/im_convert.php?lang='.LANGUAGE_ID.'">', "#A_TAG_END#" => "</a>")),
				"TAG" => "IM_CONVERT",
				"MODULE_ID" => "IM",
				"ENABLE_CLOSE" => "Y"
			));
			CAgent::AddAgent("CIMConvert::UndeliveredMessageAgent();", "im", "N", 20, "", "Y", ConvertTimeStamp(time()+CTimeZone::GetOffset()+20, "FULL"));
		}

		return true;
	}

	function InstallFiles()
	{
		if($_ENV['COMPUTERNAME']!='BX')
		{
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/im/install/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js", true, true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/im/install/components", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", true, true);
			CopyDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/im/install/activities', $_SERVER['DOCUMENT_ROOT'].'/bitrix/activities', true, true);
			CopyDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/im/install/admin', $_SERVER['DOCUMENT_ROOT'].'/bitrix/admin', true, true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/im/install/templates/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/", True, True);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/im/install/public/", $_SERVER["DOCUMENT_ROOT"]."/", True, True);

			$default_site_id = CSite::GetDefSite();
			if ($default_site_id)
			{
				$arAppTempalate = Array(
					"SORT" => 1,
					"CONDITION" => "CSite::InDir('/desktop_app/')",
					"TEMPLATE" => "desktop_app"
				);

				$arFields = Array("TEMPLATE"=>Array());
				$dbTemplates = CSite::GetTemplateList($default_site_id);
				$desktopAppFound = false;
				while($template = $dbTemplates->Fetch())
				{
					if ($template["TEMPLATE"] == "desktop_app")
					{
						$desktopAppFound = true;
						$template = $arAppTempalate;
					}
					$arFields["TEMPLATE"][] = array(
						"TEMPLATE" => $template['TEMPLATE'],
						"SORT" => $template['SORT'],
						"CONDITION" => $template['CONDITION']
					);
				}
				if (!$desktopAppFound)
					$arFields["TEMPLATE"][] = $arAppTempalate;

				$obSite = new CSite;
				$arFields["LID"] = $default_site_id;
				$obSite->Update($default_site_id, $arFields);
			}
			$GLOBALS["APPLICATION"]->SetFileAccessPermission('/desktop_app/', array("*" => "R"));
		}
		return true;
	}

	function InstallEvents()
	{
		global $DB;

		$rs = $DB->Query("SELECT count(*) CNT FROM b_event_type WHERE EVENT_NAME IN ('IM_NEW_NOTIFY', 'IM_NEW_NOTIFY_GROUP', 'IM_NEW_MESSAGE', 'IM_NEW_MESSAGE_GROUP') ", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$ar = $rs->Fetch();
		if($ar["CNT"] <= 0)
			include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/im/install/events/set_events.php");

		return true;
	}

	function DoUninstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION, $step;
		$step = IntVal($step);
		if($step<2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("IM_UNINSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/im/install/unstep1.php");
		}
		elseif($step==2)
		{
			$this->UnInstallDB(array("savedata" => $_REQUEST["savedata"]));
			if(!isset($_REQUEST["saveemails"]) || $_REQUEST["saveemails"] != "Y")
				$this->UnInstallEvents();

			$this->UnInstallFiles();
			$APPLICATION->IncludeAdminFile(GetMessage("IM_UNINSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/im/install/unstep2.php");
		}
	}

	function UnInstallDB($arParams = Array())
	{
		global $APPLICATION, $DB, $errors;

		$this->errors = false;

		if (!$arParams['savedata'])
			$this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/im/install/db/".strtolower($DB->type)."/uninstall.sql");

		if(is_array($this->errors))
			$arSQLErrors = $this->errors;

		if(!empty($arSQLErrors))
		{
			$this->errors = $arSQLErrors;
			$APPLICATION->ThrowException(implode("", $arSQLErrors));
			return false;
		}
		CAdminNotify::DeleteByTag("IM_CONVERT");

		CAgent::RemoveAgent("CIMMail::MailNotifyAgent();", "im");
		CAgent::RemoveAgent("CIMMail::MailMessageAgent();", "im");
		UnRegisterModuleDependences("im", "OnGetNotifySchema", "im", "CIMNotifySchema", "OnGetNotifySchema");
		UnRegisterModuleDependences("perfmon", "OnGetTableSchema", "im", "CIMTableSchema", "OnGetTableSchema");
		UnRegisterModuleDependences('main', 'OnAddRatingVote', 'im', 'CIMEvent', 'OnAddRatingVote');
		UnRegisterModuleDependences('main', 'OnCancelRatingVote', 'im', 'CIMEvent', 'OnCancelRatingVote');
		UnRegisterModuleDependences('main', 'OnAfterUserUpdate', 'im', 'CIMEvent', 'OnAfterUserUpdate');
		UnRegisterModuleDependences("pull", "OnGetDependentModule", "im", "CIMEvent", "OnGetDependentModule");
		UnRegisterModuleDependences("main", "OnProlog", "main", "", "", "/modules/im/ajax_hit.php");
		UnRegisterModule("im");

		return true;
	}

	function UnInstallFiles($arParams = array())
	{
		if($_ENV["COMPUTERNAME"]!='BX')
		{
			DeleteDirFilesEx('/desktop_app/');
			DeleteDirFilesEx('/bitrix/templates/desktop_app/');
		}
		$GLOBALS["APPLICATION"]->SetFileAccessPermission('/desktop_app/', array("*" => "D"));
		return true;
	}

	function UnInstallEvents()
	{
		global $DB;
		include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/im/install/events/del_events.php");
		return true;
	}
}
?>