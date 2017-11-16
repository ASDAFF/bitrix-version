<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
__IncludeLang(dirname(__FILE__)."/lang/".LANGUAGE_ID."/component_epilog.php");

if(strlen($arResult["FATAL_ERROR_MESSAGE"]) == 0)
{
    if($arParams["SET_NAV_CHAIN"]=="Y" || $arParams["SET_TITLE"]=="Y")
    {
        foreach($arResult["CATEGORY"] as $v)
        {
            if(array_key_exists("tag", $_REQUEST) && $_REQUEST["tag"]>0 && $_REQUEST["tag"]==$v["ID"])
            {
                if($arParams["SET_NAV_CHAIN"]=="Y")
                    $APPLICATION->AddChainItem(GetMessage("IDEA_TAG_CHAINT_TITLE", array("#TAG#" => $v["NAME"])), $v["URL"]);
                if($arParams["SET_TITLE"]=="Y")
                    $APPLICATION->SetTitle(GetMessage("IDEA_TAG_CHAINT_TITLE", array("#TAG#" => $v["NAME"])));
            }
        }
    }
}
?>
