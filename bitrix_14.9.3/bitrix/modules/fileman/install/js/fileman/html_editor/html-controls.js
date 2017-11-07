/**
 * Bitrix HTML Editor 3.0
 * Date: 24.04.13
 * Time: 4:23
 *
 * Taskbarmanager
 * Taskbar
 * Context Menu
 * Search/Replace
 */
;(function() {
	function TaskbarManager(editor, init)
	{
		this.editor = editor;
		this.bShown = false;
		this.closedWidth = 20;
		this.MIN_CLOSED_WIDTH = 120;
		this.width = this.editor.config.taskbarWidth || 250;
		this.taskbars = {};
		this.freezeOnclickHandler = false;

		if (init)
		{
			this.Init();
		}
	}

	TaskbarManager.prototype = {
		Init: function()
		{
			this.pCont = this.editor.dom.taskbarCont;
			this.pCont.setAttribute('data-bx-type', 'taskbarmanager');

			this.pResizer = BX('bx-html-editor-tskbr-res-' + this.editor.id);
			this.pResizer.setAttribute('data-bx-type', 'taskbarflip');
			this.pTopCont = BX('bx-html-editor-tskbr-top-' + this.editor.id);

			BX.bind(this.pResizer, 'mousedown', BX.proxy(this.StartResize, this));
			BX.bind(this.pCont, 'click', BX.proxy(this.OnClick, this));

			// Search
			this.pSearchCont = BX('bxhed-tskbr-search-cnt-' + this.editor.id);
			this.pSearchAli = BX('bxhed-tskbr-search-ali-' + this.editor.id);
			this.pSearchInput = BX('bxhed-tskbr-search-inp-' + this.editor.id);
			this.pSearchNothingNotice = BX('bxhed-tskbr-search-nothing-' + this.editor.id);
			BX.bind(this.pSearchInput, 'keyup', BX.proxy(this.TaskbarSearch, this));
		},

		OnClick: function(e)
		{
			if (!e)
				e = window.event;

			if (this.freezeOnclickHandler)
				return;

			var
				_this = this,
				target = e.target || e.srcElement,
				type = (target && target.getAttribute) ? target.getAttribute('data-bx-type') : null;

			if (!type)
			{
				target = BX.findParent(target, function(node)
				{
					return node == _this.pCont || (node.getAttribute && node.getAttribute('data-bx-type'));
				}, this.pCont);
				type = (target && target.getAttribute) ? target.getAttribute('data-bx-type') : null;
			}

			if (type == 'taskbarflip' || (!this.bShown && (type == 'taskbarmanager' || !type)))
			{
				if (this.bShown)
				{
					this.Hide();
				}
				else
				{
					this.Show();
				}
			}
			else if(type == 'taskbargroup_title')
			{
				BX.onCustomEvent(this, 'taskbargroupTitleClick', [target]);
			}
			else if(type == 'taskbarelement')
			{
				BX.onCustomEvent(this, 'taskbarelementClick', [target]);
			}
			else if(type == 'taskbar_title_but')
			{
				BX.onCustomEvent(this, 'taskbarTitleClick', [target]);
			}
			else if(type == 'taskbar_top_menu')
			{
				BX.onCustomEvent(this, 'taskbarMenuClick', [target]);
			}
			else if(type == 'taskbar_search_cancel')
			{
				this.pSearchInput.value = '';
				this.TaskbarSearch();
			}
		},

		Show: function(saveValue)
		{
			if (!this.bShown)
			{
				this.bShown = true;
				this.pCont.className = 'bxhtmled-taskbar-cnt bxhtmled-taskbar-shown';
			}
			this.pCont.style.width = this.GetWidth(true) + 'px';
			this.editor.ResizeSceleton();

			if (saveValue !== false)
			{
				this.editor.SaveOption('taskbar_shown', 1);
			}
		},

		Hide: function(saveValue)
		{
			if (this.bShown)
			{
				this.bShown = false;
				this.pCont.className = 'bxhtmled-taskbar-cnt bxhtmled-taskbar-hidden';
			}
			this.pCont.style.width = this.GetWidth() + 'px';
			this.editor.ResizeSceleton();

			if (saveValue !== false)
			{
				this.editor.SaveOption('taskbar_shown', 0);
			}
		},

		GetWidth: function(bCheck, maxWidth)
		{
			var width;
			if (this.bShown)
			{
				width = bCheck ? Math.max(this.width, this.closedWidth + this.MIN_CLOSED_WIDTH) : this.width;
				if(maxWidth && width > maxWidth)
				{
					width = this.width = Math.round(maxWidth);
				}
			}
			else
			{
				width = this.closedWidth;
			}

			return width;
		},

		AddTaskbar: function(oTaskbar)
		{
			this.taskbars[oTaskbar.id] = oTaskbar;
			this.pCont.appendChild(oTaskbar.GetCont());
			this.pTopCont.appendChild(oTaskbar.GetTitleCont());
		},

		ShowTaskbar: function(taskbarId)
		{
			this.pSearchInput.value = '';
			for(var id in this.taskbars)
			{
				if (this.taskbars.hasOwnProperty(id))
				{
					if (id == taskbarId)
					{
						this.taskbars[id].Activate();
						this.pSearchInput.placeholder = this.taskbars[id].searchPlaceholder;
					}
					else
					{
						this.taskbars[id].Deactivate();
					}

					this.activeTaskbarId = taskbarId;
					this.taskbars[id].ClearSearchResult();
				}
			}
		},

		GetActiveTaskbar: function()
		{
			return this.taskbars[this.activeTaskbarId];
		},

		StartResize: function(e)
		{
			if(!e)
				e = window.event;

			var target = e.target || e.srcElement;
			if (target.getAttribute('data-bx-tsk-split-but') == 'Y')
				return true;

			this.freezeOnclickHandler = true;

			var
				width = this.GetWidth(),
				overlay = this.editor.dom.resizerOverlay,
				dX = 0, newWidth,
				windowScroll = BX.GetWindowScrollPos(),
				startX = e.clientX + windowScroll.scrollLeft,
				_this = this;

			overlay.style.display = 'block';

			function moveResizer(e, bFinish)
			{
				if(!e)
					e = window.event;

				var x = e.clientX + windowScroll.scrollLeft;

				if(startX == x)
					return;

				dX = startX - x;
				newWidth = width + dX;

				if (bFinish)
				{
					_this.width = Math.max(newWidth, _this.closedWidth + _this.MIN_CLOSED_WIDTH);
					if (isNaN(_this.width))
					{
						_this.width = _this.closedWidth + _this.MIN_CLOSED_WIDTH;
					}
				}
				else
				{
					_this.width = newWidth;
				}

				if (newWidth > _this.closedWidth + (bFinish ? 20 : 0))
				{
					_this.Show();
				}
				else
				{
					_this.Hide();
				}
			}

			function finishResizing(e)
			{
				moveResizer(e, true);
				BX.unbind(document, 'mousemove', moveResizer);
				BX.unbind(document, 'mouseup', finishResizing);
				overlay.style.display = 'none';
				setTimeout(function(){_this.freezeOnclickHandler = false;}, 10);
				BX.PreventDefault(e);

				_this.editor.SaveOption('taskbar_width', _this.GetWidth(true));
			}

			BX.bind(document, 'mousemove', moveResizer);
			BX.bind(document, 'mouseup', finishResizing);
		},

		Resize: function(w, h)
		{
			var topHeight = parseInt(this.pTopCont.offsetHeight, 10);
			for(var id in this.taskbars)
			{
				if (this.taskbars.hasOwnProperty(id) && this.taskbars[id].pTreeCont)
				{
					this.taskbars[id].pTreeCont.style.height = (h - topHeight - 42) + 'px';
				}
			}

			this.pSearchCont.style.width = w + 'px';
			if (!BX.browser.IsDoctype())
			{
				this.pSearchAli.style.width = (w - 20) + 'px';
			}
		},

		TaskbarSearch: function(e)
		{
			var
				taskbar = this.GetActiveTaskbar(),
				value = this.pSearchInput.value;

			if (e && e.keyCode == this.editor.KEY_CODES['escape'])
			{
				value = this.pSearchInput.value = '';
			}

			if (value.length < 2)
			{
				taskbar.ClearSearchResult();
			}
			else
			{
				taskbar.Search(value);
			}
		}
	};


	/*
	 *
	 *
	 * */
	function Taskbar(editor)
	{
		this.editor = editor;
		this.manager = this.editor.taskbarManager;
		this.searchIndex = [];
		this._searchResult = [];
		this._searchResultSect = [];

		BX.addCustomEvent(this.manager, 'taskbargroupTitleClick', BX.proxy(this.OnGroupTitleClick, this));
		BX.addCustomEvent(this.manager, 'taskbarelementClick', BX.proxy(this.OnElementClick, this));
		BX.addCustomEvent(this.manager, 'taskbarTitleClick', BX.proxy(this.OnTitleClick, this));
		BX.addCustomEvent(this.manager, 'taskbarMenuClick', BX.proxy(this.OnMenuClick, this));
	}

	Taskbar.prototype = {
		GetCont: function()
		{
			return this.pTreeCont;
		},

		GetTitleCont: function()
		{
			return this.pTitleCont;
		},

		BuildSceleton: function()
		{
			// Build title & menu
			this.pTitleCont = BX.create("span", {props: {className: "bxhtmled-split-btn"},html: '<span class="bxhtmled-split-btn-l"><span class="bxhtmled-split-btn-bg">' + this.title + '</span></span><span class="bxhtmled-split-btn-r"><span data-bx-type="taskbar_top_menu" data-bx-taskbar="' + this.id + '" class="bxhtmled-split-btn-bg"></span></span>'});
			this.pTitleCont.setAttribute('data-bx-type', 'taskbar_title_but');
			this.pTitleCont.setAttribute('data-bx-taskbar', this.id);

			this.pTreeCont = BX.create("DIV", {props: {className: "bxhtmled-taskbar-tree-cont"}});
			this.pTreeInnerCont = this.pTreeCont.appendChild(BX.create("DIV", {props: {className: "bxhtmled-taskbar-tree-inner-cont"}}));
		},

		BuildTree: function(sections, elements)
		{
			BX.cleanNode(this.pTreeCont);
			this.treeSectionIndex = {};
			this.BuildTreeSections(sections);
			this.BuildTreeElements(elements);
		},

		BuildTreeSections: function(sections)
		{
			this.sections = [];
			for (var i = 0; i < sections.length; i++)
			{
				this.BuildSection(sections[i]);
			}
		},

		GetSectionsTreeInfo: function()
		{
			return this.sections;
		},

		BuildSection: function(section)
		{
			var
				parentCont = this.GetSectionContByPath(section.path),
				pGroup = BX.create("DIV", {props: {className: "bxhtmled-tskbr-sect-outer"}}),
				pGroupTitle = pGroup.appendChild(BX.create("DIV", {props: {className: "bxhtmled-tskbr-sect"}})),
				icon = pGroupTitle.appendChild(BX.create("SPAN", {props: {className: "bxhtmled-tskbr-sect-icon"}})),
				title = pGroupTitle.appendChild(BX.create("SPAN", {props: {className: "bxhtmled-tskbr-sect-title"}, text: section.title || section.name})),
				childCont = pGroup.appendChild(BX.create("DIV", {props: {className: "bxhtmled-tskb-child"}})),
				elementsCont = pGroup.appendChild(BX.create("DIV", {props: {className: "bxhtmled-tskb-child-elements"}}));

			var key = section.path == '' ? section.name : section.path + ',' + section.name;
			var depth = section.path == '' ? 0 : 1; // Todo....

			var sect = {
				key: key,
				children: [],
				section: section
			}

			this.treeSectionIndex[key] = {
				icon: icon,
				outerCont: pGroup,
				cont: pGroupTitle,
				childCont: childCont,
				elementsCont: elementsCont,
				sect: sect
			};

			this.GetSectionByPath(section.path).push(sect);

			if (depth > 0)
			{
				BX.addClass(pGroupTitle, "bxhtmled-tskbr-sect-" + depth);
				BX.addClass(icon, "bxhtmled-tskbr-sect-icon-" + depth);
			}

			pGroupTitle.setAttribute('data-bx-type', 'taskbargroup_title');
			pGroupTitle.setAttribute('data-bx-taskbar', this.id);

			pGroup.setAttribute('data-bx-type', 'taskbargroup');
			pGroup.setAttribute('data-bx-path', key);
			pGroup.setAttribute('data-bx-taskbar', this.id);

			parentCont.appendChild(pGroup);
		},

		BuildTreeElements: function(elements)
		{
			this.elements = elements;
			for (var i in elements)
			{
				if (elements.hasOwnProperty(i))
				{
					this.BuildElement(elements[i]);
				}
			}
		},

		BuildElement: function(element)
		{
			var
				_this = this,
				parentCont = this.GetSectionContByPath(element.key || element.path, true),
				pElement = BX.create("DIV", {props: {className: "bxhtmled-tskbr-element"}, html: '<span class="bxhtmled-tskbr-element-icon"></span><span class="bxhtmled-tskbr-element-text">' + element.title + '</span>'});

			var dd = pElement.appendChild(BX.create("IMG", {props: {
				src: this.editor.util.GetEmptyImage(),
				className: "bxhtmled-drag"
			}}));

			this.HandleElementEx(pElement, dd, element);

			this.searchIndex.push({
				content: (element.title + ' ' + element.name).toLowerCase(),
				element: pElement
			});

			dd.onmousedown = function (e)
			{
				if (!e)
				{
					e = window.event;
				}

				var
					target = e.target || e.srcElement,
					bxTag = _this.editor.GetBxTag(target);

				return _this.OnElementMouseDownEx(e, target, bxTag);
			};

			dd.ondblclick = function(e)
			{
				var
					target = e.target || e.srcElement,
					bxTag = _this.editor.GetBxTag(target);

				return _this.OnElementDoubleClick(e, target, bxTag);
			};

			dd.ondragend = function (e)
			{
				if (!e)
				{
					e = window.event;
				}
				_this.OnDragEndHandler(e, this);
			};

			pElement.setAttribute('data-bx-type', 'taskbarelement');

			parentCont.appendChild(pElement);
		},

		HandleElementEx: function(dd)
		{

		},

		GetSectionContByPath: function(path, bElement)
		{
			if (path == '' || !this.treeSectionIndex[path])
			{
				return this.pTreeCont;
			}
			else
			{
				return bElement ? this.treeSectionIndex[path].elementsCont : this.treeSectionIndex[path].childCont;
			}
		},

		GetSectionByPath: function(path)
		{
			if (path == '' || !this.treeSectionIndex[path])
			{
				return this.sections;
			}
			else
			{
				return this.treeSectionIndex[path].sect.children;
			}
		},

		// Open or close
		ToggleGroup: function(cont, bOpen)
		{
			// TODO: animation
			var path = cont.getAttribute('data-bx-path');
			if (path)
			{
				var group = this.treeSectionIndex[path];
				if (!group)
				{
					return;
				}

				if (bOpen !== undefined)
				{
					group.opened = !bOpen;
				}

				if (group.opened)
				{
					BX.removeClass(group.cont, 'bxhtmled-tskbr-sect-open');
					BX.removeClass(group.icon, 'bxhtmled-tskbr-sect-icon-open');
					BX.removeClass(group.outerCont, 'bxhtmled-tskbr-sect-outer-open');
					group.childCont.style.display = 'none';
					group.elementsCont.style.display = 'none';
					group.opened = false;
				}
				else
				{
					BX.addClass(group.cont, 'bxhtmled-tskbr-sect-open');
					BX.addClass(group.icon, 'bxhtmled-tskbr-sect-icon-open');
					BX.addClass(group.outerCont, 'bxhtmled-tskbr-sect-outer-open');
					group.childCont.style.display = 'block';
					group.elementsCont.style.display = group.elementsCont.childNodes.length > 0 ? 'block' : 'none';
					group.opened = true;
				}
			}
		},

		OnDragEndHandler: function(e, node)
		{
			var _this = this;
			this.editor.skipPasteHandler = true;
			setTimeout(function()
			{
				var dd = _this.editor.GetIframeElement(node.id);
				if (dd && dd.parentNode)
				{
					var sur = _this.editor.util.CheckSurrogateNode(dd.parentNode);
					if (sur)
					{
						_this.editor.util.InsertAfter(dd, sur);
					}
				}
				_this.editor.synchro.FullSyncFromIframe();
				_this.editor.skipPasteHandler = false;
			}, 10);
		},

		OnElementMouseDownEx: function(e)
		{
			return true;
		},

		OnElementClick: function(e)
		{
			this.OnElementClickEx();
			return true;
		},

		OnElementClickEx: function()
		{
			return true;
		},

		OnElementDoubleClick: function(e, target, bxTag)
		{
			if (target)
			{
				var dd = target.cloneNode(true);
				this.editor.Focus();
				this.editor.selection.InsertNode(dd);
				this.editor.synchro.FullSyncFromIframe();
			}
		},

		OnGroupTitleClick: function(pElement)
		{
			if (pElement && pElement.getAttribute('data-bx-taskbar') == this.id)
			{
				return this.ToggleGroup(pElement.parentNode);
			}
			return true;
		},

		OnTitleClick: function(pElement)
		{
			if (pElement && pElement.getAttribute('data-bx-taskbar') == this.id)
			{
				return this.manager.ShowTaskbar(this.id);
			}
			return true;
		},

		OnMenuClick: function(pElement)
		{
			if (pElement && pElement.getAttribute('data-bx-taskbar') == this.id)
				return this.ShowMenu(pElement);
			return true;
		},

		Activate: function()
		{
			this.pTreeCont.style.display = 'block';
			this.bActive = true;
			return true;
		},

		Deactivate: function()
		{
			this.pTreeCont.style.display = 'none';
			this.bActive = false;
			return true;
		},

		IsActive: function()
		{
			return !!this.bActive;
		},

		ShowMenu: function(pElement)
		{
			var arItems = this.GetMenuItems();
			BX.PopupMenu.destroy(this.uniqueId + "_menu");
			BX.PopupMenu.show(this.uniqueId + "_menu", pElement, arItems, {
					overlay: {opacity: 0.1},
					events: {
						onPopupClose: function(){BX.removeClass(this.bindElement, "bxec-add-more-over");}
					},
					offsetLeft: 1,
					zIndex: 3005
				}
			);
			return true;
		},

		GetMenuItems: function()
		{
			return [];
		},

		Search: function(value)
		{
			this.ClearSearchResult();
			var
				bFoundItems = false,
				pSect, el,
				i, l = this.searchIndex.length;

			value = BX.util.trim(value.toLowerCase());

			BX.addClass(this.pTreeCont, 'bxhtmled-taskbar-tree-cont-search');
			BX.addClass(this.manager.pSearchCont, 'bxhtmled-search-cont-res');

			for(i = 0; i < l; i++)
			{
				el = this.searchIndex[i];
				if (el.content.indexOf(value) !== -1) // Show element
				{
					bFoundItems = true;
					BX.addClass(el.element, 'bxhtmled-tskbr-search-res');
					this._searchResult.push(el.element);

					pSect = BX.findParent(el.element, function(node)
					{
						return node.getAttribute && node.getAttribute('data-bx-type') == 'taskbargroup';
					}, this.pTreeCont);

					while (pSect)
					{
						BX.addClass(pSect, 'bxhtmled-tskbr-search-res');
						this.ToggleGroup(pSect, true);
						this._searchResultSect.push(pSect);

						pSect = BX.findParent(pSect, function(node)
						{
							return node.getAttribute && node.getAttribute('data-bx-type') == 'taskbargroup';
						}, this.pTreeCont);
					}
				}
			}

			if (!bFoundItems)
			{
				this.manager.pSearchNothingNotice.style.display = 'block';
			}
		},

		ClearSearchResult: function()
		{
			BX.removeClass(this.pTreeCont, 'bxhtmled-taskbar-tree-cont-search');
			BX.removeClass(this.manager.pSearchCont, 'bxhtmled-search-cont-res');
			this.manager.pSearchNothingNotice.style.display = 'none';
			var i;
			if (this._searchResult)
			{
				for(i = 0; i < this._searchResult.length; i++)
				{
					BX.removeClass(this._searchResult[i], 'bxhtmled-tskbr-search-res');
				}
				this._searchResult = [];
			}
			if (this._searchResultSect)
			{
				for(i = 0; i < this._searchResultSect.length; i++)
				{
					BX.removeClass(this._searchResultSect[i], 'bxhtmled-tskbr-search-res');
					this.ToggleGroup(this._searchResultSect[i], false);
				}
				this._searchResultSect = [];
			}
		},

		GetId: function()
		{
			return this.id;
		}
	};

	function ComponentsControl(editor)
	{
		// Call parrent constructor
		ComponentsControl.superclass.constructor.apply(this, arguments);

		this.id = 'components';
		this.title = BX.message('ComponentsTitle');
		this.templateId = this.editor.templateId;
		this.uniqueId = 'taskbar_' + this.editor.id + '_' + this.id;
		this.searchPlaceholder = BX.message('BXEdCompSearchPlaceHolder');

		this.Init();
	}

	BX.extend(ComponentsControl, Taskbar);

	ComponentsControl.prototype.Init = function()
	{
		this.BuildSceleton();
		// Build structure
		var list = this.editor.components.GetList();
		this.BuildTree(list.groups, list.items);
	};

	ComponentsControl.prototype.HandleElementEx = function(wrap, dd, params)
	{
		this.editor.SetBxTag(dd, {tag: "component_icon", params: params});
		if (params.complex == "Y")
		{
			params.className = 'bxhtmled-surrogate-green';
			BX.addClass(wrap, 'bxhtmled-tskbr-element-green');
			wrap.title = BX.message('BXEdComplexComp');
		}
	};

	ComponentsControl.prototype.OnElementMouseDownEx = function(e, target, bxTag)
	{
		if (!bxTag || bxTag.tag !== 'component_icon')
		{
			return false;
		}

		this.editor.components.LoadParamsList({
			name: bxTag.params.name
		});
	};

	ComponentsControl.prototype.GetMenuItems = function()
	{
		var _this = this;
		return [
			{
				text : BX.message('RefreshTaskbar'),
				title : BX.message('RefreshTaskbar'),
				className : "",
				onclick: function()
				{
					_this.editor.componentsTaskbar.ClearSearchResult();
					_this.editor.components.ReloadList();
					BX.PopupMenu.destroy(_this.uniqueId + "_menu");
				}
			}
		];
	};

	// Editor dialog
	function Dialog(editor, params)
	{
		this.editor = editor;
		this.id = params.id;
		this.params = params;
		this.className = "bxhtmled-dialog" + (params.className ? ' ' + params.className : '');
		this.zIndex = params.zIndex || 3007;
		this.Init();
	}

	Dialog.prototype = {
		Init: function()
		{
			var
				_this = this,
				config = {
					title : this.params.title || this.params.name || '',
					width: this.params.width || 600,
					resizable: false
				};

			if (this.params.resizable)
			{
				config.resizable = true;
				config.min_width = this.params.min_width || 400;
				config.min_height = this.params.min_height || 250;
				config.resize_id = this.params.resize_id || this.params.id + '_res';
			}

			this.oDialog = new BX.CDialog(config);
			config.height = this.params.height || false;

			BX.addCustomEvent(this.oDialog, 'onWindowResize', BX.proxy(this.OnResize, this));
			BX.addCustomEvent(this.oDialog, 'onWindowResizeFinished', BX.proxy(this.OnResizeFinished, this));
			BX.addClass(this.oDialog.PARTS.CONTENT, this.className);

			// Clear dialog height for auto resizing
			if (!config.height)
			{
				this.oDialog.PARTS.CONTENT_DATA.style.height = null;
			}

			// Buttons
			this.oDialog.SetButtons([
				new BX.CWindowButton(
					{
						title: BX.message('DialogSave'),
						className: 'adm-btn-save',
						action: function()
						{
							BX.onCustomEvent(_this, "OnDialogSave");
							_this.oDialog.Close();
						}
					}),
				this.oDialog.btnCancel
			]);

			BX.addCustomEvent(this.oDialog, 'onWindowUnRegister', function()
			{
				BX.unbind(window, "keydown", BX.proxy(_this.OnKeyDown, _this));
				_this.dialogShownTimeout = setTimeout(function(){_this.editor.dialogShown = false;}, 300);
			});
		},

		Show: function()
		{
			var _this = this;
			this.editor.dialogShown = true;
			if (this.dialogShownTimeout)
			{
				this.dialogShownTimeout = clearTimeout(this.dialogShownTimeout);
			}
			this.oDialog.Show();
			this.oDialog.DIV.style.zIndex = this.zIndex;
			this.oDialog.OVERLAY.style.zIndex = this.zIndex - 2;
			var top = parseInt(this.oDialog.DIV.style.top) - 180;
			this.oDialog.DIV.style.top = (top > 50 ? top : 50) + 'px';
			BX.bind(window, "keydown", BX.proxy(this.OnKeyDown, this));
			if (BX.browser.IsOpera())
			{
				// Hack for Opera
				setTimeout(function(){_this.oDialog.Move(1, 1);}, 100);
			}

			setTimeout(function(){_this.oDialog.__resizeOverlay();}, 100);
		},

		BuildTabControl: function(pCont, arTabs)
		{
			var
				i,
				pTabsWrap = BX.create('DIV', {props: {className: 'bxhtmled-dlg-tabs-wrap'}}),
				pContWrap = BX.create('DIV', {props: {className: 'bxhtmled-dlg-cont-wrap'}});

			for (i = 0; i < arTabs.length; i++)
			{
				arTabs[i].tab = pTabsWrap.appendChild(BX.create('SPAN', {props: {className: 'bxhtmled-dlg-tab' + (i == 0 ? ' bxhtmled-dlg-tab-active' : '')}, attrs: {'data-bx-dlg-tab-ind': i.toString()}, text: arTabs[i].name}));
				arTabs[i].cont = pContWrap.appendChild(BX.create('DIV', {props: {className: 'bxhtmled-dlg-cont'}, style: {'display' : i == 0 ? '' : 'none'}}));
			}

			BX.bind(pTabsWrap, 'click', function(e)
			{
				var
					ind,
					target = e.target || e.srcElement;

				if (target && target.getAttribute)
				{
					ind = parseInt(target.getAttribute('data-bx-dlg-tab-ind'));
					if (!isNaN(ind))
					{
						for (i = 0; i < arTabs.length; i++)
						{
							if (i == ind)
							{
								arTabs[i].cont.style.display = '';
								BX.addClass(arTabs[i].tab, 'bxhtmled-dlg-tab-active');
							}
							else
							{
								arTabs[i].cont.style.display = 'none';
								BX.removeClass(arTabs[i].tab, 'bxhtmled-dlg-tab-active');
							}
						}
					}
				}
			});

			pCont.appendChild(pTabsWrap);
			pCont.appendChild(pContWrap);

			return {
				cont: pCont,
				tabsWrap : pTabsWrap,
				contWrap : pContWrap,
				tabs: arTabs
			};
		},

		OnKeyDown: function(e)
		{
			if (e.keyCode == 13 && this.closeByEnter !== false)
			{
				var target = e.target || e.srcElement;
				if (target && target.nodeName !== 'TEXTAREA')
				{
					this.oDialog.PARAMS.buttons[0].emulate();
				}
			}
		},

		SetContent: function(html)
		{
			return this.oDialog.SetContent(html);
		},

		SetTitle: function(title)
		{
			return this.oDialog.SetTitle(title);
		},

		OnResize: function()
		{
		},

		OnResizeFinished: function()
		{
		},

		GetContentSize: function()
		{
			return {
				width : this.oDialog.PARTS.CONTENT_DATA.offsetWidth,
				height : this.oDialog.PARTS.CONTENT_DATA.offsetHeight
			};
		},

		Save: function()
		{
			if (this.savedRange)
			{
				this.editor.selection.SetBookmark(this.savedRange);
			}

			if (this.action && this.editor.action.IsSupported(this.action))
			{
				this.editor.action.Exec(this.action, this.GetValues());
			}
		},

		Close: function()
		{
			if (this.IsOpen())
			{
				this.oDialog.Close();
			}
		},

		IsOpen: function()
		{
			return this.oDialog.isOpen;
		},

		DisableKeyCheck: function()
		{
			this.closeByEnter = false;
			BX.WindowManager.disableKeyCheck();
		},

		EnableKeyCheck: function()
		{
			var _this = this;
			setTimeout(function()
			{
				_this.closeByEnter = true;
				BX.WindowManager.enableKeyCheck();
			}, 200);
		},

		AddTableRow: function (tbl, firstCell)
		{
			var r, c1, c2;

			r = tbl.insertRow(-1);
			c1 = r.insertCell(-1);
			c1.className = 'bxhtmled-left-c';

			if (firstCell && firstCell.label)
			{
				c1.appendChild(BX.create('LABEL', {props: {className: firstCell.required ? 'bxhtmled-req' : ''}, text: firstCell.label})).setAttribute('for', firstCell.id);
			}

			c2 = r.insertCell(-1);
			c2.className = 'bxhtmled-right-c';
			return {row: r, leftCell: c1, rightCell: c2};
		},

		SetValues: BX.DoNothing,
		GetValues: BX.DoNothing
	};

	function ContextMenu(editor)
	{
		this.editor = editor;
		this.lastMenuId = null;
		BX.addCustomEvent(this.editor, 'OnIframeContextMenu', BX.delegate(this.Show, this));
		this.Init();
	}

	ContextMenu.prototype = {
		Init: function()
		{
			var
			_this = this,
			defaultItem = {
				text: BX.message('ContMenuDefProps'),
				onclick: function()
				{
					_this.editor.selection.SetBookmark(_this.savedRange);
					_this.editor.GetDialog('Default').Show(false, _this.savedRange);
					_this.Hide();
				}
			};

			// Remove format ?
			// Replace Node by children ?

			//this.tagsList = [];
			this.items = {
				// Surrogates
				'php' : [
					{
						text: BX.message('BXEdContMenuPhpCode'),
						onclick: function()
						{
							var items = _this.GetTargetItem();
							if (items && items.php)
							{
								_this.editor.GetDialog('Source').Show(items.php.bxTag);
							}
							_this.Hide();
						}
					}
				],
				'anchor' : [
					{
						text: BX.message('BXEdEditAnchor'),
						onclick: function()
						{
							var items = _this.GetTargetItem();
							if (items && items.anchor)
							{
								_this.editor.GetDialog('Anchor').Show(items.anchor.bxTag);
							}
							_this.Hide();
						}
					}
				],
				'javascript' : [
					{
						text: BX.message('BXEdContMenuJavascript'),
						onclick: function()
						{
							var items = _this.GetTargetItem();
							if (items && items.javascript)
							{
								_this.editor.GetDialog('Source').Show(items.javascript.bxTag);
							}
							_this.Hide();
						}
					}
				],
				'htmlcomment' : [
					{
						text: BX.message('BXEdContMenuHtmlComment'),
						onclick: function()
						{
							var items = _this.GetTargetItem();
							if (items && items.htmlcomment)
							{
								_this.editor.GetDialog('Source').Show(items.htmlcomment.bxTag);
							}
							_this.Hide();
						}
					}
				],
				'iframe' : [
					{
						text: BX.message('BXEdContMenuIframe'),
						onclick: function()
						{
							var items = _this.GetTargetItem();
							if (items && items.iframe)
							{
								_this.editor.GetDialog('Source').Show(items.iframe.bxTag);
							}
							_this.Hide();
						}
					}
				],
				'style' : [
					{
						text: BX.message('BXEdContMenuStyle'),
						onclick: function()
						{
							var items = _this.GetTargetItem();
							if (items && items.style)
							{
								_this.editor.GetDialog('Source').Show(items.style.bxTag);
							}
							_this.Hide();
						}
					}
				],
				'object' : [
					{
						text: BX.message('BXEdContMenuObject'),
						onclick: function()
						{
							var items = _this.GetTargetItem();
							if (items && items.object)
							{
								_this.editor.GetDialog('Source').Show(items.object.bxTag);
							}
							_this.Hide();
						}
					}
				],
				'component' : [
					{
						text: BX.message('BXEdContMenuComponent'),
						onclick: function()
						{
							var items = _this.GetTargetItem();
							if (items && items.component)
							{
								// Show dialog
								_this.editor.components.ShowPropertiesDialog(items.component.bxTag.params, _this.editor.GetBxTag(items.component.bxTag.surrogateId));
							}
							_this.Hide();
						}
					},
					{
						text: BX.message('BXEdContMenuComponentRemove'),
						onclick: function()
						{
							var items = _this.GetTargetItem();
							if (items && items.component)
							{
								BX.remove(items.component.element);
							}
							_this.Hide();
						}
					}
				],
				'printbreak' : [
					{
						text: BX.message('NodeRemove'),
						onclick: function(e)
						{
							var node = _this.GetTargetItem('printbreak');
							if (node && node.element)
							{
								_this.editor.selection.RemoveNode(node.element);
							}
							_this.Hide();
						}
					}
				],
				'video': [
					{
						text: BX.message('BXEdVideoProps'),
						onclick: function()
						{
							var node = _this.GetTargetItem('video');
							if (node)
							{
								_this.editor.GetDialog('Video').Show(node.bxTag);
							}
							_this.Hide();
						}
					},
					{
						text: BX.message('BXEdVideoDel'),
						onclick: function(e)
						{
							var node = _this.GetTargetItem('video');
							if (node && node.element)
							{
								_this.editor.selection.RemoveNode(node.element);
							}
							_this.Hide();
						}
					}
				],

				// Nodes
				'A' : [
					{
						text: BX.message('ContMenuLinkEdit'),
						onclick: function()
						{
							var node = _this.GetTargetItem('A');
							if (node)
							{
								_this.editor.GetDialog('Link').Show([node], this.savedRange);
							}
							_this.Hide();
						}
					},
					{
						text: BX.message('ContMenuLinkDel'),
						onclick: function()
						{
							var link = _this.GetTargetItem('A');
							if (link && _this.editor.action.IsSupported('removeLink'))
							{
								_this.editor.action.Exec('removeLink', [link]);
							}
							_this.Hide();
						}
					}
				],
				'IMG': [
					{
						text: BX.message('ContMenuImgEdit'),
						onclick: function()
						{
							var node = _this.GetTargetItem('IMG');
							if (node)
							{
								_this.editor.GetDialog('Image').Show([node], this.savedRange);
							}
							_this.Hide();
						}
					},
					{
						text: BX.message('ContMenuImgDel'),
						onclick: function()
						{
							var node = _this.GetTargetItem('IMG');
							if (node)
							{
								_this.editor.selection.RemoveNode(node);
							}
							_this.Hide();
						}
					}
				],
				'DIV': [
					{
						text: BX.message('ContMenuCleanDiv'),
						title: BX.message('ContMenuCleanDiv_Title'),
						onclick: function()
						{
							var node = _this.GetTargetItem('DIV');
							if (node)
							{
								_this.editor.On('OnHtmlContentChangedByControl');
								_this.editor.util.ReplaceWithOwnChildren(node);
								_this.editor.synchro.FullSyncFromIframe();
							}
							_this.Hide();
						}
					},
					defaultItem
				],
				'TABLE': [
//					{
//						text: BX.message('BXEdTableInsCell'),
//						onclick: function()
//						{
//							_this.Hide();
//						}
//					},
//					{
//						text: BX.message('BXEdTableInsRow'),
//						onclick: function()
//						{
//							_this.Hide();
//						}
//					},
//					{
//						text: BX.message('BXEdTableInsColumn'),
//						onclick: function()
//						{
//							_this.Hide();
//						}
//					},
					{
						text: BX.message('BXEdTableTableProps'),
						onclick: function()
						{
							var node = _this.GetTargetItem('TABLE');
							if (node)
							{
								_this.editor.GetDialog('Table').Show([node], this.savedRange);
							}
							_this.Hide();
						}
					},
					{
						text: BX.message('BXEdTableDeleteTable'),
						onclick: function()
						{
							var node = _this.GetTargetItem('TABLE');
							if (node)
							{
								_this.editor.selection.RemoveNode(node);
							}
							_this.Hide();
						}
					}
				],
				// ...
				'DEFAULT': [defaultItem]
			};
		},

		Show: function(e, target)
		{
			this.savedRange = this.editor.selection.GetBookmark();

			if (this.lastMenuId)
			{
				this.Hide();
			}

			this.editor.contextMenuShown = true;
			if (this.contextMenuShownTimeout)
			{
				this.contextMenuShownTimeout = clearTimeout(this.contextMenuShownTimeout);
			}
			this.nodes = [];
			this.tagIndex = {};
			this.lastMenuId = 'bx_context_menu_' + Math.round(Math.random() * 1000000000);
			var
				bxTag,
				i, j,
				arItems = [],
				maxIter = 20, iter = 0,
				element = target,
				label;

			this.targetItems = {};
			while (true)
			{
				if (element.nodeName && element.nodeName.toUpperCase() != 'BODY')
				{
					if (element.nodeType != 3)
					{
						bxTag = this.editor.GetBxTag(element);
						if (bxTag && bxTag.tag == 'surrogate_dd')
						{
							var origTag = this.editor.GetBxTag(bxTag.params.origId);
							element = this.editor.GetIframeElement(origTag.id);
							this.targetItems[origTag.tag] = {element: element, bxTag: origTag};
							this.nodes = [element];
							this.tagIndex[origTag.tag] = 0;
							iter = 0;
							element = element.parentNode;
							continue;
						}
						else if (bxTag && bxTag.tag && this.items[bxTag.tag])
						{
							this.nodes = [element];
							this.targetItems[bxTag.tag] = {element: element, bxTag: bxTag.tag};
							this.nodes = [element];
							this.tagIndex[bxTag.tag] = 0;
							iter = 0;
							element = element.parentNode;
							continue;
						}

						label = element.nodeName;
						this.targetItems[label] = element;
						this.nodes.push(element);
						this.tagIndex[label] = this.nodes.length - 1;
					}
					iter++;
				}

				if (!element ||
					element.nodeName && element.nodeName.toUpperCase() == 'BODY' ||
					iter >= maxIter)
				{
					break;
				}

				element = element.parentNode;
			}

			for (i in this.items)
			{
				if (this.items.hasOwnProperty(i) && this.tagIndex[i] != undefined)
				{
					if (arItems.length > 0)
					{
						arItems.push({separator : true});
					}

					for (j = 0; j < this.items[i].length; j++)
					{
						this.items[i][j].title = this.items[i][j].title || this.items[i][j].text;
						arItems.push(this.items[i][j]);
					}
				}
			}

			if (arItems.length == 0)
			{
				var def = this.items['DEFAULT'];
				for (j = 0; j < def.length; j++)
				{
					def[j].title = def[j].title || def[j].text;
					arItems.push(def[j]);
				}
			}


			var
				x = e.clientX,
				y = e.clientY;

			if (!this.dummyTarget)
			{
				this.dummyTarget = this.editor.dom.iframeCont.appendChild(BX.create('DIV', {props: {className: 'bxhtmled-dummy-target'}}));
			}

			this.dummyTarget.style.left = x + 'px';
			this.dummyTarget.style.top = y + 'px';


			// TODO: !!!!
//			bindElement.OPENER = new BX.COpener({
//				DIV: bindElement,
//				MENU: menu,
//				TYPE: 'click',
//				ACTIVE_CLASS: (typeof params.active_class != 'undefined') ? params.active_class : 'adm-btn-active',
//				CLOSE_ON_CLICK: (typeof params.close_on_click != 'undefined') ? !!params.close_on_click : true
//			});

			BX.PopupMenu.show(this.lastMenuId, this.dummyTarget, arItems, {
					events: {
						onPopupClose: BX.proxy(this.Hide, this)
					},
					offsetLeft: 1,
					zIndex: 2005
				}
			);

			this.isOpened = true;
			BX.addCustomEvent(this.editor, 'OnIframeClick', BX.proxy(this.Hide, this));
			BX.addCustomEvent(this.editor, 'OnIframeKeyup', BX.proxy(this.CheckEscapeClose, this));
		},

		Hide: function()
		{
			if (this.lastMenuId)
			{
				var _this = this;
				this.contextMenuShownTimeout = setTimeout(function(){_this.editor.contextMenuShown = false;}, 300);
				BX.PopupMenu.destroy(this.lastMenuId);
				this.lastMenuId = null;
				this.isOpened = false;

				BX.removeCustomEvent(this.editor, 'OnIframeClick', BX.proxy(this.Hide, this));
				BX.removeCustomEvent(this.editor, 'OnIframeKeyup', BX.proxy(this.CheckEscapeClose, this));
			}
		},

		CheckEscapeClose: function(e, keyCode)
		{
			if (keyCode == this.editor.KEY_CODES['escape'])
				this.Hide();
		},

		GetTargetItem: function(tag)
		{
			return tag ? (this.targetItems[tag] || null) : this.targetItems;
		}
	};

	function Toolbar(editor, topControls)
	{
		this.editor = editor;
		this.pCont = editor.dom.toolbar;
		this.controls = {};
		this.bCompact = false;
		this.topControls = topControls;
		this.showMoreButton = false;
		this.Init();
	}

	Toolbar.prototype = {
		Init: function()
		{
			this.BuildControls();

			// Init Event handlers
			BX.addCustomEvent(this.editor, "OnIframeFocus", BX.delegate(this.EnableWysiwygButtons, this));
			BX.addCustomEvent(this.editor, "OnTextareaFocus", BX.delegate(this.DisableWysiwygButtons, this));
		},

		BuildControls: function()
		{
			BX.cleanNode(this.pCont);
			var
				i,
				wrap, moreCont, cont,
				map = this.GetControlsMap(),
				wraps = {
					left: this.pCont.appendChild(BX.create('span', {props: {className: 'bxhtmled-top-bar-left-wrap'}})),
					main: this.pCont.appendChild(BX.create('span', {props: {className: 'bxhtmled-top-bar-wrap'}})),
					right: this.pCont.appendChild(BX.create('span', {props: {className: 'bxhtmled-top-bar-right-wrap'}})),
					hidden: this.pCont.appendChild(BX.create('span', {props: {className: 'bxhtmled-top-bar-hidden-wrap'}}))
				};

			this.hiddenWrap = wraps.hidden;

			for (i = 0; i < map.length; i++)
			{
				if(map[i].hidden)
				{
					map[i].wrap = 'hidden';
					this.showMoreButton = true;
				}
				wrap = wraps[(map[i].wrap || 'main')];

				if (map[i].separator)
				{
					wrap.appendChild(this.GetSeparator()); // Show separator
				}
				else if(this.topControls[map[i].id])
				{
					if (!this.controls[map[i].id])
					{
						this.controls[map[i].id] = new this.topControls[map[i].id](this.editor, wrap);
					}
					else
					{
						cont = this.controls[map[i].id].GetPopupBindCont ? this.controls[map[i].id].GetPopupBindCont() : this.controls[map[i].id].GetCont();

						if ((this.bCompact && !map[i].compact) || map[i].hidden)
						{
							if (!moreCont)
							{
								moreCont = this.controls.More.GetPopupCont();
							}
							moreCont.appendChild(cont);
						}
						else
						{
							wrap.appendChild(cont);
						}
					}
				}
			}
		},

		GetControlsMap: function()
		{
			var res = [
				//{id: 'SearchButton', wrap: 'left', compact: true},
				{id: 'ChangeView', wrap: 'left', compact: true, sort: 10},
				{id: 'Undo', compact: false, sort: 20},
				{id: 'Redo', compact: false, sort: 30},
				{id: 'StyleSelector', compact: true, sort: 40},
				{id: 'FontSelector', compact: false, sort: 50},
				{id: 'FontSize', compact: false, sort: 60},
				{separator: true, compact: false, sort: 70},
				{id: 'Bold', compact: true, sort: 80},
				{id: 'Italic', compact: true, sort: 90},
				{id: 'Underline', compact: true, sort: 100},
				{id: 'Strikeout', compact: true, sort: 110},
				{id: 'RemoveFormat', compact: true, sort: 120},
				{id: 'Color', compact: true, sort: 130},
				{separator: true, compact: false, sort: 140},
				{id: 'OrderedList', compact: true, sort: 150},
				{id: 'UnorderedList', compact: true, sort: 160},
				{id: 'IndentButton', compact: true, sort: 170},
				{id: 'OutdentButton', compact: true, sort: 180},
				{id: 'AlignList',compact: true, sort: 190},
				{separator: true, compact: false, sort: 200},
				{id: 'InsertLink', compact: true, sort: 210},
				{id: 'InsertImage', compact: true, sort: 220},
				{id: 'InsertVideo', compact: true, sort: 230},
				{id: 'InsertAnchor', compact: false, sort: 240},
				{id: 'InsertTable', compact: false, sort: 250},
				{id: 'InsertChar', compact: false, hidden: true, sort: 260},
				{id: 'PrintBreak', compact: false, hidden: true, sort: 270},
//				{id: 'PageBreak', compact: false, hidden: true, sort: 280},
//				{id: 'InsertHr', compact: false, hidden: true, sort: 290},
				{id: 'TemplateSelector', compact: false, sort: 300},
				{id: 'Fullscreen', compact: true, sort: 310},
				{id: 'More', compact: true, sort: 320}
				//{id: 'Settings',  wrap: 'right', compact: true, sort: 500}
			];
//			IndentButton: IndentButton,
//			OutdentButton: OutdentButton,
//			AlignList: AlignList,

			this.editor.On("GetControlsMap", [res]);

			res = res.sort(function(a, b){return a.sort - b.sort});

			return res;
		},

		GetSeparator: function()
		{
			return BX.create('span', {props: {className: 'bxhtmled-top-bar-separator'}});
		},

		AddButton: function()
		{
		},

		GetHeight: function()
		{
			if (!this.height)
				this.height = parseInt(this.editor.dom.toolbarCont.offsetHeight);

			return this.height;
		},

		DisableWysiwygButtons: function(bDisable)
		{
			bDisable = bDisable !== false;
			for (var i in this.controls)
			{
				if (this.controls.hasOwnProperty(i) && typeof this.controls[i].Disable == 'function' && this.controls[i].disabledForTextarea !== false)
					this.controls[i].Disable(bDisable);
			}
		},

		EnableWysiwygButtons: function()
		{
			this.DisableWysiwygButtons(false);
		},

		AdaptControls: function(width)
		{
			var bCompact = width < this.editor.NORMAL_WIDTH;
			if (bCompact || this.showMoreButton)
			{
				this.controls.More.GetCont().style.display = '';
			}
			else
			{
				this.controls.More.GetCont().style.display = 'none';
			}
			this.controls.More.Close();

			if (!bCompact && this.showMoreButton)
			{
				var moreCont = this.controls.More.GetPopupCont();
				while (this.hiddenWrap.firstChild)
				{
					moreCont.appendChild(this.hiddenWrap.firstChild);
				}
			}

			if (this.bCompact != bCompact)
			{
				this.bCompact = bCompact;
				this.BuildControls();
			}
		}
	};

	function NodeNavi(editor)
	{
		this.editor = editor;
		this.bShown = false;
		this.pCont = editor.dom.navCont;
		this.controls = {};
		this.Init();
	}

	NodeNavi.prototype = {
		Init: function()
		{
			BX.addCustomEvent(this.editor, "OnIframeMouseDown", BX.proxy(this.OnIframeMousedown, this));
			BX.addCustomEvent(this.editor, "OnIframeKeyup", BX.proxy(this.OnIframeKeyup, this));
			BX.addCustomEvent(this.editor, "OnTextareaFocus", BX.delegate(this.Disable, this));
			BX.addCustomEvent(this.editor, "OnHtmlContentChangedByControl", BX.delegate(this.OnIframeKeyup, this));
			BX.bind(this.pCont, 'click', BX.delegate(this.ShowMenu, this));

			var _this = this;

			this.items = {
				// Surrogates
				'php' : function(node, bxTag)
				{
					_this.editor.GetDialog('Source').Show(bxTag);
				},
				'anchor' : function(node, bxTag)
				{
					_this.editor.GetDialog('Anchor').Show(bxTag);
				},
				'javascript' : function(node, bxTag)
				{
					_this.editor.GetDialog('Source').Show(bxTag);
				},
				'htmlcomment' : function(node, bxTag)
				{
					_this.editor.GetDialog('Source').Show(bxTag);
				},
				'iframe' : function(node, bxTag)
				{
					_this.editor.GetDialog('Source').Show(bxTag);
				},
				'style' : function(node, bxTag)
				{
					_this.editor.GetDialog('Source').Show(bxTag);
				},
				'video' : function(node, bxTag)
				{
					_this.editor.GetDialog('Video').Show(bxTag);
				},
				'component' : function(node, bxTag)
				{
					_this.editor.components.ShowPropertiesDialog(bxTag.params, _this.editor.GetBxTag(bxTag.surrogateId));
				},
				'printbreak' : false,

				// Nodes
				'A' : function(node)
				{
					_this.editor.GetDialog('Link').Show([node]);
				},
				'IMG' : function(node)
				{
					_this.editor.GetDialog('Image').Show([node]);
				},
				'TABLE' : function(node)
				{
					_this.editor.GetDialog('Table').Show([node]);
				},
				'DEFAULT' : function(node)
				{
					_this.editor.GetDialog('Default').Show([node]);
				}
			};
		},

		Show: function(bShow)
		{
			this.bShown = bShow = bShow !== false;
			this.pCont.style.display = bShow ? 'block' : 'none';
		},

		GetHeight: function()
		{
			if (!this.bShown)
				return 0;

			if (!this.height)
				this.height = parseInt(this.pCont.offsetHeight);

			return this.height;
		},

		OnIframeMousedown: function(e, target, bxTag)
		{
			this.BuildNavi(target);
		},

		OnIframeKeyup: function(e, keyCode, target)
		{
			this.BuildNavi(target);
		},

		BuildNavi: function(node)
		{
			BX.cleanNode(this.pCont);
			if (!node)
			{
				node = this.editor.GetIframeDoc().body;
			}
			this.nodeIndex = [];
			var itemCont, label, bxTag;
			while (node)
			{
				if (node.nodeType != 3)
				{
					bxTag = this.editor.GetBxTag(node);
					if (bxTag.tag)
					{
						if (bxTag.tag == "surrogate_dd")
						{
							node = node.parentNode;
							continue;
						}

						BX.cleanNode(this.pCont);
						this.nodeIndex = [];

						label = bxTag.name || bxTag.tag;
					}
					else
					{
						label = node.nodeName;
					}

					itemCont = BX.create("SPAN", {props: {className: "bxhtmled-nav-item"}, text: label});
					itemCont.setAttribute('data-bx-node-ind', this.nodeIndex.length.toString());

					this.nodeIndex.push({node: node, bxTag: bxTag.tag});

					if (this.pCont.firstChild)
					{
						this.pCont.insertBefore(itemCont, this.pCont.firstChild);
					}
					else
					{
						this.pCont.appendChild(itemCont);
					}
				}
				if (node.nodeName && node.nodeName.toUpperCase() == 'BODY')
				{
					break;
				}

				node = node.parentNode;
			}
		},

		ShowMenu: function(e)
		{
			if (!this.nodeIndex)
			{
				return;
			}

			var
				_this = this,
				nodeIndex,
				origNode,
				target;

			if (e.target)
			{
				target = e.target;
			}
			else if (e.srcElement)
			{
				target = e.srcElement;
			}
			if (target.nodeType == 3)
			{
				target = target.parentNode;
			}

			if (target)
			{
				nodeIndex = target.getAttribute('data-bx-node-ind');
				if (!this.nodeIndex[nodeIndex])
				{
					target = BX.findParent(target, function(node)
					{
						return node == _this.pCont || (node.getAttribute && node.getAttribute('data-bx-node-ind') >= 0);
					}, this.pCont);
					nodeIndex = target.getAttribute('data-bx-node-ind')
				}

				if (this.nodeIndex[nodeIndex])
				{
					var id = 'bx_node_nav_' + Math.round(Math.random() * 1000000000);
					origNode = this.nodeIndex[nodeIndex].node;

					var arItems = [];
					if (origNode.nodeName && origNode.nodeName.toUpperCase() != 'BODY')
					{
						if (!this.nodeIndex[nodeIndex].bxTag || !this.editor.phpParser.surrogateTags[this.nodeIndex[nodeIndex].bxTag])
						{
							arItems.push({
								text : BX.message('NodeSelect'),
								title : BX.message('NodeSelect'),
								className : "",
								onclick: function()
								{
									_this.editor.action.Exec('selectNode', origNode);
									this.popupWindow.close();
									this.popupWindow.destroy();
								}
							});
						}

						arItems.push({
							text : BX.message('NodeRemove'),
							title : BX.message('NodeRemove'),
							className : "",
							onclick: function()
							{
								if (origNode && origNode.parentNode)
								{
									_this.BuildNavi(origNode.parentNode);
									_this.editor.selection.RemoveNode(origNode);
								}
								this.popupWindow.close();
								this.popupWindow.destroy();
							}
						});

						var showProps = !(this.nodeIndex[nodeIndex] && this.nodeIndex[nodeIndex].bxTag && this.items[this.nodeIndex[nodeIndex].bxTag] == false);
						if (showProps)
						{
							arItems.push({
								text : BX.message('NodeProps'),
								title : BX.message('NodeProps'),
								className : "",
								onclick: function()
								{
									_this.ShowNodeProperties(origNode);
									this.popupWindow.close();
									this.popupWindow.destroy();
								}
							});
						}
					}
					else
					{
						arItems = [
							{
								text : BX.message('NodeSelectBody'),
								title : BX.message('NodeSelectBody'),
								className : "",
								onclick: function()
								{
									_this.editor.iframeView.CheckContentLastChild();
									_this.editor.action.Exec('selectNode', origNode);
									_this.editor.Focus();
									this.popupWindow.close();
									this.popupWindow.destroy();
								}
							},
							{
								text : BX.message('NodeRemoveBodyContent'),
								title : BX.message('NodeRemoveBodyContent'),
								className : "",
								onclick: function()
								{
									_this.BuildNavi(origNode);
									_this.editor.On('OnHtmlContentChangedByControl');
									_this.editor.iframeView.Clear();
									_this.editor.util.Refresh(origNode);
									_this.editor.synchro.FullSyncFromIframe();
									_this.editor.Focus();

									this.popupWindow.close();
									this.popupWindow.destroy();
								}
							}
						];
					}


					BX.PopupMenu.show(id + "_menu", target, arItems, {
							overlay: {opacity: 1},
							events: {
								onPopupClose: function()
								{
									//BX.removeClass(this.bindElement, "bxec-add-more-over");
								}
							},
							offsetLeft: 1
						}
					);
				}
			}
		},

		ShowNodeProperties: function(node)
		{
			var bxTag, key;
			if (node.nodeName && node.nodeType == 1)
			{
				bxTag = this.editor.GetBxTag(node);
				key = bxTag.tag ? bxTag.tag : node.nodeName;

				if (this.items[key] && typeof this.items[key] == 'function')
				{
					this.items[key](node, bxTag);
				}
				else
				{
					this.items['DEFAULT'](node, bxTag);
				}
			}
		},

		// TODO: hide it ??
		Disable: function()
		{
			this.BuildNavi(false);
		},

		Enable: function()
		{
		}
	};

	function Overlay(editor, params)
	{
		this.editor = editor;
		this.id = 'bxeditor_overlay' + this.editor.id;
		this.zIndex = params && params.zIndex ? params.zIndex : 3001;
	}

	Overlay.prototype =
	{
		Create: function ()
		{
			this.bCreated = true;
			this.bShown = false;
			var ws = BX.GetWindowScrollSize();
			this.pWnd = document.body.appendChild(BX.create("DIV", {props: {id: this.id, className: "bxhtmled-overlay"}, style: {zIndex: this.zIndex, width: ws.scrollWidth + "px", height: ws.scrollHeight + "px"}}));
			this.pWnd.ondrag = BX.False;
			this.pWnd.onselectstart = BX.False;
		},

		Show: function(arParams)
		{
			if (!this.bCreated)
				this.Create();
			this.bShown = true;
			if (this.shownTimeout)
			{
				this.shownTimeout = clearTimeout(this.shownTimeout);
			}
			var ws = BX.GetWindowScrollSize();
			this.pWnd.style.display = 'block';
			this.pWnd.style.width = ws.scrollWidth + "px";
			this.pWnd.style.height = ws.scrollHeight + "px";

			if (!arParams)
			{
				arParams = {};
			}

			this.pWnd.style.zIndex = arParams.zIndex || this.zIndex;

			BX.bind(window, "resize", BX.proxy(this.Resize, this));
			return this.pWnd;
		},

		Hide: function ()
		{
			if (!this.bShown)
			{
				return;
			}
			var _this = this;
			_this.shownTimeout = setTimeout(function(){_this.bShown = false;}, 300);
			this.pWnd.style.display = 'none';
			BX.unbind(window, "resize", BX.proxy(this.Resize, this));
			this.pWnd.onclick = null;
		},

		Resize: function ()
		{
			if (this.bCreated)
			{
				var ws = BX.GetWindowScrollSize();
				this.pWnd.style.width = ws.scrollWidth + "px";
				this.pWnd.style.height = ws.scrollHeight + "px";
			}
		}
	}

	function Button(editor)
	{
		this.editor = editor;
		this.className = 'bxhtmled-top-bar-btn';
		this.activeClassName = 'bxhtmled-top-bar-btn-active';
		this.disabledClassName = 'bxhtmled-top-bar-btn-disabled';
		this.checkableAction = true;
		this.disabledForTextarea = true;
	}

	Button.prototype = {
		Create: function ()
		{
			this.pCont = BX.create("SPAN", {props: {className: this.className, title: this.title || ''}, html: '<i></i>'});
			BX.bind(this.pCont, "click", BX.delegate(this.OnClick, this));
			BX.bind(this.pCont, "mousedown", BX.delegate(this.OnMouseDown, this));
			BX.bind(this.pCont, "dblclick", function(e){return BX.PreventDefault(e);});

			if (this.action)
			{
				this.pCont.setAttribute('data-bx-type', 'action');
				this.pCont.setAttribute('data-bx-action', this.action);
				if (this.value)
					this.pCont.setAttribute('data-bx-value', this.value);

				if (this.checkableAction)
				{
					this.editor.RegisterCheckableAction(this.action, {
						action: this.action,
						control: this,
						value: this.value
					});
				}
			}
		},

		GetCont: function()
		{
			return this.pCont;
		},

		Check: function (bFlag)
		{
			if(bFlag == this.checked || this.disabled)
				return;

			this.checked = bFlag;
			if(this.checked)
			{
				BX.addClass(this.pCont, this.activeClassName);
			}
			else
			{
				BX.removeClass(this.pCont, this.activeClassName);
			}
		},

		Disable: function(bFlag)
		{
			if(bFlag != this.disabled)
			{
				this.disabled = !!bFlag;
				if(bFlag)
				{
					BX.addClass(this.pCont, this.disabledClassName);
				}
				else
				{
					BX.removeClass(this.pCont, this.disabledClassName);
				}
			}
		},

		OnClick: BX.DoNothing,
		OnMouseUp: function()
		{
			if(!this.checked)
			{
				BX.removeClass(this.pCont, this.activeClassName);
			}
			BX.unbind(document, 'mouseup', BX.proxy(this.OnMouseUp, this));
			BX.removeCustomEvent(this.editor, "OnIframeMouseUp", BX.proxy(this.OnMouseUp, this));
		},

		OnMouseDown: function()
		{
			if (!this.disabled)
			{
				this.savedRange = this.editor.selection.SaveBookmark();
				BX.addClass(this.pCont, this.activeClassName);
				BX.bind(document, 'mouseup', BX.proxy(this.OnMouseUp, this));
				BX.addCustomEvent(this.editor, "OnIframeMouseUp", BX.proxy(this.OnMouseUp, this));
			}
		},

		GetValue: function()
		{
			return !!this.checked;
		},

		SetValue: function(value)
		{
			this.Check(value);
		}
	}

	// List
	function DropDown(editor)
	{
		this.editor = editor;
		this.className = 'bxhtmled-top-bar-btn';
		this.activeClassName = 'bxhtmled-top-bar-btn-active';
		this.activeListClassName = 'bxhtmled-top-bar-btn-active';
		this.arValues = [];
		this.checkableAction = true;
		this.disabledForTextarea = true;
		this.posOffset = {top: 6, left: -4};
		this.zIndex = 3005;
	}

	DropDown.prototype = {
		Create: function ()
		{
			this.pCont = BX.create("SPAN", {props: {className: this.className}, html: '<i></i>'});
			this.pValuesCont = BX.create("DIV", {props: {className: "bxhtmled-popup bxhtmled-dropdown-cont"}, html: '<div class="bxhtmled-popup-corner"></div>'});
			if (this.title)
			{
				this.pCont.title = this.title;
			}

			if(this.zIndex)
			{
				this.pValuesCont.style.zIndex = this.zIndex;
			}

			this.valueIndex = {};
			this.pValuesContWrap = this.pValuesCont.appendChild(BX.create("DIV"));
			var but, value, _this = this;
			for (var i = 0; i < this.arValues.length; i++)
			{
				value = this.arValues[i];
				but = this.pValuesContWrap.appendChild(BX.create("SPAN", {props: {title: value.title, className: value.className}, html: '<i></i>'}));
				but.setAttribute('data-bx-dropdown-value', value.id);
				this.valueIndex[value.id] = i;

				if (value.action)
				{
					but.setAttribute('data-bx-type', 'action');
					but.setAttribute('data-bx-action', value.action);
					if (value.value)
					{
						but.setAttribute('data-bx-value', value.value);
					}
				}

				BX.bind(but, 'mousedown', function(e)
				{
					_this.SelectItem(this.getAttribute('data-bx-dropdown-value'));
					_this.editor.CheckCommand(this);
					_this.Close();
				});

				this.arValues[i].listCont = but;
			}

			if (this.action && this.checkableAction)
			{
				this.editor.RegisterCheckableAction(this.action, {
					action: this.action,
					control: this
				});
			}

			BX.bind(this.pCont, 'click', BX.proxy(this.OnClick, this));
			BX.bind(this.pCont, "mousedown", BX.delegate(this.OnMouseDown, this));
		},

		GetCont: function()
		{
			return this.pCont;
		},

		GetPopupBindCont: function()
		{
			return this.pCont;
		},

		Disable: function(bFlag)
		{
			if(bFlag != this.disabled)
			{
				this.disabled = !!bFlag;
				if(bFlag)
				{
					BX.addClass(this.pCont, 'bxhtmled-top-bar-btn-disabled');
				}
				else
				{
					BX.removeClass(this.pCont, 'bxhtmled-top-bar-btn-disabled');
				}
			}
		},

		OnKeyDown: function(e)
		{
			if(e.keyCode == 27)
			{
				this.Close();
			}
		},

		OnClick: function()
		{
			if(!this.disabled)
			{
				if (this.bOpened)
				{
					this.Close();
				}
				else
				{
					this.Open();
				}
			}
		},

		OnMouseUp: function()
		{
			this.editor.selection.RestoreBookmark();
			if(!this.checked)
			{
				BX.removeClass(this.pCont, this.activeClassName);
			}
			BX.unbind(document, 'mouseup', BX.proxy(this.OnMouseUp, this));
			BX.removeCustomEvent(this.editor, "OnIframeMouseUp", BX.proxy(this.OnMouseUp, this));
		},

		OnMouseDown: function()
		{
			if (!this.disabled)
			{
				this.savedRange = this.editor.selection.SaveBookmark();
				BX.addClass(this.pCont, this.activeClassName);
				BX.bind(document, 'mouseup', BX.proxy(this.OnMouseUp, this));
				BX.addCustomEvent(this.editor, "OnIframeMouseUp", BX.proxy(this.OnMouseUp, this));
			}
		},

		Close: function ()
		{
			var _this = this;
			this.popupShownTimeout = setTimeout(function(){_this.editor.popupShown = false;}, 300);
			BX.removeClass(this.pCont, this.activeClassName);
			this.pValuesCont.style.display = 'none';
			this.editor.overlay.Hide();

			BX.unbind(window, "keydown", BX.proxy(this.OnKeyDown, this));
			BX.unbind(document, 'mousedown', BX.proxy(this.CheckClose, this));

			BX.onCustomEvent(this, "OnPopupClose");

			this.bOpened = false;
		},

		CheckClose: function(e)
		{
			if (!this.bOpened)
			{
				return BX.unbind(document, 'mousedown', BX.proxy(this.CheckClose, this));
			}

			var pEl;
			if (e.target)
				pEl = e.target;
			else if (e.srcElement)
				pEl = e.srcElement;
			if (pEl.nodeType == 3)
				pEl = pEl.parentNode;

			if (!BX.findParent(pEl, {className: 'bxhtmled-popup'}))
			{
				this.Close();
			}
		},

		Open: function ()
		{
			this.editor.popupShown = true;
			if (this.popupShownTimeout)
			{
				this.popupShownTimeout = clearTimeout(this.popupShownTimeout);
			}
			document.body.appendChild(this.pValuesCont);
			this.pValuesCont.style.display = 'block';
			BX.addClass(this.pCont, this.activeClassName);
			var
				pOverlay = this.editor.overlay.Show({zIndex: this.zIndex - 1}),
				bindCont = this.GetPopupBindCont(),
				pos = BX.pos(bindCont),
				left = Math.round(pos.left - this.pValuesCont.offsetWidth / 2 + bindCont.offsetWidth / 2 + this.posOffset.left),
				top = Math.round(pos.bottom + this.posOffset.top),
				_this = this;

			BX.bind(window, "keydown", BX.proxy(this.OnKeyDown, this));
			pOverlay.onclick = function(){_this.Close()};

			this.pValuesCont.style.top = top + 'px';
			this.pValuesCont.style.left = left + 'px';
			this.bOpened = true;

			setTimeout(function()
			{
				BX.bind(document, 'mousedown', BX.proxy(_this.CheckClose, _this));
			},100);
		},

		SelectItem: function(id, val)
		{
			if (!val)
				val = this.arValues[this.valueIndex[id]];

			if (this.lastActiveItem)
				BX.removeClass(this.lastActiveItem, this.activeListClassName);

			if (val)
			{
				// Select value in list as active
				if (val.listCont)
				{
					this.lastActiveItem = val.listCont;
					BX.addClass(val.listCont, this.activeListClassName);
				}

				this.pCont.className = val.className;
				this.pCont.title = BX.util.htmlspecialchars(val.title || val.name || '');
			}
			else
			{
				this.pCont.className = this.className;
				this.pCont.title = this.title;
			}

			return val;
		},

		SetValue: function()
		{
		},

		GetValue: function()
		{
		}
	};

	function DropDownList(editor)
	{
		// Call parrent constructor
		DropDownList.superclass.constructor.apply(this, arguments);
		this.className = 'bxhtmled-top-bar-select';
		this.itemClassName = 'bxhtmled-dd-list-item';
		this.activeListClassName = 'bxhtmled-dd-list-item-active';
		this.disabledForTextarea = true;
	}
	BX.extend(DropDownList, DropDown);

	DropDownList.prototype.Create = function ()
	{
		this.pCont = BX.create("SPAN", {props: {className: this.className, title: this.title}, attrs: {unselectable: 'on'}, text: ''});
		if (this.width)
			this.pCont.style.width = this.width + 'px';

		this.pValuesCont = BX.create("DIV", {props: {className: "bxhtmled-popup bxhtmled-dropdown-list-cont"}, html: '<div class="bxhtmled-popup-corner"></div>'});
		this.pValuesContWrap = this.pValuesCont.appendChild(BX.create("DIV", {props: {className: "bxhtmled-dd-list-wrap"}}));
		this.valueIndex = {};

		if(this.zIndex)
		{
			this.pValuesCont.style.zIndex = this.zIndex;
		}

		var but, value, _this = this, itemClass, i, html;
		for (i = 0; i < this.arValues.length; i++)
		{
			value = this.arValues[i];
			itemClass = this.itemClassName;
			if (value.className)
				itemClass += ' ' + value.className;

			html = value.tagName ? ('<' + value.tagName + '>' + value.name + '</' + value.tagName + '>') : value.name;
			but = this.pValuesContWrap.appendChild(BX.create("SPAN", {props: {title: value.title || value.name, className: itemClass}, html: html, style: value.style}));

			but.setAttribute('data-bx-dropdown-value', value.id);
			this.valueIndex[value.id] = i;

			if (value.defaultValue)
				this.SelectItem(null, value);

			if (value.action)
			{
				but.setAttribute('data-bx-type', 'action');
				but.setAttribute('data-bx-action', value.action);
				if (value.value)
					but.setAttribute('data-bx-value', value.value);
			}

			BX.bind(but, 'mousedown', function(e)
			{
				if (!e)
					e = window.event;
				_this.SelectItem(this.getAttribute('data-bx-dropdown-value'));
				_this.editor.CheckCommand(this);
			});

			this.arValues[i].listCont = but;
		}

		if (this.action && this.checkableAction)
		{
			this.editor.RegisterCheckableAction(this.action, {
				action: this.action,
				control: this
			});
		}

		BX.bind(this.pCont, 'click', BX.proxy(this.OnClick, this));
	};

	DropDownList.prototype.SelectItem = function (valDropdown, val, bClose)
	{
		bClose = bClose !== false;
		if (!val)
		{
			val = this.arValues[this.valueIndex[valDropdown]];
		}

		if (this.lastActiveItem)
		{
			BX.removeClass(this.lastActiveItem, this.activeListClassName);
		}

		if (val)
		{
			this.pCont.innerHTML = BX.util.htmlspecialchars((val.topName || val.name || val.id));
			this.pCont.title = this.title + ': ' + BX.util.htmlspecialchars(val.title || val.name);


			// Select value in list as active
			if (val.listCont)
			{
				this.lastActiveItem = val.listCont;
				BX.addClass(val.listCont, this.activeListClassName);
			}
		}

		if (this.bOpened && bClose)
		{
			this.Close();
		}
	};

	DropDownList.prototype.SetValue = function(active, state)
	{
	};

	DropDownList.prototype.Disable = function(bFlag)
	{
		if(bFlag != this.disabled)
		{
			this.disabled = !!bFlag;
			if(bFlag)
			{
				BX.addClass(this.pCont, 'bxhtmled-top-bar-select-disabled');
			}
			else
			{
				BX.removeClass(this.pCont, 'bxhtmled-top-bar-select-disabled');
			}
		}
	};

	// Combobox with multiple choice of classes
	function ComboBox(editor, params)
	{
		this.values = [];
		this.pInput = params.input;
		this.editor = editor;
		this.value = params.value || '';
		this.defaultValue = params.defaultValue || '';
		this.posOffset = {top: 8, left: -4};
		this.zIndex = 3010;
		this.itemClassName = 'bxhtmled-dd-list-item';
		this.itemClassNameActive = 'bxhtmled-dd-list-item-active';
	}

	ComboBox.prototype = {
		Init: function()
		{
			BX.bind(this.pInput, 'focus', BX.proxy(this.Focus, this));
			BX.bind(this.pInput, 'click', BX.proxy(this.Focus, this));
			BX.bind(this.pInput, 'blur', BX.proxy(this.Blur, this));
			BX.bind(this.pInput, 'keyup', BX.proxy(this.KeyUp, this));

			this.visibleItemsLength = this.values.length;
			this.currentItem = false;
		},

		Create: function()
		{
			this.pValuesCont = BX.create("DIV", {props: {className: "bxhtmled-popup bxhtmled-combo-cont"}, html: '<div class="bxhtmled-popup-corner"></div>'});
			this.pValuesCont.style.zIndex = this.zIndex;

			if (this.pValuesContWrap)
			{
				BX.cleanNode(this.pValuesContWrap);
				this.pValuesCont.appendChild(this.pValuesContWrap);
			}
			else
			{
				this.pValuesContWrap = this.pValuesCont.appendChild(BX.create("DIV", {props: {className: "bxhtmled-dd-list-wrap"}}));

				BX.bind(this.pValuesContWrap, 'mousedown', function(e)
				{
					var target = e.target || e.srcElement;
					if (!target.getAttribute('data-bx-dropdown-value'))
					{
						target = BX.findParent(target, function(n)
						{
							return n.getAttribute && n.getAttribute('data-bx-dropdown-value');
						}, _this.pValuesContWrap);
					}

					if (target)
					{
						_this.currentItem = parseInt(target.getAttribute('data-bx-dropdown-value'), 10);
						_this.SetValueFromList();
					}

					_this.ClosePopup();
				});
			}
			this.valueIndex = {};

			var but, value, _this = this, itemClass, i, html;
			for (i = 0; i < this.values.length; i++)
			{
				value = this.values[i];
				itemClass = this.itemClassName || '';
				this.values[i].TITLE = this.values[i].TITLE || this.values[i].NAME;
				if (this.values[i].VALUE)
				{
					this.values[i].TITLE += ' (' + this.values[i].VALUE + ')';
				}
				else
				{
					this.values[i].VALUE = this.values[i].NAME;
				}

				but = this.pValuesContWrap.appendChild(BX.create("SPAN", {props: {className: itemClass}, html: value.TITLE}));
				but.setAttribute('data-bx-dropdown-value', i);
				this.values[i].cont = but;
			}

			this.bCreated = true;
		},

		KeyUp: function(e)
		{
			var keyCode = e.keyCode;
			if (keyCode == this.editor.KEY_CODES['down'])
			{
				this.SelectItem(1);
			}
			else if (keyCode == this.editor.KEY_CODES['up'])
			{
				this.SelectItem(-1);
			}
			else if (keyCode == this.editor.KEY_CODES['escape'])
			{
				if (this.bOpened)
				{
					this.ClosePopup();
					return BX.PreventDefault(e);
				}
			}
			else if (keyCode == this.editor.KEY_CODES['enter'])
			{
				if (this.bOpened)
				{
					this.SetValueFromList();
					this.ClosePopup();
					return BX.PreventDefault(e);
				}
			}
			else
			{
				this.FilterValue();
			}
		},

		FilterValue: function()
		{
			// Range
			var
				i, val,
				splitedVals = this.GetSplitedValues(),
				caretPos = this.GetCaretPos(this.pInput);

			for (i = 0; i < splitedVals.length; i++)
			{
				val = splitedVals[i];
				if (caretPos >= val.start && caretPos <= val.end)
				{
					break;
				}
			}

			// Filter values && highlight values
			this.FilterAndHighlight(val.value);
		},

		GetSplitedValues: function()
		{
			var
				arVals, i, gStart, gEnd, val,
				res = [],
				str = this.pInput.value;

			if (str.indexOf(',') === -1)
			{
				res.push(
					{
						start: 0,
						end: str.length,
						value: BX.util.trim(str)
					}
				);
			}
			else
			{
				arVals = str.split(',');
				gStart = 0;
				gEnd = 0;
				for (i = 0; i < arVals.length; i++)
				{
					val = arVals[i];
					gEnd += val.length + i;
					res.push(
						{
							start: gStart,
							end: gEnd,
							value: BX.util.trim(val)
						}
					);
					gStart = gEnd;
				}
			}

			return res;
		},

		FilterAndHighlight: function(needle)
		{
			needle = BX.util.trim(needle);
			var val, i, showPopup = false, pos;

			this.visibleItemsLength = 0;
			for (i = 0; i < this.values.length; i++)
			{
				val = this.values[i];
				if (needle === '')
				{
					showPopup = true;
					val.cont.style.display = '';
					this.visibleItemsLength++;
				}
				else
				{
					pos = val.TITLE.indexOf(needle);
					if (pos !== -1 || needle == '')
					{
						val.cont.innerHTML = BX.util.htmlspecialchars(val.TITLE.substr(0, pos)) + '<b>' + BX.util.htmlspecialchars(needle) + '</b>' + BX.util.htmlspecialchars(val.TITLE.substr(pos + needle.length));
						showPopup = true;
						val.cont.style.display = '';
						val.cont.setAttribute('data-bx-dropdown-value', this.visibleItemsLength);
						this.visibleItemsLength++;
					}
					else
					{
						val.cont.innerHTML = BX.util.htmlspecialchars(val.TITLE);
						val.cont.style.display = 'none';
					}
				}
			}

			this.currentItem = false;

			if (showPopup && !this.bOpened)
			{
				this.ShowPopup();
			}
			else if (!showPopup && this.bOpened)
			{
				this.ClosePopup();
			}
		},

		GetCaretPos: function(input)
		{
			var caretPos = 0;

			// IE Support
			if (document.selection)
			{
				BX.focus(input);
				var oSel = document.selection.createRange();
				oSel.moveStart ('character', - input.value.length);
				// The caret position is selection length
				caretPos = oSel.text.length;
			}
			else if (input.selectionStart || input.selectionStart == '0')
			{
				caretPos = input.selectionStart;
			}
			return (caretPos);
		},

		SetValueFromList: function()
		{
			var ind = 0, val, i;
			for (i = 0; i < this.values.length; i++)
			{
				val = this.values[i];
				if (val.cont.style.display != 'none')
				{
					if (ind == this.currentItem)
					{
						BX.addClass(val.cont, this.itemClassNameActive);
						break;
					}
					ind++;
				}
			}

			var
				splVal,
				splitedVals = this.GetSplitedValues(),
				caretPos = this.GetCaretPos(this.pInput);

			for (i = 0; i < splitedVals.length; i++)
			{
				splVal = splitedVals[i];
				if (caretPos >= splVal.start && caretPos <= splVal.end)
				{
					break;
				}
			}

			var
				curValue = this.pInput.value,
				before = curValue.substr(0, splVal.start),
				after = curValue.substr(splVal.end);

			before = before.replace(/^[\s\r\n\,]+/g, '').replace(/[\s\r\n\,]+$/g, '');
			after = after.replace(/^[\s\r\n\,]+/g, '').replace(/[\s\r\n\,]+$/g, '');

			this.pInput.value = before +
				(before == '' ? '' : ', ') +
				val.VALUE +
				(after == '' ? '' : ', ') +
				after;

			this.FilterAndHighlight('');
		},

		SelectItem: function(delta)
		{
			var ind, val, i, len;
			if (this.currentItem === false)
			{
				this.currentItem = 0;
			}
			else if(delta !== undefined)
			{
				this.currentItem += delta;

				if (this.currentItem > this.visibleItemsLength - 1)
				{
					this.currentItem = 0;
				}
				else if(this.currentItem < 0)
				{
					this.currentItem = this.visibleItemsLength - 1;
				}
			}

			var selected = this.pValuesContWrap.querySelectorAll("." + this.itemClassNameActive);
			if (selected)
			{
				for (i = 0; i < selected.length; i++)
				{
					BX.removeClass(selected[i], this.itemClassNameActive);
				}
			}

			ind = 0;
			len = this.values.length;
			for (i = 0; i < this.values.length; i++)
			{
				val = this.values[i];
				if (val.cont.style.display != 'none')
				{
					if (ind == this.currentItem)
					{
						BX.addClass(val.cont, this.itemClassNameActive);
						break;
					}
					ind++;
				}
			}
		},

		Focus: function(e)
		{
			if (this.values.length > 0 && !this.bFocused)
			{
				BX.focus(this.pInput);
				this.bFocused = true;
				if (this.value == this.defaultValue)
				{
					this.value = '';
				}

				this.ShowPopup();
			}
		},

		Blur: function()
		{
			if (this.values.length > 0 && this.bFocused)
			{
				this.bFocused = false;
				this.ClosePopup();
			}
		},

		ShowPopup: function()
		{
			if (!this.bCreated)
			{
				this.Create();
			}

			this.editor.popupShown = true;
			if (this.popupShownTimeout)
			{
				this.popupShownTimeout = clearTimeout(this.popupShownTimeout);
			}

			document.body.appendChild(this.pValuesCont);
			this.pValuesCont.style.display = 'block';

			var
				i,
				pos = BX.pos(this.pInput),
				left = pos.left + this.posOffset.left,
				top = pos.bottom + this.posOffset.top;

			this.pValuesCont.style.top = top + 'px';
			this.pValuesCont.style.left = left + 'px';
			this.bOpened = true;

			var selected = this.pValuesContWrap.querySelectorAll("." + this.itemClassNameActive);
			if (selected)
			{
				for (i = 0; i < selected.length; i++)
				{
					BX.removeClass(selected[i], this.itemClassNameActive);
				}
			}

			BX.onCustomEvent(this, "OnComboPopupOpen");
		},

		ClosePopup: function()
		{
			var _this = this;
			this.popupShownTimeout = setTimeout(function(){_this.editor.popupShown = false;}, 300);
			this.pValuesCont.style.display = 'none';
			this.editor.overlay.Hide();

			this.bOpened = false;
			BX.onCustomEvent(this, "OnComboPopupClose");
		},

		OnChange: function()
		{
		},

		CheckClose: function(e)
		{
			if (!this.bOpened)
			{
				return BX.unbind(document, 'mousedown', BX.proxy(this.CheckClose, this));
			}

			var pEl;
			if (e.target)
				pEl = e.target;
			else if (e.srcElement)
				pEl = e.srcElement;
			if (pEl.nodeType == 3)
				pEl = pEl.parentNode;

			if (!BX.findParent(pEl, {className: 'bxhtmled-popup'}))
				this.Close();
		}
	};

	function ClassSelector(editor, params)
	{
		// Call parrent constructor
		ClassSelector.superclass.constructor.apply(this, arguments);
		this.filterTag = params.filterTag || '';
		this.lastTemplateId = this.editor.GetTemplateId();
		this.values = this.GetClasses();
		this.Init();
	};
	BX.extend(ClassSelector, ComboBox);

	ClassSelector.prototype.OnChange = function()
	{
		if (this.lastTemplateId != this.editor.GetTemplateId())
		{
			this.lastTemplateId = this.editor.GetTemplateId();
			this.values = this.GetClasses();
			this.bCreated = false;
		}
	};

	ClassSelector.prototype.GetClasses = function()
	{
		var classes = this.editor.GetCurrentCssClasses(this.filterTag);
		this.values = [];
		if (classes && classes.length > 0)
		{
			for (var i = 0; i < classes.length; i++)
			{
				this.values.push(
					{
						NAME: classes[i].className
					}
				);
			}
		}
		return this.values;
	};


	function ColorPicker(editor, wrap)
	{
		this.editor = editor;
		this.className = 'bxhtmled-top-bar-btn bxhtmled-top-bar-color';
		this.activeClassName = 'bxhtmled-top-bar-btn-active';
		this.disabledClassName = 'bxhtmled-top-bar-btn-disabled';
		this.bCreated = false;
		this.zIndex = 3006;
		this.disabledForTextarea = true;
		this.posOffset = {top: 6, left: 0};
		this.id = 'color';
		this.title = BX.message('BXEdForeColor');
		this.actionColor = 'foreColor';
		this.actionBg = 'backgroundColor';
		this.Create();

		if (wrap)
		{
			wrap.appendChild(this.GetCont());
		}
	}

	ColorPicker.prototype = {
		Create: function ()
		{
			this.pCont = BX.create("SPAN", {props: {className: this.className, title: this.title || ''}});
			this.pContLetter = this.pCont.appendChild(BX.create("SPAN", {props: {className: 'bxhtmled-top-bar-btn-text'}, html: 'A'}));
			this.pContStrip = this.pCont.appendChild(BX.create("SPAN", {props: {className: 'bxhtmled-top-bar-color-strip'}}));
			this.currentAction = this.actionColor;
			BX.bind(this.pCont, "click", BX.delegate(this.OnClick, this));
			BX.bind(this.pCont, "mousedown", BX.delegate(this.OnMouseDown, this));

			this.editor.RegisterCheckableAction(this.actionColor, {
				action: this.actionColor,
				control: this,
				value: this.value
			});
			this.editor.RegisterCheckableAction(this.actionBg, {
				action: this.actionBg,
				control: this,
				value: this.value
			});
		},

		GetCont: function()
		{
			return this.pCont;
		},

		Check: function (bFlag)
		{
			if(bFlag != this.checked && !this.disabled)
			{
				this.checked = bFlag;
				if(this.checked)
				{
					BX.addClass(this.pCont, 'bxhtmled-top-bar-btn-active');
				}
				else
				{
					BX.removeClass(this.pCont, 'bxhtmled-top-bar-btn-active');
				}
			}
		},

		Disable: function (bFlag)
		{
			if(bFlag != this.disabled)
			{
				this.disabled = !!bFlag;
				if(bFlag)
				{
					BX.addClass(this.pCont, 'bxhtmled-top-bar-btn-disabled');
				}
				else
				{
					BX.removeClass(this.pCont, 'bxhtmled-top-bar-btn-disabled');
				}
			}
		},

		GetValue: function()
		{
			return !!this.checked;
		},

		SetValue: function(active, state, action)
		{
			if (state && state[0])
			{
				var color = action == this.actionColor ? state[0].style.color : state[0].style.backgroundColor;
				this.SelectColor(color, action);
			}
			else
			{
				this.SelectColor(null, action);
			}
		},

		OnClick: function()
		{
			if(this.disabled)
			{
				return false;
			}
			if (this.bOpened)
			{
				return this.Close();
			}
			this.Open();
		},

		OnMouseUp: function()
		{
			this.editor.selection.RestoreBookmark();

			if(!this.checked)
			{
				BX.removeClass(this.pCont, this.activeClassName);
			}
			BX.unbind(document, 'mouseup', BX.proxy(this.OnMouseUp, this));
			BX.removeCustomEvent(this.editor, "OnIframeMouseUp", BX.proxy(this.OnMouseUp, this));
		},

		OnMouseDown: function()
		{
			if (!this.disabled)
			{
				this.editor.selection.SaveBookmark();

				BX.addClass(this.pCont, this.activeClassName);
				BX.bind(document, 'mouseup', BX.proxy(this.OnMouseUp, this));
				BX.addCustomEvent(this.editor, "OnIframeMouseUp", BX.proxy(this.OnMouseUp, this));
			}
		},

		Close: function ()
		{
			var _this = this;
			this.popupShownTimeout = setTimeout(function(){_this.editor.popupShown = false;}, 300);
			this.pValuesCont.style.display = 'none';
			BX.removeClass(this.pCont, this.activeClassName);
			this.editor.overlay.Hide();
			BX.unbind(window, "keydown", BX.proxy(this.OnKeyDown, this));
			BX.unbind(document, 'mousedown', BX.proxy(this.CheckClose, this));
			this.bOpened = false;
		},

		CheckClose: function(e)
		{
			if (!this.bOpened)
			{
				return BX.unbind(document, 'mousedown', BX.proxy(this.CheckClose, this));
			}

			var pEl;
			if (e.target)
				pEl = e.target;
			else if (e.srcElement)
				pEl = e.srcElement;
			if (pEl.nodeType == 3)
				pEl = pEl.parentNode;

			if (!BX.findParent(pEl, {className: 'lhe-colpick-cont'}))
			{
				this.Close();
			}
		},

		Open: function ()
		{
			this.editor.popupShown = true;
			if (this.popupShownTimeout)
			{
				this.popupShownTimeout = clearTimeout(this.popupShownTimeout);
			}
			var _this = this;
			if (!this.bCreated)
			{
				this.pValuesCont = document.body.appendChild(BX.create("DIV", {props: {className: "bxhtmled-popup  bxhtmled-color-cont"}, style: {zIndex: this.zIndex}, html: '<div class="bxhtmled-popup-corner"></div>'}));

				this.pTextColorLink = this.pValuesCont.appendChild(BX.create("SPAN", {props: {className: "bxhtmled-color-link bxhtmled-color-link-active"}, text: BX.message('BXEdForeColor')}));
				this.pTextColorLink.setAttribute('data-bx-type', 'changeColorAction');
				this.pTextColorLink.setAttribute('data-bx-value', this.actionColor);
				this.pBgColorLink = this.pValuesCont.appendChild(BX.create("SPAN", {props: {className: "bxhtmled-color-link"}, text: BX.message('BXEdBackColor')}));
				this.pBgColorLink.setAttribute('data-bx-type', 'changeColorAction');
				this.pBgColorLink.setAttribute('data-bx-value', this.actionBg);

				this.pValuesContWrap = this.pValuesCont.appendChild(BX.create("DIV", {props: {className: "bxhtmled-color-wrap"}}));

				BX.bind(this.pValuesCont, 'mousedown', function(e)
				{
					var target = e.target || e.srcElement, type;

					if (target != _this.pValuesCont)
					{
						type = (target && target.getAttribute) ? target.getAttribute('data-bx-type') : null;
						if (!type)
						{
							target = BX.findParent(target, function(n)
							{
								return n == _this.pValuesCont || (n.getAttribute && n.getAttribute('data-bx-type'));
							}, _this.pValuesCont);
							type = (target && target.getAttribute) ? target.getAttribute('data-bx-type') : null;
						}

						if (type == 'changeColorAction')
						{
							_this.SetMode(target.getAttribute('data-bx-value'));
							BX.PreventDefault(e);
						}
						else if (target && type)
						{
							target.setAttribute('data-bx-action', _this.currentAction);
							_this.editor.CheckCommand(target);
							_this.SelectColor(target.getAttribute('data-bx-value'));
						}
					}
				});

				var arColors = [
					'#FF0000', '#FFFF00', '#00FF00', '#00FFFF', '#0000FF', '#FF00FF', '#FFFFFF', '#EBEBEB', '#E1E1E1', '#D7D7D7', '#CCCCCC', '#C2C2C2', '#B7B7B7', '#ACACAC', '#A0A0A0', '#959595',
					'#EE1D24', '#FFF100', '#00A650', '#00AEEF', '#2F3192', '#ED008C', '#898989', '#7D7D7D', '#707070', '#626262', '#555555', '#464646', '#363636', '#262626', '#111111', '#000000',
					'#F7977A', '#FBAD82', '#FDC68C', '#FFF799', '#C6DF9C', '#A4D49D', '#81CA9D', '#7BCDC9', '#6CCFF7', '#7CA6D8', '#8293CA', '#8881BE', '#A286BD', '#BC8CBF', '#F49BC1', '#F5999D',
					'#F16C4D', '#F68E54', '#FBAF5A', '#FFF467', '#ACD372', '#7DC473', '#39B778', '#16BCB4', '#00BFF3', '#438CCB', '#5573B7', '#5E5CA7', '#855FA8', '#A763A9', '#EF6EA8', '#F16D7E',
					'#EE1D24', '#F16522', '#F7941D', '#FFF100', '#8FC63D', '#37B44A', '#00A650', '#00A99E', '#00AEEF', '#0072BC', '#0054A5', '#2F3192', '#652C91', '#91278F', '#ED008C', '#EE105A',
					'#9D0A0F', '#A1410D', '#A36209', '#ABA000', '#588528', '#197B30', '#007236', '#00736A', '#0076A4', '#004A80', '#003370', '#1D1363', '#450E61', '#62055F', '#9E005C', '#9D0039',
					'#790000', '#7B3000', '#7C4900', '#827A00', '#3E6617', '#045F20', '#005824', '#005951', '#005B7E', '#003562', '#002056', '#0C004B', '#30004A', '#4B0048', '#7A0045', '#7A0026'
				];

				var
					row, cell, colorCell,
					tbl = BX.create("TABLE", {props:{className: 'bxhtmled-color-tbl'}}),
					i, l = arColors.length;

				row = tbl.insertRow(-1);
				cell = row.insertCell(-1);
				cell.colSpan = 8;
				var defBut = cell.appendChild(BX.create("SPAN", {props:{className: 'bxhtmled-color-def-but'}}));
				defBut.innerHTML = BX.message('BXEdDefaultColor');
				defBut.setAttribute('data-bx-type', 'action');
				defBut.setAttribute('data-bx-action', this.action);
				defBut.setAttribute('data-bx-value', '');

				colorCell = row.insertCell(-1);
				colorCell.colSpan = 8;
				colorCell.className = 'bxhtmled-color-inp-cell';
				colorCell.style.backgroundColor = arColors[38];

				for(i = 0; i < l; i++)
				{
					if (Math.round(i / 16) == i / 16) // new row
					{
						row = tbl.insertRow(-1);
					}

					cell = row.insertCell(-1);
					cell.innerHTML = '&nbsp;';
					cell.className = 'bxhtmled-color-col-cell';
					cell.style.backgroundColor = arColors[i];
					cell.id = 'bx_color_id__' + i;

					cell.setAttribute('data-bx-type', 'action');
					cell.setAttribute('data-bx-action', this.action);
					cell.setAttribute('data-bx-value', arColors[i]);

					cell.onmouseover = function (e)
					{
						this.className = 'bxhtmled-color-col-cell bxhtmled-color-col-cell-over';
						colorCell.style.backgroundColor = arColors[this.id.substring('bx_color_id__'.length)];
					};
					cell.onmouseout = function (e){this.className = 'bxhtmled-color-col-cell';};
					cell.onclick = function (e)
					{
						_this.Select(arColors[this.id.substring('bx_color_id__'.length)]);
					};
				}

				this.pValuesContWrap.appendChild(tbl);
				this.bCreated = true;
			}
			document.body.appendChild(this.pValuesCont);

			this.pValuesCont.style.display = 'block';
			var
				pOverlay = this.editor.overlay.Show(),
				pos = BX.pos(this.pCont),
				left = pos.left - this.pValuesCont.offsetWidth / 2 + this.pCont.offsetWidth / 2 + this.posOffset.left,
				top = pos.bottom + this.posOffset.top;

			BX.bind(window, "keydown", BX.proxy(this.OnKeyDown, this));
			BX.addClass(this.pCont, this.activeClassName);
			pOverlay.onclick = function(){_this.Close()};

			this.pValuesCont.style.left = left + 'px';
			this.pValuesCont.style.top = top + 'px';

			this.bOpened = true;

			setTimeout(function()
			{
				BX.bind(document, 'mousedown', BX.proxy(_this.CheckClose, _this));
			},100);
		},

		SetMode: function(action)
		{
			this.currentAction = action;
			var cnActiv = 'bxhtmled-color-link-active';

			if (action == this.actionColor)
			{
				BX.addClass(this.pTextColorLink, cnActiv);
				BX.removeClass(this.pBgColorLink, cnActiv);
			}
			else
			{
				BX.addClass(this.pBgColorLink, cnActiv);
				BX.removeClass(this.pTextColorLink, cnActiv);
			}
		},

		SelectColor: function(color, action)
		{
			if (!action)
			{
				action = this.currentAction;
			}

			if (action == this.actionColor)
			{
				this.pContLetter.style.color = color || '#000';
				this.pContStrip.style.backgroundColor = color || '#000';
			}
			else
			{
				this.pContLetter.style.backgroundColor = color || 'transparent';
			}
		}
	};
	// Buttons and controls of editor

	// Search and replace
	function SearchButton(editor, wrap)
	{
		// Call parrent constructor
		SearchButton.superclass.constructor.apply(this, arguments);
		this.id = 'search';
		this.title = BX.message('ButtonSearch');
		this.className += ' bxhtmled-button-search';
		this.Create();

		this.bInited = false;

		if (wrap)
			wrap.appendChild(this.GetCont());
	}
	BX.extend(SearchButton, Button);
	SearchButton.prototype.OnClick = function()
	{
		if (!this.bInited)
		{
			var _this = this;
			this.pSearchCont = BX('bx-html-editor-search-cnt-' + this.editor.id);
			this.pSearchWrap = this.pSearchCont.appendChild(BX.create('DIV', {props: {className: 'bxhtmled-search-cnt-search'}}));
			this.pReplaceWrap = this.pSearchCont.appendChild(BX.create('DIV', {props: {className: 'bxhtmled-search-cnt-replace'}}));
			this.pSearchInput = this.pSearchWrap.appendChild(BX.create('INPUT', {props: {className: 'bxhtmled-top-search-inp', type: 'text'}}));
			//this.pSearchBut = this.pSearchWrap.appendChild(BX.create('INPUT', {props: {type: 'button', value: 'Search'}}));
			this.pShowReplace = this.pSearchWrap.appendChild(BX.create('INPUT', {props: {type: 'checkbox', value: 'Y'}}));
			this.pReplaceInput = this.pReplaceWrap.appendChild(BX.create('INPUT', {props: {type: 'text'}}));

			BX.bind(this.pShowReplace, 'click', function(){_this.ShowReplace(!!this.checked);})

			this.animation = null;
			this.animationStartHeight = 0;
			this.animationEndHeight = 0;

			this.height0 = 0;
			this.height1 = 37;
			this.height2 = 66;

			this.bInited = true;
			this.bReplaceOpened = false;
		}

		if (!this.bOpened)
			this.OpenPanel();
		else
			this.ClosePanel();
	};

	SearchButton.prototype.SetPanelHeight = function(height, opacity)
	{
		this.pSearchCont.style.height = height + 'px';
		this.pSearchCont.style.opacity = opacity / 100;

		this.editor.SetAreaContSize(this.origAreaWidth, this.origAreaHeight - height, {areaContTop: this.editor.toolbar.GetHeight() + height});
	};

	SearchButton.prototype.OpenPanel = function(bShowReplace)
	{
		this.pSearchCont.style.display = 'block';

		if (this.animation)
			this.animation.stop();

		if (bShowReplace)
		{
			this.animationStartHeight = this.height1;
			this.animationEndHeight = this.height2;
		}
		else
		{
			this.origAreaHeight = parseInt(this.editor.dom.areaCont.style.height, 10);
			this.origAreaWidth = parseInt(this.editor.dom.areaCont.style.width, 10);

			this.pShowReplace.checked = false;
			this.pSearchCont.style.opacity = 0;
			this.animationStartHeight = this.height0;
			this.animationEndHeight = this.height1;
		}

		var _this = this;
		this.animation = new BX.easing({
			duration : 300,
			start : {height: this.animationStartHeight, opacity : bShowReplace ? 100 : 0},
			finish : {height: this.animationEndHeight, opacity : 100},
			transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),

			step : function(state)
			{
				_this.SetPanelHeight(state.height, state.opacity);
			},

			complete : BX.proxy(function()
			{
				this.animation = null;
			}, this)
		});

		this.animation.animate();
		this.bOpened = true;
	};

	SearchButton.prototype.ClosePanel = function(bShownReplace)
	{
		if (this.animation)
			this.animation.stop();

		this.pSearchCont.style.opacity = 1;
		if (bShownReplace)
		{
			this.animationStartHeight = this.height2;
			this.animationEndHeight = this.height1;
		}
		else
		{
			this.animationStartHeight = this.bReplaceOpened ? this.height2 : this.height1;
			this.animationEndHeight = this.height0;
		}

		var _this = this;
		this.animation = new BX.easing({
			duration : 200,
			start : {height: this.animationStartHeight, opacity : bShownReplace ? 100 : 0},
			finish : {height: this.animationEndHeight, opacity : 100},
			transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),

			step : function(state)
			{
				_this.SetPanelHeight(state.height, state.opacity);
			},

			complete : BX.proxy(function()
			{
				if (!bShownReplace)
					this.pSearchCont.style.display = 'none';
				this.animation = null;
			}, this)
		});

		this.animation.animate();
		if (!bShownReplace)
			this.bOpened = false;
	};

	SearchButton.prototype.ShowReplace = function(bShow)
	{
		if (bShow)
		{
			this.OpenPanel(true);
			this.bReplaceOpened = true;
		}
		else
		{
			this.ClosePanel(true);
			this.bReplaceOpened = false;
		}
	};

	// Change ViewMode
	function ChangeView(editor, wrap)
	{
		// Call parrent constructor
		ChangeView.superclass.constructor.apply(this, arguments);
		this.id = 'change_view';
		this.title = BX.message('ButtonViewMode');
		this._className = this.className;
		this.activeClassName = 'bxhtmled-top-bar-btn-active bxhtmled-top-bar-dd-active';
		this.topClassName = 'bxhtmled-top-bar-dd';

		this.arValues = [
			{
				id: 'view_wysiwyg',
				title: BX.message('ViewWysiwyg'),
				className: this.className + ' bxhtmled-button-viewmode-wysiwyg',
				action: 'changeView',
				value: 'wysiwyg'
			},
			{
				id: 'view_code',
				title: BX.message('ViewCode'),
				className: this.className + ' bxhtmled-button-viewmode-code',
				action: 'changeView',
				value: 'code'
			},
			{
				id: 'view_split_hor',
				title: BX.message('ViewSplitHor'),
				className: this.className + ' bxhtmled-button-viewmode-split-hor',
				action: 'splitMode',
				value: '0'
			},
			{
				id: 'view_split_ver',
				title: BX.message('ViewSplitVer'),
				className: this.className + ' bxhtmled-button-viewmode-split-ver',
				action: 'splitMode',
				value: '1'
			}
		];

		this.className += ' bxhtmled-top-bar-dd';
		this.disabledForTextarea = false;
		this.Create();

		if (wrap)
			wrap.appendChild(this.GetCont());

		var _this = this;
		BX.addCustomEvent(this.editor, 'OnSetViewAfter', function()
		{
			var currentValueId = 'view_' + _this.editor.currentViewName;
			if (_this.editor.currentViewName == 'split')
			{
				currentValueId += '_' + (_this.editor.GetSplitMode() ? 'ver' : 'hor');
			}
			if (currentValueId !== _this.currentValueId)
			{
				_this.SelectItem(currentValueId);
			}
		});
	}
	BX.extend(ChangeView, DropDown);

	ChangeView.prototype.Open = function()
	{
		this.posOffset.left = this.editor.IsExpanded() ? 40 : -4;
		ChangeView.superclass.Open.apply(this, arguments);
		this.pValuesCont.firstChild.style.left = this.editor.IsExpanded() ? '20px' : '';
	};

	ChangeView.prototype.SelectItem = function(id, val)
	{
		val = ChangeView.superclass.SelectItem.apply(this, [id, val]);
		if (val)
		{
			this.pCont.className = this.topClassName + ' ' + val.className;
		}
		else
		{
			this.pCont.className = this.topClassName + ' ' + this.className;
		}
		this.currentValueId = id;
	};

	function UndoButton(editor, wrap)
	{
		// Call parrent constructor
		BoldButton.superclass.constructor.apply(this, arguments);
		this.id = 'undo';
		this.title = BX.message('Undo');
		this.className += ' bxhtmled-button-undo';
		this.action = 'doUndo';

		this.Create();

		if (wrap)
			wrap.appendChild(this.GetCont());

		var _this = this;
		this.Disable(true);
		this._disabled = true;
		BX.addCustomEvent(this.editor, "OnEnableUndo", function(bFlag)
		{
			_this._disabled = !bFlag;
			_this.Disable(!bFlag);
		});
	}
	BX.extend(UndoButton, Button);
	UndoButton.prototype.Disable = function(bFlag)
	{
		bFlag = bFlag || this._disabled;
		if(bFlag != this.disabled)
		{
			this.disabled = !!bFlag;
			if(bFlag)
				BX.addClass(this.pCont, 'bxhtmled-top-bar-btn-disabled');
			else
				BX.removeClass(this.pCont, 'bxhtmled-top-bar-btn-disabled');
		}
	};


	function RedoButton(editor, wrap)
	{
		// Call parrent constructor
		BoldButton.superclass.constructor.apply(this, arguments);
		this.id = 'redo';
		this.title = BX.message('Redo');
		this.className += ' bxhtmled-button-redo';
		this.action = 'doRedo';

		this.Create();

		if (wrap)
			wrap.appendChild(this.GetCont());

		var _this = this;
		this.Disable(true);
		this._disabled = true;
		BX.addCustomEvent(this.editor, "OnEnableRedo", function(bFlag)
		{
			_this._disabled = !bFlag;
			_this.Disable(!bFlag);
		});
	}
	BX.extend(RedoButton, Button);
	RedoButton.prototype.Disable = function(bFlag)
	{
		bFlag = bFlag || this._disabled;
		if(bFlag != this.disabled)
		{
			this.disabled = !!bFlag;
			if(bFlag)
				BX.addClass(this.pCont, 'bxhtmled-top-bar-btn-disabled');
			else
				BX.removeClass(this.pCont, 'bxhtmled-top-bar-btn-disabled');
		}
	};

	function StyleSelectorList(editor, wrap)
	{
		// Call parrent constructor
		StyleSelectorList.superclass.constructor.apply(this, arguments);
		this.id = 'style_selector';
		this.title = BX.message('StyleSelectorTitle');
		this.className += ' ';
		this.action = 'formatStyle';

		var styleList = this.editor.GetStyleList();
		this.arValues = [
			{
				id: '',
				name: BX.message('StyleNormal'),
				topName: BX.message('StyleSelectorName'),
				title: BX.message('StyleNormal'),
				tagName: false,
				action: 'formatStyle',
				value: '',
				defaultValue: true
			}
		];
		var i, name, cn, style, val;

		for (i in styleList)
		{
			if (styleList.hasOwnProperty(i))
			{
				val = styleList[i].value;
				name = styleList[i].name;
				cn = styleList[i].className || 'bxhtmled-style-' + val.toLowerCase().replace(' ', '-');
				style = styleList[i].arStyle || {};

				this.arValues.push(
					{
						id: val,
						name: name,
						title: name,
						className: cn,
						style: style,
						tagName: styleList[i].tagName || false,
						action: 'formatStyle',
						value: val,
						defaultValue: !!styleList[i].defaultValue
					}
				);
			}
		}

		this.Create();

		if (wrap)
			wrap.appendChild(this.GetCont());
	}
	BX.extend(StyleSelectorList, DropDownList);

	StyleSelectorList.prototype.SetValue = function (active, state)
	{
		if (active)
		{
			var nodeName = state.nodeName.toUpperCase();
			this.SelectItem(nodeName, false, false);
		}
		else
		{
			this.SelectItem('', false, false);
		}
	};

	function FontSelectorList(editor, wrap)
	{
		// Call parrent constructor
		FontSelectorList.superclass.constructor.apply(this, arguments);
		this.id = 'font_selector';
		this.title = BX.message('FontSelectorTitle');
		this.action = 'fontFamily';
		this.zIndex = 3008;
		var fontList = this.editor.GetFontFamilyList();
		this.arValues = [
			{
				id: '',
				name: BX.message('NoFontTitle'),
				topName: BX.message('FontSelectorTitle'),
				title: BX.message('NoFontTitle'),
				className: '',
				style: '',
				action: 'fontFamily',
				value: '',
				defaultValue: true
			}
		];

		var i, name, val, style;
		for (i in fontList)
		{
			if (fontList.hasOwnProperty(i))
			{
				val = fontList[i].value;
				if (typeof val != 'object')
					val = [val];

				name = fontList[i].name;
				style = fontList[i].arStyle || {fontFamily: val.join(',')};
				this.arValues.push(
					{
						id: name,
						name: name,
						title: name,
						className: fontList[i].className || '',
						style: fontList[i].arStyle || {fontFamily: val.join(',')},
						action: 'fontFamily',
						value: val.join(',')
					}
				);
			}
		}

		this.Create();

		if (wrap)
		{
			wrap.appendChild(this.GetCont());
		}
	}
	BX.extend(FontSelectorList, DropDownList);

	FontSelectorList.prototype.SetValue = function(active, state)
	{
		if (active)
		{
			var
				i, j, arFonts, valueId,
				l = this.arValues.length,
				node = state[0],
				value = BX.util.trim(BX.style(node, 'fontFamily'));

			if (value !== '' && BX.type.isString(value))
			{
				arFonts = value.split(',');
				for (i in arFonts)
				{
					valueId = false;
					if (arFonts.hasOwnProperty(i))
					{
						for (j = 0; j < l; j++)
						{
							arFonts[i] = arFonts[i].replace(/'|"/ig, '');
							if (this.arValues[j].value.indexOf(arFonts[i]) !== -1)
							{
								valueId = this.arValues[j].id;
								break;
							}
						}
						if (valueId !== false)
						{
							break;
						}
					}
				}
				this.SelectItem(valueId, false, false);
			}
			else
			{
				this.SelectItem('', false, false);
			}
		}
		else
		{
			this.SelectItem('', false, false);
		}
	};

	function FontSizeButton(editor, wrap)
	{
		// Call parrent constructor
		FontSizeButton.superclass.constructor.apply(this, arguments);
		this.id = 'font_size';
		this.title = BX.message('FontSizeTitle');
		this.className += ' bxhtmled-button-fontsize';
		this.activeClassName = 'bxhtmled-top-bar-btn-active bxhtmled-button-fontsize-active';
		this.disabledClassName = 'bxhtmled-top-bar-btn-disabled bxhtmled-button-fontsize-disabled';
		this.action = 'fontSize';
		this.zIndex = 3007;

		var fontSize = [6,7,8,9,10,11,12,13,14,15,16,18,20,22,24,26,28,36,48,72];
		this.arValues = [{
			id: 'font-size-0',
			className: 'bxhtmled-top-bar-btn bxhtmled-button-remove-fontsize',
			action: this.action,
			value: '<i></i>'
		}];
		var i, val;
		for (i in fontSize)
		{
			if (fontSize.hasOwnProperty(i))
			{
				val = fontSize[i];
				this.arValues.push(
					{
						id: 'font-size-' + val,
						action: this.action,
						value: val
					}
				);
			}
		}

		this.Create();

		if (wrap)
			wrap.appendChild(this.pCont_);

		BX.addCustomEvent(this, "OnPopupClose", BX.proxy(this.OnPopupClose, this));
	}
	BX.extend(FontSizeButton, DropDown);

	FontSizeButton.prototype.Create = function ()
	{
		this.pCont_ = BX.create("SPAN", {props: {className: 'bxhtmled-button-fontsize-wrap', title: this.title}});
		this.pCont = this.pButCont = this.pCont_.appendChild(BX.create("SPAN", {props: {className: this.className}, html: '<i></i>'}));
		this.pListCont = this.pCont_.appendChild(BX.create("SPAN", {props: {className: 'bxhtmled-top-bar-select', title: this.title}, attrs: {unselectable: 'on'}, text: '', style: {display: 'none'}}));

		this.pValuesCont = BX.create("DIV", {props: {className: "bxhtmled-popup bxhtmled-dropdown-cont"}, html: '<div class="bxhtmled-popup-corner"></div>'});

		this.pValuesContWrap = this.pValuesCont.appendChild(BX.create("DIV", {props: {className: "bxhtmled-dropdown-cont bxhtmled-font-size-popup"}}));
		this.valueIndex = {};
		var but, value, _this = this, i, itemClass = 'bxhtmled-dd-list-item';

		for (i = 0; i < this.arValues.length; i++)
		{
			value = this.arValues[i];
			but = this.pValuesContWrap.appendChild(BX.create("SPAN", {props: {className: value.className || itemClass}, html: value.value, style: value.style || {}}));

			but.setAttribute('data-bx-dropdown-value', value.id);
			this.valueIndex[value.id] = i;

			if (value.action)
			{
				but.setAttribute('data-bx-type', 'action');
				but.setAttribute('data-bx-action', value.action);
				if (value.value)
					but.setAttribute('data-bx-value', value.value);
			}

			BX.bind(but, 'mousedown', function(e)
			{
				_this.SelectItem(this.getAttribute('data-bx-dropdown-value'));
				_this.editor.CheckCommand(this);
				_this.Close();
			});
		}

		this.editor.RegisterCheckableAction(this.action, {
			action: this.action,
			control: this
		});

		BX.bind(this.pCont_, 'click', BX.proxy(this.OnClick, this));
		BX.bind(this.pCont, "mousedown", BX.delegate(this.OnMouseDown, this));
	};

	FontSizeButton.prototype.SetValue = function(active, state)
	{
		if (state && state[0])
		{
			var element = state[0];
			var value = element.style.fontSize;
			this.SelectItem(false, {value: parseInt(value, 10), title: value});
		}
		else
		{
			this.SelectItem(false, {value: 0});
		}
	};

	FontSizeButton.prototype.SelectItem = function(valDropdown, val)
	{
		if (!val)
			val = this.arValues[this.valueIndex[valDropdown]];

		if (val.value)
		{
			this.pListCont.innerHTML = val.value;
			this.pListCont.title = this.title + ': ' + (val.title || val.value);
			this.pListCont.style.display = '';
			this.pButCont.style.display = 'none';
		}
		else
		{
			this.pListCont.title = this.title;
			this.pButCont.style.display = '';
			this.pListCont.style.display = 'none';
		}
	};

	FontSizeButton.prototype.GetPopupBindCont = function()
	{
		return this.pCont_;
	};

	FontSizeButton.prototype.Open = function()
	{
		FontSizeButton.superclass.Open.apply(this, arguments);
		BX.addClass(this.pListCont, 'bxhtmled-top-bar-btn-active');
	};

	FontSizeButton.prototype.Close = function()
	{
		FontSizeButton.superclass.Close.apply(this, arguments);
		BX.removeClass(this.pListCont, 'bxhtmled-top-bar-btn-active');
	};

	FontSizeButton.prototype.OnPopupClose = function()
	{
		var more = this.editor.toolbar.controls.More;
		setTimeout(function()
		{
			if (more.bOpened)
			{
				more.CheckOverlay();
			}
		}, 100);
	};

	function BoldButton(editor, wrap)
	{
		// Call parrent constructor
		BoldButton.superclass.constructor.apply(this, arguments);
		this.id = 'bold';
		this.title = BX.message('Bold');
		this.className += ' bxhtmled-button-bold';
		this.action = 'bold';

		this.Create();

		if (wrap)
			wrap.appendChild(this.GetCont());
	}
	BX.extend(BoldButton, Button);

	function ItalicButton(editor, wrap)
	{
		// Call parrent constructor
		ItalicButton.superclass.constructor.apply(this, arguments);
		this.id = 'italic';
		this.title = BX.message('Italic');
		this.className += ' bxhtmled-button-italic';
		this.action = 'italic';

		this.Create();

		if (wrap)
			wrap.appendChild(this.GetCont());
	}
	BX.extend(ItalicButton, Button);

	function UnderlineButton(editor, wrap)
	{
		// Call parrent constructor
		UnderlineButton.superclass.constructor.apply(this, arguments);
		this.id = 'underline';
		this.title = BX.message('Underline');
		this.className += ' bxhtmled-button-underline';
		this.action = 'underline';

		this.Create();

		if (wrap)
			wrap.appendChild(this.GetCont());
	}
	BX.extend(UnderlineButton, Button);

	function StrikeoutButton(editor, wrap)
	{
		// Call parrent constructor
		StrikeoutButton.superclass.constructor.apply(this, arguments);
		this.id = 'strike';
		this.title = BX.message('Strike');
		this.className += ' bxhtmled-button-strike';
		this.action = 'strikeout';

		this.Create();

		if (wrap)
			wrap.appendChild(this.GetCont());
	}
	BX.extend(StrikeoutButton, Button);


	function RemoveFormatButton(editor, wrap)
	{
		// Call parrent constructor
		RemoveFormatButton.superclass.constructor.apply(this, arguments);
		this.id = 'remove_format';
		this.title = BX.message('RemoveFormat');
		this.className += ' bxhtmled-button-remove-format';
		this.action = 'removeFormat';
		this.checkableAction = false;
		this.Create();

		if (wrap)
			wrap.appendChild(this.GetCont());
	}
	BX.extend(RemoveFormatButton, Button);

	function TemplateSelectorList(editor, wrap)
	{
		// Call parrent constructor
		TemplateSelectorList.superclass.constructor.apply(this, arguments);
		this.id = 'template_selector';
		this.title = BX.message('TemplateSelectorTitle');
		this.className += ' ';
		this.width = 85;
		this.zIndex = 3007;
		this.arValues = [];
		var
			templateId = this.editor.GetTemplateId(),
			templates = this.editor.config.templates,
			i, template;

		for (i in templates)
		{
			if (templates.hasOwnProperty(i))
			{
				template = templates[i];
				this.arValues.push(
					{
						id: template.value,
						name: template.name,
						title: template.name,
						className: 'bxhtmled-button-viewmode-wysiwyg',
						action: 'changeTemplate',
						value: template.value,
						defaultValue: template.value == templateId
					}
				);
			}
		}

		this.Create();

		if (wrap)
			wrap.appendChild(this.GetCont());

		this.SelectItem(templateId);
	}
	BX.extend(TemplateSelectorList, DropDownList);

	function OrderedListButton(editor, wrap)
	{
		// Call parrent constructor
		OrderedListButton.superclass.constructor.apply(this, arguments);
		this.id = 'ordered-list';
		this.title = BX.message('OrderedList');
		this.className += ' bxhtmled-button-ordered-list';
		this.action = 'insertOrderedList';
		this.Create();
		if (wrap)
		{
			wrap.appendChild(this.GetCont());
		}
	}
	BX.extend(OrderedListButton, Button);

	function UnorderedListButton(editor, wrap)
	{
		// Call parrent constructor
		UnorderedListButton.superclass.constructor.apply(this, arguments);
		this.id = 'unordered-list';
		this.title = BX.message('UnorderedList');
		this.className += ' bxhtmled-button-unordered-list';
		this.action = 'insertUnorderedList';
		this.Create();
		if (wrap)
		{
			wrap.appendChild(this.GetCont());
		}
	}
	BX.extend(UnorderedListButton, Button);

	function IndentButton(editor, wrap)
	{
		// Call parrent constructor
		IndentButton.superclass.constructor.apply(this, arguments);
		this.id = 'indent';
		this.title = BX.message('Indent');
		this.className += ' bxhtmled-button-indent';
		this.action = 'indent';
		this.checkableAction = false;
		this.Create();
		if (wrap)
		{
			wrap.appendChild(this.GetCont());
		}
	}
	BX.extend(IndentButton, Button);

	function OutdentButton(editor, wrap)
	{
		// Call parrent constructor
		OutdentButton.superclass.constructor.apply(this, arguments);
		this.id = 'outdent';
		this.title = BX.message('Outdent');
		this.className += ' bxhtmled-button-outdent';
		this.action = 'outdent';
		this.checkableAction = false;
		this.Create();
		if (wrap)
		{
			wrap.appendChild(this.GetCont());
		}
	}
	BX.extend(OutdentButton, Button);

	function AlignList(editor, wrap)
	{
		// Call parrent constructor
		AlignList.superclass.constructor.apply(this, arguments);
		this.id = 'align-list';
		this.title = BX.message('BXEdTextAlign');
		this.posOffset.left = 0;
		this.action = 'align';
		var cn = this.className;
		this.className += ' bxhtmled-button-align-left';

		this.arValues = [
			{
				id: 'align_left',
				title: BX.message('AlignLeft'),
				className: cn + ' bxhtmled-button-align-left',
				action: 'align',
				value: 'left'
			},
			{
				id: 'align_center',
				title: BX.message('AlignCenter'),
				className: cn + ' bxhtmled-button-align-center',
				action: 'align',
				value: 'center'
			},
			{
				id: 'align_right',
				title: BX.message('AlignRight'),
				className: cn + ' bxhtmled-button-align-right',
				action: 'align',
				value: 'right'
			},
			{
				id: 'align_justify',
				title: BX.message('AlignJustify'),
				className: cn + ' bxhtmled-button-align-justify',
				action: 'align',
				value: 'justify'
			}
		];

		this.Create();

		if (wrap)
			wrap.appendChild(this.GetCont());
	}
	BX.extend(AlignList, DropDown);
	AlignList.prototype.SetValue = function(active, state)
	{
		if (state && state.value)
		{
			this.SelectItem('align_' + state.value);
		}
		else
		{
			this.SelectItem(null);
		}
	};

	function InsertLinkButton(editor, wrap)
	{
		// Call parrent constructor
		InsertLinkButton.superclass.constructor.apply(this, arguments);
		this.id = 'insert-link';
		this.title = BX.message('InsertLink');
		this.className += ' bxhtmled-button-link';
		this.posOffset = {top: 6, left: 0};

		this.arValues = [
			{
				id: 'edit_link',
				title: BX.message('EditLink'),
				className: this.className + ' bxhtmled-button-link'
			},
			{
				id: 'remove_link',
				title: BX.message('RemoveLink'),
				className: this.className + ' bxhtmled-button-remove-link',
				action: 'removeLink'
			}
		];

		this.Create();
		if (wrap)
		{
			wrap.appendChild(this.GetCont());
		}
	}
	BX.extend(InsertLinkButton, DropDown);

	InsertLinkButton.prototype.OnClick = function()
	{
		if(!this.disabled)
		{
			var
				i, link, lastLink, linksCount = 0,
				nodes = this.editor.action.CheckState('formatInline', {}, "a");

			if (nodes)
			{
				// Selection contains links
				for (i = 0; i < nodes.length; i++)
				{
					link = nodes[i];
					if (link)
					{
						lastLink = link;
						linksCount++;
					}

					if (linksCount > 1)
					{
						break;
					}
				}
			}

			// Link exists: show drop down with two buttons - edit or remove
			if (linksCount === 1 && lastLink)
			{
				if (this.bOpened)
				{
					this.Close();
				}
				else
				{
					this.Open();
				}
			}
			else // No link: show dialog to add new one
			{
				this.editor.GetDialog('Link').Show(nodes, this.savedRange);
			}
		}
	};

	InsertLinkButton.prototype.SelectItem = function(id)
	{
		if (id == 'edit_link')
		{
			this.editor.GetDialog('Link').Show(false, this.savedRange);
		}
	};

	function InsertImageButton(editor, wrap)
	{
		// Call parrent constructor
		InsertImageButton.superclass.constructor.apply(this, arguments);
		this.id = 'image';
		this.title = BX.message('InsertImage');
		this.className += ' bxhtmled-button-image';
		this.Create();
		if (wrap)
		{
			wrap.appendChild(this.GetCont());
		}
	}
	BX.extend(InsertImageButton, Button);

	InsertImageButton.prototype.OnClick = function()
	{
		this.editor.GetDialog('Image').Show(false, this.savedRange);
	};

	function InsertVideoButton(editor, wrap)
	{
		// Call parrent constructor
		InsertVideoButton.superclass.constructor.apply(this, arguments);
		this.id = 'video';
		this.title = BX.message('BXEdInsertVideo');
		this.className += ' bxhtmled-button-video';
		this.Create();
		if (wrap)
		{
			wrap.appendChild(this.GetCont());
		}
	}
	BX.extend(InsertVideoButton, Button);

	InsertVideoButton.prototype.OnClick = function()
	{
		this.editor.GetDialog('Video').Show(false, this.savedRange);
	};

	function InsertAnchorButton(editor, wrap)
	{
		// Call parrent constructor
		InsertAnchorButton.superclass.constructor.apply(this, arguments);
		this.id = 'insert-anchor';
		this.title = BX.message('BXEdAnchor');
		this.className += ' bxhtmled-button-anchor';
		this.action = 'insertAnchor';
		this.Create();

		if (wrap)
		{
			wrap.appendChild(this.GetCont());
		}
	}
	BX.extend(InsertAnchorButton, Button);

	InsertAnchorButton.prototype.OnClick = function(e)
	{
		if (!this.pPopup)
		{
			this.pPopup = new BX.PopupWindow(this.id + '-popup', this.GetCont(),
				{
					zIndex: 3005,
					lightShadow : true,
					offsetTop: 4,
					overlay: {opacity: 1},
					offsetLeft: -128,
					autoHide: true,
					closeByEsc: true,
					className: 'bxhtmled-popup',
					content : ''
				});

			this.pPopupCont = BX(this.id + '-popup');
			this.pPopupCont.className = 'bxhtmled-popup';
			this.pPopupCont.innerHTML = '<div class="bxhtmled-popup-corner"></div>';
			this.pPopupContWrap = this.pPopupCont.appendChild(BX.create("DIV"));
			this.pPopupContInput = this.pPopupContWrap.appendChild(BX.create("INPUT", {props: {type: 'text', placeholder: BX.message('BXEdAnchorName') + '...', title: BX.message('BXEdAnchorInsertTitle')}, style: {width: '150px'}}));
			this.pPopupContBut = this.pPopupContWrap.appendChild(BX.create("INPUT", {props: {type: 'button', value: BX.message('BXEdInsert')}, style: {marginLeft: '6px'}}));

			BX.bind(this.pPopupContInput, 'keyup', BX.proxy(this.OnKeyUp, this));
			BX.bind(this.pPopupContBut, 'click', BX.proxy(this.Save, this));
		}

		this.pPopupContInput.value = '';
		this.pPopup.show();
		BX.focus(this.pPopupContInput);
	};

	InsertAnchorButton.prototype.Save = function()
	{
		var name = BX.util.trim(this.pPopupContInput.value);
		if (name !== '')
		{
			name = name.replace(/[^ a-z0-9_\-]/gi, "");
			if (this.savedRange)
			{
				this.editor.selection.SetBookmark(this.savedRange);
			}

			var
				node = this.editor.phpParser.GetSurrogateNode("anchor",
					BX.message('BXEdAnchor') + ": #" + name,
					null,
					{
						html: '',
						name: name
					}
				);

			this.editor.selection.InsertNode(node);
			var sur = this.editor.util.CheckSurrogateNode(node.parentNode);
			if (sur)
			{
				this.editor.util.InsertAfter(node, sur);
			}
			this.editor.selection.SetInvisibleTextAfterNode(node);
			this.editor.synchro.StartSync(100);

			if (this.editor.toolbar.controls.More)
			{
				this.editor.toolbar.controls.More.Close();
			}
		}
		this.pPopup.close();
	};
	InsertAnchorButton.prototype.OnKeyUp = function(e)
	{
		if (e.keyCode === this.editor.KEY_CODES['enter'])
		{
			this.Save();
		}
	};


	function InsertTableButton(editor, wrap)
	{
		// Call parrent constructor
		InsertTableButton.superclass.constructor.apply(this, arguments);
		this.id = 'insert-table';
		this.title = BX.message('BXEdTable');
		this.className += ' bxhtmled-button-table';
		this.itemClassName = 'bxhtmled-dd-list-item';
		this.action = 'insertTable';

		this.PATTERN_ROWS = 10;
		this.PATTERN_COLS = 10;
		this.zIndex = 3007;
		this.posOffset = {top: 6, left: 0};
		this.Create();
		if (wrap)
		{
			wrap.appendChild(this.GetCont());
		}
		BX.addCustomEvent(this, "OnPopupClose", BX.proxy(this.OnPopupClose, this));
	}
	BX.extend(InsertTableButton, DropDown);

	InsertTableButton.prototype.Create = function()
	{
		this.pCont = BX.create("SPAN", {props: {className: this.className, title: this.title}, html: '<i></i>'});
		this.pValuesCont = BX.create("DIV", {props: {className: "bxhtmled-popup bxhtmled-dropdown-cont"}, html: '<div class="bxhtmled-popup-corner"></div>'});
		this.pValuesCont.style.zIndex = this.zIndex;
		this.valueIndex = {};
		this.pPatternWrap = this.pValuesCont.appendChild(BX.create("DIV")); //
		this.pValuesContWrap = this.pValuesCont.appendChild(BX.create("DIV"));
		var
			_this = this,
			i, row, cell,
			lastNode, overPattern = false,
			l = this.PATTERN_ROWS * this.PATTERN_COLS,
			but;

		// Selectable table
		this.pPatternTbl = this.pPatternWrap.appendChild(BX.create("TABLE", {props: {className: "bxhtmled-pattern-tbl"}}));

		function markPatternTable(row, cell)
		{
			var r, c, pCell;
			for(r = 0; r < _this.PATTERN_ROWS; r++)
			{
				for(c = 0; c < _this.PATTERN_COLS; c++)
				{
					pCell = _this.pPatternTbl.rows[r].cells[c];
					pCell.className = (r <= row && c <= cell) ? 'bxhtmled-td-selected' : '';
				}
			}
		}

		BX.bind(this.pPatternTbl, "mousemove", function(e)
		{
			var node = e.target || e.srcElement;
			if (lastNode !== node)
			{
				lastNode = node;
				if (node.nodeName == "TD")
				{
					overPattern = true;
					markPatternTable(node.parentNode.rowIndex, node.cellIndex);
				}
				else if (node.nodeName == "TABLE")
				{
					overPattern = false;
					markPatternTable(-1, -1);
				}
			}
		});

		BX.bind(this.pPatternWrap, "mouseout", function(e)
		{
			overPattern = false;
			setTimeout(function()
			{
				if (!overPattern)
				{
					markPatternTable(-1, -1);
				}
			}, 300);
		});

		BX.bind(this.pPatternTbl, "click", function(e)
		{
			var node = e.target || e.srcElement;
			if (node.nodeName == "TD")
			{
				if (_this.editor.action.IsSupported(_this.action))
				{
					if (_this.savedRange)
					{
						_this.editor.selection.SetBookmark(_this.savedRange);
					}

					_this.editor.action.Exec(
						_this.action,
						{
							rows: node.parentNode.rowIndex + 1,
							cols: node.cellIndex + 1,
							border: 1,
							cellPadding: 1,
							cellSpacing: 1
						});
				}

				if (_this.editor.toolbar.controls.More)
				{
					_this.editor.toolbar.controls.More.Close();
				}
				_this.Close();
			}
		});

		for(i = 0; i < l; i++)
		{
			if (i % this.PATTERN_COLS == 0) // new row
			{
				row = this.pPatternTbl.insertRow(-1);
			}

			cell = row.insertCell(-1);
			cell.innerHTML = '&nbsp;';
			cell.title = (cell.cellIndex + 1) + 'x' + (row.rowIndex + 1);
		}

		but = this.pValuesContWrap.appendChild(BX.create("SPAN", {props: {title: BX.message('BXEdInsertTableTitle'), className: this.itemClassName}, html: BX.message('BXEdInsertTable')}));

		BX.bind(but, 'mousedown', function(e)
		{
			_this.editor.GetDialog('Table').Show(false, _this.savedRange);
			if (_this.editor.toolbar.controls.More)
			{
				_this.editor.toolbar.controls.More.Close();
			}
			_this.Close();
		});

		BX.bind(this.pCont, 'click', BX.proxy(this.OnClick, this));
		BX.bind(this.pCont, "mousedown", BX.delegate(this.OnMouseDown, this));
	};

	InsertTableButton.prototype.OnPopupClose = function()
	{
		var more = this.editor.toolbar.controls.More;
		setTimeout(function()
		{
			if (more.bOpened)
			{
				more.CheckOverlay();
			}
		}, 100);
	};

	function InsertCharButton(editor, wrap)
	{
		// Call parrent constructor
		InsertCharButton.superclass.constructor.apply(this, arguments);
		this.id = 'specialchar';
		this.title = BX.message('BXEdSpecialchar');
		this.className += ' bxhtmled-button-specialchar';
		this.itemClassName = 'bxhtmled-dd-list-item';
		this.CELLS_COUNT = 10;

		this.posOffset = {top: 6, left: 0};
		this.zIndex = 3007;
		this.Create();
		if (wrap)
		{
			wrap.appendChild(this.GetCont());
		}

		BX.addCustomEvent(this, "OnPopupClose", BX.proxy(this.OnPopupClose, this));
	}
	BX.extend(InsertCharButton, DropDown);

	InsertCharButton.prototype.Create = function()
	{
		this.pCont = BX.create("SPAN", {props: {className: this.className, title: this.title}, html: '<i></i>'});
		this.pValuesCont = BX.create("DIV", {props: {className: "bxhtmled-popup bxhtmled-dropdown-cont"}, html: '<div class="bxhtmled-popup-corner"></div>'});
		this.pValuesCont.style.zIndex = this.zIndex;
		this.valueIndex = {};
		this.pPatternWrap = this.pValuesCont.appendChild(BX.create("DIV")); //
		this.pValuesContWrap = this.pValuesCont.appendChild(BX.create("DIV"));
		var
			lastUsedChars = this.editor.GetLastSpecialchars(),
			_this = this,
			i, row, cell,
			l = lastUsedChars.length,
			but;

		this.pLastChars = this.pPatternWrap.appendChild(BX.create("TABLE", {props: {className: "bxhtmled-last-chars"}}));

		for(i = 0; i < l; i++)
		{
			if (i % this.CELLS_COUNT == 0) // new row
			{
				row = this.pLastChars.insertRow(-1);
			}
			cell = row.insertCell(-1);
		}

		BX.bind(this.pLastChars, 'click', function(e)
		{
			var
				ent,
				target = e.target || e.srcElement;
			if (target.nodeType == 3)
			{
				target = target.parentNode;
			}
			if (target && target.getAttribute && target.getAttribute('data-bx-specialchar') &&
				_this.editor.action.IsSupported('insertHTML'))
			{
				if (_this.savedRange)
				{
					_this.editor.selection.SetBookmark(_this.savedRange);
				}
				ent = target.getAttribute('data-bx-specialchar');
				_this.editor.On('OnSpecialcharInserted', [ent]);
				_this.editor.action.Exec('insertHTML', ent);
			}
			if (_this.editor.toolbar.controls.More)
			{
				_this.editor.toolbar.controls.More.Close();
			}
			_this.Close();
		});

		but = this.pValuesContWrap.appendChild(BX.create("SPAN", {props: {title: BX.message('BXEdSpecialcharMoreTitle'), className: this.itemClassName}, html: BX.message('BXEdSpecialcharMore')}));

		BX.bind(but, 'mousedown', function()
		{
			_this.editor.GetDialog('Specialchar').Show(_this.savedRange);
			if (_this.editor.toolbar.controls.More)
			{
				_this.editor.toolbar.controls.More.Close();
			}
			_this.Close();
		});

		BX.bind(this.pCont, 'click', BX.proxy(this.OnClick, this));
		BX.bind(this.pCont, "mousedown", BX.delegate(this.OnMouseDown, this));
	};

	InsertCharButton.prototype.OnClick = function()
	{
		var
			lastUsedChars = this.editor.GetLastSpecialchars(),
			i, r = -1, c = -1, cell,
			l = lastUsedChars.length;

		for(i = 0; i < l; i++)
		{
			if (i % this.CELLS_COUNT == 0) // new row
			{
				r++;
				c = -1;
			}
			c++;

			cell = this.pLastChars.rows[r].cells[c];
			if (cell)
			{
				cell.innerHTML = lastUsedChars[i];
				cell.setAttribute('data-bx-specialchar', lastUsedChars[i]);
				cell.title = BX.message('BXEdSpecialchar') + ': ' + lastUsedChars[i].substr(1, lastUsedChars[i].length - 2);
			}
		}

		InsertCharButton.superclass.OnClick.apply(this, arguments);
	};

	InsertCharButton.prototype.OnPopupClose = function()
	{
		var more = this.editor.toolbar.controls.More;
		setTimeout(function()
		{
			if (more.bOpened)
			{
				more.CheckOverlay();
			}
		}, 100);
	};

	function PrintBreakButton(editor, wrap)
	{
		// Call parrent constructor
		PrintBreakButton.superclass.constructor.apply(this, arguments);
		this.id = 'print_break';
		this.title = BX.message('BXEdPrintBreak');
		this.className += ' bxhtmled-button-print-break';
		this.Create();
		if (wrap)
		{
			wrap.appendChild(this.GetCont());
		}
	}
	BX.extend(PrintBreakButton, Button);

	PrintBreakButton.prototype.OnClick = function()
	{
		if (this.editor.action.IsSupported('insertHTML'))
		{
			if (this.savedRange)
			{
				this.editor.selection.SetBookmark(this.savedRange);
			}

			var
				doc = this.editor.GetIframeDoc(),
				id = this.editor.SetBxTag(false, {tag: 'printbreak', params: {innerHTML: '<span style="display: none">&nbsp;</span>'}, name: BX.message('BXEdPrintBreakName'), title: BX.message('BXEdPrintBreakTitle')}),
				node = BX.create('IMG', {props: {src: this.editor.EMPTY_IMAGE_SRC, id: id,className: "bxhtmled-printbreak", title: BX.message('BXEdPrintBreakTitle')}}, doc);

			this.editor.selection.InsertNode(node);
			var sur = this.editor.util.CheckSurrogateNode(node.parentNode);
			if (sur)
			{
				this.editor.util.InsertAfter(node, sur);
			}
			this.editor.selection.SetAfter(node);
			this.editor.Focus();
			this.editor.synchro.StartSync(100);
		}

		if (this.editor.toolbar.controls.More)
		{
			this.editor.toolbar.controls.More.Close();
		}
	};

	function PageBreakButton(editor, wrap)
	{
		// Call parrent constructor
		PageBreakButton.superclass.constructor.apply(this, arguments);
		this.id = 'page_break';
		this.title = BX.message('BXEdPageBreak');
		this.className += ' bxhtmled-button-page-break';
		this.Create();
		if (wrap)
		{
			wrap.appendChild(this.GetCont());
		}
	}
	BX.extend(PageBreakButton, Button);

	function InsertHrButton(editor, wrap)
	{
		// Call parrent constructor
		InsertHrButton.superclass.constructor.apply(this, arguments);
		this.id = 'hr';
		this.title = BX.message('BXEdInsertHr');
		this.className += ' bxhtmled-button-hr';
		this.Create();
		if (wrap)
		{
			wrap.appendChild(this.GetCont());
		}
	}
	BX.extend(InsertHrButton, Button);


	function SettingsButton(editor, wrap)
	{
		// Call parrent constructor
		SettingsButton.superclass.constructor.apply(this, arguments);
		this.id = 'settings';
		this.title = BX.message('BXEdSettings');
		this.className += ' bxhtmled-button-settings';
		this.Create();
		if (wrap)
		{
			wrap.appendChild(this.GetCont());
		}
	}
	BX.extend(SettingsButton, Button);
	SettingsButton.prototype.OnClick = function()
	{
		this.editor.GetDialog('Settings').Show();
	};

	function FullscreenButton(editor, wrap)
	{
		// Call parrent constructor
		FullscreenButton.superclass.constructor.apply(this, arguments);
		this.id = 'fullscreen';
		this.title = BX.message('BXEdFullscreen');
		this.className += ' bxhtmled-button-fullscreen';
		this.action = 'fullscreen';
		this.disabledForTextarea = false;
		this.Create();
		if (wrap)
		{
			wrap.appendChild(this.GetCont());
		}
	}
	BX.extend(FullscreenButton, Button);

	FullscreenButton.prototype.Check = function(bFlag)
	{
		this.GetCont().title = bFlag ? BX.message('BXEdFullscreenBack') : BX.message('BXEdFullscreen');
		// Call parrent Check()
		FullscreenButton.superclass.Check.apply(this, arguments);
	};

	function MoreButton(editor, wrap)
	{
		// Call parrent constructor
		MoreButton.superclass.constructor.apply(this, arguments);
		this.id = 'more';
		this.title = BX.message('BXEdMore');
		this.className += ' bxhtmled-button-more';
		this.Create();
		this.posOffset.left = -8;
		BX.addClass(this.pValuesContWrap, 'bxhtmled-more-cnt');

		if (wrap)
		{
			wrap.appendChild(this.GetCont());
		}

		var _this = this;
		BX.bind(this.pValuesContWrap, "click", function(e)
		{
			var
				target = e.target || e.srcElement,
				bxType = (target && target.getAttribute) ? target.getAttribute('data-bx-type') : false;
			_this.editor.CheckCommand(target);
		});
	}
	BX.extend(MoreButton, DropDown);

	MoreButton.prototype.Open = function()
	{
		this.pValuesCont.style.width = '';
		MoreButton.superclass.Open.apply(this, arguments);

		var
			bindCont = this.GetPopupBindCont(),
			pos = BX.pos(bindCont),
			left = Math.round(pos.left - this.pValuesCont.offsetWidth / 2 + bindCont.offsetWidth / 2 + this.posOffset.left);

		this.pValuesCont.style.width = this.pValuesCont.offsetWidth + 'px';
		this.pValuesCont.style.left = left + 'px';
	};

	MoreButton.prototype.GetPopupCont = function()
	{
		return this.pValuesContWrap;
	};

	MoreButton.prototype.CheckClose = function(e)
	{
		if (!this.bOpened)
		{
			return BX.unbind(document, 'mousedown', BX.proxy(this.CheckClose, this));
		}

		var pEl;
		if (e.target)
			pEl = e.target;
		else if (e.srcElement)
			pEl = e.srcElement;
		if (pEl.nodeType == 3)
			pEl = pEl.parentNode;

		if (pEl.style.zIndex > this.zIndex)
		{
			this.CheckOverlay();
		}
		else if (!BX.findParent(pEl, {className: 'bxhtmled-popup'}))
		{
			this.Close();
		}
	};

	MoreButton.prototype.CheckOverlay = function()
	{
		var _this = this;
		this.editor.overlay.Show({zIndex: this.zIndex - 1}).onclick = function(){_this.Close()};
	};


	/* ~~~~ Editor dialogs ~~~~*/
	// Image
	function ImageDialog(editor, params)
	{
		params = {
			id: 'bx_image',
			width: 700,
			resizable: false,
			className: 'bxhtmled-img-dialog'
		};

		this.id = 'image';
		this.action = 'insertImage';
		this.loremIpsum = BX.message('BXEdLoremIpsum') + "\n" + BX.message('BXEdLoremIpsum');

		// Call parrent constructor
		ImageDialog.superclass.constructor.apply(this, [editor, params]);

		this.SetContent(this.Build());
		BX.addCustomEvent(this, "OnDialogSave", BX.proxy(this.Save, this));
	}
	BX.extend(ImageDialog, Dialog);

	ImageDialog.prototype.Build = function()
	{
		function addRow(tbl, c1Par, bAdditional)
		{
			var r, c1, c2;

			r = tbl.insertRow(-1);
			if (bAdditional)
			{
				r.className = 'bxhtmled-add-row';
			}

			c1 = r.insertCell(-1);
			c1.className = 'bxhtmled-left-c';

			if (c1Par && c1Par.label)
			{
				c1.appendChild(BX.create('LABEL', {props: {className: c1Par.required ? 'bxhtmled-req' : ''},text: c1Par.label})).setAttribute('for', c1Par.id);
			}

			c2 = r.insertCell(-1);
			c2.className = 'bxhtmled-right-c';
			return {row: r, leftCell: c1, rightCell: c2};
		}

		var
			_this = this,
			r, c;

		this.pCont = BX.create('DIV', {props: {className: 'bxhtmled-img-dialog-cnt'}});
		var pTableWrap = BX.create('TABLE', {props: {className: 'bxhtmled-dialog-tbl bxhtmled-img-dialog-tbl'}});

		// Preview row
		r = pTableWrap.insertRow(-1);
		r.className = 'bxhtmled-img-preview-row';
		c = BX.adjust(r.insertCell(-1), {props: {colSpan: 2, className: 'bxhtmled-img-prev-c'}});
		this.pPreview = c.appendChild(BX.create('DIV', {props: {className: 'bxhtmled-img-preview', id: this.id + '-preview'}, html: this.loremIpsum}));
		this.pPreviewRow = r;

		// Src
		r = addRow(pTableWrap, {label: BX.message('BXEdImgSrc') + ':', id: this.id + '-src', required: true});
		this.pSrc = r.rightCell.appendChild(BX.create('INPUT', {props: {type: 'text', id: this.id + '-src', className: 'bxhtmled-80-input'}}));
		this.pSrc.placeholder = BX.message('BXEdImgSrcRequired');
		BX.bind(this.pSrc, 'blur', BX.proxy(this.SrcOnChange, this));
		BX.bind(this.pSrc, 'change', BX.proxy(this.SrcOnChange, this));
		BX.bind(this.pSrc, 'keyup', BX.proxy(this.SrcOnChange, this));

		var butMl = BX('bx-open-file-medialib-but-' + this.editor.id);
		if (butMl)
		{
			r.rightCell.appendChild(butMl);
		}
		else
		{
			var butFd = BX('bx_open_file_medialib_button_' + this.editor.id);
			if (butFd)
			{
				r.rightCell.appendChild(butFd);
				BX.bind(butFd, 'click', window['BxOpenFileBrowserImgFile' + this.editor.id]);
			}
		}

		// Size
		r = addRow(pTableWrap, {label: BX.message('BXEdImgSize') + ':', id: this.id + '-size'});
		r.rightCell.appendChild(this.GetSizeControl());
		BX.addClass(r.leftCell,'bxhtmled-left-c-top');
		r.leftCell.style.paddingTop = '12px';
		this.pSizeRow = r.row;

		// Title
		r = addRow(pTableWrap, {label: BX.message('BXEdImgTitle') + ':', id: this.id + '-title'});
		this.pTitle = r.rightCell.appendChild(BX.create('INPUT', {props: {type: 'text', id: this.id + '-title', className: 'bxhtmled-90-input'}}));

		// *** Additional params ***
		r = pTableWrap.insertRow(-1);
		var addTitleCell = r.insertCell(-1);
		BX.adjust(addTitleCell, {props: {className: 'bxhtmled-title-cell bxhtmled-title-cell-foldable', colSpan: 2}, text: BX.message('BXEdLinkAdditionalTitle')});
		addTitleCell.onclick = function()
		{
			_this.ShowRows(['align', 'style', 'alt', 'link'], true, !_this.bAdditional);
			_this.bAdditional = !_this.bAdditional;
		};

		// Align
		r = addRow(pTableWrap, {label: BX.message('BXEdImgAlign') + ':', id: this.id + '-align'});
		this.pAlign = r.rightCell.appendChild(BX.create('SELECT', {props: {id: this.id + '-align'}}));
		this.pAlign.options.add(new Option(BX.message('BXEdImgAlignNone'), '', true, true));
		this.pAlign.options.add(new Option(BX.message('BXEdImgAlignTop'), 'top', true, true));
		this.pAlign.options.add(new Option(BX.message('BXEdImgAlignLeft'), 'left', true, true));
		this.pAlign.options.add(new Option(BX.message('BXEdImgAlignRight'), 'right', true, true));
		this.pAlign.options.add(new Option(BX.message('BXEdImgAlignBottom'), 'bottom', true, true));
		this.pAlign.options.add(new Option(BX.message('BXEdImgAlignMiddle'), 'middle', true, true));
		BX.bind(this.pAlign, 'change', BX.delegate(this.ShowPreview, this));
		this.pAlignRow = r.row;

		// Alt
		r = addRow(pTableWrap, {label: BX.message('BXEdImgAlt') + ':', id: this.id + '-alt'});
		this.pAlt = r.rightCell.appendChild(BX.create('INPUT', {props: {type: 'text', id: this.id + '-alt', className: 'bxhtmled-90-input'}}));
		this.pAltRow = r.row;

		// Style
		r = addRow(pTableWrap, {label: BX.message('BXEdCssClass') + ':', id: this.id + '-style'}, true);
		this.pClass = r.rightCell.appendChild(BX.create('INPUT', {props: {type: 'text', id: this.id + '-style'}}));
		this.pStyleRow = r.row;

		// Link on image
		r = addRow(pTableWrap, {label: BX.message('BXEdImgLinkOnImage') + ':', id: this.id + '-link'});
		this.pLink = r.rightCell.appendChild(BX.create('INPUT', {props: {type: 'text', id: this.id + '-link', className: 'bxhtmled-80-input'}}));
		this.pEditLinkBut = r.rightCell.appendChild(BX.create('SPAN', {props: {className: 'bxhtmled-top-bar-btn bxhtmled-button-link', title: BX.message('EditLink')}, html: '<i></i>'}));
		BX.bind(this.pEditLinkBut, 'click', function()
		{
			if (BX.util.trim(_this.pSrc.value) == '')
			{
				BX.focus(_this.pSrc);
			}
			else
			{
				var parLinkHref = _this.pLink.value;
				_this.pLink.value = 'bx-temp-link-href';
				_this.Save();
				_this.oDialog.Close();

				var
					i,
					link,
					links = _this.editor.GetIframeDoc().getElementsByTagName('A');
				for (i = 0; i < links.length; i++)
				{
					var href = links[i].getAttribute('href');
					if (href == 'bx-temp-link-href')
					{
						link = links[i];
						link.setAttribute('href', parLinkHref);

						_this.editor.selection.SelectNode(link);
						_this.editor.GetDialog('Link').Show([link]);
						break;
					}
				}
			}
		});
		this.pCont.appendChild(pTableWrap);
		this.pLinkRow = r.row;

		window['OnFileDialogImgSelect' + this.editor.id] = function(filename, path, site)
		{
			var url;
			if (typeof filename == 'object') // Using medialibrary
			{
				url = filename.src;
				if (_this.pTitle.value == '')
					_this.pTitle.value = filename.description || filename.name;
				if (_this.pAlt.value == '')
					_this.pAlt.value = filename.description || filename.name;
			}
			else // Using file dialog
			{
				url = (path == '/' ? '' : path) + '/' + filename;
			}

			_this.pSrc.value = url;
			BX.focus(_this.pSrc);
			_this.pSrc.select();
			_this.SrcOnChange();
		};

		this.rows = {
			preview : {
				cont: this.pPreviewRow,
				height: 200
			},
			size: {
				cont: this.pSizeRow,
				height: 68
			},
			align: {
				cont: this.pAlignRow,
				height: 36
			},
			style: {
				cont: this.pStyleRow,
				height: 36
			},
			alt: {
				cont: this.pAltRow,
				height: 36
			},
			link: {
				cont: this.pLinkRow,
				height: 36
			}
		};

		return this.pCont;
	};

	ImageDialog.prototype.GetSizeControl = function()
	{
		var
			lastWidth,
			lastHeight,
			_this = this,
			setPercTimeout,
			i,
			percVals = [100, 90, 80, 70, 60, 50, 40, 30, 20],
			cont = BX.create('DIV'),
			percWrap = cont.appendChild(BX.create('SPAN', {props: {className: 'bxhtmled-size-perc'}}));

		this.percVals = percVals;
		this.pPercWrap = percWrap;
		this.pSizeCont = cont;

		this.oSize = {};
		BX.bind(percWrap, 'click', function(e)
		{
			var node = e.target || e.srcElement;
			if (node)
			{
				var perc = parseInt(node.getAttribute('data-bx-size-val'), 10);
				if (perc)
				{
					_this.SetPercentSize(perc, true);
				}
			}
		});

		BX.bind(percWrap, 'mouseover', function(e)
		{
			var
				perc,
				node = e.target || e.srcElement;

			perc = parseInt(node.getAttribute('data-bx-size-val'), 10);
			_this.overPerc = perc > 0;
			if (_this.overPerc)
			{
				_this.SetPercentSize(perc, false);
			}
			else
			{
				if (setPercTimeout)
				{
					clearTimeout(setPercTimeout);
				}
				setPercTimeout = setTimeout(function()
				{
					if (!_this.overPerc)
					{
						_this.SetPercentSize(_this.savedPerc, false);
					}
				}, 200);
			}
		});
		BX.bind(percWrap, 'mouseout', function(e)
		{
			var
				perc,
				node = e.target || e.srcElement;

			if (setPercTimeout)
			{
				clearTimeout(setPercTimeout);
			}
			setPercTimeout = setTimeout(function()
			{
				if (!_this.overPerc)
				{
					_this.SetPercentSize(_this.savedPerc, false);
				}
			}, 200);
		});


		function widthOnchange()
		{
			var w = parseInt(_this.pWidth.value);
			if (!isNaN(w) && lastWidth != w)
			{
				if (!_this.sizeRatio && _this.originalWidth && _this.originalHeight)
				{
					_this.sizeRatio = _this.originalWidth / _this.originalHeight;
				}
				if (_this.sizeRatio)
				{
					_this.pHeight.value = Math.round(w / _this.sizeRatio);
					lastWidth = w;
					_this.ShowPreview();
				}
			}
		}

		function heightOnchange()
		{
			var h = parseInt(_this.pHeight.value);
			if (!isNaN(h) && lastHeight != h)
			{
				if (!_this.sizeRatio && _this.originalWidth && _this.originalHeight)
				{
					_this.sizeRatio = _this.originalWidth / _this.originalHeight;
				}
				if (_this.sizeRatio)
				{
					_this.pWidth.value = parseInt(h * _this.sizeRatio);
					lastHeight = h;
					_this.ShowPreview();
				}
			}
		}
		// Second row: width, height
		cont.appendChild(BX.create('LABEL', {text: BX.message('BXEdImgWidth') + ': '})).setAttribute('for', this.id + '-width');
		this.pWidth = cont.appendChild(BX.create('INPUT', {props: {type: 'text', id: this.id + '-width'}, style:{width: '40px', marginBottom: '4px'}}));
		cont.appendChild(BX.create('LABEL', {style: {marginLeft: '20px'}, text: BX.message('BXEdImgHeight') + ': '})).setAttribute('for', this.id + '-height');
		this.pHeight = cont.appendChild(BX.create('INPUT', {props: {type: 'text', id: this.id + '-height'}, style:{width: '40px', marginBottom: '4px'}}));
		// "No dimentions" checkbox
		this.pNoSize = cont.appendChild(BX.create('INPUT', {props: {type: 'checkbox', id: this.id + '-no-size', className: 'bxhtmled-img-no-size-ch'}}));
		cont.appendChild(BX.create('LABEL', {props: {className: 'bxhtmled-img-no-size-lbl'}, text: BX.message('BXEdImgNoSize')})).setAttribute('for', this.id + '-no-size');
		BX.bind(this.pNoSize, 'click', BX.proxy(this.NoSizeCheck, this));

		BX.bind(this.pWidth, 'blur', widthOnchange);
		BX.bind(this.pWidth, 'change', widthOnchange);
		BX.bind(this.pWidth, 'keyup', widthOnchange);
		BX.bind(this.pHeight, 'blur', heightOnchange);
		BX.bind(this.pHeight, 'change', heightOnchange);
		BX.bind(this.pHeight, 'keyup', heightOnchange);

		for (i = 0; i < percVals.length; i++)
		{
			percWrap.appendChild(BX.create('SPAN', {props: {className: 'bxhtmled-size-perc-i'}, attrs: {'data-bx-size-val': percVals[i]}, html: percVals[i] + '%'}));
		}

		return cont;
	};

	ImageDialog.prototype.NoSizeCheck = function()
	{
		if (this.pNoSize.checked)
		{
			BX.addClass(this.pSizeCont, 'bxhtmled-img-no-size-cont');
			this.pSizeRow.cells[0].style.height = this.pSizeRow.cells[1].style.height = '';
		}
		else
		{
			BX.removeClass(this.pSizeCont, 'bxhtmled-img-no-size-cont');
		}
		this.ShowPreview();
	};

	ImageDialog.prototype.SetPercentSize = function(perc, bSet)
	{
		var
			n, i,
			activeCn = 'bxhtmled-size-perc-i-active';

		if (bSet)
		{
			for (i = 0; i < this.pPercWrap.childNodes.length; i++)
			{
				n = this.pPercWrap.childNodes[i];
				if (perc && n.getAttribute('data-bx-size-val') == perc)
				{
					BX.addClass(n, activeCn);
				}
				else
				{
					BX.removeClass(n, activeCn);
				}
			}
		}

		if (perc !== false)
		{
			perc = perc / 100;
			this.pWidth.value = Math.round(this.originalWidth * perc) || '';
			this.pHeight.value = Math.round(this.originalHeight * perc) || '';
		}
		else if (this.savedWidth && this.savedHeight)
		{
			this.pWidth.value = this.savedWidth;
			this.pHeight.value = this.savedHeight;
		}

		this.ShowPreview();

		if (bSet)
		{
			this.savedWidth = this.pWidth.value;
			this.savedHeight = this.pHeight.value;
			this.savedPerc = perc !== false ? (perc || 1) * 100 : false;
		}
	};

	ImageDialog.prototype.SrcOnChange = function(updateSize)
	{
		var
			i,
			resPerc, perc, perc1, perc2,
			_this = this,
			src = this.pSrc.value;

		updateSize = updateSize !== false;

		if (this.lastSrc !== src)
		{
			this.lastSrc = src;
			if (!this.pInvisCont)
			{
				this.pInvisCont = this.pCont.appendChild(BX.create('DIV', {props: {className: 'bxhtmled-invis-cnt'}}));
			}
			else
			{
				BX.cleanNode(this.pInvisCont);
			}
			this.dummyImg = this.pInvisCont.appendChild(BX.create('IMG'));

			BX.bind(this.dummyImg, 'load', function()
			{
				setTimeout(function(){
					_this.originalWidth = _this.dummyImg.offsetWidth;
					_this.originalHeight = _this.dummyImg.offsetHeight;

					if (updateSize)
					{
						_this.pWidth.value = _this.originalWidth;
						_this.pHeight.value = _this.originalHeight;
						resPerc = 100;
					}
					else
					{
						resPerc = false;
						perc1 = Math.round(100 * parseInt(_this.pWidth.value) / parseInt(_this.originalWidth));
						perc2 = Math.round(100* parseInt(_this.pHeight.value) / parseInt(_this.originalHeight));

						// difference max 1%
						if (Math.abs(perc1 - perc2) <= 1)
						{
							perc = (perc1 + perc2) / 2;

							// inaccuracy 1% for calculating percent size
							for (i = 0; i < _this.percVals.length; i++)
							{
								if (Math.abs(_this.percVals[i] - perc) <= 1)
								{
									resPerc = _this.percVals[i];
									break;
								}
							}
						}
					}

					_this.sizeRatio = _this.originalWidth / _this.originalHeight;
					_this.SetPercentSize(resPerc, true);
					if (_this.bEmptySrcRowsHidden)
					{
						_this.ShowRows((_this.bAdditional ? ['preview', 'size', 'align', 'style', 'alt'] : ['preview', 'size']), true, true);
						_this.bEmptySrcRowsHidden = false;
					}

					_this.ShowPreview();
				}, 100);
			});

			BX.bind(this.dummyImg, 'error', function()
			{
				_this.pWidth.value = '';
				_this.pHeight.value = '';
			});

			this.dummyImg.src = src;
		}
	};

	ImageDialog.prototype.ShowPreview = function()
	{
		if (!this.pPreviewImg)
		{
			this.pPreviewImg = BX.create('IMG');
			this.pPreview.insertBefore(this.pPreviewImg, this.pPreview.firstChild);
		}

		if (this.pPreviewImg.src != this.pSrc.value)
		{
			this.pPreviewImg.src = this.pSrc.value;
		}

		// Size
		if (this.pNoSize.checked)
		{
			this.pPreviewImg.style.width = '';
			this.pPreviewImg.style.height = '';
		}
		else
		{
			this.pPreviewImg.style.width = this.pWidth.value + 'px';
			this.pPreviewImg.style.height = this.pHeight.value + 'px';
		}


		// Align
		var align = this.pAlign.value;
		if (align != this.pPreviewImg.align)
		{
			if (align == '')
			{
				this.pPreviewImg.removeAttribute('align');
			}
			else
			{
				this.pPreviewImg.align = align;
			}
		}
	};

	ImageDialog.prototype.SetValues = function(params)
	{
		if (!params)
		{
			params = {};
		}

		var i, row, rows = ['preview', 'size', 'align', 'style', 'alt'];
		this.lastSrc = '';
		this.bEmptySrcRowsHidden = this.bNewImage;
		if (this.bNewImage)
		{
			for (i = 0; i < rows.length; i++)
			{
				row = this.rows[rows[i]];
				if (row && row.cont)
				{
					row.cont.style.display = 'none';
					this.SetRowHeight(row.cont, 0, 0);
				}
			}
		}
		else
		{
			for (i = 0; i < rows.length; i++)
			{
				row = this.rows[rows[i]];
				if (row && row.cont)
				{
					row.cont.style.display = '';
					this.SetRowHeight(row.cont, row.height, 100);
				}
			}
		}

		this.pSrc.value = params.src || '';
		this.pTitle.value = params.title || '';
		this.pAlt.value = params.alt || '';
		this.pWidth.value = params.width || '';
		this.pHeight.value = params.height || '';
		this.pAlign.value = params.align || '';
		this.pClass.value = params.className || '';
		this.pLink.value = params.link || '';
		this.pNoSize.checked = params.noWidth && params.noHeight;
		this.NoSizeCheck();

		this.ShowRows(['align', 'style', 'alt', 'link'], false, false);
		this.bAdditional = false;
		this.SrcOnChange(!params.width || !params.height);

		if (!this.oClass)
		{
			this.oClass = new ClassSelector(this.editor,
				{
					id: this.id + '-class-selector',
					input: this.pClass,
					filterTag: 'IMG',
					value: this.pClass.value
				}
			);

			var _this = this;
			BX.addCustomEvent(this.oClass, "OnComboPopupClose", function()
			{
				_this.closeByEnter = true;
			});
			BX.addCustomEvent(this.oClass, "OnComboPopupOpen", function()
			{
				_this.closeByEnter = false;
			});
		}
		else
		{
			this.oClass.OnChange();
		}
	};
	ImageDialog.prototype.GetValues = function()
	{
		return {
			src: this.pSrc.value,
			title: this.pTitle.value,
			alt: this.pAlt.value,
			width: this.pNoSize.checked ? '' : this.pWidth.value,
			height: this.pNoSize.checked ? '' : this.pHeight.value,
			align: this.pAlign.value,
			className: this.pClass.value || '',
			link: this.pLink.value || '',
			image: this.image || false
		};
	};

	ImageDialog.prototype.Show = function(nodes, savedRange)
	{
		var
			range,
			value = {}, bxTag,
			i, img = false;

		if (!this.editor.iframeView.IsFocused())
		{
			this.editor.iframeView.Focus();
		}

		this.savedRange = savedRange;
		if (this.savedRange)
		{
			this.editor.selection.SetBookmark(this.savedRange);
		}

		if (!nodes)
		{
			range = this.editor.selection.GetRange();
			nodes = range.getNodes([1]);
		}

		if (nodes)
		{
			for (i = 0; i < nodes.length; i++)
			{
				img = nodes[i];
				bxTag = this.editor.GetBxTag(img);
				if (bxTag.tag || !img.nodeName || img.nodeName != 'IMG')
				{
					img = false;
				}
				else
				{
					break;
				}
			}
		}
		this.bNewImage = !img;

		this.image = img;
		if (img)
		{
			value.src = img.getAttribute('src');

			// Width
			if (img.style.width)
			{
				value.width = img.style.width;
			}
			if (!value.width && img.getAttribute('width'))
			{
				value.width = img.getAttribute('width');
			}
			if (!value.width)
			{
				value.width = img.offsetWidth;
				value.noWidth = true;
			}

			// Height
			if (img.style.height)
			{
				value.height = img.style.height;
			}
			if (!value.height && img.getAttribute('height'))
			{
				value.height = img.getAttribute('height');
			}
			if (!value.height)
			{
				value.height = img.offsetHeight;
				value.noHeight = true;
			}

			var cleanAttribute = img.getAttribute('data-bx-clean-attribute');
			if (cleanAttribute)
			{
				img.removeAttribute(cleanAttribute);
				img.removeAttribute('data-bx-clean-attribute');
			}

			value.alt = img.alt || '';
			value.title = img.title || '';
			value.title = img.title || '';
			value.className = img.className;
			value.align = img.align || '';

			var parentLink = img.parentNode.nodeName == 'A' ? img.parentNode : null;
			if (parentLink && parentLink.href)
			{
				value.link = parentLink.getAttribute('href');
			}
		}

		this.SetValues(value);
		this.SetTitle(BX.message('InsertImage'));

		// Call parrent Dialog.Show()
		ImageDialog.superclass.Show.apply(this, arguments);
	};

	ImageDialog.prototype.SetPanelHeight = function(height, opacity)
	{
		this.pSearchCont.style.height = height + 'px';
		this.pSearchCont.style.opacity = opacity / 100;

		this.editor.SetAreaContSize(this.origAreaWidth, this.origAreaHeight - height, {areaContTop: this.editor.toolbar.GetHeight() + height});
	};

	ImageDialog.prototype.ShowRows = function(rows, animate, show)
	{
		var
			_this = this,
			startHeight,
			endHeight,
			startOpacity,
			endOpacity,
			i, row;

		if (animate)
		{
			for (i = 0; i < rows.length; i++)
			{
				row = this.rows[rows[i]];
				if (row && row.cont)
				{
					if (row.animation)
						row.animation.stop();
					row.cont.style.display = '';
					if (show)
					{
						startHeight = 0;
						endHeight = row.height;
						startOpacity = 0;
						endOpacity = 100;
					}
					else
					{
						startHeight = row.height;
						endHeight = 0;
						startOpacity = 100;
						endOpacity = 0;
					}

					row.animation = new BX.easing({
						_row: row,
						duration : 300,
						start : {height: startHeight, opacity : startOpacity},
						finish : {height: endHeight, opacity : endOpacity},
						transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
						step : function(state)
						{
							_this.SetRowHeight(this._row.cont, state.height, state.opacity);
						},
						complete : function()
						{
							this._row.animation = null;
						}
					});

					row.animation.animate();
				}
			}
		}
		else
		{
			for (i = 0; i < rows.length; i++)
			{
				row = this.rows[rows[i]];
				if (row && row.cont)
				{
					if (show)
					{
						row.cont.style.display = '';
						this.SetRowHeight(row.cont, row.height, 100);
					}
					else
					{
						row.cont.style.display = 'none';
						this.SetRowHeight(row.cont, 0, 0);
					}
				}
			}
		}
	};

	ImageDialog.prototype.SetRowHeight = function(tr, height, opacity)
	{
		if (tr && tr.cells)
		{
			if (height == 0 || opacity == 0)
			{
				tr.style.display = 'none';
			}
			else
			{
				tr.style.display = '';
			}

			tr.style.opacity = opacity / 100;
			for (var i = 0; i < tr.cells.length; i++)
			{
				tr.cells[i].style.height = height + 'px';
			}
		}
	};

	/*
	SearchButton.prototype.ClosePanel = function(bShownReplace)
	{
		if (this.animation)
			this.animation.stop();

		this.pSearchCont.style.opacity = 1;
		if (bShownReplace)
		{
			this.animationStartHeight = this.height2;
			this.animationEndHeight = this.height1;
		}
		else
		{
			this.animationStartHeight = this.bReplaceOpened ? this.height2 : this.height1;
			this.animationEndHeight = this.height0;
		}

		var _this = this;
		this.animation = new BX.easing({
			duration : 200,
			start : {height: this.animationStartHeight, opacity : bShownReplace ? 100 : 0},
			finish : {height: this.animationEndHeight, opacity : 100},
			transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),

			step : function(state)
			{
				_this.SetPanelHeight(state.height, state.opacity);
			},

			complete : BX.proxy(function()
			{
				if (!bShownReplace)
					this.pSearchCont.style.display = 'none';
				this.animation = null;
			}, this)
		});

		this.animation.animate();
		if (!bShownReplace)
			this.bOpened = false;
	};

*/

	// Link
	function LinkDialog(editor, params)
	{
		params = {
			id: 'bx_link',
			width: 600,
			resizable: false,
			className: 'bxhtmled-link-dialog'
		};

		// Call parrent constructor
		LinkDialog.superclass.constructor.apply(this, [editor, params]);

		this.id = 'link' + this.editor.id;
		this.action = 'createLink';

		this.SetContent(this.Build());
		this.ChangeType();

		BX.addCustomEvent(this, "OnDialogSave", BX.proxy(this.Save, this));
	}
	BX.extend(LinkDialog, Dialog);

	LinkDialog.prototype.Build = function()
	{
		function addRow(tbl, c1Par, bAdditional)
		{
			var r, c1, c2;
			r = tbl.insertRow(-1);
			if (bAdditional)
			{
				r.className = 'bxhtmled-add-row';
			}

			c1 = r.insertCell(-1);
			c1.className = 'bxhtmled-left-c';

			if (c1Par && c1Par.label)
			{
				c1.appendChild(BX.create('LABEL', {text: c1Par.label})).setAttribute('for', c1Par.id);
			}

			c2 = r.insertCell(-1);
			c2.className = 'bxhtmled-right-c';
			return {row: r, leftCell: c1, rightCell: c2};
		}

		var
			r,
			cont = BX.create('DIV');

		var pTableWrap = BX.create('TABLE', {props: {className: 'bxhtmled-dialog-tbl bxhtmled-dialog-tbl-collapsed'}});

		// Link type
		r = addRow(pTableWrap, {label: BX.message('BXEdLinkType') + ':', id: this.id + '-type'});
		this.pType = r.rightCell.appendChild(BX.create('SELECT', {props: {id: this.id + '-type'}}));
		this.pType.options.add(new Option(BX.message('BXEdLinkTypeInner'), 'internal', true, true));
		this.pType.options.add(new Option(BX.message('BXEdLinkTypeOuter'), 'external', false, false));
		this.pType.options.add(new Option(BX.message('BXEdLinkTypeAnchor'), 'anchor', false, false));
		this.pType.options.add(new Option(BX.message('BXEdLinkTypeEmail'), 'email', false, false));
		BX.bind(this.pType, 'change', BX.delegate(this.ChangeType, this));

		// Link text
		r = addRow(pTableWrap, {label: BX.message('BXEdLinkText') + ':', id: this.id + '-text'});
		this.pText = r.rightCell.appendChild(BX.create('INPUT', {props: {type: 'text', id: this.id + '-text', placeholder: BX.message('BXEdLinkTextPh')}}));
		this.pTextCont = r.row;
		// Link html (for dificult cases (html without text nodes))
		r = addRow(pTableWrap, {label: BX.message('BXEdLinkInnerHtml') + ':', id: this.id + '-innerhtml'});
		this.pInnerHtml = r.rightCell.appendChild(BX.create('DIV', {props: {className: 'bxhtmled-ld-html-wrap'}}));
		this.pInnerHtmlCont = r.row;

		// Link href
		// 1. Internal
		r = addRow(pTableWrap, {label: BX.message('BXEdLinkHref') + ':', id: this.id + '-href'});
		this.pHrefIn = r.rightCell.appendChild(BX.create('INPUT', {props: {type: 'text', id: this.id + '-href', placeholder: BX.message('BXEdLinkHrefPh')}, style: {minWidth: '80%'}}));
		this.pFileDialogBut = r.rightCell.appendChild(BX.create('INPUT', {props: {className: 'bxhtmled-link-dialog-fdbut', type: 'button', id: this.id + '-href-fd', value: '...'}}));
		BX.bind(this.pFileDialogBut, 'click', BX.delegate(this.OpenFileDialog, this));
		this.pHrefIntCont = r.row;

		// 2. External
		r = addRow(pTableWrap, {label: BX.message('BXEdLinkHref') + ':', id: this.id + '-href-ext'});
		this.pHrefType = r.rightCell.appendChild(BX.create('SELECT', {props: {id: this.id + '-href-type'}}));
		this.pHrefType.options.add(new Option('http://', 'http://', false, false));
		this.pHrefType.options.add(new Option('https://', 'https://', false, false));
		this.pHrefType.options.add(new Option('ftp://', 'ftp://', false, false));
		this.pHrefType.options.add(new Option('', '', false, false));
		this.pHrefExt = r.rightCell.appendChild(BX.create('INPUT', {props: {type: 'text', id: this.id + '-href-ext', placeholder: BX.message('BXEdLinkHrefExtPh')}, style: {minWidth: '250px'}}));
		this.pHrefExtCont = r.row;

		// 3. Anchor
		r = addRow(pTableWrap, {label: BX.message('BXEdLinkHrefAnch') + ':', id: this.id + '-href-anch'});
		this.pHrefAnchor = r.rightCell.appendChild(BX.create('INPUT', {props: {type: 'text', id: this.id + '-href-anchor', placeholder: BX.message('BXEdLinkSelectAnchor')}}));
		this.pHrefAnchCont = r.row;

		// 4. E-mail
		r = addRow(pTableWrap, {label: BX.message('BXEdLinkHrefEmail') + ':', id: this.id + '-href-email'});
		this.pHrefEmail = r.rightCell.appendChild(BX.create('INPUT', {props: {type: 'email', id: this.id + '-href-email'}}));
		this.pHrefEmailCont = r.row;

		// *** Additional params ***
		r = pTableWrap.insertRow(-1);
		var addTitleCell = r.insertCell(-1);
		BX.adjust(addTitleCell, {props: {className: 'bxhtmled-title-cell bxhtmled-title-cell-foldable', colSpan: 2}, text: BX.message('BXEdLinkAdditionalTitle')});
		addTitleCell.onclick = function()
		{
			BX.toggleClass(pTableWrap, 'bxhtmled-dialog-tbl-collapsed');
		};

		// Use statistics
		r = addRow(pTableWrap, false, true);
		this.pStatCont = r.row;
		this.pStat = r.leftCell.appendChild(BX.create('INPUT', {props: {type: 'checkbox', id: this.id + '-stat'}}));
		r.rightCell.appendChild(BX.create('LABEL', {text: BX.message('BXEdLinkStat')})).setAttribute('for', this.id + '-stat');
		var
			wrap,
			statInfoCont = r.rightCell.appendChild(BX.create('DIV', {props: {className: 'bxhtmled-stat-wrap'}}));
		wrap = statInfoCont.appendChild(BX.create('DIV', {html: '<label for="event1">' + BX.message('BXEdLinkStatEv1') + ':</label> '}));
		this.pStatEvent1 = wrap.appendChild(BX.create('INPUT', {props: {type: 'text',  id: "event1"}, style: {minWidth: '50px'}}));
		wrap = statInfoCont.appendChild(BX.create('DIV', {html: '<label for="event2">' + BX.message('BXEdLinkStatEv2') + ':</label> '}));
		this.pStatEvent2 = wrap.appendChild(BX.create('INPUT', {props: {type: 'text',  id: "event2"}, style: {minWidth: '50px'}}));
		wrap = statInfoCont.appendChild(BX.create('DIV', {html: '<label for="event3">' + BX.message('BXEdLinkStatEv3') + ':</label> '}));
		this.pStatEvent3 = wrap.appendChild(BX.create('INPUT', {props: {type: 'text',  id: "event3"}, style: {minWidth: '50px'}}));
		BX.addClass(r.leftCell,'bxhtmled-left-c-top');
		BX.bind(this.pStat, 'click', BX.delegate(this.CheckShowStatParams, this));

		// Link title
		r = addRow(pTableWrap, {label: BX.message('BXEdLinkTitle') + ':', id: this.id + '-title'}, true);
		this.pTitle = r.rightCell.appendChild(BX.create('INPUT', {props: {type: 'text', id: this.id + '-title'}}));

		// Link class selector
		r = addRow(pTableWrap, {label: BX.message('BXEdCssClass') + ':', id: this.id + '-style'}, true);
		this.pClass = r.rightCell.appendChild(BX.create('INPUT', {props: {type: 'text', id: this.id + '-style'}}));

		// Link target
		r = addRow(pTableWrap, {label: BX.message('BXEdLinkTarget') + ':', id: this.id + '-target'}, true);
		this.pTarget = r.rightCell.appendChild(BX.create('SELECT', {props: {id: this.id + '-target'}}));
		this.pTarget.options.add(new Option(BX.message('BXEdLinkTargetBlank'), '_blank', false, false));
		this.pTarget.options.add(new Option(BX.message('BXEdLinkTargetParent'), '_parent', false, false));
		this.pTarget.options.add(new Option(BX.message('BXEdLinkTargetSelf'), '_self', true, true));
		this.pTarget.options.add(new Option(BX.message('BXEdLinkTargetTop'), '_top', false, false));

		// Nofollow noindex
		r = addRow(pTableWrap, false, true);
		this.pNoindex = r.leftCell.appendChild(BX.create('INPUT', {props: {type: 'checkbox', id: this.id + '-noindex'}}));
		r.rightCell.appendChild(BX.create('LABEL', {text: BX.message('BXEdLinkNoindex')})).setAttribute('for', this.id + '-noindex');
		BX.bind(this.pNoindex, 'click', BX.delegate(this.CheckNoindex, this));

		// Link id
		r = addRow(pTableWrap, {label: BX.message('BXEdLinkId') + ':', id: this.id + '-id'}, true);
		this.pId = r.rightCell.appendChild(BX.create('INPUT', {props: {type: 'text', id: this.id + '-id'}}));

		// Link rel
		r = addRow(pTableWrap, {label: BX.message('BXEdLinkRel') + ':', id: this.id + '-rel'}, true);
		this.pRel = r.rightCell.appendChild(BX.create('INPUT', {props: {type: 'text', id: this.id + '-rel'}}));

		// *** Additional params END ***

		cont.appendChild(pTableWrap);
		return cont;
	};

	LinkDialog.prototype.OpenFileDialog = function()
	{
		var run = window['BxOpenFileBrowserWindFile' + this.editor.id];
		if (run && typeof run == 'function')
		{
			var _this = this;
			window['OnFileDialogSelect' + this.editor.id] = function(filename, path, site)
			{
				_this.pHrefIn.value = (path == '/' ? '' : path) + '/' + filename;
				_this.pHrefIn.focus();
				_this.pHrefIn.select();

				// Clean function
				window['OnFileDialogSelect' + _this.editor.id] = null;
			};
			run();
		}
	};

	LinkDialog.prototype.ChangeType = function()
	{
		var type = this.pType.value;

		this.pHrefIntCont.style.display = 'none';
		this.pHrefExtCont.style.display = 'none';
		this.pHrefAnchCont.style.display = 'none';
		this.pHrefEmailCont.style.display = 'none';
		this.pStatCont.style.display = 'none';

		if (type == 'internal')
		{
			this.pHrefIntCont.style.display = '';
		}
		else if (type == 'external')
		{
			this.pStatCont.style.display = '';
			this.pHrefExtCont.style.display = '';
		}
		else if (type == 'anchor')
		{
			this.pHrefAnchCont.style.display = '';
		}
		else if (type == 'email')
		{
			this.pHrefEmailCont.style.display = '';
		}
	};

	LinkDialog.prototype.CheckShowStatParams = function()
	{
		if (this.pStat.checked)
		{
			BX.removeClass(this.pStatCont, 'bxhtmled-link-stat-hide');
		}
		else
		{
			BX.addClass(this.pStatCont, 'bxhtmled-link-stat-hide');
		}
	};

	LinkDialog.prototype.CheckNoindex = function()
	{
		if (this.pNoindex.checked)
		{
			this.pRel.value = 'nofollow';
			this.pRel.disabled = true;
		}
		else
		{
			this.pRel.value = this.pRel.value == 'nofollow' ? '' : this.pRel.value;
			this.pRel.disabled = false;
		}
	};

	LinkDialog.prototype.SetValues = function(values)
	{
		this.pHrefAnchor.value =
		this.pStatEvent1.value =
		this.pStatEvent2.value =
		this.pStatEvent3.value = '';
		this.pStat.checked = false;

		if (!values)
		{
			values = {};
		}
		else
		{
			// 1. Detect type
			var href = values.href || '';
			if (href != '')
			{
				if(href.substring(0, 'mailto:'.length).toLowerCase() == 'mailto:') // email
				{
					values.type = 'email';
					this.pHrefEmail.value = href.substring('mailto:'.length);
				}
				else if(href.substr(0, 1) == '#') // anchor
				{
					values.type = 'anchor';
					this.pHrefAnchor.value = href;
				}
				else if (href.indexOf("://") !== -1 || href.substr(0, 'www.'.length) == 'www.' || href.indexOf("&goto=") !== -1)
				{
					values.type = 'external';

					// Fix link in statistic
					if(href.substr(0, '/bitrix/redirect.php'.length) == '/bitrix/redirect.php')
					{
						this.pStat.checked = true;
						this.CheckShowStatParams();
						var sParams = href.substring('/bitrix/redirect.php'.length);
						function __ExtrParam(p, s)
						{
							var pos = s.indexOf(p + '=');
							if (pos < 0)
							{
								return '';
							}

							var pos2 = s.indexOf('&', pos + p.length+1);
							if (pos2 < 0)
							{
								s = s.substring(pos + p.length + 1);
							}
							else
							{
								s = s.substr(pos+p.length+1, pos2 - pos - 1 - p.length);
							}
							return unescape(s);
						};

						this.pStatEvent1.value = __ExtrParam('event1', sParams);
						this.pStatEvent2.value = __ExtrParam('event2', sParams);
						this.pStatEvent3.value = __ExtrParam('event3', sParams);

						href = __ExtrParam('goto', sParams);
					}

					if (href.substr(0, 'www.'.length) == 'www.')
						href = "http://" + href;
					var prot = href.substr(0, href.indexOf("://") + 3);
					this.pHrefType.value = prot;
					if (this.pHrefType.value != prot)
						this.pHrefType.value = '';
					this.pHrefExt.value = href.substring(href.indexOf("://") + 3);
				}
				else // link to page on server
				{
					values.type = 'internal';
					this.pHrefIn.value = href || '';
				}
			}

			if (!values.type)
			{
				if (values.text && values.text.match(this.editor.autolinkEmailRegExp))
				{
					this.pHrefEmail.value = values.text;
					values.type = 'email';
				}
				else
				{
					values.type = 'internal';
					this.pHrefIn.value = href || '';
				}
			}

			this.pType.value = values.type;
			this.pInnerHtmlCont.style.display = 'none';
			this.pTextCont.style.display = 'none';
			// Text
			if (values.bTextContent) // Simple text
			{
				this.pText.value = values.text || '';
				this.pTextCont.style.display = '';
			}
			else //
			{
				if (!values.text && values.innerHtml)
				{
					this.pInnerHtml.innerHTML = values.innerHtml;
					this.pInnerHtmlCont.style.display = '';
				}
				else
				{
					this.pText.value = values.text || '';
					this.pTextCont.style.display = '';
				}
				this._originalText = values.text;
			}
		}

		this.pTitle.value = values.title || '';
		this.pTarget.value = values.target || '_self';
		this.pClass.value = values.className || '';
		this.pId.value = values.id || '';
		this.pRel.value = values.rel || '';
		this.pNoindex.checked = values.noindex;

		this.ChangeType();
		this.CheckShowStatParams();
		this.CheckNoindex();

		if (!this.oClass)
		{
			this.oClass = new ClassSelector(this.editor,
				{
					id: this.id + '-class-selector',
					input: this.pClass,
					filterTag: 'A',
					value: this.pClass.value
				}
			);

			var _this = this;
			BX.addCustomEvent(this.oClass, "OnComboPopupClose", function()
			{
				_this.closeByEnter = true;
			});
			BX.addCustomEvent(this.oClass, "OnComboPopupOpen", function()
			{
				_this.closeByEnter = false;
			});
		}
		else
		{
			this.oClass.OnChange();
		}
	};

	LinkDialog.prototype.GetValues = function()
	{
		var
			type = this.pType.value,
			value = {
				text: this.pText.value,
				title: this.pTitle.value,
				id: this.pId.value,
				rel: this.pRel.value,
				noindex: !!this.pNoindex.checked
			};

		if (type == 'internal')
		{
			value.href = this.pHrefIn.value;
		}
		else if (type == 'external')
		{
			value.href = this.pHrefExt.value;
			if (this.pHrefType.value && value.href.indexOf('://') == -1)
			{
				value.href = this.pHrefType.value + value.href;
			}

			if(this.pStat.checked)
			{
				value.href = '/bitrix/redirect.php?event1=' + escape(this.pStatEvent1.value) + '&event2=' + escape(this.pStatEvent2.value) + '&event3=' + escape(this.pStatEvent3.value) + '&goto=' + escape(value.href);
			}
		}
		else if (type == 'anchor')
		{
			value.href = this.pHrefAnchor.value;
		}
		else if (type == 'email')
		{
			value.href = 'mailto:' + this.pHrefEmail.value;
		}

		if (this.pTarget.value !== '_self')
		{
			value.target = this.pTarget.value;
		}

		value.className = this.pClass.value || '';

		return value;
	};

	LinkDialog.prototype.Show = function(nodes, savedRange)
	{
		var
			values = {},
			i, l, link, lastLink, linksCount = 0;

		this.savedRange = savedRange;
		if (!nodes)
		{
			nodes = this.editor.action.CheckState('formatInline', {}, "a");
		}

		if (nodes)
		{
			// Selection contains links
			for (i = 0; i < nodes.length; i++)
			{
				link = nodes[i];
				if (link)
				{
					lastLink = link;
					linksCount++;
				}

				if (linksCount > 1)
				{
					break;
				}
			}

			// One link
			if (linksCount === 1 && lastLink)
			{
				// 1. Link contains only text
				if (!lastLink.querySelector("*"))
				{
					values.text = this.editor.util.GetTextContent(lastLink);
					values.bTextContent = true;
				}
				// Link contains
				else
				{
					values.text = this.editor.util.GetTextContent(lastLink);
					if (BX.util.trim(values.text) == '')
					{
						values.innerHtml = lastLink.innerHTML;
					}
					values.bTextContent = false;
				}

				var cleanAttribute = lastLink.getAttribute('data-bx-clean-attribute');
				if (cleanAttribute)
				{
					lastLink.removeAttribute(cleanAttribute);
					lastLink.removeAttribute('data-bx-clean-attribute');
				}

				values.noindex = lastLink.getAttribute('data-bx-noindex') == "Y";
				values.href = lastLink.getAttribute('href');
				values.title = lastLink.title;
				values.id = lastLink.id;
				values.rel = lastLink.getAttribute('rel');
				values.target = lastLink.target;
				values.className = lastLink.className;
			}
		}
		else
		{
			var text = BX.util.trim(this.editor.selection.GetText());
			if (text && text != this.editor.INVISIBLE_SPACE)
			{
				values.text = text;
			}
		}

		this.bNewLink = nodes && linksCount > 0;
		var anchors = [], bxTag;
		var surrs = this.editor.sandbox.GetDocument().querySelectorAll('.bxhtmled-surrogate');
		l = surrs.length;
		for (i = 0; i < l; i++)
		{
			bxTag = this.editor.GetBxTag(surrs[i]);
			if (bxTag.tag == 'anchor')
			{
				anchors.push({
					NAME: '#' + bxTag.params.name,
					DESCRIPTION: BX.message('BXEdLinkHrefAnch') + ': #' + bxTag.params.name,
					CLASS_NAME: 'bxhtmled-inp-popup-item'
				});
			}
		}

		if (anchors.length > 0)
		{
			this.oHrefAnchor = new BXInputPopup({
				id: this.id + '-href-anchor-cntrl' + Math.round(Math.random() * 1000000000),
				values: anchors,
				input: this.pHrefAnchor,
				className: 'bxhtmled-inp-popup'
			});

			BX.addCustomEvent(this.oHrefAnchor, "onInputPopupShow", function(anchorPopup)
			{
				if (anchorPopup && anchorPopup.oPopup &&  anchorPopup.oPopup.popupContainer)
				{
					anchorPopup.oPopup.popupContainer.style.zIndex = 3010;
				}
			});
		}

		this.SetValues(values);
		this.SetTitle(BX.message('InsertLink'));

		// Call parrent Dialog.Show()
		LinkDialog.superclass.Show.apply(this, arguments);
	};

	// Video dialog
	function VideoDialog(editor, params)
	{
		params = {
			id: 'bx_video',
			width: 600,
			className: 'bxhtmled-video-dialog'
		};

		this.sizes = [
			{key:'560x315', width: 560, height: 315},
			{key:'640x360', width: 640, height: 360},
			{key:'853x480', width: 853, height: 480},
			{key:'1280x720', width: 1280, height: 720}
		];

		// Call parrent constructor
		VideoDialog.superclass.constructor.apply(this, [editor, params]);
		this.id = 'video_' + this.editor.id;
		this.waitCounter = false;
		this.SetContent(this.Build());

		BX.addCustomEvent(this, "OnDialogSave", BX.proxy(this.Save, this));
	}
	BX.extend(VideoDialog, Dialog);

	VideoDialog.prototype.Build = function()
	{
		this.pCont = BX.create('DIV', {props: {className: 'bxhtmled-video-dialog-cnt bxhtmled-video-cnt  bxhtmled-video-empty'}});
		var
			_this = this,
			r, c,
			pTableWrap = BX.create('TABLE', {props: {className: 'bxhtmled-dialog-tbl bxhtmled-video-dialog-tbl'}});

		// Source
		r = this.AddTableRow(pTableWrap, {label: BX.message('BXEdVideoSource') + ':', id: this.id + '-source'});
		this.pSource = r.rightCell.appendChild(BX.create('INPUT', {props: {id: this.id + '-source', type: 'text', className: 'bxhtmled-90-input', placeholder: BX.message('BXEdVideoSourcePlaceholder')}}));
		BX.bind(this.pSource, 'change', BX.delegate(this.VideoSourceChanged, this));
		BX.bind(this.pSource, 'mouseup', BX.delegate(this.VideoSourceChanged, this));
		BX.bind(this.pSource, 'keyup', BX.delegate(this.VideoSourceChanged, this));

		r = pTableWrap.insertRow(-1);
		c = BX.adjust(r.insertCell(-1), {props:{className: 'bxhtmled-video-params-wrap'}, attrs: {colSpan: 2}});
		var pParTbl = c.appendChild(BX.create('TABLE', {props: {className: 'bxhtmled-dialog-tbl bxhtmled-video-dialog-tbl'}}));

		// Title
		r = this.AddTableRow(pParTbl, {label: BX.message('BXEdVideoInfoTitle') + ':', id: this.id + '-title'});
		this.pTitle = r.rightCell.appendChild(BX.create('INPUT', {props: {id: this.id + '-title', type: 'text', className: 'bxhtmled-90-input'}}));
		BX.addClass(r.row, 'bxhtmled-video-ext-row');

		// Size
		r = this.AddTableRow(pParTbl, {label: BX.message('BXEdVideoSize') + ':', id: this.id + '-size'});
		this.pSize = r.rightCell.appendChild(BX.create('SELECT', {props: {id: this.id + '-size'}}));
		BX.addClass(r.row, 'bxhtmled-video-ext-row');

		this.pUserSizeCnt = r.rightCell.appendChild(BX.create('SPAN', {props: {className: 'bxhtmled-user-size'}, style: {display: 'none'}}));
		this.pUserSizeCnt.appendChild(BX.create('LABEL', {props: {className: 'bxhtmled-width-lbl'}, text: BX.message('BXEdImgWidth') + ': ', attrs: {'for': this.id + '-width'}}));
		this.pWidth = this.pUserSizeCnt.appendChild(BX.create('INPUT', {props: {id: this.id + '-width', type: 'text'}}));
		this.pUserSizeCnt.appendChild(BX.create('LABEL', {props: {className: 'bxhtmled-width-lbl'}, text: BX.message('BXEdImgHeight') + ': ', attrs: {'for': this.id + '-height'}}));
		this.pHeight = this.pUserSizeCnt.appendChild(BX.create('INPUT', {props: {id: this.id + '-height', type: 'text'}}));
		BX.bind(this.pSize, 'change', function()
		{
			_this.pUserSizeCnt.style.display = _this.pSize.value == '' ? '' : 'none'
		});

		// Preview
		this.pPreviewCont = pParTbl.insertRow(-1);
		c = BX.adjust(this.pPreviewCont.insertCell(-1), {props:{title: BX.message('BXEdVideoPreview')},attrs: {colSpan: 2}});
		this.pPreview = c.appendChild(BX.create('DIV', {props: {className: 'bxhtmled-video-preview-cnt'}}));
		BX.addClass(this.pPreviewCont, 'bxhtmled-video-ext-row');

		this.pCont.appendChild(pTableWrap);
		return this.pCont;
	};

	VideoDialog.prototype.VideoSourceChanged = function()
	{
		var value = BX.util.trim(this.pSource.value);
		if (value !== this.lastSourceValue)
		{
			this.lastSourceValue = value;
			this.AnalyzeVideoSource(value);
		}
	};

	VideoDialog.prototype.AnalyzeVideoSource = function(value)
	{
		var _this = this;
		if (value.match(/<iframe([\s\S]*?)\/iframe>/gi))
		{
			var video = this.editor.phpParser.CheckForVideo(value);
			if (video)
			{
				var videoData = this.editor.phpParser.FetchVideoIframeParams(value, video.provider) || {};
				this.ShowVideoParams({
					html: value,
					provider: video.provider || false,
					title: videoData.origTitle || '',
					width: videoData.width || false,
					height: videoData.height || false
				});
			}
		}
		else
		{
			this.StartWaiting();
			this.editor.Request({
				getData: this.editor.GetReqData('video_oembed',
					{
						video_source: value
					}
				),
				handler: function(res)
				{
					if (res.result)
					{
						_this.StopWaiting();
						_this.ShowVideoParams(res.data);
					}
					else
					{
						_this.StopWaiting();
						if (res.error !== '')
						{
							_this.ShowVideoParams(false);
						}
					}
				}
			});
		}
	};

	VideoDialog.prototype.StartWaiting = function()
	{
		var
			dot = '',
			_this = this;

		this.waitCounter = (this.waitCounter === false || this.waitCounter > 3) ? 0 : this.waitCounter;

		if (_this.waitCounter == 1)
			dot = '.';
		else if (_this.waitCounter == 2)
			dot = '..';
		else if (_this.waitCounter == 3)
			dot = '...';

		_this.SetTitle(BX.message('BXEdVideoTitle') + dot);

		this.StopWaiting(false);
		this.waitingTimeout = setTimeout(
			function(){
				_this.waitCounter++;
				_this.StartWaiting();
			}, 250
		);
	};

	VideoDialog.prototype.StopWaiting = function(title)
	{
		if (this.waitingTimeout)
		{
			clearTimeout(this.waitingTimeout);
			this.waitingTimeout = null;
		}

		if (title !== false)
		{
			this.waitCounter = false;
			this.SetTitle(title || BX.message('BXEdVideoTitle'));
		}
	};

	VideoDialog.prototype.ShowVideoParams = function(data)
	{
		this.data = data;
		if (data === false || typeof data != 'object')
		{
			BX.addClass(this.pCont, 'bxhtmled-video-empty');
		}
		else
		{
			BX.removeClass(this.pCont, 'bxhtmled-video-empty');
			if (data.provider)
			{
				this.SetTitle(BX.message('BXEdVideoTitleProvider').replace('#PROVIDER_NAME#', BX.util.htmlspecialchars(data.provider)));
			}

			// Title
			this.pTitle.value = BX.util.htmlspecialchars(data.title) || '';

			// Size
			this.SetSize(data.width, data.height);

			// Preview
			if (data.html)
			{
				var
					w = Math.min(data.width, 560),
					h = Math.min(data.height, 315),
					previewHtml = data.html;

				previewHtml = this.UpdateHtml(previewHtml, w, h);
				this.pPreview.innerHTML = previewHtml;
				this.pPreviewCont.style.display = '';
			}
			else
			{
				this.pPreviewCont.style.display = 'none';
			}
		}
	};

	VideoDialog.prototype.SetSize = function(width, height)
	{
		var key = width + 'x' + height;
		if (!this.sizeIndex[key])
		{
			sizes = [{
				key: key,
				width: width, height: height,
				title: BX.message('BXEdVideoSizeAuto') + ' (' + width + ' x ' + height + ')'
			}].concat(this.sizes);
			this.ClearSizeControl(sizes);
		}
		this.pSize.value = key;
	};

	VideoDialog.prototype.ClearSizeControl = function(sizes)
	{
		sizes = sizes || this.sizes;
		this.pSize.options.length = 0;
		this.sizeIndex = {};
		for (var i = 0; i < sizes.length; i++)
		{
			this.sizeIndex[sizes[i].key] = true;
			this.pSize.options.add(new Option(sizes[i].title || (sizes[i].width + ' x ' + sizes[i].height), sizes[i].key, false, false));
		}
		this.pSize.options.add(new Option(BX.message('BXEdVideoSizeCustom'), '', false, false));
	};

	VideoDialog.prototype.UpdateHtml = function(html, width, height, title)
	{
		var bTitle = false;
		html = html.replace(/((?:title)|(?:width)|(?:height))\s*=\s*("|')([\s\S]*?)(\2)/ig, function(s, attrName, q, attrValue)
		{
			attrName = attrName.toLowerCase();
			if (attrName == 'width' && width)
			{
				return attrName + '="' + width + '"';
			}
			else if(attrName == 'height' && height)
			{
				return attrName + '="' + height + '"';
			}
			else if (attrName == 'title' && title)// title
			{
				bTitle = true;
				return attrName + '="' + title + '"';
			}
			return '';
		});

		if (!bTitle && title)
		{
			html = html.replace(/<iframe\s*/i, function(s)
			{
				return s + ' title="' + title + '" ';
			});
		}

		return html;
	};

	VideoDialog.prototype.Show = function(bxTag, savedRange)
	{
		this.savedRange = savedRange;
		if (this.savedRange)
		{
			this.editor.selection.SetBookmark(this.savedRange);
		}

		this.SetTitle(BX.message('BXEdVideoTitle'));
		this.ClearSizeControl();

		this.bEdit = bxTag && bxTag.tag == 'video';
		this.bxTag = bxTag;
		if (this.bEdit)
		{
			this.pSource.value = this.lastSourceValue = bxTag.params.value;
			this.AnalyzeVideoSource(bxTag.params.value);
		}
		else
		{
			this.ShowVideoParams(false);
			this.pSource.value = '';
		}

		// Call parrent Dialog.Show()
		VideoDialog.superclass.Show.apply(this, arguments);
	};

	VideoDialog.prototype.Save = function()
	{
		var
			title = this.pTitle.value,
			width = parseInt(this.pWidth.value),
			height = parseInt(this.pHeight.value),
			html = this.data.html;

		title = BX.util.htmlspecialchars(title);
		title = title.replace('"', '\"');

		if (this.pSize.value !== '')
		{
			var sz = this.pSize.value.split('x');
			if (sz && sz.length == 2)
			{
				width = parseInt(sz[0]);
				height = parseInt(sz[1]);
			}
		}
		html = this.UpdateHtml(html, width, height, title);

		if (this.bEdit)
		{
			if (this.data && this.editor.action.IsSupported('insertHTML') )
			{
				var node = this.editor.GetIframeDoc().getElementById(this.bxTag.id);
				if (node)
				{
					this.editor.selection.SelectNode(node);
				}
				this.editor.action.Exec('insertHTML', html);
			}
		}
		else
		{
			if (this.data && this.editor.action.IsSupported('insertHTML') )
			{
				if (this.savedRange)
				{
					this.editor.selection.SetBookmark(this.savedRange);
				}

				this.editor.action.Exec('insertHTML', html);
			}
		}

		var _this = this;
		setTimeout(function()
		{
			_this.editor.synchro.FullSyncFromIframe();
		}, 50);
	};


	// Source dialog (php, javascript, html-comment, iframe, style, etc.)
	function SourceDialog(editor, params)
	{
		params = {
			id: 'bx_source',
			height: 400,
			width: 700,
			resizable: true,
			className: 'bxhtmled-source-dialog'
		};

		// Call parrent constructor
		SourceDialog.superclass.constructor.apply(this, [editor, params]);
		this.id = 'source_' + this.editor.id;
		this.SetContent(this.Build());

		BX.addCustomEvent(this, "OnDialogSave", BX.proxy(this.Save, this));
	}
	BX.extend(SourceDialog, Dialog);

	SourceDialog.prototype.Build = function()
	{
		this.pValue = BX.create('TEXTAREA', {props: {className: 'bxhtmled-source-value', id: this.id + '-value'}});
		return this.pValue;
	};

	SourceDialog.prototype.OnResize = function()
	{
		var
			w = this.oDialog.PARTS.CONTENT_DATA.offsetWidth,
			h = this.oDialog.PARTS.CONTENT_DATA.offsetHeight;

		this.pValue.style.width = (w - 30) + 'px';
		this.pValue.style.height = (h - 30) + 'px';
	};

	SourceDialog.prototype.OnResizeFinished = function()
	{
	};

	SourceDialog.prototype.Save = function()
	{
		this.bxTag.params.value = this.pValue.value;
		this.editor.SetBxTag(false, this.bxTag);
		var _this = this;
		setTimeout(function()
		{
			_this.editor.synchro.FullSyncFromIframe();
		}, 50);
	};

	SourceDialog.prototype.Show = function(bxTag)
	{
		this.bxTag = bxTag;
		if (bxTag && bxTag.tag)
		{
			this.SetTitle(bxTag.name);
			this.pValue.value = bxTag.params.value;
			// Call parrent Dialog.Show()
			SourceDialog.superclass.Show.apply(this, arguments);
			this.OnResize();
			BX.focus(this.pValue);
		}
	};

	// Anchor dialog
	function AnchorDialog(editor, params)
	{
		params = {
			id: 'bx_anchor',
			width: 300,
			resizable: false,
			className: 'bxhtmled-anchor-dialog'
		};

		// Call parrent constructor
		AnchorDialog.superclass.constructor.apply(this, [editor, params]);
		this.id = 'anchor_' + this.editor.id;
		this.SetContent(this.Build());

		BX.addCustomEvent(this, "OnDialogSave", BX.proxy(this.Save, this));
	}
	BX.extend(AnchorDialog, Dialog);

	AnchorDialog.prototype.Build = function()
	{
		var cont = BX.create('DIV');
		cont.appendChild(BX.create('LABEL', {text: BX.message('BXEdAnchorName') + ': '})).setAttribute('for', this.id + '-value');
		this.pValue = cont.appendChild(BX.create('INPUT', {props: {className: '', id: this.id + '-value'}}));
		return cont;
	};

	AnchorDialog.prototype.Save = function()
	{
		this.bxTag.params.name = BX.util.trim(this.pValue.value.replace(/[^ a-z0-9_\-]/gi, ""));
		this.editor.SetBxTag(false, this.bxTag);
		var _this = this;
		setTimeout(function()
		{
			_this.editor.synchro.FullSyncFromIframe();
		}, 50);
	};

	AnchorDialog.prototype.Show = function(bxTag)
	{
		this.bxTag = bxTag;
		if (bxTag && bxTag.tag)
		{
			this.SetTitle(BX.message('BXEdAnchor'));
			this.pValue.value = bxTag.params.name;
			// Call parrent Dialog.Show()
			AnchorDialog.superclass.Show.apply(this, arguments);
			BX.focus(this.pValue);
			this.pValue.select();
		}
	};


	// Table dialog
	function TableDialog(editor, params)
	{
		params = {
			id: 'bx_table',
			width: 600,
			resizable: false,
			className: 'bxhtmled-table-dialog'
		};

		// Call parrent constructor
		LinkDialog.superclass.constructor.apply(this, [editor, params]);

		this.id = 'table' + this.editor.id;
		this.action = 'insertTable';

		this.SetContent(this.Build());

		BX.addCustomEvent(this, "OnDialogSave", BX.proxy(this.Save, this));
	}
	BX.extend(TableDialog, Dialog);

	TableDialog.prototype.Build = function()
	{
		var
			pInnerTable,
			r, c,
			cont = BX.create('DIV');

		var pTableWrap = BX.create('TABLE', {props: {className: 'bxhtmled-dialog-tbl bxhtmled-dialog-tbl-hide-additional'}});
		r = pTableWrap.insertRow(-1); // 1 row
		c = BX.adjust(r.insertCell(-1), {attrs: {colSpan: 4}}); // First row

		pInnerTable = c.appendChild(BX.create('TABLE', {props: {className: 'bxhtmled-dialog-tbl'}}));
		r = pInnerTable.insertRow(-1); // 1.1 row

		// Rows
		c = BX.adjust(r.insertCell(-1), {props: {className: 'bxhtmled-lbl-cell'}});
		c.appendChild(BX.create('LABEL', {text: BX.message('BXEdTableRows') + ':', attrs: {'for': this.id + '-rows'}}));
		c = BX.adjust(r.insertCell(-1), {props: {className: 'bxhtmled-val-cell'}});
		this.pRows = c.appendChild(BX.create('INPUT', {props: {type: 'text', id: this.id + '-rows'}}));

		// Width
		c = BX.adjust(r.insertCell(-1), {props: {className: 'bxhtmled-lbl-cell'}});
		c.appendChild(BX.create('LABEL', {text: BX.message('BXEdTableWidth') + ':', attrs: {'for': this.id + '-width'}}));
		c = BX.adjust(r.insertCell(-1), {props: {className: 'bxhtmled-val-cell'}});
		this.pWidth = c.appendChild(BX.create('INPUT', {props: {type: 'text', id: this.id + '-width'}}));

		r = pInnerTable.insertRow(-1); // 1.2  row
		// Cols
		c = BX.adjust(r.insertCell(-1), {props: {className: 'bxhtmled-lbl-cell'}});
		c.appendChild(BX.create('LABEL', {text: BX.message('BXEdTableRows') + ':', attrs: {'for': this.id + '-cols'}}));
		c = BX.adjust(r.insertCell(-1), {props: {className: 'bxhtmled-val-cell'}});
		this.pCols = c.appendChild(BX.create('INPUT', {props: {type: 'text', id: this.id + '-cols'}}));

		// Height
		c = BX.adjust(r.insertCell(-1), {props: {className: 'bxhtmled-lbl-cell'}});
		c.appendChild(BX.create('LABEL', {text: BX.message('BXEdTableHeight') + ':', attrs: {'for': this.id + '-height'}}));
		c = BX.adjust(r.insertCell(-1), {props: {className: 'bxhtmled-val-cell'}});
		this.pHeight = c.appendChild(BX.create('INPUT', {props: {type: 'text', id: this.id + '-height'}}));

		// *** Additional params ***
		r = pTableWrap.insertRow(-1);
		var addTitleCell = BX.adjust(r.insertCell(-1), {props: {className: 'bxhtmled-title-cell bxhtmled-title-cell-foldable', colSpan: 4}, text: BX.message('BXEdLinkAdditionalTitle')});
		BX.bind(addTitleCell, "click", function()
		{
			BX.toggleClass(pTableWrap, 'bxhtmled-dialog-tbl-hide-additional');
		});

		var pTbody = pTableWrap.appendChild(BX.create('TBODY', {props: {className: 'bxhtmled-additional-tbody'}}));

		r = pTbody.insertRow(-1); // 3rd row
		// Header cells
		c = BX.adjust(r.insertCell(-1), {props: {className: 'bxhtmled-lbl-cell'}});
		c.appendChild(BX.create('LABEL', {text: BX.message('BXEdTableHeads') + ':', attrs: {'for': this.id + '-th'}}));
		c = BX.adjust(r.insertCell(-1), {props: {className: 'bxhtmled-val-cell'}});

		this.pHeaders = c.appendChild(BX.create('SELECT', {props: {id: this.id + '-th'}, style: {width: '130px'}}));
		this.pHeaders.options.add(new Option(BX.message('BXEdThNone'), '', true, true));
		this.pHeaders.options.add(new Option(BX.message('BXEdThTop'), 'top', false, false));
		this.pHeaders.options.add(new Option(BX.message('BXEdThLeft'), 'left', false, false));
		this.pHeaders.options.add(BX.adjust(new Option(BX.message('BXEdThTopLeft'), 'topleft', false, false), {props: {title: BX.message('BXEdThTopLeftTitle')}}));

		// CellSpacing
		c = BX.adjust(r.insertCell(-1), {props: {className: 'bxhtmled-lbl-cell'}});
		c.appendChild(BX.create('LABEL', {text: BX.message('BXEdTableCellSpacing') + ':', attrs: {'for': this.id + '-cell-spacing'}}));
		c = BX.adjust(r.insertCell(-1), {props: {className: 'bxhtmled-val-cell'}});
		this.pCellSpacing = c.appendChild(BX.create('INPUT', {props: {type: 'text', id: this.id + '-cell-spacing'}}));

		r = pTbody.insertRow(-1); // 4th row
		// Border
		c = BX.adjust(r.insertCell(-1), {props: {className: 'bxhtmled-lbl-cell'}});
		c.appendChild(BX.create('LABEL', {text: BX.message('BXEdTableBorder') + ':', attrs: {'for': this.id + '-border'}}));
		c = BX.adjust(r.insertCell(-1), {props: {className: 'bxhtmled-val-cell'}});
		this.pBorder = c.appendChild(BX.create('INPUT', {props: {type: 'text', id: this.id + '-border'}}));

		// CellPadding
		c = BX.adjust(r.insertCell(-1), {props: {className: 'bxhtmled-lbl-cell'}});
		c.appendChild(BX.create('LABEL', {text: BX.message('BXEdTableCellPadding') + ':', attrs: {'for': this.id + '-cell-padding'}}));
		c = BX.adjust(r.insertCell(-1), {props: {className: 'bxhtmled-val-cell'}});
		this.pCellPadding = c.appendChild(BX.create('INPUT', {props: {type: 'text', id: this.id + '-cell-padding'}}));

		r = pTbody.insertRow(-1); // 5th row
		c = BX.adjust(r.insertCell(-1), {attrs: {colSpan: 4}});

		pInnerTable = c.appendChild(BX.create('TABLE', {props: {className: 'bxhtmled-dialog-inner-tbl'}}));

		// Table align
		r = pInnerTable.insertRow(-1); // 5.0 align row
		c = BX.adjust(r.insertCell(-1), {props: {className: 'bxhtmled-inner-lbl-cell'}});
		c.appendChild(BX.create('LABEL', {text: BX.message('BXEdTableAlign') + ':', attrs: {'for': this.id + '-align'}}));
		c = BX.adjust(r.insertCell(-1), {props: {className: 'bxhtmled-inner-val-cell'}});
		this.pAlign = c.appendChild(BX.create('SELECT', {props: {id: this.id + '-align'}, style: {width: '130px'}}));
		this.pAlign.options.add(new Option(BX.message('BXEdTableAlignLeft'), 'left', true, true));
		this.pAlign.options.add(new Option(BX.message('BXEdTableAlignCenter'), 'center', false, false));
		this.pAlign.options.add(new Option(BX.message('BXEdTableAlignRight'), 'right', false, false));

		r = pInnerTable.insertRow(-1); // 5.1 th row
		// Table caption
		c = BX.adjust(r.insertCell(-1), {props: {className: 'bxhtmled-inner-lbl-cell'}});
		c.appendChild(BX.create('LABEL', {text: BX.message('BXEdTableCaption') + ':', attrs: {'for': this.id + '-caption'}}));
		c = BX.adjust(r.insertCell(-1), {props: {className: 'bxhtmled-inner-val-cell'}});
		this.pCaption = c.appendChild(BX.create('INPUT', {props: {type: 'text', id: this.id + '-caption'}}));

		r = pInnerTable.insertRow(-1); // 5.2 th row
		// CSS class selector
		c = BX.adjust(r.insertCell(-1), {props: {className: 'bxhtmled-inner-lbl-cell'}});
		c.appendChild(BX.create('LABEL', {text: BX.message('BXEdCssClass') + ':', attrs: {'for': this.id + '-class'}}));
		c = BX.adjust(r.insertCell(-1), {props: {className: 'bxhtmled-inner-val-cell'}});
		this.pClass = c.appendChild(BX.create('INPUT', {props: {type: 'text', id: this.id + '-class'}}));

		r = pInnerTable.insertRow(-1); // 5.3 th row
		// Id
		c = BX.adjust(r.insertCell(-1), {props: {className: 'bxhtmled-inner-lbl-cell'}});
		c.appendChild(BX.create('LABEL', {text: BX.message('BXEdTableId') + ':', attrs: {'for': this.id + '-id'}}));
		c = BX.adjust(r.insertCell(-1), {props: {className: 'bxhtmled-inner-val-cell'}});
		this.pId = c.appendChild(BX.create('INPUT', {props: {type: 'text', id: this.id + '-id'}}));
		// *** Additional params END ***

		cont.appendChild(pTableWrap);
		return cont;
	};

	TableDialog.prototype.SetValues = function(values)
	{
		this.pRows.value = values.rows || 3;
		this.pCols.value = values.cols || 4;
		this.pWidth.value = values.width || '';
		this.pHeight.value = values.height || '';
		this.pId.value = values.id || '';
		this.pCaption.value = values.caption || '';
		this.pCellPadding.value = values.cellPadding || 0;
		this.pCellSpacing.value = values.cellSpacing || 0;
		this.pBorder.value = values.border || '';
		this.pClass.value = values.className || '';
		this.pHeaders.value = values.headers || '';
		this.pRows.disabled = this.pCols.disabled = !!this.currentTable;
		this.pAlign.value = values.align || 'left';

		if (!this.oClass)
		{
			this.oClass = new ClassSelector(this.editor,
				{
					id: this.id + '-class-selector',
					input: this.pClass,
					filterTag: 'TABLE',
					value: this.pClass.value
				}
			);

			var _this = this;
			BX.addCustomEvent(this.oClass, "OnComboPopupClose", function()
			{
				_this.closeByEnter = true;
			});
			BX.addCustomEvent(this.oClass, "OnComboPopupOpen", function()
			{
				_this.closeByEnter = false;
			});
		}
		else
		{
			this.oClass.OnChange();
		}
	};

	TableDialog.prototype.GetValues = function()
	{
		return {
			table: this.currentTable || false,
			rows: parseInt(this.pRows.value) || 1,
			cols: parseInt(this.pCols.value) || 1,
			width: BX.util.trim(this.pWidth.value),
			height: BX.util.trim(this.pHeight.value),
			id: BX.util.trim(this.pId.value),
			caption: BX.util.trim(this.pCaption.value),
			cellPadding: parseInt(this.pCellPadding.value) || '',
			cellSpacing: parseInt(this.pCellSpacing.value) || '',
			border: parseInt(this.pBorder.value) || '',
			headers: this.pHeaders.value,
			className: this.pClass.value,
			align: this.pAlign.value
		};
	};

	TableDialog.prototype.Show = function(nodes, savedRange)
	{
		var
			table,
			value = {};

		this.savedRange = savedRange;
		if (this.savedRange)
		{
			this.editor.selection.SetBookmark(this.savedRange);
		}

		if (!nodes)
		{
			nodes = this.editor.action.CheckState('insertTable');
		}

		if (nodes && nodes.nodeName)
		{
			table = nodes;
		}
		else if ((nodes && nodes[0] && nodes[0].nodeName))
		{
			table = nodes[0];
		}

		this.currentTable = false;
		if (table)
		{
			this.currentTable = table;
			value.rows = table.rows.length;
			value.cols = table.rows[0].cells.length;

			// Width
			if (table.style.width)
			{
				value.width = table.style.width;
			}
			if (!value.width && table.width)
			{
				value.width = table.width;
			}

			// Height
			if (table.style.height)
			{
				value.height = table.style.height;
			}
			if (!value.height && table.height)
			{
				value.height = table.height;
			}

			value.cellPadding = table.getAttribute('cellPadding') || '';
			value.cellSpacing = table.getAttribute('cellSpacing') || '';
			value.border = table.getAttribute('border') || 0;
			value.id = table.getAttribute('id') || '';
			var pCaption = BX.findChild(table, {tag: 'CAPTION'}, false);
			value.caption = pCaption ? BX.util.htmlspecialcharsback(pCaption.innerHTML) : '';
			value.className = table.className || '';

			// Determine headers
			var r, c, pCell, bTop = true, bLeft = true;
			for(r = 0; r < table.rows.length; r++)
			{
				for(c = 0; c < table.rows[r].cells.length; c++)
				{
					pCell = table.rows[r].cells[c];
					if (r == 0)
					{
						bTop = pCell.nodeName == 'TH' && bTop;
					}

					if (c == 0)
					{
						bLeft = pCell.nodeName == 'TH' && bLeft;
					}
				}
			}

			if (!bTop && !bLeft)
			{
				value.headers = '';
			}
			else if(bTop && bLeft)
			{
				value.headers = 'topleft';
			}
			else if(bTop)
			{
				value.headers = 'top';
			}
			else
			{
				value.headers = 'left';
			}

			// Align
			value.align = table.getAttribute('align');
		}

		this.SetValues(value);
		this.SetTitle(BX.message('BXEdTable'));
		// Call parrent Dialog.Show()
		TableDialog.superclass.Show.apply(this, arguments);
	};
	// Table dialog END



	// Setting dialog
	function SettingsDialog(editor, params)
	{
		params = {
			id: 'bx_settings',
			height: 100,
			width: 400,
			resizable: false
		};

		this.id = 'settings';

		// Call parrent constructor
		DefaultDialog.superclass.constructor.apply(this, [editor, params]);

		var cont = BX.create('DIV', {text: 'settings here!'});

		this.SetContent(cont);

		BX.addCustomEvent(this, "OnDialogSave", BX.proxy(this.Save, this));
	}
	BX.extend(SettingsDialog, Dialog);

	SettingsDialog.prototype.Show = function()
	{
		var value = {};
		this.SetValues(value);
		this.SetTitle(BX.message('BXEdSettings'));
		// Call parrent Dialog.Show()
		SettingsDialog.superclass.Show.apply(this, arguments);
	};

	// Default properties dialog
	function DefaultDialog(editor, params)
	{
		params = {
			id: 'bx_default',
			width: 500,
			resizable: false,
			className: 'bxhtmled-default-dialog'
		};

		this.id = 'default';
		this.action = 'universalFormatStyle';

		// Call parrent constructor
		DefaultDialog.superclass.constructor.apply(this, [editor, params]);

		this.SetContent(this.Build());

		BX.addCustomEvent(this, "OnDialogSave", BX.proxy(this.Save, this));
	}
	BX.extend(DefaultDialog, Dialog);

	DefaultDialog.prototype.Show = function(nodes, savedRange)
	{
		var
			nodeNames = [],
			i,
			style,
			renewNodes = typeof nodes !== 'object' || nodes.length == 0,
			className;

		this.savedRange = savedRange;
		if (this.savedRange)
		{
			this.editor.selection.SetBookmark(this.savedRange);
		}

		if (renewNodes)
		{
			nodes = this.editor.action.CheckState(this.action);
		}
		if (!nodes)
		{
			nodes = [];
		}

		for (i = 0; i < nodes.length; i++)
		{
			if (style === undefined && className === undefined)
			{
				style = nodes[i].style.cssText;
				className = nodes[i].className;
			}
			else
			{
				style = nodes[i].style.cssText === style ? style : false;
				className = nodes[i].className === className ? className : false;
			}
			nodeNames.push(nodes[i].nodeName);
		}

		this.SetValues({
			nodes: nodes,
			renewNodes: renewNodes,
			style: style,
			className: className
		});

		this.SetTitle(BX.message('BXEdDefaultPropDialog').replace('#NODES_LIST#', nodeNames.join(', ')));

		// Call parrent Dialog.Show()
		DefaultDialog.superclass.Show.apply(this, arguments);
	};

	DefaultDialog.prototype.Build = function()
	{
		var
			r, c,
			cont = BX.create('DIV');

		var pTableWrap = BX.create('TABLE', {props: {className: 'bxhtmled-dialog-tbl'}});

		// Css class
		r = pTableWrap.insertRow(-1);
		c = BX.adjust(r.insertCell(-1), {props: {className: 'bxhtmled-left-c'}});
		c.appendChild(BX.create('LABEL', {text: BX.message('BXEdCssClass') + ':', attrs: {'for': this.id + '-css-class'}}));
		c = BX.adjust(r.insertCell(-1), {props: {className: 'bxhtmled-right-c'}});
		this.pCssClass = c.appendChild(BX.create('INPUT', {props: {type: 'text', id: this.id + '-css-class'}}));

		// Inline css
		r = pTableWrap.insertRow(-1);
		c = BX.adjust(r.insertCell(-1), {props: {className: 'bxhtmled-left-c'}});
		c.appendChild(BX.create('LABEL', {text: BX.message('BXEdCSSStyle') + ':', attrs: {'for': this.id + '-css-style'}}));
		c = BX.adjust(r.insertCell(-1), {props: {className: 'bxhtmled-right-c'}});
		this.pCssStyle = c.appendChild(BX.create('INPUT', {props: {type: 'text', id: this.id + '-css-style'}}));

		cont.appendChild(pTableWrap);
		return cont;
	};

	DefaultDialog.prototype.SetValues = function(values)
	{
		if (!values)
		{
			values = {};
		}

		this.nodes = values.nodes || [];
		this.renewNodes = values.renewNodes;
		this.pCssStyle.value = values.style || '';
		this.pCssClass.value = values.className || '';

		if (!this.oClass)
		{
			this.oClass = new ClassSelector(this.editor,
				{
					id: this.id + '-class-selector',
					input: this.pCssClass,
					filterTag: 'A',
					value: this.pCssClass.value
				}
			);

			var _this = this;
			BX.addCustomEvent(this.oClass, "OnComboPopupClose", function()
			{
				_this.closeByEnter = true;
			});
			BX.addCustomEvent(this.oClass, "OnComboPopupOpen", function()
			{
				_this.closeByEnter = false;
			});
		}
		else
		{
			this.oClass.OnChange();
		}
	};

	DefaultDialog.prototype.GetValues = function()
	{
		return {
			className: this.pCssClass.value,
			style: this.pCssStyle.value,
			nodes: this.renewNodes ? [] : this.nodes
		};
	};

	// Specialchars dialog
	function SpecialcharDialog(editor, params)
	{
		this.editor = editor;
		params = {
			id: 'bx_char',
			width: 570,
			resizable: false,
			className: 'bxhtmled-char-dialog'
		};
		this.id = 'char' + this.editor.id;
		// Call parrent constructor
		SpecialcharDialog.superclass.constructor.apply(this, [editor, params]);

		this.oDialog.ClearButtons();
		this.oDialog.SetButtons([this.oDialog.btnCancel]);

		this.SetContent(this.Build());
		BX.addCustomEvent(this, "OnDialogSave", BX.proxy(this.Save, this));
	}
	BX.extend(SpecialcharDialog, Dialog);

	SpecialcharDialog.prototype.Build = function()
	{
		var
			_this = this,
			r, c, i,
			cells = 18, ent,
			l = this.editor.HTML_ENTITIES.length,
			cont = BX.create('DIV');

		var pTableWrap = BX.create('TABLE', {props: {className: 'bxhtmled-specialchar-tbl'}});

		for(i = 0; i < l; i++)
		{
			if (i % cells == 0) // new row
			{
				r = pTableWrap.insertRow(-1);
			}

			ent = this.editor.HTML_ENTITIES[i];
			c = r.insertCell(-1);
			c.innerHTML = ent;
			c.setAttribute('data-bx-specialchar', ent);
			c.title = BX.message('BXEdSpecialchar') + ': ' + ent.substr(1, ent.length - 2);
		}

		BX.bind(pTableWrap, 'click', function(e)
		{
			var
				ent,
				target = e.target || e.srcElement;
			if (target.nodeType == 3)
			{
				target = target.parentNode;
			}

			if (target && target.getAttribute && target.getAttribute('data-bx-specialchar') &&
				_this.editor.action.IsSupported('insertHTML'))
			{
				if (_this.savedRange)
				{
					_this.editor.selection.SetBookmark(_this.savedRange);
				}

				ent = target.getAttribute('data-bx-specialchar');
				_this.editor.On('OnSpecialcharInserted', [ent]);
				_this.editor.action.Exec('insertHTML', ent);
			}
			_this.oDialog.Close();
		});

		cont.appendChild(pTableWrap);
		return cont;
	};

	SpecialcharDialog.prototype.SetValues = BX.DoNothing;
	SpecialcharDialog.prototype.GetValues = BX.DoNothing;

	SpecialcharDialog.prototype.Show = function(savedRange)
	{
		this.savedRange = savedRange;
		if (this.savedRange)
		{
			this.editor.selection.SetBookmark(this.savedRange);
		}

		this.SetTitle(BX.message('BXEdSpecialchar'));
		// Call parrent Dialog.Show()
		SpecialcharDialog.superclass.Show.apply(this, arguments);
	};
	// Specialchars dialog END
	/* ~~~~ Editor dialogs END~~~~*/

	function __run()
	{
		window.BXHtmlEditor.TaskbarManager = TaskbarManager;
		window.BXHtmlEditor.Taskbar = Taskbar;
		window.BXHtmlEditor.ComponentsControl = ComponentsControl;
		window.BXHtmlEditor.ContextMenu = ContextMenu;
		window.BXHtmlEditor.Dialog = Dialog;
		window.BXHtmlEditor.Toolbar = Toolbar;
		window.BXHtmlEditor.NodeNavigator = NodeNavi;
		window.BXHtmlEditor.Button = Button;
		window.BXHtmlEditor.Overlay = Overlay;

		window.BXHtmlEditor.Controls = {
			SearchButton: SearchButton,
			ChangeView: ChangeView,
			Undo: UndoButton,
			Redo: RedoButton,
			StyleSelector: StyleSelectorList,
			FontSelector: FontSelectorList,
			FontSize: FontSizeButton,
			Bold: BoldButton,
			Italic: ItalicButton,
			Underline: UnderlineButton,
			Strikeout: StrikeoutButton,
			Color: ColorPicker,
			RemoveFormat: RemoveFormatButton,
			TemplateSelector: TemplateSelectorList,
			OrderedList: OrderedListButton,
			UnorderedList: UnorderedListButton,
			IndentButton: IndentButton,
			OutdentButton: OutdentButton,
			AlignList: AlignList,
			InsertLink: InsertLinkButton,
			InsertImage: InsertImageButton,
			InsertVideo: InsertVideoButton,
			InsertAnchor: InsertAnchorButton,
			InsertTable: InsertTableButton,
			InsertChar: InsertCharButton,
			Settings: SettingsButton,
			Fullscreen: FullscreenButton,
			PrintBreak: PrintBreakButton,
			PageBreak: PageBreakButton,
			InsertHr: InsertHrButton,
			More: MoreButton
		};

		window.BXHtmlEditor.dialogs = {
			Image: ImageDialog,
			Link: LinkDialog,
			Video: VideoDialog,
			Source: SourceDialog,
			Anchor: AnchorDialog,
			Table: TableDialog,
			Settings: SettingsDialog,
			Default: DefaultDialog,
			Specialchar: SpecialcharDialog
		};
	}

	if (window.BXHtmlEditor)
	{
		__run();
	}
	else
	{
		BX.addCustomEvent(window, "OnBXHtmlEditorInit", __run);
	}
})();
