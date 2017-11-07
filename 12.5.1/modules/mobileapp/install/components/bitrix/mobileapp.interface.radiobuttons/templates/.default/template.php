<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$APPLICATION->SetAdditionalCSS(CUtil::GetAdditionalFileURL('/bitrix/js/mobileapp/interface.css'));
?>

<script type="text/javascript">
	radioButtonsControl_<?=$arResult["DOM_CONTAINER_ID"]?> = new __MARadioButtonsControl({
		containerId: "<?=$arResult["DOM_CONTAINER_ID"]?>",
	});

	<?if(isset($arParams["JS_EVENT_GET_SELECTED"])):?>
		BX.addCustomEvent('<?=$arParams["JS_EVENT_GET_SELECTED"]?>',
							function (){ radioButtonsControl_<?=$arResult["DOM_CONTAINER_ID"]?>.getSelected(<?=$arResult["JS_RESULT_HANDLER"]?>);});
	<?endif;?>
</script>
<div class="order_status_infoblock" id="<?=$arResult["DOM_CONTAINER_ID"]?>">
	<?if($arResult["TITLE"]):?>
		<div class="order_acceptpay_infoblock_title"><?=$arResult["TITLE"]?></div>
	<?endif;?>
	<ul>
		<?foreach ($arParams["ITEMS"] as $id => $text):?>
			<li>
				<div id="div_<?=$id?>" class="order_status_li_container<?=($id == $arResult["SELECTED"] ? ' checked' : '')?>">
					<table>
						<tr>
							<td>
								<span class="inputradio">
									<input type="radio" id="<?=$id?>" name="<?=$arResult["RADIO_NAME"]?>"<?=($id == $arResult["SELECTED"] ? ' checked' : '')?>>
								</span>
							</td>
							<td><label for="<?=$id?>"><span><?=$text?></span></label></td>
						</tr>
					</table>
				</div>
			</li>
			<script type="text/javascript">
				radioButtonsControl_<?=$arResult["DOM_CONTAINER_ID"]?>.makeFastButton("<?=$id?>");
			</script>
		<?endforeach;?>
	</ul>
</div>
<script type="text/javascript">
	BX.ready(function(){ radioButtonsControl_<?=$arResult["DOM_CONTAINER_ID"]?>.init(); });
</script>
