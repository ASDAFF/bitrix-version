<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(defined("IDEA_POPUP_DIALOG_INITED"))
    return;
define("IDEA_POPUP_DIALOG_INITED", true);

if (!CModule::IncludeModule("blog"))
{
	ShowError(GetMessage("BLOG_MODULE_NOT_INSTALL"));
	return;
}

if (!CModule::IncludeModule("idea"))
{
	ShowError(GetMessage("IDEA_MODULE_NOT_INSTALL"));
	return;
}

if (!CModule::IncludeModule("iblock"))
{
	ShowError(GetMessage("IBLOCK_MODULE_NOT_INSTALL"));
	return;
}

$arParams["IBLOCK_CATOGORIES"] = intval($arParams["IBLOCK_CATOGORIES"]);
$arParams["CATOGORIES_CNT"] = intval($arParams["CATOGORIES_CNT"]);
if($arParams["CATOGORIES_CNT"] == 0)
    $arParams["CATOGORIES_CNT"] = 4;

$arParams["LIST_MESSAGE_COUNT"] = intval($arParams["LIST_MESSAGE_COUNT"]);
if($arParams["LIST_MESSAGE_COUNT"] == 0)
    $arParams["LIST_MESSAGE_COUNT"] = 8;

if($arParams["SHOW_RATING"] != "N")
$arParams["SHOW_RATING"] = "Y";
if($arParams["RATING_TEMPLATE"] != 'standart')
    $arParams["RATING_TEMPLATE"] = 'like';

$arParams["BUTTON_COLOR"] = htmlspecialchars($arParams["BUTTON_COLOR"]);
$arParams["AUTH_TEMPLATE"] = trim($arParams["AUTH_TEMPLATE"]);

//Set category
CIdeaManagment::getInstance()->Idea()->SetCategoryListID($arParams["IBLOCK_CATOGORIES"]);
//Notifications
if($arParams["DISABLE_SONET_LOG"] == "Y" || !IsModuleInstalled('socialnetwork'))
    CIdeaManagment::getInstance()->Notification()->GetSonetNotify()->Disable();
if($arParams["DISABLE_EMAIL"] == "Y")
    
    CIdeaManagment::getInstance()->Notification()->GetEmailNotify()->Disable();
CJSCore::Init(array('ajax', 'translit'));

if($_REQUEST["AJAX"] == "Y")
{
    switch ($_REQUEST["ACTION"]) {
        case 'GET_LIST':
            $APPLICATION->RestartBuffer();
            $this->IncludeComponentTemplate('list');
            die();
        break;
        case 'GET_ADD_FORM':
            $APPLICATION->RestartBuffer();
            $this->IncludeComponentTemplate('add');
            die();
        break;
    }
}
else
    $this->IncludeComponentTemplate();
?>