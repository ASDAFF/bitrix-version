<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<div class="bx_unsubscribe">
	<?ShowError($arResult["ERROR"]);?>
	<?
	if ($arResult['DATA_SAVED'] == 'Y')
		ShowNote(GetMessage('MAIN_MAIL_UNSUBSCRIBE_SUCCESS'));
	?>
	<?if(!empty($arResult['LIST'])):?>
		<form method="POST" action="<?=$arResult['FORM_URL']?>">
			<p class="bx_unsubscribe_text_black"><?=GetMessage('MAIN_MAIL_UNSUBSCRIBE_TEMPL_DEFAULT_LIST')?></p>
			<dl class="bx_unsubscribe_list">
				<?foreach($arResult['LIST'] as $arSub):?>
					<dt class="bx_unsubscribe_temp">
						<input class="bx_unsubscribe_input" type="checkbox" name="MAIN_MAIL_UNSUB[]" id="MAIN_MAIL_UNSUB_<?=$arSub['ID']?>" value="<?=$arSub['ID']?>" <?=($arSub['SELECTED'] ? 'checked' : '')?> />
						<label class="bx_unsubscribe_label" for="MAIN_MAIL_UNSUB_<?=$arSub['ID']?>"><?=htmlspecialcharsbx($arSub['NAME'])?></label>
					</dt>
					<dd class="bx_unsubscribe_prop"><?=htmlspecialcharsbx($arSub['DESC'])?></dd>
				<?endforeach;?>
			</dl>
			<p class="bx_unsubscribe_text_gray"><?=GetMessage('MAIN_MAIL_UNSUBSCRIBE_TEMPL_DEFAULT_SELECT')?></p>

			<div class="bx_unsubscribe_btn_container">
				<input type="submit" value="<?=GetMessage('MAIN_MAIL_UNSUBSCRIBE_TEMPL_DEFAULT_BUTTON')?>" class="bx_unsubscribe_btn">
			</div>
			<input type="hidden" value="Y" name="MAIN_MAIL_UNSUB_BUTTON">
			<?=bitrix_sessid_post()?>
		</form>
	<?elseif(empty($arResult["ERROR"])):?>
		<?=GetMessage('MAIN_MAIL_UNSUBSCRIBE_TEMPL_DEFAULT_EMPTY')?>
	<?endif;?>
</div>