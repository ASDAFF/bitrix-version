<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?><?$APPLICATION->IncludeComponent("bitrix:lists.element.navchain", ".default", array(
	"IBLOCK_TYPE_ID" => $arParams["IBLOCK_TYPE_ID"],
	"IBLOCK_ID" => $arResult["VARIABLES"]["list_id"],
	"LISTS_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["lists"],
	"LIST_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["list"],
	"ADD_NAVCHAIN_SECTIONS" => "N",
	"ADD_NAVCHAIN_ELEMENT" => "N",
	"CACHE_TYPE" => $arParams["CACHE_TYPE"],
	"CACHE_TIME" => $arParams["CACHE_TIME"],
	),
	$component,
	array("HIDE_ICONS" => "Y")
);?><?$APPLICATION->IncludeComponent("bitrix:bizproc.workflow.setvar", ".default", array(
	"MODULE_ID" => "iblock",
	"ENTITY" => "CIBlockDocument",
	"DOCUMENT_TYPE" => "iblock_".$arResult["VARIABLES"]["list_id"],
	"ID" => $arResult['VARIABLES']['ID'],
	"EDIT_PAGE_TEMPLATE" => str_replace(
				array("#list_id#"),
				array($arResult["VARIABLES"]["list_id"]),
				$arResult["FOLDER"].$arResult["URL_TEMPLATES"]["bizproc_workflow_vars"]
			),
	"LIST_PAGE_URL" => str_replace(
				array("#list_id#"),
				array($arResult["VARIABLES"]["list_id"]),
				$arResult["FOLDER"].$arResult["URL_TEMPLATES"]["bizproc_workflow_admin"]
			),
	"SHOW_TOOLBAR" => "Y",
	"SET_TITLE" => "Y",
	),
	$component,
	array("HIDE_ICONS" => "Y")
);?>