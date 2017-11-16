<?IncludeModuleLangFile(__FILE__);
if(!check_bitrix_sessid() || !CModule::IncludeModule("iblock") || !CModule::IncludeModule("blog")) 
        return;

echo CAdminMessage::ShowNote(GetMessage("MOD_INST_OK"));
?>
<form action="<?=$APPLICATION->GetCurPage()?>">
	<input type="hidden" name="lang" value="<?=LANG?>">
	<input type="submit" name="" value="<?=GetMessage("MOD_BACK")?>">
<form>