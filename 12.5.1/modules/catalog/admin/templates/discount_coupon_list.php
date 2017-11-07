<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

global $APPLICATION;

/*
* B_ADMIN_SUBCOUPONS
* if defined and equal 1 - working, another die
* B_ADMIN_SUBCOUPONS_LIST - true/false
* if not defined - die
* if equal true - get list mode
* 	include prolog and epilog
* other - get simple html
*
* need variables
* 		$strSubElementAjaxPath - path for ajax
*		$intDiscountID - ID for filter
*		$strSubTMP_ID - string identifier for link with new product ($intSubPropValue = 0, in edit form send -1)
*
*
*created variables
*		$arSubElements - array subelements for product with ID = 0
*/
if ((false == defined('B_ADMIN_SUBCOUPONS')) || (1 != B_ADMIN_SUBCOUPONS))
	return '';
if (false == defined('B_ADMIN_SUBCOUPONS_LIST'))
	return '';

$strSubElementAjaxPath = trim($strSubElementAjaxPath);

if ($_REQUEST['mode']=='list' || $_REQUEST['mode']=='frame')
	CFile::DisableJSFunction(true);

$intDiscountID = intval($intDiscountID);
$strSubTMP_ID = intval($strSubTMP_ID);

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/catalog/admin/cat_discount_coupon.php");
IncludeModuleLangFile(__FILE__);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/iblock/classes/general/subelement.php');

$sTableID = "tbl_catalog_sub_coupon_".md5($strSubIBlockType.".".$intSubIBlockID);

$arHideFields = array('DISCOUNT_ID');
$lAdmin = new CAdminSubList($sTableID, false, $strSubElementAjaxPath, $arHideFields);

$arFilterFields = Array(
	"find_discount_id",
);

$lAdmin->InitFilter($arFilterFields);

$arFilter = Array(
	"DISCOUNT_ID" => $intDiscountID,
);

if (!($USER->CanDoOperation('catalog_read') || $USER->CanDoOperation('catalog_discount')))
	return '';

$boolCouponsReadOnly = (isset($boolCouponsReadOnly) && false === $boolCouponsReadOnly ? false : true);

if ($lAdmin->EditAction() && !$boolCouponsReadOnly)
{
	foreach ($_POST['FIELDS'] as $ID => $arFields)
	{
		$DB->StartTransaction();
		$ID = intval($ID);

		if (!$lAdmin->IsUpdated($ID))
			continue;

		if (!CCatalogDiscountCoupon::Update($ID, $arFields))
		{
			if ($ex = $APPLICATION->GetException())
				$lAdmin->AddUpdateError($ex->GetString(), $ID);
			else
				$lAdmin->AddUpdateError(str_replace("#ID#", $ID, GetMessage("ERROR_UPDATE_DISCOUNT_CPN")), $ID);

			$DB->Rollback();
		}

		$DB->Commit();
	}
}


if (($arID = $lAdmin->GroupAction()) && !$boolCouponsReadOnly)
{
	if ($_REQUEST['action_target']=='selected')
	{
		$arID = array();
		$dbResultList = CCatalogDiscountCoupon::GetList(
			array($by => $order),
			$arFilter,
			false,
			false,
			array("ID")
		);
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

				if (!CCatalogDiscountCoupon::Delete($ID))
				{
					$DB->Rollback();

					if ($ex = $APPLICATION->GetException())
						$lAdmin->AddGroupError($ex->GetString(), $ID);
					else
						$lAdmin->AddGroupError(str_replace("#ID#", $ID, GetMessage("ERROR_DELETE_DISCOUNT_CPN")), $ID);
				}

				$DB->Commit();

				break;

			case "activate":
			case "deactivate":

				$arFields = array(
					"ACTIVE" => (($_REQUEST['action']=="activate") ? "Y" : "N")
				);

				if (!CCatalogDiscountCoupon::Update($ID, $arFields))
				{
					if ($ex = $APPLICATION->GetException())
						$lAdmin->AddGroupError($ex->GetString(), $ID);
					else
						$lAdmin->AddGroupError(str_replace("#ID#", $ID, GetMessage("ERROR_UPDATE_DISCOUNT_CPN")), $ID);
				}

				break;
		}
	}
}

$CAdminCalendar_ShowScript = '';
if (true == B_ADMIN_SUBCOUPONS_LIST)
	$CAdminCalendar_ShowScript = CAdminCalendar::ShowScript();

$lAdmin->AddHeaders(array(
	array(
		"id" => "ID",
		"content" => "ID",
		"sort" => "ID",
		"default" => true
	),
	array(
		"id" => "ACTIVE",
		"content" => GetMessage("DSC_CPN_ACTIVE"),
		"sort" => "ACTIVE",
		"default" => true
	),
	array(
		"id" => "COUPON",
		"content" => GetMessage("DSC_CPN_CPN"),
		"sort" => "COUPON",
		"default" => true
	),
	array(
		"id" => "DATE_APPLY",
		"content" => GetMessage("DSC_CPN_DATE"),
		"sort" => "DATE_APPLY",
		"default" => true
	),
	array(
		"id" => "ONE_TIME",
		"content" => GetMessage("DSC_CPN_TIME2"),
		"sort" => "ONE_TIME",
		"default" => true
	),
	array(
		"id" => "DESCRIPTION",
		"content" => GetMessage("DSC_CPN_DESCRIPTION"),
		"sort" => "",
		"default" => false
	),
	array(
		"id" => "MODIFIED_BY",
		"content" => GetMessage('DSC_MODIFIED_BY'),
		"sort" => "MODIFIED_BY",
		"default" => true
	),
	array(
		"id" => "TIMESTAMP_X",
		"content" => GetMessage('DSC_TIMESTAMP_X'),
		"sort" => "TIMESTAMP_X",
		"default" => true
	),
	array(
		"id" => "CREATED_BY",
		"content" => GetMessage('DSC_CREATED_BY'),
		"sort" => "CREATED_BY",
		"default" => false
	),
	array(
		"id" => "DATE_CREATE",
		"content" => GetMessage('DSC_DATE_CREATE'),
		"sort" => "DATE_CREATE",
		"default" => false
	),
));

$arSelectFields = $lAdmin->GetVisibleHeaderColumns();
if (!in_array('ID', $arSelectFields))
	$arSelectFields[] = 'ID';

$arSelectFields = array_values($arSelectFields);
$arSelectFieldsMap = array_fill_keys($arSelectFields, true);

$arCouponType = CCatalogDiscountCoupon::GetCoupontTypes(true);

$arUserList = array();
$arUserID = array();
$strNameFormat = CSite::GetNameFormat(true);

if (!((false == B_ADMIN_SUBCOUPONS_LIST) && ($bCopy)))
{
	if (array_key_exists("mode", $_REQUEST) && $_REQUEST["mode"] == "excel")
		$arNavParams = false;
	else
		$arNavParams = array("nPageSize"=>CAdminSubResult::GetNavSize($sTableID, 20, $lAdmin->GetListUrl(true)));

	$dbResultList = CCatalogDiscountCoupon::GetList(
		array($by => $order),
		$arFilter,
		false,
		$arNavParams,
		$arSelectFields
	);
	$dbResultList = new CAdminSubResult($dbResultList, $sTableID, $lAdmin->GetListUrl(true));
	$dbResultList->NavStart();
	$lAdmin->NavText($dbResultList->GetNavPrint(htmlspecialcharsbx(GetMessage("DSC_NAV"))));

	$arRows = array();

	while ($arCouponDiscount = $dbResultList->Fetch())
	{
		$edit_url = '/bitrix/admin/cat_subcoupon_edit.php?ID='.$arCouponDiscount['ID'].'&DISCOUNT_ID='.$intDiscountID.'&lang='.LANGUAGE_ID.'&TMP_ID='.$strSubTMP_ID;

		$arCouponDiscount['ID'] = intval($arCouponDiscount['ID']);
		if (array_key_exists('CREATED_BY', $arSelectFieldsMap))
		{
			$arCouponDiscount['CREATED_BY'] = intval($arCouponDiscount['CREATED_BY']);
			if (0 < $arCouponDiscount['CREATED_BY'])
				$arUserID[$arCouponDiscount['CREATED_BY']] = true;
		}
		if (array_key_exists('MODIFIED_BY', $arSelectFieldsMap))
		{
			$arCouponDiscount['MODIFIED_BY'] = intval($arCouponDiscount['MODIFIED_BY']);
			if (0 < $arCouponDiscount['MODIFIED_BY'])
				$arUserID[$arCouponDiscount['MODIFIED_BY']] = true;
		}

		$arRows[$arCouponDiscount['ID']] = $row =& $lAdmin->AddRow($arCouponDiscount['ID'], $arCouponDiscount, $edit_url, '', true);

		if (array_key_exists('DATE_CREATE', $arSelectFieldsMap))
			$row->AddViewField("DATE_CREATE", $arCouponDiscount['DATE_CREATE']);
		if (array_key_exists('TIMESTAMP_X', $arSelectFieldsMap))
			$row->AddViewField("TIMESTAMP_X", $arCouponDiscount['TIMESTAMP_X']);

		$row->AddField("ID", $arCouponDiscount['ID']);
		if (array_key_exists('DISCOUNT_NAME', $arSelectFieldsMap))
			$row->AddEditField("DISCOUNT_NAME", false);

		if ($boolCouponsReadOnly)
		{
			if (array_key_exists('ACTIVE', $arSelectFieldsMap))
				$row->AddCheckField("ACTIVE", false);
			if (array_key_exists('ACTIVE', $arSelectFieldsMap))
				$row->AddEditField("COUPON", false);
			if (array_key_exists('DATE_APPLY', $arSelectFieldsMap))
				$row->AddCalendarField("DATE_APPLY", false);
			if (array_key_exists('ONE_TIME', $arSelectFieldsMap))
				$row->AddViewField("ONE_TIME", htmlspecialcharsex($arCouponType[$arCouponDiscount['ONE_TIME']]));
			if (array_key_exists('DESCRIPTION', $arSelectFieldsMap))
				$row->AddEditField("DESCRIPTION", false);
		}
		else
		{
			if (array_key_exists('ACTIVE', $arSelectFieldsMap))
				$row->AddCheckField("ACTIVE");
			if (array_key_exists('COUPON', $arSelectFieldsMap))
				$row->AddInputField("COUPON", array("size" => "30"));
			if (array_key_exists('DATE_APPLY', $arSelectFieldsMap))
				$row->AddCalendarField("DATE_APPLY", array("size" => "10"));
			if (array_key_exists('ONE_TIME', $arSelectFieldsMap))
				$row->AddSelectField("ONE_TIME", $arCouponType);
			if (array_key_exists('DESCRIPTION', $arSelectFieldsMap))
				$row->AddInputField("DESCRIPTION");
		}

		$arActions = array();
		$arActions[] = array(
			"ICON" => "edit",
			"TEXT" => GetMessage("DSC_UPDATE_ALT"),
			"DEFAULT" => true,
			"ACTION"=>"(new BX.CAdminDialog({
				'content_url': '/bitrix/admin/cat_subcoupon_edit.php?ID=".$arCouponDiscount['ID']."&DISCOUNT_ID=".$intDiscountID."&lang=".LANGUAGE_ID."&TMP_ID=".$strSubTMP_ID."',
				'content_post': 'bxpublic=Y',
				'draggable': true,
				'resizable': true,
				'buttons': [BX.CAdminDialog.btnSave, BX.CAdminDialog.btnCancel]
			})).Show();",
		);

		if (!$boolCouponsReadOnly)
		{
			$arActions[] = array("SEPARATOR" => true);
			$arActions[] = array("ICON"=>"delete", "TEXT"=>GetMessage("DSC_DELETE_ALT"), "ACTION"=>"if(confirm('".GetMessage('DSC_DELETE_CONF')."')) ".$lAdmin->ActionDoGroup($arCouponDiscount['ID'], "delete"));
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

	if (!$boolCouponsReadOnly)
	{
		$lAdmin->AddGroupActionTable(
			array(
				"delete" => GetMessage("MAIN_ADMIN_LIST_DELETE"),
				"activate" => GetMessage("MAIN_ADMIN_LIST_ACTIVATE"),
				"deactivate" => GetMessage("MAIN_ADMIN_LIST_DEACTIVATE"),
			)
		);
	}

?><script type="text/javascript">
function ShowNewCoupon(id)
{
	var PostParams = {
		'lang': '<? echo LANGUAGE_ID; ?>',
		'DISCOUNT_ID': id,
		'MULTI': 'N',
		'ID': 0,
		'bxpublic': 'Y',
		'sessid': BX.bitrix_sessid()
	};
	(new BX.CAdminDialog({
		'content_url': '/bitrix/admin/cat_subcoupon_edit.php',
		'content_post': PostParams,
		'draggable': true,
		'resizable': true,
		'buttons': [BX.CAdminDialog.btnSave, BX.CAdminDialog.btnCancel]
	})).Show();
}

function ShowNewMultiCoupons(id)
{
	var PostParams = {
		'lang': '<? echo LANGUAGE_ID; ?>',
		'DISCOUNT_ID': id,
		'MULTI': 'Y',
		'ID': 0,
		'bxpublic': 'Y',
		'sessid': BX.bitrix_sessid()
	};
	(new BX.CAdminDialog({
		'content_url': '/bitrix/admin/cat_subcoupon_edit.php',
		'content_post': PostParams,
		'draggable': true,
		'resizable': false,
		'buttons': [BX.CAdminDialog.btnSave, BX.CAdminDialog.btnCancel]
	})).Show();
}
</script><?

	$aContext = array();
	if (!$boolCouponsReadOnly)
	{
		if (0 < $intDiscountID)
		{
			$arAddMenu = array();
			$arAddMenu[] = array(
				"TEXT" => GetMessage("BT_CAT_DISC_COUPON_LIST_ADD_ONE_COUPON"),
				"LINK" => "javascript:ShowNewCoupon(".$intDiscountID.")",
				"TITLE" => GetMessage("BT_CAT_DISC_COUPON_LIST_ADD_ONE_COUPON_TITLE")
			);
			$arAddMenu[] = array(
				"TEXT" => GetMessage("BT_CAT_DISC_COUPON_LIST_ADD_MULTI_COUPON"),
				"LINK" => "javascript:ShowNewMultiCoupons(".$intDiscountID.")",
				"TITLE" => GetMessage("BT_CAT_DISC_COUPON_LIST_ADD_MULTI_COUPON_TITLE")
			);

			$aContext[] = array(
				"TEXT" => GetMessage("DSC_CPN_ADD"),
				"ICON" => "btn_new",
				"MENU" => $arAddMenu,
			);
		}
	}

	$aContext[] = array(
		"ICON"=>"btn_sub_refresh",
		"TEXT"=>htmlspecialcharsex(GetMessage('BT_CAT_DISC_COUPON_LIST_REFRESH')),
		"LINK" => "javascript:".$lAdmin->ActionAjaxReload($lAdmin->GetListUrl(true)),
		"TITLE"=>GetMessage("BT_CAT_DISC_COUPON_LIST_REFRESH_TITLE"),
	);

	$lAdmin->AddAdminContextMenu($aContext);

	$lAdmin->CheckListMode();

	if (true == B_ADMIN_SUBCOUPONS_LIST)
	{
		echo $CAdminCalendar_ShowScript;
	}

	$lAdmin->DisplayList(B_ADMIN_SUBCOUPONS_LIST);
}
else
{
	ShowMessage(GetMessage('BT_CAT_DISC_COUPON_LIST_SHOW_AFTER_COPY'));
}
?>