<?
$module_id = "security";
$RIGHT_R = $USER->CanDoOperation('security_module_settings_read');
$RIGHT_W = $USER->CanDoOperation('security_module_settings_write');
if($RIGHT_R || $RIGHT_W) :

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/options.php");
IncludeModuleLangFile(__FILE__);

$arSyslogFacilities = array();
$arSyslogFacilities['reference_id'] = array_keys(CSecurityEvent::getSyslogFacilities());
$arSyslogFacilities['reference'] = array_values(CSecurityEvent::getSyslogFacilities());

$arSyslogPriorities = array();
$arSyslogPriorities['reference_id'] = array_keys(CSecurityEvent::getSyslogPriorities());
$arSyslogPriorities['reference'] = array_values(CSecurityEvent::getSyslogPriorities());

$arAllOptions = Array(
	array("", GetMessage("SEC_OPTIONS_IPCHECK")." ", array("heading")),
	array("ipcheck_allow_self_block", GetMessage("SEC_OPTIONS_IPCHECK_ALLOW_SELF_BLOCK")." ", array("checkbox")),
	array("ipcheck_disable_file", GetMessage("SEC_OPTIONS_IPCHECK_DISABLE_FILE")." ", array("text", 45)),
	array("", GetMessage("SEC_OPTIONS_EVENTS")." ", array("heading")),
	array("security_event_collect_user_info", GetMessage("SEC_OPTIONS_EVENT_COLLECT_USER_INFO")." ", array("checkbox")),
	array("security_event_db_active", GetMessage("SEC_OPTIONS_EVENT_DB_ACTIVE")." ", array("checkbox")),
	array("security_event_syslog_active", GetMessage("SEC_OPTIONS_EVENT_SYSLOG_ACTIVE")." ", array("checkbox")),
	array("security_event_syslog_facility", GetMessage("SEC_OPTIONS_EVENT_SYSLOG_FACILITY")." ", array("selectbox", $arSyslogFacilities)),
	array("security_event_syslog_priority", GetMessage("SEC_OPTIONS_EVENT_SYSLOG_PRIORITY")." ", array("selectbox", $arSyslogPriorities)),
	array("security_event_file_active", GetMessage("SEC_OPTIONS_EVENT_FILE_ACTIVE")." ", array("checkbox")),
	array("security_event_file_path", GetMessage("SEC_OPTIONS_EVENT_FILE_PATH")." ", array("text", 45)),
);

$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("MAIN_TAB_SET"), "ICON" => "security_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_SET")),
	array("DIV" => "edit2", "TAB" => GetMessage("MAIN_TAB_RIGHTS"), "ICON" => "security_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_RIGHTS")),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);

CModule::IncludeModule($module_id);

if($REQUEST_METHOD=="POST" && strlen($Update.$Apply.$RestoreDefaults) > 0 && $RIGHT_W && check_bitrix_sessid())
{

	if($RestoreDefaults != "")
	{
		COption::RemoveOption($module_id);
	}
	else
	{
		foreach($arAllOptions as $arOption)
		{
			$name = $arOption[0];
			$val = trim($_REQUEST[$name], " \t\n\r");
			if($arOption[2][0]=="checkbox" && $val!="Y")
				$val="N";
			COption::SetOptionString($module_id, $name, $val, $arOption[1]);
		}
	}

	ob_start();
	$Update = $Update.$Apply;
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights2.php");
	ob_end_clean();

	if($_REQUEST["back_url_settings"] != "")
	{
		if(($Apply != "") || ($RestoreDefaults != ""))
			LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($module_id)."&lang=".urlencode(LANGUAGE_ID)."&back_url_settings=".urlencode($_REQUEST["back_url_settings"])."&".$tabControl->ActiveTabParam());
		else
			LocalRedirect($_REQUEST["back_url_settings"]);
	}
	else
	{
		LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($module_id)."&lang=".urlencode(LANGUAGE_ID)."&".$tabControl->ActiveTabParam());
	}
}

$message = CSecurityIPRule::CheckAntiFile(true);
if($message)
	echo $message->Show();

?>
<form method="post" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=urlencode($module_id)?>&amp;lang=<?=LANGUAGE_ID?>">
<?
$tabControl->Begin();
$tabControl->BeginNextTab();

	foreach($arAllOptions as $arOption):
	$type = $arOption[2];?>
	<?if($type[0] == "heading"):?>
	<tr class="heading">
		<td colspan="2"><b><?echo $arOption[1]?></b></td>
	</tr>
	<?else:?>
	<?$val = COption::GetOptionString($module_id, $arOption[0]);?>
	<tr>
		<td width="40%">
			<label for="<?echo htmlspecialcharsbx($arOption[0])?>"><?echo $arOption[1]?>:</label>
		</td>
		<td width="60%">
			<?if($type[0] == "checkbox"):?>
				<input type="checkbox" name="<?echo htmlspecialcharsbx($arOption[0])?>" id="<?echo htmlspecialcharsbx($arOption[0])?>" value="Y"<?if($val=="Y")echo" checked";?>>
			<?elseif($type[0] == "text"):?>
				<input type="text" size="<?echo $type[1]?>" maxlength="255" value="<?echo htmlspecialcharsbx($val)?>" name="<?echo htmlspecialcharsbx($arOption[0])?>" id="<?echo htmlspecialcharsbx($arOption[0])?>">
			<?elseif($type[0] == "textarea"):?>
				<textarea rows="<?echo $type[1]?>" cols="<?echo $type[2]?>" name="<?echo htmlspecialcharsbx($arOption[0])?>" id="<?echo htmlspecialcharsbx($arOption[0])?>"><?echo htmlspecialcharsbx($val)?></textarea>
			<?elseif($type[0] == "selectbox"):
				echo SelectBoxFromArray($arOption[0], $type[1], $val);
			endif?>
		</td>
	</tr>
	<?endif;?>
	<?endforeach?>
<?$tabControl->BeginNextTab();?>
<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights2.php");?>
<?$tabControl->Buttons();?>
	<input <?if(!$RIGHT_W) echo "disabled" ?> type="submit" name="Update" value="<?=GetMessage("MAIN_SAVE")?>" title="<?=GetMessage("MAIN_OPT_SAVE_TITLE")?>">
	<input <?if(!$RIGHT_W) echo "disabled" ?> type="submit" name="Apply" value="<?=GetMessage("MAIN_OPT_APPLY")?>" title="<?=GetMessage("MAIN_OPT_APPLY_TITLE")?>">
	<?if($_REQUEST["back_url_settings"] != "" ):?>
		<input <?if(!$RIGHT_W) echo "disabled" ?> type="button" name="Cancel" value="<?=GetMessage("MAIN_OPT_CANCEL")?>" title="<?=GetMessage("MAIN_OPT_CANCEL_TITLE")?>" onclick="window.location='<?echo htmlspecialcharsbx(CUtil::addslashes($_REQUEST["back_url_settings"]))?>'">
		<input type="hidden" name="back_url_settings" value="<?=htmlspecialcharsbx($_REQUEST["back_url_settings"])?>">
	<?endif?>
	<input <?if(!$RIGHT_W) echo "disabled" ?> type="submit" name="RestoreDefaults" title="<?echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" OnClick="confirm('<?echo AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>')" value="<?echo GetMessage("MAIN_RESTORE_DEFAULTS")?>">
	<?=bitrix_sessid_post();?>
<?$tabControl->End();?>
</form>
<?endif;?>
<script>
	function jsEventChanged(eventType) {
<!--		if(eventType == --><?//=CSecurityEvent::ENGINE_SYSLOG?><!--) {-->
<!--			BX('security_event_engine_syslog_facility_row').style.display = "table-row";-->
<!--			BX('security_event_engine_syslog_priority_row').style.display = "table-row";-->
<!--			BX('security_event_engine_file_path_row').style.display = "none";-->
<!--		} else if(eventType == --><?//=CSecurityEvent::ENGINE_FILE?><!--) {-->
<!--			BX('security_event_engine_syslog_facility_row').style.display = "none";-->
<!--			BX('security_event_engine_syslog_priority_row').style.display = "none";-->
<!--			BX('security_event_engine_file_path_row').style.display = "table-row";-->
<!--		} else {-->
<!--			BX('security_event_engine_syslog_facility_row').style.display = "none";-->
<!--			BX('security_event_engine_syslog_priority_row').style.display = "none";-->
<!--			BX('security_event_engine_file_path_row').style.display = "none";-->
<!--		}-->
	}
</script>