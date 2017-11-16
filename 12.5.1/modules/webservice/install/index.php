<?
global $MESS;
$strPath2Lang = str_replace("\\", "/", __FILE__);
$strPath2Lang = substr($strPath2Lang, 0, strlen($strPath2Lang)-18);
include(GetLangFileName($strPath2Lang."/lang/", "/install/index.php"));

class webservice extends CModule
{
	var $MODULE_ID = "webservice";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_GROUP_RIGHTS = "N";

	function webservice()
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
			$this->MODULE_VERSION = $WEBSERVICE_VERSION;
			$this->MODULE_VERSION_DATE = $WEBSERVICE_VERSION_DATE;
		}

		$this->MODULE_NAME = GetMessage("WEBS_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("WEBS_MODULE_DESCRIPTION");
	}

	function DoInstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION;
		
		$this->InstallFiles();
		$this->InstallDB();
		
		$APPLICATION->IncludeAdminFile(GetMessage("WEBS_INSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/webservice/install/step.php");
	}

	function InstallFiles()
	{
		CheckDirPath($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/webservice.checkauth");
		CheckDirPath($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/webservice.server");
		//CheckDirPath($_SERVER["DOCUMENT_ROOT"]."/ws");

		CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/webservice/install/components/bitrix/webservice.checkauth", 
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/webservice.checkauth", 
			true, true);
			
		CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/webservice/install/components/bitrix/webservice.server", 
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/webservice.server", 
			true, true);

		CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/webservice/install/components/bitrix/webservice.statistic",
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/webservice.statistic",
			true, true);

		CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/webservice/install/tools", 
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/tools", 
			true, true);
		
		/*
		CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/webservice/install/ws", 
			$_SERVER["DOCUMENT_ROOT"]."/ws", 
			false);
		*/
		
		return true;
	}
	
	function InstallDB()
	{
		RegisterModule("webservice");
		
		return true;
	}
	
	function InstallEvents()
	{
		return true;
	}
	
	function DoUninstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION;
		
		$this->UnInstallFiles();
		$this->UnInstallDB();
		
		$APPLICATION->IncludeAdminFile(GetMessage("WEBS_UNINSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/webservice/install/unstep.php");
	}
	
	function UnInstallDB()
	{
		UnRegisterModule("webservice");

		return true;
	}
	
	function UnInstallFiles()
	{
		DeleteDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/webservice.checkauth");
		DeleteDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/webservice.server");
		
		return true;
	}
	
	function UnInstallEvents()
	{
		return true;
	}
}
?>