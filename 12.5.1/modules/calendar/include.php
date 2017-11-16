<?
/*patchlimitationmutatormark1*/
global $DBType;
IncludeModuleLangFile(__FILE__);

global $DBType;
CModule::AddAutoloadClasses(
	"calendar",
	array(
		"CCalendar" => "classes/general/calendar.php",
		"CCalendarSceleton" => "classes/general/calendar_sceleton.php",
		"CCalendarEvent" => "classes/general/calendar_event.php",
		"CCalendarSect" => "classes/general/calendar_sect.php",
		"CCalendarType" => "classes/general/calendar_type.php",
		"CCalendarPlanner" => "classes/general/calendar_planer.php",
		"CCalendarPlanner" => "classes/general/calendar_planer.php",
		"CCalendarWebService" => "classes/general/webservice.php",
		"CCalendarNotifySchema" => "classes/general/calendar_notify_schema.php",
		"CCalendarPullSchema" => "classes/general/calendar_notify_schema.php",
		"CCalendarEventHandlers" => "classes/general/calendar_event_handlers.php",
	)
);
/*patchlimitationmutatormark2*/
?>