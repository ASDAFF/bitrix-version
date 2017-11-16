<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

function arraySort($a, $b)
{
	$a = MakeTimeStamp($a["DATE_VISIT"], "DD.MM.YYYY HH:MI:SS");
	$b = MakeTimeStamp($b["DATE_VISIT"], "DD.MM.YYYY HH:MI:SS");

	if ($a == $b) {
		return 0;
	}

	return ($a > $b) ? -1 : 1;
}

$arParams["VIEWED_COUNT"] = IntVal($arParams["VIEWED_COUNT"]);
if ($arParams["VIEWED_COUNT"] <= 0)
	$arParams["VIEWED_COUNT"] = 5;
$arParams["VIEWED_IMG_HEIGHT"] = IntVal($arParams["VIEWED_IMG_HEIGHT"]);
if($arParams["VIEWED_IMG_HEIGHT"] <= 0)
	$arParams["VIEWED_IMG_HEIGHT"] = 150;
$arParams["VIEWED_IMG_WIDTH"] = IntVal($arParams["VIEWED_IMG_WIDTH"]);
if ($arParams["VIEWED_IMG_WIDTH"] <= 0)
	$arParams["VIEWED_IMG_WIDTH"] = 150;

if($arParams["SET_TITLE"] == "Y")
	$APPLICATION->SetTitle(GetMessage("VIEW_TITLE"));

$arParams["VIEWED_NAME"] = (($arParams["VIEWED_NAME"] == "Y") ? "Y" : "N");
$arParams["VIEWED_IMAGE"] = (($arParams["VIEWED_IMAGE"] == "Y") ? "Y" : "N");
$arParams["VIEWED_PRICE"] = (($arParams["VIEWED_PRICE"] == "Y") ? "Y" : "N");

if (!isset($arParams["VIEWED_CURRENCY"]) || strlen($arParams["VIEWED_CURRENCY"]) <= 0)
	$arParams["VIEWED_CURRENCY"] = "default";

$arResult = array();
$arFilter = array();

if (!CModule::IncludeModule("sale"))
{
	ShowError(GetMessage("VIEWE_NOT_INSTALL"));
	return;
}
if (!CModule::IncludeModule("iblock"))
{
	ShowError(GetMessage("VIEWIBLOCK_NOT_INSTALL"));
	return;
}
if (!CModule::IncludeModule("catalog"))
{
	ShowError(GetMessage("VIEWCATALOG_NOT_INSTALL"));
	return;
}

$arFilter["LID"] = SITE_ID;
$arFilter["FUSER_ID"] = CSaleBasket::GetBasketUserID();
$arGroups = $USER->GetUserGroupArray();

//add to busket
if (array_key_exists($arParams["ACTION_VARIABLE"], $_REQUEST) && array_key_exists($arParams["PRODUCT_ID_VARIABLE"], $_REQUEST))
{
	if(array_key_exists($arParams["ACTION_VARIABLE"]."BUY", $_REQUEST))
		$action = "BUY";
	elseif(array_key_exists($arParams["ACTION_VARIABLE"]."ADD2BASKET", $_REQUEST))
		$action = "ADD2BASKET";
	else
		$action = ToUpper($_REQUEST[$arParams["ACTION_VARIABLE"]]);

	$productID = intval($_REQUEST[$arParams["PRODUCT_ID_VARIABLE"]]);

	//get props sku
	$product_properties = array();
	$arPropsSku = array();

	$arParentSku = CCatalogSku::GetProductInfo($productID);
	if ($arParentSku && count($arParentSku) > 0)
	{
		$dbProduct = CIBlockElement::GetList(array(), array("ID" => $productID), false, false, array('IBLOCK_ID', 'IBLOCK_SECTION_ID'));
		$arProduct = $dbProduct->Fetch();

		$dbOfferProperties = CIBlock::GetProperties($arProduct["IBLOCK_ID"], array(), array("!XML_ID" => "CML2_LINK"));
		while($arOfferProperties = $dbOfferProperties->Fetch())
			$arPropsSku[] = $arOfferProperties["CODE"];

		$product_properties = CIBlockPriceTools::GetOfferProperties(
						$productID,
						$arParentSku["IBLOCK_ID"],
						$arPropsSku
					);
	}

	if (($action == "ADD2BASKET" || $action == "BUY") && $productID > 0)
	{
		if (CModule::IncludeModule('catalog'))
			Add2BasketByProductID($productID, 1, array(), $product_properties);

		if ($action == "BUY")
			LocalRedirect($arParams["BASKET_URL"]);
		else
			LocalRedirect($APPLICATION->GetCurPageParam("", array($arParams["PRODUCT_ID_VARIABLE"], $arParams["ACTION_VARIABLE"])));
	}
}//end add to basket

$arViewed = array();
$arViewedId = array();
$db_res = CSaleViewedProduct::GetList(
		array(
			"DATE_VISIT" => "DESC"
		),
		$arFilter,
		false,
		array(
			"nTopCount" => $arParams["VIEWED_COUNT"]
		),
		array('ID', 'IBLOCK_ID', 'PRICE', 'CURRENCY', 'CAN_BUY', 'PRODUCT_ID', 'DATE_VISIT', 'DETAIL_PAGE_URL', 'DETAIL_PICTURE', 'PREVIEW_PICTURE', 'NAME', 'NOTES')
);
while ($arItems = $db_res->Fetch())
{
	$arViewedId[] = $arItems["PRODUCT_ID"];
	$arViewed[$arItems["PRODUCT_ID"]] = $arItems;
}
$arElementSort = array();
//check catalog
if (count($arViewedId) > 0 && CModule::IncludeModule("iblock") && CModule::IncludeModule("catalog"))
{
	$res = CIBlockElement::GetList(array(), array("ID" => $arViewedId), false, false, array('ID', 'IBLOCK_ID', 'IBLOCK_SECTION_ID'));
	while ($arElements = $res->Fetch())
	{
		$arElements["DATE_VISIT"] = $arViewed[$arElements["ID"]]["DATE_VISIT"];
		$arElementSort[] = $arElements;
	}

	usort($arElementSort, "arraySort");

	foreach ($arElementSort as $arElements)
	{
		static $arCacheOffersIblock = array();
		$priceMin = 0;
		$arItems = $arViewed[$arElements["ID"]];
		$arItems["IBLOCK_ID"] = $arElements["IBLOCK_ID"];

		if (!is_set($arCacheOffersIblock[$arElements["IBLOCK_ID"]]))
		{
			$mxResult = CCatalogSKU::GetInfoByProductIBlock($arElements["IBLOCK_ID"]);
			if (is_array($mxResult))
			{
				$arOffersIblock["OFFERS_IBLOCK_ID"] = $mxResult["IBLOCK_ID"];
				$arCacheOffersIblock[$arElements["IBLOCK_ID"]] = $arOffersIblock;
			}
		}
		else
			$arOffersIblock = $arCacheOffersIblock[$arElements["IBLOCK_ID"]];

		if (IntVal($arOffersIblock["OFFERS_IBLOCK_ID"]) > 0)
		{
			static $arCacheOfferProperties = array();
			if (!is_set($arCacheOfferProperties[$arOffersIblock["OFFERS_IBLOCK_ID"]]))
			{
				$dbOfferProperties = CIBlock::GetProperties($arOffersIblock["OFFERS_IBLOCK_ID"], Array(), Array("!XML_ID" => "CML2_LINK"));
				while($arOfferProperties = $dbOfferProperties->Fetch())
					$arCacheOfferProperties[$arOffersIblock["OFFERS_IBLOCK_ID"]][] = $arOfferProperties["CODE"];
			}
			$arIblockOfferPropsFilter = $arCacheOfferProperties[$arOffersIblock["OFFERS_IBLOCK_ID"]];

			$arIblockOfferProps = array();
			$arIblockOfferPropsFilter = array();
			foreach ($arIblockOfferPropsFilter as $val)
			{
				$arIblockOfferProps[] = array("CODE" => $val["CODE"], "NAME" => $val["NAME"]);
				$arIblockOfferPropsFilter[] = $val["CODE"];
			}

			static $arCacheResultPrices = array();
			if (!is_set($arCacheResultPrices[$arElements["IBLOCK_ID"]]))
			{
				$dbPriceType = CCatalogGroup::GetList(array(),array('NAME_LANG' => $arItems['NOTES'], 'CAN_BUY' => 'Y'),false,false,array('NAME', 'ID'));
				$arPriceType = $dbPriceType->Fetch();
				$arResultPrices = CIBlockPriceTools::GetCatalogPrices($arElements["IBLOCK_ID"], array($arPriceType["NAME"]));
				$arCacheResultPrices[$arElements["IBLOCK_ID"]] = $arResultPrices;
			}
			else
				$arResultPrices = $arCacheResultPrices[$arElements["IBLOCK_ID"]];

			$arOffers = CIBlockPriceTools::GetOffersArray(
						$arElements["IBLOCK_ID"],
						$arItems["PRODUCT_ID"],
						array("ID" => "DESC"),
						array("NAME"),
						$arIblockOfferPropsFilter,
						0,
						$arResultPrices,
						1,
						array(),
						$USER->GetID(),
						$arItems['LID']
			);
			if (count($arOffers) > 0)
			{
				foreach($arOffers as $arOffer)
				{
					/*$arPrice = CCatalogProduct::GetOptimalPrice($arOffer['ID'], 1, $arGroups, "N", array(), $arItems['LID']);
					$arOffer["PRICES"] = $arPrice;

					if ($arCatalogProduct = CCatalogProduct::GetByID($arOffer['ID']))
					{
						if ($arCatalogProduct["CAN_BUY_ZERO"]!="Y" && ($arCatalogProduct["QUANTITY_TRACE"]=="Y" && doubleval($arCatalogProduct["QUANTITY"])<=0))
							$arItems["CAN_BUY"] = "N";
						else
							$arItems["CAN_BUY"] = "Y";
					}

					if (($priceMin === 0) || ($arPrice["DISCOUNT_PRICE"] < $priceMin))
						$priceMin = $arPrice["DISCOUNT_PRICE"];*/

					foreach($arOffer["PRICES"] as $arPrice)
					{
						if($arPrice["CAN_ACCESS"])
						{
							if ($arPrice["DISCOUNT_VALUE"] < $arPrice["VALUE"])
								$priceMinTmp = $arPrice["DISCOUNT_VALUE"];
							else
								$priceMinTmp = $arPrice["VALUE"];

							if ($priceMinTmp < $priceMin || $priceMin === 0)
								$priceMin = $priceMinTmp;
						}
					}

					$arOffer["BUY_URL"] = htmlspecialcharsBX($APPLICATION->GetCurPageParam($arParams["ACTION_VARIABLE"]."=BUY&".$arParams["PRODUCT_ID_VARIABLE"]."=".$arOffer["ID"], array($arParams["PRODUCT_ID_VARIABLE"], $arParams["ACTION_VARIABLE"])));
					$arOffer["ADD_URL"] = htmlspecialcharsBX($APPLICATION->GetCurPageParam($arParams["ACTION_VARIABLE"]."=ADD2BASKET&".$arParams["PRODUCT_ID_VARIABLE"]."=".$arOffer["ID"], array($arParams["PRODUCT_ID_VARIABLE"], $arParams["ACTION_VARIABLE"])));

					$arItems["OFFERS"][] = $arOffer;
				}

				if ($priceMin > 0)
					$arItems["PRICE"] = $priceMin;
			}
		}

		if (floatval($arItems["PRICE"]) > 0)
		{
			if ($arParams["VIEWED_CURRENCY"] != "default" && $arItems["CURRENCY"] != $arParams["VIEWED_CURRENCY"])
			{
				$arItems["PRICE"] = CCurrencyRates::ConvertCurrency($arItems["PRICE"], $arItems["CURRENCY"], $arParams["VIEWED_CURRENCY"]);
				$arItems["CURRENCY"] = $arParams["VIEWED_CURRENCY"];
			}

			$arItems["PRICE_FORMATED"] = SaleFormatCurrency($arItems["PRICE"], $arItems["CURRENCY"]);
			if ($priceMin > 0)
				$arItems["PRICE_FORMATED"] = GetMessage("VIEW_PRICE_FROM")." ".$arItems["PRICE_FORMATED"];
			
			$arItems["CAN_BUY"] = "Y";
		}
		else
			$arItems["CAN_BUY"] = "N";

		$arItems["BUY_URL"] = htmlspecialcharsex($APPLICATION->GetCurPageParam($arParams["ACTION_VARIABLE"]."=BUY&".$arParams["PRODUCT_ID_VARIABLE"]."=".$arItems["PRODUCT_ID"], array($arParams["PRODUCT_ID_VARIABLE"], $arParams["ACTION_VARIABLE"])));
		$arItems["ADD_URL"] = htmlspecialcharsex($APPLICATION->GetCurPageParam($arParams["ACTION_VARIABLE"]."=ADD2BASKET&".$arParams["PRODUCT_ID_VARIABLE"]."=".$arItems["PRODUCT_ID"], array($arParams["PRODUCT_ID_VARIABLE"], $arParams["ACTION_VARIABLE"])));

		$arResult[] = $arItems;
	}
}

$this->IncludeComponentTemplate();
?>