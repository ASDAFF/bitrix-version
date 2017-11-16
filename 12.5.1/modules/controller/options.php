<?
$module_id = "controller";
$M_RIGHT = $APPLICATION->GetGroupRight($module_id);
if (($M_RIGHT>="R") && (CModule::IncludeModule("controller"))):

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/options.php");
IncludeModuleLangFile(__FILE__);

$arGroups = Array();
$dbr_groups = CControllerGroup::GetList(Array("SORT"=>"ASC", "ID"=>"ASC"));
while($ar_groups = $dbr_groups->GetNext())
	$arGroups[$ar_groups["ID"]] = $ar_groups["NAME"]." [".$ar_groups["ID"]."]";

$arOptions = Array(
	Array("default_group", GetMessage("CTRLR_OPTIONS_DEF_GROUP"), 1, Array("selectbox", $arGroups)),
	Array("group_update_time", GetMessage("CTRLR_OPTIONS_TIME_AUTOUPDATE"), 0, Array("text", 5)),
);
if(ControllerIsSharedMode())
	$arOptions[] = Array("shared_kernel_path", GetMessage("CTRLR_OPTIONS_SHARED_KERNEL_PATH"), "", Array("text", 50));

$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("MAIN_TAB_SET"), "ICON" => "main_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_SET")),
	array("DIV" => "edit3", "TAB" => GetMessage("MAIN_TAB_RIGHTS"), "ICON" => "main_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_RIGHTS")),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);

if($REQUEST_METHOD=="POST" && strlen($Update.$Apply.$RestoreDefaults)>0 && $M_RIGHT>="W" && check_bitrix_sessid())
{
	if(strlen($RestoreDefaults)>0)
	{
		COption::RemoveOption("controller");
		$z = CGroup::GetList($v1="id",$v2="asc", array("ACTIVE" => "Y", "ADMIN" => "N"));
		while($zr = $z->Fetch())
			$APPLICATION->DelGroupRight($module_id, array($zr["ID"]));
	}
	else
	{
		$prev_group_update_time = COption::GetOptionInt("controller", "group_update_time");

		__AdmSettingsSaveOptions("controller", $arOptions);

		if($prev_group_update_time!=COption::GetOptionInt("controller", "group_update_time"))
		{
			CAgent::RemoveAgent("CControllerGroup::CheckDefaultUpdate();", "controller");
			if(COption::GetOptionInt("controller", "group_update_time")>0)
				CAgent::AddAgent("CControllerGroup::CheckDefaultUpdate();", "controller", "N", COption::GetOptionInt("controller", "group_update_time")*60);
		}
	}

	$Update = $Update.$Apply;
	ob_start();
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");
	ob_end_clean();

	if(strlen($_REQUEST["back_url_settings"]) > 0)
	{
		if((strlen($Apply) > 0) || (strlen($RestoreDefaults) > 0))
			LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($module_id)."&lang=".urlencode(LANGUAGE_ID)."&back_url_settings=".urlencode($_REQUEST["back_url_settings"])."&".$tabControl->ActiveTabParam());
		else
			LocalRedirect($_REQUEST["back_url_settings"]);
	}
	else
	{
		LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($module_id)."&lang=".urlencode(LANGUAGE_ID)."&".$tabControl->ActiveTabParam());
	}

}

?>
<form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&amp;lang=<?echo LANG?>">
<?=bitrix_sessid_post()?>
<?
$tabControl->Begin();

$tabControl->BeginNextTab();
__AdmSettingsDrawList("controller", $arOptions);

$tabControl->BeginNextTab();
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");
$tabControl->Buttons();?>
	<input <?if ($M_RIGHT<"W") echo "disabled" ?> type="submit" name="Update" value="<?=GetMessage("MAIN_SAVE")?>" title="<?=GetMessage("MAIN_OPT_SAVE_TITLE")?>" class="adm-btn-save">
	<input <?if ($M_RIGHT<"W") echo "disabled" ?> type="submit" name="Apply" value="<?=GetMessage("MAIN_OPT_APPLY")?>" title="<?=GetMessage("MAIN_OPT_APPLY_TITLE")?>">
	<?if(strlen($_REQUEST["back_url_settings"])>0):?>
		<input <?if ($M_RIGHT<"W") echo "disabled" ?> type="button" name="Cancel" value="<?=GetMessage("MAIN_OPT_CANCEL")?>" title="<?=GetMessage("MAIN_OPT_CANCEL_TITLE")?>" onclick="window.location='<?echo htmlspecialcharsbx(CUtil::addslashes($_REQUEST["back_url_settings"]))?>'">
		<input type="hidden" name="back_url_settings" value="<?=htmlspecialcharsbx($_REQUEST["back_url_settings"])?>">
	<?endif?>
	<input <?if ($M_RIGHT<"W") echo "disabled" ?> type="submit" name="RestoreDefaults" title="<?echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" OnClick="return confirm('<?echo AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>')" value="<?echo GetMessage("MAIN_RESTORE_DEFAULTS")?>">
	<?=bitrix_sessid_post();?>
<?$tabControl->End();?>
</form>
<?endif //if (($M_RIGHT>="R") && (CModule::IncludeModule("controller"))):
?>
