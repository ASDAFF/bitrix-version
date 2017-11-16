<?
$langs = CLanguage::GetList(($b=""), ($o=""));
while ($lang = $langs->Fetch())
{
	$lid = $lang["LID"];
	IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/im/install/events/set_events.php", $lid);

	$et = new CEventType;
	$et->Add(array(
		"LID" => $lid,
		"EVENT_NAME" => "IM_NEW_NOTIFY",
		"NAME" => GetMessage("IM_NEW_NOTIFY_NAME"),
		"DESCRIPTION" => GetMessage("IM_NEW_NOTIFY_DESC"),
	));

	$et = new CEventType;
	$et->Add(array(
		"LID" => $lid,
		"EVENT_NAME" => "IM_NEW_NOTIFY_GROUP",
		"NAME" => GetMessage("IM_NEW_NOTIFY_GROUP_NAME"),
		"DESCRIPTION" => GetMessage("IM_NEW_NOTIFY_GROUP_DESC"),
	));

	$et = new CEventType;
	$et->Add(array(
		"LID" => $lid,
		"EVENT_NAME" => "IM_NEW_MESSAGE",
		"NAME" => GetMessage("IM_NEW_MESSAGE_NAME"),
		"DESCRIPTION" => GetMessage("IM_NEW_MESSAGE_DESC"),
	));

	$et = new CEventType;
	$et->Add(array(
		"LID" => $lid,
		"EVENT_NAME" => "IM_NEW_MESSAGE_GROUP",
		"NAME" => GetMessage("IM_NEW_MESSAGE_GROUP_NAME"),
		"DESCRIPTION" => GetMessage("IM_NEW_MESSAGE_GROUP_DESC"),
	));

	
	$arSites = array();
	$sites = CSite::GetList(($b=""), ($o=""), Array("LANGUAGE_ID"=>$lid));
	while ($site = $sites->Fetch())
		$arSites[] = $site["LID"];

	if(count($arSites) > 0)
	{
		$emess = new CEventMessage;
		$emess->Add(array(
			"ACTIVE" => "Y",
			"EVENT_NAME" => "IM_NEW_NOTIFY",
			"LID" => $arSites,
			"EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
			"EMAIL_TO" => "#EMAIL_TO#",
			"SUBJECT" => GetMessage("IM_NEW_NOTIFY_SUBJECT"),
			"MESSAGE" => GetMessage("IM_NEW_NOTIFY_MESSAGE"),
			"BODY_TYPE" => "text",
		));

		$emess = new CEventMessage;
		$emess->Add(array(
			"ACTIVE" => "Y",
			"EVENT_NAME" => "IM_NEW_NOTIFY_GROUP",
			"LID" => $arSites,
			"EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
			"EMAIL_TO" => "#EMAIL_TO#",
			"SUBJECT" => GetMessage("IM_NEW_NOTIFY_GROUP_SUBJECT"),
			"MESSAGE" => GetMessage("IM_NEW_NOTIFY_GROUP_MESSAGE"),
			"BODY_TYPE" => "text",
		));

		$emess = new CEventMessage;
		$emess->Add(array(
			"ACTIVE" => "Y",
			"EVENT_NAME" => "IM_NEW_MESSAGE",
			"LID" => $arSites,
			"EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
			"EMAIL_TO" => "#EMAIL_TO#",
			"SUBJECT" => GetMessage("IM_NEW_MESSAGE_SUBJECT"),
			"MESSAGE" => GetMessage("IM_NEW_MESSAGE_MESSAGE"),
			"BODY_TYPE" => "text",
		));

		$emess = new CEventMessage;
		$emess->Add(array(
			"ACTIVE" => "Y",
			"EVENT_NAME" => "IM_NEW_MESSAGE_GROUP",
			"LID" => $arSites,
			"EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
			"EMAIL_TO" => "#EMAIL_TO#",
			"SUBJECT" => GetMessage("IM_NEW_MESSAGE_GROUP_SUBJECT"),
			"MESSAGE" => GetMessage("IM_NEW_MESSAGE_GROUP_MESSAGE"),
			"BODY_TYPE" => "text",
		));
	}
}
?>