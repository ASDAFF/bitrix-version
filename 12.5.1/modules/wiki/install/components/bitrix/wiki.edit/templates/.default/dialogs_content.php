<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
if($arResult['INSERT_IMAGE'] == 'Y'):
	?>
	<table>
	<tr>
		<td width="30%"><?=GetMessage('WIKI_IMAGE_URL')?>:</td>
		<td width="70%"><input type="text" id="image_url" name="image_url" value="" /></td>
	</tr>
	</table>
	<script type="text/javascript">
		BX.WindowManager.Get().SetTitle('<?=GetMessage('WIKI_INSERT_IMAGE')?>');
		var _BTN = [
			{
				'title': "<?=GetMessage('WIKI_BUTTON_INSERT');?>",
				'id': 'wk_insert',
				'action': function () {
					top.wiki_tag_image(BX('image_url').value);
					BX.WindowManager.Get().Close();
				}
			},
			BX.CDialog.btnCancel
		];

		getSelectedText();
		BX.WindowManager.Get().ClearButtons();
		BX.WindowManager.Get().SetButtons(_BTN);
		BX.WindowManager.Get().adjustSizeEx();
	</script>
	<?
	die();

elseif($arResult['INSERT_CATEGORY'] == 'Y'):
	?>
	<table>
	<tr>
		<td width="30%"><?=GetMessage('WIKI_CATEGORY_NAME')?>:</td>
		<td width="70%"><input type="text" id="category_name" name="category_name" value="" /></td>
	</tr>
	<?
	if (count($arResult['TREE']) > 1):
	?>
	<tr>
		<td width="30%"><?=GetMessage('WIKI_CATEGORY_SELECT')?>:</td>
		<td width="70%">
			<select id="category_select" onchange="if(this.options[this.selectedIndex].value != -1) BX('category_name').value = this.options[this.selectedIndex].value" style="width: 240px;">
			<?foreach ($arResult['TREE'] as $key => $value):?>
				<option value="<?=CUtil::JSEscape($key)?>" title="<?=CUtil::JSEscape(htmlspecialcharsbx($value, ENT_QUOTES))?>"><?=CUtil::JSEscape(htmlspecialcharsbx($value, ENT_QUOTES))?></option>
			<?endforeach;?>
			</select>
		</td>
	</tr>
	<?
	endif;
	?>
	</table>
	<script type="text/javascript">
		BX.WindowManager.Get().SetTitle('<?=GetMessage('WIKI_INSERT_CATEGORY')?>');
		var _BTN = [
			{
				'title': "<?=GetMessage('WIKI_BUTTON_INSERT');?>",
				'id': 'wk_insert',
				'action': function () {
					top.wiki_tag_category(BX('category_name').value);
					BX.WindowManager.Get().Close();
				}
			},
			BX.CDialog.btnCancel
		];

		getSelectedText();
		BX.WindowManager.Get().ClearButtons();
		BX.WindowManager.Get().SetButtons(_BTN);
		BX.WindowManager.Get().adjustSizeEx();
	</script>
	<?
	die();

elseif ($arResult['INSERT_LINK'] == 'Y'):
	?>
	<table>
	<tr>
		<td width="30%"><?=GetMessage('WIKI_LINK_URL')?>:</td>
		<td width="70%">
			<?=(isset($_REQUEST['external']) ? '
			<select id="bx_url_type">
				<option value="http://" SELECTED>http://</option>
				<option value="ftp://" >ftp://</option>
				<option value="https://">https://</option>
				</select>
				' : '')
			?>
			<input type="text" id="link_url" name="link_url" value="" />
		</td>
	</tr>
	<tr>
		<td width="30%"><?=GetMessage('WIKI_LINK_NAME')?>:</td>
		<td width="70%"><input type="text" id="link_name" name="link_name" value="" <?=(isset($_REQUEST['external']) ? 'size="30"': '')?> /></td>
	</tr>
	</table>
	<script type="text/javascript">
		var _bExternal = <?=(isset($_REQUEST['external']) ? 'true' : 'false') ?>;

		BX.WindowManager.Get().SetTitle(_bExternal ? '<?=GetMessage('WIKI_INSERT_EXTERANL_HYPERLINK')?>' : '<?=GetMessage("WIKI_INSERT_HYPERLINK")?>');
		var _BTN = [
			{
				'title': "<?=GetMessage('WIKI_BUTTON_INSERT');?>",
				'id': 'wk_insert',
				'action': function () {
					if (_bExternal)
						top.wiki_tag_url_external(BX('bx_url_type').value,BX('link_url').value, BX('link_name').value);
					else
						top.wiki_tag_url(BX('link_url').value, BX('link_name').value);
					BX.WindowManager.Get().Close();
				}
			},
			BX.CDialog.btnCancel
		];


		BX.WindowManager.Get().ClearButtons();
		BX.WindowManager.Get().SetButtons(_BTN);

		var selectedText = false;
		selectedText = getSelectedText();
		if (selectedText)
			BX('link_name').value = selectedText;

		BX.WindowManager.Get().adjustSizeEx();

	</script>
	<?
	die();

elseif($arResult['IMAGE_UPLOAD'] == 'Y'):
	if (!isset($_POST['do_upload'])) :
	?>
		<form action="<?=POST_FORM_ACTION_URI?>" name="load_form" method="post" enctype="multipart/form-data">
		<?=bitrix_sessid_post()?>
		<input type="hidden" name="do_upload" value="1" />
		<input type="hidden" name="image_upload" value="Y" />
		<table>
		<tr>
			<td width="30%"><?=GetMessage('WIKI_IMAGE')?>:</td>
			<td width="70%"><?=CFile::InputFile('FILE_ID', 20, 0)?></td>
		</tr>
		</table>
		</form>
		<script type="text/javascript">
			var _BTN = [
				BX.CAdminDialog.btnSave,
				BX.CAdminDialog.btnCancel
			];

			getSelectedText();
			BX.WindowManager.Get().SetTitle('<?=GetMessage('WIKI_IMAGE_UPLOAD')?>');
			BX.WindowManager.Get().SetButtons(_BTN);
			BX.WindowManager.Get().adjustSizeEx();
		</script>
	<?
	elseif(strlen($_POST['do_upload'])>0):
		?>
		<script type="text/javascript">
		<!--
		<?
		if(!empty($arResult['IMAGE'])):
		?>

			var my_html = '<div class="wiki-post-image-item"><div class="blog-post-image-item-border"><?=$arResult['IMAGE']['FILE_SHOW']?></div>' +
				'<div class="wiki-post-image-item-input">'+
				'<div><input type="checkbox" name="IMAGE_ID_del[<?=$arResult['IMAGE']['ID']?>]" id="img_del_<?=$arResult['IMAGE']['ID']?>"/> <label for="img_del_<?=$arResult['IMAGE']['ID']?>"><?=GetMessage('WIKI_IMAGE_DELETE')?></label></div></div>';

			var imgTable = top.BX('wiki-post-image');

			imgTable.innerHTML += my_html;
			top.arWikiImg[<?=$arResult['IMAGE']['ID']?>] = '<?=CUTIL::JSEscape($arResult['IMAGE']['ORIGINAL_NAME'])?>';
			var pLEditor = top.pLEditorWiki;
			if(pLEditor && top.document.getElementById('wki-text-html').checked)
			{
				var __image = top.BX('<?=$arResult['IMAGE']['ID']?>');
				var imageSrc = top.BX('<?=$arResult['IMAGE']['ID']?>').src;
				if (!__image.naturalWidth)
				{
					var _imgStyle = '';
					var lgi = new Image();
					lgi.src = imageSrc;
					var _imgWidth = lgi.width;
				}
				else
				{
					_imgWidth = __image.naturalWidth;
				}

				if (_imgWidth > <?=COption::GetOptionString('wiki', 'image_max_width', 600);?>)
				{
					_imgStyle += 'width: <?=COption::GetOptionString('wiki', 'image_max_width', 600);?>;';
				}

				_str = '<img id="' + pLEditor.SetBxTag(false, {'tag': 'wiki_img', 'params': {'id' : <?=$arResult['IMAGE']['ID']?>, 'file_name' : '<?=CUTIL::JSEscape($arResult['IMAGE']['ORIGINAL_NAME'])?>'}}) + '"  \
					src="'+imageSrc+'" style="'+_imgStyle+'">';

				//pLEditor.InsertHTML(_str);
				top.doInsert('[File:<?=CUTIL::JSEscape($arResult['IMAGE']['ORIGINAL_NAME'])?>]','',false,"<?=$arResult['IMAGE']['ID']?>");
			}
			else {
				top.doInsert('[File:<?=CUTIL::JSEscape($arResult['IMAGE']['ORIGINAL_NAME'])?>]','',false);
			}

			top.BX.closeWait();
			top.BX.WindowManager.Get().Close();
			<?
		else :
		?>
			top.BX.WindowManager.Get().ShowError('<?=CUtil::JSEscape($arResult['ERROR_MESSAGE'])?>');
		<?
		endif;
		?>
		//-->
		</script>
		<?
	endif;
	die();

elseif ($arResult['WIKI_oper'] == 'delete'):
	?><table width="100%" height="100%"><tr><td align="center" valign="middle"><?
	if(strlen($arResult['ERROR_MESSAGE'])<=0)
	{
		?>
		<form action="<?=$arResult['PATH_TO_DELETE']?>" name="load_form" method="GET">
		<?=bitrix_sessid_post()?>
		<input type="hidden" name="<?=$arResult['PAGE_VAR']?>" value="<?=$arResult['ELEMENT']['ID']?>"/>
		<input type="hidden" name="<?=$arResult['OPER_VAR']?>" value="delete"/>
		<input type="hidden" name="save" value="Y"/>
		<input type="hidden" name="del_dialog" value="Y"/>
		<table>
		<tr>
			<td><?=GetMessage('WIKI_DELETE_PAGE')?></td>
		</tr>
		</form>
		<script type="text/javascript">
			BX.WindowManager.Get().SetTitle('<?=GetMessage('WIKI_DELETE_CONFIRM')?>');
			var _BTN = [
				{
					'title': "<?=GetMessage('WIKI_BUTTON_DELETE');?>",
					'id': 'wk_delete',
					'action': function () {
						document.forms.load_form.submit();
						BX.WindowManager.Get().Close();
					}
				},
				BX.CDialog.btnCancel
			];

			BX.WindowManager.Get().ClearButtons();
			BX.WindowManager.Get().SetButtons(_BTN);
			BX.WindowManager.Get().adjustSizeEx();
		</script>
		<?
	}
	else
	{

		?>
		<form action="<?=$arResult['LIST_PAGE_URL']?>" name="load_form" method="GET">
		<?=bitrix_sessid_post()?>
		<table cellspacing="0" cellpadding="0" border="0"  class="bx-width100">
		<tr>
			<td><?=$arResult['ERROR_MESSAGE']?></td>
		</tr>
		</form>
		<script type="text/javascript">
			BX.WindowManager.Get().SetTitle('<?=GetMessage('WIKI_DELETE_CONFIRM')?>');
			var _BTN = [
				{
					'title': "Ok",
					'id': 'wk_ok',
					'action': function () {
						document.forms.load_form.submit();
						BX.WindowManager.Get().Close();
					}
				}

			];

			BX.WindowManager.Get().ClearButtons();
			BX.WindowManager.Get().SetButtons(_BTN);
		</script>
		<?
	}
	?></td></tr></table><?
	die();

elseif ($arResult['WIKI_oper'] == 'rename'):
	?><table width="100%" height="100%"><tr><td align="center" valign="middle"><?
	if(strlen($arResult['ERROR_MESSAGE'])<=0)
	{
		$sCatName = '';
		if (CWikiUtils::IsCategoryPage($arResult['ELEMENT']['NAME'] , $sCatName))
			$catLocalName = CWikiUtils::UnlocalizeCategoryName($sPageName);
		?>
		<form action="<?=$arResult['PATH_TO_POST_EDIT']?>" name="rename_form" method="POST">
		<?=bitrix_sessid_post()?>
		<input type="hidden" name="<?=$arResult['PAGE_VAR']?>" value="$arResult['ELEMENT']['NAME_LOCALIZE']?>"/>
		<input type="hidden" name="<?=$arResult['OPER_VAR']?>" value="rename_it"/>
		<input type="hidden" name="save" value="Y"/>
		<table>
		<tr>
			<td><?=GetMessage('WIKI_DIALOG_RENAME_PAGE_NAME').": "?></td>
			<td><input type="text" name="NEW_NAME" value="<? echo ($sCatName ? $sCatName : $arResult['ELEMENT']['NAME_LOCALIZE'])?>"></td>
		</tr>
		</form>
		<script type="text/javascript">
			BX.WindowManager.Get().SetTitle('<?=GetMessage("WIKI_DIALOG_RENAME_TITLE")?>');
			var _BTN = [
				{
					'title': '<?=GetMessage("WIKI_DIALOG_RENAME_BUT_RENAME")?>',
					'id': 'wk_rename',
					'action': function () {
						document.forms.rename_form.submit();
						BX.WindowManager.Get().Close();
					}
				},
				BX.CDialog.btnCancel
			];

			BX.WindowManager.Get().ClearButtons();
			BX.WindowManager.Get().SetButtons(_BTN);
			BX.WindowManager.Get().adjustSizeEx();
		</script>
		<?
	}
	else
	{

		?>
		<form action="<?=$arResult['LIST_PAGE_URL']?>" name="rename_form" method="GET">
		<?=bitrix_sessid_post()?>
		<table cellspacing="0" cellpadding="0" border="0"  class="bx-width100">
		<tr>
			<td><?=$arResult['ERROR_MESSAGE']?></td>
		</tr>
		</form>
		<script type="text/javascript">
			BX.WindowManager.Get().SetTitle('<?=GetMessage("WIKI_DIALOG_RENAME_ERROR")?>');
			var _BTN = [
				{
					'title': "Ok",
					'id': 'wk_ok',
					'action': function () {
						document.forms.rename_form.submit();
						BX.WindowManager.Get().Close();
					}
				}

			];

			BX.WindowManager.Get().ClearButtons();
			BX.WindowManager.Get().SetButtons(_BTN);
		</script>
		<?
	}
	?></td></tr></table><?
	die();

elseif($arResult['LOAD_EDITOR'] == 'Y'):
	include($_SERVER['DOCUMENT_ROOT'].$templateFolder.'/lhe_custom.php');
	die();

endif;
?>