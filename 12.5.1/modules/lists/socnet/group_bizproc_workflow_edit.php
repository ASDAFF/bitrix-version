<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?><?
$APPLICATION->IncludeComponent(
	"bitrix:socialnetwork.group_menu",
	"",
	Array(
		"GROUP_VAR" => $arResult["ALIASES"]["group_id"],
		"PAGE_VAR" => $arResult["ALIASES"]["page"],
		"PATH_TO_GROUP" => $arResult["PATH_TO_GROUP"],
		"PATH_TO_GROUP_MODS" => $arResult["PATH_TO_GROUP_MODS"],
		"PATH_TO_GROUP_USERS" => $arResult["PATH_TO_GROUP_USERS"],
		"PATH_TO_GROUP_EDIT" => $arResult["PATH_TO_GROUP_EDIT"],
		"PATH_TO_GROUP_REQUEST_SEARCH" => $arResult["PATH_TO_GROUP_REQUEST_SEARCH"],
		"PATH_TO_GROUP_REQUESTS" => $arResult["PATH_TO_GROUP_REQUESTS"],
		"PATH_TO_GROUP_REQUESTS_OUT" => $arResult["PATH_TO_GROUP_REQUESTS_OUT"],
		"PATH_TO_GROUP_BAN" => $arResult["PATH_TO_GROUP_BAN"],
		"PATH_TO_GROUP_BLOG" => $arResult["PATH_TO_GROUP_BLOG"],
		"PATH_TO_GROUP_MICROBLOG" => $arResult["PATH_TO_GROUP_MICROBLOG"],
		"PATH_TO_GROUP_PHOTO" => $arResult["PATH_TO_GROUP_PHOTO"],
		"PATH_TO_GROUP_FORUM" => $arResult["PATH_TO_GROUP_FORUM"],
		"PATH_TO_GROUP_CALENDAR" => $arResult["PATH_TO_GROUP_CALENDAR"],
		"PATH_TO_GROUP_FILES" => $arResult["PATH_TO_GROUP_FILES"],
		"PATH_TO_GROUP_TASKS" => $arResult["PATH_TO_GROUP_TASKS"],
		"PATH_TO_GROUP_CONTENT_SEARCH" => $arResult["PATH_TO_GROUP_CONTENT_SEARCH"],
		"FILES_GROUP_IBLOCK_ID" => $arParams["FILES_GROUP_IBLOCK_ID"],
		"GROUP_ID" => $arResult["VARIABLES"]["group_id"],
		"PAGE_ID" => "group_group_lists",
		"USE_MAIN_MENU" => $arParams["USE_MAIN_MENU"],
		"MAIN_MENU_TYPE" => $arParams["MAIN_MENU_TYPE"],
	),
	$component
);
?>
<?
$APPLICATION->IncludeComponent(
	"bitrix:socialnetwork.group",
	"short",
	Array(
		"PATH_TO_USER" => $arParams["PATH_TO_USER"],
		"PATH_TO_GROUP" => $arResult["PATH_TO_GROUP"],
		"PATH_TO_GROUP_EDIT" => $arResult["PATH_TO_GROUP_EDIT"],
		"PATH_TO_GROUP_CREATE" => $arResult["PATH_TO_GROUP_CREATE"],
		"PATH_TO_GROUP_REQUEST_SEARCH" => $arResult["PATH_TO_GROUP_REQUEST_SEARCH"],
		"PATH_TO_USER_REQUEST_GROUP" => $arResult["PATH_TO_USER_REQUEST_GROUP"],
		"PATH_TO_GROUP_REQUESTS" => $arResult["PATH_TO_GROUP_REQUESTS"],
		"PATH_TO_GROUP_MODS" => $arResult["PATH_TO_GROUP_MODS"],
		"PATH_TO_GROUP_USERS" => $arResult["PATH_TO_GROUP_USERS"],
		"PATH_TO_USER_LEAVE_GROUP" => $arResult["PATH_TO_USER_LEAVE_GROUP"],
		"PATH_TO_GROUP_DELETE" => $arResult["PATH_TO_GROUP_DELETE"],
		"PATH_TO_GROUP_FEATURES" => $arResult["PATH_TO_GROUP_FEATURES"],
		"PATH_TO_SEARCH" => $arResult["PATH_TO_SEARCH"],
		"PATH_TO_GROUP_BAN" => $arResult["PATH_TO_GROUP_BAN"],
		"PATH_TO_MESSAGE_TO_GROUP" => $arResult["PATH_TO_MESSAGE_TO_GROUP"],
		"PAGE_VAR" => $arResult["ALIASES"]["page"],
		"USER_VAR" => $arResult["ALIASES"]["user_id"],
		"GROUP_VAR" => $arResult["ALIASES"]["group_id"],
		"SET_NAV_CHAIN" => "N",
		"SET_TITLE" => "N",
		"SHORT_FORM" => "Y",
		"USER_ID" => $arResult["VARIABLES"]["user_id"],
		"GROUP_ID" => $arResult["VARIABLES"]["group_id"],
		"ITEMS_COUNT" => $arParams["ITEM_MAIN_COUNT"],
	),
	$component
);
?>
<?$APPLICATION->IncludeComponent("bitrix:lists.element.navchain", ".default", array(
	"IBLOCK_TYPE_ID" => COption::GetOptionString("lists", "socnet_iblock_type_id"),
	"SOCNET_GROUP_ID" => $arResult["VARIABLES"]["group_id"],
	"PATH_TO_GROUP" => $arResult["PATH_TO_GROUP"],

	"ADD_NAVCHAIN_GROUP" => "Y",
	"LISTS_URL" => $arResult["PATH_TO_GROUP_LISTS"],

	"IBLOCK_ID" => $arResult["VARIABLES"]["list_id"],
	"ADD_NAVCHAIN_LIST" => "Y",
	"LIST_URL" => $arResult["PATH_TO_GROUP_LIST_VIEW"],

	"ADD_NAVCHAIN_SECTIONS" => "N",
	"ADD_NAVCHAIN_ELEMENT" => "N",
	),
	$component
);?><?$APPLICATION->IncludeComponent("bitrix:bizproc.workflow.edit", ".default", array(
	"MODULE_ID" => "iblock",
	"ENTITY" => "CIBlockDocument",
	"DOCUMENT_TYPE" => "iblock_".$arResult["VARIABLES"]["list_id"],
	"ID" => $arResult['VARIABLES']['ID'],
	"EDIT_PAGE_TEMPLATE" => str_replace(
				array("#list_id#", "#group_id#"),
				array($arResult["VARIABLES"]["list_id"], $arResult["VARIABLES"]["group_id"]),
				$arResult["PATH_TO_GROUP_BIZPROC_WORKFLOW_EDIT"]
			),
	"LIST_PAGE_URL" => str_replace(
				array("#list_id#", "#group_id#"),
				array($arResult["VARIABLES"]["list_id"], $arResult["VARIABLES"]["group_id"]),
				$arResult["PATH_TO_GROUP_BIZPROC_WORKFLOW_ADMIN"]
			),
	"SHOW_TOOLBAR" => "Y",
	"SET_TITLE" => "Y",
	),
	$component,
	array("HIDE_ICONS" => "Y")
);?>