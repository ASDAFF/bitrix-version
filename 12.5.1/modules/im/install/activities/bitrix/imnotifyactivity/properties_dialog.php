<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>

<tr>
	<td align="right" width="40%"><span style="color:#FF0000;">*</span> <?= GetMessage("BPIMNA_PD_FROM") ?>:</td>
	<td width="60%">
		<?
		global $USER;
		if ($USER->IsAdmin() || (CModule::IncludeModule("bitrix24") && CBitrix24::IsPortalAdmin($USER->GetID())))
		{
			?>
			<input type="text" name="to_user_id" id="id_to_user_id" value="<?= htmlspecialcharsbx($arCurrentValues["to_user_id"]) ?>" size="50">
			<input type="button" value="..." onclick="BPAShowSelector('id_to_user_id', 'user');">
			<?
		}
		else
		{
			echo $USER->GetFullName();
		}
		?>
	</td>
</tr>
<tr>
	<td align="right" width="40%"><span style="color:#FF0000;">*</span> <?= GetMessage("BPIMNA_PD_TO") ?>:</td>
	<td width="60%">
		<input type="text" name="from_user_id" id="id_from_user_id" value="<?= htmlspecialcharsbx($arCurrentValues["from_user_id"]) ?>" size="50">
		<input type="button" value="..." onclick="BPAShowSelector('id_from_user_id', 'user');">
	</td>
</tr>
<tr>
	<td align="right" width="40%"><span style="color:#FF0000;">*</span> <?= GetMessage("BPIMNA_PD_MESSAGE") ?>:</td>
	<td width="60%">
		<textarea name="message_site" id="id_message_site" rows="4" cols="40"><?= htmlspecialcharsbx($arCurrentValues["message_site"]) ?></textarea>
		<input type="button" value="..." onclick="BPAShowSelector('id_message_site', 'string');"><br/>
		<?= GetMessage("BPIMNA_PD_MESSAGE_BBCODE") ?>
	</td>
</tr>
<tr>
	<td align="right" width="40%"><span style="color:#FF0000;">*</span> <?= GetMessage("BPIMNA_PD_MESSAGE_OUT") ?>:</td>
	<td width="60%">
		<textarea name="message_out" id="id_message_out" rows="4" cols="40"><?= htmlspecialcharsbx($arCurrentValues["message_out"]) ?></textarea>
		<input type="button" value="..." onclick="BPAShowSelector('id_message_out', 'string');"><br/>
		<?= GetMessage("BPIMNA_PD_MESSAGE_OUT_EMPTY") ?>
	</td>
</tr>
<tr>
	<td align="right" width="40%"><span style="color:#FF0000;">*</span> <?= GetMessage("BPIMNA_PD_NOTIFY_TYPE") ?>:</td>
	<td width="60%">
		<?=InputType("radio", "message_type", "2", $arCurrentValues["message_type"], false, "&nbsp;".GetMessage("BPIMNA_PD_NOTIFY_TYPE_FROM"))?><br/>
		<?=InputType("radio", "message_type", "4", $arCurrentValues["message_type"], false, "&nbsp;".GetMessage("BPIMNA_PD_NOTIFY_TYPE_SYSTEM"))?>
	</td>
</tr>