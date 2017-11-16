<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

if(!CModule::IncludeModule("controller"))
	die('The controller module is not installed!');

require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/controller/prolog.php");
IncludeModuleLangFile(__FILE__);

$MOD_RIGHT = $APPLICATION->GetGroupRight("controller");
if ($MOD_RIGHT <= "T")
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$entity_id = "CONTROLLER_MEMBER";
$sTableID = "t_controll_admin";
$oSort = new CAdminSorting($sTableID, "timestamp_x", "desc");
$lAdmin = new CAdminList($sTableID, $oSort);

$arFilterRows = array(
	"ID" => "ID",
	"URL" => GetMessage("CTRL_MEMB_ADMIN_FILTER_URL"),
	"GROUP" => GetMessage("CTRL_MEMB_ADMIN_FILTER_GROUP"),
	"UNIQID" => GetMessage("CTRL_MEMB_ADMIN_FILTER_UNIQID"),
	"ACTIVE" => GetMessage("CTRL_MEMB_ADMIN_FILTER_ACTIVE"),
	"DISCONN" => GetMessage("CTRL_MEMB_ADMIN_FILTER_DISCONN"),
	"MODIFIED" => GetMessage("CTRL_MEMB_ADMIN_FILTER_MODIFIED"),
	"CREATED" => GetMessage("CTRL_MEMB_ADMIN_FILTER_CREATED"),
	"ACT_FROM" => GetMessage("CTRL_MEMB_ADMIN_FILTER_ACT_FROM"),
	"ACT_TO" => GetMessage("CTRL_MEMB_ADMIN_FILTER_ACT_TO"),
	"CONTACT_PERSON" => GetMessage("CTRL_MEMB_ADMIN_CONTACT_PERSON"),
	"EMAIL" => GetMessage("CTRL_MEMB_ADMIN_EMAIL"),
);
$USER_FIELD_MANAGER->AddFindFields($entity_id, $arFilterRows);

$filter = new CAdminFilter(
	$sTableID."_filter_id",
	$arFilterRows
);

$arFilterFields = array(
	"find_name",
	"find_id",
	"find_active",
	"find_disconnected",
	"find_active_from_from",
	"find_active_from_to",
	"find_active_to_from",
	"find_active_to_to",
	"find_controller_group_id",
	"find_timestamp_x_from",
	"find_timestamp_x_to",
	"find_created_from",
	"find_created_to",
	"find_member_id",
	"find_url",
	"find_contact_person",
	"find_email",
);
$USER_FIELD_MANAGER->AdminListAddFilterFields($entity_id, $arFilterFields);

$lAdmin->InitFilter($arFilterFields);

$arFilter = array(
	"ID" => $find_id,
	"%NAME" => $find_name,
	"%EMAIL" => $find_email,
	"%CONTACT_PERSON" => $find_contact_person,
	"ACTIVE" => $find_active,
	"DISCONNECTED" => $find_disconnected,
	">=DATE_ACTIVE_FROM" => $find_active_from_from,
	"<=DATE_ACTIVE_FROM" => $find_active_from_to,
	">=DATE_ACTIVE_TO" => $find_active_to_from,
	"<=DATE_ACTIVE_TO" => $find_active_to_to,
	"CONTROLLER_GROUP_ID" => $find_controller_group_id,
	">=TIMESTAMP_X" => $find_timestamp_x_from,
	"<=TIMESTAMP_X" => $find_timestamp_x_to,
	">=DATE_CREATE" => $find_created_from,
	"<=DATE_CREATE" => $find_created_to,
	"%MEMBER_ID" => $find_member_id,
	"%URL" => $find_url,
);
$USER_FIELD_MANAGER->AdminListAddFilter($entity_id, $arFilter);

$arGroups = array();
$dbr_groups = CControllerGroup::GetList(array("SORT" => "ASC"));
while ($ar_groups = $dbr_groups->Fetch())
	$arGroups[$ar_groups["ID"]] = $ar_groups["NAME"];

if ($MOD_RIGHT >= "V" && $lAdmin->EditAction())
{
	foreach ($FIELDS as $ID => $arFields)
	{
		$ID = intval($ID);
		if (!$lAdmin->IsUpdated($ID))
			continue;

		$DB->StartTransaction();
		$USER_FIELD_MANAGER->AdminListPrepareFields($entity_id, $arFields);
		if (!CControllerMember::Update($ID, $arFields))
		{
			$e = $APPLICATION->GetException();
			$lAdmin->AddUpdateError(GetMessage("CTRL_MEMB_ADMIN_SAVE_ERR")." #".$ID.": ".$e->GetString(), $ID);
			$DB->Rollback();
		}
		$DB->Commit();
	}
}

if ($MOD_RIGHT >= "V" && $arID = $lAdmin->GroupAction())
{
	if ($_REQUEST['action_target'] == 'selected')
	{
		$rsData = CControllerMember::GetList(array(
			$by => $order,
		), $arFilter);
		while ($arRes = $rsData->Fetch())
			$arID[] = $arRes['ID'];
	}

	foreach ($arID as $ID)
	{
		if (strlen($ID) <= 0)
			continue;

		$ID = IntVal($ID);
		switch ($_REQUEST['action'])
		{
		case "delete":
			if ($MOD_RIGHT >= "W")
			{
				@set_time_limit(0);
				$DB->StartTransaction();
				if (!CControllerMember::Delete($ID))
				{
					$DB->Rollback();
					$lAdmin->AddGroupError(GetMessage("CTRL_MEMB_ADMIN_DEL_ERR"), $ID);
				}
				$DB->Commit();
			}
			break;

		case "activate":
		case "deactivate":
			$arFields = array(
				"ACTIVE" => ($_REQUEST['action'] == "activate" ? "Y" : "N"),
			);
			if (!CControllerMember::Update($ID, $arFields))
				if ($e = $APPLICATION->GetException())
					$lAdmin->AddGroupError(GetMessage("CTRL_MEMB_ADMIN_SAVE_ERR")." ".$ID.": ".$e->GetString(), $ID);

			break;

		case "disconnect":
			if (!CControllerMember::UnRegister($ID))
				if ($e = $APPLICATION->GetException())
					$lAdmin->AddGroupError(GetMessage("CTRL_MEMB_ADMIN_DISC_ERR")." ".$ID.": ".$e->GetString(), $ID);

			break;

		case "update_settings":
			if (!CControllerMember::SetGroupSettings($ID))
				if ($e = $APPLICATION->GetException())
					$lAdmin->AddGroupError(GetMessage("CTRL_MEMB_ADMIN_UPDSET_ERR").$ID.": ".$e->GetString(), $ID);

			break;

		case "site_update":
			if (!CControllerMember::SiteUpdate($ID))
				if ($e = $APPLICATION->GetException())
					$lAdmin->AddGroupError(GetMessage("CTRL_MEMB_ADMIN_UPD_ERR").$ID.": ".$e->GetString(), $ID);

			break;

		case "update_counters":
			if (!CControllerMember::UpdateCounters($ID))
				if ($e = $APPLICATION->GetException())
					$lAdmin->AddGroupError(GetMessage("CTRL_MEMB_ADMIN_UPDCNT_ERR").$ID.": ".$e->GetString(), $ID);

			break;
		}
	}
}


$arHeaders = array(
	array(
		"id" => "TIMESTAMP_X",
		"content" => GetMessage("CTRL_MEMB_ADMIN_COLUMN_MODIFIED"),
		"default" => true,
		"sort" => "timestamp_x",
	),
	array(
		"id" => "MODIFIED_BY",
		"content" => GetMessage("CTRL_MEMB_ADMIN_COLUMN_MODIFIEDBY"),
		"default" => true,
		"sort" => "modified_by",
	),
	array(
		"id" => "NAME",
		"content" => GetMessage("CTRL_MEMB_ADMIN_COLUMN_NAME"),
		"default" => true,
		"sort" => "name",
	),
	array(
		"id" => "URL",
		"content" => GetMessage("CTRL_MEMB_ADMIN_FILTER_URL"),
		"default" => true,
		"sort" => "URL",
	),
	array(
		"id" => "CONTACT_PERSON",
		"content" => GetMessage("CTRL_MEMB_ADMIN_CONTACT_PERSON"),
		"sort" => "CONTACT_PERSON",
	),
	array(
		"id" => "EMAIL",
		"content" => GetMessage("CTRL_MEMB_ADMIN_EMAIL"),
		"sort" => "URL",
	),
	array(
		"id" => "CONTROLLER_GROUP_ID",
		"content" => GetMessage("CTRL_MEMB_ADMIN_FILTER_GROUP"),
		"default" => true,
		"sort" => "CONTROLLER_GROUP_ID",
	),
	array(
		"id" => "DISCONNECTED",
		"content" => GetMessage("CTRL_MEMB_ADMIN_FILTER_DISCONN"),
		"default" => true,
		"sort" => "active",
	),
	array(
		"id" => "ACTIVE",
		"content" => GetMessage("CTRL_MEMB_ADMIN_COLUMN_ACTIVE"),
		"default" => true,
		"sort" => "active",
		"align" => "center",
	),
	array(
		"id" => "DATE_ACTIVE_FROM",
		"content" => GetMessage("CTRL_MEMB_ADMIN_FILTER_ACT_FROM"),
		"sort" => "DATE_ACTIVE_FROM",
	),
	array(
		"id" => "DATE_ACTIVE_TO",
		"content" => GetMessage("CTRL_MEMB_ADMIN_FILTER_ACT_TO"),
		"sort" => "DATE_ACTIVE_TO",
	),
	array(
		"id" => "DATE_CREATE",
		"content" => GetMessage("CTRL_MEMB_ADMIN_COLUMN_CREATED"),
		"sort" => "DATE_CREATE",
	),
	array(
		"id" => "CREATED_BY",
		"content" => GetMessage("CTRL_MEMB_ADMIN_COLUMN_CREATEDBY"),
		"sort" => "CREATED_BY",
	),
	array(
		"id" => "MEMBER_ID",
		"content" => GetMessage("CTRL_MEMB_ADMIN_FILTER_UNIQID"),
		"sort" => "MEMBER_ID",
	),
	array(
		"id" => "COUNTERS_UPDATED",
		"content" => GetMessage("CTRL_MEMB_ADMIN_COLUMN_COUNTER_UPD"),
		"sort" => "COUNTERS_UPDATED",
	),
	array(
		"id" => "COUNTER_FREE_SPACE",
		"content" => GetMessage("CTRL_MEMB_ADMIN_COLUMN_COUNTER_FREE"),
		"sort" => "COUNTER_FREE_SPACE",
		"align" => "right",
	),
	array(
		"id" => "COUNTER_SITES",
		"content" => GetMessage("CTRL_MEMB_ADMIN_COLUMN_COUNTER_SITES"),
		"sort" => "COUNTER_SITES",
		"align" => "right",
	),
	array(
		"id" => "COUNTER_USERS",
		"content" => GetMessage("CTRL_MEMB_ADMIN_COLUMN_COUNTER_USERS"),
		"sort" => "COUNTER_USERS",
		"align" => "right",
	),
	array(
		"id" => "COUNTER_LAST_AUTH",
		"content" => GetMessage("CTRL_MEMB_ADMIN_COLUMN_COUNTER_LAST_AU"),
		"sort" => "COUNTER_LAST_AUTH",
	),
	array(
		"id" => "NOTES",
		"content" => GetMessage("CTRL_MEMB_ADMIN_COLUMN_NOTES"),
	),
	array(
		"id" => "ID",
		"content" => "ID",
		"default" => true,
		"sort" => "id",
		"align" => "right",
	),
);
if (ControllerIsSharedMode())
{
	$arHeaders[] = array(
		"id" => "SHARED_KERNEL",
		"content" => GetMessage("CTRL_MEMB_ADMIN_COLUMN_SHARED_KERN"),
		"sort" => "SHARED_KERNEL",
		"align" => "center",
	);
}

$arCounters = array();
$rsCounters = CControllerCounter::GetList();
while($arCounter = $rsCounters->Fetch())
{
	$key = "COUNTER_".$arCounter["ID"];
	$arCounters[$key] = $arCounter;
	$arHeaders[] = array(
		"id" => $key,
		"content" => htmlspecialcharsex($arCounter["NAME"]),
		"sort" => $key,
		"align" => ($arCounter["COUNTER_FORMAT"] == "F"? "right": "left"),
	);
}

$USER_FIELD_MANAGER->AdminListAddHeaders($entity_id, $arHeaders);

$lAdmin->AddHeaders($arHeaders);

$arSelect = $lAdmin->GetVisibleHeaderColumns();
$arSelect[] = "ID";
$arSelect[] = "DISCONNECTED";
$arSelect[] = "SHARED_KERNEL";
if(in_array("MODIFIED_BY", $arSelect))
	$arSelect[] = "MODIFIED_BY_USER";
if(in_array("CREATED_BY", $arSelect))
	$arSelect[] = "CREATED_BY_USER";

$rsData = CControllerMember::GetList(array($by=>$order), $arFilter, $arSelect);
$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart();

$lAdmin->NavText($rsData->GetNavPrint(GetMessage("CTRL_MEMB_ADMIN_NAVSTRING")));

while ($arRes = $rsData->NavNext(true, "f_"))
{
	$row = & $lAdmin->AddRow($f_ID, $arRes);
	$USER_FIELD_MANAGER->AddUserFields($entity_id, $arRes, $row);

	$row->AddViewField("MODIFIED_BY", '[<a href="user_edit.php?ID='.$f_MODIFIED_BY.'">'.$f_MODIFIED_BY.'</a>] '.$f_MODIFIED_BY_USER);
	$row->AddViewField("CREATED_BY", '[<a href="user_edit.php?ID='.$f_CREATED_BY.'">'.$f_CREATED_BY.'</a>] '.$f_CREATED_BY_USER);
	$row->AddCheckField("ACTIVE");
	if (ControllerIsSharedMode())
		$row->AddCheckField("SHARED_KERNEL");

	$row->AddInputField("NAME", array( "size" => "35" ));
	$row->AddInputField("URL", array( "size" => "35" ));

	if ($f_DISCONNECTED == 'Y')
		$str = '<span class="adm-lamp adm-lamp-in-list adm-lamp-red"></span>'.GetMessage("admin_lib_list_yes");
	elseif ($f_DISCONNECTED == 'I')
		$str = GetMessage("CTRL_MEMB_ADMIN_DISCON");
	else
		$str = GetMessage("admin_lib_list_no");

	$row->AddViewField("DISCONNECTED", $str);

	$row->AddViewField("URL", '<a href="'.$f_URL.'">'.$f_URL.'</a>');
	$row->AddInputField("EMAIL", array( "size" => "35" ));
	$row->AddInputField("CONTACT_PERSON", array( "size" => "35" ));

	if ($f_EMAIL != '')
		$row->AddViewField("EMAIL", '<a href="mailto:'.$f_EMAIL.'">'.$f_EMAIL.'</a>');

	$row->AddSelectField("CONTROLLER_GROUP_ID", $arGroups);
	foreach ($arCounters as $key => $arCounter)
	{
		if (isset($arRes[$key]))
		{
			$html = CControllerCounter::FormatValue($arRes[$key], $arCounter["COUNTER_FORMAT"]);
			if ($arCounter["COUNTER_FORMAT"] == "F")
				$html = str_replace(" ", "&nbsp;", $html);
			$row->AddViewField($key, $html);
		}
	}

	$arActions = array(
		array(
			"ICON" => "edit",
			"DEFAULT" => "Y",
			"TEXT" => GetMessage("CTRL_MEMB_ADMIN_MENU_EDIT"),
			"ACTION" => $lAdmin->ActionRedirect("controller_member_edit.php?ID=".$f_ID."&lang=".LANG),
		),
		array(
			"SEPARATOR" => true,
		),
	);
	if ($f_DISCONNECTED == 'N')
	{
		$arActions[] = array(
			"ICON" => "other",
			"TEXT" => GetMessage("CTRL_MEMB_ADMIN_MENU_GOADMIN"),
			"ACTION" => $lAdmin->ActionRedirect("controller_goto.php?member=".$f_ID."&lang=".LANG),
		);
		if ($f_SHARED_KERNEL != 'Y')
		{
			$arActions[] = array(
				"ICON" => "other",
				"TEXT" => GetMessage("CTRL_MEMB_ADMIN_MENU_UPD"),
				"ACTION" => $lAdmin->ActionDoGroup($f_ID, "site_update"),
			);
		}
		$arActions[] = array(
			"ICON" => "other",
			"TEXT" => GetMessage("CTRL_MEMB_ADMIN_MENU_UPDSETT"),
			"ACTION" => $lAdmin->ActionDoGroup($f_ID, "update_settings"),
		);
		$arActions[] = array(
			"ICON" => "other",
			"TEXT" => GetMessage("CTRL_MEMB_ADMIN_MENU_UPDCNT"),
			"ACTION" => $lAdmin->ActionDoGroup($f_ID, "update_counters"),
		);
		$arActions[] = array(
			"ICON" => "other",
			"TEXT" => GetMessage("CTRL_MEMB_ADMIN_MENU_RUNPHP"),
			"ACTION" => $lAdmin->ActionRedirect("controller_run_command.php?controller_member_id=".$f_ID."&lang=".LANG),
		);
	}
	$arActions[] = array(
		"ICON" => "list",
		"TEXT" => GetMessage("CTRL_MEMB_ADMIN_MENU_LOG"),
		"ACTION" => $lAdmin->ActionRedirect("controller_log_admin.php?find_controller_member_id=".$f_ID."&set_filter=Y&lang=".LANG),
	);
	$arActions[] = array(
		"SEPARATOR" => true,
	);
	if ($f_DISCONNECTED == 'N')
		$arActions[] = array(
			"ICON" => "other",
			"TEXT" => GetMessage("CTRL_MEMB_ADMIN_MENU_DISC"),
			"ACTION" => "if(confirm('".GetMessage("CTRL_MEMB_ADMIN_MENU_DISC_CONFIRM")."')) ".$lAdmin->ActionDoGroup($f_ID, "disconnect"),
		);

	if ($MOD_RIGHT >= "W")
	{
		$arActions[] = array(
			"ICON" => "delete",
			"TEXT" => GetMessage("CTRL_MEMB_ADMIN_MENU_DEL"),
			"ACTION" => "if(confirm('".GetMessage("CTRL_MEMB_ADMIN_MENU_DEL_ALERT")."')) ".$lAdmin->ActionDoGroup($f_ID, "delete"),
		);
	}
	$row->AddActions($arActions);
}

$arB = array(
	"activate" => GetMessage("MAIN_ADMIN_LIST_ACTIVATE"),
	"deactivate" => GetMessage("MAIN_ADMIN_LIST_DEACTIVATE"),
	"update_settings" => GetMessage("CTRL_MEMB_ADMIN_ACTIONBAR_UPDSETT"),
	"update_counters" => GetMessage("CTRL_MEMB_ADMIN_ACTIONBAR_UPDCNT"),
	"disconnect" => GetMessage("CTRL_MEMB_ADMIN_ACTIONBAR_DISC"),
	"site_update" => GetMessage("CTRL_MEMB_ADMIN_ACTIONBAR_UPD"),
);
if ($MOD_RIGHT == "W")
	$arB["delete"] = GetMessage("MAIN_ADMIN_LIST_DELETE");

$lAdmin->AddGroupActionTable($arB);
$aContext = array(
	array(
		"ICON" => "btn_new",
		"TEXT" => GetMessage("MAIN_ADD"),
		"LINK" => "controller_member_edit.php?lang=".LANG,
		"TITLE" => GetMessage("MAIN_ADD"),
	),
);

$lAdmin->AddAdminContextMenu($aContext);

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("CTRL_MEMB_ADMIN_TITLE"));
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");
?>
<form name="form1" method="GET" action="<?echo $APPLICATION->GetCurPage()?>?">
<?$filter->Begin();?>
<tr>
	<td nowrap><?=GetMessage("CTRL_MEMB_ADMIN_COLUMN_NAME")?>:</td>
	<td nowrap><input type="text" name="find_name" value="<?echo htmlspecialcharsbx($find_name)?>" size="47"><?//=ShowFilterLogicHelp()?></td>
</tr>

<tr>
	<td nowrap>ID:</td>
	<td nowrap><input type="text" name="find_id" value="<?echo htmlspecialcharsbx($find_id)?>" size="47"><?//=ShowFilterLogicHelp()?></td>
</tr>

<tr>
	<td nowrap><?=GetMessage("CTRL_MEMB_ADMIN_FILTER_URL")?>:</td>
	<td nowrap><input type="text" name="find_url" value="<?echo htmlspecialcharsbx($find_url)?>" size="47"><?//=ShowFilterLogicHelp()?></td>
</tr>
<tr>
	<td nowrap><?=GetMessage("CTRL_MEMB_ADMIN_FILTER_GROUP")?></td>
	<td>
	<select name="find_controller_group_id">
		<option value=""><?echo GetMessage("CTRL_MEMB_ADMIN_FILTER_ANY")?></option>
	<?foreach($arGroups as $group_id=>$group_name):?>
		<option value="<?=htmlspecialcharsbx($group_id)?>" <?if($group_id==$find_controller_group_id)echo "selected"?>><?=htmlspecialcharsex($group_name)?></option>
	<?endforeach;?>
	</select>
	</td>
</tr>
<tr>
	<td nowrap><?=GetMessage("CTRL_MEMB_ADMIN_FILTER_UNIQID")?>:</td>
	<td nowrap><input type="text" name="find_member_id" value="<?echo htmlspecialcharsbx($find_member_id)?>" size="47"><?//=ShowFilterLogicHelp()?></td>
</tr>
<tr>
	<td nowrap><?=GetMessage("CTRL_MEMB_ADMIN_FILTER_ACTIVE")?>:</td>
	<td nowrap><?
		$arr = array("reference"=>Array(GetMessage("CTRL_MEMB_ADMIN_FILTER_ANY2"),  GetMessage("MAIN_YES"), GetMessage("MAIN_NO")), "reference_id"=>array("", "Y","N"));
		echo SelectBoxFromArray("find_active", $arr, htmlspecialcharsbx($find_active), GetMessage("MAIN_ALL"));
		?></td>
</tr>

<tr>
	<td nowrap><?=GetMessage("CTRL_MEMB_ADMIN_FILTER_DISCONNECTED")?>:</td>
	<td nowrap><?
		$arr = array("reference"=>Array(GetMessage("CTRL_MEMB_ADMIN_FILTER_ANY2"),  GetMessage("MAIN_YES"), GetMessage("MAIN_NO"), GetMessage("CTRL_MEMB_ADMIN_DISCON")), "reference_id"=>array("", "Y", "N", "I"));
		echo SelectBoxFromArray("find_disconnected", $arr, htmlspecialcharsbx($find_disconnected), GetMessage("MAIN_ALL"));
		?></td>
</tr>

<tr>
	<td nowrap><?=GetMessage("CTRL_MEMB_ADMIN_FILTER_MODIFIED")?>:</td>
	<td nowrap><?echo CalendarPeriod("find_timestamp_x_from", $find_timestamp_x_from, "find_timestamp_x_to", $find_timestamp_x_to, "form1", "Y")?></td>
</tr>
<tr>
	<td nowrap><?=GetMessage("CTRL_MEMB_ADMIN_FILTER_CREATED")?>:</td>
	<td nowrap><?echo CalendarPeriod("find_created_from", $find_created_from, "find_created_to", $find_created_to, "form1", "Y")?></td>
</tr>
<tr>
	<td nowrap><?=GetMessage("CTRL_MEMB_ADMIN_FILTER_ACT_FROM")?>:</td>
	<td nowrap><?echo CalendarPeriod("find_active_from_from", $find_active_from_from, "find_active_from_to", $find_active_from_to, "form1", "Y")?></td>
</tr>
<tr>
	<td nowrap><?=GetMessage("CTRL_MEMB_ADMIN_FILTER_ACT_TO")?>:</td>
	<td nowrap><?echo CalendarPeriod("find_active_to_from", $find_active_to_from, "find_active_to_to", $find_active_to_to, "form1", "Y")?></td>
</tr>
<tr>
	<td nowrap><?=GetMessage("CTRL_MEMB_ADMIN_CONTACT_PERSON")?>:</td>
	<td nowrap><input type="text" name="find_contact_person" value="<?echo htmlspecialcharsbx($find_contact_person)?>" size="47"><?//=ShowFilterLogicHelp()?></td>
</tr>
<tr>
	<td nowrap><?=GetMessage("CTRL_MEMB_ADMIN_EMAIL")?>:</td>
	<td nowrap><input type="text" name="find_email" value="<?echo htmlspecialcharsbx($find_email)?>" size="47"><?//=ShowFilterLogicHelp()?></td>
</tr>

<?
$USER_FIELD_MANAGER->AdminListShowFilter($entity_id);
$filter->Buttons(array(
	"table_id" => $sTableID,
	"url" => $APPLICATION->GetCurPage(),
	"form" => "form1",
));
$filter->End();
?>
</form>

<?$lAdmin->DisplayList();?>

<?require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");?>
