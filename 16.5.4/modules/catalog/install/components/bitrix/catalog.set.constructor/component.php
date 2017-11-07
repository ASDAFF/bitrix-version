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

use Bitrix\Main,
	Bitrix\Main\Loader,
	Bitrix\Main\Application,
	Bitrix\Iblock,
	Bitrix\Catalog;

if (!CBXFeatures::IsFeatureEnabled('CatCompleteSet'))
	return;

$arParams['IBLOCK_ID'] = isset($arParams['IBLOCK_ID']) ? (int)$arParams['IBLOCK_ID'] : 0;
if ($arParams['IBLOCK_ID'] <= 0)
	return;

if (!isset($arParams["BASKET_URL"]))
	$arParams["BASKET_URL"] = '/personal/cart/';
if ('' == trim($arParams["BASKET_URL"]))
	$arParams["BASKET_URL"] = '/personal/cart/';

if (!isset($arParams["CACHE_TIME"]))
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

if($this->startResultCache(false, array($elementID, ($arParams["CACHE_GROUPS"]==="N"? false: $USER->GetGroups()))))
{
	if (!Loader::includeModule('catalog'))
	{
		ShowError(GetMessage("CATALOG_MODULE_NOT_INSTALLED"));
		$this->abortResultCache();
		return;
	}
	$isProductHaveSet = CCatalogProductSet::isProductHaveSet($elementID, CCatalogProductSet::TYPE_GROUP);
	$product = false;
	if (!$isProductHaveSet)
	{
		$product = CCatalogSKU::GetProductInfo($elementID, $arParams['IBLOCK_ID']);
		if (!empty($product))
		{
			$isProductHaveSet = CCatalogProductSet::isProductHaveSet($product['ID'], CCatalogProductSet::TYPE_GROUP);
			if (!$isProductHaveSet)
				$product = false;
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
		$arParams['CONVERT_CURRENCY'] = 'N';
	elseif ($arParams['CONVERT_CURRENCY'] == 'N')
		$arParams['CURRENCY_ID'] = '';

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

	$currentSet = false;
	$productLink = array();
	$allSets = CCatalogProductSet::getAllSetsByProduct($arResult['PRODUCT_ID'], CCatalogProductSet::TYPE_GROUP);
	foreach ($allSets as &$oneSet)
	{
		if ($oneSet['ACTIVE'] == 'Y')
		{
			$currentSet = $oneSet;
			break;
		}
	}
	unset($oneSet, $allSets);
	if (empty($currentSet))
	{
		$this->AbortResultCache();
		return;
	}
	Main\Type\Collection::sortByColumn($currentSet['ITEMS'], array('SORT' => SORT_ASC), '', null, true);
	$arSetItemsID = array($arResult['ELEMENT_ID']);
	foreach ($currentSet['ITEMS'] as $index => $item)
	{
		$arSetItemsID[] = $item['ITEM_ID'];
		if (!isset($productLink[$item['ITEM_ID']]))
			$productLink[$item['ITEM_ID']] = array();
		$productLink[$item['ITEM_ID']][] = $index;
	}
	unset($index, $item);

	$countSetDefaultItems = 0;

	$select = array(
		'ID',
		'NAME',
		'CODE',
		'IBLOCK_ID',
		'IBLOCK_SECTION_ID',
		'DETAIL_PAGE_URL',
		'PREVIEW_PICTURE',
		'DETAIL_PICTURE',
		'PREVIEW_TEXT',
		'CATALOG_AVAILABLE',
		'CATALOG_MEASURE'
	);
	$arResult["PRICES"] = CIBlockPriceTools::GetCatalogPrices($arResult['PRODUCT_IBLOCK_ID'], $arParams["PRICE_CODE"]);
	foreach($arResult["PRICES"] as $key => $value)
	{
		if (!$value['CAN_VIEW'] && !$value['CAN_BUY'])
			continue;
		$select[] = $value["SELECT"];
	}

	$arResult["SET_ITEMS"]["DEFAULT"] = array();
	$arResult["SET_ITEMS"]["OTHER"] = array();
	$arResult["SET_ITEMS"]["PRICE"] = 0;
	$arResult["SET_ITEMS"]["OLD_PRICE"] = 0;
	$arResult["SET_ITEMS"]["PRICE_DISCOUNT_DIFFERENCE"] = 0;

	$arResult['ITEMS_RATIO'] = array_fill_keys($arSetItemsID, 1);
	$ratioResult = Catalog\ProductTable::getCurrentRatioWithMeasure($arSetItemsID);
	foreach ($ratioResult as $ratioProduct => $ratioData)
		$arResult['ITEMS_RATIO'][$ratioProduct] = $ratioData['RATIO'];
	unset($ratioProduct, $ratioData);

	$tagIblockList = array();
	$tagIblockList[$arResult['PRODUCT_IBLOCK_ID']] = $arResult['PRODUCT_IBLOCK_ID'];
	$tagIblockList[$arResult['ELEMENT_IBLOCK_ID']] = $arResult['ELEMENT_IBLOCK_ID'];
	$tagCurrencyList = array();

	$found = false;
	$itemsList = array();
	$emptyOffers = array();
	$itemsIterator = CIBlockElement::GetList(
		array(),
		array('ID' => $arSetItemsID),
		false,
		false,
		$select
	);
	while ($item = $itemsIterator->GetNext())
	{
		$found = true;
		$item['ID'] = (int)$item['ID'];
		$item['IBLOCK_ID'] = (int)$item['IBLOCK_ID'];
		$tagIblockList[$item['IBLOCK_ID']] = $item['IBLOCK_ID'];
		$itemsList[$item['ID']] = $item;
		if (empty($item['PREVIEW_PICTURE']) && empty($item['DETAIL_PICTURE']))
			$emptyOffers[] = $item['ID'];
	}
	unset($select, $item, $itemsIterator);
	if (!$found)
	{
		$this->abortResultCache();
		return;
	}
	if (!empty($emptyOffers))
	{
		$parents = CCatalogSku::getProductList($emptyOffers);
		if (!empty($parents) && is_array($parents))
		{
			$offerLinks = array();
			foreach ($parents as $offerId => $parentData)
			{
				$parentId = $parentData['ID'];
				if (!isset($offerLinks[$parentId]))
					$offerLinks[$parentId] = array();
				if (!isset($itemsList[$offerId]))
					continue;
				$offerLinks[$parentId][] = &$itemsList[$offerId];
			}
			unset($offerId, $parentData);
			$itemsIterator = Iblock\ElementTable::getList(array(
				'select' => array('ID', 'PREVIEW_PICTURE', 'DETAIL_PICTURE'),
				'filter' => array(
					'@ID' => array_keys($offerLinks),
					array(
						'LOGIC' => 'OR',
						'!=PREVIEW_PICTURE' => null,
						'!=DETAIL_PICTURE' => null
					)
				)
			));
			while ($item = $itemsIterator->fetch())
			{
				$id = (int)$item['ID'];
				if (empty($offerLinks[$id]))
					continue;
				foreach (array_keys($offerLinks[$id]) as $offerIndex)
				{
					$offerLinks[$id][$offerIndex]['PREVIEW_PICTURE'] = $item['PREVIEW_PICTURE'];
					$offerLinks[$id][$offerIndex]['DETAIL_PICTURE'] = $item['DETAIL_PICTURE'];
				}
				unset($offerIndex);
			}
			unset($item, $itemsIterator);
			unset($offerLinks);
		}
		unset($parents);
	}
	unset($emptyOffers);

	foreach ($itemsList as $item)
	{
		$priceList = CIBlockPriceTools::GetItemPrices(
			$item['IBLOCK_ID'],
			$arResult['PRICES'],
			$item,
			$arParams['PRICE_VAT_INCLUDE'],
			$arConvertParams
		);
		if (empty($priceList))
			continue;
		//TODO: after iblock 15.5.9 change this code to CIBlockPriceTools::getMinPriceFromList
		foreach($priceList as &$price)
		{
			$tagCurrencyList[$price['CURRENCY']] = $price['CURRENCY'];
			if (isset($price['ORIG_CURRENCY']))
				$tagCurrencyList[$price['ORIG_CURRENCY']] = $price['ORIG_CURRENCY'];
			if ($price['MIN_PRICE'] == "Y")
			{
				$item['PRICE_CURRENCY'] = $price['CURRENCY'];
				$item['PRICE_DISCOUNT_VALUE'] = $price['DISCOUNT_VALUE'];
				$item['PRICE_PRINT_DISCOUNT_VALUE'] = $price['PRINT_DISCOUNT_VALUE'];
				$item['PRICE_VALUE'] = $price['VALUE'];
				$item['PRICE_PRINT_VALUE'] = $price['PRINT_VALUE'];
				$item['PRICE_DISCOUNT_DIFFERENCE_VALUE'] = $price['DISCOUNT_DIFF'];
				$item['PRICE_DISCOUNT_DIFFERENCE'] = $price['PRINT_DISCOUNT_DIFF'];
				$item['PRICE_DISCOUNT_PERCENT'] = $price['DISCOUNT_DIFF_PERCENT'];
				break;
			}
		}
		unset($price, $priceList);
		//TODO: end
		$item['CAN_BUY'] = CIBlockPriceTools::CanBuy(
			$item['IBLOCK_ID'],
			$arResult['PRICES'],
			$item
		);

		if (!empty($productLink[$item['ID']]))
		{
			foreach ($productLink[$item['ID']] as &$index)
				$currentSet['ITEMS'][$index]['ITEM_DATA'] = $item;
			unset($index);
		}
		elseif ($item['ID'] == $arResult['ELEMENT_ID'])
		{
			$currentSet['ITEM_DATA'] = $item;
		}
	}
	unset($item, $itemsList);
	if (empty($currentSet['ITEM_DATA']))
	{
		$this->abortResultCache();
		return;
	}
	$defaultMeasure = CCatalogMeasure::getDefaultMeasure(true, true);
	$arResult['ELEMENT'] = $currentSet['ITEM_DATA'];
	$arResult['ELEMENT']['SET_QUANTITY'] = 1;
	$arResult['ELEMENT']['MEASURE_RATIO'] = $arResult['ITEMS_RATIO'][$arResult['ELEMENT']['ID']];
	$arResult['ELEMENT']['MEASURE'] = (
		!empty($ratioResult[$arResult['ELEMENT']['ID']]['MEASURE'])
		? $ratioResult[$arResult['ELEMENT']['ID']]['MEASURE']
		: $defaultMeasure
	);
	$arResult['ELEMENT']['BASKET_QUANTITY'] = $arResult['ELEMENT']['MEASURE_RATIO'];
	$arResult['SET_ITEMS']['PRICE'] = $currentSet['ITEM_DATA']['PRICE_DISCOUNT_VALUE'];
	$arResult['SET_ITEMS']['OLD_PRICE'] = $currentSet['ITEM_DATA']['PRICE_VALUE'];
	$arResult['SET_ITEMS']['PRICE_DISCOUNT_DIFFERENCE'] = $currentSet['ITEM_DATA']['PRICE_DISCOUNT_DIFFERENCE_VALUE'];
	$arResult['BASKET_QUANTITY'] = array(
		$arResult['ELEMENT']['ID'] => $arResult['ELEMENT']['BASKET_QUANTITY']
	);

	$defaultCurrency = $arResult['ELEMENT']['PRICE_CURRENCY'];
	$found = false;
	$resort = false;
	foreach ($currentSet['ITEMS'] as &$setItem)
	{
		if (!isset($setItem['ITEM_DATA']))
			continue;

		$setItem['ITEM_DATA']['SET_QUANTITY'] = (empty($setItem['QUANTITY']) ? 1 : $setItem['QUANTITY']);
		$setItem['ITEM_DATA']['MEASURE_RATIO'] = $arResult['ITEMS_RATIO'][$setItem['ITEM_DATA']['ID']];
		$setItem['ITEM_DATA']['MEASURE'] = (
			!empty($ratioResult[$setItem['ITEM_DATA']['ID']]['MEASURE'])
			? $ratioResult[$setItem['ITEM_DATA']['ID']]['MEASURE']
			: $defaultMeasure
		);
		$setItem['ITEM_DATA']['BASKET_QUANTITY'] = $setItem['ITEM_DATA']['SET_QUANTITY']*$setItem['ITEM_DATA']['MEASURE_RATIO'];
		$arResult['BASKET_QUANTITY'][$setItem['ITEM_DATA']['ID']] = $setItem['ITEM_DATA']['BASKET_QUANTITY'];
		$setItem['ITEM_DATA']['SET_SORT'] = $setItem['SORT'];
		if ($arParams['CONVERT_CURRENCY'] == 'N' && $setItem['ITEM_DATA']['PRICE_CURRENCY'] != $defaultCurrency)
		{
			$setItem['ITEM_DATA']['PRICE_CONVERT_DISCOUNT_VALUE'] = CCurrencyRates::ConvertCurrency($setItem['ITEM_DATA']['PRICE_DISCOUNT_VALUE'], $setItem['ITEM_DATA']['PRICE_CURRENCY'], $defaultCurrency);
			$setItem['ITEM_DATA']['PRICE_CONVERT_VALUE'] = CCurrencyRates::ConvertCurrency($setItem['ITEM_DATA']["PRICE_VALUE"], $setItem['ITEM_DATA']['PRICE_CURRENCY'], $defaultCurrency);
			$setItem['ITEM_DATA']['PRICE_CONVERT_DISCOUNT_DIFFERENCE_VALUE'] = CCurrencyRates::ConvertCurrency($setItem['ITEM_DATA']['PRICE_DISCOUNT_DIFFERENCE_VALUE'], $setItem['ITEM_DATA']['PRICE_CURRENCY'], $defaultCurrency);
			$setItem['ITEM_DATA']['PRICE_CURRENCY'] = $defaultCurrency;
		}
		if ($setItem['ITEM_DATA']['CAN_BUY'] && $countSetDefaultItems < 3)
		{
			$arResult['SET_ITEMS']['DEFAULT'][] = $setItem['ITEM_DATA'];
			$arResult['SET_ITEMS']['PRICE'] += $setItem['ITEM_DATA']['PRICE_DISCOUNT_VALUE']*$setItem['ITEM_DATA']['BASKET_QUANTITY'];
			$arResult['SET_ITEMS']['OLD_PRICE'] += $setItem['ITEM_DATA']['PRICE_VALUE']*$setItem['ITEM_DATA']['BASKET_QUANTITY'];
			$arResult['SET_ITEMS']['PRICE_DISCOUNT_DIFFERENCE'] += $setItem['ITEM_DATA']['PRICE_DISCOUNT_DIFFERENCE_VALUE']*$setItem['ITEM_DATA']['BASKET_QUANTITY'];
			$countSetDefaultItems++;
		}
		else
		{
			if (!$setItem['ITEM_DATA']['CAN_BUY'])
				$resort = true;
			$arResult['SET_ITEMS']['OTHER'][] = $setItem['ITEM_DATA'];
		}
		$found = true;
	}
	unset($setItem, $currentSet);
	if (!$found || empty($arResult['SET_ITEMS']['DEFAULT']))
	{
		$this->AbortResultCache();
		return;
	}
	unset($found);
	if ($resort)
		Main\Type\Collection::sortByColumn($arResult['SET_ITEMS']['OTHER'], array('CAN_BUY' => SORT_DESC, 'SET_SORT' => SORT_ASC));
	unset($resort);

	if (defined('BX_COMP_MANAGED_CACHE') && (!empty($tagIblockList) || !empty($tagCurrencyList)))
	{
		$taggedCache = Application::getInstance()->getTaggedCache();
		if (!empty($tagIblockList))
		{
			foreach ($tagIblockList as &$iblock)
				$taggedCache->registerTag('iblock_id_'.$iblock);
			unset($iblock);
		}
		if (!empty($tagCurrencyList))
		{
			foreach ($tagCurrencyList as &$currency)
				$taggedCache->registerTag('currency_id_'.$currency);
			unset($currency);
		}
	}

	if ($arResult["SET_ITEMS"]["OLD_PRICE"] && $arResult["SET_ITEMS"]["OLD_PRICE"] != $arResult["SET_ITEMS"]["PRICE"])
		$arResult["SET_ITEMS"]["OLD_PRICE"] = CCurrencyLang::CurrencyFormat($arResult["SET_ITEMS"]["OLD_PRICE"], $defaultCurrency, true);
	else
		$arResult["SET_ITEMS"]["OLD_PRICE"] = 0;
	if ($arResult["SET_ITEMS"]["PRICE"])
		$arResult["SET_ITEMS"]["PRICE"] = CCurrencyLang::CurrencyFormat($arResult["SET_ITEMS"]["PRICE"], $defaultCurrency, true);
	if ($arResult["SET_ITEMS"]["PRICE_DISCOUNT_DIFFERENCE"])
		$arResult["SET_ITEMS"]["PRICE_DISCOUNT_DIFFERENCE"] = CCurrencyLang::CurrencyFormat($arResult["SET_ITEMS"]["PRICE_DISCOUNT_DIFFERENCE"], $defaultCurrency, true);

	$currencyFormat = CCurrencyLang::GetFormatDescription($defaultCurrency);
	$arResult['CURRENCIES'] = array(
		array(
			'CURRENCY' => $defaultCurrency,
			'FORMAT' => array(
				'FORMAT_STRING' => $currencyFormat['FORMAT_STRING'],
				'DEC_POINT' => $currencyFormat['DEC_POINT'],
				'THOUSANDS_SEP' => $currencyFormat['THOUSANDS_SEP'],
				'DECIMALS' => $currencyFormat['DECIMALS'],
				'THOUSANDS_VARIANT' => $currencyFormat['THOUSANDS_VARIANT'],
				'HIDE_ZERO' => $currencyFormat['HIDE_ZERO']
			)
		)
	);
	unset($currencyFormat);

	$this->SetResultCacheKeys(array());
	$this->IncludeComponentTemplate();
}