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
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/statistic/prolog.php");

$STAT_RIGHT = $APPLICATION->GetGroupRight("statistic");
if($STAT_RIGHT=="D")
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
$statDB = CDatabase::GetModuleConnection('statistic');
IncludeModuleLangFile(__FILE__);

$delay = intval($_REQUEST["delay"]);

function CheckFilter()
{
	global $arFilterFields, $statDB;
	foreach ($arFilterFields as $f) global $$f;
	$str = "";
	$arMsg = Array();
	$arr = array();

	$arr[] = array(
		"date1" => $find_date1,
		"date2" => $find_date2,
		"mess1" => GetMessage("STAT_WRONG_DATE_FROM"),
		"mess2" => GetMessage("STAT_WRONG_DATE_TILL"),
		"mess3" => GetMessage("STAT_FROM_TILL_DATE")
		);

	$arr[] = array(
		"date1" => $find_date_end1,
		"date2" => $find_date_end2,
		"mess1" => GetMessage("STAT_WRONG_DATE_END_FROM"),
		"mess2" => GetMessage("STAT_WRONG_DATE_END_TILL"),
		"mess3" => GetMessage("STAT_FROM_TILL_DATE_END")
		);

	foreach($arr as $ar)
	{
		if (strlen($ar["date1"])>0 && !CheckDateTime($ar["date1"]))
			$arMsg[] = array("id"=>"find_date1", "text"=> $ar["mess1"]);
		elseif (strlen($ar["date2"])>0 && !CheckDateTime($ar["date2"]))
			$arMsg[] = array("id"=>"find_date2", "text"=> $ar["mess2"]);
		elseif (strlen($ar["date1"])>0 && strlen($ar["date2"])>0 && $statDB->CompareDates($ar["date1"], $ar["date2"])==1)
			$arMsg[] = array("id"=>"find_date2", "text"=> $ar["mess3"]);
	}

	if (intval($find_hits1)>0 and intval($find_hits2)>0 and $find_hits1>$find_hits2)
		$arMsg[] = array("id"=>"find_hits2", "text"=> GetMessage("STAT_HITS1_HITS2"));

	if(!empty($arMsg))
	{
		$e = new CAdminException($arMsg);
		$GLOBALS["APPLICATION"]->ThrowException($e);
		return false;
	}

	return true;
}

$arSites = array();
$ref = $ref_id = array();
$rs = CSite::GetList(($v1="sort"), ($v2="asc"));
while ($ar = $rs->Fetch())
{
	$ref[] = $ar["ID"];
	$ref_id[] = $ar["ID"];
	$arSites[$ar["ID"]] = "[<a href=\"/bitrix/admin/site_edit.php?LID=".$ar["ID"]."&lang=".LANGUAGE_ID."\">".$ar["ID"]."</a>]&nbsp;";
}
$arSiteDropdown = array("reference" => $ref, "reference_id" => $ref_id);



$sTableID = "t_users_online";
$oSort = new CAdminSorting($sTableID,"s_session_time", "desc");
$lAdmin = new CAdminList($sTableID, $oSort);

$filter = new CAdminFilter(
	$sTableID."_filter_id",
	array(
		GetMessage("STAT_F_ID"),
		GetMessage("STAT_F_GUEST_ID"),
		GetMessage("STAT_F_AUTH"),
		GetMessage("STAT_F_NEW_GUEST"),
		GetMessage("STAT_F_IP"),
		GetMessage("STAT_COUNTRY"),
		GetMessage("STAT_F_STOP"),
		GetMessage("STAT_F_STOP_LIST_ID"),
		GetMessage("STAT_F_HITS"),
		GetMessage("STAT_F_CAME_ADV"),
		GetMessage("STAT_F_ADV"),
		"referer1 / referer2",
		"referer3",
		GetMessage("STAT_F_ADV_BACK"),
		GetMessage("STAT_FIRST_FROM_PAGE"),
		GetMessage("STAT_F_URL_LAST"),
	)
);

$arFilterFields = Array(
	"find_user",
	"find_id",
	"find_id_exact_match",
	"find_guest_id",
	"find_guest_id_exact_match",
	"find_registered",
	"find_new_guest",
	"find_ip",
	"find_ip_exact_match",
	"find_country_id",
	"find_country",
	"find_country_exact_match",
	"find_stop",
	"find_stop_list_id",
	"find_stop_list_id_exact_match",
	"find_hits1",
	"find_hits2",
	"find_adv",
	"find_adv_id",
	"find_adv_id_exact_match",
	"find_referer1",
	"find_referer2",
	"find_referer12_exact_match",
	"find_referer3",
	"find_referer3_exact_match",
	"find_adv_back",
	"find_first_from",
	"find_first_from_exact_match",
	"find_last_site_id",
	"find_url_last_404",
	"find_url_last",
	"find_url_last_exact_match",
);

$lAdmin->InitFilter($arFilterFields);

InitBVar($find_id_exact_match);
InitBVar($find_user_exact_match);
InitBVar($find_guest_id_exact_match);
InitBVar($find_ip_exact_match);
InitBVar($find_adv_id_exact_match);
InitBVar($find_referer12_exact_match);
InitBVar($find_referer12_exact_match);
InitBVar($find_referer3_exact_match);
InitBVar($find_user_agent_exact_match);
InitBVar($find_country_exact_match);
InitBVar($find_country_exact_match);
InitBVar($find_stop_list_id_exact_match);
InitBVar($find_url_last_exact_match);
InitBVar($find_first_from_exact_match);

if (CheckFilter())
{
	$arFilter = Array(
		"ID"			=> $find_id,
		"USER"			=> $find_user,
		"NEW_GUEST"		=> $find_new_guest,
		"GUEST_ID"		=> $find_guest_id,
		"IP"			=> $find_ip,
		"REGISTERED"	=> $find_registered,
		"HITS1"			=> $find_hits1,
		"HITS2"			=> $find_hits2,
		"ADV"			=> $find_adv,
		"ADV_ID"		=> $find_adv_id,
		"ADV_BACK"		=> $find_adv_back,
		"REFERER1"		=> $find_referer1,
		"REFERER2"		=> $find_referer2,
		"REFERER3"		=> $find_referer3,
		"COUNTRY_ID"	=> $find_country_id,
		"COUNTRY"		=> $find_country,
		"STOP"			=> $find_stop,
		"STOP_LIST_ID"	=> $find_stop_list_id,
		"FIRST_URL_FROM"		=> $find_first_from,

		"LAST_SITE_ID"	=> $find_last_site_id,
		"URL_LAST"	=> $find_url_last,
		"URL_LAST_404"	=> $find_url_last_404,

		"ID_EXACT_MATCH"			=> $find_id_exact_match,
		"USER_EXACT_MATCH"			=> $find_user_exact_match,
		"GUEST_ID_EXACT_MATCH"		=> $find_guest_id_exact_match,
		"IP_EXACT_MATCH"			=> $find_ip_exact_match,
		"ADV_ID_EXACT_MATCH"		=> $find_adv_id_exact_match,
		"REFERER1_EXACT_MATCH"		=> $find_referer12_exact_match,
		"REFERER2_EXACT_MATCH"		=> $find_referer12_exact_match,
		"REFERER3_EXACT_MATCH"		=> $find_referer3_exact_match,
		"USER_AGENT_EXACT_MATCH"	=> $find_user_agent_exact_match,
		"COUNTRY_EXACT_MATCH"		=> $find_country_exact_match,
		"COUNTRY_ID_EXACT_MATCH"	=> $find_country_exact_match,
		"STOP_LIST_ID_EXACT_MATCH"	=> $find_stop_list_id_exact_match,
		"URL_LAST_EXACT_MATCH"		=> $find_url_last_exact_match,
		"FIRST_URL_FROM_EXACT_MATCH"		=> $find_first_from_exact_match,
		);
}
else
{
	if($e = $APPLICATION->GetException())
		$GLOBALS["lAdmin"]->AddFilterError(GetMessage("STAT_FILTER_ERROR").": ".$e->GetString());
}


$arDelay=array(20,30,60,120,300);

if ($delay > 0) $_SESSION["SESS_DELAY"] = $delay;
if (intval($_SESSION["SESS_DELAY"])>0) $delay = intval($_SESSION["SESS_DELAY"]);
if (!in_array($delay, $arDelay)) $delay=30;

$rsData = CUserOnline::GetList($guest_count, $session_count, Array($by=>$order), $arFilter);

$s = str_replace("#SESSIONS#",$session_count,GetMessage("STAT_TITLE"));
$s = str_replace("#GUESTS#",$guest_count,$s);
$lAdmin->onLoadScript = "BX.adminPanel.setTitle('".addslashes($s)."');";

$APPLICATION->SetTitle($s);

$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart();

$lAdmin->NavText($rsData->GetNavPrint(GetMessage("STAT_USERS_PAGES")));

$arHeaders = Array();

$arHeaders[] = array("id"=>"ID", "content"=>GetMessage("STAT_VIEW_SESSION"), "default"=>false, "sort" => "s_id");

$arHeaders[] = array("id"=>"ADV_ID", "content"=>GetMessage("STAT_VIEW_ADV"), "default"=>true, "sort" => "s_adv_id");
$arHeaders[] = array("id"=>"HITS", "content"=>GetMessage("STAT_HITS"), "default"=>true, "sort" => "s_hits");
$arHeaders[] = array("id"=>"SESSION_TIME", "content"=>GetMessage("STAT_SESSION_TIME"), "default"=>true, "sort" => "s_session_time");

$arHeaders[] = array("id"=>"LAST_USER_ID", "content"=>GetMessage("STAT_USER"), "default"=>true, "sort" => "s_guest_id");
$arHeaders[] = array("id"=>"IP_LAST", "content"=>GetMessage("STAT_IP"), "default"=>true, "sort" => "s_ip");
$arHeaders[] = array("id"=>"COUNTRY_ID", "content"=>GetMessage("STAT_COUNTRY"), "default"=>true, "sort" => "s_country_id");
$arHeaders[] = array("id"=>"REGION_NAME", "content"=>GetMessage("STAT_REGION"), "default"=>false);
$arHeaders[] = array("id"=>"CITY_ID", "content"=>GetMessage("STAT_CITY"), "default"=>true);

$arHeaders[] = array("id"=>"URL_LAST", "content"=>GetMessage("STAT_LAST_TO_PAGE"), "default"=>true, "sort" => "s_url_last");
$arHeaders[] = array("id"=>"FIRST_URL_FROM", "content"=>GetMessage("STAT_FIRST_FROM_PAGE"), "default"=>true,);
$arHeaders[] = array("id"=>"URL_FROM", "content"=>GetMessage("STAT_LAST_FROM_PAGE"), "default"=>false, );

$lAdmin->AddHeaders($arHeaders);

//$rsSessions = CUserOnline::GetList($guest_count, $session_count);

$arrUsers = array();
while($arRes = $rsData->NavNext(true, "f_"))
{
	$row =& $lAdmin->AddRow($f_ID, $arRes);

	$str = '<a target="_blank" title="'.GetMessage("STAT_VIEW_SESSION").'" href="session_list.php?lang='.LANG.'&find_id='.$f_ID.'&find_id_exact_match=Y&set_filter=Y&rand='.rand().'">'.$f_ID.'</a>';
	$row->AddViewField("ID", $str);


	$str = '<a target="_blank" title="'.GetMessage("STAT_VIEW_SESSION").'" href="session_list.php?lang='.LANG.'&find_id='.$f_ID.'&find_id_exact_match=Y&set_filter=Y&rand='.rand().'">'.$f_ID.'</a>';
	$row->AddViewField("ID", $str);

	$str = "";
	if (intval($f_ADV_ID)>0) :
		$str .= '[<a title="'.GetMessage("STAT_VIEW_ADV").'" href="adv_list.php?lang='.LANG.'&find_id='.$f_ADV_ID.'&find_id_exact_match=Y&set_filter=Y">'.$f_ADV_ID.'</a>]';

		$str .= ($f_ADV_BACK=="Y" ? "* " : " ");


		if (strlen($f_REFERER1)>0) :
			$str .= '<a title="'.GetMessage("STAT_VIEW_REFERER_1").'" href="session_list.php?lang='.LANG.'&find_referer1='.urlencode("\"".$f_REFERER1."\"").'&find_referer12_exact_match=Y&set_filter=Y">'.$f_REFERER1.'</a>';
		endif;
		if (strlen($f_REFERER2)>0) :
			$str .= ' / <a title="'.GetMessage("STAT_VIEW_REFERER_2").'" href="session_list.php?lang='.LANG.'&find_referer2='.urlencode("\"".$f_REFERER2."\"").'&find_referer12_exact_match=Y&set_filter=Y">'.$f_REFERER2.'</a>';
		endif;
		if (strlen($f_REFERER3)>0) :
			$str .= '<br><a title="'.GetMessage("STAT_VIEW_REFERER_3").'" href="session_list.php?lang='.LANG.'&find_referer3='.urlencode("\"".$f_REFERER3."\"").'&find_referer3_exact_match=Y&set_filter=Y">'.$f_REFERER3.'</a>';
		endif;
		$row->AddViewField("ADV_ID", $str);
	endif;

	$str = "";

	if ($f_LAST_USER_ID>0) :

		$str .= "[<a target=\"_blank\" title=\"".GetMessage("STAT_EDIT_USER")."\" href=\"user_edit.php?lang=".LANG."&amp;ID=".$f_LAST_USER_ID."\">".$f_LAST_USER_ID."</a>] ";

		if(!array_key_exists($f_LAST_USER_ID, $arrUsers))
		{
			$rsUser = CUser::GetByID($f_LAST_USER_ID);
			$arUser = $rsUser->GetNext();
			$arrUsers[$f_LAST_USER_ID] = array(
				"USER_NAME" => $arUser["NAME"]." ".$arUser["LAST_NAME"],
				"LOGIN" => $arUser["LOGIN"],
			);
		}
		$USER_NAME = $arrUsers[$f_LAST_USER_ID]["USER_NAME"];
		$LOGIN = $arrUsers[$f_LAST_USER_ID]["LOGIN"];

		if (strlen($LOGIN)>0) :
			$str .= "(".$LOGIN.") ".$USER_NAME."</font>";
		endif;

		$str .= ($f_USER_AUTH!="Y" ? "<br><span class=\"stat_notauth\">".GetMessage("STAT_NOT_AUTH")."</span>" : "");

	else :
		$str .= GetMessage("STAT_NOT_REGISTERED");
		if (intval($f_STOP_LIST_ID)>0)
			$str .= "<br><span class=\"stat_attention\">".GetMessage("STAT_STOP")."</span>";
	endif;

	$str .= "<br>";

	$str .= ($f_NEW_GUEST=="Y" ? "<span class=\"stat_newguest\">".GetMessage("STAT_NEW_GUEST")."</span>" : "<span class='stat_oldguest'>".GetMessage("STAT_OLD_GUEST")."</span>");

	$str .=  "&nbsp;[<a href=\"guest_list.php?lang=".LANG."&amp;find_id=".$f_GUEST_ID."&amp;find_id_exact_match=Y&amp;set_filter=Y\">".$f_GUEST_ID."</a>]";

	$row->AddViewField("LAST_USER_ID", $str);

	$row->AddViewField("URL_LAST", StatAdminListFormatURL($arRes["URL_LAST"], array(
		"new_window" => true,
		"attention" => $f_URL_LAST_404 == "Y",
		"max_display_chars" => "default",
		"chars_per_line" => "default",
		"kill_sessid" => $STAT_RIGHT < "W",
	)));

	if(strlen($f_URL_FROM) > 0)
		$row->AddViewField("URL_FROM", StatAdminListFormatURL($arRes["URL_FROM"], array(
			"new_window" => true,
			"max_display_chars" => "default",
			"chars_per_line" => "default",
			"kill_sessid" => $STAT_RIGHT < "W",
		)));

	if(strlen($f_FIRST_URL_FROM) > 0)
	{
		$row->AddViewField("FIRST_URL_FROM", StatAdminListFormatURL($arRes["FIRST_URL_FROM"], array(
			"new_window" => true,
			"max_display_chars" => "default",
			"chars_per_line" => "default",
			"kill_sessid" => $STAT_RIGHT < "W",
		)));
	}

	$str = '<a href="hit_list.php?lang='.LANG.'&find_guest_id='.$f_GUEST_ID.'&find_guest_id_exact_match=Y&set_filter=Y">'.$f_HITS.'</a>';
	$row->AddViewField("HITS", $str);

	$row->AddViewField("IP_LAST", GetWhoisLink($f_IP_LAST));

	if (strlen($f_COUNTRY_ID)>0):
		$row->AddViewField("COUNTRY_ID", "[".$f_COUNTRY_ID."] ".$f_COUNTRY_NAME);
	endif;

	if (strlen($f_CITY_ID)>0):
		$row->AddViewField("CITY_ID", "[".$f_CITY_ID."] ".$f_CITY_NAME);
	endif;

	$str = "";
	$hours = IntVal($f_SESSION_TIME/3600);
	if ($hours>0) :
		$str .= $hours."&nbsp;".GetMessage("STAT_HOUR")." ";
	$f_SESSION_TIME = $f_SESSION_TIME - $hours*3600;
	endif;
	$str .= intval($f_SESSION_TIME/60)."&nbsp;".GetMessage("STAT_MIN")." ";
	$str .= ($f_SESSION_TIME%60)."&nbsp;".GetMessage("STAT_SEC");

	$row->AddViewField("SESSION_TIME", $str);

	$arActions = Array();

	$arActions[] = array(
		"ICON"=>"list",
		"TEXT"=>GetMessage("STAT_DETAIL"),
		"ACTION"=>"javascript:CloseWaitWindow(); jsUtils.OpenWindow('guest_detail.php?lang=".LANG."&ID=".$f_GUEST_ID."', '700', '550');",
		"DEFAULT" => "Y",
	);

	$arActions[] = array("SEPARATOR" => true);

	$arr = explode(".",$f_IP_LAST);
	$arActions[] = array(
		"ICON"=>"delete",
		"TITLE"=>GetMessage("STAT_ADD_TO_STOPLIST_TITLE"),
		"TEXT"=>GetMessage("STAT_STOP"),
		"ACTION"=>$lAdmin->ActionRedirect("stoplist_edit.php?lang=".LANG."&net1=".$arr[0]."&net2=".$arr[1]."&net3=".$arr[2]."&net4=".$arr[3]),
	);



	$row->AddActions($arActions);

}

$lAdmin->AddFooter(
	array(
		array("title"=>GetMessage("MAIN_ADMIN_LIST_SELECTED"), "value"=>$rsData->SelectedRowsCount()),
	)
);

$lAdmin->BeginPrologContent();?>

<p><?=GetMessage("STAT_REFRESH_TIME");?>
<?
	for($i=0;$i<count($arDelay);$i++) :
		if($arDelay[$i]!=$delay) :
			?> <a target="_top" href="javascript:Refresh(<?echo $arDelay[$i]?>);"><?echo $arDelay[$i]?></a> / <?
		else :
			echo $arDelay[$i]?> / <?
		endif;
	endfor;
	echo GetMessage("STAT_SEC");
?>
&nbsp;/&nbsp;<a target="_top" href="javascript:Refresh(<?=$delay?>);"><?=GetMessage("STAT_REFRESH");?></a>

&nbsp;(<span id="counter"><?=$delay;?></span>)
</p>
<?
$lAdmin->EndPrologContent();

$lAdmin->AddAdminContextMenu();
$lAdmin->CheckListMode();

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");?>

<script language="JavaScript">
var timeID = null;
var timeCounterID = null;

function Refresh(delay)
{
	if (timeID) clearTimeout(timeID);
	if (timeCounterID) clearTimeout(timeCounterID);
	<?=$sTableID?>.GetAdminList('/bitrix/admin/users_online.php?delay='+delay+'&lang=<?=LANG?>');
	timeID = setTimeout('Refresh('+delay+')', delay+'000');
	timeCounterID = setTimeout('ShowCounter('+delay+')',950);
}

function ShowCounter(counter)
{
	document.getElementById("counter").innerHTML = counter;
	if(counter == 0)
		return;
	counter--;
	timeCounterID = setTimeout('ShowCounter('+counter+')',950);
}

timeID = setTimeout('Refresh(<?=$delay?>)',<?=$delay?>000);
BX.ready(function(){ShowCounter(<?=$delay?>);});

</script>
<form name="form1" method="GET" action="<?=$APPLICATION->GetCurPage()?>?">
<?$filter->Begin();?>

<tr>
	<td><?echo GetMessage("STAT_F_USER")?>:</td>
	<td><input type="text" name="find_user" size="30" value="<?echo htmlspecialcharsbx($find_user)?>"><?=ShowExactMatchCheckbox("find_user")?>&nbsp;<?=ShowFilterLogicHelp()?></td>
</tr>

<tr>
	<td><?echo GetMessage("STAT_F_ID")?>:</td>
	<td><input type="text" name="find_id" size="30" value="<?echo htmlspecialcharsbx($find_id)?>"><?=ShowExactMatchCheckbox("find_id")?>&nbsp;<?=ShowFilterLogicHelp()?></td>
</tr>
<tr>
	<td><?echo GetMessage("STAT_F_GUEST_ID")?>:</td>
	<td><input type="text" name="find_guest_id" size="30" value="<?echo htmlspecialcharsbx($find_guest_id)?>"><?=ShowExactMatchCheckbox("find_guest_id")?>&nbsp;<?=ShowFilterLogicHelp()?></td>
</tr>

<tr>
	<td>
		<?echo GetMessage("STAT_F_AUTH")?>:</td>
	<td>
		<?
		$arr = array("reference"=>array(GetMessage("STAT_YES"), GetMessage("STAT_NO")), "reference_id"=>array("Y","N"));
		echo SelectBoxFromArray("find_registered", $arr, htmlspecialcharsbx($find_registered), GetMessage("MAIN_ALL"));
		?></td>
</tr>
<tr>
	<td><?echo GetMessage("STAT_F_NEW_GUEST")?>:</td>
	<td><?
		$arr = array("reference"=>array(GetMessage("STAT_NEW_GUEST_1"), GetMessage("STAT_OLD_GUEST_1")), "reference_id"=>array("Y","N"));
		echo SelectBoxFromArray("find_new_guest", $arr, htmlspecialcharsbx($find_new_guest), GetMessage("MAIN_ALL"));
		?></td>
</tr>
<tr>
	<td><?echo GetMessage("STAT_F_IP")?>:</td>
	<td><input type="text" name="find_ip" size="30" value="<?echo htmlspecialcharsbx($find_ip)?>"><?=ShowExactMatchCheckbox("find_ip")?>&nbsp;<?=ShowFilterLogicHelp()?></td>
</tr>

<tr>
	<td><?echo GetMessage("STAT_COUNTRY")?>:</td>
	<td valign="center">
	[&nbsp;<input type="text" name="find_country_id" size="5" value="<?echo htmlspecialcharsbx($find_country_id)?>">&nbsp;]&nbsp;&nbsp;&nbsp;
	<input type="text" name="find_country" size="30" value="<?echo htmlspecialcharsbx($find_country)?>"><?=ShowExactMatchCheckbox("find_country")?>&nbsp;<?=ShowFilterLogicHelp()?>
</tr>
<tr>
	<td><?echo GetMessage("STAT_F_STOP")?>:</td>
	<td><?
		$arr = array("reference"=>array(GetMessage("STAT_YES"), GetMessage("STAT_NO")), "reference_id"=>array("Y","N"));
		echo SelectBoxFromArray("find_stop", $arr, htmlspecialcharsbx($find_stop), GetMessage("MAIN_ALL"));
		?></td>
</tr>
<tr>
	<td><?echo GetMessage("STAT_F_STOP_LIST_ID")?>:</td>
	<td><input type="text" name="find_stop_list_id" size="30" value="<?echo htmlspecialcharsbx($find_stop_list_id)?>"><?=ShowExactMatchCheckbox("find_stop_list_id")?>&nbsp;<?=ShowFilterLogicHelp()?></td>
</tr>
<tr>
	<td>
		<?echo GetMessage("STAT_F_HITS")?>:</td>
	<td>
		<input type="text" name="find_hits1" size="10" value="<?echo htmlspecialcharsbx($find_hits1)?>"><?echo "&nbsp;".GetMessage("STAT_TILL")."&nbsp;"?><input type="text" name="find_hits2" size="10" value="<?echo htmlspecialcharsbx($find_hits2)?>"></td>
</tr>

<tr>
	<td><?echo GetMessage("STAT_F_CAME_ADV")?>:</td>
	<td><?
		$arr = array("reference"=>array(GetMessage("STAT_YES"), GetMessage("STAT_NO")), "reference_id"=>array("Y","N"));
		echo SelectBoxFromArray("find_adv", $arr, htmlspecialcharsbx($find_adv), GetMessage("MAIN_ALL"));
		?></td>
</tr>
<tr>
	<td><?echo GetMessage("STAT_F_ADV")?>:</td>
	<td><input type="text" name="find_adv_id" size="30" value="<?echo htmlspecialcharsbx($find_adv_id)?>"><?=ShowExactMatchCheckbox("find_adv_id")?>&nbsp;<?=ShowFilterLogicHelp()?></td>
</tr>
<tr>
	<td>referer1 / referer2:</td>
	<td><input type="text" name="find_referer1" size="14" value="<?echo htmlspecialcharsbx($find_referer1)?>">&nbsp;/&nbsp;<input type="text" name="find_referer2" size="14" value="<?echo htmlspecialcharsbx($find_referer2)?>"><?=ShowExactMatchCheckbox("find_referer12")?>&nbsp;<?=ShowFilterLogicHelp()?></td>
</tr>
<tr>
	<td>referer3:</td>
	<td><input type="text" name="find_referer3" size="30" value="<?echo htmlspecialcharsbx($find_referer3)?>"><?=ShowExactMatchCheckbox("find_referer3")?>&nbsp;<?=ShowFilterLogicHelp()?></td>
</tr>
<tr>
	<td>
		<?echo GetMessage("STAT_F_ADV_BACK")?>:</td>
	<td>
		<?
		$arr = array("reference"=>array(GetMessage("STAT_YES"), GetMessage("STAT_NO")), "reference_id"=>array("Y","N"));
		echo SelectBoxFromArray("find_adv_back", $arr, htmlspecialcharsbx($find_adv_back), GetMessage("MAIN_ALL"));
		?></td>
</tr>
<tr>
	<td><?echo GetMessage("STAT_FIRST_FROM_PAGE")?>:</td>
	<td><input type="text" name="find_first_from" size="34" value="<?echo htmlspecialcharsbx($find_first_from)?>"><?=ShowExactMatchCheckbox("find_first_from")?>&nbsp;<?=ShowFilterLogicHelp()?></td>
</tr>
<tr>
	<td><?echo GetMessage("STAT_F_URL_LAST")?>:</td>
	<td><?
		echo SelectBoxFromArray("find_last_site_id", $arSiteDropdown, $find_last_site_id, GetMessage("STAT_D_SITE"));
	?>&nbsp;<?
		echo SelectBoxFromArray("find_url_last_404", array("reference"=>array(GetMessage("STAT_YES"), GetMessage("STAT_NO")), "reference_id"=>array("Y","N")), htmlspecialcharsbx($find_url_last_404), GetMessage("STAT_404"));
	?>&nbsp;<input type="text" name="find_url_last" size="34" value="<?echo htmlspecialcharsbx($find_url_last)?>"><?=ShowExactMatchCheckbox("find_url_last")?>&nbsp;<?=ShowFilterLogicHelp()?></td>
</tr>
<?//=ShowLogicRadioBtn()?>
<?$filter->Buttons(array("table_id"=>$sTableID, "url"=>$APPLICATION->GetCurPage(), "form"=>"form1"));$filter->End();?>
</form>

<?
if($message)
	echo $message->Show();
$lAdmin->DisplayList();
?>

<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>
