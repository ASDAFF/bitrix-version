<?
define("ADMIN_MODULE_NAME", "controller");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

$MOD_RIGHT = $APPLICATION->GetGroupRight("controller");
if($MOD_RIGHT < "W" || !CModule::IncludeModule("controller"))
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

IncludeModuleLangFile(__FILE__);

$aTabs = array(
	array(
		"DIV" => "auth_cs",
		"TAB" => GetMessage("CTRLR_AUTH_CS_TAB"),
		"ICON"=>"main_user_edit",
		"TITLE"=>GetMessage("CTRLR_AUTH_CS_TAB_TITLE"),
	),
	array(
		"DIV" => "auth_ss",
		"TAB" => GetMessage("CTRLR_AUTH_SS_TAB"),
		"ICON"=>"main_user_edit",
		"TITLE"=>GetMessage("CTRLR_AUTH_SS_TAB_TITLE"),
	),
	array(
		"DIV" => "auth_sc",
		"TAB" => GetMessage("CTRLR_AUTH_SC_TAB"),
		"ICON"=>"main_user_edit",
		"TITLE"=>GetMessage("CTRLR_AUTH_SC_TAB_TITLE"),
	),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs, true, true);

if(
	$_SERVER["REQUEST_METHOD"] === "POST"
	&& (
		$save!=""
		|| $apply!=""
	)
	&& $MOD_RIGHT >= "W"
	&& check_bitrix_sessid()
)
{
	COption::SetOptionString("controller", "auth_loc_enabled", $_POST["auth_cs"]==="Y"? "Y": "N");
	if(array_key_exists("CONTR_GROUPS_LOC", $_POST) && is_array($_POST["CONTR_GROUPS_LOC"]))
	{
		$arGroupResult = array();
		foreach($_POST["CONTR_GROUPS_LOC"] as $i => $loc_group_id)
		{
			if(intval($loc_group_id) > 0 && strlen($_POST["CONTR_GROUPS_REM"][$i]) > 0)
				$arGroupResult[] = array('LOC'=>$loc_group_id, 'REM'=>$_POST["CONTR_GROUPS_REM"][$i]);
		}
		COption::SetOptionString("controller", "auth_loc", serialize($arGroupResult));
	}

	COption::SetOptionString("controller", "auth_trans_enabled", $_POST["auth_ss"]==="Y"? "Y": "N");
	if(array_key_exists("TRANS_GROUPS_FROM", $_POST) && is_array($_POST["TRANS_GROUPS_FROM"]))
	{
		$arGroupResult = array();
		foreach($_POST["TRANS_GROUPS_FROM"] as $i => $from_group_id)
		{
			if(strlen($from_group_id) > 0 && strlen($_POST["TRANS_GROUPS_TO"][$i]) > 0)
				$arGroupResult[] = array('FROM'=>$from_group_id, 'TO'=>$_POST["TRANS_GROUPS_TO"][$i]);
		}
		COption::SetOptionString("controller", "auth_trans", serialize($arGroupResult));
	}

	COption::SetOptionString("controller", "auth_controller_enabled", $_POST["auth_sc"]==="Y"? "Y": "N");
	if(array_key_exists("CONTR_GROUPS_FROM", $_POST) && is_array($_POST["CONTR_GROUPS_FROM"]))
	{
		$arGroupResult = array();
		foreach($_POST["CONTR_GROUPS_FROM"] as $i => $from_group_id)
		{
			if(strlen($from_group_id) > 0 && intval($_POST["CONTR_GROUPS_TO"][$i]) > 0)
				$arGroupResult[] = array('FROM'=>$from_group_id, 'TO'=>$_POST["CONTR_GROUPS_TO"][$i]);
		}
		COption::SetOptionString("controller", "auth_controller", serialize($arGroupResult));
	}

	if(COption::GetOptionString("controller", "auth_controller_enabled", "N") === "Y")
		RegisterModuleDependences("main", "OnUserLoginExternal", "main", "CControllerClient", "OnExternalLogin", 1);
	else
		UnRegisterModuleDependences("main", "OnUserLoginExternal", "main", "CControllerClient", "OnExternalLogin", 1);

	if($save!="" && $_GET["return_url"]!="")
		LocalRedirect($_GET["return_url"]);
	LocalRedirect("/bitrix/admin/controller_auth.php?lang=".LANGUAGE_ID.($return_url? "&return_url=".urlencode($_GET["return_url"]): "")."&".$tabControl->ActiveTabParam());
}

$APPLICATION->SetTitle(GetMessage("CTRLR_AUTH_TITLE"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

?>
<script>
function DeleteRow(ob)
{
	var row = BX.findParent(ob, {'tag':'tr'});
	var tbl = BX.findParent(row, {'tag':'table'});
	var tbl_rows = BX.findChildren(tbl, {'tag' : 'tr', 'class' : 'groups'}, true);
	if(tbl_rows.length > 1)
		row.parentNode.removeChild(row);
}
function AddRow(ob)
{
	var row = BX.findParent(ob, {'tag':'tr'});
	var tbl = BX.findParent(row, {'tag':'table'});
	var tbl_rows = BX.findChildren(tbl, {'tag' : 'tr', 'class' : 'groups'}, true);
	if(tbl_rows.length > 0)
	{
		var tbl_row = tbl_rows[tbl_rows.length-1];
		var new_row = BX.clone(tbl_row, true);
		row.parentNode.insertBefore(new_row, row);
	}
}
</script>
<form method="POST" action="controller_auth.php?lang=<?echo LANGUAGE_ID?><?echo $_GET["return_url"]? "&amp;return_url=".urlencode($_GET["return_url"]): ""?>"  enctype="multipart/form-data" name="editform">
<?
$tabControl->Begin();
$tabControl->BeginNextTab();
?>
	<tr>
		<td nowrap><label for="cauth_cs"><?echo GetMessage("CTRLR_AUTH_CS_LABEL")?></label> <span class="required"><sup>1</sup></span>:</td>
		<td><input type="hidden" name="auth_cs" value="N"><input type="checkbox" value="Y" name="auth_cs" id="cauth_cs" <?if(COption::GetOptionString("controller", "auth_loc_enabled") == "Y") echo 'checked="checked"';?>></td>
	</tr>
	<tr>
		<td  class="adm-detail-valign-top"><?echo GetMessage("CTRLR_AUTH_LOC_GROUPS")?>:</td>
		<td>
		<table class="internal" cellspacing="0" cellpadding="0">
			<tr class="heading">
				<td width="49%"><?echo GetMessage("CTRLR_AUTH_LOC_GROUPS_LOC")?></td>
				<td width="1%" nowrap>-></td>
				<td width="49%"><?echo GetMessage("CTRLR_AUTH_LOC_GROUPS_REM")?></td>
				<td width="1%" nowrap>&nbsp;</td>
			</tr>
			<?
			$arLocGroups = unserialize(COption::GetOptionString("controller", "auth_loc", serialize(array())));
			$arLocGroups['_1_'] = Array();
			?>
			<?
			foreach($arLocGroups as $arGroup):
				$loc_group_id = $arGroup['LOC'];
				$rem_group_id = $arGroup['REM'];
			?>
			<tr class="groups">
				<td>
						<?$dbgr = CGroup::GetList($o="sort", $b="asc");?>
						<select name="CONTR_GROUPS_LOC[]" style="width:100%;">
							<option value=""></option>
						<?while($argr = $dbgr->GetNext()):?>
							<option value="<?=$argr['ID']?>"<?if($loc_group_id==$argr['ID'])echo ' selected'?>><?=$argr['NAME']?> [<?=$argr['ID']?>]</option>
						<?endwhile?>
						</select>
				</td>
				<td nowrap align="center">-></td>
				<td>
					<input type="text" style="width:100%" name="CONTR_GROUPS_REM[]" size="30" value="<?=htmlspecialcharsbx($rem_group_id)?>">
				</td>
				<td align="center">
					<a href="javascript:void(0);" onclick="DeleteRow(this)" class="group-delete"></a>
				</td>
			</tr>
			<?
			endforeach;
			?>
			<tr>
				<td width="60%" align="left" colspan="4">
					<a href="javascript:void(0);" onclick="AddRow(this)" class="bx-action-href"><?echo GetMessage("CTRLR_AUTH_GROUPS_ADD")?></a>
				</td>
			</tr>
		</table>
		</td>
	</tr>
<?
$tabControl->BeginNextTab();
?>
	<tr>
		<td nowrap><label for="cauth_ss"><?echo GetMessage("CTRLR_AUTH_SS_LABEL")?></label>:</td>
		<td><input type="hidden" name="auth_ss" value="N"><input type="checkbox" value="Y" name="auth_ss" id="cauth_ss" <?if(COption::GetOptionString("controller", "auth_trans_enabled") == "Y") echo 'checked="checked"';?>></td>
	</tr>
	<tr>
		<td class="adm-detail-valign-top"><?echo GetMessage("CTRLR_AUTH_LOC_GROUPS")?>:</td>
		<td>
		<table class="internal" cellspacing="0" cellpadding="0">
			<tr class="heading">
				<td width="49%"><?echo GetMessage("CTRLR_AUTH_LOC_GROUPS_HOME")?></td>
				<td width="1%" nowrap align="center">-></td>
				<td width="49%"><?echo GetMessage("CTRLR_AUTH_LOC_GROUPS_REM")?></td>
				<td width="1%" nowrap>&nbsp;</td>
			</tr>
			<?
			$arLocGroups = unserialize(COption::GetOptionString("controller", "auth_trans", serialize(array())));
			$arLocGroups['_1_'] = Array();
			?>
			<?
			foreach($arLocGroups as $arGroup):
				$from_group_id = $arGroup['FROM'];
				$to_group_id = $arGroup['TO'];
			?>
			<tr class="groups">
				<td>
					<input type="text" style="width:100%" name="TRANS_GROUPS_FROM[]" size="30" value="<?=htmlspecialcharsbx($from_group_id)?>">
				</td>
				<td nowrap align="center">-></td>
				<td>
					<input type="text" style="width:100%" name="TRANS_GROUPS_TO[]" size="30" value="<?=htmlspecialcharsbx($to_group_id)?>">
				</td>
				<td align="center">
					<a href="javascript:void(0);" onclick="DeleteRow(this)" class="group-delete"></a>
				</td>
			</tr>
			<?
			endforeach;
			?>
			<tr>
				<td width="60%" align="left" colspan="4">
					<a href="javascript:void(0);" onclick="AddRow(this)" class="bx-action-href"><?echo GetMessage("CTRLR_AUTH_GROUPS_ADD")?></a>
				</td>
			</tr>
		</table>
		</td>
	</tr>
<?
$tabControl->BeginNextTab();
?>
	<tr>
		<td nowrap><label for="cauth_sc"><?echo GetMessage("CTRLR_AUTH_SC_LABEL")?></label>:</td>
		<td><input type="hidden" name="auth_sc" value="N"><input type="checkbox" value="Y" name="auth_sc" id="cauth_sc" <?if(COption::GetOptionString("controller", "auth_controller_enabled") == "Y") echo 'checked="checked"';?>></td>
	</tr>
	<tr>
		<td class="adm-detail-valign-top"><?echo GetMessage("CTRLR_AUTH_LOC_GROUPS")?>:</td>
		<td>
		<table class="internal" cellspacing="0" cellpadding="0">
			<tr class="heading">
				<td width="49%"><?echo GetMessage("CTRLR_AUTH_LOC_GROUPS_HOME")?></td>
				<td width="1%" nowrap align="center">-></td>
				<td width="49%"><?echo GetMessage("CTRLR_AUTH_LOC_GROUPS_LOC")?></td>
				<td width="1%" nowrap>&nbsp;</td>
			</tr>
			<?
			$arLocGroups = unserialize(COption::GetOptionString("controller", "auth_controller", serialize(array())));
			$arLocGroups['_1_'] = Array();
			?>
			<?
			foreach($arLocGroups as $arGroup):
				$from_group_id = $arGroup['FROM'];
				$to_group_id = $arGroup['TO'];
			?>
			<tr class="groups">
				<td>
					<input type="text" style="width:100%" name="CONTR_GROUPS_FROM[]" size="30" value="<?=htmlspecialcharsbx($from_group_id)?>">
				</td>
				<td nowrap align="center">-></td>
				<td>
						<?$dbgr = CGroup::GetList($o="sort", $b="asc");?>
						<select name="CONTR_GROUPS_TO[]" style="width:100%;">
							<option value=""></option>
						<?while($argr = $dbgr->GetNext()):?>
							<option value="<?=$argr['ID']?>"<?if($to_group_id==$argr['ID'])echo ' selected'?>><?=$argr['NAME']?> [<?=$argr['ID']?>]</option>
						<?endwhile?>
						</select>
				</td>
				</td>
				<td align="center">
					<a href="javascript:void(0);" onclick="DeleteRow(this)" class="group-delete"></a>
				</td>
			</tr>
			<?
			endforeach;
			?>
			<tr>
				<td width="60%" align="left" colspan="4">
					<a href="javascript:void(0);" onclick="AddRow(this)" class="bx-action-href"><?echo GetMessage("CTRLR_AUTH_GROUPS_ADD")?></a>
				</td>
			</tr>
		</table>
		</td>
	</tr>
<?
$tabControl->Buttons(
	array(
		"disabled"=>($MOD_RIGHT < "W"),
		"back_url"=>$_GET["return_url"]? $_GET["return_url"]: "controller_auth.php?lang=".LANGUAGE_ID,
	)
);
?>
<?echo bitrix_sessid_post();?>
<input type="hidden" name="lang" value="<?echo LANGUAGE_ID?>">
<?
$tabControl->End();
?>
</form>
<?echo BeginNote();?>
<span class="required"><sup>1</sup></span><?echo GetMessage("CTRLR_AUTH_NOTE")?>
<?echo EndNote();?>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>