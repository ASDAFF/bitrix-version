<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/*
	"GROUPS" => array(
		"FILTER_SETTINGS" => array(
			"NAME" => GetMessage("T_IBLOCK_DESC_FILTER_SETTINGS"),
		),
*/

$arComponentParameters = Array(
	"PARAMETERS" => Array(
		"ID" => Array(
			"NAME" => Loc::getMessage("SALE_SLS_ID_PARAMETER"),
			"PARENT" => "BASE",
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
		),
		"CODE" => Array(
			"NAME" => Loc::getMessage("SALE_SLS_CODE_PARAMETER"),
			"PARENT" => "BASE",
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
		),
		"INPUT_NAME" => Array(
			"NAME" => Loc::getMessage("SALE_SLS_INPUT_NAME_PARAMETER"),
			"PARENT" => "BASE",
			"TYPE" => "STRING",
			"DEFAULT" => "LOCATION",
		),
		"PROVIDE_LINK_BY" => Array(
			"NAME" => Loc::getMessage("SALE_SLS_PROVIDE_LINK_BY_PARAMETER"),
			"PARENT" => "BASE",
			"TYPE" => "LIST",
			"VALUES" => array(
				'id' => Loc::getMessage("SALE_SLS_PROVIDE_LINK_BY_PARAMETER_ID"),
				'code' => Loc::getMessage("SALE_SLS_PROVIDE_LINK_BY_PARAMETER_CODE")
			),
			"DEFAULT" => "id"
		),

		"SEARCH_BY_PRIMARY" => Array(
			"NAME" => Loc::getMessage("SALE_SLS_SEARCH_BY_PRIMARY_PARAMETER"),
			"PARENT" => "DATA_SOURCE",
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N"
		),

		"JSCONTROL_GLOBAL_ID" => Array(
			"NAME" => Loc::getMessage("SALE_SLS_JSCONTROL_GLOBAL_ID_PARAMETER"),
			"PARENT" => "ADDITIONAL",
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => ""
		),
		"JS_CALLBACK" => Array(
			"NAME" => Loc::getMessage("SALE_SLS_JS_CALLBACK"),
			"PARENT" => "ADDITIONAL",
			"TYPE" => "STRING"
		),

		"CACHE_TIME"  =>  array("DEFAULT" => 36000000)
	)
);

$arComponentParameters["PARAMETERS"]["FILTER_BY_SITE"] = Array(
	"NAME" => Loc::getMessage("SALE_SLS_FILTER_BY_SITE_PARAMETER"),
	"PARENT" => "DATA_SOURCE",
	"TYPE" => "CHECKBOX",
	"DEFAULT" => "Y",
	"REFRESH" => "Y"
);
$arComponentParameters["PARAMETERS"]["SHOW_DEFAULT_LOCATIONS"] = Array(
	"NAME" => Loc::getMessage("SALE_SLS_SHOW_DEFAULT_LOCATIONS_PARAMETER"),
	"PARENT" => "DATA_SOURCE",
	"TYPE" => "CHECKBOX",
	"DEFAULT" => "Y",
	"REFRESH" => "Y"
);

if($arCurrentValues['FILTER_BY_SITE'] == 'Y' || $arCurrentValues['SHOW_DEFAULT_LOCATIONS'] == 'Y')
{
	$by = '';
	$order = '';
	$res = CSite::GetList($by, $order);
	$sites = array();
	while($item = $res->Fetch())
		$sites[$item['ID']] = '['.$item['ID'].'] '.$item['NAME'];

	$arComponentParameters["PARAMETERS"]["FILTER_SITE_ID"] = Array(
		"NAME" => Loc::getMessage("SALE_SLS_FILTER_SITE_ID_PARAMETER"),
		"PARENT" => "DATA_SOURCE",
		"TYPE" => "LIST",
		"VALUES" => array_merge(array(
			'current' => Loc::getMessage("SALE_SLS_FILTER_BY_SITE_SITE_ID_CURRENT")
		), $sites)
	);
}