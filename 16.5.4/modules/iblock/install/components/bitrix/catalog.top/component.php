<?
use Bitrix\Main\Loader,
	Bitrix\Currency,
	Bitrix\Catalog,
	Bitrix\Iblock;

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


/*************************************************************************
	Processing of received parameters
*************************************************************************/
if(!isset($arParams["CACHE_TIME"]))
	$arParams["CACHE_TIME"] = 36000000;

unset($arParams["IBLOCK_TYPE"]); //was used only for IBLOCK_ID setup with Editor
$arParams["IBLOCK_ID"] = intval($arParams["IBLOCK_ID"]);

if (empty($arParams["ELEMENT_SORT_FIELD"]))
	$arParams["ELEMENT_SORT_FIELD"] = "sort";
if (!preg_match('/^(asc|desc|nulls)(,asc|,desc|,nulls){0,1}$/i', $arParams["ELEMENT_SORT_ORDER"]))
	$arParams["ELEMENT_SORT_ORDER"] = "asc";
if (empty($arParams["ELEMENT_SORT_FIELD2"]))
	$arParams["ELEMENT_SORT_FIELD2"] = "id";
if (!preg_match('/^(asc|desc|nulls)(,asc|,desc|,nulls){0,1}$/i', $arParams["ELEMENT_SORT_ORDER2"]))
	$arParams["ELEMENT_SORT_ORDER2"] = "desc";

$arParams["SECTION_URL"]=trim($arParams["SECTION_URL"]);
$arParams["DETAIL_URL"]=trim($arParams["DETAIL_URL"]);
$arParams["BASKET_URL"]=trim($arParams["BASKET_URL"]);
if($arParams["BASKET_URL"] === '')
	$arParams["BASKET_URL"] = "/personal/basket.php";

$arParams["ACTION_VARIABLE"]=trim($arParams["ACTION_VARIABLE"]);
if($arParams["ACTION_VARIABLE"] === '' || !preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/", $arParams["ACTION_VARIABLE"]))
	$arParams["ACTION_VARIABLE"] = "action";

$arParams["PRODUCT_ID_VARIABLE"]=trim($arParams["PRODUCT_ID_VARIABLE"]);
if($arParams["PRODUCT_ID_VARIABLE"] === '' || !preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/", $arParams["PRODUCT_ID_VARIABLE"]))
	$arParams["PRODUCT_ID_VARIABLE"] = "id";

$arParams["PRODUCT_QUANTITY_VARIABLE"]=trim($arParams["PRODUCT_QUANTITY_VARIABLE"]);
if($arParams["PRODUCT_QUANTITY_VARIABLE"] === '' || !preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/", $arParams["PRODUCT_QUANTITY_VARIABLE"]))
	$arParams["PRODUCT_QUANTITY_VARIABLE"] = "quantity";

$arParams["PRODUCT_PROPS_VARIABLE"]=trim($arParams["PRODUCT_PROPS_VARIABLE"]);
if($arParams["PRODUCT_PROPS_VARIABLE"] === '' || !preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/", $arParams["PRODUCT_PROPS_VARIABLE"]))
	$arParams["PRODUCT_PROPS_VARIABLE"] = "prop";

$arParams["SECTION_ID_VARIABLE"]=trim($arParams["SECTION_ID_VARIABLE"]);
if($arParams["SECTION_ID_VARIABLE"] === '' || !preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/", $arParams["SECTION_ID_VARIABLE"]))
	$arParams["SECTION_ID_VARIABLE"] = "SECTION_ID";

$arParams["SET_TITLE"] = $arParams["SET_TITLE"]!="N";

$arParams["DISPLAY_COMPARE"] = (isset($arParams["DISPLAY_COMPARE"]) && $arParams["DISPLAY_COMPARE"] == "Y");
$arParams['COMPARE_PATH'] = (isset($arParams['COMPARE_PATH']) ? trim($arParams['COMPARE_PATH']) : '');

$arParams["ELEMENT_COUNT"] = intval($arParams["ELEMENT_COUNT"]);
if($arParams["ELEMENT_COUNT"]<=0)
	$arParams["ELEMENT_COUNT"]=9;
$arParams["LINE_ELEMENT_COUNT"] = intval($arParams["LINE_ELEMENT_COUNT"]);
if($arParams["LINE_ELEMENT_COUNT"]<=0)
	$arParams["LINE_ELEMENT_COUNT"]=3;

if(!isset($arParams["PROPERTY_CODE"]) || !is_array($arParams["PROPERTY_CODE"]))
	$arParams["PROPERTY_CODE"] = array();
foreach($arParams["PROPERTY_CODE"] as $k=>$v)
	if($v==="")
		unset($arParams["PROPERTY_CODE"][$k]);
if(!isset($arParams["PRICE_CODE"]) || !is_array($arParams["PRICE_CODE"]))
	$arParams["PRICE_CODE"] = array();

$arParams["USE_PRICE_COUNT"] = $arParams["USE_PRICE_COUNT"]=="Y";
$arParams["SHOW_PRICE_COUNT"] = (isset($arParams["SHOW_PRICE_COUNT"]) ? (int)$arParams["SHOW_PRICE_COUNT"] : 1);
if($arParams["SHOW_PRICE_COUNT"]<=0)
	$arParams["SHOW_PRICE_COUNT"]=1;
$arParams["USE_PRODUCT_QUANTITY"] = $arParams["USE_PRODUCT_QUANTITY"]==="Y";

$arParams['ADD_PROPERTIES_TO_BASKET'] = (isset($arParams['ADD_PROPERTIES_TO_BASKET']) && $arParams['ADD_PROPERTIES_TO_BASKET'] === 'N' ? 'N' : 'Y');
if ('N' == $arParams['ADD_PROPERTIES_TO_BASKET'])
{
	$arParams["PRODUCT_PROPERTIES"] = array();
	$arParams["OFFERS_CART_PROPERTIES"] = array();
}
$arParams['PARTIAL_PRODUCT_PROPERTIES'] = (isset($arParams['PARTIAL_PRODUCT_PROPERTIES']) && $arParams['PARTIAL_PRODUCT_PROPERTIES'] === 'Y' ? 'Y' : 'N');
if(!isset($arParams["PRODUCT_PROPERTIES"]) || !is_array($arParams["PRODUCT_PROPERTIES"]))
	$arParams["PRODUCT_PROPERTIES"] = array();
foreach($arParams["PRODUCT_PROPERTIES"] as $k=>$v)
	if($v==="")
		unset($arParams["PRODUCT_PROPERTIES"][$k]);

if (!isset($arParams["OFFERS_CART_PROPERTIES"]) || !is_array($arParams["OFFERS_CART_PROPERTIES"]))
	$arParams["OFFERS_CART_PROPERTIES"] = array();
foreach($arParams["OFFERS_CART_PROPERTIES"] as $i => $pid)
	if ($pid === "")
		unset($arParams["OFFERS_CART_PROPERTIES"][$i]);

if (empty($arParams["OFFERS_SORT_FIELD"]))
	$arParams["OFFERS_SORT_FIELD"] = "sort";
if (!preg_match('/^(asc|desc|nulls)(,asc|,desc|,nulls){0,1}$/i', $arParams["OFFERS_SORT_ORDER"]))
	$arParams["OFFERS_SORT_ORDER"] = "asc";
if (empty($arParams["OFFERS_SORT_FIELD2"]))
	$arParams["OFFERS_SORT_FIELD2"] = "id";
if (!preg_match('/^(asc|desc|nulls)(,asc|,desc|,nulls){0,1}$/i', $arParams["OFFERS_SORT_ORDER2"]))
	$arParams["OFFERS_SORT_ORDER2"] = "desc";

$arParams["PRICE_VAT_INCLUDE"] = $arParams["PRICE_VAT_INCLUDE"] !== "N";

$arrFilter=array();
if(strlen($arParams["FILTER_NAME"])>0)
{
	global ${$arParams["FILTER_NAME"]};
	if (is_array(${$arParams["FILTER_NAME"]}))
		$arrFilter = ${$arParams["FILTER_NAME"]};
}

$arParams["CACHE_FILTER"]=$arParams["CACHE_FILTER"]=="Y";
if(!$arParams["CACHE_FILTER"] && count($arrFilter)>0)
	$arParams["CACHE_TIME"] = 0;

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

//$arParams['HIDE_NOT_AVAILABLE'] = (!isset($arParams['HIDE_NOT_AVAILABLE']) || 'Y' != $arParams['HIDE_NOT_AVAILABLE'] ? 'N' : 'Y');
if (!isset($arParams['HIDE_NOT_AVAILABLE']))
	$arParams['HIDE_NOT_AVAILABLE'] = 'N';
if ($arParams['HIDE_NOT_AVAILABLE'] != 'Y' && $arParams['HIDE_NOT_AVAILABLE'] != 'L')
	$arParams['HIDE_NOT_AVAILABLE'] = 'N';

$arParams["OFFERS_LIMIT"] = intval($arParams["OFFERS_LIMIT"]);
if (0 > $arParams["OFFERS_LIMIT"])
	$arParams["OFFERS_LIMIT"] = 0;

$arParams['CACHE_GROUPS'] = trim($arParams['CACHE_GROUPS']);
if ('N' != $arParams['CACHE_GROUPS'])
	$arParams['CACHE_GROUPS'] = 'Y';

/*************************************************************************
			Processing of the Buy link
*************************************************************************/
$strError = '';
$successfulAdd = true;

if(isset($_REQUEST[$arParams["ACTION_VARIABLE"]]) && isset($_REQUEST[$arParams["PRODUCT_ID_VARIABLE"]]))
{
	if(isset($_REQUEST[$arParams["ACTION_VARIABLE"]."BUY"]))
		$action = "BUY";
	elseif(isset($_REQUEST[$arParams["ACTION_VARIABLE"]."ADD2BASKET"]))
		$action = "ADD2BASKET";
	else
		$action = strtoupper($_REQUEST[$arParams["ACTION_VARIABLE"]]);

	$productID = intval($_REQUEST[$arParams["PRODUCT_ID_VARIABLE"]]);
	if (($action == "ADD2BASKET" || $action == "BUY") && $productID > 0)
	{
		if (Loader::includeModule("sale") && Loader::includeModule("catalog"))
		{
			$addByAjax = isset($_REQUEST['ajax_basket']) && 'Y' == $_REQUEST['ajax_basket'];
			if ($addByAjax)
				CUtil::JSPostUnescape();
			$QUANTITY = 0;
			$product_properties = array();
			$intProductIBlockID = intval(CIBlockElement::GetIBlockByID($productID));
			if (0 < $intProductIBlockID)
			{
				if ($arParams['ADD_PROPERTIES_TO_BASKET'] == 'Y')
				{
					if ($intProductIBlockID == $arParams["IBLOCK_ID"])
					{
						if (!empty($arParams["PRODUCT_PROPERTIES"]))
						{
							if (
								isset($_REQUEST[$arParams["PRODUCT_PROPS_VARIABLE"]])
								&& is_array($_REQUEST[$arParams["PRODUCT_PROPS_VARIABLE"]])
							)
							{
								$product_properties = CIBlockPriceTools::CheckProductProperties(
									$arParams["IBLOCK_ID"],
									$productID,
									$arParams["PRODUCT_PROPERTIES"],
									$_REQUEST[$arParams["PRODUCT_PROPS_VARIABLE"]],
									$arParams['PARTIAL_PRODUCT_PROPERTIES'] == 'Y'
								);
								if (!is_array($product_properties))
								{
									$strError = GetMessage("CATALOG_PARTIAL_BASKET_PROPERTIES_ERROR");
									$successfulAdd = false;
								}
							}
							else
							{
								$strError = GetMessage("CATALOG_EMPTY_BASKET_PROPERTIES_ERROR");
								$successfulAdd = false;
							}
						}
					}
					else
					{
						$skuAddProps = (isset($_REQUEST['basket_props']) && !empty($_REQUEST['basket_props']) ? $_REQUEST['basket_props'] : '');
						if (!empty($arParams["OFFERS_CART_PROPERTIES"]) || !empty($skuAddProps))
						{
							$product_properties = CIBlockPriceTools::GetOfferProperties(
								$productID,
								$arParams["IBLOCK_ID"],
								$arParams["OFFERS_CART_PROPERTIES"],
								$skuAddProps
							);
						}
					}
				}
				if ($arParams["USE_PRODUCT_QUANTITY"])
				{
					if (isset($_REQUEST[$arParams["PRODUCT_QUANTITY_VARIABLE"]]))
					{
						$QUANTITY = doubleval($_REQUEST[$arParams["PRODUCT_QUANTITY_VARIABLE"]]);
					}
				}
				if (0 >= $QUANTITY)
				{
					$rsRatios = CCatalogMeasureRatio::getList(
						array(),
						array('PRODUCT_ID' => $productID),
						false,
						false,
						array('PRODUCT_ID', 'RATIO')
					);
					if ($arRatio = $rsRatios->Fetch())
					{
						$intRatio = intval($arRatio['RATIO']);
						$dblRatio = doubleval($arRatio['RATIO']);
						$QUANTITY = ($dblRatio > $intRatio ? $dblRatio : $intRatio);
					}
				}
				if (0 >= $QUANTITY)
					$QUANTITY = 1;
			}
			else
			{
				$strError = GetMessage('CATALOG_PRODUCT_NOT_FOUND');
				$successfulAdd = false;
			}

			if ($successfulAdd)
			{
				if(!Add2BasketByProductID($productID, $QUANTITY, array(), $product_properties))
				{
					if ($ex = $APPLICATION->GetException())
						$strError = $ex->GetString();
					else
						$strError = GetMessage("CATALOG_ERROR2BASKET");
					$successfulAdd = false;
				}
			}

			if ($addByAjax)
			{
				if ($successfulAdd)
				{
					$addResult = array('STATUS' => 'OK', 'MESSAGE' => GetMessage('CATALOG_SUCCESSFUL_ADD_TO_BASKET'));
				}
				else
				{
					$addResult = array('STATUS' => 'ERROR', 'MESSAGE' => $strError);
				}
				$APPLICATION->RestartBuffer();
				echo CUtil::PhpToJSObject($addResult);
				die();
			}
			else
			{
				if ($successfulAdd)
				{
					$pathRedirect = (
					$action == "BUY"
						? $arParams["BASKET_URL"]
						: $APPLICATION->GetCurPageParam("", array(
							$arParams["PRODUCT_ID_VARIABLE"],
							$arParams["ACTION_VARIABLE"],
							$arParams['PRODUCT_QUANTITY_VARIABLE'],
							$arParams['PRODUCT_PROPS_VARIABLE']
						))
					);
					LocalRedirect($pathRedirect);
				}
			}
		}
	}
}
if (!$successfulAdd)
{
	ShowError($strError);
	return;
}

/*************************************************************************
			Work with cache
*************************************************************************/
if($this->startResultCache(false, array($arrFilter, ($arParams["CACHE_GROUPS"]==="N"? false: $USER->GetGroups()))))
{
	if (!Loader::includeModule("iblock"))
	{
		$this->abortResultCache();
		ShowError(GetMessage("IBLOCK_MODULE_NOT_INSTALLED"));
		return;
	}

	$arResultModules = array(
		'iblock' => true,
		'catalog' => false,
		'currency' => false
	);

	global $CACHE_MANAGER;
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
			$arResultModules['currency'] = true;
			$currency = Currency\CurrencyTable::getList(array(
				'select' => array('CURRENCY'),
				'filter' => array('=CURRENCY' => $arParams['CURRENCY_ID'])
			))->fetch();
			if (!empty($currency))
			{
				$arParams['CURRENCY_ID'] = $currency['CURRENCY'];
				$arConvertParams['CURRENCY_ID'] = $currency['CURRENCY'];
			}
			else
			{
				$arParams['CONVERT_CURRENCY'] = 'N';
				$arParams['CURRENCY_ID'] = '';
			}
			unset($currency);
		}
	}
	$arResult['CONVERT_CURRENCY'] = $arConvertParams;

	$bIBlockCatalog = false;
	$arCatalog = false;
	$boolNeedCatalogCache = false;
	$bCatalog = Loader::includeModule('catalog');
	if ($bCatalog)
	{
		$arResultModules['catalog'] = true;
		$arResultModules['currency'] = true;
		$arCatalog = CCatalogSKU::GetInfoByIBlock($arParams["IBLOCK_ID"]);
		if (!empty($arCatalog) && is_array($arCatalog))
		{
			$bIBlockCatalog = $arCatalog['CATALOG_TYPE'] != CCatalogSKU::TYPE_PRODUCT;
			$boolNeedCatalogCache = true;
		}
	}
	$arResult['CATALOG'] = $arCatalog;
	//This function returns array with prices description and access rights
	//in case catalog module n/a prices get values from element properties
	$arResult["PRICES"] = CIBlockPriceTools::GetCatalogPrices($arParams["IBLOCK_ID"], $arParams["PRICE_CODE"]);
	$arResult['PRICES_ALLOW'] = CIBlockPriceTools::GetAllowCatalogPrices($arResult["PRICES"]);

	if ($bCatalog && $boolNeedCatalogCache && !empty($arResult['PRICES_ALLOW']))
		$boolNeedCatalogCache = CIBlockPriceTools::SetCatalogDiscountCache($arResult['PRICES_ALLOW'], $USER->GetUserGroupArray());

	/************************************
			Elements
	************************************/
	//SELECT
	$arSelect = array(
		"ID",
		"IBLOCK_ID",
		"CODE",
		"XML_ID",
		"NAME",
		"ACTIVE",
		"DATE_ACTIVE_FROM",
		"DATE_ACTIVE_TO",
		"SORT",
		"PREVIEW_TEXT",
		"PREVIEW_TEXT_TYPE",
		"DETAIL_TEXT",
		"DETAIL_TEXT_TYPE",
		"DATE_CREATE",
		"CREATED_BY",
		"TIMESTAMP_X",
		"MODIFIED_BY",
		"TAGS",
		"IBLOCK_SECTION_ID",
		"DETAIL_PAGE_URL",
		"DETAIL_PICTURE",
		"PREVIEW_PICTURE",
	);
	//WHERE
	$arrFilter["ACTIVE"] = "Y";
	if($arParams["IBLOCK_ID"] > 0)
		$arrFilter["IBLOCK_ID"] = $arParams["IBLOCK_ID"];
	$arrFilter["IBLOCK_LID"] = SITE_ID;
	$arrFilter["IBLOCK_ACTIVE"] = "Y";
	$arrFilter["ACTIVE_DATE"] = "Y";
	$arrFilter["ACTIVE"] = "Y";
	$arrFilter["CHECK_PERMISSIONS"] = "Y";
	if ($bIBlockCatalog && 'Y' == $arParams['HIDE_NOT_AVAILABLE'])
		$arrFilter['CATALOG_AVAILABLE'] = 'Y';

	//ORDER BY

	$arSort = array();
	if ($bIBlockCatalog && $arParams['HIDE_NOT_AVAILABLE'] == 'L')
		$arSort['CATALOG_AVAILABLE'] = 'desc,nulls';
	if (!isset($arSort['CATALOG_AVAILABLE']) || $arParams["ELEMENT_SORT_FIELD"] != 'CATALOG_AVAILABLE')
		$arSort[$arParams["ELEMENT_SORT_FIELD"]] = $arParams["ELEMENT_SORT_ORDER"];
	if (!isset($arSort['CATALOG_AVAILABLE']) || $arParams["ELEMENT_SORT_FIELD2"] != 'CATALOG_AVAILABLE')
		$arSort[$arParams["ELEMENT_SORT_FIELD2"]] = $arParams["ELEMENT_SORT_ORDER2"];

	//PRICES
	$arPriceTypeID = array();
	if (!empty($arResult["PRICES"]))
	{
		if (!$arParams["USE_PRICE_COUNT"])
		{
			foreach ($arResult["PRICES"] as &$value)
			{
				if (!$value['CAN_VIEW'] && !$value['CAN_BUY'])
					continue;
				$arSelect[] = $value["SELECT"];
				$arrFilter["CATALOG_SHOP_QUANTITY_".$value["ID"]] = $arParams["SHOW_PRICE_COUNT"];
			}
			unset($value);
		}
		else
		{
			foreach ($arResult["PRICES"] as &$value)
			{
				if (!$value['CAN_VIEW'] && !$value['CAN_BUY'])
					continue;
				$arPriceTypeID[] = $value["ID"];
			}
			unset($value);
		}
	}

	$arDefaultMeasure = array();
	if ($bIBlockCatalog)
		$arDefaultMeasure = CCatalogMeasure::getDefaultMeasure(true, true);
	$currencyList = array();

	$bGetPropertyCodes = !empty($arParams["PROPERTY_CODE"]);
	$bGetProductProperties = !empty($arParams["PRODUCT_PROPERTIES"]);
	$bGetProperties = $bGetPropertyCodes || $bGetProductProperties;
	$propertyList = array();
	if ($bGetProperties)
	{
		$selectProperties = array_fill_keys($arParams['PROPERTY_CODE'], true);
		$propertyIterator = Iblock\PropertyTable::getList(array(
				'select' => array('ID', 'CODE'),
				'filter' => array('=IBLOCK_ID' => $arParams['IBLOCK_ID'], '=ACTIVE' => 'Y'),
				'order' => array('SORT' => 'ASC', 'ID' => 'ASC')
		));
		while ($property = $propertyIterator->fetch())
		{
			$code = (string)$property['CODE'];
			if ($code == '')
				$code = $property['ID'];
			if (!isset($selectProperties[$code]))
				continue;
			$propertyList[] = $code;
			unset($code);
		}
		unset($property, $propertyIterator);
		unset($selectProperties);
	}

	$arResult["ITEMS"] = array();
	$arMeasureMap = array();
	$intKey = 0;
	$arElementLink = array();
	$rsElements = CIBlockElement::GetList($arSort, $arrFilter, false, array("nTopCount" => $arParams["ELEMENT_COUNT"]), $arSelect);
	$rsElements->SetUrlTemplates($arParams["DETAIL_URL"]);

	while($arItem = $rsElements->GetNext())
	{
		$arItem['ID'] = intval($arItem['ID']);

		$arItem['ACTIVE_FROM'] = $arItem['DATE_ACTIVE_FROM'];
		$arItem['ACTIVE_TO'] = $arItem['DATE_ACTIVE_TO'];

		$arButtons = CIBlock::GetPanelButtons(
			$arItem["IBLOCK_ID"],
			$arItem["ID"],
			$arItem["IBLOCK_SECTION_ID"],
			array("SECTION_BUTTONS"=>false, "SESSID"=>false, "CATALOG"=>true)
		);
		$arItem["EDIT_LINK"] = $arButtons["edit"]["edit_element"]["ACTION_URL"];
		$arItem["DELETE_LINK"] = $arButtons["edit"]["delete_element"]["ACTION_URL"];

		$ipropValues = new \Bitrix\Iblock\InheritedProperty\ElementValues($arItem["IBLOCK_ID"], $arItem["ID"]);
		$arItem["IPROPERTY_VALUES"] = $ipropValues->getValues();

		Iblock\Component\Tools::getFieldImageData(
			$arItem,
			array('PREVIEW_PICTURE', 'DETAIL_PICTURE'),
			Iblock\Component\Tools::IPROPERTY_ENTITY_ELEMENT,
			'IPROPERTY_VALUES'
		);

		$arItem["PROPERTIES"] = array();
		$arItem["DISPLAY_PROPERTIES"] = array();
		$arItem["PRODUCT_PROPERTIES"] = array();
		$arItem['PRODUCT_PROPERTIES_FILL'] = array();

		if ($bIBlockCatalog)
		{
			if (!isset($arItem["CATALOG_MEASURE_RATIO"]))
				$arItem["CATALOG_MEASURE_RATIO"] = 1;
			if (!isset($arItem['CATALOG_MEASURE']))
				$arItem['CATALOG_MEASURE'] = 0;
			$arItem['CATALOG_MEASURE'] = intval($arItem['CATALOG_MEASURE']);
			if (0 > $arItem['CATALOG_MEASURE'])
				$arItem['CATALOG_MEASURE'] = 0;
			if (!isset($arItem['CATALOG_MEASURE_NAME']))
				$arItem['CATALOG_MEASURE_NAME'] = '';

			$arItem['CATALOG_MEASURE_NAME'] = $arDefaultMeasure['SYMBOL_RUS'];
			$arItem['~CATALOG_MEASURE_NAME'] = $arDefaultMeasure['~SYMBOL_RUS'];
			if (0 < $arItem['CATALOG_MEASURE'])
			{
				if (!isset($arMeasureMap[$arItem['CATALOG_MEASURE']]))
					$arMeasureMap[$arItem['CATALOG_MEASURE']] = array();
				$arMeasureMap[$arItem['CATALOG_MEASURE']][] = $intKey;
			}
		}
		$arResult["ITEMS"][$intKey] = $arItem;
		$arResult["ELEMENTS"][$intKey] = $arItem["ID"];
		$arElementLink[$arItem['ID']] = &$arResult["ITEMS"][$intKey];
		$intKey++;
	}
	$arResult['MODULES'] = $arResultModules;

	if (!empty($arResult["ELEMENTS"]) && ($bGetProperties || ($bCatalog && $boolNeedCatalogCache)))
	{
		$arPropFilter = array(
			'ID' => $arResult["ELEMENTS"],
			'IBLOCK_ID' => $arParams['IBLOCK_ID']
		);
		CIBlockElement::GetPropertyValuesArray($arElementLink, $arParams["IBLOCK_ID"], $arPropFilter);

		foreach ($arResult["ITEMS"] as &$arItem)
		{
			if ($bCatalog && $boolNeedCatalogCache)
				CCatalogDiscount::SetProductPropertiesCache($arItem['ID'], $arItem["PROPERTIES"]);

			if ($bGetProperties)
			{
				if (!empty($propertyList))
				{
					foreach ($propertyList as &$pid)
					{
						if (!isset($arItem["PROPERTIES"][$pid]))
							continue;
						$prop = &$arItem["PROPERTIES"][$pid];
						$boolArr = is_array($prop["VALUE"]);
						if (
								($boolArr && !empty($prop["VALUE"]))
								|| (!$boolArr && strlen($prop["VALUE"]) > 0)
						)
						{
							$arItem["DISPLAY_PROPERTIES"][$pid] = CIBlockFormatProperties::GetDisplayValue($arItem, $prop, "catalog_out");
						}
						unset($prop);
					}
					unset($pid);
				}

				if ($bGetProductProperties)
				{
					$arItem["PRODUCT_PROPERTIES"] = CIBlockPriceTools::GetProductProperties(
						$arParams["IBLOCK_ID"],
						$arItem["ID"],
						$arParams["PRODUCT_PROPERTIES"],
						$arItem["PROPERTIES"]
					);
					if (!empty($arItem["PRODUCT_PROPERTIES"]))
						$arItem['PRODUCT_PROPERTIES_FILL'] = CIBlockPriceTools::getFillProductProperties($arItem['PRODUCT_PROPERTIES']);
				}
			}
		}
		unset($arItem);
	}

	if ($bIBlockCatalog)
	{
		if (!empty($arResult["ELEMENTS"]))
		{
			$rsRatios = CCatalogMeasureRatio::getList(
				array(),
				array('PRODUCT_ID' => $arResult["ELEMENTS"]),
				false,
				false,
				array('PRODUCT_ID', 'RATIO')
			);
			while ($arRatio = $rsRatios->Fetch())
			{
				$arRatio['PRODUCT_ID'] = intval($arRatio['PRODUCT_ID']);
				if (isset($arElementLink[$arRatio['PRODUCT_ID']]))
				{
					$intRatio = intval($arRatio['RATIO']);
					$dblRatio = doubleval($arRatio['RATIO']);
					$mxRatio = ($dblRatio > $intRatio ? $dblRatio : $intRatio);
					if (CATALOG_VALUE_EPSILON > abs($mxRatio))
						$mxRatio = 1;
					elseif (0 > $mxRatio)
						$mxRatio = 1;
					$arElementLink[$arRatio['PRODUCT_ID']]['CATALOG_MEASURE_RATIO'] = $mxRatio;
				}
			}
		}
		if (!empty($arMeasureMap))
		{
			$rsMeasures = CCatalogMeasure::getList(
				array(),
				array('@ID' => array_keys($arMeasureMap)),
				false,
				false,
				array('ID', 'SYMBOL_RUS')
			);
			while ($arMeasure = $rsMeasures->GetNext())
			{
				$arMeasure['ID'] = intval($arMeasure['ID']);
				if (isset($arMeasureMap[$arMeasure['ID']]) && !empty($arMeasureMap[$arMeasure['ID']]))
				{
					foreach ($arMeasureMap[$arMeasure['ID']] as &$intOneKey)
					{
						$arResult['ITEMS'][$intOneKey]['CATALOG_MEASURE_NAME'] = $arMeasure['SYMBOL_RUS'];
						$arResult['ITEMS'][$intOneKey]['~CATALOG_MEASURE_NAME'] = $arMeasure['~SYMBOL_RUS'];
					}
					unset($intOneKey);
				}
			}
		}
	}
	if ($bCatalog && $boolNeedCatalogCache && !empty($arResult["ELEMENTS"]))
	{
		CCatalogDiscount::SetProductSectionsCache($arResult["ELEMENTS"]);
		CCatalogDiscount::SetDiscountProductCache($arResult["ELEMENTS"], array('IBLOCK_ID' => $arParams["IBLOCK_ID"], 'GET_BY_ID' => 'Y'));
	}

	$currentPath = CHTTP::urlDeleteParams(
		$APPLICATION->GetCurPageParam(),
		array($arParams['PRODUCT_ID_VARIABLE'], $arParams['ACTION_VARIABLE'], ''),
		array('delete_system_params' => true)
	);
	$currentPath .= (stripos($currentPath, '?') === false ? '?' : '&');
	if ($arParams['COMPARE_PATH'] == '')
	{
		$comparePath = $currentPath;
	}
	else
	{
		$comparePath = CHTTP::urlDeleteParams(
			$arParams['COMPARE_PATH'],
			array($arParams['PRODUCT_ID_VARIABLE'], $arParams['ACTION_VARIABLE'], ''),
			array('delete_system_params' => true)
		);
		$comparePath .= (stripos($comparePath, '?') === false ? '?' : '&');
	}
	$arParams['COMPARE_PATH'] = $comparePath.$arParams['ACTION_VARIABLE'].'=COMPARE';

	$arResult['~BUY_URL_TEMPLATE'] = $currentPath.$arParams["ACTION_VARIABLE"]."=BUY&".$arParams["PRODUCT_ID_VARIABLE"]."=#ID#";
	$arResult['BUY_URL_TEMPLATE'] = htmlspecialcharsbx($arResult['~BUY_URL_TEMPLATE']);
	$arResult['~ADD_URL_TEMPLATE'] = $currentPath.$arParams["ACTION_VARIABLE"]."=ADD2BASKET&".$arParams["PRODUCT_ID_VARIABLE"]."=#ID#";
	$arResult['ADD_URL_TEMPLATE'] = htmlspecialcharsbx($arResult['~ADD_URL_TEMPLATE']);
	$arResult['~SUBSCRIBE_URL_TEMPLATE'] = $currentPath.$arParams["ACTION_VARIABLE"]."=SUBSCRIBE_PRODUCT&".$arParams["PRODUCT_ID_VARIABLE"]."=#ID#";
	$arResult['SUBSCRIBE_URL_TEMPLATE'] = htmlspecialcharsbx($arResult['~SUBSCRIBE_URL_TEMPLATE']);
	$arResult['~COMPARE_URL_TEMPLATE'] = $comparePath.$arParams["ACTION_VARIABLE"]."=ADD_TO_COMPARE_LIST&".$arParams["PRODUCT_ID_VARIABLE"]."=#ID#";
	$arResult['COMPARE_URL_TEMPLATE'] = htmlspecialcharsbx($arResult['~COMPARE_URL_TEMPLATE']);
	unset($comparePath, $currentPath);

	foreach ($arResult["ITEMS"] as &$arItem)
	{
		$arItem["PRICES"] = array();
		$arItem["PRICE_MATRIX"] = false;
		$arItem['MIN_PRICE'] = false;
		if($arParams["USE_PRICE_COUNT"])
		{
			if ($bCatalog)
			{
				$arItem["PRICE_MATRIX"] = CatalogGetPriceTableEx($arItem["ID"], 0, $arPriceTypeID, 'Y', $arConvertParams);
				if (isset($arItem["PRICE_MATRIX"]["COLS"]) && is_array($arItem["PRICE_MATRIX"]["COLS"]))
				{
					foreach($arItem["PRICE_MATRIX"]["COLS"] as $keyColumn=>$arColumn)
						$arItem["PRICE_MATRIX"]["COLS"][$keyColumn]["NAME_LANG"] = htmlspecialcharsbx($arColumn["NAME_LANG"]);
				}
			}
		}
		else
		{
			$arItem["PRICES"] = CIBlockPriceTools::GetItemPrices($arParams["IBLOCK_ID"], $arResult["PRICES"], $arItem, $arParams['PRICE_VAT_INCLUDE'], $arConvertParams);
			if (!empty($arItem['PRICES']))
				$arItem['MIN_PRICE'] = CIBlockPriceTools::getMinPriceFromList($arItem['PRICES']);
		}
		$arItem["CAN_BUY"] = CIBlockPriceTools::CanBuy($arParams["IBLOCK_ID"], $arResult["PRICES"], $arItem);

		$arItem['~BUY_URL'] = str_replace('#ID#', $arItem["ID"], $arResult['~BUY_URL_TEMPLATE']);
		$arItem['BUY_URL'] = str_replace('#ID#', $arItem["ID"], $arResult['BUY_URL_TEMPLATE']);
		$arItem['~ADD_URL'] = str_replace('#ID#', $arItem["ID"], $arResult['~ADD_URL_TEMPLATE']);
		$arItem['ADD_URL'] = str_replace('#ID#', $arItem["ID"], $arResult['ADD_URL_TEMPLATE']);
		$arItem['~SUBSCRIBE_URL'] = str_replace('#ID#', $arItem["ID"], $arResult['~SUBSCRIBE_URL_TEMPLATE']);
		$arItem['SUBSCRIBE_URL'] = str_replace('#ID#', $arItem["ID"], $arResult['SUBSCRIBE_URL_TEMPLATE']);
		if ($arParams['DISPLAY_COMPARE'])
		{
			$arItem['~COMPARE_URL'] = str_replace('#ID#', $arItem["ID"], $arResult['~COMPARE_URL_TEMPLATE']);
			$arItem['COMPARE_URL'] = str_replace('#ID#', $arItem["ID"], $arResult['COMPARE_URL_TEMPLATE']);
		}

		if ('Y' == $arParams['CONVERT_CURRENCY'])
		{
			if ($arParams["USE_PRICE_COUNT"])
			{
				if (is_array($arItem["PRICE_MATRIX"]) && !empty($arItem["PRICE_MATRIX"]))
				{
					if (!empty($arItem["PRICE_MATRIX"]['CURRENCY_LIST']) && is_array($arItem["PRICE_MATRIX"]['CURRENCY_LIST']))
						$currencyList = array_merge($arItem['PRICE_MATRIX']['CURRENCY_LIST'], $currencyList);
				}
			}
			else
			{
				if (!empty($arItem["PRICES"]))
				{
					foreach ($arItem["PRICES"] as &$arOnePrices)
					{
						if (isset($arOnePrices['ORIG_CURRENCY']))
							$currencyList[$arOnePrices['ORIG_CURRENCY']] = $arOnePrices['ORIG_CURRENCY'];
					}
					unset($arOnePrices);
				}
			}
		}
	}
	if (isset($arItem))
		unset($arItem);

	if(!isset($arParams["OFFERS_FIELD_CODE"]))
		$arParams["OFFERS_FIELD_CODE"] = array();
	elseif (!is_array($arParams["OFFERS_FIELD_CODE"]))
		$arParams["OFFERS_FIELD_CODE"] = array($arParams["OFFERS_FIELD_CODE"]);
	foreach($arParams["OFFERS_FIELD_CODE"] as $key => $value)
		if($value === "")
			unset($arParams["OFFERS_FIELD_CODE"][$key]);

	if(!isset($arParams["OFFERS_PROPERTY_CODE"]))
		$arParams["OFFERS_PROPERTY_CODE"] = array();
	elseif (!is_array($arParams["OFFERS_PROPERTY_CODE"]))
		$arParams["OFFERS_PROPERTY_CODE"] = array($arParams["OFFERS_PROPERTY_CODE"]);
	foreach($arParams["OFFERS_PROPERTY_CODE"] as $key => $value)
		if($value === "")
			unset($arParams["OFFERS_PROPERTY_CODE"][$key]);

	if(
		$bCatalog
		&& !empty($arResult["ELEMENTS"])
		&& (
			!empty($arParams["OFFERS_FIELD_CODE"])
			|| !empty($arParams["OFFERS_PROPERTY_CODE"])
		)
	)
	{
		$offersFilter = array(
			'IBLOCK_ID' => $arParams['IBLOCK_ID'],
			'HIDE_NOT_AVAILABLE' => $arParams['HIDE_NOT_AVAILABLE']
		);
		if (!$arParams["USE_PRICE_COUNT"])
			$offersFilter['SHOW_PRICE_COUNT'] = $arParams['SHOW_PRICE_COUNT'];

		$arOffers = CIBlockPriceTools::GetOffersArray(
			$offersFilter,
			$arResult["ELEMENTS"],
			array(
				$arParams["OFFERS_SORT_FIELD"] => $arParams["OFFERS_SORT_ORDER"],
				$arParams["OFFERS_SORT_FIELD2"] => $arParams["OFFERS_SORT_ORDER2"],
			),
			$arParams["OFFERS_FIELD_CODE"],
			$arParams["OFFERS_PROPERTY_CODE"],
			$arParams["OFFERS_LIMIT"],
			$arResult["PRICES"],
			$arParams['PRICE_VAT_INCLUDE'],
			$arConvertParams
		);
		if (!empty($arOffers))
		{
			foreach ($arResult["ELEMENTS"] as $id)
			{
				$arElementLink[$id]['OFFERS'] = array();
				$arElementLink[$id]['OFFER_ID_SELECTED'] = 0;
			}
			unset($id);

			$uniqueSortHash = array();
			foreach($arOffers as &$arOffer)
			{
				$linkElement = $arOffer['LINK_ELEMENT_ID'];
				if (!isset($arElementLink[$linkElement]))
					continue;

				if (!isset($uniqueSortHash[$linkElement]))
					$uniqueSortHash[$linkElement] = array();
				$uniqueSortHash[$linkElement][$arOffer['SORT_HASH']] = true;
				unset($arOffer['SORT_HASH']);

				$arOffer['~BUY_URL'] = str_replace('#ID#', $arOffer["ID"], $arResult['~BUY_URL_TEMPLATE']);
				$arOffer['BUY_URL'] = str_replace('#ID#', $arOffer["ID"], $arResult['BUY_URL_TEMPLATE']);
				$arOffer['~ADD_URL'] = str_replace('#ID#', $arOffer["ID"], $arResult['~ADD_URL_TEMPLATE']);
				$arOffer['ADD_URL'] = str_replace('#ID#', $arOffer["ID"], $arResult['ADD_URL_TEMPLATE']);
				if ($arParams['DISPLAY_COMPARE'])
				{
					$arOffer['~COMPARE_URL'] = str_replace('#ID#', $arOffer["ID"], $arResult['~COMPARE_URL_TEMPLATE']);
					$arOffer['COMPARE_URL'] = str_replace('#ID#', $arOffer["ID"], $arResult['COMPARE_URL_TEMPLATE']);
				}
				$arOffer['~SUBSCRIBE_URL'] = str_replace('#ID#', $arOffer["ID"], $arResult['~SUBSCRIBE_URL_TEMPLATE']);
				$arOffer['SUBSCRIBE_URL'] = str_replace('#ID#', $arOffer["ID"], $arResult['SUBSCRIBE_URL_TEMPLATE']);

				$arElementLink[$linkElement]['OFFERS'][] = $arOffer;
				if ($arElementLink[$linkElement]['OFFER_ID_SELECTED'] == 0 && $arOffer['CAN_BUY'])
					$arElementLink[$linkElement]['OFFER_ID_SELECTED'] = $arOffer['ID'];

				if ('Y' == $arParams['CONVERT_CURRENCY'] && !empty($arOffer['PRICES']))
				{
					foreach ($arOffer['PRICES'] as &$arOnePrices)
					{
						if (isset($arOnePrices['ORIG_CURRENCY']))
							$currencyList[$arOnePrices['ORIG_CURRENCY']] = $arOnePrices['ORIG_CURRENCY'];
					}
					unset($arOnePrices);
				}
			}
			unset($arOffer);

			foreach ($arElementLink as &$item)
			{
				if ($item['OFFER_ID_SELECTED'] == 0)
					continue;
				if (count($uniqueSortHash[$item['ID']]) < 2)
					$item['OFFER_ID_SELECTED'] = 0;
			}
			unset($item);
			unset($uniqueSortHash);
		}
		unset($arOffers);
	}

	if (
		'Y' == $arParams['CONVERT_CURRENCY']
		&& !empty($currencyList)
		&& defined("BX_COMP_MANAGED_CACHE")
	)
	{
		$currencyList[$arConvertParams['CURRENCY_ID']] = $arConvertParams['CURRENCY_ID'];
		foreach ($currencyList as &$oneCurrency)
			$CACHE_MANAGER->RegisterTag('currency_id_'.$oneCurrency);
		unset($oneCurrency);
	}
	unset($currencyList);

	$this->SetResultCacheKeys(array(
	));
	$this->IncludeComponentTemplate();

	if ($bCatalog && $boolNeedCatalogCache)
	{
		CCatalogDiscount::ClearDiscountCache(array(
			'PRODUCT' => true,
			'SECTIONS' => true,
			'PROPERTIES' => true
		));
	}
}