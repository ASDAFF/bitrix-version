<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?
$arSizes = array('width'=>20, 'height'=>20);
$arCategoryList = CIdeaManagment::getInstance()->Idea()->GetCategoryList();
$arStatusList = CIdeaManagment::getInstance()->Idea()->GetStatusList();
$arResult["AUTHOR_AVATAR"] = array();

if(!array_key_exists($arResult["arUser"]["ID"], $arResult["AUTHOR_AVATAR"]))
{
    if($arResult["arUser"]["PERSONAL_PHOTO"]>0)
        $arResult["AUTHOR_AVATAR"][$arResult["arUser"]["ID"]] = CFile::ResizeImageGet(
            $arResult["arUser"]["PERSONAL_PHOTO"],
            $arSizes,
            BX_RESIZE_IMAGE_EXACT
        );
    else 
        $arResult["AUTHOR_AVATAR"][$arResult["arUser"]["ID"]]["src"] = $this->__folder.'/images/default_avatar.png';
}

//Check dublicate
$arResult["IS_DUBLICATE"] = false;
if(array_key_exists("DATA", $arResult["POST_PROPERTIES"]) 
    && array_key_exists(CIdeaManagment::UFOriginalIdField, $arResult["POST_PROPERTIES"]["DATA"])
)
    if(strlen(trim($arResult["POST_PROPERTIES"]["DATA"][CIdeaManagment::UFOriginalIdField]["VALUE"]))>0)
    {
        $DublicateValue = htmlspecialchars($arResult["POST_PROPERTIES"]["DATA"][CIdeaManagment::UFOriginalIdField]["VALUE"], ENT_QUOTES);
        if(strpos($DublicateValue, "://")!==false) //Link
            $arResult["IS_DUBLICATE"] = $DublicateValue;
        else //Id
            $arResult["IS_DUBLICATE"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_POST"], array("post_id" => $DublicateValue));
    }
    
//Disable vote (reasosns: dublicate, completed status)
$arResult["DISABLE_VOTE"] = false;
if($arResult["IS_DUBLICATE"] 
    ||ToLower($arStatusList[$arResult["POST_PROPERTIES"]["DATA"][CIdeaManagment::UFStatusField]["VALUE"]]["XML_ID"])=='completed'
    ||$arResult["Post"]["PUBLISH_STATUS"] != BLOG_PUBLISH_STATUS_PUBLISH
)    
    $arResult["DISABLE_VOTE"] = true;

//Prepare Category Info
$Category = $arCategoryList[$arResult["POST_PROPERTIES"]["DATA"][CIdeaManagment::UFCategroryCodeField]["VALUE"]];
$Category["NAME"] = trim($Category["NAME"]);
$arCategorySequence = CIdeaManagment::getInstance()->Idea()->GetCategorySequence($Category["CODE"]);

$arResult["IDEA_CATEGORY"] = array(
    "NAME" => false,
    "LINK" => false,
);

if(is_string($Category["NAME"]) && strlen($Category["NAME"])>0)
    $arResult["IDEA_CATEGORY"]["NAME"] = $Category["NAME"];

if($arCategorySequence["CATEGORY_2"]!==false)
    $arResult["IDEA_CATEGORY"]["LINK"] = str_replace(array("#category_1#", "#category_2#"), array($arCategorySequence["CATEGORY_1"], $arCategorySequence["CATEGORY_2"]),$arParams["EXT"][0]["PATH_TO_CATEGORY_2"]);
elseif($arCategorySequence["CATEGORY_1"]!==false)
    $arResult["IDEA_CATEGORY"]["LINK"] = str_replace(array("#category_1#"), array($arCategorySequence["CATEGORY_1"]),$arParams["EXT"][0]["PATH_TO_CATEGORY_1"]);
?>