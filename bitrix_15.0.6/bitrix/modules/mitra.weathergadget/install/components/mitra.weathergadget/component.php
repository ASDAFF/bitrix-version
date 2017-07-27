<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if ($arParams["WEATHER_YANDEX_CACHE_TYPE"] == "Y" || ($arParams["WEATHER_YANDEX_CACHE_TYPE"] == "A" && COption::GetOptionString("main", "component_cache_on", "Y") == "Y")) {
    $arParams["WEATHER_YANDEX_CACHE_TIME"] = intval($arParams["WEATHER_YANDEX_CACHE_TIME"]);
} else {
    $arParams["WEATHER_YANDEX_CACHE_TIME"] = 0;
}

$url = 'region='.substr($arParams["WEATHER_YANDEX_CITY"], 1).'&ts='.mktime();

if($this->StartResultCache($arParams["WEATHER_YANDEX_CACHE_TIME"], array($arParams["WEATHER_YANDEX_CITY"])))
{
    $ob = new CHTTP();
    $ob->http_timeout = 10;
    $ob->Query(
        "GET",
        "export.yandex.ru",
        80,
        "/bar/reginfo.xml?".$url,
        false,
        "",
        "N"
        );

    $errno = $ob->errno;
    $errstr = $ob->errstr;

    $res = $ob->result;

    $res = str_replace("\xE2\x88\x92", "-", $res);

    require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/classes/general/xml.php');

    $xml = new CDataXML();
    $xml->LoadString($APPLICATION->ConvertCharset($res, 'UTF-8', SITE_CHARSET));

    $arResult['RESULT'] = $xml;
    $arResult['ERRNO'] = $errno;
    $arResult['ERRSTR'] = $errstr;

    $this->IncludeComponentTemplate();
}