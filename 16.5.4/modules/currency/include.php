<?
use Bitrix\Main\Loader;

global $DB;
$strDBType = strtolower($DB->type);

Loader::registerAutoLoadClasses(
	'currency',
	array(
		'CCurrency' => 'general/currency.php',
		'CCurrencyLang' => 'general/currency_lang.php',
		'CCurrencyRates' => $strDBType.'/currency_rate.php',
		'\Bitrix\Currency\Compatible\Tools' => 'lib/compatible/tools.php',
		'\Bitrix\Currency\Helpers\Admin\Tools' => 'lib/helpers/admin/tools.php',
		'\Bitrix\Currency\Price\Rounding' => 'lib/price/rounding.php',
		'\Bitrix\Currency\CurrencyManager' => 'lib/currencymanager.php',
		'\Bitrix\Currency\CurrencyTable' => 'lib/currency.php',
		'\Bitrix\Currency\CurrencyLangTable' => 'lib/currencylang.php',
		'\Bitrix\Currency\CurrencyRateTable' => 'lib/currencyrate.php'
	)
);
unset($strDBType);

CJSCore::RegisterExt(
	'currency',
	array(
		'js' => '/bitrix/js/currency/core_currency.js',
		'rel' => array('core')
	)
);

define('CURRENCY_CACHE_DEFAULT_TIME', 10800);
define('CURRENCY_ISO_STANDART_URL', 'http://www.iso.org/iso/home/standards/currency_codes.htm');

/*
* @deprecated deprecated since currency 14.0.0
* @see CCurrencyLang::CurrencyFormat()
*/
function CurrencyFormat($price, $currency)
{
	return CCurrencyLang::CurrencyFormat($price, $currency, true);
}

/*
* @deprecated deprecated since currency 14.0.0
* @see CCurrencyLang::CurrencyFormat()
*/
function CurrencyFormatNumber($price, $currency)
{
	return CCurrencyLang::CurrencyFormat($price, $currency, false);
}