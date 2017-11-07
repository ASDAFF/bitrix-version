<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(is_array($arResult["HISTORY"]) && !empty($arResult["HISTORY"]))
{
	$arExcludedFields = array(
	"ID", "H_USER_ID", "H_DATE_INSERT", "H_CURRENCY", "H_ORDER_ID", "EMP_CANCELED_ID",
	"EMP_STATUS_ID", "EMP_ALLOW_DELIVERY_ID", "STATUS_ID", "PAYED", "ALLOW_DELIVERY",
	"CANCELED", "PRICE", "STAT_GID","RECOUNT_FLAG", "DELIVERY_ID");

	$mad = new CAdminMobileDetail;

	foreach ($arResult["HISTORY"] as $arItemHistory)
	{
		$stmp = MakeTimeStamp($arItemHistory["H_DATE_INSERT"], "DD.MM.YYYY HH:MI:SS");
		$dateInsert = date("d.m.Y", $stmp).' <div class="time_icon">'.date("H:i", $stmp).'</div>';

		$arSection = array(
					"TITLE" => $dateInsert,
					"ROWS" => array(
						array(
							"TITLE" => GetMessage("SMOH_USER").":",
							"VALUE" => $arItemHistory['USER']['LOGIN']),
						array(
							"TITLE" => GetMessage("SMOH_FIO").":",
							"VALUE" => ($arItemHistory['USER']['NAME']." ".$arItemHistory['USER']['LAST_NAME'])),
						),
					);

		foreach ($arItemHistory as $key => $value)
		{
			if(is_array($value) || strlen($value) <= 0)
				continue;

			switch($key)
			{

				case "PAYED":
					$arSection["ROWS"][] = array(
						"TITLE" => GetMessage("SMOH_PAYED").":",
						"VALUE" => $arItemHistory['PAYED'] == 'Y' ? GetMessage("SMOH_YES") : GetMessage("SMOH_NO"));
					continue 2;

				case "ALLOW_DELIVERY":
					$arSection["ROWS"][] = array(
						"TITLE" => GetMessage("SMOH_ALLOW_DELIVERY").":",
						"VALUE" => $arItemHistory['ALLOW_DELIVERY'] == 'Y' ? GetMessage("SMOH_YES") : GetMessage("SMOH_NO"));
					continue 2;

				case "CANCELED":
					$arSection["ROWS"][] = array(
						"TITLE" => GetMessage("SMOH_CANCELED").":",
						"VALUE" => $arItemHistory['CANCELED'] == 'Y' ? GetMessage("SMOH_YES") : GetMessage("SMOH_NO"));
					continue 2;

				case "STATUS_ID":
					$arSection["ROWS"][] = array(
						"TITLE" => GetMessage("SMOH_STATUS_ID").":",
						"VALUE" => $arResult["STATUSES"][$arItemHistory['STATUS_ID']]);
					continue 2;

				case "PRICE":
					$arSection["ROWS"][] = array(
						"TITLE" => GetMessage("SMOH_PRICE").":",
						"VALUE" => SaleFormatCurrency($arItemHistory["PRICE"], $arItemHistory["H_CURRENCY"]));
					continue 2;

				case "PRICE_DELIVERY":
					$arSection["ROWS"][] = array(
						"TITLE" => GetMessage("SMOH_PRICE_DELIVERY").":",
						"VALUE" => SaleFormatCurrency($arItemHistory["PRICE_DELIVERY"], $arItemHistory["H_CURRENCY"]));
					continue 2;

				case "DISCOUNT_VALUE":
					$arSection["ROWS"][] = array(
						"TITLE" => GetMessage("SMOH_DISCOUNT_VALUE").":",
						"VALUE" => SaleFormatCurrency($arItemHistory["DISCOUNT_VALUE"], $arItemHistory["H_CURRENCY"]));
					continue 2;

				case "TAX_VALUE":
					$arSection["ROWS"][] = array(
						"TITLE" => GetMessage("SMOH_TAX_VALUE").":",
						"VALUE" => SaleFormatCurrency($arItemHistory["TAX_VALUE"], $arItemHistory["H_CURRENCY"]));
					continue 2;

				case "SUM_PAID":
					$arSection["ROWS"][] = array(
						"TITLE" => GetMessage("SMOH_SUM_PAID").":",
						"VALUE" => SaleFormatCurrency($arItemHistory["SUM_PAID"], $arItemHistory["H_CURRENCY"]));
					continue 2;

				case "EMP_PAYED_ID":
					$arSection["ROWS"][] = array(
						"TITLE" => GetMessage("SMOH_FN_EMP_PAYED_ID").":",
						"VALUE" => CSaleMobileOrderUtils::GetFormatedUserName($arItemHistory["EMP_PAYED_ID"]));
					continue 2;

				case "DELIVERY":
					$arSection["ROWS"][] = array("TITLE" => GetMessage("SMOH_DELIVERY_ID").":", "VALUE" => $arItemHistory['DELIVERY']);
					continue 2;

				case "PAY_SYSTEM_ID":
					$arSection["ROWS"][] = array(
						"TITLE" => GetMessage("SMOH_PAY_SYSTEM_ID").":",
						"VALUE" => $arResult["PAY_SYSTEMS"][$arItemHistory['PAY_SYSTEM_ID']]);
					continue 2;

				case "MARKED":
					$arSection["ROWS"][] = array(
						"TITLE" => GetMessage("SMOH_FN_MARKED").":",
						"VALUE" => $arItemHistory['MARKED'] == 'Y' ? GetMessage("SMOH_YES") : GetMessage("SMOH_NO"));
					continue 2;

				case "DEDUCTED":
					$arSection["ROWS"][] = array(
						"TITLE" => GetMessage("SMOH_FN_DEDUCTED").":",
						"VALUE" => $arItemHistory['DEDUCTED'] == 'Y' ? GetMessage("SMOH_YES") : GetMessage("SMOH_NO"));
					continue 2;
			}

			if(in_array($key, $arExcludedFields))
				continue;

			$langMess = GetMessage("SMOH_FN_".$key);

			$arSection["ROWS"][] = array("TITLE" => $langMess ? $langMess : $key, "VALUE" => $value);
		}

		$mad->addSection($arSection);
	}

	echo $mad->getHtml();
}
else
{
	echo GetMessage("SMOH_HISTORY_EMPTY");
}
?>
