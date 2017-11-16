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


$sTableID = "t_controller_counter";
$oSort = new CAdminSorting($sTableID, "id", "desc");
$lAdmin = new CAdminList($sTableID, $oSort);

$arFilterRows = array(
);

$arGroups = array();
$dbr_groups = CControllerGroup::GetList(array("SORT"=>"ASC"));
while($ar_groups = $dbr_groups->Fetch())
	$arGroups[$ar_groups["ID"]] = $ar_groups["NAME"];

$filter = new CAdminFilter(
	$sTableID."_filter_id",
	$arFilterRows
);

$arFilterFields = Array(
	"find_controller_group_id",
);

$lAdmin->InitFilter($arFilterFields);

if($find_controller_group_id)
	$arFilter = array("=CONTROLLER_GROUP_ID" => $find_controller_group_id);
else
	$arFilter = array();

if($MOD_RIGHT >= "W" && $lAdmin->EditAction())
{
	foreach($FIELDS as $ID => $arFields)
	{
		$ID = intval($ID);

		if(!$lAdmin->IsUpdated($ID))
			continue;

		$DB->StartTransaction();
		if(!CControllerCounter::Update($ID, $arFields))
		{
			$e = $APPLICATION->GetException();
			$lAdmin->AddUpdateError(GetMessage("CTRL_CNT_ADMIN_UPDATE_ERROR", array("#ID#" => $ID, "#ERROR#" => $e->GetString())), $ID);
			$DB->Rollback();
		}
		$DB->Commit();
	}
}


if($MOD_RIGHT >= "W" && $arID = $lAdmin->GroupAction())
{
	if($_REQUEST['action_target']=='selected')
	{
		$rsData = CControllerCounter::GetList(array($by=>$order), $arFilter);
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
				if(!CControllerCounter::Delete($ID))
				{
					$e = $APPLICATION->GetException();
					$DB->Rollback();
					$lAdmin->AddGroupError(GetMessage("CTRL_CNT_ADMIN_DELETE_ERROR", array("#ID#" => $ID, "#ERROR#" => $e->GetString())), $ID);
				}
				$DB->Commit();
			break;
		}
	}
}

$rsData = CControllerCounter::GetList(Array($by=>$order), $arFilter);
$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart();
$lAdmin->NavText($rsData->GetNavPrint(GetMessage("CTRL_CNT_ADMIN_NAV")));

$arHeaders = array(
	array(
		"id" => "NAME",
		"content" => GetMessage("CTRL_CNT_ADMIN_NAME"),
		"default" => true,
		"sort" => "name",
	),
	array(
		"id" => "COUNTER_TYPE",
		"content" => GetMessage("CTRL_CNT_ADMIN_COUNTER_TYPE"),
		"default" => true,
	),
	array(
		"id" => "COUNTER_FORMAT",
		"content" => GetMessage("CTRL_CNT_ADMIN_COUNTER_FORMAT"),
		"default" => true,
	),
	array(
		"id" => "COMMAND",
		"content" => GetMessage("CTRL_CNT_ADMIN_COMMAND"),
		"default" => true,
	),
);

$lAdmin->AddHeaders($arHeaders);

while($arRes = $rsData->NavNext(true, "f_"))
{
	$row = $lAdmin->AddRow($f_ID, $arRes);

	$row->AddInputField("NAME", Array("size"=>"35"));
	$row->AddSelectField("COUNTER_TYPE", CControllerCounter::GetTypeArray());
	$row->AddSelectField("COUNTER_FORMAT", CControllerCounter::GetFormatArray());
	$row->AddField("COMMAND", "<pre>".htmlspecialcharsbx($arRes["COMMAND"])."</pre>", "<textarea cols=\"80\" rows=\"15\" name=\"FIELDS[".$f_ID."][COMMAND]\">".htmlspecialcharsbx($arRes["COMMAND"])."</textarea>");

	$arActions = array(
		array(
			"ICON" => "edit",
			"DEFAULT" => "Y",
			"TEXT" => GetMessage("CTRL_CNT_ADMIN_MENU_EDIT"),
			"ACTION" => $lAdmin->ActionRedirect("controller_counter_edit.php?ID=".$f_ID."&lang=".LANGUAGE_ID),
		),
		array("SEPARATOR"=>true),
		array(
			"ICON" => "delete",
			"TEXT" => GetMessage("CTRL_CNT_ADMIN_MENU_DELETE"),
			"ACTION" => "if(confirm('".GetMessage("CTRL_CNT_ADMIN_MENU_DELETE_ALERT")."')) ".$lAdmin->ActionDoGroup($f_ID, "delete"),
		),
	);

	$row->AddActions($arActions);
}

$lAdmin->AddFooter(
	array(
		array(
			"title" => GetMessage("MAIN_ADMIN_LIST_SELECTED"),
			"value" => $rsData->SelectedRowsCount(),
		),
		array(
			"counter" => true,
			"title" => GetMessage("MAIN_ADMIN_LIST_CHECKED"),
			"value" => 0,
		),
	)
);

if($MOD_RIGHT >= "W")
{
	$lAdmin->AddGroupActionTable(Array(
		"delete" => GetMessage("MAIN_ADMIN_LIST_DELETE"),
		)
	);
}

$aContext = array(
	array(
		"ICON" => "btn_new",
		"TEXT" => GetMessage("MAIN_ADD"),
		"LINK" => "controller_counter_edit.php?lang=".LANGUAGE_ID,
		"TITLE" => GetMessage("MAIN_ADD")
	),
);


$lAdmin->AddAdminContextMenu($aContext);

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("CTRL_CNT_ADMIN_TITLE"));
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");
?>
<form name="form1" method="GET" action="<?echo $APPLICATION->GetCurPage()?>?">
<?$filter->Begin();?>
<tr>
	<td nowrap><?=GetMessage("CTRL_CNT_ADMIN_FILTER_GROUP")?></td>
	<td>
	<select name="find_controller_group_id">
		<option value=""><?echo GetMessage("CTRL_CNT_ADMIN_FILTER_ANY")?></option>
	<?foreach($arGroups as $group_id=>$group_name):?>
		<option value="<?=htmlspecialcharsbx($group_id)?>" <?if($group_id==$find_controller_group_id)echo "selected"?>><?=htmlspecialcharsex($group_name)?></option>
	<?endforeach;?>
	</select>
	</td>
</tr>

<?$filter->Buttons(array("table_id"=>$sTableID, "url"=>$APPLICATION->GetCurPage(), "form"=>"form1"));$filter->End();?>

</form>

<?$lAdmin->DisplayList();?>


<?require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");?>
