<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>

<tr>
	<td align="right" width="40%"><span style="color:#FF0000;">*</span> <?= GetMessage("BPSNMA_PD_FROM") ?>:</td>
	<td width="60%">
		<?
		global $USER;
		if ($USER->IsAdmin() || (CModule::IncludeModule("bitrix24") && CBitrix24::IsPortalAdmin($USER->GetID())))
		{
			?>
			<input type="text" name="message_user_from" id="id_message_user_from" value="<?= htmlspecialcharsbx($arCurrentValues["message_user_from"]) ?>" size="50">
			<input type="button" value="..." onclick="BPAShowSelector('id_message_user_from', 'user');">
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
	<td align="right" width="40%"><span style="color:#FF0000;">*</span> <?= GetMessage("BPSNMA_PD_TO") ?>:</td>
	<td width="60%">
		<input type="text" name="message_user_to" id="id_message_user_to" value="<?= htmlspecialcharsbx($arCurrentValues["message_user_to"]) ?>" size="50">
		<input type="button" value="..." onclick="BPAShowSelector('id_message_user_to', 'user');">
	</td>
</tr>
<tr>
	<td align="right" width="40%"><span style="color:#FF0000;">*</span> <?= GetMessage("BPSNMA_PD_MESSAGE") ?>:</td>
	<td width="60%">
		<textarea name="message_text" id="id_message_text" rows="7" cols="40"><?= htmlspecialcharsbx($arCurrentValues["message_text"]) ?></textarea>
		<input type="button" value="..." onclick="BPAShowSelector('id_message_text', 'string');">
	</td>
</tr>