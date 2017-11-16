<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

if(!CModule::IncludeModule("controller"))
	die('The controller module is not installed!');

require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/controller/prolog.php");
IncludeModuleLangFile(__FILE__);

$MOD_RIGHT = $APPLICATION->GetGroupRight("controller");
if($MOD_RIGHT <= "T")
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$sTableID = "t_controller_member_history";
$lAdmin = new CAdminList($sTableID);

$arFilterRows = array(
	GetMessage("CTRL_MEMB_HIST_FIELD"),
);

$filter = new CAdminFilter(
	$sTableID."_filter_id",
	$arFilterRows
);

$arFilterFields = Array(
	"find_id",
	"find_field",
);

$lAdmin->InitFilter($arFilterFields);

$arFilter = array(
	"=CONTROLLER_MEMBER_ID" => $find_id,
	"=FIELD" => $find_field,
);
foreach($arFilter as $k => $v)
	if(!strlen($v))
		unset($arFilter[$k]);

$arHeaders = array(
	array(
		"id" => "CREATED_DATE",
		"content" => GetMessage("CTRL_MEMB_HIST_CREATED_DATE"),
		"default" => true,
	),
	array(
		"id" => "FIELD",
		"content" => GetMessage("CTRL_MEMB_HIST_FIELD"),
		"default" => true,
	),
	array(
		"id"=>"USER_ID",
		"content" => GetMessage("CTRL_MEMB_HIST_USER_ID"),
		"default" => true,
	),
	array(
		"id" => "FROM_VALUE",
		"content" => GetMessage("CTRL_MEMB_HIST_FROM_VALUE"),
		"default" => true,
	),
	array(
		"id" => "TO_VALUE",
		"content" => GetMessage("CTRL_MEMB_HIST_TO_VALUE"),
		"default" => true,
	),
	array(
		"id" => "NOTES",
		"content" => GetMessage("CTRL_MEMB_HIST_NOTES"),
	),
);

$lAdmin->AddHeaders($arHeaders);

$arGroups = Array();
$dbr_groups = CControllerGroup::GetList(Array("SORT"=>"ASC"));
while($ar_groups = $dbr_groups->GetNext())
	$arGroups[$ar_groups["ID"]] = $ar_groups["NAME"];

$rsData = CControllerMember::GetLog($arFilter);
$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart();

$lAdmin->NavText($rsData->GetNavPrint(GetMessage("CTRL_MEMB_HIST_NAVSTRING")));

while($arRes = $rsData->NavNext(true, "f_"))
{
	$row =& $lAdmin->AddRow($f_ID, $arRes);

	$row->AddViewField("CREATED_DATE", $f_CREATED_DATE);
	$row->AddViewField("USER_ID", '[<a href="user_edit.php?ID='.$f_USER_ID.'&amp;lang='.LANGUAGE_ID.'">'.$f_USER_ID.'</a>] '.$f_USER_ID_USER);
	switch($f_FIELD)
	{
		case "CONTROLLER_GROUP_ID":
			$row->AddViewField("FIELD", GetMessage("CTRL_MEMB_HIST_CONTROLLER_GROUP_ID"));
			$row->AddViewField("FROM_VALUE", '[<a href="controller_group_edit.php?ID='.$f_FROM_VALUE.'&amp;lang='.LANGUAGE_ID.'">'.$f_FROM_VALUE.'</a>] '.$arGroups[$f_FROM_VALUE]);
			$row->AddViewField("TO_VALUE", '[<a href="controller_group_edit.php?ID='.$f_TO_VALUE.'&amp;lang='.LANGUAGE_ID.'">'.$f_TO_VALUE.'</a>] '.$arGroups[$f_TO_VALUE]);
			break;
		case "SITE_ACTIVE":
			$row->AddViewField("FIELD", GetMessage("CTRL_MEMB_HIST_SITE_ACTIVE"));
			$row->AddViewField("FROM_VALUE", $f_FROM_VALUE == "Y"? GetMessage("MAIN_YES"): GetMessage("MAIN_NO"));
			$row->AddViewField("TO_VALUE", $f_TO_VALUE == "Y"? GetMessage("MAIN_YES"): GetMessage("MAIN_NO"));
			break;
	}
	$row->AddViewField("NOTES", $f_NOTES);
}

$lAdmin->AddFooter(
	array(
		array("title"=>GetMessage("MAIN_ADMIN_LIST_SELECTED"), "value"=>$rsData->SelectedRowsCount()),
	)
);

$aContext = array(
	array(
		"TEXT" => GetMessage("CTRL_MEMB_HIST_BACK"),
		"LINK" => "controller_member_edit.php?ID=".intval($find_id)."&lang=".LANGUAGE_ID,
		"TITLE" => GetMessage("CTRL_MEMB_HIST_BACK_TITLE"),
		"ICON" => "btn_edit",
	),
);

$lAdmin->AddAdminContextMenu($aContext);

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("CTRL_MEMB_HIST_TITLE"));
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");
?>
<form name="form1" method="GET" action="<?echo $APPLICATION->GetCurPage()?>?">
<?$filter->Begin();?>
<tr>
	<td nowrap><?=GetMessage("CTRL_MEMB_HIST_CONTROLLER_MEMBER_ID")?>:</td>
	<td nowrap><input type="text" name="find_id" value="<?echo htmlspecialcharsbx($find_id)?>" size="47"><?//=ShowFilterLogicHelp()?></td>
</tr>
<tr>
	<td nowrap><?=GetMessage("CTRL_MEMB_HIST_FIELD")?></td>
	<td>
	<select name="find_field">
		<option value=""><?echo GetMessage("CTRL_MEMB_HIST_ANY")?></option>
		<option value="CONTROLLER_GROUP_ID" <?if($find_field == "CONTROLLER_GROUP_ID")echo "selected"?>><?echo GetMessage("CTRL_MEMB_HIST_CONTROLLER_GROUP_ID")?></option>
		<option value="SITE_ACTIVE" <?if($find_field == "SITE_ACTIVE")echo "selected"?>><?echo GetMessage("CTRL_MEMB_HIST_SITE_ACTIVE")?></option>
	</select>
	</td>
</tr>
<?$filter->Buttons(array("table_id"=>$sTableID, "url"=>$APPLICATION->GetCurPage(), "form"=>"form1"));$filter->End();?>
</form>

<?$lAdmin->DisplayList();?>

<?require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");?>
