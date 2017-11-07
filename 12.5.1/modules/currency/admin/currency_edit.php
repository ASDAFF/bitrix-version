<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/currency/include.php");

$CURRENCY_RIGHT = $APPLICATION->GetGroupRight("currency");
if ($CURRENCY_RIGHT=="D") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

__IncludeLang(GetLangFileName($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/currency/lang/", "/currencies.php"));
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/currency/prolog.php");

ClearVars("f_");

$message = null;
$bVarsFromForm = false;

$ID = htmlspecialcharsEx(trim($ID));
$ID = (strlen($ID) <= 0 ? false : $ID);


$db_result_lang = CLangAdmin::GetList($by = "sort", $order = "asc");

$iCount = 0;
while ($db_result_lang_array = $db_result_lang->Fetch())
{
	$arLangsLID[$iCount] = $db_result_lang_array["LID"];
	$arLangNamesLID[$iCount] = htmlspecialcharsbx($db_result_lang_array["NAME"]);
	$iCount++;
}

$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("currency_curr"), "ICON"=>"main_user_edit", "TITLE"=>GetMessage("currency_curr_settings")),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);

if ($_SERVER["REQUEST_METHOD"] == "POST" && $CURRENCY_RIGHT=="W" && strlen($Update)>0 && check_bitrix_sessid())
{

	$arFields = array(
		"AMOUNT" => $_POST['AMOUNT'],
		"AMOUNT_CNT" => $_POST['AMOUNT_CNT'],
		"SORT" => $_POST['SORT']
	);
	if (isset($_POST['CURRENCY']))
	{
		$arFields["CURRENCY"] = $_POST['CURRENCY'];
	}
	$strAction = ($ID ? 'UPDATE' : 'ADD');
	$bVarsFromForm = !CCurrency::CheckFields($strAction, $arFields, $ID);

	if (!$bVarsFromForm)
	{
		$arMsg = array();
		for ($i=0; $i<$iCount; $i++)
		{
			if (!isset(${"FORMAT_STRING_".$arLangsLID[$i]}) || strlen(${"FORMAT_STRING_".$arLangsLID[$i]})<=0)
			{
				$arMsg[] = array("id"=>"FORMAT_STRING_".$arLangsLID[$i], "text"=> GetMessage("currency_format_string", Array("#LANG#" => $arLangNamesLID[$i])));
				continue;
			}
		}

		if(!empty($arMsg))
		{
			$bVarsFromForm = true;
			$e = new CAdminException($arMsg);
			$APPLICATION->ThrowException($e);
			$e = $APPLICATION->GetException();
			$message = new CAdminMessage(GetMessage("currency_error"), $e);
		}
		else
		{
			if (strlen($ID) > 0)
				CCurrency::Update($ID, $arFields);
			else
				$ID = CCurrency::Add($arFields);

			for ($i=0; $i<$iCount; $i++)
			{
				unset($arFields);
				$arFields["FORMAT_STRING"] = Trim(${"FORMAT_STRING_".$arLangsLID[$i]});
				$arFields["FULL_NAME"] = Trim(${"FULL_NAME_".$arLangsLID[$i]});
				$arFields["DEC_POINT"] = ${"DEC_POINT_".$arLangsLID[$i]};
				$arFields["THOUSANDS_SEP"] = ${"THOUSANDS_SEP_".$arLangsLID[$i]};
				$arFields["THOUSANDS_VARIANT"] = ${"THOUSANDS_VARIANT_".$arLangsLID[$i]};
				$arFields["DECIMALS"] = IntVal(${"DECIMALS_".$arLangsLID[$i]});
				$arFields["CURRENCY"] = $ID /*$arFields["CURRENCY"]*/;
				$arFields["LID"] = $arLangsLID[$i];
				if(strlen($arFields["THOUSANDS_VARIANT"]) > 0)
					$arFields["THOUSANDS_SEP"] = false;
				else
					$arFields["THOUSANDS_VARIANT"] = false;

				if (strlen($ID) > 0)
				{
					$db_result_lang = CCurrencyLang::GetByID($ID, $arLangsLID[$i]);
					if ($db_result_lang)
						CCurrencyLang::Update($ID, $arLangsLID[$i], $arFields);
					else
						CCurrencyLang::Add($arFields);
				}
				else
				{
					CCurrencyLang::Add($arFields);
				}
			}

			if(strlen($apply)<=0)
				LocalRedirect("/bitrix/admin/currencies.php?lang=". LANG);

			LocalRedirect("/bitrix/admin/currency_edit.php?ID=".$ID."&lang=".LANG);
		}
	}
	else
	{
		if ($e = $APPLICATION->GetException())
			$message = new CAdminMessage(GetMessage("currency_error"), $e);
	}

}

if (strlen($ID) > 0)
	$APPLICATION->SetTitle(GetMessage("CURRENCY_EDIT_TITLE"));
else
	$APPLICATION->SetTitle(GetMessage("CURRENCY_NEW_TITLE"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$aContext = array(
	array(
		"ICON" => "btn_list",
		"TEXT"=>GetMessage("MAIN_ADMIN_MENU_LIST"),
		"LINK"=>"currencies.php?lang=".LANG,
		"TITLE"=>GetMessage("MAIN_ADMIN_MENU_LIST")
	),
);

if (strlen($ID) > 0)
{
	$aContext[] = 	array(
		"ICON" => "btn_new",
		"TEXT"=>GetMessage("MAIN_ADMIN_MENU_CREATE"),
		"LINK"=>"currency_edit.php?lang=".LANG,
		"TITLE"=>GetMessage("MAIN_ADMIN_MENU_CREATE")
	);

	if ($CURRENCY_RIGHT=="W")
	{
		$aContext[] = 	array(
			"ICON" => "btn_delete",
			"TEXT"=>GetMessage("MAIN_ADMIN_MENU_DELETE"),
			"ONCLICK"	=> "javascript:if(confirm('".GetMessage("CONFIRM_DEL_MESSAGE")."'))window.location='currencies.php?action=delete&ID[]=".CUtil::JSEscape($ID)."&lang=".LANG."&".bitrix_sessid_get()."';",
		);
	}
}
$context = new CAdminContextMenu($aContext);
$context->Show();

//Defaults
$f_SORT = "10";
$f_AMOUNT_CNT = "1";

if (strlen($ID) > 0 && !$bVarsFromForm)
{
	$result = CCurrency::GetByID($ID);
	$f_AMOUNT_CNT = $result["AMOUNT_CNT"];
	$f_AMOUNT = number_format($result["AMOUNT"], 4);
	$f_SORT = $result["SORT"] ;

	$res = CCurrencyLang::GetList($by, $order, $ID);
	while ($ar = $res->Fetch())
	{
		${"l_FULL_NAME_".$ar["LID"]} = $ar["FULL_NAME"];
		${"l_FORMAT_STRING_".$ar["LID"]} = $ar["FORMAT_STRING"];
		${"l_DEC_POINT_".$ar["LID"]} = $ar["DEC_POINT"];
		${"l_THOUSANDS_SEP_".$ar["LID"]} = $ar["THOUSANDS_SEP"];
		${"l_THOUSANDS_VARIANT_".$ar["LID"]} = $ar["THOUSANDS_VARIANT"];
		${"l_DECIMALS_".$ar["LID"]} = $ar["DECIMALS"];
	}

}

if($bVarsFromForm)
{
	$DB->InitTableVarsForEdit("b_catalog_currency", "", "f_");

	for ($i=0; $i<$iCount; $i++)
	{
		${"l_FULL_NAME_".$arLangsLID[$i]} = ${"FULL_NAME_".$arLangsLID[$i]};
		${"l_FORMAT_STRING_".$arLangsLID[$i]} = ${"FORMAT_STRING_".$arLangsLID[$i]};
		${"l_DEC_POINT_".$arLangsLID[$i]} = ${"DEC_POINT_".$arLangsLID[$i]};
		${"l_THOUSANDS_SEP_".$arLangsLID[$i]} = ${"THOUSANDS_SEP_".$arLangsLID[$i]};
		${"l_THOUSANDS_VARIANT_".$arLangsLID[$i]} = ${"THOUSANDS_VARIANT_".$arLangsLID[$i]};
		${"l_DECIMALS_".$arLangsLID[$i]} = ${"DECIMALS_".$arLangsLID[$i]};
	}
}

if($message)
	echo $message->Show();

$arTemplates = Array(
	Array("TEXT" => "$1.234,10", "FORMAT" => "$#", "DEC_POINT" => ",", "THOUSANDS_VARIANT" => "D", "DECIMALS" => "2", ),
	Array("TEXT" => "$1 234,10", "FORMAT" => "$#", "DEC_POINT" => ",", "THOUSANDS_VARIANT" => "S", "DECIMALS" => "2", ),
	Array("TEXT" => GetMessage("currency_euro")."2.345,20", "FORMAT" => "&euro;#", "DEC_POINT" => ",", "THOUSANDS_VARIANT" => "D", "DECIMALS" => "2", ),
	Array("TEXT" => GetMessage("currency_euro")."2 345,20", "FORMAT" => "&euro;#", "DEC_POINT" => ",", "THOUSANDS_VARIANT" => "S", "DECIMALS" => "2", ),
);

if ($lang=="ru")
{
	$arTemplates[] = Array("TEXT" => "3.456,70 ".GetMessage("CURRENCY_RUBLE"), "FORMAT" => "# ".GetMessage("CURRENCY_RUBLE"), "DEC_POINT" => ",", "THOUSANDS_VARIANT" => "D", "DECIMALS" => "2", );
	$arTemplates[] = Array("TEXT" => "3 456,70 ".GetMessage("CURRENCY_RUBLE"), "FORMAT" => "# ".GetMessage("CURRENCY_RUBLE"), "DEC_POINT" => ",", "THOUSANDS_VARIANT" => "S", "DECIMALS" => "2", );
}
?><script type="text/javascript">
function setTemplate(lang)
{
	var arFormat = new Array();
	var arPoint = new Array();
	var arThousand = new Array();
	var arDecimals = new Array();

	<?
	foreach ($arTemplates as $key => $ar)
	{
		echo "arFormat[".$key."]='".$ar["FORMAT"]."'; ";
		echo "arPoint[".$key."]='".$ar["DEC_POINT"]."'; ";
		echo "arThousand[".$key."]='".$ar["THOUSANDS_VARIANT"]."'; ";
		echo "arDecimals[".$key."]='".$ar["DECIMALS"]."'; ";
	}
	?>

	var sIndex = document.forms['form1'].elements['format_' + lang].selectedIndex;

	if (sIndex > 0)
	{
		document.forms['form1'].elements['FORMAT_STRING_' + lang].value = arFormat[sIndex-1];
		document.forms['form1'].elements['DEC_POINT_' + lang].value = arPoint[sIndex-1];
		document.forms['form1'].elements['THOUSANDS_VARIANT_' + lang].value = arThousand[sIndex-1];
		document.forms['form1'].elements['DECIMALS_' + lang].value = arDecimals[sIndex-1];
	}
}
function setThousandsVariant(lang)
{
	var value = document.forms['form1'].elements['THOUSANDS_VARIANT_' + lang].value;
	if(value.length > 0)
		document.forms['form1'].elements['THOUSANDS_SEP_' + lang].disabled = true;
	else
		document.forms['form1'].elements['THOUSANDS_SEP_' + lang].disabled = false;
}
</script>
<form method="post" action="<?$APPLICATION->GetCurPage()?>" name="form1">
<? echo bitrix_sessid_post(); ?>
<?echo GetFilterHiddens("filter_");?>
<input type="hidden" name="ID" value="<?echo $ID?>">
<input type="hidden" name="Update" value="Y">
<input type="hidden" name="from" value="<?echo htmlspecialcharsbx($from)?>">
<?if(strlen($return_url)>0):?><input type="hidden" name="return_url" value="<?=htmlspecialcharsbx($return_url)?>"><?endif?>

<?$tabControl->Begin();?>
<?$tabControl->BeginNextTab();?>

	<tr class="adm-detail-required-field">
		<td width="40%"><?echo GetMessage("currency_curr")?>:</td>
		<td width="60%">
		<?if (!$ID):?>
			<input type="text" value="<?echo htmlspecialcharsbx($f_CURRENCY)?>" size="3" name="CURRENCY" maxlength="3">
		<?else:?>
			<?=$ID; ?>
		<? endif?>
		</td>
	</tr>

	<tr class="adm-detail-required-field">
		<td><?echo GetMessage("currency_rate_cnt")?>:</td>
		<td>
			<input type="text" class="typeinput" size="10" name="AMOUNT_CNT" value="<?=htmlspecialcharsbx($f_AMOUNT_CNT)?>">
		</td>
	</tr>

	<tr class="adm-detail-required-field">
		<td><?echo GetMessage("currency_rate")?>:</td>
		<td>
			<input type="text" size="10" name="AMOUNT" value="<?=htmlspecialcharsbx($f_AMOUNT)?>" maxlength="10">
		</td>
	</tr>

	<tr>
		<td><?echo GetMessage("currency_sort_ex")?>:</td>
		<td>
			<input type="text" class="typeinput" size="10" name="SORT" value="<?echo intval($f_SORT)?>" maxlength="10">
		</td>
	</tr>
	<?for($i=0;$i<$iCount;$i++):?>
	<tr class="heading"><td colspan="2"><?=$arLangNamesLID[$i]?></td></tr>
	<tr>
		<td><?echo GetMessage("CURRENCY_FULL_NAME")?>:</td>
		<td><input class="typeinput" title="<?echo GetMessage("CURRENCY_FULL_NAME_DESC")?>" type="text" maxlength="50" size="15" name="FULL_NAME_<?echo $arLangsLID[$i]?>" value="<?=htmlspecialcharsbx(${"l_FULL_NAME_".$arLangsLID[$i]})?>"></td>
	</tr>

	<tr>
		<td><?echo GetMessage("CURRENCY_FORMAT_TEMPLATE")?>:</td>
		<td>
			<select name="format_<?echo $arLangsLID[$i]?>" OnChange="setTemplate('<?echo $arLangsLID[$i]?>')">
				<option value="">-<?echo GetMessage("CURRENCY_SELECT_TEMPLATE")?>-</option>
			<?foreach ($arTemplates as $key => $ar):?>
				<option value="<?=$key?>"><?=$ar["TEXT"]?></option>
			<?endforeach?>
			</select>
		</td>
	</tr>

	<tr class="adm-detail-required-field">
		<td><?echo GetMessage("CURRENCY_FORMAT_DESC")?>:</td>
		<td><input class="typeinput" title="<?echo GetMessage("CURRENCY_FORMAT_DESC")?>" type="text" maxlength="50" size="10" name="FORMAT_STRING_<?echo $arLangsLID[$i]?>" value="<?=htmlspecialcharsbx(${"l_FORMAT_STRING_".$arLangsLID[$i]})?>"></td>
	</tr>
	<tr>
		<td><?echo GetMessage("CURRENCY_DEC_POINT_DESC")?>:</td>
		<td><input class="typeinput" title="<?echo GetMessage("CURRENCY_DEC_POINT_DESC")?>" type="text" maxlength="5" size="5" name="DEC_POINT_<?echo $arLangsLID[$i]?>" value="<?=htmlspecialcharsbx(${"l_DEC_POINT_".$arLangsLID[$i]})?>"></td>
	</tr>
	<tr>
		<td><?echo GetMessage("THOU_SEP_DESC")?>:</td>
		<td>
		<select name="THOUSANDS_VARIANT_<?echo $arLangsLID[$i]?>" onChange="setThousandsVariant('<?echo $arLangsLID[$i]?>')">
			<option value="N"<?if(${"l_THOUSANDS_VARIANT_".$arLangsLID[$i]} == "N") echo " selected"?>><?=GetMessage("CURRENCY_THOUSANDS_VARIANT_N")?></option>
			<option value="D"<?if(${"l_THOUSANDS_VARIANT_".$arLangsLID[$i]} == "D") echo " selected"?>><?=GetMessage("CURRENCY_THOUSANDS_VARIANT_D")?></option>
			<option value="C"<?if(${"l_THOUSANDS_VARIANT_".$arLangsLID[$i]} == "C") echo " selected"?>><?=GetMessage("CURRENCY_THOUSANDS_VARIANT_C")?></option>
			<option value="S"<?if(${"l_THOUSANDS_VARIANT_".$arLangsLID[$i]} == "S") echo " selected"?>><?=GetMessage("CURRENCY_THOUSANDS_VARIANT_S")?></option>
			<option value="B"<?if(${"l_THOUSANDS_VARIANT_".$arLangsLID[$i]} == "B") echo " selected"?>><?=GetMessage("CURRENCY_THOUSANDS_VARIANT_B")?></option>
			<option value=""<?if(strlen(${"l_THOUSANDS_SEP_".$arLangsLID[$i]}) >0) echo " selected"?>><?=GetMessage("CURRENCY_THOUSANDS_VARIANT_O")?></option>
		</select>
		<input class="typeinput" title="<?echo GetMessage("THOU_SEP_DESC")?>" type="text" maxlength="5" size="5" name="THOUSANDS_SEP_<?echo $arLangsLID[$i]?>" value="<?=htmlspecialcharsbx(${"l_THOUSANDS_SEP_".$arLangsLID[$i]})?>">
		<script type="text/javascript">
		setThousandsVariant('<?=$arLangsLID[$i]?>');
		</script>
		</td>
	</tr>
	<tr>
		<td><?echo GetMessage("DECIMALS_DESC")?>:</td>
		<td><input class="typeinput" title="<?echo GetMessage("DECIMALS_DESC")?>" type="text" maxlength="5" size="5" name="DECIMALS_<?echo $arLangsLID[$i]?>" value="<?=htmlspecialcharsbx(${"l_DECIMALS_".$arLangsLID[$i]})?>"></td>
	</tr>

	<?endfor;?>

<?$tabControl->EndTab();?>
<?$tabControl->Buttons(Array("disabled" => $CURRENCY_RIGHT<"W", "back_url" =>"/bitrix/admin/currencies.php?lang=".LANGUAGE_ID));?>
<?$tabControl->End();?>
</form>
<?$tabControl->ShowWarnings("form1", $message);

echo BeginNote();
echo GetMessage("CURRENCY_BASE_CURRENCY")?><br />
<?echo EndNote();
?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>