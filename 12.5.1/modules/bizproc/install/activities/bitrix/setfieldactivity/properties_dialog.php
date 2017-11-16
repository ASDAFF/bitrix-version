<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>

<?= $javascriptFunctions ?>
<script language="JavaScript">
function BWFVCChangeFieldType(ind, field, value)
{
	BX.showWait();

	objFields.GetFieldInputControl(
		objFields.arDocumentFields[field],
		value,
		{'Field':field, 'Form':'<?= $formName ?>'},
		function(v){
			if (v == undefined)
				document.getElementById('id_td_document_value_' + ind).innerHTML = "";
			else
				document.getElementById('id_td_document_value_' + ind).innerHTML = v;

			BX.closeWait();
		},
		true
	);
}

var bwfvc_counter = -1;
var bwfvc_newfield_counter = -1;
var currentType = null;

function BWFVCAddCondition(field, val)
{
	var addrowTable = document.getElementById('bwfvc_addrow_table');

	bwfvc_counter++;
	var newRow = addrowTable.insertRow(-1);
	newRow.id = "delete_row_" + bwfvc_counter;

	var newCell = newRow.insertCell(-1);
	var newSelect = document.createElement("select");
	newSelect.setAttribute('bwfvc_counter', bwfvc_counter);
	newSelect.onchange = function(){BWFVCChangeFieldType(this.getAttribute("bwfvc_counter"), this.options[this.selectedIndex].value, null)};
	newSelect.id = "id_document_field_" + bwfvc_counter;
	newSelect.name = "document_field_" + bwfvc_counter;

	var defFld = "";

	var i = -1;
	var i1 = -1;
	for (var key in objFields.arDocumentFields)
	{
		i++;
		newSelect.options[i] = new Option(objFields.arDocumentFields[key]['Name'], key);
		if (defFld.length <= 0 || field.length > 0 && key == field)
		{
			i1 = i;
			defFld = key;
		}
	}
	newSelect.selectedIndex = i1;

	newCell.appendChild(newSelect);

	var newCell = newRow.insertCell(-1);
	newCell.innerHTML = "=";

	var newCell = newRow.insertCell(-1);
	newCell.id = "id_td_document_value_" + bwfvc_counter;
	newCell.innerHTML = '<input type="text" id="id_' + field + '" name="' + field + '" value="' + val + '">';

	var newCell = newRow.insertCell(-1);
	newCell.align="right";
	newCell.innerHTML = '<a href="#" onclick="BWFVCDeleteCondition(' + bwfvc_counter + '); return false;"><?= GetMessage("BPSFA_PD_DELETE") ?></a>';

	BWFVCChangeFieldType(bwfvc_counter, defFld, val);
}

function BWFVCDeleteCondition(ind)
{
	var addrowTable = document.getElementById('bwfvc_addrow_table');

	var cnt = addrowTable.rows.length;
	for (i = 0; i < cnt; i++)
	{
		if (addrowTable.rows[i].id != 'delete_row_' + ind)
			continue;

		addrowTable.deleteRow(i);

		break;
	}
}

function BWFVCCreateField(b)
{
	var f = document.getElementById('sfa_pd_edit_form');
	var l = document.getElementById('sfa_pd_list_form');
	if (b)
	{
		<?=$popupWindow->jsPopup?>.btnSave.disable();
		<?=$popupWindow->jsPopup?>.btnCancel.disable();
	}
	else
	{
		<?=$popupWindow->jsPopup?>.btnSave.enable();
		<?=$popupWindow->jsPopup?>.btnCancel.enable();
	}
//	document.getElementById('btn_popup_save').disabled = b;
//	document.getElementById('btn_popup_cancel').disabled = b;
	if (b)
	{
		l.style.display = 'none';
		try{f.style.display = 'table-row';}
		catch(e){f.style.display = 'inline';}

		for (var t in objFields.arFieldTypes)
			break;

		window.currentType = {'Type' : t, 'Options' : null, 'Required' : 'N', 'Multiple' : 'N'};

		BWFVCCreateFieldChangeType(window.currentType);
	}
	else
	{
		f.style.display = 'none';
		try{l.style.display = 'table-row';}
		catch(e){l.style.display = 'inline';}
	}
}

function BWFVCToHiddens(ob, name)
{
	if (typeof ob == "object")
	{
		var s = "";
		for (var k in ob)
			s += BWFVCToHiddens(ob[k], name + "[" + k + "]");
		return s;
	}
	return '<input type="hidden" name="' + objFields.HtmlSpecialChars(name) + '" value="' + objFields.HtmlSpecialChars(ob) + '">';
}

function BWFVCCreateFieldSave()
{
	var fldName = document.getElementById("id_fld_name").value;
	var fldCode = document.getElementById("id_fld_code").value;
	fldCode = fldCode.replace(/\W+/g, '');
	var fldType = document.getElementById("id_fld_type").options[document.getElementById("id_fld_type").selectedIndex].value;
	var fldMultiple = (document.getElementById("id_fld_multiple").checked ? "Y" : "N");
	var fldRequired = (document.getElementById("id_fld_required").checked ? "Y" : "N");
	var fldOptions = window.currentType['Options'];

	if (fldName.replace(/^\s+|\s+$/g, '').length <= 0)
	{
		alert('<?= GetMessage("BPSFA_PD_EMPTY_NAME") ?>');
		document.getElementById("id_fld_name").focus();
		return;
	}
	if (fldCode.replace(/^\s+|\s+$/g, '').length <= 0)
	{
		alert('<?= GetMessage("BPSFA_PD_EMPTY_CODE") ?>');
		document.getElementById("id_fld_code").focus();
		return;
	}
	if (fldCode.match(/[^A-Za-z0-9\s._-]/g))
	{
		alert('<?= GetMessage("BPSFA_PD_WRONG_CODE") ?>');
		document.getElementById("id_fld_code").focus();
		return;
	}

	objFields.AddField(fldCode, fldName, fldType, fldMultiple, fldOptions);

	for (var i = 0; i <= bwfvc_counter; i++)
	{
		var o = document.getElementById("id_document_field_" + i);
		if (o)
			o.options[o.options.length] = new Option(fldName, fldCode);
	}

	document.getElementById("id_fld_name").value = "";
	document.getElementById("id_fld_code").value = "";
	document.getElementById("id_fld_type").selectedIndex = -1;
	document.getElementById("id_fld_multiple").checked = false;
	document.getElementById("id_fld_required").checked = false;
	document.getElementById("id_fld_options").value = "";

	bwfvc_newfield_counter++;
	var cont = document.getElementById("bwfvc_container");
	cont.innerHTML += "<input type='hidden' name='new_field_name[" + bwfvc_newfield_counter + "]' value='" + objFields.HtmlSpecialChars(fldName) + "'>";
	cont.innerHTML += "<input type='hidden' name='new_field_code[" + bwfvc_newfield_counter + "]' value='" + objFields.HtmlSpecialChars(fldCode) + "'>";
	cont.innerHTML += "<input type='hidden' name='new_field_type[" + bwfvc_newfield_counter + "]' value='" + objFields.HtmlSpecialChars(fldType) + "'>";
	cont.innerHTML += "<input type='hidden' name='new_field_mult[" + bwfvc_newfield_counter + "]' value='" + objFields.HtmlSpecialChars(fldMultiple) + "'>";
	cont.innerHTML += "<input type='hidden' name='new_field_req[" + bwfvc_newfield_counter + "]' value='" + objFields.HtmlSpecialChars(fldRequired) + "'>";
	cont.innerHTML += BWFVCToHiddens(fldOptions, 'new_field_options[' + bwfvc_newfield_counter + ']');

	BWFVCCreateField(false);

	BWFVCAddCondition(fldCode, "");
}

function BWFVCCreateFieldSwitchType(newType)
{
	window.currentType['Type'] = newType;
	BWFVCCreateFieldChangeType(window.currentType);
}

function BWFVCCreateFieldChangeType(type)
{
	if (objFields.arFieldTypes[type['Type']]['Complex'] == "Y")
	{
		BX.showWait();

		objFields.GetFieldInputControl4Type(
			type,
			null,
			{'Field':'fri_default', 'Form':'<?= $formName ?>'},
			"BWFVCSwitchSubTypeControl",
			function(v, newPromt)
			{
				if (v == undefined)
				{
					document.getElementById('id_tr_pbria_options').style.display = 'none';
				}
				else
				{
					document.getElementById('id_tr_pbria_options').style.display = '';
					document.getElementById('id_td_fri_options').innerHTML = v;
				}

				if (newPromt.length <= 0)
					newPromt = '<?= GetMessage("BPSFA_PD_F_MULT") ?>';
				document.getElementById('id_td_fri_options_promt').innerHTML = newPromt + ":";

				BX.closeWait();
			}
		);
	}
	else
	{
		document.getElementById('id_tr_pbria_options').style.display = 'none';
	}
}

function BWFVCSwitchSubTypeControl(v)
{
	window.currentType['Options'] = v;
}
</script>

<tr id="sfa_pd_list_form" style="display:block">
	<td colspan="2">
		<table width="100%" border="0" cellpadding="2" cellspacing="2" id="bwfvc_addrow_table">
		</table>
		<a href="#" onclick="BWFVCAddCondition('', ''); return false;"><?= GetMessage("BPSFA_PD_ADD") ?></a>
		<a href="#" onclick="BWFVCCreateField(true); return false;"><?= GetMessage("BPSFA_PD_CREATE") ?></a>
		<span id="bwfvc_container"></span>
	</td>
</tr>

<tr id="sfa_pd_edit_form" style="display:none">
	<td colspan="2">

	<table width="100%" border="0" cellpadding="2" cellspacing="2">
	<tr>
		<td align="center" colspan="2" style="align:center;"><b><?= GetMessage("BPSFA_PD_FIELD") ?></b></td>
	</tr>
	<tr>
		<td align="right" width="40%"><span style="color:#FF0000;">*</span> <?= GetMessage("BPSFA_PD_F_NAME") ?>:</td>
		<td width="60%">
			<input type="text" name="fld_name" id="id_fld_name" value="" />
		</td>
	</tr>
	<tr>
		<td align="right" width="40%"><span style="color:#FF0000;">*</span> <?= GetMessage("BPSFA_PD_F_CODE") ?>:</td>
		<td width="60%">
			<input type="text" name="fld_code" id="id_fld_code" value="" />
		</td>
	</tr>
	<tr>
		<td align="right" width="40%"><span style="color:#FF0000;">*</span> <?= GetMessage("BPSFA_PD_F_TYPE") ?>:</td>
		<td width="60%">
			<select name="fld_type" id="id_fld_type" onchange="BWFVCCreateFieldSwitchType(this.options[this.selectedIndex].value)">
				<?
				foreach ($arFieldTypes as $key => $value)
				{
					?><option value="<?= htmlspecialcharsbx($key) ?>"><?= htmlspecialcharsbx($value["Name"]) ?></option><?
				}
				?>
			</select>
			<span id="WFSAdditionalTypeInfo"></span>
		</td>
	</tr>
	<tr id="id_tr_pbria_options" style="display:none">
		<td align="right" width="40%" id="id_td_fri_options_promt"><?= GetMessage("BPSFA_PD_F_MULT") ?>:</td>
		<td width="60%" id="id_td_fri_options"></td>
	</tr>
	<tr>
		<td align="right" width="40%"><?= GetMessage("BPSFA_PD_F_MULT") ?>:</td>
		<td width="60%">
			<input type="checkbox" name="fld_multiple" id="id_fld_multiple" value="Y" />
		</td>
	</tr>
	<tr>
		<td align="right" width="40%"><?= GetMessage("BPSFA_PD_F_REQ") ?>:</td>
		<td width="60%">
			<input type="checkbox" name="fld_required" id="id_fld_required" value="Y" />
		</td>
	</tr>
	<tr id="id_tr_fld_options" style="display:none">
		<td align="right" width="40%"><?= GetMessage("BPSFA_PD_F_LIST") ?>:</td>
		<td width="60%">
			<textarea name="fld_options" id="id_fld_options" rows="3" cols="30"></textarea>
		</td>
	</tr>
	<tr>
		<td align="center" colspan="2">
			<input type="button" value="<?= GetMessage("BPSFA_PD_SAVE") ?>" onclick="BWFVCCreateFieldSave()" title="<?= GetMessage("BPSFA_PD_SAVE_HINT") ?>" />
			<input type="button" value="<?= GetMessage("BPSFA_PD_CANCEL") ?>" onclick="BWFVCCreateField(false);" title="<?= GetMessage("BPSFA_PD_CANCEL_HINT") ?>" />
		</td>
	</tr>
	</table>

	</td>
</tr>
<script>
BX.showWait();
<?
foreach ($arCurrentValues as $fieldKey => $documentFieldValue)
{
	if (!array_key_exists($fieldKey, $arDocumentFields))
		continue;
	?>
	BWFVCAddCondition('<?= CUtil::JSEscape($fieldKey) ?>', <?= CUtil::PhpToJSObject($documentFieldValue) ?>);
	<?
}

if (count($arCurrentValues) <= 0)
{
	?>BWFVCAddCondition("", "");<?
}
?>
BX.closeWait();

document.getElementById('sfa_pd_edit_form').style.display = 'none';
try{
	document.getElementById('sfa_pd_list_form').style.display = 'table-row';
}catch(e){
	document.getElementById('sfa_pd_list_form').style.display = 'inline';
}
</script>
