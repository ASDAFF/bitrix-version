<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/bizproc/include.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/bizproc/prolog.php");

IncludeModuleLangFile(__FILE__);

$fatalErrorMessage = "";

$sTableID = "tbl_bizproc_task_list";

$oSort = new CAdminSorting($sTableID, "ID", "DESC");
$lAdmin = new CAdminList($sTableID, $oSort);

$allowAdminAccess = $USER->IsAdmin();

$arFilterFields = array(
	"filter_modified_1",
	"filter_modified_2",
	"filter_name",
);
if ($allowAdminAccess)
	$arFilterFields[] = "filter_user_id";

$lAdmin->InitFilter($arFilterFields);

$arFilter = array();
if (!$allowAdminAccess)
	$arFilter["USER_ID"] = $USER->GetID();
elseif (strlen($filter_user_id) > 0)
	$arFilter["USER_ID"] = $filter_user_id;
if (strlen($filter_modified_1) > 0)
	$arFilter[">=MODIFIED"] = $filter_modified_1;
if (strlen($filter_modified_2) > 0)
	$arFilter["<=MODIFIED"] = $filter_modified_2;
if (strlen($filter_name) > 0)
	$arFilter["~NAME"] = "%".$filter_name."%";

$arAddHeaders = array(
	array("id" => "ID", "content" => "ID", "sort" => "ID", "default" => true),
	array("id" => "NAME", "content" => GetMessage("BPATL_NAME"), "sort" => "NAME", "default" => true),
	array("id" => "DESCRIPTION", "content" => GetMessage("BPATL_DESCR"), "default" => true),
	array("id" => "DESCRIPTION_FULL", "content" => GetMessage("BPATL_DESCR_FULL"), "default" => false),
	array("id" => "MODIFIED", "content" => GetMessage("BPATL_MODIFIED"), "sort" => "MODIFIED", "default" => true),
	array("id" => "WORKFLOW_NAME", "content" => GetMessage("BPATL_WORKFLOW_NAME"), "default" => true, "sort" => ""),
	array("id" => "WORKFLOW_STATE", "content" => GetMessage("BPATL_WORKFLOW_STATE"), "default" => true, "sort" => ""),
);
if ($allowAdminAccess)
	$arAddHeaders[] = array("id" => "USER", "content" => GetMessage("BPATL_USER"), "default" => true, "sort" => "");

$lAdmin->AddHeaders($arAddHeaders);

$arVisibleColumns = $lAdmin->GetVisibleHeaderColumns();

$arSelectFields = array("ID", "WORKFLOW_ID", "ACTIVITY", "ACTIVITY_NAME", "MODIFIED", "OVERDUE_DATE", "NAME", "DESCRIPTION", "PARAMETERS");
if (in_array("USER", $arVisibleColumns) && $allowAdminAccess)
	$arSelectFields[] = "USER_ID";

$dbResultList = CBPTaskService::GetList(
	array($by => $order),
	$arFilter,
	false,
	false,
	$arSelectFields
);

$dbResultList = new CAdminResult($dbResultList, $sTableID);
$dbResultList->NavStart();

$lAdmin->NavText($dbResultList->GetNavPrint(GetMessage("BPATL_NAV")));

while ($arResultItem = $dbResultList->NavNext(true, "f_"))
{
	$row =& $lAdmin->AddRow($f_ID, $arResultItem);

	$s = $allowAdminAccess ? "&uid=".intval($arResultItem["USER_ID"]) : "";
	$row->AddField(
		"ID",
		'<a href="bizproc_task.php?id='.$f_ID.$s.'&back_url='.urlencode($APPLICATION->GetCurPageParam("lang=".LANGUAGE_ID, array("lang"))).'" title="'.GetMessage("BPATL_VIEW").'">'.$f_ID.'</a>'
	);
	$row->AddField("NAME", $f_NAME);

	$description = $f_DESCRIPTION;
	if (strlen($description) > 100)
		$description = substr($description, 0, 97)."...";

	$row->AddField("DESCRIPTION", $description);
	$row->AddField("DESCRIPTION_FULL", $f_DESCRIPTION);
	$row->AddField("MODIFIED", $f_MODIFIED);

	if (count(array_intersect($arVisibleColumns, array("WORKFLOW_NAME", "WORKFLOW_STATE"))) > 0)
	{
		$arState = CBPStateService::GetWorkflowState($arResultItem["WORKFLOW_ID"]);
		$row->AddField("WORKFLOW_NAME", $arState["TEMPLATE_NAME"]);
		$row->AddField("WORKFLOW_STATE", $arState["STATE_TITLE"]);
	}

	if (in_array("USER", $arVisibleColumns))
	{
		$dbUserTmp = CUser::GetByID($arResultItem["USER_ID"]);
		if ($arUserTmp = $dbUserTmp->GetNext())
		{
			$str = CUser::FormatName(COption::GetOptionString("bizproc", "name_template", CSite::GetNameFormat(false), SITE_ID), $arUserTmp, true);
			$str .= " [".$arResultItem["USER_ID"]."]";
		}
		else
			$str = str_replace("#USER_ID#", $arResultItem["USER_ID"], GetMessage("BPATL_USER_NOT_FOUND"));
		$row->AddField("USER", $str);
	}

	$arActions = Array();
	$arActions[] = array(
		"ICON" => "edit",
		"TEXT" => GetMessage("BPATL_VIEW"),
		"ACTION" => $lAdmin->ActionRedirect('bizproc_task.php?id='.$f_ID.$s.'&back_url='.urlencode($APPLICATION->GetCurPageParam("lang=".LANGUAGE_ID, array("lang"))).''),
		"DEFAULT" => true
	);

	$row->AddActions($arActions);
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

//$lAdmin->AddGroupActionTable(
//	array(
//		"delete" => GetMessage("MAIN_ADMIN_LIST_DELETE"),
//	)
//);

if ($bizprocModulePermissions >= "W")
{
	$aContext = array(
//		array(
//			"TEXT" => GetMessage("SONET_ADD_NEW"),
//			"ICON" => "btn_new",
//			"LINK" => "socnet_subject_edit.php?lang=".LANG,
//			"TITLE" => GetMessage("SONET_ADD_NEW_ALT")
//		),
	);
	$lAdmin->AddAdminContextMenu($aContext);
}

$lAdmin->CheckListMode();


/****************************************************************************/
/***********  MAIN PAGE  ****************************************************/
/****************************************************************************/
$APPLICATION->SetTitle(GetMessage("BPATL_TITLE"));
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
?>
<form name="find_form" method="GET" action="<?echo $APPLICATION->GetCurPage()?>?">

<?
$ar = array(
	GetMessage("BPATL_F_MODIFIED"),
	GetMessage("BPATL_F_NAME"),
);
if ($allowAdminAccess)
	$ar[] = GetMessage("BPATL_USER_ID");

$oFilter = new CAdminFilter(
	$sTableID."_filter",
	$ar
);

$oFilter->Begin();
?>
	<tr>
		<td><?= GetMessage("BPATL_F_MODIFIED") ?>:</td>
		<td><?echo CalendarPeriod("filter_modified_1", htmlspecialcharsbx($filter_modified_1), "filter_modified_2", htmlspecialcharsbx($filter_modified_2), "find_form", "Y")?></td>
	</tr>
	<tr>
		<td><?= GetMessage("BPATL_F_NAME") ?>:</td>
		<td><input type="text" name="filter_name" value="<?echo htmlspecialcharsex($filter_name)?>" size="30">
		</td>
	</tr>
<?
if ($allowAdminAccess)
{
	?><tr>
		<td><?= GetMessage("BPATL_USER_ID") ?>:</td>
		<td><input type="text" name="filter_user_id" value="<?echo htmlspecialcharsex($filter_user_id)?>" size="5"></td>
	</tr><?
}
?>
<?
$oFilter->Buttons(
	array(
		"table_id" => $sTableID,
		"url" => $APPLICATION->GetCurPage(),
		"form" => "find_form"
	)
);
$oFilter->End();
?>
</form>

<?
$lAdmin->DisplayList();
?>

<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>
