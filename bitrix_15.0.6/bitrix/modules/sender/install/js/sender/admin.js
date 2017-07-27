	function GroupManager(isAdd, $controlName)
	{
		var groupExists = BX($controlName+'_EXISTS');
		var groupSelected = BX($controlName);
		var groupSelectedHidden = BX($controlName+'_HIDDEN');
		var groupSelectedOptions = BX.findChildren(groupSelected, {"tag" : "option"});;
		if(isAdd)
		{
			var groupExistsOptions = BX.findChildren(groupExists, {"tag" : "option"});
			if(groupExistsOptions && groupExistsOptions.length > 0)
			{
				var arSelectedValues = [];
				var elementFor;
				for(var i in groupSelectedOptions)
				{
					elementFor = groupSelectedOptions[i];
					if(!elementFor) continue;
					arSelectedValues.push(elementFor.value);
				}


				var elementAdd;
				for(var i in groupExistsOptions)
				{
					elementFor = groupExistsOptions[i];
					if(!elementFor || !elementFor.selected) continue;
					if(!BX.util.in_array(elementFor.value, arSelectedValues))
					{
						elementAdd = elementFor.cloneNode(true);
						groupSelected.appendChild(elementAdd);
					}
				}
			}
		}
		else
		{
			var elementDelete;
			var elementDeleteParent;
			var groupSelectedSelectedOptions = [];
			if(groupSelectedOptions && groupSelectedOptions.length > 0)
			{
				for(var i in groupSelectedOptions)
				{
					if(groupSelectedOptions[i] && groupSelectedOptions[i].selected)
					{
						groupSelectedSelectedOptions.push(groupSelectedOptions[i]);
					}
				}
			}

			while(groupSelectedSelectedOptions.length>0)
			{
				elementDelete = groupSelectedSelectedOptions.pop();
				elementDeleteParent = elementDelete.parentNode;
				if(elementDeleteParent)
					elementDeleteParent.removeChild(elementDelete);
			}
		}

		var element;
		var selectedGroupId = '';
		var arSelectedGroupId = [];
		groupSelectedOptions = BX.findChildren(groupSelected, {"tag" : "option"});
		for(var i in groupSelectedOptions)
		{
			element = groupSelectedOptions[i];
			if(element && element != 'undefined')
			{
				if(element.value != 'undefined' && parseInt(element.value)>0 && !BX.util.in_array(element.value, arSelectedGroupId))
				{
					selectedGroupId = selectedGroupId+element.value+',';
					arSelectedGroupId.push(element.value);
				}
			}
		}
		groupSelectedHidden.value = selectedGroupId;
	}
	

	function ConnectorGetHtmlForm(data)
	{
		var templ = document.getElementById('connector-template');
		var connectorFormHtml = templ.innerHTML;

		for(var key in data)
		{
			connectorFormHtml = connectorFormHtml.replace(new RegExp(key,'g'), data[key]);
		}

		return connectorFormHtml;
	}
	function ConnectorSettingWatch()
	{
        var arConForms = document.getElementsByName('post_form');
        var controls = arConForms[arConForms.length - 1].elements;
		var ctrl;
		for(var i in controls){
			ctrl = controls[i];
			if(ctrl && ctrl.name && BX.type.isString(ctrl.name) && ctrl.name.substring(0,11)=='CONNECTOR_S'){
				BX.unbindAll(BX(ctrl));
				BX.bind(BX(ctrl), 'change', function() {ConnectorSettingGetCount(this);});
			}
		}
	}

	function ConnectorSettingShowToggle(element, elementParent)
	{
		if(element)
			elementParent = BX.findParent(element, {"tag" : "div", "className": "connector_form"}, true);

		BX.toggleClass(elementParent, 'sender-box-list-item-hidden');
		/*
		 var elementContainer = BX.findChild(elementParent, {"tag" : "div", "className": "connector_form_container"}, true);
		 elementContainer.style.display = BX.toggle(elementContainer.style.display, ['block', 'none']);
		 */
	}
	function ConnectorSettingDelete(element)
	{
		var elementDelete = BX.findParent(element, {"tag" : "div", "className": "connector_form"}, true);

		var easing = new BX.easing({
			duration : 500,
			start : { height : 100, opacity: 100 },
			finish : { height : 0, opacity : 0 },
			transition : BX.easing.transitions.quart,
			step : function(state){
				elementDelete.style.opacity = state.opacity/100;
			},
			complete : function() {
				BX.remove(elementDelete);
				ConnectorCounterSummary();
			}
		});
		easing.animate();
	}

	function ConnectorSettingGetCount(element, form)
	{
		var arAjaxQueryFields = {};
		var currentParent;
		var elementParent;
		if(form)
		{
			elementParent = form;
		}
		else
		{
			elementParent = BX.findParent(element, {"tag" : "div", "className": "connector_form"}, true);
		}

        var arConForms = document.getElementsByName('post_form');
        var controls = arConForms[arConForms.length - 1].elements;
		var ctrl;
		for(var i in controls){
			ctrl = controls[i];
			if(ctrl && ctrl.name && BX.type.isString(ctrl.name) && ctrl.name.substring(0,11)=='CONNECTOR_S'){
				currentParent = BX.findParent(ctrl, {"tag" : "div", "className": "connector_form"}, true);
				if(currentParent == elementParent){
					arAjaxQueryFields[ctrl.name] = ctrl.value;
				}
			}
		}

		BX.ajax({
			url: 'sender_group_count.php',
			method: 'POST',
			data: arAjaxQueryFields,
			dataType: 'json',
			timeout: 30,
			async: true,
			processData: true,
			onsuccess: function(data){
				var counter = BX.findChild(elementParent, {
					"className": "connector_form_counter"
				}, true);

				if(counter)
				{
					counter.innerHTML = data.COUNT;
					ConnectorCounterSummary();
				}
			}
		});

	}

	function addNewConnector()
	{
		var name = connectorListToAdd[BX('connector_list_to_add').value]['NAME'];
		var htmlForm = connectorListToAdd[BX('connector_list_to_add').value]['FORM'];
		htmlForm = htmlForm.replace(new RegExp("%CONNECTOR_NUM%",'g'), (Math.floor(Math.random() * (10000 - 100 + 1)) + 100) );

		var html = ConnectorGetHtmlForm({'%CONNECTOR_NAME%':  name, '%CONNECTOR_COUNT%':  '0', '%CONNECTOR_FORM%':  htmlForm});

		var parsedHtml = BX.processHTML(html);


		var newParentElement = document.createElement('div');
		newParentElement.innerHTML = parsedHtml.HTML;

		var newConnectorNode = BX.findChild(newParentElement, {'tag': 'div'});
		var connector_form_container = BX('connector_form_container');
		newConnectorNodeDisplay = newConnectorNode.style.display;
		newConnectorNode.style.display = 'none';

		connector_form_container.insertBefore(newConnectorNode, connector_form_container.firstChild);
		if(parsedHtml.SCRIPT.length>0)
		{
			var script;
			for(var i in parsedHtml['SCRIPT'])
			{
				script = parsedHtml['SCRIPT'][i];
				BX.evalGlobal(script.JS);
			}
		}

		ConnectorSettingShowToggle(false, newConnectorNode);

		var easing = new BX.easing({
			duration : 500,
			start : { height : 0, opacity : 0 },
			finish : { height : 100, opacity: 100 },
			transition : BX.easing.transitions.quart,
			step : function(state){
				newConnectorNode.style.opacity = state.opacity/100;
				newConnectorNode.style.display = newConnectorNodeDisplay;
			},
			complete : function() {
			}
		});
		easing.animate();

		ConnectorSettingGetCount(null, newConnectorNode);
		ConnectorSettingWatch();
	}

	function ConnectorCounterSummary()
	{
		var cnt = 0;
		var cntSummary = 0;
		var findContainer = BX('connector_form_container');
		var counterList = BX.findChildren(findContainer, {"className": "connector_form_counter"}, true);

		for(var i in counterList)
		{
			cnt = parseInt(counterList[i].innerHTML);
			if(!isNaN(cnt))
				cntSummary += cnt;
		}

		BX('sender_group_address_counter').innerHTML = cntSummary;
	}
	
	
	function SetAddressToControl(controlName, address, bAdd)
	{
		var control = BX(controlName);
		if(bAdd)
			control.value += address;
		else
			control.value = address;
	}
	function ProcessAddressToControl(controlName, address, deleteAddress)
	{
		address = BX.util.trim(address);
		var control = BX(controlName);
		var addressList = [];
		var addressListNew = [];
		if(control.value)
			addressList = control.value.split(',');

		var bFind = false;
		for(var addr in addressList)
		{
			addressFromList = BX.util.trim(addressList[addr]);

			if(addressFromList == address)
			{
				bFind = true;
				if(!deleteAddress)
					addressListNew.push(addressFromList);
			}
			else
			{
				addressListNew.push(addressFromList);
			}
		}

		if(!bFind && !deleteAddress)
			addressListNew.push(address);

		control.value = addressListNew.join(', ');
	}
	function DeleteAddressFromControl(controlName, address)
	{
		ProcessAddressToControl(controlName, address, true)
	}
	function AddAddressToControl(controlName, address)
	{
		ProcessAddressToControl(controlName, address, false)
	}

	function SetSendType()
	{
		var sendType = BX('chain_send_type').value;
		var typeContList = BX.findChildren(
			BX('chain_send_type_list_container'),
			{'className': 'sender-box-list-item'},
			true
		);
		for(var i in typeContList){
			if(typeContList[i].id != 'chain_send_type_'+sendType)
				typeContList[i].style.display = 'none';
			else
				typeContList[i].style.display = 'block';
		}

		BX('SEND_TYPE').value = sendType;
		BX('sender_wizard_chain_send_type_btn').disabled = true;
		BX('chain_send_type').disabled = true;
	}

	function DeleteSelectedSendType(obj)
	{
		BX.findParent(obj, {'className':'sender-box-list-item'}).style.display='none';
		BX('SEND_TYPE').value = '';
		BX('sender_wizard_chain_send_type_btn').disabled = false;
		BX('chain_send_type').disabled = false;
	}