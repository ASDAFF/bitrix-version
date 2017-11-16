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
require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/statistic/prolog.php");
$STAT_RIGHT = $APPLICATION->GetGroupRight("statistic");
if($STAT_RIGHT=="D") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

IncludeModuleLangFile(__FILE__);

$ref = $ref_id = array();
$rs = CSite::GetList(($v1="sort"), ($v2="asc"));
while ($ar = $rs->Fetch())
{
	$ref[] = "[".$ar["ID"]."] ".$ar["NAME"];
	$ref_id[] = $ar["ID"];
}
$arSiteDropdown = array("reference" => $ref, "reference_id" => $ref_id);

$sTableID = "t_stat_list";
$sFilterID = $sTableID."_filter_id";
$oSort = new CAdminSorting($sTableID);
$lAdmin = new CAdminList($sTableID, $oSort);

$FilterArr = Array(
	"find_date1",
	"find_date2",
	"find_site_id"
);

$lAdmin->InitFilter($FilterArr);

$strError="";
AdminListCheckDate($strError, array("find_date1"=>$find_date1, "find_date2"=>$find_date2));

$arFilter = Array(
	"SITE_ID"	=> $find_site_id,
	"DATE1"		=> $find_date1,
	"DATE2"		=> $find_date2
);

if (strlen($find_site_id)>0 && $find_site_id!="NOT_REF")
	$site_filter="Y";
else
	$site_filter="N";

if (strlen($arFilter["DATE1"])>0 || strlen($arFilter["DATE2"])>0)
	$is_filtered = true;
else
	$is_filtered = false;

$now_date = GetTime(time());
$yesterday_date = GetTime(time()-86400);
$bef_yesterday_date = GetTime(time()-172800);

$sTableID_tab1 = "t_stat_list_tab1";
$oSort_tab1 = new CAdminSorting($sTableID_tab1);
$lAdmin_tab1 = new CAdminList($sTableID_tab1, $oSort_tab1);
$lAdmin_tab1->BeginCustomContent();
if(strlen($strError)>0):
	CAdminMessage::ShowMessage($strError);
elseif($_REQUEST["table_id"]=="" || $_REQUEST["table_id"]==$sTableID_tab1):
	$arComm = CTraffic::GetCommonValues($arFilter);
?>
<table border="0" cellspacing="0" cellpadding="0" width="100%" class="list-table">
<tr class="heading">
	<td width="30%">&nbsp;</td>
	<td><?echo GetMessage("STAT_TODAY")?><br><?=$now_date?></td>
	<td><?echo GetMessage("STAT_YESTERDAY")?><br><?=$yesterday_date?></td>
	<td><?echo GetMessage("STAT_BEFORE_YESTERDAY")?><br><?=$bef_yesterday_date?></td>
	<?if ($is_filtered) : ?>
		<td><?echo GetMessage("STAT_PERIOD")?><br><?=$arFilter["DATE1"]?>&nbsp;- <?=$arFilter["DATE2"]?></td>
	<?endif;?>
	<td><?echo GetMessage("STAT_TOTAL_1")?></td>
</tr>
<tr>
	<td><?echo GetMessage("STAT_HITS")?></td>
	<td class="bx-digit-cell"><a href="hit_list.php?lang=<?=LANG?>&amp;find_date1=<?echo $now_date?>&amp;find_date2=<?echo $now_date?>&amp;set_filter=Y"><?echo $arComm["TODAY_HITS"]?></a></td>
	<td class="bx-digit-cell"><a href="hit_list.php?lang=<?=LANG?>&amp;find_date1=<?echo $yesterday_date?>&amp;find_date2=<?echo $yesterday_date?>&amp;set_filter=Y"><?echo $arComm["YESTERDAY_HITS"]?></a></td>
	<td class="bx-digit-cell"><a href="hit_list.php?lang=<?=LANG?>&amp;find_date1=<?echo $bef_yesterday_date?>&amp;find_date2=<?echo $bef_yesterday_date?>&amp;set_filter=Y"><?echo $arComm["B_YESTERDAY_HITS"]?></a></td>
	<?if ($is_filtered) : ?>
		<td class="bx-digit-cell"><a href="hit_list.php?lang=<?=LANG?>&amp;find_date1=<?echo $find_date1?>&amp;find_date2=<?echo $find_date2?>&amp;set_filter=Y"><?echo $arComm["PERIOD_HITS"]?></td>
	<?endif;?>
	<td class="bx-digit-cell"><a href="hit_list.php?lang=<?=LANG?>&amp;del_filter=Y"><?echo $arComm["TOTAL_HITS"]?></a></td>
</tr>
<tr>
	<td><?echo GetMessage("STAT_HOSTS")?></td>
	<td class="bx-digit-cell"><?echo $arComm["TODAY_HOSTS"]?></td>
	<td class="bx-digit-cell"><?echo $arComm["YESTERDAY_HOSTS"]?></td>
	<td class="bx-digit-cell"><?echo $arComm["B_YESTERDAY_HOSTS"]?></td>
	<?if ($is_filtered) : ?>
		<td class="bx-digit-cell">&nbsp;</td>
	<?endif;?>
	<td class="bx-digit-cell"><?echo $arComm["TOTAL_HOSTS"]?></td>
</tr>
<tr>
	<td><?echo GetMessage("STAT_SESSIONS")?></td>
	<td class="bx-digit-cell"><a href="session_list.php?lang=<?=LANG?>&amp;find_date1=<?echo $now_date?>&amp;find_date2=<?echo $now_date?>&amp;set_filter=Y"><?echo $arComm["TODAY_SESSIONS"]?></a></td>
	<td class="bx-digit-cell"><a href="session_list.php?lang=<?=LANG?>&amp;find_date1=<?echo $yesterday_date?>&amp;find_date2=<?echo $yesterday_date?>&amp;set_filter=Y"><?echo $arComm["YESTERDAY_SESSIONS"]?></a></td>
	<td class="bx-digit-cell"><a href="session_list.php?lang=<?=LANG?>&amp;find_date1=<?echo $bef_yesterday_date?>&amp;find_date2=<?echo $bef_yesterday_date?>&amp;set_filter=Y"><?echo $arComm["B_YESTERDAY_SESSIONS"]?></a></td>
	<?if ($is_filtered) : ?>
		<td class="bx-digit-cell"><a href="session_list.php?lang=<?=LANG?>&amp;find_date1=<?echo $find_date1?>&amp;find_date2=<?echo $find_date2?>&amp;set_filter=Y"><?echo $arComm["PERIOD_SESSIONS"]?></a></td>
	<?endif;?>
	<td class="bx-digit-cell"><a href="session_list.php?lang=<?=LANG?>&amp;del_filter=Y"><?echo $arComm["TOTAL_SESSIONS"]?></a></td>
</tr>
<tr>
	<td><?echo GetMessage("STAT_C_EVENTS")?></td>
	<td class="bx-digit-cell"><a href="event_list.php?lang=<?=LANG?>&amp;find_date1=<?echo $now_date?>&amp;find_date2=<?echo $now_date?>&amp;set_filter=Y"><?echo $arComm["TODAY_EVENTS"]?></a></td>
	<td class="bx-digit-cell"><a href="event_list.php?lang=<?=LANG?>&amp;find_date1=<?echo $yesterday_date?>&amp;find_date2=<?echo $yesterday_date?>&amp;set_filter=Y"><?echo $arComm["YESTERDAY_EVENTS"]?></a></td>
	<td class="bx-digit-cell"><a href="event_list.php?lang=<?=LANG?>&amp;find_date1=<?echo $bef_yesterday_date?>&amp;find_date2=<?echo $bef_yesterday_date?>&amp;set_filter=Y"><?echo $arComm["B_YESTERDAY_EVENTS"]?></a></td>
	<?if ($is_filtered) : ?>
		<td class="bx-digit-cell"><a href="event_list.php?lang=<?=LANG?>&amp;find_date1=<?echo $find_date1?>&amp;find_date2=<?echo $find_date2?>&amp;set_filter=Y"><?echo $arComm["PERIOD_EVENTS"]?></a></td>
	<?endif;?>
	<td class="bx-digit-cell"><a href="event_list.php?lang=<?=LANG?>&amp;del_filter=Y"><?echo $arComm["TOTAL_EVENTS"]?></a></td>
</tr>
<?if ($site_filter!="Y"):?>
<tr class="heading">
	<td colspan="<?=$is_filtered?"6":"5"?>"><?echo GetMessage("STAT_GUESTS")?></td>
</tr>
<tr>
	<td><?echo GetMessage("STAT_TOTAL")?></td>
	<td class="bx-digit-cell"><a href="guest_list.php?lang=<?=LANG?>&amp;find_last_date1=<?echo $now_date?>&amp;find_last_date2=<?echo $now_date?>&amp;set_filter=Y"><?echo $arComm["TODAY_GUESTS"]?></a></td>
	<td class="bx-digit-cell"><a href="guest_list.php?lang=<?=LANG?>&amp;find_period_date1=<?echo $yesterday_date?>&amp;find_period_date2=<?echo $yesterday_date?>&amp;set_filter=Y"><?echo $arComm["YESTERDAY_GUESTS"]?></a></td>
	<td class="bx-digit-cell"><a href="guest_list.php?lang=<?=LANG?>&amp;find_period_date1=<?echo $bef_yesterday_date?>&amp;find_period_date2=<?echo $bef_yesterday_date?>&amp;set_filter=Y"><?echo $arComm["B_YESTERDAY_GUESTS"]?></a></td>
	<?if($is_filtered):?>
		<td class="bx-digit-cell">&nbsp;</td>
	<?endif;?>
	<td class="bx-digit-cell"><a href="guest_list.php?lang=<?=LANG?>&amp;del_filter=Y"><?echo $arComm["TOTAL_GUESTS"]?></a></td>
</tr>
<tr>
	<td><?echo GetMessage("STAT_NEW")?></td>
	<td class="bx-digit-cell"><a href="guest_list.php?lang=<?=LANG?>&amp;find_period_date1=<?echo $now_date?>&amp;find_period_date2=<?echo $now_date?>&amp;find_sess2=1&amp;set_filter=Y"><?echo $arComm["TODAY_NEW_GUESTS"]?></a></td>
	<td class="bx-digit-cell"><a href="guest_list.php?lang=<?=LANG?>&amp;find_period_date1=<?echo $yesterday_date?>&amp;find_period_date2=<?echo $yesterday_date?>&amp;find_sess2=1&amp;set_filter=Y"><?echo $arComm["YESTERDAY_NEW_GUESTS"]?></a></td>
	<td class="bx-digit-cell"><a href="guest_list.php?lang=<?=LANG?>&amp;find_period_date1=<?echo $bef_yesterday_date?>&amp;find_period_date2=<?echo $bef_yesterday_date?>&amp;find_sess2=1&amp;set_filter=Y"><?echo $arComm["B_YESTERDAY_NEW_GUESTS"]?></a></td>
	<?if($is_filtered):?>
		<td class="bx-digit-cell"><a href="guest_list.php?lang=<?=LANG?>&amp;find_period_date1=<?echo $find_date1?>&amp;find_period_date2=<?echo $find_date2?>&amp;find_sess2=1&amp;set_filter=Y"><?echo $arComm["PERIOD_NEW_GUESTS"]?></a></td>
	<?endif;?>
	<td class="bx-digit-cell">&nbsp;</td>
</tr>
<tr>
	<td><?echo GetMessage("STAT_ONLINE")?></td>
	<td class="bx-digit-cell"><a href="users_online.php?lang=<?=LANG?>"><?echo $arComm["ONLINE_GUESTS"]?></a></td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<?if($is_filtered):?>
		<td>&nbsp;</td>
	<?endif;?>
	<td>&nbsp;</td>
</tr>
<?endif;?>
</table>
<?endif;
$lAdmin_tab1->EndCustomContent();
if($_REQUEST["table_id"]=="" || $_REQUEST["table_id"]==$sTableID_tab1)
	$lAdmin_tab1->CheckListMode();

$sTableID_tab2 = "t_stat_list_tab2";
$oSort_tab2 = new CAdminSorting($sTableID_tab2);
$lAdmin_tab2 = new CAdminList($sTableID_tab2, $oSort_tab2);
$lAdmin_tab2->BeginCustomContent();
if(strlen($strError)>0):
	CAdminMessage::ShowMessage($strError);
elseif($site_filter=="Y" && $_REQUEST["table_id"]==$sTableID_tab2):
	CAdminMessage::ShowMessage(GetMessage("STAT_NO_DATA"));
elseif($_REQUEST["table_id"]==$sTableID_tab2):
	$arADVF["DATE1_PERIOD"] = $arFilter["DATE1"];
	$arADVF["DATE2_PERIOD"] = $arFilter["DATE2"];
	$adv = CAdv::GetList($a_by, $a_order, $arADVF, $is_filtered, "", $arrGROUP_DAYS, $v);
?>
<table border="0" cellspacing="0" cellpadding="0" width="100%" class="list-table">
<tr class="heading" valign="top">
	<td><?echo GetMessage("STAT_ADV_NAME")?></td>
	<td><a href="session_list.php?lang=<?=LANG?>&amp;find_date1=<?=$now_date?>&amp;find_date2=<?=$now_date?>&amp;find_adv_back=N&amp;set_filter=Y"><?=GetMessage("STAT_TODAY")?></a><br><?=$now_date?></td>
	<td><a href="session_list.php?lang=<?=LANG?>&amp;find_date1=<?=$yesterday_date?>&amp;find_date2=<?=$yesterday_date?>&amp;find_adv_back=N&amp;set_filter=Y"><?=GetMessage("STAT_YESTERDAY")?></a><br><?=$yesterday_date?></td>
	<td><a href="session_list.php?lang=<?=LANG?>&amp;find_date1=<?=$bef_yesterday_date?>&amp;find_adv_back=N&amp;find_date2=<?=$bef_yesterday_date?>&amp;set_filter=Y"><?=GetMessage("STAT_BEFORE_YESTERDAY")?></a><br><?=$bef_yesterday_date?></td>
	<?if ($is_filtered) : ?>
		<td><a href="session_list.php?lang=<?=LANG?>&amp;find_date1=<?=htmlspecialcharsbx($find_date1)?>&amp;find_date2=<?=htmlspecialcharsbx($find_date2)?>&amp;find_adv_back=N&amp;set_filter=Y"><?=GetMessage("STAT_PERIOD")?></a><br><?=$arFilter["DATE1"]?>&nbsp;- <?=$arFilter["DATE2"]?></td>
	<?endif;?>
	<td><a href="session_list.php?lang=<?=LANG?>&amp;find_adv_back=N&amp;set_filter=Y"><?=GetMessage("STAT_TOTAL_1")?></a></td>
</tr>
<?
ClearVars("f_");
$i = 0;
$total_SESSIONS_TODAY = 0;
$total_SESSIONS_YESTERDAY = 0;
$total_SESSIONS_BEF_YESTERDAY = 0;
$total_SESSIONS_PERIOD = 0;
$total_SESSIONS = 0;
while ($adv->ExtractFields("f_")) :
	$i++;
	$total_SESSIONS_TODAY += $f_SESSIONS_TODAY;
	$total_SESSIONS_YESTERDAY += $f_SESSIONS_YESTERDAY;
	$total_SESSIONS_BEF_YESTERDAY += $f_SESSIONS_BEF_YESTERDAY;
	$total_SESSIONS_PERIOD += $f_SESSIONS_PERIOD;
	$total_SESSIONS += $f_SESSIONS;
	if($i<=COption::GetOptionInt("statistic","STAT_LIST_TOP_SIZE")):
?>
<tr valign="top">
	<td>[<a href="adv_list.php?lang=<?=LANG?>&amp;find_id=<?=$f_ID?>&amp;find_id_exact_match=Y&amp;set_filter=Y"><?echo $f_ID?></a>]&nbsp;<?echo $f_REFERER1?>&nbsp;/&nbsp;<?echo $f_REFERER2?></td>
	<td class="bx-digit-cell">
		<?if (intval($f_SESSIONS_TODAY)>0):?>
			<a href="session_list.php?lang=<?=LANG?>&amp;find_date1=<?echo $now_date?>&amp;find_date2=<?echo $now_date?>&amp;find_adv_id=<?echo $f_ID?>&amp;find_adv_id_exact_match=Y&amp;find_adv_back=N&amp;set_filter=Y"><?echo $f_SESSIONS_TODAY?></a>
		<?else:?>
			&nbsp;
		<?endif;?>
	</td>
	<td class="bx-digit-cell">
		<?if (intval($f_SESSIONS_YESTERDAY)>0):?>
			<a href="session_list.php?lang=<?=LANG?>&amp;find_date1=<?echo $yesterday_date?>&amp;find_date2=<?echo $yesterday_date?>&amp;find_adv_id=<?echo $f_ID?>&amp;find_adv_id_exact_match=Y&amp;find_adv_back=N&amp;set_filter=Y"><?echo $f_SESSIONS_YESTERDAY?></a>
		<?else:?>
			&nbsp;
		<?endif;?>
	</td>
	<td class="bx-digit-cell">
		<?if (intval($f_SESSIONS_BEF_YESTERDAY)>0):?>
			<a href="session_list.php?lang=<?=LANG?>&amp;find_date1=<?echo $bef_yesterday_date?>&amp;find_date2=<?echo $bef_yesterday_date?>&amp;find_adv_id=<?echo $f_ID?>&amp;find_adv_id_exact_match=Y&amp;find_adv_back=N&amp;set_filter=Y"><?echo $f_SESSIONS_BEF_YESTERDAY?></a>
		<?else:?>
			&nbsp;
		<?endif;?>
	</td>
<?if ($is_filtered) : ?>
	<td class="bx-digit-cell">
		<?if (intval($f_SESSIONS_PERIOD)>0) :?>
			<a href="session_list.php?lang=<?=LANG?>&amp;find_date1=<?echo htmlspecialcharsbx($find_date1)?>&amp;find_date2=<?echo htmlspecialcharsbx($find_date2)?>&amp;find_adv_id=<?echo $f_ID?>&amp;find_adv_id_exact_match=Y&amp;find_adv_back=N&amp;set_filter=Y"><?echo $f_SESSIONS_PERIOD?></a>
		<?else:?>
			&nbsp;
		<?endif;?>
	</td>
<?endif;?>
	<td class="bx-digit-cell">
		<?if (intval($f_SESSIONS)>0):?>
			<a href="session_list.php?lang=<?=LANG?>&amp;find_adv_id=<?echo $f_ID?>&amp;find_adv_id_exact_match=Y&amp;find_adv_back=N&amp;set_filter=Y"><?echo $f_SESSIONS?></a>
		<?else:?>
			&nbsp;
		<?endif;?>
	</td>
</tr>
<?	endif;//if($i<=COption::GetOptionInt("statistic","STAT_LIST_TOP_SIZE")):
endwhile;?>
<tr>
	<td class="bx-digit-cell"><?echo GetMessage("STAT_TOTAL")?></td>
	<td class="bx-digit-cell">
		<?if(intval($total_SESSIONS_TODAY)>0):
				echo $total_SESSIONS_TODAY;
		else:?>
			&nbsp;
		<?endif;?>
	</td>
	<td class="bx-digit-cell">
		<?if(intval($total_SESSIONS_YESTERDAY)>0):
				echo $total_SESSIONS_YESTERDAY;
		else:?>
			&nbsp;
		<?endif;?>
	</td>
	<td class="bx-digit-cell">
		<?if(intval($total_SESSIONS_BEF_YESTERDAY)>0):
				echo $total_SESSIONS_BEF_YESTERDAY;
		else:?>
			&nbsp;
		<?endif;?>
	</td>
<?if($is_filtered):?>
	<td class="bx-digit-cell">
		<?if(intval($total_SESSIONS_PERIOD)>0):
				echo $total_SESSIONS_PERIOD;
		else:?>
			&nbsp;
		<?endif;?>
	</td>
<?endif;?>
	<td class="bx-digit-cell">
		<?if(intval($total_SESSIONS)>0):
				echo $total_SESSIONS;
		else:?>
			&nbsp;
		<?endif;?>
	</td>
</tr>
</table>
<?
endif;
$lAdmin_tab2->EndCustomContent();
if($_REQUEST["table_id"]==$sTableID_tab2)
	$lAdmin_tab2->CheckListMode();

$sTableID_tab3 = "t_stat_list_tab3";
$oSort_tab3 = new CAdminSorting($sTableID_tab3);
$lAdmin_tab3 = new CAdminList($sTableID_tab3, $oSort_tab3);
$lAdmin_tab3->BeginCustomContent();
if(strlen($strError)>0):
	CAdminMessage::ShowMessage($strError);
elseif($site_filter=="Y" && $_REQUEST["table_id"]==$sTableID_tab3):
	CAdminMessage::ShowMessage(GetMessage("STAT_NO_DATA"));
elseif($_REQUEST["table_id"]==$sTableID_tab3):
	$arEVENTF["DATE1_PERIOD"] = $arFilter["DATE1"];
	$arEVENTF["DATE2_PERIOD"] = $arFilter["DATE2"];
	if (strlen($e_by)<=0) $e_by = "s_stat";
	if (strlen($e_order)<=0) $e_order = "desc";
	$events = CStatEventType::GetList($e_by, $e_order, $arEVENTF, $is_filtered);
	if ($e_by=="s_stat") $e_by = "s_today_counter";
?>
<table border="0" cellspacing="0" cellpadding="0" width="100%" class="list-table">
<tr class="heading" valign="top">
	<td><?=GetMessage("STAT_EVENT")?></td>
	<td><a href="event_list.php?lang=<?=LANG?>&amp;find_date1=<?=$now_date?>&amp;find_date2=<?=$now_date?>&amp;set_filter=Y"><?=GetMessage("STAT_TODAY")?></a><br><?=$now_date?></td>
	<td><a href="event_list.php?lang=<?=LANG?>&amp;find_date1=<?=$yesterday_date?>&amp;find_date2=<?=$yesterday_date?>&amp;set_filter=Y"><?=GetMessage("STAT_YESTERDAY")?></a><br><?=$yesterday_date?></td>
	<td><a href="event_list.php?lang=<?=LANG?>&amp;find_date1=<?=$bef_yesterday_date?>&amp;find_date2=<?=$bef_yesterday_date?>&amp;set_filter=Y"><?=GetMessage("STAT_BEFORE_YESTERDAY")?></a><br><?=$bef_yesterday_date?></td>
<?if ($is_filtered) : ?>
	<td><a href="event_list.php?lang=<?=LANG?>&amp;find_date1=<?=htmlspecialcharsbx($find_date1)?>&amp;find_date2=<?=htmlspecialcharsbx($find_date2)?>&amp;set_filter=Y"><?=GetMessage("STAT_PERIOD")?></a><br><?=$arFilter["DATE1"]?>&nbsp;- <?=$arFilter["DATE2"]?></td>
<?endif;?>
	<td><a href="event_list.php?lang=<?=LANG?>&amp;del_filter=Y"><?=GetMessage("STAT_TOTAL_1")?></a></td>
</tr>
<?
ClearVars("e_");
$i = 0;
$total_TODAY_COUNTER = 0;
$total_YESTERDAY_COUNTER = 0;
$total_B_YESTERDAY_COUNTER = 0;
$total_TOTAL_COUNTER = 0;
$total_PERIOD_COUNTER = 0;
while ($events->ExtractFields("e_")) :
	$i++;
	$total_TODAY_COUNTER += intval($e_TODAY_COUNTER);
	$total_YESTERDAY_COUNTER += intval($e_YESTERDAY_COUNTER);
	$total_B_YESTERDAY_COUNTER += intval($e_B_YESTERDAY_COUNTER);
	$total_TOTAL_COUNTER += intval($e_TOTAL_COUNTER);
	$total_PERIOD_COUNTER += intval($e_PERIOD_COUNTER);
	if($i<=COption::GetOptionInt("statistic","STAT_LIST_TOP_SIZE")):
?>
<tr>
	<td>
		<?$dynamic_days = CStatEventType::DynamicDays($e_ID);
		if ($dynamic_days>=2 && function_exists("ImageCreate")):?>
			<a href="event_graph_list.php?lang=<?=LANG?>&amp;find_events[]=<?echo $e_ID?>&amp;set_filter=Y" title="<?=GetMessage("STAT_EVENT_GRAPH")?>"><?echo $e_EVENT?></a>
		<?else:
			echo $e_EVENT;
		endif;?>
	</td>
	<td class="bx-digit-cell">
		<?if (intval($e_TODAY_COUNTER)>0):?>
			<a href="event_list.php?lang=<?=LANG?>&amp;find_date1=<?echo $now_date?>&amp;find_date2=<?echo $now_date?>&amp;find_event_id=<?echo $e_ID?>&amp;find_event_id_exact_match=Y&amp;set_filter=Y"><?echo $e_TODAY_COUNTER?></a>
		<?else:?>
			&nbsp;
		<?endif;?>
	</td>
	<td class="bx-digit-cell">
		<?if (intval($e_YESTERDAY_COUNTER)>0):?>
			<a href="event_list.php?lang=<?=LANG?>&amp;find_date1=<?echo $yesterday_date?>&amp;find_date2=<?echo $yesterday_date?>&amp;find_event_id=<?echo $e_ID?>&amp;find_event_id_exact_match=Y&amp;set_filter=Y"><?echo $e_YESTERDAY_COUNTER?></a>
		<?else:?>
			&nbsp;
		<?endif;?>
	</td>
	<td class="bx-digit-cell">
		<?if (intval($e_B_YESTERDAY_COUNTER)>0):?>
			<a href="event_list.php?lang=<?=LANG?>&amp;find_date1=<?echo $bef_yesterday_date?>&amp;find_date2=<?echo $bef_yesterday_date?>&amp;find_event_id=<?echo $e_ID?>&amp;find_event_id_exact_match=Y&amp;set_filter=Y"><?echo $e_B_YESTERDAY_COUNTER?></a>
		<?else:?>
			&nbsp;
		<?endif;?>
	</td>
<?if($is_filtered):?>
	<td class="bx-digit-cell">
		<?if (intval($e_PERIOD_COUNTER)>0):?>
			<a href="event_list.php?lang=<?=LANG?>&amp;find_date1=<?echo htmlspecialcharsbx($find_date1)?>&amp;find_date2=<?echo htmlspecialcharsbx($find_date2)?>&amp;find_event_id=<?echo $e_ID?>&amp;find_event_id_exact_match=Y&amp;set_filter=Y"><?echo $e_PERIOD_COUNTER?></a>
		<?else:?>
			&nbsp;
		<?endif;?>
	</td>
<?endif;?>
	<td class="bx-digit-cell">
		<?if (intval($e_TOTAL_COUNTER)>0):?>
			<a href="event_list.php?lang=<?=LANG?>&amp;find_event_id=<?echo $e_ID?>&amp;find_event_id_exact_match=Y&amp;set_filter=Y"><?echo $e_TOTAL_COUNTER?></a>
		<?else:?>
			&nbsp;
		<?endif;?>
	</td>
</tr>
<?	endif;//if($i<=COption::GetOptionInt("statistic","STAT_LIST_TOP_SIZE")):
endwhile;?>
<tr>
	<td class="bx-digit-cell"><?echo GetMessage("STAT_TOTAL")?></td>
	<td class="bx-digit-cell"><?=(intval($total_TODAY_COUNTER)>0?$total_TODAY_COUNTER:'&nbsp;')?></td>
	<td class="bx-digit-cell"><?=(intval($total_YESTERDAY_COUNTER)>0?$total_YESTERDAY_COUNTER:'&nbsp;')?></td>
	<td class="bx-digit-cell"><?=(intval($total_B_YESTERDAY_COUNTER)>0?$total_B_YESTERDAY_COUNTER:'&nbsp;')?></td>
<?if($is_filtered):?>
	<td class="bx-digit-cell"><?=(intval($total_PERIOD_COUNTER)>0?$total_PERIOD_COUNTER:'&nbsp;')?></td>
<?endif;?>
	<td class="bx-digit-cell"><?=(intval($total_TOTAL_COUNTER)>0?$total_TOTAL_COUNTER:'&nbsp;')?></td>
</tr>

</table>
<?
endif;
$lAdmin_tab3->EndCustomContent();
if($_REQUEST["table_id"]==$sTableID_tab3)
	$lAdmin_tab3->CheckListMode();

$sTableID_tab4 = "t_stat_list_tab4";
$oSort_tab4 = new CAdminSorting($sTableID_tab4);
$lAdmin_tab4 = new CAdminList($sTableID_tab4, $oSort_tab4);
$lAdmin_tab4->BeginCustomContent();
if(strlen($strError)>0):
	CAdminMessage::ShowMessage($strError);
elseif($_REQUEST["table_id"]==$sTableID_tab4):
	$referers = CTraffic::GetRefererList($by, $order, $arFilter, $is_filtered, false);
?>
<table border="0" cellspacing="0" cellpadding="0" width="100%" class="list-table">

<tr class="heading" valign="top">
	<td><?=GetMessage("STAT_SERVER")?></td>
	<td><a href="referer_list.php?lang=<?=LANG?>&amp;find_date1=<?=$now_date?>&amp;find_date2=<?=$now_date?>&amp;group_by=none&amp;set_filter=Y"><?=GetMessage("STAT_TODAY")?></a><br><?=$now_date?></td>
	<td><a href="referer_list.php?lang=<?=LANG?>&amp;find_date1=<?=$yesterday_date?>&amp;find_date2=<?=$yesterday_date?>&amp;group_by=none&amp;set_filter=Y"><?=GetMessage("STAT_YESTERDAY")?></a><br><?=$yesterday_date?></td>
	<td><a href="referer_list.php?lang=<?=LANG?>&amp;find_date1=<?=$bef_yesterday_date?>&amp;find_date2=<?=$bef_yesterday_date?>&amp;group_by=none&amp;set_filter=Y"><?=GetMessage("STAT_BEFORE_YESTERDAY")?></a><br><?=$bef_yesterday_date?></td>
<?if($is_filtered):?>
	<td><a href="referer_list.php?lang=<?=LANG?>&amp;find_date1=<?=htmlspecialcharsbx($find_date1)?>&amp;find_date2=<?=htmlspecialcharsbx($find_date2)?>&amp;group_by=none&amp;set_filter=Y"><?=GetMessage("STAT_PERIOD")?></a><br> <?=$arFilter["DATE1"]?>&nbsp;- <?=$arFilter["DATE2"]?></td>
<?endif;?>
	<td><a href="referer_list.php?lang=<?=LANG?>&amp;group_by=none&amp;del_filter=Y"><?=GetMessage("STAT_TOTAL_1")?></a></td>
</tr>
<?
ClearVars("t_");
$i = 0;
$total_TODAY_REFERERS = 0;
$total_YESTERDAY_REFERERS = 0;
$total_B_YESTERDAY_REFERERS = 0;
$total_TOTAL_REFERERS = 0;
$total_PERIOD_REFERERS = 0;
while ($referers->ExtractFields("t_")) :
	$i++;
	$total_TODAY_REFERERS += $t_TODAY_REFERERS;
	$total_YESTERDAY_REFERERS += $t_YESTERDAY_REFERERS;
	$total_B_YESTERDAY_REFERERS += $t_B_YESTERDAY_REFERERS;
	$total_TOTAL_REFERERS += $t_TOTAL_REFERERS;
	$total_PERIOD_REFERERS += $t_PERIOD_REFERERS;
	if($i<=COption::GetOptionInt("statistic","STAT_LIST_TOP_SIZE")):
?>
<tr>
	<td><a href="referer_list.php?lang=<?=LANG?>&amp;find_from_domain=<?echo urlencode("\"".$t_SITE_NAME."\"")?>&amp;set_filter=Y"><?echo $t_SITE_NAME?></a></td>
	<td class="bx-digit-cell">
		<?if(intval($t_TODAY_REFERERS)>0):?>
			<a href="referer_list.php?lang=<?=LANG?>&amp;find_date1=<?echo $now_date?>&amp;find_date2=<?echo $now_date?>&amp;find_from=<?echo urlencode("\"".$t_SITE_NAME."\"")?>&amp;set_filter=Y"><?echo $t_TODAY_REFERERS?></a>
		<?else:?>
			&nbsp;
		<?endif;?>
	</td>
	<td class="bx-digit-cell">
		<?if(intval($t_YESTERDAY_REFERERS)>0):?>
			<a href="referer_list.php?lang=<?=LANG?>&amp;find_date1=<?echo $yesterday_date?>&amp;find_date2=<?echo $yesterday_date?>&amp;find_from=<?echo urlencode("\"".$t_SITE_NAME."\"")?>&amp;set_filter=Y"><?echo $t_YESTERDAY_REFERERS?></a>
		<?else:?>
			&nbsp;
		<?endif;?>
	</td>
	<td class="bx-digit-cell">
		<?if(intval($t_B_YESTERDAY_REFERERS)>0):?>
			<a href="referer_list.php?lang=<?=LANG?>&amp;find_date1=<?echo $bef_yesterday_date?>&amp;find_date2=<?echo $bef_yesterday_date?>&amp;find_from=<?echo urlencode("\"".$t_SITE_NAME."\"")?>&amp;set_filter=Y"><?echo $t_B_YESTERDAY_REFERERS?></a>
		<?else:?>
			&nbsp;
		<?endif;?>
	</td>
<?if($is_filtered):?>
	<td class="bx-digit-cell">
		<?if(intval($t_PERIOD_REFERERS)>0):?>
			<a href="referer_list.php?lang=<?=LANG?>&amp;find_date1=<?echo htmlspecialcharsbx($find_date1)?>&amp;find_date2=<?echo htmlspecialcharsbx($find_date2)?>&amp;find_from=<?echo urlencode("\"".$SITE_NAME."\"")?>&amp;set_filter=Y"><?echo $t_PERIOD_REFERERS?></a>
		<?else:?>
			&nbsp;
		<?endif;?>
	</td>
<?endif;?>
	<td class="bx-digit-cell">
		<?if(intval($t_TOTAL_REFERERS)>0):?>
			<a href="referer_list.php?lang=<?=LANG?>&amp;find_from=<?echo urlencode("\"".$SITE_NAME."\"")?>&amp;set_filter=Y"><?echo $t_TOTAL_REFERERS?></a>
		<?else:?>
			&nbsp;
		<?endif;?>
	</td>
</tr>
<?
	endif;
endwhile;
?>
<tr>
	<td class="bx-digit-cell"><?echo GetMessage("STAT_TOTAL")?></td>
	<td class="bx-digit-cell"><?=(intval($total_TODAY_REFERERS)>0?$total_TODAY_REFERERS:'&nbsp;')?></td>
	<td class="bx-digit-cell"><?=(intval($total_YESTERDAY_REFERERS)>0?$total_YESTERDAY_REFERERS:'&nbsp;')?></td>
	<td class="bx-digit-cell"><?=(intval($total_B_YESTERDAY_REFERERS)>0?$total_B_YESTERDAY_REFERERS:'&nbsp;')?></td>
<?if($is_filtered):?>
	<td class="bx-digit-cell"><?=(intval($total_PERIOD_REFERERS)>0?$total_PERIOD_REFERERS:'&nbsp;')?></td>
<?endif;?>
	<td class="bx-digit-cell"><?=(intval($total_TOTAL_REFERERS)>0?$total_TOTAL_REFERERS:'&nbsp;')?></td>
</tr>
</table>
<?
endif;
$lAdmin_tab4->EndCustomContent();
if($_REQUEST["table_id"]==$sTableID_tab4)
	$lAdmin_tab4->CheckListMode();

$sTableID_tab5 = "t_stat_list_tab5";
$oSort_tab5 = new CAdminSorting($sTableID_tab5);
$lAdmin_tab5 = new CAdminList($sTableID_tab5, $oSort_tab5);
$lAdmin_tab5->BeginCustomContent();
if(strlen($strError)>0):
	CAdminMessage::ShowMessage($strError);
elseif($_REQUEST["table_id"]==$sTableID_tab5):
	$phrases = CTraffic::GetPhraseList($s_by, $s_order, $arFilter, $is_filtered, false);
?>
<table border="0" cellspacing="0" cellpadding="0" width="100%" class="list-table">
<tr class="heading" valign="top">
	<td><?=GetMessage("STAT_PHRASE")?></td>
	<td><a href="phrase_list.php?lang=<?=LANG?>&amp;find_date1=<?=$now_date?>&amp;find_date2=<?=$now_date?>&amp;set_filter=Y&amp;group_by=none&amp;menu_item_id=1"><?=GetMessage("STAT_TODAY")?></a><br><?=$now_date?></td>
	<td><a href="phrase_list.php?lang=<?=LANG?>&amp;find_date1=<?=$yesterday_date?>&amp;find_date2=<?=$yesterday_date?>&amp;set_filter=Y&amp;group_by=none&amp;menu_item_id=1"><?=GetMessage("STAT_YESTERDAY")?></a><br><?=$yesterday_date?></td>
	<td><a href="phrase_list.php?lang=<?=LANG?>&amp;find_date1=<?=$bef_yesterday_date?>&amp;find_date2=<?=$bef_yesterday_date?>&amp;set_filter=Y&amp;group_by=none&amp;menu_item_id=1"><?=GetMessage("STAT_BEFORE_YESTERDAY")?></a><br><?=$bef_yesterday_date?></td>
<?if($is_filtered):?>
	<td><a href="phrase_list.php?lang=<?=LANG?>&amp;find_date1=<?=htmlspecialcharsbx($find_date1)?>&amp;find_date2=<?=htmlspecialcharsbx($find_date2)?>&amp;set_filter=Y&amp;group_by=none&amp;menu_item_id=1"><?=GetMessage("STAT_PERIOD")?></a><br> <?=$arFilter["DATE1"]?>&nbsp;- <?=$arFilter["DATE2"]?></td>
<?endif;?>
	<td><a href="phrase_list.php?lang=<?=LANG?>&amp;del_filter=Y&amp;group_by=none&amp;menu_item_id=1"><?=GetMessage("STAT_TOTAL_1")?></a></td>
</tr>
<?
ClearVars("ph_");
$i = 0;
$total_TODAY_PHRASES = 0;
$total_YESTERDAY_PHRASES = 0;
$total_B_YESTERDAY_PHRASES = 0;
$total_TOTAL_PHRASES = 0;
$total_PERIOD_PHRASES = 0;
while ($phrases->ExtractFields("ph_")) :
	$i++;
	$total_TODAY_PHRASES += $ph_TODAY_PHRASES;
	$total_YESTERDAY_PHRASES += $ph_YESTERDAY_PHRASES;
	$total_B_YESTERDAY_PHRASES += $ph_B_YESTERDAY_PHRASES;
	$total_TOTAL_PHRASES += $ph_TOTAL_PHRASES;
	$total_PERIOD_PHRASES += $ph_PERIOD_PHRASES;
	if($i<=COption::GetOptionInt("statistic","STAT_LIST_TOP_SIZE")):
?>
<tr>
	<td><a href="phrase_list.php?lang=<?=LANG?>&amp;find_phrase=<?=urlencode("\"".htmlspecialcharsback($ph_PHRASE)."\"")?>&amp;set_filter=Y&amp;group_by=none&amp;menu_item_id=1&amp;find_phrase_exact_match=Y"><?echo TruncateText($ph_PHRASE,50)?></a>&nbsp;</td>
	<td class="bx-digit-cell">
		<?if(intval($ph_TODAY_PHRASES)>0):?>
			<a href="phrase_list.php?lang=<?=LANG?>&amp;find_date1=<?echo $now_date?>&amp;find_date2=<?echo $now_date?>&amp;find_phrase=<?=urlencode("\"".htmlspecialcharsback($ph_PHRASE)."\"")?>&amp;set_filter=Y&amp;group_by=none&amp;menu_item_id=1&amp;find_phrase_exact_match=Y"><?echo $ph_TODAY_PHRASES?></a>
		<?else:?>
			&nbsp;
		<?endif;?>
	</td>
	<td class="bx-digit-cell">
		<?if(intval($ph_YESTERDAY_PHRASES)>0):?>
			<a href="phrase_list.php?lang=<?=LANG?>&amp;find_date1=<?echo $yesterday_date?>&amp;find_date2=<?echo $yesterday_date?>&amp;find_phrase=<?=urlencode("\"".htmlspecialcharsback($ph_PHRASE)."\"")?>&amp;set_filter=Y&amp;group_by=none&amp;menu_item_id=1&amp;find_phrase_exact_match=Y"><?echo $ph_YESTERDAY_PHRASES?></a>
		<?else:?>
			&nbsp;
		<?endif;?>
	</td>
	<td class="bx-digit-cell">
		<?if(intval($ph_B_YESTERDAY_PHRASES)>0):?>
			<a href="phrase_list.php?lang=<?=LANG?>&amp;find_date1=<?echo $bef_yesterday_date?>&amp;find_date2=<?echo $bef_yesterday_date?>&amp;find_phrase=<?=urlencode("\"".htmlspecialcharsback($ph_PHRASE)."\"")?>&amp;set_filter=Y&amp;group_by=none&amp;menu_item_id=1&amp;find_phrase_exact_match=Y"><?echo $ph_B_YESTERDAY_PHRASES?></a>
		<?else:?>
			&nbsp;
		<?endif;?>
	</td>
<?if($is_filtered):?>
	<td class="bx-digit-cell">
		<?if(intval($ph_PERIOD_PHRASES)>0):?>
			<a href="phrase_list.php?lang=<?=LANG?>&amp;find_date1=<?echo htmlspecialcharsbx($find_date1)?>&amp;find_date2=<?echo htmlspecialcharsbx($find_date2)?>&amp;find_phrase=<?=urlencode("\"".htmlspecialcharsback($ph_PHRASE)."\"")?>&amp;set_filter=Y&amp;group_by=none&amp;menu_item_id=1&amp;find_phrase_exact_match=Y"><?echo $ph_PERIOD_PHRASES?></a>
		<?else:?>
			&nbsp;
		<?endif;?>
	</td>
<?endif;?>
	<td class="bx-digit-cell">
		<?if(intval($ph_TOTAL_PHRASES)>0):?>
			<a href="phrase_list.php?lang=<?=LANG?>&amp;find_phrase=<?=urlencode("\"".htmlspecialcharsback($ph_PHRASE)."\"")?>&amp;set_filter=Y&amp;group_by=none&amp;menu_item_id=1&amp;find_phrase_exact_match=Y"><?echo $ph_TOTAL_PHRASES?></a>
		<?else:?>
			&nbsp;
		<?endif;?>
	</td>
</tr>
<?
	endif;
endwhile;
?>
<tr>
		<td class="bx-digit-cell"><?echo GetMessage("STAT_TOTAL")?></td>
	<td class="bx-digit-cell"><?=(intval($total_TODAY_PHRASES)>0?$total_TODAY_PHRASES:'&nbsp;')?></td>
	<td class="bx-digit-cell"><?=(intval($total_YESTERDAY_PHRASES)>0?$total_YESTERDAY_PHRASES:'&nbsp;')?></td>
	<td class="bx-digit-cell"><?=(intval($total_B_YESTERDAY_PHRASES)>0?$total_B_YESTERDAY_PHRASES:'&nbsp;')?></td>
<?if ($is_filtered) : ?>
	<td class="bx-digit-cell"><?=(intval($total_PERIOD_PHRASES)>0?$total_PERIOD_PHRASES:'&nbsp;')?></td>
<?endif;?>
	<td class="bx-digit-cell"><?=(intval($total_TOTAL_PHRASES)>0?$total_TOTAL_PHRASES:'&nbsp;')?></td>
</tr>
</table>
<?
endif;
$lAdmin_tab5->EndCustomContent();
if($_REQUEST["table_id"]==$sTableID_tab5)
	$lAdmin_tab5->CheckListMode();

$sTableID_tab6 = "t_stat_list_tab6";
$oSort_tab6 = new CAdminSorting($sTableID_tab6);
$lAdmin_tab6 = new CAdminList($sTableID_tab6, $oSort_tab6);
$lAdmin_tab6->BeginCustomContent();
if(strlen($strError)>0):
	CAdminMessage::ShowMessage($strError);
elseif($site_filter=="Y" && $_REQUEST["table_id"]==$sTableID_tab6):
	CAdminMessage::ShowMessage(GetMessage("STAT_NO_DATA"));
elseif($_REQUEST["table_id"]==$sTableID_tab6):
	$arSEARCHERF["DATE1_PERIOD"] = $arFilter["DATE1"];
	$arSEARCHERF["DATE2_PERIOD"] = $arFilter["DATE2"];
	if (strlen($f_by)<=0) $f_by = "s_stat";
	if (strlen($f_order)<=0) $f_order = "desc";
	$searchers = CSearcher::GetList($f_by, $f_order, $arSEARCHERF, $is_filtered);
	if ($f_by=="s_stat") $f_by = "s_today_hits";
?>
<table border="0" cellspacing="0" cellpadding="0" width="100%" class="list-table">
<tr class="heading" valign="top">
	<td><?=GetMessage("STAT_SEARCHER")?></td>
	<td><a href="hit_searcher_list.php?lang=<?=LANG?>&amp;find_date1=<?=$now_date?>&amp;find_date2=<?=$now_date?>&amp;set_filter=Y"><?=GetMessage("STAT_TODAY")?></a><br><?=$now_date?></td>
	<td><a href="hit_searcher_list.php?lang=<?=LANG?>&amp;find_date1=<?=$yesterday_date?>&amp;find_date2=<?=$yesterday_date?>&amp;set_filter=Y"><?=GetMessage("STAT_YESTERDAY")?></a><br><?=$yesterday_date?></td>

	<td><a href="hit_searcher_list.php?lang=<?=LANG?>&amp;find_date1=<?=$bef_yesterday_date?>&amp;find_date2=<?=$bef_yesterday_date?>&amp;set_filter=Y"><?=GetMessage("STAT_BEFORE_YESTERDAY")?></a><br><?=$bef_yesterday_date?></td>
<?if($is_filtered):?>
	<td><a href="hit_searcher_list.php?lang=<?=LANG?>&amp;find_date1=<?=htmlspecialcharsbx($find_date1)?>&amp;find_date2=<?=htmlspecialcharsbx($find_date2)?>&amp;set_filter=Y"><?=GetMessage("STAT_PERIOD")?></a><br> <?=$arFilter["DATE1"]?>&nbsp;- <?=$arFilter["DATE2"]?></td>
<?endif;?>
	<td><a href="hit_searcher_list.php?lang=<?=LANG?>&amp;del_filter=Y"><?=GetMessage("STAT_TOTAL_1")?></a></td>
</tr>
<?
ClearVars("f_");
$i = 0;
$total_TODAY_HITS = 0;
$total_YESTERDAY_HITS = 0;
$total_B_YESTERDAY_HITS = 0;
$total_TOTAL_HITS = 0;
$total_PERIOD_HITS = 0;
while ($searchers->ExtractFields("f_")) :
	$i++;
	$total_TODAY_HITS += $f_TODAY_HITS;
	$total_YESTERDAY_HITS += $f_YESTERDAY_HITS;
	$total_B_YESTERDAY_HITS += $f_B_YESTERDAY_HITS;
	$total_TOTAL_HITS += $f_TOTAL_HITS;
	$total_PERIOD_HITS += $f_PERIOD_HITS;
	if($i<=COption::GetOptionInt("statistic","STAT_LIST_TOP_SIZE")):
?>
<tr>
	<td>
		<?$dynamic_days = CSearcher::DynamicDays($f_ID);
		if ($dynamic_days>=2 && function_exists("ImageCreate")):?>
			<a href="searcher_graph_list.php?lang=<?=LANG?>&amp;find_searchers[]=<?echo $f_ID?>&amp;set_filter=Y" title="<?=GetMessage("STAT_SEARCHER_GRAPH")?>"><?echo $f_NAME?></a>
		<?else:?>
			<?echo $f_NAME?>
		<?endif;?>
	</td>
	<td class="bx-digit-cell">
		<?if(intval($f_TODAY_HITS)>0):?>
			<a href="hit_searcher_list.php?lang=<?=LANG?>&amp;find_date1=<?echo $now_date?>&amp;find_date2=<?echo $now_date?>&amp;find_searcher_id=<?echo $f_ID?>&amp;find_searcher_id_exact_match=Y&amp;set_filter=Y"><?echo $f_TODAY_HITS?></a>
		<?else:?>
			&nbsp;
		<?endif;?>
	</td>
	<td class="bx-digit-cell">
		<?if(intval($f_YESTERDAY_HITS)>0):?>
			<a href="hit_searcher_list.php?lang=<?=LANG?>&amp;find_date1=<?echo $yesterday_date?>&amp;find_date2=<?echo $yesterday_date?>&amp;find_searcher_id=<?echo $f_ID?>&amp;find_searcher_id_exact_match=Y&amp;set_filter=Y"><?echo $f_YESTERDAY_HITS?></a>
		<?else:?>
			&nbsp;
		<?endif;?>
	</td>
	<td class="bx-digit-cell">
		<?if(intval($f_B_YESTERDAY_HITS)>0):?>
			<a href="hit_searcher_list.php?lang=<?=LANG?>&amp;find_date1=<?echo $bef_yesterday_date?>&amp;find_date2=<?echo $bef_yesterday_date?>&amp;find_searcher_id=<?echo $f_ID?>&amp;find_searcher_id_exact_match=Y&amp;set_filter=Y"><?echo $f_B_YESTERDAY_HITS?></a>
		<?else:?>
			&nbsp;
		<?endif;?>
	</td>
<?if($is_filtered):?>
	<td class="bx-digit-cell">
		<?if(intval($f_PERIOD_HITS)>0):?>
			<a href="hit_searcher_list.php?lang=<?=LANG?>&amp;find_date1=<?echo htmlspecialcharsbx($find_date1)?>&amp;find_date2=<?echo htmlspecialcharsbx($find_date2)?>&amp;find_searcher_id=<?echo $f_ID?>&amp;find_searcher_id_exact_match=Y&amp;set_filter=Y"><?echo $f_PERIOD_HITS?></a>
		<?else:?>
			&nbsp;
		<?endif;?>
	</td>
<?endif;?>
	<td class="bx-digit-cell">
		<?if(intval($f_TOTAL_HITS)>0):?>
			<a href="hit_searcher_list.php?lang=<?=LANG?>&amp;find_searcher_id=<?echo $f_ID?>&amp;find_searcher_id_exact_match=Y&amp;set_filter=Y"><?echo $f_TOTAL_HITS?></a></a>
		<?else:?>
			&nbsp;
		<?endif;?>
	</td>
</tr>
<?
	endif;
endwhile;
?>
<tr>
	<td class="bx-digit-cell"><?echo GetMessage("STAT_TOTAL")?></td>
	<td class="bx-digit-cell"><?=(intval($total_TODAY_HITS)>0?$total_TODAY_HITS:'&nbsp;')?></td>
	<td class="bx-digit-cell"><?=(intval($total_YESTERDAY_HITS)>0?$total_YESTERDAY_HITS:'&nbsp;')?></td>
	<td class="bx-digit-cell"><?=(intval($total_B_YESTERDAY_HITS)>0?$total_B_YESTERDAY_HITS:'&nbsp;')?></td>
<?if ($is_filtered) : ?>
	<td class="bx-digit-cell"><?=(intval($total_PERIOD_HITS)>0?$total_PERIOD_HITS:'&nbsp;')?></td>
<?endif;?>
	<td class="bx-digit-cell"><?=(intval($total_TOTAL_HITS)>0?$total_TOTAL_HITS:'&nbsp;')?></td>
</tr>
</table>
<?
endif;
$lAdmin_tab6->EndCustomContent();
if($_REQUEST["table_id"]==$sTableID_tab6)
	$lAdmin_tab6->CheckListMode();

$aTabs = array(
	array(
		"DIV" => "tab1",
		"TAB" => GetMessage("STAT_VISIT"),
		"ICON"=>"",
		"TITLE"=>GetMessage("STAT_VISIT_TITLE"),
		"ONSELECT"=>"selectTabWithFilter(".$sFilterID.", ".$sTableID_tab1.", 'stat_list.php');"
	),
	array(
	"DIV" => "tab2",
	"TAB" => GetMessage("STAT_ADV"),
	"ICON"=>"",
	"TITLE"=>GetMessage("STAT_ADV").' ('.GetMessage("STAT_DIRECT_SESSIONS").') (Top '.COption::GetOptionInt("statistic","STAT_LIST_TOP_SIZE").')',
	"ONSELECT"=>"selectTabWithFilter(".$sFilterID.", ".$sTableID_tab2.", 'stat_list.php');"
	),
	array(
	"DIV" => "tab3",
	"TAB" => GetMessage("STAT_EVENTS"),
	"ICON"=>"",
	"TITLE"=>GetMessage("STAT_EVENTS_2").' (Top '.COption::GetOptionInt("statistic","STAT_LIST_TOP_SIZE").')',
	"ONSELECT"=>"selectTabWithFilter(".$sFilterID.", ".$sTableID_tab3.", 'stat_list.php');"
	),
	array(
	"DIV" => "tab4",
	"TAB" => GetMessage("STAT_REFERERS"),
	"ICON"=>"",
	"TITLE"=>GetMessage("STAT_REFERERS").' (Top '.COption::GetOptionInt("statistic","STAT_LIST_TOP_SIZE").')',
	"ONSELECT"=>"selectTabWithFilter(".$sFilterID.", ".$sTableID_tab4.", 'stat_list.php');"
	),
	array(
	"DIV" => "tab5",
	"TAB" => GetMessage("STAT_PHRASES"),
	"ICON"=>"",
	"TITLE"=>GetMessage("STAT_PHRASES").' (Top '.COption::GetOptionInt("statistic","STAT_LIST_TOP_SIZE").')',
	"ONSELECT"=>"selectTabWithFilter(".$sFilterID.", ".$sTableID_tab5.", 'stat_list.php');"
	),
	array(
	"DIV" => "tab6",
	"TAB" => GetMessage("STAT_INDEXING"),
	"ICON"=>"",
	"TITLE"=>GetMessage("STAT_SITE_INDEXING").' (Top '.COption::GetOptionInt("statistic","STAT_LIST_TOP_SIZE").')',
	"ONSELECT"=>"selectTabWithFilter(".$sFilterID.", ".$sTableID_tab6.", 'stat_list.php');"
	),
);

$tabControl = new CAdminViewTabControl("tabControl", $aTabs);


$lAdmin->BeginCustomContent();

$aContext = array(
	array(
		"TEXT"=>GetMessage("STAT_GRAPH_ALT"),
		"LINK"=>"traffic.php?lang=".LANG."&find_graph_type=date&find_date1_DAYS_TO_BACK=90&find_date2=".ConvertTimeStamp(time()-86400, "SHORT")."&find_host=Y&find_session=Y&find_event=Y&find_guest=Y&find_new_guest=Y&set_filter=Y",
		"TITLE"=>"",
	),
);

$lAdmin->AddAdminContextMenu($aContext, false, false);
?>
<?
$lAdmin->EndCustomContent();

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("STAT_RECORDS_LIST"));
require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$oFilter = new CAdminFilter($sFilterID, array(GetMessage("STAT_SERVER")));

?>

<p><?echo GetMessage("STAT_SERVER_TIME")."&nbsp;&nbsp;".GetTime(time(),"FULL")?></p>

<script type="text/javascript">
var currentTable = null;
var cached = new Array();
function selectTabWithFilter(filter, table, url, force)
{
	var resultDiv = document.getElementById(table.table_id+"_result_div");
	if(resultDiv)
	{
		if(force || !cached[table.table_id])
		{
			if(url.indexOf('?')>=0)
				url += '&lang=<?=LANG?>&set_filter=Y'+filter.GetParameters();
			else
				url += '?lang=<?=LANG?>&set_filter=Y'+filter.GetParameters();
			resultDiv.innerHTML='<?=AddSlashes(GetMessage("STAT_LOADING_WAIT"))?>';

			//table.GetAdminList(url);

			filter.OnSet(table.table_id, url);

			cached[table.table_id]=true;
		}
		currentTable = table;
	}
}
function applyFilter(filter, url)
{
	cached=new Array();
	if(!currentTable)
		currentTable=t_stat_list_tab1;
	if(currentTable)
		selectTabWithFilter(filter, currentTable, url);

}
function clearFilter(filter, url)
{
	filter.ClearParameters();
	//filter.SetActive(false);
	applyFilter(filter, url);
}
</script>

<form name="form1" method="GET" action="<?=$APPLICATION->GetCurPage()?>?">
<?$oFilter->Begin();?>
<tr valign="center">
	<td class="bx-digit-cell" width="0%" nowrap><?echo GetMessage("STAT_F_PERIOD").":"?></td>
	<td width="0%" nowrap><?echo CalendarPeriod("find_date1", $find_date1, "find_date2", $find_date2, "form1","Y")?></td>
</tr>

<tr valign="center">
	<td width="0%" nowrap><?echo GetMessage("STAT_SERVER")?>:</td>
	<td width="0%" nowrap><?echo SelectBoxFromArray("find_site_id", $arSiteDropdown, $find_site_id, GetMessage("MAIN_ALL"));?></td>
</tr>
<?$oFilter->Buttons()?>
<span class="adm-btn-wrap"><input type="submit" class="adm-btn" name="set_filter" value="<?=GetMessage("STAT_F_FIND")?>" title="<?=GetMessage("STAT_F_FIND_TITLE")?>" onClick="BX.adminPanel.showWait(this); applyFilter(<?=$sFilterID?>, 'stat_list.php?lang=<?=LANG?>'); return false;"></span>
<span class="adm-btn-wrap"><input type="submit" class="adm-btn" name="del_filter" value="<?=GetMessage("STAT_F_CLEAR")?>" title="<?=GetMessage("STAT_F_CLEAR_TITLE")?>" onClick="BX.adminPanel.showWait(this); clearFilter(<?=$sFilterID?>, 'stat_list.php?lang=<?=LANG?>'); return false;"></span>
<?
$oFilter->End();
?>
</form>

<?
if($message)
	echo $message->Show();
$lAdmin->DisplayList();
?>
<div class="adm-detail-content-wrap">
	<div class="adm-detail-content">
<?
$tabControl->Begin();
$tabControl->BeginNextTab();
$lAdmin_tab1->DisplayList();
?>

<?$tabControl->BeginNextTab();?>
<a href="/bitrix/admin/adv_list.php?lang=<?=LANG?>"><?=GetMessage("STAT_VIEW_ALL_CAPMPAIGNS")?></a><br><br>
<?$lAdmin_tab2->DisplayList();?>

<?$tabControl->BeginNextTab();?>
<a href="/bitrix/admin/event_type_list.php?lang=<?=LANG?>"><?=GetMessage("STAT_VIEW_ALL_EVENTS")?></a><br><br>
<?$lAdmin_tab3->DisplayList();?>

<?$tabControl->BeginNextTab();?>
<a href="/bitrix/admin/referer_list.php?lang=<?=LANG?>&amp;group_by=none&amp;del_filter=Y"><?=GetMessage("STAT_VIEW_ALL_REFERERS")?></a><br><br>
<?$lAdmin_tab4->DisplayList();?>

<?$tabControl->BeginNextTab();?>
<a href="/bitrix/admin/phrase_list.php?lang=<?=LANG?>&amp;set_default=Y&amp;group_by=none&amp;menu_item_id=1"><?=GetMessage("STAT_VIEW_ALL_PHRASES")?></a><br><br>
<?$lAdmin_tab5->DisplayList();?>

<?$tabControl->BeginNextTab();?>
<a href="/bitrix/admin/searcher_list.php?lang=<?=LANG?>"><?=GetMessage("STAT_VIEW_ALL_SEACHERS")?></a><br><br>
<?$lAdmin_tab6->DisplayList();?>

<?$tabControl->End();?>
	</div>
	<br />
</div>
<?require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>
