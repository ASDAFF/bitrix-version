<?php

// admin initialization
define("ADMIN_MODULE_NAME", "highloadblock");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

global $APPLICATION, $USER, $USER_FIELD_MANAGER;

IncludeModuleLangFile(__FILE__);

if (!$USER->IsAdmin())
{
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

if (!CModule::IncludeModule(ADMIN_MODULE_NAME))
{
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

use Bitrix\Highloadblock as HL;

// get entity settings
$hlblock = null;

if (isset($_REQUEST['ENTITY_ID']) && $_REQUEST['ENTITY_ID'] > 0)
{
	$hlblock = HL\HighloadBlockTable::getById($_REQUEST['ENTITY_ID'])->fetch();
}

if (empty($hlblock))
{
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

	echo GetMessage('HLBLOCK_ADMIN_ROWS_LIST_NOT_FOUND');

	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");

	die;
}

$APPLICATION->SetTitle(GetMessage('HLBLOCK_ADMIN_ROWS_LIST_PAGE_TITLE', array('#NAME#' => $hlblock['NAME'])));

$entity = HL\HighloadBlockTable::compileEntity($hlblock);

/** @var HL\DataManager $entity_data_class */
$entity_data_class = $entity->getDataClass();
$entity_table_name = $hlblock['TABLE_NAME'];

$sTableID = 'tbl_'.$entity_table_name;
$oSort = new CAdminSorting($sTableID, "ID", "asc");
$lAdmin = new CAdminList($sTableID, $oSort);

$arHeaders = array(array(
	'id' => 'ID',
	'content' => 'ID',
	'sort' => 'ID',
	'default' => true
));

$ufEntityId = 'HLBLOCK_'.$hlblock['ID'];
$USER_FIELD_MANAGER->AdminListAddHeaders($ufEntityId, $arHeaders);

// show all columns by default
foreach ($arHeaders as &$arHeader)
{
	$arHeader['default'] = true;
}
unset($arHeader);

$lAdmin->AddHeaders($arHeaders);

if (!in_array($by, $lAdmin->GetVisibleHeaderColumns(), true))
{
	$by = 'ID';
}

// add filter
$filter = null;

$filterFields = array('find_id');
$filterValues = array();
$filterTitles = array('ID');

$USER_FIELD_MANAGER->AdminListAddFilterFields($ufEntityId, $filterFields);

$filter = $lAdmin->InitFilter($filterFields);

if (!empty($find_id))
{
	$filterValues['ID'] = $find_id;
}

$USER_FIELD_MANAGER->AdminListAddFilter($ufEntityId, $filterValues);
$USER_FIELD_MANAGER->AddFindFields($ufEntityId, $filterTitles);

$filter = new CAdminFilter(
	$sTableID."_filter_id",
	$filterTitles
);


// group actions
if($lAdmin->EditAction())
{
	foreach($FIELDS as $ID=>$arFields)
	{
		$ID = IntVal($ID);

		if(!$lAdmin->IsUpdated($ID))
			continue;

		$entity_data_class::update($ID, $arFields);
	}
}

if($arID = $lAdmin->GroupAction())
{
	if($_REQUEST['action_target']=='selected')
	{
		$arID = array();

		$rsData = $entity_data_class::getList(array(
			"select" => array('ID'),
			"filter" => $filterValues
		));

		while($arRes = $rsData->Fetch())
			$arID[] = $arRes['ID'];
	}

	foreach ($arID as $ID)
	{
		$ID = intval($ID);

		if (!$ID)
		{
			continue;
		}

		switch($_REQUEST['action'])
		{
			case "delete":
				$entity_data_class::delete($ID);
				break;
		}
	}
}

$arr = array('delete' => true);
$lAdmin->AddGroupActionTable($arr);

// select data
/** @var string $order */
$rsData = $entity_data_class::getList(array(
	"select" => $lAdmin->GetVisibleHeaderColumns(),
	"filter" => $filterValues,
	"order" => array($by => strtoupper($order))
));

$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart();


// build list
$lAdmin->NavText($rsData->GetNavPrint(GetMessage("PAGES")));
while($arRes = $rsData->NavNext(true, "f_"))
{
	$row = $lAdmin->AddRow($f_ID, $arRes);
	$USER_FIELD_MANAGER->AddUserFields('HLBLOCK_'.$hlblock['ID'], $arRes, $row);

	$can_edit = true;

	$arActions = Array();

	$arActions[] = array(
		"ICON" => "edit",
		"TEXT" => GetMessage($can_edit ? "MAIN_ADMIN_MENU_EDIT" : "MAIN_ADMIN_MENU_VIEW"),
		"ACTION" => $lAdmin->ActionRedirect("highloadblock_row_edit.php?ENTITY_ID=".$hlblock['ID'].'&ID='.$f_ID),
		"DEFAULT" => true
	);

	$arActions[] = array(
		"ICON"=>"delete",
		"TEXT" => GetMessage("MAIN_ADMIN_MENU_DELETE"),
		"ACTION" => "if(confirm('".GetMessageJS('HLBLOCK_ADMIN_DELETE_ROW_CONFIRM')."')) ".
			$lAdmin->ActionRedirect("highloadblock_row_edit.php?action=delete&ENTITY_ID=".$hlblock['ID'].'&ID='.$f_ID.'&'.bitrix_sessid_get())
	);

	$row->AddActions($arActions);
}


// view
$lAdmin->AddAdminContextMenu(array(array(
	"TEXT"	=> GetMessage('HLBLOCK_ADMIN_ROWS_ADD_NEW_BUTTON'),
	"TITLE"	=> GetMessage('HLBLOCK_ADMIN_ROWS_ADD_NEW_BUTTON'),
	"LINK"	=> "highloadblock_row_edit.php?ENTITY_ID=".intval($_REQUEST['ENTITY_ID'])."&lang=".LANGUAGE_ID,
	"ICON"	=> "btn_new"
)));

$lAdmin->CheckListMode();

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

?>
<form name="find_form" method="GET" action="<?echo $APPLICATION->GetCurPage()?>?ENTITY_ID=<?=$hlblock['ID']?>">
<?
	$filter->Begin();
	?>
	<tr>
		<td>ID</td>
		<td><input type="text" name="find_id" size="47" value="<?echo htmlspecialcharsbx($find_id)?>"><?=ShowFilterLogicHelp()?></td>
	</tr>
	<?
	$USER_FIELD_MANAGER->AdminListShowFilter($ufEntityId);
	$filter->Buttons(array("table_id"=>$sTableID, "url"=>$APPLICATION->GetCurPage().'?ENTITY_ID='.$hlblock['ID'], "form"=>"find_form"));
	$filter->End();
?>
</form>
<?

$lAdmin->DisplayList();

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");