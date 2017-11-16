<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!is_array($arResult["PresetFilters"]) &&
	!(array_key_exists("SHOW_SETTINGS_LINK", $arParams) && $arParams["SHOW_SETTINGS_LINK"] == "Y"))
	return;

$isFiltered = false;
foreach (array("flt_created_by_id", "flt_group_id", "flt_date_datesel", "flt_show_hidden") as $param)
{
	if (array_key_exists($param, $_GET) && (strlen($_GET[$param]) > 0) && ($_GET[$param] !== "0"))
	{
		$isFiltered = true;
		break;
	}
}

if ($arResult["MODE"] == "AJAX") // filter form
{
	$APPLICATION->RestartBuffer();

	?><div id="sonet-log-filter" class="sonet-log-filter-block">
		<div class="log-filter-block-title sonet-log-filter-title"><?=GetMessage("SONET_C30_T_FILTER_TITLE")?></div>
		<form method="GET" id="log_filter_form" name="log_filter" target="_self" action="<?=POST_FORM_ACTION_URI?>"><?
		$userName = "";
		if (intval($arParams["CREATED_BY_ID"]) > 0)
		{
			$rsUser = CUser::GetByID($arParams["CREATED_BY_ID"]);
			if ($arUser = $rsUser->Fetch())
				$userName = CUser::FormatName($arParams["NAME_TEMPLATE"], $arUser, ($arParams["SHOW_LOGIN"] != "N" ? true : false));
		}
		?><div class="log-filter-field"><?
		if (IsModuleInstalled("intranet")):
			?><label class="log-filter-field-title" for="log-filter-field-created-by"><?=GetMessage("SONET_C30_T_FILTER_CREATED_BY");?></label>
			<span class="webform-field webform-field-textbox<?=(!$arParams["CREATED_BY_ID"]?" webform-field-textbox-empty":"")?> webform-field-textbox-clearable">
				<span id="sonet-log-filter-created-by" class="webform-field-textbox-inner" style="width: 200px; padding: 0 20px 0 4px;">
					<input type="text" class="webform-field-textbox" id="filter-field-created-by" value="<?=$userName?>" style="height: 20px; width: 200px;"/>
					<a class="sonet-log-field-textbox-clear" href=""></a>
				</span>
			</span><?
			$APPLICATION->IncludeComponent(
				"bitrix:intranet.user.selector.new", ".default", array(
					"MULTIPLE" => "N",
					"NAME" => "FILTER_CREATEDBY",
					"VALUE" => intval($arParams["CREATED_BY_ID"]),
					"POPUP" => "Y",
					"INPUT_NAME" => "filter-field-created-by",
					"ON_SELECT" => "onFilterCreatedBySelect",
					"SITE_ID" => SITE_ID,
					"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
					"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
					"SHOW_EXTRANET_USERS" => "FROM_MY_GROUPS"
				), null, array("HIDE_ICONS" => "Y")
			);
			?><input type="hidden" name="flt_created_by_id" value="<?=$arParams["CREATED_BY_ID"]?>" id="filter_field_createdby_hidden"><?
		else:
			?><label class="log-filter-field-title" for="flt_created_by_id"><?=GetMessage("SONET_C30_T_FILTER_CREATED_BY");?></label><?
			$APPLICATION->IncludeComponent("bitrix:socialnetwork.user_search_input", ".default", array(
					"NAME" => "flt_created_by_id",
					"VALUE" => $_REQUEST["flt_created_by_id"],
					"TEXT" => 'size="20"',
					"EXTRANET" => "I",
					"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
					"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
					"FUNCTON" => "onFilterCreatedBySelect"
				)
			);
		endif;
		?><script type="text/javascript">
			BX.bind(document.forms["log_filter"]["flt_created_by_id"], "change", onFilterCreatedBySelect);
			BX.bind(document.forms["log_filter"]["flt_created_by_id"], "keypress", onFilterCreatedBySelect);
		</script><?
		?></div><?

		if (array_key_exists("flt_comments", $_REQUEST) && $_REQUEST["flt_comments"] == "Y")
			$bChecked = true;
		else
			$bChecked = false;
		?><div class="log-filter-field" id="flt_comments_cont" style="display: <?=(intval($arParams["CREATED_BY_ID"]) > 0 ? "block" : "none")?>"><?
			?><input type="checkbox" class="log-filter-checkbox" id="flt_comments" name="flt_comments" value="Y" <?=($bChecked ? "checked" : "")?>> <label for="flt_comments"><?=GetMessage("SONET_C30_T_FILTER_COMMENTS")?></label><?
		?></div><?

		?><div class="log-filter-field"><?
		if (IsModuleInstalled("intranet")):
			?><label class="log-filter-field-title" for="filter-field-group"><?=GetMessage("SONET_C30_T_FILTER_GROUP");?></label>
			<span class="webform-field webform-field-textbox<?=(!$arResult["Group"]["ID"]?" webform-field-textbox-empty":"")?> webform-field-textbox-clearable">
				<span id="sonet-log-filter-group" class="webform-field-textbox-inner" style="width: 200px; padding: 0 20px 0 4px;">
					<input type="text" class="webform-field-textbox" id="filter-field-group" value="<?=$arResult["Group"]["NAME"]?>" style="height: 20px; width: 200px;"/>
					<a class="sonet-log-field-textbox-clear" href=""></a>
				</span>
			</span>
			<input type="hidden" name="flt_group_id" value="<?=$arResult["Group"]["ID"]?>" id="filter_field_group_hidden">
			<? $APPLICATION->IncludeComponent(
				"bitrix:socialnetwork.group.selector",
				".default",
				array(
					"BIND_ELEMENT" => "sonet-log-filter-group",
					"JS_OBJECT_NAME" => "filterGroupsPopup",
					"ON_SELECT" => "onFilterGroupSelect",
					"SEARCH_INPUT" => "filter-field-group",
					"SELECTED" => $arResult["Group"]["ID"] ? $arResult["Group"]["ID"] : 0
				),
				null,
				array("HIDE_ICONS" => "Y")
			);
		else:
			?><label class="log-filter-field-title" for="filter-field-group"><?=GetMessage("SONET_C30_T_FILTER_GROUP");?></label>
			<span class="log-webform-field log-webform-field-textbox<?=(!$arResult["Group"]["ID"]?" log-webform-field-textbox-empty":"")?> log-webform-field-textbox-clearable">
				<span id="sonet-log-filter-group" class="log-webform-field-textbox-inner" style="width: 200px; padding: 0 20px 0 4px;">
					<input type="text" class="log-webform-field-textbox" id="filter-field-group" value="<?=$arResult["Group"]["NAME"]?>" style="height: 20px; width: 200px;"/>
					<a class="sonet-log-field-textbox-clear" href=""></a>
				</span>
			</span>
			<input type="hidden" name="flt_group_id" value="<?=$arResult["Group"]["ID"]?>" id="filter_field_group_hidden">
			<? $APPLICATION->IncludeComponent(
				"bitrix:socialnetwork.group.selector",
				".default",
				array(
					"BIND_ELEMENT" => "sonet-log-filter-group",
					"JS_OBJECT_NAME" => "filterGroupsPopup",
					"ON_SELECT" => "onFilterGroupSelect",
					"SEARCH_INPUT" => "filter-field-group",
					"SELECTED" => $arResult["Group"]["ID"] ? $arResult["Group"]["ID"] : 0
				),
				null,
				array("HIDE_ICONS" => "Y")
			);
		endif;
		?></div>
		<div class="log-filter-field log-filter-field-date-combobox">
			<label for="flt-date-datesel" class="log-filter-field-title"><?=GetMessage("SONET_C30_T_FILTER_DATE");?></label>
			<select name="flt_date_datesel" onchange="__logOnDateChange(this)" class="log-filter-dropdown" id="flt-date-datesel"><?
			foreach($arResult["DATE_FILTER"] as $k=>$v):
				?><option value="<?=$k?>"<?if($_REQUEST["flt_date_datesel"] == $k) echo ' selected="selected"'?>><?=$v?></option><?
			endforeach;
			?></select>
		<span class="log-filter-field log-filter-day-interval" style="display:none" id="flt_date_day_span">
			<input type="text" name="flt_date_days" value="<?=htmlspecialcharsbx($_REQUEST["flt_date_days"])?>" class="log-filter-date-days" size="2" /> <?echo GetMessage("SONET_C30_DATE_FILTER_DAYS")?>
		</span>
		<span class="log-filter-date-interval log-filter-date-interval-after log-filter-date-interval-before">
			<span class="log-filter-field log-filter-date-interval-from" style="display:none" id="flt_date_from_span"><input type="text" name="flt_date_from" value="<?=(array_key_exists("LOG_DATE_FROM", $arParams) ? $arParams["LOG_DATE_FROM"] : "")?>" class="log-filter-date-interval-from" /><?
				$APPLICATION->IncludeComponent(
					"bitrix:main.calendar",
					"",
					array(
						"SHOW_INPUT" => "N",
						"INPUT_NAME" => "flt_date_from",
						"INPUT_VALUE" => (array_key_exists("LOG_DATE_FROM", $arParams) ? $arParams["LOG_DATE_FROM"] : ""),
						"FORM_NAME" => "log_filter",
					),
					$component,
					array("HIDE_ICONS"	=> true)
				);?></span><span class="log-filter-date-interval-hellip" style="display:none" id="flt_date_hellip_span">&hellip;</span><span class="log-filter-field log-filter-date-interval-to" style="display:none" id="flt_date_to_span"><input type="text" name="flt_date_to" value="<?=(array_key_exists("LOG_DATE_TO", $arParams) ? $arParams["LOG_DATE_TO"] : "")?>" class="log-filter-date-interval-to" /><?
				$APPLICATION->IncludeComponent(
					"bitrix:main.calendar",
					"",
					array(
						"SHOW_INPUT" => "N",
						"INPUT_NAME" => "flt_date_to",
						"INPUT_VALUE" => (array_key_exists("LOG_DATE_TO", $arParams) ? $arParams["LOG_DATE_TO"] : ""),
						"FORM_NAME" => "log_filter",
					),
					$component,
					array("HIDE_ICONS"	=> true)
				);?></span>
		</span>
		</div>

		<script type="text/javascript">
		BX.ready(function(){
			__logOnDateChange(document.forms['log_filter'].flt_date_datesel);
		});
		</script>
		<?
		if ($arParams["SUBSCRIBE_ONLY"] == "Y"):
			if (array_key_exists("flt_show_hidden", $_REQUEST) && $_REQUEST["flt_show_hidden"] == "Y")
				$bChecked = true;
			else
				$bChecked = false;
			?><div class="log-filter-field"><input type="checkbox" class="log-filter-checkbox" id="flt_show_hidden" name="flt_show_hidden" value="Y" <?=($bChecked ? "checked" : "")?>> <label for="flt_show_hidden"><?=GetMessage("SONET_C30_T_SHOW_HIDDEN")?></label></div>
			<?
		endif;

		?><div class="sonet-log-filter-submit"><?
			?><span class="popup-window-button popup-window-button-create" onclick="document.forms['log_filter'].submit();"><span class="popup-window-button-left"></span><span class="popup-window-button-text"><?=GetMessage("SONET_C30_T_SUBMIT")?></span><span class="popup-window-button-right"></span></span><input type="hidden" name="log_filter_submit" value="Y"><?if ($isFiltered):?><a href="<?=$GLOBALS["APPLICATION"]->GetCurPageParam("preset_filter_id=".(array_key_exists("preset_filter_id", $_GET) && strlen($_GET["preset_filter_id"]) > 0 ? htmlspecialcharsbx($_GET["preset_filter_id"]) : "clearall"), array("flt_created_by_id","flt_group_id","flt_date_datesel","flt_date_days","flt_date_from","flt_date_to","flt_date_to","flt_show_hidden","skip_subscribe","preset_filter_id","sessid","bxajaxid", "log_filter_submit", "FILTER_CREATEDBY","SONET_FILTER_MODE", "set_follow_type"), false)?>" class="popup-window-button popup-window-button-link popup-window-button-link-cancel"><span class="popup-window-button-link-text"><?=GetMessage("SONET_C30_T_RESET")?></span></a><?endif;
		?></div>
		<input type="hidden" name="skip_subscribe" value="<?=(isset($_REQUEST["skip_subscribe"]) && $_REQUEST["skip_subscribe"] == "Y" ? "Y" : "N")?>">
		<input type="hidden" name="preset_filter_id" value="<?=(array_key_exists("preset_filter_id", $_GET) ? htmlspecialcharsbx($_GET["preset_filter_id"]) : "")?>" />
		</form>
	</div><?
	die();	
}
else
{
	CUtil::InitJSCore(array("popup", "ajax"));

	if (
		isset($arParams["TARGET_ID"]) 
		&& strlen($arParams["TARGET_ID"]) > 0
	)
		$this->SetViewTarget($arParams["TARGET_ID"], 100);

	?><script type="text/javascript">

		function showLentaMenu(bindElement)
		{
			BX.PopupMenu.show(
				"lenta-sort-popup", 
				bindElement, 
				[
					{ 
						text : "<?=GetMessageJS("SONET_C30_PRESET_FILTER_ALL")?>", 
						className : "feed-sort-item<?=(!$arResult["PresetFilterActive"] ? " feed-sort-item-checked" : "")?>", 
						href : "<?=CUtil::JSEscape($GLOBALS["APPLICATION"]->GetCurPageParam("preset_filter_id=clearall", array("preset_filter_id", "set_follow_type")))?>" 
					},
					<?
					$buttonName = false;
					if (is_array($arResult["PresetFilters"]))
					{
						foreach($arResult["PresetFilters"] as $preset_filter_id => $arPresetFilter)
						{
							if ($arResult["PresetFilterActive"] == $preset_filter_id)
								$buttonName = $arPresetFilter["NAME"];
							?>{ 
								text : "<?=$arPresetFilter["NAME"]?>", 
								className : "feed-sort-item<?=($arResult["PresetFilterActive"] == $preset_filter_id ? " feed-sort-item-checked" : "")?>", 
								href : "<?=CUtil::JSEscape($GLOBALS["APPLICATION"]->GetCurPageParam("preset_filter_id=".$preset_filter_id, array("preset_filter_id", "set_follow_type")))?>" 
							},<?
						}
					}
					?>
					{ 
						text : "<?=GetMessageJS("SONET_C30_T_FILTER_TITLE")?>...", 
						className : "feed-sort-item<?=($isFiltered ? " feed-sort-item-checked" : "")?>", 
						onclick: function() { 
							this.popupWindow.close(); 
							ShowFilterPopup(BX("feed_filter_button"), <?=(IsModuleInstalled("intranet") ? "true" : "false")?>); 
						}
					}<?
					if ($arParams["SHOW_FOLLOW"] != "N")
					{
						?>,
						{ 
							text : "<?=GetMessageJS("SONET_C30_SMART_FOLLOW")?>", 
							className : "feed-sort-item<?=($arResult["FOLLOW_TYPE"] == "N" ? " feed-sort-item-checked" : "")?>", 
							href : "<?=CUtil::JSEscape($GLOBALS["APPLICATION"]->GetCurPageParam("set_follow_type=".($arResult["FOLLOW_TYPE"] == "Y" ? "N" : "Y"), array("set_follow_type")))?>" 
						}
						<?
					}
					?>
				],
				{
					offsetTop: 5,
					offsetLeft: 15,
					angle:
					{
						position: 'top',
						offset: 25
					}
				}
			);
			return false;
		}

	BX.message({
		sonetLFAjaxPath: '<?=CUtil::JSEscape($arResult["AjaxURL"])?>'
	});
	</script><?

	if (IsModuleInstalled("intranet"))
	{
		$APPLICATION->AddHeadScript("/bitrix/components/bitrix/intranet.user.selector.new/templates/.default/users.js");
		$APPLICATION->SetAdditionalCSS("/bitrix/components/bitrix/intranet.user.selector.new/templates/.default/style.css");
	}
	else
	{
		$APPLICATION->AddHeadScript("/bitrix/components/bitrix/socialnetwork.user_search_input/templates/.default/script.js");
		$APPLICATION->SetAdditionalCSS("/bitrix/components/bitrix/socialnetwork.user_search_input/templates/.default/style.css");
	}

	$APPLICATION->AddHeadScript('/bitrix/components/bitrix/socialnetwork.group.selector/templates/.default/script.js');
	$APPLICATION->SetAdditionalCSS("/bitrix/components/bitrix/socialnetwork.group.selector/templates/.default/style.css");

	?><div class="feed-filter-btn-wrap">
		<span class="feed-filter-btn" id="feed_filter_button" onclick="showLentaMenu(this)"><?
			?><?=($buttonName !== false ? $buttonName : GetMessage("SONET_C30_PRESET_FILTER_ALL") )?><?=($isFiltered ? " (".GetMessageJS("SONET_C30_T_FILTER_TITLE").")" : "")?><?
			if ($buttonName === false):			
				?><i id="sonet_log_counter_preset"><?=((intval($arResult["LOG_COUNTER"]) > 0 && $arParams["ENTITY_TYPE"] != SONET_ENTITY_GROUP) ? $arResult["LOG_COUNTER"] : "")?></i><?
			endif;
		?></span>
	</div><?

	if (
		isset($arParams["TARGET_ID"]) 
		&& strlen($arParams["TARGET_ID"]) > 0
	)
		$this->EndViewTarget();

	if (isset($_SESSION["SL_SHOW_FOLLOW_HINT"])):
		unset($_SESSION["SL_SHOW_FOLLOW_HINT"]);
		?><div class="feed-smart-follow-hint"><?=GetMessage("SONET_C30_SMART_FOLLOW_HINT");?></div><?
	endif;
}
?>