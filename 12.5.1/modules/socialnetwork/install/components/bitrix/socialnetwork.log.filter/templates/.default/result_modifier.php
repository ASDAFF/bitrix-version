<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$strParams = "SONET_FILTER_MODE=AJAX";
$arResult["AjaxURL"] = $GLOBALS["APPLICATION"]->GetCurPageParam($strParams, array("SONET_FILTER_MODE"));
$arResult["MODE"] = (isset($_REQUEST["SONET_FILTER_MODE"]) && $_REQUEST["SONET_FILTER_MODE"] == "AJAX" ? "AJAX" : false);

if (
	(
		$GLOBALS["USER"]->IsAuthorized() 
		|| $arParams["AUTH"] == "Y" 
		|| $arParams["SUBSCRIBE_ONLY"] != "Y"
	)
	&& $arParams["SHOW_EVENT_ID_FILTER"] == "Y"
)
{
	__IncludeLang(dirname(__FILE__)."/lang/".LANGUAGE_ID."/result_modifier.php");	

	$arResult["DATE_FILTER"] = array(
		"" => GetMessage("SONET_C30_DATE_FILTER_NO_NO_NO_1"),
		"today" => GetMessage("SONET_C30_DATE_FILTER_TODAY"),
		"yesterday" => GetMessage("SONET_C30_DATE_FILTER_YESTERDAY"),
		"week" => GetMessage("SONET_C30_DATE_FILTER_WEEK"),
		"week_ago" => GetMessage("SONET_C30_DATE_FILTER_WEEK_AGO"),
		"month" => GetMessage("SONET_C30_DATE_FILTER_MONTH"),
		"month_ago" => GetMessage("SONET_C30_DATE_FILTER_MONTH_AGO"),
		"days" => GetMessage("SONET_C30_DATE_FILTER_LAST"),
		"exact" => GetMessage("SONET_C30_DATE_FILTER_EXACT"),
		"after" => GetMessage("SONET_C30_DATE_FILTER_LATER"),
		"before" => GetMessage("SONET_C30_DATE_FILTER_EARLIER"),
		"interval" => GetMessage("SONET_C30_DATE_FILTER_INTERVAL"),
	);
}

$arResult["FOLLOW_TYPE"] = "";
if ($GLOBALS["USER"]->IsAuthorized())
{
	if (array_key_exists("set_follow_type", $_GET))
	{
		CSocNetLogFollow::Set($GLOBALS["USER"]->GetID(), "**", $_GET["set_follow_type"] == "Y" ? "Y" : "N", false);
		if ($_GET["set_follow_type"] != "Y")
			$_SESSION["SL_SHOW_FOLLOW_HINT"] = "Y";
		LocalRedirect("");
	}

	$rsFollow = CSocNetLogFollow::GetList(
		array(
			"USER_ID" => $GLOBALS["USER"]->GetID(),
			"CODE" => "**"
		),
		array("TYPE")
	);

	if ($arFollow = $rsFollow->Fetch())
		$arResult["FOLLOW_TYPE"] = $arFollow["TYPE"];
	else
		$arResult["FOLLOW_TYPE"] = COption::GetOptionString("socialnetwork", "follow_default_type", "Y");
}

$arResult["flt_created_by_string"] = "";

if (strlen($_REQUEST["flt_created_by_string"]) > 0)
	$arResult["flt_created_by_string"] = $_REQUEST["flt_created_by_string"];
else
{
	if (is_array($_REQUEST["flt_created_by_id"]) && intval($_REQUEST["flt_created_by_id"][0]) > 0)
		$user_id_tmp = $_REQUEST["flt_created_by_id"][0];
	elseif(intval($_REQUEST["flt_created_by_id"]) > 0)
		$user_id_tmp = $_REQUEST["flt_created_by_id"];

	if (intval($user_id_tmp) > 0)
	{
		$rsUser = CUser::GetByID($user_id_tmp);
		if ($arUser = $rsUser->GetNext())
			$arResult["flt_created_by_string"] = CUser::FormatName($arParams["NAME_TEMPLATE"]." <#EMAIL#> [#ID#]", $arUser, ($arParams["SHOW_LOGIN"] != "N"), false);
	}
}

?>