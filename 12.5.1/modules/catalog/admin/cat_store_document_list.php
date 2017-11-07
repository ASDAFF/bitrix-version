<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

global $APPLICATION;
global $DB;
global $USER;

if (!($USER->CanDoOperation('catalog_read') || $USER->CanDoOperation('catalog_store')))
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
$bReadOnly = !$USER->CanDoOperation('catalog_store');

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/include.php");

if ($ex = $APPLICATION->GetException())
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

	$strError = $ex->GetString();
	ShowError($strError);

	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
	die();
}

IncludeModuleLangFile(__FILE__);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/prolog.php");
ClearVars();

$sTableID = "b_catalog_store_docs";

$order = ($_REQUEST["order"]) ? $_REQUEST["order"] : 'desc';
$oSort = new CAdminSorting($sTableID, "ID", "desc");
$lAdmin = new CAdminList($sTableID, $oSort);

$errorMessage = "";
$bVarsFromForm = false;

$ID = IntVal($_REQUEST["ID"]);
$TAB_TITLE = (isset($TAB_TITLE)) ? GetMessage("CAT_DOC_$TAB_TITLE") : GetMessage("CAT_DOC_NEW");

$str_ACTIVE = "Y";

/** For a given contractor ID, issues generated title.
 * @param $contractorId
 * @return int
 */
function getContractorTitle($contractorId)
{
	static $dbContractors = '';
	static $arContractors = array();
	$contractorId = $contractorTitle = intval($contractorId);

	if($dbContractors === '')
	{
		$dbContractors = CCatalogContractor::GetList(array());
		while($arContractor = $dbContractors -> Fetch())
			$arContractors[] = $arContractor;
	}

	foreach($arContractors as $arContractor)
	{
		if($arContractor["ID"] == $contractorId)
		{
			$contractorTitle = $arContractor["COMPANY"];
		}
	}
	return $contractorTitle;
}

/** For a given site ID, issues generated site title.
 * @param $siteId
 * @return string
 */
function getSiteTitle($siteId)
{
	static $rsSites = '';
	static $arSitesShop = array();
	$siteTitle = $siteId;

	if($rsSites === '')
	{
		$rsSites = CSite::GetList($b="id", $o="asc", Array("ACTIVE" => "Y"));
		while ($arSite = $rsSites->GetNext())
			$arSitesShop[] = array("ID" => $arSite["ID"], "NAME" => $arSite["NAME"]);
	}

	foreach($arSitesShop as $arSite)
	{
		if($arSite["ID"] == $siteId)
		{
			$siteTitle = $arSite["NAME"]." (".$arSite["ID"].")";
		}
	}
	return $siteTitle;
}

if($bVarsFromForm)
	$DB->InitTableVarsForEdit("b_catalog_store_docs", "", "str_");

$documentTypes = CCatalogDocs::$types;
$arSiteMenu = array();

foreach($documentTypes as $type => $class)
	$arSiteMenu[] = array(
		"TEXT" => GetMessage("CAT_DOC_".$type),
		"ACTION" => "window.location = 'cat_store_document_edit.php?lang=".LANG."&DOCUMENT_TYPE=".$type."';"
	);

$aContext = array(
	array(
		"TEXT" => GetMessage("CAT_DOC_ADD"),
		"ICON" => "btn_new",
		"LINK" => "cat_store_document_edit.php?lang=".LANG.$siteLID,
		"TITLE" =>  GetMessage("CAT_DOC_ADD_TITLE"),
		"MENU" => $arSiteMenu
	),
);

$lAdmin->AddAdminContextMenu($aContext);

$arFilterFields = array(
	"filter_site_id",
	"filter_doc_type",
	"filter_contractor_id",
	"filter_status",
	"filter_date_document_from",
	"filter_date_document_to",
);
$lAdmin->InitFilter($arFilterFields);

$arFilter = array();
if (strlen($_REQUEST["filter_site_id"]) > 0 && $_REQUEST["filter_site_id"] != "NOT_REF") $arFilter["SITE_ID"] = $_REQUEST["filter_site_id"];
if (strlen($_REQUEST["filter_doc_type"]) > 0) $arFilter["DOC_TYPE"] = $_REQUEST["filter_doc_type"];
if (strlen($_REQUEST["filter_date_document_from"]) > 0) $arFilter["!<DATE_DOCUMENT"] = $_REQUEST["filter_date_document_from"];
if (strlen($_REQUEST["filter_date_document_to"])>0)
{
	if ($arDate = ParseDateTime($_REQUEST["filter_date_document_to"], CSite::GetDateFormat("FULL", SITE_ID)))
	{
		if (StrLen($_REQUEST["filter_date_document_to"]) < 11)
		{
			$arDate["HH"] = 23;
			$arDate["MI"] = 59;
			$arDate["SS"] = 59;
		}

		$filter_date_document_to = date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL", SITE_ID)), mktime($arDate["HH"], $arDate["MI"], $arDate["SS"], $arDate["MM"], $arDate["DD"], $arDate["YYYY"]));
		$arFilter["!>DATE_DOCUMENT"] = $filter_date_document_to;
	}
	else
	{
		$filter_date_document_to = "";
	}
}
if (strlen($_REQUEST["filter_status"]) > 0) $arFilter["STATUS"] = $_REQUEST["filter_status"];
if (strlen($_REQUEST["filter_contractor_id"]) > 0) $arFilter["CONTRACTOR_ID"] = $_REQUEST["filter_contractor_id"];
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
		$dbResultList = CCatalogDocs::GetList(array($_REQUEST["by"] => $order), $arFilter);
		while ($arResult = $dbResultList->Fetch())
		{

			if($arResult['STATUS'] !== 'Y')
				$arID[] = $arResult['ID'];
		}
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

				if (!CCatalogDocs::Delete($ID))
				{
					$DB->Rollback();

					if ($ex = $APPLICATION->GetException())
						$lAdmin->AddGroupError($ex->GetString(), $ID);
					else
						$lAdmin->AddGroupError(GetMessage("ERROR_DELETING_TYPE"), $ID);
				}
				$DB->Commit();
				break;
			case "conduct":
				$DB->StartTransaction();
				$result = CCatalogDocs::conductDocument($ID, $userId);
				if($result == true)
					$DB->Commit();
				else
					$DB->Rollback();

				if($ex = $APPLICATION->GetException())
				{
					$strError = $ex->GetString();
					if(!empty($result) && is_array($result))
					{
						$strError .= CCatalogStoreControlUtil::showErrorProduct($result);
					}
					$lAdmin->AddGroupError($strError, $ID);
				}
				break;
			case "cancellation":
				$DB->StartTransaction();
				$result = CCatalogDocs::cancellationDocument($ID, $userId);
				if($result == true)
					$DB->Commit();
				else
					$DB->Rollback();

				if($ex = $APPLICATION->GetException())
				{
					$strError = $ex->GetString();
					if(!empty($result) && is_array($result))
					{
						$strError .= CCatalogStoreControlUtil::showErrorProduct($result);
					}
					$lAdmin->AddGroupError($strError, $ID);
				}
				break;
		}
	}
}

$arSelect = array(
	"ID",
	"DOC_TYPE",
	"DATE_DOCUMENT",
	"CREATED_BY",
	"SITE_ID",
	"CONTRACTOR_ID",
	"STATUS",
	"CURRENCY",
	"TOTAL"
);
$by = ($_REQUEST["by"]) ? $_REQUEST["by"] : 'ID';
$order = ($_REQUEST["order"]) ? $_REQUEST["order"] : 'desc';

if (array_key_exists("mode", $_REQUEST) && $_REQUEST["mode"] == "excel")
	$arNavParams = false;
else
	$arNavParams = array("nPageSize"=>CAdminResult::GetNavSize($sTableID));

$dbResultList = CCatalogDocs::GetList(
	array($by => $order),
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
	array("id"=>"DOC_TYPE", "content"=>GetMessage("CAT_DOC_TYPE"), "sort"=>"DOC_TYPE", "default"=>true),
	array("id"=>"STATUS", "content"=>GetMessage("CAT_DOC_STATUS"), "sort"=>"STATUS", "default"=>true),
	array("id"=>"DATE_DOCUMENT","content"=>GetMessage("CAT_DOC_DATE_CREATE"), "sort"=>"DATE_DOCUMENT", "default"=>true),
	array("id"=>"CREATED_BY", "content"=>GetMessage("CAT_DOC_CREATOR"),  "sort"=>"CREATED_BY", "default"=>true),
	array("id"=>"CONTRACTOR_ID", "content"=>GetMessage("CAT_DOC_CONTRACTOR"),  "sort"=>"CONTRACTOR_ID", "default"=>true),
	array("id"=>"SITE_ID", "content"=>GetMessage("CAT_DOC_SITE_ID"),  "sort"=>"SITE_ID", "default"=>true),
	array("id"=>"CURRENCY", "content"=>GetMessage("CAT_DOC_CURRENCY"),  "sort"=>"CURRENCY", "default"=>true),
	array("id"=>"TOTAL", "content"=>GetMessage("CAT_DOC_TOTAL"),  "sort"=>"TOTAL", "default"=>true),
));

$arVisibleColumns = $lAdmin->GetVisibleHeaderColumns();
$arUserList = array();
$strNameFormat = CSite::GetNameFormat(true);

while ($arDOCUMENT = $dbResultList->NavNext(true, "f_"))
{
	$bAllowForEdit = true;
	$strForAction = "EDIT";
	$contractorTitle = '';

	$row =& $lAdmin->AddRow($f_ID, $arDOCUMENT);
	$row->AddField("ID", $f_ID);

	$f_DOC_TYPE = GetMessage("CAT_DOC_".$f_DOC_TYPE);

	if($f_STATUS == "Y")
	{
		$strForAction = "VIEW";
		$bAllowForEdit = false;
	}

	$f_STATUS = GetMessage("CAT_DOC_EXECUTION_".$f_STATUS);

	if(intval($f_CONTRACTOR_ID) > 0)
		$contractorTitle = '<a href="/bitrix/admin/cat_contractor_edit.php?lang='.LANGUAGE_ID.'&ID='.$f_CONTRACTOR_ID.'">'.htmlspecialcharsbx(getContractorTitle($f_CONTRACTOR_ID)).'</a>';

	$f_SITE_ID = getSiteTitle($f_SITE_ID);

	$strCreatedBy = '';
	$strModifiedBy = '';

	$f_CREATED_BY = intval($f_CREATED_BY);
	if ($f_CREATED_BY > 0)
	{
		if (!array_key_exists($f_CREATED_BY, $arUserList))
		{
			$rsUsers = CUser::GetList($_REQUEST["by2"] = 'ID', $_REQUEST["order2"] = 'ASC', array('ID_EQUAL_EXACT' => $f_CREATED_BY),array('FIELDS' => array('ID', 'LOGIN', 'NAME', 'LAST_NAME')));
			if ($arOneUser = $rsUsers->Fetch())
			{
				$arOneUser['ID'] = intval($arOneUser['ID']);
				$arUserList[$arOneUser['ID']] = CUser::FormatName($strNameFormat, $arOneUser);
			}
		}
		if (isset($arUserList[$f_CREATED_BY]))
			$strCreatedBy = '<a href="/bitrix/admin/user_edit.php?lang='.LANGUAGE_ID.'&ID='.$f_CREATED_BY.'">'.($arUserList[$f_CREATED_BY]).'</a>';
	}

	if(is_callable("CurrencyFormatNumber"))
		$f_TOTAL = ($f_CURRENCY) ? CurrencyFormatNumber(doubleval($f_TOTAL), $f_CURRENCY) : '';

	$row->AddViewField("DOC_TYPE", $f_DOC_TYPE);
	$row->AddViewField("STATUS", $f_STATUS);
	$row->AddViewField("DATE_CREATE", $f_DATE_CREATE);
	$row->AddViewField("CREATED_BY", $strCreatedBy);
	$row->AddViewField("CONTRACTOR_ID", $contractorTitle);
	$row->AddViewField("SITE_ID", $f_SITE_ID);
	$row->AddViewField("TOTAL", $f_TOTAL);


	$arActions = Array();

	$arActions[] = array("ICON"=>"edit", "TEXT"=>GetMessage("CAT_DOC_".$strForAction), "ACTION"=>$lAdmin->ActionRedirect("cat_store_document_edit.php?ID=".$f_ID."&lang=".LANG/*."&".GetFilterParams("filter_").""*/), "DEFAULT"=>true);

	if (!$bReadOnly && $bAllowForEdit)
	{
		$arActions[] = array("ICON"=>"pack", "TEXT"=>GetMessage("CAT_DOC_CONDUCT"), "ACTION"=>$lAdmin->ActionDoGroup($f_ID, "conduct"));
		$arActions[] = array("SEPARATOR" => true);
		$arActions[] = array("ICON"=>"delete", "TEXT"=>GetMessage("CAT_DOC_DELETE"), "ACTION"=>"if(confirm('".GetMessage('CAT_DOC_DELETE_CONFIRM')."')) ".$lAdmin->ActionDoGroup($f_ID, "delete"));
	}
	elseif(!$bAllowForEdit)
	{
		$arActions[] = array("ICON"=>"unpack", "TEXT"=>GetMessage("CAT_DOC_CANCELLATION"), "ACTION"=>$lAdmin->ActionDoGroup($f_ID, "cancellation"));
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

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("CAT_DOCS"));
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
				GetMessage("CAT_DOC_SITE_ID"),
				GetMessage("CAT_DOC_TYPE"),
				GetMessage("CAT_DOC_DATE"),
				GetMessage("CAT_DOC_CONTRACTOR"),
				GetMessage("CAT_DOC_STATUS"),
			)
		);

		$oFilter->Begin();
		?>
		<tr>
			<td><?= GetMessage("CAT_DOC_SITE_ID") ?>:</td>
			<td>
				<?echo CSite::SelectBox("filter_site_id", $filter_site_id, "(".GetMessage("CAT_DOC_SITE_ID").")"); ?>
			</td>
		</tr>
		<tr>
			<td><?= GetMessage("CAT_DOC_TYPE") ?>:</td>
			<td>
				<select name="filter_doc_type">
					<option value=""><?= htmlspecialcharsex("(".GetMessage("CAT_DOC_TYPE").")") ?></option>

					<?
					foreach($documentTypes as $type => $class)
					{
						?>
						<option value="<?=$type?>"<?if ($_REQUEST["filter_doc_type"] == $type) echo " selected"?>><?= htmlspecialcharsex(GetMessage("CAT_DOC_".$type)) ?></option>
					<?
					}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<td><?= GetMessage("CAT_DOC_DATE") ?> (<?= CSite::GetDateFormat("SHORT") ?>):</td>
			<td>
				<?echo CalendarPeriod("filter_date_document_from", $_REQUEST["filter_date_document_from"], "filter_date_document_to", $_REQUEST["filter_date_document_to"], "find_form", "Y")?>
			</td>
		</tr>
		<tr>
			<td><?= GetMessage("CAT_DOC_CONTRACTOR") ?>:</td>
			<td>
				<select name="filter_contractor_id">
					<option value=""><?= htmlspecialcharsex("(".GetMessage("CAT_DOC_CONTRACTOR").")") ?></option>

					<?
					foreach($arContractors as $arContractor)
					{
						?>
						<option value="<?=$arContractor["ID"]?>"<?if ($_REQUEST["filter_doc_type"] == $arContractor["ID"]) echo " selected"?>><?= htmlspecialcharsbx(getContractorTitle($arContractor["ID"])) ?></option>
					<?
					}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<td><?= GetMessage("CAT_DOC_STATUS") ?>:</td>
			<td>
				<select name="filter_status">
					<option value=""><?= htmlspecialcharsex("(".GetMessage("CAT_DOC_STATUS").")") ?></option>
					<option value="Y"<?if ($filter_status == "Y") echo " selected"?>><?= htmlspecialcharsex(GetMessage("CAT_DOC_EXECUTION_Y")) ?></option>
					<option value="N"<?if ($filter_status == "N") echo " selected"?>><?= htmlspecialcharsex(GetMessage("CAT_DOC_EXECUTION_N")) ?></option>
				</select>
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
//echo bitrix_sessid_post();
if(strlen($errorMessage) > 0)
	CAdminMessage::ShowMessage($errorMessage);?>

<?
?>


<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>