<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("SRP_DEFAULT_TEMPLATE_NAME"),
	"DESCRIPTION" => GetMessage("SRP_DEFAULT_TEMPLATE_DESCRIPTION"),
	"ICON" => "/images/sale_rec.gif",
	"PATH" => array(
		"ID" => "content",
		"CHILD" => array(
			"ID" => "catalog",
			"NAME" => GetMessage("T_IBLOCK_DESC_CATALOG"),
			"SORT" => 350,
			"CHILD" => array(
				"ID" => "sale_recommended_product",
			),
		),
	),
);
?>