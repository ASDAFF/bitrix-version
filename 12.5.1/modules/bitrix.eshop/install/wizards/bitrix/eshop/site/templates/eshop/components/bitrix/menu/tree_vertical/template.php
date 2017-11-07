<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
if (count($arResult) < 1)
	return;
$wizTemplateId = COption::GetOptionString("main", "wizard_template_id", "eshop_vertical", SITE_ID);
$bManyIblock = array_key_exists("IBLOCK_ROOT_ITEM", $arResult[0]["PARAMS"]);
?>

<ul class="nav<?if ($wizTemplateId == "eshop_vertical_popup"):?> popup<?endif?>">
<?
	$previousLevel = 0;
	foreach($arResult as $key => $arItem):

		if ($previousLevel && $arItem["DEPTH_LEVEL"] < $previousLevel): // recursion end
			echo str_repeat("</ul></li>", ($previousLevel - $arItem["DEPTH_LEVEL"]));
		endif;

		if ($arItem["IS_PARENT"]): //has children
			$i = $key;
			$bHasSelected = $arItem['SELECTED'];
			$childSelected = false;
			if (!$bHasSelected)         //if parent is selected, check children
			{
				while ($arResult[++$i]['DEPTH_LEVEL'] > $arItem['DEPTH_LEVEL'])
				{
					if ($arResult[$i]['SELECTED'])
					{
						$bHasSelected = $childSelected = true; break;   // if child is selected, select parent
					}
				}
			}

		//	$className = $nHasSelected ? 'selected' : '';//($bHasSelected ? 'selected' : '');
?>
		<? if ($arItem['DEPTH_LEVEL'] > 1 && !$childSelected && $bHasSelected) :  // if child is selected, but his children are not selected?>
			<li class="current selected lvl<?=$arItem['DEPTH_LEVEL']?>">
				<span style="position:relative; display: block;"><a href="<?=$arItem["LINK"]?>"><?=$arItem["TEXT"]?></a><?if ($wizTemplateId == "eshop_vertical"):?><span class="showchild"><span class="arrow"></span></span><?endif?></span>
			<ul>

		<? else:?>
			<?$className = $bHasSelected ? 'current selected' : '';
			/*if ($arItem['DEPTH_LEVEL'] > 1)*/ $className.= " lvl".$arItem['DEPTH_LEVEL'];?>
			<li<?=$className ? ' class="'.$className.'"' : ''?>>
				<span style="position:relative; display: block;"><a href="<?=$arItem["LINK"]?>"><?=$arItem["TEXT"]?></a><span class="showchild<?if ($wizTemplateId == "eshop_vertical_popup"):?>_popup<?endif?>"><span class="arrow"></span></span></span>
				<ul<?=$bHasSelected || $wizTemplateId == "eshop_vertical_popup" || ($bManyIblock && $arItem['DEPTH_LEVEL'] <= 1) ? '' : ' style="display: none;"'?>>
		<? endif?>

<?
		else:  // no childs
			if ($arItem["PERMISSION"] > "D"):
				$className = $arItem['SELECTED'] ? 'current selected' : '';
			/*if ($arItem['DEPTH_LEVEL'] > 1)*/ $className.= " lvl".$arItem['DEPTH_LEVEL'];
?>
			<li<?=$className ? ' class="'.$className.'"' : ''?>>
				<span style="position:relative; <?if ($wizTemplateId == "eshop_vertical"):?>display: block;<?endif?>"><a href="<?=$arItem["LINK"]?>"><?=$arItem["TEXT"]?></a></span></li><?
			endif;
		endif;

		$previousLevel = $arItem["DEPTH_LEVEL"];
	endforeach;

	if ($previousLevel > 1)://close last item tags
		echo str_repeat("</ul></li>", ($previousLevel-1) );
	endif;
?>
</ul>
<?if ($wizTemplateId == "eshop_vertical"):?>
<script type="text/javascript">
	function setHeightlvlp(clickitem){
		if(clickitem.parent("span").parent("li").find("ul:first").attr('rel')){
			heightlvl2Ul = clickitem.parent("span").parent("li").find("ul:first").attr('rel');
		} else {
			clickitem.parent("span").parent("li").find("ul:first").css({display: 'block',height:"auto"});
			heightlvl2Ul = clickitem.parent("span").parent("li").find("ul:first").height();
		}
	}

	var lis = $('.sidebar .nav').find('li');
	for(var i = 0; i < lis.length; i++) {
		if($(lis[i]).hasClass('current')) {
			if($(lis[i]).parents("li").hasClass('lvl1')){

				var ul = $(lis[i]).find('ul:first');
				$(ul).css({display: 'block',height:"auto"});
				var h = $(ul).height();
				$(ul).css({height: 0, display: 'block'});

				var ulp= $(lis[i]).parents("li.lvl1").find('ul:first');
				$(ulp).css({display: 'block'});
				var hp = $(ulp).height();
				$(ulp).css({height: 0, display: 'block'});

				$(ul).attr("rel", h);
				// $(ulp).attr("rel", hp);
				$(ul).css({height: h+'px'});
				$(ulp).css({height: h+hp+'px'});
			} else {
				var ul = $(lis[i]).find('ul:first');
				$(ul).css({display: 'block',height:"auto"});
				var h = $(ul).height();
				$(ul).css({height: 0, display: 'block'});
				$(ul).attr("rel", h);
				$(ul).css({height: h+'px'})
			}
		}
	}

	$(".showchild").live('click', function() {
		var clickitem = $(this);
		if( clickitem.parent("span").parent("li").hasClass('lvl1')){

			if( clickitem.parent("span").parent("li").hasClass('current')){
				clickitem.parent("span").parent("li").find("ul").animate({height: 0}, 300);
				clickitem.parent("span").parent("li").removeClass("current");
				clickitem.parent("span").parent("li").find(".current").removeClass("current");
			} else {
				setHeightlvlp(clickitem);
				clickitem.parent("span").parent("li").find("ul:first").attr('rel',heightlvl2Ul);
				clickitem.parent("span").parent("li").find("ul:first").css({height: 0, display: 'block'});
				clickitem.parent("span").parent("li").find("ul:first").animate({height: heightlvl2Ul+'px'}, 300);
				clickitem.parent("span").parent("li").addClass("current");
			}
		} else {
			if( clickitem.parent("span").parent("li").hasClass('current')){
				setHeightlvlp(clickitem);
				heightLVL1= clickitem.parents(".lvl1").find("ul:first").height(); 
				var resulth = parseInt(heightLVL1)-parseInt(heightlvl2Ul)
				clickitem.parent("span").parent("li").find("ul").animate({height: 0}, 300);
				clickitem.parents(".lvl1").find("ul:first").animate({height: resulth+"px"}, 300);
				clickitem.parent("span").parent("li").removeClass("current");
			} else {
				setHeightlvlp(clickitem);
				heightLVL1 = clickitem.parents(".lvl1").find("ul:first").height();
				clickitem.parent("span").parent("li").find("ul:first").attr('rel',heightlvl2Ul);
				clickitem.parent("span").parent("li").find("ul:first").css({height: 0, display: 'block'});
				clickitem.parent("span").parent("li").find("ul:first").animate({height: heightlvl2Ul+'px'}, 300);
				clickitem.parents(".lvl1").find("ul:first").animate({height:  parseInt(heightlvl2Ul)+ parseInt(heightLVL1)+'px'}, 300);
				clickitem.parent("span").parent("li").addClass("current");
			}
		}
		return false;
	});
</script>
<?endif?>