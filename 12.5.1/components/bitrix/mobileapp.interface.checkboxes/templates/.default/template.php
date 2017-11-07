<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$APPLICATION->SetAdditionalCSS(CUtil::GetAdditionalFileURL('/bitrix/js/mobileapp/interface.css'));
?>
<script type="text/javascript">
	checkboxControl_<?=$arResult["DOM_CONTAINER_ID"]?> = new __MACheckBoxControl({
		containerId: "<?=$arResult["DOM_CONTAINER_ID"]?>",
		resultCallback: <?=(isset($arParams["JS_RESULT_HANDLER"]) ? '"'.$arParams["JS_RESULT_HANDLER"].'"' : 'false')?>
	});

	<?if(isset($arParams["JS_EVENT_TAKE_CHECKBOXES_VALUES"])):?>
		BX.addCustomEvent('<?=$arParams["JS_EVENT_TAKE_CHECKBOXES_VALUES"]?>',
							function (){ checkboxControl_<?=$arResult["DOM_CONTAINER_ID"]?>.getChecked();});
	<?endif;?>
</script>

<div id="<?=$arResult["DOM_CONTAINER_ID"]?>" class="order_acceptpay_infoblock">
	<?if($arResult["TITLE"]):?>
		<div class="order_acceptpay_infoblock_title"><?=$arResult["TITLE"]?></div>
	<?endif;?>
	<?foreach ($arParams["ITEMS"] as $id => $text):?>
	<ul>
		<li>
			<div id="div_<?=$id?>" class="order_acceptpay_li_container<?=(in_array($id, $arResult["CHECKED"]) ? ' checked' : '')?>">
				<table>
					<tr>
						<td>
							<span class="inputcheckbox">
								<input type="checkbox" id="<?=$id?>" name="<?=$id?>"<?=(in_array($id, $arResult["CHECKED"]) ? ' checked' : '')?>>
							</span>
						</td>
						<td><label for="<?=$id?>"><span><?=$text?></span></label></td>
					</tr>
				</table>
			</div>
		</li>
	</ul>
	<script type="text/javascript">
		checkboxControl_<?=$arResult["DOM_CONTAINER_ID"]?>.makeFastButton("<?=$id?>");
	</script>
	<?endforeach;?>
</div>