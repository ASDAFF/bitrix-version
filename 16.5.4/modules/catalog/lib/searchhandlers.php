<?php

namespace Bitrix\Catalog;

class SearchHandlers
{
	protected static $catalogList = array();
	/**
	 * Fill parameters before create search index.
	 *
	 * @param array $fields		Item fields.
	 * @return array
	 */
	public static function onBeforeIndex($fields)
	{
		if (!isset($fields['MODULE_ID']) || $fields['MODULE_ID'] != 'iblock')
			return $fields;
		if (empty($fields['PARAM2']))
			return $fields;

		if (!isset(self::$catalogList[$fields['PARAM2']]))
		{
			self::$catalogList[$fields['PARAM2']] = false;
			$catalog = CatalogIblockTable::getList(array(
				'select' => array('IBLOCK_ID'),
				'filter' => array('=IBLOCK_ID' => $fields['PARAM2'])
			))->fetch();
			if (!empty($catalog))
				self::$catalogList[$fields['PARAM2']] = $catalog['IBLOCK_ID'];
			unset($catalog);
		}

		if (!empty(self::$catalogList[$fields['PARAM2']]))
		{
			$fields["PARAMS"]["iblock_section"] = array();
			if (strpos($fields['ITEM_ID'], 'S') === false)
			{
				/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
				$sections = \CIBlockElement::getElementGroups($fields["ITEM_ID"], true, array('ID'));
				/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
				while ($section = $sections->fetch())
				{
					/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
					$nav = \CIBlockSection::getNavChain($fields['PARAM2'], $section["ID"], array('ID'));
					while ($chain = $nav->fetch())
						$fields["PARAMS"]["iblock_section"][] = $chain['ID'];
					unset($chain, $nav);
				}
				unset($section, $sections);
			}
			else
			{
				/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
				$nav = \CIBlockSection::getNavChain($fields['PARAM2'], preg_replace('#[^0-9]+#', '', $fields["ITEM_ID"]), array('ID'));
				while ($chain = $nav->fetch())
					$fields["PARAMS"]["iblock_section"][] = $chain['ID'];
				unset($chain, $nav);
			}
		}
		return $fields;
	}
}