<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var CBitrixComponent $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

use Bitrix\Main\Loader;
use Bitrix\Main\Type;
use Bitrix\Main\Application;

if (!CBXFeatures::IsFeatureEnabled('CatCompleteSet'))
{
	return;
}

$arParams['IBLOCK_ID'] = isset($arParams['IBLOCK_ID']) ? (int)$arParams['IBLOCK_ID'] : 0;
if ($arParams['IBLOCK_ID'] <= 0)
	return;

if (!isset($arParams["BASKET_URL"]))
	$arParams["BASKET_URL"] = '/personal/cart/';
if ('' == trim($arParams["BASKET_URL"]))
	$arParams["BASKET_URL"] = '/personal/cart/';

if(!isset($arParams["CACHE_TIME"]))
	$arParams["CACHE_TIME"] = 36000000;

$arParams['CACHE_GROUPS'] = trim($arParams['CACHE_GROUPS']);
if ('N' != $arParams['CACHE_GROUPS'])
	$arParams['CACHE_GROUPS'] = 'Y';

$elementID = intval($arParams["ELEMENT_ID"]);
if (!$elementID)
{
	ShowError(GetMessage("EMPTY_ELEMENT_ERROR"));
	return;
}

if (!is_array($arParams["OFFERS_CART_PROPERTIES"]))
	$arParams["OFFERS_CART_PROPERTIES"] = array();
foreach($arParams["OFFERS_CART_PROPERTIES"] as $i => $pid)
	if ($pid === "")
		unset($arParams["OFFERS_CART_PROPERTIES"][$i]);

if($this->StartResultCache(false, array($elementID, ($arParams["CACHE_GROUPS"]==="N"? false: $USER->GetGroups()))))
{
	if(!Loader::includeModule('catalog'))
	{
		ShowError(GetMessage("CATALOG_MODULE_NOT_INSTALLED"));
		$this->AbortResultCache();
		return;
	}
	$isProductHaveSet = CCatalogProductSet::isProductHaveSet($elementID, CCatalogProductSet::TYPE_GROUP);
	$product = false;
	if (!$isProductHaveSet)
	{
		$product = CCatalogSKU::GetProductInfo($elementID, $arParams['IBLOCK_ID']);
		if (!empty($product))
		{
			$isProductHaveSet = true;
		}
	}
	if (!$isProductHaveSet)
	{
		$this->AbortResultCache();
		return;
	}

	if (!empty($product))
	{
		$arResult['PRODUCT_ID'] = $product['ID'];
		$arResult['PRODUCT_IBLOCK_ID'] = $product['IBLOCK_ID'];
		$arResult['ELEMENT_ID'] = $elementID;
		$arResult['ELEMENT_IBLOCK_ID'] = $arParams['IBLOCK_ID'];
	}
	else
	{
		$arResult['PRODUCT_ID'] = $elementID;
		$arResult['PRODUCT_IBLOCK_ID'] = $arParams['IBLOCK_ID'];
		$arResult['ELEMENT_ID'] = $elementID;
		$arResult['ELEMENT_IBLOCK_ID'] = $arParams['IBLOCK_ID'];
	}

	$arParams['CONVERT_CURRENCY'] = (isset($arParams['CONVERT_CURRENCY']) && 'Y' == $arParams['CONVERT_CURRENCY'] ? 'Y' : 'N');
	$arParams['CURRENCY_ID'] = trim(strval($arParams['CURRENCY_ID']));
	if ($arParams['CURRENCY_ID'] == '')
	{
		$arParams['CONVERT_CURRENCY'] = 'N';
	}
	elseif ($arParams['CONVERT_CURRENCY'] == 'N')
	{
		$arParams['CURRENCY_ID'] = '';
	}
	$arParams["PRICE_VAT_INCLUDE"] = $arParams["PRICE_VAT_INCLUDE"] !== "N";

	$arConvertParams = array();
	if ($arParams['CONVERT_CURRENCY'] == 'Y')
	{
		if (!Loader::includeModule('currency'))
		{
			$arParams['CONVERT_CURRENCY'] = 'N';
			$arParams['CURRENCY_ID'] = '';
		}
		else
		{
			$arCurrencyInfo = CCurrency::GetByID($arParams['CURRENCY_ID']);
			if (!(is_array($arCurrencyInfo) && !empty($arCurrencyInfo)))
			{
				$arParams['CONVERT_CURRENCY'] = 'N';
				$arParams['CURRENCY_ID'] = '';
			}
			else
			{
				$arParams['CURRENCY_ID'] = $arCurrencyInfo['CURRENCY'];
				$arConvertParams['CURRENCY_ID'] = $arCurrencyInfo['CURRENCY'];
			}
		}
	}
	$arResult['CONVERT_CURRENCY'] = $arConvertParams;

	$countSetDefaultItems = 0;
	$arSetItemsID = array($arResult['ELEMENT_ID']);
	$arSetItemsDefaultID = array();
	$arSetItemsOtherID = array();
	$arSetItems = CCatalogProductSet::getAllSetsByProduct($arResult['PRODUCT_ID'], CCatalogProductSet::TYPE_GROUP);
	foreach ($arSetItems as $arItems)
	{
		Type\Collection::sortByColumn($arItems["ITEMS"], array('SORT' => SORT_ASC));
		foreach ($arItems["ITEMS"] as $arItem)
		{
			$arSetItemsID[] = $arItem["ITEM_ID"];
			if ($countSetDefaultItems < 3)
			{
				$arSetItemsDefaultID[$arItem["ITEM_ID"]] = $arItem["SORT"];
				$countSetDefaultItems++;
			}
			else
			{
				$arSetItemsOtherID[$arItem["ITEM_ID"]] = $arItem["SORT"];
			}
		}
	}

	$arSelect = array(
		'ID',
		'NAME',
		'CODE',
		'IBLOCK_ID',
		'IBLOCK_SECTION_ID',
		'DETAIL_PAGE_URL',
		'PREVIEW_PICTURE',
		'DETAIL_PICTURE',
		'PREVIEW_TEXT',
		'CATALOG_AVAILABLE'
	);
	$arResult["PRICES"] = CIBlockPriceTools::GetCatalogPrices($arResult['PRODUCT_IBLOCK_ID'], $arParams["PRICE_CODE"]);
	foreach($arResult["PRICES"] as $key => $value)
	{
		if (!$value['CAN_VIEW'] && !$value['CAN_BUY'])
			continue;
		$arSelect[] = $value["SELECT"];
	}

	$arResult["SET_ITEMS"]["DEFAULT"] = array();
	$arResult["SET_ITEMS"]["OTHER"] = array();
	$arResult["SET_ITEMS"]["PRICE"] = 0;
	$arResult["SET_ITEMS"]["OLD_PRICE"] = 0;
	$arResult["SET_ITEMS"]["PRICE_DISCOUNT_DIFFERENCE"] = 0;

	$arSetItemsRatio = array_fill_keys($arSetItemsID, 1);
	$rsRatios = CCatalogMeasureRatio::getList(
		array(),
		array('PRODUCT_ID' => $arSetItemsID),
		false,
		false,
		array('PRODUCT_ID', 'RATIO')
	);
	while ($arRatio = $rsRatios->Fetch())
	{
		if (isset($arSetItemsRatio[$arRatio['PRODUCT_ID']]))
		{
			$intRatio = (int)$arRatio['RATIO'];
			$dblRatio = (float)($arRatio['RATIO']);
			$arSetItemsRatio[$arRatio['PRODUCT_ID']] = ($dblRatio > $intRatio ? $dblRatio : $intRatio);
			$arSetItemsRatio[$arRatio['PRODUCT_ID']] = ($arSetItemsRatio[$arRatio['PRODUCT_ID']] > 0) ? $arSetItemsRatio[$arRatio['PRODUCT_ID']] : 1;
		}
	}
	$arResult["ITEMS_RATIO"] = $arSetItemsRatio;

	$dbElement = CIBlockElement::GetList(
		array(),
		array('ID' => $arSetItemsID),
		false,
		false,
		$arSelect
	);
	while ($arItem = $dbElement->GetNext())
	{
		if ($arItem['IBLOCK_ID'] > 0)
		{
			$arPrices = CIBlockPriceTools::GetItemPrices($arItem['IBLOCK_ID'], $arResult["PRICES"], $arItem, $arParams['PRICE_VAT_INCLUDE'], $arConvertParams);
			if (empty($arPrices) && isset($arSetItemsDefaultID[$arItem['ID']]))
			{
				$this->AbortResultCache();
				return;
			}
			$minPrice = 0;
			foreach($arPrices as $arPrice)
			{
				if ($arPrice['MIN_PRICE'] == "Y")
				{
					$arItem["PRICE_CURRENCY"] = $arPrice["CURRENCY"];
					$arItem["PRICE_DISCOUNT_VALUE"] = $arPrice["DISCOUNT_VALUE"];
					$arItem["PRICE_PRINT_DISCOUNT_VALUE"] = $arPrice["PRINT_DISCOUNT_VALUE"];
					$arItem["PRICE_VALUE"] = $arPrice["VALUE"];
					$arItem["PRICE_PRINT_VALUE"] = $arPrice["PRINT_VALUE"];
					$arItem["PRICE_DISCOUNT_DIFFERENCE_VALUE"] = $arPrice["DISCOUNT_DIFF"];
					$arItem["PRICE_DISCOUNT_DIFFERENCE"] = $arPrice["PRINT_DISCOUNT_DIFF"];
					$arItem["PRICE_DISCOUNT_PERCENT"] = $arPrice["DISCOUNT_DIFF_PERCENT"];
					break;
				}
			}

			$arItem["CAN_BUY"] = CIBlockPriceTools::CanBuy($arItem["IBLOCK_ID"], $arResult["PRICES"], $arItem);
		}

		if (defined('BX_COMP_MANAGED_CACHE'))
		{
			$taggedCache = Application::getInstance()->getTaggedCache();
			$taggedCache->registerTag('iblock_id_'.$arResult['ELEMENT_IBLOCK_ID']);
			if ($arResult['ELEMENT_IBLOCK_ID'] != $arResult['PRODUCT_IBLOCK_ID'])
				$taggedCache->registerTag('iblock_id_'.$arResult['PRODUCT_IBLOCK_ID']);
		}

		if ($arItem["ID"] == $elementID)
		{
			$arResult["ELEMENT"] = $arItem;

			$arResult["SET_ITEMS"]["PRICE"] += $arItem["PRICE_DISCOUNT_VALUE"];
			$arResult["SET_ITEMS"]["OLD_PRICE"] += $arItem["PRICE_VALUE"];
			$arResult["SET_ITEMS"]["PRICE_DISCOUNT_DIFFERENCE"] += $arItem["PRICE_DISCOUNT_DIFFERENCE_VALUE"];
		}
		elseif (isset($arSetItemsDefaultID[$arItem['ID']]))
		{
			if ($arItem['CATALOG_AVAILABLE'] != 'Y')
			{
				$this->AbortResultCache();
				return;
			}
			$arItem["SORT"] = $arSetItemsDefaultID[$arItem["ID"]];
			$arResult["SET_ITEMS"]["DEFAULT"][] = $arItem;
			if ($arParams['CONVERT_CURRENCY'] == 'Y')
			{
				$arResult["SET_ITEMS"]["PRICE"] += $arItem["PRICE_DISCOUNT_VALUE"];
				$arResult["SET_ITEMS"]["OLD_PRICE"] += $arItem["PRICE_VALUE"];
				$arResult["SET_ITEMS"]["PRICE_DISCOUNT_DIFFERENCE"] += $arItem["PRICE_DISCOUNT_DIFFERENCE_VALUE"];
			}
		}
		else
		{
			if ($arItem['CATALOG_AVAILABLE'] == 'Y')
			{
				$arItem["SORT"] = $arSetItemsOtherID[$arItem["ID"]];
				$arResult["SET_ITEMS"]["OTHER"][] = $arItem;
			}
		}
	}
	Type\Collection::sortByColumn($arResult["SET_ITEMS"]["DEFAULT"], array('SORT' => SORT_ASC));
	Type\Collection::sortByColumn($arResult["SET_ITEMS"]["OTHER"], array('SORT' => SORT_ASC));

	if ($arParams['CONVERT_CURRENCY'] == 'N')
	{
		//convert all prices to main element currency
		foreach($arResult["SET_ITEMS"]["DEFAULT"] as $key=>$arItem)
		{
			$arResult["SET_ITEMS"]["DEFAULT"][$key]["PRICE_CONVERT_DISCOUNT_VALUE"] = CCurrencyRates::ConvertCurrency($arItem['PRICE_DISCOUNT_VALUE'], $arItem["PRICE_CURRENCY"] , $arResult["ELEMENT"]["PRICE_CURRENCY"]);
			$arResult["SET_ITEMS"]["PRICE"] += $arResult["SET_ITEMS"]["DEFAULT"][$key]["PRICE_CONVERT_DISCOUNT_VALUE"];
			$arResult["SET_ITEMS"]["DEFAULT"][$key]["PRICE_CONVERT_VALUE"] = CCurrencyRates::ConvertCurrency($arItem["PRICE_VALUE"], $arItem["PRICE_CURRENCY"] , $arResult["ELEMENT"]["PRICE_CURRENCY"]);
			$arResult["SET_ITEMS"]["OLD_PRICE"] += $arResult["SET_ITEMS"]["DEFAULT"][$key]["PRICE_CONVERT_VALUE"];
			$arResult["SET_ITEMS"]["DEFAULT"][$key]["PRICE_CONVERT_DISCOUNT_DIFFERENCE_VALUE"] = CCurrencyRates::ConvertCurrency($arItem["PRICE_DISCOUNT_DIFFERENCE_VALUE"], $arItem["PRICE_CURRENCY"] , $arResult["ELEMENT"]["PRICE_CURRENCY"]);
			$arResult["SET_ITEMS"]["PRICE_DISCOUNT_DIFFERENCE"] += $arResult["SET_ITEMS"]["DEFAULT"][$key]["PRICE_CONVERT_DISCOUNT_DIFFERENCE_VALUE"];
		}
		foreach($arResult["SET_ITEMS"]["OTHER"] as $key=>$arItem)
		{
			$arResult["SET_ITEMS"]["OTHER"][$key]["PRICE_CONVERT_DISCOUNT_VALUE"] = CCurrencyRates::ConvertCurrency($arItem['PRICE_DISCOUNT_VALUE'], $arItem["PRICE_CURRENCY"] , $arResult["ELEMENT"]["PRICE_CURRENCY"]);
			$arResult["SET_ITEMS"]["OTHER"][$key]["PRICE_CONVERT_VALUE"] = CCurrencyRates::ConvertCurrency($arItem["PRICE_VALUE"], $arItem["PRICE_CURRENCY"] , $arResult["ELEMENT"]["PRICE_CURRENCY"]);
			$arResult["SET_ITEMS"]["OTHER"][$key]["PRICE_CONVERT_DISCOUNT_DIFFERENCE_VALUE"] = CCurrencyRates::ConvertCurrency($arItem["PRICE_DISCOUNT_DIFFERENCE_VALUE"], $arItem["PRICE_CURRENCY"] , $arResult["ELEMENT"]["PRICE_CURRENCY"]);
		}
	}

	if ($arResult["SET_ITEMS"]["OLD_PRICE"] && $arResult["SET_ITEMS"]["OLD_PRICE"] != $arResult["SET_ITEMS"]["PRICE"])
		$arResult["SET_ITEMS"]["OLD_PRICE"] = CCurrencyLang::CurrencyFormat($arResult["SET_ITEMS"]["OLD_PRICE"], $arResult["ELEMENT"]["PRICE_CURRENCY"], true);
	else
		$arResult["SET_ITEMS"]["OLD_PRICE"] = 0;

	if ($arResult["SET_ITEMS"]["PRICE"])
		$arResult["SET_ITEMS"]["PRICE"] = CCurrencyLang::CurrencyFormat($arResult["SET_ITEMS"]["PRICE"], $arResult["ELEMENT"]["PRICE_CURRENCY"], true);

	if ($arResult["SET_ITEMS"]["PRICE_DISCOUNT_DIFFERENCE"])
		$arResult["SET_ITEMS"]["PRICE_DISCOUNT_DIFFERENCE"] = CCurrencyLang::CurrencyFormat($arResult["SET_ITEMS"]["PRICE_DISCOUNT_DIFFERENCE"], $arResult["ELEMENT"]["PRICE_CURRENCY"], true);


	$this->SetResultCacheKeys(array());
	$this->IncludeComponentTemplate();
}
?>