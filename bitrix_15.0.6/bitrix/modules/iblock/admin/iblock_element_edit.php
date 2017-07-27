<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
CModule::IncludeModule("iblock");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/prolog.php");

/** @global CUser $USER */
global $USER;
/** @global CMain $APPLICATION */
global $APPLICATION;

$io = CBXVirtualIo::GetInstance();

/*Change any language identifiers carefully*/
/*because of user customized forms!*/
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/iblock/admin/iblock_element_edit_compat.php");

IncludeModuleLangFile(__FILE__);
$IBLOCK_ID = 0;
if (isset($_REQUEST['IBLOCK_ID']))
	$IBLOCK_ID = (int)$_REQUEST["IBLOCK_ID"]; //information block ID
define("MODULE_ID", "iblock");
define("ENTITY", "CIBlockDocument");
define("DOCUMENT_TYPE", "iblock_".$IBLOCK_ID);

/* autocomplete */
$strLookup = '';
if (isset($_REQUEST['lookup']))
	$strLookup = preg_replace("/[^a-zA-Z0-9_:]/", "", $_REQUEST["lookup"]);
if ('' != $strLookup)
{
	define('BT_UT_AUTOCOMPLETE', 1);
}
$bAutocomplete = defined('BT_UT_AUTOCOMPLETE') && (BT_UT_AUTOCOMPLETE == 1);

/* property ajax */
$bPropertyAjax = (isset($_REQUEST["ajax_action"]) && $_REQUEST["ajax_action"] === "section_property");

$strWarning = '';
$bVarsFromForm = false;

$ID = 0;
if (isset($_REQUEST['ID']))
	$ID = (int)$_REQUEST['ID']; //ID of the persistent record

/* copy element */
$bCopy = false;
$copyID = 0;
if (!$bAutocomplete)
{
	$bCopy = (isset($_REQUEST['action']) && $_REQUEST["action"] == "copy");
}
$copyID = (int)(isset($_REQUEST['copyID']) ? $_REQUEST['copyID'] : 0);

if($ID<=0 && intval($PID)>0)
	$ID = intval($PID);

$PREV_ID = intval($PREV_ID);

$WF_ID = $ID; //This is ID of the current copy

$arShowTabs = array(
	'edit_rights' => false,
	'workflow' => false,
	'bizproc' => false,
	'sections' => false,
	'catalog' => false,
	'sku' => false,
	'product_set' => false,
	'product_group' => false
);

$bWorkflow = CModule::IncludeModule("workflow") && (CIBlock::GetArrayByID($IBLOCK_ID, "WORKFLOW") != "N");
$bBizproc = CModule::IncludeModule("bizproc") && (CIBlock::GetArrayByID($IBLOCK_ID, "BIZPROC") != "N");

$bCatalog = CModule::IncludeModule('catalog');
$arMainCatalog = false;
$arCatalogTabs = false;
$bOffers = false;
$boolCatalogRead = false;
$boolCatalogPrice = false;
if ($bCatalog)
{
	$boolCatalogRead = $USER->CanDoOperation('catalog_read');
	$boolCatalogPrice = $USER->CanDoOperation('catalog_price');
	$arMainCatalog = CCatalogSKU::GetInfoByIBlock($IBLOCK_ID);
	if (!empty($arMainCatalog))
	{
		if (CCatalogSKU::TYPE_PRODUCT == $arMainCatalog['CATALOG_TYPE'] || CCatalogSKU::TYPE_FULL == $arMainCatalog['CATALOG_TYPE'])
			$bOffers = true;
		CCatalogAdminTools::setProductFormParams();

		$arCatalogTabs = CCatalogAdminTools::getShowTabs($IBLOCK_ID, ($copyID > 0 && $ID == 0 ? $copyID : $ID), $arMainCatalog);
		if (!empty($arCatalogTabs))
		{
			$arShowTabs['catalog'] = $arCatalogTabs[CCatalogAdminTools::TAB_CATALOG];
			$arShowTabs['sku'] = $arCatalogTabs[CCatalogAdminTools::TAB_SKU];
			$arShowTabs['product_set'] = $arCatalogTabs[CCatalogAdminTools::TAB_SET];
			$arShowTabs['product_group'] = $arCatalogTabs[CCatalogAdminTools::TAB_GROUP];
		}
	}
}
$str_TMP_ID = 0;
if ($bOffers && (0 == $ID || $bCopy))
{
	if ('GET' == $_SERVER['REQUEST_METHOD'] && 0 >= $ID)
	{
		$str_TMP_ID = CIBlockOffersTmp::Add($IBLOCK_ID, $arMainCatalog['IBLOCK_ID']);
	}
	else
	{
		if (isset($_REQUEST['TMP_ID']))
			$str_TMP_ID = (int)$_REQUEST['TMP_ID'];
	}
}
$TMP_ID = $str_TMP_ID;

if(($ID <= 0 || $bCopy) && $bWorkflow)
	$WF = "Y";
elseif(!$bWorkflow)
	$WF = "N";
else
	$WF = ($_REQUEST["WF"] === "Y")? "Y": "N";

$historyId = intval($history_id);
if ($historyId > 0 && $bBizproc)
	$view = "Y";
else
	$historyId = 0;

$APPLICATION->AddHeadScript('/bitrix/js/iblock/iblock_edit.js');

$error = false;

$view = ($view=="Y") ? "Y" : "N"; //view mode

$return_url = (isset($return_url) ? (string)$return_url : '');

if ($bAutocomplete)
{
	$return_url = '';
}
else
{
	if ($return_url != '' && strtolower(substr($return_url, strlen($APPLICATION->GetCurPage())))==strtolower($APPLICATION->GetCurPage()))
		$return_url = '';
	if ($return_url == '')
	{
		if ($from=="iblock_section_admin")
			$return_url = CIBlock::GetAdminSectionListLink($IBLOCK_ID, array('find_section_section'=>intval($find_section_section)));
	}
}

do{ //one iteration loop

	$errorTriger = false;
	if ($historyId > 0)
	{
		$arErrorsTmp = array();
		$arResult = CBPDocument::GetDocumentFromHistory($historyId, $arErrorsTmp);

		if (!empty($arErrorsTmp))
		{
			foreach ($arErrorsTmp as $e)
			{
				$error = new _CIBlockError(1, $e["code"], $e["message"]);
				break;
			}
		}

		$canWrite = CBPDocument::CanUserOperateDocument(
			CBPCanUserOperateOperation::WriteDocument,
			$USER->GetID(),
			$arResult["DOCUMENT_ID"],
			array("UserGroups" => $USER->GetUserGroupArray())
		);
		if (!$canWrite)
		{
			$error = new _CIBlockError(1, "ACCESS_DENIED", GetMessage("IBLOCK_ACCESS_DENIED_STATUS"));
			break;
		}

		$type = $arResult["DOCUMENT"]["FIELDS"]["IBLOCK_TYPE_ID"];
		$IBLOCK_ID = $arResult["DOCUMENT"]["FIELDS"]["IBLOCK_ID"];
	}

	$arIBTYPE = CIBlockType::GetByIDLang($type, LANGUAGE_ID);
	if($arIBTYPE===false)
	{
		$error = new _CIBlockError(1, "BAD_IBLOCK_TYPE", GetMessage("IBLOCK_BAD_BLOCK_TYPE_ID"));
		break;
	}

	$MENU_SECTION_ID = intval($IBLOCK_SECTION_ID)? intval($IBLOCK_SECTION_ID): intval($find_section_section);

	$bBadBlock = true;
	$arIBlock = CIBlock::GetArrayByID($IBLOCK_ID);
	if($arIBlock)
	{
		if(($ID > 0 && !$bCopy) && !CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $ID, "iblock_admin_display"))
			$bBadBlock = true;
		elseif(($ID <= 0 || $bCopy) && !CIBlockSectionRights::UserHasRightTo($IBLOCK_ID, $MENU_SECTION_ID, "iblock_admin_display"))
			$bBadBlock = true;
		elseif(CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $ID, "element_edit_any_wf_status"))
			$bBadBlock = false;
		elseif(!$bWorkflow && CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $ID, "element_edit"))
			$bBadBlock = false;
		elseif($bWorkflow && ($WF=="Y" || $view=="Y"))
			$bBadBlock = false;
		elseif($bBizproc)
			$bBadBlock = false;
		elseif(
			(($ID <= 0) || $bCopy)
			&& CIBlockSectionRights::UserHasRightTo($IBLOCK_ID, $MENU_SECTION_ID, "section_element_bind")
		)
			$bBadBlock = false;
		elseif(($ID > 0 && !$bCopy) && CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $ID, "iblock_admin_display"))
			$bBadBlock = false;
	}

	if($bBadBlock)
	{
		$error = new _CIBlockError(1, "BAD_IBLOCK", GetMessage("IBLOCK_BAD_IBLOCK"));
		$APPLICATION->SetTitle($arIBTYPE["ELEMENT_NAME"].": ".GetMessage("IBLOCK_EDIT_TITLE"));
		break;
	}

	$bEditRights = $arIBlock["RIGHTS_MODE"] === "E" && (
		CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $ID, "element_rights_edit")
		|| (($ID <= 0 || $bCopy) && CIBlockRights::UserHasRightTo($IBLOCK_ID, $IBLOCK_ID, "element_rights_edit"))
	);

	$arShowTabs['sections'] = ('Y' == $arIBTYPE["SECTIONS"]);
	$arShowTabs['workflow'] = $bWorkflow;
	$arShowTabs['bizproc'] = $bBizproc && (0 >= $historyId);
	$arShowTabs['edit_rights'] = $bEditRights;
	if ('Y' == $view || $bPropertyAjax)
	{
		$arShowTabs['sku'] = false;
		$arShowTabs['product_set'] = false;
		$arShowTabs['product_group'] = false;
	}

	$aTabs = array(
		array(
			"DIV" => "edit1",
			"TAB" => $arIBlock["ELEMENT_NAME"],
			"ICON" => "iblock_element",
			"TITLE" => htmlspecialcharsex($arIBlock["ELEMENT_NAME"])
		),
		array(
			"DIV" => "edit5",
			"TAB" => GetMessage("IBEL_E_TAB_PREV"),
			"ICON" => "iblock_element",
			"TITLE" => GetMessage("IBEL_E_TAB_PREV_TITLE")
		),
		array(
			"DIV" => "edit6",
			"TAB" => GetMessage("IBEL_E_TAB_DET"),
			"ICON" => "iblock_element",
			"TITLE" => GetMessage("IBEL_E_TAB_DET_TITLE")
		),
		array(
			"DIV" => "edit14",
			"TAB" => GetMessage("IBEL_E_TAB14"),
			"ICON" => "iblock_iprops",
			"TITLE" => GetMessage("IBEL_E_TAB14_TITLE"),
			"ONSELECT" => "InheritedPropertiesTemplates.onTabSelect();",
		),
	);
	if($arShowTabs['sections'])
		$aTabs[] = array(
			"DIV" => "edit2",
			"TAB" => $arIBlock["SECTIONS_NAME"],
			"ICON" => "iblock_element_section",
			"TITLE" => htmlspecialcharsex($arIBlock["SECTIONS_NAME"]),
		);
	if ($arShowTabs['catalog'])
		$aTabs[] = array(
			"DIV" => "edit10",
			"TAB" => GetMessage("IBLOCK_TCATALOG"),
			"ICON" => "iblock_element",
			"TITLE" => GetMessage("IBLOCK_TCATALOG"),
			"required" => true,
		);
	if($arShowTabs['sku'])
		$aTabs[] = array(
			"DIV" => "edit8",
			"TAB" => GetMessage("IBLOCK_EL_TAB_OFFERS"),
			"ICON" => "iblock_element",
			"TITLE" => GetMessage("IBLOCK_EL_TAB_OFFERS_TITLE"),
			"required" => true,
		);
	if ($arShowTabs['product_set'])
		$aTabs[] = array(
			"DIV" => "edit11",
			"TAB" => GetMessage("IBLOCK_EL_TAB_PRODUCT_SET"),
			"ICON" => "iblock_element",
			"TITLE" => GetMessage("IBLOCK_EL_TAB_PRODUCT_SET_TITLE"),
			"required" => true,
		);
	if ($arShowTabs['product_group'])
		$aTabs[] = array(
			"DIV" => "edit12",
			"TAB" => GetMessage("IBLOCK_EL_TAB_PRODUCT_GROUP"),
			"ICON" => "iblock_element",
			"TITLE" => GetMessage("IBLOCK_EL_TAB_PRODUCT_GROUP_TITLE"),
			"required" => true,
		);
	if($arShowTabs['workflow'])
		$aTabs[] = array(
			"DIV" => "edit4",
			"TAB" => GetMessage("IBLOCK_EL_TAB_WF"),
			"ICON" => "iblock_element_wf",
			"TITLE" => GetMessage("IBLOCK_EL_TAB_WF_TITLE")
		);
	if($arShowTabs['bizproc'])
		$aTabs[] = array(
			"DIV" => "edit7",
			"TAB" => GetMessage("IBEL_E_TAB_BIZPROC"),
			"ICON" => "iblock_element_bizproc",
			"TITLE" => GetMessage("IBEL_E_TAB_BIZPROC")
		);
	if($arShowTabs['edit_rights'])
		$aTabs[] = array(
			"DIV" => "edit9",
			"TAB" => GetMessage("IBEL_E_TAB_RIGHTS"),
			"ICON" => "iblock_element_rights",
			"TITLE" => GetMessage("IBEL_E_TAB_RIGHTS_TITLE")
		);

	$bCustomForm = (strlen($arIBlock["EDIT_FILE_AFTER"])>0 && is_file($_SERVER["DOCUMENT_ROOT"].$arIBlock["EDIT_FILE_AFTER"]))
		|| (strlen($arIBTYPE["EDIT_FILE_AFTER"])>0 && is_file($_SERVER["DOCUMENT_ROOT"].$arIBTYPE["EDIT_FILE_AFTER"]));

	if($ID>0)
	{
		$rsElement = CIBlockElement::GetList(array(), array("ID" => $ID, "IBLOCK_ID" => $IBLOCK_ID, "SHOW_HISTORY"=>"Y"), false, false, array("ID", "CREATED_BY"));
		if(!($arElement = $rsElement->Fetch()))
		{
			$error = new _CIBlockError(1, "BAD_ELEMENT", GetMessage("IBLOCK_BAD_ELEMENT"));
			$APPLICATION->SetTitle($arIBTYPE["ELEMENT_NAME"].": ".GetMessage("IBLOCK_EDIT_TITLE"));
			$errorTriger = true;
		}
	}

	if (!$errorTriger)
	{
		// workflow mode
		$isLocked = false;
		if($ID>0 && $WF=="Y")
		{
			// get ID of the last record in workflow
			$WF_ID = CIBlockElement::WF_GetLast($ID);

			// check for edit permissions
			$STATUS_ID = CIBlockElement::WF_GetCurrentStatus($WF_ID, $STATUS_TITLE);
			$STATUS_PERMISSION = CIBlockElement::WF_GetStatusPermission($STATUS_ID);

			if($STATUS_ID>1 && $STATUS_PERMISSION<2)
			{
				$error = new _CIBlockError(1, "ACCESS_DENIED", GetMessage("IBLOCK_ACCESS_DENIED_STATUS"));
				$errorTriger = true;
			}
			elseif($STATUS_ID==1)
			{
				$WF_ID = $ID;
				$STATUS_ID = CIBlockElement::WF_GetCurrentStatus($WF_ID, $STATUS_TITLE);
				$STATUS_PERMISSION = CIBlockElement::WF_GetStatusPermission($STATUS_ID);
			}

			if (!$errorTriger)
			{
				// check if document is locked
				$isLocked = CIBlockElement::WF_IsLocked($ID, $locked_by, $date_lock);
				if($isLocked)
				{
					if($locked_by > 0)
					{
						$rsUser = CUser::GetList(($by="ID"), ($order="ASC"), array("ID_EQUAL_EXACT" => $locked_by));
						if($arUser = $rsUser->GetNext())
							$locked_by = rtrim("[".$arUser["ID"]."] (".$arUser["LOGIN"].") ".$arUser["NAME"]." ".$arUser["LAST_NAME"]);
					}
					$error = new _CIBlockError(2, "BLOCKED", GetMessage("IBLOCK_DOCUMENT_LOCKED", array("#ID#"=>$locked_by, "#DATE#"=>$date_lock)));
					$errorTriger = true;
				}
			}
		}
		elseif ($bBizproc)
		{
			$arDocumentStates = CBPDocument::GetDocumentStates(
				array(MODULE_ID, ENTITY, DOCUMENT_TYPE),
				($ID > 0) ? array(MODULE_ID, ENTITY, $ID) : null,
				"Y"
			);

			$arCurrentUserGroups = $USER->GetUserGroupArray();
			if ($ID > 0 && is_array($arElement))
			{
				if ($USER->GetID() == $arElement["CREATED_BY"])
					$arCurrentUserGroups[] = "Author";
			}
			else
			{
				$arCurrentUserGroups[] = "Author";
			}

			if ($ID > 0)
			{
				$canWrite = CBPDocument::CanUserOperateDocument(
					CBPCanUserOperateOperation::WriteDocument,
					$USER->GetID(),
					array(MODULE_ID, ENTITY, $ID),
					array("AllUserGroups" => $arCurrentUserGroups, "DocumentStates" => $arDocumentStates)
				);
				$canRead = CBPDocument::CanUserOperateDocument(
					CBPCanUserOperateOperation::ReadDocument,
					$USER->GetID(),
					array(MODULE_ID, ENTITY, $ID),
					array("AllUserGroups" => $arCurrentUserGroups, "DocumentStates" => $arDocumentStates)
				);
			}
			else
			{
				$canWrite = CBPDocument::CanUserOperateDocumentType(
					CBPCanUserOperateOperation::WriteDocument,
					$USER->GetID(),
					array(MODULE_ID, ENTITY, DOCUMENT_TYPE),
					array("AllUserGroups" => $arCurrentUserGroups, "DocumentStates" => $arDocumentStates)
				);
				$canRead = false;
			}

			if (!$canWrite && !$canRead)
			{
				$error = new _CIBlockError(1, "ACCESS_DENIED", GetMessage("IBLOCK_ACCESS_DENIED_STATUS"));
				$errorTriger = true;
			}
		}
	}

	$denyAutosave = false;
	if ($bWorkflow)
	{
		$denyAutosave = CIBlockElement::WF_IsLocked($ID, $locked_by1, $date_lock1);
	}
	else
	{
		$denyAutosave = ($view=="Y")
			|| (
				(($ID <= 0) || $bCopy)
				&& !CIBlockSectionRights::UserHasRightTo($IBLOCK_ID, $MENU_SECTION_ID, "section_element_bind")
			)
			|| (
				(($ID > 0) && !$bCopy)
				&& !CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $ID, "element_edit")
			)
			|| (
				$bBizproc
				&& !$canWrite
			);
	}

	$tabControl = new CAdminForm($bCustomForm? "tabControl": "form_element_".$IBLOCK_ID, $aTabs, true, $denyAutosave);
	$customTabber = new CAdminTabEngine("OnAdminIBlockElementEdit", array("ID" => $ID, "IBLOCK"=>$arIBlock, "IBLOCK_TYPE"=>$arIBTYPE));
	$tabControl->AddTabs($customTabber);

	if($bCustomForm)
	{
		$tabControl->SetShowSettings(false);
		if ($bCatalog && !empty($arMainCatalog))
		{
			$arMainCatalog['OFFERS_PROPERTY_ID'] = 0;
			$arMainCatalog['OFFERS_IBLOCK_ID'] = 0;
			if ($arMainCatalog['CATALOG_TYPE'] == CCatalogSKU::TYPE_FULL || $arMainCatalog['CATALOG_TYPE'] == CCatalogSKU::TYPE_PRODUCT)
			{
				$arMainCatalog['OFFERS_PROPERTY_ID'] = $arMainCatalog['SKU_PROPERTY_ID'];
				$arMainCatalog['OFFERS_IBLOCK_ID'] = $arMainCatalog['IBLOCK_ID'];
			}
		}
	}

	if (!$errorTriger)
	{
		//Find out files properties
		$arFileProps = array();
		$properties = CIBlockProperty::GetList(array(), array("IBLOCK_ID"=>$IBLOCK_ID, "PROPERTY_TYPE" => "F", "ACTIVE"=>"Y"));
		while($prop_fields = $properties->Fetch())
			$arFileProps[] = $prop_fields['ID'];

		//Assembly properties values from $_POST and $_FILES
		$PROP = array();
		if (isset($_POST['PROP']))
			$PROP = $_POST['PROP'];

		//Recover some user defined properties
		if(is_array($PROP))
		{
			foreach($PROP as $k1 => $val1)
			{
				if(is_array($val1))
				{
					foreach($val1 as $k2 => $val2)
					{
						$text_name = preg_replace("/([^a-z0-9])/is", "_", "PROP[".$k1."][".$k2."][VALUE][TEXT]");
						if(array_key_exists($text_name, $_POST))
						{
							$type_name = preg_replace("/([^a-z0-9])/is", "_", "PROP[".$k1."][".$k2."][VALUE][TYPE]");
							$PROP[$k1][$k2]["VALUE"] = array(
								"TEXT" => $_POST[$text_name],
								"TYPE" => $_POST[$type_name],
							);
						}
					}
				}
			}

			foreach($PROP as $k1 => $val1)
			{
				if(is_array($val1))
				{
					foreach($val1 as $k2 => $val2)
					{
						if(!is_array($val2))
							$PROP[$k1][$k2] = array("VALUE" => $val2);
					}
				}
			}
		}

		//transpose files array
		// [property id] [value id] = file array (name, type, tmp_name, error, size)
		$files = $_FILES["PROP"];
		if(is_array($files))
		{
			if(!is_array($PROP))
				$PROP = array();
			CAllFile::ConvertFilesToPost($_FILES["PROP"], $PROP);
		}

		foreach($arFileProps as $k1)
		{
			if (isset($PROP_del[$k1]) && is_array($PROP_del[$k1]))
			{
				foreach ($PROP_del[$k1] as $prop_value_id => $tmp)
				{
					if (!array_key_exists($prop_value_id, $PROP[$k1]))
						$PROP[$k1][$prop_value_id] = null;
				}
			}

			if (isset($PROP[$k1]) && is_array($PROP[$k1]))
			{
				foreach ($PROP[$k1] as $prop_value_id => $prop_value)
				{
					$PROP[$k1][$prop_value_id] = CIBlock::makeFilePropArray(
						$PROP[$k1][$prop_value_id],
						$PROP_del[$k1][$prop_value_id] === "Y",
						isset($_POST["DESCRIPTION_PROP"][$k1][$prop_value_id])? $_POST["DESCRIPTION_PROP"][$k1][$prop_value_id]: $_POST["PROP_descr"][$k1][$prop_value_id]
					);
				}
			}
		}

		$DESCRIPTION_PROP = $_POST["DESCRIPTION_PROP"];
		if(is_array($DESCRIPTION_PROP))
		{
			foreach($DESCRIPTION_PROP as $k1=>$val1)
			{
				foreach($val1 as $k2=>$val2)
				{
					if(is_set($PROP[$k1], $k2) && is_array($PROP[$k1][$k2]) && is_set($PROP[$k1][$k2], "DESCRIPTION"))
						$PROP[$k1][$k2]["DESCRIPTION"] = $val2;
					else
						$PROP[$k1][$k2] = Array("VALUE"=>$PROP[$k1][$k2], "DESCRIPTION"=>$val2);
				}
			}
		}

		function _prop_value_id_cmp($a, $b)
		{
			if(substr($a, 0, 1)==="n")
			{
				$a = intval(substr($a, 1));
				if(substr($b, 0, 1)==="n")
				{
					$b = intval(substr($b, 1));
					if($a < $b)
						return -1;
					elseif($a > $b)
						return 1;
					else
						return 0;
				}
				else
				{
					return 1;
				}
			}
			else
			{
				if(substr($b, 0, 1)==="n")
				{
					return -1;
				}
				else
				{
					if(preg_match("/^(\\d+):(\\d+)$/", $a, $a_match))
						$a = intval($a_match[2]);
					else
						$a = intval($a);

					if(preg_match("/^(\\d+):(\\d+)$/", $b, $b_match))
						$b = intval($b_match[2]);
					else
						$b = intval($b);

					if($a < $b)
						return -1;
					elseif($a > $b)
						return 1;
					else
						return 0;
				}
			}
		}

		//Now reorder property values
		if(is_array($PROP) && !empty($arFileProps))
		{
			foreach($arFileProps as $id)
			{
				if(is_array($PROP[$id]))
					uksort($PROP[$id], "_prop_value_id_cmp");
			}
		}

		if(strlen($arIBlock["EDIT_FILE_BEFORE"])>0 && is_file($_SERVER["DOCUMENT_ROOT"].$arIBlock["EDIT_FILE_BEFORE"]))
		{
			include($_SERVER["DOCUMENT_ROOT"].$arIBlock["EDIT_FILE_BEFORE"]);
		}
		elseif(strlen($arIBTYPE["EDIT_FILE_BEFORE"])>0 && is_file($_SERVER["DOCUMENT_ROOT"].$arIBTYPE["EDIT_FILE_BEFORE"]))
		{
			include($_SERVER["DOCUMENT_ROOT"].$arIBTYPE["EDIT_FILE_BEFORE"]);
		}

		if (
			$bBizproc
			&& $canWrite
			&& $historyId <= 0
			&& $ID > 0
			&& $REQUEST_METHOD=="GET"
			&& isset($_REQUEST["stop_bizproc"]) && strlen($_REQUEST["stop_bizproc"]) > 0
			&& check_bitrix_sessid()
		)
		{
			CBPDocument::TerminateWorkflow(
				$_REQUEST["stop_bizproc"],
				array(MODULE_ID, ENTITY, $ID),
				$ar
			);

			if (!empty($ar))
			{
				$str = "";
				foreach ($ar as $a)
					$str .= $a["message"];
				$error = new _CIBlockError(2, "STOP_BP_ERROR", $str);
			}
			else
			{
				LocalRedirect($APPLICATION->GetCurPageParam("", array("stop_bizproc", "sessid")));
			}
		}

		if(
			$historyId <= 0
			&& 'GET' == $_SERVER['REQUEST_METHOD']
			&& $bCatalog
			&& 0 < $ID
			&& check_bitrix_sessid()
		)
		{
			if (CCatalogAdminTools::changeTabs($IBLOCK_ID, $ID, $arMainCatalog))
			{
				$arUrlParams = array(
					"find_section_section" => intval($find_section_section)
				);
				if ('Y' == $WF)
					$arUrlParams['WF'] = 'Y';
				if ('' != $return_url)
					$arUrlParams['return_url'] = $return_url;
				if ($bAutocomplete)
					$arUrlParams['lookup'] = $strLookup;
				CCatalogAdminTools::addTabParams($arUrlParams);
				LocalRedirect("/bitrix/admin/".CIBlock::GetAdminElementEditLink($IBLOCK_ID, $ID, $arUrlParams, "&".$tabControl->ActiveTabParam()));
			}
		}

		if(
			$historyId <= 0
			&& 'POST' == $_SERVER['REQUEST_METHOD']
			&& strlen($Update) > 0
			&& $view != "Y"
			&& (!$error)
			&& empty($dontsave)
		)
		{
			$DB->StartTransaction();

			if(isset($_POST["IBLOCK_SECTION"]))
			{
				if(is_array($_POST["IBLOCK_SECTION"]))
				{
					foreach($_POST["IBLOCK_SECTION"] as $i => $parent_section_id)
					{
						if(!CIBlockSectionRights::UserHasRightTo($IBLOCK_ID, $parent_section_id, "section_element_bind"))
							unset($_POST["IBLOCK_SECTION"][$i]);
					}

					if(empty($_POST["IBLOCK_SECTION"]))
						unset($_POST["IBLOCK_SECTION"]);
				}
				else
				{
					if(!CIBlockSectionRights::UserHasRightTo($IBLOCK_ID, $_POST["IBLOCK_SECTION"], "section_element_bind"))
						unset($_POST["IBLOCK_SECTION"]);
				}
			}

			if(!(check_bitrix_sessid() || $_SESSION['IBLOCK_CUSTOM_FORM']===true))
			{
				$strWarning .= GetMessage("IBLOCK_WRONG_SESSION")."<br>";
				$error = new _CIBlockError(2, "BAD_SAVE", $strWarning);
				$bVarsFromForm = true;
			}
			elseif($WF=="Y" && $bWorkflow && intval($_POST["WF_STATUS_ID"])<=0)
				$strWarning .= GetMessage("IBLOCK_WRONG_WF_STATUS")."<br>";
			elseif($WF=="Y" && $bWorkflow && CIBlockElement::WF_GetStatusPermission($_POST["WF_STATUS_ID"])<1)
				$strWarning .= GetMessage("IBLOCK_ACCESS_DENIED_STATUS")." [".$_POST["WF_STATUS_ID"]."]."."<br>";
			elseif(0 >= $ID && !isset($_POST["IBLOCK_SECTION"]) && !CIBlockRights::UserHasRightTo($IBLOCK_ID, $IBLOCK_ID, "section_element_bind"))
				$strWarning .= GetMessage("IBLOCK_ACCESS_DENIED_SECTION")."<br>";
			elseif(!$customTabber->Check())
			{
				if($ex = $APPLICATION->GetException())
					$strWarning .= $ex->GetString();
				else
					$strWarning .= "Error. ";
			}
			else
			{
				if ($bCatalog)
				{
					$arCatalogItem = array(
						'IBLOCK_ID' => $IBLOCK_ID,
						'SECTION_ID' => $MENU_SECTION_ID,
						'ID' => (!$bCopy ? $ID : 0),
						'PRODUCT_ID' => (0 < $ID && !$bCopy ? CIBlockElement::GetRealElement($ID) : 0)
					);
					if (
						$arShowTabs['catalog']
						&& file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/admin/templates/product_edit_validator.php")
					)
					{
					// errors'll be appended to $strWarning;
						$boolSKUExists = false;
						if (CCatalogSKU::TYPE_FULL == $arMainCatalog['CATALOG_TYPE'])
						{
							$boolSKUExists = CCatalogSKU::IsExistOffers(($ID > 0 && !$bCopy ? $ID : '-'.$str_TMP_ID), $IBLOCK_ID);
						}
						include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/admin/templates/product_edit_validator.php");
					}
					if ($arShowTabs['product_set'])
					{
						CCatalogAdminProductSetEdit::setProductFormParams(array('TYPE' => CCatalogProductSet::TYPE_SET));
						if (!CCatalogAdminProductSetEdit::checkFormValues($arCatalogItem))
						{
							$strWarning .= implode('<br>', CCatalogAdminProductSetEdit::getErrors());
						}
					}
					if ($arShowTabs['product_group'])
					{
						CCatalogAdminProductSetEdit::setProductFormParams(array('TYPE' => CCatalogProductSet::TYPE_GROUP));
						if (!CCatalogAdminProductSetEdit::checkFormValues($arCatalogItem))
						{
							$strWarning .= implode('<br>', CCatalogAdminProductSetEdit::getErrors());
						}
					}
				}
				if ($bBizproc)
				{
					if($canWrite)
					{
						$arBizProcParametersValues = array();
						foreach ($arDocumentStates as $arDocumentState)
						{
							if (strlen($arDocumentState["ID"]) <= 0)
							{
								$arErrorsTmp = array();

								$arBizProcParametersValues[$arDocumentState["TEMPLATE_ID"]] = CBPDocument::StartWorkflowParametersValidate(
									$arDocumentState["TEMPLATE_ID"],
									$arDocumentState["TEMPLATE_PARAMETERS"],
									array(MODULE_ID, ENTITY, DOCUMENT_TYPE),
									$arErrorsTmp
								);

								if (!empty($arErrorsTmp))
								{
									foreach ($arErrorsTmp as $e)
										$strWarning .= $e["message"]."<br />";
								}
							}
						}
					}
					else
					{
						$strWarning .= GetMessage("IBLOCK_ACCESS_DENIED_STATUS")."<br />";
					}
				}

				if ($strWarning == '')
				{
					$bs = new CIBlockElement;

					$arPREVIEW_PICTURE = CIBlock::makeFileArray(
						array_key_exists("PREVIEW_PICTURE", $_FILES)? $_FILES["PREVIEW_PICTURE"]: $_REQUEST["PREVIEW_PICTURE"],
						${"PREVIEW_PICTURE_del"} === "Y",
						${"PREVIEW_PICTURE_descr"}
					);
					if ($arPREVIEW_PICTURE["error"] == 0)
						$arPREVIEW_PICTURE["COPY_FILE"] = "Y";

					$arDETAIL_PICTURE = CIBlock::makeFileArray(
						array_key_exists("DETAIL_PICTURE", $_FILES)? $_FILES["DETAIL_PICTURE"]: $_REQUEST["DETAIL_PICTURE"],
						${"DETAIL_PICTURE_del"} === "Y",
						${"DETAIL_PICTURE_descr"}
					);
					if ($arDETAIL_PICTURE["error"] == 0)
						$arDETAIL_PICTURE["COPY_FILE"] = "Y";

					$arFields = array(
						"ACTIVE" => $_POST["ACTIVE"],
						"MODIFIED_BY" => $USER->GetID(),
						"IBLOCK_ID" => $IBLOCK_ID,
						"ACTIVE_FROM" => $_POST["ACTIVE_FROM"],
						"ACTIVE_TO" => $_POST["ACTIVE_TO"],
						"SORT" => $_POST["SORT"],
						"NAME" => $_POST["NAME"],
						"CODE" => trim($_POST["CODE"], " \t\n\r"),
						"TAGS" => $_POST["TAGS"],
						"PREVIEW_PICTURE" => $arPREVIEW_PICTURE,
						"PREVIEW_TEXT" => $_POST["PREVIEW_TEXT"],
						"PREVIEW_TEXT_TYPE" => $_POST["PREVIEW_TEXT_TYPE"],
						"DETAIL_PICTURE" => $arDETAIL_PICTURE,
						"DETAIL_TEXT" => $_POST["DETAIL_TEXT"],
						"DETAIL_TEXT_TYPE" => $_POST["DETAIL_TEXT_TYPE"],
						"TMP_ID" => $str_TMP_ID,
						"PROPERTY_VALUES" => $PROP,
					);

					if(isset($_POST["IBLOCK_SECTION"]) && is_array($_POST["IBLOCK_SECTION"]))
						$arFields["IBLOCK_SECTION"] = $_POST["IBLOCK_SECTION"];

					if(COption::GetOptionString("iblock", "show_xml_id", "N")=="Y" && is_set($_POST, "XML_ID"))
						$arFields["XML_ID"] = trim($_POST["XML_ID"], " \t\n\r");

					if($bEditRights)
					{
						if(is_array($_POST["RIGHTS"]) )
							$arFields["RIGHTS"] = CIBlockRights::Post2Array($_POST["RIGHTS"]);
						else
							$arFields["RIGHTS"] = array();
					}

					if (is_array($_POST["IPROPERTY_TEMPLATES"]))
					{
						$ELEMENT_PREVIEW_PICTURE_FILE_NAME = \Bitrix\Iblock\Template\Helper::convertArrayToModifiers($_POST["IPROPERTY_TEMPLATES"]["ELEMENT_PREVIEW_PICTURE_FILE_NAME"]);
						$ELEMENT_DETAIL_PICTURE_FILE_NAME = \Bitrix\Iblock\Template\Helper::convertArrayToModifiers($_POST["IPROPERTY_TEMPLATES"]["ELEMENT_DETAIL_PICTURE_FILE_NAME"]);

						$arFields["IPROPERTY_TEMPLATES"] = array(
							"ELEMENT_META_TITLE" => (
								$_POST["IPROPERTY_TEMPLATES"]["ELEMENT_META_TITLE"]["INHERITED"]==="N"?
									$_POST["IPROPERTY_TEMPLATES"]["ELEMENT_META_TITLE"]["TEMPLATE"]:
									""
								),
							"ELEMENT_META_KEYWORDS" => (
								$_POST["IPROPERTY_TEMPLATES"]["ELEMENT_META_KEYWORDS"]["INHERITED"]==="N"?
									$_POST["IPROPERTY_TEMPLATES"]["ELEMENT_META_KEYWORDS"]["TEMPLATE"]:
									""
								),
							"ELEMENT_META_DESCRIPTION" => (
								$_POST["IPROPERTY_TEMPLATES"]["ELEMENT_META_DESCRIPTION"]["INHERITED"]==="N"?
									$_POST["IPROPERTY_TEMPLATES"]["ELEMENT_META_DESCRIPTION"]["TEMPLATE"]:
									""
								),
							"ELEMENT_PAGE_TITLE" => (
								$_POST["IPROPERTY_TEMPLATES"]["ELEMENT_PAGE_TITLE"]["INHERITED"]==="N"?
									$_POST["IPROPERTY_TEMPLATES"]["ELEMENT_PAGE_TITLE"]["TEMPLATE"]:
									""
								),
							"ELEMENT_PREVIEW_PICTURE_FILE_ALT" => (
								$_POST["IPROPERTY_TEMPLATES"]["ELEMENT_PREVIEW_PICTURE_FILE_ALT"]["INHERITED"]==="N"?
									$_POST["IPROPERTY_TEMPLATES"]["ELEMENT_PREVIEW_PICTURE_FILE_ALT"]["TEMPLATE"]:
									""
								),
							"ELEMENT_PREVIEW_PICTURE_FILE_TITLE" => (
								$_POST["IPROPERTY_TEMPLATES"]["ELEMENT_PREVIEW_PICTURE_FILE_TITLE"]["INHERITED"]==="N"?
									$_POST["IPROPERTY_TEMPLATES"]["ELEMENT_PREVIEW_PICTURE_FILE_TITLE"]["TEMPLATE"]:
									""
								),
							"ELEMENT_PREVIEW_PICTURE_FILE_NAME" => (
								$_POST["IPROPERTY_TEMPLATES"]["ELEMENT_PREVIEW_PICTURE_FILE_NAME"]["INHERITED"]==="N"?
									$ELEMENT_PREVIEW_PICTURE_FILE_NAME:
									""
								),
							"ELEMENT_DETAIL_PICTURE_FILE_ALT" => (
								$_POST["IPROPERTY_TEMPLATES"]["ELEMENT_DETAIL_PICTURE_FILE_ALT"]["INHERITED"]==="N"?
									$_POST["IPROPERTY_TEMPLATES"]["ELEMENT_DETAIL_PICTURE_FILE_ALT"]["TEMPLATE"]:
									""
								),
							"ELEMENT_DETAIL_PICTURE_FILE_TITLE" => (
								$_POST["IPROPERTY_TEMPLATES"]["ELEMENT_DETAIL_PICTURE_FILE_TITLE"]["INHERITED"]==="N"?
									$_POST["IPROPERTY_TEMPLATES"]["ELEMENT_DETAIL_PICTURE_FILE_TITLE"]["TEMPLATE"]:
									""
								),
							"ELEMENT_DETAIL_PICTURE_FILE_NAME" => (
								$_POST["IPROPERTY_TEMPLATES"]["ELEMENT_DETAIL_PICTURE_FILE_NAME"]["INHERITED"]==="N"?
									$ELEMENT_DETAIL_PICTURE_FILE_NAME:
									""
								),
						);
					}

					if ($bWorkflow)
					{
						$arFields["WF_COMMENTS"] = $_POST["WF_COMMENTS"];
						if(intval($_POST["WF_STATUS_ID"])>0)
						{
							$arFields["WF_STATUS_ID"] = $_POST["WF_STATUS_ID"];
						}
					}

					if($bBizproc)
					{
						$BP_HISTORY_NAME = $arFields["NAME"];
						if($ID <= 0)
							$arFields["BP_PUBLISHED"] = "N";
					}

					if($ID > 0)
					{
						$bCreateRecord = false;
						$res = $bs->Update($ID, $arFields, $WF=="Y", true, true);
					}
					else
					{
						$bCreateRecord = true;
						$ID = $bs->Add($arFields, $bWorkflow, true, true);
						$res = ($ID > 0);
						$PARENT_ID = $ID;

						if ($res)
						{
							if ($arShowTabs['sku'])
							{
								$arFilter = array('IBLOCK_ID' => $arMainCatalog['IBLOCK_ID'],'=PROPERTY_'.$arMainCatalog['SKU_PROPERTY_ID'] => '-'.$str_TMP_ID);
								$rsOffersItems = CIBlockElement::GetList(
									array(),
									$arFilter,
									false,
									false,
									array('ID')
								);
								while ($arOfferItem = $rsOffersItems->Fetch())
								{
									CIBlockElement::SetPropertyValues(
										$arOfferItem['ID'],
										$arMainCatalog['IBLOCK_ID'],
										$ID,
										$arMainCatalog['SKU_PROPERTY_ID']
									);
								}
								$boolFlagClear = CIBlockOffersTmp::Delete($str_TMP_ID);
								$boolFlagClearAll = CIBlockOffersTmp::DeleteOldID($IBLOCK_ID);
							}
						}
					}

					if (!$res)
					{
						$strWarning .= $bs->LAST_ERROR."<br>";
					}
					else
					{
						$ipropValues = new \Bitrix\Iblock\InheritedProperty\ElementValues($IBLOCK_ID, $ID);
						$ipropValues->clearValues();
						CIBlockElement::RecalcSections($ID);
					}

					if ('' == $strWarning && $bCatalog)
					{
						$arCatalogItem = array(
							'IBLOCK_ID' => $IBLOCK_ID,
							'SECTION_ID' => $MENU_SECTION_ID,
							'ID' => $ID,
							'PRODUCT_ID' => CIBlockElement::GetRealElement($ID)
						);
						if ($arShowTabs['catalog'])
						{
							include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/admin/templates/product_edit_action.php");
						}
						elseif ($arShowTabs['sku'])
						{
							if (!CCatalogProduct::IsExistProduct($arCatalogItem['PRODUCT_ID']))
							{
								$arEmptyProduct = array(
									'ID' => $arCatalogItem['PRODUCT_ID'],
									'QUANTITY' => 0,
									'QUANTITY_TRACE' => 'N',
									'CAN_BUY_ZERO' => 'N',
									'NEGATIVE_AMOUNT_TRACE' => 'N',
									'TYPE' => CCatalogProduct::TYPE_SKU
								);
								CCatalogProduct::Add($arEmptyProduct, false);
							}
						}
						if ($arShowTabs['product_set'])
						{
							CCatalogAdminProductSetEdit::setProductFormParams(array('TYPE' => CCatalogProductSet::TYPE_SET));
							CCatalogAdminProductSetEdit::saveFormValues($arCatalogItem);
						}
						if ($arShowTabs['product_group'])
						{
							CCatalogAdminProductSetEdit::setProductFormParams(array('TYPE' => CCatalogProductSet::TYPE_GROUP));
							CCatalogAdminProductSetEdit::saveFormValues($arCatalogItem);
						}
					}
				} // if ($strWarning)

				if ($bBizproc)
				{
					if ($strWarning == '')
					{
						$arBizProcWorkflowId = array();
						foreach ($arDocumentStates as $arDocumentState)
						{
							if (strlen($arDocumentState["ID"]) <= 0)
							{
								$arErrorsTmp = array();

								$arBizProcWorkflowId[$arDocumentState["TEMPLATE_ID"]] = CBPDocument::StartWorkflow(
									$arDocumentState["TEMPLATE_ID"],
									array(MODULE_ID, ENTITY, $ID),
									$arBizProcParametersValues[$arDocumentState["TEMPLATE_ID"]],
									$arErrorsTmp
								);

								if (!empty($arErrorsTmp))
								{
									foreach ($arErrorsTmp as $e)
										$strWarning .= $e["message"]."<br />";
								}
							}
						}
					}

					if ($strWarning == '')
					{
						$bizprocIndex = intval($_REQUEST["bizproc_index"]);
						if ($bizprocIndex > 0)
						{
							for ($i = 1; $i <= $bizprocIndex; $i++)
							{
								$bpId = trim($_REQUEST["bizproc_id_".$i]);
								$bpTemplateId = intval($_REQUEST["bizproc_template_id_".$i]);
								$bpEvent = trim($_REQUEST["bizproc_event_".$i]);

								if (strlen($bpEvent) > 0)
								{
									if (strlen($bpId) > 0)
									{
										if (!array_key_exists($bpId, $arDocumentStates))
											continue;
									}
									else
									{
										if (!array_key_exists($bpTemplateId, $arDocumentStates))
											continue;
										$bpId = $arBizProcWorkflowId[$bpTemplateId];
									}

									$arErrorTmp = array();
									CBPDocument::SendExternalEvent(
										$bpId,
										$bpEvent,
										array("Groups" => $arCurrentUserGroups, "User" => $USER->GetID()),
										$arErrorTmp
									);

									if (!empty($arErrorsTmp))
									{
										foreach ($arErrorsTmp as $e)
											$strWarning .= $e["message"]."<br />";
									}
								}
							}
						}

						$arDocumentStates = null;
						CBPDocument::AddDocumentToHistory(array(MODULE_ID, ENTITY, $ID), $BP_HISTORY_NAME, $GLOBALS["USER"]->GetID());
					}
				}
			}

			if($strWarning == '')
			{
				if(!$customTabber->Action())
				{
					if ($ex = $APPLICATION->GetException())
						$strWarning .= $ex->GetString();
					else
						$strWarning .= "Error. ";
				}
			}

			if($strWarning != '')
			{
				$error = new _CIBlockError(2, "BAD_SAVE", $strWarning);
				$bVarsFromForm = true;
				$DB->Rollback();
			}
			else
			{
				if($bWorkflow)
					CIBlockElement::WF_UnLock($ID);

				$arFields['ID'] = $ID;
				if (function_exists('BXIBlockAfterSave'))
					BXIBlockAfterSave($arFields);

				$DB->Commit();

				if(strlen($apply) <= 0 && strlen($save_and_add) <= 0)
				{
					if ($bAutocomplete)
					{
						if (defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1)
						{
							?><script type="text/javascript">
							top.<? echo $strLookup; ?>.AddValue(<? echo $ID;?>);
							top.BX.WindowManager.Get().AllowClose(); top.BX.WindowManager.Get().Close();
							</script><?
							die();
						}
						else
						{
							?><script type="text/javascript">
							window.opener.<? echo $strLookup; ?>.AddValue(<? echo $ID;?>);
							window.close();
							</script><?
						}
					}
					elseif(strlen($return_url) > 0)
					{
						if(strpos($return_url, "#")!==false)
						{
							$rsElement = CIBlockElement::GetList(array(), array(
								"ID" => $ID,
								"SHOW_NEW" => "Y",
							), false, false, array("DETAIL_PAGE_URL"));
							$arElement = $rsElement->Fetch();
							if($arElement)
								$return_url = CIBlock::ReplaceDetailUrl($return_url, $arElement, true, "E");
						}

						if(defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1)
						{
							if($return_url === "reload_absence_calendar")
							{
								echo '<script type="text/javascript">top.jsBXAC.__reloadCurrentView();</script>';
								die();
							}
							else
							{
								LocalRedirect($return_url);
							}
						}
						else
						{
							LocalRedirect($return_url);
						}
					}
					else
					{
						LocalRedirect("/bitrix/admin/".CIBlock::GetAdminElementListLink($IBLOCK_ID, array('find_section_section'=>intval($find_section_section))));
					}
				}
				elseif(strlen($save_and_add) > 0)
				{
					$params = array(
						"WF" => ($WF=="Y"? "Y": null),
						"find_section_section" => intval($find_section_section),
						"return_url" => (strlen($return_url) > 0? $return_url: null),
					);
					if ($IBLOCK_SECTION_ID > 0)
					{
						$params["IBLOCK_SECTION_ID"] = intval($IBLOCK_SECTION_ID);
					}
					elseif(isset($arFields["IBLOCK_SECTION"]) && !empty($arFields["IBLOCK_SECTION"]))
					{
						foreach($arFields["IBLOCK_SECTION"] as $i => $id)
						$params["IBLOCK_SECTION_ID[".$i."]"] = $id;
					}

					if (defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1)
					{
						while(ob_end_clean());
						$l = CUtil::JSEscape(CIBlock::GetAdminElementEditLink($IBLOCK_ID, 0, array_merge($params, array(
								"from_module" => "iblock",
								"bxpublic" => "Y",
								"nobuttons" => "Y",
							)), "&".$tabControl->ActiveTabParam()));
						?>
						<script type="text/javascript">
							top.BX.ajax.get(
								'/bitrix/admin/<? echo $l; ?>',
								function (result) {
									top.BX.closeWait();
									top.window.reloadAfterClose = true;
									top.BX.WindowManager.Get().SetContent(result);
								}
							);
						</script>
						<?
						die();
					}
					else
					{
						LocalRedirect("/bitrix/admin/".CIBlock::GetAdminElementEditLink($IBLOCK_ID, 0, $params, "&".$tabControl->ActiveTabParam()));
					}
				}
				else
				{
					LocalRedirect("/bitrix/admin/".CIBlock::GetAdminElementEditLink($IBLOCK_ID, $ID, array(
						"WF" => ($WF=="Y"? "Y": null),
						"find_section_section" => intval($find_section_section),
						"return_url" => (strlen($return_url) > 0? $return_url: null),
						"lookup" => $bAutocomplete ? $strLookup : null,
					), "&".$tabControl->ActiveTabParam()));
				}
			}
		}

		if(!empty($dontsave) && check_bitrix_sessid())
		{
			if($bWorkflow)
				CIBlockElement::WF_UnLock($ID);

			if(strlen($return_url)>0)
			{
				if ($bAutocomplete)
				{
						?><script type="text/javascript">
						window.opener.<? echo $strLookup; ?>.AddValue(<? echo $ID;?>);
						window.close();
						</script><?
				}
				elseif(defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1)
				{
					echo '<script type="text/javascript">top.BX.closeWait(); top.BX.WindowManager.Get().AllowClose(); top.BX.WindowManager.Get().Close();</script>';
					die();
				}
				else
				{
					$rsElement = CIBlockElement::GetList(array(), array("=ID" => $ID), false, array("nTopCount" => 1), array("DETAIL_PAGE_URL"));
					$arElement = $rsElement->Fetch();
					if($arElement)
						$return_url = CIBlock::ReplaceDetailUrl($return_url, $arElement, true, "E");
					LocalRedirect($return_url);
				}
			}
			else
			{
				if ($bAutocomplete)
				{
					?><script type="text/javascript">
					window.opener.<? echo $strLookup; ?>.AddValue(<? echo $ID;?>);
					window.close();
					</script><?
				}
				else
				{
					LocalRedirect("/bitrix/admin/".CIBlock::GetAdminElementListLink($IBLOCK_ID, array('find_section_section'=>intval($find_section_section))));
				}
			}
		}
	}

}while(false);

if($error && $error->err_level==1)
{
	if ($bAutocomplete)
		require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");
	else
		require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

	CAdminMessage::ShowOldStyleError($error->GetErrorText());
}
else
{
	if(!$arIBlock["ELEMENT_NAME"])
		$arIBlock["ELEMENT_NAME"] = $arIBTYPE["ELEMENT_NAME"]? $arIBTYPE["ELEMENT_NAME"]: GetMessage("IBEL_E_IBLOCK_ELEMENT");
	if(!$arIBlock["SECTIONS_NAME"])
		$arIBlock["SECTIONS_NAME"] = $arIBTYPE["SECTION_NAME"]? $arIBTYPE["SECTION_NAME"]: GetMessage("IBEL_E_IBLOCK_SECTIONS");

	ClearVars("str_");
	ClearVars("str_prev_");
	ClearVars("prn_");
	$str_SORT="500";

	if(!$error && $bWorkflow && $view!="Y")
	{
		if(!$bCopy)
			CIBlockElement::WF_Lock($ID);
		else
			CIBlockElement::WF_UnLock($ID);
	}

	if($historyId <= 0 && $view=="Y")
	{
		$WF_ID = $ID;
		$ID = CIBlockElement::GetRealElement($ID);

		if($PREV_ID)
		{
			$prev_result = CIBlockElement::GetByID($PREV_ID);
			$prev_arElement = $prev_result->ExtractFields("str_prev_");
			if(!$prev_arElement)
				$PREV_ID = 0;
		}
	}

	$str_IBLOCK_ELEMENT_SECTION = Array();
	$str_ACTIVE = $arIBlock["FIELDS"]["ACTIVE"]["DEFAULT_VALUE"] === "N"? "N": "Y";
	$str_NAME = htmlspecialcharsbx($arIBlock["FIELDS"]["NAME"]["DEFAULT_VALUE"]);
	if($arIBlock["FIELDS"]["ACTIVE_FROM"]["DEFAULT_VALUE"] === "=now")
		$str_ACTIVE_FROM = ConvertTimeStamp(time() + CTimeZone::GetOffset(), "FULL");
	elseif($arIBlock["FIELDS"]["ACTIVE_FROM"]["DEFAULT_VALUE"] === "=today")
		$str_ACTIVE_FROM = ConvertTimeStamp(time() + CTimeZone::GetOffset(), "SHORT");

	if(intval($arIBlock["FIELDS"]["ACTIVE_TO"]["DEFAULT_VALUE"]) > 0)
		$str_ACTIVE_TO = ConvertTimeStamp(time() + intval($arIBlock["FIELDS"]["ACTIVE_TO"]["DEFAULT_VALUE"])*24*60*60 + CTimeZone::GetOffset(), "FULL");

	$str_PREVIEW_TEXT_TYPE = $arIBlock["FIELDS"]["PREVIEW_TEXT_TYPE"]["DEFAULT_VALUE"] !== "html"? "text": "html";
	$str_PREVIEW_TEXT = htmlspecialcharsbx($arIBlock["FIELDS"]["PREVIEW_TEXT"]["DEFAULT_VALUE"]);
	$str_DETAIL_TEXT_TYPE = $arIBlock["FIELDS"]["DETAIL_TEXT_TYPE"]["DEFAULT_VALUE"] !== "html"? "text": "html";
	$str_DETAIL_TEXT = htmlspecialcharsbx($arIBlock["FIELDS"]["DETAIL_TEXT"]["DEFAULT_VALUE"]);

	if ($historyId > 0)
	{
		$view = "Y";
		foreach ($arResult["DOCUMENT"]["FIELDS"] as $k => $v)
			${"str_".$k} = $v;
	}
	else
	{
		$result = CIBlockElement::GetByID($WF_ID);

		if($arElement = $result->ExtractFields("str_"))
		{
			if($str_IN_SECTIONS=="N")
			{
				$str_IBLOCK_ELEMENT_SECTION[] = 0;
			}
			else
			{
				$result = CIBlockElement::GetElementGroups($WF_ID, true, array('ID', 'IBLOCK_ELEMENT_ID'));
				while($ar = $result->Fetch())
					$str_IBLOCK_ELEMENT_SECTION[] = $ar["ID"];
			}
			$ipropTemlates = new \Bitrix\Iblock\InheritedProperty\ElementTemplates($IBLOCK_ID, $WF_ID);
		}
		else
		{
			$WF_ID=0;
			$ID=0;
			if(is_array($IBLOCK_SECTION_ID))
			{
				foreach($IBLOCK_SECTION_ID as $id)
					if($id > 0)
						$str_IBLOCK_ELEMENT_SECTION[] = $id;
			}
			elseif($IBLOCK_SECTION_ID > 0)
			{
				$str_IBLOCK_ELEMENT_SECTION[] = $IBLOCK_SECTION_ID;
			}
			$ipropTemlates = new \Bitrix\Iblock\InheritedProperty\ElementTemplates($IBLOCK_ID, 0);
			$ipropTemlates->getValuesEntity()->setParents($str_IBLOCK_ELEMENT_SECTION);
		}
		$str_IPROPERTY_TEMPLATES = $ipropTemlates->findTemplates();
		$str_IPROPERTY_TEMPLATES["ELEMENT_PREVIEW_PICTURE_FILE_NAME"] = \Bitrix\Iblock\Template\Helper::convertModifiersToArray($str_IPROPERTY_TEMPLATES["ELEMENT_PREVIEW_PICTURE_FILE_NAME"]);
		$str_IPROPERTY_TEMPLATES["ELEMENT_DETAIL_PICTURE_FILE_NAME"] = \Bitrix\Iblock\Template\Helper::convertModifiersToArray($str_IPROPERTY_TEMPLATES["ELEMENT_DETAIL_PICTURE_FILE_NAME"]);
	}

	if($bCopy)
	{
		$str_XML_ID = "";
	}

	if($ID > 0 && !$bCopy)
	{
		if($view=="Y" || ($bBizproc && !$canWrite))
			$APPLICATION->SetTitle($arIBlock["NAME"].": ".$arIBlock["ELEMENT_NAME"].": ".$arElement["NAME"]." - ".GetMessage("IBLOCK_ELEMENT_EDIT_VIEW"));
		else
			$APPLICATION->SetTitle($arIBlock["NAME"].": ".$arIBlock["ELEMENT_NAME"].": ".$arElement["NAME"]." - ".GetMessage("IBLOCK_EDIT_TITLE"));
	}
	else
	{
		$APPLICATION->SetTitle($arIBlock["NAME"].": ".$arIBlock["ELEMENT_NAME"].": ".GetMessage("IBLOCK_NEW_TITLE"));
	}

	if($arIBTYPE["SECTIONS"]=="Y")
		$sSectionUrl = CIBlock::GetAdminSectionListLink($IBLOCK_ID, array('find_section_section'=>0));
	else
		$sSectionUrl = CIBlock::GetAdminElementListLink($IBLOCK_ID, array('find_section_section'=>0));

	if(!defined("CATALOG_PRODUCT"))
	{
		$adminChain->AddItem(array(
			"TEXT" => htmlspecialcharsex($arIBlock["NAME"]),
			"LINK" => htmlspecialcharsbx($sSectionUrl),
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
				$adminChain->AddItem(array(
					"TEXT" => $ar_nav["NAME"],
					"LINK" => htmlspecialcharsbx($sSectionUrl),
				));

				if($ar_nav["ID"] != $find_section_section)
					$sLastFolder = $sSectionUrl;
			}
		}
	}

	if ($bAutocomplete)
		require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");
	elseif ($bPropertyAjax)
		require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");
	else
		require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

	if($bVarsFromForm)
	{
		if(!isset($ACTIVE)) $ACTIVE = "N"; //It is checkbox. So it is not set in POST.
		$DB->InitTableVarsForEdit("b_iblock_element", "", "str_");
		$str_IBLOCK_ELEMENT_SECTION = $IBLOCK_SECTION;
		$str_IPROPERTY_TEMPLATES = $_POST["IPROPERTY_TEMPLATES"];
	}
	elseif ($bPropertyAjax)
	{
		CUtil::JSPostUnescape();
	}

	if ($bPropertyAjax)
		$str_IBLOCK_ELEMENT_SECTION = $_REQUEST["IBLOCK_SECTION"];

	if (is_array($str_IBLOCK_ELEMENT_SECTION))
		$str_IBLOCK_ELEMENT_SECTION = array_unique($str_IBLOCK_ELEMENT_SECTION);

	$clearedByCopyProperties = array();
	if ($bCopy)
	{
		$clearedByCopyProperties = CIBlockPropertyTools::getClearedPropertiesID($IBLOCK_ID);
	}
	$arPROP_tmp = array();
	$properties = CIBlockProperty::GetList(array("SORT"=>"ASC", "NAME"=>"ASC"), array("IBLOCK_ID"=>$IBLOCK_ID, "ACTIVE"=>"Y", "CHECK_PERMISSIONS"=>"N"));
	while($prop_fields = $properties->Fetch())
	{
		$prop_values = array();
		$prop_values_with_descr = array();
		if (
			$bPropertyAjax
			&& is_array($_POST["PROP"])
			&& array_key_exists($prop_fields["ID"], $_POST["PROP"])
		)
		{
			$prop_values = $_POST["PROP"][$prop_fields["ID"]];
			$prop_values_with_descr = $prop_values;
		}
		elseif ($bVarsFromForm)
		{
			if($prop_fields["PROPERTY_TYPE"]=="F")
			{
				$db_prop_values = CIBlockElement::GetProperty($IBLOCK_ID, $WF_ID, "id", "asc", Array("ID"=>$prop_fields["ID"], "EMPTY"=>"N"));
				while($res = $db_prop_values->Fetch())
				{
					$prop_values[$res["PROPERTY_VALUE_ID"]] = $res["VALUE"];
					$prop_values_with_descr[$res["PROPERTY_VALUE_ID"]] = array("VALUE"=>$res["VALUE"],"DESCRIPTION"=>$res["DESCRIPTION"]);
				}
			}
			elseif(is_array($PROP))
			{
				if(array_key_exists($prop_fields["ID"], $PROP))
					$prop_values = $PROP[$prop_fields["ID"]];
				else
					$prop_values = $PROP[$prop_fields["CODE"]];
				$prop_values_with_descr = $prop_values;
			}
			else
			{
				$prop_values = "";
				$prop_values_with_descr = $prop_values;
			}
		}
		else
		{
			if ($historyId > 0)
			{
				$vx = $arResult["DOCUMENT"]["PROPERTIES"][(strlen(trim($prop_fields["CODE"])) > 0) ? $prop_fields["CODE"] : $prop_fields["ID"]];

				$prop_values = array();
				if (is_array($vx["VALUE"]) && is_array($vx["DESCRIPTION"]))
				{
					for ($i = 0, $cnt = count($vx["VALUE"]); $i < $cnt; $i++)
						$prop_values[] = array("VALUE" => $vx["VALUE"][$i], "DESCRIPTION" => $vx["DESCRIPTION"][$i]);
				}
				else
				{
					$prop_values[] = array("VALUE" => $vx["VALUE"], "DESCRIPTION" => $vx["DESCRIPTION"]);
				}

				$prop_values_with_descr = $prop_values;
			}
			elseif($ID>0)
			{
				if (empty($clearedByCopyProperties) || !in_array($prop_fields["ID"], $clearedByCopyProperties))
				{
					$db_prop_values = CIBlockElement::GetProperty($IBLOCK_ID, $WF_ID, "id", "asc", array("ID"=>$prop_fields["ID"], "EMPTY"=>"N"));
					while($res = $db_prop_values->Fetch())
					{
						if($res["WITH_DESCRIPTION"]=="Y")
							$prop_values[$res["PROPERTY_VALUE_ID"]] = array("VALUE"=>$res["VALUE"], "DESCRIPTION"=>$res["DESCRIPTION"]);
						else
							$prop_values[$res["PROPERTY_VALUE_ID"]] = $res["VALUE"];
						$prop_values_with_descr[$res["PROPERTY_VALUE_ID"]] = array("VALUE"=>$res["VALUE"], "DESCRIPTION"=>$res["DESCRIPTION"]);
					}
				}
			}
		}

		$prop_fields["VALUE"] = $prop_values;
		$prop_fields["~VALUE"] = $prop_values_with_descr;
		if(strlen(trim($prop_fields["CODE"]))>0)
			$arPROP_tmp[$prop_fields["CODE"]] = $prop_fields;
		else
			$arPROP_tmp[$prop_fields["ID"]] = $prop_fields;
	}
	$PROP = $arPROP_tmp;

	$aMenu = array();
	if ( !$bAutocomplete && !$bPropertyAjax )
	{
		$aMenu = array(
			array(
				"TEXT" => htmlspecialcharsex($arIBlock["ELEMENTS_NAME"]),
				"LINK" => CIBlock::GetAdminElementListLink($IBLOCK_ID, array('find_section_section'=>intval($find_section_section))),
				"ICON" => "btn_list",
			)
		);

		if (!$bCopy)
		{
			$aMenu[] = array(
				"TEXT"=>GetMessage("IBEL_E_COPY_ELEMENT"),
				"TITLE"=>GetMessage("IBEL_E_COPY_ELEMENT_TITLE"),
				"LINK"=>"/bitrix/admin/".CIBlock::GetAdminElementEditLink($IBLOCK_ID, $ID, array(
					"IBLOCK_SECTION_ID" => $MENU_SECTION_ID,
					"find_section_section" => intval($find_section_section),
					"action" => "copy",
				)),
				"ICON"=>"btn_copy",
			);
		}

		if ($bCatalog)
		{
			$arCatalogBtns = CCatalogAdminTools::getIBlockElementContentMenu(
				$IBLOCK_ID,
				$ID,
				$arMainCatalog,
				array(
					"IBLOCK_SECTION_ID" => $MENU_SECTION_ID,
					"find_section_section" => intval($find_section_section),
				)
			);
			if (!empty($arCatalogBtns))
			{
				$aMenu[] = array('SEPARATOR' => 'Y');
				$aMenu[] = $arCatalogBtns;
			}
		}

		if($ID > 0 && !$bCopy)
		{
			$arSubMenu = array();
			$arSubMenu[] = array(
				"TEXT" => htmlspecialcharsex($arIBlock["ELEMENT_ADD"]),
				"LINK" => "/bitrix/admin/".CIBlock::GetAdminElementEditLink($IBLOCK_ID, 0, array(
					"IBLOCK_SECTION_ID" => $MENU_SECTION_ID,
					"find_section_section" => intval($find_section_section),
				)),
				'ICON' => 'edit',
			);
			if (CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $ID, "element_delete"))
			{
				$urlDelete = CIBlock::GetAdminElementListLink($IBLOCK_ID, array('find_section_section'=>intval($find_section_section), 'action'=>'delete'));
				$urlDelete .= '&'.bitrix_sessid_get();
				$urlDelete .= '&ID='.(preg_match('/^iblock_list_admin\.php/', $urlDelete)? "E": "").$ID;
				$arSubMenu[] = array(
					"TEXT" => htmlspecialcharsex($arIBlock["ELEMENT_DELETE"]),
					"ACTION" => "if(confirm('".GetMessageJS("IBLOCK_ELEMENT_DEL_CONF")."'))window.location='".CUtil::JSEscape($urlDelete)."';",
					'ICON' => 'delete',
				);
			}

			if($bWorkflow && !defined("CATALOG_PRODUCT"))
			{
				$arSubMenu[] = array(
					"TEXT" => GetMessage("IBEL_HIST"),
					"LINK" => '/bitrix/admin/iblock_history_list.php?ELEMENT_ID='.$ID.'&type='.urlencode($arIBlock["IBLOCK_TYPE_ID"]).'&lang='.LANGUAGE_ID.'&IBLOCK_ID='.$IBLOCK_ID.'&find_section_section='.intval($find_section_section),
				);
			}

			if (!empty($arSubMenu))
			{
				$aMenu[] = array("SEPARATOR"=>"Y");
				$aMenu[] = array(
					"TEXT" => GetMessage('IBEL_E_ACTIONS'),
					"TITLE" => GetMessage('IBEL_E_ACTIONS_TITLE'),
					"MENU" => $arSubMenu,
					'ICON' => 'btn_new'
				);
			}
		}

		$context = new CAdminContextMenu($aMenu);
		$context->Show();
	}

	if($error)
		CAdminMessage::ShowOldStyleError($error->GetErrorText());

	$bFileman = CModule::IncludeModule("fileman");
	$arTranslit = $arIBlock["FIELDS"]["CODE"]["DEFAULT_VALUE"];
	$bLinked = (!strlen($str_TIMESTAMP_X) || $bCopy) && $_POST["linked_state"]!=='N';

if(strlen($arIBlock["EDIT_FILE_AFTER"])>0 && is_file($_SERVER["DOCUMENT_ROOT"].$arIBlock["EDIT_FILE_AFTER"])):
	include($_SERVER["DOCUMENT_ROOT"].$arIBlock["EDIT_FILE_AFTER"]);
	$_SESSION['IBLOCK_CUSTOM_FORM'] = true;
elseif(strlen($arIBTYPE["EDIT_FILE_AFTER"])>0 && is_file($_SERVER["DOCUMENT_ROOT"].$arIBTYPE["EDIT_FILE_AFTER"])):
	include($_SERVER["DOCUMENT_ROOT"].$arIBTYPE["EDIT_FILE_AFTER"]);
	$_SESSION['IBLOCK_CUSTOM_FORM'] = true;
else:
?>

<?
	//////////////////////////
	//START of the custom form
	//////////////////////////

	//We have to explicitly call calendar and editor functions because
	//first output may be discarded by form settings

	$tabControl->BeginPrologContent();
	CJSCore::Init(array('date'));

	if(COption::GetOptionString("iblock", "use_htmledit", "Y")=="Y" && $bFileman)
	{
		//TODO:This dirty hack will be replaced by special method like calendar do
		echo '<div style="display:none">';
		CFileMan::AddHTMLEditorFrame(
			"SOME_TEXT",
			"",
			"SOME_TEXT_TYPE",
			"text",
			array(
				'height' => 450,
				'width' => '100%'
			),
			"N",
			0,
			"",
			"",
			$arIBlock["LID"]
		);
		echo '</div>';
	}

	if($arTranslit["TRANSLITERATION"] == "Y")
	{
		CJSCore::Init(array('translit'));
		?>
		<script type="text/javascript">
		var linked=<?if($bLinked) echo 'true'; else echo 'false';?>;
		function set_linked()
		{
			linked=!linked;

			var name_link = document.getElementById('name_link');
			if(name_link)
			{
				if(linked)
					name_link.src='/bitrix/themes/.default/icons/iblock/link.gif';
				else
					name_link.src='/bitrix/themes/.default/icons/iblock/unlink.gif';
			}
			var code_link = document.getElementById('code_link');
			if(code_link)
			{
				if(linked)
					code_link.src='/bitrix/themes/.default/icons/iblock/link.gif';
				else
					code_link.src='/bitrix/themes/.default/icons/iblock/unlink.gif';
			}
			var linked_state = document.getElementById('linked_state');
			if(linked_state)
			{
				if(linked)
					linked_state.value='Y';
				else
					linked_state.value='N';
			}
		}
		var oldValue = '';
		function transliterate()
		{
			if(linked)
			{
				var from = document.getElementById('NAME');
				var to = document.getElementById('CODE');
				if(from && to && oldValue != from.value)
				{
					BX.translit(from.value, {
						'max_len' : <?echo intval($arTranslit['TRANS_LEN'])?>,
						'change_case' : '<?echo $arTranslit['TRANS_CASE']?>',
						'replace_space' : '<?echo $arTranslit['TRANS_SPACE']?>',
						'replace_other' : '<?echo $arTranslit['TRANS_OTHER']?>',
						'delete_repeat_replace' : <?echo $arTranslit['TRANS_EAT'] == 'Y'? 'true': 'false'?>,
						'use_google' : <?echo $arTranslit['USE_GOOGLE'] == 'Y'? 'true': 'false'?>,
						'callback' : function(result){to.value = result; setTimeout('transliterate()', 250); }
					});
					oldValue = from.value;
				}
				else
				{
					setTimeout('transliterate()', 250);
				}
			}
			else
			{
				setTimeout('transliterate()', 250);
			}
		}
		transliterate();
		</script>
		<?
	}
	?>
	<script type="text/javascript">
		var InheritedPropertiesTemplates = new JCInheritedPropertiesTemplates(
			'<?echo $tabControl->GetName()?>_form',
			'/bitrix/admin/iblock_templates.ajax.php?ENTITY_TYPE=E&IBLOCK_ID=<?echo intval($IBLOCK_ID)?>&ENTITY_ID=<?echo intval($ID)?>'
		);
		BX.ready(function(){
			setTimeout(function(){
				InheritedPropertiesTemplates.updateInheritedPropertiesTemplates(true);
			}, 1000);
		});
	</script>
<?
	$tabControl->EndPrologContent();

	$tabControl->BeginEpilogContent();

echo bitrix_sessid_post();
echo GetFilterHiddens("find_");?>
<input type="hidden" name="linked_state" id="linked_state" value="<?if($bLinked) echo 'Y'; else echo 'N';?>">
<input type="hidden" name="Update" value="Y">
<input type="hidden" name="from" value="<?echo htmlspecialcharsbx($from)?>">
<input type="hidden" name="WF" value="<?echo htmlspecialcharsbx($WF)?>">
<input type="hidden" name="return_url" value="<?echo htmlspecialcharsbx($return_url)?>">
<?if($ID>0 && !$bCopy)
{
	?><input type="hidden" name="ID" value="<?echo $ID?>"><?
}
if ($bCopy)
{
	?><input type="hidden" name="copyID" value="<? echo $ID; ?>"><?
}
if ($bCatalog)
	CCatalogAdminTools::showFormParams();
?>
<input type="hidden" name="IBLOCK_SECTION_ID" value="<?echo intval($IBLOCK_SECTION_ID)?>">
<input type="hidden" name="TMP_ID" value="<?echo intval($TMP_ID)?>">
<?
$tabControl->EndEpilogContent();

$customTabber->SetErrorState($bVarsFromForm);

$arEditLinkParams = array(
	"find_section_section" => intval($find_section_section)
);
if ($bAutocomplete)
{
	$arEditLinkParams['lookup'] = $strLookup;
}

$tabControl->Begin(array(
	"FORM_ACTION" => "/bitrix/admin/".CIBlock::GetAdminElementEditLink($IBLOCK_ID, null, $arEditLinkParams)
));

$tabControl->BeginNextFormTab();
	if($ID > 0 && !$bCopy)
	{
		$p = CIblockElement::GetByID($ID);
		$pr = $p->ExtractFields("prn_");
	}
	else
	{
		$pr = array();
	}
$tabControl->AddCheckBoxField("ACTIVE", GetMessage("IBLOCK_FIELD_ACTIVE").":", false, array("Y","N"), $str_ACTIVE=="Y");
$tabControl->BeginCustomField("ACTIVE_FROM", GetMessage("IBLOCK_FIELD_ACTIVE_PERIOD_FROM"), $arIBlock["FIELDS"]["ACTIVE_FROM"]["IS_REQUIRED"] === "Y");
?>
	<tr id="tr_ACTIVE_FROM">
		<td><?echo $tabControl->GetCustomLabelHTML()?>:</td>
		<td><?echo CAdminCalendar::CalendarDate("ACTIVE_FROM", $str_ACTIVE_FROM, 19, true)?></td>
	</tr>
<?
$tabControl->EndCustomField("ACTIVE_FROM", '<input type="hidden" id="ACTIVE_FROM" name="ACTIVE_FROM" value="'.$str_ACTIVE_FROM.'">');
$tabControl->BeginCustomField("ACTIVE_TO", GetMessage("IBLOCK_FIELD_ACTIVE_PERIOD_TO"), $arIBlock["FIELDS"]["ACTIVE_TO"]["IS_REQUIRED"] === "Y");
?>
	<tr id="tr_ACTIVE_TO">
		<td><?echo $tabControl->GetCustomLabelHTML()?>:</td>
		<td><?echo CAdminCalendar::CalendarDate("ACTIVE_TO", $str_ACTIVE_TO, 19, true)?></td>
	</tr>

<?
$tabControl->EndCustomField("ACTIVE_TO", '<input type="hidden" id="ACTIVE_TO" name="ACTIVE_TO" value="'.$str_ACTIVE_TO.'">');

if($arTranslit["TRANSLITERATION"] == "Y")
{
	$tabControl->BeginCustomField("NAME", GetMessage("IBLOCK_FIELD_NAME").":", true);
	?>
		<tr id="tr_NAME">
			<td><?echo $tabControl->GetCustomLabelHTML()?></td>
			<td style="white-space: nowrap;">
				<input type="text" size="50" name="NAME" id="NAME" maxlength="255" value="<?echo $str_NAME?>"><image id="name_link" title="<?echo GetMessage("IBEL_E_LINK_TIP")?>" class="linked" src="/bitrix/themes/.default/icons/iblock/<?if($bLinked) echo 'link.gif'; else echo 'unlink.gif';?>" onclick="set_linked()" />
			</td>
		</tr>
	<?
	$tabControl->EndCustomField("NAME",
		'<input type="hidden" name="NAME" id="NAME" value="'.$str_NAME.'">'
	);

	$tabControl->BeginCustomField("CODE", GetMessage("IBLOCK_FIELD_CODE").":", $arIBlock["FIELDS"]["CODE"]["IS_REQUIRED"] === "Y");
	?>
		<tr id="tr_CODE">
			<td><?echo $tabControl->GetCustomLabelHTML()?></td>
			<td style="white-space: nowrap;">
				<input type="text" size="50" name="CODE" id="CODE" maxlength="255" value="<?echo $str_CODE?>"><image id="code_link" title="<?echo GetMessage("IBEL_E_LINK_TIP")?>" class="linked" src="/bitrix/themes/.default/icons/iblock/<?if($bLinked) echo 'link.gif'; else echo 'unlink.gif';?>" onclick="set_linked()" />
			</td>
		</tr>
	<?
	$tabControl->EndCustomField("CODE",
		'<input type="hidden" name="CODE" id="CODE" value="'.$str_CODE.'">'
	);
}
else
{
	$tabControl->AddEditField("NAME", GetMessage("IBLOCK_FIELD_NAME").":", true, array("size" => 50, "maxlength" => 255), $str_NAME);
	$tabControl->AddEditField("CODE", GetMessage("IBLOCK_FIELD_CODE").":", $arIBlock["FIELDS"]["CODE"]["IS_REQUIRED"] === "Y", array("size" => 20, "maxlength" => 255), $str_CODE);
}

if(COption::GetOptionString("iblock", "show_xml_id", "N")=="Y")
	$tabControl->AddEditField("XML_ID", GetMessage("IBLOCK_FIELD_XML_ID").":", $arIBlock["FIELDS"]["XML_ID"]["IS_REQUIRED"] === "Y", array("size" => 20, "maxlength" => 255), $str_XML_ID);

$tabControl->AddEditField("SORT", GetMessage("IBLOCK_FIELD_SORT").":", $arIBlock["FIELDS"]["SORT"]["IS_REQUIRED"] === "Y", array("size" => 7, "maxlength" => 10), $str_SORT);

if(!empty($PROP)):
	if ($arIBlock["SECTION_PROPERTY"] === "Y" || defined("CATALOG_PRODUCT"))
	{
		$arPropLinks = array("IBLOCK_ELEMENT_PROP_VALUE");
		if(is_array($str_IBLOCK_ELEMENT_SECTION) && !empty($str_IBLOCK_ELEMENT_SECTION))
		{
			foreach($str_IBLOCK_ELEMENT_SECTION as $section_id)
			{
				foreach(CIBlockSectionPropertyLink::GetArray($IBLOCK_ID, $section_id) as $PID => $arLink)
					$arPropLinks[$PID] = "PROPERTY_".$PID;
			}
		}
		else
		{
			foreach(CIBlockSectionPropertyLink::GetArray($IBLOCK_ID, 0) as $PID => $arLink)
				$arPropLinks[$PID] = "PROPERTY_".$PID;
		}
		$tabControl->AddFieldGroup("IBLOCK_ELEMENT_PROPERTY", GetMessage("IBLOCK_ELEMENT_PROP_VALUE"), $arPropLinks, $bPropertyAjax);
	}

	$tabControl->AddSection("IBLOCK_ELEMENT_PROP_VALUE", GetMessage("IBLOCK_ELEMENT_PROP_VALUE"));

	foreach($PROP as $prop_code=>$prop_fields):
		$prop_values = $prop_fields["VALUE"];
		$tabControl->BeginCustomField("PROPERTY_".$prop_fields["ID"], $prop_fields["NAME"], $prop_fields["IS_REQUIRED"]==="Y");
		?>
		<tr id="tr_PROPERTY_<?echo $prop_fields["ID"];?>"<?if ($prop_fields["PROPERTY_TYPE"]=="F"):?> class="adm-detail-file-row"<?endif?>>
			<td class="adm-detail-valign-top" width="40%"><?if($prop_fields["HINT"]!=""):
				?><span id="hint_<?echo $prop_fields["ID"];?>"></span><script type="text/javascript">BX.hint_replace(BX('hint_<?echo $prop_fields["ID"];?>'), '<?echo CUtil::JSEscape($prop_fields["HINT"])?>');</script>&nbsp;<?
			endif;?><?echo $tabControl->GetCustomLabelHTML();?>:</td>
			<td width="60%"><?_ShowPropertyField('PROP['.$prop_fields["ID"].']', $prop_fields, $prop_fields["VALUE"], (($historyId <= 0) && (!$bVarsFromForm) && ($ID<=0)), $bVarsFromForm||$bPropertyAjax, 50000, $tabControl->GetFormName(), $bCopy);?></td>
		</tr>
		<?
			$hidden = "";
			if(!is_array($prop_fields["~VALUE"]))
				$values = Array();
			else
				$values = $prop_fields["~VALUE"];
			$start = 1;
			foreach($values as $key=>$val)
			{
				if($bCopy)
				{
					$key = "n".$start;
					$start++;
				}

				if(is_array($val) && array_key_exists("VALUE",$val))
				{
					$hidden .= _ShowHiddenValue('PROP['.$prop_fields["ID"].']['.$key.'][VALUE]', $val["VALUE"]);
					$hidden .= _ShowHiddenValue('PROP['.$prop_fields["ID"].']['.$key.'][DESCRIPTION]', $val["DESCRIPTION"]);
				}
				else
				{
					$hidden .= _ShowHiddenValue('PROP['.$prop_fields["ID"].']['.$key.'][VALUE]', $val);
					$hidden .= _ShowHiddenValue('PROP['.$prop_fields["ID"].']['.$key.'][DESCRIPTION]', "");
				}
			}
		$tabControl->EndCustomField("PROPERTY_".$prop_fields["ID"], $hidden);
	endforeach;?>
<?endif;

	if (!$bAutocomplete)
	{
		$rsLinkedProps = CIBlockProperty::GetList(array(), array(
			"PROPERTY_TYPE" => "E",
			"LINK_IBLOCK_ID" => $IBLOCK_ID,
			"ACTIVE" => "Y",
			"FILTRABLE" => "Y",
		));
		$arLinkedProp = $rsLinkedProps->GetNext();
		if($arLinkedProp)
		{
			$tabControl->BeginCustomField("LINKED_PROP", GetMessage("IBLOCK_ELEMENT_EDIT_LINKED"));
			?>
			<tr class="heading" id="tr_LINKED_PROP">
				<td colspan="2"><?echo $tabControl->GetCustomLabelHTML();?></td>
			</tr>
			<?
			do {
				$elements_name = CIBlock::GetArrayByID($arLinkedProp["IBLOCK_ID"], "ELEMENTS_NAME");
				if(strlen($elements_name) <= 0)
					$elements_name = GetMessage("IBLOCK_ELEMENT_EDIT_ELEMENTS");
			?>
			<tr id="tr_LINKED_PROP<?echo $arLinkedProp["ID"]?>">
				<td colspan="2"><a href="<?echo htmlspecialcharsbx(CIBlock::GetAdminElementListLink($arLinkedProp["IBLOCK_ID"], array('set_filter'=>'Y', 'find_el_property_'.$arLinkedProp["ID"]=>$ID, 'find_section_section' => -1)))?>"><?echo CIBlock::GetArrayByID($arLinkedProp["IBLOCK_ID"], "NAME").": ".$elements_name?></a></td>
			</tr>
			<?
			} while ($arLinkedProp = $rsLinkedProps->GetNext());
			$tabControl->EndCustomField("LINKED_PROP", "");
		}
	}

$tabControl->BeginNextFormTab();
$tabControl->BeginCustomField("PREVIEW_PICTURE", GetMessage("IBLOCK_FIELD_PREVIEW_PICTURE"), $arIBlock["FIELDS"]["PREVIEW_PICTURE"]["IS_REQUIRED"] === "Y");
if($bVarsFromForm && !array_key_exists("PREVIEW_PICTURE", $_REQUEST) && $arElement)
	$str_PREVIEW_PICTURE = intval($arElement["PREVIEW_PICTURE"]);
?>
	<tr id="tr_PREVIEW_PICTURE" class="adm-detail-file-row">
		<td width="40%" class="adm-detail-valign-top"><?echo $tabControl->GetCustomLabelHTML()?>:</td>
		<td width="60%">
			<?if($historyId > 0):?>
				<?echo CFileInput::Show("PREVIEW_PICTURE", $str_PREVIEW_PICTURE, array(
					"IMAGE" => "Y",
					"PATH" => "Y",
					"FILE_SIZE" => "Y",
					"DIMENSIONS" => "Y",
					"IMAGE_POPUP" => "Y",
					"MAX_SIZE" => array(
						"W" => COption::GetOptionString("iblock", "detail_image_size"),
						"H" => COption::GetOptionString("iblock", "detail_image_size"),
					),
				));
				?>
			<?else:?>
				<?echo CFileInput::Show("PREVIEW_PICTURE", ($ID > 0 && !$bCopy? $str_PREVIEW_PICTURE: 0),
					array(
						"IMAGE" => "Y",
						"PATH" => "Y",
						"FILE_SIZE" => "Y",
						"DIMENSIONS" => "Y",
						"IMAGE_POPUP" => "Y",
						"MAX_SIZE" => array(
							"W" => COption::GetOptionString("iblock", "detail_image_size"),
							"H" => COption::GetOptionString("iblock", "detail_image_size"),
						),
					), array(
						'upload' => true,
						'medialib' => true,
						'file_dialog' => true,
						'cloud' => true,
						'del' => true,
						'description' => true,
					)
				);
				?>
			<?endif?>
		</td>
	</tr>
<?
$tabControl->EndCustomField("PREVIEW_PICTURE", "");
$tabControl->BeginCustomField("PREVIEW_TEXT", GetMessage("IBLOCK_FIELD_PREVIEW_TEXT"), $arIBlock["FIELDS"]["PREVIEW_TEXT"]["IS_REQUIRED"] === "Y");
?>
	<tr class="heading" id="tr_PREVIEW_TEXT_LABEL">
		<td colspan="2"><?echo $tabControl->GetCustomLabelHTML()?></td>
	</tr>
	<?if($ID && $PREV_ID && $bWorkflow):?>
	<tr id="tr_PREVIEW_TEXT_DIFF">
		<td colspan="2">
			<div style="width:95%;background-color:white;border:1px solid black;padding:5px">
				<?echo getDiff($prev_arElement["PREVIEW_TEXT"], $arElement["PREVIEW_TEXT"])?>
			</div>
		</td>
	</tr>
	<?elseif(COption::GetOptionString("iblock", "use_htmledit", "Y")=="Y" && $bFileman):?>
	<tr id="tr_PREVIEW_TEXT_EDITOR">
		<td colspan="2" align="center">
			<?CFileMan::AddHTMLEditorFrame(
			"PREVIEW_TEXT",
			$str_PREVIEW_TEXT,
			"PREVIEW_TEXT_TYPE",
			$str_PREVIEW_TEXT_TYPE,
			array(
				'height' => 450,
				'width' => '100%'
			),
			"N",
			0,
			"",
			"",
			$arIBlock["LID"],
			true,
			false,
			array(
				'toolbarConfig' => CFileman::GetEditorToolbarConfig("iblock_".(defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1 ? 'public' : 'admin')),
				'saveEditorKey' => $IBLOCK_ID
			)
			);?>
		</td>
	</tr>
	<?else:?>
	<tr id="tr_PREVIEW_TEXT_TYPE">
		<td><?echo GetMessage("IBLOCK_DESC_TYPE")?></td>
		<td><input type="radio" name="PREVIEW_TEXT_TYPE" id="PREVIEW_TEXT_TYPE_text" value="text"<?if($str_PREVIEW_TEXT_TYPE!="html")echo " checked"?>> <label for="PREVIEW_TEXT_TYPE_text"><?echo GetMessage("IBLOCK_DESC_TYPE_TEXT")?></label> / <input type="radio" name="PREVIEW_TEXT_TYPE" id="PREVIEW_TEXT_TYPE_html" value="html"<?if($str_PREVIEW_TEXT_TYPE=="html")echo " checked"?>> <label for="PREVIEW_TEXT_TYPE_html"><?echo GetMessage("IBLOCK_DESC_TYPE_HTML")?></label></td>
	</tr>
	<tr id="tr_PREVIEW_TEXT">
		<td colspan="2" align="center">
			<textarea cols="60" rows="10" name="PREVIEW_TEXT" style="width:100%"><?echo $str_PREVIEW_TEXT?></textarea>
		</td>
	</tr>
	<?endif;
$tabControl->EndCustomField("PREVIEW_TEXT",
	'<input type="hidden" name="PREVIEW_TEXT" value="'.$str_PREVIEW_TEXT.'">'.
	'<input type="hidden" name="PREVIEW_TEXT_TYPE" value="'.$str_PREVIEW_TEXT_TYPE.'">'
);
$tabControl->BeginNextFormTab();
$tabControl->BeginCustomField("DETAIL_PICTURE", GetMessage("IBLOCK_FIELD_DETAIL_PICTURE"), $arIBlock["FIELDS"]["DETAIL_PICTURE"]["IS_REQUIRED"] === "Y");
if($bVarsFromForm && !array_key_exists("DETAIL_PICTURE", $_REQUEST) && $arElement)
	$str_DETAIL_PICTURE = intval($arElement["DETAIL_PICTURE"]);
?>
	<tr id="tr_DETAIL_PICTURE" class="adm-detail-file-row">
		<td width="40%" class="adm-detail-valign-top"><?echo $tabControl->GetCustomLabelHTML()?>:</td>
		<td width="60%">
			<?if($historyId > 0):?>
				<?echo CFileInput::Show("DETAIL_PICTURE", $str_DETAIL_PICTURE, array(
					"IMAGE" => "Y",
					"PATH" => "Y",
					"FILE_SIZE" => "Y",
					"DIMENSIONS" => "Y",
					"IMAGE_POPUP" => "Y",
					"MAX_SIZE" => array(
						"W" => COption::GetOptionString("iblock", "detail_image_size"),
						"H" => COption::GetOptionString("iblock", "detail_image_size"),
					),
				));
				?>
			<?else:?>
				<?echo CFileInput::Show("DETAIL_PICTURE", ($ID > 0 && !$bCopy? $str_DETAIL_PICTURE: 0),
					array(
						"IMAGE" => "Y",
						"PATH" => "Y",
						"FILE_SIZE" => "Y",
						"DIMENSIONS" => "Y",
						"IMAGE_POPUP" => "Y",
						"MAX_SIZE" => array(
							"W" => COption::GetOptionString("iblock", "detail_image_size"),
							"H" => COption::GetOptionString("iblock", "detail_image_size"),
						),
					), array(
						'upload' => true,
						'medialib' => true,
						'file_dialog' => true,
						'cloud' => true,
						'del' => true,
						'description' => true,
					)
				);
				?>
			<?endif?>
		</td>
	</tr>
<?
$tabControl->EndCustomField("DETAIL_PICTURE", "");
$tabControl->BeginCustomField("DETAIL_TEXT", GetMessage("IBLOCK_FIELD_DETAIL_TEXT"), $arIBlock["FIELDS"]["DETAIL_TEXT"]["IS_REQUIRED"] === "Y");
?>
	<tr class="heading" id="tr_DETAIL_TEXT_LABEL">
		<td colspan="2"><?echo $tabControl->GetCustomLabelHTML()?></td>
	</tr>
	<?if($ID && $PREV_ID && $bWorkflow):?>
	<tr id="tr_DETAIL_TEXT_DIFF">
		<td colspan="2">
			<div style="width:95%;background-color:white;border:1px solid black;padding:5px">
				<?echo getDiff($prev_arElement["DETAIL_TEXT"], $arElement["DETAIL_TEXT"])?>
			</div>
		</td>
	</tr>
	<?elseif(COption::GetOptionString("iblock", "use_htmledit", "Y")=="Y" && $bFileman):?>
	<tr id="tr_DETAIL_TEXT_EDITOR">
		<td colspan="2" align="center">
			<?CFileMan::AddHTMLEditorFrame(
				"DETAIL_TEXT",
				$str_DETAIL_TEXT,
				"DETAIL_TEXT_TYPE",
				$str_DETAIL_TEXT_TYPE,
				array(
					'height' => 450,
					'width' => '100%'
				),
				"N",
				0,
				"",
				"",
				$arIBlock["LID"],
				true,
				false,
				array('toolbarConfig' => CFileman::GetEditorToolbarConfig("iblock_".(defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1 ? 'public' : 'admin')), 'saveEditorKey' => $IBLOCK_ID)
			);
		?></td>
	</tr>
	<?else:?>
	<tr id="tr_DETAIL_TEXT_TYPE">
		<td><?echo GetMessage("IBLOCK_DESC_TYPE")?></td>
		<td><input type="radio" name="DETAIL_TEXT_TYPE" id="DETAIL_TEXT_TYPE_text" value="text"<?if($str_DETAIL_TEXT_TYPE!="html")echo " checked"?>> <label for="DETAIL_TEXT_TYPE_text"><?echo GetMessage("IBLOCK_DESC_TYPE_TEXT")?></label> / <input type="radio" name="DETAIL_TEXT_TYPE" id="DETAIL_TEXT_TYPE_html" value="html"<?if($str_DETAIL_TEXT_TYPE=="html")echo " checked"?>> <label for="DETAIL_TEXT_TYPE_html"><?echo GetMessage("IBLOCK_DESC_TYPE_HTML")?></label></td>
	</tr>
	<tr id="tr_DETAIL_TEXT">
		<td colspan="2" align="center">
			<textarea cols="60" rows="20" name="DETAIL_TEXT" style="width:100%"><?echo $str_DETAIL_TEXT?></textarea>
		</td>
	</tr>
	<?endif?>
<?
$tabControl->EndCustomField("DETAIL_TEXT",
	'<input type="hidden" name="DETAIL_TEXT" value="'.$str_DETAIL_TEXT.'">'.
	'<input type="hidden" name="DETAIL_TEXT_TYPE" value="'.$str_DETAIL_TEXT_TYPE.'">'
);
	$tabControl->BeginNextFormTab();
	?>
	<?
	$tabControl->BeginCustomField("IPROPERTY_TEMPLATES_ELEMENT_META_TITLE", GetMessage("IBEL_E_SEO_META_TITLE"));
	?>
	<tr class="adm-detail-valign-top">
		<td width="40%"><?echo $tabControl->GetCustomLabelHTML()?></td>
		<td width="60%"><?echo IBlockInheritedPropertyInput($IBLOCK_ID, "ELEMENT_META_TITLE", $str_IPROPERTY_TEMPLATES, "E", GetMessage("IBEL_E_SEO_OVERWRITE"))?></td>
	</tr>
	<?
	$tabControl->EndCustomField("IPROPERTY_TEMPLATES_ELEMENT_META_TITLE",
		IBlockInheritedPropertyHidden($IBLOCK_ID, "ELEMENT_META_TITLE", $str_IPROPERTY_TEMPLATES, "E", GetMessage("IBEL_E_SEO_OVERWRITE"))
	);
	?>
	<?
	$tabControl->BeginCustomField("IPROPERTY_TEMPLATES_ELEMENT_META_KEYWORDS", GetMessage("IBEL_E_SEO_META_KEYWORDS"));
	?>
	<tr class="adm-detail-valign-top">
		<td width="40%"><?echo $tabControl->GetCustomLabelHTML()?></td>
		<td width="60%"><?echo IBlockInheritedPropertyInput($IBLOCK_ID, "ELEMENT_META_KEYWORDS", $str_IPROPERTY_TEMPLATES, "E", GetMessage("IBEL_E_SEO_OVERWRITE"))?></td>
	</tr>
	<?
	$tabControl->EndCustomField("IPROPERTY_TEMPLATES_ELEMENT_META_KEYWORDS",
		IBlockInheritedPropertyHidden($IBLOCK_ID, "ELEMENT_META_KEYWORDS", $str_IPROPERTY_TEMPLATES, "E", GetMessage("IBEL_E_SEO_OVERWRITE"))
	);
	?>
	<?
	$tabControl->BeginCustomField("IPROPERTY_TEMPLATES_ELEMENT_META_DESCRIPTION", GetMessage("IBEL_E_SEO_META_DESCRIPTION"));
	?>
	<tr class="adm-detail-valign-top">
		<td width="40%"><?echo $tabControl->GetCustomLabelHTML()?></td>
		<td width="60%"><?echo IBlockInheritedPropertyInput($IBLOCK_ID, "ELEMENT_META_DESCRIPTION", $str_IPROPERTY_TEMPLATES, "E", GetMessage("IBEL_E_SEO_OVERWRITE"))?></td>
	</tr>
	<?
	$tabControl->EndCustomField("IPROPERTY_TEMPLATES_ELEMENT_META_DESCRIPTION",
		IBlockInheritedPropertyHidden($IBLOCK_ID, "ELEMENT_META_DESCRIPTION", $str_IPROPERTY_TEMPLATES, "E", GetMessage("IBEL_E_SEO_OVERWRITE"))
	);
	?>
	<?
	$tabControl->BeginCustomField("IPROPERTY_TEMPLATES_ELEMENT_PAGE_TITLE", GetMessage("IBEL_E_SEO_ELEMENT_TITLE"));
	?>
	<tr class="adm-detail-valign-top">
		<td width="40%"><?echo $tabControl->GetCustomLabelHTML()?></td>
		<td width="60%"><?echo IBlockInheritedPropertyInput($IBLOCK_ID, "ELEMENT_PAGE_TITLE", $str_IPROPERTY_TEMPLATES, "E", GetMessage("IBEL_E_SEO_OVERWRITE"))?></td>
	</tr>
	<?
	$tabControl->EndCustomField("IPROPERTY_TEMPLATES_ELEMENT_PAGE_TITLE",
		IBlockInheritedPropertyHidden($IBLOCK_ID, "ELEMENT_PAGE_TITLE", $str_IPROPERTY_TEMPLATES, "E", GetMessage("IBEL_E_SEO_OVERWRITE"))
	);
	?>
	<?
	$tabControl->AddSection("IPROPERTY_TEMPLATES_ELEMENTS_PREVIEW_PICTURE", GetMessage("IBEL_E_SEO_FOR_ELEMENTS_PREVIEW_PICTURE"));
	$tabControl->BeginCustomField("IPROPERTY_TEMPLATES_ELEMENT_PREVIEW_PICTURE_FILE_ALT", GetMessage("IBEL_E_SEO_FILE_ALT"));
	?>
	<tr class="adm-detail-valign-top">
		<td width="40%"><?echo $tabControl->GetCustomLabelHTML()?></td>
		<td width="60%"><?echo IBlockInheritedPropertyInput($IBLOCK_ID, "ELEMENT_PREVIEW_PICTURE_FILE_ALT", $str_IPROPERTY_TEMPLATES, "E", GetMessage("IBEL_E_SEO_OVERWRITE"))?></td>
	</tr>
	<?
	$tabControl->EndCustomField("IPROPERTY_TEMPLATES_ELEMENT_PREVIEW_PICTURE_FILE_ALT",
		IBlockInheritedPropertyHidden($IBLOCK_ID, "ELEMENT_PREVIEW_PICTURE_FILE_ALT", $str_IPROPERTY_TEMPLATES, "E", GetMessage("IBEL_E_SEO_OVERWRITE"))
	);
	?>
	<?
	$tabControl->BeginCustomField("IPROPERTY_TEMPLATES_ELEMENT_PREVIEW_PICTURE_FILE_TITLE", GetMessage("IBEL_E_SEO_FILE_TITLE"));
	?>
	<tr class="adm-detail-valign-top">
		<td width="40%"><?echo $tabControl->GetCustomLabelHTML()?></td>
		<td width="60%"><?echo IBlockInheritedPropertyInput($IBLOCK_ID, "ELEMENT_PREVIEW_PICTURE_FILE_TITLE", $str_IPROPERTY_TEMPLATES, "E", GetMessage("IBEL_E_SEO_OVERWRITE"))?></td>
	</tr>
	<?
	$tabControl->EndCustomField("IPROPERTY_TEMPLATES_ELEMENT_PREVIEW_PICTURE_FILE_TITLE",
		IBlockInheritedPropertyHidden($IBLOCK_ID, "ELEMENT_PREVIEW_PICTURE_FILE_TITLE", $str_IPROPERTY_TEMPLATES, "E", GetMessage("IBEL_E_SEO_OVERWRITE"))
	);
	?>
	<?
	$tabControl->BeginCustomField("IPROPERTY_TEMPLATES_ELEMENT_PREVIEW_PICTURE_FILE_NAME", GetMessage("IBEL_E_SEO_FILE_NAME"));
	?>
	<tr class="adm-detail-valign-top">
		<td width="40%"><?echo $tabControl->GetCustomLabelHTML()?></td>
		<td width="60%"><?echo IBlockInheritedPropertyInput($IBLOCK_ID, "ELEMENT_PREVIEW_PICTURE_FILE_NAME", $str_IPROPERTY_TEMPLATES, "E", GetMessage("IBEL_E_SEO_OVERWRITE"))?></td>
	</tr>
	<?
	$tabControl->EndCustomField("IPROPERTY_TEMPLATES_ELEMENT_PREVIEW_PICTURE_FILE_NAME",
		IBlockInheritedPropertyHidden($IBLOCK_ID, "ELEMENT_PREVIEW_PICTURE_FILE_NAME", $str_IPROPERTY_TEMPLATES, "E", GetMessage("IBEL_E_SEO_OVERWRITE"))
	);
	?>
	<?
	$tabControl->AddSection("IPROPERTY_TEMPLATES_ELEMENTS_DETAIL_PICTURE", GetMessage("IBEL_E_SEO_FOR_ELEMENTS_DETAIL_PICTURE"));
	$tabControl->BeginCustomField("IPROPERTY_TEMPLATES_ELEMENT_DETAIL_PICTURE_FILE_ALT", GetMessage("IBEL_E_SEO_FILE_ALT"));
	?>
	<tr class="adm-detail-valign-top">
		<td width="40%"><?echo $tabControl->GetCustomLabelHTML()?></td>
		<td width="60%"><?echo IBlockInheritedPropertyInput($IBLOCK_ID, "ELEMENT_DETAIL_PICTURE_FILE_ALT", $str_IPROPERTY_TEMPLATES, "E", GetMessage("IBEL_E_SEO_OVERWRITE"))?></td>
	</tr>
	<?
	$tabControl->EndCustomField("IPROPERTY_TEMPLATES_ELEMENT_DETAIL_PICTURE_FILE_ALT",
		IBlockInheritedPropertyHidden($IBLOCK_ID, "ELEMENT_DETAIL_PICTURE_FILE_ALT", $str_IPROPERTY_TEMPLATES, "E", GetMessage("IBEL_E_SEO_OVERWRITE"))
	);
	?>
	<?
	$tabControl->BeginCustomField("IPROPERTY_TEMPLATES_ELEMENT_DETAIL_PICTURE_FILE_TITLE", GetMessage("IBEL_E_SEO_FILE_TITLE"));
	?>
	<tr class="adm-detail-valign-top">
		<td width="40%"><?echo $tabControl->GetCustomLabelHTML()?></td>
		<td width="60%"><?echo IBlockInheritedPropertyInput($IBLOCK_ID, "ELEMENT_DETAIL_PICTURE_FILE_TITLE", $str_IPROPERTY_TEMPLATES, "E", GetMessage("IBEL_E_SEO_OVERWRITE"))?></td>
	</tr>
	<?
	$tabControl->EndCustomField("IPROPERTY_TEMPLATES_ELEMENT_DETAIL_PICTURE_FILE_TITLE",
		IBlockInheritedPropertyHidden($IBLOCK_ID, "ELEMENT_DETAIL_PICTURE_FILE_TITLE", $str_IPROPERTY_TEMPLATES, "E", GetMessage("IBEL_E_SEO_OVERWRITE"))
	);
	?>
	<?
	$tabControl->BeginCustomField("IPROPERTY_TEMPLATES_ELEMENT_DETAIL_PICTURE_FILE_NAME", GetMessage("IBEL_E_SEO_FILE_NAME"));
	?>
	<tr class="adm-detail-valign-top">
		<td width="40%"><?echo $tabControl->GetCustomLabelHTML()?></td>
		<td width="60%"><?echo IBlockInheritedPropertyInput($IBLOCK_ID, "ELEMENT_DETAIL_PICTURE_FILE_NAME", $str_IPROPERTY_TEMPLATES, "E", GetMessage("IBEL_E_SEO_OVERWRITE"))?></td>
	</tr>
	<?
	$tabControl->EndCustomField("IPROPERTY_TEMPLATES_ELEMENT_DETAIL_PICTURE_FILE_NAME",
		IBlockInheritedPropertyHidden($IBLOCK_ID, "ELEMENT_DETAIL_PICTURE_FILE_NAME", $str_IPROPERTY_TEMPLATES, "E", GetMessage("IBEL_E_SEO_OVERWRITE"))
	);
	?>
	<?
	$tabControl->AddSection("SEO_ADDITIONAL", GetMessage("IBLOCK_EL_TAB_MO"));
	$tabControl->BeginCustomField("TAGS", GetMessage("IBLOCK_FIELD_TAGS").":", $arIBlock["FIELDS"]["TAGS"]["IS_REQUIRED"] === "Y");
	?>
		<tr id="tr_TAGS">
			<td><?echo $tabControl->GetCustomLabelHTML()?><br><?echo GetMessage("IBLOCK_ELEMENT_EDIT_TAGS_TIP")?></td>
			<td>
				<?if(CModule::IncludeModule('search')):
					$arLID = array();
					$rsSites = CIBlock::GetSite($IBLOCK_ID);
					while($arSite = $rsSites->Fetch())
						$arLID[] = $arSite["LID"];
					echo InputTags("TAGS", htmlspecialcharsback($str_TAGS), $arLID, 'size="55"');
				else:?>
					<input type="text" size="20" name="TAGS" maxlength="255" value="<?echo $str_TAGS?>">
				<?endif?>
			</td>
		</tr>
	<?
	$tabControl->EndCustomField("TAGS",
		'<input type="hidden" name="TAGS" value="'.$str_TAGS.'">'
	);

	?>

<?if($arShowTabs['sections']):
	$tabControl->BeginNextFormTab();

	$tabControl->BeginCustomField("SECTIONS", GetMessage("IBLOCK_SECTION"), $arIBlock["FIELDS"]["IBLOCK_SECTION"]["IS_REQUIRED"] === "Y");
	?>
	<tr id="tr_SECTIONS">
	<?if($arIBlock["SECTION_CHOOSER"] != "D" && $arIBlock["SECTION_CHOOSER"] != "P"):?>

		<?$l = CIBlockSection::GetTreeList(Array("IBLOCK_ID"=>$IBLOCK_ID), array("ID", "NAME", "DEPTH_LEVEL"));?>
		<td width="40%" class="adm-detail-valign-top"><?echo $tabControl->GetCustomLabelHTML()?></td>
		<td width="60%">
		<select name="IBLOCK_SECTION[]" size="14" multiple onchange="onSectionChanged()">
			<option value="0"<?if(is_array($str_IBLOCK_ELEMENT_SECTION) && in_array(0, $str_IBLOCK_ELEMENT_SECTION))echo " selected"?>><?echo GetMessage("IBLOCK_UPPER_LEVEL")?></option>
		<?
			while($ar_l = $l->GetNext()):
				?><option value="<?echo $ar_l["ID"]?>"<?if(is_array($str_IBLOCK_ELEMENT_SECTION) && in_array($ar_l["ID"], $str_IBLOCK_ELEMENT_SECTION))echo " selected"?>><?echo str_repeat(" . ", $ar_l["DEPTH_LEVEL"])?><?echo $ar_l["NAME"]?></option><?
			endwhile;
		?>
		</select>
		</td>

	<?elseif($arIBlock["SECTION_CHOOSER"] == "D"):?>
		<td width="40%" class="adm-detail-valign-top"><?echo $tabControl->GetCustomLabelHTML()?></td>
		<td width="60%">
			<table class="internal" id="sections">
			<?
			if(is_array($str_IBLOCK_ELEMENT_SECTION))
			{
				$i = 0;
				foreach($str_IBLOCK_ELEMENT_SECTION as $section_id)
				{
					$rsChain = CIBlockSection::GetNavChain($IBLOCK_ID, $section_id);
					$strPath = "";
					while($arChain = $rsChain->Fetch())
						$strPath .= $arChain["NAME"]."&nbsp;/&nbsp;";
					if(strlen($strPath) > 0)
					{
						?><tr>
							<td nowrap><?echo $strPath?></td>
							<td>
							<input type="button" value="<?echo GetMessage("MAIN_DELETE")?>" OnClick="deleteRow(this)">
							<input type="hidden" name="IBLOCK_SECTION[]" value="<?echo intval($section_id)?>">
							</td>
						</tr><?
					}
					$i++;
				}
			}
			?>
			<tr>
				<td>
				<script type="text/javascript">
				function deleteRow(button)
				{
					var my_row = button.parentNode.parentNode;
					var table = document.getElementById('sections');
					if(table)
					{
						for(var i=0; i<table.rows.length; i++)
						{
							if(table.rows[i] == my_row)
							{
								table.deleteRow(i);
								onSectionChanged();
							}
						}
					}
				}
				function addPathRow()
				{
					var table = document.getElementById('sections');
					if(table)
					{
						var section_id = 0;
						var html = '';
						var lev = 0;
						var oSelect;
						while(oSelect = document.getElementById('select_IBLOCK_SECTION_'+lev))
						{
							if(oSelect.value < 1)
								break;
							html += oSelect.options[oSelect.selectedIndex].text+'&nbsp;/&nbsp;';
							section_id = oSelect.value;
							lev++;
						}
						if(section_id > 0)
						{
							var cnt = table.rows.length;
							var oRow = table.insertRow(cnt-1);

							var i=0;
							var oCell = oRow.insertCell(i++);
							oCell.innerHTML = html;

							var oCell = oRow.insertCell(i++);
							oCell.innerHTML =
								'<input type="button" value="<?echo GetMessage("MAIN_DELETE")?>" OnClick="deleteRow(this)">'+
								'<input type="hidden" name="IBLOCK_SECTION[]" value="'+section_id+'">';
							onSectionChanged();
						}
					}
				}
				function find_path(item, value)
				{
					if(item.id==value)
					{
						var a = Array(1);
						a[0] = item.id;
						return a;
					}
					else
					{
						for(var s in item.children)
						{
							if(ar = find_path(item.children[s], value))
							{
								var a = Array(1);
								a[0] = item.id;
								return a.concat(ar);
							}
						}
						return null;
					}
				}
				function find_children(level, value, item)
				{
					if(level==-1 && item.id==value)
						return item;
					else
					{
						for(var s in item.children)
						{
							if(ch = find_children(level-1,value,item.children[s]))
								return ch;
						}
						return null;
					}
				}
				function change_selection(name_prefix, prop_id,value,level,id)
				{
					var lev = level+1;
					var oSelect;

					while(oSelect = document.getElementById(name_prefix+lev))
					{
						jsSelectUtils.deleteAllOptions(oSelect);
						jsSelectUtils.addNewOption(oSelect, '0', '(<?echo GetMessage("MAIN_NO")?>)');
						lev++;
					}

					oSelect = document.getElementById(name_prefix+(level+1))
					if(oSelect && (value!=0||level==-1))
					{
						var item = find_children(level,value,window['sectionListsFor'+prop_id]);
						for(var s in item.children)
						{
							var obj = item.children[s];
							jsSelectUtils.addNewOption(oSelect, obj.id, obj.name, true);
						}
					}
					if(document.getElementById(id))
						document.getElementById(id).value = value;
				}
				function init_selection(name_prefix, prop_id, value, id)
				{
					var a = find_path(window['sectionListsFor'+prop_id], value);
					change_selection(name_prefix, prop_id, 0, -1, id);
					for(var i=1;i<a.length;i++)
					{
						if(oSelect = document.getElementById(name_prefix+(i-1)))
						{
							for(var j=0;j<oSelect.length;j++)
							{
								if(oSelect[j].value==a[i])
								{
									oSelect[j].selected=true;
									break;
								}
							}
						}
						change_selection(name_prefix, prop_id, a[i], i-1, id);
					}
				}
				var sectionListsFor0 = {id:0,name:'',children:Array()};

				<?
				$rsItems = CIBlockSection::GetTreeList(Array("IBLOCK_ID"=>$IBLOCK_ID), array("ID", "NAME", "DEPTH_LEVEL"));
				$depth = 0;
				$max_depth = 0;
				$arChain = array();
				while($arItem = $rsItems->GetNext())
				{
					if($max_depth < $arItem["DEPTH_LEVEL"])
					{
						$max_depth = $arItem["DEPTH_LEVEL"];
					}
					if($depth < $arItem["DEPTH_LEVEL"])
					{
						$arChain[]=$arItem["ID"];

					}
					while($depth > $arItem["DEPTH_LEVEL"])
					{
						array_pop($arChain);
						$depth--;
					}
					$arChain[count($arChain)-1] = $arItem["ID"];
					echo "sectionListsFor0";
					foreach($arChain as $i)
						echo ".children['".intval($i)."']";

					echo " = { id : ".$arItem["ID"].", name : '".CUtil::JSEscape($arItem["NAME"])."', children : Array() };\n";
					$depth = $arItem["DEPTH_LEVEL"];
				}
				?>
				</script>
				<?
				for($i = 0; $i < $max_depth; $i++)
					echo '<select id="select_IBLOCK_SECTION_'.$i.'" onchange="change_selection(\'select_IBLOCK_SECTION_\',  0, this.value, '.$i.', \'IBLOCK_SECTION[n'.$key.']\')"><option value="0">('.GetMessage("MAIN_NO").')</option></select>&nbsp;';
				?>
				<script type="text/javascript">
					init_selection('select_IBLOCK_SECTION_', 0, '', 0);
				</script>
				</td>
				<td><input type="button" value="<?echo GetMessage("IBLOCK_ELEMENT_EDIT_PROP_ADD")?>" onClick="addPathRow()"></td>
			</tr>
			</table>
		</td>

	<?else:?>
		<td width="40%" class="adm-detail-valign-top"><?echo $tabControl->GetCustomLabelHTML()?></td>
		<td width="60%">
			<table id="sections" class="internal">
			<?
			if(is_array($str_IBLOCK_ELEMENT_SECTION))
			{
				$i = 0;
				foreach($str_IBLOCK_ELEMENT_SECTION as $section_id)
				{
					$rsChain = CIBlockSection::GetNavChain($IBLOCK_ID, $section_id);
					$strPath = "";
					while($arChain = $rsChain->GetNext())
						$strPath .= $arChain["NAME"]."&nbsp;/&nbsp;";
					if(strlen($strPath) > 0)
					{
						?><tr>
							<td><?echo $strPath?></td>
							<td>
							<input type="button" value="<?echo GetMessage("MAIN_DELETE")?>" OnClick="deleteRow(this)">
							<input type="hidden" name="IBLOCK_SECTION[]" value="<?echo intval($section_id)?>">
							</td>
						</tr><?
					}
					$i++;
				}
			}
			?>
			</table>
				<script type="text/javascript">
				function deleteRow(button)
				{
					var my_row = button.parentNode.parentNode;
					var table = document.getElementById('sections');
					if(table)
					{
						for(var i=0; i<table.rows.length; i++)
						{
							if(table.rows[i] == my_row)
							{
								table.deleteRow(i);
								onSectionChanged();
							}
						}
					}
				}
				function InS<?echo md5("input_IBLOCK_SECTION")?>(section_id, html)
				{
					var table = document.getElementById('sections');
					if(table)
					{
						if(section_id > 0 && html)
						{
							var cnt = table.rows.length;
							var oRow = table.insertRow(cnt-1);

							var i=0;
							var oCell = oRow.insertCell(i++);
							oCell.innerHTML = html;

							var oCell = oRow.insertCell(i++);
							oCell.innerHTML =
								'<input type="button" value="<?echo GetMessage("MAIN_DELETE")?>" OnClick="deleteRow(this)">'+
								'<input type="hidden" name="IBLOCK_SECTION[]" value="'+section_id+'">';
							onSectionChanged();
						}
					}
				}
				</script>
				<input name="input_IBLOCK_SECTION" id="input_IBLOCK_SECTION" type="hidden">
				<input type="button" value="<?echo GetMessage("IBLOCK_ELEMENT_EDIT_PROP_ADD")?>..." onClick="jsUtils.OpenWindow('/bitrix/admin/iblock_section_search.php?lang=<?echo LANGUAGE_ID?>&amp;IBLOCK_ID=<?echo $IBLOCK_ID?>&amp;n=input_IBLOCK_SECTION&amp;m=y', 600, 500);">
		</td>
	<?endif;?>
	</tr>
	<input type="hidden" name="IBLOCK_SECTION[]" value="">
	<script type="text/javascript">
	function onSectionChanged()
	{
		var form = BX('<?echo CUtil::JSEscape($tabControl->GetFormName())?>');
		var url = '<?echo CUtil::JSEscape($APPLICATION->GetCurPageParam())?>';
		<?if($arIBlock["SECTION_PROPERTY"] === "Y" || defined("CATALOG_PRODUCT")):?>
		var groupField = new JCIBlockGroupField(form, 'tr_IBLOCK_ELEMENT_PROPERTY', url);
		groupField.reload();
		<?endif?>
		return;
	}
	</script>
	<?
	$hidden = "";
	if(is_array($str_IBLOCK_ELEMENT_SECTION))
		foreach($str_IBLOCK_ELEMENT_SECTION as $section_id)
			$hidden .= '<input type="hidden" name="IBLOCK_SECTION[]" value="'.intval($section_id).'">';
	$tabControl->EndCustomField("SECTIONS", $hidden);
endif;

if ($arShowTabs['catalog'])
{
	$tabControl->BeginNextFormTab();
	$tabControl->BeginCustomField("CATALOG", GetMessage("IBLOCK_TCATALOG"), true);
	include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/admin/templates/product_edit.php");
	$tabControl->EndCustomField("CATALOG", "");
}

if ($arShowTabs['sku'])
{
	$tabControl->BeginNextFormTab();
	$tabControl->BeginCustomField('OFFERS', GetMessage("IBLOCK_EL_TAB_OFFERS"), false);
	?><tr id="tr_OFFERS"><td colspan="2"><?

	define('B_ADMIN_SUBELEMENTS',1);
	define('B_ADMIN_SUBELEMENTS_LIST',false);

	$intSubIBlockID = $arMainCatalog['IBLOCK_ID'];
	$arSubIBlock = CIBlock::GetArrayByID($intSubIBlockID);
	$arSubIBlock["SITE_ID"] = array();
	$rsSites = CIBlock::GetSite($intSubIBlockID);
	while($arSite = $rsSites->Fetch())
		$arSubIBlock["SITE_ID"][] = $arSite["LID"];
	$strSubIBlockType = $arSubIBlock['IBLOCK_TYPE_ID'];
	$arSubIBlockType = CIBlockType::GetByIDLang($strSubIBlockType, LANGUAGE_ID);

	$boolIncludeOffers = CIBlockRights::UserHasRightTo($intSubIBlockID, $intSubIBlockID, "iblock_admin_display");;
	$arSubCatalog = CCatalogSKU::GetInfoByOfferIBlock($arMainCatalog['IBLOCK_ID']);
	$boolSubCatalog = (!empty($arSubCatalog) && is_array($arSubCatalog));
	if (!$boolCatalogRead && !$boolCatalogPrice)
		$boolSubCatalog = false;

	$boolSubWorkFlow = CModule::IncludeModule("workflow") && $arSubIBlock["WORKFLOW"] != "N";
	$boolSubBizproc = CModule::IncludeModule("bizproc") && $arSubIBlock["BIZPROC"] != "N";

	$intSubPropValue = (0 == $ID || $bCopy ? '-'.$TMP_ID : $ID);
	$strSubTMP_ID = $TMP_ID;

	$strSubElementAjaxPath = '/bitrix/admin/iblock_subelement_admin.php?WF=Y&IBLOCK_ID='.$intSubIBlockID.'&type='.urlencode($strSubIBlockType).'&lang='.LANGUAGE_ID.'&find_section_section=0&find_el_property_'.$arSubCatalog['SKU_PROPERTY_ID'].'='.((0 == $ID) || ($bCopy) ? '-'.$TMP_ID : $ID).'&TMP_ID='.urlencode($strSubTMP_ID);
	if ($boolIncludeOffers && file_exists($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/iblock/admin/templates/iblock_subelement_list.php'))
	{
		require($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/iblock/admin/templates/iblock_subelement_list.php');
	}
	else
	{
		ShowError(GetMessage('IBLOCK_EL_OFFERS_ACCESS_DENIED'));
	}
	?></td></tr><?
	$tabControl->EndCustomField('OFFERS','');
}

if ($arShowTabs['product_set'])
{
	$tabControl->BeginNextFormTab();
	$tabControl->BeginCustomField('PRODUCT_SET', GetMessage('IBLOCK_EL_PRODUCT_SET').':', false);
	?><tr id="tr_PRODUCT_SET"><td colspan="2"><?

	$intProductID = (0 < $ID ? CIBlockElement::GetRealElement($ID) : 0);

	$arSets = false;
	CCatalogAdminProductSetEdit::setProductFormParams(array('TYPE' => CCatalogProductSet::TYPE_SET));
	if (0 < $intProductID)
	{
		$arSets = CCatalogProductSet::getAllSetsByProduct($intProductID, CCatalogProductSet::TYPE_SET);
		if ($bCopy)
			CCatalogAdminProductSetEdit::clearOwnerSet($arSets);
	}
	if (empty($arSets))
		$arSets = CCatalogAdminProductSetEdit::getEmptySet($intProductID);

	if ($bVarsFromForm)
		CCatalogAdminProductSetEdit::getFormValues($arSets);
	CCatalogAdminProductSetEdit::addEmptyValues($arSets);

	CCatalogAdminProductSetEdit::showEditForm($arSets);
	?></td></tr><?
	$tabControl->EndCustomField('PRODUCT_SET', '');
}
if ($arShowTabs['product_group'])
{
	$tabControl->BeginNextFormTab();
	$tabControl->BeginCustomField('PRODUCT_GROUP', GetMessage('IBLOCK_EL_PRODUCT_GROUP').':', false);
	?><tr id="tr_PRODUCT_GROUP"><td colspan="2"><?

	$intProductID = (0 < $ID ? CIBlockElement::GetRealElement($ID) : 0);

	$arSets = false;
	CCatalogAdminProductSetEdit::setProductFormParams(array('TYPE' => CCatalogProductSet::TYPE_GROUP));
	if (0 < $intProductID)
	{
		$arSets = CCatalogProductSet::getAllSetsByProduct($intProductID, CCatalogProductSet::TYPE_GROUP);
		if ($bCopy)
			CCatalogAdminProductSetEdit::clearOwnerSet($arSets);
	}
	if (empty($arSets))
		$arSets = CCatalogAdminProductSetEdit::getEmptySet($intProductID);
	if ($bVarsFromForm)
		CCatalogAdminProductSetEdit::getFormValues($arSets);
	CCatalogAdminProductSetEdit::addEmptyValues($arSets);

	CCatalogAdminProductSetEdit::showEditForm($arSets);

	?></td></tr><?
	$tabControl->EndCustomField('PRODUCT_SET', '');
}
if($arShowTabs['workflow']):?>
<?
	$tabControl->BeginNextFormTab();
	$tabControl->BeginCustomField("WORKFLOW_PARAMS", GetMessage("IBLOCK_EL_TAB_WF_TITLE"));
	if(strlen($pr["DATE_CREATE"])>0):
	?>
		<tr id="tr_WF_CREATED">
			<td width="40%"><?echo GetMessage("IBLOCK_CREATED")?></td>
			<td width="60%"><?echo $pr["DATE_CREATE"]?><?
			if (intval($pr["CREATED_BY"])>0):
			?>&nbsp;&nbsp;&nbsp;[<a href="user_edit.php?lang=<?=LANGUAGE_ID?>&amp;ID=<?=$pr["CREATED_BY"]?>"><?echo $pr["CREATED_BY"]?></a>]&nbsp;<?=htmlspecialcharsex($pr["CREATED_USER_NAME"])?><?
			endif;
			?></td>
		</tr>
	<?endif;?>
	<?if(strlen($str_TIMESTAMP_X) > 0 && !$bCopy):?>
	<tr id="tr_WF_MODIFIED">
		<td><?echo GetMessage("IBLOCK_LAST_UPDATE")?></td>
		<td><?echo $str_TIMESTAMP_X?><?
		if (intval($str_MODIFIED_BY)>0):
		?>&nbsp;&nbsp;&nbsp;[<a href="user_edit.php?lang=<?=LANGUAGE_ID?>&amp;ID=<?=$str_MODIFIED_BY?>"><?echo $str_MODIFIED_BY?></a>]&nbsp;<?=$str_USER_NAME?><?
		endif;
		?></td>
	</tr>
	<?endif?>
	<?if($WF=="Y" && strlen($prn_WF_DATE_LOCK)>0):?>
	<tr id="tr_WF_LOCKED">
		<td><?echo GetMessage("IBLOCK_DATE_LOCK")?></td>
		<td><?echo $prn_WF_DATE_LOCK?><?
		if (intval($prn_WF_LOCKED_BY)>0):
		?>&nbsp;&nbsp;&nbsp;[<a href="user_edit.php?lang=<?=LANGUAGE_ID?>&amp;ID=<?=$prn_WF_LOCKED_BY?>"><?echo $prn_WF_LOCKED_BY?></a>]&nbsp;<?=$prn_LOCKED_USER_NAME?><?
		endif;
		?></td>
	</tr>
	<?endif;
	$tabControl->EndCustomField("WORKFLOW_PARAMS", "");
	if ($WF=="Y" || $view=="Y"):
	$tabControl->BeginCustomField("WF_STATUS_ID", GetMessage("IBLOCK_FIELD_STATUS").":");
	?>
	<tr id="tr_WF_STATUS_ID">
		<td><?echo $tabControl->GetCustomLabelHTML()?></td>
		<td>
			<?if($ID > 0 && !$bCopy):?>
				<?echo SelectBox("WF_STATUS_ID", CWorkflowStatus::GetDropDownList("N", "desc"), "", $str_WF_STATUS_ID);?>
			<?else:?>
				<?echo SelectBox("WF_STATUS_ID", CWorkflowStatus::GetDropDownList("N", "desc"), "", "");?>
			<?endif?>
		</td>
	</tr>
	<?
	if($ID > 0 && !$bCopy)
		$hidden = '<input type="hidden" name="WF_STATUS_ID" value="'.$str_WF_STATUS_ID.'">';
	else
	{
		$rsStatus = CWorkflowStatus::GetDropDownList("N", "desc");
		$arDefaultStatus = $rsStatus->Fetch();
		if($arDefaultStatus)
			$def_WF_STATUS_ID = intval($arDefaultStatus["REFERENCE_ID"]);
		else
			$def_WF_STATUS_ID = "";
		$hidden = '<input type="hidden" name="WF_STATUS_ID" value="'.$def_WF_STATUS_ID.'">';
	}
	$tabControl->EndCustomField("WF_STATUS_ID", $hidden);
	endif;
	$tabControl->BeginCustomField("WF_COMMENTS", GetMessage("IBLOCK_COMMENTS"));
	?>
	<tr class="heading" id="tr_WF_COMMENTS_LABEL">
		<td colspan="2"><b><?echo $tabControl->GetCustomLabelHTML()?></b></td>
	</tr>
	<tr id="tr_WF_COMMENTS">
		<td colspan="2">
			<?if($ID > 0 && !$bCopy):?>
				<textarea name="WF_COMMENTS" style="width:100%" rows="10"><?echo $str_WF_COMMENTS?></textarea>
			<?else:?>
				<textarea name="WF_COMMENTS" style="width:100%" rows="10"><?echo ""?></textarea>
			<?endif?>
		</td>
	</tr>
	<?
	$tabControl->EndCustomField("WF_COMMENTS", '<input type="hidden" name="WF_COMMENTS" value="'.$str_WF_COMMENTS.'">');
endif;

if ($arShowTabs['bizproc']):

	$tabControl->BeginNextFormTab();

	$tabControl->BeginCustomField("BIZPROC_WF_STATUS", GetMessage("IBEL_E_PUBLISHED"));
	?>
	<tr id="tr_BIZPROC_WF_STATUS">
		<td style="width:40%;"><?=GetMessage("IBEL_E_PUBLISHED")?>:</td>
		<td style="width:60%;"><?=($str_BP_PUBLISHED=="Y"?GetMessage("MAIN_YES"):GetMessage("MAIN_NO"))?></td>
	</tr>
	<?
	$tabControl->EndCustomField("BIZPROC_WF_STATUS", '');

	ob_start();
	$required = false;
	CBPDocument::AddShowParameterInit(MODULE_ID, "only_users", DOCUMENT_TYPE);

	$bizProcIndex = 0;
	if (!isset($arDocumentStates))
	{
		$arDocumentStates = CBPDocument::GetDocumentStates(
			array(MODULE_ID, ENTITY, DOCUMENT_TYPE),
			($ID > 0) ? array(MODULE_ID, ENTITY, $ID) : null,
			"Y"
		);
	}
	foreach ($arDocumentStates as $arDocumentState)
	{
		$bizProcIndex++;
		if (strlen($arDocumentState["ID"]) > 0)
		{
			$canViewWorkflow = CBPDocument::CanUserOperateDocument(
				CBPCanUserOperateOperation::ViewWorkflow,
				$GLOBALS["USER"]->GetID(),
				array(MODULE_ID, ENTITY, $ID),
				array("AllUserGroups" => $arCurrentUserGroups, "DocumentStates" => $arDocumentStates, "WorkflowId" => $arDocumentState["ID"] > 0 ? $arDocumentState["ID"] : $arDocumentState["TEMPLATE_ID"])
			);
		}
		else
		{
			$canViewWorkflow = CBPDocument::CanUserOperateDocumentType(
				CBPCanUserOperateOperation::ViewWorkflow,
				$GLOBALS["USER"]->GetID(),
				array(MODULE_ID, ENTITY, DOCUMENT_TYPE),
				array("AllUserGroups" => $arCurrentUserGroups, "DocumentStates" => $arDocumentStates, "WorkflowId" => $arDocumentState["ID"] > 0 ? $arDocumentState["ID"] : $arDocumentState["TEMPLATE_ID"])
			);
		}
		if (!$canViewWorkflow)
			continue;
		?>
		<tr class="heading">
			<td colspan="2">
				<?= htmlspecialcharsbx($arDocumentState["TEMPLATE_NAME"]) ?>
				<?if (strlen($arDocumentState["ID"]) > 0 && strlen($arDocumentState["WORKFLOW_STATUS"]) > 0):?>
					(<a href="<?echo htmlspecialcharsbx("/bitrix/admin/".CIBlock::GetAdminElementEditLink($IBLOCK_ID, $ID, array(
						"WF"=>$WF,
						"find_section_section" => $find_section_section,
						"stop_bizproc" => $arDocumentState["ID"],
					),  "&".bitrix_sessid_get()))?>"><?echo GetMessage("IBEL_BIZPROC_STOP")?></a>)
				<?endif;?>
			</td>
		</tr>
		<tr>
			<td width="40%"><?echo GetMessage("IBEL_BIZPROC_NAME")?></td>
			<td width="60%"><?= htmlspecialcharsbx($arDocumentState["TEMPLATE_NAME"]) ?></td>
		</tr>
		<?if($arDocumentState["TEMPLATE_DESCRIPTION"]!=''):?>
		<tr>
			<td width="40%"><?echo GetMessage("IBEL_BIZPROC_DESC")?></td>
			<td width="60%"><?= htmlspecialcharsbx($arDocumentState["TEMPLATE_DESCRIPTION"]) ?></td>
		</tr>
		<?endif?>
		<?if (strlen($arDocumentState["STATE_MODIFIED"]) > 0):?>
		<tr>
			<td width="40%"><?echo GetMessage("IBEL_BIZPROC_DATE")?></td>
			<td width="60%"><?= $arDocumentState["STATE_MODIFIED"] ?></td>
		</tr>
		<?endif;?>
		<?if (strlen($arDocumentState["STATE_NAME"]) > 0):?>
		<tr>
			<td width="40%"><?echo GetMessage("IBEL_BIZPROC_STATE")?></td>
			<td width="60%"><?if (strlen($arDocumentState["ID"]) > 0):?><a href="/bitrix/admin/bizproc_log.php?ID=<?= $arDocumentState["ID"] ?>"><?endif;?><?= strlen($arDocumentState["STATE_TITLE"]) > 0 ? $arDocumentState["STATE_TITLE"] : $arDocumentState["STATE_NAME"] ?><?if (strlen($arDocumentState["ID"]) > 0):?></a><?endif;?></td>
		</tr>
		<?endif;?>
		<?
		if (strlen($arDocumentState["ID"]) <= 0)
		{
			CBPDocument::StartWorkflowParametersShow(
				$arDocumentState["TEMPLATE_ID"],
				$arDocumentState["TEMPLATE_PARAMETERS"],
				($bCustomForm ? "tabControl" : "form_element_".$IBLOCK_ID)."_form",
				$bVarsFromForm
			);
			if (is_array($arDocumentState["TEMPLATE_PARAMETERS"]))
			{
				foreach ($arDocumentState["TEMPLATE_PARAMETERS"] as $arParameter)
				{
					if ($arParameter["Required"])
						$required = true;
				}
			}
		}
		?>
		<?
		$arEvents = CBPDocument::GetAllowableEvents($GLOBALS["USER"]->GetID(), $arCurrentUserGroups, $arDocumentState);
		if (!empty($arEvents))
		{
			?>
			<tr>
				<td width="40%"><?echo GetMessage("IBEL_BIZPROC_RUN_CMD")?></td>
				<td width="60%">
					<input type="hidden" name="bizproc_id_<?= $bizProcIndex ?>" value="<?= $arDocumentState["ID"] ?>">
					<input type="hidden" name="bizproc_template_id_<?= $bizProcIndex ?>" value="<?= $arDocumentState["TEMPLATE_ID"] ?>">
					<select name="bizproc_event_<?= $bizProcIndex ?>">
						<option value=""><?echo GetMessage("IBEL_BIZPROC_RUN_CMD_NO")?></option>
						<?
						foreach ($arEvents as $e)
						{
							?><option value="<?= htmlspecialcharsbx($e["NAME"]) ?>"<?= ($_REQUEST["bizproc_event_".$bizProcIndex] == $e["NAME"]) ? " selected" : ""?>><?= htmlspecialcharsbx($e["TITLE"]) ?></option><?
						}
						?>
					</select>
				</td>
			</tr>
			<?
		}

		if (strlen($arDocumentState["ID"]) > 0)
		{
			$arTasks = CBPDocument::GetUserTasksForWorkflow($USER->GetID(), $arDocumentState["ID"]);
			if (!empty($arTasks))
			{
				?>
				<tr>
					<td width="40%"><?echo GetMessage("IBEL_BIZPROC_TASKS")?></td>
					<td width="60%">
						<?
						foreach ($arTasks as $arTask)
						{
							?><a href="/bitrix/admin/bizproc_task.php?id=<?= $arTask["ID"] ?>&back_url=<?= urlencode($APPLICATION->GetCurPageParam("", array())) ?>" title="<?= strip_tags($arTask["DESCRIPTION"]) ?>"><?= $arTask["NAME"] ?></a><br /><?
						}
						?>
					</td>
				</tr>
				<?
			}
		}
	}
	if ($bizProcIndex <= 0)
	{
		?>
		<tr>
			<td><br /></td>
			<td><?=GetMessage("IBEL_BIZPROC_NA")?></td>
		</tr>
		<?
	}
	?>
	<input type="hidden" name="bizproc_index" value="<?= $bizProcIndex ?>">
	<?
	if ($ID > 0):
		$bStartWorkflowPermission = CBPDocument::CanUserOperateDocument(
			CBPCanUserOperateOperation::StartWorkflow,
			$USER->GetID(),
			array(MODULE_ID, ENTITY, $ID),
			array("AllUserGroups" => $arCurrentUserGroups, "DocumentStates" => $arDocumentStates, "WorkflowId" => $arDocumentState["TEMPLATE_ID"])
		);
		if ($bStartWorkflowPermission):
			?>
			<tr class="heading">
				<td colspan="2"><?echo GetMessage("IBEL_BIZPROC_NEW")?></td>
			</tr>
			<tr>
				<td colspan="2" align="center">
					<a href="/bitrix/admin/<?=MODULE_ID?>_start_bizproc.php?document_id=<?= $ID ?>&document_type=<?= DOCUMENT_TYPE ?>&back_url=<?= urlencode($APPLICATION->GetCurPageParam("", array())) ?>"><?echo GetMessage("IBEL_BIZPROC_START")?></a>
				</td>
			</tr>
			<?
		endif;
	endif;
	$html = ob_get_contents();
	ob_end_clean();
	$tabControl->BeginCustomField("BIZPROC", GetMessage("IBEL_E_TAB_BIZPROC"), $required);
	echo $html;
	$tabControl->EndCustomField("BIZPROC", "");
endif;

if($arShowTabs['edit_rights']):
	$tabControl->BeginNextFormTab();
	if($ID > 0)
	{
		$obRights = new CIBlockElementRights($IBLOCK_ID, $ID);
		$htmlHidden = '';
		foreach($obRights->GetRights() as $RIGHT_ID => $arRight)
			$htmlHidden .= '
				<input type="hidden" name="RIGHTS[][RIGHT_ID]" value="'.htmlspecialcharsbx($RIGHT_ID).'">
				<input type="hidden" name="RIGHTS[][GROUP_CODE]" value="'.htmlspecialcharsbx($arRight["GROUP_CODE"]).'">
				<input type="hidden" name="RIGHTS[][TASK_ID]" value="'.htmlspecialcharsbx($arRight["TASK_ID"]).'">
			';
	}
	else
	{
		$obRights = new CIBlockSectionRights($IBLOCK_ID, $MENU_SECTION_ID);
		$htmlHidden = '';
	}

	$tabControl->BeginCustomField("RIGHTS", GetMessage("IBEL_E_RIGHTS_FIELD"));
		IBlockShowRights(
			'element',
			$IBLOCK_ID,
			$ID,
			GetMessage("IBEL_E_RIGHTS_SECTION_TITLE"),
			"RIGHTS",
			$obRights->GetRightsList(),
			$obRights->GetRights(array("count_overwrited" => true, "parents" => $str_IBLOCK_ELEMENT_SECTION)),
			false, /*$bForceInherited=*/($ID <= 0) || $bCopy
		);
	$tabControl->EndCustomField("RIGHTS", $htmlHidden);
endif;

$bDisabled =
	($view=="Y")
	|| ($bWorkflow && $prn_LOCK_STATUS=="red")
	|| (
		(($ID <= 0) || $bCopy)
		&& !CIBlockSectionRights::UserHasRightTo($IBLOCK_ID, $MENU_SECTION_ID, "section_element_bind")
	)
	|| (
		(($ID > 0) && !$bCopy)
		&& !CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $ID, "element_edit")
	)
	|| (
		$bBizproc
		&& !$canWrite
	)
;

if (!defined('BX_PUBLIC_MODE') || BX_PUBLIC_MODE != 1):
	ob_start();
	?>
	<input <?if ($bDisabled) echo "disabled";?> type="submit" class="adm-btn-save" name="save" id="save" value="<?echo GetMessage("IBLOCK_EL_SAVE")?>">
	<? if (!$bAutocomplete)
	{
		?><input <?if ($bDisabled) echo "disabled";?> type="submit" class="button" name="apply" id="apply" value="<?echo GetMessage('IBLOCK_APPLY')?>"><?
	}
	?>
	<input <?if ($bDisabled) echo "disabled";?> type="submit" class="button" name="dontsave" id="dontsave" value="<?echo GetMessage("IBLOCK_EL_CANC")?>">
	<? if (!$bAutocomplete)
	{
		?><input <?if ($bDisabled) echo "disabled";?> type="submit" class="adm-btn-add" name="save_and_add" id="save_and_add" value="<?echo GetMessage("IBLOCK_EL_SAVE_AND_ADD")?>"><?
	}
	$buttons_add_html = ob_get_contents();
	ob_end_clean();
	$tabControl->Buttons(false, $buttons_add_html);
elseif(!$bPropertyAjax && $nobuttons !== "Y"):

	$wfClose = "{
		title: '".CUtil::JSEscape(GetMessage("IBLOCK_EL_CANC"))."',
		name: 'dontsave',
		id: 'dontsave',
		action: function () {
			var FORM = this.parentWindow.GetForm();
			FORM.appendChild(BX.create('INPUT', {
				props: {
					type: 'hidden',
					name: this.name,
					value: 'Y'
				}
			}));
			this.disableUntilError();
			this.parentWindow.Submit();
		}
	}";
	$save_and_add = "{
		title: '".CUtil::JSEscape(GetMessage("IBLOCK_EL_SAVE_AND_ADD"))."',
		name: 'save_and_add',
		id: 'save_and_add',
		className: 'adm-btn-add',
		action: function () {
			var FORM = this.parentWindow.GetForm();
			FORM.appendChild(BX.create('INPUT', {
				props: {
					type: 'hidden',
					name: 'save_and_add',
					value: 'Y'
				}
			}));

			this.parentWindow.hideNotify();
			this.disableUntilError();
			this.parentWindow.Submit();
		}
	}";
	$cancel = "{
		title: '".CUtil::JSEscape(GetMessage("IBLOCK_EL_CANC"))."',
		name: 'cancel',
		id: 'cancel',
		action: function () {
			BX.WindowManager.Get().Close();
			if(window.reloadAfterClose)
				top.BX.reload(true);
		}
	}";
	$tabControl->ButtonsPublic(array(
		'.btnSave',
		($ID > 0 && $bWorkflow? $wfClose: $cancel),
		$save_and_add,
	));
endif;

$tabControl->Show();
if (
	(!defined('BX_PUBLIC_MODE') || BX_PUBLIC_MODE != 1)
	&& CIBlockRights::UserHasRightTo($IBLOCK_ID, $IBLOCK_ID, "iblock_edit")
	&& !$bAutocomplete
)
{

	echo
		BeginNote(),
		GetMessage("IBEL_E_IBLOCK_MANAGE_HINT"),
		' <a href="/bitrix/admin/iblock_edit.php?type='.htmlspecialcharsbx($type).'&amp;lang='.LANGUAGE_ID.'&amp;ID='.$IBLOCK_ID.'&amp;admin=Y&amp;return_url='.urlencode("/bitrix/admin/".CIBlock::GetAdminElementEditLink($IBLOCK_ID, $ID, array("WF" => ($WF=="Y"? "Y": null), "find_section_section" => intval($find_section_section), "return_url" => (strlen($return_url)>0? $return_url: null)))).'">',
		GetMessage("IBEL_E_IBLOCK_MANAGE_HINT_HREF"),
		'</a>',
		EndNote()
	;
}
	//////////////////////////
	//END of the custom form
	//////////////////////////
endif;

}
if ($bAutocomplete)
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");
elseif ($bPropertyAjax)
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");
else
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>