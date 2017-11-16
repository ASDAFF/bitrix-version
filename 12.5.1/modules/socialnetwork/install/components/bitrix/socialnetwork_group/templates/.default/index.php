<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
if (CModule::IncludeModule('intranet'))
	$GLOBALS['INTRANET_TOOLBAR']->Show();

$APPLICATION->IncludeComponent(
	"bitrix:socialnetwork.user_groups", 
	"", 
	Array(
		"THUMBNAIL_SIZE" => $arParams["GROUP_THUMBNAIL_SIZE"],
		"PATH_TO_USER" => $arParams["PATH_TO_USER"],
		"PATH_TO_GROUP" => $arResult["PATH_TO_GROUP"],
		"PATH_TO_GROUP_EDIT" => $arResult["PATH_TO_GROUP_EDIT"],
		"PATH_TO_GROUP_CREATE" => $arParams["PATH_TO_GROUP_CREATE"],
		"USER_VAR" => $arResult["ALIASES"]["user_id"],
		"USER_ID" => $arResult["VARIABLES"]["user_id"],
		"SET_NAV_CHAIN" => $arResult["SET_NAV_CHAIN"],
		"SET_TITLE" => $arResult["SET_TITLE"],
		"COLUMNS_COUNT" => 3,
		"CACHE_TYPE" => $arParams["CACHE_TYPE"],
		"CACHE_TIME" => $arParams["CACHE_TIME"],
		"ITEMS_COUNT" => $arParams["ITEM_DETAIL_COUNT"],
		"PAGE" => "groups_list",
		"PATH_TO_LOG" => $arResult["PATH_TO_LOG"],
		"FONT_MAX" => $arParams["SEARCH_TAGS_FONT_MAX"],
		"FONT_MIN" => $arParams["SEARCH_TAGS_FONT_MIN"],
		"COLOR_NEW" => $arParams["SEARCH_TAGS_COLOR_NEW"],
		"COLOR_OLD" => $arParams["SEARCH_TAGS_COLOR_OLD"],
		"PERIOD" => $arParams["SEARCH_TAGS_PERIOD"],
		"ANGULARITY" => "0",
		"COLOR_TYPE" => "Y",
		"WIDTH" => "100%",
		"USE_KEYWORDS" => $arParams["GROUP_USE_KEYWORDS"],
	),
	$component 
);
?>