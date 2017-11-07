<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if($arResult["AJAX_MODE"])
	$arItemsHtml = array();
else
{
	?>
		<div class="order_itemlist_component" id="mobile-list">
	<?

	$finalHtml = '';
}

foreach ($arResult["ITEMS"] as $order)
{
	if(isset($order["FOLDED"]) && $order["FOLDED"])
		$bFolded = true;
	else
		$bFolded = false;

	$itemHtml = '
			<div id="mobile-list-item-'.$order["ID"].'" class="order_itemlist_item_container';

	if($bFolded || !isset($order["TITLE_COLOR"]) || !$order["TITLE_COLOR"])
		$itemHtml .= ' order_completed';
	elseif(isset($order["TITLE_COLOR"]) && $order["TITLE_COLOR"])
		$itemHtml .= ' order_'.$order["TITLE_COLOR"];

	$itemHtml .= '">';

	if(isset($order['TITLE']))
		$itemHtml .= '
			<div class="order_itemlist_item_title"><span>'.$order['TITLE'].'</span></div>';

	if(isset($order['CONTENT']) && is_array($order['CONTENT']))
	{
		$itemHtml .= '
			<div class="order_itemlist_item_content">';

		if(!$bFolded)
			foreach ($order['CONTENT'] as $arRow)
				$itemHtml .= '
					<div class="order_itemlist_item_customer" >'.$arRow.'</div>';

		if(isset($order['CONTENT_RIGHT']))
			$itemHtml .= '
				<div class="order_itemlist_item_idshop">'.$order['CONTENT_RIGHT'].'</div>';

		$itemHtml .= '
			</div>';
	}

	if(isset($order['BOTTOM']))
	{
		$itemHtml .= '
			<div class="order_itemlist_item_total">';

		if(isset($order['BOTTOM']['LEFT']))
			$itemHtml .='
				<div class="order_itemlist_item_price">'.$order['BOTTOM']['LEFT'].'</div>';

		if(isset($order['BOTTOM']['RIGHT']) && !$bFolded)
			$itemHtml .= '
				<div class="'.($bFolded ? 'order_itemlist_item_total_completed' : 'order_itemlist_item_itemcount').'">'.$order['BOTTOM']['RIGHT'].'</div>';

		if($bFolded && isset($order['CONTENT_RIGHT']))
			$itemHtml .= '
				<div class="order_itemlist_item_idshop">'.$order['CONTENT_RIGHT'].'</div>';

		$itemHtml .= '
		</div>
		</div>';
	}

	if(isset($order['DETAIL_LINK']))
		$itemHtml = '
		<a href="'.$order['DETAIL_LINK'].'">
		'.$itemHtml.'
		</a>';

	if($arResult["AJAX_MODE"])
		$arItemsHtml[$order["ID"]] = $itemHtml;
	else
		$finalHtml .= $itemHtml;
}

if($arResult["AJAX_MODE"])
{
	$arItemsHtml = $APPLICATION->ConvertCharsetArray($arItemsHtml, SITE_CHARSET, 'utf-8');
	echo json_encode($arItemsHtml);
	die();
}

echo $finalHtml;

?>
		</div>

<script type="text/javascript">

	var mobileAppListParams  = {
		ajaxUrl: "<?=$arResult["AJAX_PATH"]?>"
	};

	var mobileAppList = new __MobileAppList(mobileAppListParams);

	BX.addCustomEvent('<?=$arResult["JS_EVENT_ITEM_CHANGE"]?>', function (params){ mobileAppList.getItemsHtml(params.arItems, params.insertToBottom);});

	var bottomReached = false;
	window.onscroll = function ()
	{
		var preloadCoefficient = <?=$arResult["SALE_ORDERS_LIST_PRELOAD_START"]?>;
		var clientHeight = document.documentElement.clientHeight ? document.documentElement.clientHeight : document.body.clientHeight;
		var documentHeight = document.documentElement.scrollHeight ? document.documentElement.scrollHeight : document.body.scrollHeight;
		var scrollTop = window.pageYOffset ? window.pageYOffset : (document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop);

		if((documentHeight - clientHeight*(1+preloadCoefficient)) <= scrollTop)
		{
			if(!bottomReached)
			{
				BX.onCustomEvent('<?=$arResult["JS_EVENT_BOTTOM_REACHED"]?>');
				bottomReached = true;
			}
		}
	}

</script>