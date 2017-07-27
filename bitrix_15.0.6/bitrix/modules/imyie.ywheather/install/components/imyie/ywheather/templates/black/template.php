<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$tmpl = "/bitrix/components/imyie/ywheather/templates/black/";?>

<div class="ygoda-bg">
	<div class="ygoda">
		<div class="ygoda-left_block">
			<div class="ygoda-fact">
				<?=$arResult["NOT_FORMATED"]["@attributes"]["country"]?><br /><span class="ygoda-town">&#149; <?=$arResult["NOT_FORMATED"]["@attributes"]["city"]?> &#149;</span>
			</div>
			<div class="ygoda-temperature">
				<?if($arResult["NOT_FORMATED"]["fact"]["temperature"]>0):?>
					<span class="ygoda-temp ygoda-hot"><?=GetMessage("IMYIE_WHEATHER_PLUS")?><?=$arResult["NOT_FORMATED"]["fact"]["temperature"]?></span>
				<?elseif($arResult["NOT_FORMATED"]["fact"]["temperature"]==0):?>
					<span class="ygoda-temp ygoda-norm"><?=$arResult["NOT_FORMATED"]["fact"]["temperature"]?></span>
				<?else:?>
					<span class="ygoda-temp ygoda-cold"><?=$arResult["NOT_FORMATED"]["fact"]["temperature"]?></span>
				<?endif;?>
			</div>
		</div>
		<div class="ygoda-img">
			<?if($arResult["NOT_FORMATED"]["fact"]["image"]!=""):?>
				<img src="<?=$tmpl?>img/<?=$arResult["NOT_FORMATED"]["fact"]["image"]?>.png" border="0" alt="" />
			<?else:?>
				<img src="<?=$tmpl?>img/18.png" border="0" alt="" />
			<?endif;?>
		</div>
		<div class="ygoda-moreinfo">
			<span class="ygoda-weather_type"><?=$arResult["NOT_FORMATED"]["fact"]["weather_type_short"]?></span><br />
			<?=GetMessage("IMYIE_WHEATHER_WIND_SPEED")?>: <?=$arResult["NOT_FORMATED"]["fact"]["wind_speed"]?> <?=GetMessage("IMYIE_WHEATHER_WIND_SPEED_ED")?><br />
			<?=GetMessage("IMYIE_WHEATHER_HUMIDITY")?>: <?=$arResult["NOT_FORMATED"]["fact"]["humidity"]?><?=GetMessage("IMYIE_WHEATHER_HUMIDITY_ED")?><br />
			<?=GetMessage("IMYIE_WHEATHER_PRESSURE")?>: <?=$arResult["NOT_FORMATED"]["fact"]["pressure"]?> <?=GetMessage("IMYIE_WHEATHER_PRESSURE_ED")?>
			<div class="ygoda-pogodadays">
				<div class="ygoda-pogodadays-span">
					<?=GetMessage("IMYIE_WHEATHER_NIGHT")?> <?if($arResult["NOT_FORMATED"]["informer"]["temperature"][0]>0):?>
						<?=GetMessage("IMYIE_WHEATHER_PLUS")?><?=$arResult["NOT_FORMATED"]["informer"]["temperature"][0]?>
					<?elseif($arResult["NOT_FORMATED"]["informer"]["temperature"][0]==0):?>
						<?=$arResult["NOT_FORMATED"]["informer"]["temperature"][0]?>
					<?else:?>
						<?=$arResult["NOT_FORMATED"]["informer"]["temperature"][0]?>
					<?endif;?> | 
					<?=GetMessage("IMYIE_WHEATHER_TOMORROW")?> <?if($arResult["NOT_FORMATED"]["informer"]["temperature"][1]>0):?>
						<?=GetMessage("IMYIE_WHEATHER_PLUS")?><?=$arResult["NOT_FORMATED"]["informer"]["temperature"][1]?>
					<?elseif($arResult["NOT_FORMATED"]["informer"]["temperature"][1]==0):?>
						<?=$arResult["NOT_FORMATED"]["informer"]["temperature"][1]?>
					<?else:?>
						<?=$arResult["NOT_FORMATED"]["informer"]["temperature"][1]?>
					<?endif;?>
				</div>
			</div>
		</div>
	</div>
</div>