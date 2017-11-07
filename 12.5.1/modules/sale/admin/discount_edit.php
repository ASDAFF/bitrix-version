<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

$saleModulePermissions = $APPLICATION->GetGroupRight("sale");
if ($saleModulePermissions < "W")
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

IncludeModuleLangFile(__FILE__);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/include.php");

$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("BT_SALE_DISCOUNT_EDIT_TAB_NAME_COMMON"), "ICON" => "sale", "TITLE" => GetMessage("BT_SALE_DISCOUNT_EDIT_TAB_TITLE_COMMON")),
	array("DIV" => "edit3", "TAB" => GetMessage("BT_SALE_DISCOUNT_EDIT_TAB_NAME_ACTIONS"), "ICON" => "sale", "TITLE" => GetMessage("BT_CAT_DISCOUNT_EDIT_TAB_TITLE_ACTIONS")),
	array("DIV" => "edit2", "TAB" => GetMessage("BT_SALE_DISCOUNT_EDIT_TAB_NAME_GROUPS"), "ICON" => "sale", "TITLE" => GetMessage("BT_SALE_DISCOUNT_EDIT_TAB_TITLE_GROUPS")),
	array("DIV" => "edit4", "TAB" => GetMessage("BT_SALE_DISCOUNT_EDIT_TAB_NAME_MISC"), "ICON" => "sale", "TITLE" => GetMessage("BT_SALE_DISCOUNT_EDIT_TAB_TITLE_MISC")),
);

$tabControl = new CAdminForm("sale_discount", $aTabs);

$arErrorMess = array();
$bVarsFromForm = false;
$boolCondParseError = false;
$boolActParseError = false;

$ID = intval($ID);

if ('POST' == $_SERVER['REQUEST_METHOD'] && strlen($Update)>0 && $saleModulePermissions>="W" && check_bitrix_sessid())
{
	$obCond3 = new CSaleCondTree();

	$boolCond = $obCond3->Init(BT_COND_MODE_PARSE, BT_COND_BUILD_SALE, array());
	if (!$boolCond)
	{
		if ($ex = $APPLICATION->GetException())
			$arErrorMess[] = $ex->GetString();
		else
			$arErrorMess[] = (0 < $ID ? str_replace('#ID#', $ID, GetMessage('BT_SALE_DISCOUNT_EDIT_ERR_UPDATE')) : GetMessage('BT_SALE_DISCOUNT_EDIT_ERR_ADD'));
		$bVarsFromForm = true;
	}
	else
	{
		$boolCond = false;
		if (isset($_POST['CONDITIONS']) && isset($_POST['CONDITIONS_CHECK']))
		{
			if (is_string($_POST['CONDITIONS']) && is_string($_POST['CONDITIONS_CHECK']) && md5($_POST['CONDITIONS']) == $_POST['CONDITIONS_CHECK'])
			{
				$CONDITIONS = base64_decode($_POST['CONDITIONS']);
				if (CheckSerializedData($CONDITIONS))
				{
					$CONDITIONS = unserialize($CONDITIONS);
					$boolCond = true;
				}
				else
				{
					$boolCondParseError = true;
				}
			}
		}

		if (!$boolCond)
			$CONDITIONS = $obCond3->Parse();
		if (empty($CONDITIONS))
		{
			if ($ex = $APPLICATION->GetException())
				$arErrorMess[] = $ex->GetString();
			else
				$arErrorMess[] = (0 < $ID ? str_replace('#ID#', $ID, GetMessage('BT_SALE_DISCOUNT_EDIT_ERR_UPDATE')) : GetMessage('BT_SALE_DISCOUNT_EDIT_ERR_ADD'));
			$bVarsFromForm = true;
			$boolCondParseError = true;
		}
	}

	$obAct3 = new CSaleActionTree();

	$boolAct = $obAct3->Init(BT_COND_MODE_PARSE, BT_COND_BUILD_SALE_ACTIONS, array('PREFIX' => 'actrl'));
	if (!$boolAct)
	{
		if ($ex = $APPLICATION->GetException())
			$arErrorMess[] = $ex->GetString();
		else
			$arErrorMess[] = (0 < $ID ? str_replace('#ID#', $ID, GetMessage('BT_SALE_DISCOUNT_EDIT_ERR_UPDATE')) : GetMessage('BT_SALE_DISCOUNT_EDIT_ERR_ADD'));
		$bVarsFromForm = true;
	}
	else
	{
		$boolAct = false;
		if (isset($_POST['ACTIONS']) && isset($_POST['ACTIONS_CHECK']))
		{
			if (is_string($_POST['ACTIONS']) && is_string($_POST['ACTIONS_CHECK']) && md5($_POST['ACTIONS']) == $_POST['ACTIONS_CHECK'])
			{
				$ACTIONS = base64_decode($_POST['ACTIONS']);
				if (CheckSerializedData($ACTIONS))
				{
					$ACTIONS = unserialize($ACTIONS);
					$boolAct = true;
				}
				else
				{
					$boolActParseError = true;
				}
			}
		}

		if (!$boolAct)
			$ACTIONS = $obAct3->Parse();
		if (empty($ACTIONS))
		{
			if ($ex = $APPLICATION->GetException())
				$arErrorMess[] = $ex->GetString();
			else
				$arErrorMess[] = (0 < $ID ? str_replace('#ID#', $ID, GetMessage('BT_SALE_DISCOUNT_EDIT_ERR_UPDATE')) : GetMessage('BT_SALE_DISCOUNT_EDIT_ERR_ADD'));
			$bVarsFromForm = true;
			$boolActParseError = true;
		}
	}

	$arFields = array(
		"LID" => (isset($_POST['LID']) ? $_POST['LID'] : ''),
		"NAME" => (isset($_POST['NAME']) ? $_POST['NAME'] : ''),
		"ACTIVE_FROM" => (isset($_POST['ACTIVE_FROM']) ? $_POST['ACTIVE_FROM'] : ''),
		"ACTIVE_TO" => (isset($_POST['ACTIVE_TO']) ? $_POST['ACTIVE_TO'] : ''),
		"ACTIVE" => (isset($_POST['ACTIVE']) && 'Y' == $_POST['ACTIVE'] ? 'Y' : 'N'),
		"SORT" => intval(isset($_POST['SORT']) ? $_POST['SORT'] : 500),
		"PRIORITY" => intval(isset($_POST['PRIORITY']) ? $_POST['PRIORITY'] : ''),
		"LAST_DISCOUNT" => (isset($_POST['LAST_DISCOUNT']) && 'N' == $_POST['LAST_DISCOUNT'] ? 'N' : 'Y'),
		"XML_ID" => (isset($_POST['XML_ID']) ? $_POST['XML_ID'] : ''),
		'CONDITIONS' => $CONDITIONS,
		'ACTIONS' => $ACTIONS,
	);
	if (isset($USER_GROUPS))
		$arFields["USER_GROUPS"] = $USER_GROUPS;
	else
		$arFields["USER_GROUPS"] = array();

	if (empty($arErrorMess))
	{
		if ($ID > 0)
		{
			if (!CSaleDiscount::Update($ID, $arFields))
			{
				if ($ex = $APPLICATION->GetException())
					$arErrorMess[] = $ex->GetString();
				else
					$arErrorMess[] = str_replace('#ID#', $ID, GetMessage('BT_SALE_DISCOUNT_EDIT_ERR_UPDATE'));
			}
		}
		else
		{
			$ID = CSaleDiscount::Add($arFields);
			$ID = intval($ID);
			if ($ID <= 0)
			{
				if ($ex = $APPLICATION->GetException())
					$arErrorMess[] = $ex->GetString();
				else
					$arErrorMess[] = GetMessage('BT_SALE_DISCOUNT_EDIT_ERR_ADD');
			}
		}
	}
	if (empty($arErrorMess))
	{
		if (strlen($apply) <= 0)
			LocalRedirect("/bitrix/admin/sale_discount.php?lang=".LANGUAGE_ID.GetFilterParams("filter_", false));
		else
			LocalRedirect("/bitrix/admin/sale_discount_edit.php?lang=".LANGUAGE_ID."&ID=".$ID.'&'.$tabControl->ActiveTabParam());
	}
	else
	{
		$bVarsFromForm = true;
	}
}

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/prolog.php");

if ($ID > 0)
	$APPLICATION->SetTitle(str_replace('#ID#', $ID, GetMessage("BT_SALE_DISCOUNT_EDIT_MESS_UPDATE_DISCOUNT")));
else
	$APPLICATION->SetTitle(GetMessage("BT_SALE_DISCOUNT_EDIT_MESS_ADD_DISCOUNT"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$arDefaultValues = array(
	'LID' => '',
	'NAME' => '',
	'CURRENCY' => '',
	'DISCOUNT_VALUE' => '',
	'DISCOUNT_TYPE' => 'P',
	'ACTIVE' => 'Y',
	'SORT' => '100',
	'ACTIVE_FROM' => '',
	'ACTIVE_TO' => '',
	'PRIORITY' => 1,
	'LAST_DISCOUNT' => 'Y',
	'CONDITIONS' => '',
	'XML_ID' => '',
	'ACTIONS' => '',
);
if (isset($_REQUEST['LID']))
	$arDefaultValues['LID'] = trim($_REQUEST['LID']);
if ('' == $arDefaultValues['LID'])
	$arDefaultValues['LID'] = 's1';

$arSelect = array_merge(array('ID'), array_keys($arDefaultValues));

$arDiscount = array();
$arDiscountGroupList = array();

$rsDiscounts = CSaleDiscount::GetList(array(), array("ID" => $ID), false, false, $arSelect);
if (!($arDiscount = $rsDiscounts->Fetch()))
{
	$ID = 0;
	$arDiscount = $arDefaultValues;
}
else
{
	$rsDiscountGroups = CSaleDiscount::GetDiscountGroupList(array(),array('DISCOUNT_ID' => $ID),false,false,array('GROUP_ID'));
	while ($arDiscountGroup = $rsDiscountGroups->Fetch())
	{
		$arDiscountGroupList[] = intval($arDiscountGroup['GROUP_ID']);
	}
}
if ($bVarsFromForm)
{
	if ($boolCondParseError || $boolActParseError)
	{
		$mxTempo = $arDiscount['CONDITIONS'];
		$mxTempo2 = $arDiscount['ACTIONS'];
		$arDiscount = $arFields;
		if ($boolCondParseError)
			$arDiscount['CONDITIONS'] = $mxTempo;
		if ($boolActParseError)
			$arDiscount['ACTIONS'] = $mxTempo2;
		unset($mxTempo);
		unset($mxTempo2);
	}
	else
	{
		$arDiscount = $arFields;
	}
	$arDiscountGroupList = $arFields['USER_GROUPS'];
}

$aMenu = array(
	array(
		"TEXT" => GetMessage("BT_SALE_DISCOUNT_EDIT_MESS_DISCOUNT_LIST"),
		"LINK" => "/bitrix/admin/sale_discount.php?lang=".LANGUAGE_ID.GetFilterParams("filter_"),
		"ICON" => "btn_list"
	)
);

if ($ID > 0 && $saleModulePermissions >= "W")
{
	$aMenu[] = array("SEPARATOR" => "Y");

	$aMenu[] = array(
			"TEXT" => GetMessage("BT_SALE_DISCOUNT_EDIT_MESS_NEW_DISCOUNT"),
			"LINK" => "/bitrix/admin/sale_discount_edit.php?lang=".LANGUAGE_ID.GetFilterParams("filter_"),
			"ICON" => "btn_new"
		);

	if ($saleModulePermissions >= "W")
	{
		$aMenu[] = array(
			"TEXT" => GetMessage("BT_SALE_DISCOUNT_EDIT_MESS_DELETE_DISCOUNT"),
			"LINK" => "javascript:if(confirm('".GetMessage("BT_SALE_DISCOUNT_EDIT_MESS_DELETE_DISCOUNT_CONFIRM")."')) window.location='/bitrix/admin/sale_discount.php?ID=".$ID."&action=delete&lang=".LANGUAGE_ID."&".bitrix_sessid_get()."#tb';",
			"WARNING" => "Y",
			"ICON" => "btn_delete"
		);
	}
}
$context = new CAdminContextMenu($aMenu);
$context->Show();

if (!empty($arErrorMess))
{
	echo CAdminMessage::ShowMessage(
		array(
			"DETAILS" => implode('<br>', $arErrorMess),
			"TYPE" => "ERROR",
			"MESSAGE" => GetMessage("BT_SALE_DISCOUNT_EDIT_MESS_SAVE_ERROR"),
			"HTML" => true
		)
	);
}

$arSiteList = array();
$rsSites = CSite::GetList(($by = 'sort'),($order = 'asc'));
while ($arSite = $rsSites->Fetch())
{
	$arSiteList[$arSite['LID']] = '('.$arSite['LID'].') '.$arSite['NAME'];
}

$tabControl->BeginPrologContent();

CAdminCalendar::ShowScript();

$tabControl->EndPrologContent();

$tabControl->BeginEpilogContent();
echo GetFilterHiddens("filter_");?>
<input type="hidden" name="Update" value="Y">
<input type="hidden" name="lang" value="<?echo LANGUAGE_ID; ?>">
<input type="hidden" name="ID" value="<?echo $ID ?>">
<? echo bitrix_sessid_post();
$tabControl->EndEpilogContent();
$tabControl->Begin(array(
	"FORM_ACTION" => '/bitrix/admin/sale_discount_edit.php?lang='.LANGUAGE_ID,
));
$tabControl->BeginNextFormTab();
	if ($ID > 0)
		$tabControl->AddViewField('ID','ID:',$ID,false);
	$tabControl->AddCheckBoxField("ACTIVE", GetMessage("SDEN_ACTIVE").":", false, "Y", $arDiscount['ACTIVE'] == "Y");
	$tabControl->AddDropDownField("LID", GetMessage('SDEN_SITE').':', true, $arSiteList, $arDiscount['LID']);
	$tabControl->AddEditField("NAME", GetMessage("BT_SALE_DISCOUNT_EDIT_FIELDS_NAME").":", false, array("size" => 50, "maxlength" => 255), htmlspecialcharsbx($arDiscount['NAME']));
	$tabControl->BeginCustomField("PERIOD", GetMessage('SDEN_PERIOD').":",false);
	?><tr id="tr_PERIOD">
		<td width="40%"><? echo $tabControl->GetCustomLabelHTML(); ?></td>
		<td width="60%"><?
			global $ACTIVE_FROM_FILTER_PERIOD;
			$ACTIVE_FROM_FILTER_PERIOD = "";
			if ('' != $arDiscount['ACTIVE_FROM'] || '' != $arDiscount['ACTIVE_TO'])
				$ACTIVE_FROM_FILTER_PERIOD = CAdminCalendar::PERIOD_INTERVAL;

			echo CAdminCalendar::CalendarPeriodCustom("ACTIVE_FROM", "ACTIVE_TO", $arDiscount['ACTIVE_FROM'], $arDiscount['ACTIVE_TO'], true, 19, true, array(
				CAdminCalendar::PERIOD_EMPTY => GetMessage('BT_SALE_DISCOUNT_EDIT_CALENDARE_PERIOD_EMPTY'),
				CAdminCalendar::PERIOD_INTERVAL => GetMessage('BT_SALE_DISCOUNT_EDIT_CALENDARE_PERIOD_INTERVAL')
			));
		?></td>
	</tr><?
	$tabControl->EndCustomField("PERIOD",
		'<input type="hidden" name="ACTIVE_FROM" value="'.htmlspecialcharsbx($arDiscount['ACTIVE_FROM']).'">'.
		'<input type="hidden" name="ACTIVE_TO" value="'.htmlspecialcharsbx($arDiscount['ACTIVE_FROM']).'">'
	);
	$tabControl->AddEditField("PRIORITY", GetMessage("BT_SALE_DISCOUNT_EDIT_FIELDS_PRIORITY").":", false, array("size" => 20, "maxlength" => 20), intval($arDiscount['PRIORITY']));
	$tabControl->AddEditField("SORT", GetMessage("BT_SALE_DISCOUNT_EDIT_FIELDS_SORT"), false, array('size' => 10, 'maxlength' => 20), intval($arDiscount['SORT']));
	$tabControl->BeginCustomField("LAST_DISCOUNT", GetMessage('BT_SALE_DISCOUNT_EDIT_FIELDS_LAST_DISCOUNT').":",false);
	?><tr id="tr_LAST_DISCOUNT">
		<td width="40%"><? echo $tabControl->GetCustomLabelHTML(); ?></td>
		<td width="60%">
			<input type="hidden" value="N" name="LAST_DISCOUNT">
			<input type="checkbox" value="Y" name="LAST_DISCOUNT" <? echo ('Y' == $arDiscount['LAST_DISCOUNT']? 'checked' : '');?>>
		</td>
	</tr><?
	$tabControl->EndCustomField("LAST_DISCOUNT",
		'<input type="hidden" name="LAST_DISCOUNT" value="'.htmlspecialcharsbx($arDiscount['LAST_DISCOUNT']).'">'
	);
$tabControl->BeginNextFormTab();
	$tabControl->AddSection("BT_SALE_DISCOUNT_SECT_APP", GetMessage("BT_SALE_DISCOUNT_SECTIONS_APP"));
	$tabControl->BeginCustomField("ACTIONS", GetMessage('BT_SALE_DISCOUNT_EDIT_FIELDS_APP').":",false);
	?><tr id="ACTIONS">
		<td valign="top" colspan="2"><div id="tree_actions" style="position: relative; z-index: 1;"></div><?
			if (!is_array($arDiscount['APPICATIONS']))
			{
				if (CheckSerializedData($arDiscount['APPICATIONS']))
				{
					$arDiscount['APPICATIONS'] = unserialize($arDiscount['APPICATIONS']);
				}
				else
				{
					$arDiscount['APPICATIONS'] = '';
				}
			}
			$arCondParams = array(
				'FORM_NAME' => 'sale_discount_form',
				'CONT_ID' => 'tree_actions',
				'JS_NAME' => 'JSSaleAct',
				'PREFIX' => 'actrl',
				'INIT_CONTROLS' => array(
					'SITE_ID' => $arDiscount['LID'],
					'CURRENCY' => CSaleLang::GetLangCurrency($arDiscount['LID']),
				),
				'SYSTEM_MESSAGES' => array(
					'SELECT_CONTROL' => GetMessage('BT_SALE_DISCOUNT_ACTIONS_SELECT_CONTROL'),
					'ADD_CONTROL' => GetMessage('BT_SALE_DISCOUNT_ACTIONS_ADD_CONTROL'),
					'DELETE_CONTROL' => GetMessage('BT_SALE_DISCOUNT_ACTIONS_DELETE_CONTROL'),
				),
			);
			$obAct = new CSaleActionTree();
			$boolAct = $obAct->Init(BT_COND_MODE_DEFAULT, BT_COND_BUILD_SALE_ACTIONS, $arCondParams);
			if (!$boolAct)
			{
				if ($ex = $APPLICATION->GetException())
					echo $ex->GetString()."<br>";
			}
			else
			{
				$obAct->Show($arDiscount['ACTIONS']);
			}
		?></td>
	</tr><?
	$strHidden = '';
	$strApp = base64_encode(serialize($arDiscount['ACTIONS']));

	$tabControl->EndCustomField('ACTIONS',
		'<input type="hidden" name="ACTIONS" value="'.htmlspecialcharsbx($strApp).'">'.
		'<input type="hidden" name="ACTIONS_CHECK" value="'.htmlspecialcharsbx(md5($strApp)).'">'
	);
	$tabControl->AddSection("BT_SALE_DISCOUNT_SECT_COND", GetMessage("BT_SALE_DISCOUNT_SECTIONS_COND_ADD"));
	$tabControl->BeginCustomField("CONDITIONS", GetMessage('BT_SALE_DISCOUNT_EDIT_FIELDS_COND_ADD').":",false);
	?><tr id="tr_CONDITIONS">
		<td valign="top" colspan="2"><div id="tree" style="position: relative; z-index: 1;"></div><?
			if (!is_array($arDiscount['CONDITIONS']))
			{
				if (CheckSerializedData($arDiscount['CONDITIONS']))
				{
					$arDiscount['CONDITIONS'] = unserialize($arDiscount['CONDITIONS']);
				}
				else
				{
					$arDiscount['CONDITIONS'] = '';
				}
			}
			$arCondParams = array(
				'FORM_NAME' => 'sale_discount_form',
				'CONT_ID' => 'tree',
				'JS_NAME' => 'JSSaleCond',
				'INIT_CONTROLS' => array(
					'SITE_ID' => $arDiscount['LID'],
					'CURRENCY' => CSaleLang::GetLangCurrency($arDiscount['LID']),
				),
			);
			$obCond = new CSaleCondTree();
			$boolCond = $obCond->Init(BT_COND_MODE_DEFAULT, BT_COND_BUILD_SALE, $arCondParams);
			if (!$boolCond)
			{
				if ($ex = $APPLICATION->GetException())
					echo $ex->GetString()."<br>";
			}
			else
			{
				$obCond->Show($arDiscount['CONDITIONS']);
			}
		?></td>
	</tr><?
	$strHidden = '';
	$strCond = base64_encode(serialize($arDiscount['CONDITIONS']));
	$tabControl->EndCustomField('CONDITIONS',
		'<input type="hidden" name="CONDITIONS" value="'.htmlspecialcharsbx($strCond).'">'.
		'<input type="hidden" name="CONDITIONS_CHECK" value="'.htmlspecialcharsbx(md5($strCond)).'">'
	);
$tabControl->BeginNextFormTab();
	$tabControl->BeginCustomField('USER_GROUPS', GetMessage('BT_SALE_DISCOUNT_EDIT_FIELDS_GROUPS').':', true);
	?><tr id="tr_USER_GROUPS" class="adm-detail-required-field">
		<td valign="top" width="40%"><? echo $tabControl->GetCustomLabelHTML(); ?></td>
		<td valign="top" width="60%">
			<select name="USER_GROUPS[]" multiple size="8">
			<?
			$dbGroups = CGroup::GetList(($b="c_sort"), ($o="asc"), array());
			while ($arGroups = $dbGroups->Fetch())
			{
				?><option value="<?= $arGroups["ID"] ?>"<?if (in_array(intval($arGroups["ID"]), $arDiscountGroupList)) echo " selected";?>>[<?= $arGroups["ID"] ?>] <?= htmlspecialcharsEx($arGroups["NAME"]) ?></option><?
			}
			?>
			</select>
		</td>
	</tr><?
	if ($ID > 0 && !empty($arDiscountGroupList))
	{
		$arHidden = array();
		foreach ($arDiscountGroupList as &$value)
		{
			if (0 < intval($value))
				$arHidden[] = '<input type="hidden" name="USER_GROUPS[]" value="'.intval($value).'">';
		}
		if (isset($value))
			unset($value);
		$strHidden = implode('',$arHidden);
	}
	else
	{
		$strHidden = '<input type="hidden" name="USER_GROUPS[]" value="">';
	}
	$tabControl->EndCustomField("USER_GROUPS",
		$strHidden
	);
$tabControl->BeginNextFormTab();
	$tabControl->AddEditField("XML_ID", GetMessage("BT_SALE_DISCOUNT_EDIT_FIELDS_XML_ID").":", false, array("size" => 50, "maxlength" => 255), htmlspecialcharsbx($arDiscount['XML_ID']));

$tabControl->Buttons(
	array(
		"disabled" => ($saleModulePermissions < "W"),
		"back_url" => "/bitrix/admin/sale_discount.php?lang=".LANGUAGE_ID.GetFilterParams("filter_")
	)
);
$tabControl->Show();

?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>