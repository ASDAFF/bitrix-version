<?
IncludeModuleLangFile(__FILE__);

if(class_exists("lists")) return;
Class lists extends CModule
{
	var $MODULE_ID = "lists";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $MODULE_GROUP_RIGHTS = "N";

	function lists()
	{
		$arModuleVersion = array();

		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
		include($path."/version.php");

		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

		$this->MODULE_NAME = GetMessage("LISTS_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("LISTS_MODULE_DESCRIPTION");
	}

	function InstallDB($arParams = array())
	{
		global $DB, $APPLICATION;
		$this->errors = false;

		// Database tables creation
		if(!$DB->Query("SELECT 'x' FROM b_lists_permission WHERE 1=0", true))
		{
			$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/lists/install/db/".strtolower($DB->type)."/install.sql");
		}

		if($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}
		else
		{
			RegisterModule("lists");
			CModule::IncludeModule("lists");
			RegisterModuleDependences("iblock", "OnIBlockDelete", "lists", "CLists", "OnIBlockDelete");
			RegisterModuleDependences("iblock", "CIBlockDocument_OnGetDocumentAdminPage", "lists", "CList", "OnGetDocumentAdminPage");
			RegisterModuleDependences("intranet", "OnSharepointCreateProperty", "lists", "CLists", "OnSharepointCreateProperty");
			RegisterModuleDependences("intranet", "OnSharepointCheckAccess", "lists", "CLists", "OnSharepointCheckAccess");
			RegisterModuleDependences("perfmon", "OnGetTableSchema", "lists", "lists", "OnGetTableSchema");
			return true;
		}
	}

	function UnInstallDB($arParams = array())
	{
		global $DB, $APPLICATION;
		$this->errors = false;

		if(!array_key_exists("savedata", $arParams) || $arParams["savedata"] != "Y")
		{
			$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/lists/install/db/".strtolower($DB->type)."/uninstall.sql");
		}

		UnRegisterModuleDependences("iblock", "OnIBlockDelete", "lists", "CLists", "OnIBlockDelete");
		UnRegisterModuleDependences("iblock", "CIBlockDocument_OnGetDocumentAdminPage", "lists", "CList", "OnGetDocumentAdminPage");
		UnRegisterModuleDependences("intranet", "OnSharepointCreateProperty", "lists", "CLists", "OnSharepointCreateProperty");
		UnRegisterModuleDependences("intranet", "OnSharepointCheckAccess", "lists", "CLists", "OnSharepointCheckAccess");
		UnRegisterModuleDependences("perfmon", "OnGetTableSchema", "lists", "lists", "OnGetTableSchema");
		UnRegisterModule("lists");

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
		if($_ENV["COMPUTERNAME"]!='BX')
		{
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/lists/install/components", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", True, True);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/lists/install/images", $_SERVER["DOCUMENT_ROOT"]."/bitrix/images/lists", True, True);
		}
		return true;
	}

	function UnInstallFiles()
	{
		if($_ENV["COMPUTERNAME"]!='BX')
		{
			DeleteDirFilesEx("/bitrix/images/lists/");//images
		}
		return true;
	}

	function DoInstall()
	{
		global $DB, $APPLICATION, $USER, $step;
		$step = IntVal($step);

		if(!$USER->IsAdmin())
			return;

		if(!CBXFeatures::IsFeatureEditable("Lists"))
		{
			$this->errors = array(GetMessage("MAIN_FEATURE_ERROR_EDITABLE"));
			$APPLICATION->ThrowException(implode("<br>", $this->errors));

			$GLOBALS["errors"] = $this->errors;
			$APPLICATION->IncludeAdminFile(GetMessage("LISTS_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/lists/install/step2.php");
		}
		elseif($step < 2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("LISTS_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/lists/install/step1.php");
		}
		elseif($step==2)
		{
			$this->InstallDB(array());
			$this->InstallFiles(array());
			CBXFeatures::SetFeatureEnabled("Lists", true);

			$GLOBALS["errors"] = $this->errors;
			$APPLICATION->IncludeAdminFile(GetMessage("LISTS_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/lists/install/step2.php");
		}
	}

	function DoUninstall()
	{
		global $DB, $APPLICATION, $USER, $step;
		if($USER->IsAdmin())
		{
			$step = IntVal($step);
			if($step < 2)
			{
				$APPLICATION->IncludeAdminFile(GetMessage("LISTS_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/lists/install/unstep1.php");
			}
			elseif($step == 2)
			{
				$this->UnInstallDB(array(
					"savedata" => $_REQUEST["savedata"],
				));
				$this->UnInstallFiles();
				CBXFeatures::SetFeatureEnabled("Lists", false);
				$GLOBALS["errors"] = $this->errors;
				$APPLICATION->IncludeAdminFile(GetMessage("LISTS_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/lists/install/unstep2.php");
			}
		}
	}

	function OnGetTableSchema()
	{
		return array(
			"iblock" => array(
				"b_iblock_type" => array(
					"ID" => array(
						"b_lists_permission" => "IBLOCK_TYPE_ID",
					)
				),
				"b_iblock" => array(
					"ID" => array(
						"b_lists_field" => "IBLOCK_ID",
						"b_lists_socnet_group" => "IBLOCK_ID",
						"b_lists_url" => "IBLOCK_ID",
					)
				),
			),
			"main" => array(
				"b_group" => array(
					"ID" => array(
						"b_lists_permission" => "GROUP_ID",
					)
				),
			),
		);
	}
}
?>