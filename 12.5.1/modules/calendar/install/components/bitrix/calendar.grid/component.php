<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("calendar"))
	return ShowError("EC_CALENDAR_MODULE_NOT_INSTALLED");

CModule::IncludeModule("socialnetwork");

$APPLICATION->ResetException();
$Params = array(
	'type' => $arParams["CALENDAR_TYPE"],
	'ownerId' => $arParams["OWNER_ID"],
	'pageUrl' => htmlspecialcharsback(POST_FORM_ACTION_URI),
	'allowSuperpose' => $arParams["ALLOW_SUPERPOSE"] == 'Y',
	'allowResMeeting' => $arParams["ALLOW_RES_MEETING"] != 'N',
	'allowVideoMeeting' => $arParams["ALLOW_RES_MEETING"] != 'N',
	'SectionControlsDOMId' => 'sidebar'
);

if (isset($arParams["SIDEBAR_DOM_ID"]))
	$Params['SectionControlsDOMId'] = $arParams["SIDEBAR_DOM_ID"];

// Create new instance of Event Calendar object
$EC = new CCalendar;
$EC->Init($Params); // Init with $Params array

if (isset($_REQUEST['action']))
	$EC->Request($_REQUEST['action']); // Die inside
else
	$EC->Show();

if($ex = $APPLICATION->GetException())
	return ShowError($ex->GetString());

// Set title and navigation
$arParams["SET_TITLE"] = $arParams["SET_TITLE"] == "Y" ? "Y" : "N";
$arParams["SET_NAV_CHAIN"] = $arParams["SET_NAV_CHAIN"] == "Y" ? "Y" : "N"; //Turn OFF by default

if ($arParams["STR_TITLE"])
{
	$arParams["STR_TITLE"] = trim($arParams["STR_TITLE"]);
}
else
{
	if (!$arParams['OWNER_ID'] && $arParams['CALENDAR_TYPE'] == "group")
		return ShowError(GetMessage('EC_GROUP_ID_NOT_FOUND'));
	if (!$arParams['OWNER_ID'] && $arParams['CALENDAR_TYPE'] == "user")
		return ShowError(GetMessage('EC_USER_ID_NOT_FOUND'));

	if ($arParams['CALENDAR_TYPE'] == "group" || $arParams['CALENDAR_TYPE'] == "user")
	{
		$feature = "calendar";
		$arEntityActiveFeatures = CSocNetFeatures::GetActiveFeaturesNames((($arParams['CALENDAR_TYPE'] == "group") ? SONET_ENTITY_GROUP : SONET_ENTITY_USER), $arParams['OWNER_ID']);
		$strFeatureTitle = ((array_key_exists($feature, $arEntityActiveFeatures) && StrLen($arEntityActiveFeatures[$feature]) > 0) ? $arEntityActiveFeatures[$feature] : GetMessage("EC_SONET_CALENDAR"));
		$arParams["STR_TITLE"] = $strFeatureTitle;
	}
	else
		$arParams["STR_TITLE"] = GetMessage("EC_SONET_CALENDAR");
}

$bOwner = $arParams["CALENDAR_TYPE"] == 'user' || $arParams["CALENDAR_TYPE"] == 'group';
if ($arParams["SET_TITLE"] == "Y" || ($bOwner && $arParams["SET_NAV_CHAIN"] == "Y"))
{
	$ownerName = '';
	if ($bOwner)
		$ownerName = CCalendar::GetOwnerName($arParams["CALENDAR_TYPE"], $arParams["OWNER_ID"]);

	if($arParams["SET_TITLE"] == "Y")
	{
		$title = ($ownerName ? $ownerName.': ' : '').(empty($arParams["STR_TITLE"]) ? GetMessage("WD_TITLE") : $arParams["STR_TITLE"]);
		$APPLICATION->SetTitle($title);
	}

	if ($bOwner && $arParams["SET_NAV_CHAIN"] == "Y")
	{
		$set = CCalendar::GetSettings();
		if($arParams["CALENDAR_TYPE"] == 'group')
		{
			$APPLICATION->AddChainItem($ownerName, CComponentEngine::MakePathFromTemplate($set['path_to_group'], array("group_id" => $arParams["OWNER_ID"])));
			$APPLICATION->AddChainItem($arParams["STR_TITLE"], CComponentEngine::MakePathFromTemplate($set['path_to_group_calendar'], array("group_id" => $arParams["OWNER_ID"], "path" => "")));
		}
		else
		{
			$APPLICATION->AddChainItem(htmlspecialcharsEx($ownerName), CComponentEngine::MakePathFromTemplate($set['path_to_user'], array("user_id" => $arParams["OWNER_ID"])));
			$APPLICATION->AddChainItem($arParams["STR_TITLE"], CComponentEngine::MakePathFromTemplate($set['path_to_user_calendar'], array("user_id" => $arParams["OWNER_ID"], "path" => "")));
		}
	}
}
?>