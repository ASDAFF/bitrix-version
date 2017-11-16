function WriteBlogPost(val)
{
	if(val)
	{
		document.getElementById('microblog-link').style.display = "none";
		document.getElementById('microblog-form').style.display = "block";
		BX.onCustomEvent(BX('microblog-form'), 'onFormShow');
	}
	else
	{
		document.getElementById('microblog-link').style.display = "block";
		document.getElementById('microblog-form').style.display = "none";
	}
}

function changePostFormTab(type, bVisibleTabs)
{
	bVisibleTabs = !!bVisibleTabs;

	var tabsContainer = BX('feed-add-post-form-tab');
	var arTabs = BX.findChildren(tabsContainer, {'tag':'span', 'className': 'feed-add-post-form-link'}, false);
	var arrow = BX('feed-add-post-form-tab-arrow');
	var tabs = {}, bodies = {};

	if (
		bVisibleTabs
			&& BX("microblog-form")
			&& BX("microblog-form").style.display == "none"
		)
		WriteMicroblog(true);

	for (var i = 0; i < arTabs.length; i++)
	{
		var id = arTabs[i].getAttribute("id").replace("feed-add-post-form-tab-", "");
		tabs[id] = arTabs[i];
		bodies[id] = BX('feed-add-post-content-' + id);
	}

	// set active
	for (var ii in tabs)
	{
		if (ii != type)
		{
			BX.removeClass(tabs[ii], 'feed-add-post-form-link-active');
			if (!!bodies[ii])
				BX.adjust(bodies[ii], {style : {display : (type == 'file' || ii != 'message' ? "none" : "block")}});
		}
	}

	if (!!tabs[type])
	{
		BX.addClass(tabs[type], 'feed-add-post-form-link-active');
		var tabPosTab = BX.pos(tabs[type], true),
			tabPos = BX.pos(tabs[type].firstChild.nextSibling, true);
		arrow.style.left = (tabPosTab.left + 25) + 'px';		
		type = (!!bodies[type] ? type : 'message');
		if (!!bodies[type])
			BX.adjust(bodies[type], {style : {display : "block"}});
	}
	return false;
}

window.BXfpGratSelectCallback = function(item, type_user, name)
{
	BXfpGratMedalSelectCallback(item, 'grat');
}

window.BXfpMedalSelectCallback = function(item, type_user, name)
{
	BXfpGratMedalSelectCallback(item, 'medal');
}

window.BXfpGratMedalSelectCallback = function(item, type)
{
	if (type != 'grat')
		type = 'medal';

	var prefix = 'U';

	BX('feed-add-post-'+type+'-item').appendChild(
		BX.create("span", { 
			attrs : { 'data-id' : item.id }, 
			props : { className : "feed-add-post-"+type+" feed-add-post-destination-users" }, 
			children: [
				BX.create("input", { 
					attrs : { 'type' : 'hidden', 'name' : (type == 'grat' ? 'GRAT' : 'MEDAL')+'['+prefix+'][]', 'value' : item.id }
				}),
				BX.create("span", { 
					props : { 'className' : "feed-add-post-"+type+"-text" }, 
					html : item.name
				}),
				BX.create("span", { 
					props : { 'className' : "feed-add-post-del-but"}, 
					events : {
						'click' : function(e){
							BX.SocNetLogDestination.deleteItem(item.id, 'users', BXSocNetLogGratFormName);
							BX.PreventDefault(e)
						}, 
						'mouseover' : function(){
							BX.addClass(this.parentNode, 'feed-add-post-'+type+'-hover')
						}, 
						'mouseout' : function(){
							BX.removeClass(this.parentNode, 'feed-add-post-'+type+'-hover')
						}
					}
				})
			]
		})
	);

	BX('feed-add-post-'+type+'-input').value = '';
	BXfpGratMedalLinkName(type == 'grat' ? BXSocNetLogGratFormName : BXSocNetLogMedalFormName, type);
}

window.BXfpGratUnSelectCallback = function(item, type, search)
{
	BXfpGratMedalUnSelectCallback(item, 'grat');
}

window.BXfpMedalUnSelectCallback = function(item, type, search)
{
	BXfpGratMedalUnSelectCallback(item, 'medal');
}

window.BXfpGratMedalUnSelectCallback = function(item, type)
{
	var elements = BX.findChildren(BX('feed-add-post-'+type+'-item'), {attribute: {'data-id': ''+item.id+''}}, true);
	if (elements != null)
	{
		for (var j = 0; j < elements.length; j++)
			BX.remove(elements[j]);
	}
	BX('feed-add-post-'+type+'-input').value = '';
	BXfpGratMedalLinkName((type == 'grat' ? BXSocNetLogGratFormName : BXSocNetLogMedalFormName), type);
}

window.BXfpGratMedalLinkName = function(name, type)
{
	if (type != 'grat')
		type = 'medal';

	if (BX.SocNetLogDestination.getSelectedCount(name) <= 0)
		BX('bx-'+type+'-tag').innerHTML = BX.message("BX_FPGRATMEDAL_LINK_1");
	else
		BX('bx-'+type+'-tag').innerHTML = BX.message("BX_FPGRATMEDAL_LINK_2");
}

window.BXfpGratOpenDialogCallback = function()
{
	BX.style(BX('feed-add-post-grat-input-box'), 'display', 'inline-block');
	BX.style(BX('bx-grat-tag'), 'display', 'none');
	BX.focus(BX('feed-add-post-grat-input'));
}

window.BXfpGratCloseDialogCallback = function()
{
	if (!BX.SocNetLogDestination.isOpenSearch() && BX('feed-add-post-grat-input').value.length <= 0)
	{
		BX.style(BX('feed-add-post-grat-input-box'), 'display', 'none');
		BX.style(BX('bx-grat-tag'), 'display', 'inline-block');
		BXfpdDisableBackspace();
	}
}

window.BXfpGratCloseSearchCallback = function()
{
	if (!BX.SocNetLogDestination.isOpenSearch() && BX('feed-add-post-grat-input').value.length > 0)
	{
		BX.style(BX('feed-add-post-grat-input-box'), 'display', 'none');
		BX.style(BX('bx-grat-tag'), 'display', 'inline-block');
		BX('feed-add-post-grat-input').value = '';
		BXfpdDisableBackspace();
	}

}

window.BXfpGratSearchBefore = function(event)
{
	if (event.keyCode == 8 && BX('feed-add-post-grat-input').value.length <= 0)
	{
		BX.SocNetLogDestination.sendEvent = false;
		BX.SocNetLogDestination.deleteLastItem(BXSocNetLogGratFormName);
	}

	return true;
}
window.BXfpGratSearch = function(event)
{
	if(event.keyCode == 16 || event.keyCode == 17 || event.keyCode == 18)
		return false;

	if (event.keyCode == 13)
	{
		BX.SocNetLogDestination.selectFirstSearchItem(BXSocNetLogGratFormName);
		return true;
	}
	if (event.keyCode == 27)
	{
		BX('feed-add-post-grat-input').value = '';
		BX.style(BX('bx-grat-tag'), 'display', 'inline');
	}
	else
	{
		BX.SocNetLogDestination.search(BX('feed-add-post-grat-input').value, true, BXSocNetLogGratFormName);
	}

	if (!BX.SocNetLogDestination.isOpenDialog() && BX('feed-add-post-grat-input').value.length <= 0)
	{
		BX.SocNetLogDestination.openDialog(BXSocNetLogGratFormName);
	}
	else
	{
		if (BX.SocNetLogDestination.sendEvent && BX.SocNetLogDestination.isOpenDialog())
			BX.SocNetLogDestination.closeDialog();
	}
	if (event.keyCode == 8)
	{
		BX.SocNetLogDestination.sendEvent = true;
	}
	return true;
}

;(function(){

if (!!BX.SocNetGratSelector)
	return;

BX.SocNetGratSelector = 
{
	popupWindow: null,
	obWindowCloseIcon: {},
	sendEvent: true,
	obCallback: {},
	gratsContentElement: null,
	itemSelectedImageItem: {},
	itemSelectedInput: {},

	searchTimeout: null,
	obDepartmentEnable: {},
	obSonetgroupsEnable: {},
	obLastEnable: {},
	obWindowClass: {},
	obPathToAjax: {},
	obDepartmentLoad: {},
	obDepartmentSelectDisable: {},
	obItems: {},
	obItemsLast: {},
	obItemsSelected: {},

	obElementSearchInput: {},
	obElementBindMainPopup: {},
	obElementBindSearchPopup: {}
}

BX.SocNetGratSelector.init = function(arParams)
{
	if(!arParams.name)
		arParams.name = 'lm';

	BX.SocNetGratSelector.obCallback[arParams.name] = arParams.callback;
	BX.SocNetGratSelector.obWindowCloseIcon[arParams.name] = typeof (arParams.obWindowCloseIcon) == 'undefined' ? true : arParams.obWindowCloseIcon;
	BX.SocNetGratSelector.itemSelectedImageItem[arParams.name] = arParams.itemSelectedImageItem;
	BX.SocNetGratSelector.itemSelectedInput[arParams.name] = arParams.itemSelectedInput;	
}

BX.SocNetGratSelector.openDialog = function(name)
{
	if(!name)
		name = 'lm';

	if (BX.SocNetGratSelector.popupWindow != null)
	{
		BX.SocNetGratSelector.popupWindow.close();
		return false;
	}

	var arGratsItems = [];
	for (var i = 0; i < arGrats.length; i++)
	{
		arGratsItems[arGratsItems.length] = BX.create("span", {
			props: {
				className: 'feed-add-grat-box ' + arGrats[i].style
			},
			attrs: {
				'title': arGrats[i].title
			},
			events: {
				'click' : BX.delegate(function(e){
					BX.SocNetGratSelector.selectItem(name, this.code, this.style, this.title);
					BX.PreventDefault(e)
				}, arGrats[i])
			}
		});
	}
	var arGratsRows = [];
	var rownum = 1;
	for (var i = 0; i < arGratsItems.length; i++)
	{
		if (i >= arGratsItems.length/2)
			rownum = 2;

		if (arGratsRows[rownum] == null || arGratsRows[rownum] == 'undefined')
			arGratsRows[rownum] = BX.create("div", {
				props: {
					className: 'feed-add-grat-list-row'
				}
			});
		arGratsRows[rownum].appendChild(arGratsItems[i]);
	}

	BX.SocNetGratSelector.gratsContentElement = BX.create("div", {
		children: [
			BX.create("div", {
				props: {
					className: 'feed-add-grat-list-title'
				},
				html: BX.message('BLOG_GRAT_POPUP_TITLE')
			}),
			BX.create("div", {
				props: {
					className: 'feed-add-grat-list'
				},
				children: arGratsRows
			})
		]
	});

	BX.SocNetGratSelector.popupWindow = new BX.PopupWindow('BXSocNetGratSelector', BX('feed-add-post-grat-type-selected'), {
		autoHide: true,
		offsetLeft: 25,
		bindOptions: { forceBindPosition: true },
		closeByEsc: true,
		closeIcon : BX.SocNetGratSelector.obWindowCloseIcon[name] ? { 'top': '5px', 'right': '10px' } : false,
		events : {
			onPopupShow : function() {
				if(BX.SocNetGratSelector.sendEvent && BX.SocNetGratSelector.obCallback[name] && BX.SocNetGratSelector.obCallback[name].openDialog)
					BX.SocNetGratSelector.obCallback[name].openDialog();
			},
			onPopupClose : function() { 
				this.destroy();
			},
			onPopupDestroy : BX.proxy(function() { 
				BX.SocNetGratSelector.popupWindow = null; 
				if(BX.SocNetGratSelector.sendEvent && BX.SocNetGratSelector.obCallback[name] && BX.SocNetGratSelector.obCallback[name].closeDialog)
					BX.SocNetGratSelector.obCallback[name].closeDialog();
			}, this)
		},
		content: BX.SocNetGratSelector.gratsContentElement,
		angle : {
			position: "bottom",
			offset : 20
		}
	});
	BX.SocNetGratSelector.popupWindow.setAngle({});
	BX.SocNetGratSelector.popupWindow.show();
}

BX.SocNetGratSelector.selectItem = function(name, code, style, title)
{
	BX.SocNetGratSelector.itemSelectedImageItem[name].className = 'feed-add-grat-medal ' + style;
	BX.SocNetGratSelector.itemSelectedImageItem[name].title = title;
	BX.SocNetGratSelector.itemSelectedInput[name].value = code;
	BX.SocNetGratSelector.popupWindow.close();
}

})(); // one-time-use

WDFileDialogBranch = function(node)
{
	this.fileInput = node;
	this.controller = BX.findChild(node.form, {'className': 'wduf-selectdialog'}, true, false);
	BX.addCustomEvent('WDSelectFileDialogLoaded', BX.proxy(this.onWDSelectFileDialogLoaded, this));
	BX.bind(BX("feed-add-post-form-tab-file"), 'click', BX.proxy(this.onTabClick, this));
	BX.bind(this.fileInput, 'change', BX.proxy(this.onFileChange, this));
	BX.addCustomEvent(this.controller.parentNode, 'OnFileFromDialogSelected', BX.proxy(this.onDone, this));
	if (BX.browser.IsIE())
	{
		var res = BX.findChildren(node.form, {'className': 'feed-add-file-form-light-descript'}, true);
		if (!!res)
		{
			for (var ii = 0; ii < res.length; ii++)
				BX.hide(res[ii]);
		}

	}
}
WDFileDialogBranch.prototype = {
	onTabClick : function(e)
	{
		this.display = this.controller.style.display;
		BX.addCustomEvent('WDSelectFileDialogLoaded', BX.proxy(this.onWDSelectFileDialogLoadedRestore, this));
		BX.onCustomEvent(this.controller.parentNode, "WDLoadFormController");
		BX.unbind(BX("feed-add-post-form-tab-file"), 'click', BX.proxy(this.onTabClick, this));
	},
	onWDSelectFileDialogLoadedRestore : function(wdFD)
	{
		if (this.display == 'block')
			BX.fx.show(this.controller, 'fade', {time:0.2});
		else
			BX.fx.hide(this.controller, 'fade', {time:0.2});
		BX.addCustomEvent('WDSelectFileDialogLoaded', BX.proxy(this.onWDSelectFileDialogLoadedRestore, this));
	},
	onWDSelectFileDialogLoaded : function(wdFD)
	{
		BX.unbind(BX("feed-add-post-form-tab-file"), 'click', BX.proxy(this.onTabClick, this));
		this.wdFD = wdFD;
		if (!this.wdFD.hShowSelectDialog)
			this.wdFD.hShowSelectDialog = BX.proxy(this.wdFD.ShowSelectDialog, this.wdFD);
		this.onWDAgentLoaded();
		BX.bind(BX('D' + this.fileInput.id), 'click', this.wdFD.hShowSelectDialog);
	},
	onWDAgentLoaded : function()
	{
		if (!this.wdFD.agent){
			setTimeout(BX.delegate(this.onWDAgentLoaded, this), 100);
			return;
		}

		if (!this.loaded)
		{
			this.loaded = true;
			BX.loadScript('/bitrix/js/main/core/core_dd.js', BX.delegate(function() {
				var controller = BX.findChild(this.fileInput.form, { 'className': 'feed-add-file-form-light'}, true);
				if (!controller)
					return false;
				var dropbox = new BX.DD.dropFiles(controller);
				if (dropbox && dropbox.supported() && BX.ajax.FormData.isSupported()) {
					this.wdFD.agent.Init();
					BX.addCustomEvent(dropbox, 'dragEnter', BX.delegate(function() {BX.addClass(controller, 'feed-add-file-form-light-hover');}, this));
					BX.addCustomEvent(dropbox, 'dragLeave', BX.delegate(function() {BX.removeClass(controller, 'feed-add-file-form-light-hover');}, this));
					BX.addCustomEvent(dropbox, 'dropFiles', BX.delegate(this.onFileDrop, this));
				}
			}, this));
		}
	},
	onFileDrop : function(files)
	{
		if (!!this.wdFD)
		{
			this.wdFD.urlUpload = this.wdFD.urlUpload.replace('&random_folder=Y', '&dropped=Y');
			this.wdFD.agent.UploadDroppedFiles(files);
			this.onDone();
		}
	},
	onFileChange : function(e)
	{
		if (!!this.wdFD && !!this.wdFD.agent)
		{
			this.wdFD.urlUpload = this.wdFD.urlUpload.replace('&random_folder=Y', '&dropped=Y');
			this.wdFD.agent.fileInput = this.fileInput;
			if (!!this.wdFD.uploadDialog && !!this.wdFD.uploadDialog.__form)
			{
				this.wdFD.uploadDialog.__form.setAttribute(
					"action",
					this.wdFD.uploadDialog.__form.getAttribute("action").replace('random_folder=Y', 'dropped=Y'));
				if (!!this.wdFD.uploadDialog.__form["random_folder"])
					BX.remove(this.wdFD.uploadDialog.__form["random_folder"]);
				if (!!this.wdFD.uploadDialog.__form["SECTION_ID"])
					this.wdFD.uploadDialog.__form["SECTION_ID"].value = 0;
			}
			this.wdFD.agent.Init();
			this.wdFD.agent.hUploaderChange(e);
			this.onDone();
		}
	},

	onDone: function()
	{
		var node = BX('feed-add-post-content-file');
		if (!!node)
		{
			BX.fx.show(this.controller, 'fade', {time:0.2});
			node.id = 'feed-add-post-content-file1';
			setTimeout(function(){BX.hide(node);changePostFormTab('file', true);}, 300);
		}
	}
}