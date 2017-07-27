<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(!CModule::IncludeModule('iblock'))
	return;

$arTemplateParameters = array(
	"DATE_FORMAT" => CIBlockParameters::GetDateFormat(GetMessage("PARAM_DATE_FORMAT"), "ADDITIONAL_SETTINGS"),
);
?>