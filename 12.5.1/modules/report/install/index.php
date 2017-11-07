<?
global $MESS;
$strPath2Lang = str_replace("\\", "/", __FILE__);
$strPath2Lang = substr($strPath2Lang, 0, strlen($strPath2Lang)-strlen("/install/index.php"));
include(GetLangFileName($strPath2Lang."/lang/", "/install/index.php"));

Class report extends CModule
{
	var $MODULE_ID = "report";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $errors;

	function report()
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
			$this->MODULE_VERSION = REPORT_VERSION;
			$this->MODULE_VERSION_DATE = REPORT_VERSION_DATE;
		}

		$this->MODULE_NAME = GetMessage("REPORT_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("REPORT_MODULE_DESC");
	}

	function InstallDB($arParams = array())
	{
		global $DB, $DBType, $APPLICATION;
		$this->errors = false;

		// Database tables creation
		if(!$DB->Query("SELECT 'x' FROM b_report WHERE 1=0", true))
		{
			$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/report/install/db/".ToLower($DB->type)."/install.sql");
		}

		if($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}

		RegisterModule("report");

		return true;
	}

	function UnInstallDB($arParams = array())
	{
		global $DB, $DBType, $APPLICATION;
		$this->errors = false;

		if(!array_key_exists("savedata", $arParams) || $arParams["savedata"] != "Y")
		{
			$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/report/install/db/".ToLower($DB->type)."/uninstall.sql");
		}

		UnRegisterModule("report");

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
		global $DB;

		if($_ENV["COMPUTERNAME"]!='BX')
		{
			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/report/install/components",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/components",
				true,
				true
			);

			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/report/install/public/js",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/js",
				true,
				true
			);
		}

		return true;
	}

	function UnInstallFiles()
	{
		DeleteDirFilesEx("/bitrix/js/report/");//scripts
		return true;
	}

	function DoInstall()
	{
		global $DB, $DOCUMENT_ROOT, $APPLICATION;

		if (!IsModuleInstalled("report"))
		{
			$this->InstallFiles();
			$this->InstallDB();
			$this->InstallEvents();

			$GLOBALS["errors"] = $this->errors;
			$APPLICATION->IncludeAdminFile(GetMessage("REPORT_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/report/install/step1.php");
		}
	}

	function DoUninstall()
	{
		global $DB, $DOCUMENT_ROOT, $APPLICATION, $step;
		$step = IntVal($step);
		if($step < 2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("REPORT_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/report/install/unstep1.php");
		}
		elseif($step == 2)
		{
			$this->UnInstallDB(array(
					"savedata" => $_REQUEST["savedata"],
			));
			$this->UnInstallFiles();
			$this->UnInstallEvents();
			$GLOBALS["errors"] = $this->errors;
			$APPLICATION->IncludeAdminFile(GetMessage("REPORT_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/report/install/unstep2.php");
		}
	}
}
?>