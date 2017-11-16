<? 
/*
##############################################
# Bitrix: SiteManager                        #
# Copyright (c) 2004 Bitrix                  #
# http://www.bitrix.ru                       #
# mailto:admin@bitrix.ru                     #
##############################################
*/

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/support/prolog.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/support/include.php");
IncludeModuleLangFile(__FILE__);

$bDemo = (CTicket::IsDemo()) ? "Y" : "N";
$bAdmin = (CTicket::IsAdmin()) ? "Y" : "N";

if($bAdmin!="Y" && $bDemo!="Y")
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

//CTicket::GetRoles($isDemo, $isSupportClient, $isSupportTeam, $isAdmin, $isAccess, $USER_ID, $CHECK_RIGHTS);
//if(!$isAdmin && !$isDemo) $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$EDIT_URL = "/bitrix/admin/ticket_sla_edit.php";
$LIST_URL = $APPLICATION->GetCurPage();

$arErrors = array();

/***************************************************************************
									Функции
****************************************************************************/

function Support_GetUserInfo($USER_ID, &$login, &$name)
{
	static $arrUsers;
	$login = "";
	$name = "";
	if (intval($USER_ID)>0)
	{
		if (is_array($arrUsers) && in_array($USER_ID, array_keys($arrUsers)))
		{
			$login = $arrUsers[$USER_ID]["LOGIN"];
			$name = $arrUsers[$USER_ID]["NAME"];
		}
		else
		{
			$rsUser = CUser::GetByID($USER_ID);
			$arUser = $rsUser->Fetch();
			$login = htmlspecialcharsbx($arUser["LOGIN"]);
			$name = htmlspecialcharsbx($arUser["NAME"]." ".$arUser["LAST_NAME"]);
			$arrUsers[$USER_ID] = array("LOGIN" => $login, "NAME" => $name);
		}
	}
}


$sTableID = "t_sla_list";
$oSort = new CAdminSorting($sTableID, "SORT", "asc");// инициализация сортировки
$lAdmin = new CAdminList($sTableID, $oSort);// инициализация списка

$filter = new CAdminFilter(
	$sTableID."_filter_id", 
	array(
		"ID",
		GetMessage("SUP_SITE"),
		GetMessage("SUP_DESCRIPTION"),
	)
);

$arFilterFields = Array(
	"find_name",
	"find_name_exact_match",
	"find_id",
	"find_id_exact_match",
	"find_description",
	"find_description_exact_match",
	"find_site",
	);

$lAdmin->InitFilter($arFilterFields);//инициализация фильтра


foreach($arFilterFields as $key) 
{
	if (strpos($key, "_exact_match")!==false) InitBVar(${$key});
	$arFilter[strtoupper(substr($key,5))] = ${$key};
}


if ($bAdmin=="Y" && $lAdmin->EditAction()) //если идет сохранение со списка
{
	foreach($FIELDS as $ID => $arFields)
	{
		$ID = intval($ID);

		if(!$lAdmin->IsUpdated($ID))
			continue;

		$arFields["PRIORITY"] = intval($arFields["PRIORITY"]);

		if (strlen(trim($arFields["NAME"]))>0)
		{
			CTicketSLA::Set(array("NAME" => $arFields["NAME"], "PRIORITY" => $arFields["PRIORITY"]), $ID);
		}
		else
		{
			$lAdmin->AddUpdateError(str_replace("#ID#", $ID, GetMessage("SUP_FORGOT_NAME")), $ID);
		}
	}
}


if($bAdmin=="Y" && $arID = $lAdmin->GroupAction())
{
	if($_REQUEST['action_target']=='selected')
	{
		$rsData = CTicketSLA::GetList($arSort, $arFilter, $is_filtered);
		while($arRes = $rsData->Fetch())
			$arID[] = $arRes['ID'];
	}

	foreach($arID as $ID)
	{
		if(strlen($ID)<=0)
			continue;
		$ID = intval($ID);

		switch($_REQUEST['action'])
		{
			case "delete":
				@set_time_limit(0);
				if (!CTicketSLA::Delete($ID))
				{
					if ($e = $APPLICATION->GetException())
					{
						$lAdmin->AddGroupError($e->GetString(), $ID);
					}
				}
			break;
		}
	}
}

$arSort = (strlen($by)>0 && strlen($order)>0) ? array($by=>$order) : "";


$rsData = CTicketSLA::GetList($arSort, $arFilter, $is_filtered);
$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart(50);

// установка строки навигации

$lAdmin->NavText($rsData->GetNavPrint(GetMessage("SUP_PAGES")));


$arHeaders = Array();
$arHeaders[] = Array("id"=>"ID", "content"=>"ID", "default"=>true, "sort" => "ID");
$arHeaders[] = Array("id"=>"PRIORITY", "content"=>GetMessage("SUP_PRIORITY"), "default"=>true, "sort" => "PRIORITY");
$arHeaders[] = Array("id"=>"SITE_ID", "content"=>GetMessage("SUP_SITE"), "default"=>true);
$arHeaders[] = Array("id"=>"NAME", "content"=>GetMessage("SUP_NAME"), "default"=>true, "sort" => "NAME");
$arHeaders[] = Array("id"=>"DESCRIPTION", "content"=>GetMessage("SUP_DESCRIPTION"), "default"=>false, "sort" => "DESCRIPTION");
$arHeaders[] = Array("id"=>"RESPONSE_TIME", "content"=>GetMessage("SUP_RESPONSE_TIME"), "default"=>true, "sort" => "RESPONSE_TIME");
$arHeaders[] = Array("id"=>"GROUP_ID", "content"=>GetMessage("SUP_USER_GROUPS"), "default"=>true,);
$arHeaders[] = Array("id"=>"RESPONSIBLE_USER_ID", "content"=>GetMessage("SUP_RESPONSIBLE"), "default"=>true, "sort" => "RESPONSIBLE_USER_ID");
//$arHeaders[] = Array("id"=>"TIMETABLE_ID", "content"=>GetMessage("SUP_SHEDULE_S"), "default"=>true, "sort" => "TIMETABLE_ID");

$lAdmin->AddHeaders($arHeaders);

// построение списка
while($arRes = $rsData->NavNext(true, "f_"))
{
	$row =& $lAdmin->AddRow($f_ID, $arRes);

	$str_SITE = "";
	$arrSITE =  CTicketSLA::GetSiteArray($f_ID);
	reset($arrSITE);
	if (is_array($arrSITE))
	{
		foreach($arrSITE as $sid)
		{
			if ($sid!="ALL")
			{
				$str_SITE .= ($str_SITE == "" ? "" : " / ").'<a title="'.GetMessage("MAIN_ADMIN_MENU_EDIT").'" href="/bitrix/admin/site_edit.php?LID='.$sid.'&lang='.LANG.'">'.$sid.'</a>';
			}
			else $str_SITE .= GetMessage("SUP_ALL");
		}
	}

	$row->AddViewField("SITE_ID", $str_SITE);

	$row->AddInputField("NAME", Array("size"=>"35"));
	$row->AddInputField("PRIORITY", Array("size"=>"3"));


	$str = "";
	if (intval($f_RESPONSE_TIME)>0):
		$str .= $f_RESPONSE_TIME." ";
		switch ($f_RESPONSE_TIME_UNIT)
		{
			case "hour": 
			$str .= __PrintRussian($f_RESPONSE_TIME, array(GetMessage("SUP_HOUR_1"), GetMessage("SUP_HOUR_3"), GetMessage("SUP_HOUR_5"))); 
			break;

			case "minute": 
			$str .= __PrintRussian($f_RESPONSE_TIME, array(GetMessage("SUP_MINUTE_1"), GetMessage("SUP_MINUTE_3"), GetMessage("SUP_MINUTE_5"))); 
			break;

			case "day": 
			$str .= __PrintRussian($f_RESPONSE_TIME, array(GetMessage("SUP_DAY_1"), GetMessage("SUP_DAY_3"), GetMessage("SUP_DAY_5"))); 
			break;
		}
	else:
		$str .= "<nobr>".GetMessage("SUP_NO_LIMITS")."</nobr>";
	endif;

		$row->AddViewField("RESPONSE_TIME", $str);


	$str = "";
	$arG = CTicketSLA::GetGroupArray($f_ID);
	foreach($arG as $gid)
	{
		if (!is_array($arGROUPS) || !in_array($gid, array_keys($arGROUPS)))
		{
			$rs = CGroup::GetByID($gid);
			if ($ar = $rs->Fetch()) $arGROUPS[$gid] = $ar["NAME"];
		}
		$str .= '[<a title="'.GetMessage("MAIN_ADMIN_MENU_EDIT").'" href="/bitrix/admin/group_edit.php?ID='.$gid.'&lang='.LANG.'">'.$gid.'</a>] '.htmlspecialcharsbx($arGROUPS[$gid]).'<br>';
	}

	$row->AddViewField("GROUP_ID", $str);

	$str = "&nbsp;";

	if (intval($f_RESPONSIBLE_USER_ID)>0):
		Support_GetUserInfo($f_RESPONSIBLE_USER_ID, $f_RESPONSIBLE_LOGIN, $f_RESPONSIBLE_NAME);
		$str = '[<a title="'.GetMessage("SUP_USER_PROFILE").'" href="/bitrix/admin/user_edit.php?lang='.LANG.'&ID='.$f_RESPONSIBLE_USER_ID.'">'.$f_RESPONSIBLE_USER_ID.'</a>] ('.$f_RESPONSIBLE_LOGIN.') '.$f_RESPONSIBLE_NAME;
	endif;

	$row->AddViewField("RESPONSIBLE_USER_ID", $str);

	$arActions = Array();

	$arActions[] = array(
		"ICON"=>"edit",
		"DEFAULT" => "Y",
		"TEXT"=>GetMessage("SUP_EDIT"),
		"ACTION"=>$lAdmin->ActionRedirect($EDIT_URL."?lang=".LANGUAGE_ID."&ID=".$f_ID)
	);

	if ($bAdmin=="Y")
	{
		$arActions[] = array("SEPARATOR" => true);

		$arActions[] = array(
		"ICON" => "delete",
		"TEXT"	=> GetMessage("SUP_DELETE"),
		"ACTION"=>"if(confirm('".GetMessage('SUP_DELETE_CONFIRMATION')."')) ".$lAdmin->ActionDoGroup($f_ID, "delete"),
		);
	}

	$row->AddActions($arActions);

}

// "подвал" списка
$lAdmin->AddFooter(
	array(
		array("title"=>GetMessage("MAIN_ADMIN_LIST_SELECTED"), "value"=>$rsData->SelectedRowsCount()),
		array("counter"=>true, "title"=>GetMessage("MAIN_ADMIN_LIST_CHECKED"), "value"=>"0"),
	)
);

if ($bAdmin=="Y")
{
	$lAdmin->AddGroupActionTable(Array(
		"delete"=>GetMessage("MAIN_ADMIN_LIST_DELETE"),
		)
	);
}

$aContext = array(
	array(
		"ICON"=> "btn_new",
		"TEXT"=> GetMessage("SUP_ADD"),
		"LINK"=>$EDIT_URL."?lang=".LANG,
		"TITLE"=>GetMessage("SUP_ADD")
	),
);


$lAdmin->AddAdminContextMenu($aContext);

$lAdmin->CheckListMode();


//$rsRecords = CTicketSLA::GetList($arSort, $arFilter, $is_filtered);
//reset($arSort); list($by, $order) = each($arSort);
//if($obException = $APPLICATION->GetException()) $strError .= $obException->GetString()."<br>";

$APPLICATION->SetTitle(GetMessage("SUP_PAGE_TITLE"));
/***************************************************************************
								HTML форма
****************************************************************************/
require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");?>

<form name="form1" method="GET" action="<?=$APPLICATION->GetCurPage()?>?">
<?$filter->Begin();?>
<tr> 
	<td><?=GetMessage("SUP_NAME")?>:</td>
	<td><input type="text" name="find_name" size="47" value="<?=htmlspecialcharsbx($find_name)?>"><?=InputType("checkbox", "find_name_exact_match", "Y", $find_name_exact_match, false, "", "title='".GetMessage("SUP_EXACT_MATCH")."'")?>&nbsp;<?=ShowFilterLogicHelp()?></td>
</tr>

<tr> 
	<td>ID:</td>
	<td><input type="text" name="find_id" size="47" value="<?=htmlspecialcharsbx($find_id)?>"><?=InputType("checkbox", "find_id_exact_match", "Y", $find_id_exact_match, false, "", "title='".GetMessage("SUP_EXACT_MATCH")."'")?>&nbsp;<?=ShowFilterLogicHelp()?></td>
</tr>
<tr valign="top"> 
	<td valign="top"><?=GetMessage("SUP_SITE")?>:<br><img src="/bitrix/images/support/mouse.gif" width="44" height="21" border=0 alt=""></td>
	<td><?
	$ref = array(GetMessage("SUP_ALL"));
	$ref_id = array("ALL");
	$rs = CSite::GetList(($v1="sort"), ($v2="asc"));
	while ($ar = $rs->Fetch()) 
	{
		$ref[] = "[".$ar["ID"]."] ".$ar["NAME"];
		$ref_id[] = $ar["ID"];
	}
	echo SelectBoxMFromArray("find_site[]", array("reference" => $ref, "reference_id" => $ref_id), $find_site, "",false,"4");
	?></td>
</tr>

<tr> 
	<td><?=GetMessage("SUP_DESCRIPTION")?>:</td>
	<td><input type="text" name="find_description" size="47" value="<?=htmlspecialcharsbx($find_description)?>"><?=InputType("checkbox", "find_description_exact_match", "Y", $find_description_exact_match, false, "", "title='".GetMessage("SUP_EXACT_MATCH")."'")?>&nbsp;<?=ShowFilterLogicHelp()?></td>
</tr>

<?
$filter->Buttons(array("table_id"=>$sTableID, "url"=>$APPLICATION->GetCurPage(), "form"=>"form1"));
$filter->End();
?>
</form>


<?

$lAdmin->DisplayList();


?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>