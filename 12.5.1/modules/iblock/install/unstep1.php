<?IncludeModuleLangFile(__FILE__);

if(IsModuleInstalled('catalog')):?>
	<form action="<?echo $APPLICATION->GetCurPage()?>">
		<?echo CAdminMessage::ShowMessage(GetMessage("MOD_UNINST_CATALOG_INSTALLED"));?>
		<input type="hidden" name="lang" value="<?echo LANG?>">
		<input type="submit" name="inst" value="<?echo GetMessage("MOD_UNINST_BACK")?>">
	</form>
<?else:?>
	<form action="<?echo $APPLICATION->GetCurPage()?>">
		<?=bitrix_sessid_post()?>
		<input type="hidden" name="lang" value="<?echo LANG?>">
		<input type="hidden" name="id" value="iblock">
		<input type="hidden" name="uninstall" value="Y">
		<input type="hidden" name="step" value="2">
		<?echo CAdminMessage::ShowMessage(GetMessage("MOD_UNINST_WARN"))?>
		<p><?echo GetMessage("MOD_UNINST_SAVE")?></p>
		<p><input type="checkbox" name="savedata" id="savedata" value="Y" checked><label for="savedata"><?echo GetMessage("MOD_UNINST_SAVE_TABLES")?></label></p>
		<input type="submit" name="inst" value="<?echo GetMessage("MOD_UNINST_DEL")?>">
	</form>
<?endif?>