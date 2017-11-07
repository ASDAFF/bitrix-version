<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

global $APPLICATION;
global $DB;
global $USER;

if (!($USER->CanDoOperation('catalog_read') || $USER->CanDoOperation('catalog_store')))
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
$bReadOnly = !$USER->CanDoOperation('catalog_store');

IncludeModuleLangFile(__FILE__);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/include.php");

$bCanAdd = true;
$bExport = false;
if($_REQUEST["mode"] == "excel")
	$bExport = true;

if (!CBXFeatures::IsFeatureEnabled('CatMultiStore'))
{
	$dbResultList = CCatalogStore::GetList(array());
	if($arResult = $dbResultList->Fetch())
		$bCanAdd = false;
}

if ($ex = $APPLICATION->GetException())
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

	$strError = $ex->GetString();
	ShowError($strError);

	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
	die();
}

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/prolog.php");

$sTableID = "b_catalog_store";
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
		$arFields['ID']=$ID;
		if(isset($arFields["IMAGE_ID"]))
			unset($arFields["IMAGE_ID"]);
		if (!$lAdmin->IsUpdated($ID))
			continue;

		if (!CCatalogStore::Update($ID, $arFields))
		{
			if ($ex = $APPLICATION->GetException())
				$lAdmin->AddUpdateError($ex->GetString(), $ID);
			else
				$lAdmin->AddUpdateError(GetMessage("ERROR_UPDATING_REC")." (".$arFields["ID"].", ".$arFields["TITLE"].", ".$arFields["SORT"].")", $ID);

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
		$dbResultList = CCatalogStore::GetList(array($_REQUEST["by"] => $_REQUEST["order"]));
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

				if (!CCatalogStore::Delete($ID))
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
$arSelect = array(
	"ID",
	"ACTIVE",
	"TITLE",
	"ADDRESS",
	"DESCRIPTION",
	"GPS_N",
	"GPS_S",
	"IMAGE_ID",
	"PHONE",
	"SCHEDULE",
	"XML_ID",
	"DATE_MODIFY",
	"DATE_CREATE",
	"USER_ID",
	"MODIFIED_BY"
);

if (array_key_exists("mode", $_REQUEST) && $_REQUEST["mode"] == "excel")
	$arNavParams = false;
else
	$arNavParams = array("nPageSize"=>CAdminResult::GetNavSize($sTableID));

$dbResultList = CCatalogStore::GetList(
	array($_REQUEST["by"] => $_REQUEST["order"]),
	array(),
	false,
	$arNavParams,
	$arSelect
);
$dbResultList = new CAdminResult($dbResultList, $sTableID);
$dbResultList->NavStart();
$lAdmin->NavText($dbResultList->GetNavPrint(GetMessage("group_admin_nav")));

$lAdmin->AddHeaders(array(
	array("id"=>"ID", "content"=>"ID", "sort"=>"ID", "default"=>true),
	array("id"=>"TITLE","content"=>GetMessage("TITLE"), "sort"=>"TITLE", "default"=>true),
	array("id"=>"ACTIVE","content"=>GetMessage("STORE_ACTIVE"), "sort"=>"ACTIVE_FLAG", "default"=>true),
	array("id"=>"ADDRESS", "content"=>GetMessage("ADDRESS"), "sort"=>"ADDRESS", "default"=>true),
	array("id"=>"IMAGE_ID", "content"=>GetMessage("STORE_IMAGE"),  "sort"=>"IMAGE_ID", "default"=>false),
	array("id"=>"DESCRIPTION", "content"=>GetMessage("DESCRIPTION"),  "sort"=>"DESCRIPTION", "default"=>true),
	array("id"=>"GPS_N", "content"=>GetMessage("GPS_N"),  "sort"=>"GPS_N", "default"=>false),
	array("id"=>"GPS_S", "content"=>GetMessage("GPS_S"),  "sort"=>"GPS_S", "default"=>false),
	array("id"=>"PHONE", "content"=>GetMessage("PHONE"),  "sort"=>"PHONE", "default"=>true),
	array("id"=>"SCHEDULE", "content"=>GetMessage("SCHEDULE"),  "sort"=>"SCHEDULE", "default"=>true),
	array("id"=>"DATE_MODIFY", "content"=>GetMessage("DATE_MODIFY"),  "sort"=>"DATE_MODIFY", "default"=>true),
	array("id"=>"MODIFIED_BY", "content"=>GetMessage("MODIFIED_BY"),  "sort"=>"MODIFIED_BY", "default"=>true),
	array("id"=>"DATE_CREATE", "content"=>GetMessage("DATE_CREATE"),  "sort"=>"DATE_CREATE", "default"=>false),
	array("id"=>"USER_ID", "content"=>GetMessage("USER_ID"),  "sort"=>"USER_ID", "default"=>false),
));

$arSelectFields = $lAdmin->GetVisibleHeaderColumns();
if (!in_array('ID', $arSelectFields))
	$arSelectFields[] = 'ID';

$arSelectFieldsMap = array_fill_keys($arSelectFields, true);

$arUserList = array();
$arUserID = array();
$strNameFormat = CSite::GetNameFormat(true);

$arRows = array();

while ($arSTORE = $dbResultList->Fetch())
{
	$arSTORE['ID'] = intval($arSTORE['ID']);
	if (array_key_exists('USER_ID', $arSelectFieldsMap))
	{
		$arSTORE['USER_ID'] = intval($arSTORE['USER_ID']);
		if (0 < $arSTORE['USER_ID'])
			$arUserID[$arSTORE['USER_ID']] = true;
	}
	if (array_key_exists('MODIFIED_BY', $arSelectFieldsMap))
	{
		$arSTORE['MODIFIED_BY'] = intval($arSTORE['MODIFIED_BY']);
		if (0 < $arSTORE['MODIFIED_BY'])
			$arUserID[$arSTORE['MODIFIED_BY']] = true;
	}

	$arRows[$arSTORE['ID']] = $row =& $lAdmin->AddRow($arSTORE['ID'], $arSTORE);
	$row->AddField("ID", $arSTORE['ID']);
	if ($bReadOnly)
	{
		$row->AddInputField("TITLE", false);
		$row->AddInputField("ADDRESS", false);
		$row->AddInputField("DESCRIPTION", false);
	}
	else
	{
		$row->AddInputField("TITLE");
		$row->AddCheckField("ACTIVE");
		$row->AddInputField("ADDRESS", array("size" => "30"));
		$row->AddInputField("DESCRIPTION", array("size" => "50"));
		$row->AddInputField("PHONE", array("size" => "25"));
		$row->AddInputField("SCHEDULE", array("size" => "35"));

		if (!$bExport)
			$row->AddField("IMAGE_ID", CFile::ShowImage($arSTORE['IMAGE_ID'], 100, 100, "border=0", "", true));
	}

	if (array_key_exists('DATE_CREATE', $arSelectFieldsMap))
		$row->AddViewField("DATE_CREATE", $arSTORE['DATE_CREATE']);
	if (array_key_exists('DATE_MODIFY', $arSelectFieldsMap))
		$row->AddViewField("DATE_MODIFY", $arSTORE['DATE_MODIFY']);

	$arActions = array();
	$arActions[] = array("ICON"=>"edit", "TEXT"=>GetMessage("EDIT_STORE_ALT"), "ACTION"=>$lAdmin->ActionRedirect("cat_store_edit.php?ID=".$arSTORE['ID']."&lang=".LANGUAGE_ID."&".GetFilterParams("filter_").""), "DEFAULT"=>true);

	if (!$bReadOnly)
	{
		$arActions[] = array("SEPARATOR" => true);
		$arActions[] = array("ICON"=>"delete", "TEXT"=>GetMessage("DELETE_STORE_ALT"), "ACTION"=>"if(confirm('".GetMessage('DELETE_STORE_CONFIRM')."')) ".$lAdmin->ActionDoGroup($arSTORE['ID'], "delete"));
	}

	$row->AddActions($arActions);
}
if (isset($row))
	unset($row);

if (array_key_exists('USER_ID', $arSelectFieldsMap) || array_key_exists('MODIFIED_BY', $arSelectFieldsMap))
{
	if (!empty($arUserID))
	{
		$rsUsers = CUser::GetList(($by2 = 'ID'),($order2 = 'ASC'), array('ID' => implode(' || ', array_keys($arUserID))), array('FIELDS' => array('ID', 'LOGIN', 'NAME', 'LAST_NAME')));
		while ($arOneUser = $rsUsers->Fetch())
		{
			$arOneUser['ID'] = intval($arOneUser['ID']);
			$arUserList[$arOneUser['ID']] = CUser::FormatName($strNameFormat, $arOneUser);
		}
	}

	foreach ($arRows as &$row)
	{
		if (array_key_exists('USER_ID', $arSelectFieldsMap))
		{
			$strCreatedBy = '';
			if (0 < $row->arRes['USER_ID'] && array_key_exists($row->arRes['USER_ID'], $arUserList))
			{
				$strCreatedBy = '<a href="/bitrix/admin/user_edit.php?lang='.LANGUAGE_ID.'&ID='.$row->arRes['USER_ID'].'">'.$arUserList[$row->arRes['USER_ID']].'</a>';
			}
			$row->AddViewField("USER_ID", $strCreatedBy);
		}
		if (array_key_exists('MODIFIED_BY', $arSelectFieldsMap))
		{
			$strModifiedBy = '';
			if (0 < $row->arRes['MODIFIED_BY'] && array_key_exists($row->arRes['MODIFIED_BY'], $arUserList))
			{
				$strModifiedBy = '<a href="/bitrix/admin/user_edit.php?lang='.LANGUAGE_ID.'&ID='.$row->arRes['MODIFIED_BY'].'">'.$arUserList[$row->arRes['MODIFIED_BY']].'</a>';
			}
			$row->AddViewField("MODIFIED_BY", $strModifiedBy);
		}
	}
	if (isset($row))
		unset($row);
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
	$lAdmin->AddGroupActionTable(
		array(
			"delete" => GetMessage("MAIN_ADMIN_LIST_DELETE"),
		)
	);
}

if (!$bReadOnly && $bCanAdd)
{
	$aContext = array(
		array(
			"TEXT" => GetMessage("STORE_ADD_NEW"),
			"ICON" => "btn_new",
			"LINK" => "cat_store_edit.php?lang=".LANG,
			"TITLE" => GetMessage("STORE_ADD_NEW_ALT")
		),
	);
	$lAdmin->AddAdminContextMenu($aContext);
}

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("STORE_TITLE"));
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
?>

<?
$lAdmin->DisplayList();
?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>