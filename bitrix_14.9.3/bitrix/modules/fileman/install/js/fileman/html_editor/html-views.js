/**
 * Bitrix HTML Editor 3.0
 * Date: 24.04.13
 * Time: 4:23
 *
 * Views class
 */
(function()
{

function BXEditorView(editor, element, container)
{
	this.editor = editor;
	this.element = element;
	this.container = container;
	this.config = editor.config || {};
	this.isShown = null;
	BX.addCustomEvent(this.editor, "OnClickBefore", BX.proxy(this.OnClick, this));
}

BXEditorView.prototype = {
	Focus: function()
	{
		if (this.element.ownerDocument.querySelector(":focus") === this.element)
			return;

		try{this.element.focus();}catch(e){}
	},

	Hide: function()
	{
		this.isShown = false;
		this.container.style.display = "none";
	},

	Show: function()
	{
		this.isShown = true;
		this.container.style.display = "";
	},

	Disable: function()
	{
		this.element.setAttribute("disabled", "disabled");
	},

	Enable: function()
	{
		this.element.removeAttribute("disabled");
	},

	OnClick: function(params)
	{

	},

	IsShown: function()
	{
		return !!this.isShown;
	}
};


function BXEditorTextareaView(parent, textareaElement, container)
{
	// Call parrent constructor
	BXEditorIframeView.superclass.constructor.apply(this, arguments);
	this.name = "textarea";
	this.InitEventHandlers();
}

BX.extend(BXEditorTextareaView, BXEditorView);

BXEditorTextareaView.prototype.Clear = function()
{
	this.element.value = "";
};

BXEditorTextareaView.prototype.GetValue = function(bParse)
{
	var value = this.IsEmpty() ? "" : this.element.value;

	if (bParse)
	{
		value = this.parent.parse(value);
	}

	return value;
};

BXEditorTextareaView.prototype.SetValue = function(html, bParse, bFormat)
{
	if (bParse)
	{
		html = this.editor.Parse(html, true, bFormat);
	}
	this.editor.dom.pValueInput.value = this.element.value = html;
};


BXEditorTextareaView.prototype.SaveValue = function()
{
	this.editor.dom.pValueInput.value = this.element.value;
};

BXEditorTextareaView.prototype.HasPlaceholderSet = function()
{
	return false;
	var
		supportsPlaceholder = supportsPlaceholderAttributeOn(this.element),
		placeholderText = this.element.getAttribute("placeholder") || null,
		value = this.element.value,
		isEmpty = !value;
	return (supportsPlaceholder && isEmpty) || (value === placeholderText);
};

BXEditorTextareaView.prototype.IsEmpty = function()
{
	var value = BX.util.trim(this.element.value);
	return value === '' || this.HasPlaceholderSet();
};

BXEditorTextareaView.prototype.InitEventHandlers = function()
{
	var _this = this;
	BX.bind(this.element, "focus", function()
	{
		_this.editor.On("OnTextareaFocus");
		_this.isFocused = true;
	});

	BX.bind(this.element, "blur", function()
	{
		_this.editor.On("OnTextareaBlur");
		_this.isFocused = false;
	});

	return;

	var
		element = this.element,
		parent = this.parent,
		eventMapping = {
			focusin: "focus",
			focusout: "blur"
		},
		/**
		 * Calling focus() or blur() on an element doesn't synchronously trigger the attached focus/blur events
		 * This is the case for focusin and focusout, so let's use them whenever possible, kkthxbai
		 */
			events = supportsEvent("focusin") ? ["focusin", "focusout", "change"] : ["focus", "blur", "change"];

	parent.observe("beforeload", function()
	{
		observe(element, events, function(event) {
			var eventName = eventMapping[event.type] || event.type;
			parent.fire(eventName).fire(eventName + ":textarea");
		});

		observe(element, ["paste", "drop"], function()
		{
			setTimeout(function() { parent.fire("paste").fire("paste:textarea"); }, 0);
		});
	});
};

BXEditorTextareaView.prototype.IsFocused = function()
{
	return this.isFocused;
};

BXEditorTextareaView.prototype.ScrollToSelectedText = function(searchText)
{
// http://blog.blupixelit.eu/scroll-textarea-to-selected-word-using-javascript-jquery/
//	var parola_cercata = "parola"; // the searched word
//	var posi = jQuery('#my_textarea').val().indexOf(parola_cercata); // take the position of the word in the text
//	if (posi != -1) {
//		var target = document.getElementById("my_textarea");
//		// select the textarea and the word
//		target.focus();
//		if (target.setSelectionRange)
//			target.setSelectionRange(posi, posi+parola_cercata.length);
//		else {
//			var r = target.createTextRange();
//			r.collapse(true);
//			r.moveEnd('character',  posi+parola_cercata);
//			r.moveStart('character', posi);
//			r.select();
//		}
//		var objDiv = document.getElementById("my_textarea");
//		var sh = objDiv.scrollHeight; //height in pixel of the textarea (n_rows*line_height)
//		var line_ht = jQuery('#my_textarea').css('line-height').replace('px',''); //height in pixel of each row
//		var n_lines = sh/line_ht; // the total amount of lines
//		var char_in_line = jQuery('#insert_textarea').val().length / n_lines; // amount of chars for each line
//		var height = Math.floor(posi/char_in_line); // amount of lines in the textarea
//		jQuery('#my_textarea').scrollTop(height*line_ht); // scroll to the selected line
//	} else {
//		alert('parola '+parola_cercata+' non trovata'); // alert word not found
//	}
};

BXEditorTextareaView.prototype.SelectText = function(searchText)
{
	var
		value = this.element.value,
	 	ind = value.indexOf(searchText);

	if(ind != -1)
	{
		this.element.focus();
		this.element.setSelectionRange(ind, ind + searchText.length);
	}
};


function BXEditorIframeView(editor, textarea, container)
{
	// Call parrent constructor
	BXEditorIframeView.superclass.constructor.apply(this, arguments);
	this.name = "wysiwyg";
	this.caretNode = "<br>";
}

BX.extend(BXEditorIframeView, BXEditorView);

BXEditorIframeView.prototype.OnCreateIframe = function()
{
	this.document = this.editor.sandbox.GetDocument();
	this.element = this.document.body;
	this.textarea = this.editor.dom.textarea;
	this.isFocused = false;
	this.InitEventHandlers();

	// Check and init external range library
	window.rangy.init();

	this.Enable();
};

BXEditorIframeView.prototype.Clear = function()
{
	//this.element.innerHTML = BX.browser.IsFirefox() ? this.caretNode : "";
	this.element.innerHTML = this.caretNode;
};

BXEditorIframeView.prototype.GetValue = function(bParse)
{
	var value = this.IsEmpty() ? "" : this.editor.GetInnerHtml(this.element);
	if (bParse)
	{
		value = this.editor.Parse(value);
	}
	return value;
};

BXEditorIframeView.prototype.SetValue = function(html, bParse)
{
	if (bParse)
	{
		html = this.editor.Parse(html);
	}
	this.element.innerHTML = html;
	// Check last child - if it's block node in the end - add <br> tag there
	this.CheckContentLastChild(this.element);
};

BXEditorIframeView.prototype.Show = function()
{
	this.isShown = true;
	this.container.style.display = "";
	this.ReInit();
};

BXEditorIframeView.prototype.ReInit = function()
{
	// Firefox needs this, otherwise contentEditable becomes uneditable
	this.Disable();
	this.Enable();
	this.document = this.editor.sandbox.GetDocument();
	this.editor.On('OnIframeReInit');
};

BXEditorIframeView.prototype.Hide = function()
{
	this.isShown = false;
	this.container.style.display = "none";
};

BXEditorIframeView.prototype.Disable = function()
{
	this.element.removeAttribute("contentEditable");
};

BXEditorIframeView.prototype.Enable = function()
{
	this.element.setAttribute("contentEditable", "true");
};

BXEditorIframeView.prototype.Focus = function(setToEnd)
{
	if (BX.browser.IsIE() && this.HasPlaceholderSet())
	{
		this.Clear();
	}

	if (this.element.ownerDocument.querySelector(":focus") !== this.element)
	{
		try{this.element.focus();} catch(e){}
	}

	if (setToEnd && this.element.lastChild)
	{
		if (this.element.lastChild.nodeName === "BR")
		{
			this.editor.selection.SetBefore(this.element.lastChild);
		}
		else
		{
			this.editor.selection.SetAfter(this.element.lastChild);
		}
	}
};

BXEditorIframeView.prototype.IsFocused = function()
{
	return this.isFocused;
};

BXEditorIframeView.prototype.GetTextContent = function()
{
	return this.editor.util.GetTextContent(this.element);
};

BXEditorIframeView.prototype.HasPlaceholderSet = function()
{
	return this.GetTextContent() == this.textarea.getAttribute("placeholder");
};

BXEditorIframeView.prototype.IsEmpty = function()
{
	var
		innerHTML = this.element.innerHTML,
		elementsWithVisualValue = "blockquote, ul, ol, img, embed, object, table, iframe, svg, video, audio, button, input, select, textarea";

	return innerHTML === "" ||
		innerHTML === this.caretNode ||
		this.HasPlaceholderSet() ||
		(this.GetTextContent() === "" && !this.element.querySelector(elementsWithVisualValue));
};

BXEditorIframeView.prototype._initObjectResizing = function()
{
	var properties = ["width", "height"],
		propertiesLength = properties.length,
		element = this.element;

	this.commands.exec("enableObjectResizing", this.config.allowObjectResizing);

	if (this.config.allowObjectResizing) {
		// IE sets inline styles after resizing objects
		// The following lines make sure _this the width/height css properties
		// are copied over to the width/height attributes
		if (browser.supportsEvent("resizeend")) {
			dom.observe(element, "resizeend", function(event) {
				var target = event.target || event.srcElement,
					style = target.style,
					i = 0,
					property;
				for(; i<propertiesLength; i++) {
					property = properties[i];
					if (style[property]) {
						target.setAttribute(property, parseInt(style[property], 10));
						style[property] = "";
					}
				}
				// After resizing IE sometimes forgets to remove the old resize handles
				redraw(element);
			});
		}
	} else {
		if (browser.supportsEvent("resizestart")) {
			dom.observe(element, "resizestart", function(event) { event.preventDefault(); });
		}
	}
};

/**
 * With "setActive" IE offers a smart way of focusing elements without scrolling them into view:
 * http://msdn.microsoft.com/en-us/library/ms536738(v=vs.85).aspx
 *
 * Other browsers need a more hacky way: (pssst don't tell my mama)
 * In order to prevent the element being scrolled into view when focusing it, we simply
 * move it out of the scrollable area, focus it, and reset it's position
 */

var focusWithoutScrolling = function(element)
{
	if (element.setActive) {
		// Following line could cause a js error when the textarea is invisible
		// See https://github.com/xing/wysihtml5/issues/9
		try { element.setActive(); } catch(e) {}
	} else {
		var elementStyle = element.style,
			originalScrollTop = doc.documentElement.scrollTop || doc.body.scrollTop,
			originalScrollLeft = doc.documentElement.scrollLeft || doc.body.scrollLeft,
			originalStyles = {
				position: elementStyle.position,
				top: elementStyle.top,
				left: elementStyle.left,
				WebkitUserSelect: elementStyle.WebkitUserSelect
			};

		dom.setStyles({
			position: "absolute",
			top: "-99999px",
			left: "-99999px",
			// Don't ask why but temporarily setting -webkit-user-select to none makes the whole thing performing smoother
			WebkitUserSelect: "none"
		}).on(element);

		element.focus();

		dom.setStyles(originalStyles).on(element);

		if (win.scrollTo) {
			// Some browser extensions unset this method to prevent annoyances
			// "Better PopUp Blocker" for Chrome http://code.google.com/p/betterpopupblocker/source/browse/trunk/blockStart.js#100
			// Issue: http://code.google.com/p/betterpopupblocker/issues/detail?id=1
			win.scrollTo(originalScrollLeft, originalScrollTop);
		}
	}
};


/**
 * Taking care of events
 * - Simulating 'change' event on contentEditable element
 * - Handling drag & drop logic
 * - Catch paste events
 * - Dispatch proprietary newword:composer event
 * - Keyboard shortcuts
 */

	BXEditorIframeView.prototype.InitEventHandlers = function()
	{
		var
			_this = this,
			editor = this.editor,
			value = this.GetValue(),
			element = this.element,
			_element = !BX.browser.IsOpera() ? element : this.editor.sandbox.GetWindow();

		BX.bind(_element, "focus", function()
		{
			_this.editor.On("OnIframeFocus");
			_this.isFocused = true;
			if (value !== _this.GetValue())
				BX.onCustomEvent(editor, "OnIframeChange");
		});

		BX.bind(_element, "blur", function()
		{
			_this.editor.On("OnIframeBlur");
			_this.isFocused = false;
			setTimeout(function(){value = _this.GetValue();}, 0);
		});

		BX.bind(_element, "contextmenu", function(e)
		{
			if(e && !e.ctrlKey && !e.shiftKey && (BX.getEventButton(e) & BX.MSRIGHT))
			{
				var target = e.target || e.srcElement;
				_this.editor.On("OnIframeContextMenu", [e, target]);
				return BX.PreventDefault(e);
			}
		});

		BX.bind(_element, "mousedown", function(e)
		{
			var
				target = e.target || e.srcElement,
				bxTag = _this.editor.GetBxTag(target);

			if (_this.editor.synchro.IsSyncOn())
			{
				_this.editor.synchro.StopSync();
			}

			if (BX.browser.IsIE10() || BX.browser.IsIE11())
			{
				_this.editor.phpParser.RedrawSurrogates();
			}

			if (target.nodeName == 'BODY' || !_this.editor.phpParser.CheckParentSurrogate(target))
			{
				setTimeout(function()
				{
					var range = _this.editor.selection.GetRange();
					if (range && range.collapsed && range.startContainer && range.startContainer == range.endContainer)
					{
						var surr = _this.editor.phpParser.CheckParentSurrogate(range.startContainer);
						if (surr)
						{
							_this.editor.selection.SetInvisibleTextAfterNode(surr);
							_this.editor.selection.SetInvisibleTextBeforeNode(surr);
						}
					}
				}, 10);
			}

			editor.selection.SaveRange();
			_this.editor.On("OnIframeMouseDown", [e, target, bxTag]);
		});

		BX.bind(_element, "click", function(e)
		{
			var
				target = e.target || e.srcElement;
			_this.editor.On("OnIframeClick", [e, target]);

			var selNode = _this.editor.selection.GetSelectedNode();

			//var node = _this.CheckParentSurrogate(_this.editor.selection.GetSelectedNode());
//			setTimeout(function()
//			{
//				var newSelNode = _this.editor.selection.GetSelectedNode();
//				if (selNode !== newSelNode)
//				{
//				}
//				var node = _this.CheckParentSurrogate(_this.editor.selection.GetSelectedNode());
//				if(node)
//				{
//					_this.editor.selection.SetAfter(node);
//
////					if (node.nextSibling && node.nextSibling.nodeType == 3 && _this.editor.util.IsEmptyNode(link.nextSibling))
////						invisText = link.nextSibling;
////					else
//					var invisText = _this.editor.util.GetInvisibleTextNode();
//					_this.editor.selection.InsertNode(invisText);
//					_this.editor.selection.SetAfter(invisText);
//				}
//			}, 0);
		});

		BX.bind(_element, "dblclick", function(e)
		{
			var
				target = e.target || e.srcElement;
			_this.editor.On("OnIframeDblClick", [e, target]);
		});

		BX.bind(_element, "mouseup", function(e)
		{
			var target = e.target || e.srcElement;
			if (!_this.editor.synchro.IsSyncOn())
			{
				_this.editor.synchro.StartSync();
			}

			_this.editor.On("OnIframeMouseUp", [e, target]);
		});

		// resizestart
		// resizeend

		// TODO: check it on ios
		if (BX.browser.IsIOS() && false)
		{
			// When on iPad/iPhone/IPod after clicking outside of editor, the editor loses focus
			// but the UI still acts as if the editor has focus (blinking caret and onscreen keyboard visible)
			// We prevent _this by focusing a temporary input element which immediately loses focus
			dom.observe(element, "blur", function()
			{
				var input = element.ownerDocument.createElement("input"),
					originalScrollTop = document.documentElement.scrollTop || document.body.scrollTop,
					originalScrollLeft = document.documentElement.scrollLeft || document.body.scrollLeft;
				try {
					_this.selection.insertNode(input);
				} catch(e) {
					element.appendChild(input);
				}
				input.focus();
				input.parentNode.removeChild(input);

				window.scrollTo(originalScrollLeft, originalScrollTop);
			});
		}

		// --------- Drag & Drop logic ---------
		BX.bind(element, "dragenter", function ()
		{
			//_this.parent.fire("unset_placeholder");
		});

		// Chrome & Safari & Firefox only fire the ondrop/ondragend/... events when the ondragover event is cancelled
		//if (BX.browser.IsChrome() || BX.browser.IsFirefox())
		// TODO: Truobles with firefox during selections http://jabber.bx/view.php?id=49370
		if (BX.browser.IsFirefox())
		{
			BX.bind(element, "dragover", function(e)
			{
				e.preventDefault();
			});
			BX.bind(element, "dragenter", function(e)
			{
				e.preventDefault();
			});
		}

		BX.bind(element, "drop", BX.delegate(this.OnPasteHandler, this));
		BX.bind(element, "paste", BX.delegate(this.OnPasteHandler, this));
		BX.bind(element, "keyup", function(e)
		{
			var
				keyCode = e.keyCode,
				target = editor.selection.GetSelectedNode(true);

			if (keyCode === editor.KEY_CODES['space'] || keyCode === editor.KEY_CODES['enter'])
			{
				editor.On("OnIframeNewWord");
			}
			else if (keyCode === editor.KEY_CODES['right'] || keyCode === editor.KEY_CODES['down'])
			{
				editor.selection.SetCursorAfterNode(e);
			}
			else if (keyCode === editor.KEY_CODES['left'] || keyCode === editor.KEY_CODES['up'])
			{
				editor.selection.SetCursorBeforeNode(e);
			}

			editor.selection.SaveRange();
			editor.On('OnIframeKeyup', [e, keyCode, target]);
		});

		if (!editor.util.CheckImageSelectSupport())
		{
			BX.bind(element, "mousedown", function(e)
			{
				var target = e.target || e.srcElement;
				if (target.nodeName === "IMG")
				{
					editor.selection.SelectNode(target);
				}
			});
		}

		BX.bind(element, "keydown", BX.proxy(this.KeyDown, this));

		// Show urls and srcs in tooltip when hovering links or images
		var nodeTitles = {
			IMG: BX.message.SrcTitle + ": ",
			A: BX.message.UrlTitle + ": "
		};
		BX.bind(element, "mouseover", function(e)
		{
			var
				target = e.target || e.srcElement,
				nodeName = target.nodeName;

			if (!nodeTitles[nodeName])
			{
				return;
			}

			if(!target.hasAttribute("title"))
			{
				target.setAttribute("title", nodeTitles[nodeName] + (target.getAttribute("href") || target.getAttribute("src")));
				target.setAttribute("data-bx-clean-attribute", "title");
			}
		});
	};

	BXEditorIframeView.prototype.KeyDown = function(e)
	{
		var
			_this = this,
			keyCode = e.keyCode,
			KEY_CODES = this.editor.KEY_CODES,
			command = this.editor.SHORTCUTS[keyCode],
			selectedNode = this.editor.selection.GetSelectedNode(true),
			range = this.editor.selection.GetRange(),
			parent;

		if ((BX.browser.IsIE() || BX.browser.IsIE10() || BX.browser.IsIE11()) &&
			!BX.util.in_array(keyCode, [16, 17, 18, 20, 65, 144, 37, 38, 39, 40]))
		{
			var body = this.document.body;
			if (selectedNode && selectedNode.nodeName == "BODY"
				||
				range.startContainer && range.startContainer.nodeName == "BODY"
				||
				(range.startContainer == body.firstChild &&
				range.endContainer == body.lastChild &&
				range.startOffset == 0 &&
				range.endOffset == body.lastChild.length))
			{
				BX.addCustomEvent(this.editor, "OnIframeKeyup", BX.proxy(this._IEBodyClearHandler, this));
			}
		}

		this.isUserTyping = true;
		if (this.typingTimeout)
		{
			this.typingTimeout = clearTimeout(this.typingTimeout);
		}
		this.typingTimeout = setTimeout(function()
		{
			_this.isUserTyping = false;
		}, 1000);

		this.editor.synchro.StartSync(200);

		this.editor.On('OnIframeKeydown', [e, keyCode, command, selectedNode]);

		// Handle  Shortcuts
		if ((e.ctrlKey || e.metaKey) && !e.altKey && command)
		{
			this.editor.action.Exec(command);
			return BX.PreventDefault(e);
		}

		// Clear link with image
		if (selectedNode && selectedNode.nodeName === "IMG" &&
			(keyCode === KEY_CODES['backspace'] || keyCode === KEY_CODES['delete']))
		{
			parent = selectedNode.parentNode;
			parent.removeChild(selectedNode); // delete image

			// Parent - is LINK, and it's hasn't got any other childs
			if (parent.nodeName === "A" && !parent.firstChild)
			{
				parent.parentNode.removeChild(parent);
			}

			setTimeout(function(){_this.editor.util.Refresh(_this.element);}, 0);
			BX.PreventDefault(e);
		}

		// Handle "Enter"
		if (!e.shiftKey && (keyCode === KEY_CODES["enter"] || keyCode === KEY_CODES["backspace"]))
		{
			return this.OnEnterHandler(e, keyCode, selectedNode);
		}
	};

	BXEditorIframeView.prototype._IEBodyClearHandler = function(e)
	{
		var
			_this = this,
			p = this.document.body.firstChild;

		if (e.keyCode == this.editor.KEY_CODES['enter'] && p.nodeName == "P" && p != this.document.body.lastChild)
		{
			if (p.innerHTML && p.innerHTML.toLowerCase() == '<br>')
			{
				var newPar = p.nextSibling;
				this.editor.util.ReplaceWithOwnChildren(p);
				p = newPar;
			}
		}

		if (p && p.nodeName == "P" && p == this.document.body.lastChild)
		{
			_this.editor.util.ReplaceWithOwnChildren(p);
		}
		BX.removeCustomEvent(this.editor, "OnIframeKeyup", BX.proxy(this._IEBodyClearHandler, this));
	};

	BXEditorIframeView.prototype.OnEnterHandler = function(e, keyCode, selectedNode)
	{
		// Check selectedNode
		if (!selectedNode)
		{
			return;
		}

		var _this = this;
		function unwrap(node)
		{
			if (node)
			{
				if (node.nodeName !== "P" && node.nodeName !== "DIV")
				{
					node = BX.findParent(node, function(n)
					{
						return n.nodeName === "P" || n.nodeName === "DIV";
					}, _this.document.body);
				}

				var emptyNode = _this.editor.util.GetInvisibleTextNode();
				if (node)
				{
					node.parentNode.insertBefore(emptyNode, node);
					_this.editor.util.ReplaceWithOwnChildren(node);
					_this.editor.selection.SelectNode(emptyNode);
				}
			}
		}

		var
			list, i, li, br, blockElement,
			blockTags  = ["LI", "P", "H1", "H2", "H3", "H4", "H5", "H6"],
			listTags  = ["UL", "OL", "MENU"];


		// We trying to exit from list (double enter) (only in Chrome)
		if (BX.browser.IsChrome() &&
			selectedNode.nodeName === "LI" &&
			selectedNode.childNodes.length == 1 &&
			selectedNode.firstChild.nodeName === "BR")
		{
			// 1. Get parrent list
			list = BX.findParent(selectedNode, function(n)
			{
				return BX.util.in_array(n.nodeName, listTags);
			}, _this.document.body);

			li = list.getElementsByTagName('LI');

			// Last item - we have to exit from list and insert <br>
			if (selectedNode === li[li.length - 1])
			{
				br = list.ownerDocument.createElement("BR");
				if (list.nextSibling)
				{
					list.parentNode.insertBefore(list.nextSibling, br);
				}
				else
				{
					list.parentNode.appendChild(br);
				}

				this.editor.selection.SetAfter(br);
				this.editor.Focus();
				BX.remove(selectedNode);

			}
			else // We have to split list into 2 lists
			{
				var
					newList = list.ownerDocument.createElement(list.nodeName),
					toNew = false,
					invisText = _this.editor.util.GetInvisibleTextNode();

				for (i = 0; i < li.length; i++)
				{
					if (li[i] == selectedNode)
					{
						toNew = true;
						continue;
					}

					if (toNew)
					{
						newList.appendChild(li[i]);
					}
				}

				if (list.nextSibling)
				{
					list.parentNode.insertBefore(list.nextSibling, invisText);
					invisText.parentNode.insertBefore(invisText.nextSibling, newList);
				}
				else
				{
					list.parentNode.appendChild(invisText);
					list.parentNode.appendChild(newList);
				}
				this.editor.selection.SetAfter(invisText);

				this.editor.Focus();
				BX.remove(selectedNode);
			}

			return BX.PreventDefault(e);
		}
		else
		{
			if (BX.util.in_array(selectedNode.nodeName, blockTags))
			{
				blockElement = selectedNode;
			}
			else
			{
				blockElement = BX.findParent(selectedNode, function(n)
				{
					return BX.util.in_array(n.nodeName, blockTags);
				}, this.document.body);
			}

			if (blockElement)
			{
				// Some browsers create <p> elements after leaving a list
				// check after keydown of backspace and return whether a <p> got inserted and unwrap it
				if (blockElement.nodeName === "LI")
				{
					setTimeout(function()
					{
						var node = _this.editor.selection.GetSelectedNode(true);
						if (node)
						{
							list = BX.findParent(node, function(n)
							{
								return BX.util.in_array(n.nodeName, listTags);
							}, _this.document.body);

							if (!list)
							{
								unwrap(node);
							}
						}
					}, 0);
				}
				else if (blockElement.nodeName.match(/H[1-6]/) && keyCode === this.editor.KEY_CODES["enter"])
				{
					setTimeout(function()
					{
						unwrap(_this.editor.selection.GetSelectedNode());
					}, 0);
				}
				return true;
			}

			if (keyCode === this.editor.KEY_CODES["enter"] && !BX.browser.IsFirefox() && this.editor.action.IsSupported('insertLineBreak'))
			{
				if (BX.browser.IsIE10() || BX.browser.IsIE11())
				{
					this.editor.action.Exec('insertHTML', '<br>' + this.editor.INVISIBLE_SPACE);
				}
				else
				{
					this.editor.action.Exec('insertLineBreak');
				}
				return BX.PreventDefault(e);
			}
		}
	}

	BXEditorIframeView.prototype.OnPasteHandler = function(e)
	{
		if (this.editor.skipPasteHandler !== false)
		{
			var
				_this = this,
				arNodes = [],
				curNode, i, node, qnodes,
				range = this.editor.selection.GetRange();

			function markGoodNode(n)
			{
				if (n && n.setAttribute)
				{
					n.setAttribute('data-bx-paste-flag', 'Y');
				}
			}

			function getElementParent(n)
			{
				return n.nodeType == 1 ? n : BX.findParent(n, function(node)
				{
					return node.nodeType == 1;
				});
			}

			if (range)
			{
				if (range.collapsed)
				{
					curNode = getElementParent(this.editor.selection.GetSelectedNode());
				}
				else
				{
					curNode = this.document.body;
				}

				if (curNode)
				{
					qnodes = curNode.querySelectorAll("*");
					for (i = 0; i < qnodes.length; i++)
					{
						if (qnodes[i].nodeType == 1)
							arNodes.push(qnodes[i]);
					}

					if (curNode.nodeName != 'BODY')
					{
						for (i = 0; i < curNode.parentNode.childNodes.length; i++)
						{
							node = curNode.parentNode.childNodes[i];
							if (node.nodeType == 1)
							{
								arNodes.push(node);
							}
						}
					}
				}
			}

			for (i = 0; i < arNodes.length; i++)
			{
				markGoodNode(arNodes[i]);
			}

			setTimeout(function()
			{
				_this.editor.pasteHandleMode = true;
				_this.editor.synchro.FullSyncFromIframe();
				_this.editor.pasteHandleMode = false;
				_this.editor.On("OnIframePaste");
				_this.editor.On("OnIframeNewWord");
			}, 10);
		}
	};

	BXEditorIframeView.prototype.InitAutoLinking = function()
	{
		var
			_this = this,
			editor = this.editor,
			nativeAutolinkCanBeDisabled = editor.action.IsSupportedByBrowser("autoUrlDetect"),
			nativeAutoLink = BX.browser.IsIE() || BX.browser.IsIE9() || BX.browser.IsIE10();

		if (nativeAutolinkCanBeDisabled)
			editor.action.Exec("autoUrlDetect", false);

		if (editor.config.autoLink === false)
			return;

		// Init Autolink system
		var
			ignorableParents = {"CODE" : 1, "PRE" : 1, "A" : 1, "SCRIPT" : 1, "HEAD" : 1, "TITLE" : 1, "STYLE" : 1},
			urlRegExp = /(((?:https?|ftp):\/\/|www\.)[^\s<]{3,})/gi,
			emailRegExp = /.+@.+\..+/gi,
			MAX_LENGTH = 100,
			BRACKETS = {
				")": "(",
				"]": "[",
				"}": "{"
			};
		this.editor.autolinkUrlRegExp = urlRegExp;
		this.editor.autolinkEmailRegExp = emailRegExp;

		function autoLink(element)
		{
			if (element && !ignorableParents[element.nodeName])
			{
				var ignorableParent = BX.findParent(element, function(node)
				{
					return !!ignorableParents[node.nodeName];
				}, element.ownerDocument.body);

				if (ignorableParent)
					return element;

				if (element === element.ownerDocument.documentElement)
					element = element.ownerDocument.body;

				return parseNode(element);
			}
		}

		function convertUrlToLink(str)
		{
			return str.replace(urlRegExp, function(match, url)
			{
				var
					punctuation = (url.match(/([^\w\/\-](,?))$/i) || [])[1] || "",
					opening = BRACKETS[punctuation];

				url = url.replace(/([^\w\/\-](,?))$/i, "");

				if (url.split(opening).length > url.split(punctuation).length)
				{
					url = url + punctuation;
					punctuation = "";
				}

				var
					realUrl = url,
					displayUrl = url;

				if (url.length > MAX_LENGTH)
					displayUrl = displayUrl.substr(0, MAX_LENGTH) + "...";

				if (realUrl.substr(0, 4) === "www.")
					realUrl = "http://" + realUrl;

				return '<a href="' + realUrl + '">' + displayUrl + '</a>' + punctuation;
			});
		}

		function convertEmailToLink(str)
		{
			return str.replace(emailRegExp, function(email)
			{

				var
					punctuation = (email.match(/([^\w\/\-](,?))$/i) || [])[1] || "",
					opening = BRACKETS[punctuation];
//
				email = email.replace(/([^\w\/\-](,?))$/i, "");
//
				if (email.split(opening).length > email.split(punctuation).length)
				{
					email = email + punctuation;
					punctuation = "";
				}

				var realUrl = "mailto:" + email;

				return '<a href="' + realUrl + '">' + email + '</a>' + punctuation;
			});
		}

		function getTmpDiv(doc)
		{
			var tmp = doc._bx_autolink_temp_div;
			if (!tmp)
				tmp = doc._bx_autolink_temp_div = doc.createElement("div");
			return tmp;
		}

		function parseNode(element)
		{
			var res;
			if (element && !ignorableParents[element.nodeName])
			{
				// Replaces the content of the text node by link
				if (element.nodeType === 3 && element.data.match(urlRegExp) && element.parentNode)
				{
					var
						parentNode = element.parentNode,
						tmpDiv = getTmpDiv(parentNode.ownerDocument);

					tmpDiv.innerHTML = "<span></span>" + convertUrlToLink(element.data);
					tmpDiv.removeChild(tmpDiv.firstChild);

					while (tmpDiv.firstChild)
						parentNode.insertBefore(tmpDiv.firstChild, element);

					parentNode.removeChild(element);
				}
				else if (element.nodeType === 3 && element.data.match(emailRegExp) && element.parentNode)
				{
					var
						parentNode = element.parentNode,
						tmpDiv = getTmpDiv(parentNode.ownerDocument);

					tmpDiv.innerHTML = "<span></span>" + convertEmailToLink(element.data);
					tmpDiv.removeChild(tmpDiv.firstChild);

					while (tmpDiv.firstChild)
						parentNode.insertBefore(tmpDiv.firstChild, element);

					parentNode.removeChild(element);
				}
				else if (element.nodeType === 1)
				{
					var
						childNodes = element.childNodes,
						i;

					for (i = 0; i < childNodes.length; i++)
						parseNode(childNodes[i]);

					res = element;
				}
			}
			return res;
		}

		if (!nativeAutoLink || (nativeAutoLink && nativeAutolinkCanBeDisabled))
		{
			BX.addCustomEvent(editor, "OnIframeNewWord", function()
			{
				try
				{
					editor.selection.ExecuteAndRestore(function(startContainer, endContainer)
					{
						autoLink(endContainer.parentNode);
					});
				}
				catch(e){}
			});
		}

		var
			links = editor.sandbox.GetDocument().getElementsByTagName("a"),
			getTextContent  = function(element)
			{
				var textContent = BX.util.trim(editor.util.GetTextContent(element));
				if (textContent.substr(0, 4) === "www.")
					textContent = "http://" + textContent;
				return textContent;
			};

		BX.addCustomEvent(editor, "OnIframeKeydown", function(e, keyCode, command, selectedNode)
		{
			if (links.length > 0 && selectedNode)
			{
				var link = BX.findParent(selectedNode, {tag: 'A'}, selectedNode.ownerDocument.body);
				if (link)
				{
					var textContent = getTextContent(link);
					setTimeout(function()
					{
						var newTextContent = getTextContent(link);
						if (newTextContent === textContent)
							return;

						// Only set href when new href looks like a valid url
						if (newTextContent.match(urlRegExp))
							link.setAttribute("href", newTextContent);
					}, 0);
				}
			}
		});
	};

	BXEditorIframeView.prototype.IsUserTypingNow = function(e)
	{
		return this.isFocused && this.isShown && this.isUserTyping;
	};

	BXEditorIframeView.prototype.CheckContentLastChild = function(element)
	{
		if (!element)
		{
			element = this.element;
		}

		var lastChild = element.lastChild;
		if (lastChild && (this.editor.util.IsEmptyNode(lastChild, true) && this.editor.util.IsBlockNode(lastChild.previousSibling) || this.editor.phpParser.IsSurrogate(lastChild)))
		{
			element.appendChild(BX.create('BR', {}, element.ownerDocument));
			element.appendChild(this.editor.util.GetInvisibleTextNode());
		}
	};

/**
 * Class _this takes care that the value of the composer and the textarea is always in sync
 */
	function BXEditorViewsSynchro(editor, textareaView, iframeView)
	{
		this.INTERVAL = 500;

		this.editor = editor;
		this.textareaView = textareaView;
		this.iframeView = iframeView;
		this.lastFocused = 'wysiwyg';

		this.InitEventHandlers();
	}

	/**
	 * Sync html from composer to textarea
	 * Takes care of placeholders
	 * @param {Boolean} bParseHtml Whether the html should be sanitized before inserting it into the textarea
	 */
	BXEditorViewsSynchro.prototype =
	{
		FromIframeToTextarea: function(bParseHtml, bFormat)
		{
			var value = this.iframeView.GetValue();
			if (value !== this.lastIframeValue)
			{
				value = BX.util.trim(value);
				this.textareaView.SetValue(value, true, bFormat);
				this.lastIframeValue = value;
			}
		},

		/**
		* Sync value of textarea to composer
		* Takes care of placeholders
		* @param {Boolean} bParseHtml Whether the html should be sanitized before inserting it into the composer
		*/
		FromTextareaToIframe: function(bParseHtml)
		{
			var value = this.textareaView.GetValue();
			if (value !== this.lastTextareaValue)
			{
				if (value)
				{
					this.iframeView.SetValue(value, bParseHtml);
				}
				else
				{
					this.iframeView.Clear();
				}
				this.lastTextareaValue = value;
			}
		},

		FullSyncFromIframe: function()
		{
			this.lastIframeValue = false;
			this.FromIframeToTextarea(true);
			this.lastTextareaValue = false;
			this.FromTextareaToIframe(true);
		},

		Sync: function()
		{
			var bParseHtml = true;
			var view = this.editor.currentViewName;

			if (view === "split")
			{
				if (this.GetSplitMode() === "code")
				{
					this.FromTextareaToIframe(bParseHtml);
				}
				else // wysiwyg
				{
					this.FromIframeToTextarea(bParseHtml);
				}
			}
			else if (view === "code")
			{
				this.FromTextareaToIframe(bParseHtml);
			}
			else // wysiwyg
			{
				this.FromIframeToTextarea(bParseHtml);
			}
		},

		GetSplitMode: function()
		{
			var mode = false;
			if (this.editor.currentViewName == "split")
			{
				if (this.editor.iframeView.IsFocused())
				{
					mode = "wysiwyg";
				}
				else if(this.editor.textareaView.IsFocused())
				{
					mode = "code";
				}
				else
				{
					mode = this.lastFocused;
				}
			}
			return mode;
		},

		InitEventHandlers: function()
		{
			var _this = this;
			BX.addCustomEvent(this.editor, "OnTextareaFocus", function()
			{
				_this.lastFocused = 'code';
				_this.StartSync();
			});
			BX.addCustomEvent(this.editor, "OnIframeFocus", function()
			{
				_this.lastFocused = 'wysiwyg';
				_this.StartSync();
			});

			BX.addCustomEvent(this.editor, "OnTextareaBlur", BX.delegate(this.StopSync, this));
			BX.addCustomEvent(this.editor, "OnIframeBlur", BX.delegate(this.StopSync, this));

			//BX.addCustomEvent(this.editor, "OnIframeMouseDown", BX.proxy(this.OnIframeMousedown, this));
			//this.On('OnSetViewAfter');
		},

		StartSync: function(delay)
		{
			var _this = this;

			if (this.interval)
			{
				this.interval = clearTimeout(this.interval);
			}

			this.delay = delay || this.INTERVAL; // it can reduce or increase initial timeout
			function sync()
			{
				// set delay to normal value
				_this.delay = _this.INTERVAL;
				_this.Sync();
				_this.interval = setTimeout(sync, _this.delay);
			}
			this.interval = setTimeout(sync, _this.delay);
		},

		StopSync: function()
		{
			if (this.interval)
			{
				this.interval = clearTimeout(this.interval);
			}
		},

		IsSyncOn: function()
		{
			return !!this.interval;
		},

		OnIframeMousedown: function(e, target, bxTag)
		{
			//var caret = this.editor.iframeView.document.createTextNode(this.INVISIBLE_CURSOR);
			//this.editor.selection.InsertNode(caret);
//			target.setAttribute('data-svd', "svd");
//			var _this = this;
//			setTimeout(function(){
//				_this.textareaView.SelectText('data-svd="svd"');
//			}, 1000);
		}
	}

	// global interface
	top.BXEditorTextareaView = window.BXEditorTextareaView = BXEditorTextareaView;
	top.BXEditorIframeView = window.BXEditorIframeView = BXEditorIframeView;
	top.BXEditorViewsSynchro = window.BXEditorViewsSynchro = BXEditorViewsSynchro;
})();