<?
class CIBlockFindTools
{
	function GetElementID($element_id, $element_code, $section_id, $section_code, $arFilter)
	{
		$element_id = intval($element_id);
		if($element_id > 0)
		{
			return $element_id;
		}
		elseif(strlen($element_code) > 0)
		{
			$arFilter["=CODE"] = $element_code;

			$section_id = intval($section_id);
			if($section_id > 0)
				$arFilter["SECTION_ID"] = $section_id;
			elseif(strlen($section_code) > 0)
				$arFilter["SECTION_CODE"] = $section_code;

			$rsElement = CIBlockElement::GetList(array(), $arFilter, false, false, array("ID"));
			if($arElement = $rsElement->Fetch())
				return intval($arElement["ID"]);
		}
		return 0;
	}

	function GetSectionID($section_id, $section_code, $arFilter)
	{
		$section_id = intval($section_id);
		if($section_id > 0)
		{
			return $section_id;
		}
		elseif(strlen($section_code) > 0)
		{
			$arFilter["=CODE"] = $section_code;

			$rsSection = CIBlockSection::GetList(array(), $arFilter);
			if($arSection = $rsSection->Fetch())
				return intval($arSection["ID"]);
		}
		return 0;
	}

	function resolveComponentEngine(CComponentEngine $engine, $pageCandidates, &$arVariables)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION, $CACHE_MANAGER;
		$component = $engine->GetComponent();
		if ($component)
			$iblock_id = intval($component->arParams["IBLOCK_ID"]);
		else
			$iblock_id = 0;

		$requestURL = $APPLICATION->GetCurPage(true);

		$cache = new CPHPCache;
		if ($cache->startDataCache(3600, $requestURL, "iblock_find"))
		{
			if (defined("BX_COMP_MANAGED_CACHE"))
			{
				$CACHE_MANAGER->StartTagCache("iblock_find");
				$CACHE_MANAGER->RegisterTag("iblock_id_".$iblock_id);
			}

			foreach ($pageCandidates as $pageID => $arVariablesTmp)
			{
				if (
					$arVariablesTmp["SECTION_CODE_PATH"] != ""
					&& (isset($arVariablesTmp["ELEMENT_ID"]) || isset($arVariablesTmp["ELEMENT_CODE"]))
				)
				{
					if (CIBlockFindTools::checkElement($iblock_id, $arVariablesTmp))
					{
						$arVariables = $arVariablesTmp;
						if (defined("BX_COMP_MANAGED_CACHE"))
							$CACHE_MANAGER->EndTagCache();
						$cache->endDataCache(array($pageID, $arVariablesTmp));
						return $pageID;
					}
				}
			}

			foreach ($pageCandidates as $pageID => $arVariablesTmp)
			{
				if (
					$arVariablesTmp["SECTION_CODE_PATH"] != ""
					&& (!isset($arVariablesTmp["ELEMENT_ID"]) && !isset($arVariablesTmp["ELEMENT_CODE"]))
				)
				{
					if (CIBlockFindTools::checkSection($iblock_id, $arVariablesTmp))
					{
						$arVariables = $arVariablesTmp;
						if (defined("BX_COMP_MANAGED_CACHE"))
							$CACHE_MANAGER->EndTagCache();
						$cache->endDataCache(array($pageID, $arVariablesTmp));
						return $pageID;
					}
				}
			}

			if (defined("BX_COMP_MANAGED_CACHE"))
				$CACHE_MANAGER->AbortTagCache();
			$cache->abortDataCache();
		}
		else
		{
			$vars = $cache->getVars();
			$pageID = $vars[0];
			$arVariables = $vars[1];
			return $pageID;
		}

		list($pageID, $arVariables) = each($pageCandidates);
		return $pageID;
	}

	function checkElement($iblock_id, &$arVariables)
	{
		global $DB;

		$strFrom = "
			b_iblock_element BE
		";

		$strWhere = "
			".($arVariables["ELEMENT_ID"] != ""? "AND BE.ID = ".intval($arVariables["ELEMENT_ID"]): "")."
			".($arVariables["ELEMENT_CODE"] != ""? "AND BE.CODE = '".$DB->ForSql($arVariables["ELEMENT_CODE"])."'": "")."
		";

		if ($arVariables["SECTION_CODE_PATH"] != "")
		{
			$strFrom .= "
				INNER JOIN b_iblock_section_element BSE ON BSE.IBLOCK_ELEMENT_ID = BE.ID AND BSE.ADDITIONAL_PROPERTY_ID IS NULL
			";
			$joinField = "BSE.IBLOCK_SECTION_ID";

			$sectionPath = explode("/", $arVariables["SECTION_CODE_PATH"]);
			foreach (array_reverse($sectionPath) as $i => $SECTION_CODE)
			{
				$strFrom .= "
					INNER JOIN b_iblock_section BS".$i." ON BS".$i.".ID = ".$joinField."
				";
				$joinField = "BS".$i.".IBLOCK_SECTION_ID";
				$strWhere .= "
					AND BS".$i.".CODE = '".$DB->ForSql($SECTION_CODE)."'
				";
			}
		}

		$strSql = "
			select BE.ID
			from ".$strFrom."
			WHERE BE.IBLOCK_ID = ".$iblock_id."
			".$strWhere."
		";
		$rs = $DB->Query($strSql);
		if ($rs->Fetch())
		{
			if (isset($sectionPath))
				$arVariables["SECTION_CODE"] = $sectionPath[count($sectionPath)-1];
			return true;
		}
		else
		{
			return false;
		}
	}

	function checkSection($iblock_id, &$arVariables)
	{
		global $DB;

		$sectionPath = explode("/", $arVariables["SECTION_CODE_PATH"]);

		if (count($sectionPath) == 1)
		{
			$arVariables["SECTION_CODE"] = $arVariables["SECTION_CODE_PATH"];
			return true;
		}

		$strFrom = "";
		$joinField = "";
		$strWhere = "";
		foreach (array_reverse($sectionPath) as $i => $SECTION_CODE)
		{
			if ($i == 0)
			{
				$strFrom .= "
					b_iblock_section BS
				";
				$joinField .= "BS.IBLOCK_SECTION_ID";
				$strWhere .= "
					AND BS.CODE = '".$DB->ForSql($SECTION_CODE)."'
				";
			}
			else
			{
				$strFrom .= "
					INNER JOIN b_iblock_section BS".$i." ON BS".$i.".ID = ".$joinField."
				";
				$joinField = "BS".$i.".IBLOCK_SECTION_ID";
				$strWhere .= "
					AND BS".$i.".CODE = '".$DB->ForSql($SECTION_CODE)."'
				";
			}
		}

		$strSql = "
			select BS.ID
			from ".$strFrom."
			WHERE BS.IBLOCK_ID = ".$iblock_id."
			".$strWhere."
		";
		$rs = $DB->Query($strSql);
		if ($rs->Fetch())
		{
			$arVariables["SECTION_CODE"] = $sectionPath[count($sectionPath)-1];
			return true;
		}
		else
		{
			return false;
		}
	}
}
?>