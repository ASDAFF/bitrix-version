<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

$APPLICATION->IncludeComponent(
	"bitrix:main.interface.toolbar",
	"",
	array(
		"BUTTONS"=>array(
			array(
				"TEXT"=>GetMessage("CT_BLF_TOOLBAR_ADD"),
				"TITLE"=>GetMessage("CT_BLF_TOOLBAR_ADD_TITLE"),
				"LINK"=>$arResult["LIST_FIELD_EDIT_URL"],
				"ICON"=>"btn-add-field",
			),
			array("SEPARATOR"=>true),
			array(
				"TEXT"=>GetMessage("CT_BLF_TOOLBAR_LIST_EDIT"),
				"TITLE"=>GetMessage("CT_BLF_TOOLBAR_LIST_TITLE"),
				"LINK"=>$arResult["LIST_EDIT_URL"],
				"ICON"=>"btn-edit-list",
			),
			array(
				"TEXT"=>$arResult["IBLOCK"]["ELEMENTS_NAME"],
				"TITLE"=>GetMessage("CT_BLF_TOOLBAR_ELEMENTS_TITLE"),
				"LINK"=>$arResult["LIST_URL"],
				"ICON"=>"btn-view-elements",
			),
		),
	),
	$component, array("HIDE_ICONS" => "Y")
);

$APPLICATION->IncludeComponent(
	"bitrix:main.interface.grid",
	"",
	array(
		"GRID_ID"=>$arResult["GRID_ID"],
		"HEADERS"=>array(
			array("id"=>"SORT", "name"=>GetMessage("CT_BLF_LIST_SORT"), "default"=>true, "editable"=>true, "align"=>"right"),
			array("id"=>"NAME", "name"=>GetMessage("CT_BLF_LIST_NAME"), "default"=>true, "editable"=>true),
			array("id"=>"TYPE", "name"=>GetMessage("CT_BLF_LIST_TYPE"), "default"=>true),
			array("id"=>"IS_REQUIRED", "name"=>GetMessage("CT_BLF_LIST_IS_REQUIRED"), "default"=>true, "type"=>"checkbox", "editable"=>true),
			array("id"=>"MULTIPLE", "name"=>GetMessage("CT_BLF_LIST_MULTIPLE"), "default"=>true, "type"=>"checkbox", "editable"=>true),
		),
		"ROWS"=>$arResult["ROWS"],
		"ACTIONS"=>array("delete"=>true),
		"ACTION_ALL_ROWS"=>true,
		"AJAX_MODE"=>"Y",
		"AJAX_OPTION_SHADOW"=>"Y",
		"AJAX_OPTION_JUMP" => "N",
	),
	$component, array("HIDE_ICONS" => "Y")
);?>