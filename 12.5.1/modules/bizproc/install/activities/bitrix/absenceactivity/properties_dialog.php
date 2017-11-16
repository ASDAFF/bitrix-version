<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>

<tr>
	<td align="right" width="40%"><span style="color:#FF0000;">*</span> <?= GetMessage("BPSNAA2_PD_CUSER") ?>:</td>
	<td width="60%">
		<input type="text" name="absence_user" id="id_absence_user" value="<?= htmlspecialcharsbx($arCurrentValues["absence_user"]) ?>" size="50">
		<input type="button" value="..." onclick="BPAShowSelector('id_absence_user', 'user');">
	</td>
</tr>
<tr>
	<td align="right" width="40%"><span style="color:#FF0000;">*</span> <?= GetMessage("BPSNAA2_PD_CNAME") ?>:</td>
	<td width="60%">
		<input type="text" name="absence_name" id="id_absence_name" value="<?= htmlspecialcharsbx($arCurrentValues["absence_name"]) ?>" size="50">
		<input type="button" value="..." onclick="BPAShowSelector('id_absence_name', 'string');">
	</td>
</tr>
<tr>
	<td align="right" width="40%"> <?= GetMessage("BPSNAA2_PD_CDESCR") ?>:</td>
	<td width="60%">
		<textarea name="absence_desrc" id="id_absence_desrc" rows="7" cols="40"><?= htmlspecialcharsbx($arCurrentValues["absence_desrc"]) ?></textarea>
		<input type="button" value="..." onclick="BPAShowSelector('id_absence_desrc', 'string');">
	</td>
</tr>
<tr>
	<td align="right" width="40%"><span style="color:#FF0000;">*</span> <?= GetMessage("BPSNAA2_PD_CFROM") ?>:</td>
	<td width="60%">
		<span style="white-space:nowrap;"><input type="text" name="absence_from" id="id_absence_from" size="30" value="<?= htmlspecialcharsbx($arCurrentValues["absence_from"]) ?>"><?= CAdminCalendar::Calendar("absence_from", "", "", true) ?></span>
		<input type="button" value="..." onclick="BPAShowSelector('id_absence_from', 'datetime');">
	</td>
</tr>
<tr>
	<td align="right" width="40%"><span style="color:#FF0000;">*</span> <?= GetMessage("BPSNAA2_PD_CTO") ?>:</td>
	<td width="60%">
		<span style="white-space:nowrap;"><input type="text" name="absence_to" id="id_absence_to" size="30" value="<?= htmlspecialcharsbx($arCurrentValues["absence_to"]) ?>"><?= CAdminCalendar::Calendar("absence_to", "", "", true) ?></span>
		<input type="button" value="..." onclick="BPAShowSelector('id_absence_to', 'datetime');">
	</td>
</tr>
<tr>
	<td align="right" width="40%"><span style="color:#FF0000;">*</span> <?= GetMessage("BPSNAA2_PD_CTYPES") ?>:</td>
	<td width="60%">
		<select name="absence_type" id="id_absence_type">
			<?
			foreach ($arAbsenceTypes as $key => $value)
			{
				?><option value="<?= $key ?>"<?= ($key == $arCurrentValues["absence_type"]) ? " selected" : "" ?>><?= $value ?></option><?
			}
			?>
		</select>
	</td>
</tr>
<tr>
	<td align="right" width="40%"><?= GetMessage("BPSNAA2_PD_CSTATE") ?>:</td>
	<td width="60%">
		<input type="text" name="absence_state" id="id_absence_state" value="<?= htmlspecialcharsbx($arCurrentValues["absence_state"]) ?>" size="50">
		<input type="button" value="..." onclick="BPAShowSelector('id_absence_state', 'string');">
	</td>
</tr>
<tr>
	<td align="right" width="40%"><?= GetMessage("BPSNAA2_PD_CFSTATE") ?>:</td>
	<td width="60%">
		<input type="text" name="absence_finish_state" id="id_absence_finish_state" value="<?= htmlspecialcharsbx($arCurrentValues["absence_finish_state"]) ?>" size="50">
		<input type="button" value="..." onclick="BPAShowSelector('id_absence_finish_state', 'string');">
	</td>
</tr>
