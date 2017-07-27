<?
use Bitrix\Main;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Currency;

Loc::loadMessages(__FILE__);

class CAllCurrency
{
/*
* @deprecated deprecated since currency 9.0.0
* @see CCurrency::GetByID()
*/
	public function GetCurrency($currency)
	{
		return CCurrency::GetByID($currency);
	}

	public function CheckFields($ACTION, &$arFields, $strCurrencyID = false)
	{
		global $APPLICATION, $DB, $USER;

		$arMsg = array();

		$ACTION = strtoupper($ACTION);
		if ($ACTION != 'UPDATE' && $ACTION != 'ADD')
			return false;
		if (!is_array($arFields))
			return false;

		$defaultValues = array(
			'SORT' => 100,
			'BASE' => 'N'
		);

		$clearFields = array(
			'~CURRENCY',
			'~NUMCODE',
			'~AMOUNT_CNT',
			'~AMOUNT',
			'~BASE',
			'DATE_UPDATE',
			'DATE_CREATE',
			'~DATE_CREATE',
			'~MODIFIED_BY',
			'~CREATED_BY',
			'CURRENT_BASE_RATE',
			'~CURRENT_BASE_RATE'
		);
		if ($ACTION == 'UPDATE')
			$clearFields[] = 'CREATED_BY';
		$arFields = array_filter($arFields, 'CCurrency::clearFields');
		foreach ($clearFields as &$fieldName)
		{
			if (isset($arFields[$fieldName]))
				unset($arFields[$fieldName]);
		}
		unset($fieldName, $clearFields);

		if ($ACTION == 'ADD')
		{
			if (!isset($arFields['CURRENCY']))
			{
				$arMsg[] = array('id' => 'CURRENCY', 'text' => Loc::getMessage('BT_MOD_CURR_ERR_CURR_CUR_ID_ABSENT'));
			}
			elseif (!preg_match("~^[a-z]{3}$~i", $arFields['CURRENCY']))
			{
				$arMsg[] = array('id' => 'CURRENCY','text' => Loc::getMessage('BT_MOD_CURR_ERR_CURR_CUR_ID_LAT_EXT'));
			}
			else
			{
				$db_result = $DB->Query("select 'x' FROM b_catalog_currency where UPPER(CURRENCY) = UPPER('".$DB->ForSql($arFields['CURRENCY'])."')");
				if ($db_result->Fetch())
				{
					$arMsg[] = array('id' => 'CURRENCY','text' => Loc::getMessage('BT_MOD_CURR_ERR_CURR_CUR_ID_EXISTS'));
				}
				else
				{
					$arFields['CURRENCY'] = strtoupper($arFields['CURRENCY']);
				}
			}
			$arFields = array_merge($defaultValues, $arFields);
			if (!isset($arFields['AMOUNT_CNT']))
			{
				$arMsg[] = array('id' => 'AMOUNT_CNT','text' => Loc::getMessage('BT_MOD_CURR_ERR_CURR_AMOUNT_CNT_ABSENT'));
			}
			if (!isset($arFields['AMOUNT']))
			{
				$arMsg[] = array('id' => 'AMOUNT','text' => Loc::getMessage('BT_MOD_CURR_ERR_CURR_AMOUNT_ABSENT'));
			}
		}

		if ($ACTION == 'UPDATE')
		{
			$strCurrencyID = Currency\CurrencyManager::checkCurrencyID($strCurrencyID);
			if ($strCurrencyID === false)
			{
				$arMsg[] = array('id' => 'CURRENCY','text' => Loc::getMessage('BT_MOD_CURR_ERR_CURR_CUR_ID_BAD'));
			}
			if (isset($arFields['CURRENCY']))
				unset($arFields['CURRENCY']);
		}

		if (empty($arMsg))
		{
			if (isset($arFields['AMOUNT_CNT']))
			{
				$arFields['AMOUNT_CNT'] = (int)$arFields['AMOUNT_CNT'];
				if ($arFields['AMOUNT_CNT'] <= 0)
				{
					$arMsg[] = array('id' => 'AMOUNT_CNT','text' => Loc::getMessage('BT_MOD_CURR_ERR_CURR_AMOUNT_CNT_BAD'));
				}
			}
			if (isset($arFields['AMOUNT']))
			{
				$arFields['AMOUNT'] = (float)$arFields['AMOUNT'];
				if ($arFields['AMOUNT'] <= 0)
				{
					$arMsg[] = array('id' => 'AMOUNT','text' => Loc::getMessage('BT_MOD_CURR_ERR_CURR_AMOUNT_BAD'));
				}
			}
			if (isset($arFields['SORT']))
			{
				$arFields['SORT'] = (int)$arFields['SORT'];
				if ($arFields['SORT'] <= 0)
				{
					$arFields['SORT'] = 100;
				}
			}
			if (isset($arFields['BASE']))
			{
				$arFields['BASE'] = ((string)$arFields['BASE'] === 'Y' ? 'Y' : 'N');
			}
			if (isset($arFields['NUMCODE']))
			{
				$arFields['NUMCODE'] = (string)$arFields['NUMCODE'];
				if ($arFields['NUMCODE'] === '')
				{
					unset($arFields['NUMCODE']);
				}
				elseif (!preg_match("~^[0-9]{3}$~", $arFields['NUMCODE']))
				{
					$arMsg[] = array('id' => 'NUMCODE','text' => Loc::getMessage('BT_MOD_CURR_ERR_CURR_NUMCODE_IS_BAD'));
				}
			}
		}

		$intUserID = 0;
		$boolUserExist = self::isUserExists();
		if ($boolUserExist)
			$intUserID = (int)$USER->GetID();
		$strDateFunction = $DB->GetNowFunction();
		$arFields['~DATE_UPDATE'] = $strDateFunction;
		if ($boolUserExist)
		{
			if (!isset($arFields['MODIFIED_BY']))
				$arFields['MODIFIED_BY'] = $intUserID;
			$arFields['MODIFIED_BY'] = (int)$arFields['MODIFIED_BY'];
			if ($arFields['MODIFIED_BY'] <= 0)
				$arFields['MODIFIED_BY'] = $intUserID;
		}
		if ($ACTION == 'ADD')
		{
			$arFields['~DATE_CREATE'] = $strDateFunction;
			if ($boolUserExist)
			{
				if (!isset($arFields['CREATED_BY']))
					$arFields['CREATED_BY'] = $intUserID;
				$arFields['CREATED_BY'] = (int)$arFields['CREATED_BY'];
				if ($arFields['CREATED_BY'] <= 0)
					$arFields['CREATED_BY'] = $intUserID;
			}
		}

		if (isset($arFields['LANG']))
		{
			if (empty($arFields['LANG']) || !is_array($arFields['LANG']))
			{
				$arMsg[] = array('id' => 'LANG','text' => Loc::getMessage('BT_MOD_CURR_ERR_CURR_LANG_BAD'));
			}
			else
			{
				$langSettings = array();
				$currency = ($ACTION == 'ADD' ? $arFields['CURRENCY'] : $strCurrencyID);
				foreach ($arFields['LANG'] as $lang => $settings)
				{
					if (empty($settings) || !is_array($settings))
						continue;
					$langAction = 'ADD';
					if ($ACTION == 'UPDATE')
					{
						$langAction = (CCurrencyLang::isExistCurrencyLanguage($currency, $lang) ? 'UPDATE' : 'ADD');
					}
					$checkLang = CCurrencyLang::checkFields($langAction, $settings, $currency, $lang, true);
					$settings['CURRENCY'] = $currency;
					$settings['LID'] = $lang;
					$settings['IS_EXIST'] = ($langAction == 'ADD' ? 'N' : 'Y');
					$langSettings[$lang] = $settings;
					if (is_array($checkLang))
					{
						$arMsg = array_merge($arMsg, $checkLang);
					}
				}
				$arFields['LANG'] = $langSettings;
				unset($settings, $lang, $currency, $langSettings);
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

	public function Add($arFields)
	{
		global $DB;

		foreach (GetModuleEvents("currency", "OnBeforeCurrencyAdd", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array(&$arFields))===false)
				return false;
		}

		if (!CCurrency::CheckFields('ADD', $arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_catalog_currency", $arFields);

		$strSql = "insert into b_catalog_currency(".$arInsert[0].") values(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if (isset($arFields['LANG']))
		{
			foreach ($arFields['LANG'] as $lang => $settings)
			{
				if ($settings['IS_EXIST'] == 'N')
				{
					CCurrencyLang::Add($settings);
				}
				else
				{
					CCurrencyLang::Update($arFields['CURRENCY'], $lang, $settings);
				}
			}
			unset($settings, $lang);
		}

		self::updateBaseRates('', $arFields['CURRENCY']);
		Currency\CurrencyManager::clearCurrencyCache();

		foreach (GetModuleEvents("currency", "OnCurrencyAdd", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($arFields['CURRENCY'], $arFields));
		}

		return $arFields["CURRENCY"];
	}

	public function Update($currency, $arFields)
	{
		global $DB;

		foreach (GetModuleEvents("currency", "OnBeforeCurrencyUpdate", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array($currency, &$arFields))===false)
				return false;
		}

		$currency = Currency\CurrencyManager::checkCurrencyID($currency);
		if (!CCurrency::CheckFields('UPDATE', $arFields, $currency))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_catalog_currency", $arFields);
		if (!empty($strUpdate))
		{
			$strSql = "update b_catalog_currency set ".$strUpdate." where CURRENCY = '".$DB->ForSql($currency)."'";
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			self::updateBaseRates('', $currency);
			Currency\CurrencyManager::clearTagCache($currency);
		}
		if (isset($arFields['LANG']))
		{
			foreach ($arFields['LANG'] as $lang => $settings)
			{
				if ($settings['IS_EXIST'] == 'N')
				{
					CCurrencyLang::Add($settings);
				}
				else
				{
					CCurrencyLang::Update($currency, $lang, $settings);
				}
			}
			unset($settings, $lang);
		}
		if (!empty($strUpdate) || isset($arFields['LANG']))
		{
			Currency\CurrencyManager::clearCurrencyCache();
		}

		foreach (GetModuleEvents("currency", "OnCurrencyUpdate", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($currency, $arFields));
		}

		return $currency;
	}

	public function Delete($currency)
	{
		global $DB;

		$currency = Currency\CurrencyManager::checkCurrencyID($currency);
		if ($currency === false)
			return false;

		foreach(GetModuleEvents("currency", "OnBeforeCurrencyDelete", true) as $arEvent)
		{
			if(ExecuteModuleEventEx($arEvent, array($currency))===false)
				return false;
		}

		$sqlCurrency = $DB->ForSQL($currency);

		$query = "select CURRENCY, BASE from b_catalog_currency where CURRENCY = '".$sqlCurrency."'";
		$currencyIterator = $DB->Query($query, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
		if ($existCurrency = $currencyIterator->Fetch())
		{
			if ($existCurrency['BASE'] == 'Y')
				return false;
		}
		else
		{
			return false;
		}

		foreach(GetModuleEvents("currency", "OnCurrencyDelete", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($currency));
		}

		Currency\CurrencyManager::clearCurrencyCache();

		$DB->Query("delete from b_catalog_currency_lang where CURRENCY = '".$sqlCurrency."'", true);
		$DB->Query("delete from b_catalog_currency_rate where CURRENCY = '".$sqlCurrency."'", true);

		Currency\CurrencyManager::clearTagCache($currency);

		return $DB->Query("delete from b_catalog_currency where CURRENCY = '".$sqlCurrency."'", true);
	}

	public function GetByID($currency)
	{
		global $DB;

		$currency = Currency\CurrencyManager::checkCurrencyID($currency);
		if ($currency === false)
			return false;

		$strSql = "select CUR.CURRENCY, CUR.AMOUNT_CNT, CUR.AMOUNT, CUR.SORT, CUR.BASE, CUR.NUMCODE, CUR.CREATED_BY, CUR.MODIFIED_BY, ".
			$DB->DateToCharFunction('CUR.DATE_UPDATE', 'FULL').' as DATE_UPDATE_FORMAT, '.
			$DB->DateToCharFunction('CUR.DATE_CREATE', 'FULL').' as DATE_CREATE_FORMAT '.
			"from b_catalog_currency CUR where CUR.CURRENCY = '".$DB->ForSQL($currency)."'";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
			return $res;

		return false;
	}

	public function GetBaseCurrency()
	{
		return Currency\CurrencyManager::getBaseCurrency();
	}

	public function SetBaseCurrency($currency)
	{
		global $DB;
		$currency = Currency\CurrencyManager::checkCurrencyID($currency);
		if ($currency === false)
			return false;
		$currency = $DB->ForSql($currency);
		$query = "select CURRENCY, BASE from b_catalog_currency where CURRENCY = '".$currency."'";
		$currencyIterator = $DB->Query($query, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
		if ($existCurrency = $currencyIterator->Fetch())
		{
			if ($existCurrency['BASE'] == 'Y')
				return true;
			$result = self::updateBaseCurrency($currency);
			if ($result)
			{
				Currency\CurrencyManager::clearCurrencyCache();
			}
			return $result;
		}
		return false;
	}

	public function SelectBox($sFieldName, $sValue, $sDefaultValue = '', $bFullName = true, $JavaFunc = '', $sAdditionalParams = '')
	{
		$s = '<select name="'.$sFieldName.'"';
		if ('' != $JavaFunc) $s .= ' onchange="'.$JavaFunc.'"';
		if ('' != $sAdditionalParams) $s .= ' '.$sAdditionalParams.' ';
		$s .= '>';
		$s1 = '';
		$found = false;

		$currencyList = Currency\CurrencyManager::getCurrencyList();
		if (!empty($currencyList) && is_array($currencyList))
		{
			foreach ($currencyList as $currency => $title)
			{
				$found = ($currency == $sValue);
				$s1 .= '<option value="'.$currency.'"'.($found ? ' selected' : '').'>'.($bFullName ? htmlspecialcharsex($title) : $currency).'</option>';
			}
		}
		if ('' != $sDefaultValue)
			$s .= '<option value=""'.($found ? '' : ' selected').'>'.htmlspecialcharsex($sDefaultValue).'</option>';
		return $s.$s1.'</select>';
	}

	public function GetList(&$by, &$order, $lang = LANGUAGE_ID)
	{
		global $CACHE_MANAGER;

		if (defined("CURRENCY_SKIP_CACHE") && CURRENCY_SKIP_CACHE
			|| strtolower($by) == "name"
			|| strtolower($by) == "currency"
			|| strtolower($order) == "desc")
		{
			$dbCurrencyList = CCurrency::__GetList($by, $order, $lang);
		}
		else
		{
			$cacheTime = (int)CURRENCY_CACHE_DEFAULT_TIME;
			if (defined("CURRENCY_CACHE_TIME"))
				$cacheTime = (int)CURRENCY_CACHE_TIME;

			if ($CACHE_MANAGER->Read($cacheTime, "currency_currency_list_".$lang, 'b_catalog_currency'))
			{
				$arCurrencyList = $CACHE_MANAGER->Get("currency_currency_list_".$lang);
				$dbCurrencyList = new CDBResult();
				$dbCurrencyList->InitFromArray($arCurrencyList);
			}
			else
			{
				$arCurrencyList = array();
				$dbCurrencyList = CCurrency::__GetList($by, $order, $lang);
				while ($arCurrency = $dbCurrencyList->Fetch())
					$arCurrencyList[] = $arCurrency;

				$CACHE_MANAGER->Set("currency_currency_list_".$lang, $arCurrencyList);

				$dbCurrencyList = new CDBResult();
				$dbCurrencyList->InitFromArray($arCurrencyList);
			}
		}

		return $dbCurrencyList;
	}

	public function __GetList(&$by, &$order, $lang = LANGUAGE_ID)
	{
		$lang = substr((string)$lang, 0, 2);
		$normalBy = strtolower($by);
		if ($normalBy != 'currency' && $normalBy != 'name')
		{
			$normalBy = 'sort';
			$by = 'sort';
		}
		$normalOrder = strtoupper($order);
		if ($normalOrder != 'DESC')
		{
			$normalOrder = 'ASC';
			$order = 'asc';
		}
		switch($normalBy)
		{
			case 'currency':
				$currencyOrder = array('CURRENCY' => $normalOrder);
				break;
			case 'name':
				$currencyOrder = array('FULL_NAME' => $normalOrder);
				break;
			case 'sort':
			default:
				$currencyOrder = array('SORT' => $normalOrder);
				break;
		}
		unset($normalOrder, $normalBy);

		$datetimeField = Currency\CurrencyManager::getDatetimeExpressionTemplate();
		$currencyIterator = Currency\CurrencyTable::getList(array(
			'select' => array(
				'CURRENCY', 'AMOUNT_CNT', 'AMOUNT', 'SORT', 'BASE', 'NUMCODE', 'CREATED_BY', 'MODIFIED_BY',
				new Main\Entity\ExpressionField('DATE_UPDATE_FORMAT', $datetimeField,  array('DATE_UPDATE'), array('data_type' => 'datetime')),
				new Main\Entity\ExpressionField('DATE_CREATE_FORMAT', $datetimeField,  array('DATE_CREATE'), array('data_type' => 'datetime')),
				'FULL_NAME' => 'RT_LANG.FULL_NAME', 'LID' => 'RT_LANG.LID', 'FORMAT_STRING' => 'RT_LANG.FORMAT_STRING',
				'DEC_POINT' => 'RT_LANG.DEC_POINT', 'THOUSANDS_SEP' => 'RT_LANG.THOUSANDS_SEP',
				'DECIMALS' => 'RT_LANG.DECIMALS', 'HIDE_ZERO' => 'RT_LANG.HIDE_ZERO'
			),
			'order' => $currencyOrder,
			'runtime' => array(
				'RT_LANG' => array(
					'data_type' => 'Bitrix\Currency\CurrencyLang',
					'reference' => array(
						'=this.CURRENCY' => 'ref.CURRENCY',
						'=ref.LID' => new Main\DB\SqlExpression('?', $lang)
					)
				)
			)
		));
		unset($datetimeField);
		$currencyList = array();
		while ($currency = $currencyIterator->fetch())
		{
			$currency['DATE_UPDATE'] = $currency['DATE_UPDATE_FORMAT'];
			$currency['DATE_CREATE'] = $currency['DATE_CREATE_FORMAT'];
			$currencyList[] = $currency;
		}
		unset($currency, $currencyIterator);
		$result = new CDBResult();
		$result->InitFromArray($currencyList);
		return $result;
	}

	public static function isUserExists()
	{
		global $USER;
		return (isset($USER) && $USER instanceof CUser);
	}

	public static function getInstalledCurrencies()
	{
		return Currency\CurrencyManager::getInstalledCurrencies();
	}

	public static function clearCurrencyCache()
	{
		Currency\CurrencyManager::clearCurrencyCache();
	}

	public static function clearTagCache($currency)
	{
		Currency\CurrencyManager::clearTagCache($currency);
	}

	public static function checkCurrencyID($currency)
	{
		return Currency\CurrencyManager::checkCurrencyID($currency);
	}

	public static function updateCurrencyBaseRate($currency)
	{
		global $DB;

		$currency = Currency\CurrencyManager::checkCurrencyID($currency);
		if ($currency === false)
			return;
		$query = "select CURRENCY from b_catalog_currency where CURRENCY = '".$currency."'";
		$currencyIterator = $DB->Query($query, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
		if ($existCurrency = $currencyIterator->Fetch())
		{
			self::updateBaseRates('', $existCurrency['CURRENCY']);
		}
	}

	public static function updateAllCurrencyBaseRate()
	{
		global $DB;

		$baseCurrency = (string)Currency\CurrencyManager::getBaseCurrency();
		if ($baseCurrency === '')
			return;

		$query = "select CURRENCY from b_catalog_currency where 1=1";
		$currencyIterator = $DB->Query($query, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
		while ($existCurrency = $currencyIterator->Fetch())
		{
			self::updateBaseRates($baseCurrency, $existCurrency['CURRENCY']);
		}
	}

	public static function initCurrencyBaseRateAgent()
	{
		if (!ModuleManager::isModuleInstalled('bitrix24'))
		{
			$agentIterator = CAgent::GetList(
				array(),
				array('MODULE_ID' => 'currency','=NAME' => '\Bitrix\Currency\CurrencyTable::currencyBaseRateAgent();')
			);
			if ($agentIterator)
			{
				if (!($currencyAgent = $agentIterator->Fetch()))
				{
					self::updateAllCurrencyBaseRate();
					$checkDate = Main\Type\DateTime::createFromTimestamp(strtotime('tomorrow 00:01:00'));;
					CAgent::AddAgent('\Bitrix\Currency\CurrencyTable::currencyBaseRateAgent();', 'currency', 'Y', 86400, '', 'Y', $checkDate->toString(), 100, false, true);
				}
			}
		}
		return '';
	}

	protected static function updateBaseCurrency($currency)
	{
		global $DB, $USER;
		$currency = Currency\CurrencyManager::checkCurrencyID($currency);
		if ($currency === false)
			return false;
		$userID = (self::isUserExists() ? (int)$USER->GetID() : false);
		$currentDate = $DB->GetNowFunction();
		$fields = array(
			'BASE' => 'N',
			'~DATE_UPDATE' => $currentDate,
			'MODIFIED_BY' => $userID
		);
		$update = $DB->PrepareUpdate('b_catalog_currency', $fields);
		$query = "update b_catalog_currency set ".$update." where CURRENCY <> '".$currency."' and BASE = 'Y'";
		$DB->Query($query, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
		$fields = array(
			'BASE' => 'Y',
			'~DATE_UPDATE' => $currentDate,
			'MODIFIED_BY' => $userID,
			'AMOUNT' => 1,
			'AMOUNT_CNT' => 1
		);
		$update = $DB->PrepareUpdate('b_catalog_currency', $fields);
		$query = "update b_catalog_currency set ".$update." where CURRENCY = '".$currency."'";
		$DB->Query($query, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
		self::updateBaseRates($currency);
		return true;
	}

	protected static function updateBaseRates($currency = '', $updateCurrency = '')
	{
		global $DB;
		if ($currency === '')
		{
			$currency = (string)Currency\CurrencyManager::getBaseCurrency();
		}
		if ($currency === '')
			return;

		if ($updateCurrency != '')
		{
			$factor = 1;
			if ($updateCurrency != $currency)
			{
				$factor = CCurrencyRates::GetConvertFactor($updateCurrency, $currency);
			}
			$query = "update b_catalog_currency set CURRENT_BASE_RATE = ".(float)$factor." where CURRENCY = '".$updateCurrency."'";
			$DB->Query($query, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
		}
		else
		{
			$query = "select CURRENCY from b_catalog_currency";
			$currencyIterator = $DB->Query($query, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
			while ($oneCurrency = $currencyIterator->Fetch())
			{
				$factor = 1;
				if ($oneCurrency['CURRENCY'] != $currency)
				{
					$factor = CCurrencyRates::GetConvertFactor($oneCurrency['CURRENCY'], $currency);
				}
				$query = "update b_catalog_currency set CURRENT_BASE_RATE = ".(float)$factor." where CURRENCY = '".$oneCurrency['CURRENCY']."'";
				$DB->Query($query, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
			}
		}
	}

	protected static function clearFields($value)
	{
		return ($value !== null);
	}
}

class CCurrency extends CAllCurrency
{

}
?>