<?
define("ADMIN_MODULE_NAME", "security");

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/security/include.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/security/prolog.php");

IncludeModuleLangFile(__FILE__);

$RIGHT_R = $USER->CanDoOperation('security_redirect_settings_read');
$RIGHT_W = $USER->CanDoOperation('security_redirect_settings_write');
if(!$RIGHT_R && !$RIGHT_W)
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$aTabs = array(
	array(
		"DIV" => "main",
		"TAB" => GetMessage("SEC_REDIRECT_MAIN_TAB"),
		"ICON"=>"main_user_edit",
		"TITLE"=>GetMessage("SEC_REDIRECT_MAIN_TAB_TITLE"),
	),
	array(
		"DIV" => "parameters",
		"TAB" => GetMessage("SEC_REDIRECT_PARAMETERS_TAB"),
		"ICON"=>"main_user_edit",
		"TITLE"=>GetMessage("SEC_REDIRECT_PARAMETERS_TAB_TITLE"),
	),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs, true, true);

if($REQUEST_METHOD == "POST" && ($save!="" || $apply!="" || $redirect_button!="") && $RIGHT_W && check_bitrix_sessid())
{

	if($redirect_button!="")
		CSecurityRedirect::SetActive($_POST["redirect_active"]==="Y");

	COption::SetOptionString("security", "redirect_log", $_POST["redirect_log"]==="Y"? "Y": "N");
	COption::SetOptionString("security", "redirect_referer_check", $_POST["redirect_referer_check"]==="Y"? "Y": "N");
	COption::SetOptionString("security", "redirect_referer_site_check", $_POST["redirect_referer_site_check"]==="Y"? "Y": "N");
	COption::SetOptionString("security", "redirect_href_sign", $_POST["redirect_href_sign"]==="Y"? "Y": "N");
	if($_POST["redirect_action"]==="show_message")
	{
		COption::SetOptionString("security", "redirect_action", "show_message");
		COption::RemoveOption("security", "redirect_message_warning");
		$l = CLanguage::GetList($lby="sort", $lorder="asc");
		while($ar = $l->Fetch())
		{
			$mess = trim($_POST["redirect_message_warning_".$ar["LID"]]);
			if(strlen($mess) > 0)
				COption::SetOptionString("security", "redirect_message_warning_".$ar["LID"], $mess);
			else
				COption::RemoveOption("security", "redirect_message_warning_".$ar["LID"]);
		}
		COption::SetOptionString("security", "redirect_message_charset", LANG_CHARSET);
		COption::SetOptionInt("security", "redirect_message_timeout", $_POST["redirect_message_timeout"]);
	}
	else
	{
		COption::SetOptionString("security", "redirect_action", "force_url");
		COption::SetOptionString("security", "redirect_url", $_POST["redirect_url"]);
	}

	CSecurityRedirect::Update($_POST["URLS"]);

	if($save!="" && $_GET["return_url"]!="")
		LocalRedirect($_GET["return_url"]);
	LocalRedirect("/bitrix/admin/security_redirect.php?lang=".LANGUAGE_ID.($return_url? "&return_url=".urlencode($_GET["return_url"]): "")."&".$tabControl->ActiveTabParam());
}

$messageDetails = "";
if (CSecurityRedirect::IsActive())
{
	$messageType = "OK";
	$messageText = GetMessage("SEC_REDIRECT_ON");
} else
{
	$messageType = "ERROR";
	$messageText = GetMessage("SEC_REDIRECT_OFF");
}

$APPLICATION->SetTitle(GetMessage("SEC_REDIRECT_TITLE"));

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

<form method="POST" action="security_redirect.php?lang=<?echo LANGUAGE_ID?><?echo $_GET["return_url"]? "&amp;return_url=".urlencode($_GET["return_url"]): ""?>" enctype="multipart/form-data" name="editform">
<?
$tabControl->Begin();
?>
<?
$tabControl->BeginNextTab();
?>
<?if(CSecurityRedirect::IsActive()):?>
	<tr>
		<td colspan="2" align="left">
			<input type="hidden" name="redirect_active" value="N">
			<input type="submit" name="redirect_button" value="<?echo GetMessage("SEC_REDIRECT_BUTTON_OFF")?>"<?if(!$RIGHT_W) echo " disabled"?>>
		</td>
	</tr>
<?else:?>
	<tr>
		<td colspan="2" align="left">
			<input type="hidden" name="redirect_active" value="Y">
			<input type="submit" name="redirect_button" value="<?echo GetMessage("SEC_REDIRECT_BUTTON_ON")?>"<?if(!$RIGHT_W) echo " disabled"?> class="adm-btn-save">
		</td>
	</tr>
<?endif?>
<tr>
	<td colspan="2">
		<?echo BeginNote();?><?echo GetMessage("SEC_REDIRECT_NOTE")?>
		<?echo EndNote(); ?>
	</td>
</tr>
<?
$tabControl->BeginNextTab();
$arSysUrls = array();
$arUsrUrls = array();
$rs = CSecurityRedirect::GetList();
while($ar = $rs->Fetch())
{
	if($ar["IS_SYSTEM"] == "Y")
		$arSysUrls[] = array(
			"URL" => htmlspecialcharsbx($ar["URL"]),
			"PARAMETER_NAME" => htmlspecialcharsbx($ar["PARAMETER_NAME"]),
		);
	else
		$arUsrUrls[] = array(
			"URL" => htmlspecialcharsbx($ar["URL"]),
			"PARAMETER_NAME" => htmlspecialcharsbx($ar["PARAMETER_NAME"]),
		);
}
?>
<tr class="heading">
	<td colspan="2"><?echo GetMessage("SEC_REDIRECT_METHODS_HEADER")?></td>
</tr>
<tr>
	<td class="adm-detail-valign-top" width="40%"><?echo GetMessage("SEC_REDIRECT_METHODS")?></td>
	<td width="60%">
		<div class="adm-list">
			<div class="adm-list-item">
				<div class="adm-list-control"><input type="checkbox" name="redirect_referer_check" id="redirect_referer_check" value="Y" <?if(COption::GetOptionString("security", "redirect_referer_check") == "Y") echo "checked";?>></div>
				<div class="adm-list-label"><label for="redirect_referer_check"><?echo GetMessage("SEC_REDIRECT_REFERER_CHECK")?></label></div>
			</div>
			<div class="adm-list-item">
				<div class="adm-list-control"><input type="checkbox" name="redirect_referer_site_check" id="redirect_referer_site_check" value="Y" <?if(COption::GetOptionString("security", "redirect_referer_site_check") == "Y") echo "checked";?>></div>
				<div class="adm-list-label"><label for="redirect_referer_site_check"><?echo GetMessage("SEC_REDIRECT_REFERER_SITE_CHECK")?></label></div>
			</div>
			<div class="adm-list-item">
				<div class="adm-list-control"><input type="checkbox" name="redirect_href_sign" id="redirect_href_sign" value="Y" <?if(COption::GetOptionString("security", "redirect_href_sign") == "Y") echo "checked";?>></div>
				<div class="adm-list-label"><label for="redirect_href_sign"><?echo GetMessage("SEC_REDIRECT_HREF_SIGN")?></label></div>
			</div>
		</div>
	</td>
</tr>
<tr>
	<td class="adm-detail-valign-top"><?echo GetMessage("SEC_REDIRECT_URLS")?>:</td>
	<td>
		<?echo GetMessage("SEC_REDIRECT_SYSTEM")?><br>
	<table cellpadding="2" cellspacing="2" border="0" width="100%" id="tbURLS">
		<?foreach($arSysUrls as $i => $arUrl):?>
			<tr><td nowrap style="padding-bottom: 3px;">
				<?echo GetMessage("SEC_REDIRECT_URL")?>&nbsp;<input type="text" size="35" name="URLS[<?echo $i?>][URL]" value="<?echo $arUrl["URL"]?>" disabled>&nbsp;<?echo GetMessage("SEC_REDIRECT_PARAMETER_NAME")?>&nbsp;<input type="text" size="15" name="URLS[<?echo $i?>][PARAMETER_NAME]" value="<?echo $arUrl["PARAMETER_NAME"]?>" disabled><br>
			</td></tr>
		<?endforeach;?>
		<tr><td nowrap style="padding-bottom: 3px;">
			<br><?echo GetMessage("SEC_REDIRECT_USER")?>
		</td></tr>
		<?foreach($arUsrUrls as $i => $arUrl):?>
			<tr><td nowrap style="padding-bottom: 3px;">
				<?echo GetMessage("SEC_REDIRECT_URL")?>&nbsp;<input type="text" size="35" name="URLS[<?echo $i?>][URL]" value="<?echo $arUrl["URL"]?>">&nbsp;<?echo GetMessage("SEC_REDIRECT_PARAMETER_NAME")?>&nbsp;<input type="text" size="15" name="URLS[<?echo $i?>][PARAMETER_NAME]" value="<?echo $arUrl["PARAMETER_NAME"]?>"><br>
			</td></tr>
		<?endforeach;?>
		<tr><td nowrap>
			<?echo GetMessage("SEC_REDIRECT_URL")?>&nbsp;<input type="text" size="35" name="URLS[n0][URL]" value="">&nbsp;<?echo GetMessage("SEC_REDIRECT_PARAMETER_NAME")?>&nbsp;<input type="text" size="15" name="URLS[n0][PARAMETER_NAME]" value=""><br>
		</td></tr>
		<tr><td>
			<br><input type="button" value="<?echo GetMessage("SEC_REDIRECT_ADD")?>" onClick="addNewRow('tbURLS')">
		</td></tr>
	</table>
	</td>
</tr>
<tr class="heading">
	<td colspan="2"><?echo GetMessage("SEC_REDIRECT_ACTIONS_HEADER")?></td>
</tr>
<tr>
	<td  class="adm-detail-valign-top" width="40%"><?echo GetMessage("SEC_REDIRECT_ACTIONS")?></td>
	<td width="60%">
		<script>
		function Toggle(input)
		{
			var flag = true;
			if(input.id == 'redirect_show_message')
				flag = true;
			if(input.id == 'redirect_force_url')
				flag = false;

			document.getElementById('redirect_url').disabled = flag;
			document.getElementById('redirect_message_timeout').disabled = !flag;
			var c = arLangs.length;
			for(var i = 0; i < c; i++)
				document.getElementById('redirect_message_warning_'+arLangs[i]).disabled = !flag;
		}
		</script>
		<label><input type="radio" name="redirect_action" id="redirect_show_message" value="show_message" <?if(COption::GetOptionString("security", "redirect_action") == "show_message") echo "checked";?> onClick="Toggle(this);"><?echo GetMessage("SEC_REDIRECT_ACTION_SHOW_MESSAGE")?></label><br>
		<table style="margin-left:24px">
		<?
		$disabled = COption::GetOptionString("security", "redirect_action") == "force_url";
		$l = CLanguage::GetList($lby="sort", $lorder="asc");
		$arLangs = array();
		while($ar = $l->GetNext()):?>
			<tr class="adm-detail-valign-top">
				<td><?echo GetMessage("SEC_REDIRECT_MESSAGE")."(".$ar["LID"].")"?></td>
				<td><textarea name="redirect_message_warning_<?echo $ar["LID"]?>" id="redirect_message_warning_<?echo $ar["LID"]?>" cols=40 rows=5 <?if($disabled) echo "disabled";?>
				><?
				$mess = trim(COption::GetOptionString("security", "redirect_message_warning_".$ar["LID"]));
				if(strlen($mess) <= 0)
					$mess = trim(COption::GetOptionString("security", "redirect_message_warning"));
				if(strlen($mess) <= 0)
					$mess = trim(CSecurityRedirect::GetDefaultMessage($ar["LID"]));
				echo htmlspecialcharsbx($mess);
				$arLangs[] = $ar["LID"];
				?></textarea></td>
			</tr>
		<?endwhile?>
		<tr>
			<td>
				<script>
					var arLangs = <?echo CUtil::PHPToJSObject($arLangs);?>;
				</script>
				<?echo GetMessage("SEC_REDIRECT_TIMEOUT")?>
			</td>
			<td>
				<input type="text" name="redirect_message_timeout" id="redirect_message_timeout" value="<?echo COption::GetOptionInt("security", "redirect_message_timeout")?>" size="4" <?if(COption::GetOptionString("security", "redirect_action") == "force_url") echo "disabled";?>><?echo GetMessage("SEC_REDIRECT_TIMEOUT_SEC")?>
			</td>
		</tr>
		</table>
		<label><input type="radio" name="redirect_action" id="redirect_force_url" value="force_url" <?if(COption::GetOptionString("security", "redirect_action") == "force_url") echo "checked";?> onClick="Toggle(this);"><?echo GetMessage("SEC_REDIRECT_ACTION_REDIRECT")?></label><br>
		<table style="margin-left:24px">
		<tr><td>
		<?echo GetMessage("SEC_REDIRECT_ACTION_REDIRECT_URL")?></td><td><input type="text" name="redirect_url" id="redirect_url" value="<?echo htmlspecialcharsbx(COption::GetOptionString("security", "redirect_url"))?>" size="45" <?if(COption::GetOptionString("security", "redirect_action") == "show_message") echo "disabled";?>>
		</td></tr>
		</table>
	</td>
</tr>
<tr>
	<td width="40%"><label for="filter_log"><?echo GetMessage("SEC_REDIRECT_LOG")?></label>:</td>
	<td width="60%">
		<input type="checkbox" name="redirect_log" id="redirect_log" value="Y" <?if(COption::GetOptionString("security", "redirect_log") == "Y") echo "checked";?>>
	</td>
</tr>
<?
$tabControl->Buttons(
	array(
		"disabled"=>(!$RIGHT_W),
		"back_url"=>$_GET["return_url"]? $_GET["return_url"]: "security_redirect.php?lang=".LANG,
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