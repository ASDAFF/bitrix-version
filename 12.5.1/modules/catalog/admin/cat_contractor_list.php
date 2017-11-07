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

$bExport = false;
if($_REQUEST["mode"] == "excel")
	$bExport = true;

if ($ex = $APPLICATION->GetException())
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

	$strError = $ex->GetString();
	ShowError($strError);

	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
	die();
}

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/prolog.php");

$sTableID = "b_catalog_contractor";
$oSort = new CAdminSorting($sTableID, "ID", "asc");
$lAdmin = new CAdminList($sTableID, $oSort);
$arFilterFields = array(
	"filter_contractor_title",
	"filter_contractor_type",
	"filter_phone",
	"filter_email",
	"filter_inn",
	"filter_kpp",
);
$lAdmin->InitFilter($arFilterFields);
$arFilter = array();

if (strlen($_REQUEST["filter_contractor_title"]) > 0) $arFilter["COMPANY"] = $_REQUEST["filter_contractor_title"];
if (strlen($_REQUEST["filter_contractor_type"]) > 0) $arFilter["PERSON_TYPE"] = $_REQUEST["filter_contractor_type"];
if (strlen($_REQUEST["filter_phone"]) > 0) $arFilter["PHONE"] = $_REQUEST["filter_phone"];
if (strlen($_REQUEST["filter_email"]) > 0) $arFilter["EMAIL"] = $_REQUEST["filter_email"];
if (strlen($_REQUEST["filter_inn"]) > 0) $arFilter["INN"] = $_REQUEST["filter_inn"];
if (strlen($_REQUEST["filter_kpp"]) > 0) $arFilter["KPP"] = $_REQUEST["filter_kpp"];

if ($lAdmin->EditAction() && !$bReadOnly)
{
	foreach ($_POST['FIELDS'] as $ID => $arFields)
	{
		$DB->StartTransaction();
		$ID = IntVal($ID);
		$arFields['ID']=$ID;
		if (!$lAdmin->IsUpdated($ID))
			continue;

		if (!CCatalogContractor::Update($ID, $arFields))
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
		$dbResultList = CCatalogContractor::GetList(array($_REQUEST["by"] => $_REQUEST["order"]), $arFilter);
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

				if (!CCatalogContractor::Delete($ID))
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
	"PERSON_TYPE",
	"EMAIL",
	"PHONE",
	"POST_INDEX",
	"COUNTRY",
	"CITY",
	"COMPANY",
	"INN",
	"KPP",
	"ADDRESS",
);

if (array_key_exists("mode", $_REQUEST) && $_REQUEST["mode"] == "excel")
	$arNavParams = false;
else
	$arNavParams = array("nPageSize"=>CAdminResult::GetNavSize($sTableID));

$dbResultList = CCatalogContractor::GetList(
	array($_REQUEST["by"] => $_REQUEST["order"]),
	$arFilter,
	false,
	$arNavParams,
	$arSelect
);
$dbResultList = new CAdminResult($dbResultList, $sTableID);
$dbResultList->NavStart();
$lAdmin->NavText($dbResultList->GetNavPrint(GetMessage("group_admin_nav")));

$lAdmin->AddHeaders(array(
	array("id"=>"ID", "content"=>"ID", "sort"=>"ID", "default"=>true),
	array("id"=>"COMPANY", "content"=>GetMessage("CONTRACTOR_TITLE"),  "sort"=>"COMPANY", "default"=>true),
	array("id"=>"EMAIL", "content"=>GetMessage("CONTRACTOR_EMAIL"),  "sort"=>"EMAIL", "default"=>true),
	array("id"=>"PHONE", "content"=>GetMessage("CONTRACTOR_PHONE"),  "sort"=>"PHONE", "default"=>false),
	array("id"=>"POST_INDEX", "content"=>GetMessage("CONTRACTOR_POST_INDEX"),  "sort"=>"POST_INDEX", "default"=>false),
	//array("id"=>"COUNTRY", "content"=>GetMessage("CONTRACTOR_COUNTRY"),  "sort"=>"COUNTRY", "default"=>false),
	array("id"=>"INN", "content"=>GetMessage("CONTRACTOR_INN"),  "sort"=>"INN", "default"=>false),
	array("id"=>"KPP", "content"=>GetMessage("CONTRACTOR_KPP"),  "sort"=>"KPP", "default"=>false),
	array("id"=>"ADDRESS", "content"=>GetMessage("CONTRACTOR_ADDRESS"),  "sort"=>"ADDRESS", "default"=>true),
));

$arVisibleColumns = $lAdmin->GetVisibleHeaderColumns();

while ($arCONTRACTOR = $dbResultList->NavNext(true, "f_"))
{
	$row =& $lAdmin->AddRow($f_ID, $arCONTRACTOR);
	$row->AddField("ID", $f_ID);
	if ($bReadOnly)
	{
		$row->AddViewField("COMPANY", $f_COMPANY);
		$row->AddViewField("EMAIL", $f_EMAIL);

	}
	else
	{
		$row->AddInputField("EMAIL", array("size" => "30"));
		$row->AddInputField("PHONE", array("size" => "25"));
		$row->AddInputField("ADDRESS", array("size" => "40"));
	}

	$arActions = Array();
	$arActions[] = array("ICON"=>"edit", "TEXT"=>GetMessage("EDIT_CONTRACTOR_ALT"), "ACTION"=>$lAdmin->ActionRedirect("cat_contractor_edit.php?ID=".$f_ID."&lang=".LANG."&".GetFilterParams("filter_").""), "DEFAULT"=>true);

	if (!$bReadOnly)
	{
		$arActions[] = array("SEPARATOR" => true);
		$arActions[] = array("ICON"=>"delete", "TEXT"=>GetMessage("DELETE_CONTRACTOR_ALT"), "ACTION"=>"if(confirm('".GetMessage('DELETE_CONTRACTOR_CONFIRM')."')) ".$lAdmin->ActionDoGroup($f_ID, "delete"));
	}

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

if (!$bReadOnly)
{
	$lAdmin->AddGroupActionTable(
		array(
			"delete" => GetMessage("MAIN_ADMIN_LIST_DELETE"),
		)
	);
}

if (!$bReadOnly)
{
	$aContext = array(
		array(
			"TEXT" => GetMessage("CONTRACTOR_ADD_NEW"),
			"ICON" => "btn_new",
			"LINK" => "cat_contractor_edit.php?lang=".LANG,
			"TITLE" => GetMessage("CONTRACTOR_ADD_NEW_ALT")
		),
	);
	$lAdmin->AddAdminContextMenu($aContext);
}

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("CONTRACTOR_PAGE_TITLE"));
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
?>
	<form name="find_form" method="GET" action="<?echo $APPLICATION->GetCurPage()?>?">
		<?
		$arContractors = array();
		$dbContractors = CCatalogContractor::getList(array());
		while($arContractorRes = $dbContractors->Fetch())
		{
			$arContractors[] = $arContractorRes;
		}

		$oFilter = new CAdminFilter(
			$sTableID."_filter",
			array(
				GetMessage("CONTRACTOR_TYPE"),
				GetMessage("CONTRACTOR_TITLE"),
				GetMessage("CONTRACTOR_EMAIL"),
				GetMessage("CONTRACTOR_PHONE"),
				GetMessage("CONTRACTOR_INN"),
				GetMessage("CONTRACTOR_KPP"),
			)
		);

		$oFilter->Begin();
		?>
		<tr>
			<td><? echo GetMessage("CONTRACTOR_TYPE") ?>:</td>
			<td>
				<select name="filter_contractor_type">
					<option value=""><? echo htmlspecialcharsex("(".GetMessage("CONTRACTOR_TYPE").")") ?></option>
					<option value="1"<?if($_REQUEST["filter_contractor_type"] == "1") echo " selected"?>><? echo htmlspecialcharsex(GetMessage("CONTRACTOR_INDIVIDUAL")) ?></option>
					<option value="2"<?if($_REQUEST["filter_contractor_type"] == "2") echo " selected"?>><? echo htmlspecialcharsex(GetMessage("CONTRACTOR_JURIDICAL")) ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<td><?= GetMessage("CONTRACTOR_TITLE") ?>:</td>
			<td>
				<select name="filter_contractor_title">
					<option value=""><?= htmlspecialcharsex("(".GetMessage("CONTRACTOR_TITLE").")") ?></option>

					<?
					foreach($arContractors as $arContractor)
					{
						?>
						<option value="<?=$arContractor["COMPANY"]?>"<?if ($_REQUEST["filter_contractor_title"] == $arContractor["COMPANY"]) echo " selected"?>><?= htmlspecialcharsbx(($arContractor["COMPANY"])) ?></option>
					<?
					}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<td><? echo GetMessage("CONTRACTOR_EMAIL") ?>:</td>
			<td>
				<input type="text" name="filter_email" value="<?echo htmlspecialcharsbx($_REQUEST["filter_email"])?>" />
			</td>
		</tr>
		<tr>
			<td><? echo GetMessage("CONTRACTOR_PHONE") ?>:</td>
			<td>
				<input type="text" name="filter_phone" value="<?echo htmlspecialcharsbx($_REQUEST["filter_phone"])?>" />
			</td>
		</tr>
		<tr>
			<td><? echo GetMessage("CONTRACTOR_INN") ?>:</td>
			<td>
				<input type="text" name="filter_inn" value="<?echo htmlspecialcharsbx($_REQUEST["filter_inn"])?>" />
			</td>
		</tr>
		<tr>
			<td><? echo GetMessage("CONTRACTOR_KPP") ?>:</td>
			<td>
				<input type="text" name="filter_kpp" value="<?echo htmlspecialcharsbx($_REQUEST["filter_kpp"])?>" />
			</td>
		</tr>


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

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>