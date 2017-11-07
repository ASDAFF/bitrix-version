<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/*if (!empty($arResult["PROPERTIES"]["TITLE"]["VALUE"]))
	$APPLICATION->SetTitle($arResult["PROPERTIES"]["TITLE"]["VALUE"]);
else
	$APPLICATION->SetTitle($arResult["NAME"]);
if (!empty($arResult["PROPERTIES"]["HEADER1"]["VALUE"]))
	$APPLICATION->SetPageProperty("ADDITIONAL_TITLE", $arResult["PROPERTIES"]["HEADER1"]["VALUE"]);
else
	$APPLICATION->SetPageProperty("ADDITIONAL_TITLE", $arResult["NAME"]);
if (!empty($arResult["PROPERTIES"]["KEYWORDS"]["VALUE"]))
	$APPLICATION->SetPageProperty("keywords", $arResult["PROPERTIES"]["KEYWORDS"]["VALUE"]);
if (!empty($arResult["PROPERTIES"]["META_DESCRIPTION"]["VALUE"]))
	$APPLICATION->SetPageProperty("description", $arResult["PROPERTIES"]["META_DESCRIPTION"]["VALUE"]); */
	
__IncludeLang($_SERVER["DOCUMENT_ROOT"].$templateFolder."/lang/".LANGUAGE_ID."/template.php");

$APPLICATION->AddHeadScript('/bitrix/templates/'.SITE_TEMPLATE_ID.'/js/fancybox/jquery.fancybox-1.3.1.pack.js');
$APPLICATION->SetAdditionalCSS('/bitrix/templates/'.SITE_TEMPLATE_ID.'/js/fancybox/jquery.fancybox-1.3.1.css');

$arPropertyRecommend = array();
unset($arResult['DISPLAY_PROPERTIES']["MORE_PHOTO"]);
if (COption::GetOptionString("eshop", "catalogDetailDescr", "list", SITE_ID) == "tabs")
{
?>
<div class="tabsblock">
	<div class="tabs">
		<?if ($arResult["DETAIL_TEXT"]):?>
		<a href="#" id="tab1"><span><?=GetMessage("CATALOG_FULL_DESC")?></span><span class="clr"></span></a>
		<?endif?>
		<?if (is_array($arResult['DISPLAY_PROPERTIES']) && count($arResult['DISPLAY_PROPERTIES']) > 0):
		$arPropertyRecommend = $arResult["DISPLAY_PROPERTIES"]["RECOMMEND"];
		unset($arResult["DISPLAY_PROPERTIES"]["RECOMMEND"]);
		if (is_array($arResult['DISPLAY_PROPERTIES']) && count($arResult['DISPLAY_PROPERTIES']) > 0):?>
			<a href="#" id="tab2"><span><?=GetMessage("CATALOG_PROPERTIES")?></span><span class="clr"></span></a>
			<?endif?>
		<?endif?>
		<?if($arParams["USE_REVIEW"]=="Y" && IsModuleInstalled("forum") && $arResult["ID"]):?>
		<a href="#" id="tab3"><span><?=GetMessage("CATALOG_REVIEWS")?></span><span class="clr"></span></a>
		<?endif?>
	</div>
	<div class="tabcontent">
		<?if($arResult["DETAIL_TEXT"]):?>
		<div class="cnt">
			<span  itemprop = "description"><?echo $arResult["DETAIL_TEXT"];?></span>
		</div>
		<?endif?>

		<?if (is_array($arResult['DISPLAY_PROPERTIES']) && count($arResult['DISPLAY_PROPERTIES']) > 0):?>
		<div class="cnt">
			<ul class="options lsnn">
				<?foreach($arResult["DISPLAY_PROPERTIES"] as $pid=>$arProperty):?>
				<li>
					<span><?=$arProperty["NAME"]?>:</span><b><?
					if(is_array($arProperty["DISPLAY_VALUE"])):
						echo implode("&nbsp;/&nbsp;", $arProperty["DISPLAY_VALUE"]);
					elseif($pid=="MANUAL"):
						?><a href="<?=$arProperty["VALUE"]?>"><?=GetMessage("CATALOG_DOWNLOAD")?></a><?
					else:
						echo $arProperty["DISPLAY_VALUE"];?>
						<?endif?></b>
				</li>
				<?endforeach?>
			</ul>
		</div>
		<?endif?>
		<?if($arParams["USE_REVIEW"]=="Y" && IsModuleInstalled("forum") && $arResult["ID"]):?>
		<div class="cnt">
			<?$APPLICATION->IncludeComponent(
			"bitrix:forum.topic.reviews",
			"",
			Array(
				"CACHE_TYPE" => $arParams["CACHE_TYPE"],
				"CACHE_TIME" => $arParams["CACHE_TIME"],
				"MESSAGES_PER_PAGE" => $arParams["MESSAGES_PER_PAGE"],
				"USE_CAPTCHA" => $arParams["USE_CAPTCHA"],
				"FORUM_ID" => $arParams["FORUM_ID"],
				"ELEMENT_ID" => $arResult["ID"],
				"IBLOCK_ID" => $arParams["IBLOCK_ID"],
				"AJAX_POST" => $arParams["REVIEW_AJAX_POST"],
				"SHOW_RATING" => "N",
				"SHOW_MINIMIZED" => "Y",
			),
			false
		);?>
		</div>
		<?endif?>
	</div>
</div>
<script type="text/javascript">
	$(".tabs a:first").addClass("active");
	$(".tabcontent .cnt:first").addClass("active");
</script>
<?
}
else
{
?>
	<?if($arResult["DETAIL_TEXT"]):?>
	<h3><?=GetMessage("CATALOG_FULL_DESC")?></h3>
	<span  itemprop = "description"><?echo $arResult["DETAIL_TEXT"];?></span>
	<?endif?>
<?
	if (is_array($arResult['DISPLAY_PROPERTIES']) && count($arResult['DISPLAY_PROPERTIES']) > 0)
	{
		$arPropertyRecommend = $arResult["DISPLAY_PROPERTIES"]["RECOMMEND"];
		unset($arResult["DISPLAY_PROPERTIES"]["RECOMMEND"]);
		if (is_array($arResult['DISPLAY_PROPERTIES']) && count($arResult['DISPLAY_PROPERTIES']) > 0):?>
		<h3><?=GetMessage("CATALOG_PROPERTIES")?></h3>
		<ul class="options lsnn">
			<?foreach($arResult["DISPLAY_PROPERTIES"] as $pid=>$arProperty):?>
			<li>
				<span><?=$arProperty["NAME"]?>:</span><b><?
				if(is_array($arProperty["DISPLAY_VALUE"])):
					echo implode("&nbsp;/&nbsp;", $arProperty["DISPLAY_VALUE"]);
				elseif($pid=="MANUAL"):
					?><a href="<?=$arProperty["VALUE"]?>"><?=GetMessage("CATALOG_DOWNLOAD")?></a><?
				else:
					echo $arProperty["DISPLAY_VALUE"];?>
					<?endif?></b>
			</li>
			<?endforeach?>
		</ul>
		<?endif?>
	<?
	}

	if($arParams["USE_REVIEW"]=="Y" && IsModuleInstalled("forum") && $arResult["ID"]):?>
	<h3><?=GetMessage("CATALOG_REVIEWS")?></h3>
	<?$APPLICATION->IncludeComponent(
			"bitrix:forum.topic.reviews",
			"",
			Array(
				"CACHE_TYPE" => $arParams["CACHE_TYPE"],
				"CACHE_TIME" => $arParams["CACHE_TIME"],
				"MESSAGES_PER_PAGE" => $arParams["MESSAGES_PER_PAGE"],
				"USE_CAPTCHA" => $arParams["USE_CAPTCHA"],
				"FORUM_ID" => $arParams["FORUM_ID"],
				"ELEMENT_ID" => $arResult["ID"],
				"IBLOCK_ID" => $arParams["IBLOCK_ID"],
				"AJAX_POST" => $arParams["REVIEW_AJAX_POST"],
				"SHOW_RATING" => "N",
				"SHOW_MINIMIZED" => "Y",
			),
			false
		);?>
	<?endif;
}
?>
<!-- recommend -->
<?if(count($arPropertyRecommend["DISPLAY_VALUE"]) > 0):?>
<div class="catalog-detail-recommends">
	<h3><?=$arPropertyRecommend["NAME"]?></h3>
	<div class="catalog-detail-recommend">
		<?
		global $arRecPrFilter;
		$arRecPrFilter["ID"] = $arPropertyRecommend["VALUE"];
		$APPLICATION->IncludeComponent("bitrix:eshop.catalog.top", "recommend", array(
				"IBLOCK_TYPE" => "",
				"IBLOCK_ID" => $arParams["IBLOCK_ID"],
				"ELEMENT_SORT_FIELD" => "sort",
				"ELEMENT_SORT_ORDER" => "desc",
				"ELEMENT_COUNT" => $arParams["ELEMENT_COUNT"],
				//"LINE_ELEMENT_COUNT" => $arParams["LINE_ELEMENT_COUNT"],
				"BASKET_URL" => $arParams["BASKET_URL"],
				"ACTION_VARIABLE" => $arParams["ACTION_VARIABLE"],
				"PRODUCT_ID_VARIABLE" => $arParams["PRODUCT_ID_VARIABLE"],
				"CACHE_TYPE" => $arParams["CACHE_TYPE"],
				"CACHE_TIME" => $arParams["CACHE_TIME"],
				"DISPLAY_COMPARE" => "N",
				"PRICE_CODE" => $arParams["PRICE_CODE"],
				"USE_PRICE_COUNT" => $arParams["USE_PRICE_COUNT"],
				"SHOW_PRICE_COUNT" => $arParams["SHOW_PRICE_COUNT"],
				"PRICE_VAT_INCLUDE" => $arParams["PRICE_VAT_INCLUDE"],
				"FILTER_NAME" => "arRecPrFilter",
				"DISPLAY_IMG_WIDTH"	 =>	$arParams["DISPLAY_IMG_WIDTH"],
				"DISPLAY_IMG_HEIGHT" =>	$arParams["DISPLAY_IMG_HEIGHT"],
				"SHARPEN" => $arParams["SHARPEN"],
				"ELEMENT_COUNT" => 30,
				"CONVERT_CURRENCY" => $arParams['CONVERT_CURRENCY'],
				"CURRENCY_ID" => $arParams['CURRENCY_ID'],
			),
			false
		);
		?>
	</div>
</div>
<?endif;?>

<?if (CModule::IncludeModule('sale'))
{
	$dbBasketItems = CSaleBasket::GetList(
		array(
			"ID" => "ASC"
		),
		array(
			"PRODUCT_ID" => $arResult['ID'],
			"FUSER_ID" => CSaleBasket::GetBasketUserID(),
			"LID" => SITE_ID,
			"ORDER_ID" => "NULL",
		),
		false,
		false,
		array()
	);

	if ($arBasket = $dbBasketItems->Fetch())
	{
		$notifyOption = COption::GetOptionString("sale", "subscribe_prod", "");
		$arNotify = array();
		if (strlen($notifyOption) > 0)
			$arNotify = unserialize($notifyOption);
		if($arBasket["DELAY"] == "Y")
			echo "<script type=\"text/javascript\">$(function() {disableAddToCart('catalog_add2cart_link', 'detail', '".GetMessage("CATALOG_IN_CART_DELAY")."')});</script>\r\n";
		elseif ($arNotify[SITE_ID]['use'] == 'Y' && $arBasket["SUBSCRIBE"] == "Y")
			echo "<script type=\"text/javascript\">$(function() {disableAddToSubscribe('catalog_add2cart_link', '".GetMessage("CATALOG_IN_SUBSCRIBE")."')});</script>\r\n";
		elseif($arResult["CAN_BUY"] == "N"  && $arBasket["SUBSCRIBE"] == "N")
			echo "<script type=\"text/javascript\">$(function() {disableAddToCart('catalog_add2cart_link', 'detail', '".GetMessage("CATALOG_IN_CART")."')});</script>\r\n";
	}
}

if ($arParams['USE_COMPARE'])
{
	if (isset(
		$_SESSION[$arParams["COMPARE_NAME"]][$arParams["IBLOCK_ID"]]["ITEMS"][$arResult['ID']]
	))
	{
		echo '<script type="text/javascript">$(function(){disableAddToCompare(BX(\'catalog_add2compare_link\'), \'detail\', \''.GetMessage("CATALOG_IN_COMPARE").'\', \'\');})</script>';
	}
}
?>