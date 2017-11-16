<?
/*
##############################################
# Bitrix: SiteManager                        #
# Copyright (c) 2002-2007 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:sources@bitrixsoft.com              #
##############################################
*/
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

CModule::IncludeModule("controller");
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/controller/prolog.php");
IncludeModuleLangFile(__FILE__);

$MOD_RIGHT = $APPLICATION->GetGroupRight("controller");
if($MOD_RIGHT<"W") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));


$err_mess = "File: ".__FILE__."<br>Line: ";

$sTableID = "t_controll_group";
$oSort = new CAdminSorting($sTableID, "timestamp_x", "desc");
$lAdmin = new CAdminList($sTableID, $oSort);


$arFilterRows = 	array(
		"ID",
		GetMessage("CTRLR_GR_AD_FLT_MODIF"),
		GetMessage("CTRLR_GR_AD_FLT_CREAT"),
	);


$filter = new CAdminFilter(
	$sTableID."_filter_id",
	$arFilterRows
);


$arFilterFields = Array(
	"find_name",
	"find_id",
	"find_timestamp_x_from",
	"find_timestamp_x_to",
	"find_created_from",
	"find_created_to",
);

$lAdmin->InitFilter($arFilterFields);

$arFilter = Array(
	"ID"=>$find_id,
	"%NAME"=>$find_name,
	">=TIMESTAMP_X"=>$find_timestamp_x_from,
	"<=TIMESTAMP_X"=>$find_timestamp_x_to,
	">=DATE_CREATE"=>$find_created_from,
	"<=DATE_CREATE"=>$find_created_to,
	);

if ($MOD_RIGHT>="W" && $lAdmin->EditAction())
{
	foreach($FIELDS as $ID => $arFields)
	{
		$ID = intval($ID);

		if(!$lAdmin->IsUpdated($ID))
			continue;

		$DB->StartTransaction();
		if(!CControllerGroup::Update($ID, $arFields))
		{
			$e = $APPLICATION->GetException();
			$lAdmin->AddUpdateError(GetMessage("CTRLR_GR_AD_ERR1")." #".$ID.": ".$e->GetString(), $ID);
			$DB->Rollback();
		}
		$DB->Commit();
	}
}


if($MOD_RIGHT>="W" && $arID = $lAdmin->GroupAction())
{
	if($_REQUEST['action_target']=='selected')
	{
		$rsData = CControllerGroup::GetList(Array($by=>$order), $arFilter);
		while($arRes = $rsData->Fetch())
			$arID[] = $arRes['ID'];
	}

	foreach($arID as $ID)
	{
		if(strlen($ID)<=0)
			continue;
		$ID = IntVal($ID);

		switch($_REQUEST['action'])
		{
			case "delete":
				@set_time_limit(0);
				$DB->StartTransaction();
				if(!CControllerGroup::Delete($ID))
				{
					$e = $APPLICATION->GetException();
					$DB->Rollback();
					$lAdmin->AddGroupError(GetMessage("CTRLR_GR_AD_ERR2").":".$e->GetString(), $ID);
				}
				$DB->Commit();
			break;
		}
	}
}

$rsData = CControllerGroup::GetList(Array($by=>$order), $arFilter);
$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart();


$lAdmin->NavText($rsData->GetNavPrint(GetMessage("CTRLR_GR_AD_NAV")));


$arHeaders = Array();
$arHeaders[] = Array("id"=>"NAME", "content"=>GetMessage("CTRLR_GR_AD_COL_NAME"), "default"=>true, "sort" => "name");
$arHeaders[] = Array("id"=>"TIMESTAMP_X", "content"=>GetMessage("CTRLR_GR_AD_COL_MOD"), "default"=>true, "sort" => "timestamp_x");
$arHeaders[] = Array("id"=>"MODIFIED_BY", "content"=>GetMessage("CTRLR_GR_AD_COL_MODBY"), "default"=>true, "sort" => "modified_by");
$arHeaders[] = Array("id"=>"DATE_CREATE", "content"=>GetMessage("CTRLR_GR_AD_COL_CRE"), "sort" => "DATE_CREATE");
$arHeaders[] = Array("id"=>"CREATED_BY", "content"=>GetMessage("CTRLR_GR_AD_COL_CREBY"), "sort" => "CREATED_BY");
$arHeaders[] = Array("id"=>"DESCRIPTION", "content"=>GetMessage("CTRLR_GR_AD_COL_DESC"));
$arHeaders[] = Array("id"=>"COUNTER_UPDATE_PERIOD", "content"=>GetMessage("CTRLE_GR_AD_COUNTER_UPD_PER"), "sort" => "COUNTER_UPDATE_PERIOD");
$arHeaders[] = Array("id"=>"CHECK_COUNTER_FREE_SPACE", "content"=>GetMessage("CTRLE_GR_AD_COUNTER_FREE"), "sort" => "CHECK_COUNTER_FREE_SPACE");
$arHeaders[] = Array("id"=>"CHECK_COUNTER_SITES", "content"=>GetMessage("CTRLE_GR_AD_COUNTER_SITES"), "sort" => "CHECK_COUNTER_SITES");
$arHeaders[] = Array("id"=>"CHECK_COUNTER_USERS", "content"=>GetMessage("CTRLE_GR_AD_COUNTER_USERS"), "sort" => "CHECK_COUNTER_USERS");
$arHeaders[] = Array("id"=>"CHECK_COUNTER_LAST_AUTH", "content"=>GetMessage("CTRLE_GR_AD_COUNTER_LAST_AU"), "sort" => "CHECK_COUNTER_LAST_AUTH");

$arHeaders[] = Array("id"=>"ID", "content"=>"ID", "default"=>true, "sort" => "id");

$lAdmin->AddHeaders($arHeaders);

while($arRes = $rsData->NavNext(true, "f_"))
{
	$row =& $lAdmin->AddRow($f_ID, $arRes);

	$row->AddViewField("MODIFIED_BY", '<a href="user_edit.php?ID='.$f_MODIFIED_BY.'">('.$f_MODIFIED_BY_LOGIN.') '.$f_MODIFIED_BY_NAME.' '.$f_MODIFIED_BY_LAST_NAME).'</a>';
	$row->AddViewField("CREATED_BY", '<a href="user_edit.php?ID='.$f_CREATED_BY.'">('.$f_CREATED_BY_LOGIN.') '.$f_CREATED_BY_NAME.' '.$f_CREATED_BY_LAST_NAME).'</a>';
	$row->AddInputField("NAME", Array("size"=>"35"));

	$row->AddInputField("COUNTER_UPDATE_PERIOD", Array("size"=>"5"));

	$row->AddCheckField("CHECK_COUNTER_FREE_SPACE");
	$row->AddCheckField("CHECK_COUNTER_SITES");
	$row->AddCheckField("CHECK_COUNTER_USERS");
	$row->AddCheckField("CHECK_COUNTER_LAST_AUTH");

	$arActions = Array();

	$arActions[] = array(
		"ICON"=>"edit",
		"DEFAULT" => "Y",
		"TEXT"=>GetMessage("CTRLR_GR_AD_MENU_EDIT"),
		"ACTION"=>$lAdmin->ActionRedirect("controller_group_edit.php?ID=".$f_ID."&lang=".LANG)
	);

	if($f_ID>1)
	{
		$arActions[] = array("SEPARATOR"=>true);

		$arActions[] = array(
			"ICON"=>"delete",
			"TEXT"=>GetMessage("CTRLR_GR_AD_MENU_DEL"),
			"ACTION"=>"if(confirm('".GetMessage("CTRLR_GR_AD_MENU_DEL_CONFIRM")."')) ".$lAdmin->ActionDoGroup($f_ID, "delete"),
		);
	}

	$row->AddActions($arActions);
}

$lAdmin->AddFooter(
	array(
		array("title"=>GetMessage("MAIN_ADMIN_LIST_SELECTED"), "value"=>$rsData->SelectedRowsCount()),
		array("counter"=>true, "title"=>GetMessage("MAIN_ADMIN_LIST_CHECKED"), "value"=>"0"),
	)
);

if ($MOD_RIGHT>="W")
{
	$lAdmin->AddGroupActionTable(Array(
		"delete"=>GetMessage("MAIN_ADMIN_LIST_DELETE"),
		)
	);
}

$aContext = array(
	array(
		"ICON" => "btn_new",
		"TEXT"=>GetMessage("MAIN_ADD"),
		"LINK"=>"controller_group_edit.php?lang=".LANG,
		"TITLE"=>GetMessage("MAIN_ADD")
	),
);


$lAdmin->AddAdminContextMenu($aContext);

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("CTRLR_GR_AD_TITLE"));
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");
?>
<form name="form1" method="GET" action="<?echo $APPLICATION->GetCurPage()?>?">
<?$filter->Begin();?>
<tr>
	<td nowrap><?=GetMessage("CTRLR_GR_AD_COL_NAME")?>:</td>
	<td nowrap><input type="text" name="find_name" value="<?echo htmlspecialcharsbx($find_name)?>" size="47"><?//=ShowFilterLogicHelp()?></td>
</tr>

<tr>
	<td nowrap>ID:</td>
	<td nowrap><input type="text" name="find_id" value="<?echo htmlspecialcharsbx($find_id)?>" size="47"><?//=ShowFilterLogicHelp()?></td>
</tr>

<tr>
	<td nowrap><?=GetMessage("CTRLR_GR_AD_FLT_MODIF")?>:</td>
	<td nowrap><?echo CalendarPeriod("find_timestamp_x_from", $find_timestamp_x_from, "find_timestamp_x_to", $find_timestamp_x_to, "form1", "Y")?></td>
</tr>
<tr>
	<td nowrap><?=GetMessage("CTRLR_GR_AD_FLT_CREAT")?>:</td>
	<td nowrap><?echo CalendarPeriod("find_created_from", $find_created_from, "find_created_to", $find_created_to, "form1", "Y")?></td>
</tr>
<?if(false):?>
<tr>
	<td nowrap><?=GetMessage("CTRLR_GR_AD_FLT_ACT_FROM")?>:</td>
	<td nowrap><?echo CalendarPeriod("find_active_from_from", $find_active_from_from, "find_active_from_to", $find_active_from_to, "form1", "Y")?></td>
</tr>
<tr>
	<td nowrap><?=GetMessage("CTRLR_GR_AD_FLT_ACT_TO")?>:</td>
	<td nowrap><?echo CalendarPeriod("find_active_to_from", $find_active_to_from, "find_active_to_to", $find_active_to_to, "form1", "Y")?></td>
</tr>
<?endif?>

<?$filter->Buttons(array("table_id"=>$sTableID, "url"=>$APPLICATION->GetCurPage(), "form"=>"form1"));$filter->End();?>

</form>

<?$lAdmin->DisplayList();?>


<?require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");?>
