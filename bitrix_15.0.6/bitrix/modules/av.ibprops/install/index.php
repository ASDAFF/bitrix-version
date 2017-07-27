<?
IncludeModuleLangFile(__FILE__);
Class av_ibprops extends CModule
{
	const MODULE_ID = 'av.ibprops';
	var $MODULE_ID = 'av.ibprops';
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $strError = '';

	function __construct()
	{
		$arModuleVersion = array();
		include(dirname(__FILE__)."/version.php");
		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		$this->MODULE_NAME = GetMessage("AV_IBPROPS_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("AV_IBPROPS_MODULE_DESC");

		$this->PARTNER_NAME = GetMessage("AV_IBPROPS_PARTNER_NAME");
		$this->PARTNER_URI = GetMessage("AV_IBPROPS_PARTNER_URI");
	}

	function InstallDB($arParams = array())
	{
		RegisterModuleDependences('main', 'OnBuildGlobalMenu', self::MODULE_ID, 'C_AV_ibprops_service', 'OnBuildGlobalMenu');
		return true;
	}

	function UnInstallDB($arParams = array())
	{
		UnRegisterModuleDependences('main', 'OnBuildGlobalMenu', self::MODULE_ID, 'C_AV_ibprops_service', 'OnBuildGlobalMenu');
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
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/av.ibprops/install/admin/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin", true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/av.ibprops/install/tools/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/tools", true, true);
		return true;
	}

	function UnInstallFiles()
	{
		DeleteDirFilesEx('/bitrix/tools/av.ibprops/');
		DeleteDirFilesEx('/bitrix/tmp/av.ibprops/');
		unlink($_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/ibprops_manage.php");
	}

	function DoInstall()
	{
		global $APPLICATION;
		$this->InstallFiles();
		$this->InstallDB();
		RegisterModule(self::MODULE_ID);
		$this->ShowForm();
	}

	function DoUninstall()
	{
		global $APPLICATION;
		UnRegisterModule(self::MODULE_ID);
		$this->UnInstallDB();
		$this->UnInstallFiles();
	}

	private function ShowForm()
	{
		IncludeModuleLangFile(__FILE__);
		
		$keys = array_keys($GLOBALS);
		for($i=0; $i<count($keys); $i++)
			if($keys[$i]!='i' && $keys[$i]!='GLOBALS' && $keys[$i]!='strTitle' && $keys[$i]!='filepath')
				global ${$keys[$i]};
				
		$APPLICATION->SetTitle(GetMessage('AV_IBPROPS_MODULE_NAME'));
		include($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php');
		echo CAdminMessage::ShowNote(GetMessage('AV_IBPROPS_OK_TEXT'));
		?>
		<form action="/bitrix/admin/ibprops_manage.php" method="get">
		<p>
			<input type="hidden" name="lang" value="<?= LANG?>" />
			<input type="submit" value="<?=GetMessage('AV_IBPROPS_MANAGE_PAGE')?>" />
		</p>
		</form>
		<?
		include($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php');
		die();
	}
}
?>
