<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?><?
$ORDER_ID = IntVal($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["ID"]);
?>
<div class="tablebodytext">
<form ACTION="http://127.0.0.1:8129/wallet" METHOD="POST" target="_blank">
<input NAME="currency" value="643" type="hidden">
<input NAME="PayManner" TYPE="HIDDEN" value="paycash">
<input NAME="invoice" TYPE="HIDDEN" value="<?= $ORDER_ID ?>">
�� ������ �������� ����� ������� <b>������.������</b>.<br><br>
C��� � <?= htmlspecialcharsEx($ORDER_ID." �� ".$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["DATE_INSERT"]) ?><br>
����� � ������ �� �����: <b><?echo SaleFormatCurrency($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["SHOULD_PAY"], $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"]) ?></b><br>
<br>
<input name="InvoiceArticlesNames" TYPE="HIDDEN" value="Order &nbsp;<?= $ORDER_ID ?>&nbsp(<?= htmlspecialcharsEx($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["DATE_INSERT"]) ?>)">
<input type="HIDDEN" name="sum" value="<?= htmlspecialcharsbx($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["SHOULD_PAY"]) ?>">
<input type=hidden name="ShopID" value="<?= htmlspecialcharsbx(CSalePaySystemAction::GetParamValue("SHOP_ACCOUNT")) ?>">
<input type=hidden name="wbp_InactivityPeriod" value="2">
<input type=hidden name="wbp_ShopAddress" value="195.239.63.41:8128">
<input type=hidden name="wbp_ShopKeyID" value="<?= htmlspecialcharsbx(CSalePaySystemAction::GetParamValue("SHOP_KEY_ID")) ?>">
<input type=hidden name="wbp_ShopEncryptionKey" value="<?= htmlspecialcharsbx(CSalePaySystemAction::GetParamValue("SHOP_KEY")) ?>">
<input type=hidden name="wbp_ShopErrorInfo" value="">
<input type=hidden name="wbp_Version" value="1.0">
<br>
������ ������:<br>
<textarea rows="5" name="OrderDetails" cols="60">
����� No <?= $ORDER_ID." �� ".htmlspecialcharsbx($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["DATE_INSERT"]) ?>
</textarea><br>
<br>
<input type="Submit" name="Ok" value="��������� ������">
</form>

<p><b>��������!</b> ������� ������� �� ��������� ������� ������.������ - ����������, ����������, ������ ����������� ��� ������ ������.</p>

<p><b>��������� ������</b></p>

<p>����� �������� ������ "��������", ��������� ��� <i>������� "������.������" � ��� �������</i>. ����� ������� ������ "��������" ������� �������� ������ �������� "������.������" ���������� �� ������, ���������� �������� ������. ���������� �� ������ ��������� ����������� �������� �������� ��������.<p>

<p>��� ������� ����������� ��� ���������� ������. ���� �� ��������, � � ��� ���������� ����� �� �����, �� ��� ������� �������� �������� ������ �������� ����������� ������ � ����������� ����� ����������� �������� ����. ����� ����, ��� �� �������� ���� � ������� ������.������, �� ������ ������, ����� ����� ��������� � ��������� �������� �������� ��������. ��������, ������ ������� �������� � ���������������� ������ � ������� ����� ������ ������: � 10.00 �� 18.00, �� ������.</p>
