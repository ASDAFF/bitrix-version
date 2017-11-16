<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();?>
<?
$APPLICATION->IncludeComponent(
	'bitrix:socialnetwork.group_menu',
	'',
	Array(
		'GROUP_VAR' => $arResult['ALIASES']['group_id'],
		'PAGE_VAR' => $arResult['ALIASES']['page'],
		'PATH_TO_GROUP' => $arResult['PATH_TO_GROUP'],
		'PATH_TO_GROUP_MODS' => $arResult['PATH_TO_GROUP_MODS'],
		'PATH_TO_GROUP_USERS' => $arResult['PATH_TO_GROUP_USERS'],
		'PATH_TO_GROUP_EDIT' => $arResult['PATH_TO_GROUP_EDIT'],
		'PATH_TO_GROUP_REQUEST_SEARCH' => $arResult['PATH_TO_GROUP_REQUEST_SEARCH'],
		'PATH_TO_GROUP_REQUESTS' => $arResult['PATH_TO_GROUP_REQUESTS'],
		'PATH_TO_GROUP_BAN' => $arResult['PATH_TO_GROUP_BAN'],
		'PATH_TO_GROUP_BLOG' => $arResult['PATH_TO_GROUP_BLOG'],
		'PATH_TO_GROUP_MICROBLOG' => $arResult['PATH_TO_GROUP_MICROBLOG'],
		'PATH_TO_GROUP_PHOTO' => $arResult['PATH_TO_GROUP_PHOTO'],
		'PATH_TO_GROUP_FORUM' => $arResult['PATH_TO_GROUP_FORUM'],
		'PATH_TO_GROUP_CALENDAR' => $arResult['PATH_TO_GROUP_CALENDAR'],
		'PATH_TO_GROUP_FILES' => $arResult['PATH_TO_GROUP_FILES'],
		'PATH_TO_GROUP_TASKS' => $arResult['PATH_TO_GROUP_TASKS'],
		'PATH_TO_GROUP_CONTENT_SEARCH' => $arResult['PATH_TO_GROUP_CONTENT_SEARCH'],
		'GROUP_ID' => $arResult['VARIABLES']['group_id'],
		'PAGE_ID' => 'group_wiki',
		'USE_MAIN_MENU' => $arParams['USE_MAIN_MENU'],
		'MAIN_MENU_TYPE' => $arParams['MAIN_MENU_TYPE'],
		'FILES_GROUP_IBLOCK_ID' => $arParams['FILES_GROUP_IBLOCK_ID']
	),
	$component
);
?>
<?$APPLICATION->IncludeComponent(
	'bitrix:socialnetwork.group',
	'short',
	Array(
		'PATH_TO_USER' => $arResult['PATH_TO_USER'],
		'PATH_TO_GROUP' => $arResult['PATH_TO_GROUP'],
		'PATH_TO_GROUP_EDIT' => $arResult['PATH_TO_GROUP_EDIT'],
		'PATH_TO_GROUP_CREATE' => $arResult['PATH_TO_GROUP_CREATE'],
		'PATH_TO_GROUP_REQUEST_SEARCH' => $arResult['PATH_TO_GROUP_REQUEST_SEARCH'],
		'PATH_TO_USER_REQUEST_GROUP' => $arResult['PATH_TO_USER_REQUEST_GROUP'],
		'PATH_TO_GROUP_REQUESTS' => $arResult['PATH_TO_GROUP_REQUESTS'],
		'PATH_TO_GROUP_MODS' => $arResult['PATH_TO_GROUP_MODS'],
		'PATH_TO_GROUP_USERS' => $arResult['PATH_TO_GROUP_USERS'],
		'PATH_TO_USER_LEAVE_GROUP' => $arResult['PATH_TO_USER_LEAVE_GROUP'],
		'PATH_TO_GROUP_DELETE' => $arResult['PATH_TO_GROUP_DELETE'],
		'PATH_TO_GROUP_FEATURES' => $arResult['PATH_TO_GROUP_FEATURES'],
		'PATH_TO_SEARCH' => $arResult['PATH_TO_SEARCH'],
		'PATH_TO_GROUP_BAN' => $arResult['PATH_TO_GROUP_BAN'],
		'PATH_TO_MESSAGE_TO_GROUP' => $arResult['PATH_TO_MESSAGE_TO_GROUP'],
		'PAGE_VAR' => $arResult['ALIASES']['page'],
		'USER_VAR' => $arResult['ALIASES']['user_id'],
		'GROUP_VAR' => $arResult['ALIASES']['group_id'],
		'SET_NAV_CHAIN' => 'N',
		'SET_TITLE' => 'N',
		'SHORT_FORM' => 'Y',
		'USER_ID' => $arResult['VARIABLES']['user_id'],
		'GROUP_ID' => $arResult['VARIABLES']['group_id'],
		'ITEMS_COUNT' => $arParams['ITEM_MAIN_COUNT'],
	),
	$component
);
?>
<?$APPLICATION->IncludeComponent(
	'bitrix:wiki.menu',
	'',
	Array(
		'IBLOCK_TYPE' => COption::GetOptionString('wiki', 'socnet_iblock_type_id'),
		'IBLOCK_ID' => COption::GetOptionString('wiki', 'socnet_iblock_id'),
		'ELEMENT_NAME' => isset($arResult['VARIABLES']['title']) ? $arResult['VARIABLES']['title'] : $arResult['VARIABLES']['wiki_name'],
		'MENU_TYPE' => 'page',
		'PATH_TO_POST' => $arResult['PATH_TO_GROUP_WIKI_POST'],
		'PATH_TO_POST_EDIT' => $arResult['PATH_TO_GROUP_WIKI_POST_EDIT'],
		'PATH_TO_CATEGORIES' => $arResult['PATH_TO_GROUP_WIKI_CATEGORIES'],
		'PATH_TO_DISCUSSION' => $arResult['PATH_TO_GROUP_WIKI_POST_DISCUSSION'],
		'PATH_TO_HISTORY' => $arResult['PATH_TO_GROUP_WIKI_POST_HISTORY'],
		'PATH_TO_HISTORY_DIFF' => $arResult['PATH_TO_GROUP_WIKI_POST_HISTORY_DIFF'],
		'PAGE_VAR' => 'title',
		'OPER_VAR' => 'oper',
		'USE_REVIEW' => COption::GetOptionString('wiki', 'socnet_use_review'),
		'SOCNET_GROUP_ID' => $arResult['VARIABLES']['group_id']
	),
	$component
);?>
<?$APPLICATION->IncludeComponent(
	'bitrix:wiki.history.diff',
	'',
	Array(
		'PATH_TO_POST' => $arResult['PATH_TO_GROUP_WIKI_POST'],
		'PATH_TO_POST_EDIT' => $arResult['PATH_TO_GROUP_WIKI_POST_EDIT'],
		'PATH_TO_CATEGORIES' => $arResult['PATH_TO_GROUP_WIKI_CATEGORIES'],
		'PATH_TO_DISCUSSION' => $arResult['PATH_TO_GROUP_WIKI_POST_DISCUSSION'],
		'PATH_TO_HISTORY' => $arResult['PATH_TO_GROUP_WIKI_POST_HISTORY'],
		'PATH_TO_HISTORY_DIFF' => $arResult['PATH_TO_GROUP_WIKI_POST_HISTORY_DIFF'],
		'PAGE_VAR' => 'title',
		'OPER_VAR' => 'oper',
		'IBLOCK_TYPE' => COption::GetOptionString('wiki', 'socnet_iblock_type_id'),
		'IBLOCK_ID' => COption::GetOptionString('wiki', 'socnet_iblock_id'),
		'ELEMENT_NAME' => isset($arResult['VARIABLES']['title']) ? $arResult['VARIABLES']['title'] : $arResult['VARIABLES']['wiki_name'],
		'SOCNET_GROUP_ID' => $arResult['VARIABLES']['group_id']	,
		'CACHE_TYPE' => $arResult['CACHE_TYPE'],
		'CACHE_TIME' => $arResult['CACHE_TIME'],
		'NAME_TEMPLATE' => $arResult['NAME_TEMPLATE']
	),
	$component
);?>