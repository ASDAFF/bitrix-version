<?
define("PUBLIC_AJAX_MODE", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("NO_AGENT_CHECK", true);
define("DisableEventsCheck", true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

if (!CModule::IncludeModule("socialnetwork"))
{
	echo CUtil::PhpToJsObject(Array('ERROR' => 'MODULE_NOT_INSTALLED'));
	die();
}
if (check_bitrix_sessid())
{	
	if (CModule::IncludeModule('extranet') && !CExtranet::IsIntranetUser())
	{
		echo CUtil::PhpToJsObject(Array('ERROR' => 'EXTRANET_USER'));
	}
	else
	{
		if (isset($_POST["nt"]))
		{
			preg_match_all("/(#NAME#)|(#LAST_NAME#)|(#SECOND_NAME#)|(#NAME_SHORT#)|(#SECOND_NAME_SHORT#)|\s|\,/", urldecode($_REQUEST["nt"]), $matches);
			$nameTemplate = implode("", $matches[0]);
		}
		else
			$nameTemplate = CSite::GetNameFormat(false);

		if ($_POST['LD_SEARCH'] == 'Y')
		{
			CUtil::decodeURIComponent($_POST);

			echo CUtil::PhpToJsObject(Array(
				'USERS' => CSocNetLogDestination::SearchUsers($_POST['SEARCH'], $nameTemplate), 
			));
		}
		elseif ($_POST['LD_DEPARTMENT_RELATION'] == 'Y')
		{			
			echo CUtil::PhpToJsObject(Array(
				'USERS' => CSocNetLogDestination::GetUsers(Array('deportament_id' => $_POST['DEPARTMENT_ID'], "NAME_TEMPLATE" => $nameTemplate)), 
			));
		}
		else
		{
			echo CUtil::PhpToJsObject(Array('ERROR' => 'UNKNOWN_ERROR'));
		}
	}
}
else
{
	echo CUtil::PhpToJsObject(Array('ERROR' => 'SESSION_ERROR'));
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>