<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(!CModule::IncludeModule("sale"))
	return;

$arComponentParameters = array(

	"PARAMETERS" => array(
		"BY" => Array(
			"NAME" => GetMessage("SBP_SHOW"),
			"TYPE" => "LIST",
			"MULTIPLE" => "Y",
			"VALUES" => Array("AMOUNT" => GetMessage("SBP_AMOUNT"), "QUANTITY" => GetMessage("SBP_QUANTITY")),
		),
		
		"PERIOD" => Array(
			"NAME" => GetMessage("SBP_PERIOD"),
			"TYPE" => "LIST",
			"MULTIPLE" => "Y",
			"VALUES" => Array(
					"15" => "15 ".GetMessage("SBP_DAYS"), 
					"30" => "30 ".GetMessage("SBP_DAYS"), 
					"60" => "60 ".GetMessage("SBP_DAYS"), 
					"90" => "90 ".GetMessage("SBP_DAYS"), 
					"180" => "180 ".GetMessage("SBP_DAYS"), 
				),
			"ADDITIONAL_VALUES" => "Y",
		),
		
		"FILTER_NAME" => array(
			"NAME" => GetMessage("SBP_FILTER_NAME"),
			"TYPE" => "STRING",
			"DEFAULT" => "arFilter",
		),
		"ORDER_FILTER_NAME" => array(
			"NAME" => GetMessage("SBP_ORDER_FILTER_NAME"),
			"TYPE" => "STRING",
			"DEFAULT" => "arOrderFilter",
		),
		"ITEM_COUNT" => array(
			"NAME" => GetMessage("SBP_ITEM_COUNT"),
			"TYPE" => "STRING",
			"DEFAULT" => "10",
		),
		"DETAIL_URL" => array(
			"NAME" => GetMessage("SBP_DETAIL_URL"),
			"TYPE" => "STRING",
			"DEFAULT" => "",
		),

		"CACHE_TIME" => array("DEFAULT"=>86400),
		"AJAX_MODE" => array(),
	),
);
?>
