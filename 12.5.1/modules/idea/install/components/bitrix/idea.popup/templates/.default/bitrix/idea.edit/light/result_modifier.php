<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
if($_REQUEST["AJAX"] == "Y" && $_REQUEST["save"] == "Y" && $arResult["imageUploadFrame"]!= "Y")
{
    $APPLICATION->RestartBuffer();

    if(strlen($arResult["SUCCESS_MESSAGE"])>0)
    {
            ?><br/>
            <div class="blog-textinfo blog-note-box">
                    <div class="blog-textinfo-text">
                            <?=$arResult["SUCCESS_MESSAGE"]?>
                            <script type="text/javascript">
                                try{
                                    top.JSIdeaDialog.Dialog.EditorRestart();
                                    top.BX.cleanNode('idea-list-container', true);
                                    top.BX.fireEvent(top.BX('idea-side-button'), 'click');
                                }catch(e){}
                            </script>
                    </div>
            </div>
            <?
    }
    if(strlen($arResult["ERROR_MESSAGE"])>0)
    {
            ?><br/>
            <div class="blog-errors blog-note-box blog-note-error">
                    <div class="blog-error-text">
                            <?=$arResult["ERROR_MESSAGE"]?>
                            <script type="text/javascript">
                                var SID = top.BX('sessid');
                                if(SID) 
                                    SID.value = '<?=bitrix_sessid()?>';
                            </script>
                    </div>
            </div>
            <?
    }
    if(strlen($arResult["FATAL_MESSAGE"])>0)
    {
            ?><br/>
            <div class="blog-errors blog-note-box blog-note-error">
                    <div class="blog-error-text">
                            <?=$arResult["FATAL_MESSAGE"]?>
                    </div>
            </div>
            <?
    }
    elseif(strlen($arResult["UTIL_MESSAGE"])>0)
    {
            ?><br/>
            <div class="blog-textinfo blog-note-box">
                    <div class="blog-textinfo-text">
                            <?=$arResult["UTIL_MESSAGE"]?>
                    </div>
            </div>
            <?
    }
    
    die();
}

$arResult["POST_PROPERTIES"]["UF_SHOW_BLOCK"] = false;
if(is_array($arResult["POST_PROPERTIES"]["DATA"]) && !empty($arResult["POST_PROPERTIES"]["DATA"]))
{
    $IsNew = !array_key_exists("Post", $arResult);
    foreach ($arResult["POST_PROPERTIES"]["DATA"] as $FIELD_NAME => $arPostField):

        //UF prepare templates
        $arResult["POST_PROPERTIES"]["DATA"][$FIELD_NAME]["UF_TEMPLATE"] = $arPostField["USER_TYPE"]["USER_TYPE_ID"];
        if($FIELD_NAME == CIdeaManagment::UFCategroryCodeField)
            $arResult["POST_PROPERTIES"]["DATA"][$FIELD_NAME]["UF_TEMPLATE"] .= '_category';
        elseif($FIELD_NAME == CIdeaManagment::UFOriginalIdField)
            $arResult["POST_PROPERTIES"]["DATA"][$FIELD_NAME]["UF_TEMPLATE"] .= '_dublicate';

        //UF prepare for display
        $arResult["POST_PROPERTIES"]["DATA"][$FIELD_NAME]["UF_SHOW"] = true;
        //Skip binded offical props
        if($FIELD_NAME==CIdeaManagment::UFAnswerIdField)
            $arResult["POST_PROPERTIES"]["DATA"][$FIELD_NAME]["UF_SHOW"] = false;
        //Skip status for light variant
        if($FIELD_NAME==CIdeaManagment::UFStatusField)
            $arResult["POST_PROPERTIES"]["DATA"][$FIELD_NAME]["UF_SHOW"] = false;
        //If no admin skip dublicate field
        if($FIELD_NAME==CIdeaManagment::UFOriginalIdField && (!$arResult["IDEA_MODERATOR"] || $IsNew))
            $arResult["POST_PROPERTIES"]["DATA"][$FIELD_NAME]["UF_SHOW"] = false;
        //If not admin and not NEW - skip
        if(!$IsNew && $FIELD_NAME==CIdeaManagment::UFCategroryCodeField && !$arResult["IDEA_MODERATOR"])//existed
            $arResult["POST_PROPERTIES"]["DATA"][$FIELD_NAME]["UF_SHOW"] = false;

        if($arResult["POST_PROPERTIES"]["DATA"][$FIELD_NAME]["UF_SHOW"])
            $arResult["POST_PROPERTIES"]["UF_SHOW_BLOCK"] = true;

    endforeach;
}
?>