<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(!CModule::IncludeModule("search"))
{
	ShowError(GetMessage("BSF_C_MODULE_NOT_INSTALLED"));
	return;
}

$exFILTER = CSearchParameters::ConvertParamsToFilter($arParams, "arrFILTER");
foreach($exFILTER as $i => $subFilter)
{
	if(
		is_array($subFilter)
		&& array_key_exists("PARAMS", $subFilter)
		&& is_array($subFilter["PARAMS"])
		&& array_key_exists("socnet_group", $subFilter["PARAMS"])
	)
		$exFILTER["SOCIAL_NETWORK_GROUP"] = $subFilter["PARAMS"]["socnet_group"];
}

$exFILTER["SITE_ID"] = (!empty($arParams["SITE_ID"]) ? $arParams["SITE_ID"] : SITE_ID);
$arResult["exFILTER"] = $exFILTER;

if (empty($arParams["NAME"]))
{
	$arParams["NAME"] = "TAGS";
	$arParams["~NAME"] = "TAGS";
}

$arResult["ID"] = GenerateUniqId($arParams["NAME"]);
$arResult["NAME"] = $arParams["NAME"];
$arResult["~NAME"] = $arParams["~NAME"];
$arResult["VALUE"] = $arParams["VALUE"];
$arResult["~VALUE"] = $arParams["~VALUE"];

$this->IncludeComponentTemplate();

?>