<?php
if(!$USER->IsAdmin())
	return;

global $MESS;
include(GetLangFileName($GLOBALS['DOCUMENT_ROOT'].'/bitrix/modules/pull/lang/', '/options.php'));
IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/options.php');

$module_id = 'pull';
CModule::IncludeModule($module_id);

$MOD_RIGHT = $APPLICATION->GetGroupRight($module_id);

$arDefaultValues['default'] = array(
	'nginx' => 'N',
	'path_to_listener' => (CMain::IsHTTPS() ? "https" : "http")."://#DOMAIN#".(CMain::IsHTTPS() ? ":8894" : ":8893").(BX_UTF ? '/bitrix/sub/' : '/bitrix/subwin/'),
	'path_to_publish' => 'http://127.0.0.1:8895/bitrix/pub/',
	'push' => 'N',
);

$aTabs = array(
	array(
		"DIV" => "edit1", "TAB" => GetMessage("PULL_TAB_SETTINGS"), "ICON" => "pull_path", "TITLE" => GetMessage("PULL_TAB_TITLE_SETTINGS"),
	),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);
if(strlen($_POST['Update'].$_GET['RestoreDefaults'])>0 && check_bitrix_sessid())
{
	if(strlen($_GET['RestoreDefaults'])>0)
	{
		$arDefValues = $arDefaultValues['default'];
		foreach($arDefValues as $key=>$value)
		{
			COption::SetOptionString("pull", $key, $value);
		}
	}
	elseif(strlen($_POST['Update'])>0)
	{
		$send = false;
		if ($_POST['path_to_publish'] != "" && CPullOptions::GetPublishUrl() != $_POST['path_to_publish'])
			CPullOptions::SetPublishUrl($_POST['path_to_publish']);
		if ($_POST['path_to_listener'] != "" && CPullOptions::GetListenUrl() != $_POST['path_to_listener'])
		{
			CPullOptions::SetListenUrl($_POST['path_to_listener']);
			$send = true;
		}
		if ($send)
			CPullOptions::SendConfigDie();

		if (isset($_POST['nginx']))
		{
			if (!CPullOptions::GetNginxStatus())
			{
				$send = true;
				CPullOptions::SendConfigDie();
				CPullOptions::SetNginxStatus('Y');
			}
		}
		else
		{
			if (CPullOptions::GetNginxStatus())
			{
				$send = true;
				CPullOptions::SendConfigDie();
				CPullOptions::SetNginxStatus('N');
			}
		}

		if (isset($_POST['push']))
		{
			if (!CPullOptions::GetPushStatus())
				CPullOptions::SetPushStatus('Y');
		}
		else
		{
			if (CPullOptions::GetPushStatus())
				CPullOptions::SetPushStatus('N');
		}
	}
	if($send):
	?>
		<script type="text/javascript">
			if (BX.PULL)
			{
				BX.PULL.clearChannelId();
			}
		</script>
	<?
	endif;
}
?>
<form method="post" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&lang=<?echo LANG?>">
<?php echo bitrix_sessid_post()?>
<?php
$tabControl->Begin();
$tabControl->BeginNextTab();

CPullOptions::ClearAgent();
$arDependentModule = Array();
$ar = CPullOptions::GetDependentModule();
foreach ($ar as $key => $value)
	$arDependentModule[] = $value['MODULE_ID'];

?>
	<tr>
		<td width="40%"><?=GetMessage("PULL_OPTIONS_STATUS")?>:</td>
		<td width="60%">
			<? if(CPullOptions::ModuleEnable()): ?>
				<span style="color:green; font-weight: bold"><?=GetMessage("PULL_OPTIONS_STATUS_Y")?></span>
			<? else: ?>
				<span style="color:gray; font-weight: bold"><?=GetMessage("PULL_OPTIONS_STATUS_N")?></span>
			<? endif; ?>
		</td>
	</tr>
<? if(CPullOptions::ModuleEnable()): ?>
	<tr>
		<td width="40%"><?=GetMessage("PULL_OPTIONS_USE")?>:</td>
		<td width="60%"><?=implode(", ", $arDependentModule)?></td>
	</tr>
<?endif;?>
	<tr>
		<td width="40%"></td>
		<td width="60%"></td>
	</tr>
	<tr>
		<td width="40%"><nobr><?=GetMessage("PULL_OPTIONS_NGINX")?></nobr>:</td>
		<td width="60%"><input id="config_nginx" type="checkbox" size="40" value="Y" <?=(CPullOptions::GetNginxStatus()?' checked':'')?> name="nginx"></td>
	</tr>
	<tr>
		<td ><?=GetMessage("PULL_OPTIONS_PATH_TO_LISTENER")?>:</td>
		<td><input id="config_path_to_listener" type="text" size="40" value="<?=CPullOptions::GetListenUrl()?>" name="path_to_listener" <?=(CPullOptions::GetNginxStatus()? '':'disabled="true"')?>></td>
	</tr>
	<tr>
		<td><?=GetMessage("PULL_OPTIONS_PATH_TO_PUBLISH")?>:</td>
		<td><input id="config_path_to_publish" type="text" size="40" value="<?=CPullOptions::GetPublishUrl()?>" name="path_to_publish" <?=(CPullOptions::GetNginxStatus()? '':'disabled="true"')?>></td>
	</tr>
<?if (IsModuleInstalled('mobileapp')):?>
	<tr>
		<td align="right" width="50%"><?=GetMessage("PULL_OPTIONS_PUSH")?>:</td>
		<td><input type="checkbox" size="40" value="Y" <?=(CPullOptions::GetPushStatus()?' checked':'')?> name="push"></td>
	</tr>
<?endif;?>
<?$tabControl->Buttons();?>
<script language="JavaScript">
BX.bind(BX('config_nginx'), 'change', function(){
	if (this.checked)
	{
		if (confirm("<?=GetMessage("PULL_OPTIONS_NGINX_CONFIRM")?>"))
		{
			BX('config_path_to_publish').disabled = false;
			BX('config_path_to_listener').disabled = false;
		}
		else
		{
			this.checked = false;
		}
	}
	else
	{
		BX('config_path_to_publish').disabled = true;
		BX('config_path_to_listener').disabled = true;
	}
});
function RestoreDefaults()
{
	if(confirm('<?echo AddSlashes(GetMessage('MAIN_HINT_RESTORE_DEFAULTS_WARNING'))?>'))
		window.location = "<?echo $APPLICATION->GetCurPage()?>?RestoreDefaults=Y&lang=<?echo LANG?>&mid=<?echo urlencode($mid)."&".bitrix_sessid_get();?>";
}
</script>
<input type="submit" name="Update" <?if ($MOD_RIGHT<'W') echo "disabled" ?> value="<?echo GetMessage('MAIN_SAVE')?>" class="adm-btn-save">
<input type="reset" name="reset" value="<?echo GetMessage('MAIN_RESET')?>">
<?=bitrix_sessid_post();?>
<input type="button" <?if ($MOD_RIGHT<'W') echo "disabled" ?> title="<?echo GetMessage('MAIN_HINT_RESTORE_DEFAULTS')?>" OnClick="RestoreDefaults();" value="<?echo GetMessage('MAIN_RESTORE_DEFAULTS')?>">
<?$tabControl->End();?>
</form>
<?=BeginNote();?>
	<?=GetMessage("PULL_OPTIONS_NGINX_DOC")?> <a href="<?=(LANGUAGE_ID == "ru"? "http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=41&LESSON_ID=2033": "http://www.bitrixsoft.com/support/training/course/index.php?COURSE_ID=26&LESSON_ID=5144")?>" target="_blank"><?=GetMessage("PULL_OPTIONS_NGINX_DOC_LINK")?></a>.
<?=EndNote();?>
</div>