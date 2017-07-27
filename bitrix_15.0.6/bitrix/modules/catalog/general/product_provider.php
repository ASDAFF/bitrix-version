<?
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\DiscountCouponsManager;
use Bitrix\Currency;
use Bitrix\Catalog;

if (!Loader::includeModule('sale'))
	return false;

Loc::loadMessages(__FILE__);

class CCatalogProductProvider implements IBXSaleProductProvider
{
	protected static $arOneTimeCoupons = array();	// unused variable, compatibilty only
	protected static $clearAutoCache = array();
	protected static $catalogList = array();
	protected static $userCache = array();

	/**
	 * @param array $arParams
	 * @return array|false
	 */
	public static function GetProductData($arParams)
	{
		$adminSection = (defined('ADMIN_SECTION') && ADMIN_SECTION === true);

		if (!isset($arParams['QUANTITY']) || (float)$arParams['QUANTITY'] <= 0)
			$arParams['QUANTITY'] = 0;

		$arParams['RENEWAL'] = (isset($arParams['RENEWAL']) && $arParams['RENEWAL'] == 'Y' ? 'Y' : 'N');
		$arParams['CHECK_QUANTITY'] = (isset($arParams['CHECK_QUANTITY']) && $arParams["CHECK_QUANTITY"] == 'N' ? 'N' : 'Y');
		$arParams['CHECK_PRICE'] = (isset($arParams['CHECK_PRICE']) && $arParams['CHECK_PRICE'] == 'N' ? 'N' : 'Y');
		$arParams['CHECK_COUPONS'] = (isset($arParams['CHECK_COUPONS']) && $arParams['CHECK_COUPONS'] == 'N' ? 'N' : 'Y');
		$arParams['CHECK_DISCOUNT'] = (isset($arParams['CHECK_DISCOUNT']) && $arParams['CHECK_DISCOUNT'] == 'N' ? 'N' : 'Y');
		$arParams['BASKET_ID'] = (string)(isset($arParams['BASKET_ID']) ? $arParams['BASKET_ID'] : '0');
		$arParams['USER_ID'] = (isset($arParams['USER_ID']) ? (int)$arParams['USER_ID'] : 0);
		if ($arParams['USER_ID'] < 0)
			$arParams['USER_ID'] = 0;
		$arParams['SITE_ID'] = (isset($arParams['SITE_ID']) ? $arParams['SITE_ID'] : false);
		$strSiteID = $arParams['SITE_ID'];

		$arParams['CURRENCY'] = (isset($arParams['CURRENCY']) ? Currency\CurrencyManager::checkCurrencyID($arParams['CURRENCY']) : false);
		if ($arParams['CURRENCY'] === false)
			$arParams['CURRENCY'] = CSaleLang::GetLangCurrency($strSiteID ? $strSiteID : SITE_ID);

		$productID = (int)$arParams['PRODUCT_ID'];
		$quantity = (float)$arParams['QUANTITY'];
		$intUserID = (int)$arParams['USER_ID'];

		global $USER, $APPLICATION;

		$arResult = array();

		if ($adminSection)
		{
			$userGroups = self::getUserGroups($intUserID);
			if (empty($userGroups))
				return $arResult;

			$dbIBlockElement = CIBlockElement::GetList(
				array(),
				array(
					'ID' => $productID,
					'ACTIVE' => 'Y',
					'ACTIVE_DATE' => 'Y',
					'CHECK_PERMISSIONS' => 'N'
				),
				false,
				false,
				array('ID', 'IBLOCK_ID', 'NAME', 'DETAIL_PAGE_URL')
			);
			if (!($arProduct = $dbIBlockElement->GetNext()))
				return $arResult;

			$extRights = CIBlock::GetArrayByID($arProduct['IBLOCK_ID'], 'RIGHTS_MODE') == 'E';
			if ($intUserID == 0)
			{
				if ($extRights)
				{
					$elementRights = new CIBlockElementRights($arProduct['IBLOCK_ID'], $arProduct['ID']);
					$readList = $elementRights->GetRights(array('operations' => array('element_read')));
					$disable = true;
					if (!empty($readList) && is_array($readList))
					{
						foreach ($readList as &$row)
						{
							if ($row['GROUP_CODE'] == 'G2')
							{
								$disable = false;
								break;
							}
						}
						unset($row);
					}
					unset($readList, $elementRights);
					if ($disable)
						return $arResult;
					unset($disable);
				}
				else
				{
					$groupRights = CIBlock::GetGroupPermissions($arProduct['IBLOCK_ID']);
					if (empty($groupRights) || !isset($groupRights[2]) || $groupRights[2] < 'R')
						return $arResult;
					unset($groupRights);
				}
			}
			else
			{
				if ($extRights)
				{
					$arUserRights = CIBlockElementRights::GetUserOperations($productID, $intUserID);
					if (empty($arUserRights) || !isset($arUserRights['element_read']))
						return $arResult;
					unset($arUserRights);
				}
				else
				{
					if (CIBlock::GetPermission($arProduct['IBLOCK_ID'], $intUserID) < 'R')
						return $arResult;
				}
			}
			unset($extRights);
		}
		else
		{
			$userGroups = $USER->GetUserGroupArray();
			$dbIBlockElement = CIBlockElement::GetList(
				array(),
				array(
					'ID' => $productID,
					'ACTIVE' => 'Y',
					'ACTIVE_DATE' => 'Y',
					'CHECK_PERMISSIONS' => 'Y',
					'MIN_PERMISSION' => 'R'
				),
				false,
				false,
				array('ID', 'IBLOCK_ID', 'NAME', 'DETAIL_PAGE_URL')
			);
			if (!($arProduct = $dbIBlockElement->GetNext()))
				return $arResult;
		}

		if (!isset(self::$catalogList[$arProduct['IBLOCK_ID']]))
		{
			$catalogIterator = Catalog\CatalogIblockTable::getList(array(
				'select' => array('IBLOCK_ID', 'SUBSCRIPTION'),
				'filter' => array('IBLOCK_ID' => $arProduct['IBLOCK_ID'])
			));
			self::$catalogList[$arProduct['IBLOCK_ID']] = $catalogIterator->fetch();
		}
		if (empty(self::$catalogList[$arProduct['IBLOCK_ID']]) || !is_array(self::$catalogList[$arProduct['IBLOCK_ID']]))
			return $arResult;
		if (self::$catalogList[$arProduct['IBLOCK_ID']]['SUBSCRIPTION'] == 'Y')
			$quantity = 1;

		$dblQuantity = 0;
		$boolQuantity = false;

		$rsProducts = CCatalogProduct::GetList(
			array(),
			array('ID' => $productID),
			false,
			false,
			array(
				'ID',
				'CAN_BUY_ZERO',
				'QUANTITY_TRACE',
				'QUANTITY',
				'WEIGHT',
				'WIDTH',
				'HEIGHT',
				'LENGTH',
				'BARCODE_MULTI',
				'TYPE'
			)
		);

		if ($arCatalogProduct = $rsProducts->Fetch())
		{
			$dblQuantity = doubleval($arCatalogProduct["QUANTITY"]);
			$boolQuantity = ('Y' != $arCatalogProduct["CAN_BUY_ZERO"] && 'Y' == $arCatalogProduct["QUANTITY_TRACE"]);

			if ($arParams["CHECK_QUANTITY"] == "Y" && $boolQuantity && 0 >= $dblQuantity)
			{
				$APPLICATION->ThrowException(Loc::getMessage("CATALOG_NO_QUANTITY_PRODUCT", array("#NAME#" => htmlspecialcharsbx($arProduct["~NAME"]))), "CATALOG_NO_QUANTITY_PRODUCT");
				return $arResult;
			}
		}
		else
		{
			$APPLICATION->ThrowException(Loc::getMessage("CATALOG_ERR_NO_PRODUCT"), "CATALOG_NO_QUANTITY_PRODUCT");
			return $arResult;
		}

		if ($arParams["CHECK_PRICE"] == "Y")
		{
			$productHash = array(
				'MODULE' => 'catalog',
				'PRODUCT_ID' => $productID,
				'BASKET_ID' => $arParams['BASKET_ID']
			);

			$arCoupons = array();
			if ($arParams['CHECK_COUPONS'] == 'Y')
			{
				$arCoupons = DiscountCouponsManager::getForApply(array(), $productHash, true);
				if (!empty($arCoupons))
					$arCoupons = array_keys($arCoupons);
			}
			if ($adminSection)
			{
				if ($intUserID > 0)
					CCatalogDiscountSave::SetDiscountUserID($intUserID);
				else
					CCatalogDiscountSave::Disable();
			}

			$currentVatMode = CCatalogProduct::getPriceVatIncludeMode();
			$currentUseDiscount = CCatalogProduct::getUseDiscount();
			CCatalogProduct::setUseDiscount($arParams['CHECK_DISCOUNT'] == 'Y');
			CCatalogProduct::setPriceVatIncludeMode(true);
			CCatalogProduct::setUsedCurrency($arParams['CURRENCY']);
			$arPrice = CCatalogProduct::GetOptimalPrice($productID, $quantity, $userGroups, $arParams['RENEWAL'], array(), ($adminSection ? $strSiteID : false), $arCoupons);

			if (empty($arPrice))
			{
				if ($nearestQuantity = CCatalogProduct::GetNearestQuantityPrice($productID, $quantity, $userGroups))
				{
					$quantity = $nearestQuantity;
					$arPrice = CCatalogProduct::GetOptimalPrice($productID, $quantity, $userGroups, $arParams['RENEWAL'], array(), ($adminSection ? $strSiteID : false), $arCoupons);
				}
			}
			CCatalogProduct::clearUsedCurrency();
			CCatalogProduct::setPriceVatIncludeMode($currentVatMode);
			CCatalogProduct::setUseDiscount($currentUseDiscount);
			unset($userGroups, $currentUseDiscount, $currentVatMode);
			if ($adminSection)
			{
				if ($intUserID > 0)
					CCatalogDiscountSave::ClearDiscountUserID();
				else
					CCatalogDiscountSave::Enable();
			}

			if (empty($arPrice))
				return $arResult;

			$arDiscountList = array();
			if (empty($arPrice['DISCOUNT_LIST']) && !empty($arPrice['DISCOUNT']) && is_array($arPrice['DISCOUNT']))
				$arPrice['DISCOUNT_LIST'] = array($arPrice['DISCOUNT']);
			if (!empty($arPrice['DISCOUNT_LIST']))
			{
				$appliedCoupons = array();
				foreach ($arPrice['DISCOUNT_LIST'] as &$arOneDiscount)
				{
					$arOneList = array(
						'ID' => $arOneDiscount['ID'],
						'NAME' => $arOneDiscount['NAME'],
						'COUPON' => '',
						'COUPON_TYPE' => '',
						'USE_COUPONS' => (isset($arOneDiscount['USE_COUPONS']) ? $arOneDiscount['USE_COUPONS'] : 'N'),
						'MODULE_ID' => (isset($oneDiscount['MODULE_ID']) ? $oneDiscount['MODULE_ID'] : 'catalog'),
						'TYPE' => $arOneDiscount['TYPE'],
						'VALUE' => $arOneDiscount['VALUE'],
						'VALUE_TYPE' => $arOneDiscount['VALUE_TYPE'],
						'MAX_VALUE' => (
							$arOneDiscount['VALUE_TYPE'] == Catalog\DiscountTable::VALUE_TYPE_PERCENT
							? $arOneDiscount['MAX_DISCOUNT']
							: 0
						),
						'CURRENCY' => $arOneDiscount['CURRENCY'],
						'HANDLERS' => (isset($arOneDiscount['HANDLERS']) ? $arOneDiscount['HANDLERS'] : array())
					);

					if (!empty($arOneDiscount['COUPON']))
					{
						$arOneList['USE_COUPONS'] = 'Y';
						$arOneList['COUPON'] = $arOneDiscount['COUPON'];
						$arOneList['COUPON_TYPE'] = $arOneDiscount['COUPON_ONE_TIME'];
						$appliedCoupons[] = $arOneDiscount['COUPON'];
					}
					$arDiscountList[] = $arOneList;
				}
				unset($arOneList, $arOneDiscount);
				if (!empty($appliedCoupons))
					$resultApply = DiscountCouponsManager::setApplyByProduct($productHash, $appliedCoupons);
				unset($resultApply, $appliedCoupons);
			}

			if (empty($arPrice['PRICE']['CATALOG_GROUP_NAME']))
			{
				if (!empty($arPrice['PRICE']['CATALOG_GROUP_ID']))
				{
					$groupIterator = Catalog\GroupTable::getList(array(
						'select' => array('ID', 'NAME', 'NAME_LANG' => 'CURRENT_LANG.NAME'),
						'filter' => array('ID' => $arPrice['PRICE']['CATALOG_GROUP_ID'])
					));
					if ($group = $groupIterator->fetch())
						$arPrice['PRICE']['CATALOG_GROUP_NAME'] = (!empty($group['NAME_LANG']) ? $group['NAME_LANG'] : $group['NAME']);
					unset($group, $groupIterator);
				}
			}
		}
		else
		{
			$vatRate = 0.0;
			$rsVAT = CCatalogProduct::GetVATInfo($productID);
			if ($arVAT = $rsVAT->Fetch())
			{
				$vatRate = (float)$arVAT['RATE'] * 0.01;
			}
		}

		$arResult = array(
			"NAME" => $arProduct["~NAME"],
			"CAN_BUY" => "Y",
			"DETAIL_PAGE_URL" => $arProduct['~DETAIL_PAGE_URL'],
			"BARCODE_MULTI" => $arCatalogProduct["BARCODE_MULTI"],
			"WEIGHT" => (float)$arCatalogProduct['WEIGHT'],
			"DIMENSIONS" => serialize(array(
				"WIDTH" => $arCatalogProduct["WIDTH"],
				"HEIGHT" => $arCatalogProduct["HEIGHT"],
				"LENGTH" => $arCatalogProduct["LENGTH"]
			)),
			"TYPE" => ($arCatalogProduct["TYPE"] == CCatalogProduct::TYPE_SET) ? CCatalogProductSet::TYPE_SET : NULL
		);

		if ($arParams["CHECK_QUANTITY"] == "Y")
			$arResult["QUANTITY"] = ($boolQuantity && $dblQuantity < $quantity) ? $dblQuantity : $quantity;
		else
			$arResult["QUANTITY"] = $arParams["QUANTITY"];

		if ($arParams["CHECK_QUANTITY"] == "Y" && $boolQuantity && $dblQuantity < $quantity)
			$APPLICATION->ThrowException(Loc::getMessage("CATALOG_QUANTITY_NOT_ENOGH", array("#NAME#" => htmlspecialcharsbx($arProduct["~NAME"]), "#CATALOG_QUANTITY#" => $arCatalogProduct["QUANTITY"], "#QUANTITY#" => $quantity)), "CATALOG_QUANTITY_NOT_ENOGH");

		if ($arParams['CHECK_PRICE'] == 'Y')
		{
			$arResult['PRODUCT_PRICE_ID'] = $arPrice['PRICE']['ID'];
			$arResult['NOTES'] = $arPrice['PRICE']['CATALOG_GROUP_NAME'];
			$arResult['VAT_RATE'] = $arPrice['PRICE']['VAT_RATE'];
			$arResult['DISCOUNT_NAME'] = '';
			$arResult['DISCOUNT_COUPON'] = '';
			$arResult['DISCOUNT_LIST'] = array();

			if (empty($arPrice['RESULT_PRICE']) || !is_array($arPrice['RESULT_PRICE']))
				$arPrice['RESULT_PRICE'] = CCatalogDiscount::calculateDiscountList($arPrice['PRICE'], $arParams['CURRENCY'], $arDiscountList, true);

			$arResult['BASE_PRICE'] = $arPrice['RESULT_PRICE']['BASE_PRICE'];
			$arResult['PRICE'] = $arPrice['RESULT_PRICE']['DISCOUNT_PRICE'];
			$arResult['CURRENCY'] = $arPrice['RESULT_PRICE']['CURRENCY'];
			$arResult['DISCOUNT_PRICE'] = $arPrice['RESULT_PRICE']['DISCOUNT'];
			if (isset($arPrice['RESULT_PRICE']['PERCENT']))
				$arResult['DISCOUNT_VALUE'] = ($arPrice['RESULT_PRICE']['PERCENT'] > 0 ? $arPrice['RESULT_PRICE']['PERCENT'].'%' : 0);
			else
				$arResult['DISCOUNT_VALUE'] = $arPrice['RESULT_PRICE']['DISCOUNT_VALUE'];

			if (!empty($arDiscountList))
				$arResult['DISCOUNT_LIST'] = $arDiscountList;
			if (!empty($arPrice['DISCOUNT']))
			{
				$arResult['DISCOUNT_NAME'] = '['.$arPrice['DISCOUNT']['ID'].'] '.$arPrice['DISCOUNT']['NAME'];
				if (!empty($arPrice['DISCOUNT']['COUPON']))
				{
					$arResult['DISCOUNT_COUPON'] = $arPrice['DISCOUNT']['COUPON'];
				}
				if (empty($arResult['DISCOUNT_LIST']))
					$arResult['DISCOUNT_LIST'] = array($arPrice['DISCOUNT']);
			}
		}
		else
		{
			$arResult["VAT_RATE"] = $vatRate;
		}

		return $arResult;
	}

	/**
	 * @param array $arParams
	 * @return array|false
	 */
	public static function OrderProduct($arParams)
	{
		$adminSection = (defined('ADMIN_SECTION') && ADMIN_SECTION === true);

		$arParams['RENEWAL'] = (isset($arParams['RENEWAL']) && $arParams['RENEWAL'] == 'Y' ? 'Y' : 'N');
		$arParams['CHECK_QUANTITY'] = (isset($arParams['CHECK_QUANTITY']) && $arParams['CHECK_QUANTITY'] == 'N' ? 'N' : 'Y');
		$arParams['CHECK_DISCOUNT'] = (isset($arParams['CHECK_DISCOUNT']) && $arParams['CHECK_DISCOUNT'] == 'N' ? 'N' : 'Y');
		$arParams['USER_ID'] = (isset($arParams['USER_ID']) ? (int)$arParams['USER_ID'] : 0);
		if ($arParams['USER_ID'] < 0)
			$arParams['USER_ID'] = 0;
		$arParams['SITE_ID'] = (isset($arParams['SITE_ID']) ? $arParams['SITE_ID'] : false);
		$strSiteID = $arParams['SITE_ID'];
		$arParams['BASKET_ID'] = (string)(isset($arParams['BASKET_ID']) ? $arParams['BASKET_ID'] : '0');

		$arParams['CURRENCY'] = (isset($arParams['CURRENCY']) ? Currency\CurrencyManager::checkCurrencyID($arParams['CURRENCY']) : false);
		if ($arParams['CURRENCY'] === false)
			$arParams['CURRENCY'] = CSaleLang::GetLangCurrency($strSiteID ? $strSiteID : SITE_ID);

		global $USER;

		$productID = (int)$arParams['PRODUCT_ID'];
		$quantity = (float)$arParams['QUANTITY'];
		$intUserID = (int)$arParams['USER_ID'];

		$arResult = array();

		if ($adminSection)
		{
			if ($intUserID == 0)
				return $arResult;
			$userGroups = self::getUserGroups($intUserID);
			if (empty($userGroups))
				return $arResult;

			$dbIBlockElement = CIBlockElement::GetList(
				array(),
				array(
					'ID' => $productID,
					'ACTIVE' => 'Y',
					'ACTIVE_DATE' => 'Y',
					'CHECK_PERMISSION' => 'N'
				),
				false,
				false,
				array('ID', 'IBLOCK_ID', 'NAME', 'DETAIL_PAGE_URL')
			);
			if (!($arProduct = $dbIBlockElement->GetNext()))
				return $arResult;

			if (CIBlock::GetArrayByID($arProduct['IBLOCK_ID'], 'RIGHTS_MODE') == 'E')
			{
				$arUserRights = CIBlockElementRights::GetUserOperations($productID, $intUserID);
				if (empty($arUserRights) || !isset($arUserRights['element_read']))
					return $arResult;
				unset($arUserRights);
			}
			else
			{
				if (CIBlock::GetPermission($arProduct['IBLOCK_ID'], $intUserID) < 'R')
					return $arResult;
			}
		}
		else
		{
			$userGroups = $USER->GetUserGroupArray();
			$dbIBlockElement = CIBlockElement::GetList(
				array(),
				array(
					'ID' => $productID,
					'ACTIVE' => 'Y',
					'ACTIVE_DATE' => 'Y',
					'CHECK_PERMISSIONS' => 'Y',
					'MIN_PERMISSION' => 'R'
				),
				false,
				false,
				array('ID', 'IBLOCK_ID', 'NAME', 'DETAIL_PAGE_URL')
			);
			if (!($arProduct = $dbIBlockElement->GetNext()))
				return $arResult;
		}

		$rsProducts = CCatalogProduct::GetList(
			array(),
			array('ID' => $productID),
			false,
			false,
			array(
				'ID',
				'CAN_BUY_ZERO',
				'QUANTITY_TRACE',
				'QUANTITY',
				'WEIGHT',
				'WIDTH',
				'HEIGHT',
				'LENGTH',
				'BARCODE_MULTI',
				'TYPE'
			)
		);

		if ($arCatalogProduct = $rsProducts->Fetch())
		{
			$arCatalogProduct["QUANTITY"] = (double)$arCatalogProduct["QUANTITY"];
			if ($arParams["CHECK_QUANTITY"] == "Y")
			{
				if (
					'Y' != $arCatalogProduct["CAN_BUY_ZERO"]
					&& 'Y' == $arCatalogProduct["QUANTITY_TRACE"]
					&& ($arCatalogProduct["QUANTITY"] <= 0 || $quantity > $arCatalogProduct["QUANTITY"])
				)
				{
					return $arResult;
				}
			}
		}
		else
		{
			return $arResult;
		}

		if ($adminSection)
			CCatalogDiscountSave::SetDiscountUserID($intUserID);

		$productHash = array(
			'MODULE' => 'catalog',
			'PRODUCT_ID' => $productID,
			'BASKET_ID' => $arParams['BASKET_ID']
		);
		$arCoupons = DiscountCouponsManager::getForApply(array(), $productHash, true);

		if (!empty($arCoupons))
			$arCoupons = array_keys($arCoupons);

		$currentVatMode = CCatalogProduct::getPriceVatIncludeMode();
		$currentUseDiscount = CCatalogProduct::getUseDiscount();
		CCatalogProduct::setUseDiscount($arParams['CHECK_DISCOUNT'] == 'Y');
		CCatalogProduct::setPriceVatIncludeMode(true);
		CCatalogProduct::setUsedCurrency($arParams['CURRENCY']);
		$arPrice = CCatalogProduct::GetOptimalPrice($productID, $quantity, $userGroups, $arParams['RENEWAL'], array(), ($adminSection ? $strSiteID : false), $arCoupons);

		if (empty($arPrice))
		{
			if ($nearestQuantity = CCatalogProduct::GetNearestQuantityPrice($productID, $quantity, $userGroups))
			{
				$quantity = $nearestQuantity;
				$arPrice = CCatalogProduct::GetOptimalPrice($productID, $quantity, $userGroups, $arParams['RENEWAL'], array(), ($adminSection ? $strSiteID : false), $arCoupons);
			}
		}
		CCatalogProduct::clearUsedCurrency();
		CCatalogProduct::setPriceVatIncludeMode($currentVatMode);
		CCatalogProduct::setUseDiscount($currentUseDiscount);
		unset($userGroups, $currentUseDiscount, $currentVatMode);
		if ($adminSection)
			CCatalogDiscountSave::ClearDiscountUserID();

		if (empty($arPrice))
			return $arResult;

		$arDiscountList = array();
		if (empty($arPrice['DISCOUNT_LIST']) && !empty($arPrice['DISCOUNT']) && is_array($arPrice['DISCOUNT']))
			$arPrice['DISCOUNT_LIST'] = array($arPrice['DISCOUNT']);
		if (!empty($arPrice['DISCOUNT_LIST']))
		{
			$appliedCoupons = array();
			foreach ($arPrice['DISCOUNT_LIST'] as &$arOneDiscount)
			{
				$arOneList = array(
					'ID' => $arOneDiscount['ID'],
					'NAME' => $arOneDiscount['NAME'],
					'COUPON' => '',
					'COUPON_TYPE' => '',
					'USE_COUPONS' => (isset($arOneDiscount['USE_COUPONS']) ? $arOneDiscount['USE_COUPONS'] : 'N'),
					'MODULE_ID' => (isset($oneDiscount['MODULE_ID']) ? $oneDiscount['MODULE_ID'] : 'catalog'),
					'TYPE' => $arOneDiscount['TYPE'],
					'VALUE' => $arOneDiscount['VALUE'],
					'VALUE_TYPE' => $arOneDiscount['VALUE_TYPE'],
					'MAX_VALUE' => (
						$arOneDiscount['VALUE_TYPE'] == Catalog\DiscountTable::VALUE_TYPE_PERCENT
						? $arOneDiscount['MAX_DISCOUNT']
						: 0
					),
					'CURRENCY' => $arOneDiscount['CURRENCY'],
					'HANDLERS' => (isset($arOneDiscount['HANDLERS']) ? $arOneDiscount['HANDLERS'] : array())
				);

				if (!empty($arOneDiscount['COUPON']))
				{
					$arOneList['USE_COUPONS'] = 'Y';
					$arOneList['COUPON'] = $arOneDiscount['COUPON'];
					$arOneList['COUPON_TYPE'] = $arOneDiscount['COUPON_ONE_TIME'];
					$appliedCoupons[] = $arOneDiscount['COUPON'];
				}

				$arDiscountList[] = $arOneList;
			}
			unset($arOneList, $arOneDiscount);
			if (!empty($appliedCoupons))
				$resultApply = DiscountCouponsManager::setApplyByProduct($productHash, $appliedCoupons);
			unset($resultApply, $appliedCoupons);
		}

		if (empty($arPrice['PRICE']['CATALOG_GROUP_NAME']))
		{
			if (!empty($arPrice['PRICE']['CATALOG_GROUP_ID']))
			{
				$groupIterator = Catalog\GroupTable::getList(array(
					'select' => array('ID', 'NAME', 'NAME_LANG' => 'CURRENT_LANG.NAME'),
					'filter' => array('ID' => $arPrice['PRICE']['CATALOG_GROUP_ID'])
				));
				if ($group = $groupIterator->fetch())
					$arPrice["PRICE"]["CATALOG_GROUP_NAME"] = (!empty($group['NAME_LANG']) ? $group['NAME_LANG'] : $group['NAME']);
				unset($group, $groupIterator);
			}
		}

		if (empty($arPrice['RESULT_PRICE']) || !is_array($arPrice['RESULT_PRICE']))
			$arPrice['RESULT_PRICE'] = CCatalogDiscount::calculateDiscountList($arPrice['PRICE'], $arParams['CURRENCY'], $arDiscountList, true);

		$arResult = array(
			'PRODUCT_PRICE_ID' => $arPrice['PRICE']['ID'],
			'BASE_PRICE' => $arPrice['RESULT_PRICE']['BASE_PRICE'],
			'PRICE' => $arPrice['RESULT_PRICE']['DISCOUNT_PRICE'],
			'VAT_RATE' => $arPrice['PRICE']['VAT_RATE'],
			"CURRENCY" => $arPrice['RESULT_PRICE']['CURRENCY'],
			"WEIGHT" => (float)$arCatalogProduct["WEIGHT"],
			"DIMENSIONS" => serialize(array(
				"WIDTH" => $arCatalogProduct["WIDTH"],
				"HEIGHT" => $arCatalogProduct["HEIGHT"],
				"LENGTH" => $arCatalogProduct["LENGTH"]
			)),
			"NAME" => $arProduct["~NAME"],
			"CAN_BUY" => "Y",
			"DETAIL_PAGE_URL" => $arProduct['~DETAIL_PAGE_URL'],
			"NOTES" => $arPrice["PRICE"]["CATALOG_GROUP_NAME"],
			"DISCOUNT_PRICE" => $arPrice['RESULT_PRICE']['DISCOUNT'],
			"TYPE" => ($arCatalogProduct["TYPE"] == CCatalogProduct::TYPE_SET) ? CCatalogProductSet::TYPE_SET : NULL,
			"DISCOUNT_VALUE" => ($arPrice['RESULT_PRICE']['PERCENT'] > 0 ? $arPrice['RESULT_PRICE']['PERCENT'].'%' : 0),
			"DISCOUNT_NAME" => '',
			"DISCOUNT_COUPON" => '',
			"DISCOUNT_LIST" => array()
		);

		if ($arParams["CHECK_QUANTITY"] == "Y")
			$arResult["QUANTITY"] = $quantity;
		else
			$arResult["QUANTITY"] = $arParams["QUANTITY"];

		if (!empty($arDiscountList))
			$arResult['DISCOUNT_LIST'] = $arDiscountList;
		if (!empty($arPrice['DISCOUNT']))
		{
			$arResult['DISCOUNT_NAME'] = '['.$arPrice['DISCOUNT']['ID'].'] '.$arPrice['DISCOUNT']['NAME'];
			if (!empty($arPrice['DISCOUNT']['COUPON']))
			{
				$arResult['DISCOUNT_COUPON'] = $arPrice['DISCOUNT']['COUPON'];
			}
			if (empty($arResult['DISCOUNT_LIST']))
				$arResult['DISCOUNT_LIST'] = array($arPrice['DISCOUNT']);
		}

		return $arResult;
	}

	// in case product provider class is used,
	// instead of this method quantity is changed with ReserveProduct and DeductProduct methods
	public static function CancelProduct($arParams)
	{
		return true;
	}

	public static function DeliverProduct($arParams)
	{
		return CatalogPayOrderCallback(
			$arParams["PRODUCT_ID"],
			$arParams["USER_ID"],
			$arParams["PAID"],
			$arParams["ORDER_ID"]
		);
	}

	public static function ViewProduct($arParams)
	{
		if (!is_set($arParams["SITE_ID"]))
			$arParams["SITE_ID"] = SITE_ID;

		return CatalogViewedProductCallback(
			$arParams["PRODUCT_ID"],
			$arParams["USER_ID"],
			$arParams["SITE_ID"]
		);
	}

	public static function RecurringOrderProduct($arParams)
	{
		return CatalogRecurringCallback(
			$arParams["PRODUCT_ID"],
			$arParams["USER_ID"]
		);
	}

	public static function ReserveProduct($arParams)
	{
		global $APPLICATION;

		$arRes = array();
		$arFields = array();

		if ((int)$arParams["PRODUCT_ID"] <= 0)
		{
			$APPLICATION->ThrowException(Loc::getMessage("RSRV_INCORRECT_ID"), "NO_ORDER_ID");
			$arRes["RESULT"] = false;
			return $arRes;
		}

		$disableReservation = (COption::GetOptionString("catalog", "enable_reservation") == "N"
			&& COption::GetOptionString("sale", "product_reserve_condition") != "S"
			&& COption::GetOptionString('catalog','default_use_store_control') != "Y");

		if ((string)$arParams["UNDO_RESERVATION"] != "Y")
			$arParams["UNDO_RESERVATION"] = "N";

		$arParams["QUANTITY_ADD"] = doubleval($arParams["QUANTITY_ADD"]);

		$rsProducts = CCatalogProduct::GetList(
			array(),
			array('ID' => $arParams["PRODUCT_ID"]),
			false,
			false,
			array('ID', 'CAN_BUY_ZERO', 'NEGATIVE_AMOUNT_TRACE', 'QUANTITY_TRACE', 'QUANTITY', 'QUANTITY_RESERVED')
		);

		if ($arProduct = $rsProducts->Fetch())
		{
			if ($disableReservation)
			{
				$startReservedQuantity = 0;

				if ($arParams["UNDO_RESERVATION"] != "Y")
					$arFields = array("QUANTITY" => $arProduct["QUANTITY"] - $arParams["QUANTITY_ADD"]);
				else
					$arFields = array("QUANTITY" => $arProduct["QUANTITY"] + $arParams["QUANTITY_ADD"]);

				$arRes["RESULT"] = CCatalogProduct::Update($arParams["PRODUCT_ID"], $arFields);
				$arFields["QUANTITY_RESERVED"] = 0;
				if (self::isNeedClearPublicCache(
					$arProduct['QUANTITY'],
					$arFields['QUANTITY'],
					$arProduct['QUANTITY_TRACE'],
					$arProduct['CAN_BUY_ZERO']
				))
				{
					$productInfo = array(
						'CAN_BUY_ZERO' => $arProduct['CAN_BUY_ZERO'],
						'NEGATIVE_AMOUNT_TRACE' => $arProduct['NEGATIVE_AMOUNT_TRACE'],
						'QUANTITY_TRACE' => $arProduct['QUANTITY_TRACE'],
						'OLD_QUANTITY' => $arProduct['QUANTITY'],
						'QUANTITY' => $arFields['QUANTITY'],
						'DELTA' => $arFields['QUANTITY'] - $arProduct['QUANTITY']
					);
					self::clearPublicCache($arProduct['ID'], $productInfo);
				}
			}
			else
			{
				if ($arProduct["QUANTITY_TRACE"] == "N" || (isset($arParams["ORDER_DEDUCTED"]) && $arParams["ORDER_DEDUCTED"] == "Y"))
				{
					$arRes["RESULT"] = true;
					$arFields["QUANTITY_RESERVED"] = 0;
					$startReservedQuantity = 0;
				}
				else
				{
					$startReservedQuantity = $arProduct["QUANTITY_RESERVED"];

					if ($arParams["UNDO_RESERVATION"] == "N")
					{
						if ($arProduct["CAN_BUY_ZERO"] == "Y")
						{
							$arFields["QUANTITY_RESERVED"] = $arProduct["QUANTITY_RESERVED"] + $arParams["QUANTITY_ADD"];

							if ($arProduct["QUANTITY"] >= $arParams["QUANTITY_ADD"])
							{
								$arFields["QUANTITY"] = $arProduct["QUANTITY"] - $arParams["QUANTITY_ADD"];
							}
							elseif ($arProduct["QUANTITY"] < $arParams["QUANTITY_ADD"])
							{
								if ($arProduct["NEGATIVE_AMOUNT_TRACE"] == "Y")
								{
									//reserve value, quantity will be negative
									$arFields["QUANTITY"] = $arProduct["QUANTITY"] - $arParams["QUANTITY_ADD"];
								}
								else
								{
									$arFields["QUANTITY"] = 0;
								}
							}

							$arRes["RESULT"] = CCatalogProduct::Update($arParams["PRODUCT_ID"], $arFields);
						}
						else //CAN_BUY_ZERO = N
						{
							if ($arProduct["QUANTITY"] >= $arParams["QUANTITY_ADD"])
							{
								$arFields["QUANTITY"] = $arProduct["QUANTITY"] - $arParams["QUANTITY_ADD"];
								$arFields["QUANTITY_RESERVED"] = $arProduct["QUANTITY_RESERVED"] + $arParams["QUANTITY_ADD"];
							}
							elseif ($arProduct["QUANTITY"] < $arParams["QUANTITY_ADD"])
							{
								//reserve only possible value, quantity = 0

								$arRes["QUANTITY_NOT_RESERVED"] = $arParams["QUANTITY_ADD"] - $arProduct["QUANTITY"];

								$arFields["QUANTITY"] = 0;
								$arFields["QUANTITY_RESERVED"] = $arProduct["QUANTITY_RESERVED"] + $arProduct["QUANTITY"];

								$APPLICATION->ThrowException(Loc::getMessage("RSRV_QUANTITY_NOT_ENOUGH_ERROR", self::GetProductCatalogInfo($arParams["PRODUCT_ID"])), "ERROR_NOT_ENOUGH_QUANTITY");
							}
							if (self::isNeedClearPublicCache(
								$arProduct['QUANTITY'],
								$arFields['QUANTITY'],
								$arProduct['QUANTITY_TRACE'],
								$arProduct['CAN_BUY_ZERO']
							))
							{
								$productInfo = array(
									'CAN_BUY_ZERO' => $arProduct['CAN_BUY_ZERO'],
									'NEGATIVE_AMOUNT_TRACE' => $arProduct['NEGATIVE_AMOUNT_TRACE'],
									'QUANTITY_TRACE' => $arProduct['QUANTITY_TRACE'],
									'OLD_QUANTITY' => $arProduct['QUANTITY'],
									'QUANTITY' => $arFields['QUANTITY'],
									'DELTA' => $arFields['QUANTITY'] - $arProduct['QUANTITY']
								);
								self::clearPublicCache($arProduct['ID'], $productInfo);
							}
							$arRes["RESULT"] = CCatalogProduct::Update($arParams["PRODUCT_ID"], $arFields);
						}
					}
					else //undo reservation
					{
						$arFields["QUANTITY"] = $arProduct["QUANTITY"] + $arParams["QUANTITY_ADD"];
						$arFields["QUANTITY_RESERVED"] = $arProduct["QUANTITY_RESERVED"] - $arParams["QUANTITY_ADD"];
						$arRes["RESULT"] = CCatalogProduct::Update($arParams["PRODUCT_ID"], $arFields);
						if (self::isNeedClearPublicCache(
							$arProduct['QUANTITY'],
							$arFields['QUANTITY'],
							$arProduct['QUANTITY_TRACE'],
							$arProduct['CAN_BUY_ZERO']
						))
						{
							$productInfo = array(
								'CAN_BUY_ZERO' => $arProduct['CAN_BUY_ZERO'],
								'NEGATIVE_AMOUNT_TRACE' => $arProduct['NEGATIVE_AMOUNT_TRACE'],
								'QUANTITY_TRACE' => $arProduct['QUANTITY_TRACE'],
								'OLD_QUANTITY' => $arProduct['QUANTITY'],
								'QUANTITY' => $arFields['QUANTITY'],
								'DELTA' => $arFields['QUANTITY'] - $arProduct['QUANTITY']
							);
							self::clearPublicCache($arProduct['ID'], $productInfo);
						}
					}
				} //quantity trace
			}
		} //product found
		else
		{
			$APPLICATION->ThrowException(Loc::getMessage("RSRV_ID_NOT_FOUND", array("#PRODUCT_ID#" => $arParams["PRODUCT_ID"])), "ID_NOT_FOUND");
			$arRes["RESULT"] = false;
			return $arRes;
		}

		if ($arRes["RESULT"])
		{
			$arRes["QUANTITY_RESERVED"] = $arFields["QUANTITY_RESERVED"] - $startReservedQuantity;
		}
		else
		{
			$APPLICATION->ThrowException(Loc::getMessage("RSRV_UNKNOWN_ERROR", self::GetProductCatalogInfo($arParams["PRODUCT_ID"])), "UNKNOWN_RESERVATION_ERROR");
		}

		return $arRes;
	}

	public static function DeductProduct($arParams)
	{
		global $APPLICATION;

		$arRes = array();
		$arFields = array();

		$strUseStoreControl = COption::GetOptionString('catalog','default_use_store_control');

		$disableReservation = (COption::GetOptionString("catalog", "enable_reservation") == "N"
			&& COption::GetOptionString("sale", "product_reserve_condition") != "S"
			&& $strUseStoreControl != "Y");

		if ($disableReservation)
		{
			$arRes["RESULT"] = true;
			return $arRes;
		}

		if ((int)$arParams["PRODUCT_ID"] <= 0)
		{
			$APPLICATION->ThrowException(Loc::getMessage("RSRV_INCORRECT_ID"), "NO_ORDER_ID");
			$arRes["RESULT"] = false;
			return $arRes;
		}

		$arParams["QUANTITY"] = doubleval($arParams["QUANTITY"]);

		if ((string)$arParams["UNDO_DEDUCTION"] != "Y")
			$arParams["UNDO_DEDUCTION"] = "N";

		if ((string)$arParams["EMULATE"] != "Y")
			$arParams["EMULATE"] = "N";

		if ((string)$arParams["PRODUCT_RESERVED"] != "Y")
			$arParams["PRODUCT_RESERVED"] = "N";

		if (!isset($arParams["STORE_DATA"]))
			$arParams["STORE_DATA"] = array();

		if (!is_array($arParams["STORE_DATA"]))
			$arParams["STORE_DATA"] = array($arParams["STORE_DATA"]);

		$rsProducts = CCatalogProduct::GetList(
			array(),
			array('ID' => $arParams["PRODUCT_ID"]),
			false,
			false,
			array('ID', 'QUANTITY', 'QUANTITY_RESERVED', 'QUANTITY_TRACE', 'CAN_BUY_ZERO', 'NEGATIVE_AMOUNT_TRACE')
		);

		if ($arProduct = $rsProducts->Fetch())
		{
			if ($arParams["UNDO_DEDUCTION"] == "N")
			{
				if ($arParams["EMULATE"] == "Y" || $arProduct["QUANTITY_TRACE"] == "N")
				{
					$arRes["RESULT"] = true;
				}
				else
				{
					if ($strUseStoreControl == "Y")
					{
						if (!empty($arParams["STORE_DATA"]))
						{
							$totalAmount = 0;
							foreach ($arParams["STORE_DATA"] as $id => $arRecord)
							{
								if (!isset($arRecord["STORE_ID"]) || intval($arRecord["STORE_ID"]) < 0 || !isset($arRecord["QUANTITY"]) || intval($arRecord["QUANTITY"]) < 0)
								{
									$APPLICATION->ThrowException(Loc::getMessage("DDCT_DEDUCTION_STORE_ERROR", self::GetProductCatalogInfo($arParams["PRODUCT_ID"])), "DDCT_DEDUCTION_STORE_ERROR");
									$arRes["RESULT"] = false;
									return $arRes;
								}

								$rsProps = CCatalogStoreProduct::GetList(
									array(),
									array(
										"PRODUCT_ID" => $arParams["PRODUCT_ID"],
										"STORE_ID" => $arRecord["STORE_ID"]
									),
									false,
									false,
									array('ID', 'AMOUNT')
								);
								if ($arProp = $rsProps->Fetch())
								{
									if ($arProp["AMOUNT"] < $arRecord["QUANTITY"])
									{
										$APPLICATION->ThrowException(
											Loc::getMessage(
												"DDCT_DEDUCTION_QUANTITY_STORE_ERROR",
												array_merge(self::GetProductCatalogInfo($arParams["PRODUCT_ID"]), array("#STORE_ID#" => $arRecord["STORE_ID"]))
											),
											"DDCT_DEDUCTION_QUANTITY_STORE_ERROR"
										);
										$arRes["RESULT"] = false;
										return $arRes;
									}
									else
									{
										$res = CCatalogStoreProduct::Update($arProp["ID"], array("AMOUNT" => $arProp["AMOUNT"] - $arRecord["QUANTITY"]));

										if ($res)
										{
											$arRes["STORES"][$arRecord["STORE_ID"]] = $arRecord["QUANTITY"];
											$totalAmount += $arRecord["QUANTITY"];

											//deleting barcodes
											if (isset($arRecord["BARCODE"]) && is_array($arRecord["BARCODE"]) && count($arRecord["BARCODE"]) > 0)
											{
												foreach ($arRecord["BARCODE"] as $barcodeId => $barcodeValue)
												{
													$arFields = array(
														"STORE_ID" => $arRecord["STORE_ID"],
														"BARCODE" => $barcodeValue,
														"PRODUCT_ID" => $arParams["PRODUCT_ID"]
													);

													$dbres = CCatalogStoreBarcode::GetList(
														array(),
														$arFields,
														false,
														false,
														array("ID", "STORE_ID", "BARCODE", "PRODUCT_ID")
													);

													if ($arRes = $dbres->Fetch())
													{
														CCatalogStoreBarcode::Delete($arRes["ID"]);
													}
													else
													{
														$APPLICATION->ThrowException(
															Loc::getMessage(
																"DDCT_DEDUCTION_BARCODE_ERROR",
																array_merge(self::GetProductCatalogInfo($arParams["PRODUCT_ID"]), array("#BARCODE#" => $barcodeValue))
															),
															"DDCT_DEDUCTION_BARCODE_ERROR"
														);
														$arRes["RESULT"] = false;
														return $arRes;
													}
												}
											}
										}
										else
										{
											$APPLICATION->ThrowException(Loc::getMessage("DDCT_DEDUCTION_SAVE_ERROR", self::GetProductCatalogInfo($arParams["PRODUCT_ID"])), "DDCT_DEDUCTION_SAVE_ERROR");
											$arRes["RESULT"] = false;
											return $arRes;
										}
									}
								}
							}

							//updating total sum
							if ($arParams["PRODUCT_RESERVED"] == "Y")
							{
								if ($totalAmount <= $arProduct["QUANTITY_RESERVED"])
								{
									$arFields["QUANTITY_RESERVED"] = $arProduct["QUANTITY_RESERVED"] - $totalAmount;
								}
								else if ($totalAmount <= $arProduct["QUANTITY_RESERVED"] + $arProduct["QUANTITY"])
								{
									$arFields["QUANTITY_RESERVED"] = 0;
									$arFields["QUANTITY"] = $arProduct["QUANTITY"] - ($totalAmount - $arProduct["QUANTITY_RESERVED"]);
								}
								else //not enough products - don't deduct anything
								{
									$arRes["RESULT"] = false;
									return $arRes;
								}
							}
							else //product not reserved, use main quantity field to deduct from, quantity_reserved only if there is shortage in the main field
							{
								if ($totalAmount <= $arProduct["QUANTITY"])
								{
									$arFields["QUANTITY"] = $arProduct["QUANTITY"] - $totalAmount;
								}
								else if ($totalAmount <= $arProduct["QUANTITY_RESERVED"] + $arProduct["QUANTITY"])
								{
									$arFields["QUANTITY"] = 0;
									$arFields["QUANTITY_RESERVED"] = $arProduct["QUANTITY_RESERVED"] - ($totalAmount - $arProduct["QUANTITY"]);
								}
								else //not enough products - don't deduct anything
								{
									$arRes["RESULT"] = false;
									return $arRes;
								}
							}

							CCatalogProduct::Update($arParams["PRODUCT_ID"], $arFields);
							if (isset($arFields['QUANTITY']) && self::isNeedClearPublicCache(
								$arProduct['QUANTITY'],
								$arFields['QUANTITY'],
								$arProduct['QUANTITY_TRACE'],
								$arProduct['CAN_BUY_ZERO']
							))
							{
								$productInfo = array(
									'CAN_BUY_ZERO' => $arProduct['CAN_BUY_ZERO'],
									'NEGATIVE_AMOUNT_TRACE' => $arProduct['NEGATIVE_AMOUNT_TRACE'],
									'QUANTITY_TRACE' => $arProduct['QUANTITY_TRACE'],
									'OLD_QUANTITY' => $arProduct['QUANTITY'],
									'QUANTITY' => $arFields['QUANTITY'],
									'DELTA' => $arFields['QUANTITY'] - $arProduct['QUANTITY']
								);
								self::clearPublicCache($arProduct['ID'], $productInfo);
							}

							$arRes["RESULT"] = true;
						}
						else
						{
							$APPLICATION->ThrowException(Loc::getMessage("DDCT_DEDUCTION_STORE_ERROR", self::GetProductCatalogInfo($arParams["PRODUCT_ID"])), "DEDUCTION_STORE_ERROR1");
							$arRes["RESULT"] = false;
							return $arRes;
						}
					}
					else // store control not used
					{
						if ($arParams["QUANTITY"] <= $arProduct["QUANTITY_RESERVED"] + $arProduct["QUANTITY"])
						{
							if ($arParams["PRODUCT_RESERVED"] == "Y")
							{
								if ($arParams["QUANTITY"] <= $arProduct["QUANTITY_RESERVED"])
								{
									$arFields["QUANTITY_RESERVED"] = $arProduct["QUANTITY_RESERVED"] - $arParams["QUANTITY"];
								}
								else
								{
									$arFields["QUANTITY_RESERVED"] = 0;
									$arFields["QUANTITY"] = $arProduct["QUANTITY"] - ($arParams["QUANTITY"] - $arProduct["QUANTITY_RESERVED"]);
								}
							}
							else //product not reserved, use main quantity field to deduct from, quantity_reserved only if there is shortage in the main field
							{
								if ($arParams["QUANTITY"] <= $arProduct["QUANTITY"])
								{
									$arFields["QUANTITY"] = $arProduct["QUANTITY"] - $arParams["QUANTITY"];
								}
								else
								{
									$arFields["QUANTITY"] = 0;
									$arFields["QUANTITY_RESERVED"] = $arProduct["QUANTITY_RESERVED"] - ($arParams["QUANTITY"] - $arProduct["QUANTITY"]);
								}
							}

							$arRes["RESULT"] = CCatalogProduct::Update($arParams["PRODUCT_ID"], $arFields);
							if (isset($arFields['QUANTITY']) && self::isNeedClearPublicCache(
									$arProduct['QUANTITY'],
									$arFields['QUANTITY'],
									$arProduct['QUANTITY_TRACE'],
									$arProduct['CAN_BUY_ZERO']
								))
							{
								$productInfo = array(
									'CAN_BUY_ZERO' => $arProduct['CAN_BUY_ZERO'],
									'NEGATIVE_AMOUNT_TRACE' => $arProduct['NEGATIVE_AMOUNT_TRACE'],
									'QUANTITY_TRACE' => $arProduct['QUANTITY_TRACE'],
									'OLD_QUANTITY' => $arProduct['QUANTITY'],
									'QUANTITY' => $arFields['QUANTITY'],
									'DELTA' => $arFields['QUANTITY'] - $arProduct['QUANTITY']
								);
								self::clearPublicCache($arProduct['ID'], $productInfo);
							}
						}
						else //not enough products - don't deduct anything
						{
							$APPLICATION->ThrowException(Loc::getMessage("DDCT_DEDUCTION_QUANTITY_ERROR", self::GetProductCatalogInfo($arParams["PRODUCT_ID"])), "DDCT_DEDUCTION_QUANTITY_ERROR");
							$arRes["RESULT"] = false;
							return $arRes;
						}

					} //store control
				} //emulate /quantity trace
			}
			else //undo deduction
			{
				if ($arParams["EMULATE"] == "Y" || $arProduct["QUANTITY_TRACE"] == "N")
				{
					$arRes["RESULT"] = true;
				}
				else
				{
					if ($strUseStoreControl == "Y")
					{
						if (!empty($arParams["STORE_DATA"]))
						{
							$totalAddedAmount = 0;
							foreach ($arParams["STORE_DATA"] as $id => $arRecord)
							{
								$rsProps = CCatalogStoreProduct::GetList(
									array(),
									array(
										"PRODUCT_ID" => $arParams["PRODUCT_ID"],
										"STORE_ID" => $arRecord["STORE_ID"]
									),
									false,
									false,
									array('ID', 'AMOUNT')
								);

								if ($arProp = $rsProps->Fetch())
								{
									$res = CCatalogStoreProduct::Update(
										$arProp["ID"],
										array("AMOUNT" => $arProp["AMOUNT"] + $arRecord["QUANTITY"])
									);

									if ($res)
									{
										$arRes["STORES"][$arRecord["STORE_ID"]] = $arRecord["QUANTITY"];
										$totalAddedAmount += $arRecord["QUANTITY"];

										//adding barcodes
										if (isset($arRecord["BARCODE"]) && strlen($arRecord["BARCODE"]) > 0)
										{
											$arFields = array(
												"STORE_ID" => $arRecord["STORE_ID"],
												"BARCODE" => $arRecord["BARCODE"],
												"PRODUCT_ID" => $arParams["PRODUCT_ID"]
											);

											CCatalogStoreBarcode::Add($arFields);
										}
									}
									else
									{
										$APPLICATION->ThrowException(Loc::getMessage("DDCT_DEDUCTION_SAVE_ERROR", self::GetProductCatalogInfo($arParams["PRODUCT_ID"])), "DDCT_DEDUCTION_SAVE_ERROR");
										$arRes["RESULT"] = false;
										return $arRes;
									}
								}
							}

							// $dbAmount = $DB->Query("SELECT SUM(AMOUNT) as AMOUNT FROM b_catalog_store_product WHERE PRODUCT_ID = ".$arParams["PRODUCT_ID"]." ", true);
							// if ($totalAddedAmount = $dbAmount->Fetch())
							// {
							// }
							if ($arParams["PRODUCT_RESERVED"] == "Y")
							{
								$arUpdateFields["QUANTITY_RESERVED"] = $arProduct["QUANTITY_RESERVED"] + $totalAddedAmount;
							}
							else
							{
								$arUpdateFields["QUANTITY"] = $arProduct["QUANTITY"] + $totalAddedAmount;
							}

							CCatalogProduct::Update($arParams["PRODUCT_ID"], $arUpdateFields);
							if (isset($arUpdateFields['QUANTITY']) && self::isNeedClearPublicCache(
									$arProduct['QUANTITY'],
									$arUpdateFields['QUANTITY'],
									$arProduct['QUANTITY_TRACE'],
									$arProduct['CAN_BUY_ZERO']
								))
							{
								$productInfo = array(
									'CAN_BUY_ZERO' => $arProduct['CAN_BUY_ZERO'],
									'NEGATIVE_AMOUNT_TRACE' => $arProduct['NEGATIVE_AMOUNT_TRACE'],
									'QUANTITY_TRACE' => $arProduct['QUANTITY_TRACE'],
									'OLD_QUANTITY' => $arProduct['QUANTITY'],
									'QUANTITY' => $arUpdateFields['QUANTITY'],
									'DELTA' => $arUpdateFields['QUANTITY'] - $arProduct['QUANTITY']
								);
								self::clearPublicCache($arProduct['ID'], $productInfo);
							}

							$arRes["RESULT"] = true;
						}
						else
						{
							$APPLICATION->ThrowException(Loc::getMessage("DDCT_DEDUCTION_STORE_ERROR", self::GetProductCatalogInfo($arParams["PRODUCT_ID"])), "DEDUCTION_STORE_ERROR2");
							$arRes["RESULT"] = false;
							return $arRes;
						}
					}
					else //store control not used
					{
						if ($arParams["PRODUCT_RESERVED"] == "Y")
						{
							$arFields["QUANTITY_RESERVED"] = $arProduct["QUANTITY_RESERVED"] + $arParams["QUANTITY"];
							// $arFields["QUANTITY"] = $arProduct["QUANTITY"] - $arParams["QUANTITY_RESERVED"];
						}
						else
						{
							$arFields["QUANTITY"] = $arProduct["QUANTITY"] + $arParams["QUANTITY"];
							// $arFields["QUANTITY_RESERVED"] = $arProduct["QUANTITY_RESERVED"] - $arParams["QUANTITY_RESERVED"];
						}

						$arRes["RESULT"] = CCatalogProduct::Update($arParams["PRODUCT_ID"], $arFields);
						if (isset($arFields['QUANTITY']) && self::isNeedClearPublicCache(
								$arProduct['QUANTITY'],
								$arFields['QUANTITY'],
								$arProduct['QUANTITY_TRACE'],
								$arProduct['CAN_BUY_ZERO']
							))
						{
							$productInfo = array(
								'CAN_BUY_ZERO' => $arProduct['CAN_BUY_ZERO'],
								'NEGATIVE_AMOUNT_TRACE' => $arProduct['NEGATIVE_AMOUNT_TRACE'],
								'QUANTITY_TRACE' => $arProduct['QUANTITY_TRACE'],
								'OLD_QUANTITY' => $arProduct['QUANTITY'],
								'QUANTITY' => $arFields['QUANTITY'],
								'DELTA' => $arFields['QUANTITY'] - $arProduct['QUANTITY']
							);
							self::clearPublicCache($arProduct['ID'], $productInfo);
						}
					}
				} //emulate or quantity trace
			}
		}
		else
		{
			$arRes["RESULT"] = false;
		}

		if (!$arRes["RESULT"])
		{
			$APPLICATION->ThrowException(Loc::getMessage("DDCT_UNKNOWN_ERROR", self::GetProductCatalogInfo($arParams["PRODUCT_ID"])), "UNKNOWN_DEDUCTION_ERROR");
		}

		return $arRes;
	}

	public static function GetStoresCount($arParams = array())
	{
		$strUseStoreControl = COption::GetOptionString('catalog','default_use_store_control');

		//without store control stores are used for information purposes only
		if ($strUseStoreControl == "N")
			return -1;

		$arFilter = array("ACTIVE" => "Y", "SHIPPING_CENTER" => "Y");

		if (isset($arParams["SITE_ID"]) && strlen($arParams["SITE_ID"]) > 0)
			$arFilter["+SITE_ID"] = $arParams["SITE_ID"];

		$arStoreID = array();
		$dbStoreRes = CCatalogStore::GetList(
			array("SORT" => "DESC"),
			$arFilter,
			false,
			false,
			array("ID", "ACTIVE")
		);

		while ($arStoreRes = $dbStoreRes->GetNext())
			$arStoreID[] = $arStoreRes["ID"];

		return count($arStoreID);
	}

	public static function GetProductStores($arParams)
	{
		$strUseStoreControl = COption::GetOptionString('catalog','default_use_store_control');

		//without store control stores are used for information purposes only and manual deduction won't work
		if ($strUseStoreControl == "N")
			return false;

		$arResult = array();
		$arStoreID = array();

		if (intval($arParams["PRODUCT_ID"] < 0))
			return false;

		$arFilter = array("ACTIVE" => "Y", "SHIPPING_CENTER" => "Y");

		if (isset($arParams["SITE_ID"]) && strlen($arParams["SITE_ID"]) > 0)
			$arFilter["+SITE_ID"] = $arParams["SITE_ID"];

		$dbStoreRes = CCatalogStore::GetList(
			array("SORT" => "DESC"),
			$arFilter,
			false,
			false,
			array("ID", "ACTIVE")
		);
		while ($arStoreRes = $dbStoreRes->Fetch())
			$arStoreID[] = $arStoreRes["ID"];

		$dbRes = CCatalogStoreProduct::GetList(
			array(),
			array(
				"PRODUCT_ID" => $arParams["PRODUCT_ID"],
				// ">AMOUNT" => "0"
			),
			false,
			false,
			array("STORE_NAME", "STORE_ID", "AMOUNT", "PRODUCT_ID")
		);
		while ($arRes = $dbRes->Fetch())
		{
			if (in_array($arRes["STORE_ID"], $arStoreID))
			{
				if(isset($arParams["ENUM_BY_ID"]) && $arParams["ENUM_BY_ID"] == true)
					$arResult[$arRes["STORE_ID"]] = $arRes;
				else
					$arResult[] = $arRes;
			}
		}

		return $arResult;
	}

	public static function CheckProductBarcode($arParams)
	{
		$result = false;

		$arFilter = array(
			"PRODUCT_ID" => $arParams["PRODUCT_ID"],
			"BARCODE"	 => $arParams["BARCODE"]
		);

		if (isset($arParams["STORE_ID"]))
			$arFilter["STORE_ID"] = intval($arParams["STORE_ID"]);

		$dbres = CCatalogStoreBarcode::GetList(
			array(),
			$arFilter
		);
		if ($res = $dbres->GetNext())
			$result = true;

		return $result;
	}

	private static function GetProductCatalogInfo($productID)
	{
		$productID = (int)$productID;
		if ($productID <= 0)
			return array();

		$dbProduct = CIBlockElement::GetList(array(), array("ID" => $productID), false, false, array('ID', 'IBLOCK_ID', 'NAME'));
		if ($arProduct = $dbProduct->Fetch())
		{
			if ($arProduct["IBLOCK_ID"] > 0)
				$arProduct["EDIT_PAGE_URL"] = CIBlock::GetAdminElementEditLink($arProduct["IBLOCK_ID"], $productID);
		}

		return array(
			"#PRODUCT_ID#"   => $arProduct["ID"],
			"#PRODUCT_NAME#" => $arProduct["NAME"],
		);
	}

	public static function GetSetItems($productID, $intType, $arProducInfo = array())
	{
		$arProductId = array();
		$arSets = CCatalogProductSet::getAllSetsByProduct($productID, $intType);

		if (is_array($arSets))
		{
			foreach ($arSets as $k => $arSet)
			{
				foreach ($arSet["ITEMS"] as $k1 => $item)
				{
					$arItem = self::GetProductData(array("PRODUCT_ID" => $item["ITEM_ID"], "QUANTITY" => $item["QUANTITY"], "CHECK_QUANTITY" => "N", "CHECK_PRICE" => "N"));

					$arItem["PRODUCT_ID"] = $item["ITEM_ID"];
					$arItem["MODULE"] = "catalog";
					$arItem["PRODUCT_PROVIDER_CLASS"] = "CCatalogProductProvider";
					if ($intType == CCatalogProductSet::TYPE_SET)
					{
						$arItem['SET_DISCOUNT_PERCENT'] = ($item['DISCOUNT_PERCENT'] == '' ? false : (float)$item['DISCOUNT_PERCENT']);
					}

					$arProductId[] = $item["ITEM_ID"];

					$arItem["PROPS"] = array();
					$arParentSku = CCatalogSku::GetProductInfo($item["ITEM_ID"]);
					if (!empty($arParentSku))
					{
						$arPropsSku = array();

						$dbProduct = CIBlockElement::GetList(array(), array("ID" => $item["ITEM_ID"]), false, false, array('IBLOCK_ID', 'IBLOCK_SECTION_ID'));
						$arProduct = $dbProduct->Fetch();

						$dbOfferProperties = CIBlock::GetProperties($arProduct["IBLOCK_ID"], array(), array("!XML_ID" => "CML2_LINK"));
						while($arOfferProperties = $dbOfferProperties->Fetch())
							$arPropsSku[] = $arOfferProperties["CODE"];

						$product_properties = CIBlockPriceTools::GetOfferProperties(
							$item["ITEM_ID"],
							$arParentSku["IBLOCK_ID"],
							$arPropsSku
						);

						foreach ($product_properties as $propData)
						{
							$arItem["PROPS"][] = array(
								"NAME" => $propData["NAME"],
								"CODE" => $propData["CODE"],
								"VALUE" => $propData["VALUE"],
								"SORT" => $propData["SORT"]
							);
						}
					}

					$arSets[$k]["ITEMS"][$k1] = array_merge($item, $arItem);
				}
			}

			$rsProducts = CIBlockElement::GetList(
				array(),
				array('ID' => $arProductId),
				false,
				false,
				array("ID", "IBLOCK_ID", "IBLOCK_SECTION_ID", "PREVIEW_PICTURE", "DETAIL_PICTURE", "IBLOCK_TYPE_ID", "XML_ID")
			);
			while ($arProduct = $rsProducts->GetNext())
			{
				foreach ($arSets as $k => $arSet)
				{
					foreach ($arSet["ITEMS"] as $k1 => $item)
					{
						if ($item["ITEM_ID"] == $arProduct["ID"])
						{
							$arProps = array();
							$strIBlockXmlID = strval(CIBlock::GetArrayByID($arProduct['IBLOCK_ID'], 'XML_ID'));
							if ($strIBlockXmlID != "")
							{
								$arProps[] = array(
									"NAME" => "Catalog XML_ID",
									"CODE" => "CATALOG.XML_ID",
									"VALUE" => $strIBlockXmlID
								);
							}

							$arProps[] = array(
								"NAME" => "Product XML_ID",
								"CODE" => "PRODUCT.XML_ID",
								"VALUE" => $arProduct["XML_ID"]
							);

							$arSets["$k"]["ITEMS"][$k1]["IBLOCK_ID"] = $arProduct["IBLOCK_ID"];
							$arSets["$k"]["ITEMS"][$k1]["IBLOCK_SECTION_ID"] = $arProduct["IBLOCK_SECTION_ID"];
							$arSets["$k"]["ITEMS"][$k1]["PREVIEW_PICTURE"] = $arProduct["PREVIEW_PICTURE"];
							$arSets["$k"]["ITEMS"][$k1]["DETAIL_PICTURE"] = $arProduct["DETAIL_PICTURE"];
							$arSets["$k"]["ITEMS"][$k1]["PROPS"] = array_merge($arSets["$k"]["ITEMS"][$k1]["PROPS"], $arProps);
						}
					}
				}
			}
		}

		foreach(GetModuleEvents("sale", "OnGetSetItems", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$arSets));

		return $arSets;
	}

	protected static function isNeedClearPublicCache($currentQuantity, $newQuantity, $quantityTrace, $canBuyZero, $ratio = 1)
	{
		if (!defined('BX_COMP_MANAGED_CACHE'))
			return false;
		if ($canBuyZero == 'Y' || $quantityTrace == 'N')
			return false;
		if ($currentQuantity * $newQuantity > 0)
			return false;
		return true;
	}

	protected static function clearPublicCache($productID, $productInfo = array())
	{
		$productID = (int)$productID;
		if ($productID <= 0)
			return;
		$iblockID = (int)(isset($productInfo['IBLOCK_ID']) ? $productInfo['IBLOCK_ID'] : CIBlockElement::GetIBlockByID($productID));
		if ($iblockID <= 0)
			return;
		if (defined('BX_COMP_MANAGED_CACHE') && !isset(self::$clearAutoCache[$iblockID]))
		{
			$taggedCache = \Bitrix\Main\Application::getInstance()->getTaggedCache();
			$taggedCache->clearByTag('iblock_id_'.$iblockID);
			self::$clearAutoCache[$iblockID] = true;
		}

		$productInfo['ID'] = $productID;
		$productInfo['ELEMENT_IBLOCK_ID'] = $iblockID;
		$productInfo['IBLOCK_ID'] = $iblockID;
		foreach (GetModuleEvents('catalog', 'OnProductQuantityTrace', true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($productID, $productInfo));
	}

	protected static function getUserGroups($userId)
	{
		$userId = (int)$userId;
		if ($userId < 0)
			return false;
		if (!isset(self::$userCache[$userId]))
		{
			if ($userId == 0)
			{
				self::$userCache[$userId] = array(2);
			}
			else
			{
				self::$userCache[$userId] = false;
				$userIterator = Main\UserTable::getList(array(
					'select' => array('ID'),
					'filter' => array('=ID' => $userId)
				));
				if ($user = $userIterator->fetch())
				{
					$user['ID'] = (int)$user['ID'];
					self::$userCache[$user['ID']] = CUser::GetUserGroup($user['ID']);
				}
				unset($user, $userIterator);
			}
		}
		return self::$userCache[$userId];
	}
}