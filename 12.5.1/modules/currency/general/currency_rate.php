<?
IncludeModuleLangFile(__FILE__);

class CAllCurrencyRates
{
	function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		global $APPLICATION;
		global $DB;

		$arMsg = array();

		if ('UPDATE' != $ACTION && 'ADD' != $ACTION)
			return false;
		if (array_key_exists('ID', $arFields))
			unset($arFields['ID']);

		if ('UPDATE' == $ACTION && 0 >= intval($ID))
		{
			$arMsg[] = array('id' => 'ID','text' => GetMessage('BT_MOD_CURR_ERR_RATE_ID_BAD'));
		}

		if (!isset($arFields["CURRENCY"]))
		{
			$arMsg[] = array('id' => 'CURRENCY','text' => GetMessage('BT_MOD_CURR_ERR_RATE_CURRENCY_ABSENT'));
		}
		else
		{
			$arFields["CURRENCY"] = substr($arFields["CURRENCY"],0,3);
		}

		if (empty($arFields['DATE_RATE']))
		{
			$arMsg[] = array('id' => 'DATE_RATE','text' => GetMessage('BT_MOD_CURR_ERR_RATE_DATE_ABSENT'));
		}
		elseif (!$DB->IsDate($arFields['DATE_RATE']))
		{
			$arMsg[] = array('id' => 'DATE_RATE','text' => GetMessage('BT_MOD_CURR_ERR_RATE_DATE_FORMAT_BAD'));
		}

		if (is_set($arFields, 'RATE_CNT') || 'ADD' == $ACTION)
		{
			if (!isset($arFields['RATE_CNT']))
			{
				$arMsg[] = array('id' => 'RATE_CNT','text' => GetMessage('BT_MOD_CURR_ERR_RATE_RATE_CNT_ABSENT'));
			}
			elseif (0 >= intval($arFields['RATE_CNT']))
			{
				$arMsg[] = array('id' => 'RATE_CNT','text' => GetMessage('BT_MOD_CURR_ERR_RATE_RATE_CNT_BAD'));
			}
			else
			{
				$arFields['RATE_CNT'] = intval($arFields['RATE_CNT']);
			}
		}
		if (is_set($arFields['RATE']) || 'ADD' == $ACTION)
		{
			if (!isset($arFields['RATE']))
			{
				$arMsg[] = array('id' => 'RATE','text' => GetMessage('BT_MOD_CURR_ERR_RATE_RATE_ABSENT'));
			}
			else
			{
				$arFields['RATE'] = doubleval($arFields['RATE']);
				if (!(0 < $arFields['RATE']))
				{
					$arMsg[] = array('id' => 'RATE','text' => GetMessage('BT_MOD_CURR_ERR_RATE_RATE_BAD'));
				}
			}
		}

		if (!empty($arMsg))
		{
			$obError = new CAdminException($arMsg);
			$APPLICATION->ResetException();
			$APPLICATION->ThrowException($obError);
			return false;
		}
		return true;
	}

	function Add($arFields)
	{
		global $DB;
		global $CACHE_MANAGER;
		global $APPLICATION;
		global $stackCacheManager;

		$arMsg = array();

		if (!CCurrencyRates::CheckFields("ADD", $arFields))
			return false;

		$db_result = $DB->Query("SELECT 'x' ".
			"FROM b_catalog_currency_rate ".
			"WHERE CURRENCY = '".$DB->ForSql($arFields["CURRENCY"])."' ".
			"	AND DATE_RATE = ".$DB->CharToDateFunction($DB->ForSql($arFields["DATE_RATE"]), "SHORT"));
		if ($db_result->Fetch())
		{
			$arMsg[] = array("id"=>"DATE_RATE", "text"=> GetMessage("ERROR_ADD_REC2"));
			$e = new CAdminException($arMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}
		else
		{
			$stackCacheManager->Clear("currency_rate");

			$ID = $DB->Add("b_catalog_currency_rate", $arFields);

			if (defined("BX_COMP_MANAGED_CACHE"))
				$CACHE_MANAGER->ClearByTag("currency_id_".$arFields["CURRENCY"]);

			return $ID;
		}
	}

	function Update($ID, $arFields)
	{
		global $DB;
		global $CACHE_MANAGER;
		global $APPLICATION;
		global $stackCacheManager;

		$ID = intval($ID);
		$arMsg = array();

		if (!CCurrencyRates::CheckFields("UPDATE", $arFields, $ID))
			return false;

		$db_result = $DB->Query("SELECT 'x' ".
			"FROM b_catalog_currency_rate ".
			"WHERE CURRENCY = '".$DB->ForSql($arFields["CURRENCY"])."' ".
			"	AND DATE_RATE = ".$DB->CharToDateFunction($DB->ForSql($arFields["DATE_RATE"]), "SHORT")." ".
			"	AND ID<>".$ID." ");
		if ($db_result->Fetch())
		{
			$arMsg[] = array("id"=>"DATE_RATE", "text"=> GetMessage("ERROR_ADD_REC2"));
			$e = new CAdminException($arMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}
		else
		{
			$strUpdate = $DB->PrepareUpdate("b_catalog_currency_rate", $arFields);
			if (!empty($strUpdate))
			{
				$strSql = "UPDATE b_catalog_currency_rate SET ".$strUpdate." WHERE ID = ".$ID;
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

				$stackCacheManager->Clear("currency_rate");

				if (defined("BX_COMP_MANAGED_CACHE"))
					$CACHE_MANAGER->ClearByTag("currency_id_".$arFields["CURRENCY"]);
			}
		}
		return true;
	}

	function Delete($ID)
	{
		global $DB;
		global $CACHE_MANAGER;
		global $stackCacheManager;
		global $APPLICATION;

		$arMsg = array();

		$ID = intval($ID);

		if (0 >= $ID)
			return false;

		$arFields = CCurrencyRates::GetByID($ID);
		if (!is_array($arFields))
		{
			$arMsg = array('id' => 'ID', 'text' => GetMessage('BT_MOD_CURR_ERR_RATE_CANT_DELETE_ABSENT_ID'));
			$e = new CAdminException($arMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}

		$stackCacheManager->Clear("currency_rate");

		$strSql = "DELETE FROM b_catalog_currency_rate WHERE ID = ".$ID." ";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if (defined("BX_COMP_MANAGED_CACHE"))
			$CACHE_MANAGER->ClearByTag("currency_id_".$arFields['CURRENCY']);

		return true;
	}

	function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		$strSql =
			"SELECT C.*, ".$DB->DateToCharFunction("C.DATE_RATE", "SHORT")." as DATE_RATE ".
			"FROM b_catalog_currency_rate C ".
			"WHERE ID = ".$ID." ";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			return $res;
		}
		return false;
	}


	function GetList(&$by, &$order, $arFilter=Array())
	{
		global $DB, $DBType;
		$arSqlSearch = Array();

		if(!is_array($arFilter))
			$filter_keys = Array();
		else
			$filter_keys = array_keys($arFilter);

		for($i=0; $i<count($filter_keys); $i++)
		{
			$val = $DB->ForSql($arFilter[$filter_keys[$i]]);
			if (strlen($val)<=0) continue;

			$key = $filter_keys[$i];
			if ($key[0]=="!")
			{
				$key = substr($key, 1);
				$bInvert = true;
			}
			else
				$bInvert = false;

			switch(strtoupper($key))
			{
			case "CURRENCY":
				$arSqlSearch[] = "C.CURRENCY = '".$val."'";
				break;
			case "DATE_RATE":
				$arSqlSearch[] = "(C.DATE_RATE ".($bInvert?"<":">=")." ".($DBType == "mysql"?"CAST(":"").$DB->CharToDateFunction($DB->ForSql($val), "SHORT").($DBType == "mysql"?" AS DATE)":"")."".($bInvert?"":" OR C.DATE_RATE IS NULL").")";
				break;
			}
		}

		$strSqlSearch = "";
		for($i=0; $i<count($arSqlSearch); $i++)
		{
			if($i>0)
				$strSqlSearch .= " AND ";
			else
				$strSqlSearch = " WHERE ";

			$strSqlSearch .= " (".$arSqlSearch[$i].") ";
		}

		$strSql =
			"SELECT C.ID, C.CURRENCY, C.RATE_CNT, C.RATE, ".$DB->DateToCharFunction("C.DATE_RATE", "SHORT")." as DATE_RATE ".
			"FROM b_catalog_currency_rate C ".
			$strSqlSearch;

		if (strtolower($by) == "curr") $strSqlOrder = " ORDER BY C.CURRENCY ";
		elseif (strtolower($by) == "rate") $strSqlOrder = " ORDER BY C.RATE ";
		else
		{
			$strSqlOrder = " ORDER BY C.DATE_RATE ";
			$by = "date";
		}

		if (strtolower($order)=="desc")
			$strSqlOrder .= " desc ";
		else
			$order = "asc";

		$strSql .= $strSqlOrder;
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $res;
	}

	function GetConvertFactorEx($curFrom, $curTo, $valDate = "")
	{
		global $stackCacheManager;

		if(strlen($curFrom) <= 0 || strlen($curTo) <= 0)
			return 0;

		if (strlen($valDate) <= 0)
			$valDate = date("Y-m-d");
		list($dpYear, $dpMonth, $dpDay) = explode("-", $valDate, 3);
		$dpDay += 1;
		if($dpYear < 2038 && $dpYear > 1970)
			$valDate = date("Y-m-d", mktime(0, 0, 0, $dpMonth, $dpDay, $dpYear));
		else
			$valDate = date("Y-m-d");

		$curFromRate = 0;
		$curFromRateCnt = 0;
		$curToRate = 1;
		$curToRateCnt = 1;

		if (defined("CURRENCY_SKIP_CACHE") && CURRENCY_SKIP_CACHE)
		{
			$cacheTime = 0;
			if($res = $this->_get_last_rates($valDate, $curFrom))
			{
				$curFromRate = doubleval($res["RATE"]);
				$curFromRateCnt = intval($res["RATE_CNT"]);
				if ($curFromRate <= 0)
				{
					$curFromRate = doubleval($res["AMOUNT"]);
					$curFromRateCnt = intval($res["AMOUNT_CNT"]);
				}
			}

			if($res = $this->_get_last_rates($valDate, $curTo))
			{
				$curToRate = doubleval($res["RATE"]);
				$curToRateCnt = intval($res["RATE_CNT"]);
				if ($curToRate <= 0)
				{
					$curToRate = doubleval($res["AMOUNT"]);
					$curToRateCnt = intval($res["AMOUNT_CNT"]);
				}
			}
		}
		else
		{
			$cacheTime = CURRENCY_CACHE_DEFAULT_TIME;
			if (defined("CURRENCY_CACHE_TIME"))
				$cacheTime = IntVal(CURRENCY_CACHE_TIME);

			$strCacheKey = "C_R_".$valDate."_".$curFrom."_".$curTo;

			$stackCacheManager->SetLength("currency_rate", 10);
			$stackCacheManager->SetTTL("currency_rate", $cacheTime);
			if ($stackCacheManager->Exist("currency_rate", $strCacheKey))
			{
				$arResult = $stackCacheManager->Get("currency_rate", $strCacheKey);

				$curFromRate = $arResult["curFromRate"];
				$curFromRateCnt = $arResult["curFromRateCnt"];
				$curToRate = $arResult["curToRate"];
				$curToRateCnt = $arResult["curToRateCnt"];
			}
			else
			{
				if($res = $this->_get_last_rates($valDate, $curFrom))
				{
					$curFromRate = doubleval($res["RATE"]);
					$curFromRateCnt = intval($res["RATE_CNT"]);
					if ($curFromRate <= 0)
					{
						$curFromRate = doubleval($res["AMOUNT"]);
						$curFromRateCnt = intval($res["AMOUNT_CNT"]);
					}
				}

				if($res = $this->_get_last_rates($valDate, $curTo))
				{
					$curToRate = doubleval($res["RATE"]);
					$curToRateCnt = intval($res["RATE_CNT"]);
					if ($curToRate <= 0)
					{
						$curToRate = doubleval($res["AMOUNT"]);
						$curToRateCnt = intval($res["AMOUNT_CNT"]);
					}
				}

				$arResult = array(
					"curFromRate" => $curFromRate,
					"curFromRateCnt" => $curFromRateCnt,
					"curToRate" => $curToRate,
					"curToRateCnt" => $curToRateCnt
				);

				$stackCacheManager->Set("currency_rate", $strCacheKey, $arResult);
			}
		}

		if($curFromRate == 0 || $curToRateCnt == 0 || $curToRate == 0 || $curFromRateCnt == 0)
			return 0;

		return DoubleVal($curFromRate*$curToRateCnt/$curToRate/$curFromRateCnt);
	}
}
?>