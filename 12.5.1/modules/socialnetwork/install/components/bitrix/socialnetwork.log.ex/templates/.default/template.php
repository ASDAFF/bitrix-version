<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
if ($arResult["NEED_AUTH"] == "Y")
	$APPLICATION->AuthForm("");
elseif (strlen($arResult["FatalError"])>0)
{
	?><span class='errortext'><?=$arResult["FatalError"]?></span><br /><br /><?
}
else
{
	CAjax::Init();
	CUtil::InitJSCore(array("ajax", "window", "tooltip", "popup"));
	$log_content_id = "sonet_log_content_".RandString(8);
	$event_cnt = 0;

	if (!$arResult["AJAX_CALL"])
	{
		$APPLICATION->AddHeadScript("/bitrix/components/bitrix/socialnetwork.log.entry/templates/.default/scripts.js");

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
			),
			false,
			array("HIDE_ICONS" => "Y")
		);

		if (IsModuleInstalled('tasks'))
		{
			?><?
			$GLOBALS["APPLICATION"]->IncludeComponent(
				"bitrix:tasks.iframe.popup",
				".default",
				array(
					"ON_TASK_ADDED" => "BX.DoNothing",
					"ON_TASK_CHANGED" => "BX.DoNothing",
					"ON_TASK_DELETED" => "BX.DoNothing",
				),
				null,
				array("HIDE_ICONS" => "Y")
			);
			?><?
		}

		if(
			$arParams["HIDE_EDIT_FORM"] != "Y"
			&& IntVal($arResult["MICROBLOG_USER_ID"]) > 0
		)
		{
			?><div id="sonet_log_microblog_container" style="padding-bottom: 10px;"><span id="slog-mb-hide" style="display:none;"><div onclick="WriteMicroblog(false)"></div></span><?
				$arBlogComponentParams = Array(
					"ID" => "new",
					"PATH_TO_BLOG" => $APPLICATION->GetCurPageParam(),
					"PATH_TO_POST" => $arParams["PATH_TO_USER_MICROBLOG_POST"],
					"PATH_TO_GROUP_POST" => $arParams["PATH_TO_GROUP_MICROBLOG_POST"],
					"PATH_TO_SMILE" => $arParams["PATH_TO_BLOG_SMILE"],
					"SET_TITLE" => "N",
					"GROUP_ID" => $arParams["BLOG_GROUP_ID"],
					"USER_ID" => $USER->GetID(),
					"SET_NAV_CHAIN" => "N",
					"USE_SOCNET" => "Y",
					"MICROBLOG" => "Y",
					"USE_CUT" => $arParams["BLOG_USE_CUT"],
					"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
					"CHECK_PERMISSIONS_DEST" => $arParams["CHECK_PERMISSIONS_DEST"],
					"TOP_TABS_VISIBLE" => (array_key_exists("TOP_TABS_VISIBLE", $arParams) ? $arParams["TOP_TABS_VISIBLE"] : "Y")
				);

				if ($arParams["ENTITY_TYPE"] == SONET_ENTITY_GROUP)
					$arBlogComponentParams["SOCNET_GROUP_ID"] = $arParams["GROUP_ID"];
				elseif ($arParams["ENTITY_TYPE"] != SONET_ENTITY_GROUP && $USER->GetID() != $arParams["CURRENT_USER_ID"])
					$arBlogComponentParams["SOCNET_USER_ID"] = $arParams["CURRENT_USER_ID"];

				?><?
				$APPLICATION->IncludeComponent(
					"bitrix:socialnetwork.blog.post.edit",
					"",
					$arBlogComponentParams,
					$component,
					array("HIDE_ICONS" => "Y")
				);
			?></div><?
		}

		if ($arParams["SHOW_EVENT_ID_FILTER"] == "Y")
		{
			$GLOBALS["APPLICATION"]->IncludeComponent(
				"bitrix:socialnetwork.log.filter",
				".default",
				array(
					"arParams" => array_merge(
						$arParams, 
						array(
							"TARGET_ID" => (
								isset($_REQUEST["SONET_FILTER_MODE"]) 
								&& $_REQUEST["SONET_FILTER_MODE"] == "AJAX"
									? "" 
									: "sonet_blog_form"
							),
							"SHOW_FOLLOW" => (isset($arParams["SHOW_FOLLOW_FILTER"]) && $arParams["SHOW_FOLLOW_FILTER"] == "N" ? "N" : "Y")
						)
					),
					"arResult" => $arResult
				),
				null,
				array("HIDE_ICONS" => "Y")
			);

			if (
				isset($_REQUEST["SONET_FILTER_MODE"]) 
				&& $_REQUEST["SONET_FILTER_MODE"] == "AJAX"
			)
				return;
		}

		if ($arParams["SHOW_REFRESH"] != "N")
		{
			?><a onclick="__logRefresh('<?=CUtil::JSEscape($GLOBALS["APPLICATION"]->GetCurPageParam("logajax=Y&RELOAD=Y", array(
				"flt_created_by_id",
				"flt_group_id",
				"flt_date_datesel",
				"flt_date_days",
				"flt_date_from",
				"flt_date_to",
				"flt_date_to",
				"preset_filter_id",
				"sessid",
				"bxajaxid",
				"logajax"
			), false))?>')" href="javascript:void(0);" id="sonet_log_counter_2_container" class="feed-new-message-informer"><span class="feed-new-message-inf-text"><?=GetMessage("SONET_C30_COUNTER_TEXT_1")?><span class="feed-new-message-informer-counter" id="sonet_log_counter_2"></span><span class="feed-new-message-icon"></span></span><span class="feed-new-message-inf-text feed-new-message-inf-text-waiting" style="display: none;"><span class="feed-new-message-wait-icon"></span><?=GetMessage("SONET_C30_T_MORE_WAIT")?></span></a><?
		}

		?><div id="log_internal_container"><?
	}
	else
	{
		$arCSSList = $APPLICATION->sPath2css;
		$arHeadScripts = $APPLICATION->arHeadScripts;

		$APPLICATION->RestartBuffer();
	}

	if (!$arResult["AJAX_CALL"])
	{		
		?><div class="feed-wrap"><?
	}

	?><script>
		var logAjaxMode = false;
		var nodeTmp1Cap = false;
		var nodeTmp2Cap = false;

		<?
		if (!$arResult["AJAX_CALL"])
		{
			?>		
			BX.message({
				sonetLGetPath: '<?=CUtil::JSEscape('/bitrix/components/bitrix/socialnetwork.log.ex/ajax.php')?>',
				sonetLSetPath: '<?=CUtil::JSEscape('/bitrix/components/bitrix/socialnetwork.log.ex/ajax.php')?>',
				sonetLESetPath: '<?=CUtil::JSEscape('/bitrix/components/bitrix/socialnetwork.log.entry/ajax.php')?>',
				sonetLSessid: '<?=bitrix_sessid_get()?>',
				sonetLLangId: '<?=CUtil::JSEscape(LANGUAGE_ID)?>',
				sonetLSiteId: '<?=CUtil::JSEscape(SITE_ID)?>',
				sonetLSiteTemplateId: '<?=CUtil::JSEscape(SITE_TEMPLATE_ID)?>',
				sonetLNoSubscriptions: '<?=GetMessageJS("SONET_C30_NO_SUBSCRIPTIONS")?>',
				sonetLInherited: '<?=GetMessageJS("SONET_C30_INHERITED")?>',
				sonetLDialogClose: '<?=GetMessageJS("SONET_C30_DIALOG_CLOSE_BUTTON")?>',
				sonetLDialogSubmit: '<?=GetMessageJS("SONET_C30_DIALOG_SUBMIT_BUTTON")?>',
				sonetLDialogCancel: '<?=GetMessageJS("SONET_C30_DIALOG_CANCEL_BUTTON")?>',
				sonetLTransportTitle: '<?=GetMessageJS("SONET_C30_DIALOG_TRANSPORT_TITLE")?>',
				sonetLMenuTransportTitle: '<?=GetMessageJS("SONET_C30_MENU_TITLE_TRANSPORT")?>',
				sonetLMenuFavoritesTitleY: '<?=GetMessageJS("SONET_C30_MENU_TITLE_FAVORITES_Y")?>',
				sonetLMenuFavoritesTitleN: '<?=GetMessageJS("SONET_C30_MENU_TITLE_FAVORITES_N")?>',
				sonetLCounterType: '<?=CUtil::JSEscape($arResult["COUNTER_TYPE"])?>',
				sonetLIsB24: '<?=(SITE_TEMPLATE_ID == "bitrix24" ? "Y" : "N")?>'
			});
			<?
		}
		
		if ($arResult["AJAX_CALL"] && $arParams["SHOW_RATING"] == "Y"):?>
			<?if ($arParams["RATING_TYPE"] == "like"):?>
				BX.loadCSS('/bitrix/components/bitrix/rating.vote/templates/<?=$arParams["RATING_TYPE"]?>/popup.css');
			<?endif;?>
			BX.loadCSS('/bitrix/components/bitrix/rating.vote/templates/<?=$arParams["RATING_TYPE"]?>/style.css');
		<?endif;?>

		<?
		if ($arResult["bReload"]):
			?>
			if (typeof __logOnReload === 'function')
			{
				BX.ready(function(){
					__logOnReload(<?=intval($arResult["LOG_COUNTER"])?>);
				});
			}
			<?
		endif;

		if (
			!$arResult["AJAX_CALL"] 
			|| $arResult["bReload"]
		):
			?>
			BX.ready(function(){
				BX.onCustomEvent(window, 'onSonetLogCounterClear', [BX.message('sonetLCounterType')]);
				<?
				if (!$arResult["AJAX_CALL"]):
					?>
					BX.addCustomEvent(window, "onImUpdateCounter", BX.proxy(function(arCount){ __logChangeCounterArray(arCount); }, this));
					<?
				endif;
				?>
			});
			<?
		endif;
		?>
		BX.ready(function(){
			BX.addCustomEvent(window, "onAjaxInsertToNode", function() { BX.ajax.Setup({denyShowWait: true}, true); });

			BX.bind(BX('sonet_log_counter_2_container'), 'click', sonetLClearContainerExternalNew);
			BX.bind(BX('sonet_log_counter_2_container'), 'click', __logOnAjaxInsertToNode);

			BX.bind(BX('sonet_log_more_container'), 'click', sonetLClearContainerExternalMore);
			BX.bind(BX('sonet_log_more_container'), 'click', __logOnAjaxInsertToNode);

			if (BX('sonet_log_comment_text'))
				BX('sonet_log_comment_text').onkeydown = BX.eventCancelBubble;
		});

	</script>
	<?
	if(strlen($arResult["ErrorMessage"]) > 0)
	{
		?><span class='errortext'><?=$arResult["ErrorMessage"]?></span><br /><br /><?
	}

	if (
		$arResult["Events"] 
		&& is_array($arResult["Events"]) 
		&& count($arResult["Events"]) > 0
	)
	{
		?><div id="sonet_log_items"><?
		foreach ($arResult["Events"] as $arEvent)
		{
			if (!empty($arEvent))
			{
				$event_cnt++;
				$ind = RandString(8);

				if (isset($arEvent["LOG_DATE_TS"]))
					$event_date_log_ts = $arEvent["LOG_DATE_TS"];
				else
					$event_date_log_ts = (MakeTimeStamp($arEvent["LOG_DATE"]) - intval($arResult["TZ_OFFSET"]));

				$is_unread = (
					$arParams["SHOW_UNREAD"] == "Y"
					&& ($arResult["COUNTER_TYPE"] == "**" || $arResult["COUNTER_TYPE"] == "blog_post")
					&& $arEvent["USER_ID"] != $GLOBALS["USER"]->GetID() 
					&& intval($arResult["LAST_LOG_TS"]) > 0 
					&& $event_date_log_ts > $arResult["LAST_LOG_TS"]
				);

				if(in_array($arEvent["EVENT_ID"], Array("blog_post", "blog_post_micro", "blog_comment", "blog_comment_micro")))
				{
					?><div><?
					$arAditMenu = array();

					if ($GLOBALS["USER"]->IsAuthorized())
					{
						$arAditMenu["1"] = Array(
							"text" => (array_key_exists("FAVORITES_USER_ID", $arEvent) && intval($arEvent["FAVORITES_USER_ID"]) > 0 ? "sonetLMenuFavoritesTitleY" : "sonetLMenuFavoritesTitleN"),
							"onclick" => "function(e) { __logChangeFavorites('".$arEvent["ID"]."'); return BX.PreventDefault(e);}",
						);
						$arAditMenu["4"] = Array(
							"text" => "sonetLMenuTransportTitle",
							"onclick" => "function(e) { __logShowTransportDialog('".$ind."', '".$arEvent["ENTITY_TYPE"]."', '".$arEvent["ENTITY_ID"]."', '".$arEvent["EVENT_ID"]."', ".($arEvent["EVENT_ID_FULLSET"] ? "'".$arEvent["EVENT_ID_FULLSET"]."'" : "false").", '".$arEvent["USER_ID"]."'); this.popupWindow.close();}",
						);
					}

					$arComponentParams = Array(
						"PATH_TO_BLOG" => $arParams["PATH_TO_USER_BLOG"],
						"PATH_TO_POST" => $arParams["PATH_TO_USER_MICROBLOG_POST"],
						"PATH_TO_BLOG_CATEGORY" => $arParams["PATH_TO_USER_BLOG_CATEGORY"],
						"PATH_TO_POST_EDIT" => $arParams["PATH_TO_USER_BLOG_POST_EDIT"],
						"PATH_TO_USER" => $arParams["PATH_TO_USER"],
						"PATH_TO_GROUP" => $arParams["PATH_TO_GROUP"],
						"PATH_TO_SMILE" => $arParams["PATH_TO_BLOG_SMILE"],
						"PATH_TO_MESSAGES_CHAT" => $arParams["PATH_TO_MESSAGES_CHAT"],
						"SET_NAV_CHAIN" => "N",
						"SET_TITLE" => "N",
						"POST_PROPERTY" => $arParams["POST_PROPERTY"],
						"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
						"CREATED_BY_ID" => $arParams["CREATED_BY_ID"],
						"USER_ID" => $arEvent["USER_ID"],
						"ENTITY_TYPE" => SONET_ENTITY_USER,
						"ENTITY_ID" => $arEvent["ENTITY_ID"],
						"EVENT_ID" => $arEvent["EVENT_ID"],
						"EVENT_ID_FULLSET" => $arEvent["EVENT_ID_FULLSET"],
						"IND" => $ind,
						"GROUP_ID" => $arParams["BLOG_GROUP_ID"],
						"SONET_GROUP_ID" => $arParams["GROUP_ID"],
						"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
						"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
						"SHOW_YEAR" => $arParams["SHOW_YEAR"],
						"PATH_TO_CONPANY_DEPARTMENT" => $arParams["PATH_TO_CONPANY_DEPARTMENT"],
						"PATH_TO_VIDEO_CALL" => $arParams["PATH_TO_VIDEO_CALL"],
						"USE_SHARE" => $arParams["USE_SHARE"],
						"SHARE_HIDE" => $arParams["SHARE_HIDE"],
						"SHARE_TEMPLATE" => $arParams["SHARE_TEMPLATE"],
						"SHARE_HANDLERS" => $arParams["SHARE_HANDLERS"],
						"SHARE_SHORTEN_URL_LOGIN" => $arParams["SHARE_SHORTEN_URL_LOGIN"],
						"SHARE_SHORTEN_URL_KEY" => $arParams["SHARE_SHORTEN_URL_KEY"],
						"SHOW_RATING" => $arParams["SHOW_RATING"],
						"RATING_TYPE" => $arParams["RATING_TYPE"],
						"IMAGE_MAX_WIDTH" => $arParams["BLOG_IMAGE_MAX_WIDTH"],
						"IMAGE_MAX_HEIGHT" => $arParams["BLOG_IMAGE_MAX_HEIGHT"],
						"ALLOW_POST_CODE" => $arParams["ALLOW_POST_CODE"],
						"ID" => $arEvent["SOURCE_ID"],
						"LOG_ID" => $arEvent["ID"],
						"FROM_LOG" => "Y",
						"ADIT_MENU" => $arAditMenu,
						"IS_UNREAD" => $is_unread,
						"MARK_NEW_COMMENTS" => (
							$GLOBALS["USER"]->IsAuthorized() 
							&& $arResult["COUNTER_TYPE"] == "**" 
							&& $arParams["SHOW_UNREAD"] == "Y"
							) 
								? "Y" 
								: "N",
						"IS_HIDDEN" => false,
						"LAST_LOG_TS" => ($arResult["LAST_LOG_TS"] + $arResult["TZ_OFFSET"]), 
						"CACHE_TIME" => $arParams["CACHE_TIME"],
						"CACHE_TYPE" => $arParams["CACHE_TYPE"],
						"ALLOW_VIDEO" => $arParams["BLOG_COMMENT_ALLOW_VIDEO"],
						"ALLOW_IMAGE_UPLOAD" => $arParams["BLOG_COMMENT_ALLOW_IMAGE_UPLOAD"],
						"USE_CUT" => $arParams["BLOG_USE_CUT"],
						"AVATAR_SIZE" => $arParams["AVATAR_SIZE"],
						"AVATAR_SIZE_COMMENT" => $arParams["AVATAR_SIZE_COMMENT"],
					);

					if ($arParams["USE_FOLLOW"] == "Y")
						$arComponentParams["FOLLOW"] = $arEvent["FOLLOW"];

					if ($arResult["CURRENT_PAGE_DATE"])
						$arComponentParams["CURRENT_PAGE_DATE"] = $arResult["CURRENT_PAGE_DATE"];

					$APPLICATION->IncludeComponent(
						"bitrix:socialnetwork.blog.post",
						"",
						$arComponentParams,
						$component
					);
					?></div><?
				}
				else
				{
					$arComponentParams = array_merge($arParams, array(
							"LOG_ID" => $arEvent["ID"],
							"LAST_LOG_TS" => $arResult["LAST_LOG_TS"],
							"COUNTER_TYPE" => $arResult["COUNTER_TYPE"],
							"AJAX_CALL" => $arResult["AJAX_CALL"],
							"bReload" => $arResult["bReload"],
							"bGetComments" => $arResult["bGetComments"],
							"IND" => $ind,
							"CURRENT_PAGE_DATE" => $arResult["CURRENT_PAGE_DATE"],
							"EVENT" => array(
								"IS_UNREAD" => $is_unread
							)
						)
					);

					if ($GLOBALS["USER"]->IsAuthorized())
					{
						if ($arParams["USE_FOLLOW"] == "Y")
						{
							$arComponentParams["EVENT"]["FOLLOW"] = $arEvent["FOLLOW"];
							$arComponentParams["EVENT"]["DATE_FOLLOW"] = $arEvent["DATE_FOLLOW"];
						}

						$arComponentParams["EVENT"]["FAVORITES"] = (
							array_key_exists("FAVORITES_USER_ID", $arEvent) 
							&& intval($arEvent["FAVORITES_USER_ID"]) > 0 
								? "Y" 
								: "N"
						);
					}

					if ($arResult["CURRENT_PAGE_DATE"])
						$arComponentParams["CURRENT_PAGE_DATE"] = $arResult["CURRENT_PAGE_DATE"];

					$APPLICATION->IncludeComponent(
						"bitrix:socialnetwork.log.entry",
						"",
						$arComponentParams,
						$component
					);
				}
			}
		}
		?></div><?
	}

	if (
		$arParams["SHOW_NAV_STRING"] != "N"
		&& is_array($arResult["Events"])
		&& $event_cnt > 0
		&& ($event_cnt >= intval($arParams["PAGE_SIZE"]))
	)
	{
		$strParams = "logajax=Y&PAGEN_".$arResult["PAGE_NAVNUM"]."=".($arResult["PAGE_NUMBER"] + 1);
		if (!$arResult["AJAX_CALL"])
			$strParams .= "&ts=".$arResult["LAST_LOG_TS"];

		ob_start();

		?><a onclick="__logGetNextPage('<?=CUtil::JSEscape($APPLICATION->GetCurPageParam($strParams, array("PAGEN_".$arResult["PAGE_NAVNUM"], "RELOAD")))?>')" href="javascript:void(0);" id="sonet_log_more_container" class="feed-new-message-informer feed-new-message-inf-bottom"><span class="feed-new-message-inf-text"><?=GetMessage("SONET_C30_MORE")?><span class="feed-new-message-icon"></span></span><span class="feed-new-message-inf-text feed-new-message-inf-text-waiting" style="display: none;"><span class="feed-new-message-wait-icon"></span><?=GetMessage("SONET_C30_T_MORE_WAIT")?></span></a><?
		
		$moreLink = ob_get_contents();
		ob_end_clean();
	}
	else
		$moreLink = "";

	if (!$arResult["AJAX_CALL"])
	{	
		?></div><? // feed-wrap
		echo $moreLink;
		?></div><? // log_internal_container		
	}
	else
	{
		echo $moreLink;
		
		$arCSSListNew = $APPLICATION->sPath2css;
		$cnt_old = count($arCSSList);
		$cnt_new = count($arCSSListNew);

		$arCSSNew = array();

		if ($cnt_old != $cnt_new)
			for ($i = $cnt_old; $i < $cnt_new; $i++)
			{
				$css_path = $arCSSListNew[$i];
				if(strtolower(substr($css_path, 0, 7)) != 'http://' && strtolower(substr($css_path, 0, 8)) != 'https://')
				{
					if(($p = strpos($css_path, "?"))>0)
						$css_file = substr($css_path, 0, $p);
					else
						$css_file = $css_path;

					if(file_exists($_SERVER["DOCUMENT_ROOT"].$css_file))
						$arCSSNew[] = $arCSSListNew[$i];
				}
				else
					$arCSSNew[] = $arCSSListNew[$i];
			}

		$arCSSNew = array_unique($arCSSNew);
		
		$arHeadScriptsListNew = $APPLICATION->arHeadScripts;

		$cnt_old = count($arHeadScripts);
		$cnt_new = count($arHeadScriptsListNew);
		$arHeadScriptsNew = array();

		if ($cnt_old != $cnt_new)
			for ($i = $cnt_old; $i < $cnt_new; $i++)
				$arHeadScriptsNew[] = $arHeadScriptsListNew[$i];

		if(!$APPLICATION->IsJSOptimized())
			$arHeadScriptsNew = array_merge(CJSCore::GetScriptsList(), $arHeadScriptsNew);

		$arAdditionalData["CSS"] = array();
		foreach($arCSSNew as $style)
			$arAdditionalData["CSS"][] = CUtil::GetAdditionalFileURL($style);

		$arAdditionalData['SCRIPTS'] = array();
		$arHeadScriptsNew = array_unique($arHeadScriptsNew);

		foreach($arHeadScriptsNew as $script)
			$arAdditionalData["SCRIPTS"][] = CUtil::GetAdditionalFileURL($script);

		$additional_data = '<script type="text/javascript" bxrunfirst="true">'."\n";
		$additional_data .= 'var arAjaxPageData = '.CUtil::PhpToJSObject($arAdditionalData).";\r\n";
		$additional_data .= 'top.BX.ajax.UpdatePageData(arAjaxPageData)'.";\r\n";
		$additional_data .= '</script>';

		echo $additional_data;

		die();
	}

	?><div id="sonet_log_comment_form_container" style="display: none;">
	<form method="POST" onsubmit="return false;" action="" id="sonet_log_comment_form">
		<?=bitrix_sessid_post()?>
		<input type="hidden" name="sonet_log_comment_logid" id="sonet_log_comment_logid" value="">
		<textarea id="sonet_log_comment_text" cols="35" rows="4"></textarea>
		<script type="text/javascript">
			var CommentFormWidth = 0;
			var CommentFormColsDefault = 0;
			var CommentFormRowsDefault = 0;
			var CommentFormSymbolWidth = 6.6;
			BX.bind(BX('sonet_log_comment_text', true), "keyup", BX.delegate(__logCommentFormAutogrow, this));
		</script>
		<div><input onclick="__logCommentAdd(); return false;" id="sonet_log_post_comment_button" type="submit" value="<?=GetMessage("SONET_C30_COMMENT_SUBMIT")?>" name="add_comment"></div>
	</form>
	</div><?
	// sonet_log_content
}
?>