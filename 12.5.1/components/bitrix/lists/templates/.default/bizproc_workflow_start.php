<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?><?$APPLICATION->IncludeComponent("bitrix:lists.element.navchain", ".default", array(
	"IBLOCK_TYPE_ID" => $arParams["IBLOCK_TYPE_ID"],
	"IBLOCK_ID" => $arResult["VARIABLES"]["list_id"],
	"SECTION_ID" => $arResult["VARIABLES"]["section_id"],
	"ELEMENT_ID" => $arResult["VARIABLES"]["element_id"],
	"LISTS_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["lists"],
	"LIST_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["list"],
	"LIST_ELEMENT_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["list_element_edit"],
	"CACHE_TYPE" => $arParams["CACHE_TYPE"],
	"CACHE_TIME" => $arParams["CACHE_TIME"],
	),
	$component
);?><?$APPLICATION->IncludeComponent("bitrix:bizproc.workflow.start", ".default", array(
	"MODULE_ID" => "iblock",
	"ENTITY" => "CIBlockDocument",
	"DOCUMENT_TYPE" => "iblock_".$arResult["VARIABLES"]["list_id"],
	"DOCUMENT_ID" => $arResult["VARIABLES"]["element_id"],
	),
	$component
);?>