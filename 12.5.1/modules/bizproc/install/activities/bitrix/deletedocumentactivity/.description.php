<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	"NAME" => GetMessage("BPDDA_DESCR_NAME"),
	"DESCRIPTION" => GetMessage("BPDDA_DESCR_DESCR"),
	"TYPE" => "activity",
	"CLASS" => "DeleteDocumentActivity",
	"JSCLASS" => "BizProcActivity",
	"CATEGORY" => array(
		"ID" => "document",
	),
);
?>
