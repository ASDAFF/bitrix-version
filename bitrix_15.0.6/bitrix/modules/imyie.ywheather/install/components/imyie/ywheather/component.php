<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if(!function_exists('objectsIntoArray'))
{
	function objectsIntoArray($arrObjData, $go2win1251 = false)
	{
		$arrData = array();
		if (is_object($arrObjData)) {
			$arrObjData = get_object_vars($arrObjData);
		}
		if (is_array($arrObjData)) {
			foreach ($arrObjData as $index => $value) {
				if (is_object($value) || is_array($value)) {
					$value = objectsIntoArray($value, $go2win1251);
				}
				if($go2win1251=="Y" && !is_object($value) && !is_array($value))
				{
					$value2 = utf8win1251($value);
				} else {
					$value2 = $value;
				}
				$arrData[$index] = $value2;
			}
		}
		return $arrData;
	}
}

if(isset($arParams["LANG_CHARSET"]))
	$LANG_CHARSET = $arParams["LANG_CHARSET"];
else
	$LANG_CHARSET = "auto";
	
$CITY_ID = $arParams["CITY"];
if(!isset($arParams['CACHE_TIME']))
{
	$arParams['CACHE_TIME'] = 3600;
}
if(isset($CITY_ID) && $CITY_ID>0 && is_numeric($CITY_ID))
{
	if ($this->StartResultCache($arParams['CACHE_TIME']))
	{
		$strQueryText = QueryGetData(
			"export.yandex.ru",
			80,
			"/weather-ng/forecasts/".$CITY_ID.".xml",
			"123123123123123123123123",
			$error_number,
			$error_text
		);
		if (strlen($strQueryText)<=0)
		{
			$this->AbortResultCache();
			if (intval($error_number)>0 || strlen($error_text)>0)
				ShowError( GetMessage("IMYIE_ERROR_GET_DATA").": (".$error_number.") ".$error_text );
		} else {
			// __________________________ work with cache __________________________ //
			$xmlObj = simplexml_load_string($strQueryText);
			if($xmlObj->count == null)
			{
				$this->AbortResultCache();
				ShowError( GetMessage("IMYIE_ERROR_CANT_CREATE_OBJ") );
			} else {
				if($LANG_CHARSET=="AUTO")
				{
					if(strtoupper(LANG_CHARSET)=="WINDOWS-1251")
					{
						$arResult["NOT_FORMATED"] = objectsIntoArray($xmlObj, "Y");
					} else {
						$arResult["NOT_FORMATED"] = objectsIntoArray($xmlObj);
					}
				} else {
					if($LANG_CHARSET=="WINDOWS-1251" && $LANG_CHARSET!="")
					{
						$arResult["NOT_FORMATED"] = objectsIntoArray($xmlObj, "Y");
					} elseif($LANG_CHARSET=="UTF-8" && $LANG_CHARSET!="")
					{
						$arResult["NOT_FORMATED"] = objectsIntoArray($xmlObj);
					} else {
						if(strtoupper(LANG_CHARSET)=="WINDOWS-1251")
						{
							$arResult["NOT_FORMATED"] = objectsIntoArray($xmlObj, "Y");
						} else {
							$arResult["NOT_FORMATED"] = objectsIntoArray($xmlObj);
						}
					}
				}
			}
			// __________________________ work with cache __________________________ //
		}
		$this->IncludeComponentTemplate();
	}
} else {
	ShowError( GetMessage("IMYIE_ERROR_NO_CITY_ID") );
	return;
}
?>