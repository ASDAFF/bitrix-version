<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<tr>
	<td align="right" width="40%"><span style="color:#FF0000;">*</span> <?= GetMessage("BPCA_PD_PHP") ?>:</td>
	<td width="60%">
		<textarea name="execute_code" id="id_execute_code" rows="10" cols="70"><?= htmlspecialcharsbx($arCurrentValues["execute_code"]) ?></textarea>
		<input type="button" value="..." onclick="BPAShowSelector('id_execute_code', 'string');">
	</td>
</tr>