<?
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Collection;
use Bitrix\Iblock;
use Bitrix\Catalog;

Loc::loadMessages(__FILE__);

class CAllCatalogSKU
{
	const TYPE_CATALOG = 'D';
	const TYPE_PRODUCT = 'P';
	const TYPE_OFFERS = 'O';
	const TYPE_FULL = 'X';

	static protected $arOfferCache = array();
	static protected $arProductCache = array();
	static protected $arPropertyCache = array();
	static protected $arIBlockCache = array();

	public static function GetCatalogTypes($boolFull = false)
	{
		$boolFull = ($boolFull === true);
		if ($boolFull)
		{
			return array(
				self::TYPE_CATALOG => Loc::getMessage('BT_CAT_SKU_TYPE_CATALOG'),
				self::TYPE_PRODUCT => Loc::getMessage('BT_CAT_SKU_TYPE_PRODUCT'),
				self::TYPE_OFFERS => Loc::getMessage('BT_CAT_SKU_TYPE_OFFERS'),
				self::TYPE_FULL => Loc::getMessage('BT_CAT_SKU_TYPE_FULL')
			);
		}
		return array(
			self::TYPE_CATALOG,
			self::TYPE_PRODUCT,
			self::TYPE_OFFERS,
			self::TYPE_FULL
		);
	}

	public static function GetProductInfo($intOfferID, $intIBlockID = 0)
	{
		$intOfferID = (int)$intOfferID;
		if ($intOfferID <= 0)
			return false;

		$intIBlockID = (int)$intIBlockID;
		if ($intIBlockID <= 0)
		{
			$intIBlockID = (int)CIBlockElement::GetIBlockByID($intOfferID);
		}
		if ($intIBlockID <= 0)
			return false;

		if (!isset(self::$arOfferCache[$intIBlockID]))
		{
			$arSkuInfo = CCatalogSKU::GetInfoByOfferIBlock($intIBlockID);
		}
		else
		{
			$arSkuInfo = self::$arOfferCache[$intIBlockID];
		}
		if (empty($arSkuInfo) || empty($arSkuInfo['SKU_PROPERTY_ID']))
			return false;

		$rsItems = CIBlockElement::GetProperty(
			$intIBlockID,
			$intOfferID,
			array(),
			array('ID' => $arSkuInfo['SKU_PROPERTY_ID'])
		);
		if ($arItem = $rsItems->Fetch())
		{
			$arItem['VALUE'] = (int)$arItem['VALUE'];
			if ($arItem['VALUE'] > 0)
			{
				return array(
					'ID' => $arItem['VALUE'],
					'IBLOCK_ID' => $arSkuInfo['PRODUCT_IBLOCK_ID'],
					'OFFER_IBLOCK_ID' => $intIBlockID,
					'SKU_PROPERTY_ID' => $arSkuInfo['SKU_PROPERTY_ID']
				);
			}
		}
		return false;
	}

	public static function GetInfoByOfferIBlock($intIBlockID)
	{
		$intIBlockID = (int)$intIBlockID;
		if ($intIBlockID <= 0)
			return false;

		if (!isset(self::$arOfferCache[$intIBlockID]))
		{
			self::$arOfferCache[$intIBlockID] = false;
			$iblockIterator = Catalog\CatalogIblockTable::getList(array(
				'select' => array('IBLOCK_ID', 'PRODUCT_IBLOCK_ID', 'SKU_PROPERTY_ID', 'VERSION' => 'IBLOCK.VERSION'),
				'filter' => array('=IBLOCK_ID' => $intIBlockID, '!=PRODUCT_IBLOCK_ID' => 0)
			));
			$arResult = $iblockIterator->fetch();
			if (!empty($arResult))
			{
				$arResult['IBLOCK_ID'] = (int)$arResult['IBLOCK_ID'];
				$arResult['PRODUCT_IBLOCK_ID'] = (int)$arResult['PRODUCT_IBLOCK_ID'];
				$arResult['SKU_PROPERTY_ID'] = (int)$arResult['SKU_PROPERTY_ID'];
				$arResult['VERSION'] = (int)$arResult['VERSION'];
				self::$arOfferCache[$arResult['IBLOCK_ID']] = $arResult;
				self::$arProductCache[$arResult['PRODUCT_IBLOCK_ID']] = $arResult;
				self::$arPropertyCache[$arResult['SKU_PROPERTY_ID']] = $arResult;
			}
		}
		else
		{
			$arResult = self::$arOfferCache[$intIBlockID];
		}
		return $arResult;
	}

	public static function GetInfoByProductIBlock($intIBlockID)
	{
		$intIBlockID = (int)$intIBlockID;
		if ($intIBlockID <= 0)
			return false;
		if (!isset(self::$arProductCache[$intIBlockID]))
		{
			self::$arProductCache[$intIBlockID] = false;
			$iblockIterator = Catalog\CatalogIblockTable::getList(array(
				'select' => array('IBLOCK_ID', 'PRODUCT_IBLOCK_ID', 'SKU_PROPERTY_ID', 'VERSION' => 'IBLOCK.VERSION'),
				'filter' => array('=PRODUCT_IBLOCK_ID' => $intIBlockID)
			));
			$arResult = $iblockIterator->fetch();
			if (!empty($arResult))
			{
				$arResult['IBLOCK_ID'] = (int)$arResult['IBLOCK_ID'];
				$arResult['PRODUCT_IBLOCK_ID'] = (int)$arResult['PRODUCT_IBLOCK_ID'];
				$arResult['SKU_PROPERTY_ID'] = (int)$arResult['SKU_PROPERTY_ID'];
				$arResult['VERSION'] = (int)$arResult['VERSION'];
				self::$arProductCache[$arResult['PRODUCT_IBLOCK_ID']] = $arResult;
				self::$arOfferCache[$arResult['IBLOCK_ID']] = $arResult;
				self::$arPropertyCache[$arResult['SKU_PROPERTY_ID']] = $arResult;
			}
		}
		else
		{
			$arResult = self::$arProductCache[$intIBlockID];
		}
		return $arResult;
	}

	public static function GetInfoByLinkProperty($intPropertyID)
	{
		$intPropertyID = (int)$intPropertyID;
		if ($intPropertyID <= 0)
			return false;
		if (!isset(self::$arPropertyCache[$intPropertyID]))
		{
			self::$arPropertyCache[$intPropertyID] = false;
			$iblockIterator = Catalog\CatalogIblockTable::getList(array(
				'select' => array('IBLOCK_ID', 'PRODUCT_IBLOCK_ID', 'SKU_PROPERTY_ID', 'VERSION' => 'IBLOCK.VERSION'),
				'filter' => array('=SKU_PROPERTY_ID' => $intPropertyID)
			));
			$arResult = $iblockIterator->fetch();
			if (!empty($arResult))
			{
				$arResult['IBLOCK_ID'] = (int)$arResult['IBLOCK_ID'];
				$arResult['PRODUCT_IBLOCK_ID'] = (int)$arResult['PRODUCT_IBLOCK_ID'];
				$arResult['SKU_PROPERTY_ID'] = (int)$arResult['SKU_PROPERTY_ID'];
				$arResult['VERSION'] = (int)$arResult['VERSION'];
				self::$arPropertyCache[$arResult['SKU_PROPERTY_ID']] = $arResult;
				self::$arProductCache[$arResult['PRODUCT_IBLOCK_ID']] = $arResult;
				self::$arOfferCache[$arResult['IBLOCK_ID']] = $arResult;
			}
		}
		else
		{
			$arResult = self::$arPropertyCache[$intPropertyID];
		}
		return $arResult;
	}

	public static function GetInfoByIBlock($intIBlockID)
	{
	}

/*
* @deprecated deprecated since catalog 15.0.1
* @see CCatalogSKU::getExistOffers()
*/
	public static function IsExistOffers($intProductID, $intIBlockID = 0)
	{
		$result = self::getExistOffers($intProductID, $intIBlockID);
		return !empty($result[$intProductID]);
	}

	public static function getExistOffers($productID, $iblockID = 0)
	{
		$iblockID = (int)$iblockID;
		if (!is_array($productID))
			$productID = array($productID);
		Collection::normalizeArrayValuesByInt($productID);
		if (empty($productID))
			return false;
		$iblockProduct = array();
		$iblockSku = array();
		if ($iblockID == 0)
		{
			$iblockList = array();
			$elementIterator = Iblock\ElementTable::getList(array(
				'select' => array('ID', 'IBLOCK_ID'),
				'filter' => array('@ID' => $productID)
			));
			while ($element = $elementIterator->fetch())
			{
				$element['ID'] = (int)$element['ID'];
				$element['IBLOCK_ID'] = (int)$element['IBLOCK_ID'];
				if (!isset($iblockList[$element['IBLOCK_ID']]))
					$iblockList[$element['IBLOCK_ID']] = array();
				$iblockList[$element['IBLOCK_ID']][] = $element['ID'];
			}
			unset($element, $elementIterator);
			if (!empty($iblockList))
			{
				$iblockIterator = Catalog\CatalogIblockTable::getList(array(
					'select' => array('IBLOCK_ID', 'PRODUCT_IBLOCK_ID', 'SKU_PROPERTY_ID', 'VERSION' => 'IBLOCK.VERSION'),
					'filter' => array('@PRODUCT_IBLOCK_ID' => array_keys($iblockList))
				));
				while ($iblock = $iblockIterator->fetch())
				{
					$iblock['IBLOCK_ID'] = (int)$iblock['IBLOCK_ID'];
					$iblock['PRODUCT_IBLOCK_ID'] = (int)$iblock['PRODUCT_IBLOCK_ID'];
					$iblock['SKU_PROPERTY_ID'] = (int)$iblock['SKU_PROPERTY_ID'];
					$iblock['VERSION'] = (int)$iblock['VERSION'];
					$iblockSku[$iblock['PRODUCT_IBLOCK_ID']] = $iblock;
					unset($iblock['VERSION']);
					self::$arProductCache[$iblock['PRODUCT_IBLOCK_ID']] = $iblock;
					self::$arOfferCache[$iblock['IBLOCK_ID']] = $iblock;
					$iblockProduct[$iblock['PRODUCT_IBLOCK_ID']] = $iblockList[$iblock['PRODUCT_IBLOCK_ID']];
				}
				unset($iblock, $iblockIterator);
			}
			unset($iblockList);
		}
		else
		{
			$iblockIterator = Catalog\CatalogIblockTable::getList(array(
				'select' => array('IBLOCK_ID', 'PRODUCT_IBLOCK_ID', 'SKU_PROPERTY_ID', 'VERSION' => 'IBLOCK.VERSION'),
				'filter' => array('=PRODUCT_IBLOCK_ID' => $iblockID)
			));
			if ($iblock = $iblockIterator->fetch())
			{
				$iblock['IBLOCK_ID'] = (int)$iblock['IBLOCK_ID'];
				$iblock['PRODUCT_IBLOCK_ID'] = (int)$iblock['PRODUCT_IBLOCK_ID'];
				$iblock['SKU_PROPERTY_ID'] = (int)$iblock['SKU_PROPERTY_ID'];
				$iblock['VERSION'] = (int)$iblock['VERSION'];
				$iblockSku[$iblock['PRODUCT_IBLOCK_ID']] = $iblock;
				unset($iblock['VERSION']);
				self::$arProductCache[$iblock['PRODUCT_IBLOCK_ID']] = $iblock;
				self::$arOfferCache[$iblock['IBLOCK_ID']] = $iblock;
				$iblockProduct[$iblockID] = $productID;
			}
			unset($iblock, $iblockIterator);
		}
		if (empty($iblockProduct))
			return array();

		$conn = Application::getConnection();
		$helper = $conn->getSqlHelper();

		$result = array_fill_keys($productID, false);
		foreach ($iblockProduct as $iblockID => $productID)
		{
			$sku = $iblockSku[$iblockID];
			if ($sku['VERSION'] == 2)
			{
				$productField = $helper->quote('PROPERTY_'.$sku['SKU_PROPERTY_ID']);
				$sqlQuery = 'select '.$productField.' as PRODUCT_ID, COUNT(*) as CNT from '.$helper->quote('b_iblock_element_prop_s'.$sku['IBLOCK_ID']).
				' where '.$productField.' IN ('.implode(',', $productID).')'.
				' group by '.$productField;

			}
			else
			{
				$productField = $helper->quote('VALUE_NUM');
				$sqlQuery = 'select '.$productField.' as PRODUCT_ID, COUNT(*) as CNT from '.$helper->quote('b_iblock_element_property').
				' where '.$helper->quote('IBLOCK_PROPERTY_ID').' = '.$sku['SKU_PROPERTY_ID'].
				' and '.$productField.' IN ('.implode(',', $productID).')'.
				' group by '.$productField;
			}
			unset($productField);
			$productIterator = $conn->query($sqlQuery);
			while ($product = $productIterator->fetch())
			{
				$product['CNT'] = (int)$product['CNT'];
				if ($product['CNT'] > 0)
				{
					$product['PRODUCT_ID'] = (int)$product['PRODUCT_ID'];
					$result[$product['PRODUCT_ID']] = true;
				}
			}
			unset($product, $productIterator);
		}
		unset($sku, $productID, $iblockID, $iblockProduct);

		return $result;
	}

	public static function getOffersList($productID, $iblockID = 0, $skuFilter = array(), $fields = array(), $propertyFilter = array())
	{
		$iblockID = (int)$iblockID;
		if (!is_array($productID))
			$productID = array($productID);
		Collection::normalizeArrayValuesByInt($productID);
		if (empty($productID))
			return false;
		if (!is_array($skuFilter))
			$skuFilter = array();
		if (!is_array($fields))
			$fields = array($fields);
		$fields = array_merge($fields, array('ID', 'IBLOCK_ID'));
		if (!is_array($propertyFilter))
			$propertyFilter = array();

		$iblockProduct = array();
		$iblockSku = array();
		$offersIblock = array();
		if ($iblockID == 0)
		{
			$iblockList = array();
			$elementIterator = Iblock\ElementTable::getList(array(
				'select' => array('ID', 'IBLOCK_ID'),
				'filter' => array('@ID' => $productID)
			));
			while ($element = $elementIterator->fetch())
			{
				$element['ID'] = (int)$element['ID'];
				$element['IBLOCK_ID'] = (int)$element['IBLOCK_ID'];
				if (!isset($iblockList[$element['IBLOCK_ID']]))
					$iblockList[$element['IBLOCK_ID']] = array();
				$iblockList[$element['IBLOCK_ID']][] = $element['ID'];
			}
			unset($element, $elementIterator);
			if (!empty($iblockList))
			{
				$iblockIterator = Catalog\CatalogIblockTable::getList(array(
					'select' => array('IBLOCK_ID', 'PRODUCT_IBLOCK_ID', 'SKU_PROPERTY_ID', 'VERSION' => 'IBLOCK.VERSION'),
					'filter' => array('@PRODUCT_IBLOCK_ID' => array_keys($iblockList))
				));
				while ($iblock = $iblockIterator->fetch())
				{
					$iblock['IBLOCK_ID'] = (int)$iblock['IBLOCK_ID'];
					$iblock['PRODUCT_IBLOCK_ID'] = (int)$iblock['PRODUCT_IBLOCK_ID'];
					$iblock['SKU_PROPERTY_ID'] = (int)$iblock['SKU_PROPERTY_ID'];
					$iblock['VERSION'] = (int)$iblock['VERSION'];
					$iblockSku[$iblock['PRODUCT_IBLOCK_ID']] = $iblock;
					self::$arProductCache[$iblock['PRODUCT_IBLOCK_ID']] = $iblock;
					self::$arOfferCache[$iblock['IBLOCK_ID']] = $iblock;
					$offersIblock[] = $iblock['IBLOCK_ID'];
					$iblockProduct[$iblock['PRODUCT_IBLOCK_ID']] = $iblockList[$iblock['PRODUCT_IBLOCK_ID']];
				}
				unset($iblock, $iblockIterator);
			}
			unset($iblockList);
		}
		else
		{
			$iblockIterator = Catalog\CatalogIblockTable::getList(array(
				'select' => array('IBLOCK_ID', 'PRODUCT_IBLOCK_ID', 'SKU_PROPERTY_ID', 'VERSION' => 'IBLOCK.VERSION'),
				'filter' => array('=PRODUCT_IBLOCK_ID' => $iblockID)
			));
			if ($iblock = $iblockIterator->fetch())
			{
				$iblock['IBLOCK_ID'] = (int)$iblock['IBLOCK_ID'];
				$iblock['PRODUCT_IBLOCK_ID'] = (int)$iblock['PRODUCT_IBLOCK_ID'];
				$iblock['SKU_PROPERTY_ID'] = (int)$iblock['SKU_PROPERTY_ID'];
				$iblock['VERSION'] = (int)$iblock['VERSION'];
				$iblockSku[$iblock['PRODUCT_IBLOCK_ID']] = $iblock;
				self::$arProductCache[$iblock['PRODUCT_IBLOCK_ID']] = $iblock;
				self::$arOfferCache[$iblock['IBLOCK_ID']] = $iblock;
				$offersIblock[] = $iblock['IBLOCK_ID'];
				$iblockProduct[$iblockID] = $productID;
			}
			unset($iblock, $iblockIterator);
		}
		if (empty($iblockProduct))
			return array();

		$propertyFilter = array_filter($propertyFilter);
		if (isset($propertyFilter['ID']))
		{
			$propertyFilter['ID'] = array_filter($propertyFilter['ID']);
			if (empty($propertyFilter['ID']))
				unset($propertyFilter['ID']);
		}
		if (isset($propertyFilter['CODE']))
		{
			$propertyFilter['CODE'] = array_filter($propertyFilter['CODE']);
			if (empty($propertyFilter['CODE']))
				unset($propertyFilter['CODE']);
		}

		$iblockProperties = array();
		if (!empty($propertyFilter['ID']) || !empty($propertyFilter['CODE']))
		{
			$propertyIblock = array('=IBLOCK_ID' => $offersIblock);
			if (!empty($propertyFilter['ID']))
				$propertyIblock['@ID'] = $propertyFilter['ID'];
			else
				$propertyIblock['@CODE'] = $propertyFilter['CODE'];
			$propertyIterator = Iblock\PropertyTable::getList(array(
				'select' => array('ID', 'IBLOCK_ID'),
				'filter' => $propertyIblock
			));
			while ($property = $propertyIterator->fetch())
			{
				$property['IBLOCK_ID'] = (int)$property['IBLOCK_ID'];
				if (!isset($iblockProperties[$property['IBLOCK_ID']]))
					$iblockProperties[$property['IBLOCK_ID']] = array();
				$iblockProperties[$property['IBLOCK_ID']][] = (int)$property['ID'];
			}
			unset($property, $propertyIterator, $propertyIblock);
		}
		unset($offersIblock);

		$result = array_fill_keys($productID, array());

		foreach ($iblockProduct as $iblockID => $productList)
		{
			$skuProperty = 'PROPERTY_'.$iblockSku[$iblockID]['SKU_PROPERTY_ID'];
			$iblockFilter = $skuFilter;
			$iblockFilter['IBLOCK_ID'] = $iblockSku[$iblockID]['IBLOCK_ID'];
			$iblockFilter['='.$skuProperty] = $productList;
			$iblockFields = $fields;
			$iblockFields[] = $skuProperty;
			$skuProperty .= '_VALUE';
			$offersLinks = array();

			$offersIterator = CIBlockElement::GetList(
				array('ID' => 'ASC'),
				$iblockFilter,
				false,
				false,
				$iblockFields
			);
			while ($offer = $offersIterator->Fetch())
			{
				$offerProduct = (int)$offer[$skuProperty];
				unset($offer[$skuProperty]);
				if (!isset($result[$offerProduct]))
					continue;
				$offer['ID'] = (int)$offer['ID'];
				$offer['IBLOCK_ID'] = (int)$offer['IBLOCK_ID'];
				$offer['PROPERTIES'] = array();
				$result[$offerProduct][$offer['ID']] = $offer;
				$offersLinks[$offer['ID']] = &$result[$offerProduct][$offer['ID']];
			}
			unset($offerProduct, $offer, $offersIterator, $skuProperty);
			if (!empty($iblockProperties[$iblockSku[$iblockID]['IBLOCK_ID']]))
			{
				CIBlockElement::GetPropertyValuesArray(
					$offersLinks,
					$iblockSku[$iblockID]['IBLOCK_ID'],
					$iblockFilter,
					array('ID' => $iblockProperties[$iblockSku[$iblockID]['IBLOCK_ID']])
				);
			}
			unset($offersLinks);
		}
		unset($productList, $iblockID, $iblockProduct);

		return array_filter($result);
	}

	public static function getProductList($offerID, $iblockID = 0)
	{
		$iblockID = (int)$iblockID;
		if (!is_array($offerID))
			$offerID = array($offerID);
		Collection::normalizeArrayValuesByInt($offerID);
		if (empty($offerID))
			return false;

		$iblockSku = array();
		$iblockOffers = array();
		if ($iblockID == 0)
		{
			$iblockList = array();
			$elementIterator = Iblock\ElementTable::getList(array(
				'select' => array('ID', 'IBLOCK_ID'),
				'filter' => array('@ID' => $offerID)
			));
			while ($element = $elementIterator->fetch())
			{
				$element['ID'] = (int)$element['ID'];
				$element['IBLOCK_ID'] = (int)$element['IBLOCK_ID'];
				if (!isset($iblockList[$element['IBLOCK_ID']]))
					$iblockList[$element['IBLOCK_ID']] = array();
				$iblockList[$element['IBLOCK_ID']][] = $element['ID'];
			}
			unset($element, $elementIterator);
			if (!empty($iblockList))
			{
				$iblockIterator = Catalog\CatalogIblockTable::getList(array(
					'select' => array('IBLOCK_ID', 'PRODUCT_IBLOCK_ID', 'SKU_PROPERTY_ID', 'VERSION' => 'IBLOCK.VERSION'),
					'filter' => array('@IBLOCK_ID' => array_keys($iblockList), '!=PRODUCT_IBLOCK_ID' => 0)
				));
				while ($iblock = $iblockIterator->fetch())
				{
					$iblock['IBLOCK_ID'] = (int)$iblock['IBLOCK_ID'];
					$iblock['PRODUCT_IBLOCK_ID'] = (int)$iblock['PRODUCT_IBLOCK_ID'];
					$iblock['SKU_PROPERTY_ID'] = (int)$iblock['SKU_PROPERTY_ID'];
					$iblock['VERSION'] = (int)$iblock['VERSION'];
					$iblockSku[$iblock['IBLOCK_ID']] = $iblock;
					self::$arProductCache[$iblock['PRODUCT_IBLOCK_ID']] = $iblock;
					$iblockOffers[$iblock['IBLOCK_ID']] = $iblockList[$iblock['IBLOCK_ID']];
				}
				unset($iblock, $iblockIterator);
			}
			unset($iblockList);
		}
		else
		{
			$iblockIterator = Catalog\CatalogIblockTable::getList(array(
				'select' => array('IBLOCK_ID', 'PRODUCT_IBLOCK_ID', 'SKU_PROPERTY_ID', 'VERSION' => 'IBLOCK.VERSION'),
				'filter' => array('=IBLOCK_ID' => $iblockID, '!=PRODUCT_IBLOCK_ID' => 0)
			));
			if ($iblock = $iblockIterator->fetch())
			{
				$iblock['IBLOCK_ID'] = (int)$iblock['IBLOCK_ID'];
				$iblock['PRODUCT_IBLOCK_ID'] = (int)$iblock['PRODUCT_IBLOCK_ID'];
				$iblock['SKU_PROPERTY_ID'] = (int)$iblock['SKU_PROPERTY_ID'];
				$iblock['VERSION'] = (int)$iblock['VERSION'];
				$iblockSku[$iblock['IBLOCK_ID']] = $iblock;
				self::$arProductCache[$iblock['PRODUCT_IBLOCK_ID']] = $iblock;
				$iblockOffers[$iblockID] = $offerID;
			}
			unset($iblock, $iblockIterator);
		}
		if (empty($iblockOffers))
			return array();

		$result = array_fill_keys($offerID, array());

		foreach ($iblockOffers as $iblockID => $offerList)
		{
			$skuProperty = 'PROPERTY_'.$iblockSku[$iblockID]['SKU_PROPERTY_ID'];
			$iblockFilter = array(
				'IBLOCK_ID' => $iblockID,
				'=ID' => $offerList
			);
			$iblockFields = array('ID', 'IBLOCK_ID', $skuProperty);
			$skuProperty .= '_VALUE';

			$offersIterator = CIBlockElement::GetList(
				array('ID' => 'ASC'),
				$iblockFilter,
				false,
				false,
				$iblockFields
			);
			while ($offer = $offersIterator->Fetch())
			{
				$currentOffer = (int)$offer['ID'];
				$productID = (int)$offer[$skuProperty];
				if (!isset($result[$currentOffer]) || $productID <= 0)
					continue;
				unset($offer[$skuProperty]);

				$result[$currentOffer] = array(
					'ID' => $productID,
					'IBLOCK_ID' => $iblockSku[$iblockID]['PRODUCT_IBLOCK_ID'],
					'OFFER_IBLOCK_ID' => $iblockID,
					'SKU_PROPERTY_ID' => $iblockSku[$iblockID]['SKU_PROPERTY_ID']
				);
			}
			unset($currentOffer, $offer, $offersIterator, $skuProperty);
		}

		unset($iblockID, $iblockOffers);

		return array_filter($result);
	}

	public static function ClearCache()
	{
		self::$arOfferCache = array();
		self::$arProductCache = array();
		self::$arPropertyCache = array();
		self::$arIBlockCache = array();
	}
}