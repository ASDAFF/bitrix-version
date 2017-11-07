<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!CModule::IncludeModule("forum")):
	ShowError(GetMessage("F_NO_MODULE"));
	return 0;
elseif (intVal($arParams["FORUM_ID"]) <= 0):
	ShowError(GetMessage("F_ERR_FID_EMPTY"));
	return 0;
elseif (empty($arParams["ENTITY_TYPE"])):
	ShowError(GetMessage("F_ERR_ENT_EMPTY"));
	return 0;
elseif (strlen(trim($arParams["ENTITY_TYPE"])) !== 2 ):
	ShowError(GetMessage("F_ERR_ENT_INVALID"));
	return 0;
elseif (empty($arParams["ENTITY_XML_ID"]) || (intval($arParams['ENTITY_ID']) <= 0 && $arParams['ENTITY_ID'] !== 0)):
	ShowError(GetMessage("F_ERR_EID_EMPTY"));
	return 0;
endif;
/********************************************************************
				Input params
********************************************************************/

/***************** BASE ********************************************/

$arParams["FORUM_ID"] = intVal($arParams["FORUM_ID"]);

/***************** URL *********************************************/

$URL_NAME_DEFAULT = array(
		"profile_view" => "PAGE_NAME=profile_view&UID=#UID#",
	);
foreach ($URL_NAME_DEFAULT as $URL => $URL_VALUE)
{
	if (empty($arParams["URL_TEMPLATES_".strToUpper($URL)]))
		continue;
	$arParams["~URL_TEMPLATES_".strToUpper($URL)] = $arParams["URL_TEMPLATES_".strToUpper($URL)];
	$arParams["URL_TEMPLATES_".strToUpper($URL)] = htmlspecialcharsbx($arParams["~URL_TEMPLATES_".strToUpper($URL)]);
}

/***************** ADDITIONAL **************************************/

$arParams["POST_FIRST_MESSAGE"] = ($arParams["POST_FIRST_MESSAGE"] == "Y" ? "Y" : "N");
$arParams["ENABLE_HIDDEN"] = ($arParams["ENABLE_HIDDEN"] == "Y" ? "Y" : "N");
$arParams["EDITOR_CODE_DEFAULT"] = ($arParams["EDITOR_CODE_DEFAULT"] == "Y" ? "Y" : "N");
$arParams["SHOW_MINIMIZED"] = ($arParams["SHOW_MINIMIZED"] == "Y" ? "Y" : "N");
$arParams["SUBSCRIBE_AUTHOR_ELEMENT"] = ($arParams["SUBSCRIBE_AUTHOR_ELEMENT"] == "Y" ? "Y" : "N");
$arParams["IMAGE_SIZE"] = (intVal($arParams["IMAGE_SIZE"]) > 0 ? $arParams["IMAGE_SIZE"] : 300);
$arParams["MESSAGES_PER_PAGE"] = intVal($arParams["MESSAGES_PER_PAGE"] > 0 ? $arParams["MESSAGES_PER_PAGE"] : COption::GetOptionString("forum", "MESSAGES_PER_PAGE", "10"));
$arParams["DATE_TIME_FORMAT"] = trim(empty($arParams["DATE_TIME_FORMAT"]) ? $DB->DateFormatToPHP(CSite::GetDateFormat("FULL")):$arParams["DATE_TIME_FORMAT"]);
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);
$arParams["USE_CAPTCHA"] = ($arParams["USE_CAPTCHA"] == "Y" ? "Y" : "N");
$arParams["PREORDER"] = ($arParams["PREORDER"] == "Y" ? "Y" : "N");
$arParams["PERMISSION"] = (isset($arParams['PERMISSION']) ? $arParams['PERMISSION'] : null);
$arParams["SHOW_RATING"] = ($arParams["SHOW_RATING"] == "Y" ? "Y" : "N");
$arParams["SHOW_AVATAR"] = ($arParams["SHOW_AVATAR"] == "N" ? "N" : "Y");
$arParams["SHOW_MODERATION"] = ($arParams["SHOW_MODERATION"] == "N" ? "N" : "Y");
$arParams["SHOW_SUBSCRIBE"] = ($arParams["SHOW_SUBSCRIBE"] == "N" ? "N" : "Y");
$arParams['SHOW_WYSIWYG_EDITOR'] = ($arParams["SHOW_WYSIWYG_EDITOR"] == "N" ? "N" : "Y");
$arParams["PAGE_NAVIGATION_TEMPLATE"] = trim($arParams["PAGE_NAVIGATION_TEMPLATE"]);
$arParams["PAGE_NAVIGATION_TEMPLATE"] = (!empty($arParams["PAGE_NAVIGATION_TEMPLATE"]) ? $arParams["PAGE_NAVIGATION_TEMPLATE"] : "modern");
if ($arParams['AUTOSAVE'] !== false)
	$arParams["AUTOSAVE"] = CForumAutosave::GetInstance();

$arEditParams = array("ALLOW_HTML", "ALLOW_ANCHOR", "ALLOW_BIU", "ALLOW_IMG", 
	"ALLOW_VIDEO", "ALLOW_LIST", "ALLOW_QUOTE", "ALLOW_CODE", "ALLOW_FONT", 
	"ALLOW_SMILES", "ALLOW_NL2BR", "ALLOW_TABLE", "ALLOW_UPLOAD");
foreach ($arEditParams as $paramName)
	$arParams[$paramName] = ($arParams[$paramName] === "N" ? "N":"Y");

$arMessages = array(
	"MINIMIZED_EXPAND_TEXT" => GetMessage('F_EXPAND_TEXT'),
	"MINIMIZED_MINIMIZE_TEXT" => GetMessage('F_MINIMIZE_TEXT'),
	"MESSAGE_TITLE" => GetMessage('F_MESSAGE_TEXT')
);
foreach($arMessages as $paramName => $paramValue)
	$arParams[$paramName] = (($arParams[$paramName]) ? $arParams[$paramName] : $paramValue);

/***************** STANDART ****************************************/
if ($arParams["CACHE_TYPE"] == "Y" || ($arParams["CACHE_TYPE"] == "A" && COption::GetOptionString("main", "component_cache_on", "Y") == "Y"))
	$arParams["CACHE_TIME"] = intval($arParams["CACHE_TIME"]);
else
	$arParams["CACHE_TIME"] = 0;
global $CACHE_MANAGER;
/********************************************************************
				/Input params
********************************************************************/

/********************************************************************
				Default values
********************************************************************/
$arError = array();
$arNote = array();
$arResult["ERROR_MESSAGE"] = "";
$arResult["OK_MESSAGE"] = ($_REQUEST["result"] == "reply" ? GetMessage("COMM_COMMENT_OK") : (
	$_REQUEST["result"] == "not_approved" ? GetMessage("COMM_COMMENT_OK_AND_NOT_APPROVED") : ""));
unset($_GET["result"]); unset($GLOBALS["HTTP_GET_VARS"]["result"]);
DeleteParam(array("result"));

$arResult["MESSAGES"] = array();
$arResult["MESSAGE_VIEW"] = array();
$arResult["MESSAGE"] = array();

$arResult["FORUM"] = CForumNew::GetByIDEx($arParams["FORUM_ID"], SITE_ID);
$arResult["ELEMENT"] = array();
$arResult["USER"] = array(
	"PERMISSION" => ($arParams['PERMISSION'] !== null ? $arParams['PERMISSION'] : ForumCurrUserPermissions($arParams["FORUM_ID"])),
	"SHOWED_NAME" => $GLOBALS["FORUM_STATUS_NAME"]["guest"],
	"SUBSCRIBE" => array(),
	"FORUM_SUBSCRIBE" => "N", "TOPIC_SUBSCRIBE" => "N");

// A - NO ACCESS		E - READ			I - ANSWER
// M - NEW TOPIC		Q - MODERATE	U - EDIT			Y - FULL_ACCESS

$userId = $USER->GetID();
$arUserGroups = $USER->GetUserGroupArray();
if ($arParams['PERMISSION'] !== null)
{
	$arResult["USER"]["RIGHTS"] = array(
		"ADD_TOPIC" => ($arParams['PERMISSION'] >= 'M' ? "Y" : "N"), 
		"MODERATE" => ($arParams['PERMISSION'] >= 'Q' ? "Y" : "N"), 
		"EDIT" => ($arParams['PERMISSION'] >= 'U' ? "Y" : "N"), 
		"ADD_MESSAGE" => ($arParams['PERMISSION'] >= 'I' ? "Y" : "N")
	);
}
else
{
	$arResult["USER"]["RIGHTS"] = array(
		"ADD_TOPIC" => CForumTopic::CanUserAddTopic($arParams["FID"], $arUserGroups, $userId, $arResult["FORUM"]) ? "Y" : "N", 
		"MODERATE" => (CForumNew::CanUserModerateForum($arParams["FID"], $arUserGroups, $userId) == true ? "Y" : "N"), 
		"EDIT" => CForumNew::CanUserEditForum($arParams["FID"], $arUserGroups, $userId) ? "Y" : "N", 
		"ADD_MESSAGE" => CForumMessage::CanUserAddMessage($arParams["TID"], $arUserGroups, $userId) ? "Y" : "N"
	);
}

if ($USER->IsAuthorized())
{
	$arResult["USER"]["ID"] = $GLOBALS["USER"]->GetID();
	$tmpName = empty($arParams["NAME_TEMPLATE"]) ? $GLOBALS["USER"]->GetFormattedName(false) : CUser::FormatName($arParams["NAME_TEMPLATE"], array(
			"NAME"			=>	$USER->GetFirstName(),
			"LAST_NAME"		=>	$USER->GetLastName(),
			"SECOND_NAME"	=>	$USER->GetSecondName(),
			"LOGIN"			=>	$USER->GetLogin()
			));
	$arResult["USER"]["SHOWED_NAME"] = trim($_SESSION["FORUM"]["SHOW_NAME"] == "Y" ? $tmpName :	$GLOBALS["USER"]->GetLogin());
	$arResult["USER"]["SHOWED_NAME"] = trim(!empty($arResult["USER"]["SHOWED_NAME"]) ? $arResult["USER"]["SHOWED_NAME"] : $GLOBALS["USER"]->GetLogin());
}

$arResult['DO_NOT_CACHE'] = false;

// PARSER
$parser = new forumTextParser(LANGUAGE_ID, $arParams["PATH_TO_SMILE"]);
$parser->image_params["width"] = $arParams["IMAGE_SIZE"];
$parser->image_params["height"] = $arParams["IMAGE_SIZE"];
$arResult["PARSER"] = $parser;

// Post message format settings from FORUM & component settings
$arEditorSettings = array("ALLOW_HTML", "ALLOW_ANCHOR", "ALLOW_BIU",
	"ALLOW_IMG", "ALLOW_VIDEO", "ALLOW_LIST", "ALLOW_QUOTE", "ALLOW_CODE",
	"ALLOW_TABLE", "ALLOW_FONT", "ALLOW_SMILES", "ALLOW_NL2BR");
foreach ($arEditorSettings as $sName)
	$arEditorAllow[$sName] = ($arResult['FORUM'][$sName] === "Y" && $arParams[$sName] === "Y") ? "Y" : "N";

$arAllow = array(
	"HTML" => $arEditorAllow["ALLOW_HTML"],
	"ANCHOR" => $arEditorAllow["ALLOW_ANCHOR"],
	"BIU" => $arEditorAllow["ALLOW_BIU"],
	"IMG" => $arEditorAllow["ALLOW_IMG"],
	"VIDEO" => $arEditorAllow["ALLOW_VIDEO"],
	"LIST" => $arEditorAllow["ALLOW_LIST"],
	"QUOTE" => $arEditorAllow["ALLOW_QUOTE"],
	"CODE" => $arEditorAllow["ALLOW_CODE"],
	"FONT" => $arEditorAllow["ALLOW_FONT"],
	"SMILES" => $arEditorAllow["ALLOW_SMILES"],
	"NL2BR" => $arEditorAllow["ALLOW_NL2BR"],
	"TABLE" => $arEditorAllow["ALLOW_TABLE"],
	"UPLOAD" => $arResult["FORUM"]["ALLOW_UPLOAD"],
);
// FORUM
CPageOption::SetOptionString("main", "nav_page_in_session", "N");

$arResult['FORUM_TOPIC_ID']=null;
$arFilter = array("FORUM_ID"=>$arParams['FORUM_ID'], "XML_ID"=>$arParams['ENTITY_XML_ID']);
$dbRes = CForumTopic::GetList(null, $arFilter);
if ($dbRes && $arRes = $dbRes->Fetch())
	$arResult['FORUM_TOPIC_ID']=$arRes['ID'];

/********************************************************************
				/Default values
********************************************************************/

if (empty($arResult["FORUM"]))
{
	ShowError(str_replace("#FORUM_ID#", $arParams["FORUM_ID"], GetMessage("F_ERR_FID_IS_NOT_EXIST")));
	return false;
}
elseif ($arResult["USER"]["PERMISSION"] <= "A")
{
	return false;
}

$path = dirname(__FILE__);

if (isset($arParams['UPLOAD_SIMPLE']) && $arParams['UPLOAD_SIMPLE'] === 'Y')
	include_once($path."/files.php");
else
	include_once($path."/files_input.php");
$commentFiles = new CCommentFiles($this);

if ($arParams["SHOW_RATING"] == "Y")
{
	include_once($path."/ratings.php");
	$commentRating = new CCommentRatings($this);
}

$rsEvents = GetModuleEvents('forum', 'OnCommentsInit');
while ($arEvent = $rsEvents->Fetch())
	ExecuteModuleEventEx($arEvent, array(&$this));

/********************************************************************
				Actions
********************************************************************/
ForumSetLastVisit($arParams["FORUM_ID"], $arResult['FORUM_TOPIC_ID'], array("nameTemplate" => $arParams["NAME_TEMPLATE"]));
include($path."/action.php");
$strErrorMessage = "";
foreach ($arError as $res)
	$strErrorMessage .= (empty($res["title"]) ? $res["code"] : $res["title"]);

$arResult["ERROR_MESSAGE"] = $strErrorMessage;
$arResult["OK_MESSAGE"] .= $strOKMessage;

if (strlen($arResult["ERROR_MESSAGE"]) > 0)
	$arParams["SHOW_MINIMIZED"] = "N";
/********************************************************************
				/Actions
********************************************************************/

$arResult["PANELS"] = array(
	"MODERATE" => $arResult["USER"]["RIGHTS"]["MODERATE"], 
	"DELETE" => $arResult["USER"]["RIGHTS"]["EDIT"], 
);
$arResult["SHOW_PANEL"] = in_array("Y", $arResult["PANELS"]) ? "Y" : "N";
$arResult["SHOW_PANEL"] = ($arParams["SHOW_MODERATION"] === "Y" ? $arResult["SHOW_PANEL"] : 'N');

/************** Show post form **********************************/
$arResult["SHOW_POST_FORM"] = (($arResult["USER"]["PERMISSION"] >= "M" || ($arResult["USER"]["PERMISSION"] >= "I" && count($arResult["MESSAGES"]) > 0)) ? "Y" : "N");

if ($arResult["SHOW_POST_FORM"] == "Y")
{
	// Author name
	$arResult["~REVIEW_AUTHOR"] = $arResult["USER"]["SHOWED_NAME"];
	$arResult["~REVIEW_USE_SMILES"] = ($arResult["FORUM"]["ALLOW_SMILES"] == "Y" ? "Y" : "N");

	if (!empty($arError) || !empty($arResult["MESSAGE_VIEW"]))
	{
		if (!empty($_POST["REVIEW_AUTHOR"]))
			$arResult["~REVIEW_AUTHOR"] = $_POST["REVIEW_AUTHOR"];
		$arResult["~REVIEW_EMAIL"] = $_POST["REVIEW_EMAIL"];
		$arResult["~REVIEW_TEXT"] = $_POST["REVIEW_TEXT"];
		$arResult["~REVIEW_USE_SMILES"] = ($_POST["REVIEW_USE_SMILES"] == "Y" ? "Y" : "N");
	}
	$arResult["REVIEW_AUTHOR"] = htmlspecialcharsEx($arResult["~REVIEW_AUTHOR"]);
	$arResult["REVIEW_EMAIL"] = htmlspecialcharsEx($arResult["~REVIEW_EMAIL"]);
	$arResult["REVIEW_TEXT"] = htmlspecialcharsEx($arResult["~REVIEW_TEXT"]);
	$arResult["REVIEW_USE_SMILES"] = $arResult["~REVIEW_USE_SMILES"];

	// Form Info
	$arResult["SHOW_PANEL_ATTACH_IMG"] = (in_array($arResult["FORUM"]["ALLOW_UPLOAD"], array("A", "F", "Y")) ? "Y" : "N");
	$arResult["TRANSLIT"] = (LANGUAGE_ID=="ru" ? "Y" : " N");
	if ($arResult["FORUM"]["ALLOW_SMILES"] == "Y"):
		$arResult["ForumPrintSmilesList"] = ($arResult["FORUM"]["ALLOW_SMILES"] == "Y" ?
			ForumPrintSmilesList(3, LANGUAGE_ID, $arParams["PATH_TO_SMILE"], $arParams["CACHE_TIME"]) : "");
		$arResult["SMILES"] = CForumSmile::GetByType("S", LANGUAGE_ID);
	endif;

	$arResult["CAPTCHA_CODE"] = "";
	if ($arParams["USE_CAPTCHA"] == "Y" && !$GLOBALS["USER"]->IsAuthorized())
	{
		include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/captcha.php");
		$cpt = new CCaptcha();
		$captchaPass = COption::GetOptionString("main", "captcha_password", "");
		if (strLen($captchaPass) <= 0)
		{
			$captchaPass = randString(10);
			COption::SetOptionString("main", "captcha_password", $captchaPass);
		}
		$cpt->SetCodeCrypt($captchaPass);
		$arResult["CAPTCHA_CODE"] = htmlspecialcharsbx($cpt->GetCodeCrypt());
	}
}

/********************************************************************
				Input params II
********************************************************************/
/************** URL ************************************************/
if (empty($arParams["~URL_TEMPLATES_READ"]) && !empty($arResult["FORUM"]["PATH2FORUM_MESSAGE"]))
	$arParams["~URL_TEMPLATES_READ"] = $arResult["FORUM"]["PATH2FORUM_MESSAGE"];
elseif (empty($arParams["~URL_TEMPLATES_READ"]))
	$arParams["~URL_TEMPLATES_READ"] = $APPLICATION->GetCurPage()."?PAGE_NAME=read&FID=#FID#&TID=#TID#&MID=#MID#";
$arParams["~URL_TEMPLATES_READ"] = str_replace(array("#FORUM_ID#", "#TOPIC_ID#", "#MESSAGE_ID#"),
		array("#FID#", "#TID#", "#MID#"), $arParams["~URL_TEMPLATES_READ"]);
$arParams["URL_TEMPLATES_READ"] = htmlspecialcharsEx($arParams["~URL_TEMPLATES_READ"]);
//
// Link to forum
$arResult["read"] = CComponentEngine::MakePathFromTemplate($arParams["URL_TEMPLATES_READ"],
	array("FID" => $arParams["FORUM_ID"], "TID" => $arResult["FORUM_TOPIC_ID"], "MID" => "s",
		"PARAM1" => $arParams['ENTITY_TYPE'], "PARAM2" => $arParams["ENTITY_ID"]));
/************** ADDITIONAL *****************************************/
$arParams["USE_CAPTCHA"] = $arResult["FORUM"]["USE_CAPTCHA"] == "Y" ? "Y" : $arParams["USE_CAPTCHA"];
/********************************************************************
				/Input params
********************************************************************/

/********************************************************************
				Data
********************************************************************/
/************** 4. Get message list ********************************/
$pageNo = 0;
if ($arResult["FORUM_TOPIC_ID"] > 0)
{
	ForumSetReadTopic($arParams["FORUM_ID"], $arResult["FORUM_TOPIC_ID"]);

	$pager_number = $GLOBALS["NavNum"] + 1;

	$MID = intVal($_REQUEST["MID"]);
	unset($_GET["MID"]); unset($GLOBALS["MID"]);
	if (isset($arResult['RESULT']) && intval($arResult['RESULT']) > 0)
		$MID = $arResult['RESULT'];
	if (intVal($MID) > 0)
	{
		$pageNo = CForumMessage::GetMessagePage(
			$MID,
			$arParams["MESSAGES_PER_PAGE"],
			$GLOBALS["USER"]->GetUserGroupArray(),
			$arResult["FORUM_TOPIC_ID"],
			array(
				"ORDER_DIRECTION" => ($arParams["PREORDER"] == "N" ? "DESC" : "ASC"),
				"PERMISSION_EXTERNAL" => $arResult["USER"]["PERMISSION"],
				"FILTER" => array("!PARAM1" => $arParams['ENTITY_TYPE'])
			)
		);
	} 
	else
	{
		$pageNo = $_GET["PAGEN_".$pager_number];
		if (isset($arResult['RESULT']) && intval($arResult['RESULT']) > 0) $pageNo = $arResult['RESULT'];
	}

	if ($pageNo > 200) $pageNo = 0;
}

$ar_cache_id = array(
	$arParams["FORUM_ID"],
	$arParams["ENTITY_XML_ID"],
	$arResult["FORUM_TOPIC_ID"],
	$arResult["USER"]["RIGHTS"],
	$arResult["USER"]["PERMISSION"],
	$arResult["PANELS"],
	$arParams['SHOW_AVATAR'],
	$arParams['SHOW_RATING'],
	$arParams["MESSAGES_PER_PAGE"],
	$arParams["DATE_TIME_FORMAT"],
	$arParams["PREORDER"],
	$pageNo
);

$cache_id = "forum_comment_".serialize($ar_cache_id);

ob_start();

if ($arResult['DO_NOT_CACHE'] || $this->StartResultCache($arParams["CACHE_TIME"], $cache_id))
{
	if ($arResult["FORUM_TOPIC_ID"] > 0)
	{
		$arMessages = array();

		if (empty($arMessages))
		{
			$arOrder = array("ID" => ($arParams["PREORDER"] == "N" ? "DESC" : "ASC"));
			$arFields = array("bDescPageNumbering" => false, "nPageSize" => $arParams["MESSAGES_PER_PAGE"], "bShowAll" => false);

			if ((intVal($MID) > 0) && ($pageNo > 0))
				$arFields["iNumPage"] = intVal($pageNo);

			$arFilter = array("FORUM_ID"=>$arParams["FORUM_ID"], "TOPIC_ID"=>$arResult["FORUM_TOPIC_ID"], "!PARAM1" => $arParams['ENTITY_TYPE']);
			if ($arResult["USER"]["RIGHTS"]["MODERATE"] != "Y") {$arFilter["APPROVED"] = "Y";}
			$db_res = CForumMessage::GetListEx( $arOrder, $arFilter, false, 0, $arFields);
			$db_res->NavStart($arParams["MESSAGES_PER_PAGE"], false, ($arFields["iNumPage"] > 0 ? $arFields["iNumPage"] : false));
			$arResult["NAV_RESULT"] = $db_res;
			if ($db_res)
			{
				$arResult["NAV_STRING"] = $db_res->GetPageNavStringEx($navComponentObject, GetMessage("NAV_OPINIONS"), $arParams["PAGE_NAVIGATION_TEMPLATE"]);
				$arResult["NAV_STYLE"] = $APPLICATION->GetAdditionalCSS();
				$arResult["PAGE_COUNT"] = $db_res->NavPageCount;
				$arResult['PAGE_NUMBER'] = $db_res->NavPageNomer;
				$number = intVal($db_res->NavPageNomer-1)*$arParams["MESSAGES_PER_PAGE"] + 1;
				$GLOBALS['forumComponent'] = &$this;
				while ($res = $db_res->GetNext())
				{
					/************** Message info ***************************************/
					// number in topic
					$res["NUMBER"] = $number++;
					// data
					$res["POST_DATE"] = CForumFormat::DateFormat($arParams["DATE_TIME_FORMAT"], MakeTimeStamp($res["POST_DATE"], CSite::GetDateFormat()));
					$res["EDIT_DATE"] = CForumFormat::DateFormat($arParams["DATE_TIME_FORMAT"], MakeTimeStamp($res["EDIT_DATE"], CSite::GetDateFormat()));
					// text
					$arAllow["SMILES"] = ($res["USE_SMILES"] == "Y" ? $arResult["FORUM"]["ALLOW_SMILES"] : "N");
					$res["~POST_MESSAGE_TEXT"] = (COption::GetOptionString("forum", "FILTER", "Y")=="Y" ? $res["~POST_MESSAGE_FILTER"] : $res["~POST_MESSAGE"]);
					$res["POST_MESSAGE_TEXT"] = $parser->convert($res["~POST_MESSAGE_TEXT"], $arAllow);
					$arAllow["SMILES"] = $arResult["FORUM"]["ALLOW_SMILES"];
					// links 
					if ($arResult["SHOW_PANEL"] == "Y")
					{
						$res["URL"]["REVIEWS"] = $APPLICATION->GetCurPageParam();
						$res["URL"]["MODERATE"] = ForumAddPageParams($res["URL"]["REVIEWS"], 
							array("MID" => $res["ID"], "REVIEW_ACTION" => $res["APPROVED"]=="Y" ? "HIDE" : "SHOW"))."&amp;".bitrix_sessid_get();
						$res["URL"]["DELETE"] = ForumAddPageParams($res["URL"]["REVIEWS"], 
							array("MID" => $res["ID"], "REVIEW_ACTION" => "DEL"))."&amp;".bitrix_sessid_get();
					}
					/************** Message info/***************************************/
					/************** Author info ****************************************/
					$res["AUTHOR_ID"] = intVal($res["AUTHOR_ID"]);
					$res["AUTHOR_URL"] = "";
					if (!empty($arParams["URL_TEMPLATES_PROFILE_VIEW"]))
					{
						$res["AUTHOR_URL"] = CComponentEngine::MakePathFromTemplate($arParams["URL_TEMPLATES_PROFILE_VIEW"], array(
							"UID" => $res["AUTHOR_ID"],
							"USER_ID" => $res["AUTHOR_ID"],
							"ID" => $res["AUTHOR_ID"]
						));
					}
					// avatar
					if ($arParams['SHOW_AVATAR'] == 'Y') 
					{
						if (strLen($res["AVATAR"]) > 0) 
						{
							$res["AVATAR"] = array("ID" => $res["AVATAR"]);
							$res["AVATAR"]["FILE"] = CFile::ResizeImageGet(
								$res["AVATAR"]["ID"],
								array("width" => 30, "height" => 30),
								BX_RESIZE_IMAGE_EXACT,
								false
							);
						} elseif ($res["AUTHOR_ID"] > 0) {
							$rAuthor = CUser::GetByID($res["AUTHOR_ID"]);
							$arAuthor = $rAuthor->Fetch();
							$res["AVATAR"]["FILE"] = CFile::ResizeImageGet(
								$arAuthor["PERSONAL_PHOTO"],
								array("width" => 30, "height" => 30),
								BX_RESIZE_IMAGE_EXACT,
								false
							);
						}
						if (isset($res["AVATAR"]["FILE"]) && $res["AVATAR"]["FILE"] !== false)
							$res["AVATAR"]["HTML"] = CFile::ShowImage($res["AVATAR"]["FILE"]['src'], 30, 30, "border=0 align='right'");
					}
					// For quote JS
					$res["FOR_JS"]["AUTHOR_NAME"] = CUtil::JSEscape($res["AUTHOR_NAME"]);
					$res["FOR_JS"]["POST_MESSAGE_TEXT"] = CUtil::JSEscape(htmlspecialcharsbx($res["POST_MESSAGE_TEXT"]));
					$arMessages[$res["ID"]] = $res;
				}
			}
			$arResult["MESSAGES"] = $arMessages;
			unset($arMessages);

			$rsEvents = GetModuleEvents('forum', 'OnPrepareComments');
			while ($arEvent = $rsEvents->Fetch())
				$result = ExecuteModuleEventEx($arEvent);

			if(defined("BX_COMP_MANAGED_CACHE"))
				CForumCacheManager::SetTag($this->GetCachePath(), "forum_topic_".$arResult['FORUM_TOPIC_ID']);
		}
		else
		{
			$GLOBALS["NavNum"]++;
		}
	}
	$this->IncludeComponentTemplate();
}

$output = ob_get_clean();

$rsEvents = GetModuleEvents('forum', 'OnCommentsDisplayTemplate');
while ($arEvent = $rsEvents->Fetch())
	$result = ExecuteModuleEventEx($arEvent, array(&$output));

echo $output;
?>
