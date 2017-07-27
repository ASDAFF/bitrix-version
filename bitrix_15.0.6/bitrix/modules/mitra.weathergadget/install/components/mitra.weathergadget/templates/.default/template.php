<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if($arResult['ERRNO']) {
    return false;
}

$xml = $arResult['RESULT'];

?>
<div style="border: 1px solid #D3D3D3; border-radius: 20px 20px 20px 20px; margin: 0 5px 20px; padding: 5px; text-align: center; width: 250px;">
    <table>
        <tr><td><strong><?$node = $xml->SelectNodes('/info/region/title');?><?=$node->content?></strong></td></tr>
    <tr>
        <td nowrap="yes" width="20%" valign="top"><? $node = $xml->SelectNodes('/info/weather/day/day_part/temperature'); $t = Intval($node->content);?><span class="t<?=intval($t/10)?>"><?=$node->content?> C</span><br/>
            <br/>
            <?$node = $xml->SelectNodes('/info/weather/day/day_part/image-v3');?><img src="<?=$node->content?>" class="gdwico">
        </td>
        <td width="80%" nowrap valign="top" style="text-align: left;">
            <?$node = $xml->SelectNodes('/info/weather/day/day_part/weather_type');?>
            <span class="gdweather"><?=$node->content?></span><br>
            <span class="gdwinfo" >
                <?$node = $xml->SelectNodes('/info/weather/day/day_part/wind_direction');?>
                <?=GetMessage('MITRA_WEATHERGADGET_WIND');?>: <?=$node->content?>, <?$node = $xml->SelectNodes('/info/weather/day/day_part/wind_speed');?><?=$node->content?> <?=GetMessage('MITRA_WEATHERGADGET_M_SEC');?>. <br>
                <?$node = $xml->SelectNodes('/info/weather/day/day_part/pressure');?>
                <?=GetMessage('MITRA_WEATHERGADGET_PRESSURE');?>: <?=$node->content?> <?=GetMessage('MITRA_WEATHERGADGET_MM_HG');?><br>
                <?$node = $xml->SelectNodes('/info/weather/day/day_part/dampness');?>
                <?=GetMessage('MITRA_WEATHERGADGET_HUMIDITY');?>: <?=$node->content?>%<br>

                <?$node = $xml->SelectNodes('/info/weather/day/sun_rise');?>
                <?=GetMessage('MITRA_WEATHERGADGET_SUNRISE');?>: <?=$node->content?><br>
                <?$node = $xml->SelectNodes('/info/weather/day/sunset');?>
                <?=GetMessage('MITRA_WEATHERGADGET_SET');?>: <?=$node->content?>
            </span>
        </td>
    </tr>

    <?$node = $xml->SelectNodes('/info/weather/tonight/temperature');?>
    <?if($node):?>
    <tr>
        <td><?=GetMessage('MITRA_WEATHERGADGET_NIGHT');?>:</td>
        <td colspan="2"><?=$node->content?>C</td>
    </tr>
    <?endif?>
    </table>
</div>
<?/*
<?$node = $xml->SelectNodes('/info/weather/url');?>
<a href="<?=htmlspecialchars($node->content)?>"><?=GetMessage('MITRA_WEATHERGADGET_MORE');?></a> <a href="<?=htmlspecialchars($node->content)?>"><img width="7" height="7" border="0" src="/bitrix/components/bitrix/desktop/images/arrows.gif" /></a>
*/?>