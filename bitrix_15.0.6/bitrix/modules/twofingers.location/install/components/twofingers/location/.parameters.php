<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$arComponentParameters = array(
	"GROUPS" => array(
		"SOURCE" => array(
			"NAME" => GetMessage("DATA_SOURCE"),
			"SORT" => 10,
		)
	)
);
$params = array();
$params['CACHE_TIME'] = array("DEFAULT"=>3600);
$params['SET_TITLE'] = array("DEFAULT" => "N");
$arComponentParameters['PARAMETERS'] = $params;
?>