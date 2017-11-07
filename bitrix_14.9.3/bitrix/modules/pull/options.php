<?php
if(!$USER->IsAdmin())
	return;

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/pull/options.php');
IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/options.php');

$module_id = 'pull';
CModule::IncludeModule($module_id);
$MOD_RIGHT = $APPLICATION->GetGroupRight($module_id);

include_once($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/pull/default_option.php');
$arDefaultValues['default'] = $pull_default_option;

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
			COption::RemoveOption("pull", $key);
		}
		COption::RemoveOption("pull", 'exclude_sites');
	}
	elseif(strlen($_POST['Update'])>0)
	{
		$send = false;
		if ($_POST['path_to_publish'] != "" && CPullOptions::GetPublishUrl() != $_POST['path_to_publish'])
		{
			CPullOptions::SetPublishUrl($_POST['path_to_publish']);
		}
		if ($_POST['path_to_listener'] != "" && CPullOptions::GetListenUrl() != $_POST['path_to_listener'])
		{
			CPullOptions::SetListenUrl($_POST['path_to_listener']);
			$send = true;
		}
		if ($_POST['nginx_command_per_hit'] != "" && CPullOptions::GetCommandPerHit() != $_POST['nginx_command_per_hit'])
		{
			CPullOptions::SetCommandPerHit($_POST['nginx_command_per_hit']);
		}
		if ($_POST['path_to_listener_secure'] != "" && CPullOptions::GetListenSecureUrl() != $_POST['path_to_listener_secure'])
		{
			CPullOptions::SetListenSecureUrl($_POST['path_to_listener_secure']);
			$send = true;
		}
		if ($_POST['path_to_mobile_listener'] != "" && CPullOptions::GetListenUrl("", true) != $_POST['path_to_mobile_listener'])
		{
			CPullOptions::SetListenUrl($_POST['path_to_mobile_listener'], true);
			$send = true;
		}
		if ($_POST['path_to_mobile_listener_secure'] != "" && CPullOptions::GetListenSecureUrl("", true) != $_POST['path_to_mobile_listener_secure'])
		{
			CPullOptions::SetListenSecureUrl($_POST['path_to_mobile_listener_secure'], true);
			$send = true;
		}
		if ($_POST['path_to_websocket'] != "" && CPullOptions::GetWebSocketUrl() != $_POST['path_to_websocket'])
		{
			CPullOptions::SetWebSocketUrl($_POST['path_to_websocket']);
			if (isset($_POST['websocket']))
				$send = true;
		}
		if ($_POST['nginx_version'] != "" && CPullOptions::GetQueueServerVersion() != $_POST['nginx_version'])
		{
			CPullOptions::SetQueueServerVersion($_POST['nginx_version']);
			if (isset($_POST['websocket']))
				$send = true;
		}
		$websocketEnabled = CPullOptions::GetWebSocket();
		if (isset($_POST['websocket']))
		{
			CPullOptions::SetWebSocket('Y');
			if (!$websocketEnabled)
				$send = true;
		}
		else
		{
			CPullOptions::SetWebSocket('N');
			if ($websocketEnabled)
				$send = true;
		}

		if ($_POST['path_to_websocket_secure'] != "" && CPullOptions::GetWebSocketSecureUrl() != $_POST['path_to_websocket_secure'])
		{
			CPullOptions::SetWebSocketSecureUrl($_POST['path_to_websocket_secure']);
			$send = true;
		}

		if ($send)
			CPullOptions::SendConfigDie();

		if (isset($_POST['nginx']))
		{
			if (!CPullOptions::GetQueueServerStatus())
			{
				$send = true;
				CPullOptions::SendConfigDie();
				CPullOptions::SetQueueServerStatus('Y');
			}
		}
		else
		{
			if (CPullOptions::GetQueueServerStatus())
			{
				$send = true;
				CPullOptions::SendConfigDie();
				CPullOptions::SetQueueServerStatus('N');
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

		if (isset($_POST['exclude_sites']))
		{
			$arSites = Array();
			foreach ($_POST['exclude_sites'] as $site)
			{
				$site = htmlspecialcharsbx(trim($site));
				if (strlen($site) <= 0)
					continue;

				$arSites[$site] = $site;
			}
			CPullOptions::SetExcludeSites($arSites);
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


$dbSites = CSite::GetList(($b = ""), ($o = ""), Array("ACTIVE" => "Y"));
$arSites = array();
$aSubTabs = array();
while ($site = $dbSites->Fetch())
{
	$site["ID"] = htmlspecialcharsbx($site["ID"]);
	$site["NAME"] = htmlspecialcharsbx($site["NAME"]);
	$arSites[$site["ID"]] = $site;
}
$arExcludeSites = CPullOptions::GetExcludeSites();
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
		<td align="right" width="50%"><?=GetMessage("PULL_OPTIONS_PUSH")?>:</td>
		<td><input type="checkbox" size="40" value="Y" <?=(CPullOptions::GetPushStatus()?' checked':'')?> name="push"></td>
	</tr>
	<tr>
		<td width="40%"></td>
		<td width="60%"></td>
	</tr>
	<tr>
		<td width="40%"><nobr><?=GetMessage("PULL_OPTIONS_NGINX")?></nobr>:</td>
		<td width="60%"><input id="config_nginx" type="checkbox" size="40" value="Y" <?=(CPullOptions::GetQueueServerStatus()?' checked':'')?> name="nginx"></td>
	</tr>
	<tr>
		<td width="40%"><nobr><?=GetMessage("PULL_OPTIONS_NGINX_VERSION")?></nobr>:</td>
		<td width="60%">
			<nobr><label><input type="radio" id="config_nginx_version_1" value="1" name="nginx_version" <?=(CPullOptions::GetQueueServerVersion() == 1?' checked':'')?> <?=(CPullOptions::GetQueueServerStatus()? '':'disabled="true"')?>><?=GetMessage("PULL_OPTIONS_NGINX_VERSION_034")?></label></nobr><br>
			<nobr><label><input type="radio" id="config_nginx_version_2" value="2" name="nginx_version" <?=(CPullOptions::GetQueueServerVersion() == 2?' checked':'')?> <?=(CPullOptions::GetQueueServerStatus()? '':'disabled="true"')?>><?=GetMessage("PULL_OPTIONS_NGINX_VERSION_040")?></label></nobr>
		</td>
	</tr>
	<tr>
		<td width="40%"></td>
		<td width="60%">
			<?=GetMessage("PULL_OPTIONS_NGINX_VERSION_034_DESC")?>
		</td>
	</tr>
	<tr>
		<td width="40%"></td>
		<td width="60%"></td>
	</tr>
	<tr>
		<td><?=GetMessage("PULL_OPTIONS_PATH_TO_PUBLISH")?>:</td>
		<td><input id="config_path_to_publish" type="text" size="40" value="<?=htmlspecialcharsbx(CPullOptions::GetPublishUrl())?>" name="path_to_publish" <?=(CPullOptions::GetQueueServerStatus()? '':'disabled="true"')?>></td>
	</tr>
	<tr>
		<td align="right" width="50%"><?=GetMessage("PULL_OPTIONS_NGINX_BUFFER")?>:</td>
		<td><input id="config_nginx_command_per_hit" type="text" size="10" value="<?=CPullOptions::GetCommandPerHit()?>" name="nginx_command_per_hit" <?=(CPullOptions::GetQueueServerStatus() && CPullOptions::GetQueueServerVersion() > 1? '':'disabled="true"')?>></td>
	</tr>
	<tr>
		<td width="40%"></td>
		<td width="60%">
			<?=GetMessage("PULL_OPTIONS_NGINX_BUFFERS_DESC")?>
		</td>
	</tr>
	<tr>
		<td width="40%"></td>
		<td width="60%"></td>
	</tr>
	<tr>
		<td ><?=GetMessage("PULL_OPTIONS_PATH_TO_LISTENER")?>:</td>
		<td><input id="config_path_to_listener" type="text" size="40" value="<?=htmlspecialcharsbx(CPullOptions::GetListenUrl())?>" name="path_to_listener" <?=(CPullOptions::GetQueueServerStatus()? '':'disabled="true"')?>></td>
	</tr>
	<tr>
		<td ><?=GetMessage("PULL_OPTIONS_PATH_TO_LISTENER_SECURE")?>:</td>
		<td><input id="config_path_to_listener_secure" type="text" size="40" value="<?=htmlspecialcharsbx(CPullOptions::GetListenSecureUrl())?>" name="path_to_listener_secure" <?=(CPullOptions::GetQueueServerStatus()? '':'disabled="true"')?>></td>
	</tr>
	<tr>
		<td width="40%"></td>
		<td width="60%">
			<?=GetMessage("PULL_OPTIONS_PATH_TO_LISTENER_DESC")?>
		</td>
	</tr>
	<tr>
		<td ><?=GetMessage("PULL_OPTIONS_PATH_TO_MOBILE_LISTENER")?>:</td>
		<td><input id="config_path_to_mobile_listener" type="text" size="40" value="<?=htmlspecialcharsbx(CPullOptions::GetListenUrl("", true))?>" name="path_to_mobile_listener" <?=(CPullOptions::GetQueueServerStatus()? '':'disabled="true"')?>></td>
	</tr>
	<tr>
		<td ><?=GetMessage("PULL_OPTIONS_PATH_TO_MOBILE_LISTENER_SECURE")?>:</td>
		<td><input id="config_path_to_mobile_listener_secure" type="text" size="40" value="<?=htmlspecialcharsbx(CPullOptions::GetListenSecureUrl("", true))?>" name="path_to_mobile_listener_secure" <?=(CPullOptions::GetQueueServerStatus()? '':'disabled="true"')?>></td>
	</tr>
	<tr>
		<td width="40%"></td>
		<td width="60%">
			<?=GetMessage("PULL_OPTIONS_PATH_TO_MOBILE_LISTENER_DESC")?>
		</td>
	</tr>
	<tr>
		<td width="40%"></td>
		<td width="60%"></td>
	</tr>
	<tr>
		<td align="right" width="50%"><?=GetMessage("PULL_OPTIONS_WEBSOCKET")?>:</td>
		<td><input type="checkbox" size="40" value="Y" <?=(CPullOptions::GetWebSocket()?' checked':'')?> id="config_websocket" name="websocket" <?=(CPullOptions::GetQueueServerStatus() && CPullOptions::GetQueueServerVersion() > 1 ? '': 'disabled="true"')?>></td>
	</tr>
	<tr>
		<td ><?=GetMessage("PULL_OPTIONS_PATH_TO_WEBSOCKET")?>:</td>
		<td><input id="config_path_to_websocket" type="text" size="40" value="<?=htmlspecialcharsbx(CPullOptions::GetWebSocketUrl())?>" name="path_to_websocket" <?=(!CPullOptions::GetQueueServerStatus() || !CPullOptions::GetWebSocketStatus() ? 'disabled="true"': '')?></td>
	</tr>
	<tr>
		<td ><?=GetMessage("PULL_OPTIONS_PATH_TO_WEBSOCKET_SECURE")?>:</td>
		<td><input id="config_path_to_websocket_secure" type="text" size="40" value="<?=htmlspecialcharsbx(CPullOptions::GetWebSocketSecureUrl())?>" name="path_to_websocket_secure" <?=(!CPullOptions::GetQueueServerStatus() || !CPullOptions::GetWebSocketStatus() ? 'disabled="true"': '')?></td>
	</tr>
	<tr>
		<td width="40%"></td>
		<td width="60%">
			<?=GetMessage("PULL_OPTIONS_WEBSOCKET_DESC")?>
		</td>
	</tr>
	<tr>
		<td width="40%"></td>
		<td width="60%"></td>
	</tr>
	<tr>
		<td width="40%"></td>
		<td width="60%"></td>
	</tr>
	<?if (count($arSites) > 1):?>
	<tr valign="top">
		<td><?=GetMessage("PULL_OPTIONS_SITES")?>:</td>
		<td>
			<select name="exclude_sites[]" multiple size="4">
				<option value=""></option>
			<?foreach($arSites as $site):?>
				<option value="<?=$site['ID']?>" <?=(isset($arExcludeSites[$site['ID']])?' selected':'')?>><? echo $site['NAME'].' ['.$site['ID'].']'?></option>
			<?endforeach;?>
			</select>
		</td>
	</tr>
	<?endif;?>

<?$tabControl->Buttons();?>
<script language="JavaScript">
BX.bind(BX('config_nginx'), 'change', function(){
	if (this.checked)
	{
		if (confirm("<?=GetMessageJS("PULL_OPTIONS_NGINX_CONFIRM")?>"))
		{
			BX('config_nginx_version_1').disabled = false;
			BX('config_nginx_version_2').disabled = false;
			BX('config_path_to_publish').disabled = false;
			BX('config_path_to_listener').disabled = false;
			BX('config_path_to_listener_secure').disabled = false;
			BX('config_path_to_mobile_listener').disabled = false;
			BX('config_path_to_mobile_listener_secure').disabled = false;

			if (BX('config_nginx_version_2').checked)
			{
				BX('config_nginx_command_per_hit').disabled = false;
				BX('config_websocket').disabled = false;
				if (BX('config_websocket').checked)
				{
					BX('config_path_to_websocket').disabled = false;
					BX('config_path_to_websocket_secure').disabled = false;
				}
			}
		}
		else
		{
			this.checked = false;
		}
	}
	else
	{
		BX('config_nginx_version_1').disabled = true;
		BX('config_nginx_version_2').disabled = true;
		BX('config_path_to_publish').disabled = true;
		BX('config_path_to_listener').disabled = true;
		BX('config_path_to_listener_secure').disabled = true;
		BX('config_path_to_mobile_listener').disabled = true;
		BX('config_path_to_mobile_listener_secure').disabled = true;


		BX('config_nginx_command_per_hit').disabled = true;
		BX('config_websocket').disabled = true;
		BX('config_path_to_websocket').disabled = true;
		BX('config_path_to_websocket_secure').disabled = true;
	}
});
BX.bind(BX('config_nginx_version_1'), 'change', function(){
	if (this.checked)
	{
		BX('config_nginx_command_per_hit').disabled = true;
		BX('config_websocket').disabled = true;
		BX('config_path_to_websocket').disabled = true;
		BX('config_path_to_websocket_secure').disabled = true;
	}
	else
	{
		BX('config_nginx_command_per_hit').disabled = false;
		BX('config_websocket').disabled = false;
		if (BX('config_websocket').checked)
		{
			BX('config_path_to_websocket').disabled = false;
			BX('config_path_to_websocket_secure').disabled = false;
		}
	}
});
BX.bind(BX('config_nginx_version_2'), 'change', function(){
	if (this.checked)
	{
		BX('config_nginx_command_per_hit').disabled = false;
		BX('config_websocket').disabled = false;
		if (BX('config_websocket').checked)
		{
			BX('config_path_to_websocket').disabled = false;
			BX('config_path_to_websocket_secure').disabled = false;
		}
	}
	else
	{
		BX('config_nginx_command_per_hit').disabled = true;
		BX('config_websocket').disabled = true;
		BX('config_path_to_websocket').disabled = true;
		BX('config_path_to_websocket_secure').disabled = true;
	}
});
BX.bind(BX('config_websocket'), 'change', function(){
	if (this.checked)
	{
		BX('config_path_to_websocket').disabled = false;
		BX('config_path_to_websocket_secure').disabled = false;
	}
	else
	{
		BX('config_path_to_websocket').disabled = true;
		BX('config_path_to_websocket_secure').disabled = true;
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