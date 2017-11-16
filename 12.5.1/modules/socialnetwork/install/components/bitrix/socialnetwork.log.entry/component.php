<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/socialnetwork.log.entry/include.php");

if (!CModule::IncludeModule("socialnetwork"))
{
	ShowError(GetMessage("SONET_MODULE_NOT_INSTALL"));
	return;
}

if (
	!isset($arParams["LOG_ID"])
	|| intval($arParams["LOG_ID"]) <= 0
)
	return;

if (!isset($arParams["IND"]) || strlen($arParams["IND"]) <= 0)
	$arParams["IND"] = RandString(8);

if (isset($arParams["CURRENT_PAGE_DATE"]))
	$current_page_date = $arParams["CURRENT_PAGE_DATE"];

$bCurrentUserIsAdmin = CSocNetUser::IsCurrentUserModuleAdmin();

$arResult["TZ_OFFSET"] = CTimeZone::GetOffset();
$arResult["LAST_LOG_TS"] = intval($arParams["LAST_LOG_TS"]);
$arResult["COUNTER_TYPE"] = $arParams["COUNTER_TYPE"];
$arResult["AJAX_CALL"] = $arParams["AJAX_CALL"];
$arResult["bReload"] = $arParams["bReload"];
$arResult["bGetComments"] = $arParams["bGetComments"];

$arResult["Event"] = false;
$arCurrentUserSubscribe = array("TRANSPORT" => array());

$arEvent = __SLEGetLogRecord($arParams["LOG_ID"], $arParams, $arCurrentUserSubscribe, $current_page_date);

if ($arEvent)
{
	if (
		isset($arEvent["HAS_COMMENTS"])
		&& $arEvent["HAS_COMMENTS"] == "Y"
	)
	{
		$cache_time = 31536000;

		$cache = new CPHPCache;

		$arCacheID = array();
		$arKeys = array(
			"AVATAR_SIZE_COMMENT",
			"NAME_TEMPLATE",
			"NAME_TEMPLATE_WO_NOBR",
			"SHOW_LOGIN",
			"DATE_TIME_FORMAT",
			"PATH_TO_USER",
			"PATH_TO_GROUP",
			"PATH_TO_CONPANY_DEPARTMENT"
		);
		foreach($arKeys as $param_key)
		{
			if (array_key_exists($param_key, $arParams))
				$arCacheID[$param_key] = $arParams[$param_key];
			else
				$arCacheID[$param_key] = false;
		}
		$cache_id = "log_comments_".$arParams["LOG_ID"]."_".md5(serialize($arCacheID))."_".SITE_TEMPLATE_ID."_".SITE_ID."_".LANGUAGE_ID."_".$arResult["TZ_OFFSET"];
		$cache_path = "/sonet/log_comments/";

		if (
			is_object($cache)
			&& $cache->InitCache($cache_time, $cache_id, $cache_path)
		)
		{
			$arCacheVars = $cache->GetVars();
			$arCommentsFullList = $arCacheVars["COMMENTS_FULL_LIST"];
		}
		else
		{
			$arCommentsFullList = array();

			if (is_object($cache))
				$cache->StartDataCache($cache_time, $cache_id, $cache_path);

			if (defined("BX_COMP_MANAGED_CACHE"))
			{
				$GLOBALS["CACHE_MANAGER"]->StartTagCache($cache_path);
				$GLOBALS["CACHE_MANAGER"]->RegisterTag("SONET_LOG_".$arParams["LOG_ID"]);
			}

			$arFilter = array(
				"LOG_ID" => $arParams["LOG_ID"]
			);

			$arSelect = array(
				"ID", "LOG_ID", "SOURCE_ID", "ENTITY_TYPE", "ENTITY_ID", "USER_ID", "EVENT_ID", "LOG_DATE", "MESSAGE", "TEXT_MESSAGE", "URL", "MODULE_ID",
				"GROUP_NAME", "GROUP_OWNER_ID", "GROUP_VISIBLE", "GROUP_OPENED", "GROUP_IMAGE_ID",
				"USER_NAME", "USER_LAST_NAME", "USER_SECOND_NAME", "USER_LOGIN", "USER_PERSONAL_PHOTO", "USER_PERSONAL_GENDER",
				"CREATED_BY_NAME", "CREATED_BY_LAST_NAME", "CREATED_BY_SECOND_NAME", "CREATED_BY_LOGIN", "CREATED_BY_PERSONAL_PHOTO", "CREATED_BY_PERSONAL_GENDER",
				"LOG_SITE_ID", "LOG_SOURCE_ID",
				"RATING_TYPE_ID", "RATING_ENTITY_ID"
			);

			if ($GLOBALS["DB"]->type == "MYSQL")
				$arSelect[] = "LOG_DATE_TS";

			$arListParams = array(
				"USE_SUBSCRIBE" => "N",
				"CHECK_RIGHTS" => "N"
			);

			$dbComments = CSocNetLogComments::GetList(
				array("LOG_DATE" => "DESC"), // revert then
				$arFilter,
				false,
				false,
				$arSelect,
				$arListParams
			);

			while($arComments = $dbComments->GetNext())
			{
				if (defined("BX_COMP_MANAGED_CACHE"))
				{
					$GLOBALS["CACHE_MANAGER"]->RegisterTag("USER_CARD_".intval($arComments["USER_ID"] / 100));
					$GLOBALS["CACHE_MANAGER"]->RegisterTag("SONET_LOG_COMMENT_".intval($arComments["ID"]));
				}

				$arCommentsFullList[] = __SLEGetLogCommentRecord($arComments, $arParams, $arCurrentUserSubscribe);
			}

			if (is_object($cache))
			{
				$arCacheData = Array(
					"COMMENTS_FULL_LIST" => $arCommentsFullList
				);
				$cache->EndDataCache($arCacheData);
				if(defined("BX_COMP_MANAGED_CACHE"))
					$GLOBALS["CACHE_MANAGER"]->EndTagCache();
			}
		}

		$arCommentsFullListCut = array();
		$arCommentID = array();

		foreach ($arCommentsFullList as $key => $arCommentTmp)
		{
			if ($key === 0)
				$rating_entity_type = $arCommentTmp["EVENT"]["RATING_TYPE_ID"];

			if (
				$arResult["bGetComments"] 
				&& intval($arParams["CREATED_BY_ID"]) > 0
			)
			{
				if ($arCommentTmp["EVENT"]["USER_ID"] == $arParams["CREATED_BY_ID"])
					$arCommentsFullListCut[] = $arCommentTmp;
			}
			else
			{
				if (isset($arCommentTmp["EVENT"]["LOG_DATE_TS"]))
					$event_date_log_ts = $arCommentTmp["EVENT"]["LOG_DATE_TS"];
				else
					$event_date_log_ts = (MakeTimeStamp($arCommentTmp["EVENT"]["LOG_DATE"]) - intval($arResult["TZ_OFFSET"]));

				if (
					$key >= $arParams["COMMENTS_IN_EVENT"]
					&& (
						intval($arResult["LAST_LOG_TS"]) <= 0
						|| $event_date_log_ts <= $arResult["LAST_LOG_TS"]
					)
				)
				{
					//
				}
				else
					$arCommentsFullListCut[] = $arCommentTmp;
			}

			$arCommentID[] = $arCommentTmp["EVENT"]["RATING_ENTITY_ID"];
		}

		$arEvent["COMMENTS"] = array_reverse($arCommentsFullListCut);

		$arResult["RATING_COMMENTS"] = array();
		if(
			!empty($arCommentID)
			&& $arParams["SHOW_RATING"] == "Y"
			&& strlen($rating_entity_type) > 0
		)
			$arResult["RATING_COMMENTS"] = CRatings::GetRatingVoteResult($rating_entity_type, $arCommentID);
	}
}

$arResult["Event"] = $arEvent;
$arResult["WORKGROUPS_PAGE"] = COption::GetOptionString("socialnetwork", "workgroups_page", "/workgroups/", SITE_ID);

$arResult["GET_COMMENTS"] = ($bGetComments ? "Y" : "N");

$this->IncludeComponentTemplate();
?>