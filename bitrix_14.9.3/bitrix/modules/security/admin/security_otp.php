<?
define("ADMIN_MODULE_NAME", "security");

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

CModule::IncludeModule('security');
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/security/options_user_settings_1.php");
IncludeModuleLangFile(__FILE__);

/**
 * @global CUser $USER
 * @global CMain $APPLICATION
 **/

$canRead = $USER->CanDoOperation('security_otp_settings_read');
$canWrite = $USER->CanDoOperation('security_otp_settings_write');
if(!$canRead && !$canWrite)
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
$returnUrl = $_GET["return_url"]? "&return_url=".urlencode($_GET["return_url"]): "";

if($_SERVER["REQUEST_METHOD"] == "POST" && $_REQUEST["save"].$_REQUEST["apply"].$_REQUEST["otp_siteb"] !="" && $canWrite && check_bitrix_sessid())
{

	if($_REQUEST["otp_siteb"] != "")
		CSecurityUser::setActive($_POST["otp_active"]==="Y");

	$hotp_user_window = intval($_POST["window_size"]);
	if($hotp_user_window <= 0)
		$hotp_user_window = 10;
	COption::SetOptionString("security", "hotp_user_window", $hotp_user_window);

	if($_REQUEST["save"] != "" && $_GET["return_url"] != "")
		LocalRedirect($_GET["return_url"]);
	else
		LocalRedirect("/bitrix/admin/security_otp.php?lang=".LANGUAGE_ID.$returnUrl."&".$tabControl->ActiveTabParam());
}

$APPLICATION->SetTitle(GetMessage("SEC_OTP_TITLE"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

if (CSecurityUser::isActive())
{
	$messageType = "OK";
	$messageText = GetMessage("SEC_OTP_ON");
}
else
{
	$messageType = "ERROR";
	$messageText = GetMessage("SEC_OTP_OFF");
}
CAdminMessage::ShowMessage(array(
			"MESSAGE" => $messageText,
			"TYPE" => $messageType,
			"HTML" => true
		));
?>

<form method="POST" action="security_otp.php?lang=<?=LANGUAGE_ID?><?=htmlspecialcharsbx($returnUrl)?>" enctype="multipart/form-data" name="editform">
<?
$tabControl->Begin();
?>
<?
$tabControl->BeginNextTab();
?>
<tr>
	<td colspan="2" align="left">
		<?if(CSecurityUser::isActive()):?>
			<input type="hidden" name="otp_active" value="N">
			<input type="submit" name="otp_siteb" value="<?echo GetMessage("SEC_OTP_BUTTON_OFF")?>"<?if(!$canWrite) echo " disabled"?>>
		<?else:?>
			<input type="hidden" name="otp_active" value="Y">
			<input type="submit" name="otp_siteb" value="<?echo GetMessage("SEC_OTP_BUTTON_ON")?>"<?if(!$canWrite) echo " disabled"?> class="adm-btn-save">
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
		"disabled"=>(!$canWrite),
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