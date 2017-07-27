<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */

namespace Bitrix\Main\Analytics;
use Bitrix\Catalog\CatalogViewedProductTable;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;

\CModule::IncludeModule('catalog');

/**
 * @package bitrix
 * @subpackage main
 */ 
class Catalog
{
	protected static $cookieLogName = 'RCM_PRODUCT_LOG';

	// basket (catalog:OnBasketAdd)
	public static function catchCatalogBasket($id, $arFields)
	{
		global $APPLICATION;

		// alter b_sale_basket - add recommendation, update it here
		if (!static::isOn())
		{
			return;
		}

		// get product id by offer id
		$productInfo = \CCatalogSKU::GetProductInfo($arFields['PRODUCT_ID']);

		if (!empty($productInfo['ID']))
		{
			$realProductId = $productInfo['ID'];
		}
		else
		{
			$realProductId = $arFields['PRODUCT_ID'];
		}

		// select site user id & recommendation id
		$siteUserId = 0;
		$recommendationId = '';

		// first, try to find in cookies
		$recommendationCookie = $APPLICATION->get_cookie(static::getCookieLogName());

		if (!empty($recommendationCookie))
		{
			$recommendations = static::decodeProductLog($recommendationCookie);

			if (is_array($recommendations) && isset($recommendations[$realProductId]))
			{
				$recommendationId = $recommendations[$realProductId][0];
			}
		}

		if (empty($recommendationId))
		{
			// ok then, lets see in views history
			//if(\COption::GetOptionString("sale", "encode_fuser_id", "N") == "Y")
			if (!is_numeric($arFields['FUSER_ID']))
			{
				$filter = array('CODE' => $arFields['FUSER_ID']);
			}
			else
			{
				$filter = array('ID' => $arFields['FUSER_ID']);
			}

			$result = \CSaleUser::getList($filter);

			if (!empty($result))
			{
				$siteUserId = $result['USER_ID'];

				// select recommendation id
				$fuser = $result['ID'];

				$viewResult = CatalogViewedProductTable::getList(array(
					'select' => array('RECOMMENDATION'),
					'filter' => array(
						'=FUSER_ID' => $fuser,
						'=PRODUCT_ID' => $arFields['PRODUCT_ID']
					),
					'order' => array('DATE_VISIT' => 'DESC')
				))->fetch();

				if (!empty($viewResult['RECOMMENDATION']))
				{
					$recommendationId = $viewResult['RECOMMENDATION'];
				}
			}
		}

		// prepare data
		$data = array(
			'product_id' => $realProductId,
			'user_id' => $siteUserId,
			'bx_user_id' => static::getBxUserId(),
			'domain' => Context::getCurrent()->getServer()->getHttpHost(),
			'recommendation' => $recommendationId,
			'date' => date(DATE_ISO8601)
		);

		CounterDataTable::add(array(
			'TYPE' => 'basket',
			'DATA' => $data
		));

		// update basket with recommendation id
		if (!empty($recommendationId))
		{
			$conn = Application::getConnection();
			$helper = $conn->getSqlHelper();

			$conn->query(
				"UPDATE ".$helper->quote('b_sale_basket')
				." SET RECOMMENDATION='".$helper->forSql($recommendationId)."' WHERE ID=".(int) $id
			);
		}
	}

	// order detailed info (OnOrderSave)
	public static function catchCatalogOrder($orderId, $arFields, $arOrder, $isNew)
	{
		if (!static::isOn())
		{
			return;
		}

		if (!$isNew)
		{
			// only new orders
			return;
		}

		$data = static::getOrderInfo($orderId);

		$data['paid'] = '0';
		$data['bx_user_id'] = static::getBxUserId();
		$data['domain'] = Context::getCurrent()->getServer()->getHttpHost();
		$data['date'] = date(DATE_ISO8601);

		CounterDataTable::add(array(
			'TYPE' => 'order',
			'DATA' => $data
		));
	}

	// order payment (OnSalePayOrder)
	public static function catchCatalogOrderPayment($orderId, $value)
	{
		if (!static::isOn())
		{
			return;
		}

		if ($value == 'Y')
		{
			$data = static::getOrderInfo($orderId);

			$data['paid'] = '1';
			$data['bx_user_id'] = static::getBxUserId();
			$data['domain'] = Context::getCurrent()->getServer()->getHttpHost();
			$data['date'] = date(DATE_ISO8601);

			CounterDataTable::add(array(
				'TYPE' => 'order_pay',
				'DATA' => $data
			));
		}
	}

	protected static function getOrderInfo($orderId)
	{
		// order itself
		$order = \CSaleOrder::getById($orderId);

		// buyer info
		$siteUserId = $order['USER_ID'];

		$phone = '';
		$email = '';

		$result = \CSaleOrderPropsValue::GetList(array(), array("ORDER_ID" => $orderId));
		while ($row = $result->fetch())
		{
			if (empty($phone) && stripos($row['CODE'], 'PHONE') !== false)
			{
				$stPhone = static::normalizePhoneNumber($row['VALUE']);

				if (!empty($stPhone))
				{
					$phone = sha1($stPhone);
				}
			}

			if (empty($email) && stripos($row['CODE'], 'EMAIL') !== false)
			{
				if (!empty($row['VALUE']))
				{
					$email = sha1($row['VALUE']);
				}
			}
		}

		// products info
		$products = array();

		$result = \CSaleBasket::getList(
			array(), $arFilter = array('ORDER_ID' => $orderId), false, false, array('PRODUCT_ID', 'RECOMMENDATION')
		);

		while ($row = $result->fetch())
		{
			$productInfo = \CCatalogSKU::GetProductInfo($row['PRODUCT_ID']);

			if (!empty($productInfo['ID']))
			{
				$realProductId = $productInfo['ID'];
			}
			else
			{
				$realProductId = $row['PRODUCT_ID'];
			}

			$products[] = array('product_id' => $realProductId, 'recommendation' => $row['RECOMMENDATION']);
		}

		// all together
		$data = array(
			'order_id' => $orderId,
			'user_id' => $siteUserId,
			'phone' => $phone,
			'email' => $email,
			'products' => $products
		);

		return $data;
	}

	protected function getBxUserId()
	{
		if (defined('ADMIN_SECTION') && ADMIN_SECTION)
		{
			return '';
		}
		else
		{
			return $_COOKIE['BX_USER_ID'];
		}
	}

	public static function normalizePhoneNumber($phone)
	{
		$phone = preg_replace('/[^\d]/', '', $phone);

		$cleanPhone = \NormalizePhone($phone, 6);

		if (strlen($cleanPhone) == 10)
		{
			$cleanPhone = '7'.$cleanPhone;
		}

		return $cleanPhone;
	}

	public static function isOn()
	{
		return SiteSpeed::isLicenseAccepted()
			&& Option::get("main", "gather_catalog_stat", "Y") === "Y"
			&& defined("LICENSE_KEY") && LICENSE_KEY !== "DEMO"
		;
	}

	public static function getProductIdsByOfferIds($offerIds)
	{
		if (empty($offerIds))
			return array();

		$bestList = array();
		$iblockGroup = array();
		$itemIterator = \Bitrix\Iblock\ElementTable::getList(array(
			'select' => array('ID', 'IBLOCK_ID'),
			'filter' => array('ID' => $offerIds, 'ACTIVE'=> 'Y')
		));
		while ($item = $itemIterator->fetch())
		{
			if (!isset($iblockGroup[$item['IBLOCK_ID']]))
				$iblockGroup[$item['IBLOCK_ID']] = array();
			$iblockGroup[$item['IBLOCK_ID']][] = $item['ID'];
			$bestList[$item['ID']] = array();
		}

		if (empty($iblockGroup))
			return array();

		$offerLink = array();
		foreach ($iblockGroup as $iblockId => $items)
		{
			$skuInfo = \CCatalogSKU::GetInfoByOfferIBlock($iblockId);
			if (empty($skuInfo))
				continue;
			$offerItetator = \CIBlockElement::GetList(
				array(),
				array('IBLOCK_ID' => $iblockId, 'ID' => $items),
				false,
				false,
				array('ID', 'IBLOCK_ID', 'PROPERTY_'.$skuInfo['SKU_PROPERTY_ID'])
			);
			while ($offer = $offerItetator->Fetch())
			{
				$productId = (int)$offer['PROPERTY_'.$skuInfo['SKU_PROPERTY_ID'].'_VALUE'];
				if ($productId <= 0)
				{
					unset($bestList[$offer['ID']]);
				}
				else
				{
					$bestList[$offer['ID']]['PARENT_ID'] = $productId;
					$bestList[$offer['ID']]['PARENT_IBLOCK'] = $skuInfo['PRODUCT_IBLOCK_ID'];
					if (!isset($offerLink[$productId]))
						$offerLink[$productId] = array();
					$offerLink[$productId][] = $offer['ID'];
				}
			}
		}
		if (!empty($offerLink))
		{
			$productIterator = \Bitrix\Iblock\ElementTable::getList(array(
				'select' => array('ID'),
				'filter' => array('@ID' => array_keys($offerLink), 'ACTIVE' => 'N')
			));
			while ($product = $productIterator->fetch())
			{
				if (empty($offerLink[$product['ID']]))
					continue;
				foreach ($offerLink[$product['ID']] as $value)
				{
					unset($bestList[$value]);
				}
			}
		}

		if (empty($bestList))
			return array();

		$finalIds = array();

		foreach ($bestList as $id => $info)
		{
			if (empty($info))
			{
				$finalIds[] = $id;
			}
			else
			{
				$finalIds[] = $info['PARENT_ID'];
			}
		}

		return $finalIds;
	}

	/**
	 * @param array $log
	 *
	 * @return string
	 */
	public static function encodeProductLog(array $log)
	{
		$value = array();

		foreach ($log as $itemId => $recommendation)
		{
			$rcmId = $recommendation[0];
			$rcmTime = $recommendation[1];

			$value[] = $itemId.'-'.$rcmId.'-'.$rcmTime;
		}

		return join('.', $value);
	}

	/**
	 * @param $log
	 *
	 * @return array
	 */
	public static function decodeProductLog($log)
	{
		$value = array();
		$tmp = explode('.', $log);

		foreach ($tmp as $tmpval)
		{
			$meta = explode('-', $tmpval);

			if (count($meta) > 2)
			{
				$itemId = $meta[0];
				$rcmId = $meta[1];
				$rcmTime = $meta[2];

				if ($itemId && $rcmId && $rcmTime)
				{
					$value[(int)$itemId] = array($rcmId, (int) $rcmTime);
				}
			}
		}

		return $value;
	}

	/**
	 * @return string
	 */
	public static function getCookieLogName()
	{
		return self::$cookieLogName;
	}
}
