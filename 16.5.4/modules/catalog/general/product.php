<?
use Bitrix\Main\Localization\Loc,
	Bitrix\Main,
	Bitrix\Currency,
	Bitrix\Catalog;

Loc::loadMessages(__FILE__);

class CAllCatalogProduct
{
	const TYPE_PRODUCT = Catalog\ProductTable::TYPE_PRODUCT;
	const TYPE_SET = Catalog\ProductTable::TYPE_SET;
	const TYPE_SKU = Catalog\ProductTable::TYPE_SKU;
	const TYPE_OFFER = Catalog\ProductTable::TYPE_OFFER;
	const TYPE_FREE_OFFER = Catalog\ProductTable::TYPE_FREE_OFFER;
	const TYPE_EMPTY_SKU = Catalog\ProductTable::TYPE_EMPTY_SKU;

	const TIME_PERIOD_HOUR = 'H';
	const TIME_PERIOD_DAY = 'D';
	const TIME_PERIOD_WEEK = 'W';
	const TIME_PERIOD_MONTH = 'M';
	const TIME_PERIOD_QUART = 'Q';
	const TIME_PERIOD_SEMIYEAR = 'S';
	const TIME_PERIOD_YEAR = 'Y';
	const TIME_PERIOD_DOUBLE_YEAR = 'T';

	protected static $arProductCache = array();

	protected static $usedCurrency = null;
	protected static $optimalPriceWithVat = true;
	protected static $useDiscount = true;

	protected static $saleIncluded = null;

	public static function setUsedCurrency($currency)
	{
		/** @var $oldCurrency string */
		static $oldCurrency = null;
		if ($oldCurrency !== null && $oldCurrency === $currency)
		{
			self::$usedCurrency = $currency;
			return;
		}
		$currency = CCurrency::checkCurrencyID($currency);
		if ($currency === false)
			return;
		$currencyIterator = Currency\CurrencyTable::getList(array(
			'select' => array('CURRENCY'),
			'filter' => array('=CURRENCY' => $currency)
		));
		if ($result = $currencyIterator->fetch())
		{
			self::$usedCurrency = $currency;
			$oldCurrency = $currency;
		}
		unset($result, $currencyIterator);
	}

	public static function getUsedCurrency()
	{
		return self::$usedCurrency;
	}

	public static function clearUsedCurrency()
	{
		self::$usedCurrency = null;
	}

	public static function setPriceVatIncludeMode($mode)
	{
		if ($mode !== true && $mode !== false)
			return;
		self::$optimalPriceWithVat = $mode;
	}

	public static function getPriceVatIncludeMode()
	{
		return self::$optimalPriceWithVat;
	}

	public static function setUseDiscount($use)
	{
		if ($use !== true && $use !== false)
			return;
		self::$useDiscount = $use;
	}

	public static function getUseDiscount()
	{
		return self::$useDiscount;
	}

	public static function ClearCache()
	{
		self::$arProductCache = array();
	}

	/**
	 * @param array $product
	 * @return bool
	 */
	public static function isAvailable($product)
	{
		$result = true;
		if (!empty($product) && is_array($product))
		{
			if (isset($product['QUANTITY']) && isset($product['QUANTITY_TRACE']) && isset($product['CAN_BUY_ZERO']))
			{
				$result = !((float)$product['QUANTITY'] <= 0 && $product['QUANTITY_TRACE'] == 'Y' && $product['CAN_BUY_ZERO'] == 'N');
			}
		}
		return $result;
	}

	/**
	 * @deprecated deprecated since catalog 15.5.2
	 * @see \Bitrix\Catalog\ProductTable::isExistProduct()
	 * @param int $intID
	 * @return bool
	 */
	public static function IsExistProduct($intID)
	{
		return Catalog\ProductTable::isExistProduct($intID);
	}

	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		global $APPLICATION;

		$arMsg = array();
		$boolResult = true;

		$ACTION = strtoupper($ACTION);
		$ID = (int)$ID;
		if ($ACTION == "ADD" && (!is_set($arFields, "ID") || (int)$arFields["ID"]<=0))
		{
			$arMsg[] = array('id' => 'ID','text' => Loc::getMessage('KGP_EMPTY_ID'));
			$boolResult = false;
		}
		if ($ACTION != "ADD" && $ID <= 0)
		{
			$arMsg[] = array('id' => 'ID','text' => Loc::getMessage('KGP_EMPTY_ID'));
			$boolResult = false;
		}

		$clearFields = array(
			'NEGATIVE_AMOUNT_TRACE',
			'~NEGATIVE_AMOUNT_TRACE',
			'~TYPE',
			'~AVAILABLE'
		);
		if ($ACTION =='UPDATE')
		{
			$clearFields[] = 'ID';
			$clearFields[] = '~ID';
		}
		if ($ACTION == 'ADD')
		{
			$clearFields[] = 'BUNDLE';
			$clearFields[] = '~BUNDLE';
		}

		foreach ($clearFields as &$fieldName)
		{
			if (array_key_exists($fieldName, $arFields))
				unset($arFields[$fieldName]);
		}
		unset($fieldName, $clearFields);

		if ('ADD' == $ACTION)
		{
			if (!array_key_exists('SUBSCRIBE', $arFields))
				$arFields['SUBSCRIBE'] = '';
			if (!isset($arFields['TYPE']))
				$arFields['TYPE'] = self::TYPE_PRODUCT;
			$arFields['BUNDLE'] = Catalog\ProductTable::STATUS_NO;
		}

		if (is_set($arFields, "ID") || $ACTION=="ADD")
			$arFields["ID"] = (int)$arFields["ID"];
		if (is_set($arFields, "QUANTITY") || $ACTION=="ADD")
			$arFields["QUANTITY"] = doubleval($arFields["QUANTITY"]);
		if (is_set($arFields, "QUANTITY_RESERVED") || $ACTION=="ADD")
			$arFields["QUANTITY_RESERVED"] = doubleval($arFields["QUANTITY_RESERVED"]);
		if (is_set($arFields, "OLD_QUANTITY"))
			$arFields["OLD_QUANTITY"] = doubleval($arFields["OLD_QUANTITY"]);
		if (is_set($arFields, "WEIGHT") || $ACTION=="ADD")
			$arFields["WEIGHT"] = doubleval($arFields["WEIGHT"]);
		if (is_set($arFields, "WIDTH") || $ACTION=="ADD")
			$arFields["WIDTH"] = doubleval($arFields["WIDTH"]);
		if (is_set($arFields, "LENGTH") || $ACTION=="ADD")
			$arFields["LENGTH"] = doubleval($arFields["LENGTH"]);
		if (is_set($arFields, "HEIGHT") || $ACTION=="ADD")
			$arFields["HEIGHT"] = doubleval($arFields["HEIGHT"]);

		if (is_set($arFields, "VAT_ID") || $ACTION=="ADD")
			$arFields["VAT_ID"] = intval($arFields["VAT_ID"]);
		if ((is_set($arFields, "VAT_INCLUDED") || $ACTION=="ADD") && ($arFields["VAT_INCLUDED"] != "Y"))
			$arFields["VAT_INCLUDED"] = "N";

		if ((is_set($arFields, "QUANTITY_TRACE") || $ACTION=="ADD") && ($arFields["QUANTITY_TRACE"] != "Y" && $arFields["QUANTITY_TRACE"] != "N"))
			$arFields["QUANTITY_TRACE"] = "D";
		if ((is_set($arFields, "CAN_BUY_ZERO") || $ACTION=="ADD") && ($arFields["CAN_BUY_ZERO"] != "Y" && $arFields["CAN_BUY_ZERO"] != "N"))
			$arFields["CAN_BUY_ZERO"] = "D";
		if (isset($arFields['CAN_BUY_ZERO']))
			$arFields['NEGATIVE_AMOUNT_TRACE'] = $arFields['CAN_BUY_ZERO'];

		if ((is_set($arFields, "PRICE_TYPE") || $ACTION=="ADD") && ($arFields["PRICE_TYPE"] != "R") && ($arFields["PRICE_TYPE"] != "T"))
			$arFields["PRICE_TYPE"] = "S";

		if ((is_set($arFields, "RECUR_SCHEME_TYPE") || $ACTION=="ADD") && (strlen($arFields["RECUR_SCHEME_TYPE"]) <= 0 || !in_array($arFields["RECUR_SCHEME_TYPE"], CCatalogProduct::GetTimePeriodTypes(false))))
		{
			$arFields["RECUR_SCHEME_TYPE"] = self::TIME_PERIOD_DAY;
		}

		if ((is_set($arFields, "RECUR_SCHEME_LENGTH") || $ACTION=="ADD") && (intval($arFields["RECUR_SCHEME_LENGTH"])<=0))
			$arFields["RECUR_SCHEME_LENGTH"] = 0;

		if ((is_set($arFields, "TRIAL_PRICE_ID") || $ACTION=="ADD") && (intval($arFields["TRIAL_PRICE_ID"])<=0))
			$arFields["TRIAL_PRICE_ID"] = false;

		if ((is_set($arFields, "WITHOUT_ORDER") || $ACTION=="ADD") && ($arFields["WITHOUT_ORDER"] != "Y"))
			$arFields["WITHOUT_ORDER"] = "N";

		if ((is_set($arFields, "SELECT_BEST_PRICE") || $ACTION=="ADD") && ($arFields["SELECT_BEST_PRICE"] != "N"))
			$arFields["SELECT_BEST_PRICE"] = "Y";

		if (is_set($arFields, 'PURCHASING_PRICE'))
		{
			if ($ACTION != 'ADD')
			{
				if ($arFields['PURCHASING_PRICE'] === null || trim($arFields['PURCHASING_PRICE']) == '')
					unset($arFields['PURCHASING_PRICE']);
			}
		}
		if (is_set($arFields, 'PURCHASING_PRICE'))

			$arFields['PURCHASING_PRICE'] = (float)(str_replace(',', '.', $arFields['PURCHASING_PRICE']));

		if (is_set($arFields, 'PURCHASING_CURRENCY') || ($ACTION=="ADD" && is_set($arFields, 'PURCHASING_PRICE')))
		{
			if (empty($arFields['PURCHASING_CURRENCY']))
			{
				$arMsg[] = array('id' => 'PURCHASING_CURRENCY','text' => Loc::getMessage('BT_MOD_CATALOG_PROD_ERR_COST_CURRENCY'));
				$boolResult = false;
			}
			else
			{
				$arFields['PURCHASING_CURRENCY'] = strtoupper($arFields['PURCHASING_CURRENCY']);
			}
		}
		if ((is_set($arFields, 'BARCODE_MULTI') || 'ADD' == $ACTION) && 'Y' != $arFields['BARCODE_MULTI'])
			$arFields['BARCODE_MULTI'] = 'N';
		if (array_key_exists('SUBSCRIBE', $arFields))
		{
			if ('Y' != $arFields['SUBSCRIBE'] && 'N' != $arFields['SUBSCRIBE'])
				$arFields['SUBSCRIBE'] = 'D';
		}
		if (array_key_exists('BUNDLE', $arFields))
			$arFields['BUNDLE'] = ($arFields['BUNDLE'] == Catalog\ProductTable::STATUS_YES ? Catalog\ProductTable::STATUS_YES : Catalog\ProductTable::STATUS_NO);

		if ($boolResult)
		{
			$availableFieldsList = array(
				'QUANTITY',
				'QUANTITY_TRACE',
				'CAN_BUY_ZERO'
			);
			$needCalculateAvailable = false;
			$copyFields = $arFields;
			if (isset($copyFields['QUANTITY_TRACE']) && $copyFields['QUANTITY_TRACE'] == 'D')
				$copyFields['QUANTITY_TRACE'] = Main\Config\Option::get('catalog', 'default_quantity_trace');
			if (isset($copyFields['CAN_BUY_ZERO']) && $copyFields['CAN_BUY_ZERO'] == 'D')
				$copyFields['CAN_BUY_ZERO'] = Main\Config\Option::get('catalog', 'default_can_buy_zero');

			if (!isset($arFields['AVAILABLE']))
			{
				if (
					!isset($arFields['TYPE'])
					|| $arFields['TYPE'] == Catalog\ProductTable::TYPE_PRODUCT
					|| $arFields['TYPE'] == Catalog\ProductTable::TYPE_OFFER
					|| $arFields['TYPE'] == Catalog\ProductTable::TYPE_FREE_OFFER
				)
				{
					if ($ACTION == 'ADD' && $arFields['TYPE'] == Catalog\ProductTable::TYPE_PRODUCT && !isset($arFields['AVAILABLE']))
					{
						$needCalculateAvailable = true;
					}
					elseif ($ACTION == 'UPDATE')
					{
						$needFields = array();
						foreach ($availableFieldsList as &$availableField)
						{
							if (isset($arFields[$availableField]))
								$needCalculateAvailable = true;
							else
								$needFields[] = $availableField;
						}
						unset($availableField);
						if ($needCalculateAvailable && !empty($needFields))
						{
							$product = $productIterator = Catalog\ProductTable::getList(array(
								'select' => $needFields,
								'filter' => array('=ID' => $ID)
							))->fetch();
							if (!empty($product) && is_array($product))
							{
								foreach ($availableFieldsList as &$availableField)
								{
									if (isset($copyFields[$availableField]))
										continue;
									$copyFields[$availableField] = $product[$availableField];
								}
								unset($availableField);
							}
							unset($product);
						}
						unset($needFields);
					}
				}
				elseif (isset($arFields['TYPE']) && $arFields['TYPE'] == CCatalogProduct::TYPE_SKU)
				{
					$offerList = CCatalogSKU::getOffersList(array($ID), 0, array('ACTIVE' => 'Y'), array('ID'));
					if (!empty($offerList[$ID]))
					{
						$skuAvailable = false;
						$offerIterator = Catalog\ProductTable::getList(array(
							'select' => array('ID', 'QUANTITY', 'QUANTITY_TRACE', 'CAN_BUY_ZERO'),
							'filter' => array('@ID' => array_keys($offerList[$ID]))
						));
						while ($offer = $offerIterator->fetch())
						{
							if (Catalog\ProductTable::calculateAvailable($offer) == Catalog\ProductTable::STATUS_YES)
								$skuAvailable = true;
						}
						unset($offer, $offerIterator);
						if ($skuAvailable)
						{
							$arFields['AVAILABLE'] = 'Y';
							$arFields['QUANTITY'] = '0';
							$arFields['QUANTITY_TRACE'] = 'N';
							$arFields['CAN_BUY'] = 'Y';
						}
						else
						{
							$arFields['AVAILABLE'] = 'N';
							$arFields['QUANTITY'] = '0';
							$arFields['QUANTITY_TRACE'] = 'Y';
							$arFields['CAN_BUY'] = 'N';
						}
					}
					else
					{
						$arFields['AVAILABLE'] = 'N';
					}
					unset($offerList);
				}
			}
			if ($needCalculateAvailable)
				$arFields['AVAILABLE'] = Catalog\ProductTable::calculateAvailable($copyFields);
			unset($copyFields);
		}

		if (!$boolResult)
		{
			$obError = new CAdminException($arMsg);
			$APPLICATION->ThrowException($obError);
		}
		return $boolResult;
	}

	public static function ParseQueryBuildField($field)
	{
		$field = (string)$field;
		if ($field == '')
			return false;
		$field = strtoupper($field);
		if (strncmp($field, 'CATALOG_', 8) != 0)
			return false;

		$iNum = 0;
		$field = substr($field, 8);
		$p = strrpos($field, '_');
		if ($p !== false && $p > 0)
		{
			$iNum = (int)substr($field, $p+1);
			if ($iNum > 0)
				$field = substr($field, 0, $p);
		}
		return array(
			'FIELD' => $field,
			'NUM' => $iNum
		);
	}

	public static function GetByID($ID)
	{
		$ID = (int)$ID;
		if ($ID <= 0)
			return false;

		if (isset(self::$arProductCache[$ID]))
		{
			return self::$arProductCache[$ID];
		}
		else
		{
			$rsProducts = CCatalogProduct::GetList(
				array(),
				array('ID' => $ID),
				false,
				false,
				array(
					'ID', 'QUANTITY', 'QUANTITY_RESERVED', 'QUANTITY_TRACE', 'QUANTITY_TRACE_ORIG', 'WEIGHT', 'WIDTH', 'LENGTH', 'HEIGHT', 'MEASURE',
					'VAT_ID', 'VAT_INCLUDED', 'CAN_BUY_ZERO', 'CAN_BUY_ZERO_ORIG', 'NEGATIVE_AMOUNT_TRACE', 'NEGATIVE_AMOUNT_TRACE_ORIG',
					'PRICE_TYPE', 'RECUR_SCHEME_TYPE', 'RECUR_SCHEME_LENGTH', 'TRIAL_PRICE_ID', 'WITHOUT_ORDER', 'SELECT_BEST_PRICE',
					'TMP_ID', 'PURCHASING_PRICE', 'PURCHASING_CURRENCY', 'BARCODE_MULTI', 'TIMESTAMP_X', 'SUBSCRIBE', 'SUBSCRIBE_ORIG', 'TYPE'
				)
			);
			if ($arProduct = $rsProducts->Fetch())
			{
				$arProduct['ID'] = (int)$arProduct['ID'];
				self::$arProductCache[$ID] = $arProduct;
				if (defined('CATALOG_GLOBAL_VARS') && CATALOG_GLOBAL_VARS == 'Y')
				{
					/** @var array $CATALOG_PRODUCT_CACHE */
					global $CATALOG_PRODUCT_CACHE;
					$CATALOG_PRODUCT_CACHE = self::$arProductCache;
				}
				return $arProduct;
			}
		}
		return false;
	}

	public static function GetByIDEx($ID, $boolAllValues = false)
	{
		$boolAllValues = ($boolAllValues === true);
		$ID = (int)$ID;
		if ($ID <= 0)
			return false;
		$arFilter = array("ID" => $ID, "ACTIVE" => "Y", "ACTIVE_DATE" => "Y");

		$dbIBlockElement = CIBlockElement::GetList(array(), $arFilter);
		if ($arIBlockElement = $dbIBlockElement->GetNext())
		{
			if ($arIBlock = CIBlock::GetArrayByID($arIBlockElement["IBLOCK_ID"]))
			{
				$arIBlockElement["IBLOCK_ID"] = $arIBlock["ID"];
				$arIBlockElement["IBLOCK_NAME"] = htmlspecialcharsbx($arIBlock["NAME"]);
				$arIBlockElement["~IBLOCK_NAME"] = $arIBlock["NAME"];
				$arIBlockElement["PROPERTIES"] = false;
				$dbProps = CIBlockElement::GetProperty($arIBlock["ID"], $ID, "sort", "asc", array("ACTIVE"=>"Y", "NON_EMPTY"=>"Y"));
				if ($arProp = $dbProps->Fetch())
				{
					$arAllProps = array();
					do
					{
						$strID = (strlen($arProp["CODE"])>0 ? $arProp["CODE"] : $arProp["ID"]);
						if (is_array($arProp["VALUE"]))
						{
							foreach ($arProp["VALUE"] as &$strOneValue)
							{
								$strOneValue = htmlspecialcharsbx($strOneValue);
							}
							if (isset($strOneValue))
								unset($strOneValue);
						}
						else
						{
							$arProp["VALUE"] = htmlspecialcharsbx($arProp["VALUE"]);
						}

						if ($boolAllValues && 'Y' == $arProp['MULTIPLE'])
						{
							if (!isset($arAllProps[$strID]))
							{
								$arAllProps[$strID] = array(
									"NAME" => htmlspecialcharsbx($arProp["NAME"]),
									"VALUE" => array($arProp["VALUE"]),
									"VALUE_ENUM" => array(htmlspecialcharsbx($arProp["VALUE_ENUM"])),
									"VALUE_XML_ID" => array(htmlspecialcharsbx($arProp["VALUE_XML_ID"])),
									"DEFAULT_VALUE" => htmlspecialcharsbx($arProp["DEFAULT_VALUE"]),
									"SORT" => htmlspecialcharsbx($arProp["SORT"]),
									"MULTIPLE" => $arProp['MULTIPLE'],
								);
							}
							else
							{
								$arAllProps[$strID]['VALUE'][] = $arProp["VALUE"];
								$arAllProps[$strID]['VALUE_ENUM'][] = htmlspecialcharsbx($arProp["VALUE_ENUM"]);
								$arAllProps[$strID]['VALUE_XML_ID'][] = htmlspecialcharsbx($arProp["VALUE_XML_ID"]);
							}
						}
						else
						{
							$arAllProps[$strID] = array(
								"NAME" => htmlspecialcharsbx($arProp["NAME"]),
								"VALUE" => $arProp["VALUE"],
								"VALUE_ENUM" => htmlspecialcharsbx($arProp["VALUE_ENUM"]),
								"VALUE_XML_ID" => htmlspecialcharsbx($arProp["VALUE_XML_ID"]),
								"DEFAULT_VALUE" => htmlspecialcharsbx($arProp["DEFAULT_VALUE"]),
								"SORT" => htmlspecialcharsbx($arProp["SORT"]),
								"MULTIPLE" => $arProp['MULTIPLE'],
							);
						}
					}
					while($arProp = $dbProps->Fetch());

					$arIBlockElement["PROPERTIES"] = $arAllProps;
				}

				// bugfix: 2007-07-31 by Sigurd
				$arIBlockElement["PRODUCT"] = CCatalogProduct::GetByID($ID);

				$dbPrices = CPrice::GetList(array("SORT" => "ASC"), array("PRODUCT_ID" => $ID));
				if ($arPrices = $dbPrices->Fetch())
				{
					$arAllPrices = array();
					do
					{
						$arAllPrices[$arPrices["CATALOG_GROUP_ID"]] = array(
							"EXTRA_ID" => intval($arPrices["EXTRA_ID"]),
							"PRICE" => doubleval($arPrices["PRICE"]),
							"CURRENCY" => htmlspecialcharsbx($arPrices["CURRENCY"])
						);
					}
					while($arPrices = $dbPrices->Fetch());

					$arIBlockElement["PRICES"] = $arAllPrices;
				}

				return $arIBlockElement;
			}
		}

		return false;
	}

	public static function QuantityTracer($ProductID, $DeltaQuantity)
	{
		global $CACHE_MANAGER;

		$boolClearCache = false;

		$ProductID = (int)$ProductID;
		if ($ProductID <= 0)
			return false;
		$DeltaQuantity = (float)$DeltaQuantity;
		if ($DeltaQuantity==0)
			return false;

		$rsProducts = CCatalogProduct::GetList(
			array(),
			array('ID' => $ProductID),
			false,
			false,
			array('ID', 'CAN_BUY_ZERO', 'NEGATIVE_AMOUNT_TRACE', 'QUANTITY_TRACE', 'QUANTITY', 'ELEMENT_IBLOCK_ID')
		);
		if (($arProduct = $rsProducts->Fetch())
			&& ($arProduct["QUANTITY_TRACE"]=="Y"))
		{
			$strAllowNegativeAmount = $arProduct["NEGATIVE_AMOUNT_TRACE"];

			$arFields = array();
			$arFields["QUANTITY"] = (float)$arProduct["QUANTITY"] - $DeltaQuantity;

			if ('Y' != $arProduct['CAN_BUY_ZERO'])
			{
				if (defined("BX_COMP_MANAGED_CACHE"))
				{
					$boolClearCache = (0 >= $arFields["QUANTITY"]*$arProduct["QUANTITY"]);
				}
			}

			if ('Y' != $arProduct['CAN_BUY_ZERO'] || 'Y' != $strAllowNegativeAmount)
			{
				if (0 >= $arFields["QUANTITY"])
					$arFields["QUANTITY"] = 0;
			}

			$arFields['OLD_QUANTITY'] = $arProduct["QUANTITY"];
			CCatalogProduct::Update($arProduct["ID"], $arFields);

			if ($boolClearCache)
				$CACHE_MANAGER->ClearByTag('iblock_id_'.$arProduct['ELEMENT_IBLOCK_ID']);

			$arProduct['OLD_QUANTITY'] = $arFields['OLD_QUANTITY'];
			$arProduct['QUANTITY'] = $arFields['QUANTITY'];
			$arProduct['ALLOW_NEGATIVE_AMOUNT'] = $strAllowNegativeAmount;
			$arProduct['DELTA'] = $DeltaQuantity;
			foreach (GetModuleEvents("catalog", "OnProductQuantityTrace", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, array($arProduct["ID"], $arProduct));
			}

			return true;
		}

		return false;
	}

	/**
	 * @param int $productID
	 * @param int|float $quantity
	 * @param array $arUserGroups
	 * @return bool|float|int
	 */
	public static function GetNearestQuantityPrice($productID, $quantity = 1, $arUserGroups = array())
	{
		static $eventOnGetExists = null;
		static $eventOnResultExists = null;

		global $APPLICATION;

		if ($eventOnGetExists === true || $eventOnGetExists === null)
		{
			foreach (GetModuleEvents('catalog', 'OnGetNearestQuantityPrice', true) as $arEvent)
			{
				$eventOnGetExists = true;
				$mxResult = ExecuteModuleEventEx($arEvent, array($productID, $quantity, $arUserGroups));
				if ($mxResult !== true)
					return $mxResult;
			}
			if ($eventOnGetExists === null)
				$eventOnGetExists = false;
		}

		// Check input params
		$productID = (int)$productID;
		if ($productID <= 0)
		{
			$APPLICATION->ThrowException(Loc::getMessage("BT_MOD_CATALOG_PROD_ERR_PRODUCT_ID_ABSENT"), "NO_PRODUCT_ID");
			return false;
		}

		$quantity = (float)$quantity;
		if ($quantity <= 0)
		{
			$APPLICATION->ThrowException(Loc::getMessage("BT_MOD_CATALOG_PROD_ERR_QUANTITY_ABSENT"), "NO_QUANTITY");
			return false;
		}

		if (!is_array($arUserGroups) && (int)$arUserGroups.'|' == (string)$arUserGroups.'|')
			$arUserGroups = array((int)$arUserGroups);

		if (!is_array($arUserGroups))
			$arUserGroups = array();

		if (!in_array(2, $arUserGroups))
			$arUserGroups[] = 2;

		$quantityDifference = -1;
		$nearestQuantity = -1;

		// Find nearest quantity
		$dbPriceList = CPrice::GetListEx(
			array(),
			array(
				"PRODUCT_ID" => $productID,
				"GROUP_GROUP_ID" => $arUserGroups,
				"GROUP_BUY" => "Y"
			),
			false,
			false,
			array("ID", "QUANTITY_FROM", "QUANTITY_TO")
		);
		while ($arPriceList = $dbPriceList->Fetch())
		{
			$arPriceList['QUANTITY_FROM'] = (float)$arPriceList['QUANTITY_FROM'];
			$arPriceList['QUANTITY_TO'] = (float)$arPriceList['QUANTITY_TO'];
			if ($quantity >= $arPriceList["QUANTITY_FROM"]
				&& ($quantity <= $arPriceList["QUANTITY_TO"] || $arPriceList["QUANTITY_TO"] == 0))
			{
				$nearestQuantity = $quantity;
				break;
			}

			if ($quantity < $arPriceList["QUANTITY_FROM"])
			{
				$nearestQuantity_tmp = $arPriceList["QUANTITY_FROM"];
				$quantityDifference_tmp = $arPriceList["QUANTITY_FROM"] - $quantity;
			}
			else
			{
				$nearestQuantity_tmp = $arPriceList["QUANTITY_TO"];
				$quantityDifference_tmp = $quantity - $arPriceList["QUANTITY_TO"];
			}

			if ($quantityDifference < 0 || $quantityDifference_tmp < $quantityDifference)
			{
				$quantityDifference = $quantityDifference_tmp;
				$nearestQuantity = $nearestQuantity_tmp;
			}
		}

		if ($eventOnResultExists === true || $eventOnResultExists === null)
		{
			foreach (GetModuleEvents('catalog', 'OnGetNearestQuantityPriceResult', true) as $arEvent)
			{
				$eventOnResultExists = true;
				if (ExecuteModuleEventEx($arEvent, array(&$nearestQuantity)) === false)
					return false;
			}
			if ($eventOnResultExists === null)
				$eventOnResultExists = false;
		}

		return ($nearestQuantity > 0 ? $nearestQuantity : false);
	}

	/**
	 * @param int $intProductID
	 * @param int|float $quantity
	 * @param array $arUserGroups
	 * @param string $renewal
	 * @param array $arPrices
	 * @param bool|string $siteID
	 * @param bool|array $arDiscountCoupons
	 * @return array|bool
	 */
	public static function GetOptimalPrice($intProductID, $quantity = 1, $arUserGroups = array(), $renewal = "N", $arPrices = array(), $siteID = false, $arDiscountCoupons = false)
	{
		static $eventOnGetExists = null;
		static $eventOnResultExists = null;

		static $priceTypeCache = array();

		global $APPLICATION;

		if ($eventOnGetExists === true || $eventOnGetExists === null)
		{
			foreach (GetModuleEvents('catalog', 'OnGetOptimalPrice', true) as $arEvent)
			{
				$eventOnGetExists = true;
				$mxResult = ExecuteModuleEventEx($arEvent, array($intProductID, $quantity, $arUserGroups, $renewal, $arPrices, $siteID, $arDiscountCoupons));
				if ($mxResult !== true)
				{
					self::updateUserHandlerOptimalPrice($mxResult);
					if (!empty($mxResult) && is_array($mxResult))
						$mxResult['PRODUCT_ID'] = $intProductID;
					return $mxResult;
				}
			}
			if ($eventOnGetExists === null)
				$eventOnGetExists = false;
		}

		$intProductID = (int)$intProductID;
		if ($intProductID <= 0)
		{
			$APPLICATION->ThrowException(Loc::getMessage("BT_MOD_CATALOG_PROD_ERR_PRODUCT_ID_ABSENT"), "NO_PRODUCT_ID");
			return false;
		}

		$quantity = (float)$quantity;
		if ($quantity <= 0)
		{
			$APPLICATION->ThrowException(Loc::getMessage("BT_MOD_CATALOG_PROD_ERR_QUANTITY_ABSENT"), "NO_QUANTITY");
			return false;
		}

		if (!is_array($arUserGroups) && (int)$arUserGroups.'|' == (string)$arUserGroups.'|')
			$arUserGroups = array((int)$arUserGroups);

		if (!is_array($arUserGroups))
			$arUserGroups = array();

		if (!in_array(2, $arUserGroups))
			$arUserGroups[] = 2;
		Main\Type\Collection::normalizeArrayValuesByInt($arUserGroups);

		$renewal = ($renewal == 'Y' ? 'Y' : 'N');

		if ($siteID === false)
			$siteID = SITE_ID;

		$resultCurrency = Currency\CurrencyManager::getBaseCurrency();
		if (empty($resultCurrency))
		{
			$APPLICATION->ThrowException(Loc::getMessage("BT_MOD_CATALOG_PROD_ERR_NO_BASE_CURRENCY"), "NO_BASE_CURRENCY");
			return false;
		}
		if (self::$usedCurrency !== null)
			$resultCurrency = self::$usedCurrency;

		$intIBlockID = (int)CIBlockElement::GetIBlockByID($intProductID);
		if ($intIBlockID <= 0)
		{
			$APPLICATION->ThrowException(
				Loc::getMessage(
					'BT_MOD_CATALOG_PROD_ERR_ELEMENT_ID_NOT_FOUND',
					array('#ID#' => $intProductID)
				),
				'NO_ELEMENT'
			);
			return false;
		}

		if (!isset($arPrices) || !is_array($arPrices))
			$arPrices = array();

		if (empty($arPrices))
		{
			$cacheKey = 'U'.implode('_', $arUserGroups);
			if (!isset($priceTypeCache[$cacheKey]))
			{
				$priceTypeCache[$cacheKey] = array();
				$priceIterator = CCatalogGroup::GetGroupsList(array('@GROUP_ID' => $arUserGroups, '=BUY' => 'Y'));
				while ($priceType = $priceIterator->Fetch())
				{
					$priceTypeId = (int)$priceType['CATALOG_GROUP_ID'];
					$priceTypeCache[$cacheKey][$priceTypeId] = $priceTypeId;
					unset($priceTypeId);
				}
				unset($priceType, $priceIterator);
			}
			if (empty($priceTypeCache[$cacheKey]))
				return false;

			$dbPriceList = CPrice::GetListEx(
				array(),
				array(
					"PRODUCT_ID" => $intProductID,
					"@CATALOG_GROUP_ID" => $priceTypeCache[$cacheKey],
					"+<=QUANTITY_FROM" => $quantity,
					"+>=QUANTITY_TO" => $quantity
				),
				false,
				false,
				array("ID", "CATALOG_GROUP_ID", "PRICE", "CURRENCY")
			);
			while ($arPriceList = $dbPriceList->Fetch())
			{
				$arPriceList['ELEMENT_IBLOCK_ID'] = $intIBlockID;
				$arPrices[] = $arPriceList;
			}
			unset($arPriceList, $dbPriceList);
			unset($cacheKey);
		}
		else
		{
			foreach (array_keys($arPrices) as $priceIndex)
				$arPrices[$priceIndex]['ELEMENT_IBLOCK_ID'] = $intIBlockID;
			unset($priceIndex);
		}

		if (empty($arPrices))
			return false;

		$rsVAT = CCatalogProduct::GetVATInfo($intProductID);
		if ($arVAT = $rsVAT->Fetch())
			$arVAT['RATE'] = (float)$arVAT['RATE'] * 0.01;
		else
			$arVAT = array('RATE' => 0.0, 'VAT_INCLUDED' => 'N');
		unset($rsVAT);

		if (self::getUseDiscount())
		{
			if ($arDiscountCoupons === false)
				$arDiscountCoupons = CCatalogDiscountCoupon::GetCoupons();
		}

//		$boolDiscountVat = ('N' != COption::GetOptionString('catalog', 'discount_vat', 'Y'));
		$boolDiscountVat = true;

		$minPrice = false;
		$basePrice = false;
		$arMinPrice = array();
		$arMinDiscounts = array();

		foreach ($arPrices as &$arPriceList)
		{
			$arPriceList['VAT_RATE'] = $arVAT['RATE'];
			$arPriceList['VAT_INCLUDED'] = $arVAT['VAT_INCLUDED'];

			$currentPrice = $arPriceList['PRICE'];
			if ($boolDiscountVat)
			{
				if ('N' == $arPriceList['VAT_INCLUDED'])
					$currentPrice *= (1 + $arPriceList['VAT_RATE']);
			}
			else
			{
				if ('Y' == $arPriceList['VAT_INCLUDED'])
					$currentPrice /= (1 + $arPriceList['VAT_RATE']);
			}

			if ($arPriceList['CURRENCY'] != $resultCurrency)
				$currentPrice = CCurrencyRates::ConvertCurrency($currentPrice, $arPriceList['CURRENCY'], $resultCurrency);
			$currentPrice = roundEx($currentPrice, CATALOG_VALUE_PRECISION);

			$arDiscounts = array();
			if (self::getUseDiscount())
				$arDiscounts = CCatalogDiscount::GetDiscount($intProductID, $intIBlockID, $arPriceList["CATALOG_GROUP_ID"], $arUserGroups, $renewal, $siteID, $arDiscountCoupons);

			$result = CCatalogDiscount::applyDiscountList($currentPrice, $resultCurrency, $arDiscounts);
			if ($result === false)
				return false;

			if ($minPrice === false || $minPrice > $result['PRICE'])
			{
				$basePrice = $currentPrice;
				$minPrice = $result['PRICE'];
				$arMinPrice = $arPriceList;
				$arMinDiscounts = $result['DISCOUNT_LIST'];
			}
			unset($currentPrice);
		}
		unset($arPriceList);

		if ($boolDiscountVat)
		{
			if (!self::$optimalPriceWithVat)
			{
				$minPrice /= (1 + $arMinPrice['VAT_RATE']);
				$basePrice /= (1 + $arMinPrice['VAT_RATE']);

				$minPrice = roundEx($minPrice, CATALOG_VALUE_PRECISION);
				$basePrice = roundEx($basePrice, CATALOG_VALUE_PRECISION);
			}
		}
		else
		{
			if (self::$optimalPriceWithVat)
			{
				$minPrice *= (1 + $arMinPrice['VAT_RATE']);
				$basePrice *= (1 + $arMinPrice['VAT_RATE']);

				$minPrice = roundEx($minPrice, CATALOG_VALUE_PRECISION);
				$basePrice = roundEx($basePrice, CATALOG_VALUE_PRECISION);
			}
		}

		$arResult = array(
			'PRICE' => $arMinPrice,
			'RESULT_PRICE' => array(
				'BASE_PRICE' => $basePrice,
				'DISCOUNT_PRICE' => $minPrice,
				'DISCOUNT' => $basePrice - $minPrice,
				'PERCENT' => ($basePrice > 0 ? roundEx((100*($basePrice - $minPrice))/$basePrice, CATALOG_VALUE_PRECISION) : 0),
				'CURRENCY' => $resultCurrency,
				'VAT_RATE' => $arMinPrice['VAT_RATE'],
				'VAT_INCLUDED' => (self::$optimalPriceWithVat ? 'Y' : 'N')
			),
			'DISCOUNT_PRICE' => $minPrice,
			'DISCOUNT' => array(),
			'DISCOUNT_LIST' => array(),
			'PRODUCT_ID' => $intProductID
		);
		if (!empty($arMinDiscounts))
		{
			reset($arMinDiscounts);
			$arResult['DISCOUNT'] = current($arMinDiscounts);
			$arResult['DISCOUNT_LIST'] = $arMinDiscounts;
		}

		if ($eventOnResultExists === true || $eventOnResultExists === null)
		{
			foreach (GetModuleEvents('catalog', 'OnGetOptimalPriceResult', true) as $arEvent)
			{
				$eventOnResultExists = true;
				if (ExecuteModuleEventEx($arEvent, array(&$arResult)) === false)
					return false;
			}
			if ($eventOnResultExists === null)
				$eventOnResultExists = false;
		}

		return $arResult;
	}

	/**
	 * @param float $price
	 * @param string $currency
	 * @param array $arDiscounts
	 * @return bool|float
	 */
	public static function CountPriceWithDiscount($price, $currency, $arDiscounts)
	{
		static $eventOnGetExists = null;
		static $eventOnResultExists = null;

		if ($eventOnGetExists === true || $eventOnGetExists === null)
		{
			foreach (GetModuleEvents('catalog', 'OnCountPriceWithDiscount', true) as $arEvent)
			{
				$eventOnGetExists = true;
				$mxResult = ExecuteModuleEventEx($arEvent, array($price, $currency, $arDiscounts));
				if ($mxResult !== true)
					return $mxResult;
			}
			if ($eventOnGetExists === null)
				$eventOnGetExists = false;
		}

		$currency = CCurrency::checkCurrencyID($currency);
		if ($currency === false)
			return false;

		$price = (float)$price;
		if ($price <= 0)
			return $price;

		if (empty($arDiscounts) || !is_array($arDiscounts))
			return $price;

		$result = CCatalogDiscount::applyDiscountList($price, $currency, $arDiscounts);
		if ($result === false)
			return false;

		$currentMinPrice = $result['PRICE'];

		if ($eventOnResultExists === true || $eventOnResultExists === null)
		{
			foreach (GetModuleEvents('catalog', 'OnCountPriceWithDiscountResult', true) as $arEvent)
			{
				$eventOnResultExists = true;
				if (ExecuteModuleEventEx($arEvent, array(&$currentMinPrice)) === false)
					return false;
			}
			if ($eventOnResultExists === null)
				$eventOnResultExists = false;
		}

		return $currentMinPrice;
	}

	public function GetProductSections($ID)
	{
		/** @global CStackCacheManager $stackCacheManager */
		global $stackCacheManager;

		$ID = (int)$ID;
		if ($ID <= 0)
			return false;

		$cacheTime = CATALOG_CACHE_DEFAULT_TIME;
		if (defined('CATALOG_CACHE_TIME'))
			$cacheTime = intval(CATALOG_CACHE_TIME);

		$arProductSections = array();

		$dbElementSections = CIBlockElement::GetElementGroups($ID, false, array('ID', 'ADDITIONAL_PROPERTY_ID'));
		while ($arElementSections = $dbElementSections->Fetch())
		{
			if ((int)$arElementSections['ADDITIONAL_PROPERTY_ID'] > 0)
				continue;
			$arSectionsTmp = array();

			$strCacheKey = "p".$arElementSections["ID"];

			$stackCacheManager->SetLength("catalog_group_parents", 50);
			$stackCacheManager->SetTTL("catalog_group_parents", $cacheTime);
			if ($stackCacheManager->Exist("catalog_group_parents", $strCacheKey))
			{
				$arSectionsTmp = $stackCacheManager->Get("catalog_group_parents", $strCacheKey);
			}
			else
			{
				$dbSection = CIBlockSection::GetList(
					array(),
					array('ID' => $arElementSections["ID"]),
					false,
					array(
						'ID',
						'IBLOCK_ID',
						'LEFT_MARGIN',
						'RIGHT_MARGIN',
					)
				);
				if ($arSection = $dbSection->Fetch())
				{
					$dbSectionTree = CIBlockSection::GetList(
						array("LEFT_MARGIN" => "DESC"),
						array(
							"IBLOCK_ID" => $arSection["IBLOCK_ID"],
							"ACTIVE" => "Y",
							"GLOBAL_ACTIVE" => "Y",
							"IBLOCK_ACTIVE" => "Y",
							"<=LEFT_BORDER" => $arSection["LEFT_MARGIN"],
							">=RIGHT_BORDER" => $arSection["RIGHT_MARGIN"]
						),
						false,
						array('ID')
					);
					while ($arSectionTree = $dbSectionTree->Fetch())
					{
						$arSectionTree["ID"] = intval($arSectionTree["ID"]);
						$arSectionsTmp[] = $arSectionTree["ID"];
					}
				}

				$stackCacheManager->Set("catalog_group_parents", $strCacheKey, $arSectionsTmp);
			}

			$arProductSections = array_merge($arProductSections, $arSectionsTmp);
		}

		$arProductSections = array_unique($arProductSections);

		return $arProductSections;
	}

	public static function OnIBlockElementDelete($ProductID)
	{
		return CCatalogProduct::Delete($ProductID);
	}

	public static function OnAfterIBlockElementUpdate($arFields)
	{
		if (isset($arFields["IBLOCK_SECTION"]))
		{
			/** @global CStackCacheManager $stackCacheManager */
			global $stackCacheManager;
			$stackCacheManager->Clear("catalog_element_groups");
		}
	}

	public static function CheckProducts($arItemIDs)
	{
		if (!is_array($arItemIDs))
			$arItemIDs = array($arItemIDs);
		Main\Type\Collection::normalizeArrayValuesByInt($arItemIDs);
		if (empty($arItemIDs))
			return false;
		$arProductList = array();
		$rsProducts = CCatalogProduct::GetList(
			array(),
			array('@ID' => $arItemIDs),
			false,
			false,
			array('ID')
		);
		while ($arProduct = $rsProducts->Fetch())
		{
			$arProduct['ID'] = (int)$arProduct['ID'];
			$arProductList[$arProduct['ID']] = true;
		}
		if (empty($arProductList))
			return false;
		$boolFlag = true;
		foreach ($arItemIDs as &$intItemID)
		{
			if (!isset($arProductList[$intItemID]))
			{
				$boolFlag = false;
				break;
			}
		}
		unset($intItemID);
		return $boolFlag;
	}

	public static function GetTimePeriodTypes($boolFull = false)
	{
		$boolFull = ($boolFull === true);
		if ($boolFull)
		{
			return array(
				self::TIME_PERIOD_HOUR => Loc::getMessage('BT_MOD_CATALOG_PROD_PERIOD_HOUR'),
				self::TIME_PERIOD_DAY => Loc::getMessage('BT_MOD_CATALOG_PROD_PERIOD_DAY'),
				self::TIME_PERIOD_WEEK => Loc::getMessage('BT_MOD_CATALOG_PROD_PERIOD_WEEK'),
				self::TIME_PERIOD_MONTH => Loc::getMessage('BT_MOD_CATALOG_PROD_PERIOD_MONTH'),
				self::TIME_PERIOD_QUART => Loc::getMessage('BT_MOD_CATALOG_PROD_PERIOD_QUART'),
				self::TIME_PERIOD_SEMIYEAR => Loc::getMessage('BT_MOD_CATALOG_PROD_PERIOD_SEMIYEAR'),
				self::TIME_PERIOD_YEAR => Loc::getMessage('BT_MOD_CATALOG_PROD_PERIOD_YEAR')
			);
		}
		return array(
			self::TIME_PERIOD_HOUR,
			self::TIME_PERIOD_DAY,
			self::TIME_PERIOD_WEEK,
			self::TIME_PERIOD_MONTH,
			self::TIME_PERIOD_QUART,
			self::TIME_PERIOD_SEMIYEAR,
			self::TIME_PERIOD_YEAR
		);
	}

	/**
	 * Update result user handlers for event OnGetOptimalPrice.
	 *
	 * @param array &$userResult		Optimal price array.
	 * @return void
	 */
	public static function updateUserHandlerOptimalPrice(&$userResult)
	{
		global $APPLICATION;
		if (empty($userResult) || !is_array($userResult))
		{
			$userResult = false;
			return;
		}
		if (empty($userResult['PRICE']) || !is_array($userResult['PRICE']))
		{
			$userResult = false;
			return;
		}
		if (empty($userResult['RESULT_PRICE']) || !is_array($userResult['RESULT_PRICE']))
		{
			$resultCurrency = Currency\CurrencyManager::getBaseCurrency();
			if (empty($resultCurrency))
			{
				$APPLICATION->ThrowException(Loc::getMessage('BT_MOD_CATALOG_PROD_ERR_NO_BASE_CURRENCY'), 'NO_BASE_CURRENCY');
				$userResult = false;

				return;
			}
			if (self::$usedCurrency !== null)
				$resultCurrency = self::$usedCurrency;

			$oldDiscountExist = !empty($userResult['DISCOUNT']) && is_array($userResult['DISCOUNT']);
			if ($oldDiscountExist)
			{
				if (empty($userResult['DISCOUNT']['MODULE_ID']))
					$userResult['DISCOUNT']['MODULE_ID'] = 'catalog';
				if ($userResult['DISCOUNT']['CURRENCY'] != $resultCurrency)
					Catalog\DiscountTable::convertCurrency($userResult['DISCOUNT'], $resultCurrency);
			}
			if (!isset($userResult['DISCOUNT_LIST']) || !is_array($userResult['DISCOUNT_LIST']))
			{
				$userResult['DISCOUNT_LIST'] = array();
				if ($oldDiscountExist)
					$userResult['DISCOUNT_LIST'][] = $userResult['DISCOUNT'];
			}
			if (isset($userResult['DISCOUNT_LIST']))
			{
				foreach ($userResult['DISCOUNT_LIST'] as &$discount)
				{
					if (empty($discount['MODULE_ID']))
						$discount['MODULE_ID'] = 'catalog';
					if ($discount['CURRENCY'] != $resultCurrency)
						Catalog\DiscountTable::convertCurrency($discount, $resultCurrency);
				}
				unset($discount);
			}
			$userResult['RESULT_PRICE'] = CCatalogDiscount::calculateDiscountList($userResult['PRICE'], $resultCurrency, $userResult['DISCOUNT_LIST'], self::$optimalPriceWithVat);
		}
		else
		{
			$userResult['RESULT_PRICE']['BASE_PRICE'] = roundEx($userResult['RESULT_PRICE']['BASE_PRICE'], CATALOG_VALUE_PRECISION);
			$userResult['RESULT_PRICE']['DISCOUNT'] = roundEx($userResult['RESULT_PRICE']['DISCOUNT'], CATALOG_VALUE_PRECISION);
			$userResult['RESULT_PRICE']['DISCOUNT_PRICE'] = $userResult['RESULT_PRICE']['BASE_PRICE'] - $userResult['RESULT_PRICE']['DISCOUNT'];
			$userResult['RESULT_PRICE']['VAT_RATE'] = $userResult['PRICE']['VAT_RATE'];
		}
	}

	/**
	* @deprecated deprecated since catalog 15.0.0
	* @see CCatalogDiscount::applyDiscountList()
	* @see CCatalogDiscount::primaryDiscountFilter()
	*/
	protected static function __PrimaryDiscountFilter(&$arDiscount, &$arPriceDiscount, &$arDiscSave, &$arParams)
	{
		if (isset($arParams['PRICE']) && isset($arParams['CURRENCY']))
		{
			$arParams['PRICE'] = (float)$arParams['PRICE'];
			$arParams['BASE_PRICE'] = $arParams['PRICE'];
			if ($arParams['PRICE'] > 0)
			{
				$arPriceDiscount = array();
				$arDiscSave = array();

				foreach ($arDiscount as $arOneDiscount)
				{
					$changeData = ($arParams['CURRENCY'] != $arOneDiscount['CURRENCY']);
					$dblDiscountValue = 0.0;
					$arOneDiscount['PRIORITY'] = (int)$arOneDiscount['PRIORITY'];
					if (CCatalogDiscount::TYPE_FIX == $arOneDiscount['VALUE_TYPE'])
					{
						$dblDiscountValue = (
							!$changeData
							? $arOneDiscount['VALUE']
							: roundEx(
								CCurrencyRates::ConvertCurrency($arOneDiscount['VALUE'], $arOneDiscount['CURRENCY'], $arParams['CURRENCY']),
								CATALOG_VALUE_PRECISION
							)
						);
						if ($arParams['PRICE'] < $dblDiscountValue)
							continue;
						$arOneDiscount['DISCOUNT_CONVERT'] = $dblDiscountValue;
						if ($changeData)
							$arOneDiscount['VALUE'] = $arOneDiscount['DISCOUNT_CONVERT'];
					}
					elseif (CCatalogDiscount::TYPE_SALE == $arOneDiscount['VALUE_TYPE'])
					{
						$dblDiscountValue = (
							!$changeData
							? $arOneDiscount['VALUE']
							: roundEx(
								CCurrencyRates::ConvertCurrency($arOneDiscount['VALUE'], $arOneDiscount['CURRENCY'], $arParams['CURRENCY']),
								CATALOG_VALUE_PRECISION
							)
						);
						if ($arParams['PRICE'] <= $dblDiscountValue)
							continue;
						$arOneDiscount['DISCOUNT_CONVERT'] = $dblDiscountValue;
						if ($changeData)
							$arOneDiscount['VALUE'] = $arOneDiscount['DISCOUNT_CONVERT'];
					}
					elseif (CCatalogDiscount::TYPE_PERCENT == $arOneDiscount['VALUE_TYPE'])
					{
						if (100 < $arOneDiscount["VALUE"])
							continue;
						if ($arOneDiscount['TYPE'] == CCatalogDiscount::ENTITY_ID && $arOneDiscount["MAX_DISCOUNT"] > 0)
						{
							$dblDiscountValue = (
								!$changeData
								? $arOneDiscount['MAX_DISCOUNT']
								: roundEx(
									CCurrencyRates::ConvertCurrency($arOneDiscount['MAX_DISCOUNT'], $arOneDiscount['CURRENCY'], $arParams['CURRENCY']),
									CATALOG_VALUE_PRECISION
								)
							);
							$arOneDiscount['DISCOUNT_CONVERT'] = $dblDiscountValue;
							if ($changeData)
								$arOneDiscount['MAX_DISCOUNT'] = $arOneDiscount['DISCOUNT_CONVERT'];
						}
					}
					if ($changeData)
						$arOneDiscount['CURRENCY'] = $arParams['CURRENCY'];
					if ($arOneDiscount['TYPE'] == CCatalogDiscountSave::ENTITY_ID)
					{
						$arDiscSave[] = $arOneDiscount;
					}
					else
					{
						$arPriceDiscount[$arOneDiscount['PRIORITY']][] = $arOneDiscount;
					}
				}

				if (!empty($arPriceDiscount))
					krsort($arPriceDiscount);
			}
		}
	}

	/**
	* @deprecated deprecated since catalog 15.0.0
	* @see CCatalogDiscount::applyDiscountList()
	* @see CCatalogDiscount::calculatePriorityLevel()
	*/
	protected static function __CalcOnePriority(&$arDiscounts, &$arResultDiscount, &$arParams)
	{
		$boolResult = false;
		if (isset($arParams['PRICE']) && isset($arParams['CURRENCY']))
		{
			$arParams['PRICE'] = (float)$arParams['PRICE'];
			$arParams['BASE_PRICE'] = (float)$arParams['BASE_PRICE'];
			if ($arParams['PRICE'] > 0)
			{
				$dblCurrentPrice = $arParams['PRICE'];
				do
				{
					$dblMinPrice = -1;
					$strMinKey = -1;
					$boolLast = false;
					$boolApply = false;
					foreach ($arDiscounts as $strDiscountKey => $arOneDiscount)
					{
						$boolDelete = false;
						$dblPriceTmp = -1;
						switch($arOneDiscount['VALUE_TYPE'])
						{
						case CCatalogDiscount::TYPE_PERCENT:
							$dblTempo = roundEx((
								CCatalogDiscount::getUseBasePrice()
								? $arParams['BASE_PRICE']
								: $dblCurrentPrice
								)*$arOneDiscount['VALUE']/100,
								CATALOG_VALUE_PRECISION
							);
							if (isset($arOneDiscount['DISCOUNT_CONVERT']))
							{
								if ($dblTempo > $arOneDiscount['DISCOUNT_CONVERT'])
									$dblTempo = $arOneDiscount['DISCOUNT_CONVERT'];
							}
							$dblPriceTmp = $dblCurrentPrice - $dblTempo;
							break;
						case CCatalogDiscount::TYPE_FIX:
							if ($arOneDiscount['DISCOUNT_CONVERT'] > $dblCurrentPrice)
							{
								$boolDelete = true;
							}
							else
							{
								$dblPriceTmp = $dblCurrentPrice - $arOneDiscount['DISCOUNT_CONVERT'];
							}
							break;
						case CCatalogDiscount::TYPE_SALE:
							if (!($arOneDiscount['DISCOUNT_CONVERT'] < $dblCurrentPrice))
							{
								$boolDelete = true;
							}
							else
							{
								$dblPriceTmp = $arOneDiscount['DISCOUNT_CONVERT'];
							}
							break;
						}
						if ($boolDelete)
						{
							unset($arDiscounts[$strDiscountKey]);
						}
						else
						{
							if (-1 == $dblMinPrice || $dblMinPrice > $dblPriceTmp)
							{
								$dblMinPrice = $dblPriceTmp;
								$strMinKey = $strDiscountKey;
								$boolApply = true;
							}
						}
					}
					if ($boolApply)
					{
						$dblCurrentPrice = $dblMinPrice;
						$arResultDiscount[] = $arDiscounts[$strMinKey];
						if ('Y' == $arDiscounts[$strMinKey]['LAST_DISCOUNT'])
						{
							$arDiscounts = array();
							$arParams['LAST_DISCOUNT'] = 'Y';
						}
						unset($arDiscounts[$strMinKey]);
					}
				} while (!empty($arDiscounts));
				if ($boolApply)
				{
					$arParams['PRICE'] = $dblCurrentPrice;
				}
				$boolResult = true;
			}
		}
		return $boolResult;
	}

	/**
	* @deprecated deprecated since catalog 15.0.0
	* @see CCatalogDiscount::applyDiscountList()
	* @see CCatalogDiscount::calculateDiscSave()
	*/
	protected static function __CalcDiscSave(&$arDiscSave, &$arResultDiscount, &$arParams)
	{
		$boolResult = false;
		if (isset($arParams['PRICE']) && isset($arParams['CURRENCY']))
		{
			$arParams['PRICE'] = (float)$arParams['PRICE'];
			if (0 < $arParams['PRICE'])
			{
				$dblCurrentPrice = $arParams['PRICE'];
				$dblMinPrice = -1;
				$strMinKey = -1;
				$boolApply = false;
				foreach ($arDiscSave as $strDiscountKey => $arOneDiscount)
				{
					$dblPriceTmp = -1;
					$boolDelete = false;
					switch($arOneDiscount['VALUE_TYPE'])
					{
					case CCatalogDiscountSave::TYPE_PERCENT:
						$dblPriceTmp = roundEx($dblCurrentPrice*(1 - $arOneDiscount['VALUE']/100.0), CATALOG_VALUE_PRECISION);
						break;
					case CCatalogDiscountSave::TYPE_FIX:
						if ($arOneDiscount['DISCOUNT_CONVERT'] > $dblCurrentPrice)
						{
							$boolDelete = true;
						}
						else
						{
							$dblPriceTmp = $dblCurrentPrice - $arOneDiscount['DISCOUNT_CONVERT'];
						}
						break;
					}
					if (!$boolDelete)
					{
						if (-1 == $dblMinPrice || $dblMinPrice > $dblPriceTmp)
						{
							$dblMinPrice = $dblPriceTmp;
							$strMinKey = $strDiscountKey;
							$boolApply = true;
						}
					}
				}
				if ($boolApply)
				{
					$arParams['PRICE'] = $dblMinPrice;
					$arResultDiscount[] = $arDiscSave[$strMinKey];
				}
				$boolResult = true;
			}
		}
		return $boolResult;
	}

	protected static function getQueryBuildCurrencyScale($filter, $priceTypeId)
	{
		$result = array();
		if (!isset($filter['CATALOG_CURRENCY_SCALE_'.$priceTypeId]))
			return $result;
		$currencId = Currency\CurrencyManager::checkCurrencyID($filter['CATALOG_CURRENCY_SCALE_'.$priceTypeId]);
		if ($currencId === false)
			return $result;

		$currency = CCurrency::GetByID($currencId);
		if (empty($currency))
			return $result;

		$result['CURRENCY'] = $currency['CURRENCY'];
		$result['BASE_RATE'] = $currency['CURRENT_BASE_RATE'];

		return $result;
	}

	protected static function getQueryBuildPriceScaled($prices, $scale)
	{
		$result = array();
		$scale = (float)$scale;
		if (!is_array($prices))
			$prices = array($prices);
		if (empty($prices) || $scale <= 0)
			return $result;
		foreach ($prices as &$value)
			$result[] = (float)$value*$scale;
		unset($value);
		return $result;
	}
}