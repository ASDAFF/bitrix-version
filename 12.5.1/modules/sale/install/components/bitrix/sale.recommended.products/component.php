<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("sale"))
{
	ShowError(GetMessage("SAP_MODULE_NOT_INSTALL"));
	return;
}
$arParams["ID"] = IntVal($arParams["ID"]);
if($arParams["ID"] <= 0)
	return;
$arParams["ELEMENT_COUNT"] = IntVal($arParams["ELEMENT_COUNT"]);
$arParams["MIN_BUYES"] = IntVal($arParams["MIN_BUYES"]);
if($arParams["MIN_BUYES"] <= 0)
	$arParams["MIN_BUYES"] = 2;
if($arParams["ELEMENT_COUNT"] <= 0)
	$arParams["ELEMENT_COUNT"] = 5;

$arParams['CONVERT_CURRENCY'] = (isset($arParams['CONVERT_CURRENCY']) && 'Y' == $arParams['CONVERT_CURRENCY'] ? 'Y' : 'N');
$arParams['CURRENCY_ID'] = trim(strval($arParams['CURRENCY_ID']));
if ('' == $arParams['CURRENCY_ID'])
{
	$arParams['CONVERT_CURRENCY'] = 'N';
}
elseif ('N' == $arParams['CONVERT_CURRENCY'])
{
	$arParams['CURRENCY_ID'] = '';
}

$arResult = Array();
$dbRes = CSaleProduct::GetProductList($arParams["ID"], $arParams["MIN_BUYES"], $arParams["ELEMENT_COUNT"]*2);
while($arRes = $dbRes->Fetch())
{
	$arResult["ID"][] = $arRes["PARENT_PRODUCT_ID"];
}

$this->IncludeComponentTemplate();
?>