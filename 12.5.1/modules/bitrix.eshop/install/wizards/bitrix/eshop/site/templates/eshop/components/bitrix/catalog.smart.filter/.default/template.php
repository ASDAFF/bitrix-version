<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/js/jquery.ui.widget.js');?>
<?$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/js/jquery.ui.mouse.js');?>
<?$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/js/jquery.ui.slider.js');?>
<?$IsIe = (strpos($_SERVER['HTTP_USER_AGENT'], "MSIE")) ? true : false;?>
<script type="text/javascript">

	/*function activeProp(element, propID)
	{
		$(".horizontalfilter li").removeClass("active");
		$(element).addClass("active");
		$(".cnt").removeClass("active");
		$("#propBlockValues_"+propID).addClass("active");
	}                            */
</script>

<form name="<?echo $arResult["FILTER_NAME"]."_form"?>" action="<?echo $arResult["FORM_ACTION"]?>" method="get" class="smartfilter">
	<?foreach($arResult["HIDDEN"] as $arItem):?>
		<input type="hidden" name="<?echo $arItem["CONTROL_NAME"]?>" id="<?echo $arItem["CONTROL_ID"]?>" value="<?echo $arItem["HTML_VALUE"]?>"/>
	<?endforeach;?>
	<div class="filtren compare">
		<h5><?echo GetMessage("CT_BCSF_FILTER_TITLE")?></h5>
		<table style="width:100%; margin-bottom: 25px;">
		<?foreach($arResult["ITEMS"] as $key => $arItem):?>
			<?if(isset($arItem["PRICE"])):?>
			<?
				if (empty($arItem["VALUES"]["MIN"]["VALUE"])) $arItem["VALUES"]["MIN"]["VALUE"] = 0;
				if (empty($arItem["VALUES"]["MAX"]["VALUE"])) $arItem["VALUES"]["MAX"]["VALUE"] = 100000;
			?>
			<tr class="cnt" id="<?=$arItem["CODE"]?>">
				<td style="padding:10px;width: 180px" class="<?=$arItem["CODE"]?>" ><?=$arItem["NAME"]?></td>
				<td style="width:75px;padding:10px;"><?if ($IsIe):?><span style="position: absolute; margin-top: 11px;margin-left: -21px;"><?echo GetMessage("CT_BCSF_FILTER_FROM")?></span><?endif?><input class="min-price" type="text"  name="<?echo $arItem["VALUES"]["MIN"]["CONTROL_NAME"]?>" placeholder="<?echo GetMessage("CT_BCSF_FILTER_FROM")?>" id="<?echo $arItem["VALUES"]["MIN"]["CONTROL_ID"]?>" value="<?echo $arItem["VALUES"]["MIN"]["HTML_VALUE"]?>" size="5" onkeyup="smartFilter.keyup(this)"/></td>
				<td style="width:75px;padding:10px;"><?if ($IsIe):?><span style="position: absolute; margin-top: 11px;margin-left: -21px;"><?echo GetMessage("CT_BCSF_FILTER_TO")?></span><?endif?><input class="max-price" type="text"  name="<?echo $arItem["VALUES"]["MAX"]["CONTROL_NAME"]?>" placeholder="<?echo GetMessage("CT_BCSF_FILTER_TO")?>" id="<?echo $arItem["VALUES"]["MAX"]["CONTROL_ID"]?>" value="<?echo $arItem["VALUES"]["MAX"]["HTML_VALUE"]?>" size="5" onkeyup="smartFilter.keyup(this)" /></td>
				<td style="padding:10px;vertical-align:top;">
					<div class="slider-range" id="slider-<?echo $arItem["VALUES"]["MIN"]["CONTROL_ID"]?>" style="margin:7px auto 8px"></div>
					<div class="max-price" id="max-price-<?echo $arItem["VALUES"]["MAX"]["CONTROL_ID"]?>"></div>
					<div class="min-price" id="min-price-<?echo $arItem["VALUES"]["MIN"]["CONTROL_ID"]?>"></div>
				</td>
			</tr>
			<script>
				$(function() {
					var minprice = <?=CUtil::JSEscape($arItem["VALUES"]["MIN"]["VALUE"])?>;
					var maxprice = <?=CUtil::JSEscape($arItem["VALUES"]["MAX"]["VALUE"])?>;
					$("#slider-<?echo $arItem["VALUES"]["MIN"]["CONTROL_ID"]?>").slider({
						range: true,
						min: minprice,
						max: maxprice,
						values: [ <?=(empty($arItem["VALUES"]["MIN"]["HTML_VALUE"])) ? CUtil::JSEscape($arItem["VALUES"]["MIN"]["VALUE"]) : CUtil::JSEscape($arItem["VALUES"]["MIN"]["HTML_VALUE"])?>, <?=(empty($arItem["VALUES"]["MAX"]["HTML_VALUE"])) ? CUtil::JSEscape($arItem["VALUES"]["MAX"]["VALUE"]) : CUtil::JSEscape($arItem["VALUES"]["MAX"]["HTML_VALUE"])?> ],
						slide: function( event, ui ) {
							$("#<?echo $arItem["VALUES"]["MIN"]["CONTROL_ID"]?>").val(ui.values[0]);
							$("#<?echo $arItem["VALUES"]["MAX"]["CONTROL_ID"]?>").val(ui.values[1]);
							smartFilter.keyup(BX("<?echo $arItem["VALUES"]["MIN"]["CONTROL_ID"]?>"));
						}
					});
					$("#max-price-<?echo $arItem["VALUES"]["MAX"]["CONTROL_ID"]?>").text(maxprice);
					$("#min-price-<?echo $arItem["VALUES"]["MIN"]["CONTROL_ID"]?>").text(minprice);
					//$(".min-price").val($(".slider-range").slider("values", 0));
					//$(".max-price").val($(".slider-range").slider("values", 1));
				});
			</script>
				<?unset($arResult["ITEMS"][$key]);?>
			<?endif;?>
		<?endforeach;?>
		</table>
	<?if (count($arResult["ITEMS"]) > 0):?>
		<div class="more-options-hfilter">
			<div class="catf" id="horizontalfilter">
				<ul class="horizontalfilter lsnn"> <!-- Titles -->
				<?$flag = 0;?>
				<?foreach($arResult["ITEMS"] as $arItem):?>
					<?if(!empty($arItem["VALUES"]) && !isset($arItem["PRICE"])):?>
					<li  class="<?=$arItem["CODE"]?><?if ($flag == 0) echo " active"?>"><span><span><?=$arItem["NAME"]?></span></span></li>
					<?$flag = 1;?>
					<?endif?>
				<?endforeach?>
				</ul>
			</div>
			<div class="cntf">  <!-- Content -->
			<?$flag = 0;?>
			<?foreach($arResult["ITEMS"] as $arItem):?>
				<?if (!empty($arItem["VALUES"])):?>
				<div class="cnt<?if ($flag == 0) echo " active"?>" id="<?=$arItem["CODE"]?>">
					<?if($arItem["PROPERTY_TYPE"] == "N" && !isset($arItem["PRICE"])):?>
						<?
							//$arItem["VALUES"]["MIN"]["VALUE"];
							//$arItem["VALUES"]["MAX"]["VALUE"];
						?>
						<?if ($IsIe):?><span style="position: absolute; margin-top: 11px;margin-left: -21px;"><?echo GetMessage("CT_BCSF_FILTER_FROM")?></span><?endif?><input  type="text" class="<?=$arItem["CODE"]?>" name="<?echo $arItem["VALUES"]["MIN"]["CONTROL_NAME"]?>" id="<?echo $arItem["VALUES"]["MIN"]["CONTROL_ID"]?>" placeholder="<?echo GetMessage("CT_BCSF_FILTER_FROM")?>" value="<?echo $arItem["VALUES"]["MIN"]["HTML_VALUE"]?>" size="5" onkeyup="smartFilter.keyup(this)"/>
						<?if ($IsIe):?><span style="position: absolute; margin-top: 11px;margin-left: -21px;"><?echo GetMessage("CT_BCSF_FILTER_TO")?></span><?endif?><input type="text" name="<?echo $arItem["VALUES"]["MAX"]["CONTROL_NAME"]?>" id="<?echo $arItem["VALUES"]["MAX"]["CONTROL_ID"]?>" placeholder="<?echo GetMessage("CT_BCSF_FILTER_TO")?>" value="<?echo $arItem["VALUES"]["MAX"]["HTML_VALUE"]?>" size="5" onkeyup="smartFilter.keyup(this)" />
						<div  style="float:right; width: 253px;margin-right: 11px;">
							<div class="slider-range" id="slider-<?echo $arItem["VALUES"]["MIN"]["CONTROL_ID"]?>" style="margin:5px auto 3px;"></div>
							<div class="max-price" id="max-price-<?echo $arItem["VALUES"]["MAX"]["CONTROL_ID"]?>"></div>
							<div class="min-price" id="min-price-<?echo $arItem["VALUES"]["MIN"]["CONTROL_ID"]?>"></div>
						</div>
						<?if ($arItem["VALUES"]["MIN"]["VALUE"] > 0 && $arItem["VALUES"]["MAX"]["VALUE"] > 0 && $arItem["VALUES"]["MIN"]["VALUE"] < $arItem["VALUES"]["MAX"]["VALUE"]):?>
						<script>
							var minprice2 = <?=CUtil::JSEscape($arItem["VALUES"]["MIN"]["VALUE"])?>;
							var maxprice2 = <?=CUtil::JSEscape($arItem["VALUES"]["MAX"]["VALUE"])?>;
							$("#slider-<?echo $arItem["VALUES"]["MIN"]["CONTROL_ID"]?>").slider({
								range: true,
								min: minprice2,
								max: maxprice2,
								values: [ <?=(empty($arItem["VALUES"]["MIN"]["HTML_VALUE"])) ? CUtil::JSEscape($arItem["VALUES"]["MIN"]["VALUE"]) : CUtil::JSEscape($arItem["VALUES"]["MIN"]["HTML_VALUE"])?>, <?=(empty($arItem["VALUES"]["MAX"]["HTML_VALUE"])) ? CUtil::JSEscape($arItem["VALUES"]["MAX"]["VALUE"]) : CUtil::JSEscape($arItem["VALUES"]["MAX"]["HTML_VALUE"])?> ],
								slide: function( event, ui ) {
									$("#<?echo $arItem["VALUES"]["MIN"]["CONTROL_ID"]?>").val(ui.values[0]);
									$("#<?echo $arItem["VALUES"]["MAX"]["CONTROL_ID"]?>").val(ui.values[1]);
									smartFilter.keyup(BX("<?echo $arItem["VALUES"]["MIN"]["CONTROL_ID"]?>"));
								}
							});
							$("#max-price-<?echo $arItem["VALUES"]["MAX"]["CONTROL_ID"]?>").text(maxprice2);
							$("#min-price-<?echo $arItem["VALUES"]["MIN"]["CONTROL_ID"]?>").text(minprice2);
						</script>
						<?endif?>
					<?elseif(!empty($arItem["VALUES"]) && !isset($arItem["PRICE"])):?>
					<ul class="lsnn">
						<?foreach($arItem["VALUES"] as $val => $ar):?>
						<li class="lvl2<?echo $ar["DISABLED"]? ' lvl2_disabled': ''?>" >
							<input type="checkbox" value="<?echo $ar["HTML_VALUE"]?>" name="<?echo $ar["CONTROL_NAME"]?>" id="<?echo $ar["CONTROL_ID"]?>" <?echo $ar["CHECKED"]? 'checked="checked"': ''?> onclick="smartFilter.click(this)"/>
							<label for="<?echo $ar["CONTROL_ID"]?>"><?echo $ar["VALUE"];?></label>
						</li>
						<?endforeach;?>
					</ul>
					<?endif;?>

				</div>
				<?$flag = 1;?>
				<?endif?>
			<?endforeach;?>
			</div>
		</div>
	<?endif?>
		<a href="" class="more-options-hfilter-button"><?=GetMessageJS("CT_BCSF_HIDE_PROPS")?></a>
		<div class="posabo">
			<input type="submit" id="set_filter" name="set_filter" value="<?=GetMessage("CT_BCSF_SET_FILTER")?>" class="bt1 lupe"/>
			<input type="submit" id="del_filter" name="del_filter" value="<?=GetMessage("CT_BCSF_DEL_FILTER")?>" class="bt2"/>
		</div>
		<div class="modef" id="modef" <?if(!isset($arResult["ELEMENT_COUNT"])) echo 'style="display:none"';?>>
			<?echo GetMessage("CT_BCSF_FILTER_COUNT", array("#ELEMENT_COUNT#" => '<span id="modef_num">'.intval($arResult["ELEMENT_COUNT"]).'</span>'));?>
			<a href="<?echo $arResult["FILTER_URL"]?>" ><?echo GetMessage("CT_BCSF_FILTER_SHOW")?></a>
			<span class="ecke"></span>
		</div>
	</div>
</form>
<script>
	var smartFilter = new JCSmartFilter('<?echo $arResult["FORM_ACTION"]?>');
	var height = $(".catf").height();
	if ($.cookie("acstatus") == "open"){
		$(".more-options-hfilter").css({"min-height":height+"px"});	
	} else {	
		$.cookie("acstatus", "close",{expires:14});
		$(".more-options-hfilter").css({"overflow":"hidden", "height":"0px"});
		$(".more-options-hfilter-button").text("<?=GetMessageJS("CT_BCSF_SHOW_PROPS")?>");
	}
	$(".more-options-hfilter-button").click(function(){
		if ($.cookie("acstatus") == "close"){
			$.cookie("acstatus", "open",{expires:14});
			$(".more-options-hfilter").animate({"min-height":height+"px"},300);
			setTimeout(function() {
				$(".more-options-hfilter").css({"min-height":height+"px","height":"auto","overflow":"visible"}); 
			}, 300)	
			$(".more-options-hfilter-button").text("<?=GetMessageJS("CT_BCSF_HIDE_PROPS")?>");
		} else {
			$.cookie("acstatus", "close",{expires:14});
			$(".more-options-hfilter").css({"overflow":"hidden","min-height":"auto"});
			$(".more-options-hfilter").animate({"height":0},300);
			$(".more-options-hfilter-button").text("<?=GetMessageJS("CT_BCSF_SHOW_PROPS")?>");
			$.cookie("acstatus", "close");
		}
		return false;
	});
</script>