<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<div id="idea-posts-content">
<?
if(!empty($arResult["OK_MESSAGE"]))
{
	?>
	<div class="blog-notes blog-note-box">
		<div class="blog-note-text">
			<ul>
				<?
				foreach($arResult["OK_MESSAGE"] as $v)
				{
					?>
					<li><?=$v?></li>
					<?
				}
				?>
			</ul>
		</div>
	</div>
	<?
}
if(!empty($arResult["MESSAGE"]))
{
	?>
	<div class="blog-textinfo blog-note-box">
		<div class="blog-textinfo-text">
			<ul>
				<?
				foreach($arResult["MESSAGE"] as $v)
				{
					?>
					<li><?=$v?></li>
					<?
				}
				?>
			</ul>
		</div>
	</div>
	<?
}
if(!empty($arResult["ERROR_MESSAGE"]))
{
	?>
	<div class="blog-errors blog-note-box blog-note-error">
		<div class="blog-error-text">
			<ul>
				<?
				foreach($arResult["ERROR_MESSAGE"] as $v)
				{
					?>
					<li><?=$v?></li>
					<?
				}
				?>
			</ul>
		</div>
	</div>
	<?
}

if(count($arResult["POST"])>0)
{
        $arStatusList = CIdeaManagment::getInstance()->Idea()->GetStatusList();
        ?>
	<?foreach($arResult["POST"] as $CurPost)
	{
		?>
                <?if($arParams["SHOW_RATING"] == "Y"):?>
                    <div class="idea-rating-block">
                        <span class="idea-rating-block-left"><span class="idea-rating-block-right"><span class="idea-rating-block-content idea-rating-block-content-ext-<?=$arParams['RATING_TEMPLATE']?>"><span class="idea-rating-block-content-description"><?=GetMessage("IDEA_RATING_TITLE");?>:</span>
                                    <?$APPLICATION->IncludeComponent(
                                        "bitrix:rating.vote", $arParams['RATING_TEMPLATE'],
                                        Array(
                                            "VOTE_AVAILABLE" => $CurPost["DISABLE_VOTE"]?"N":"Y",
                                            "ENTITY_TYPE_ID" => "BLOG_POST",
                                            "ENTITY_ID" => $CurPost["ID"],
                                            "OWNER_ID" => $CurPost["arUser"]["ID"],
                                            "USER_VOTE" => $arResult["RATING"][$CurPost["ID"]]["USER_VOTE"],
                                            "USER_HAS_VOTED" => $arResult["RATING"][$CurPost["ID"]]["USER_HAS_VOTED"],
                                            "TOTAL_VOTES" => $arResult["RATING"][$CurPost["ID"]]["TOTAL_VOTES"],
                                            "TOTAL_POSITIVE_VOTES" => $arResult["RATING"][$CurPost["ID"]]["TOTAL_POSITIVE_VOTES"],
                                            "TOTAL_NEGATIVE_VOTES" => $arResult["RATING"][$CurPost["ID"]]["TOTAL_NEGATIVE_VOTES"],
                                            "TOTAL_VALUE" => $arResult["RATING"][$CurPost["ID"]]["TOTAL_VALUE"],
                                            "PATH_TO_USER_PROFILE" => $arParams["AR_RESULT"]["PATH_TO_USER"],
                                        ),
                                        false,
                                        array("HIDE_ICONS" => "Y")
                                    );?>
                                </span>
                            </span>
                        </span>
                    </div>
                <?endif;?>
                        <div class="blog-qtl<?if(in_array($CurPost["PUBLISH_STATUS"], array(BLOG_PUBLISH_STATUS_READY, BLOG_PUBLISH_STATUS_DRAFT))):?> blog-post-hidden<?endif;?>">
                            <div class="blog-qtr">
                                <div class="blog-idea-body">
                                    <div class="idea-owner">
                                        <div class="bx-idea-condition-description status-color-<?=ToLower($arStatusList[$CurPost["POST_PROPERTIES"]["DATA"]["UF_STATUS"]["VALUE"]]["XML_ID"]);?>">
                                            <div <?if($arResult["IDEA_MODERATOR"]):?>class="status-action idea-action-cursor" onclick="JSPublicIdea.ShowStatusDialog(this, '<?=$CurPost["ID"]?>')" id="status-<?=$CurPost["ID"]?>"<?endif;?>><?=GetMessage("IDEA_STATUS_".ToUpper($arStatusList[$CurPost["POST_PROPERTIES"]["DATA"]["UF_STATUS"]["VALUE"]]["XML_ID"]))?></div>
                                        </div>
                                            <?=GetMessage("IDEA_INTRODUCED_TITLE")?> <img class="idea-user-avatar" src="<?=$arResult["AUTHOR_AVATAR"][$CurPost["arUser"]["ID"]]["src"]?>" align="top">
                                            <?if (COption::GetOptionString("blog", "allow_alias", "Y") == "Y" && array_key_exists("ALIAS", $CurPost["BlogUser"]) && strlen($CurPost["BlogUser"]["ALIAS"]) > 0)
                                                    $arTmpUser = array(
                                                            "NAME" => "",
                                                            "LAST_NAME" => "",
                                                            "SECOND_NAME" => "",
                                                            "LOGIN" => "",
                                                            "NAME_LIST_FORMATTED" => $CurPost["BlogUser"]["~ALIAS"],
                                                    );
                                            elseif (strlen($CurPost["urlToAuthor"]) > 0)
                                                    $arTmpUser = array(
                                                            "NAME" => $CurPost["arUser"]["~NAME"],
                                                            "LAST_NAME" => $CurPost["arUser"]["~LAST_NAME"],
                                                            "SECOND_NAME" => $CurPost["arUser"]["~SECOND_NAME"],
                                                            "LOGIN" => $CurPost["arUser"]["~LOGIN"],
                                                            "NAME_LIST_FORMATTED" => "",
                                                    );
                                            ?>					
                                            <?if($arParams["SEO_USER"] == "Y"):?>
                                                    <noindex>
                                            <?endif;?>
                                            <?$APPLICATION->IncludeComponent("bitrix:main.user.link",
                                                    '',
                                                    array(
                                                            "ID" => $CurPost["arUser"]["ID"],
                                                            "HTML_ID" => "blog_blog_".$CurPost["arUser"]["ID"],
                                                            "NAME" => $arTmpUser["NAME"],
                                                            "LAST_NAME" => $arTmpUser["LAST_NAME"],
                                                            "SECOND_NAME" => $arTmpUser["SECOND_NAME"],
                                                            "LOGIN" => $arTmpUser["LOGIN"],
                                                            "NAME_LIST_FORMATTED" => $arTmpUser["NAME_LIST_FORMATTED"],
                                                            "USE_THUMBNAIL_LIST" => "N",
                                                            "PROFILE_URL" => $CurPost["urlToAuthor"],
                                                            //"PROFILE_URL_LIST" => $CurPost["urlToBlog"],
                                                            "PATH_TO_SONET_MESSAGES_CHAT" => $arParams["~PATH_TO_MESSAGES_CHAT"],
                                                            "PATH_TO_VIDEO_CALL" => $arParams["~PATH_TO_VIDEO_CALL"],
                                                            "DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
                                                            "SHOW_YEAR" => $arParams["SHOW_YEAR"],
                                                            "CACHE_TYPE" => $arParams["CACHE_TYPE"],
                                                            "CACHE_TIME" => $arParams["CACHE_TIME"],
                                                            "NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
                                                            "SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
                                                            "PATH_TO_CONPANY_DEPARTMENT" => $arParams["~PATH_TO_CONPANY_DEPARTMENT"],
                                                            "PATH_TO_SONET_USER_PROFILE" => $arParams["PATH_TO_USER"],
                                                            "INLINE" => "Y",
                                                            "SEO_USER" => $arParams["SEO_USER"],
                                                    ),
                                                    false,
                                                    array("HIDE_ICONS" => "Y")
                                            );
                                            ?>
                                            <?if($arParams["SEO_USER"] == "Y"):?>
                                                    </noindex>
                                            <?endif;?>
                                            <?=$CurPost["DATE_PUBLISH_FORMATED"]?>
                                    </div>
                                    <div class="post-title"><h2><a href="<?=$CurPost["urlToPost"]?>" title="<?=$CurPost["TITLE"]?>"><?=$CurPost["TITLE"]?></a></h2></div>
                                    <div class="idea-post-content">
                                            <?=$CurPost["TEXT_FORMATED"]?>
                                            <?
                                            if ($CurPost["CUT"] == "Y")
                                            {
                                                    ?><p><a class="blog-postmore-link" href="<?=$CurPost["urlToPost"]?>"><?=GetMessage("BLOG_BLOG_BLOG_MORE")?></a></p><?
                                            }
                                            ?>
                                            <?if($CurPost["POST_PROPERTIES"]["SHOW"] == "Y" && false):?>
                                                    <p>
                                                    <?foreach ($CurPost["POST_PROPERTIES"]["DATA"] as $FIELD_NAME => $arPostField):?>
                                                    <?if(strlen($arPostField["VALUE"])>0):?>
                                                    <b><?=$arPostField["EDIT_FORM_LABEL"]?>:</b>&nbsp;<?$APPLICATION->IncludeComponent(
                                                                                    "bitrix:system.field.view", 
                                                                                    $arPostField["USER_TYPE"]["USER_TYPE_ID"], 
                                                                                    array("arUserField" => $arPostField), null, array("HIDE_ICONS"=>"Y"));?><br />
                                                    <?endif;?>
                                                    <?endforeach;?>
                                                    </p>
                                            <?endif;?>
                                    </div>

                                    <?if(strLen($CurPost["urlToHide"])>0
                                        || strLen($CurPost["urlToShow"])>0
                                        || strLen($CurPost["urlToEdit"])>0
                                        || strLen($CurPost["urlToDelete"])
                                    ):?>
                                        <div class="idea-post-meta">
                                            <div class="idea-post-meta-util">
                                                <?if(strLen($CurPost["urlToHide"])>0):?>
                                                        <a href="javascript:if(confirm('<?=GetMessage("BLOG_MES_HIDE_POST_CONFIRM")?>')) window.location='<?=$CurPost["urlToHide"]."&".bitrix_sessid_get()?>'"><span class="idea-post-link-caption"><?=GetMessage("BLOG_MES_HIDE")?></span></a>
                                                <?elseif(strLen($CurPost["urlToShow"])>0):?>
                                                        <a href="javascript:if(confirm('<?=GetMessage("IDEA_MES_SHOW_POST_CONFIRM")?>')) window.location='<?=$CurPost["urlToShow"]."&".bitrix_sessid_get()?>'"><span class="idea-post-link-caption"><?=GetMessage("IDEA_MES_SHOW")?></span></a>
                                                <?endif;?>
                                                <?if(strLen($CurPost["urlToEdit"])>0):?>
                                                        <a href="<?=$CurPost["urlToEdit"]?>"><span class="idea-post-link-caption"><?=GetMessage("BLOG_MES_EDIT")?></span></a>
                                                <?endif;?>
                                                <?if(strLen($CurPost["urlToDelete"])>0):?>
                                                        <a href="javascript:if(confirm('<?=GetMessage("BLOG_MES_DELETE_POST_CONFIRM")?>')) window.location='<?=$CurPost["urlToDelete"]."&".bitrix_sessid_get()?>'"><span class="idea-post-link-caption"><?=GetMessage("BLOG_MES_DELETE")?></span></a>
                                                <?endif;?>
                                            </div>
                                            <br clear="both"/>
                                        </div>
                                    <?endif;?>
				</div>
                            </div>
			</div>
                        <div>
<?if($CurPost["IS_DUBLICATE"] !== false):?>
<div class="blog-comments-dublicate">
    <div class="blog-comment-line-dublicate"></div>
    <div class="blog-comment-dublicate">
        <?=GetMessage("IDEA_POST_DUBLICATE", array("#LINK#" => $CurPost["IS_DUBLICATE"]))?>
    </div>
</div>
<?endif;?>
<?$cntOfficial = 0;?>
<?if(!empty($CurPost["OFFICIAL_POST_ID"])):?>
<?$arOfficialComments = array("ID"=>$CurPost["OFFICIAL_POST_ID"]);?>
<?$cntOfficial = $APPLICATION->IncludeComponent(
    "bitrix:idea.comment.list", 
    "official_list", 
    Array(
        "RATING_TEMPLATE" => $arParams['RATING_TEMPLATE'],
        "FILTER" => $arOfficialComments,
        "BLOG_VAR"		=> $arParams["AR_RESULT"]["ALIASES"]["blog"],
        "USER_VAR"		=> $arParams["AR_RESULT"]["ALIASES"]["user_id"],
        "PAGE_VAR"		=> $arParams["AR_RESULT"]["ALIASES"]["page"],
        "POST_VAR"			=> $arParams["AR_RESULT"]["ALIASES"]["post_id"],
        "PATH_TO_BLOG"	=> $arParams["AR_RESULT"]["PATH_TO_BLOG"],
        "PATH_TO_POST"	=> $arParams["AR_RESULT"]["PATH_TO_POST"],
        "PATH_TO_USER"	=> $arParams["AR_RESULT"]["PATH_TO_USER"],
        "PATH_TO_SMILE"	=> $arParams["AR_RESULT"]["PATH_TO_SMILE"],
        "BLOG_URL"		=> $arParams["AR_RESULT"]["VARIABLES"]["blog"],
        "ID"			=> $CurPost["ID"],
        "CACHE_TYPE"	=> $arParams["AR_RESULT"]["CACHE_TYPE"],
        "CACHE_TIME"	=> $arParams["AR_RESULT"]["CACHE_TIME"],
        "COMMENTS_COUNT" => 1000, //unlimited by logic
        "DATE_TIME_FORMAT"	=> $arParams["AR_RESULT"]["DATE_TIME_FORMAT"],
        "USE_ASC_PAGING"	=> $arParams["AR_PARAMS"]["USE_ASC_PAGING"],
        "NOT_USE_COMMENT_TITLE"	=> $arParams["AR_PARAMS"]["NOT_USE_COMMENT_TITLE"],
        "GROUP_ID" 			=> $arParams["AR_PARAMS"]["GROUP_ID"],
        "SEO_USER"			=> $arParams["AR_PARAMS"]["SEO_USER"],
        "NAME_TEMPLATE" => $arParams["AR_PARAMS"]["NAME_TEMPLATE"],
        "SHOW_LOGIN" => $arParams["AR_PARAMS"]["SHOW_LOGIN"],
        "PATH_TO_CONPANY_DEPARTMENT" => $arParams["AR_PARAMS"]["PATH_TO_CONPANY_DEPARTMENT"],
        "PATH_TO_SONET_USER_PROFILE" => $arParams["AR_PARAMS"]["PATH_TO_SONET_USER_PROFILE"],
        "PATH_TO_MESSAGES_CHAT" => $arParams["AR_PARAMS"]["PATH_TO_MESSAGES_CHAT"],
        "PATH_TO_VIDEO_CALL" => $arParams["AR_PARAMS"]["PATH_TO_VIDEO_CALL"],
        "SHOW_RATING" => $arParams["AR_PARAMS"]["SHOW_RATING"],
        "SMILES_COUNT" => $arParams["AR_PARAMS"]["SMILES_COUNT"],
        "IMAGE_MAX_WIDTH" => $arParams["AR_PARAMS"]["IMAGE_MAX_WIDTH"],
        "IMAGE_MAX_HEIGHT" => $arParams["AR_PARAMS"]["IMAGE_MAX_HEIGHT"],
        "EDITOR_RESIZABLE" => $arParams["AR_PARAMS"]["COMMENT_EDITOR_RESIZABLE"],
        "EDITOR_DEFAULT_HEIGHT" => $arParams["AR_PARAMS"]["COMMENT_EDITOR_DEFAULT_HEIGHT"],
        "EDITOR_CODE_DEFAULT" => $arParams["AR_PARAMS"]["COMMENT_EDITOR_CODE_DEFAULT"],
        "ALLOW_VIDEO" => $arParams["AR_PARAMS"]["COMMENT_ALLOW_VIDEO"],
        "ALLOW_POST_CODE" => $arParams["AR_PARAMS"]["ALLOW_POST_CODE"],
        "SHOW_SPAM" => $arParams["AR_PARAMS"]["SHOW_SPAM"],
        "NO_URL_IN_COMMENTS" => $arParams["AR_PARAMS"]["NO_URL_IN_COMMENTS"],
        "NO_URL_IN_COMMENTS_AUTHORITY" => $arParams["AR_PARAMS"]["NO_URL_IN_COMMENTS_AUTHORITY"],
        "POST_BIND_USER" => $arParams["AR_PARAMS"]["POST_BIND_USER"],
    ),
    $component,
    array("HIDE_ICONS" => "Y")
);
$cntOfficial = intval($cntOfficial);
?>
<?endif;?>
<div class="tag-tl">
    <div class="tag-tr">
        <div class="tag-block">
            
                <div class="tag-line">
                    <span class="main-tag-category"><?
                    if($CurPost["IDEA_CATEGORY"]["NAME"]!==false)
                    {
                        if($CurPost["IDEA_CATEGORY"]["LINK"]===false)
                            echo $CurPost["IDEA_CATEGORY"]["NAME"];
                        else
                        {
                            ?><a href="<?=$CurPost["IDEA_CATEGORY"]["LINK"];?>"><?=$CurPost["IDEA_CATEGORY"]["NAME"];?></a><?
                        }
                    }?></span>
                    <?if(!empty($CurPost["CATEGORY"]))
                    {
                            $skipFirst = true;
                            ?><span class="tag-marker"></span><?
                            foreach($CurPost["CATEGORY"] as $v)
                            {
                                if (!$skipFirst) echo ', ';
                                    ?><a href="<?=$v["urlToCategory"]?>" rel="nofollow"><?=$v["NAME"]?></a><?
                                $skipFirst = false;
                            }
                    }
                    ?>
                </div>
            
            <span class="post-comment"><?/*<a href="<?=$CurPost["urlToPost"]?>#comments-add"><?=GetMessage("IDEA_POST_DO_COMMENT")?></a> */?>(<a href="<?=$CurPost["urlToPost"]?>#comments"><?=GetMessage("IDEA_POST_COMMENT_CNT")?>: <?=(IntVal($CurPost["NUM_COMMENTS"]) - $cntOfficial);?></a>)</span>
            <br clear="both" />
        </div>
    </div>
</div>
<div class="tag-tbl"><div class="tag-tbr"><div class="tag-tbb"></div></div></div>
                        </div>
                        
                        <div class="bottom-space"></div>
	<?}
	if(strlen($arResult["NAV_STRING"])>0)
            echo $arResult["NAV_STRING"];
}
elseif(!empty($arResult["BLOG"])){?>
    <div class="blog-post-current">
            <div class="blog-errors blog-note-box blog-textinfo">
                    <div class="blog-error-text"><?=GetMessage("BLOG_BLOG_BLOG_NO_AVAIBLE_MES");?></div>
            </div>
    </div>
<?}?>
</div>