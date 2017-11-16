<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>

<?
?>
<tr>
	<td align="right" width="40%"><?= GetMessage("BPSHA_PD_NAME") ?>:<br /><small><?= GetMessage("BPSHA_PD_NAME_ALT") ?></small></td>
	<td width="60%">
		<input type="text" name="sh_name" id="id_sh_name" value="<?= htmlspecialcharsbx($arCurrentValues["sh_name"]) ?>" size="50">
		<input type="button" value="..." onclick="BPAShowSelector('id_sh_name', 'string');">
	</td>
</tr>
<tr>
	<td align="right" width="40%"><?= GetMessage("BPSHA_PD_USER") ?>:</td>
	<td width="60%">
		<input type="text" name="sh_user_id" id="id_sh_user_id" value="<?= htmlspecialcharsbx($arCurrentValues["sh_user_id"]) ?>" size="50">
		<input type="button" value="..." onclick="BPAShowSelector('id_sh_user_id', 'user');">
	</td>
</tr>