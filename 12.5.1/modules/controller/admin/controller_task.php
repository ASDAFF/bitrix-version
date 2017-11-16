<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

define("BX_STEP_SIZE", "1");

CModule::IncludeModule("controller");
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/controller/prolog.php");
IncludeModuleLangFile(__FILE__);

$MOD_RIGHT = $APPLICATION->GetGroupRight("controller");
if($MOD_RIGHT<"V") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$arTask = CControllerTask::GetTaskArray();

$arStatus = CControllerTask::GetStatusArray();

$dbrTaskN = CControllerTask::GetList(Array(), Array("=STATUS"=>Array('N', 'P')), true);
$arTaskN = $dbrTaskN->Fetch();
$iTaskNCnt = IntVal($arTaskN['C']);

if(
	$_SERVER["REQUEST_METHOD"] == "POST"
	&& $_REQUEST['act'] == 'process'
	&& check_bitrix_sessid()
)
{
	$strError = "";
	$iStepSize = BX_STEP_SIZE;
	$iCntExecuted = intval($_REQUEST["executed"]);
	$iCntTotal = intval($_REQUEST["cnt"]);
	$tBeginTime = getmicrotime();

	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");
	if($iTaskNCnt > 0)
	{
		$dbrTask = CControllerTask::GetList(Array("ID"=>"ASC"), Array("=STATUS"=>Array('N', 'P')));
		$tTasksTime = getmicrotime();
		while($arTask = $dbrTask->Fetch())
		{
			$status = CControllerTask::ProcessTask($arTask["ID"]);

			if($status === "0" && $e = $APPLICATION->GetException())
			{
				$strError = GetMessage("CTRLR_TASK_ERR_LOCK")."<br>".$e->GetString();
				if(strpos($strError, "PLS-00201") !== false && strpos($strError, "'DBMS_LOCK'") !== false)
					$strError .= "<br>".GetMessage("CTRLR_TASK_ERR_LOCK_ADVICE");
				$APPLICATION->ResetException();
				break;
			}

			$iCntExecuted++;
			$iAvgExecTime = (getmicrotime() - $tTasksTime) / $iCntExecuted;
			//if(getmicrotime() - $tBeginTime + 1.5 * $iAvgExecTime > $iStepSize)
				break;

			while($status=="P")
			{
				$status = CControllerTask::ProcessTask($arTask["ID"]);
				if(getmicrotime() - $tBeginTime + 1.5 * $iAvgExecTime > $iStepSize)
					break;
			}
		}

		if(strlen($strError))
		{
			CAdminMessage::ShowMessage($strError);
		}
		else
		{
			CAdminMessage::ShowMessage(array(
				"TYPE" => "PROGRESS",
				"MESSAGE" => GetMessage("CTRLR_TASK_PROGRESS"),
				"DETAILS" => GetMessage("CTRLR_TASK_PROGRESS_BAR")." $iCntExecuted ".GetMessage("CTRLR_TASK_PROGRESS_BAR_FROM")." $iCntTotal #PROGRESS_BAR#",
				"HTML" => true,
				"PROGRESS_TOTAL" => $iCntTotal,
				"PROGRESS_VALUE" => $iCntExecuted,
			));
			?>
			<script>
				Start(<?echo $iCntTotal?>, <?echo $iCntExecuted?>);
			</script>
			<?
		}
	}
	else
	{
		CAdminMessage::ShowMessage(array(
			"TYPE" => "PROGRESS",
			"MESSAGE" => GetMessage("CTRLR_TASK_PROGRESS"),
			"DETAILS" => GetMessage("CTRLR_TASK_PROGRESS_BAR")." $iCntExecuted ".GetMessage("CTRLR_TASK_PROGRESS_BAR_FROM")." $iCntTotal #PROGRESS_BAR#",
			"HTML" => true,
			"PROGRESS_TOTAL" => $iCntTotal,
			"PROGRESS_VALUE" => $iCntExecuted,
		));
		?>
		<script>
			CloseWaitWindow();
		</script>
		<?
	}
	require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin_js.php");
}


$err_mess = "File: ".__FILE__."<br>Line: ";

$sTableID = "t_controll_task";
$oSort = new CAdminSorting($sTableID, "timestamp_x", "desc");
$lAdmin = new CAdminList($sTableID, $oSort);

$arFilterRows = 	array(
		"ID",
		GetMessage("CTRLR_TASK_FLT_CLIENT"),
		GetMessage("CTRLR_TASK_FLT_OPERATION"),
		GetMessage("CTRLR_TASK_FLT_EXECUTED"),
		GetMessage("CTRLR_TASK_FLT_MODYFIED"),
		GetMessage("CTRLR_TASK_FLT_CREATED"),
	);

$filter = new CAdminFilter(
	$sTableID."_filter_id",
	$arFilterRows
);
$arFilterFields = Array(
	"find_status",
	"find_task_id",
	"find_id",
	"find_controller_member_id",
	"find_executed_from",
	"find_executed_to",
	"find_timestamp_x_from",
	"find_timestamp_x_to",
	"find_created_from",
	"find_created_to",
);

$lAdmin->InitFilter($arFilterFields);

if(!isset($find_status))
	$find_status = Array('N', 'P');

$arFilter = Array(
	"%TASK_ID"				=>$find_task_id,
	"ID"					=>$find_id,
	"=STATUS"				=>$find_status,
	"CONTROLLER_MEMBER_ID"	=>$find_controller_member_id,
	">=TIMESTAMP_X"			=>$find_timestamp_x_from,
	"<=TIMESTAMP_X"			=>$find_timestamp_x_to,
	">=DATE_CREATE"			=>$find_created_from,
	"<=DATE_CREATE"			=>$find_created_to,
	">=DATE_EXECUTE"		=>$find_executed_from,
	"<=DATE_EXECUTE"		=>$find_executed_to,
	);


if($MOD_RIGHT>="V" && $arID = $lAdmin->GroupAction())
{
	if($_REQUEST['action_target']=='selected')
	{
		$rsData = CControllerTask::GetList(Array($by=>$order), $arFilter);
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
				if(!CControllerTask::Delete($ID))
				{
					$DB->Rollback();
					$lAdmin->AddGroupError(GetMessage("CTRLR_TASK_ERR_DELETE"), $ID);
				}
				$DB->Commit();
				break;

			case "repeat":
				if(!CControllerTask::Update($ID, Array("STATUS"=>"N", "DATE_EXECUTE"=>false)))
					if($e = $APPLICATION->GetException())
						$lAdmin->AddGroupError(GetMessage("CTRLR_TASK_REP_DELETE")." ".$ID.": ".$e->GetString(), $ID);
				break;
		}
	}
}

$rsData = CControllerTask::GetList(Array($by=>$order), $arFilter);
$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart();


$lAdmin->NavText($rsData->GetNavPrint(GetMessage("CTRLR_TASK_NAV")));



$arHeaders = Array();
$arHeaders[] = Array("id"=>"CONTROLLER_MEMBER_NAME", "content"=>GetMessage("CTRLR_TASK_FLT_CLIENT"), "default"=>true, "sort" => "CONTROLLER_MEMBER_NAME");
$arHeaders[] = Array("id"=>"TASK_ID", "content"=>GetMessage("CTRLR_TASK_COLUMN_TASK"), "default"=>true, "sort" => "TASK_ID");
$arHeaders[] = Array("id"=>"STATUS", "content"=>GetMessage("CTRLR_TASK_COLUMN_STATUS"), "default"=>true, "sort" => "STATUS");
$arHeaders[] = Array("id"=>"DATE_EXECUTE", "content"=>GetMessage("CTRLR_TASK_COLUMN_EXEC"), "default"=>true, "sort" => "DATE_EXECUTE");
$arHeaders[] = Array("id"=>"INIT_EXECUTE", "content"=>GetMessage("CTRLR_TASK_COLUMN_ARGS"));
$arHeaders[] = Array("id"=>"RESULT_EXECUTE", "content"=>GetMessage("CTRLR_TASK_COLUMN_RESULT"), "default"=>true);
$arHeaders[] = Array("id"=>"CONTROLLER_MEMBER_URL", "content"=>GetMessage("CTRLR_TASK_COLUMN_URL"), "sort" => "CONTROLLER_MEMBER_URL");
$arHeaders[] = Array("id"=>"TIMESTAMP_X", "content"=>GetMessage("CTRLR_TASK_COLUMN_DATE_MOD"), "sort" => "timestamp_x");
$arHeaders[] = Array("id"=>"DATE_CREATE", "content"=>GetMessage("CTRLR_TASK_COLUMN_DATE_CRE"), "default"=>true, "sort" => "DATE_CREATE");
$arHeaders[] = Array("id"=>"ID", "content"=>"ID", "default"=>true, "sort" => "id");

$lAdmin->AddHeaders($arHeaders);


while($arRes = $rsData->NavNext(true, "f_"))
{
	$row =& $lAdmin->AddRow($f_ID, $arRes);

	if($f_STATUS=='N')
	{
		$row->AddViewField("RESULT_EXECUTE", '');
		$row->AddViewField("DATE_EXECUTE", '');
	}

	$row->AddViewField("STATUS", (isset($arStatus[$f_STATUS])?$arStatus[$f_STATUS]:$f_STATUS));
	$row->AddViewField("TASK_ID", (isset($arTask[$f_TASK_ID])?$arTask[$f_TASK_ID]:$f_TASK_ID));
	$row->AddViewField("CONTROLLER_MEMBER_NAME", '<a href="controller_member_edit.php?ID='.$f_CONTROLLER_MEMBER_ID.'">'.$f_CONTROLLER_MEMBER_NAME.'</a>');
	$row->AddViewField("CONTROLLER_MEMBER_URL", '<a href="'.$f_CONTROLLER_MEMBER_URL.'">'.$f_CONTROLLER_MEMBER_URL.'</a>');

	$arActions = Array();

	$arActions[] = array(
		"ICON"=>"other",
		"TEXT"=>GetMessage("CTRLR_TASK_MENU_REPEAT"),
		"ACTION"=>"if(confirm('".GetMessage("CTRLR_TASK_MENU_REPEAT_CONFIRM")."')) ".$lAdmin->ActionDoGroup($f_ID, "repeat"),
	);

	$arActions[] = array("SEPARATOR"=>true);

	$arActions[] = array(
		"ICON"=>"delete",
		"TEXT"=>GetMessage("CTRLR_TASK_MENU_CANCEL"),
		"ACTION"=>"if(confirm('".GetMessage("CTRLR_TASK_MENU_CANCEL_CONFIRM")."')) ".$lAdmin->ActionDoGroup($f_ID, "delete"),
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
		"repeat"=>GetMessage("CTRLR_TASK_REPEAT"),
		)
	);
}

$lAdmin->AddAdminContextMenu(array());

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("CTRLR_TASK_TITLE"));
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");
?>
<form name="form1" method="GET" action="<?echo $APPLICATION->GetCurPage()?>?">
<?$filter->Begin();?>

<tr>
	<td nowrap valign="top"><?=GetMessage("CTRLR_TASK_FLR_ST")?>:</td>
	<td nowrap>
		<select name="find_status[]" multiple="yes">
			<?foreach($arStatus as $status_id=>$status_name):?>
				<option value="<?=htmlspecialcharsbx($status_id)?>"<?if(in_array($status_id, $find_status))echo ' selected'?>><?=htmlspecialcharsex($status_name)?></option>
			<?endforeach?>
		</select>
	</td>
</tr>
<tr>
	<td nowrap>ID:</td>
	<td nowrap><input type="text" name="find_id" value="<?echo htmlspecialcharsbx($find_id)?>" size="47"><?//=ShowFilterLogicHelp()?></td>
</tr>
<tr>
	<td nowrap><?=GetMessage("CTRLR_TASK_FLT_CLIENT")?>:</td>
	<td nowrap><input type="text" name="find_controller_member_id" value="<?echo htmlspecialcharsbx($find_controller_member_id)?>" size="47"><?//=ShowFilterLogicHelp()?></td>
</tr>
<tr>
	<td nowrap><?=GetMessage("CTRLR_TASK_FLT_OPERATION")?>:</td>
	<td nowrap>
		<select name="find_task_id">
			<option value=""><?echo GetMessage("CTRLR_TASK_FLR_ANY")?></option>
			<?foreach($arTask as $task_id=>$task_name):?>
				<option value="<?=htmlspecialcharsbx($task_id)?>" <?if($find_task_id==$task_id)echo ' selected';?>><?=htmlspecialcharsex($task_name)?></option>
			<?endforeach?>
		</select>
</tr>
<tr>
	<td nowrap><?=GetMessage("CTRLR_TASK_FLT_EXECUTED")?>:</td>
	<td nowrap><?echo CalendarPeriod("find_executed_from", $find_executed_from, "find_executed_to", $find_executed_to, "form1", "Y")?></td>
</tr>
<tr>
	<td nowrap><?=GetMessage("CTRLR_TASK_FLT_MODYFIED")?>:</td>
	<td nowrap><?echo CalendarPeriod("find_timestamp_x_from", $find_timestamp_x_from, "find_timestamp_x_to", $find_timestamp_x_to, "form1", "Y")?></td>
</tr>
<tr>
	<td nowrap><?=GetMessage("CTRLR_TASK_FLT_CREATED")?>:</td>
	<td nowrap><?echo CalendarPeriod("find_created_from", $find_created_from, "find_created_to", $find_created_to, "form1", "Y")?></td>
</tr>

<?$filter->Buttons(array("table_id"=>$sTableID, "url"=>$APPLICATION->GetCurPage(), "form"=>"form1"));$filter->End();?>

</form>

<?
$lAdmin->BeginPrologContent();
?>
<div id="progress">
<?
	if($iTaskNCnt > 0)
	{
		CAdminMessage::ShowMessage(array(
			"TYPE" => "PROGRESS",
			"MESSAGE" => GetMessage("CTRLR_TASK_PROGRESS"),
			"DETAILS" => GetMessage("CTRLR_TASK_PROGRESS_BAR")." 0 ".GetMessage("CTRLR_TASK_PROGRESS_BAR_FROM")." $iTaskNCnt #PROGRESS_BAR#",
			"HTML" => true,
			"PROGRESS_TOTAL" => $iTaskNCnt,
			"PROGRESS_VALUE" => 0,
			"BUTTONS" => array(
				array(
					"ID" => "btn_start",
					"VALUE" => GetMessage("CTRLR_TASK_BUTTON_START"),
					"ONCLICK" => "Start($iTaskNCnt, 0);",
				),
			),
		));
	}
?>
</div>
<script>
function Start(cnt, executed)
{
	ShowWaitWindow();
	BX.ajax.post(
		'controller_task.php?lang=<?echo LANGUAGE_ID?>&<?echo bitrix_sessid_get()?>&act=process&cnt='+cnt+'&executed='+executed,
		null,
		function(result){
			BX('progress').innerHTML = result;
		}
	);
}
</script>
<?
$lAdmin->EndPrologContent();
$lAdmin->DisplayList();

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");?>
