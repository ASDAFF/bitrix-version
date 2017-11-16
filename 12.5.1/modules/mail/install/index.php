<?
global $MESS;
$strPath2Lang = str_replace("\\", "/", __FILE__);
$strPath2Lang = substr($strPath2Lang, 0, strlen($strPath2Lang)-strlen("/install/index.php"));
include(GetLangFileName($strPath2Lang."/lang/", "/install/index.php"));

Class mail extends CModule
{
	var $MODULE_ID = "mail";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;

	function mail()
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
			$this->MODULE_VERSION = MAIL_VERSION;
			$this->MODULE_VERSION_DATE = MAIL_VERSION_DATE;
		}

		$this->MODULE_NAME = GetMessage("MAIL_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("MAIL_MODULE_DESC");
	}

	function InstallDB($arParams = array())
	{
		global $DB, $DBType, $APPLICATION;
		$this->errors = false;

		// Database tables creation
		if(!$DB->Query("SELECT 'x' FROM b_mail_mailbox WHERE 1=0", true))
		{
			$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mail/install/db/".strtolower($DB->type)."/install.sql");
		}

		if($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}
		else
		{
			RegisterModule("mail");
			CModule::IncludeModule("mail");

			CAgent::AddAgent("CMailbox::CleanUp();", "mail", "N", 60*60*24);

			return true;
		}
	}

	function UnInstallDB($arParams = array())
	{
		global $DB, $DBType, $APPLICATION;
		$this->errors = false;

		if(!array_key_exists("savedata", $arParams) || $arParams["savedata"] != "Y")
		{
			$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mail/install/db/".strtolower($DB->type)."/uninstall.sql");
		}

		//delete agents
		CAgent::RemoveModuleAgents("mail");

		UnRegisterModule("mail");

		if($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}

		return true;
	}

	function InstallEvents()
	{
		return true;
	}

	function UnInstallEvents()
	{
		return true;
	}

	function InstallFiles($arParams = array())
	{
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mail/install/images/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/images/mail", true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mail/install/admin/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin", true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mail/install/themes/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes/", true, true);
		return true;
	}

	function UnInstallFiles()
	{
		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mail/install/admin/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mail/install/themes/.default/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes/.default");//css
		DeleteDirFilesEx("/bitrix/themes/.default/icons/mail/");//icons
		DeleteDirFilesEx("/bitrix/images/mail/");//images
		return true;
	}

	function DoInstall()
	{
		global $DB, $APPLICATION, $step;

		if(!CBXFeatures::IsFeatureEditable("SMTP"))
		{
			$APPLICATION->ThrowException(GetMessage("MAIN_FEATURE_ERROR_EDITABLE"));
		}
		else
		{
			$this->InstallFiles();
			$this->InstallDB();
			CBXFeatures::SetFeatureEnabled("SMTP", true);
		}
		$APPLICATION->IncludeAdminFile(GetMessage("MAIL_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mail/install/step1.php");
	}

	function DoUninstall()
	{
		global $DB, $APPLICATION, $step;
		if($step < 2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("MAIL_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mail/install/unstep1.php");
		}
		elseif($step == 2)
		{
			$this->UnInstallDB(array(
					"savedata" => $_REQUEST["savedata"],
			));
			$this->UnInstallFiles();
			CBXFeatures::SetFeatureEnabled("SMTP", false);
			$GLOBALS["errors"] = $this->errors;
			$APPLICATION->IncludeAdminFile(GetMessage("MAIL_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mail/install/unstep2.php");
		}
	}
}
?>