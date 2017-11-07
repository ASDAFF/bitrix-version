<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?>
<?
$ORDER_ID = IntVal(CSalePaySystemAction::GetParamValue("ORDER_ID"));
$arOrder = CSaleOrder::GetByID($ORDER_ID);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Рахунок</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?=LANG_CHARSET?>">
</head>
<body bgColor="#fff">

<p><b>Рахунок на оплату №<?= $ORDER_ID ?> від <?= (CSalePaySystemAction::GetParamValue("DATE_INSERT")) ?></b></p>

<p><span style="display:inline-block;width:100px;">Постачальник:</span> <?= (CSalePaySystemAction::GetParamValue("SELLER_NAME")) ?><br>


<span style="margin-left:103px;">Р/р <?= (CSalePaySystemAction::GetParamValue("SELLER_RS")) ?>, Банк <?= (CSalePaySystemAction::GetParamValue("SELLER_BANK")) ?>, МФО <?= (CSalePaySystemAction::GetParamValue("SELLER_MFO")) ?></span><br>
<span style="margin-left:103px;"> Юридична адреса: <?= (CSalePaySystemAction::GetParamValue("SELLER_ADDRESS")) ?>, тел.: <?= (CSalePaySystemAction::GetParamValue("SELLER_PHONE")) ?></span><br>
<span style="margin-left:103px;">ЄДРПОУ: <?= (CSalePaySystemAction::GetParamValue("SELLER_EDRPOY")) ?>,
	ІПН: <?= (CSalePaySystemAction::GetParamValue("SELLER_IPN")) ?>,
	№ свід. ПДВ: <?= (CSalePaySystemAction::GetParamValue("SELLER_PDV")) ?></span><br>
<?if (strlen(CSalePaySystemAction::GetParamValue("SELLER_SYS")) > 0):?>
	<span style="margin-left:103px;"><?= (CSalePaySystemAction::GetParamValue("SELLER_SYS")) ?></span><br>
<?endif;?>

<p><span style="display:inline-block;width:100px;">Покупець:</span> <?= (CSalePaySystemAction::GetParamValue("BUYER_NAME")) ?><br>
<span style="margin-left:103px;">
	<?
	$bPhone = false;
	if (strlen(CSalePaySystemAction::GetParamValue("BUYER_PHONE")) > 0)
	{
		$bPhone = true;
		echo "тел.: ".CSalePaySystemAction::GetParamValue("BUYER_PHONE");
	}
	if (strlen(CSalePaySystemAction::GetParamValue("BUYER_FAX")) > 0)
	{
		if ($bPhone)
			echo ", ";
		echo "факс: ".CSalePaySystemAction::GetParamValue("BUYER_FAX");
	}
	?>
</span><br>
<?if (strlen(CSalePaySystemAction::GetParamValue("BUYER_ADDRESS")) > 0):?>
<span style="margin-left:103px;">
	Адреса: <?= (CSalePaySystemAction::GetParamValue("BUYER_ADDRESS")) ?>
</span>
<?endif;?>
</p>

<?if (strlen(CSalePaySystemAction::GetParamValue("BUYER_DOGOVOR")) > 0):?>
	<p>Договір: <?= (CSalePaySystemAction::GetParamValue("BUYER_DOGOVOR")) ?></p>
<?endif;?>

<?
$dbBasket = CSaleBasket::GetList(
		array("NAME" => "ASC"),
		array("ORDER_ID" => $ORDER_ID)
	);
if ($arBasket = $dbBasket->Fetch()):
	?>
	<table cellspacing="0" cellpadding="2" width="100%">
	<tr bgcolor="#E2E2E2">
		<td align="center" style="border: 1pt solid #000; border-right:none;">№</td>
		<td align="center" style="border: 1pt solid #000; border-right:none;">Товар/Послуга</td>
		<td nowrap align="center" style="border: 1pt solid #000; border-right:none;">Кіл-сть</td>
		<td nowrap align="center" style="border: 1pt solid #000; border-right:none;">Од.</td>
		<td nowrap align="center" style="border: 1pt solid #000; border-right:none;">Ціна з ПДВ</td>
		<td nowrap align="center" style="border: 1pt solid #000;">Сума з ПДВ</td>
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
			<td bgcolor="#fff" style="border: 1pt solid #000; border-right:none; border-top:none;">
				<?= $n++ ?>
			</td>
			<td bgcolor="#fff" style="border: 1pt solid #000; border-right:none; border-top:none;">
				<?= ("[".$arBasket["ID"]."] ".$arBasket["NAME"]); ?><br>
				<?
				if (count($arBasket["PROPS"]) > 0)
				{
					foreach($arBasket["PROPS"] as $vv)
						?><small><span><?=$vv["NAME"]?>: <?=$vv["VALUE"]?></span></small><br><?
				}
				?>
			</td>
			<td align="right" bgcolor="#fff" style="border: 1pt solid #000; border-right:none; border-top:none;">
				<?= $arBasket["QUANTITY"]; ?>
			</td>
			<td bgcolor="#fff" style="border: 1pt solid #000; border-right:none; border-top:none;">шт.</td>
			<td align="right" bgcolor="#fff" style="border: 1pt solid #000; border-right:none; border-top:none;">
				<?= SaleFormatCurrency($arBasket["PRICE"], $arBasket["CURRENCY"], true) ?>
			</td>
			<td align="right" bgcolor="#fff" style="border: 1pt solid #000; border-top:none;">
				<?= SaleFormatCurrency(($arBasket["PRICE"])*$arBasket["QUANTITY"], $arBasket["CURRENCY"], true) ?>
			</td>
		</tr>
		<?
		$sum += doubleval(($arBasket["PRICE"])*$arBasket["QUANTITY"]);
	}
	while ($arBasket = $dbBasket->Fetch());
	$n--;
	?>

	<?if (floatval($arOrder["PRICE_DELIVERY"])>0):?>
	<?$n++;?>
	<tr>
		<td bgcolor="#fff" style="border: 1pt solid #000; border-right:none; border-top:none;">
			<?echo $n?>
		</td>
		<td bgcolor="#fff" style="border: 1pt solid #000; border-right:none; border-top:none;">
			Доставка <?
			$arDelivery_tmp = CSaleDelivery::GetByID($arOrder["DELIVERY_ID"]);
			echo ((strlen($arDelivery_tmp["NAME"])>0) ? "([".$arOrder["DELIVERY_ID"]."] " : "" );
			echo ($arDelivery_tmp["NAME"]);
			echo ((strlen($arDelivery_tmp["NAME"])>0) ? ")" : "" );
			?>
		</td>
		<td valign="top" align="right" bgcolor="#fff" style="border: 1pt solid #000; border-right:none; border-top:none;">1</td>
		<td bgcolor="#fff" style="border: 1pt solid #000; border-right:none; border-top:none;">&nbsp;</td>
		<td align="right" bgcolor="#fff" style="border: 1pt solid #000; border-right:none; border-top:none;">
			<?= SaleFormatCurrency($arOrder["PRICE_DELIVERY"], $arOrder["CURRENCY"], true) ?>
		</td>
		<td align="right" bgcolor="#fff" style="border: 1pt solid #000; border-top:none;">
			<?= SaleFormatCurrency($arOrder["PRICE_DELIVERY"], $arOrder["CURRENCY"], true) ?>
		</td>
	</tr>
	<?endif?>

	<?
	$orderTax = 0;
	$dbTaxList = CSaleOrderTax::GetList(
			array("APPLY_ORDER" => "ASC"),
			array("ORDER_ID"=>$ORDER_ID)
		);
	while ($arTaxList = $dbTaxList->Fetch())
	{
		?>
		<tr>
			<td align="right" bgcolor="#fff" colspan="5" style="border: 1pt solid #000; border-right:none; border-top:none;font-weight:bold;">
				<?
				if ($arTaxList["IS_IN_PRICE"]=="Y")
				{
					echo "У тому числі ";
				}
				echo ($arTaxList["TAX_NAME"]);
				if ($arTaxList["IS_PERCENT"]=="Y")
				{
					echo " (".$arTaxList["VALUE"]."%)";
				}
				$orderTax += $arTaxList["VALUE_MONEY"];
				?>:
			</td>
			<td align="right" bgcolor="#fff" style="border: 1pt solid #000; border-top:none;">
				<?echo SaleFormatCurrency($arTaxList["VALUE_MONEY"], $arOrder["CURRENCY"], true)?>
			</td>
		</tr>
		<?
	}
	
	if(floatval($arOrder["SUM_PAID"]) > 0)
	{
		?>
		<tr>
			<td align="right" bgcolor="#fff" colspan="5" style="border: 1pt solid #000; border-right:none; border-top:none;font-weight:bold;">Вже сплачено:</td>
			<td align="right" bgcolor="#fff" style="border: 1pt solid #000; border-top:none;"><?= SaleFormatCurrency($arOrder["SUM_PAID"], $arOrder["CURRENCY"], true) ?></td>
		</tr>
		<?
	}	
	if(floatval($arOrder["DISCOUNT_VALUE"]) > 0)
	{
		?>
		<tr>
			<td align="right" bgcolor="#ffffff" colspan="5" style="border: 1pt solid #000000; border-right:none; border-top:none;font-weight:bold;">Знижка:</td>
			<td align="right" bgcolor="#ffffff" style="border: 1pt solid #000000; border-top:none;"><?= SaleFormatCurrency($arOrder["DISCOUNT_VALUE"], $arOrder["CURRENCY"], true) ?></td>
		</tr>
		<?
	}
	?>

	<tr>
		<td align="right" bgcolor="#fff" colspan="5" style="border: 1pt solid #000; border-right:none; border-top:none;font-weight:bold;">Всього:</td>
		<td align="right" bgcolor="#fff" style="border: 1pt solid #000; border-top:none;" nowrap><?= SaleFormatCurrency($arOrder["PRICE"], $arOrder["CURRENCY"], true) ?></td>
	</tr>

</table>
<?endif?>


<br>
<span style="font-weight: bold;">Всього найменувань: <?=$n;?>, на суму
	<?
	if ($arOrder["CURRENCY"]=="UAH" || $arOrder["CURRENCY"]=="UAH")
		echo Number2Word_Rus($sum, "Y", $arOrder["CURRENCY"]);
	else
		echo SaleFormatCurrency($arOrder["PRICE"], $arOrder["CURRENCY"]);
	?>
</span><br>

<?if ($orderTax > 0):?>
<span style="font-weight: bold;">У т.ч. ПДВ:
	<?
	if ($arOrder["CURRENCY"]=="UAH" || $arOrder["CURRENCY"]=="UAH")
		echo Number2Word_Rus($orderTax, "Y", $arOrder["CURRENCY"]);
	else
		echo SaleFormatCurrency($orderTax, $arOrder["CURRENCY"]);
	?>
</span>
<?endif;?>

<br>
<div style="border-bottom: 2px solid #000;width:100%;">&nbsp;</div>
<br>
<span><b>Виписав(ла):</b>
<input size="25" style="border:0px solid #000;font-size:16px;font-style:bold;text-decoration: underline;" type="text" value="_______________________ "></span>

<span style="margin-left:100px;"><b>Посада:</b>
<input size="25" style="border:0px solid #000;font-size:16px;font-style:bold;text-decoration: underline;" type="text" value="_______________________ "></span>


<p>
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


<p>&nbsp;</p>

</body>
</html>