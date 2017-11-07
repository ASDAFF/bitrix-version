<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/iblock.php");
IncludeModuleLangFile(__FILE__);

//Init variables
$n = preg_replace("/[^a-zA-Z0-9_:\\[\\]]/", "", $_GET["n"]);
$k = preg_replace("/[^a-zA-Z0-9_:]/", "", $_GET["k"]);
$lookup = preg_replace("/[^a-zA-Z0-9_:]/", "", $_GET["lookup"]);

$m = $_GET["m"] === "y";
$get_xml_id = $_GET["get_xml_id"] === "Y";
$strWarning = "";

$sTableID = "tbl_iblock_el_search".md5($n);
$lAdmin = new CAdminList($sTableID);
$lAdmin->InitFilter(array("filter_iblock_id"));
$IBLOCK_ID = intval($_GET["IBLOCK_ID"]) > 0? intval($_GET["IBLOCK_ID"]): intval($filter_iblock_id);

$arIBTYPE = false;
if($IBLOCK_ID > 0)
{
	$arIBlock = CIBlock::GetArrayByID($IBLOCK_ID);

	if($arIBlock)
	{
		$arIBTYPE = CIBlockType::GetByIDLang($arIBlock["IBLOCK_TYPE_ID"], LANG);
		if(!$arIBTYPE)
			$APPLICATION->AuthForm(GetMessage("IBLOCK_BAD_BLOCK_TYPE_ID"));

		$bBadBlock = !CIBlockRights::UserHasRightTo($IBLOCK_ID, $IBLOCK_ID, "iblock_admin_display");
	}
	else
	{
		$bBadBlock = true;
	}

	if($bBadBlock)
		$APPLICATION->AuthForm(GetMessage("IBLOCK_BAD_IBLOCK"));
}
else
{
	$arIBlock = array(
		"ID" => 0,
		"ELEMENTS_NAME" => GetMessage("IBLOCK_ELSEARCH_ELEMENTS"),
	);
}

$APPLICATION->SetTitle(GetMessage("IBLOCK_ELSEARCH_TITLE"));

$dbrFProps = CIBlockProperty::GetList(
		Array(
			"SORT" => "ASC",
			"NAME" => "ASC",
		),
		Array(
			"ACTIVE"=>"Y",
			"IBLOCK_ID"=>$IBLOCK_ID,
		)
	);

$arProps = Array();
while($arFProps = $dbrFProps->GetNext())
{
	if(strlen($arFProps["USER_TYPE"])>0)
		$arFProps["PROPERTY_USER_TYPE"] = CIBlockProperty::GetUserType($arFProps["USER_TYPE"]);
	else
		$arFProps["PROPERTY_USER_TYPE"] = array();

	$arProps[] = $arFProps;
}

$arFilterFields = Array(
	"filter_iblock_id",
	"filter_section",
	"filter_subsections",
	"filter_id_start",
	"filter_id_end",
	"filter_type",
	"filter_timestamp_from",
	"filter_timestamp_to",
	"filter_modified_user_id",
	"filter_modified_by",
	"filter_status_id",
	"filter_status",
	"filter_active",
	"filter_intext",
	"filter_name",
);

foreach($arProps as $prop)
{
	if($prop["FILTRABLE"]=="Y" && $prop["PROPERTY_TYPE"]!="F")
		$arFilterFields[] = "find_property_".$prop["ID"];
}

$oSort = new CAdminSorting($sTableID, "NAME", "asc");
$lAdmin = new CAdminList($sTableID, $oSort);

$lAdmin->InitFilter($arFilterFields);

$arFilter = Array(
	"IBLOCK_TYPE" => $filter_type,
	"SECTION_ID" => $filter_section,
	"MODIFIED_USER_ID" => $filter_modified_user_id,
	"MODIFIED_BY" => $filter_modified_by,
	"ACTIVE" => $filter_active,
	"?NAME" => $filter_name,
	"?SEARCHABLE_CONTENT" => $filter_intext,
	"SHOW_NEW" => "Y",
);

if($filter_iblock_id > 0)
	$arFilter["IBLOCK_ID"] = $filter_iblock_id;
elseif($IBLOCK_ID > 0)
	$arFilter["IBLOCK_ID"] = $IBLOCK_ID;
else
	$arFilter["IBLOCK_ID"] = -1;

if(IntVal($filter_section)<0 || strlen($filter_section)<=0)
	unset($arFilter["SECTION_ID"]);
elseif($filter_subsections=="Y")
{
	if($arFilter["SECTION_ID"]==0)
		unset($arFilter["SECTION_ID"]);
	else
		$arFilter["INCLUDE_SUBSECTIONS"] = "Y";
}

if (!empty($filter_id_start)) $arFilter[">=ID"] = $filter_id_start;
if (!empty($filter_id_end)) $arFilter["<=ID"] = $filter_id_end;
if (!empty($filter_timestamp_from)) $arFilter["DATE_MODIFY_FROM"] = $filter_timestamp_from;
if (!empty($filter_timestamp_to)) $arFilter["DATE_MODIFY_TO"] = $filter_timestamp_to;
if (!empty($filter_status_id)) $arFilter["WF_STATUS"] = $filter_status_id;
if (!empty($filter_status) && strcasecmp($filter_status, "NOT_REF")) $arFilter["WF_STATUS"] = $filter_status;

foreach($arProps as $prop)
{
	if($prop["FILTRABLE"]=="Y" && $prop["PROPERTY_TYPE"]!="F" && !empty(${"find_el_property_".$prop["ID"]}))
		$arFilter["?PROPERTY_".$prop["ID"]] = ${"find_el_property_".$prop["ID"]};
}

$arFilter["CHECK_PERMISSIONS"]="Y";

$arHeader = Array();
$arHeader[] = array("id"=>"ID", "content"=>GetMessage("IBLOCK_FIELD_ID"), "sort"=>"id", "align"=>"right", "default"=>true);
$arHeader[] = array("id"=>"TIMESTAMP_X", "content"=>GetMessage("IBLOCK_FIELD_TIMESTAMP_X"), "sort"=>"timestamp_x", "default"=>true);
$arHeader[] = array("id"=>"USER_NAME", "content"=>GetMessage("IBLOCK_FIELD_USER_NAME"), "sort"=>"modified_by", "default"=>true);
$arHeader[] = array("id"=>"ACTIVE", "content"=>GetMessage("IBLOCK_FIELD_ACTIVE"), "sort"=>"active", "align"=>"center", "default"=>true);
$arHeader[] = array("id"=>"NAME", "content"=>GetMessage("IBLOCK_FIELD_NAME"), "sort"=>"name", "default"=>true);

$arHeader[] = array("id"=>"ACTIVE_FROM", "content"=>GetMessage("IBLOCK_FIELD_ACTIVE_FROM"), "sort"=>"date_active_from");
$arHeader[] = array("id"=>"ACTIVE_TO", "content"=>GetMessage("IBLOCK_FIELD_ACTIVE_TO"), "sort"=>"date_active_to");
$arHeader[] = array("id"=>"SORT", "content"=>GetMessage("IBLOCK_FIELD_SORT"), "sort"=>"sort", "align"=>"right");
$arHeader[] = array("id"=>"DATE_CREATE", "content"=>GetMessage("IBLOCK_FIELD_DATE_CREATE"), "sort"=>"created");
$arHeader[] = array("id"=>"CREATED_USER_NAME", "content"=>GetMessage("IBLOCK_FIELD_CREATED_USER_NAME"), "sort"=>"created_by");

$arHeader[] = array("id"=>"CODE", "content"=>GetMessage("IBLOCK_FIELD_CODE"), "sort"=>"code");
$arHeader[] = array("id"=>"EXTERNAL_ID", "content"=>GetMessage("IBLOCK_FIELD_XML_ID"), "sort"=>"external_id");

if(CModule::IncludeModule("workflow"))
{
	$arHeader[] = array("id"=>"WF_STATUS_ID", "content"=>GetMessage("IBLOCK_FIELD_STATUS"), "sort"=>"status", "default"=>true);
	$arHeader[] = array("id"=>"LOCKED_USER_NAME", "content"=>GetMessage("IBLOCK_ELSEARCH_LOCK_BY"));
}

$arHeader[] = array("id"=>"SHOW_COUNTER", "content"=>GetMessage("IBLOCK_FIELD_SHOW_COUNTER"), "sort"=>"show_counter", "align"=>"right");
$arHeader[] = array("id"=>"SHOW_COUNTER_START", "content"=>GetMessage("IBLOCK_FIELD_SHOW_COUNTER_START"), "sort"=>"show_counter_start", "align"=>"right");
$arHeader[] = array("id"=>"PREVIEW_PICTURE", "content"=>GetMessage("IBLOCK_FIELD_PREVIEW_PICTURE"), "align"=>"right");
$arHeader[] = array("id"=>"PREVIEW_TEXT", "content"=>GetMessage("IBLOCK_FIELD_PREVIEW_TEXT"));
$arHeader[] = array("id"=>"DETAIL_PICTURE", "content"=>GetMessage("IBLOCK_FIELD_DETAIL_PICTURE"), "align"=>"center");
$arHeader[] = array("id"=>"DETAIL_TEXT", "content"=>GetMessage("IBLOCK_FIELD_DETAIL_TEXT"));

foreach($arProps as $prop)
{
	$arHeader[] = array("id"=>"PROPERTY_".$prop['ID'], "content"=>$prop['NAME'], "align"=>($prop["PROPERTY_TYPE"]=='N'?"right":"left"), "sort" => ($prop["MULTIPLE"]!='Y'? "PROPERTY_".$prop['ID'] : ""));
}

$lAdmin->AddHeaders($arHeader);

$arSelectedFields = $lAdmin->GetVisibleHeaderColumns();

$arSelectedProps = Array();
foreach($arProps as $prop)
{
	if(in_array("PROPERTY_".$prop['ID'], $arSelectedFields))
	{
		$arSelectedProps[] = $prop;
		$arSelect[$prop['ID']] = Array();
		$props = CIBlockProperty::GetPropertyEnum($prop['ID']);
		while($res = $props->Fetch())
			$arSelect[$prop['ID']][$res["ID"]] = $res["VALUE"];
	}

	if($prop["MULTIPLE"]=='Y')
	{
		if($key = array_search("PROPERTY_".$prop['ID'], $arSelectedFields))
			unset($arSelectedFields[$key]);
	}
}

if(!in_array("ID", $arSelectedFields))
	$arSelectedFields[] = "ID";

$arSelectedFields[] = "LANG_DIR";
$arSelectedFields[] = "LID";
$arSelectedFields[] = "WF_PARENT_ELEMENT_ID";

if(in_array("LOCKED_USER_NAME", $arSelectedFields))
	$arSelectedFields[] = "WF_LOCKED_BY";
if(in_array("USER_NAME", $arSelectedFields))
	$arSelectedFields[] = "MODIFIED_BY";
if(in_array("CREATED_USER_NAME", $arSelectedFields))
	$arSelectedFields[] = "CREATED_BY";
if(in_array("PREVIEW_TEXT", $arSelectedFields))
	$arSelectedFields[] = "PREVIEW_TEXT_TYPE";
if(in_array("DETAIL_TEXT", $arSelectedFields))
	$arSelectedFields[] = "DETAIL_TEXT_TYPE";

$arSelectedFields[] = "LOCK_STATUS";
$arSelectedFields[] = "WF_NEW";
$arSelectedFields[] = "WF_STATUS_ID";
$arSelectedFields[] = "DETAIL_PAGE_URL";
$arSelectedFields[] = "SITE_ID";
$arSelectedFields[] = "CODE";
$arSelectedFields[] = "EXTERNAL_ID";
$arSelectedFields[] = "NAME";
$arSelectedFields[] = "XML_ID";

$rsData = CIBlockElement::GetList(Array($by=>$order), $arFilter, false, Array("nPageSize"=>CAdminResult::GetNavSize($sTableID)), $arSelectedFields);
$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart();
$lAdmin->NavText($rsData->GetNavPrint($arIBlock["ELEMENTS_NAME"]));

function GetElementName($ID)
{
	$ID = IntVal($ID);
	static $cache = array();
	if(!array_key_exists($ID, $cache))
	{
		$rsElement = CIBlockElement::GetList(Array(), Array("ID"=>$ID, "SHOW_HISTORY"=>"Y"), false, false, array("ID","IBLOCK_ID","NAME"));
		$cache[$ID] = $rsElement->GetNext();
	}
	return $cache[$ID];
}
function GetIBlockTypeID($IBLOCK_ID)
{
	$IBLOCK_ID = IntVal($IBLOCK_ID);
	static $cache = array();
	if(!array_key_exists($IBLOCK_ID, $cache))
	{
		$rsIBlock = CIBlock::GetByID($IBLOCK_ID);
		if(!($cache[$IBLOCK_ID] = $rsIBlock->GetNext()))
			$cache[$IBLOCK_ID] = array("IBLOCK_TYPE_ID"=>"");
	}
	return $cache[$IBLOCK_ID]["IBLOCK_TYPE_ID"];
}

if($IBLOCK_ID <= 0)
{
	$lAdmin->BeginPrologContent();
	$message = new CAdminMessage(array("MESSAGE"=>GetMessage("IBLOCK_ELSEARCH_CHOOSE_IBLOCK"), "TYPE"=>"OK"));
	echo $message->Show();
	$lAdmin->EndPrologContent();
}

while($arRes = $rsData->GetNext())
{
	foreach($arSelectedProps as $aProp)
	{
		if($arRes["PROPERTY_".$aProp['ID'].'_ENUM_ID']>0)
			$arRes["PROPERTY_".$aProp['ID']] = $arRes["PROPERTY_".$aProp['ID'].'_ENUM_ID'];
		else
			$arRes["PROPERTY_".$aProp['ID']] = $arRes["PROPERTY_".$aProp['ID'].'_VALUE'];
	}

	$row =& $lAdmin->AddRow($arRes["ID"], $arRes);

	$row->AddViewField("NAME", $arRes["NAME"]."<input type=hidden name='n".$arRes["ID"]."' id='name_".$arRes["ID"]."' value='".CUtil::JSEscape(htmlspecialcharsbx($arRes["NAME"]))."'>");
	$row->AddViewField("USER_NAME", "[<a target=\"_blank\" href=\"user_edit.php?lang=".LANGUAGE_ID."&ID=".$arRes["MODIFIED_BY"]."\">".$arRes["MODIFIED_BY"]."</a>]&nbsp;".$arRes["USER_NAME"]);
	$row->AddCheckField("ACTIVE");
	$row->AddViewField("CREATED_USER_NAME", "[<a target=\"_blank\" href=\"user_edit.php?lang=".LANGUAGE_ID."&ID=".$arRes["CREATED_BY"]."\">".$arRes["CREATED_BY"]."</a>]&nbsp;".$arRes["CREATED_USER_NAME"]);
	$row->AddViewField("PREVIEW_PICTURE", CFile::ShowFile($arRes["PREVIEW_PICTURE"], 100000, 50, 50, true));
	$row->AddViewField("DETAIL_PICTURE", CFile::ShowFile($arRes["DETAIL_PICTURE"], 100000, 50, 50, true));

	$row->AddViewField("WF_STATUS_ID", htmlspecialcharsbx(CIBlockElement::WF_GetStatusTitle($arRes["WF_STATUS_ID"]))."<input type=hidden name='n".$arRes["ID"]."' value='".CUtil::JSEscape($arRes["NAME"])."'>");
	$row->AddViewField("LOCKED_USER_NAME", '&nbsp;<a href="user_edit.php?lang='.LANG.'&ID='.$arRes["WF_LOCKED_BY"].'" title="'.GetMessage("IBLOCK_ELSEARCH_USERINFO").'">'.$arRes["LOCKED_USER_NAME"].'</a>');

	foreach($arSelectedProps as $aProp)
	{
		if(!in_array("PROPERTY_".$aProp['ID'], $lAdmin->GetVisibleHeaderColumns()))
			continue;

		if($aProp['MULTIPLE']!='Y')
		{
			if($aProp['PROPERTY_TYPE']=='L')
				$row->AddViewField("PROPERTY_".$aProp['ID'], $arRes["PROPERTY_".$aProp['ID']."_VALUE"]);
			elseif($aProp['PROPERTY_TYPE']=='F')
				$row->AddViewField("PROPERTY_".$aProp['ID'], CFile::ShowFile($arRes["PROPERTY_".$aProp['ID']], 100000, 50, 50, true));
			elseif($aProp['PROPERTY_TYPE']=='G')
			{
				$PropV = $arRes["PROPERTY_".$aProp['ID']];
				if(IntVal($arRes["PROPERTY_".$aProp['ID']])>0)
				{
					$dbPropEl = CIBlockSection::GetList(Array(), Array("ID"=>$arRes["PROPERTY_".$aProp['ID']]));
					if($arPropEl = $dbPropEl->GetNext())
					{
						$PropV = $arPropEl['NAME'].' [<a href="'.htmlspecialcharsbx(CIBlock::GetAdminSectionEditLink($arPropEl['IBLOCK_ID'], $arPropEl['ID'])).'" title="'.GetMessage("IBLOCK_ELSEARCH_SECTION_EDIT").'">'.$arPropEl['ID'].'</a>]';
					}
				}
				$row->AddViewField("PROPERTY_".$aProp['ID'], $PropV);
			}
			elseif($aProp['PROPERTY_TYPE']=='E')
			{
				if($t = GetElementName($arRes["PROPERTY_".$aProp['ID']]))
				{
					$row->AddViewField("PROPERTY_".$aProp['ID'], $t['NAME'].' [<a href="'.htmlspecialcharsbx(CIBlock::GetAdminElementEditLink($t['IBLOCK_ID'], $t['ID'])).'" title="'.GetMessage("IBLOCK_ELSEARCH_ELEMENT_EDIT").'">'.$t['ID'].'</a>]');
				}
			}
		}
		else
		{
			$v = '';
			$arPropMultVal = Array();
			$arPropMultValID = Array();
			$dbPVals = CIBlockElement::GetProperty($IBLOCK_ID, $arRes["ID"], $xxord, $xxby, Array("ID"=>$aProp['ID']));
			while($arPVals = $dbPVals->Fetch())
			{
				$res = '';
				if($aProp['PROPERTY_TYPE']=='F')
					$res = CFile::ShowFile($arPVals['VALUE'], 100000, 50, 50, true);
				elseif($aProp['PROPERTY_TYPE']=='G')
				{
					$t = CIBlockSection::GetByID($arPVals['VALUE']);
					if($t = $t->GetNext())
						$res = $t['NAME'].' [<a href="'.htmlspecialcharsbx(CIBlock::GetAdminSectionEditLink($t['IBLOCK_ID'], $t['ID'])).'" title="'.GetMessage("IBLOCK_ELSEARCH_SECTION_EDIT").'">'.$t['ID'].'</a>]';
				}
				elseif($aProp['PROPERTY_TYPE']=='E')
				{
					if($t = GetElementName($arPVals['VALUE']))
					{
						$res = $t['NAME'].' [<a href="'.htmlspecialcharsbx(CIBlock::GetAdminElementEditLink($t['IBLOCK_ID'], $t['ID'])).'" title="'.GetMessage("IBLOCK_ELSEARCH_ELEMENT_EDIT").'">'.$t['ID'].'</a>]';
					}
				}
				else
					$res = htmlspecialcharsex(($arPVals['VALUE_ENUM']?$arPVals['VALUE_ENUM']:$arPVals['VALUE']));

				$v .= ($v!=''?' / ':'').$res;
				$arPropMultVal[] = ($arPVals['VALUE_ENUM']?$arPVals['VALUE_ENUM']:$arPVals['VALUE']);
				$arPropMultValID[$arPVals['PROPERTY_VALUE_ID']] = $arPVals['VALUE'];
			}

			$row->AddViewField("PROPERTY_".$aProp['ID'], $v);
		}
		unset($arSelectedProps[$aProp['ID']]["CACHE"]);
	}

	$row->AddActions(array(
		array(
			"DEFAULT" => "Y",
			"TEXT" => GetMessage("IBLOCK_ELSEARCH_SELECT"),
			"ACTION"=>"javascript:SelEl('".CUtil::JSEscape($get_xml_id? $arRes["XML_ID"]: $arRes["ID"])."', '".CUtil::JSEscape($arRes["NAME"])."')",
		),
	));
}

$lAdmin->AddFooter(
	array(
		array("title"=>GetMessage("MAIN_ADMIN_LIST_SELECTED"), "value"=>$rsData->SelectedRowsCount()),
		array("counter"=>true, "title"=>GetMessage("MAIN_ADMIN_LIST_CHECKED"), "value"=>"0"),
	)
);

if($m)
{
	$lAdmin->AddGroupActionTable(array(
		array(
			"action" => "SelAll()",
			"value" => "select",
			"type" => "button",
			"name" => GetMessage("IBLOCK_ELSEARCH_SELECT"),
			)
	), array("disable_action_target"=>true));
}

$lAdmin->AddAdminContextMenu(array(), false);

$lAdmin->CheckListMode();

/***************************************************************************
				HTML form
****************************************************************************/
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");
?>
<form name="form1" method="GET" action="<?echo $APPLICATION->GetCurPage()?>">
<?
function _ShowGroupPropertyField($name, $property_fields, $values)
{
	if(!is_array($values)) $values = Array();

	$res = "";
	$bWas = false;
	$sections = CIBlockSection::GetTreeList(Array("IBLOCK_ID"=>$property_fields["LINK_IBLOCK_ID"]));
	while($ar = $sections->GetNext())
	{
		$res .= '<option value="'.$ar["ID"].'"';
		if(in_array($ar["ID"], $values))
		{
			$bWas = true;
			$res .= ' selected';
		}
		$res .= '>'.str_repeat(" . ", $ar["DEPTH_LEVEL"]).$ar["NAME"].'</option>';
	}
	echo '<select name="'.$name.'[]">';
	echo '<option value=""'.(!$bWas?' selected':'').'>'.GetMessage("IBLOCK_ELSEARCH_NOT_SET").'</option>';
	echo $res;
	echo '</select>';
}

$arFindFields = Array(
		"id"=>"ID",
		"date"=>GetMessage("IBLOCK_ELSEARCH_F_DATE"),
		"chn"=>GetMessage("IBLOCK_ELSEARCH_F_CHANGED"),
	);

if(CModule::IncludeModule("workflow"))
	$arFindFields["stat"] = GetMessage("IBLOCK_ELSEARCH_F_STATUS");

if(is_array($arIBTYPE) && ($arIBTYPE["SECTIONS"] == "Y"))
	$arFindFields["sec"] = GetMessage("IBLOCK_ELSEARCH_F_SECTION");

$arFindFields["act"] = GetMessage("IBLOCK_ELSEARCH_F_ACTIVE");
$arFindFields["tit"] = GetMessage("IBLOCK_ELSEARCH_F_TITLE");
$arFindFields["dsc"] = GetMessage("IBLOCK_ELSEARCH_F_DSC");

foreach($arProps as $prop)
	if($prop["FILTRABLE"]=="Y" && $prop["PROPERTY_TYPE"]!="F")
		$arFindFields["p".$prop["ID"]] = $prop["NAME"];

$oFilter = new CAdminFilter($sTableID."_filter", $arFindFields);

$oFilter->Begin();

?>
<script type="text/javascript">
function SelEl(id, name)
{
<?
	if ('' != $lookup)
	{
		if ('' != $m)
		{
			?>window.opener.<? echo $lookup; ?>.AddValue(id);<?
		}
		else
		{
			?>
	window.opener.<? echo $lookup; ?>.AddValue(id);
	window.close();<?
		}
	}
	else
	{
		?><?if($m):?>
	window.opener.InS<? echo md5($n)?>(id, name);
	<?else:?>
	el = window.opener.document.getElementById('<?echo $n?>[<?echo $k?>]');
	if(!el)
		el = window.opener.document.getElementById('<?echo $n?>');
	if(el)
	{
		el.value = id;
		if (window.opener.BX)
			window.opener.BX.fireEvent(el, 'change');
	}
	el = window.opener.document.getElementById('sp_<?echo md5($n)?>_<?echo $k?>');
	if(!el)
		el = window.opener.document.getElementById('sp_<?echo $n?>');
	if(!el)
		el = window.opener.document.getElementById('<?echo $n?>_link');
	if(el)
		el.innerHTML = name;
	window.close();
		<?endif;?><?
	}
	?>
}

function SelAll()
{
	var frm = document.getElementById('form_<?echo $sTableID?>');
	if(frm)
	{
		var e = frm.elements['ID[]'];
		if(e && e.nodeName)
		{
			var v = e.value;
			var n = document.getElementById('name_'+v).value;
			SelEl(v, n);
		}
		else if(e)
		{
			var l = e.length;
			for(i=0;i<l;i++)
			{
				var a = e[i].checked;
				if (a == true)
				{
					var v = e[i].value;
					var n = document.getElementById('name_'+v).value;
					SelEl(v, n);
				}
			}
		}
		window.close();
	}
}
</script>
	<tr>
		<td><b><?echo GetMessage("IBLOCK_ELSEARCH_IBLOCK")?></b></td>
		<td><?echo GetIBlockDropDownList($IBLOCK_ID, "filter_type", "filter_iblock_id");?></td>
	</tr>

	<tr>
		<td><?echo GetMessage("IBLOCK_ELSEARCH_FROMTO_ID")?></td>
		<td>
			<nobr>
			<input type="text" name="filter_id_start" size="10" value="<?echo htmlspecialcharsex($filter_id_start)?>">
			...
			<input type="text" name="filter_id_end" size="10" value="<?echo htmlspecialcharsex($filter_id_end)?>">
			</nobr>
		</td>
	</tr>

	<tr>
		<td  nowrap><? echo GetMessage("IBLOCK_FIELD_TIMESTAMP_X").":"?></td>
		<td nowrap><? echo CalendarPeriod("filter_timestamp_from", htmlspecialcharsex($filter_timestamp_from), "filter_timestamp_to", htmlspecialcharsex($filter_timestamp_to), "form1")?></td>
	</tr>

	<tr>
		<td nowrap><?=GetMessage("IBLOCK_FIELD_MODIFIED_BY")?>:</td>
		<td nowrap><input type="text" name="filter_modified_user_id" value="<?echo htmlspecialcharsex($filter_modified_user_id)?>" size="3">&nbsp;<?
		$gr_res = CIBlock::GetGroupPermissions($IBLOCK_ID);
		$res = Array(1);
		foreach($gr_res as $gr=>$perm)
			if($perm>"R")
				$res[] = $gr;
		$res = CUser::GetList($byx="NAME", $orderx="ASC", Array("GROUP_MULTI"=>$res));
		?><select name="filter_modified_by">
		<option value=""><?echo GetMessage("IBLOCK_VALUE_ANY")?></option><?
		while($arr = $res->Fetch())
			echo "<option value='".$arr["ID"]."'".($filter_modified_by==$arr["ID"]?" selected":"").">(".htmlspecialcharsex($arr["LOGIN"].") ".$arr["NAME"]." ".$arr["LAST_NAME"])."</option>";
		?></select>
		</td>
	</tr>
	<?if(CModule::IncludeModule("workflow")):?>
	<tr>
		<td nowrap><?=GetMessage("IBLOCK_FIELD_STATUS")?>:</td>
		<td nowrap><input type="text" name="filter_status_id" value="<?echo htmlspecialcharsex($filter_status_id)?>" size="3">
		<select name="filter_status">
		<option value=""><?=GetMessage("IBLOCK_VALUE_ANY")?></option>
		<?
		$rs = CWorkflowStatus::GetDropDownList("Y");
		while($arRs = $rs->GetNext())
		{
			?><option value="<?=$arRs["REFERENCE_ID"]?>"<?if($filter_status == $arRs["~REFERENCE_ID"])echo " selected"?>><?=$arRs["REFERENCE"]?></option><?
		}
		?>
		</select></td>
	</tr>
	<?endif?>

	<?if(is_array($arIBTYPE) && ($arIBTYPE["SECTIONS"] == "Y")):?>
	<tr>
		<td nowrap><?echo GetMessage("IBLOCK_FIELD_SECTION_ID")?>:</td>
		<td nowrap>
			<select name="filter_section">
				<option value=""><?echo GetMessage("IBLOCK_VALUE_ANY")?></option>
				<option value="0"<?if($filter_section=="0")echo" selected"?>><?echo GetMessage("IBLOCK_UPPER_LEVEL")?></option>
				<?
				$bsections = CIBlockSection::GetTreeList(Array("IBLOCK_ID"=>$IBLOCK_ID));
				while($arSection = $bsections->GetNext()):
					?><option value="<?echo $arSection["ID"]?>"<?if($arSection["ID"]==$filter_section)echo " selected"?>><?echo str_repeat("&nbsp;.&nbsp;", $arSection["DEPTH_LEVEL"])?><?echo $arSection["NAME"]?></option><?
				endwhile;
				?>
			</select><br>

			<input type="checkbox" name="filter_subsections" value="Y"<?if($filter_subsections=="Y")echo" checked"?>> <?echo GetMessage("IBLOCK_ELSEARCH_INCLUDING_SUBSECTIONS")?>

		</td>
	</tr>
	<?endif?>

	<tr>
		<td nowrap><?echo GetMessage("IBLOCK_FIELD_ACTIVE")?>:</td>
		<td nowrap>
			<select name="filter_active">
				<option value=""><?=htmlspecialcharsex(GetMessage('IBLOCK_VALUE_ANY'))?></option>
				<option value="Y"<?if($filter_active=="Y")echo " selected"?>><?=htmlspecialcharsex(GetMessage("IBLOCK_YES"))?></option>
				<option value="N"<?if($filter_active=="N")echo " selected"?>><?=htmlspecialcharsex(GetMessage("IBLOCK_NO"))?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td nowrap><?echo GetMessage("IBLOCK_FIELD_NAME")?>:</td>
		<td nowrap>
			<input type="text" name="filter_name" value="<?echo htmlspecialcharsex($filter_name)?>" size="30">
		</td>
	</tr>
	<tr>
		<td nowrap><?echo GetMessage("IBLOCK_ELSEARCH_DESC")?></td>
		<td nowrap>
			<input type="text" name="filter_intext" size="50" value="<?echo htmlspecialcharsex($filter_intext)?>" size="30">&nbsp;<?=ShowFilterLogicHelp()?>
		</td>
	</tr>
	<?
	foreach($arProps as $prop):
		if($prop["FILTRABLE"]!="Y" || $prop["PROPERTY_TYPE"]=="F")
			continue;
	?>
	<tr>
		<td><?=$prop["NAME"]?>:</td>
		<td>
			<?if(array_key_exists("GetAdminFilterHTML", $prop["PROPERTY_USER_TYPE"])):
			echo call_user_func_array($prop["PROPERTY_USER_TYPE"]["GetAdminFilterHTML"], array(
				$prop,
				array("VALUE" => "find_el_property_".$prop["ID"]),
			));
			elseif($prop["PROPERTY_TYPE"]=='L'):?>
				<select name="find_el_property_<?=$prop["ID"]?>">
					<option value=""><?echo GetMessage("IBLOCK_VALUE_ANY")?></option><?
					$dbrPEnum = CIBlockPropertyEnum::GetList(Array("SORT"=>"ASC", "NAME"=>"ASC"), Array("PROPERTY_ID"=>$prop["ID"]));
					while($arPEnum = $dbrPEnum->GetNext()):
					?>
						<option value="<?=$arPEnum["ID"]?>"<?if(${"find_el_property_".$prop["ID"]} == $arPEnum["ID"])echo " selected"?>><?=$arPEnum["VALUE"]?></option>
					<?
					endwhile;
			?></select>
			<?
			elseif($prop["PROPERTY_TYPE"]=='G'):
				_ShowGroupPropertyField('find_el_property_'.$prop["ID"], $prop, ${'find_el_property_'.$prop["ID"]});
			else:
				?>
				<input type="text" name="find_el_property_<?=$prop["ID"]?>" value="<?echo htmlspecialcharsex(${"find_el_property_".$prop["ID"]})?>" size="30">&nbsp;<?=ShowFilterLogicHelp()?>
				<?
			endif;
			?>
		</td>
	</tr>
	<?endforeach;?>

<?
$oFilter->Buttons(array(
	"url" => "/bitrix/admin/iblock_element_search.php?lang=".LANGUAGE_ID."&get_xml_id=".($get_xml_id? "Y": "N")."&k=".urlencode($k)."&n=".urlencode($n)."&m=".($m? "y": "n"),
	"table_id" => $sTableID,
));
?>

<?$oFilter->End();?>
</form>

<?
$lAdmin->DisplayList();

echo ShowError($strWarning);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");
?>