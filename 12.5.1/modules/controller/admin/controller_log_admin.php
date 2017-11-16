<?
/*
##############################################
# Bitrix: SiteManager                        #
# Copyright (c) 2002-2007 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:sources@bitrixsoft.com              #
##############################################
*/
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

CModule::IncludeModule("controller");
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/controller/prolog.php");
IncludeModuleLangFile(__FILE__);

$MOD_RIGHT = $APPLICATION->GetGroupRight("controller");
if($MOD_RIGHT<"V") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));


$err_mess = "File: ".__FILE__."<br>Line: ";

$sTableID = "t_controll_log";
$oSort = new CAdminSorting($sTableID, "timestamp_x", "desc");
$lAdmin = new CAdminList($sTableID, $oSort);

$arLogNames = CControllerLog::GetNameArray();
$arTaskNames = CControllerTask::GetTaskArray();

$arFilterRows = array(
		GetMessage("CTRL_LOG_ADMIN_FILTER_STATUS"),
		GetMessage("CTRL_LOG_ADMIN_FILTER_DESC"),
		"ID",
		GetMessage("CTRL_LOG_ADMIN_FILTER_CLIENT"),
		GetMessage("CTRL_LOG_ADMIN_FILTER_IDCLIENT"),
		GetMessage("CTRL_LOG_ADMIN_FILTER_TASK"),
		GetMessage("CTRL_LOG_ADMIN_FILTER_TASKID"),
		GetMessage("CTRL_LOG_ADMIN_FILTER_CREATED"),
	);


$filter = new CAdminFilter(
	$sTableID."_filter_id",
	$arFilterRows
);


$arFilterFields = Array(
	"find_name",
	"find_name2",
	"find_description",
	"find_id",
	"find_status",
	"find_task_id",
	"find_task_name",
	"find_controller_member_id",
	"find_controller_member_name",
	"find_timestamp_x_from",
	"find_timestamp_x_to",
);

$lAdmin->InitFilter($arFilterFields);

$arFilter = Array(
	"ID"=>$find_id,
	"CONTROLLER_MEMBER_ID"=>$find_controller_member_id,
	"STATUS"=>$find_status,
	"TASK_ID"=>$find_task_id,
	"%NAME"=>(strlen($find_name2)>0 ? $find_name2 : $find_name),
	"%DESCRIPTION"=>$find_description,
	"%TASK_NAME"=>$find_task_name,
	"%CONTROLLER_MEMBER_NAME"=>$find_controller_member_name,
	">=TIMESTAMP_X"=>$find_timestamp_x_from,
	"<=TIMESTAMP_X"=>$find_timestamp_x_to,
	);

if($MOD_RIGHT>="V" && $arID = $lAdmin->GroupAction())
{
	if($_REQUEST['action_target']=='selected')
	{
		$rsData = CControllerLog::GetList(Array($by=>$order), $arFilter);
		while($arRes = $rsData->Fetch())
			$arID[] = $arRes['ID'];
	}

	foreach($arID as $ID)
	{
		if(strlen($ID)<=0)
			continue;
		$ID = IntVal($ID);

		switch($_REQUEST['action'])
		{
			case "delete":
				@set_time_limit(0);
				$DB->StartTransaction();
				if(!CControllerLog::Delete($ID))
				{
					$DB->Rollback();
					$lAdmin->AddGroupError(GetMessage("CTRL_LOG_ADMIN_ERR_DELETE"), $ID);
				}
				$DB->Commit();
			break;
		}
	}
}

$rsData = CControllerLog::GetList(Array($by=>$order), $arFilter);
$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart();


$lAdmin->NavText($rsData->GetNavPrint(GetMessage("CTRL_LOG_ADMIN_PAGETITLE")));


$arHeaders = Array();
$arHeaders[] = Array("id"=>"TIMESTAMP_X", "content"=>GetMessage("CTRL_LOG_ADMIN_COLUMN_CREATED"), "default"=>true, "sort" => "timestamp_x");
$arHeaders[] = Array("id"=>"NAME", "content"=>GetMessage("CTRL_LOG_ADMIN_COLUMN_NAME"), "default"=>true, "sort" => "name");
$arHeaders[] = Array("id"=>"CONTROLLER_MEMBER_NAME", "content"=>GetMessage("CTRL_LOG_ADMIN_FILTER_CLIENT"), "default"=>true, "sort" => "controller_member_name");
$arHeaders[] = Array("id"=>"STATUS", "content"=>GetMessage("CTRL_LOG_ADMIN_FILTER_STATUS"), "default"=>true, "sort" => "status");
$arHeaders[] = Array("id"=>"TASK_NAME", "content"=>GetMessage("CTRL_LOG_ADMIN_FILTER_TASK"), "default"=>true, "sort" => "task_name");
$arHeaders[] = Array("id"=>"USER", "content"=>GetMessage("CTRL_LOG_ADMIN_COLUMN_USER"), "default"=>true);
$arHeaders[] = Array("id"=>"DESCRIPTION", "content"=>GetMessage("CTRL_LOG_ADMIN_FILTER_DESC"));
$arHeaders[] = Array("id"=>"ID", "content"=>"ID", "default"=>true, "sort" => "id");

$lAdmin->AddHeaders($arHeaders);

while($arRes = $rsData->NavNext(true, "f_"))
{
	$row =& $lAdmin->AddRow($f_ID, $arRes);

	$row->AddViewField("CONTROLLER_MEMBER_NAME", '<a href="controller_member_edit.php?ID='.$f_CONTROLLER_MEMBER_ID.'">'.$f_CONTROLLER_MEMBER_NAME.' ['.$f_CONTROLLER_MEMBER_ID.']</a>');
	if($f_TASK_ID>0)
		$row->AddViewField("TASK_NAME", htmlspecialcharsex($arTaskNames[$f_TASK_NAME]).' ['.$f_TASK_ID.']');
	$row->AddViewField("NAME", (isset($arLogNames[$f_NAME])? htmlspecialcharsex($arLogNames[$f_NAME]) : $f_NAME));
	if($f_USER_ID>0)
		$row->AddViewField("USER", '<a href="/bitrix/admin/user_edit.php?ID='.$f_USER_ID.'&lang='.LANGUAGE_ID.'">'.$f_USER_NAME.' '.$f_USER_LAST_NAME.' ('.$f_USER_LOGIN.')</a>');
	$row->AddViewField("STATUS", ($f_STATUS=='Y'?GetMessage("CTRL_LOG_ADMIN_COLUMN_STATUS_OK"):GetMessage("CTRL_LOG_ADMIN_COLUMN_STATUS_ERR")));

	$arActions = Array();

	$arActions[] = array(
		"ICON"=>"list",
		"TEXT"=>GetMessage("CTRL_LOG_ADMIN_MENU_DETAIL"),
		"ACTION"=>"jsUtils.OpenWindow('controller_log_detail.php?lang=".LANG."&ID=".$f_ID."', '700', '550');",
		"DEFAULT" => "Y",
	);

	$arActions[] = array(
		"ICON"=>"delete",
		"TEXT"=>GetMessage("CTRL_LOG_ADMIN_MENU_DEL"),
		"ACTION"=>"if(confirm('".GetMessage("CTRL_LOG_ADMIN_MENU_DEL_CONFIRM")."')) ".$lAdmin->ActionDoGroup($f_ID, "delete"),
	);

	$row->AddActions($arActions);
}

$lAdmin->AddFooter(
	array(
		array("title"=>GetMessage("MAIN_ADMIN_LIST_SELECTED"), "value"=>$rsData->SelectedRowsCount()),
		array("counter"=>true, "title"=>GetMessage("MAIN_ADMIN_LIST_CHECKED"), "value"=>"0"),
	)
);

if ($MOD_RIGHT>="V")
{
	$lAdmin->AddGroupActionTable(Array(
		"delete"=>GetMessage("MAIN_ADMIN_LIST_DELETE"),
		)
	);
}

$lAdmin->AddAdminContextMenu();

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("CTRL_LOG_ADMIN_TITLE"));
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");
?>
<form name="form1" method="GET" action="<?echo $APPLICATION->GetCurPage()?>?">
<?$filter->Begin();?>
<tr>
	<td nowrap><?=GetMessage("CTRL_LOG_ADMIN_COLUMN_NAME")?>:</td>
	<td nowrap>
		<select name="find_name">
			<option value=""></option>
			<?foreach($arLogNames as $name_id=>$name_value):?>
			<option value="<?=$name_id?>"><?=htmlspecialcharsex($name_value)?></option>
			<?endforeach;?>
		</select>
		<input type="text" name="find_name2" value="<?echo htmlspecialcharsbx($find_name2)?>" size="15"><?//=ShowFilterLogicHelp()?>
	</td>
</tr>

<tr>
	<td nowrap><?=GetMessage("CTRL_LOG_ADMIN_FILTER_STATUS")?>:</td>
	<td nowrap>
		<select name="find_status">
			<option value=""><?echo GetMessage("CTRL_LOG_ADMIN_FILTER_ANY")?></option>
			<option value="Y"<?if($find_status=="Y")echo ' selected'?>><?echo GetMessage("CTRL_LOG_ADMIN_COLUMN_STATUS_OK")?></option>
			<option value="N"<?if($find_status=="N")echo ' selected'?>><?echo GetMessage("CTRL_LOG_ADMIN_COLUMN_STATUS_ERR")?></option>
		</select>
	</td>
</tr>
<tr>
	<td nowrap><?=GetMessage("CTRL_LOG_ADMIN_FILTER_DESC")?>:</td>
	<td nowrap><input type="text" name="find_description" value="<?echo htmlspecialcharsbx($find_description)?>" size="47"><?//=ShowFilterLogicHelp()?></td>
</tr>

<tr>
	<td nowrap>ID:</td>
	<td nowrap><input type="text" name="find_id" value="<?echo htmlspecialcharsbx($find_id)?>" size="47"><?//=ShowFilterLogicHelp()?></td>
</tr>

<tr>
	<td nowrap><?=GetMessage("CTRL_LOG_ADMIN_FILTER_CLIENT")?>:</td>
	<td nowrap><input type="text" name="find_controller_member_name" value="<?echo htmlspecialcharsbx($find_controller_member_name)?>" size="47"><?//=ShowFilterLogicHelp()?></td>
</tr>

<tr>
	<td nowrap><?=GetMessage("CTRL_LOG_ADMIN_FILTER_IDCLIENT")?>:</td>
	<td nowrap><input type="text" name="find_controller_member_id" value="<?echo htmlspecialcharsbx($find_controller_member_id)?>" size="47"><?//=ShowFilterLogicHelp()?></td>
</tr>

<tr>
	<td nowrap><?=GetMessage("CTRL_LOG_ADMIN_FILTER_TASK")?>:</td>
	<td nowrap><input type="text" name="find_controller_task_name" value="<?echo htmlspecialcharsbx($find_controller_task_name)?>" size="47"><?//=ShowFilterLogicHelp()?></td>
</tr>

<tr>
	<td nowrap><?=GetMessage("CTRL_LOG_ADMIN_FILTER_TASKID")?>:</td>
	<td nowrap><input type="text" name="find_controller_task_id" value="<?echo htmlspecialcharsbx($find_controller_task_id)?>" size="47"><?//=ShowFilterLogicHelp()?></td>
</tr>

<tr>
	<td nowrap><?=GetMessage("CTRL_LOG_ADMIN_FILTER_CREATED")?>:</td>
	<td nowrap><?echo CalendarPeriod("find_timestamp_x_from", $find_timestamp_x_from, "find_timestamp_x_to", $find_timestamp_x_to, "form1", "Y")?></td>
</tr>

<?$filter->Buttons(array("table_id"=>$sTableID, "url"=>$APPLICATION->GetCurPage(), "form"=>"form1"));$filter->End();?>

</form>

<?$lAdmin->DisplayList();?>


<?require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");?>
