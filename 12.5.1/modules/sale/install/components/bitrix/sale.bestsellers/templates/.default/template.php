<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<div class="sale_bestseller">
<div class="sale_bestseller_days">
	<?
	$bFirst = true;
	foreach($arParams["PERIOD"] as $val)
	{
		if(!$bFirst)
			echo "&nbsp;|&nbsp;";
		if(IntVal($val) == $arParams["days"])
			echo "<b>";
		else
			echo '<a title="'.GetMessage("SB_HREF_TITLE").' '.IntVal($val).' '.GetMessage("SB_DAYS").'" href="'.$APPLICATION->GetCurPageParam("days=".IntVal($val), Array("days")).'">';
		echo IntVal($val);
		if(IntVal($val) == $arParams["days"])
			echo "</b>";
		else
			echo '</a>';
		$bFirst = false;
	}
	echo "&nbsp;(".GetMessage("SB_DAYS").")";
	?>
</div>
<?
if(!empty($arResult["ELEMENT"]))
{
	?>
	<div class="sale_bestseller_list">
	<ol>
	<?
	foreach($arResult["ELEMENT"] as $arItem)
	{
		echo "<li>";
		if(strlen($arItem["DETAIL_PAGE_URL"]) > 0)
			echo "<a href=\"".$arItem["DETAIL_PAGE_URL"]."\">";
		echo $arItem["NAME"];
		if(strlen($arItem["DETAIL_PAGE_URL"]) > 0)
			echo "</a>";
		echo "</li>";
	}
	?>
	</ol>
	</div>
	<?
}
?>
<div class="sale_bestseller_type">
	<?
	$bFirst = true;
	foreach($arParams["BY"] as $val)
	{
		if(!$bFirst)
			echo "&nbsp;|&nbsp;";
		if($val == $arParams["by_val"])
			echo "<b>";
		else
			echo '<a href="'.$APPLICATION->GetCurPageParam("by=".htmlspecialcharsbx($val), Array("by")).'">';
		echo GetMessage("SB_".htmlspecialcharsbx($val));
		if($val == $arParams["by_val"])
			echo "</b>";
		else
			echo '</a>';
		$bFirst = false;
	}
	?>
</div>
</div>