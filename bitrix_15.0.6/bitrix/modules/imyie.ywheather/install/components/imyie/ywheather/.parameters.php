<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/imyie/ywheather/data/ru/data.php");

$arCoutries = array();
$arCities = array();

$arCoutries[""] = GetMessage("IMYIE_PARAM_NULL");
foreach($DATA as $tname => $name)
{
	$arCoutries[$tname] = $name["COUNTRY"];
}

if(isset($arCurrentValues["COUNTRY"]) && strlen($arCurrentValues["COUNTRY"])>0)
{
	$cntr = $arCurrentValues["COUNTRY"];
	foreach($DATA[$cntr]["CITIES"] as $ID => $City)
	{
		$arCities[$ID] = $City;
	}
} else {
	$arCities[] = GetMessage("IMYIE_PARAM_NULL");
}
if(count($arCities)<1)
{
	$arCities[] = GetMessage("IMYIE_PARAM_NULL");
}

$arLangCharset = array(
	"AUTO" => GetMessage("IMYIE_PARAM_LANG_CHARSET1"),
	"UTF-8" => GetMessage("IMYIE_PARAM_LANG_CHARSET2"),
	"WINDOWS-1251" => GetMessage("IMYIE_PARAM_LANG_CHARSET3"),
);

$arComponentParameters = array(
	"GROUPS" => array(),
	"PARAMETERS" => array(
		"LANG_CHARSET" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("IMYIE_PARAM_LANG_CHARSET"),
			"TYPE" => "LIST",
			"VALUES" => $arLangCharset,
			"REFRESH" => "N",
		),
		"COUNTRY" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("IMYIE_PARAM_COUNTRY"),
			"TYPE" => "LIST",
			"VALUES" => $arCoutries,
			"REFRESH" => "Y",
		),
		"CITY" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("IMYIE_PARAM_CITY"),
			"TYPE" => "LIST",
			"VALUES" => $arCities,
			"REFRESH" => "N",
		),
		"CACHE_TIME"  => array(
			"DEFAULT" => 3600
		),
	),
);

?>