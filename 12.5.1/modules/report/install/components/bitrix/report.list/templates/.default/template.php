<?

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/js/report/css/report.css');
CJSCore::Init(array('date','popup'));

$GLOBALS['APPLICATION']->SetTitle(GetMessage('REPORT_LIST'));

$containerID = 'reports_list_table_'.$arResult['OWNER_ID'];
?>


<? if (empty($arResult['list'])): ?>

<?=GetMessage('REPORT_EMPTY_LIST')?><br/><br/>

<form action="" method="POST">
	<?=bitrix_sessid_post();?>
	<input type="hidden" name="CREATE_DEFAULT" value="1" />
	<input type="hidden" name="HELPER_CLASS" value="<?=htmlspecialcharsbx($arResult['HELPER_CLASS'])?>" />
	<input type="submit" value="<?=GetMessage('REPORT_CREATE_DEFAULT')?>" />
</form>

<? else: ?>

<div class="reports-list-wrap">
	<div class="reports-list">
		<div class="reports-list-left-corner"></div>
		<div class="reports-list-right-corner"></div>
		<style>
			.reports-list-table th:hover {
				cursor: default;
			}
		</style>
		<table cellspacing="0" class="reports-list-table" id="<?=htmlspecialcharsbx($containerID)?>">
			<tr>
				<th class="reports-first-column reports-head-cell-top" colspan="2">
					<div class="reports-head-cell"><!--<span class="reports-table-arrow"></span>--><span class="reports-head-cell-title"><?=GetMessage('REPORT_TABLE_TITLE')?></span></div>
				</th>
				<th class="reports-last-column">
					<div class="reports-head-cell"><!--<span class="reports-table-arrow"></span>--><span class="reports-head-cell-title"><?=GetMessage('REPORT_TABLE_CREATE_DATE')?></span></div>
				</th>
			</tr>
			<? foreach($arResult['list'] as $listItem): ?>
			<tr class="reports-list-item">
				<td class="reports-first-column"><a title="<?=htmlspecialcharsbx(strip_tags($listItem['DESCRIPTION']))?>" href="<?=CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_REPORT_VIEW"], array("report_id" => $listItem['ID']));?>" class="reports-title-link"><?=htmlspecialcharsbx($listItem['TITLE'])?></a></td>
				<td class="reports-list-menu">
					<a id="rmb-<?=$listItem['ID']?>" href="#" class="reports-menu-button"><i class="reports-menu-button-icon"></i></a>
				</td>
				<td  class="reports-date-column reports-last-column"><?=FormatDate($arResult['dateFormat'], strtotime($listItem['CREATED_DATE']))?></td>
			</tr>
			<? endforeach; ?>
		</table>
	</div>
</div>

<script type="text/javascript">
	var menu_butons = BX.findChildren(BX('<?=CUtil::JSEscape($containerID)?>'), {tagName:'a', className:'reports-menu-button'}, true);
	for(var i=0; i<menu_butons.length; i++){
		BX.bind(menu_butons[i], 'click', show_menu)
	}

	function show_menu(e){
		BX.PreventDefault(e);

		var RID = parseInt(this.id.substr(4));
		var edit_href = "<?=CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_REPORT_CONSTRUCT"], array("report_id" => "REPORT_ID", 'action' => 'edit'));?>".replace('REPORT_ID', RID);
		var delete_href = "<?=CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_REPORT_CONSTRUCT"], array("report_id" => "REPORT_ID", 'action' => 'delete'));?>".replace('REPORT_ID', RID);
		var copy_href = "<?=CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_REPORT_CONSTRUCT"], array("report_id" => "REPORT_ID", 'action' => 'copy'));?>".replace('REPORT_ID', RID);

		BX.PopupMenu.show(
				RID,
				this,
				[
					{ text : "<?=GetMessage('REPORT_EDIT_SHORT')?>", title : "<?=GetMessage('REPORT_EDIT_FULL')?>", className : "reports-menu-popup-item-edit", href: edit_href },
					{ text : "<?=GetMessage('REPORT_DELETE_SHORT')?>", title : "<?=GetMessage('REPORT_DELETE_FULL')?>", className : "reports-menu-popup-item-delete", href: delete_href, onclick: function(e){ConfirmReportDelete(RID); BX.PreventDefault(e);} },
					{ text : "<?=GetMessage('REPORT_COPY_SHORT')?>", title : "<?=GetMessage('REPORT_COPY_FULL')?>", className : "reports-menu-popup-item-copy", href: copy_href }
				],
				{}
		);

	}

	BX.message({REPORT_DELETE_CONFIRM : "<?=  GetMessage('REPORT_DELETE_CONFIRM')?>"});

	function ConfirmReportDelete(id)
	{
		var href = "<?=CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_REPORT_CONSTRUCT"], array("report_id" => 'REPORT_ID', 'action' => 'delete_confirmed'));?>".replace('REPORT_ID', id);

		if(confirm(BX.message('REPORT_DELETE_CONFIRM')))
		{
			var form = BX.create('form', {attrs:{method:'post'}});
			form.action = href;
			form.appendChild(BX.create('input', {attrs:{type:'hidden', name:'csrf_token', value:BX.message('bitrix_sessid')}}));

			document.body.appendChild(form);
			BX.submit(form);
		}
	}

</script>

<? endif; ?>

<?php $this->SetViewTarget("pagetitle", 100);?>
<div class="reports-title-buttons">
	<a class="reports-title-button" href="<?=CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_REPORT_CONSTRUCT"], array("report_id" => 0, 'action' => 'create'));?>">
		<i class="reports-title-button-create-icon"></i><span class="reports-link"><?=GetMessage('REPORT_ADD')?></span>
	</a>
</div>
<?php $this->EndViewTarget();?>
