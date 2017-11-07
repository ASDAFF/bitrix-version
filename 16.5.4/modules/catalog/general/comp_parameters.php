<?
use Bitrix\Main\Localization\Loc;

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
		foreach (CCatalogGroup::GetListArray() as $priceType)
		{
			$priceType['NAME_LANG'] = (string)$priceType['NAME_LANG'];
			$priceCode = ($useId ? $priceType['ID'] : $priceType['NAME']);
			$priceTitle = '['.$priceType['ID'].'] ['.$priceType['NAME'].']'.($priceType['NAME_LANG'] != '' ? ' '.$priceType['NAME_LANG'] : '');
			$result[$priceCode] = $priceTitle;
			unset($priceCode, $priceTitle);
		}
		unset($priceType);

		return $result;
	}
}