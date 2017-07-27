<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<div class="ygoda">
	<div class="ygoda-title">
		<?=$arResult["NOT_FORMATED"]["@attributes"]["country"]?> | <?=$arResult["NOT_FORMATED"]["@attributes"]["city"]?>
	</div>
	<div class="ygoda-type"><?=$arResult["NOT_FORMATED"]["fact"]["weather_type_short"]?></div>
	<div class="ygoda-temperature">
		<?if($arResult["NOT_FORMATED"]["fact"]["temperature"]>0):?>
			<span class="ygoda-hot"><?=GetMessage("IMYIE_WHEATHER_PLUS")?><?=$arResult["NOT_FORMATED"]["fact"]["temperature"]?></span>
		<?elseif($arResult["NOT_FORMATED"]["fact"]["temperature"]==0):?>
			<span class="ygoda-norm"><?=$arResult["NOT_FORMATED"]["fact"]["temperature"]?></span>
		<?else:?>
			<span class="ygoda-cold"><?=$arResult["NOT_FORMATED"]["fact"]["temperature"]?></span>
		<?endif;?>
	</div>
	<div class="ygoda-2temperature">
		<?=GetMessage("IMYIE_WHEATHER_NIGHT")?> <?if($arResult["NOT_FORMATED"]["informer"]["temperature"][0]>0):?>
			<?=GetMessage("IMYIE_WHEATHER_PLUS")?><?=$arResult["NOT_FORMATED"]["informer"]["temperature"][0]?>
		<?elseif($arResult["NOT_FORMATED"]["informer"]["temperature"][0]==0):?>
			<?=$arResult["NOT_FORMATED"]["informer"]["temperature"][0]?>
		<?else:?>
			<?=$arResult["NOT_FORMATED"]["informer"]["temperature"][0]?>
		<?endif;?><br />
		<?=GetMessage("IMYIE_WHEATHER_TOMORROW")?> <?if($arResult["NOT_FORMATED"]["informer"]["temperature"][1]>0):?>
			<?=GetMessage("IMYIE_WHEATHER_PLUS")?><?=$arResult["NOT_FORMATED"]["informer"]["temperature"][1]?>
		<?elseif($arResult["NOT_FORMATED"]["informer"]["temperature"][1]==0):?>
			<?=$arResult["NOT_FORMATED"]["informer"]["temperature"][1]?>
		<?else:?>
			<?=$arResult["NOT_FORMATED"]["informer"]["temperature"][1]?>
		<?endif;?>
	</div>
</div>