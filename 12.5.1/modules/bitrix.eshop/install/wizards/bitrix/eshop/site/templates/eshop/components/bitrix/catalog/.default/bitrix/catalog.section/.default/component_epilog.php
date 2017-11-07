<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/*if (!($APPLICATION->GetTitle()))
{
	if (!empty($arResult["SECTION_USER_FIELDS"]["UF_BROWSER_TITLE"]))
		$APPLICATION->SetTitle($arResult["SECTION_USER_FIELDS"]["UF_BROWSER_TITLE"]);
	else
		$APPLICATION->SetTitle($arResult["NAME"]);
	
	if (!empty($arResult["SECTION_USER_FIELDS"]["UF_TITLE_H1"]))
		$APPLICATION->SetPageProperty("ADDITIONAL_TITLE", $arResult["SECTION_USER_FIELDS"]["UF_TITLE_H1"]);
	else
		$APPLICATION->SetPageProperty("ADDITIONAL_TITLE", $arResult["NAME"]);
}
if (!empty($arResult["SECTION_USER_FIELDS"]["UF_KEYWORDS"]))
	$APPLICATION->SetPageProperty("keywords", $arResult["SECTION_USER_FIELDS"]["UF_KEYWORDS"]);
if (!empty($arResult["SECTION_USER_FIELDS"]["UF_META_DESCRIPTION"]))
	$APPLICATION->SetPageProperty("description", $arResult["SECTION_USER_FIELDS"]["UF_META_DESCRIPTION"]);   */
	
__IncludeLang($_SERVER["DOCUMENT_ROOT"].$templateFolder."/lang/".LANGUAGE_ID."/template.php");

if (count($arResult['IDS']) > 0 && CModule::IncludeModule('sale'))
{
	$arItemsInCompare = array();
	foreach ($arResult['IDS'] as $ID)
	{
		if (isset(
			$_SESSION[$arParams["COMPARE_NAME"]][$arParams["IBLOCK_ID"]]["ITEMS"][$ID]
		))
			$arItemsInCompare[] = $ID;

	}
	$dbBasketItems = CSaleBasket::GetList(
		array(
			"ID" => "ASC"
		),
		array(
			"FUSER_ID" => CSaleBasket::GetBasketUserID(),
			"LID" => SITE_ID,
			"ORDER_ID" => "NULL",
			),
		false,
		false,
		array()
	);

	$arPageItems = array();
	$arPageItemsDelay = array();
	$arPageItemsSubscribe = array();

	$notifyOption = COption::GetOptionString("sale", "subscribe_prod", "");
	$arNotify = unserialize($notifyOption);
	while ($arItem = $dbBasketItems->Fetch())
	{
		if (in_array($arItem['PRODUCT_ID'], $arResult['IDS']))
		{
			if($arItem["DELAY"] == "Y")
				$arPageItemsDelay[] = $arItem['PRODUCT_ID'];
			elseif ($arNotify[SITE_ID]['use'] == 'Y' && $arItem["SUBSCRIBE"] == "Y")
				$arPageItemsSubscribe[] = $arItem['PRODUCT_ID'];
			elseif($arItem["CAN_BUY"] == "N"  && $arItem["SUBSCRIBE"] == "N")
				$arPageItems[] = $arItem['PRODUCT_ID'];
		}
	}

	if (count($arPageItems) > 0 || count($arPageItemsDelay) > 0 || count($arPageItemsSubscribe) > 0)
	{
		echo '<script type="text/javascript">$(function(){'."\r\n";
		foreach ($arPageItems as $id)
		{
			echo "disableAddToCart('catalog_add2cart_link_".$id."', 'list', '".GetMessage("CATALOG_IN_CART")."');\r\n";
		}
		foreach ($arPageItemsDelay as $id)
		{
			echo "disableAddToCart('catalog_add2cart_link_".$id."', 'list', '".GetMessage("CATALOG_IN_CART_DELAY")."');\r\n";
		}
		foreach ($arPageItemsSubscribe as $id)
		{
			echo "disableAddToSubscribe('catalog_add2cart_link_".$id."', '".GetMessage("CATALOG_IN_SUBSCRIBE")."');\r\n";
		}
		echo '})</script>';
	}

	if (count($arItemsInCompare) > 0)
	{
		echo '<script type="text/javascript">$(function(){'."\r\n";
		foreach ($arItemsInCompare as $id)
		{
			echo "disableAddToCompare(BX('catalog_add2compare_link_".$id."'), 'list', '".GetMessage("CATALOG_IN_COMPARE")."', '".htmlspecialcharsback($arResult["DELETE_COMPARE_URLS"][$id])."');\r\n";
		}
		echo '})</script>';
	}
}

if (count($arResult['OFFERS_IDS']) > 0 && CModule::IncludeModule('sale'))
{
	$arItemsInCompare = array();
	foreach ($arResult['OFFERS_IDS'] as $elementID => $arOfferIDs)
	{
		$allOffersInCompare = true;
		foreach ($arOfferIDs as $ID)
		{
			if (!isset($_SESSION[$arParams["COMPARE_NAME"]][$arParams["IBLOCK_ID"]]["ITEMS"][$ID]))
				$allOffersInCompare = false;
		}
		if ($allOffersInCompare)
			$arItemsInCompare[] = $elementID;
	}

	if (count($arItemsInCompare) > 0)
	{                  
		echo '<script type="text/javascript">$(function(){'."\r\n";
		foreach ($arItemsInCompare as $id)
		{
			echo "$('#catalog_add2compare_link_".$id." input').attr('checked', 'checked')";
		}
		echo '})</script>';
	}
}
?>