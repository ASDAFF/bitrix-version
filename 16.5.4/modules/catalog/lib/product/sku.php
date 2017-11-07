<?php
namespace Bitrix\Catalog\Product;

use Bitrix\Main,
	Bitrix\Catalog;

/**
 * Class Sku
 * Provides various useful methods for sku data.
 *
 * @package Bitrix\Catalog\Product
 */
class Sku
{
	const OFFERS_ERROR = 0x0000;
	const OFFERS_NOT_EXIST = 0x0001;
	const OFFERS_NOT_AVAILABLE = 0x0002;
	const OFFERS_AVAILABLE = 0x0004;

	protected static $allowUpdateAvailable = 0;
	protected static $allowPropertyHandler = true;

	protected static $productIds = array();
	protected static $offers = array();
	protected static $changeActive = array();
	protected static $currentActive = array();

	/**
	 * Enable automatic update product available.
	 *
	 * @return void
	 */
	public static function enableUpdateAvailable()
	{
		self::$allowUpdateAvailable++;
	}

	/**
	 * Disable automatic update product available.
	 *
	 * @return void
	 */
	public static function disableUpdateAvailable()
	{
		self::$allowUpdateAvailable--;
	}

	/**
	 * Return is allow automatic update product available.
	 *
	 * @return bool
	 */
	public static function allowedUpdateAvailable()
	{
		return (self::$allowUpdateAvailable >= 0);
	}

	/**
	 * Return default settings for product with sku.
	 *
	 * @param int $state			State flag.
	 * @return array
	 */
	public static function getDefaultParentSettings($state)
	{
		$state = (int)$state;
		switch ($state)
		{
			case self::OFFERS_NOT_EXIST:
				$result = array(
					'TYPE' => Catalog\ProductTable::TYPE_PRODUCT,
					'AVAILABLE' => Catalog\ProductTable::STATUS_NO,
					'QUANTITY' => '0',
					'QUANTITY_TRACE' => Catalog\ProductTable::STATUS_YES,
					'CAN_BUY_ZERO' => Catalog\ProductTable::STATUS_NO
				);
				break;
			case self::OFFERS_NOT_AVAILABLE:
				$result = array(
					'TYPE' => Catalog\ProductTable::TYPE_SKU,
					'AVAILABLE' => Catalog\ProductTable::STATUS_NO,
					'QUANTITY' => '0',
					'QUANTITY_TRACE' => Catalog\ProductTable::STATUS_YES,
					'CAN_BUY_ZERO' => Catalog\ProductTable::STATUS_NO
				);
				break;
			case self::OFFERS_AVAILABLE:
				$result = array(
					'TYPE' => Catalog\ProductTable::TYPE_SKU,
					'AVAILABLE' => Catalog\ProductTable::STATUS_YES,
					'QUANTITY' => '0',
					'QUANTITY_TRACE' => Catalog\ProductTable::STATUS_NO,
					'CAN_BUY_ZERO' => Catalog\ProductTable::STATUS_YES,
				);
				break;
			default:
				$result = array();
				break;
		}
		return $result;
	}

	/**
	 * Update product available.
	 *
	 * @param int $productId			Product Id.
	 * @param int $iblockId				Iblock Id (optional).
	 * @param array $productFields		Product fields (optional).
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Exception
	 */
	public static function updateAvailable($productId, $iblockId = 0, array $productFields = array())
	{
		if (!static::allowedUpdateAvailable())
			return true;
		static::disableUpdateAvailable();

		$useCatalogTab = (string)Main\Config\Option::get('catalog', 'show_catalog_tab_with_offers') == 'Y';

		$result = true;
		$process = true;
		$iblockData = false;
		$fields = array();

		$productId = (int)$productId;
		if ($productId <= 0)
		{
			$process = false;
			$result = false;
		}
		if ($process)
		{
			$iblockId = (int)$iblockId;
			if ($iblockId <= 0)
			{
				/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
				$iblockId = (int)\CIBlockElement::getIBlockByID($productId);
			}
			if ($iblockId <= 0)
			{
				$process = false;
				$result = false;
			}
		}

		if ($process)
		{
			/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
			$iblockData = \CCatalogSku::getInfoByIBlock($iblockId);
			if (empty($iblockData))
			{
				$process = false;
				$result = false;
			}
		}

		if ($process)
		{
			switch ($iblockData['CATALOG_TYPE'])
			{
				case \CCatalogSku::TYPE_PRODUCT:
					if ($useCatalogTab)
						$fields = static::getParentDataAsProduct($productId, $productFields);
					else
						$fields = static::getDefaultParentSettings(static::getOfferState($productId, $iblockId));
					break;
				case \CCatalogSku::TYPE_FULL:
					$offerState = static::getOfferState($productId, $iblockId);
					if ($offerState != self::OFFERS_ERROR)
					{
						switch ($offerState)
						{
							case self::OFFERS_AVAILABLE:
							case self::OFFERS_NOT_AVAILABLE:
								if ($useCatalogTab)
									$fields = static::getParentDataAsProduct($productId, $productFields);
								else
									$fields = static::getDefaultParentSettings($offerState);
								break;
							case self::OFFERS_NOT_EXIST:
								$product = Catalog\ProductTable::getList(array(
									'select' => array('ID', 'TYPE', 'QUANTITY', 'QUANTITY_TRACE', 'CAN_BUY_ZERO'),
									'filter' => array('=ID' => $productId)
								))->fetch();
								if (!empty($product))
								{
									switch ($product['TYPE'])
									{
										case Catalog\ProductTable::TYPE_SKU:
											$fields = static::getDefaultParentSettings($offerState);
											break;
										case Catalog\ProductTable::TYPE_PRODUCT:
										case Catalog\ProductTable::TYPE_SET:
											$fields['AVAILABLE'] = Catalog\ProductTable::calculateAvailable($product);
											break;
										default:
											break;
									}
								}
								unset($product);
								break;
						}
					}
					break;
				case \CCatalogSku::TYPE_OFFERS:
					$parent = \CCatalogSku::getProductList($productId, $iblockId);
					if (!isset($parent[$productId]))
					{
						$fields = array(
							'TYPE' => Catalog\ProductTable::TYPE_FREE_OFFER,
						);
					}
					else
					{
						/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
						$parentIBlock = \CCatalogSku::getInfoByIblock($parent[$productId]['IBLOCK_ID']);
						if ($useCatalogTab && $parentIBlock['CATALOG_TYPE'] == \CCatalogSku::TYPE_FULL)
							$parentFields = static::getParentDataAsProduct($parent[$productId]['ID']);
						else
							$parentFields = static::getDefaultParentSettings(static::getOfferState(
								$parent[$productId]['ID'],
								$parent[$productId]['IBLOCK_ID']
							));
						$existParent = Catalog\ProductTable::getList(array(
							'select' => array('ID', 'AVAILABLE', 'SUBSCRIBE'),
							'filter' => array('=ID' => $parent[$productId]['ID'])
						))->fetch();
						if ($existParent)
						{
							if(Catalog\SubscribeTable::checkPermissionSubscribe($existParent['SUBSCRIBE']))
							{
								if($existParent['AVAILABLE'] == Catalog\ProductTable::STATUS_NO
									&& $parentFields['AVAILABLE'] == Catalog\ProductTable::STATUS_YES)
								{
									Catalog\SubscribeTable::runAgentToSendNotice($existParent['ID']);
								}
								elseif($existParent['AVAILABLE'] == Catalog\ProductTable::STATUS_YES
									&& $parentFields['AVAILABLE'] == Catalog\ProductTable::STATUS_NO
									&& (string)Main\Config\Option::get('catalog', 'subscribe_repeated_notify') == 'Y')
								{
									Catalog\SubscribeTable::runAgentToSendRepeatedNotice($existParent['ID']);
								}
							}
							
							$updateResult = Catalog\ProductTable::update($parent[$productId]['ID'], $parentFields);
						}
						else
						{
							$parentFields['ID'] = $parent[$productId]['ID'];
							$updateResult = Catalog\ProductTable::add($parentFields);
						}
						unset($existParent);
						if (!$updateResult->isSuccess())
						{
							$process = false;
							$result = false;
						}
						else
						{
							$fields = array(
								'TYPE' => Catalog\ProductTable::TYPE_OFFER,
							);
						}
						unset($updateResult, $parentFields);
					}
					if ($process)
					{
						$offer = Catalog\ProductTable::getList(array(
							'select' => array('ID', 'TYPE', 'QUANTITY', 'QUANTITY_TRACE', 'CAN_BUY_ZERO'),
							'filter' => array('=ID' => $productId)
						))->fetch();
						if (!empty($offer))
							$fields['AVAILABLE'] = Catalog\ProductTable::calculateAvailable($offer);
						unset($offer);
					}
					unset($parent);
					break;
				case \CCatalogSku::TYPE_CATALOG:
					$product = Catalog\ProductTable::getList(array(
						'select' => array('ID', 'TYPE', 'QUANTITY', 'QUANTITY_TRACE', 'CAN_BUY_ZERO'),
						'filter' => array('=ID' => $productId)
					))->fetch();
					if (!empty($product))
					{
						switch ($product['TYPE'])
						{
							case Catalog\ProductTable::TYPE_PRODUCT:
							case Catalog\ProductTable::TYPE_SET:
								$fields['AVAILABLE'] = Catalog\ProductTable::calculateAvailable($product);
								break;
							default:
								break;
						}
					}
					unset($product);
					break;
			}
		}
		if ($process)
		{
			if (!empty($fields))
			{
				$updateResult = Catalog\ProductTable::update($productId, $fields);
				if (!$updateResult->isSuccess())
				{
					$process = false;
					$result = false;
				}
				unset($updateResult);
			}
		}
		unset($fields, $iblockData, $process);
		static::enableUpdateAvailable();
		return $result;
	}

	/**
	 * OnIBlockElementAdd event handler. Do not use directly.
	 *
	 * @param array $fields				Element data.
	 * @return void
	 */
	public static function handlerIblockElementAdd(/** @noinspection PhpUnusedParameterInspection */$fields)
	{
		static::disablePropertyHandler();
	}

	/**
	 * OnAfterIBlockElementAdd event handler. Do not use directly.
	 *
	 * @param array &$fields			Element data.
	 * @return void
	 */
	public static function handlerAfterIblockElementAdd(/** @noinspection PhpUnusedParameterInspection */&$fields)
	{
		static::enablePropertyHandler();
	}

	/**
	 * OnIBlockElementUpdate event handler. Do not use directly.
	 *
	 * @param array $newFields			New element data.
	 * @param array $oldFields			Current element data.
	 * @return void
	 */
	public static function handlerIblockElementUpdate($newFields, $oldFields)
	{
		static::disablePropertyHandler();
		/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
		$iblockData = \CCatalogSku::getInfoByOfferIBlock($newFields['IBLOCK_ID']);
		if (empty($iblockData))
			return;

		if (isset($newFields['ACTIVE']) && $newFields['ACTIVE'] != $oldFields['ACTIVE'])
			self::$changeActive[$newFields['ID']] = $newFields['ACTIVE'];
		self::$currentActive[$newFields['ID']] = $oldFields['ACTIVE'];
	}

	/**
	 * OnAfterIBlockElementUpdate event handler. Do not use directly.
	 *
	 * @param array &$fields			New element data.
	 * @return void
	 */
	public static function handlerAfterIblockElementUpdate(&$fields)
	{
		$process = true;
		$modifyActive = false;
		$modifyProperty = false;
		$iblockData = false;
		$elementId = 0;

		if (!$fields['RESULT'])
			$process = false;
		else
			$elementId = $fields['ID'];

		if ($process)
		{
			$modifyActive = isset(self::$changeActive[$elementId]);
			$modifyProperty = (
				isset(self::$offers[$elementId])
				&& self::$offers[$elementId]['CURRENT_PRODUCT'] != self::$offers[$elementId]['NEW_PRODUCT']
			);
			$process = $modifyActive || $modifyProperty;
		}

		if ($process)
		{
			/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
			$iblockData = \CCatalogSku::getInfoByOfferIBlock($fields['IBLOCK_ID']);
			$process = !empty($iblockData);
		}

		if ($process)
		{
			if ($modifyActive && !isset(self::$offers[$elementId]))
			{
				$parent = \CCatalogSku::getProductList($elementId, $fields['IBLOCK_ID']);
				if (!empty($parent[$elementId]))
					self::$offers[$elementId] = array(
						'CURRENT_PRODUCT' => $parent[$elementId]['ID'],
						'NEW_PRODUCT' => $parent[$elementId]['ID']
					);
				unset($parent);
			}

			if (isset(self::$offers[$elementId]))
			{
				if (self::$offers[$elementId]['CURRENT_PRODUCT'] > 0)
				{
					if ($modifyActive || $modifyProperty)
						static::updateProductAvailable(self::$offers[$elementId]['CURRENT_PRODUCT'], $iblockData['PRODUCT_IBLOCK_ID']);
				}
				if (self::$offers[$elementId]['NEW_PRODUCT'] > 0)
				{
					$elementActive = '';
					if (self::$currentActive[$elementId])
						$elementActive = self::$currentActive[$elementId];
					if (isset(self::$changeActive[$elementId]))
						$elementActive = self::$changeActive[$elementId];
					if ($modifyProperty && $elementActive == 'Y')
						static::updateProductAvailable(self::$offers[$elementId]['NEW_PRODUCT'], $iblockData['PRODUCT_IBLOCK_ID']);
				}
				if (self::$offers[$elementId]['CURRENT_PRODUCT'] == 0 || self::$offers[$elementId]['NEW_PRODUCT'] == 0)
				{
					$type = (
						self::$offers[$elementId]['NEW_PRODUCT'] > 0
						? Catalog\ProductTable::TYPE_OFFER
						: Catalog\ProductTable::TYPE_FREE_OFFER
					);
					static::updateOfferType($elementId, $type);
					unset($type);
				}
			}
			else
			{
				static::updateOfferType($elementId, Catalog\ProductTable::TYPE_FREE_OFFER);
			}
		}
		if (isset(self::$offers[$elementId]))
			unset(self::$offers[$elementId]);
		static::enablePropertyHandler();
	}

	/**
	 * OnIBlockElementDelete event handler. Do not use directly.
	 *
	 * @param int $elementId			Element id.
	 * @param array $elementData		Element data.
	 * @return void
	 */
	public static function handlerIblockElementDelete($elementId, $elementData)
	{
		if ((int)$elementData['WF_PARENT_ELEMENT_ID'] > 0)
			return;
		/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
		$iblockData = \CCatalogSku::getInfoByOfferIBlock($elementData['IBLOCK_ID']);
		if (empty($iblockData))
			return;

		$parent = \CCatalogSku::getProductList($elementId, $elementData['IBLOCK_ID']);
		if (!empty($parent[$elementId]))
			self::$offers[$elementId] = array(
				'CURRENT_PRODUCT' => $parent[$elementId]['ID'],
				'NEW_PRODUCT' => $parent[$elementId]['ID'],
				'PRODUCT_IBLOCK_ID' => $iblockData['PRODUCT_IBLOCK_ID']
			);
		unset($parent);
	}

	/**
	 * OnAfterIBlockElementDelete event handler. Do not use directly.
	 *
	 * @param array $elementData		Element data.
	 * @return void
	 */
	public static function handlerAfterIblockElementDelete($elementData)
	{
		$elementId = $elementData['ID'];
		if (!isset(self::$offers[$elementId]))
			return;

		static::updateProductAvailable(self::$offers[$elementId]['CURRENT_PRODUCT'], self::$offers[$elementId]['PRODUCT_IBLOCK_ID']);

		unset(self::$offers[$elementId]);
	}

	/**
	 * OnIBlockElementSetPropertyValues event handler. Do not use directly.
	 *
	 * @param int $elementId							Element id.
	 * @param int $iblockId								Iblock id.
	 * @param array $newValues							New properties values.
	 * @param int|string|false $propertyIdentifyer		Property identifier.
	 * @param array $propertyList						Changed property list.
	 * @param array $currentValues						Current properties values.
	 * @return void
	 */
	public static function handlerIblockElementSetPropertyValues($elementId, $iblockId, $newValues, $propertyIdentifyer, $propertyList, $currentValues)
	{
		/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
		$iblockData = \CCatalogSku::getInfoByOfferIBlock($iblockId);
		if (empty($iblockData))
			return;

		$skuPropertyId = $iblockData['SKU_PROPERTY_ID'];
		if (!isset($propertyList[$skuPropertyId]))
			return;
		$skuPropertyCode = (string)$propertyList[$skuPropertyId]['CODE'];

		$skuValue = null;
		if ($propertyIdentifyer)
		{
			if (is_int($propertyIdentifyer))
			{
				$propertyId = $propertyIdentifyer;
			}
			else
			{
				$propertyId = (int)$propertyIdentifyer;
				if ($propertyId.'' != $propertyIdentifyer)
					$propertyId = ($skuPropertyCode == $propertyIdentifyer ? $skuPropertyId : 0);
			}
			if ($propertyId == $skuPropertyId)
				$skuValue = $newValues;
			unset($propertyId);
		}
		else
		{
			if (isset($newValues[$skuPropertyId]))
				$skuValue = $newValues[$skuPropertyId];
			elseif (isset($newValues[$skuPropertyCode]))
				$skuValue = $newValues[$skuPropertyCode];
		}
		if ($skuValue === null)
			return;

		$newSkuPropertyValue = 0;
		if (!empty($skuValue))
		{
			if (!is_array($skuValue))
			{
				$newSkuPropertyValue = (int)$skuValue;
			}
			else
			{
				$skuValue = current($skuValue);
				if (!is_array($skuValue))
					$newSkuPropertyValue = (int)$skuValue;
				elseif (!empty($skuValue['VALUE']))
					$newSkuPropertyValue = (int)$skuValue['VALUE'];
			}
		}
		unset($skuValue);
		if ($newSkuPropertyValue < 0)
			$newSkuPropertyValue = 0;

		$currentSkuPropertyValue = 0;
		if (!empty($currentValues[$skuPropertyId]) && is_array($currentValues[$skuPropertyId]))
		{
			$currentSkuProperty = current($currentValues[$skuPropertyId]);
			if (!empty($currentSkuProperty['VALUE']))
				$currentSkuPropertyValue = (int)$currentSkuProperty['VALUE'];
			unset($currentSkuProperty);
		}
		if ($currentSkuPropertyValue < 0)
			$currentSkuPropertyValue = 0;

		if ($currentSkuPropertyValue > 0)
			self::$productIds[$currentSkuPropertyValue] = $elementId;

		if ($newSkuPropertyValue > 0)
			self::$productIds[$newSkuPropertyValue] = $elementId;

		if (!static::allowedPropertyHandler() || ($currentSkuPropertyValue != $newSkuPropertyValue))
		{
			self::$offers[$elementId] = array(
				'CURRENT_PRODUCT' => $currentSkuPropertyValue,
				'NEW_PRODUCT' => $newSkuPropertyValue
			);
		}
	}

	/**
	 * OnAfterIBlockElementSetPropertyValues event handler. Do not use directly.
	 *
	 * @param int $elementId							Element id.
	 * @param int $iblockId								Iblock id.
	 * @param array $newValues							New properties values.
	 * @param int|string|false $propertyIdentifyer		Property identifier.
	 * @return void
	 */
	public static function handlerAfterIBlockElementSetPropertyValues(
		$elementId,
		$iblockId,
		/** @noinspection PhpUnusedParameterInspection */$newValues,
		/** @noinspection PhpUnusedParameterInspection */$propertyIdentifyer
	)
	{
		if (!static::allowedPropertyHandler())
			return;

		if (!isset(self::$offers[$elementId]))
			return;

		/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
		$iblockData = \CCatalogSku::getInfoByOfferIBlock($iblockId);
		if (!empty($iblockData))
		{
			$existCurrentProduct = (self::$offers[$elementId]['CURRENT_PRODUCT'] > 0);
			$existNewProduct = (self::$offers[$elementId]['NEW_PRODUCT'] > 0);
			if ($existCurrentProduct > 0)
				static::updateProductAvailable(self::$offers[$elementId]['CURRENT_PRODUCT'], $iblockData['PRODUCT_IBLOCK_ID']);
			if ($existNewProduct > 0)
				static::updateProductAvailable(self::$offers[$elementId]['NEW_PRODUCT'], $iblockData['PRODUCT_IBLOCK_ID']);
			if (!$existCurrentProduct || !$existNewProduct)
			{
				if ($existNewProduct)
					static::updateOfferType($elementId, Catalog\ProductTable::TYPE_OFFER);
				else
					static::updateOfferType($elementId, Catalog\ProductTable::TYPE_FREE_OFFER);
			}
			unset($existNewProduct, $existCurrentProduct);
		}
		unset(self::$offers[$elementId]);
	}

	/**
	 * Return available and exist product offers.
	 *
	 * @param int $productId			Product id.
	 * @param int $iblockId				Iblock id.
	 * @return int
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getOfferState($productId, $iblockId = 0)
	{
		$result = self::OFFERS_ERROR;
		$productId = (int)$productId;
		if ($productId <= 0)
			return $result;
		$iblockId = (int)$iblockId;
		if ($iblockId <= 0)
		{
			/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
			$iblockId = (int)\CIBlockElement::getIBlockByID($productId);
		}
		if ($iblockId <= 0)
			return $result;

		$result = self::OFFERS_NOT_EXIST;
		$offerList = \CCatalogSku::getOffersList($productId, $iblockId, array(), array('ID', 'ACTIVE'));
		if (!empty($offerList[$productId]))
		{
			$result = self::OFFERS_NOT_AVAILABLE;
			$activeOffers = array_filter($offerList[$productId], '\Bitrix\Catalog\Product\Sku::filterActive');
			if (!empty($activeOffers))
			{
				$existOffers = Catalog\ProductTable::getList(array(
					'select' => array('ID', 'AVAILABLE'),
					'filter' => array('@ID' => array_keys($activeOffers), '=AVAILABLE' => Catalog\ProductTable::STATUS_YES),
					'limit' => 1
				))->fetch();
				if (!empty($existOffers))
					$result = self::OFFERS_AVAILABLE;
				unset($existOffers);
			}
			unset($activeOffers);
		}
		unset($offerList);

		return $result;
	}

	/**
	 * Update sku product available.
	 *
	 * @param int $productId			Product id.
	 * @param int $iblockId				Iblock id.
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Exception
	 */
	protected static function updateProductAvailable($productId, $iblockId)
	{
		$productId = (int)$productId;
		$iblockId = (int)$iblockId;
		if ($productId <= 0 || $iblockId <= 0)
			return false;

		$fields = static::getDefaultParentSettings(static::getOfferState($productId, $iblockId));
		static::disableUpdateAvailable();
		$existParent = Catalog\ProductTable::getList(array(
			'select' => array('ID'),
			'filter' => array('=ID' => $productId)
		))->fetch();
		if ($existParent)
		{
			$updateResult = Catalog\ProductTable::update($productId, $fields);
		}
		else
		{
			$fields['ID'] = $productId;
			$updateResult = Catalog\ProductTable::add($fields);
		}
		$result = $updateResult->isSuccess();
		unset($updateResult, $existParent);
		unset($fields);
		static::enableUpdateAvailable();
		return $result;
	}

	/**
	 * Update offer product type.
	 *
	 * @param int $offerId				Offer id.
	 * @param int $type					Product type.
	 * @return bool
	 * @throws \Exception
	 */
	protected static function updateOfferType($offerId, $type)
	{
		$offerId = (int)$offerId;
		$type = (int)$type;
		if ($offerId <= 0 || ($type != Catalog\ProductTable::TYPE_OFFER && $type != Catalog\ProductTable::TYPE_FREE_OFFER))
			return false;
		static::disableUpdateAvailable();
		$updateResult = Catalog\ProductTable::update($offerId, array('TYPE' => $type));
		$result = $updateResult->isSuccess();
		static::enableUpdateAvailable();
		return $result;
	}

	/**
	 * Enable property handlers.
	 *
	 * @return void
	 */
	protected static function enablePropertyHandler()
	{
		self::$allowPropertyHandler++;
	}

	/**
	 * Disable property handlers.
	 *
	 * @return void
	 */
	protected static function disablePropertyHandler()
	{
		self::$allowPropertyHandler--;
	}

	/**
	 * Return is enabled property handlers.
	 *
	 * @return bool
	 */
	protected static function allowedPropertyHandler()
	{
		return (self::$allowPropertyHandler >= 0);
	}

	/**
	 * Method for array_filter.
	 *
	 * @param array $row			Product/ Offer data.
	 * @return bool
	 */
	protected static function filterActive(array $row)
	{
		return (isset($row['ACTIVE']) && $row['ACTIVE'] == 'Y');
	}

	/**
	 * Calculate available for product with sku as simple product. Compatible only.
	 *
	 * @param int $productId				Product id.
	 * @param array $productFields			Product fields (optional).
	 * @return array
	 * @throws Main\ArgumentException
	 */
	private static function getParentDataAsProduct($productId, array $productFields = array())
	{
		$productId = (int)$productId;
		if ($productId <= 0)
			return static::getDefaultParentSettings(self::OFFERS_NOT_AVAILABLE);

		$fieldKeys = Catalog\Helpers\Tools::prepareKeys($productFields, array('QUANTITY', 'QUANTITY_TRACE', 'CAN_BUY_ZERO'));

		if (!empty($fieldKeys['MISSING']))
		{
			$product = Catalog\ProductTable::getList(array(
				'select' => array_merge(array('ID', 'TYPE'), $fieldKeys['MISSING']),
				'filter' => array('=ID' => $productId)
			))->fetch();
			if (empty($product))
				return static::getDefaultParentSettings(self::OFFERS_NOT_AVAILABLE);

			$productFields = array_merge($product, $productFields);
			unset($product);
		}
		unset($fieldKeys);
		return array(
			'TYPE' => Catalog\ProductTable::TYPE_SKU,
			'AVAILABLE' => Catalog\ProductTable::calculateAvailable($productFields)
		);
	}
}