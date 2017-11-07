<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if(!CModule::IncludeModule("sale") || !CModule::IncludeModule("currency"))
	return false;

$saleModulePermissions = $APPLICATION->GetGroupRight("sale");
if ($saleModulePermissions == "D")
	return false;

if (strlen($arGadgetParams["SITE_ID"]) > 0)
{
	if (strlen($arGadgetParams["TITLE_STD"]) <= 0)	
	{
		$rsSites = CSite::GetByID($arGadgetParams["SITE_ID"]);
		if ($arSite = $rsSites->GetNext())
			$arGadget["TITLE"] .= " / [".$arSite["ID"]."] ".$arSite["NAME"];
	}
}

$arGadgetParams["RND_STRING"] = randString(8);

$arFilter = Array();
if (strlen($arGadgetParams["SITE_ID"]) > 0)
	$arFilter["LID"] = $arGadgetParams["SITE_ID"];
if($arGadgetParams["PERIOD"] == "WEEK")
{
	$arFilter[">=DATE_INSERT"] = ConvertTimeStamp(AddToTimeStamp(Array("DD" => -7)));
	$cache_time = 60*60*4;
}
elseif(strlen($arGadgetParams["PERIOD"]) <= 0 || $arGadgetParams["PERIOD"] == "MONTH")
{
	$arFilter[">=DATE_INSERT"] = ConvertTimeStamp(AddToTimeStamp(Array("MM" => -1)));
	$cache_time = 60*60*12;
}
elseif($arGadgetParams["PERIOD"] == "QUATER")
{
	$arFilter[">=DATE_INSERT"] = ConvertTimeStamp(AddToTimeStamp(Array("MM" => -4))); 
	$cache_time = 60*60*24;
}
elseif($arGadgetParams["PERIOD"] == "YEAR")
{
	$arFilter[">=DATE_INSERT"] = ConvertTimeStamp(AddToTimeStamp(Array("YYYY" => -1)));
	$cache_time = 60*60*24;
}
if(IntVal($arGadgetParams["LIMIT"]) <= 0)
	$arGadgetParams["LIMIT"] = 5;

$obCache = new CPHPCache; 
$cache_id = "admin_products_".md5(serialize($arFilter))."_".$arGadgetParams["LIMIT"]; 
if($obCache->InitCache($cache_time, $cache_id, "/"))
{
	$arResult = $obCache->GetVars();
}
else
{
	$arResult = Array();
	$arResult["SEL"] = Array();
	$arFilter["PAYED"] = "Y";
	$dbR = CSaleProduct::GetBestSellerList("AMOUNT", array(), $arFilter, $arGadgetParams["LIMIT"]);
	while($arR = $dbR->Fetch())
	{
		$arResult["SEL"][] = $arR;
	}

	$arResult["VIEWED"] = Array();
	$arFilter[">=DATE_VISIT"] = $arFilter[">=DATE_INSERT"];
	unset($arFilter[">=DATE_INSERT"]);
	$dbR = CSaleViewedProduct::GetList(Array("ID" => "DESC"), $arFilter, Array("PRODUCT_ID", "NAME", "CURRENCY","COUNT" => "ID", "AVG" => "PRICE"), Array("nTopCount" => $arGadgetParams["LIMIT"]));
	while($arR = $dbR->Fetch())
	{
		$arResult["VIEWED"][] = $arR;
	}
}

if($obCache->StartDataCache())
{
	$obCache->EndDataCache($arResult);
}

?><script type="text/javascript">
	var gdSaleProductsTabControl_<?=$arGadgetParams["RND_STRING"]?> = false;
	BX.ready(function(){
		gdSaleProductsTabControl_<?=$arGadgetParams["RND_STRING"]?> = new gdTabControl('bx_gd_tabset_sale_products_<?=$arGadgetParams["RND_STRING"]?>');
	});
</script><?

$aTabs = array(
	array(
		"DIV" => "bx_gd_sale_products1_".$arGadgetParams["RND_STRING"],
		"TAB" => GetMessage("GD_PRD_TAB_1"),
		"ICON" => "",
		"TITLE" => "",
		"ONSELECT" => "gdSaleProductsTabControl_".$arGadgetParams["RND_STRING"].".SelectTab('bx_gd_sale_products1_".$arGadgetParams["RND_STRING"]."');"
	),
	array(
		"DIV" => "bx_gd_sale_products2_".$arGadgetParams["RND_STRING"],
		"TAB" => GetMessage("GD_PRD_TAB_2"),
		"ICON" => "",
		"TITLE" => "",
		"ONSELECT" => "gdSaleProductsTabControl_".$arGadgetParams["RND_STRING"].".SelectTab('bx_gd_sale_products2_".$arGadgetParams["RND_STRING"]."');"
	)
);

$tabControl = new CAdminViewTabControl("salePrdTabControl", $aTabs);

?><div class="bx-gadgets-tabs-wrap" id="bx_gd_tabset_sale_products_<?=$arGadgetParams["RND_STRING"]?>"><?

	$tabControl->Begin();
	for($i = 0; $i < count($aTabs); $i++)
		$tabControl->BeginNextTab();
	$tabControl->End();

	?><div class="bx-gadgets-tabs-cont"><?
		for($i = 0; $i < count($aTabs); $i++)
		{
			?><div id="<?=$aTabs[$i]["DIV"]?>_content" style="display: <?=($i==0 ? "block" : "none")?>;" class="bx-gadgets-tab-container"><?
				if ($i == 0)
				{
					if (count($arResult["SEL"]) > 0)
					{
						?><table class="bx-gadgets-table">
							<tbody>
								<tr>
									<th><?=GetMessage("GD_PRD_NAME")?></th>
									<th><?=GetMessage("GD_PRD_QUANTITY")?></th>
									<th><?=GetMessage("GD_PRD_AV_PRICE")?></th>
									<th><?=GetMessage("GD_PRD_SUM")?></th>
								</tr><?
								foreach($arResult["SEL"] as $val)
								{
									?><tr>
										<td><?=htmlspecialcharsbx($val["NAME"])?></td>
										<td align="right"><?=IntVal($val["QUANTITY"])?></td>
										<td align="right" nowrap><?=CurrencyFormat(DoubleVal($val["AVG_PRICE"]), $val["CURRENCY"])?></td>
										<td align="right" nowrap><?=CurrencyFormat(DoubleVal($val["PRICE"]), $val["CURRENCY"])?></td>
									</tr><?
								}
							?></tbody>
						</table><?
					}
					else
					{
						?><div align="center" class="bx-gadgets-content-padding-rl bx-gadgets-content-padding-t"><?=GetMessage("GD_PRD_NO_DATA")?></div><?
					}
				}
				elseif ($i == 1)
				{
					if (count($arResult["VIEWED"]) > 0)
					{				
						?><table class="bx-gadgets-table">
							<tbody>
								<tr>
									<th><?=GetMessage("GD_PRD_NAME")?></th>
									<th><?=GetMessage("GD_PRD_VIEWED")?></th>
									<th><?=GetMessage("GD_PRD_PRICE")?></th>
								</tr><?
								foreach($arResult["VIEWED"] as $val)
								{
									?><tr>
										<td><?=htmlspecialcharsbx($val["NAME"])?></td>
										<td align="right"><?=IntVal($val["ID"])?></td>
										<td align="right" nowrap><?=(DoubleVal($val["PRICE"]) > 0 ? CurrencyFormat(DoubleVal($val["PRICE"]), $val["CURRENCY"]) : "")?></td>
									</tr><?
								}
							?></tbody>
						</table><?
					}
					else
					{
						?><div align="center" class="bx-gadgets-content-padding-rl bx-gadgets-content-padding-t"><?=GetMessage("GD_PRD_NO_DATA")?></div><?
					}
				}
			?></div><?
		}
	?></div>
</div>