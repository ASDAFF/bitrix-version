<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?><?
$ORDER_ID = IntVal($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["ID"]);
if (!is_array($arOrder))
	$arOrder = CSaleOrder::GetByID($ORDER_ID);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>����</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?=LANG_CHARSET?>">
</head>
<body bgColor="#ffffff">
<p><b>���������:</b>
<?= (CSalePaySystemAction::GetParamValue("SELLER_NAME")) ?><br>
�����: <?= (CSalePaySystemAction::GetParamValue("SELLER_ADDRESS")) ?><br>
�������: <?= (CSalePaySystemAction::GetParamValue("SELLER_PHONE")) ?><br>
���: <?= (CSalePaySystemAction::GetParamValue("SELLER_INN")) ?> / ���: <?= (CSalePaySystemAction::GetParamValue("SELLER_KPP")) ?><br>
���������� ���������:<br>
<?= (CSalePaySystemAction::GetParamValue("SELLER_RS")) ?><br>
�/� <?= (CSalePaySystemAction::GetParamValue("SELLER_KS")) ?><br>
��� <?= (CSalePaySystemAction::GetParamValue("SELLER_BIK")) ?>
</p>

<p><b>��������:</b>
<?= (CSalePaySystemAction::GetParamValue("BUYER_NAME")) ?><br>
���: <?= (CSalePaySystemAction::GetParamValue("BUYER_INN")) ?><br>
�����: <?= (CSalePaySystemAction::GetParamValue("BUYER_ADDRESS")) ?><br>
�������: <?= (CSalePaySystemAction::GetParamValue("BUYER_PHONE")) ?><br>
����: <?= (CSalePaySystemAction::GetParamValue("BUYER_FAX")) ?><br>
���������� ����: <?= (CSalePaySystemAction::GetParamValue("BUYER_PAYER_NAME")) ?><br>

<?
$arPaySys_tmp = CSalePaySystem::GetByID($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["PAY_SYSTEM_ID"], $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["PERSON_TYPE_ID"]);
echo "<br>��������� �������: [".$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["PAY_SYSTEM_ID"]."] ".$arPaySys_tmp["PSA_NAME"];
?>
</p>

<p><b>���� N:</b> <?= $ORDER_ID ?> ��  <?= (CSalePaySystemAction::GetParamValue("DATE_INSERT")) ?></p>

<?
$dbBasket = CSaleBasket::GetList(
		array("NAME" => "ASC"),
		array("ORDER_ID" => $ORDER_ID)
	);
if ($arBasket = $dbBasket->Fetch()):
	?>
	<table border="0" cellspacing="0" cellpadding="2" width="100%">
	 <tr bgcolor="#E2E2E2">
		<td align="center" style="border: 1pt solid #000000; border-right:none;">�</td>
		<td align="center" style="border: 1pt solid #000000; border-right:none;">������� �����</td>
		<td nowrap align="center" style="border: 1pt solid #000000; border-right:none;">���-��</td>
		<td nowrap align="center" style="border: 1pt solid #000000; border-right:none;">����, ���</td>
		<td nowrap align="center" style="border: 1pt solid #000000;">�����, ���</td>
	 </tr>
	<?
	$n = 1;
	$sum = 0.00;
	do
	{
		//props in busket product
		$arProdProps = array();
		$dbBasketProps = CSaleBasket::GetPropsList(
			array("SORT" => "ASC", "ID" => "DESC"),
			array(
				"BASKET_ID" => $arBasket["ID"],
				"!CODE" => array("CATALOG.XML_ID", "PRODUCT.XML_ID")
			),
			false,
			false,
			array("ID", "BASKET_ID", "NAME", "VALUE", "CODE", "SORT")
		);
		while($arBasketProps = $dbBasketProps->GetNext())
		{
			if(!empty($arBasketProps) AND $arBasketProps["VALUE"] != "")
				$arProdProps[] = $arBasketProps;
		}
		$arBasket["PROPS"] = $arProdProps;
		?>
		<tr valign="top">
			<td bgcolor="#ffffff" style="border: 1pt solid #000000; border-right:none; border-top:none;">
				<?= $n++ ?>
			</td>
			<td bgcolor="#ffffff" style="border: 1pt solid #000000; border-right:none; border-top:none;">
				<?= ("[".$arBasket["ID"]."] ".$arBasket["NAME"]); ?><br>
				<?
				foreach($arBasket["PROPS"] as $vv) 
				{
					?><small><span><?=$vv["NAME"]?>: <?=$vv["VALUE"]?></span></small><br><?
				}
				?>
			</td>
			<td align="right" bgcolor="#ffffff" style="border: 1pt solid #000000; border-right:none; border-top:none;">
				<?= $arBasket["QUANTITY"]; ?>
			</td>
			<td align="right" bgcolor="#ffffff" style="border: 1pt solid #000000; border-right:none; border-top:none;">
				<?= SaleFormatCurrency($arBasket["PRICE"], $arBasket["CURRENCY"], true) ?>
			</td>
			<td align="right" bgcolor="#ffffff" style="border: 1pt solid #000000; border-top:none;">
				<?= SaleFormatCurrency(($arBasket["PRICE"])*$arBasket["QUANTITY"], $arBasket["CURRENCY"], true) ?>
			</td>
		</tr>
		<?
		$sum += doubleval(($arBasket["PRICE"])*$arBasket["QUANTITY"]);
	}
	while ($arBasket = $dbBasket->Fetch());
	?>

	<?if (DoubleVal($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["PRICE_DELIVERY"])>0):?>
	<tr>
		<td bgcolor="#ffffff" style="border: 1pt solid #000000; border-right:none; border-top:none;">
			<?echo $n?>
		</td>
		<td bgcolor="#ffffff" style="border: 1pt solid #000000; border-right:none; border-top:none;">
			�������� <?
			$arDelivery_tmp = CSaleDelivery::GetByID($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["DELIVERY_ID"]);
			echo ((strlen($arDelivery_tmp["NAME"])>0) ? "([".$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["DELIVERY_ID"]."] " : "" );
			echo ($arDelivery_tmp["NAME"]);
			echo ((strlen($arDelivery_tmp["NAME"])>0) ? ")" : "" );
			?>
		</td>
		<td valign="top" align="right" bgcolor="#ffffff" style="border: 1pt solid #000000; border-right:none; border-top:none;">1 </td>
		<td align="right" bgcolor="#ffffff" style="border: 1pt solid #000000; border-right:none; border-top:none;">
			<?= SaleFormatCurrency($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["PRICE_DELIVERY"], $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"], true) ?>
		</td>
		<td align="right" bgcolor="#ffffff" style="border: 1pt solid #000000; border-top:none;">
			<?= SaleFormatCurrency($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["PRICE_DELIVERY"], $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"], true) ?>
		</td>
	</tr>
	<?endif?>

	<?
	$dbTaxList = CSaleOrderTax::GetList(
			array("APPLY_ORDER" => "ASC"),
			array("ORDER_ID"=>$ORDER_ID)
		);
	while ($arTaxList = $dbTaxList->Fetch())
	{
		?>
		<tr>
			<td align="right" bgcolor="#ffffff" colspan="4" style="border: 1pt solid #000000; border-right:none; border-top:none;">
				<?
				if ($arTaxList["IS_IN_PRICE"]=="Y")
				{
					echo "� ��� ����� ";
				}
				echo ($arTaxList["TAX_NAME"]);
				if ($arTaxList["IS_PERCENT"]=="Y")
				{
					echo " (".$arTaxList["VALUE"]."%)";
				}
				?>:
			</td>
			<td align="right" bgcolor="#ffffff" style="border: 1pt solid #000000; border-top:none;">
				<?echo SaleFormatCurrency($arTaxList["VALUE_MONEY"], $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"], true)?>
			</td>
		</tr>
		<?
	}
	
	if(DoubleVal($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["SUM_PAID"]) > 0)
	{
		?>
		<tr>
			<td align="right" bgcolor="#ffffff" colspan="4" style="border: 1pt solid #000000; border-right:none; border-top:none;">��� ��������:</td>
			<td align="right" bgcolor="#ffffff" style="border: 1pt solid #000000; border-top:none;"><?= SaleFormatCurrency($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["SUM_PAID"], $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"], true) ?></td>
		</tr>
		<?
	}	
	if(DoubleVal($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["DISCOUNT_VALUE"]) > 0)
	{
		?>
		<tr>
			<td align="right" bgcolor="#ffffff" colspan="4" style="border: 1pt solid #000000; border-right:none; border-top:none;">������:</td>
			<td align="right" bgcolor="#ffffff" style="border: 1pt solid #000000; border-top:none;"><?= SaleFormatCurrency($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["DISCOUNT_VALUE"], $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"], true) ?></td>
		</tr>
		<?
	}
	?>
	<tr>
		<td align="right" bgcolor="#ffffff" colspan="4" style="border: 1pt solid #000000; border-right:none; border-top:none;">�����:</td>
		<td align="right" bgcolor="#ffffff" style="border: 1pt solid #000000; border-top:none;" nowrap><?= SaleFormatCurrency($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["SHOULD_PAY"], $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"], true) ?></td>
	</tr>
</table>
<?endif?>
<p><b>����� � ������:</b> 
	<?
	if ($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"]=="RUR" || $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"]=="RUB")
	{
		echo Number2Word_Rus($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["SHOULD_PAY"]);
	}
	else
	{
		echo SaleFormatCurrency($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["SHOULD_PAY"], $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"]);
	}
	?></p>
<p>
<div align="right" style="padding-right:30px;"><?= (CSalePaySystemAction::GetParamValue("DATE_INSERT")) ?></div><br />
<?
$stamp = CSalePaySystemAction::GetParamValue("PATH_TO_STAMP");
if (strlen($stamp) > 0)
{
	if (file_exists($_SERVER["DOCUMENT_ROOT"].$stamp) && is_file($_SERVER["DOCUMENT_ROOT"].$stamp))
	{
		list($width, $height, $type, $attr) = CFile::GetImageSize($_SERVER["DOCUMENT_ROOT"].$stamp);
		?><img align="right" src="<?= $stamp ?>" <?= $attr ?> border="0" alt=""><br clear="all"><?
	}
}
?>
</p>
<p><font size="2">� ������ ������������� ������� �� ��������� ���� �������� � ������� ����
���������� ���� �� ��� ������� �����, �������� ��������� �� ����� �����
������������ ��������� ���� ������ � ������ ��������������� ��������� ����� �������
� ��������� ���� �� �������.<br><br>
� ��������� ��������� ����������� ������� - "������ �� ����� � <?echo $ORDER_ID ?> �� <?= (CSalePaySystemAction::GetParamValue("DATE_INSERT")) ?>".<br><br>
��������� ������ ������ ����� ������� ����� �� ��������� ���� ��������.
</font></p>

<p>&nbsp;</p>

</body>
</html>