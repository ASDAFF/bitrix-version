/*
 * arParams
 * 		PREFIX				- prefix for vars
 * 		FORM_ID				- id form
 * 		TABLE_PROP_ID		- id table with properties
 * 		PROP_COUNT_ID		- id field with count properties
 * 		IBLOCK_ID			- id iblock
 *		LANG				- lang id
 *		TITLE				- window title
 *		OBJ					- object var name
 * Variables
 * 		this.PREFIX
 * 		this.PREFIX_TR
 * 		this.FORM_ID
 * 		this.FORM_DATA
 * 		this.TABLE_PROP_ID
 * 		this.PROP_TBL
 * 		this.PROP_COUNT_ID
 * 		this.PROP_COUNT
 * 		this.PROP_COUNT_VALUE
 * 		this.IBLOCK_ID
 * 		this.LANG
 * 		this.TITLE
 * 		this.CELLS
 * 		this.CELL_IND
 * 		this.CELL_CENT
 * 		this.OBJNAME
 */
function JCIBlockProperty(arParams)
{
	var _this = this;

	if (!arParams) return;

	this.intERROR = 0;
	this.PREFIX = arParams.PREFIX;
	this.PREFIX_TR = this.PREFIX+'ROW_';
	this.FORM_ID = arParams.FORM_ID;
	this.TABLE_PROP_ID = arParams.TABLE_PROP_ID;
	this.PROP_COUNT_ID = arParams.PROP_COUNT_ID;
	this.IBLOCK_ID = arParams.IBLOCK_ID;
	this.LANG = arParams.LANG;
	this.TITLE = arParams.TITLE;
	this.CELLS = [];
	this.CELL_IND = -1;
	this.CELL_CENT = [];
	this.OBJNAME = arParams.OBJ;

	BX.ready(BX.delegate(this.Init,this));
};

JCIBlockProperty.prototype.Init = function()
{
	this.FORM_DATA = BX(this.FORM_ID);
	if (!this.FORM_DATA)
	{
		this.intERROR = -1;
		return;
	}
	this.PROP_TBL = BX(this.TABLE_PROP_ID);
	if (!this.PROP_TBL)
	{
		this.intERROR = -1;
		return;
	}
	this.PROP_COUNT = BX(this.PROP_COUNT_ID);
	if (!this.PROP_COUNT)
	{
		this.intERROR = -1;
		return;
	}
	var clButtons = BX.findChildren(this.PROP_TBL, {'tag': 'input','attribute': { 'type':'button'}}, true);
	if (clButtons)
	{
		for (var i = 0; i < clButtons.length; i++)
			BX.bind(clButtons[i], 'click', BX.proxy(function(e){this.ShowPropertyDialog(e);}, this));
	}

	BX.addCustomEvent(this.FORM_DATA, 'onAutoSaveRestore', BX.delegate(this.onAutoSaveRestore, this));
};

JCIBlockProperty.prototype.GetPropInfo = function(ID)
{
	if (0 > this.intERROR)
		return;

	ID = this.PREFIX + ID;

	arResult = {
		'PROPERTY_TYPE' : this.FORM_DATA[ID+'_PROPERTY_TYPE'].value,
		'NAME' : this.FORM_DATA[ID+'_NAME'].value,
		'ACTIVE' : (true == this.FORM_DATA[ID+'_ACTIVE_Y'].checked ? this.FORM_DATA[ID+'_ACTIVE_Y'].value : this.FORM_DATA[ID+'_ACTIVE_N'].value),
		'MULTIPLE' : (true == this.FORM_DATA[ID+'_MULTIPLE_Y'].checked ? this.FORM_DATA[ID+'_MULTIPLE_Y'].value : this.FORM_DATA[ID+'_MULTIPLE_N'].value),
		'IS_REQUIRED' : (true == this.FORM_DATA[ID+'_IS_REQUIRED_Y'].checked ? this.FORM_DATA[ID+'_IS_REQUIRED_Y'].value : this.FORM_DATA[ID+'_IS_REQUIRED_N'].value),
		'SORT' : this.FORM_DATA[ID+'_SORT'].value,
		'CODE' : this.FORM_DATA[ID+'_CODE'].value,
		'PROPINFO': this.FORM_DATA[ID+'_PROPINFO'].value
	};
	return arResult;
};

JCIBlockProperty.prototype.SetPropInfo = function(ID,arProp,formsess)
{
	if (0 > this.intERROR)
		return;

	if (!formsess)
		return;
	if (BX.bitrix_sessid() != formsess)
		return;

	ID = this.PREFIX+ID;

	this.FORM_DATA[ID+'_NAME'].value = arProp.NAME;
	this.FORM_DATA[ID+'_SORT'].value = arProp.SORT;
	this.FORM_DATA[ID+'_CODE'].value = arProp.CODE;
	var PropActive = BX(ID+'_ACTIVE_Y');
	PropActive.checked = ('Y' == arProp.ACTIVE ? true : false);
	var PropMulti = BX(ID+'_MULTIPLE_Y');
	PropMulti.checked = ('Y' == arProp.MULTIPLE ? true : false);
	var PropReq = BX(ID+'_IS_REQUIRED_Y');
	PropReq.checked = ('Y' == arProp.IS_REQUIRED ? true : false);
	this.FORM_DATA[ID+'_PROPINFO'].value = arProp.PROPINFO;
	for (i = 0; i < this.FORM_DATA[ID+'_PROPERTY_TYPE'].length; i++)
		if (arProp.PROPERTY_TYPE == this.FORM_DATA[ID+'_PROPERTY_TYPE'].options[i].value)
			this.FORM_DATA[ID+'_PROPERTY_TYPE'].options[i].selected = true;

	BX.fireEvent(this.FORM_DATA[ID+'_NAME'], 'change');
};

JCIBlockProperty.prototype.GetProperty = function(strName)
{
	if (0 > this.intERROR)
		return;

	if ((!strName) || (!this[strName])) return;
	return this[strName];
};

JCIBlockProperty.prototype.SetProperty = function(strName,value)
{
	if (0 > this.intERROR)
		return;

	if (strName)
		this[strName] = value;
};

JCIBlockProperty.prototype.ShowPropertyDialog = function (e)
{
	if(!e)
		e = window.event;
	if (0 > this.intERROR)
		return;
	var s = (BX.browser.IsIE() ? e.srcElement.id : e.target.id);

	if (!s)
		return;

	s = s.replace(this.PREFIX,'');
	s = s.replace('_BTN','');
	var ID = s;

	var arResult = {
		'PARAMS': {
			'PREFIX': this.PREFIX,
			'ID': ID,
			'IBLOCK_ID': this.IBLOCK_ID,
			'TITLE': this.TITLE,
			'RECEIVER': this.OBJNAME
		},
		'PROP': this.GetPropInfo(ID),
		'sessid': BX.bitrix_sessid()
	};
	(new BX.CAdminDialog({
		'title': this.TITLE,
	    'content_url': '/bitrix/admin/iblock_edit_property.php?lang='+this.LANG+'&propedit='+ID+'&bxpublic=Y&receiver='+this.OBJNAME,
	    'content_post': arResult,
		'draggable': true,
		'resizable': true,
		'buttons': [BX.CAdminDialog.btnSave, BX.CAdminDialog.btnCancel]
	})).Show();
};

JCIBlockProperty.prototype.SetCells = function(arCells,intIndex,arCenter)
{
	if (0 > this.intERROR)
		return;

	if (arCells)
		this.CELLS = BX.clone(arCells,true);
	for (var i = 0; i < this.CELLS.length; i++)
	{
		this.CELLS[i] = this.CELLS[i].replace(/PREFIX/ig, this.PREFIX);
	}
	if (intIndex)
		this.CELL_IND = intIndex;
	if (arCenter)
		this.CELL_CENT = BX.clone(arCenter,true);
};

JCIBlockProperty.prototype.addPropRow = function()
{
	if (0 > this.intERROR)
		return;
	var i = 0;
	var id = parseInt(this.PROP_COUNT.value);

	var newRow = this.PROP_TBL.insertRow(this.PROP_TBL.rows.length);
	newRow.id = this.PREFIX_TR+'n'+id;
	for (i = 0; i < this.CELLS.length; i++)
	{
		var oCell = newRow.insertCell(-1);
		var typeHtml = this.CELLS[i];
		typeHtml = typeHtml.replace(/tmp_xxx/ig, 'n'+id);
		oCell.innerHTML = typeHtml;
	}
	for (i = 0; i < this.CELL_CENT.length; i++)
	{
		var needCell = newRow.cells[this.CELL_CENT[i]-1];
		if (!!needCell)
		{
			BX.adjust(needCell, { style: {'textAlign': 'center', 'verticalAlign' : 'middle'} });
		}
	}
	var needCell = newRow.cells[0];
	if (!!needCell)
	{
		BX.adjust(needCell, { style: {'verticalAlign' : 'middle'} });
	}

	if (newRow.cells[this.CELL_IND])
	{
		var needCell = newRow.cells[this.CELL_IND];
		var clButtons = BX.findChildren(needCell, {'tag': 'input','attribute': { 'type':'button'}}, true);
		if (!!clButtons)
		{
			for (var i = 0; i < clButtons.length; i++)
				BX.bind(clButtons[i], 'click', BX.proxy(function(e){this.ShowPropertyDialog(e);}, this));
		}
	}

	BX.adminFormTools.modifyFormElements(this.FORM_ID);

	setTimeout(function() {
		var r = BX.findChildren(newRow.parentNode, {tag: /^(input|select|textarea)$/i}, true);
		if (r && r.length > 0)
		{
			for (var i=0,l=r.length;i<l;i++)
			{
				if (r[i].form && r[i].form.BXAUTOSAVE)
					r[i].form.BXAUTOSAVE.RegisterInput(r[i]);
				else
					break;
			}
		}
	}, 10);

	this.PROP_COUNT.value = id + 1;
};

JCIBlockProperty.prototype.onAutoSaveRestore = function(ob, data)
{
	while (data['IB_PROPERTY_n' + this.PROP_COUNT.value + '_NAME'])
	{
		this.addPropRow();
	}
};

function JCIBlockAccess(entity_type, iblock_id, id, arSelected, variable_name, table_id, href_id, sSelect, arHighLight)
{
	this.entity_type = entity_type;
	this.iblock_id = iblock_id;
	this.id = id;
	this.arSelected = arSelected;
	this.variable_name = variable_name;
	this.table_id = table_id;
	this.href_id = href_id;
	this.sSelect = sSelect;
	this.arHighLight = arHighLight;

	BX.ready(BX.delegate(this.Init, this));
}

JCIBlockAccess.prototype.Init = function()
{
	BX.bind(BX(this.href_id), 'click', BX.delegate(this.Add, this));
	var heading = BX(this.variable_name + '_heading');
	if(heading)
		BX.bind(heading, 'dblclick', BX.delegate(this.ShowInfo, this));
	BX.Access.Init(this.arHighLight);
	BX.Access.SetSelected(this.arSelected, this.variable_name);
}

JCIBlockAccess.prototype.Add = function()
{
	BX.Access.ShowForm({callback: BX.delegate(this.InsertRights, this), bind: this.variable_name})
}

JCIBlockAccess.prototype.InsertRights = function(obSelected)
{
	var tbl = BX(this.table_id);
	for(var provider in obSelected)
	{
		for(var id in obSelected[provider])
		{
			var cnt = tbl.rows.length;
			var row = tbl.insertRow(cnt-1);
			row.vAlign = 'top';
			row.insertCell(-1);
			row.insertCell(-1);
			row.cells[0].align = 'right';
			row.cells[0].style.textAlign = 'right';
			row.cells[0].style.verticalAlign = 'middle';
			row.cells[0].innerHTML = BX.Access.GetProviderName(provider)+' '+obSelected[provider][id].name+':'+'<input type="hidden" name="'+this.variable_name+'[][RIGHT_ID]" value=""><input type="hidden" name="'+this.variable_name+'[][GROUP_CODE]" value="'+id+'">';
			row.cells[1].align = 'left';
			row.cells[1].innerHTML = this.sSelect + ' ' + '<a href="javascript:void(0);" onclick="JCIBlockAccess.DeleteRow(this, \''+id+'\', \''+this.variable_name+'\')" class="access-delete"></a><span title="'+BX.message('langApplyTitle')+'" id="overwrite_'+id+'"></span>';

			var parents = BX.findChildren(tbl, {'class' : this.variable_name + '_row_for_' + id}, true);
			if(parents)
			for(var i = 0; i < parents.length; i++)
				parents[i].className += ' iblock-strike-out';
		}
	}

	if(parseInt(this.id) > 0)
	{
		BX.ajax.loadJSON(
			'/bitrix/admin/iblock_edit.php'+
			'?ajax=y'+
			'&sessid='+BX.bitrix_sessid()+
			'&entity_type='+this.entity_type+
			'&iblock_id='+this.iblock_id+
			'&id='+this.id,
			{added: obSelected},
			function(result)
			{
				if(result)
				{
					for(var id in result)
					{
						var s = parseInt(result[id][0]);
						var e = parseInt(result[id][1]);
						var mess = '';
						if(s > 0 && e > 0)
							mess = BX.message('langApply1Title');
						else if (s > 0)
							mess = BX.message('langApply2Title');
						else if (e > 0)
							mess = BX.message('langApply3Title');

						if(mess)
							BX('overwrite_'+id).innerHTML = '<br><input type="checkbox" name="'+this.variable_name+'[][DO_CLEAN]" value="Y">'+mess+' ('+(s+e)+')';
					}
				}
			}
		);
	}

	BX.onCustomEvent('onAdminTabsChange');
}

JCIBlockAccess.prototype.ShowInfo = function()
{
	var entity_type = this.entity_type;
	var iblock_id = this.iblock_id;
	var id = this.id;

	var btnOK = new BX.CWindowButton({
		'title': 'Query',
		'action': function()
		{
			var _user_id = BX('prompt_user_id');
			BX('info_result').innerHTML = '';
			BX.showWait();
			BX.ajax.loadJSON(
				'/bitrix/admin/iblock_edit.php'+
				'?ajax=y'+
				'&sessid='+BX.bitrix_sessid()+
				'&entity_type='+entity_type+
				'&iblock_id='+iblock_id+
				'&id='+id,
				{info: _user_id.value},
				function(result)
				{
					if(result)
					{
						for(var id in result)
						{
							BX('info_result').innerHTML += '<span style="display:inline-block;width:200px;height:15px;">' + id + '</span>';
						}
					}
					BX.closeWait();
				}
			);
		}
	})

	if (null == this.iblock_info_obDialog)
	{
		this.iblock_info_obDialog = new BX.CDialog({
			content: '<table cellspacing="0" cellpadding="0" border="0" width="100%"><tr><td width="50%" align="right">User ID:</td><td width="50%" align="left"><input type="text" size="6" id="prompt_user_id" value=""></td></tr><tr><td colspan="2" id="info_result"></td></tr></table>',
			buttons: [btnOK, BX.CDialog.btnCancel],
			width: 420,
			height: 200
		});
	}

	this.iblock_info_obDialog.Show();

	var inp = BX('prompt_user_id');
	inp.focus();
	inp.select();
}

JCIBlockAccess.DeleteRow = function(ob, id, variable_name)
{
	var row = BX.findParent(ob, {'tag':'tr'});
	var tbl = BX.findParent(row, {'tag':'table'});
	var parents = BX.findChildren(tbl, {'class' : variable_name + '_row_for_' + id + ' iblock-strike-out'}, true);
	if(parents)
	for(var i = 0; i < parents.length; i++)
		parents[i].className = variable_name + '_row_for_' + id;
	row.parentNode.removeChild(row);
	BX.onCustomEvent('onAdminTabsChange');
	BX.Access.DeleteSelected(id, variable_name);
}

function addNewRow(tableID, row_to_clone)
{
	var tbl = document.getElementById(tableID);
	var cnt = tbl.rows.length;
	if(row_to_clone == null)
		row_to_clone = -2;
	var sHTML = tbl.rows[cnt+row_to_clone].cells[0].innerHTML;
	var oRow = tbl.insertRow(cnt+row_to_clone+1);
	var oCell = oRow.insertCell(0);

	var p = 0;
	while(true)
	{
		var s = sHTML.indexOf('[n',p);
		if(s<0)break;
		var e = sHTML.indexOf(']',s);
		if(e<0)break;
		var n = parseInt(sHTML.substr(s+2,e-s));
		sHTML = sHTML.substr(0, s)+'[n'+(++n)+']'+sHTML.substr(e+1);
		p=s+1;
	}
	var p = 0;
	while(true)
	{
		var s = sHTML.indexOf('__n',p);
		if(s<0)break;
		var e = sHTML.indexOf('_',s+2);
		if(e<0)break;
		var n = parseInt(sHTML.substr(s+3,e-s));
		sHTML = sHTML.substr(0, s)+'__n'+(++n)+'_'+sHTML.substr(e+1);
		p=e+1;
	}
	var p = 0;
	while(true)
	{
		var s = sHTML.indexOf('__N',p);
		if(s<0)break;
		var e = sHTML.indexOf('__',s+2);
		if(e<0)break;
		var n = parseInt(sHTML.substr(s+3,e-s));
		sHTML = sHTML.substr(0, s)+'__N'+(++n)+'__'+sHTML.substr(e+2);
		p=e+2;
	}
	var p = 0;
	while(true)
	{
		var s = sHTML.indexOf('xxn',p);
		if(s<0)break;
		var e = sHTML.indexOf('xx',s+2);
		if(e<0)break;
		var n = parseInt(sHTML.substr(s+3,e-s));
		sHTML = sHTML.substr(0, s)+'xxn'+(++n)+'xx'+sHTML.substr(e+2);
		p=e+2;
	}
	var p = 0;
	while(true)
	{
		var s = sHTML.indexOf('%5Bn',p);
		if(s<0)break;
		var e = sHTML.indexOf('%5D',s+3);
		if(e<0)break;
		var n = parseInt(sHTML.substr(s+4,e-s));
		sHTML = sHTML.substr(0, s)+'%5Bn'+(++n)+'%5D'+sHTML.substr(e+3);
		p=e+3;
	}
	oCell.innerHTML = sHTML;

	var patt = new RegExp ("<"+"script"+">[^\000]*?<"+"\/"+"script"+">", "ig");
	var code = sHTML.match(patt);
	if(code)
	{
		for(var i = 0; i < code.length; i++)
		{
			if(code[i] != '')
			{
				var s = code[i].substring(8, code[i].length-9);
				jsUtils.EvalGlobal(s);
			}
		}
	}

	BX.adminPanel.modifyFormElements(oRow);
	BX.onCustomEvent('onAdminTabsChange');

	setTimeout(function() {
		var r = BX.findChildren(oCell, {tag: /^(input|select|textarea)$/i});
		if (r && r.length > 0)
		{
			for (var i=0,l=r.length;i<l;i++)
			{
				if (r[i].form && r[i].form.BXAUTOSAVE)
					r[i].form.BXAUTOSAVE.RegisterInput(r[i]);
				else
					break;
			}
		}
	}, 10);
}

function JCIBlockGroupField(form, groupSection_id, ajaxURL)
{
	this.form = form;
	this.groupSection = BX(groupSection_id);
	this.ajaxURL = ajaxURL;
}

JCIBlockGroupField.prototype.reload = function()
{
	var post_data = this.preparePost();
}

JCIBlockGroupField.prototype.preparePost = function()
{
	var values = new Array;
	values[values.length] = {name : 'ajax_action', value : 'section_property'};
	this.gatherInputsValues(values, document.getElementsByName('IBLOCK_SECTION[]'));

	var toReload = BX.findChildren(this.form, {'tag' : 'tr', 'class' : 'bx-in-group'}, true);
	if(toReload)
		for(var i = 0; i < toReload.length; i++)
			this.gatherInputsValues(values, BX.findChildren(toReload[i], null, true));

	var formHiddens = BX.findChildren(this.form, {'tag' : 'span', 'class' : 'bx-fields-hidden'}, true);
	if(formHiddens)
		for(var i = 0; i < formHiddens.length; i++)
			this.gatherInputsValues(values, BX.findChildren(formHiddens[i], null, true));

	BX.ajax.post(
		this.ajaxURL,
		this.values2post(values),
		BX.delegate(this.postHandler, this)
	);
}

JCIBlockGroupField.prototype.postHandler = function (result)
{
	if(this.form)
	{
		var toDelete = BX.findChildren(this.form, {'tag' : 'tr', 'class' : 'bx-in-group'}, true);
		if(toDelete)
		{
			for(var i = 0; i < toDelete.length; i++)
				this.groupSection.parentNode.removeChild(toDelete[i]);
		}

		var responseDOM = document.createElement('DIV');
		responseDOM.innerHTML = result;

		var toInsert = BX.findChildren(responseDOM, {'tag' : 'tr', 'class' : 'bx-in-group'}, true);
		if(toInsert)
		{
			var sibling = this.groupSection.nextSibling;
			for(var i = 0; i < toInsert.length; i++)
			{
				var toMove = toInsert[i];
				toMove.parentNode.removeChild(toMove);
				this.groupSection.parentNode.insertBefore(toMove, sibling);
			}
		}

		var formHiddens;
		formHiddens = BX.findChildren(this.form, {'tag' : 'span', 'class' : 'bx-fields-hidden'}, true);
		if(formHiddens)
			for(var i = 0; i < formHiddens.length; i++)
				formHiddens[i].parentNode.removeChild(formHiddens[i]);

		formHiddens = BX.findChildren(responseDOM, {'tag' : 'span', 'class' : 'bx-fields-hidden'}, true);
		if(formHiddens)
		{
			for(var i = 0; i < formHiddens.length; i++)
			{
				var span = formHiddens[i];
				span.parentNode.removeChild(span);
				this.form.appendChild(span);
			}
		}

		BX.onCustomEvent('onAdminTabsChange');
		BX.adminPanel.modifyFormElements(this.form);
//document.removeChild(responseDOM);
	}
}

JCIBlockGroupField.prototype.gatherInputsValues = function (values, elements)
{
	if(elements)
	{
		for(var i = 0; i < elements.length; i++)
		{
			var el = elements[i];
			if (el.disabled || !el.type)
				continue;

			switch(el.type.toLowerCase())
			{
				case 'text':
				case 'textarea':
				case 'password':
				case 'hidden':
				case 'select-one':
					values[values.length] = {name : el.name, value : el.value};
					break;
				case 'radio':
				case 'checkbox':
					if(el.checked)
						values[values.length] = {name : el.name, value : el.value};
					break;
				case 'select-multiple':
					for (var j = 0; j < el.options.length; j++)
					{
						if (el.options[j].selected)
							values[values.length] = {name : el.name, value : el.options[j].value};
					}
					break;
				default:
					break;
			}
		}
	}
}

JCIBlockGroupField.prototype.values2post = function (values)
{
	var post = new Array;
	var current = post;
	var i = 0;
	while(i < values.length)
	{
		var p = values[i].name.indexOf('[');
		if(p == -1)
		{
			current[values[i].name] = values[i].value;
			current = post;
			i++;
		}
		else
		{
			var name = values[i].name.substring(0, p);
			var rest = values[i].name.substring(p+1);
			if(!current[name])
				current[name] = new Array;

			var pp = rest.indexOf(']');
			if(pp == -1)
			{
				//Error - not balanced brackets
				current = post;
				i++;
			}
			else if(pp == 0)
			{
				//No index specified - so take the next integer
				current = current[name];
				values[i].name = '' + current.length;
			}
			else
			{
				//Now index name becomes and name and we go deeper into the array
				current = current[name];
				values[i].name = rest.substring(0, pp) + rest.substring(pp+1);
			}
		}
	}
	return post;
}
