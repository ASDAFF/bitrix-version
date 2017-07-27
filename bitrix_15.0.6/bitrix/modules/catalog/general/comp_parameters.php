<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Catalog;
Loc::loadMessages(__FILE__);

class CCatalogIBlockParameters
{
	public static function GetCatalogSortFields()
	{
		return array(
			'CATALOG_AVAILABLE' => Loc::getMessage('IBLOCK_SORT_FIELDS_CATALOG_AVAILABLE')
		);
	}

	public static function getPriceTypesList($useId = false)
	{
		$useId = ($useId === true);
		$result = array();
		$priceTypeIterator = Catalog\GroupTable::getList(array(
			'select' => array('ID', 'NAME', 'NAME_LANG' => 'CURRENT_LANG.NAME'),
			'order' => array('SORT' => 'ASC', 'ID' => 'ASC')
		));
		while ($priceType = $priceTypeIterator->fetch())
		{
			$priceType['NAME_LANG'] = (string)$priceType['NAME_LANG'];
			$priceCode = ($useId ? $priceType['ID'] : $priceType['NAME']);
			$priceTitle = '['.$priceType['ID'].'] ['.$priceType['NAME'].']'.($priceType['NAME_LANG'] != '' ? ' '.$priceType['NAME_LANG'] : '');
			$result[$priceCode] = $priceTitle;
		}
		unset($priceTitle, $priceCode, $priceType, $priceTypeIterator);
		return $result;
	}
}