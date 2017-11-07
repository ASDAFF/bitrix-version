;(function(window){
	if (window.BX["UploaderFile"])
		return false;
	var
		BX = window.BX,
		statuses = { "new" : 0, ready : 1, preparing : 2, inprogress : 3, done : 4, failed : 5, stopped : 6, changed : 7, uploaded : 8},
		cnvConstr = function(timelimit)
		{
			this.timeLimit = (typeof timelimit == "number" && timelimit > 0 ? timelimit : 50);
			this.status = statuses.ready;
			this.queue = new BX.UploaderUtils.Hash();
		};
	cnvConstr.prototype = {
		image : null,
		getImage : function()
		{
			if (!this.image)
				this.image = new Image();
			return this.image;
		},
		canvas : null,
		getCanvas : function()
		{
			if (!this.canvas)
				this.canvas = BX.create('CANVAS');

			return this.canvas;
		},
		context : null,
		getContext : function()
		{
			if (!this.context && this.getCanvas()["getContext"])
				this.context = this.getCanvas().getContext('2d');
			return this.context;
		},
		reader : null,
		getReader : function()
		{
			if (!this.reader && window["FileReader"])
				this.reader = new FileReader();
			return this.reader;
		},
		load : function(file, callback, id, callbackFail)
		{
			var image = this.getImage();

			BX.unbindAll(image);
			this.onload = null;
			delete this.onload;
			this.onerror = null;
			delete this.onerror;
			image.src = '/bitrix/images/1.gif';
			this.onload = BX.delegate(function(){
				if (!!callback)
				{
					try{
						callback(BX.proxy_context, this.getCanvas(), this.getContext());
					}
					catch (e)
					{
						BX.debug(e);
					}
				}
				if (!!id)
				{
					this.queue.removeItem(id);
					setTimeout(BX.proxy(this.exec, this), this.timeLimit);
				}
			}, this);
			this.onerror = BX.delegate(function(){
				if (!!callbackFail)
				{
					try
					{
						callbackFail(BX.proxy_context);
					}
					catch (e)
					{
						BX.debug(e);
					}
				}
				if (!!id)
				{
					this.queue.removeItem(id);
					setTimeout(BX.proxy(this.exec, this), this.timeLimit);
				}
			}, this);
			image.name = file.name;
			BX.bind(image, 'load', this.onload);
			BX.bind(image, 'error', this.onerror);

			var res = Object.prototype.toString.call(file);
			if (file["tmp_url"])
			{
				image.src = file["tmp_url"];
			}
			else if (res !== '[object File]' && res !== '[object Blob]')
			{
				this.onerror(null);
			}
			else if (!!window["URL"])
			{
				image.src = window["URL"]["createObjectURL"](file);
			}
			else if (this.getReader() !== null)
			{
				this.__readerOnLoad = BX.delegate(function(e) {
					image.src = e.target.result;
					this.__readerOnLoad = null;
					delete this.__readerOnLoad;
				}, this);
				this.getReader().onload = this.__readerOnLoad;
				this.getReader().readAsDataURL(file);
			}
		},
		push : function(file, callback, failCallback)
		{
			var id = BX.UploaderUtils.getId();
			this.queue.setItem(id, [id, file, callback, failCallback]);
			this.exec();
		},
		exec : function()
		{
			var item = this.queue.getFirst();
			if (!!item)
				this.load(item[1], item[2], item[0], item[3]);
		}
	};
	BX.UploaderFileCnvConstr = cnvConstr;
	var fileLoader = function(timelimit)
	{
		this.timeLimit = (typeof timelimit == "number" && timelimit > 0 ? timelimit : 50);
		this.status = statuses.ready;
		this.queue = new BX.UploaderUtils.Hash();
		this._exec = BX.delegate(this.exec, this);
	};
	fileLoader.prototype = {
		xhr : null,
		goToNext : function(id)
		{
			delete this.xhr;
			this.xhr = null;
			this.queue.removeItem(id);
			this.status = statuses.ready;
			setTimeout(this._exec, this.timeLimit);
		},
		load : function(id, path, onsuccess, onfailure)
		{
			if (this.status != statuses.ready)
				return;
			this.status = statuses.inprogress;
			var _this = this;
			this.xhr = BX.ajax({
				'method': 'GET',
				'data' : '',
				'url': path,
				'onsuccess': function(blob){if (blob === null){onfailure(blob);} else {onsuccess(blob);} _this.goToNext(id);},
				'onfailure': function(blob){onfailure(blob); _this.goToNext(id);},
				'start': false,
				'preparePost':false,
				'processData':false
			});
			this.xhr.withCredentials = true;
			this.xhr.responseType = 'blob';


			this.xhr.send();
		},
		push : function(path, onsuccess, onfailure)
		{
			var id = BX.UploaderUtils.getId();
			this.queue.setItem(id, [id, path, onsuccess, onfailure]);
			this.exec();
		},
		exec : function()
		{
			var item = this.queue.getFirst();
			if (!!item)
				this.load(item[0], item[1], item[2], item[3]);
		}
	};
	BX.UploaderFileFileLoader = fileLoader();
	var prvw = new cnvConstr(), upld = new cnvConstr(), edtr = new cnvConstr(), canvas = BX.create('CANVAS'), ctx;
	/**
	 * @return {BX.UploaderFile}
	 * @file file
	 * @params array
	 * @limits array
	 * @caller {BX.Uploader}
	 * You should work with params["fields"] in case you want to change visual part
	 */
var mobileNames = {};
	BX.UploaderFile = function (file, params, limits, caller)
	{
		this.dialogName = (this.dialogName ? this.dialogName : "BX.UploaderFile");
		this.file = file;
		this.id = (file['id'] || 'file' + BX.UploaderUtils.getId());
		this.name = file.name;
		this.isNode = false;
		if (BX.type.isDomNode(file))
		{
			this.isNode = true;
			this.name = BX.UploaderUtils.getFileNameOnly(file.value);
			if (/\[(.+?)\]/.test(file.name))
			{
				var tmp = /\[(.+?)\]/.exec(file.name);
				this.id = tmp[1];
			}
			this.file.bxuHandler = this;
		}
		else if (file["tmp_url"] && !file["name"])
		{
			this.name = BX.UploaderUtils.getFileNameOnly(file["tmp_url"]);
		}
		this.preview = '<span id="' + this.id + 'Canvas" class="bx-bxu-canvas"></span>';
		this.nameWithoutExt = (this.name.lastIndexOf('.') > 0 ? this.name.substr(0, this.name.lastIndexOf('.')) : this.name);
		this.ext = this.name.substr(this.nameWithoutExt.length + 1);

		if (/iPhone|iPad|iPod/i.test(navigator.userAgent) && this.nameWithoutExt == "image")
		{
			var nameWithoutExt = 'mobile_' + BX.date.format("Ymd_His");
			mobileNames[nameWithoutExt] = (mobileNames[nameWithoutExt] || 0);
			this.nameWithoutExt = nameWithoutExt + (mobileNames[nameWithoutExt] > 0 ? ("_" + mobileNames[nameWithoutExt]) : "");
			this.name = this.nameWithoutExt + (BX.type.isNotEmptyString(this.ext) ? ("." + this.ext) : "");
			mobileNames[nameWithoutExt]++;
		}

		this.size = '';
		if (file.size)
			this.size = BX.UploaderUtils.getFormattedSize(file.size, 0);
		this.type = file.type;
		this.status = statuses["new"];
		this.limits = limits;
		this.caller = caller;
		this.fields = {
			thumb : {
				tagName : 'SPAN',
				template : '<div class="someclass">#preview#<div>#name#</div>',
				editorTemplate : '<div class="someeditorclass"><div>#name#</div>',
				className : "bx-bxu-thumb-thumb",
				placeHolder : null
			},
			preview : {
				params : { width : 400, height : 400 },
				template : "#preview#",
				editorParams : { width : 1024, height : 860 },
				editorTemplate : '<span>#preview#</span>',
				className : "bx-bxu-thumb-preview",
				placeHolder : null,
				events : {
					click : BX.delegate(this.clickFile, this)
				}
			},
			name : {
				template : "#name#",
				editorTemplate : '<span><input type="text" name="name" value="#name#" /></span>',
				className : "bx-bxu-thumb-name",
				placeHolder : null
			},
			type : {
				template : "#type#",
				editorTemplate : '#type#',
				className : "bx-bxu-thumb-type",
				placeHolder : null
			}
		};

		if (!!params["fields"])
		{
			var ij, key;
			for (var ii in params["fields"])
			{
				if (params["fields"].hasOwnProperty(ii))
				{
					if (!!this.fields[ii])
					{
						for (ij in params["fields"][ii])
						{
							if (params["fields"][ii].hasOwnProperty(ij))
							{
								this.fields[ii][ij] = params["fields"][ii][ij];
							}
						}
					}
					else
						this.fields[ii] = params["fields"][ii];
					key = ii + '';
					if (key.toLowerCase() != "thumb" && key.toLowerCase() != "preview")
					{
						this[key.toLowerCase()] = (!!params["fields"][ii]["value"] ? params["fields"][ii]["value"] : "");
						this.log(key.toLowerCase() + ': ' + this[key.toLowerCase()]);
					}
				}
			}
		}

		BX.onCustomEvent(this, "onFileIsCreated", [this.id, this, this.caller]);
		BX.onCustomEvent(this.caller, "onFileIsCreated", [this.id, this, this.caller]);

		this.makePreview();
		this.preparationStatus = statuses.done;
		return this;
	};
	BX.UploaderFile.prototype = {
		log : function(text)
		{
			BX.UploaderUtils.log('file ' + this.name, text);
		},
		makeThumb : function()
		{
			var template = this.fields.thumb.template, name, ii, events = {}, node, jj;
			for (ii in this.fields)
			{
				if (this.fields.hasOwnProperty(ii))
				{
					if (this.fields[ii].template && this.fields[ii].template.indexOf('#' + ii + '#') >= 0)
					{
						name = this.id + ii.toUpperCase().substr(0, 1) + ii.substr(1);
						node = this.setProps(ii, this[ii], true);
						template = template.replace('#' + ii + '#', '<span id="' + name + '" class="' + this.fields[ii]["className"] + '">' + (
							BX.type.isNotEmptyString(node.html) ? node.html.replace("#", "<\x19>") : node.html) + '</span>');
						for (jj in node.events)
						{
							if (node.events.hasOwnProperty(jj))
							{
								events[jj] = node.events[jj];
							}
						}
						if (!!this.fields[ii].events)
							events[name] = this.fields[ii].events;
					}
				}
			}
			var res, patt = [], repl = [], tmp;
			while ((res = /#(.+?)#/gi.exec(template)) && !!res)
			{
				if (this[res[1]] !== undefined)
				{
					template = template.replace(res[0], BX.type.isNotEmptyString(this[res[1]]) ? this[res[1]].replace("#", "<\x19>") : this[res[1]]);
				}
				else
				{
					tmp = "<\x18" + patt.length + ">";
					patt.push(tmp);
					repl.push(res[0]);
					template = template.replace(res[0], tmp);
				}
			}
			while ((res = patt.shift()) && res)
			{
				tmp = repl.shift();
				template = template.replace(res, tmp);
			}
			template = template.replace("<\x19>", "#");
			if (!!this.fields.thumb.tagName)
			{
				res = BX.create(this.fields.thumb.tagName, {
					attrs : {
						id : (this.id + 'Thumb'),
						className : this.fields.thumb.className
					},
					events : this.fields.thumb.events,
					html : template
					}
				);
			}
			else
			{
				res = template;
			}
			this.__makeThumbEventsObj = events;
			this.__makeThumbEvents = BX.delegate(function()
			{
				var ii, jj;
				for (ii in events)
				{
					if (events.hasOwnProperty(ii) && BX(ii))
					{
						for (jj in events[ii])
						{
							if (events[ii].hasOwnProperty(jj))
							{
								BX.bind(BX(ii), jj, events[ii][jj]);
							}
						}
					}
				}
				this.__makeThumbEvents = null;
				delete this.__makeThumbEvents;
			}, this);
			BX.addCustomEvent(this, "onFileIsAppended", this.__makeThumbEvents);

			if (BX.type.isDomNode(this.file))
			{
				if (BX.type.isString(template))
				{
					this.__bindFileNode = BX.delegate(function(id)
					{
						var node = BX(id + 'Item');
						if (node.tagName == 'TR')
							node.cells[0].appendChild(this.file);
						else if (node.tagName == 'TABLE')
							node.rows[0].cells[0].appendChild(this.file);
						else
							BX(id + 'Item').appendChild(this.file);
						this.__bindFileNode = null;
						delete this.__bindFileNode;
					}, this);
					BX.addCustomEvent(this, "onFileIsAppended", this.__bindFileNode);
				}
				else
				{
					res.appendChild(this.file);
				}
			}
			return res;
		},
		checkProps : function()
		{
			var el2 = BX.UploaderUtils.FormToArray({elements : [BX.proxy_context]}), ii;
			for (ii in el2.data)
			{
				if (el2.data.hasOwnProperty(ii))
					this[ii] = el2.data[ii];
			}
		},
		setProps : function(name, val, bReturn)
		{
			if (typeof name == "string")
			{
				if (name == "size")
					val = BX.UploaderUtils.getFormattedSize(this.file.size, 0);
				if (typeof this[name] != "undefined" && typeof this.fields[name] != "undefined")
				{
					this[name] = val;
					var template = this.fields[name].template.
							replace('#' + name + '#', (!!val ? val : '')).
							replace(/#id#/gi, this.id),
						fii, fjj, el, result = {html : template, events : {}};

					this.hiddenForm = (!!this.hiddenForm ? this.hiddenForm : BX.create("FORM", { style : { display : "none" } } ));
					this._checkProps = (!!this._checkProps ? this._checkProps : BX.delegate(this.checkProps, this));
					this.hiddenForm.innerHTML = template;
					if (this.hiddenForm.elements.length > 0)
					{
						for (fii = 0; fii < this.hiddenForm.elements.length; fii++)
						{
							el = this.hiddenForm.elements[fii];
							if (typeof this[el.name] != "undefined")
							{
								if (!el.hasAttribute("id"))
									el.setAttribute("id", this.id + name + BX.UploaderUtils.getId());
								result.events[el.id] = {
									blur : this._checkProps
								}

							}
						}
						result.html = this.hiddenForm.innerHTML;
					}
					if (BX(this.hiddenForm))
						BX.remove(this.hiddenForm);
					this.hiddenForm = null;
					delete this.hiddenForm;
					if (bReturn)
						return result;
					var node = this.getPH(name);
					if (!!node)
					{
						node.innerHTML = result.html;
						for (fii in result.events)
						{
							if (result.events.hasOwnProperty(fii))
							{
								for (fjj in result.events[fii])
								{
									if (result.events[fii].hasOwnProperty(fjj))
									{
										BX.bind(BX(fii), fjj, result.events[fii][fjj]);
									}
								}
							}
						}
					}
				}
			}
			else if (!!name)
			{
				for (var ij in name)
				{
					if (name.hasOwnProperty(ij))
					{
						if (this.fields.hasOwnProperty(ij) && ij !== "preview")
							this.setProps(ij, name[ij]);
					}
				}
			}
			return true;
		},
		getProps : function(name)
		{
			if (name == "canvas")
			{
				return BX(this.id + "ProperCanvas");
			}
			else if (typeof name == "string")
			{
				return this[name];
			}
			var data = {};
			for (var ii in this.fields)
			{
				if (this.fields.hasOwnProperty(ii) && (ii !== "preview" && ii !== "thumb"))
				{
					data[ii] = this[ii];
				}
			}
			if (!!this.copies)
			{
				var copy;
				data["canvases"] = {};
				while ((copy = this.copies.getNext()) && !!copy)
				{
					data["canvases"][copy.id] = { width : copy.width, height : copy.height, name : copy.name };
				}
			}
			return data;
		},
		getThumbs : function()
		{
			return null;
		},
		getPH : function(name)
		{
			name = (typeof name === "string" ? name : "");
			name = name.toLowerCase();
			if (this.fields.hasOwnProperty(name))
			{
				var id = name.substr(0, 1).toUpperCase() + name.substr(1);
				this.fields[name]["placeHolder"] = BX(this.id  + id);
				return this.fields[name]["placeHolder"];
			}
			return null;
		},
		clickFile : function ()
		{
			return false;
		},
		makePreview: function()
		{
			this.status = statuses.ready;
			BX.onCustomEvent(this, "onFileIsInited", [this.id, this, this.caller]);
			BX.onCustomEvent(this.caller, "onFileIsInited", [this.id, this, this.caller]);

			this.log('is initialized as a file');
		},
		preparationStatus : statuses.ready,
		deleteFile: function()
		{
			var ii, events = this.__makeThumbEventsObj;
			for (ii in this.fields)
			{
				if (this.fields.hasOwnProperty(ii))
				{
					if (!!this.fields[ii]["placeHolder"])
					{
						this.fields[ii]["placeHolder"] = null;
						BX.unbindAll(this.fields[ii]["placeHolder"]);
						delete this.fields[ii]["placeHolder"];
					}
				}
			}

			for (ii in events)
			{
				if (events.hasOwnProperty(ii) && BX(ii))
				{
					BX.unbindAll(BX(ii));
				}
			}

			this.file = null;
			delete this.file;

			BX.remove(this.canvas);
			this.canvas = null;
			delete this.canvas;

			BX.onCustomEvent(this.caller, "onFileIsDeleted", [this.id, this, this.caller]);
			BX.onCustomEvent(this, "onFileIsDeleted", [this, this.caller]);
		}
	};
	BX.UploaderImage = function(file, params, limits, caller)
	{
		this.dialogName = "BX.UploaderImage";
		BX.UploaderImage.superclass.constructor.apply(this, arguments);
		this.isImage = true;
		this.copies = new BX.UploaderUtils.Hash();
		this.caller = caller;

		if (!this.isNode && BX.Uploader.getInstanceName() == "BX.Uploader")
		{
			if (!!params["copies"])
			{
				var copies = params["copies"], copy;
				for (var ii in copies)
				{
					if (copies.hasOwnProperty(ii) && !!copies[ii])
					{
						copy = { width : parseInt(copies[ii]['width']), height : parseInt(copies[ii]["height"]), id : ii };
						if (copy['width'] > 0 && copy["height"] > 0)
						{
							this.copies.setItem(ii, copy);
						}
					}
				}
			}
			this.preparationStatus = statuses["new"];
			BX.addCustomEvent(this, "onFileHasToBePrepared", BX.delegate(function()
			{
				this.preparationStatus = statuses.inprogress;
				if (this.status != statuses["new"])
				{
					upld.push(this.file, BX.delegate(this.makeCopies, this));
				}
			}, this));
			BX.addCustomEvent(this, "onUploadDone", BX.delegate(function()
			{
				var copy;
				while ((copy = this.copies.getNext()) && !!copy)
				{
					copy.file = null;
					delete copy.file;
				}
				this.preparationStatus = statuses["new"];
			}, this));
			this.canvas = BX.create('CANVAS', {attrs : { id : this.id + "ProperCanvas" } } );
		}
		else
		{
			this.preparationStatus = statuses.done;
			this.canvas = null;
		}
		return this;
	};
	BX.extend(BX.UploaderImage, BX.UploaderFile);
	BX.UploaderImage.prototype.makePreviewImageWork = function(image)
	{
		var result = null;
		if (this.file)
		{
			this.file.width = image.width;
			this.file.height = image.height;
		}

		if (!!this.canvas)
		{
			BX.adjust(prvw.getCanvas(), { props : { width : image.width, height : image.height } } );
			prvw.getContext().drawImage(image, 0, 0);

			this.applyFile(prvw.getCanvas(), false);

			result = this.canvas;
		}
		else if (BX(this.id + 'Canvas'))
		{
			var res2 = BX.UploaderUtils.scaleImage(image, this.fields.preview.params),
				props = {
					props : { width : res2.destin.width, height : res2.destin.height, src : image.src },
					attrs : {
						className : (this.file.width > this.file.height ? "landscape" : "portrait")
					}
				};
			result = BX.create("IMG", props);
		}

		BX.onCustomEvent(this, "onFileCanvasIsLoaded", [this.id, this, this.caller, image]);
		BX.onCustomEvent(this.caller, "onFileCanvasIsLoaded", [this.id, this, this.caller, image]);

		if (BX(this.id + 'Canvas'))
			BX(this.id + 'Canvas').appendChild(result);

		return result;
	};

	BX.UploaderImage.prototype.makePreviewImageLoadHandler = function(image, canvas, context){
		this.makePreviewImageWork(image);
		this.status = statuses.ready;

		BX.onCustomEvent(this, "onFileIsInited", [this.id, this, this.caller]);
		BX.onCustomEvent(this.caller, "onFileIsInited", [this.id, this, this.caller]);
		this.log('is initialized as an image with preview');
		if (this.preparationStatus == statuses.inprogress)
			this.makeCopies(image, canvas, context);
		if (this["_makePreviewImageLoadHandler"])
		{
			this._makePreviewImageLoadHandler = null;
			delete this._makePreviewImageLoadHandler;
		}
		if (this["_makePreviewImageFailedHandler"])
		{
			this._makePreviewImageFailedHandler = null;
			delete this._makePreviewImageFailedHandler;
		}
	};

	BX.UploaderImage.prototype.makePreviewImageFailedHandler = function(){
		this.status = statuses.ready;
		this.preparationStatus = statuses.done;

		BX.onCustomEvent(this, "onFileIsInited", [this.id, this, this.caller]);
		BX.onCustomEvent(this.caller, "onFileIsInited", [this.id, this, this.caller]);

		this.log('is initialized without canvas');
		if (this["_makePreviewImageLoadHandler"])
		{
			this._makePreviewImageLoadHandler = null;
			delete this._makePreviewImageLoadHandler;
		}
		if (this["_makePreviewImageFailedHandler"])
		{
			this._makePreviewImageFailedHandler = null;
			delete this._makePreviewImageFailedHandler;
		}
	};
	BX.UploaderImage.prototype.makePreview = function()
	{
		if (!this.isNode)
		{
			this._makePreviewImageLoadHandler = BX.delegate(this.makePreviewImageLoadHandler, this);
			this._makePreviewImageFailedHandler = BX.delegate(this.makePreviewImageFailedHandler, this);
			prvw.push(this.file, this._makePreviewImageLoadHandler, this._makePreviewImageFailedHandler);
		}
		else
		{
			this.status = statuses.ready;
			BX.onCustomEvent(this, "onFileIsInited", [this.id, this, this.caller]);
			BX.onCustomEvent(this.caller, "onFileIsInited", [this.id, this, this.caller]);

			this.log('is initialized as an image without preview');
			if (this.caller.queue.placeHolder)
			{
				this._onFileHasGotPreview = BX.delegate(function(id, item) {

					BX.removeCustomEvent(this, "onFileHasGotPreview", this._onFileHasGotPreview);
					BX.removeCustomEvent(this, "onFileHasNotGotPreview", this._onFileHasNotGotPreview);

					this._makePreviewImageLoadHandler = BX.delegate(function(image){
						image = this.makePreviewImageWork(image);
						BX.onCustomEvent(this, "onFileHasPreview", [item.id, item, image]);
						delete this._makePreviewImageLoadHandler;
						delete this._makePreviewImageFailedHandler;
					}, this);
					this._makePreviewImageFailedHandler = BX.delegate(function(image){
						delete this._makePreviewImageLoadHandler;
						delete this._makePreviewImageFailedHandler;
					}, this);
					prvw.push({tmp_url : item.file.url}, this._makePreviewImageLoadHandler, this._makePreviewImageFailedHandler);
				}, this);
				this._onFileHasNotGotPreview = BX.delegate(function(id){
					if (id == this.id)
					{
						BX.removeCustomEvent(this, "onFileHasGotPreview", this._onFileHasGotPreview);
						BX.removeCustomEvent(this, "onFileHasNotGotPreview", this._onFileHasNotGotPreview);
					}
				}, this);
				BX.addCustomEvent(this, "onFileHasGotPreview", this._onFileHasGotPreview);
				BX.addCustomEvent(this, "onFileHasNotGotPreview", this._onFileHasNotGotPreview);
				BX.onCustomEvent(this.caller, "onFileNeedsPreview", [this.id, this, this.caller]);
			}
		}
		return true;
	};
	BX.UploaderImage.prototype.checkPreview = function()
	{
		// TODO check preview
	};
	BX.UploaderImage.prototype.applyFile = function(cnv, params)
	{
		this.checkPreview();

		if (!!params && params.data )
			this.setProps(params.data);

		var realScale = BX.UploaderUtils.scaleImage(cnv, {width : this.limits["uploadFileWidth"], height : this.limits["uploadFileHeight"]}),
			prvwScale = BX.UploaderUtils.scaleImage(cnv, this.fields.preview.params),
			prvwProps = {
				props : { width : prvwScale.destin.width, height : prvwScale.destin.height },
				attrs : {
					className : "bx-bxu-proper-canvas"+(prvwScale.destin.width > prvwScale.destin.height ? " landscape" : " portrait")
				}
			};

		if (realScale.bNeedCreatePicture || !!params)
		{
			BX.adjust(canvas, { props : { width : realScale.destin.width, height : realScale.destin.height } } );
			ctx = canvas.getContext('2d');
			ctx.drawImage(cnv,
				realScale.source.x, realScale.source.y, realScale.source.width, realScale.source.height,
				realScale.destin.x, realScale.destin.y, realScale.destin.width, realScale.destin.height
			);

			var dataURI = canvas.toDataURL(this.file.type);
			this.file = BX.UploaderUtils.dataURLToBlob(dataURI);
		}

		this.file.name = this.name;
		this.file.width = realScale.destin.width;
		this.file.height = realScale.destin.height;

		BX.adjust(this.canvas, prvwProps);

		ctx = this.canvas.getContext('2d');
		ctx.drawImage(cnv,
			prvwScale.source.x, prvwScale.source.y, prvwScale.source.width, prvwScale.source.height,
			prvwScale.destin.x, prvwScale.destin.y, prvwScale.destin.width, prvwScale.destin.height
		);

		ctx = null;
		cnv = null;

		this.setProps('size');
		this.status = statuses.changed;
	};
	BX.UploaderImage.prototype.clickFile = function()
	{
		if (!this.canvas || !BX["CanvasEditor"] || this.status == statuses["new"])
			return false;
		if (!this.__showEditor)
		{
			this.__showEditor = BX.delegate(this.showEditor, this);
			this.eFunc = {
				"apply" : BX.delegate(this.applyFile, this),
				"delete" : BX.delegate(this.deleteFile, this),
				"clear" : BX.delegate(function()
				{
					BX.removeCustomEvent(editor, "onApplyCanvas", this.eFunc["apply"]);
					BX.removeCustomEvent(editor, "onDeleteCanvas", this.eFunc["delete"]);
					BX.removeCustomEvent(editor, "onClose", this.eFunc["clear"]);
				}, this)
			};
		}
		var template = this.fields.thumb.editorTemplate, name;
		for (var ii in this.fields)
		{
			if (this.fields.hasOwnProperty(ii))
			{
				name = ii.substr(0, 1).toUpperCase() + ii.substr(1);
				template = template.replace('#' + ii + '#',
					(ii === "preview" ? "" :
						('<span id="' + this.id + name + 'Editor" class="' + this.fields[ii]["className"] + '">' +
						this.fields[ii]["editorTemplate"].replace('#' + ii + '#', (!!this[ii] ? this[ii] : '')) + '</span>')));
			}
		}

		BX.adjust(edtr.getCanvas(), { props : { width : this.file.width, height : this.file.height } } );
		edtr.getContext().drawImage(this.canvas,
			0, 0, this.canvas.width, this.canvas.height,
			0, 0, edtr.getCanvas().width, edtr.getCanvas().height);
		var editor = BX.CanvasEditor.show(edtr.getCanvas(), {title : this.name, template : template});

		BX.addCustomEvent(editor, "onApplyCanvas", this.eFunc["apply"]);
		BX.addCustomEvent(editor, "onDeleteCanvas", this.eFunc["delete"]);
		BX.addCustomEvent(editor, "onClose", this.eFunc["clear"]);
		BX.onCustomEvent(this, "onCanvasEditorIsCreated", [editor, this]);

		edtr.push(this.file, this.__showEditor);
		this.editor = editor;
		return false;
	};
	BX.UploaderImage.prototype.showEditor = function(image, canvas, context)
	{
		BX.adjust(canvas, { props : { width : this.file.width, height : this.file.height } } );
		context.drawImage(image, 0, 0);
		this.editor.copyCanvas(canvas);
	};
	BX.UploaderImage.prototype.makeCopies = function(image, canvas, context)
	{
		var copy, res, dataURI, result;

		while ((copy = this.copies.getNext()) && !!copy)
		{
			res = BX.UploaderUtils.scaleImage(image, copy);
			BX.adjust(canvas, {props : { width : res.destin.width, height : res.destin.height } } );
			context.drawImage(image,
				res.source.x, res.source.y, res.source.width, res.source.height,
				res.destin.x, res.destin.y, res.destin.width, res.destin.height
			);

			dataURI = canvas.toDataURL(this.file.type);
			result = BX.UploaderUtils.dataURLToBlob(dataURI);
			result.width = canvas.width;
			result.height = canvas.height;
			result.name = this.name;
			result.thumb = copy.id;
			result.canvases = this.copies.length;
			result.canvas = this.copies.pointer - 1;
			copy.file = result;
		}
		this.preparationStatus = statuses.done;
	};
	BX.UploaderImage.prototype.getThumbs = function(name)
	{
		if (name == "getCount")
			return this.copies.length;

		var res = (typeof name == "string" ? this.copies.getItem(name) : this.copies.getNext());

		if (!!res)
			return res.file;
		return null;
	};
	return true;
}(window));
