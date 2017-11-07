<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!CModule::IncludeModule("fileman"))
	return;
if(!function_exists('CustomizeLightEditor'))
{
	function CustomizeLightEditor($id = false, $templateFolder = "")
	{
		static $bCalled = array();
		static $sTemplateFolder = "";
		if ($templateFolder != "")
			$sTemplateFolder = $templateFolder;

		if ($id && $bCalled[$id] !== true)
		{
?>
<script>
	function CheckLightEditorCustom()
	{
		if (!window.CustomizeLightEditor)
		{
			setTimeout("CheckLightEditorCustom", 100);
			return;
		}

		CustomizeLightEditor('<?=$id?>', {
			path: '<?= CUtil::JSEscape($sTemplateFolder)?>',
			imageLinkText: '<?=GetMessageJS("MPF_IMAGE_LINK")?>',
			spoilerText: '<?=GetMessageJS("MPF_SPOILER")?>',
			videoText: '<?=GetMessageJS("FPF_VIDEO")?>',
			videoUploadText: '<?= GetMessageJS("BPC_VIDEO_P")?>',
			videoUploadText1: '<?= GetMessageJS("BPC_VIDEO_PATH_EXAMPLE")?>',
			videoUploadText2: '<?= GetMessageJS("FPF_VIDEO")?>',
			videoUploadText3: '<?=GetMessageJS("MPF_VIDEO_SIZE")?>'
		});
	}
	CheckLightEditorCustom();
</script>
		<?
			$bCalled[$id] = true;
		}
	}
	CustomizeLightEditor(false, $templateFolder);
}
AddEventHandler("fileman", "OnIncludeLightEditorScript", "CustomizeLightEditor");
if ($arParams["LHE"]['ctrlEnterHandler'] === true || !empty($arParams["LHE"]['ctrlEnterHandler']))
{
?>
<script type="text/javascript">
window.__ctrlEnterHandler<?=$arParams["FORM_ID"]?> = function(e)
{
	window['<?=$arParams["LHE"]["jsObjName"]?>'].SaveContent();
	<?if ($arParams["LHE"]['ctrlEnterHandler'] !== true ):?>
	if (typeof window['<?=$arParams["LHE"]['ctrlEnterHandler']?>'] == 'function')
		window['<?=$arParams["LHE"]['ctrlEnterHandler']?>']();
	<?else:?>
	BX.submit(BX('<?=$arParams["FORM_ID"]?>'));
	<?endif;?>
}
</script>
<?
}
?>
<div id="edit-post-text"><?
$LHE = new CLightHTMLEditor;
$res = array_merge(
	array(
		'id' => $arParams["LHE"]["id"],
//		'width' => '800', // default 100%
		'height' => $arParams["TEXT"]["HEIGHT"],
		'inputId' => $arParams["TEXT"]["ID"],
		'inputName' => $arParams["TEXT"]["NAME"],
		'content' => htmlspecialcharsBack($arParams["TEXT"]["VALUE"]),
		'bUseFileDialogs' => false,
		'bUseMedialib' => false,
		'toolbarConfig' => $arParams["PARSER"],
		'jsObjName' => $arParams["LHE"]["jsObjName"],
		'arSmiles' => $arParams["SMILES"]["VALUE"],
		'smileCountInToolbar' => $arParams['SMILES_COUNT'],
		'bSaveOnBlur' => true,
		'BBCode' => true,
		'bConvertContentFromBBCodes' => false,
		'bQuoteFromSelection' => true, // Make quote from any text in the page
		'bSetDefaultCodeView' => false, // Set first view to CODE or to WYSIWYG
		'bBBParseImageSize' => true, // [IMG ID=XXX WEIGHT=5 HEIGHT=6],  [IMGWEIGHT=5 HEIGHT=6]/image.gif[/IMG]
		'bResizable' => true,
		'bAutoResize' => true,
		'autoResizeOffset' => 40,
		'controlButtonsHeight' => '34',
		'autoResizeSaveSize' => false
	), $arParams["LHE"]);
if (!array_key_exists("documentCSS", $res))
	$res["documentCSS"] = "";
	$res["documentCSS"] .=
		"font[size=\"1\"]{font-size: 40%;}".
		"font[size=\"2\"]{font-size: 60%;}".
		"font[size=\"3\"]{font-size: 80%;}".
		"font[size=\"4\"]{font-size: 100%;}".
		"font[size=\"5\"]{font-size: 120%;}".
		"font[size=\"6\"]{font-size: 140%;}".
		"font[size=\"7\"]{font-size: 160%;}";
if ($arParams["LHE"]['ctrlEnterHandler'] === true || !empty($arParams["LHE"]['ctrlEnterHandler']))
	$res['ctrlEnterHandler'] = "__ctrlEnterHandler".$arParams["FORM_ID"];
$LHE->Show($res);
?></div>
<script type="text/javascript">
<?
/* To remember:
 * All events from LHE (fileman) are executed from window (LHE_OnBeforeParsersInit, LHE_ConstructorInited, LHE_OnInit)
 * that is why we should to check editor id
 * All custom events (OnShowLHE, OnBeforeShowLHE, OnAfterShowLHE) are executed from parent node. In this case
 * we do not have to check id.*/
?>
BX.addCustomEvent(window, 'LHE_OnBeforeParsersInit', __LHE_OnBeforeParsersInit);
<?if (!$arParams["LHE"]['bInitByJS']){ /* This actions exist to execute custom events like OnBeforeShowLHE, OnAfterShowLHE*/?>
BX.addCustomEvent(window, 'LHE_ConstructorInited', function(pEditor){
	if (pEditor.id == '<?=$arParams["LHE"]["id"]?>')
		BX.onCustomEvent(BX('div<?=$arParams["LHE"]["jsObjName"]?>'), 'OnShowLHE', [true])});
<? } ?>
BX.addCustomEvent(window, 'LHE_OnInit', function(pEditor) {
	if (pEditor.id == '<?=$arParams["LHE"]["id"]?>')
	{
		if (!!window['<?=$arParams["JS_OBJECT_NAME"]?>'])
			window['<?=$arParams["JS_OBJECT_NAME"]?>']['oEditor'] = pEditor;
		__LHE_OnInit(pEditor);

		<?
		if(in_array("SmileList", $arParams["PARSER"]))
		{
			?>
			if(el = BX.findChild(BX('<?=$arParams["FORM_ID"]?>'), {'attr': {id: 'lhe_btn_smilelist'}}, true, false))
			{
				if(el.onmousedown !== null)
				{
					window['smileMenu<?=$arParams["FORM_ID"]?>'] = new MPFSmileMenu();
					window['smileMenu<?=$arParams["FORM_ID"]?>'].smile = <?=CUtil::PhpToJSObject($arParams["SMILES"]["VALUE"])?>;
					window['smileMenu<?=$arParams["FORM_ID"]?>'].smileSet = <?=CUtil::PhpToJSObject(array(1 => array("ID" => 1, "NAME" => "")))?>;
					window['smileMenu<?=$arParams["FORM_ID"]?>'].pEditor = pEditor;
					
					el.onmousedown = null;

					BX.bind(el, 'click', BX.delegate(function(e){window['smileMenu<?=$arParams["FORM_ID"]?>'].mpfOpenSmileMenu(); return BX.PreventDefault(e);}, this));
				}
			}
			<?
		}
		?>

	}
});
</script>