<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Currency;

Loc::loadMessages(__FILE__);

class CAllCurrencyLang
{
	const SEP_EMPTY = 'N';
	const SEP_DOT = 'D';
	const SEP_COMMA = 'C';
	const SEP_SPACE = 'S';
	const SEP_NBSPACE = 'B';

	static protected $arSeparators = array(
		self::SEP_EMPTY => '',
		self::SEP_DOT => '.',
		self::SEP_COMMA => ',',
		self::SEP_SPACE => ' ',
		self::SEP_NBSPACE => ' '
	);

	static protected $arDefaultValues = array(
		'FORMAT_STRING' => '#',
		'DEC_POINT' => '.',
		'THOUSANDS_SEP' => ' ',
		'DECIMALS' => 2,
		'THOUSANDS_VARIANT' => self::SEP_SPACE,
		'HIDE_ZERO' => 'N'
	);

	static protected $arCurrencyFormat = array();

	static protected $useHideZero = 0;

	public static function enableUseHideZero()
	{
		if (defined('ADMIN_SECTION') && ADMIN_SECTION === true)
			return;
		self::$useHideZero++;
	}

	public static function disableUseHideZero()
	{
		if (defined('ADMIN_SECTION') && ADMIN_SECTION === true)
			return;
		self::$useHideZero--;
	}

	public static function isAllowUseHideZero()
	{
		return (!(defined('ADMIN_SECTION') && ADMIN_SECTION === true) && self::$useHideZero >= 0);
	}

	public static function checkFields($action, &$fields, $currency = '', $language = '', $getErrors = false)
	{
		global $DB, $USER, $APPLICATION;

		$getErrors = ($getErrors === true);
		$action = strtoupper($action);
		if ($action != 'ADD' && $action != 'UPDATE')
			return false;
		if (!is_array($fields))
			return false;
		if ($action == 'ADD')
		{
			if (isset($fields['CURRENCY']))
				$currency = $fields['CURRENCY'];
			if (isset($fields['LID']))
				$language = $fields['LID'];
		}
		$currency = Currency\CurrencyManager::checkCurrencyID($currency);
		$language = Currency\CurrencyManager::checkLanguage($language);
		if ($currency === false || $language === false)
			return false;

		$errorMessages = array();

		$clearFields = array(
			'~CURRENCY',
			'~LID',
			'TIMESTAMP_X',
			'DATE_CREATE',
			'~DATE_CREATE',
			'~MODIFIED_BY',
			'~CREATED_BY'
		);
		if ($action == 'UPDATE')
		{
			$clearFields[] = 'CREATED_BY';
			$clearFields[] = 'CURRENCY';
			$clearFields[] = 'LID';
		}
		$fields = array_filter($fields, 'CCurrencyLang::clearFields');
		foreach ($clearFields as &$fieldName)
		{
			if (isset($fields[$fieldName]))
				unset($fields[$fieldName]);
		}
		unset($fieldName, $clearFields);

		if ($action == 'ADD')
		{
			$defaultValues = self::$arDefaultValues;
			unset($defaultValues['FORMAT_STRING']);

			$fields = array_merge($defaultValues, $fields);
			unset($defaultValues);

			if (!isset($fields['FORMAT_STRING']) || empty($fields['FORMAT_STRING']))
			{
				$errorMessages[] = array(
					'id' => 'FORMAT_STRING', 'text' => Loc::getMessage('BT_CUR_LANG_ERR_FORMAT_STRING_IS_EMPTY', array('#LANG#' => $language))
				);
			}

			if (empty($errorMessages))
			{
				$fields['CURRENCY'] = $currency;
				$fields['LID'] = $language;
			}
		}
		if (empty($errorMessages))
		{
			if (isset($fields['FORMAT_STRING']) && empty($fields['FORMAT_STRING']))
			{
				$errorMessages[] = array(
					'id' => 'FORMAT_STRING', 'text' => Loc::getMessage('BT_CUR_LANG_ERR_FORMAT_STRING_IS_EMPTY', array('#LANG#' => $language))
				);
			}
			if (isset($fields['DECIMALS']))
			{
				$fields['DECIMALS'] = (int)$fields['DECIMALS'];
				if ($fields['DECIMALS'] < 0)
					$fields['DECIMALS'] = self::$arDefaultValues['DECIMALS'];
			}
			if (isset($fields['THOUSANDS_VARIANT']))
			{
				if (empty($fields['THOUSANDS_VARIANT']) || !isset(self::$arSeparators[$fields['THOUSANDS_VARIANT']]))
				{
					$fields['THOUSANDS_VARIANT'] = false;
				}
				else
				{
					$fields['THOUSANDS_SEP'] = false;
				}
			}
			if (isset($fields['HIDE_ZERO']))
			{
				$fields['HIDE_ZERO'] = ($fields['HIDE_ZERO'] == 'Y' ? 'Y' : 'N');
			}
		}
		$intUserID = 0;
		$boolUserExist = CCurrency::isUserExists();
		if ($boolUserExist)
			$intUserID = (int)$USER->GetID();
		$strDateFunction = $DB->GetNowFunction();
		$fields['~DATE_UPDATE'] = $strDateFunction;
		if ($boolUserExist)
		{
			if (!isset($fields['MODIFIED_BY']))
				$fields['MODIFIED_BY'] = $intUserID;
			$fields['MODIFIED_BY'] = (int)$fields['MODIFIED_BY'];
			if ($fields['MODIFIED_BY'] <= 0)
				$fields['MODIFIED_BY'] = $intUserID;
		}
		if ($action == 'ADD')
		{
			$fields['~DATE_CREATE'] = $strDateFunction;
			if ($boolUserExist)
			{
				if (!isset($arFields['CREATED_BY']))
					$fields['CREATED_BY'] = $intUserID;
				$fields['CREATED_BY'] = (int)$fields['CREATED_BY'];
				if ($fields['CREATED_BY'] <= 0)
					$fields['CREATED_BY'] = $intUserID;
			}
		}

		if (!empty($errorMessages))
		{
			if ($getErrors)
			{
				return $errorMessages;
			}

			$obError = new CAdminException($errorMessages);
			$APPLICATION->ResetException();
			$APPLICATION->ThrowException($obError);
			return false;
		}
		return true;
	}

	public function Add($arFields)
	{
		global $DB;

		if (!self::checkFields('ADD', $arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_catalog_currency_lang", $arFields);

		$strSql = "insert into b_catalog_currency_lang(".$arInsert[0].") values(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		Currency\CurrencyManager::clearCurrencyCache($arFields['LID']);

		return true;
	}

	public function Update($currency, $lang, $arFields)
	{
		global $DB;

		$currency = Currency\CurrencyManager::checkCurrencyID($currency);
		$lang = Currency\CurrencyManager::checkLanguage($lang);
		if ($currency === false || $lang === false)
			return false;

		if (!self::checkFields('UPDATE', $arFields, $currency, $lang))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_catalog_currency_lang", $arFields);
		if (!empty($strUpdate))
		{
			$strSql = "update b_catalog_currency_lang set ".$strUpdate." where CURRENCY = '".$DB->ForSql($currency)."' and LID='".$DB->ForSql($lang)."'";
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			Currency\CurrencyManager::clearCurrencyCache($lang);
		}

		return true;
	}

	public function Delete($currency, $lang)
	{
		global $DB;

		$currency = Currency\CurrencyManager::checkCurrencyID($currency);
		$lang = Currency\CurrencyManager::checkLanguage($lang);
		if ($currency === false || $lang === false)
			return false;

		Currency\CurrencyManager::clearCurrencyCache($lang);

		$strSql = "delete from b_catalog_currency_lang where CURRENCY = '".$DB->ForSql($currency)."' and LID = '".$DB->ForSql($lang)."'";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return true;
	}

	public function GetByID($currency, $lang)
	{
		global $DB;

		$currency = Currency\CurrencyManager::checkCurrencyID($currency);
		$lang = Currency\CurrencyManager::checkLanguage($lang);
		if ($currency === false || $lang === false)
			return false;

		$strSql = "select * from b_catalog_currency_lang where CURRENCY = '".$DB->ForSql($currency)."' and LID = '".$DB->ForSql($lang)."'";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
			return $res;

		return false;
	}

	public function GetCurrencyFormat($currency, $lang = LANGUAGE_ID)
	{
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

	public function GetList(&$by, &$order, $currency = "")
	{
		global $DB;

		$strSql = "select CURL.* from b_catalog_currency_lang CURL ";

		if ('' != $currency)
		{
			$strSql .= "where CURL.CURRENCY = '".$DB->ForSql($currency, 3)."' ";
		}

		if (strtolower($by) == "currency") $strSqlOrder = " order by CURL.CURRENCY ";
		elseif (strtolower($by) == "name") $strSqlOrder = " order by CURL.FULL_NAME ";
		else
		{
			$strSqlOrder = " order BY CURL.LID ";
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

	public static function GetDefaultValues()
	{
		return self::$arDefaultValues;
	}

	public static function GetSeparators()
	{
		return self::$arSeparators;
	}

	public static function GetSeparatorTypes($boolFull = false)
	{
		$boolFull = (true == $boolFull);
		if ($boolFull)
		{
			return array(
				self::SEP_EMPTY => Loc::getMessage('BT_CUR_LANG_SEP_VARIANT_EMPTY'),
				self::SEP_DOT => Loc::getMessage('BT_CUR_LANG_SEP_VARIANT_DOT'),
				self::SEP_COMMA => Loc::getMessage('BT_CUR_LANG_SEP_VARIANT_COMMA'),
				self::SEP_SPACE => Loc::getMessage('BT_CUR_LANG_SEP_VARIANT_SPACE'),
				self::SEP_NBSPACE => Loc::getMessage('BT_CUR_LANG_SEP_VARIANT_NBSPACE')
			);
		}
		return array(
			self::SEP_EMPTY,
			self::SEP_DOT,
			self::SEP_COMMA,
			self::SEP_SPACE,
			self::SEP_NBSPACE
		);
	}

	public static function GetFormatTemplates()
	{
		$installCurrencies = CCurrency::getInstalledCurrencies();
		$templates = array();
		$templates[] = array(
			'TEXT' => '$1.234,10',
			'FORMAT' => '$#',
			'DEC_POINT' => ',',
			'THOUSANDS_VARIANT' => self::SEP_DOT,
			'DECIMALS' => '2'
		);
		$templates[] = array(
			'TEXT' => '$1 234,10',
			'FORMAT' => '$#',
			'DEC_POINT' => ',',
			'THOUSANDS_VARIANT' => self::SEP_SPACE,
			'DECIMALS' => '2'
		);
		$templates[] = array(
			'TEXT' => '1.234,10 USD',
			'FORMAT' => '# USD',
			'DEC_POINT' => ',',
			'THOUSANDS_VARIANT' => self::SEP_DOT,
			'DECIMALS' => '2'
		);
		$templates[] = array(
			'TEXT' => '1 234,10 USD',
			'FORMAT' => '# USD',
			'DEC_POINT' => ',',
			'THOUSANDS_VARIANT' => self::SEP_SPACE,
			'DECIMALS' => '2'
		);
		$templates[] = array(
			'TEXT' => '&euro;2.345,20',
			'FORMAT' => '&euro;#',
			'DEC_POINT' => ',',
			'THOUSANDS_VARIANT' => self::SEP_DOT,
			'DECIMALS' => '2'
		);
		$templates[] = array(
			'TEXT' => '&euro;2 345,20',
			'FORMAT' => '&euro;#',
			'DEC_POINT' => ',',
			'THOUSANDS_VARIANT' => self::SEP_SPACE,
			'DECIMALS' => '2'
		);
		$templates[] = array(
			'TEXT' => '2.345,20 EUR',
			'FORMAT' => '# EUR',
			'DEC_POINT' => ',',
			'THOUSANDS_VARIANT' => self::SEP_DOT,
			'DECIMALS' => '2'
		);
		$templates[] = array(
			'TEXT' => '2 345,20 EUR',
			'FORMAT' => '# EUR',
			'DEC_POINT' => ',',
			'THOUSANDS_VARIANT' => self::SEP_SPACE,
			'DECIMALS' => '2'
		);

		if (in_array('RUB', $installCurrencies))
		{
			$rubTitle = Loc::getMessage('BT_CUR_LANG_CURRENCY_RUBLE');
			$templates[] = array(
				'TEXT' => '3.456,70 '.$rubTitle,
				'FORMAT' => '# '.$rubTitle,
				'DEC_POINT' => ',',
				'THOUSANDS_VARIANT' => self::SEP_DOT,
				'DECIMALS' => '2'
			);
			$templates[] = array(
				'TEXT' => '3 456,70 '.$rubTitle,
				'FORMAT' => '# '.$rubTitle,
				'DEC_POINT' => ',',
				'THOUSANDS_VARIANT' => self::SEP_SPACE,
				'DECIMALS' => '2'
			);
		}
		return $templates;
	}

	public static function GetFormatDescription($currency)
	{
		$boolAdminSection = (defined('ADMIN_SECTION') && ADMIN_SECTION === true);
		$currency = (string)$currency;

		if (!isset(self::$arCurrencyFormat[$currency]))
		{
			$arCurFormat = CCurrencyLang::GetCurrencyFormat($currency);
			if ($arCurFormat === false)
			{
				$arCurFormat = self::$arDefaultValues;
			}
			else
			{
				if (!isset($arCurFormat['DECIMALS']))
					$arCurFormat['DECIMALS'] = self::$arDefaultValues['DECIMALS'];
				$arCurFormat['DECIMALS'] = (int)$arCurFormat['DECIMALS'];
				if (!isset($arCurFormat['DEC_POINT']))
					$arCurFormat['DEC_POINT'] = self::$arDefaultValues['DEC_POINT'];
				if (!empty($arCurFormat['THOUSANDS_VARIANT']) && isset(self::$arSeparators[$arCurFormat['THOUSANDS_VARIANT']]))
				{
					$arCurFormat['THOUSANDS_SEP'] = self::$arSeparators[$arCurFormat['THOUSANDS_VARIANT']];
				}
				elseif (!isset($arCurFormat['THOUSANDS_SEP']))
				{
					$arCurFormat['THOUSANDS_SEP'] = self::$arDefaultValues['THOUSANDS_SEP'];
				}
				if (!isset($arCurFormat['FORMAT_STRING']))
				{
					$arCurFormat['FORMAT_STRING'] = self::$arDefaultValues['FORMAT_STRING'];
				}
				elseif ($boolAdminSection)
				{
					$arCurFormat["FORMAT_STRING"] = strip_tags(preg_replace(
						'#<script[^>]*?>.*?</script[^>]*?>#is',
						'',
						$arCurFormat["FORMAT_STRING"]
					));
				}
				if (!isset($arCurFormat['HIDE_ZERO']) || empty($arCurFormat['HIDE_ZERO']))
					$arCurFormat['HIDE_ZERO'] = self::$arDefaultValues['HIDE_ZERO'];
			}
			self::$arCurrencyFormat[$currency] = $arCurFormat;
		}
		else
		{
			$arCurFormat = self::$arCurrencyFormat[$currency];
		}
		return $arCurFormat;
	}

	public static function CurrencyFormat($price, $currency, $useTemplate = true)
	{
		$result = '';
		$useTemplate = !!$useTemplate;
		if ($useTemplate)
		{
			foreach(GetModuleEvents('currency', 'CurrencyFormat', true) as $arEvent)
			{
				$result = ExecuteModuleEventEx($arEvent, array($price, $currency));
				if ($result != '')
					return $result;
			}
		}

		if (!isset($price) || $price === '')
			return '';

		$currency = Currency\CurrencyManager::checkCurrencyID($currency);
		if ($currency === false)
			return '';

		$arCurFormat = (isset(self::$arCurrencyFormat[$currency]) ? self::$arCurrencyFormat[$currency] : self::GetFormatDescription($currency));
		$intDecimals = $arCurFormat['DECIMALS'];
		if (self::isAllowUseHideZero() && $arCurFormat['HIDE_ZERO'] == 'Y')
		{
			if (round($price, $arCurFormat["DECIMALS"]) == round($price, 0))
				$intDecimals = 0;
		}
		$price = number_format($price, $intDecimals, $arCurFormat['DEC_POINT'], $arCurFormat['THOUSANDS_SEP']);
		if ($arCurFormat['THOUSANDS_VARIANT'] == self::SEP_NBSPACE)
			$price = str_replace(' ', '&nbsp;', $price);

		return (
			$useTemplate
			? str_replace('#', $price, $arCurFormat['FORMAT_STRING'])
			: $price
		);
	}

	public static function checkLanguage($language)
	{
		return Currency\CurrencyManager::checkLanguage($language);
	}

	public static function isExistCurrencyLanguage($currency, $language)
	{
		global $DB;
		$currency = Currency\CurrencyManager::checkCurrencyID($currency);
		$language = Currency\CurrencyManager::checkLanguage($language);
		if ($currency === false || $language === false)
			return false;
		$query = "select LID from b_catalog_currency_lang where CURRENCY = '".$DB->ForSql($currency)."' and LID = '".$DB->ForSql($language)."'";
		$searchIterator = $DB->Query($query, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
		if ($result = $searchIterator->Fetch())
		{
			return true;
		}
		return false;
	}

	protected static function clearFields($value)
	{
		return ($value !== null);
	}
}

class CCurrencyLang extends CAllCurrencyLang
{
}
?>