<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?><?

$Sum = CSalePaySystemAction::GetParamValue("SHOULD_PAY");
$ShopID = CSalePaySystemAction::GetParamValue("SHOP_ID");
$scid = CSalePaySystemAction::GetParamValue("SCID");
$customerNumber = CSalePaySystemAction::GetParamValue("ORDER_ID");
$orderDate = CSalePaySystemAction::GetParamValue("ORDER_DATE");
$orderNumber = CSalePaySystemAction::GetParamValue("ORDER_ID");
$paymentType = CSalePaySystemAction::GetParamValue("PAYMENT_VALUE");

$Sum = number_format($Sum, 2, ',', '');
?>
<font class="tablebodytext">
”слугу предоставл€ет сервис онлайн-платежей <b>&laquo;яндекс.ƒеньги&raquo;</b>.<br /><br />
—умма к оплате по счету: <b><?=$Sum?> р.</b><br />
<br />
<?if(strlen(CSalePaySystemAction::GetParamValue("IS_TEST")) > 0):
	?>
	<form name="ShopForm" action="https://demomoney.yandex.ru/eshop.xml" method="post" target="_blank">
<?else:
	?>
	<form name="ShopForm" action="https://money.yandex.ru/eshop.xml" method="post">
<?endif;?>

<input name="ShopID" value="<?=$ShopID?>" type="hidden">
<input name="scid" value="<?=$scid?>" type="hidden">
<input name="customerNumber" value="<?=$customerNumber?>" type="hidden">
<input name="orderNumber" value="<?=$orderNumber?>" type="hidden">
<input name="Sum" value="<?=$Sum?>" type="hidden">
<input name="paymentType" value="<?=$paymentType?>" type="hidden">
<input name="cms_name" value="1C-Bitrix" type="hidden">

<!-- <br /> -->
<!-- ƒетали заказа:<br /> -->
<!-- <input name="OrderDetails" value="заказ є<?=$orderNumber?> (<?=$orderDate?>)" type="hidden"> -->
<br />
<input name="BuyButton" value="ќплатить" type="submit">

</font><p><font class="tablebodytext"><b>ќбратите внимание:</b> если вы откажетесь от покупки, дл€ возврата денег вам придетс€ обратитьс€ в магазин.</font></p>
</form>