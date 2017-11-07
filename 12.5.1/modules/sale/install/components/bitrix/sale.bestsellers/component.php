<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if(!isset($arParams["CACHE_TIME"]))
	$arParams["CACHE_TIME"] = 86400;

$arParams["DETAIL_URL"] = trim($arParams["DETAIL_URL"]);

$arParams["ITEM_COUNT"] = IntVal($arParams["ITEM_COUNT"]);
if($arParams["ITEM_COUNT"] <= 0)
	$arParams["ITEM_COUNT"] = 10;

$arResult = Array();
$arOrderFilter = Array();
$arFilter = Array();

if($_REQUEST["by"] == "QUANTITY")
	$arParams["by_val"] = "QUANTITY";
else
	$arParams["by_val"] = "AMOUNT";

if(IntVal($_REQUEST["days"]) > 0)
	$arParams["days"] = IntVal($_REQUEST["days"]);
else
	$arParams["days"] = 30;


if(!is_array($arParams["PERIOD"]) || count($arParams["PERIOD"]) <= 0)
	$arParams["PERIOD"] = Array(15, 30, 90, 180);
else
{
	foreach($arParams["PERIOD"] as $k => $v)
	{
		if(IntVal($v) <= 0)
			unset($arParams["PERIOD"][$k]);
	}
}

if(!is_array($arParams["BY"]) || count($arParams["BY"]) <= 0)
	$arParams["BY"] = Array("AMOUNT", "QUANTITY");


if($this->StartResultCache(false, $USER->GetGroups()))
{
	if(!CModule::IncludeModule("iblock"))
	{
		$this->AbortResultCache();
		ShowError(GetMessage("SB_IBLOCK_MODULE_NOT_INSTALL"));
		return;
	}
	if (!CModule::IncludeModule("sale"))
	{
		$this->AbortResultCache();
		ShowError(GetMessage("SB_MODULE_NOT_INSTALL"));
		return;
	}

	if(strlen($arParams["FILTER_NAME"])<=0 || !preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/", $arParams["FILTER_NAME"]))
	{
		$arFilterRes = array();
	}
	else
	{
		global $$arParams["FILTER_NAME"];
		$arFilterRes = ${$arParams["FILTER_NAME"]};
		if(!is_array($arFilter))
			$arFilterRes = array();
	}

	$arOrderFilterRes = Array(
			">=DATE_ALLOW_DELIVERY" => ConvertTimeStamp(AddToTimeStamp(Array("DD" => "-".$arParams["days"]))),
			"=ALLOW_DELIVERY" => "Y",
			"=LID" => SITE_ID,
		);

	if(strlen($arParams["ORDER_FILTER_NAME"])>=0 && preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/", $arParams["ORDER_FILTER_NAME"]))
	{
		global $$arParams["ORDER_FILTER_NAME"];
		if(is_array(${$arParams["ORDER_FILTER_NAME"]}))
		{
			foreach(${$arParams["ORDER_FILTER_NAME"]} as $k => $v)
				$arOrderFilterRes[$k] = $v;
		}
	}

	$i = 0;
	$dbRes = CSaleProduct::GetBestSellerList($arParams["by_val"], $arFilterRes, $arOrderFilterRes, $arParams["ITEM_COUNT"]*2);
	while($arRes = $dbRes->GetNext())
	{
		$arFilterIB = Array("SITE_ID" => SITE_ID, "ID" => $arRes["PRODUCT_ID"], "ACTIVE" => "Y");
		if(strlen($arRes["CATALOG_XML_ID"]) > 0)
			$arFilterIB["IBLOCK_EXTERNAL_ID"] = $arRes["CATALOG_XML_ID"];
		$arResult["PRODUCT"][] = $arRes;

		$dbItem = CIBlockElement::GetList(Array(), $arFilterIB, false, Array("nTopCount" => 1), Array("ID", "IBLOCK_ID", "NAME", "DETAIL_PAGE_URL"));
		$dbItem->SetUrlTemplates($arParams["DETAIL_URL"]);
		if($arItem = $dbItem -> GetNext())
		{
			$arResTmp = $arItem;
			$i++;
			$arResult["ELEMENT"][] = $arResTmp;
		}
		if($i >= $arParams["ITEM_COUNT"])
			break;
	}
	$this->SetResultCacheKeys(array());
	$this->IncludeComponentTemplate();
}
?>