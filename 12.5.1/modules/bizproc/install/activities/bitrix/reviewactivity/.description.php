<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	"NAME" => GetMessage("BPAR_DESCR_NAME"),
	"DESCRIPTION" => GetMessage("BPAR_DESCR_DESCR"),
	"TYPE" => "activity",
	"CLASS" => "ReviewActivity",
	"JSCLASS" => "BizProcActivity",
	"CATEGORY" => array(
		"ID" => "document",
	),
	"RETURN" => array(
		"Comments" => array(
			"NAME" => GetMessage("BPAA_DESCR_CM"),
			"TYPE" => "string",
		),
		"ReviewedCount" => array(
			"NAME" => GetMessage("BPAR_DESCR_RC"),
			"TYPE" => "int",
		),
		"TotalCount" => array(
			"NAME" => GetMessage("BPAR_DESCR_TC"),
			"TYPE" => "int",
		),
		"IsTimeout" => array(
			"NAME" => GetMessage("BPAR_DESCR_TA1"),
			"TYPE" => "int",
		),
	),
);
?>