<?
define("NO_KEEP_STATISTIC", true);
define("NO_AGENT_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if($USER->IsAuthorized() && check_bitrix_sessid())
{
	//get saved columns and sorting from user settings
	$aOptions = CUserOptions::GetOption("main.interface.form", $_REQUEST["FORM_ID"], array());
	
	if($_REQUEST["action"] == "expand")
	{
		$aOptions["expand_tabs"] = ($_REQUEST["expand"] == "Y"? "Y":"N");
	}
	elseif($_REQUEST["action"] == "enable")
	{
		$aOptions["settings_disabled"] = ($_REQUEST["enabled"] == "Y"? "N":"Y");
	}
	elseif($_REQUEST["action"] == "settheme")
	{
		$aOptions["theme"] = $_REQUEST["theme"];
		if($_REQUEST["GRID_ID"] <> '')
		{
			$aGridOptions = CUserOptions::GetOption("main.interface.grid", $_REQUEST["GRID_ID"], array());
			$aGridOptions["theme"] = $_REQUEST["theme"];
			CUserOptions::SetOption("main.interface.grid", $_REQUEST["GRID_ID"], $aGridOptions);
		}
	}
	elseif($_REQUEST["action"] == "savesettings")
	{
		CUtil::decodeURIComponent($_POST);
		$aOptions["tabs"] = $_POST["tabs"];
	}

	CUserOptions::SetOption("main.interface.form", $_REQUEST["FORM_ID"], $aOptions);
}
echo "OK";
?>