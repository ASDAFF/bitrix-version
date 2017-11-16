<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
$arFilter = array();
//Prepare filter for life search (if pagination used)
if(strlen($arResult["LIFE_SEARCH_QUERY"]) > 0)
    $arFilter["~TITLE"] = '%'.$arResult["LIFE_SEARCH_QUERY"].'%';
?>

<div class="idea-managment-content">
    <?$APPLICATION->IncludeComponent(
        "bitrix:main.interface.toolbar",
        "",
        array(
            "BUTTONS" => $arResult["ACTIONS"]
        ),
        $component
    );?>
    <?//Side bar tools?>
    <?$this->SetViewTarget("sidebar", 100)?>
        <?$APPLICATION->IncludeComponent(
                "bitrix:idea.category.list",
                "",
                Array(
                    "IBLOCK_CATOGORIES" => $arParams["IBLOCK_CATOGORIES"],
                    "PATH_TO_CATEGORY_1" => $arResult["PATH_TO_CATEGORY_1"],
                    "PATH_TO_CATEGORY_2" => $arResult["PATH_TO_CATEGORY_2"],
                ),
                $component
        );
        ?>
        <?$APPLICATION->IncludeComponent(
                "bitrix:idea.statistic",
                "",
                Array(
                    "BLOG_URL" => $arResult["VARIABLES"]["blog"],
                    "PATH_WITH_STATUS" => $arResult["PATH_TO_STATUS_0"],
                    "PATH_TO_INDEX" => $arResult["PATH_TO_INDEX"],
                ),
                $component
        );
        ?>
        <?$APPLICATION->IncludeComponent(
                "bitrix:idea.tags", 
                "", 
                Array(
                    "BLOG_URL" => $arParams["BLOG_URL"],
                    "PATH_TO_BLOG_CATEGORY" => $arResult["PATH_TO_BLOG_CATEGORY"],
                    "SET_NAV_CHAIN" => $arResult["SET_NAV_CHAIN"],
                    "SET_TITLE" => $arResult["SET_TITLE"],
                    "TAGS_COUNT" => $arParams["TAGS_COUNT"]
                ),
                $component
        );
        ?>
    <?$this->EndViewTarget();?>
    <?//Work Field?>
    <?$this->SetViewTarget("idea_filter", 100)?>
        <?if($arParams["DISABLE_RSS"] != "Y"):?>
            <?$APPLICATION->IncludeComponent(
                "bitrix:blog.rss.link",
                "",
                Array(
                    "RSS1"				=> "N",
                    "RSS2"				=> "Y",
                    "ATOM"				=> "N",
                    "BLOG_VAR"			=> $arResult["ALIASES"]["blog"],
                    "PATH_TO_RSS"		=> $arResult["PATH_TO_RSS"],
                    "BLOG_URL"			=> $arResult["VARIABLES"]["blog"],
                ),
                $component 
            );
            ?>
        <?endif;?>
        <?$APPLICATION->IncludeComponent(
                "bitrix:idea.filter",
                "",
                Array(
                    "PATH_TO_CATEGORY_WITH_STATUS" => $arResult["PATH_TO_STATUS_0"],
                    "PATH_TO_CATEGORY" => $arResult["PATH_TO_INDEX"],
                    "SELECTED_STATUS" => false,
                    "SET_NAV_CHAIN" => $arParams["SET_NAV_CHAIN"],
                ),
                $component
        );
        ?>
    <?$this->EndViewTarget();?>
    <?$this->SetViewTarget("idea_body", 100)?>
        <?$APPLICATION->IncludeComponent(
            "bitrix:idea.list", 
            "", 
            Array(
                "RATING_TEMPLATE" => $arParams['RATING_TEMPLATE'],
                "SORT_BY1" => $_SESSION["IDEA_SORT_ORDER"],
                "IBLOCK_CATOGORIES" => $arParams["IBLOCK_CATOGORIES"],
                "EXT_FILTER" => $arFilter,
                "MESSAGE_COUNT"			=> $arResult["MESSAGE_COUNT"],
                "BLOG_VAR"				=> $arResult["ALIASES"]["blog"],
                "POST_VAR"				=> $arResult["ALIASES"]["post_id"],
                "USER_VAR"				=> $arResult["ALIASES"]["user_id"],
                "PAGE_VAR"				=> $arResult["ALIASES"]["page"],
                "PATH_TO_BLOG"			=> $arResult["PATH_TO_BLOG"],
                "PATH_TO_BLOG_CATEGORY"	=> $arResult["PATH_TO_BLOG_CATEGORY"],
                "PATH_TO_POST"			=> $arResult["PATH_TO_POST"],
                "PATH_TO_POST_EDIT"		=> $arResult["PATH_TO_POST_EDIT"],
                "PATH_TO_USER"			=> $arResult["PATH_TO_USER"],
                "PATH_TO_SMILE"			=> $arResult["PATH_TO_SMILE"],
                "BLOG_URL"				=> $arResult["VARIABLES"]["blog"],
                "YEAR"					=> $arResult["VARIABLES"]["year"],
                "MONTH"					=> $arResult["VARIABLES"]["month"],
                "DAY"					=> $arResult["VARIABLES"]["day"],
                "CATEGORY_ID"			=> $arResult["VARIABLES"]["tag"],
                "CACHE_TYPE"			=> $arResult["CACHE_TYPE"],
                "CACHE_TIME"			=> $arResult["CACHE_TIME"],
                "CACHE_TIME_LONG"		=> $arResult["CACHE_TIME_LONG"],
                "SET_NAV_CHAIN"			=> $arResult["SET_NAV_CHAIN"],
                "POST_PROPERTY_LIST"	=> $arParams["POST_PROPERTY_LIST"],
                "DATE_TIME_FORMAT"		=> $arResult["DATE_TIME_FORMAT"],
                "NAV_TEMPLATE"			=> $arParams["NAV_TEMPLATE"],
                "GROUP_ID" 				=> $arParams["GROUP_ID"],
                "SEO_USER"				=> $arParams["SEO_USER"],
                "NAME_TEMPLATE" 		=> $arParams["NAME_TEMPLATE"],
                "SHOW_LOGIN" 			=> $arParams["SHOW_LOGIN"],
                "PATH_TO_CONPANY_DEPARTMENT" 	=> $arParams["PATH_TO_CONPANY_DEPARTMENT"],
                "PATH_TO_SONET_USER_PROFILE" 	=> $arParams["PATH_TO_SONET_USER_PROFILE"],
                "PATH_TO_MESSAGES_CHAT"	=> $arParams["PATH_TO_MESSAGES_CHAT"],
                "PATH_TO_VIDEO_CALL" 	=> $arParams["PATH_TO_VIDEO_CALL"],			
                "SHOW_RATING" => $arParams["SHOW_RATING"],
                "IMAGE_MAX_WIDTH" => $arParams["IMAGE_MAX_WIDTH"],
                "IMAGE_MAX_HEIGHT" => $arParams["IMAGE_MAX_HEIGHT"],
                "ALLOW_POST_CODE" => $arParams["ALLOW_POST_CODE"],
                "AR_RESULT" => $arResult,
                "AR_PARAMS" => $arParams,
                "POST_BIND_USER" => $arParams["POST_BIND_USER"],
            ),
            $component 
	);
	?>
    <?$this->EndViewTarget();?>
    <?if($arResult["IS_CORPORTAL"] != "Y"):?>
        <div class="idea-managment-content-left">
            <?$APPLICATION->ShowViewContent("sidebar")?>
        </div>
    <?endif;?>
    <div class="idea-managment-content-right">
        <?$APPLICATION->ShowViewContent("idea_filter")?>
        <?$APPLICATION->ShowViewContent("idea_body")?>
    </div>
</div>
<?
//Set Title
if($arResult["SET_TITLE"] == "Y")
    $APPLICATION->SetTitle(GetMessage("IDEA_INDEX_PAGE_TITLE"));
?>