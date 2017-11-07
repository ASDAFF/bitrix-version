<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!function_exists("_GetChildMenuRecursive"))
{
	function _GetChildMenuRecursive(&$arMenu, &$arResult, $menuType, $use_ext, $menuTemplate, $currentLevel, $maxLevel, $bMultiSelect, $bCheckSelected)
	{
		if ($currentLevel > $maxLevel)
			return;

		for ($menuIndex = 0, $menuCount = count($arMenu); $menuIndex < $menuCount; $menuIndex++)
		{
			//Menu from iblock (bitrix:menu.sections)
			if (is_array($arMenu[$menuIndex]["PARAMS"]) && isset($arMenu[$menuIndex]["PARAMS"]["FROM_IBLOCK"]))
			{
				$iblockSectionLevel = intval($arMenu[$menuIndex]["PARAMS"]["DEPTH_LEVEL"]);
				if ($currentLevel > 1)
					$iblockSectionLevel = $iblockSectionLevel + $currentLevel - 1;

				$arResult[] = $arMenu[$menuIndex] + Array("DEPTH_LEVEL" => $iblockSectionLevel, "IS_PARENT" => $arMenu[$menuIndex]["PARAMS"]["IS_PARENT"]);
				continue;
			}

			//Menu from files
			$subMenuExists = false;
			if ($currentLevel < $maxLevel)
			{
				//directory link only
				$bDir = false;
				if(!preg_match("'^(([a-z]+://)|mailto:|javascript:)'i", $arMenu[$menuIndex]["LINK"]))
				{
					if(substr($arMenu[$menuIndex]["LINK"], -1) == "/")
						$bDir = true;
				}
				if($bDir)
				{
					$menu = new CMenu($menuType);
					$success = $menu->Init($arMenu[$menuIndex]["LINK"], $use_ext, $menuTemplate, $onlyCurrentDir = true);
					$subMenuExists = ($success && count($menu->arMenu) > 0);

					if ($subMenuExists)
					{
						$menu->RecalcMenu($bMultiSelect, $bCheckSelected);

						$arResult[] = $arMenu[$menuIndex] + Array("DEPTH_LEVEL" => $currentLevel, "IS_PARENT" => (count($menu->arMenu) > 0));

						if($arMenu[$menuIndex]["SELECTED"])
						{
							$arResult["menuType"] = $menuType;
							$arResult["menuDir"] = $arMenu[$menuIndex]["LINK"];
						}

						if(count($menu->arMenu) > 0)
							_GetChildMenuRecursive($menu->arMenu, $arResult, $menuType, $use_ext, $menuTemplate, $currentLevel+1, $maxLevel, $bMultiSelect, $bCheckSelected);
					}
				}
			}

			if(!$subMenuExists)
				$arResult[] = $arMenu[$menuIndex] + Array("DEPTH_LEVEL" => $currentLevel, "IS_PARENT" => false);
		}
	}
}

if (!function_exists('__GetMenuString'))
{
	/**
	 * @param string $type
	 * @param CBitrixComponent $obMenuComponent
	 * @return string
	 */
	function __GetMenuString($type = "left", $obMenuComponent)
	{
		/** @var CMenuCustom*/
		global $BX_MENU_CUSTOM;

		$sReturn = "";

		if ($GLOBALS["APPLICATION"]->buffer_manual)
		{
			$arMenuCustom = $BX_MENU_CUSTOM->GetItems($type);
			if (is_array($arMenuCustom))
				$obMenuComponent->arResult = array_merge($obMenuComponent->arResult, $arMenuCustom);

			ob_start();
			$obMenuComponent->IncludeComponentTemplate();
			$sReturn = ob_get_contents();
			ob_end_clean();
		}
		return $sReturn;
	}
}

if (!function_exists('_SetSelectedItems'))
{
	function _SetSelectedItems(&$arResult, $bMultiSelect = false)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$cur_page = $APPLICATION->GetCurPage(true);
		$cur_page_no_index = $APPLICATION->GetCurPage(false);
		$cur_selected = -1;
		$cur_selected_len = -1;

		foreach($arResult as $iMenuItem => $MenuItem)
		{
			$LINK = $MenuItem['LINK'];
			$ADDITIONAL_LINKS = $MenuItem['ADDITIONAL_LINKS'];
			$SELECTED = false;

			$all_links = array();
			if(is_array($ADDITIONAL_LINKS))
			{
				foreach($ADDITIONAL_LINKS as $link)
				{
					$tested_link = trim($link);
					if(strlen($tested_link)>0)
						$all_links[] = $tested_link;
				}
			}
			$all_links[] = $LINK;

			if($MenuItem['PERMISSION'] != 'Z')
			{
				foreach($all_links as $tested_link)
				{
					if($tested_link == '')
						continue;

					$SELECTED = CMenu::IsItemSelected($tested_link, $cur_page, $cur_page_no_index);
					if($SELECTED)
					{
						$arResult[$iMenuItem]['SELECTED'] = true;
						break;
					}
				}
			}

			if($SELECTED && !$bMultiSelect)
			{
				/** @noinspection PhpUndefinedVariableInspection */
				$new_len = strlen($tested_link);
				if($new_len > $cur_selected_len)
				{
					if($cur_selected !== -1)
						$arResult[$cur_selected]['SELECTED'] = false;

					$cur_selected = $iMenuItem;
					$cur_selected_len = $new_len;
				}
				elseif($new_len > 1)
				{
					$arResult[$iMenuItem]['SELECTED'] = false;
				}
			}
		}
	}
}
