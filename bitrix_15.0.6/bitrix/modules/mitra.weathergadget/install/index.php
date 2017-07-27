<?php
if (class_exists('mitra_weathergadget')) {
    return;
}

class mitra_weathergadget extends CModule
{
    var $MODULE_ID = 'mitra.weathergadget';
    public $MODULE_NAME = '';
    public $MODULE_DESCRIPTION = '';

    public $PARTNER_NAME = '';
    public $PARTNER_URI = '';

    public $MODULE_VERSION = '1.0.3';
    public $MODULE_VERSION_DATE = '2013-02-04 02:30:00';

    public $module_class;


    public function __construct()
    {
        $this->module_class = get_class($this);

        IncludeModuleLangFile(__FILE__);

        $arModuleVersion = array();
        include(dirname(__FILE__).DIRECTORY_SEPARATOR."version.php");

        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

        $this->MODULE_NAME = GetMessage(strtoupper($this->module_class).'_MODULE_NAME');
        $this->MODULE_DESCRIPTION = GetMessage(strtoupper($this->module_class).'_MODULE_DESCRIPTION');

        $this->PARTNER_NAME = GetMessage('MITRA_WEATHERGADGET_PARTNER_NAME');

        $this->PARTNER_URI = 'http://www.mitra.ru/';
    }


    /*
     * Registration.
     */
    public function DoInstall()
    {
        RegisterModule($this->MODULE_ID);
        $this->InstallFiles($this->MODULE_ID);
    }


    /*
     * Unregistration.
     */
    public function DoUninstall()
    {
        $this->UnInstallFiles($this->MODULE_ID);
        UnRegisterModule($this->MODULE_ID);
    }


    function InstallFiles($module)
    {
        if(!file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/mitra")) {
            if(!mkdir($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/mitra", BX_DIR_PERMISSIONS)) {
                return false;
            }
        }

        CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module."/install/components/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components/mitra", true, true);
        return true;
    }


    function UnInstallFiles($module)
    {
        DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module."/install/components/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components/mitra");
        return true;
    }
}