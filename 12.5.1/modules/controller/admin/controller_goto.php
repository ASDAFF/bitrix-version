<?
define("NOT_CHECK_PERMISSIONS", true);
require_once(dirname(__FILE__)."/../../main/include/prolog_admin_before.php");

$MOD_RIGHT = $APPLICATION->GetGroupRight("controller");
if($MOD_RIGHT < "V")
{
	//For L right we'll make and exception
	$arRIGHTS = $APPLICATION->GetUserRoles("controller");
	if(in_array("L", $arRIGHTS))
		$MOD_RIGHT = "L";
	else
		$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/controller/prolog.php");

IncludeModuleLangFile(__FILE__);
CModule::IncludeModule("controller");

$member_id = intval($_REQUEST['member']);
$dbr = CControllerMember::GetById($member_id);
$ar = $dbr->GetNext();
if(!$ar)
	LocalRedirect("/bitrix/admin/controller_member_admin.php");

if($MOD_RIGHT == "L")
{//Authorize as user

	$arGroups = array();
	$arUserGroups = $USER->GetUserGroupArray();
	$arLocGroups = unserialize(COption::GetOptionString("controller", "auth_loc", serialize(Array())));
	foreach($arLocGroups as $arTGroup)
		foreach($arUserGroups as $group_id)
			if($arTGroup["LOC"] == $group_id)
				$arGroups[] = EscapePHPString($arTGroup["REM"]);

	if(count($arGroups) > 0)
		$strGroups = '"GROUP_ID" => Array("'.implode('", "', $arGroups).'"),';
	else
		$strGroups = '';

	$param = 'Array(
		'.$strGroups.'
		"LOGIN"=>"'.EscapePHPString($USER->GetParam("LOGIN")).'",
		"NAME"=>"'.EscapePHPString($USER->GetParam("FIRST_NAME")).'",
		"LAST_NAME"=>"'.EscapePHPString($USER->GetParam("LAST_NAME")).'",
		"EMAIL"=>"'.EscapePHPString($USER->GetParam("EMAIL")).'",
	)';
	$query = '
	CControllerClient::AuthorizeUser('.$param.');
	LocalRedirect("/");
	';
	$arControllerLog = Array(
		'NAME'=>'AUTH',
		'CONTROLLER_MEMBER_ID'=>$ar["ID"],
		'DESCRIPTION'=>GetMessage("CTRLR_LOG_GOUSER").' ('.$USER->GetParam("LOGIN").')',
		'STATUS'=>'Y'
	);
}
else
{//Authorize as admin
	$param = 'Array(
		"LOGIN"=>"'.EscapePHPString($USER->GetParam("LOGIN")).'",
		"NAME"=>"'.EscapePHPString($USER->GetParam("FIRST_NAME")).'",
		"LAST_NAME"=>"'.EscapePHPString($USER->GetParam("LAST_NAME")).'",
		"EMAIL"=>"'.EscapePHPString($USER->GetParam("EMAIL")).'",
	)';
	$query = '
	CControllerClient::AuthorizeAdmin('.$param.');
	LocalRedirect("/");
	';
	$arControllerLog = Array(
		'NAME'=>'AUTH',
		'CONTROLLER_MEMBER_ID'=>$ar["ID"],
		'DESCRIPTION'=>GetMessage("CTRLR_LOG_GOADMIN").' ('.$USER->GetParam("LOGIN").')',
		'STATUS'=>'Y'
	);
}
CControllerLog::Add($arControllerLog);

$result = CControllerMember::RunCommandRedirect($ar["ID"], $query, Array(), false);
if($result!==false)
{
	LocalRedirect($ar["URL"]."/bitrix/main_controller.php?lang=".LANGUAGE_ID, true);
}
else
{
	$e = $APPLICATION->GetException();
	require_once(dirname(__FILE__)."/../../main/include/prolog_admin_after.php");
	ShowError("Error: ".$e->GetString());
	?>
	<a href="/bitrix/admin/controller_member_admin.php?lang=<?=LANGUAGE_ID?>"><?echo GetMessage("CTRLR_GOTO_BACK")?></a>
	<?
	require_once(dirname(__FILE__)."/../../main/include/epilog_admin.php");
}
?>
