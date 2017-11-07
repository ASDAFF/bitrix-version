<?
if(!defined("CACHED_b_iblock_type")) define("CACHED_b_iblock_type", 36000);
if(!defined("CACHED_b_iblock")) define("CACHED_b_iblock", 36000);
if(!defined("CACHED_b_iblock_count")) define("CACHED_b_iblock_count", 300);
if(!defined("CACHED_b_iblock_bucket_size")) define("CACHED_b_iblock_bucket_size", 20);
if(!defined("CACHED_b_iblock_property_enum")) define("CACHED_b_iblock_property_enum", 36000);
if(!defined("CACHED_b_iblock_property_enum_bucket_size")) define("CACHED_b_iblock_property_enum_bucket_size", 100);

global $DBType;
$arClasses = array(
	"iblock" => "install/index.php",
	"CIBlockPropertyResult" => "classes/general/iblockpropresult.php",
	"CIBlockResult" => "classes/general/iblockresult.php",
	"_CIBElement" => "classes/general/iblock_element.php",
	"CIBlockType" => "classes/general/iblocktype.php",
	"CAllIBlock" => "classes/general/iblock.php",
	"CIBlock" => "classes/".$DBType."/iblock.php",
	"CAllIBlockSection" => "classes/general/iblocksection.php",
	"CIBlockSection" => "classes/".$DBType."/iblocksection.php",
	"CAllIBlockProperty" => "classes/general/iblockproperty.php",
	"CIBlockPropertyEnum" => "classes/general/iblockpropertyenum.php",
	"CIBlockProperty" => "classes/".$DBType."/iblockproperty.php",
	"CAllIBlockElement" => "classes/general/iblockelement.php",
	"CIBlockElement" => "classes/".$DBType."/iblockelement.php",
	"CAllIBlockRSS" => "classes/general/iblockrss.php",
	"CIBlockRSS" => "classes/".$DBType."/iblockrss.php",
	"CIBlockPropertyDateTime" => "classes/general/prop_datetime.php",
	"CIBlockPropertyXmlID" => "classes/general/prop_xmlid.php",
	"CIBlockPropertyFileMan" => "classes/general/prop_fileman.php",
	"CIBlockPropertyHTML" => "classes/general/prop_html.php",
	"CIBlockPropertyElementList" => "classes/general/prop_element_list.php",
	"CIBlockXMLFile" => "classes/".$DBType."/cml2.php",
	"CIBlockCMLImport" => "classes/general/cml2.php",
	"CIBlockCMLExport" => "classes/general/cml2.php",
	"CIBlockFindTools" => "classes/general/comp_findtools.php",
	"CIBlockPriceTools" => "classes/general/comp_pricetools.php",
	"CIBlockParameters" => "classes/general/comp_parameters.php",
	"CIBlockFormatProperties" => "classes/general/comp_formatprops.php",
	"CIBlockSequence" => "classes/".$DBType."/iblocksequence.php",
	"CIBlockPropertySequence" => "classes/general/prop_seq.php",
	"CIBlockPropertyElementAutoComplete" => "classes/general/prop_element_auto.php",
	"CIBlockPropertySKU" => "classes/general/prop_element_sku.php",
	"CAllIBlockOffersTmp" => "classes/general/iblockoffers.php",
	"CIBlockOffersTmp" => "classes/".$DBType."/iblockoffers.php",
	"CEventIblock" => "classes/general/iblock_event_list.php",
	"CRatingsComponentsIBlock" => "classes/general/ratings_components.php",
	"CIBlockRights" => "classes/general/iblock_rights.php",
	"CIBlockSectionRights" => "classes/general/iblock_rights.php",
	"CIBlockElementRights" => "classes/general/iblock_rights.php",
	"CIBlockRightsStorage" => "classes/general/iblock_rights.php",
	"Bitrix\\Iblock\\IblockTable" => "lib/iblock.php",
	"Bitrix\\Iblock\\ElementTable" => "lib/element.php",
	"Bitrix\\Iblock\\SectionElementTable" => "lib/sectionelement.php",
	"Bitrix\\Iblock\\SectionTable" => "lib/section.php",
	"Bitrix\\Iblock\\SiteTable" => "lib/site.php",
	"CIBlockSectionPropertyLink" => "classes/general/section_property.php",
);

if(IsModuleInstalled('bizproc'))
{
	$arClasses["CIBlockDocument"] = "classes/general/iblockdocument.php";
}

CModule::AddAutoloadClasses("iblock", $arClasses);

IncludeModuleLangFile(__FILE__);

/*********************************************
Public helper functions
*********************************************/
function GetIBlockListWithCnt($type, $arTypesInc = array(), $arTypesExc = array(), $arOrder = array(
	"SORT" => "ASC",
), $cnt = 0)
{
	if (!is_array($arTypesInc))
	{
		$arTypesInc = array(
			$arTypesInc,
		);
	}

	$arIDsInc = array();
	$arCODEsInc = array();
	foreach ($arTypesInc as $i)
	{
		if (intval($i) > 0)
			$arIDsInc[] = $i;
		else
			$arCODEsInc[] = $i;
	}

	if (!is_array($arTypesExc))
	{
		$arTypesExc = array(
			$arTypesExc,
		);
	}

	$arIDsExc = array();
	$arCODEsExc = array();
	foreach ($arTypesExc as $i)
	{
		if (intval($i) > 0)
			$arIDsExc[] = $i;
		else
			$arCODEsExc[] = $i;
	}

	$res = CIBlock::GetList($arOrder, array(
		"type" => $type,
		"LID" => LANG,
		"ACTIVE" => "Y",
		"ID" => $arIDsInc,
		"CNT_ACTIVE" => "Y",
		"CODE" => $arCODEsInc,
		"!ID" => $arIDsExc,
		"!CODE" => $arCODEsExc,
	), true);

	$dbr = new CIBlockResult($res);
	if ($cnt > 0)
		$dbr->NavStart($cnt);

	return $dbr;
}

function GetIBlockList($type, $arTypesInc = Array(), $arTypesExc = Array(), $arOrder=Array("SORT"=>"ASC"), $cnt=0)
{
	return GetIBlockListLang(LANG, $type, $arTypesInc, $arTypesExc, $arOrder, $cnt);
}

function GetIBlockListLang($lang, $type, $arTypesInc = array(), $arTypesExc = array(), $arOrder = array(
	"SORT" => "ASC",
), $cnt = 0)
{
	if (!is_array($arTypesInc))
	{
		$arTypesInc = array(
			$arTypesInc,
		);
	}

	$arIDsInc = array();
	$arCODEsInc = array();
	foreach ($arTypesInc as $i)
	{
		if (IntVal($i) > 0)
			$arIDsInc[] = $i;
		else
			$arCODEsInc[] = $i;
	}

	if (!is_array($arTypesExc))
	{
		$arTypesExc = array(
			$arTypesExc,
		);
	}

	$arIDsExc = array();
	$arCODEsExc = array();
	foreach ($arTypesExc as $i)
	{
		if (intval($arTypesExc[$i]) > 0)
			$arIDsExc[] = $i;
		else
			$arCODEsExc[] = $i;
	}

	$res = CIBlock::GetList($arOrder, array(
		"type" => $type,
		"LID" => $lang,
		"ACTIVE" => "Y",
		"ID" => $arIDsInc,
		"CODE" => $arCODEsInc,
		"!ID" => $arIDsExc,
		"!CODE" => $arCODEsExc,
	));

	$dbr = new CIBlockResult($res);
	if ($cnt > 0)
		$dbr->NavStart($cnt);

	return $dbr;
}

function GetIBlock($ID, $type="")
{
	return GetIBlockLang(LANG, $ID, $type);
}

function GetIBlockLang($lang, $ID, $type="")
{
	$res = CIBlock::GetList(Array("sort"=>"asc"), Array("ID"=>IntVal($ID), "TYPE"=>$type, "LID"=>$lang, "ACTIVE"=>"Y"));
	$res = new CIBlockResult($res);
	return $arRes = $res->GetNext();
}

/**************************
Elements helper functions
**************************/
function GetIBlockElementListEx($type, $arTypesInc=Array(), $arTypesExc=Array(), $arOrder=Array("sort"=>"asc"), $cnt=0, $arFilter = Array(), $arSelect=Array(), $arGroupBy=false)
{
	return GetIBlockElementListExLang(LANG, $type, $arTypesInc, $arTypesExc, $arOrder, $cnt, $arFilter, $arSelect, $arGroupBy);
}

function GetIBlockElementCountEx($type, $arTypesInc=Array(), $arTypesExc=Array(), $arOrder=Array("sort"=>"asc"), $cnt=0, $arFilter = Array())
{
	return GetIBlockElementCountExLang(LANG, $type, $arTypesInc, $arTypesExc, $arOrder, $cnt, $arFilter);
}

function GetIBlockElementListExLang($lang, $type, $arTypesInc=Array(), $arTypesExc=Array(), $arOrder=Array("sort"=>"asc"), $cnt=0, $arFilter = Array(), $arSelect=Array(), $arGroupBy=false)
{
	$filter = _GetIBlockElementListExLang_tmp($lang, $type, $arTypesInc, $arTypesExc, $arOrder, $cnt, $arFilter);
	if(is_array($cnt))
		$arNavParams = $cnt; //Array("nPageSize"=>$cnt, "bShowAll"=>false);
	elseif($cnt>0)
		$arNavParams = Array("nPageSize"=>$cnt);
	else
		$arNavParams = false;

	$dbr = CIBlockElement::GetList($arOrder, $filter, $arGroupBy, $arNavParams, $arSelect);
	if(!is_array($cnt) && $cnt>0)
		$dbr->NavStart($cnt);

	return $dbr;
}

function GetIBlockElementCountExLang($lang, $type, $arTypesInc=Array(), $arTypesExc=Array(), $arOrder=Array("sort"=>"asc"), $cnt=0, $arFilter = Array())
{
	$filter = _GetIBlockElementListExLang_tmp($lang, $type, $arTypesInc, $arTypesExc, $arOrder, $cnt, $arFilter);
	return CIBlockElement::GetList($arOrder, $filter, true);
}

function _GetIBlockElementListExLang_tmp($lang, $type, $arTypesInc = array(), $arTypesExc = array(), $arOrder = array(
	"sort" => "asc",
), $cnt = 0, $arFilter = array(), $arSelect = array())
{
	if (!is_array($arTypesInc))
	{
		if ($arTypesInc !== false)
			$arTypesInc = array(
				$arTypesInc,
			);
		else
			$arTypesInc = array();
	}
	$arIDsInc = array();
	$arCODEsInc = array();
	foreach ($arTypesInc as $i)
	{
		if (intval($i) > 0)
			$arIDsInc[] = $i;
		else
			$arCODEsInc[] = $i;
	}

	if (!is_array($arTypesExc))
	{
		if ($arTypesExc !== false)
			$arTypesExc = array(
				$arTypesExc,
			);
		else
			$arTypesExc = array();
	}
	$arIDsExc = array();
	$arCODEsExc = array();
	foreach ($arTypesExc as $i)
	{
		if (intval($i) > 0)
			$arIDsExc[] = $i;
		else
			$arCODEsExc[] = $i;
	}

	$filter = array(
		"IBLOCK_ID" => $arIDsInc,
		"IBLOCK_LID" => $lang,
		"IBLOCK_ACTIVE" => "Y",
		"IBLOCK_CODE" => $arCODEsInc,
		"!IBLOCK_ID" => $arIDsExc,
		"!IBLOCK_CODE" => $arCODEsExc,
		"ACTIVE_DATE" => "Y",
		"ACTIVE" => "Y",
		"CHECK_PERMISSIONS" => "Y",
	);
	if ($type != false && strlen($type) > 0)
		$filter["IBLOCK_TYPE"] = $type;

	if (is_array($arFilter) && count($arFilter) > 0)
		$filter = array_merge($filter, $arFilter);

	return $filter;
}

function GetIBlockElementCount($IBLOCK, $SECT_ID=false, $arOrder=Array("sort"=>"asc"), $cnt=0)
{
	$filter = Array("IBLOCK_ID"=>IntVal($IBLOCK), "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y", "CHECK_PERMISSIONS"=>"Y");
	if($SECT_ID!==false)
		$filter["SECTION_ID"]=IntVal($SECT_ID);

	return CIBlockElement::GetList($arOrder, $filter, true);
}

function GetIBlockElementList($IBLOCK, $SECT_ID=false, $arOrder=Array("sort"=>"asc"), $cnt=0, $arFilter=array(), $arSelect=array())
{
	$filter = Array("IBLOCK_ID"=>IntVal($IBLOCK), "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y", "CHECK_PERMISSIONS"=>"Y");
	if($SECT_ID!==false)
		$filter["SECTION_ID"]=IntVal($SECT_ID);

	if (is_array($arFilter) && count($arFilter)>0)
		$filter = array_merge($filter, $arFilter);

	$dbr = CIBlockElement::GetList($arOrder, $filter, false, false, $arSelect);
	if($cnt>0)
		$dbr->NavStart($cnt);

	return $dbr;
}

function GetIBlockElement($ID, $TYPE="")
{
	$filter = Array("ID"=>IntVal($ID), "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y", "CHECK_PERMISSIONS"=>"Y");
	if($TYPE!="")
		$filter["IBLOCK_TYPE"]=$TYPE;

	$iblockelement = CIBlockElement::GetList(Array(), $filter);
	if($obIBlockElement = $iblockelement->GetNextElement())
	{
		$arIBlockElement = $obIBlockElement->GetFields();
		if($arIBlock = GetIBlock($arIBlockElement["IBLOCK_ID"], $TYPE))
		{
			$arIBlockElement["IBLOCK_ID"] = $arIBlock["ID"];
			$arIBlockElement["IBLOCK_NAME"] = $arIBlock["NAME"];
			$arIBlockElement["~IBLOCK_NAME"] = $arIBlock["~NAME"];
			$arIBlockElement["PROPERTIES"] = $obIBlockElement->GetProperties();
			return $arIBlockElement;
		}
	}

	return false;
}

/******************************
Sections functions
******************************/
function GetIBlockSectionListWithCnt($IBLOCK, $SECT_ID=false, $arOrder = Array("left_margin"=>"asc"), $cnt=0, $arFilter=Array())
{
	$filter = Array("IBLOCK_ID"=>IntVal($IBLOCK), "ACTIVE"=>"Y", "CNT_ACTIVE"=>"Y");
	if($SECT_ID!==false)
		$filter["SECTION_ID"]=IntVal($SECT_ID);

	if(is_array($arFilter) && count($arFilter)>0)
		$filter = array_merge($filter, $arFilter);

	$dbr = CIBlockSection::GetList($arOrder, $filter, true);
	if($cnt>0)
		$dbr->NavStart($cnt);

	return $dbr;
}

function GetIBlockSectionList($IBLOCK, $SECT_ID=false, $arOrder = Array("left_margin"=>"asc"), $cnt=0, $arFilter=Array())
{
	$filter = Array("IBLOCK_ID"=>IntVal($IBLOCK), "ACTIVE"=>"Y", "IBLOCK_ACTIVE"=>"Y");
	if($SECT_ID!==false)
		$filter["SECTION_ID"]=IntVal($SECT_ID);

	if(is_array($arFilter) && count($arFilter)>0)
		$filter = array_merge($filter, $arFilter);

	$dbr = CIBlockSection::GetList($arOrder, $filter);
	if($cnt>0)
		$dbr->NavStart($cnt);

	return $dbr;
}

function GetIBlockSection($ID, $TYPE="")
{
	$ID = intval($ID);
	if($ID>0)
	{
		$iblocksection = CIBlockSection::GetList(Array(), Array("ID"=>$ID, "ACTIVE"=>"Y"));
		if($arIBlockSection = $iblocksection->GetNext())
		{
			if($arIBlock = GetIBlock($arIBlockSection["IBLOCK_ID"], $TYPE))
			{
				$arIBlockSection["IBLOCK_ID"] = $arIBlock["ID"];
				$arIBlockSection["IBLOCK_NAME"] = $arIBlock["NAME"];
				return $arIBlockSection;
			}
		}
	}
	return false;
}

function GetIBlockSectionPath($IBLOCK, $SECT_ID)
{
	return CIBlockSection::GetNavChain(IntVal($IBLOCK), IntVal($SECT_ID));
}

/***************************************************************
* RSS
***************************************************************/
function xmlize_rss($data)
{
	$data = trim($data);
	$vals = $index = $array = array();
	$parser = xml_parser_create();
	xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
	xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
	xml_parse_into_struct($parser, $data, $vals, $index);
	xml_parser_free($parser);

	$i = 0;

	$tagname = $vals[$i]['tag'];
	if (isset($vals[$i]['attributes']))
		$array[$tagname]['@'] = $vals[$i]['attributes'];
	else
		$array[$tagname]['@'] = array();

	$array[$tagname]["#"] = xml_depth_rss($vals, $i);

	return $array;
}

function xml_depth_rss($vals, &$i)
{
	$children = array();

	if (isset($vals[$i]['value']))
		array_push($children, $vals[$i]['value']);

	while (++$i < count($vals))
	{
		switch ($vals[$i]['type'])
		{
			case 'open':
				if (isset($vals[$i]['tag']))
					$tagname = $vals[$i]['tag'];
				else
					$tagname = '';

				if (isset($children[$tagname]))
					$size = sizeof($children[$tagname]);
				else
					$size = 0;

				if (isset($vals[$i]['attributes']))
					$children[$tagname][$size]['@'] = $vals[$i]["attributes"];

				$children[$tagname][$size]['#'] = xml_depth_rss($vals, $i);
			break;

			case 'cdata':
				array_push($children, $vals[$i]['value']);
			break;

			case 'complete':
				$tagname = $vals[$i]['tag'];

				if(isset($children[$tagname]))
					$size = sizeof($children[$tagname]);
				else
					$size = 0;

				if(isset($vals[$i]['value']))
					$children[$tagname][$size]["#"] = $vals[$i]['value'];
				else
					$children[$tagname][$size]["#"] = '';

				if (isset($vals[$i]['attributes']))
					$children[$tagname][$size]['@'] = $vals[$i]['attributes'];
			break;

			case 'close':
				return $children;
			break;
		}

	}

	return $children;
}

function GetIBlockDropDownList($IBLOCK_ID, $strTypeName, $strIBlockName, $arFilter = false, $strAddType = '', $strAddIBlock = '')
{
	$html = '';

	static $arTypes = false;
	static $arIBlocks = false;

	if(!$arTypes)
	{
		$arTypes = array(''=>GetMessage("IBLOCK_CHOOSE_IBLOCK_TYPE"));
		$arIBlocks = array(''=>array(''=>GetMessage("IBLOCK_CHOOSE_IBLOCK")));

		if(!is_array($arFilter))
			$arFilter = array();
		$arFilter["MIN_PERMISSION"] = "W";

		$rsIBlocks = CIBlock::GetList(array("IBLOCK_TYPE" => "ASC", "NAME" => "ASC"), $arFilter);
		while($arIBlock = $rsIBlocks->Fetch())
		{
			if(!array_key_exists($arIBlock["IBLOCK_TYPE_ID"], $arTypes))
			{
				$arType = CIBlockType::GetByIDLang($arIBlock["IBLOCK_TYPE_ID"], LANG);
				$arTypes[$arType["~ID"]] = $arType["~NAME"]." [".$arType["~ID"]."]";
				$arIBlocks[$arType["~ID"]] = array(''=>GetMessage("IBLOCK_CHOOSE_IBLOCK"));
			}
			$arIBlocks[$arIBlock["IBLOCK_TYPE_ID"]][$arIBlock["ID"]] = $arIBlock["NAME"]." [".$arIBlock["ID"]."]";
		}

		$html .= '
		<script language="JavaScript">
		function OnTypeChanged(typeSelect, iblockSelectID)
		{
			var arIBlocks = '.CUtil::PhpToJSObject($arIBlocks).';
			var iblockSelect = document.getElementById(iblockSelectID);
			if(iblockSelect)
			{
				for(var i=iblockSelect.length-1; i >= 0; i--)
					iblockSelect.remove(i);
				var n = 0;
				for(var j in arIBlocks[typeSelect.value])
				{
					var newoption = new Option(arIBlocks[typeSelect.value][j], j, false, false);
					iblockSelect.options[n]=newoption;
					n++;
				}
			}
		}
		</script>
		';
	}

	$IBLOCK_TYPE = false;
	if($IBLOCK_ID > 0)
	{
		foreach($arIBlocks as $iblock_type_id => $iblocks)
		{
			if(array_key_exists($IBLOCK_ID, $iblocks))
			{
				$IBLOCK_TYPE = $iblock_type_id;
				break;
			}
		}
	}

	$strAddType = trim($strAddType);
	$strAddIBlock = trim($strAddIBlock);

	$html .= '<select name="'.htmlspecialcharsbx($strTypeName).'" id="'.htmlspecialcharsbx($strTypeName).'" OnChange="'.htmlspecialcharsbx('OnTypeChanged(this, \''.CUtil::JSEscape($strIBlockName).'\')').'"'.($strAddType != '' ? ' '.$strAddType : '').'>'."\n";
	foreach($arTypes as $key => $value)
	{
		if($IBLOCK_TYPE === false)
			$IBLOCK_TYPE = $key;
		$html .= '<option value="'.htmlspecialcharsbx($key).'"'.($IBLOCK_TYPE===$key? ' selected': '').'>'.htmlspecialcharsbx($value).'</option>'."\n";
	}
	$html .= "</select>\n";

	$html .= "&nbsp;\n";

	$html .= '<select name="'.htmlspecialcharsbx($strIBlockName).'" id="'.htmlspecialcharsbx($strIBlockName).'"'.($strAddIBlock != '' ? ' '.$strAddIBlock : '').'>'."\n";
	foreach($arIBlocks[$IBLOCK_TYPE] as $key => $value)
	{
		$html .= '<option value="'.htmlspecialcharsbx($key).'"'.($IBLOCK_ID==$key? ' selected': '').'>'.htmlspecialcharsbx($value).'</option>'."\n";
	}
	$html .= "</select>\n";

	return $html;
}

function GetIBlockDropDownListEx($IBLOCK_ID, $strTypeName, $strIBlockName, $arFilter = false, $onChangeType = '', $onChangeIBlock = '', $strAddType = '', $strAddIBlock = '')
{
	$html = '';

	static $arTypes = false;
	static $arIBlocks = false;

	if(!$arTypes)
	{
		$arTypes = array(0 => GetMessage("IBLOCK_CHOOSE_IBLOCK_TYPE"));
		$arIBlocks = array(0 => array(''=>GetMessage("IBLOCK_CHOOSE_IBLOCK")));

		if(!is_array($arFilter))
			$arFilter = array();
		if (!array_key_exists('MIN_PERMISSION',$arFilter) || trim($arFilter['MIN_PERMISSION']) == '')
			$arFilter["MIN_PERMISSION"] = "W";

		$rsIBlocks = CIBlock::GetList(array("IBLOCK_TYPE" => "ASC", "NAME" => "ASC"), $arFilter);
		while($arIBlock = $rsIBlocks->Fetch())
		{
			if(!array_key_exists($arIBlock["IBLOCK_TYPE_ID"], $arTypes))
			{
				$arType = CIBlockType::GetByIDLang($arIBlock["IBLOCK_TYPE_ID"], LANG);
				$arTypes[$arType["~ID"]] = $arType["~NAME"]." [".$arType["~ID"]."]";
				$arIBlocks[$arType["~ID"]] = array(0 => GetMessage("IBLOCK_CHOOSE_IBLOCK"));
			}
			$arIBlocks[$arIBlock["IBLOCK_TYPE_ID"]][$arIBlock["ID"]] = $arIBlock["NAME"]." [".$arIBlock["ID"]."]";
		}

		$html .= '
		<script type="text/javascript">
		function OnTypeExtChanged(typeSelect, iblockSelectID)
		{
			var arIBlocks = '.CUtil::PhpToJSObject($arIBlocks).';
			var iblockSelect = BX(iblockSelectID);
			if(iblockSelect)
			{
				for(var i=iblockSelect.length-1; i >= 0; i--)
					iblockSelect.remove(i);
				var n = 0;
				for(var j in arIBlocks[typeSelect.value])
				{
					var newoption = new Option(arIBlocks[typeSelect.value][j], j, false, false);
					iblockSelect.options.add(newoption);
					n++;
				}
			}
		}
		</script>
		';
	}

	$IBLOCK_TYPE = false;
	if($IBLOCK_ID > 0)
	{
		foreach($arIBlocks as $iblock_type_id => $iblocks)
		{
			if(array_key_exists($IBLOCK_ID, $iblocks))
			{
				$IBLOCK_TYPE = $iblock_type_id;
				break;
			}
		}
	}

	$onChangeType = trim($onChangeType);
	if ($onChangeType != '')
	{
		if (substr($onChangeType,-1) != ';')
			$onChangeType .= ';';
		$onChangeType = 'OnTypeExtChanged(this, \''.CUtil::JSEscape($strIBlockName).'\'); '.$onChangeType;
	}
	else
	{
		$onChangeType = 'OnTypeExtChanged(this, \''.CUtil::JSEscape($strIBlockName).'\');';
	}
	$onChangeIBlock = trim($onChangeIBlock);
	$strAddType = trim($strAddType);
	$strAddIBlock = trim($strAddIBlock);

	$html .= '<select name="'.htmlspecialcharsbx($strTypeName).'" id="'.htmlspecialcharsbx($strTypeName).'" onchange="'.htmlspecialcharsbx($onChangeType).'"'.($strAddType != '' ? ' '.$strAddType : '').'>'."\n";
	foreach($arTypes as $key => $value)
	{
		if($IBLOCK_TYPE === false)
			$IBLOCK_TYPE = $key;
		$html .= '<option value="'.htmlspecialcharsbx($key).'"'.($IBLOCK_TYPE===$key? ' selected': '').'>'.htmlspecialcharsbx($value).'</option>'."\n";
	}
	$html .= "</select>\n";

	$html .= "&nbsp;\n";

	$html .= '<select name="'.htmlspecialcharsbx($strIBlockName).'" id="'.htmlspecialcharsbx($strIBlockName).'"'.($onChangeIBlock != '' ? ' onchange="'.$onChangeIBlock.'"' : '').($strAddIBlock != '' ? ' '.$strAddIBlock : '').'>'."\n";
	foreach($arIBlocks[$IBLOCK_TYPE] as $key => $value)
	{
		$html .= '<option value="'.htmlspecialcharsbx($key).'"'.($IBLOCK_ID==$key? ' selected': '').'>'.htmlspecialcharsbx($value).'</option>'."\n";
	}
	$html .= "</select>\n";

	return $html;
}

function ImportXMLFile($file_name, $iblock_type="-", $site_id=false, $section_action="D", $element_action="D", $use_crc=false, $preview=false, $sync=false, $return_last_error=false)
{
	global $APPLICATION;

	$ABS_FILE_NAME = false;
	$WORK_DIR_NAME = false;
	if(strlen($file_name)>0)
	{
		if(
			file_exists($file_name)
			&& is_file($file_name)
			&& (
				substr($file_name, -4) === ".xml"
				|| substr($file_name, -7) === ".tar.gz"
			)
		)
		{
			$ABS_FILE_NAME = $file_name;

		}
		else
		{
			$filename = trim(str_replace("\\", "/", trim($file_name)), "/");
			$FILE_NAME = rel2abs($_SERVER["DOCUMENT_ROOT"], "/".$filename);
			if((strlen($FILE_NAME) > 1) && ($FILE_NAME === "/".$filename) && ($APPLICATION->GetFileAccessPermission($FILE_NAME) >= "W"))
			{
				$ABS_FILE_NAME = $_SERVER["DOCUMENT_ROOT"].$FILE_NAME;
			}
		}
	}

	if(!$ABS_FILE_NAME)
		return GetMessage("IBLOCK_XML2_FILE_ERROR");

	$WORK_DIR_NAME = substr($ABS_FILE_NAME, 0, strrpos($ABS_FILE_NAME, "/")+1);

	if(substr($ABS_FILE_NAME, -7) == ".tar.gz")
	{
		include_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/tar_gz.php");
		$obArchiver = new CArchiver($ABS_FILE_NAME);
		if(!$obArchiver->ExtractFiles($WORK_DIR_NAME))
		{
			$strError = "";
			if(is_object($APPLICATION))
			{
				$arErrors = $obArchiver->GetErrors();
				if(count($arErrors))
				{
					foreach($arErrors as $error)
						$strError .= $error[1]."<br>";
				}
			}
			if($strError != "")
				return $strError;
			else
				return GetMessage("IBLOCK_XML2_FILE_ERROR");
		}
		$IMP_FILE_NAME = substr($ABS_FILE_NAME, 0, -7).".xml";
	}
	else
	{
		$IMP_FILE_NAME = $ABS_FILE_NAME;
	}

	$fp = fopen($IMP_FILE_NAME, "rb");
	if(!$fp)
		return GetMessage("IBLOCK_XML2_FILE_ERROR");

	if($sync)
		$table_name = "b_xml_tree_sync";
	else
		$table_name = "b_xml_tree";

	$NS = array("STEP"=>0);

	$obCatalog = new CIBlockCMLImport;
	$obCatalog->Init($NS, $WORK_DIR_NAME, $use_crc, $preview, false, false, false, $table_name);

	if($sync)
	{
		if(!$obCatalog->StartSession(bitrix_sessid()))
			return GetMessage("IBLOCK_XML2_TABLE_CREATE_ERROR");

		$obCatalog->ReadXMLToDatabase($fp, $NS, 0, 1024);

		$xml_root = $obCatalog->GetSessionRoot();
		$bUpdateIBlock = false;
	}
	else
	{
		$obCatalog->DropTemporaryTables();

		if(!$obCatalog->CreateTemporaryTables())
			return GetMessage("IBLOCK_XML2_TABLE_CREATE_ERROR");

		$obCatalog->ReadXMLToDatabase($fp, $NS, 0, 1024);

		if(!$obCatalog->IndexTemporaryTables())
			return GetMessage("IBLOCK_XML2_INDEX_ERROR");

		$xml_root = 1;
		$bUpdateIBlock = true;
	}

	fclose($fp);

	$result = $obCatalog->ImportMetaData($xml_root, $iblock_type, $site_id, $bUpdateIBlock);
	if($result !== true)
		return GetMessage("IBLOCK_XML2_METADATA_ERROR").implode("\n", $result);

	$obCatalog->ImportSections();
	$obCatalog->DeactivateSections($section_action);
	$obCatalog->SectionsResort();

	$obCatalog = new CIBlockCMLImport;
	$obCatalog->Init($NS, $WORK_DIR_NAME, $use_crc, $preview, false, false, false, $table_name);
	if($sync)
	{
		if(!$obCatalog->StartSession(bitrix_sessid()))
			return GetMessage("IBLOCK_XML2_TABLE_CREATE_ERROR");
	}
	$SECTION_MAP = false;
	$PRICES_MAP = false;
	$obCatalog->ReadCatalogData($SECTION_MAP, $PRICES_MAP);
	$result = $obCatalog->ImportElements(time(), 0);

	$obCatalog->DeactivateElement($element_action, time(), 0);
	if($sync)
		$obCatalog->EndSession();

	if($return_last_error)
	{
		if(strlen($obCatalog->LAST_ERROR))
			return $obCatalog->LAST_ERROR;
	}

	return true;
}

?>
