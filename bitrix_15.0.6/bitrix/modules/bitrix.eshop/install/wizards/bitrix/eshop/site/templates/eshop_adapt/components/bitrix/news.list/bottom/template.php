<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
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
$this->setFrameMode(true);

if (count($arResult["ITEMS"]) < 1)
	return;
?>

<div class="bx_inc_news_footer">
	<h4 style="font-weight: normal;"><?=GetMessage("NEWS_TITLE")?></h4>
	<ul class="bx_inc_news_footer_newslist">
		<?foreach($arResult["ITEMS"] as $arItem):?>
			<li>
				<a href="<?=$arItem["DETAIL_PAGE_URL"]?>"><?=$arItem["DISPLAY_ACTIVE_FROM"]?></a><br/>
				<?=(strlen($arItem["NAME"])> 0 ? $arItem["NAME"] : $arItem["PREVIEW_TEXT"])?>
			</li>
		<?endforeach;?>
	</ul>
	<br/>
	<a href="<?=SITE_DIR?>news/" class="bx_bt_button_type_2 bx_big bx_shadow"><?=GetMessage("SDNW_ALLNEWS")?></a>
</div>