<?
//IncludeModuleLangFile(__FILE__);

class CAllCurrencyLang
{
	function Add($arFields)
	{
		global $DB;
		global $stackCacheManager;
		global $CACHE_MANAGER;

		$arInsert = $DB->PrepareInsert("b_catalog_currency_lang", $arFields);

		$strSql =
			"INSERT INTO b_catalog_currency_lang(".$arInsert[0].") ".
			"VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$stackCacheManager->Clear("currency_currency_lang");
		$CACHE_MANAGER->Clean("currency_currency_list");
		$CACHE_MANAGER->Clean("currency_currency_list_".substr($arFields['LID'], 0, 2));

		return true;
	}

	function Update($currency, $lang, $arFields)
	{
		global $DB;
		global $stackCacheManager;
		global $CACHE_MANAGER;

		$strUpdate = $DB->PrepareUpdate("b_catalog_currency_lang", $arFields);
		$strSql = "UPDATE b_catalog_currency_lang SET ".$strUpdate." WHERE CURRENCY = '".$DB->ForSql($currency, 3)."' AND LID='".$DB->ForSql($lang, 2)."'";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$stackCacheManager->Clear("currency_currency_lang");
		$CACHE_MANAGER->Clean("currency_currency_list");
		$CACHE_MANAGER->Clean("currency_currency_list_".substr($lang, 0, 2));
		if (isset($arFields['LID']))
			$CACHE_MANAGER->Clean("currency_currency_list_".substr($arFields['LID'], 0, 2));

		return true;
	}

	function Delete($currency, $lang)
	{
		global $DB;
		global $stackCacheManager;
		global $CACHE_MANAGER;

		$stackCacheManager->Clear("currency_currency_lang");
		$CACHE_MANAGER->Clean("currency_currency_list");
		$CACHE_MANAGER->Clean("currency_currency_list_".substr($lang, 0, 2));

		$strSql = "DELETE FROM b_catalog_currency_lang ".
			"WHERE CURRENCY = '".$DB->ForSql($currency, 3)."' ".
			"	AND LID = '".$DB->ForSql($lang, 2)."' ";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return true;
	}

	function GetByID($currency, $lang)
	{
		global $DB;

		$strSql =
			"SELECT * ".
			"FROM b_catalog_currency_lang ".
			"WHERE CURRENCY = '".$DB->ForSql($currency, 3)."' ".
			"	AND LID = '".$DB->ForSql($lang, 2)."' ";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
			return $res;

		return false;
	}

	function GetCurrencyFormat($currency, $lang = LANGUAGE_ID)
	{
		global $DB;
		global $stackCacheManager;

		if (defined("CURRENCY_SKIP_CACHE") && CURRENCY_SKIP_CACHE)
		{
			$arCurrencyLang = CCurrencyLang::GetByID($currency, $lang);
		}
		else
		{
			$cacheTime = CURRENCY_CACHE_DEFAULT_TIME;
			if (defined("CURRENCY_CACHE_TIME"))
				$cacheTime = intval(CURRENCY_CACHE_TIME);

			$strCacheKey = $currency."_".$lang;

			$stackCacheManager->SetLength("currency_currency_lang", 20);
			$stackCacheManager->SetTTL("currency_currency_lang", $cacheTime);
			if ($stackCacheManager->Exist("currency_currency_lang", $strCacheKey))
			{
				$arCurrencyLang = $stackCacheManager->Get("currency_currency_lang", $strCacheKey);
			}
			else
			{
				$arCurrencyLang = CCurrencyLang::GetByID($currency, $lang);
				$stackCacheManager->Set("currency_currency_lang", $strCacheKey, $arCurrencyLang);
			}
		}

		return $arCurrencyLang;
	}

	function GetList(&$by, &$order, $currency = "")
	{
		global $DB;

		$strSql =
			"SELECT CURL.CURRENCY, CURL.LID, CURL.FORMAT_STRING, CURL.FULL_NAME, CURL.DEC_POINT, CURL.THOUSANDS_SEP, CURL.DECIMALS, CURL.THOUSANDS_VARIANT ".
			"FROM b_catalog_currency_lang CURL ";

		if (strlen($currency)>0)
		{
			$strSql .= "WHERE CURL.CURRENCY = '".$DB->ForSql($currency, 3)."' ";
		}

		if (strtolower($by) == "currency") $strSqlOrder = " ORDER BY CURL.CURRENCY ";
		elseif (strtolower($by) == "name") $strSqlOrder = " ORDER BY CURL.FULL_NAME ";
		else
		{
			$strSqlOrder = " ORDER BY CURL.LID ";
			$by = "lang";
		}

		if ($order=="desc")
			$strSqlOrder .= " desc ";
		else
			$order = "asc";

		$strSql .= $strSqlOrder;
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $res;
	}
}
?>