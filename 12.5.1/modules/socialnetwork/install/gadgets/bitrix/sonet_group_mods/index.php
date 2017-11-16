<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if(!CModule::IncludeModule("socialnetwork"))
	return false;

?>
<table width="100%">
<tr>
	<td><?
	if ($arGadgetParams["MODERATORS_LIST"]):
		?><table width="100%" border="0" class="sonet-user-profile-friend-box"><?
		foreach ($arGadgetParams["MODERATORS_LIST"] as $friend)
		{
			echo "<tr>";
			echo "<td align=\"left\">";
					
			$GLOBALS["APPLICATION"]->IncludeComponent("bitrix:main.user.link",
				'',
				array(
					"ID" => $friend["USER_ID"],
					"HTML_ID" => "group_moderators_".$friend["USER_ID"],
					"NAME" => htmlspecialcharsback($friend["USER_NAME"]),
					"LAST_NAME" => htmlspecialcharsback($friend["USER_LAST_NAME"]),
					"SECOND_NAME" => htmlspecialcharsback($friend["USER_SECOND_NAME"]),
					"LOGIN" => htmlspecialcharsback($friend["USER_LOGIN"]),
					"PERSONAL_PHOTO_IMG" => $friend["USER_PERSONAL_PHOTO_IMG"],
					"PROFILE_URL" => htmlspecialcharsback($friend["USER_PROFILE_URL"]),
					"THUMBNAIL_LIST_SIZE" => $arParams["THUMBNAIL_LIST_SIZE"],
					"PATH_TO_SONET_MESSAGES_CHAT" => $arParams["~PATH_TO_MESSAGES_CHAT"],
					"PATH_TO_SONET_USER_PROFILE" => $arParams["~PATH_TO_USER"],
					"PATH_TO_VIDEO_CALL" => $arParams["~PATH_TO_VIDEO_CALL"],
					"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
					"SHOW_YEAR" => $arParams["SHOW_YEAR"],
					"CACHE_TYPE" => $arGadgetParams["CACHE_TYPE"],
					"CACHE_TIME" => $arGadgetParams["CACHE_TIME"],
					"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
					"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
					"PATH_TO_CONPANY_DEPARTMENT" => $arParams["~PATH_TO_CONPANY_DEPARTMENT"],
				),
				false, 
				array("HIDE_ICONS" => "Y")
			);
									
			echo "</td>";
			echo "</tr>";
		}
		?></table>
		<br>
	<?else:?>
		<?= GetMessage("GD_SONET_GROUP_MODS_NO_MODS") ?>
		<br><br>
	<?endif;?>
	</td>
</tr>
</table>