<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/** @global CMain $APPLICATION */
global $APPLICATION;

/**
 * @param CBitrixComponentTemplate &$component
 * @param mixed &$arParams[]
 * @param mixed &$arResult[]
 */
function reportViewShowTopButtons(&$component, &$arParams, &$arResult)
{
	/** @global CMain $APPLICATION */
	global $APPLICATION;

	$component->SetViewTarget("pagetitle", 100);?>
<div class="reports-title-buttons">
	<a class="reports-title-button" href="<?php echo $APPLICATION->GetCurPageParam("EXCEL=Y&ncc=1")?>"> <?//ncc=1 is for preventing composite work on this hit?>
		<i class="reports-title-button-excel-icon"></i><span class="reports-link"><?=GetMessage('REPORT_EXCEL_EXPORT')?></span>
	</a>
	&nbsp;
<? if ($arResult['MARK_DEFAULT'] > 0) : ?>
	<a class="reports-title-button" href="<?=CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_REPORT_CONSTRUCT"], array("report_id" => $arParams['REPORT_ID'], 'action' => 'copy'));?>">
		<i class="reports-title-button-edit-icon"></i><span class="reports-link"><?=GetMessage('REPORT_COPY')?></span>
	</a>
<? else : ?>
	<a class="reports-title-button" href="<?=CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_REPORT_CONSTRUCT"], array("report_id" => $arParams['REPORT_ID'], 'action' => 'edit'));?>">
		<i class="reports-title-button-edit-icon"></i><span class="reports-link"><?=GetMessage('REPORT_EDIT')?></span>
	</a>
<? endif; ?>
	&nbsp;
	<a class="reports-title-button" href="<?=CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_REPORT_LIST"], array());?>">
		<i class="reports-title-button-back-icon"></i><span class="reports-link"><?=GetMessage('REPORT_RETURN_TO_LIST')?></span>
	</a>
</div>
<?php
	$component->EndViewTarget();
}

if (!empty($arResult['ERROR']))
{
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/js/report/css/report.css');
	$APPLICATION->SetTitle($arResult['report']['TITLE']);
	echo $arResult['ERROR'];
	reportViewShowTopButtons($this, $arParams, $arResult);
	return false;
}

if ($arParams['USE_CHART'] && $arResult['settings']['chart']['display'])
{
	require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/img.php');
	// amCharts
	$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/js/main/amcharts/3.3/amcharts.js');
	$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/js/main/amcharts/3.3/serial.js');
	$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/js/main/amcharts/3.3/pie.js');
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

// determine column data type
function getResultColumnDataType(&$viewColumnInfo, &$customColumnTypes = array(), $helperClassName)
{
	$dataType = null;
	if (array_key_exists($viewColumnInfo['fieldName'], $customColumnTypes))
	{
		$dataType = $customColumnTypes[$viewColumnInfo['fieldName']];
	}
	else
	{
		/** @var Bitrix\Main\Entity\Field[] $viewColumnInfo */
		$dataType = call_user_func(array($helperClassName, 'getFieldDataType'), $viewColumnInfo['field']);
	}
	if (!empty($viewColumnInfo['prcnt']))
	{
		$dataType = 'float';
	}
	else if (!empty($viewColumnInfo['aggr']))
	{
		if ($viewColumnInfo['aggr'] == 'COUNT_DISTINCT') $dataType = 'integer';
		else if ($viewColumnInfo['aggr'] == 'GROUP_CONCAT') $dataType = 'string';
		else if ($dataType == 'boolean')
		{
			if ($viewColumnInfo['aggr'] == 'MIN' || $viewColumnInfo['aggr'] == 'AVG'
				|| $viewColumnInfo['aggr'] == 'MAX' || $viewColumnInfo['aggr'] == 'SUM'
				|| $viewColumnInfo['aggr'] == 'COUNT_DISTINCT')
			{
				$dataType = 'integer';
			}
		}

	}
	return $dataType;
}
?>


<div class="reports-result-list-wrap">
	<?php if ($arParams['USE_CHART'] && $arResult['settings']['chart']['display']): ?>
	<style type="text/css">
		div.graph {
			background-color: white;
			border: 1px solid #D0D8D9;
			box-shadow: 1px 1px 2px 0 rgba(88, 112, 118, 0.1);
			border-radius: 2px;
			color: gray;
			font-size: 14px;
			margin: 0 3px 23px;
			overflow: auto;
			padding: 0 16px;
		}
		a.report-chart-show:before {
			background:url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAYAAAADCAIAAAA/Y+msAAAABnRSTlMA/wD/AP83WBt9AAAAJklEQVQImVXHwQ0AMAzCQKf7sSwMSH6R6td52gJJAEnAuz+Mbf4WzdgMSstcwD0AAAAASUVORK5CYII=") no-repeat;
		}
		a.report-chart-hide:before {
			background:url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAYAAAADCAIAAAA/Y+msAAAABnRSTlMA/wD/AP83WBt9AAAAH0lEQVQImWPctGkTAypg8vX1Reb7+voyQSg4n4GBAQC4MgW4jUjKCwAAAABJRU5ErkJggg==") no-repeat;
		}
		a.report-chart-show:before,
		a.report-chart-hide:before {
			content: "";
			height: 3px;
			width: 6px;
			display: inline-block;
			margin: 0 4px 2px 0;
		}
		a.report-chart-show,
		a.report-chart-hide {
			font-size: 12px;
			color: gray;
			cursor: pointer;
			border-bottom: 1px dashed gray;
			margin-left: 4px;
			text-decoration: none;
		}
	</style>
	<?php
	// data preparation for the chart
	function prepareChartData(&$arResult, &$arGroupingResult = null)
	{
		$nMaxValues = 200;

		// check
		$chartSettings = $arResult['settings']['chart'];
		if (!isset($chartSettings['x_column'])) return null;
		$xColumnIndex = $chartSettings['x_column'];
		if (!is_array($chartSettings['y_columns'])) return null;
		$yColumnsCount = count($chartSettings['y_columns']);
		if ($yColumnsCount === 0) return null;
		$chartTypeIds = array();
		foreach ($arResult['chartTypes'] as $chartTypeInfo) $chartTypeIds[] = $chartTypeInfo['id'];
		if (!is_set($chartSettings['type'])
			|| empty($chartSettings['type'])
			|| !in_array($chartSettings['type'], $chartTypeIds)) return null;
		$chartType = $chartSettings['type'];
		if ($chartType === 'pie') $yColumnsCount = 1;    // pie chart has only one array of a values
		$xColumnDataType = getResultColumnDataType($arResult['viewColumns'][$xColumnIndex],
			$arResult['customColumnTypes'], $arResult['helperClassName']);
		$xColumnResultName = $arResult['viewColumns'][$xColumnIndex]['resultName'];
		$yColumnsIndexes = array();
		$yColumnsResultNames = array();
		$columnsHumanTitles = array();
		$columnsHumanTitles[0] = $arResult['viewColumns'][$xColumnIndex]['humanTitle'];
		$columnsTypes = array();
		$columnsTypes[0] = $xColumnDataType;
		for ($i = 0; $i < $yColumnsCount; $i++)
		{
			$yColumnsIndexes[] = $yColumnIndex = $chartSettings['y_columns'][$i];
			$yColumnsResultNames[] = $arResult['viewColumns'][$yColumnIndex]['resultName'];
			$columnsHumanTitles[] = $arResult['viewColumns'][$yColumnIndex]['humanTitle'];
			$columnsTypes[$i + 1] = getResultColumnDataType($arResult['viewColumns'][$yColumnIndex],
				$arResult['customColumnTypes'], $arResult['helperClassName']);
		}
		$requestData = array(
			'type' => $chartType,
			'columnTypes' => $columnsTypes
		);
		if (!is_null($arGroupingResult) && is_array($arGroupingResult))
		{
			$n = min($nMaxValues, count($arGroupingResult));
			for ($i = 0; $i < $n; $i++)
			{
				$row = array();
				$dataRow = $arGroupingResult[$i];
				$row[0] = htmlspecialcharsback(str_replace(array('&nbsp;', '&quot;'), array('', '"'), strip_tags($dataRow[$xColumnIndex])));
				foreach ($yColumnsIndexes as $yColumnIndex)
					$row[] = htmlspecialcharsback(str_replace(array('&nbsp;', '&quot;'), array('', '"'), strip_tags($dataRow[$yColumnIndex])));
				$requestData['data'][] = $row;
			}
		}
		else
		{
			$n = min($nMaxValues, count($arResult['data']));
			for ($i = 0; $i < $n; $i++)
			{
				$row = array();
				$dataRow = $arResult['data'][$i];
				if (isset($arResult['customChartData'][$i][$xColumnResultName]['multiple']))
				{
					$customValueInfo = $arResult['customChartData'][$i][$xColumnResultName];
					if ($customValueInfo['multiple'] === true)
					{
						$nValue = 0;
						foreach ($customValueInfo as $cvKey => $cvInfo)
						{
							if ($cvKey !== 'multiple')
							{
								$dataValue = null;
								switch ($xColumnDataType)
								{
									case 'boolean':
									case 'float':
									case 'integer':
										if ($nValue === 0)
											$dataValue = $cvInfo['value'];
										else
											$dataValue = $cvInfo['value'] + $dataValue;
										break;
									case 'date':
									case 'datetime':
									case 'string':
									case 'enum':
										if ($nValue === 0)
											$dataValue = $cvInfo['value'];
										else
											$dataValue = $dataValue.' / '.$cvInfo['value'];
										break;
								}
								$nValue++;
							}
						}
					}
					else
					{
						$dataValue = $customValueInfo[0]['value'];
					}
				}
				else
				{
					$dataValue = $dataRow[$xColumnResultName];
				}
				$row[0] = htmlspecialcharsback(str_replace(array('&nbsp;', '&quot;'), array('', '"'), strip_tags($dataValue)));
				foreach ($yColumnsResultNames as $yColumnResultName)
				{
					if (isset($arResult['customChartData'][$i][$yColumnResultName]['multiple']))
					{
						$customValueInfo = $arResult['customChartData'][$i][$yColumnResultName];
						if ($customValueInfo['multiple'] === true)
						{
							$dataValue = 0;
							foreach ($customValueInfo as $cvKey => $cvInfo)
							{
								if ($cvKey !== 'multiple')
									$dataValue = $cvInfo['value'] + $dataValue;
							}
						}
						else
						{
							$dataValue = $customValueInfo[0]['value'];
						}
					}
					else
					{
						$dataValue = $dataRow[$yColumnResultName];
					}
					$row[] = htmlspecialcharsback(str_replace(array('&nbsp;', '&quot;'), array('', '"'), strip_tags($dataValue)));
				}
				$requestData['data'][] = $row;
			}
		}

		return array('requestData' => $requestData, 'columnsNames' => $columnsHumanTitles);
	}
	function validateChartData(&$chartInfo)
	{
		$err = 0;
		$chartXValueTypes = array('boolean', 'date', 'datetime', 'float', 'integer', 'string', 'enum');
		$chartTypes = array(
			array('id' => 'line', 'name' => GetMessage('REPORT_CHART_TYPE_LINE1'), 'value_types' => array(
				/*'boolean', 'date', 'datetime', */'float', 'integer'/*, 'string', 'enum'*/)),
			array('id' => 'bar', 'name' => GetMessage('REPORT_CHART_TYPE_BAR1'), 'value_types' => array(
				/*'boolean', 'date', 'datetime', */'float', 'integer'/*, 'string', 'enum'*/)),
			array('id' => 'pie', 'name' => GetMessage('REPORT_CHART_TYPE_PIE'), 'value_types' => array(
				/*'boolean', 'date', 'datetime', */'float', 'integer'/*, 'string', 'enum'*/))
		);

		// check meta
		$columnYValueTypes = array();
		$nColumns = 0;
		if (is_array($chartInfo)
			&& is_array($chartInfo['requestData']))
		{
			$chartTypeIds = array();
			foreach ($chartTypes as &$chartTypeInfo) $chartTypeIds[] = $chartTypeInfo['id'];
			$chartTypesIndexes = array_flip($chartTypeIds);
			$columnYValueTypes = $chartTypes[$chartTypesIndexes[$chartInfo['requestData']['type']]]['value_types'];
			if (isset($chartInfo['requestData']['type']) && in_array($chartInfo['requestData']['type'], $chartTypeIds))
			{
				if (isset($chartInfo['requestData']['columnTypes']) && is_array($chartInfo['requestData']['columnTypes']))
				{
					if (isset($chartInfo['columnsNames']) && is_array($chartInfo['columnsNames']))
					{
						$nColumnsNames = count($chartInfo['columnsNames']);
						$nColumns = count($chartInfo['requestData']['columnTypes']);
						if ($nColumns >= 2)
						{
							if ($nColumns === $nColumnsNames)
							{
								if ($chartInfo['requestData']['type'] == 'pie' && $nColumns != 2) $err = 5;
								else
								{
									foreach ($chartInfo['requestData']['columnTypes'] as $columnIndex => $columnType)
									{
										if (is_int($columnIndex) && $columnIndex >= 0)
										{
											if ($columnIndex === 0)
											{
												if (!in_array($columnType, $chartXValueTypes)) $err = 7;
											}
											else
											{
												if (!in_array($columnType, $columnYValueTypes)) $err = 8;
											}
										}
										else $err = 6;
										if ($err !== 0) break;
									}
								}
							}
							else $err = 17;
						}
						else $err = 4;
					}
					else $err = 16;
				}
				else $err = 3;
			}
			else $err = 2;
		}
		else $err = 1;

		// check data
		if ($err === 0)
		{
			if (isset($chartInfo['requestData']['data']) && is_array($chartInfo['requestData']['data']))
			{
				foreach ($chartInfo['requestData']['data'] as $rowIndex => &$dataRow)
				{
					if (is_int($rowIndex) && $rowIndex >= 0)
					{
						if (is_array($dataRow))
						{
							$nDataColumns = count($dataRow);
							if ($nDataColumns === $nColumns)
							{
								foreach ($dataRow as $columnIndex => &$dataValue)
								{
									if (is_int($columnIndex) && $columnIndex >= 0)
									{
										// convert type of value
										switch ($chartInfo['requestData']['columnTypes'][$columnIndex])
										{
											case 'boolean':
												$dataValue = ($dataValue) ? true : false;
												break;
											case 'date':
											case 'datetime':
												if (!empty($dataValue))
												{
													if (!CheckDateTime($dataValue, CSite::GetDateFormat('SHORT'))) $err = 15;
												}
												break;
											case 'float':
												if (is_string($dataValue)) $dataValue = str_replace(' ', '', $dataValue);
												$dataValue = (float)$dataValue;
												break;
											case 'integer':
												if (is_string($dataValue)) $dataValue = str_replace(' ', '', $dataValue);
												$dataValue = (int)$dataValue;
												break;
											case 'string':
											case 'enum':
												$dataValue = (string)$dataValue;
												break;
											default:
												$err = 14;
										}
									}
									else $err = 13;
									if ($err !== 0) break;
								}
							}
							else $err = 12;
						}
						else $err = 11;
					}
					else $err = 10;
					if ($err !== 0) break;
				}
			}
			else $err = 9;
		}

		return $err;
	}
	function prepareChartDataForAmCharts(&$chartInfo)
	{
		$type = $categoryField = $categoryType = '';
		$width = $height = 0;
		$data = $valueColors = $valueTypes = $valueFields = array();
		$baseColor = '6699CC';

		$err = validateChartData($chartInfo);

		if ($err === 0)
		{
			switch ($chartInfo['requestData']['type'])
			{
				case 'line':
					$type = 'line';
					break;
				case 'bar':
					$type = 'column';
					break;
				case 'pie':
					$type = 'pie';
					break;
				default:
					$type = 'line';
			}
			$data = array();

			// chart size
			$minWidth = 192;
			$minHeight = 120;
			$maxWidth = 10000;
			$maxHeight = 6250;

			if (isset($chartInfo['requestData']['width']))
			{
				$width = intval($chartInfo['requestData']['width']);
				if ($width < $minWidth) $width = $minWidth;
				if ($width > $maxWidth) $width = $maxWidth;
			}
			else $width = 670;

			if (isset($chartInfo['requestData']['height']))
			{
				$height = intval($chartInfo['requestData']['height']);
				if ($height < $minHeight) $height = $minHeight;
				if ($height > $maxHeight) $height = $maxHeight;
			}
			else $height = 420;

			$categoryType = $chartInfo['requestData']['columnTypes'][0];

			if ($type === 'line')
			{
				if (count($chartInfo['requestData']['data']) >= 1)
				{
					$bDateSort = false;
					$arDateSort = array();
					$tmpData = array();
					foreach ($chartInfo['requestData']['data'] as $rowIndex => $row)
					{
						$dataRow = array();
						foreach ($row as $k => $v)
						{
							$bSkipRow = false;
							if ($k === 0)
							{
								if ($categoryType === 'date' || $categoryType === 'datetime')
								{
									if ($rowIndex === 0)
										$bDateSort = true;
									if (empty($v))
										$bSkipRow = true;
									$v = ConvertDateTime($v, 'YYYY-MM-DD HH:MI:SS');
									$v[10] = 'T';
									if ($bDateSort && !$bSkipRow)
										$arDateSort[$rowIndex] = strtotime($v);
								}
							}
							$dataRow[$chartInfo['columnsNames'][$k]] = $v;
						}
						if (!$bSkipRow)
							$tmpData[] = $dataRow;
					}
					if (!$bDateSort)
					{
						$data = $tmpData;
					}
					else
					{
						if (count($arDateSort) >= 1)
						{
							asort($arDateSort);
							foreach (array_keys($arDateSort) as $rowIndex)
								$data[] = $tmpData[$rowIndex];
						}
					}
					unset($tmpData);
					if (count($data) >= 1)
					{
						$nColors = count($chartInfo['columnsNames']) - 1;
						$color = $baseColor;
						foreach ($chartInfo['columnsNames'] as $k => $v)
						{
							if ($k === 0)
							{
								$categoryField = $v;
							}
							else
							{
								$valueFields[] = $v;
								$valueTypes[] = $chartInfo['requestData']['columnTypes'][$k];
								$valueColors[] = '#'.$color;
								$color = GetNextRGB($color, $nColors);
							}
						}
					}
					else $err = 48;
				}
				else $err = 47;//42;
			}
			else if ($type === 'column')
			{
				if (count($chartInfo['requestData']['data']) >= 1)
				{
					foreach ($chartInfo['requestData']['data'] as $row)
					{
						$dataRow = array();
						foreach ($row as $k => $v)
							$dataRow[$chartInfo['columnsNames'][$k]] = $v;
						$data[] = $dataRow;
					}
					$nColors = count($chartInfo['columnsNames']) - 1;
					$color = $baseColor;
					foreach ($chartInfo['columnsNames'] as $k => $v)
					{
						if ($k === 0)
						{
							$categoryField = $v;
						}
						else
						{
							$valueFields[] = $v;
							$valueTypes[] = $chartInfo['requestData']['columnTypes'][$k];
							$valueColors[] = '#'.$color;
							$color = GetNextRGB($color, $nColors);
						}
					}
				}
				else $err = 47;//43;
			}
			else if ($type === 'pie')
			{
				if (count($chartInfo['requestData']['data']) >= 1)
				{
					$arConsolidated = array();
					foreach ($chartInfo['requestData']['data'] as $dataRow)
					{
						$index = $dataRow[0];
						$arConsolidated[$index] += $dataRow[1];
					}
					$sumAll = 0.0;
					foreach ($arConsolidated as $k => $v)
					{
						if ($v <= 0.0) unset($arConsolidated[$k]);
						else $sumAll += $v;
					}
					$arCounting = $arConsolidated;
					$nValues = count($arCounting);
					if ($nValues > 0)
					{
						$sumAllPrcnt = 0;
						foreach ($arCounting as $k => $v)
						{
							$arCounting[$k] = $v * 100 / $sumAll;
							$sumAllPrcnt =+ $arCounting[$k];
						}
						if (arsort($arCounting, SORT_NUMERIC))
						{
							$averageValuePrcnt = $sumAllPrcnt/$nValues;
							$trifleFactor = max($averageValuePrcnt/50, 1.0);
							$i = 0; $prcntCount = 0.0; $offset = 0;
							foreach ($arCounting as $k => $v)
							{
								if ($v < $trifleFactor)
								{
									$offset = $i;
									break;
								}
								else $prcntCount += $v;
								$i++;
							}
							$sumTrifle = 0;
							if ($offset > 0)
							{
								$arTrifle = array_splice($arCounting, $offset);
								foreach (array_keys($arTrifle) as $k) $sumTrifle += $arConsolidated[$k];
							}
							if (round($prcntCount,2) < 100.0)
							{
								$trifleName = GetMessage('REPORT_CHART_TRIFLE_LABEL_TEXT');
								$arCounting[$trifleName] = 100.0 - $prcntCount;
								$arConsolidated[$trifleName] = $sumTrifle;
								$nValues++;
							}
							$nColors = count($arCounting);
							$color = $baseColor;
							foreach ($arCounting as $k => $v)
							{
								$dataRow = array(
									$chartInfo['columnsNames'][0] => $k,
									$chartInfo['columnsNames'][1] => round($arConsolidated[$k], 2)
								);
								$data[] = $dataRow;
								$valueColors[] = '#'.$color;
								$color = GetNextRGB($color, $nColors);
							}
							$categoryField = $chartInfo['columnsNames'][0];
							$valueFields[] = $chartInfo['columnsNames'][1];
							$valueTypes[] = $chartInfo['requestData']['columnTypes'][1];
						}
						else $err = 46;
					}
					else $err = 45;
				}
				else $err = 47;//44;
			}
			else $err = 41;
		}

		$amChartData = array('err' => $err);

		if ($err === 0)
		{
			$amChartData['type'] = $type;
			$amChartData['width'] = $width;
			$amChartData['height'] = $height;
			$amChartData['data'] = $data;
			$amChartData['categoryField'] = $categoryField;
			$amChartData['categoryType'] = $categoryType;
			$amChartData['valueFields'] = $valueFields;
			$amChartData['valueTypes'] = $valueTypes;
			$amChartData['valueColors'] = $valueColors;
		}

		return $amChartData;
	}

	$chartInfo = prepareChartData($arResult);
	$amChartData = prepareChartDataForAmCharts($chartInfo);
	unset($chartInfo);
	$chartErrorCode = $amChartData['err'];
	$chartErrorMessage = '';
	if ($chartErrorCode !== 0)
	{
		$chartErrorMessage = GetMessage('REPORT_CHART_ERR_'.sprintf('%02d', $chartErrorCode));
	}
	?>
	<div style="margin-bottom: 14px;"><a id="report-chart-showhide" class="report-chart-show"><?= GetMessage('REPORT_CHART_HIDE') ?></a></div>
	<div id="report-chart-container" class="graph"<?php echo ($chartErrorCode > 0) ? '' : ' style="height: 540px;"'; ?>><?= htmlspecialcharsbx($chartErrorMessage) ?></div>
	<script type="text/javascript">
		function reportChartShowHide()
		{
			var chartContainer = BX("report-chart-container");
			if (chartContainer)
			{
				if (chartContainer.style.display === "none")
				{
					chartContainer.style.display = "";
					this.innerHTML = BX.util.htmlspecialchars("<?= CUtil::JSEscape(GetMessage('REPORT_CHART_HIDE')) ?>");
					this.className = "report-chart-show";
				}
				else
				{
					chartContainer.style.display = "none";
					this.innerHTML = BX.util.htmlspecialchars("<?= CUtil::JSEscape(GetMessage('REPORT_CHART_SHOW')) ?>");
					this.className = "report-chart-hide";
				}
			}
		}
		BX.ready(function(){
			var chartLink = BX("report-chart-showhide");
			if (chartLink)
			{
				BX.bind(chartLink, "click", reportChartShowHide);
			}
		});
		<? if ($chartErrorCode === 0): ?>
		function drawChart()
		{
			var amChartData = <?=CUtil::PhpToJSObject($amChartData)?>;
			var chartType = amChartData["type"];
			var valueFields = amChartData["valueFields"];
			var valueColors = amChartData["valueColors"];

			// CHART
			var chart = null;
			if (chartType === "pie")
			{
				chart = new AmCharts.AmPieChart();
			}
			else
			{
				chart = new AmCharts.AmSerialChart();
			}
			chart.dataProvider = amChartData["data"];
			chart.numberFormatter = {
				precision: -1,
				decimalSeparator: '.',
				thousandsSeparator:' '
			};
			chart.percentFormatter = {
				precision: 2,
				decimalSeparator: '.',
				thousandsSeparator:' '
			};
			chart.zoomOutText = "<?=GetMessage('REPORT_CHART_SHOW_ALL_TEXT')?>";
			if (chartType === "pie")
			{
				chart.addTitle(amChartData["categoryField"] + ": " + valueFields[0]);
				chart.titleField = amChartData["categoryField"];
				chart.valueField = valueFields[0];
				chart.outlineAlpha = 0.8;
				chart.outlineThickness = 0;
				chart.balloonText = "<div>[[title]]: [[percents]]%</div>" + valueFields[0] + ": <b>[[value]]</b>";
				chart.colors = valueColors;
				chart.groupedTitle = "<?=GetMessage('REPORT_CHART_TRIFLE_LABEL_TEXT')?>";
			}
			else
			{
				chart.categoryField = amChartData["categoryField"];
			}
			chart.startDuration = 1;
			if (chartType === "column" || chartType === "pie")
			{
				chart.depth3D = 15;
				chart.angle = 30;
			}

			if (chartType == "line" || chartType == "column")
			{
				// AXES X
				var categoryAxis = chart.categoryAxis;
				categoryAxis.labelRotation = 45;
				if (chartType === 'column')
				{
					categoryAxis.gridPosition = "start";
				}
				categoryAxis.title = amChartData["categoryField"];
				if (chartType === "line"
					&& (amChartData["categoryType"] === "date" || amChartData["categoryType"] === "datetime"))
				{
					categoryAxis.dateFormats = [
						{period:"fff", format:"JJ:NN:SS"},
						{period:"ss", format:"JJ:NN:SS"},
						{period:"mm", format:"JJ:NN"},
						{period:"hh", format:"JJ:NN"},
						{period:"DD", format:"DD.MM"},
						{period:"WW", format:"DD.MM"},
						{period:"MM", format:"MM.YYYY"},
						{period:"YYYY", format:"MM.YYYY"}
					];
					categoryAxis.parseDates = true;
					categoryAxis.minPeriod = "DD";
				}

				// VALUE
				for (var i = 0; i < valueFields.length; i++)
				{
					// GRAPH
					var graph = new AmCharts.AmGraph();
					graph.title = valueFields[i];
					graph.valueField = valueFields[i];
					graph.balloonText = "[[title]]: <b>[[value]]</b>";
					graph.type = chartType;
					graph.lineAlpha = 0.8;
					graph.lineColor = valueColors[i];
					if (chartType === "column")
					{
						graph.fillAlphas = 0.8;
					}
					if (chartType === "line")
					{
						graph.bullet = "round";
						graph.hideBulletsCount = 30;
						graph.bulletBorderThickness = 1;
					}
					chart.addGraph(graph);
				}

				// CURSOR
				var chartCursor = new AmCharts.ChartCursor();
				//chartCursor.zoomable = false;
				if (chartType === "line")
				{
					chartCursor.cursorAlpha = 0.8;
					chartCursor.categoryBalloonDateFormat = "DD.MM.YYYY";
					chartCursor.cursorPosition = "mouse";
				}
				else if (chartType === 'column')
				{
					chartCursor.cursorAlpha = 0;
					chartCursor.categoryBalloonEnabled = false;
				}
				chart.addChartCursor(chartCursor);
			}

			// LEGEND
			var legend = new AmCharts.AmLegend();
			legend.align = "left";
			legend.markerType = "square";
			legend.valueWidth = 120;
			//legend.useMarkerColorForValues = true;

			chart.addLegend(legend);
			// WRITE
			chart.write("report-chart-container");
		}

		<? if (\Bitrix\Main\Page\Frame::isAjaxRequest()):?>
		drawChart();
		<? else: ?>
		AmCharts.ready(drawChart);
		<? endif ?>

		<? endif; // if ($chartErrorCode === 0) ?>
	</script>
	<?php endif; // if ($arParams['USE_CHART'] && $arResult['settings']['chart']['display']): ?>
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

							if (CReport::isColumnPercentable($col, $arResult['helperClassName']))
							{
								$colType = getResultColumnDataType($col, $arResult['customColumnTypes'],
									$arResult['helperClassName']);
								if (!in_array($colType, array('string', 'datetime', 'date', 'boolean'), true))
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
										$finalValue[$grcIndex] = '<a target="_blank" href="'
										.$arResult['grcData'][$col['resultName']][$grcIndex]['__HREF_'.$col['resultName']]
										.'">'.$v.'</a>';
									}
								}
								elseif (strlen($row[$col['resultName']]))
								{
									$finalValue = '<a target="_blank" href="'.$row['__HREF_'.$col['resultName']].'">'.$row[$col['resultName']].'</a>';
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

						if (CReport::isColumnPercentable($col, $arResult['helperClassName']))
						{
							$colType = getResultColumnDataType($col, $arResult['customColumnTypes'], $arResult['helperClassName']);
							if (!in_array($colType, array('string', 'datetime', 'date', 'boolean'), true))
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

	<div class="filter-field filter-field-user chfilter-field-\Bitrix\Main\User" callback="RTFilter_chooseUser">
		<label for="user-email" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<span class="webform-field-textbox-inner">
			<input id="%ID%" type="text" class="webform-field-textbox" caller="true" />
			<input type="hidden" name="%NAME%" value=""/>
			<a href="" class="webform-field-textbox-clear"></a>
		</span>
	</div>

	<div class="filter-field filter-field-user chfilter-field-\Bitrix\Socialnetwork\Workgroup" callback="RTFilter_chooseGroup">
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

<!-- UF enumerations control examples -->
<div id="report-chfilter-examples-ufenums" style="display: none;">
	<?
	if (is_array($arResult['ufEnumerations'])):
		foreach ($arResult['ufEnumerations'] as $ufId => $enums):
			foreach ($enums as $fieldKey => $enum):
	?>
	<div class="filter-field chfilter-field-<?=($ufId.'_'.$fieldKey)?>" callback="RTFilter_chooseBoolean">
		<label for="" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select name="%NAME%" class="filter-dropdown" id="%ID%" caller="true">
			<option value=""><?=GetMessage('REPORT_IGNORE_FILTER_VALUE')?></option>
			<?
			foreach ($enum as $itemId => $itemInfo):
			?>
			<option value="<?=$itemId?>"><?=$itemInfo['VALUE']?></option>
			<?
			endforeach;
			?>
		</select>
	</div>
	<?
			endforeach;
		endforeach;
	endif;
	?>
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
				<select class="filter-dropdown" style="margin-bottom: 0;" onchange="OnTaskIntervalChange(this)" id="task-interval-filter" name="F_DATE_TYPE">
					<?php foreach ($arPeriodTypes as $key => $type): ?>
					<option value="<?php echo $key?>"<?=($key == $arResult['period']['type']) ? " selected" : ""?>><?php echo $type?></option>
					<?php endforeach;?>
				</select>
				<span class="filter-date-interval<?php
							if (isset($arResult["FILTER"]["F_DATE_TYPE"]))
							{
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

						var dateInterval = BX.findNextSibling(select, { "tag": "span", 'className': "filter-date-interval" });
						var dayInterval = BX.findNextSibling(select, { "tag": "span", 'className': "filter-day-interval" });

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
						'FIELD_TYPE' => $chFilter['data_type'],
						'IS_UF' => $chFilter['isUF'],
						'UF_ID' => $chFilter['ufId'],
						'UF_NAME' => $chFilter['ufName']
					);
				}
			?>
			<script type="text/javascript">

			BX.ready(function() {
				var info = <?=CUtil::PhpToJSObject($info)?>;
				var cpControl, fieldType;

				for (var i in info)
				{
					if (!info.hasOwnProperty(i))
						continue;

					cpControl = null;
					fieldType = info[i].FIELD_TYPE;
					if (info[i]['IS_UF'] && fieldType === 'enum')
					{
						cpControl = BX.clone(
							BX.findChild(
								BX('report-chfilter-examples-ufenums'),
								{className:'chfilter-field-'+info[i]['UF_ID'] + "_" + info[i]['UF_NAME']}
							),
							true
						);
					}
					else
					{
						// insert value control
						// search in `examples-custom` by name or type
						// then search in `examples` by type
						cpControl = BX.clone(
							BX.findChild(
								BX('report-chfilter-examples-custom'),
								{className:'chfilter-field-'+info[i].FIELD_NAME}
							)
							||
							BX.findChild(
								BX('report-chfilter-examples-custom'),
								{className:'chfilter-field-'+fieldType}
							)
							||
							BX.findChild(
								BX('report-chfilter-examples'),
								{className:'chfilter-field-'+fieldType}
							),
							true
						);
					}

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
				var controls = BX.findChildren(BX('report-rewrite-filter'), {className:'chfilter-field-\\Bitrix\\Main\\User'}, true);
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
				var controls = BX.findChildren(BX('report-rewrite-filter'), {className:'chfilter-field-\\Bitrix\\Socialnetwork\\Workgroup'}, true);
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

				<? /** @var \Bitrix\Main\Entity\ReferenceField[] $chFilter */?>

				<? if ($chFilter['field'] && $chFilter['field'] instanceof \Bitrix\Main\Entity\ReferenceField && $chFilter['field']->getRefEntityName() == '\Bitrix\Main\User'): ?>

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

				<? elseif ($chFilter['field'] && $chFilter['field'] instanceof \Bitrix\Main\Entity\ReferenceField && $chFilter['field']->getRefEntityName() == '\Bitrix\Socialnetwork\Workgroup'): ?>

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

<?php
$this->EndViewTarget();

reportViewShowTopButtons($this, $arParams, $arResult);
?>