<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!isset($GLOBALS["CurUserCanAddComments"]))
	$GLOBALS["CurUserCanAddComments"] = array();

if (!function_exists('__SLTransportSort'))
{
	function __SLTransportSort($a, $b)
	{
		$arPattern = array("M", "X", "D", "E");
		$a_key = array_search($a, $arPattern);
		$b_key = array_search($b, $arPattern);

		if ($a_key == $b_key)
			return 0;

		return ($a_key < $b_key) ? -1 : 1;
	}
}

if (!function_exists('__SLEGetTransport'))
{
	function __SLEGetTransport($arFields, $arCurrentUserSubscribe)
	{
		if (array_key_exists($arFields["ENTITY_TYPE"]."_".$arFields["ENTITY_ID"]."_".$arFields["EVENT_ID"]."_N_N", $arCurrentUserSubscribe["TRANSPORT"]))
			$arTransport[] = $arCurrentUserSubscribe["TRANSPORT"][$arFields["ENTITY_TYPE"]."_".$arFields["ENTITY_ID"]."_".$arFields["EVENT_ID"]."_N_N"];

		if (array_key_exists($arFields["ENTITY_TYPE"]."_".$arFields["ENTITY_ID"]."_all_N_N", $arCurrentUserSubscribe["TRANSPORT"]))
			$arTransport[] = $arCurrentUserSubscribe["TRANSPORT"][$arFields["ENTITY_TYPE"]."_".$arFields["ENTITY_ID"]."_all_N_N"];

		$bHasLogEventCreatedBy = CSocNetLogTools::HasLogEventCreatedBy($arFields["EVENT_ID"]);
		if ($bHasLogEventCreatedBy)
		{
			if ($arFields["EVENT_ID"])
			{
				if (array_key_exists("U_".$arFields["USER_ID"]."_all_N_Y", $arCurrentUserSubscribe["TRANSPORT"]))
					$arTransport[] = $arCurrentUserSubscribe["TRANSPORT"]["U_".$arFields["USER_ID"]."_all_N_Y"];
				elseif (array_key_exists("U_".$arFields["USER_ID"]."_all_Y_Y", $arCurrentUserSubscribe["TRANSPORT"]))
					$arTransport[] = $arCurrentUserSubscribe["TRANSPORT"]["U_".$arFields["USER_ID"]."_all_Y_Y"];
			}
		}

		if (
			!array_key_exists($arFields["ENTITY_TYPE"]."_".$arFields["ENTITY_ID"]."_".$arFields["EVENT_ID"]."_N_N", $arCurrentUserSubscribe["TRANSPORT"])
			&& !array_key_exists($arFields["ENTITY_TYPE"]."_".$arFields["ENTITY_ID"]."_all_N_N", $arCurrentUserSubscribe["TRANSPORT"])
			)
		{
			if (array_key_exists($arFields["ENTITY_TYPE"]."_0_".$arFields["EVENT_ID"]."_N_N", $arCurrentUserSubscribe["TRANSPORT"]))
				$arTransport[] = $arCurrentUserSubscribe["TRANSPORT"][$arFields["ENTITY_TYPE"]."_0_".$arFields["EVENT_ID"]."_N_N"];
			elseif (array_key_exists($arFields["ENTITY_TYPE"]."_0_all_N_N", $arCurrentUserSubscribe["TRANSPORT"]))
				$arTransport[] = $arCurrentUserSubscribe["TRANSPORT"][$arFields["ENTITY_TYPE"]."_0_all_N_N"];
			else
				$arTransport[] = "N";
		}

		$arTransport = array_unique($arTransport);
		usort($arTransport, "__SLTransportSort");

		return $arTransport;
	}
}

if (!function_exists('__SLGetLogRecord'))
{
	function __SLEGetLogRecord($logID, $arParams, $arCurrentUserSubscribe, $current_page_date)
	{
		$cache_time = 31536000;
		$arEvent = array();

		$cache = new CPHPCache;

		$arCacheID = array();
		$arKeys = array(
			"AVATAR_SIZE",
			"DESTINATION_LIMIT",
			"CHECK_PERMISSIONS_DEST",
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
		$cache_id = "log_post_".$logID."_".md5(serialize($arCacheID))."_".SITE_TEMPLATE_ID."_".SITE_ID."_".LANGUAGE_ID."_".CTimeZone::GetOffset();
		$cache_path = "/sonet/log/";

		if (
			is_object($cache)
			&& $cache->InitCache($cache_time, $cache_id, $cache_path)
		)
		{
			$arCacheVars = $cache->GetVars();
			$arEvent["FIELDS_FORMATTED"] = $arCacheVars["FIELDS_FORMATTED"];

			if (array_key_exists("CACHED_CSS_PATH", $arEvent["FIELDS_FORMATTED"]))
			{
				if (
					!is_array($arEvent["FIELDS_FORMATTED"]["CACHED_CSS_PATH"]) 
					&& strlen($arEvent["FIELDS_FORMATTED"]["CACHED_CSS_PATH"]) > 0
				)
					$GLOBALS['APPLICATION']->SetAdditionalCSS($arEvent["FIELDS_FORMATTED"]["CACHED_CSS_PATH"]);
				elseif(is_array($arEvent["FIELDS_FORMATTED"]["CACHED_CSS_PATH"]))
					foreach($arEvent["FIELDS_FORMATTED"]["CACHED_CSS_PATH"] as $css_path)
						$GLOBALS['APPLICATION']->SetAdditionalCSS($css_path);
			}
		}
		else
		{
			if (is_object($cache))
				$cache->StartDataCache($cache_time, $cache_id, $cache_path);

			$arFilter = array(
				"ID" => $logID
			);

			$arListParams = array(
				"CHECK_RIGHTS" => "N",
				"USE_FOLLOW" => "N",
				"USE_SUBSCRIBE" => "N"
			);

			$arSelect = array(
				"ID", "TMP_ID", "ENTITY_TYPE", "ENTITY_ID", "USER_ID", "EVENT_ID", "LOG_DATE", "LOG_UPDATE", "TITLE_TEMPLATE", "TITLE", "MESSAGE", "TEXT_MESSAGE", "URL", "MODULE_ID", "CALLBACK_FUNC", "EXTERNAL_ID", "SITE_ID", "PARAMS",
				"COMMENTS_COUNT", "ENABLE_COMMENTS", "SOURCE_ID",
				"GROUP_NAME", "GROUP_OWNER_ID", "GROUP_INITIATE_PERMS", "GROUP_VISIBLE", "GROUP_OPENED", "GROUP_IMAGE_ID",
				"USER_NAME", "USER_LAST_NAME", "USER_SECOND_NAME", "USER_LOGIN", "USER_PERSONAL_PHOTO", "USER_PERSONAL_GENDER",
				"CREATED_BY_NAME", "CREATED_BY_LAST_NAME", "CREATED_BY_SECOND_NAME", "CREATED_BY_LOGIN", "CREATED_BY_PERSONAL_PHOTO", "CREATED_BY_PERSONAL_GENDER",
				"RATING_TYPE_ID", "RATING_ENTITY_ID",
				"SOURCE_TYPE"
			);

			$dbEvent = CSocNetLog::GetList(
				array(),
				$arFilter,
				false,
				false,
				$arSelect,
				$arListParams
			);

			if ($arEvent = $dbEvent->GetNext())
			{
				if (defined("BX_COMP_MANAGED_CACHE"))
				{
					$GLOBALS["CACHE_MANAGER"]->StartTagCache($cache_path);
					$GLOBALS["CACHE_MANAGER"]->RegisterTag("USER_CARD_".intval($arEvent["USER_ID"] / 100));
					$GLOBALS["CACHE_MANAGER"]->RegisterTag("SONET_LOG_".intval($arEvent["ID"]));

					if ($arEvent["ENTITY_TYPE"] == SONET_ENTITY_GROUP)
						$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_group_".$arEvent["ENTITY_ID"]);
				}

				$arEvent["EVENT_ID_FULLSET"] = CSocNetLogTools::FindFullSetEventIDByEventID($arEvent["EVENT_ID"]);

				if ($arEvent["ENTITY_TYPE"] == SONET_ENTITY_GROUP)
				{
					static $arSiteWorkgroupsPage;

					if (
						!$arSiteWorkgroupsPage
						&& IsModuleInstalled("extranet")
					)
					{
						$rsSite = CSite::GetList($by="sort", $order="desc", Array("ACTIVE" => "Y"));
						while($arSite = $rsSite->Fetch())
							$arSiteWorkgroupsPage[$arSite["ID"]] = COption::GetOptionString("socialnetwork", "workgroup_page", $arSite["DIR"]."workgroups/", $arSite["ID"]);
					}

					if (
						is_set($arEvent["URL"]) 
						&& is_array($arSiteWorkgroupsPage) 
						&& array_key_exists(SITE_ID, $arSiteWorkgroupsPage)
					)
						$arEvent["URL"] = str_replace("#GROUPS_PATH#", $arSiteWorkgroupsPage[SITE_ID], $arEvent["URL"]);
				}

				$arEventTmp = CSocNetLogTools::FindLogEventByID($arEvent["EVENT_ID"]);
				if (
					$arEventTmp
					&& is_array($arEventTmp) 
					&& array_key_exists("CLASS_FORMAT", $arEventTmp)
					&& array_key_exists("METHOD_FORMAT", $arEventTmp)
				)
				{
					$arEvent["FIELDS_FORMATTED"] = call_user_func(array($arEventTmp["CLASS_FORMAT"], $arEventTmp["METHOD_FORMAT"]), $arEvent, $arParams);

					if (is_array($arEvent["FIELDS_FORMATTED"]))
					{
						if (
							isset($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"])
							&& is_array($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"])
							&& isset($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["MESSAGE"])
						)
							$arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["MESSAGE"] = CSocNetTextParser::closetags(htmlspecialcharsback($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["MESSAGE"]));

						if (
							isset($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"])
							&& is_array($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"])
						)
						{
							$arFields2Cache = array(
								"URL",
								"STYLE",
								"DESTINATION",
								"DESTINATION_MORE",
								"TITLE_24",
								"TITLE_24_2",
								"IS_IMPORTANT",
								"MESSAGE",
								"MESSAGE_TITLE_24",
								"DATETIME_FORMATTED"
							);
							foreach ($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"] as $field => $value)
								if (!in_array($field, $arFields2Cache))
									unset($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"][$field]);
						}

						if (
							isset($arEvent["FIELDS_FORMATTED"]["EVENT"])
							&& is_array($arEvent["FIELDS_FORMATTED"]["EVENT"])
						)
						{
							$arFields2Cache = array(
								"ID",
								"URL",
								"USER_ID",
								"ENTITY_TYPE",
								"ENTITY_ID",
								"EVENT_ID",
								"EVENT_ID_FULLSET",
								"TITLE",
								"SOURCE_ID",
								"PARAMS",
								"RATING_TYPE_ID",
								"RATING_ENTITY_ID"
							);
							foreach ($arEvent["FIELDS_FORMATTED"]["EVENT"] as $field => $value)
								if (!in_array($field, $arFields2Cache))
									unset($arEvent["FIELDS_FORMATTED"]["EVENT"][$field]);
						}

						if (
							isset($arEvent["FIELDS_FORMATTED"]["CREATED_BY"])
							&& is_array($arEvent["FIELDS_FORMATTED"]["CREATED_BY"])
						)
						{
							$arFields2Cache = array(
								"TOOLTIP_FIELDS",
								"FORMATTED",
								"URL"
							);
							foreach ($arEvent["FIELDS_FORMATTED"]["CREATED_BY"] as $field => $value)
								if (!in_array($field, $arFields2Cache))
									unset($arEvent["FIELDS_FORMATTED"]["CREATED_BY"][$field]);

							if (
								isset($arEvent["FIELDS_FORMATTED"]["CREATED_BY"]["TOOLTIP_FIELDS"])
								&& is_array($arEvent["FIELDS_FORMATTED"]["CREATED_BY"]["TOOLTIP_FIELDS"])
							)
							{
								$arFields2Cache = array(
									"ID",
									"PATH_TO_SONET_USER_PROFILE",
									"NAME",
									"LAST_NAME",
									"SECOND_NAME",
									"LOGIN",
									"EMAIL"
								);
								foreach ($arEvent["FIELDS_FORMATTED"]["CREATED_BY"]["TOOLTIP_FIELDS"] as $field => $value)
									if (!in_array($field, $arFields2Cache))
										unset($arEvent["FIELDS_FORMATTED"]["CREATED_BY"]["TOOLTIP_FIELDS"][$field]);
							}
						}

						if (
							isset($arEvent["FIELDS_FORMATTED"]["ENTITY"])
							&& is_array($arEvent["FIELDS_FORMATTED"]["ENTITY"])
						)
						{
							$arFields2Cache = array(
								"TOOLTIP_FIELDS",
								"FORMATTED",
								"URL"
							);
							foreach ($arEvent["FIELDS_FORMATTED"]["ENTITY"] as $field => $value)
								if (!in_array($field, $arFields2Cache))
									unset($arEvent["FIELDS_FORMATTED"]["ENTITY"][$field]);

							if (
								isset($arEvent["FIELDS_FORMATTED"]["ENTITY"]["TOOLTIP_FIELDS"])
								&& is_array($arEvent["FIELDS_FORMATTED"]["ENTITY"]["TOOLTIP_FIELDS"])
							)
							{
								$arFields2Cache = array(
									"ID",
									"PATH_TO_SONET_USER_PROFILE",
									"NAME",
									"LAST_NAME",
									"SECOND_NAME",
									"LOGIN",
									"EMAIL"
								);
								foreach ($arEvent["FIELDS_FORMATTED"]["ENTITY"]["TOOLTIP_FIELDS"] as $field => $value)
									if (!in_array($field, $arFields2Cache))
										unset($arEvent["FIELDS_FORMATTED"]["ENTITY"]["TOOLTIP_FIELDS"][$field]);
							}
						}
					}
				}

				if ($arEvent["ENTITY_TYPE"] == SONET_ENTITY_GROUP)
					$path2Entity = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP"], array("group_id" => $arEvent["ENTITY_ID"]));
				else
					$path2Entity = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $arEvent["ENTITY_ID"]));

				$dateFormated = FormatDate(
					$GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE),
					MakeTimeStamp
					(
						is_array($arEvent["FIELDS_FORMATTED"])
						&& array_key_exists("EVENT_FORMATTED", $arEvent["FIELDS_FORMATTED"])
						&& is_array($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"])
						&& array_key_exists("LOG_DATE_FORMAT", $arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"])
							? $arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["LOG_DATE_FORMAT"]
							: (
								array_key_exists("LOG_DATE_FORMAT", $arEvent)
								? $arEvent["LOG_DATE_FORMAT"]
								: $arEvent["LOG_DATE"]
							)
					)
				);

				$timeFormated = FormatDateFromDB(
					(
						is_array($arEvent["FIELDS_FORMATTED"])
						&& array_key_exists("EVENT_FORMATTED", $arEvent["FIELDS_FORMATTED"])
						&& is_array($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"])
						&& array_key_exists("LOG_DATE_FORMAT", $arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"])
							? $arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["LOG_DATE_FORMAT"]
							: (
								array_key_exists("LOG_DATE_FORMAT", $arEvent) 
									? $arEvent["LOG_DATE_FORMAT"] 
									: $arEvent["LOG_DATE"]
							)
					), 
					(stripos($arParams["DATE_TIME_FORMAT"], 'a') || ($arParams["DATE_TIME_FORMAT"] == 'FULL' && IsAmPmMode()) !== false ? 'H:MI T' : 'HH:MI')
				);

				$dateTimeFormated = FormatDate(
					(
						!empty($arParams["DATE_TIME_FORMAT"]) 
							? ($arParams["DATE_TIME_FORMAT"] == "FULL" 
								? $GLOBALS["DB"]->DateFormatToPHP(str_replace(":SS", "", FORMAT_DATETIME)) 
								: $arParams["DATE_TIME_FORMAT"]
							) 
							: $GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATETIME)
					),
					MakeTimeStamp(
						is_array($arEvent["FIELDS_FORMATTED"])
						&& array_key_exists("EVENT_FORMATTED", $arEvent["FIELDS_FORMATTED"])
						&& is_array($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"])
						&& array_key_exists("LOG_DATE_FORMAT", $arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"])
							? $arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["LOG_DATE_FORMAT"]
							: (
								array_key_exists("LOG_DATE_FORMAT", $arEvent)
								? $arEvent["LOG_DATE_FORMAT"]
								: $arEvent["LOG_DATE"]
							)
					)
				);
				
				if (strcasecmp(LANGUAGE_ID, 'EN') !== 0 && strcasecmp(LANGUAGE_ID, 'DE') !== 0)
				{
					$dateTimeFormated = ToLower($dateTimeFormated);
					$dateFormated = ToLower($dateFormated);
				}
				// strip current year
				if (!empty($arParams['DATE_TIME_FORMAT']) && ($arParams['DATE_TIME_FORMAT'] == 'j F Y G:i' || $arParams['DATE_TIME_FORMAT'] == 'j F Y g:i a'))
				{
					$dateTimeFormated = ltrim($dateTimeFormated, '0');
					$curYear = date('Y');
					$dateTimeFormated = str_replace(array('-'.$curYear, '/'.$curYear, ' '.$curYear, '.'.$curYear), '', $dateTimeFormated);
				}

				$arEvent["MESSAGE_FORMAT"] = htmlspecialcharsback($arEvent["MESSAGE"]);
				if (StrLen($arEvent["CALLBACK_FUNC"]) > 0)
				{
					if (StrLen($arEvent["MODULE_ID"]) > 0)
						CModule::IncludeModule($arEvent["MODULE_ID"]);

					$arEvent["MESSAGE_FORMAT"] = call_user_func($arEvent["CALLBACK_FUNC"], $arEvent);
				}
				
				$arEvent["FIELDS_FORMATTED"]["LOG_TIME_FORMAT"] = $timeFormated;
				$arEvent["FIELDS_FORMATTED"]["LOG_UPDATE_TS"] = MakeTimeStamp($arEvent["LOG_UPDATE"]);

				$arEvent["FIELDS_FORMATTED"]["LOG_DATE_TS"] = MakeTimeStamp($arEvent["LOG_DATE"]);
				$arEvent["FIELDS_FORMATTED"]["LOG_DATE_DAY"] = ConvertTimeStamp(MakeTimeStamp($arEvent["LOG_DATE"]), "SHORT");
				$arEvent["FIELDS_FORMATTED"]["LOG_UPDATE_DAY"] = ConvertTimeStamp(MakeTimeStamp($arEvent["LOG_UPDATE"]), "SHORT");
				$arEvent["FIELDS_FORMATTED"]["COMMENTS_COUNT"] = $arEvent["COMMENTS_COUNT"];
				$arEvent["FIELDS_FORMATTED"]["TMP_ID"] = $arEvent["TMP_ID"];

				if (
					array_key_exists("EVENT_FORMATTED", $arEvent["FIELDS_FORMATTED"])
					&& is_array($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"])
					&& array_key_exists("LOG_DATE_FORMAT", $arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"])
				)
				{
					if (ConvertTimeStamp(MakeTimeStamp($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["LOG_DATE_FORMAT"]), "SHORT") == ConvertTimeStamp())
						$arEvent["FIELDS_FORMATTED"]["DATETIME_FORMATTED"] = $timeFormated;
					else
						$arEvent["FIELDS_FORMATTED"]["DATETIME_FORMATTED"] = $dateTimeFormated;
				}
				elseif ($arEvent["FIELDS_FORMATTED"]["LOG_DATE_DAY"] == ConvertTimeStamp())
					$arEvent["FIELDS_FORMATTED"]["DATETIME_FORMATTED"] = $timeFormated;
				else
					$arEvent["FIELDS_FORMATTED"]["DATETIME_FORMATTED"] = $dateTimeFormated;

				$arCommentEvent = CSocNetLogTools::FindLogCommentEventByLogEventID($arEvent["EVENT_ID"]);
				if (
					!array_key_exists("HAS_COMMENTS", $arEvent["FIELDS_FORMATTED"])
					|| $arEvent["FIELDS_FORMATTED"]["HAS_COMMENTS"] != "N"
				)
				{
					$arEvent["FIELDS_FORMATTED"]["HAS_COMMENTS"] = (
						$arCommentEvent
						&& (
							!array_key_exists("ENABLE_COMMENTS", $arEvent)
							|| $arEvent["ENABLE_COMMENTS"] != "N"
						)
							? "Y"
							: "N"
					);
				}
			}

			if (is_object($cache))
			{
				$arCacheData = Array(
					"FIELDS_FORMATTED" => $arEvent["FIELDS_FORMATTED"]
				);
				$cache->EndDataCache($arCacheData);
				if(defined("BX_COMP_MANAGED_CACHE"))
					$GLOBALS["CACHE_MANAGER"]->EndTagCache();
			}
		}

		$bCurrentUserIsAdmin = CSocNetUser::IsCurrentUserModuleAdmin();

		if (is_array($arCurrentUserSubscribe))
			$arEvent["FIELDS_FORMATTED"]["TRANSPORT"] = __SLEGetTransport($arEvent, $arCurrentUserSubscribe);

		$array_key = $arEvent["ENTITY_TYPE"]."_".$arEvent["ENTITY_ID"]."_".$arEvent["EVENT_ID"];
		if (array_key_exists($array_key, $GLOBALS["CurUserCanAddComments"]))
			$arEvent["FIELDS_FORMATTED"]["CAN_ADD_COMMENTS"] = ($GLOBALS["CurUserCanAddComments"][$array_key] == "Y" && $arEvent["FIELDS_FORMATTED"]["HAS_COMMENTS"] == "Y" ? "Y" : "N");
		else
		{
			$feature = CSocNetLogTools::FindFeatureByEventID($arEvent["EVENT_ID"]);
			if ($feature && $arCommentEvent && array_key_exists("OPERATION_ADD", $arCommentEvent) && strlen($arCommentEvent["OPERATION_ADD"]) > 0)
				$GLOBALS["CurUserCanAddComments"][$array_key] = (CSocNetFeaturesPerms::CanPerformOperation($GLOBALS["USER"]->GetID(), $arEvent["ENTITY_TYPE"], $arEvent["ENTITY_ID"], ($feature == "microblog" ? "blog" : $feature), $arCommentEvent["OPERATION_ADD"], $bCurrentUserIsAdmin) ? "Y" : "N");
			else
				$GLOBALS["CurUserCanAddComments"][$array_key] = "Y";

			$arEvent["FIELDS_FORMATTED"]["CAN_ADD_COMMENTS"] = (
				$GLOBALS["CurUserCanAddComments"][$array_key] == "Y" 
				&& $arEvent["FIELDS_FORMATTED"]["HAS_COMMENTS"] == "Y" 
					? "Y" 
					: "N"
			);
		}

		$arEvent["FIELDS_FORMATTED"]["FAVORITES"] = $arParams["EVENT"]["FAVORITES"];

		if ($arParams["USE_FOLLOW"] == "Y")
		{
			$arEvent["FIELDS_FORMATTED"]["EVENT"]["FOLLOW"] = $arParams["EVENT"]["FOLLOW"];
			$arEvent["FIELDS_FORMATTED"]["EVENT"]["DATE_FOLLOW_X1"] = $arParams["EVENT"]["DATE_FOLLOW_X1"];
			$arEvent["FIELDS_FORMATTED"]["EVENT"]["DATE_FOLLOW"] = $arParams["EVENT"]["DATE_FOLLOW"];
		}

		if (
			$arParams["CHECK_PERMISSIONS_DEST"] == "N"
			&& !$bCurrentUserIsAdmin
			&& is_object($GLOBALS["USER"])
			&& is_array($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"])
			&& (
				(
					array_key_exists("DESTINATION", $arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]) 
					&& is_array($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["DESTINATION"])
				)
				|| (
					array_key_exists("DESTINATION_CODE", $arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]) 
					&& is_array($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["DESTINATION_CODE"])
				)
			)
		)
		{
			$arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["DESTINATION_HIDDEN"] = 0;

			$arGroupID = array();

			if (!empty($GLOBALS["SONET_GROUPS_ID_AVAILABLE"]))
				$arGroupID = $GLOBALS["SONET_GROUPS_ID_AVAILABLE"];
			else
			{
				// get tagged cached available groups and intersect
				$cache = new CPHPCache;	
				$cache_id = $GLOBALS["USER"]->GetID();
				$cache_path = "/sonet/groups_available/";

				if (
					$cache->InitCache($cache_time, $cache_id, $cache_path)
				)
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

				$GLOBALS["SONET_GROUPS_ID_AVAILABLE"] = $arGroupID;
			}

			if (
				array_key_exists("DESTINATION", $arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]) 
				&& is_array($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["DESTINATION"])
			)
			{
				foreach($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["DESTINATION"] as $key => $arDestination)
				{
					if (
						array_key_exists("TYPE", $arDestination)
						&& array_key_exists("ID", $arDestination)
						&& $arDestination["TYPE"] == "SG"
						&& !in_array(intval($arDestination["ID"]), $arGroupID)
					)
					{
						unset($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["DESTINATION"][$key]);
						$arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["DESTINATION_HIDDEN"]++;
					}
				}

				if (
					intval($arParams["DESTINATION_LIMIT_SHOW"]) > 0
					&& count($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["DESTINATION"]) > $arParams["DESTINATION_LIMIT_SHOW"]
				)
				{
					$arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["DESTINATION_MORE"] = count($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["DESTINATION"]) - $arParams["DESTINATION_LIMIT_SHOW"];
					$arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["DESTINATION"] = array_slice($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["DESTINATION"], 0, $arParams["DESTINATION_LIMIT_SHOW"]);
				}
			}
			elseif (
				array_key_exists("DESTINATION_CODE", $arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]) 
				&& is_array($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["DESTINATION_CODE"])
			)
			{
				foreach($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["DESTINATION_CODE"] as $key => $right_tmp)
				{
					if (
						preg_match('/^SG(\d+)$/', $right_tmp, $matches)
						&& !in_array(intval($matches[1]), $arGroupID)						
					)
					{
						unset($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["DESTINATION_CODE"][$key]);
						$arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["DESTINATION_HIDDEN"]++;
					}
				}
			}
		}

		if (
			$arParams["SHOW_RATING"] == "Y"
			&& strlen($arEvent["FIELDS_FORMATTED"]["EVENT"]["RATING_TYPE_ID"]) > 0
			&& intval($arEvent["FIELDS_FORMATTED"]["EVENT"]["RATING_ENTITY_ID"]) > 0
		)
			$arEvent["FIELDS_FORMATTED"]["RATING"] = CRatings::GetRatingVoteResult($arEvent["FIELDS_FORMATTED"]["EVENT"]["RATING_TYPE_ID"], $arEvent["FIELDS_FORMATTED"]["EVENT"]["RATING_ENTITY_ID"]);

		return $arEvent["FIELDS_FORMATTED"];
	}
}

if (!function_exists('__SLEGetLogCommentRecord'))
{
	function __SLEGetLogCommentRecord($arComments, $arParams, $arCurrentUserSubscribe, $bTooltip = true)
	{
		// for the same post log_update - time only, if not - date and time
		$dateFormated = FormatDate(
			$GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE),
			MakeTimeStamp(array_key_exists("LOG_DATE_FORMAT", $arComments) 
				? $arComments["LOG_DATE_FORMAT"] 
				: $arComments["LOG_DATE"]
			)
		);
		$timeFormated = FormatDateFromDB(
			(array_key_exists("LOG_DATE_FORMAT", $arComments) 
				? $arComments["LOG_DATE_FORMAT"] 
				: $arComments["LOG_DATE"]
			),
			(stripos($arParams["DATE_TIME_FORMAT"], 'a') || ($arParams["DATE_TIME_FORMAT"] == 'FULL' && IsAmPmMode()) !== false 
				? 'H:MI T' 
				: 'HH:MI'
			)
		);
		$dateTimeFormated = FormatDate(
			(!empty($arParams['DATE_TIME_FORMAT']) 
				? ($arParams['DATE_TIME_FORMAT'] == 'FULL' 
					? $GLOBALS['DB']->DateFormatToPHP(str_replace(':SS', '', FORMAT_DATETIME)) 
					: $arParams['DATE_TIME_FORMAT']
				) 
				: $GLOBALS['DB']->DateFormatToPHP(FORMAT_DATETIME)
			),
			MakeTimeStamp(
				array_key_exists("LOG_DATE_FORMAT", $arComments) 
					? $arComments["LOG_DATE_FORMAT"] 
					: $arComments["LOG_DATE"]
			)
		);
		if (strcasecmp(LANGUAGE_ID, 'EN') !== 0 && strcasecmp(LANGUAGE_ID, 'DE') !== 0)
		{
			$dateFormated = ToLower($dateFormated);
			$dateTimeFormated = ToLower($dateTimeFormated);
		}
		// strip current year
		if (
			!empty($arParams['DATE_TIME_FORMAT']) 
			&& (
				$arParams['DATE_TIME_FORMAT'] == 'j F Y G:i' 
				|| $arParams['DATE_TIME_FORMAT'] == 'j F Y g:i a'
			)
		)
		{
			$dateTimeFormated = ltrim($dateTimeFormated, '0');
			$curYear = date('Y');
			$dateTimeFormated = str_replace(array('-'.$curYear, '/'.$curYear, ' '.$curYear, '.'.$curYear), '', $dateTimeFormated);
		}

		if ($arComments["ENTITY_TYPE"] == SONET_ENTITY_GROUP)
			$path2Entity = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP"], array("group_id" => $arComments["ENTITY_ID"]));
		else
			$path2Entity = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $arComments["ENTITY_ID"]));

		if (intval($arComments["USER_ID"]) > 0)
		{
			$suffix = (
				is_array($GLOBALS["arExtranetUserID"]) 
				&& in_array($arComments["USER_ID"], $GLOBALS["arExtranetUserID"]) 
					? GetMessage("SONET_LOG_EXTRANET_SUFFIX") 
					: ""
			);

			$arTmpUser = array(
				"NAME" => $arComments["~CREATED_BY_NAME"],
				"LAST_NAME" => $arComments["~CREATED_BY_LAST_NAME"],
				"SECOND_NAME" => $arComments["~CREATED_BY_SECOND_NAME"],
				"LOGIN" => $arComments["~CREATED_BY_LOGIN"]
			);
			$bUseLogin = $arParams["SHOW_LOGIN"] != "N" ? true : false;
			$arCreatedBy = array(
				"FORMATTED" => CUser::FormatName($arParams["NAME_TEMPLATE"], $arTmpUser, $bUseLogin).$suffix,
				"URL" => CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $arComments["USER_ID"], "id" => $arComments["USER_ID"]))
			);

			if ($bTooltip)
			{
				$arCreatedBy["TOOLTIP_FIELDS"] = array(
					"ID" => $arComments["USER_ID"],
					"NAME" => $arComments["~CREATED_BY_NAME"],
					"LAST_NAME" => $arComments["~CREATED_BY_LAST_NAME"],
					"SECOND_NAME" => $arComments["~CREATED_BY_SECOND_NAME"],
					"LOGIN" => $arComments["~CREATED_BY_LOGIN"],
					"USE_THUMBNAIL_LIST" => "N",
					"PATH_TO_SONET_MESSAGES_CHAT" => $arParams["PATH_TO_MESSAGES_CHAT"],
					"PATH_TO_SONET_USER_PROFILE" => $arParams["PATH_TO_USER"],
					"PATH_TO_VIDEO_CALL" => $arParams["PATH_TO_VIDEO_CALL"],
					"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
					"SHOW_YEAR" => $arParams["SHOW_YEAR"],
					"CACHE_TYPE" => $arParams["CACHE_TYPE"],
					"CACHE_TIME" => $arParams["CACHE_TIME"],
					"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"].$suffix,
					"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
					"PATH_TO_CONPANY_DEPARTMENT" => $arParams["PATH_TO_CONPANY_DEPARTMENT"],
					"INLINE" => "Y"
				);
			}
		}
		else
			$arCreatedBy = array("FORMATTED" => GetMessage("SONET_C73_CREATED_BY_ANONYMOUS"));

		$arTmpUser = array(
			"NAME" => $arComments["~USER_NAME"],
			"LAST_NAME" => $arComments["~USER_LAST_NAME"],
			"SECOND_NAME" => $arComments["~USER_SECOND_NAME"],
			"LOGIN" => $arComments["~USER_LOGIN"]
		);

		$arParamsTmp = $arParams;
		$arParamsTmp["AVATAR_SIZE"] = $arParams["AVATAR_SIZE_COMMENT"];

		$arTmpCommentEvent = array(
			"EVENT"	=> $arComments,
			"LOG_DATE" => $arComments["LOG_DATE"],
			"LOG_DATE_TS" => MakeTimeStamp($arComments["LOG_DATE"]),
			"LOG_DATE_DAY"	=> ConvertTimeStamp(MakeTimeStamp($arComments["LOG_DATE"]), "SHORT"),
			"LOG_TIME_FORMAT" => $timeFormated,
			"TITLE_TEMPLATE" => "",
			"TITLE" => "",
			"TITLE_FORMAT" => "", // need to use url here
			"ENTITY_NAME" => (($arComments["ENTITY_TYPE"] == SONET_ENTITY_GROUP) ? $arComments["GROUP_NAME"] : CUser::FormatName($arParams['NAME_TEMPLATE'], $arTmpUser, $bUseLogin)),
			"ENTITY_PATH" => $path2Entity,
			"CREATED_BY" => $arCreatedBy,
			"AVATAR_SRC" => CSocNetLogTools::FormatEvent_CreateAvatar($arComments, $arParamsTmp)
		);

//		if (is_array($arCurrentUserSubscribe) && $arParams["USER_COMMENTS"] != "Y")
//			$arTmpCommentEvent["TRANSPORT"] = __SLEGetTransport($arComments, $arCurrentUserSubscribe);

		$arEvent = CSocNetLogTools::FindLogCommentEventByID($arComments["EVENT_ID"]);

		if (
			$arEvent
			&& array_key_exists("CLASS_FORMAT", $arEvent)
			&& array_key_exists("METHOD_FORMAT", $arEvent)
		)
		{
			if ($arParams["USER_COMMENTS"] == "Y")
				$arLog = array();
			else
				$arLog = array(
					"TITLE" => $arComments["~LOG_TITLE"],
					"URL" => $arComments["~LOG_URL"],
					"PARAMS" => $arComments["~LOG_PARAMS"]
				);

			$arFIELDS_FORMATTED = call_user_func(array($arEvent["CLASS_FORMAT"], $arEvent["METHOD_FORMAT"]), $arComments, $arParams, false, $arLog);

			if ($arParams["USE_COMMENTS"] != "Y")
			{
				if (
					array_key_exists("CREATED_BY", $arFIELDS_FORMATTED)
					&& is_array($arFIELDS_FORMATTED["CREATED_BY"])
					&& array_key_exists("TOOLTIP_FIELDS", $arFIELDS_FORMATTED["CREATED_BY"])
				)
					$arTmpCommentEvent["CREATED_BY"]["TOOLTIP_FIELDS"] = $arFIELDS_FORMATTED["CREATED_BY"]["TOOLTIP_FIELDS"];
//				$arTmpCommentEvent["ENTITY"] = $arFIELDS_FORMATTED["ENTITY"];
			}
		}

		$message = (
			$arFIELDS_FORMATTED
			&& array_key_exists("EVENT_FORMATTED", $arFIELDS_FORMATTED)
			&& array_key_exists("MESSAGE", $arFIELDS_FORMATTED["EVENT_FORMATTED"])
				? $arFIELDS_FORMATTED["EVENT_FORMATTED"]["MESSAGE"]
				: $arTmpCommentEvent["EVENT"]["MESSAGE"]
		);

		if (strlen($message) > 0)
			$arFIELDS_FORMATTED["EVENT_FORMATTED"]["FULL_MESSAGE_CUT"] = CSocNetTextParser::closetags(htmlspecialcharsback($message));

		if (is_array($arTmpCommentEvent))
		{
			if ($arTmpCommentEvent["LOG_DATE_DAY"] == ConvertTimeStamp())
				$arFIELDS_FORMATTED["EVENT_FORMATTED"]["DATETIME"] = $timeFormated;
			else
				$arFIELDS_FORMATTED["EVENT_FORMATTED"]["DATETIME"] = $dateTimeFormated;

			$arTmpCommentEvent["EVENT_FORMATTED"] = $arFIELDS_FORMATTED["EVENT_FORMATTED"];

			if (
				isset($arTmpCommentEvent["EVENT_FORMATTED"])
				&& is_array($arTmpCommentEvent["EVENT_FORMATTED"])
			)
			{
				$arFields2Cache = array(
					"DATETIME",
					"MESSAGE",
					"FULL_MESSAGE_CUT",
					"ERROR_MSG"
				);
				foreach ($arTmpCommentEvent["EVENT_FORMATTED"] as $field => $value)
					if (!in_array($field, $arFields2Cache))
						unset($arTmpCommentEvent["EVENT_FORMATTED"][$field]);
			}

			if (
				isset($arTmpCommentEvent["EVENT"])
				&& is_array($arTmpCommentEvent["EVENT"])
			)
			{
				$arFields2Cache = array(
					"ID",
					"USER_ID",
					"LOG_DATE",
					"RATING_TYPE_ID",
					"RATING_ENTITY_ID"
				);
				foreach ($arTmpCommentEvent["EVENT"] as $field => $value)
					if (!in_array($field, $arFields2Cache))
						unset($arTmpCommentEvent["EVENT"][$field]);
			}

			if (
				isset($arTmpCommentEvent["CREATED_BY"])
				&& is_array($arTmpCommentEvent["CREATED_BY"])
			)
			{
				$arFields2Cache = array(
					"TOOLTIP_FIELDS",
					"FORMATTED",
					"URL"
				);
				foreach ($arTmpCommentEvent["CREATED_BY"] as $field => $value)
					if (!in_array($field, $arFields2Cache))
						unset($arTmpCommentEvent["CREATED_BY"][$field]);

				if (
					isset($arTmpCommentEvent["CREATED_BY"]["TOOLTIP_FIELDS"])
					&& is_array($arTmpCommentEvent["CREATED_BY"]["TOOLTIP_FIELDS"])
				)
				{
					$arFields2Cache = array(
						"ID",
						"PATH_TO_SONET_USER_PROFILE",
						"NAME",
						"LAST_NAME",
						"SECOND_NAME",
						"LOGIN",
						"EMAIL"
					);
					foreach ($arTmpCommentEvent["CREATED_BY"]["TOOLTIP_FIELDS"] as $field => $value)
						if (!in_array($field, $arFields2Cache))
							unset($arTmpCommentEvent["CREATED_BY"]["TOOLTIP_FIELDS"][$field]);
				}
			}
		}

		foreach($arTmpCommentEvent["EVENT"] as $key => $value)
			if (strpos($key, "~") === 0)
				unset($arTmpCommentEvent["EVENT"][$key]);

		return $arTmpCommentEvent;
	}
}

?>