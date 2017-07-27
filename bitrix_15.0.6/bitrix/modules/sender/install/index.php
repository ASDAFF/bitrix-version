<?
IncludeModuleLangFile(__FILE__);

if(class_exists("sender")) return;
class sender extends CModule
{
	var $MODULE_ID = "sender";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $MODULE_GROUP_RIGHTS = "Y";

	var $errors;

	function sender()
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
			$this->MODULE_VERSION = SENDER_VERSION;
			$this->MODULE_VERSION_DATE = SENDER_VERSION_DATE;
		}

		$this->MODULE_NAME = GetMessage("SENDER_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("SENDER_MODULE_DESC");
		$this->MODULE_CSS = "/bitrix/modules/sender/styles.css";
	}

	function InstallDB($arParams = array())
	{
		global $DB, $DBType, $APPLICATION;
		$this->errors = false;

		// Database tables creation
		if(!$DB->Query("SELECT 'x' FROM b_sender_contact WHERE 1=0", true))
		{
			$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sender/install/db/".$DBType."/install.sql");
		}

		if($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}
		else
		{
			RegisterModule("sender");
			CModule::IncludeModule("sender");

			// read and click notifications
			RegisterModuleDependences("main", "OnMailEventMailRead", "sender", "bitrix\\sender\\postingmanager", "onMailEventMailRead");
			RegisterModuleDependences("main", "OnMailEventMailClick", "sender", "bitrix\\sender\\postingmanager", "onMailEventMailClick");

			// unsubscription notifications
			RegisterModuleDependences("main", "OnMailEventSubscriptionDisable", "sender", "Bitrix\\Sender\\Subscription", "onMailEventSubscriptionDisable");
			RegisterModuleDependences("main", "OnMailEventSubscriptionEnable", "sender", "Bitrix\\Sender\\Subscription", "onMailEventSubscriptionEnable");
			RegisterModuleDependences("main", "OnMailEventSubscriptionList", "sender", "Bitrix\\Sender\\Subscription", "onMailEventSubscriptionList");

			// connectors of module sender
			RegisterModuleDependences("sender", "OnConnectorList", "sender", "bitrix\\sender\\connectormanager", "onConnectorListContact");
			RegisterModuleDependences("sender", "OnConnectorList", "sender", "bitrix\\sender\\connectormanager", "onConnectorListRecipient");

			// mail templates and blocks
			RegisterModuleDependences("sender", "OnPresetTemplateList", "sender", "Bitrix\\Sender\\Preset\\TemplateBase", "onPresetTemplateList");
			RegisterModuleDependences("sender", "OnPresetTemplateList", "sender", "Bitrix\\Sender\\TemplateTable", "onPresetTemplateList");
			RegisterModuleDependences("sender", "OnPresetMailBlockList", "sender", "Bitrix\\Sender\\Preset\\MailBlockBase", "OnPresetMailBlockList");

			CTimeZone::Disable();

			\Bitrix\Sender\MailingManager::actualizeAgent();
			CAgent::AddAgent( \Bitrix\Sender\MailingManager::getAgentNamePeriod(), "sender", "N", COption::GetOptionString("sender", "reiterate_interval"));

			CTimeZone::Enable();

			return true;
		}
	}

	function UnInstallDB($arParams = array())
	{
		global $DB, $DBType, $APPLICATION;
		$this->errors = false;

		if(!array_key_exists("save_tables", $arParams) || ($arParams["save_tables"] != "Y"))
		{
			$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sender/install/db/".$DBType."/uninstall.sql");
		}

		CAgent::RemoveModuleAgents('sender');

		UnRegisterModuleDependences("main", "OnMailEventMailRead", "sender", "bitrix\\sender\\postingmanager", "onMailEventMailRead");
		UnRegisterModuleDependences("main", "OnMailEventMailClick", "sender", "bitrix\\sender\\postingmanager", "onMailEventMailClick");

		UnRegisterModuleDependences("main", "OnMailEventSubscriptionDisable", "sender", "Bitrix\\Sender\\Subscription", "onMailEventSubscriptionDisable");
		UnRegisterModuleDependences("main", "OnMailEventSubscriptionEnable", "sender", "Bitrix\\Sender\\Subscription", "onMailEventSubscriptionEnable");
		UnRegisterModuleDependences("main", "OnMailEventSubscriptionList", "sender", "Bitrix\\Sender\\Subscription", "onMailEventSubscriptionList");

		UnRegisterModuleDependences("sender", "OnConnectorList", "sender", "bitrix\\sender\\connectormanager", "onConnectorListContact");
		UnRegisterModuleDependences("sender", "OnConnectorList", "sender", "bitrix\\sender\\connectormanager", "onConnectorListRecipient");

		UnRegisterModuleDependences("sender", "OnPresetTemplateList", "sender", "Bitrix\\Sender\\Preset\\TemplateBase", "onPresetTemplateList");
		UnRegisterModuleDependences("sender", "OnPresetTemplateList", "sender", "Bitrix\\Sender\\TemplateTable", "onPresetTemplateList");
		UnRegisterModuleDependences("sender", "OnPresetMailBlockList", "sender", "Bitrix\\Sender\\Preset\\MailBlockBase", "OnPresetMailBlockList");

		UnRegisterModule("sender");

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
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sender/install/admin", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sender/install/themes", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes", true, true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sender/install/components", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", True, True);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sender/install/images", $_SERVER["DOCUMENT_ROOT"]."/bitrix/images", true, true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sender/install/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js", true, true);
		}

		return true;
	}

	function UnInstallFiles()
	{
		if($_ENV["COMPUTERNAME"]!='BX')
		{
			//admin files
			DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sender/install/admin", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
			//css
			DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sender/install/themes/.default/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes/.default");
			DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sender/install/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js");
		}

		return true;
	}

	function DoInstall()
	{
		global $DB, $DOCUMENT_ROOT, $APPLICATION, $step;
		$POST_RIGHT = $APPLICATION->GetGroupRight("sender");
		if($POST_RIGHT == "W")
		{
			$step = IntVal($step);
			if($step < 2)
			{
				$APPLICATION->IncludeAdminFile(GetMessage("SENDER_MODULE_INST_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sender/install/inst1.php");
			}
			elseif($step==2)
			{
				if($this->InstallDB())
				{
					$this->InstallEvents();
					$this->InstallFiles(array());
				}
				$GLOBALS["errors"] = $this->errors;
				$APPLICATION->IncludeAdminFile(GetMessage("SENDER_MODULE_INST_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sender/install/inst2.php");
			}
		}
	}

	function DoUninstall()
	{
		global $DB, $DOCUMENT_ROOT, $APPLICATION, $step;
		$POST_RIGHT = $APPLICATION->GetGroupRight("sender");
		if($POST_RIGHT == "W")
		{
			$step = IntVal($step);
			if($step < 2)
			{
				$APPLICATION->IncludeAdminFile(GetMessage("SENDER_MODULE_UNINST_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sender/install/uninst1.php");
			}
			elseif($step == 2)
			{
				$this->UnInstallDB(array(
					"save_tables" => $_REQUEST["save_tables"],
				));
				//message types and templates
				if($_REQUEST["save_templates"] != "Y")
				{
					$this->UnInstallEvents();
				}
				$this->UnInstallFiles();
				$GLOBALS["errors"] = $this->errors;
				$APPLICATION->IncludeAdminFile(GetMessage("SENDER_MODULE_UNINST_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sender/install/uninst2.php");
			}
		}
	}

}
?>