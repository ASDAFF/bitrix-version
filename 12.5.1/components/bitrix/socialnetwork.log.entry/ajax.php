<?
define("NO_KEEP_STATISTIC", true);
define("BX_STATISTIC_BUFFER_USED", false);
define("NO_LANG_FILES", true);
define("NOT_CHECK_PERMISSIONS", true);
define("BX_PUBLIC_TOOLS", true);

$site_id = (isset($_REQUEST["site"]) && is_string($_REQUEST["site"])) ? trim($_REQUEST["site"]): "";
$site_id = substr(preg_replace("/[^a-z0-9_]/i", "", $site_id), 0, 2);

define("SITE_ID", $site_id);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/bx_root.php");

$action = (isset($_REQUEST["action"]) && is_string($_REQUEST["action"])) ? trim($_REQUEST["action"]): "";
$entity_type = (isset($_REQUEST["et"]) && is_string($_REQUEST["et"])) ? trim($_REQUEST["et"]): "";
$entity_id = isset($_REQUEST["eid"])? $_REQUEST["eid"]: "";
$cb_id = isset($_REQUEST["cb_id"])? $_REQUEST["cb_id"]: "";
$event_id = (isset($_REQUEST["evid"]) && is_string($_REQUEST["evid"])) ? trim($_REQUEST["evid"]): "";
$transport = (isset($_REQUEST["transport"]) && is_string($_REQUEST["transport"])) ? trim($_REQUEST["transport"]): "";

$lng = (isset($_REQUEST["lang"]) && is_string($_REQUEST["lang"])) ? trim($_REQUEST["lang"]): "";
$lng = substr(preg_replace("/[^a-z0-9_]/i", "", $lng), 0, 2);

$ls = isset($_REQUEST["ls"]) && !is_array($_REQUEST["ls"])? trim($_REQUEST["ls"]): "";
$ls_arr = isset($_REQUEST["ls_arr"])? $_REQUEST["ls_arr"]: "";

$st_id = (isset($_REQUEST["st_id"]) && is_string($_REQUEST["st_id"])) ? trim($_REQUEST["st_id"]): "";
$st_id = preg_replace("/[^a-z0-9_]/i", "", $st_id);

define("SITE_TEMPLATE_ID", $st_id);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$rsSite = CSite::GetByID($site_id);
if ($arSite = $rsSite->Fetch())
	define("LANGUAGE_ID", $arSite["LANGUAGE_ID"]);
else
	define("LANGUAGE_ID", "en");

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/socialnetwork.log.entry/include.php");

__IncludeLang(dirname(__FILE__)."/lang/".$lng."/ajax.php");

if(CModule::IncludeModule("compression"))
	CCompress::Disable2048Spaces();

if(CModule::IncludeModule("socialnetwork"))
{
	$bCurrentUserIsAdmin = CSocNetUser::IsCurrentUserModuleAdmin();

	// write and close session to prevent lock;
	session_write_close();

	$arResult = array();

	if (in_array($action, array("get_comment", "get_comments")))
	{
		$GLOBALS["arExtranetGroupID"] = array();
		$GLOBALS["arExtranetUserID"] = array();

		if ($GLOBALS["USER"]->IsAuthorized())
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
					)
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
	}

	if (!$GLOBALS["USER"]->IsAuthorized())
		$arResult[0] = "*";
	elseif (!check_bitrix_sessid())
		$arResult[0] = "*";
	elseif ($action == "add_comment")
	{
		$log_id = $_REQUEST["log_id"];
		if ($arLog = CSocNetLog::GetByID($log_id))
		{
			$arCommentEvent = CSocNetLogTools::FindLogCommentEventByLogEventID($arLog["EVENT_ID"]);
			if ($arCommentEvent)
			{
				$feature = CSocNetLogTools::FindFeatureByEventID($arCommentEvent["EVENT_ID"]);

				if ($feature && array_key_exists("OPERATION_ADD", $arCommentEvent) && strlen($arCommentEvent["OPERATION_ADD"]) > 0)
					$bCanAddComments = CSocNetFeaturesPerms::CanPerformOperation($GLOBALS["USER"]->GetID(), $arLog["ENTITY_TYPE"], $arLog["ENTITY_ID"], ($feature == "microblog" ? "blog" : $feature), $arCommentEvent["OPERATION_ADD"], $bCurrentUserIsAdmin);
				else
					$bCanAddComments = true;

				if ($bCanAddComments)
				{
					// add source object and get source_id, $source_url
					$arParams = array(
						"PATH_TO_SMILE" => $_REQUEST["p_smile"],
						"PATH_TO_USER_BLOG_POST" => $_REQUEST["p_ubp"],
						"PATH_TO_GROUP_BLOG_POST" => $_REQUEST["p_gbp"],
						"PATH_TO_USER_MICROBLOG_POST" => $_REQUEST["p_umbp"],
						"PATH_TO_GROUP_MICROBLOG_POST" => $_REQUEST["p_gmbp"],
						"BLOG_ALLOW_POST_CODE" => $_REQUEST["bapc"]
					);
					$parser = new logTextParser(LANGUAGE_ID, $arParams["PATH_TO_SMILE"]);

					$comment_text = $_REQUEST["message"];
					CUtil::decodeURIComponent($comment_text);
					$comment_text = Trim($comment_text);

					if (strlen($comment_text) > 0)
					{
						$arSearchParams = array();

						if($arCommentEvent["EVENT_ID"] == "forum")
						{
							$arSearchParams["FORUM_ID"] = intval($_REQUEST["f_id"]);
							$arSearchParams["PATH_TO_GROUP_FORUM_MESSAGE"] = (
								$arLog["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP 
									? str_replace(
										"#GROUPS_PATH#", 
										COption::GetOptionString("socialnetwork", "workgroups_page", false, $site_id),
										$arLog["URL"]
									) 
									: ""
							);
							$arSearchParams["PATH_TO_USER_FORUM_MESSAGE"] = (
								$arLog["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_USER 
									? $arLog["URL"] 
									: ""
							);
						}
						elseif ($arCommentEvent["EVENT_ID"] == "files_comment")
						{
							if (strlen($arLog["PARAMS"]) > 0)
							{
								$files_forum_id = 0;
								$arLogParams = explode("&", htmlspecialcharsback($arLog["PARAMS"]));
								foreach($arLogParams as $prm)
								{
									list($k, $v) = explode("=", $prm);
									if ($k == "forum_id")
									{
										$files_forum_id = $v;
										break;
									}
								}
							}
							$arSearchParams["FILES_FORUM_ID"] = $files_forum_id;
							$arSearchParams["PATH_TO_GROUP_FILES_ELEMENT"] = (
								$arLog["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP 
									? str_replace(
										"#GROUPS_PATH#", 
										COption::GetOptionString("socialnetwork", "workgroups_page", false, $site_id),
										$arLog["URL"]
									) 
									: ""
							);
							$arSearchParams["PATH_TO_USER_FILES_ELEMENT"] = (
								$arLog["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_USER 
									? $arLog["URL"] 
									: ""
							);
						}
						elseif($arCommentEvent["EVENT_ID"] == "photo_comment")
						{
							if (strlen($arLog["PARAMS"]) > 0)
							{
								$photo_forum_id = 0;
								$arLogParams = unserialize(htmlspecialcharsback($arLog["PARAMS"]));
								if (
									is_array($arLogParams)
									&& array_key_exists("FORUM_ID", $arLogParams)
									&& intval($arLogParams["FORUM_ID"]) > 0
								)
									$photo_forum_id = $arLogParams["FORUM_ID"];
							}
							$arSearchParams["PHOTO_FORUM_ID"] = $photo_forum_id;
							$arSearchParams["PATH_TO_GROUP_PHOTO_ELEMENT"] = (
								$arLog["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP 
									? str_replace(
										"#GROUPS_PATH#",
										COption::GetOptionString("socialnetwork", "workgroups_page", false, $site_id),
										$arLog["URL"]
									) 
									: ""
							);
							$arSearchParams["PATH_TO_USER_PHOTO_ELEMENT"] = (
								$arLog["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_USER 
									? $arLog["URL"]
									: ""
							);
						}

						global $bxSocNetSearch;
						if (
							!empty($arSearchParams)
							&& !is_object($bxSocNetSearch)
						)
						{
							$bxSocNetSearch = new CSocNetSearch(
								($arLog["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_USER ? $arLog["ENTITY_ID"] : false), 
								($arLog["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? $arLog["ENTITY_ID"] : false),
								$arSearchParams
							);
							AddEventHandler("search", "BeforeIndex", Array($bxSocNetSearch, "BeforeIndex"));
						}

						$arAllow = array(
							"HTML" => "N",
							"ANCHOR" => "Y",
							"LOG_ANCHOR" => "N",
							"BIU" => "N",
							"IMG" => "N",
							"LIST" => "N",
							"QUOTE" => "N",
							"CODE" => "N",
							"FONT" => "N",
							"UPLOAD" => $arForum["ALLOW_UPLOAD"],
							"NL2BR" => "N",
							"SMILES" => "N"
						);

						$arFields = array(
							"ENTITY_TYPE" => $arLog["ENTITY_TYPE"],
							"ENTITY_ID" => $arLog["ENTITY_ID"],
							"EVENT_ID" => $arCommentEvent["EVENT_ID"],
							"=LOG_DATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
							"MESSAGE" => $parser->convert($comment_text, array(), $arAllow),
							"TEXT_MESSAGE" => $comment_text,
							"MODULE_ID" => false,
							"LOG_ID" => $arLog["TMP_ID"],
							"USER_ID" => $GLOBALS["USER"]->GetID(),
							"PATH_TO_USER_BLOG_POST" => $arParams["PATH_TO_USER_BLOG_POST"],
							"PATH_TO_GROUP_BLOG_POST" => $arParams["PATH_TO_GROUP_BLOG_POST"],
							"PATH_TO_USER_MICROBLOG_POST" => $arParams["PATH_TO_USER_MICROBLOG_POST"],
							"PATH_TO_GROUP_MICROBLOG_POST" => $arParams["PATH_TO_GROUP_MICROBLOG_POST"],
							"BLOG_ALLOW_POST_CODE" => $arParams["BLOG_ALLOW_POST_CODE"]
						);

						$comment = CSocNetLogComments::Add($arFields, true);
						if (!is_array($comment) && intval($comment) > 0)
							$arResult["commentID"] = $comment;
						elseif (is_array($comment) &&  array_key_exists("MESSAGE", $comment) && strlen($comment["MESSAGE"]) > 0)
						{
							$arResult["strMessage"] = $comment["MESSAGE"];
							$arResult["commentText"] = $comment_text;
						}
					}
					else
						$arResult["strMessage"] = GetMessage("SONET_LOG_COMMENT_EMPTY");
				}
				else
					$arResult["strMessage"] = GetMessage("SONET_LOG_COMMENT_NO_PERMISSIONS");
			}
		}
	}
	elseif ($action == "get_comment")
	{
		$comment_id = $_REQUEST["cid"];

		if ($arComment = CSocNetLogComments::GetByID($comment_id))
		{
			$arResult["arComment"] = $arComment;

			$dateFormated = FormatDate(
				$GLOBALS['DB']->DateFormatToPHP(FORMAT_DATE),
				MakeTimeStamp(array_key_exists("LOG_DATE_FORMAT", $arComment) ? $arComment["LOG_DATE_FORMAT"] : $arComment["LOG_DATE"])
			);

			$timeFormat = (isset($_REQUEST["dtf"]) ? $_REQUEST["dtf"] : CSite::GetTimeFormat());

			$timeFormated = FormatDateFromDB(
				(array_key_exists("LOG_DATE_FORMAT", $arComment) ? $arComment["LOG_DATE_FORMAT"] : $arComment["LOG_DATE"]),
				(stripos($timeFormat, 'a') || ($timeFormat == 'FULL' && IsAmPmMode()) !== false ? 'H:MI T' : 'HH:MI')
			);

			if (intval($arComment["USER_ID"]) > 0)
			{
				$arParams = array(
					"PATH_TO_USER" => $_REQUEST["p_user"],
					"NAME_TEMPLATE" => $_REQUEST["nt"],
					"SHOW_LOGIN" => $_REQUEST["sl"],
					"AVATAR_SIZE" => $_REQUEST["as"],
					"PATH_TO_SMILE" => $_REQUEST["p_smile"]
				);

				$arUser = array(
					"ID" => $arComment["USER_ID"],
					"NAME" => $arComment["~CREATED_BY_NAME"],
					"LAST_NAME" => $arComment["~CREATED_BY_LAST_NAME"],
					"SECOND_NAME" => $arComment["~CREATED_BY_SECOND_NAME"],
					"LOGIN" => $arComment["~CREATED_BY_LOGIN"],
					"PERSONAL_PHOTO" => $arComment["~CREATED_BY_PERSONAL_PHOTO"],
					"PERSONAL_GENDER" => $arComment["~CREATED_BY_PERSONAL_GENDER"],
				);
				$bUseLogin = $arParams["SHOW_LOGIN"] != "N" ? true : false;
				$arCreatedBy = array(
					"FORMATTED" => CUser::FormatName($arParams["NAME_TEMPLATE"], $arUser, $bUseLogin),
					"URL" => CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $arComment["USER_ID"], "id" => $arComment["USER_ID"]))
				);

			}
			else
				$arCreatedBy = array("FORMATTED" => GetMessage("SONET_C73_CREATED_BY_ANONYMOUS"));

			$arTmpCommentEvent = array(
				"LOG_DATE" => $arComment["LOG_DATE"],
				"LOG_DATE_FORMAT" => $arComment["LOG_DATE_FORMAT"],
				"LOG_DATE_DAY" => ConvertTimeStamp(MakeTimeStamp($arComment["LOG_DATE"]), "SHORT"),
				"LOG_TIME_FORMAT" => $timeFormated,
				"MESSAGE" => $arComment["MESSAGE"],
				"MESSAGE_FORMAT" => $arComment["~MESSAGE"],
				"CREATED_BY" => $arCreatedBy,
				"AVATAR_SRC" => CSocNetLogTools::FormatEvent_CreateAvatar($arUser, $arParams, ""),
				"USER_ID" => $arComment["USER_ID"]
			);

			$arEventTmp = CSocNetLogTools::FindLogCommentEventByID($arComment["EVENT_ID"]);
			if (
				$arEventTmp
				&& array_key_exists("CLASS_FORMAT", $arEventTmp)
				&& array_key_exists("METHOD_FORMAT", $arEventTmp)
			)
			{
				$arFIELDS_FORMATTED = call_user_func(array($arEventTmp["CLASS_FORMAT"], $arEventTmp["METHOD_FORMAT"]), $arComment, $arParams);
				$arTmpCommentEvent["MESSAGE_FORMAT"] = htmlspecialcharsback($arFIELDS_FORMATTED["EVENT_FORMATTED"]["MESSAGE"]);
			}

			$arResult["arCommentFormatted"] = $arTmpCommentEvent;
		}
	}
	elseif ($action == "get_comments")
	{
		$arResult["arComments"] = array();

		$log_tmp_id = $_REQUEST["logid"];

		if (intval($log_tmp_id) > 0)
		{
			$arParams = array(
				"PATH_TO_USER" => $_REQUEST["p_user"],
				"PATH_TO_GROUP" => $_REQUEST["p_group"],
				"PATH_TO_CONPANY_DEPARTMENT" => $_REQUEST["p_dep"],
				"NAME_TEMPLATE" => $_REQUEST["nt"],
				"NAME_TEMPLATE_WO_NOBR" => str_replace(array("#NOBR#", "#/NOBR#"), array("", ""), $_REQUEST["nt"]),
				"SHOW_LOGIN" => $_REQUEST["sl"],
				"DATE_TIME_FORMAT" => (isset($_REQUEST["dtf"]) ? $_REQUEST["dtf"] : CSite::GetTimeFormat()),
				"AVATAR_SIZE_COMMENT" => $_REQUEST["as"],
				"PATH_TO_SMILE" => $_REQUEST["p_smile"]
			);

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
			$cache_id = "log_comments_".$log_tmp_id."_".md5(serialize($arCacheID))."_".SITE_TEMPLATE_ID."_".SITE_ID."_".LANGUAGE_ID."_".CTimeZone::GetOffset();
			$cache_path = "/sonet/log_comments/";

			if (
				is_object($cache)
				&& $cache->InitCache($cache_time, $cache_id, $cache_path)
			)
			{
				$arCacheVars = $cache->GetVars();
				$arResult["arComments"] = $arCacheVars["COMMENTS_FULL_LIST"];
			}
			else
			{
				$arCommentsFullList = array();

				if (is_object($cache))
					$cache->StartDataCache($cache_time, $cache_id, $cache_path);

				if (defined("BX_COMP_MANAGED_CACHE"))
				{
					$GLOBALS["CACHE_MANAGER"]->StartTagCache($cache_path);
					$GLOBALS["CACHE_MANAGER"]->RegisterTag("SONET_LOG_".$log_tmp_id);
				}

				$arFilter = array("LOG_ID" => $log_tmp_id);
				$arListParams = array("USE_SUBSCRIBE" => "N");

				$arSelect = array(
					"ID", "LOG_ID", "SOURCE_ID", "ENTITY_TYPE", "ENTITY_ID", "USER_ID", "EVENT_ID", "LOG_DATE", "MESSAGE", "TEXT_MESSAGE", "URL", "MODULE_ID",
					"GROUP_NAME", "GROUP_OWNER_ID", "GROUP_VISIBLE", "GROUP_OPENED", "GROUP_IMAGE_ID",
					"USER_NAME", "USER_LAST_NAME", "USER_SECOND_NAME", "USER_LOGIN", "USER_PERSONAL_PHOTO", "USER_PERSONAL_GENDER",
					"CREATED_BY_NAME", "CREATED_BY_LAST_NAME", "CREATED_BY_SECOND_NAME", "CREATED_BY_LOGIN", "CREATED_BY_PERSONAL_PHOTO", "CREATED_BY_PERSONAL_GENDER",
					"LOG_SITE_ID", "LOG_SOURCE_ID",
					"RATING_TYPE_ID", "RATING_ENTITY_ID"
				);

				$dbComments = CSocNetLogComments::GetList(
					array("LOG_DATE" => "ASC"),
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

					$arResult["arComments"][] = __SLEGetLogCommentRecord($arComments, $arParams, false);
				}

				if (is_object($cache))
				{
					$arCacheData = Array(
						"COMMENTS_FULL_LIST" => $arResult["arComments"]
					);
					$cache->EndDataCache($arCacheData);
					if(defined("BX_COMP_MANAGED_CACHE"))
						$GLOBALS["CACHE_MANAGER"]->EndTagCache();
				}
			}

			foreach ($arResult["arComments"] as $key => $arCommentTmp)
			{
				if ($key === 0)
					$rating_entity_type = $arCommentTmp["EVENT"]["RATING_TYPE_ID"];

				$arCommentID[] = $arCommentTmp["EVENT"]["RATING_ENTITY_ID"];
			}

			$arRatingComments = array();
			if(
				!empty($arCommentID)
				&& strlen($rating_entity_type) > 0
			)
				$arRatingComments = CRatings::GetRatingVoteResult($rating_entity_type, $arCommentID);

			foreach($arResult["arComments"] as $key => $arCommentTmp)
			{
				if (array_key_exists($arCommentTmp["EVENT"]["RATING_ENTITY_ID"], $arRatingComments))
				{
					$arResult["arComments"][$key]["EVENT"]["RATING_USER_VOTE_VALUE"] = (isset($arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["USER_VOTE"]) ? $arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["USER_VOTE"] : 0);
					$arResult["arComments"][$key]["EVENT"]["RATING_USER_HAS_VOTED"] = (isset($arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["USER_HAS_VOTED"]) ? $arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["USER_HAS_VOTED"] : "N");
					$arResult["arComments"][$key]["EVENT"]["RATING_TOTAL_POSITIVE_VOTES"] = (isset($arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["TOTAL_POSITIVE_VOTES"]) ? $arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["TOTAL_POSITIVE_VOTES"] : 0);
					$arResult["arComments"][$key]["EVENT"]["RATING_TOTAL_NEGATIVE_VOTES"] = (isset($arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["TOTAL_NEGATIVE_VOTES"]) ? $arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["TOTAL_NEGATIVE_VOTES"] : 0);
					$arResult["arComments"][$key]["EVENT"]["RATING_TOTAL_VALUE"] = (isset($arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["TOTAL_VALUE"]) ? $arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["TOTAL_VALUE"] : 0);
					$arResult["arComments"][$key]["EVENT"]["RATING_TOTAL_VOTES"] = (isset($arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["TOTAL_VOTES"]) ? $arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["TOTAL_VOTES"] : 0);
				}
				else
				{
					$arResult["arComments"][$key]["EVENT"]["RATING_USER_VOTE_VALUE"] = 0;
					$arResult["arComments"][$key]["EVENT"]["RATING_USER_HAS_VOTED"] = "N";
					$arResult["arComments"][$key]["EVENT"]["RATING_TOTAL_POSITIVE_VOTES"] = 0;
					$arResult["arComments"][$key]["EVENT"]["RATING_TOTAL_NEGATIVE_VOTES"] = 0;
					$arResult["arComments"][$key]["EVENT"]["RATING_TOTAL_VALUE"] = 0;
					$arResult["arComments"][$key]["EVENT"]["RATING_TOTAL_VOTES"] = 0;
				}
				
				if (strlen($rating_entity_type) > 0)
					$arResult["arComments"][$key]["EVENT_FORMATTED"]["ALLOW_VOTE"] = CRatings::CheckAllowVote(
						array(
							"ENTITY_TYPE_ID" => $rating_entity_type,
							"OWNER_ID" => $arResult["arComments"][$key]["EVENT"]["USER_ID"]
						)
					);
			}
		}
	}
	elseif ($action == "change_favorites" && $GLOBALS["USER"]->IsAuthorized())
	{
		$log_id = intval($_REQUEST["log_id"]);
		if ($arLog = CSocNetLog::GetByID($log_id))
		{
			if ($strRes = CSocNetLogFavorites::Change($GLOBALS["USER"]->GetID(), $log_id))
			{
				if ($strRes == "Y")
					CSocNetLogFollow::Set($GLOBALS["USER"]->GetID(), "L".$log_id, "Y");
				$arResult["bResult"] = $strRes;
			}
			else
			{
				if($e = $GLOBALS["APPLICATION"]->GetException())
					$arResult["strMessage"] = $e->GetString();
				else
					$arResult["strMessage"] = GetMessage("SONET_LOG_FAVORITES_CANNOT_CHANGE");
				$arResult["bResult"] = "E";
			}
		}
		else
		{
			$arResult["strMessage"] = GetMessage("SONET_LOG_FAVORITES_INCORRECT_LOG_ID");
			$arResult["bResult"] = "E";
		}
	}
	elseif ($action == "get_more_destination")
	{
		$arResult["arDestinations"] = false;
		$log_id = intval($_REQUEST["log_id"]);
		$created_by_id = intval($_REQUEST["created_by_id"]);
		$iDestinationLimit = intval($_REQUEST["dlim"]);

		if ($log_id > 0)
		{
			$dbRight = CSocNetLogRights::GetList(array(), array("LOG_ID" => $log_id));
			while ($arRight = $dbRight->Fetch())
				$arRights[] = $arRight["GROUP_CODE"];

			$arParams = array(
				"PATH_TO_USER" => $_REQUEST["p_user"],
				"PATH_TO_GROUP" => $_REQUEST["p_group"],
				"PATH_TO_CONPANY_DEPARTMENT" => $_REQUEST["p_dep"],
				"NAME_TEMPLATE" => $_REQUEST["nt"],
				"SHOW_LOGIN" => $_REQUEST["sl"],
				"DESTINATION_LIMIT" => 100,
				"CHECK_PERMISSIONS_DEST" => "N"
			);

			if ($created_by_id > 0)
				$arParams["CREATED_BY"] = $created_by_id;

			$arDestinations = CSocNetLogTools::FormatDestinationFromRights($arRights, $arParams, $iMoreCount);
			if (is_array($arDestinations))
			{
				$iDestinationsHidden = 0;
				$arGroupID = array();

				// get tagged cached available groups and intersect
				$cache = new CPHPCache;	
				$cache_id = $GLOBALS["USER"]->GetID();
				$cache_path = "/sonet/groups_available/";

				if ($cache->InitCache($cache_time, $cache_id, $cache_path))
				{
					$arCacheVars = $cache->GetVars();
					$arGroupID = $arCacheVars["arGroupID"];
				}
				else
				{
					$cache->StartDataCache($cache_time, $cache_id, $cache_path);
					if (defined("BX_COMP_MANAGED_CACHE"))
					{
						$GLOBALS["CACHE_MANAGER"]->StartTagCache($cache_path);
						$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_user2group_U".$GLOBALS["USER"]->GetID());
						$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_group");
					}

					$rsGroup = CSocNetGroup::GetList(
						array(),
						array("CHECK_PERMISSIONS" => $GLOBALS["USER"]->GetID()),
						false,
						false,
						array("ID")
					);
					while($arGroup = $rsGroup->Fetch())
						$arGroupID[] = $arGroup["ID"];

					$arCacheData = array(
						"arGroupID" => $arGroupID
					);
					$cache->EndDataCache($arCacheData);
					if(defined("BX_COMP_MANAGED_CACHE"))
						$GLOBALS["CACHE_MANAGER"]->EndTagCache();
				}

				foreach($arDestinations as $key => $arDestination)
				{
					if (
						array_key_exists("TYPE", $arDestination)
						&& array_key_exists("ID", $arDestination)
						&& $arDestination["TYPE"] == "SG"
						&& !in_array(intval($arDestination["ID"]), $arGroupID)
					)
					{
						unset($arDestinations[$key]);
						$iDestinationsHidden++;
					}
				}

				$arResult["arDestinations"] = array_slice($arDestinations, $iDestinationLimit);
				$arResult["iDestinationsHidden"] = $iDestinationsHidden;
			}
		}
	}

	header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo CUtil::PhpToJSObject($arResult);
}

define('PUBLIC_AJAX_MODE', true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>