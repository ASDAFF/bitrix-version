<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("forum"))
	return;
$arForum = array();
$db_res = CForumNew::GetList(array(), array());
$iForumDefault = 0;
$selectedForum = null;
$selectedForumProps = null;

if (is_set($arCurrentValues, "FORUM_ID"))
	$selectedForum = intval($arCurrentValues['FORUM_ID']);

if ($db_res && ($res = $db_res->GetNext()))
{
	do 
	{
		$iForumDefault = intVal($res["ID"]);
		$arForum[intVal($res["ID"])] = $res["NAME"];
		if ($selectedForum !== null && $selectedForum === intval($res['ID']))
			$selectedForumProps = $res;
	}while ($res = $db_res->GetNext());
}

$uniqueID = (!is_set($arCurrentValues, "UNIQUE_ID") || $arCurrentValues["UNIQUE_ID"] === "" ? "F_COMMENTS_".strtoupper(GetRandomCode(4)) : $arCurrentValues["UNIQUE_ID"]);

$arComponentParameters = Array(
	"GROUPS" => array(
		"EDITOR_SETTINGS" => array(
			"NAME" => GetMessage("F_EDITOR_SETTINGS"),
		),
		"RSS_SETTINGS" => array(
			"NAME" => GetMessage("F_RSS"),
		),
	),

	"PARAMETERS" => Array(
		"FORUM_ID" => Array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("F_FORUM_ID"),
			"TYPE" => "LIST",
			"DEFAULT" => $iForumDefault,
			"REFRESH" => "Y",
			"VALUES" => $arForum),
		"URL_TEMPLATES_READ" => Array(
			"PARENT" => "URL_TEMPLATES",
			"NAME" => GetMessage("F_READ_TEMPLATE"),
			"TYPE" => "STRING",
			"DEFAULT" => ""),
		"URL_TEMPLATES_PROFILE_VIEW" => Array(
			"PARENT" => "URL_TEMPLATES",
			"NAME" => GetMessage("F_PROFILE_VIEW_TEMPLATE"),
			"TYPE" => "STRING",
			"DEFAULT" => ""),
		"MESSAGES_PER_PAGE" => Array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("F_MESSAGES_PER_PAGE"),
			"TYPE" => "STRING",
			"DEFAULT" => intVal(COption::GetOptionString("forum", "MESSAGES_PER_PAGE", "10"))),
		"PAGE_NAVIGATION_TEMPLATE" => Array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("F_PAGE_NAVIGATION_TEMPLATE"),
			"TYPE" => "STRING",
			"DEFAULT" => ""),
		"POST_FIRST_MESSAGE" => Array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("F_POST_FIRST_MESSAGE"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y"),
		"DATE_TIME_FORMAT" => CComponentUtil::GetDateTimeFormatField(GetMessage("F_DATE_TIME_FORMAT"), "ADDITIONAL_SETTINGS"),
		"NAME_TEMPLATE" => array(
			"TYPE" => "LIST",
			"NAME" => GetMessage("F_NAME_TEMPLATE"),
			"VALUES" => CComponentUtil::GetDefaultNameTemplates(),
			"MULTIPLE" => "N",
			"ADDITIONAL_VALUES" => "Y",
			"DEFAULT" => "",
			"PARENT" => "ADDITIONAL_SETTINGS",
		),
		"PATH_TO_SMILE" => Array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("F_PATH_TO_SMILE"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "/bitrix/images/forum/smile/"),
		"EDITOR_CODE_DEFAULT" => Array(
			"NAME" => GetMessage("F_EDITOR_CODE_DEFAULT"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N",
			"PARENT" => "ADDITIONAL_SETTINGS",),
		"SHOW_MODERATION" => Array(
			"NAME" => GetMessage("F_SHOW_MODERATION"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
			"PARENT" => "ADDITIONAL_SETTINGS",),
		"SHOW_AVATAR" => Array(
			"NAME" => GetMessage("F_SHOW_AVATAR"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
			"PARENT" => "ADDITIONAL_SETTINGS",),
		"SHOW_SUBSCRIBE" => Array(
			"NAME" => GetMessage("F_SHOW_SUBSCRIBE"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N",
			"PARENT" => "ADDITIONAL_SETTINGS",),
		"SUBSCRIBE_AUTHOR_ELEMENT" => Array(
			"NAME" => GetMessage("F_SUBSCRIBE_AUTHOR_ELEMENT"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N",
			"PARENT" => "ADDITIONAL_SETTINGS",),
		"SHOW_RATING" => Array(
			"NAME" => GetMessage("F_SHOW_RATING"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
			"PARENT" => "ADDITIONAL_SETTINGS",),
		"SHOW_MINIMIZED" => Array(
			"NAME" => GetMessage("F_SHOW_MINIMIZED"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N",
			"PARENT" => "ADDITIONAL_SETTINGS",),
		"USE_CAPTCHA" => Array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("F_USE_CAPTCHA"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y"),
		"PREORDER" => Array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("F_PREORDER"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y"),
		/*"USE_RSS" => Array(
			"PARENT" => "RSS_SETTINGS",
			"NAME" => GetMessage("F_RSS_USE"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N",
			"REFRESH" => "Y"),*/
		"CACHE_TIME" => Array(),
	)
);

$arEditorSettings = array("ALLOW_HTML", "ALLOW_ANCHOR", "ALLOW_BIU", 
	"ALLOW_IMG", "ALLOW_VIDEO", "ALLOW_LIST", "ALLOW_QUOTE", "ALLOW_CODE", 
	"ALLOW_TABLE", "ALLOW_FONT", "ALLOW_SMILES", "ALLOW_NL2BR");
foreach ($arEditorSettings as $settingName)
{
	$hidden = "N";
	if ($selectedForumProps !== null)
		$hidden = ($selectedForumProps[$settingName] === "N");
	$arComponentParameters['PARAMETERS'][$settingName] = array(
			"PARENT" => "EDITOR_SETTINGS",
			"NAME" => GetMessage($settingName."_TITLE"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
			"HIDDEN" => $hidden
		);
}

/*
if($arCurrentValues["USE_RSS"]=="Y")
{
	$arComponentParameters["PARAMETERS"]["RSS_TYPE_RANGE"] = array(
		"PARENT" => "RSS_SETTINGS",
		"NAME" => GetMessage("F_RSS_TYPE"),
		"TYPE" => "LIST",
		"VALUES" => array(
			"RSS1" => "RSS 0.92",
			"RSS2" => "RSS 2.0",
			"ATOM" => "Atom 0.3"),
		"MULTIPLE" => "Y",
		"DEFAULT" => array("RSS1", "RSS2", "ATOM"), 
		"HIDDEN" => "N");
	$arComponentParameters["PARAMETERS"]["RSS_CACHE"] = array(
		"PARENT" => "CACHE_SETTINGS",
		"NAME" => GetMessage("F_RSS_CACHE"),
		"TYPE" => "STRING",
		"DEFAULT"=> "1800", 
		"HIDDEN" => "N");
	$arComponentParameters["PARAMETERS"]["RSS_COUNT"] = array(
		"PARENT" => "RSS_SETTINGS",
		"NAME" => GetMessage("F_RSS_COUNT"),
		"TYPE" => "STRING",
		"DEFAULT"=>'30');
	$arComponentParameters["PARAMETERS"]["RSS_TN_TITLE"] = array(
		"PARENT" => "RSS_SETTINGS",
		"NAME" => GetMessage("RSS_TITLE"),
		"TYPE" => "STRING",
		"DEFAULT"=> "", 
		"HIDDEN" => "N");
	$arComponentParameters["PARAMETERS"]["RSS_TN_DESCRIPTION"] = array(
		"PARENT" => "RSS_SETTINGS",
		"NAME" => GetMessage("RSS_DESCRIPTION"),
		"TYPE" => "STRING",
		"COLS" => "25",
		"ROWS" => "10",
		"DEFAULT"=> "", 
		"HIDDEN" => "N");
}*/
?>
