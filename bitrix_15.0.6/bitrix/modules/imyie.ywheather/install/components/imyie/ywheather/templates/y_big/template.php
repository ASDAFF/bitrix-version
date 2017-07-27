<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<div class="ygoda">
	<div class="ygoda-title">
		<?=$arResult["NOT_FORMATED"]["@attributes"]["country"]?> &#149; <?=$arResult["NOT_FORMATED"]["@attributes"]["city"]?>
	</div>
	<div class="ygoda-body">
		<div class="ygoda-image">
			<?if($arResult["NOT_FORMATED"]["fact"]["image"]!=""):?>
				<img src="http://img.yandex.net/i/wiz<?=$arResult["NOT_FORMATED"]["fact"]["image"]?>.png" border="0" alt="<?=GetMessage("IMYIE_WHEATHER_BAD_YA_IMAGE")?>" />
			<?else:?>
				<?=GetMessage("IMYIE_WHEATHER_BAD_YA_IMAGE")?>
			<?endif;?>
			<?if($arResult["NOT_FORMATED"]["fact"]["temperature"]>0):?>
				<?=GetMessage("IMYIE_WHEATHER_PLUS")?><?=$arResult["NOT_FORMATED"]["fact"]["temperature"]?>
			<?elseif($arResult["NOT_FORMATED"]["fact"]["temperature"]==0):?>
				<?=$arResult["NOT_FORMATED"]["fact"]["temperature"]?>
			<?else:?>
				<?=$arResult["NOT_FORMATED"]["fact"]["temperature"]?>
			<?endif;?> &#176;C,
			<?=$arResult["NOT_FORMATED"]["fact"]["weather_type"]?>, 
			<?=GetMessage("IMYIE_WHEATHER_WIND_SPEED")?>: <?=$arResult["NOT_FORMATED"]["fact"]["wind_speed"]?> <?=GetMessage("IMYIE_WHEATHER_WIND_SPEED_ED")?>, 
			<?=GetMessage("IMYIE_WHEATHER_HUMIDITY")?>: <?=$arResult["NOT_FORMATED"]["fact"]["humidity"]?><?=GetMessage("IMYIE_WHEATHER_HUMIDITY_ED")?>, 
			<?=GetMessage("IMYIE_WHEATHER_PRESSURE")?>: <?=$arResult["NOT_FORMATED"]["fact"]["pressure"]?> <?=GetMessage("IMYIE_WHEATHER_PRESSURE_ED")?> 
		</div>
		<div class="ygoda-days">
			<?foreach($arResult["NOT_FORMATED"]["day"] as $key1 => $day):
			$result = $key1%2;
			if ($result===0){$chetnoe = true;}else{$chetnoe = false;}
			?>
				<div class="ygoda-day<?if($chetnoe):?> ygoda-day-chet<?endif;?>">
					<div class="ygoda-day-date"><?=$day["@attributes"]["date"]?></div>
					<div class="ygoda-day-temperature">
						<?foreach($day["day_part"] as $key2 => $day_part):?>
							<?if($key2<4):?>
							<?=GetMessage("IMYIE_WHEATHER_DAY_PART_".$day_part["@attributes"]["type"])?>
							<?if($day_part["temperature_from"]>0):?>
								<?=GetMessage("IMYIE_WHEATHER_PLUS")?><?=$day_part["temperature_from"]?>
							<?elseif($day_part["temperature_from"]==0):?>
								<?=$day_part["temperature_from"]?>
							<?else:?>
								<?=$day_part["temperature_from"]?>
							<?endif;?>...
							<?if($day_part["temperature_to"]>0):?>
								<?=GetMessage("IMYIE_WHEATHER_PLUS")?><?=$day_part["temperature_to"]?>
							<?elseif($day_part["temperature_to"]==0):?>
								<?=$day_part["temperature_to"]?>
							<?else:?>
								<?=$day_part["temperature_to"]?>
							<?endif;?>
							<br />
							<?endif;?>
						<?endforeach;?>
					</div>
				</div>
			<?endforeach;?>
		</div>
	</div>
</div>