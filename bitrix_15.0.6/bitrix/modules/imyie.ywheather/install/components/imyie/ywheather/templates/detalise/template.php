<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$tmpl = "/bitrix/components/imyie/ywheather/templates/detalise/";?>

<div class="ygoda">
	<div class="ygoda-title"><?=$arResult["NOT_FORMATED"]["@attributes"]["country"]?> - <?=$arResult["NOT_FORMATED"]["@attributes"]["city"]?></div>
	<table border="0" cellspacing="0" cellpadding="0">
		<tr>
			<td valign="top">
				<div class="ygoda-current_day-image">
					<?if($arResult["NOT_FORMATED"]["fact"]["image"]!=""):?>
						<img src="<?=$tmpl?>img/<?=$arResult["NOT_FORMATED"]["fact"]["image"]?>.png" width="100" border="0" alt="" />
					<?else:?>
						<?=GetMessage("IMYIE_WHEATHER_BAD_YA_IMAGE")?>
					<?endif;?>
				</div>
			</td>
			<td valign="top">
				<div class="ygoda-current_day">
					<div class="ygoda-current_day-head"><?=GetMessage("IMYIE_WHEATHER_RIGHT_WHEATHER_NOW")?></div>
					<div class="ygoda-current_day-iformation">
						<?=$arResult["NOT_FORMATED"]["fact"]["temperature"]?> &deg;C, <?
							?><?=$arResult["NOT_FORMATED"]["fact"]["weather_type_short"]?>, <?
							?><?=GetMessage("IMYIE_WHEATHER_WIND_SPEED")?>: <?=$arResult["NOT_FORMATED"]["fact"]["wind_speed"]?> <?=GetMessage("IMYIE_WHEATHER_WIND_SPEED_ED")?>,<br /><?
						?><?=GetMessage("IMYIE_WHEATHER_HUMIDITY")?>: <?=$arResult["NOT_FORMATED"]["fact"]["humidity"]?> <?=GetMessage("IMYIE_WHEATHER_HUMIDITY_ED")?>, <?
							?><?=GetMessage("IMYIE_WHEATHER_PRESSURE")?>: <?=$arResult["NOT_FORMATED"]["fact"]["humidity"]?> <?=GetMessage("IMYIE_WHEATHER_PRESSURE_ED")?>
					</div>
				</div>
				<div class="ygoda-other_days">
					<?foreach($arResult["NOT_FORMATED"]["day"] as $key1 => $day):
					$result = $key1%2;
					if ($result===0){$chetnoe = true;}else{$chetnoe = false;}
					?>
						<div class="ygoda-day<?if($chetnoe):?> ygoda-day-chet<?else:?> ygoda-day-nechet<?endif;?>">
							<img class="ygoda-day-image" src="http://img.yandex.net/i/wiz<?=$arResult["NOT_FORMATED"]["fact"]["image"]?>.png" border="0" alt="" />
							<div class="ygoda-day-date">
								<?=$day["print_date"]?><br />
								<?=GetMessage("IMYIE_WHEATHER_DAY_NUM_".$day["day_num"])?>
							</div>
							<div class="ygoda-clear"></div>
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
			</td>
		</tr>
	</table>
</div>