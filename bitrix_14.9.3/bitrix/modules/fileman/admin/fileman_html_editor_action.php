<?
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("NO_AGENT_CHECK", true);
define("DisableEventsCheck", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

//$APPLICATION->ShowAjaxHead();
CModule::IncludeModule("fileman");

//if(CModule::IncludeModule("compression"))
//	CCompress::Disable2048Spaces();

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : false;

CHTMLEditor::RequestAction($action);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>
