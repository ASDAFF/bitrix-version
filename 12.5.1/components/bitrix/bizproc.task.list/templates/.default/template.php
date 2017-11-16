<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
if (strlen($arResult["FatalErrorMessage"]) > 0)
{
	?>
	<span class='errortext'><?= $arResult["FatalErrorMessage"] ?></span><br /><br />
	<?
}
else
{
	if (strlen($arResult["ErrorMessage"]) > 0)
	{
		?>
		<span class='errortext'><?= $arResult["ErrorMessage"] ?></span><br /><br />
		<?
	}
	?>

	<?
	$APPLICATION->IncludeComponent(
		"bitrix:main.interface.grid",
		"",
		array(
			"GRID_ID"=>$arResult["GRID_ID"],
			"HEADERS"=>$arResult["HEADERS"],
			"SORT"=>$arResult["SORT"],
			"ROWS"=>$arResult["RECORDS"],
			"FOOTER"=>array(array("title"=>GetMessage("BPWC_WLCT_TOTAL"), "value"=>$arResult["ROWS_COUNT"])),
			"ACTIONS"=>array("delete"=>false, "list"=>array()),
			"ACTION_ALL_ROWS"=>false,
			"EDITABLE"=>false,
			"NAV_OBJECT"=>$arResult["NAV_RESULT"],
			"AJAX_MODE"=>"Y",
			"AJAX_OPTION_JUMP"=>"N",
			"FILTER"=>$arResult["FILTER"],
		),
		$component
	);
	?>

	<?
	if ($arParams["SHOW_TRACKING"] == "Y")
	{
		?><h2><?=GetMessage("BPATL_FINISHED_TASKS")?></h2>
		<?
		$APPLICATION->IncludeComponent(
			"bitrix:main.interface.grid",
			"",
			array(
				"GRID_ID"=>$arResult["H_GRID_ID"],
				"HEADERS"=>$arResult["H_HEADERS"],
				"SORT"=>$arResult["H_SORT"],
				"ROWS"=>$arResult["H_RECORDS"],
				"FOOTER"=>array(array("title"=>GetMessage("BPWC_WLCT_TOTAL"), "value"=>$arResult["H_ROWS_COUNT"])),
				"ACTIONS"=>array("delete"=>false, "list"=>array()),
				"ACTION_ALL_ROWS"=>false,
				"EDITABLE"=>false,
				"NAV_OBJECT"=>$arResult["H_NAV_RESULT"],
				"AJAX_MODE"=>"Y",
				"AJAX_OPTION_JUMP"=>"N",
				"FILTER"=>$arResult["H_FILTER"],
			),
			$component
		);
	}
}
?>

<?if (false):?>
<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<div class="bizproc-page-tasks">
<?
if (!empty($arResult["TRACKING"]))
{
	?><h2><?=GetMessage("BPATL_CURRENT_TASKS")?></h2><?
}
?>
	<table class="data-table bizproc-table-main bizproc-tasks-table" cellpadding="0" border="0">
		<thead>
			<tr>
				<th class="bizproc-table-header-name"><?=GetMessage("BPATL_NAME")?></th>
				<th class="bizproc-table-header-date"><?=GetMessage("BPATL_MODIFIED")?></th>
			</tr>
		</thead>
		<tbody>
<?
if (empty($arResult["TASKS"]))
{
?>
			<tr>
				<td colspan="2"><?=GetMessage("BPATL_EMPTY")?></td>
			</tr>
<?
}
else 
{
	foreach ($arResult["TASKS"] as $key => $res)
	{
?>
			<tr>
				<td>
					<div class="bizproc-item-title bizproc-task-title">
						<a href="<?=$res["URL"]["TASK"]?>"><?=$res["NAME"]?></a>
					</div><?
				if (!empty($res["DESCRIPTION"])):
					?><div class="bizproc-item-description bizproc-task-description"><?=nl2br($res["DESCRIPTION"])?></div><?
				endif;
				?></td>
				<td><?=$res["MODIFIED"]?></td>
			</tr>
<?
	}
}
?>
		</tbody>
<?
if (!empty($arResult["TASKS"]) && !empty($arResult["NAV_STRING"])):
?>
		<tfoot>
			<tr>
				<td colspan="2">
					<?=$arResult["NAV_STRING"]?>
				</td>
			</tr>
		</tfoot>
<?
endif;
?>
	</table>
<?
if (!empty($arResult["TRACKING"]))
{
?>
	<h2><?=GetMessage("BPATL_FINISHED_TASKS")?></h2>
	<table class="data-table bizproc-table-main bizproc-tasks-table" cellpadding="0" border="0">
		<thead>
			<tr>
				<th class="bizproc-table-header-date"><?=GetMessage("BPATL_MODIFIED")?></th>
				<th class="bizproc-table-header-name"><?=GetMessage("BPATL_DESCRIPTION")?></th>
			</tr>
		</thead>
		<tbody>
	<?
	foreach($arResult["TRACKING"] as $val)
	{
		?>
			<tr>
				<td class="bizproc-table-item-date"><?=$val["MODIFIED"]?></td>
				<td class="bizproc-table-item-data">
					<div class="bizproc-item-title bizproc-task-title">
						<?=$val["ACTION_NOTE"]?>
					</div>
					<?if(strlen($val["STATE"]["URL"]) > 0):?>
						<a href="<?=$val["STATE"]["URL"]?>" title="<?=GetMessage("BPATL_DOCUMENT_TITLE")?>"><?=GetMessage("BPATL_DOCUMENT")?></a>
					<?endif;?>
				</td>
			</tr>
		<?
	}
	?>
		<tbody>
<?
if (!empty($arResult["NAV_STRING_TRACKING"])):
?>
		<tfoot>
			<tr>
				<td colspan="2">
					<?=$arResult["NAV_STRING_TRACKING"]?>
				</td>
			</tr>
		</tfoot>
<?
endif;
?>
	</table>
	<?

}
?>	
</div>
<?endif;?>