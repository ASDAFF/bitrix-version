<?
define("STOP_STATISTICS", true);
define("BX_SECURITY_SHOW_MESSAGE", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
__IncludeLang(dirname(__FILE__)."/lang/".LANGUAGE_ID."/getdata.php");

if(!CModule::IncludeModule("statistic"))
	die();

if($GLOBALS["APPLICATION"]->GetGroupRight("statistic")=="D")
	die();

if (strlen($_REQUEST["site_id"]) > 0)
{
	$site_filter = "Y";
	$strFilterSite = "&amp;find_site_id=".$_REQUEST["site_id"];
}
else
{
	$site_filter = "N";
	$strFilterSite = "";
}

$arFilter = Array(
	"SITE_ID" => $_REQUEST["site_id"]
);

$now_date = GetTime(time());
$yesterday_date = GetTime(time()-86400);
$bef_yesterday_date = GetTime(time()-172800);

if($_REQUEST["table_id"] == "adv"):
	if($site_filter == "Y")
		die();
	$rsAdv = CAdv::GetList($a_by, $a_order, $arFilter, $is_filtered, 10, $arrGROUP_DAYS, $v);

	?><table class="bx-gadgets-table">
	<tbody>
	<tr>
		<th><?echo GetMessage("GD_STAT_ADV_NAME")?></th>
		<th><a href="/bitrix/admin/session_list.php?lang=<?=$_REQUEST["lang"]?>&amp;find_date1=<?=$now_date?>&amp;find_date2=<?=$now_date?>&amp;find_adv_back=N&amp;set_filter=Y"><?=GetMessage("GD_STAT_TODAY")?></a><br><?=$now_date?></th>
		<th><a href="/bitrix/admin/session_list.php?lang=<?=$_REQUEST["lang"]?>&amp;find_date1=<?=$yesterday_date?>&amp;find_date2=<?=$yesterday_date?>&amp;find_adv_back=N&amp;set_filter=Y"><?=GetMessage("GD_STAT_YESTERDAY")?></a><br><?=$yesterday_date?></th>
		<th><a href="/bitrix/admin/session_list.php?lang=<?=$_REQUEST["lang"]?>&amp;find_date1=<?=$bef_yesterday_date?>&amp;find_adv_back=N&amp;find_date2=<?=$bef_yesterday_date?>&amp;set_filter=Y"><?=GetMessage("GD_STAT_BEFORE_YESTERDAY")?></a><br><?=$bef_yesterday_date?></th>
		<th><a href="/bitrix/admin/session_list.php?lang=<?=$_REQUEST["lang"]?>&amp;find_adv_back=N&amp;del_filter=Y"><?=GetMessage("GD_STAT_TOTAL_1")?></a></th>
	</tr><?

	$bFound = false;
	while ($arAdv = $rsAdv->Fetch()):
		?><tr>
			<td>[<a href="/bitrix/admin/adv_list.php?lang=<?=$_REQUEST["lang"]?>&find_id=<?=$arAdv["ID"]?>&amp;find_id_exact_match=Y&amp;set_filter=Y"><?=$arAdv["ID"]?></a>]&nbsp;<?=$arAdv["REFERER1"]?>&nbsp;/&nbsp;<?=$arAdv["REFERER2"]?></td>
			<td align="right"><?
				if (intval($arAdv["SESSIONS_TODAY"]) > 0):
					?><a href="/bitrix/admin/session_list.php?lang=<?=$_REQUEST["lang"]?>&find_date1=<?=$now_date?>&amp;find_date2=<?=$now_date?>&amp;find_adv_id=<?=$arAdv["ID"]?>&amp;find_adv_id_exact_match=Y&amp;find_adv_back=N&amp;set_filter=Y"><?=$arAdv["SESSIONS_TODAY"]?></a><?
				else:
					?>&nbsp;<?
				endif;
			?></td>
			<td align="right"><?
				if (intval($arAdv["SESSIONS_YESTERDAY"])>0):
					?><a href="/bitrix/admin/session_list.php?lang=<?=$_REQUEST["lang"]?>&find_date1=<?=$yesterday_date?>&amp;find_date2=<?=$yesterday_date?>&amp;find_adv_id=<?=$arAdv["ID"]?>&amp;find_adv_id_exact_match=Y&amp;find_adv_back=N&amp;set_filter=Y"><?=$arAdv["SESSIONS_YESTERDAY"]?></a><?
				else:
					?>&nbsp;<?
				endif;
			?></td>
			<td align="right"><?
				if (intval($arAdv["SESSIONS_BEF_YESTERDAY"])>0):
					?><a href="/bitrix/admin/session_list.php?lang=<?=$_REQUEST["lang"]?>&find_date1=<?=$bef_yesterday_date?>&amp;find_date2=<?=$bef_yesterday_date?>&amp;find_adv_id=<?=$arAdv["ID"]?>&amp;find_adv_id_exact_match=Y&amp;find_adv_back=N&amp;set_filter=Y"><?=$arAdv["SESSIONS_BEF_YESTERDAY"]?></a><?
				else:
					?>&nbsp;<?
				endif;
			?></td>
			<td align="right"><?
				if (intval($arAdv["SESSIONS"])>0):
					?><a href="/bitrix/admin/session_list.php?lang=<?=$_REQUEST["lang"]?>&find_adv_id=<?=$arAdv["ID"]?>&amp;find_adv_id_exact_match=Y&amp;find_adv_back=N&amp;set_filter=Y"><?=$arAdv["SESSIONS"]?></a><?
				else:
					?>&nbsp;<?
				endif;
			?></td>
		</tr><?
		$bFound = true;
	endwhile;

	if (!$bFound):
		?><tr><td align="center" colspan="5"><?=GetMessage("GD_STAT_NO_DATA")?></td></tr><?
	endif;

	?></tbody>
	</table><?

elseif($_REQUEST["table_id"] == "event"):
	if($site_filter == "Y")
		die();

	$e_by = "s_stat";
	$e_order = "desc";
	$rsEvents = CStatEventType::GetList($e_by, $e_order, $arEVENTF, $is_filtered, 10);

	?><table class="bx-gadgets-table">
	<tbody>
	<tr>
		<th><?echo GetMessage("GD_STAT_EVENT")?></th>
		<th><a href="/bitrix/admin/event_list.php?lang=<?=$_REQUEST["lang"]?>&amp;find_date1=<?=$now_date?>&amp;find_date2=<?=$now_date?>&amp;set_filter=Y"><?=GetMessage("GD_STAT_TODAY")?></a><br><?=$now_date?></th>
		<th><a href="/bitrix/admin/event_list.php?lang=<?=$_REQUEST["lang"]?>&amp;find_date1=<?=$yesterday_date?>&amp;find_date2=<?=$yesterday_date?>&amp;set_filter=Y"><?=GetMessage("GD_STAT_YESTERDAY")?></a><br><?=$yesterday_date?></th>
		<th><a href="/bitrix/admin/event_list.php?lang=<?=$_REQUEST["lang"]?>&amp;find_date1=<?=$bef_yesterday_date?>&amp;find_date2=<?=$bef_yesterday_date?>&amp;set_filter=Y"><?=GetMessage("GD_STAT_BEFORE_YESTERDAY")?></a><br><?=$bef_yesterday_date?></th>
		<th><a href="/bitrix/admin/event_list.php?lang=<?=$_REQUEST["lang"]?>&amp;del_filter=Y"><?=GetMessage("GD_STAT_TOTAL_1")?></a></th>
	</tr><?

	$bFound = false;
	while ($arEvent = $rsEvents->Fetch()):
		?><tr>
			<td><?
				$dynamic_days = CStatEventType::DynamicDays($arEvent["ID"]);
				if ($dynamic_days >= 2 && function_exists("ImageCreate")):?>
					<a href="/bitrix/admin/event_graph_list.php?lang=<?=$_REQUEST["lang"]?>&amp;find_events[]=<?=$arEvent["ID"]?>&amp;set_filter=Y" title="<?=GetMessage("GD_STAT_EVENT_GRAPH")?>"><?=$arEvent["EVENT"]?></a>
				<?else:?>
					<?=$arEvent["EVENT"]?>
				<?endif;?>
			</td>
			<td align="right"><?
				if (intval($arEvent["TODAY_COUNTER"]) > 0):
					?><a href="/bitrix/admin/event_list.php?lang=<?=$_REQUEST["lang"]?>&amp;find_date1=<?=$now_date?>&amp;find_date2=<?=$now_date?>&amp;find_event_id=<?=$arEvent["ID"]?>&amp;find_event_id_exact_match=Y&amp;set_filter=Y"><?=$arEvent["TODAY_COUNTER"]?></a><?
				else:
					?>&nbsp;<?
				endif;
			?></td>
			<td align="right">
				<?if (intval($arEvent["YESTERDAY_COUNTER"]) > 0):?>
					<a href="/bitrix/admin/event_list.php?lang=<?=$_REQUEST["lang"]?>&amp;find_date1=<?=$yesterday_date?>&amp;find_date2=<?=$yesterday_date?>&amp;find_event_id=<?=$arEvent["ID"]?>&amp;find_event_id_exact_match=Y&amp;set_filter=Y"><?=$arEvent["YESTERDAY_COUNTER"]?></a>
				<?else:?>
					&nbsp;
				<?endif;?>
			</td>
			<td align="right">
				<?if (intval($arEvent["B_YESTERDAY_COUNTER"]) > 0):?>
					<a href="/bitrix/admin/event_list.php?lang=<?=$_REQUEST["lang"]?>&amp;find_date1=<?=$bef_yesterday_date?>&amp;find_date2=<?=$bef_yesterday_date?>&amp;find_event_id=<?=$arEvent["ID"]?>&amp;find_event_id_exact_match=Y&amp;set_filter=Y"><?=$arEvent["B_YESTERDAY_COUNTER"]?></a>
				<?else:?>
					&nbsp;
				<?endif;?>
			</td>
			<td align="right">
				<?if (intval($arEvent["TOTAL_COUNTER"]) > 0):?>
					<a href="/bitrix/admin/event_list.php?lang=<?=$_REQUEST["lang"]?>&amp;find_event_id=<?=$arEvent["ID"]?>&amp;find_event_id_exact_match=Y&amp;set_filter=Y"><?=$arEvent["TOTAL_COUNTER"]?></a>
				<?else:?>
					&nbsp;
				<?endif;?>
			</td>
		</tr><?
		$bFound = true;
	endwhile;

	if (!$bFound):
		?><tr><td align="center" colspan="5"><?=GetMessage("GD_STAT_NO_DATA")?></td></tr><?
	endif;

	?></tbody>
	</table><?

elseif($_REQUEST["table_id"] == "referer"):
	$rsReferers = CTraffic::GetRefererList($by, $order, $arFilter, $is_filtered, 10);

	?><table class="bx-gadgets-table">
	<tbody>
	<tr>
		<th><?=GetMessage("GD_STAT_SERVER")?></th>
		<th><a href="/bitrix/admin/referer_list.php?lang=<?=$_REQUEST["lang"]?><?=$strFilterSite?>&amp;find_date1=<?=$now_date?>&amp;find_date2=<?=$now_date?>&amp;group_by=none&amp;set_filter=Y"><?=GetMessage("GD_STAT_TODAY")?></a><br><?=$now_date?></th>
		<th><a href="/bitrix/admin/referer_list.php?lang=<?=$_REQUEST["lang"]?><?=$strFilterSite?>&amp;find_date1=<?=$yesterday_date?>&amp;find_date2=<?=$yesterday_date?>&amp;group_by=none&amp;set_filter=Y"><?=GetMessage("GD_STAT_YESTERDAY")?></a><br><?=$yesterday_date?></th>
		<th><a href="/bitrix/admin/referer_list.php?lang=<?=$_REQUEST["lang"]?><?=$strFilterSite?>&amp;find_date1=<?=$bef_yesterday_date?>&amp;find_date2=<?=$bef_yesterday_date?>&amp;group_by=none&amp;set_filter=Y"><?=GetMessage("GD_STAT_BEFORE_YESTERDAY")?></a><br><?=$bef_yesterday_date?></th>
		<th><a href="/bitrix/admin/referer_list.php?lang=<?=$_REQUEST["lang"]?><?=$strFilterSite?>&amp;group_by=none&amp;del_filter=Y"><?=GetMessage("GD_STAT_TOTAL_1")?></a></th>
	</tr><?

	$bFound = false;
	while ($arReferer = $rsReferers->Fetch()):
		?><tr>
			<td><a href="/bitrix/admin/referer_list.php?lang=<?=$_REQUEST["lang"]?><?=$strFilterSite?>&amp;find_from_domain=<?=urlencode("\"".$arReferer["SITE_NAME"]."\"")?>&amp;set_filter=Y"><?=$arReferer["SITE_NAME"]?></a></td>
			<td align="center">
				<?if(intval($arReferer["TODAY_REFERERS"]) > 0):?>
					<a href="/bitrix/admin/referer_list.php?lang=<?=$_REQUEST["lang"]?><?=$strFilterSite?>&amp;find_date1=<?=$now_date?>&amp;find_date2=<?=$now_date?>&amp;find_from=<?=urlencode("\"".$arReferer["SITE_NAME"]."\"")?>&amp;set_filter=Y"><?=$arReferer["TODAY_REFERERS"]?></a>
				<?else:?>
					&nbsp;
				<?endif;?>
			</td>
			<td align="center">
				<?if(intval($arReferer["YESTERDAY_REFERERS"]) > 0):?>
					<a href="/bitrix/admin/referer_list.php?lang=<?=$_REQUEST["lang"]?><?=$strFilterSite?>&amp;find_date1=<?=$yesterday_date?>&amp;find_date2=<?=$yesterday_date?>&amp;find_from=<?=urlencode("\"".$arReferer["SITE_NAME"]."\"")?>&amp;set_filter=Y"><?=$arReferer["YESTERDAY_REFERERS"]?></a>
				<?else:?>
					&nbsp;
				<?endif;?>
			</td>
			<td align="center">
				<?if(intval($arReferer["B_YESTERDAY_REFERERS"]) > 0):?>
					<a href="/bitrix/admin/referer_list.php?lang=<?=$_REQUEST["lang"]?><?=$strFilterSite?>&amp;find_date1=<?=$bef_yesterday_date?>&amp;find_date2=<?=$bef_yesterday_date?>&amp;find_from=<?=urlencode("\"".$arReferer["SITE_NAME"]."\"")?>&amp;set_filter=Y"><?=$arReferer["B_YESTERDAY_REFERERS"]?></a>
				<?else:?>
					&nbsp;
				<?endif;?>
			</td>
			<td align="center">
				<?if(intval($arReferer["TOTAL_REFERERS"]) > 0):?>
					<a href="/bitrix/admin/referer_list.php?lang=<?=$_REQUEST["lang"]?><?=$strFilterSite?>&amp;find_from=<?=urlencode("\"".$arReferer["SITE_NAME"]."\"")?>&amp;set_filter=Y"><?=$arReferer["TOTAL_REFERERS"]?></a>
				<?else:?>
					&nbsp;
				<?endif;?>
			</td>
		</tr><?
		$bFound = true;
	endwhile;

	if (!$bFound):
		?><tr><td align="center" colspan="5"><?=GetMessage("GD_STAT_NO_DATA")?></td></tr><?
	endif;

	?></tbody>
	</table><?

elseif($_REQUEST["table_id"] == "phrase"):
	$rsPhrases = CTraffic::GetPhraseList($s_by, $s_order, $arFilter, $is_filtered, 10);

	?><table class="bx-gadgets-table">
	<tbody>
	<tr>
		<th><?=GetMessage("GD_STAT_PHRASE")?></th>
		<th><a href="/bitrix/admin/phrase_list.php?lang=<?=$_REQUEST["lang"]?><?=$strFilterSite?>&amp;find_date1=<?=$now_date?>&amp;find_date2=<?=$now_date?>&amp;set_filter=Y&amp;group_by=none&amp;menu_item_id=1"><?=GetMessage("GD_STAT_TODAY")?></a><br><?=$now_date?></th>
		<th><a href="/bitrix/admin/phrase_list.php?lang=<?=$_REQUEST["lang"]?><?=$strFilterSite?>&amp;find_date1=<?=$yesterday_date?>&amp;find_date2=<?=$yesterday_date?>&amp;set_filter=Y&amp;group_by=none&amp;menu_item_id=1"><?=GetMessage("GD_STAT_YESTERDAY")?></a><br><?=$yesterday_date?></th>
		<th><a href="/bitrix/admin/phrase_list.php?lang=<?=$_REQUEST["lang"]?><?=$strFilterSite?>&amp;find_date1=<?=$bef_yesterday_date?>&amp;find_date2=<?=$bef_yesterday_date?>&amp;set_filter=Y&amp;group_by=none&amp;menu_item_id=1"><?=GetMessage("GD_STAT_BEFORE_YESTERDAY")?></a><br><?=$bef_yesterday_date?></th>
		<th><a href="/bitrix/admin/phrase_list.php?lang=<?=$_REQUEST["lang"]?><?=$strFilterSite?>&amp;del_filter=Y&amp;group_by=none&amp;menu_item_id=1"><?=GetMessage("GD_STAT_TOTAL_1")?></a></th>
	</tr><?
	$bFound = false;
	while ($arPhrase = $rsPhrases->Fetch()):
		?><tr>
			<td><a href="/bitrix/admin/phrase_list.php?lang=<?=$_REQUEST["lang"]?><?=$strFilterSite?>&amp;find_phrase=<?=urlencode("\"".htmlspecialcharsback($arPhrase["PHRASE"])."\"")?>&amp;set_filter=Y&amp;group_by=none&amp;menu_item_id=1&amp;find_phrase_exact_match=Y"><?echo TruncateText($arPhrase["PHRASE"],50)?></a>&nbsp;</td>
			<td align="center">
				<?if(intval($arReferer["TODAY_PHRASES"]) > 0):?>
					<a href="/bitrix/admin/phrase_list.php?lang=<?=$_REQUEST["lang"]?><?=$strFilterSite?>&amp;find_date1=<?=$now_date?>&amp;find_date2=<?=$now_date?>&amp;find_phrase=<?=urlencode("\"".htmlspecialcharsback($arPhrase["PHRASE"])."\"")?>&amp;set_filter=Y&amp;group_by=none&amp;menu_item_id=1&amp;find_phrase_exact_match=Y"><?=$arPhrase["TODAY_PHRASES"]?></a>
				<?else:?>
					&nbsp;
				<?endif;?>
			</td>
			<td align="center">
				<?if(intval($arReferer["YESTERDAY_PHRASES"]) > 0):?>
					<a href="/bitrix/admin/phrase_list.php?lang=<?=$_REQUEST["lang"]?><?=$strFilterSite?>&amp;find_date1=<?=$yesterday_date?>&amp;find_date2=<?=$yesterday_date?>&amp;find_phrase=<?=urlencode("\"".htmlspecialcharsback($arPhrase["PHRASE"])."\"")?>&amp;set_filter=Y&amp;group_by=none&amp;menu_item_id=1&amp;find_phrase_exact_match=Y"><?=$arPhrase["YESTERDAY_PHRASES"]?></a>
				<?else:?>
					&nbsp;
				<?endif;?>
			</td>
			<td align="center">
				<?if(intval($arReferer["B_YESTERDAY_PHRASES"]) > 0):?>
					<a href="/bitrix/admin/phrase_list.php?lang=<?=$_REQUEST["lang"]?><?=$strFilterSite?>&amp;find_date1=<?=$bef_yesterday_date?>&amp;find_date2=<?=$bef_yesterday_date?>&amp;find_phrase=<?=urlencode("\"".htmlspecialcharsback($arPhrase["PHRASE"])."\"")?>&amp;set_filter=Y&amp;group_by=none&amp;menu_item_id=1&amp;find_phrase_exact_match=Y"><?=$arPhrase["B_YESTERDAY_PHRASES"]?></a>
				<?else:?>
					&nbsp;
				<?endif;?>
			</td>
			<td align="center">
				<?if(intval($arReferer["TOTAL_PHRASES"]) > 0):?>
					<a href="/bitrix/admin/phrase_list.php?lang=<?=$_REQUEST["lang"]?><?=$strFilterSite?>&amp;find_phrase=<?=urlencode("\"".htmlspecialcharsback($arPhrase["PHRASE"])."\"")?>&amp;set_filter=Y&amp;group_by=none&amp;menu_item_id=1&amp;find_phrase_exact_match=Y"><?=$arPhrase["TOTAL_PHRASES"]?></a>
				<?else:?>
					&nbsp;
				<?endif;?>
			</td>
		</tr><?
		$bFound = true;
	endwhile;

	if (!$bFound):
		?><tr><td align="center" colspan="5"><?=GetMessage("GD_STAT_NO_DATA")?></td></tr><?
	endif;

	?></tbody>
	</table><?
endif;
?>