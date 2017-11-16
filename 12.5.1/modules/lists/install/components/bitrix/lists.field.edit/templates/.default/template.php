<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

$arToolbar = array();
if($arResult["FIELD_ID"] && $arResult["FIELD_ID"] != "NAME")
{
	$arToolbar[] = array(
		"TEXT"=>GetMessage("CT_BLFE_TOOLBAR_DELETE"),
		"TITLE"=>GetMessage("CT_BLFE_TOOLBAR_DELETE_TITLE"),
		"LINK"=>"javascript:jsDelete('".CUtil::JSEscape("form_".$arResult["FORM_ID"])."', '".GetMessage("CT_BLFE_TOOLBAR_DELETE_WARNING")."')",
		"ICON"=>"btn-delete-field",
	);
}

if(count($arToolbar))
	$arToolbar[] = array(
		"SEPARATOR"=>"Y",
	);

$arToolbar[] = array(
	"TEXT"=>GetMessage("CT_BLFE_TOOLBAR_FIELDS"),
	"TITLE"=>GetMessage("CT_BLFE_TOOLBAR_FIELDS_TITLE"),
	"LINK"=>$arResult["LIST_FIELDS_URL"],
	"ICON"=>"btn-view-fields",
);

$APPLICATION->IncludeComponent(
	"bitrix:main.interface.toolbar",
	"",
	array(
		"BUTTONS"=>$arToolbar,
	),
	$component, array("HIDE_ICONS" => "Y")
);

$arTab1Fields = array(
	array("id"=>"SORT", "name"=>GetMessage("CT_BLFE_FIELD_SORT"), "params"=>array("size"=>5)),
	array("id"=>"NAME", "name"=>GetMessage("CT_BLFE_FIELD_NAME"), "required"=>true),
);

if($arResult["IS_READ_ONLY"])
	$arTab1Fields[] = array(
		"id"=>"IS_REQUIRED",
		"name"=>GetMessage("CT_BLFE_FIELD_IS_REQUIRED"),
		"type"=>"label",
		"value"=>GetMessage("MAIN_NO"),
	);
elseif($arResult["CAN_BE_OPTIONAL"])
	$arTab1Fields[] = array(
		"id"=>"IS_REQUIRED",
		"name"=>GetMessage("CT_BLFE_FIELD_IS_REQUIRED"),
		"type"=>"checkbox",
	);
else
	$arTab1Fields[] = array(
		"id"=>"IS_REQUIRED",
		"name"=>GetMessage("CT_BLFE_FIELD_IS_REQUIRED"),
		"type"=>"label",
		"value"=>GetMessage("MAIN_YES"),
	);


if($arResult["CAN_BE_MULTIPLE"])
	$arTab1Fields[] = array(
		"id"=>"MULTIPLE",
		"name"=>GetMessage("CT_BLFE_FIELD_MULTIPLE"),
		"type"=>"checkbox",
	);
else
	$arTab1Fields[] = array(
		"id"=>"MULTIPLE",
		"name"=>GetMessage("CT_BLFE_FIELD_MULTIPLE"),
		"type"=>"label",
		"value"=>GetMessage("MAIN_NO"),
	);

$arTab1Fields[] = array(
	"id"=>"TYPE",
	"name"=>GetMessage("CT_BLFE_FIELD_TYPE"),
	"type"=>"list",
	"items"=>$arResult["TYPES"],
	"params"=>array(
		'OnChange' => 'jsTypeChanged(\'form_'.$arResult["FORM_ID"].'\', this);',
	),
);

$arUserType = $arResult["FIELD"]["PROPERTY_USER_TYPE"];
$arPropertyFields = array();
$USER_TYPE_SETTINGS_HTML = "";
if(is_array($arUserType))
{
	if(array_key_exists("GetSettingsHTML", $arUserType))
	{
		$USER_TYPE_SETTINGS_HTML = call_user_func_array($arUserType["GetSettingsHTML"],
			array(
				$arResult["FIELD"],
				array(
					"NAME"=>"USER_TYPE_SETTINGS",
				),
				&$arPropertyFields,
			));
	}
}

if($arResult["IS_READ_ONLY"])
{
}
elseif($arResult["FORM_DATA"]["TYPE"] == "SORT")
{
	$arTab1Fields[] = array(
		"id" => "DEFAULT_VALUE",
		"name" => GetMessage("CT_BLFE_FIELD_DEFAULT_VALUE"),
	);
}
elseif($arResult["FORM_DATA"]["TYPE"] == "ACTIVE_FROM")
{
	$arTab1Fields[] = array(
		"id"=>"DEFAULT_VALUE",
		"name"=>GetMessage("CT_BLFE_FIELD_DEFAULT_VALUE"),
		"type"=>"list",
		"items" => array(
			"" => GetMessage("CT_BLFE_FIELD_ACTIVE_FROM_EMPTY"),
			"=now" => GetMessage("CT_BLFE_FIELD_ACTIVE_FROM_NOW"),
			"=today" => GetMessage("CT_BLFE_FIELD_ACTIVE_FROM_TODAY"),
		),
	);
}
elseif($arResult["FORM_DATA"]["TYPE"] == "ACTIVE_TO")
{
	//TODO
}
elseif($arResult["FORM_DATA"]["TYPE"] == "PREVIEW_PICTURE")
{
	$arTab1Fields[] = array(
		"id" => "DEFAULT_VALUE[FROM_DETAIL]",
		"name" => GetMessage("CT_BLFE_FIELD_PREVIEW_PICTURE_FROM_DETAIL"),
		"type" => "checkbox",
		"value" => $arResult["FORM_DATA"]["DEFAULT_VALUE"]["FROM_DETAIL"],
	);
	$arTab1Fields[] = array(
		"id" => "DEFAULT_VALUE[DELETE_WITH_DETAIL]",
		"name" => GetMessage("CT_BLFE_FIELD_PREVIEW_PICTURE_DELETE_WITH_DETAIL"),
		"type" => "checkbox",
		"value" => $arResult["FORM_DATA"]["DEFAULT_VALUE"]["DELETE_WITH_DETAIL"],
	);
	$arTab1Fields[] = array(
		"id" => "DEFAULT_VALUE[UPDATE_WITH_DETAIL]",
		"name" => GetMessage("CT_BLFE_FIELD_PREVIEW_PICTURE_UPDATE_WITH_DETAIL"),
		"type" => "checkbox",
		"value" => $arResult["FORM_DATA"]["DEFAULT_VALUE"]["UPDATE_WITH_DETAIL"],
	);
	$arTab1Fields[] = array(
		"id" => "DEFAULT_VALUE[SCALE]",
		"name" => GetMessage("CT_BLFE_FIELD_PICTURE_SCALE"),
		"type" => "checkbox",
		"value" => $arResult["FORM_DATA"]["DEFAULT_VALUE"]["SCALE"],
	);
	$arTab1Fields[] = array(
		"id" => "DEFAULT_VALUE[WIDTH]",
		"name" => GetMessage("CT_BLFE_FIELD_PICTURE_WIDTH"),
		"params" => array("size" => 7),
		"value" => $arResult["FORM_DATA"]["DEFAULT_VALUE"]["WIDTH"],
	);
	$arTab1Fields[] = array(
		"id" => "DEFAULT_VALUE[HEIGHT]",
		"name" => GetMessage("CT_BLFE_FIELD_PICTURE_HEIGHT"),
		"params" => array("size" => 7),
		"value" => $arResult["FORM_DATA"]["DEFAULT_VALUE"]["HEIGHT"],
	);
	$arTab1Fields[] = array(
		"id" => "DEFAULT_VALUE[IGNORE_ERRORS]",
		"name" => GetMessage("CT_BLFE_FIELD_PICTURE_IGNORE_ERRORS"),
		"type" => "checkbox",
		"value" => $arResult["FORM_DATA"]["DEFAULT_VALUE"]["IGNORE_ERRORS"],
	);
}
elseif($arResult["FORM_DATA"]["TYPE"] == "PREVIEW_TEXT" || $arResult["FORM_DATA"]["TYPE"] == "DETAIL_TEXT")
{
	$arTab1Fields[] = array(
		"id"=>"SETTINGS[USE_EDITOR]",
		"name"=>GetMessage("CT_BLFE_TEXT_USE_EDITOR"),
		"type"=>"checkbox",
		"value"=>$arResult["FORM_DATA"]["SETTINGS"]["USE_EDITOR"],
	);
	$arTab1Fields[] = array(
		"id"=>"SETTINGS[WIDTH]",
		"name"=>GetMessage("CT_BLFE_TEXT_WIDTH"),
		"params" => array("size" => 7),
		"value"=>$arResult["FORM_DATA"]["SETTINGS"]["WIDTH"],
	);
	$arTab1Fields[] = array(
		"id"=>"SETTINGS[HEIGHT]",
		"name"=>GetMessage("CT_BLFE_TEXT_HEIGHT"),
		"params" => array("size" => 7),
		"value"=>$arResult["FORM_DATA"]["SETTINGS"]["HEIGHT"],
	);
	$arTab1Fields[] = array(
		"id"=>"DEFAULT_VALUE",
		"name"=>GetMessage("CT_BLFE_FIELD_DEFAULT_VALUE"),
		"type"=>"textarea",
		"rows"=>"5"
	);
}
elseif($arResult["FORM_DATA"]["TYPE"] == "DETAIL_PICTURE")
{
	$arTab1Fields[] = array(
		"id" => "DEFAULT_VALUE[SCALE]",
		"name" => GetMessage("CT_BLFE_FIELD_PICTURE_SCALE"),
		"type" => "checkbox",
		"value" => $arResult["FORM_DATA"]["DEFAULT_VALUE"]["SCALE"],
	);
	$arTab1Fields[] = array(
		"id" => "DEFAULT_VALUE[WIDTH]",
		"name" => GetMessage("CT_BLFE_FIELD_PICTURE_WIDTH"),
		"params" => array("size" => 7),
		"value" => $arResult["FORM_DATA"]["DEFAULT_VALUE"]["WIDTH"],
	);
	$arTab1Fields[] = array(
		"id" => "DEFAULT_VALUE[HEIGHT]",
		"name" => GetMessage("CT_BLFE_FIELD_PICTURE_HEIGHT"),
		"params" => array("size" => 7),
		"value" => $arResult["FORM_DATA"]["DEFAULT_VALUE"]["HEIGHT"],
	);
	$arTab1Fields[] = array(
		"id" => "DEFAULT_VALUE[IGNORE_ERRORS]",
		"name" => GetMessage("CT_BLFE_FIELD_PICTURE_IGNORE_ERRORS"),
		"type" => "checkbox",
		"value" => $arResult["FORM_DATA"]["DEFAULT_VALUE"]["IGNORE_ERRORS"],
	);
}
elseif(preg_match("/^(L|L:)/", $arResult["FORM_DATA"]["TYPE"]))
{
	//No default value input
}
elseif(preg_match("/^(L|L:)/", $arResult["FORM_DATA"]["TYPE"]))
{
	//No default value input
}
elseif(preg_match("/^(F|F:)/", $arResult["FORM_DATA"]["TYPE"]))
{
	//No default value input
}
elseif(preg_match("/^(G|G:)/", $arResult["FORM_DATA"]["TYPE"]))
{
	$LINK = $arResult["FORM_DATA"]["LINK_IBLOCK_ID"];
	if($LINK <= 0)
		list($LINK,) = each($arResult["LINK_IBLOCKS"]);

	$items = array("" => GetMessage("CT_BLFE_NO_VALUE"));
	$rsSections = CIBlockSection::GetTreeList(Array("IBLOCK_ID"=>$LINK));
	while($ar = $rsSections->Fetch())
		$items[$ar["ID"]] = str_repeat(" . ", $ar["DEPTH_LEVEL"]).$ar["NAME"];

	$arTab1Fields[] = array(
		"id"=>"DEFAULT_VALUE",
		"name"=>GetMessage("CT_BLFE_FIELD_DEFAULT_VALUE"),
		"type"=>"list",
		"items"=>$items,
	);
}
elseif(preg_match("/^(E|E:)/", $arResult["FORM_DATA"]["TYPE"]))
{
	//No default value input
}
elseif(!is_array($arPropertyFields["HIDE"]) || !in_array("DEFAULT_VALUE", $arPropertyFields["HIDE"]))
{//Show default property value input if it was not cancelled by property
	if(is_array($arUserType) && array_key_exists("GetPublicEditHTML", $arUserType))
	{
		$arTab1Fields[] = array(
			"id"=>"DEFAULT_VALUE",
			"name"=>GetMessage("CT_BLFE_FIELD_DEFAULT_VALUE"),
			"type"=>"custom",
			"value"=> call_user_func_array($arUserType["GetPublicEditHTML"],
				array(
					$arResult["FIELD"],
					array(
						"VALUE"=>$arResult["FORM_DATA"]["~DEFAULT_VALUE"],
						"DESCRIPTION"=>""
					),
					array(
						"VALUE"=>"DEFAULT_VALUE",
						"DESCRIPTION"=>"",
						"MODE" => "EDIT_FORM",
						"FORM_NAME" => "form_".$arResult["FORM_ID"],
					),
				)
			),
		);
	}
	else
	{
		$arTab1Fields[] = array(
			"id"=>"DEFAULT_VALUE",
			"name"=>GetMessage("CT_BLFE_FIELD_DEFAULT_VALUE"),
		);
	}
}

$custom_html = "";

if(preg_match("/^(G|G:)/", $arResult["FORM_DATA"]["TYPE"]))
{
	$arTab1Fields[] = array(
		"id"=>"LINK_IBLOCK_ID",
		"name"=>GetMessage("CT_BLFE_FIELD_SECTION_LINK_IBLOCK_ID"),
		"type"=>"list",
		"items"=>$arResult["LINK_IBLOCKS"],
		"params"=>array('OnChange' => 'jsTypeChanged(\'form_'.$arResult["FORM_ID"].'\', this);'),
	);
}
elseif(preg_match("/^(E|E:)/", $arResult["FORM_DATA"]["TYPE"]))
{
	$arTab1Fields[] = array(
		"id"=>"LINK_IBLOCK_ID",
		"name"=>GetMessage("CT_BLFE_FIELD_ELEMENT_LINK_IBLOCK_ID"),
		"type"=>"list",
		"items"=>$arResult["LINK_IBLOCKS"],
	);
}
elseif(isset($arResult["FORM_DATA"]["LINK_IBLOCK_ID"]))
{
	$custom_html .= '<input type="hidden" name="LINK_IBLOCK_ID" value="'.$arResult["FORM_DATA"]["LINK_IBLOCK_ID"].'">';
}

if($USER_TYPE_SETTINGS_HTML)
{
	$arTab1Fields[] = array(
		"id"=>"USER_TYPE_SETTINGS",
		"type"=>"custom",
		"value"=>$USER_TYPE_SETTINGS_HTML,
		"colspan"=>true,
	);
}

$arTabs = array(
	array("id"=>"tab1", "name"=>GetMessage("CT_BLFE_TAB_EDIT"), "title"=>GetMessage("CT_BLFE_TAB_EDIT_TITLE"), "icon"=>"", "fields"=>$arTab1Fields),
);

//List properties
if(is_array($arResult["LIST"]))
{
	if(preg_match("/^(L|L:)/", $arResult["FORM_DATA"]["TYPE"]))
	{
		$sort = 10;
		$html = '<table id="tblLIST" width="100%">';
		foreach($arResult["LIST"] as $arEnum)
		{
			$html .= '
				<tr>
				<td style="display:none"></td>
				<td align="center" class="sort-up"><div class="sort-arrow sort-up" onclick="sort_up(this);" title="'.GetMessage("CT_BLFE_SORT_UP_TITLE").'"></div></td>
				<td align="center" class="sort-down"><div class="sort-arrow sort-down" onclick="sort_down(this);" title="'.GetMessage("CT_BLFE_SORT_DOWN_TITLE").'"></div></td>
				<td>
					<input type="hidden" name="LIST['.$arEnum["ID"].'][SORT]" value="'.$sort.'" class="sort-input">
					<input type="text" size="35" name="LIST['.$arEnum["ID"].'][VALUE]" value="'.$arEnum["VALUE"].'" class="value-input">
				</td>
				<td align="center" class="delete-action"><div class="delete-action" onclick="delete_item(this);" title="'.GetMessage("CT_BLFE_DELETE_TITLE").'"></div></td>
				</tr>
			';
			$sort += 10;
		}

		$html .= '</table>';
		$html .= '<input type="button" value="'.GetMessage("CT_BLFE_LIST_ITEM_ADD").'" onClick="addNewTableRow(\'tblLIST\', /LIST\[(n)([0-9]*)\]/g, 2)">';

		$html .= '
			<br><br>
			<a class="href-action" href="javascript:void(0)" onclick="toggle_input(\'import\'); return false;">'.GetMessage("CT_BLFE_ENUM_IMPORT").'</a>
			<div id="import" style="'.(strlen($arResult["FORM_DATA"]["LIST_TEXT_VALUES"]) > 0? '': 'display:none; ').'width:100%">
				<p>'.GetMessage("CT_BLFE_ENUM_IMPORT_HINT").'</p>
				<textarea name="LIST_TEXT_VALUES" id="LIST_TEXT_VALUES" style="width:100%" rows="20">'.htmlspecialcharsex($arResult["FORM_DATA"]["LIST_TEXT_VALUES"]).'</textarea>
			</div>
		';

		$html .= '
			<br><br>
			<a class="href-action" href="javascript:void(0)" onclick="toggle_input(\'defaults\'); return false;">'.($arResult["FORM_DATA"]["MULTIPLE"] == "Y"? GetMessage("CT_BLFE_ENUM_DEFAULTS"): GetMessage("CT_BLFE_ENUM_DEFAULT")).'</a>
			<div id="defaults" style="'.(strlen($arResult["FORM_DATA"]["LIST_TEXT_VALUES"]) > 0? '': 'display:none; ').'width:100%">
			<br>
		';

		if($arResult["FORM_DATA"]["MULTIPLE"] == "Y")
			$html .= '<select multiple name="LIST_DEF[]" id="LIST_DEF" size="10">';
		else
			$html .= '<select name="LIST_DEF[]" id="LIST_DEF" size="1">';

		if($arResult["FORM_DATA"]["IS_REQIRED"] != "Y")
			$html .= '<option value=""'.(count($arResult["LIST_DEF"])==0? ' selected': '').'>'.GetMessage("CT_BLFE_ENUM_NO_DEFAULT").'</option>';

		foreach($arResult["LIST"] as $arEnum)
			$html .= '<option value="'.$arEnum["ID"].'"'.(isset($arResult["LIST_DEF"][$arEnum["ID"]])? ' selected': '').'>'.$arEnum["VALUE"].'</option>';

		$html .= '
				</select>
			</div>
		';

		$arTabs[] = array(
			"id"=>"tab2",
			"name"=>GetMessage("CT_BLFE_TAB_LIST"),
			"title"=>GetMessage("CT_BLFE_TAB_LIST_TITLE"),
			"icon"=>"",
			"fields"=>array(
				array(
					"id" => "LIST",
					"colspan" => true,
					"type" => "custom",
					"value" => $html,
				),
			),
		);
	}
	else
	{
		foreach($arResult["LIST"] as $arEnum)
		{
			$custom_html .= '<input type="hidden" name="LIST['.$arEnum["ID"].'][SORT]" value="'.$arEnum["SORT"].'">'
				.'<input type="hidden" name="LIST['.$arEnum["ID"].'][VALUE]" value="'.$arEnum["VALUE"].'">';
		}
	}
}

$custom_html .= '<input type="hidden" name="action" id="action" value="">';

$APPLICATION->IncludeComponent(
	"bitrix:main.interface.form",
	"",
	array(
		"FORM_ID"=>$arResult["FORM_ID"],
		"TABS"=>$arTabs,
		"BUTTONS"=>array("back_url"=>$arResult["~LIST_FIELDS_URL"], "custom_html"=>$custom_html),
		"DATA"=>$arResult["FORM_DATA"],
		"SHOW_SETTINGS"=>"N",
		"THEME_GRID_ID"=>$arResult["GRID_ID"],
	),
	$component, array("HIDE_ICONS" => "Y")
);
?>