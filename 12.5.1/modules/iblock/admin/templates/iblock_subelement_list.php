<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

global $APPLICATION;

/*
* B_ADMIN_SUBELEMENTS
* if defined and equal 1 - working, another die
* B_ADMIN_SUBELEMENTS_LIST - true/false
* if not defined - die
* if equal true - get list mode
* 	include prolog and epilog
* other - get simple html
*
* need variables
* 		$strSubElementAjaxPath - path for ajax
* 		$strSubIBlockType - iblock type
* 		$arSubIBlockType - iblock type array
* 		$intSubIBlockID - iblock ID
* 		$arSubIBlock	- array with info about iblock
*		$boolSubWorkFlow - workflow and iblock in workflow
*		$boolSubBizproc - business process and iblock in business
*		$boolSubCatalog - catalog and iblock in catalog
*		$arSubCatalog - info about catalog (with product_iblock_id iand sku_property_id info)
*		$intSubPropValue - ID for filter
*		$strSubTMP_ID - string identifier for link with new product ($intSubPropValue = 0, in edit form send -1)
*
*
*created variables
*		$arSubElements - array subelements for product with ID = 0
*/
if ((false == defined('B_ADMIN_SUBELEMENTS')) || (1 != B_ADMIN_SUBELEMENTS))
	return '';
if (false == defined('B_ADMIN_SUBELEMENTS_LIST'))
	return '';

$strSubElementAjaxPath = trim($strSubElementAjaxPath);
$strSubIBlockType = trim($strSubIBlockType);
$intSubIBlockID = intval($intSubIBlockID);
if ($intSubIBlockID <= 0)
	return;
$boolSubWorkFlow = ($boolSubWorkFlow === true ? true : false);
$boolSubBizproc = ($boolSubBizproc === true ? true : false);
$boolSubCatalog = ($boolSubCatalog === true ? true : false);
$boolSubSearch = false;
$boolSubCurrency = false;
$arCurrencyList = array();

define("MODULE_ID", "iblock");
define("ENTITY", "CIBlockDocument");
define("DOCUMENT_TYPE", "iblock_".$intSubIBlockID);

if ($_REQUEST['mode']=='list' || $_REQUEST['mode']=='frame')
	CFile::DisableJSFunction(true);

$intSubPropValue = intval($intSubPropValue);

$strSubTMP_ID = intval($strSubTMP_ID);

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/iblock/admin/iblock_element_admin.php");
IncludeModuleLangFile(__FILE__);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/iblock/classes/general/subelement.php');

$dbrFProps = CIBlockProperty::GetList(array("SORT"=>"ASC","NAME"=>"ASC"),array("IBLOCK_ID"=>$intSubIBlockID,"ACTIVE"=>"Y", "CHECK_PERMISSIONS" => "N"));

$arProps = array();
while($arProp = $dbrFProps->GetNext())
{
	$arProp["PROPERTY_USER_TYPE"] = (0 < strlen($arProp["USER_TYPE"]) ? CIBlockProperty::GetUserType($arProp["USER_TYPE"]) : array());
	$arProps[] = $arProp;
}

$sTableID = "tbl_iblock_sub_element_".md5($strSubIBlockType.".".$intSubIBlockID);

$arHideFields = array('PROPERTY_'.$arCatalog['SKU_PROPERTY_ID']);
$lAdmin = new CAdminSubList($sTableID,false,$strSubElementAjaxPath,$arHideFields);

// only sku property filter
$arFilterFields = Array(
	"find_el_property_".$arCatalog['SKU_PROPERTY_ID'],
);

$find_section_section = -1;

//We have to handle current section in a special way
$section_id = intval($find_section_section);
$lAdmin->InitFilter($arFilterFields);
$find_section_section = $section_id;
//This is all parameters needed for proper navigation
//$sThisSectionUrl = '&type='.urlencode($strSubIBlockType).'&lang='.LANG.'&IBLOCK_ID='.$intSubIBlockID.'&find_section_section='.intval($find_section_section);
$sThisSectionUrl = '';

// simple filter
$arFilter = Array(
	"IBLOCK_ID" => $intSubIBlockID,
);

if (0 < $intSubPropValue)
	$arFilter["=PROPERTY_".$arSubCatalog['SKU_PROPERTY_ID']] = $intSubPropValue;
else
{
	$arFilter["=PROPERTY_".$arSubCatalog['SKU_PROPERTY_ID']] = $intSubPropValue;
}
$arFilter["CHECK_PERMISSIONS"] = "Y";
$arFilter["MIN_PERMISSION"] = "R";

if ((true == defined('B_ADMIN_SUBELEMENTS_LIST')) && (true == B_ADMIN_SUBELEMENTS_LIST))
{
	if ($lAdmin->EditAction())
	{
		if (is_array($_FILES['FIELDS']))
			CAllFile::ConvertFilesToPost($_FILES['FIELDS'], $_POST['FIELDS']);
		if (is_array($FIELDS_del))
			CAllFile::ConvertFilesToPost($FIELDS_del, $_POST['FIELDS'], "del");

		foreach ($_POST['FIELDS'] as $subID => $arFields)
		{
			if (!$lAdmin->IsUpdated($subID))
				continue;
			$subID = IntVal($subID);

			$arRes = CIBlockElement::GetByID($subID);
			$arRes = $arRes->Fetch();
			if (!$arRes)
				continue;

			$WF_ID = $subID;
			if ($boolSubWorkFlow)
			{
				$WF_ID = CIBlockElement::WF_GetLast($subID);
				if ($WF_ID != $subID)
				{
					$rsData2 = CIBlockElement::GetByID($WF_ID);
					if ($arRes = $rsData2->Fetch())
						$WF_ID = $arRes["ID"];
					else
						$WF_ID = $subID;
				}

				if ($arRes["LOCK_STATUS"]=='red' && !($_REQUEST['action']=='unlock' && CWorkflow::IsAdmin()))
				{
					$lAdmin->AddUpdateError(GetMessage("IBEL_A_UPDERR1")." (ID:".$subID.")", $subID);
					continue;
				}
			}
			elseif ($boolSubBizproc)
			{
				if (CIBlockDocument::IsDocumentLocked($subID, ""))
				{
					$lAdmin->AddUpdateError(GetMessage("IBEL_A_UPDERR_LOCKED", array("#ID#" => $subID)), $subID);
					continue;
				}
			}

			if (
				$boolSubWorkFlow
			)
			{
				if (!CIBlockElementRights::UserHasRightTo($intSubIBlockID, $subID, "element_edit"))
				{
					$lAdmin->AddUpdateError(GetMessage("IBEL_A_UPDERR3")." (ID:".$subID.")", $subID);
					continue;
				}
				$STATUS_PERMISSION = 2;
				// change is under workflow find status and its permissions
				if (!CIBlockElementRights::UserHasRightTo($intSubIBlockID, $subID, "element_edit_any_wf_status"))
					$STATUS_PERMISSION = CIBlockElement::WF_GetStatusPermission($arRes["WF_STATUS_ID"]);
				if ($STATUS_PERMISSION < 2)
				{
					$lAdmin->AddUpdateError(GetMessage("IBEL_A_UPDERR3")." (ID:".$subID.")", $subID);
					continue;
				}

				// status change  - check permissions
				if (isset($arFields["WF_STATUS_ID"]))
				{
					if (CIBlockElement::WF_GetStatusPermission($arFields["WF_STATUS_ID"])<1)
					{
						$lAdmin->AddUpdateError(GetMessage("IBEL_A_UPDERR2")." (ID:".$subID.")", $subID);
						continue;
					}
				}
			}
			elseif ($bBizproc)
			{
				$bCanWrite = CIBlockDocument::CanUserOperateDocument(
					CBPCanUserOperateOperation::WriteDocument,
					$USER->GetID(),
					$subID,
					array(
						"IBlockId" => $intSubIBlockID,
						'IBlockRightsMode' => $arSubIBlock['RIGHTS_MODE'],
						'UserGroups' => $USER->GetUserGroupArray(),
					)
				);

				if (!$bCanWrite)
				{
					$lAdmin->AddUpdateError(GetMessage("IBEL_A_UPDERR3")." (ID:".$subID.")", $subID);
					continue;
				}
			}
			elseif (!CIBlockElementRights::UserHasRightTo($intSubIBlockID, $subID, "element_edit"))
			{
				$lAdmin->AddUpdateError(GetMessage("IBEL_A_UPDERR3")." (ID:".$subID.")", $subID);
				continue;
			}

			if (!is_array($arFields["PROPERTY_VALUES"]))
				$arFields["PROPERTY_VALUES"] = Array();
			$bFieldProps = array();
			foreach ($arFields as $k=>$v)
			{
				if (
					substr($k, 0, strlen("PROPERTY_")) == "PROPERTY_"
					&& $k != "PROPERTY_VALUES"
				)
				{
					$prop_id = substr($k, strlen("PROPERTY_"));
					$arFields["PROPERTY_VALUES"][$prop_id] = $v;
					unset($arFields[$k]);
					$bFieldProps[$prop_id]=true;
				}
			}
			if (count($bFieldProps) > 0)
			{
				//We have to read properties from database in order not to delete its values
				if (!$boolSubWorkFlow)
				{
					$dbPropV = CIBlockElement::GetProperty($intSubIBlockID, $subID, "sort", "asc", Array("ACTIVE"=>"Y"));
					while ($arPropV = $dbPropV->Fetch())
					{
						if (!array_key_exists($arPropV["ID"], $bFieldProps) && $arPropV["PROPERTY_TYPE"] != "F")
						{
							if (!array_key_exists($arPropV["ID"], $arFields["PROPERTY_VALUES"]))
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
			foreach ($arSubIBlock["FIELDS"] as $FIELD_ID => $field)
			{
				if (
					$field["IS_REQUIRED"] === "Y"
					&& !array_key_exists($FIELD_ID, $arFields)
					&& $FIELD_ID !== "DETAIL_PICTURE"
					&& $FIELD_ID !== "PREVIEW_PICTURE"
				)
					$arFields[$FIELD_ID] = $arRes[$FIELD_ID];
			}
			if ($arRes["IN_SECTIONS"] == "Y")
			{
				$arFields["IBLOCK_SECTION"] = array();
				$rsSections = CIBlockElement::GetElementGroups($arRes["ID"], true);
				while ($arSection = $rsSections->Fetch())
					$arFields["IBLOCK_SECTION"][] = $arSection["ID"];
			}

			$arFields["MODIFIED_BY"] = $USER->GetID();
			$ib = new CIBlockElement();
			$DB->StartTransaction();

			if (!$ib->Update($subID, $arFields, true, true, true))
			{
				$lAdmin->AddUpdateError(GetMessage("IBEL_A_SAVE_ERROR", array("#ID#"=>$subID, "#ERROR_TEXT#"=>$ib->LAST_ERROR)), $subID);
				$DB->Rollback();
			}
			else
			{
				$DB->Commit();
			}

			if ($boolSubCatalog)
			{
				if (
					$USER->CanDoOperation('catalog_price') && CIBlockElementRights::UserHasRightTo($intSubIBlockID, $subID, "element_edit_price")
				)
				{
					$CATALOG_QUANTITY = $arFields["CATALOG_QUANTITY"];
					$CATALOG_QUANTITY_TRACE = $arFields["CATALOG_QUANTITY_TRACE"];
					$CATALOG_WEIGHT = $arFields["CATALOG_WEIGHT"];

					if (!CCatalogProduct::GetByID($subID))
					{
						$arCatalogQuantity = Array("ID" => $subID);
						if (strlen($CATALOG_QUANTITY) > 0)
						{
							if (COption::GetOptionString("catalog", "default_use_store_control") != "Y")
								$arCatalogQuantity["QUANTITY"] = $CATALOG_QUANTITY;
						}
						if (strlen($CATALOG_QUANTITY_TRACE) > 0)
							$arCatalogQuantity["QUANTITY_TRACE"] = (($CATALOG_QUANTITY_TRACE == "Y") ? "Y" : (($CATALOG_QUANTITY_TRACE == "D") ? "D" : "N"));
						if (strlen($CATALOG_WEIGHT) > 0)
							$arCatalogQuantity['WEIGHT'] = $CATALOG_WEIGHT;
						CCatalogProduct::Add($arCatalogQuantity);
					}
					else
					{
						$arCatalogQuantity = Array();
						if (strlen($CATALOG_QUANTITY) > 0)
						{
							if (COption::GetOptionString("catalog", "default_use_store_control") != "Y")
								$arCatalogQuantity["QUANTITY"] = $CATALOG_QUANTITY;
						}
						if (strlen($CATALOG_QUANTITY_TRACE) > 0)
							$arCatalogQuantity["QUANTITY_TRACE"] = (($CATALOG_QUANTITY_TRACE == "Y") ? "Y" : (($CATALOG_QUANTITY_TRACE == "D") ? "D" : "N"));
						if (strlen($CATALOG_WEIGHT) > 0)
							$arCatalogQuantity['WEIGHT'] = $CATALOG_WEIGHT;
						if (!empty($arCatalogQuantity))
							CCatalogProduct::Update($subID, $arCatalogQuantity);
					}
				}
			}
		}

		if ($boolSubCatalog)
		{
			if ($USER->CanDoOperation('catalog_price') && (isset($_POST["CATALOG_PRICE"]) || isset($_POST["CATALOG_CURRENCY"])))
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

				foreach ($CATALOG_PRICE as $elID => $arPrice)
				{
					if (!(CIBlockElementRights::UserHasRightTo($intSubIBlockID, $elID, "element_edit_price")
						&& CIBlockElementRights::UserHasRightTo($intSubIBlockID, $elID, "element_edit")))
						continue;
					//1 Find base price ID
					//2 If such a column is displayed then
					//	check if it is greater than 0
					//3 otherwise
					//	look up it's value in database and
					//	output an error if not found or found less or equal then zero
					$bError = false;
					$arBaseGroup = CCatalogGroup::GetBaseGroup();
					if (isset($arPrice[$arBaseGroup['ID']]))
					{
						if ($arPrice[$arBaseGroup['ID']] <= 0)
						{
							$bError = true;
							$lAdmin->AddUpdateError($elID.': '.GetMessage('IB_CAT_NO_BASE_PRICE'), $elID);
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
							$lAdmin->AddGroupError($elID.': '.GetMessage('IB_CAT_NO_BASE_PRICE'), $elID);
						}
					}

					if ($bError)
						continue;

					$arCurrency = $CATALOG_CURRENCY[$elID];

					$dbCatalogGroups = CCatalogGroup::GetList(
						array("SORT" => "ASC"),
						array("LID"=>LANGUAGE_ID)
					);
					while ($arCatalogGroup = $dbCatalogGroups->Fetch())
					{
						if (doubleval($arPrice[$arCatalogGroup["ID"]]) != doubleval($CATALOG_PRICE_old[$elID][$arCatalogGroup["ID"]])
							|| $arCurrency[$arCatalogGroup["ID"]] != $CATALOG_CURRENCY_old[$elID][$arCatalogGroup["ID"]])
						{
							if ($arCatalogGroup["BASE"]=="Y") // if base price check extra for other prices
							{
								$arFields = Array(
									"PRODUCT_ID" => $elID,
									"CATALOG_GROUP_ID" => $arCatalogGroup["ID"],
									"PRICE" => DoubleVal($arPrice[$arCatalogGroup["ID"]]),
									"CURRENCY" => $arCurrency[$arCatalogGroup["ID"]],
									"QUANTITY_FROM" => $CATALOG_QUANTITY_FROM[$elID][$arCatalogGroup["ID"]],
									"QUANTITY_TO" => $CATALOG_QUANTITY_TO[$elID][$arCatalogGroup["ID"]],
								);
								if ($arFields["PRICE"] <=0 )
								{
									CPrice::Delete($CATALOG_PRICE_ID[$elID][$arCatalogGroup["ID"]]);
								}
								elseif (IntVal($CATALOG_PRICE_ID[$elID][$arCatalogGroup["ID"]])>0)
								{
									CPrice::Update(IntVal($CATALOG_PRICE_ID[$elID][$arCatalogGroup["ID"]]), $arFields);
								}
								elseif ($arFields["PRICE"] > 0)
								{
									CPrice::Add($arFields);
								}

								$arPrFilter = array(
									"PRODUCT_ID" => $elID,
								);
								if (DoubleVal($arPrice[$arCatalogGroup["ID"]])>0)
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
								while ($ar_res = $db_res->Fetch())
								{
									$arFields = Array(
										"PRICE" => DoubleVal($arPrice[$arCatalogGroup["ID"]])*(1+$arCatExtraUp[$ar_res["EXTRA_ID"]]/100) ,
										"EXTRA_ID" => $ar_res["EXTRA_ID"],
										"CURRENCY" => $arCurrency[$arCatalogGroup["ID"]],
										"QUANTITY_FROM" => $ar_res["QUANTITY_FROM"],
										"QUANTITY_TO" => $ar_res["QUANTITY_TO"]
									);
									if ($arFields["PRICE"] <= 0)
										CPrice::Delete($ar_res["ID"]);
									else
										CPrice::Update($ar_res["ID"], $arFields);
								}
							}
							elseif (!isset($CATALOG_EXTRA[$elID][$arCatalogGroup["ID"]]))
							{
								$arFields = Array(
									"PRODUCT_ID" => $elID,
									"CATALOG_GROUP_ID" => $arCatalogGroup["ID"],
									"PRICE" => DoubleVal($arPrice[$arCatalogGroup["ID"]]),
									"CURRENCY" => $arCurrency[$arCatalogGroup["ID"]],
									"QUANTITY_FROM" => $CATALOG_QUANTITY_FROM[$elID][$arCatalogGroup["ID"]],
									"QUANTITY_TO" => $CATALOG_QUANTITY_TO[$elID][$arCatalogGroup["ID"]]
								);
								if ($arFields["PRICE"] <= 0)
									CPrice::Delete($CATALOG_PRICE_ID[$elID][$arCatalogGroup["ID"]]);
								elseif (IntVal($CATALOG_PRICE_ID[$elID][$arCatalogGroup["ID"]])>0)
									CPrice::Update(IntVal($CATALOG_PRICE_ID[$elID][$arCatalogGroup["ID"]]), $arFields);
								elseif ($arFields["PRICE"] > 0)
									CPrice::Add($arFields);
							}
						}
					}
				}
			}
		}
	}

	if (($arID = $lAdmin->GroupAction()))
	{
		if ($_REQUEST['action_target']=='selected')
		{
			$rsData = CIBlockElement::GetList(Array($by=>$order), $arFilter);
			while($arRes = $rsData->Fetch())
				$arID[] = $arRes['ID'];
		}

		foreach ($arID as $subID)
		{
			if (strlen($subID)<=0)
				continue;

			$subID = IntVal($subID);
			$arRes = CIBlockElement::GetByID($subID);
			$arRes = $arRes->Fetch();
			if (!$arRes)
				continue;

			$WF_ID = $subID;
			if ($boolSubWorkFlow)
			{
				$WF_ID = CIBlockElement::WF_GetLast($subID);
				if ($WF_ID != $subID)
				{
					$rsData2 = CIBlockElement::GetByID($WF_ID);
					if ($arRes = $rsData2->Fetch())
						$WF_ID = $arRes["ID"];
					else
						$WF_ID = $subID;
				}

				if ($arRes["LOCK_STATUS"]=='red' && !($_REQUEST['action']=='unlock' && CWorkflow::IsAdmin()))
				{
					$lAdmin->AddGroupError(GetMessage("IBEL_A_UPDERR1")." (ID:".$subID.")", $subID);
					continue;
				}
			}
			elseif ($boolSubBizproc)
			{
				if (CIBlockDocument::IsDocumentLocked($subID, "") && !($_REQUEST['action']=='unlock' && CBPDocument::IsAdmin()))
				{
					$lAdmin->AddUpdateError(GetMessage("IBEL_A_UPDERR_LOCKED", array("#ID#" => $subID)), $subID);
					continue;
				}
			}

			$bPermissions = false;
			//delete and modify can:
/*			if (CIBlockElementRights::UserHasRightTo($intSubIBlockID, $subID, "element_edit_any_wf_status")) // only writers
			{
				$bPermissions = true;
			}
			elseif ($boolSubWorkFlow) */
			if ($boolSubWorkFlow)
			{
				//For delete action we have to check all statuses in element history
				$STATUS_PERMISSION = CIBlockElement::WF_GetStatusPermission($arRes["WF_STATUS_ID"], $_REQUEST['action']=="delete"? $subID: false);
				if ($STATUS_PERMISSION >= 2)
				$bPermissions = true;
			}
			elseif ($boolSubBizproc)
			{
/*				$bCanWrite = CIBlockDocument::CanUserOperateDocument(
					IBLOCK_DOCUMENT_OPERATION_WRITE_DOCUMENT,
					$USER->GetID(),
					$subID,
					array("UserGroups" => $USER->GetUserGroupArray())
				); */
				$bCanWrite = CIBlockDocument::CanUserOperateDocument(
					CBPCanUserOperateOperation::WriteDocument,
					$USER->GetID(),
					$subID,
					array(
						"IBlockId" => $intSubIBlockID,
						'IBlockRightsMode' => $arSubIBlock['RIGHTS_MODE'],
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

			if (!$bPermissions)
			{
				$lAdmin->AddGroupError(GetMessage("IBEL_A_UPDERR3")." (ID:".$subID.")", $subID);
				continue;
			}

			switch($_REQUEST['action'])
			{
			case "delete":
				if (CIBlockElementRights::UserHasRightTo($intSubIBlockID, $subID, "element_delete"))
				{
					@set_time_limit(0);
					$DB->StartTransaction();
					$APPLICATION->ResetException();
					if (!CIBlockElement::Delete($subID))
					{
						$DB->Rollback();
						if ($ex = $APPLICATION->GetException())
							$lAdmin->AddGroupError(GetMessage("IBLOCK_DELETE_ERROR")." [".$ex->GetString()."]", $subID);
						else
							$lAdmin->AddGroupError(GetMessage("IBLOCK_DELETE_ERROR"), $subID);
					}
					else
					{
						$DB->Commit();
					}
				}
				else
				{
					$lAdmin->AddGroupError(GetMessage("IBLOCK_DELETE_ERROR")." [".$subID."]", $subID);
				}
				break;
			case "activate":
			case "deactivate":
				if (CIBlockElementRights::UserHasRightTo($intSubIBlockID, $subID, "element_edit"))
				{
					$ob = new CIBlockElement();
					$arFields = Array("ACTIVE"=>($_REQUEST['action']=="activate"?"Y":"N"));
					if (!$ob->Update($subID, $arFields, true))
						$lAdmin->AddGroupError(GetMessage("IBEL_A_UPDERR").$ob->LAST_ERROR, $subID);
				}
				else
				{
					$lAdmin->AddGroupError(GetMessage("IBEL_A_UPDERR3")." (ID:".$subID.")", $subID);
				}
				break;
			case "wf_status":
				if ($boolSubWorkFlow)
				{
					$new_status = intval($_REQUEST["wf_status_id"]);
					if (
						$new_status > 0
					)
					{
						if (CIBlockElement::WF_GetStatusPermission($new_status) > 0
							|| CIBlockElementRights::UserHasRightTo($intSubIBlockID, $subID, "element_edit_any_wf_status")
						)
						{
							if ($arRes["WF_STATUS_ID"] != $new_status)
							{
								$obE = new CIBlockElement();
								$res = $obE->Update($subID, array(
									"WF_STATUS_ID" => $new_status,
									"MODIFIED_BY" => $USER->GetID(),
								), true);
								if (!$res)
									$lAdmin->AddGroupError(GetMessage("IBEL_A_SAVE_ERROR", array("#ID#" => $subID, "#ERROR_TEXT#" => $obE->LAST_ERROR)), $subID);
							}
						}
						else
						{
							$lAdmin->AddGroupError(GetMessage("IBEL_A_UPDERR3")." (ID:".$subID.")", $subID);
						}
					}
				}
				break;
			case "lock":
				if ($bWorkFlow && !CIBlockElementRights::UserHasRightTo($intSubIBlockID, $subID, "element_edit"))
				{
					$lAdmin->AddGroupError(GetMessage("IBEL_A_UPDERR3")." (ID:".$subID.")", $subID);
				}
				CIBlockElement::WF_Lock($subID);
				break;
			case "unlock":
				if ($bWorkFlow && !CIBlockElementRights::UserHasRightTo($intSubIBlockID, $subID, "element_edit"))
				{
					$lAdmin->AddGroupError(GetMessage("IBEL_A_UPDERR3")." (ID:".$ID.")", $ID);
					continue;
				}
				if ($bBizproc)
					call_user_func(array(ENTITY, "UnlockDocument"), $subID, "");
				else
					CIBlockElement::WF_UnLock($subID);
				break;
			}
		}
	}
}

CUtil::InitJSCore(array('translit'));

$CAdminCalendar_ShowScript = '';
if (true == B_ADMIN_SUBELEMENTS_LIST)
	$CAdminCalendar_ShowScript = CAdminCalendar::ShowScript();

$arHeader = Array();
$arHeader[] = array("id"=>"NAME", "content"=>GetMessage("IBLOCK_FIELD_NAME"), "sort"=>"name", "default"=>true);

$arHeader[] = array("id"=>"ACTIVE", "content"=>GetMessage("IBLOCK_FIELD_ACTIVE"), "sort"=>"active", "default"=>true, "align"=>"center");
$arHeader[] = array("id"=>"SORT", "content"=>GetMessage("IBLOCK_FIELD_SORT"), "sort"=>"sort", "default"=>true, "align"=>"right");
$arHeader[] = array("id"=>"TIMESTAMP_X", "content"=>GetMessage("IBLOCK_FIELD_TIMESTAMP_X"), "sort"=>"timestamp_x");
$arHeader[] = array("id"=>"USER_NAME", "content"=>GetMessage("IBLOCK_FIELD_USER_NAME"), "sort"=>"modified_by");
$arHeader[] = array("id"=>"DATE_CREATE", "content"=>GetMessage("IBLOCK_EL_ADMIN_DCREATE"), "sort"=>"created");
$arHeader[] = array("id"=>"CREATED_USER_NAME", "content"=>GetMessage("IBLOCK_EL_ADMIN_WCREATE2"), "sort"=>"created_by");

$arHeader[] = array("id"=>"CODE", "content"=>GetMessage("IBEL_A_CODE"), "sort"=>"code");
$arHeader[] = array("id"=>"EXTERNAL_ID", "content"=>GetMessage("IBEL_A_EXTERNAL_ID"), "sort"=>"external_id");
$arHeader[] = array("id"=>"TAGS", "content"=>GetMessage("IBEL_A_TAGS"), "sort"=>"tags");

if ($boolSubWorkFlow)
{
	$arHeader[] = array("id"=>"WF_STATUS_ID", "content"=>GetMessage("IBLOCK_FIELD_STATUS"), "sort"=>"status", "default"=>true);
	$arHeader[] = array("id"=>"WF_NEW", "content"=>GetMessage("IBEL_A_EXTERNAL_WFNEW"), "sort"=>"");
	$arHeader[] = array("id"=>"LOCK_STATUS", "content"=>GetMessage("IBEL_A_EXTERNAL_LOCK"), "default"=>true);
	$arHeader[] = array("id"=>"LOCKED_USER_NAME", "content"=>GetMessage("IBEL_A_EXTERNAL_LOCK_BY"));
	$arHeader[] = array("id"=>"WF_DATE_LOCK", "content"=>GetMessage("IBEL_A_EXTERNAL_LOCK_WHEN"));
	$arHeader[] = array("id"=>"WF_COMMENTS", "content"=>GetMessage("IBEL_A_EXTERNAL_COM"));
}

$arHeader[] = array("id"=>"ID", "content"=>'ID', "sort"=>"id", "default"=>true, "align"=>"right");
$arHeader[] = array("id"=>"SHOW_COUNTER", "content"=>GetMessage("IBEL_A_EXTERNAL_SHOWS"), "sort"=>"show_counter", "align"=>"right");
$arHeader[] = array("id"=>"SHOW_COUNTER_START", "content"=>GetMessage("IBEL_A_EXTERNAL_SHOW_F"), "sort"=>"show_counter_start", "align"=>"right");
$arHeader[] = array("id"=>"PREVIEW_PICTURE", "content"=>GetMessage("IBEL_A_EXTERNAL_PREV_PIC"), "align"=>"right");
$arHeader[] = array("id"=>"PREVIEW_TEXT", "content"=>GetMessage("IBEL_A_EXTERNAL_PREV_TEXT"));
$arHeader[] = array("id"=>"DETAIL_PICTURE", "content"=>GetMessage("IBEL_A_EXTERNAL_DET_PIC"), "align"=>"center");
$arHeader[] = array("id"=>"DETAIL_TEXT", "content"=>GetMessage("IBEL_A_EXTERNAL_DET_TEXT"));


foreach ($arProps as &$arFProps)
{
	if ($arSubCatalog['SKU_PROPERTY_ID'] != $arFProps['ID'])
		$arHeader[] = array("id"=>"PROPERTY_".$arFProps['ID'], "content"=>$arFProps['NAME'], "align"=>($arFProps["PROPERTY_TYPE"]=='N'?"right":"left"), "sort" => ($arFProps["MULTIPLE"]!='Y'? "PROPERTY_".$arFProps['ID'] : ""));
}
if (isset($arFProps))
	unset($arFProps);

$arWFStatus = Array();
if ($boolSubWorkFlow)
{
	$rsWF = CWorkflowStatus::GetDropDownList('Y');
	while($arWF = $rsWF->GetNext())
		$arWFStatus[$arWF["~REFERENCE_ID"]] = $arWF["~REFERENCE"];
}

if ($boolSubCatalog)
{
	$arHeader[] = array(
		"id" => "CATALOG_QUANTITY",
		"content" => GetMessage("IBEL_CATALOG_QUANTITY"),
		"align" => "right",
		"sort" => "CATALOG_QUANTITY",
	);
	$arHeader[] = array(
		"id" => "CATALOG_QUANTITY_TRACE",
		"content" => GetMessage("IBEL_CATALOG_QUANTITY_TRACE"),
		"align" => "right",
	);
	$arHeader[] = array(
		"id" => "CATALOG_WEIGHT",
		"content" => GetMessage("IBEL_CATALOG_WEIGHT"),
		"align" => "right",
		"sort" => "CATALOG_WEIGHT",
	);
	if ($USER->CanDoOperation('catalog_purchas_info'))
	{
		$arHeader[] = array(
			"id" => "CATALOG_PURCHASING_PRICE",
			"content" => GetMessage("IBEL_CATALOG_PURCHASING_PRICE"),
			"title" => "",
			"align" => "right",
			"sort" => "CATALOG_PURCHASING_PRICE",
			"default" => false,
		);
	}
	if (COption::GetOptionString("catalog", "default_use_store_control") == "Y")
	{
		$arHeader[] = array(
			"id" => "CATALOG_BAR_CODE",
			"content" => GetMessage("IBEL_CATALOG_BAR_CODE"),
			"title" => "",
			"align" => "right",
			"default" => false,
		);
	}

	$arCatGroup = Array();
	$arBaseGroup = CCatalogGroup::GetBaseGroup();
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
			"default" => ($arBaseGroup['ID'] == $arCatalogGroup["ID"] ? true : false),
		);
		$arCatGroup[$arCatalogGroup["ID"]] = $arCatalogGroup;
	}
	$arCatExtra = Array();

	$db_extras = CExtra::GetList(($by3="NAME"), ($order3="ASC"));
	while ($extras = $db_extras->Fetch())
		$arCatExtra[] = $extras;
}

if ($boolSubBizproc)
{
	$arWorkflowTemplates = CBPDocument::GetWorkflowTemplatesForDocumentType(array("iblock", "CIBlockDocument", "iblock_".$intSubIBlockID));
	foreach ($arWorkflowTemplates as $arTemplate)
	{
		$arHeader[] = array(
			"id" => "WF_".$arTemplate["ID"],
			"content" => $arTemplate["NAME"],
		);
	}
	$arHeader[] = array(
		"id" => "BIZPROC",
		"content" => GetMessage("IBEL_A_BP_H"),
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
foreach ($arProps as $i => $arProperty)
{
	$k = array_search("PROPERTY_".$arProperty['ID'], $arSelectedFields);
	if ($k!==false)
	{
		$arSelectedProps[] = $arProperty;
		if ($arProperty["PROPERTY_TYPE"] == "L")
		{
			$arSelect[$arProperty['ID']] = Array();
			$rs = CIBlockProperty::GetPropertyEnum($arProperty['ID']);
			while($ar = $rs->GetNext())
				$arSelect[$arProperty['ID']][$ar["ID"]] = $ar["VALUE"];
		}
		elseif ($arProperty["PROPERTY_TYPE"] == "G")
		{
			$arSelect[$arProperty['ID']] = Array();
			$rs = CIBlockSection::GetTreeList(Array("IBLOCK_ID"=>$arProperty["LINK_IBLOCK_ID"]));
			while($ar = $rs->GetNext())
				$arSelect[$arProperty['ID']][$ar["ID"]] = str_repeat(" . ", $ar["DEPTH_LEVEL"]).$ar["NAME"];
		}
		unset($arSelectedFields[$k]);
	}
}

if (!in_array("ID", $arSelectedFields))
	$arSelectedFields[] = "ID";
if (!in_array("CREATED_BY", $arSelectedFields))
	$arSelectedFields[] = "CREATED_BY";

$arSelectedFields[] = "LANG_DIR";
$arSelectedFields[] = "LID";
$arSelectedFields[] = "WF_PARENT_ELEMENT_ID";

if (in_array("LOCKED_USER_NAME", $arSelectedFields))
	$arSelectedFields[] = "WF_LOCKED_BY";
if (in_array("USER_NAME", $arSelectedFields))
	$arSelectedFields[] = "MODIFIED_BY";
if (in_array("CREATED_USER_NAME", $arSelectedFields))
	$arSelectedFields[] = "CREATED_BY";
if (in_array("PREVIEW_TEXT", $arSelectedFields))
	$arSelectedFields[] = "PREVIEW_TEXT_TYPE";
if (in_array("DETAIL_TEXT", $arSelectedFields))
	$arSelectedFields[] = "DETAIL_TEXT_TYPE";

$arSelectedFields[] = "LOCK_STATUS";
$arSelectedFields[] = "WF_NEW";
$arSelectedFields[] = "WF_STATUS_ID";
$arSelectedFields[] = "DETAIL_PAGE_URL";
$arSelectedFields[] = "SITE_ID";
$arSelectedFields[] = "CODE";
$arSelectedFields[] = "EXTERNAL_ID";

if ($boolSubCatalog)
{
	if(in_array("CATALOG_QUANTITY_TRACE", $arSelectedFields))
		$arSelectedFields[] = "CATALOG_QUANTITY_TRACE_ORIG";
	if ($USER->CanDoOperation('catalog_purchas_info'))
	{
		if (in_array("CATALOG_PURCHASING_PRICE", $arSelectedFields))
			$arSelectedFields[] = "CATALOG_PURCHASING_CURRENCY";
	}
	if (is_array($arCatGroup) && !empty($arCatGroup))
	{
		$boolPriceInc = false;
		foreach ($arCatGroup as &$CatalogGroups)
		{
			if (in_array("CATALOG_GROUP_".$CatalogGroups["ID"], $arSelectedFields))
			{
				$arFilter["CATALOG_SHOP_QUANTITY_".$CatalogGroups["ID"]] = 1;
				$boolPriceInc = true;
			}
		}
		unset($CatalogGroups);
		if ($boolPriceInc)
		{
			$boolSubCurrency = CModule::IncludeModule('currency');
			if ($boolSubCurrency)
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

$arSelectedFieldsMap = array();
foreach ($arSelectedFields as $field)
	$arSelectedFieldsMap[$field] = true;

if (!((false == B_ADMIN_SUBELEMENTS_LIST) && ($bCopy)))
{
	$wf_status_id = "";
	/*if ($boolSubWorkFlow && (strpos($find_el_status_id, "-") !== false))
	{
		$ar = explode("-", $find_el_status_id);
		$wf_status_id = $ar[1];
	}

	if ($wf_status_id)
	{
		$rsData = CIBlockElement::GetList(
			Array($by=>$order),
			$arFilter,
			false,
			false,
			$arSelectedFields
		);
		while($arElement = $rsData->Fetch())
		{
			if ($wf_status_id!==false)
			{
				$LAST_ID = CIBlockElement::WF_GetLast($arElement['ID']);
				if ($LAST_ID!=$arElement['ID'])
				{
					$rsData2 = CIBlockElement::GetList(
						Array(),
						Array(
							"ID"=>$LAST_ID,
							"SHOW_HISTORY"=>"Y"
							),
						false,
						Array("nTopCount"=>1),
						array("ID","WF_STATUS_ID")
					);
					if ($arRes = $rsData2->Fetch())
					{
						if ($arRes["WF_STATUS_ID"]!=$wf_status_id)
							continue;
					}
				}
				else
					continue;
			}
			$arResult[]=$arElement;
		}
		$rsData = new CDBResult();
		$rsData->InitFromArray($arResult);
		$rsData = new CAdminResult($rsData, $sTableID);
	}
	else
	{
		$rsData = CIBlockElement::GetList(
			Array($by=>$order),
			$arFilter,
			false,
		//	Array("nPageSize"=>CAdminResult::GetNavSize($sTableID)),
			false,
			$arSelectedFields
		);
		$rsData->SetTableID($sTableID);
		$wf_status_id = false;
	} */
	if(isset($_REQUEST["mode"]) && $_REQUEST["mode"] == "excel")
		$arNavParams = false;
	else
		$arNavParams = array("nPageSize"=>CAdminSubResult::GetNavSize($sTableID, 20, $lAdmin->GetListUrl(true)));

	$rsData = CIBlockElement::GetList(
		array($by=>$order),
		$arFilter,
		false,
		$arNavParams,
		$arSelectedFields
	);
	$rsData = new CAdminSubResult($rsData, $sTableID, $lAdmin->GetListUrl(true));
	$wf_status_id = false;

	$rsData->NavStart();
	$lAdmin->NavText($rsData->GetNavPrint(htmlspecialcharsbx($arSubIBlock["ELEMENTS_NAME"])));

	function GetElementName($ID)
	{
		$ID = intval($ID);
		static $cache = array();
		if (!array_key_exists($ID, $cache))
		{
			$rsElement = CIBlockElement::GetList(Array(), Array("ID"=>$ID, "SHOW_HISTORY"=>"Y"), false, false, array("ID","IBLOCK_ID","NAME"));
			$cache[$ID] = $rsElement->GetNext();
		}
		return $cache[$ID];
	}
	function GetIBlockTypeID($intSubIBlockID)
	{
		$intSubIBlockID = IntVal($intSubIBlockID);
		if (0 > $intSubIBlockID)
			$intSubIBlockID = 0;
		static $cache = array();
		if (!array_key_exists($intSubIBlockID, $cache))
		{
			$rsIBlock = CIBlock::GetByID($intSubIBlockID);
			if (!($cache[$intSubIBlockID] = $rsIBlock->GetNext()))
				$cache[$intSubIBlockID] = array("IBLOCK_TYPE_ID"=>"");
		}
		return $cache[$intSubIBlockID]["IBLOCK_TYPE_ID"];
	}

	$arRows = array();

	$boolSubSearch = CModule::IncludeModule('search');

	$boolOldOffers = false;
	while ($arRes = $rsData->NavNext(true, "f_"))
	{
		$arRes_orig = $arRes;
		// in workflow mode show latest changes
		if ($boolSubWorkFlow)
		{
			$LAST_ID = CIBlockElement::WF_GetLast($arRes['ID']);
			if ($LAST_ID!=$arRes['ID'])
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
				if (isset($arCatGroup))
				{
					$arRes_tmp = Array();
					foreach ($arRes as $vv => $vval)
					{
						if (substr($vv, 0, 8) == "CATALOG_")
							$arRes_tmp[$vv] = $arRes[$vv];
					}
				}

				$arRes = $rsData2->NavNext(true, "f_");
				if (isset($arCatGroup))
					$arRes = array_merge($arRes, $arRes_tmp);

				$f_ID = $arRes_orig["ID"];
			}
			$lockStatus = $arRes_orig['LOCK_STATUS'];
		}
		elseif ($boolSubBizproc)
		{
			$lockStatus = CIBlockDocument::IsDocumentLocked($f_ID, "") ? "red" : "green";
		}
		else
		{
			$lockStatus = "";
		}

		if (array_key_exists("CATALOG_QUANTITY_TRACE", $arSelectedFieldsMap))
		{
			$arRes['CATALOG_QUANTITY_TRACE'] = $arRes['CATALOG_QUANTITY_TRACE_ORIG'];
			$f_CATALOG_QUANTITY_TRACE = $f_CATALOG_QUANTITY_TRACE_ORIG;
		}

		$arRes['lockStatus'] = $lockStatus;
		$arRes["orig"] = $arRes_orig;

		$edit_url = '/bitrix/admin/iblock_subelement_edit.php?WF=Y&type='.urlencode($strSubIBlockType).'&IBLOCK_ID='.$intSubIBlockID.'&lang='.LANGUAGE_ID.'&PRODUCT_ID='.$ID.'&ID='.$arRes_orig['ID'].'&TMP_ID='.$strSubTMP_ID.$sThisSectionUrl;
		$arRows[$f_ID] = $row = $lAdmin->AddRow($f_ID, $arRes, $edit_url, GetMessage("IB_SE_L_EDIT_ELEMENT"), true);

		$boolEditPrice = false;
		$boolEditPrice = CIBlockElementRights::UserHasRightTo($intSubIBlockID, $f_ID, "element_edit_price");

		$row->AddViewField("ID",$f_ID);

		if ($f_LOCKED_USER_NAME)
			$row->AddViewField("LOCKED_USER_NAME", '<a href="/bitrix/admin/user_edit.php?lang='.LANGUAGE_ID.'&ID='.$f_WF_LOCKED_BY.'" title="'.GetMessage("IBEL_A_USERINFO").'">'.$f_LOCKED_USER_NAME.'</a>');
		if ($f_USER_NAME)
			$row->AddViewField("USER_NAME", '<a href="/bitrix/admin/user_edit.php?lang='.LANGUAGE_ID.'&ID='.$f_MODIFIED_BY.'" title="'.GetMessage("IBEL_A_USERINFO").'">'.$f_USER_NAME.'</a>');
		$row->AddViewField("CREATED_USER_NAME", '<a href="/bitrix/admin/user_edit.php?lang='.LANGUAGE_ID.'&ID='.$f_CREATED_BY.'" title="'.GetMessage("IBEL_A_USERINFO").'">'.$f_CREATED_USER_NAME.'</a>');

		if ($bSubWorkFlow || $bSubBizproc)
		{
			$lamp = "/bitrix/images/workflow/".$lockStatus.".gif";
			if ($lockStatus=="green")
				$lamp_alt = GetMessage("IBLOCK_GREEN_ALT");
			elseif ($lockStatus=="yellow")
				$lamp_alt = GetMessage("IBLOCK_YELLOW_ALT");
			else
				$lamp_alt = GetMessage("IBLOCK_RED_ALT");

			if ($lockStatus=='red' && $arRes_orig['LOCKED_USER_NAME']!='')
				$row->AddViewField("LOCK_STATUS", '<table cellpadding="0" cellspacing="0" border="0"><tr><td><img hspace="4" src="'.$lamp.'" alt="'.htmlspecialcharsbx($lamp_alt).'" title="'.htmlspecialcharsbx($lamp_alt).'" /></td><td>'.$arRes_orig['LOCKED_USER_NAME'].$unlock.'</td></tr></table>');
			else
				$row->AddViewField("LOCK_STATUS", '<img src="'.$lamp.'" hspace="4" alt="'.htmlspecialcharsbx($lamp_alt).'" title="'.htmlspecialcharsbx($lamp_alt).'" />');
		}

		if ($bSubBizproc)
			$row->AddCheckField("BP_PUBLISHED", false);

		$row->arRes['props'] = array();
		$arProperties = array();
		if (count($arSelectedProps) > 0)
		{
			$rsProperties = CIBlockElement::GetProperty($intSubIBlockID, $arRes["ID"]);
			while($ar = $rsProperties->GetNext())
			{
				if (!array_key_exists($ar["ID"], $arProperties))
					$arProperties[$ar["ID"]] = array();
				$arProperties[$ar["ID"]][$ar["PROPERTY_VALUE_ID"]] = $ar;
			}
		}

		foreach ($arSelectedProps as $aProp)
		{
			$arViewHTML = array();
			$arEditHTML = array();
			if (strlen($aProp["USER_TYPE"])>0)
				$arUserType = CIBlockProperty::GetUserType($aProp["USER_TYPE"]);
			else
				$arUserType = array();
			$max_file_size_show=100000;

			$last_property_id = false;
			foreach ($arProperties[$aProp["ID"]] as $prop_id => $prop)
			{
				$prop['PROPERTY_VALUE_ID'] = intval($prop['PROPERTY_VALUE_ID']);
				$VALUE_NAME = 'FIELDS['.$f_ID.'][PROPERTY_'.$prop['ID'].']['.$prop['PROPERTY_VALUE_ID'].'][VALUE]';
				$DESCR_NAME = 'FIELDS['.$f_ID.'][PROPERTY_'.$prop['ID'].']['.$prop['PROPERTY_VALUE_ID'].'][DESCRIPTION]';
				//View part
				if (array_key_exists("GetAdminListViewHTML", $arUserType))
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
				elseif ($prop['PROPERTY_TYPE']=='N')
					$arViewHTML[] = $prop["VALUE"];
				elseif ($prop['PROPERTY_TYPE']=='S')
					$arViewHTML[] = $prop["VALUE"];
				elseif ($prop['PROPERTY_TYPE']=='L')
					$arViewHTML[] = $prop["VALUE_ENUM"];
				elseif ($prop['PROPERTY_TYPE']=='F')
				{
					$arViewHTML[] = CFile::ShowFile($prop["VALUE"], 100000, 50, 50, true);
				}
				elseif ($prop['PROPERTY_TYPE']=='G')
				{
					if (intval($prop["VALUE"])>0)
					{
						$rsSection = CIBlockSection::GetList(Array(), Array("ID" => $prop["VALUE"]));
						if ($arSection = $rsSection->GetNext())
						{
							$arViewHTML[] = $arSection['NAME'].
							' [<a href="/bitrix/admin/iblock_section_edit.php?type='.GetIBlockTypeID($arSection['IBLOCK_ID']).
							'&amp;IBLOCK_ID='.$arSection['IBLOCK_ID'].
							'&amp;ID='.$arSection['ID'].
							'&amp;lang='.LANGUAGE_ID.
							'" title="'.GetMessage("IBEL_A_SEC_EDIT").'">'.$arSection['ID'].'</a>]';
						}
					}
				}
				elseif ($prop['PROPERTY_TYPE']=='E')
				{
					if ($t = GetElementName($prop["VALUE"]))
					{
						$arViewHTML[] = $t['NAME'].
						' [<a href="/bitrix/admin/iblock_element_edit.php?WF=Y&type='.GetIBlockTypeID($t['IBLOCK_ID']).
						'&amp;IBLOCK_ID='.$t['IBLOCK_ID'].
						'&amp;ID='.$t['ID'].
						'&amp;lang='.LANGUAGE_ID.
						'" title="'.GetMessage("IBEL_A_EL_EDIT").'">'.$t['ID'].'</a>]';
					}
				}
				//Edit Part
				$bUserMultiple = $prop["MULTIPLE"] == "Y" && array_key_exists("GetPropertyFieldHtmlMulty", $arUserType);
				if ($bUserMultiple)
				{
					if ($last_property_id != $prop["ID"])
					{
						$VALUE_NAME = 'FIELDS['.$f_ID.'][PROPERTY_'.$prop['ID'].']';
						$arEditHTML[] = call_user_func_array($arUserType["GetPropertyFieldHtmlMulty"], array(
							$prop,
							$arProperties[$prop["ID"]],
							array(
								"VALUE" => $VALUE_NAME,
								"MODE"=>"iblock_element_admin",
								"FORM_NAME"=>"form_".$sTableID,
							)
						));
					}
				}
				elseif (('F' != $prop['PROPERTY_TYPE']) && array_key_exists("GetPropertyFieldHtml", $arUserType))
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
				elseif ($prop['PROPERTY_TYPE']=='N' || $prop['PROPERTY_TYPE']=='S')
				{
					if ($prop["ROW_COUNT"] > 1)
						$html = '<textarea name="'.$VALUE_NAME.'" cols="'.$prop["COL_COUNT"].'" rows="'.$prop["ROW_COUNT"].'">'.$prop["VALUE"].'</textarea>';
					else
						$html = '<input type="text" name="'.$VALUE_NAME.'" value="'.$prop["VALUE"].'" size="'.$prop["COL_COUNT"].'">';
					if ($prop["WITH_DESCRIPTION"] == "Y")
						$html .= ' <span title="'.GetMessage("IBLOCK_ELEMENT_EDIT_PROP_DESC").'">'.GetMessage("IBLOCK_ELEMENT_EDIT_PROP_DESC_1").
							'<input type="text" name="'.$DESCR_NAME.'" value="'.$prop["DESCRIPTION"].'" size="18"></span>';
					$arEditHTML[] = $html;
				}
				elseif ($prop['PROPERTY_TYPE']=='L' && ($last_property_id!=$prop["ID"]))
				{
					$VALUE_NAME = 'FIELDS['.$f_ID.'][PROPERTY_'.$prop['ID'].'][]';
					$arValues = array();
					foreach ($arProperties[$prop["ID"]] as $g_prop)
					{
						$g_prop = intval($g_prop["VALUE"]);
						if ($g_prop > 0)
							$arValues[$g_prop] = $g_prop;
					}
					if ($prop['LIST_TYPE']=='C')
					{
						if ($prop['MULTIPLE'] == "Y" || count($arSelect[$prop['ID']]) == 1)
						{
							$html = '<input type="hidden" name="'.$VALUE_NAME.'" value="">';
							foreach ($arSelect[$prop['ID']] as $value => $display)
							{
								$html .= '<input type="checkbox" name="'.$VALUE_NAME.'" id="'.$prop["PROPERTY_VALUE_ID"]."_".$value.'" value="'.$value.'"';
								if (array_key_exists($value, $arValues))
									$html .= ' checked';
								$html .= '>&nbsp;<label for="'.$prop["PROPERTY_VALUE_ID"]."_".$value.'">'.$display.'</label><br>';
							}
						}
						else
						{
							$html = '<input type="radio" name="'.$VALUE_NAME.'" id="'.$prop["PROPERTY_VALUE_ID"].'_none" value=""';
							if (count($arValues) < 1)
								$html .= ' checked';
							$html .= '>&nbsp;<label for="'.$prop["PROPERTY_VALUE_ID"].'_none">'.GetMessage("IBLOCK_ELEMENT_EDIT_NOT_SET").'</label><br>';
							foreach ($arSelect[$prop['ID']] as $value => $display)
							{
								$html .= '<input type="radio" name="'.$VALUE_NAME.'" id="'.$prop["PROPERTY_VALUE_ID"]."_".$value.'" value="'.$value.'"';
								if (array_key_exists($value, $arValues))
									$html .= ' checked';
								$html .= '>&nbsp;<label for="'.$prop["PROPERTY_VALUE_ID"]."_".$value.'">'.$display.'</label><br>';
							}
						}
					}
					else
					{
						$html = '<select name="'.$VALUE_NAME.'" size="'.$prop["MULTIPLE_CNT"].'" '.($prop["MULTIPLE"]=="Y"?"multiple":"").'>';
						$html .= '<option value=""'.(count($arValues) < 1? ' selected': '').'>'.GetMessage("IBLOCK_ELEMENT_EDIT_NOT_SET").'</option>';
						foreach ($arSelect[$prop['ID']] as $value => $display)
						{
							$html .= '<option value="'.$value.'"';
							if (array_key_exists($value, $arValues))
								$html .= ' selected';
							$html .= '>'.$display.'</option>'."\n";
						}
						$html .= "</select>\n";
					}
					$arEditHTML[] = $html;
				}
				elseif ($prop['PROPERTY_TYPE']=='F')
				{
				}
				elseif (($prop['PROPERTY_TYPE']=='G') && ($last_property_id!=$prop["ID"]))
				{
					$VALUE_NAME = 'FIELDS['.$f_ID.'][PROPERTY_'.$prop['ID'].'][]';
					$arValues = array();
					foreach ($arProperties[$prop["ID"]] as $g_prop)
					{
						$g_prop = intval($g_prop["VALUE"]);
						if ($g_prop > 0)
							$arValues[$g_prop] = $g_prop;
					}
					$html = '<select name="'.$VALUE_NAME.'" size="'.$prop["MULTIPLE_CNT"].'" '.($prop["MULTIPLE"]=="Y"?"multiple":"").'>';
					$html .= '<option value=""'.(count($arValues) < 1? ' selected': '').'>'.GetMessage("IBLOCK_ELEMENT_EDIT_NOT_SET").'</option>';
					foreach ($arSelect[$prop['ID']] as $value => $display)
					{
						$html .= '<option value="'.$value.'"';
						if (array_key_exists($value, $arValues))
							$html .= ' selected';
						$html .= '>'.$display.'</option>'."\n";
					}
					$html .= "</select>\n";
					$arEditHTML[] = $html;
				}
				elseif ($prop['PROPERTY_TYPE']=='E')
				{
					$VALUE_NAME = 'FIELDS['.$f_ID.'][PROPERTY_'.$prop['ID'].']['.$prop['PROPERTY_VALUE_ID'].']';
					if ($t = GetElementName($prop["VALUE"]))
					{
						$arEditHTML[] = '<input type="text" name="'.$VALUE_NAME.'" id="'.$VALUE_NAME.'" value="'.$prop["VALUE"].'" size="5">'.
						'<input type="button" value="..." onClick="jsUtils.OpenWindow(\'iblock_element_search.php?lang='.LANGUAGE_ID.'&amp;IBLOCK_ID='.$prop["LINK_IBLOCK_ID"].'&amp;n='.urlencode($VALUE_NAME).'\', 600, 500);">'.
						'&nbsp;<span id="sp_'.$VALUE_NAME.'" >'.$t['NAME'].'</span>';
					}
					else
					{
						$arEditHTML[] = '<input type="text" name="'.$VALUE_NAME.'" id="'.$VALUE_NAME.'" value="" size="5">'.
						'<input type="button" value="..." onClick="jsUtils.OpenWindow(\'iblock_element_search.php?lang='.LANGUAGE_ID.'&amp;IBLOCK_ID='.$prop["LINK_IBLOCK_ID"].'&amp;n='.urlencode($VALUE_NAME).'\', 600, 500);">'.
						'&nbsp;<span id="sp_'.$VALUE_NAME.'" ></span>';
					}
				}
				$last_property_id = $prop['ID'];
			}
			$table_id = md5($f_ID.':'.$aProp['ID']);
			if ($aProp["MULTIPLE"] == "Y")
			{
				$VALUE_NAME = 'FIELDS['.$f_ID.'][PROPERTY_'.$prop['ID'].'][n0][VALUE]';
				$DESCR_NAME = 'FIELDS['.$f_ID.'][PROPERTY_'.$prop['ID'].'][n0][DESCRIPTION]';
				if (array_key_exists("GetPropertyFieldHtmlMulty", $arUserType))
				{
				}
				elseif (('F' != $prop['PROPERTY_TYPE']) && array_key_exists("GetPropertyFieldHtml", $arUserType))
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
				elseif ($prop['PROPERTY_TYPE']=='N' || $prop['PROPERTY_TYPE']=='S')
				{
					if ($prop["ROW_COUNT"] > 1)
						$html = '<textarea name="'.$VALUE_NAME.'" cols="'.$prop["COL_COUNT"].'" rows="'.$prop["ROW_COUNT"].'"></textarea>';
					else
						$html = '<input type="text" name="'.$VALUE_NAME.'" value="" size="'.$prop["COL_COUNT"].'">';
					if ($prop["WITH_DESCRIPTION"] == "Y")
						$html .= ' <span title="'.GetMessage("IBLOCK_ELEMENT_EDIT_PROP_DESC").'">'.GetMessage("IBLOCK_ELEMENT_EDIT_PROP_DESC_1").'<input type="text" name="'.$DESCR_NAME.'" value="" size="18"></span>';
					$arEditHTML[] = $html;
				}
				elseif ($prop['PROPERTY_TYPE']=='F')
				{
				}
				elseif ($prop['PROPERTY_TYPE']=='E')
				{
					$VALUE_NAME = 'FIELDS['.$f_ID.'][PROPERTY_'.$prop['ID'].'][n0]';
					$arEditHTML[] = '<input type="text" name="'.$VALUE_NAME.'" id="'.$VALUE_NAME.'" value="" size="5">'.
						'<input type="button" value="..." onClick="jsUtils.OpenWindow(\'iblock_element_search.php?lang='.LANGUAGE_ID.'&amp;IBLOCK_ID='.$prop["LINK_IBLOCK_ID"].'&amp;n='.urlencode($VALUE_NAME).'\', 600, 500);">'.
						'&nbsp;<span id="sp_'.$VALUE_NAME.'" ></span>';
				}

				if ($prop["PROPERTY_TYPE"]!=="G" && $prop["PROPERTY_TYPE"]!=="L" && !$bUserMultiple)
					$arEditHTML[] = '<input type="button" value="'.GetMessage("IBLOCK_ELEMENT_EDIT_PROP_ADD").'" onClick="addNewRow(\'tb'.$table_id.'\')">';
			}
			if (count($arViewHTML) > 0)
				$row->AddViewField("PROPERTY_".$aProp['ID'], implode(" / ", $arViewHTML)."&nbsp;");
			if (count($arEditHTML) > 0)
				$row->arRes['props']["PROPERTY_".$aProp['ID']] = array("table_id"=>$table_id, "html"=>$arEditHTML);
		}

		if ($boolSubCatalog)
		{
			if (isset($arCatGroup) && !empty($arCatGroup))
			{
				$row->arRes['price'] = array();
				foreach ($arCatGroup as &$CatGroup)
				{
					if (array_key_exists("CATALOG_GROUP_".$CatGroup["ID"], $arSelectedFieldsMap))
					{
						$price = "";
						$sHTML = "";
						$selectCur = "";
						if ($boolSubCurrency)
						{
							$price = CurrencyFormat($arRes["CATALOG_PRICE_".$CatGroup["ID"]],$arRes["CATALOG_CURRENCY_".$CatGroup["ID"]]);
							if ($USER->CanDoOperation('catalog_price') && $boolEditPrice)
							{
								$selectCur = '<select name="CATALOG_CURRENCY['.$f_ID.']['.$CatGroup["ID"].']" id="CATALOG_CURRENCY['.$f_ID.']['.$CatGroup["ID"].']"';
								if (intval($arRes["CATALOG_EXTRA_ID_".$CatGroup["ID"]])>0)
									$selectCur .= ' disabled="disabled" readonly="readonly"';
								if ($CatGroup["BASE"]=="Y")
									$selectCur .= ' onchange="top.SubChangeBaseCurrency('.$f_ID.')"';
								$selectCur .= '>';
								foreach ($arCurrencyList as &$arOneCurrency)
								{
									$selectCur .= '<option value="'.$arOneCurrency["CURRENCY"].'"';
									if ($arOneCurrency["~CURRENCY"] == $arRes["CATALOG_CURRENCY_".$CatGroup["ID"]])
										$selectCur .= ' selected';
									$selectCur .= '>'.$arOneCurrency["CURRENCY"].'</option>';
								}
								unset($arOneCurrency);
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
								$sHTML .= ' onchange="top.SubChangeBasePrice('.$f_ID.')"';
							if (intval($arRes["CATALOG_EXTRA_ID_".$CatGroup["ID"]])>0)
								$sHTML .= ' disabled readonly';
							$sHTML .= '> '.$selectCur;
							if (intval($arRes["CATALOG_EXTRA_ID_".$CatGroup["ID"]])>0)
								$sHTML .= '<input type="hidden" id="CATALOG_EXTRA['.$f_ID.']['.$CatGroup["ID"].']" name="CATALOG_EXTRA['.$f_ID.']['.$CatGroup["ID"].']" value="'.$arRes["CATALOG_EXTRA_ID_".$CatGroup["ID"]].'">';

							$sHTML .= '<input type="hidden" name="CATALOG_old_PRICE['.$f_ID.']['.$CatGroup["ID"].']" value="'.$arRes["CATALOG_PRICE_".$CatGroup["ID"]].'">';
							$sHTML .= '<input type="hidden" name="CATALOG_old_CURRENCY['.$f_ID.']['.$CatGroup["ID"].']" value="'.$arRes["CATALOG_CURRENCY_".$CatGroup["ID"]].'">';
							$sHTML .= '<input type="hidden" name="CATALOG_PRICE_ID['.$f_ID.']['.$CatGroup["ID"].']" value="'.$arRes["CATALOG_PRICE_ID_".$CatGroup["ID"]].'">';
							$sHTML .= '<input type="hidden" name="CATALOG_QUANTITY_FROM['.$f_ID.']['.$CatGroup["ID"].']" value="'.$arRes["CATALOG_QUANTITY_FROM_".$CatGroup["ID"]].'">';
							$sHTML .= '<input type="hidden" name="CATALOG_QUANTITY_TO['.$f_ID.']['.$CatGroup["ID"].']" value="'.$arRes["CATALOG_QUANTITY_TO_".$CatGroup["ID"]].'">';

							$row->arRes['price']["CATALOG_GROUP_".$CatGroup["ID"]] = $sHTML;
						}
					}
				}
				unset($CatGroup);
			}
		}

		if ($boolSubBizproc)
		{
			$arDocumentStates = CBPDocument::GetDocumentStates(
				array("iblock", "CIBlockDocument", "iblock_".$intSubIBlockID),
				array("iblock", "CIBlockDocument", $f_ID)
			);

			$arRes["CURRENT_USER_GROUPS"] = $USER->GetUserGroupArray();
			if ($arRes["CREATED_BY"] == $USER->GetID())
				$arRes["CURRENT_USER_GROUPS"][] = "Author";
			$row->arRes["CURRENT_USER_GROUPS"] = $arRes["CURRENT_USER_GROUPS"];

			$arStr = array();
			$arStr1 = array();
			foreach ($arDocumentStates as $kk => $vv)
			{
				$canViewWorkflow = CIBlockDocument::CanUserOperateDocument(
					CBPCanUserOperateOperation::ViewWorkflow,
					$USER->GetID(),
					$f_ID,
					array("AllUserGroups" => $arRes["CURRENT_USER_GROUPS"], "DocumentStates" => $arDocumentStates, "WorkflowId" => $kk)
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
						$arStr[$vv["TEMPLATE_ID"]] .= GetMessage("IBEL_A_BP_TASK").":<br /><a href=\"bizproc_task.php?id=".$arTask["ID"]."\" title=\"".$arTask["DESCRIPTION"]."\">".$arTask["NAME"]."</a><br /><br />";
					}
				}
			}

			$str = "";
			foreach ($arStr as $k => $v)
			{
				$row->AddViewField("WF_".$k, $v);
				$str .= "<b>".(strlen($arStr1[$k]) > 0 ? $arStr1[$k] : GetMessage("IBEL_A_BP_PROC"))."</b>:<br />".$v."<br />";
			}

			$row->AddViewField("BIZPROC", $str);
		}
	}

	$boolIBlockElementAdd = CIBlockSectionRights::UserHasRightTo($intSubIBlockID, $find_section_section, "section_element_bind");

$availQuantityTrace = COption::GetOptionString("catalog", "default_quantity_trace", 'N');
$arQuantityTrace = array(
	"D" => GetMessage("IBEL_DEFAULT_VALUE")." (".($availQuantityTrace=='Y' ? GetMessage("IBEL_YES_VALUE") : GetMessage("IBEL_NO_VALUE")).")",
	"Y" => GetMessage("IBEL_YES_VALUE"),
	"N" => GetMessage("IBEL_NO_VALUE"),
);

if (COption::GetOptionString("catalog", "default_use_store_control") == "Y" && in_array("CATALOG_BAR_CODE", $arSelectedFields))
{
	$rsProducts = CCatalogProduct::GetList(
		array(),
		array("ID" => array_keys($arRows)),
		false,
		false,
		array('ID', 'BARCODE_MULTI')
	);
	$productsWithBarCode = array();
	while ($product = $rsProducts->Fetch())
	{
		if (isset($arRows[$product["ID"]]))
		{
			if ($product["BARCODE_MULTI"] == "Y")
				$arRows[$product["ID"]]->arRes["CATALOG_BAR_CODE"] = GetMessage("IBEL_CATALOG_BAR_CODE_MULTI");
			else
				$productsWithBarCode[] = $product["ID"];
		}
	}
	if (!empty($productsWithBarCode))
	{
		$rsProducts = CCatalogStoreBarCode::getList(array(), array(
			"PRODUCT_ID" => $productsWithBarCode,
		));
		while ($product = $rsProducts->Fetch())
		{
			if (isset($arRows[$product["PRODUCT_ID"]]))
			{
				$arRows[$product["PRODUCT_ID"]]->arRes["CATALOG_BAR_CODE"] = htmlspecialcharsEx($product["BARCODE"]);
			}
		}
	}
}

	$arElementOps = CIBlockElementRights::UserHasRightTo(
		$intSubIBlockID,
		array_keys($arRows),
		"",
		CIBlockRights::RETURN_OPERATIONS
	);
	foreach ($arRows as $f_ID => $row)
	{
		$edit_url = '/bitrix/admin/iblock_subelement_edit.php?WF=Y&type='.urlencode($strSubIBlockType).'&IBLOCK_ID='.$intSubIBlockID.'&lang='.LANGUAGE_ID.'&PRODUCT_ID='.$ID.'&ID='.$row->arRes['orig']['ID'].'&TMP_ID='.$strSubTMP_ID.$sThisSectionUrl;

		if (array_key_exists("PREVIEW_PICTURE", $arSelectedFieldsMap))
			$row->AddViewField("PREVIEW_PICTURE", CFile::ShowFile($row->arRes['PREVIEW_PICTURE'], 100000, 50, 50, true));
		if (array_key_exists("DETAIL_PICTURE", $arSelectedFieldsMap))
			$row->AddViewField("DETAIL_PICTURE", CFile::ShowFile($row->arRes['DETAIL_PICTURE'], 100000, 50, 50, true));
		if (array_key_exists("PREVIEW_TEXT", $arSelectedFieldsMap))
			$row->AddViewField("PREVIEW_TEXT", ($row->arRes["PREVIEW_TEXT_TYPE"]=="text" ? htmlspecialcharsex($row->arRes["PREVIEW_TEXT"]) : HTMLToTxt($row->arRes["PREVIEW_TEXT"])));
		if (array_key_exists("DETAIL_TEXT", $arSelectedFieldsMap))
			$row->AddViewField("DETAIL_TEXT", ($row->arRes["DETAIL_TEXT_TYPE"]=="text" ? htmlspecialcharsex($row->arRes["DETAIL_TEXT"]) : HTMLToTxt($row->arRes["DETAIL_TEXT"])));

		if (isset($arElementOps[$f_ID]) && isset($arElementOps[$f_ID]["element_edit"]))
		{
			if (isset($arElementOps[$f_ID]) && isset($arElementOps[$f_ID]["element_edit_price"]))
			{
				if (isset($row->arRes['price']) && is_array($row->arRes['price']))
					foreach ($row->arRes['price'] as $price_id => $sHTML)
						$row->AddEditField($price_id, $sHTML);
			}

			$row->AddCheckField("ACTIVE");
			$row->AddInputField("NAME", array('size'=>'35'));
			$row->AddViewField("NAME", '<div class="iblock_menu_icon_elements"></div>'.htmlspecialcharsex($row->arRes["NAME"]));
			$row->AddInputField("SORT", array('size'=>'3'));
			$row->AddInputField("CODE");
			$row->AddInputField("EXTERNAL_ID");
			if ($boolSubSearch)
			{
				$row->AddViewField("TAGS", htmlspecialcharsex($row->arRes["TAGS"]));
				$row->AddEditField("TAGS", InputTags("FIELDS[".$f_ID."][TAGS]", $row->arRes["TAGS"], $arSubIBlock["SITE_ID"]));
			}
			else
			{
				$row->AddInputField("TAGS");
			}
			if ($arWFStatus)
			{
				$row->AddSelectField("WF_STATUS_ID", $arWFStatus);
				if ($row->arRes['orig']['WF_NEW']=='Y' || $row->arRes['WF_STATUS_ID']=='1')
					$row->AddViewField("WF_STATUS_ID", htmlspecialcharsex($arWFStatus[$row->arRes['WF_STATUS_ID']]));
				else
					$row->AddViewField("WF_STATUS_ID", '<a href="'.$edit_url.'" title="'.GetMessage("IBEL_A_ED_TITLE").'">'.htmlspecialcharsex($arWFStatus[$row->arRes['WF_STATUS_ID']]).'</a> / <a href="'.'iblock_element_edit.php?ID='.$row->arRes['orig']['ID'].(!isset($arElementOps[$f_ID]) || !isset($arElementOps[$f_ID]["element_edit_any_wf_status"])?'&view=Y':'').$sThisSectionUrl.'" title="'.GetMessage("IBEL_A_ED2_TITLE").'">'.htmlspecialcharsex($arWFStatus[$row->arRes['orig']['WF_STATUS_ID']]).'</a>');
			}
			if (array_key_exists("PREVIEW_TEXT", $arSelectedFieldsMap))
			{
				$sHTML = '<input type="radio" name="FIELDS['.$f_ID.'][PREVIEW_TEXT_TYPE]" value="text" id="'.$f_ID.'PREVIEWtext"';
				if ($row->arRes["PREVIEW_TEXT_TYPE"]!="html")
					$sHTML .= ' checked';
				$sHTML .= '><label for="'.$f_ID.'PREVIEWtext">text</label> /';
				$sHTML .= '<input type="radio" name="FIELDS['.$f_ID.'][PREVIEW_TEXT_TYPE]" value="html" id="'.$f_ID.'PREVIEWhtml"';
				if ($row->arRes["PREVIEW_TEXT_TYPE"]=="html")
					$sHTML .= ' checked';
				$sHTML .= '><label for="'.$f_ID.'PREVIEWhtml">html</label><br>';
				$sHTML .= '<textarea rows="10" cols="50" name="FIELDS['.$f_ID.'][PREVIEW_TEXT]">'.htmlspecialcharsbx($row->arRes["PREVIEW_TEXT"]).'</textarea>';
				$row->AddEditField("PREVIEW_TEXT", $sHTML);
			}
			if (array_key_exists("DETAIL_TEXT", $arSelectedFieldsMap))
			{
				$sHTML = '<input type="radio" name="FIELDS['.$f_ID.'][DETAIL_TEXT_TYPE]" value="text" id="'.$f_ID.'DETAILtext"';
				if ($row->arRes["DETAIL_TEXT_TYPE"]!="html")
					$sHTML .= ' checked';
				$sHTML .= '><label for="'.$f_ID.'DETAILtext">text</label> /';
				$sHTML .= '<input type="radio" name="FIELDS['.$f_ID.'][DETAIL_TEXT_TYPE]" value="html" id="'.$f_ID.'DETAILhtml"';
				if ($row->arRes["DETAIL_TEXT_TYPE"]=="html")
					$sHTML .= ' checked';
				$sHTML .= '><label for="'.$f_ID.'DETAILhtml">html</label><br>';

				$sHTML .= '<textarea rows="10" cols="50" name="FIELDS['.$f_ID.'][DETAIL_TEXT]">'.htmlspecialcharsbx($row->arRes["DETAIL_TEXT"]).'</textarea>';
				$row->AddEditField("DETAIL_TEXT", $sHTML);
			}
			foreach ($row->arRes['props'] as $prop_id => $arEditHTML)
				$row->AddEditField($prop_id, '<table id="tb'.$arEditHTML['table_id'].'" border=0 cellpadding=0 cellspacing=0><tr><td nowrap>'.implode("</td></tr><tr><td nowrap>", $arEditHTML['html']).'</td></tr></table>');

			if (isset($arElementOps[$f_ID]["element_edit_price"]) && $USER->CanDoOperation('catalog_price'))
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
				if ($USER->CanDoOperation('catalog_purchas_info'))
				{
					if ($row->arRes["CATALOG_PURCHASING_PRICE"] != 0.0)
						$row->AddViewField("CATALOG_PURCHASING_PRICE", htmlspecialcharsEx($row->arRes["CATALOG_PURCHASING_PRICE"])." (".htmlspecialcharsEx($row->arRes["CATALOG_PURCHASING_CURRENCY"]).")");
				}
			}
			elseif ($USER->CanDoOperation('catalog_read'))
			{
				$row->AddInputField("CATALOG_QUANTITY", false);
				$row->AddCheckField("CATALOG_QUANTITY_TRACE", $arQuantityTrace, false);
				$row->AddInputField("CATALOG_WEIGHT", false);
				if ($USER->CanDoOperation('catalog_purchas_info'))
				{
					if ($row->arRes["CATALOG_PURCHASING_PRICE"] != 0.0)
						$row->AddViewField("CATALOG_PURCHASING_PRICE", htmlspecialcharsEx($row->arRes["CATALOG_PURCHASING_PRICE"])." (".htmlspecialcharsEx($row->arRes["CATALOG_PURCHASING_CURRENCY"]).")");
				}
			}
		}
		else
		{
			$row->AddCheckField("ACTIVE", false);
			$row->AddViewField("NAME", '<div class="iblock_menu_icon_elements"></div>'.htmlspecialcharsex($row->arRes["NAME"]));
			$row->AddInputField("SORT", false);
			$row->AddInputField("CODE", false);
			$row->AddInputField("EXTERNAL_ID", false);
			$row->AddViewField("TAGS", htmlspecialcharsex($row->arRes["TAGS"]));
			if ($arWFStatus)
			{
				$row->AddViewField("WF_STATUS_ID", htmlspecialcharsex($arWFStatus[$row->arRes['WF_STATUS_ID']]));
			}
			if ($boolSubCatalog)
			{
				$row->AddInputField("CATALOG_QUANTITY", false);
				$row->AddSelectField("CATALOG_QUANTITY_TRACE", $arQuantityTrace, false);
				$row->AddInputField("CATALOG_WEIGHT", false);
				if ($USER->CanDoOperation('catalog_purchas_info'))
				{
					if ($row->arRes["CATALOG_PURCHASING_PRICE"] != 0.0)
						$row->AddViewField("CATALOG_PURCHASING_PRICE", htmlspecialcharsEx($row->arRes["CATALOG_PURCHASING_PRICE"])." (".htmlspecialcharsEx($row->arRes["CATALOG_PURCHASING_CURRENCY"]).")");
				}
			}
		}

		$arActions = Array();

		if (
			$boolSubWorkFlow
		)
			{
				if (isset($arElementOps[$f_ID])
					&& isset($arElementOps[$f_ID]["element_edit_any_wf_status"])
			)
			{
				$STATUS_PERMISSION = 2;
			}
			else
			{
				$STATUS_PERMISSION = CIBlockElement::WF_GetStatusPermission($row->arRes["WF_STATUS_ID"]);
			}
			$intMinPerm = 2;

			$arUnLock = array(
				"ICON" => "unlock",
				"TEXT" => GetMessage("IBEL_A_UNLOCK"),
				"TITLE" => GetMessage("IBLOCK_UNLOCK_ALT"),
				"ACTION" => "if (confirm('".GetMessageJS("IBLOCK_UNLOCK_CONFIRM")."')) ".$lAdmin->ActionDoGroup($row->arRes['orig']['ID'], "unlock", $sThisSectionUrl)
			);

			if ($row->arRes['orig']['LOCK_STATUS'] == "red")
			{
				if (CWorkflow::IsAdmin())
					$arActions[] = $arUnLock;
			}
			else
			{
				if (
					isset($arElementOps[$f_ID])
					&& isset($arElementOps[$f_ID]["element_edit"])
					&& (2 <= $STATUS_PERMISSION)
				)
				{
					if ($row->arRes['orig']['LOCK_STATUS'] == "yellow")
					{
						$arActions[] = $arUnLock;
						$arActions[] = array("SEPARATOR"=>true);
					}

					$arActions[] = array(
						"ICON" => "edit",
						"TEXT" => GetMessage("IBEL_A_CHANGE"),
						"DEFAULT" => true,
						"ACTION"=>"(new BX.CAdminDialog({
							'content_url': '/bitrix/admin/iblock_subelement_edit.php?WF=Y&type=".CUtil::JSEscape(htmlspecialcharsbx($strSubIBlockType))."&IBLOCK_ID=".$intSubIBlockID."&lang=".LANGUAGE_ID."&PRODUCT_ID=".$ID."&ID=".$row->arRes['orig']['ID']."&TMP_ID=".urlencode($strSubTMP_ID).$sThisSectionUrl.'&'.bitrix_sessid_get()."',
							'content_post': '".(!(defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1) ? '&bxsku=Y' : '')."&bxpublic=Y',
							'draggable': true,
							'resizable': true,
							'buttons': [BX.CAdminDialog.btnSave, BX.CAdminDialog.btnCancel]
						})).Show();",
					);
				}

				if ($boolIBlockElementAdd
					&& (2 <= $STATUS_PERMISSION)
				)
				{
					$arActions[] = array(
						"ICON" => "copy",
						"TEXT" => GetMessage("IBEL_A_COPY_ELEMENT"),
						"ACTION"=>"(new BX.CAdminDialog({
							'content_url': '/bitrix/admin/iblock_subelement_edit.php?WF=Y&type=".CUtil::JSEscape(htmlspecialcharsbx($strSubIBlockType))."&IBLOCK_ID=".$intSubIBlockID."&lang=".LANGUAGE_ID."&PRODUCT_ID=".$ID."&ID=".$row->arRes['orig']['ID']."&TMP_ID=".urlencode($strSubTMP_ID)."&action=copy".$sThisSectionUrl.'&'.bitrix_sessid_get()."',
							'content_post': '".(!(defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1) ? '&bxsku=Y' : '')."&bxpublic=Y',
							'draggable': true,
							'resizable': true,
							'buttons': [BX.CAdminDialog.btnSave, BX.CAdminDialog.btnCancel]
						})).Show();",
					);
				}

/*					$arActions[] = array(
						"ICON" => "history",
						"TEXT" => GetMessage("IBEL_A_HIST"),
						"TITLE" => GetMessage("IBLOCK_HISTORY_ALT"),
						"ACTION" => $lAdmin->ActionRedirect('iblock_history_list.php?ELEMENT_ID='.$row->arRes['orig']['ID'].$sThisSectionUrl)
					); */

				if (
					isset($arElementOps[$f_ID])
					&& isset($arElementOps[$f_ID]["element_delete"])
					&& (2 <= $STATUS_PERMISSION)
				)
				{
					if (!isset($arElementOps[$f_ID]["element_edit_any_wf_status"]))
						$intMinPerm = CIBlockElement::WF_GetStatusPermission($row->arRes["WF_STATUS_ID"], $f_ID);
					if (2 <= $intMinPerm)
					{
						if (!empty($arActions))
							$arActions[] = array("SEPARATOR"=>true);
						$arActions[] = array(
							"ICON" => "delete",
							"TEXT" => GetMessage('MAIN_DELETE'),
							"TITLE" => GetMessage("IBLOCK_DELETE_ALT"),
							"ACTION" => "if (confirm('".GetMessageJS('IBLOCK_CONFIRM_DEL_MESSAGE')."')) ".$lAdmin->ActionDoGroup($row->arRes['orig']['ID'], "delete", $sThisSectionUrl)
						);

					}
				}
			}
		}
		elseif ($boolSubBizproc)
		{
			$bWritePermission = CIBlockDocument::CanUserOperateDocument(
				CBPCanUserOperateOperation::WriteDocument,
				$USER->GetID(),
				$f_ID,
				array(
					"IBlockId" => $intSubIBlockID,
					"AllUserGroups" => $row->arRes["CURRENT_USER_GROUPS"],
					"DocumentStates" => $arDocumentStates,
				)
			);

			$bStartWorkflowPermission = CIBlockDocument::CanUserOperateDocument(
				CBPCanUserOperateOperation::StartWorkflow,
				$USER->GetID(),
				$f_ID,
				array(
					"IBlockId" => $intSubIBlockID,
					"AllUserGroups" => $row->arRes["CURRENT_USER_GROUPS"],
					"DocumentStates" => $arDocumentStates,
				)
			);

/*		if (
			$bStartWorkflowPermission
			|| (
				isset($arElementOps[$f_ID])
				&& isset($arElementOps[$f_ID]["element_bizproc_start"])
			)
		)
		{
			$arActions[] = array(
				"ICON" => "",
				"TEXT" => GetMessage("IBEL_A_BP_RUN"),
				"ACTION" => $lAdmin->ActionRedirect('iblock_start_bizproc.php?document_id='.$f_ID.'&document_type=iblock_'.$IBLOCK_ID.'&back_url='.urlencode($APPLICATION->GetCurPageParam("", array("mode", "table_id"))).''),
			);
		} */

			if ($row->arRes['lockStatus'] == "red")
			{
				if (CBPDocument::IsAdmin())
				{
					$arActions[] = Array(
						"ICON" => "unlock",
						"TEXT" => GetMessage("IBEL_A_UNLOCK"),
						"TITLE" => GetMessage("IBEL_A_UNLOCK_ALT"),
						"ACTION" => "if (confirm('".GetMessageJS("IBEL_A_UNLOCK_CONFIRM")."')) ".$lAdmin->ActionDoGroup($f_ID, "unlock", $sThisSectionUrl),
					);
				}
			}
			elseif ($bWritePermission)
			{
				$arActions[] = array(
					"ICON" => "edit",
					"TEXT" => GetMessage("IBEL_A_CHANGE"),
					"DEFAULT" => true,
					"ACTION"=>"(new BX.CAdminDialog({
						'content_url': '/bitrix/admin/iblock_subelement_edit.php?WF=Y&type=".CUtil::JSEscape(htmlspecialcharsbx($strSubIBlockType))."&IBLOCK_ID=".$intSubIBlockID."&lang=".LANGUAGE_ID."&PRODUCT_ID=".$ID."&ID=".$row->arRes['orig']['ID']."&TMP_ID=".urlencode($strSubTMP_ID).$sThisSectionUrl.'&'.bitrix_sessid_get()."',
						'content_post': '".(!(defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1) ? '&bxsku=Y' : '')."&bxpublic=Y',
						'draggable': true,
						'resizable': true,
						'buttons': [BX.CAdminDialog.btnSave, BX.CAdminDialog.btnCancel]
					})).Show();",
				);

				$arActions[] = array(
					"ICON" => "copy",
					"TEXT" => GetMessage("IBEL_A_COPY_ELEMENT"),
					"ACTION"=>"(new BX.CAdminDialog({
						'content_url': '/bitrix/admin/iblock_subelement_edit.php?WF=Y&type=".CUtil::JSEscape(htmlspecialcharsbx($strSubIBlockType))."&IBLOCK_ID=".$intSubIBlockID."&lang=".LANGUAGE_ID."&PRODUCT_ID=".$ID."&ID=".$row->arRes['orig']['ID']."&TMP_ID=".urlencode($strSubTMP_ID)."&action=copy".$sThisSectionUrl.'&'.bitrix_sessid_get()."',
						'content_post': '".(!(defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1) ? '&bxsku=Y' : '')."&bxpublic=Y',
						'draggable': true,
						'resizable': true,
						'buttons': [BX.CAdminDialog.btnSave, BX.CAdminDialog.btnCancel]
					})).Show();",
				);

/*			$arActions[] = array(
				"ICON" => "history",
				"TEXT" => GetMessage("IBEL_A_HIST"),
				"TITLE" => GetMessage("IBEL_A_HISTORY_ALT"),
				"ACTION" => $lAdmin->ActionRedirect('iblock_bizproc_history.php?document_id='.$f_ID.'&back_url='.urlencode($APPLICATION->GetCurPageParam("", array())).''),
			); */

				$arActions[] = array("SEPARATOR"=>true);
				$arActions[] = array(
					"ICON" => "delete",
					"TEXT" => GetMessage('MAIN_DELETE'),
					"TITLE" => GetMessage("IBLOCK_DELETE_ALT"),
					"ACTION" => "if (confirm('".GetMessageJS('IBLOCK_CONFIRM_DEL_MESSAGE')."')) ".$lAdmin->ActionDoGroup($f_ID, "delete", $sThisSectionUrl),
				);
			}
		}
		else
		{
			if (
				isset($arElementOps[$f_ID])
				&& isset($arElementOps[$f_ID]["element_edit"])
			)
			{
				$arActions[] = array(
					"ICON" => "edit",
					"TEXT" => GetMessage("IBEL_A_CHANGE"),
					"DEFAULT" => true,
					"ACTION"=>"(new BX.CAdminDialog({
						'content_url': '/bitrix/admin/iblock_subelement_edit.php?WF=Y&type=".CUtil::JSEscape(htmlspecialcharsbx($strSubIBlockType))."&IBLOCK_ID=".$intSubIBlockID."&lang=".LANGUAGE_ID."&PRODUCT_ID=".$ID."&ID=".$row->arRes['orig']['ID']."&TMP_ID=".urlencode($strSubTMP_ID).$sThisSectionUrl.'&'.bitrix_sessid_get()."',
						'content_post': '".(!(defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1) ? '&bxsku=Y' : '')."&bxpublic=Y',
						'draggable': true,
						'resizable': true,
						'buttons': [BX.CAdminDialog.btnSave, BX.CAdminDialog.btnCancel]
					})).Show();",
				);
			}

			if ($boolIBlockElementAdd && isset($arElementOps[$f_ID])
				&& isset($arElementOps[$f_ID]["element_edit"]))
			{
				$arActions[] = array(
					"ICON" => "copy",
					"TEXT" => GetMessage("IBEL_A_COPY_ELEMENT"),
					"ACTION"=>"(new BX.CAdminDialog({
						'content_url': '/bitrix/admin/iblock_subelement_edit.php?WF=Y&type=".CUtil::JSEscape(htmlspecialcharsbx($strSubIBlockType))."&IBLOCK_ID=".$intSubIBlockID."&lang=".LANGUAGE_ID."&PRODUCT_ID=".$ID."&ID=".$row->arRes['orig']['ID']."&TMP_ID=".urlencode($strSubTMP_ID)."&action=copy".$sThisSectionUrl.'&'.bitrix_sessid_get()."',
						'content_post': '".(!(defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1) ? '&bxsku=Y' : '')."&bxpublic=Y',
						'draggable': true,
						'resizable': true,
						'buttons': [BX.CAdminDialog.btnSave, BX.CAdminDialog.btnCancel]
					})).Show();",
				);
			}

			if (
				isset($arElementOps[$f_ID])
				&& isset($arElementOps[$f_ID]["element_delete"])
			)
			{
				$arActions[] = array("SEPARATOR"=>true);
				$arActions[] = array(
					"ICON" => "delete",
					"TEXT" => GetMessage('MAIN_DELETE'),
					"TITLE" => GetMessage("IBLOCK_DELETE_ALT"),
					"ACTION" => "if (confirm('".GetMessageJS('IBLOCK_CONFIRM_DEL_MESSAGE')."')) ".$lAdmin->ActionDoGroup($row->arRes['orig']['ID'], "delete", $sThisSectionUrl)
				);
			}
		}

		if (!empty($arActions))
			$row->AddActions($arActions);
	}

	$lAdmin->AddFooter(
		array(
			array("title"=>GetMessage("MAIN_ADMIN_LIST_SELECTED"), "value"=>$rsData->SelectedRowsCount()),
			array("counter"=>true, "title"=>GetMessage("MAIN_ADMIN_LIST_CHECKED"), "value"=>"0"),
		)
	);

	$arGroupActions = array();
	foreach ($arElementOps as $id => $arOps)
	{
		if (isset($arOps["element_delete"]))
		{
			$arGroupActions["delete"] = GetMessage("MAIN_ADMIN_LIST_DELETE");
			break;
		}
	}
	foreach ($arElementOps as $id => $arOps)
	{
		if (isset($arOps["element_edit"]))
		{
			$arGroupActions["activate"] = GetMessage("MAIN_ADMIN_LIST_ACTIVATE");
			$arGroupActions["deactivate"] = GetMessage("MAIN_ADMIN_LIST_DEACTIVATE");
			break;
		}
	}

	$arParams = array('disable_action_sub_target' => true);
	if ($bWorkFlow)
	{
		$arGroupActions["unlock"] = GetMessage("IBEL_A_UNLOCK_ACTION");
		$arGroupActions["lock"] = GetMessage("IBEL_A_LOCK_ACTION");

		$statuses = '<div id="wf_status_id" style="display:none">'.SelectBox("wf_status_id", CWorkflowStatus::GetDropDownList("N", "desc")).'</div>';
		$arGroupActions["wf_status"] = GetMessage("IBEL_A_WF_STATUS_CHANGE");
		$arGroupActions["wf_status_chooser"] = array("type" => "html", "value" => $statuses);

		$arParams["select_onchange"] .= "BX('wf_status_id').style.display = (this.value == 'wf_status'? 'block':'none');";
	}
	elseif ($bBizproc)
	{
		$arGroupActions["unlock"] = GetMessage("IBEL_A_UNLOCK_ACTION");
	}

	$lAdmin->AddGroupActionTable($arGroupActions, $arParams);

?><script type="text/javascript">
function CheckProductName(id)
{
	if (!id)
		return false;
	var obj = BX(id);
	if (!obj)
		return false;
	var obFormElement = BX.findParent(obj,{tag: 'form'});
	if (!obFormElement)
		return false;
	if ((obFormElement.elements['NAME']) && (0 < obFormElement.elements['NAME'].value.length))
		return obFormElement.elements['NAME'].value;
	else
		return false;

}
function ShowNewOffer(id)
{
	var mxProductName = CheckProductName(id);
	if (!mxProductName)
		alert('<? echo CUtil::JSEscape(GetMessage('IB_SE_L_ENTER_PRODUCT_NAME')); ?>');
	else
	{
		var PostParams = {};
		PostParams.bxpublic = 'Y';
		<? if (!(defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1))
		{
			?>PostParams.bxsku = 'Y';<?
		}
		?>
		PostParams.PRODUCT_NAME = mxProductName;
		PostParams.sessid = BX.bitrix_sessid();
		(new BX.CAdminDialog({
			'content_url': '/bitrix/admin/iblock_subelement_edit.php?WF=Y&type=<? echo CUtil::JSEscape(htmlspecialcharsbx($strSubIBlockType)); ?>&IBLOCK_ID=<? echo $intSubIBlockID; ?>&lang=<? echo LANGUAGE_ID; ?>&PRODUCT_ID=<? echo $intSubPropValue; ?>&ID=0&TMP_ID=<? echo urlencode($strSubTMP_ID).$sThisSectionUrl; ?>',
			'content_post': PostParams,
			'draggable': true,
			'resizable': true,
			'buttons': [BX.CAdminDialog.btnSave, BX.CAdminDialog.btnCancel]
		})).Show();
	}
}
</script><?

	if (true == B_ADMIN_SUBELEMENTS_LIST)
	{
		echo $CAdminCalendar_ShowScript;
	}

	//We need javascript not in excel mode
	if (($_REQUEST["mode"]=='list' || $_REQUEST["mode"]=='frame') && $boolSubCatalog && $boolSubCurrency)
	{
		?><script type="text/javascript">
		top.arSubCatalogShowedGroups = new Array();
		top.arSubExtra = new Array();
		top.arSubCatalogGroups = new Array();
		top.SubBaseIndex = "";
		<?
		if (is_array($arCatGroup) && !empty($arCatGroup))
		{
			$i = 0;
			$j = 0;
			foreach ($arCatGroup as &$CatalogGroups)
			{
				if (in_array("CATALOG_GROUP_".$CatalogGroups["ID"], $arSelectedFields))
				{
					echo "top.arSubCatalogShowedGroups[".$i."]=".$CatalogGroups["ID"].";\n";
					$i++;
				}
				if ($CatalogGroups["BASE"] != "Y")
				{
					echo "top.arSubCatalogGroups[".$j."]=".$CatalogGroups["ID"].";\n";
					$j++;
				}
				else
				{
					echo "top.SubBaseIndex=".$CatalogGroups["ID"].";\n";
				}
			}
			unset($CatalogGroups);
		}
		if (is_array($arCatExtra) && !empty($arCatExtra))
		{
			$i = 0;
			foreach ($arCatExtra as &$CatExtra)
			{
				echo "top.arSubExtra[".$CatExtra["ID"]."]=".$CatExtra["PERCENTAGE"].";\n";
				$i++;
			}
			unset($CatExtra);
		}
		?>
		top.SubChangeBasePrice = function(id)
		{
			for (var i = 0, cnt = top.arSubCatalogShowedGroups.length; i < cnt; i++)
			{
				var pr = top.document.getElementById("CATALOG_PRICE["+id+"]"+"["+top.arSubCatalogShowedGroups[i]+"]");
				if (pr.disabled)
				{
					var price = top.document.getElementById("CATALOG_PRICE["+id+"]"+"["+top.SubBaseIndex+"]").value;
					if (price > 0)
					{
						var extraId = top.document.getElementById("CATALOG_EXTRA["+id+"]"+"["+top.arSubCatalogShowedGroups[i]+"]").value;
						var esum = parseFloat(price) * (1 + top.arSubExtra[extraId] / 100);
						var eps = 1.00/Math.pow(10, 6);
						esum = Math.round((esum+eps)*100)/100;
					}
					else
						var esum = "";

					pr.value = esum;
				}
			}
		}

		top.SubChangeBaseCurrency = function(id)
		{
			var currency = top.document.getElementById("CATALOG_CURRENCY["+id+"]["+top.SubBaseIndex+"]");
			for (var i = 0, cnt = top.arSubCatalogShowedGroups.length; i < cnt; i++)
			{
				var pr = top.document.getElementById("CATALOG_CURRENCY["+id+"]["+top.arSubCatalogShowedGroups[i]+"]");
				if (pr.disabled)
				{
					pr.selectedIndex = currency.selectedIndex;
				}
			}
		}
	</script>
	<?
	}

	$aContext = array();
	if ($boolIBlockElementAdd)
	{
		$aContext[] = array(
			"ICON"=>"btn_sub_new",
			"TEXT"=>htmlspecialcharsex('' != trim($arSubIBlock["ELEMENT_ADD"]) ? $arSubIBlock["ELEMENT_ADD"] : GetMessage('IB_SE_L_ADD_NEW_ELEMENT')),
			"LINK"=>"javascript:ShowNewOffer('btn_sub_new')",
			"TITLE"=>GetMessage("IB_SE_L_ADD_NEW_ELEMENT_DESCR")
		);
	}
	$aContext[] = array(
		"ICON"=>"btn_sub_refresh",
		"TEXT"=>htmlspecialcharsex(GetMessage('IB_SE_L_REFRESH_ELEMENTS')),
		"LINK" => "javascript:".$lAdmin->ActionAjaxReload($lAdmin->GetListUrl(true)),
		"TITLE"=>GetMessage("IB_SE_L_REFRESH_ELEMENTS_DESCR"),
	);

	$lAdmin->AddAdminContextMenu($aContext);

	$lAdmin->CheckListMode();

	$lAdmin->DisplayList(B_ADMIN_SUBELEMENTS_LIST);
}
else
{
	ShowMessage(GetMessage('IB_SE_L_SHOW_PRICES_AFTER_COPY'));
}
?>