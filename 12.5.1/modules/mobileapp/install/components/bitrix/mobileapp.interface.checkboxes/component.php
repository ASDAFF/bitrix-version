<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

/*sample data
$arParams = array(
	"ITEMS" => array(
		"1" =>"first checkbox",
		"2" =>"second checkbox",
		"3" =>"fird checkbox"
		),
	"CHECKED" => array("1","3");
	"TITLE" => "Checkboxes titles",
	"JS_EVENT_TAKE_CHECKBOXES_VALUES" => "onTakeCheckboxesValues",
	"JS_RESULT_HANDLER" => "resultHandlerFunction"
	"DOM_CONTAINER_ID" => "cb_container"
);

*/

if(!isset($arParams["ITEMS"]) || empty($arParams["ITEMS"]) || !is_array($arParams["ITEMS"]))
	return;

$arResult["TITLE"] = $arParams["TITLE"] ? $arParams["TITLE"] : false;
$arResult["DOM_CONTAINER_ID"] = $arParams["DOM_CONTAINER_ID"] ? $arParams["DOM_CONTAINER_ID"] : "ma_cb_".rand(1, 100);
$arResult["CHECKED"] = $arParams["CHECKED"] && is_array($arParams["CHECKED"]) ? $arParams["CHECKED"] : array();

$this->IncludeComponentTemplate();
?>
