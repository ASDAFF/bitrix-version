<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$tmpl = "/bitrix/components/imyie/ywheather/templates/iphone/";?>

<div class="ygoda-border">
<div class="ygoda">
	<div class="ygoda-img">
		<?if($arResult["NOT_FORMATED"]["fact"]["image"]!=""):?>
			<img src="<?=$tmpl?>img/<?=$arResult["NOT_FORMATED"]["fact"]["image"]?>.png" width="80" border="0" alt="" />
		<?else:?>
			<img src="<?=$tmpl?>img/18.png" width="80" border="0" alt="" />
		<?endif;?>
	</div>
	<div class="ygoda-fact">
		<div class="ygoda-fact-left">
			<span class="ygoda-span-1"><?=$arResult["NOT_FORMATED"]["@attributes"]["city"]?></span><br />
			<span class="ygoda-span-2"><?=$arResult["NOT_FORMATED"]["fact"]["weather_type_short"]?></span>
		</div>
		<div class="ygoda-fact-right">
			<?if($arResult["NOT_FORMATED"]["fact"]["temperature"]>0):?>
				<span class="ygoda-hot"><?=GetMessage("IMYIE_WHEATHER_PLUS")?><?=$arResult["NOT_FORMATED"]["fact"]["temperature"]?>&#176;</span>
			<?elseif($arResult["NOT_FORMATED"]["fact"]["temperature"]==0):?>
				<span class="ygoda-norm"><?=$arResult["NOT_FORMATED"]["fact"]["temperature"]?>&#176;</span>
			<?else:?>
				<span class="ygoda-cold"><?=$arResult["NOT_FORMATED"]["fact"]["temperature"]?>&#176;</span>
			<?endif;?>
		</div>
		<div class="ygoda-days">
			<?foreach($arResult["NOT_FORMATED"]["day"] as $dayKey => $day):
			$result = $dayKey%2;
			if ($result===0){$chetnoe = true;}else{$chetnoe = false;}
			if($dayKey<7 && $dayKey!=0):
			$dat_date_arr = explode('-', $day["@attributes"]["date"]);
			$day_name = date("l", mktime(0, 0, 0, $dat_date_arr[1], $dat_date_arr[2], $dat_date_arr[0]));
			$day_temperatura = round($day["day_part"][1]["temperature_from"] + (round($day["day_part"][1]["temperature_to"]-$day["day_part"][1]["temperature_from"])/2));
			$day_img = $day["day_part"][1]["image"];
			?>
				<div class="ygoda-oneday<?if(!$chetnoe):?> ygoda-oneday-bg1<?else:?> ygoda-oneday-bg0<?endif;?>">
					<div class="ygoda-oneday-name"><?=GetMessage("IMYIE_WHEATHER_DAY_NAME_".$day_name)?></div>
					<div class="ygoda-oneday-temp">
						<?if($day_temperatura>0):?>
							<?=GetMessage("IMYIE_WHEATHER_PLUS")?><?=$day_temperatura?>&#176;
						<?elseif($arResult["NOT_FORMATED"]["fact"]["temperature"]==0):?>
							<?=$day_temperatura?>&#176;
						<?else:?>
							<?=$day_temperatura?>&#176;
						<?endif;?>
					</div>
					<div class="ygoda-oneday-img">
						<?if($day_img!=""):?>
							<img src="<?=$tmpl?>img/<?=$day_img?>.png" width="20" border="0" alt="" />
						<?else:?>
							<img src="<?=$tmpl?>img/18.png" width="20" border="0" alt="" />
						<?endif;?>
					</div>
				</div>
			<?endif;?>
			<?endforeach;?>
		</div>
	</div>
	<div class="ygoda-end">
	
	</div>
</div>
</div>