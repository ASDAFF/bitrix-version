<?if(!defined("B_PROLOG_INCLUDED")||B_PROLOG_INCLUDED!==true)die();

$uid = randString(5);
$controller = "BX('file-selectdialog-".$uid."')";
$controlName = $arParams['INPUT_NAME'];
$controlNameFull = $controlName . (($arParams['MULTIPLE'] == 'Y') ? '[]' : '');
$arValue = $arResult['FILES'];
$addClass = ((strpos($_SERVER['HTTP_USER_AGENT'], 'Mac OS') !== false) ? 'file-filemacos' : '');

if (!function_exists('mfi_format_line'))
{
	function mfi_format_line($arValue, $uid, $controlNameFull)
	{
		$result = '';

		if (is_array($arValue) && sizeof($arValue) > 0)
		{
			ob_start();
			foreach ($arValue as $arElement)
			{
				$elementID = intval($arElement['ID']);
?>
				<tr class="file-inline-file" id="wd-doc<?=$elementID?>">
					<td class="files-name">
						<span class="files-text">
							<span class="f-wrap"><?=htmlspecialcharsEx($arElement['ORIGINAL_NAME'])?></span>
						</span>
					</td>
					<td class="files-size"><?=CFile::FormatSize($arElement["FILE_SIZE"])?></td>
					<td class="files-storage">
						<div class="files-storage-block">&nbsp;
							<span class='del-but' onclick="BfileFD<?=$uid?>.agent.StopUpload(BX('wd-doc<?=$elementID?>'));"></span>
							<span class="files-placement"><?/*=htmlspecialcharsEx($title)*/?></span>
							<input id="file-doc<?=$elementID?>" type="hidden" name="<?=htmlspecialcharsbx($controlNameFull)?>" value="<?=$elementID?>" />
						</div>
					</td>
				</tr>
<?
			}
			$result = ob_get_clean();
		}

		return $result;
	}
}

?>
<div id="file-selectdialog-<?=$uid?>" class="file-selectdialog">
	<table id="file-file-template" style='display:none;'>
		<tr class="file-inline-file" id="file-doc">
			<td class="files-name">
				<span class="files-text">
					<span class="f-wrap" data-role='name'>#name#</span>
				</span>
			</td>
			<td class="files-size" data-role='size'>#size#</td>
			<td class="files-storage">
				<div class="files-storage-block">
					<span class="files-placement">&nbsp;</span>
				</div>
			</td>
		</tr>
	</table>
	<div id="file-image-template" style='display:none;'>
		<span class="feed-add-photo-block">
			<span class="feed-add-img-wrap">
				<img width="90" height="90" border="0" data-role='image'>
			</span>
			<span class="feed-add-img-title" data-role='name'>#name#</span>
			<span class="feed-add-post-del-but"></span>
		</span>
	</div>
	<div class="file-extended">
		<span class="file-label"><?=GetMessage('BFDND_FILES')?></span>
		<div class="file-placeholder">
			<table class="files-list" cellspacing="0">
				<tbody class="file-placeholder-tbody">
					<?=mfi_format_line($arValue, $uid, $controlNameFull);?>
				</tbody>
			</table>
		</div>
		<div class="file-selector">
			<?=GetMessage('BFDND_DROPHERE');?><br />
			<span class="file-uploader"><span class="file-but-text"><?=GetMessage('BFDND_SELECT_EXIST');?></span><input class="file-fileUploader <?=$addClass?>" id="file-fileUploader-<?=$uid?>" type="file" multiple='multiple' size='1' /></span>
			<div class="file-load-img"></div>
		</div>
	</div>
	<div class="file-simple" style='padding:0; margin:0;'>
		<span class="file-label"><?=GetMessage('BFDND_FILES')?></span>
		<div class="file-placeholder">
			<table class="files-list" cellspacing="0">
				<tbody class="file-placeholder-tbody">
					<tr style='display: none;'><td colspan='3'></td></tr>
					<?=mfi_format_line($arValue, $uid, $controlNameFull);?>
				</tbody>
			</table>
		</div>
		<div class="file-selector"><span class="file-uploader"><span class="file-uploader-left"></span><span class="file-but-text"><?=GetMessage('BFDND_SELECT_LOCAL');?></span><span class="file-uploader-right"></span><input class="file-fileUploader <?=$addClass?>" id="file-fileUploader-<?=$uid?>" type="file" <?/*multiple='multiple'*/?> size='1' /></span></div></div>
	<script>
		BX.loadCSS('/bitrix/components/bitrix/main.file.input/templates/drag_n_drop/style.css');

		if (! BX.browser.IsIE())
		{
			var bfDisp<?=$uid?> = null;
			BX.loadScript('/bitrix/components/bitrix/main.file.input/templates/drag_n_drop/script.js', function() {
				bfDisp<?=$uid?> = new BlogBFileDialogDispatcher(<?=$controller?>);
			});

			function BfileUnbindDispatcher<?=$uid?>()
			{
				BX.onCustomEvent(<?=$controller?>.parentNode.parentNode, 'UnbindDndDispatcher');
			}
		}


		var BfileFD<?=$uid?> = null;
<? if (sizeof($arValue) < 1) { ?>
		BX.addCustomEvent(<?=$controller?>.parentNode, "BFileDLoadFormController", function() {
<? } ?>
			if (! <?=$controller?>.loaded)
			{
				BX.loadScript(
					[
					'<?=CUtil::GetAdditionalFileURL('/bitrix/components/bitrix/main.file.input/templates/drag_n_drop/script.js')?>',
						'/bitrix/js/main/core/core_ajax.js',
						'/bitrix/js/main/core/core_dd.js'
					], function() {

						<?=$controller?>.loaded = true;

						var dropbox = new BX.DD.dropFiles();
						var variant = 'simple';
						if (dropbox && dropbox.supported() && BX.ajax.FormData.isSupported())
						{
							variant = 'extended';
						}

						BfileFD<?=$uid?> = new BlogBFileDialog({
							'mode' : variant,
							'CID' : "<?=$arResult['CONTROL_UID']?>",
							'upload_path' : "<?=CUtil::JSEscape(htmlspecialcharsback(POST_FORM_ACTION_URI))?>",
							'multiple' : <?=( $arParams['MULTIPLE'] == 'N' ? 'false' : 'true' )?>,
							'controller':  <?=$controller?>,
							'inputName' : "<?=CUtil::JSEscape($controlName)?>",
							'fileInput' :  "file-fileUploader-<?=$uid?>",
							'fileInputName' : "mfi_files[]",
							'msg' : {
								'loading' : "<?=CUtil::JSEscape(GetMessage('BFDND_FILE_LOADING'))?>",
								'file_exists':"<?=CUtil::JSEscape(GetMessage('BFDND_FILE_EXISTS'))?>",
								'upload_error':"<?=CUtil::JSEscape(GetMessage('BFDND_UPLOAD_ERROR'))?>",
								'access_denied':"<p style='margin-top:0;'><?=CUtil::JSEscape(GetMessage('BFDND_ACCESS_DENIED'))?></p>"
							}
						});
<? if (sizeof($arValue) < 1) { ?>
							BX.fx.show(<?=$controller?>, 'fade', {time:0.2});
<? } else { ?>
						BX.show(<?=$controller?>);
<? } ?>
					BX.onCustomEvent('BFileDSelectFileDialogLoaded', [BfileFD<?=$uid?>]);
				});

				if (! BX.browser.IsIE())
					BfileUnbindDispatcher<?=$uid?>();
			} else {
				if (<?=$controller?>.style.display == 'block') {
					BX.fx.hide(<?=$controller?>, 'fade', {time:0.2});
				} else {
					BX.fx.show(<?=$controller?>, 'fade', {time:0.2});
				}
			}
<? if (sizeof($arValue) < 1) { ?>
		});
<? } ?>

		BX.addCustomEvent('BFileDSelectFileDialogLoaded', function(BfileFD<?=$uid?>) {
			BfileFD<?=$uid?>.LoadDialogs('DropInterface');
		});
	</script>
</div>
