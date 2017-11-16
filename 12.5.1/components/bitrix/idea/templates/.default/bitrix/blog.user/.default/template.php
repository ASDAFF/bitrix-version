<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

//if(!CModule::IncludeModule("socialnetwork"))
//	return false;

if($arParams["SET_NAV_CHAIN"] == "Y")
{
    $TitleName = trim($arResult["User"]["ALIAS"]);
    if($TitleName == "")
        $TitleName = trim($arResult["User"]["NAME"]." ".$arResult["User"]["LAST_NAME"]);
    if($TitleName == "")
        $TitleName = $arResult["User"]["LOGIN"];
    
    $APPLICATION->AddChainItem(GetMessage("IDEA_USER_INFO_NAV_TITLE", array("#NAME#" => $TitleName)));
    $APPLICATION->SetTitle(GetMessage("IDEA_USER_INFO_NAV_TITLE", array("#NAME#" => $TitleName)));
}
?>
<a href='<?=$arResult["USER_IDEA_LINK"]?>'><?=GetMessage("IDEA_USER_INFO_LINK_TITLE")?></a>

<h4 class="bx-idea-user-desc-contact"><?=htmlspecialcharsback($TitleName)?></h4>
<hr style="background: #E5E5E5; border:0px; height:1px; line-height:1px; "/>
    <?=$arResult["User"]["AVATAR_IMG"]?>
    <table width="100%" cellspacing="2" cellpadding="3"><?
            foreach ($arResult["DISPLAY_FIELDS"]['FIELDS_MAIN_DATA'] as $fieldName=>$Title):
                    if (StrLen($arResult["User"][$fieldName]) > 0):
                            ?><tr valign="top">
                                    <td width="40%"><?=$Title?>:</td>
                                    <td width="60%"><?=$arResult["User"][$fieldName]?></td>
                            </tr><?
                    endif;
            endforeach;
?>
    </table>

<h4 class="bx-idea-user-desc-contact"><?=GetMessage("GD_SONET_USER_DESC_CONTACT_TITLE") ?></h4>
<hr style="background: #E5E5E5; border:0px; height:1px; line-height:1px; "/>
    <table width="100%" cellspacing="2" cellpadding="3"><?
    foreach ($arResult["DISPLAY_FIELDS"]['FIELDS_CONTACT_DATA'] as $fieldName=>$Title):
        if (StrLen($arResult["User"][$fieldName]) > 0):
                ?><tr valign="top">
                        <td width="40%"><?=$Title?>:</td>
                        <td width="60%"><?=$arResult["User"][$fieldName]?></td>
                </tr><?
        endif;
    endforeach;
    ?></table>
<h4 class="bx-idea-user-desc-contact"><?=GetMessage("GD_SONET_USER_DESC_PERSONAL_TITLE") ?></h4>
<hr style="background: #E5E5E5; border:0px; height:1px; line-height:1px; "/>
    <table width="100%" cellspacing="2" cellpadding="3"><?
            foreach ($arResult["DISPLAY_FIELDS"]['FIELDS_PERSONAL_DATA'] as $fieldName=>$Title):
                    if (StrLen($arResult["User"][$fieldName]) > 0):
                            ?><tr valign="top">
                                    <td width="40%"><?=$Title?>:</td>
                                    <td width="60%"><?=$arResult["User"][$fieldName]?></td>
                            </tr><?
                    endif;
            endforeach;
    ?></table>