<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	"NAME" => GetMessage("BPMA_DESCR_NAME"),
	"DESCRIPTION" => GetMessage("BPMA_DESCR_DESCR"),
	"TYPE" => "activity",
	"CLASS" => "MailActivity",
	"JSCLASS" => "BizProcActivity",
	"CATEGORY" => array(
		"ID" => "interaction",
	),
);
?>
