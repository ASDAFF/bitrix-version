<?
define("ADMIN_MODULE_NAME", "security");

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/security/include.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/security/prolog.php");

IncludeModuleLangFile(__FILE__);

$RIGHT_R = $USER->CanDoOperation('security_antivirus_settings_read');
$RIGHT_W = $USER->CanDoOperation('security_antivirus_settings_write');
if(!$RIGHT_R && !$RIGHT_W)
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$rsSecurityWhiteList = CSecurityAntiVirus::GetWhiteList();
if($rsSecurityWhiteList->Fetch())
	$bSecurityWhiteList = true;
else
	$bSecurityWhiteList = false;

$aTabs = array(
	array(
		"DIV" => "main",
		"TAB" => GetMessage("SEC_ANTIVIRUS_MAIN_TAB"),
		"ICON"=>"main_user_edit",
		"TITLE"=>GetMessage("SEC_ANTIVIRUS_MAIN_TAB_TITLE"),
	),
	array(
		"DIV" => "params",
		"TAB" => GetMessage("SEC_ANTIVIRUS_PARAMETERS_TAB"),
		"ICON"=>"main_user_edit",
		"TITLE"=>GetMessage("SEC_ANTIVIRUS_PARAMETERS_TAB_TITLE"),
	),
	array(
		"DIV" => "exceptions",
		"TAB" => $bSecurityWhiteList? GetMessage("SEC_ANTIVIRUS_WHITE_LIST_SET_TAB"): GetMessage("SEC_ANTIVIRUS_WHITE_LIST_TAB"),
		"ICON"=>"main_user_edit",
		"TITLE"=>GetMessage("SEC_ANTIVIRUS_WHITE_LIST_TAB_TITLE"),
	),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs, true, true);

$bVarsFromForm = false;

if($REQUEST_METHOD == "POST" && ($save!="" || $apply!="" || $antivirus_b!="") && $RIGHT_W && check_bitrix_sessid())
{

	if($antivirus_b!="")
		CSecurityAntiVirus::SetActive($_POST["antivirus_active"]==="Y");

	$antivirus_timeout = intval($_POST["antivirus_timeout"]);
	if($antivirus_timeout <= 0)
		$antivirus_timeout = 1;
	COption::SetOptionInt("security", "antivirus_timeout", $antivirus_timeout);

	if($_POST["antivirus_action"]==="notify_only")
		COption::SetOptionString("security", "antivirus_action", "notify_only");
	else
		COption::SetOptionString("security", "antivirus_action", "replace");

	CSecurityAntiVirus::UpdateWhiteList($_POST["WHITE_LIST"]);

	if($save!="" && $_GET["return_url"]!="")
		LocalRedirect($_GET["return_url"]);
	LocalRedirect("/bitrix/admin/security_antivirus.php?lang=".LANGUAGE_ID.($return_url? "&return_url=".urlencode($_GET["return_url"]): "")."&".$tabControl->ActiveTabParam());
}

$messageDetails = "";
if (CSecurityAntiVirus::IsActive())
{
	$messageType = "OK";
	$messageText = GetMessage("SEC_ANTIVIRUS_ON");
	if($bSecurityWhiteList || COption::GetOptionString("security", "antivirus_action") == "notify_only")
		$messageDetails = "<span style=\"font-style: italic;\">".GetMessage("SEC_ANTIVIRUS_WARNING")."</span>";
} else
{
	$messageType = "ERROR";
	$messageText = GetMessage("SEC_ANTIVIRUS_OFF");
}

$warningMessage = "";
if(!defined("BX_SECURITY_AV_STARTED")){
	if(preg_match("/cgi/i", php_sapi_name()))
		$warningMessage = GetMessage("SEC_ANTIVIRUS_PREBODY_NOTFOUND_CGI", array("#PATH#" => $_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/security/tools/start.php"));
	else
		$warningMessage = GetMessage("SEC_ANTIVIRUS_PREBODY_NOTFOUND", array("#PATH#" => $_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/security/tools/start.php"));
}

$APPLICATION->SetTitle(GetMessage("SEC_ANTIVIRUS_TITLE"));

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

<form method="POST" action="security_antivirus.php?lang=<?echo LANGUAGE_ID?><?echo $_GET["return_url"]? "&amp;return_url=".urlencode($_GET["return_url"]): ""?>" enctype="multipart/form-data" name="editform">
<?
$tabControl->Begin();
?>
<?
$tabControl->BeginNextTab();
?>
<?if(CSecurityAntiVirus::IsActive()):?>
	<tr>
		<td colspan="2" align="left">
			<input type="hidden" name="antivirus_active" value="N">
			<input type="submit" name="antivirus_b" value="<?echo GetMessage("SEC_ANTIVIRUS_BUTTON_OFF")?>"<?if(!$RIGHT_W) echo " disabled"?>>
		</td>
	</tr>
<?else:?>
	<tr>
		<td colspan="2" align="left">
			<input type="hidden" name="antivirus_active" value="Y">
			<input type="submit" name="antivirus_b" value="<?echo GetMessage("SEC_ANTIVIRUS_BUTTON_ON")?>"<?if(!$RIGHT_W) echo " disabled"?> class="adm-btn-save">
		</td>
	</tr>
<?endif?>
<?if(strlen($warningMessage) > 0):?>
	<tr>
		<td colspan="2" align="left">
			<?
				CAdminMessage::ShowMessage(array(
					"TYPE"=>"ERROR",
					"DETAILS"=>$warningMessage,
					"HTML"=>true
				));
			?>
		</td>
	</tr>
<?endif;?>
<tr>
	<td colspan="2">
		<?echo BeginNote();?>
		<?echo GetMessage("SEC_ANTIVIRUS_NOTE")?>
		<p><i><?echo GetMessage("SEC_ANTIVIRUS_LEVEL")?></i></p>
		<?echo EndNote(); ?>
	</td>
</tr>
<?
$tabControl->BeginNextTab();
?>
<tr>
	<td class="adm-detail-valign-top" width="40%"><?echo GetMessage("SEC_ANTIVIRUS_ACTION")?>:</td>
	<td width="60%">
		<label><input type="radio" name="antivirus_action" value="replace" <?if(COption::GetOptionString("security", "antivirus_action") != "notify_only") echo "checked";?>><?echo GetMessage("SEC_ANTIVIRUS_ACTION_REPLACE")?></span></label><br>
		<label><input type="radio" name="antivirus_action" value="notify_only" <?if(COption::GetOptionString("security", "antivirus_action") == "notify_only") echo "checked";?>><?echo GetMessage("SEC_ANTIVIRUS_ACTION_NOTIFY_ONLY")?></label><br>
	</td>
</tr>
<tr>
	<td><label for="antivirus_timeout"><?echo GetMessage("SEC_ANTIVIRUS_TIMEOUT")?></label>:</td>
	<td>
		<input type="text" size="4" name="antivirus_timeout" value="<?echo COption::GetOptionInt("security", "antivirus_timeout")?>">
	</td>
</tr>
<?
$tabControl->BeginNextTab();
$arWhiteList = array();
if($bVarsFromForm)
{
	if(is_array($_POST["WHITE_LIST"]))
		foreach($_POST["WHITE_LIST"] as $i => $v)
			$arWhiteList[] = htmlspecialcharsbx($v);
}
else
{
	$rs = CSecurityAntiVirus::GetWhiteList();
	while($ar = $rs->Fetch())
		$arWhiteList[] = htmlspecialcharsbx($ar["WHITE_SUBSTR"]);
}
?>
<tr>
	<td class="adm-detail-valign-top" width="40%" style="padding-top:12px;"><?echo GetMessage("SEC_ANTIVIRUS_WHITE_LIST")?></td>
	<td width="60%">
	<table cellpadding="0" cellspacing="0" border="0" width="100%" id="tb_WHITE_LIST">
		<?foreach($arWhiteList as $i => $white_substr):?>
			<tr><td nowrap style="padding-bottom: 3px;">
				<input type="text" size="45" name="WHITE_LIST[<?echo $i?>]" value="<?echo $white_substr?>">
			</td></tr>
		<?endforeach;?>
		<?if(!$bVarsFromForm):?>
			<tr><td nowrap style="padding-bottom: 3px;">
				<input type="text" size="45" name="WHITE_LIST[n0]" value="">
			</td></tr>
		<?endif;?>
			<tr><td>
				<br><input type="button" value="<?echo GetMessage("SEC_ANTIVIRUS_ADD")?>" onClick="addNewRow('tb_WHITE_LIST')">
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