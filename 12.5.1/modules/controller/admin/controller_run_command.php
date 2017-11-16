<?
##############################################
# Bitrix Site Manager                        #
# Copyright (c) 2002-2007 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:sources@bitrixsoft.com              #
##############################################

require_once(dirname(__FILE__)."/../../main/include/prolog_admin_before.php");
require_once ($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/filter_tools.php");

CModule::IncludeModule("controller");
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/controller/prolog.php");

$MOD_RIGHT = $APPLICATION->GetGroupRight("controller");
if($MOD_RIGHT<"V") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

IncludeModuleLangFile(__FILE__);
$sTableID = "tbl_controller_run";
$lAdmin = new CAdminList($sTableID);

if($query<>"" && $USER->IsAdmin() && check_bitrix_sessid())
{
	$cnt = 0;
	$lAdmin->BeginPrologContent();
	$arFilter = array(
		"DISCONNECTED" => "N",
		"CONTROLLER_GROUP_ID" => $_REQUEST['controller_group_id'],
	);

	if(isset($_REQUEST['controller_member_id']) && trim($_REQUEST['controller_member_id']) != "")
	{
		if(!is_array($_REQUEST['controller_member_id']))
			$IDs = array_map("trim", explode(" ", $_REQUEST['controller_member_id']));
		else
			$IDs = array_map("trim", $_REQUEST['controller_member_id']);

		$arFilterID = array();
		$arFilterNAME = array();

		foreach($IDs as $id)
		{
			if(is_numeric($id))
				$arFilterID[] = $id;
			else
				$arFilterNAME[] = strtoupper($id);
		}

		if(!empty($arFilterID) || !empty($arFilterNAME))
		{
			$arFilter[0] = array("LOGIC" => "OR");
			if(!empty($arFilterID))
				$arFilter[0]["=ID"] = $arFilterID;
			if(!empty($arFilterNAME))
				$arFilter[0]["NAME"] = $arFilterNAME;
		}
	}

	$cnt_ok = 0;
	$dbr_members = CControllerMember::GetList(Array("ID"=>"ASC"), $arFilter);
	while($ar_member = $dbr_members->GetNext())
	{
		$cnt++;
		if($add_task == "Y")
		{
			if(CControllerTask::Add(Array(
					"TASK_ID" => "REMOTE_COMMAND",
					"CONTROLLER_MEMBER_ID" => $ar_member["ID"],
					"INIT_EXECUTE" => $query
				)))
				$cnt_ok++;
		}
		else
		{
			echo BeginNote();
			echo "<b><u>".$ar_member["NAME"].":</u></b><br>";
			$result = CControllerMember::RunCommandWithLog($ar_member["ID"], $query);
			if($result===false)
			{
				$e = $APPLICATION->GetException();
				echo "Error: ".$e->GetString();
			}
			else
				echo nl2br($result);
			echo EndNote();
		}
	}

	if($cnt<=0)
	{
		echo BeginNote();
		echo GetMessage("CTRLR_RUN_ERR_NSELECTED");
		echo EndNote();
	}

	if($add_task=="Y")
	{
		echo BeginNote();
		echo str_replace(Array("#SUCCESS_CNT#", "#CNT#", "#LANG#"), Array($cnt_ok, $cnt, LANGUAGE_ID), GetMessage("CTRLR_RUN_SUCCESS"));
		echo EndNote();
	}

	$lAdmin->EndPrologContent();
}

$lAdmin->BeginEpilogContent();
?>
	<input type="hidden" name="query" id="query" value="<?=htmlspecialcharsbx($query)?>">
	<input type="hidden" name="controller_member_id" id="controller_member_id" value="<?=htmlspecialcharsbx($controller_member_id)?>">
	<input type="hidden" name="add_task" id="add_task" value="<?=htmlspecialcharsbx($add_task)?>">
	<input type="hidden" name="controller_group_id" id="controller_group_id" value="<?=htmlspecialcharsbx($controller_group_id)?>">
<?
$lAdmin->EndEpilogContent();

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("CTRLR_RUN_TITLE"));

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");
?>
<script>
function __FPHPSubmit()
{
	if(confirm('<?echo GetMessage("CTRLR_RUN_CONFIRM")?>'))
	{
		document.getElementById('query').value = document.getElementById('php').value;
		document.getElementById('controller_member_id').value = document.getElementById('fcontroller_member_id').value;
		document.getElementById('controller_group_id').value = document.getElementById('fcontroller_group_id').value;
		document.getElementById('add_task').value = (document.getElementById('fadd_task').checked?'Y':'N');

		window.scrollTo(0, 500);
		<?=$lAdmin->ActionPost($APPLICATION->GetCurPageParam("mode=frame", Array("mode", "PAGEN_1")))?>
	}
}
</script>
<?
$aTabs = array(
	array("DIV"=>"tab1", "TAB"=>GetMessage("CTRLR_RUN_COMMAND_FIELD"), "TITLE"=>GetMessage("CTRLR_RUN_COMMAND_TAB_TITLE")),
);
$editTab = new CAdminTabControl("editTab", $aTabs);
?>
<form name="form1" action="<?echo $APPLICATION->GetCurPage()?>" method="POST">
<input type="hidden" name="lang" value="<?=LANG?>">
<?
$arGroups = Array();
$dbr_groups = CControllerGroup::GetList(Array("SORT"=>"ASC"));
while($ar_groups = $dbr_groups->GetNext())
	$arGroups[$ar_groups["ID"]] = $ar_groups["NAME"];


$filter = new CAdminFilter(
	$sTableID."_filter_id",
	Array(GetMessage("CTRLR_RUN_FILTER_GROUP"))
);

$filter->Begin();
?>
<tr>
	<td nowrap><?=GetMessage("CTRLR_RUN_FILTER_SITE")?>:</td>
	<td nowrap>
		<?
		$dbr_members = CControllerMember::GetList(Array("SORT"=>"ASC"), Array("DISCONNECTED"=>"N"));
		$arMembers = array();
		while($ar_member = $dbr_members->Fetch())
		{
			$arMembers[$ar_member["ID"]] = $ar_member["NAME"];
			if(count($arMembers) > 0)
				break;
		}

		if(count($arMembers) <= 0):?>
			<select name="fcontroller_member_id" id="fcontroller_member_id">
				<option value=""><?echo GetMessage("CTRLR_RUN_FILTER_SITE_ALL")?></option>
				<?foreach($arMembers as $ID => $NAME):?>
					<option value="<?echo htmlspecialcharsbx($ID)?>"<?if($controller_member_id==$ID)echo ' selected';?>><?echo htmlspecialcharsex($NAME." [".$ID."]")?></option>
				<?endforeach?>
			</select>
		<?else:?>
			<input type="text" name="fcontroller_member_id" id="fcontroller_member_id" value="<?echo htmlspecialcharsbx($controller_member_id)?>" size="47">
		<?endif?>
	</td>
</tr>
<tr>
	<td nowrap><?echo htmlspecialcharsEx(GetMessage("CTRLR_RUN_FILTER_GROUP"))?>:</td>
	<td nowrap><?echo htmlspecialcharsEx($controller_group_id)?>
	<select name="fcontroller_group_id" id="fcontroller_group_id">
		<option value=""><?echo GetMessage("CTRLR_RUN_FILTER_GROUP_ANY")?></option>
	<?foreach($arGroups as $group_id=>$group_name):?>
		<option value="<?=$group_id?>" <?if($group_id==$controller_group_id)echo "selected"?>><?=$group_name?></option>
	<?endforeach;?>
	</select>
	</td>
</tr>
<?
$filter->Buttons();
?>
<?
$filter->End();
?>


<?=bitrix_sessid_post()?>
<?
$editTab->Begin();
$editTab->BeginNextTab();
?>
<tr>
	<td>
		<input type="hidden" name="lang" value="<?=LANG?>">
		<textarea cols="60" name="php" id="php" rows="15" wrap="OFF" style="width:100%;"><? echo htmlspecialcharsbx($query); ?></textarea>
	</td>
</tr>
<tr>
	<td>
		<label for="fadd_task" title="<?echo GetMessage("CTRLR_RUN_ADD_TASK_LABEL")?>">
		<input type="checkbox" id="fadd_task" name="fadd_task" title="<?echo GetMessage("CTRLR_RUN_ADD_TASK_LABEL")?>" value="Y">
		<?echo GetMessage("CTRLR_RUN_ADD_TASK")?></label>
	</td>
</tr>

<?$editTab->Buttons();?>
<input <?if (!$USER->IsAdmin()) echo "disabled"?> type="button" accesskey="x" name="execute" value="<?echo GetMessage("CTRLR_RUN_BUTT_RUN")?>" onclick="return __FPHPSubmit();" class="adm-btn-save">
<input type="reset" value="<?echo GetMessage("CTRLR_RUN_BUTT_CLEAR")?>">
<?
$editTab->End();
?>
</form>
<?
$lAdmin->DisplayList();

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
?>
