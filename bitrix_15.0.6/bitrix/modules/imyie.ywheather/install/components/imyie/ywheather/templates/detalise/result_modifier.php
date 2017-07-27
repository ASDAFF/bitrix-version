<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if(!CModule::IncludeModule('iblock'))
	return;

foreach($arResult["NOT_FORMATED"]["day"] as $key1 => $day)
{
	$ts = MakeTimeStamp($day["@attributes"]["date"],"YYYY-MM-DD");
	if($arParams["DATE_FORMAT"]!="")
		$arResult["NOT_FORMATED"]["day"][$key1]["print_date"] = CIBlockFormatProperties::DateFormat($arParams["DATE_FORMAT"],$ts);
	else
		$arResult["NOT_FORMATED"]["day"][$key1]["print_date"] = $day["@attributes"]["date"];
	
	$arResult["NOT_FORMATED"]["day"][$key1]["day_num"] = CIBlockFormatProperties::DateFormat("N",$ts);
}
?>