<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?><?
$ORDER_ID = IntVal($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["ID"]);
?>
<form method=post action=http://www.kreditpilot.com/servlets/com.kreditpilot.server.FirstStep target=_blank>
<font class="tablebodytext">
<input type=hidden name=BillNumber value="<?echo $ORDER_ID?>">
�� ������ �������� ����� ������� <b>www.kreditpilot.ru</b>.<br>
C��� � <?echo $ORDER_ID." �� ".CSalePaySystemAction::GetParamValue("DATE_INSERT")?><br>
<input type=hidden name=BillDescription value="Order &nbsp;<?echo $ORDER_ID?>&nbsp">
<input type=hidden name=BillSum value="<?echo CSalePaySystemAction::GetParamValue("SHOULD_PAY")?>">
����� � ������ �� �����: <?echo SaleFormatCurrency(CSalePaySystemAction::GetParamValue("SHOULD_PAY"), CSalePaySystemAction::GetParamValue("CURRENCY"))?><br>
<input type=hidden name=BillShopId value="<?echo CSalePaySystemAction::GetParamValue("SHOP_ID")?>">
<input type=hidden name=BillDate value="<?echo CSalePaySystemAction::GetParamValue("DATE_INSERT")?>">
<input type=hidden name=BillCurrency value="<?echo (CSalePaySystemAction::GetParamValue("CURRENCY") == "RUR"? "���.":"")?>">
<br>
<input type=submit name=sub value="��������">
</font>
</form>
