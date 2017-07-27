<?php

namespace Bitrix\Catalog;

class SearchHandlers
{
	/**
	 * @param array $fields		Item fields.
	 * @return array
	 */
	public function onBeforeIndex($fields)
	{
		if($fields["MODULE_ID"] == "iblock")
		{
			$catalogs = \CCatalog::getList(
				array(),
				array('IBLOCK_ID' => $fields['PARAM2']),
				false,
				false,
				array('IBLOCK_ID')
			);
			if ($catalogs->fetch())
			{
				$fields["PARAMS"]["iblock_section"] = array();
				if (strpos($fields['ITEM_ID'], 'S') === false)
				{
					$sections = \CIBlockElement::getElementGroups($fields["ITEM_ID"], true, array('ID'));
					while ($section = $sections->fetch())
					{
						$nav = \CIBlockSection::getNavChain($fields['PARAM2'], $section["ID"], array('ID'));
						while ($chain = $nav->fetch())
						{
							$fields["PARAMS"]["iblock_section"][] = $chain['ID'];
						}
					}
				}
				else
				{
					$nav = \CIBlockSection::getNavChain($fields['PARAM2'], preg_replace('#[^0-9]+#', '', $fields["ITEM_ID"]), array('ID'));
					while ($chain = $nav->fetch())
					{
						$fields["PARAMS"]["iblock_section"][] = $chain['ID'];
					}
				}
			}
		}
		return $fields;
	}
}