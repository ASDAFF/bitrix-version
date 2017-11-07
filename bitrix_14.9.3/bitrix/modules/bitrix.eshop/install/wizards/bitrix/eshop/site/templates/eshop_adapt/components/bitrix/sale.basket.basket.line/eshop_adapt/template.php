<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
?>
<script>
	var obEshopBasket = new JSEshopBasket("<?=$this->GetFolder()?>/ajax.php", "<?=SITE_ID?>");
</script>
<span class="bx_cart_top_inline_icon"></span>
<span id="sale-basket-basket-line-container">
<?
$frame = $this->createFrame("sale-basket-basket-line-container", false)->begin();
	if (intval($arResult["NUM_PRODUCTS"])>0)
	{
		?><a class="bx_cart_top_inline_link" href="<?=$arParams["PATH_TO_BASKET"]?>"><?echo str_replace('#NUM#', intval($arResult["NUM_PRODUCTS"]), GetMessage('YOUR_CART'))?></a><?
	}
	else
	{
		?><a class="bx_cart_top_inline_link" href="<?=$arParams["PATH_TO_BASKET"]?>"><?echo GetMessage('YOUR_CART_EMPTY')?> <span id="bx_cart_num">(0)</span></a><?
	}
$frame->beginStub();
	?><a class="bx_cart_top_inline_link" href="<?=$arParams["PATH_TO_BASKET"]?>"><?echo GetMessage('YOUR_CART_EMPTY')?> <span id="bx_cart_num">(0)</span></a><?
$frame->end();
?>
</span>