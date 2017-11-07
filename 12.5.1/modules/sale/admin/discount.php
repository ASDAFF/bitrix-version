<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/include.php");

$saleModulePermissions = $APPLICATION->GetGroupRight("sale");
if ($saleModulePermissions < "W")
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

IncludeModuleLangFile(__FILE__);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/prolog.php");

$sTableID = "tbl_sale_discount";

$oSort = new CAdminSorting($sTableID, "ID", "asc");
$lAdmin = new CAdminList($sTableID, $oSort);

$arFilterFields = array(
	"filter_lang",
);

$lAdmin->InitFilter($arFilterFields);

$arFilter = array();

if ($filter_lang != "NOT_REF" && strlen($filter_lang) > 0)
	$arFilter["LID"] = $filter_lang;
else
	Unset($arFilter["LID"]);

if ($lAdmin->EditAction() && $saleModulePermissions >= "W")
{
	foreach ($FIELDS as $ID => $arFields)
	{
		$DB->StartTransaction();
		$ID = IntVal($ID);

		if (!$lAdmin->IsUpdated($ID))
			continue;

		if (!CSaleDiscount::Update($ID, $arFields))
		{
			if ($ex = $APPLICATION->GetException())
				$lAdmin->AddUpdateError($ex->GetString(), $ID);
			else
				$lAdmin->AddUpdateError(GetMessage("ERROR_UPDATE_REC")." (".$ID.", ".$arFields["LID"].", ".$arFields["NAME"].", ".$arFields["SORT"].")", $ID);

			$DB->Rollback();
		}
		else
		{
			$DB->Commit();
		}
	}
}

if (($arID = $lAdmin->GroupAction()) && $saleModulePermissions >= "W")
{
	if ($_REQUEST['action_target']=='selected')
	{
		$arID = Array();
		$dbResultList = CSaleDiscount::GetList($by, $order, $arFilter);
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

				if (!CSaleDiscount::Delete($ID))
				{
					$DB->Rollback();

					if ($ex = $APPLICATION->GetException())
						$lAdmin->AddGroupError($ex->GetString(), $ID);
					else
						$lAdmin->AddGroupError(GetMessage("BT_SALE_DISCOUNT_LIST_ERR_DELETE_DISCOUNT"), $ID);
				}
				else
				{
					$DB->Commit();
				}

				break;

			case "activate":
			case "deactivate":

				$arFields = array(
					"ACTIVE" => (($_REQUEST['action']=="activate") ? "Y" : "N")
				);

				if (!CSaleDiscount::Update($ID, $arFields))
				{
					if ($ex = $APPLICATION->GetException())
						$lAdmin->AddGroupError($ex->GetString(), $ID);
					else
						$lAdmin->AddGroupError(GetMessage("ERROR_UPDATE_REC"), $ID);
				}

				break;
		}
	}
}

$lAdmin->AddHeaders(array(
	array(
		"id" => "ID",
		"content" => GetMessage("PERS_TYPE_ID"),
		"sort" => "ID",
		"default" => true
	),
	array(
		"id" => "LID",
		"content" => GetMessage("PERS_TYPE_LID"),
		"sort" => "LID",
		"default" => true
	),
	array(
		"id" => "NAME",
		"content" => GetMessage('BT_SALE_DISCOUNT_ADM_TITLE_NAME'),
		"sort" => "",
		"default" => true
	),
	array(
		"id" => "ACTIVE",
		"content" => GetMessage('PERS_TYPE_ACTIVE'),
		"sort" => "ACTIVE",
		"default" => true
	),
	array(
		"id" => "PRIORITY",
		"content" => GetMessage('SDSN_PRIORITY'),
		"sort" => "PRIORITY",
		"default" => true
	),
	array(
		"id" => "SORT",
		"content" => GetMessage("PERS_TYPE_SORT"),
		"sort" => "SORT",
		"default"=>true
	),
	array(
		"id" => "LAST_DISCOUNT",
		"content" => GetMessage('SDSN_LAST_DISCOUNT'),
		"sort" => "LAST_DISCOUNT",
		"default" => true
	),
	array(
		"id" => "ACTIVE_FROM",
		"content" => GetMessage("SDSN_ACTIVE_FROM"),
		"sort" => "ACTIVE_FROM",
		"default" => true
	),
	array(
		"id" => "ACTIVE_TO",
		"content" => GetMessage("SDSN_ACTIVE_TO"),
		"sort" => "ACTIVE_TO",
		"default" => true
	),
	array(
		"id" => "MODIFIED_BY",
		"content" => GetMessage('SDSN_MODIFIED_BY'),
		"sort" => "MODIFIED_BY",
		"default" => true
	),
	array(
		"id" => "TIMESTAMP_X",
		"content" => GetMessage('SDSN_TIMESTAMP_X'),
		"sort" => "TIMESTAMP_X",
		"default" => true
	),
	array(
		"id" => "CREATED_BY",
		"content" => GetMessage('SDSN_CREATED_BY'),
		"sort" => "CREATED_BY",
		"default" => false
	),
	array(
		"id" => "DATE_CREATE",
		"content" => GetMessage('SDSN_DATE_CREATE'),
		"sort" => "DATE_CREATE",
		"default" => false
	),
	array(
		"id" => "XML_ID",
		"content" => GetMessage('SDSN_XML_ID'),
		"sort" => "XML_ID",
		"default" => false
	),
));

$arSelectFields = $lAdmin->GetVisibleHeaderColumns();
if (!in_array('ID', $arSelectFields))
	$arSelectFields[] = 'ID';

$arSelectFieldsMap = array_fill_keys($arSelectFields, true);

$arLangs = array();
$dbLangsList = CLang::GetList(($b = "sort"), ($o = "asc"));
while ($arLang = $dbLangsList->Fetch())
	$arLangs[$arLang["LID"]] = $arLang["LID"];

$arSelectFields = array_values($arSelectFields);

if (array_key_exists("mode", $_REQUEST) && $_REQUEST["mode"] == "excel")
	$arNavParams = false;
else
	$arNavParams = array("nPageSize"=>CAdminResult::GetNavSize($sTableID));

$dbResultList = CSaleDiscount::GetList(
	array($by => $order),
	$arFilter,
	false,
	$arNavParams,
	$arSelectFields
);

$dbResultList = new CAdminResult($dbResultList, $sTableID);
$dbResultList->NavStart();

$lAdmin->NavText($dbResultList->GetNavPrint(GetMessage("BT_SALE_DISCOUNT_LIST_MESS_NAV")));

$arUserList = array();
$arUserID = array();
$strNameFormat = CSite::GetNameFormat(true);

$arRows = array();

while ($arDiscount = $dbResultList->Fetch())
{
	$arDiscount['ID'] = intval($arDiscount['ID']);
	if (array_key_exists('CREATED_BY', $arSelectFieldsMap))
	{
		$arDiscount['CREATED_BY'] = intval($arDiscount['CREATED_BY']);
		if (0 < $arDiscount['CREATED_BY'])
			$arUserID[$arDiscount['CREATED_BY']] = true;
	}
	if (array_key_exists('MODIFIED_BY', $arSelectFieldsMap))
	{
		$arDiscount['MODIFIED_BY'] = intval($arDiscount['MODIFIED_BY']);
		if (0 < $arDiscount['MODIFIED_BY'])
			$arUserID[$arDiscount['MODIFIED_BY']] = true;
	}
	$arRows[$arDiscount['ID']] = $row =& $lAdmin->AddRow($arDiscount['ID'], $arDiscount, "sale_discount_edit.php?ID=".$arDiscount['ID']."&lang=".LANGUAGE_ID.GetFilterParams("filter_"), GetMessage("BT_SALE_DISCOUNT_LIST_MESS_EDIT_DISCOUNT"));

	if (array_key_exists('DATE_CREATE', $arSelectFieldsMap))
		$row->AddViewField("DATE_CREATE", $arDiscount['DATE_CREATE']);
	if (array_key_exists('TIMESTAMP_X', $arSelectFieldsMap))
		$row->AddViewField("TIMESTAMP_X", $arDiscount['TIMESTAMP_X']);

	$row->AddViewField("ID", '<a href="/bitrix/admin/sale_discount_edit.php?lang='.LANGUAGE_ID.'&ID='.$arDiscount["ID"].'">'.$arDiscount["ID"].'</a>');
	if (array_key_exists('LID', $arSelectFieldsMap))
		$row->AddSelectField("LID", $arLangs, array());
	if (array_key_exists('ACTIVE', $arSelectFieldsMap))
		$row->AddCheckField("ACTIVE");

	if (array_key_exists('NAME', $arSelectFieldsMap))
		$row->AddInputField("NAME", array("size" => "30"));

	if (array_key_exists('SORT', $arSelectFieldsMap))
		$row->AddInputField("SORT", array("size" => "3"));

	if (array_key_exists('ACTIVE_FROM', $arSelectFieldsMap))
		$row->AddCalendarField("ACTIVE_FROM");
	if (array_key_exists('ACTIVE_TO', $arSelectFieldsMap))
		$row->AddCalendarField("ACTIVE_TO");

	if (array_key_exists('PRIORITY', $arSelectFieldsMap))
		$row->AddInputField("PRIORITY");
	if (array_key_exists('LAST_DISCOUNT', $arSelectFieldsMap))
		$row->AddCheckField("LAST_DISCOUNT");

	if (array_key_exists('XML_ID', $arSelectFieldsMap))
		$row->AddInputField("XML_ID", array("size" => "20"));

	$arActions = array();
	$arActions[] = array(
		"ICON" => "edit",
		"TEXT" => GetMessage("BT_SALE_DISCOUNT_LIST_MESS_EDIT_DISCOUNT_SHORT"),
		"ACTION" => $lAdmin->ActionRedirect("sale_discount_edit.php?ID=".$arDiscount['ID']."&lang=".LANGUAGE_ID.GetFilterParams("filter_").""),
		"DEFAULT" => true
	);
	if ($saleModulePermissions >= "W")
	{
		$arActions[] = array("SEPARATOR" => true);
		$arActions[] = array(
			"ICON" =>" delete",
			"TEXT" => GetMessage("BT_SALE_DISCOUNT_LIST_MESS_DELETE_DISCOUNT_SHORT"),
			"ACTION" => "if(confirm('".GetMessage('BT_SALE_DISCOUNT_LIST_MESS_DELETE_DISCOUNT_CONFIRM')."')) ".$lAdmin->ActionDoGroup($arDiscount['ID'], "delete")
		);
	}

	$row->AddActions($arActions);
}
if (isset($row))
	unset($row);

if (array_key_exists('CREATED_BY', $arSelectFieldsMap) || array_key_exists('MODIFIED_BY', $arSelectFieldsMap))
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
		if (array_key_exists('CREATED_BY', $arSelectFieldsMap))
		{
			$strCreatedBy = '';
			if (0 < $row->arRes['CREATED_BY'] && array_key_exists($row->arRes['CREATED_BY'], $arUserList))
			{
				$strCreatedBy = '<a href="/bitrix/admin/user_edit.php?lang='.LANGUAGE_ID.'&ID='.$row->arRes['CREATED_BY'].'">'.$arUserList[$row->arRes['CREATED_BY']].'</a>';
			}
			$row->AddViewField("CREATED_BY", $strCreatedBy);
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

$lAdmin->AddGroupActionTable(
	array(
		"delete" => GetMessage("MAIN_ADMIN_LIST_DELETE"),
		"activate" => GetMessage("MAIN_ADMIN_LIST_ACTIVATE"),
		"deactivate" => GetMessage("MAIN_ADMIN_LIST_DEACTIVATE"),
	)
);

if ($saleModulePermissions >= "W")
{
	$siteLID = "";
	$arSiteMenu = array();
	$arSitesShop = array();
	$arSitesTmp = array();
	$rsSites = CSite::GetList($b="id", $o="asc", Array("ACTIVE" => "Y"));
	while ($arSite = $rsSites->GetNext())
	{
		$site = COption::GetOptionString("sale", "SHOP_SITE_".$arSite["ID"], "");
		if ($arSite["ID"] == $site)
		{
			$arSitesShop[] = array("ID" => $arSite["ID"], "NAME" => $arSite["NAME"]);
		}
		$arSitesTmp[] = array("ID" => $arSite["ID"], "NAME" => $arSite["NAME"]);
	}

	if (empty($arSitesShop))
	{
		$arSitesShop = $arSitesTmp;
	}

	if (1 === count($arSitesShop))
	{
		$siteLID = "&LID=".$arSitesShop[0]["ID"];
	}
	else
	{
		foreach ($arSitesShop as $key => $val)
		{
			$arSiteMenu[] = array(
				"TEXT" => $val["NAME"]." (".$val["ID"].")",
				"ACTION" => "window.location = 'sale_discount_edit.php?lang=".LANGUAGE_ID."&LID=".$val["ID"]."';"
			);
		}
	}
	$aContext = array(
		array(
			"TEXT" => GetMessage("BT_SALE_DISCOUNT_LIST_MESS_NEW_DISCOUNT"),
			"ICON" => "btn_new",
			"LINK" => "sale_discount_edit.php?lang=".LANGUAGE_ID.$siteLID,
			"TITLE" => GetMessage("BT_SALE_DISCOUNT_LIST_MESS_NEW_DISCOUNT_TITLE"),
			"MENU" => $arSiteMenu
		),
	);

	$lAdmin->AddAdminContextMenu($aContext);
}

$lAdmin->CheckListMode();


$APPLICATION->SetTitle(GetMessage("BT_SALE_DISCOUNT_LIST_MESS_TITLE"));
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
?>
<form name="find_form" method="GET" action="<?echo $APPLICATION->GetCurPage()?>?">
<?
$oFilter = new CAdminFilter(
	$sTableID."_filter",
	array()
);

$oFilter->Begin();
?>
	<tr>
		<td><?echo GetMessage("LANG_FILTER_NAME")?>:</td>
		<td><?echo CLang::SelectBox("filter_lang", $filter_lang, GetMessage("DS_ALL")) ?>
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

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>