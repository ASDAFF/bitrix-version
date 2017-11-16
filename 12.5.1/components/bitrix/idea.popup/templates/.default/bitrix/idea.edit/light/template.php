<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<?if($arResult["FATAL_MESSAGE_CODE"] == "NO_RIGHTS"):?>
<?unset($_POST["AJAX"], $_POST["ACTION"])?>
    <br/>
    <div class="blog-errors blog-note-box blog-note-error">
            <div class="blog-error-text">
                    <?=$arResult["FATAL_MESSAGE"]?>
            </div>
    </div>
    <br/>
    <?$APPLICATION->IncludeComponent(
            "bitrix:system.auth.form",
            $arParams["AUTH_TEMPLATE"],
            Array(
                    "REGISTER_URL" => $arParams["REGISTER_URL"],
                    "FORGOT_PASSWORD_URL" => $arParams["FORGOT_PASSWORD_URL"],
                    "PROFILE_URL" => "",
                    "SHOW_ERRORS" => "N"
            )
    );?>
<?
return;
endif;
?>

<div class="blog-post-edit idea-post-edit-light">
    <div id="idea-ajax-notification"></div>
<?
// Frame with file input to ajax uploading in WYSIWYG editor dialog
if($arResult["imageUploadFrame"] == "Y")
{
        if (!isset($_POST["blog_upload_image"]))
        {
                ?>
                <html>
                        <head></head>
                        <body style="overflow: hidden; margin: 0!important; padding: 6px 0 0 0!important;">
                        <form action="<?=POST_FORM_ACTION_URI?>" method="post" enctype="multipart/form-data" style="margin: 0!important; padding: 0!important;">
                        <?=bitrix_sessid_post()?>
                        <input type="file" size="30" name="BLOG_UPLOAD_FILE" id="bx_lhed_blog_img_input" />
                        <input type="hidden" value="Y" name="blog_upload_image"/>
                        </form></body>
                </html>
                <?
        }
        else
        {
                ?>
                <script>
                        <?if(!empty($arResult["Image"])):?>
                        var imgTable = top.BX('blog-post-image');
                        if (imgTable)
                        {
                                imgTable.innerHTML += '<div class="blog-post-image-item"><div class="blog-post-image-item-border"><?=$arResult["ImageModified"]?></div>' +
                                '<div class="blog-post-image-item-input"><input name=IMAGE_ID_title[<?=$arResult["Image"]["ID"]?>] value="<?=Cutil::JSEscape($arResult["Image"]["TITLE'"])?>"></div>' +
                                '<div><input type=checkbox name=IMAGE_ID_del[<?=$arResult["Image"]["ID"]?>] id=img_del_<?=$arResult["Image"]["ID"]?>> <label for=img_del_<?=$arResult["Image"]["ID"]?>><?=GetMessage("BLOG_DELETE")?></label></div></div>';
                        }

                        top.arImages.push('<?=$arResult["Image"]["ID"]?>');
                        window.bxBlogImageId = top.bxBlogImageId = '<?=$arResult["Image"]["ID"]?>';
                        <?elseif(strlen($arResult["ERROR_MESSAGE"]) > 0):?>
                                alert('<?=$arResult["ERROR_MESSAGE"]?>');
                        <?endif;?>
                </script>
                <?
        }
        die();
}
else
{
        ?>
        <form action="<?=POST_FORM_ACTION_URI?>" name="REPLIER_LIGHT" id="REPLIER-form-light" method="post" enctype="multipart/form-data">
        <?=bitrix_sessid_post();?>

        <div class="blog-post-field blog-post-field-code blog-edit-field blog-edit-field-code" style="display:none;">
            <label for="CODE" class="blog-edit-field-caption"><?=GetMessage("BLOG_P_CODE")?>: </label><?=$arResult["PATH_TO_POST1"]?><a href="javascript:changeCode()" title="<?=GetMessage("BLOG_CHANGE_CODE")?>" id="post-code-text"><?=(strlen($arResult["PostToShow"]["CODE"]) > 0) ? $arResult["PostToShow"]["CODE"] : GetMessage("BLOG_P_CODE");?></a><span id="post-code-input"><input maxlength="255" size="70" tabindex="2" type="text" name="CODE" id="CODE" value="<?=$arResult["PostToShow"]["CODE"]?>"><image id="code_link" title="<?echo GetMessage("BLOG_LINK_TIP")?>" class="linked" src="/bitrix/themes/.default/icons/iblock/<?if($bLinked) echo 'link.gif'; else echo 'unlink.gif';?>" onclick="set_linked()" /> </span><?=$arResult["PATH_TO_POST2"]?>
        </div>

        <div>
        <div class="blog-post-fields blog-edit-fields">
                <div class="field-title-idea-title"><?=GetMessage("IDEA_TITLE_TITLE")?></div>
                <div class="blog-post-field blog-post-field-title blog-edit-field blog-edit-field-title">
                        <input maxlength="255" size="70" tabindex="1" type="text" name="POST_TITLE" id="POST_TITLE" value="<?=$arResult["PostToShow"]["TITLE"]?>">
                </div>
                <div class="blog-clear-float"></div>		
                <?if($arParams["ALLOW_POST_CODE"]):?>
                        <?CUtil::InitJSCore(array('translit'));
                        $bLinked = $arParams["ID"] <= 0 && $_POST["linked_state"]!=='N';

                        ?>
                        <input type="hidden" name="linked_state" id="linked_state" value="<?if($bLinked) echo 'Y'; else echo 'N';?>">
                        <script>
                        var oldValue = '';
                        var linked=<?if($bLinked) echo 'true'; else echo 'false';?>;

                        function set_linked()
                        {
                                linked=!linked;
                                var code_link = BX('code_link');
                                if(code_link)
                                {
                                        if(linked)
                                                code_link.src='/bitrix/themes/.default/icons/iblock/link.gif';
                                        else
                                                code_link.src='/bitrix/themes/.default/icons/iblock/unlink.gif';
                                }
                                var linked_state = BX('linked_state');
                                if(linked_state)
                                {
                                        if(linked)
                                                linked_state.value='Y';
                                        else
                                                linked_state.value='N';
                                }
                        }

                        function transliterate()
                        {
                                if(linked)
                                {
                                        var from = BX('POST_TITLE');
                                        var to = BX('CODE');
                                        //var toText = BX('post-code-text');
                                        if(from && to && oldValue != from.value)
                                        {
                                                BX.translit(from.value, {
                                                        'max_len' : 70,
                                                        'change_case' : 'L',
                                                        'replace_space' : '-',
                                                        'replace_other' : '',
                                                        'delete_repeat_replace' : true,
                                                        'use_google' : <?echo $arParams['USE_GOOGLE_CODE'] == 'Y'? 'true': 'false'?>,
                                                        'callback' : function(result){
                                                            to.value = result;
                                                            //toText.innerHTML = result;
                                                            setTimeout('transliterate()', 250);
                                                        }
                                                });
                                                oldValue = from.value;
                                        }
                                        else
                                        {
                                                setTimeout('transliterate()', 250);
                                        }
                                }
                                else
                                {
                                        setTimeout('transliterate()', 250);
                                }
                        }

                        function changeCode()
                        {
                                BX("post-code-text").style.display = "none";
                                BX("post-code-input").style.display = "inline";
                        }
                        transliterate();
                        </script>
                        <div class="blog-clear-float"></div>
                <?endif;?>
                <div class="blog-post-field blog-post-field-date blog-edit-field blog-edit-field-post-date">
                        <span><input type="hidden" id="DATE_PUBLISH_DEF" name="DATE_PUBLISH_DEF" value="<?=$arResult["PostToShow"]["DATE_PUBLISH"];?>">
                        <?/*<div id="date-publ-text">
                                <a href="javascript:changeDate()" title="<?=GetMessage("BLOG_DATE")?>"><?=$arResult["PostToShow"]["DATE_PUBLISH"];?></a>
                        </div>*/?>
                        <div id="date-publ" style="display:none;">
                        <?
                                $APPLICATION->IncludeComponent(
                                        'bitrix:main.calendar',
                                        '',
                                        array(
                                                'SHOW_INPUT' => 'Y',
                                                'FORM_NAME' => 'REPLIER_LIGHT',
                                                'INPUT_NAME' => 'DATE_PUBLISH',
                                                'INPUT_VALUE' => $arResult["PostToShow"]["DATE_PUBLISH"],
                                                'SHOW_TIME' => 'Y'
                                        ),
                                        null,
                                        array('HIDE_ICONS' => 'Y')
                                );
                        ?>
                        </div></span>
                </div>
                <div class="blog-clear-float"></div>
        </div>

        <div class="field-title-idea-text"><?=GetMessage("IDEA_DESCRIPTION_TITLE")?></div>
        <div class="blog-post-message blog-edit-editor-area blog-edit-field-text">
                <div class="blog-comment-field">
                        <?include($_SERVER["DOCUMENT_ROOT"].$templateFolder."/lhe.php");?>
                </div>
                <div class="blog-post-field blog-post-field-images blog-edit-field" id="blog-post-image">
                <?
                if (!empty($arResult["Images"]))
                {
                        ?><div class="blog-field-title-images"><?=GetMessage("BLOG_P_IMAGES")?></div><?
                        foreach($arResult["Images"] as $aImg)
                        {
                                ?>
                                        <div class="blog-post-image-item">
                                                <div class="blog-post-image-item-border"><?=$aImg["FileShow"]?></div>

                                                        <div class="blog-post-image-item-input">
                                                                <input name="IMAGE_ID_title[<?=$aImg["ID"]?>]" value="<?=$aImg["TITLE"]?>" title="<?=GetMessage("BLOG_BLOG_IN_IMAGES_TITLE")?>">
                                                        </div>
                                                        <div>
                                                                <input type="checkbox" name="IMAGE_ID_del[<?=$aImg["ID"]?>]" id="img_del_<?=$aImg["ID"]?>"> <label for="img_del_<?=$aImg["ID"]?>"><?=GetMessage("BLOG_DELETE")?></label>
                                                        </div>

                                        </div>
                                <?
                        }
                }
                ?>
                </div>
        </div>
        <div class="blog-clear-float"></div>
        <div class="blog-post-field blog-post-field-category blog-edit-field blog-edit-field-tags">
                <div class="field-title-idea-tags"><?=GetMessage("BLOG_CATEGORY")?></div>
                <?
                if(IsModuleInstalled("search"))
                {
                        $arSParams = Array(
                            "NAME"	=>	"TAGS",
                            "VALUE"	=>	$arResult["PostToShow"]["CategoryText"],
                            "arrFILTER"	=>	"blog",
                            "PAGE_ELEMENTS"	=>	"10",
                            "SORT_BY_CNT"	=>	"Y",
                            "TEXT" => 'size="30" tabindex="3"'
                        );
                        $APPLICATION->IncludeComponent("bitrix:search.tags.input", ".default", $arSParams);
                }
                else
                {
                        ?><input type="text" id="TAGS" tabindex="3" name="TAGS" size="30" value="<?=$arResult["PostToShow"]["CategoryText"]?>">
                <?}?>
        </div>
        <div class="blog-clear-float"></div>
        <?//if($arResult["POST_PROPERTIES"]["SHOW"] == "Y"): //ext additional condition?>
        <?if($arResult["POST_PROPERTIES"]["UF_SHOW_BLOCK"]):?>
            <div class="blog-post-params">
                <div class="blog-post-field blog-post-field-user-prop blog-edit-field">
                    <?foreach ($arResult["POST_PROPERTIES"]["DATA"] as $FIELD_NAME => $arPostField):
                        if($arResult["POST_PROPERTIES"]["DATA"][$FIELD_NAME]["UF_SHOW"]===false) continue;
                    ?>
                        <div class="field-title-idea-<?=$FIELD_NAME?>"><?=$arPostField["EDIT_FORM_LABEL"]?></div>
                        <?$APPLICATION->IncludeComponent(
                                "bitrix:system.field.edit",
                                $arPostField["UF_TEMPLATE"],
                                array(
                                    "arUserField" => $arPostField, 
                                    "POST_BIND_USER" => $arParams["POST_BIND_USER"], 
                                    "IBLOCK_CATOGORIES" => CIdeaManagment::getInstance()->Idea()->GetCategoryListID()
                                ), 
                                $component->__parent, 
                                array("HIDE_ICONS"=>"Y"));
                        ?>
                    <?endforeach;?>
                    <br clear="both"/>
                </div>
                <div class="blog-clear-float"></div>
            </div>
        <?endif;?>
        <?//endif;?>
            <input type="hidden" name="save" value="Y">
            <input type="hidden" name="AJAX" value="Y">
            <input type="hidden" name="ACTION" value="GET_ADD_FORM">
            <div class="idea-add-comment">
                <a class="idea-add-button" onclick="BX('REPLIER-form-light').onsubmit(); BX.ajax.submit(BX('REPLIER-form-light'), function(data){BX('idea-ajax-notification').innerHTML = data;})">
                    <span class="l"></span><span class="t"><?=GetMessage("IDEA_ADD_IDEA_BUTTON_TITLE")?></span><span class="r"></span>
                </a>
                <a class="idea-back-to-list-button" onclick="JSIdeaDialog.Dialog.SetContent(false, 'idea-list-container');"><?=GetMessage("IDEA_BACKTOLIST_IDEA_BUTTON_TITLE")?></a>
            </div>
        </div>
        </form>
        <?
}
?>
</div>