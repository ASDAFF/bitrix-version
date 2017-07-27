<?
global $MESS;
$strPath2Lang = str_replace("\\", "/", __FILE__);
$strPath2Lang = substr($strPath2Lang, 0, strlen($strPath2Lang)-strlen("/install/index.php"));
include(GetLangFileName($strPath2Lang."/lang/", "/install/index.php"));

class twofingers_location extends CModule{
	var $MODULE_ID = 'twofingers.location';
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
	
	function twofingers_location(){
		$arModuleVersion = array();

		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
		include($path."/version.php");
		
		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		
		$this->MODULE_NAME = GetMessage('TF_LOCATION_INSTALL_NAME');
		$this->MODULE_DESCRIPTION = GetMessage('TF_LOCATION_INSTALL_DESCRIPTION');
		$this->PARTNER_NAME = GetMessage("TF_LOCATION_PARTNER");
		$this->PARTNER_URI = GetMessage("TF_LOCATION_PARTNER_URI");
	}
	
	public function DoInstall(){		
		
		global $DB, $DBType, $APPLICATION;
		$this->errors = false;

		RegisterModule($this->MODULE_ID);
		
		RegisterModuleDependences("sale", "OnSaleComponentOrderOneStepOrderProps", $this->MODULE_ID, "TF_LOCATION_Events", "setDefaultLocation");
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/".$this->MODULE_ID."/install/components/", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components", true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/".$this->MODULE_ID."/install/location/", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/templates/.default/components", true, true);
		
		$settings = Array(
			'TF_LOCATION_HEADLINK_TEXT' => GetMessage("TF_LOCATION_HEADLINK_TEXT_DEFAULT"),
			'TF_LOCATION_DELIVERY' => 'Y',
			'TF_LOCATION_TEMPLATE' => 'Y',
			'TF_LOCATION_POPUP_RADIUS' => '10',
		);		
		foreach($settings as $key=>$value){
			COption::SetOptionString($this->MODULE_ID, $key, $value, SITE_ID);
		}
		
		LocalRedirect('/bitrix/admin/settings.php?lang=ru&mid=twofingers.location&mid_menu=1');

	}
	
	public function DoUninstall(){
	
		global $DB, $DBType, $APPLICATION;
		$this->errors = false;

		UnRegisterModuleDependences("sale", "OnSaleComponentOrderOneStepOrderProps", $this->MODULE_ID, "TF_LOCATION_Events", "setDefaultLocation");
		DeleteDirFilesEx("/".$this->MODULE_ID."/");
		DeleteDirFilesEx("/bitrix/components/twofingers/location/");
		DeleteDirFilesEx("/bitrix/templates/.default/components/bitrix/sale.ajax.locations/");
	
		UnRegisterModule($this->MODULE_ID);
				
	}
}
?>