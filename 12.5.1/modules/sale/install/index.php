<?
global $MESS;
$strPath2Lang = str_replace("\\", "/", __FILE__);
$strPath2Lang = substr($strPath2Lang, 0, strlen($strPath2Lang)-strlen("/install/index.php"));
include(GetLangFileName($strPath2Lang."/lang/", "/install.php"));

Class sale extends CModule
{
	var $MODULE_ID = "sale";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $MODULE_GROUP_RIGHTS = "Y";

	function sale()
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
			$this->MODULE_VERSION = SALE_VERSION;
			$this->MODULE_VERSION_DATE = SALE_VERSION_DATE;
		}

		$this->MODULE_NAME = GetMessage("SALE_INSTALL_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("SALE_INSTALL_DESCRIPTION");
	}

	function DoInstall()
	{
		global $APPLICATION, $step;
		$step = IntVal($step);
		if($step<2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("SALE_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/install/step1.php");
		}
		elseif($step==2)
		{
			$this->InstallFiles();
			$this->InstallDB();
			$this->InstallEvents();
			$GLOBALS["errors"] = $this->errors;

			$APPLICATION->IncludeAdminFile(GetMessage("SALE_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/install/step2.php");
		}
	}

	function DoUninstall()
	{
		global $APPLICATION, $step;
		$step = IntVal($step);
		if($step<2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("SALE_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/install/unstep1.php");
		}
		elseif($step==2)
		{
			$this->UnInstallFiles();
			if($_REQUEST["saveemails"] != "Y")
				$this->UnInstallEvents();

			$this->UnInstallDB(array(
				"savedata" => $_REQUEST["savedata"],
			));

			$GLOBALS["errors"] = $this->errors;
			$APPLICATION->IncludeAdminFile(GetMessage("SALE_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/install/unstep2.php");
		}
	}

	function GetModuleRightList()
	{
		$arr = array(
			"reference_id" => array("D",/* "R",*/ "U", "W"),
			"reference" => array(
					"[D] ".GetMessage("SINS_PERM_D"),
					//"[R] ".GetMessage("SINS_PERM_R"),
					"[U] ".GetMessage("SINS_PERM_U"),
					"[W] ".GetMessage("SINS_PERM_W")
				)
			);
		return $arr;
	}

	function InstallDB()
	{
		global $DB, $DBType, $APPLICATION;
		$this->errors = false;

		if(!$DB->Query("SELECT 'x' FROM b_sale_basket", true))
		{
			$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/install/db/".$DBType."/install.sql");
		}

		if($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("", $this->errors));
			return false;
		}

		RegisterModule("sale");
		RegisterModuleDependences("main", "OnUserLogin", "sale", "CSaleUser", "OnUserLogin");
		RegisterModuleDependences("main", "OnUserLogout", "sale", "CSaleUser", "OnUserLogout");
		RegisterModuleDependences("main", "OnBeforeLangDelete", "sale", "CSalePersonType", "OnBeforeLangDelete");
		RegisterModuleDependences("main", "OnLanguageDelete", "sale", "CSaleLocation", "OnLangDelete");
		RegisterModuleDependences("main", "OnLanguageDelete", "sale", "CSaleLocationGroup", "OnLangDelete");

		RegisterModuleDependences("main", "OnUserDelete", "sale", "CSaleOrderUserProps", "OnUserDelete");
		RegisterModuleDependences("main", "OnUserDelete", "sale", "CSaleUserAccount", "OnUserDelete");
		RegisterModuleDependences("main", "OnUserDelete", "sale", "CSaleAuxiliary", "OnUserDelete");
		RegisterModuleDependences("main", "OnUserDelete", "sale", "CSaleUser", "OnUserDelete");
		RegisterModuleDependences("main", "OnUserDelete", "sale", "CSaleRecurring", "OnUserDelete");
		RegisterModuleDependences("main", "OnUserDelete", "sale", "CSaleUserCards", "OnUserDelete");

		RegisterModuleDependences("main", "OnBeforeUserDelete", "sale", "CSaleOrder", "OnBeforeUserDelete");
		RegisterModuleDependences("main", "OnBeforeUserDelete", "sale", "CSaleAffiliate", "OnBeforeUserDelete");
		RegisterModuleDependences("main", "OnBeforeUserDelete", "sale", "CSaleUserAccount", "OnBeforeUserDelete");

		RegisterModuleDependences("main", "OnBeforeProlog", "main", "", "", 100, "/modules/sale/affiliate.php");

		RegisterModuleDependences("currency", "OnBeforeCurrencyDelete", "sale", "CSaleOrder", "OnBeforeCurrencyDelete");
		RegisterModuleDependences("currency", "OnBeforeCurrencyDelete", "sale", "CSaleLang", "OnBeforeCurrencyDelete");
		RegisterModuleDependences("currency", "OnModuleUnInstall", "sale", "", "CurrencyModuleUnInstallSale");

		RegisterModuleDependences("catalog", "OnSaleOrderSumm", "sale", "CSaleOrder", "__SaleOrderCount");

		RegisterModuleDependences("mobileapp", "OnBeforeAdminMobileMenuBuild", "sale", "CSaleMobileOrderUtils", "buildSaleAdminMobileMenu");

		RegisterModuleDependences("sale", "OnCondSaleControlBuildList", "sale", "CSaleCondCtrlGroup", "GetControlDescr", 100);
		RegisterModuleDependences("sale", "OnCondSaleControlBuildList", "sale", "CSaleCondCtrlBasketGroup", "GetControlDescr", 200);
		RegisterModuleDependences("sale", "OnCondSaleControlBuildList", "sale", "CSaleCondCtrlBasketFields", "GetControlDescr", 300);
//		RegisterModuleDependences("sale", "OnCondSaleControlBuildList", "sale", "CSaleCondCtrlBasketProps", "GetControlDescr", 400);
		RegisterModuleDependences("sale", "OnCondSaleControlBuildList", "sale", "CSaleCondCtrlOrderFields", "GetControlDescr", 1000);
//		RegisterModuleDependences("sale", "OnCondSaleControlBuildList", "sale", "CSaleCondCtrlBasketProductFields", "GetControlDescr", 1100);
//		RegisterModuleDependences("sale", "OnCondSaleControlBuildList", "sale", "CSaleCondCtrlBasketProductProps", "GetControlDescr", 1200);
		RegisterModuleDependences("sale", "OnCondSaleControlBuildList", "sale", "CSaleCondCtrlCommon", "GetControlDescr", 10000);

		RegisterModuleDependences("sale", "OnCondSaleActionsControlBuildList", "sale", "CSaleActionCtrlGroup", "GetControlDescr", 100);
		RegisterModuleDependences("sale", "OnCondSaleActionsControlBuildList", "sale", "CSaleActionCtrlDelivery", "GetControlDescr", 200);
		RegisterModuleDependences("sale", "OnCondSaleActionsControlBuildList", "sale", "CSaleActionCtrlBasketGroup", "GetControlDescr", 300);
//		RegisterModuleDependences("sale", "OnCondSaleActionsControlBuildList", "sale", "CSaleActionCtrlGiftsGroup", "GetControlDescr", 400);
		RegisterModuleDependences("sale", "OnCondSaleActionsControlBuildList", "sale", "CSaleActionCtrlSubGroup", "GetControlDescr", 1000);
		RegisterModuleDependences("sale", "OnCondSaleActionsControlBuildList", "sale", "CSaleActionCondCtrlBasketFields", "GetControlDescr", 1100);
//		RegisterModuleDependences("sale", "OnCondSaleActionsControlBuildList", "sale", "CSaleActionCtrlBasketProductFields", "GetControlDescr", 1200);
//		RegisterModuleDependences("sale", "OnCondSaleActionsControlBuildList", "sale", "CSaleActionCtrlBasketProductProps", "GetControlDescr", 1300);

		//pulling for mobile orders
		RegisterModuleDependences("sale", "OnOrderDelete", "sale", "CSaleMobileOrderPull", "onOrderDelete", 100);
		RegisterModuleDependences("sale", "OnOrderAdd", "sale", "CSaleMobileOrderPull", "onOrderAdd", 100);
		RegisterModuleDependences("sale", "OnOrderUpdate", "sale", "CSaleMobileOrderPull", "onOrderUpdate", 100);

		CAgent::AddAgent("CSaleRecurring::AgentCheckRecurring();", "sale", "N", 7200, "", "Y");
		CAgent::AddAgent("CSaleOrder::RemindPayment();", "sale", "N", 86400, "", "Y");
		CAgent::AddAgent("CSaleViewedProduct::ClearViewed();", "sale", "N", 86400, "", "Y");

		if (CModule::IncludeModule("sale"))
		{
			$dbStatusList = CSaleStatus::GetList(array(), array(), false, false, array());
			if (!($arStatusList = $dbStatusList->Fetch()))
			{
				$arLandDataN = array();
				$arLandDataF = array();

				$dbLangs = CLanguage::GetList(($b = ""), ($o = ""), array("ACTIVE" => "Y"));
				while ($arLangs = $dbLangs->Fetch())
				{
					IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/status.php", $arLangs["LID"]);

					$arLandDataN[] = array(
							"LID" => $arLangs["LID"],
							"NAME" => GetMessage("SIM_ACCEPTED"),
							"DESCRIPTION" => GetMessage("SIM_ACCEPTED_DESCR")
						);

					$arLandDataF[] = array(
							"LID" => $arLangs["LID"],
							"NAME" => GetMessage("SIM_FINISHED"),
							"DESCRIPTION" => GetMessage("SIM_FINISHED_DESCR")
						);
				}

				CSaleStatus::Add(
						array(
								"ID" => "N",
								"SORT" => 100,
								"LANG" => $arLandDataN
							)
					);

				CSaleStatus::Add(
						array(
								"ID" => "F",
								"SORT" => 200,
								"LANG" => $arLandDataF
							)
					);
			}
		}

		return true;
	}

	function UnInstallDB($arParams = array())
	{
		global $DB, $DBType, $APPLICATION;
		$this->errors = false;
		if(array_key_exists("savedata", $arParams) && $arParams["savedata"] != "Y")
		{
			$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/install/db/".$DBType."/uninstall.sql");

			if($this->errors !== false)
			{
				$APPLICATION->ThrowException(implode("", $this->errors));
				return false;
			}
		}

		UnRegisterModuleDependences("catalog", "OnSaleOrderSumm", "sale", "CSaleOrder", "__SaleOrderCount");

		UnRegisterModuleDependences("main", "OnBeforeProlog", "main", "", "", "/modules/sale/affiliate.php");
		UnRegisterModuleDependences("main", "OnUserLogin", "sale", "CSaleUser", "OnUserLogin");
		UnRegisterModuleDependences("main", "OnBeforeLangDelete", "sale", "CSalePersonType", "OnBeforeLangDelete");
		UnRegisterModuleDependences("main", "OnLanguageDelete", "sale", "CSaleLocation", "OnLangDelete");
		UnRegisterModuleDependences("main", "OnLanguageDelete", "sale", "CSaleLocationGroup", "OnLangDelete");

		UnRegisterModuleDependences("main", "OnUserDelete", "sale", "CSaleOrderUserProps", "OnUserDelete");
		UnRegisterModuleDependences("main", "OnUserDelete", "sale", "CSaleUserAccount", "OnUserDelete");

		UnRegisterModuleDependences("main", "OnUserDelete", "sale", "CSaleAuxiliary", "OnUserDelete");
		UnRegisterModuleDependences("main", "OnUserDelete", "sale", "CSaleUser", "OnUserDelete");
		UnRegisterModuleDependences("main", "OnUserDelete", "sale", "CSaleRecurring", "OnUserDelete");
		UnRegisterModuleDependences("main", "OnUserDelete", "sale", "CSaleUserCards", "OnUserDelete");

		UnRegisterModuleDependences("main", "OnBeforeUserDelete", "sale", "CSaleOrder", "OnBeforeUserDelete");
		UnRegisterModuleDependences("main", "OnBeforeUserDelete", "sale", "CSaleAffiliate", "OnBeforeUserDelete");
		UnRegisterModuleDependences("main", "OnBeforeUserDelete", "sale", "CSaleUserAccount", "OnBeforeUserDelete");

		UnRegisterModuleDependences("currency", "OnBeforeCurrencyDelete", "sale", "CSaleOrder", "OnBeforeCurrencyDelete");
		UnRegisterModuleDependences("currency", "OnBeforeCurrencyDelete", "sale", "CSaleLang", "OnBeforeCurrencyDelete");
		UnRegisterModuleDependences("currency", "OnModuleUnInstall", "sale", "", "CurrencyModuleUnInstallSale");

		UnRegisterModuleDependences("mobileapp", "OnBeforeAdminMobileMenuBuild", "sale", "CSaleMobileOrderUtils", "buildSaleAdminMobileMenu");

		UnRegisterModuleDependences("sale", "OnCondSaleControlBuildList", "sale", "CSaleCondCtrlGroup", "GetControlDescr", 100);
		UnRegisterModuleDependences("sale", "OnCondSaleControlBuildList", "sale", "CSaleCondCtrlBasketGroup", "GetControlDescr", 200);
		UnRegisterModuleDependences("sale", "OnCondSaleControlBuildList", "sale", "CSaleCondCtrlBasketFields", "GetControlDescr", 300);
		UnRegisterModuleDependences("sale", "OnCondSaleControlBuildList", "sale", "CSaleCondCtrlBasketProps", "GetControlDescr", 400);
		UnRegisterModuleDependences("sale", "OnCondSaleControlBuildList", "sale", "CSaleCondCtrlOrderFields", "GetControlDescr", 1000);
		UnRegisterModuleDependences("sale", "OnCondSaleControlBuildList", "sale", "CSaleCondCtrlBasketProductFields", "GetControlDescr", 1100);
		UnRegisterModuleDependences("sale", "OnCondSaleControlBuildList", "sale", "CSaleCondCtrlBasketProductProps", "GetControlDescr", 1200);
		UnRegisterModuleDependences("sale", "OnCondSaleControlBuildList", "sale", "CSaleCondCtrlCommon", "GetControlDescr", 10000);

		UnRegisterModuleDependences("sale", "OnCondSaleActionsControlBuildList", "sale", "CSaleActionCtrlGroup", "GetControlDescr", 100);
		UnRegisterModuleDependences("sale", "OnCondSaleActionsControlBuildList", "sale", "CSaleActionCtrlDelivery", "GetControlDescr", 200);
		UnRegisterModuleDependences("sale", "OnCondSaleActionsControlBuildList", "sale", "CSaleActionCtrlBasketGroup", "GetControlDescr", 300);
		UnRegisterModuleDependences("sale", "OnCondSaleActionsControlBuildList", "sale", "CSaleActionCtrlGiftsGroup", "GetControlDescr", 400);
		UnRegisterModuleDependences("sale", "OnCondSaleActionsControlBuildList", "sale", "CSaleActionCtrlSubGroup", "GetControlDescr", 1000);
		UnRegisterModuleDependences("sale", "OnCondSaleActionsControlBuildList", "sale", "CSaleActionCondCtrlBasketFields", "GetControlDescr", 1100);
		UnRegisterModuleDependences("sale", "OnCondSaleActionsControlBuildList", "sale", "CSaleActionCtrlBasketProductFields", "GetControlDescr", 1200);
		UnRegisterModuleDependences("sale", "OnCondSaleActionsControlBuildList", "sale", "CSaleActionCtrlBasketProductProps", "GetControlDescr", 1300);

		UnRegisterModuleDependences("sale", "OnOrderDelete", "sale", "CSaleMobileOrderPull", "onOrderDelete", 100);
		UnRegisterModuleDependences("sale", "OnOrderAdd", "sale", "CSaleMobileOrderPull", "onOrderAdd", 100);
		UnRegisterModuleDependences("sale", "OnOrderUpdate", "sale", "CSaleMobileOrderPull", "onOrderUpdate", 100);

		CAgent::RemoveModuleAgents("sale");

		UnRegisterModule("sale");

		return true;
	}

	function InstallEvents()
	{
		global $DB;
		include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/install/events.php");
		return true;
	}

	function UnInstallEvents()
	{
		global $DB;

		$statusMes = Array();
		$dbStatus = $DB->Query("SELECT * FROM b_sale_status", true);
		while($arStatus = $dbStatus->Fetch())
		{
			$eventType = new CEventType;
			$eventType->Delete("SALE_STATUS_CHANGED_".$arStatus["ID"]);
			$statusMes[] = "SALE_STATUS_CHANGED_".$arStatus["ID"];
		}

		$eventType = new CEventType;
		$eventType->Delete("SALE_NEW_ORDER");
		$eventType->Delete("SALE_ORDER_CANCEL");
		$eventType->Delete("SALE_ORDER_PAID");
		$eventType->Delete("SALE_ORDER_DELIVERY");
		$eventType->Delete("SALE_RECURRING_CANCEL");
		$eventType->Delete("SALE_STATUS_CHANGED");
		$eventType->Delete("SALE_ORDER_REMIND_PAYMENT");
		$eventType->Delete("SALE_NEW_ORDER_RECURRING");

		$statusMes[] = "SALE_NEW_ORDER";
		$statusMes[] = "SALE_ORDER_CANCEL";
		$statusMes[] = "SALE_ORDER_PAID";
		$statusMes[] = "SALE_ORDER_DELIVERY";
		$statusMes[] = "SALE_RECURRING_CANCEL";
		$statusMes[] = "SALE_STATUS_CHANGED";
		$statusMes[] = "SALE_ORDER_REMIND_PAYMENT";
		$statusMes[] = "SALE_NEW_ORDER_RECURRING";

		foreach($statusMes as $v)
		{
			$eventM = new CEventMessage;
			$dbEvent = CEventMessage::GetList($b="ID", $order="ASC", Array("EVENT_NAME" => $v));
			while($arEvent = $dbEvent->Fetch())
			{
				$eventM->Delete($arEvent["ID"]);
			}
		}

		return true;
	}

	function InstallFiles()
	{
		if($_ENV["COMPUTERNAME"]!='BX')
		{
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/install/admin", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin", true, true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/install/images",  $_SERVER["DOCUMENT_ROOT"]."/bitrix/images/sale", true, true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/install/themes", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes", true, true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/install/components", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", true, true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/install/gadgets", $_SERVER["DOCUMENT_ROOT"]."/bitrix/gadgets", true, true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/install/wizards", $_SERVER["DOCUMENT_ROOT"]."/bitrix/wizards", true, true);
		}
		return true;
	}

	function UnInstallFiles()
	{
		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/install/admin", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/install/themes/.default/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes/.default");//css
		DeleteDirFilesEx("/bitrix/themes/.default/icons/sale/");//icons
		DeleteDirFilesEx("/bitrix/images/sale/");//images

		return true;
	}
}
?>