<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?             
if(!empty($arResult["CATEGORIES"])):?>
	<table class="title-search-result">
		<?foreach($arResult["CATEGORIES"] as $category_id => $arCategory):?>
			<tr class="title-search-line">
				<th class="title-search-separator">&nbsp;</th>
				<td class="title-search-separator">&nbsp;</td>
			</tr>
			<?foreach($arCategory["ITEMS"] as $i => $arItem):?>
			<tr>
				<?if($i == 0):?>
					<th>&nbsp;<?echo $arCategory["TITLE"]?></th>
				<?else:?>
					<th>&nbsp;</th>
				<?endif?>

				<?if($category_id === "all"):?>
					<td class="title-search-all"><a href="<?echo $arItem["URL"]?>"><?echo $arItem["NAME"]?></td>
				<?elseif(isset($arResult["ELEMENTS"][$arItem["ITEM_ID"]])):
					$arElement = $arResult["ELEMENTS"][$arItem["ITEM_ID"]];
					?>
					<td class="title-search-item"><?
						if (is_array($arElement["PICTURE"])):?>
							<div style="inline-block"><img align="left" src="<?echo $arElement["PICTURE"]["src"]?>" width="<?echo $arElement["PICTURE"]["width"]?>" height="<?echo $arElement["PICTURE"]["height"]?>"></div>
						<?endif;?>
						<a href="<?echo $arItem["URL"]?>"><?echo $arItem["NAME"]?></a>
						<!--<p class="title-search-preview"><?echo $arElement["PREVIEW_TEXT"];?></p>-->
						<br/>
						<?foreach($arElement["PRICES"] as $code=>$arPrice):?>
							<?if($arPrice["CAN_ACCESS"]):?>
								<span class="title-search-price"><?=$arResult["PRICES"][$code]["TITLE"];?>:&nbsp;&nbsp;
								<?if($arPrice["DISCOUNT_VALUE"] < $arPrice["VALUE"]):?>
									<span class="catalog-price discount-price"><?=$arPrice["PRINT_DISCOUNT_VALUE"]?></span>
									<span class="price old-price"><?=$arPrice["PRINT_VALUE"]?></span>
								<?else:?>
									<span class="catalog-price price"><?=$arPrice["PRINT_VALUE"]?></span>
								<?endif;?>
								</span>
							<?endif;?>
						<?endforeach;?>
					</td>
				<?elseif(isset($arItem["ICON"])):?>
					<td class="title-search-item"><a href="<?echo $arItem["URL"]?>"><?echo $arItem["NAME"]?></td>
				<?else:?>
					<td class="title-search-more"><a href="<?echo $arItem["URL"]?>"><?echo $arItem["NAME"]?></td>
				<?endif;?>
			</tr>
			<?endforeach;?>
		<?endforeach;?>
	</table>
<?endif;

?>
<script type="text/javascript">
	var offsetSearch = $("#search").offset();
	var widthSearch = $("#search").width();
	$(".searchtd").css({"z-index":"300","position":"relative"})
	$(".title-search-result").css({"left":3+offsetSearch.left+"px","top":40+offsetSearch.top+"px","width":widthSearch-8+"px"});
</script>