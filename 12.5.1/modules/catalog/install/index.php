<?
global $MESS;
$strPath2Lang = str_replace("\\", "/", __FILE__);
$strPath2Lang = substr($strPath2Lang, 0, strlen($strPath2Lang)-strlen("/install/index.php"));
include(GetLangFileName($strPath2Lang."/lang/", "/install.php"));

class catalog extends CModule
{
	var $MODULE_ID = "catalog";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $MODULE_GROUP_RIGHTS = "Y";

	function catalog()
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
			$this->MODULE_VERSION = CATALOG_VERSION;
			$this->MODULE_VERSION_DATE = CATALOG_VERSION_DATE;
		}

		$this->MODULE_NAME = GetMessage("CATALOG_INSTALL_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("CATALOG_INSTALL_DESCRIPTION2");
	}

	function DoInstall()
	{
		global $APPLICATION, $step, $errors;

		$step = IntVal($step);
		$errors = false;

		if(!IsModuleInstalled("currency"))
			$errors = GetMessage("CATALOG_UNINS_CURRENCY");
		elseif(!IsModuleInstalled("iblock"))
			$errors = GetMessage("CATALOG_UNINS_IBLOCK");
		else
		{
			$this->InstallFiles();
			$this->InstallDB();
			$this->InstallEvents();
		}

		$APPLICATION->IncludeAdminFile(GetMessage("CATALOG_INSTALL_TITLE"), $_SERVER['DOCUMENT_ROOT']."/bitrix/modules/catalog/install/step1.php");
	}

	function InstallFiles()
	{
		if ($_ENV["COMPUTERNAME"]!='BX')
		{
			CopyDirFiles($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/catalog/install/admin", $_SERVER['DOCUMENT_ROOT']."/bitrix/admin");
			CopyDirFiles($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/catalog/install/components", $_SERVER['DOCUMENT_ROOT']."/bitrix/components", true, true);
			CopyDirFiles($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/catalog/install/images", $_SERVER['DOCUMENT_ROOT']."/bitrix/images/catalog", true, true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/install/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js", true, true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/install/panel", $_SERVER["DOCUMENT_ROOT"]."/bitrix/panel", true, true);
			CopyDirFiles($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/catalog/install/themes", $_SERVER['DOCUMENT_ROOT']."/bitrix/themes", true, true);
			CopyDirFiles($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/catalog/install/tools", $_SERVER['DOCUMENT_ROOT']."/bitrix/tools");

			CopyDirFiles($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/catalog/install/public/catalog_import", $_SERVER['DOCUMENT_ROOT']."/bitrix/php_interface/include/catalog_import");
			CopyDirFiles($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/catalog/install/public/catalog_export", $_SERVER['DOCUMENT_ROOT']."/bitrix/php_interface/include/catalog_export");
			CopyDirFiles($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/catalog/install/public/catalog_export/froogle_util.php", $_SERVER['DOCUMENT_ROOT']."/bitrix/tools/catalog_export/froogle_util.php");
			CopyDirFiles($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/catalog/install/public/catalog_export/yandex_util.php", $_SERVER['DOCUMENT_ROOT']."/bitrix/tools/catalog_export/yandex_util.php");
			CopyDirFiles($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/catalog/install/public/catalog_export/yandex_detail.php", $_SERVER['DOCUMENT_ROOT']."/bitrix/tools/catalog_export/yandex_detail.php");

			CheckDirPath($_SERVER['DOCUMENT_ROOT']."/bitrix/catalog_export/");
		}

		return true;
	}

	function InstallDB()
	{
		global $APPLICATION;
		global $DB;
		global $DBType;
		global $errors;

		if(!$DB->Query("SELECT 'x' FROM b_catalog_group", true))
			$errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/catalog/install/db/".$DBType."/install.sql");

		if (!empty($errors))
		{
			$APPLICATION->ThrowException(implode("", $errors));
			return false;
		}

		RegisterModule("catalog");

		RegisterModuleDependences("iblock", "OnIBlockDelete", "catalog", "CCatalog", "OnIBlockDelete");
		RegisterModuleDependences("iblock", "OnIBlockElementDelete", "catalog", "CCatalogProduct", "OnIBlockElementDelete");
		RegisterModuleDependences("iblock", "OnIBlockElementDelete", "catalog", "CPrice", "OnIBlockElementDelete");
		RegisterModuleDependences("iblock", "OnIBlockElementDelete", "catalog", "CCatalogStoreProduct", "OnIBlockElementDelete");
		RegisterModuleDependences("currency", "OnCurrencyDelete", "catalog", "CPrice", "OnCurrencyDelete");
		RegisterModuleDependences("main", "OnGroupDelete", "catalog", "CCatalogProductGroups", "OnGroupDelete");
		RegisterModuleDependences("iblock", "OnAfterIBlockElementUpdate", "catalog", "CCatalogProduct", "OnAfterIBlockElementUpdate");
		RegisterModuleDependences("currency", "OnModuleUnInstall", "catalog", "", "CurrencyModuleUnInstallCatalog");
		RegisterModuleDependences("iblock", "OnBeforeIBlockDelete", "catalog", "CCatalog", "OnBeforeCatalogDelete", 300);
		RegisterModuleDependences("iblock", "OnBeforeIBlockElementDelete", "catalog", "CCatalog", "OnBeforeIBlockElementDelete", 10000);
		RegisterModuleDependences("main", "OnEventLogGetAuditTypes", "catalog", "CCatalogEvent", "GetAuditTypes");
		RegisterModuleDependences('sale', 'OnSetCouponList', 'catalog', 'CCatalogDiscountCoupon', 'OnSetCouponList');
		RegisterModuleDependences('sale', 'OnClearCouponList', 'catalog', 'CCatalogDiscountCoupon', 'OnClearCouponList');
		RegisterModuleDependences('main', 'OnBuildGlobalMenu', 'catalog', 'CCatalogAdmin', 'OnBuildGlobalMenu');
		RegisterModuleDependences('main', 'OnAdminListDisplay', 'catalog', 'CCatalogAdmin', 'OnAdminListDisplay');
		RegisterModuleDependences('main', 'OnBuildGlobalMenu', 'catalog', 'CCatalogAdmin', 'OnBuildSaleMenu');
		RegisterModuleDependences("catalog", "OnCondCatControlBuildList", "catalog", "CCatalogCondCtrlGroup", "GetControlDescr", 100);
		RegisterModuleDependences("catalog", "OnCondCatControlBuildList", "catalog", "CCatalogCondCtrlIBlockFields", "GetControlDescr", 200);
		RegisterModuleDependences("catalog", "OnCondCatControlBuildList", "catalog", "CCatalogCondCtrlIBlockProps", "GetControlDescr", 300);
		RegisterModuleDependences("catalog", "OnDocumentBarcodeDelete", "catalog", "CCatalogStoreDocsElement", "OnDocumentBarcodeDelete");
		RegisterModuleDependences("catalog", "OnBeforeDocumentDelete", "catalog", "CCatalogStoreDocsBarcode", "OnBeforeDocumentDelete");

		$this->InstallTasks();

		$rsLangs = CLanguage::GetList(($by="id"), ($order="asc"), array('ID' => 'ru',"ACTIVE" => "Y"));
		if ($arLang = $rsLangs->Fetch())
		{
			$strPath2Lang = str_replace("\\", "/", __FILE__);
			$strPath2Lang = substr($strPath2Lang, 0, strlen($strPath2Lang)-strlen('/install/index.php')).'/lang/';
			$arMess = self::__GetMessagesForAllLang($strPath2Lang,'/install.php',array('CATALOG_INSTALL_PROFILE_IRR2'),'irr.ru', array('ru'));
			if (is_array($arMess) && !empty($arMess['CATALOG_INSTALL_PROFILE_IRR2']['ru']))
			{
				$strQuery = "select COUNT(CE.ID) as CNT from b_catalog_export CE where CE.IS_EXPORT = 'Y' and CE.FILE_NAME ='yandex' and CE.NAME = '".$DB->ForSql($arMess['CATALOG_INSTALL_PROFILE_IRR2']['ru'])."'";
				$rsProfiles = $DB->Query($strQuery, true);
				if (false !== $rsProfiles)
				{
					$arProfile = $rsProfiles->Fetch();
					if (0 == $arProfile['CNT'])
					{
						$arFields = array(
							'FILE_NAME' => 'yandex',
							'NAME' => $arMess['CATALOG_INSTALL_PROFILE_IRR2']['ru'],
							'DEFAULT_PROFILE' => 'N',
							'IN_MENU' => 'N',
							'IN_AGENT' => 'N',
							'IN_CRON' => 'N',
							'NEED_EDIT' => 'Y',
							'IS_EXPORT' => 'Y'
						);
						$arInsert = $DB->PrepareInsert("b_catalog_export", $arFields);
						$strQuery = "INSERT INTO b_catalog_export(".$arInsert[0].") VALUES(".$arInsert[1].")";
						$DB->Query($strQuery, true);
					}
				}
			}
		}

		return true;
	}

	function InstallEvents()
	{
		return true;
	}

	function DoUnInstall()
	{
		global $APPLICATION, $step, $errors;
		$step = IntVal($step);
		if($step<2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("CATALOG_INSTALL_TITLE"), $_SERVER['DOCUMENT_ROOT']."/bitrix/modules/catalog/install/unstep1.php");
		}
		elseif($step==2)
		{
			$errors = false;

			$this->UnInstallDB(array(
				"savedata" => $_REQUEST["savedata"],
			));

			$this->UnInstallFiles(array(
				"savedata" => $_REQUEST["savedata"],
			));

			$APPLICATION->IncludeAdminFile(GetMessage("CATALOG_INSTALL_TITLE"), $_SERVER['DOCUMENT_ROOT']."/bitrix/modules/catalog/install/unstep2.php");
		}
	}

	function UnInstallFiles()
	{
		if ($_ENV["COMPUTERNAME"]!='BX')
		{
			DeleteDirFiles($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/catalog/install/admin", $_SERVER['DOCUMENT_ROOT']."/bitrix/admin");
			DeleteDirFiles($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/catalog/install/themes/.default/", $_SERVER['DOCUMENT_ROOT']."/bitrix/themes/.default");//css
			DeleteDirFilesEx("/bitrix/themes/.default/icons/catalog/");//icons
			DeleteDirFiles($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/catalog/install/tools", $_SERVER['DOCUMENT_ROOT']."/bitrix/tools");
			DeleteDirFilesEx("/bitrix/js/catalog/");//javascript
			DeleteDirFilesEx("/bitrix/panel/catalog/");//panel
		}
		return true;
	}

	function UnInstallDB($arParams = array())
	{
		global $APPLICATION, $DB, $errors;

		if(!array_key_exists("savedata", $arParams) || $arParams["savedata"] != "Y")
		{
			$errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/catalog/install/db/".strtolower($DB->type)."/uninstall.sql");
			if (!empty($errors))
			{
				$APPLICATION->ThrowException(implode("", $errors));
				return false;
			}
			$this->UnInstallTasks();
			COption::RemoveOption("catalog");
		}

		UnRegisterModuleDependences("iblock", "OnIBlockDelete", "catalog", "CCatalog", "OnIBlockDelete");
		UnRegisterModuleDependences("iblock", "OnIBlockElementDelete", "catalog", "CProduct", "OnIBlockElementDelete");
		UnRegisterModuleDependences("iblock", "OnIBlockElementDelete", "catalog", "CPrice", "OnIBlockElementDelete");
		UnRegisterModuleDependences("iblock", "OnIBlockElementDelete", "catalog", "CCatalogStoreProduct", "OnIBlockElementDelete");
		UnRegisterModuleDependences("currency", "OnCurrencyDelete", "catalog", "CPrice", "OnCurrencyDelete");
		UnRegisterModuleDependences("iblock", "OnAfterIBlockElementUpdate", "catalog", "CCatalogProduct", "OnAfterIBlockElementUpdate");
		UnRegisterModuleDependences("currency", "OnModuleUnInstall", "catalog", "", "CurrencyModuleUnInstallCatalog");
		UnRegisterModuleDependences("iblock", "OnBeforeIBlockDelete", "catalog", "CCatalog", "OnBeforeCatalogDelete");
		UnRegisterModuleDependences("iblock", "OnBeforeIBlockElementDelete", "catalog", "CCatalog", "OnBeforeIBlockElementDelete");
		UnRegisterModuleDependences("main", "OnEventLogGetAuditTypes", "catalog", "CCatalogEvent", "GetAuditTypes");
		UnRegisterModuleDependences('sale', 'OnSetCouponList', 'catalog', 'CCatalogDiscountCoupon', 'OnSetCouponList');
		UnRegisterModuleDependences('sale', 'OnClearCouponList', 'catalog', 'CCatalogDiscountCoupon', 'OnClearCouponList');
		UnRegisterModuleDependences('main', 'OnBuildGlobalMenu', 'catalog', 'CCatalogAdmin', 'OnBuildGlobalMenu');
		UnRegisterModuleDependences('main', 'OnAdminListDisplay', 'catalog', 'CCatalogAdmin', 'OnAdminListDisplay');
		UnRegisterModuleDependences('main', 'OnBuildGlobalMenu', 'catalog', 'CCatalogAdmin', 'OnBuildSaleMenu');
		UnRegisterModuleDependences("catalog", "OnCondCatControlBuildList", "catalog", "CCatalogCondCtrlGroup", "GetControlDescr", 100);
		UnRegisterModuleDependences("catalog", "OnCondCatControlBuildList", "catalog", "CCatalogCondCtrlIBlockFields", "GetControlDescr", 200);
		UnRegisterModuleDependences("catalog", "OnCondCatControlBuildList", "catalog", "CCatalogCondCtrlIBlockProps", "GetControlDescr", 300);
		UnRegisterModuleDependences("catalog", "OnDocumentBarcodeDelete", "catalog", "CCatalogStoreDocsElement", "OnDocumentBarcodeDelete");
		UnRegisterModuleDependences("catalog", "OnBeforeDocumentDelete", "catalog", "CCatalogStoreDocsBarcode", "OnBeforeDocumentDelete");

		UnRegisterModule("catalog");

		if(!defined("BX_CATALOG_UNINSTALLED"))
			define("BX_CATALOG_UNINSTALLED", true);

		return true;
	}

	function UnInstallEvents()
	{
		return true;
	}

	function GetModuleTasks()
	{
		return array(
			'catalog_denied' => array(
				"LETTER" => "D",
				"BINDING" => "module",
				"OPERATIONS" => array(
				),
			),
			'catalog_read' => array(
				"LETTER" => "R",
				"BINDING" => "module",
				"OPERATIONS" => array(
					'catalog_read',
				),
			),
			'catalog_price_edit' => array(
				"LETTER" => "T",
				"BINDING" => "module",
				"OPERATIONS" => array(
					'catalog_read',
					'catalog_price',
					'catalog_group',
					'catalog_discount',
					'catalog_vat',
					'catalog_store',
				),
			),
			'catalog_store_edit' => array(
				"LETTER" => "S",
				"BINDING" => "module",
				"OPERATIONS" => array(
					'catalog_read',
					'catalog_price',
					'catalog_store',
					'catalog_purchas_info',
				),
			),
			'catalog_export_import' => array(
				"LETTER" => "U",
				"BINDING" => "module",
				"OPERATIONS" => array(
					'catalog_read',
					'catalog_export_edit',
					'catalog_export_exec',
					'catalog_import_edit',
					'catalog_import_exec',
				),
			),
			'catalog_full_access' => array(
				"LETTER" => "W",
				"BINDING" => "module",
				"OPERATIONS" => array(
					'catalog_read',
					'catalog_price',
					'catalog_group',
					'catalog_discount',
					'catalog_vat',
					'catalog_store',
					'catalog_purchas_info',
					'catalog_export_edit',
					'catalog_export_exec',
					'catalog_import_edit',
					'catalog_import_exec',
					'catalog_settings',
				),
			),
		);
	}

	private function __GetMessagesForAllLang($strBefore, $strAfter, $MessID, $strDefMess = false, $arLangList = array())
	{
		$arResult = false;

		if (empty($MessID))
			return $arResult;
		if (!is_array($MessID))
			$MessID = array($MessID);

		if (!is_array($arLangList))
			$arLangList = array($arLangList);

		if (empty($arLangList))
		{
			$rsLangs = CLanguage::GetList(($by="lid"), ($order="asc"), array("ACTIVE" => "Y"));
			while ($arLang = $rsLangs->Fetch())
			{
				$arLangList[] = $arLang['LID'];
			}
		}
		foreach ($arLangList as &$strLID)
		{
			@include(GetLangFileName($strBefore, $strAfter, $strLID));
			foreach ($MessID as &$strMessID)
			{
				if (0 >= strlen($strMessID))
					continue;
				$arResult[$strMessID][$strLID] = (isset($MESS[$strMessID]) ? $MESS[$strMessID] : $strDefMess);
			}
		}
		return $arResult;
	}
}
?>