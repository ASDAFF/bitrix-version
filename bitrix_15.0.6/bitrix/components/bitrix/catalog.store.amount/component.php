<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;

global $USER_FIELD_MANAGER, $APPLICATION;

if (!function_exists("getStringCatalogStoreAmount"))
{
	function getStringCatalogStoreAmount($amount, $minAmount)
	{
		$amount = (float)$amount;
		$minAmount = (float)$minAmount;
		$message = GetMessage("NOT_MUCH_GOOD");
		if ($amount <= 0)
			$message = GetMessage("ABSENT");
		elseif ($amount >= $minAmount)
			$message = GetMessage("LOT_OF_GOOD");
		return $message;
	}
}
if (!Loader::includeModule('catalog'))
{
	ShowError(GetMessage('CATALOG_MODULE_NOT_INSTALL'));
	return;
}

if (!isset($arParams['CACHE_TIME']))
	$arParams['CACHE_TIME'] = 360000;

$arParams['ELEMENT_ID']     = (int)(isset($arParams['ELEMENT_ID']) ? $arParams['ELEMENT_ID'] : 0);
$arParams['ELEMENT_CODE']   = (isset($arParams['ELEMENT_CODE']) ? $arParams['ELEMENT_CODE'] : '');
$arParams['MAIN_TITLE']     = trim($arParams['MAIN_TITLE']);
$arParams['STORE_PATH']     = trim($arParams['STORE_PATH']);
$arParams['USE_MIN_AMOUNT'] = (isset($arParams['USE_MIN_AMOUNT']) && $arParams['USE_MIN_AMOUNT'] == 'N' ? 'N' : 'Y');
$arParams['MIN_AMOUNT']     = (float)(isset($arParams['MIN_AMOUNT']) ? $arParams['MIN_AMOUNT'] : 0);
if (!isset($arParams['FIELDS']))
	$arParams['FIELDS'] = array();
if (!is_array($arParams['FIELDS']))
	$arParams['FIELDS'] = array($arParams['FIELDS']);
if (!isset($arParams['USER_FIELDS']))
	$arParams['USER_FIELDS'] = array();
if (!is_array($arParams['USER_FIELDS']))
	$arParams['USER_FIELDS'] = array($arParams['USER_FIELDS']);
if (!isset($arParams['STORES']))
	$arParams['STORES'] = array();
if (!is_array($arParams['STORES']))
	$arParams['STORES'] = array($arParams['STORES']);

if (isset($arParams['USE_STORE_PHONE']) && $arParams['USE_STORE_PHONE'] == 'Y')
	$arParams['FIELDS'][] = "PHONE";
if (isset($arParams['SCHEDULE']) && $arParams['SCHEDULE'] == 'Y')
	$arParams['FIELDS'][] = "SCHEDULE";
$arParams['SHOW_EMPTY_STORE'] = (isset($arParams['SHOW_EMPTY_STORE']) && $arParams['SHOW_EMPTY_STORE'] == 'N' ? 'N' : 'Y');

if ($arParams["ELEMENT_ID"] <= 0 && $arParams["ELEMENT_CODE"] != '')
{
	$res = CIBlockElement::GetList(
		array(),
		array('=CODE' => $arParams['ELEMENT_CODE']),
		false,
		false,
		array('ID')
	);
	if ($elementId = $res->Fetch())
		$arParams["ELEMENT_ID"] = $elementId['ID'];
}

$siteId             = \Bitrix\Main\Application::getInstance()->getContext()->getSite();
$quantity           = 0;
$productId          = 0;
$iblockId           = 0;
$arResult["IS_SKU"] = true;
$lang               = \Bitrix\Main\Application::getInstance()->getContext()->getLanguage();

if ($arParams["ELEMENT_ID"] <= 0)
{
	ShowError(GetMessage("PRODUCT_NOT_EXIST"));
	return;
}

if ($this->StartResultCache())
{
	$arResult['STORES'] = array();
	$isProductExistSKU = CCatalogSKU::IsExistOffers($arParams['ELEMENT_ID'], $iblockId);
	if ($isProductExistSKU)
	{
		$res = CIBlockElement::GetList(
			array(),
			array('ID' => $arParams['ELEMENT_ID']),
			false,
			false,
			array('ID', 'IBLOCK_ID')
		);
		if ($productInfo = $res->Fetch())
		{
			$productId  = $productInfo['ID'];
			$iblockId   = $productInfo['IBLOCK_ID'];
		}

		$skuInfo = CCatalogSKU::GetInfoByProductIBlock($iblockId);
		$skuIterator = CIBlockElement::GetList(
			array('ID' => 'DESC'),
			array('IBLOCK_ID' => $skuInfo['IBLOCK_ID'], 'PROPERTY_'.$skuInfo['SKU_PROPERTY_ID'] => $productId),
			false,
			false,
			array('ID')
		);

		$productSku = array();
		while ($sku = $skuIterator->Fetch())
		{
			$amount = array();
			$sum = 0;
			$filter = array('PRODUCT_ID' => $sku['ID']);
			if (!empty($arParams['STORES']))
				$filter['STORE_ID'] = $arParams['STORES'];
			$storeIterator = CCatalogStoreProduct::GetList(array(), $filter, false, false, array('ID', 'STORE_ID', 'AMOUNT'));
			while ($store = $storeIterator->Fetch())
			{
				if ($arParams["SHOW_GENERAL_STORE_INFORMATION"] == "Y")
					$sum += $store['AMOUNT'];
				else
				{
					$amount[$store['STORE_ID']] = 0;
					$amount[$store['STORE_ID']] += $store['AMOUNT'];
				}
			}
			unset($store, $storeIterator, $filter);

			if ($arParams["SHOW_GENERAL_STORE_INFORMATION"] == "Y")
				$productSku[$sku['ID']][] = $sum;
			else
				$productSku[$sku['ID']] = $amount;
			$arParams["ELEMENT_ID"] = $sku['ID'];
		}
	}

	$res = CCatalogProduct::GetList(
		array(),
		array("ID" => $arParams["ELEMENT_ID"]),
		false,
		false,
		array("TYPE", "QUANTITY", "ID")
	);
	$data = $res->Fetch();

	if ($data["TYPE"] == CCatalogProduct::TYPE_SET)
	{
		$arParams["SHOW_GENERAL_STORE_INFORMATION"] = "Y";
		$arParams["~SHOW_GENERAL_STORE_INFORMATION"] = "Y";
		$quantity = $data["QUANTITY"];
		$arResult["IS_SKU"] = false;
	}
	else
	{
		if (in_array('COORDINATES', $arParams['FIELDS']))
			$arParams['FIELDS'] = array_merge($arParams['FIELDS'], array('GPS_N', 'GPS_S'));

		$select = array_merge(
			array("ID", "ACTIVE", "PRODUCT_AMOUNT", "TITLE", "TYPE"),
			$arParams["FIELDS"],
			$arParams["USER_FIELDS"]
		);

		foreach ($select as $key => $value)
			if (empty($value) || $value == 'COORDINATES')
				unset($select[$key]);

		$filter = array(
			"ACTIVE" => "Y",
			"PRODUCT_ID" => $arParams["ELEMENT_ID"],
			"+SITE_ID" => $siteId,
			"ISSUING_CENTER" => 'Y'
		);

		if (!empty($arParams["STORES"]))
			$filter["ID"] = $arParams["STORES"];

		$rsProps = CCatalogStore::GetList(
			array('TITLE' => 'ASC', 'ID' => 'ASC'),
			$filter,
			false,
			false,
			$select
		);

		while ($prop = $rsProps->GetNext())
		{
			$amount = (is_null($prop["PRODUCT_AMOUNT"])) ? 0 : $prop["PRODUCT_AMOUNT"];

			if ($arParams["SHOW_GENERAL_STORE_INFORMATION"] == "Y")
			{
				$quantity += $amount;
				continue;
			}
			$storeURL = CComponentEngine::MakePathFromTemplate($arParams["STORE_PATH"], array("store_id" => $prop["ID"]));

			if ($prop["TITLE"] == '' && $prop["ADDRESS"] != '')
				$storeName = $prop["ADDRESS"];
			elseif ($prop["ADDRESS"] == '' && $prop["TITLE"] != '')
				$storeName = $prop["TITLE"];
			else
				$storeName = $prop["TITLE"] . " (" . $prop["ADDRESS"] . ")";

			if (isset($prop["PHONE"]) && $prop["PHONE"] != '')
				$storePhone = $prop["PHONE"];
			else
				$storePhone = null;

			$storeSchedule = (isset($prop["SCHEDULE"]) && $prop["SCHEDULE"] != '') ? $prop["SCHEDULE"] : null;
			$storeEmail = (isset($prop["EMAIL"]) && $prop["EMAIL"] != '') ? $prop["EMAIL"] : null;
			$storeDescription = (isset($prop["DESCRIPTION"]) && $prop["DESCRIPTION"] != '') ? $prop["DESCRIPTION"] : null;
			$storeImageId = (isset($prop["IMAGE_ID"]) && $prop["IMAGE_ID"] != '') ? $prop["IMAGE_ID"] : null;

			if (isset($prop['GPS_N']) && isset($prop['GPS_S']) && $prop['GPS_N'] != '' && $prop['GPS_S'] != '')
				$storeCoordinates = array(
					'GPS_N' => $prop['GPS_N'],
					'GPS_S' => $prop['GPS_S']
				);
			else
				$storeCoordinates = null;

			if ($arParams["USE_MIN_AMOUNT"] == 'Y')
				$amount = getStringCatalogStoreAmount($amount, $arParams['MIN_AMOUNT']);

			$storeInformation = array(
				'ID' => $prop["ID"],
				'URL' => $storeURL,
				'TITLE' => $storeName,
				'PHONE' => $storePhone,
				'SCHEDULE' => $storeSchedule,
				'IMAGE_ID' => $storeImageId,
				'EMAIL' => $storeEmail,
				'COORDINATES' => $storeCoordinates,
				'DESCRIPTION' => $storeDescription,
				'AMOUNT' => $amount
			);

			$arResult["USER_FIELDS"] = $arParams["USER_FIELDS"];

			$userFields = $USER_FIELD_MANAGER->GetUserFields('CAT_STORE', 0, $lang);

			foreach ($arResult["USER_FIELDS"] as $userField)
			{
				if (!empty($userField) && !empty($prop[$userField]))
				{
					ob_start();
					$APPLICATION->IncludeComponent(
						"bitrix:system.field.view",
						$userFields[$userField]["USER_TYPE_ID"],
						array("arUserField" => array_merge($userFields[$userField], array('VALUE' => $prop[$userField]))),
						null,
						array("HIDE_ICONS"=>"Y")
					);

					$storeInformation["USER_FIELDS"][$userField] = array(
						'CONTENT'   => ob_get_contents(),
						'TITLE'     => (strlen($userFields[$userField]['LIST_COLUMN_LABEL']) > 0) ? $userFields[$userField]['LIST_COLUMN_LABEL'] : $userField
					);
					ob_end_clean();
				}
			}

			$arResult["STORES"][] = $storeInformation;
		}
	}

	if ($arParams["SHOW_GENERAL_STORE_INFORMATION"] == "Y")
		$arResult["STORES"][] = array(
			'ID'     => 0,
			'AMOUNT' => ($arParams["USE_MIN_AMOUNT"] == 'Y') ? getStringCatalogStoreAmount($quantity, $arParams['MIN_AMOUNT']) : $quantity
		);

	if ($arResult["IS_SKU"])
	{
		$strMainId = $this->GetEditAreaId($arParams['ELEMENT_ID']);
		$strObName = 'ob'.preg_replace("/[^a-zA-Z0-9_]/", "x", $strMainId);
		$arResult['JS']['SKU'] = $productSku;
		$arResult['JS']['ID'] = $strObName;
		$arResult['JS']['MESSAGES'] = array(
			'NOT_MUCH_GOOD' => GetMessage("NOT_MUCH_GOOD"),
			'ABSENT'        => GetMessage("ABSENT"),
			'LOT_OF_GOOD'   => GetMessage("LOT_OF_GOOD")
		);
		$arResult['JS']['SHOW_EMPTY_STORE'] = ($arParams['SHOW_EMPTY_STORE'] == "Y");
		$arResult['JS']["USE_MIN_AMOUNT"] = ($arParams["USE_MIN_AMOUNT"] == 'Y');
		$arResult['JS']["MIN_AMOUNT"] = $arParams["MIN_AMOUNT"];

		$arResult['JS']['STORES'] = array();
		if ($arParams["SHOW_GENERAL_STORE_INFORMATION"] == "Y")
			$arResult['JS']['STORES'][] = 0;
		elseif (!empty($arResult['STORES']))
			foreach ($arResult['STORES'] as $store)
				$arResult['JS']['STORES'][] = $store['ID'];
	}
	$this->IncludeComponentTemplate();
}