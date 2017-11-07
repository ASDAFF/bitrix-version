<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
IncludeAJAX();
$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/components/bitrix/forum.interface/templates/popup/script.js");
$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/js/main/utils.js");
?>
<script>
if (phpVars == null || typeof(phpVars) != "object")
{
	var phpVars = {
		'ADMIN_THEME_ID': '.default',
		'titlePrefix': '<?=CUtil::JSEscape(COption::GetOptionString("main", "site_name", $_SERVER["SERVER_NAME"]))?> - '};
}
</script>

<form name="forum_form" id="forum_form_<?=$arResult["id"]?>" action="<?=$APPLICATION->GetCurPageParam()?>" method="get" class="forum-form">
	<?=bitrix_sessid_post()?>
<?
	foreach ($arResult["FIELDS"] as $key => $res):
		if ($res["TYPE"] == "HIDDEN"):
?>
	<input type="hidden" name="<?=$res["NAME"]?>" value="<?=$res["VALUE"]?>" />
<?
			unset($arResult["FIELDS"][$key]);
		endif;
	endforeach;
?>
<table border="0" cellpadding="0" cellspacing="0" style="font-size:100%;"><tr><td style="font-size:100%;">
<table border="0" cellpadding="0" cellspacing="0" class="forum-title" width="100%">
	<tr>
		<td width="100%"><?=$arParams["HEADER"]["TITLE"]?></td>
<?
			
/* Filter popup*/			
if (count($arResult["FIELDS"]) > $arParams["SHOW_STRINGS"]):
		?><td class="filter-more">
			<span id="switcher_<?=$arResult["id"]?>" onclick="ForumFilter.ShowFilter(this, '<?=$arResult["id"]?>');" <?
				?>title="<?=GetMessage("FMI_SHOW")?>"></span>

		<div style="position:relative;">
			<div id="container_<?=$arResult["id"]?>" style="visibility:hidden; position:absolute;" class="forum-popup">
				<table cellpadding="0" cellspacing="0" border="0" class="forum-popup forum-filter">
					<thead onclick="ForumFilter.CheckFilter('<?=$arResult["id"]?>', 'all')">
						<tr onmouseover="this.className='over'" onmouseout="this.className=''">
							<td><input type="checkbox" name="forum_filter[]" id="forum_filter_<?=$arResult["id"]?>_all" value="all" readonly="readonly" /></td>
							<td><?=GetMessage("FMI_SHOW_ALL_FILTER")?></td></tr>
					</thead>
					<tbody>
		<?
			$counter = 0;
			foreach ($arResult["FIELDS"] as $key => $res):
				$counter++;
				if ($arParams["SHOW_STRINGS"] >= $counter)
					continue;
		?>
					<tr onmouseover="this.className='over'" onmouseout="this.className=''" <?
						?>onclick="ForumFilter.CheckFilter('<?=$arResult["id"]?>', '<?=addslashes($res["NAME"])?>'); <?
							?>document.getElementById('forum_filter_<?=$arResult["id"]?>_all').checked = false;">
						<td><input type="checkbox" name="forum_filter[]" id="forum_filter_<?=$arResult["id"]?>_<?=$res["NAME"]?>" value="<?=$res["NAME"]?>" readonly="readonly" <?=(!in_array($res["NAME"], $arResult["SHOW_FILTER"]) ? "" : " checked='checked'")?> /></td>
						<td><?=$res["TITLE"]?></td></tr>
				<?
			endforeach;
				?>
				</tbody>
				</table>
			</div>
		</div>
		</td><?
endif;
/* Filter popup*/
?></tr></table>

<div class="forum-br"></div>
<table class="forum-main forum-filter" width="100%">
	<tbody><?
	$counter = 0;
	foreach ($arResult["FIELDS"] as $key => $res):
		$counter++;
		if ($arParams["SHOW_STRINGS"] < $counter):
			?><tr id="row_<?=$arResult["id"]."_".$res["NAME"]?>" <?
			?><?=(!in_array($res["NAME"], $arResult["SHOW_FILTER"]) ? " style=\"display:none;\"" : "")?> ><?
		else:
			?><tr><?
		endif;
			
		?><td align="right"><?=$res["TITLE"]?>:</td><td align="left"><?
		
		if ($arParams["SHOW_STRINGS"] < $counter):
			?><span class="filter-hide" onclick="ForumFilter.CheckFilter('<?=$arResult["id"]?>', '<?=addslashes($res["NAME"])?>');"></span><?
		endif;
		
		if ($res["TYPE"] == "SELECT"):
			if (!empty($_REQUEST["del_filter"]))
				$res["ACTIVE"] = "";
			?><select name="<?=$res["NAME"]?>" <?=($res["MULTIPLE"] == "Y" ? "multiple='multiple' size='3'" : "")?>><?
			foreach ($res["VALUE"] as $key => $val) 
			{
				if ($val["TYPE"] == "OPTGROUP"):
					?><optgroup label="<?=$val["NAME"]?>"></optgroup><?
				else:
					?><option value="<?=$key?>" <?=($res["ACTIVE"] == $key ? " selected='selected'" : "")?>><?=$val["NAME"]?></option><?
				endif;
			}
			?></select><?
		elseif ($res["TYPE"] == "PERIOD"):
			if (!empty($_REQUEST["del_filter"]))
			{
				$res["VALUE"] = "";
				$res["VALUE_TO"] = "";
			}
			?><?$APPLICATION->IncludeComponent("bitrix:main.calendar", "",
				array(
					"SHOW_INPUT" => "Y",
					"INPUT_NAME" => $res["NAME"], 
					"INPUT_NAME_FINISH" => $res["NAME_TO"],
					"INPUT_VALUE" => $res["VALUE"], 
					"INPUT_VALUE_FINISH" => $res["VALUE_TO"],
					"FORM_NAME" => "forum_form"),
				$component,
				array(
					"HIDE_ICONS" => "Y"));?><?
		else:
			if (!empty($_REQUEST["del_filter"]))
			{
				$res["VALUE"] = "";
			}
			?><input type="text" name="<?=$res["NAME"]?>" value="<?=$res["VALUE"]?>" /><?
		endif;
		?></td></tr><?
	endforeach;
?>
	</tbody>
	<tfoot>
		<tr><td colspan="2" align="center"><?
	if (empty($arResult["BUTTONS"])):
		?><input type="submit" name="set_filter" value="<?=GetMessage("FORUM_BUTTON_FILTER")?>" />&nbsp;
		<input type="submit" name="del_filter" value="<?=GetMessage("FORUM_BUTTON_RESET")?>" /><?
	else:
		foreach ($arResult["BUTTONS"] as $res):
		?><input type="submit" name="<?=$res["NAME"]?>" value="<?=$res["VALUE"]?>" /><?
		endforeach;
	endif;
	?></td></tr>
	</tfoot>
</table>
</td></tr>
</table>
</form>
