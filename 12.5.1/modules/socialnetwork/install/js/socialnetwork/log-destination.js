(function() {
var BX = window.BX;
if(BX.SocNetLogDestination)
	return;

BX.SocNetLogDestination = 
{
	popupWindow: null,
	popupSearchWindow: null,
	sendEvent: true,
	extranetUser: false,
	
	obSearchFirstElement: null,
	searchTimeout: null,

	obDepartmentEnable: {},
	obSonetgroupsEnable: {},
	obLastEnable: {},	

	obWindowClass: {},
	obWindowCloseIcon: {},
	obPathToAjax: {},
	obDepartmentLoad: {},
	obDepartmentSelectDisable: {},
	obItems: {},
	obItemsLast: {},
	obItemsSelected: {},
	obCallback: {},
	obElementSearchInput: {},
	obElementBindMainPopup: {},
	obElementBindSearchPopup: {}
}

BX.SocNetLogDestination.init = function(arParams)
{
	if(!arParams.name)
		arParams.name = 'lm';

	BX.SocNetLogDestination.obPathToAjax[arParams.name] = !arParams.pathToAjax? '/bitrix/components/bitrix/main.post.form/post.ajax.php': arParams.pathToAjax;

	BX.SocNetLogDestination.obCallback[arParams.name] = arParams.callback;
	BX.SocNetLogDestination.obElementBindMainPopup[arParams.name] = arParams.bindMainPopup;
	BX.SocNetLogDestination.obElementBindSearchPopup[arParams.name] = arParams.bindSearchPopup;
	BX.SocNetLogDestination.obElementSearchInput[arParams.name] = arParams.searchInput;
	BX.SocNetLogDestination.obDepartmentSelectDisable[arParams.name] = arParams.departmentSelectDisable == true? true: false;
	BX.SocNetLogDestination.obDepartmentLoad[arParams.name] = {};
	BX.SocNetLogDestination.obWindowClass[arParams.name] = !arParams.obWindowClass? 'bx-lm-socnet-log-destination': arParams.obWindowClass;
	BX.SocNetLogDestination.obWindowCloseIcon[arParams.name] = typeof (arParams.obWindowCloseIcon) == 'undefined' ? true: arParams.obWindowCloseIcon;
	BX.SocNetLogDestination.extranetUser = arParams.extranetUser;

	BX.SocNetLogDestination.obLastEnable[arParams.name] = (arParams.lastTabDisable == true ? false : true);
	BX.SocNetLogDestination.obDepartmentEnable[arParams.name] = false;
	if (arParams.items.department)
	{
		for(var i in arParams.items.department)
		{
			BX.SocNetLogDestination.obDepartmentEnable[arParams.name] = true;
			break;
		}
	}

	BX.SocNetLogDestination.obSonetgroupsEnable[arParams.name] = false;
	if (arParams.items.sonetgroups)
	{
		for(var i in arParams.items.sonetgroups)
		{
			BX.SocNetLogDestination.obSonetgroupsEnable[arParams.name] = true;
			break;
		}
	}

	BX.SocNetLogDestination.obItems[arParams.name] = arParams.items;
	BX.SocNetLogDestination.obItemsLast[arParams.name] = arParams.itemsLast;
	BX.SocNetLogDestination.obItemsSelected[arParams.name] = arParams.itemsSelected;

	for (var itemId in BX.SocNetLogDestination.obItemsSelected[arParams.name])
	{
		var type = BX.SocNetLogDestination.obItemsSelected[arParams.name][itemId];	
		BX.SocNetLogDestination.runSelectCallback(itemId, type, arParams.name);
	}
}

BX.SocNetLogDestination.openDialog = function(name)
{
	if(!name)
		name = 'lm';

	if (BX.SocNetLogDestination.popupSearchWindow != null)
		BX.SocNetLogDestination.popupSearchWindow.close();

	if (BX.SocNetLogDestination.popupWindow != null)
	{
		BX.SocNetLogDestination.popupWindow.close();
		return false;
	}

	BX.SocNetLogDestination.popupWindow = new BX.PopupWindow('BXSocNetLogDestination', BX.SocNetLogDestination.obElementBindMainPopup[name].node, {
		autoHide: true,
		offsetLeft: parseInt(BX.SocNetLogDestination.obElementBindMainPopup[name].offsetLeft),
		offsetTop: parseInt(BX.SocNetLogDestination.obElementBindMainPopup[name].offsetTop),
		bindOptions: {forceBindPosition: true},
		closeByEsc: true,
		closeIcon : BX.SocNetLogDestination.obWindowCloseIcon[name]? {'top': '12px', 'right': '15px'}: false,
		events : {
			onPopupShow : function() {
				if(BX.SocNetLogDestination.sendEvent && BX.SocNetLogDestination.obCallback[name] && BX.SocNetLogDestination.obCallback[name].openDialog)
					BX.SocNetLogDestination.obCallback[name].openDialog();
			},
			onPopupClose : function() { 
				this.destroy();
			},
			onPopupDestroy : BX.proxy(function() { 
				BX.SocNetLogDestination.popupWindow = null; 
				if(BX.SocNetLogDestination.sendEvent && BX.SocNetLogDestination.obCallback[name] && BX.SocNetLogDestination.obCallback[name].closeDialog)
					BX.SocNetLogDestination.obCallback[name].closeDialog();
			}, this)
		},
		content: 
		'<div class="bx-finder-box bx-lm-box '+BX.SocNetLogDestination.obWindowClass[name] +'" style="width: 450px; padding-bottom: 8px;">'+
			(!BX.SocNetLogDestination.obLastEnable[name] && !BX.SocNetLogDestination.obSonetgroupsEnable[name] && !BX.SocNetLogDestination.obDepartmentEnable[name]? '':
			'<div class="bx-finder-box-tabs">'+
				(BX.SocNetLogDestination.obLastEnable[name] ? '<a hidefocus="true" onclick="return BX.SocNetLogDestination.SwitchTab(\''+name+'\', this, \'last\')" class="bx-finder-box-tab bx-lm-tab-last bx-finder-box-tab-selected" href="#switchTab"><span class="bx-finder-box-tab-left"></span><span class="bx-finder-box-tab-text">'+BX.message('LM_POPUP_TAB_LAST')+'</span><span class="bx-finder-box-tab-right"></span></a>':'')+
				(BX.SocNetLogDestination.obSonetgroupsEnable[name] ? '<a hidefocus="true" onclick="return BX.SocNetLogDestination.SwitchTab(\''+name+'\', this, \'group\')" class="bx-finder-box-tab bx-lm-tab-sonetgroup" href="#switchTab"><span class="bx-finder-box-tab-left"></span><span class="bx-finder-box-tab-text">'+BX.message('LM_POPUP_TAB_SG')+'</span><span class="bx-finder-box-tab-right"></span></a>':'')+
				(BX.SocNetLogDestination.obDepartmentEnable[name] ? '<a hidefocus="true" id="destDepartmentTab_'+name+'" onclick="return BX.SocNetLogDestination.SwitchTab(\''+name+'\', this, \'department\')" class="bx-finder-box-tab bx-lm-tab-department" href="#switchTab"><span class="bx-finder-box-tab-left"></span><span class="bx-finder-box-tab-text">'+BX.message('LM_POPUP_TAB_STRUCTURE')+'</span><span class="bx-finder-box-tab-right"></span></a>':'')+
			'</div><div class="popup-window-hr popup-window-buttons-hr"><i></i></div>')+
			'<div class="bx-finder-box-tabs-content bx-finder-box-tabs-content-window">'+
				(BX.SocNetLogDestination.obLastEnable[name] ? '<div class="bx-finder-box-tab-content bx-lm-box-tab-content-last' + (BX.SocNetLogDestination.obLastEnable[name] ? ' bx-finder-box-tab-content-selected' : '') + '">'
					+BX.SocNetLogDestination.getItemLastHtml(false, false, name)+
				'</div>' : '') +
				(BX.SocNetLogDestination.obSonetgroupsEnable[name] ? '<div class="bx-finder-box-tab-content bx-lm-box-tab-content-sonetgroup' + (!BX.SocNetLogDestination.obLastEnable[name] && BX.SocNetLogDestination.obSonetgroupsEnable[name] ? ' bx-finder-box-tab-content-selected' : '') + '"></div>' : '') +
				(BX.SocNetLogDestination.obDepartmentEnable[name] ? '<div class="bx-finder-box-tab-content bx-lm-box-tab-content-department' + (!BX.SocNetLogDestination.obLastEnable[name] && !BX.SocNetLogDestination.obSonetgroupsEnable[name] && BX.SocNetLogDestination.obDepartmentEnable[name] ? ' bx-finder-box-tab-content-selected' : '') + '"></div>' : '') +
			'</div>'+
		'</div>'
	});
	BX.SocNetLogDestination.popupWindow.setAngle({});
	BX.SocNetLogDestination.popupWindow.show();
	
	if (
		!BX.SocNetLogDestination.obLastEnable[name] 
		&& !BX.SocNetLogDestination.obSonetgroupsEnable[name] 
		&& BX.SocNetLogDestination.obDepartmentEnable[name]
		&& BX('destDepartmentTab_'+name)
	)
		BX.SocNetLogDestination.SwitchTab(name, BX('destDepartmentTab_'+name), 'department');
}

BX.SocNetLogDestination.search = function(text, sendAjax, name, nameTemplate)
{
	if(!name)
		name = 'lm';

	sendAjax = sendAjax == false? false: true;
	if (BX.SocNetLogDestination.extranetUser)
		sendAjax = false;
	
	BX.SocNetLogDestination.obSearchFirstElement = null;

	if (text.length <= 0)
	{
		clearTimeout(BX.SocNetLogDestination.searchTimeout);
		if(BX.SocNetLogDestination.popupSearchWindow != null)
			BX.SocNetLogDestination.popupSearchWindow.close();
		return false;
	}
	else
	{
		var items = {'groups': {}, 'users':{}, 'sonetgroups': {}, 'department' : {}};
		var count = 0;
		for (var group in items)
		{
			if((BX.SocNetLogDestination.obDepartmentSelectDisable[name] && group == 'department'))
				continue;
			for (var i in BX.SocNetLogDestination.obItems[name][group])
			{
				if (BX.SocNetLogDestination.obItemsSelected[name][i])
					continue;
				if (BX.SocNetLogDestination.obItems[name][group][i].name.toLowerCase().indexOf(text.toLowerCase()) < 0)
					continue;
				items[group][i] = true;
				if (count <= 0)
				{
					var item = BX.clone(BX.SocNetLogDestination.obItems[name][group][i]);
					item.type = group;
					BX.SocNetLogDestination.obSearchFirstElement = item;
				}
				count++;
			}
		}
		
		if (sendAjax)
		{
			
			if (BX.SocNetLogDestination.popupSearchWindow != null)
				BX.SocNetLogDestination.popupSearchWindowContent.innerHTML = BX.SocNetLogDestination.getItemLastHtml(items, true, name);
			else
			{
				if (count > 0)
					BX.SocNetLogDestination.openSearch(items, name);
			}
		}
		else
		{
			if (count <= 0)
			{
				if (BX.SocNetLogDestination.popupSearchWindow != null)
					BX.SocNetLogDestination.popupSearchWindow.destroy();
			}
			else
			{
				if (BX.SocNetLogDestination.popupSearchWindow != null)
					BX.SocNetLogDestination.popupSearchWindowContent.innerHTML = BX.SocNetLogDestination.getItemLastHtml(items, true, name);
				else
					BX.SocNetLogDestination.openSearch(items, name);
			}
		}

		clearTimeout(BX.SocNetLogDestination.searchTimeout);
		if (sendAjax && text.toLowerCase() != '')
		{
			BX.SocNetLogDestination.searchTimeout = setTimeout(function(){
				BX.ajax({
					url: BX.SocNetLogDestination.obPathToAjax[name],
					method: 'POST',
					dataType: 'json',
					data: {'LD_SEARCH' : 'Y', 'SEARCH' : text.toLowerCase(), 'sessid': BX.bitrix_sessid(), 'nt': nameTemplate},
					onsuccess: function(data){
						for(var i in data.USERS)
						{
							if (!BX.SocNetLogDestination.obItems[name].users[i])
								BX.SocNetLogDestination.obItems[name].users[i]	= data.USERS[i];							
						}
						BX.SocNetLogDestination.search(text, false, name, nameTemplate);
					},
					onfailure: function(data) {} 
				});
			}, 1000);
		}
	}
}

BX.SocNetLogDestination.openSearch = function(items, name)
{
	if(!name)
		name = 'lm';

	if (BX.SocNetLogDestination.popupWindow != null)
		BX.SocNetLogDestination.popupWindow.close();

	if (BX.SocNetLogDestination.popupSearchWindow != null)
	{
		BX.SocNetLogDestination.popupSearchWindow.close();
		return false;
	}

	BX.SocNetLogDestination.popupSearchWindow = new BX.PopupWindow('BXSocNetLogDestinationSearch', BX.SocNetLogDestination.obElementBindSearchPopup[name].node, {
		autoHide: true,
		offsetLeft: parseInt(BX.SocNetLogDestination.obElementBindSearchPopup[name].offsetLeft),
		offsetTop: parseInt(BX.SocNetLogDestination.obElementBindSearchPopup[name].offsetTop),
		bindOptions: {forceBindPosition: true},
		closeByEsc: true,
		closeIcon : BX.SocNetLogDestination.obWindowCloseIcon[name]? {'top': '12px', 'right': '15px'}: false,
		events : {
			onPopupShow : function() {
				if(BX.SocNetLogDestination.sendEvent && BX.SocNetLogDestination.obCallback[name] && BX.SocNetLogDestination.obCallback[name].openSearch)
					BX.SocNetLogDestination.obCallback[name].openSearch();
			},
			onPopupClose : function() { 
				this.destroy();
				if(BX.SocNetLogDestination.sendEvent && BX.SocNetLogDestination.obCallback[name] && BX.SocNetLogDestination.obCallback[name].closeSearch)
					BX.SocNetLogDestination.obCallback[name].closeSearch();
			},
			onPopupDestroy : BX.proxy(function() { 
				BX.SocNetLogDestination.popupSearchWindow = null; 
				BX.SocNetLogDestination.popupSearchWindowContent = null;
			}, this)
		},
		content: 
		'<div class="bx-finder-box bx-lm-box '+BX.SocNetLogDestination.obWindowClass[name] +'" style="width: 450px; padding-bottom: 8px;">'+
			'<div class="bx-finder-box-tabs-content">'+
				'<div id="bx-lm-box-search-content" class="bx-finder-box-tab-content bx-finder-box-tab-content-selected">'
					+BX.SocNetLogDestination.getItemLastHtml(items, true, name)+
				'</div>'+
			'</div>'+
		'</div>'
	});
	BX.SocNetLogDestination.popupSearchWindow.setAngle({});
	BX.SocNetLogDestination.popupSearchWindow.show();
	BX.SocNetLogDestination.popupSearchWindowContent = BX('bx-lm-box-search-content');
}

/* privat function */
BX.SocNetLogDestination.getItemLastHtml = function(lastItems, search, name)
{
	if(!name)
		name = 'lm';

	if (!lastItems)
		lastItems = BX.SocNetLogDestination.obItemsLast[name];

	var itemsHtml = '';
	var count = 0;
	if (search)
	{
		for (var i in lastItems.groups)
		{
			if (!BX.SocNetLogDestination.obItems[name].groups[i])
				continue;
			itemsHtml = BX.SocNetLogDestination.getHtmlByTemplate3(name, BX.SocNetLogDestination.obItems[name].groups[i], {className: 'bx-lm-element-groups', itemType: 'groups', 'search': true, 'itemHover': (count <=0? true: false)});
			count++;
		}
		
		for (var i in lastItems.users)
		{
			if (!BX.SocNetLogDestination.obItems[name].users[i])
				continue;
			itemsHtml += BX.SocNetLogDestination.getHtmlByTemplate3(name, BX.SocNetLogDestination.obItems[name].users[i], {className: 'bx-lm-element-user', itemType: 'users', 'search': true, 'itemHover': (count <=0? true: false)});
			count++;
		}
	}
	else
	{
		for (var i in lastItems.groups)
		{
			if (!BX.SocNetLogDestination.obItems[name].groups[i])
				continue;
			itemsHtml = BX.SocNetLogDestination.getHtmlByTemplate5(name, BX.SocNetLogDestination.obItems[name].groups[i], {className: 'bx-lm-element-groups', itemType: 'groups', 'search': false});
			count++;
		}
		for (var i in lastItems.users)
		{
			if (!BX.SocNetLogDestination.obItems[name].users[i])
				continue;
			itemsHtml += BX.SocNetLogDestination.getHtmlByTemplate5(name, BX.SocNetLogDestination.obItems[name].users[i], {className: 'bx-lm-element-user', itemType: 'users', 'search': false});
			count++;
		}
	}
	
	var html = '';
	if (itemsHtml != '')
	{
		html += 
			'<span class="bx-finder-groupbox bx-lm-groupbox-last">'+
				'<span class="bx-finder-groupbox-name">'+BX.message('LM_POPUP_TAB_LAST_USERS')+':</span>'+
				'<span class="bx-finder-groupbox-content">'+itemsHtml+'</span>'+
			'</span>';
	}

	itemsHtml = '';
	for (var i in lastItems.sonetgroups)
	{
		if (!BX.SocNetLogDestination.obItems[name].sonetgroups[i])
			continue;
		itemsHtml += BX.SocNetLogDestination.getHtmlByTemplate3(name, BX.SocNetLogDestination.obItems[name].sonetgroups[i], {className: 'bx-lm-element-sonetgroup', itemType: 'sonetgroups', 'search': search, 'itemHover': (count <=0? true: false)});
		count++;
	}
	
	if (itemsHtml != '')
	{
		html += 
			'<span class="bx-finder-groupbox bx-lm-groupbox-sonetgroup">'+
				'<span class="bx-finder-groupbox-name">'+BX.message('LM_POPUP_TAB_LAST_SG')+':</span>'+
				'<span class="bx-finder-groupbox-content">'+itemsHtml+'</span>'+
			'</span>';
	}
	
	if (BX.SocNetLogDestination.obDepartmentEnable[name])
	{
		itemsHtml = '';
		if (search)
		{
			for (var i in lastItems.department)
			{
				if (!BX.SocNetLogDestination.obItems[name].department[i])
					continue;
				itemsHtml += BX.SocNetLogDestination.getHtmlByTemplate3(name, BX.SocNetLogDestination.obItems[name].department[i], {className: 'bx-lm-element-department', itemType: 'department', 'search': true, 'itemHover': (count <=0? true: false)});
				count++;
			}
		}
		else
		{
			for (var i in lastItems.department)
			{
				if (!BX.SocNetLogDestination.obItems[name].department[i])
					continue;
				itemsHtml += BX.SocNetLogDestination.getHtmlByTemplate3(name, BX.SocNetLogDestination.obItems[name].department[i], {className: 'bx-lm-element-department', itemType: 'department', 'search': false});			
				count++;
			}
		}
		if (itemsHtml != '')
		{
			html += 
				'<span class="bx-finder-groupbox bx-lm-groupbox-department">'+
					'<span class="bx-finder-groupbox-name">'+BX.message('LM_POPUP_TAB_LAST_STRUCTURE')+':</span>'+
					'<span class="bx-finder-groupbox-content">'+itemsHtml+'</span>'+
				'</span>';
		}
	}

	if (html.length <= 0)
	{
		html = 
				'<span class="bx-finder-groupbox bx-lm-groupbox-search">'+
					'<span class="bx-finder-groupbox-content">'+BX.message('LM_SEARCH_PLEASE_WAIT')+'</span>'+
				'</span>';
	}

	return html;
}
BX.SocNetLogDestination.getItemGroupHtml = function(name)
{
	if(!name)
		name = 'lm';

	var html = '';
	for (var i in BX.SocNetLogDestination.obItems[name].sonetgroups)
		html += BX.SocNetLogDestination.getHtmlByTemplate6(name, BX.SocNetLogDestination.obItems[name].sonetgroups[i], {itemType: 'sonetgroups'});
	
	return html;
}
BX.SocNetLogDestination.getItemDepartmentHtml = function(name, relation, categoryId, categoryOpened)
{
	if(!name)
		name = 'lm';

	categoryId = categoryId ? categoryId: false;
	categoryOpened = categoryOpened ? true: false;

	var bFirstRelation = false;
	if(!relation)
	{
		relation = BX.SocNetLogDestination.obItems[name].departmentRelation;
		bFirstRelation = true;
	}

	var html = '';
	for (var i in relation)
	{
		if (relation[i].type == 'category')
		{
			var category = BX.SocNetLogDestination.obItems[name].department[relation[i].id];
			var activeClass = BX.SocNetLogDestination.obItemsSelected[name][relation[i].id]? 'bx-finder-company-department-check-checked': '';
			html += '<div class="bx-finder-company-department'+(bFirstRelation? ' bx-finder-company-department-opened': '')+'"><a href="#'+category.id+'" class="bx-finder-company-department-inner" onclick="return BX.SocNetLogDestination.OpenCompanyDepartment(\''+name+'\', this.parentNode, \''+category.entityId+'\')" hidefocus="true"><div class="bx-finder-company-department-arrow"></div><div class="bx-finder-company-department-text">'+category.name+'</div></a></div>';
			html += '<div class="bx-finder-company-department-children'+(bFirstRelation? ' bx-finder-company-department-children-opened': '')+'">';
			if(!BX.SocNetLogDestination.obDepartmentSelectDisable[name])
			{
				html += '<a class="bx-finder-company-department-check '+activeClass+' bx-finder-element" hidefocus="true" onclick="return BX.SocNetLogDestination.selectItem(\''+name+'\', this, \'department\', \''+relation[i].id+'\', \'department\')" rel="'+relation[i].id+'" href="#'+relation[i].id+'">';
				html += '<span class="bx-finder-company-department-check-inner">\
						<div class="bx-finder-company-department-check-arrow"></div>\
						<div class="bx-finder-company-department-check-text" rel="'+category.name+': '+BX.message("LM_POPUP_CHECK_STRUCTURE")+'">'+BX.message("LM_POPUP_CHECK_STRUCTURE")+'</div>\
					</span>\
				</a>';
			}
			html += BX.SocNetLogDestination.getItemDepartmentHtml(name, relation[i].items, category.entityId, bFirstRelation);
			html += '</div>';
		}
	}
	if (categoryId)
	{
		html += '<div class="bx-finder-company-department-employees" id="bx-lm-category-relation-'+categoryId+'">';
		userCount = 0;
		for (var i in relation)
		{
			if (relation[i].type == 'user')
			{
				var user = BX.SocNetLogDestination.obItems[name].users[relation[i].id];
				if (user == null)
					continue;
				
				var activeClass = BX.SocNetLogDestination.obItemsSelected[name][relation[i].id]? 'bx-finder-company-department-employee-selected': '';
				html += '<a href="#'+user.id+'" class="bx-finder-company-department-employee '+activeClass+' bx-finder-element" rel="'+user.id+'" onclick="return BX.SocNetLogDestination.selectItem(\''+name+'\', this, \'department-user\', \''+user.id+'\', \'users\')" hidefocus="true">\
					<div class="bx-finder-company-department-employee-icon"></div>\
					<div class="bx-finder-company-department-employee-info">\
						<div class="bx-finder-company-department-employee-name">'+user.name+'</div>\
						<div class="bx-finder-company-department-employee-position">'+user.desc+'</div>\
					</div>\
					<div style="'+(user.avatar? 'background:url(\''+user.avatar+'\') no-repeat center center': '')+'" class="bx-finder-company-department-employee-avatar"></div>\
				</a>';
				userCount++;
			}
		}
		if (userCount <=0)
		{
			if (!BX.SocNetLogDestination.obDepartmentLoad[name][categoryId])
				html += '<div class="bx-finder-company-department-employees-loading">'+BX.message('LM_PLEASE_WAIT')+'</div>';
			if (categoryOpened)
				BX.SocNetLogDestination.getDepartmentRelation(name, categoryId);
		}
		html += '</div>';
	}
	
	return html;
}

BX.SocNetLogDestination.getDepartmentRelation = function(name, departmentId)
{
	if (BX.SocNetLogDestination.obDepartmentLoad[name][departmentId])
		return false;
	
	BX.ajax({
		url: BX.SocNetLogDestination.obPathToAjax[name],
		method: 'POST',
		dataType: 'json',
		data: {'LD_DEPARTMENT_RELATION' : 'Y', 'DEPARTMENT_ID' : departmentId, 'sessid': BX.bitrix_sessid()},
		onsuccess: function(data){
			BX.SocNetLogDestination.obDepartmentLoad[name][departmentId] = true;
			var departmentItem = BX.util.object_search_key('DR'+departmentId, BX.SocNetLogDestination.obItems[name].departmentRelation);

			html = '';
			for(var i in data.USERS)
			{
				if (!BX.SocNetLogDestination.obItems[name].users[i])
					BX.SocNetLogDestination.obItems[name].users[i]	= data.USERS[i];	

				if (!departmentItem.items[i])
				{
					departmentItem.items[i] = {'id': i,	'type': 'user'};
					var activeClass = BX.SocNetLogDestination.obItemsSelected[name][data.USERS[i].id]? 'bx-finder-company-department-employee-selected': '';
					html += '<a href="#'+data.USERS[i].id+'" class="bx-finder-company-department-employee '+activeClass+' bx-finder-element" rel="'+data.USERS[i].id+'" onclick="return BX.SocNetLogDestination.selectItem(\''+name+'\', this, \'department-user\', \''+data.USERS[i].id+'\', \'users\')" hidefocus="true">\
						<div class="bx-finder-company-department-employee-icon"></div>\
						<div class="bx-finder-company-department-employee-info">\
							<div class="bx-finder-company-department-employee-name">'+data.USERS[i].name+'</div>\
							<div class="bx-finder-company-department-employee-position">'+data.USERS[i].desc+'</div>\
						</div>\
						<div style="'+(data.USERS[i].avatar? 'background:url(\''+data.USERS[i].avatar+'\') no-repeat center center': '')+'" class="bx-finder-company-department-employee-avatar"></div>\
					</a>';	
				}								
			}
			BX('bx-lm-category-relation-'+departmentId).innerHTML = html;

		},
		onfailure: function(data)	{} 
	});
}

BX.SocNetLogDestination.getHtmlByTemplate1 = function(name, item, params)
{
	if(!name)
		name = 'lm';
	if(!params)
		params = {};

	var activeClass = BX.SocNetLogDestination.obItemsSelected[name][item.id]? ' bx-finder-box-item-selected': '';
	var hoverClass = params.itemHover? 'bx-finder-box-item-hover': '';
	var html = '<a class="bx-finder-box-item '+activeClass+' '+hoverClass+' bx-finder-element'+(params.className? ' '+params.className: '')+'" hidefocus="true" onclick="return BX.SocNetLogDestination.selectItem(\''+name+'\', this, 1, \''+item.id+'\', \''+(params.itemType? params.itemType: 'item')+'\', '+(params.search? true: false)+')" rel="'+item.id+'" href="#'+item.id+'">\
		<div class="bx-finder-box-item-text">'+item.name+'</div>\
		<div class="bx-finder-box-item-icon"></div>\
	</a>';
	return html;
}

BX.SocNetLogDestination.getHtmlByTemplate2 = function(name, item, params)
{
	if(!name)
		name = 'lm';
	if(!params)
		params = {};

	var activeClass = BX.SocNetLogDestination.obItemsSelected[name][item.id]? ' bx-finder-box-item-t2-selected': '';
	var hoverClass = params.itemHover? 'bx-finder-box-item-t2-hover': '';
	var html = '<a class="bx-finder-box-item-t2 '+activeClass+' '+hoverClass+' bx-finder-element'+(params.className? ' '+params.className: '')+'" hidefocus="true" onclick="return BX.SocNetLogDestination.selectItem(\''+name+'\', this, 2, \''+item.id+'\', \''+(params.itemType? params.itemType: 'item')+'\', '+(params.search? true: false)+')" rel="'+item.id+'" href="#'+item.id+'">\
		<div class="bx-finder-box-item-t2-text">'+item.name+'</div>\
		<div class="bx-finder-box-item-t2-icon"></div>\
	</a>';
	return html;
}

BX.SocNetLogDestination.getHtmlByTemplate3 = function(name, item, params)
{
	if(!name)
		name = 'lm';
	if(!params)
		params = {};

	var activeClass = BX.SocNetLogDestination.obItemsSelected[name][item.id]? ' bx-finder-box-item-t3-selected': '';
	var hoverClass = params.itemHover? 'bx-finder-box-item-t3-hover': '';
	var html = '<a hidefocus="true" onclick="return BX.SocNetLogDestination.selectItem(\''+name+'\', this, 3, \''+item.id+'\', \''+(params.itemType? params.itemType: 'item')+'\', '+(params.search? true: false)+')" rel="'+item.id+'" class="bx-finder-box-item-t3 '+activeClass+' '+hoverClass+' bx-finder-element'+(params.className? ' '+params.className: '')+'" href="#'+item.id+'">'+
		'<div class="bx-finder-box-item-t3-avatar" '+(item.avatar? 'style="background:url(\''+item.avatar+'\') no-repeat center center"':'')+'></div>'+
		'<div class="bx-finder-box-item-t3-info">'+
			'<div class="bx-finder-box-item-t3-icon"></div>'+
			'<div class="bx-finder-box-item-t3-name">'+item.name+'</div>'+
			(item.desc? '<div class="bx-finder-box-item-t3-desc">'+item.desc+'</div>': '')+
		'</div>'+
		'<div class="bx-clear"></div>'+
	'</a>';
	return html;
}

BX.SocNetLogDestination.getHtmlByTemplate5 = function(name, item, params)
{
	if(!name)
		name = 'lm';
	if(!params)
		params = {};

	var activeClass = BX.SocNetLogDestination.obItemsSelected[name][item.id]? ' bx-finder-box-item-t5-selected': '';
	var hoverClass = params.itemHover? 'bx-finder-box-item-t5-hover': '';
	var html = '<a hidefocus="true" onclick="return BX.SocNetLogDestination.selectItem(\''+name+'\', this, 5, \''+item.id+'\', \''+(params.itemType? params.itemType: 'item')+'\', '+(params.search? true: false)+')" rel="'+item.id+'" class="bx-finder-box-item-t5 '+activeClass+' '+hoverClass+' bx-finder-element'+(params.className? ' '+params.className: '')+'" href="#'+item.id+'">'+
		'<div class="bx-finder-box-item-t5-avatar" '+(item.avatar? 'style="background:url(\''+item.avatar+'\') no-repeat center center"':'')+'></div>'+
		'<div class="bx-finder-box-item-t5-info">'+
			'<div class="bx-finder-box-item-t5-icon"></div>'+
			'<div class="bx-finder-box-item-t5-name">'+item.name+'</div>'+
			(item.desc? '<div class="bx-finder-box-item-t5-desc">'+item.desc+'</div>': '')+
		'</div>'+
		'<div class="bx-clear"></div>'+
	'</a>';
	return html;
}

BX.SocNetLogDestination.getHtmlByTemplate6 = function(name, item, params)
{
	if(!name)
		name = 'lm';
	if(!params)
		params = {};

	var activeClass = BX.SocNetLogDestination.obItemsSelected[name][item.id]? ' bx-finder-box-item-t6-selected': '';
	var hoverClass = params.itemHover? 'bx-finder-box-item-t6-hover': '';
	var html = '<a hidefocus="true" onclick="return BX.SocNetLogDestination.selectItem(\''+name+'\', this, 6, \''+item.id+'\', \''+(params.itemType? params.itemType: 'item')+'\', '+(params.search? true: false)+')" rel="'+item.id+'" class="bx-finder-box-item-t6 '+activeClass+' '+hoverClass+' bx-finder-element'+(params.className? ' '+params.className: '')+'" href="#'+item.id+'">'+
		'<div class="bx-finder-box-item-t6-avatar" '+(item.avatar? 'style="background:url(\''+item.avatar+'\') no-repeat center center"':'')+'></div>'+
		'<div class="bx-finder-box-item-t6-info">'+
			'<div class="bx-finder-box-item-t6-icon"></div>'+
			'<div class="bx-finder-box-item-t6-name">'+item.name+'</div>'+
			(item.desc? '<div class="bx-finder-box-item-t6-desc">'+item.desc+'</div>': '')+
		'</div>'+
		'<div class="bx-clear"></div>'+
	'</a>';
	return html;
}

BX.SocNetLogDestination.SwitchTab = function(name, currentTab, type)
{
	var tabsContent = BX.findChildren(
		BX.findChild(currentTab.parentNode.parentNode, { tagName : "div", className : "bx-finder-box-tabs-content"}),
		{ tagName : "div" }
	);

	if (!tabsContent)
		return false;
				
	var tabIndex = 0;
	var tabs = BX.findChildren(currentTab.parentNode, { tagName : "a" });
	for (var i = 0; i < tabs.length; i++)
	{
		if (tabs[i] === currentTab)
		{
			BX.addClass(tabs[i], "bx-finder-box-tab-selected");
			tabIndex = i;
		}
		else
			BX.removeClass(tabs[i], "bx-finder-box-tab-selected");
	}

	for (i = 0; i < tabsContent.length; i++)
	{
		if (tabIndex === i)
		{
			if (type == 'last')
				tabsContent[i].innerHTML = BX.SocNetLogDestination.getItemLastHtml(false, false, name);
			else if (type == 'group')
				tabsContent[i].innerHTML = BX.SocNetLogDestination.getItemGroupHtml(name);
			else if (type == 'department')
				tabsContent[i].innerHTML = BX.SocNetLogDestination.getItemDepartmentHtml(name);
			BX.addClass(tabsContent[i], "bx-finder-box-tab-content-selected");
		}
		else
			BX.removeClass(tabsContent[i], "bx-finder-box-tab-content-selected");
	}
	BX.focus(BX.SocNetLogDestination.obElementSearchInput[name]);
	return false;
}

BX.SocNetLogDestination.OpenCompanyDepartment = function(name, department, categoryId)
{
	if(!name)
		name = 'lm';

	BX.toggleClass(department, "bx-finder-company-department-opened");

	var nextDiv = BX.findNextSibling(department, { tagName : "div"} );
	if (BX.hasClass(nextDiv, "bx-finder-company-department-children"))
		BX.toggleClass(nextDiv, "bx-finder-company-department-children-opened");

	BX.SocNetLogDestination.getDepartmentRelation(name, categoryId);
	
	return false;
}

Object.size = function(obj) {
    var size = 0, key;
    for (key in obj) {
        if (obj.hasOwnProperty(key)) size++;
    }
    return size;
};

BX.SocNetLogDestination.selectItem = function(name, element, template, itemId, type, search)
{
	if(!name)
		name = 'lm';

	BX.focus(BX.SocNetLogDestination.obElementSearchInput[name]);

	if (BX.SocNetLogDestination.obItemsSelected[name][itemId])
		return BX.SocNetLogDestination.unSelectItem(name, element, template, itemId, type, search);
	
	BX.SocNetLogDestination.obItemsSelected[name][itemId] = type;
	BX.SocNetLogDestination.obItemsLast[name][type][itemId] = itemId;
	
	if (!(element == null || template == null))
	{
		if (template == 1)
			BX.addClass(element, 'bx-finder-box-item-selected');
		else if (template == 2)
			BX.addClass(element, 'bx-finder-box-item-t2-selected');
		else if (template == 3)
			BX.addClass(element, 'bx-finder-box-item-t3-selected');
		else if (template == 4)
			BX.addClass(element, 'bx-finder-box-item-t3-selected');
		else if (template == 5)
			BX.addClass(element, 'bx-finder-box-item-t5-selected');
		else if (template == 6)
			BX.addClass(element, 'bx-finder-box-item-t6-selected');	
		else if (template == 'department-user')
			BX.addClass(element, 'bx-finder-company-department-employee-selected');
		else if (template == 'department')
			BX.addClass(element, 'bx-finder-company-department-check-checked');
	}
	
	BX.SocNetLogDestination.runSelectCallback(itemId, type, name, search);
	
	if (search === true)
	{
		if (BX.SocNetLogDestination.popupWindow != null)
			BX.SocNetLogDestination.popupWindow.close();
		if (BX.SocNetLogDestination.popupSearchWindow != null)
			BX.SocNetLogDestination.popupSearchWindow.close();	
	}
	else
	{
		if (BX.SocNetLogDestination.popupWindow != null)
			BX.SocNetLogDestination.popupWindow.adjustPosition();
		if (BX.SocNetLogDestination.popupSearchWindow != null)
			BX.SocNetLogDestination.popupSearchWindow.adjustPosition();		
	}
	
	
	var objSize = Object.size(BX.SocNetLogDestination.obItemsLast[name][type]);
	
	if(objSize > 5)
	{
		var destLast = {};
		var ii = 0;
		var jj = objSize-5;
		
		for(var i in BX.SocNetLogDestination.obItemsLast[name][type])
		{
			if(ii >= jj)
				destLast[BX.SocNetLogDestination.obItemsLast[name][type][i]] = BX.SocNetLogDestination.obItemsLast[name][type][i];
			ii++;
		}
	}
	else
	{
		var destLast = BX.SocNetLogDestination.obItemsLast[name][type];
	}

 	BX.userOptions.save('socialnetwork', 'log_destination', type, JSON.stringify(destLast));

	return false;
};

BX.SocNetLogDestination.unSelectItem = function(name, element, template, itemId, type, search)
{
	if(!name)
		name = 'lm';

	delete BX.SocNetLogDestination.obItemsLast[name][type][itemId];
	if (!BX.SocNetLogDestination.obItemsSelected[name][itemId])
		return false;
		
	if (template == 1)
		BX.removeClass(element, 'bx-finder-box-item-selected');
	else if (template == 2)
		BX.removeClass(element, 'bx-finder-box-item-t2-selected');
	else if (template == 3)
		BX.removeClass(element, 'bx-finder-box-item-t3-selected');
	else if (template == 4)
		BX.removeClass(element, 'bx-finder-box-item-t3-selected');
	else if (template == 5)
		BX.removeClass(element, 'bx-finder-box-item-t5-selected');
	else if (template == 6)
		BX.removeClass(element, 'bx-finder-box-item-t6-selected');	
	else if (template == 'department-user')
		BX.removeClass(element, 'bx-finder-company-department-employee-selected');
	else if (template == 'department')
		BX.removeClass(element, 'bx-finder-company-department-check-checked');
	
	BX.SocNetLogDestination.runUnSelectCallback(itemId, type, name, search);
	
	if (search === true)
	{
		if (BX.SocNetLogDestination.popupWindow != null)
			BX.SocNetLogDestination.popupWindow.close();
		if (BX.SocNetLogDestination.popupSearchWindow != null)
			BX.SocNetLogDestination.popupSearchWindow.close();	
	}
	else
	{
		if (BX.SocNetLogDestination.popupWindow != null)
			BX.SocNetLogDestination.popupWindow.adjustPosition();
		if (BX.SocNetLogDestination.popupSearchWindow != null)
			BX.SocNetLogDestination.popupSearchWindow.adjustPosition();		
	}

	return false;
};


BX.SocNetLogDestination.runSelectCallback = function(itemId, type, name, search)
{
	if(!name)
		name = 'lm';

	if(!search)
		search = false;

	if(BX.SocNetLogDestination.obCallback[name] && BX.SocNetLogDestination.obCallback[name].select && BX.SocNetLogDestination.obItems[name][type] && BX.SocNetLogDestination.obItems[name][type][itemId])
		BX.SocNetLogDestination.obCallback[name].select(BX.SocNetLogDestination.obItems[name][type][itemId], type, search);
}

BX.SocNetLogDestination.runUnSelectCallback = function(itemId, type, name, search)
{
	if(!name)
		name = 'lm';

	if(!search)
		search = false;

	delete BX.SocNetLogDestination.obItemsSelected[name][itemId];
	if(BX.SocNetLogDestination.obCallback[name] && BX.SocNetLogDestination.obCallback[name].unSelect && BX.SocNetLogDestination.obItems[name][type] && BX.SocNetLogDestination.obItems[name][type][itemId])
		BX.SocNetLogDestination.obCallback[name].unSelect(BX.SocNetLogDestination.obItems[name][type][itemId], type, search);
}

/* public function */
BX.SocNetLogDestination.deleteItem = function(itemId, type, name)
{
	if(!name)
		name = 'lm';

	BX.SocNetLogDestination.runUnSelectCallback(itemId, type, name);
}
BX.SocNetLogDestination.deleteLastItem = function(name)
{
	if(!name)
		name = 'lm';
	
	//if (BX.SocNetLogDestination.popupWindow != null)
	//	BX.SocNetLogDestination.popupWindow.close();

	var lastId = false;
	for (var itemId in BX.SocNetLogDestination.obItemsSelected[name])
		lastId = itemId;

	if (lastId)
	{
		var type = BX.SocNetLogDestination.obItemsSelected[name][lastId];	
		BX.SocNetLogDestination.runUnSelectCallback(lastId, type, name);
	}
}

BX.SocNetLogDestination.selectFirstSearchItem = function(name)
{
	if(!name)
		name = 'lm';
	var item = BX.SocNetLogDestination.obSearchFirstElement;
	if (item != null)
	{
		BX.SocNetLogDestination.selectItem(name, null, null, item.id, item.type, true);
		BX.SocNetLogDestination.obSearchFirstElement = null;
	}
}
					
BX.SocNetLogDestination.getSelectedCount = function(name)
{
	if(!name)
		name = 'lm';

	var count = 0;
	for (var i in BX.SocNetLogDestination.obItemsSelected[name])
		count++;

	return count;
}
BX.SocNetLogDestination.getSelected = function(name)
{
	if(!name)
		name = 'lm';
	return BX.SocNetLogDestination.obItemsSelected[name];
}

BX.SocNetLogDestination.isOpenDialog = function()
{
	return BX.SocNetLogDestination.popupWindow != null? true: false;
}

BX.SocNetLogDestination.isOpenSearch = function()
{
	return BX.SocNetLogDestination.popupSearchWindow != null? true: false;
}

BX.SocNetLogDestination.closeDialog = function(silent)
{
	silent = silent === true? true: false;
	if (BX.SocNetLogDestination.popupWindow != null)
		if (silent)
			BX.SocNetLogDestination.popupWindow.destroy();
		else
			BX.SocNetLogDestination.popupWindow.close();
	return true;
}

BX.SocNetLogDestination.closeSearch = function()
{
	if (BX.SocNetLogDestination.popupSearchWindow != null)
		BX.SocNetLogDestination.popupSearchWindow.close();
	return true;
}


})();