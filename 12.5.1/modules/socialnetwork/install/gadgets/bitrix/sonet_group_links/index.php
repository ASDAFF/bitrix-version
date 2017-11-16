<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if(!CModule::IncludeModule("socialnetwork"))
	return false;

$arGadgetParams["SHOW_FEATURES"] = "Y";
?>
<table width="100%">
<tr>
	<td><?=htmlspecialcharsback($arGadgetParams["IMAGE"])?></td>
</tr>
<tr>
	<td><?
	if ($GLOBALS["USER"]->IsAuthorized()):
		if (!IsModuleInstalled("im") && $arGadgetParams["CAN_SPAM_GROUP"] && !$arGadgetParams["HIDE_ARCHIVE_LINKS"]):
			?><div class="bx-group-control">
			<ul>
				<li class="bx-icon-message"><a href="<?= $arGadgetParams["URL_MESSAGE_TO_GROUP"] ?>" onclick="window.open('<?= $arGadgetParams["URL_MESSAGE_TO_GROUP"] ?>', '', 'location=yes,status=no,scrollbars=yes,resizable=yes,width=750,height=550,top='+Math.floor((screen.height - 550)/2-14)+',left='+Math.floor((screen.width - 750)/2-5)); return false;" title="<?= GetMessage("GD_SONET_GROUP_LINKS_SEND_MESSAGE_GROUP_TITLE") ?>"><?= GetMessage("GD_SONET_GROUP_LINKS_SEND_MESSAGE_GROUP") ?></a></li>
			</ul>
			</div><?
		endif;
							
		if ($arGadgetParams["CAN_MODIFY_GROUP"]):
			?><div class="bx-group-control">
			<ul>
				<li class="bx-icon-edit"><a href="<?= $arGadgetParams["URL_EDIT"] ?>" onclick="sonetGroupIFramePopup.Edit(<?=$arParams["SOCNET_GROUP_ID"]?>, '<?=CUtil::JSEscape($arGadgetParams["NAME"])?>', event); return false;"><?= GetMessage("GD_SONET_GROUP_LINKS_ACT_EDIT") ?></a></li><? 
				if (!$arGadgetParams["HIDE_ARCHIVE_LINKS"] && $arGadgetParams["SHOW_FEATURES"] == "Y"):
					?><li class="bx-icon-settings"><a href="<?= $arGadgetParams["URL_FEATURES"] ?>"><?= GetMessage("GD_SONET_GROUP_LINKS_ACT_FEAT") ?></a></li><?
				endif;
				?><li class="bx-icon-del"><a href="<?= $arGadgetParams["URL_GROUP_DELETE"] ?>"><?= GetMessage("GD_SONET_GROUP_LINKS_ACT_DELETE") ?></a></li>
			</ul>
			</div><?
		endif;
	endif;

	?><div class="bx-group-control">
	<ul><?
		?><li class="bx-icon-memb-view"><a href="<?= $arGadgetParams["URL_GROUP_USERS"] ?>"><?= GetMessage("GD_SONET_GROUP_LINKS_ACT_USER1") ?></a></li><?
		if ($GLOBALS["USER"]->IsAuthorized()):
			if ($arGadgetParams["CAN_INITIATE"] && !$arGadgetParams["HIDE_ARCHIVE_LINKS"]):
				?><li class="bx-icon-invite"><a href="<?= $arGadgetParams["URL_GROUP_REQUEST_SEARCH"] ?>" onclick="sonetGroupIFramePopup.Invite(<?=$arParams["SOCNET_GROUP_ID"]?>, '<?=CUtil::JSEscape($arGadgetParams["NAME"])?>', event); return false;"><?= GetMessage("GD_SONET_GROUP_LINKS_ACT_REQU") ?></a></li><?
				if (!CModule::IncludeModule('extranet') || ($arGadgetParams["OPENED"] != "Y" && !CExtranet::IsExtranetSite())):
					?><li class="bx-icon-requests"><a href="<?= $arGadgetParams["URL_GROUP_REQUESTS"] ?>"><?= GetMessage("GD_SONET_GROUP_LINKS_ACT_VREQU") ?></a></li><?
				else:
				?><li class="bx-icon-requests"><a href="<?= $arGadgetParams["URL_GROUP_REQUESTS"] ?>"><?= GetMessage("GD_SONET_GROUP_LINKS_ACT_VREQU_OUT") ?></a></li><?
				endif;
			endif;
			if ((!$arGadgetParams["USER_ROLE"] || ($arGadgetParams["USER_ROLE"] != SONET_ROLES_REQUEST)) && !$arGadgetParams["HIDE_ARCHIVE_LINKS"]):
				?><li class="bx-icon-join"><a href="<?= $arGadgetParams["URL_USER_REQUEST_GROUP"] ?>"><?= GetMessage("GD_SONET_GROUP_LINKS_ACT_JOIN") ?></a></li><?
			endif;
			if ($arGadgetParams["USER_IS_MEMBER"] && !$arGadgetParams["USER_IS_OWNER"]):
				?><li class="bx-icon-leave"><a href="<?= $arGadgetParams["URL_USER_LEAVE_GROUP"] ?>"><?= GetMessage("GD_SONET_GROUP_LINKS_ACT_EXIT") ?></a></li><?
			endif;
			if (!$arGadgetParams["HIDE_ARCHIVE_LINKS"]):
				?><li class="bx-icon-subscribe"><a href="<?= $arGadgetParams["URL_SUBSCRIBE"] ?>"><?= GetMessage("GD_SONET_GROUP_LINKS_ACT_SUBSCRIBE") ?></a></li><?
			endif;
		endif;
		?>
	</ul>
	</div>
	</td>
</tr>
</table>