<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

foreach (GetModuleEvents("main", "system.field.edit.file", true) as $arEvent)
{
	if (ExecuteModuleEventEx($arEvent, array($arResult, $arParams)))
		return;
}

?>
<div id="main_<?=$arParams["arUserField"]["FIELD_NAME"]?>">
<?
$postFix = ($arParams["arUserField"]["MULTIPLE"] == "Y" ? "[]" : "");
foreach ($arResult["VALUE"] as $res):
	?>
	<div class="fields files">
		<input type="hidden" name="<?=$arParams["arUserField"]["~FIELD_NAME"]?>_old_id<?=$postFix?>" value="<?=$res?>" />
		<?=CFile::InputFile($arParams["arUserField"]["FIELD_NAME"], 0, $res, false, 0, "", "", 0, "", ' value="'.$res.'"', true, isset($arParams['SHOW_FILE_PATH']) ? $arParams['SHOW_FILE_PATH'] : true).
		'<br>'.
		CFile::ShowImage($res, 0, 0, null, '', false, 0, 0, 0, !empty($arParams['FILE_URL_TEMPLATE']) ? $arParams['FILE_URL_TEMPLATE'] : '');
		?>
	</div>
	<?
endforeach;
?>
</div>
<?if ($arParams["arUserField"]["MULTIPLE"] == "Y" && $arParams["SHOW_BUTTON"] != "N"):?>
<div style="display:none" id="main_add_<?=$arParams["arUserField"]["FIELD_NAME"]?>" class="fields files">
	<input type="hidden" name="<?=$arParams["arUserField"]["~FIELD_NAME"]?>_old_id[]" value="" />
	<?=CFile::InputFile($arParams["arUserField"]["FIELD_NAME"], 0, "")?>
</div>
<input type="button" value="<?=GetMessage("USER_TYPE_PROP_ADD")?>" onClick="addElement('<?=$arParams["arUserField"]["FIELD_NAME"]?>', this)">
<?endif;?>
