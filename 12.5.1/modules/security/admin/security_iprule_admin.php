<?
define("ADMIN_MODULE_NAME", "security");

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/security/include.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/security/prolog.php");

IncludeModuleLangFile(__FILE__);

$RIGHT_R = $USER->CanDoOperation('security_iprule_admin_settings_read');
$RIGHT_W = $USER->CanDoOperation('security_iprule_admin_settings_write');
if(!$RIGHT_R && !$RIGHT_W)
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$aTabs = array(
	array(
		"DIV" => "main",
		"TAB" => GetMessage("SEC_IPRULE_ADMIN_MAIN_TAB"),
		"ICON"=>"main_user_edit",
		"TITLE"=>GetMessage("SEC_IPRULE_ADMIN_MAIN_TAB_TITLE"),
	),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs, false, true);

$rsIPRule = CSecurityIPRule::GetList(array(), array(
	"=RULE_TYPE" => "A",
	"=ADMIN_SECTION" => "Y",
	"=SITE_ID" => false,
	"=SORT" => 10,
	"=ACTIVE_FROM" => false,
	"=ACTIVE_TO" => false,
), array("ID" => "ASC"));

$arIPRule = $rsIPRule->Fetch();
if($arIPRule)
{
	$ID = $arIPRule["ID"];
	$ACTIVE = $arIPRule["ACTIVE"];
}
else
{
	$ID = 0;
	$ACTIVE = "N";
}

$exclMasks=array();

foreach(GetModuleEvents("security", "OnIPRuleAdmin", true) as $event) {
	$exclMasks = array_merge($exclMasks,ExecuteModuleEventEx($event));
}

$strError = "";
$bVarsFromForm = false;
$bShowForce = false;
$message = CSecurityIPRule::CheckAntiFile(true);

if($REQUEST_METHOD == "POST" && ($save!="" || $apply!="" || $activate_iprule!="" || $deactivate_iprule!="") && $RIGHT_W && check_bitrix_sessid())
{
	$ob = new CSecurityIPRule;

	if(!$activate_iprule && $deactivate_iprule)
	{
		//When rule is going to be deactivated we will no check for IP
		$noExclIPS = false;
		$selfBlock = false;
	}
	else
	{
		//Otherwise check if ANY input supplied
		$noExclIPS = true;
		foreach($_POST["EXCL_IPS"] as $ip)
		{
			if(strlen(trim($ip)) > 0)
			{
				$noExclIPS = false;
				break;
			}
		}
		//AND it is not selfblocking rule
		$INCL_IPS = array("0.0.0.1-255.255.255.255");
		$selfBlock = $ob->CheckIP($INCL_IPS, $_POST["EXCL_IPS"]);
	}

	if($noExclIPS)
	{
		$message = new CAdminMessage(GetMessage("SEC_IPRULE_ADMIN_NO_IP"));
		$bVarsFromForm = true;
	}
	elseif($selfBlock && (COption::GetOptionString("security", "ipcheck_allow_self_block")!=="Y"))
	{
		if($e = $APPLICATION->GetException())
			$message = new CAdminMessage(GetMessage("SEC_IPRULE_ADMIN_SAVE_ERROR"), $e);
		$bVarsFromForm = true;
	}
	elseif($selfBlock && $_POST["USE_THE_FORCE_LUK"]!=="Y")
	{
		if($e = $APPLICATION->GetException())
			$message = new CAdminMessage(GetMessage("SEC_IPRULE_ADMIN_SAVE_ERROR"), $e);
		$bVarsFromForm = true;
		$bShowForce = true;
	}
	else
	{
		$arFields = array(
			"RULE_TYPE" => "A",
			"ACTIVE" => $activate_iprule? "Y": ($deactivate_iprule? "N": $ACTIVE),
			"ADMIN_SECTION" => "Y",
			"SITE_ID" => false,
			"SORT" => 10,
			"NAME" => GetMessage("SEC_IPRULE_ADMIN_RULE_NAME"),
			"ACTIVE_FROM" => false,
			"ACTIVE_TO" => false,
			"INCL_IPS" => $INCL_IPS,
			"EXCL_IPS" => $_POST["EXCL_IPS"],
			"INCL_MASKS" => array("/bitrix/admin/*"),
			"EXCL_MASKS" => $exclMasks,
		);
		if($ID > 0)
		{
			$res = $ob->Update($ID, $arFields);
		}
		else
		{
			$ID = $ob->Add($arFields);
			$res = ($ID>0);
		}

		if($res)
		{
			if($save!="" && $_GET["return_url"]!="")
				LocalRedirect($_GET["return_url"]);
			LocalRedirect("/bitrix/admin/security_iprule_admin.php?lang=".LANGUAGE_ID.($return_url? "&return_url=".urlencode($_GET["return_url"]): "")."&".$tabControl->ActiveTabParam());
		}
		else
		{
			if($e = $APPLICATION->GetException())
				$message = new CAdminMessage(GetMessage("SEC_IPRULE_ADMIN_SAVE_ERROR"), $e);
			$bVarsFromForm = true;
		}
	}
}

$messageDetails = "";
if ($ID > 0 && $ACTIVE=="Y")
{
	$messageType = "OK";
	$messageText = GetMessage("SEC_IPRULE_ADMIN_ON");
} else
{
	$messageType = "ERROR";
	$messageText = GetMessage("SEC_IPRULE_ADMIN_OFF");
}

$APPLICATION->SetTitle(GetMessage("SEC_IPRULE_ADMIN_TITLE"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

if($message)
	echo $message->Show();

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

<form method="POST" action="security_iprule_admin.php?lang=<?echo LANGUAGE_ID?><?echo $_GET["return_url"]? "&amp;return_url=".urlencode($_GET["return_url"]): ""?>"  enctype="multipart/form-data" name="editform">
<?
$tabControl->Begin();
?>
<?
$tabControl->BeginNextTab();
?>
<tr>
	<td colspan="2" align="left">
		<?if($ID > 0 && $ACTIVE=="Y"):?>
			<input type="submit" name="deactivate_iprule" value="<?echo GetMessage("SEC_IPRULE_ADMIN_BUTTON_OFF")?>"<?if(!$RIGHT_W) echo " disabled"?>>
		<?else:?>
			<input type="submit" name="activate_iprule" value="<?echo GetMessage("SEC_IPRULE_ADMIN_BUTTON_ON")?>"<?if(!$RIGHT_W) echo " disabled"?> class="adm-btn-save">
		<?endif?>
	</td>
</tr>
<tr>
	<td colspan="2">
		<?echo BeginNote();?><?echo GetMessage("SEC_IPRULE_ADMIN_NOTE", array("#IP#" => $_SERVER["REMOTE_ADDR"]))?>
		<?echo EndNote(); ?>
	</td>
</tr>
<?
$arExclIPs = array();
if($bVarsFromForm)
{
	if(is_array($_POST["EXCL_IPS"]))
		foreach($_POST["EXCL_IPS"] as $i => $ip)
			$arExclIPs[] = htmlspecialcharsbx($ip);
}
elseif($ID > 0)
{
	$ar = CSecurityIPRule::GetRuleExclIPs($ID);
	foreach($ar as $i => $ip)
		$arExclIPs[] = htmlspecialcharsbx($ip);
}
?>
<tr>
	<td class="adm-detail-valign-top" width="40%"><?echo GetMessage("SEC_IPRULE_ADMIN_EXCL_IPS")?>:<br><?echo GetMessage("SEC_IPRULE_ADMIN_EXCL_IPS_SAMPLE")?></td>
	<td width="60%">
	<table cellpadding="0" cellspacing="0" border="0" class="nopadding" width="100%" id="tbEXCL_IPS">
		<?foreach($arExclIPs as $i => $ip):?>
			<tr><td nowrap style="padding-bottom: 3px;">
				<input type="text" size="45" name="EXCL_IPS[<?echo $i?>]" value="<?echo $ip?>">
			</td></tr>
		<?endforeach;?>
		<?if(!$bVarsFromForm):?>
			<tr><td nowrap style="padding-bottom: 3px;">
				<input type="text" size="45" name="EXCL_IPS[n0]" value="">
			</td></tr>
		<?endif;?>
			<tr><td>
				<br><input type="button" value="<?echo GetMessage("SEC_IPRULE_ADMIN_ADD")?>" onClick="addNewRow('tbEXCL_IPS')">
			</td></tr>
		</table>
	</td>
</tr>
<?
if (count($exclMasks) > 0)
{
?>
<tr>
	<td class="adm-detail-valign-top" width="40%"><?echo GetMessage("SEC_IPRULE_ADMIN_EXCL_FILES_".(($ACTIVE == 'Y')?'ACTIVE':'INACTIVE'))?></td>
	<td width="60%">
		<table cellpadding="0" cellspacing="0" border="0" class="nopadding" width="100%" id="tbEXCL_FILES">
		<?foreach($exclMasks as $mask):?>
			<tr><td nowrap>
				<?echo htmlspecialcharsbx($mask)?>
			</td></tr>
		<?endforeach;?>
		</table>
	</td>
</tr>
<?
}
$tabControl->Buttons(
	array(
		"disabled"=>(!$RIGHT_W),
		"back_url"=>$_GET["return_url"]? $_GET["return_url"]: "security_iprule_admin.php?lang=".LANG,
	)
);
?>
<?echo bitrix_sessid_post();?>
<input type="hidden" name="lang" value="<?echo LANG?>">
<?if($bShowForce && (COption::GetOptionString("security", "ipcheck_allow_self_block")==="Y")):?>
	<input type="hidden" name="USE_THE_FORCE_LUK" value="Y">
<?endif;?>
<?
$tabControl->End();
?>
</form>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>