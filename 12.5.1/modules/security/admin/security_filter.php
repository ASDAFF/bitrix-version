<?
define("ADMIN_MODULE_NAME", "security");

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/security/include.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/security/prolog.php");

IncludeModuleLangFile(__FILE__);

$RIGHT_R = $USER->CanDoOperation('security_filter_settings_read');
$RIGHT_W = $USER->CanDoOperation('security_filter_settings_write');
if(!$RIGHT_R && !$RIGHT_W)
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$aTabs = array(
	array(
		"DIV" => "main",
		"TAB" => GetMessage("SEC_FILTER_MAIN_TAB"),
		"ICON"=>"main_user_edit",
		"TITLE"=>GetMessage("SEC_FILTER_MAIN_TAB_TITLE"),
	),
	array(
		"DIV" => "params",
		"TAB" => GetMessage("SEC_FILTER_PARAMETERS_TAB"),
		"ICON"=>"main_user_edit",
		"TITLE"=>GetMessage("SEC_FILTER_PARAMETERS_TAB_TITLE"),
	),
	array(
		"DIV" => "exceptions",
		"TAB" => GetMessage("SEC_FILTER_EXCEPTIONS_TAB"),
		"ICON"=>"main_user_edit",
		"TITLE"=>GetMessage("SEC_FILTER_EXCEPTIONS_TAB_TITLE"),
	),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs, true, true);

$bVarsFromForm = false;

if($REQUEST_METHOD == "POST" && ($save!="" || $apply!="" || $filter_siteb!="") && $RIGHT_W && check_bitrix_sessid())
{

	if($filter_siteb!="")
		CSecurityFilter::SetActive($_POST["filter_active"]==="Y");

	if($_POST["filter_action"]==="clear")
		COption::SetOptionString("security", "filter_action", "clear");
	elseif($_POST["filter_action"]==="none")
		COption::SetOptionString("security", "filter_action", "none");
	else
		COption::SetOptionString("security", "filter_action", "filter");

	COption::SetOptionString("security", "filter_stop", $_POST["filter_stop"]==="Y"? "Y": "N");
	COption::SetOptionInt("security", "filter_duration", $_POST["filter_duration"]);
	COption::SetOptionString("security", "filter_log", $_POST["filter_log"]==="Y"? "Y": "N");

	CSecurityFilterMask::Update($_POST["FILTER_MASKS"]);

	if($save!="" && $_GET["return_url"]!="")
		LocalRedirect($_GET["return_url"]);
	LocalRedirect("/bitrix/admin/security_filter.php?lang=".LANGUAGE_ID.($return_url? "&return_url=".urlencode($_GET["return_url"]): "")."&".$tabControl->ActiveTabParam());
}

$rsSecurityFilterExclMask = CSecurityFilterMask::GetList();
if($rsSecurityFilterExclMask->Fetch())
	$bSecurityFilterExcl = true;
else
	$bSecurityFilterExcl = false;

$messageDetails = "";
if (CSecurityFilter::IsActive())
{
	$messageType = "OK";
	$messageText = GetMessage("SEC_FILTER_ON");
	if($bSecurityFilterExcl)
		$messageDetails = "<span style=\"font-style: italic;\">".GetMessage("SEC_FILTER_EXCL_FOUND")."</span>";
} else
{
	$messageType = "ERROR";
	$messageText = GetMessage("SEC_FILTER_OFF");
}

$APPLICATION->SetTitle(GetMessage("SEC_FILTER_TITLE"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

CAdminMessage::ShowMessage(array(
			"MESSAGE"=>$messageText,
			"TYPE"=>$messageType,
			"DETAILS"=>$messageDetails,
			"HTML"=>true
		));
?>

<script language="JavaScript">
<!--
function addNewRow(tableID)
{
	var tbl = document.getElementById(tableID);
	var cnt = tbl.rows.length;
	var oRow = tbl.insertRow(cnt-1);
	var oCell = oRow.insertCell(0);
	var sHTML=tbl.rows[cnt-2].cells[0].innerHTML;

	//styles hack
	oCell.style.cssText  = 'padding-bottom:3px;';

	var p = 0;
	while(true)
	{
		var s = sHTML.indexOf('[n',p);
		if(s<0)break;
		var e = sHTML.indexOf(']',s);
		if(e<0)break;
		var n = parseInt(sHTML.substr(s+2,e-s));
		sHTML = sHTML.substr(0, s)+'[n'+(++n)+']'+sHTML.substr(e+1);
		p=s+1;
	}
	var p = 0;
	while(true)
	{
		var s = sHTML.indexOf('__n',p);
		if(s<0)break;
		var e = sHTML.indexOf('__',s+2);
		if(e<0)break;
		var n = parseInt(sHTML.substr(s+3,e-s));
		sHTML = sHTML.substr(0, s)+'__n'+(++n)+'__'+sHTML.substr(e+2);
		p=e+2;
	}
	var p = 0;
	while(true)
	{
		var s = sHTML.indexOf('__N',p);
		if(s<0)break;
		var e = sHTML.indexOf('__',s+2);
		if(e<0)break;
		var n = parseInt(sHTML.substr(s+3,e-s));
		sHTML = sHTML.substr(0, s)+'__N'+(++n)+'__'+sHTML.substr(e+2);
		p=e+2;
	}
	var p = 0;
	while(true)
	{
		var s = sHTML.indexOf('xxn',p);
		if(s<0)break;
		var e = sHTML.indexOf('xx',s+2);
		if(e<0)break;
		var n = parseInt(sHTML.substr(s+3,e-s));
		sHTML = sHTML.substr(0, s)+'xxn'+(++n)+'xx'+sHTML.substr(e+2);
		p=e+2;
	}
	oCell.innerHTML = sHTML;

	var patt = new RegExp ("<"+"script"+">[^\000]*?<"+"\/"+"script"+">", "g");
	var code = sHTML.match(patt);
	if(code)
	{
		for(var i = 0; i < code.length; i++)
			if(code[i] != '')
				jsUtils.EvalGlobal(code[i]);
	}
}
//-->
</script>

<form method="POST" action="security_filter.php?lang=<?echo LANGUAGE_ID?><?echo $_GET["return_url"]? "&amp;return_url=".urlencode($_GET["return_url"]): ""?>" enctype="multipart/form-data" name="editform">
<?
$tabControl->Begin();
?>
<?
$tabControl->BeginNextTab();
?>
<?if(CSecurityFilter::IsActive()):?>
	<tr>
		<td colspan="2" align="left">
			<input type="hidden" name="filter_active" value="N">
			<input type="submit" name="filter_siteb" value="<?echo GetMessage("SEC_FILTER_BUTTON_OFF")?>"<?if(!$RIGHT_W) echo " disabled"?>>
		</td>
	</tr>
<?else:?>
	<tr>
		<td colspan="2" align="left">
			<input type="hidden" name="filter_active" value="Y">
			<input type="submit" name="filter_siteb" value="<?echo GetMessage("SEC_FILTER_BUTTON_ON")?>"<?if(!$RIGHT_W) echo " disabled"?> class="adm-btn-save">
		</td>
	</tr>
<?endif?>
<tr>
	<td colspan="2">
		<?echo BeginNote();?><?echo GetMessage("SEC_FILTER_NOTE")?>
		<?echo EndNote(); ?>
	</td>
</tr>
<?
$tabControl->BeginNextTab();
?>
<tr>
	<td width="40%" class="adm-detail-valign-top"><?echo GetMessage("SEC_FILTER_ACTION")?>:</td>
	<td width="60%">
		<label><input type="radio" name="filter_action" value="filter" <?if(COption::GetOptionString("security", "filter_action") != "clear" && COption::GetOptionString("security", "filter_action") != "none") echo "checked";?>><?echo GetMessage("SEC_FILTER_ACTION_FILTER")?><span class="required"><sup>1</sup></span></label><br>
		<label><input type="radio" name="filter_action" value="clear" <?if(COption::GetOptionString("security", "filter_action") == "clear") echo "checked";?>><?echo GetMessage("SEC_FILTER_ACTION_CLEAR")?></label><br>
		<label><input type="radio" name="filter_action" value="none" <?if(COption::GetOptionString("security", "filter_action") == "none") echo "checked";?>><?echo GetMessage("SEC_FILTER_ACTION_NONE")?></label><br>
	</td>
</tr>
<tr>
	<td><label for="filter_stop"><?echo GetMessage("SEC_FILTER_STOP")?></label><span class="required"><sup>2</sup></span>:</td>
	<td>
		<input type="checkbox" name="filter_stop" id="filter_stop" value="Y" <?if(COption::GetOptionString("security", "filter_stop") == "Y") echo "checked";?>>
	</td>
</tr>
<tr>
	<td><label for="filter_duration"><?echo GetMessage("SEC_FILTER_DURATION")?></label>:</td>
	<td>
		<input type="text" size="4" name="filter_duration" value="<?echo COption::GetOptionInt("security", "filter_duration")?>">
	</td>
</tr>
<tr>
	<td><label for="filter_log"><?echo GetMessage("SEC_FILTER_LOG")?></label>:</td>
	<td>
		<input type="checkbox" name="filter_log" id="filter_log" value="Y" <?if(COption::GetOptionString("security", "filter_log") == "Y") echo "checked";?>>
	</td>
</tr>
<tr>
	<td colspan="2">
		<?echo BeginNote();?>
		<span class="required"><sup>1</sup></span><?echo GetMessage("SEC_FILTER_ACTION_NOTE_1")?><br>
		<span class="required"><sup>2</sup></span><?echo GetMessage("SEC_FILTER_ACTION_NOTE_2")?><br>
		<?echo EndNote();?>
	</td>
</tr>
<?
$tabControl->BeginNextTab();
$arMasks = array();
if($bVarsFromForm)
{
	if(is_array($_POST["FILTER_MASKS"]))
		foreach($_POST["FILTER_MASKS"] as $i => $POST_MASK)
			$arMasks[] = array(
				"SITE_ID" => htmlspecialcharsbx($POST_MASK["SITE_ID"]),
				"FILTER_MASK" => htmlspecialcharsbx($POST_MASK["FILTER_MASK"]),
			);
}
else
{
	$rs = CSecurityFilterMask::GetList();
	while($ar = $rs->Fetch())
		$arMasks[] = array(
			"SITE_ID" => htmlspecialcharsbx($ar["SITE_ID"]),
			"FILTER_MASK" => htmlspecialcharsbx($ar["FILTER_MASK"]),
		);
}
?>
<tr>
	<td class="adm-detail-valign-top" width="40%"><?echo GetMessage("SEC_FILTER_MASKS")?></td>
	<td width="60%">
	<table cellpadding="0" cellspacing="0" border="0" class="nopadding" width="100%" id="tbFILTER_MASKS">
		<?foreach($arMasks as $i => $arMask):?>
			<tr><td nowrap style="padding-bottom: 3px;">
				<input type="text" size="45" name="FILTER_MASKS[<?echo $i?>][MASK]" value="<?echo $arMask["FILTER_MASK"]?>">&nbsp;<?echo GetMessage("SEC_FILTER_SITE")?>&nbsp;<?echo CSite::SelectBox("FILTER_MASKS[$i][SITE_ID]", $arMask["SITE_ID"], GetMessage("MAIN_ALL"), "");?><br>
			</td></tr>
		<?endforeach;?>
		<?if(!$bVarsFromForm):?>
			<tr><td nowrap style="padding-bottom: 3px;">
				<input type="text" size="45" name="FILTER_MASKS[n0][MASK]" value="">&nbsp;<?echo GetMessage("SEC_FILTER_SITE")?>&nbsp;<?echo CSite::SelectBox("FILTER_MASKS[n0][SITE_ID]", "", GetMessage("MAIN_ALL"), "");?><br>
			</td></tr>
		<?endif;?>
			<tr><td>
				<br><input type="button" value="<?echo GetMessage("SEC_FILTER_ADD")?>" onClick="addNewRow('tbFILTER_MASKS')">
			</td></tr>
		</table>
	</td>
</tr>
<?
$tabControl->Buttons(
	array(
		"disabled"=>(!$RIGHT_W),
		"back_url"=>$_GET["return_url"]? $_GET["return_url"]: "security_iprule_list.php?lang=".LANG,
	)
);
?>
<?echo bitrix_sessid_post();?>
<input type="hidden" name="lang" value="<?echo LANG?>">
<?
$tabControl->End();
?>
</form>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>