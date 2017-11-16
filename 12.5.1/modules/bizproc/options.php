<?
$module_id = "bizproc";
$bizprocPerms = $APPLICATION->GetGroupRight($module_id);
if ($bizprocPerms>="R") :

global $MESS;
include(GetLangFileName($GLOBALS["DOCUMENT_ROOT"]."/bitrix/modules/main/lang/", "/options.php"));
include(GetLangFileName($GLOBALS["DOCUMENT_ROOT"]."/bitrix/modules/bizproc/lang/", "/options.php"));

include_once($GLOBALS["DOCUMENT_ROOT"]."/bitrix/modules/bizproc/include.php");

$dbSites = CSite::GetList(($b = ""), ($o = ""), Array("ACTIVE" => "Y"));
$arSites = array();
$aSubTabs = array();
while ($site = $dbSites->Fetch())
{
	$site["ID"] = htmlspecialcharsbx($site["ID"]);
	$site["NAME"] = htmlspecialcharsbx($site["NAME"]);
	$arSites[] = $site;

	$aSubTabs[] = array("DIV" => "opt_site_".$site["ID"], "TAB" => "(".$site["ID"].") ".$site["NAME"], 'TITLE' => '');
}
$subTabControl = new CAdminViewTabControl("subTabControl", $aSubTabs);

if ($REQUEST_METHOD == "GET" && strlen($RestoreDefaults) > 0 && $bizprocPerms == "W" && check_bitrix_sessid())
{
	COption::RemoveOption("bizproc");
}

$arAllOptions = array(
	array("log_cleanup_days", GetMessage("BIZPROC_LOG_CLEANUP_DAYS"), "90", Array("text", 3)),
//	array("name_template", GetMessage("BIZPROC_NAME_TEMPLATE"), "", Array("select", 35))
);

$strWarning = "";
if ($REQUEST_METHOD == "POST" && strlen($Update) > 0 && $bizprocPerms == "W" && check_bitrix_sessid())
{
	COption::SetOptionString("bizproc", "log_cleanup_days", $log_cleanup_days);
	if ($log_cleanup_days > 0)
		CAgent::AddAgent("CBPTrackingService::ClearOldAgent();", "bizproc", "N", 43200);
	else
		CAgent::RemoveAgent("CBPTrackingService::ClearOldAgent();", "bizproc");

	foreach($arSites as $site)
	{
		if (isset($_POST["name_template_".$site["LID"]]))
		{
			if (empty($_POST["name_template_".$site["LID"]]))
				COption::RemoveOption("bizproc", "name_template", $site["LID"]);
			else
				COption::SetOptionString("bizproc", "name_template", $_POST["name_template_".$site["LID"]], false, $site["LID"]);
		}
	}
}

if (strlen($strWarning) > 0)
	CAdminMessage::ShowMessage($strWarning);

$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("BIZPROC_TAB_SET"), "ICON" => "", "TITLE" => GetMessage("BIZPROC_TAB_SET_ALT")),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);
$tabControl->Begin();
?>
<form method="POST" name="bizproc_opt_form" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($mid) ?>&lang=<?= LANGUAGE_ID ?>" ENCTYPE="multipart/form-data"><?
echo bitrix_sessid_post();
$tabControl->BeginNextTab();
?>
	<?for ($i = 0; $i < count($arAllOptions); $i++):
		$Option = $arAllOptions[$i];
		$val = COption::GetOptionString("bizproc", $Option[0], $Option[2]);
		$type = $Option[3];
		?>
		<tr>
			<td width="50%"><?
				if ($type[0]=="checkbox")
					echo "<label for=\"".htmlspecialcharsbx($Option[0])."\">".$Option[1]."</label>:";
				else
					echo $Option[1];
			?>:</td>
			<td width="50%">
				<?if($type[0]=="checkbox"):?>
					<input type="checkbox" name="<?echo htmlspecialcharsbx($Option[0])?>" id="<?echo htmlspecialcharsbx($Option[0])?>" value="Y"<?if($val=="Y")echo" checked";?>>
				<?elseif($type[0]=="text"):?>
					<input type="text" size="<?echo $type[1]?>" value="<?echo htmlspecialcharsbx($val)?>" name="<?echo htmlspecialcharsbx($Option[0])?>">
				<?elseif($type[0]=="textarea"):?>
					<textarea rows="<?echo $type[1]?>" cols="<?echo $type[2]?>" name="<?echo htmlspecialcharsbx($Option[0])?>"><?echo htmlspecialcharsbx($val)?></textarea>
				<?endif?>
			</td>
		</tr>
	<?endfor;?>
		<tr>
			<td valign="top" colspan="2" align="center">
			<?
				$subTabControl->Begin();
				foreach ($arSites as $site)
				{
					$subTabControl->BeginNextTab();
					$curVal = COption::GetOptionString("bizproc", "name_template", "", $site["LID"]);
						?>
						<label><?=GetMessage("BIZPROC_NAME_TEMPLATE")?></label>:
							<select name="<?php echo $Option[0]?>_<?php echo $site["LID"]?>">
								<?
								$arNameTemplates = CSite::GetNameTemplates();
								$arNameTemplates = array_reverse($arNameTemplates, true); //prepend array with default '' => Site Format value
								$arNameTemplates[""] = GetMessage("BIZPROC_OPTIONS_NAME_IN_SITE_FORMAT");
								$arNameTemplates = array_reverse($arNameTemplates, true); 
								foreach ($arNameTemplates as $template => $phrase)
								{
									$template = str_replace(array("#NOBR#","#/NOBR#"), array("",""), $template);
									?><option value="<?= $template?>" <?=(($template == $curVal) ? " selected" : "")?> ><?= $phrase?></option><?
								}
								?>
							</select>
						<?
				}
				$subTabControl->End();
			?>
		</td>
	</tr>
<?$tabControl->Buttons();?>
<script language="JavaScript">
function RestoreDefaults()
{
	if (confirm('<?echo AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>'))
		window.location = "<?= $APPLICATION->GetCurPage() ?>?RestoreDefaults=Y&lang=<?= LANG ?>&mid=<?= urlencode($mid) ?>&<?= bitrix_sessid_get() ?>";
}
</script>

<input type="submit" class="adm-btn-save" <?if ($bizprocPerms < "W") echo "disabled" ?> name="Update" value="<?echo GetMessage("MAIN_SAVE")?>">
<input type="hidden" name="Update" value="Y">
<input type="reset" name="reset" value="<?echo GetMessage("MAIN_RESET")?>">
<input type="button" <?if ($bizprocPerms<"W") echo "disabled" ?> title="<?echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" OnClick="RestoreDefaults();" value="<?echo GetMessage("MAIN_RESTORE_DEFAULTS")?>">
<?$tabControl->End();?>
</form>
<?endif;?>