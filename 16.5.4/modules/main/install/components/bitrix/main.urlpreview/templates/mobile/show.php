<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/**
 * Global variables
 * @var array $arResult
 */
?>
<div class="urlpreview-mobile">
	<span class="urlpreview-mobile-title">
		<a href="<?= $arResult['METADATA']['URL']?>" target="_blank">
			<span class="urlpreview-mobile-title-name"><?=$arResult['METADATA']['TITLE']?></span>
		</a>
	</span>
	<?if($arResult['METADATA']['DESCRIPTION']):?>
		<span class="urlpreview-mobile-description"><?=$arResult['METADATA']['DESCRIPTION']?></span>
	<?endif?>
	<?if($arResult['METADATA']['IMAGE']):?>
		<a href="<?= $arResult['METADATA']['URL']?>" target="_blank">
			<span class="urlpreview-mobile-image">
				<img src="<?= $arResult['METADATA']['IMAGE']?>" onerror="this.style.display='none';" class="urlpreview-mobile-image-img">
			</span>
		</a>
	<?endif?>
</div>
