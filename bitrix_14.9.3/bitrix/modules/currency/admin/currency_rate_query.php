<?
define("STOP_STATISTICS", true);
define("BX_SECURITY_SHOW_MESSAGE", true);
define('NO_AGENT_CHECK', true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/currency/include.php");
$CURRENCY_RIGHT = $APPLICATION->GetGroupRight("currency");
__IncludeLang(GetLangFileName($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/currency/lang/", "/currencies_rates.php"));

if ($CURRENCY_RIGHT=="D") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$RATE = $RATE_CNT = "";
$strError = "";
$port = 80;

if(!check_bitrix_sessid())
	$strError = GetMessage("ERROR_SESSID");
if ($DATE_RATE == "" || !$DB->IsDate($DATE_RATE) || strlen($CURRENCY) < 0)
	$strError = GetMessage("ERROR_DATE_RATE");

if (strlen($strError) <= 0)
{
	//currency
	$currantCurrancy = CCurrency::GetBaseCurrency();

	//select host
	if ($currantCurrancy == "UAH")
	{//ukraina
		$host = "pfsoft.com.ua";
		$path = "/service/currency/";
		$QUERY_STR = "date=".$DB->FormatDate($DATE_RATE, CLang::GetDateFormat("SHORT", $lang), "DMY");
	}
	elseif ($currantCurrancy == "BYR")
	{//belarus
		$host = "www.nbrb.by";
		$path = "/Services/XmlExRates.aspx";
		$QUERY_STR = "ondate=".$DB->FormatDate($DATE_RATE, CLang::GetDateFormat("SHORT", $lang), "Y-M-D");
	}
	else
	{//all time russia
		$host = "www.cbr.ru";
		$path = "/scripts/XML_daily.asp";
		$QUERY_STR = "date_req=".$DB->FormatDate($DATE_RATE, CLang::GetDateFormat("SHORT", $lang), "D.M.Y");
	}

	$strQueryText = QueryGetData($host, $port, $path, $QUERY_STR, $errno, $errstr);

	if (strlen($strQueryText)<=0)
	{
		if (intval($errno)>0 || strlen($errstr)>0)
			$strError = GetMessage("ERROR_QUERY_RATE");
		else
			$strError = GetMessage("ERROR_EMPTY_ANSWER");
	}
	else
	{
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/xml.php");

		$charset = "windows-1251";
		if (preg_match("/<"."\?XML[^>]{1,}encoding=[\"']([^>\"']{1,})[\"'][^>]{0,}\?".">/i", $strQueryText, $matches))
		{
			$charset = trim($matches[1]);
		}
		$strQueryText = preg_replace("#<!DOCTYPE[^>]+?>#i", "", $strQueryText);
		$strQueryText = preg_replace("#<"."\\?XML[^>]+?\\?".">#i", "", $strQueryText);
		$strQueryText = $APPLICATION->ConvertCharset($strQueryText, $charset, SITE_CHARSET);

		$objXML = new CDataXML();
		$res = $objXML->LoadString($strQueryText);
		if ($res !== false)
			$arData = $objXML->GetArray();
		else
			$arData = false;

		if ($currantCurrancy == "UAH")
		{//ukraina
			if (is_array($arData) && count($arData["ValCurs"]["#"]["Valute"])>0)
			{
				for ($j1 = 0, $intCount = count($arData["ValCurs"]["#"]["Valute"]); $j1 < $intCount; $j1++)
				{
					if ($arData["ValCurs"]["#"]["Valute"][$j1]["#"]["CharCode"][0]["#"] == $CURRENCY)
					{
						$RATE_CNT = IntVal($arData["ValCurs"]["#"]["Valute"][$j1]["#"]["Nominal"][0]["#"]);
						$arCurrValue = str_replace(",", ".", $arData["ValCurs"]["#"]["Valute"][$j1]["#"]["Value"][0]["#"]);
						$RATE = DoubleVal($arCurrValue);
						break;
					}
				}
			}
		}
		elseif ($currantCurrancy == "BYR")
		{//belarus
			if (is_array($arData) && count($arData["DailyExRates"]["#"]["Currency"])>0)
			{
				for ($j1 = 0, $intCount = count($arData["DailyExRates"]["#"]["Currency"]); $j1 < $intCount; $j1++)
				{
					if ($arData["DailyExRates"]["#"]["Currency"][$j1]["#"]["CharCode"][0]["#"] == $CURRENCY)
					{
						$RATE_CNT = IntVal($arData["DailyExRates"]["#"]["Currency"][$j1]["#"]["Scale"][0]["#"]);
						$arCurrValue = str_replace(",", ".", $arData["DailyExRates"]["#"]["Currency"][$j1]["#"]["Rate"][0]["#"]);
						$RATE = DoubleVal($arCurrValue);
						break;
					}
				}
			}
		}
		else
		{//russia
			if (is_array($arData) && count($arData["ValCurs"]["#"]["Valute"])>0)
			{
				for ($j1 = 0, $intCount = count($arData["ValCurs"]["#"]["Valute"]); $j1 < $intCount; $j1++)
				{
					if ($arData["ValCurs"]["#"]["Valute"][$j1]["#"]["CharCode"][0]["#"] == $CURRENCY)
					{
						$RATE_CNT = IntVal($arData["ValCurs"]["#"]["Valute"][$j1]["#"]["Nominal"][0]["#"]);
						$arCurrValue = str_replace(",", ".", $arData["ValCurs"]["#"]["Valute"][$j1]["#"]["Value"][0]["#"]);
						$RATE = DoubleVal($arCurrValue);
						break;
					}
				}
			}
		}
	}
}

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");

if (strlen($strError) > 0)
{
	?>BX('cyrrency_query_error_div').innerHTML = '<span class="required"><?=$strError;?></span>';<?
}
else
{
	?>document.forms.form1.RATE.value = '<?=$RATE?>';
document.forms.form1.RATE_CNT.value = '<?=$RATE_CNT?>';
BX.fireEvent(document.forms.form1.RATE, 'change');
BX.fireEvent(document.forms.form1.RATE_CNT, 'change');<?
}

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin_js.php");?>