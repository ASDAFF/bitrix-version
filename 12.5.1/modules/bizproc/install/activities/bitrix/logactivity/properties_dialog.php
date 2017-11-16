<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<tr>
	<td align="right" width="40%"><span style="color:#FF0000;">*</span> <?= GetMessage("BPCAL_PD_TEXT") ?>:</td>
	<td width="60%">
		<textarea name="text" id="id_text" rows="3" cols="40"><?= htmlspecialcharsbx($arCurrentValues["text"]) ?></textarea>
		<input type="button" value="..." onclick="BPAShowSelector('id_text', 'string');">
	</td>
</tr>
<tr>
	<td align="right" width="40%"><?= GetMessage("BPCAL_PD_SET_VAR") ?>:</td>
	<td width="60%">
		<input type="checkbox" name="set_variable" value="Y"<?= ($arCurrentValues["set_variable"] == "Y") ? " checked" : "" ?>>
	</td>
</tr>