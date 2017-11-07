<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
/********************************
Get Prices from linked price list
********************************/
if($arParams["LINK_IBLOCK_ID"] && $arParams["LINK_PROPERTY_SID"] && count($arResult["LINKED_ELEMENTS"]))
{
	//SELECT
	$arSelect = array(
		"ID",
		"IBLOCK_ID",
		"XML_ID",
	);
	if(is_array($arParams["OFFERS_FIELDS"]))
		foreach($arParams["OFFERS_FIELDS"] as $key => $FIELD_CODE)
		{
			if($FIELD_CODE)
			{
				$FIELD_CODE = ToUpper($FIELD_CODE);
				$arParams["OFFERS_FIELDS"][$key] = $FIELD_CODE; 
				$arSelect[] = $FIELD_CODE;
			}
		}
	$bProperty = false;
	if(is_array($arParams["OFFERS_PROPERTIES"]))
		foreach($arParams["OFFERS_PROPERTIES"] as $PROPERTY_CODE)
			if($PROPERTY_CODE)
			{
				$bProperty = true;
				break;
			}
	if($bProperty)
		$arSelect[] = "PROPERTY_*";
	//WHERE
	$arID = array();
	$arMap = array();
	foreach($arResult["LINKED_ELEMENTS"] as $key=>$arItem)
	{
		$arID[] = $arItem["ID"];
		$arMap[$arItem["ID"]] = $key;
	}

	$arFilter = array(
		"ACTIVE" => "Y",
		"IBLOCK_ID" => $arParams["LINK_IBLOCK_ID"],
		"IBLOCK_LID" => SITE_ID,
		"IBLOCK_ACTIVE" => "Y",
		"ACTIVE_DATE" => "Y",
		"ACTIVE" => "Y",
		"CHECK_PERMISSIONS" => "Y",
		"ID" => $arID,
	);
	//ORDER BY
	$arSort = array(
		"ID" => "ASC",
	);
	//PRICES
	if(!$arParams["USE_PRICE_COUNT"])
	{
		foreach($arResult["CAT_PRICES"] as $key => $value)
		{
			$arSelect[] = $value["SELECT"];
			$arFilter["CATALOG_SHOP_QUANTITY_".$value["ID"]] = $arParams["SHOW_PRICE_COUNT"];
		}
	}

	$rsElements = CIBlockElement::GetList($arSort, $arFilter, false, false, $arSelect);
	while($obElement = $rsElements->GetNextElement())
	{
		$arElement = $obElement->GetFields();
		if($bProperty)
			$arProperties = $obElement->GetProperties();
		$ID = $arElement["ID"];
		$arItem = &$arResult["LINKED_ELEMENTS"][$arMap[$ID]];

		if(is_array($arParams["OFFERS_FIELDS"]))
			foreach($arParams["OFFERS_FIELDS"] as $FIELD_CODE)
				if($FIELD_CODE)
				{
					$arItem[$FIELD_CODE] = $arElement[$FIELD_CODE];
					$arItem["~".$FIELD_CODE] = $arElement["~".$FIELD_CODE];
				}

		$arItem["DISPLAY_PROPERTIES"] = array();
		if(is_array($arParams["OFFERS_PROPERTIES"]))
			foreach($arParams["OFFERS_PROPERTIES"] as $PROPERTY_CODE)
				if($PROPERTY_CODE)
				{
					$arItem["DISPLAY_PROPERTIES"][$PROPERTY_CODE] = CIBlockFormatProperties::GetDisplayValue($arElement, $arProperties[$PROPERTY_CODE], "catalog_out");
				}

		if($arParams["USE_PRICE_COUNT"])
		{
			if(CModule::IncludeModule("catalog"))
			{
				$arItem["PRICE_MATRIX"] = CatalogGetPriceTableEx($arElement["ID"]);
				foreach($arItem["PRICE_MATRIX"]["COLS"] as $keyColumn=>$arColumn)
					$arItem["PRICE_MATRIX"]["COLS"][$keyColumn]["NAME_LANG"] = htmlspecialchars($arColumn["NAME_LANG"]);
			}
			else
			{
				$arItem["PRICE_MATRIX"] = false;
			}
			$arItem["PRICES"] = array();
		}
		else
		{
			$arItem["PRICE_MATRIX"] = false;
			$arItem["PRICES"] = CIBlockPriceTools::GetItemPrices($arParams["LINK_IBLOCK_ID"], $arResult["CAT_PRICES"], $arElement);
		}
		$arItem["CAN_BUY"] = CIBlockPriceTools::CanBuy($arParams["LINK_IBLOCK_ID"], $arResult["CAT_PRICES"], $arElement);

		$arItem["BUY_URL"] = htmlspecialchars($GLOBALS["APPLICATION"]->GetCurPageParam($arParams["ACTION_VARIABLE"]."=BUY&".$arParams["PRODUCT_ID_VARIABLE"]."=".$arItem["ID"], array($arParams["PRODUCT_ID_VARIABLE"], $arParams["ACTION_VARIABLE"])));
		$arItem["ADD_URL"] = htmlspecialchars($GLOBALS["APPLICATION"]->GetCurPageParam($arParams["ACTION_VARIABLE"]."=ADD2BASKET&".$arParams["PRODUCT_ID_VARIABLE"]."=".$arItem["ID"], array($arParams["PRODUCT_ID_VARIABLE"], $arParams["ACTION_VARIABLE"])));
	}
}
?>
