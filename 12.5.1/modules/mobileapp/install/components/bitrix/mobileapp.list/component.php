<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('mobileapp'))
{
	ShowError("SMOL_MOBILEAPP_NOT_INSTALLED");
	return;
}

if(empty($arParams["ITEMS"]) || !is_array($arParams["ITEMS"]))
	return;

$arResult["ITEMS"] = $arParams["ITEMS"];

if (isset($_REQUEST['ajax_mode']) && $_REQUEST['ajax_mode'] == 'Y')
{
	$arResult["AJAX_MODE"] = true;
}
else
{
	$arResult["AJAX_MODE"] = false;
	$arResult["AJAX_PATH"] = $componentPath."/ajax.php";
	$arResult["JS_EVENT_ITEM_CHANGE"] = $arParams["JS_EVENT_ITEM_CHANGE"];
	$arResult["JS_EVENT_BOTTOM_REACHED"] = $arParams["JS_EVENT_BOTTOM_REACHED"];
	$arResult["SALE_ORDERS_LIST_PRELOAD_START"] = $arResult["SALE_ORDERS_LIST_PRELOAD_START"] ? $arParams["SALE_ORDERS_LIST_PRELOAD_START"] : 1;
}

$Sanitizer = new CBXSanitizer();
$Sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_LOW);

function sanitizeInputData(&$item, $key, $Sanitizer)
{
	if($key == 'DETAIL_LINK')
	{
		$linkItem = '<a href="'.$item.'">test</a>';
		if($linkItem != $Sanitizer->SanitizeHtml($linkItem))
			$item = '';
	}
	else
	{
		$item = $Sanitizer->SanitizeHtml($item);
	}

}

array_walk_recursive($arResult["ITEMS"], 'sanitizeInputData', $Sanitizer);

$this->IncludeComponentTemplate();
?>