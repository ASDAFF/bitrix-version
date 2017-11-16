<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/socialnetwork.log.ex/include.php");

CPageOption::SetOptionString("main", "nav_page_in_session", "N");

if (!CModule::IncludeModule("socialnetwork"))
{
	ShowError(GetMessage("SONET_MODULE_NOT_INSTALL"));
	return;
}

if (!array_key_exists("CHECK_PERMISSIONS_DEST", $arParams) || strLen($arParams["CHECK_PERMISSIONS_DEST"]) <= 0)
	$arParams["CHECK_PERMISSIONS_DEST"] = "N";

if (!array_key_exists("USE_FOLLOW", $arParams) || strLen($arParams["USE_FOLLOW"]) <= 0)
	$arParams["USE_FOLLOW"] = "Y";
	
if(defined("DisableSonetLogFollow") && DisableSonetLogFollow === true)
	$arParams["USE_FOLLOW"] = "N";

if(!$GLOBALS["USER"]->IsAuthorized())
	$arParams["USE_FOLLOW"] = "N";

if(isset($arParams["DISPLAY"]))
	$arParams["USE_FOLLOW"] = "N";	

// activation rating
CRatingsComponentsMain::GetShowRating($arParams);
if (strlen($arParams["RATING_TYPE"]) <= 0)
{
	$arParams["RATING_TYPE"] = COption::GetOptionString("main", "rating_vote_template", COption::GetOptionString("main", "rating_vote_type", "standart") == "like"? "like": "standart");
	if ($arParams["RATING_TYPE"] == "like_graphic")
		$arParams["RATING_TYPE"] = "like";
	else if ($arParams["RATING_TYPE"] == "standart")
		$arParams["RATING_TYPE"] = "standart_text";
}
else
{
	if ($arParams["RATING_TYPE"] == "like_graphic")
		$arParams["RATING_TYPE"] = "like";
	else if ($arParams["RATING_TYPE"] == "standart")
		$arParams["RATING_TYPE"] = "standart_text";
}

if (strLen($arParams["USER_VAR"]) <= 0)
	$arParams["USER_VAR"] = "user_id";
if (strLen($arParams["GROUP_VAR"]) <= 0)
	$arParams["GROUP_VAR"] = "group_id";
if (strLen($arParams["PAGE_VAR"]) <= 0)
	$arParams["PAGE_VAR"] = "page";

$arParams["PATH_TO_USER"] = trim($arParams["PATH_TO_USER"]);
if (strlen($arParams["PATH_TO_USER"]) <= 0)
	$arParams["PATH_TO_USER"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=user&".$arParams["USER_VAR"]."=#user_id#");

$arParams["PATH_TO_GROUP"] = trim($arParams["PATH_TO_GROUP"]);
if (strlen($arParams["PATH_TO_GROUP"]) <= 0)
{
	$arParams["~PATH_TO_GROUP"] = $APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=group&".$arParams["GROUP_VAR"]."=#group_id#";
	$arParams["PATH_TO_GROUP"] = htmlspecialcharsbx($arParams["~PATH_TO_GROUP"]);
}

$arParams["PATH_TO_SMILE"] = trim($arParams["PATH_TO_SMILE"]);
if (strlen($arParams["PATH_TO_SMILE"]) <= 0)
	$arParams["PATH_TO_SMILE"] = "/bitrix/images/socialnetwork/smile/";

$arParams["GROUP_ID"] = IntVal($arParams["GROUP_ID"]);
if ($arParams["GROUP_ID"] <= 0)
	$arParams["GROUP_ID"] = IntVal($_REQUEST["flt_group_id"]);

if ($arParams["GROUP_ID"] > 0)
	$arParams["ENTITY_TYPE"] = SONET_ENTITY_GROUP;

$arParams["USER_ID"] = IntVal($arParams["USER_ID"]);
if ($arParams["USER_ID"] <= 0)
	$arParams["USER_ID"] = IntVal($_REQUEST["flt_user_id"]);

if (is_array($_REQUEST["flt_created_by_id"]))
	$_REQUEST["flt_created_by_id"] = $_REQUEST["flt_created_by_id"][0];

preg_match('/^(\d+)$/', $_REQUEST["flt_created_by_id"], $matches);
if (count($matches) > 0)
	$arParams["CREATED_BY_ID"] = $_REQUEST["flt_created_by_id"];
else
{
	$arFoundUsers = CSocNetUser::SearchUser($_REQUEST["flt_created_by_id"], false);
	if (is_array($arFoundUsers) && count($arFoundUsers) > 0)
		$arParams["CREATED_BY_ID"] = key($arFoundUsers);
}

$arParams["NAME_TEMPLATE"] = $arParams["NAME_TEMPLATE"] ? $arParams["NAME_TEMPLATE"] : CSite::GetNameFormat();
$arParams["NAME_TEMPLATE_WO_NOBR"] = str_replace(
	array("#NOBR#", "#/NOBR#"),
	array("", ""),
	$arParams["NAME_TEMPLATE"]
);
$bUseLogin = $arParams['SHOW_LOGIN'] != "N" ? true : false;

if (StrLen($arParams["ENTITY_TYPE"]) <= 0)
	$arParams["ENTITY_TYPE"] = Trim($_REQUEST["flt_entity_type"]);

$arParams["AVATAR_SIZE"] = (isset($arParams["AVATAR_SIZE"]) && intval($arParams["AVATAR_SIZE"]) != 0) ? intval($arParams["AVATAR_SIZE"]) : 42;
$arParams["AVATAR_SIZE_COMMENT"] = (isset($arParams["AVATAR_SIZE_COMMENT"]) && intval($arParams["AVATAR_SIZE_COMMENT"]) != 0) ? intval($arParams["AVATAR_SIZE_COMMENT"]) : 30;

$arParams["USE_COMMENTS"] = (isset($arParams["USE_COMMENTS"]) ? $arParams["USE_COMMENTS"] : "N");
$arParams["COMMENTS_IN_EVENT"] = (isset($arParams["COMMENTS_IN_EVENT"]) && intval($arParams["COMMENTS_IN_EVENT"]) > 0 ? $arParams["COMMENTS_IN_EVENT"] : "3");
$arParams["DESTINATION_LIMIT"] = (isset($arParams["DESTINATION_LIMIT"]) ? intval($arParams["DESTINATION_LIMIT"]) : 100);
$arParams["DESTINATION_LIMIT_SHOW"] = (isset($arParams["DESTINATION_LIMIT_SHOW"]) ? intval($arParams["DESTINATION_LIMIT_SHOW"]) : 3);

$arResult["AJAX_CALL"] = (array_key_exists("bxajaxid", $_REQUEST) || array_key_exists("logajax", $_REQUEST) || array_key_exists("AJAX_CALL", $_REQUEST));
$arResult["bReload"] = ($arResult["AJAX_CALL"] && $_REQUEST["RELOAD"] == "Y");

$arParams["SET_LOG_COUNTER"] = ($GLOBALS["USER"]->IsAuthorized() && (!$arResult["AJAX_CALL"] || $arResult["bReload"]) ? "Y" : "N");
$arParams["SET_LOG_PAGE_CACHE"] = "Y";

$arResult["SHOW_UNREAD"] = $arParams["SHOW_UNREAD"] = ($arParams["SET_LOG_COUNTER"] == "Y" ? "Y" : "N");
if (!$GLOBALS["USER"]->IsAuthorized())

$arResult["PresetFilters"] = false;
if ($GLOBALS["USER"]->IsAuthorized())
{
	$arPresetFilters = CUserOptions::GetOption("socialnetwork", "~log_filter_".SITE_ID, $GLOBALS["USER"]->GetID());
	if (!is_array($arPresetFilters))
		$arPresetFilters = CUserOptions::GetOption("socialnetwork", "~log_filter", $GLOBALS["USER"]->GetID());
}

$bGetComments = (
	array_key_exists("log_filter_submit", $_REQUEST) 
	&& array_key_exists("flt_comments", $_REQUEST) 
	&& $_REQUEST["flt_comments"] == "Y"
);

if (is_array($arPresetFilters))
{

	if (!function_exists("__SL_PF_sort"))
	{
		function __SL_PF_sort($a, $b)
		{
			if ($a["SORT"] == $b["SORT"])
				return 0;
			return ($a["SORT"] < $b["SORT"]) ? -1 : 1;
		}
	}
	usort($arPresetFilters, "__SL_PF_sort");

	foreach ($arPresetFilters as $tmp_id_1 => $arPresetFilterTmp)
	{
		$bCorrect = true;

// echo "<pre>".print_r(array_diff($arPresetFilterTmp["FILTER"]["EVENT_ID"], array("tasks", "timeman_entry", "report")), true)."</pre>";
		if (
			is_array($arPresetFilterTmp["FILTER"])
			&& is_array($arPresetFilterTmp["FILTER"]["EVENT_ID"])
			&& count(array_diff($arPresetFilterTmp["FILTER"]["EVENT_ID"], array("tasks", "timeman_entry", "report"))) <= 0
			&& !IsModuleInstalled("tasks")
			&& !IsModuleInstalled("timeman")
		)
			continue;

		if (array_key_exists("NAME", $arPresetFilterTmp))
		{
			switch($arPresetFilterTmp["NAME"])
			{
				case "#WORK#":
					$arPresetFilterTmp["NAME"] = GetMessage("SONET_INSTALL_LOG_PRESET_WORK"); // lang/include.php
					break;
				case "#FAVORITES#":
					$arPresetFilterTmp["NAME"] = GetMessage("SONET_INSTALL_LOG_PRESET_FAVORITES");
					break;
				case "#MY#":
					$arPresetFilterTmp["NAME"] = GetMessage("SONET_INSTALL_LOG_PRESET_MY");
					break;
			}
		}

		if (
			array_key_exists("FILTER", $arPresetFilterTmp)
			&& is_array($arPresetFilterTmp["FILTER"])
		)
		{
			foreach($arPresetFilterTmp["FILTER"] as $tmp_id_2 => $filterTmp)
			{
				if (
					(!is_array($filterTmp) && $filterTmp == "#CURRENT_USER_ID#")
					|| (is_array($filterTmp) && in_array("#CURRENT_USER_ID#", $filterTmp))
				)
				{
					if (!$GLOBALS["USER"]->IsAuthorized())
					{
						$bCorrect = false;
						break;
					}
					elseif (!is_array($filterTmp))
						$arPresetFilterTmp["FILTER"][$tmp_id_2] = $GLOBALS["USER"]->GetID();
					elseif (is_array($filterTmp))
						foreach($filterTmp as $tmp_id_3 => $valueTmp)
							if ($valueTmp == "#CURRENT_USER_ID#")
								$arPresetFilterTmp["FILTER"][$tmp_id_2][$tmp_id_3] = $GLOBALS["USER"]->GetID();
				}
			}
		}

		if ($bCorrect)
			$arResult["PresetFilters"][$arPresetFilterTmp["ID"]] = $arPresetFilterTmp;
	}

	if ($_REQUEST["preset_filter_id"] == "clearall")
		$preset_filter_id = false;
	elseif(array_key_exists("preset_filter_id", $_REQUEST) && strlen($_REQUEST["preset_filter_id"]) > 0)
		$preset_filter_id = $_REQUEST["preset_filter_id"];

	if(array_key_exists("preset_filter_id", $_REQUEST))
		CUserOptions::DeleteOption("socialnetwork", "~log_".$arParams["ENTITY_TYPE"]."_".($arParams["ENTITY_TYPE"] == SONET_ENTITY_GROUP ? $arParams["GROUP_ID"] : $arParams["USER_ID"]));

	if (
		strlen($preset_filter_id) > 0
		&& array_key_exists($preset_filter_id, $arResult["PresetFilters"])
		&& is_array($arResult["PresetFilters"][$preset_filter_id])
		&& array_key_exists("FILTER", $arResult["PresetFilters"][$preset_filter_id])
		&& is_array($arResult["PresetFilters"][$preset_filter_id]["FILTER"])
	)
	{
		if (array_key_exists("EVENT_ID", $arResult["PresetFilters"][$preset_filter_id]["FILTER"]))
		{
			$arParams["EVENT_ID"] = $arResult["PresetFilters"][$preset_filter_id]["FILTER"]["EVENT_ID"];
			$bGetComments = false;
		}

		if (array_key_exists("CREATED_BY_ID", $arResult["PresetFilters"][$preset_filter_id]["FILTER"]))
			$arParams["CREATED_BY_ID"] = $arResult["PresetFilters"][$preset_filter_id]["FILTER"]["CREATED_BY_ID"];

		if (
			array_key_exists("FAVORITES_USER_ID", $arResult["PresetFilters"][$preset_filter_id]["FILTER"])
			&& $arResult["PresetFilters"][$preset_filter_id]["FILTER"]["FAVORITES_USER_ID"] == "Y"
		)
		{
			$arParams["FAVORITES"] = "Y";
			$bGetComments = false;
		}

		$arResult["PresetFilterActive"] = $preset_filter_id;
		$arParams["SET_LOG_COUNTER"] = $arParams["SET_LOG_PAGE_CACHE"] = "N";
		$arParams["USE_FOLLOW"] = "N";
	}
	else
		$arResult["PresetFilterActive"] = false;
}

if (
	array_key_exists("flt_date_datesel", $_REQUEST)
	&& strlen($_REQUEST["flt_date_datesel"]) > 0
)
{
	switch($_REQUEST["flt_date_datesel"])
	{
		case "today":
			$arParams["LOG_DATE_FROM"] = $arParams["LOG_DATE_TO"] = ConvertTimeStamp();
			break;
		case "yesterday":
			$arParams["LOG_DATE_FROM"] = $arParams["LOG_DATE_TO"] = ConvertTimeStamp(time()-86400);
			break;
		case "week":
			$day = date("w");
			if($day == 0)
				$day = 7;
			$arParams["LOG_DATE_FROM"] = ConvertTimeStamp(time()-($day-1)*86400);
			$arParams["LOG_DATE_TO"] = ConvertTimeStamp(time()+(7-$day)*86400);
			break;
		case "week_ago":
			$day = date("w");
			if($day == 0)
				$day = 7;
			$arParams["LOG_DATE_FROM"] = ConvertTimeStamp(time()-($day-1+7)*86400);
			$arParams["LOG_DATE_TO"] = ConvertTimeStamp(time()-($day)*86400);
			break;
		case "month":
			$arParams["LOG_DATE_FROM"] = ConvertTimeStamp(mktime(0, 0, 0, date("n"), 1));
			$arParams["LOG_DATE_TO"] = ConvertTimeStamp(mktime(0, 0, 0, date("n")+1, 0));
			break;
		case "month_ago":
			$arParams["LOG_DATE_FROM"] = ConvertTimeStamp(mktime(0, 0, 0, date("n")-1, 1));
			$arParams["LOG_DATE_TO"] = ConvertTimeStamp(mktime(0, 0, 0, date("n"), 0));
			break;
		case "days":
			$arParams["LOG_DATE_FROM"] = ConvertTimeStamp(time() - intval($_REQUEST["flt_date_days"])*86400);
			$arParams["LOG_DATE_TO"] = "";
			break;
		case "exact":
			$arParams["LOG_DATE_FROM"] = $arParams["LOG_DATE_TO"] = $_REQUEST["flt_date_from"];
			break;
		case "after":
			$arParams["LOG_DATE_FROM"] = $_REQUEST["flt_date_from"];
			$arParams["LOG_DATE_TO"] = "";
			break;
		case "before":
			$arParams["LOG_DATE_FROM"] = "";
			$arParams["LOG_DATE_TO"] = $_REQUEST["flt_date_to"];
			break;
		case "interval":
			$arParams["LOG_DATE_FROM"] = $_REQUEST["flt_date_from"];
			$arParams["LOG_DATE_TO"] = $_REQUEST["flt_date_to"];
			break;
	}
}
elseif (array_key_exists("flt_date_datesel", $_REQUEST))
{
	$arParams["LOG_DATE_FROM"] = "";
	$arParams["LOG_DATE_TO"] = "";
}
else
{
	if (array_key_exists("flt_date_from", $_REQUEST))
		$arParams["LOG_DATE_FROM"] = trim($_REQUEST["flt_date_from"]);

	if (array_key_exists("flt_date_to", $_REQUEST))
		$arParams["LOG_DATE_TO"] = trim($_REQUEST["flt_date_to"]);
}

$arParams["LOG_CNT"] = (array_key_exists("LOG_CNT", $arParams) && intval($arParams["LOG_CNT"]) > 0 ? $arParams["LOG_CNT"] : 0);
$arParams["AUTH"] = ((StrToUpper($arParams["AUTH"]) == "Y") ? "Y" : "N");

$arParams["PAGE_SIZE"] = intval($arParams["PAGE_SIZE"]);
if($arParams["PAGE_SIZE"] <= 0)
	$arParams["PAGE_SIZE"] = 20;

$arParams["PAGER_TITLE"] = trim($arParams["PAGER_TITLE"]);

if(strlen($arParams["PATH_TO_USER_BLOG_POST"]) > 0)
	$arParams["PATH_TO_USER_MICROBLOG_POST"] = $arParams["PATH_TO_USER_BLOG_POST"];
$parent = $this->GetParent();
if (is_object($parent) && strlen($parent->__name) > 0)
{
	$arParams["PATH_TO_USER_MICROBLOG"] = $parent->arResult["PATH_TO_USER_BLOG"];
	if(strlen($arParams["PATH_TO_USER_MICROBLOG_POST"]) <= 0)
		$arParams["PATH_TO_USER_MICROBLOG_POST"] = $parent->arResult["PATH_TO_USER_BLOG_POST"];
	$arParams["PATH_TO_GROUP_MICROBLOG"] = $parent->arResult["PATH_TO_GROUP_BLOG"];
	$arParams["PATH_TO_USER_BLOG_POST_EDIT"] = $parent->arResult["PATH_TO_USER_BLOG_POST_EDIT"];
	if(strlen($arParams["PATH_TO_GROUP_MICROBLOG"]) <= 0)
		$arParams["PATH_TO_GROUP_MICROBLOG"] = $parent->arParams["PATH_TO_GROUP_BLOG"];
	if(strlen($arParams["PATH_TO_USER_MICROBLOG"]) <= 0)
		$arParams["PATH_TO_USER_MICROBLOG"] = $parent->arParams["PATH_TO_USER_BLOG"];
	if(strlen($arParams["PATH_TO_GROUP_MICROBLOG_POST"]) <= 0)
		$arParams["PATH_TO_GROUP_MICROBLOG_POST"] = $parent->arParams["PATH_TO_GROUP_BLOG_POST"];
	if(strlen($arParams["PATH_TO_GROUP_MICROBLOG_POST"]) <= 0)
		$arParams["PATH_TO_GROUP_MICROBLOG_POST"] = $parent->arResult["PATH_TO_GROUP_BLOG_POST"];
	if(strlen($arParams["PATH_TO_USER_BLOG_POST_EDIT"]) <= 0)
		$arParams["PATH_TO_USER_BLOG_POST_EDIT"] = $parent->arResult["PATH_TO_USER_BLOG_POST_EDIT"];
	if(strlen($arParams["PATH_TO_USER_MICROBLOG_POST"]) <= 0)
		$arParams["PATH_TO_USER_MICROBLOG_POST"] = $parent->arParams["PATH_TO_USER_BLOG_POST"];
	if(strlen($arParams["PATH_TO_USER_MICROBLOG_POST"]) <= 0)
		$arParams["PATH_TO_USER_MICROBLOG_POST"] = $parent->arParams["PATH_TO_USER_POST"];
	if(strlen($arParams["PATH_TO_USER_BLOG_POST_EDIT"]) <= 0)
		$arParams["PATH_TO_USER_BLOG_POST_EDIT"] = $parent->arParams["PATH_TO_USER_BLOG_POST_EDIT"];
	if(strlen($arParams["PATH_TO_USER_BLOG_POST_EDIT"]) <= 0)
		$arParams["PATH_TO_USER_BLOG_POST_EDIT"] = $parent->arParams["PATH_TO_USER_POST_EDIT"];
	if(strlen($arParams["BLOG_IMAGE_MAX_WIDTH"]) <= 0)
		$arParams["BLOG_IMAGE_MAX_WIDTH"] = $parent->arParams["BLOG_IMAGE_MAX_WIDTH"];
	if(strlen($arParams["BLOG_IMAGE_MAX_HEIGHT"]) <= 0)
		$arParams["BLOG_IMAGE_MAX_HEIGHT"] = $parent->arParams["BLOG_IMAGE_MAX_HEIGHT"];
	if(strlen($arParams["BLOG_COMMENT_ALLOW_IMAGE_UPLOAD"]) <= 0)
		$arParams["BLOG_COMMENT_ALLOW_IMAGE_UPLOAD"] = $parent->arParams["BLOG_COMMENT_ALLOW_IMAGE_UPLOAD"];
	if(strlen($arParams["BLOG_ALLOW_POST_CODE"]) <= 0)
		$arParams["BLOG_ALLOW_POST_CODE"] = $parent->arParams["BLOG_ALLOW_POST_CODE"];
	if(strlen($arParams["BLOG_COMMENT_ALLOW_VIDEO"]) <= 0)
		$arParams["BLOG_COMMENT_ALLOW_VIDEO"] = $parent->arParams["BLOG_COMMENT_ALLOW_VIDEO"];
	$arParams["BLOG_GROUP_ID"] = $parent->arParams["BLOG_GROUP_ID"];
	if(isset($parent->arParams["BLOG_USE_CUT"]))
		$arParams["BLOG_USE_CUT"] = $parent->arParams["BLOG_USE_CUT"];

	$arParams["PHOTO_USER_IBLOCK_TYPE"] = $parent->arParams["PHOTO_USER_IBLOCK_TYPE"];
	$arParams["PHOTO_USER_IBLOCK_ID"] = $parent->arParams["PHOTO_USER_IBLOCK_ID"];
	$arParams["PHOTO_GROUP_IBLOCK_TYPE"] = $parent->arParams["PHOTO_GROUP_IBLOCK_TYPE"];
	$arParams["PHOTO_GROUP_IBLOCK_ID"] = $parent->arParams["PHOTO_GROUP_IBLOCK_ID"];
	$arParams["PHOTO_MAX_VOTE"] = $parent->arParams["PHOTO_MAX_VOTE"];
	$arParams["PHOTO_USE_COMMENTS"] = $parent->arParams["PHOTO_USE_COMMENTS"];
	$arParams["PHOTO_COMMENTS_TYPE"] = $parent->arParams["PHOTO_COMMENTS_TYPE"];
	$arParams["PHOTO_FORUM_ID"] = $parent->arParams["PHOTO_FORUM_ID"];
	$arParams["PHOTO_BLOG_URL"] = $parent->arParams["PHOTO_BLOG_URL"];
	$arParams["PHOTO_USE_CAPTCHA"] = $parent->arParams["PHOTO_USE_CAPTCHA"];

	if (
		(
			strlen($arParams["PHOTO_GROUP_IBLOCK_TYPE"]) <= 0
			|| intval($arParams["PHOTO_GROUP_IBLOCK_ID"]) <= 0
		)
		&& CModule::IncludeModule("iblock"))
	{
		$ttl = 60*60*24;
		$cache_id = 'sonet_group_photo_iblock_'.SITE_ID;
		$cache_dir = '/bitrix/sonet_group_photo_iblock';
		$obCache = new CPHPCache;

		if($obCache->InitCache($ttl, $cache_id, $cache_dir))
		{
			$cacheData = $obCache->GetVars();
			$arParams["PHOTO_GROUP_IBLOCK_TYPE"] = $cacheData["PHOTO_GROUP_IBLOCK_TYPE"];
			$arParams["PHOTO_GROUP_IBLOCK_ID"] = $cacheData["PHOTO_GROUP_IBLOCK_ID"];
			unset($cacheData);
		}
		else
		{
			$rsIBlockType = CIBlockType::GetByID("photos");
			if ($arIBlockType = $rsIBlockType->Fetch())
			{
				$rsIBlock = CIBlock::GetList(
					array("SORT" => "ASC"),
					array(
						"IBLOCK_TYPE" => $arIBlockType["ID"],
						"CODE" => array("group_photogallery", "group_photogallery_".SITE_ID),
						"ACTIVE" => "Y",
						"SITE_ID" => SITE_ID
					)
				);
				if ($arIBlock = $rsIBlock->Fetch())
				{
					$arParams["PHOTO_GROUP_IBLOCK_TYPE"] = $arIBlock["IBLOCK_TYPE_ID"];
					$arParams["PHOTO_GROUP_IBLOCK_ID"] = $arIBlock["ID"];
				}
			}

			if ($obCache->StartDataCache())
			{
				$obCache->EndDataCache(array(
					"PHOTO_GROUP_IBLOCK_TYPE" => $arIBlock["IBLOCK_TYPE_ID"],
					"PHOTO_GROUP_IBLOCK_ID" => $arIBlock["ID"]
				));
			}
		}
		unset($obCache);
	}

	$arParams["PATH_TO_USER_PHOTO"] = $parent->arResult["PATH_TO_USER_PHOTO"];
	$arParams["PATH_TO_GROUP_PHOTO"] = $parent->arResult["PATH_TO_GROUP_PHOTO"];
	if (strlen($arParams["PATH_TO_GROUP_PHOTO"]) <= 0)
		$arParams["PATH_TO_GROUP_PHOTO"] = $parent->arParams["PATH_TO_GROUP_PHOTO"];

	$arParams["PATH_TO_USER_PHOTO_SECTION"] = $parent->arResult["PATH_TO_USER_PHOTO_SECTION"];
	$arParams["PATH_TO_GROUP_PHOTO_SECTION"] = $parent->arResult["PATH_TO_GROUP_PHOTO_SECTION"];
	if (strlen($arParams["PATH_TO_GROUP_PHOTO_SECTION"]) <= 0)
		$arParams["PATH_TO_GROUP_PHOTO_SECTION"] = $parent->arParams["PATH_TO_GROUP_PHOTO_SECTION"];

	$arParams["PATH_TO_USER_PHOTO_ELEMENT"] = $parent->arResult["PATH_TO_USER_PHOTO_ELEMENT"];
	$arParams["PATH_TO_GROUP_PHOTO_ELEMENT"] = $parent->arResult["PATH_TO_GROUP_PHOTO_ELEMENT"];
	if (strlen($arParams["PATH_TO_GROUP_PHOTO_ELEMENT"]) <= 0)
		$arParams["PATH_TO_GROUP_PHOTO_ELEMENT"] = $parent->arParams["PATH_TO_GROUP_PHOTO_ELEMENT"];

	$arParams["PHOTO_COUNT"] = $parent->arParams["LOG_PHOTO_COUNT"];
	$arParams["PHOTO_THUMBNAIL_SIZE"] = $parent->arParams["LOG_PHOTO_THUMBNAIL_SIZE"];

	$arParams["FORUM_ID"] = $parent->arParams["FORUM_ID"];

	// parent of 2nd level
	$parent = $parent->GetParent();
	if (is_object($parent) && strlen($parent->__name) > 0)
	{
		if(strlen($arParams["PATH_TO_USER_MICROBLOG"]) <= 0)
			$arParams["PATH_TO_USER_MICROBLOG"] = $parent->arResult["PATH_TO_USER_BLOG"];
		if(strlen($arParams["PATH_TO_USER_MICROBLOG_POST"]) <= 0)
			$arParams["PATH_TO_USER_MICROBLOG_POST"] = $parent->arResult["PATH_TO_USER_BLOG_POST"];
		if(strlen($arParams["PATH_TO_GROUP_MICROBLOG"]) <= 0)
			$arParams["PATH_TO_GROUP_MICROBLOG"] = $parent->arResult["PATH_TO_GROUP_BLOG"];
		if(strlen($arParams["PATH_TO_GROUP_MICROBLOG"]) <= 0)
			$arParams["PATH_TO_GROUP_MICROBLOG"] = $parent->arParams["PATH_TO_GROUP_BLOG"];
		if(strlen($arParams["PATH_TO_USER_MICROBLOG"]) <= 0)
			$arParams["PATH_TO_USER_MICROBLOG"] = $parent->arParams["PATH_TO_USER_BLOG"];
		if(strlen($arParams["PATH_TO_GROUP_MICROBLOG_POST"]) <= 0)
			$arParams["PATH_TO_GROUP_MICROBLOG_POST"] = $parent->arParams["PATH_TO_GROUP_BLOG_POST"];
		if(strlen($arParams["PATH_TO_GROUP_MICROBLOG_POST"]) <= 0)
			$arParams["PATH_TO_GROUP_MICROBLOG_POST"] = $parent->arResult["PATH_TO_GROUP_BLOG_POST"];
		if(strlen($arParams["PATH_TO_USER_BLOG_POST_EDIT"]) <= 0)
			$arParams["PATH_TO_USER_BLOG_POST_EDIT"] = $parent->arResult["PATH_TO_USER_BLOG_POST_EDIT"];
		if(strlen($arParams["PATH_TO_USER_MICROBLOG_POST"]) <= 0)
			$arParams["PATH_TO_USER_MICROBLOG_POST"] = $parent->arParams["PATH_TO_USER_BLOG_POST"];
		if(strlen($arParams["PATH_TO_USER_MICROBLOG_POST"]) <= 0)
			$arParams["PATH_TO_USER_MICROBLOG_POST"] = $parent->arParams["PATH_TO_USER_POST"];
		if(strlen($arParams["PATH_TO_USER_BLOG_POST_EDIT"]) <= 0)
			$arParams["PATH_TO_USER_BLOG_POST_EDIT"] = $parent->arParams["PATH_TO_USER_BLOG_POST_EDIT"];
		if(strlen($arParams["PATH_TO_USER_BLOG_POST_EDIT"]) <= 0)
			$arParams["PATH_TO_USER_BLOG_POST_EDIT"] = $parent->arParams["PATH_TO_USER_POST_EDIT"];
		if(strlen($arParams["BLOG_IMAGE_MAX_WIDTH"]) <= 0)
			$arParams["BLOG_IMAGE_MAX_WIDTH"] = $parent->arParams["BLOG_IMAGE_MAX_WIDTH"];
		if(strlen($arParams["BLOG_IMAGE_MAX_HEIGHT"]) <= 0)
			$arParams["BLOG_IMAGE_MAX_HEIGHT"] = $parent->arParams["BLOG_IMAGE_MAX_HEIGHT"];
		if(strlen($arParams["BLOG_COMMENT_ALLOW_IMAGE_UPLOAD"]) <= 0)
			$arParams["BLOG_COMMENT_ALLOW_IMAGE_UPLOAD"] = $parent->arParams["BLOG_COMMENT_ALLOW_IMAGE_UPLOAD"];
		if(strlen($arParams["BLOG_ALLOW_POST_CODE"]) <= 0)
			$arParams["BLOG_ALLOW_POST_CODE"] = $parent->arParams["BLOG_ALLOW_POST_CODE"];
		if(strlen($arParams["BLOG_COMMENT_ALLOW_VIDEO"]) <= 0)
			$arParams["BLOG_COMMENT_ALLOW_VIDEO"] = $parent->arParams["BLOG_COMMENT_ALLOW_VIDEO"];
		if(intval($arParams["BLOG_GROUP_ID"]) <= 0)
			$arParams["BLOG_GROUP_ID"] = $parent->arParams["BLOG_GROUP_ID"];
		if(isset($parent->arParams["BLOG_USE_CUT"]))
			$arParams["BLOG_USE_CUT"] = $parent->arParams["BLOG_USE_CUT"];

		if(strlen($arParams["PHOTO_USER_IBLOCK_TYPE"]) <= 0)
			$arParams["PHOTO_USER_IBLOCK_TYPE"] = $parent->arParams["PHOTO_USER_IBLOCK_TYPE"];
		if(intval($arParams["PHOTO_USER_IBLOCK_ID"]) <= 0)
			$arParams["PHOTO_USER_IBLOCK_ID"] = $parent->arParams["PHOTO_USER_IBLOCK_ID"];
		if(strlen($arParams["PHOTO_GROUP_IBLOCK_TYPE"]) <= 0)
			$arParams["PHOTO_GROUP_IBLOCK_TYPE"] = $parent->arParams["PHOTO_GROUP_IBLOCK_TYPE"];
		if(intval($arParams["PHOTO_GROUP_IBLOCK_ID"]) <= 0)
			$arParams["PHOTO_GROUP_IBLOCK_ID"] = $parent->arParams["PHOTO_GROUP_IBLOCK_ID"];
		if(intval($arParams["PHOTO_MAX_VOTE"]) <= 0)
			$arParams["PHOTO_MAX_VOTE"] = $parent->arParams["PHOTO_MAX_VOTE"];
		if(strlen($arParams["PHOTO_USE_COMMENTS"]) <= 0)
			$arParams["PHOTO_USE_COMMENTS"] = $parent->arParams["PHOTO_USE_COMMENTS"];
		if(strlen($arParams["PHOTO_COMMENTS_TYPE"]) <= 0)
			$arParams["PHOTO_COMMENTS_TYPE"] = $parent->arParams["PHOTO_COMMENTS_TYPE"];
		if(intval($arParams["PHOTO_FORUM_ID"]) <= 0)
			$arParams["PHOTO_FORUM_ID"] = $parent->arParams["PHOTO_FORUM_ID"];
		if(strlen($arParams["PHOTO_BLOG_URL"]) <= 0)
			$arParams["PHOTO_BLOG_URL"] = $parent->arParams["PHOTO_BLOG_URL"];
		if(strlen($arParams["PHOTO_USE_CAPTCHA"]) <= 0)
			$arParams["PHOTO_USE_CAPTCHA"] = $parent->arParams["PHOTO_USE_CAPTCHA"];

		if(strlen($arParams["PATH_TO_USER_PHOTO"]) <= 0)
			$arParams["PATH_TO_USER_PHOTO"] = $parent->arResult["PATH_TO_USER_PHOTO"];
		if(strlen($arParams["PATH_TO_GROUP_PHOTO"]) <= 0)
			$arParams["PATH_TO_GROUP_PHOTO"] = $parent->arResult["PATH_TO_GROUP_PHOTO"];
		if (strlen($arParams["PATH_TO_GROUP_PHOTO"]) <= 0)
			$arParams["PATH_TO_GROUP_PHOTO"] = $parent->arParams["PATH_TO_GROUP_PHOTO"];

		if(strlen($arParams["PATH_TO_USER_PHOTO_SECTION"]) <= 0)
			$arParams["PATH_TO_USER_PHOTO_SECTION"] = $parent->arResult["PATH_TO_USER_PHOTO_SECTION"];
		if(strlen($arParams["PATH_TO_GROUP_PHOTO_SECTION"]) <= 0)
			$arParams["PATH_TO_GROUP_PHOTO_SECTION"] = $parent->arResult["PATH_TO_GROUP_PHOTO_SECTION"];
		if (strlen($arParams["PATH_TO_GROUP_PHOTO_SECTION"]) <= 0)
			$arParams["PATH_TO_GROUP_PHOTO_SECTION"] = $parent->arParams["PATH_TO_GROUP_PHOTO_SECTION"];

		if(strlen($arParams["PATH_TO_USER_PHOTO_ELEMENT"]) <= 0)
			$arParams["PATH_TO_USER_PHOTO_ELEMENT"] = $parent->arResult["PATH_TO_USER_PHOTO_ELEMENT"];
		if(strlen($arParams["PATH_TO_GROUP_PHOTO_ELEMENT"]) <= 0)
			$arParams["PATH_TO_GROUP_PHOTO_ELEMENT"] = $parent->arResult["PATH_TO_GROUP_PHOTO_ELEMENT"];
		if (strlen($arParams["PATH_TO_GROUP_PHOTO_ELEMENT"]) <= 0)
			$arParams["PATH_TO_GROUP_PHOTO_ELEMENT"] = $parent->arParams["PATH_TO_GROUP_PHOTO_ELEMENT"];

		if(intval($arParams["PHOTO_COUNT"]) <= 0)
			$arParams["PHOTO_COUNT"] = $parent->arParams["LOG_PHOTO_COUNT"];
		if(intval($arParams["PHOTO_THUMBNAIL_SIZE"]) <= 0)
			$arParams["PHOTO_THUMBNAIL_SIZE"] = $parent->arParams["LOG_PHOTO_THUMBNAIL_SIZE"];

		if(intval($arParams["FORUM_ID"]) <= 0)
			$arParams["FORUM_ID"] = $parent->arParams["FORUM_ID"];
	}
}
if(strlen($arParams["PATH_TO_USER_MICROBLOG_POST"]) <= 0)
	$arParams["PATH_TO_USER_MICROBLOG_POST"] = "/company/personal/user/#user_id#/blog/#post_id#/";
if(strlen($arParams["PATH_TO_USER_BLOG_POST_EDIT"]) <= 0)
	$arParams["PATH_TO_USER_BLOG_POST_EDIT"] = "/company/personal/user/#user_id#/blog/edit/#post_id#/";


if (intval($arParams["PHOTO_COUNT"]) <= 0)
	$arParams["PHOTO_COUNT"] = 6;
if (intval($arParams["PHOTO_THUMBNAIL_SIZE"]) <= 0)
	$arParams["PHOTO_THUMBNAIL_SIZE"] = 48;

if(
	IntVal($GLOBALS["USER"]->GetID()) > 0
	&& (
		(
			$arParams["ENTITY_TYPE"] != SONET_ENTITY_GROUP 
			&& CSocNetFeatures::IsActiveFeature(SONET_ENTITY_USER, $GLOBALS["USER"]->GetID(), "blog")
		) 
		|| (
			$arParams["ENTITY_TYPE"] == SONET_ENTITY_GROUP 
			&& CSocNetFeatures::IsActiveFeature(SONET_ENTITY_GROUP, $arParams["GROUP_ID"], "blog")
		)
	)
)
	$arResult["MICROBLOG_USER_ID"] = $GLOBALS["USER"]->GetID();

$bCurrentUserIsAdmin = CSocNetUser::IsCurrentUserModuleAdmin();

$arResult["TZ_OFFSET"] = CTimeZone::GetOffset();

$GLOBALS["arExtranetGroupID"] = array();
$GLOBALS["arExtranetUserID"] = array();

if($GLOBALS["USER"]->IsAuthorized())
{
	if(defined("BX_COMP_MANAGED_CACHE"))
		$ttl = 2592000;
	else
		$ttl = 600;

	$cache_id = 'sonet_ex_gr_'.SITE_ID;
	$obCache = new CPHPCache;
	$cache_dir = '/bitrix/sonet_log_sg';

	if($obCache->InitCache($ttl, $cache_id, $cache_dir))
	{
		$tmpVal = $obCache->GetVars();
		$GLOBALS["arExtranetGroupID"] = $tmpVal['EX_GROUP_ID'];
		$GLOBALS["arExtranetUserID"] = $tmpVal['EX_USER_ID'];
		unset($tmpVal);
	}
	elseif (CModule::IncludeModule("extranet") && !CExtranet::IsExtranetSite())
	{
		global $CACHE_MANAGER;
		$CACHE_MANAGER->StartTagCache($cache_dir);
		$dbGroupTmp = CSocNetGroup::GetList(
			array(),
			array(
				"SITE_ID" => CExtranet::GetExtranetSiteID()
			),
			false,
			false,
			array("ID")
		);
		while($arGroupTmp = $dbGroupTmp->Fetch())
		{
			$GLOBALS["arExtranetGroupID"][] = $arGroupTmp["ID"];
			$CACHE_MANAGER->RegisterTag('sonet_group_'.$arGroupTmp["ID"]);
		}

		$rsUsers = CUser::GetList(
			($by="ID"),
			($order="asc"),
			array(
				"GROUPS_ID" => array(CExtranet::GetExtranetUserGroupID()),
				"UF_DEPARTMENT" => false
			),
			array("FIELDS" => array("ID"))
		);
		while($arUser = $rsUsers->Fetch())
		{
			$GLOBALS["arExtranetUserID"][] = $arUser["ID"];
			$CACHE_MANAGER->RegisterTag('sonet_user2group_U'.$arUser["ID"]);
		}
		$CACHE_MANAGER->EndTagCache();
		if($obCache->StartDataCache())
			$obCache->EndDataCache(array(
				'EX_GROUP_ID' => $GLOBALS["arExtranetGroupID"],
				'EX_USER_ID' => $GLOBALS["arExtranetUserID"]
			));
	}
	unset($obCache);
}

if (
	$GLOBALS["USER"]->IsAuthorized() 
	|| $arParams["AUTH"] == "Y" 
)
{
	$arTmpEventsNew = array();

	$arResult["IS_FILTERED"] = false;

	if (
		$arParams["SET_TITLE"] == "Y" 
		|| $arParams["SET_NAV_CHAIN"] != "N"
		|| $arParams["GROUP_ID"] > 0
	)
	{
		if ($arParams["ENTITY_TYPE"] == SONET_ENTITY_USER)
		{
			$rsUser = CUser::GetByID($arParams["USER_ID"]);
			if ($arResult["User"] = $rsUser->Fetch())
				$strTitleFormatted = CUser::FormatName($arParams['NAME_TEMPLATE'], $arResult["User"], $bUseLogin);
		}
		elseif ($arParams["ENTITY_TYPE"] == SONET_ENTITY_GROUP)
			$arResult["Group"] = CSocNetGroup::GetByID($arParams["GROUP_ID"]);
	}

	if ($arParams["SET_TITLE"] == "Y")
		$APPLICATION->SetTitle(GetMessage("SONET_C73_PAGE_TITLE"));

	if ($arParams["SET_NAV_CHAIN"] != "N")
		$APPLICATION->AddChainItem(GetMessage("SONET_C73_PAGE_TITLE"));

	$arResult["Events"] = false;

	$arFilter = array();

	if(isset($arParams["DISPLAY"]))
	{
		$arResult["SHOW_UNREAD"] = $arParams["SHOW_UNREAD"] = "N";

		$arParams["SHOW_EVENT_ID_FILTER"] = "N";
		if($arParams["DISPLAY"] === "forme")
		{
			$arAccessCodes = $USER->GetAccessCodes();
			foreach($arAccessCodes as $i => $code)
				if(!preg_match("/^(U|D|DR)/", $code)) //Users and Departments
					unset($arAccessCodes[$i]);
			$arFilter["LOG_RIGHTS"] = $arAccessCodes;
			$arFilter["!USER_ID"] = $USER->GetID();
			$arResult["IS_FILTERED"] = true;
			$arParams["SET_LOG_COUNTER"] = $arParams["SET_LOG_PAGE_CACHE"] = "N";
			$arParams["USE_FOLLOW"] = "N";
		}
		elseif($arParams["DISPLAY"] === "mine")
		{
			$arFilter["USER_ID"] = $USER->GetID();
			$arResult["IS_FILTERED"] = true;
			$arParams["SET_LOG_COUNTER"] = $arParams["SET_LOG_PAGE_CACHE"] = "N";
			$arParams["USE_FOLLOW"] = "N";
		}
		elseif($arParams["DISPLAY"] === "my")
		{
			$arAccessCodes = $USER->GetAccessCodes();
			foreach($arAccessCodes as $i => $code)
				if(!preg_match("/^(U|D|DR)/", $code)) //Users and Departments
					unset($arAccessCodes[$i]);
			$arFilter["LOG_RIGHTS"] = $arAccessCodes;
			$arParams["SET_LOG_PAGE_CACHE"] = "N";
			$arParams["USE_FOLLOW"] = "N";
		}
		elseif($arParams["DISPLAY"] > 0)
		{
			$arFilter["USER_ID"] = intval($arParams["DISPLAY"]);
			$arResult["IS_FILTERED"] = true;
			$arParams["SET_LOG_COUNTER"] = $arParams["SET_LOG_PAGE_CACHE"] = "N";
			$arParams["USE_FOLLOW"] = "N";
		}
	}

	if ($arParams["DESTINATION"] > 0)
		$arFilter["LOG_RIGHTS"] = $arParams["DESTINATION"];
	elseif ($arParams["GROUP_ID"] > 0)
	{
		$ENTITY_TYPE = SONET_ENTITY_GROUP;
		$ENTITY_ID = $arParams["GROUP_ID"];

		$arFilter["LOG_RIGHTS"] = "SG".intval($arParams["GROUP_ID"]);
		$arParams["SET_LOG_PAGE_CACHE"] = "N";
		$arParams["USE_FOLLOW"] = "N";
	}
	elseif ($arParams["USER_ID"] > 0)
	{
		$ENTITY_TYPE = $arFilter["ENTITY_TYPE"] = SONET_ENTITY_USER;
		$ENTITY_ID = $arFilter["ENTITY_ID"] = $arParams["USER_ID"];
	}
	elseif (StrLen($arParams["ENTITY_TYPE"]) > 0)
	{
		$ENTITY_TYPE = $arFilter["ENTITY_TYPE"] = $arParams["ENTITY_TYPE"];
		$ENTITY_ID = 0;
	}
	else
	{
		$ENTITY_TYPE = "";
		$ENTITY_ID = 0;
	}

	if (isset($arParams["EXACT_EVENT_ID"]))
	{
		$arFilter["EVENT_ID"] = array($arParams["EXACT_EVENT_ID"]);
		$arResult["IS_FILTERED"] = true;
		$arParams["SET_LOG_COUNTER"] = $arParams["SET_LOG_PAGE_CACHE"] = "N";
		$arParams["USE_FOLLOW"] = "N";
	}
	elseif (is_array($arParams["EVENT_ID"]))
	{
		if (!in_array("all", $arParams["EVENT_ID"]))
		{
			$event_id_fullset_tmp = array();
			foreach($arParams["EVENT_ID"] as $event_id_tmp)
				$event_id_fullset_tmp = array_merge($event_id_fullset_tmp, CSocNetLogTools::FindFullSetByEventID($event_id_tmp));
			$arFilter["EVENT_ID"] = array_unique($event_id_fullset_tmp);

			$arResult["IS_FILTERED"] = true;
			$arParams["SET_LOG_COUNTER"] = $arParams["SET_LOG_PAGE_CACHE"] = "N";
			$arParams["USE_FOLLOW"] = "N";
		}
	}
	elseif ($arParams["EVENT_ID"])
	{
		$arFilter["EVENT_ID"] = CSocNetLogTools::FindFullSetByEventID($arParams["EVENT_ID"]);
		$arResult["IS_FILTERED"] = true;
		$arParams["SET_LOG_COUNTER"] = $arParams["SET_LOG_PAGE_CACHE"] = "N";
		$arParams["USE_FOLLOW"] = "N";
	}

	if (IntVal($arParams["CREATED_BY_ID"]) > 0)
	{
		if ($bGetComments)
			$arFilter["USER_ID|COMMENT_USER_ID"] = $arParams["CREATED_BY_ID"];
		else
			$arFilter["USER_ID"] = $arParams["CREATED_BY_ID"];

		$arResult["IS_FILTERED"] = true;
		$arParams["SET_LOG_COUNTER"] = $arParams["SET_LOG_PAGE_CACHE"] = "N";
		$arParams["USE_FOLLOW"] = "N";
	}

	if (IntVal($arParams["GROUP_ID"]) > 0)
		$arResult["IS_FILTERED"] = true;

	if ($arParams["FLT_ALL"] == "Y")
		$arFilter["ALL"] = "Y";

	if (
		$ENTITY_TYPE != "" 
		&& $ENTITY_ID > 0
		&& !array_key_exists("EVENT_ID", $arFilter)
	)
	{
		$arFilter["EVENT_ID"] = array();

		foreach($GLOBALS["arSocNetLogEvents"] as $event_id_tmp => $arEventTmp)
		{
			if (
				array_key_exists("HIDDEN", $arEventTmp)
				&& $arEventTmp["HIDDEN"]
			)
				continue;

			$arFilter["EVENT_ID"][] = $event_id_tmp;
		}

		$arFeatures = CSocNetFeatures::GetActiveFeatures($ENTITY_TYPE, $ENTITY_ID);
		foreach($arFeatures as $feature_id)
		{
			if(
				array_key_exists($feature_id, $GLOBALS["arSocNetFeaturesSettings"])
				&& array_key_exists("subscribe_events", $GLOBALS["arSocNetFeaturesSettings"][$feature_id])
			)
				foreach ($GLOBALS["arSocNetFeaturesSettings"][$feature_id]["subscribe_events"] as $event_id_tmp => $arEventTmp)
					$arFilter["EVENT_ID"][] = $event_id_tmp;
		}
	}

	if (
		!$arFilter["EVENT_ID"]
		|| (is_array($arFilter["EVENT_ID"]) && count($arFilter["EVENT_ID"]) <= 0)
	)
		unset($arFilter["EVENT_ID"]);

	if (CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite())
		$arFilter["SITE_ID"] = SITE_ID;
	else
		$arFilter["SITE_ID"] = array(SITE_ID, false);

	if (
		array_key_exists("LOG_DATE_FROM", $arParams)
		&& strlen(trim($arParams["LOG_DATE_FROM"])) > 0
		&& MakeTimeStamp($arParams["LOG_DATE_FROM"], CSite::GetDateFormat("SHORT")) < time()+$arResult["TZ_OFFSET"]
	)
	{
		$arFilter[">=LOG_DATE"] = $arParams["LOG_DATE_FROM"];
		$arParams["SET_LOG_COUNTER"] = $arParams["SET_LOG_PAGE_CACHE"] = "N";
		$arParams["USE_FOLLOW"] = "N";
		$arResult["IS_FILTERED"] = true;
	}
	else
		unset($_REQUEST["flt_date_from"]);

	if (
		array_key_exists("LOG_DATE_TO", $arParams)
		&& strlen(trim($arParams["LOG_DATE_TO"])) > 0
		&& MakeTimeStamp($arParams["LOG_DATE_TO"], CSite::GetDateFormat("SHORT")) < time()+$arResult["TZ_OFFSET"]
	)
	{
		$arFilter["<=LOG_DATE"] = ConvertTimeStamp(MakeTimeStamp($arParams["LOG_DATE_TO"], CSite::GetDateFormat("SHORT"))+86399, "FULL");
		$arParams["SET_LOG_COUNTER"] = $arParams["SET_LOG_PAGE_CACHE"] = "N";
		$arParams["USE_FOLLOW"] = "N";
		$arResult["IS_FILTERED"] = true;
	}
	else
	{
		$arFilter["<=LOG_DATE"] = "NOW";
		unset($_REQUEST["flt_date_to"]);
	}

	if (intval($arParams["LOG_CNT"]) > 0)
	{
		$arNavStartParams = array("nTopCount" => $arParams["LOG_CNT"]);
		$arResult["PAGE_NUMBER"] = 1;
		$bFirstPage = true;
		$arParams["SHOW_NAV_STRING"] = "N";
		$arParams["SHOW_REFRESH"] = "N";
	}
	elseif (!$arResult["AJAX_CALL"] || $arResult["bReload"])
	{
		$arNavStartParams = array("nTopCount" => $arParams["PAGE_SIZE"]);
		$arResult["PAGE_NUMBER"] = 1;
		$bFirstPage = true;
	}
	else
	{
		if (intval($_REQUEST["PAGEN_".($GLOBALS["NavNum"] + 1)]) > 0)
			$arResult["PAGE_NUMBER"] = intval($_REQUEST["PAGEN_".($GLOBALS["NavNum"] + 1)]);

		$arNavStartParams = array(
			"nPageSize" => $arParams["PAGE_SIZE"],
			"bShowAll" => false,
			"iNavAddRecords" => 1,
			"bSkipPageReset" => true
		);
	}

	if ($bGetComments)
		$arOrder = array("LOG_UPDATE" => "DESC");	
	elseif ($arParams["USE_FOLLOW"] == "Y")
		$arOrder = array("DATE_FOLLOW" => "DESC");
	elseif ($arParams["USE_COMMENTS"] == "Y")
		$arOrder = array("LOG_UPDATE" => "DESC");
	else
		$arOrder = array("LOG_DATE"	=> "DESC");

	if ($arParams["FAVORITES"] == "Y")
		$arFilter[">FAVORITES_USER_ID"] = 0;

	$arParams["NAME_TEMPLATE"] = $arParams["NAME_TEMPLATE_WO_NOBR"];

	if (intval($arParams["GROUP_ID"]) > 0)
		$arResult["COUNTER_TYPE"] = "SG".intval($arParams["GROUP_ID"]);
	elseif($arParams["EXACT_EVENT_ID"] == "blog_post")
		$arResult["COUNTER_TYPE"] = "blog_post";
	else
		$arResult["COUNTER_TYPE"] = "**";

	if (!$arResult["AJAX_CALL"] || $arResult["bReload"])
	{
		$arResult["LAST_LOG_TS"] = CUserCounter::GetLastDate($GLOBALS["USER"]->GetID(), $arResult["COUNTER_TYPE"]);

		if($arResult["LAST_LOG_TS"] == 0)
			$arResult["LAST_LOG_TS"] = 1;
		else
		{
			//We substruct TimeZone offset in order to get server time
			//because of template compatibility
			$arResult["LAST_LOG_TS"] -= $arResult["TZ_OFFSET"];
		}
	}
	else
		$arResult["LAST_LOG_TS"] = intval($_REQUEST["ts"]);

	if ($arParams["SET_LOG_PAGE_CACHE"] == "Y")
	{
		$rsLogPages = CSocNetLogPages::GetList(
			array(
				"USER_ID" => $GLOBALS["USER"]->GetID(),
				"SITE_ID" => SITE_ID,
				"PAGE_SIZE" => $arParams["PAGE_SIZE"],
				"PAGE_NUM" => $arResult["PAGE_NUMBER"]
			),
			array("PAGE_LAST_DATE")
		);

		if ($arLogPages = $rsLogPages->Fetch())
			$arFilter[">=LOG_UPDATE"] = $dateLastPageStart = $arLogPages["PAGE_LAST_DATE"];
	}

	$arListParams = array(
		"CHECK_RIGHTS" => "Y",
	);

	if ($bCurrentUserIsAdmin)
		$arListParams["USER_ID"] = "A";

	if ($arParams["USE_FOLLOW"] == "Y")
		$arListParams["USE_FOLLOW"] = "Y";
	else
	{
		$arListParams["USE_FOLLOW"] = "N";
		$arListParams["USE_SUBSCRIBE"] = "N";
	}

	$arSelectFields = array(
		"ID", 
		"LOG_DATE", "LOG_UPDATE", "DATE_FOLLOW", 
		"ENTITY_TYPE", "ENTITY_ID", "EVENT_ID", "SOURCE_ID", "USER_ID", "FOLLOW", "FAVORITES_USER_ID"
	);

	if ($GLOBALS["DB"]->type == "MYSQL")
		$arSelectFields[] = "LOG_DATE_TS";

	$dbEventsID = CSocNetLog::GetList(
		$arOrder,
		$arFilter,
		false,
		$arNavStartParams,
		$arSelectFields,
		$arListParams
	);

	if ($bFirstPage)
	{
		$arResult["NAV_STRING"] = "";
		$arResult["PAGE_NAVNUM"] = $GLOBALS["NavNum"]+1;
		$arResult["PAGE_NAVCOUNT"] = 1000000;
	}
	else
	{
		$arResult["NAV_STRING"] = $dbEventsID->GetPageNavStringEx($navComponentObject, GetMessage("SONET_C73_NAV"), "", false);
		$arResult["PAGE_NUMBER"] = $dbEventsID->NavPageNomer;
		$arResult["PAGE_NAVNUM"] = $dbEventsID->NavNum;
		$arResult["PAGE_NAVCOUNT"] = $dbEventsID->NavPageCount;
	}

	$cnt = 0;
	$arLogTmpID = array();

	while ($arEventsID = $dbEventsID->GetNext())
	{
		$cnt++;
		if ($cnt == 1)
		{
			if ($dbEventsID->NavPageNomer > 1)
				$current_page_date = $arEvents["LOG_UPDATE"];
			else
			{
				$current_page_date = ConvertTimeStamp(time() + $arResult["TZ_OFFSET"], "FULL");
				$bNow = true;
			}
		}
		$arLogTmpID[] = ($arEvents["TMP_ID"] > 0 ? $arEvents["TMP_ID"] : $arEvents["ID"]);
		$arTmpEventsNew[] = $arEventsID;
	}

	if ($bFirstPage)
		$last_date = $arTmpEventsNew[count($arTmpEventsNew)-1][($arParams["USE_FOLLOW"] == "Y" ? "DATE_FOLLOW" : "LOG_UPDATE")];
	elseif (
		$dbEventsID
		&& $dbEventsID->NavContinue() 
		&& $arEvents = $dbEventsID->GetNext()
	)
	{
		$next_page_date = ($arParams["USE_FOLLOW"] == "Y" ? $arEvents["DATE_FOLLOW"] : $arEvents["LOG_UPDATE"]);
		if ($GLOBALS["USER"]->IsAuthorized())
		{
			if ($arResult["LAST_LOG_TS"] < MakeTimeStamp($next_page_date))
				$next_page_date = $arResult["LAST_LOG_TS"];
		}
	}

	foreach ($arTmpEventsNew as $key => $arTmpEvent)
	{
		if (
			!is_array($_SESSION["SONET_LOG_ID"])
			|| !in_array($arTmpEvent["ID"], $_SESSION["SONET_LOG_ID"])
		)
			$_SESSION["SONET_LOG_ID"][] = $arTmpEvent["ID"];

		$arTmpEventsNew[$key]["EVENT_ID_FULLSET"] = CSocNetLogTools::FindFullSetEventIDByEventID($arTmpEvent["EVENT_ID"]);
	}

	$arResult["Events"] = $arTmpEventsNew;

	if ($arTmpEvent["DATE_FOLLOW"])
		$dateLastPage = ConvertTimeStamp(MakeTimeStamp($arTmpEvent["DATE_FOLLOW"], CSite::GetDateFormat("FULL")), "FULL");

	$arResult["WORKGROUPS_PAGE"] = COption::GetOptionString("socialnetwork", "workgroups_page", "/workgroups/", SITE_ID);

	if (
		$GLOBALS["USER"]->IsAuthorized() 
		&& $arParams["SET_LOG_COUNTER"] == "Y"
	)
	{
		$arCounters = CUserCounter::GetValues($GLOBALS["USER"]->GetID(), SITE_ID);
		if (isset($arCounters[$arResult["COUNTER_TYPE"]]))
			$arResult["LOG_COUNTER"] = intval($arCounters[$arResult["COUNTER_TYPE"]]);
		else
		{
			$bEmptyCounter = true;
			$arResult["LOG_COUNTER"] = 0;
		}
	}
	else
		$arParams["SHOW_UNREAD"] = "N";

	if (
		$GLOBALS["USER"]->IsAuthorized()
		&& $arParams["SET_LOG_COUNTER"] == "Y"
		&& (intval($arResult["LOG_COUNTER"]) > 0 || $bEmptyCounter)
	)
		CUserCounter::ClearByUser(
			$GLOBALS["USER"]->GetID(), 
			array(SITE_ID, "**"),
			$arResult["COUNTER_TYPE"]
		);

	if (
		$GLOBALS["USER"]->IsAuthorized()
		&& $arParams["SET_LOG_PAGE_CACHE"] == "Y"
		&& $dateLastPage
		&& (
			!$dateLastPageStart
			|| $dateLastPageStart != $dateLastPage
		)
	)
	{
		CSocNetLogPages::Set(
			$GLOBALS["USER"]->GetID(), 
			$dateLastPage,
			$arParams["PAGE_SIZE"],
			$arResult["PAGE_NUMBER"],
			SITE_ID
		);
	}
}
else
	$arResult["NEED_AUTH"] = "Y";

$arResult["GET_COMMENTS"] = ($bGetComments ? "Y" : "N");
$arResult["CURRENT_PAGE_DATE"] = $current_page_date;
$arResult["bGetComments"] = $bGetComments;

$this->IncludeComponentTemplate();
?>