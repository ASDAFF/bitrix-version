<?
define("ADMIN_MODULE_NAME", "security");

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/security/include.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/security/prolog.php");

IncludeModuleLangFile(__FILE__);

$RIGHT_R = $USER->CanDoOperation('security_frame_settings_read');
$RIGHT_W = $USER->CanDoOperation('security_frame_settings_write');
if(!$RIGHT_R && !$RIGHT_W)
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$aTabs = array(
	array(
		"DIV" => "main",
		"TAB" => GetMessage("SEC_FRAME_MAIN_TAB"),
		"ICON"=>"main_user_edit",
		"TITLE"=>GetMessage("SEC_FRAME_MAIN_TAB_TITLE"),
	),
	array(
		"DIV" => "exceptions",
		"TAB" => GetMessage("SEC_FRAME_EXCEPTIONS_TAB"),
		"ICON"=>"main_user_edit",
		"TITLE"=>GetMessage("SEC_FRAME_EXCEPTIONS_TAB_TITLE"),
	),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs, true, true);

$bVarsFromForm = false;

if($REQUEST_METHOD == "POST" && ($save.$apply.$frame_siteb!="") && $RIGHT_W && check_bitrix_sessid())
{

	if($frame_siteb!="")
		CSecurityFrame::SetActive($_POST["frame_active"]==="Y");

	CSecurityFrameMask::Update($_POST["FRAME_MASKS"]);

	if($save!="" && $_GET["return_url"]!="")
		LocalRedirect($_GET["return_url"]);
	LocalRedirect("/bitrix/admin/security_frame.php?lang=".LANGUAGE_ID.($return_url? "&return_url=".urlencode($_GET["return_url"]): "")."&".$tabControl->ActiveTabParam());
}

$rsSecurityFrameExclMask = CSecurityFrameMask::GetList();
if($rsSecurityFrameExclMask->Fetch())
	$bSecurityFrameExcl = true;
else
	$bSecurityFrameExcl = false;

$messageDetails = "";
if (CSecurityFrame::IsActive())
{
	$messageType = "OK";
	$messageText = GetMessage("SEC_FRAME_ON");
	if($bSecurityFrameExcl)
		$messageDetails = "<span style=\"font-style: italic;\">".GetMessage("SEC_FRAME_EXCL_FOUND")."</span>";
} else
{
	$messageType = "ERROR";
	$messageText = GetMessage("SEC_FRAME_OFF");
}

$APPLICATION->SetTitle(GetMessage("SEC_FRAME_TITLE"));

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

<form method="POST" action="security_frame.php?lang=<?echo LANGUAGE_ID?><?echo $_GET["return_url"]? "&amp;return_url=".urlencode($_GET["return_url"]): ""?>" enctype="multipart/form-data" name="editform">
<?
$tabControl->Begin();
?>
<?
$tabControl->BeginNextTab();
?>
<?if(CSecurityFrame::IsActive()):?>
	<tr>
		<td colspan="2" align="left">
			<input type="hidden" name="frame_active" value="N">
			<input type="submit" name="frame_siteb" value="<?echo GetMessage("SEC_FRAME_BUTTON_OFF")?>"<?if(!$RIGHT_W) echo " disabled"?>>
		</td>
	</tr>
<?else:?>
	<tr>
		<td colspan="2" align="left">
			<input type="hidden" name="frame_active" value="Y">
			<input type="submit" name="frame_siteb" value="<?echo GetMessage("SEC_FRAME_BUTTON_ON")?>"<?if(!$RIGHT_W) echo " disabled"?> class="adm-btn-save">
		</td>
	</tr>
<?endif?>
<tr>
	<td colspan="2">
		<?echo BeginNote();?><?echo GetMessage("SEC_FRAME_NOTE")?>
		<?echo EndNote(); ?>
	</td>
</tr>
<?
$tabControl->BeginNextTab();
$arMasks = array();
if($bVarsFromForm)
{
	if(is_array($_POST["FRAME_MASKS"]))
		foreach($_POST["FRAME_MASKS"] as $i => $POST_MASK)
			$arMasks[] = array(
				"SITE_ID" => htmlspecialcharsbx($POST_MASK["SITE_ID"]),
				"FRAME_MASK" => htmlspecialcharsbx($POST_MASK["FRAME_MASK"]),
			);
}
else
{
	$rs = CSecurityFrameMask::GetList();
	while($ar = $rs->Fetch())
		$arMasks[] = array(
			"SITE_ID" => htmlspecialcharsbx($ar["SITE_ID"]),
			"FRAME_MASK" => htmlspecialcharsbx($ar["FRAME_MASK"]),
		);
}
?>
<tr>
	<td class="adm-detail-valign-top" width="40%"><?echo GetMessage("SEC_FRAME_MASKS")?></td>
	<td width="60%">
	<table cellpadding="0" cellspacing="0" border="0" class="nopadding" width="100%" id="tbFRAME_MASKS">
		<?foreach($arMasks as $i => $arMask):?>
			<tr><td nowrap style="padding-bottom: 3px;">
				<input type="text" size="45" name="FRAME_MASKS[<?echo $i?>][MASK]" value="<?echo $arMask["FRAME_MASK"]?>">&nbsp;<?echo GetMessage("SEC_FRAME_SITE")?>&nbsp;<?echo CSite::SelectBox("FRAME_MASKS[$i][SITE_ID]", $arMask["SITE_ID"], GetMessage("MAIN_ALL"), "");?><br>
			</td></tr>
		<?endforeach;?>
		<?if(!$bVarsFromForm):?>
			<tr><td nowrap style="padding-bottom: 3px;">
				<input type="text" size="45" name="FRAME_MASKS[n0][MASK]" value="">&nbsp;<?echo GetMessage("SEC_FRAME_SITE")?>&nbsp;<?echo CSite::SelectBox("FRAME_MASKS[n0][SITE_ID]", "", GetMessage("MAIN_ALL"), "");?><br>
			</td></tr>
		<?endif;?>
			<tr><td>
				<br><input type="button" value="<?echo GetMessage("SEC_FRAME_ADD")?>" onClick="addNewRow('tbFRAME_MASKS')">
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