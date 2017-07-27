<?
/** @global CUser $USER */
use Bitrix\Main;
use Bitrix\Currency;

if ($USER->CanDoOperation('catalog_read') || $USER->CanDoOperation('catalog_price'))
{
	IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/catalog/templates/product_edit.php');

	$currencyList = array();
	foreach (Currency\CurrencyManager::getCurrencyList() as $currency => $currencyName)
	{
		$currencyList[$currency] = array(
			'CURRENCY' => $currency,
			'FULL_NAME' => htmlspecialcharsex($currencyName),
			'FULL_NAME_JS' => CUtil::JSEscape(htmlspecialcharsbx($currencyName))
		);
	}
	unset($currency, $currencyName);

	$IBLOCK_ID = (int)$IBLOCK_ID;
	if ($IBLOCK_ID <= 0)
		return;
	$MENU_SECTION_ID = (int)$MENU_SECTION_ID;
	$PRODUCT_ID = ($ID > 0 ? CIBlockElement::GetRealElement($ID) : 0);
	$arBaseProduct = false;
	$periodTimeTypes = array();
	if ($arMainCatalog['SUBSCRIPTION'] == 'Y')
	{
		$arDefProduct = array(
			'QUANTITY' => '',
			'QUANTITY_RESERVED' => '',
			'VAT_ID' => 0,
			'VAT_INCLUDED' => 'N',
			'QUANTITY_TRACE_ORIG' => 'D',
			'CAN_BUY_ZERO_ORIG' => 'D',
			'NEGATIVE_AMOUNT_TRACE_ORIG' => 'D',
			'PRICE_TYPE' => '',
			'RECUR_SCHEME_TYPE' => '',
			'RECUR_SCHEME_LENGTH' => '',
			'TRIAL_PRICE_ID' => '',
			'WITHOUT_ORDER' => '',
			'PURCHASING_PRICE' => '',
			'PURCHASING_CURRENCY' => '',
			'BARCODE_MULTI' => '',
			'SUBSCRIBE_ORIG' => 'D'
		);
		$periodTimeTypes = CCatalogProduct::GetTimePeriodTypes(true);
	}
	else
	{
		$arDefProduct = array(
			'QUANTITY' => '',
			'QUANTITY_RESERVED' => '',
			'WEIGHT' => '',
			'WIDTH' => '',
			'LENGTH' => '',
			'HEIGHT' => '',
			'MEASURE' => '',
			'VAT_ID' => 0,
			'VAT_INCLUDED' => 'N',
			'QUANTITY_TRACE_ORIG' => 'D',
			'CAN_BUY_ZERO_ORIG' => 'D',
			'NEGATIVE_AMOUNT_TRACE_ORIG' => 'D',
			'PURCHASING_PRICE' => '',
			'PURCHASING_CURRENCY' => '',
			'BARCODE_MULTI' => '',
			'SUBSCRIBE_ORIG' => 'D'
		);
	}
	if ($PRODUCT_ID > 0)
	{
		$bReadOnly = !($USER->CanDoOperation('catalog_price') && CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $PRODUCT_ID, "element_edit_price"));
		if ($arMainCatalog['SUBSCRIPTION'] == 'Y')
		{
			$arProductSelect = array(
				'ID', 'QUANTITY', 'QUANTITY_RESERVED', 'QUANTITY_TRACE_ORIG',
				'VAT_ID', 'VAT_INCLUDED', 'CAN_BUY_ZERO_ORIG', 'NEGATIVE_AMOUNT_TRACE_ORIG',
				'PRICE_TYPE', 'RECUR_SCHEME_TYPE', 'RECUR_SCHEME_LENGTH', 'TRIAL_PRICE_ID', 'WITHOUT_ORDER',
				'PURCHASING_PRICE', 'PURCHASING_CURRENCY', 'BARCODE_MULTI', 'SUBSCRIBE_ORIG', 'TYPE'
			);
		}
		else
		{
			$arProductSelect = array(
				'ID', 'QUANTITY', 'QUANTITY_RESERVED', 'QUANTITY_TRACE_ORIG', 'WEIGHT', 'WIDTH', 'LENGTH', 'HEIGHT', 'MEASURE',
				'VAT_ID', 'VAT_INCLUDED', 'CAN_BUY_ZERO_ORIG', 'NEGATIVE_AMOUNT_TRACE_ORIG',
				'PURCHASING_PRICE', 'PURCHASING_CURRENCY', 'BARCODE_MULTI', 'SUBSCRIBE_ORIG', 'TYPE'
			);
		}
		$rsProducts = CCatalogProduct::GetList(
			array(),
			array('ID' => $PRODUCT_ID),
			false,
			false,
			$arProductSelect
		);
		$arBaseProduct = $rsProducts->Fetch();
		if ($bCopy)
		{
			$arBaseProduct['QUANTITY'] = '';
			$arBaseProduct['QUANTITY_RESERVED'] = '';
		}
	}
	else
	{
		$bReadOnly = !($USER->CanDoOperation('catalog_price') && CIBlockSectionRights::UserHasRightTo($IBLOCK_ID, $MENU_SECTION_ID, "element_edit_price"));
	}
	if (empty($arBaseProduct))
	{
		$arBaseProduct = $arDefProduct;
	}
	$productIsSet = (
		CBXFeatures::IsFeatureEnabled('CatCompleteSet')
		&& (
			$arBaseProduct['TYPE'] == CCatalogProduct::TYPE_SET
			|| $arShowTabs['product_set']
		)
	);

	$bDiscount = $USER->CanDoOperation('catalog_discount');
	$bStore = $USER->CanDoOperation('catalog_store');
	$bUseStoreControl = (COption::GetOptionString('catalog', 'default_use_store_control') == 'Y');
	$bEnableReservation = (COption::GetOptionString('catalog', 'enable_reservation') != 'N');

	$availQuantityTrace = COption::GetOptionString("catalog", "default_quantity_trace");
	$availCanBuyZero = COption::GetOptionString("catalog", "default_can_buy_zero");
	$availNegativeAmountGlobal = COption::GetOptionString("catalog", "allow_negative_amount");
	$strGlobalSubscribe = COption::GetOptionString("catalog", "default_subscribe");

	$arExtraList = array();
	$l = CExtra::GetList(array("NAME" => "ASC"));
	while ($l_res = $l->Fetch())
	{
		$arExtraList[] = $l_res;
	}
	?>
<tr class="heading">
<td colspan="2"><?
	echo GetMessage("IBLOCK_TCATALOG");
	if ($bReadOnly) echo " ".GetMessage("IBLOCK_TREADONLY");
	?>
<script type="text/javascript">
var bReadOnly = <? echo ($bReadOnly ? 'true' : 'false'); ?>;

function onChangeCanBuyZero(el)
{
	var prefix = '',
		defaultValue = '<? echo $availCanBuyZero; ?>',
		obNegativeAmount,
		oldValue = 0,
		i = 0;

	obNegativeAmount = BX(prefix+'NEGATIVE_AMOUNT');
	if (!!obNegativeAmount)
	{
		if (-1 < el.selectedIndex)
		{
			if (
				'Y' === el.options[el.selectedIndex].value ||
					('D' === el.options[el.selectedIndex].value && 'Y' === defaultValue)
				)
			{
				obNegativeAmount.selectedIndex = ('Y' === el.options[el.selectedIndex].value ? 1 : 0);
				obNegativeAmount.disabled = true;
			}
			else
			{
				obNegativeAmount.disabled = false;
				if (obNegativeAmount.hasAttribute('data-oldvalue'))
				{
					oldValue = parseInt(obNegativeAmount.getAttribute('data-oldvalue'), 10);
				}
				obNegativeAmount.selectedIndex = oldValue;
			}
		}
	}
}

function getElementForm()
{
	for(var i = 0; i < document.forms.length; i++)
	{
		var check = document.forms[i].name.substring(0, 10).toUpperCase();
		if(check == 'FORM_ELEME' || check == 'TABCONTROL')
			return document.forms[i];
	}
}
function getElementFormName()
{
	var form = getElementForm();
	if (form)
		return form.name;
	else
		return '';
}
function checkForm(e)
{
	if (window.BX_CANCEL)
		return true;

	if (!e)
		e = window.event;

	var bReturn = true;

	if (document.getElementById('CAT_ROW_COUNTER').value > 0 && !!document.getElementById('price_useextform') && !document.getElementById('price_useextform').checked)
	{
		bReturn = confirm('<?=CUtil::JSEscape(GetMessage("CAT_E_PRICE_EXT"))?>');
	}
	if (!bReturn)
	{
		if (e.preventDefault)
			e.preventDefault();

		return false;
	}

	return true;
}

jsUtils.addEvent(window, 'load', function () {
	var obForm = getElementForm();
	jsUtils.addEvent(obForm, 'submit', checkForm);
	jsUtils.addEvent(obForm.dontsave, 'click', function() {window.BX_CANCEL = true; setTimeout('window.BX_CANCEL = false', 10);});
});

function checkBarCode()
{
	var arTrBarCode = document.getElementsByClassName('tr-barcode-class');

	if(BX('CAT_BARCODE_MULTIPLY').checked)
	{
		if (!!arTrBarCode)
		{
			for (var i = 0; i < arTrBarCode.length; i++)
			{
				arTrBarCode[i].disabled = true;
				arTrBarCode[i].style.display = "none";
			}
		}
	}
	else
	{
		if (!!arTrBarCode)
		{
			for(i = 0; i < arTrBarCode.length; i++)
			{
				arTrBarCode[i].disabled = false;
				arTrBarCode[i].style.display = "table-row";
			}
		}
	}
}

function editBarCode()
{
	var obEditBarCode = BX('CAT_BARCODE_EDIT_Y');
	var obBarCode = BX('CAT_BARCODE');
	if (!bReadOnly && !!obEditBarCode && !!obBarCode)
	{
		if (obEditBarCode.checked)
		{
			if (confirm('<? echo GetMessageJS("CAT_BARCODE_EDIT_CONFIRM"); ?>'))
			{
				obBarCode.disabled = false;
			}
			else
			{
				obEditBarCode.checked = false;
				obBarCode.disabled = true;
			}
		}
		else
		{
			obBarCode.disabled = true;
		}
	}
}
function SetFieldsStyle(table_id)
{
	var tbl = document.getElementById(table_id);
	var n = tbl.rows.length;
	for(var i=0; i<n; i++)
		if(tbl.rows[i].cells[0].colSpan == 1)
			tbl.rows[i].cells[0].className = 'field-name';
}

function togglePriceType()
{
	var obPriceSimple = BX('prices_simple');
	var obPriceExt = BX('prices_ext');
	var obBasePrice = BX('tr_BASE_PRICE');
	var obBaseCurrency = BX('tr_BASE_CURRENCY');

	if (obPriceSimple.style.display == 'block')
	{
		obPriceSimple.style.display = 'none';
		obPriceExt.style.display = 'block';
		if (!!obBasePrice)
			BX.style(obBasePrice, 'display', 'none');
		if (!!obBaseCurrency)
			BX.style(obBaseCurrency, 'display', 'none');
	}
	else
	{
		obPriceSimple.style.display = 'block';
		obPriceExt.style.display = 'none';
		if (!!obBasePrice)
			BX.style(obBasePrice, 'display', 'table-row');
		if (!!obBaseCurrency)
			BX.style(obBaseCurrency, 'display', 'table-row');
	}
}
</script>
</td>
</tr>
<tr>
<td valign="top" colspan="2">
	<?
	$aTabs1 = array();
	$aTabs1[] = array("DIV" => "cat_edit1", "TAB" => GetMessage("C2IT_PRICES"), "TITLE" => GetMessage("C2IT_PRICES_D"));
	$aTabs1[] = array("DIV" => "cat_edit3", "TAB" => GetMessage("C2IT_PARAMS"), "TITLE" => GetMessage("C2IT_PARAMS_D"));
	if ($arMainCatalog['SUBSCRIPTION'] == 'Y')
		$aTabs1[] = array("DIV" => "cat_edit4", "TAB" => GetMessage("C2IT_GROUPS"), "TITLE" => GetMessage("C2IT_GROUPS_D"));
	$aTabs1[] = array("DIV" => "cat_edit6", "TAB" => GetMessage("C2IT_DISCOUNTS"), "TITLE" => GetMessage("C2IT_DISCOUNTS_D"));
	$aTabs1[] = array("DIV" => "cat_edit5", "TAB" => GetMessage("C2IT_STORE"), "TITLE" => GetMessage("C2IT_STORE_D"));
	if($bUseStoreControl)
	{
		$aTabs1[] = array("DIV" => "cat_edit7", "TAB" => GetMessage("C2IT_BAR_CODE"), "TITLE" => GetMessage("C2IT_BAR_CODE_D"));
	}

	$tabControl1 = new CAdminViewTabControl("tabControl1", $aTabs1);
	$tabControl1->Begin();

	// Define boundaries
	$arProductFilter = array("PRODUCT_ID" => $PRODUCT_ID);
	if (!CBXFeatures::IsFeatureEnabled('CatMultiPrice'))
	{
		$arProductFilter['BASE'] = 'Y';
	}
	$arPriceBoundariesError = array();
	$arPriceBoundaries = array();
	$dbPrice = CPrice::GetList(
		array("BASE" => "DESC", "CATALOG_GROUP_ID" => "ASC", "QUANTITY_FROM" => "ASC", "QUANTITY_TO" => "ASC"),
		$arProductFilter
	);
	while ($arPrice = $dbPrice->Fetch())
	{
		$arPrice['QUANTITY_FROM'] = (int)$arPrice['QUANTITY_FROM'];
		$arPrice['QUANTITY_TO'] = (int)$arPrice['QUANTITY_TO'];
		if ($arPrice["BASE"] == "Y")
		{
			$arPriceBoundaries[] = array(
				"FROM" => $arPrice["QUANTITY_FROM"],
				"TO" => $arPrice["QUANTITY_TO"]
			);
			if ($arPrice["QUANTITY_FROM"] > $arPrice["QUANTITY_TO"]
				&& $arPrice["QUANTITY_TO"] != 0)
			{
				$arPriceBoundariesError[] = str_replace("#RIGHT#", $arPrice["QUANTITY_TO"], str_replace("#LEFT#", $arPrice["QUANTITY_FROM"], GetMessage("C2IT_BOUND_LR")));
			}
		}
		else
		{
			if ($arPrice["QUANTITY_FROM"] > $arPrice["QUANTITY_TO"]
				&& $arPrice["QUANTITY_TO"] != 0)
			{
				$arPriceBoundariesError[] = str_replace("#TYPE#", $arPrice["CATALOG_GROUP_NAME"], str_replace("#RIGHT#", $arPrice["QUANTITY_TO"], str_replace("#LEFT#", $arPrice["QUANTITY_FROM"], GetMessage("C2IT_BOUND_LR1"))));
			}
			else
			{
				$bNewSegment = true;
				for ($i = 0, $intCount = count($arPriceBoundaries); $i < $intCount; $i++)
				{
					if ($arPriceBoundaries[$i]["FROM"] == $arPrice["QUANTITY_FROM"])
					{
						if ($arPriceBoundaries[$i]["TO"] != $arPrice["QUANTITY_TO"])
						{
							$arPriceBoundariesError[] = str_replace("#TYPE#", $arPrice["CATALOG_GROUP_NAME"], str_replace("#RIGHT#", $arPrice["QUANTITY_TO"], str_replace("#LEFT#", $arPrice["QUANTITY_FROM"], GetMessage("C2IT_BOUND_DIAP"))));
						}
						$bNewSegment = false;
						break;
					}
					else
					{
						if ($arPriceBoundaries[$i]["FROM"] < $arPrice["QUANTITY_FROM"]
							&& $arPriceBoundaries[$i]["TO"] >= $arPrice["QUANTITY_TO"]
							&& $arPrice["QUANTITY_TO"] != 0)
						{
							$arPriceBoundariesError[] = str_replace("#TYPE#", $arPrice["CATALOG_GROUP_NAME"], str_replace("#RIGHT#", $arPrice["QUANTITY_TO"], str_replace("#LEFT#", $arPrice["QUANTITY_FROM"], GetMessage("C2IT_BOUND_DIAP"))));
							$bNewSegment = false;
							break;
						}
					}
				}
				if ($bNewSegment)
				{
					$arPriceBoundaries[] = array("FROM" => $arPrice["QUANTITY_FROM"], "TO" => $arPrice["QUANTITY_TO"]);
				}
			}
		}
	}

	if (!empty($arPriceBoundaries))
	{
		if (count($arPriceBoundaries) > 1)
		{
			Main\Type\Collection::sortByColumn($arPriceBoundaries, array('FROM' => SORT_ASC));
		}
		else
		{
			$arPriceBoundaries[0]['FROM'] = false;
			$arPriceBoundaries[0]['TO'] = false;
		}
	}

// prices tab
	$tabControl1->BeginNextTab();
	$arCatPricesExist = array(); // attr for exist prices for range
	$bUseExtendedPrice = $bVarsFromForm ? $price_useextform == 'Y' : count($arPriceBoundaries) > 1;
	$str_CAT_VAT_ID = $bVarsFromForm ? $CAT_VAT_ID : ($arBaseProduct['VAT_ID'] == 0 ? $arMainCatalog['VAT_ID'] : $arBaseProduct['VAT_ID']);
	$str_CAT_VAT_INCLUDED = $bVarsFromForm ? $CAT_VAT_INCLUDED : $arBaseProduct['VAT_INCLUDED'];
	?>
<input type="hidden" name="price_useextform" id="price_useextform_N" value="N" />
<table border="0" cellspacing="0" cellpadding="0" width="100%" class="edit-table" id="catalog_vat_table">
	<tr>
		<td width="40%"><label for="price_useextform"><? echo GetMessage('C2IT_PRICES_USEEXT'); ?>:</label></td>
		<td width="60%">
			<input type="checkbox" name="price_useextform" id="price_useextform" value="Y" onclick="togglePriceType()" <?=$bUseExtendedPrice ? 'checked="checked"' : ''?> <? echo ($bReadOnly ? ' disabled readonly' : ''); ?>/>
		</td>
	</tr>
	<tr>
		<td width="40%">
			<?echo GetMessage("CAT_VAT")?>:
		</td>
		<td width="60%">
			<?
			$arVATRef = CatalogGetVATArray(array(), true);
			echo SelectBoxFromArray('CAT_VAT_ID', $arVATRef, $str_CAT_VAT_ID, "", $bReadOnly ? "disabled readonly" : '');
			?>
		</td>
	</tr>
	<tr>
		<td width="40%"><label for="CAT_VAT_INCLUDED"><?echo GetMessage("CAT_VAT_INCLUDED")?></label>:</td>
		<td width="60%">
			<input type="hidden" name="CAT_VAT_INCLUDED" id="CAT_VAT_INCLUDED_N" value="N">
			<input type="checkbox" name="CAT_VAT_INCLUDED" id="CAT_VAT_INCLUDED" value="Y" <?=$str_CAT_VAT_INCLUDED == 'Y' ? 'checked="checked"' : ''?> <?=$bReadOnly ? 'disabled readonly' : ''?> />
		</td>
	</tr>
	<?if($USER->CanDoOperation('catalog_purchas_info')):?>
		<tr id="tr_PURCHASING_PRICE">
			<?
			$str_CAT_PURCHASING_PRICE = $bVarsFromForm ? $CAT_PURCHASING_PRICE : $arBaseProduct['PURCHASING_PRICE'];
			?>
			<td width="40%"><?echo GetMessage("C2IT_COST_PRICE")?>:</td>
			<td width="60%">
				<input type="hidden" id="CAT_PURCHASING_PRICE_hidden" name="CAT_PURCHASING_PRICE" value="<?echo htmlspecialcharsbx($str_CAT_PURCHASING_PRICE) ?>">
				<input type="text" <?if ($bReadOnly || $bUseStoreControl) echo "disabled readonly" ?> id="CAT_PURCHASING_PRICE" name="CAT_PURCHASING_PRICE" value="<?echo htmlspecialcharsbx($str_CAT_PURCHASING_PRICE) ?>" size="30">
			</td>
		</tr>
		<tr>
			<td><?= GetMessage("C2IT_COST_CURRENCY") ?>:</td>
			<td>
				<input type="hidden" id="CAT_PURCHASING_CURRENCY_hidden" name="CAT_PURCHASING_CURRENCY" value="<?echo htmlspecialcharsbx($arBaseProduct['PURCHASING_CURRENCY']) ?>">

				<? $isDisabled = ''; if($bUseStoreControl) $isDisabled = " disabled"; echo CCurrency::SelectBox("CAT_PURCHASING_CURRENCY", $arBaseProduct['PURCHASING_CURRENCY'], "", true, "", "id='CAT_PURCHASING_CURRENCY' $isDisabled");?></td>
		</tr>
	<?endif;?>
	<tr id="tr_BASE_PRICE" style="display: <? echo ($bUseExtendedPrice ? 'none' : 'table-row'); ?>;">
		<td width="40%">
	<?
	$arBaseGroup = CCatalogGroup::GetBaseGroup();
	$arBasePrice = CPrice::GetBasePrice($PRODUCT_ID, $arPriceBoundaries[0]["FROM"], $arPriceBoundaries[0]["TO"]);
	echo GetMessage("BASE_PRICE")?> (<? echo GetMessage('C2IT_PRICE_TYPE'); ?> "<? echo htmlspecialcharsbx(!empty($arBaseGroup['NAME_LANG']) ? $arBaseGroup['NAME_LANG'] : $arBaseGroup["NAME"]); ?>"):
		</td>
		<td width="60%">
<script type="text/javascript">
var arExtra = [], arExtraPrc = [];
	<?
	$db_extras = CExtra::GetList(($by3="NAME"), ($order3="ASC"));
	$i = 0;
	while ($extras = $db_extras->Fetch())
	{
		echo "arExtra[".$i."]=".$extras["ID"].";";
		echo "arExtraPrc[".$i."]=".$extras["PERCENTAGE"].";";
		$i++;
	}
	?>
function OnChangeExtra(priceType)
{
	if (bReadOnly)
		return;
	var e_base_price = BX('CAT_BASE_PRICE');
	var e_extra = BX('CAT_EXTRA_' + priceType);
	var e_price = BX('CAT_PRICE_' + priceType);
	var e_currency = BX('CAT_CURRENCY_' + priceType);

	if (isNaN(e_base_price.value) || e_base_price.value <= 0)
	{
		e_currency.disabled = false;
		e_price.disabled = false;
		return;
	}

	var i, esum, eps;
	if (parseInt(e_extra.selectedIndex)==0)
	{
		e_currency.disabled = false;
		e_price.disabled = false;
	}
	else
	{
		e_currency.selectedIndex = 0;
		e_currency.disabled = true;
		e_price.disabled = true;
		for (i = 0; i < arExtra.length; i++)
		{
			if (parseInt(e_extra.options[e_extra.selectedIndex].value) == parseInt(arExtra[i]))
			{
				esum = parseFloat(e_base_price.value) * (1 + arExtraPrc[i] / 100);
				eps = 1.00/Math.pow(10, 6);
				e_price.value = Math.round((esum+eps)*100)/100;
				break;
			}
		}
	}
}

function OnChangeExtraEx(e)
{
	if (bReadOnly)
		return;

	var thename = e.name;

	var pos = thename.lastIndexOf("_");
	var ind = thename.substr(pos + 1);
	thename = thename.substr(0, pos);
	pos = thename.lastIndexOf("_");
	var ptype = thename.substr(pos + 1);

	var e_ext = BX('CAT_EXTRA_'+ptype+"_"+ind);
	var e_price = BX('CAT_PRICE_'+ptype+"_"+ind);
	var e_currency = BX('CAT_CURRENCY_'+ptype+"_"+ind);

	var e_base_price = BX('CAT_BASE_PRICE_'+ind);

	if (isNaN(e_base_price.value) || e_base_price.value <= 0)
	{
		e_price.disabled = false;
		e_currency.disabled = false;
		return;
	}

	var i, esum;
	if (parseInt(e_ext.selectedIndex)==0)
	{
		e_price.disabled = false;
		e_currency.disabled = false;
	}
	else
	{
		e_currency.selectedIndex = 0;
		e_currency.disabled = true;
		e_price.disabled = true;
		for (i = 0; i < arExtra.length; i++)
		{
			if (parseInt(e_ext.options[e_ext.selectedIndex].value) == parseInt(arExtra[i]))
			{
				esum = parseFloat(e_base_price.value) * (1 + arExtraPrc[i] / 100);
				eps = 1.00/Math.pow(10, 6);
				e_price.value = Math.round((esum+eps)*100)/100;
				break;
			}
		}
	}
}

function ChangeExtra(codID)
{
	if (bReadOnly)
		return;

	OnChangeExtra(codID);

	var e_extra = BX('CAT_EXTRA_' + codID + '_0');
	if (e_extra)
	{
		var e_extra_s = document.getElementById('CAT_EXTRA_' + codID);
		e_extra.selectedIndex = e_extra_s.selectedIndex;
		OnChangeExtraEx(e_extra);
	}
}

function OnChangeBasePrice()
{
	if (bReadOnly)
		return;

	var e_base_price = document.getElementById('CAT_BASE_PRICE');

	if (isNaN(e_base_price.value) || e_base_price.value <= 0)
	{
		var k;
		for (k = 0; k < arCatalogGroups.length; k++)
		{
			e_price = BX('CAT_PRICE_' + arCatalogGroups[k]);
			e_price.disabled = false;
			e_currency = BX('CAT_CURRENCY_' + arCatalogGroups[k]);
			e_currency.disabled = false;
		}
		OnChangePriceExist();
		return;
	}

	var i, j, esum, eps;
	var e_price;
	for (i = 0; i < arCatalogGroups.length; i++)
	{
		e_extra = document.getElementById('CAT_EXTRA_' + arCatalogGroups[i]);
		if (e_extra.selectedIndex > 0)
		{
			e_price = document.getElementById('CAT_PRICE_' + arCatalogGroups[i]);
			e_currency = BX('CAT_CURRENCY_' + arCatalogGroups[i]);

			for (j = 0; j < arExtra.length; j++)
			{
				if (parseInt(e_extra.options[e_extra.selectedIndex].value) == parseInt(arExtra[j]))
				{
					esum = parseFloat(e_base_price.value) * (1 + arExtraPrc[j] / 100);
					eps = 1.00/Math.pow(10, 6);
					e_price.value = Math.round((esum+eps)*100)/100;
					e_currency.selectedIndex = 0;
					e_currency.disabled = true;
					e_price.disabled = true;
					break;
				}
			}
		}
	}
	OnChangePriceExist();
}

function ChangeBasePrice(e)
{
	if (bReadOnly)
		return;

	if (e.value != '' && (isNaN(e.value) || e.value <= 0))
	{
	}
	else
	{
		e.className = '';
	}

	OnChangeBasePrice();

	var e_base_price = BX('CAT_BASE_PRICE_0');
	e_base_price.value = BX('CAT_BASE_PRICE').value;
	OnChangeBasePriceEx(e_base_price);
	OnChangePriceExistEx(e_base_price);
}

function ChangeBaseCurrency()
{
	if (bReadOnly)
		return;

	document.getElementById('CAT_BASE_CURRENCY_0').selectedIndex = document.getElementById('CAT_BASE_CURRENCY').selectedIndex;
}

function ChangePrice(codID)
{
	if (bReadOnly)
		return;

	var e_price = document.getElementById('CAT_PRICE_' + codID + '_0');
	e_price.value = document.getElementById('CAT_PRICE_' + codID).value;
	OnChangePriceExist();
	OnChangePriceExistEx(e_price);
}

function ChangeCurrency(codID)
{
	if (bReadOnly)
		return;

	var e_currency = document.getElementById('CAT_CURRENCY_' + codID + "_0");
	e_currency.selectedIndex = document.getElementById('CAT_CURRENCY_' + codID).selectedIndex;
}

function OnChangePriceExist()
{
	if (bReadOnly)
		return;

	var bExist = 'N';
	var e_price_exist = BX('CAT_PRICE_EXIST');
	var e_ext_price_exist = BX('CAT_PRICE_EXIST_0');
	var e_base_price = BX('CAT_BASE_PRICE');

	if (isNaN(e_base_price.value) || e_base_price.value <= 0)
	{
		var i;
		var e_price;
		for (i = 0; i < arCatalogGroups.length; i++)
		{
			e_price = BX('CAT_PRICE_' + arCatalogGroups[i]);
			if (!(isNaN(e_price.value) || e_price.value <= 0))
			{
				bExist = 'Y';
				break;
			}
		}
	}
	else
	{
		bExist = 'Y';
	}
	e_price_exist.value = bExist;
	e_ext_price_exist.value = bExist;
}
</script>
	<?
	$boolBaseExistPrice = false;
	$str_CAT_BASE_PRICE = "";
	if ($arBasePrice)
		$str_CAT_BASE_PRICE = $arBasePrice["PRICE"];
	if ($bVarsFromForm)
		$str_CAT_BASE_PRICE = $CAT_BASE_PRICE;
	if (trim($str_CAT_BASE_PRICE) != '' && doubleval($str_CAT_BASE_PRICE) >= 0)
		$boolBaseExistPrice = true;
	?>
			<input type="text" <?if ($bReadOnly) echo "disabled readonly" ?> id="CAT_BASE_PRICE" name="CAT_BASE_PRICE" value="<?echo htmlspecialcharsbx($str_CAT_BASE_PRICE) ?>" size="30">
		</td>
	</tr>
	<tr id="tr_BASE_CURRENCY" style="display: <? echo ($bUseExtendedPrice ? 'none' : 'table-row'); ?>;">
		<td width="40%">
			<?echo GetMessage("BASE_CURRENCY")?>:
		</td>
		<td width="60%">
		<?
		if ($arBasePrice)
			$str_CAT_BASE_CURRENCY = $arBasePrice["CURRENCY"];
		if ($bVarsFromForm)
			$str_CAT_BASE_CURRENCY = $CAT_BASE_CURRENCY;

		?>
			<select id="CAT_BASE_CURRENCY" name="CAT_BASE_CURRENCY" <?if ($bReadOnly) echo "disabled readonly" ?> OnChange="ChangeBaseCurrency()">
			<?
			foreach ($currencyList as &$currency)
			{
				?><option value="<? echo $currency["CURRENCY"]; ?>"<? if ($currency["CURRENCY"] == $str_CAT_BASE_CURRENCY) echo " selected"?>><? echo $currency["FULL_NAME"]; ?></option><?
			}
			unset($currency);
			?>
			</select>
		</td>
	</tr>
</table>
<script type="text/javascript">
	SetFieldsStyle('catalog_vat_table');
</script>
	<?
// simple price form
	?>
<div id="prices_simple" style="display: <?=$bUseExtendedPrice ? 'none' : 'block'?>;">
	<?
	if (!empty($arPriceBoundariesError))
	{
		?>
	<span class="errortext">
		<? echo GetMessage("C2IT_BOUND_WRONG"); ?><br>
		<? echo implode('<br>', $arPriceBoundariesError); ?><br>
		<? echo GetMessage("C2IT_BOUND_RECOUNT"); ?>
	</span>
		<?
	}
	if (CBXFeatures::IsFeatureEnabled('CatMultiPrice'))
	{
		$bFirst = true;
		$dbCatalogGroups = CCatalogGroup::GetList(
			array("SORT" => "ASC","NAME" => "ASC","ID" => "ASC"),
			array("!BASE" => "Y")
		);
		while ($arCatalogGroup = $dbCatalogGroups->Fetch())
		{
			if($bFirst)
			{
				?>
			<br>
				<table border="0" cellspacing="0" cellpadding="0" width="100%" class="internal">
				<tr class="heading">
					<td><? echo GetMessage("PRICE_TYPE"); ?></td>
					<td><? echo GetMessage("PRICE_EXTRA"); ?></td>
					<td><? echo GetMessage("PRICE_SUM"); ?></td>
					<td><? echo GetMessage("PRICE_CURRENCY"); ?></td>
				</tr>
				<?
				$bFirst = false;
			}
			$str_CAT_EXTRA = 0;
			$str_CAT_PRICE = "";
			$str_CAT_CURRENCY = "";

			$dbPriceList = CPrice::GetList(
				array("QUANTITY_FROM" => "ASC", "QUANTITY_TO" => "ASC"),
				array(
					"PRODUCT_ID" => $PRODUCT_ID,
					"CATALOG_GROUP_ID" => $arCatalogGroup["ID"],
					"QUANTITY_FROM" => $arPriceBoundaries[0]["FROM"],
					"QUANTITY_TO" => $arPriceBoundaries[0]["TO"]
				)
			);
			if ($arPrice = $dbPriceList->Fetch())
			{
				$str_CAT_EXTRA = $arPrice["EXTRA_ID"];
				$str_CAT_PRICE = $arPrice["PRICE"];
				$str_CAT_CURRENCY = $arPrice["CURRENCY"];
			}
			if ($bVarsFromForm)
			{
				$str_CAT_EXTRA = ${"CAT_EXTRA_".$arCatalogGroup["ID"]};
				$str_CAT_PRICE = ${"CAT_PRICE_".$arCatalogGroup["ID"]};
				$str_CAT_CURRENCY = ${"CAT_CURRENCY_".$arCatalogGroup["ID"]};
			}
			if (trim($str_CAT_PRICE) != '' && doubleval($str_CAT_PRICE) >= 0)
				$boolBaseExistPrice = true;
			?>
			<tr <?if ($bReadOnly) echo "disabled readonly" ?>>
				<td valign="top" align="left">
					<? echo htmlspecialcharsbx(!empty($arCatalogGroup['NAME_LANG']) ? $arCatalogGroup['NAME_LANG'] : $arCatalogGroup["NAME"]); ?>
					<?if ($arPrice):?>
					<input type="hidden" name="CAT_ID_<?echo $arCatalogGroup["ID"] ?>" value="<?echo $arPrice["ID"] ?>">
					<?endif;?>
				</td>
				<td valign="top" align="center">
					<?
					echo CExtra::SelectBox("CAT_EXTRA_".$arCatalogGroup["ID"], $str_CAT_EXTRA, GetMessage("VAL_NOT_SET"), "ChangeExtra(".$arCatalogGroup["ID"].")", (($bReadOnly) ? "disabled readonly" : "").' id="'."CAT_EXTRA_".$arCatalogGroup["ID"].'" ');
					?>
				</td>
				<td valign="top" align="center">
					<input type="text" <?if ($bReadOnly) echo "disabled readonly" ?> id="CAT_PRICE_<?echo $arCatalogGroup["ID"] ?>" name="CAT_PRICE_<?echo $arCatalogGroup["ID"] ?>" value="<?echo htmlspecialcharsbx($str_CAT_PRICE) ?>" size="8" OnChange="ChangePrice(<?= $arCatalogGroup["ID"] ?>)">
				</td>
				<td valign="top" align="center">
					<?
					echo CCurrency::SelectBox("CAT_CURRENCY_".$arCatalogGroup["ID"], $str_CAT_CURRENCY, GetMessage("VAL_BASE"), true, "ChangeCurrency(".$arCatalogGroup["ID"].")", (($bReadOnly) ? "disabled readonly" : "").' id="'."CAT_CURRENCY_".$arCatalogGroup["ID"].'" ')
					?>
					<script type="text/javascript">
						ChangeExtra(<?echo $arCatalogGroup["ID"] ?>);
					</script>
				</td>
			</tr>
			<?
		}// endwhile
		if(!$bFirst) echo "</table>";
	}
	?><input type="hidden" name="CAT_PRICE_EXIST" id="CAT_PRICE_EXIST" value="<? echo ($boolBaseExistPrice == true ? 'Y' : 'N'); ?>">
</div>
	<?
	//$tabControl1->BeginNextTab();
// extended price form
	?>
<div id="prices_ext" style="display: <?=$bUseExtendedPrice ? 'block' : 'none'?>;">
<script type="text/javascript">
function addNewElementsGroup(parentId, modelId, counterId, keepValues, typefocus)
{
	if (bReadOnly)
		return;

	if (!document.getElementById(counterId))
		return false;
	var n = ++document.getElementById(counterId).value;
	var thebody = document.getElementById(parentId);
	if (!thebody)
		return false;
	var therow = document.getElementById(modelId);
	if (!therow)
		return false;
	var thecopy = duplicateElement(therow, n, keepValues);
	thebody.appendChild(thecopy);

	return true;
}

function duplicateElement(e, n, keepVal)
{
	if (bReadOnly)
		return;

	if (typeof e.tagName != "undefined")
	{
		var copia = document.createElement(e.tagName);

		var attr = e.attributes;
		if (attr)
		{
			for (i=0; i<attr.length; i++)
			{
				copia.setAttribute(attr[i].name, attr[i].value);
			}
		}

		if (e.id) copia.id = e.id + n;
		if (e.text) copia.text = e.text;

		if (e.tagName.toLowerCase() == "textarea" && !keepVal)
		{
			copia.text = "";
		}
		if (e.name)
		{
			var thename = e.name;

			if (thename.substr(thename.length-1)!="]")
			{
				var ind = thename.lastIndexOf("_");
				if (ind > -1)
				{
					var thename_postf = thename.substr(ind + 1);
					if (!isNaN(parseFloat(thename_postf)))
					{
						thename = thename.substring(0, ind);
					}
				}
				thename = thename + "_" + n;
			}
			else
			{
				var ind = thename.indexOf("[");
				if (ind > -1)
				{
					thename = thename.substring(0, ind);
					thename = thename + "[" + n + "]";
				}
			}

			copia.name = thename;
		}

		copia.value = ((keepVal == true) ?  e.value : ((e.tagName.toLowerCase() == "option" || e.type == "button") ? e.value : null));

		var hijos = e.childNodes;
		if (hijos)
		{
			for (key in hijos)
			{
				if (typeof hijos[key] != "undefined")
				{
					hijocopia = duplicateElement(hijos[key], n, keepVal);
					if (hijocopia) copia.appendChild(hijocopia);
				}
			}
		}
		return copia;
	}
	return null;
}

function CloneBasePriceGroup()
{
	if (bReadOnly)
		return;

	var oTbl = BX("BASE_PRICE_GROUP_TABLE");
	if (!oTbl)
		return;

	var oCntr = document.getElementById("CAT_ROW_COUNTER");
	var cnt = parseInt(oCntr.value);
	cnt = cnt + 1;

	var oRow = oTbl.insertRow(-1);
	var oCell = oRow.insertCell(-1);
	oCell.valign = "top";
	oCell.align = "center";
	oCell.innerHTML = '<input type="text" <?if ($bReadOnly) echo "disabled readonly" ?> name="CAT_BASE_QUANTITY_FROM_'+cnt+'" value="" size="3" OnChange="ChangeBaseQuantityEx(this)">';

	var oCell = oRow.insertCell(-1);
	oCell.valign = "top";
	oCell.align = "center";
	oCell.innerHTML = '<input type="text" <?if ($bReadOnly) echo "disabled readonly" ?> name="CAT_BASE_QUANTITY_TO_'+cnt+'" value="" size="3" OnChange="ChangeBaseQuantityEx(this)">';

	var oCell = oRow.insertCell(-1);
	oCell.valign = "top";
	oCell.align = "center";
	oCell.innerHTML = '<input type="text" <?if ($bReadOnly) echo "disabled readonly" ?> id="CAT_BASE_PRICE_'+cnt+'" name="CAT_BASE_PRICE_'+cnt+'" value="" size="15" OnBlur="ChangeBasePriceEx(this)">';

	var oCell = oRow.insertCell(-1);
	oCell.valign = "top";
	oCell.align = "center";
	var str = '<select id="CAT_BASE_CURRENCY_'+cnt+'" name="CAT_BASE_CURRENCY_'+cnt+'" <?if ($bReadOnly) echo "disabled readonly" ?> onchange="ChangeBaseCurrencyEx(this)">';
	<?
	foreach ($currencyList as &$currency)
	{
		?>str += '<option value="<?echo $currency["CURRENCY"] ?>"><?echo $currency["FULL_NAME_JS"]; ?></option>';<?
	}
	unset($currency);
	?>
	str += '</select>';
	oCell.innerHTML = str;

	var div_ext_price_exist = BX('ext_price_exist');
	var new_price_exist = BX.create('input',
		{'attrs': {
			'type': 'hidden',
			'name': 'CAT_PRICE_EXIST_'+cnt,
			'value': 'N'
		}
		});
	new_price_exist.id = 'CAT_PRICE_EXIST_'+cnt,
		div_ext_price_exist.appendChild(new_price_exist);
	oCntr.value = cnt;
}

function CloneOtherPriceGroup(ind)
{
	if (bReadOnly)
		return;

	var oTbl = document.getElementById("OTHER_PRICE_GROUP_TABLE_"+ind);
	if (!oTbl)
		return;

	var oCntr = document.getElementById("CAT_ROW_COUNTER_"+ind);
	var cnt = parseInt(oCntr.value);
	cnt = cnt + 1;

	var oRow = oTbl.insertRow(-1);
	var oCell = oRow.insertCell(-1);
	oCell.valign = "top";
	oCell.align = "center";
	oCell.innerHTML = '<input type="text" disabled readonly id="CAT_QUANTITY_FROM_'+ind+'_'+cnt+'" name="CAT_QUANTITY_FROM_'+ind+'_'+cnt+'" value="" size="3">';

	var oCell = oRow.insertCell(-1);
	oCell.valign = "top";
	oCell.align = "center";
	oCell.innerHTML = '<input type="text" disabled readonly id="CAT_QUANTITY_TO_'+ind+'_'+cnt+'" name="CAT_QUANTITY_TO_'+ind+'_'+cnt+'" value="" size="3">';

	var oCell = oRow.insertCell(-1);
	oCell.valign = "top";
	oCell.align = "center";
	var str = '';
	oCell.valign = "top";
	oCell.align = "center";
	str += '<select id="CAT_EXTRA_'+ind+'_'+cnt+'" name="CAT_EXTRA_'+ind+'_'+cnt+'" onchange="ChangeExtraEx(this)" <?if ($bReadOnly) echo "disabled readonly" ?>>';
	str += '<option value=""><?= GetMessage("VAL_NOT_SET") ?></option>';
	<?
	foreach ($arExtraList as $arOneExtra)
	{
		?>
		str += '<option value="<?= $arOneExtra["ID"] ?>"><?= CUtil::JSEscape(htmlspecialcharsbx($arOneExtra["NAME"]))." (".htmlspecialcharsbx($arOneExtra["PERCENTAGE"])."%)" ?></option>';
		<?
	}
	?>
	str += '</select>';
	oCell.innerHTML = str;

	var oCell = oRow.insertCell(-1);
	oCell.valign = "top";
	oCell.align = "center";
	oCell.innerHTML = '<input type="text" <?if ($bReadOnly) echo "disabled readonly" ?> id="CAT_PRICE_'+ind+'_'+cnt+'" name="CAT_PRICE_'+ind+'_'+cnt+'" value="" size="10" OnChange="ptPriceChangeEx(this)">';

	var oCell = oRow.insertCell(-1);
	oCell.valign = "top";
	oCell.align = "center";
	var str = '<select id="CAT_CURRENCY_'+ind+'_'+cnt+'" name="CAT_CURRENCY_'+ind+'_'+cnt+'" onchange="ChangeCurrencyEx(this)" <?if ($bReadOnly) echo "disabled readonly" ?>>';
	str += '<option value=""><?= GetMessage("VAL_BASE") ?></option>';
	<?
	foreach ($currencyList as &$currency)
	{
		?>str += '<option value="<?echo $currency["CURRENCY"] ?>"><?echo $currency["FULL_NAME_JS"]; ?></option>';<?
	}
	unset($currency);
	?>
	str += '</select>';
	oCell.innerHTML = str;

	oCntr.value = cnt;
}

function ClonePriceSections()
{
	if (bReadOnly)
		return;

	CloneBasePriceGroup();

	var i, n;
	for (i = 0; i < arCatalogGroups.length; i++)
	{
		CloneOtherPriceGroup(arCatalogGroups[i]);

		n = document.getElementById('CAT_ROW_COUNTER_'+arCatalogGroups[i]).value;
		ChangeExtraEx(document.getElementById('CAT_EXTRA_'+arCatalogGroups[i]+"_"+n));
	}
}

function ChangeBaseQuantityEx(e)
{
	if (bReadOnly)
		return;

	var thename = e.name;

	var pos = thename.lastIndexOf("_");
	var ind = thename.substr(pos + 1);

	var type;
	if (thename.substring(0, "CAT_BASE_QUANTITY_FROM_".length) == "CAT_BASE_QUANTITY_FROM_")
	{
		type = "FROM";
	}
	else
	{
		type = "TO";
	}

	var i;
	var quantity;

	for (i = 0; i < arCatalogGroups.length; i++)
	{
		quantity = document.getElementById('CAT_QUANTITY_'+type+"_"+arCatalogGroups[i]+"_"+ind);
		quantity.value = e.value;
	}
}

function OnChangeBasePriceEx(e)
{
	if (bReadOnly)
		return;

	var thename = e.name;

	var pos = thename.lastIndexOf("_");
	var ind = thename.substr(pos + 1);

	if (isNaN(e.value) || e.value <= 0)
	{
		for (i = 0; i < arCatalogGroups.length; i++)
		{
			e_price = document.getElementById('CAT_PRICE_'+arCatalogGroups[i]+"_"+ind);
			e_price.disabled = false;
			e_cur = document.getElementById('CAT_CURRENCY_'+arCatalogGroups[i]+"_"+ind);
			e_cur.disabled = false;
		}
		OnChangePriceExistEx(e);
		return;
	}

	var i;
	var e_price, e_ext;

	for (i = 0; i < arCatalogGroups.length; i++)
	{
		e_price = BX('CAT_PRICE_'+arCatalogGroups[i]+"_"+ind);
		e_cur = BX('CAT_CURRENCY_'+arCatalogGroups[i]+"_"+ind);
		e_ext = BX('CAT_EXTRA_'+arCatalogGroups[i]+"_"+ind);

		if (!e_ext)
			continue;

		for (j = 0; j < arExtra.length; j++)
		{
			if (parseInt(e_ext.options[e_ext.selectedIndex].value) == parseInt(arExtra[j]))
			{
				esum = parseFloat(e.value) * (1 + arExtraPrc[j] / 100);
				eps = 1.00/Math.pow(10, 6);
				e_price.value = Math.round((esum+eps)*100)/100;
				e_price.disabled = true;
				e_cur.selectedIndex = 0;
				e_cur.disabled = true;
				break;
			}
		}
	}
	OnChangePriceExistEx(e);
}

function ChangeBasePriceEx(e)
{
	if (bReadOnly)
		return;

	if (isNaN(e.value) || e.value <= 0)
	{
	}
	else
	{
		e.className = '';
	}

	OnChangeBasePriceEx(e);

	var thename = e.name;
	var pos = thename.lastIndexOf("_");
	var ind = thename.substr(pos + 1);

	if (parseInt(ind) == 0)
	{
		BX('CAT_BASE_PRICE').value = e.value;
		OnChangeBasePrice();
		OnChangePriceExist();
	}
}

function ChangeExtraEx(e)
{
	if (bReadOnly)
		return;

	if (null == e)
		return;

	OnChangeExtraEx(e);
	var thename = e.name;

	var pos = thename.lastIndexOf("_");
	var ind = thename.substr(pos + 1);
	thename = thename.substr(0, pos);
	pos = thename.lastIndexOf("_");
	var ptype = thename.substr(pos + 1);

	if (parseInt(ind) == 0)
	{
		document.getElementById('CAT_EXTRA_'+ptype).selectedIndex = e.selectedIndex;
		OnChangeExtra(ptype);
	}
}

function ChangeBaseCurrencyEx(e)
{
	if (bReadOnly)
		return;

	var thename = e.name;

	var pos = thename.lastIndexOf("_");
	var ind = thename.substr(pos + 1);

	if (parseInt(ind) == 0)
	{
		document.getElementById('CAT_BASE_CURRENCY').selectedIndex = e.selectedIndex;
	}
}

function ptPriceChangeEx(e)
{
	if (bReadOnly)
		return;

	var thename = e.name;

	var pos = thename.lastIndexOf("_");
	var ind = thename.substr(pos + 1);

	if (parseInt(ind) == 0)
	{
		thename = thename.substr(0, pos);
		pos = thename.lastIndexOf("_");
		var ptype = thename.substr(pos + 1);
		BX('CAT_PRICE_'+ptype).value = e.value;
		OnChangePriceExist();
	}
	OnChangePriceExistEx(e);
}

function ChangeCurrencyEx(e)
{
	if (bReadOnly)
		return;

	var thename = e.name;

	var pos = thename.lastIndexOf("_");
	var ind = thename.substr(pos + 1);

	if (parseInt(ind) == 0)
	{
		thename = thename.substr(0, pos);
		pos = thename.lastIndexOf("_");
		var ptype = thename.substr(pos + 1);

		document.getElementById('CAT_CURRENCY_'+ptype).selectedIndex = e.selectedIndex;
	}
}

function OnChangePriceExistEx(e)
{
	if (bReadOnly)
		return;

	var thename = e.name;

	var pos = thename.lastIndexOf("_");
	var ind = thename.substr(pos + 1);

	if (!(isNaN(ind) || parseInt(ind) < 0))
	{
		var price_ext = BX('CAT_PRICE_EXIST_'+ind);
		if (!price_ext)
			return;

		var i;
		var e_price;
		bExist = 'N';
		e_price = BX('CAT_BASE_PRICE_'+ind);
		if (!e_price)
			return;

		if (isNaN(e_price.value) || e_price.value <= 0)
		{
			for (i = 0; i < arCatalogGroups.length; i++)
			{
				e_price = document.getElementById('CAT_PRICE_'+arCatalogGroups[i]+"_"+ind);
				if (!(isNaN(e_price.value) || e_price.value <= 0))
				{
					bExist = 'Y';
					break;
				}
			}
		}
		else
		{
			bExist = 'Y';
		}
		price_ext.value = bExist;
	}
}

function ShowNotice()
{
	BX('CAT_QUANTITY_RESERVED_DIV').style.display = 'inline-block';
}
function HideNotice()
{
	BX('CAT_QUANTITY_RESERVED_DIV').style.display = 'none';
}

function CloneBarcodeField()
{
	if (bReadOnly)
		return;

	var oTbl = BX("catalog_barcode_table");
	if (!oTbl)
		return;

	var oCntr = document.getElementById("CAT_BARCODE_COUNTER");
	var cnt = parseInt(oCntr.value);
	cnt = cnt + 1;

	var oRow = oTbl.insertRow(-1);
	oRow.setAttribute('id','tr_CAT_BARCODE');
	oRow.setAttribute('class', "tr-barcode-class");
	var oCell1 = oRow.insertCell(-1);
	var oCell = oRow.insertCell(-1);
	oCell.valign = "top";
	oCell.align = "center";
	oCell.innerHTML = '<input type="text" <?if ($bReadOnly) echo "disabled readonly" ?> name="CAT_BARCODE_ADD['+cnt+']" value="" size="30">';

	oCntr.value = cnt;
}
</script>

	<?
	if (!empty($arPriceBoundariesError))
	{
		?>
	<span class="errortext">
		<? echo GetMessage("C2IT_BOUND_WRONG")?><br>
		<? echo implode('<br>', $arPriceBoundariesError); ?><br>
		<?echo GetMessage("C2IT_BOUND_RECOUNT")?>
	</span>
		<?
	}
	$boolExistPrice = false;
	?>
<table border="0" cellspacing="0" cellpadding="0" width="100%" class="internal">
<tr>
	<td valign="top" align="right">
		<?
		echo GetMessage("BASE_PRICE")?> (<? echo GetMessage('C2IT_PRICE_TYPE'); ?> "<? echo htmlspecialcharsbx(!empty($arBaseGroup['NAME_LANG']) ? $arBaseGroup['NAME_LANG'] : $arBaseGroup["NAME"]); ?>"):
	</td>
	<td valign="top" align="left">
		<table border="0" cellspacing="1" cellpadding="3" id="BASE_PRICE_GROUP_TABLE">
			<thead>
			<tr>
				<td align="center"><?echo GetMessage("C2IT_FROM")?></td>
				<td align="center"><?echo GetMessage("C2IT_TO")?></td>
				<td align="center"><?echo GetMessage("C2IT_PRICE")?></td>
				<td align="center"><?echo GetMessage("C2IT_CURRENCY")?></td>
			</tr>
			</thead>
			<tbody id="container3">
				<?
				$ind = -1;
				$dbBasePrice = CPrice::GetList(
					array("QUANTITY_FROM" => "ASC", "QUANTITY_TO" => "ASC"),
					array("PRODUCT_ID" => $PRODUCT_ID, "BASE" => "Y")
				);
				$arBasePrice = $dbBasePrice->Fetch();

				for ($i = 0, $intCount = count($arPriceBoundaries); $i < $intCount; $i++)
				{
					$boolExistPrice = false;
					$ind++;
					$str_CAT_BASE_QUANTITY_FROM = $arPriceBoundaries[$i]["FROM"];
					$str_CAT_BASE_QUANTITY_TO = $arPriceBoundaries[$i]["TO"];

					if ($arBasePrice
						&& intval($arBasePrice["QUANTITY_FROM"]) == $arPriceBoundaries[$i]["FROM"])
					{
						$str_CAT_BASE_ID = $arBasePrice["ID"];
						$str_CAT_BASE_PRICE = $arBasePrice["PRICE"];
						$str_CAT_BASE_CURRENCY = $arBasePrice["CURRENCY"];

						$arBasePrice = $dbBasePrice->Fetch();
					}
					else
					{
						$str_CAT_BASE_ID = 0;
						$str_CAT_BASE_PRICE = "";
						$str_CAT_BASE_CURRENCY = "";
					}

					if ($bVarsFromForm)
					{
						$str_CAT_BASE_QUANTITY_FROM = ${"CAT_BASE_QUANTITY_FROM_".$ind};
						$str_CAT_BASE_QUANTITY_TO = ${"CAT_BASE_QUANTITY_TO_".$ind};
						$str_CAT_BASE_PRICE = ${"CAT_BASE_PRICE_".$ind};
						$str_CAT_BASE_CURRENCY = ${"CAT_BASE_CURRENCY_".$ind};
					}
					if (trim($str_CAT_BASE_PRICE) != '' && doubleval($str_CAT_BASE_PRICE) >= 0)
						$boolExistPrice = true;
					$arCatPricesExist[$ind][$arBaseGroup['ID']] = ($boolExistPrice == true ? 'Y' : 'N');
					?>
				<tr id="model3">
					<td valign="top" align="center">
						<input type="text" <?if ($bReadOnly) echo "disabled readonly" ?> name="CAT_BASE_QUANTITY_FROM_<?= $ind ?>" value="<?echo ($str_CAT_BASE_QUANTITY_FROM != 0 ? htmlspecialcharsbx($str_CAT_BASE_QUANTITY_FROM) : "") ?>" size="3" OnChange="ChangeBaseQuantityEx(this)">
						<input type="hidden" name="CAT_BASE_ID[<?= $ind ?>]" value="<?= htmlspecialcharsbx($str_CAT_BASE_ID) ?>">
					</td>
					<td valign="top" align="center">
						<input type="text" <?if ($bReadOnly) echo "disabled readonly" ?> name="CAT_BASE_QUANTITY_TO_<?= $ind ?>" value="<?echo ($str_CAT_BASE_QUANTITY_TO != 0 ? htmlspecialcharsbx($str_CAT_BASE_QUANTITY_TO) : "") ?>" size="3" OnChange="ChangeBaseQuantityEx(this)">
					</td>
					<td valign="top" align="center">
						<input type="text" <?if ($bReadOnly) echo "disabled readonly" ?> id="CAT_BASE_PRICE_<?= $ind ?>" name="CAT_BASE_PRICE_<?= $ind ?>" value="<?echo htmlspecialcharsbx($str_CAT_BASE_PRICE) ?>" size="15" OnBlur="ChangeBasePriceEx(this)">
					</td>
					<td valign="top" align="center">
						<select id="CAT_BASE_CURRENCY_<?= $ind ?>" name="CAT_BASE_CURRENCY_<?= $ind ?>" <?if ($bReadOnly) echo "disabled readonly" ?> OnChange="ChangeBaseCurrencyEx(this)">
							<?
							foreach ($currencyList as &$currency)
							{
								?><option value="<? echo $currency["CURRENCY"]; ?>"<? if ($currency["CURRENCY"] == $str_CAT_BASE_CURRENCY) echo " selected"?>><? echo $currency["FULL_NAME"];?></option><?
							}
							unset($currency);
							?>
						</select>
					</td>
				</tr>
					<?
				}

				if ($bVarsFromForm && $ind < intval($CAT_ROW_COUNTER))
				{
					for ($i = $ind + 1; $i <= intval($CAT_ROW_COUNTER); $i++)
					{
						$boolExistPrice = false;
						$ind++;
						$str_CAT_BASE_QUANTITY_FROM = ${"CAT_BASE_QUANTITY_FROM_".$ind};
						$str_CAT_BASE_QUANTITY_TO = ${"CAT_BASE_QUANTITY_TO_".$ind};
						$str_CAT_BASE_PRICE = ${"CAT_BASE_PRICE_".$ind};
						$str_CAT_BASE_CURRENCY = ${"CAT_BASE_CURRENCY_".$ind};
						if (trim($str_CAT_BASE_PRICE) != '' && doubleval($str_CAT_BASE_PRICE) >= 0)
							$boolExistPrice = true;
						$arCatPricesExist[$ind][$arBaseGroup['ID']] = ($boolExistPrice == true ? 'Y' : 'N');
						?>
					<tr id="model3">
						<td valign="top" align="center">
							<input type="text" <?if ($bReadOnly) echo "disabled readonly" ?> name="CAT_BASE_QUANTITY_FROM_<?= $ind ?>" value="<?echo ($str_CAT_BASE_QUANTITY_FROM != 0 ? htmlspecialcharsbx($str_CAT_BASE_QUANTITY_FROM) : "") ?>" size="3" OnChange="ChangeBaseQuantityEx(this)">
							<input type="hidden" name="CAT_BASE_ID[<?= $ind ?>]" value="<?= 0 ?>">
						</td>
						<td valign="top" align="center">
							<input type="text" <?if ($bReadOnly) echo "disabled readonly" ?> name="CAT_BASE_QUANTITY_TO_<?= $ind ?>" value="<?echo ($str_CAT_BASE_QUANTITY_TO != 0 ? htmlspecialcharsbx($str_CAT_BASE_QUANTITY_TO) : "") ?>" size="3" OnChange="ChangeBaseQuantityEx(this)">
						</td>
						<td valign="top" align="center">
							<input type="text" <?if ($bReadOnly) echo "disabled readonly" ?> id="CAT_BASE_PRICE_<?= $ind ?>" name="CAT_BASE_PRICE_<?= $ind ?>" value="<?echo htmlspecialcharsbx($str_CAT_BASE_PRICE) ?>" size="15" OnBlur="ChangeBasePriceEx(this)">
						</td>
						<td valign="top" align="center">
							<select id="CAT_BASE_CURRENCY_<?= $ind ?>" name="CAT_BASE_CURRENCY_<?= $ind ?>" <?if ($bReadOnly) echo "disabled readonly" ?> OnChange="ChangeBaseCurrencyEx(this)">
								<?
								foreach ($currencyList as &$currency)
								{
									?><option value="<? echo $currency["CURRENCY"]; ?>"<? if ($currency["CURRENCY"] == $str_CAT_BASE_CURRENCY) echo " selected"?>><? echo $currency["FULL_NAME"];?></option><?
								}
								unset($currency);
								?>
							</select>
						</td>
					</tr>
						<?
					}
				}
				if ($ind == -1)
				{
					$ind++;
					?>
				<tr id="model3">
					<td valign="top" align="center">
						<input type="text" <?if ($bReadOnly) echo "disabled readonly" ?> name="CAT_BASE_QUANTITY_FROM_<?= $ind ?>" value="" size="3" OnChange="ChangeBaseQuantityEx(this)">
					</td>
					<td valign="top" align="center">
						<input type="text" <?if ($bReadOnly) echo "disabled readonly" ?> name="CAT_BASE_QUANTITY_TO_<?= $ind ?>" value="" size="3" OnChange="ChangeBaseQuantityEx(this)">
					</td>
					<td valign="top" align="center">
						<input type="text" <?if ($bReadOnly) echo "disabled readonly" ?> id="CAT_BASE_PRICE_<?= $ind ?>" name="CAT_BASE_PRICE_<?= $ind ?>" value="" size="15" OnBlur="ChangeBasePriceEx(this)">
					</td>
					<td valign="top" align="center">
						<select id="CAT_BASE_CURRENCY_<?= $ind ?>" name="CAT_BASE_CURRENCY_<?= $ind ?>" <?if ($bReadOnly) echo "disabled readonly" ?> OnChange="ChangeBaseCurrencyEx(this)">
							<?
							foreach ($currencyList as &$currency)
							{
								?><option value="<? echo $currency["CURRENCY"]; ?>"><? echo $currency["FULL_NAME"];?></option><?
							}
							unset($currency);
							?>
						</select>
					</td>
				</tr>
					<?
					$arCatPricesExist[$ind][$arBaseGroup['ID']] = 'N';
				}
				?>
			</tbody>
		</table>
		<input type="hidden" name="CAT_ROW_COUNTER" id="CAT_ROW_COUNTER" value="<?= $ind ?>">
		<input type="button" value="<?echo GetMessage("C2IT_MORE")?>" OnClick="ClonePriceSections()">
	</td>
</tr>
<script type="text/javascript">
	arCatalogGroups = [];
	catalogGroupsInd = 0;
</script>
	<?

	if (CBXFeatures::IsFeatureEnabled('CatMultiPrice'))
	{

	$dbCatalogGroups = CCatalogGroup::GetList(
		array("SORT" => "ASC","NAME" => "ASC","ID" => "ASC"),
		array("!BASE" => "Y")
	);
	while ($arCatalogGroup = $dbCatalogGroups->Fetch())
	{
		?>
	<script type="text/javascript">
		arCatalogGroups[catalogGroupsInd] = <?= $arCatalogGroup["ID"] ?>;
		catalogGroupsInd++;
	</script>
	<tr>
		<td valign="top" align="right">
			<?echo GetMessage("C2IT_PRICE_TYPE")?> "<? echo htmlspecialcharsbx(!empty($arCatalogGroup['NAME_LANG']) ? $arCatalogGroup['NAME_LANG'] : $arCatalogGroup["NAME"]); ?>":
		</td>
		<td valign="top" align="left">
			<table border="0" cellspacing="1" cellpadding="3" id="OTHER_PRICE_GROUP_TABLE_<?= $arCatalogGroup["ID"] ?>">
				<thead>
				<tr>
					<td align="center"><?echo GetMessage("C2IT_FROM")?></td>
					<td align="center"><?echo GetMessage("C2IT_TO")?></td>
					<td align="center"><?echo GetMessage("C2IT_NAC_TYPE")?></td>
					<td align="center"><?echo GetMessage("C2IT_PRICE")?></td>
					<td align="center"><?echo GetMessage("C2IT_CURRENCY")?></td>
				</tr>
				</thead>
				<tbody id="container3_<?= $arCatalogGroup["ID"] ?>">
					<?
					$ind = -1;
					$dbPriceList = CPrice::GetList(
						array("QUANTITY_FROM" => "ASC", "QUANTITY_TO" => "ASC"),
						array("PRODUCT_ID" => $PRODUCT_ID, "CATALOG_GROUP_ID" => $arCatalogGroup["ID"])
					);
					$arPrice = $dbPriceList->Fetch();
					for ($i = 0, $intCount = count($arPriceBoundaries); $i < $intCount; $i++)
					{
						$boolExistPrice = false;
						$ind++;
						$str_CAT_QUANTITY_FROM = $arPriceBoundaries[$i]["FROM"];
						$str_CAT_QUANTITY_TO = $arPriceBoundaries[$i]["TO"];

						if ($arPrice
							&& intval($arPrice["QUANTITY_FROM"]) == $arPriceBoundaries[$i]["FROM"])
						{
							$str_CAT_ID = $arPrice["ID"];
							$str_CAT_EXTRA = $arPrice["EXTRA_ID"];
							$str_CAT_PRICE = $arPrice["PRICE"];
							$str_CAT_CURRENCY = $arPrice["CURRENCY"];

							$arPrice = $dbPriceList->Fetch();
						}
						else
						{
							$str_CAT_ID = 0;
							$str_CAT_EXTRA = 0;
							$str_CAT_PRICE = "";
							$str_CAT_CURRENCY = "";
						}

						if ($bVarsFromForm)
						{
							$str_CAT_EXTRA = ${"CAT_EXTRA_".$arCatalogGroup["ID"]."_".$ind};
							$str_CAT_PRICE = ${"CAT_PRICE_".$arCatalogGroup["ID"]."_".$ind};
							$str_CAT_CURRENCY = ${"CAT_CURRENCY_".$arCatalogGroup["ID"]."_".$ind};
							$str_CAT_QUANTITY_FROM = ${"CAT_BASE_QUANTITY_FROM_".$ind};
							$str_CAT_QUANTITY_TO = ${"CAT_BASE_QUANTITY_TO_".$ind};
						}
						if (trim($str_CAT_PRICE) != '' && doubleval($str_CAT_PRICE) >= 0)
							$boolExistPrice = true;
						$arCatPricesExist[$ind][$arCatalogGroup['ID']] = ($boolExistPrice == true ? 'Y' : 'N');
						?>
					<tr id="model3_<?= $arCatalogGroup["ID"] ?>">
						<td valign="top" align="center">
							<input type="text" disabled readonly id="CAT_QUANTITY_FROM_<?= $arCatalogGroup["ID"] ?>_<?= $ind ?>" name="CAT_QUANTITY_FROM_<?= $arCatalogGroup["ID"] ?>_<?= $ind ?>" value="<?echo ($str_CAT_QUANTITY_FROM != 0 ? htmlspecialcharsbx($str_CAT_QUANTITY_FROM) : "") ?>" size="3">
							<input type="hidden" name="CAT_ID_<?= $arCatalogGroup["ID"] ?>[<?= $ind ?>]" value="<?= htmlspecialcharsbx($str_CAT_ID) ?>">
						</td>
						<td valign="top" align="center">
							<input type="text" disabled readonly id="CAT_QUANTITY_TO_<?= $arCatalogGroup["ID"] ?>_<?= $ind ?>" name="CAT_QUANTITY_TO_<?= $arCatalogGroup["ID"] ?>_<?= $ind ?>" value="<?echo ($str_CAT_QUANTITY_TO != 0 ? htmlspecialcharsbx($str_CAT_QUANTITY_TO) : "") ?>" size="3">

						</td>
						<td valign="top" align="center">
							<?
							echo CExtra::SelectBox("CAT_EXTRA_".$arCatalogGroup["ID"]."_".$ind, $str_CAT_EXTRA, GetMessage("VAL_NOT_SET"), "ChangeExtraEx(this)", (($bReadOnly) ? "disabled readonly" : "").' id="'."CAT_EXTRA_".$arCatalogGroup["ID"]."_".$ind.'" ');
							?>

						</td>
						<td valign="top" align="center">
							<input type="text" <?if ($bReadOnly) echo "disabled readonly" ?> id="CAT_PRICE_<?= $arCatalogGroup["ID"] ?>_<?= $ind ?>" name="CAT_PRICE_<?= $arCatalogGroup["ID"] ?>_<?= $ind ?>" value="<?echo htmlspecialcharsbx($str_CAT_PRICE) ?>" size="10" OnChange="ptPriceChangeEx(this)">

						</td>
						<td valign="top" align="center">

							<?= CCurrency::SelectBox("CAT_CURRENCY_".$arCatalogGroup["ID"]."_".$ind, $str_CAT_CURRENCY, GetMessage("VAL_BASE"), true, "ChangeCurrencyEx(this)", (($bReadOnly) ? "disabled readonly" : "").' id="'."CAT_CURRENCY_".$arCatalogGroup["ID"]."_".$ind.'" ') ?>
							<script type="text/javascript">
								jsUtils.addEvent(window, 'load', function() {ChangeExtraEx(document.getElementById('CAT_EXTRA_<?= $arCatalogGroup["ID"] ?>_<?= $ind ?>'));});
							</script>

						</td>
					</tr>
						<?
					}

					if ($bVarsFromForm && $ind < intval(${"CAT_ROW_COUNTER_".$arCatalogGroup["ID"]}))
					{
						for ($i = $ind + 1; $i <= intval(${"CAT_ROW_COUNTER_".$arCatalogGroup["ID"]}); $i++)
						{
							$boolExistPrice = false;
							$ind++;
							$str_CAT_QUANTITY_FROM = ${"CAT_BASE_QUANTITY_FROM_".$ind};
							$str_CAT_QUANTITY_TO = ${"CAT_BASE_QUANTITY_TO_".$ind};
							$str_CAT_EXTRA = ${"CAT_EXTRA_".$arCatalogGroup["ID"]."_".$ind};
							$str_CAT_PRICE = ${"CAT_PRICE_".$arCatalogGroup["ID"]."_".$ind};
							$str_CAT_CURRENCY = ${"CAT_CURRENCY_".$arCatalogGroup["ID"]."_".$ind};
							if (trim($str_CAT_PRICE) != '' && doubleval($str_CAT_PRICE) >= 0)
								$boolExistPrice = true;
							$arCatPricesExist[$ind][$arCatalogGroup['ID']] = ($boolExistPrice == true ? 'Y' : 'N');
							?>
						<tr id="model3_<?= $arCatalogGroup["ID"] ?>">
							<td valign="top" align="center">
								<input type="text" disabled readonly id="CAT_QUANTITY_FROM_<?= $arCatalogGroup["ID"] ?>_<?= $ind ?>" name="CAT_QUANTITY_FROM_<?= $arCatalogGroup["ID"] ?>_<?= $ind ?>" value="<?echo ($str_CAT_QUANTITY_FROM != 0 ? htmlspecialcharsbx($str_CAT_QUANTITY_FROM) : "") ?>" size="3">
								<input type="hidden" name="CAT_ID_<?= $arCatalogGroup["ID"] ?>[<?= $ind ?>]" value="<?= 0 ?>">
							</td>
							<td valign="top" align="center">
								<input type="text" disabled readonly id="CAT_QUANTITY_TO_<?= $arCatalogGroup["ID"] ?>_<?= $ind ?>" name="CAT_QUANTITY_TO_<?= $arCatalogGroup["ID"] ?>_<?= $ind ?>" value="<?echo ($str_CAT_QUANTITY_TO != 0 ? htmlspecialcharsbx($str_CAT_QUANTITY_TO) : "") ?>" size="3">

							</td>
							<td valign="top" align="center">
								<?
								echo CExtra::SelectBox("CAT_EXTRA_".$arCatalogGroup["ID"]."_".$ind, $str_CAT_EXTRA, GetMessage("VAL_NOT_SET"), "ChangeExtraEx(this)", (($bReadOnly) ? "disabled readonly" : "").' id="'."CAT_EXTRA_".$arCatalogGroup["ID"]."_".$ind.'" ');
								?>

							</td>
							<td valign="top" align="center">
								<input type="text" <?if ($bReadOnly) echo "disabled readonly" ?> id="CAT_PRICE_<?= $arCatalogGroup["ID"] ?>_<?= $ind ?>" name="CAT_PRICE_<?= $arCatalogGroup["ID"] ?>_<?= $ind ?>" value="<?echo htmlspecialcharsbx($str_CAT_PRICE) ?>" size="10" OnChange="ptPriceChangeEx(this)">

							</td>
							<td valign="top" align="center">

								<?= CCurrency::SelectBox("CAT_CURRENCY_".$arCatalogGroup["ID"]."_".$ind, $str_CAT_CURRENCY, GetMessage("VAL_BASE"), true, "ChangeCurrencyEx(this)", (($bReadOnly) ? "disabled readonly" : "").' id="'."CAT_CURRENCY_".$arCatalogGroup["ID"]."_".$ind.'" ') ?>
								<script type="text/javascript">
									jsUtils.addEvent(window, 'load', function () {ChangeExtraEx(document.getElementById('CAT_EXTRA_<?= $arCatalogGroup["ID"] ?>_<?= $ind ?>'));});
								</script>

							</td>
						</tr>
							<?
						}
					}
					if ($ind == -1)
					{
						$ind++;
						?>
					<tr id="model3_<?= $arCatalogGroup["ID"] ?>">
						<td valign="top" align="center">
							<input type="text" disabled readonly id="CAT_QUANTITY_FROM_<?= $arCatalogGroup["ID"] ?>_<?= $ind ?>" name="CAT_QUANTITY_FROM_<?= $arCatalogGroup["ID"] ?>_<?= $ind ?>" value="" size="3">
						</td>
						<td valign="top" align="center">
							<input type="text" disabled readonly id="CAT_QUANTITY_TO_<?= $arCatalogGroup["ID"] ?>_<?= $ind ?>" name="CAT_QUANTITY_TO_<?= $arCatalogGroup["ID"] ?>_<?= $ind ?>" value="" size="3">

						</td>
						<td valign="top" align="center">
							<?
							echo CExtra::SelectBox("CAT_EXTRA_".$arCatalogGroup["ID"]."_".$ind, "", GetMessage("VAL_NOT_SET"), "ChangeExtraEx(this)", (($bReadOnly) ? "disabled readonly" : "").' id="'."CAT_EXTRA_".$arCatalogGroup["ID"]."_".$ind.'" ');
							?>

						</td>
						<td valign="top" align="center">
							<input type="text" <?if ($bReadOnly) echo "disabled readonly" ?> id="CAT_PRICE_<?= $arCatalogGroup["ID"] ?>_<?= $ind ?>" name="CAT_PRICE_<?= $arCatalogGroup["ID"] ?>_<?= $ind ?>" value="" size="10" OnChange="ptPriceChangeEx(this)">

						</td>
						<td valign="top" align="center">

							<?= CCurrency::SelectBox("CAT_CURRENCY_".$arCatalogGroup["ID"]."_".$ind, "", GetMessage("VAL_BASE"), true, "ChangeCurrencyEx(this)", (($bReadOnly) ? "disabled readonly" : "").' id="'."CAT_CURRENCY_".$arCatalogGroup["ID"]."_".$ind.'" ') ?>

						</td>
					</tr>
						<?
						$arCatPricesExist[$ind][$arCatalogGroup['ID']] = 'N';
					}
					?>
				</tbody>
			</table>
			<input type="hidden" name="CAT_ROW_COUNTER_<?= $arCatalogGroup["ID"] ?>" id="CAT_ROW_COUNTER_<?= $arCatalogGroup["ID"] ?>" value="<?= $ind ?>">
		</td>
	</tr>
		<?
	}
	}
	?>
</table>
<div id="ext_price_exist">
	<?
	foreach ($arCatPricesExist as $ind => $arPriceExist)
	{
		$strExist = (in_array('Y',$arPriceExist) ? 'Y' : 'N');
		?><input type="hidden" name="CAT_PRICE_EXIST_<? echo $ind; ?>" id="CAT_PRICE_EXIST_<? echo $ind; ?>" value="<? echo $strExist; ?>"><?
	}
	?>
</div>
</div>
	<?
	$tabControl1->BeginNextTab();
	?>
<table border="0" cellspacing="0" cellpadding="0" width="100%" class="edit-table" id="catalog_properties_table">
	<tr id="CAT_BASE_QUANTITY2">
		<td width="40%"><?echo GetMessage("FULL_QUANTITY")?>:</td>
		<td width="60%"><?
		$str_CAT_BASE_QUANTITY = $arBaseProduct["QUANTITY"];
		if (!$bUseStoreControl && $bVarsFromForm) $str_CAT_BASE_QUANTITY = $CAT_BASE_QUANTITY;
		?>
			<input type="text" id="CAT_BASE_QUANTITY" name="CAT_BASE_QUANTITY" <?if ($bReadOnly || $bUseStoreControl || $productIsSet) echo "disabled readonly" ?> value="<?echo htmlspecialcharsbx($str_CAT_BASE_QUANTITY) ?>" size="30">
		</td>
	</tr><?
	if ($bEnableReservation && !$productIsSet)
	{
	?>
	<tr id="CAT_BASE_QUANTITY_RESERV">
		<td width="40%"><?echo GetMessage("BASE_QUANTITY_RESERVED")?>:</td>
		<td width="60%"><?
		$str_CAT_BASE_QUANTITY_RESERVED = $arBaseProduct["QUANTITY_RESERVED"];
		?>
			<input type="hidden" id="CAT_BASE_QUANTITY_RESERVED_hidden" name="CAT_BASE_QUANTITY_RESERVED" value="<?echo htmlspecialcharsbx($str_CAT_BASE_QUANTITY_RESERVED) ?>">
			<input type="text" id="CAT_BASE_QUANTITY_RESERVED" name="CAT_BASE_QUANTITY_RESERVED" <?if ($bReadOnly || $bUseStoreControl) echo "disabled readonly" ?> onfocus="ShowNotice()" onblur="HideNotice()" value="<?echo htmlspecialcharsbx($str_CAT_BASE_QUANTITY_RESERVED) ?>" size="30"><span id="CAT_QUANTITY_RESERVED_DIV" style="color: #af2d49; margin-left: 10px; display: none;">	<?echo GetMessage("QUANTITY_RESERVED_NOTICE")?></span>
		</td>
	</tr>
	<?
	}
	if ($productIsSet)
	{
		?>
		<tr><td colspan="2">
			<div class="adm-info-message-wrap">
				<div class="adm-info-message">
					<? echo GetMessage('SET_NOTICE_QUANTITY'); ?>
				</div>
			</div>
		</td></tr><?
	}
	if ($arMainCatalog['SUBSCRIPTION'] != 'Y')
	{
	?>
		<tr>
			<td width="40%"><?echo GetMessage("C2IT_MEASURE")?>:</td>
			<td width="60%"><?
				$str_CAT_MEASURE = $arBaseProduct["MEASURE"];
				$arAllMeasure = array();
				$dbResultList = CCatalogMeasure::getList(array(), array(), false, false, array("ID", "CODE", "MEASURE_TITLE", "SYMBOL_INTL", "IS_DEFAULT"));
				while($arMeasure = $dbResultList->Fetch())
				{
					$arAllMeasure[] = $arMeasure;
				}
				if($bVarsFromForm) $str_CAT_MEASURE = $CAT_MEASURE;
				if(!empty($arAllMeasure)):?>
					<select style="max-width:220px" id="CAT_MEASURE" name="CAT_MEASURE" <?if ($bReadOnly || $productIsSet) echo "disabled readonly"; ?>>
						<?foreach($arAllMeasure as &$arMeasure):?>
							<option <?if(($arBaseProduct["MEASURE"] == $arMeasure["ID"]) || ($str_CAT_MEASURE == '' && $arMeasure["IS_DEFAULT"] == 'Y')) echo " selected";?>  value="<?=$arMeasure["ID"]?>"><?=htmlspecialcharsbx($arMeasure["MEASURE_TITLE"])?></option>
						<?endforeach;
						if (isset($arMeasure))
							unset($arMeasure);
						?>
					</select>
				<?else:
					echo GetMessage("C2IT_MEASURE_NO_MEASURE"); ?> <a href="/bitrix/admin/cat_measure_list.php?lang=<? echo LANGUAGE_ID; ?>"><? echo GetMessage("C2IT_MEASURES"); ?></a><br><?
				endif;?>
			</td>
		</tr>
	<?
	if (!empty($arAllMeasure))
	{
		?>
		<tr>
			<td width="40%"><?echo GetMessage("C2IT_MEASURE_RATIO")?>:</td>
			<td width="60%"><?
				$str_CAT_MEASURE_RATIO = 1;
				$CAT_MEASURE_RATIO_ID = 0;
				$db_CAT_MEASURE_RATIO = CCatalogMeasureRatio::getList(array(), array("PRODUCT_ID" => $PRODUCT_ID));
				if($ar_CAT_MEASURE_RATIO = $db_CAT_MEASURE_RATIO->Fetch())
				{
					$str_CAT_MEASURE_RATIO = $ar_CAT_MEASURE_RATIO["RATIO"];
					$CAT_MEASURE_RATIO_ID =  $ar_CAT_MEASURE_RATIO["ID"];
				}
				if($bVarsFromForm) $str_CAT_MEASURE_RATIO = $CAT_MEASURE_RATIO;
				?>
				<input type="text" <?if ($bReadOnly || $productIsSet) echo "disabled readonly" ?> id="CAT_MEASURE_RATIO" name="CAT_MEASURE_RATIO" value="<?echo htmlspecialcharsbx($str_CAT_MEASURE_RATIO) ?>" size="30">
				<input type="hidden" id="CAT_MEASURE_RATIO_ID" name="CAT_MEASURE_RATIO_ID" value="<?echo htmlspecialcharsbx($CAT_MEASURE_RATIO_ID) ?>">
			</td>
		</tr>
		<?
	}
	if ($productIsSet)
	{
		?>
		<tr><td colspan="2">
			<div class="adm-info-message-wrap">
				<div class="adm-info-message">
					<? echo GetMessage('SET_NOTICE_MEASURE'); ?>
				</div>
			</div>
		</td></tr><?
	}
	?>
		<tr class="heading">
			<td colspan="2"><?echo GetMessage("C2IT_PARAMS")?></td>
		</tr>
		<tr>
			<td width="40%"><?echo GetMessage("ENABLE_STORE_TRACE")?>:</td>
			<td width="60%"><?
				$str_CAT_BASE_QUANTITY_TRACE = $arBaseProduct["QUANTITY_TRACE_ORIG"];
				if ($bVarsFromForm) $str_CAT_BASE_QUANTITY_TRACE = $CAT_BASE_QUANTITY_TRACE;
				?>
				<select id="CAT_BASE_QUANTITY_TRACE" name="CAT_BASE_QUANTITY_TRACE" <?if ($bReadOnly || $productIsSet) echo "disabled readonly" ?>>
					<option value="D" <?if ("D"==$str_CAT_BASE_QUANTITY_TRACE) echo " selected"?>><?=GetMessage("C2IT_DEFAULT_NEGATIVE")." ("?><?echo $availQuantityTrace=='Y' ? GetMessage("C2IT_YES_NEGATIVE") : GetMessage("C2IT_NO_NEGATIVE")?>) </option>
					<option value="Y" <?if ("Y"==$str_CAT_BASE_QUANTITY_TRACE) echo " selected"?>><?=GetMessage("C2IT_YES_NEGATIVE")?></option>
					<option value="N" <?if ("N"==$str_CAT_BASE_QUANTITY_TRACE) echo " selected"?>><?=GetMessage("C2IT_NO_NEGATIVE")?></option>
				</select>
			</td>
		</tr>
		<tr>
			<td width="40%"><?echo GetMessage("C2IT_CAN_BUY_NULL")?>:</td>
			<td width="60%"><?
				$str_CAT_BASE_CAN_BUY_ZERO = $arBaseProduct["CAN_BUY_ZERO_ORIG"];
				if ($bVarsFromForm) $str_CAT_BASE_CAN_BUY_ZERO = $USE_STORE;
				?>
				<select id="USE_STORE" name="USE_STORE" <? echo ($bReadOnly || $productIsSet ? "disabled readonly" : 'onchange="onChangeCanBuyZero(this)"'); ?>>
					<option value="D" <?if ("D"==$str_CAT_BASE_CAN_BUY_ZERO) echo " selected"?>><?=GetMessage("C2IT_DEFAULT_NEGATIVE")." ("?><?echo $availCanBuyZero=='Y' ? GetMessage("C2IT_YES_NEGATIVE") : GetMessage("C2IT_NO_NEGATIVE")?>) </option>
					<option value="Y" <?if ("Y"==$str_CAT_BASE_CAN_BUY_ZERO) echo " selected"?>><?=GetMessage("C2IT_YES_NEGATIVE")?></option>
					<option value="N" <?if ("N"==$str_CAT_BASE_CAN_BUY_ZERO) echo " selected"?>><?=GetMessage("C2IT_NO_NEGATIVE")?></option>
				</select>
			</td>
		</tr>
		<tr>
			<td width="40%"><?echo GetMessage("C2IT_CAN_NEGATIVE_AMOUMT")?>:</td>
			<td width="60%"><?
				$str_CAT_BASE_NEGATIVE_AMOUNT_TRACE = $arBaseProduct["NEGATIVE_AMOUNT_TRACE_ORIG"];
				if ($bVarsFromForm) $str_CAT_BASE_NEGATIVE_AMOUNT_TRACE = $NEGATIVE_AMOUNT;
				$oldIndex = 0;
				if ('N' == $str_CAT_BASE_NEGATIVE_AMOUNT_TRACE)
				{
					$oldIndex = 2;
				}
				elseif ('Y' == $str_CAT_BASE_NEGATIVE_AMOUNT_TRACE)
				{
					$oldIndex = 1;
				}
				$disableNegative = false;
				if ('Y' == $str_CAT_BASE_CAN_BUY_ZERO || ('D' == $str_CAT_BASE_CAN_BUY_ZERO && 'Y' == $availCanBuyZero))
				{
					$str_CAT_BASE_NEGATIVE_AMOUNT_TRACE = $str_CAT_BASE_CAN_BUY_ZERO;
					$disableNegative = true;
				}
				?>
				<select id="NEGATIVE_AMOUNT" name="NEGATIVE_AMOUNT" <?if ($bReadOnly || $productIsSet) echo "disabled readonly"; ?><? if ($disableNegative) echo " disabled"; ?> data-oldvalue="<? echo $oldIndex; ?>">
					<option value="D" <?if ("D"==$str_CAT_BASE_NEGATIVE_AMOUNT_TRACE) echo " selected"?>><?=GetMessage("C2IT_DEFAULT_NEGATIVE")." ("?><?echo $availNegativeAmountGlobal=='Y' ? GetMessage("C2IT_YES_NEGATIVE") : GetMessage("C2IT_NO_NEGATIVE")?>) </option>
					<option value="Y" <?if ("Y"==$str_CAT_BASE_NEGATIVE_AMOUNT_TRACE) echo " selected"?>><?=GetMessage("C2IT_YES_NEGATIVE")?></option>
					<option value="N" <?if ("N"==$str_CAT_BASE_NEGATIVE_AMOUNT_TRACE) echo " selected"?>><?=GetMessage("C2IT_NO_NEGATIVE")?></option>
				</select>
			</td>
		</tr>
		<tr>
			<td width="40%"><? echo GetMessage("C2IT_SUBSCRIBE"); ?>:</td>
			<td width="60%"><?
				$str_CAT_SUBSCRIBE = $arBaseProduct["SUBSCRIBE_ORIG"];
				if ($bVarsFromForm) $str_CAT_SUBSCRIBE = $SUBSCRIBE;
				?>
				<select id="SUBSCRIBE" name="SUBSCRIBE" <?if ($bReadOnly) echo "disabled readonly" ?>>
					<option value="D" <?if ("D"==$str_CAT_SUBSCRIBE) echo " selected"?>><?=GetMessage("C2IT_DEFAULT_NEGATIVE")." ("?><?echo 'Y' == $strGlobalSubscribe ? GetMessage("C2IT_YES_NEGATIVE") : GetMessage("C2IT_NO_NEGATIVE")?>)</option>
					<option value="Y" <?if ("Y"==$str_CAT_SUBSCRIBE) echo " selected"?>><?=GetMessage("C2IT_YES_NEGATIVE")?></option>
					<option value="N" <?if ("N"==$str_CAT_SUBSCRIBE) echo " selected"?>><?=GetMessage("C2IT_NO_NEGATIVE")?></option>
				</select>
			</td>
		</tr>
		<?
		if ($productIsSet)
		{
			?>
			<tr><td colspan="2">
				<div class="adm-info-message-wrap">
					<div class="adm-info-message">
						<? echo GetMessage('SET_NOTICE_AVAILAVLE'); ?>
					</div>
				</div>
			</td></tr><?
		}
		?>
		<tr class="heading">
			<td colspan="2"><?echo GetMessage("C2IT_MEASUREMENTS_EXT")?></td>
		</tr>
		<tr>
			<td width="40%"><?echo GetMessage("BASE_WEIGHT")?>:</td>
			<td width="60%"><?
				$str_CAT_BASE_WEIGHT = $arBaseProduct["WEIGHT"];
				if ($bVarsFromForm) $str_CAT_BASE_WEIGHT = $CAT_BASE_WEIGHT;
				?>
				<input type="text" <?if ($bReadOnly || $productIsSet) echo "disabled readonly" ?> id="CAT_BASE_WEIGHT" name="CAT_BASE_WEIGHT" value="<?echo htmlspecialcharsbx($str_CAT_BASE_WEIGHT) ?>" size="30">
			</td>
		</tr>
		<tr>
			<td width="40%"><?echo GetMessage("C2IT_BASE_LENGTH")?>:</td>
			<td width="60%"><?
				$str_CAT_BASE_LENGTH = $arBaseProduct["LENGTH"];
				if ($bVarsFromForm) $str_CAT_BASE_LENGTH = $CAT_BASE_LENGTH;
				?>
				<input type="text" <?if ($bReadOnly) echo "disabled readonly" ?> id="CAT_BASE_LENGTH" name="CAT_BASE_LENGTH" value="<?echo htmlspecialcharsbx($str_CAT_BASE_LENGTH) ?>" size="30">
			</td>
		</tr>
		<tr>
			<td width="40%"><?echo GetMessage("C2IT_BASE_WIDTH")?>:</td>
			<td width="60%"><?
				$str_CAT_BASE_WIDTH = $arBaseProduct["WIDTH"];
				if ($bVarsFromForm) $str_CAT_BASE_WIDTH = $CAT_BASE_WIDTH;
				?>
				<input type="text" <?if ($bReadOnly) echo "disabled readonly" ?> id="CAT_BASE_WIDTH" name="CAT_BASE_WIDTH" value="<?echo htmlspecialcharsbx($str_CAT_BASE_WIDTH) ?>" size="30">
			</td>
		</tr>
		<tr>
			<td width="40%"><?echo GetMessage("C2IT_BASE_HEIGHT")?>:</td>
			<td width="60%"><?
				$str_CAT_BASE_HEIGHT = $arBaseProduct["HEIGHT"];
				if ($bVarsFromForm) $str_CAT_BASE_HEIGHT = $CAT_BASE_HEIGHT;
				?>
				<input type="text" <?if ($bReadOnly) echo "disabled readonly" ?> id="CAT_BASE_HEIGHT" name="CAT_BASE_HEIGHT" value="<?echo htmlspecialcharsbx($str_CAT_BASE_HEIGHT) ?>" size="30">
			</td>
		</tr>
	<?
		if ($productIsSet)
		{
			?>
			<tr><td colspan="2">
				<div class="adm-info-message-wrap">
					<div class="adm-info-message">
						<? echo GetMessage('SET_NOTICE_WEIGHT'); ?>
					</div>
				</div>
			</td></tr><?
		}
	}
	if ($arMainCatalog['SUBSCRIPTION'] == 'Y')
	{
	?>
	<tr class="heading">
		<td colspan="2"><?echo GetMessage("C2IT_SUBSCR_PARAMS")?></td>
	</tr>
	<tr>
		<td width="40%"><?echo GetMessage("C2IT_PAY_TYPE")?></td>
		<td width="60%">
			<script type="text/javascript">
			function ChangePriceType()
			{
				if (bReadOnly)
					return;

				var e_pt = document.getElementById('CAT_PRICE_TYPE');

				var e_pt_value = '';
				if (-1 < e_pt.selectedIndex)
					e_pt_value = e_pt.options[e_pt.selectedIndex].value;
				if (e_pt_value == "S")
				{
					document.getElementById('CAT_RECUR_SCHEME_TYPE').disabled = true;
					document.getElementById('CAT_RECUR_SCHEME_LENGTH').disabled = true;
					document.getElementById('CAT_TRIAL_PRICE_ID').disabled = true;
					document.getElementById('CAT_TRIAL_PRICE_ID_BUTTON').disabled = true;
				}
				else if (e_pt_value == "R")
				{
						document.getElementById('CAT_RECUR_SCHEME_TYPE').disabled = false;
						document.getElementById('CAT_RECUR_SCHEME_LENGTH').disabled = false;
						document.getElementById('CAT_TRIAL_PRICE_ID').disabled = true;
						document.getElementById('CAT_TRIAL_PRICE_ID_BUTTON').disabled = true;
				}
				else
				{
					document.getElementById('CAT_RECUR_SCHEME_TYPE').disabled = false;
					document.getElementById('CAT_RECUR_SCHEME_LENGTH').disabled = false;
					document.getElementById('CAT_TRIAL_PRICE_ID').disabled = false;
					document.getElementById('CAT_TRIAL_PRICE_ID_BUTTON').disabled = false;
				}
			}
			</script>
			<?
			$str_CAT_PRICE_TYPE = $arBaseProduct["PRICE_TYPE"];
			if ($bVarsFromForm) $str_CAT_PRICE_TYPE = $CAT_PRICE_TYPE;
			?>
			<select id="CAT_PRICE_TYPE" name="CAT_PRICE_TYPE" onchange="ChangePriceType()">
				<option value="S"<?if ($str_CAT_PRICE_TYPE=="S") echo " selected";?>><?echo GetMessage("C2IT_SINGLE")?></option>
				<option value="R"<?if ($str_CAT_PRICE_TYPE=="R") echo " selected";?>><?echo GetMessage("C2IT_REGULAR")?></option>
				<option value="T"<?if ($str_CAT_PRICE_TYPE=="T") echo " selected";?>><?echo GetMessage("C2IT_TRIAL")?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td width="40%"><?echo GetMessage("C2IT_PERIOD_LENGTH")?></td>
		<td width="60%"><?
		$str_CAT_RECUR_SCHEME_LENGTH = $arBaseProduct["RECUR_SCHEME_LENGTH"];
		if ($bVarsFromForm) $str_CAT_RECUR_SCHEME_LENGTH = $CAT_RECUR_SCHEME_LENGTH;
		?>
			<input type="text" <?if ($bReadOnly) echo "disabled readonly" ?> id="CAT_RECUR_SCHEME_LENGTH" name="CAT_RECUR_SCHEME_LENGTH" value="<?echo htmlspecialcharsbx($str_CAT_RECUR_SCHEME_LENGTH) ?>" size="10">
		</td>
	</tr>
	<tr>
		<td width="40%"><?echo GetMessage("C2IT_PERIOD_TIME")?></td>
		<td width="60%"><?
		$str_CAT_RECUR_SCHEME_TYPE = $arBaseProduct["RECUR_SCHEME_TYPE"];
		if ($bVarsFromForm) $str_CAT_RECUR_SCHEME_TYPE = $CAT_RECUR_SCHEME_TYPE;
		?>
			<select id="CAT_RECUR_SCHEME_TYPE" name="CAT_RECUR_SCHEME_TYPE">
			<?
			foreach ($periodTimeTypes as $key => $value)
			{
				?><option value="<?= $key ?>"<?if ($str_CAT_RECUR_SCHEME_TYPE==$key) echo " selected";?>><?= $value ?></option><?
			}
			?>
			</select>
		</td>
	</tr>
	<tr>
		<td width="40%"><?echo GetMessage("C2IT_TRIAL_FOR")?></td>
		<td width="60%"><?
		$str_CAT_TRIAL_PRICE_ID = $arBaseProduct["TRIAL_PRICE_ID"];
		if ($bVarsFromForm) $str_CAT_TRIAL_PRICE_ID = $CAT_TRIAL_PRICE_ID;
		$catProductName = "";
		$str_CAT_TRIAL_PRICE_ID = intval($str_CAT_TRIAL_PRICE_ID);
		if (0 < $str_CAT_TRIAL_PRICE_ID)
		{
			$dbCatElement = CIBlockElement::GetList(
				array(),
				array('ID' => $str_CAT_TRIAL_PRICE_ID),
				false,
				false,
				array('ID', 'NAME')
			);
			if ($arCatElement = $dbCatElement->Fetch())
				$catProductName = $arCatElement["NAME"];
		}
		?>
			<input id="CAT_TRIAL_PRICE_ID" name="CAT_TRIAL_PRICE_ID" value="<? echo $str_CAT_TRIAL_PRICE_ID; ?>" size="5" type="text"><input type="button" id="CAT_TRIAL_PRICE_ID_BUTTON" name="CAT_TRIAL_PRICE_ID_BUTTON" value="..." onclick="window.open('cat_product_search.php?IBLOCK_ID=<?= $IBLOCK_ID ?>&amp;field_name=CAT_TRIAL_PRICE_ID&amp;alt_name=trial_price_alt&amp;form_name='+getElementFormName(), '', 'scrollbars=yes,resizable=yes,width=600,height=500,top='+Math.floor((screen.height - 500)/2-14)+',left='+Math.floor((screen.width - 600)/2-5));">&nbsp;<span id="trial_price_alt"><? echo htmlspecialcharsex($catProductName); ?></span>
		</td>
	</tr>
	<tr>
		<td width="40%"><?echo GetMessage("C2IT_WITHOUT_ORDER")?></td>
		<td width="60%"><?
		$str_CAT_WITHOUT_ORDER = $arBaseProduct["WITHOUT_ORDER"];
		if ($bVarsFromForm) $str_CAT_WITHOUT_ORDER = $CAT_WITHOUT_ORDER;
		?>
			<input type="checkbox" <?if ($bReadOnly) echo "disabled readonly" ?> name="CAT_WITHOUT_ORDER" value="Y" <?if ($str_CAT_WITHOUT_ORDER=="Y") echo "checked"?>>
		</td>
	</tr>
	<?
	}
	?>
</table>
<script type="text/javascript">
	SetFieldsStyle('catalog_properties_table');
<?
if ('Y' == $arMainCatalog['SUBSCRIPTION'])
{
?>
	ChangePriceType();
<?
}
?>
</script>
<?
if ('Y' == $arMainCatalog['SUBSCRIPTION']):
	$tabControl1->BeginNextTab();
	?>
<script type="text/javascript">
	function CatGroupsActivate(obj, id)
	{
		if (bReadOnly)
			return;

		var ed = document.getElementById('CAT_ACCESS_LENGTH_' + id);
		var ed1 = document.getElementById('CAT_ACCESS_LENGTH_TYPE_' + id);
		ed.disabled = !obj.checked;
		ed1.disabled = !obj.checked;
	}
</script>
<table border="0" cellspacing="0" cellpadding="0" width="100%" class="internal">
	<tr class="heading">
		<td><?echo GetMessage("C2IT_VKL")?></td>
		<td><?echo GetMessage("C2IT_USERS_GROUP")?></td>
		<td><?echo GetMessage("C2IT_ACTIVE_TIME")?> <sup>1)</sup></td>
	</tr>
	<?
	$arCurProductGroups = array();

	$dbProductGroups = CCatalogProductGroups::GetList(
		array(),
		array("PRODUCT_ID" => $ID),
		false,
		false,
		array("ID", "GROUP_ID", "ACCESS_LENGTH", "ACCESS_LENGTH_TYPE")
	);
	while ($arProductGroup = $dbProductGroups->Fetch())
	{
		$arCurProductGroups[intval($arProductGroup["GROUP_ID"])] = array(intval($arProductGroup["ACCESS_LENGTH"]), $arProductGroup["ACCESS_LENGTH_TYPE"]);
	}

	$arAvailContentGroups = array();
	$availContentGroups = COption::GetOptionString("catalog", "avail_content_groups");
	if (strlen($availContentGroups) > 0)
		$arAvailContentGroups = explode(",", $availContentGroups);

	$bNoAvailGroups = true;

	$dbGroups = CGroup::GetList(
		($b="c_sort"),
		($o="asc"),
		array("ANONYMOUS" => "N")
	);
	while ($arGroup = $dbGroups->Fetch())
	{
		$arGroup["ID"] = intval($arGroup["ID"]);

		if ($arGroup["ID"] == 2
			|| !in_array($arGroup["ID"], $arAvailContentGroups))
			continue;

		if ($bVarsFromForm)
		{
			if (isset(${"CAT_USER_GROUP_ID_".$arGroup["ID"]}) && ${"CAT_USER_GROUP_ID_".$arGroup["ID"]} == "Y")
			{
				$arCurProductGroups[$arGroup["ID"]] = array(intval(${"CAT_ACCESS_LENGTH_".$arGroup["ID"]}), ${"CAT_ACCESS_LENGTH_TYPE_".$arGroup["ID"]});
			}
			elseif (array_key_exists($arGroup["ID"], $arCurProductGroups))
			{
				unset($arCurProductGroups[$arGroup["ID"]]);
			}
		}

		$bNoAvailGroups = false;
		?>
		<tr>
			<td align="center">
				<input type="checkbox" name="CAT_USER_GROUP_ID_<?= $arGroup["ID"] ?>" value="Y"<?if (isset($arCurProductGroups[$arGroup["ID"]])) echo " checked";?> onclick="CatGroupsActivate(this, <?= $arGroup["ID"] ?>)">
			</td>
			<td align="left"><? echo htmlspecialcharsbx($arGroup["NAME"]); ?></td>
			<td align="center">
				<input type="text" id="CAT_ACCESS_LENGTH_<?= $arGroup["ID"] ?>" name="CAT_ACCESS_LENGTH_<?= $arGroup["ID"] ?>" size="5" <?
					if (isset($arCurProductGroups[$arGroup["ID"]]))
						echo 'value="'.$arCurProductGroups[$arGroup["ID"]][0].'" ';
					else
						echo 'disabled ';
					?>>
				<select id="CAT_ACCESS_LENGTH_TYPE_<?= $arGroup["ID"] ?>" name="CAT_ACCESS_LENGTH_TYPE_<?= $arGroup["ID"] ?>"<?
					if (!isset($arCurProductGroups[$arGroup["ID"]]))
						echo ' disabled';
					?>>
					<?
					foreach ($periodTimeTypes as $key => $value)
					{
						?><option value="<?= $key ?>"<?if ($arCurProductGroups[$arGroup["ID"]][1] == $key) echo " selected";?>><?= $value ?></option><?
					}
					?>
				</select>
			</td>
		</tr>
		<?
	}

	if ($bNoAvailGroups)
	{
		?>
		<tr>
			<td colspan="3"><? echo GetMessage("C2IT_NO_USER_GROUPS1")?> <a href="/bitrix/admin/settings.php?mid=catalog&lang=<? echo LANGUAGE_ID; ?>"><?echo GetMessage("C2IT_NO_USER_GROUPS2")?></a>.</td>
		</tr>
		<?
	}
	?>
</table>
<br><b>1)</b> <?echo GetMessage("C2IT_ZERO_HINT")?>
<?endif;

	$tabControl1->BeginNextTab();

	$arParams = array();
	if (CCatalogSKU::TYPE_OFFERS == $arMainCatalog['CATALOG_TYPE'])
	{
		$arParams['SKU'] = 'Y';
		$arParams['SKU_PARAMS'] = array(
			'IBLOCK_ID' => $arMainCatalog['IBLOCK_ID'],
			'PRODUCT_IBLOCK_ID' => $arMainCatalog['PRODUCT_IBLOCK_ID'],
			'SKU_PROPERTY_ID' => $arMainCatalog['SKU_PROPERTY_ID'],
		);
	}

	$arDiscountList = CCatalogDiscount::GetDiscountForProduct(array('ID' => $PRODUCT_ID, 'IBLOCK_ID' => $IBLOCK_ID), $arParams);

	if (empty($arDiscountList))
	{
		?><b><?echo GetMessage("C2IT_NO_ACTIVE_DISCOUNTS")?></b><br><?
	}
	else
	{
		?><table border="0" cellspacing="0" cellpadding="0" class="internal" align="center" width="100%">
		<tr class="heading">
			<td>ID</td>
			<td><?echo GetMessage("C2IT_SITE")?></td>
			<td><?echo GetMessage("C2IT_ACTIVITY")?></td>
			<td><?echo GetMessage("C2IT_NAME")?></td>
			<td><?echo GetMessage("C2IT_AMOUNT")?></td>
			<? if ($bDiscount)
			{
			?><td><?echo GetMessage("C2IT_ACTIONS")?></td><?
			}
			?>
		</tr><?
		foreach ($arDiscountList as $arProductDiscounts)
		{
			$boolWork = true;
			?><tr>
			<td align="center"><? echo $arProductDiscounts["ID"] ?></td>
			<td align="center"><? echo $arProductDiscounts["SITE_ID"] ?></td>
			<td align="center"><? echo GetMessage("C2IT_YES")?></td>
			<td align="left"><? echo htmlspecialcharsbx($arProductDiscounts["NAME"]) ?></td>
			<td align="right">
			<?
			if ($arProductDiscounts["VALUE_TYPE"]=="P")
			{
				echo $arProductDiscounts["VALUE"]."%";
			}
			elseif ($arProductDiscounts["VALUE_TYPE"]=="S")
			{
				?>= <? echo CCurrencyLang::CurrencyFormat($arProductDiscounts["VALUE"], $arProductDiscounts["CURRENCY"], true);
			}
			else
			{
				echo CCurrencyLang::CurrencyFormat($arProductDiscounts["VALUE"], $arProductDiscounts["CURRENCY"], true);
			}
			?>
			</td>
			<?
			if ($bDiscount)
			{
			?>
				<td align="left">
					<a href="/bitrix/admin/cat_discount_edit.php?ID=<? echo $arProductDiscounts["ID"] ?>&lang=<? echo urlencode(LANGUAGE_ID); ?>#tb" target="_blank"><?echo GetMessage("C2IT_MODIFY")?></a>
				</td>
			<?
			}
			?>
			</tr>
			<?
		}
		?></table><?
	}
	?><br><?
	echo GetMessage("C2IT_DISCOUNT_HINT");

	$tabControl1->BeginNextTab();
	?>
	<table border="0" cellspacing="0" cellpadding="0" width="100%" class="internal">
		<tr class="heading">
		<td><?echo GetMessage("C2IT_STORE_NUMBER"); ?></td>
		<td><?echo GetMessage("C2IT_NAME"); ?></td>
		<td><?echo GetMessage("C2IT_STORE_ADDR"); ?></td>
		<td><?echo GetMessage("C2IT_PROD_AMOUNT"); ?></td>
		</tr>
	<?
	$numStore = 1;
	if ($bCopy)
	{
		$arSelect = array(
			"ID",
			"TITLE",
			"ADDRESS",
		);
		$rsProps = CCatalogStore::GetList(array('SORT' => 'ASC'), array('ACTIVE' => 'Y'), false, false, $arSelect);
	}
	else
	{
		$arSelect = array(
			"ID",
			"TITLE",
			"ADDRESS",
			"PRODUCT_AMOUNT",
		);
		$rsProps = CCatalogStore::GetList(array('SORT' => 'ASC'), array("PRODUCT_ID" => $PRODUCT_ID, 'ACTIVE' => 'Y'), false, false, $arSelect);
	}
	while ($arProp = $rsProps->GetNext())
	{
		if ($bCopy)
		{
			$amount = 0;
		}
		else
		{
			$amount = (is_null($arProp["PRODUCT_AMOUNT"])) ? 0 : $arProp["PRODUCT_AMOUNT"];
		}
		if ($bVarsFromForm && isset($_POST['AR_AMOUNT'][$arProp['ID']]))
			$amount = $_POST['AR_AMOUNT'][$arProp['ID']];
		$address = '';
		$strNum = $numStore;
		if ($bStore)
		{
			$arProp['ADDRESS'] = trim($arProp['ADDRESS']);
			$address = ('' != $arProp['ADDRESS'] ? $arProp['ADDRESS'] : '<a href="/bitrix/admin/cat_store_edit.php?ID='.$arProp['ID'].'&lang='.LANGUAGE_ID.'">'.GetMessage("C2IT_EDIT").'</a>');
			$strNum = '<a href="/bitrix/admin/cat_store_edit.php?ID='.$arProp['ID'].'&lang='.LANGUAGE_ID.'">'.$numStore.'</a>';
		}
		?>
		<tr>
			<td style="text-align:center;"><? echo $strNum; ?></td>
			<td style="text-align:center;"><?=$arProp['TITLE']?></td>
			<td style="text-align:center;"><?=$address?></td>
			<td style="text-align:center;"><input type="text" id="AR_AMOUNT_<? echo $arProp['ID']; ?>" name="AR_AMOUNT[<?=$arProp['ID']?>]" size="12" value="<? echo htmlspecialcharsbx($amount); ?>" <? echo ((!$bStore || $bUseStoreControl) ? 'disabled readonly' : ''); ?>><?
			if ($bStore)
			{
				?><input type="hidden" name="AR_STORE_ID[<?=$arProp['ID']?>]" value="<?=$arProp['ID']?>"><?
			}
			?></td></tr><?
		$numStore++;
	}
	?></table><?
	if ($bStore)
	{
		if($numStore < 2)
		{
			?><b><? echo GetMessage("C2IT_STORE_NO_STORE"); ?> <a href="/bitrix/admin/cat_store_list.php?lang=<? echo LANGUAGE_ID; ?>"><? echo GetMessage("C2IT_STORE"); ?></a></b><br><?
		}
	}
	if (!$bUseStoreControl)
		echo "<br>".GetMessage("C2IT_STORE_HINT");

	if($bUseStoreControl)
	{
		$tabControl1->BeginNextTab();
		$bUseMultiplyBarcode = ($arBaseProduct['BARCODE_MULTI'] == "Y");
		$arBarcodes = array();
		if (!$bCopy)
		{
			$dbBarcode = CCatalogStoreBarCode::GetList(array(), array("PRODUCT_ID" => $PRODUCT_ID, "STORE_ID" => 0));
			while($arBarcode = $dbBarcode->Fetch())
				$arBarcodes[$arBarcode["ID"]] = $arBarcode["BARCODE"];
		}
		?>
		<input type="hidden" name="CAT_ROW_BARCODE_COUNTER" id="CAT_ROW_BARCODE_COUNTER" value="<?= $ind ?>">
		<input type="hidden" name="CAT_BARCODE_MULTIPLY" id="CAT_BARCODE_MULTIPLY_N" value="N" />
		<table border="0" cellspacing="0" cellpadding="0" width="100%" class="edit-table" id="catalog_barcode_table">

			<tr>
				<td width="40%"><label for="CAT_BARCODE_MULTIPLY"><? echo GetMessage('C2IT_BARCODE_MULTIPLY'); ?>:</label></td>
				<td width="60%">
					<input type="checkbox" name="CAT_BARCODE_MULTIPLY" id="CAT_BARCODE_MULTIPLY" value="Y" <?=$bUseMultiplyBarcode ? 'checked="checked"' : ''?> <? echo (($bReadOnly) ? 'disabled readonly' : 'onclick="checkBarCode();"'); ?>/>
				</td>
			</tr>

			<tr id="tr_CAT_BARCODE" class="tr-barcode-class"<? echo (($bUseMultiplyBarcode) ? ' style="display: none;"' : ''); ?>>
				<td><?echo GetMessage("C2IT_BAR_CODE")?>:</td><?
				$strDisable = '';
				$firstBarcodeKey = intval(key($arBarcodes));
				$firstBarcode = current($arBarcodes);
				unset($arBarcodes[$firstBarcodeKey]);
				if($bReadOnly)
				{
					$strDisable = ' disabled readonly';
				}
				elseif(!empty($arBarcodes))
				{
					$strDisable = ' disabled';
				}
				?>
				<td>
					<input type="hidden" name="AR_BARCODE_ID[<?=$firstBarcodeKey?>]" value="<?=$firstBarcodeKey?>" />
					<input type="hidden" name="CAT_BARCODE_COUNTER" id="CAT_BARCODE_COUNTER" value="0" />
					<input type="text" name="CAT_BARCODE_<?=$firstBarcodeKey?>" id="CAT_BARCODE" size="30" value="<?=htmlspecialcharsbx($firstBarcode); ?>" <? //echo $strDisable; ?>/>
					<input type="button" value="<?echo GetMessage("C2IT_MORE")?>" OnClick="CloneBarcodeField()">
				</td>
			</tr>
			<?if(!empty($arBarcodes))
			{
				foreach($arBarcodes as $id => $barcode)
				{
				?>
					<tr id="tr_CAT_BARCODE" class="tr-barcode-class"<? echo (($bUseMultiplyBarcode) ? ' style="display: none;"' : ''); ?>>
						<td></td>
						<td>
							<input type="hidden" name="AR_BARCODE_ID[<?=$id?>]" value="<?=$id?>" />
							<input type="text" name="CAT_BARCODE_<?=$id?>" id="CAT_BARCODE_<?=$id?>" size="30" value="<?=htmlspecialcharsbx($barcode); ?>" <? //echo $strDisable; ?>/>
						</td>
					</tr>
				<?
				}
			}
			?>
			<?
			if (0 < $PRODUCT_ID && '' != $arBarcodes)
			{
			?>
			<tr id="tr_CAT_BARCODE_EDIT"<? echo ' style="display: none;"'; ?>>
				<td><?echo GetMessage("C2IT_BAR_CODE_EDIT")?>:</td>
				<td>
					<input type="hidden" name="CAT_BARCODE_EDIT" id="CAT_BARCODE_EDIT_N" value="Y" />
					<input type="checkbox" name="CAT_BARCODE_EDIT" id="CAT_BARCODE_EDIT_Y" size="30" value="Y" <? //echo (($bReadOnly) ? ' disabled readonly' : ' onclick="editBarCode();"'); ?> />
				</td>
			</tr>
			<?
			}
			?>
		</table>
		<?
	}
		$tabControl1->End();
	?>
<script type="text/javascript">
BX.ready(function(){
	var basePrice = BX('CAT_BASE_PRICE');
	if (!!basePrice && !basePrice.disabled)
		BX.bind(basePrice, 'bxchange', function(e){ ChangeBasePrice(e); });

});
</script>
	</td>
</tr>
<?
}
?>