<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (strlen($arResult["FatalError"]) > 0)
{
	?><span class='errortext'><?=$arResult["FatalError"]?></span><br /><br /><?
}
else
{
	$jsAjaxPage = CUtil::JSEscape($APPLICATION->GetCurPageParam("", array("bxajaxid", "logajax", "logout")));
	$randomString = RandString(8);
	$randomId = 0;

	if (!defined("SONET_LOG_JS"))
	{
		define("SONET_LOG_JS", true);

		$message = array(
			'sonetLEGetPath' => '/bitrix/components/bitrix/socialnetwork.log.entry/ajax.php',
			'sonetLESetPath' => '/bitrix/components/bitrix/socialnetwork.log.entry/ajax.php',
			'sonetLPathToUser' => $arParams["PATH_TO_USER"],
			'sonetLPathToGroup' => $arParams["PATH_TO_GROUP"],
			'sonetLPathToDepartment' => $arParams["PATH_TO_CONPANY_DEPARTMENT"],
			'sonetLPathToSmile' => $arParams["PATH_TO_SMILE"],
			'sonetLShowRating' => $arParams["SHOW_RATING"],
			'sonetLTextLikeY' => COption::GetOptionString("main", "rating_text_like_y", GetMessage("SONET_C30_TEXT_LIKE_Y")),
			'sonetLTextLikeN' => COption::GetOptionString("main", "rating_text_like_n", GetMessage("SONET_C30_TEXT_LIKE_N")),
			'sonetLTextLikeD' => COption::GetOptionString("main", "rating_text_like_d", GetMessage("SONET_C30_TEXT_LIKE_D")),
			'sonetLTextPlus' => GetMessage("SONET_C30_TEXT_PLUS"),
			'sonetLTextMinus' => GetMessage("SONET_C30_TEXT_MINUS"),
			'sonetLTextCancel' => GetMessage("SONET_C30_TEXT_CANCEL"),
			'sonetLTextAvailable' => GetMessage("SONET_C30_TEXT_AVAILABLE"),
			'sonetLTextDenied' => GetMessage("SONET_C30_TEXT_DENIED"),
			'sonetLTextRatingY' => GetMessage("SONET_C30_TEXT_RATING_YES"),
			'sonetLTextRatingN' => GetMessage("SONET_C30_TEXT_RATING_NO"),
			'sonetLPathToUserBlogPost' => $arParams["PATH_TO_USER_BLOG_POST"],
			'sonetLPathToGroupBlogPost' => $arParams["PATH_TO_GROUP_BLOG_POST"],
			'sonetLPathToUserMicroblogPost' => $arParams["PATH_TO_USER_MICROBLOG_POST"],
			'sonetLPathToGroupMicroblogPost' => $arParams["PATH_TO_GROUP_MICROBLOG_POST"],
			'sonetLForumID' => intval($arParams["FORUM_ID"]),
			'sonetLNameTemplate' => $arParams["NAME_TEMPLATE"],
			'sonetLDateTimeFormat' => $arParams["DATE_TIME_FORMAT"],
			'sonetLShowLogin' => $arParams["SHOW_LOGIN"],
			'sonetLRatingType' => $arParams["RATING_TYPE"],
			'sonetLCurrentUserID' => intval($GLOBALS["USER"]->GetID()),
			'sonetLAvatarSize' => $arParams["AVATAR_SIZE"],
			'sonetLAvatarSizeComment' => $arParams["AVATAR_SIZE_COMMENT"],
			'sonetLBlogAllowPostCode' => $arParams["BLOG_ALLOW_POST_CODE"],
			'sonetLDestinationHidden1' => GetMessage("SONET_C30_DESTINATION_HIDDEN_1"),
			'sonetLDestinationHidden2' => GetMessage("SONET_C30_DESTINATION_HIDDEN_2"),
			'sonetLDestinationHidden3' => GetMessage("SONET_C30_DESTINATION_HIDDEN_3"),
			'sonetLDestinationHidden4' => GetMessage("SONET_C30_DESTINATION_HIDDEN_4"),
			'sonetLDestinationHidden5' => GetMessage("SONET_C30_DESTINATION_HIDDEN_5"),
			'sonetLDestinationHidden6' => GetMessage("SONET_C30_DESTINATION_HIDDEN_6"),
			'sonetLDestinationHidden7' => GetMessage("SONET_C30_DESTINATION_HIDDEN_7"),
			'sonetLDestinationHidden8' => GetMessage("SONET_C30_DESTINATION_HIDDEN_8"),
			'sonetLDestinationHidden9' => GetMessage("SONET_C30_DESTINATION_HIDDEN_9"),
			'sonetLDestinationHidden0' => GetMessage("SONET_C30_DESTINATION_HIDDEN_0"),
			'sonetLDestinationLimit' => intval($arParams["DESTINATION_LIMIT_SHOW"]),
		);
		if ($arParams["USE_FOLLOW"] == "Y")
		{
			$message['sonetLFollowY'] = GetMessage("SONET_LOG_T_FOLLOW_Y");
			$message['sonetLFollowN'] = GetMessage("SONET_LOG_T_FOLLOW_N");
		}
		?><script>
			BX.message(<?echo CUtil::PhpToJSObject($message)?>);
		</script>
		<?
	}

	if(strlen($arResult["ErrorMessage"]) > 0)
	{
		?><span class='errortext'><?=$arResult["ErrorMessage"]?></span><br /><br /><?
	}

	if (
		$arResult["Event"]
		&& is_array($arResult["Event"])
		&& !empty($arResult["Event"])
	)
	{
		$arEvent = &$arResult["Event"];

		$ind = $arParams["IND"];
		$is_unread = $arParams["EVENT"]["IS_UNREAD"];

		if (
			isset($arEvent["EVENT_FORMATTED"]["URL"])
			&& $arEvent["EVENT_FORMATTED"]["URL"] !== ""
			&& $arEvent["EVENT_FORMATTED"]["URL"] !== false
		)
			$url = $arEvent["EVENT_FORMATTED"]["URL"];
		elseif (
			isset($arEvent["EVENT"]["URL"])
			&& $arEvent["EVENT"]["URL"] !== ""
			&& $arEvent["EVENT"]["URL"] !== false
		)
			$url = $arEvent["EVENT"]["URL"];
		else
			$url = "";

		$hasTitle24 = isset($arEvent["EVENT_FORMATTED"]["TITLE_24"])
			&& $arEvent["EVENT_FORMATTED"]["TITLE_24"] !== ""
			&& $arEvent["EVENT_FORMATTED"]["TITLE_24"] !== false;

		$hasTitle24_2 = isset($arEvent["EVENT_FORMATTED"]["TITLE_24_2"])
			&& $arEvent["EVENT_FORMATTED"]["TITLE_24_2"] !== ""
			&& $arEvent["EVENT_FORMATTED"]["TITLE_24_2"] !== false;

		?><div class="feed-post-block<?=($is_unread ? " feed-post-block-new" : "")?><?=(array_key_exists("EVENT_FORMATTED", $arEvent) && array_key_exists("STYLE", $arEvent["EVENT_FORMATTED"]) && strlen($arEvent["EVENT_FORMATTED"]["STYLE"]) > 0 ? " feed-".$arEvent["EVENT_FORMATTED"]["STYLE"] : "")?>">
			<div id="sonet_log_day_item_<?=$ind?>" class="feed-post-cont-wrap<?
			if (
				isset($arEvent["EVENT"]["USER_ID"])
				&& $arEvent["EVENT"]["USER_ID"] > 0
			)
			{
				?> sonet-log-item-createdby-<?=intval($arEvent["EVENT"]["USER_ID"])?><?
			}
			if (
				array_key_exists("ENTITY_TYPE", $arEvent["EVENT"])
				&& strlen($arEvent["EVENT"]["ENTITY_TYPE"]) > 0
				&& array_key_exists("ENTITY_ID", $arEvent["EVENT"])
				&& intval($arEvent["EVENT"]["ENTITY_ID"]) > 0
			)
			{
				?> sonet-log-item-where-<?=$arEvent["EVENT"]["ENTITY_TYPE"]?>-<?=intval($arEvent["EVENT"]["ENTITY_ID"])?>-all <?
				if (
					array_key_exists("EVENT_ID", $arEvent["EVENT"])
					&& strlen($arEvent["EVENT"]["EVENT_ID"]) > 0
				)
				{
					?> sonet-log-item-where-<?=$arEvent["EVENT"]["ENTITY_TYPE"]?>-<?=intval($arEvent["EVENT"]["ENTITY_ID"])?>-<?=str_replace("_", '-', $arEvent["EVENT"]["EVENT_ID"])?><?

					if (
						array_key_exists("EVENT_ID_FULLSET", $arEvent["EVENT"])
						&& strlen($arEvent["EVENT"]["EVENT_ID_FULLSET"]) > 0
					)
					{
						?> sonet-log-item-where-<?=$arEvent["EVENT"]["ENTITY_TYPE"]?>-<?=intval($arEvent["EVENT"]["ENTITY_ID"])?>-<?=str_replace("_", '-', $arEvent["EVENT"]["EVENT_ID_FULLSET"])?> <?
					}
				}
			}

			?>">
				<div class="feed-user-avatar"<?=(strlen($arEvent["AVATAR_SRC"]) > 0 ? " style=\"background:url('".$arEvent["AVATAR_SRC"]."') no-repeat center;\"" : "")?>></div>
				<div class="feed-post-title-block"><?
					$strDestination = "";
					if (
						is_array($arEvent["EVENT_FORMATTED"])
						&& array_key_exists("DESTINATION", $arEvent["EVENT_FORMATTED"])
						&& is_array($arEvent["EVENT_FORMATTED"]["DESTINATION"])
						&& !empty($arEvent["EVENT_FORMATTED"]["DESTINATION"])
					)
					{
						if (in_array($arEvent["EVENT"]["EVENT_ID"], array("system", "system_groups", "system_friends")))
						{
							$strDestination .= '<div class="feed-post-item">';

							if ($hasTitle24)
								$strDestination .= '<div class="feed-add-post-destination-title">'.$arEvent["EVENT_FORMATTED"]["TITLE_24"].'<span class="feed-add-post-destination-icon"></span></div>';

							foreach($arEvent["EVENT_FORMATTED"]["DESTINATION"] as $arDestination)
							{
								if (strlen($arDestination["URL"]) > 0)
									$strDestination .= '<a target="_self" href="'.$arDestination["URL"].'" class="feed-add-post-destination feed-add-post-destination-'.$arDestination["STYLE"].'"><span class="feed-add-post-destination-text">'.$arDestination["TITLE"].'</span></a>';
								else
									$strDestination .= '<span class="feed-add-post-destination feed-add-post-destination-'.$arDestination["STYLE"].'"><span class="feed-add-post-destination-text">'.$arDestination["TITLE"].'</span></span>';
							}
							$strDestination .= '</div>';
						}
						else
						{
							$strDestination .= ' <span class="feed-add-post-destination-icon"></span> ';
							$i = 0;
							foreach($arEvent["EVENT_FORMATTED"]["DESTINATION"] as $arDestination)
							{
								if ($i > 0)
									$strDestination .= ', ';

								if (strlen($arDestination["URL"]) > 0)
									$strDestination .= '<a class="feed-add-post-destination-new" href="'.$arDestination["URL"].'">'.$arDestination["TITLE"].'</a>';
								else
									$strDestination .= '<span class="feed-add-post-destination-new">'.$arDestination["TITLE"].'</span>';
								$i++;
							}
							if (intval($arEvent["EVENT_FORMATTED"]["DESTINATION_MORE"]) > 0)
							{
								if (
									($arEvent["EVENT_FORMATTED"]["DESTINATION_MORE"] % 100) > 10
									&& ($arEvent["EVENT_FORMATTED"]["DESTINATION_MORE"] % 100) < 20
								)
									$suffix = 5;
								else
									$suffix = $arEvent["EVENT_FORMATTED"]["DESTINATION_MORE"] % 10;

								$strDestination .= '<a class="feed-post-link-new" onclick="__logShowHiddenDestination('.$arEvent["EVENT"]["ID"].', '.(
									isset($arEvent["CREATED_BY"])
									&& is_array($arEvent["CREATED_BY"])
									&& isset($arEvent["CREATED_BY"]["TOOLTIP_FIELDS"])
									&& is_array($arEvent["CREATED_BY"]["TOOLTIP_FIELDS"])
									&& isset($arEvent["CREATED_BY"]["TOOLTIP_FIELDS"]["ID"])
										? intval($arEvent["CREATED_BY"]["TOOLTIP_FIELDS"]["ID"])
										: "false"
									).', this)" href="javascript:void(0)">'.str_replace("#COUNT#", $arEvent["EVENT_FORMATTED"]["DESTINATION_MORE"], GetMessage("SONET_C30_DESTINATION_MORE_".$suffix)).'</a>';
							}
							elseif (
								isset($arEvent["EVENT_FORMATTED"]["DESTINATION_HIDDEN"])
								&& intval($arEvent["EVENT_FORMATTED"]["DESTINATION_HIDDEN"]) > 0
							)
							{
								if (
									($arEvent["EVENT_FORMATTED"]["DESTINATION_HIDDEN"] % 100) > 10
									&& ($arEvent["EVENT_FORMATTED"]["DESTINATION_HIDDEN"] % 100) < 20
								)
									$suffix = 5;
								else
									$suffix = $arEvent["EVENT_FORMATTED"]["DESTINATION_HIDDEN"] % 10;

								$strDestination .= ' '.str_replace("#COUNT#", $arEvent["EVENT_FORMATTED"]["DESTINATION_HIDDEN"], GetMessage("SONET_C30_DESTINATION_HIDDEN_".$suffix));
							}
						}
					}

					$strCreatedBy = "";
					if (
						array_key_exists("CREATED_BY", $arEvent)
						&& is_array($arEvent["CREATED_BY"])
					)
					{
						if (
							array_key_exists("TOOLTIP_FIELDS", $arEvent["CREATED_BY"])
							&& is_array($arEvent["CREATED_BY"]["TOOLTIP_FIELDS"])
						)
						{
							$anchor_id = $randomString.($randomId++);
							$strCreatedBy .= '<a class="feed-post-user-name" id="anchor_'.$anchor_id.'" href="'.str_replace(array("#user_id#", "#USER_ID#", "#id#", "#ID#"), $arEvent["CREATED_BY"]["TOOLTIP_FIELDS"]["ID"], $arEvent["CREATED_BY"]["TOOLTIP_FIELDS"]["PATH_TO_SONET_USER_PROFILE"]).'">'.CUser::FormatName($arParams["NAME_TEMPLATE"], $arEvent["CREATED_BY"]["TOOLTIP_FIELDS"], ($arParams["SHOW_LOGIN"] != "N" ? true : false)).'</a>';
							$strCreatedBy .= '<script type="text/javascript">';
							$strCreatedBy .= 'BX.tooltip('.$arEvent["CREATED_BY"]["TOOLTIP_FIELDS"]["ID"].', "anchor_'.$anchor_id.'", "'.$jsAjaxPage.'");';
							$strCreatedBy .= '</script>';
						}
						elseif (
							array_key_exists("FORMATTED", $arEvent["CREATED_BY"])
							&& strlen($arEvent["CREATED_BY"]["FORMATTED"]) > 0
						)
						{
							$strCreatedBy .= '<span class="feed-post-user-name">'.$arEvent["CREATED_BY"]["FORMATTED"].'</span>';
						}
					}
					elseif (
						array_key_exists("ENTITY", $arEvent)
						&& (
							$arEvent["EVENT"]["EVENT_ID"] === "data"
							|| $arEvent["EVENT"]["EVENT_ID"] === "news"
						)
					)
					{
						if (
							array_key_exists("TOOLTIP_FIELDS", $arEvent["ENTITY"])
							&& is_array($arEvent["ENTITY"]["TOOLTIP_FIELDS"])
						)
						{
							$anchor_id = $randomString.($randomId++);
							$strCreatedBy .= '<a class="feed-post-user-name" id="anchor_'.$anchor_id.'" href="'.str_replace(array("#user_id#", "#USER_ID#", "#id#", "#ID#"), $arEvent["ENTITY"]["TOOLTIP_FIELDS"]["ID"], $arEvent["ENTITY"]["TOOLTIP_FIELDS"]["PATH_TO_SONET_USER_PROFILE"]).'">'.CUser::FormatName($arParams["NAME_TEMPLATE"], $arEvent["ENTITY"]["TOOLTIP_FIELDS"], ($arParams["SHOW_LOGIN"] != "N" ? true : false)).'</a>';
							$strCreatedBy .= '<script type="text/javascript">';
							$strCreatedBy .= 'BX.tooltip('.$arEvent["ENTITY"]["TOOLTIP_FIELDS"]["ID"].', "anchor_'.$anchor_id.'", "'.$jsAjaxPage.'");';
							$strCreatedBy .= '</script>';
						}
						elseif (
							array_key_exists("FORMATTED", $arEvent["ENTITY"])
							&& array_key_exists("NAME", $arEvent["ENTITY"]["FORMATTED"])
						)
						{
							if (array_key_exists("URL", $arEvent["ENTITY"]["FORMATTED"]) && strlen($arEvent["ENTITY"]["FORMATTED"]["URL"]) > 0)
								$strCreatedBy .= '<a href="'.$arEvent["ENTITY"]["FORMATTED"]["URL"].'" class="feed-post-user-name">'.$arEvent["ENTITY"]["FORMATTED"]["NAME"].'</a>';
							else
								$strCreatedBy .= '<span class="feed-post-user-name">'.$arEvent["ENTITY"]["FORMATTED"]["NAME"].'</span>';
						}
					}
					elseif (
						$arEvent["EVENT"]["EVENT_ID"] === "system"
						&& array_key_exists("ENTITY", $arEvent)
						&& array_key_exists("FORMATTED", $arEvent["ENTITY"])
						&& array_key_exists("NAME", $arEvent["ENTITY"]["FORMATTED"])
					)
					{
						if (array_key_exists("URL", $arEvent["ENTITY"]["FORMATTED"]) && strlen($arEvent["ENTITY"]["FORMATTED"]["URL"]) > 0)
							$strCreatedBy .= '<a href="'.$arEvent["ENTITY"]["FORMATTED"]["URL"].'" class="feed-post-user-name">'.$arEvent["ENTITY"]["FORMATTED"]["NAME"].'</a>';
						else
							$strCreatedBy .= '<span class="feed-post-user-name">'.$arEvent["ENTITY"]["FORMATTED"]["NAME"].'</span>';
					}

					?><?=($strCreatedBy != "" ? $strCreatedBy : "")?><?
					?><?=$strDestination?><?

					if (
						array_key_exists("EVENT_FORMATTED", $arEvent)
						&& ( $hasTitle24 || $hasTitle24_2 )
					)
					{
						if ($hasTitle24)
						{
							?><div class="feed-post-item"><?
							switch ($arEvent["EVENT"]["EVENT_ID"])
							{
							case "photo":
								?><div class="feed-add-post-destination-title"><span class="feed-add-post-files-title feed-add-post-p"><?=$arEvent["EVENT_FORMATTED"]["TITLE_24"]?></span></div><?
								break;
							case "timeman_entry":
								?><div class="feed-add-post-files-title"><?=$arEvent["EVENT_FORMATTED"]["TITLE_24"]?><a href="<?=$arEvent['ENTITY']['FORMATTED']['URL']?>" class="feed-work-time-link"><?=GetMessage("SONET_C30_MENU_ENTRY_TIMEMAN")?><span class="feed-work-time-icon"></span></a></div><?
								break;
							case "report":
								?><div class="feed-add-post-files-title"><?=$arEvent["EVENT_FORMATTED"]["TITLE_24"]?><a href="<?=$arEvent['ENTITY']['FORMATTED']['URL']?>" class="feed-work-time-link"><?=GetMessage("SONET_C30_MENU_ENTRY_REPORTS")?><span class="feed-work-time-icon"></span></a></div><?
								break;
							case "tasks":
								?><div class="feed-add-post-destination-title"><?=$arEvent["EVENT_FORMATTED"]["TITLE_24"]?><span class="feed-work-time"><?=GetMessage("SONET_C30_MENU_ENTRY_TASKS")?><span class="feed-work-time-icon"></span></span></div><?
								break;
							case "system":
							case "system_groups":
							case "system_friends":
								break;
							default:
								?><div class="feed-add-post-destination-title"><?=$arEvent["EVENT_FORMATTED"]["TITLE_24"]?></div><?
								break;
							}
							?></div><?
						}

						if (
							(
								!array_key_exists("IS_IMPORTANT", $arEvent["EVENT_FORMATTED"])
								|| !$arEvent["EVENT_FORMATTED"]["IS_IMPORTANT"]
							)
							&& $hasTitle24_2
						)
						{
							if ($url !== "")
							{
								?><div class="feed-post-title"><a href="<?=$url?>"><?=$arEvent["EVENT_FORMATTED"]["TITLE_24_2"]?></a></div><?
							}
							else
							{
								?><div class="feed-post-title"><?=$arEvent["EVENT_FORMATTED"]["TITLE_24_2"]?></div><?
							}
						}
					}

				?></div><? // title

				// body
				$EVENT_ID = $arEvent["EVENT"]["EVENT_ID"];
				if (
					array_key_exists("EVENT_FORMATTED", $arEvent)
					&& array_key_exists("IS_IMPORTANT", $arEvent["EVENT_FORMATTED"])
					&& $arEvent["EVENT_FORMATTED"]["IS_IMPORTANT"]
				)
				{
					?><div class="feed-info-block"><?

						if (
							array_key_exists("IS_IMPORTANT", $arEvent["EVENT_FORMATTED"])
							&& $arEvent["EVENT_FORMATTED"]["IS_IMPORTANT"]
							&& $hasTitle24_2
						)
						{
							if ($url !== "")
							{
								?><a href="<?=$url?>" class="feed-post-title"><?=$arEvent["EVENT_FORMATTED"]["TITLE_24_2"]?></a><?
							}
							else
							{
								?><div class="feed-post-title"><?=$arEvent["EVENT_FORMATTED"]["TITLE_24_2"]?></div><?
							}
						}

						?><div class="feed-post-text-block"><?=$arEvent["EVENT_FORMATTED"]["MESSAGE"]?><?if($arEvent["EVENT_FORMATTED"]["URL"]):?>&nbsp;<a href="<?=$arEvent["EVENT_FORMATTED"]["URL"]?>"><?=GetMessage("SONET_C30_MORE_IMPORTANT")?></a><?endif?><i class="feed-info-block-icon"></i></div><?
					?></div><?
				}
				elseif (
					$EVENT_ID === "files"
					|| $EVENT_ID === "commondocs"
				)
				{
					?><div class="feed-post-item feed-post-add-files">
						<div class="feed-add-post-files-title feed-add-post-f"><?=$arEvent["EVENT_FORMATTED"]["MESSAGE_TITLE_24"]?></div><?
						$file_ext = GetFileExtension($arEvent["EVENT"]["TITLE"]);
						?><div class="feed-files-cont">
							<span class="feed-com-file-wrap">
								<span class="feed-com-file-icon feed-file-icon-<?=$file_ext?>"></span><?
								if (
									array_key_exists("URL", $arEvent["EVENT"])
									&& strlen($arEvent["EVENT"]["URL"]) > 0
								)
								{
									?><span class="feed-com-file-name"><a href="<?=$arEvent["EVENT"]["URL"]?>"><?=$arEvent["EVENT"]["TITLE"]?></a></span><?
								}
								else
								{
									?><span class="feed-com-file-name"><?=$arEvent["EVENT"]["TITLE"]?></span><?
								}
								?><span class="feed-com-size"></span>
							</span>
						</div>
					</div><?
				}
				elseif (
					$EVENT_ID === "photo"
					|| $EVENT_ID === "photo_photo"
				)
				{
					?><div class="feed-post-item"><?

						$arPhotoItems = array();
						$photo_section_id = false;
						if ($EVENT_ID == "photo")
						{
							$photo_section_id = $arEvent["EVENT"]["SOURCE_ID"];
							if (strlen($arEvent["EVENT"]["PARAMS"]) > 0)
							{
								$arEventParams = unserialize(htmlspecialcharsback($arEvent["EVENT"]["PARAMS"]));
								if (
									$arEventParams
									&& is_array($arEventParams)
									&& array_key_exists("arItems", $arEventParams)
									&& is_array($arEventParams["arItems"])
								)
									$arPhotoItems = $arEventParams["arItems"];
							}
						}
						elseif ($EVENT_ID == "photo_photo")
						{
							if (intval($arEvent["EVENT"]["SOURCE_ID"]) > 0)
								$arPhotoItems = array($arEvent["EVENT"]["SOURCE_ID"]);

							if (strlen($arEvent["EVENT"]["PARAMS"]) > 0)
							{
								$arEventParams = unserialize(htmlspecialcharsback($arEvent["EVENT"]["PARAMS"]));
								if (
									$arEventParams
									&& is_array($arEventParams)
									&& array_key_exists("SECTION_ID", $arEventParams)
									&& intval($arEventParams["SECTION_ID"]) > 0
								)
									$photo_section_id = $arEventParams["SECTION_ID"];
							}
						}

						if (strlen($arEvent["EVENT"]["PARAMS"]) > 0)
						{
							$arEventParams = unserialize(htmlspecialcharsback($arEvent["EVENT"]["PARAMS"]));

							$photo_iblock_type = $arEventParams["IBLOCK_TYPE"];
							$photo_iblock_id = $arEventParams["IBLOCK_ID"];

							if (is_array($arEventParams) && array_key_exists("ALIAS", $arEventParams))
								$alias = $arEventParams["ALIAS"];
						else
								$alias = false;

							if ($EVENT_ID == "photo")
							{
								$photo_detail_url = $arEventParams["DETAIL_URL"];
								if ($photo_detail_url && IsModuleInstalled("extranet") && $arEvent["EVENT"]["ENTITY_TYPE"] == SONET_ENTITY_GROUP)
									$photo_detail_url = str_replace("#GROUPS_PATH#", $arResult["WORKGROUPS_PAGE"], $photo_detail_url);
							}
							elseif ($EVENT_ID == "photo_photo")
								$photo_detail_url = $arEvent["EVENT"]["URL"];

							if (!$photo_detail_url)
								$photo_detail_url = $arParams["PATH_TO_".($arEvent["EVENT"]["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "GROUP" : "USER")."_PHOTO_ELEMENT"];

							if (
								strlen($photo_iblock_type) > 0
								&& intval($photo_iblock_id) > 0
								&& intval($photo_section_id) > 0
								&& count($arPhotoItems) > 0
							)
							{
								?><?$APPLICATION->IncludeComponent(
									"bitrix:photogallery.detail.list.ex",
									"",
									Array(
										"IBLOCK_TYPE" => $photo_iblock_type,
										"IBLOCK_ID" => $photo_iblock_id,
										"SHOWN_PHOTOS" => (count($arPhotoItems) > $arParams["PHOTO_COUNT"]
											? array_slice($arPhotoItems, 0, $arParams["PHOTO_COUNT"])
											: $arPhotoItems
										),
										"DRAG_SORT" => "N",
										"MORE_PHOTO_NAV" => "N",

										"THUMBNAIL_SIZE" => $arParams["PHOTO_THUMBNAIL_SIZE"],
										"SHOW_CONTROLS" => "Y",
										"USE_RATING" => ($arParams["PHOTO_USE_RATING"] == "Y" || $arParams["SHOW_RATING"] == "Y" ? "Y" : "N"),
										"SHOW_RATING" => $arParams["SHOW_RATING"],
										"SHOW_SHOWS" => "N",
										"SHOW_COMMENTS" => "Y",
										"MAX_VOTE" => $arParams["PHOTO_MAX_VOTE"],
										"VOTE_NAMES" => isset($arParams["PHOTO_VOTE_NAMES"])? $arParams["PHOTO_VOTE_NAMES"]: Array(),
										"DISPLAY_AS_RATING" => $arParams["SHOW_RATING"] == "Y"? "rating_main": isset($arParams["PHOTO_DISPLAY_AS_RATING"])? $arParams["PHOTO_DISPLAY_AS_RATING"]: "rating",
										"RATING_MAIN_TYPE" => $arParams["SHOW_RATING"] == "Y"? $arParams["RATING_TYPE"]: "",

										"BEHAVIOUR" => "SIMPLE",
										"SET_TITLE" => "N",
										"CACHE_TYPE" => "A",
										"CACHE_TIME" => $arParams["CACHE_TIME"],
										"CACHE_NOTES" => "",
										"SECTION_ID" => $photo_section_id,
										"ELEMENT_LAST_TYPE"	=> "none",
										"ELEMENT_SORT_FIELD" => "ID",
										"ELEMENT_SORT_ORDER" => "asc",
										"ELEMENT_SORT_FIELD1" => "",
										"ELEMENT_SORT_ORDER1" => "asc",
										"PROPERTY_CODE" => array(),

										"INDEX_URL" => CComponentEngine::MakePathFromTemplate(
											$arParams["PATH_TO_".($arEvent["EVENT"]["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "GROUP" : "USER")."_PHOTO"],
											array(
												"user_id" => $arEvent["EVENT"]["ENTITY_ID"],
												"group_id" => $arEvent["EVENT"]["ENTITY_ID"]
											)
										),
										"DETAIL_URL" => CComponentEngine::MakePathFromTemplate(
											$photo_detail_url,
											array(
												"user_id" => $arEvent["EVENT"]["ENTITY_ID"],
												"group_id" => $arEvent["EVENT"]["ENTITY_ID"],
											)
										),
										"GALLERY_URL" => "",
										"SECTION_URL" => CComponentEngine::MakePathFromTemplate(
											$arParams["PATH_TO_".($arEvent["EVENT"]["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "GROUP" : "USER")."_PHOTO_SECTION"],
											array(
												"user_id" => $arEvent["EVENT"]["ENTITY_ID"],
												"group_id" => $arEvent["EVENT"]["ENTITY_ID"],
												"section_id" => ($EVENT_ID == "photo_photo" ? $photo_section_id : $arEvent["EVENT"]["SOURCE_ID"])
											)
										),
										"PATH_TO_USER" => $arParams["PATH_TO_USER"],
										"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
										"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],

										"USE_PERMISSIONS" => "N",
										"GROUP_PERMISSIONS" => array(),
										"PAGE_ELEMENTS" => $arParams["PHOTO_COUNT"],
										"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT_DETAIL"],
										"SET_STATUS_404" => "N",
										"ADDITIONAL_SIGHTS" => array(),
										"PICTURES_SIGHT" => "real",
										"USE_COMMENTS" => $arParams["PHOTO_USE_COMMENTS"],
										"COMMENTS_TYPE" => ($arParams["PHOTO_COMMENTS_TYPE"] == "blog" ? "blog" : "forum"),
										"FORUM_ID" => $arParams["PHOTO_FORUM_ID"],
										"BLOG_URL" => $arParams["PHOTO_BLOG_URL"],
										"USE_CAPTCHA" => $arParams["PHOTO_USE_CAPTCHA"],
										"SHOW_LINK_TO_FORUM" => "N",
										"IS_SOCNET" => "Y",
										"USER_ALIAS" => ($alias ? $alias : ($arEvent["EVENT"]["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "group" : "user")."_".$arEvent["EVENT"]["ENTITY_ID"]),
										//these two params below used to set action url and unique id - for any ajax actions
										"~UNIQUE_COMPONENT_ID" => 'bxfg_ucid_from_req_'.$photo_iblock_id.'_'.($EVENT_ID == "photo_photo" ? $photo_section_id : $arEvent["EVENT"]["SOURCE_ID"])."_".$arEvent["EVENT"]["ID"],
										"ACTION_URL" => CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_".($arEvent["EVENT"]["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "GROUP" : "USER")."_PHOTO_SECTION"], array("user_id" => $arEvent["EVENT"]["ENTITY_ID"],"group_id" => $arEvent["EVENT"]["ENTITY_ID"],"section_id" => ($EVENT_ID == "photo_photo" ? $photo_section_id : $arEvent["EVENT"]["SOURCE_ID"]))),
									),
									$component,
									array(
										"HIDE_ICONS" => "Y"
									)
								);?><?
							}
						}

					?></div><?
				}
				elseif ($EVENT_ID === "tasks")
				{
					?><div class="feed-post-info-block-wrap"><?=$arEvent["EVENT_FORMATTED"]["MESSAGE"]?></div><?
				}
				elseif (
					$EVENT_ID === "timeman_entry"
					|| $EVENT_ID === "report"
				)
				{
					?><div class="feed-post-text-block"><?=$arEvent["EVENT_FORMATTED"]["MESSAGE"]?></div><?
				}
				elseif (
					$EVENT_ID !== "system"
					&& $EVENT_ID !== "system_groups"
					&& $EVENT_ID !== "system_friends"
					&& strlen($arEvent["EVENT_FORMATTED"]["MESSAGE"]) > 0
				) // all other events
				{
					?><div class="feed-post-text-block">
						<div class="feed-post-text-block-inner"><div class="feed-post-text-block-inner-inner"><?=$arEvent["EVENT_FORMATTED"]["MESSAGE"]?></div></div>
						<div class="feed-post-text-more" onclick="__logEventExpand(this); return false;"><div class="feed-post-text-more-but"></div></div>
					</div><?
				}

				?><div class="feed-post-informers"><?
					if (
						array_key_exists("HAS_COMMENTS", $arEvent)
						&& $arEvent["HAS_COMMENTS"] == "Y"
						&& array_key_exists("CAN_ADD_COMMENTS", $arEvent)
						&& $arEvent["CAN_ADD_COMMENTS"] == "Y"
					)
					{
						$bHasComments = true;
						?><span class="feed-inform-comments"><?=(intval($arEvent["COMMENTS_COUNT"]) > 0 ? "<a href=".$url.">".GetMessage("SONET_C30_COMMENTS")."</a>" : "<a href=\"javascript:void(0);\" onclick=\"BX('feed_comments_block_".$arEvent["EVENT"]["ID"]."').style.display = 'block'; return __logShowCommentForm('".$arEvent["EVENT"]["ID"]."')\">".GetMessage("SONET_C30_COMMENT_ADD")."</a>")?></span><?
					}
					else
						$bHasComments = false;

					if (
						$arParams["SHOW_RATING"] == "Y"
						&& strlen($arEvent["EVENT"]["RATING_TYPE_ID"]) > 0
						&& intval($arEvent["EVENT"]["RATING_ENTITY_ID"]) > 0

					)
					{
						?><span class="feed-inform-ilike"><?
						$APPLICATION->IncludeComponent(
							"bitrix:rating.vote", $arParams["RATING_TYPE"],
							Array(
								"ENTITY_TYPE_ID" => $arEvent["EVENT"]["RATING_TYPE_ID"],
								"ENTITY_ID" => $arEvent["EVENT"]["RATING_ENTITY_ID"],
								"OWNER_ID" => $arEvent["CREATED_BY"]["TOOLTIP_FIELDS"]["ID"],
								"USER_VOTE" => $arEvent["RATING"]["USER_VOTE"],
								"USER_HAS_VOTED" => $arEvent["RATING"]["USER_HAS_VOTED"],
								"TOTAL_VOTES" => $arEvent["RATING"]["TOTAL_VOTES"],
								"TOTAL_POSITIVE_VOTES" => $arEvent["RATING"]["TOTAL_POSITIVE_VOTES"],
								"TOTAL_NEGATIVE_VOTES" => $arEvent["RATING"]["TOTAL_NEGATIVE_VOTES"],
								"TOTAL_VALUE" => $arEvent["RATING"]["TOTAL_VALUE"],
								"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER"]
							),
							$component,
							array("HIDE_ICONS" => "Y")
						);
						?></span><?
					}

					if (
						$bHasComments
						&& array_key_exists("FOLLOW", $arEvent["EVENT"])
					)
					{
						?><span class="feed-inform-follow" data-follow="<?=($arEvent["EVENT"]["FOLLOW"] == "Y" ? "Y" : "N")?>" id="log_entry_follow_<?=intval($arEvent["EVENT"]["ID"])?>" onclick="__logSetFollow(<?=$arEvent["EVENT"]["ID"]?>)"><a href="javascript:void(0);"><?=GetMessage("SONET_LOG_T_FOLLOW_".($arEvent["EVENT"]["FOLLOW"] == "Y" ? "Y" : "N"))?></a></span><?
					}

					?><span class="feed-post-time-wrap"><?
						if ($url !== "")
							echo '<a href="'.$url.'">';

						if (
							array_key_exists("EVENT_FORMATTED", $arEvent)
							&& array_key_exists("DATETIME_FORMATTED", $arEvent["EVENT_FORMATTED"])
							&& strlen($arEvent["EVENT_FORMATTED"]["DATETIME_FORMATTED"]) > 0
						)
							echo '<span class="feed-time">'.$arEvent["EVENT_FORMATTED"]["DATETIME_FORMATTED"].'</span>';
						elseif (
							array_key_exists("DATETIME_FORMATTED", $arEvent)
							&& strlen($arEvent["DATETIME_FORMATTED"]) > 0
						)
							echo '<span class="feed-time">'.$arEvent["DATETIME_FORMATTED"].'</span>';
						elseif ($arEvent["LOG_DATE_DAY"] == ConvertTimeStamp())
							echo '<span class="feed-time">'.$arEvent["LOG_TIME_FORMAT"].'</span>';
						else
							echo '<span class="feed-time">'.$arEvent["LOG_DATE_DAY"]." ".$arEvent["LOG_TIME_FORMAT"].'</span>';

						if ($url !== "")
							echo '</a>';

					?></span>
				</div><?

			?></div><? // cont_wrap

			if (
				isset($arEvent["HAS_COMMENTS"])
				&& $arEvent["HAS_COMMENTS"] == "Y"
			)
			{
				?><div class="feed-comments-block" id="feed_comments_block_<?=$arEvent["EVENT"]["ID"]?>" style="display: <?=(intval($arEvent["COMMENTS_COUNT"]) > 0 ? "block" : "none")?>"><?
					if (
						(intval($arEvent["COMMENTS_COUNT"]) > count($arEvent["COMMENTS"]))
						|| (empty($arEvent["COMMENTS"]) && $arParams["CREATED_BY_ID"] > 0)
					)
					{
						?><div class="feed-com-header">
							<div class="feed-com-all"><a href="javascript:void(0);" onclick="__logComments(<?=$arEvent["EVENT"]["ID"]?>, <?=intval($arResult["LAST_LOG_TS"] + $arResult["TZ_OFFSET"])?>, <?=($arEvent["EVENT"]["FOLLOW"] != "N" ? "true" : "false")?>);"><span class="feed-com-all-text"><?=GetMessage("SONET_C30_ALL_COMMENTS")?></span><span class="feed-comments-all-count"> (<?=intval($arEvent["COMMENTS_COUNT"])?>)</span><span class="feed-comments-all-hide" style="display: none;"><?=GetMessage("SONET_C30_ALL_COMMENTS_HIDE")?></span></a><i></i></div>
						</div>
						<div class="feed-comments-limited"><div class="feed-comments-limited-inner"><?
					}

					if (array_key_exists("COMMENTS", $arEvent) && !empty($arEvent["COMMENTS"]))
					{
						foreach($arEvent["COMMENTS"] as $arComment)
						{
							$ind_comment = $randomString.($randomId++);
							?><div class="feed-com-block<?
							
							
								if (isset($arComment["EVENT"]["LOG_DATE_TS"]))
									$event_date_log_ts = $arComment["EVENT"]["LOG_DATE_TS"];
								else
									$event_date_log_ts = (MakeTimeStamp($arComment["EVENT"]["LOG_DATE"]) - intval($arResult["TZ_OFFSET"]));

								echo (
									$GLOBALS["USER"]->IsAuthorized()
									&& $arEvent["EVENT"]["FOLLOW"] != "N"
									&& $arComment["EVENT"]["USER_ID"] != $GLOBALS["USER"]->GetID()
									&& intval($arResult["LAST_LOG_TS"]) > 0
									&& $event_date_log_ts > $arResult["LAST_LOG_TS"]
									&& ($arResult["COUNTER_TYPE"] == "**" || $arResult["COUNTER_TYPE"] == "blog_post")
										? " feed-com-block-new"
										: ""
								);?><?
							if (
								array_key_exists("USER_ID", $arComment["EVENT"])
								&& intval($arComment["EVENT"]["USER_ID"]) > 0
							)
							{
								?> sonet-log-comment-createdby-<?=$arComment["EVENT"]["USER_ID"]?><?
							}
							?>" id="sonet_log_comment_<?=$ind_comment?>">
								<div class="feed-com-avatar"<?=(strlen($arComment["AVATAR_SRC"]) > 0 ? " style=\"background:url('".$arComment["AVATAR_SRC"]."') no-repeat center #FFFFFF;\"" : "")?>></div><?

								if (
									isset($arComment["CREATED_BY"]["TOOLTIP_FIELDS"])
									&& is_array($arComment["CREATED_BY"]["TOOLTIP_FIELDS"])
								)
								{
									$anchor_id = $randomString.($randomId++);
									?><a class="feed-com-name" id="anchor_<?=$anchor_id?>" href="<?=str_replace(array("#user_id#", "#USER_ID#", "#id#", "#ID#"), $arComment["CREATED_BY"]["TOOLTIP_FIELDS"]["ID"], $arComment["CREATED_BY"]["TOOLTIP_FIELDS"]["PATH_TO_SONET_USER_PROFILE"])?>"><?=CUser::FormatName($arParams["NAME_TEMPLATE"], $arComment["CREATED_BY"]["TOOLTIP_FIELDS"], ($arParams["SHOW_LOGIN"] != "N" ? true : false))?></a><?
									?><script type="text/javascript">
										BX.tooltip(<?=$arComment["CREATED_BY"]["TOOLTIP_FIELDS"]["ID"]?>, "anchor_<?=$anchor_id?>", "<?=$jsAjaxPage?>");
									</script><?
								}
								elseif (
									isset($arComment["CREATED_BY"]["FORMATTED"])
									&& $arComment["CREATED_BY"]["FORMATTED"] !== ""
									&& $arComment["CREATED_BY"]["FORMATTED"] !== false
								)
									echo '<span class="feed-com-name">'.$arComment["CREATED_BY"]["FORMATTED"].'</span>';

								?><div class="feed-com-informers"><?
									echo (
										array_key_exists("EVENT_FORMATTED", $arComment)
										&& array_key_exists("DATETIME", $arComment["EVENT_FORMATTED"])
										&& strlen($arComment["EVENT_FORMATTED"]["DATETIME"]) > 0
											? '<span class="feed-time">'.$arComment["EVENT_FORMATTED"]["DATETIME"].'</span>'
											: ($arComment["LOG_DATE_DAY"] == ConvertTimeStamp() ? '<span class="feed-time">'.$arComment["LOG_TIME_FORMAT"]."</span>" : '<span class="feed-date">'.$arComment["LOG_DATE_DAY"]." ".$arComment["LOG_TIME_FORMAT"].'</span>')
									);

									if (
										strlen($arComment["EVENT"]["RATING_TYPE_ID"]) > 0
										&& $arComment["EVENT"]["RATING_ENTITY_ID"] > 0
										&& $arParams["SHOW_RATING"] == "Y"
									)
									{
										$RATING_ENTITY_ID = $arComment["EVENT"]["RATING_ENTITY_ID"];

										?><span class="sonet-log-comment-like rating_vote_text"><?
										$APPLICATION->IncludeComponent(
											"bitrix:rating.vote", $arParams["RATING_TYPE"],
											Array(
												"ENTITY_TYPE_ID" => $arComment["EVENT"]["RATING_TYPE_ID"],
												"ENTITY_ID" => $RATING_ENTITY_ID,
												"OWNER_ID" => $arComment["CREATED_BY"]["TOOLTIP_FIELDS"]["ID"],
												"USER_VOTE" => $arResult["RATING_COMMENTS"][$RATING_ENTITY_ID]["USER_VOTE"],
												"USER_HAS_VOTED" => $arResult["RATING_COMMENTS"][$RATING_ENTITY_ID]["USER_HAS_VOTED"],
												"TOTAL_VOTES" => $arResult["RATING_COMMENTS"][$RATING_ENTITY_ID]["TOTAL_VOTES"],
												"TOTAL_POSITIVE_VOTES" => $arResult["RATING_COMMENTS"][$RATING_ENTITY_ID]["TOTAL_POSITIVE_VOTES"],
												"TOTAL_NEGATIVE_VOTES" => $arResult["RATING_COMMENTS"][$RATING_ENTITY_ID]["TOTAL_NEGATIVE_VOTES"],
												"TOTAL_VALUE" => $arResult["RATING_COMMENTS"][$RATING_ENTITY_ID]["TOTAL_VALUE"],
												"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER"],
											),
											$component,
											array("HIDE_ICONS" => "Y")
										);
										?></span><?
									}
								?></div>
								<div class="feed-com-text">
									<div class="feed-com-text-inner"><div class="feed-com-text-inner-inner"><?
										echo (array_key_exists("FULL_MESSAGE_CUT", $arComment["EVENT_FORMATTED"]) ? $arComment["EVENT_FORMATTED"]["FULL_MESSAGE_CUT"] : "");
									?></div></div>
									<div class="feed-post-text-more" onclick="__logCommentExpand(this);"><div class="feed-post-text-more-but"><div class="feed-post-text-more-left"></div><div class="feed-post-text-more-right"></div></div></div>
								</div>
							</div><?
						}
					}

					if (
						(intval($arEvent["COMMENTS_COUNT"]) > count($arEvent["COMMENTS"]))
						|| (empty($arEvent["COMMENTS"]) && intval($arParams["CREATED_BY_ID"]) > 0)
					)
					{
						?></div></div>
						<div class="feed-comments-full" style="display:none"><div class="feed-comments-full-inner"></div></div><?
					}

					if (
						isset($arEvent["HAS_COMMENTS"])
						&& $arEvent["HAS_COMMENTS"] === "Y"
						&& isset($arEvent["CAN_ADD_COMMENTS"])
						&& $arEvent["CAN_ADD_COMMENTS"] === "Y"
					)
					{
						?><div class="feed-com-footer" onclick="return __logShowCommentForm('<?=$arEvent["EVENT"]["ID"]?>')"><?=GetMessage("SONET_C30_COMMENT_ADD")?></div>
						<div class="sonet-log-comment-form-place" id="sonet_log_comment_form_place_<?=$arEvent["EVENT"]["ID"]?>"></div><?
					}
					?><div class="feed-com-corner"></div>
				</div><?
			}

			if ($GLOBALS["USER"]->IsAuthorized())
			{
				?><div class="feed-post-menu-wrap">
					<div class="feed-post-menu-but" onclick="__logShowPostMenu(this, '<?=$ind?>', '<?=$arEvent["EVENT"]["ENTITY_TYPE"] ?>', <?=$arEvent["EVENT"]["ENTITY_ID"] ?>, '<?=$arEvent["EVENT"]["EVENT_ID"] ?>', <?=($arEvent["EVENT"]["EVENT_ID_FULLSET"] ? "'".$arEvent["EVENT"]["EVENT_ID_FULLSET"]."'" : "false")?>, '<?=$arEvent["EVENT"]["USER_ID"] ?>', '<?=$arEvent["EVENT"]["ID"] ?>', <?=(array_key_exists("FAVORITES", $arEvent) && $arEvent["FAVORITES"] == "Y" ? "true" : "false")?>);" onmouseup="BX.removeClass(this,'feed-post-menu-but-active')" onmousedown="BX.addClass(this,'feed-post-menu-but-active')"></div>
				</div><?
			}
		?></div><?
	}
}
?>