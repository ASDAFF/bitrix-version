<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?CUtil::InitJSCore(array("tooltip", "popup", "image"));?>
<?
$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/socialnetwork.log.ex/templates/.default/style.css');
$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/socialnetwork.blog.blog/templates/.default/style.css');
$ajax_page = $APPLICATION->GetCurPageParam("", array("logajax", "bxajaxid", "logout"));

?><div class="feed-wrap"><?
if(strlen($arResult["MESSAGE"])>0)
{
	?><div class="feed-add-successfully">
		<span class="feed-add-info-text"><span class="feed-add-info-icon"></span><?=$arResult["MESSAGE"]?></span>
	</div><?
}
if(strlen($arResult["ERROR_MESSAGE"])>0)
{
	?><div class="feed-add-error">
		<span class="feed-add-info-text"><span class="feed-add-info-icon"></span><?=$arResult["ERROR_MESSAGE"]?></span>
	</div><?
}
if(strlen($arResult["FATAL_MESSAGE"])>0)
{
	if(!$arResult["bFromList"])
	{
		?><div class="feed-add-error">
			<span class="feed-add-info-text"><span class="feed-add-info-icon"></span><?=$arResult["FATAL_MESSAGE"]?></span>
		</div><?
	}
}
elseif(strlen($arResult["NOTE_MESSAGE"])>0)
{
	?><div class="feed-add-successfully">
		<span class="feed-add-info-text"><span class="feed-add-info-icon"></span><?=$arResult["NOTE_MESSAGE"]?></span>
	</div><?
}
else
{
	if(!empty($arResult["Post"])>0)
	{
		$APPLICATION->IncludeComponent("bitrix:main.user.link",
			'',
			array(
				"AJAX_ONLY" => "Y",
				"PATH_TO_SONET_USER_PROFILE" => $arParams["~PATH_TO_USER"],
				"PATH_TO_SONET_MESSAGES_CHAT" => $arParams["~PATH_TO_MESSAGES_CHAT"],
				"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
				"SHOW_YEAR" => $arParams["SHOW_YEAR"],
				"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
				"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
				"PATH_TO_CONPANY_DEPARTMENT" => $arParams["~PATH_TO_CONPANY_DEPARTMENT"],
				"PATH_TO_VIDEO_CALL" => $arParams["~PATH_TO_VIDEO_CALL"],
				"HTML_ID" => "user".$arResult["Post"]["ID"],
			),
			false,
			array("HIDE_ICONS" => "Y")
		);

		$className = "feed-post-block";
		if($arResult["Post"]["new"] == "Y")
			$className .= " feed-post-block-new";
		if(strlen($_REQUEST["bxajaxid"]) <= 0 && strlen($_REQUEST["logajax"]) <= 0)
			include_once($_SERVER["DOCUMENT_ROOT"].$templateFolder."/script.php");

		if (
			array_key_exists("FOLLOW", $arParams)
			&& strlen($arParams["FOLLOW"]) > 0
			&& intval($arParams["LOG_ID"]) > 0
		):
			?><script>
			BX.message({
				sonetBPSetPath: '<?=CUtil::JSEscape("/bitrix/components/bitrix/socialnetwork.log.ex/ajax.php")?>',
				sonetBPSiteId: '<?=CUtil::JSEscape(SITE_ID)?>',
				sonetBPFollowY: '<?=GetMessageJS("BLOG_POST_FOLLOW_Y")?>',
				sonetBPFollowN: '<?=GetMessageJS("BLOG_POST_FOLLOW_N")?>'
			});
			</script><?
		endif;
		?>
		<script>
		BX.viewImageBind(
			'blg-post-img-<?=$arResult["Post"]["ID"]?>',
			{showTitle: false},
			{tag:'IMG', attr: 'data-bx-image'}
		);
		</script>
		<div class="<?=$className?>" id="blg-post-<?=$arResult["Post"]["ID"]?>">
			<?
			$aditStyles = ($arResult["Post"]["hidden"] == "Y" ? " feed-hidden-post" : "");

			if (array_key_exists("USER_ID", $arParams) && intval($arParams["USER_ID"]) > 0)
				$aditStyles .= " sonet-log-item-createdby-".$arParams["USER_ID"];

			if (array_key_exists("ENTITY_TYPE", $arParams) && strlen($arParams["ENTITY_TYPE"]) > 0 && array_key_exists("ENTITY_ID", $arParams) && intval($arParams["ENTITY_ID"]) > 0 )
			{
				$aditStyles .= " sonet-log-item-where-".$arParams["ENTITY_TYPE"]."-".$arParams["ENTITY_ID"]."-all";
				if (array_key_exists("EVENT_ID", $arParams) && strlen($arParams["EVENT_ID"]) > 0)
				{
					$aditStyles .= " sonet-log-item-where-".$arParams["ENTITY_TYPE"]."-".$arParams["ENTITY_ID"]."-".str_replace("_", '-', $arParams["EVENT_ID"]);
					if (array_key_exists("EVENT_ID_FULLSET", $arParams) && strlen($arParams["EVENT_ID_FULLSET"]) > 0)
						$aditStyles .= " sonet-log-item-where-".$arParams["ENTITY_TYPE"]."-".$arParams["ENTITY_ID"]."-".str_replace("_", '-', $arParams["EVENT_ID_FULLSET"]);
				}
			}
			?>
			<div class="feed-post-cont-wrap<?=$aditStyles?>" id="blg-post-img-<?=$arResult["Post"]["ID"]?>">
				<div class="feed-user-avatar"<?=(strlen($arResult["arUser"]["PERSONAL_PHOTO_resized"]["src"]) > 0 ? " style=\"background:url('".$arResult["arUser"]["PERSONAL_PHOTO_resized"]["src"]."') no-repeat center;\"" : "")?>></div>
				<div class="feed-post-title-block"><?
					$anchor_id = $arResult["Post"]["ID"];
					$arTmpUser = array(
							"NAME" => $arResult["arUser"]["~NAME"],
							"LAST_NAME" => $arResult["arUser"]["~LAST_NAME"],
							"SECOND_NAME" => $arResult["arUser"]["~SECOND_NAME"],
							"LOGIN" => $arResult["arUser"]["~LOGIN"],
							"NAME_LIST_FORMATTED" => "",
						);

					if($arParams["SEO_USER"] == "Y"):
						?><noindex><?
					endif;
					?><a class="feed-post-user-name" id="bp_<?=$anchor_id?>" href="<?=$arResult["arUser"]["url"]?>"><?=CUser::FormatName($arParams["NAME_TEMPLATE"], $arTmpUser, ($arParams["SHOW_LOGIN"] != "N" ? true : false))?></a>
					<script type="text/javascript">
						BX.tooltip('<?=$arResult["arUser"]["ID"]?>', "bp_<?=$anchor_id?>", "<?=CUtil::JSEscape($ajax_page)?>");
					</script><?
					if($arParams["SEO_USER"] == "Y"):
						?></noindex><?
					endif;
					if(!empty($arResult["Post"]["SPERM"]))
					{
						?><span class="feed-add-post-destination-icon"></span><?
						$cnt = count($arResult["Post"]["SPERM"]["U"]) + count($arResult["Post"]["SPERM"]["SG"]) + count($arResult["Post"]["SPERM"]["DR"]);
						$i = 0;
						if(!empty($arResult["Post"]["SPERM"]["U"]))
						{
							foreach($arResult["Post"]["SPERM"]["U"] as $id => $val)
							{
								$i++;
								if($i == 4)
								{
									$more_cnt = $cnt - 3;
									if (
										($more_cnt % 100) > 10
										&& ($more_cnt % 100) < 20
									)
										$suffix = 5;
									else
										$suffix = $more_cnt % 10;								
										
									?><a href="javascript:void(0)" onclick="showHiddenDestination('<?=$arResult["Post"]["ID"]?>', this)" class="feed-post-link-new"><?=GetMessage("BLOG_DESTINATION_MORE_".$suffix, Array("#NUM#" => $more_cnt))?></a><span id="blog-destination-hidden-<?=$arResult["Post"]["ID"]?>" style="display:none;"><?
								}
								if($i != 1)
									echo ", ";
								if($val["NAME"] != "All")
								{
									$anchor_id = $arResult["Post"]["ID"]."_".$id;
									?><a id="dest_<?=$anchor_id?>" href="<?=$val["URL"]?>" class="feed-add-post-destination-new"><?=$val["NAME"]?></a><script type="text/javascript">BX.tooltip('<?=$val["ID"]?>', "dest_<?=$anchor_id?>", "<?=CUtil::JSEscape($ajax_page)?>");</script><?
								}
								else
								{
									?><span class="feed-add-post-destination-new"><?=(IsModuleInstalled("intranet") ? GetMessage("BLOG_DESTINATION_ALL") : GetMessage("BLOG_DESTINATION_ALL_BSM"))?></span><?
								}
							}
						}
						if(!empty($arResult["Post"]["SPERM"]["SG"]))
						{
							foreach($arResult["Post"]["SPERM"]["SG"] as $id => $val)
							{
								$i++;
								if($i == 4)
								{
									$more_cnt = $cnt - 3;
									if (
										($more_cnt % 100) > 10
										&& ($more_cnt % 100) < 20
									)
										$suffix = 5;
									else
										$suffix = $more_cnt % 10;

									?><a href="javascript:void(0)" onclick="showHiddenDestination('<?=$arResult["Post"]["ID"]?>', this)" class="feed-post-link-new"><?=GetMessage("BLOG_DESTINATION_MORE_".$suffix, Array("#NUM#" => $more_cnt))?></a><span id="blog-destination-hidden-<?=$arResult["Post"]["ID"]?>" style="display:none;"><?
								}
								if($i != 1)
									echo ", ";
								?><a href="<?=$val["URL"]?>" class="feed-add-post-destination-new"><?=$val["NAME"]?></a><?
							}
						}
						if(!empty($arResult["Post"]["SPERM"]["DR"]))
						{
							foreach($arResult["Post"]["SPERM"]["DR"] as $id => $val)
							{
								$i++;
								if($i == 4)
								{
									$more_cnt = $cnt - 3;
									if (
										($more_cnt % 100) > 10
										&& ($more_cnt % 100) < 20
									)
										$suffix = 5;
									else
										$suffix = $more_cnt % 10;

									?><a href="javascript:void(0)" onclick="showHiddenDestination('<?=$arResult["Post"]["ID"]?>', this)" class="feed-post-link-new"><?=GetMessage("BLOG_DESTINATION_MORE_".$suffix, Array("#NUM#" => $more_cnt))?></a><span id="blog-destination-hidden-<?=$arResult["Post"]["ID"]?>" style="display:none;"><?
								}

								if($i != 1)
									echo ", ";
								?><span class="feed-add-post-destination-new"><?=$val["NAME"]?></span><?
							}
						}
						if (
							isset($arResult["Post"]["SPERM_HIDDEN"])
							&& intval($arResult["Post"]["SPERM_HIDDEN"]) > 0
						)
						{
							if (
								($arResult["Post"]["SPERM_HIDDEN"] % 100) > 10
								&& ($arResult["Post"]["SPERM_HIDDEN"] % 100) < 20
							)
								$suffix = 5;
							else
								$suffix = $arResult["Post"]["SPERM_HIDDEN"] % 10;

							?><span class="feed-add-post-destination-new">&nbsp;<?=GetMessage("BLOG_DESTINATION_HIDDEN_".$suffix, Array("#NUM#" => intval($arResult["Post"]["SPERM_HIDDEN"])))?></span><?
						}

						if($i > 3)
							echo "</span>";
					}

					if(strlen($arResult["urlToEdit"]) > 0)
					{
						?><a href="<?=$arResult["urlToEdit"]?>" title="<?=GetMessage("BLOG_BLOG_BLOG_EDIT")?>"><span class="feed-destination-edit" onclick="BX.addClass(this, 'feed-destination-edit-pressed');"></span></a>
						<?
					}

					if($arResult["Post"]["MICRO"] != "Y")
					{
						?><div class="feed-post-item"><a class="feed-post-title" href="<?=$arResult["Post"]["urlToPost"]?>"><?=$arResult["Post"]["TITLE"]?></a></div><?
					}
				?></div>
				<div class="feed-post-text-block">
					<div class="<?if($arResult["bFromList"]):?>feed-post-text-block-inner<?endif;?>">
						<div class="feed-post-text-block-inner-inner"><?=$arResult["Post"]["textFormated"]?><?
						if ($arResult["Post"]["CUT"] == "Y")
						{
							?><div><a class="blog-postmore-link" href="<?=$arResult["Post"]["urlToPost"]?>"><?=GetMessage("BLOG_BLOG_BLOG_MORE")?></a></div><?
						}
						?></div>
					</div><?
					if($arResult["bFromList"]):
						?><div class="feed-post-text-more" onclick="showBlogPost('<?=$arResult["Post"]["ID"]?>', this)">
							<div class="feed-post-text-more-but"></div>
						</div><?
					endif;
				?></div><?

				if(!empty($arResult["images"]))
				{
					?><div class="feed-com-files">
						<div class="feed-com-files-title"><?=GetMessage("BLOG_PHOTO")?></div>
						<div class="feed-com-files-cont"><?
							foreach($arResult["images"] as $val)
							{
								?><span class="feed-com-files-photo"><img src="<?=$val["small"]?>" alt="" border="0" data-bx-image="<?=$val["full"]?>" /></span><?
							}
						?></div>
					</div><?
				}

				if($arResult["POST_PROPERTIES"]["SHOW"] == "Y")
				{
					$eventHandlerID = false;
					$eventHandlerID = AddEventHandler('main', 'system.field.view.file', Array('CBlogTools', 'blogUFfileShow'));
					foreach ($arResult["POST_PROPERTIES"]["DATA"] as $FIELD_NAME => $arPostField)
					{
						if(!empty($arPostField["VALUE"]))
						{
							?><?$APPLICATION->IncludeComponent(
								"bitrix:system.field.view",
								$arPostField["USER_TYPE"]["USER_TYPE_ID"],
								array(
									"arUserField" => $arPostField,
									"arAddField" => array("NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"], "PATH_TO_USER" => $arParams["~PATH_TO_USER"])
								), null, array("HIDE_ICONS"=>"Y")
							);?><?
						}
					}
					if ($eventHandlerID !== false && ( intval($eventHandlerID) > 0 ))
						RemoveEventHandler('main', 'system.field.view.file', $eventHandlerID);
				}

				if(!empty($arResult["Category"]))
				{
					?><div class="feed-com-tags-block">
						<noindex>
						<div class="feed-com-files-title"><?=GetMessage("BLOG_BLOG_BLOG_CATEGORY")?></div>
						<div class="feed-com-files-cont"><?
							$i=0;
							foreach($arResult["Category"] as $v)
							{
								if($i!=0)
									echo ",";
								?> <a href="<?=$v["urlToCategory"]?>" rel="nofollow" class="feed-com-tag"><?=$v["NAME"]?></a><?
								$i++;
							}
						?></div>
						</noindex>
					</div><?
				}

				if (!empty($arResult["GRATITUDE"]))
				{
					$grat_users_count = count($arResult["GRATITUDE"]["USERS_FULL"]);

					?><div class="feed-grat-block feed-info-block<?=($grat_users_count > 4 ? " feed-grat-block-small" : " feed-grat-block-large")?>"><?

					if ($grat_users_count <= 4)
					{
						?><span class="feed-workday-left-side"><?
							?><div class="feed-grat-img<?=(is_array($arResult["GRATITUDE"]["TYPE"]) ? " feed-grat-img-".htmlspecialcharsbx($arResult["GRATITUDE"]["TYPE"]["XML_ID"]) : "")?>"></div><?
							?><div class="feed-grat-block-arrow"></div><?
							?><div class="feed-user-name-wrap-outer"><?
								foreach($arResult["GRATITUDE"]["USERS_FULL"] as $arGratUser)
								{
									$anchor_id = 'post_grat_'.$arGratUser["ID"].'_'.RandString(5);
									?><span class="feed-user-name-wrap"><?
										?><div <?if($arGratUser['AVATAR_SRC']):?> style="background: url('<?=$arGratUser['AVATAR_SRC']?>') no-repeat center center;"<?endif?> class="feed-user-avatar"></div><?
										?><div class="feed-user-name-wrap-inner"><?
											?><a class="feed-workday-user-name" href="<?=$arGratUser['URL']?>" id="<?=$anchor_id?>"><?=CUser::FormatName($arParams['NAME_TEMPLATE'], $arGratUser)?></a><?
											?><span class="feed-workday-user-position"><?=htmlspecialcharsbx($arGratUser['WORK_POSITION'])?></span><?
										?></div><?
									?></span><?
									?><script type="text/javascript">BX.tooltip('<?=$arGratUser['ID']?>', '<?=$anchor_id?>', '<?=$APPLICATION->GetCurPageParam("", array("logajax", "bxajaxid", "logout"));?>');</script><?
								}
							?></div><?
						?></span><?
					}
					else
					{
						?><div class="feed-grat-small-left"><?
							?><div class="feed-grat-img<?=(is_array($arResult["GRATITUDE"]["TYPE"]) ? " feed-grat-img-".htmlspecialcharsbx($arResult["GRATITUDE"]["TYPE"]["XML_ID"]) : "")?>"></div><?
							?><div class="feed-grat-block-arrow"></div><?
						?></div><?
						?><div class="feed-grat-small-block-names"><?
							foreach($arResult["GRATITUDE"]["USERS_FULL"] as $arGratUser)
							{
								$anchor_id = 'post_grat_'.$arGratUser["ID"].'_'.RandString(5);
								?><span class="feed-user-name-wrap"><?
									?><div <?if($arGratUser['AVATAR_SRC']):?> style="background: url('<?=$arGratUser['AVATAR_SRC']?>') no-repeat center center;"<?endif?> class="feed-user-avatar"></div><?
									?><a class="feed-workday-user-name" href="<?=$arGratUser['URL']?>" id="<?=$anchor_id?>"><?=CUser::FormatName($arParams['NAME_TEMPLATE'], $arGratUser)?></a><?
									?><!--<span class="feed-workday-user-position"><?=htmlspecialcharsbx($arGratUser['WORK_POSITION'])?></span>--><?
								?></span><?
								?><script type="text/javascript">BX.tooltip('<?=$arGratUser['ID']?>', '<?=$anchor_id?>', '<?=$APPLICATION->GetCurPageParam("", array("logajax", "bxajaxid", "logout"));?>');</script><?
							}
						?></div><?
					}
					?></div><?
				}

				?><div class="feed-post-informers"><?

					if(!in_array($arParams["TYPE"], array("DRAFT", "MODERATION"))):
						?><span class="feed-inform-comments"><a href="<?=$arResult["Post"]["urlToPost"]?>"><?=GetMessage("BLOG_COMMENTS")?></a></span><?
					endif;

					if ($arParams["SHOW_RATING"] == "Y"):
						?><span class="feed-inform-ilike"><?
						$APPLICATION->IncludeComponent(
							"bitrix:rating.vote", $arParams["RATING_TYPE"],
							Array(
								"ENTITY_TYPE_ID" => "BLOG_POST",
								"ENTITY_ID" => $arResult["Post"]["ID"],
								"OWNER_ID" => $arResult["Post"]["AUTHOR_ID"],
								"USER_VOTE" => $arResult["RATING"][$arResult["Post"]["ID"]]["USER_VOTE"],
								"USER_HAS_VOTED" => $arResult["RATING"][$arResult["Post"]["ID"]]["USER_HAS_VOTED"],
								"TOTAL_VOTES" => $arResult["RATING"][$arResult["Post"]["ID"]]["TOTAL_VOTES"],
								"TOTAL_POSITIVE_VOTES" => $arResult["RATING"][$arResult["Post"]["ID"]]["TOTAL_POSITIVE_VOTES"],
								"TOTAL_NEGATIVE_VOTES" => $arResult["RATING"][$arResult["Post"]["ID"]]["TOTAL_NEGATIVE_VOTES"],
								"TOTAL_VALUE" => $arResult["RATING"][$arResult["Post"]["ID"]]["TOTAL_VALUE"],
								"PATH_TO_USER_PROFILE" => $arParams["~PATH_TO_USER"],
							),
							$component,
							array("HIDE_ICONS" => "Y")
						);?></span><?
					endif;

					if (
						array_key_exists("FOLLOW", $arParams)
						&& strlen($arParams["FOLLOW"]) > 0
						&& intval($arParams["LOG_ID"]) > 0
					):
						?><span class="feed-inform-follow" data-follow="<?=($arParams["FOLLOW"] == "Y" ? "Y" : "N")?>" id="log_entry_follow_<?=intval($arParams["LOG_ID"])?>" onclick="__blogPostSetFollow(<?=intval($arParams["LOG_ID"])?>)"><a href="javascript:void(0);"><?=GetMessage("BLOG_POST_FOLLOW_".($arParams["FOLLOW"] == "Y" ? "Y" : "N"))?></a></span><?
					endif;

					?><span class="feed-post-time-wrap"><?
						if (ConvertTimeStamp(MakeTimeStamp($arResult["Post"]["DATE_PUBLISH"]), "SHORT") == ConvertTimeStamp())
						{
							?><a href="<?=$arResult["Post"]["urlToPost"]?>"><span class="feed-time"><?=$arResult["Post"]["DATE_PUBLISH_TIME"]?></span></a><?
						}
						else
						{
							?><a href="<?=$arResult["Post"]["urlToPost"]?>"><span class="feed-time"><?=$arResult["Post"]["DATE_PUBLISH_FORMATED"]?></span></a><?
						}
					?></span>
				</div>
			</div><?

		if(!in_array($arParams["TYPE"], array("DRAFT", "MODERATION")))
		{
			
			$APPLICATION->IncludeComponent(
				"bitrix:socialnetwork.blog.post.comment",
				"",
				Array(
						"BLOG_VAR" => $arResult["ALIASES"]["blog"],
						"POST_VAR" => $arParams["POST_VAR"],
						"USER_VAR" => $arParams["USER_VAR"],
						"PAGE_VAR" => $arParams["PAGE_VAR"],
						"PATH_TO_BLOG" => $arParams["PATH_TO_BLOG"],
						"PATH_TO_POST" => $arParams["PATH_TO_POST"],
						"PATH_TO_USER" => $arParams["PATH_TO_USER"],
						"PATH_TO_SMILE" => $arParams["PATH_TO_SMILE"],
						"PATH_TO_MESSAGES_CHAT" => $arParams["PATH_TO_MESSAGES_CHAT"],
						"ID" => $arResult["Post"]["ID"],
						"CACHE_TYPE" => $arParams["CACHE_TYPE"],
						"CACHE_TIME" => $arParams["CACHE_TIME"],
						"COMMENTS_COUNT" => $arParams["COMMENTS_COUNT"],
						"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT_S"],
						"USE_ASC_PAGING" => $arParams["USE_ASC_PAGING"],
						"USER_ID" => $arResult["USER_ID"],
						"GROUP_ID" => $arParams["GROUP_ID"],
						"SONET_GROUP_ID" => $arParams["SONET_GROUP_ID"],
						"NOT_USE_COMMENT_TITLE" => "Y",
						"USE_SOCNET" => "Y",
						"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
						"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
						"SHOW_YEAR" => $arParams["SHOW_YEAR"],
						"PATH_TO_CONPANY_DEPARTMENT" => $arParams["PATH_TO_CONPANY_DEPARTMENT"],
						"PATH_TO_VIDEO_CALL" => $arParams["PATH_TO_VIDEO_CALL"],
						"SHOW_RATING" => $arParams["SHOW_RATING"],
						"RATING_TYPE" => $arParams["RATING_TYPE"],
						"IMAGE_MAX_WIDTH" => $arParams["IMAGE_MAX_WIDTH"],
						"IMAGE_MAX_HEIGHT" => $arParams["IMAGE_MAX_HEIGHT"],
						"ALLOW_VIDEO" => $arParams["ALLOW_VIDEO"],
						"ALLOW_IMAGE_UPLOAD" => $arParams["ALLOW_IMAGE_UPLOAD"],
						"SHOW_SPAM" => $arParams["BLOG_SHOW_SPAM"],
						"NO_URL_IN_COMMENTS" => $arParams["BLOG_NO_URL_IN_COMMENTS"],
						"NO_URL_IN_COMMENTS_AUTHORITY" => $arParams["BLOG_NO_URL_IN_COMMENTS_AUTHORITY"],
						"ALLOW_POST_CODE" => $arParams["BLOG_ALLOW_POST_CODE"],
						"AJAX_POST" => "Y",
						"POST_DATA" => $arResult["PostSrc"],
						"BLOG_DATA" => $arResult["Blog"],
						"FROM_LOG" => $arParams["FROM_LOG"],
						"bFromList" => $arResult["bFromList"],
						"LAST_LOG_TS" => $arParams["LAST_LOG_TS"],
						"MARK_NEW_COMMENTS" => $arParams["MARK_NEW_COMMENTS"],
						"AVATAR_SIZE_COMMENT" => $arParams["AVATAR_SIZE_COMMENT"],
						"FOLLOW" => $arParams["FOLLOW"],
						"LOG_ID" => intval($arParams["LOG_ID"]),
						"CREATED_BY_ID" => $arParams["CREATED_BY_ID"],
						"MOBILE" => $arParams["MOBILE"],
					),
				$component
			);

		}

		if(!in_array($arParams["TYPE"], array("DRAFT", "MODERATION")))
		{
			$arParams["ADIT_MENU"][2] = Array(
				"text_php" => GetMessage("BLOG_LINK"),
				"href" => CUtil::JSEscape($arResult["Post"]["urlToPost"]),
				);
		}
		if(strlen($arResult["urlToEdit"]) > 0)
		{
			$arParams["ADIT_MENU"][3] = Array(
				"text_php" => GetMessage("BLOG_BLOG_BLOG_EDIT"),
				"href" => CUtil::JSEscape($arResult["urlToEdit"]),
				);
		}
		if(!$arResult["bFromList"] && strlen($arResult["urlToHide"]) > 0)
		{
			$arParams["ADIT_MENU"][6] = Array(
				"text_php" => GetMessage("BLOG_MES_HIDE"),
				"onclick" => "function() { if(confirm('".GetMessage("BLOG_MES_HIDE_POST_CONFIRM")."')) window.location='".$arResult["urlToHide"]."';  this.popupWindow.close();}",
				);
		}
		if($arResult["canDelete"] == "Y")
		{
			$arParams["ADIT_MENU"][7] = Array(
				"text_php" => GetMessage("BLOG_BLOG_BLOG_DELETE"),
				"onclick" => "function() { if(confirm('".GetMessage("BLOG_MES_DELETE_POST_CONFIRM")."')) ".($arResult["bFromList"] ? "deleteBlogPost('".$arResult["Post"]["ID"]."');" : " window.location='".$arResult["urlToDelete"]."';")." this.popupWindow.close();}",
				);
		}
		ksort($arParams["ADIT_MENU"]);
		if(!empty($arParams["ADIT_MENU"]))
		{
			?><div class="feed-post-menu-wrap">
				<div class="feed-post-menu-but" onmousedown="BX.addClass(this,'feed-post-menu-but-active')" onmouseup="BX.removeClass(this,'feed-post-menu-but-active')" onclick="BX.addClass(this, 'feed-post-menu-but-active');
					BX.PopupMenu.show('blog-post-<?=$arResult["Post"]["ID"]?>', this, [
						<?
						$bFirst = true;
						foreach($arParams["ADIT_MENU"] as $val)
						{
							if($bFirst)
								$bFirst = false;
							else
								echo ", ";
							echo "{text : ";
							if(strlen($val["text"]) > 0)
								echo "BX.message('".$val["text"]."')";
							else
								echo "'".$val["text_php"]."'";
							if(strlen($val["onclick"]) > 0)
								echo ", onclick: ".$val["onclick"];
							else
								echo ", href: '".$val["href"]."'";
							echo ", className: 'blog-post-popup-menu'}";
						}
						?>
						],
					{
						offsetLeft: -32,
						offsetTop: 4,
						lightShadow: false,
						<?if(SITE_TEMPLATE_ID == "bitrix24"):?>angle: {position: 'top', offset: 93},<?endif;?>
						events : {
							onPopupClose : function(popupWindow) {BX.removeClass(this.bindElement, 'feed-post-menu-but-active');}
						}
					});"></div>
			</div><?
		}
		?></div><?
	}
	elseif(!$arResult["bFromList"])
		echo GetMessage("BLOG_BLOG_BLOG_NO_AVAIBLE_MES");
}
?></div>