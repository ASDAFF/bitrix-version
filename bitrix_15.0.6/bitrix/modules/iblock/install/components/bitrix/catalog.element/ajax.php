<?
define('NO_AGENT_CHECK', true);
define("STOP_STATISTICS", true);

use \Bitrix\Catalog\CatalogViewedProductTable as CatalogViewedProductTable;

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
if (isset($_POST['AJAX']) && $_POST['AJAX'] == 'Y')
{
	if (isset($_POST['PRODUCT_ID']) && isset($_POST['SITE_ID']))
	{
		$productID = (int)$_POST['PRODUCT_ID'];
		$parentID = (int)$_POST['PARENT_ID'];
		$siteID = substr((string)$_POST['SITE_ID'], 0, 2);
		if ($productID > 0 && $siteID !== '' && \Bitrix\Main\Loader::includeModule('catalog') && \Bitrix\Main\Loader::includeModule('sale'))
		{
			// check if there was a recommendation
			$recommendationId = '';
			$recommendationCookie = $APPLICATION->get_cookie(Bitrix\Main\Analytics\Catalog::getCookieLogName());

			if (!empty($recommendationCookie))
			{
				$recommendations = \Bitrix\Main\Analytics\Catalog::decodeProductLog($recommendationCookie);

				if (is_array($recommendations) && isset($recommendations[$parentID]))
				{
					$recommendationId = $recommendations[$parentID][0];
				}
			}

			// add record
			CatalogViewedProductTable::refresh(
				$productID,
				CSaleBasket::GetBasketUserID(),
				$siteID,
				$parentID,
				$recommendationId
			);
			echo CUtil::PhpToJSObject(array("STATUS" => "SUCCESS"));
		}
		else
		{
			echo CUtil::PhpToJSObject(array("STATUS" => "ERROR", "TEXT" => "UNDEFINED PRODUCT"));
		}
	}
	die();
}