<?
IncludeModuleLangFile(__FILE__);

if(!CModule::IncludeModule("security") || !$USER->CanDoOperation('security_edit_user_otp')):?>
	<tr>
		<td><?echo GetMessage("SEC_OTP_ACCESS_DENIED")?></td>
	</tr>
<?else:
	global $DB;
	ClearVars("str_security_");
	$ID = IntVal($ID);
	$str_security_SYNC1 = "";
	$str_security_SYNC2 = "";

	$dbKey = $DB->Query("SELECT * from b_sec_user WHERE USER_ID = ".$ID);
	if(!$dbKey->ExtractFields("str_security_", true))
	{
		if(!isset($str_security_ACTIVE) || ($str_security_ACTIVE !== "Y" && $str_security_ACTIVE !== "N"))
			$str_security_ACTIVE = "N";
		if(!isset($str_security_SECRET))
			$str_security_SECRET = "";
	}

	if(strlen($strError)>0)
	{
		$DB->InitTableVarsForEdit("b_sec_user", "security_", "str_security_");
	}
		?>
		<input type="hidden" name="profile_module_id[]" value="security">
		<tr>
			<td width="40%"><?echo GetMessage("SEC_OTP_SWITCH_ON")?>:</td>
			<td width="60%"><input type="checkbox" name="security_ACTIVE" value="Y" <?echo ($str_security_ACTIVE==="Y"? "checked": "")?> onclick="document.getElementById('security_SECRET').disabled = !this.checked;document.getElementById('security_SYNC1').disabled = !this.checked;document.getElementById('security_SYNC2').disabled = !this.checked;"></td>
		</tr>
		<tr>
			<td width="40%"><?echo GetMessage("SEC_OTP_SECRET_KEY")?>:</td>
			<td width="60%"><input type="text" id="security_SECRET" name="security_SECRET" size="64" maxlength="64" value="<?=$str_security_SECRET?>" <?echo ($str_security_ACTIVE==="Y"? "": "disabled")?>></td>
		</tr>
		<tr class="heading">
			<td colspan="2"><?echo GetMessage("SEC_OTP_INIT")?></td>
		</tr>
		<tr>
			<td><?echo GetMessage("SEC_OTP_PASS1")?>:</td>
			<td><input type="text" id="security_SYNC1" name="security_SYNC1" size="8" maxlength="8" value="<?=$str_security_SYNC?>" <?echo ($str_security_ACTIVE==="Y"? "": "disabled")?>></td>
		</tr>
		<tr>
			<td><?echo GetMessage("SEC_OTP_PASS2")?>:</td>
			<td><input type="text" id="security_SYNC2" name="security_SYNC2" size="8" maxlength="8" value="<?=$str_security_SYNC?>" <?echo ($str_security_ACTIVE==="Y"? "": "disabled")?>></td>
		</tr>
		<tr>
			<td align="center" colspan="2"><?echo BeginNote(), GetMessage("SEC_OTP_NOTE"), EndNote();?></td>
		</tr>
<?endif;?>