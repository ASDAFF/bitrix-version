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
	CUtil::InitJSCore(array("tooltip", "popup"));
	if(strlen($arResult["ErrorMessage"])>0)
	{
		?><span class='errortext'><?=$arResult["ErrorMessage"]?></span><br /><br /><?
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

	if ($arResult["CurrentUserPerms"]["UserCanInitiate"])
	{
		?><script type="text/javascript">
		BX.message({
			GUEGroupId: <?=intval($arParams["GROUP_ID"])?>,
			GUEGroupName: '<?=CUtil::JSEscape($arResult["Group"]["NAME"])?>'
		});		
		</script><?
		?><?
			$APPLICATION->IncludeComponent(
				"bitrix:socialnetwork.group.iframe.popup",
				".default",
				array(
					"PATH_TO_GROUP_INVITE" => htmlspecialcharsback($arResult["Urls"]["GroupEdit"]).(strpos($arResult["Urls"]["GroupEdit"], "?") === false ? "?" : "&")."tab=invite",
					"ON_GROUP_ADDED" => "BX.DoNothing",
					"ON_GROUP_CHANGED" => "BX.DoNothing",
					"ON_GROUP_DELETED" => "BX.DoNothing"
				),
				null,
				array("HIDE_ICONS" => "Y")
			);
		?><?
	}

	?><div class="invite-main-wrap">
		<div class="invite-title"><?=GetMessage("SONET_GRE_T_SUBTITLE_IN")?></div>
		<form method="post" name="form1" action="<?=POST_FORM_ACTION_URI?>" enctype="multipart/form-data" id="form_requests"><?
		if ($arResult["Requests"] && $arResult["Requests"]["List"])
		{
			?><table class="invite-list" cellspacing="0">
			<tr>
				<td class="invite-list-header"><input type="checkbox" title="<?=GetMessage("SONET_GRE_T_CHECK_ALL")?>" onclick="__GRECheckedAll(this)"/></td>
				<td class="invite-list-header" colspan="2"><?=GetMessage("SONET_GRE_T_SENDER")?></td>
				<td class="invite-list-header"><?=GetMessage("SONET_GRE_T_MESSAGE_IN")?></td>
			</tr><?
			$ind = 0;
			foreach ($arResult["Requests"]["List"] as $arRequest)
			{
				$tooltip_id = randString(8);

				?><tr>
					<td class="invite-list-checkbox"><div class="invite-active-block"><?
						?><input type="checkbox" name="checked_<?= $ind ?>" value="Y" onclick="BX.toggleClass(this.parentNode.parentNode.parentNode, 'invite-list-active');" /><?
						?><input type="hidden" name="id_<?=$ind ?>" value="<?=$arRequest["ID"] ?>"><?
					?></div></td>
					<td class="invite-list-img"><div class="invite-active-block"><span class="invite-list-img-image" style="<?=(is_array($arRequest["USER_PERSONAL_PHOTO_IMG"]) && strlen($arRequest["USER_PERSONAL_PHOTO_IMG"]["src"]) > 0 ? "background: url('".$arRequest["USER_PERSONAL_PHOTO_IMG"]["src"]."') no-repeat 0 0;" : "")?>"></span></div></td>
					<td class="invite-list-name">
						<div class="invite-active-block"><?
						if ($arRequest["SHOW_PROFILE_LINK"])
						{
							?><a id="anchor_<?=$tooltip_id?>" href="<?=htmlspecialcharsback($arRequest["USER_PROFILE_URL"])?>" class="invite-user-link"><?=$arRequest["USER_NAME_FORMATTED"]?></a><?
						}
						else
						{
							?><span id="anchor_<?=$tooltip_id?>" class="invite-user-link"><?=$arRequest["USER_NAME_FORMATTED"]?></span><?
						}
						?></div><?
						?><script type="text/javascript">
							BX.tooltip(<?=$arRequest["USER_ID"]?>, "anchor_<?=$tooltip_id?>");
						</script><?
					?></td>
					<td class="invite-list-message"><div class="invite-active-block"><?=$arRequest["MESSAGE"]?><br /><i><?=$arRequest["DATE_CREATE"]?></i></div></td>
				</tr><?

				$ind++;
			}
			?></table>


			<div class="invite-list-nav"><?
			if (StrLen($arResult["Requests"]["NAV_STRING"]) > 0):
				?><?=$arResult["Requests"]["NAV_STRING"]?><br /><br /><?
			endif;
			?></div><?
		}
		else
		{
			?><span class="sonet-group-requests-info"><?=GetMessage("SONET_GRE_T_NO_REQUESTS")?><br /><?=GetMessage("SONET_GRE_T_NO_REQUESTS_DESCR")?></span><?
		}

		?><div class="invite-buttons-block"><?
		if ($arResult["Requests"] && $arResult["Requests"]["List"])
		{
			?><a class="sonet-group-requests-smbutton sonet-group-requests-smbutton-accept" href="#" onclick="__GRESubmitForm('in', 'accept');"><?
				?><span class="sonet-group-requests-smbutton-left"></span><?
				?><span class="sonet-group-requests-smbutton-text"><?=GetMessage("SONET_GRE_T_DO_SAVE")?></span><?
				?><span class="sonet-group-requests-smbutton-right"></span><?
			?></a><?
			?><span class="popup-window-button popup-window-button-link popup-window-button-link-cancel" onclick="__GRESubmitForm('in', 'reject');"><?
				?><span class="popup-window-button-link-text"><?=GetMessage("SONET_GRE_T_REJECT")?></span><?
			?></span><?
		}
		?><a class="sonet-group-requests-smbutton" href="#" onclick="__GREAddToUsers(event);"<?=($arResult["Requests"] && $arResult["Requests"]["List"] ? ' style="float: right;"' : '')?>><?
			?><span class="sonet-group-requests-smbutton-left"></span><?
			?><span class="sonet-group-requests-smbutton-text"><?=GetMessage("SONET_GRE_T_INVITE")?></span><?
			?><span class="sonet-group-requests-smbutton-right"></span><?
		?></a><?
		?></div><?
		?><input type="hidden" name="max_count" value="<?= $ind ?>">
		<input type="hidden" name="type" value="in">
		<input type="hidden" name="action" id="requests_action_in" value=""><?
		?><?=bitrix_sessid_post()?><?
		?></form><?
	?></div><?

	?><div class="invite-main-wrap invite-main-wrap-out">
		<div class="invite-title"><?=GetMessage("SONET_GRE_T_SUBTITLE_OUT")?></div>
		<form method="post" name="form2" action="<?=POST_FORM_ACTION_URI?>" enctype="multipart/form-data" id="form_requests_out"><?
		if ($arResult["RequestsOut"] && $arResult["RequestsOut"]["List"])
		{
			?><table class="invite-list" cellspacing="0">
			<tr>
				<td class="invite-list-header"><input type="checkbox" title="<?=GetMessage("SONET_GRE_T_CHECK_ALL")?>" onclick="__GRECheckedAll(this)"/></td>
				<td class="invite-list-header" colspan="2"><?=GetMessage("SONET_GRE_T_RECIPIENT")?></td>
				<td class="invite-list-header"><?=GetMessage("SONET_GRE_T_MESSAGE_OUT")?></td>
			</tr><?
			$ind = 0;
			foreach ($arResult["RequestsOut"]["List"] as $arRequest)
			{
				$tooltip_id = randString(8);

				?><tr>
					<td class="invite-list-checkbox"><div class="invite-active-block"><?
						?><input type="checkbox" name="checked_<?= $ind ?>" value="Y" onclick="BX.toggleClass(this.parentNode.parentNode.parentNode, 'invite-list-active');" /><?
						?><input type="hidden" name="id_<?=$ind ?>" value="<?=$arRequest["ID"] ?>"><?
					?></div></td>
					<td class="invite-list-img"><div class="invite-active-block"><span class="invite-list-img-image" style="<?=(is_array($arRequest["USER_PERSONAL_PHOTO_IMG"]) && strlen($arRequest["USER_PERSONAL_PHOTO_IMG"]["src"]) > 0 ? "background: url('".$arRequest["USER_PERSONAL_PHOTO_IMG"]["src"]."') no-repeat 0 0;" : "")?>"></span></div></td>
					<td class="invite-list-name">
						<div class="invite-active-block"><?
						if ($arRequest["SHOW_PROFILE_LINK"])
						{
							?><a id="anchor_<?=$tooltip_id?>" href="<?=htmlspecialcharsback($arRequest["USER_PROFILE_URL"])?>" class="invite-user-link"><?=$arRequest["USER_NAME_FORMATTED"]?></a><?
						}
						else
						{
							?><span id="anchor_<?=$tooltip_id?>" class="invite-user-link"><?=$arRequest["USER_NAME_FORMATTED"]?></span><?
						}
						?></div><?
						?><script type="text/javascript">
							BX.tooltip(<?=$arRequest["USER_ID"]?>, "anchor_<?=$tooltip_id?>");
						</script><?
					?></td>
					<td class="invite-list-message"><div class="invite-active-block"><?=$arRequest["MESSAGE"]?><br /><i><?=$arRequest["DATE_CREATE"]?></i></div></td>
				</tr><?

				$ind++;
			}
			?></table>

			<div class="invite-list-nav"><?
			if (StrLen($arResult["RequestsOut"]["NAV_STRING"]) > 0):
				?><?=$arResult["RequestsOut"]["NAV_STRING"]?><br /><br /><?
			endif;
			?></div><?
		}
		else
		{
			?><span class="sonet-group-requests-info"><?=GetMessage("SONET_GRE_T_NO_REQUESTS_OUT")?><br /><?=GetMessage("SONET_GRE_T_NO_REQUESTS_OUT_DESCR")?></span><?
		}

		?><div class="invite-buttons-block"><?
		if ($arResult["RequestsOut"] && $arResult["RequestsOut"]["List"])
		{
			?><a class="sonet-group-requests-smbutton" href="#" onclick="__GRESubmitForm('out', 'reject');"><?
				?><span class="sonet-group-requests-smbutton-left"></span><?
				?><span class="sonet-group-requests-smbutton-text"><?=GetMessage("SONET_GRE_T_REJECT_OUT")?></span><?
				?><span class="sonet-group-requests-smbutton-right"></span><?
			?></a><?
		}
		?><a class="sonet-group-requests-smbutton" href="#" onclick="__GREAddToUsers(event);"<?=($arResult["RequestsOut"] && $arResult["RequestsOut"]["List"] ? ' style="float: right;"' : '')?>><?
			?><span class="sonet-group-requests-smbutton-left"></span><?
			?><span class="sonet-group-requests-smbutton-text"><?=GetMessage("SONET_GRE_T_INVITE")?></span><?
			?><span class="sonet-group-requests-smbutton-right"></span><?
		?></a><?
		?></div><?
		?><input type="hidden" name="max_count" value="<?= $ind ?>">
		<input type="hidden" name="type" value="out">
		<input type="hidden" name="action" id="requests_action_out" value=""><?
		?><?=bitrix_sessid_post()?><?
		?></form><?
	?></div><?	
}
?>