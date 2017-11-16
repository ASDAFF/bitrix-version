<?
global $DOCUMENT_ROOT, $MESS;

IncludeModuleLangFile(__FILE__);

if (class_exists("calendar")) return;

Class calendar extends CModule
{
	var $MODULE_ID = "calendar";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $MODULE_GROUP_RIGHTS = "Y";

	function calendar()
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

		$this->MODULE_NAME = GetMessage("CAL_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("CAL_MODULE_DESCRIPTION");
	}

	function GetModuleTasks()
	{
		return array(
			// Tasks for sections
			'calendar_denied' => array(
				"LETTER" => "D",
				"BINDING" => "calendar_section",
				"OPERATIONS" => array()
			),
			'calendar_view_time' => array(
				"LETTER" => "O",
				"BINDING" => "calendar_section",
				"OPERATIONS" => array(
					'calendar_view_time'
				)
			),
			'calendar_view_title' => array(
				"LETTER" => "P",
				"BINDING" => "calendar_section",
				"OPERATIONS" => array(
					'calendar_view_time',
					'calendar_view_title'
				)
			),
			'calendar_view' => array(
				"LETTER" => "R",
				"BINDING" => "calendar_section",
				"OPERATIONS" => array(
					'calendar_view_time',
					'calendar_view_title',
					'calendar_view_full'
				)
			),
			'calendar_edit' => array(
				"LETTER" => "W",
				"BINDING" => "calendar_section",
				"OPERATIONS" => array(
					'calendar_view_time',
					'calendar_view_title',
					'calendar_view_full',
					'calendar_add',
					'calendar_edit',
					'calendar_edit_section'
				)
			),
			'calendar_access' => array(
				"LETTER" => "X",
				"BINDING" => "calendar_section",
				"OPERATIONS" => array(
					'calendar_view_time',
					'calendar_view_title',
					'calendar_view_full',
					'calendar_add',
					'calendar_edit',
					'calendar_edit_section',
					'calendar_edit_access'
				),
			),
			// Tasks for types
			'calendar_type_denied' => array(
				"LETTER" => "D",
				"BINDING" => "calendar_type",
				"OPERATIONS" => array()
			),
			'calendar_type_view' => array(
				"LETTER" => "R",
				"BINDING" => "calendar_type",
				"OPERATIONS" => array(
					'calendar_type_view'
				)
			),
			'calendar_type_edit' => array(
				"LETTER" => "W",
				"BINDING" => "calendar_type",
				"OPERATIONS" => array(
					'calendar_type_view',
					'calendar_type_add',
					'calendar_type_edit',
					'calendar_type_edit_section'
				)
			),
			'calendar_type_access' => array(
				"LETTER" => "X",
				"BINDING" => "calendar_type",
				"OPERATIONS" => array(
					'calendar_type_view',
					'calendar_type_add',
					'calendar_type_edit',
					'calendar_type_edit_section',
					'calendar_type_edit_access'
				)
			)
		);
	}

	function InstallDB()
	{
		global $DB, $APPLICATION;

		$arCurPhpVer = Explode(".", PhpVersion());
		if (IntVal($arCurPhpVer[0]) < 5)
			return true;

		if (!$DB->Query("SELECT 'x' FROM b_calendar_access ", true))
			$errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/'.$this->MODULE_ID.'/install/db/'.strtolower($DB->type).'/install.sql');
		$this->InstallTasks();

		if (!empty($errors))
		{
			$APPLICATION->ThrowException(implode("", $errors));
			return false;
		}

		RegisterModule("calendar");
		RegisterModuleDependences("pull", "OnGetDependentModule", "calendar", "CCalendarPullSchema", "OnGetDependentModule");
		RegisterModuleDependences("im", "OnGetNotifySchema", "calendar", "CCalendarNotifySchema", "OnGetNotifySchema");
		RegisterModuleDependences("im", "OnBeforeConfirmNotify", "calendar", "CCalendar", "HandleImCallback");

		return true;
	}

	function UnInstallDB($arParams = array())
	{
		global $DB, $APPLICATION;
		CAgent::RemoveModuleAgents('calendar');
		$errors = null;
		if ((true == array_key_exists("savedata", $arParams)) && ($arParams["savedata"] != 'Y'))
		{
			$errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/'.$this->MODULE_ID.'/install/db/'.strtolower($DB->type).'/uninstall.sql');

			if (!empty($errors))
			{
				$APPLICATION->ThrowException(implode("", $errors));
				return false;
			}
			$this->UnInstallTasks();
		}

		UnRegisterModuleDependences("pull", "OnGetDependentModule", "calendar", "CCalendarPullSchema", "OnGetDependentModule");
		UnRegisterModuleDependences("im", "OnGetNotifySchema", "calendar", "CCalendarNotifySchema", "OnGetNotifySchema");
		UnRegisterModuleDependences("im", "OnBeforeConfirmNotify", "calendar", "CCalendar", "HandleImCallback");
		UnRegisterModule("calendar");
		return true;
	}

	function InstallEvents()
	{
		global $DB;

		$arCurPhpVer = Explode(".", PhpVersion());
		if (IntVal($arCurPhpVer[0]) < 5)
			return true;

		$sIn = "'CALENDAR_INVITATION'";
		$rs = $DB->Query("SELECT count(*) C FROM b_event_type WHERE EVENT_NAME IN (".$sIn.") ", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$ar = $rs->Fetch();

		if($ar["C"] <= 0)
			include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/calendar/install/events.php");

		if (!IsModuleInstalled('intranet'))
		{
			COption::SetOptionString("intranet", "calendar_2", "Y");
			CModule::IncludeModule('calendar');
			CCalendar::ClearCache();
			CCalendar::CacheTime(0);

			$arTypes = CCalendarType::GetList();
			if (!$arTypes || !count($arTypes))
			{
				CCalendarType::Edit(array(
					'NEW' => true,
					'arFields' => array(
						'XML_ID' => 'events',
						'NAME' => GetMessage('CAL_DEFAULT_TYPE'),
						'ACCESS' => array(
							'G2' => CCalendar::GetAccessTasksByName('calendar_type', 'calendar_type_view')
						)
					)
				));
			}
		}

		return true;
	}

	function UnInstallEvents()
	{
		global $DB;
		$sIn = "'CALENDAR_INVITATION'";
		$DB->Query("DELETE FROM b_event_message WHERE EVENT_NAME IN (".$sIn.") ", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$DB->Query("DELETE FROM b_event_type WHERE EVENT_NAME IN (".$sIn.") ", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return true;
	}

	function InstallFiles()
	{
		global $APPLICATION;

		$arCurPhpVer = Explode(".", PhpVersion());
		if (IntVal($arCurPhpVer[0]) < 5)
			return true;

		CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/calendar/install/components/",
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/components",
			true, true
		);

		CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/calendar/install/admin/",
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/admin",
			true, true
		);

		CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/calendar/install/js",
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/js/",
			true, true
		);

		CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/calendar/install/images/",
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/images",
			true, true
		);

		return true;
	}

	function UnInstallFiles()
	{
		return true;
	}

	function DoInstall()
	{
		global $APPLICATION;

		if (!IsModuleInstalled("calendar"))
		{
			$this->InstallFiles();
			$this->InstallDB();
			$this->InstallEvents();

			$GLOBALS["errors"] = $this->errors;

			$APPLICATION->IncludeAdminFile(GetMessage("CAL_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/calendar/install/step1.php");
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
				$APPLICATION->IncludeAdminFile(GetMessage("CAL_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/calendar/install/unstep1.php");
			}
			elseif($step == 2)
			{
				$this->UnInstallDB(array(
					"savedata" => $_REQUEST["savedata"],
				));
				$this->UnInstallEvents();
				$this->UnInstallFiles();

				$GLOBALS["errors"] = $this->errors;
				$APPLICATION->IncludeAdminFile(GetMessage("CAL_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/calendar/install/unstep2.php");
			}
		}
	}

	function InstallDemoCalendarType()
	{

	}
}
?>