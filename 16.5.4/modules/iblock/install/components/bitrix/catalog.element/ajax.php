<?
/** @global CMain $APPLICATION */
define('NO_AGENT_CHECK', true);

use Bitrix\Main;
use Bitrix\Catalog;

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
if (isset($_POST['AJAX']) && $_POST['AJAX'] == 'Y')
{
	if (Main\Loader::includeModule('statictic') && isset($_SESSION['SESS_SEARCHER_ID']) && (int)$_SESSION['SESS_SEARCHER_ID'] > 0)
	{
		echo CUtil::PhpToJSObject(array("STATUS" => "ERROR", "TEXT" => "SEARCHER"));
		die();
	}
	if (isset($_POST['PRODUCT_ID']) && isset($_POST['SITE_ID']))
	{
		$productID = (int)$_POST['PRODUCT_ID'];
		$parentID = (isset($_POST['PARENT_ID']) ? (int)$_POST['PARENT_ID'] : 0);
		$siteID = '';
		if (preg_match('/^[a-z0-9_]{2}$/i', (string)$_POST['SITE_ID']) === 1)
			$siteID = (string)$_POST['SITE_ID'];
		if ($productID > 0 && $siteID !== '' && Main\Loader::includeModule('catalog') && Main\Loader::includeModule('sale'))
		{
			// check if there was a recommendation
			$recommendationId = '';
			$recommendationCookie = $APPLICATION->get_cookie(Bitrix\Main\Analytics\Catalog::getCookieLogName());

			if (!empty($recommendationCookie))
			{
				$recommendations = \Bitrix\Main\Analytics\Catalog::decodeProductLog($recommendationCookie);

				if (is_array($recommendations) && isset($recommendations[$parentID]))
					$recommendationId = $recommendations[$parentID][0];
			}

			// add record
			Catalog\CatalogViewedProductTable::refresh(
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