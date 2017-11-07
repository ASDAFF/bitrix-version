<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?><?
$strMerchantID = CSalePaySystemAction::GetParamValue("SHOP_ACCOUNT");
$strMerchantName = CSalePaySystemAction::GetParamValue("SHOP_NAME");

$ORDER_ID = IntVal($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["ID"]);
?>
<div class="tablebodytext">
<p>
¬ы хотите оплатить по —март-карте &quot;»мпэксбанка&quot; через процессинговый центр платежной системы <b>»ћѕЁ —Ѕанка</b>.<br><br>
Cчет є <?= htmlspecialcharsEx($ORDER_ID." от ".$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["DATE_INSERT"]) ?><br>
—умма к оплате по счету: <b><?= SaleFormatCurrency($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["SHOULD_PAY"], $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"])."&nbsp;"?></b>
</p>

<form method="post" action="https://www.impexbank.ru/servlets/SPCardPaymentServlet">
<input type="hidden" name="Order_ID" value="<?= $ORDER_ID ?>"><br>
<input type="hidden" name="Amount" value="<?= htmlspecialcharsbx($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["SHOULD_PAY"]) ?>"><br>
<input type="hidden" name="Formtype" value="AuthForm">
<input type="hidden" name="Merchant_ID" value="<?= htmlspecialcharsbx($strMerchantID) ?>">
<input type="hidden" name="Merchant_Name" value="<?= htmlspecialcharsbx($strMerchantName) ?>">
<input type="hidden" name="Currency" value="<?= htmlspecialcharsbx($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"]) ?>">
<input type="submit" value="ќплатить">
</form>

<p>
<b>ќбратите внимание!</b><br><br>
¬се финансовые операции осуществл€ютс€ в процессинговом центре платежной системы »ћѕЁ —Ѕанка. 
¬се данные, необходимые дл€ осуществлени€ платежа, гарантированно защищены платежной системой »ћѕЁ —Ѕанка.
</p>

</div>