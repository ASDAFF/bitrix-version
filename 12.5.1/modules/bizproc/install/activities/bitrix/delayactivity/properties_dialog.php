<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<tr>
	<td align="right" width="40%"><?= GetMessage("CPAD_DP_TIME_SELECT") ?>:</td>
	<td width="60%">
		<input type="radio" name="time_type_selector" value="delay" id="time_type_selector_delay" onclick="SetDelayMode(true)"><label for="time_type_selector_delay"><?= GetMessage("CPAD_DP_TIME_SELECT_DELAY") ?></label><br />
		<input type="radio" name="time_type_selector" value="time" id="time_type_selector_time" onclick="SetDelayMode(false)"><label for="time_type_selector_time"><?= GetMessage("CPAD_DP_TIME_SELECT_TIME") ?></label>
		<script type="text/javascript">
			function SetDelayMode(val)
			{
				var f1 = document.getElementById('tr_time_type_selector_delay');
				var f2 = document.getElementById('tr_time_type_selector_time');

				if (val)
				{
					f2.style.display = 'none';
					try{
						f1.style.display = 'table-row';
					}catch(e){
						f1.style.display = 'inline';
					}
					document.getElementById('time_type_selector_delay').checked = true;
				}
				else
				{
					f1.style.display = 'none';
					try{
						f2.style.display = 'table-row';
					}catch(e){
						f2.style.display = 'inline';
					}
					document.getElementById('time_type_selector_time').checked = true;
				}
			}
		</script>
	</td>
</tr>
<tr id="tr_time_type_selector_delay">
	<td align="right" width="40%"><?= GetMessage("CPAD_DP_TIME") ?>:</td>
	<td width="60%">
		<input type="text" name="delay_time" id="id_delay_time" value="<?= htmlspecialcharsbx($arCurrentValues["delay_time"]) ?>" size="20" />
		<input type="button" value="..." onclick="BPAShowSelector('id_delay_time', 'int');" />
		<select name="delay_type">
			<option value="s"<?= ($arCurrentValues["delay_type"] == "s") ? " selected" : "" ?>><?= GetMessage("CPAD_DP_TIME_S") ?></option>
			<option value="m"<?= ($arCurrentValues["delay_type"] == "m") ? " selected" : "" ?>><?= GetMessage("CPAD_DP_TIME_M") ?></option>
			<option value="h"<?= ($arCurrentValues["delay_type"] == "h") ? " selected" : "" ?>><?= GetMessage("CPAD_DP_TIME_H") ?></option>
			<option value="d"<?= ($arCurrentValues["delay_type"] == "d") ? " selected" : "" ?>><?= GetMessage("CPAD_DP_TIME_D") ?></option>
		</select>
	</td>
</tr>
<tr id="tr_time_type_selector_time">
	<td align="right" width="40%"><?= GetMessage("CPAD_DP_TIME1") ?>:</td>
	<td width="60%">
		<?
		$v = "";
		$v_x = trim($arCurrentValues["delay_date"]);
		if (!preg_match("#^\{=[a-z0-9_]+:[a-z0-9_]+\}$#i", $v_x) && (substr($v_x, 0, 1) != "="))
		{
			$v = $v_x;
			$v_x = "";
		}

		require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/init_admin.php");
		echo CAdminCalendar::CalendarDate("delay_date", $v, 19, true);
		?>
		<input type="text" name="delay_date_x" id="id_delay_date_x" value="<?= htmlspecialcharsbx($v_x) ?>" size="20" />
		<input type="button" value="..." onclick="BPAShowSelector('id_delay_date_x', 'datetime');" />
	</td>
</tr>
<script type="text/javascript">
	SetDelayMode(<?= (!array_key_exists("delay_date", $arCurrentValues)) ? "true" : "false" ?>);
</script>