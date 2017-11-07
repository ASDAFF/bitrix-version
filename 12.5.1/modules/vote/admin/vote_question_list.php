<?
##############################################
# Bitrix Site Manager Forum					 #
# Copyright (c) 2002-2009 Bitrix			 #
# http://www.bitrixsoft.com					 #
# mailto:admin@bitrixsoft.com				 #
##############################################
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/vote/prolog.php");
$VOTE_RIGHT = $APPLICATION->GetGroupRight("vote");
if($VOTE_RIGHT=="D") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/vote/include.php");

IncludeModuleLangFile(__FILE__);
$err_mess = "File: ".__FILE__."<br>Line: ";
define("HELP_FILE","vote_list.php");

$sTableID = "tbl_vote_question";
$oSort = new CAdminSorting($sTableID, "ID", "asc");
$lAdmin = new CAdminList($sTableID, $oSort);

$aMenu = array();

$VOTE_ID = intval($VOTE_ID);
$z = CVote::GetByID($VOTE_ID);
if (!$arVote = $z->Fetch()) 
{
	require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
	echo "<a href='vote_list.php?lang=".LANGUAGE_ID."' class='navchain'>".GetMessage("VOTE_VOTE_LIST")."</a>";
	echo ShowError(GetMessage("VOTE_NOT_FOUND"));
	require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
	die();
}

$t = CVoteChannel::GetByID($arVote["CHANNEL_ID"]);
$arChannel = $t->Fetch();

$adminChain->AddItem(array(
	"TEXT" => htmlspecialcharsbx($arChannel["TITLE"]), 
	"LINK"=> "vote_channel_edit.php?ID=$arChannel[ID]&lang=".LANGUAGE_ID));
$adminChain->AddItem(array(
	"TEXT"=> (strlen($arVote["TITLE"]) > 0 ? htmlspecialcharsbx($arVote["TITLE"]) : TruncateText(
	($arVote["DESCRIPTION_TYPE"] == "html" ? strip_tags($arVote["DESCRIPTION"]) : htmlspecialcharsbx($arVote["DESCRIPTION"])), 200)), 
	"LINK"=> "vote_edit.php?ID=$arVote[ID]&lang=".LANGUAGE_ID));

$arFilterFields = Array(
	"find_id", 
	"find_id_exact_match",
	"find_active",
	"find_diagram",
	"find_required",
	"find_question",
	"find_question_exact_match");

$lAdmin->InitFilter($arFilterFields);
/********************************************************************
				Actions
********************************************************************/
InitBVar($find_id_exact_match);
InitBVar($find_question_exact_match);
$arFilter = Array(
	"ID"					=> $find_id,
	"ID_EXACT_MATCH"		=> $find_id_exact_match,
	"ACTIVE"				=> $find_active,
	"DIAGRAM"				=> $find_diagram,
	"REQUIRED"				=> $find_required,
	"QUESTION"				=> $find_question,
	"QUESTION_EXACT_MATCH"	=> $find_question_exact_match);

if ($lAdmin->EditAction() && $VOTE_RIGHT>="W" && check_bitrix_sessid())
{
		foreach($FIELDS as $ID=>$arFields)
		{
				if(!$lAdmin->IsUpdated($ID))
						continue;
		$DB->StartTransaction();
		InitBVar($arFields["REQUIRED"]);
		InitBVar($arFields["DIAGRAM"]);
		InitBVar($arFields["ACTIVE"]);
		$arFieldsStore = Array(
			"ACTIVE"		=> "'$arFields[ACTIVE]'",
			"DIAGRAM"		=> "'$arFields[DIAGRAM]'",
			"REQUIRED"		=> "'$arFields[REQUIRED]'",
			"C_SORT"		=> "'".intval($arFields[C_SORT])."'",
			"QUESTION"		=> "'".$DB->ForSql($arFields[QUESTION])."'",
		);

		if (!$DB->Update("b_vote_question",$arFieldsStore,"WHERE ID='".$ID."'",$err_mess.__LINE__))
		{
			$lAdmin->AddUpdateError(GetMessage("SAVE_ERROR").$ID.": ".GetMessage("VOTE_SAVE_ERROR"), $ID);
			$DB->Rollback();
		}
		$DB->Commit();
		global $CACHE_MANAGER;
		if (defined("BX_COMP_MANAGED_CACHE"))
		{
			$CACHE_MANAGER->ClearByTag("vote_form_question_".$ID);
		} 
	}
}

if(($arID = $lAdmin->GroupAction()) && $VOTE_RIGHT=="W" && check_bitrix_sessid())
{
		if($_REQUEST['action_target']=='selected')
		{
				$arID = Array();
				$rsData = CVoteQuestion::GetList($VOTE_ID, $by, $order, $arFilter, $is_filtered);
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
						if(!CVoteQuestion::Delete($ID))
						{
								$DB->Rollback();
								$lAdmin->AddGroupError(GetMessage("DELETE_ERROR"), $ID);
						}
						$DB->Commit();
						break;
				case "activate":
				case "deactivate":
						$arFields = Array("ACTIVE"=>($_REQUEST['action']=="activate"?"'Y'":"'N'"));
						if (!$DB->Update("b_vote_question",$arFields,"WHERE ID='$ID'",$err_mess.__LINE__))
								$lAdmin->AddGroupError(GetMessage("VOTE_SAVE_ERROR"), $ID);
						break;
				}
		}
}

$rsData = CVoteQuestion::GetList($VOTE_ID, $by, $order, $arFilter, $is_filtered);
$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart();

$lAdmin->NavText($rsData->GetNavPrint(GetMessage("VOTE_PAGES")));


$lAdmin->AddHeaders(array(
				array("id"=>"ID", "content"=>"ID", "sort"=>"s_id", "default"=>true),
				array("id"=>"TIMESTAMP_X", "content"=>GetMessage("VOTE_TIMESTAMP_X"), "sort"=>"s_timestamp_x", "default"=>true),
				array("id"=>"ACTIVE", "content"=>GetMessage("VOTE_ACTIVE"), "sort"=>"s_active", "default"=>true),
				array("id"=>"DIAGRAM", "content"=>GetMessage("VOTE_DIAGRAM"), "sort"=>"s_diagram", "default"=>true),
				array("id"=>"REQUIRED", "content"=>GetMessage("VOTE_REQUIRED"), "sort"=>"s_required", "default"=>true),
				array("id"=>"C_SORT", "content"=>GetMessage("VOTE_C_SORT"), "sort"=>"s_c_sort", "default"=>true),
				array("id"=>"QUESTION", "content"=>GetMessage("VOTE_QUESTION"), "sort"=>"s_question", "default"=>true),
		)
);

while($arRes = $rsData->NavNext(true, "f_"))
{
		$row =& $lAdmin->AddRow($f_ID, $arRes);

		if ($VOTE_RIGHT=="W")
		{
				$row->AddViewField("SITE",trim($str, " ,"));
				$row->AddCheckField("ACTIVE");
				$row->AddCheckField("DIAGRAM");
				$row->AddCheckField("REQUIRED");
				$row->AddInputField("C_SORT");
				$row->AddInputField("QUESTION");
		}
	else
	{
		$row->AddViewField("ACTIVE",$f_ACTIVE=="Y"?GetMessage("MAIN_YES"):GetMessage("MAIN_NO"));
		$row->AddViewField("DIAGRAM",$f_DIAGRAM=="Y"?GetMessage("MAIN_YES"):GetMessage("MAIN_NO"));
		$row->AddViewField("REQUIRED",$f_REQUIRED=="Y"?GetMessage("MAIN_YES"):GetMessage("MAIN_NO"));
	}
		$arActions = Array();
		$arActions[] = array("ICON"=>"edit", "DEFAULT" => true, "TEXT"=>GetMessage("MAIN_ADMIN_MENU_EDIT"), "ACTION"=>$lAdmin->ActionRedirect("vote_question_edit.php?ID=$f_ID&VOTE_ID=$VOTE_ID"));
		$arActions[] = array("ICON"=>"delete", "TEXT"=>GetMessage("MAIN_ADMIN_MENU_DELETE"), "ACTION"=>"if(confirm('".GetMessage("VOTE_CONFIRM_DEL_QUESTION")."')) window.location='vote_question_list.php?lang=".LANGUAGE_ID."&VOTE_ID=$VOTE_ID&action=delete&ID=$f_ID&".bitrix_sessid_get()."'");

		if ($VOTE_RIGHT=="W")
			$row->AddActions($arActions);

}

$lAdmin->AddFooter(
		array(
				array("title"=>GetMessage("MAIN_ADMIN_LIST_SELECTED"), "value"=>$rsData->SelectedRowsCount()),
				array("counter"=>true, "title"=>GetMessage("MAIN_ADMIN_LIST_CHECKED"), "value"=>"0"),
		)
);

if ($VOTE_RIGHT=="W")
	$lAdmin->AddGroupActionTable(Array(
		"delete"=>GetMessage("VOTE_DELETE"),
		"activate"=>GetMessage("VOTE_ACTIVATE"),
		"deactivate"=>GetMessage("VOTE_DEACTIVATE"),
		));

if ($VOTE_RIGHT=="W")
{
	$aMenu[] = array(
		"TEXT"	=> GetMessage("VOTE_CREATE"),
		"TITLE"=>GetMessage("VOTE_ADD_QUESTION"),
				"LINK"=>"vote_question_edit.php?lang=".LANG."&VOTE_ID=$VOTE_ID",
		"ICON" => "btn_new"
	);
	
	$aContext = $aMenu;

$lAdmin->AddAdminContextMenu($aContext);
}

$lAdmin->CheckListMode();

/********************************************************************
				Form
********************************************************************/
$APPLICATION->SetTitle(str_replace("#ID#","$VOTE_ID",GetMessage("VOTE_PAGE_TITLE")));
require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
?>
<form name="form1" method="GET" action="<?=$APPLICATION->GetCurPage()?>?">
<?
$oFilter = new CAdminFilter(
		$sTableID."_filter",
		array(
				GetMessage("VOTE_FLT_ID"),
				GetMessage("VOTE_FLT_ACTIVE"),
				GetMessage("VOTE_FLT_DIAGRAM"),
				GetMessage("VOTE_FLT_REQUIRED")
		)
);

$oFilter->Begin();
?>
<tr>
	<td nowrap><b><?=GetMessage("VOTE_F_QUESTION")?></b></td>
	<td nowrap><input type="text" name="find_question" value="<?echo htmlspecialcharsbx($find_question)?>" size="47"><?=InputType("checkbox", "find_question_exact_match", "Y", $find_question_exact_match, false, "", "title='".GetMessage("VOTE_EXACT_MATCH")."'")?>&nbsp;<?=ShowFilterLogicHelp()?></td>
</tr>
<tr> 
	<td>ID:</td>
	<td><input type="text" name="find_id" size="47" value="<?echo htmlspecialcharsbx($find_id)?>"><?=InputType("checkbox", "find_id_exact_match", "Y", $find_id_exact_match, false, "", "title='".GetMessage("VOTE_EXACT_MATCH")."'")?>&nbsp;<?=ShowFilterLogicHelp()?></td>
</tr>
<tr>
	<td nowrap><?echo GetMessage("VOTE_F_ACTIVE")?></td>
	<td nowrap><?
		$arr = array("reference"=>array(GetMessage("VOTE_YES"), GetMessage("VOTE_NO")), "reference_id"=>array("Y","N"));
		echo SelectBoxFromArray("find_active", $arr, htmlspecialcharsbx($find_active), GetMessage("VOTE_ALL"));
		?></td>
</tr>
<tr valign="top">
	<td nowrap><?echo GetMessage("VOTE_F_DIAGRAM")?></td>
	<td nowrap><?
		$arr = array("reference"=>array(GetMessage("VOTE_YES"), GetMessage("VOTE_NO")), "reference_id"=>array("Y","N"));
		echo SelectBoxFromArray("find_diagram", $arr, htmlspecialcharsbx($find_diagram), GetMessage("VOTE_ALL"));
		?></td>
</tr>
<tr valign="top">
	<td nowrap><?echo GetMessage("VOTE_F_REQUIRED")?></td>
	<td nowrap><?
		$arr = array("reference"=>array(GetMessage("VOTE_YES"), GetMessage("VOTE_NO")), "reference_id"=>array("Y","N"));
		echo SelectBoxFromArray("find_required", $arr, htmlspecialcharsbx($find_required), GetMessage("VOTE_ALL"));
		?></td>
</tr>
<?
$oFilter->Buttons(array("table_id"=>$sTableID, "url"=>"/bitrix/admin/vote_question_list.php?lang=".LANGUAGE_ID."&VOTE_ID=$VOTE_ID", "form"=>"form1"));
$oFilter->End();
?>
</form>
<?
$lAdmin->DisplayList();

require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php"); 
?>
