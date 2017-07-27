<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arCity = array();
include(dirname(__FILE__).'/city.php');

$arComponentParameters = array(
	"GROUPS" => array(
		"CACHE_SETTINGS" => array(
			"NAME" => GetMessage("COMP_GROUP_CACHE_SETTINGS"),
			"SORT" => 600
		),
	),
	"PARAMETERS" => array(

		"WEATHER_YANDEX_CITY" => Array(
			"NAME"=>GetMessage("WEATHER_YANDEX_CITY"),
			"TYPE" => "LIST",
			"DEFAULT"=>'c213',
			"VALUES" => $arCity,
			"ADDITIONAL_VALUES"	=> "Y",
			"PARENT" => "BASE",
			"COLS" => 45
		),

		"WEATHER_YANDEX_CACHE_TYPE" => array(
			"PARENT" => "CACHE_SETTINGS",
			"NAME" => GetMessage("COMP_PROP_CACHE_TYPE"),
			"TYPE" => "LIST",
			"VALUES" => array(
				"A" => GetMessage("COMP_PROP_CACHE_TYPE_AUTO"),
				"Y" => GetMessage("COMP_PROP_CACHE_TYPE_YES"),
				"N" => GetMessage("COMP_PROP_CACHE_TYPE_NO"),
			),
			"DEFAULT" => "N",
			"ADDITIONAL_VALUES" => "N",
		),

		"WEATHER_YANDEX_CACHE_TIME" => array(
			"PARENT" => "CACHE_SETTINGS",
			"NAME" => GetMessage("COMP_PROP_CACHE_TIME"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => 3600,
			"COLS" => 5,
		),
	)
);