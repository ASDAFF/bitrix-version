<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();


if (!CModule::IncludeModule("sale"))
{
	ShowError(GetMessage("SALE_MODULE_NOT_INSTALL"));
	return;
}
$arParams["PATH_TO_BASKET"] = Trim($arParams["PATH_TO_BASKET"]);
$arParams["PATH_TO_ORDER"] = Trim($arParams["PATH_TO_ORDER"]);

$dbBaket = CSaleBasket::GetList(
	array("NAME" => "ASC"),
	array("FUSER_ID" => CSaleBasket::GetBasketUserID(), "LID" => SITE_ID, "ORDER_ID" => "NULL")
);

$bReady = False;
$bDelay = False;
$bNotAvail = False;
$bSubscribe = False;
$arItems = array();

while ($arBasket = $dbBaket->GetNext())
{
	if ($arBasket["DELAY"]=="N" && $arBasket["CAN_BUY"]=="Y")
		$bReady = True;
	elseif ($arBasket["DELAY"]=="Y" && $arBasket["CAN_BUY"]=="Y")
		$bDelay = True;
	elseif ($arBasket["CAN_BUY"]=="N" && $arBasket["SUBSCRIBE"]=="N")
		$bNotAvail = True;
	elseif ($arBasket["CAN_BUY"]=="N" && $arBasket["SUBSCRIBE"]=="Y")
		$bSubscribe = True;
	
	$arBasket["PRICE_FORMATED"] = SaleFormatCurrency($arBasket["PRICE"], $arBasket["CURRENCY"]);
	$arItems[] = $arBasket;
}

$arResult["READY"] = (($bReady)?"Y":"N");
$arResult["DELAY"] = (($bDelay)?"Y":"N");
$arResult["NOTAVAIL"] = (($bNotAvail)?"Y":"N");
$arResult["SUBSCRIBE"] = (($bSubscribe)?"Y":"N");
$arResult["ITEMS"] = $arItems;

$this->IncludeComponentTemplate();
?>