<?
global $MESS;
$strPath2Lang = str_replace("\\", "/", __FILE__);
$strPath2Lang = substr($strPath2Lang, 0, strlen($strPath2Lang)-strlen("/install/index.php"));
include(GetLangFileName($strPath2Lang."/lang/", "/install.php"));

Class currency extends CModule
{
	var $MODULE_ID = "currency";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $MODULE_GROUP_RIGHTS = "Y";

	function currency()
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
			$this->MODULE_VERSION = CURRENCY_VERSION;
			$this->MODULE_VERSION_DATE = CURRENCY_VERSION_DATE;
		}

		$this->MODULE_NAME = GetMessage("CURRENCY_INSTALL_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("CURRENCY_INSTALL_DESCRIPTION");
	}

	function DoInstall()
	{
		global $APPLICATION;
		$this->InstallFiles();
		$this->InstallDB();
		$GLOBALS["errors"] = $this->errors;

		$APPLICATION->IncludeAdminFile(GetMessage("CURRENCY_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/currency/install/step1.php");
	}

	function DoUninstall()
	{
		global $APPLICATION, $step;
		$step = IntVal($step);
		if($step<2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("CURRENCY_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/currency/install/unstep1.php");
		}
		elseif($step==2)
		{
			$this->UnInstallDB(array(
				"savedata" => $_REQUEST["savedata"],
			));
			$this->UnInstallFiles();

			$GLOBALS["errors"] = $this->errors;
			$APPLICATION->IncludeAdminFile(GetMessage("CURRENCY_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/currency/install/unstep2.php");
		}
	}

	function InstallDB()
	{
		global $DB, $DBType, $APPLICATION;
		global $stackCacheManager;
		global $CACHE_MANAGER;

		$this->errors = false;

		if (!$DB->Query("SELECT COUNT(CURRENCY) FROM b_catalog_currency", true)):
			$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/currency/install/db/".$DBType."/install.sql");
		endif;

		if($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("", $this->errors));
			return false;
		}
		RegisterModule("currency");
		$stackCacheManager->Clear("currency_currency_lang");
		$CACHE_MANAGER->Clean("currency_currency_list");
		$CACHE_MANAGER->Clean("currency_base_currency");
		$stackCacheManager->Clear("currency_rate");

		if (CModule::IncludeModule("currency"))
		{
			$dbCurrency = CCurrency::GetList($by = "sort", $order = "asc");
			if(!$dbCurrency->Fetch())
			{
				$rsLang = CLanguage::GetByID("ru");
				if($arLang = $rsLang->Fetch())
				{
					$arFields = array(
						"CURRENCY" => "RUB",
						"AMOUNT" => 1,
						"AMOUNT_CNT" => 1,
						"SORT" => 100
					);
					CCurrency::Add($arFields);

					$arFields = array(
						"CURRENCY" => "USD",
						"AMOUNT" => 30.33,
						"AMOUNT_CNT" => 1,
						"SORT" => 200
					);
					CCurrency::Add($arFields);

					$arFields = array(
						"CURRENCY" => "EUR",
						"AMOUNT" => 38.98,
						"AMOUNT_CNT" => 1,
						"SORT" => 300
					);
					CCurrency::Add($arFields);

					$arFields = array(
						"CURRENCY" => "UAH",
						"AMOUNT" => 3.77,
						"AMOUNT_CNT" => 1,
						"SORT" => 400
					);
					CCurrency::Add($arFields);
					$arCurrency = Array("RUB", "USD", "EUR", "UAH");
				}
				else
				{
					$rsLang = CLanguage::GetByID("de");
					if($arLang = $rsLang->Fetch())
					{
						$arFields = array(
							"CURRENCY" => "USD",
							"AMOUNT" => 0.78,
							"AMOUNT_CNT" => 1,
							"SORT" => 200
						);
						CCurrency::Add($arFields);

						$arFields = array(
							"CURRENCY" => "EUR",
							"AMOUNT" => 1,
							"AMOUNT_CNT" => 1,
							"SORT" => 100
						);
						CCurrency::Add($arFields);
						$arCurrency = Array("USD", "EUR");
					}
					else
					{
						$arFields = array(
							"CURRENCY" => "USD",
							"AMOUNT" => 1,
							"AMOUNT_CNT" => 1,
							"SORT" => 100
						);
						CCurrency::Add($arFields);

						$arFields = array(
							"CURRENCY" => "EUR",
							"AMOUNT" => 1.26,
							"AMOUNT_CNT" => 1,
							"SORT" => 200
						);
						CCurrency::Add($arFields);
						$arCurrency = Array("USD", "EUR");
					}
				}

				$dbLangs = CLanguage::GetList(($b = ""), ($o = ""), array("ACTIVE" => "Y"));
				while ($arLangs = $dbLangs->Fetch())
				{
					$CACHE_MANAGER->Clean("currency_currency_list_".$arLangs["LID"]);

					IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/currency/install_lang.php", $arLangs["LID"]);
					foreach($arCurrency as $val)
					{
						$arFields = Array();
						if($val == "USD")
						{
							$arFields = Array(
								"LID" => $arLangs["LID"],
								"CURRENCY" => "USD",
								"FORMAT_STRING" => GetMessage("CUR_INSTALL_USD_FORMAT_STRING"),
								"FULL_NAME" => GetMessage("CUR_INSTALL_USD_FULL_NAME"),
								"DEC_POINT" => GetMessage("CUR_INSTALL_USD_DEC_POINT"),
								"THOUSANDS_VARIANT" => GetMessage("CUR_INSTALL_USD_THOUSANDS_SEP"),
								"THOUSANDS_SEP" => false,
								"DECIMALS" => 2
								);
						}
						elseif($val == "EUR")
						{
							$arFields = Array(
								"LID" => $arLangs["LID"],
								"CURRENCY" => "EUR",
								"FORMAT_STRING" => GetMessage("CUR_INSTALL_EUR_FORMAT_STRING"),
								"FULL_NAME" => GetMessage("CUR_INSTALL_EUR_FULL_NAME"),
								"DEC_POINT" => GetMessage("CUR_INSTALL_EUR_DEC_POINT"),
								"THOUSANDS_VARIANT" => GetMessage("CUR_INSTALL_EUR_THOUSANDS_SEP"),
								"THOUSANDS_SEP" => false,
								"DECIMALS" => 2
								);
						}
						elseif($val == "RUB")
						{
							$arFields = Array(
								"LID" => $arLangs["LID"],
								"CURRENCY" => "RUB",
								"FORMAT_STRING" => GetMessage("CUR_INSTALL_RUB_FORMAT_STRING"),
								"FULL_NAME" => GetMessage("CUR_INSTALL_RUB_FULL_NAME"),
								"DEC_POINT" => GetMessage("CUR_INSTALL_RUB_DEC_POINT"),
								"THOUSANDS_VARIANT" => GetMessage("CUR_INSTALL_RUB_THOUSANDS_SEP"),
								"THOUSANDS_SEP" => false,
								"DECIMALS" => 2
								);
						}
						elseif($val == "UAH")
						{
							$arFields = Array(
								"LID" => $arLangs["LID"],
								"CURRENCY" => "UAH",
								"FORMAT_STRING" => GetMessage("CUR_INSTALL_UAH_FORMAT_STRING"),
								"FULL_NAME" => GetMessage("CUR_INSTALL_UAH_FULL_NAME"),
								"DEC_POINT" => GetMessage("CUR_INSTALL_UAH_DEC_POINT"),
								"THOUSANDS_VARIANT" => GetMessage("CUR_INSTALL_UAH_THOUSANDS_SEP"),
								"THOUSANDS_SEP" => false,
								"DECIMALS" => 2
								);
						}

						CCurrencyLang::Add($arFields);
					}
				}
			}
		}
		$stackCacheManager->Clear("currency_currency_lang");
		$CACHE_MANAGER->Clean("currency_currency_list");
		$CACHE_MANAGER->Clean("currency_base_currency");
		$stackCacheManager->Clear("currency_rate");

		return true;
	}

	function UnInstallDB($arParams = array())
	{
		global $DB, $DBType, $APPLICATION;
		$this->errors = false;
		if(array_key_exists("savedata", $arParams) && $arParams["savedata"] != "Y")
		{
			$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/currency/install/db/".$DBType."/uninstall.sql");
			if($this->errors !== false)
			{
				$APPLICATION->ThrowException(implode("", $this->errors));
				return false;
			}
		}

		UnRegisterModule("currency");

		return true;
	}


	function InstallFiles()
	{
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/currency/install/admin", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin", true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/currency/install/images", $_SERVER["DOCUMENT_ROOT"]."/bitrix/images/currency/", true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/currency/install/themes/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes", true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/currency/install/components/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", true, true);

		return true;
	}

	function UnInstallFiles()
	{
		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/currency/install/admin", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/currency/install/themes/.default/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes/.default");
		DeleteDirFilesEx("/bitrix/themes/.default/icons/currency/");
		DeleteDirFilesEx("/bitrix/images/currency/");
		DeleteDirFilesEx("/bitrix/themes/.default/icons/currency");

		return true;
	}
}
?>