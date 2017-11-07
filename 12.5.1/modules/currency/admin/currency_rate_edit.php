<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/currency/include.php");
$CURRENCY_RIGHT = $APPLICATION->GetGroupRight("currency");
if ($CURRENCY_RIGHT=="D") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

__IncludeLang(GetLangFileName($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/currency/lang/", "/currencies_rates.php"));
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/currency/prolog.php");

ClearVars();

$message = null;
$bVarsFromForm = false;

$ID = intval($ID);

$aTabs = array(
array("DIV" => "edit1", "TAB" => GetMessage("curr_rates_rate"), "ICON"=>"main_user_edit", "TITLE"=>GetMessage("curr_rates_rate_ex")),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);

if ($REQUEST_METHOD == "POST" && strlen($Update)>0 && $CURRENCY_RIGHT=="W" && check_bitrix_sessid())
{

	$arFields = Array(
		"DATE_RATE" => $DATE_RATE,
		"RATE" => $RATE,
		"RATE_CNT" => $RATE_CNT,
		"CURRENCY" => $CURRENCY,
	);

	if ($ID > 0)
	{
		$res = CCurrencyRates::Update($ID, $arFields);
	}
	else
	{
		$ID = CCurrencyRates::Add($arFields);
		$res = ($ID>0);
	}

	if(!$res)
	{
		if($e = $APPLICATION->GetException())
			$message = new CAdminMessage(GetMessage("currency_error"), $e);
		$bVarsFromForm = true;
	}
	else
	{
		if(strlen($apply)<=0)
		{
			if(strlen($return_url)>0)
				LocalRedirect($return_url);
			else
				LocalRedirect("/bitrix/admin/currencies_rates.php?lang=".LANGUAGE_ID.GetFilterParams("filter_", false));
		}
		LocalRedirect("/bitrix/admin/currency_rate_edit.php?lang=".LANGUAGE_ID."&ID=".$ID."&".GetFilterParams("filter_", false));
	}
}

if ($ID > 0)
	$APPLICATION->SetTitle(GetMessage("CURRENCY_EDIT_TITLE"));
else
	$APPLICATION->SetTitle(GetMessage("CURRENCY_NEW_TITLE"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$aContext = array(
	array(
		"ICON" => "btn_list",
		"TEXT"=>GetMessage("MAIN_ADMIN_MENU_LIST"),
		"LINK"=>"/bitrix/admin/currencies_rates.php?lang=".LANGUAGE_ID,
		"TITLE"=>GetMessage("MAIN_ADMIN_MENU_LIST")
	),
);

if ($ID > 0)
{
	$aContext[] = 	array(
		"ICON" => "btn_new",
		"TEXT"=>GetMessage("MAIN_ADMIN_MENU_CREATE"),
		"LINK"=>"/bitrix/admin/currency_rate_edit.php?lang=".LANGUAGE_ID,
		"TITLE"=>GetMessage("MAIN_ADMIN_MENU_CREATE")
	);

	if ($CURRENCY_RIGHT=="W")
	{
		$aContext[] = 	array(
			"ICON" => "btn_delete",
			"TEXT"=>GetMessage("MAIN_ADMIN_MENU_DELETE"),
			"LINK"	=> "javascript:if(confirm('".GetMessage("CONFIRM_DEL_MESSAGE")."'))window.location='/bitrix/admin/currencies_rates.php?action=delete&ID=".$ID."&lang=".LANGUAGE_ID."&".bitrix_sessid_get()."';",
		);
	}
}

$context = new CAdminContextMenu($aContext);
$context->Show();

if ($ID > 0 && !$bVarsFromForm)
{
	$result = CCurrencyRates::GetByID($ID);
	$str_ID = $result["ID"];
	$str_DATE_RATE = $result["DATE_RATE"];
	$str_CURRENCY = $result["CURRENCY"];
	$str_RATE_CNT = $result["RATE_CNT"];
	$str_RATE = $result["RATE"];
}

if($bVarsFromForm)
	$DB->InitTableVarsForEdit("b_catalog_currency_rate", "", "str_");

?>
<script type="text/javascript">
	var arCurrencies = new Array();
	var arCurrenciesNom = new Array();
	<?
	$db_curr = CCurrency::GetList(($by1="sort"), ($order1="asc"));
	$num_currencies = 0;
	while ($curr = $db_curr->Fetch())
	{
		$db_currate = CCurrencyRates::GetList(($by5="DATE_RATE"), ($order5="DESC"), array("CURRENCY"=>$curr["CURRENCY"]));
		if ($currate = $db_currate->Fetch())
			echo "arCurrenciesNom[".$num_currencies."]=".$currate["RATE_CNT"].";";
		else
			echo "arCurrenciesNom[".$num_currencies."]=".$curr["AMOUNT_CNT"].";";
		$num_currencies++;
	}
	?>

	function ChangeCurr()
	{
		CUR_SELECT_BOX = document.forms['form1'].elements['CURRENCY'].selectedIndex;
		CUR_RATE_CNT = document.forms['form1'].elements['RATE_CNT'];
		CUR_RATE_CNT.value = arCurrenciesNom[CUR_SELECT_BOX];
	}

	function GetAdminDiv(id, url)
	{
		document.getElementById('cyrrency_query_error_div').innerHTML = '';
		var date = document.forms['form1'].elements['DATE_RATE'].value;
		var curr = document.forms['form1'].elements['CURRENCY'].value;

		if (curr == "")
		{
			alert('<?=GetMessage("ERROR_CURRENCY")?>');
			return false;
		}

		if (date == "")
		{
			alert('<?=GetMessage("ERROR_DATE_RATE")?>');
			document.forms['form1'].elements['DATE_RATE'].focus();
			return false;
		}

		url = url + "lang=<?=LANGUAGE_ID?>&DATE_RATE=" + date + "&CURRENCY=" + curr;

		var e = BX.showWait(id);
		BX.loadScript(url, function() {BX.closeWait(e)});
	}

</script>
<?
if ($message)
	echo $message->Show();
?>
<form method="post" action="<?$APPLICATION->GetCurPage()?>" name="form1">
<?=bitrix_sessid_post()?>
<?echo GetFilterHiddens("filter_");?>
<input type="hidden" name="ID" value="<?echo $ID?>">
<input type="hidden" name="Update" value="Y">
<?$tabControl->Begin();?>
<?$tabControl->BeginNextTab();?>
	<?if ($ID > 0):?>
	<tr>
		<td>ID:</td>
		<td><?=$str_ID?></td>
	</tr>
	<?endif?>

	<tr class="adm-detail-required-field">
		<td width="40%"><?echo GetMessage("curr_rates_date1")?>:</td>
		<td width="60%"><?= CalendarDate("DATE_RATE", $str_DATE_RATE, "form1", "10", "class=\"typeinput\""); ?></td>
	</tr>
	<tr class="adm-detail-required-field">
		<td><?echo GetMessage("curr_rates_curr1")?>:</td>
		<td><?echo CCurrency::SelectBox("CURRENCY", $str_CURRENCY, "", True, "ChangeCurr()", "")?></td>
	</tr>
	<tr class="adm-detail-required-field">
		<td><?echo GetMessage("curr_rates_rate_cnt")?>:</td>
		<td><input type="text" name="RATE_CNT" value="<?=$str_RATE_CNT?>" size="5"></td>
	</tr>
	<tr class="adm-detail-required-field">
		<td><?echo GetMessage("curr_rates_rate")?>:</td>
		<td><div id="cyrrency_rate_query_div" style="display:inline;"></div><input type="text" name="RATE" value="<?=$str_RATE?>" size="12">&nbsp;<input type="button" title="<?echo GetMessage("curr_rates_query_ex")?>" value="<?echo GetMessage("curr_rates_query")?>" OnClick="GetAdminDiv('cyrrency_rate_query_div','currency_rate_query.php?<?=bitrix_sessid_get()?>&');"><div id="cyrrency_query_error_div"></div></td>
	</tr>
<?$tabControl->EndTab();?>
<?$tabControl->Buttons(Array("disabled" => $CURRENCY_RIGHT<"W", "back_url" =>"/bitrix/admin/currencies_rates.php?lang=".LANGUAGE_ID.GetFilterParams("filter_")));?>
<?$tabControl->End();?>
</form>
<?$tabControl->ShowWarnings("form1", $message);?>
<script language="javascript">ChangeCurr();</script>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>