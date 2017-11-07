<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<div class="item_list_component">
	<ul>
<?foreach($arResult["ITEMS"] as $cell=>$arElement):?>
	<li id="<?=$this->GetEditAreaId($arElement['ID']);?>" onclick="app.openNewPage('<?=$arElement["DETAIL_PAGE_URL"]?>')">
		<?
		$this->AddEditAction($arElement['ID'], $arElement['EDIT_LINK'], CIBlock::GetArrayByID($arParams["IBLOCK_ID"], "ELEMENT_EDIT"));
		$this->AddDeleteAction($arElement['ID'], $arElement['DELETE_LINK'], CIBlock::GetArrayByID($arParams["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BCS_ELEMENT_DELETE_CONFIRM')));

		$sticker = "";
		if (array_key_exists("PROPERTIES", $arElement) && is_array($arElement["PROPERTIES"]))
		{
			foreach (Array("SPECIALOFFER", "NEWPRODUCT", "SALELEADER") as $propertyCode)
				if (array_key_exists($propertyCode, $arElement["PROPERTIES"]) && intval($arElement["PROPERTIES"][$propertyCode]["PROPERTY_VALUE_ID"]) > 0)
				{
					$sticker = toLower($arElement["PROPERTIES"][$propertyCode]["NAME"]);
					break;
				}
		}
		?>
		<table>
			<tr>
				<td>
				<?if(is_array($arElement["PREVIEW_PICTURE"])):?>
					<a href="<?=$arElement["DETAIL_PAGE_URL"]?>" class="item_list_img"><span><img src="<?=$arElement["PREVIEW_PICTURE"]["SRC"]?>" alt="<?=$arElement["NAME"]?>" title="<?=$arElement["NAME"]?>" /></span></a>
				<?elseif(is_array($arElement["DETAIL_PICTURE"])):?>
					<a href="<?=$arElement["DETAIL_PAGE_URL"]?>" class="item_list_img"><span><img src="<?=$arElement["DETAIL_PICTURE"]["SRC"]?>"  alt="<?=$arElement["NAME"]?>" title="<?=$arElement["NAME"]?>" /></span></a>
				<?endif?>
				</td>
				<td class="">

					<span class="item_list_title_lable"><?=$sticker?></span>
					<div class="item_list_title">
						<a href="<?=$arElement["DETAIL_PAGE_URL"]?>"><?=$arElement["NAME"]?><?if ($sticker):?><?endif?></a>
					</div>

					<?if (is_array($arElement["DISPLAY_PROPERTIES"])):?>
					<div class="item_item_description_text">
						<ul>
							<?foreach($arElement["DISPLAY_PROPERTIES"] as $pid=>$arProperty):?>
							<li><?=$arProperty["NAME"]?>:
								<?if(is_array($arProperty["DISPLAY_VALUE"]))
									echo implode(" / ", $arProperty["DISPLAY_VALUE"]);
								else
									echo $arProperty["DISPLAY_VALUE"];?>
							</li>
							<?endforeach?>
						</ul>
					</div>
					<?endif?>
					<?if(!is_array($arElement["OFFERS"]) || empty($arElement["OFFERS"])):?>
						<?foreach($arElement["PRICES"] as $code=>$arPrice):?>
							<?if($arPrice["CAN_ACCESS"]):?>
								<?//=$arResult["PRICES"][$code]["TITLE"];?>
								<?if($arPrice["DISCOUNT_VALUE"] < $arPrice["VALUE"]):?>
									<div class="itemlist_price_container oldprice">
										<span class="item_price"><?=$arPrice["PRINT_DISCOUNT_VALUE"]?></span>
										<span class="item_price_old whsnw"><?=$arPrice["PRINT_VALUE"]?></span>
									</div>
								<?else:?>
									<div class="itemlist_price_container">
										<span class="item_price"><?=$arPrice["PRINT_VALUE"]?></span>
									</div>
								<?endif;?>
							<?endif;?>
						<?endforeach;?>
					<?endif?>






		<?/*if($arElement["CAN_BUY"]):?>
			<?if($arParams["USE_PRODUCT_QUANTITY"]):?>
				<form action="<?=POST_FORM_ACTION_URI?>" method="post" enctype="multipart/form-data">
					<div class="item_count">
						<a href="javascript:void(0)" class="count_plus" id="count_plus" ontouchstart="if (BX('item_quantity_<?=$arElement["ID"]?>').value > 1) BX('item_quantity_<?=$arElement["ID"]?>').value--;"><span></span></a>
						<input type="number" id="item_quantity_<?=$arElement["ID"]?>" name="<?echo $arParams["PRODUCT_QUANTITY_VARIABLE"]?>" value="1" size="5">
						<a href="javascript:void(0)" class="count_minus" id="count_minus" ontouchstart="BX('item_quantity_<?=$arElement["ID"]?>').value++;"><span></span></a>
					</div>
					<input type="hidden" name="<?echo $arParams["ACTION_VARIABLE"]?>" value="ADD2BASKET">
					<input type="hidden" name="<?echo $arParams["PRODUCT_ID_VARIABLE"]?>" value="<?echo $arElement["ID"]?>">
					<input type="submit" class="item_list_buy" name="<?echo $arParams["ACTION_VARIABLE"]."ADD2BASKET"?>" value="<?echo GetMessage("CATALOG_BUY")?>" onclick="
						BX.ajax({
							timeout:   30,
							method:   'POST',
							url:       '<?=POST_FORM_ACTION_URI?>',
							processData: false,
							data: {
								<?echo $arParams["ACTION_VARIABLE"]?>: 'ADD2BASKET',
								<?echo $arParams["PRODUCT_ID_VARIABLE"]?>: '<?echo $arElement["ID"]?>',
								<?echo $arParams["PRODUCT_QUANTITY_VARIABLE"]?>: this.form.elements['<?echo $arParams["PRODUCT_QUANTITY_VARIABLE"]?>'].value
							},
							onsuccess: function(reply){
							},
							onfailure: function(){
							}
						});
						return BX.PreventDefault(event);
					">
				</form>
			<?else:?>
				<noindex>
					<a href="<?echo $arElement["ADD_URL"]?>" class="item_list_buy" rel="nofollow" onclick="addItemToCart(this);"><?echo GetMessage("CATALOG_BUY")?></a>
				</noindex>
			<?endif;?>
		<?endif*/?>
				</td>
			</tr>
		</table>
	</li>
<?endforeach; // foreach($arResult["ITEMS"] as $arElement):?>
	</ul>
</div>
<?/*if($arParams["DISPLAY_BOTTOM_PAGER"]):?>
	<?=$arResult["NAV_STRING"]?>
<?endif;*/?>
<script type="text/javascript">
	app.setPageTitle({"title" : "<?=CUtil::JSEscape(htmlspecialcharsback($arResult["NAME"]))?>"});
</script>

