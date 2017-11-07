<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
	if (!function_exists("GetUniqueID"))
	{
		function GetUniqueID()
		{
			static $arID = array();

			$iUniq = rand();
			if (in_array($iUniq, $arID))
				$iUniq = GetUniqueID();
			else
				$arID[] = $iUniq;

			return $iUniq;
		}
	}

	$arResult["data"] = $arParams["~DATA"];
	$arResult["head"] = $arParams["~HEAD"];
	$arResult["id"] = "id_".GetUniqueID();
	$arParams["RETURN_DATA"] = "";
	$result = $this->IncludeComponentTemplate();
	if (!empty($arParams["RETURN_DATA"]))
		return $arParams["RETURN_DATA"];
?>