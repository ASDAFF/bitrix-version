<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
if(strlen($arResult["FatalError"])>0)
{
	?><span class='errortext'><?=$arResult["FatalError"]?></span><br /><br /><?
}
else
{
	CUtil::InitJSCore(array("tooltip", "popup"));

	if(strlen($arResult["ErrorMessage"])>0)
	{
		?><span class="errortext"><?=$arResult["ErrorMessage"]?></span><br /><br /><?
	}

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

	?><script>

		BX.message({
			GUEAddToUsersTitle: '<?=CUtil::JSEscape(GetMessage("SONET_GUE_T_ACTION_ADDTOUSERS"))?>',
			GUEAddToModeratorsTitle: '<?=CUtil::JSEscape(GetMessage("SONET_GUE_T_ACTION_ADDTOMODERATORS"))?>',
			GUEExcludeFromGroupTitle: '<?=CUtil::JSEscape(GetMessage("SONET_GUE_T_ACTION_EXCLUDEFROMGROUP"))?>',
			GUEBanFromGroupTitle: '<?=CUtil::JSEscape(GetMessage("SONET_GUE_T_ACTION_BANFROMGROUP"))?>',
			GUEExcludeFromModeratorsTitle: '<?=CUtil::JSEscape(GetMessage("SONET_GUE_T_ACTION_EXCLUDEFROMMODERATORS"))?>',
			GUEExcludeFromGroupConfirmTitle: '<?=CUtil::JSEscape(GetMessage("SONET_GUE_T_ACTION_EXCLUDEFROMGROUP_CONFIRM"))?>',
			GUEUnBanFromGroupTitle: '<?=CUtil::JSEscape(GetMessage("SONET_GUE_T_ACTION_UNBANFROMGROUP"))?>',
			GUESetGroupOwnerTitle: '<?=CUtil::JSEscape(GetMessage("SONET_GUE_T_ACTION_SETGROUPOWNER"))?>',
			GUESetGroupOwnerConfirmTitle: '<?=CUtil::JSEscape(GetMessage("SONET_GUE_T_ACTION_SETGROUPOWNER_CONFIRM"))?>',
			GUEErrorUserIDNotDefined: '<?=CUtil::JSEscape(GetMessage("SONET_GUE_T_USER_ID_NOT_DEFINED"))?>',
			GUEErrorUserIDIncorrect: '<?=CUtil::JSEscape(GetMessage("SONET_GUE_T_USER_ID_INCORRECT"))?>',
			GUEErrorGroupIDNotDefined: '<?=CUtil::JSEscape(GetMessage("SONET_GUE_T_GROUP_ID_NOT_DEFINED"))?>',
			GUEErrorCurrentUserNotAuthorized: '<?=CUtil::JSEscape(GetMessage("SONET_GUE_T_NOT_ATHORIZED"))?>',
			GUEErrorModuleNotInstalled: '<?=CUtil::JSEscape(GetMessage("SONET_GUE_T_MODULE_NOT_INSTALLED"))?>',
			GUEErrorOwnerCantExcludeHimself: '<?=CUtil::JSEscape(GetMessage("SONET_GUE_T_OWNER_CANT_EXCLUDE_HIMSELF"))?>',
			GUEErrorNoPerms: '<?=CUtil::JSEscape(GetMessage("SONET_GUE_T_NO_PERMS"))?>',
			GUEErrorSessionWrong: '<?=CUtil::JSEscape(GetMessage("SONET_GUE_T_SESSION_WRONG"))?>',
			GUEErrorActionFailedPattern: '<?=CUtil::JSEscape(GetMessage("SONET_GUE_T_ACTION_FAILED"))?>',			
			GUESiteId: '<?=CUtil::JSEscape(SITE_ID)?>',
			GUEGroupId: <?=intval($arParams["GROUP_ID"])?>,
			GUEGroupName: '<?=CUtil::JSEscape($arResult["Group"]["NAME"])?>',
			GUEUseBan: '<?=CUtil::JSEscape($arParams["GROUP_USE_BAN"])?>',
			GUEIsB24: '<?=(SITE_TEMPLATE_ID == "bitrix24" ? "Y" : "N")?>',
			GUEUserCanViewGroup: <?=($arResult["CurrentUserPerms"]["UserCanViewGroup"] ? "true" : "false")?>,
			GUEUserCanModerateGroup: <?=($arResult["CurrentUserPerms"]["UserCanModerateGroup"] ? "true" : "false")?>,
			GUEUserCanModifyGroup: <?=($arResult["CurrentUserPerms"]["UserCanModifyGroup"] ? "true" : "false")?>,
			GUEUserCanInitiate: <?=($arResult["CurrentUserPerms"]["UserCanInitiate"] ? "true" : "false")?>,
			GUEWaitTitle: '<?=CUtil::JSEscape(GetMessage("SONET_GUE_T_WAIT"))?>'
		});

		var actionUsers = false;
		var oGUEWaitWindow = false;		

		BX.ready(
			function()	
			{
				var userBlockArr = BX.findChildren(document, { className: 'sonet-members-member-block' }, true);
				if (userBlockArr)
				{
					for (var i = userBlockArr.length - 1; i >= 0; i--)
					{
						BX.bind(userBlockArr[i], 'mouseover', function() {
							BX.addClass(this, 'sonet-members-member-block-over');
						});

						BX.bind(userBlockArr[i], 'mouseout', function() {
							BX.removeClass(this, 'sonet-members-member-block-over');
						});
					}
				}

				actionUsers = { 'Moderators': new Array(), 'Users': new Array() };
				if (BX.message("GUEUseBan") == "Y")
					actionUsers['Banned'] = new Array();
			}
		);

	</script><?

	if ($arResult["CurrentUserPerms"]["UserCanInitiate"]):

		?><?
			$APPLICATION->IncludeComponent(
				"bitrix:socialnetwork.group.iframe.popup",
				".default",
				array(
					"PATH_TO_GROUP" => $arParams["PATH_TO_GROUP"],
					"PATH_TO_GROUP_INVITE" => htmlspecialcharsback($arResult["Urls"]["GroupEdit"]).(strpos($arResult["Urls"]["GroupEdit"], "?") === false ? "?" : "&")."tab=invite",
					"PATH_TO_GROUP_EDIT" => htmlspecialcharsback($arResult["Urls"]["GroupEdit"]).(strpos($arResult["Urls"]["GroupEdit"], "?") === false ? "?" : "&")."tab=edit",
					"PATH_TO_GROUP_FEATURES" => htmlspecialcharsback($arResult["Urls"]["GroupEdit"]).(strpos($arResult["Urls"]["GroupEdit"], "?") === false ? "?" : "&")."tab=features",
					"ON_GROUP_ADDED" => "BX.DoNothing",
					"ON_GROUP_CHANGED" => "BX.DoNothing",
					"ON_GROUP_DELETED" => "BX.DoNothing"
				),
				null,
				array("HIDE_ICONS" => "Y")
			);

	endif;

	if (is_array($arResult["Moderators"]) && is_array($arResult["Moderators"]["List"]))
	{
		?><div class="sonet-members-item"><?
			?><span class="sonet-members-item-name"><?=GetMessage("SONET_GUE_T_MODS_SUBTITLE")?></span><?
			?><div class="sonet-members-separator"></div><?
			if ($arResult["CurrentUserPerms"] && $arResult["CurrentUserPerms"]["UserCanModifyGroup"])
			{
				?><div class="sonet-members-item-menu"><?
					?><span class="sonet-members-item-menu-title" onclick="__GUEShowMenu(this, 'moderators');"><?
						?><?=GetMessage("SONET_GUE_T_ACTIONS_TITLE")?>&nbsp;<?
						?><span class="sonet-members-item-menu-arrow"></span><?
					?></span>
				</div><?
			}
			?><div class="sonet-members-member-block-shift"><?
				foreach ($arResult["Moderators"]["List"] as $arMember)
				{
					$tooltip_id = randString(8);
					$arUserTmp = array(
						"ID" => $arMember["USER_ID"],
						"NAME" => htmlspecialcharsback($arMember["USER_NAME"]),
						"LAST_NAME" => htmlspecialcharsback($arMember["USER_LAST_NAME"]),
						"SECOND_NAME" => htmlspecialcharsback($arMember["USER_SECOND_NAME"]),
						"LOGIN" => htmlspecialcharsback($arMember["USER_LOGIN"])
					);

					?><span class="sonet-members-member-block"><?
						?><span class="sonet-members-member-img-wrap"<?=($arMember["IS_OWNER"] ? ' id="sonet-members-owner"' : '')?> <?
						if ($arResult["CurrentUserPerms"] && $arResult["CurrentUserPerms"]["UserCanModifyGroup"])
						{
							?>onclick="__GUEtoggleCheckbox(event, this, 'M<?=intval($arMember["USER_ID"])?>');"<?
						}
						?>><?
							?><span class="sonet-members-member-img" style="<?=(is_array($arMember["USER_PERSONAL_PHOTO_IMG"]) && strlen($arMember["USER_PERSONAL_PHOTO_IMG"]["src"]) > 0 ? "background: url('".$arMember["USER_PERSONAL_PHOTO_IMG"]["src"]."') no-repeat 0 0;" : "")?>"></span><?
							if ($arResult["CurrentUserPerms"] && $arResult["CurrentUserPerms"]["UserCanModifyGroup"])
							{
								?><input class="sonet-members-checkbox" type="checkbox"/><?
							}
						?></span><?
						?><span class="sonet-members-member-text"><?
							?><span class="sonet-members-member-title"><?
							if ($arMember["SHOW_PROFILE_LINK"])
							{
								?><a id="anchor_<?=$tooltip_id?>" href="<?=htmlspecialcharsback($arMember["USER_PROFILE_URL"])?>" class="sonet-members-membet-link"><?=CUser::FormatName(str_replace(array("#NOBR#", "#/NOBR#"), array("", ""), $arParams["NAME_TEMPLATE"]), $arUserTmp, $arParams["SHOW_LOGIN"] != "N")?></a><?
							}
							else
							{
								?><span id="anchor_<?=$tooltip_id?>" class="sonet-members-membet-link"><?=CUser::FormatName(str_replace(array("#NOBR#", "#/NOBR#"), array("", ""), $arParams["NAME_TEMPLATE"]), $arUserTmp, $arParams["SHOW_LOGIN"] != "N")?></span><?
							}
							?></span><?
							if (IsModuleInstalled("intranet"))
							{
								?><span class="sonet-members-member-description"><?=$arMember["USER_WORK_POSITION"]?></span><?
							}
							if ($arMember["IS_OWNER"])
							{
								?><span class="sonet-members-caption"><?=GetMessage("SONET_GUE_T_OWNER")?></span><?
							}
							?><script type="text/javascript">
								BX.tooltip(<?=$arMember["USER_ID"]?>, "anchor_<?=$tooltip_id?>");
							</script><?
						?></span><?
					?></span><?
				}
			?></div><?

			if (StrLen($arResult["Moderators"]["NAV_STRING"]) > 0):
				?><div class="sonet-members-nav"><?=$arResult["Moderators"]["NAV_STRING"]?></div><?
			endif;

		?></div><?
	}

	if (is_array($arResult["Ban"]) && is_array($arResult["Ban"]["List"]))
	{
		?><div class="sonet-members-item"><?
			?><span class="sonet-members-item-name"><?=GetMessage("SONET_GUE_T_BAN_SUBTITLE")?></span><?
			?><div class="sonet-members-separator"></div><?
			if ($arResult["CurrentUserPerms"] && $arResult["CurrentUserPerms"]["UserCanModerateGroup"])
			{
				?><div class="sonet-members-item-menu"><?
					?><span class="sonet-members-item-menu-title" onclick="__GUEShowMenu(this, 'ban');"><?
						?><?=GetMessage("SONET_GUE_T_ACTIONS_TITLE")?>&nbsp;<?
						?><span class="sonet-members-item-menu-arrow"></span><?
					?></span>
				</div><?
			}
			?><div class="sonet-members-member-block-shift"><?
				foreach ($arResult["Ban"]["List"] as $arMember)
				{
					$tooltip_id = randString(8);
					$arUserTmp = array(
						"ID" => $arMember["USER_ID"],
						"NAME" => htmlspecialcharsback($arMember["USER_NAME"]),
						"LAST_NAME" => htmlspecialcharsback($arMember["USER_LAST_NAME"]),
						"SECOND_NAME" => htmlspecialcharsback($arMember["USER_SECOND_NAME"]),
						"LOGIN" => htmlspecialcharsback($arMember["USER_LOGIN"])
					);

					?><span class="sonet-members-member-block"><?
						?><span class="sonet-members-member-img-wrap" id="sonet-members-owner" <?
						if ($arResult["CurrentUserPerms"] && $arResult["CurrentUserPerms"]["UserCanModerateGroup"])
						{
							?>onclick="__GUEtoggleCheckbox(event, this, 'B<?=intval($arMember["USER_ID"])?>');"<?
						}
						?>><?
							?><span class="sonet-members-member-img" style="<?=(is_array($arMember["USER_PERSONAL_PHOTO_IMG"]) && strlen($arMember["USER_PERSONAL_PHOTO_IMG"]["src"]) > 0 ? "background: url('".$arMember["USER_PERSONAL_PHOTO_IMG"]["src"]."') no-repeat 0 0;" : "")?>"></span><?
							if ($arResult["CurrentUserPerms"] && $arResult["CurrentUserPerms"]["UserCanModerateGroup"])
							{							
								?><input class="sonet-members-checkbox" type="checkbox"/><?
							}
						?></span><?
						?><span class="sonet-members-member-text"><?
							?><span class="sonet-members-member-title"><?
							if ($arMember["SHOW_PROFILE_LINK"])
							{
								?><a id="anchor_<?=$tooltip_id?>" href="<?=htmlspecialcharsback($arMember["USER_PROFILE_URL"])?>" class="sonet-members-membet-link"><?=CUser::FormatName(str_replace(array("#NOBR#", "#/NOBR#"), array("", ""), $arParams["NAME_TEMPLATE"]), $arUserTmp, $arParams["SHOW_LOGIN"] != "N")?></a><?
							}
							else
							{
								?><span id="anchor_<?=$tooltip_id?>" class="sonet-members-membet-link"><?=CUser::FormatName(str_replace(array("#NOBR#", "#/NOBR#"), array("", ""), $arParams["NAME_TEMPLATE"]), $arUserTmp, $arParams["SHOW_LOGIN"] != "N")?></span><?
							}							
							?></span><?
							if (IsModuleInstalled("intranet"))
							{
								?><span class="sonet-members-member-description"><?=$arMember["USER_WORK_POSITION"]?></span><?
							}
						?></span><?
						?><script type="text/javascript">
							BX.tooltip(<?=$arMember["USER_ID"]?>, "anchor_<?=$tooltip_id?>");
						</script><?
					?></span><?
				}
			?></div><?

			if (StrLen($arResult["Ban"]["NAV_STRING"]) > 0):
				?><div class="sonet-members-nav"><?=$arResult["Ban"]["NAV_STRING"]?></div><?
			endif;

		?></div><?
	}	

	if (is_array($arResult["Users"]) && is_array($arResult["Users"]["List"]))
	{
		?><div class="sonet-members-item"><?
			?><span class="sonet-members-item-name"><?=GetMessage("SONET_GUE_T_USERS_SUBTITLE")?></span><?
			?><div class="sonet-members-separator"></div><?
			if ($arResult["CurrentUserPerms"] && $arResult["CurrentUserPerms"]["UserCanModerateGroup"])
			{
				?><div class="sonet-members-item-menu"><?
					?><span class="sonet-members-item-menu-title" onclick="__GUEShowMenu(this, 'users');"><?
						?><?=GetMessage("SONET_GUE_T_ACTIONS_TITLE")?>&nbsp;<?
						?><span class="sonet-members-item-menu-arrow"></span><?
					?></span>
				</div><?
			}
			?><div class="sonet-members-member-block-shift"><?
				foreach ($arResult["Users"]["List"] as $arMember)
				{
					$tooltip_id = randString(8);
					$arUserTmp = array(
						"ID" => $arMember["USER_ID"],
						"NAME" => htmlspecialcharsback($arMember["USER_NAME"]),
						"LAST_NAME" => htmlspecialcharsback($arMember["USER_LAST_NAME"]),
						"SECOND_NAME" => htmlspecialcharsback($arMember["USER_SECOND_NAME"]),
						"LOGIN" => htmlspecialcharsback($arMember["USER_LOGIN"])
					);

					?><span class="sonet-members-member-block"><?
						?><span class="sonet-members-member-img-wrap" id="sonet-members-owner" <?
						if ($arResult["CurrentUserPerms"] && $arResult["CurrentUserPerms"]["UserCanModifyGroup"])
						{
							?>onclick="__GUEtoggleCheckbox(event, this, 'U<?=intval($arMember["USER_ID"])?>');"<?
						}
						?>><?
							?><span class="sonet-members-member-img" style="<?=(is_array($arMember["USER_PERSONAL_PHOTO_IMG"]) && strlen($arMember["USER_PERSONAL_PHOTO_IMG"]["src"]) > 0 ? "background: url('".$arMember["USER_PERSONAL_PHOTO_IMG"]["src"]."') no-repeat 0 0;" : "")?>"></span><?
							if ($arResult["CurrentUserPerms"] && $arResult["CurrentUserPerms"]["UserCanModifyGroup"])
							{							
								?><input class="sonet-members-checkbox" type="checkbox"/><?
							}
						?></span><?
						?><span class="sonet-members-member-text"><?
							?><span class="sonet-members-member-title"><?
							if ($arMember["SHOW_PROFILE_LINK"])
							{
								?><a id="anchor_<?=$tooltip_id?>" href="<?=htmlspecialcharsback($arMember["USER_PROFILE_URL"])?>" class="sonet-members-membet-link"><?=CUser::FormatName(str_replace(array("#NOBR#", "#/NOBR#"), array("", ""), $arParams["NAME_TEMPLATE"]), $arUserTmp, $arParams["SHOW_LOGIN"] != "N")?></a><?
							}
							else
							{
								?><span id="anchor_<?=$tooltip_id?>" class="sonet-members-membet-link"><?=CUser::FormatName(str_replace(array("#NOBR#", "#/NOBR#"), array("", ""), $arParams["NAME_TEMPLATE"]), $arUserTmp, $arParams["SHOW_LOGIN"] != "N")?></span><?
							}
							?></span><?
							if (IsModuleInstalled("intranet"))
							{
								?><span class="sonet-members-member-description"><?=$arMember["USER_WORK_POSITION"]?></span><?
							}
						?></span><?
						?><script type="text/javascript">
							BX.tooltip(<?=$arMember["USER_ID"]?>, "anchor_<?=$tooltip_id?>");
						</script><?
					?></span><?
				}
			?></div><?

			if (StrLen($arResult["Users"]["NAV_STRING"]) > 0):
				?><div class="sonet-members-nav"><?=$arResult["Users"]["NAV_STRING"]?></div><?
			endif;

		?></div><?
	}
	
}
?>