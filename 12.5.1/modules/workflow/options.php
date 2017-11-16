<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/workflow/include.php");
$module_id = "workflow";
$WORKFLOW_RIGHT = $APPLICATION->GetGroupRight($module_id);
if ($WORKFLOW_RIGHT>="R") :

IncludeModuleLangFile(__FILE__);

$arAllOptions = array(
	array("USE_HTML_EDIT", GetMessage("FLOW_USE_HTML_EDIT"), "Y", Array("checkbox", "Y")),
	array("HISTORY_SIMPLE_EDITING", GetMessage("FLOW_HISTORY_SIMPLE_EDITING"), "N", Array("checkbox", "Y")),
	array("MAX_LOCK_TIME", GetMessage("FLOW_MAX_LOCK"), "60", array("text", 5)),
	array("DAYS_AFTER_PUBLISHING", GetMessage("FLOW_DAYS_AFTER_PUBLISHING"), "0", array("text", 5), "CWorkflow::CleanUpPublished();"),
	array("HISTORY_COPIES", GetMessage("FLOW_HISTORY_COPIES"), "10", array("text", 5), "CWorkflow::CleanUpHistoryCopies();"),
	array("HISTORY_DAYS", GetMessage("FLOW_HISTORY_DAYS"), "-1", array("text", 5), "CWorkflow::CleanUpHistory();")
	);

$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("MAIN_TAB_SET"), "ICON" => "workflow_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_SET")),
	array("DIV" => "edit2", "TAB" => GetMessage("MAIN_TAB_RIGHTS"), "ICON" => "workflow_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_RIGHTS")),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);

if($REQUEST_METHOD=="POST" && $WORKFLOW_RIGHT=="W" && check_bitrix_sessid())
{
	if(strlen($_POST["RestoreDefaults"]) > 0)
	{
		COption::RemoveOption($module_id);
		$z = CGroup::GetList($v1="id",$v2="asc", array("ACTIVE" => "Y", "ADMIN" => "N"));
		while($zr = $z->Fetch())
			$APPLICATION->DelGroupRight($module_id, array($zr["ID"]));
	}
	elseif(strlen($Update) > 0)
	{
		for($i=0; $i<count($arAllOptions); $i++)
		{
			$name=$arAllOptions[$i][0];
			$val=$_POST[$name];
			if($arAllOptions[$i][3][0]=="checkbox" && $val!="Y")
				$val="N";
			COption::SetOptionString($module_id, $name, $val);
			if($_POST[$name."_clear"] == "Y")
			{
				$func=$arAllOptions[$i][4];
				eval($func);
			}
		}
		COption::SetOptionString($module_id, "WORKFLOW_ADMIN_GROUP_ID", intval($WORKFLOW_ADMIN_GROUP_ID));
	}

	$Update = $Update.$Apply;
	ob_start();
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");
	ob_end_clean();

	LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($module_id)."&lang=".urlencode(LANGUAGE_ID)."&".$tabControl->ActiveTabParam());
}
$WORKFLOW_ADMIN_GROUP_ID = COption::GetOptionString($module_id, "WORKFLOW_ADMIN_GROUP_ID");

?>
<?
$tabControl->Begin();
?><form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&amp;lang=<?=LANGUAGE_ID?>"><?
$tabControl->BeginNextTab();
?>
	<?
	for($i=0; $i<count($arAllOptions); $i++):
		$Option = $arAllOptions[$i];
		$val = COption::GetOptionString($module_id, $Option[0], $Option[2]);
		$type = $Option[3];
	?>
	<tr>
		<td width="40%" nowrap <?if($type[0]=="textarea") echo 'class="adm-detail-valign-top"'?>>
			<label for="<?echo htmlspecialcharsbx($Option[0])?>"><?echo $Option[1]?></label>
		<td width="60%">
		<?if($type[0]=="checkbox"):
			?><input type="checkbox" name="<?echo htmlspecialcharsbx($Option[0])?>" id="<?echo htmlspecialcharsbx($Option[0])?>" value="Y"<?if($val=="Y")echo" checked";?>><?
		elseif($type[0]=="text"):
			?><input type="text" size="<?echo $type[1]?>" maxlength="255" value="<?echo htmlspecialcharsbx($val)?>" name="<?echo htmlspecialcharsbx($Option[0])?>"><?
			if (strlen($Option[4])>0) :
				?>&nbsp;<label for="<?echo htmlspecialcharsbx($Option[0])?>_clear"><?=GetMessage("FLOW_CLEAR")?>:</label><input type="checkbox" name="<?echo htmlspecialcharsbx($Option[0])?>_clear" id="<?echo htmlspecialcharsbx($Option[0])?>_clear" value="Y"><?
			endif;
		elseif($type[0]=="textarea"):
			?><textarea rows="<?echo $type[1]?>" cols="<?echo $type[2]?>" name="<?echo htmlspecialcharsbx($Option[0])?>"><?echo htmlspecialcharsbx($val)?></textarea><?
		endif;
		?></td>
	</tr>
	<?endfor;?>
	<tr>
		<td><?echo GetMessage("FLOW_ADMIN")?></td>
		<td><?echo SelectBox("WORKFLOW_ADMIN_GROUP_ID", CGroup::GetDropDownList(""), GetMessage("MAIN_NO"), htmlspecialcharsbx($WORKFLOW_ADMIN_GROUP_ID));?></td>
	</tr>

<?$tabControl->BeginNextTab();?>
<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");?>
<?$tabControl->Buttons();?>
<input <?if ($WORKFLOW_RIGHT<"W") echo "disabled" ?> type="submit" name="Update" value="<?=GetMessage("FLOW_SAVE")?>" class="adm-btn-save">
<input type="hidden" name="Update" value="Y">
<input type="reset" name="reset" value="<?=GetMessage("FLOW_RESET")?>">
<input <?if ($WORKFLOW_RIGHT<"W") echo "disabled" ?> type="submit" title="<?echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" OnClick="return confirm('<?echo AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>')" value="<?echo GetMessage("MAIN_RESTORE_DEFAULTS")?>" name="RestoreDefaults">
<?=bitrix_sessid_post();?>
<?$tabControl->End();?>
</form>
<?endif;?>
