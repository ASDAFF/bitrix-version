;(function(window){
	if (window.LHEPostForm)
		return;
var repo = { controller : {}, handler : {}};
BX.addCustomEvent(window, "BFileDLoadFormControllerWasBound", function(obj) { repo.controller[obj.id] = true;});
BX.addCustomEvent(window, "WDLoadFormControllerInit", function(obj) { repo.controller[obj.CID] = obj; });
BX.addCustomEvent(window, "WDLoadFormControllerWasBound", function(obj) { repo.controller[obj.CID] = true; });
BX.addCustomEvent(window, "DiskDLoadFormControllerInit", function(obj) { repo.controller[obj.CID] = obj; });
BX.addCustomEvent(window, "DiskLoadFormControllerWasBound", function(obj) { repo.controller[obj.CID] = true; });
BX.addCustomEvent(window, 'OnEditorInitedBefore', function(editor) {
	if (repo.handler[editor.id])
	{
		editor.__lhe_flags = ['OnEditorInitedBefore'];
		if (repo.handler[editor.id]["params"] && repo.handler[editor.id]["params"]['LHEJsObjName']) // for custom templates
			window[repo.handler[editor.id].params['LHEJsObjName']] = editor;
		repo.handler[editor.id].OnEditorInitedBefore(editor);
	}
});
var OnCreateIframeAfter = function(editor){
	if (repo.handler[editor.id] && repo.handler[editor.id].editorIsLoaded != true)
	{
		repo.handler[editor.id].editorIsLoaded = true;
		repo.handler[editor.id].exec();
		BX.onCustomEvent(repo.handler[editor.id], 'OnEditorIsLoaded', [repo.handler[editor.id], editor]);
	}
};
BX.addCustomEvent(window, 'OnCreateIframeAfter', OnCreateIframeAfter);

BX.addCustomEvent(window, 'OnEditorInitedAfter', function(editor, forced){
	if (repo.handler[editor.id])
	{
		editor.__lhe_flags.push('OnEditorInitedAfter');
		repo.handler[editor.id].OnEditorInitedAfter(editor);

		if (repo.handler[editor.id].editorIsLoaded != true && forced && editor.sandbox && editor.sandbox.inited)
			OnCreateIframeAfter.apply(editor, [editor]);
	}
});
BX.util.object_search = function(needle, haystack)
{
	for(var i in haystack)
	{
		if (haystack.hasOwnProperty(i))
		{
			if (haystack[i] == needle)
				return true;
			else if (typeof haystack[i] == "object")
			{
				var result = BX.util.object_search_key(needle, haystack[i]);
				if (result !== false)
					return result;
			}
		}
	}
	return false;
};
var parserClass = function(bxTag, tag, additionalTags)
{
	additionalTags = (additionalTags && additionalTags.length > 0 ? additionalTags : []);
	if (typeof tag == "object" && tag.length > 0)
	{
		var res;
		while((res = tag.pop()) && res && tag.length > 0)
		{
			additionalTags.push(res);
		}
		tag = res;
	}
	additionalTags.push(tag);
	this.exist = true;
	this.bxTag = bxTag;
	this.tag = tag;
	this.tags = additionalTags;
	this.regexp = new RegExp("\\[(" + additionalTags.join("|") + ")=((?:\\s|\\S)*?)(?:\\s*?WIDTH=(\\d+)\\s*?HEIGHT=(\\d+))?\\]", "ig");
	this.code = '[' + tag + '=#ID##ADDITIONAL#]';
	this.wysiwyg = '<span style="color: #2067B0; border-bottom: 1px dashed #2067B0; margin:0 2px;" id="#ID#"#ADDITIONAL#>#NAME#</span>';
},
diskController = function(manager, id, params)
{
	this.CID = this.id = id;
	this.parser = (manager.parser['disk_file'] || null);
	this.params = params;
	this.node = BX('diskuf-selectdialog-' + id);
	this.handler = repo.controller[id]; // BX.Disk.UF
	this.manager = manager;
	this.eventNode = this.manager.eventNode;
	this.parserName = 'disk_file';
	this.prefixNode = 'disk-edit-attach';
	this.prefixHTMLNode = 'disk-attach-';
	this.props = {
		valueEditClassName : 'wd-inline-file',
		securityCID : 'disk-upload-cid'
	};
	this.storage = 'disk';
	this.fileToAttach = {};
	this.xmlToAttach = {};
	this.events = {
		onInit : 'DiskDLoadFormControllerInit',
		onShow : 'DiskLoadFormController',
		onBound : 'DiskLoadFormControllerWasBound'};
};
diskController.prototype = {
	parser : false,
	eventNode : null,
	values : {},
	initialized : false,
	functionsToExec : [],
	exec : function(func, args)
	{
		if (typeof func == "function")
			this.functionsToExec.push([func, args]);
		if (this.handler && this.handler !== true)
		{
			var res;
			while((res = this.functionsToExec.shift()) && res)
				res[0].apply(this, res[1]);
		}
	},
	init : function()
	{
		if (this.initialized !== true)
		{
			this.values = {};
			this.functionsToExec = [];
			this.initialized = true;
			this.bindMainEvents(this.manager);
			if (this.parser !== null)
			{
				this.bindEvents(this.manager);
				return this.initValues();
			}
		}
		return false;
	},
	initValues : function()
	{
		var values = BX.findChildren(this.node, {'className' : this.props.valueEditClassName}, true);
		if (values && values.length > 0)
		{
			this.exec(this.runCheckText);
			return true;
		}
		return false;
	},
	bindMainEvents : function(manager)
	{
		var __status = null;
		BX.addCustomEvent(manager.eventNode, 'onReinitializeBefore', BX.proxy(this.clean, this));
		// Pass event to show/hide controller
		BX.addCustomEvent(manager.eventNode, 'onShowControllers', BX.proxy(function(status) {
			__status = status;
			BX.onCustomEvent(manager.eventNode, this.events.onShow, [status]);
		}, this));

		if (!repo.controller[this.id]) // in case controller has not bound yet
		{
			var func = BX.delegate(function(obj) {
				if (obj["UID"] == this.id || obj["id"] == this.id)
				{
					if (__status === 'show' || __status === 'hide')
					{
						BX.onCustomEvent(manager.eventNode, this.events.onShow, [__status]);
						__status = null;
					}
					BX.removeCustomEvent(window, this.events.onBound, func);
				}
			}, this);
			BX.addCustomEvent(window, this.events.onBound, func);
		}
	},
	bindEvents : function(manager)
	{
		this._catchHandler = BX.delegate(function(handler)
		{
			BX.removeCustomEvent(this.eventNode, this.events.onInit, this._catchHandler);
			this.handler = handler;
			var node = BX.findChild(BX(manager.formID), {attr: {id: this.props.securityCID}}, true, false);
			if (node)
				node.value = this.handler.CID;
			this.exec();
			var func = BX.delegate(function() { BX.onCustomEvent(manager.eventNode, "onUploadsHasBeenChanged", arguments); }, this);
			BX.addCustomEvent(this.handler.agent, "onFileIsInited", func); // new uploader
			BX.addCustomEvent(this.handler.agent, "ChangeFileInput", func); // old uploader
			BX.onCustomEvent(manager.eventNode, 'onControllerInitialized', [this.id, handler]);
		}, this);
		if (this.handler != "object")
			BX.addCustomEvent(manager.eventNode, this.events.onInit, this._catchHandler);
		else
			this._catchHandler(this.handler);

		BX.addCustomEvent(manager.eventNode, 'OnFileUploadSuccess', BX.delegate(function(result, obj) {if (this.id == obj.CID || this.id == obj.id) { this.addFile(result, {usePostfix : true}); } }, this));
		BX.addCustomEvent(manager.eventNode, 'OnFileUploadRemove', BX.delegate(function(result, obj) { if (this.id == obj.CID || this.id == obj.id) { this.deleteFile(result, {usePostfix : true}); } }, this));
		BX.addCustomEvent(this, "onFileIsInText", BX.proxy(function(file, inText) { this.adjustFile(this.checkFile(file), inText) }, this));
	},
	addFile : function(result, params)
	{
		var file = this.checkFile(result.element_id, result, params);
		if (file)
		{
			setTimeout(BX.proxy(function(){
				this.bindFile(file);
				this.adjustFile(file, false);
			}, this), 100);
			BX.onCustomEvent(this.eventNode, 'onFileIsAdded', [file, this]);
		}
		return true;
	},
	checkFile : function(id, result)
	{
		id = '' + (typeof id == "object" ? id.id : id);
		if (typeof result == "object" &&
			result !== null && id &&
			result.element_name &&
			BX(result.place))
		{
			var data = {
					id : id,
					name : result.element_name,
					url : result.element_url,
					type : 'isnotimage/xyz',
					isImage : false,
					place : BX(result.place, true),
					xmlID : BX(result.place, true).getAttribute("bx-attach-xml-id"),
					fileID : BX(result.place, true).getAttribute("bx-attach-file-id")
				},
				preview;
			if (/(\.png|\.jpg|\.jpeg|\.gif|\.bmp)$/i.test(result.element_name) &&
				(preview = BX.findChild(data.place, {'className': 'files-preview', 'tagName' : 'IMG'}, true, false)) && preview)
			{
				data.type = 'image/xyz';
				data.lowsrc = preview.src;
				data.element_url = data.src = preview.src.replace(/\Wwidth=(\d+)/, '').replace(/\Wheight=(\d+)/, '');
				data.isImage = true;
				data.width = parseInt(preview.getAttribute("data-bx-full-width"));
				data.height = parseInt(preview.getAttribute("data-bx-full-height"));
			}
			if (data.xmlID)
				this.xmlToAttach[data.xmlID + ''] = id;
			if (data.fileID)
				this.fileToAttach[data.fileID + ''] = id;

			this.values[id] = data;
		}
		return (this.values[id] || false);
	},
	bindFile : function(file)
	{
		var node = file.place;
		if (typeof file == "object" && node && !node.hasAttribute("bx-file-is-bound"))
		{
			var name_wrap = BX.findChild(node, {className: 'f-wrap'}, true, false),
				img_wrap = BX.findChild(node, {className: 'files-preview'}, true, false);
			if (name_wrap)
			{
				BX.bind(name_wrap, "click", BX.delegate(function(){this.insertFile(file.id);}, this));
				name_wrap.style.cursor = "pointer";
				name_wrap.title = BX.message('MPF_FILE');
			}
			if (img_wrap)
			{
				BX.bind(img_wrap, "click", BX.delegate(function(){this.insertFile(file.id);}, this));
			}
		}
	},
	adjustFile : function(file, inText)
	{
		var node = file.place;
		if (inText === true || inText === false)
		{
			if (!file.info)
				file.info = BX.findChild(file.place, {className: 'files-info'}, true, false);
			node = file.info;
			if (BX.type.isDomNode(node))
			{
				var id = 'check-in-text-' + file.id,
					button = BX(id),
					props = (inText === false ? {
						attrs : {
							'bx-file-is-in-text' : "N"
						},
						props : {
							className : 'insert-btn'
						},
						html : '<span class="insert-btn-text">' + BX.message("MPF_FILE_INSERT_IN_TEXT") + '</span>'
					} : {
						attrs : {
							'bx-file-is-in-text' : "Y"
						},
						props : {
							className : 'insert-text'
						},
						html : '<span class="insert-btn-text">' + BX.message("MPF_FILE_IN_TEXT") + '</span>'
					});
				if (!button)
				{
					props.attrs.id = id;
					props.events = {
						click : BX.proxy(function(){this.insertFile(file.id);}, this)
					};
					node.appendChild(BX.create('SPAN', props));
				}
				else
				{
					BX.adjust(button, props);
				}
			}
		}
	},
	insertFile : function(file)
	{
		BX.onCustomEvent(this.eventNode, 'onFileIsInserted', [this.checkFile(file), this]);
	},
	deleteFile : function(file, params)
	{
		file = this.checkFile(file, params);
		if (file)
		{
			BX.onCustomEvent(this.eventNode, 'onFileIsDeleted', [file, this]);
			this.values[file.id].place = null;
			delete this.values[file.id].place;
			this.values[file.id] = null;
			delete this.values[file.id];
			file = null;
			return true;
		}
		return false;
	},
	reinitValues : function(text, files) // when data needs to be generated
	{
		var id, node, data = {};
		while ((id = files.pop()) && id)
		{
			node = BX(this.prefixHTMLNode + id);
			node = (node ? (node.tagName == "A" ? node : BX.findChild(node, {tagName : "IMG"}, true)) : null);
			if (node)
			{
				data['E' + id] = {
					type: 'file',
					id: id,
					name: node.getAttribute("data-bx-title"),
					size: node.getAttribute("data-bx-size"),
					sizeInt: node.getAttribute("data-bx-size"),
					storage: 'disk',
					previewUrl: (node.tagName == "A" ? '' : node.getAttribute("data-bx-src")),
					fileId: node.getAttribute("bx-attach-file-id")
				};
				if (node.hasAttribute("bx-attach-xml-id"))
					data['E' + id]["xmlId"] = node.getAttribute("bx-attach-xml-id");
			}
		}
		this.handler.selectFile({}, {}, data);
		this.runCheckText();
	},
	runCheckText : function()
	{
		if (!this._checkText)
			this._checkText = BX.delegate(this.checkText, this);
		this.manager.exec(this._checkText);
	},
	checkText : function()
	{
		var
			text1, text = this.manager.getContent(),
			needToReparse = [], reg, ii;
		if (text != '')
		{
			text1 = text;
			for (ii in this.xmlToAttach)
			{
				if (this.xmlToAttach.hasOwnProperty(ii))
				{
					text = text.
						replace(
							new RegExp('\\&\\#91\\;DOCUMENT ID=(' + ii + ')([WIDTHHEIGHT=0-9 ]*)\\&\\#93\\;','gim'),
							'[' + this.parser["tag"] + '=' + this.xmlToAttach[ii] + "$2]").
						replace(
							new RegExp('\\[DOCUMENT ID=(' + ii + ')([WIDTHHEIGHT=0-9 ]*)\\]','gim'),
							'[' + this.parser["tag"] + '=' + this.xmlToAttach[ii] + "$2]");
				}
			}
			for (ii in this.fileToAttach)
			{
				if (this.fileToAttach.hasOwnProperty(ii))
				{
					text = text.
						replace(
							new RegExp('\\&\\#91\\;' + this.parser["tag"] + '=(' + ii + ')([WIDTHHEIGHT=0-9 ]*)\\&\\#93\\;','gim'),
							'[' + this.parser["tag"] + '=' + this.fileToAttach[ii] + "$2]").
						replace(
							new RegExp('\\[' + this.parser["tag"] + '=(' + ii + ')([WIDTHHEIGHT=0-9 ]*)\\]','gim'),
							'[' + this.parser["tag"] + '=' + this.fileToAttach[ii] + "$2]");
				}
			}
			reg = new RegExp('(?:\\&\\#91\\;)(' + this.parser["tags"].join("|") + ')=([a-z=0-9 ]+)(?:\\&\\#93\\;)','gim');
			if (reg.test(text))
			{
				for (ii in this.values)
				{
					if (this.values.hasOwnProperty(ii))
					{
						needToReparse.push(ii);
					}
				}
				if (needToReparse.length > 0)
				{
					reg = new RegExp('(?:\\&\\#91\\;|\\[)(' + this.parser["tags"].join("|") + ')=(' + needToReparse.join("|") + ')([WIDTHHEIGHT=0-9 ]*)(?:\\&\\#93\\;|\\])','gim');
					if (reg.test(text))
						text = text.replace(reg, BX.delegate(function(str, tagName, id, add) { return '[' + tagName + '=' + id + add + ']'; }, this));
				}
			}

			if (text1 != text)
				BX.onCustomEvent(this.eventNode, 'onFileIsDetected', [text, this]);
		}
		return text;
	},
	clean : function()
	{
		if (this.handler && this.handler.values)
		{
			var res, files, ii, form = BX(this.manager.formID);
			while ((res = this.handler.values.pop()) && res)
			{
				BX.remove(res);
			}
			files = BX.findChildren(form, {
				tagName : "INPUT",
				attribute : {
					name : this.handler.params.controlName
				}
			}, true);
			if (files)
			{
				for (ii = 0; ii < files.length; ii++)
				{
					BX.remove(files[ii]);
				}
			}
		}
	},
	reinit : function(text, data)
	{
		var files = [], name, ii;
		for (name in data)
		{
			if (data.hasOwnProperty(name))
			{
				if (data[name]['USER_TYPE_ID'] == this.parserName && data[name]['VALUE'])
				{
					for (ii in data[name]['VALUE'])
					{
						if (data[name]['VALUE'].hasOwnProperty(ii))
						{
							files.push(data[name]['VALUE'][ii]);
						}
					}
				}
			}
		}
		if (files.length > 0)
		{
			this.exec(this.reinitValues, [text, files]);
			return true;
		}
		return false;
	}
};
var webdavController = function(manager, id, params)
{
	webdavController.superclass.constructor.apply(this, arguments);
	this.parser = (manager.parser['webdav_element'] || null);
	this.node = BX('wduf-selectdialog-' + id);
	this.manager = manager;
	this.parserName = 'webdav_element';
	this.prefixNode = 'wd-doc';
	this.prefixHTMLNode = 'wdif-doc-';
	this.storage = 'webdav';
	this.events = {
		onInit : 'WDLoadFormControllerInit',
		onShow : 'WDLoadFormController',
		onBound : 'WDLoadFormControllerWasBound'};
};
BX.extend(webdavController, diskController);
webdavController.prototype.reinitValues = function(text, files) // when data needs to be generated
{
	var id, node, data = {};
	this.waitAnswerFromServer = [];
	while ((id = files.pop()) && id)
	{
		node = BX(this.prefixHTMLNode + id);
		node = (node ? (node.tagName == "A" ? node : BX.findChild(node, {tagName : "IMG"}, true)) : null);
		if (node)
		{
			data['E' + id] = {
				type: 'file',
				id: id,
				name: node.getAttribute("alt"),
				storage: 'webdav',
				size: node.getAttribute("data-bx-size"),
				sizeInt: 1,
				ext: '',
				link: node.getAttribute("data-bx-document")
			};
			if (node.hasAttribute("bx-attach-xml-id"))
				data['E' + id]["xmlId"] = node.getAttribute("bx-attach-xml-id");
			this.waitAnswerFromServer.push(id);
		}
	}
	if (this.waitAnswerFromServer.length > 0)
	{
		if (!this._defferCheckText)
			this._defferCheckText = BX.delegate(this.defferCheckText, this);
		BX.addCustomEvent(this.eventNode, 'OnFileUploadSuccess', this._defferCheckText);
		this.handler.WDFD_SelectFile({}, {}, data);
	}
};
webdavController.prototype.defferCheckText = function(result)
{
	var key = BX.util.array_search(result.element_id, this.waitAnswerFromServer);
	if (key >= 0)
	{
		this.runCheckText();
		this.waitAnswerFromServer = BX.util.deleteFromArray(this.waitAnswerFromServer, key);
	}
	if (this.waitAnswerFromServer.length <= 0)
		BX.removeCustomEvent(this.eventNode, 'OnFileUploadSuccess', this._defferCheckText);
};
var fileController = function(manager, id, params)
{
	fileController.superclass.constructor.apply(this, arguments);
	this.parser = (manager.parser['file'] ? manager.parser['file'] : (manager.parser['postimage']['exist'] ? manager.parser['postimage'] : null));
	this.postfix = (params['postfix'] || '');
	this.node = BX('file-selectdialog-' + id);
	this.parserName = 'file';
	this.prefixNode = 'wd-doc';
	this.prefixHTMLNode = 'file-doc-';
	this.props = {
		valueEditClassName : 'file-inline-file',
		securityCID : 'upload-cid'
	};
	this.storage = 'bfile';
	this.events = {
		onInit : 'BFileDLoadFormControllerInit',
		onShow : 'BFileDLoadFormController',
		onBound : 'BFileDLoadFormControllerWasBound'};
};
BX.extend(fileController, diskController);
fileController.prototype.initValues = function(result)
{
	var values;
	if (result !== true)
	{
		values = BX.findChildren(this.node, {'className' : this.props.valueEditClassName}, true);
		if (values && values.length > 1)
		{
			this.exec(this.initValues, [true]);
			return true;
		}
		return false;
	}
	values = (this.handler.agent.values || []);
	var
		file, node, data, id,
		ID = {},
		url = BX.util.remove_url_param(document.location.href, ["mfi_mode", "fileID", "cid", "sessid"]);
	url = (url.indexOf('#') >= 0 ? url.substr(0, url.indexOf('#')) : url);
	url += (url.indexOf("?") > 0 ? '&' : '?') + 'mfi_mode=down&cid='+this.handler.CID + '&sessid='+BX.bitrix_sessid();
	for (var ii = 0; ii < values.length; ii++)
	{
		id = parseInt(values[ii].getAttribute("id").replace(this.prefixNode, ""));
		if (ID['id' + id])
			continue;
		ID['id' + id] = "Y";
		if (id > 0)
		{
			node = BX.findChild(values[ii], {'className': 'f-wrap'}, true, false);
			if(!node)
				continue;
			data = {
				element_id : id,
				element_name : node.innerHTML,
				parser : this.parser.bxTag,
				storage : 'bfile',
				element_url : (url + '&fileID=' + id)
			};
			file = this.addFile(data, {usePostfix : true, hasPreview : false});
		}
	}
	this.runCheckText();
	return true;
};
fileController.prototype.checkFile = function(id, result, param)
{
	id = '' + (typeof id == "object" ? id.id : id);
	id = id + (param && param["usePostfix"] === true ? this.postfix : '');
	if (typeof result == "object" &&
		result !== null && id &&
		result.element_name &&
		BX(this.prefixNode + result.element_id, true))
	{
		var data = {
				id : id,
				name : result.element_name,
				url : result.element_url,
				type : 'isnotimage/xyz',
				isImage : false,
				place : BX(this.prefixNode + result.element_id, true)
			},
			preview;
		if ((result['element_type'] && result['element_type'].indexOf('image/') === 0 || /(\.png|\.jpg|\.jpeg|\.gif|\.bmp)$/i.test(result.element_name)) &&
			((preview = BX.findChild(data.place, {'tagName' : 'IMG'}, true, false)) && preview || (param && param["hasPreview"] === false)))
		{
			data.type = 'image/xyz';
			data.src = (result['element_thumbnail'] || result['element_url']);
			data.isImage = true;
			data.hasPreview = false;
			data.lowsrc = '';
			data.width = '';
			data.height = '';
			if (BX(preview))
			{
				data.hasPreview = true;
				data.lowsrc = (result['element_thumbnail'] || preview['src']);
				data.width = parseInt(preview.getAttribute("data-bx-full-width"));
				data.height = parseInt(preview.getAttribute("data-bx-full-height"));
			}
		}
		else if (this.parser.bxTag == 'postimage')
		{
			return false;
		}

		this.values[id] = data;
	}
	return (this.values[id] || false);
};
fileController.prototype.bindFile = function(file)
{
	var node = (file && file['place'] ? file['place'] : null);
	if (typeof file == "object" && node && !node.hasAttribute("bx-file-is-bound"))
	{
		if (file.isImage && file.hasPreview)
		{
			var
				img_title = BX.findChild(node, {className: 'feed-add-img-title'}, true, false),
				img_wrap = BX.findChild(node, {className: 'feed-add-img-wrap'}, true, false);
			if (img_wrap)
			{
				BX.bind(img_wrap, "click", BX.proxy(function(){this.insertFile(file);}, this));
				img_wrap.style.cursor = "pointer";
				img_wrap.title = BX.message('MPF_IMAGE');
			}
			if (img_title)
			{
				BX.bind(img_title, "click", BX.delegate(function(){this.insertFile(file);}, this));
				img_title.style.cursor = "pointer";
				img_title.title = BX.message('MPF_IMAGE');
			}
		}
		else
			fileController.superclass.bindFile.apply(this, arguments);
	}
};

window.LHEPostForm = function(formID, params)
{
	this.params = params;
	this.formID = formID;
	this.oEditorId = params['LHEJsObjId'];
	this.__divId = (params['LHEJsObjName'] || params['LHEJsObjId']);
	repo.handler[this.oEditorId] = this;
	this.oEditor = LHEPostForm.getEditor(this.oEditorId);

	this.eventNode = BX('div' + this.__divId);
	BX.addCustomEvent(this.eventNode, 'OnShowLHE', BX.delegate(this.OnShowLHE, this));
	BX.addCustomEvent(this.eventNode, 'OnButtonClick', BX.delegate(this.OnButtonClick, this));
	BX.addCustomEvent(this.eventNode, 'OnAfterShowLHE', function(status, handler) {
		if (handler.oEditor && handler.oEditor["AllowBeforeUnloadHandler"])
			handler.oEditor.AllowBeforeUnloadHandler();
		if (handler.monitoringWakeUp === true)
			handler.monitoringStart();
	});
	BX.addCustomEvent(this.eventNode, 'OnAfterHideLHE', function(status, handler) {
		handler.monitoringWakeUp = handler.monitoringStop();
		if (handler.oEditor && handler.oEditor["DenyBeforeUnloadHandler"])
			handler.oEditor.DenyBeforeUnloadHandler();
	});
	this.initParsers(params);
	this.initFiles(formID, params);
	this.inited = true;
	BX.addCustomEvent(BX(this.formID), 'onAutoSavePrepare', function(ob) { ob.FORM.setAttribute("bx-lhe-autosave-prepared", "Y"); });

	BX.onCustomEvent(this, "onInitialized", [this, formID, params, this.parsers]);
	BX.onCustomEvent(this.eventNode, "onInitialized", [this, formID, params, this.parsers]);
	if (this.oEditor && this.oEditor.inited && !this.oEditor['__lhe_flags'])
	{
		BX.onCustomEvent(this.oEditor, "OnEditorInitedBefore", [this.oEditor]);
		BX.onCustomEvent(this.oEditor, "OnEditorInitedAfter", [this.oEditor, true]);
	}
};
window.LHEPostForm.prototype = {
	editorIsLoaded : false,
	arFiles : {},
	parser : {},
	controllers : {},
	exec : function(func, args)
	{
		this.functionsToExec = (this.functionsToExec || []);
		if (typeof func == "function")
			this.functionsToExec.push([func, args]);
		if (this.editorIsLoaded == true)
		{
			var res;
			while((res = this.functionsToExec.shift()) && res)
				res[0].apply(this, res[1]);
		}
	},
	initParsers : function(params)
	{
		this.parser = {
			postimage : {
				exist : false,
				bxTag : 'postimage',
				tag : "IMG ID",
				tags : ["IMG ID"],
				regexp : /\[(IMG ID)=((?:\s|\S)*?)(?:\s*?WIDTH=(\d+)\s*?HEIGHT=(\d+))?\]/ig,
				code : '[IMG ID=#ID##ADDITIONAL#]',
				wysiwyg : '<img id="#ID#" src="' + '#SRC#" lowsrc="' + '#LOWSRC#" title=""#ADDITIONAL# />'
			}
		};
		var parsers = (params["parsers"] ? params["parsers"] : {});
		for (var ii in parsers)
		{
			if (parsers.hasOwnProperty(ii) && /[a-z]/gi.test(ii+''))
			{
				this.parser[ii] = new parserClass(ii, parsers[ii]);
			}
		}
		if (BX.util.object_search('UploadImage', parsers))
		{
			this.parser['postimage']['exist'] = true;
			if (typeof params['arSize'] == "object")
			{
				var style = '';
				if (params['arSize']['width'])
					style += 'max-width:' + params['arSize']['width'] + 'px;';
				if (params['arSize']['height'])
					style += 'max-height:' + params['arSize']['height'] + 'px;';
				if (style !== '')
					this.parser['postimage']['wysiwyg'] = this.parser['postimage']['wysiwyg'].replace('#ADDITIONAL#', 'style="' + style + '" #ADDITIONAL#');
			}
		}
	},
	initFiles : function(formID, params)
	{
		this.arFiles = {};
		this.controllers = {
			common : {
				postfix : "",
				storage : "bfile",
				parser : "postimage",
				node : window,
				obj : null,
				init : false
			}
		};
		if (!params["CID"] || typeof params["CID"] !== "object")
			return;

		BX.addCustomEvent(this.eventNode, 'onFileIsAdded', BX.delegate(this.OnFileUploadSuccess, this));
		BX.addCustomEvent(this.eventNode, 'onFileIsDeleted', BX.delegate(this.OnFileUploadRemove, this));
		BX.addCustomEvent(this.eventNode, 'onFileIsDetected', BX.delegate(this.setContent, this));
		BX.addCustomEvent(this.eventNode, 'onFileIsInserted', BX.delegate(this.insertFile, this));

		var parser, cid, init;

		for (cid in params["CID"])
		{
			if (params["CID"].hasOwnProperty(cid))
			{
				parser = params["CID"][cid]["parser"];
				if (parser == 'disk_file')
					this.controllers[cid] = new diskController(this, cid, params["CID"][cid]);
				else if (parser == 'webdav_element')
					this.controllers[cid] = new webdavController(this, cid, params["CID"][cid]);
				else if (parser == 'file')
					this.controllers[cid] = new fileController(this, cid, params["CID"][cid]);
				if (this.controllers[cid] && this.controllers[cid].init() && !init)
					init = true;
			}
		}

		BX.ready(
			BX.delegate(
				function()
				{
					BX.bind(BX('bx-b-uploadfile-' + formID), 'click', BX.proxy(this.controllerInit, this));
					if (init)
						this.controllerInit('show');
				},
				this
			)
		);
	},
	controllerInit : function(status)
	{
		this.controllerInitStatus = (status == 'show' || status == 'hide' ? status : (this.controllerInitStatus == 'show' ? 'hide' : 'show'));
		BX.onCustomEvent(this.eventNode, "onShowControllers", [this.controllerInitStatus]);
	},
	getContent : function()
	{
		return (this.oEditor ? this.oEditor.GetContent() : '');
	},
	setContent : function(text)
	{
		if (this.oEditor)
			this.oEditor.SetContent(text);
	},
	OnFileUploadSuccess : function(file, controller, uploaded)
	{
		if (this.controllers[controller.id])
		{
			var id = controller.parser.bxTag + file.id;
			this.arFiles[id] = (this.arFiles[id] || []);
			this.arFiles[id].push(controller.id);
			if (uploaded === true && file.isImage && this.insertImageAfterUpload)
			{
				if (!this._insertFile)
					this._insertFile = BX.delegate(this.insertFile, this);
				this.exec(this._insertFile, arguments);
			}
		}
	},
	OnFileUploadRemove : function(file, controller)
	{
		if (this.controllers[controller.id])
		{
			var id = controller.parser.bxTag + file.id;
			if (this.arFiles[id])
			{
				var key = BX.util.array_search(controller.id, this.arFiles[id]);
				this.arFiles[id] = BX.util.deleteFromArray(this.arFiles[id], key);
				if (!this.arFiles[id] || this.arFiles[id].length <= 0)
				{
					this.arFiles[id] = null;
					delete this.arFiles[id];
					if (!this._deleteFile)
						this._deleteFile = BX.delegate(this.deleteFile, this);
					this.exec(this._deleteFile, arguments);
				}
			}
		}
	},

	showPanelEditor : function(show, save)
	{
		if (show == undefined)
			show = !this.oEditor.toolbar.IsShown();

		this.params.showPanelEditor = show;
		var
			button = BX('lhe_button_editor_' + this.formID),
			panelClose = BX('panel-close' + this.__divId);

		if (panelClose)
		{
			this.oEditor.dom.cont.appendChild(panelClose);
		}

		if(show)
		{
			this.oEditor.dom.toolbarCont.style.opacity = 'inherit';
			this.oEditor.toolbar.Show();

			if (button)
				BX.addClass(button, 'feed-add-post-form-btn-active');

			if (panelClose)
				panelClose.style.display = '';
		}
		else
		{
			this.oEditor.toolbar.Hide();

			if (button)
				BX.removeClass(button, 'feed-add-post-form-btn-active');

			if (panelClose)
				panelClose.style.display = 'none';
		}
		if (save !== false)
			BX.userOptions.save('main.post.form', 'postEdit', 'showBBCode', show ? "Y" : "N");
	},
	monitoring : {
		interval : null,
		text : '',
		savedText : '',
		files : [],
		savedFiles : []
	},
	monitoringStart : function()
	{
		if (this.monitoring.interval === null)
		{
			if (!this._monitoringStart)
			{
				this._monitoringStart = BX.delegate(this.checkFilesInText, this);
				BX.addCustomEvent(this.oEditor, 'OnContentChanged', BX.proxy(function(text) {
					this.monitoring.text = text;
				}, this));
			}
			this.monitoring.interval = setInterval(this._monitoringStart, 1000);
		}
	},
	monitoringStop : function()
	{
		var ret = (this.monitoring.interval !== null);
		if (this.monitoring.interval !== null)
			clearInterval(this.monitoring.interval);
		this.monitoring.interval = null;
		return ret;
	},
	monitoringSetStatus: function(parser, file, inText)
	{
		if (this.arFiles[parser + file])
		{
			var cid;
			for (var ii = 0; ii < this.arFiles[parser + file].length; ii++)
			{
				this.monitoring.files.push(parser + file);
				cid = this.arFiles[parser + file][ii];
				BX.onCustomEvent(this.controllers[cid], "onFileIsInText", [file, inText]);
			}
		}
	},
	checkFilesInText: function()
	{
		if (this.monitoring.text !== this.monitoring.savedText)
		{
			this.monitoring.savedText = this.monitoring.text;
			this.monitoring.files = [];
			var text = this.monitoring.savedText,
				ii, closure = function(a,parser) {return function(str, tagName, id) { a.monitoring.files.push([parser, id].join('/')); } };
			for (ii in this.parser)
			{
				if (this.parser.hasOwnProperty(ii))
				{
					if (!this.parser[ii]["checkFilesInText"])
					{
						this.parser[ii]["checkFilesInText"] = closure(this, ii);
					}
					text.replace(
						this.parser[ii]["regexp"],
						this.parser[ii]["checkFilesInText"]
					);
				}
			}
			if (this.monitoring.savedFiles.join(',') != this.monitoring.files.join(','))
			{

				var files = this.monitoring.files.join('|') + '|', id;
				for (ii = 0; ii < this.monitoring.savedFiles.length; ii++)
				{
					id = this.monitoring.savedFiles[ii];
					if (files.indexOf(id + '|') >= 0)
						files = files.replace(id + '|', '');
					else
					{
						id = id.split('/');
						this.monitoringSetStatus(id[0], id[1], false);
					}
				}
				files = files.substring(0, files.length - 1).split('|');
				for (ii = 0; ii < files.length; ii++)
				{
					id = id = files[ii].split('/');
					this.monitoringSetStatus(id[0], id[1], true);
				}
			}
			this.monitoring.savedFiles = this.monitoring.files;
			if (this.monitoring.savedFiles.length <= 0)
				this.monitoringStop();
		}
	},
	checkFile : function(file, controller) // or fileId and controller or fileId and parser
	{
		var r = false;
		if (typeof file == 'string')
		{
			var parser = (typeof controller == 'string' ? controller : controller.parser);

			if (!!this.arFiles[parser + file])
			{
				var fileController = this.arFiles[parser + file][0];
				controller = this.controllers[fileController];
				r = {
					file : controller.values[file],
					controller : controller
				};
			}
		}
		else if (this.controllers[controller.id])
		{
			r = {
				file : file,
				controller : controller
			};
		}
		return r
	},
	insertFile : function(file, controller)
	{
		var editor = this.oEditor;
		if (editor && file)
		{
			var
				fileID = file['id'],
				params = '',
				parser = controller.parser,
				editorMode = editor.GetViewMode(),
				pattern = this.parser[parser.bxTag][editorMode];

			if (file['isImage'])
			{
				pattern = (editorMode == "wysiwyg" ? this.parser["postimage"][editorMode] : pattern);
				if (file.width > 0 && file.height > 0 && editor.sEditorMode == "html" )
				{
					params = ' style="width:' + file.width + 'px;height:' + file.height + 'px;" onload="this.style=\' \'"';
				}
			}

			if(editorMode == 'wysiwyg') // WYSIWYG
			{
				editor.InsertHtml(pattern.
					replace("#ID#", editor.SetBxTag(false, {'tag': parser.bxTag, params: {'value' : fileID}})).
					replace("#SRC#", file.src).replace("#URL#", file.url).
					replace("#LOWSRC#", (file.lowsrc || '')).
					replace("#NAME#", file.name).replace("#ADDITIONAL#", params) + '<span>&nbsp;</span>'
				);
				setTimeout(BX.delegate(editor.AutoResizeSceleton, editor), 500);
				setTimeout(BX.delegate(editor.AutoResizeSceleton, editor), 1000);
			}
			else if (editorMode == 'code' && editor.bbCode) // BB Codes
			{
				editor.textareaView.Focus();
				editor.textareaView.WrapWith(false, false, pattern.replace("#ID#", fileID).replace("#ADDITIONAL#", ""));
			}
			this.monitoringSetStatus(parser.bxTag, file.id, true);
			this.monitoringStart();
		}
	},

	deleteFile: function(file, controller)
	{
		var
			editor = this.oEditor,
			parser = controller.parser,
			id = file.id,
			content = editor.GetContent();

		if (parser && content.indexOf('=' + id) >= 0)
		{
			if(editor.GetViewMode() == 'wysiwyg') // WYSIWYG
			{
				var doc = editor.GetIframeDoc(), ii, n;
				for (ii in editor["bxTags"])
				{
					if (editor["bxTags"].hasOwnProperty(ii))
					{
						if (typeof editor.bxTags[ii] == "object" &&
							editor.bxTags[ii]["params"] &&
							editor.bxTags[ii]["params"]["value"] == file.id)
						{
							n = doc.getElementById(ii);
							if (n)
								n.parentNode.removeChild(n);
						}
					}
				}
				editor.SaveContent();
			}
			else
			{
				content = content.replace(parser.regexp, function(str, tagName, id) { return (id == file.id ? '' : str); } );
				editor.SetContent(content);
				editor.Focus();
			}
			this.monitoringSetStatus(parser.bxTag, file.id, false);
		}
	},
	reinit : function(text, data)
	{
		BX.onCustomEvent(this.eventNode, "onReinitializeBefore", [this, text, data]);
		this.arFiles = {};

		delete this.monitoringWakeUp;
		this.monitoringStop();
		this.oEditor.CheckAndReInit(text || '');

		BX.onCustomEvent(this.eventNode, "onReinitialize", [this, text, data]);
		var cid, needsToInit = false;
		for (cid in this.controllers)
		{
			if (this.controllers.hasOwnProperty(cid))
			{
				if (this.controllers[cid]['init'] && this.controllers[cid].reinit(text, data))
					needsToInit = true;
			}
		}

		this.controllerInit((needsToInit ? 'show' : 'hide'));

		if (this.params["~height"])
		{
			this.oEditor.SetConfigHeight(this.params["~height"]);
			this.oEditor.ResizeSceleton();
		}
	},
	Parse : function(parser, content, editor)
	{
		var
			arParser = this.parser[parser],
			obj = this;

		if (arParser)
		{
			content = content.replace(
				arParser.regexp,
				function(str, tagName, id, width, height)
				{
					var file = obj.checkFile(id, parser);
					if (file && (file = file.file) && file)
					{
						var
							strAdditional = "",
							template = (file.isImage ? obj.parser.postimage.wysiwyg : arParser.wysiwyg);
						obj.monitoringStart();

						if (file.isImage)
						{
							width = parseInt(width);
							height = parseInt(height);

							strAdditional = ((width && height) ?
								(" width=\"" + width + "\" height=\"" + height + "\"") : "");

							if (strAdditional === "" && file["width"] > 0 && file["height"] > 0)
							{
								strAdditional = ' style="width:' + file["width"] + 'px;height:' + file["height"] + 'px;" onload="this.style=\' \'"';
							}
						}

						return template.
							replace("#ID#", editor.SetBxTag(false, {tag: parser, params: {value : id}})).
							replace("#NAME#", file.name).
							replace("#SRC#", file.src).
							replace("#LOWSRC#", file.lowsrc).
							replace("#ADDITIONAL#", strAdditional).
							replace("#WIDTH#", parseInt(width)).
							replace("#HEIGHT#", parseInt(height));
					}
					return str;
				}
			)
		}
		return content;
	},

	/**
	 * @return {string}
	 */
	Unparse: function(bxTag, oNode/*, editor*/)
	{
		var res = "", parser = bxTag.tag;
		if (this.parser[parser])
		{
			var
				width = parseInt(oNode.node.hasAttribute("width") ? oNode.node.getAttribute("width") : 0),
				height = parseInt(oNode.node.hasAttribute("height") ? oNode.node.getAttribute("height") : 0),
				strSize = "";

			if (width > 0 && height > 0)
			{
				strSize = ' WIDTH=' + width + ' HEIGHT=' + height;
			}

			res = this.parser[parser]["code"].
				replace("#ID#", bxTag.params.value).
				replace("#ADDITIONAL#", strSize).
				replace("#WIDTH#", width).
				replace("#HEIGHT#", height);
		}

		return res;
	},

	OnShowLHE : function(show, editor, setFocus)
	{
		var lheName = this.__divId;
		show = (show === false ? false : (show === 'hide' ? 'hide' : (show === 'justShow' ? 'justShow' : true)));

		this.oEditor = (this.oEditor || LHEPostForm.getEditor(this.oEditorId));
		if (!this.oEditor)
			return;
		this.oEditor.Init();

		var
			micro = BX('micro' + lheName),
			div = this.eventNode;

		if (micro)
		{
			micro.style.display = ((show === true || show === 'justShow') ? "none" : "block");
		}

		if (show == 'hide')
		{
			BX.onCustomEvent(this.eventNode, 'OnBeforeHideLHE', [show, this]);
			if (this.eventNode.style.display == "none")
			{
				BX.onCustomEvent(this.eventNode, 'OnAfterHideLHE', [show, this]);
			}
			else
			{
				(new BX["easing"]({
					duration : 200,
					start : { opacity: 100, height : this.eventNode.scrollHeight},
					finish : { opacity : 0, height : 20},
					transition : BX.easing.makeEaseOut(BX.easing.transitions.quad),
					step : function(state)
					{
						div.style.height = state.height + "px";
						div.style.opacity = state.opacity / 100;
					},
					complete : BX.proxy(function()
					{
						this.eventNode.style.cssText = "";
						this.eventNode.style.display = "none";
						BX.onCustomEvent(div, 'OnAfterHideLHE', [show, this]);
					}, this)
				})).animate();
			}
		}
		else if (show)
		{
			BX.onCustomEvent(this.eventNode, 'OnBeforeShowLHE', [show, this]);
			if (show == "justShow")
			{
				this.eventNode.style.display = "block";
				BX.onCustomEvent(this.eventNode, 'OnAfterShowLHE', [show, this]);
				if (setFocus !== false)
					this.oEditor.Focus();
			}
			else if (this.eventNode.style.display == "block")
			{
				BX.onCustomEvent(this.eventNode, 'OnAfterShowLHE', [show, this]);
				if (setFocus !== false)
					this.oEditor.Focus();
			}
			else
			{
				BX.adjust(this.eventNode, {style:{display:"block", overflow:"hidden", height:"20px", opacity:0.1}});
				(new BX["easing"]({
					duration : 200,
					start : { opacity : 10, height : 20 },
					finish : { opacity: 100, height : div.scrollHeight},
					transition : BX["easing"].makeEaseOut(BX.easing.transitions.quad),
					step : function(state)
					{
						div.style.height = state.height + "px";
						div.style.opacity = state.opacity / 100;
					},
					complete : BX.proxy(function()
					{
						BX.onCustomEvent(div, 'OnAfterShowLHE', [show, this]);
						this.oEditor.Focus();
						this.eventNode.style.cssText = "";
					}, this)
				})).animate();
			}
		}
		else
		{
			BX.onCustomEvent(this.eventNode, 'OnBeforeHideLHE', [show, this]);
			this.eventNode.style.display = "none";
			BX.onCustomEvent(this.eventNode, 'OnAfterHideLHE', [show, this]);
		}
	},

	OnButtonClick : function(type)
	{
		if(type != 'cancel')
		{
			BX.onCustomEvent(this.eventNode, 'OnClickSubmit', [this]);
		}
		else
		{
			BX.onCustomEvent(this.eventNode, 'OnClickCancel', [this]);
			BX.onCustomEvent(this.eventNode, 'OnShowLHE', ['hide']);
		}
	},
	OnEditorInitedBefore : function(editor)
	{
		var _this = this;
		this.oEditor = editor;
		editor.formID = this.formID;
		if (this.params)
			this.params["~height"] = editor.config["height"];
		if (this.params && this.params['ctrlEnterHandler'])
		{
			BX.addCustomEvent(editor, 'OnCtrlEnter', function() {
				editor.SaveContent();
				if (typeof window[_this.params['ctrlEnterHandler']] == 'function')
					window[_this.params['ctrlEnterHandler']]();
				else
					BX.submit(BX(_this.formID));
			});
		}
		var parsers = (this.params.parsers ? this.params.parsers : []);

		if (BX.util.object_search('Spoiler', parsers))
		{
			editor.AddButton({
				id : 'spoiler',
				name : BX.message('spoilerText'),
				iconClassName : 'spoiler',
				disabledForTextarea : false,
				src : BX.message('MPF_TEMPLATE_FOLDER') + '/images/lhespoiler.png',
				toolbarSort : 205,
				handler : function()
				{
					var
						_this = this,
						res = false;

					// Iframe
					if (!_this.editor.bbCode || !_this.editor.synchro.IsFocusedOnTextarea())
					{
						res = _this.editor.action.actions.formatBlock.exec('formatBlock', 'blockquote', 'bx-spoiler', false, {bxTagParams : {tag: "spoiler"}});
					}
					else // bbcode + textarea
					{
						res = _this.editor.action.actions.formatBbCode.exec('quote', {tag: 'SPOILER'});
					}
					return res;
				}
			});
			editor.AddParser({
				name : 'spoiler',
				obj : {
					Parse: function(sName, content, pLEditor)
					{
						if (/\[(cut|spoiler)(([^\]])*)\]/gi.test(content))
						{
							content = content.
								replace(/[\001-\006]/gi, '').
								replace(/\[cut(((?:=)[^\]]*)|)\]/gi, '\001$1\001').
								replace(/\[\/cut]/gi, '\002').
								replace(/\[spoiler([^\]]*)\]/gi, '\003$1\003').
								replace(/\[\/spoiler]/gi, '\004');
							var
								reg1 = /(?:\001([^\001]*)\001)([^\001-\004]+)\002/gi,
								reg2 = /(?:\003([^\003]*)\003)([^\001-\004]+)\004/gi,
								__replace_reg = function(title, body){
									title = title.replace(/^(="|='|=)/gi, '').replace(/("|')?$/gi, '');
									return '<blockquote class="bx-spoiler" id="' + pLEditor.SetBxTag(false, {tag: "spoiler"}) + '" title="' + title + '">' + body + '</blockquote>';
								},
								func = function(str, title, body){return __replace_reg(title, body);};
							while (content.match(reg1) || content.match(reg2))
							{
								content = content.
									replace(reg1, func).
									replace(reg2, func);
							}
						}
						content = content.
							replace(/\001([^\001]*)\001/gi, '[cut$1]').
							replace(/\003([^\003]*)\003/gi, '[spoiler$1]').
							replace(/\002/gi, '[/cut]').
							replace(/\004/gi, '[/spoiler]');
						return content;
					},
					/**
					 * @return {string}
					 */
					UnParse: function(bxTag, oNode)
					{
						if (bxTag.tag == 'spoiler')
						{
							var name = '', i;
							// Handle childs
							for (i = 0; i < oNode.node.childNodes.length; i++)
							{
								name += editor.bbParser.GetNodeHtml(oNode.node.childNodes[i]);
							}
							name = BX.util.trim(name);
							if (name != '')
								return "[SPOILER" + (oNode.node.hasAttribute("title") ? '=' + oNode.node.getAttribute("title") : '')+ "]" + name +"[/SPOILER]";
						}
						return "";
					}
				}
			});
		}
		if (BX.util.object_search('MentionUser', parsers))
		{
			editor.AddParser(
				{
					name: 'postuser',
					obj: {
						Parse: function(parserName, content)
						{
							content = content.replace(/\[USER\s*=\s*(\d+)\]((?:\s|\S)*?)\[\/USER\]/ig,
								function(str, id, name)
								{
									name = BX.util.trim(name);
									if (name == '')
										return '';
									return '<span id="' + editor.SetBxTag(false, {tag: "postuser", params: {value : parseInt(id)}}) + '" class="bxhtmled-metion">' + name + '</span>';
								});
							return content;
						},
						/**
						 * @return {string}
						 */
						UnParse: function(bxTag, oNode)
						{
							if (bxTag.tag == 'postuser')
							{
								var name = '', i;
								// Handle childs
								for (i = 0; i < oNode.node.childNodes.length; i++)
								{
									name += editor.bbParser.GetNodeHtml(oNode.node.childNodes[i]);
								}
								name = BX.util.trim(name);
								if (name != '')
									return "[USER=" + bxTag.params.value + "]" + name +"[/USER]";
							}
							return "";
						}
					}
				}
			);
		}
		var funcParse = function(parserName, content) {
				return _this.Parse(parserName, content, editor);
			},
			funcUnparse = function(bxTag, oNode) {
				return _this.Unparse(bxTag, oNode/*, editor*/);
			};
		for (var parser in this.parser)
		{
			if (this.parser.hasOwnProperty(parser))
			{
				editor.AddParser({
					name: parser,
					obj: {
						Parse: funcParse,
						UnParse: funcUnparse
					}
				});
			}
		}
	},
	OnEditorInitedAfter : function(editor)
	{
		// Contextmenu changing for images/files
		editor.contextMenu.items['postimage'] =
			editor.contextMenu.items['postdocument'] =
				editor.contextMenu.items['postfile'] =
					[
						{
							text: BX.message('BXEdDelFromText'),
							bbMode: true,
							onclick: function()
							{
								var node = editor.contextMenu.GetTargetItem('postimage');
								if (!node)
									node = editor.contextMenu.GetTargetItem('postdocument');
								if (!node)
									node = editor.contextMenu.GetTargetItem('postfile');

								if (node && node.element)
								{
									editor.selection.RemoveNode(node.element);
								}
								editor.contextMenu.Hide();
							}
						}
					];
		if (!this.params["lazyLoad"])
		{
			BX.onCustomEvent(this.eventNode, 'OnShowLHE', ["justShow", editor, false])
		}

		if (editor.toolbar.controls && editor.toolbar.controls.FontSelector)
		{
			editor.toolbar.controls.FontSelector.SetWidth(45);
		}

		BX.addCustomEvent(BX(this.formID), 'onAutoSavePrepare', function (ob) {
			var _ob=ob;
			setTimeout(function() {
				BX.addCustomEvent(editor, 'OnContentChanged', BX.proxy(function(text) {
					this["mpfTextContent"] = text;
					this.Init();
				}, _ob));
			},1500);
		});
		BX.addCustomEvent(BX(this.formID), 'onAutoSave', BX.proxy(function(ob, form_data)
		{
			if (BX.type.isNotEmptyString(ob['mpfTextContent']))
				form_data['text' + this.formID] = ob['mpfTextContent'];
		}, this));
		BX.addCustomEvent(BX(this.formID), 'onAutoSaveRestore', BX.proxy(function(ob, form_data)
		{
			if (form_data['text' + this.formID] && /[^\s]+/gi.test(form_data['text' + this.formID]))
			{
				editor.CheckAndReInit(form_data['text' + this.formID]);
			}
		}, this));

		if (BX(this.formID) && BX(this.formID).hasAttribute("bx-lhe-autosave-prepared") && BX(this.formID).BXAUTOSAVE)
		{
			BX(this.formID).removeAttribute("bx-lhe-autosave-prepared");
			setTimeout(BX.proxy(function(){ BX(this.formID).BXAUTOSAVE.Prepare(); }, this), 100);
		}
		var
			formID = this.formID,
			settings = this.params;

		this.showPanelEditor(settings.showPanelEditor, false);

		if (!editor.mainPostFormCustomized)
		{
			editor.mainPostFormCustomized = true;

			BX.addCustomEvent(
				editor,
				'OnIframeKeydown',
				function(e)
				{
					if (window.onKeyDownHandler)
					{
						window.onKeyDownHandler(e, editor, formID);
					}
				}
			);

			BX.addCustomEvent(
				editor,
				'OnIframeKeyup',
				function(e)
				{
					if (window.onKeyUpHandler)
					{
						window.onKeyUpHandler(e, editor, formID);
					}
				}
			);

			if (window['BXfpdStopMent' + formID])
			{
				BX.addCustomEvent(
					editor,
					'OnIframeClick',
					function()
					{
						window['BXfpdStopMent' + formID]();
					}
				);
			}

			// Just to avoid version dependence from fileman
			if (editor && editor.textareaView.GetCursorPosition)
			{
				BX.addCustomEvent(
					editor,
					'OnTextareaKeyup',
					function(e)
					{
						if (window.onTextareaKeyUpHandler)
						{
							window.onTextareaKeyUpHandler(e, editor, formID);
						}
					}
				);

				BX.addCustomEvent(
					editor,
					'OnTextareaKeydown',
					function(e)
					{
						if (window.onTextareaKeyDownHandler)
						{
							window.onTextareaKeyDownHandler(e, editor, formID);
						}
					}
				);
			}
		}
	}
};
window.LHEPostForm.getEditor = function(editor)
{
	return (window["BXHtmlEditor"] ? window["BXHtmlEditor"].Get((typeof editor == "object" ? editor.id : editor)) : null);
};
window.LHEPostForm.getHandler = function(editor)
{
	return repo.handler[(typeof editor == "object" ? editor.id : editor)];
};
window.LHEPostForm.reinitData = function(editorID, text, data)
{
	var handler = LHEPostForm.getHandler(editorID);
	if (handler)
		handler.exec(handler.reinit, [text, data]);
	return false;
};
window.LHEPostForm.reinitDataBefore = function(editorID)
{
	var handler = LHEPostForm.getHandler(editorID);
	if (handler && handler["eventNode"])
		BX.onCustomEvent(handler.eventNode, "onReinitializeBefore", [handler]);
};
window.BXPostFormTags = function(formID, buttonID)
{
	this.popup = null;
	this.formID = formID;
	this.buttonID = buttonID;
	this.sharpButton = null;
	this.addNewLink = null;
	this.tagsArea = null;
	this.hiddenField = null;
	this.popupContent = null;

	BX.ready(BX.proxy(this.init, this));
};

window.BXPostFormTags.prototype.init = function()
{
	this.sharpButton = BX(this.buttonID);
	this.addNewLink = BX("post-tags-add-new-" + this.formID);
	this.tagsArea = BX("post-tags-block-" + this.formID);
	this.tagsContainer = BX("post-tags-container-" + this.formID);
	this.hiddenField = BX("post-tags-hidden-" + this.formID);
	this.popupContent = BX("post-tags-popup-content-" + this.formID);
	this.popupInput = BX.findChild(this.popupContent, { tag : "input" });

	var tags = BX.findChildren(this.tagsContainer, { className : "feed-add-post-del-but" }, true);
	for (var i = 0, cnt = tags.length; i < cnt; i++ )
	{
		BX.bind(tags[i], "click", BX.proxy(this.onTagDelete, {
			obj : this,
			tagBox : tags[i].parentNode,
			tagValue : tags[i].parentNode.getAttribute("data-tag")
		}));
	}

	BX.bind(this.sharpButton, "click", BX.proxy(this.onButtonClick, this));
	BX.bind(this.addNewLink, "click", BX.proxy(this.onAddNewClick, this));
};

window.BXPostFormTags.prototype.onTagDelete = function()
{
	BX.remove(this.tagBox);
	this.obj.hiddenField.value = this.obj.hiddenField.value.replace(this.tagValue + ',', '').replace('  ', ' ');
};

window.BXPostFormTags.prototype.show = function()
{
	if (this.popup === null)
	{
		this.popup = new BX.PopupWindow("bx-post-tag-popup", this.addNewLink, {
			content : this.popupContent,
			lightShadow : false,
			offsetTop: 8,
			offsetLeft: 10,
			autoHide: true,
			angle : true,
			closeByEsc: true,
			zIndex: -910,
			buttons: [
				new BX.PopupWindowButton({
					text : BX.message("TAG_ADD"),
					events : {
						click : BX.proxy(this.onTagAdd, this)
					}
				})
			]
		});

		BX.bind(this.popupInput, "keydown", BX.proxy(this.onKeyPress, this));
		BX.bind(this.popupInput, "keyup", BX.proxy(this.onKeyPress, this));
	}

	this.popup.show();
	BX.focus(this.popupInput);
};

window.BXPostFormTags.prototype.addTag = function(tagStr)
{
	var tags = BX.type.isNotEmptyString(tagStr) ? tagStr.split(",") : this.popupInput.value.split(",");
	var result = [];
	for (var i = 0; i < tags.length; i++ )
	{
		var tag = BX.util.trim(tags[i]);
		if(tag.length > 0)
		{
			var allTags = this.hiddenField.value.split(",");
			if(!BX.util.in_array(tag, allTags))
			{
				var newTagDelete;
				var newTag = BX.create("span", {
					children : [
						(newTagDelete = BX.create("span", { attrs : { "class": "feed-add-post-del-but" }}))
					],
					attrs : { "class": "feed-add-post-tags" }
				});

				newTag.insertBefore(document.createTextNode(tag), newTagDelete);
				this.tagsContainer.insertBefore(newTag, this.addNewLink);

				BX.bind(newTagDelete, "click", BX.proxy(this.onTagDelete, {
					obj : this,
					tagBox : newTag,
					tagValue : tag
				}));

				this.hiddenField.value += tag + ',';

				result.push(tag);
			}
		}
	}

	return result;
};

window.BXPostFormTags.prototype.onTagAdd = function(event)
{
	this.addTag();
	this.popupInput.value = "";
	this.popup.close();
};

window.BXPostFormTags.prototype.onAddNewClick = function(event)
{
	event = event || window.event;
	this.show();
	BX.PreventDefault(event);
};

window.BXPostFormTags.prototype.onButtonClick = function(event)
{
	event = event || window.event;
	BX.show(this.tagsArea);
	this.show();
	BX.PreventDefault(event);
};

window.BXPostFormTags.prototype.onKeyPress = function(event)
{
	event = event || window.event;
	var key = (event.keyCode ? event.keyCode : (event.which ? event.which : null));
	if (key == 13)
	{
		setTimeout(BX.proxy(this.onTagAdd, this), 0);
	}
};

var lastWaitElement = null;
window.MPFbuttonShowWait = function(el)
{
	if (el && !BX.type.isElementNode(el))
		el = null;
	el = el || this;
	el = (el ? (el.tagName == "A" ? el : el.parentNode) : el);
	if (el)
	{
		BX.addClass(el, "feed-add-button-load");
		lastWaitElement = el;
		BX.defer(function(){el.disabled = true})();
	}
};

window.MPFbuttonCloseWait = function(el)
{
	if (el && !BX.type.isElementNode(el))
		el = null;
	el = el || lastWaitElement || this;
	if (el)
	{
		el.disabled = false ;
		BX.removeClass(el, 'feed-add-button-load');
		lastWaitElement = null;
	}
};

window.__mpf_wd_getinfofromnode = function(result, obj)
{
	var preview = BX.findChild(BX((result["prefixNode"] || 'wd-doc') + result.element_id), {'className': 'files-preview', 'tagName' : 'IMG'}, true, false);
	if (preview)
	{
		result.lowsrc = preview.src;
		result.element_url = preview.src.replace(/\Wwidth\=(\d+)/, '').replace(/\Wheight\=(\d+)/, '');
		result.width = parseInt(preview.getAttribute("data-bx-full-width"));
		result.height = parseInt(preview.getAttribute("data-bx-full-height"));
	}
	else if (obj.urlGet)
	{
		result.element_url = obj.urlGet.
			replace("#element_id#", result.element_id).
			replace("#ELEMENT_ID#", result.element_id).
			replace("#element_name#", result.element_name).
			replace("#ELEMENT_NAME#", result.element_name);
	}
};

var MPFMention = {listen: false, plus : false, text : ''};

window.BXfpdSetLinkName = function(name)
{
	if (BX.SocNetLogDestination.getSelectedCount(name) <= 0)
		BX('bx-destination-tag').innerHTML = BX.message("BX_FPD_LINK_1");
	else
		BX('bx-destination-tag').innerHTML = BX.message("BX_FPD_LINK_2");
};

window.BXfpdSelectCallback = function(item, type, search, bUndeleted)
{
	if(!BX.findChild(BX('feed-add-post-destination-item'), { attr : { 'data-id' : item.id }}, false, false))
	{
		var type1 = type;
		var prefix = 'S';

		if (type == 'groups')
		{
			type1 = 'all-users';
		}
		else if (BX.util.in_array(type, ['contacts', 'companies', 'leads', 'deals']))
		{
			type1 = 'crm';
		}

		if (type == 'sonetgroups')
		{
			prefix = 'SG';
		}
		else if (type == 'groups')
		{
			prefix = 'UA';
		}
		else if (type == 'users')
		{
			prefix = 'U';
		}
		else if (type == 'department')
		{
			prefix = 'DR';
		}
		else if (type == 'contacts')
		{
			prefix = 'CRMCONTACT';
		}
		else if (type == 'companies')
		{
			prefix = 'CRMCOMPANY';
		}
		else if (type == 'leads')
		{
			prefix = 'CRMLEAD';
		}
		else if (type == 'deals')
		{
			prefix = 'CRMDEAL';
		}

		var stl = (bUndeleted ? ' feed-add-post-destination-undelete' : '');
		stl += (type == 'sonetgroups' && typeof window['arExtranetGroupID'] != 'undefined' && BX.util.in_array(item.entityId, window['arExtranetGroupID']) ? ' feed-add-post-destination-extranet' : '');

		var el = BX.create("span", {
			attrs : {
				'data-id' : item.id
			},
			props : {
				className : "feed-add-post-destination feed-add-post-destination-"+type1+stl
			},
			children: [
				BX.create("input", {
					attrs : {
						'type' : 'hidden',
						'name' : 'SPERM[' + prefix + '][]',
						'value' : item.id
					}
				}),
				BX.create("span", {
					props : {
						'className' : "feed-add-post-destination-text"
					},
					html : item.name
				})
			]
		});

		if(!bUndeleted)
		{
			el.appendChild(BX.create("span", {
				props : {
					'className' : "feed-add-post-del-but"
				},
				events : {
					'click' : function(e){
						BX.SocNetLogDestination.deleteItem(item.id, type, window.BXSocNetLogDestinationFormName);
						BX.PreventDefault(e)
					},
					'mouseover' : function(){
						BX.addClass(this.parentNode, 'feed-add-post-destination-hover');
					},
					'mouseout' : function(){
						BX.removeClass(this.parentNode, 'feed-add-post-destination-hover');
					}
				}
			}));
		}
		BX('feed-add-post-destination-item').appendChild(el);
	}

	BX('feed-add-post-destination-input').value = '';
	window.BXfpdSetLinkName(window.BXSocNetLogDestinationFormName);
};

// remove block
window.BXfpdUnSelectCallback = function(item, type, search)
{
	var elements = BX.findChildren(BX('feed-add-post-destination-item'), {attribute: {'data-id': ''+item.id+''}}, true);
	if (elements !== null)
	{
		for (var j = 0; j < elements.length; j++)
			BX.remove(elements[j]);
	}
	BX('feed-add-post-destination-input').value = '';
	window.BXfpdSetLinkName(window.BXSocNetLogDestinationFormName);
};
window.BXfpdOpenDialogCallback = function()
{
	BX.style(BX('feed-add-post-destination-input-box'), 'display', 'inline-block');
	BX.style(BX('bx-destination-tag'), 'display', 'none');
	BX.focus(BX('feed-add-post-destination-input'));
};

window.BXfpdCloseDialogCallback = function()
{
	if (!BX.SocNetLogDestination.isOpenSearch() && BX('feed-add-post-destination-input').value.length <= 0)
	{
		BX.style(BX('feed-add-post-destination-input-box'), 'display', 'none');
		BX.style(BX('bx-destination-tag'), 'display', 'inline-block');
		window.BXfpdDisableBackspace();
	}
};

window.BXfpdCloseSearchCallback = function()
{
	if (!BX.SocNetLogDestination.isOpenSearch() && BX('feed-add-post-destination-input').value.length > 0)
	{
		BX.style(BX('feed-add-post-destination-input-box'), 'display', 'none');
		BX.style(BX('bx-destination-tag'), 'display', 'inline-block');
		BX('feed-add-post-destination-input').value = '';
		window.BXfpdDisableBackspace();
	}

};
window.BXfpdDisableBackspace = function(event)
{
	if (BX.SocNetLogDestination.backspaceDisable || BX.SocNetLogDestination.backspaceDisable !== null)
		BX.unbind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable);

	BX.bind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable = function(event){
		if (event.keyCode == 8)
		{
			BX.PreventDefault(event);
			return false;
		}
	});
	setTimeout(function(){
		BX.unbind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable);
		BX.SocNetLogDestination.backspaceDisable = null;
	}, 5000);
};

window.BXfpdSearchBefore = function(event)
{
	if (event.keyCode == 8 && BX('feed-add-post-destination-input').value.length <= 0)
	{
		BX.SocNetLogDestination.sendEvent = false;
		BX.SocNetLogDestination.deleteLastItem(window.BXSocNetLogDestinationFormName);
	}

	return true;
};
window.BXfpdSearch = function(event)
{
	if (event.keyCode == 16 || event.keyCode == 17 || event.keyCode == 18 || event.keyCode == 20 || event.keyCode == 244 || event.keyCode == 224 || event.keyCode == 91)
		return false;

	if (event.keyCode == 13)
	{
		BX.SocNetLogDestination.selectFirstSearchItem(window.BXSocNetLogDestinationFormName);
		return true;
	}
	if (event.keyCode == 27)
	{
		BX('feed-add-post-destination-input').value = '';
		BX.style(BX('bx-destination-tag'), 'display', 'inline');
	}
	else
	{
		BX.SocNetLogDestination.search(BX('feed-add-post-destination-input').value, true, window.BXSocNetLogDestinationFormName);
	}

	if (!BX.SocNetLogDestination.isOpenDialog() && BX('feed-add-post-destination-input').value.length <= 0)
	{
		BX.SocNetLogDestination.openDialog(window.BXSocNetLogDestinationFormName);
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
};

window.onKeyDownHandler = function(e, editor, formID)
{
	var keyCode = e.keyCode;

	if (!window['BXfpdStopMent' + formID])
		return true;

	if (keyCode == 107 || (e.shiftKey || e.modifiers > 3) &&
		BX.util.in_array(keyCode, [187, 50, 107, 43, 61]))
	{
		setTimeout(function()
		{
			var
				range = editor.selection.GetRange(),
				doc = editor.GetIframeDoc(),
				txt = (range ? range.endContainer.textContent : ''),
				determiner = (txt ? txt.slice(range.endOffset - 1, range.endOffset) : ''),
				prevS = (txt ? txt.slice(range.endOffset - 2, range.endOffset-1) : '');

			if ((determiner == "@" || determiner == "+")
				&&
				(!prevS || BX.util.in_array(prevS, ["+", "@", ",", "("]) || (prevS.length == 1 && BX.util.trim(prevS) === "")))
			{
				MPFMention.listen = true;
				MPFMention.text = '';
				MPFMention.leaveContent = true;

				range.setStart(range.endContainer, range.endOffset - 1);
				range.setEnd(range.endContainer, range.endOffset);
				editor.selection.SetSelection(range);
				var mentNode = BX.create("SPAN", {props: {id: "bx-mention-node"}}, doc);
				editor.selection.Surround(mentNode, range);
				range.setStart(mentNode, 1);
				range.setEnd(mentNode, 1);
				editor.selection.SetSelection(range);

				if(!BX.SocNetLogDestination.isOpenDialog())
				{
					BX.SocNetLogDestination.openDialog(window['BXSocNetLogDestinationFormNameMent' + formID],
						{
							bindNode: getMentionNodePosition(mentNode, editor)
						}
					);
				}
			}
		}, 10);
	}

	if(MPFMention.listen && keyCode == editor.KEY_CODES["enter"])
	{
		BX.SocNetLogDestination.selectFirstSearchItem(window['BXSocNetLogDestinationFormNameMent' + formID]);
		editor.iframeKeyDownPreventDefault = true;
		BX.PreventDefault(e);
	}

	if (!MPFMention.listen && keyCode === editor.KEY_CODES["enter"])
	{
		var range = editor.selection.GetRange();
		if (range.collapsed)
		{
			var
				node = range.endContainer,
				doc = editor.GetIframeDoc();

			if (node)
			{
				if (node.className !== 'bxhtmled-metion')
				{
					node = BX.findParent(node, function(n)
					{
						return n.className == 'bxhtmled-metion';
					}, doc.body);
				}

				if (node && node.className == 'bxhtmled-metion')
				{
					editor.selection.SetAfter(node);
				}
			}
		}
	}
};

window.onKeyUpHandler = function(e, editor, formID)
{
	var
		keyCode = e.keyCode,
		doc, range;

	if (!window['BXfpdStopMent' + formID])
		return true;

	if(MPFMention.listen === true)
	{
		if(keyCode == editor.KEY_CODES["escape"]) //ESC
		{
			window['BXfpdStopMent' + formID]();
		}
		else if(keyCode !== editor.KEY_CODES["enter"])
		{
			doc = editor.GetIframeDoc();
			var mentNode = doc.getElementById('bx-mention-node');

			if (mentNode)
			{
				var
					mentText = BX.util.trim(editor.util.GetTextContent(mentNode)),
					mentTextOrig = mentText;

				mentText = mentText.replace(/^[\+@]*/, '');
				BX.SocNetLogDestination.search(mentText, true, window['BXSocNetLogDestinationFormNameMent' + formID], BX.message("MPF_NAME_TEMPLATE"), {bindNode: getMentionNodePosition(mentNode, editor)});

				if (MPFMention.leaveContent && MPFMention._lastText && mentTextOrig === '')
				{
					window['BXfpdStopMent' + formID]();
				}
				else if (MPFMention.leaveContent && MPFMention.lastText && mentTextOrig !== '' && mentText === '')
				{
					window['BXfpdStopMent' + formID]();
					BX.SocNetLogDestination.openDialog(window['BXSocNetLogDestinationFormNameMent' + formID],
						{
							bindNode: getMentionNodePosition(mentNode, editor)
						}
					);
				}

				MPFMention.lastText = mentText;
				MPFMention._lastText = mentTextOrig;
			}
		}
	}
	else
	{
		if (
			!e.shiftKey &&
			(keyCode === editor.KEY_CODES["space"] ||
			keyCode === editor.KEY_CODES["escape"] ||
			keyCode === 188 ||
			keyCode === 190
			))
		{
			range = editor.selection.GetRange();
			if (range.collapsed)
			{
				var node = range.endContainer;
				doc = editor.GetIframeDoc();

				if (node)
				{
					if (node.className !== 'bxhtmled-metion')
					{
						node = BX.findParent(node, function(n)
						{
							return n.className == 'bxhtmled-metion';
						}, doc.body);
					}

					if (node && node.className == 'bxhtmled-metion')
					{
						mentText = editor.util.GetTextContent(node);
						var matchSep = mentText.match(/[\s\.\,]$/);
						if (matchSep || keyCode === editor.KEY_CODES["escape"])
						{
							node.innerHTML = mentText.replace(/[\s\.\,]$/, '');
							var sepNode = BX.create('SPAN', {html: matchSep || editor.INVISIBLE_SPACE}, doc);
							editor.util.InsertAfter(sepNode, node);
							editor.selection.SetAfter(sepNode);
						}
					}
				}
			}
		}
	}
};

window.onTextareaKeyDownHandler = function(e, editor, formID)
{
	var keyCode = e.keyCode;

	if(MPFMention.listen && keyCode == editor.KEY_CODES["enter"])
	{
		BX.SocNetLogDestination.selectFirstSearchItem(window['BXSocNetLogDestinationFormNameMent' + formID]);
		editor.textareaKeyDownPreventDefault = true;
		BX.PreventDefault(e);
	}
};

window.onTextareaKeyUpHandler = function(e, editor, formID)
{
	var
		cursor, value,
		keyCode = e.keyCode;

	if(MPFMention.listen === true)
	{
		if(keyCode == 27) //ESC
		{
			window['BXfpdStopMent' + formID]();
		}
		else if(keyCode !== 13)
		{
			value = editor.textareaView.GetValue(false);
			cursor = editor.textareaView.GetCursorPosition();

			if (value.indexOf('+') !== -1 || value.indexOf('@') !== -1)
			{
				var
					valueBefore = value.substr(0, cursor),
					charPos = Math.max(valueBefore.lastIndexOf('+'), valueBefore.lastIndexOf('@'));

				if (charPos >= 0)
				{
					var
						mentText = valueBefore.substr(charPos),
						mentTextOrig = mentText;

					mentText = mentText.replace(/^[\+@]*/, '');

					if(!BX.SocNetLogDestination.isOpenDialog())
					{
						BX.SocNetLogDestination.openDialog(window['BXSocNetLogDestinationFormNameMent' + formID]);
					}

					BX.SocNetLogDestination.search(mentText, true, window['BXSocNetLogDestinationFormNameMent' + formID], BX.message("MPF_NAME_TEMPLATE"));

					if (MPFMention.leaveContent && MPFMention._lastText && mentTextOrig === '')
					{
						window['BXfpdStopMent' + formID]();
					}
					else if (MPFMention.leaveContent && MPFMention.lastText && mentTextOrig !== '' && mentText === '')
					{
						window['BXfpdStopMent' + formID]();
						BX.SocNetLogDestination.openDialog(window['BXSocNetLogDestinationFormNameMent' + formID]);
					}

					MPFMention.lastText = mentText;
					MPFMention._lastText = mentTextOrig;
				}
			}
		}
	}
	else
	{
		if (keyCode == 16)
		{
			var _this = this;
			this.shiftPressed = true;
			if (this.shiftTimeout)
				this.shiftTimeout = clearTimeout(this.shiftTimeout);

			this.shiftTimeout = setTimeout(function()
			{
				_this.shiftPressed = false;
			}, 100);
		}

		if (keyCode == 107 || (e.shiftKey || e.modifiers > 3 || this.shiftPressed) &&
			BX.util.in_array(keyCode, [187, 50, 107, 43, 61]))
		{
			cursor = editor.textareaView.element.selectionStart;
			if (cursor > 0)
			{
				value = editor.textareaView.element.value;
				var
					lastChar = value.substr(cursor - 1, 1);

				if (lastChar && (lastChar === '+' || lastChar === '@'))
				{
					MPFMention.listen = true;
					MPFMention.text = '';
					MPFMention.textarea = true;

					if(!BX.SocNetLogDestination.isOpenDialog())
					{
						BX.SocNetLogDestination.openDialog(window['BXSocNetLogDestinationFormNameMent' + formID]);
					}
				}
			}
		}
	}
};


window.getMentionNodePosition = function(mention, editor)
{
	var
		mentPos = BX.pos(mention),
		editorPos = BX.pos(editor.dom.areaCont),
		editorDocScroll = BX.GetWindowScrollPos(editor.GetIframeDoc()),
		top = editorPos.top + mentPos.bottom - editorDocScroll.scrollTop + 2,
		left = editorPos.left + mentPos.right - editorDocScroll.scrollLeft;

	return {top: top, left: left};
};

window.BxInsertMention = function (params)
{
	var
		item = params.item,
		type = params.type,
		formID = params.formID,
		editorId = params.editorId,
		bNeedComa = params.bNeedComa,
		editor = LHEPostForm.getEditor(editorId);

	if(type == 'users' && item && item.entityId > 0 && editor)
	{
		if(editor.GetViewMode() == 'wysiwyg') // WYSIWYG
		{
			var
				doc = editor.GetIframeDoc(),
				range = editor.selection.GetRange(),
				mentNode = doc.getElementById('bx-mention-node'),
				mention = BX.create('SPAN',
					{
						props: {className: 'bxhtmled-metion'},
						text: item.name
					}, doc),
				// &nbsp; - for chrome
				spaceNode = BX.create('SPAN', {html: (bNeedComa ? ',&nbsp;' : '&nbsp;')}, doc);

			editor.SetBxTag(mention, {tag: "postuser", params: {value : item.entityId}});

			if (mentNode)
			{
				editor.util.ReplaceNode(mentNode, mention);
			}
			else
			{
				editor.selection.InsertNode(mention, range);
			}

			if (mention && mention.parentNode)
			{
				editor.util.InsertAfter(spaceNode, mention);
				editor.selection.SetAfter(spaceNode);
			}
		}
		else if (editor.GetViewMode() == 'code' && editor.bbCode) // BB Codes
		{
			editor.textareaView.Focus();

			var
				value = editor.textareaView.GetValue(false),
				cursor = editor.textareaView.GetCursorPosition(),
				valueBefore = value.substr(0, cursor),
				charPos = Math.max(valueBefore.lastIndexOf('+'), valueBefore.lastIndexOf('@'));

			if (charPos >= 0 && cursor > charPos)
			{
				editor.textareaView.SetValue(value.substr(0, charPos) + value.substr(cursor));
				editor.textareaView.element.setSelectionRange(charPos, charPos);
			}

			editor.textareaView.WrapWith(false, false, "[USER=" + item.entityId + "]" + item.name + "[/USER]" + (bNeedComa ? ', ' : ' '));
		}

		delete BX.SocNetLogDestination.obItemsSelected[window['BXSocNetLogDestinationFormNameMent' + formID]][item.id];
		window['BXfpdStopMent' + formID]();
		MPFMention["text"] = '';

		if(editor.GetViewMode() == 'wysiwyg') // WYSIWYG
		{
			editor.Focus();
			editor.selection.SetAfter(spaceNode);
		}
	}
};

window.buildDepartmentRelation = function(department)
{
	var relation = {};
	for(var iid in department)
	{
		var p = department[iid]['parent'];
		if (!relation[p])
			relation[p] = [];
		relation[p][relation[p].length] = iid;
	}
	function makeDepartmentTree(id, relation)
	{
		var arRelations = {};
		if (relation[id])
		{
			for (var x in relation[id])
			{
				var relId = relation[id][x];
				var arItems = [];
				if (relation[relId] && relation[relId].length > 0)
					arItems = makeDepartmentTree(relId, relation);

				arRelations[relId] = {
					id: relId,
					type: 'category',
					items: arItems
				};
			}
		}

		return arRelations;
	}
	return makeDepartmentTree('DR0', relation);
};

window.MPFMentionInit = function(formId, params)
{
	if (!params["items"]["departmentRelation"])
		params["items"]["departmentRelation"] = window.buildDepartmentRelation(params["items"]["department"]);

	window["departmentRelation"] = params["items"]["departmentRelation"]; // for calendar - do not remove

	if (params["initDestination"] === true)
	{
		window.BXSocNetLogDestinationFormName = 'destination' + ('' + new Date().getTime()).substr(6);
		window.BXSocNetLogDestinationDisableBackspace = null;
		BX.SocNetLogDestination.init({
			name : window.BXSocNetLogDestinationFormName,
			searchInput : BX('feed-add-post-destination-input'),
			extranetUser :  params["extranetUser"],
			bindMainPopup : {
				node: BX('feed-add-post-destination-container'),
				offsetTop: '5px',
				offsetLeft: '15px'
			},
			bindSearchPopup : {
				node : BX('feed-add-post-destination-container'),
				offsetTop : '5px',
				offsetLeft: '15px'
			},
			callback : {
				select : window["BXfpdSelectCallback"],
				unSelect : window["BXfpdUnSelectCallback"],
				openDialog : window["BXfpdOpenDialogCallback"],
				closeDialog : window["BXfpdCloseDialogCallback"],
				openSearch : window["BXfpdOpenDialogCallback"],
				closeSearch : window["BXfpdCloseSearchCallback"]
			},
			items : params["items"],
			itemsLast : params["itemsLast"],
			itemsSelected : params["itemsSelected"],
			isCrmFeed : params["isCrmFeed"]
		});
		BX.bind(BX('feed-add-post-destination-input'), 'keyup', window["BXfpdSearch"]);
		BX.bind(BX('feed-add-post-destination-input'), 'keydown', window["BXfpdSearchBefore"]);
		BX.bind(BX('bx-destination-tag'), 'click', function(e){BX.SocNetLogDestination.openDialog(window.BXSocNetLogDestinationFormName); BX.PreventDefault(e); });
		BX.bind(BX('feed-add-post-destination-container'), 'click', function(e){BX.SocNetLogDestination.openDialog(window.BXSocNetLogDestinationFormName); BX.PreventDefault(e); });
		if (params["itemsHidden"])
		{
			for (var ii in params["itemsHidden"])
			{
				window.BXfpdSelectCallback({id:('SG'+params["itemsHidden"][ii]["ID"]), name:params["itemsHidden"][ii]["NAME"]}, 'sonetgroups', '', true);
			}
		}
		window.BXfpdSetLinkName(window.BXSocNetLogDestinationFormName);
	};
	window["BXfpdSelectCallbackMent" + formId] = function(item, type, search)
	{
		window.BxInsertMention({item: item, type: type, formID: formId, editorId: params["editorId"]});
	};

	window["BXfpdStopMent" + formId] = function ()
	{
		BX.SocNetLogDestination.closeDialog();
		BX.SocNetLogDestination.closeSearch();
		clearTimeout(BX.SocNetLogDestination.searchTimeout);
		BX.SocNetLogDestination.searchOnSuccessHandle = false;
	};

	window["BXfpdOnDialogOpen" + formId] = function ()
	{
		MPFMention.listen = true;
	};

	window["BXfpdOnDialogClose" + formId] = function ()
	{
		MPFMention.listen = false;
		setTimeout(function()
		{
			if (!MPFMention.listen)
			{
				var editor = LHEPostForm.getEditor(params.editorId);
				if(editor)
				{
					var
						doc = editor.GetIframeDoc(),
						mentNode = doc.getElementById('bx-mention-node');

					if (mentNode)
					{
						editor.selection.SetAfter(mentNode);
						if (MPFMention.leaveContent)
						{
							editor.util.ReplaceWithOwnChildren(mentNode);
						}
						else
						{
							BX.remove(mentNode);
						}
					}
					editor.Focus();
				}
			}
		}, 100);
	};

	window["BXSocNetLogDestinationFormNameMent" + formId] = 'mention' + ('' + new Date().getTime()).substr(5);
	window["BXSocNetLogDestinationDisableBackspace"] = null;
	var bxBMent = BX('bx-b-mention-' + formId);

	BX.SocNetLogDestination.init({
		name : window["BXSocNetLogDestinationFormNameMent" + formId],
		searchInput : bxBMent,
		extranetUser : params["extranetUser"],
		bindMainPopup :  {
			node : bxBMent,
			offsetTop : '1px',
			offsetLeft: '12px'
		},
		bindSearchPopup : {
			node : bxBMent,
			offsetTop : '1px',
			offsetLeft: '12px'
		},
		callback : {
			select : window["BXfpdSelectCallbackMent" + formId],
			openDialog : window["BXfpdOnDialogOpen" + formId],
			closeDialog : window["BXfpdOnDialogClose" + formId],
			openSearch : window["BXfpdOnDialogOpen" + formId],
			closeSearch : window["BXfpdOnDialogClose" + formId]
		},
		items : {
			users : params["items"]["users"],
			groups : {},
			sonetgroups : {},
			department : params["items"]["department"],
			departmentRelation : params["items"]["departmentRelation"]
		},
		itemsLast : {
			users : window["lastUsers"],
			sonetgroups : {},
			department : {},
			groups : {}
		},
		itemsSelected : params["itemsSelected"],
		departmentSelectDisable : true,
		obWindowClass : 'bx-lm-mention',
		obWindowCloseIcon : false
	});

	BX.ready(function() {
			var ment = BX('bx-b-mention-' + formId);
			if(BX.browser.IsIE() && !BX.browser.IsIE9())
			{
				ment.style.width = '1px';
				ment.style.marginRight = '0';
			}

			BX.bind(
				ment,
				"mousedown",
				function(e)
				{
					if(MPFMention.listen !== true)
					{
						var
							editor = LHEPostForm.getEditor(params.editorId),
							doc = editor.GetIframeDoc();

						if(editor.GetViewMode() == 'wysiwyg' && doc)
						{
							MPFMention.listen = true;
							MPFMention.text = '';
							MPFMention.leaveContent = false;

							var
								range = editor.selection.GetRange(),
								mentNode = doc.getElementById('bx-mention-node');

							if (mentNode)
							{
								BX.remove(mentNode);
							}
							editor.InsertHtml('<span id="bx-mention-node">' + editor.INVISIBLE_SPACE + '</span>', range);

							setTimeout(function()
							{
								if(!BX.SocNetLogDestination.isOpenDialog())
								{
									BX.SocNetLogDestination.openDialog(window["BXSocNetLogDestinationFormNameMent" + formId], {bindNode: ment});
								}

								var mentionNode = doc.getElementById('bx-mention-node');
								if (mentionNode)
								{
									range.setStart(mentionNode, 0);
									if (mentionNode.firstChild && mentionNode.firstChild.nodeType == 3 && mentionNode.firstChild.nodeValue.length > 0)
									{
										range.setEnd(mentionNode, 1);
									}
									else
									{
										range.setEnd(mentionNode, 0);
									}
									editor.selection.SetSelection(range);
								}
								editor.Focus();
							}, 100);
						}
						else if (editor.GetViewMode() == 'code')
						{
							MPFMention.listen = true;
							MPFMention.text = '';
							MPFMention.leaveContent = false;

							// TODO: get current cusrsor position

							setTimeout(function()
							{
								if(!BX.SocNetLogDestination.isOpenDialog())
								{
									BX.SocNetLogDestination.openDialog(window["BXSocNetLogDestinationFormNameMent" + formId], {bindNode: ment});
								}
							}, 100);
						}

						BX.onCustomEvent(ment, 'mentionClick');
					}
				}
			);
		}
	);
}
})(window);