<?php
namespace Bitrix\Catalog\Product;

use Bitrix\Main,
	Bitrix\Catalog;
/**
 * Class Price
 * Provides various useful methods for price sorting.
 *
 * @package Bitrix\Catalog\Product
 */
class Price
{
	/**
	 * Handler onAfterUpdateCurrencyBaseRate for update field PRICE_SCALE after change currency base rate.
	 *
	 * @param Main\Event $event			Event data (old and new currency rate).
	 * @return void
	 */
	public static function handlerAfterUpdateCurrencyBaseRate(Main\Event $event)
	{
		$params = $event->getParameters();
		if (empty($params))
			return;

		$oldBaseRate = (float)$params['OLD_BASE_RATE'];
		if ($oldBaseRate < 1E-4)
			return;
		$currentBaseRate = (float)$params['CURRENT_BASE_RATE'];
		if (abs($currentBaseRate - $oldBaseRate)/$oldBaseRate < 1E-4)
			return;
		$currency = $params['CURRENCY'];

		$conn = Main\Application::getConnection();
		$helper = $conn->getSqlHelper();

		$query = 'update '.$helper->quote(Catalog\PriceTable::getTableName()).
			' set '.$helper->quote('PRICE_SCALE').' = '.$helper->quote('PRICE').' * '.$currentBaseRate.
			' where '.$helper->quote('CURRENCY').' = \''.$helper->forSql($currency).'\'';
		$conn->queryExecute($query);

		if (defined('BX_COMP_MANAGED_CACHE'))
		{
			$taggedCache = Main\Application::getInstance()->getTaggedCache();
			$taggedCache->clearByTag('currency_id_'.$currency);
		}
	}
}