<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if(!CModule::IncludeModule("socialnetwork"))
	return false;

if (CSocNetUser::IsFriendsAllowed() && (!CModule::IncludeModule('extranet') || !CExtranet::IsExtranetSite())):

	if ($arGadgetParams["CAN_VIEW_FRIENDS"]):
		if ($arGadgetParams["FRIENDS_LIST"]):
			?><table width="100%" border="0" class="sonet-user-profile-friend-box"><?
			foreach ($arGadgetParams["FRIENDS_LIST"] as $friend)
			{
				echo "<tr>";
				echo "<td align=\"left\">";

				$GLOBALS["APPLICATION"]->IncludeComponent("bitrix:main.user.link",
						'',
						array(
							"ID" => $friend["USER_ID"],
							"HTML_ID" => "user_profile_friends_".$friend["USER_ID"],
							"NAME" => htmlspecialcharsback($friend["USER_NAME"]),
							"LAST_NAME" => htmlspecialcharsback($friend["USER_LAST_NAME"]),
							"SECOND_NAME" => htmlspecialcharsback($friend["USER_SECOND_NAME"]),
							"LOGIN" => htmlspecialcharsback($friend["USER_LOGIN"]),
							"PERSONAL_PHOTO_IMG" => $friend["USER_PERSONAL_PHOTO_IMG"],
							"PROFILE_URL" => $friend["USER_PROFILE_URL"],
							"PATH_TO_SONET_MESSAGES_CHAT" => $arParams["~PATH_TO_MESSAGES_CHAT"],
							"PATH_TO_VIDEO_CALL" => $arParams["~PATH_TO_VIDEO_CALL"],
							"PATH_TO_SONET_USER_PROFILE" => $arParams["~PATH_TO_USER"],
							"PATH_TO_CONPANY_DEPARTMENT" => $arParams["~PATH_TO_CONPANY_DEPARTMENT"],
							"THUMBNAIL_LIST_SIZE" => $arParams["THUMBNAIL_LIST_SIZE"],
							"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
							"SHOW_YEAR" => $arParams["SHOW_YEAR"],
							"CACHE_TYPE" => $arParams["CACHE_TYPE"],
							"CACHE_TIME" => $arParams["CACHE_TIME"],
							"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
							"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
						),
						false, 
						array("HIDE_ICONS" => "Y")
					);
				echo "</td>";
				echo "</tr>";
			}
			?></table>
			<br>
			<a href="<?= $arGadgetParams["URL_FRIENDS"] ?>"><?= GetMessage("GD_SONET_USER_FRIENDS_ALL_FRIENDS") ?> (<?= $arGadgetParams["FRIENDS_COUNT"] ?>)</a>
			<br><?
		else:
			?><?= GetMessage("GD_SONET_USER_FRIENDS_NO_FRIENDS") ?>
			<br><br><?
		endif;
	else:
		?><?= GetMessage("GD_SONET_USER_FRIENDS_FR_UNAVAIL") ?>
		<br><br><?
	endif;

	if ($arGadgetParams["IS_CURRENT_USER"]):
		?><a href="<?= $arGadgetParams["URL_SEARCH"] ?>"><?= GetMessage("GD_SONET_USER_FRIENDS_FR_SEARCH") ?></a><br />
		<a href="<?= $arGadgetParams["URL_LOG_USERS"] ?>"><?= GetMessage("GD_SONET_USER_FRIENDS_LOG_USERS") ?></a><?
	endif;

else:
	echo GetMessage('GD_SONET_USER_FRIENDS_NOT_ALLOWED');
endif;
?>