<?

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!empty($arResult['ERROR']))
{
	echo $arResult['ERROR'];
	return false;
}

// calendar
CJSCore::Init(array('date'));

$arPeriodTypes = array(
	"month" => GetMessage("TASKS_THIS_MONTH"),
	"month_ago" => GetMessage("TASKS_PREVIOUS_MONTH"),
	"week" => GetMessage("TASKS_THIS_WEEK"),
	"week_ago" => GetMessage("TASKS_PREVIOUS_WEEK"),
	"days" => GetMessage("TASKS_LAST_N_DAYS"),
	"after" => GetMessage("TASKS_AFTER"),
	"before" => GetMessage("TASKS_BEFORE"),
	"interval" => GetMessage("TASKS_DATE_INTERVAL"),
	"all" => GetMessage("TASKS_DATE_ALL")
);

$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/js/report/css/report.css');

$APPLICATION->SetTitle($arResult['report']['TITLE']);

?>


<div class="reports-result-list-wrap">
	<div class="report-table-wrap">
		<div class="reports-list-left-corner"></div>
		<div class="reports-list-right-corner"></div>
		<table cellspacing="0" class="reports-list-table" id="report-result-table">
			<!-- head -->
			<tr>
				<? $i = 0; foreach($arResult['viewColumns'] as $colId => $col): ?>
					<?
						$i++;

						if ($i == 1)
						{
							$th_class = 'reports-first-column';
						}
						else if ($i == count($arResult['viewColumns']))
						{
							$th_class = 'reports-last-column';
						}
						else
						{
							$th_class = 'reports-head-cell';
						}

						// sorting
						//$defaultSort = 'DESC';
						$defaultSort = $col['defaultSort'];

						if ($colId == $arResult['sort_id'])
						{
							$th_class .= ' reports-selected-column';

							if($arResult['sort_type'] == 'ASC')
							{
								$th_class .= ' reports-head-cell-top';
							}
						}
						else
						{
							if ($defaultSort == 'ASC')
							{
								$th_class .= ' reports-head-cell-top';
							}
						}

					?>
					<th class="<?=$th_class?>" colId="<?=$colId?>" defaultSort="<?=$defaultSort?>">
						<div class="reports-head-cell"><?if($defaultSort):
							?><span class="reports-table-arrow"></span><?
						endif?><span class="reports-head-cell-title"><?=htmlspecialcharsbx($col['humanTitle'])?></span></div>
					</th>
				<? endforeach; ?>
			</tr>

			<!-- data -->
			<? foreach ($arResult['data'] as $row): ?>
				<tr class="reports-list-item">
					<? $i = 0; foreach($arResult['viewColumns'] as $col): ?>
						<?
							$i++;
							if ($i == 1)
							{
								$td_class = 'reports-first-column';
							}
							else if ($i == count($arResult['viewColumns']))
							{
								$td_class = 'reports-last-column';
							}
							else
							{
								$td_class = '';
							}

							if (CReport::isColumnPercentable($col))
							{
								$td_class .= ' reports-numeric-column';
							}

							$finalValue = $row[$col['resultName']];

							// add link
							if (!empty($col['href'])  && !empty($row['__HREF_'.$col['resultName']]))
							{
								if (is_array($finalValue))
								{
									// grc
									foreach ($finalValue as $grcIndex => $v)
									{
										$finalValue[$grcIndex] = '<a href="'
										.$arResult['grcData'][$col['resultName']][$grcIndex]['__HREF_'.$col['resultName']]
										.'">'.$v.'</a>';
									}
								}
								elseif (strlen($row[$col['resultName']]))
								{
									$finalValue = '<a href="'.$row['__HREF_'.$col['resultName']].'">'.$row[$col['resultName']].'</a>';
								}
							}

							// magic glue
							if (is_array($finalValue))
							{
								$finalValue = join(' / ', $finalValue);
							}
							if ($arResult['settings']['red_neg_vals'] === true)
							{
								if (is_numeric($finalValue) && $finalValue < 0) $td_class .= ' report-red-neg-val';
							}
						?>
						<td class="<?=$td_class?>"><?=$finalValue?></td>
					<? endforeach; ?>
				</tr>
			<? endforeach; ?>

			<tr>
				<td colspan="<?=count($arResult['viewColumns'])?>" class="reports-pretotal-column">
					<?php echo $arResult["NAV_STRING"]?>
					<br /><br />
					<span style="font-size: 14px;"><?=GetMessage('REPORT_TOTAL')?></span>
				</td>
			</tr>

			<tr>
				<? $i = 0; foreach($arResult['viewColumns'] as $col): ?>
					<?
						$i++;
						if ($i == 1)
						{
							$td_class = 'reports-first-column';
						}
						else if ($i == count($arResult['viewColumns']))
						{
							$td_class = 'reports-last-column';
						}
						else
						{
							$td_class = '';
						}
					?>
					<td class="<?=$td_class?> reports-total-column" sstyle="background-color: #F0F0F0;"><?=htmlspecialcharsbx($col['humanTitle'])?></td>
				<? endforeach; ?>
			</tr>

			<tr>
				<? $i = 0; foreach($arResult['viewColumns'] as $col): ?>
					<?
						$i++;
						if ($i == 1)
						{
							$td_class = 'reports-first-column';
						}
						else if ($i == count($arResult['viewColumns']))
						{
							$td_class = 'reports-last-column';
						}
						else
						{
							$td_class = '';
						}

						if (CReport::isColumnPercentable($col))
						{
							$td_class .= ' reports-numeric-column';
						}
					?>
				<td class="<?=$td_class?>"><?=array_key_exists('TOTAL_'.$col['resultName'], $arResult['total']) ? $arResult['total']['TOTAL_'.$col['resultName']] : '&mdash;'?></td>
				<? endforeach; ?>
			</tr>

		</table>

		<script type="text/javascript">
		BX.ready(function(){
			var rows = BX.findChildren(BX('report-result-table'), {tag:'th'}, true);
			for (i in rows)
			{
				var ds = rows[i].getAttribute('defaultSort');
				if (ds == '')
				{
					BX.addClass(rows[i], 'report-column-disabled-sort')
					continue;
				}

				BX.bind(rows[i], 'click', function(){
					var colId = this.getAttribute('colId');
					var sortType = '';

					var isCurrent = BX.hasClass(this, 'reports-selected-column');

					if (isCurrent)
					{
						var currentSortType = BX.hasClass(this, 'reports-head-cell-top') ? 'ASC' : 'DESC';
						sortType = currentSortType == 'ASC' ? 'DESC' : 'ASC';
					}
					else
					{
						sortType = this.getAttribute('defaultSort');
					}

					var idInp = BX.findChild(BX('report-rewrite-filter'), {attr:{name:'sort_id'}});
					var typeInp = BX.findChild(BX('report-rewrite-filter'), {attr:{name:'sort_type'}});

					idInp.value = colId;
					typeInp.value = sortType;

					BX.submit(BX('report-rewrite-filter'));
				});
			}
		});
		</script>

	</div>
</div>

<?php $this->SetViewTarget("sidebar_tools_1", 100);?>

<!-- control examples -->
<div id="report-chfilter-examples" style="display: none;">

	<div class="filter-field filter-field-user chfilter-field-Bitrix\Main\User" callback="RTFilter_chooseUser">
		<label for="user-email" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<span class="webform-field-textbox-inner">
			<input id="%ID%" type="text" class="webform-field-textbox" caller="true" />
			<input type="hidden" name="%NAME%" value=""/>
			<a href="" class="webform-field-textbox-clear"></a>
		</span>
	</div>

	<div class="filter-field filter-field-user chfilter-field-Bitrix\Socialnetwork\Workgroup" callback="RTFilter_chooseGroup">
		<label for="user-email" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<span class="webform-field-textbox-inner">
			<input id="%ID%" type="text" class="webform-field-textbox" caller="true" />
			<input type="hidden" name="%NAME%" value=""/>
			<a href="" class="webform-field-textbox-clear"></a>
		</span>
	</div>

	<div class="filter-field chfilter-field-datetime">
		<label for="" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<input type="text" value="%VALUE%" name="%NAME%" value="" class="filter-field-calendar" id="" /><a class="filter-date-interval-calendar" href="" title="<?=GetMessage('TASKS_PICK_DATE')?>"><img border="0" src="/bitrix/js/main/core/images/calendar-icon.gif" alt="<?=GetMessage('TASKS_PICK_DATE')?>"></a>
	</div>

	<div class="filter-field chfilter-field-string">
		<label for="" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<input type="text" value="%VALUE%" name="%NAME%" value="" class="filter-textbox" id="" />
	</div>

	<div class="filter-field chfilter-field-integer">
		<label for="" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<input type="text" value="%VALUE%" name="%NAME%" value="" class="filter-textbox" id="" />
	</div>

	<div class="filter-field chfilter-field-float">
		<label for="" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<input type="text" value="%VALUE%" name="%NAME%" value="" class="filter-textbox" id="" />
	</div>

	<div class="filter-field chfilter-field-boolean" callback="RTFilter_chooseBoolean">
		<label for="" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select name="%NAME%" class="filter-dropdown" id="%ID%" caller="true">
			<option value=""><?=GetMessage('REPORT_IGNORE_FILTER_VALUE')?></option>
			<option value="true"><?=GetMessage('REPORT_BOOLEAN_VALUE_TRUE')?></option>
			<option value="false"><?=GetMessage('REPORT_BOOLEAN_VALUE_FALSE')?></option>
		</select>
		<script type="text/javascript">
			function RTFilter_chooseBooleanCatch(value)
			{
				setSelectValue(RTFilter_chooseBoolean_LAST_CALLER, value);
			}
		</script>
	</div>

</div>

<div class="sidebar-block">
	<b class="r2"></b><b class="r1"></b><b class="r0"></b>
	<div class="sidebar-block-inner">
		<div class="filter-block-title report-filter-block-title"><?=GetMessage('REPORT_FILTER')?><!--<a class="filter-settings" href=""></a>--></div>
		<div class="filter-block filter-field-date-combobox filter-field-date-combobox-interval">

			<form id="report-rewrite-filter" action="<?=CComponentEngine::MakePathFromTemplate(
				$arParams["PATH_TO_REPORT_VIEW"],
				array('report_id' => $arParams['REPORT_ID'])
			);?>" method="GET">

			<input type="hidden" name="set_filter" value="Y" />
			<input type="hidden" name="sort_id" value="<?=htmlspecialcharsbx($arResult['sort_id'])?>" />
			<input type="hidden" name="sort_type" value="<?=htmlspecialcharsbx($arResult['sort_type'])?>" />

			<?=$APPLICATION->GetViewContent("report_view_prefilter")?>

			<!-- period -->
			<div class="filter-field">
				<label for="task-interval-filter" class="filter-field-title"><?=GetMessage('REPORT_PERIOD')?></label>
				<select class="filter-dropdown" onchange="OnTaskIntervalChange(this)" id="task-interval-filter" name="F_DATE_TYPE">
					<?php foreach ($arPeriodTypes as $key => $type): ?>
					<option value="<?php echo $key?>"<?=($key == $arResult['period']['type']) ? " selected" : ""?>><?php echo $type?></option>
					<?php endforeach;?>
				</select>
				<span class="filter-date-interval<?php
								if (isset($arResult["FILTER"]["F_DATE_TYPE"])) {
								switch ($arResult["FILTER"]["F_DATE_TYPE"])
								{
									case "interval":
										echo " filter-date-interval-after filter-date-interval-before";
										break;
									case "before":
										echo " filter-date-interval-before";
										break;
									case "after":
										echo " filter-date-interval-after";
										break;
								}
							}
							?>"><span class="filter-date-interval-from"><input type="text" class="filter-date-interval-from" name="F_DATE_FROM" id="REPORT_INTERVAL_F_DATE_FROM"
																			value="<?=$arResult['form_date']['from']?>"/><a
									class="filter-date-interval-calendar" href="" title="<?php echo GetMessage("TASKS_PICK_DATE")?>" id="filter-date-interval-calendar-from"><img border="0"
																src="/bitrix/js/main/core/images/calendar-icon.gif"
																alt="<?php echo GetMessage("TASKS_PICK_DATE")?>"></a></span><span
									class="filter-date-interval-hellip">&hellip;</span><span class="filter-date-interval-to"><input type="text" class="filter-date-interval-to" name="F_DATE_TO"
													id ="REPORT_INTERVAL_F_DATE_TO" value="<?=$arResult['form_date']['to']?>"/><a href=""
																					class="filter-date-interval-calendar"
																					title="<?php echo GetMessage("TASKS_PICK_DATE")?>"
																					id="filter-date-interval-calendar-to"><img
									border="0" src="/bitrix/js/main/core/images/calendar-icon.gif"
									alt="<?php echo GetMessage("TASKS_PICK_DATE")?>"></a></span>
				</span>
					<span class="filter-day-interval<?php if ($arResult["FILTER"]["F_DATE_TYPE"] == "days"): ?> filter-day-interval-selected<?php endif?>"><input type="text" size="5"
						class="filter-date-days"
						value="<?=$arResult['form_date']['days']?>"
						name="F_DATE_DAYS"/> <?php echo GetMessage("TASKS_REPORT_DAYS")?></span>

				<script type="text/javascript">

					function OnTaskIntervalChange(select)
					{
						select.parentNode.className = "filter-field filter-field-date-combobox " + "filter-field-date-combobox-" + select.value;

						var dateInterval = BX.findNextSibling(select, { "tag": "span", "class": "filter-date-interval" });
						var dayInterval = BX.findNextSibling(select, { "tag": "span", "class": "filter-day-interval" });

						BX.removeClass(dateInterval, "filter-date-interval-after filter-date-interval-before");
						BX.removeClass(dayInterval, "filter-day-interval-selected");

						if (select.value == "interval")
							BX.addClass(dateInterval, "filter-date-interval-after filter-date-interval-before");
						else if(select.value == "before")
							BX.addClass(dateInterval, "filter-date-interval-before");
						else if(select.value == "after")
							BX.addClass(dateInterval, "filter-date-interval-after");
						else if(select.value == "days")
							BX.addClass(dayInterval, "filter-day-interval-selected");
					}

					BX.ready(function() {
						BX.bind(BX("filter-date-interval-calendar-from"), "click", function(e) {
							if (!e) e = window.event;

							var curDate = new Date();
							var curTimestamp = Math.round(curDate / 1000) - curDate.getTimezoneOffset()*60;

							BX.calendar({
								node: this,
								field: BX('REPORT_INTERVAL_F_DATE_FROM'),
								bTime: false
							});

							BX.PreventDefault(e);
						});

						BX.bind(BX("filter-date-interval-calendar-to"), "click", function(e) {
							if (!e) e = window.event;

							var curDate = new Date();
							var curTimestamp = Math.round(curDate / 1000) - curDate.getTimezoneOffset()*60;

							BX.calendar({
								node: this,
								field: BX('REPORT_INTERVAL_F_DATE_TO'),
								bTime: false
							});

							BX.PreventDefault(e);
						});

						jsCalendar.InsertDate = function(value) {
							BX.removeClass(this.field.parentNode.parentNode, "webform-field-textbox-empty");
							var value = this.ValueToString(value);
							this.field.value = value.substr(11, 8) == "00:00:00" ? value.substr(0, 10) : value.substr(0, 16);
							this.Close();
						}

						OnTaskIntervalChange(BX('task-interval-filter'));
					});

				</script>
			</div>

			<div id="report-filter-chfilter">

			<!-- insert changeable filters -->
			<?
				// prepare info
				$info = array();

				foreach($arResult['changeableFilters'] as $chFilter)
				{
					$field = isset($chFilter['field']) ? $chFilter['field'] : null;
					// Try to obtain qualified field name (e.g. 'COMPANY_BY.COMPANY_TYPE_BY.STATUS_ID')
					$name = isset($chFilter['name']) ? $chFilter['name'] : ($field ? $field->GetName() : '');
					$info[] = array(
						'TITLE' => $chFilter['title'],
						'COMPARE' => ToLower(GetMessage('REPORT_FILTER_COMPARE_VAR_'.$chFilter['compare'])),
						'NAME' =>$chFilter['formName'],
						'ID' => $chFilter['formId'],
						'VALUE' => $chFilter['value'],
						'FIELD_NAME' => $name,
						'FIELD_TYPE' => $chFilter['data_type']
					);
				}
			?>
			<script type="text/javascript">

			BX.ready(function() {

				var info = <?=CUtil::PhpToJSObject($info)?>;
				for (var i in info)
				{
					// insert value control
					// search in `examples-custom` by name or type
					// then search in `examples` by type
					var cpControl = BX.clone(
						BX.findChild(
							BX('report-chfilter-examples-custom'),
							{className:'chfilter-field-'+info[i].FIELD_NAME}
						)
						||
						BX.findChild(
							BX('report-chfilter-examples-custom'),
							{className:'chfilter-field-'+info[i].FIELD_TYPE}
						)
						||
						BX.findChild(
							BX('report-chfilter-examples'),
							{className:'chfilter-field-'+info[i].FIELD_TYPE}
						)
					, true);

					//global replace %ID%, %NAME%, %TITLE% and etc.
					cpControl.innerHTML = cpControl.innerHTML.replace(/%((?!VALUE)[A-Z]+)%/gi,
						function(str, p1, offset, s)
						{
							var n = p1.toUpperCase();
							return typeof(info[i][n]) != 'undefined' ? BX.util.htmlspecialchars(info[i][n]) : str;
						});

					if (cpControl.getAttribute('callback') != null)
					{
						// set last caller
						var callerName = cpControl.getAttribute('callback') + '_LAST_CALLER';
						var callerObj = BX.findChild(cpControl, {attr:'caller'}, true);
						window[callerName] = callerObj;

						// set value
						var cbFuncName = cpControl.getAttribute('callback') + 'Catch';
						window[cbFuncName](info[i].VALUE);
					}
					else
					{
						cpControl.innerHTML = cpControl.innerHTML.replace('%VALUE%', BX.util.htmlspecialchars(info[i].VALUE));
					}

					BX('report-filter-chfilter').appendChild(cpControl);
				}
			});

			</script>

			</div>

			</form>

			<form id="report-reset-filter" action="<?=CComponentEngine::MakePathFromTemplate(
				$arParams["PATH_TO_REPORT_VIEW"],
				array('report_id' => $arParams['REPORT_ID'])
			);?>" method="GET">
				<input type="hidden" name="sort_id" value="<?=htmlspecialcharsbx($arResult['sort_id'])?>" />
				<input type="hidden" name="sort_type" value="<?=htmlspecialcharsbx($arResult['sort_type'])?>" />
			</form>


			<div class="filter-field-buttons">
				<input id="report-rewrite-filter-button" type="submit" value="<?=GetMessage('REPORT_FILTER_APPLY')?>" class="filter-submit">&nbsp;&nbsp;<input id="report-reset-filter-button" type="submit" name="del_filter_company_search" value="<?=GetMessage('REPORT_FILTER_CANCEL')?>" class="filter-submit">
			</div>

			<script type="text/javascript">

			BX.ready(function(){
				BX.bind(BX('report-reset-filter-button'), 'click', function(){
					BX.submit(BX('report-reset-filter'));
				});
				BX.bind(BX('report-rewrite-filter-button'), 'click', function(){
					BX.submit(BX('report-rewrite-filter'));
				});

				// User controls
				var controls = BX.findChildren(BX('report-rewrite-filter'), {className:'chfilter-field-Bitrix\\Main\\User'}, true);
				if (controls != null)
				{
					for (i in controls)
					{
						var inp = BX.findChild(controls[i], {tag:'input', attr:{type:'text'}}, true);
						var x = BX.findNextSibling(inp, {tag:'a'});
						BX.bind(inp, 'click', RTFilter_chooseUser);
						BX.bind(inp, 'blur', RTFilter_chooseUserCatchFix);
						BX.bind(x, 'click', RTFilter_chooseUserClear);
					}
				}

				// Group controls
				var controls = BX.findChildren(BX('report-rewrite-filter'), {className:'chfilter-field-Bitrix\\Socialnetwork\\Workgroup'}, true);
				if (controls != null)
				{
					for (i in controls)
					{
						var inp = BX.findChild(controls[i], {tag:'input', attr:{type:'text'}}, true);
						var x = BX.findNextSibling(inp, {tag:'a'});
						BX.bind(inp, 'click', RTFilter_chooseGroup);
						//BX.bind(inp, 'blur', RTFilter_chooseGroupCatchFix);
						BX.bind(x, 'click', RTFilter_chooseGroupClear);
					}
				}

				// Date controls
				var controls = BX.findChildren(BX('report-rewrite-filter'), {className:'chfilter-field-datetime'}, true);
				if (controls != null)
				{
					for (i in controls)
					{
						var butt = BX.findChild(controls[i], {tag:'img'}, true);

						BX.bind(butt, "click", function(e) {
							BX.PreventDefault(e);

							var valueInput = BX.findChild(this.parentNode.parentNode, {tag:'input'});

							var curDate = new Date();
							var curTimestamp = Math.round(curDate / 1000) - curDate.getTimezoneOffset()*60;

							BX.calendar({
								node: this,
								field: valueInput,
								bTime: false
							});
						});
					}
				}
			});

			function setSelectValue(select, value)
			{
				var i, j;
				var bFirstSelected = false;
				var bMultiple = !!(select.getAttribute('multiple'));
				if (!(value instanceof Array)) value = new Array(value);
				for (i=0; i<select.options.length; i++)
				{
					for (j in value)
					{
						if (select.options[i].value == value[j])
						{
							if (!bFirstSelected) {bFirstSelected = true; select.selectedIndex = i;}
							select.options[i].selected = true;
							break;
						}
					}
					if (!bMultiple && bFirstSelected) break;
				}
			}

			function RTFilter_chooseUser(control)
			{
				if (this.parentNode)
				{
					var elem = this;
				}
				else
				{
					var elem = BX.findChild(control, {tag:'input', attr: {type:'text'}}, true);
				}

				singlePopup = BX.PopupWindowManager.create("single-employee-popup-"+Math.random(), elem, {
					offsetTop : 1,
					autoHide : true,
					content : BX("Single_"+elem.id+"_selector_content")
				});

				if (singlePopup.popupContainer.style.display != "block")
				{
					singlePopup.show();
				}

				RTFilter_chooseUser_LAST_CALLER = elem;
			}

			function RTFilter_chooseUserCatch(user)
			{
				var inp = RTFilter_chooseUser_LAST_CALLER;
				var hid = BX.findNextSibling(inp, {tag:'input',attr:{type:'hidden'}});
				var x = BX.findNextSibling(inp, {tag:'a'});

				hid.value = user.id;

				if (parseInt(user.id) > 0)
				{
					inp.value = user.name;
					x.style.display = 'inline';
				}
				else
				{
					inp.value = '';
					x.style.display = 'none';
				}

				try
				{
					singlePopup.close();
				}
				catch (e) {}
			}

			function RTFilter_chooseUserCatchFix()
			{
				var inp = RTFilter_chooseUser_LAST_CALLER;
				var hid = BX.findNextSibling(inp, {tag:'input',attr:{type:'hidden'}});

				if (inp.value.length < 1 && parseInt(hid.value) > 0)
				{
					var fobj = window['O_Single_' + inp.id];
					inp.value = fobj.arSelected[hid.value].name;
				}
			}

			function RTFilter_chooseUserClear(e)
			{
				RTFilter_chooseUser_LAST_CALLER = BX.findChild(this.parentNode, {tag:'input',attr:{type:'text'}});

				BX.PreventDefault(e);
				RTFilter_chooseUserCatch({id:''});
			}

			function RTFilter_chooseGroup(control)
			{
				if (this.parentNode)
				{
					var elem = this;
				}
				else
				{
					var elem = BX.findChild(control, {tag:'input', attr: {type:'text'}}, true);
				}

				var popup = window['filterGroupsPopup_'+elem.id];
				popup.searchInput = elem;
				popup.popupWindow.setBindElement(elem);
				popup.show();

				RTFilter_chooseGroup_LAST_CALLER = elem;
			}

			function RTFilter_chooseGroupCatch(group)
			{
				if (group.length < 1) return;

				group = group[0];

				var inp = RTFilter_chooseGroup_LAST_CALLER;
				var hid = BX.findNextSibling(inp, {tag:'input',attr:{type:'hidden'}});
				var x = BX.findNextSibling(inp, {tag:'a'});

				hid.value = group.id;

				if (parseInt(group.id) > 0)
				{
					inp.value = group.title;
					x.style.display = 'inline';
				}
				else
				{
					inp.value = '';
					x.style.display = 'none';
				}

				try
				{
					var popup = window['filterGroupsPopup_'+inp.id];
					popup.popupWindow.close();
				}
				catch (e) {}
			}

			function RTFilter_chooseGroupClear(e)
			{
				RTFilter_chooseGroup_LAST_CALLER = BX.findChild(this.parentNode, {tag:'input',attr:{type:'text'}});

				BX.PreventDefault(e);
				RTFilter_chooseGroupCatch([{id:0}]);
			}

			</script>
			<? foreach ($arResult['changeableFilters'] as $chFilter): ?>

				<? if ($chFilter['field'] && $chFilter['field']->GetDataType() == 'Bitrix\Main\User'): ?>

					<!-- user selector -->

					<?php
					$name = $APPLICATION->IncludeComponent(
							"bitrix:intranet.user.selector.new", ".default", array(
								"MULTIPLE" => "N",
								"NAME" => "Single_".$chFilter['formId'],
								"INPUT_NAME" => $chFilter['formId'],
								"VALUE" => $chFilter['value']['id'],
								"POPUP" => "Y",
								"ON_SELECT" => "RTFilter_chooseUserCatch",
								"NAME_TEMPLATE" => $arParams["USER_NAME_FORMAT"] 
							), null, array("HIDE_ICONS" => "Y")
						);

					?>

				<? elseif ($chFilter['field'] && $chFilter['field']->GetDataType() == 'Bitrix\Socialnetwork\Workgroup'): ?>

					<!-- group selector -->
					<?php
					$name = $APPLICATION->IncludeComponent(
						"bitrix:socialnetwork.group.selector", ".default", array(
							"ON_SELECT" => "RTFilter_chooseGroupCatch", //callback
							"SEARCH_INPUT" => $chFilter['formId'],
							"JS_OBJECT_NAME" => "filterGroupsPopup_".$chFilter['formId'],
							"SELECTED" => $chFilter['value'][0]['id']
						), null, array("HIDE_ICONS" => "Y")
					);
					?>

				<? endif; ?>

			<? endforeach; ?>


		</div>
	</div>
	<i class="r0"></i><i class="r1"></i><i class="r2"></i>
</div>

<? if (strlen($arResult['report']['DESCRIPTION'])): ?>
	<div class="sidebar-block">
		<b class="r2"></b><b class="r1"></b><b class="r0"></b>
		<div class="sidebar-block-inner">
			<div class="filter-block-title report-filter-block-title"><?=GetMessage('REPORT_DESCRIPTION')?></div>
			<div class="filter-block filter-field-date-combobox filter-field-date-combobox-interval"></div>
			<div class="reports-description-text">
				<?=htmlspecialcharsbx($arResult['report']['DESCRIPTION'])?>
			</div>
		</div>
	</div>
<? endif; ?>

<?php $this->EndViewTarget();?>


<?php $this->SetViewTarget("pagetitle", 100);?>
<div class="reports-title-buttons">
	<a class="reports-title-button" href="<?php echo $APPLICATION->GetCurPageParam("EXCEL=Y")?>">
		<i class="reports-title-button-excel-icon"></i><span class="reports-link"><?=GetMessage('REPORT_EXCEL_EXPORT')?></span>
	</a>
	&nbsp;
	<a class="reports-title-button" href="<?=CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_REPORT_CONSTRUCT"], array("report_id" => $arParams['REPORT_ID'], 'action' => 'edit'));?>">
		<i class="reports-title-button-edit-icon"></i><span class="reports-link"><?=GetMessage('REPORT_EDIT')?></span>
	</a>
	&nbsp;
	<a class="reports-title-button" href="<?=CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_REPORT_LIST"], array());?>">
		<i class="reports-title-button-back-icon"></i><span class="reports-link"><?=GetMessage('REPORT_RETURN_TO_LIST')?></span>
	</a>
</div>
<?php $this->EndViewTarget();?>