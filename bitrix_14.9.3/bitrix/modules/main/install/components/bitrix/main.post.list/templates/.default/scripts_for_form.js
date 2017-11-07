;(function(window){
	window["UC"] = (!!window["UC"] ? window["UC"] : {});
	if (!!window["FCForm"])
		return false;

	window.FCForm = function(arParams)
	{
		this.url = '';
		this.lhe = '';
		this.entitiesId = {};
		this.form = BX(arParams['formId']);
		this.editor = window[arParams['editorName']];
		this.editorName = arParams['editorName'];
		this.editorId = arParams['editorId'];

		this.windowEvents = {
			OnUCUnlinkForm : BX.delegate(function(entityId) {
				if (!!entityId && !!this.entitiesId[entityId]) {
					var res = {}, empty = true;
					for (var ii in this.entitiesId)
					{
						if (!!this.entitiesId && ii != entityId)
						{
							empty = false;
							res[ii] = this.entitiesId[ii];
						}
					}
					this.entitiesId = res;
					if (empty && !!this.windowEvents)
					{
						for (ii in this.windowEvents)
						{
							if (!!ii)
								BX.removeCustomEvent(window, ii, this.windowEvents[ii]);
						}
						this.windowEventsSet = false;
					}
				}
			}, this),
			OnUCUserQuote : BX.delegate(function(entityId, author, res) {
				if (!!this.entitiesId[entityId]) {
					this.show([entityId, 0]);
					if (this.editor && this.editor.quoteBut && this.editor.quoteBut.oBut &&
						(this.editor.quoteBut.oBut.bbHandler || this.editor.quoteBut.oBut.bbHandler))
					{
						var pBut = this.editor.quoteBut, strId = '';
						if (this.editor.sEditorMode == 'code' && this.editor.bBBCode) // BB Codes
						{
							if (!author)
								author = '';
							else if (author.id > 0)
								author = "[USER=" + author.id + "]" + author.name + "[/USER]";
							else
								author = author.name;
							author = (author !== '' ? (author + BX.message("MPL_HAVE_WRITTEN") + '\n') : '');

							if (res && res.length > 0)
								return pBut.pLEditor.WrapWith('[QUOTE]', '[/QUOTE]', author + res);
						}
						else if(this.editor.sEditorMode == 'html') // WYSIWYG
						{
							res = BX.util.htmlspecialchars(res);
							res = res.replace(/\n/g, '<br />');
							if (!pBut.pLEditor.bBBCode)
								strId = " id\"=" + pBut.pLEditor.SetBxTag(false, {tag: "quote"}) + "\"";

							if (res && res.length > 0)
							{
								if (!author)
									author = '';
								else if (author.id > 0)
									author = '<span id="' + pBut.pLEditor.SetBxTag(false, {'tag': "postuser", 'params': {'value' : author.id}}) +
										'" style="color: #2067B0; border-bottom: 1px dashed #2067B0;">' + author.name.replace(/</gi, '&lt;').replace(/>/gi, '&gt;') + '</span>';
								else
									author = '<span>' + author.name.replace(/</gi, '&lt;').replace(/>/gi, '&gt;') + '</span>';
								author = (author !== '' ? ( author + BX.message("MPL_HAVE_WRITTEN") + '<br/>') : '');
								return pBut.pLEditor.InsertHTML('<blockquote class="bx-quote"' + strId + ">" + author + res + "</blockquote> <br/>");
							}
						}
					}
				} }, this),
			OnUCUserReply : BX.delegate(function(entityId, authorId, authorName) {
				if (!!this.entitiesId[entityId]) {
					this.show([entityId, 0]);
					if (authorId > 0)
					{
						var item = {entityId: authorId, name: authorName};
						window.BXfpdSelectCallbackMent(item, 'users', '', this.form.id, this.editorName, true);
					}
				} }, this),
			OnUCAfterRecordEdit : BX.delegate(function(entityId, id, data, act) {
				if (!!this.entitiesId[entityId]) {
					if (act === "EDIT")
					{
						this.show([entityId, id], data['messageBBCode'], data['messageFields']);
						this.editing = true;
					}
					else
					{
						this.hide(true);
						if (!!data['errorMessage'])
						{
							this.id = [entityId, id];
							this.showError(data['errorMessage']);
						}
						else if (!!data['okMessage'])
						{
							this.id = [entityId, id];
							this.showNote(data['okMessage']);
							this.id = null;
						}
					}
				} }, this),
			OnUCUsersAreWriting : BX.delegate(function(entityId, authorId, authorName, authorAvatar, timeL) {
				if (!!this.entitiesId[entityId]) { this.showAnswering([entityId, 0], authorId, authorName, authorAvatar, timeL); } }, this),
			OnUCRecordHaveDrawn :  BX.delegate(function(entityId, data, params) {
				if (!!this.entitiesId[entityId]) {
					var authorId = parseInt(!!data && !!data["messageFields"] && !!data["messageFields"]["AUTHOR"] && !!data["messageFields"]["AUTHOR"]["ID"] ?
						data["messageFields"]["AUTHOR"]["ID"] : 0);
					if (authorId > 0)
						this.hideAnswering([entityId, 0], authorId); } }, this)
		};

		this.linkEntity(arParams['entitiesId']);

		if (!this.editor)
		{
			this.windowEvents.LHE_OnInit = BX.delegate(function(pEditor) { if (pEditor.id == this.editorId) { this.editor = pEditor; } }, this);
			this.windowEvents.LHE_ConstructorInited = BX.delegate(function(pEditor) { if (pEditor.id == this.editorId) { if (!this.editor) { this.editor = pEditor; } this.editor.ucInited = true; } }, this);

			BX.addCustomEvent(window, 'LHE_OnInit', this.windowEvents.LHE_OnInit);
			BX.addCustomEvent(window, 'LHE_ConstructorInited', this.windowEvents.LHE_ConstructorInited);
		}
		BX.remove(BX("micro" + arParams['editorName']));

		this.eventNode = BX('div' + this.editorName);

		if (!!this.eventNode)
		{
			BX.addCustomEvent(this.eventNode, 'OnBeforeHideLHE', BX.delegate(function(show, obj) {
				if (!!this.id && !!BX('uc-writing-' + this.form.id + '-' + this.id[0]))
					BX.hide(BX('uc-writing-' + this.form.id + '-' + this.id[0]));
			}, this));

			BX.addCustomEvent(this.eventNode, 'OnAfterHideLHE', BX.delegate(function(show, obj) {
				var node = this._getPlacehoder();
				if (node)
				{
					BX.hide(node);
				}

				node = this._getSwitcher();
				if (node)
				{
					BX.show(node);
					BX.focus(node.firstChild);
				}

				this.__content_length = 0;
				if (!!this.id) {
					BX.onCustomEvent(this.eventNode, 'OnUCFormAfterHide', [this]);
					this.showAnswering(this.id);
				}
				clearTimeout(this._checkWriteTimeout);
				this._checkWriteTimeout = 0;
				this.clear();
				BX.onCustomEvent(window, "OnUCFeedChanged", [this.id]);
			}, this));

			BX.addCustomEvent(this.eventNode, 'OnBeforeShowLHE', BX.delegate(function(show, obj) {
				var node = this._getPlacehoder();
				if (node)
				{
					BX.show(node);
				}
				node = this._getSwitcher();
				if (node)
				{
					BX.hide(node);
				}

				if (!!this.id && !!BX('uc-writing-' + this.form.id + '-' + this.id[0]))
					BX.hide(BX('uc-writing-' + this.form.id + '-' + this.id[0]));

			}, this));
			BX.addCustomEvent(this.eventNode, 'OnAfterShowLHE', BX.delegate(function(show, obj){
				this._checkWrite(show, obj);
				if (!!this.id)
					this.showAnswering(this.id);
				BX.onCustomEvent(window, "OnUCFeedChanged", [this.id]);
			}, this));
			BX.addCustomEvent(this.eventNode, 'OnClickSubmit', BX.delegate(this.submit, this));
			BX.addCustomEvent(this.eventNode, 'OnClickCancel', BX.delegate(this.cancel, this));

			BX.onCustomEvent(this.eventNode, 'OnUCFormInit', [this]);
		}
		this.id = null;
	}
	window.FCForm.prototype = {
		linkEntity : function(Ent)
		{
			if (!!Ent)
			{
				for(var ii in Ent)
				{
					if (!!ii  && !!Ent[ii])
					{
						BX.onCustomEvent(window, 'OnUCUnlinkForm', [ii]);
						this.entitiesId[ii] = Ent[ii];
					}
				}
			}
			if (!this.windowEventsSet && !!this.entitiesId)
			{
				BX.addCustomEvent(window, 'OnUCUnlinkForm', this.windowEvents.OnUCUnlinkForm);
				BX.addCustomEvent(window, 'OnUCUserReply', this.windowEvents.OnUCUserReply);
				BX.addCustomEvent(window, 'OnUCUserQuote', this.windowEvents.OnUCUserQuote);
				BX.addCustomEvent(window, 'OnUCAfterRecordEdit', this.windowEvents.OnUCAfterRecordEdit);
				BX.addCustomEvent(window, 'OnUCUsersAreWriting', this.windowEvents.OnUCUsersAreWriting);
				BX.addCustomEvent(window, 'OnUCRecordHaveDrawn', this.windowEvents.OnUCRecordHaveDrawn);
				this.windowEventsSet = true;
			}
		},
		_checkWrite : function(show, obj) {
			if (this.editorId == obj.oEditorId && !this.editor && !!window[this.editorName])
			{
				this.editor = this.window[this.editorName];
			}
			if (!!this.editor && this.editor.id == obj.oEditorId && this._checkWriteTimeout !== false)
			{
				this.__content_length = (this.__content_length > 0 ? this.__content_length : 0);
				this.editor.SaveContent();
				var content = this.editor.GetContent(),
					func = BX.delegate(function(){this._checkWrite(show, obj);}, this),
					time = 2000;
				if(content.length >= 4 && this.__content_length != content.length && !!this.id)
				{
					BX.onCustomEvent(window, 'OnUCUserIsWriting', [this.id[0], this.id[1]]);
					time = 30000;
				}
				this._checkWriteTimeout = setTimeout(func, time);
				this.__content_length = content.length;
			}
		},
		_getPlacehoder : function(res) {res = (!!res ? res : this.id); return (!!res ? BX('record-' + res.join('-') + '-placeholder') : null); },
		_getSwitcher : function(res) {res = (!!res ? res : this.id); return (!!res ? BX('record-' + res[0] + '-switcher') : null); },
		hide : function(quick) {if (this.eventNode.style.display != 'none') { BX.onCustomEvent(this.eventNode, 'OnShowLHE', [(quick === true ? false : 'hide')]); } },

		clear : function() {
			var form = this.form, filesForm = null;
			this.editing = false;
			var mpFormObj = window['PlEditor' + this.form.id];
			if (!!mpFormObj && !!mpFormObj.FController && !!mpFormObj.FController._CID)
				mpFormObj.FController.CID = mpFormObj.FController._CID;

			var res = this._getPlacehoder();
			if (!!res)
				BX.hide(res);
			var nodes = BX.findChildren(res, {'tagName' : "DIV", 'className' : "feed-add-error"}, true);
			if (!!nodes)
			{
				res = nodes.pop();
				do {
					BX.remove(res);
				} while ((res = nodes.pop()) && res);
			}

			BX.onCustomEvent(this.eventNode, 'OnUCFormClear', [this]);

			filesForm = BX.findChild(this.form, {'className': 'wduf-placeholder-tbody' }, true, false);
			if(filesForm !== null && typeof filesForm != 'undefined')
				BX.cleanNode(filesForm, false);
			filesForm = BX.findChild(this.form, {'className': 'wduf-selectdialog' }, true, false);
			if(filesForm !== null && typeof filesForm != 'undefined')
				BX.hide(filesForm);

			filesForm = BX.findChild(this.form, {'className': 'file-placeholder-tbody' }, true, false);
			if(filesForm !== null && typeof filesForm != 'undefined')
				BX.cleanNode(filesForm, false);

			filesForm = !!mpFormObj.FController ? mpFormObj.FController.controller : BX.findChild(this.form, {'className': 'file-selectdialog'}, true);
			var node = (!!filesForm ? BX.findChild(filesForm, {'className': 'file-placeholder' }, true, false) : null);
			nodes = (!!node ? BX.findChildren(node, {'className': 'feed-add-photo-block' }, false) : []);
			if (!!nodes) {
				for(var ii in nodes) {
					BX.cleanNode(nodes[ii], false);
				}
			}
			if(filesForm !== null && typeof filesForm != 'undefined')
				BX.hide(filesForm);

			this.id = null;
		},
		show : function(id, text, data)
		{
			if (!!this.id && !!id && this.id.join('-') == id.join('-'))
				return true;
			else
				this.hide(true);

			this.id = id;

			var node = this._getPlacehoder();
			node.appendChild(this.form);
			BX.onCustomEvent(this.eventNode, 'OnUCFormBeforeShow', [this, text, data]);
			BX.onCustomEvent(this.eventNode, 'OnShowLHE', ['show']);
			BX.onCustomEvent(this.eventNode, 'OnUCFormAfterShow', [this, text, data]);
			if (!!this.editor)
			{
				this.editor.ReInit(text || '');
				this.editor.pFrame.style.height = this.editor.arConfig.height;
				this.editor.ResizeFrame();
				this.editor.AutoResize();
				BX.defer(this.editor.SetFocus, this.editor);
			}
			return true;
		},
		submit : function(mpfObj)
		{
			if (this.busy === true)
				return 'busy';

			var text = '';
			if (!!mpfObj)
			{
				mpfObj.oEditor.SaveContent();
				text = mpfObj.oEditor.GetContent();
			}
			else
			{
				this.editor.SaveContent();
				text = this.editor.GetContent();
			}

			if (!text) {
				this.showError(BX.message('JERROR_NO_MESSAGE'));
				return false;
			}
			this.showWait();
			this.busy = true;

			var post_data = {};
			window.convertFormToArray(this.form, post_data, text);
			post_data['REVIEW_TEXT'] = text;
			post_data['NOREDIRECT'] = "Y";
			post_data['MODE'] = "RECORD";
			post_data['AJAX_POST'] = "Y";
			post_data['id'] = this.id;
			if (this.editing === true)
			{
				post_data['REVIEW_ACTION'] = "EDIT";
				post_data["FILTER"] = {"ID" : this.id[1]};
			}
			BX.onCustomEvent(this.eventNode, 'OnUCFormSubmit', [this, post_data]);
			BX.onCustomEvent(window, 'OnUCFormSubmit', [this.id[0], this.id[1], this, post_data]);
			BX.ajax({
				'method': 'POST',
				'url': this.form.action,
				'data': post_data,
				dataType: 'json',
				onsuccess: BX.proxy(function(data) {
					this.closeWait();
					var true_data = data, ENTITY_XML_ID = this.id[0];
					BX.onCustomEvent(this.eventNode, 'OnUCFormResponse', [this, data]);
					if (!!this.OnUCFormResponseData)
						data = this.OnUCFormResponseData;
					if (!!data)
					{
						if (!!data['errorMessage'])
						{
							this.showError(data['errorMessage']);
						}
						else
						{
							BX.onCustomEvent(window, 'OnUCAfterRecordAdd', [this.id[0], data]);
							this.hide(true);
						}
					}
					this.busy = false;
					BX.onCustomEvent(window, 'OnUCFormResponse', [ENTITY_XML_ID, data["messageId"], this, data]);
				}, this),
				onfailure: BX.delegate(function(){this.closeWait();
					this.busy = false;
					BX.onCustomEvent(window, 'OnUCFormResponse', [this.id[0], this.id[1], this, []]);}, this)
			});
		},
		cancel : function() {},
		showError : function(text) {
			if (!text)
				return false;

			var node = this._getPlacehoder(), nodes = BX.findChildren(node, {'tagName' : "DIV", 'className' : "feed-add-error"}, true);
			if (!!nodes)
			{
				var res = nodes.pop();
				do {
					BX.remove(res);
				} while ((res = nodes.pop()) && !!res);
			}
			node.insertBefore(BX.create('div', {attrs : {"class": "feed-add-error"},
				html: '<span class="feed-add-info-text"><span class="feed-add-info-icon"></span>' +
					'<b>' + BX.message('FC_ERROR') + '</b><br />' + text + '</span>'}),
				node.firstChild);

			BX.show(node);
		},
		showNote : function(text) {
			if (!text)
				return false;

			var node = this._getPlacehoder(), nodes = BX.findChildren(node, {'tagName' : "DIV", 'className' : "feed-add-successfully"}, true), res = null;
			if (!!nodes)
			{
				while ((res = nodes.pop()) && !!res) {
					BX.remove(res);
				}
			}
			node.insertBefore(BX.create('div', {attrs : {"class": "feed-add-successfully"},
				html: '<span class="feed-add-info-text"><span class="feed-add-info-icon"></span>' + text + '</span>'}),
				node.firstChild);
			BX.show(node);
		},
		showWait : function() {
			var el = BX('lhe_button_submit_' + this.form.id),
				id = ('lhe_button_submit_' + this.form.id + '_fill');
			if (!!el && !!el.lastChild && el.lastChild.id != id)
			{
				BX.adjust(el, {
					style : { position : "relative"},
					children : [BX.create('SPAN', {
						attrs : { "class" : "mpf-load", id : id},
						style : { position: "absolute", top : 0, left : 0, width: "100%"},
						children : [
							BX.create('DIV', {
								attrs : { "className" : "mpf-load-img" },
								style : { position: "absolute", top : 0, left : 0, width: "100%" }
							})
						]
					})]
				});
				BX.defer(function(){el.disabled = true})();
			}
		},
		closeWait : function() {
			var el = BX('lhe_button_submit_' + this.form.id),
				id = ('lhe_button_submit_' + this.form.id + '_fill');
			if (!!el && !!el.lastChild && el.lastChild.id == id)
			{
				el.disabled = false ;
				BX.removeClass(el, 'feed-add-button-press');
				el.style.cssText = '';
				BX.remove(el.lastChild);

			}
		},
		showAnswering : function(id, userId, name, avatar, time)
		{
			var
				_id = 'uc-writing-' + this.form.id + '-' + id[0],
				placeHolder = BX(_id + '-area'),
				switcher = this._getSwitcher(id),
				ucAnsweringStorage = BX.localStorage.get('ucAnsweringStorage');
			ucAnsweringStorage = (!!ucAnsweringStorage ? ucAnsweringStorage : {});

			if (!placeHolder && switcher)
			{
				placeHolder  = BX.create('DIV', {
					attrs : {id : _id + '-area'},
					style : { display : "none", "verticalAlign": "top", "fontWeight": "normal", "paddingLeft": "15px", "position": "absolute" },
					html : '<span id="' + _id + '-users"></span><span class="feed-answer-writing"></span>'
				});
				switcher.appendChild(placeHolder);
			}
			if (!!placeHolder)
			{
				if (userId > 0)
				{
					if (!time)
					{
						ucAnsweringStorage['userId' + userId] = {id : id[0], userId : userId, name : name, avatar : avatar, 'time' : (new Date())};
						BX.localStorage.set('ucAnsweringStorage', ucAnsweringStorage, 3000);
					}
					if (!BX(_id + '-user-' + userId))
					{
						BX.adjust(
							BX(_id + '-users'),
							{
								style : { marginTop: "-3px", display: "inline-block"},
								children : [
									BX.create('DIV', {
											attrs : {
												"className" : "feed-com-answer-avatar" + (!!avatar ? "" : " feed-com-answer-noavatar"),
												id : (_id + '-user-' + userId),
												title : name
											},
											children : [
												BX.create('IMG', {
													attrs : {
														src : avatar
													},
													style : {width : 'inherit', height : 'inherit'}
												})
											]
										}
									)
								]
							}
						);
					}
				}
				if (BX(_id + '-users').childNodes.length > 0)
				{
					if(BX(placeHolder.parentNode).style.display == 'none')
					{
						var node = BX('lhe_buttons_' + this.form.id);
						if (!node || node.style.display == 'none')
							node = this.form;
						node.appendChild(placeHolder);
					}
					else if(placeHolder.parentNode != switcher)
						switcher.appendChild(placeHolder);

					if(placeHolder.style.display != 'inline-block')
					{
						placeHolder.style.display = 'inline-block';
						(new BX.easing({
							duration : 500,
							start : { opacity : 0},
							finish : { opacity: 100},
							transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
							step : function(state){
								placeHolder.style.opacity = state.opacity / 100;
							}
						})).animate();
					}
					setTimeout(BX.delegate(function(){ this.hideAnswering(id, userId); }, this), (!!time ? time : 29500));
				}
			}
		},
		hideAnswering : function(id, userId)
		{
			var
				_id = 'uc-writing-' + this.form.id + '-' + id[0],
				placeHolder = BX(_id + '-area'),
				el = BX(_id + '-user-' + userId, false);
			if(el && placeHolder)
			{
				if(BX(_id + '-users').childNodes.length > 1)
				{
					(new BX.easing({
						duration : 500,
						start : { opacity: 100},
						finish : { opacity : 0},
						transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
						step : function(state){
							el.style.opacity = state.opacity / 100;
						},
						complete : function(){
							if(!!el && !!el.parentNode)
								el.parentNode.removeChild(el);
						}
					})).animate();
				}
				else
				{
					(new BX.easing({
						duration : 500,
						start : { opacity: 100},
						finish : { opacity : 0},
						transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
						step : function(state){
							placeHolder.style.opacity = state.opacity / 100;
						},
						complete : function(){
							placeHolder.style.display = 'none';
							if(!!el && !!el.parentNode)
								el.parentNode.removeChild(el);
						}
					})).animate();
				}
			}
		}
	}

	window.convertFormToArray = function(form, data)
	{
		data = (!!data ? data : []);
		if(!!form){
			var
				i,
				_data = [],
				n = form.elements.length;

			for(i=0; i<n; i++)
			{
				var el = form.elements[i];
				if (el.disabled)
					continue;
				switch(el.type.toLowerCase())
				{
					case 'text':
					case 'textarea':
					case 'password':
					case 'hidden':
					case 'select-one':
						_data.push({name: el.name, value: el.value});
						break;
					case 'radio':
					case 'checkbox':
						if(el.checked)
							_data.push({name: el.name, value: el.value});
						break;
					case 'select-multiple':
						for (var j = 0; j < el.options.length; j++) {
							if (el.options[j].selected)
								_data.push({name : el.name, value : el.options[j].value});
						}
						break;
					default:
						break;
				}
			}

			var current = data;
			i = 0;

			while(i < _data.length)
			{
				var p = _data[i].name.indexOf('[');
				if (p == -1) {
					current[_data[i].name] = _data[i].value;
					current = data;
					i++;
				}
				else
				{
					var name = _data[i].name.substring(0, p);
					var rest = _data[i].name.substring(p+1);
					if(!current[name])
						current[name] = [];

					var pp = rest.indexOf(']');
					if(pp == -1)
					{
						current = data;
						i++;
					}
					else if(pp === 0)
					{
						//No index specified - so take the next integer
						current = current[name];
						_data[i].name = '' + current.length;
					}
					else
					{
						//Now index name becomes and name and we go deeper into the array
						current = current[name];
						_data[i].name = rest.substring(0, pp) + rest.substring(pp+1);
					}
				}
			}
		}
		return data;
	};
	BX.ready(function(){
		var res = null, timeL = null, ucAnsweringStorage = BX.localStorage.get('ucAnsweringStorage');
		if(!!ucAnsweringStorage)
		{
			for (var ii in ucAnsweringStorage)
			{
				res = ucAnsweringStorage[ii];
				if (!!res && res.userId > 0)
				{
					timeL = ((new Date()) - res.time);
					if (timeL < 30000) { BX.onCustomEvent(window, 'OnUCUsersAreWriting', [res.id, res.userId, res.name, res.avatar, timeL]); }
				}
			}
		}
	})
})(window);

function fRefreshCaptcha(form)
{
	var captchaIMAGE = null,
		captchaHIDDEN = BX.findChild(form, {attr : {'name': 'captcha_code'}}, true),
		captchaINPUT = BX.findChild(form, {attr: {'name':'captcha_word'}}, true),
		captchaDIV = BX.findChild(form, {'className':'comments-reply-field-captcha-image'}, true);
	if (captchaDIV)
		captchaIMAGE = BX.findChild(captchaDIV, {'tag':'img'});
	if (captchaHIDDEN && captchaINPUT && captchaIMAGE)
	{
		captchaINPUT.value = '';
		BX.ajax.getCaptcha(function(result) {
			captchaHIDDEN.value = result.captcha_sid;
			captchaIMAGE.src = '/bitrix/tools/captcha.php?captcha_code='+result.captcha_sid;
		});
	}
}