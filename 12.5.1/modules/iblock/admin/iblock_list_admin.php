<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/iblock.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/prolog.php");
IncludeModuleLangFile(__FILE__);
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/admin_lib.php");

global $USER;

$bBizproc = CModule::IncludeModule("bizproc");
$bWorkflow = CModule::IncludeModule("workflow");
$bFileman = CModule::IncludeModule("fileman");
$bExcel = isset($_REQUEST["mode"]) && ($_REQUEST["mode"] == "excel");

$bSearch = false;
$bCurrency = false;
$arCurrencyList = array();
$minImageSize = array("W" => 1, "H"=>1);
$maxImageSize = array("W" => 50, "H"=>50);

if($_REQUEST['mode']=='list' || $_REQUEST['mode']=='frame')
	CFile::DisableJSFunction(true);

$arIBTYPE = CIBlockType::GetByIDLang($type, LANG);
if($arIBTYPE===false)
	$APPLICATION->AuthForm(GetMessage("IBLIST_A_BAD_BLOCK_TYPE_ID"));

$IBLOCK_ID = IntVal($IBLOCK_ID);
$arIBlock = CIBlock::GetArrayByID($IBLOCK_ID);

if($arIBlock)
	$bBadBlock = !CIBlockRights::UserHasRightTo($IBLOCK_ID, $IBLOCK_ID, "iblock_admin_display");
else
	$bBadBlock = true;

if($bBadBlock)
{
	$APPLICATION->SetTitle($arIBTYPE["NAME"]);
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

	if($bBadBlock):
	?>
	<?echo ShowError(GetMessage("IBLIST_A_BAD_IBLOCK"));?>
	<a href="<?echo htmlspecialcharsbx("iblock_admin.php?lang=".urlencode(LANG)."&type=".urlencode($type))?>"><?echo GetMessage("IBLOCK_BACK_TO_ADMIN")?></a>
	<?
	endif;
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
	die();
}

if(!$arIBlock["SECTIONS_NAME"])
	$arIBlock["SECTIONS_NAME"] = $arIBTYPE["SECTION_NAME"]? $arIBTYPE["SECTION_NAME"]: GetMessage("IBLIST_A_SECTIONS");
if(!$arIBlock["ELEMENTS_NAME"])
	$arIBlock["ELEMENTS_NAME"] = $arIBTYPE["ELEMENT_NAME"]? $arIBTYPE["ELEMENT_NAME"]: GetMessage("IBLIST_A_ELEMENTS");

$arIBlock["SITE_ID"] = array();
$rsSites = CIBlock::GetSite($IBLOCK_ID);
while($arSite = $rsSites->Fetch())
	$arIBlock["SITE_ID"][] = $arSite["LID"];

$bWorkFlow = $bWorkflow && (CIBlock::GetArrayByID($IBLOCK_ID, "WORKFLOW") != "N");
$bBizproc = $bBizproc && (CIBlock::GetArrayByID($IBLOCK_ID, "BIZPROC") != "N");

define("MODULE_ID", "iblock");
define("ENTITY", "CIBlockDocument");
define("DOCUMENT_TYPE", "iblock_".$IBLOCK_ID);

$bCatalog = CModule::IncludeModule("catalog");
$boolSKU = false;
$boolSKUFiltrable = false;
$strSKUName = '';
$uniq_id = 0;

if($bCatalog)
{
	$arCatalog = CCatalog::GetByIDExt($arIBlock["ID"]);
	if (false == is_array($arCatalog))
	{
		$bCatalog = false;
	}
	else
	{
		if (in_array($arCatalog['CATALOG_TYPE'],array('P','X')))
		{
			if (CIBlockRights::UserHasRightTo($arCatalog['OFFERS_IBLOCK_ID'], $arCatalog['OFFERS_IBLOCK_ID'], "iblock_admin_display"))
			{
				$boolSKU = true;
				$strSKUName = GetMessage('IBLIST_A_OFFERS');
			}
		}
		if ('P' == $arCatalog['CATALOG_TYPE'])
			$bCatalog = false;
		if(!($USER->CanDoOperation('catalog_read') || $USER->CanDoOperation('catalog_price')))
			$bCatalog = false;
	}
}

$dbrFProps = CIBlockProperty::GetList(
		array(
			"SORT"=>"ASC",
			"NAME"=>"ASC"
		),
		array(
			"IBLOCK_ID"=>$IBLOCK_ID,
			"ACTIVE"=>"Y",
			"CHECK_PERMISSIONS"=>"N",
		)
	);

$arProps = array();
while($arProp = $dbrFProps->GetNext())
{
	if(strlen($arProp["USER_TYPE"])>0)
		$arUserType = CIBlockProperty::GetUserType($arProp["USER_TYPE"]);
	else
		$arUserType = array();

	$arProp["PROPERTY_USER_TYPE"] = $arUserType;

	$arProps[] = $arProp;
}

if ($boolSKU)
{
	$dbrFProps = CIBlockProperty::GetList(
		array(
			"SORT"=>"ASC",
			"NAME"=>"ASC"
		),
		array(
			"IBLOCK_ID"=>$arCatalog['OFFERS_IBLOCK_ID'],
			"ACTIVE"=>"Y",
			"CHECK_PERMISSIONS"=>"N",
		)
	);

	$arSKUProps = Array();
	while($arProp = $dbrFProps->GetNext())
	{
		if(strlen($arProp["USER_TYPE"])>0)
			$arUserType = CIBlockProperty::GetUserType($arProp["USER_TYPE"]);
		else
			$arUserType = array();

		$arProp["PROPERTY_USER_TYPE"] = $arUserType;

		if (('Y' == $arProp['FILTRABLE']) && ('F' != $arProp['PROPERTY_TYPE']) && ($arCatalog['OFFERS_PROPERTY_ID'] != $arProp['ID']))
			$boolSKUFiltrable = true;
		$arSKUProps[] = $arProp;
	}
}

$sTableID = "tbl_iblock_list_".md5($type.".".$IBLOCK_ID);
$oSort = new CAdminSorting($sTableID, "timestamp_x", "desc");
$lAdmin = new CAdminList($sTableID, $oSort);
$lAdmin->bMultipart = true;
$arFilterFields = Array(
	"find_name",
	"find_section_section",
	"find_id_1",		"find_id_2",
	"find_timestamp_1",	"find_timestamp_2",
	"find_code",
	"find_external_id",
	"find_modified_by",	"find_modified_user_id",
	"find_created_from",	"find_created_to",
	"find_created_by",	"find_created_user_id",
	"find_date_active_from_from",	"find_date_active_from_to",
	"find_date_active_to_from",	"find_date_active_to_to",
	"find_active",
	"find_intext",
	"find_status",		"find_status_id",
	"find_tags",
);
foreach ($arProps as $arProp)
{
	if($arProp["FILTRABLE"]=="Y" && $arProp["PROPERTY_TYPE"]!="F")
		$arFilterFields[] = "find_el_property_".$arProp["ID"];
}

if ($boolSKU && $boolSKUFiltrable)
{
	for($i = 0, $intPropCount = count($arSKUProps); $i < $intPropCount; $i++)
	{
		if($arSKUProps[$i]["FILTRABLE"]!="Y" || $arSKUProps[$i]["PROPERTY_TYPE"]=="F" || $arCatalog['OFFERS_PROPERTY_ID'] == $arSKUProps[$i]['ID'])
			continue;
		$arFilterFields[] = "find_sub_el_property_".$arSKUProps[$i]["ID"];
	}
}

//We have to handle current section in a special way
$section_id = intval($find_section_section);
$lAdmin->InitFilter($arFilterFields);
$find_section_section = $section_id;
//This is all parameters needed for proper navigation
$sThisSectionUrl = '&type='.urlencode($type).'&lang='.urlencode(LANG).'&IBLOCK_ID='.$IBLOCK_ID.'&find_section_section='.intval($find_section_section);

$arFilter = Array(
	"IBLOCK_ID"		=>$IBLOCK_ID,
	"NAME"			=>$find_name,
	"SECTION_ID"		=>$find_section_section,
	"ID_1"			=>$find_id_1,
	"ID_2"			=>$find_id_2,
	"TIMESTAMP_X_1"		=>$find_timestamp_1,
	"CODE"			=>$find_code,
	"EXTERNAL_ID"		=>$find_external_id,
	"MODIFIED_BY"		=>$find_modified_by,
	"MODIFIED_USER_ID"	=>$find_modified_user_id,
	"DATE_CREATE_1"		=>$find_created_from,
	"CREATED_BY"		=>$find_created_by,
	"CREATED_USER_ID"	=>$find_created_user_id,
	"DATE_ACTIVE_FROM_1"	=>$find_date_active_from_from,
	"DATE_ACTIVE_FROM_2"	=>$find_date_active_from_to,
	"DATE_ACTIVE_TO_1"	=>$find_date_active_to_from,
	"DATE_ACTIVE_TO_2"	=>$find_date_active_to_to,
	"ACTIVE"		=>$find_active,
	"DESCRIPTION"		=>$find_intext,
	"WF_STATUS"		=>$find_status==""?$find_status_id:$find_status,
	"?TAGS"			=>$find_tags,
	"CHECK_PERMISSIONS" => "Y",
	"MIN_PERMISSION" => "R",
);
if(!empty($find_timestamp_2))
	$arFilter["TIMESTAMP_X_2"] = CIBlock::isShortDate($find_timestamp_2)? ConvertTimeStamp(AddTime(MakeTimeStamp($find_timestamp_2), 1, "D"), "FULL"): $find_timestamp_2;
if(!empty($find_created_to))
	$arFilter["DATE_CREATE_2"] = CIBlock::isShortDate($find_created_to)? ConvertTimeStamp(AddTime(MakeTimeStamp($find_created_to), 1, "D"), "FULL"): $find_created_to;

if ($bBizproc && 'E' != $arIBlock['RIGHTS_MODE'])
{
	$strPerm = CIBlock::GetPermission($IBLOCK_ID);
	if ('W' > $strPerm)
	{
		unset($arFilter['CHECK_PERMISSIONS']);
		unset($arFilter['MIN_PERMISSION']);
		$arFilter['CHECK_BP_PERMISSIONS'] = 'read';
	}
}

foreach($arProps as $arProp)
{
	if($arProp["FILTRABLE"]=="Y" && $arProp["PROPERTY_TYPE"]!="F")
	{
		$value = ${"find_el_property_".$arProp["ID"]};

		if(array_key_exists("AddFilterFields", $arProp["PROPERTY_USER_TYPE"]))
		{
			call_user_func_array($arProp["PROPERTY_USER_TYPE"]["AddFilterFields"], array(
				$arProp,
				array("VALUE" => "find_el_property_".$arProp["ID"]),
				&$arFilter,
				&$filtered,
			));
		}
		elseif(is_array($value) || strlen($value))
		{
			if($value === "NOT_REF")
				$value = false;
			$arFilter["?PROPERTY_".$arProp["ID"]] = $value;
		}
	}
}

$arSubQuery = array("IBLOCK_ID" => $arCatalog['OFFERS_IBLOCK_ID']);
if ($boolSKU && $boolSKUFiltrable)
{
	for($i = 0, $intPropCount = count($arSKUProps); $i < $intPropCount; $i++)
	{
		if (('Y' == $arSKUProps[$i]["FILTRABLE"]) && ('F' != $arSKUProps[$i]["PROPERTY_TYPE"]) && ($arCatalog['OFFERS_PROPERTY_ID'] != $arSKUProps[$i]["ID"]))
		{
			if (array_key_exists("AddFilterFields", $arSKUProps[$i]["PROPERTY_USER_TYPE"]))
			{
				call_user_func_array($arSKUProps[$i]["PROPERTY_USER_TYPE"]["AddFilterFields"], array(
					$arSKUProps[$i],
					array("VALUE" => "find_sub_el_property_".$arSKUProps[$i]["ID"]),
					&$arSubQuery,
					&$filtered,
				));
			}
			else
			{
				$value = ${"find_sub_el_property_".$arSKUProps[$i]["ID"]};
				if(strlen($value) || is_array($value))
				{
					if($value === "NOT_REF")
						$value = false;
					$arSubQuery["?PROPERTY_".$arSKUProps[$i]["ID"]] = $value;
				}
			}
		}
	}
}

if (($boolSKU) && (1 < sizeof($arSubQuery)))
{
	$arFilter['ID'] = CIBlockElement::SubQuery('PROPERTY_'.$arCatalog['OFFERS_PROPERTY_ID'], $arSubQuery);
}

if(IntVal($find_section_section)<0 || strlen($find_section_section)<=0)
	unset($arFilter["SECTION_ID"]);

// Handle edit action (check for permission before save!)
if($lAdmin->EditAction())
{
	if(is_array($_FILES['FIELDS']))
		CAllFile::ConvertFilesToPost($_FILES['FIELDS'], $_POST['FIELDS']);
	if(is_array($FIELDS_del))
		CAllFile::ConvertFilesToPost($FIELDS_del, $_POST['FIELDS'], "del");

	foreach($_POST['FIELDS'] as $ID=>$arFields)
	{
		if(!$lAdmin->IsUpdated($ID))
			continue;
		$TYPE = substr($ID, 0, 1);
		$ID = IntVal(substr($ID,1));
		$arFields["IBLOCK_ID"] = $IBLOCK_ID;

		if($TYPE=="S")
		{
			if(CIBlockSectionRights::UserHasRightTo($IBLOCK_ID, $ID, "section_edit"))
			{
				$obS = new CIBlockSection;

				if(isset($arFields["PREVIEW_PICTURE"]))
					$arFields["PICTURE"] = $arFields["PREVIEW_PICTURE"];

				if(isset($arFields["PICTURE"]) && !is_array($arFields["PICTURE"]))
					$arFields["PICTURE"] = CFile::MakeFileArray($arFields["PICTURE"]);

				if(isset($arFields["PICTURE"]) && is_array($arFields["PICTURE"]))
				{
					if(isset($_REQUEST["FIELDS_descr"][$TYPE.$ID]["PICTURE"]))
						$arFields["PICTURE"]["description"] = $_REQUEST["FIELDS_descr"][$TYPE.$ID]["PICTURE"];
				}

				if(isset($arFields["DETAIL_PICTURE"]) && !is_array($arFields["DETAIL_PICTURE"]))
					$arFields["DETAIL_PICTURE"] = CFile::MakeFileArray($arFields["DETAIL_PICTURE"]);

				if(isset($arFields["DETAIL_PICTURE"]) && is_array($arFields["DETAIL_PICTURE"]))
				{
					if(isset($_REQUEST["FIELDS_descr"][$TYPE.$ID]["DETAIL_PICTURE"]))
						$arFields["DETAIL_PICTURE"]["description"] = $_REQUEST["FIELDS_descr"][$TYPE.$ID]["DETAIL_PICTURE"];
				}

				$DB->StartTransaction();
				if(!$obS->Update($ID, $arFields))
				{
					$lAdmin->AddUpdateError(GetMessage("IBLIST_A_SAVE_ERROR", array("#ID#" => $ID, "#ERROR_MESSAGE#" => '<br>'.$obS->LAST_ERROR)), $TYPE.$ID);
					$DB->Rollback();
				}
				else
				{
					$DB->Commit();
				}
			}
		}

		if($TYPE=="E")
		{
			$arRes = CIBlockElement::GetByID($ID);
			$arRes = $arRes->Fetch();
			if(!$arRes)
				continue;

			$WF_ID = $ID;
			if($bWorkFlow)
			{
				$WF_ID = CIBlockElement::WF_GetLast($ID);
				if($WF_ID!=$ID)
				{
					$rsData2 = CIBlockElement::GetByID($WF_ID);
					if($arRes = $rsData2->Fetch())
						$WF_ID = $arRes["ID"];
					else
						$WF_ID = $ID;
				}

				if($arRes["LOCK_STATUS"]=='red' && !($_REQUEST['action']=='unlock' && CWorkflow::IsAdmin()))
				{
					$lAdmin->AddUpdateError(GetMessage("IBLIST_A_UPDERR_LOCKED", array("#ID#" => $ID)), $TYPE.$ID);
					continue;
				}
			}
			elseif ($bBizproc)
			{
				if (call_user_func(array(ENTITY, "IsDocumentLocked"), $ID, ""))
				{
					$lAdmin->AddUpdateError(GetMessage("IBLIST_A_UPDERR_LOCKED", array("#ID#" => $ID)), $TYPE.$ID);
					continue;
				}
			}

			if(
				$bWorkFlow
			)
			{
				if (!CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $ID, "element_edit"))
				{
					$lAdmin->AddUpdateError(GetMessage("IBEL_A_UPDERR3")." (ID:".$ID.")", $ID);
					continue;
				}

				// handle workflow status access permissions
				if (CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $ID, "element_edit_any_wf_status"))
					$STATUS_PERMISSION = true;
				elseif ($arFields["WF_STATUS_ID"] > 0)
					$STATUS_PERMISSION = CIBlockElement::WF_GetStatusPermission($arFields["WF_STATUS_ID"]) >= 1;
				else
					$STATUS_PERMISSION = CIBlockElement::WF_GetStatusPermission($arRes["WF_STATUS_ID"]) >= 2;

				if (!$STATUS_PERMISSION)
				{
					$lAdmin->AddUpdateError(GetMessage("IBLIST_A_UPDERR_ACCESS", array("#ID#" => $ID)), $TYPE.$ID);
					continue;
				}
			}
			elseif ($bBizproc)
			{
				$bCanWrite = call_user_func(array(ENTITY, "CanUserOperateDocument"),
					CBPCanUserOperateOperation::WriteDocument,
					$USER->GetID(),
					$ID,
					array(
						"IBlockId" => $IBLOCK_ID,
						'IBlockRightsMode' => $arIBlock['RIGHTS_MODE'],
						'UserGroups' => $USER->GetUserGroupArray(),
					)
				);

				if(!$bCanWrite)
				{
					$lAdmin->AddUpdateError(GetMessage("IBLIST_A_UPDERR_ACCESS", array("#ID#" => $ID)), $TYPE.$ID);
					continue;
				}
			}
			elseif(!CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $ID, "element_edit"))
			{
				$lAdmin->AddUpdateError(GetMessage("IBLIST_A_UPDERR_ACCESS", array("#ID#" => $ID)), $TYPE.$ID);
				continue;
			}

			if(isset($arFields["PREVIEW_PICTURE"]) && !is_array($arFields["PREVIEW_PICTURE"]))
				$arFields["PREVIEW_PICTURE"] = CFile::MakeFileArray($arFields["PREVIEW_PICTURE"]);

			if(isset($arFields["PREVIEW_PICTURE"]) && is_array($arFields["PREVIEW_PICTURE"]))
			{
				if(isset($_REQUEST["FIELDS_descr"][$TYPE.$ID]["PREVIEW_PICTURE"]))
					$arFields["PREVIEW_PICTURE"]["description"] = $_REQUEST["FIELDS_descr"][$TYPE.$ID]["PREVIEW_PICTURE"];
			}

			if(isset($arFields["DETAIL_PICTURE"]) && !is_array($arFields["DETAIL_PICTURE"]))
				$arFields["DETAIL_PICTURE"] = CFile::MakeFileArray($arFields["DETAIL_PICTURE"]);

			if(isset($arFields["DETAIL_PICTURE"]) && is_array($arFields["DETAIL_PICTURE"]))
			{
				if(isset($_REQUEST["FIELDS_descr"][$TYPE.$ID]["DETAIL_PICTURE"]))
					$arFields["DETAIL_PICTURE"]["description"] = $_REQUEST["FIELDS_descr"][$TYPE.$ID]["DETAIL_PICTURE"];
			}

			if(!is_array($arFields["PROPERTY_VALUES"]))
				$arFields["PROPERTY_VALUES"] = Array();
			$bFieldProps = array();
			foreach($arFields as $k=>$v)
			{
				if(
					substr($k, 0, strlen("PROPERTY_")) == "PROPERTY_"
					&& $k != "PROPERTY_VALUES"
				)
				{
					$prop_id = substr($k, strlen("PROPERTY_"));

					if(isset($_REQUEST["FIELDS_descr"][$TYPE.$ID][$k]) && is_array($_REQUEST["FIELDS_descr"][$TYPE.$ID][$k]))
					{
						foreach($_REQUEST["FIELDS_descr"][$TYPE.$ID][$k] as $PROPERTY_VALUE_ID => $ar)
						{
							if(
								is_array($ar)
								&& isset($ar["VALUE"])
								&& isset($v[$PROPERTY_VALUE_ID]["VALUE"])
								&& is_array($v[$PROPERTY_VALUE_ID]["VALUE"])
							)
								$v[$PROPERTY_VALUE_ID]["DESCRIPTION"] = $ar["VALUE"];
						}
					}

					$arFields["PROPERTY_VALUES"][$prop_id] = $v;
					unset($arFields[$k]);
					$bFieldProps[$prop_id] = true;
				}
			}
			if(count($bFieldProps) > 0)
			{
				//We have to read properties from database in order not to delete its values
				if(!$bWorkFlow)
				{
					$dbPropV = CIBlockElement::GetProperty($IBLOCK_ID, $ID, "sort", "asc", Array("ACTIVE"=>"Y"));
					while($arPropV = $dbPropV->Fetch())
					{
						if(!array_key_exists($arPropV["ID"], $bFieldProps) && $arPropV["PROPERTY_TYPE"] != "F")
						{
							if(!array_key_exists($arPropV["ID"], $arFields["PROPERTY_VALUES"]))
								$arFields["PROPERTY_VALUES"][$arPropV["ID"]] = array();

							$arFields["PROPERTY_VALUES"][$arPropV["ID"]][$arPropV["PROPERTY_VALUE_ID"]] = array(
								"VALUE" => $arPropV["VALUE"],
								"DESCRIPTION" => $arPropV["DESCRIPTION"],
							);
						}
					}
				}
			}
			else
			{
				//We will not update property values
				unset($arFields["PROPERTY_VALUES"]);
			}

			//All not displayed required fields from DB
			foreach($arIBlock["FIELDS"] as $FIELD_ID => $field)
			{
				if(
					$field["IS_REQUIRED"] === "Y"
					&& !array_key_exists($FIELD_ID, $arFields)
					&& $FIELD_ID !== "DETAIL_PICTURE"
					&& $FIELD_ID !== "PREVIEW_PICTURE"
				)
					$arFields[$FIELD_ID] = $arRes[$FIELD_ID];
			}
			if($arRes["IN_SECTIONS"] == "Y")
			{
				$arFields["IBLOCK_SECTION"] = array();
				$rsSections = CIBlockElement::GetElementGroups($arRes["ID"], true);
				while($arSection = $rsSections->Fetch())
					$arFields["IBLOCK_SECTION"][] = $arSection["ID"];
			}

			$arFields["MODIFIED_BY"]=$USER->GetID();
			$ib = new CIBlockElement;
			$DB->StartTransaction();
			if(!$ib->Update($ID, $arFields, true, true, true))
			{
				$lAdmin->AddUpdateError(GetMessage("IBLIST_A_SAVE_ERROR", array("#ID#" => $ID, "#ERROR_MESSAGE#" => $ib->LAST_ERROR)), $TYPE.$ID);
				$DB->Rollback();
			}
			else
			{
				$DB->Commit();
			}

			if($bCatalog)
			{
				if ($USER->CanDoOperation('catalog_price') && CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $ID, "element_edit_price"))
				{
					$CATALOG_QUANTITY = $arFields["CATALOG_QUANTITY"];
					$CATALOG_QUANTITY_TRACE = $arFields["CATALOG_QUANTITY_TRACE"];
					$CATALOG_WEIGHT = $arFields["CATALOG_WEIGHT"];

					if(!CCatalogProduct::GetByID($ID))
					{
						$arCatalogQuantity = Array("ID" => $ID);
						if(strlen($CATALOG_QUANTITY) > 0)
						{
							if (COption::GetOptionString("catalog", "default_use_store_control") != "Y")
								$arCatalogQuantity["QUANTITY"] = $CATALOG_QUANTITY;
						}
						if(strlen($CATALOG_QUANTITY_TRACE) > 0)
							$arCatalogQuantity["QUANTITY_TRACE"] = (($CATALOG_QUANTITY_TRACE == "Y") ? "Y" : (($CATALOG_QUANTITY_TRACE == "D") ? "D" : "N"));
						if (strlen($CATALOG_WEIGHT) > 0)
							$arCatalogQuantity['WEIGHT'] = $CATALOG_WEIGHT;
						CCatalogProduct::Add($arCatalogQuantity);
					}
					else
					{
						$arCatalogQuantity = Array();
						if(strlen($CATALOG_QUANTITY) > 0)
						{
							if (COption::GetOptionString("catalog", "default_use_store_control") != "Y")
								$arCatalogQuantity["QUANTITY"] = $CATALOG_QUANTITY;
						}
						if(strlen($CATALOG_QUANTITY_TRACE) > 0)
							$arCatalogQuantity["QUANTITY_TRACE"] = (($CATALOG_QUANTITY_TRACE == "Y") ? "Y" : (($CATALOG_QUANTITY_TRACE == "D") ? "D" : "N"));
						if (strlen($CATALOG_WEIGHT) > 0)
							$arCatalogQuantity['WEIGHT'] = $CATALOG_WEIGHT;
						if(!empty($arCatalogQuantity))
							CCatalogProduct::Update($ID, $arCatalogQuantity);
					}
				}
			}
		}
	}

	if($bCatalog)
	{
		if($USER->CanDoOperation('catalog_price') && (isset($_POST["CATALOG_PRICE"]) || isset($_POST["CATALOG_CURRENCY"])))
		{
			$CATALOG_PRICE = $_POST["CATALOG_PRICE"];
			$CATALOG_CURRENCY = $_POST["CATALOG_CURRENCY"];
			$CATALOG_EXTRA = $_POST["CATALOG_EXTRA"];
			$CATALOG_PRICE_ID = $_POST["CATALOG_PRICE_ID"];
			$CATALOG_QUANTITY_FROM = $_POST["CATALOG_QUANTITY_FROM"];
			$CATALOG_QUANTITY_TO = $_POST["CATALOG_QUANTITY_TO"];
			$CATALOG_PRICE_old = $_POST["CATALOG_old_PRICE"];
			$CATALOG_CURRENCY_old = $_POST["CATALOG_old_CURRENCY"];
			$db_extras = CExtra::GetList(($by3="NAME"), ($order3="ASC"));
			while ($extras = $db_extras->Fetch())
				$arCatExtraUp[$extras["ID"]] = $extras["PERCENTAGE"];

			foreach($CATALOG_PRICE as $elID => $arPrice)
			{
				if (!(
					CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $elID, "element_edit")
					&& CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $elID, "element_edit_price"))
				)
					continue;
				//1 Find base price ID
				//2 If such a column is displayed then
				//	check if it is greater than 0
				//3 otherwise
				//	look up it's value in database and
				//	output an error if not found or found less or equal then zero
				$bError = false;
				$arBaseGroup = CCatalogGroup::GetBaseGroup();
				if (COption::GetOptionString('catalog','save_product_without_price','N') != 'Y')
				{
					if (isset($arPrice[$arBaseGroup['ID']]))
					{
						if ($arPrice[$arBaseGroup['ID']] <= 0)
						{
							$bError = true;
							$lAdmin->AddUpdateError(GetMessage('IBLIST_A_NO_BASE_PRICE', array("#ID#" => $elID)), $elID);
						}
					}
					else
					{
						$arBasePrice = CPrice::GetBasePrice(
							$elID,
							$CATALOG_QUANTITY_FROM[$elID][$arBaseGroup['ID']],
							$CATALOG_QUANTITY_FROM[$elID][$arBaseGroup['ID']]
						);

						if (!is_array($arBasePrice) || $arBasePrice['PRICE'] <= 0)
						{
							$bError = true;
							$lAdmin->AddUpdateError(GetMessage('IBLIST_A_NO_BASE_PRICE', array("#ID#" => $elID)), $elID);
						}
					}
				}

				if($bError)
					continue;

				$arCurrency = $CATALOG_CURRENCY[$elID];

				$dbCatalogGroups = CCatalogGroup::GetList(
						array("SORT" => "ASC"),
						array("LID"=>LANGUAGE_ID)
					);
				while ($arCatalogGroup = $dbCatalogGroups->Fetch())
				{
					if(doubleval($arPrice[$arCatalogGroup["ID"]]) != doubleval($CATALOG_PRICE_old[$elID][$arCatalogGroup["ID"]])
						|| $arCurrency[$arCatalogGroup["ID"]] != $CATALOG_CURRENCY_old[$elID][$arCatalogGroup["ID"]])
					{
						if($arCatalogGroup["BASE"]=="Y") // if base price check extra for other prices
						{
							$arFields = Array(
								"PRODUCT_ID" => $elID,
								"CATALOG_GROUP_ID" => $arCatalogGroup["ID"],
								"PRICE" => DoubleVal($arPrice[$arCatalogGroup["ID"]]),
								"CURRENCY" => $arCurrency[$arCatalogGroup["ID"]],
								"QUANTITY_FROM" => $CATALOG_QUANTITY_FROM[$elID][$arCatalogGroup["ID"]],
								"QUANTITY_TO" => $CATALOG_QUANTITY_TO[$elID][$arCatalogGroup["ID"]],
							);
							if($arFields["PRICE"] <=0 )
							{
								CPrice::Delete($CATALOG_PRICE_ID[$elID][$arCatalogGroup["ID"]]);
							}
							elseif(IntVal($CATALOG_PRICE_ID[$elID][$arCatalogGroup["ID"]])>0)
							{
								CPrice::Update(IntVal($CATALOG_PRICE_ID[$elID][$arCatalogGroup["ID"]]), $arFields);
							}
							elseif($arFields["PRICE"] > 0)
							{
								CPrice::Add($arFields);
							}

							$arPrFilter = array(
								"PRODUCT_ID" => $elID,
							);
							if(DoubleVal($arPrice[$arCatalogGroup["ID"]])>0)
							{
								$arPrFilter["!CATALOG_GROUP_ID"] = $arCatalogGroup["ID"];
								$arPrFilter["+QUANTITY_FROM"] = "1";
								$arPrFilter["!EXTRA_ID"] = false;
							}
							$db_res = CPrice::GetList(
								array(),
								$arPrFilter,
								false,
								false,
								Array("ID", "PRODUCT_ID", "CATALOG_GROUP_ID", "PRICE", "CURRENCY", "QUANTITY_FROM", "QUANTITY_TO", "EXTRA_ID")
							);
							while($ar_res = $db_res->Fetch())
							{
								$arFields = Array(
									"PRICE" => DoubleVal($arPrice[$arCatalogGroup["ID"]])*(1+$arCatExtraUp[$ar_res["EXTRA_ID"]]/100) ,
									"EXTRA_ID" => $ar_res["EXTRA_ID"],
									"CURRENCY" => $arCurrency[$arCatalogGroup["ID"]],
									"QUANTITY_FROM" => $ar_res["QUANTITY_FROM"],
									"QUANTITY_TO" => $ar_res["QUANTITY_TO"]
								);
								if($arFields["PRICE"] <= 0)
									CPrice::Delete($ar_res["ID"]);
								else
									CPrice::Update($ar_res["ID"], $arFields);
							}
						}
						elseif(!isset($CATALOG_EXTRA[$elID][$arCatalogGroup["ID"]]))
						{
							$arFields = Array(
								"PRODUCT_ID" => $elID,
								"CATALOG_GROUP_ID" => $arCatalogGroup["ID"],
								"PRICE" => DoubleVal($arPrice[$arCatalogGroup["ID"]]),
								"CURRENCY" => $arCurrency[$arCatalogGroup["ID"]],
								"QUANTITY_FROM" => $CATALOG_QUANTITY_FROM[$elID][$arCatalogGroup["ID"]],
								"QUANTITY_TO" => $CATALOG_QUANTITY_TO[$elID][$arCatalogGroup["ID"]]
							);
							if($arFields["PRICE"] <= 0)
								CPrice::Delete($CATALOG_PRICE_ID[$elID][$arCatalogGroup["ID"]]);
							elseif(IntVal($CATALOG_PRICE_ID[$elID][$arCatalogGroup["ID"]])>0)
								CPrice::Update(IntVal($CATALOG_PRICE_ID[$elID][$arCatalogGroup["ID"]]), $arFields);
							elseif($arFields["PRICE"] > 0)
								CPrice::Add($arFields);
						}
					}
				}
			}
		}
	}
}


// Handle actions here
if(($arID = $lAdmin->GroupAction()))
{
	if($_REQUEST['action_target']=='selected')
	{
		$rsData = CIBlockSection::GetMixedList(Array($by=>$order), $arFilter);
		while($arRes = $rsData->Fetch())
			$arID[] = $arRes['TYPE'].$arRes['ID'];
	}

	foreach($arID as $ID)
	{
		if(strlen($ID)<=1)
			continue;
		$TYPE = substr($ID, 0, 1);
		$ID = IntVal(substr($ID,1));

		if($TYPE == "E")
		{
			$arRes = CIBlockElement::GetByID($ID);
			$arRes = $arRes->Fetch();
			if(!$arRes)
				continue;

			$WF_ID = $ID;
			if($bWorkFlow)
			{
				$WF_ID = CIBlockElement::WF_GetLast($ID);
				if($WF_ID!=$ID)
				{
					$rsData2 = CIBlockElement::GetByID($WF_ID);
					if($arRes = $rsData2->Fetch())
						$WF_ID = $arRes["ID"];
					else
						$WF_ID = $ID;
				}

				if($arRes["LOCK_STATUS"]=='red' && !($_REQUEST['action']=='unlock' && CWorkflow::IsAdmin()))
				{
					$lAdmin->AddUpdateError(GetMessage("IBLIST_A_UPDERR_LOCKED", array("#ID#" => $ID)), $TYPE.$ID);
					continue;
				}
			}
			elseif ($bBizproc)
			{
				if (call_user_func(array(ENTITY, "IsDocumentLocked"), $ID, "") && !($_REQUEST['action']=='unlock' && CBPDocument::IsAdmin()))
				{
					$lAdmin->AddUpdateError(GetMessage("IBLIST_A_UPDERR_LOCKED", array("#ID#" => $ID)), $TYPE.$ID);
					continue;
				}
			}

			$bPermissions = false;
			//delete and modify can:
			if($bWorkFlow)
			{
				//For delete action we have to check all statuses in element history
				$STATUS_PERMISSION = CIBlockElement::WF_GetStatusPermission($arRes["WF_STATUS_ID"], $_REQUEST['action']=="delete"? $ID: false);
				if($STATUS_PERMISSION >= 2)
					$bPermissions = true;
			}
			elseif ($bBizproc)
			{
				$bCanWrite = CIBlockDocument::CanUserOperateDocument(
					CBPCanUserOperateOperation::WriteDocument,
					$USER->GetID(),
					$ID,
					array(
						"IBlockId" => $IBLOCK_ID,
						'IBlockRightsMode' => $arIBlock['RIGHTS_MODE'],
						'UserGroups' => $USER->GetUserGroupArray(),
					)
				);


				if ($bCanWrite)
					$bPermissions = true;
			}
			else
			{
				$bPermissions = true;
			}

			if(!$bPermissions)
			{
				$lAdmin->AddGroupError(GetMessage("IBLIST_A_UPDERR_ACCESS", array("#ID#" => $ID)));
				continue;
			}

		}

		switch($_REQUEST['action'])
		{
		case "delete":
			@set_time_limit(0);
			if($TYPE=="S")
			{
				if(CIBlockSectionRights::UserHasRightTo($IBLOCK_ID, $ID, "section_delete"))
				{
					$DB->StartTransaction();
					if(!CIBlockSection::Delete($ID))
					{
						$DB->Rollback();
						$lAdmin->AddGroupError(GetMessage("IBLIST_A_SECTION_DELETE_ERROR", array("#ID#" => $ID)), $TYPE.$ID);
					}
					else
					{
						$DB->Commit();
					}
				}
				else
				{
					$lAdmin->AddGroupError(GetMessage("IBLIST_A_SECTION_DELETE_ERROR", array("#ID#" => $ID)), $TYPE.$ID);
				}
			}
			elseif($TYPE=="E")
			{
				if(CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $ID, "element_delete"))
				{
					$DB->StartTransaction();
					$APPLICATION->ResetException();
					if(!CIBlockElement::Delete($ID))
					{
						$DB->Rollback();
						if($ex = $APPLICATION->GetException())
							$lAdmin->AddGroupError(GetMessage("IBLIST_A_ELEMENT_DELETE_ERROR", array("#ID#" => $ID))." [".$ex->GetString()."]", $TYPE.$ID);
						else
							$lAdmin->AddGroupError(GetMessage("IBLIST_A_ELEMENT_DELETE_ERROR", array("#ID#" => $ID)), $TYPE.$ID);
					}
					else
					{
						$DB->Commit();
					}
				}
				else
				{
					$lAdmin->AddGroupError(GetMessage("IBLIST_A_ELEMENT_DELETE_ERROR", array("#ID#" => $ID)), $TYPE.$ID);
				}
			}
			break;
		case "activate":
		case "deactivate":
			$arFields = Array("ACTIVE"=>($_REQUEST['action']=="activate"?"Y":"N"));

			if($TYPE=="S")
			{
				if(CIBlockSectionRights::UserHasRightTo($IBLOCK_ID, $ID, "section_edit"))
				{
					$obS = new CIBlockSection();
					if(!$obS->Update($ID, $arFields))
						$lAdmin->AddGroupError(GetMessage("IBLIST_A_SAVE_ERROR", array("#ID#" => $ID, "#ERROR_MESSAGE#" => $obS->LAST_ERROR)), $TYPE.$ID);
				}
				else
				{
					$lAdmin->AddGroupError(GetMessage("IBLIST_A_UPDERR_ACCESS", array("#ID#" => $ID)), $TYPE.$ID);
				}
			}
			elseif($TYPE=="E")
			{
				if(CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $ID, "element_edit"))
				{
					$obE = new CIBlockElement();
					if(!$obE->Update($ID, $arFields, true))
						$lAdmin->AddGroupError(GetMessage("IBLIST_A_SAVE_ERROR", array("#ID#" => $ID, "#ERROR_MESSAGE#" => $obE->LAST_ERROR)), $TYPE.$ID);
				}
				else
				{
					$lAdmin->AddGroupError(GetMessage("IBLIST_A_UPDERR_ACCESS", array("#ID#" => $ID)), $TYPE.$ID);
				}
			}
			break;
		case "section":
		case "add_section":
			$new_section = intval($_REQUEST["section_to_move"]);
			if(
				($new_section >= 0)
				&& ($new_section != $section_id)
			)
			{
				if ($TYPE=="S")
				{
					if (CIBlockSectionRights::UserHasRightTo($IBLOCK_ID, $new_section, "section_section_bind"))
					{
						$obS = new CIBlockSection();
						if(!$obS->Update($ID, array("IBLOCK_SECTION_ID" => $new_section)))
							$lAdmin->AddGroupError(GetMessage("IBLIST_A_SAVE_ERROR", array("#ID#" => $ID, "#ERROR_MESSAGE#" => $obS->LAST_ERROR)), $TYPE.$ID);
					}
					else
					{
						$lAdmin->AddGroupError(GetMessage("IBLIST_A_UPDERR_ACCESS", array("#ID#" => $ID)), $TYPE.$ID);
					}
				}
				elseif($TYPE=="E")
				{
					if (CIBlockSectionRights::UserHasRightTo($IBLOCK_ID, $new_section, "section_element_bind"))
					{
						$obE = new CIBlockElement();

						$arSections = array();
						$rsSections = $obE->GetElementGroups($ID, true);
						while($ar = $rsSections->Fetch())
							$arSections[$ar["ID"]] = $ar["ID"];

						if($_REQUEST['action'] == "section")
							$arSections[$section_id] = $new_section;
						else
							$arSections[$new_section] = $new_section;

						if(!$obE->Update($ID, array("IBLOCK_SECTION" => $arSections)))
							$lAdmin->AddGroupError(GetMessage("IBLIST_A_SAVE_ERROR", array("#ID#" => $ID, "#ERROR_MESSAGE#" => $obE->LAST_ERROR)), $TYPE.$ID);
					}
					else
					{
						$lAdmin->AddGroupError(GetMessage("IBLIST_A_UPDERR_ACCESS", array("#ID#" => $ID)), $TYPE.$ID);
					}
				}
			}
			break;
		case "wf_status":
			if($TYPE=="E" && $bWorkFlow)
			{
				$new_status = intval($_REQUEST["wf_status_id"]);
				if(
					$new_status > 0
				)
				{
					if (
						CIBlockElement::WF_GetStatusPermission($new_status) > 0
						|| CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $ID, "element_edit_any_wf_status")
					)
					{
						if($arRes["WF_STATUS_ID"] != $new_status)
						{
							$obE = new CIBlockElement();
							$res = $obE->Update($ID, array(
								"WF_STATUS_ID" => $new_status,
								"MODIFIED_BY" => $USER->GetID(),
							), true);
							if(!$res)
								$lAdmin->AddGroupError(GetMessage("IBLIST_A_SAVE_ERROR", array("#ID#" => $ID, "#ERROR_MESSAGE#" => $obE->LAST_ERROR)), $TYPE.$ID);
						}
					}
					else
					{
						$lAdmin->AddGroupError(GetMessage("IBLIST_A_SAVE_ERROR", array("#ID#" => $ID, "#ERROR_MESSAGE#" => GetMessage("IBLIST_A_ACCESS_DENIED_STATUS")." [".$new_status."].<br>")), $TYPE.$ID);
					}
				}
			}
			break;
		case "lock":
			if ($TYPE=="E")
			{
				if ($bWorkflow && !CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $ID, "element_edit"))
				{
					$lAdmin->AddGroupError(GetMessage("IBLIST_A_UPDERR_ACCESS", array("#ID#" => $ID)), $TYPE.$ID);
					continue;
				}
				else
				{
					CIBlockElement::WF_Lock($ID);
				}
			}
			break;
		case "unlock":
			if ($TYPE=="E")
			{
				if ($bWorkflow && !CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $ID, "element_edit"))
				{
					$lAdmin->AddGroupError(GetMessage("IBLIST_A_UPDERR_ACCESS", array("#ID#" => $ID)), $TYPE.$ID);
					continue;
				}
				if ($bBizproc)
					call_user_func(array(ENTITY, "UnlockDocument"), $ID, "");
				else
					CIBlockElement::WF_UnLock($ID);
			}
			break;
		}
	}

	if(isset($return_url) && strlen($return_url)>0)
		LocalRedirect($return_url);
}

$CAdminCalendar_ShowScript = CAdminCalendar::ShowScript();

// List header
$arHeader = array(
//Common
	array(
		"id" => "NAME",
		"content" => GetMessage("IBLIST_A_NAME"),
		"sort" => "name",
		"default" => true,
	),
	array(
		"id" => "ACTIVE",
		"content" => GetMessage("IBLIST_A_ACTIVE"),
		"sort" => "active",
		"default" => true,
		"align" => "center",
	),
	array(
		"id" => "SORT",
		"content" => GetMessage("IBLIST_A_SORT"),
		"sort" => "sort",
		"default" => true,
		"align" => "right",
	),
	array(
		"id"=>"CODE",
		"content"=>GetMessage("IBLIST_A_CODE"),
		"sort"=>"code",
	),
	array(
		"id" => "EXTERNAL_ID",
		"content" => GetMessage("IBLIST_A_EXTCODE"),
		"sort" => "external_id",
	),
	array(
		"id" => "TIMESTAMP_X",
		"content" => GetMessage("IBLIST_A_TIMESTAMP"),
		"sort" => "timestamp_x",
		"default" => true,
	),
	array(
		"id" => "USER_NAME",
		"content" => GetMessage("IBLIST_A_MODIFIED_BY"),
		"sort" => "modified_by",
	),
	array(
		"id" => "DATE_CREATE",
		"content" => GetMessage("IBLIST_A_DATE_CREATE"),
		"sort" => "created",
	),
	array(
		"id" => "CREATED_USER_NAME",
		"content" => GetMessage("IBLIST_A_CREATED_USER_NAME"),
		"sort" => "created_by",
	),
	array(
		"id" => "ID",
		"content" => GetMessage("IBLIST_A_ID"),
		"sort" => "id",
		"default" => true,
		"align" => "right",
	),
//Section specific
	array(
		"id" => "ELEMENT_CNT",
		"content" => GetMessage("IBLIST_A_ELS"),
		"sort" => "element_cnt",
		"align" => "right",
	),
	array(
		"id" => "SECTION_CNT",
		"content" => GetMessage("IBLIST_A_SECS"),
		"align" => "right",
	),
//Element specific

	array(
		"id" => "DATE_ACTIVE_FROM",
		"content" => GetMessage("IBLIST_A_DATE_ACTIVE_FROM"),
		"sort" => "date_active_from",
	),
	array(
		"id" => "DATE_ACTIVE_TO",
		"content" => GetMessage("IBLIST_A_DATE_ACTIVE_TO"),
		"sort" => "date_active_to",
	),
	array(
		"id" => "SHOW_COUNTER",
		"content" => GetMessage("IBLIST_A_SHOW_COUNTER"),
		"sort" => "show_counter",
		"align" => "right",
	),
	array(
		"id" => "SHOW_COUNTER_START",
		"content" => GetMessage("IBLIST_A_SHOW_COUNTER_START"),
		"sort" => "show_counter_start",
		"align" => "right",
	),
	array(
		"id" => "PREVIEW_PICTURE",
		"content" => GetMessage("IBLIST_A_PREVIEW_PICTURE"),
		"align" => "right",
	),
	array(
		"id" => "PREVIEW_TEXT",
		"content" => GetMessage("IBLIST_A_PREVIEW_TEXT"),
	),
	array(
		"id" => "DETAIL_PICTURE",
		"content" => GetMessage("IBLIST_A_DETAIL_PICTURE"),
		"align" => "right",
	),
	array(
		"id" => "DETAIL_TEXT",
		"content" => GetMessage("IBLIST_A_DETAIL_TEXT"),
	),
	array(
		"id" => "TAGS",
		"content" => GetMessage("IBLIST_A_TAGS"),
		"sort" => "tags",
	),
);

$arWFStatusAll = array();
$arWFStatusPerm = array();
if($bWorkFlow)
{
	$arHeader[] = array(
		"id" => "WF_STATUS_ID",
		"content" => GetMessage("IBLIST_A_STATUS"),
		"sort" => "status",
		"default" => true,
	);
	$arHeader[] = array(
		"id" => "WF_NEW",
		"content" => GetMessage("IBLIST_A_WF_NEW"),
	);
	$arHeader[] = array(
		"id" => "LOCK_STATUS",
		"content" => GetMessage("IBLIST_A_LOCK_STATUS"),
		"default" => true,
		"align" => "center",
	);
	$arHeader[] = array(
		"id" => "LOCKED_USER_NAME",
		"content" => GetMessage("IBLIST_A_LOCKED_USER_NAME"),
	);
	$arHeader[] = array(
		"id" => "WF_DATE_LOCK",
		"content" => GetMessage("IBLIST_A_WF_DATE_LOCK"),
	);
	$arHeader[] = array(
		"id" => "WF_COMMENTS",
		"content" => GetMessage("IBLIST_A_WF_COMMENTS"),
	);
	$rsWF = CWorkflowStatus::GetDropDownList("Y");
	while($arWF = $rsWF->GetNext())
		$arWFStatusAll[$arWF["~REFERENCE_ID"]] = $arWF["~REFERENCE"];
	$rsWF = CWorkflowStatus::GetDropDownList("N", "desc");
	while($arWF = $rsWF->GetNext())
		$arWFStatusPerm[$arWF["~REFERENCE_ID"]] = $arWF["~REFERENCE"];
}

foreach($arProps as $arFProps)
{
	$arHeader[] = array(
		"id" => "PROPERTY_".$arFProps['ID'],
		"content" => $arFProps['NAME'],
		"align" => ($arFProps["PROPERTY_TYPE"]=='N'? "right": "left"),
		"sort" => ($arFProps["MULTIPLE"]!='Y'? "PROPERTY_".$arFProps['ID']: ""),
	);
}

if($bCatalog)
{
	$arHeader[] = array(
		"id" => "CATALOG_QUANTITY",
		"content" => GetMessage("IBLIST_A_CATALOG_QUANTITY"),
		"align" => "right",
		"sort" => "CATALOG_QUANTITY",
	);
	$arHeader[] = array(
		"id" => "CATALOG_QUANTITY_TRACE",
		"content" => GetMessage("IBLIST_A_CATALOG_QUANTITY_TRACE"),
		"align" => "right",
	);
	$arHeader[] = array(
		"id" => "CATALOG_WEIGHT",
		"content" => GetMessage("IBLIST_A_CATALOG_WEIGHT"),
		"align" => "right",
		"sort" => "CATALOG_WEIGHT",
		"default" => false,
	);

	$arCatGroup = array();
	$dbCatalogGroups = CCatalogGroup::GetList(
			array("SORT" => "ASC"),
			array("LID"=>LANGUAGE_ID)
		);
	while ($arCatalogGroup = $dbCatalogGroups->Fetch())
	{
		$arHeader[] = array(
			"id" => "CATALOG_GROUP_".$arCatalogGroup["ID"],
			"content" => htmlspecialcharsex(!empty($arCatalogGroup["NAME_LANG"]) ? $arCatalogGroup["NAME_LANG"] : $arCatalogGroup["NAME"]),
			"align" => "right",
			"sort" => "CATALOG_PRICE_".$arCatalogGroup["ID"],
		);
		$arCatGroup[$arCatalogGroup["ID"]] = $arCatalogGroup;
	}
	$arCatExtra = array();

	$db_extras = CExtra::GetList(($by3="NAME"), ($order3="ASC"));
	while ($extras = $db_extras->Fetch())
		$arCatExtra[] = $extras;
}

if ($bBizproc)
{
	$arWorkflowTemplates = CBPDocument::GetWorkflowTemplatesForDocumentType(array(MODULE_ID, ENTITY, DOCUMENT_TYPE));
	foreach ($arWorkflowTemplates as $arTemplate)
	{
		$arHeader[] = array(
			"id" => "WF_".$arTemplate["ID"],
			"content" => $arTemplate["NAME"],
		);
	}
	$arHeader[] = array(
		"id" => "BIZPROC",
		"content" => GetMessage("IBLIST_A_BP_H"),
	);
	$arHeader[] = array(
		"id" => "LOCK_STATUS",
		"content" => GetMessage("IBLIST_A_LOCK_STATUS"),
		"default" => true,
	);
	$arHeader[] = array(
		"id" => "BP_PUBLISHED",
		"content" => GetMessage("IBLOCK_FIELD_BP_PUBLISHED"),
		"sort" => "status",
		"default" => true,
	);
}


$lAdmin->AddHeaders($arHeader);

$arSelectedFields = $lAdmin->GetVisibleHeaderColumns();
$arSelectedProps = Array();
foreach($arProps as $i => $arProperty)
{
	$k = array_search("PROPERTY_".$arProperty['ID'], $arSelectedFields);
	if($k!==false)
	{
		$arSelectedProps[] = $arProperty;
		if($arProperty["PROPERTY_TYPE"] == "L")
		{
			$arSelect[$arProperty['ID']] = Array();
			$rs = CIBlockProperty::GetPropertyEnum($arProperty['ID']);
			while($ar = $rs->GetNext())
				$arSelect[$arProperty['ID']][$ar["ID"]] = $ar["VALUE"];
		}
		elseif($arProperty["PROPERTY_TYPE"] == "G")
		{
			$arSelect[$arProperty['ID']] = Array();
			$rs = CIBlockSection::GetTreeList(Array("IBLOCK_ID"=>$arProperty["LINK_IBLOCK_ID"]));
			while($ar = $rs->GetNext())
				$arSelect[$arProperty['ID']][$ar["ID"]] = str_repeat(" . ", $ar["DEPTH_LEVEL"]).$ar["NAME"];
		}
		unset($arSelectedFields[$k]);
	}
}

$arSelectedFields[] = "ID";
$arSelectedFields[] = "CREATED_BY";
$arSelectedFields[] = "LANG_DIR";
$arSelectedFields[] = "LID";
$arSelectedFields[] = "WF_PARENT_ELEMENT_ID";
$arSelectedFields[] = "ACTIVE";

if(in_array("LOCKED_USER_NAME", $arSelectedFields))
	$arSelectedFields[] = "WF_LOCKED_BY";
if(in_array("USER_NAME", $arSelectedFields))
	$arSelectedFields[] = "MODIFIED_BY";
if(in_array("PREVIEW_TEXT", $arSelectedFields))
	$arSelectedFields[] = "PREVIEW_TEXT_TYPE";
if(in_array("DETAIL_TEXT", $arSelectedFields))
	$arSelectedFields[] = "DETAIL_TEXT_TYPE";
if(in_array("CATALOG_QUANTITY_TRACE", $arSelectedFields))
	$arSelectedFields[] = "CATALOG_QUANTITY_TRACE_ORIG";

$arSelectedFields[] = "LOCK_STATUS";
$arSelectedFields[] = "WF_NEW";
$arSelectedFields[] = "WF_STATUS_ID";
$arSelectedFields[] = "DETAIL_PAGE_URL";
$arSelectedFields[] = "SITE_ID";
$arSelectedFields[] = "CODE";
$arSelectedFields[] = "EXTERNAL_ID";

$arVisibleColumnsMap = array();
foreach($arSelectedFields as $value)
	$arVisibleColumnsMap[$value] = true;

if ($bCatalog)
{
	if (is_array($arCatGroup) && !empty($arCatGroup))
	{
		$boolPriceInc = false;
		foreach($arCatGroup as &$CatalogGroups)
		{
			if(in_array("CATALOG_GROUP_".$CatalogGroups["ID"], $arSelectedFields))
			{
				$arFilter["CATALOG_SHOP_QUANTITY_".$CatalogGroups["ID"]] = 1;
				$boolPriceInc = true;
			}
		}
		if ($boolPriceInc)
		{
			$bCurrency = CModule::IncludeModule('currency');
			if ($bCurrency)
			{
				$rsCurrencies = CCurrency::GetList(($by1="sort"), ($order1="asc"));
				while ($arCurrency = $rsCurrencies->GetNext(true,true))
				{
					$arCurrencyList[] = $arCurrency;
				}
			}
		}
		unset($boolPriceInc);
	}
}

// Getting list data
if(array_key_exists("ELEMENT_CNT", $arVisibleColumnsMap))
{
	$arFilter["CNT_ALL"] = "Y";
	$arFilter["ELEMENT_SUBSECTIONS"] = "N";
	$rsData = CIBlockSection::GetMixedList(Array($by=>$order), $arFilter, true, $arSelectedFields);
}
else
{
	$rsData = CIBlockSection::GetMixedList(Array($by=>$order), $arFilter, false, $arSelectedFields);
}

$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart();

// Navigation setup
$lAdmin->NavText($rsData->GetNavPrint(htmlspecialcharsbx($arIBlock["SECTIONS_NAME"])));

$bSearch = CModule::IncludeModule('search');

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
	if (0 > $IBLOCK_ID)
		$IBLOCK_ID = 0;
	static $cache = array();
	if(!array_key_exists($IBLOCK_ID, $cache))
	{
		$rsIBlock = CIBlock::GetByID($IBLOCK_ID);
		if(!($cache[$IBLOCK_ID] = $rsIBlock->GetNext()))
			$cache[$IBLOCK_ID] = array("IBLOCK_TYPE_ID"=>"");
	}
	return $cache[$IBLOCK_ID]["IBLOCK_TYPE_ID"];
}

$arUsersCache = array();

$boolIBlockElementAdd = CIBlockSectionRights::UserHasRightTo($IBLOCK_ID, $find_section_section, "section_element_bind");

$availQuantityTrace = COption::GetOptionString("catalog", "default_quantity_trace", 'N');
$arQuantityTrace = array(
	"D" => GetMessage("IBLIST_DEFAULT_VALUE")." (".($availQuantityTrace=='Y' ? GetMessage("IBLIST_YES_VALUE") : GetMessage("IBLIST_NO_VALUE")).")",
	"Y" => GetMessage("IBLIST_YES_VALUE"),
	"N" => GetMessage("IBLIST_NO_VALUE"),
);
// List build
while($arRes = $rsData->NavNext(true, "f_"))
{
	$sec_list_url = htmlspecialcharsbx(CIBlock::GetAdminSectionListLink($IBLOCK_ID, array('find_section_section'=>$f_ID)));
	$el_edit_url = 'iblock_element_edit.php?WF=Y&amp;ID='.$f_ID.$sThisSectionUrl;
	$sec_edit_url = 'iblock_section_edit.php?ID='.$f_ID.$sThisSectionUrl;

	$arRes_orig = $arRes;
	if($f_TYPE=="E")
	{
		if($bWorkFlow)
		{
			$LAST_ID = CIBlockElement::WF_GetLast($arRes['ID']);
			if($LAST_ID!=$arRes['ID'])
			{
				$rsData2 = CIBlockElement::GetList(
						Array(),
						Array(
							"ID"=>$LAST_ID,
							"SHOW_HISTORY"=>"Y"
							),
						false,
						Array("nTopCount"=>1),
						$arSelectedFields
					);
				if(isset($arCatGroup))
				{
					$arRes_tmp = Array();
					foreach($arRes as $vv => $vval)
					{
						if(substr($vv, 0, 8) == "CATALOG_")
							$arRes_tmp[$vv] = $arRes[$vv];
					}
				}

				$arRes = $rsData2->NavNext(true, "f_");
				if(isset($arCatGroup))
					$arRes = array_merge($arRes, $arRes_tmp);
				$f_ID = $arRes_orig["ID"];
			}
			$lockStatus = $arRes_orig['LOCK_STATUS'];
		}
		elseif($bBizproc)
		{
			$lockStatus = call_user_func(array(ENTITY, "IsDocumentLocked"), $f_ID, "") ? "red" : "green";
		}
		else
		{
			$lockStatus = "";
		}
	}

	$boolEditPrice = false;
	if($f_TYPE=="S")
	{
		$bReadOnly = !CIBlockSectionRights::UserHasRightTo($IBLOCK_ID, $f_ID, "section_edit");
	}
	else
	{
		$bReadOnly = !CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $f_ID, "element_edit");
		$boolEditPrice = CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $f_ID, "element_edit_price");
	}

	if(in_array("CATALOG_QUANTITY_TRACE", $arSelectedFields))
	{
		$arRes['CATALOG_QUANTITY_TRACE'] = $arRes['CATALOG_QUANTITY_TRACE_ORIG'];
		$f_CATALOG_QUANTITY_TRACE = $f_CATALOG_QUANTITY_TRACE_ORIG;
	}

	if($f_TYPE=="S") // double click moves deeper
	{
		$arRes["PREVIEW_PICTURE"] = $arRes["PICTURE"];
		$row = $lAdmin->AddRow($f_TYPE.$f_ID, $arRes, $sec_list_url, GetMessage("IBLIST_A_LIST"));
	}
	else // in case of element take his action
	{
		$row = $lAdmin->AddRow($f_TYPE.$f_ID, $arRes);
	}

	if($f_TYPE=="S")
		$row->AddViewField("NAME", '<a href="'.$sec_list_url.'" class="adm-list-table-icon-link" title="'.GetMessage("IBLIST_A_LIST").'"><span class="adm-submenu-item-link-icon adm-list-table-icon iblock-section-icon"></span><span class="adm-list-table-link">'.$f_NAME.'</span></a>');
	else
		$row->AddViewField("NAME", '<a href="'.$el_edit_url.'" title="'.GetMessage("IBLIST_A_EDIT").'">'.$f_NAME.'</a>');
	if($bReadOnly)
	{
		$row->AddInputField("NAME", false);
		$row->AddCheckField("ACTIVE", false);
		$row->AddInputField("SORT", false);
		$row->AddInputField("CODE", false);
		$row->AddInputField("EXTERNAL_ID", false);
	}
	else
	{
		$row->AddInputField("NAME", Array('size'=>'35'));
		$row->AddCheckField("ACTIVE");
		$row->AddInputField("SORT", Array('size'=>'3'));
		$row->AddInputField("CODE");
		$row->AddInputField("EXTERNAL_ID");
	}

	if($bBizproc && $f_TYPE=="E")
		$row->AddCheckField("BP_PUBLISHED", false);

	if(array_key_exists("MODIFIED_BY", $arVisibleColumnsMap) && intval($f_MODIFIED_BY) > 0)
	{
		if(!array_key_exists($f_MODIFIED_BY, $arUsersCache))
		{
			$rsUser = CUser::GetByID($f_MODIFIED_BY);
			$arUsersCache[$f_MODIFIED_BY] = $rsUser->Fetch();
		}
		if($arUser = $arUsersCache[$f_MODIFIED_BY])
			$row->AddViewField("USER_NAME", '[<a href="user_edit.php?lang='.LANG.'&ID='.$f_MODIFIED_BY.'" title="'.GetMessage("IBLIST_A_USERINFO").'">'.$f_MODIFIED_BY."</a>]&nbsp;(".$arUser["LOGIN"].") ".$arUser["NAME"]." ".$arUser["LAST_NAME"]);
	}

	if(array_key_exists("CREATED_BY", $arVisibleColumnsMap) && intval($f_CREATED_BY) > 0)
	{
		if(!array_key_exists($f_CREATED_BY, $arUsersCache))
		{
			$rsUser = CUser::GetByID($f_CREATED_BY);
			$arUsersCache[$f_CREATED_BY] = $rsUser->Fetch();
		}
		if($arUser = $arUsersCache[$f_CREATED_BY])
			$row->AddViewField("CREATED_USER_NAME", '[<a href="user_edit.php?lang='.LANG.'&ID='.$f_CREATED_BY.'" title="'.GetMessage("IBLIST_A_USERINFO").'">'.$f_CREATED_BY."</a>]&nbsp;(".$arUser["LOGIN"].") ".$arUser["NAME"]." ".$arUser["LAST_NAME"]);
	}

	if (array_key_exists("PREVIEW_PICTURE", $arVisibleColumnsMap))
	{
		if ($bReadOnly)
			$row->AddViewFileField("PREVIEW_PICTURE", array(
				"IMAGE" => "Y",
				"PATH" => "Y",
				"FILE_SIZE" => "Y",
				"DIMENSIONS" => "Y",
				"IMAGE_POPUP" => "Y",
				"MAX_SIZE" => $maxImageSize,
				"MIN_SIZE" => $minImageSize,
				)
			);
		else
			$row->AddFileField("PREVIEW_PICTURE", array(
				"IMAGE" => "Y",
				"PATH" => "Y",
				"FILE_SIZE" => "Y",
				"DIMENSIONS" => "Y",
				"IMAGE_POPUP" => "Y",
				"MAX_SIZE" => $maxImageSize,
				"MIN_SIZE" => $minImageSize,
				), array(
					'upload' => true,
					'medialib' => false,
					'file_dialog' => false,
					'cloud' => true,
					'del' => true,
					'description' => false,
				)
			);
	}

	if (array_key_exists("DETAIL_PICTURE", $arVisibleColumnsMap))
	{
		if ($bReadOnly)
			$row->AddViewFileField("DETAIL_PICTURE", array(
				"IMAGE" => "Y",
				"PATH" => "Y",
				"FILE_SIZE" => "Y",
				"DIMENSIONS" => "Y",
				"IMAGE_POPUP" => "Y",
				"MAX_SIZE" => $maxImageSize,
				"MIN_SIZE" => $minImageSize,
				)
			);
		else
			$row->AddFileField("DETAIL_PICTURE", array(
				"IMAGE" => "Y",
				"PATH" => "Y",
				"FILE_SIZE" => "Y",
				"DIMENSIONS" => "Y",
				"IMAGE_POPUP" => "Y",
				"MAX_SIZE" => $maxImageSize,
				"MIN_SIZE" => $minImageSize,
				), array(
					'upload' => true,
					'medialib' => false,
					'file_dialog' => false,
					'cloud' => true,
					'del' => true,
					'description' => false,
				)
			);
	}

	if($f_TYPE=="S")
	{
		if(array_key_exists("ELEMENT_CNT", $arVisibleColumnsMap))
		{
			$row->AddViewField("ELEMENT_CNT", $f_ELEMENT_CNT.'('.IntVal(CIBlockSection::GetSectionElementsCount($f_ID, Array("CNT_ALL"=>"Y"))).')');
		}

		if(array_key_exists("SECTION_CNT", $arVisibleColumnsMap))
		{
			$arFilter = Array("IBLOCK_ID"=>$IBLOCK_ID, "SECTION_ID"=>$f_ID);
			$row->AddViewField("SECTION_CNT", " ".IntVal(CIBlockSection::GetCount($arFilter)));
		}
	}

	if($f_TYPE=="E")
	{
		if (array_key_exists("PREVIEW_TEXT", $arVisibleColumnsMap))
			$row->AddViewField("PREVIEW_TEXT", ($arRes["PREVIEW_TEXT_TYPE"]=="text" ? htmlspecialcharsex($arRes["PREVIEW_TEXT"]) : HTMLToTxt($arRes["PREVIEW_TEXT"])));
		if (array_key_exists("DETAIL_TEXT", $arVisibleColumnsMap))
			$row->AddViewField("DETAIL_TEXT", ($arRes["DETAIL_TEXT_TYPE"]=="text" ? htmlspecialcharsex($arRes["DETAIL_TEXT"]) : HTMLToTxt($arRes["DETAIL_TEXT"])));
		if($bWorkFlow || $bBizproc)
		{
			$lamp = '<span class="adm-lamp adm-lamp-in-list adm-lamp-'.$lockStatus.'"></span>';
			if($lockStatus=='red' && $arRes_orig['LOCKED_USER_NAME']!='')
				$row->AddViewField("LOCK_STATUS", $lamp.$arRes_orig['LOCKED_USER_NAME'].$unlock);
			else
				$row->AddViewField("LOCK_STATUS", $lamp);
		}

		if (!$bReadOnly)
		{
			$row->AddCalendarField("DATE_ACTIVE_FROM");
			$row->AddCalendarField("DATE_ACTIVE_TO");
			if (array_key_exists("PREVIEW_TEXT", $arVisibleColumnsMap))
			{
				$sHTML = '<input type="radio" name="FIELDS['.$f_TYPE.$f_ID.'][PREVIEW_TEXT_TYPE]" value="text" id="'.$f_TYPE.$f_ID.'PREVIEWtext"';
				if($arRes["PREVIEW_TEXT_TYPE"]!="html")
					$sHTML .= ' checked';
				$sHTML .= '><label for="'.$f_TYPE.$f_ID.'PREVIEWtext">text</label> /';
				$sHTML .= '<input type="radio" name="FIELDS['.$f_TYPE.$f_ID.'][PREVIEW_TEXT_TYPE]" value="html" id="'.$f_TYPE.$f_ID.'PREVIEWhtml"';
				if($arRes["PREVIEW_TEXT_TYPE"]=="html")
					$sHTML .= ' checked';
				$sHTML .= '><label for="'.$f_TYPE.$f_ID.'PREVIEWhtml">html</label><br>';
				$sHTML .= '<textarea rows="10" cols="50" name="FIELDS['.$f_TYPE.$f_ID.'][PREVIEW_TEXT]">'.htmlspecialcharsex($arRes["PREVIEW_TEXT"]).'</textarea>';
				$row->AddEditField("PREVIEW_TEXT", $sHTML);
			}
			if (array_key_exists("DETAIL_TEXT", $arVisibleColumnsMap))
			{
				$sHTML = '<input type="radio" name="FIELDS['.$f_TYPE.$f_ID.'][DETAIL_TEXT_TYPE]" value="text" id="'.$f_TYPE.$f_ID.'DETAILtext"';
				if($arRes["DETAIL_TEXT_TYPE"]!="html")
					$sHTML .= ' checked';
				$sHTML .= '><label for="'.$f_TYPE.$f_ID.'DETAILtext">text</label> /';
				$sHTML .= '<input type="radio" name="FIELDS['.$f_TYPE.$f_ID.'][DETAIL_TEXT_TYPE]" value="html" id="'.$f_TYPE.$f_ID.'DETAILhtml"';
				if($arRes["DETAIL_TEXT_TYPE"]=="html")
					$sHTML .= ' checked';
				$sHTML .= '><label for="'.$f_TYPE.$f_ID.'DETAILhtml">html</label><br>';
				$sHTML .= '<textarea rows="10" cols="50" name="FIELDS['.$f_TYPE.$f_ID.'][DETAIL_TEXT]">'.htmlspecialcharsex($arRes["DETAIL_TEXT"]).'</textarea>';
				$row->AddEditField("DETAIL_TEXT", $sHTML);
			}

			if (array_key_exists("TAGS", $arVisibleColumnsMap))
			{
				if ($bSearch)
				{
					$row->AddViewField("TAGS", $f_TAGS);
					$row->AddEditField("TAGS", InputTags("FIELDS[".$f_TYPE.$f_ID."][TAGS]", $arRes["TAGS"], $arIBlock["SITE_ID"]));
				}
				else
				{
					$row->AddInputField("TAGS");
				}
			}

			if(!empty($arWFStatusPerm))
				$row->AddSelectField("WF_STATUS_ID", $arWFStatusPerm);
			if($arRes_orig['WF_NEW']=='Y' || $arRes['WF_STATUS_ID']=='1')
				$row->AddViewField("WF_STATUS_ID", htmlspecialcharsex($arWFStatusAll[$arRes['WF_STATUS_ID']]));
			else
				$row->AddViewField("WF_STATUS_ID", '<a href="'.$el_edit_url.'" title="'.GetMessage("IBLIST_A_ED_TITLE").'">'.htmlspecialcharsex($arWFStatusAll[$arRes['WF_STATUS_ID']]).'</a> / <a href="'.'iblock_element_edit.php?ID='.$arRes_orig['ID'].$sThisSectionUrl.'" title="'.GetMessage("IBLIST_A_ED2_TITLE").'">'.htmlspecialcharsex($arWFStatusAll[$arRes_orig['WF_STATUS_ID']]).'</a>');
		}
		else
		{
			$row->AddCalendarField("DATE_ACTIVE_FROM", false);
			$row->AddCalendarField("DATE_ACTIVE_TO", false);
			$row->AddViewField("WF_STATUS_ID", htmlspecialcharsex($arWFStatusAll[$arRes['WF_STATUS_ID']]));
			if (array_key_exists("TAGS", $arVisibleColumnsMap))
				$row->AddViewField("TAGS", $f_TAGS);
		}
	}

	$row->AddViewField("ID", '<a href="'.($f_TYPE=="S"?$sec_edit_url:$el_edit_url).'" title="'.GetMessage("IBLIST_A_EDIT").'">'.$f_ID.'</a>');

	$arProperties = array();
	if($f_TYPE=="E" && count($arSelectedProps)>0)
	{
		$rsProperties = CIBlockElement::GetProperty($IBLOCK_ID, $arRes["ID"]);
		while($ar = $rsProperties->GetNext())
		{
			if(!array_key_exists($ar["ID"], $arProperties))
				$arProperties[$ar["ID"]] = array();
			$arProperties[$ar["ID"]][$ar["PROPERTY_VALUE_ID"]] = $ar;
		}

		foreach($arSelectedProps as $aProp)
		{
			$arViewHTML = array();
			$arEditHTML = array();
			if(strlen($aProp["USER_TYPE"])>0)
				$arUserType = CIBlockProperty::GetUserType($aProp["USER_TYPE"]);
			else
				$arUserType = array();
			$max_file_size_show=100000;

			$last_property_id = false;
			foreach($arProperties[$aProp["ID"]] as $prop_id => $prop)
			{
				$prop['PROPERTY_VALUE_ID'] = intval($prop['PROPERTY_VALUE_ID']);
				$VALUE_NAME = 'FIELDS['.$f_TYPE.$f_ID.'][PROPERTY_'.$prop['ID'].']['.$prop['PROPERTY_VALUE_ID'].'][VALUE]';
				$DESCR_NAME = 'FIELDS['.$f_TYPE.$f_ID.'][PROPERTY_'.$prop['ID'].']['.$prop['PROPERTY_VALUE_ID'].'][DESCRIPTION]';
				//View part
				if(array_key_exists("GetAdminListViewHTML", $arUserType))
				{
					$arViewHTML[] = call_user_func_array($arUserType["GetAdminListViewHTML"],
						array(
							$prop,
							array(
								"VALUE" => $prop["~VALUE"],
								"DESCRIPTION" => $prop["~DESCRIPTION"]
							),
							array(
								"VALUE" => $VALUE_NAME,
								"DESCRIPTION" => $DESCR_NAME,
								"MODE"=>"iblock_element_admin",
								"FORM_NAME"=>"form_".$sTableID,
							),
						));
				}
				elseif($prop['PROPERTY_TYPE']=='N')
					$arViewHTML[] = $prop["VALUE"];
				elseif($prop['PROPERTY_TYPE']=='S')
					$arViewHTML[] = $prop["VALUE"];
				elseif($prop['PROPERTY_TYPE']=='L')
					$arViewHTML[] = $prop["VALUE_ENUM"];
				elseif($prop['PROPERTY_TYPE']=='F')
				{
					$arViewHTML[] = CFileInput::Show('NO_FIELDS['.$prop['PROPERTY_VALUE_ID'].']', $prop["VALUE"], array(
						"IMAGE" => "Y",
						"PATH" => "Y",
						"FILE_SIZE" => "Y",
						"DIMENSIONS" => "Y",
						"IMAGE_POPUP" => "Y",
						"MAX_SIZE" => $maxImageSize,
						"MIN_SIZE" => $minImageSize,
						), array(
							'upload' => false,
							'medialib' => false,
							'file_dialog' => false,
							'cloud' => false,
							'del' => false,
							'description' => false,
						)
					);
				}
				elseif($prop['PROPERTY_TYPE']=='G')
				{
					if(intval($prop["VALUE"])>0)
					{
						$rsSection = CIBlockSection::GetList(Array(), Array("ID" => $prop["VALUE"]));
						if($arSection = $rsSection->GetNext())
						{
							$arViewHTML[] = $arSection['NAME'].
							' [<a href="'.
							'iblock_section_edit.php?'.
							'type='.GetIBlockTypeID($arSection['IBLOCK_ID']).
							'&amp;IBLOCK_ID='.$arSection['IBLOCK_ID'].
							'&amp;ID='.$arSection['ID'].
							'&amp;lang='.$lang.
							'" title="'.GetMessage("IBLIST_A_SEC_EDIT").'">'.$arSection['ID'].'</a>]';
						}
					}
				}
				elseif($prop['PROPERTY_TYPE']=='E')
				{
					if($t = GetElementName($prop["VALUE"]))
					{
						$arViewHTML[] = $t['NAME'].
						' [<a href="'.
						'iblock_element_edit.php'.
						'?WF=Y'.
						'&type='.GetIBlockTypeID($t['IBLOCK_ID']).
						'&amp;IBLOCK_ID='.$t['IBLOCK_ID'].
						'&amp;ID='.$t['ID'].
						'&amp;lang='.$lang.
						'" title="'.GetMessage("IBLIST_A_EL_EDIT").'">'.$t['ID'].'</a>]';
					}
				}
				//Edit Part
				$bUserMultiple = $prop["MULTIPLE"] == "Y" &&  array_key_exists("GetPropertyFieldHtmlMulty", $arUserType);
				if($bUserMultiple)
				{
					if($last_property_id != $prop["ID"])
					{
						$VALUE_NAME = 'FIELDS['.$f_TYPE.$f_ID.'][PROPERTY_'.$prop['ID'].']';
						$arEditHTML[] = call_user_func_array($arUserType["GetPropertyFieldHtmlMulty"], array(
							$prop,
							$arProperties[$prop["ID"]],
							array(
								"VALUE" => $VALUE_NAME,
								"DESCRIPTION" => $VALUE_NAME,
								"MODE"=>"iblock_element_admin",
								"FORM_NAME"=>"form_".$sTableID,
							)
						));
					}
				}
				elseif(array_key_exists("GetPropertyFieldHtml", $arUserType))
				{
					$arEditHTML[] = call_user_func_array($arUserType["GetPropertyFieldHtml"],
						array(
							$prop,
							array(
								"VALUE" => $prop["VALUE"],
								"DESCRIPTION" => $prop["DESCRIPTION"],
							),
							array(
								"VALUE" => $VALUE_NAME,
								"DESCRIPTION" => $DESCR_NAME,
								"MODE"=>"iblock_element_admin",
								"FORM_NAME"=>"form_".$sTableID,
							),
						));
				}
				elseif($prop['PROPERTY_TYPE']=='N' || $prop['PROPERTY_TYPE']=='S')
				{
					if($prop["ROW_COUNT"] > 1)
						$html = '<textarea name="'.$VALUE_NAME.'" cols="'.$prop["COL_COUNT"].'" rows="'.$prop["ROW_COUNT"].'">'.$prop["VALUE"].'</textarea>';
					else
						$html = '<input type="text" name="'.$VALUE_NAME.'" value="'.$prop["VALUE"].'" size="'.$prop["COL_COUNT"].'">';
					if($prop["WITH_DESCRIPTION"] == "Y")
						$html .= ' <span title="'.GetMessage("IBLIST_A_PROP_DESC_TITLE").'">'.GetMessage("IBLIST_A_PROP_DESC").
							'<input type="text" name="'.$DESCR_NAME.'" value="'.$prop["DESCRIPTION"].'" size="18"></span>';
					$arEditHTML[] = $html;
				}
				elseif($prop['PROPERTY_TYPE']=='L' && ($last_property_id!=$prop["ID"]))
				{
					$VALUE_NAME = 'FIELDS['.$f_TYPE.$f_ID.'][PROPERTY_'.$prop['ID'].'][]';
					$arValues = array();
					foreach($arProperties[$prop["ID"]] as $g_prop)
					{
						$g_prop = intval($g_prop["VALUE"]);
						if($g_prop > 0)
							$arValues[$g_prop] = $g_prop;
					}
					if($prop['LIST_TYPE']=='C')
					{
						if($prop['MULTIPLE'] == "Y" || count($arSelect[$prop['ID']]) == 1)
						{
							$html = '<input type="hidden" name="'.$VALUE_NAME.'" value="">';
							foreach($arSelect[$prop['ID']] as $value => $display)
							{
								$html .= '<input type="checkbox" name="'.$VALUE_NAME.'" id="id'.$uniq_id.'" value="'.$value.'"';
								if(array_key_exists($value, $arValues))
									$html .= ' checked';
								$html .= '>&nbsp;<label for="id'.$uniq_id.'">'.$display.'</label><br>';
								$uniq_id++;
							}
						}
						else
						{
							$html = '<input type="radio" name="'.$VALUE_NAME.'" id="id'.$uniq_id.'" value=""';
							if(count($arValues) < 1)
								$html .= ' checked';
							$html .= '>&nbsp;<label for="id'.$uniq_id.'">'.GetMessage("IBLIST_A_PROP_NOT_SET").'</label><br>';
							$uniq_id++;
							foreach($arSelect[$prop['ID']] as $value => $display)
							{
								$html .= '<input type="radio" name="'.$VALUE_NAME.'" id="id'.$uniq_id.'" value="'.$value.'"';
								if(array_key_exists($value, $arValues))
									$html .= ' checked';
								$html .= '>&nbsp;<label for="id'.$uniq_id.'">'.$display.'</label><br>';
								$uniq_id++;
							}
						}
					}
					else
					{
						$html = '<select name="'.$VALUE_NAME.'" size="'.$prop["MULTIPLE_CNT"].'" '.($prop["MULTIPLE"]=="Y"?"multiple":"").'>';
						$html .= '<option value=""'.(count($arValues) < 1? ' selected': '').'>'.GetMessage("IBLIST_A_PROP_NOT_SET").'</option>';
						foreach($arSelect[$prop['ID']] as $value => $display)
						{
							$html .= '<option value="'.$value.'"';
							if(array_key_exists($value, $arValues))
								$html .= ' selected';
							$html .= '>'.$display.'</option>'."\n";
						}
						$html .= "</select>\n";
					}
					$arEditHTML[] = $html;
				}
				elseif($prop['PROPERTY_TYPE']=='F' && ($last_property_id != $prop["ID"]))
				{
					if($prop['MULTIPLE'] == "Y")
					{
						$inputName = array();
						foreach($arProperties[$prop["ID"]] as $g_prop)
						{
							$inputName['FIELDS['.$f_TYPE.$f_ID.'][PROPERTY_'.$prop['ID'].']['.$g_prop['PROPERTY_VALUE_ID'].'][VALUE]'] = $g_prop["VALUE"];
						}

						$arEditHTML[] = CFileInput::ShowMultiple($inputName, 'FIELDS['.$f_TYPE.$f_ID.'][PROPERTY_'.$prop['ID'].'][n#IND#]', array(
							"IMAGE" => "Y",
							"PATH" => "Y",
							"FILE_SIZE" => "Y",
							"DIMENSIONS" => "Y",
							"IMAGE_POPUP" => "Y",
							"MAX_SIZE" => $maxImageSize,
							"MIN_SIZE" => $minImageSize,
							), false, array(
								'upload' => true,
								'medialib' => false,
								'file_dialog' => false,
								'cloud' => false,
								'del' => true,
								'description' => $prop["WITH_DESCRIPTION"]=="Y",
							)
						);
					}
					else
					{
						$arEditHTML[] = CFileInput::Show($VALUE_NAME, $prop["VALUE"], array(
							"IMAGE" => "Y",
							"PATH" => "Y",
							"FILE_SIZE" => "Y",
							"DIMENSIONS" => "Y",
							"IMAGE_POPUP" => "Y",
							"MAX_SIZE" => $maxImageSize,
							"MIN_SIZE" => $minImageSize,
							), array(
								'upload' => true,
								'medialib' => false,
								'file_dialog' => false,
								'cloud' => false,
								'del' => true,
								'description' => $prop["WITH_DESCRIPTION"]=="Y",
							)
						);
					}
				}
				elseif(($prop['PROPERTY_TYPE']=='G') && ($last_property_id!=$prop["ID"]))
				{
					$VALUE_NAME = 'FIELDS['.$f_TYPE.$f_ID.'][PROPERTY_'.$prop['ID'].'][]';
					$arValues = array();
					foreach($arProperties[$prop["ID"]] as $g_prop)
					{
						$g_prop = intval($g_prop["VALUE"]);
						if($g_prop > 0)
							$arValues[$g_prop] = $g_prop;
					}
					$html = '<select name="'.$VALUE_NAME.'" size="'.$prop["MULTIPLE_CNT"].'" '.($prop["MULTIPLE"]=="Y"?"multiple":"").'>';
					$html .= '<option value=""'.(count($arValues) < 1? ' selected': '').'>'.GetMessage("IBLIST_A_PROP_NOT_SET").'</option>';
					foreach($arSelect[$prop['ID']] as $value => $display)
					{
						$html .= '<option value="'.$value.'"';
						if(array_key_exists($value, $arValues))
							$html .= ' selected';
						$html .= '>'.$display.'</option>'."\n";
					}
					$html .= "</select>\n";
					$arEditHTML[] = $html;
				}
				elseif($prop['PROPERTY_TYPE']=='E')
				{
					$VALUE_NAME = 'FIELDS['.$f_TYPE.$f_ID.'][PROPERTY_'.$prop['ID'].']['.$prop['PROPERTY_VALUE_ID'].']';
					if($t = GetElementName($prop["VALUE"]))
					{
						$arEditHTML[] = '<input type="text" name="'.$VALUE_NAME.'" id="'.$VALUE_NAME.'" value="'.$prop["VALUE"].'" size="5">'.
						'<input type="button" value="..." onClick="jsUtils.OpenWindow(\'iblock_element_search.php?lang='.LANG.'&amp;IBLOCK_ID='.$prop["LINK_IBLOCK_ID"].'&amp;n='.urlencode($VALUE_NAME).'\', 600, 500);">'.
						'&nbsp;<span id="sp_'.$VALUE_NAME.'" >'.$t['NAME'].'</span>';
					}
					else
					{
						$arEditHTML[] = '<input type="text" name="'.$VALUE_NAME.'" id="'.$VALUE_NAME.'" value="" size="5">'.
						'<input type="button" value="..." onClick="jsUtils.OpenWindow(\'iblock_element_search.php?lang='.LANG.'&amp;IBLOCK_ID='.$prop["LINK_IBLOCK_ID"].'&amp;n='.urlencode($VALUE_NAME).'\', 600, 500);">'.
						'&nbsp;<span id="sp_'.$VALUE_NAME.'" ></span>';
					}
				}
				$last_property_id = $prop['ID'];
			}
			$table_id = md5($f_TYPE.$f_ID.':'.$aProp['ID']);
			if($aProp["MULTIPLE"] == "Y")
			{
				$VALUE_NAME = 'FIELDS['.$f_TYPE.$f_ID.'][PROPERTY_'.$prop['ID'].'][n0][VALUE]';
				$DESCR_NAME = 'FIELDS['.$f_TYPE.$f_ID.'][PROPERTY_'.$prop['ID'].'][n0][DESCRIPTION]';
				if(array_key_exists("GetPropertyFieldHtmlMulty", $arUserType))
				{
				}
				elseif(array_key_exists("GetPropertyFieldHtml", $arUserType))
				{
					$arEditHTML[] = call_user_func_array($arUserType["GetPropertyFieldHtml"],
						array(
							$prop,
							array(
								"VALUE" => "",
								"DESCRIPTION" => "",
							),
							array(
								"VALUE" => $VALUE_NAME,
								"DESCRIPTION" => $DESCR_NAME,
								"MODE"=>"iblock_element_admin",
								"FORM_NAME"=>"form_".$sTableID,
							),
						));
				}
				elseif($prop['PROPERTY_TYPE']=='N' || $prop['PROPERTY_TYPE']=='S')
				{
					if($prop["ROW_COUNT"] > 1)
						$html = '<textarea name="'.$VALUE_NAME.'" cols="'.$prop["COL_COUNT"].'" rows="'.$prop["ROW_COUNT"].'"></textarea>';
					else
						$html = '<input type="text" name="'.$VALUE_NAME.'" value="" size="'.$prop["COL_COUNT"].'">';
					if($prop["WITH_DESCRIPTION"] == "Y")
						$html .= ' <span title="'.GetMessage("IBLIST_A_PROP_DESC_TITLE").'">'.GetMessage("IBLIST_A_PROP_DESC").'<input type="text" name="'.$DESCR_NAME.'" value="" size="18"></span>';
					$arEditHTML[] = $html;
				}
				elseif($prop['PROPERTY_TYPE']=='F')
				{
				}
				elseif($prop['PROPERTY_TYPE']=='E')
				{
					$VALUE_NAME = 'FIELDS['.$f_TYPE.$f_ID.'][PROPERTY_'.$prop['ID'].'][n0]';
					$arEditHTML[] = '<input type="text" name="'.$VALUE_NAME.'" id="'.$VALUE_NAME.'" value="" size="5">'.
						'<input type="button" value="..." onClick="jsUtils.OpenWindow(\'iblock_element_search.php?lang='.LANG.'&amp;IBLOCK_ID='.$prop["LINK_IBLOCK_ID"].'&amp;n='.urlencode($VALUE_NAME).'\', 600, 500);">'.
						'&nbsp;<span id="sp_'.$VALUE_NAME.'" ></span>';
				}

				if(
					$prop["PROPERTY_TYPE"] !== "G"
					&& $prop["PROPERTY_TYPE"] !== "L"
					&& $prop["PROPERTY_TYPE"] !== "F"
					&& !$bUserMultiple
				)
					$arEditHTML[] = '<input type="button" value="'.GetMessage("IBLIST_A_PROP_ADD").'" onClick="addNewRow(\'tb'.$table_id.'\')">';
			}
			if(count($arViewHTML) > 0)
			{
				if($prop["PROPERTY_TYPE"] == "F")
					$row->AddViewField("PROPERTY_".$aProp['ID'], implode("", $arViewHTML));
				else
					$row->AddViewField("PROPERTY_".$aProp['ID'], implode(" / ", $arViewHTML));
			}
			if(!$bReadOnly && count($arEditHTML) > 0)
				$row->AddEditField("PROPERTY_".$aProp['ID'], '<table id="tb'.$table_id.'" border=0 cellpadding=0 cellspacing=0><tr><td nowrap>'.implode("</td></tr><tr><td nowrap>", $arEditHTML).'</td></tr></table>');
		}
	}
	if($f_TYPE == "E")
	{
		if (!$bReadOnly)
		{
			if ($boolEditPrice && $USER->CanDoOperation('catalog_price'))
			{
				if (COption::GetOptionString("catalog", "default_use_store_control") == "Y")
				{
					$row->AddInputField("CATALOG_QUANTITY", false);
				}
				else
				{
					$row->AddInputField("CATALOG_QUANTITY");
				}
				$row->AddSelectField("CATALOG_QUANTITY_TRACE", $arQuantityTrace);
				$row->AddInputField("CATALOG_WEIGHT");
			}
			elseif ($USER->CanDoOperation('catalog_read'))
			{
				$row->AddInputField("CATALOG_QUANTITY", false);
				$row->AddSelectField("CATALOG_QUANTITY_TRACE", $arQuantityTrace, false);
				$row->AddInputField("CATALOG_WEIGHT", false);
			}
		}
		else
		{
			if ($bCatalog)
			{
				$row->AddInputField("CATALOG_QUANTITY", false);
				$row->AddSelectField("CATALOG_QUANTITY_TRACE", $arQuantityTrace, false);
				$row->AddInputField("CATALOG_WEIGHT", false);
			}
		}
	}
	if($f_TYPE == "E")
	{
		if ($bCatalog)
		{
			if (isset($arCatGroup) && !empty($arCatGroup))
			{
				foreach($arCatGroup as $CatGroup)
				{
					if (array_key_exists("CATALOG_GROUP_".$CatGroup["ID"], $arVisibleColumnsMap))
					{
						$price = "";
						$sHTML = "";
						$selectCur = "";
						if ($bCurrency)
						{
							$price = CurrencyFormat($arRes["CATALOG_PRICE_".$CatGroup["ID"]],$arRes["CATALOG_CURRENCY_".$CatGroup["ID"]]);
							if ($USER->CanDoOperation('catalog_price') && $boolEditPrice)
							{
								$selectCur = '<select name="CATALOG_CURRENCY['.$f_ID.']['.$CatGroup["ID"].']" id="CATALOG_CURRENCY['.$f_ID.']['.$CatGroup["ID"].']"';
								if (intval($arRes["CATALOG_EXTRA_ID_".$CatGroup["ID"]])>0)
									$selectCur .= ' disabled readonly';
								if ($CatGroup["BASE"]=="Y")
									$selectCur .= ' onchange="top.ChangeBaseCurrency('.$f_ID.')"';
								$selectCur .= '>';
								foreach ($arCurrencyList as &$arOneCurrency)
								{
									$selectCur .= '<option value="'.$arOneCurrency["CURRENCY"].'"';
									if ($arOneCurrency["~CURRENCY"] == $arRes["CATALOG_CURRENCY_".$CatGroup["ID"]])
										$selectCur .= ' selected';
									$selectCur .= '>'.$arOneCurrency["CURRENCY"].'</option>';
								}
								$selectCur .= '</select>';
							}
						}
						else
						{
							$price = $arRes["CATALOG_PRICE_".$CatGroup["ID"]]." ".$arRes["CATALOG_CURRENCY_".$CatGroup["ID"]];
						}

						$row->AddViewField("CATALOG_GROUP_".$CatGroup["ID"], $price);
						if ($USER->CanDoOperation('catalog_price') && $boolEditPrice)
						{
							$sHTML = '<input type="text" size="5" id="CATALOG_PRICE['.$f_ID.']['.$CatGroup["ID"].']" name="CATALOG_PRICE['.$f_ID.']['.$CatGroup["ID"].']" value="'.$arRes["CATALOG_PRICE_".$CatGroup["ID"]].'"';
							if ($CatGroup["BASE"]=="Y")
								$sHTML .= ' onchange="top.ChangeBasePrice('.$f_ID.')"';
							if(intval($arRes["CATALOG_EXTRA_ID_".$CatGroup["ID"]])>0)
								$sHTML .= ' disabled readonly';
							$sHTML .= '> '.$selectCur;
							if (intval($arRes["CATALOG_EXTRA_ID_".$CatGroup["ID"]])>0)
								$sHTML .= '<input type="hidden" id="CATALOG_EXTRA['.$f_ID.']['.$CatGroup["ID"].']" name="CATALOG_EXTRA['.$f_ID.']['.$CatGroup["ID"].']" value="'.$arRes["CATALOG_EXTRA_ID_".$CatGroup["ID"]].'">';

							$sHTML .= '<input type="hidden" name="CATALOG_old_PRICE['.$f_ID.']['.$CatGroup["ID"].']" value="'.$arRes["CATALOG_PRICE_".$CatGroup["ID"]].'">';
							$sHTML .= '<input type="hidden" name="CATALOG_old_CURRENCY['.$f_ID.']['.$CatGroup["ID"].']" value="'.$arRes["CATALOG_CURRENCY_".$CatGroup["ID"]].'">';
							$sHTML .= '<input type="hidden" name="CATALOG_PRICE_ID['.$f_ID.']['.$CatGroup["ID"].']" value="'.$arRes["CATALOG_PRICE_ID_".$CatGroup["ID"]].'">';
							$sHTML .= '<input type="hidden" name="CATALOG_QUANTITY_FROM['.$f_ID.']['.$CatGroup["ID"].']" value="'.$arRes["CATALOG_QUANTITY_FROM_".$CatGroup["ID"]].'">';
							$sHTML .= '<input type="hidden" name="CATALOG_QUANTITY_TO['.$f_ID.']['.$CatGroup["ID"].']" value="'.$arRes["CATALOG_QUANTITY_TO_".$CatGroup["ID"]].'">';

							$row->AddEditField("CATALOG_GROUP_".$CatGroup["ID"], $sHTML);
						}
					}
				}
			}
		}
	}

	if ($bBizproc)
	{
		if ($f_TYPE == "E")
		{
			$arDocumentStates = CBPDocument::GetDocumentStates(
				array(MODULE_ID, ENTITY, DOCUMENT_TYPE),
				array(MODULE_ID, ENTITY, $f_ID)
			);

			$arRes["CURENT_USER_GROUPS"] = $USER->GetUserGroupArray();
			if ($arRes["CREATED_BY"] == $USER->GetID())
				$arRes["CURENT_USER_GROUPS"][] = "Author";

			$arStr = array();
			$arStr1 = array();
			foreach ($arDocumentStates as $kk => $vv)
			{
				$canViewWorkflow = call_user_func(array(ENTITY, "CanUserOperateDocument"),
					CBPCanUserOperateOperation::ViewWorkflow,
					$USER->GetID(),
					$f_ID,
					array("AllUserGroups" => $arRes["CURENT_USER_GROUPS"], "DocumentStates" => $arDocumentStates, "WorkflowId" => $kk)
				);
				if (!$canViewWorkflow)
					continue;

				$arStr1[$vv["TEMPLATE_ID"]] = $vv["TEMPLATE_NAME"];
				$arStr[$vv["TEMPLATE_ID"]] .= "<a href=\"/bitrix/admin/bizproc_log.php?ID=".$kk."\">".(strlen($vv["STATE_TITLE"]) > 0 ? $vv["STATE_TITLE"] : $vv["STATE_NAME"])."</a><br />";

				if (strlen($vv["ID"]) > 0)
				{
					$arTasks = CBPDocument::GetUserTasksForWorkflow($USER->GetID(), $vv["ID"]);
					foreach ($arTasks as $arTask)
					{
						$arStr[$vv["TEMPLATE_ID"]] .= GetMessage("IBLIST_A_BP_TASK").":<br /><a href=\"bizproc_task.php?id=".$arTask["ID"]."\" title=\"".$arTask["DESCRIPTION"]."\">".$arTask["NAME"]."</a><br /><br />";
					}
				}
			}

			$str = "";
			foreach ($arStr as $k => $v)
			{
				$row->AddViewField("WF_".$k, $v);
				$str .= "<b>".(strlen($arStr1[$k]) > 0 ? $arStr1[$k] : GetMessage("IBLIST_BP"))."</b>:<br />".$v."<br />";
			}

			$row->AddViewField("BIZPROC", $str);
		}
	}

	$arActions = Array();

	if($f_ACTIVE == "Y")
	{
		$arActive = array(
			"TEXT" => GetMessage("IBLIST_A_DEACTIVATE"),
			"ACTION" => $lAdmin->ActionDoGroup($f_TYPE.$f_ID, "deactivate", $sThisSectionUrl),
			"ONCLICK" => "",
		);
	}
	else
	{
		$arActive = array(
			"TEXT" => GetMessage("IBLIST_A_ACTIVATE"),
			"ACTION" => $lAdmin->ActionDoGroup($f_TYPE.$f_ID, "activate", $sThisSectionUrl),
			"ONCLICK" => "",
		);
	}

	if($f_TYPE=="S")
	{
		$edit_url = 'iblock_section_edit.php?ID='.$f_ID.$sThisSectionUrl;
		if(CIBlockSectionRights::UserHasRightTo($IBLOCK_ID, $f_ID, "section_edit"))
			$arActions[] = array(
				"ICON" => "edit",
				"TEXT" => GetMessage("IBLOCK_CHANGE"),
				"ACTION" => $lAdmin->ActionRedirect($edit_url),
				"DEFAULT" => true,
			);

		if(CIBlockSectionRights::UserHasRightTo($IBLOCK_ID, $f_ID, "section_delete"))
			$arActions[] = array(
				"ICON" => "delete",
				"TEXT" => GetMessage("MAIN_DELETE"),
				"ACTION" => "if(confirm('".GetMessageJS("IBLOCK_CONFIRM_DEL_MESSAGE")."')) ".$lAdmin->ActionDoGroup($f_TYPE.$f_ID, "delete", $sThisSectionUrl),
			);
	}
	elseif($bWorkFlow)
	{
		if (CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $f_ID, "element_edit_any_wf_status"))
			$STATUS_PERMISSION = 2;
		else
			$STATUS_PERMISSION = CIBlockElement::WF_GetStatusPermission($arRes["WF_STATUS_ID"]);

		$intMinPerm = 2;

		$arUnLock = Array(
			"ICON" => "unlock",
			"TEXT" => GetMessage("IBLIST_A_UNLOCK"),
			"TITLE" => GetMessage("IBLIST_A_UNLOCK_ALT"),
			"ACTION" => "if(confirm('".GetMessageJS("IBLIST_A_UNLOCK_CONFIRM")."')) ".$lAdmin->ActionDoGroup($f_TYPE.$arRes_orig['ID'], "unlock", $sThisSectionUrl),
		);

		if ($arRes_orig['LOCK_STATUS']=="red")
		{
			if (CWorkflow::IsAdmin())
				$arActions[] = $arUnLock;
		}
		else
		{
			/*
			 * yellow unlock
			 * edit
			 * copy
			 * history
			 * view (?)
			 * edit_orig (?)
			 * delete
			 */
		if (
				CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $f_ID, "element_edit")
				&& (2 <= $STATUS_PERMISSION)
			)
			{
				if ($arRes_orig['LOCK_STATUS']=="yellow")
				{
					$arActions[] = $arUnLock;
					$arActions[] = array("SEPARATOR"=>true);
				}

				$arActions[] = array(
					"ICON" => "edit",
					"TEXT" => GetMessage("IBLOCK_CHANGE"),
					"DEFAULT" => true,
					"ACTION" => $lAdmin->ActionRedirect('iblock_element_edit.php?WF=Y&ID='.$arRes_orig['ID'].$sThisSectionUrl),
				);
				$arActions[] = $arActive;
			}

			if (
				$boolIBlockElementAdd
				&& (2 <= $STATUS_PERMISSION)
			)
			{
				$arActions[] = array(
					"ICON" => "copy",
					"TEXT" => GetMessage("IBLIST_A_COPY_ELEMENT"),
					"ACTION" => $lAdmin->ActionRedirect('iblock_element_edit.php?WF=Y&ID='.$arRes_orig['ID'].$sThisSectionUrl."&action=copy"),
				);
			}

			$arActions[] = array(
				"ICON" => "history",
				"TEXT" => GetMessage("IBLIST_A_HIST"),
				"TITLE" => GetMessage("IBLIST_A_HISTORY_ALT"),
				"ACTION" => $lAdmin->ActionRedirect('iblock_history_list.php?ELEMENT_ID='.$arRes_orig['ID'].$sThisSectionUrl),
			);

			if(strlen($f_DETAIL_PAGE_URL)>0)
			{
				$tmpVar = CIBlock::ReplaceDetailUrl($arRes_orig["DETAIL_PAGE_URL"], $arRes_orig, true, "E");
				if (
					$arRes_orig['WF_NEW']=="Y"
					&& CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $f_ID, "element_edit")
					&& (2 <= $STATUS_PERMISSION)
				) // not yet published element under workflow
				{
					$arActions[] = array("SEPARATOR"=>true);
					$arActions[] = array(
						"ICON" => "view",
						"TEXT" => GetMessage("IBLIST_A_VIEW_WF"),
						"TITLE" => GetMessage("IBLIST_A_VIEW_WF_ALT"),
						"ACTION" => $lAdmin->ActionRedirect(htmlspecialcharsbx($tmpVar).((strpos($tmpVar, "?") !== false) ? "&" : "?")."show_workflow=Y"),
					);
				}
				elseif (
					$arRes["WF_STATUS_ID"] > 1
					&& CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $f_ID, "element_edit")
					&& CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $f_ID, "element_edit_any_wf_status")
				)
				{
					$arActions[] = array("SEPARATOR"=>true);
					$arActions[] = array(
						"ICON" => "view",
						"TEXT" => GetMessage("IBLIST_A_ADMIN_VIEW"),
						"TITLE" => GetMessage("IBLIST_A_VIEW_WF_ALT"),
						"ACTION" => $lAdmin->ActionRedirect(htmlspecialcharsbx($tmpVar)),
					);

					$arActions[] = array(
						"ICON" => "view",
						"TEXT" => GetMessage("IBLIST_A_VIEW_WF"),
						"TITLE" => GetMessage("IBLIST_A_VIEW_WF_ALT"),
						"ACTION" => $lAdmin->ActionRedirect(htmlspecialcharsbx($tmpVar).((strpos($tmpVar, "?") !== false) ? "&" : "?")."show_workflow=Y"),
					);
				}
				else
				{
					if (
						CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $f_ID, "element_edit")
						&& (2 <= $STATUS_PERMISSION)
					)
					$arActions[] = array("SEPARATOR"=>true);
					$arActions[] = array(
						"ICON" => "view",
						"TEXT" => GetMessage("IBLIST_A_ADMIN_VIEW"),
						"TITLE" => GetMessage("IBLIST_A_VIEW_WF_ALT"),
						"ACTION" => $lAdmin->ActionRedirect(htmlspecialcharsbx($tmpVar)),
					);
				}
			}

			if (
				$arRes["WF_STATUS_ID"] > 1
				&& CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $f_ID, "element_edit")
				&& CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $f_ID, "element_edit_any_wf_status")
			)
			{
				$arActions[] = array(
					"ICON" => "edit_orig",
					"TEXT" => GetMessage("IBLIST_A_ORIG_ED"),
					"TITLE" => GetMessage("IBLIST_A_ORIG_ED_TITLE"),
					"ACTION" => $lAdmin->ActionRedirect('iblock_element_edit.php?ID='.$arRes_orig['ID'].$sThisSectionUrl),
				);
			}

			if (
				CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $f_ID, "element_delete")
				&& (2 <= $STATUS_PERMISSION)
			)
			{
				if (!CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $f_ID, "element_edit_any_wf_status"))
					$intMinPerm = CIBlockElement::WF_GetStatusPermission($arRes["WF_STATUS_ID"], $f_ID);
				if (2 <= $intMinPerm)
				{
					$arActions[] = array("SEPARATOR"=>true);
					$arActions[] = array(
						"ICON" => "delete",
						"TEXT" => GetMessage('MAIN_DELETE'),
						"TITLE" => GetMessage("IBLOCK_DELETE_ALT"),
						"ACTION" => "if(confirm('".GetMessageJS('IBLOCK_CONFIRM_DEL_MESSAGE')."')) ".$lAdmin->ActionDoGroup($f_TYPE.$arRes_orig['ID'], "delete", $sThisSectionUrl),
					);
				}
			}
		}
	}
	elseif($bBizproc)
	{
		$bWritePermission = call_user_func(array(ENTITY, "CanUserOperateDocument"),
			CBPCanUserOperateOperation::WriteDocument,
			$USER->GetID(),
			$f_ID,
			array("IBlockId" => $IBLOCK_ID, "UserGroups" => $USER->GetUserGroupArray(), "AllUserGroups" => $arRes["CURENT_USER_GROUPS"], "DocumentStates" => $arDocumentStates)
		);
		$bStartWorkflowPermission = call_user_func(array(ENTITY, "CanUserOperateDocument"),
			CBPCanUserOperateOperation::StartWorkflow,
			$USER->GetID(),
			$f_ID,
			array("IBlockId" => $IBLOCK_ID, "UserGroups" => $USER->GetUserGroupArray(), "AllUserGroups" => $arRes["CURENT_USER_GROUPS"], "DocumentStates" => $arDocumentStates)
		);

		if ($bStartWorkflowPermission)
		{
			$arActions[] = array(
				"ICON" => "",
				"TEXT" => GetMessage("IBLIST_BP_START"),
				"ACTION" => $lAdmin->ActionRedirect('iblock_start_bizproc.php?document_id='.$f_ID.'&document_type=iblock_'.$IBLOCK_ID.'&back_url='.urlencode($APPLICATION->GetCurPageParam("", array("mode", "table_id"))).''),
			);
		}

		if ($lockStatus == "red")
		{
			if (CBPDocument::IsAdmin())
			{
				$arActions[] = Array(
					"ICON" => "unlock",
					"TEXT" => GetMessage("IBLIST_A_UNLOCK"),
					"TITLE" => GetMessage("IBLIST_A_UNLOCK_ALT"),
					"ACTION" => "if(confirm('".GetMessageJS("IBLIST_A_UNLOCK_CONFIRM")."')) ".$lAdmin->ActionDoGroup($f_TYPE.$f_ID, "unlock", $sThisSectionUrl),
				);
			}
		}
		elseif ($bWritePermission)
		{
			$arActions[] = array(
				"ICON" => "edit",
				"TEXT" => GetMessage("IBLOCK_CHANGE"),
				"DEFAULT" => true,
				"ACTION" => $lAdmin->ActionRedirect('iblock_element_edit.php?WF=Y&ID='.$f_ID.$sThisSectionUrl),
			);
			$arActions[] = $arActive;

			$arActions[] = array(
				"ICON" => "copy",
				"TEXT" => GetMessage("IBLIST_A_COPY_ELEMENT"),
				"ACTION" => $lAdmin->ActionRedirect('iblock_element_edit.php?WF=Y&ID='.$f_ID.$sThisSectionUrl."&action=copy"),
			);

			$arActions[] = array(
				"ICON" => "history",
				"TEXT" => GetMessage("IBLIST_A_HIST"),
				"TITLE" => GetMessage("IBLIST_A_HISTORY_ALT"),
				"ACTION" => $lAdmin->ActionRedirect('iblock_bizproc_history.php?document_id='.$f_ID.'&back_url='.urlencode($APPLICATION->GetCurPageParam("", array())).''),
			);

			$arActions[] = array("SEPARATOR"=>true);
			$arActions[] = array(
				"ICON" => "delete",
				"TEXT" => GetMessage('MAIN_DELETE'),
				"TITLE" => GetMessage("IBLOCK_DELETE_ALT"),
				"ACTION" => "if(confirm('".GetMessageJS('IBLOCK_CONFIRM_DEL_MESSAGE')."')) ".$lAdmin->ActionDoGroup($f_TYPE.$f_ID, "delete", $sThisSectionUrl),
			);
		}
	}
	else
	{
		if (CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $f_ID, "element_edit"))
		{
			$arActions[] = array(
				"ICON" => "edit",
				"DEFAULT" => true,
				"TEXT" => GetMessage("IBLOCK_CHANGE"),
				"ACTION" => $lAdmin->ActionRedirect('iblock_element_edit.php?ID='.$arRes_orig['ID'].$sThisSectionUrl)
			);
			$arActions[] = $arActive;
		}

		if ($boolIBlockElementAdd && CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $f_ID, "element_edit"))
		{
			$arActions[] = array(
				"ICON" => "copy",
				"TEXT" => GetMessage("IBLIST_A_COPY_ELEMENT"),
				"ACTION" => $lAdmin->ActionRedirect('iblock_element_edit.php?ID='.$arRes_orig['ID'].$sThisSectionUrl."&action=copy"),
			);
		}

		if(strlen($f_DETAIL_PAGE_URL) > 0)
		{
			$tmpVar = CIBlock::ReplaceDetailUrl($arRes["DETAIL_PAGE_URL"], $arRes_orig, true, "E");
			$arActions[] = array(
				"ICON" => "view",
				"TEXT" => GetMessage("IBLIST_A_ADMIN_VIEW"),
				"TITLE" => GetMessage("IBLIST_A_VIEW_WF_ALT"),
				"ACTION" => $lAdmin->ActionRedirect(htmlspecialcharsbx($tmpVar)),
			);
		}

		if (CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $f_ID, "element_delete"))
		{
			if (!empty($arActions))
				$arActions[] = array("SEPARATOR"=>true);
			$arActions[] = array(
				"ICON" => "delete",
				"TEXT" => GetMessage('MAIN_DELETE'),
				"TITLE" => GetMessage("IBLOCK_DELETE_ALT"),
				"ACTION" => "if(confirm('".GetMessageJS('IBLOCK_CONFIRM_DEL_MESSAGE')."')) ".$lAdmin->ActionDoGroup($f_TYPE.$arRes_orig['ID'], "delete", $sThisSectionUrl),
			);
		}
	}
	$row->AddActions($arActions);

}

// List footer
$lAdmin->AddFooter(
	array(
		array("title"=>GetMessage("MAIN_ADMIN_LIST_SELECTED"), "value"=>$rsData->SelectedRowsCount()),
		array("counter"=>true, "title"=>GetMessage("MAIN_ADMIN_LIST_CHECKED"), "value"=>"0"),
	)
);

// Action bar
if(true)
{
	$arActions = array(
		"delete" => GetMessage("MAIN_ADMIN_LIST_DELETE"),
		"activate" => GetMessage("MAIN_ADMIN_LIST_ACTIVATE"),
		"deactivate" => GetMessage("MAIN_ADMIN_LIST_DEACTIVATE"),
	);
	$arParams = array();

	if($arIBTYPE["SECTIONS"] == "Y")
	{
		$sections = '<div id="section_to_move" style="display:none"><select name="section_to_move">';
		$sections .= '<option value="">'.GetMessage("MAIN_NO").'</option>';
		$sections .= '<option value="0">'.GetMessage("IBLOCK_UPPER_LEVEL").'</option>';
		$rsSections = CIBlockSection::GetTreeList(Array("IBLOCK_ID"=>$IBLOCK_ID));
		while($ar = $rsSections->GetNext())
		{
			$sections .= '<option value="'.$ar["ID"].'">'.str_repeat(" . ", $ar["DEPTH_LEVEL"]).$ar["NAME"].'</option>';
		}
		$sections .= '</select></div>';

		$arActions["section"] = GetMessage("IBLIST_A_MOVE_TO_SECTION");
		$arActions["add_section"] = GetMessage("IBLIST_A_ADD_TO_SECTION");
		$arActions["section_chooser"] = array("type" => "html", "value" => $sections);

		$arParams["select_onchange"] = "BX('section_to_move').style.display = (this.value == 'section' || this.value == 'add_section'? 'block':'none');";

	}

	if($bWorkFlow)
	{
		$arActions["unlock"] = GetMessage("IBLIST_A_UNLOCK_ACTION");
		$arActions["lock"] = GetMessage("IBLIST_A_LOCK_ACTION");

		$statuses = '<div id="wf_status_id" style="display:none">'.SelectBox("wf_status_id", CWorkflowStatus::GetDropDownList("N", "desc")).'</div>';
		$arActions["wf_status"] = GetMessage("IBLIST_A_WF_STATUS_CHANGE");
		$arActions["wf_status_chooser"] = array("type" => "html", "value" => $statuses);

		$arParams["select_onchange"] .= "BX('wf_status_id').style.display = (this.value == 'wf_status'? 'block':'none');";

	}
	elseif($bBizproc)
	{
		$arActions["unlock"] = GetMessage("IBLIST_A_UNLOCK_ACTION");
	}

	$lAdmin->AddGroupActionTable($arActions, $arParams);
}

$chain = $lAdmin->CreateChain();

$sSectionUrl = CIBlock::GetAdminSectionListLink($IBLOCK_ID, array('find_section_section'=>0));
$chain->AddItem(array(
	"TEXT" => htmlspecialcharsex($arIBlock["NAME"]),
	"LINK" => htmlspecialcharsbx($sSectionUrl),
	"ONCLICK" => $lAdmin->ActionAjaxReload($sSectionUrl).';return false;',
));

if($find_section_section > 0)
	$sLastFolder = $sSectionUrl;
else
	$sLastFolder = '';

if($find_section_section > 0)
{
	$nav = CIBlockSection::GetNavChain($IBLOCK_ID, $find_section_section);
	while($ar_nav = $nav->GetNext())
	{
		$sSectionUrl = CIBlock::GetAdminSectionListLink($IBLOCK_ID, array('find_section_section'=>$ar_nav["ID"]));
		$chain->AddItem(array(
			"TEXT" => $ar_nav["NAME"],
			"LINK" => htmlspecialcharsbx($sSectionUrl),
			"ONCLICK" => $lAdmin->ActionAjaxReload($sSectionUrl).';return false;',
		));

		if($ar_nav["ID"] != $find_section_section)
			$sLastFolder = $sSectionUrl;
	}
}

$lAdmin->ShowChain($chain);

// toolbar
$aContext = array();
if ($boolIBlockElementAdd)
{
	$aContext[] = array(
		"TEXT" => htmlspecialcharsbx($arIBlock["ELEMENT_ADD"]),
		"ICON" => "btn_new",
		"LINK" => CIBlock::GetAdminElementEditLink($IBLOCK_ID, 0, array(
			'IBLOCK_SECTION_ID'=>$find_section_section,
			'find_section_section'=>$find_section_section,
			'from' => 'iblock_list_admin',
		)),
	);
}

if(CIBlockSectionRights::UserHasRightTo($IBLOCK_ID, $find_section_section, "section_section_bind") && $arIBTYPE["SECTIONS"]!="N")
{
	$aContext[] = array(
		"TEXT" => htmlspecialcharsbx($arIBlock["SECTION_ADD"]),
		"ICON" => "btn_new",
		"LINK" => CIBlock::GetAdminSectionEditLink($IBLOCK_ID, 0, array(
			'IBLOCK_SECTION_ID'=>$find_section_section,
			'find_section_section'=>$find_section_section,
			'from' => 'iblock_list_admin',
		)),
	);
}

if(count($aContext) == 2)
	unset($aContext[1]["ICON"]);

if(strlen($sLastFolder)>0)
{
	$aContext[] = Array(
		"TEXT" => GetMessage("IBLIST_A_UP"),
		"LINK" => $sLastFolder,
		"TITLE" => GetMessage("IBLIST_A_UP_TITLE"),
	);
}

if($bBizproc && IsModuleInstalled("bizprocdesigner"))
{
	$bCanDoIt = CBPDocument::CanUserOperateDocumentType(
		CBPCanUserOperateOperation::CreateWorkflow,
		$USER->GetID(),
		array(MODULE_ID, ENTITY, DOCUMENT_TYPE)
		);

	if($bCanDoIt)
	{
		$aContext[] = array(
			"TEXT" => GetMessage("IBLIST_BTN_BP"),
			"ICON" => "btn_bp",
			"LINK" => 'iblock_bizproc_workflow_admin.php?document_type=iblock_'.$IBLOCK_ID.'&lang='.LANGUAGE_ID.'&back_url_list='.urlencode($REQUEST_URI),
		);
	}
}

$lAdmin->AddAdminContextMenu($aContext);

$lAdmin->CheckListMode();

$APPLICATION->SetTitle($arIBlock["NAME"].": ".($arIBTYPE["SECTIONS"]=="Y"? $arIBlock["SECTIONS_NAME"]: $arIBlock["ELEMENTS_NAME"]));
$APPLICATION->AddHeadScript('/bitrix/js/iblock/iblock_edit.js');
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

//We need javascript not in excel mode
if((!$bExcel) && $bCatalog && $bCurrency)
{
	?><script type="text/javascript">
		top.arCatalogShowedGroups = new Array();
		top.arExtra = new Array();
		top.arCatalogGroups = new Array();
		top.BaseIndex = "";
	<?
	if (is_array($arCatGroup) && !empty($arCatGroup))
	{
		$i = 0;
		$j = 0;
		foreach($arCatGroup as &$CatalogGroups)
		{
			if (in_array("CATALOG_GROUP_".$CatalogGroups["ID"], $arSelectedFields))
			{
				echo "top.arCatalogShowedGroups[".$i."]=".$CatalogGroups["ID"].";\n";
				$i++;
			}
			if ($CatalogGroups["BASE"]!="Y")
			{
				echo "top.arCatalogGroups[".$j."]=".$CatalogGroups["ID"].";\n";
				$j++;
			}
			else
			{
				echo "top.BaseIndex=".$CatalogGroups["ID"].";\n";
			}
		}
	}
	if (is_array($arCatExtra) && !empty($arCatExtra))
	{
		$i = 0;
		foreach($arCatExtra as &$CatExtra)
		{
			echo "top.arExtra[".$CatExtra["ID"]."]=".$CatExtra["PERCENTAGE"].";\n";
			$i++;
		}
	}
		?>
		top.ChangeBasePrice = function(id)
		{
			for(var i = 0, cnt = top.arCatalogShowedGroups.length; i < cnt; i++)
			{
				var pr = top.document.getElementById("CATALOG_PRICE["+id+"]"+"["+top.arCatalogShowedGroups[i]+"]");
				if(pr.disabled)
				{
					var price = top.document.getElementById("CATALOG_PRICE["+id+"]"+"["+top.BaseIndex+"]").value;
					if(price > 0)
					{
						var extraId = top.document.getElementById("CATALOG_EXTRA["+id+"]"+"["+top.arCatalogShowedGroups[i]+"]").value;
						var esum = parseFloat(price) * (1 + top.arExtra[extraId] / 100);
						var eps = 1.00/Math.pow(10, 6);
						esum = Math.round((esum+eps)*100)/100;
					}
					else
						var esum = "";

					pr.value = esum;
				}
			}
		}

		top.ChangeBaseCurrency = function(id)
		{
			var currency = top.document.getElementById("CATALOG_CURRENCY["+id+"]["+top.BaseIndex+"]");
			for(var i = 0, cnt = top.arCatalogShowedGroups.length; i < cnt; i++)
			{
				var pr = top.document.getElementById("CATALOG_CURRENCY["+id+"]["+top.arCatalogShowedGroups[i]+"]");
				if(pr.disabled)
				{
					pr.selectedIndex = currency.selectedIndex;
				}
			}
		}
	</script>
	<?
}
CJSCore::Init('file_input');
echo $CAdminCalendar_ShowScript;
?>
<form method="GET" name="find_form" id="find_form" action="<?echo $APPLICATION->GetCurPage()?>">
<?
$arFindFields = Array();
$arFindFields["IBLIST_A_PARENT"] = GetMessage("IBLIST_A_PARENT");
$arFindFields["IBLIST_A_ID"] = GetMessage("IBLIST_A_ID");
$arFindFields["IBLIST_A_TS"] = GetMessage("IBLIST_A_TS");
$arFindFields["IBLIST_A_CODE"] = GetMessage("IBLIST_A_CODE");
$arFindFields["IBLIST_A_EXTCODE"] = GetMessage("IBLIST_A_EXTCODE");
$arFindFields["IBLIST_A_F_MODIFIED_BY"] = GetMessage("IBLIST_A_F_MODIFIED_BY");
$arFindFields["IBLIST_A_F_CREATED_WHEN"] = GetMessage("IBLIST_A_F_CREATED_WHEN");
$arFindFields["IBLIST_A_F_CREATED_BY"] = GetMessage("IBLIST_A_F_CREATED_BY");
if($bWorkFlow)
	$arFindFields["IBLIST_A_F_STATUS"] = GetMessage("IBLIST_A_F_STATUS");
$arFindFields["IBLIST_A_F_ACTIVE_FROM"] = GetMessage("IBLIST_A_DATE_ACTIVE_FROM");
$arFindFields["IBLIST_A_F_ACTIVE_TO"] = GetMessage("IBLIST_A_DATE_ACTIVE_TO");
$arFindFields["IBLIST_A_ACT"] = GetMessage("IBLIST_A_ACTIVE");
$arFindFields["IBLIST_A_F_DESC"] = GetMessage("IBLIST_A_F_DESC");
$arFindFields["IBLIST_A_TAGS"] = GetMessage("IBLIST_A_TAGS");

foreach($arProps as $arProp)
	if($arProp["FILTRABLE"]=="Y" && $arProp["PROPERTY_TYPE"]!="F")
		$arFindFields["IBLIST_A_PROP_".$arProp["ID"]] = $arProp["NAME"];

if ($boolSKU && $boolSKUFiltrable)
{
	foreach($arSKUProps as $arProp)
	{
		if($arProp["FILTRABLE"]=="Y" && $arProp["PROPERTY_TYPE"]!="F" && $arCatalog['OFFERS_PROPERTY_ID'] != $arProp['ID'])
		{
			$arFindFields["IBLIST_A_SUB_PROP_".$arProp["ID"]] = ('' != $strSKUName ? $strSKUName.' - ' : '').$arProp["NAME"];
		}
	}
}

$filterUrl = $APPLICATION->GetCurPageParam(); //$APPLICATION->GetCurPage().'?type='.urlencode($type).'&IBLOCK_ID='.urlencode($IBLOCK_ID).'&lang='.urlencode(LANG);
$oFilter = new CAdminFilter($sTableID."_filter", $arFindFields, array("table_id" => $sTableID, "url" => $filterUrl));
?><script type="text/javascript">
var arClearHiddenFields = new Array();
function applyFilter(el)
{
	BX.adminPanel.showWait(el);
	<?=$sTableID."_filter";?>.OnSet('<?=CUtil::JSEscape($sTableID)?>', '<?=CUtil::JSEscape($filterUrl)?>');
	return false;
}

function deleteFilter(el)
{
	BX.adminPanel.showWait(el);
	if (0 < arClearHiddenFields.length)
	{
		for (var index = 0; index < arClearHiddenFields.length; index++)
		{
			if (undefined != window[arClearHiddenFields[index]])
			{
				if ('ClearForm' in window[arClearHiddenFields[index]])
				{
					window[arClearHiddenFields[index]].ClearForm();
				}
			}
		}
	}
	<?=$sTableID."_filter"?>.OnClear('<?=CUtil::JSEscape($sTableID)?>', '<?=CUtil::JSEscape($APPLICATION->GetCurPage().'?type='.urlencode($type).'&IBLOCK_ID='.urlencode($IBLOCK_ID).'&lang='.urlencode(LANG).'&')?>');
	return false;
}
</script><?
$oFilter->Begin();
?>
	<tr>
		<td><b><?echo GetMessage("IBLIST_A_NAME")?></b></td>
		<td><input type="text" name="find_name" value="<?echo htmlspecialcharsex($find_name)?>" size="47">&nbsp;<?=ShowFilterLogicHelp()?></td>
	</tr>
	<tr>
		<td><?echo GetMessage("IBLIST_A_F_SECTION")?></td>
		<td>
			<select name="find_section_section" >
				<option value="-1"><?echo GetMessage("IBLOCK_ALL")?></option>
				<option value="0"<?if($find_section_section=="0")echo" selected"?>><?echo GetMessage("IBLOCK_UPPER_LEVEL")?></option>
				<?
				$bsections = CIBlockSection::GetTreeList(Array("IBLOCK_ID"=>$IBLOCK_ID));
				while($arSection = $bsections->GetNext()):
					?><option value="<?echo $arSection["ID"]?>"<?if($arSection["ID"]==$find_section_section)echo " selected"?>><?echo str_repeat("&nbsp;.&nbsp;", $arSection["DEPTH_LEVEL"])?><?echo $arSection["NAME"]?></option><?
				endwhile;
				?>
			</select>
		</td>
	</tr>
	<tr>
		<td><?echo GetMessage("IBLIST_A_F_FROM_TO_ID")?></td>
		<td nowrap>
			<input type="text" name="find_id_1" size="10" value="<?echo htmlspecialcharsex($find_id_1)?>">
			...
			<input type="text" name="find_id_2" size="10" value="<?echo htmlspecialcharsex($find_id_2)?>">
		</td>
	</tr>
	<tr>
		<td><?echo GetMessage("IBLOCK_FIELD_TIMESTAMP_X")?>:</td>
		<td><?echo CalendarPeriod("find_timestamp_1", htmlspecialcharsbx($find_timestamp_1), "find_timestamp_2", htmlspecialcharsbx($find_timestamp_2), "find_form", "Y")?></td>
	</tr>
	<tr>
		<td><?echo GetMessage("IBLIST_A_CODE")?>:</td>
		<td><input type="text" name="find_code" size="47" value="<?echo htmlspecialcharsbx($find_code)?>"></td>
	</tr>
	<tr>
		<td><?echo GetMessage("IBLIST_A_EXTCODE")?>:</td>
		<td><input type="text" name="find_external_id" size="47" value="<?echo htmlspecialcharsbx($find_external_id)?>"></td>
	</tr>
	<tr>
		<td><?=GetMessage("IBLIST_A_F_MODIFIED_BY")?>:</td>
		<td><input type="text" name="find_modified_user_id" value="<?echo htmlspecialcharsex($find_modified_user_id)?>" size="3">&nbsp;<?
		$gr_res = CIBlock::GetGroupPermissions($IBLOCK_ID);
		$res = Array(1);
		foreach($gr_res as $gr=>$perm)
			if($perm>"R")
				$res[] = $gr;
			$res = CUser::GetList($byx="NAME", $orderx="ASC", Array("GROUP_MULTI"=>$res));
		?><select name="find_modified_by">
		<option value=""><?echo GetMessage("IBLOCK_VALUE_ANY")?></option><?
		while($arr = $res->Fetch())
			echo "<option value='".$arr["ID"]."'".($find_modified_by==$arr["ID"]?" selected":"").">(".htmlspecialcharsex($arr["LOGIN"].") ".$arr["NAME"]." ".$arr["LAST_NAME"])."</option>";
		?></select>
		</td>
	</tr>

	<tr>
		<td><?echo GetMessage("IBLIST_A_DATE_CREATE")?>:</td>
		<td><?echo CalendarPeriod("find_created_from", htmlspecialcharsex($find_created_from), "find_created_to", htmlspecialcharsex($find_created_to), "find_element_form", "Y")?></td>
	</tr>

	<tr>
		<td><?echo GetMessage("IBLIST_A_F_CREATED_BY")?>:</td>
		<td><input type="text" name="find_created_user_id" value="<?echo htmlspecialcharsex($find_created_user_id)?>" size="3">&nbsp;<?
		$gr_res = CIBlock::GetGroupPermissions($IBLOCK_ID);
		$res = Array(1);
		foreach($gr_res as $gr=>$perm)
			if($perm>"R")
				$res[] = $gr;
		$res = CUser::GetList($byx="NAME", $orderx="ASC", Array("GROUP_MULTI"=>$res));
		?><select name="find_created_by">
		<option value=""><?echo GetMessage("IBLOCK_VALUE_ANY")?></option><?
		while($arr = $res->Fetch())
			echo "<option value='".$arr["ID"]."'".($find_created_by==$arr["ID"]?" selected":"").">(".htmlspecialcharsex($arr["LOGIN"].") ".$arr["NAME"]." ".$arr["LAST_NAME"])."</option>";
		?></select>
		</td>
	</tr>

	<?if($bWorkFlow):?>
	<tr>
		<td><?=GetMessage("IBLIST_A_STATUS")?>:</td>
		<td><input type="text" name="find_status_id" value="<?echo htmlspecialcharsex($find_status_id)?>" size="3">
		<select name="find_status">
		<option value=""><?=GetMessage("IBLOCK_VALUE_ANY")?></option>
		<?
		$rs = CWorkflowStatus::GetDropDownList("Y");
		while($arRs = $rs->GetNext())
		{
			?><option value="<?=$arRs["REFERENCE_ID"]?>"<?if($find_status == $arRs["~REFERENCE_ID"])echo " selected"?>><?=$arRs["REFERENCE"]?></option><?
		}
		?>
		</select></td>
	</tr>
	<?endif?>
	<tr>
		<td><?echo GetMessage("IBLIST_A_DATE_ACTIVE_FROM")?>:</td>
		<td><?echo CalendarPeriod("find_date_active_from_from", htmlspecialcharsex($find_date_active_from_from), "find_date_active_from_to", htmlspecialcharsex($find_date_active_from_to), "find_form")?></td>
	</tr>
	<tr>
		<td><?echo GetMessage("IBLIST_A_DATE_ACTIVE_TO")?>:</td>
		<td><?echo CalendarPeriod("find_date_active_to_from", htmlspecialcharsex($find_date_active_to_from), "find_date_active_to_to", htmlspecialcharsex($find_date_active_to_to), "find_form")?></td>
	</tr>
	<tr>
		<td><?echo GetMessage("IBLIST_A_ACTIVE")?>:</td>
		<td>
			<select name="find_active">
				<option value=""><?=htmlspecialcharsex(GetMessage('IBLOCK_VALUE_ANY'))?></option>
				<option value="Y"<?if($find_active=="Y")echo " selected"?>><?=htmlspecialcharsex(GetMessage("IBLOCK_YES"))?></option>
				<option value="N"<?if($find_active=="N")echo " selected"?>><?=htmlspecialcharsex(GetMessage("IBLOCK_NO"))?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td><?echo GetMessage("IBLIST_A_F_DESC")?>:</td>
		<td><input type="text" name="find_intext" value="<?echo htmlspecialcharsex($find_intext)?>" size="30">&nbsp;<?=ShowFilterLogicHelp()?></td>
	</tr>
	<tr>
		<td><?=GetMessage("IBLIST_A_TAGS")?>:</td>
		<td>
			<?//if(CModule::IncludeModule('search')):
			if ($bSearch):
				echo InputTags("find_tags", $find_tags, $arIBlock["SITE_ID"]);
			else:
			?>
				<input type="text" name="find_tags" value="<?echo htmlspecialcharsex($find_tags)?>" size="30">
			<?endif?>
		</td>
	</tr>
	<?

function _ShowGroupPropertyFieldList($name, $property_fields, $values)
{
	if(!is_array($values)) $values = Array();

	$res = "";
	$result = "";
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
	$result .= '<select name="'.$name.'[]" size="'.($property_fields["MULTIPLE"]=="Y" ? "5":"1").'" '.($property_fields["MULTIPLE"]=="Y"?"multiple":"").'>';
	$result .= '<option value=""'.(!$bWas?' selected':'').'>'.GetMessage("IBLIST_A_PROP_NOT_SET").'</option>';
	$result .= $res;
	$result .= '</select>';
	return $result;
}

foreach($arProps as $arProp):
	if($arProp["FILTRABLE"]=="Y" && $arProp["PROPERTY_TYPE"]!="F"):
?>
<tr>
	<td><?=$arProp["NAME"]?>:</td>
	<td>
		<?if(array_key_exists("GetAdminFilterHTML", $arProp["PROPERTY_USER_TYPE"])):
			echo call_user_func_array($arProp["PROPERTY_USER_TYPE"]["GetAdminFilterHTML"], array(
				$arProp,
				array("VALUE" => "find_el_property_".$arProp["ID"]),
			));
		elseif($arProp["PROPERTY_TYPE"]=='S'):?>
			<input type="text" name="find_el_property_<?=$arProp["ID"]?>" value="<?echo htmlspecialcharsex(${"find_el_property_".$arProp["ID"]})?>" size="30">&nbsp;<?=ShowFilterLogicHelp()?>
		<?elseif($arProp["PROPERTY_TYPE"]=='N' || $arProp["PROPERTY_TYPE"]=='E'):?>
			<input type="text" name="find_el_property_<?=$arProp["ID"]?>" value="<?echo htmlspecialcharsex(${"find_el_property_".$arProp["ID"]})?>" size="30">
		<?elseif($arProp["PROPERTY_TYPE"]=='L'):?>
			<select name="find_el_property_<?=$arProp["ID"]?>">
				<option value=""><?echo GetMessage("IBLOCK_VALUE_ANY")?></option>
				<option value="NOT_REF"><?echo GetMessage("IBLIST_A_PROP_NOT_SET")?></option><?
				$dbrPEnum = CIBlockPropertyEnum::GetList(Array("SORT"=>"ASC", "NAME"=>"ASC"), Array("PROPERTY_ID"=>$arProp["ID"]));
				while($arPEnum = $dbrPEnum->GetNext()):
				?>
					<option value="<?=$arPEnum["ID"]?>"<?if(${"find_el_property_".$arProp["ID"]} == $arPEnum["ID"])echo " selected"?>><?=$arPEnum["VALUE"]?></option>
				<?
				endwhile;
		?></select>
		<?
		elseif($arProp["PROPERTY_TYPE"]=='G'):
			echo _ShowGroupPropertyFieldList('find_el_property_'.$arProp["ID"], $arProp, ${'find_el_property_'.$arProp["ID"]});
		endif;
		?>
	</td>
</tr>
<?
	endif;
endforeach;?>
<?

if ($boolSKU && $boolSKUFiltrable)
{
	foreach($arSKUProps as $arProp)
	{
		if($arProp["FILTRABLE"]=="Y" && $arProp["PROPERTY_TYPE"]!="F" && $arCatalog['OFFERS_PROPERTY_ID'] != $arProp['ID'])
		{
?>
<tr>
	<td><? echo ('' != $strSKUName ? $strSKUName.' - ' : ''); ?><? echo $arProp["NAME"]?>:</td>
	<td>
		<?if(array_key_exists("GetAdminFilterHTML", $arProp["PROPERTY_USER_TYPE"])):
			echo call_user_func_array($arProp["PROPERTY_USER_TYPE"]["GetAdminFilterHTML"], array(
				$arProp,
				array("VALUE" => "find_sub_el_property_".$arProp["ID"]),
			));
		elseif($arProp["PROPERTY_TYPE"]=='S'):?>
			<input type="text" name="find_sub_el_property_<?=$arProp["ID"]?>" value="<?echo htmlspecialcharsex(${"find_sub_el_property_".$arProp["ID"]})?>" size="30">&nbsp;<?=ShowFilterLogicHelp()?>
		<?elseif($arProp["PROPERTY_TYPE"]=='N' || $arProp["PROPERTY_TYPE"]=='E'):?>
			<input type="text" name="find_sub_el_property_<?=$arProp["ID"]?>" value="<?echo htmlspecialcharsex(${"find_sub_el_property_".$arProp["ID"]})?>" size="30">
		<?elseif($arProp["PROPERTY_TYPE"]=='L'):?>
			<select name="find_sub_el_property_<?=$arProp["ID"]?>">
				<option value=""><?echo GetMessage("IBLOCK_VALUE_ANY")?></option>
				<option value="NOT_REF"><?echo GetMessage("IBLIST_NOT_SET")?></option><?
				$dbrPEnum = CIBlockPropertyEnum::GetList(Array("SORT"=>"ASC", "NAME"=>"ASC"), Array("PROPERTY_ID"=>$arProp["ID"]));
				while($arPEnum = $dbrPEnum->GetNext()):
				?>
					<option value="<?=$arPEnum["ID"]?>"<?if(${"find_sub_el_property_".$arProp["ID"]} == $arPEnum["ID"])echo " selected"?>><?=$arPEnum["VALUE"]?></option>
				<?
				endwhile;
		?></select>
		<?
		elseif($arProp["PROPERTY_TYPE"]=='G'):
			echo _ShowGroupPropertyFieldList('find_sub_el_property_'.$arProp["ID"], $arProp, ${'find_sub_el_property_'.$arProp["ID"]});
		endif;
		?>
	</td>
</tr>
<?
		}
	}
}

$oFilter->Buttons();
?><input  class="adm-btn" type="submit" name="set_filter" value="<? echo GetMessage("admin_lib_filter_set_butt"); ?>" title="<? echo GetMessage("admin_lib_filter_set_butt_title"); ?>" onClick="return applyFilter(this);">
<input  class="adm-btn" type="submit" name="del_filter" value="<? echo GetMessage("admin_lib_filter_clear_butt"); ?>" title="<? echo GetMessage("admin_lib_filter_clear_butt_title"); ?>" onClick="deleteFilter(this); return false;">
<?
$oFilter->End();
?>
</form>
<?
$lAdmin->DisplayList();
?>
<?if($bWorkFlow || $bBizproc):?>
	<?echo BeginNote();?>
	<span class="adm-lamp adm-lamp-green"></span> - <?echo GetMessage("IBLIST_A_GREEN_ALT")?></br>
	<span class="adm-lamp adm-lamp-yellow"></span> - <?echo GetMessage("IBLIST_A_YELLOW_ALT")?></br>
	<span class="adm-lamp adm-lamp-red"></span> - <?echo GetMessage("IBLIST_A_RED_ALT")?></br>
	<?echo EndNote();?>
<?endif;?>
<?
if(CIBlockRights::UserHasRightTo($IBLOCK_ID, $IBLOCK_ID, "iblock_edit"))
{
	echo
		BeginNote(),
		GetMessage("IBLIST_A_IBLOCK_MANAGE_HINT"),
		' <a href="'.htmlspecialcharsbx('iblock_edit.php?type='.urlencode($type).'&lang='.urlencode(LANG).'&ID='.urlencode($IBLOCK_ID).'&admin=Y&return_url='.urlencode("iblock_list_admin.php?".$sThisSectionUrl)).'">',
		GetMessage("IBLIST_A_IBLOCK_MANAGE_HINT_HREF"),
		'</a>',
		EndNote()
	;
}
?>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>
