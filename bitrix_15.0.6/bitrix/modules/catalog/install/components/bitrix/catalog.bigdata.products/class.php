<?php
use \Bitrix\Main;
use \Bitrix\Catalog\CatalogViewedProductTable as CatalogViewedProductTable;
use \Bitrix\Main\Text\String as String;
use \Bitrix\Main\Localization\Loc as Loc;
use \Bitrix\Main\SystemException as SystemException;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

CBitrixComponent::includeComponentClass("bitrix:sale.bestsellers");

class CatalogBigdataProductsComponent extends CSaleBestsellersComponent
{
	protected $rcmParams;
	protected $ajaxItemsIds;

	/**
	 * Prepare Component Params
	 */
	public function onPrepareComponentParams($params)
	{
		global $APPLICATION;

		// remember src params for further ajax query
		if (!isset($params['RCM_CUR_BASE_PAGE']))
		{
			$params['RCM_CUR_BASE_PAGE'] = $APPLICATION->GetCurPage();
		}

		$this->arResult['_ORIGINAL_PARAMS'] = $params;

		// bestselling
		$params['FILTER'] = array('PAYED');
		$params['PERIOD'] = 30;

		return parent::onPrepareComponentParams($params);
	}

	/**
	 * set prices for all items
	 * @return array currency list
	 */
	protected function setItemsPrices()
	{
		parent::setItemsPrices();

		// rewrite urls
		foreach ($this->items as &$item)
		{	// ajax mode only - get from signed parameters
			$item["~BUY_URL"] = $this->getPageParam(
				$this->arParams['RCM_CUR_BASE_PAGE'], $this->arParams["ACTION_VARIABLE"] . "=BUY&" . $this->arParams["PRODUCT_ID_VARIABLE"] . "=" . $item["ID"], array($this->arParams["PRODUCT_ID_VARIABLE"], $this->arParams["ACTION_VARIABLE"])
			);
			$item["~ADD_URL"] = $this->getPageParam(
				$this->arParams['RCM_CUR_BASE_PAGE'], $this->arParams["ACTION_VARIABLE"] . "=ADD2BASKET&" . $this->arParams["PRODUCT_ID_VARIABLE"] . "=" . $item["ID"], array($this->arParams["PRODUCT_ID_VARIABLE"], $this->arParams["ACTION_VARIABLE"])
			);
			$item["~COMPARE_URL"] = $this->getPageParam(
				$this->arParams['RCM_CUR_BASE_PAGE'], "action=ADD_TO_COMPARE_LIST&id=" . $item["ID"], array("action", "id")
			);
			$item["~SUBSCRIBE_URL"] = $this->getPageParam(
				$this->arParams['RCM_CUR_BASE_PAGE'], $this->arParams["ACTION_VARIABLE"] . "=SUBSCRIBE_PRODUCT&" . $this->arParams["PRODUCT_ID_VARIABLE"] . "=" . $item["ID"], array($this->arParams["PRODUCT_ID_VARIABLE"], $this->arParams["ACTION_VARIABLE"])
			);

			$item["BUY_URL"] = htmlspecialcharsbx($item["~BUY_URL"]);
			$item["ADD_URL"] = htmlspecialcharsbx($item["~ADD_URL"]);
			$item["COMPARE_URL"] = htmlspecialcharsbx($item["~COMPARE_URL"]);
			$item["SUBSCRIBE_URL"] = htmlspecialcharsbx($item["~SUBSCRIBE_URL"]);
		}
	}

	/**
	 * Add offers for each catalog product.
	 * @return void
	 */
	protected function setItemsOffers()
	{
		parent::setItemsOffers();

		foreach ($this->items as &$item)
		{
			if (!empty($item['OFFERS']) && is_array($item['OFFERS']))
			{
				foreach ($item['OFFERS'] as &$offer)
				{
					$offer["~BUY_URL"] = $this->getPageParam(
						$this->arParams['RCM_CUR_BASE_PAGE'], $this->arParams["ACTION_VARIABLE"] . "=BUY&" . $this->arParams["PRODUCT_ID_VARIABLE"] . "=" . $offer["ID"], array($this->arParams["PRODUCT_ID_VARIABLE"], $this->arParams["ACTION_VARIABLE"])
					);

					$offer["~ADD_URL"] = $this->getPageParam(
						$this->arParams['RCM_CUR_BASE_PAGE'], $this->arParams["ACTION_VARIABLE"] . "=ADD2BASKET&" . $this->arParams["PRODUCT_ID_VARIABLE"] . "=" . $offer["ID"], array($this->arParams["PRODUCT_ID_VARIABLE"], $this->arParams["ACTION_VARIABLE"])
					);

					$offer["~COMPARE_URL"] = $this->getPageParam(
						$this->arParams['RCM_CUR_BASE_PAGE'], "action=ADD_TO_COMPARE_LIST&id=" . $offer["ID"], array($this->arParams["PRODUCT_ID_VARIABLE"], $this->arParams["ACTION_VARIABLE"])
					);

					$offer["~SUBSCRIBE_URL"] = $this->getPageParam(
						$this->arParams['RCM_CUR_BASE_PAGE'], $this->arParams["ACTION_VARIABLE"] . "=SUBSCRIBE_PRODUCT&id=" . $offer["ID"], array($this->arParams["PRODUCT_ID_VARIABLE"], $this->arParams["ACTION_VARIABLE"])
					);

					$offer["BUY_URL"] = htmlspecialcharsbx($offer["~BUY_URL"]);
					$offer["ADD_URL"] = htmlspecialcharsbx($offer["~ADD_URL"]);
					$offer["COMPARE_URL"] = htmlspecialcharsbx($offer["~COMPARE_URL"]);
					$offer["SUBSCRIBE_URL"] = htmlspecialcharsbx($offer["~SUBSCRIBE_URL"]);
				}
			}
		}
	}

	protected function getPageParam($sUrlPath, $strParam="", $arParamKill=array(), $get_index_page=null)
	{
		$strNavQueryString = DeleteParam($arParamKill);
		if($strNavQueryString <> "" && $strParam <> "")
			$strNavQueryString = "&".$strNavQueryString;
		if($strNavQueryString == "" && $strParam == "")
			return $sUrlPath;
		else
			return $sUrlPath."?".$strParam.$strNavQueryString;
	}

	protected function getProductIds()
	{
		$ids = array();

		if (!empty($this->ajaxItemsIds))
		{
			$recommendationId = Main\Context::getCurrent()->getRequest()->get('RID');
			$ids = $this->ajaxItemsIds;
		}
		else
		{
			$bestsellers = parent::getProductIds();

			if (!empty($bestsellers))
			{
				$recommendationId = 'bestsellers';
				$ids = Main\Analytics\Catalog::getProductIdsByOfferIds($bestsellers);
			}

			if (empty($ids))
			{
				$recommendationId = 'mostviewed';
				$dublicate = array();
				// top viewed
				$result = CatalogViewedProductTable::getList(array(
					'select' => array(
						'ELEMENT_ID',
						new Main\Entity\ExpressionField('SUM_HITS', 'SUM(%s)', 'VIEW_COUNT')
					),
					'filter' => array('=SITE_ID' => SITE_ID, '>ELEMENT_ID' => 0),
					'order' => array('SUM_HITS' => 'DESC'),
					'limit' => $this->arParams['PAGE_ELEMENT_COUNT']
				));

				while ($row = $result->fetch())
				{
					if (!isset($dublicate[$row['ELEMENT_ID']]))
						$ids[] = $row['ELEMENT_ID'];
					$dublicate[$row['ELEMENT_ID']] = true;
				}
				unset($row, $result, $dublicate);
			}
		}

		if (!empty($ids) && $this->arParams['HIDE_NOT_AVAILABLE'] == 'Y')
		{
			$filter = (count($ids) > 1000 ? array('ID' => $ids) : array('@ID' => $ids));
			$ids = array_fill_keys($ids, true);
			$productIterator = CCatalogProduct::GetList(
				array(),
				$filter,
				false,
				false,
				array('ID', 'QUANTITY', 'QUANTITY_TRACE', 'CAN_BUY_ZERO')
			);
			while ($product = $productIterator->Fetch())
			{
				if (isset($ids[$product['ID']]) && !CCatalogProduct::isAvailable($product))
					unset($ids[$product['ID']]);
			}
			unset($product, $productIterator, $filter);
			$ids = array_keys($ids);
		}

		$ids = array_slice($ids, 0, $this->arParams['PAGE_ELEMENT_COUNT']);

		// remember recommendation id
		$this->arResult['RID'] = $recommendationId;

		return $ids;
	}

	/**
	 * Extract data from cache. No action by default.
	 * @return bool
	 */
	protected function extractDataFromCache()
	{
		if($this->arParams['CACHE_TYPE'] == 'N')
			return false;

		$rcmParams = $this->rcmParams;

		// cut productid from non-product recommendations
		if ($rcmParams['op'] == 'sim_domain_items' || $rcmParams['op'] == 'recommend')
		{
			unset($this->arParams['ID'], $this->arParams['~ID']);
		}

		// cut userid from non-personal recommendations
		if ($rcmParams['op'] == 'sim_domain_items' || $rcmParams['op'] == 'simitems')
		{
			unset($rcmParams['uid']);
		}

		return !($this->StartResultCache(false, $rcmParams, '/'.SITE_ID.'/bitrix/catalog.bigdata.products/common'));
	}

	protected function getServiceRequestParamsByType($type)
	{
		$a = array(
			'uid' => $_COOKIE['BX_USER_ID'],
			'aid' => \Bitrix\Main\Analytics\Counter::getAccountId(),
			'count' => $this->arParams['PAGE_ELEMENT_COUNT']+10
		);

		// random choices
		if ($type == 'any_similar')
		{
			$possible = array('similar_sell', 'similar_view', 'similar');
			$type = $possible[array_rand($possible)];
		}
		elseif ($type == 'any_personal')
		{
			$possible = array('bestsell', 'personal');
			$type = $possible[array_rand($possible)];
		}
		elseif ($type == 'any')
		{
			$possible = array('similar_sell', 'similar_view', 'similar', 'bestsell', 'personal');
			$type = $possible[array_rand($possible)];
		}

		// configure
		if ($type == 'bestsell')
		{
			$a['op'] = 'sim_domain_items';
			$a['type'] = 'order';
			$a['domain'] = Bitrix\Main\Context::getCurrent()->getServer()->getHttpHost();
		}
		elseif ($type == 'personal')
		{
			$a['op'] = 'recommend';
		}
		elseif ($type == 'similar_sell')
		{
			$a['op'] = 'simitems';
			$a['eid'] = $this->arParams['ID'];
			$a['type'] = 'order';
		}
		elseif ($type == 'similar_view')
		{
			$a['op'] = 'simitems';
			$a['eid'] = $this->arParams['ID'];
			$a['type'] = 'view';
		}
		elseif ($type == 'similar')
		{
			$a['op'] = 'simitems';
			$a['eid'] = $this->arParams['ID'];
		}
		else
		{
			// unkonwn type
		}

		// get iblocks
		$iblocks = array();

		if (!empty($this->arParams['IBLOCK_ID']))
		{
			$iblocks = array($this->arParams['IBLOCK_ID']);
		}
		else
		{
			$iblockList = array();
			/* catalog */
			$iblockIterator = \Bitrix\Catalog\CatalogIblockTable::getList(array(
				'select' => array('IBLOCK_ID', 'PRODUCT_IBLOCK_ID')
			));
			while ($iblock = $iblockIterator->fetch())
			{
				$iblock['IBLOCK_ID'] = (int)$iblock['IBLOCK_ID'];
				$iblock['PRODUCT_IBLOCK_ID'] = (int)$iblock['PRODUCT_IBLOCK_ID'];
				$iblockList[$iblock['IBLOCK_ID']] = $iblock['IBLOCK_ID'];
				if ($iblock['PRODUCT_IBLOCK_ID'] > 0)
					$iblockList[$iblock['PRODUCT_IBLOCK_ID']] = $iblock['PRODUCT_IBLOCK_ID'];
			}

			/* iblock */
			$iblockIterator = \Bitrix\Iblock\IblockSiteTable::getList(array(
				'select' => array('IBLOCK_ID'),
				'filter' => array('@IBLOCK_ID' => $iblockList, '=SITE_ID' => SITE_ID)
			));
			while ($iblock = $iblockIterator->fetch())
			{
				$iblocks[] = $iblock['IBLOCK_ID'];
			}
		}

		$a['ib'] = join('.', $iblocks);

		return $a;
	}


	/**
	 * Start Component
	 */
	public function executeComponent()
	{
		global $APPLICATION;

		$context = Main\Context::getCurrent();

		// mark usage
		$lastUsage = Main\Config\Option::get('main', 'rcm_component_usage', 0);

		if ($lastUsage == 0 || (time() - $lastUsage) > 3600)
		{
			Main\Config\Option::set('main', 'rcm_component_usage', time());
		}

		// execute
		$this->checkModules();
		$this->processRequest();

		// define what to do and check cache
		$this->rcmParams = $this->getServiceRequestParamsByType($this->arParams['RCM_TYPE']);
		$showByIds = ($context->getServer()->getRequestMethod() == 'POST' && $context->getRequest()->getPost('rcm') == 'yes');

		if (!$showByIds)
		{
			// check if ids are already in cache
			try
			{
				if (!$this->extractDataFromCache())
				{
					// echo js for requesting items from recommendation service
					$this->arResult['REQUEST_ITEMS'] = true;
					$this->arResult['RCM_PARAMS'] = $this->rcmParams;
					$this->arResult['RCM_TEMPLATE'] = $this->getTemplateName();

					// abort cache, we will write it on next request with the same parameters
					$this->abortDataCache();

					$this->includeComponentTemplate();

					$this->setResultCacheKeys(array());
				}

				// show cache and die
				return;
			}
			catch (SystemException $e)
			{
				$this->abortDataCache();

				if ($this->isAjax())
				{
					$APPLICATION->restartBuffer();
					echo CUtil::PhpToJSObject(array('STATUS' => 'ERROR', 'MESSAGE' => $e->getMessage()));
					die();
				}

				ShowError($e->getMessage());
			}
		}

		if ($showByIds)
		{
			// we have an ajax query to get items html
			// and there was no cache
			$ajaxItemIds = $context->getRequest()->get('AJAX_ITEMS');

			if (!empty($ajaxItemIds) && is_array($ajaxItemIds))
			{
				$this->ajaxItemsIds = $ajaxItemIds;
			}
			else
			{
				// show something
				$this->ajaxItemsIds = null;
				// last viewed will be shown
			}

			// draw products with collected ids
			$this->prepareData();
			$this->formatResult();
		}

		if (!$this->extractDataFromCache())
		{
			$this->setResultCacheKeys(array());
			$this->includeComponentTemplate();
		}
	}
}

