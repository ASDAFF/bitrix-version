<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	"NAME" => GetMessage("BPRIA_DESCR_NAME"),
	"DESCRIPTION" => GetMessage("BPRIA_DESCR_DESCR"),
	"TYPE" => "activity",
	"CLASS" => "RequestInformationActivity",
	"JSCLASS" => "BizProcActivity",
	"CATEGORY" => array(
		"ID" => "document",
	),
	"RETURN" => array(
		"Comments" => array(
			"NAME" => GetMessage("BPAA_DESCR_CM"),
			"TYPE" => "string",
		),
		"InfoUser" => array(
			"NAME" => GetMessage("BPAA_DESCR_LU"),
			"TYPE" => "user",
		),
	),
);
?>