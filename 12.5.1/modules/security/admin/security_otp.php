<?
define("ADMIN_MODULE_NAME", "security");

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/security/include.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/security/prolog.php");

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/security/options_user_settings_1.php");
IncludeModuleLangFile(__FILE__);

$RIGHT_R = $USER->CanDoOperation('security_otp_settings_read');
$RIGHT_W = $USER->CanDoOperation('security_otp_settings_write');
if(!$RIGHT_R && !$RIGHT_W)
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$aTabs = array(
	array(
		"DIV" => "main",
		"TAB" => GetMessage("SEC_OTP_MAIN_TAB"),
		"ICON"=>"main_user_edit",
		"TITLE"=>GetMessage("SEC_OTP_MAIN_TAB_TITLE"),
	),
	array(
		"DIV" => "params",
		"TAB" => GetMessage("SEC_OTP_PARAMETERS_TAB"),
		"ICON"=>"main_user_edit",
		"TITLE"=>GetMessage("SEC_OTP_PARAMETERS_TAB_TITLE"),
	),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs, true, true);

$ID = intval($ID); // Id of the edited record
$strError = "";
$bVarsFromForm = false;
$bShowForce = false;

if($REQUEST_METHOD == "POST" && ($save!="" || $apply!="" || $otp_siteb!="") && $RIGHT_W && check_bitrix_sessid())
{

	if($otp_siteb!="")
		CSecurityUser::SetActive($_POST["otp_active"]==="Y");

	$hotp_user_window = intval($_POST["window_size"]);
	if($hotp_user_window <= 0)
		$hotp_user_window = 10;
	COption::SetOptionString("security", "hotp_user_window", $hotp_user_window);

	if($save!="" && $_GET["return_url"]!="")
		LocalRedirect($_GET["return_url"]);
	LocalRedirect("/bitrix/admin/security_otp.php?lang=".LANGUAGE_ID.($return_url? "&return_url=".urlencode($_GET["return_url"]): "")."&".$tabControl->ActiveTabParam());
}

$messageDetails = "";
if (CSecurityUser::IsActive())
{
	$messageType = "OK";
	$messageText = GetMessage("SEC_OTP_ON");
} else
{
	$messageType = "ERROR";
	$messageText = GetMessage("SEC_OTP_OFF");
}

$APPLICATION->SetTitle(GetMessage("SEC_OTP_TITLE"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

CAdminMessage::ShowMessage(array(
			"MESSAGE" => $messageText,
			"TYPE" => $messageType,
			"DETAILS" => $messageDetails,
			"HTML" => true
		));
?>

<form method="POST" action="security_otp.php?lang=<?echo LANGUAGE_ID?><?echo $_GET["return_url"]? "&amp;return_url=".urlencode($_GET["return_url"]): ""?>" enctype="multipart/form-data" name="editform">
<?
$tabControl->Begin();
?>
<?
$tabControl->BeginNextTab();
?>
<tr>
	<td colspan="2" align="left">
		<?if(CSecurityUser::IsActive()):?>
			<input type="hidden" name="otp_active" value="N">
			<input type="submit" name="otp_siteb" value="<?echo GetMessage("SEC_OTP_BUTTON_OFF")?>"<?if(!$RIGHT_W) echo " disabled"?>>
		<?else:?>
			<input type="hidden" name="otp_active" value="Y">
			<input type="submit" name="otp_siteb" value="<?echo GetMessage("SEC_OTP_BUTTON_ON")?>"<?if(!$RIGHT_W) echo " disabled"?> class="adm-btn-save">
		<?endif?>
	</td>
</tr>
<tr>
	<td colspan="2">
		<?echo BeginNote();?><?echo GetMessage("SEC_OTP_NOTE")?>
		<?echo EndNote(); ?>
	</td>
</tr>
<?if($APPLICATION->GetFileAccessPermission("/bitrix/otp/ws/index.php", array("G2")) <= "D"):?>
	<tr>
		<td colspan="2">
			<?echo BeginNote();?><?echo GetMessage("SEC_OTP_ACCESS_DENIED")?>
			<?echo EndNote(); ?>
		</td>
	</tr>
<?endif;?>
<?
$tabControl->BeginNextTab();
?>
<tr>
	<td width="40%"><?echo GetMessage("SEC_OTP_WINDOW_SIZE")?>:</td>
	<td width="60%"><input type="text" size="4" name="window_size" value="<?echo COption::GetOptionInt("security", "hotp_user_window");?>"></td>
</tr>
<?
$tabControl->Buttons(
	array(
		"disabled"=>(!$RIGHT_W),
		"back_url"=>$_GET["return_url"]? $_GET["return_url"]: "security_otp.php?lang=".LANG,
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