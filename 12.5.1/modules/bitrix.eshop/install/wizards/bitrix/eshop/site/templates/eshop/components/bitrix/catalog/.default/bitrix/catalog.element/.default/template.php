<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();       
if (is_array($arResult['DETAIL_PICTURE_280']) || count($arResult["MORE_PHOTO"])>0):?>

<script type="text/javascript">
$(document).ready(function() {
	$('.catalog-detail-images').fancybox({
		'transitionIn': 'elastic',
		'transitionOut': 'elastic',
		'speedIn': 600,
		'speedOut': 200,
		'overlayShow': false,
		'cyclic' : true,
		'padding': 20,
		'titlePosition': 'over',
		'onComplete': function() {
		$("#fancybox-title").css({ 'top': '100%', 'bottom': 'auto' });
		}
	});
});
</script>
<?endif;?>
<?
$numPrices = count($arParams["PRICE_CODE"]);
$sticker = "";
if (array_key_exists("PROPERTIES", $arResult) && is_array($arResult["PROPERTIES"]))
{

	foreach (Array("SPECIALOFFER", "NEWPRODUCT", "SALELEADER") as $propertyCode)
		if (array_key_exists($propertyCode, $arResult["PROPERTIES"]) && intval($arResult["PROPERTIES"][$propertyCode]["PROPERTY_VALUE_ID"]) > 0)
		{
			$sticker .= "<div class='salegal'>".$arResult["PROPERTIES"][$propertyCode]["NAME"]."</div>";
			break;
		}
}

$notifyOption = COption::GetOptionString("sale", "subscribe_prod", "");
$arNotify = unserialize($notifyOption);
?>
<div itemscope itemtype = "http://schema.org/Product" class="R2D2">
	<table>
		<tr>
		<?if(is_array($arResult["PREVIEW_PICTURE"]) || is_array($arResult["DETAIL_PICTURE"])):?>
			<?if(count($arResult["MORE_PHOTO"])>0):?>
				<td rowspan="2" style="width:320px;height: 430px;vertical-align:top;position:relative;">
					<div style="position:relative;">
						<div id="basic" class="svwp" style="float:left">
							<ul>
								<?if(is_array($arResult["DETAIL_PICTURE_280"])):?>
									<li><a rel="catalog-detail-images" class="catalog-detail-images" href="<?=$arResult['DETAIL_PICTURE']['SRC']?>" title="<?=(strlen($arResult["DETAIL_PICTURE"]["DESCRIPTION"]) > 0 ? $arResult["DETAIL_PICTURE"]["DESCRIPTION"] : $arResult["NAME"])?>"><img itemprop="image" class="item_img"  src="<?=$arResult["DETAIL_PICTURE_280"]["SRC"]?>"  alt="<?=$arResult["NAME"]?>" title="<?=$arResult["NAME"]?>" /></a></li>
								<?elseif(is_array($arResult["DETAIL_PICTURE"])):?>
									<li><a rel="catalog-detail-images" class="catalog-detail-images" href="<?=$arResult['DETAIL_PICTURE']['SRC']?>" title="<?=(strlen($arResult["DETAIL_PICTURE"]["DESCRIPTION"]) > 0 ? $arResult["DETAIL_PICTURE"]["DESCRIPTION"] : $arResult["NAME"])?>"><img width="280" itemprop="image" src="<?=$arResult["DETAIL_PICTURE"]["SRC"]?>" alt="<?=$arResult["NAME"]?>" title="<?=$arResult["NAME"]?>" /></a></li>
								<?elseif(is_array($arResult["PREVIEW_PICTURE"])):?>
									<li><a rel="catalog-detail-images" class="catalog-detail-images" href="<?=$arResult['PREVIEW_PICTURE']['SRC']?>" title="<?=(strlen($arResult["PREVIEW_PICTURE"]["DESCRIPTION"]) > 0 ? $arResult["PREVIEW_PICTURE"]["DESCRIPTION"] : $arResult["NAME"])?>"><img width="280" itemprop="image" src="<?=$arResult["PREVIEW_PICTURE"]["SRC"]?>" alt="<?=$arResult["NAME"]?>" title="<?=$arResult["NAME"]?>" /></a></li>
								<?endif?>
								<?foreach($arResult["MORE_PHOTO"] as $PHOTO):?>
									<li><a rel="catalog-detail-images" class="catalog-detail-images" href="<?=$PHOTO['SRC']?>" title="<?=(strlen($PHOTO["DESCRIPTION"]) > 0 ? $PHOTO["DESCRIPTION"] : $PHOTO["NAME"])?>"><img  src="<?=$PHOTO["SRC"]?>"  alt="<?=$arResult["NAME"]?>" title="<?=$arResult["NAME"]?>" /></a></li>
								<?endforeach?>

							</ul>
						</div>
						<?if(!(is_array($arResult["OFFERS"]) && !empty($arResult["OFFERS"])) && !$arResult["CAN_BUY"]
							|| is_array($arResult["OFFERS"]) && !empty($arResult["OFFERS"]) && $arResult["ALL_SKU_NOT_AVAILABLE"]):?>
							<div class="badge notavailable"><?=GetMessage("CATALOG_NOT_AVAILABLE2")?></div>
						<?elseif (strlen($sticker)>0):?>
							<?=$sticker?>
						<?endif?>
					</div>
				</td>
			<?else:?>
				<td rowspan="2" style="width:320px;vertical-align:top;position:relative;">
					<div style="position:relative;">
						<div class="element-one-picture">
							<?if(is_array($arResult["DETAIL_PICTURE_280"])):?>
								<a rel="catalog-detail-images" class="catalog-detail-images" href="<?=$arResult['DETAIL_PICTURE']['SRC']?>" title="<?=(strlen($arResult["DETAIL_PICTURE"]["DESCRIPTION"]) > 0 ? $arResult["DETAIL_PICTURE"]["DESCRIPTION"] : $arResult["NAME"])?>"><img itemprop="image" class="item_img"  src="<?=$arResult["DETAIL_PICTURE_280"]["SRC"]?>"  alt="<?=$arResult["NAME"]?>" title="<?=$arResult["NAME"]?>" /></a>
							<?elseif(is_array($arResult["DETAIL_PICTURE"])):?>
								<a rel="catalog-detail-images" class="catalog-detail-images" href="<?=$arResult['DETAIL_PICTURE']['SRC']?>" title="<?=(strlen($arResult["DETAIL_PICTURE"]["DESCRIPTION"]) > 0 ? $arResult["DETAIL_PICTURE"]["DESCRIPTION"] : $arResult["NAME"])?>"><img width="280" itemprop="image" src="<?=$arResult["DETAIL_PICTURE"]["SRC"]?>" alt="<?=$arResult["NAME"]?>" title="<?=$arResult["NAME"]?>" /></a>
							<?elseif(is_array($arResult["PREVIEW_PICTURE"])):?>
								<a rel="catalog-detail-images" class="catalog-detail-images" href="<?=$arResult['PREVIEW_PICTURE']['SRC']?>" title="<?=(strlen($arResult["PREVIEW_PICTURE"]["DESCRIPTION"]) > 0 ? $arResult["PREVIEW_PICTURE"]["DESCRIPTION"] : $arResult["NAME"])?>"><img width="280" itemprop="image" src="<?=$arResult["PREVIEW_PICTURE"]["SRC"]?>" alt="<?=$arResult["NAME"]?>" title="<?=$arResult["NAME"]?>" /></a>
							<?endif?>
						</div>
						<?if(!(is_array($arResult["OFFERS"]) && !empty($arResult["OFFERS"])) && !$arResult["CAN_BUY"]
							|| is_array($arResult["OFFERS"]) && !empty($arResult["OFFERS"]) && $arResult["ALL_SKU_NOT_AVAILABLE"]):?>
							<div class="badge notavailable"><?=GetMessage("CATALOG_NOT_AVAILABLE2")?></div>
						<?elseif (strlen($sticker)>0):?>
							<?=$sticker?>
						<?endif?>
					</div>
				</td>
			<?endif;?>
		<?else:?>
			<td rowspan="2" style="width:320px;vertical-align:top;position:relative;">
				<div style="position:relative;">
					<div class="element-one-picture">
						<div class="no-photo-div-big" style="height:130px;"></div>
					</div>
				</div>
			</td>
		<?endif;?>
			<td class="iteminfo">
				<h2 class="posttitle"><a class="item_title" href="<?=$arResult["DETAIL_PAGE_URL"]?>" title="<?=$arResult["NAME"]?>"><span itemprop="name"><?=$arResult["NAME"]?></span></a></h2>
				<p><?=strip_tags($arResult["~PREVIEW_TEXT"])?></p>
				<?if(is_array($arResult["OFFERS"]) && !empty($arResult["OFFERS"]))
				{  
					$curSkuView = COption::GetOptionString("eshop", "catalogDetailSku", "select", SITE_ID);
					?>
					<div  id="currentOfferPrice"></div>
					<div class="price item_price" id="minOfferPrice">
						<?if (count($arResult["OFFERS"]) > 1) echo GetMessage("CATALOG_FROM");?>
						<?=$arResult["MIN_PRODUCT_OFFER_PRICE_PRINT"]?>
					</div>
					<?if ($curSkuView == "select"):?>
						<form name="buy_form">
							<table class="options" id="sku_selectors">
								<tr>
									<td colspan="2" class="fwb"><?=GetMessage("CARALOG_OFFERS_PROPS")?></td>
								</tr>
	
							</table>
						</form>
					<?endif?>
				<?
				}
				else
				{
					foreach($arResult["PRICES"] as $code=>$arPrice):?>
						<?if($arPrice["CAN_ACCESS"]):?>
							<?if ($numPrices>1):?><br><?=$arResult["CAT_PRICES"][$code]["TITLE"];?>:<?endif?>
							<?if($arPrice["DISCOUNT_VALUE"] < $arPrice["VALUE"]):?>
								<div class="discount-price item_price"><?=$arPrice["PRINT_DISCOUNT_VALUE"]?></div>
								<div class="old-price item_old_price"><?=$arPrice["PRINT_VALUE"]?></div>
							<?else:?>
								<div class="price item_price"><?=$arPrice["PRINT_VALUE"]?></div>
							<?endif?>
						<?endif;?>
					<?endforeach;
				}
				?>
			</td>
		</tr>
		<tr>
			<td style="vertical-align:bottom;padding-left:30px;padding-bottom: 38px;">
				<br/>
				<?if(is_array($arResult["OFFERS"]) && !empty($arResult["OFFERS"])):?>
					<br><div id="element_buy_button"></div>
					<?if ($arParams["USE_COMPARE"] == "Y"):?>
						<div id="element_compare_button"></div>
					<?endif?>
				<?else:?>
					<?if($arResult["CAN_BUY"]):?>
						<a href="<?echo $arResult["ADD_URL"]?>" rel="nofollow" class="bt3 addtoCart" onclick="return addToCart(this, 'detail', '<?=GetMessage("CATALOG_IN_CART")?>', 'cart');" id="catalog_add2cart_link"><span class="cartbuy"></span> <?=GetMessage("CATALOG_BUY")?></a><br/><br/><br/>
					<?elseif ($arNotify[SITE_ID]['use'] == 'Y'):?>
						<?if ($USER->IsAuthorized()):?>
							<noindex><a href="<?echo $arResult["SUBSCRIBE_URL"]?>" rel="nofollow" onclick="return addToSubscribe(this, '<?=GetMessage("CATALOG_IN_SUBSCRIBE")?>');" class="bt2" id="catalog_add2cart_link"><span></span><?echo GetMessage("CATALOG_SUBSCRIBE")?></a></noindex><br/><br/><br/>
						<?else:?>
							<noindex><a href="javascript:void(0)" rel="nofollow" onclick="showAuthForSubscribe(this, <?=$arResult["ID"]?>, '<?echo $arResult["SUBSCRIBE_URL"]?>')" class="bt2"><span></span><?echo GetMessage("CATALOG_SUBSCRIBE")?></a></noindex><br/><br/><br/>
						<?endif;?>
					<?endif?>
					<?if ($arParams["USE_COMPARE"] == "Y"):?>
						<a href="<?=$arResult["COMPARE_URL"]?>" rel="nofollow" class="bt2 addtoCompare" onclick="return addToCompare(this, 'detail', '<?=GetMessage("CATALOG_IN_COMPARE")?>');" id="catalog_add2compare_link"><?=GetMessage("CT_BCE_CATALOG_COMPARE")?></a>
					<?endif?>
				<?endif?>
			</td>
		</tr>
	</table>
	<?if (is_array($arResult["OFFERS"]) && !empty($arResult["OFFERS"]) && $curSkuView == "list"):?>
		<table class="equipment" rules="rows">
			<thead>
				<tr>
					<?foreach ($arResult["SKU_PROPERTIES"] as $key => $arProp):?>
						<td><?=$arProp["NAME"]?></td>
					<?endforeach?>
					<td><?=GetMessage("CATALOG_PRICE")?></td>
					<td></td>
					<?if ($arParams["USE_COMPARE"] == "Y"):?>
					<td></td>
					<?endif?>
				</tr>
			</thead>
			<tbody>
				<?
				$numProps = count($arResult["SKU_PROPERTIES"]);
				foreach ($arResult["SKU_ELEMENTS"] as $key => $arSKU)
				{
				?>
					<tr>
						<?
						for ($i=0; $i<$numProps; $i++)
						{
						?>
						<td>
							<?=$arSKU[$i]?>
						</td>
						<?
						}
						?>
						<td>
							<?foreach ($arSKU["PRICES"] as $code => $arPrice):?>
								<?if ($numPrices>1):?><br><?=$arPrice["TITLE"];?>:<?endif?>
								<?if (intval($arPrice["DISCOUNT_PRICE"]) > 0 && $arPrice["PRICE"] > 0):?>
									<span class="discount-price"><?=$arPrice["DISCOUNT_PRICE"]?></span> <span class="old-price"><?=$arPrice["PRICE"]?></span>
								<?else:?>
									<?=$arPrice["PRICE"]?>
								<?endif?>
							<?endforeach?>
						</td>
						<?if ($arSKU["CAN_BUY"]):?>
							<td><a href="<?=$arSKU["ADD_URL"]?>" onclick="return addToCart(this, 'detail', '<?=GetMessage("CATALOG_IN_CART")?>', 'noButton');" id="catalog_add2cart_link_ofrs_<?=$arSKU["ID"]?>"><?=GetMessage("CATALOG_BUY")?></a></td>
						<?elseif ( $arNotify[SITE_ID]['use'] == 'Y'):?>
							<?if ($USER->IsAuthorized()):?>
							<td><noindex>
								<a href="<?echo $arSKU["SUBSCRIBE_URL"]?>" rel="nofollow" onclick="return addToSubscribe(this, '<?=GetMessage("CATALOG_IN_SUBSCRIBE")?>');" id="catalog_add2cart_link_ofrs_<?=$arSKU["ID"]?>"><?echo GetMessage("CATALOG_SUBSCRIBE")?></a>
								<sup class="notavailable"><?=GetMessage("CATALOG_NOT_AVAILABLE2")?></sup>
							</noindex></td>
							<?else:?>
							<td><noindex>
								<a href="javascript:void(0)" rel="nofollow" onclick="showAuthForSubscribe(this, <?=$arSKU["ID"]?>, '<?echo $arSKU["SUBSCRIBE_URL"]?>')" ><?echo GetMessage("CATALOG_SUBSCRIBE")?></a>
								<sup class="notavailable"><?=GetMessage("CATALOG_NOT_AVAILABLE2")?></sup>
							</noindex></td>
							<?endif?>
						<?endif?>
						<?if ($arParams["USE_COMPARE"] == "Y"):?>
							<td><a href="<?=$arSKU["COMPARE_URL"]?>" onclick="return addToCompare(this, 'detail', '<?=GetMessage("CATALOG_IN_COMPARE")?>');" id="catalog_add2compare_link_ofrs_<?=$arSKU["ID"]?>"><?=GetMessage("CATALOG_COMPARE")?></a></td>
						<?endif?>
					</tr>
				<?
				}
				?>
			</tbody>
		</table>
	<?endif?>

</div>

<script type="text/javascript">
	/*$(document).ready(function() {
		$(window).bind("load", function() {
			$("#basic").slideViewerPro({});
			$(window).bind("load", function() {
				$("div#basic").slideViewerPro();
			});
		});
	});  */
</script>

<script type="text/javascript">
	$("#basic").slideViewerPro({});
	var mess = <?=CUtil::PhpToJsObject($arResult["POPUP_MESS"])?>;
	BX.message(mess);
	<?if (!empty($arResult["SKU_PROPERTIES"])):?>
		var arProperties = <?=CUtil::PhpToJsObject($arResult["SKU_PROPERTIES"])?>,
			arSKU = <?=CUtil::PhpToJsObject($arResult["SKU_ELEMENTS"])?>,
			properties_num = arProperties.length;
		var lastPropCode = arProperties[properties_num-1].CODE;

		BX.ready(function(){
			buildSelect('buy_form', 'sku_selectors', 0, arSKU, arProperties, "detail", "cart");
			addHtml(lastPropCode, arSKU, "detail", "clear_cart");
		});
	<?endif?>

</script>