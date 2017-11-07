;(function(window){
if (window.LHEPostForm) return;
window.LHEPostForm = function(formID, params, parsers)
{
	this.Inited = this.Init(formID, params);
	this.objName = 'PlEditor' + formID;
	window[this.objName] = this;

	this.formID = formID;
	this.oEditor = window[params['LHEJsObjName']];
	this.oEditorName = params['LHEJsObjName'];
	this.oEditorId = params['LHEJsObjId'];

//	this.WDController = null;
//	this.FController = null;
	this.arSize = params['arSize'];
	this.arSize = (typeof this.arSize == "object" && !!this.arSize && this.arSize.width && this.arSize.height ? this.arSize : false);

	this.sNewFilePostfix = (params["sNewFilePostfix"] ? params["sNewFilePostfix"] : '');

	parsers = (!!parsers ? parsers : []);
	this.parser = {
		postimage : {
			'exist' : (BX.util.in_array('postimage', parsers) ? true : null),
			'tag' : 'postimage',
			'thumb_width' : 800,
			'regexp' : /\[IMG ID=((?:\s|\S)*?)(?:\s*?WIDTH=(\d+)\s*?HEIGHT=(\d+))?\]/ig,
			'code' : '[IMG ID=#ID##ADDITIONAL#]',
			'html' : '<img id="#ID#" src="#SRC#" lowsrc="#LOWSRC#" title=""#ADDITIONAL# />'
		},
		postfile : {
			'exist' : (BX.util.in_array('postfile', parsers) ? true : null),
			'tag' : 'postfile',
			'thumb_width' : 800,
			'regexp' : /\[FILE ID=((?:\s|\S)*?)(?:\s*?WIDTH=(\d+)\s*?HEIGHT=(\d+))?\]/ig,
			'code' : '[FILE ID=#ID##ADDITIONAL#]',
			'html' : '<span style="color: #2067B0; border-bottom: 1px dashed #2067B0; margin:0 2px;" id="#ID#"#ADDITIONAL#>#NAME#</span>'
		},
		postdocument : {
			'exist' : (BX.util.in_array('postfile', parsers) ? true : null),
			'tag' : "postdocument", // and parser LHE
			'thumb_width' : 800,
			'regexp' : /\[DOCUMENT ID=((?:\s|\S)*?)(?:\s*?WIDTH=(\d+)\s*?HEIGHT=(\d+))?\]/ig,
			'code' : '[DOCUMENT ID=#ID##ADDITIONAL#]',
			'html' : '<span style="color: #2067B0; border-bottom: 1px dashed #2067B0; margin:0 2px;" id="#ID#"#ADDITIONAL#>#NAME#</span>'
		}
	}
	this.arFiles = {};
	if (typeof params["arFiles"] == "object" && params["arFiles"] !== null)
	{
		for (var ii in params["arFiles"]) {
			var id = this.checkFile(ii, params["arFiles"][ii]);
			if (!!id)
				this.bindToFile(id);
		}
	}
	this.eventNode = BX('div' + params['LHEJsObjName']);
	BX.addCustomEvent(this.eventNode, 'OnShowLHE', BX.delegate(this.OnShowLHE, this));
	BX.addCustomEvent(this.eventNode, 'OnButtonClick', BX.delegate(this.OnButtonClick, this));
	BX.addCustomEvent(this.eventNode, 'OnShowPanelEditor', BX.delegate(function(res){
		var node = BX('lhe_button_editor_' + this.formID);
		if (!!node)
		{
			if(res == "Y")
				BX.addClass(node, 'feed-add-post-form-btn-active');
			else
				BX.removeClass(node, 'feed-add-post-form-btn-active');
		}
	}, this));
}

window.LHEPostForm.prototype = {
	Init : function(formID, params)
	{
		if (!!params["WDLoadFormController"])
		{
			this.WDControllerNode = (!!params["WDControllerCID"] ?
				BX('wduf-selectdialog-' + params["WDControllerCID"]) : BX.findChild(BX(formID), {'className': 'wduf-selectdialog'}, true, false));
			this.WDControllerNode = (!!this.WDControllerNode ? this.WDControllerNode.parentNode : this.WDControllerNode);
			this.WDControllerInit = BX.delegate(function(status) { BX.onCustomEvent(this.WDControllerNode, "WDLoadFormController", [status]); }, this);
			var func = function(obj){
				if (!!this.WDController)
					return true;
				if ((!!params["WDControllerCID"] && obj.CID == params["WDControllerCID"]) ||
					(!params["WDControllerCID"] && obj.dialogName == 'AttachFileDialog')) {
					this.WDController = obj;
					this.OnWDSelectFileDialogLoaded(obj);
				}
			};
			BX.addCustomEvent(this.WDControllerNode, 'WDLoadFormControllerInit', BX.delegate(func, this));
			BX.addCustomEvent('WDSelectFileDialogLoaded', BX.delegate(func, this));

			BX.addCustomEvent(
				this.WDControllerNode,
				'OnFileUploadSuccess',
				BX.delegate(
					function(result, obj) {
						if (!!this.WDController && obj.id == this.WDController.id) {
							window.__mpf_wd_getinfofromnode(result, obj);
							this.OnFileUploadSuccess(result, obj);
						}
					},
					this
				)
			);
			BX.addCustomEvent(
				this.WDControllerNode,
				'OnFileUploadRemove',
				BX.delegate(
					function(result, obj){
						if (!!this.WDController && obj.id == this.WDController.id) {
							this.OnFileUploadRemove(result, obj, 'webdav');
						}
					},
					this
				)
			);
		}

		if (!!params["BFileDLoadFormController"]) {
			var func1;
			if (!!params["FControllerID"]) {
				func1 = function(obj){if (obj.id == params["FControllerID"]) this.FController = obj;};
				this.FControllerNode = BX.findParent(BX('file-selectdialog-' + params["FControllerID"]));
			} else {
				func1 = function(obj){if (obj.dialogName == 'AttachmentsDialog') this.FController = obj;};
				this.FControllerNode = BX.findParent(BX.findChild(BX(formID), {'className': 'file-selectdialog'}, true, false));
			}
			this.FControllerInit = BX.delegate(function(status){ BX.onCustomEvent(this.FControllerNode, 'BFileDLoadFormController', [status]); }, this);
			BX.addCustomEvent(this.FControllerNode, 'BFileDLoadFormControllerInit', BX.delegate(func1, this));
			BX.addCustomEvent('BFileDSelectFileDialogLoaded', BX.delegate(func1, this));
			BX.addCustomEvent(
				this.FControllerNode,
				'OnFileUploadSuccess',
				BX.delegate(
					function(result, obj){
						if (!!this.FController && obj.id == this.FController.id) {
							this.OnFileUploadSuccess(result, obj);
						}
					},
					this
				)
			);
			BX.addCustomEvent(
				this.FControllerNode,
				'OnFileUploadRemove',
				BX.delegate(
					function(result, obj){
						if (!!this.FController && obj.id == this.FController.id) {
							this.OnFileUploadRemove(result, obj, 'bfile');
						}
					},
					this
				)
			);
		}

		BX.ready(
			BX.delegate(
				function()
				{
					var
						uploadfile = BX('bx-b-uploadfile-' + formID),
						uploadImage = BX('bx-b-uploadimage-' + formID);
					if (!!uploadfile && !!params["WDLoadFormController"])
					{
						BX.bind(uploadfile, 'click', this.WDControllerInit);
					}
					if (!!params["BFileDLoadFormController"])
					{
						if (!!uploadImage)
							BX.bind(uploadImage, 'click', this.FControllerInit);
						else if(!!uploadfile)
							BX.bind(uploadfile, 'click', this.FControllerInit);
					}
				},
				this
			)
		);
		return true;
	},

	OnFileUploadSuccess : function(result, obj)
	{
		var node;
		this.oEditor.SaveContent();
		if (!!obj && !!obj.CID)
		{
			if (obj.dialogName == 'AttachmentsDialog')
			{
				node = BX.findChild(BX(this.formID), {'attr': {id: 'upload-cid'}}, true, false);
				if (!!node) node.value = obj.CID;
			}
			else if (obj.dialogName == 'AttachFileDialog')
			{
				node = BX.findChild(BX(this.formID), {'attr': {id: 'upload-wd-cid'}}, true, false);
				if (!!node) node.value = obj.CID;
			}
		}

		this.oEditor['arFiles'].push(result.element_id);
		this.parser['postimage']['exist'] = (this.parser['postimage']['exist'] === null ?
			!!this.oEditor['oSpecialParsers']['postimage'] : this.parser['postimage']['exist']);
		this.parser['postfile']['exist'] = (this.parser['postfile']['exist'] === null ?
			!!this.oEditor['oSpecialParsers']['postfile'] : this.parser['postfile']['exist']);
		this.parser['postdocument']['exist'] = (this.parser['postdocument']['exist'] === null ?
			!!this.oEditor['oSpecialParsers']['postdocument'] : this.parser['postdocument']['exist']);

		result["isImage"] = (result.element_content_type && result.element_content_type.substr(0,6) == 'image/');
		if (result.storage == 'bfile' && !(this.parser['postimage']['exist'] && result.isImage || this.parser['postfile']['exist']))
			return false;
		else if (result.storage == 'webdav' && !this.parser['postdocument']['exist'])
			return false;

		var id = this.checkFile(result.element_id, result, true);
		if (!!id){
			var f = this.bindToFile(id);
			this.checkFileInText(this.checkFile(id));
			if ((!!this.oEditor.insertImageAfterUpload && f.isImage) || !!this.oEditor.insertFileAfterUpload)
				this.insertFile(id);
		}
	},

	OnFileUploadRemove : function(result, obj, storage)
	{
		if (BX.findChild(BX(this.formID), {'attr': {id: 'wd-doc'+result}}, true, false))
			this.deleteFile(result, null, null, storage);
	},

	OnWDSelectFileDialogLoaded : function(wdFD)
	{
		if (!(typeof wdFD == "object" && !!wdFD && !!wdFD.values && !!wdFD.urlGet))
			return false;
		var needToReparse = false, id = 0, data = {}, node = null, arID = {}, preview = null, did = null,
			func = BX.delegate(function(){
				this.checkFileInText(
					this.checkFile(BX.proxy_context.getAttribute("mpfId")),
					null,
					arguments[0]
				);
			},
			this
		);

		for (var ii = 0; ii < wdFD.values.length; ii++)
		{
			id = parseInt(wdFD.values[ii].getAttribute("id").replace("wd-doc", ""));
			if (!!arID['id' + id] )
				continue;
			arID['id' + id] = "Y";
			if (id > 0)
			{
				node = BX.findChild(wdFD.values[ii], {'className': 'f-wrap'}, true, false);
				if(!node)
					continue;
				data = {
					'element_id' : id,
					'element_name' : node.innerHTML,
					'parser' : 'postdocument',
					'storage' : 'webdav'};
				window.__mpf_wd_getinfofromnode(data, wdFD);
				did = this.checkFile(id, data);
				if (did){
					this.bindToFile(did);
					needToReparse = (needToReparse === false ? [] : needToReparse);
					needToReparse.push(id);
					wdFD.values[ii].setAttribute("mpfId", did);
					BX.addCustomEvent(
						wdFD.values[ii],
						'OnMkClose',
						func
					);
				}
			}
		}
		if (needToReparse !== false && this.oEditor && this.parser.postdocument.exist)
		{
			this.oEditor.SaveContent();
			var content = this.oEditor.GetContent();
			content = content.replace(new RegExp('\\&\\#91\\;DOCUMENT ID=(' + needToReparse.join("|") + ')([WIDTHHEIGHT=0-9 ]*)\\&\\#93\\;','gim'), '[DOCUMENT ID=$1$2]');
			this.oEditor.SetContent(content);
			this.oEditor.SetEditorContent(this.oEditor.content);
			this.oEditor.SetFocus();
			this.oEditor.AutoResize();
		}
	},

	showPanelEditor : function(show, pEditor)
	{
		if (!pEditor)
			pEditor = this.oEditor;
		this.showPanelEditorFormHeaders = (!!this.showPanelEditorFormHeaders ? this.showPanelEditorFormHeaders :
			BX.findChild(pEditor.pFrame, {'className': /bxlhe-editor-buttons/ }, true, true));
		var formHeaders = this.showPanelEditorFormHeaders;

		var p = ((formHeaders && formHeaders.length >= 1) ? formHeaders[formHeaders.length-1].parentNode : null),
			res = "Y";

		if(show || (p && p.style.display == "none"))
		{
			if(p)
				p.style.display = "table-row";
			pEditor.buttonsHeight = 34;
			pEditor.ResizeFrame();
		}
		else
		{
			if(p)
				BX.hide(p);
			pEditor.buttonsHeight = 0;
			pEditor.ResizeFrame();
			res = "N";
		}
		BX.onCustomEvent(this.eventNode, "OnShowPanelEditor", [res]);
		BX.userOptions.save('main.post.form', 'postEdit', 'showBBCode', res);
	},

	isPanelEditorShowed : function(pEditor)
	{
		if (!pEditor)
			pEditor = this.oEditor;

		this.showPanelEditorFormHeaders = (!!this.showPanelEditorFormHeaders ? this.showPanelEditorFormHeaders :
			BX.findChild(pEditor.pFrame, {'className': /bxlhe-editor-buttons/ }, true, true));
		var formHeaders = this.showPanelEditorFormHeaders;

		var p = ((formHeaders && formHeaders.length >= 1) ? formHeaders[formHeaders.length-1].parentNode : null);
		if(p && p.style.display == "none")
			return false;

		return true;
	},

	bindToFile : function(id)
	{
		var f = this.checkFile(id),
			img_wrap;
		if (!!f)
		{
			var intId = (typeof f.id == "string" ? parseInt(f.id.replace(this.sNewFilePostfix, "")) : f.id);
			if (f.isImage && f.storage == 'bfile')
			{
				var img = BX.findChild(BX('wd-doc'+intId), {'tagName': 'img'}, true, false),
					img_title = BX.findChild(BX('wd-doc'+intId), {'className': 'feed-add-img-title'}, true, false);
				img_wrap = BX.findChild(BX('wd-doc'+intId), {}, true, false);
				if (!!img_wrap && !img_wrap.hasAttribute("bx-mpf-bound-to-" + this.oEditorId))
				{
					img_wrap.setAttribute("bx-mpf-bound-to-" + this.oEditorId, "Y");
					BX.bind(img_wrap, "click", BX.delegate(function(){this.insertFile(id);}, this));
					img_wrap.style.cursor = "pointer";
					img_wrap.title = BX.message('MPF_IMAGE');
				}
				if (img_title && !img_title.hasAttribute("bx-mpf-bound-to-" + this.oEditorId))
				{
					img_title.setAttribute("bx-mpf-bound-to-" + this.oEditorId, "Y");
					BX.bind(img_title, "click", BX.delegate(function(){this.insertFile(id);}, this));
					img_title.style.cursor = "pointer";
					img_title.title = BX.message('MPF_IMAGE');
				}
			}
			else
			{
				var name_wrap = BX.findChild(BX('wd-doc'+intId), {'className': 'f-wrap'}, true, false);
				img_wrap = BX.findChild(BX('wd-doc'+intId), {'className': 'files-preview'}, true, false);
				if (!!name_wrap || !name_wrap.hasAttribute("bx-mpf-bound-to-" + this.oEditorId))
				{
					name_wrap.setAttribute("bx-mpf-bound-to-" + this.oEditorId, "Y");
					BX.bind(name_wrap, "click", BX.delegate(function(){this.insertFile(id);}, this));
					name_wrap.style.cursor = "pointer";
					name_wrap.title = BX.message('MPF_FILE');
				}
				if (!!img_wrap && !img_wrap.hasAttribute("bx-mpf-bound-to-" + this.oEditorId))
				{
					img_wrap.setAttribute("bx-mpf-bound-to-" + this.oEditorId, "Y");
					BX.bind(img_wrap, "click", BX.delegate(function(){this.insertFile(id);}, this));
				}
			}
		}
		return f;
	},

	startMonitoring : function(start)
	{
		start = (start === false ? false : start === true ? true : "Y");
		if (start)
		{
			if (start === true || !this.startMonitoringStatus)
			{
				if (this.startMonitoringStatus)
					clearTimeout(this.startMonitoringStatus);
				this.startMonitoringStatus = setTimeout(BX.delegate(function() {this.checkFilesInText();}, this), 1000);
			}
		}
		else if (this.startMonitoringStatus)
		{
			clearTimeout(this.startMonitoringStatus);
			this.startMonitoringStatus = null;
		}
	},

	checkFilesInText: function()
	{
		var result = false;
		for (var id in this.arFiles)
		{
			if (this.checkFileInText(this.arFiles[id]))
				result = true;
		}
		this.startMonitoring(result);
	},

	checkFileInText : function(file, reallyInText, parent)
	{
		if (!file)
			return null;
		parent = BX.findChild((!!parent ? parent : BX('wd-doc'+file["id"])), {'className': 'files-info'}, true, false);

		if (reallyInText !== true)
		{
			if (this.oEditor.sEditorMode == "code")
			{
				var
					text = this.oEditor.GetCodeEditorContent(),
					text1 = text.replace(
						this.parser[file["parser"]]["regexp"],
						function(str, id, width, height)
						{
							if (file["id"] == id)
							{
								str = str.replace(id, "__" + id + "__");
							}
							return str;
						}
					);
				reallyInText = (text != text1);
			}
			else if (this.oEditor.bxTags)
			{
				for (var ii in this.oEditor.bxTags)
				{
					if (!!this.oEditor.bxTags[ii] &&
						this.oEditor.bxTags[ii]["tag"] == file["parser"] &&
						this.oEditor.bxTags[ii]["params"]["value"] == file["id"])
					{
						if (this.oEditor.pEditorDocument.getElementById(this.oEditor.bxTags[ii]["id"]))
						{
							reallyInText = true;
							break;
						}
						else
						{
							this.oEditor.bxTags[ii] = null;
						}
					}
				}
			}
		}
		reallyInText = (reallyInText === true || reallyInText === false ? reallyInText : false);
		if (BX.type.isDomNode(parent))
		{
			var insertBtn = BX.findChild(parent, {'className': 'insert-btn'}, true, false),
				insertText = BX.findChild(parent, {'className': 'insert-text'}, true, false);
			if (reallyInText)
			{
				parent.setAttribute("tagInText", true);
				if (!insertText)
				{
					parent.appendChild(
						BX.create('SPAN', {
								'props' : {
									'className' : 'insert-text'
								},
								'html' : BX.message("MPF_FILE_IN_TEXT")
							}
						)
					);
				}
				if (!!insertBtn)
					insertBtn.parentNode.removeChild(insertBtn);
			}
			else
			{
				parent.setAttribute("tagInText", false);
				if (!insertBtn)
				{
					parent.appendChild(
						BX.create('SPAN', {
								'props' : {
									'className' : 'insert-btn'
								},
								'html' : BX.message("MPF_FILE_INSERT_IN_TEXT"),
								'events' : {
									'click' : BX.delegate(function(){this.insertFile(file["~id"]);}, this)
								}
							}
						)
					);
				}
				if (!!insertText)
					insertText.parentNode.removeChild(insertText);
			}
		}
		if (reallyInText)
			this.startMonitoring();
		return reallyInText;
	},

	checkFile : function(id, result, isNew)
	{
		isNew = (!!isNew);
		if (typeof result == "object" && result !== null)
		{
			id = parseInt(id);

			if (!result.element_content_type && !!result.element_name)
				result.element_content_type = (/(\.png|\.jpg|\.jpeg|\.gif|\.bmp)$/i.test(result.element_name) ? 'image/xyz' : 'isnotimage/xyz');

			if (isNew === true && (result.storage == 'bfile' || !result.storage))
				id = id + this.sNewFilePostfix;
			result.isImage = (!!result.isImage ? result.isImage : (result.element_content_type ? (result.element_content_type.indexOf('image') === 0) : false));
			if (result.isImage && result.storage == 'webdav' && !!this.arSize && !!result.element_url)
			{
				result.element_thumbnail = result.element_url + (result.element_url.indexOf("?") < 0 ? "?" : "&") +
					"width=" + this.arSize.width + "&height=" + this.arSize.height;
			}
			if (!result.element_thumbnail && !result.element_url && !!result.src)
				result.element_thumbnail = result.src;
			if (!result.element_image && !!result.thumbnail)
				result.element_image = result.thumbnail;

			var res = {
				id : id,
				name : (!!result.element_name ? result.element_name : 'noname'),
				size: result.element_size,
				url: result.element_url,
				parser: (!!result['parser'] ? result['parser'] : false),
				type: result.element_content_type,
				src: (!!result.element_thumbnail ? result.element_thumbnail : result.element_url),
				lowsrc: (!!result.lowsrc ? result.lowsrc : ''),
				thumbnail: result.element_image,
				isImage: result.isImage,
				storage: result.storage
			};
			if (res.isImage && parseInt(result.width) > 0 && parseInt(result.height) > 0)
			{
				res.width = parseInt(result.width);
				res.height = parseInt(result.height);
				if (!!this.arSize) {
					var width = res.width, height = res.height,
						ResizeCoeff = {
							width : (this.arSize["width"] > 0 ? this.arSize["width"] / width : 1),
							height : (this.arSize["height"] > 0 ? this.arSize["height"] / height : 1)},
						iResizeCoeff = Math.min(ResizeCoeff["width"], ResizeCoeff["height"]);
					iResizeCoeff = ((0 < iResizeCoeff) && (iResizeCoeff < 1) ? iResizeCoeff : 1);
					res.width = Math.max(1, parseInt(iResizeCoeff * res.width));
					res.height = Math.max(1, parseInt(iResizeCoeff * res.height));
				}
			}

			if (res['isImage'] && !res['src'])
				res = false;
			else if (!res['parser'])
			{
				if (res.storage == 'webdav' && this.parser['postdocument']['exist']) {
					res['parser'] = 'postdocument';
				} else {
					res['storage'] = 'bfile';
					res['parser'] = ((res['isImage'] && this.parser['postimage']['exist']) ? 'postimage' : (this.parser['postfile']['exist'] ? 'postfile' : false));
				}
			}

			if (!!res && !!res["parser"]) {
				if (res.storage == 'bfile') {
					this.arFiles['' + id] = res;
					this.arFiles['' + id]["~id"] = '' + id;
				}
				this.arFiles[res['parser'] + id] = res;
				this.arFiles[res['parser'] + id]["~id"] = res['parser'] + id;
				return (res['parser'] + id);
			}
		}
		return (typeof this.arFiles[id] == "object" && this.arFiles[id] !== null ? this.arFiles[id] : false);
	},

	insertFile : function (id, width)
	{
		var file = this.checkFile(id);
		if (!this.oEditor || !file)
			return false;

		var fileID = file['id'],
			params = '',
			pattern = this.parser[file['parser']][this.oEditor.sEditorMode == 'html' ? "html" : "code"];

		if (file['isImage'])
		{
			pattern = (this.oEditor.sEditorMode == "html" ? this.parser["postimage"]["html"] : pattern);
			if (file.width > 0 && file.height > 0 && this.oEditor.sEditorMode == "html" )
				params = ' style="width:' + file.width + 'px;height:' + file.height + 'px;" onload="this.style=\' \'"';
		}

		if (this.oEditor.sEditorMode == 'code' && this.oEditor.bBBCode) // BB Codes
			this.oEditor.WrapWith(" ", "", pattern.replace("#ID#", fileID).replace("#ADDITIONAL#", ""));
		else if(this.oEditor.sEditorMode == 'html') // WYSIWYG
		{

			this.oEditor.InsertHTML(' ' + pattern.
				replace("#ID#", this.oEditor.SetBxTag(false, {'tag': file.parser, params: {'value' : fileID}})).
				replace("#SRC#", file.src).replace("#URL#", file.url).
				replace("#LOWSRC#", (!!file.lowsrc ? file.lowsrc : '')).
				replace("#NAME#", file.name).replace("#ADDITIONAL#", params)
			);
			setTimeout(BX.delegate(this.oEditor.AutoResize, this.oEditor), 500);
		}
		this.checkFileInText(file, true);
	},

	deleteFile: function(id, url, el, storage)
	{
		id  = id + '';
		storage = (storage != 'webdav' && storage != 'bfile' ? 'bfile' : storage);
		if (typeof url == "string")
		{
			BX.remove(el.parentNode);
			BX.ajax.get(url, function(data){});
		}
		this.oEditor.SaveContent();
		var
			content = this.oEditor.GetContent();

		if (storage == 'bfile') {
			content = content.
				replace(new RegExp('\\[IMG ID='+ id +'\\]','g'), '').
				replace(new RegExp('\\[FILE ID='+ id +'\\]','g'), '').
				replace(new RegExp('\\[IMG ID='+ id + this.sNewFilePostfix +'\\]','g'), '').
				replace(new RegExp('\\[FILE ID='+ id + this.sNewFilePostfix +'\\]','g'), '');
		} else {
			content = content.replace(new RegExp('\\[DOCUMENT ID='+ id +'\\]','g'), '');
		}

		this.oEditor.SetContent(content);
		this.oEditor.SetEditorContent(this.oEditor.content);
		this.oEditor.SetFocus();
		this.oEditor.AutoResize();
		this.arFiles[id] = false;
	},

	makeButton : function (oldb, newb)
	{
		var el = false;
		if (!this.makeButtonOldBParentNode) {
			el = BX.findChild(BX(this.formID, true), {'attr': {'id': oldb}}, true, false);
			this.makeButtonOldBParentNode = el.parentNode.parentNode;
		} else {
			el = BX.findChild(this.makeButtonOldBParentNode, {'attr': {'id': oldb}}, true, false);
			if (!el)
				el = BX.findChild(BX(this.formID, true), {'attr': {'id': oldb}}, true, false);
		}
		BX.remove(BX.findParent(el), true);
		BX(newb).appendChild(el);
		el.style.backgroundImage = 'url(/bitrix/images/1.gif)';
		el.src = '/bitrix/images/1.gif';
		el.style.width = '25px';
		el.style.height = '25px';
		el.onmouseout = '';
		el.onmouseover = '';
		el.className = '';
	},

	Parse : function(sName, sContent, pLEditor, parser)
	{
		this.parser[parser]['exist'] = true;
		var
			arParser = this.parser[parser],
			obj = this;
		if (!!arParser)
		{
			sContent = sContent.replace(
				arParser['regexp'],
				function(str, id, width, height)
				{
					var res = "", strAdditional = "",
						file = obj.checkFile(arParser["tag"] + id),
						template = (file.isImage ? obj['parser']['postimage']['html'] : arParser.html);
					if (!!file)
					{
						if (file.isImage)
						{
							width = parseInt(width); height = parseInt(height);
							strAdditional = ((width && height && pLEditor.bBBParseImageSize) ?
								(" width=\"" + width + "\" height=\"" + height + "\"") : "");
							if (strAdditional === "" && file["width"] > 0 && file["height"] > 0)
								strAdditional = ' style="width:' + file["width"] + 'px;height:' + file["height"] + 'px;" onload="this.style=\' \'"';
						}

						return template.
							replace("#ID#", pLEditor.SetBxTag(false, {tag: arParser["tag"], params: {value : id}})).
							replace("#NAME#", file['name']).
							replace("#SRC#", file['src']).
							replace("#ADDITIONAL#", strAdditional).
							replace("#WIDTH#", parseInt(width)).
							replace("#HEIGHT#", parseInt(height));
					}
					return str;
				}
			)
		}
		return sContent;
	},

	Unparse: function(bxTag, pNode, pLEditor, parser)
	{
		this.parser[parser]['exist'] = true;
		var res = "";
		if (bxTag.tag == parser)
		{
			var
				width = parseInt(pNode.arAttributes['width']),
				height = parseInt(pNode.arAttributes['height']),
				strSize = "";

			if (width && height  && pLEditor.bBBParseImageSize)
				strSize = ' WIDTH=' + width + ' HEIGHT=' + height;

			res = this.parser[parser]["code"].
				replace("#ID#", bxTag.params.value).
				replace("#ADDITIONAL#", strSize).
				replace("#WIDTH#", width).
				replace("#HEIGHT#", height);
		}
		return res;
	},

	OnShowLHE : function(show)
	{
		var lheName = this.oEditorName, lheId = this.oEditorId;
		show = (show === false ? false : (show === 'hide' ? 'hide' : (show === 'justShow' ? 'justShow' : true)));

		if (!this.oEditor)
		{
			BX.addCustomEvent(window, 'LHE_OnInit', function(pEditor) { if (pEditor.id == lheId){ pEditor.SetFocus(); } } );
			if (!!window['LoadLHE_' + lheId])
				window['LoadLHE_' + lheId]();
		}
		else if (show)
		{
			this.oEditor.SetFocus();
		}

		var micro = BX('micro' + lheName), div = this.eventNode;

		if (!!micro)
			micro.style.display = ((show === true || show === 'justShow') ? "none" : "block");

		if (show == 'hide')
		{
			BX.onCustomEvent(this.eventNode, 'OnBeforeHideLHE', [show, this]);
			if (this.eventNode.style.display == "none") {
				BX.onCustomEvent(this.eventNode, 'OnAfterHideLHE', [show, this]);
			} else {
				(new BX.easing({
					duration : 200,
					start : { opacity: 100, height : this.eventNode.scrollHeight},
					finish : { opacity : 0, height : 20},
					transition : BX.easing.makeEaseOut(BX.easing.transitions.quad),
					step : function(state) {
						div.style.height = state.height + "px";
						div.style.opacity = state.opacity / 100; },
					complete : BX.proxy(function(){
						this.eventNode.style.cssText = "";
						this.eventNode.style.display = "none";
						BX.onCustomEvent(div, 'OnAfterHideLHE', [show, this]); }, this)
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
			}
			else if (this.eventNode.style.display == "block") {
				BX.onCustomEvent(this.eventNode, 'OnAfterShowLHE', [show, this]);
			} else {
				BX.adjust(this.eventNode, {style:{display:"block", overflow:"hidden", height:"20px", opacity:0.1}});
				(new BX.easing({
					duration : 200,
					start : { opacity : 10, height : 20 },
					finish : { opacity: 100, height : div.scrollHeight},
					transition : BX.easing.makeEaseOut(BX.easing.transitions.quad),
					step : function(state) {
						div.style.height = state.height + "px";
						div.style.opacity = state.opacity / 100; },
					complete : BX.proxy(function(){
						BX.onCustomEvent(div, 'OnAfterShowLHE', [show, this]);
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
		if (type == 'cancel')
		{
			BX.onCustomEvent(this.eventNode, 'OnClickCancel', [this]);
			BX.onCustomEvent(this.eventNode, 'OnShowLHE', ['hide']);
		}
		else
		{
			BX.onCustomEvent(this.eventNode, 'OnClickSubmit', [this]);
		}
		return;
	}
}

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

window.__LHE_OnBeforeParsersInit = function(pEditor)
{
	if (!(window[pEditor.id + 'Settings'] && window[pEditor.id + 'Settings']['parsers']))
		return false;

	pEditor.formID = window[pEditor.id + 'Settings']['formID'];
	var parsers = window[pEditor.id + 'Settings']['parsers'];

	if (BX.util.in_array('postimage', parsers))
	{
		pEditor.AddParser(
			{
				name: 'postimage',
				obj: {
					Parse: function(sName, sContent, pLEditor)
					{
						return window['PlEditor' + pLEditor.formID].Parse(sName, sContent, pLEditor, "postimage");
					},
					UnParse: function(bxTag, pNode, pLEditor)
					{
						return window['PlEditor' + pLEditor.formID].Unparse(bxTag, pNode, pLEditor, "postimage");
					}
				}
			}
		);
	}
	if (BX.util.in_array('postfile', parsers))
	{
		pEditor.AddParser(
			{
				name: 'postfile',
				obj: {
					Parse: function(sName, sContent, pLEditor)
					{
						var edId = 'PlEditor' + pLEditor.formID;
						return window[edId].Parse(sName, sContent, pLEditor, "postfile");
					},
					UnParse: function(bxTag, pNode, pLEditor)
					{
						var edId = 'PlEditor' + pLEditor.formID;
						return window[edId].Unparse(bxTag, pNode, pLEditor, "postfile");
					}
				}
			}
		);
	}
//	if (BX.util.in_array('postdocument', parsers))
	{
		pEditor.AddParser(
			{
				name: 'postdocument',
				obj: {
					Parse: function(sName, sContent, pLEditor)
					{
						var edId = 'PlEditor' + pLEditor.formID;
						return window[edId].Parse(sName, sContent, pLEditor, "postdocument");
					},
					UnParse: function(bxTag, pNode, pLEditor)
					{
						var edId = 'PlEditor' + pLEditor.formID;
						return window[edId].Unparse(bxTag, pNode, pLEditor, "postdocument");
					}
				}
			}
		);
	}
	if (BX.util.in_array('postuser', parsers))
	{
		pEditor.AddParser(
			{
				name: 'postuser',
				obj: {
					Parse: function(sName, sContent, pLEditor)
					{
						sContent = sContent.replace(/\[USER\s*=\s*(\d+)\]((?:\s|\S)*?)\[\/USER\]/ig, function(str, id, name)
						{
							id = parseInt(id);
							name = BX.util.trim(name);

							return '<span id="' + pLEditor.SetBxTag(false, {tag: "postuser", params: {value : id}}) +
								'" style="color: #2067B0; border-bottom: 1px dashed #2067B0;">' + name + '</span>';
						});
						return sContent;
					},
					UnParse: function(bxTag, pNode, pLEditor)
					{
						if (bxTag.tag == 'postuser')
						{
							var name = '';
							for (var i = 0; i < pNode.arNodes.length; i++)
								name += pLEditor._RecursiveGetHTML(pNode.arNodes[i]);
							name = BX.util.trim(name);
							return "[USER=" + bxTag.params.value + "]" + name +"[/USER]";
						}
						return "";
					}
				}
			}
		);
	}
	window[pEditor.id + 'Settings']['parsers'] = false;
}
window.__LHE_OnInit = function(pEditor)
{
	if (!window[pEditor.id + 'Settings'])
		return false;
	pEditor.arFiles = window[pEditor.id + 'Settings']['arFiles'];
	pEditor.arFiles = (!!pEditor.arFiles && pEditor.arFiles.length > 0 ? pEditor.arFiles : []);

	var
		formID = window[pEditor.id + 'Settings']['formID'],
		objName = window[pEditor.id + 'Settings']['objName'];


	if(!(BX.browser.IsIE() && !BX.browser.IsIE9()))
	{
		BX.addCustomEvent(
			pEditor,
			'OnDocumentKeyDown',
			function(e){if (!!window["bxPFParser"]) { window["bxPFParser"](e, pEditor, formID); } } );
	}

	if (BX.util.in_array("CreateLink", window[pEditor.id + 'Settings']['buttons']))
		window[objName]['makeButton']('lhe_btn_createlink', 'bx-b-link-' + formID);

	if (BX.util.in_array("InputVideo", window[pEditor.id + 'Settings']['buttons']))
		window[objName]['makeButton']('lhe_btn_inputvideo', 'bx-b-video-' + formID);

	if (BX.util.in_array("Quote", window[pEditor.id + 'Settings']['buttons']))
		window[objName]['makeButton']('lhe_btn_quote', 'bx-b-quote-' + formID);

	var el = BX.findChild(BX(formID), {'attr': {id: 'bx-panel-close'}}, true, false);
	if (el)
	{
		BX.findChild(BX(formID), {'className': /lhe-stat-toolbar-cont/ }, true, false).appendChild(el);
	}
	window[objName].showPanelEditor(window[pEditor.id + 'Settings']['showEditor'], pEditor);
	window[pEditor.id + 'Settings'] = false;
}

window.CustomizeLightEditor = function(editorId, params)
{
	// Rename image button and change Icon
	window.LHEButtons['Image'].id = 'ImageLink';
	window.LHEButtons['Image'].src = params.path + '/images/lhelink_image.gif';
	window.LHEButtons['Image'].name = params.imageLinkText;

	if (!(window[editorId + 'Settings'] && window[editorId + 'Settings']['parsers']))
		return false;

	window.LHEButtons['Spoiler'] = {
		id : 'Spoiler',
		name : params.spoilerText,
		src : params.path + '/images/lhespoiler.png',
		OnBeforeCreate: function(pLEditor, pBut)
		{
			// Disable in non BBCode mode in html
			pBut.disableOnCodeView = !pLEditor.bBBCode || pLEditor.arConfig.bConvertContentFromBBCodes;

			pLEditor.systemCSS += "blockquote.bx-spoiler {border: 1px solid #C0C0C0!important; background: #fff4ca; background-url: none; padding: 4px 4px 4px 24px; color: #373737!important;}\n";
			return pBut;
		},
		handler: function(pBut)
		{
			if (pBut.pLEditor.arConfig.bQuoteFromSelection)
			{
				var res;
				if (document.selection && document.selection.createRange)
					res = document.selection.createRange().text;
				else if (window.getSelection)
					res = window.getSelection().toString();
				res = BX.util.htmlspecialchars(res);
				res = res.replace(/\n/g, '<br />');

				var strId = '';
				if (!pBut.pLEditor.bBBCode)
					strId = " id\"=" + pBut.pLEditor.SetBxTag(false, {tag: "spoiler"}) + "\"";

				if (res && res.length > 0)
					return pBut.pLEditor.InsertHTML('<blockquote class="bx-spoiler"' + strId + ">" + res + "</blockquote><br/>");
			}

			// Catch all blockquotes
			var
				arBQ = pBut.pLEditor.pEditorDocument.getElementsByTagName("blockquote"),
				i, l = arBQ.length;

			// Set specific name to nodes
			for (i = 0; i < l; i++)
				arBQ[i].name = "__bx_temp_spoiler";

			// Create new qoute
			pBut.pLEditor.executeCommand('Indent');

			// Search for created node and try to adjust new style end id
			setTimeout(function(){
				var
					arNewBQ = pBut.pLEditor.pEditorDocument.getElementsByTagName("blockquote"),
					i, l = arNewBQ.length;

				for (i = 0; i < l; i++)
				{
					if (arBQ[i].name == "__bx_temp_spoiler")
					{
						arBQ[i].removeAttribute("name");
					}
					else
					{
						arBQ[i].className = "bx-spoiler";
						arBQ[i].id = pBut.pLEditor.SetBxTag(false, {tag: "spoiler"});
					}
					try{arBQ[i].setAttribute("style", '');}catch(e){}

					if (!arBQ[i].nextSibling)
						arBQ[i].parentNode.appendChild(BX.create("BR", {}, pBut.pLEditor.pEditorDocument));

					if (arBQ[i].previousSibling && arBQ[i].previousSibling.nodeName && arBQ[i].previousSibling.nodeName.toLowerCase() == 'blockquote')
						arBQ[i].parentNode.insertBefore(BX.create("BR", {}, pBut.pLEditor.pEditorDocument), arBQ[i]);
				}
			}, 10);
		},
		bbHandler: function(pBut)
		{
			if (pBut.pLEditor.arConfig.bQuoteFromSelection)
			{
				var res = null;
				if (document.selection && document.selection.createRange)
					res = document.selection.createRange().text;
				else if (window.getSelection)
					res = window.getSelection().toString();

				if (res && res.length > 0)
					return pBut.pLEditor.WrapWith('[SPOILER]', '[/SPOILER]', res);
			}

			pBut.pLEditor.FormatBB({tag: 'SPOILER', pBut: pBut});
		},
		parser: {
			name: 'spoiler',
			obj: {
				Parse: function(sName, sContent, pLEditor)
				{
					if (/\[(cut|spoiler)(([^\]])*)\]/gi.test(sContent))
					{
						sContent = sContent.
							replace(/[\001-\006]/gi, '').
							replace(/\[cut(((?:\=)[^\]]*)|)\]/gi, '\001$1\001').
							replace(/\[\/cut]/gi, '\002').
							replace(/\[spoiler([^\]]*)\]/gi, '\003$1\003').
							replace(/\[\/spoiler]/gi, '\004');
						var
							reg1 = /(?:\001([^\001]*)\001)([^\001-\004]+)\002/gi,
							reg2 = /(?:\003([^\003]*)\003)([^\001-\004]+)\004/gi,
							__replace_reg = function(title, body){
								title = title.replace(/^(\=\"|\=\'|\=)/gi, '').replace(/(\"|\')?$/gi, '');
								return '<blockquote class="bx-spoiler" id="' + pLEditor.SetBxTag(false, {tag: "spoiler"}) + '" title="' + title + '">' + body + '</blockquote>';
							},
							func = function(str, title, body){return __replace_reg(title, body);};
						while (sContent.match(reg1) || sContent.match(reg2))
						{
							sContent = sContent.
								replace(reg1, func).
								replace(reg2, func);
						}
					}
					sContent = sContent.
						replace(/\001([^\001]*)\001/gi, '[cut$1]').
						replace(/\003([^\003]*)\003/gi, '[spoiler$1]').
						replace(/\002/gi, '[/cut]').
						replace(/\004/gi, '[/spoiler]');
					return sContent;
				},
				UnParse: function(bxTag, pNode, pLEditor)
				{
					if (bxTag.tag == 'spoiler')
					{
						var i, l = pNode.arNodes.length, res = "[SPOILER" + (pNode.arAttributes["title"] ? "="+pNode.arAttributes["title"] : "") + "]";
						for (i = 0; i < l; i++)
							res += pLEditor._RecursiveGetHTML(pNode.arNodes[i]);
						res += "[/SPOILER]";
						return res;
					}
					return "";
				}
			}
		}
	};
	window.LHEButtons['InputVideo'] = {
		id : 'InputVideo',
		src : params.path + '/images/lhevideo.gif',
		name : params.videoText,
		handler: function(pBut)
		{
			pBut.pLEditor.OpenDialog({id : 'InputVideo', obj: false});
		},
		OnBeforeCreate: function(pLEditor, pBut)
		{
			// Disable in non BBCode mode in html
			pBut.disableOnCodeView = !pLEditor.bBBCode || pLEditor.arConfig.bConvertContentFromBBCodes;
			return pBut;
		},
		parser: {
			name: 'postvideo',
			obj: {
				Parse: function(sName, sContent, pLEditor)
				{
					sContent = sContent.replace(/\[VIDEO\s*?width=(\d+)\s*?height=(\d+)\s*\]((?:\s|\S)*?)\[\/VIDEO\]/ig, function(str, w, h, src)
					{
						w = parseInt(w) || 400;
						h = parseInt(h) || 300;
						src = BX.util.trim(src);

						return '<img id="' + pLEditor.SetBxTag(false, {tag: "postvideo", params: {value : src}}) +
							'" src="/bitrix/images/1.gif" class="bxed-video" width=' + w + ' height=' + h + ' title="' + BX.message.Video + ": " + src + '" />';
					});
					return sContent;
				},
				UnParse: function(bxTag, pNode, pLEditor)
				{
					if (bxTag.tag == 'postvideo')
					{
						return "[VIDEO WIDTH=" + pNode.arAttributes["width"] + " HEIGHT=" + pNode.arAttributes["height"] + "]" + bxTag.params.value + "[/VIDEO]";
					}
					return "";
				}
			}
		}
	};
	window.LHEDailogs['InputVideo'] = function(pObj){
		var str = '<table width="100%"><tr>' +
			'<td class="lhe-dialog-label lhe-label-imp"><label for="' + pObj.pLEditor.id + 'lhed_post_video_path"><b>' + params.videoUploadText + ':</b></label></td>' +
			'<td class="lhe-dialog-param">' +
			'<input id="' + pObj.pLEditor.id + 'lhed_post_video_path" value="" size="30"/>' +
			'</td>' +
			'</tr><tr>' +
			'<td></td>' +
			'<td style="padding: 0!important; font-size: 11px!important;">' + params.videoUploadText1 + '</td>' +
			'</tr><tr>' +
			'<td class="lhe-dialog-label lhe-label-imp"><label for="' + pObj.pLEditor.id + 'lhed_post_video_width">' + params.videoUploadText3 + ':</label></td>' +
			'<td class="lhe-dialog-param">' +
			'<input id="' + pObj.pLEditor.id + 'lhed_post_video_width" value="" size="4"/>' +
			' x ' +
			'<input id="' + pObj.pLEditor.id + 'lhed_post_video_height" value="" size="4" />' +
			'</td>' +
			'</tr></table>';

		return {
			title: params.videoUploadText2,
			innerHTML : str,
			width: 480,
			OnLoad: function()
			{
				pObj.pPath = BX(pObj.pLEditor.id + "lhed_post_video_path");
				pObj.pWidth = BX(pObj.pLEditor.id + "lhed_post_video_width");
				pObj.pHeight = BX(pObj.pLEditor.id + "lhed_post_video_height");

				pObj.pLEditor.focus(pObj.pPath);
			},
			OnSave: function()
			{
				var
					src = BX.util.trim(pObj.pPath.value),
					w = parseInt(pObj.pWidth.value) || 400,
					h = parseInt(pObj.pHeight.value) || 300;

				if (src === "")
					return;

				if (pObj.pLEditor.sEditorMode == 'code' && pObj.pLEditor.bBBCode) // BB Codes
				{
					pObj.pLEditor.WrapWith("", "", "[VIDEO WIDTH=" + w + " HEIGHT=" + h + "]" + src + "[/VIDEO]");
				}
				else if(pObj.pLEditor.sEditorMode == 'html') // WYSIWYG
				{
					pObj.pLEditor.InsertHTML('<img id="' + pObj.pLEditor.SetBxTag(false, {tag: "postvideo", params: {value : src}}) + '" src="/bitrix/images/1.gif" class="bxed-video" width=' + w + ' height=' + h + ' title="' + BX.message.Video + ": " + src + '" />');
					pObj.pLEditor.AutoResize();
				}
			}
		};
	};
}

	var lastWaitElement = null;
	window.MPFbuttonShowWait = function(el)
	{
		if (el && !BX.type.isElementNode(el))
			el = null;
		el = el || this;
		el = (!!el ? (el.tagName == "A" ? el : el.parentNode) : el);
		if (!!el && (!!el.lastChild && !el.lastChild.hasAttribute("bx-mpf-fill") || !el.lastChild))
		{
			BX.adjust(el, {
				style : { position : "relative"},
				children : [BX.create('SPAN', {
					attrs : { "class" : "mpf-load", "bx-mpf-fill" : "mpf-load-img"},
					style : { position: "absolute", top : 0, left : 0, width: "100%"},
					children : [
						BX.create('DIV', {
							attrs : { "className" : "mpf-load-img" },
							style : { position: "absolute", top : 0, left : 0, width: "100%" }
						})
					]
				})]
			});
			lastWaitElement = el;
			BX.defer(function(){el.disabled = true})();
		}
	}

	window.MPFbuttonCloseWait = function(el)
	{
		if (el && !BX.type.isElementNode(el))
			el = null;
		el = el || lastWaitElement || this;

		if (!!el && !!el.lastChild && el.lastChild.hasAttribute("bx-mpf-fill"))
		{
			el.disabled = false ;
			BX.removeClass(el, 'feed-add-button-press');
			el.style.cssText = '';
			BX.remove(el.lastChild);
			lastWaitElement = null;
		}
	}

	window.__mpf_wd_getinfofromnode = function(result, obj)
	{
		var preview = BX.findChild(BX('wd-doc' + result.element_id), {'className': 'files-preview', 'tagName' : 'IMG'}, true, false);
		if (!!preview) {
			result.lowsrc = preview.src;
			result.element_url = preview.src.replace(/\Wwidth\=(\d+)/, '').replace(/\Wheight\=(\d+)/, '');
			result.width = parseInt(preview.getAttribute("data-bx-full-width"));
			result.height = parseInt(preview.getAttribute("data-bx-full-height"));
		} else if (!!obj.urlGet) {
			result.element_url = obj.urlGet.
				replace("#element_id#", result.element_id).
				replace("#ELEMENT_ID#", result.element_id).
				replace("#element_name#", result.element_name).
				replace("#ELEMENT_NAME#", result.element_name);
		}
	}

	window.MPFSmileMenu = function()
	{
		this.popupPopupMenu = null;

		this.popupSmileMenu = null;
		this.popupSmileMenuGallery = null;
		this.popupSmileMenuSet = null;

		this.pEditor = null;
	}
	window.MPFSmileMenu.prototype.mpfOpenSmileMenu = function()
	{
		if (!BX.proxy_context)
			return false;

		if (this.popupPopupMenu !== null)
			this.popupPopupMenu.destroy();

		if (this.popupSmileMenu !== null)
			this.popupSmileMenu.destroy();

		if(BX('bx-popup-smile'))
			BX.remove(BX('bx-popup-smile'));


		var arGalleryItem = {};
		for (var id in this.smile)
		{
			if(!this.smile[id].SET_ID)
				this.smile[id].SET_ID = 1;

			if (!arGalleryItem[this.smile[id].SET_ID])
				arGalleryItem[this.smile[id].SET_ID] = [];

			var arAttrs = { 'data-code': BX.util.htmlspecialcharsback(this.smile[id].code), src : this.smile[id].path, alt : this.smile[id].code, title : BX.util.htmlspecialcharsback(this.smile[id].name)};
			if(this.smile[id].width)
				arAttrs.style = "width: "+this.smile[id].width+"px; height: "+this.smile[id].height+"px";

			arGalleryItem[this.smile[id].SET_ID].push(BX.create("img", { props : { className : 'bx-smile-gallery-image'}, attrs : arAttrs}));
		}

		var setCount = 0;
		var arGallery = [];
		var arSet = [
			BX.create("span", { props : { className : "bx-smile-nav-name" }, html: BX.message('MPF_SMILE_SET')})
		];
		for (id in this.smileSet)
		{
			if (!arGalleryItem[id])
				continue;

			setCount++;
			arGallery.push(
				BX.create("span", { attrs : { 'data-set-id': id }, props : { className : "bx-smile-gallery-set"+(setCount > 1? ' bx-smile-gallery-set-hide': '') }, children: arGalleryItem[id]})
			);
			arSet.push(
				BX.create("span", { attrs : { 'data-set-id': id, title : BX.util.htmlspecialcharsback(this.smileSet[id].NAME) }, props : { className : "bx-smile-nav-item"+(setCount == 1? ' bx-smile-nav-item-active': '')}})
			);
		}

		this.popupSmileMenu = new BX.PopupWindow('bx-popup-smile', BX.proxy_context, {
			lightShadow : false,
			offsetTop: 5,
			offsetLeft: 8,
			autoHide: true,
			closeByEsc: true,
			bindOptions: {position: "bottom"},
			zIndex: 200,
			events : {
				onPopupClose : function() { this.destroy() },
				onPopupDestroy : BX.delegate(function() { this.popupSmileMenu = null; }, this)
			},
			content : BX.create("div", { props : { className : "bx-smile-f" }, children: [
				this.popupSmileMenuGallery = BX.create("div", { props : { className : "bx-smile-gallery" }, children: arGallery}),
				this.popupSmileMenuSet = BX.create("div", { props : { className : "bx-smile-nav"+(setCount <= 1? " bx-smile-nav-disabled": "")}, children: arSet})
			]})
		});
		this.popupSmileMenu.setAngle({offset: 4});
		this.popupSmileMenu.show();

		BX.bindDelegate(this.popupSmileMenuGallery, "click", {className: 'bx-smile-gallery-image'}, BX.delegate(function(e){
			if(this.pEditor)
			{
				var oSmile = null;
				for (var id in this.smile)
				{
					if(this.smile[id].code == BX.proxy_context.getAttribute('data-code'))
					{
						oSmile = this.smile[id];
					}
				}

				if(oSmile)
				{
					if (this.pEditor.sEditorMode == 'code') // In BB or in HTML
						this.pEditor.WrapWith(false, false, oSmile.code);
					else // WYSIWYG
						this.pEditor.InsertHTML('<img id="' + this.pEditor.SetBxTag(false, {tag: "smile", params: oSmile}) + '" src="' + oSmile.path + '" title="' + oSmile.name + '"/>');
				}
			}

			this.popupSmileMenu.close();
		}, this));

		BX.bindDelegate(this.popupSmileMenuSet, "click", {className: 'bx-smile-nav-item'}, BX.delegate(function(e){
			if (BX.hasClass(BX.proxy_context, 'bx-smile-nav-item-active'))
				return false;

			var nodesGallery = BX.findChildren(this.popupSmileMenuGallery, {className : "bx-smile-gallery-set"}, false);
			var nodesSet = BX.findChildren(this.popupSmileMenuSet, {className : "bx-smile-nav-item"}, false);
			for (var i = 0; i < nodesSet.length; i++)
			{
				if (BX.proxy_context == nodesSet[i])
				{
					BX.removeClass(nodesGallery[i], 'bx-smile-gallery-set-hide');
					BX.addClass(nodesSet[i], 'bx-smile-nav-item-active');
				}
				else
				{
					BX.addClass(nodesGallery[i], 'bx-smile-gallery-set-hide');
					BX.removeClass(nodesSet[i], 'bx-smile-nav-item-active');
				}
			}
		}, this));

		return false;
	}

	var MPFMention = { listen: false, plus : false, text : '' };

	window.BXfpdSetLinkName = function(name)
	{
		if (BX.SocNetLogDestination.getSelectedCount(name) <= 0)
			BX('bx-destination-tag').innerHTML = BX.message("BX_FPD_LINK_1");
		else
			BX('bx-destination-tag').innerHTML = BX.message("BX_FPD_LINK_2");
	}

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
	}

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
	}
	window.BXfpdOpenDialogCallback = function()
	{
		BX.style(BX('feed-add-post-destination-input-box'), 'display', 'inline-block');
		BX.style(BX('bx-destination-tag'), 'display', 'none');
		BX.focus(BX('feed-add-post-destination-input'));
	}

	window.BXfpdCloseDialogCallback = function()
	{
		if (!BX.SocNetLogDestination.isOpenSearch() && BX('feed-add-post-destination-input').value.length <= 0)
		{
			BX.style(BX('feed-add-post-destination-input-box'), 'display', 'none');
			BX.style(BX('bx-destination-tag'), 'display', 'inline-block');
			window.BXfpdDisableBackspace();
		}
	}

	window.BXfpdCloseSearchCallback = function()
	{
		if (!BX.SocNetLogDestination.isOpenSearch() && BX('feed-add-post-destination-input').value.length > 0)
		{
			BX.style(BX('feed-add-post-destination-input-box'), 'display', 'none');
			BX.style(BX('bx-destination-tag'), 'display', 'inline-block');
			BX('feed-add-post-destination-input').value = '';
			window.BXfpdDisableBackspace();
		}

	}
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
	}

	window.BXfpdSearchBefore = function(event)
	{
		if (event.keyCode == 8 && BX('feed-add-post-destination-input').value.length <= 0)
		{
			BX.SocNetLogDestination.sendEvent = false;
			BX.SocNetLogDestination.deleteLastItem(window.BXSocNetLogDestinationFormName);
		}

		return true;
	}
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
	}

	window.bxPFParser = function(e, eID, formID)
	{
		var pEditor = (typeof(eID) == 'string' ? window[eID] : eID);

		if (e.keyCode == 107 || (e.shiftKey || e.modifiers > 3) && BX.util.in_array(e.keyCode, [187, 50, 107, 43, 61]))
		{
			MPFMention["plus"] = false;
			setTimeout(function() {
				var r = pEditor.GetSelectionRange(),
					txt = (!!r ? r.endContainer.textContent : ''),
					determiner = (!!txt ? txt.slice(r.endOffset-1, r.endOffset) : ''),
					prevS = (!!txt ? txt.slice(r.endOffset-2, r.endOffset-1) : '');
				if (determiner == "@" || determiner == "+")
				{
					MPFMention["plus"] = (determiner == "+");
					if (!prevS || BX.util.in_array(prevS, ["+", "@", ",", "("]) || (prevS.length == 1 && BX.util.trim(prevS) === ""))
					{
						MPFMention["listen"] = true;
						MPFMention["text"] = '';
						if(!BX.SocNetLogDestination.isOpenDialog())
							BX.SocNetLogDestination.openDialog(window['BXSocNetLogDestinationFormNameMent' + formID]);
					}
				}
			}, 10);
		}

		if(MPFMention["listen"] === true)
		{
			if(e.keyCode == 8) // backspace
			{
				setTimeout(function(){
					var r = pEditor.GetSelectionRange(),
						txt = (!!r ? r.endContainer.textContent : '');
					if(txt === undefined || txt === null || txt.length === 0 || (txt.lastIndexOf("+", r.endOffset) == -1 && txt.lastIndexOf("@", r.endOffset) == -1))
						window['BXfpdStopMent' + formID]();
				}, 50);
			}
		}
		if(MPFMention["listen"] === true)
		{
			if(e.keyCode == 27) //ESC
			{
				window['BXfpdStopMent' + formID]();
			}
			else if(e.keyCode == 13) // enter
			{
				BX.PreventDefault(e);
				BX.SocNetLogDestination.selectFirstSearchItem(window['BXSocNetLogDestinationFormNameMent' + formID]);
			}
			else
			{
				setTimeout(function(){
					var r = pEditor.GetSelectionRange(),
						txt = (!!r ? r.endContainer.textContent : '');
					if(!!txt && txt.length > 0)
					{
						var txtPos = txt.lastIndexOf((MPFMention["plus"] ? "+" : "@"), r.endOffset) + 1,
							txt2 = txt.substr(txtPos, (r.endOffset - txtPos));
						if(txt2.length == 1 && BX.util.trim(txt2).length === 0)
						{
							window['BXfpdStopMent' + formID]();
						}
						else
						{
							MPFMention["text"] = txt2;
							BX.SocNetLogDestination.search(txt2, true, window['BXSocNetLogDestinationFormNameMent' + formID], BX.message("MPF_NAME_TEMPLATE"));
							if(BX.util.trim(txt2).length === 0)
							{
								window['BXfpdStopMent' + formID]();
								BX.SocNetLogDestination.openDialog(window['BXSocNetLogDestinationFormNameMent' + formID]);
							}
						}
					}
				}, 10);
			}
		}
	}

	window.BXfpdSelectCallbackMent = function (item, type, search, formID, editorName, bNeedComa)
	{
		if(type == 'users')
		{
			if(item.entityId > 0)
			{
				var pEditor = window[editorName];
				if(pEditor)
				{
					if (pEditor.sEditorMode == 'code' && pEditor.bBBCode) // BB Codes
					{
						pEditor.WrapWith("", "", "[USER=" + item.entityId + "]" + item.name + "[/USER]");
					}
					else if(pEditor.sEditorMode == 'html') // WYSIWYG
					{
						pEditor.SetFocus();
						var r = pEditor.GetSelectionRange();
						if (!r)
							return true;
						item.entityId = BX.util.htmlspecialchars(item.entityId);
						item.name = item.name.replace(/</gi, '&lt;').replace(/>/gi, '&gt;');
						var
							txt = (!!r ? r.endContainer.textContent : ''),
							txtPos = txt.lastIndexOf((MPFMention["plus"] ? "+" : "@"), r.endOffset);
						txtPos = (txtPos >= 0 ? txtPos : r.endContainer.length);
						txtPos = (txtPos >= 0 ? txtPos : 0);
						var txt2 = txt.substr(0, txtPos),
							adit = (txt2.length <= 0 ? '' : '&nbsp;'),
							id = ('nbsp' + new Date().getTime()).substr(10),
							spn,
							node = pEditor.pEditorDocument.getElementById('mentionDeterminer' + formID),
							htmlData = adit +
								'<span id="' + pEditor.SetBxTag(false, {'tag': "postuser", 'params': {'value' : item.entityId}}) +
								'" style="color: #2067B0; border-bottom: 1px dashed #2067B0;">' + item.name + '</span>' +
								(bNeedComa ? ', ' : '') + (BX.browser.IsIE() ? '<span id="' + id + '">&nbsp;</span>' : '&nbsp;');

						if (!!node) {
							spn = BX.create('SPAN', {attrs: {id : id, "bx-mpf-user" : item.entityId}, html : htmlData});
							node.parentNode.insertBefore(spn, node);
							BX.remove(pEditor.pEditorDocument.getElementById('mentionDeterminer' + formID));
						}
						else
						{
							var
								rng = pEditor.pEditorDocument.createRange(),
								txtPosEnd = txtPos + 1;
							if(MPFMention["text"].length > 0)
								txtPosEnd = MPFMention["text"].length + txtPosEnd;
							else if(txtPosEnd > r.endContainer.length)
								txtPosEnd = r.endContainer.length;

							rng.setStart(r.endContainer, txtPos);
							rng.setEnd(r.endContainer, txtPosEnd);

							pEditor.SelectRange(rng);
							pEditor.InsertHTML(htmlData);
						}
						pEditor.SetFocus();
						spn = pEditor.pEditorDocument.getElementById(id);
						r = pEditor.GetSelectionRange();
						if (!!spn && !!r)
						{
							r.setStartAfter(spn);
							r.setEndAfter(spn);
							r.collapse(false);
							pEditor.SelectRange(r);
						}
					}
				}

				delete BX.SocNetLogDestination.obItemsSelected[window['BXSocNetLogDestinationFormNameMent' + formID]][item.id];
				window['BXfpdStopMent' + formID]();
				MPFMention["text"] = '';

			}
		}
	}
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
	}

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
				'name' : window.BXSocNetLogDestinationFormName,
				'searchInput' : BX('feed-add-post-destination-input'),
				'extranetUser' :  params["extranetUser"],
				'bindMainPopup' : { 'node' : BX('feed-add-post-destination-container'), 'offsetTop' : '5px', 'offsetLeft': '15px'},
				'bindSearchPopup' : { 'node' : BX('feed-add-post-destination-container'), 'offsetTop' : '5px', 'offsetLeft': '15px'},
				'callback' : {
					'select' : window["BXfpdSelectCallback"],
					'unSelect' : window["BXfpdUnSelectCallback"],
					'openDialog' : window["BXfpdOpenDialogCallback"],
					'closeDialog' : window["BXfpdCloseDialogCallback"],
					'openSearch' : window["BXfpdOpenDialogCallback"],
					'closeSearch' : window["BXfpdCloseSearchCallback"]
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
			if (!!params["itemsHidden"])
			{
				for (var ii in params["itemsHidden"])
				{
					window.BXfpdSelectCallback({id:('SG'+params["itemsHidden"][ii]["ID"]), name:params["itemsHidden"][ii]["NAME"]}, 'sonetgroups', '', true);
				}
			}
			window.BXfpdSetLinkName(window.BXSocNetLogDestinationFormName);
		}
		window["BXfpdSelectCallbackMent" + formId] = function(item, type, search) {
			window.BXfpdSelectCallbackMent(item, type, search, formId, params["jsObjName"]);
		}
		window["BXfpdStopMent" + formId] = function () {
			clearTimeout(BX.SocNetLogDestination.searchTimeout);
			BX.SocNetLogDestination.closeDialog();
			BX.SocNetLogDestination.closeSearch();
		}

		window["BXSocNetLogDestinationFormNameMent" + formId] = 'mention' + ('' + new Date().getTime()).substr(5);
		window["BXSocNetLogDestinationDisableBackspace"] = null;
		var bxBMent = BX('bx-b-mention-' + formId);
		BX.SocNetLogDestination.init({
			'name' : window["BXSocNetLogDestinationFormNameMent" + formId],
			'searchInput' : bxBMent,
			'extranetUser' : params["extranetUser"],
			'bindMainPopup' :  { 'node' : bxBMent, 'offsetTop' : '1px', 'offsetLeft': '12px'},
			'bindSearchPopup' : { 'node' : bxBMent, 'offsetTop' : '1px', 'offsetLeft': '12px'},
			'callback' : {
				'select' : window["BXfpdSelectCallbackMent" + formId],
				'openDialog' : function() { MPFMention["listen"] = true; },
				'closeDialog' : function() { MPFMention["listen"] = false;
					if(window[params["jsObjName"]]){
						var node = window[params["jsObjName"]].pEditorDocument.getElementById('mentionDeterminer' + formId);
						while (!!node)
						{
							var txtNode = document.createTextNode(node.textContent);
							node.parentNode.insertBefore(txtNode, node);
							node.parentNode.removeChild(node);
							node = window[params["jsObjName"]].pEditorDocument.getElementById('mentionDeterminer' + formId);
						}
						window[params["jsObjName"]].SetFocus();
					}
				},
				'openSearch' : function() { MPFMention["listen"] = true; },
				'closeSearch' : function() { MPFMention["listen"] = false; }
			},
			'items' : {
				'users' : params["items"]["users"],
				'groups' : {},
				'sonetgroups' : {},
				'department' : params["items"]["department"],
				'departmentRelation' : params["items"]["departmentRelation"]
			},
			'itemsLast' : {
				'users' : window["lastUsers"],
				'sonetgroups' : {},
				'department' : {},
				'groups' : {}
			},
			'itemsSelected' : params["itemsSelected"],
			'departmentSelectDisable' : true,
			'obWindowClass' : 'bx-lm-mention',
			'obWindowCloseIcon' : false
		});

		BX.ready(function() {
				var ment = BX('bx-b-mention-' + formId);
				if(BX.browser.IsIE() && !BX.browser.IsIE9())
				{
					ment.style.width = '1px';
					ment.style.marginRight = '0';
				}
				else
				{
					BX.addCustomEvent(
						ment,
						'mentionClick',
						function(e){
							setTimeout(function()
							{
								MPFMention["plus"] = false;
								MPFMention["listen"] = true;
								MPFMention["text"] = '';

								if(!BX.SocNetLogDestination.isOpenDialog())
									BX.SocNetLogDestination.openDialog(window["BXSocNetLogDestinationFormNameMent" + formId]);
								window[params["jsObjName"]].SetFocus();
							}, 100);
						}
					);

					//mousedown for IE, that lost focus on button click
					BX.bind(
						ment,
						"mousedown",
						function(e)
						{
							if(MPFMention["listen"] !== true)
							{
								if(window[params["jsObjName"]].sEditorMode == 'html') // WYSIWYG
								{

									MPFMention["listen"] = true;
									MPFMention["text"] = '';
									MPFMention["plus"] = false;

									if(BX.browser.IsIE())
										window[params["jsObjName"]].InsertHTML('<span id="mentionDeterminer' + formId + '">@</span>');
									else
										window[params["jsObjName"]].InsertHTML('@');
								}
								BX.onCustomEvent(ment, 'mentionClick');
							}
						}
					);
				}
			}
		);
	}
})(window);