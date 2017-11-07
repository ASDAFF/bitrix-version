<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/currency/include.php");

$CURRENCY_RIGHT = $APPLICATION->GetGroupRight("currency");
if ($CURRENCY_RIGHT=="D") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

__IncludeLang(GetLangFileName($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/currency/lang/", "/currencies.php"));
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/currency/prolog.php");

$sTableID = "t_currencies";
$oSort = new CAdminSorting($sTableID, "sort", "asc");
$lAdmin = new CAdminList($sTableID, $oSort);

if ($lAdmin->EditAction() && $CURRENCY_RIGHT=="W")
{
	foreach($FIELDS as $ID => $arFields)
	{
		$ID = substr($ID,0,3);

		if(!$lAdmin->IsUpdated($ID))
			continue;

		if (!CCurrency::Update($ID, $arFields))
		{
			if ($ex = $APPLICATION->GetException())
			{
				$lAdmin->AddUpdateError(GetMessage("CURRENCY_SAVE_ERR", array("#ID#" => $ID, "#ERROR_TEXT#" => $ex->GetString())), $ID);
			}
			else
			{
				$lAdmin->AddUpdateError(GetMessage("CURRENCY_SAVE_ERR2", array("#ID#"=>$ID)), $ID);
			}
		}
	}
}

if($CURRENCY_RIGHT=="W" && $arID = $lAdmin->GroupAction())
{
	if($_REQUEST['action_target']=='selected')
	{
		$rsData = CCurrency::GetList($by, $order);
		while($arRes = $rsData->Fetch())
			$arID[] = $arRes['ID'];
	}

	foreach($arID as $ID)
	{
		if(strlen($ID)<=0)
			continue;

		switch($_REQUEST['action'])
		{
		case "delete":
			if ($CURRENCY_RIGHT=="W")
				if (!CCurrency::Delete($ID))
				{
					if ($ex = $APPLICATION->GetException())
						$lAdmin->AddGroupError($ex->GetString(), $ID);
					else
						$lAdmin->AddGroupError(GetMessage("currency_err1"), $ID);
				}
		break;

		}
	}
}

$rsData = CCurrency::GetList($by, $order);
$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart();

$lAdmin->NavText($rsData->GetNavPrint(GetMessage("CURRENCY_TITLE")));

$arHeaders = array();
$arHeaders[] = array("id"=>"CURRENCY", "content"=>GetMessage('currency_curr'), "sort"=>"CURRENCY", "default"=>true);
$arHeaders[] = array("id"=>"FULL_NAME", "content"=>GetMessage('CURRENCY_FULL_NAME'), "sort"=>"name", "default"=>true);
$arHeaders[] = array("id"=>"SORT", "content"=>GetMessage('currency_sort'), "sort" => "sort", "default"=>true);
$arHeaders[] = array("id"=>"AMOUNT_CNT", "content"=>GetMessage('currency_rate_cnt'), "default"=>true);
$arHeaders[] = array("id"=>"AMOUNT", "content"=>GetMessage('currency_rate'), "default"=>true);

$lAdmin->AddHeaders($arHeaders);

while($arRes = $rsData->NavNext(true, "f_"))
{
	$row =& $lAdmin->AddRow($f_CURRENCY, $arRes, "/bitrix/admin/currency_edit.php?ID=".$f_CURRENCY."&lang=".LANGUAGE_ID, GetMessage('CURRENCY_A_EDIT'));

	$row->AddViewField("CURRENCY", '<a href="/bitrix/admin/currency_edit.php?ID='.$f_CURRENCY.'&lang='.LANGUAGE_ID.'" title="'.GetMessage('CURRENCY_A_EDIT_TITLE').'">'.$f_CURRENCY.'</a>');
	$row->AddInputField("SORT", array("size"=>"3"));
	$row->AddViewField("FULL_NAME", $f_FULL_NAME);
	$row->AddInputField("AMOUNT_CNT", array("size"=>"5"));
	$row->AddInputField("AMOUNT", array("size"=>"6"));

	$arActions = Array();

	$arActions[] = array(
		"ICON"=>"edit",
		"DEFAULT" => "Y",
		"TEXT"=>GetMessage("MAIN_ADMIN_MENU_EDIT"),
		"ACTION"=>$lAdmin->ActionRedirect("/bitrix/admin/currency_edit.php?ID=".$f_CURRENCY."&lang=".LANGUAGE_ID)
	);

	if ($CURRENCY_RIGHT=="W")
	{
		$arActions[] = array("SEPARATOR"=>true);

		$arActions[] = array(
			"ICON"=>"delete",
			"TEXT"=>GetMessage("MAIN_ADMIN_MENU_DELETE"),
			"ACTION"=>"if(confirm('".GetMessage('CONFIRM_DEL_MESSAGE')."')) ".$lAdmin->ActionDoGroup($f_CURRENCY, "delete"));
	}

	$row->AddActions($arActions);
}


$lAdmin->AddFooter(
	array(
		array("title"=>GetMessage("MAIN_ADMIN_LIST_SELECTED"), "value"=>$rsData->SelectedRowsCount()),
		array("counter"=>true, "title"=>GetMessage("MAIN_ADMIN_LIST_CHECKED"), "value"=>"0"),
	)
);


if ($CURRENCY_RIGHT=="W")
{
	$lAdmin->AddGroupActionTable(Array(
		"delete"=>GetMessage("MAIN_ADMIN_LIST_DELETE"),
		)
	);
}

$aContext = array(
	array(
		"ICON" => "btn_new",
		"TEXT"=>GetMessage("currency_add"),
		"LINK"=>"/bitrix/admin/currency_edit.php?lang=".LANGUAGE_ID,
		"TITLE"=>GetMessage("currency_add")
	),
	array(
		"ICON" => "",
		"TEXT"=>GetMessage("currency_list"),
		"LINK"=>"/bitrix/admin/currencies_rates.php?lang=".LANGUAGE_ID,
		"TITLE"=>GetMessage("currency_list")
	),
);



$lAdmin->AddAdminContextMenu($aContext);

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("CURRENCY_TITLE"));
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$lAdmin->DisplayList();
echo BeginNote();
echo GetMessage("CURRENCY_BASE_CURRENCY");
echo EndNote();

?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>