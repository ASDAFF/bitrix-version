<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

if (!($USER->CanDoOperation('catalog_read') || $USER->CanDoOperation('catalog_group')))
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$bReadOnly = !$USER->CanDoOperation('catalog_group');

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/include.php");

if ($ex = $APPLICATION->GetException())
{
	require($DOCUMENT_ROOT."/bitrix/modules/main/include/prolog_admin_after.php");

	$strError = $ex->GetString();
	ShowError($strError);

	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
	die();
}

IncludeModuleLangFile(__FILE__);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/prolog.php");

$sTableID = "tbl_catalog_group";

$oSort = new CAdminSorting($sTableID, "ID", "asc");

$lAdmin = new CAdminList($sTableID, $oSort);

$arFilterFields = array();

$lAdmin->InitFilter($arFilterFields);

$arFilter = array();

if ($lAdmin->EditAction() && !$bReadOnly)
{
	foreach ($_POST['FIELDS'] as $ID => $arFields)
	{
		$DB->StartTransaction();
		$ID = IntVal($ID);

		if (!$lAdmin->IsUpdated($ID))
			continue;

		if (!CCatalogGroup::Update($ID, $arFields))
		{
			if ($ex = $APPLICATION->GetException())
				$lAdmin->AddUpdateError($ex->GetString(), $ID);
			else
				$lAdmin->AddUpdateError(GetMessage("ERROR_UPDATING_REC")." (".$arFields["ID"].", ".$arFields["NAME"].", ".$arFields["SORT"].")", $ID);

			$DB->Rollback();
		}

		$DB->Commit();
	}
}

if (($arID = $lAdmin->GroupAction()) && !$bReadOnly)
{
	if ($_REQUEST['action_target']=='selected')
	{
		$arID = Array();
		$dbResultList = CCatalogGroup::GetList(array($by => $order));
		while ($arResult = $dbResultList->Fetch())
			$arID[] = $arResult['ID'];
	}

	foreach ($arID as $ID)
	{
		if (strlen($ID) <= 0)
			continue;

		switch ($_REQUEST['action'])
		{
			case "delete":
				@set_time_limit(0);

				$DB->StartTransaction();

				if (!CCatalogGroup::Delete($ID))
				{
					$DB->Rollback();

					if ($ex = $APPLICATION->GetException())
						$lAdmin->AddGroupError($ex->GetString(), $ID);
					else
						$lAdmin->AddGroupError(GetMessage("ERROR_DELETING_TYPE"), $ID);
				}

				$DB->Commit();
				break;
		}
	}
}

$lAdmin->AddHeaders(array(
	array("id" => "ID", "content"=>"ID", "sort"=>"ID", "default"=>true),
	array("id" => "NAME","content"=>GetMessage("CODE"), "sort"=>"NAME", "default"=>true),
	array("id" => "NAME_LID", "content"=>GetMessage('NAME'), "sort"=>"", "default"=>true),
	array("id" => "SORT", "content"=>GetMessage("SORT"),  "sort"=>"SORT", "default"=>true),
	array("id" => "BASE", "content"=>GetMessage("BASE"),  "sort"=>"BASE", "default"=>true),
	array("id" => "XML_ID", "content"=>GetMessage("BT_CAT_GROUP_ADM_TITLE_XML_ID"),  "sort"=>"XML_ID", "default" => false),
));

$arSelectFields = $lAdmin->GetVisibleHeaderColumns();

$arSelectFieldsMap = array();
foreach ($arSelectFields as &$strOneFieldName)
{
	$arSelectFieldsMap[$strOneFieldName] = true;
}
if (isset($strOneFieldName))
	unset($strOneFieldName);


$mxKey = array_search('NAME_LID', $arSelectFields);
if (false !== $mxKey)
{
	unset($arSelectFields[$mxKey]);
}

$arLangList = array();
$arLangDefList = array();
if (array_key_exists('NAME_LID', $arSelectFieldsMap))
{
	$rsPriceLangs = CLangAdmin::GetList(($by1="sort"), ($order1="asc"));
	while ($arPriceLang = $rsPriceLangs->Fetch())
	{
		$arLangList[$arPriceLang['LID']] = true;
		$arLangDefList[$arPriceLang['LID']] = str_replace('#LANG#', htmlspecialcharsex($arPriceLang['NAME']), GetMessage('BT_CAT_GROUP_ADM_LANG_MESS'));
	}
}

if (array_key_exists("mode", $_REQUEST) && $_REQUEST["mode"] == "excel")
	$arNavParams = false;
else
	$arNavParams = array("nPageSize"=>CAdminResult::GetNavSize($sTableID));

$dbResultList = CCatalogGroup::GetList(
	array($by => $order),
	array(),
	false,
	$arNavParams,
	$arSelectFields
);

$dbResultList = new CAdminResult($dbResultList, $sTableID);
$dbResultList->NavStart();

$lAdmin->NavText($dbResultList->GetNavPrint(GetMessage("group_admin_nav")));

$arRows = array();

while ($arRes = $dbResultList->Fetch())
{
	$arRows[$arRes['ID']] = $row = &$lAdmin->AddRow($arRes['ID'], $arRes);

	$row->AddViewField("ID", '<a href="/bitrix/admin/cat_group_edit.php?lang='.LANGUAGE_ID.'&ID='.$arRes["ID"].'&'.GetFilterParams("filter_").'">'.$arRes["ID"].'</a>');

	if (!$bReadOnly)
	{
		if (array_key_exists('NAME', $arSelectFieldsMap))
			$row->AddInputField("NAME", array("size"=>30));
		if (array_key_exists('SORT', $arSelectFieldsMap))
			$row->AddInputField("SORT", array("size"=>3));
		if (array_key_exists('XML_ID', $arSelectFieldsMap))
			$row->AddInputField("XML_ID", array("size"=>30));
	}
	else
	{
		if (array_key_exists('NAME', $arSelectFieldsMap))
			$row->AddViewField("NAME", '<a href="/bitrix/admin/cat_group_edit.php?lang='.LANGUAGE_ID.'&ID='.$arRes["ID"].'&'.GetFilterParams("filter_").'">'.htmlspecialcharsex($arRes['NAME']).'</a>');
		if (array_key_exists('SORT', $arSelectFieldsMap))
			$row->AddInputField('SORT', false);
		if (array_key_exists('XML_ID', $arSelectFieldsMap))
			$row->AddInputField("XML_ID", false);
	}

	if (array_key_exists('BASE', $arSelectFieldsMap))
		$row->AddViewField("BASE", ("Y" == $arRes['BASE'] ? GetMessage("BASE_YES") : "&nbsp;"));

	$arActions = array();
	$arActions[] = array("ICON"=>"edit", "TEXT"=>GetMessage("EDIT_STATUS_ALT"), "ACTION"=>$lAdmin->ActionRedirect("/bitrix/admin/cat_group_edit.php?ID=".$arRes['ID']."&lang=".LANGUAGE_ID."&".GetFilterParams("filter_").""), "DEFAULT"=>true);

	if (!$bReadOnly)
	{
		if ('Y' != $arRes['BASE'])
		{
			$arActions[] = array("SEPARATOR" => true);
			$arActions[] = array("ICON"=>"delete", "TEXT"=>GetMessage("DELETE_STATUS_ALT"), "ACTION"=>"if(confirm('".GetMessage('DELETE_STATUS_CONFIRM')."')) ".$lAdmin->ActionDoGroup($arRes['ID'], "delete"));
		}
	}

	$row->AddActions($arActions);
}

if (array_key_exists('NAME_LID', $arSelectFieldsMap))
{
	$arGroupIDS = array_keys($arRows);
	if (!empty($arGroupIDS))
	{
		$arLangResult = array();
		$arLangResult = array_fill_keys($arGroupIDS, $arLangDefList);
		$rsLangs = CCatalogGroup::GetLangList(array("CATALOG_GROUP_ID" => $arGroupIDS));
		while ($arLang = $rsLangs->Fetch())
		{
			$arLang['CATALOG_GROUP_ID'] = intval($arLang['CATALOG_GROUP_ID']);
			if (array_key_exists($arLang['LID'], $arLangList))
			{
				$arLangResult[$arLang['CATALOG_GROUP_ID']][$arLang['LID']] = str_replace('#VALUE#', htmlspecialcharsex($arLang["NAME"]), $arLangResult[$arLang['CATALOG_GROUP_ID']][$arLang['LID']]);
			}
		}

		foreach ($arGroupIDS as &$intGroupID)
		{
			$strLang = str_replace('#VALUE#', '', implode('<br>', $arLangResult[$intGroupID]));
			$arRows[$intGroupID]->AddViewField("NAME_LID", $strLang);
		}
		if (isset($intGroupID))
			unset($intGroupID);
	}
}

$lAdmin->AddFooter(
	array(
		array(
			"title" => GetMessage("MAIN_ADMIN_LIST_SELECTED"),
			"value" => $dbResultList->SelectedRowsCount()
		),
		array(
			"counter" => true,
			"title" => GetMessage("MAIN_ADMIN_LIST_CHECKED"),
			"value" => "0"
		),
	)
);

if (!$bReadOnly)
{
	if (CBXFeatures::IsFeatureEnabled('CatMultiPrice'))
	{
		$lAdmin->AddGroupActionTable(
			array(
				"delete" => GetMessage("MAIN_ADMIN_LIST_DELETE"),
			)
		);
	}
	else
	{
		$lAdmin->AddGroupActionTable(
			array()
		);
	}
}

if (!$bReadOnly)
{
	$aContext = array();
	$boolEmptyPrice = true;
	$dbCatGroup = CCatalogGroup::GetList(array("ID" => "ASC"), array(), false, array("nTopCount" => 1), array("ID"));
	if ($arCatGroup = $dbCatGroup->Fetch())
	{
		$boolEmptyPrice = false;
	}
	if (CBXFeatures::IsFeatureEnabled('CatMultiPrice') || $boolEmptyPrice)
	{
		$aContext = array(
			array(
				"TEXT" => GetMessage("CGAN_ADD_NEW"),
				"ICON" => "btn_new",
				"LINK" => "cat_group_edit.php?lang=".LANG,
				"TITLE" => GetMessage("CGAN_ADD_NEW_ALT")
			),
		);
	}
	$lAdmin->AddAdminContextMenu($aContext);
}

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("GROUP_TITLE"));
require($DOCUMENT_ROOT."/bitrix/modules/main/include/prolog_admin_after.php");

$lAdmin->DisplayList();
?>
<?require($DOCUMENT_ROOT."/bitrix/modules/main/include/epilog_admin.php");?>