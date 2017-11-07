/**
 * Bitrix HTML Editor 3.0
 * Date: 24.04.13
 * Time: 4:23
 *
 * Parser class
 */


/**
 * HTML Sanitizer
 * Rewrites the HTML based on given rules
 *
 * @param {Element|String} elementOrHtml HTML String to be sanitized OR element whose content should be sanitized
 * @param {Object} [rules] List of rules for rewriting the HTML, if there's no rule for an element it will
 * be converted to a "span". Each rule is a key/value pair where key is the tag to convert, and value the
 * desired substitution.
 * @param {Object} context Document object in which to parse the html, needed to sandbox the parsing
 *
 * @return {Element|String} Depends on the elementOrHtml parameter. When html then the sanitized html as string elsewise the element.
 *
 * @example
 * var userHTML = '<div id="foo" onclick="alert(1);"><p><font color="red">foo</font><script>alert(1);</script></p></div>';
 * wysihtml5.dom.parse(userHTML, {
 * tags {
 * p: "div", // Rename p tags to div tags
 * font: "span" // Rename font tags to span tags
 * div: true, // Keep them, also possible (same result when passing: "div" or true)
 * script: undefined // Remove script elements
 * }
 * });
 * // => <div><div><span>foo bar</span></div></div>
 *
 * var userHTML = '<table><tbody><tr><td>I'm a table!</td></tr></tbody></table>';
 * wysihtml5.dom.parse(userHTML);
 * // => '<span><span><span><span>I'm a table!</span></span></span></span>'
 *
 * var userHTML = '<div>foobar<br>foobar</div>';
 * wysihtml5.dom.parse(userHTML, {
 * tags: {
 * div: undefined,
 * br: true
 * }
 * });
 * // => ''
 *
 * var userHTML = '<div class="red">foo</div><div class="pink">bar</div>';
 * wysihtml5.dom.parse(userHTML, {
 * classes: {
 * red: 1,
 * green: 1
 * },
 * tags: {
 * div: {
 * rename_tag: "p"
 * }
 * }
 * });
 * // => '<p class="red">foo</p><p>bar</p>'
 */


(function()
{
	function BXEditorParser(editor)
	{
		this.editor = editor;

		// Rename unknown tags to this
		this.DEFAULT_NODE_NAME = "span",
		this.WHITE_SPACE_REG_EXP = /\s+/,
		this.defaultRules = {
			tags: {},
			classes: {}
		};
		this.convertedBxNodes = [];
		this.rules = {};
	}

	BXEditorParser.prototype = {
		/**
		 * Iterates over all childs of the element, recreates them, appends them into a document fragment
		 * which later replaces the entire body content
		 */
		Parse: function(content, rules, doc, cleanUp, parseBx)
		{
			if (!doc)
			{
				doc = document;
			}

			this.convertedBxNodes = [];

			var
				frag = doc.createDocumentFragment(),
				el = this.GetAsDomElement(content, doc),
				newNode, addInvisibleNodes,
				firstChild;

			this.SetParseBxMode(parseBx);

			while (el.firstChild)
			{
				firstChild = el.firstChild;
				el.removeChild(firstChild);
				newNode = this.Convert(firstChild, cleanUp, parseBx);

				if (newNode)
				{
					addInvisibleNodes = !parseBx && this.CheckBlockNode(newNode);


					if (addInvisibleNodes)
					{
						frag.appendChild(this.editor.util.GetInvisibleTextNode());
					}

					frag.appendChild(newNode);

					if (addInvisibleNodes)
						frag.appendChild(this.editor.util.GetInvisibleTextNode());
				}
			}

			// Clear element contents
			el.innerHTML = "";

			// Insert new DOM tree
			el.appendChild(frag);

			content = this.editor.GetInnerHtml(el);

			content = this.RegexpContentParse(content, parseBx);

			return content;
		},

		SetParseBxMode: function(bParseBx)
		{
			this.bParseBx = !!bParseBx;
		},

		// here we can parse content as string, not as DOM
		CodeParse: function(content)
		{
			return content;
		},

		GetAsDomElement: function(html, doc)
		{
			if (!doc)
				doc = document;

			var el = doc.createElement("div");

			if (typeof(html) === "object" && html.nodeType)
			{
				el.appendChild(html);
			}
			else if (this.editor.util.CheckHTML5Support())
			{
				el.innerHTML = html;
			}
			else if (this.editor.util.CheckHTML5FullSupport())
			{
				el.style.display = "none";
				doc.body.appendChild(el);
				try {
					el.innerHTML = html;
				} catch(e) {}
				doc.body.removeChild(el);
			}
			return el;
		},

		Convert: function(oldNode, cleanUp, parseBx)
		{
			var
				bCleanNodeAfterPaste = false,
				oldNodeType = oldNode.nodeType,
				oldChilds = oldNode.childNodes,
				newNode,
				newChild,
				i, bxTag;

			if (oldNodeType == 1)
			{
				if (this.editor.pasteHandleMode && parseBx)
				{
					bCleanNodeAfterPaste = !oldNode.getAttribute('data-bx-paste-flag');

					if (oldNode && oldNode.id)
					{
						bxTag = this.editor.GetBxTag(oldNode.id);
						if (bxTag.tag)
						{
							bCleanNodeAfterPaste = false;
						}
					}

					if (bCleanNodeAfterPaste)
					{
						oldNode = this.CleanNodeAfterPaste(oldNode);
						if (!oldNode)
						{
							return null;
						}
						oldChilds = oldNode.childNodes;
						oldNodeType = oldNode.nodeType;
					}
					oldNode.removeAttribute('data-bx-paste-flag');
				}

				// Doublecheck nodetype
				if (oldNodeType == 1)
				{
					if (!oldNode.__bxparsed)
					{
						if (this.IsAnchor(oldNode))
						{
							oldNode = this.editor.phpParser.GetSurrogateNode("anchor", BX.message('BXEdAnchor') + ": #" + oldNode.name, null, {
								html: oldNode.innerHTML,
								name: oldNode.name
							});
						}
						else if(this.IsPrintBreak(oldNode))
						{
							oldNode = this.GetPrintBreakSurrogate(oldNode);
						}

						if (oldNode && oldNode.id)
						{
							bxTag = this.editor.GetBxTag(oldNode.id);
							if(bxTag.tag)
							{
								oldNode.__bxparsed = 1;
								// We've found bitrix-made node
								if (this.bParseBx)
								{
									newNode = oldNode.ownerDocument.createTextNode('~' + bxTag.id + '~');
									this.convertedBxNodes.push(bxTag);
								}
								else
								{
									newNode = oldNode.cloneNode(true);
								}
								return newNode;
							}
						}

						if (!newNode && oldNode.nodeType)
						{
							newNode = this.ConvertElement(oldNode);
						}
					}
				}
			}
			else if (oldNodeType == 3)
			{
				newNode = this.HandleText(oldNode);
			}

			if (!newNode)
			{
				return null;
			}

			for (i = 0; i < oldChilds.length; i++)
			{
				newChild = this.Convert(oldChilds[i], cleanUp, parseBx);
				if (newChild)
				{
					newNode.appendChild(newChild);
				}
			}

			if (newNode.nodeType == 1)
			{
				// Cleanup style="" attribute for elements
				if (newNode.style && BX.util.trim(newNode.style.cssText) == '' && newNode.removeAttribute)
				{
					newNode.removeAttribute('style');
				}

				// Cleanup senseless <span> elements
				if (cleanUp && newNode.childNodes.length <= 1 && newNode.nodeName.toLowerCase() === this.DEFAULT_NODE_NAME && !newNode.attributes.length)
				{
					return newNode.firstChild;
				}
			}

			return newNode;
		},

		ConvertElement: function(oldNode)
		{
			var
				rule,
				newNode,
				new_rule,
				tagRules = this.editor.GetParseRules().tags,
				nodeName = oldNode.nodeName.toLowerCase(),
				scopeName = oldNode.scopeName;

			// We already parsed this element ignore it!
			if (oldNode.__bxparsed)
			{
				return null;
			}

			oldNode.__bxparsed = 1;

			if (oldNode.className === "bx-editor-temp")
			{
				return null;
			}

			if (scopeName && scopeName != "HTML")
			{
				nodeName = scopeName + ":" + nodeName;
			}

			/**
			 * Repair node
			 * IE is a bit bitchy when it comes to invalid nested markup which includes unclosed tags
			 * A <p> doesn't need to be closed according HTML4-5 spec, we simply replace it with a <div> to preserve its content and layout
			 */
			if (
				"outerHTML" in oldNode &&
					!this.editor.util.AutoCloseTagSupported() &&
					oldNode.nodeName === "P" &&
					oldNode.outerHTML.slice(-4).toLowerCase() !== "</p>")
			{
				nodeName = "div";
			}

			// Add "data-bx-no-border"="Y" for tables without borders
			if (nodeName == "table" && !this.bParseBx)
			{
				var border = parseInt(oldNode.getAttribute('border'), 10);
				if (!border)
				{
					oldNode.removeAttribute("border");
					oldNode.setAttribute("data-bx-no-border", "Y");
				}
			}

			if (nodeName in tagRules)
			{
				rule = tagRules[nodeName];
				if (!rule || rule.remove)
				{
					return null;
				}

				if (rule.clean_empty &&
					// Only empty node
					(oldNode.innerHTML === "" || oldNode.innerHTML === this.editor.INVISIBLE_SPACE)
					&&
					(!oldNode.className || oldNode.className == "")
					&&
					// We check lastCreatedId to prevent cleaning elements which just were created
					(!this.editor.lastCreatedId || this.editor.lastCreatedId != oldNode.getAttribute('data-bx-last-created-id'))
					)
				{
					return null;
				}

				rule = typeof(rule) === "string" ? {rename_tag: rule} : rule;

				// New rule can be applied throw the attribute 'data-bx-new-rule'
				new_rule = oldNode.getAttribute('data-bx-new-rule');
				if (new_rule)
				{
					rule[new_rule] = oldNode.getAttribute('data-bx-' + new_rule);
				}
			}
			else if (oldNode.firstChild)
			{
				rule = {rename_tag: this.DEFAULT_NODE_NAME};
			}
			else
			{
				// Remove empty unknown elements
				return null;
			}

			if (rule.replace_with_children)
			{
				newNode = oldNode.ownerDocument.createDocumentFragment();
			}
			else
			{
				newNode = oldNode.ownerDocument.createElement(rule.rename_tag || nodeName);
				this.HandleAttributes(oldNode, newNode, rule);
			}

			if (new_rule)
			{
				rule[new_rule] = null;
				delete rule[new_rule];
			}

			oldNode = null;
			return newNode;
		},

		CleanNodeAfterPaste: function(oldNode)
		{
			var
				styleName, styleValue, name, i,
				nodeName = oldNode.nodeName,
				whiteAttributes = {align: 1, alt: 1, bgcolor: 1, border: 1, cellpadding: 1, cellspacing: 1, color:1, colspan:1, height: 1, href: 1, rowspan: 1, size: 1, span: 1, src: 1, style: 1, target: 1, title: 1, type: 1, value: 1, width: 1},
				cleanEmpty = {"A": 1, "SPAN": 1, "B": 1, "STRONG": 1, "I": 1, "EM": 1, "U": 1, "DEL": 1, "S": 1, "STRIKE": 1, "H1": 1, "H2": 1, "H3": 1, "H4": 1, "H5": 1, "H6": 1, "SPAN": 1},
				whiteCssList = {
					'background-color': 'transparent',
					'background-image': 1,
					'background-position': 1,
					'background-repeat': 1,
					'background': 1,
					'border-collapse': 1,
					'border-color': 1,
					'border-style': 1,
					'border-top': 1,
					'border-right': 1,
					'border-bottom': 1,
					'border-left': 1,
					'border-top-color': 1,
					'border-right-color': 1,
					'border-bottom-color': 1,
					'border-left-color': 1,
					'border-top-style': 1,
					'border-right-style': 1,
					'border-bottom-style': 1,
					'border-left-style': 1,
					'border-top-width': 1,
					'border-right-width': 1,
					'border-bottom-width': 1,
					'border-left-width': 1,
					'border-width': 1,
					'border': 1,
					'color': '#000000',
					//'font-size': 1,
					'font-style': 'normal',
					'font-weight': 'normal',
					'text-decoration': 'none',
					'height': 1,
					'width': 1
				};

			// Clean items with display: none
			if (oldNode.style.display == 'none' || oldNode.style.visibility == 'hidden')
			{
				return null;
			}

			// Clean empty nodes
			if (cleanEmpty[nodeName] &&  (BX.util.trim(oldNode.innerHTML) == ''))
			{
				return null;
			}

			// Clean anchors
			if (nodeName == 'A' && (BX.util.trim(oldNode.innerHTML) == '' || BX.util.trim(oldNode.innerHTML) == '&nbsp;'))
			{
				return null;
			}

			// Clean class
			oldNode.removeAttribute('class');
			oldNode.removeAttribute('id');

			// Clean attributes corresponding to white list from above
			i = 0;
			while (i < oldNode.attributes.length)
			{
				name = oldNode.attributes[i].name;
				if (!whiteAttributes[name])
				{
					oldNode.removeAttribute(name);
				}
				else
				{
					i++;
				}
			}

			// Clean pasted div's
			if (nodeName == 'DIV' || oldNode.style.display == 'block')
			{
				oldNode.appendChild(oldNode.ownerDocument.createElement("BR")).setAttribute('data-bx-paste-flag', 'Y');
				oldNode.setAttribute('data-bx-new-rule', 'replace_with_children');
				oldNode.setAttribute('data-bx-replace_with_children', '1');
			}

			// Content pastet from google docs sometimes comes with unused <b style="font-weight: normal"> wrapping
			if (nodeName == 'B' && oldNode.style.fontWeight == 'normal')
			{
				oldNode.setAttribute('data-bx-new-rule', 'replace_with_children');
				oldNode.setAttribute('data-bx-replace_with_children', '1');
			}

			var styles, j;
			// Clean style
			if (oldNode.style && BX.util.trim(oldNode.style.cssText) != '')
			{
				i = 0;
				styles = [];
				while (i < oldNode.style.length)
				{
					styleName = oldNode.style[i];
					styleValue = oldNode.style.getPropertyValue(styleName);

					if (!whiteCssList[styleName] || styleValue == whiteCssList[styleName])
					{
						oldNode.style.removeProperty(styleName);
						continue;
					}

					// Clean colors like rgb(0,0,0)
					if (styleName.indexOf('color') !== -1)
					{
						styleValue = this.editor.util.RgbToHex(styleValue);
						if (styleValue == whiteCssList[styleName] || styleValue == 'transparent')
						{
							oldNode.style.removeProperty(styleName);
							continue;
						}
					}

					// Clean hidden borders, for example: border-top: medium none;
					if (styleName.indexOf('border') !== -1 && styleValue.indexOf('none') !== -1)
					{
						oldNode.style.removeProperty(styleName);
						continue;
					}

					styles.push({name: styleName, value: styleValue});
					i++;
				}

				oldNode.removeAttribute('style');
				if (styles.length > 0)
				{
					for (j = 0; j < styles.length; j++)
					{
						oldNode.style[styles[j].name] = styles[j].value;
					}
				}
			}

			// Clear useless spans
			if (nodeName == 'SPAN' && oldNode.style.cssText == '')
			{
				oldNode.setAttribute('data-bx-new-rule', 'replace_with_children');
				oldNode.setAttribute('data-bx-replace_with_children', '1');
			}

			// Replace <p>&nbsp;</p> ==> <p> </p>, <span>&nbsp;</span> ==> <span> </span>
			if ((nodeName == 'P' || nodeName == 'SPAN' || nodeName == 'FONT') && BX.util.trim(oldNode.innerHTML) == "&nbsp;")
			{
				oldNode.innerHTML = ' ';
			}

			return oldNode;
		},

		HandleText: function(oldNode)
		{
			return oldNode.ownerDocument.createTextNode(oldNode.data);
		},

		HandleAttributes: function(oldNode, newNode, rule)
		{
			var
				attributes = {}, // fresh new set of attributes to set on newNode
				setClass = rule.set_class, // classes to set
				addClass = rule.add_class, // add classes based on existing attributes
				addCss = rule.add_css, // add classes based on existing attributes
				setAttributes = rule.set_attributes, // attributes to set on the current node
				checkAttributes = rule.check_attributes, // check/convert values of attributes
				clearAttributes = rule.clear_attributes, // clean all unknown attributes
				allowedClasses = this.editor.GetParseRules().classes,
				i = 0,
				st,
				classes = [],
				newClasses = [],
				newUniqueClasses = [],
				oldClasses = [],
				classesLength,
				newClassesLength,
				currentClass,
				newClass,
				attribute,
				attributeName,
				newAttributeValue,
				handler;

			if (checkAttributes)
			{
				for (attributeName in checkAttributes)
				{
					handler = this.GetCheckAttributeHandler(checkAttributes[attributeName]);
					if (!handler)
						continue;

					newAttributeValue = handler(this.GetAttributeEx(oldNode, attributeName));
					if (typeof(newAttributeValue) === "string" && newAttributeValue !== '')
						attributes[attributeName] = newAttributeValue;
				}
			}

			var cleanAttribute = oldNode.getAttribute('data-bx-clean-attribute');
			if (cleanAttribute)
			{
				oldNode.removeAttribute(cleanAttribute);
				oldNode.removeAttribute('data-bx-clean-attribute');
			}

			if (!clearAttributes)
			{
				for (var i = 0; i < oldNode.attributes.length; i++)
				{
					attribute = oldNode.attributes[i];
					// clear bitrix attributes
					if (attribute.name.substr(0, 8) == 'data-bx-'
						&& attribute.name != 'data-bx-noindex'
						&& this.bParseBx)
					{
						continue;
					}
					attributes[attribute.name] = this.GetAttributeEx(oldNode, attribute.name);
				}
			}

			if (setClass)
				classes.push(setClass);

			if (addCss)
			{
				for (st in addCss)
				{
					if (addCss.hasOwnProperty(st))
						newNode.style[st] = addCss[st];
				}
			}

			// TODO: !!!
			if (addClass && false)
			{
				var addClassMethods = {
					align_img: (function() {
						var mapping = {
							left: "wysiwyg-float-left",
							right: "wysiwyg-float-right"
						};
						return function(attributeValue) {
							return mapping[String(attributeValue).toLowerCase()];
						};
					})(),

					align_text: (function() {
						var mapping = {
							left: "wysiwyg-text-align-left",
							right: "wysiwyg-text-align-right",
							center: "wysiwyg-text-align-center",
							justify: "wysiwyg-text-align-justify"
						};
						return function(attributeValue) {
							return mapping[String(attributeValue).toLowerCase()];
						};
					})(),

					clear_br: (function() {
						var mapping = {
							left: "wysiwyg-clear-left",
							right: "wysiwyg-clear-right",
							both: "wysiwyg-clear-both",
							all: "wysiwyg-clear-both"
						};
						return function(attributeValue) {
							return mapping[String(attributeValue).toLowerCase()];
						};
					})(),

					size_font: (function() {
						var mapping = {
							"1": "wysiwyg-font-size-xx-small",
							"2": "wysiwyg-font-size-small",
							"3": "wysiwyg-font-size-medium",
							"4": "wysiwyg-font-size-large",
							"5": "wysiwyg-font-size-x-large",
							"6": "wysiwyg-font-size-xx-large",
							"7": "wysiwyg-font-size-xx-large",
							"-": "wysiwyg-font-size-smaller",
							"+": "wysiwyg-font-size-larger"
						};
						return function(attributeValue) {
							return mapping[String(attributeValue).charAt(0)];
						};
					})()
				};

				for (attributeName in addClass)
				{
					handler = addClassMethods[addClass[attributeName]];
					if (!handler)
						continue;
					newClass = handler(this.GetAttributeEx(oldNode, attributeName));
					if (typeof(newClass) === "string")
						classes.push(newClass);
				}
			}

			// make sure that wysihtml5 temp class doesn't get stripped out
			allowedClasses["_wysihtml5-temp-placeholder"] = 1;

			// add old classes last
			oldClasses = oldNode.getAttribute("class");
			if (oldClasses)
				classes = classes.concat(oldClasses.split(this.WHITE_SPACE_REG_EXP));

			classesLength = classes.length;
			for (; i<classesLength; i++)
			{
				currentClass = classes[i];
				if (allowedClasses[currentClass])
					newClasses.push(currentClass);
			}

			// remove duplicate entries and preserve class specificity
			newClassesLength = newClasses.length;
			while (newClassesLength--)
			{
				currentClass = newClasses[newClassesLength];
				if (!wysihtml5.lang.array(newUniqueClasses).contains(currentClass))
					newUniqueClasses.unshift(currentClass);
			}

			if (newUniqueClasses.length)
				attributes["class"] = newUniqueClasses.join(" ");

			// set attributes on newNode
			for (attributeName in attributes)
			{
				// Setting attributes can cause a js error in IE under certain circumstances
				// eg. on a <img> under https when it's new attribute value is non-https
				// TODO: Investigate this further and check for smarter handling
				try {
					newNode.setAttribute(attributeName, attributes[attributeName]);
				} catch(e) {}
			}

			// IE8 sometimes loses the width/height attributes when those are set before the "src"
			// so we make sure to set them again
			if (attributes.src)
			{
				if (typeof(attributes.width) !== "undefined")
					newNode.setAttribute("width", attributes.width);
				if (typeof(attributes.height) !== "undefined")
					newNode.setAttribute("height", attributes.height);
			}
		},

		GetAttributeEx: function(node, attributeName)
		{
			attributeName = attributeName.toLowerCase();
			var nodeName = node.nodeName;

			if (nodeName == "IMG" && attributeName == "src" && this.IsLoadedImage(node) === true)
			{
				return node.getAttribute('src');
			}
			else if (!this.editor.util.CheckGetAttributeTruth() && "outerHTML" in node)
			{
				var
					outerHTML = node.outerHTML.toLowerCase(),
					hasAttribute = outerHTML.indexOf(" " + attributeName + "=") != -1;

				return hasAttribute ? node.getAttribute(attributeName) : null;
			}
			else
			{
				return node.getAttribute(attributeName);
			}
		},


		IsLoadedImage: function(node)
		{
			try
			{
				return node.complete && !node.mozMatchesSelector(":-moz-broken");
			}
			catch(e)
			{
				if (node.complete && node.readyState === "complete")
					return true;
			}
			return false;
		},

		GetCheckAttributeHandler: function(attrName)
		{
			var methods = this.GetCheckAttributeHandlers();
			return methods[attrName];
		},

		GetCheckAttributeHandlers: function()
		{
			return {
				url: function(attributeValue)
				{
					return attributeValue;
//					if (!attributeValue || !attributeValue.match(/^https?:\/\//i))
//						return null;
//					return attributeValue.replace(/^https?:\/\//i, function(match){return match.toLowerCase();});
				},

				alt: function(attributeValue)
				{
					if (!attributeValue)
					{
						return "";
					}
					return attributeValue.replace(/[^ a-z0-9_\-]/gi, "");
				},

				numbers: function(attributeValue)
				{
					attributeValue = (attributeValue || "").replace(/\D/g, "");
					return attributeValue || null;
				}
			};
		},

		HandleBitrixNode: function(node)
		{
			return node;
		},

		RegexpContentParse: function(content, parseBx)
		{
			// parse color inside style attributes RGB ==> HEX
			// TODO: it will cause wrong replace if rgba will be not inside style attribute...
			if (content.indexOf('rgb') !== -1)
			{
				content = content.replace(/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*(\d+))?\)/ig, function(str, h1, h2, h3, h4)
				{
					function hex(x)
					{
						return ("0" + parseInt(x).toString(16)).slice(-2);
					}
					return "#" + hex(h1) + hex(h2) + hex(h3);
				});
			}

			if (parseBx && content.indexOf('data-bx-noindex') !== -1)
			{
				content = content.replace(/(<a[\s\S]*?)data\-bx\-noindex="Y"([\s\S]*?\/a>)/ig, function(s, s1, s2)
				{
					return '<!--noindex-->' + s1 + s2 + '<!--\/noindex-->';
				});
			}

			// Clean invisible spaces this.INVISIBLE_SPACE
			if (parseBx)
			{
				content = content.replace(/\uFEFF/ig, '');
			}
			else
			{
				content = content.replace(/\uFEFF+/ig, this.editor.INVISIBLE_SPACE);
			}

			return content;
		},

		IsAnchor: function(n)
		{
			return n.nodeName == 'A' && !n.href;
		},

		IsPrintBreak: function(n)
		{
			return n.style.pageBreakAfter == 'always';
		},

		GetPrintBreakSurrogate: function(node)
		{
			var
				doc = this.editor.GetIframeDoc(),
				id = this.editor.SetBxTag(false, {tag: 'printbreak', params: {innerHTML: BX.util.trim(node.innerHTML)}, name: BX.message('BXEdPrintBreakName'), title: BX.message('BXEdPrintBreakTitle')});

			return BX.create('IMG', {props: {src: this.editor.EMPTY_IMAGE_SRC, id: id,className: "bxhtmled-printbreak", title: BX.message('BXEdPrintBreakTitle')}}, doc);
		},

		CheckBlockNode: function(node)
		{
			return this.editor.phpParser.IsSurrogate(node) ||
				(node.nodeType == 1 &&
					(
						node.style.display == 'block' || node.style.display == 'inline-block' ||
						node.nodeName == 'BLOCKQUOTE' || node.nodeName == 'DIV'
					)
				);
		}
	};


	function BXEditorPhpParser(editor)
	{
		this.PHP_PATTERN = '#BXPHP_IND#';
		this.editor = editor;

		this.allowed = {
			php: this.editor.allowPhp || this.editor.lpa,
			javascript: true,
			style: true,
			htmlcomment: true,
			iframe: true,
			video: true,
			'object': true
		};

		this.arScripts = {}; // object which contains all php codes with indexes
		this.arJavascripts = {}; // object which contains all javascripts codes with indexes
		this.arHtmlComments = {}; // object which contains all html comments with indexes
		this.arIframes = {}; // object which contains all iframes with indexes
		this.arVideos = {}; // object which contains all iframes with emeded videos
		this.arStyles = {}; // object which contains all <style> tags with indexes
		this.arObjects = {}; // object which contains all <object> tags with indexes
		this.surrClass = 'bxhtmled-surrogate';

		this.surrogateTags = {
			component: 1,
			php: 1,
			javascript: 1,
			style: 1,
			htmlcomment: 1,
			anchor: 1,
			iframe: 1,
			video: 1,
			'object': 1
		};

		BX.addCustomEvent(this.editor, "OnIframeMouseDown", BX.proxy(this.OnSurrogateMousedown, this));
		//BX.addCustomEvent(this.editor, "OnIframeClick", BX.proxy(this.OnSurrogateClick, this));
		BX.addCustomEvent(this.editor, "OnIframeDblClick", BX.proxy(this.OnSurrogateDblClick, this));
		BX.addCustomEvent(this.editor, "OnIframeKeydown", BX.proxy(this.OnSurrogateKeydown, this));
		BX.addCustomEvent(this.editor, "OnIframeKeyup", BX.proxy(this.OnSurrogateKeyup, this));
		BX.addCustomEvent(this.editor, "OnAfterCommandExec", BX.proxy(this.RenewSurrogates, this));
	}
	//BX.extend(BXEditorPhpParser, BXEditorParser);

	BXEditorPhpParser.prototype = {
		ParsePhp: function(content)
		{
			var _this = this;
			//1. All fragments of the php code we replace by special str - #BXPHP_IND#
			if (this.IsAllowed('php'))
			{
				content = this.ReplacePhpBySymCode(content);
			}
			else
			{
				content = this.CleanPhp(content);
			}

			// Javascript
			content = this.ReplaceJavascriptBySymCode(content);
			// Html comments
			content = this.ReplaceHtmlCommentsBySymCode(content);
			// Iframe & Video
			content = this.ReplaceIframeBySymCode(content);
			// Style
			content = this.ReplaceStyleBySymCode(content);
			// Object && embed
			content = this.ReplaceObjectBySymCode(content);

			//2. We trying to resolve html tags with PHP code inside.
			content = this.AdvancedPhpParse(content);

			//3. We replace all #BXPHP_IND# and other sym codes by visual custom elements
			content = this.ParseSymCode(content);

			// 4. LPA
			if (this.editor.lpa)
			{
				content = content.replace(/#PHP(\d+)#/g, function(str)
				{
					return  _this.GetSurrogateHTML("php_protected", BX.message('BXEdPhpCode') + " *", BX.message('BXEdPhpCodeProtected'), {value : str});
				});
			}

			return content;
		},

		// Example:
		// <?...?> => #BXPHP0#
		ReplacePhpBySymCode: function(content, cleanPhp)
		{
			var
				arScripts = [],
				p = 0, i,
				bSlashed,
				bInString, ch, posnext, ti, quote_ch, mm = 0;

			cleanPhp = cleanPhp === true;

			while((p = content.indexOf("<?", p)) >= 0)
			{
				mm = 0;
				i = p + 2;
				bSlashed = false;
				bInString = false;
				while(i < content.length - 1)
				{
					i++;
					ch = content.substr(i, 1);

					if(!bInString)
					{
						//if it's not comment
						if(ch == "/" && i + 1 < content.length)
						{
							//find end of php fragment php
							posnext = content.indexOf("?>", i);
							if(posnext == -1)
							{
								//if it's no close tag - so script is unfinished
								p = content.length;
								break;
							}
							posnext += 2;

							ti = 0;
							if(content.substr(i + 1, 1)=="*" && (ti = content.indexOf("*/", i + 2))>=0)
							{
								ti += 2;
							}
							else if(content.substr(i + 1, 1)=="/" && (ti = content.indexOf("\n", i + 2))>=0)
							{
								ti += 1;
							}

							if(ti>0)
							{
								//find begin - "i" and end - "ti" of comment
								// check: what is coming sooner: "END of COMMENT" or "END of SCRIPT"
								if(ti > posnext && content.substr(i + 1, 1) != "*")
								{
									//if script is finished - CUT THE SCRIPT
									arScripts.push([p, posnext, content.substr(p, posnext - p)]);
									p = posnext;
									break;
								}
								else
								{
									i = ti - 1; //End of comment come sooner
								}
							}
							continue;
						}
						if(ch == "?" && i + 1 < content.length && content.substr(i + 1, 1) == ">")
						{
							i = i + 2;
							arScripts.push([p, i, content.substr(p, i - p)]);
							p = i + 1;
							break;
						}
					}

					if(bInString && ch == "\\")
					{
						bSlashed = true;
						continue;
					}

					if(ch == "\"" || ch == "'")
					{
						if(bInString)
						{
							if(!bSlashed && quote_ch == ch)
								bInString = false;
						}
						else
						{
							bInString = true;
							quote_ch = ch;
						}
					}

					bSlashed = false;
				}

				if(i >= content.length)
					break;

				p = i;
			}

			this.arScripts = {};
			if(arScripts.length > 0)
			{
				var
					newstr = "",
					plast = 0,
					arScript;

				if (cleanPhp)
				{
					for(i = 0; i < arScripts.length; i++)
					{
						arScript = arScripts[i];
						newstr += content.substr(plast, arScript[0] - plast);
						plast = arScript[1];
					}
				}
				else
				{
					for(i = 0; i < arScripts.length; i++)
					{
						arScript = arScripts[i];
						newstr += content.substr(plast, arScript[0] - plast) + this.SavePhpCode(arScript[2], i);
						plast = arScript[1];
					}
				}

				content = newstr + content.substr(plast);
			}

			return content;
		},

		CleanPhp: function(content)
		{
			return this.ReplacePhpBySymCode(content, true);
		},

		// Example: <script>...</script> => #BXJAVASCRIPT_1#
		ReplaceJavascriptBySymCode: function(content)
		{
			this.arJavascripts = {};
			var
				_this = this,
				index = 0;

			content = content.replace(/<script[\s\S]*?\/script>/gi, function(s)
				{
					_this.arJavascripts[index] = s;
					var code = _this.GetPattern(index, false, 'javascript');
					index++;
					return code;
				}
			);
			return content;
		},

		// Example: <!-- --> => #BXHTMLCOMMENT_1#
		ReplaceHtmlCommentsBySymCode: function(content)
		{
			this.arHtmlComments = {};
			var
				_this = this,
				index = 0;

			content = content.replace(/(<!--noindex-->)(?:[\s|\n|\r|\t]*?)<a([\s\S]*?)\/a>(?:[\s|\n|\r|\t]*?)(<!--\/noindex-->)/ig, function(s, s1, s2, s3)
				{
					return '<a data-bx-noindex="Y"' + s2 + '/a>';
				}
			);

			content = content.replace(/<!--[\s\S]*?-->/ig, function(s)
				{
					_this.arHtmlComments[index] = s;
					return _this.GetPattern(index++, false, 'html_comment');
				}
			);
			return content;
		},

		// Example: <iframe src="...."></iframe> => #BXIFRAME_0#
		// Also looking for embeded video
		ReplaceIframeBySymCode: function(content)
		{
			this.arIframes = {};
			var
				_this = this,
				index = 0;
			content = content.replace(/<iframe([\s\S]*?)\/iframe>/gi, function(s, s1)
				{
					var video = _this.CheckForVideo(s1);
					if (video)
					{
						_this.arVideos[index] = {
							html: s,
							provider: video.provider || false,
							src:  video.src || false
						};
						return _this.GetPattern(index++, false, 'video');
					}
					else
					{
						_this.arIframes[index] = s;
						return _this.GetPattern(index++, false, 'iframe');
					}
				}
			);
			return content;
		},

		// Example: <style type="css/text"></style> => #BXSTYLE_0#
		ReplaceStyleBySymCode: function(content)
		{
			this.arStyles = {};
			var
				_this = this,
				index = 0;

			content = content.replace(/<style[\s\S]*?\/style>/gi, function(s)
				{
					_this.arStyles[index] = s;
					return _this.GetPattern(index++, false, 'style');
				}
			);
			return content;
		},

		ReplaceObjectBySymCode: function(content)
		{
			this.arObjects = {};
			var
				_this = this,
				index = 0;

			content = content.replace(/<object[\s\S]*?\/object>/gi, function(s)
				{
					_this.arObjects[index] = s;
					return _this.GetPattern(index++, false, 'object');
				}
			);

			content = content.replace(/<embed[\s\S]*?(?:\/embed)?>/gi, function(s)
				{
					_this.arObjects[index] = s;
					return _this.GetPattern(index++, false, 'object');
				}
			);
			return content;
		},

		CheckForVideo: function(str)
		{
			var videoRe = /(?:src)\s*=\s*("|')([\s\S]*?((?:youtube.com)|(?:youtu.be)|(?:rutube.ru)|(?:vimeo.com))[\s\S]*?)(\1)/ig;

			var res = videoRe.exec(str);
			if (res)
			{
				return {
					src: res[2],
					provider: this.GetVideoProviderName(res[3])
				};
			}
			else
			{
				return false;
			}
		},

		GetVideoProviderName: function(url)
		{
			var name = '';
			switch (url)
			{
				case 'youtube.com':
				case 'youtu.be':
					name = 'YouTube';
					break;
				case 'rutube.ru':
					name = 'Rutube';
					break;
				case 'vimeo.com':
					name = 'Vimeo';
					break;
			}
			return name;
		},

		SavePhpCode: function(code, index)
		{
			this.arScripts[index] = code;
			return this.GetPhpPattern(index, false);
		},

		GetPhpPattern: function(ind, bRegexp)
		{
			if (bRegexp)
				return new RegExp('#BXPHP_' + ind + '#', 'ig');
			else
				return '#BXPHP_' + ind + '#';
		},

		GetPattern: function(ind, bRegexp, entity)
		{
			var code;

			switch (entity)
			{
				case 'php':
					code = '#BXPHP_';
					break;
				case 'javascript':
					code = '#BXJAVASCRIPT_';
					break;
				case 'html_comment':
					code = '#BXHTMLCOMMENT_';
					break;
				case 'iframe':
					code = '#BXIFRAME_';
					break;
				case 'style':
					code = '#BXSTYLE_';
					break;
				case 'video':
					code = '#BXVIDEO_';
					break;
				case 'object':
					code = '#BXOBJECT_';
					break;
				default:
					return '';
			}

			return bRegexp ? new RegExp(code + ind + '#', 'ig') : code + ind + '#';
		},

		// Example:
		// #BXPHP0# => <img ... />
		ParseSymCode: function(content)
		{
			var _this = this;

			content = content.replace(/#BX(PHP|JAVASCRIPT|HTMLCOMMENT|IFRAME|STYLE|VIDEO|OBJECT)_(\d+)#/g, function(str, type, ind)
			{
				var res = '';
				if (_this.IsAllowed(type.toLowerCase()))
				{
					switch (type)
					{
						case 'PHP':
							res = _this.GetPhpCodeHTML(_this.arScripts[ind]);
							break;
						case 'JAVASCRIPT':
							res = _this.GetJavascriptCodeHTML(_this.arJavascripts[ind]);
							break;
						case 'HTMLCOMMENT':
							res = _this.GetHtmlCommentHTML(_this.arHtmlComments[ind]);
							break;
						case 'IFRAME':
							res = _this.GetIframeHTML(_this.arIframes[ind]);
							break;
						case 'STYLE':
							res = _this.GetStyleHTML(_this.arStyles[ind]);
							break;
						case 'VIDEO':
							res = _this.GetVideoHTML(_this.arVideos[ind]);
							break;
						case 'OBJECT':
							res = _this.GetObjectHTML(_this.arObjects[ind]);
							break;
					}
				}
				return res;
			});

			return content;
		},

		GetPhpCodeHTML: function(code)
		{
			var
				result = '',
				component = this.editor.components.IsComponent(code);

			if (component !== false) // It's Bitrix Component
			{
				var
					cData = this.editor.components.GetComponentData(component.name),
					name = cData.title || component.name,
					title = (cData.params && cData.params.DESCRIPTION) ? cData.params.DESCRIPTION : title;

				if (cData.className)
				{
					component.className = cData.className || '';
				}
				result = this.GetSurrogateHTML('component', name, title, component);
			}
			else // ordinary PHP code
			{
				if (this.editor.allowPhp)
				{
					result = this.GetSurrogateHTML("php", BX.message('BXEdPhpCode'), BX.message('BXEdPhpCode') + ": " + this.GetShortTitle(code, 200), {value : code});
				}
				else
				{
					// TODO: add warning for here (access denied or smth )
					result = '';
				}
			}

			return result;
		},

		GetJavascriptCodeHTML: function(code)
		{
			return this.GetSurrogateHTML("javascript", "Javascript", "Javascript: " + this.GetShortTitle(code, 200), {value : code});
		},

		GetHtmlCommentHTML: function(code)
		{
			return this.GetSurrogateHTML("htmlcomment", BX.message('BXEdHtmlComment'), BX.message('BXEdHtmlComment') + ": " + this.GetShortTitle(code), {value : code});
		},

		GetIframeHTML: function(code)
		{
			return this.GetSurrogateHTML("iframe", BX.message('BXEdIframe'), BX.message('BXEdIframe') + ": " + this.GetShortTitle(code), {value : code});
		},

		GetStyleHTML: function(code)
		{
			return this.GetSurrogateHTML("style", BX.message('BXEdStyle'), BX.message('BXEdStyle') + ": " + this.GetShortTitle(code), {value : code});
		},

		GetVideoHTML: function(videoParams)
		{
			var
				tag = "video",
				params = this.FetchVideoIframeParams(videoParams.html, videoParams.provider);

			params.value = videoParams.html;

			var
				id = this.editor.SetBxTag(false, {tag: tag, name: params.title, params: params}),
				surrogateId = this.editor.SetBxTag(false, {tag: "surrogate_dd", params: {origParams: params, origId: id}});

			this.editor.SetBxTag({id: id},
				{
					tag: tag,
					name: params.title,
					params: params,
					title: params.title,
					surrogateId: surrogateId
				}
			);

			var result = '<span id="' + id + '" title="' + params.title + '"  class="' + this.surrClass + ' bxhtmled-video-surrogate' + '" ' +
				'style="min-width:' + params.width + 'px; max-width:' + params.width + 'px; min-height:' + params.height + 'px; max-height:' + params.height + 'px"' +
				'>' +
				'<img title="' + params.title + '" id="'+ surrogateId +'" class="bxhtmled-surrogate-dd" src="' + this.editor.util.GetEmptyImage() + '"/>' +
				'<span class="bxhtmled-surrogate-inner"><span class="bxhtmled-video-icon"></span><span class="bxhtmled-comp-lable" spellcheck=false>' + params.title + '</span></span>' +
				'</span>';

			return result;
		},

		GetObjectHTML: function(code)
		{
			return this.GetSurrogateHTML("object", BX.message('BXEdObjectEmbed'),  BX.message('BXEdObjectEmbed') + ": " + this.GetShortTitle(code), {value : code});
		},

		FetchVideoIframeParams: function(html, provider)
		{
			var
				attrRe = /((?:title)|(?:width)|(?:height))\s*=\s*("|')([\s\S]*?)(\2)/ig,
				res = {
					width: 180,
					height: 100,
					title: provider ? BX.message('BXEdVideoTitleProvider').replace('#PROVIDER_NAME#', provider) : BX.message('BXEdVideoTitle'),
					origTitle : ''
				};

			html.replace(attrRe, function(s, attrName, q, attrValue)
			{
				attrName = attrName.toLowerCase();
				if (attrName == 'width' || attrName == 'height')
				{
					attrValue = parseInt(attrValue, 10);
					if (attrValue && !isNaN(attrValue))
					{
						res[attrName] = attrValue;
					}
				}
				else if (attrName == 'title')// title
				{
					attrValue = BX.util.htmlspecialchars(attrValue);
					attrValue = attrValue.replace('"', '\"');
					res.title += ': ' + attrValue;
					res.origTitle = attrValue;
				}
				return s;
			});

			return res;
		},

		GetSurrogateHTML: function(tag, name, title, params)
		{
			if (title)
			{
				title = BX.util.htmlspecialchars(title);
				title = title.replace('"', '\"');
			}

			var
				id = this.editor.SetBxTag(false, {tag: tag, name: name, params: params}),
				surrogateId = this.editor.SetBxTag(false, {tag: "surrogate_dd", params: {origParams: params, origId: id}});

			this.editor.SetBxTag({id: id}, {tag: tag, name: name, params: params, title: title, surrogateId: surrogateId});

			if (!this.surrogateTags.tag)
			{
				this.surrogateTags.tag = 1;
			}

			var result = '<span id="' + id + '" title="' + (title || name) + '"  class="' + this.surrClass + (params.className ? ' ' + params.className : '') + '">' +
				this.GetSurrogateInner(surrogateId, title, name) +
				'</span>';

			return result;
		},

		GetSurrogateNode: function(tag, name, title, params)
		{
			var
				doc = this.editor.GetIframeDoc(),
				id = this.editor.SetBxTag(false, {tag: tag, name: name, params: params, title: title}),
				surrogateId = this.editor.SetBxTag(false, {tag: "surrogate_dd", params: {origParams: params, origId: id}});

			this.editor.SetBxTag({id: id}, {
				tag: tag,
				name: name,
				params: params,
				title: title,
				surrogateId: surrogateId
			});

			if (!this.surrogateTags.tag)
			{
				this.surrogateTags.tag = 1;
			}

			return BX.create('SPAN', {props: {
				id: id,
				title: title || name,
				className: this.surrClass + (params.className ? ' ' + params.className : '')
			},
				html: this.GetSurrogateInner(surrogateId, title, name)
			}, doc);
		},

		GetSurrogateInner: function(surrogateId, title, name)
		{
			return '<img title="' + (title || name) + '" id="'+ surrogateId +'" class="bxhtmled-surrogate-dd" src="' + this.editor.util.GetEmptyImage() + '"/>' +
				'<span class="bxhtmled-surrogate-inner"><span class="bxhtmled-right-side-item-icon"></span><span class="bxhtmled-comp-lable" unselectable="on" spellcheck=false>' + BX.util.htmlspecialchars(name) + '</span></span>';
		},

		GetShortTitle: function(str, trim)
		{
			//trim = trim || 100;
			if (str.length > 100)
				str = str.substr(0, 100) + '...';
			return str;
		},

		_GetUnParsedContent: function(content)
		{
			var _this = this;
			content = content.replace(/#BX(PHP|JAVASCRIPT|HTMLCOMMENT|IFRAME|STYLE|VIDEO|OBJECT)_(\d+)#/g, function(str, type, ind)
			{
				var res;
				switch (type)
				{
					case 'PHP':
						res = _this.arScripts[ind];
						break;
					case 'JAVASCRIPT':
						res = _this.arJavascripts[ind];
						break;
					case 'HTMLCOMMENT':
						res = _this.arHtmlComments[ind];
						break;
					case 'IFRAME':
						res = _this.arIframes[ind];
						break;
					case 'STYLE':
						res = _this.arStyles[ind];
						break;
					case 'VIDEO':
						res = _this.arVideos[ind].html;
						break;
					case 'OBJECT':
						res = _this.arObjects[ind].html;
						break;
				}
				return res;
			});

			return content;
		},

		IsSurrogate: function(node)
		{
			return node && BX.hasClass(node, this.surrClass);
		},

		IsComponent: function(code)
		{
			code = this.TrimPhpBrackets(code);
			code = this.CleanCode(code);

			var oFunction = this.ParseFunction(code);
			if (oFunction && oFunction.name.toUpperCase() == '$APPLICATION->INCLUDECOMPONENT')
			{
				var arParams = this.ParseParameters(oFunction.params);
				return {
					name: arParams[0],
					template: arParams[1] || "",
					params: arParams[2] || {},
					parentComponent: (arParams[3] && arParams[3] != '={false}') ? arParams[3] : false,
					exParams: arParams[4] || false
				};

//				for (key in params)
//					if (typeof params[key] == 'object')
//						params[key] = _BXArr2Str(params[key]);
//
//				//try{
//				var
//					comProps = window.as_arComp2Elements[name],
//					icon = (comProps.icon) ? comProps.icon : '/bitrix/images/fileman/htmledit2/component.gif',
//					tagname = (comProps.tagname) ? comProps.tagname : 'component2',
//					allParams = copyObj(comProps.params);
//
//				allParams.name = name;
//				allParams.template = template;
//				allParams.parentComponent = parentComponent;
//				allParams.exParams = exParams;
//
//				//Handling SEF_URL_TEMPLATES
//				if (params["SEF_URL_TEMPLATES"])
//				{
//					var _str = params["SEF_URL_TEMPLATES"];
//					var arSUT = oBXEditorUtils.PHPParser.getArray((_str.substr(0,8).toLowerCase() == "={array(") ? _str.substr(2,_str.length-3) : _str);
//
//					for (var _key in arSUT)
//						params["SEF_URL_TEMPLATES_"+_key] = arSUT[_key];
//
//					delete params["SEF_URL_TEMPLATES"];
//				}
//
//				if (params["VARIABLE_ALIASES"])
//				{
//					if (params["SEF_MODE"] == "N")
//					{
//						var _str = params["VARIABLE_ALIASES"];
//						var _arVA = oBXEditorUtils.PHPParser.getArray((_str.substr(0,8).toLowerCase() == "={array(") ? _str.substr(2,_str.length-3) : _str);
//
//						for (var _key in _arVA)
//							params["VARIABLE_ALIASES_"+_key] = _arVA[_key];
//					}
//					delete params["VARIABLE_ALIASES"];
//				}
//
//				allParams.paramvals = params;
//				var bTagParams = {};
//				if (pMainObj.bRenderComponents)
//				{
//					bTagParams._src = icon;
//					icon = c2wait_path;
//				}
//
//				var id = pMainObj.SetBxTag(false, {tag: tagname, params: bTagParams});
//				allParams.__bx_id = push2Component2(id, allParams.name); // Used to cache component-params for each component
//
//				if (!pMainObj.arComponents)
//					pMainObj.arComponents = {};
//				pMainObj.arComponents[id] = allParams;
//
//				return '<img style="cursor: default;" id="' + id + '" src="' + icon + '" />';
				//}catch(e) {}
			}
			return false;
		},

		AdvancedPhpParse: function(content)
		{
			return content;
		},

		TrimPhpBrackets: function(str)
		{
			if (str.substr(0, 2) != "<?")
				return str;

			if(str.substr(0, 5).toLowerCase()=="<?php")
				str = str.substr(5);
			else
				str = str.substr(2);

			str = str.substr(0, str.length-2);
			return str;
		},

		TrimQuotes: function(str, qoute)
		{
			var f_ch, l_ch;
			str = str.trim();
			if (qoute == undefined)
			{
				f_ch = str.substr(0, 1);
				l_ch = str.substr(0, 1);
				if ((f_ch == '"' && l_ch == '"') || (f_ch == '\'' && l_ch == '\''))
					str = str.substring(1, str.length - 1);
			}
			else
			{
				if (!qoute.length)
					return str;
				f_ch = str.substr(0, 1);
				l_ch = str.substr(0, 1);
				qoute = qoute.substr(0, 1);
				if (f_ch == qoute && l_ch == qoute)
					str = str.substring(1, str.length - 1);
			}
			return str;
		},

		CleanCode: function(str)
		{
			var
				bSlashed = false,
				bInString = false,
				new_str = "",
				i=-1, ch, ti, quote_ch;

			while(i < str.length - 1)
			{
				i++;
				ch = str.substr(i, 1);
				if(!bInString)
				{
					if(ch == "/" && i + 1 < str.length)
					{
						ti = 0;
						if(str.substr(i+1, 1) == "*" && ((ti = str.indexOf("*/", i + 2)) >= 0))
							ti += 2;
						else if(str.substr(i + 1, 1) == "/" && ((ti = str.indexOf("\n", i + 2)) >= 0))
							ti += 1;

						if(ti > 0)
						{
							if(i > ti)
								alert('iti=' + i + '=' + ti);
							i = ti;
						}

						continue;
					}

					if(ch == " " || ch == "\r" || ch == "\n" || ch == "\t")
						continue;
				}

				if(bInString && ch == "\\")
				{
					bSlashed = true;
					new_str += ch;
					continue;
				}

				if(ch == "\"" || ch == "'")
				{
					if(bInString)
					{
						if(!bSlashed && quote_ch == ch)
							bInString = false;
					}
					else
					{
						bInString = true;
						quote_ch = ch;
					}
				}
				bSlashed = false;
				new_str += ch;
			}
			return new_str;
		},

		ParseFunction: function(str)
		{
			var
				pos = str.indexOf("("),
				lastPos = str.lastIndexOf(")");

			if(pos >= 0 && lastPos >= 0 && pos<lastPos)
				return {name:str.substr(0, pos),params:str.substring(pos+1,lastPos)};

			return false;
		},

		ParseParameters: function(str)
		{
			str = this.CleanCode(str);
			var
				prevAr = this.GetParams(str),
				tq, j, l = prevAr.length;

			for (j = 0; j < l; j++)
			{
				if (prevAr[j].substr(0, 6).toLowerCase()=='array(')
				{
					prevAr[j] = this.GetArray(prevAr[j]);
				}
				else
				{
					tq = this.TrimQuotes(prevAr[j]);
					if (this.IsNum(tq) || prevAr[j] != tq)
						prevAr[j] = tq;
					else
						prevAr[j] = this.WrapPhpBrackets(prevAr[j]);
				}
			}
			return prevAr;
		},

		GetArray: function(str)
		{
			var resAr = {};
			if (str.substr(0, 6).toLowerCase() != 'array(')
				return str;

			str = str.substring(6, str.length-1);
			var
				tempAr = this.GetParams(str),
				prop_name, prop_val, p,
				y;

			for (y = 0; y < tempAr.length; y++)
			{
				if (tempAr[y].substr(0, 6).toLowerCase()=='array(')
				{
					resAr[y] = this.GetArray(tempAr[y]);
					continue;
				}

				p = tempAr[y].indexOf("=>");
				if (p == -1)
				{
					if (tempAr[y] == this.TrimQuotes(tempAr[y]))
						resAr[y] = this.WrapPhpBrackets(tempAr[y]);
					else
						resAr[y] = this.TrimQuotes(tempAr[y]);
				}
				else
				{
					prop_name = this.TrimQuotes(tempAr[y].substr(0, p));
					prop_val = tempAr[y].substr(p + 2);
					if (prop_val == this.TrimQuotes(prop_val))
						prop_val = this.WrapPhpBrackets(prop_val);
					else
						prop_val = this.TrimQuotes(prop_val);

					if (prop_val.substr(0, 6).toLowerCase()=='array(')
						prop_val = this.GetArray(prop_val);

					resAr[prop_name] = prop_val;
				}
			}
			return resAr;
		},

		WrapPhpBrackets: function(str)
		{
			str = str.trim();
			var
				f_ch = str.substr(0, 1),
				l_ch = str.substr(0, 1);

			if ((f_ch == '"' && l_ch == '"') || (f_ch == '\'' && l_ch == '\''))
				return str;

			return "={" + str + "}";
		},

		GetParams: function(params)
		{
			var
				arParams = [],
				sk = 0, ch, sl, q1 = 1,q2 = 1, i,
				param_tmp = "";

			for(i = 0; i < params.length; i++)
			{
				ch = params.substr(i, 1);
				if (ch == "\"" && q2 == 1 && !sl)
				{
					q1 *= -1;
				}
				else if (ch == "'" && q1 == 1 && !sl)
				{
					q2 *=-1;
				}
				else if(ch == "\\" && !sl)
				{
					sl = true;
					param_tmp += ch;
					continue;
				}

				if (sl)
					sl = false;

				if (q2 == -1 || q1 == -1)
				{
					param_tmp += ch;
					continue;
				}

				if(ch == "(")
				{
					sk++;
				}
				else if(ch == ")")
				{
					sk--;
				}
				else if(ch == "," && sk == 0)
				{
					arParams.push(param_tmp);
					param_tmp = "";
					continue;
				}

				if(sk < 0)
					break;

				param_tmp += ch;
			}
			if(param_tmp != "")
				arParams.push(param_tmp);

			return arParams;
		},

		IsNum: function(val)
		{
			var _val = val;
			val = parseFloat(_val);
			if (isNaN(val))
				val = parseInt(_val);
			if (!isNaN(val))
				return _val == val;
			return false;
		},

		ParseBxNodes: function(content)
		{
			var
				i,
				//skipBxNodeIds = [],
				bxNodes = this.editor.parser.convertedBxNodes,
				l = bxNodes.length;

			for(i = 0; i < l; i++)
			{
				if (bxNodes[i].tag == 'surrogate_dd')
				{
					content = content.replace('~' + bxNodes[i].params.origId + '~', '');
				}
			}

			this._skipNodeIndex = {}; //_skipNodeIndex - used in Chrome to prevent double parsing of surrogates
			this._skipNodeList = [];
			var _this = this;

			content = content.replace(/~(bxid\d{1,9})~/ig, function(s, bxid)
			{
				if (!_this._skipNodeIndex[bxid])
				{
					var bxTag = _this.editor.GetBxTag(bxid);
					if (bxTag && bxTag.tag)
					{
						var node = _this.GetBxNode(bxTag.tag);
						if (node)
						{
							return node.Parse(bxTag.params);
						}
					}
				}
				return '';
			});

			return content;
		},

		GetBxNodeList: function()
		{
			var _this = this;
			this.arBxNodes = {
				component: {
					Parse: function(params)
					{
						return _this.editor.components.GetSource(params);
					}
				},
				component_icon: {
					Parse: function(params)
					{
						return _this.editor.components.GetOnDropHtml(params);
					}
				},
				surrogate_dd: {
					Parse: function(params)
					{
						if (BX.browser.IsFirefox() || !params || !params.origId)
						{
							return '';
						}

						var bxTag = _this.editor.GetBxTag(params.origId);
						if (bxTag)
						{
							_this._skipNodeIndex[params.origId] = true;
							_this._skipNodeList.push(params.origId);

							var origNode = _this.GetBxNode(bxTag.tag);
							if (origNode)
							{
								return origNode.Parse(bxTag.params);
							}
						}

						return '#parse surrogate_dd#';
					}
				},
				php: {
					Parse: function(params)
					{
						return _this._GetUnParsedContent(params.value);
					}
				},
				php_protected: {
					Parse: function(params)
					{
						return params.value;
					}
				},
				javascript: {
					Parse: function(params)
					{
						return _this._GetUnParsedContent(params.value);
					}
				},
				htmlcomment: {
					Parse: function(params)
					{
						return _this._GetUnParsedContent(params.value);
					}
				},
				iframe: {
					Parse: function(params)
					{
						return _this._GetUnParsedContent(params.value);
					}
				},
				style: {
					Parse: function(params)
					{
						return _this._GetUnParsedContent(params.value);
					}
				},
				video: {
					Parse: function(params)
					{
						return _this._GetUnParsedContent(params.value);
					}
				},
				object: {
					Parse: function(params)
					{
						return _this._GetUnParsedContent(params.value);
					}
				},
				anchor: {
					Parse: function(params)
					{
						// TODO: copy other attributes
						return '<a name="' + params.name + '">' + params.html + '</a>';
					}
				},
				pagebreak: {
					Parse: function(params)
					{
						return '<BREAK />';
					}
				},
				printbreak: {
					Parse: function(params)
					{
						return '<div style="page-break-after: always">' + params.innerHTML + '</div>';
					}
				}
			};

			this.editor.On("OnGetBxNodeList");

			return this.arBxNodes;
		},

		AddBxNode: function(key, node)
		{
			if (this.arBxNodes == undefined)
			{
				var _this = this;
				BX.addCustomEvent(this.editor, "OnGetBxNodeList", function(){
					_this.arBxNodes[key] = node;
				});
			}
			else
			{
				this.arBxNodes[key] = node;
			}
		},

		GetBxNode: function(tag)
		{
			if (!this.arBxNodes)
			{
				this.arBxNodes = this.GetBxNodeList();
			}

			return this.arBxNodes[tag] || null;
		},

		OnSurrogateMousedown: function(e, target, bxTag)
		{
			var _this = this;

			// User clicked to surrogate icon
			if (bxTag.tag == 'surrogate_dd')
			{
				BX.bind(target, 'dragstart', function(e){_this.OnSurrogateDragStart(e, this)});
				BX.bind(target, 'dragend', function(e){_this.OnSurrogateDragEnd(e, this, bxTag)});
			}
			else
			{
				setTimeout(function()
				{
					var node = _this.CheckParentSurrogate(_this.editor.selection.GetSelectedNode());
					if(node)
					{
						_this.editor.selection.SetAfter(node);
						if (!node.nextSibling || node.nextSibling.nodeType != 3)
						{
							var invisText = _this.editor.util.GetInvisibleTextNode();
							_this.editor.selection.InsertNode(invisText);
							_this.editor.selection.SetAfter(invisText);
						}
					}
				}, 0);
			}
		},

		OnSurrogateDragEnd: function(e, target, bxTag)
		{
			var
				doc = this.editor.GetIframeDoc(),
				i, surr, surBxTag,
				usedSurrs = {},
				surrs = doc.querySelectorAll('.bxhtmled-surrogate'),
				surrs_dd = doc.querySelectorAll('.bxhtmled-surrogate-dd'),
				l = surrs.length;

			for (i = 0; i < surrs_dd.length; i++)
			{
				if (surrs_dd[i] && surrs_dd[i].id == bxTag.id)
				{
					BX.remove(surrs_dd[i]);
				}
			}

			for (i = 0; i < l; i++)
			{
				surr = surrs[i];
				if (usedSurrs[surr.id])
				{
					BX.remove(surr);
				}
				else
				{
					usedSurrs[surr.id] = true;
					surBxTag = this.editor.GetBxTag(surr.id);
					surr.innerHTML = this.GetSurrogateInner(surBxTag.surrogateId, surBxTag.title, surBxTag.name);
				}
			}
		},

		OnSurrogateDragStart: function(e, target)
		{
			// We need to append it to body to prevent loading picture in Firefox
			if (BX.browser.IsFirefox())
			{
				this.editor.GetIframeDoc().body.appendChild(target);
			}
		},

		CheckParentSurrogate: function(n)
		{
			if (!n)
			{
				return false;
			}

			if (this.IsSurrogate(n))
			{
				return n;
			}

			var
				_this = this,
				iter = 0,
				parentSur = BX.findParent(n, function(node)
				{
					return (iter++ > 4) || _this.IsSurrogate(node);
				}, this.editor.GetIframeDoc().body);

			return this.IsSurrogate(parentSur) ? parentSur : false;
		},

		CheckSurrogateDd: function(n)
		{
			return n && n.nodeType == 1 && this.editor.GetBxTag(n).tag == 'surrogate_dd';
		},

		OnSurrogateClick: function(e, target)
		{
			var bxTag = this.editor.GetBxTag(target);
			// User clicked to component icon
			if (bxTag && bxTag.tag == 'surrogate_dd')
			{
				var origTag = this.editor.GetBxTag(bxTag.params.origId);
				this.editor.On("OnSurrogateClick", [bxTag, origTag, target, e]);
				// Show dialog
				//this.ShowPropertiesDialog(bxTag.params.component, bxTag);
			}
		},

		OnSurrogateDblClick: function(e, target)
		{
			var bxTag = this.editor.GetBxTag(target);
			// User clicked to component icon

			if (bxTag && bxTag.tag == 'surrogate_dd')
			{
				var origTag = this.editor.GetBxTag(bxTag.params.origId);
				this.editor.On("OnSurrogateDblClick", [bxTag, origTag, target, e]);
			}
		},

		OnSurrogateKeyup: function(e, keyCode, command, target)
		{
			var
				sur, bxTag,
				range = this.editor.selection.GetRange();

			if (range)
			{
				// Collapsed selection
				if (range.collapsed)
				{
					if (keyCode === this.editor.KEY_CODES['backspace'] && range.startContainer.nodeName !== 'BODY')
					{
						sur = this.editor.util.CheckSurrogateNode(range.startContainer);
						// It's surrogate node
						bxTag = this.editor.GetBxTag(sur);
						if (sur && bxTag && this.surrogateTags[bxTag.tag])
						{
							this.RemoveSurrogate(sur, bxTag);
						}
					}
				}
				else
				{
				}
			}
		},

		OnSurrogateKeydown: function(e, keyCode, command, target)
		{
			var
				sur,
				range = this.editor.selection.GetRange(),
				invisText,
				bxTag, surNode,
				node = target;

			if (!range.collapsed)
			{
				if (keyCode === this.editor.KEY_CODES['backspace'] || keyCode === this.editor.KEY_CODES['delete'])
				{
					var
						i,
						nodes = range.getNodes([3]);

					for (i = 0; i < nodes.length; i++)
					{
						sur = this.editor.util.CheckSurrogateNode(nodes[i]);
						if (sur)
						{
							bxTag = this.editor.GetBxTag(sur);
							if (this.surrogateTags[bxTag.tag])
							{
								this.RemoveSurrogate(sur, bxTag);
							}
						}
					}
				}
			}

			if (keyCode === this.editor.KEY_CODES['delete'])
			{
				if (range.collapsed)
				{
					invisText = this.editor.util.GetInvisibleTextNode();
					this.editor.selection.InsertNode(invisText);
					this.editor.selection.SetAfter(invisText);
					var nodeNextToCarret = invisText.nextSibling;
					if (nodeNextToCarret)
					{
						if (nodeNextToCarret && nodeNextToCarret.nodeName == 'BR')
						{
							nodeNextToCarret = nodeNextToCarret.nextSibling;
						}
						if (nodeNextToCarret && nodeNextToCarret.nodeType == 3 && (nodeNextToCarret.nodeValue == '\n' || this.editor.util.IsEmptyNode(nodeNextToCarret)))
						{
							nodeNextToCarret = nodeNextToCarret.nextSibling;
						}

						if (nodeNextToCarret)
						{
							BX.remove(invisText);
							bxTag = this.editor.GetBxTag(nodeNextToCarret);

							if (this.surrogateTags[bxTag.tag])
							{
								this.RemoveSurrogate(nodeNextToCarret, bxTag);
								return BX.PreventDefault(e);
							}
						}
					}
				}
			}

			if (range.startContainer == range.endContainer && range.startContainer.nodeName !== 'BODY')
			{
				node = range.startContainer;
				surNode = this.editor.util.CheckSurrogateNode(node);

				if (surNode)
				{
					bxTag = this.editor.GetBxTag(surNode.id);
					if (keyCode === this.editor.KEY_CODES['backspace'] || keyCode === this.editor.KEY_CODES['delete'])
					{
						this.RemoveSurrogate(surNode, bxTag);
						BX.PreventDefault(e);
					}
					else if (keyCode === this.editor.KEY_CODES['left'] || keyCode === this.editor.KEY_CODES['up'])
					{
						var prevToSur = surNode.previousSibling;
						if (prevToSur && prevToSur.nodeType == 3 && this.editor.util.IsEmptyNode(prevToSur))
							this.editor.selection._MoveCursorBeforeNode(prevToSur);
						else
							this.editor.selection._MoveCursorBeforeNode(surNode);

						return BX.PreventDefault(e);
					}
					else if (keyCode === this.editor.KEY_CODES['right'] || keyCode === this.editor.KEY_CODES['down'])
					{
						var nextToSur = surNode.nextSibling;
						if (nextToSur && nextToSur.nodeType == 3 && this.editor.util.IsEmptyNode(nextToSur))
							this.editor.selection._MoveCursorAfterNode(nextToSur);
						else
							this.editor.selection._MoveCursorAfterNode(surNode);

						return BX.PreventDefault(e);
					}
					else
					{
						this.editor.selection.SelectNode(surNode);
					}
				}
			}
		},

		RemoveSurrogate: function(node, bxTag)
		{
			this.editor.undoManager.Transact();
			BX.remove(node);
			this.editor.On("OnSurrogateRemove", [node, bxTag]);
		},

		CheckHiddenSurrogateDrag: function()
		{
			var dd, i, doc = this.editor.GetIframeDoc();
			for (i = 0; i < this.hiddenDd.length; i++)
			{
				dd = doc.getElementById(this.hiddenDd[i]);
				if (dd)
				{
					dd.style.visibility = '';
				}
			}
			this.hiddenDd = [];
		},

		GetAllSurrogates: function()
		{
			var
				doc = this.editor.GetIframeDoc(),
				res = [], i, surr, bxTag,
				surrs = doc.querySelectorAll(".bxhtmled-surrogate");

			for (i = 0; i < surrs.length; i++)
			{
				surr = surrs[i];
				bxTag = this.editor.GetBxTag(surr.id);
				if (bxTag.tag)
				{
					res.push({
						node : surr,
						bxTag : bxTag
					});
				}
			}

			return res;
		},

		RenewSurrogates: function()
		{
			var
				i,
				surrs = this.GetAllSurrogates();

			for (i = 0; i < surrs.length; i++)
			{
				surrs[i].node.innerHTML = this.GetSurrogateInner(surrs[i].bxTag.surrogateId, surrs[i].bxTag.title, surrs[i].bxTag.name);

			}
		},

		RedrawSurrogates: function()
		{
			var i, surrs = this.GetAllSurrogates();

			for (i = 0; i < surrs.length; i++)
			{
				if (surrs[i].node)
				{
					BX.addClass(surrs[i].node, 'bxhtmled-surrogate-tmp');
				}
			}

			setTimeout(function(){
				for (i = 0; i < surrs.length; i++)
				{
					if (surrs[i].node)
					{
						BX.removeClass(surrs[i].node, 'bxhtmled-surrogate-tmp');
					}
				}
			}, 0);
		},

		IsAllowed: function(id)
		{
			return this.allowed[id];
		}
	};

	function BXCodeFormatter(editor)
	{
		this.editor = editor;

		var
			ownLine = ['area', 'hr', 'i?frame', 'link', 'meta', 'noscript', 'style', 'table', 'tbody', 'thead', 'tfoot'],
			contOwnLine = ['li', 'dt', 'dd', 'h[1-6]', 'option', 'script'];

		this.reBefore = new RegExp('^<(/?' + ownLine.join('|/?') + '|' + contOwnLine.join('|') + ')[ >]', 'i');
		this.reAfter = new RegExp('^<(br|/?' + ownLine.join('|/?') + '|/' + contOwnLine.join('|/') + ')[ >]');

		var newLevel = ['blockquote', 'div', 'dl', 'fieldset', 'form', 'frameset', 'map', 'ol', 'p', 'pre', 'select', 'td', 'th', 'tr', 'ul'];
		this.reLevel = new RegExp('^</?(' + newLevel.join('|') + ')[ >]');

		this.lastCode = null;
		this.lastResult = null;
	}

	BXCodeFormatter.prototype = {
		Format: function(code)
		{
			if (code != this.lastCode)
			{
				this.lastCode = code;
				this.lastResult = this.DoFormat(code);
			}
			return this.lastResult;
		},

		DoFormat: function(code)
		{
			code += ' ';
			this.level = 0;

			var
				i, t,
				point = 0,
				start = null,
				end = null,
				tag = '',
				result = '',
				cont = '';

			for (i = 0; i < code.length; i++)
			{
				point = i;
				//if no more tags ==> exit
				if (code.substr(i).indexOf('<') == -1)
				{
					result += code.substr(i);

					result = result.replace(/\n\s*\n/g, '\n');  //blank lines
					result = result.replace(/^[\s\n]*/, ''); //leading space
					result = result.replace(/[\s\n]*$/, ''); //trailing space

					if (result.indexOf('<!--noindex-->') !== -1)
					{
						result = result.replace(/(<!--noindex-->)(?:[\s|\n|\r|\t]*?)(<a[\s\S]*?\/a>)(?:[\s|\n|\r|\t]*?)(<!--\/noindex-->)/ig, "$1$2$3");
					}

					return result;
				}

				while (point < code.length && code.charAt(point) !== '<')
				{
					point++;
				}

				if (i != point)
				{
					cont = code.substr(i, point - i);
					if (cont.match(/^\s+$/))
					{
						cont = cont.replace(/\s+/g, ' ');
						result += cont;
					}
					else
					{
						if (result.charAt(result.length - 1) == '\n')
						{
							result += this.GetTabs();
						}
						else if (cont.charAt(0) == '\n')
						{
							result += '\n' + this.GetTabs();
							cont = cont.replace(/^\s+/, '');
						}
						cont = cont.replace(/\n/g, ' ');
						cont = cont.replace(/\n+/g, '');
						cont = cont.replace(/\s+/g, ' ');
						result += cont;
					}

					if (cont.match(/\n/))
					{
						result += '\n' + this.GetTabs();
					}
				}
				start = point;

				//find the end of the tag
				while (point < code.length && code.charAt(point) != '>')
				{
					point++;
				}

				tag = code.substr(start, point - start);
				i = point;

				//if this is a special tag, deal with it
				if (tag.substr(1, 3) === '!--')
				{
					if (!tag.match(/--$/))
					{
						while (code.substr(point, 3) !== '-->')
						{
							point++;
						}
						point += 2;
						tag = code.substr(start, point - start);
						i = point;
					}
					if (result.charAt(result.length - 1) !== '\n')
					{
						result += '\n';
					}

					result += this.GetTabs();
					result += tag + '>\n';
				}
				else if (tag[1] === '!')
				{
					result = this.PutTag(tag + '>', result);
				}
				else if (tag[1] == '?')
				{
					result += tag + '>\n';
				}
				else if (t = tag.match(/^<(script|style)/i))
				{
					t[1] = t[1].toLowerCase();
					result = this.PutTag(this.CleanTag(tag), result);
					end = String(code.substr(i + 1)).toLowerCase().indexOf('</' + t[1]);

					if (end)
					{
						cont = code.substr(i + 1, end);
						i += end;
						result += cont;
					}
				}
				else
				{
					result = this.PutTag(this.CleanTag(tag), result);
				}
			}

			return code;
		},

		GetTabs: function()
		{
			var s = '', j;
			for (j = 0; j < this.level; j++)
			{
				s += '\t';
			}
			return s;
		},

		CleanTag: function(tag)
		{
			var
				m,
				partRe = /\s*([^= ]+)(?:=((['"']).*?\3|[^ ]+))?/,
				result = '',
				suffix = '';

			tag = tag.replace(/\n/g, ' '); //remove newlines
			tag = tag.replace(/[\s]{2,}/g, ' '); //collapse whitespace
			tag = tag.replace(/^\s+|\s+$/g, ' '); //collapse whitespace

			if (tag.match(/\/$/))
			{
				suffix = '/';
				tag = tag.replace(/\/+$/, '');
			}

			while (m = partRe.exec(tag))
			{
				if (m[2])
					result += m[1].toLowerCase() + '=' + m[2];
				else if (m[1])
					result += m[1].toLowerCase();
				result += ' ';

				tag = tag.substr(m[0].length);
			}

			return result.replace(/\s*$/, '') + suffix + '>';
		},

		PutTag: function(tag, res)
		{
			var nl = tag.match(this.reLevel);

			if (tag.match(this.reBefore) || nl)
			{
				res = res.replace(/\s*$/, '');
				res += "\n";
			}

			if (nl && tag.charAt(1) == '/')
			{
				this.level--;
			}

			if (res.charAt(res.length-1) == '\n')
			{
				res += this.GetTabs();
			}

			if (nl && '/' != tag.charAt(1))
			{
				this.level++;
			}

			res += tag;
			if (tag.match(this.reAfter) || tag.match(this.reLevel))
			{
				res = res.replace(/ *$/, '');
				res += "\n";
			}

			return res;
		}
	};

	function __run()
	{
		window.BXHtmlEditor.BXCodeFormatter = BXCodeFormatter;
		window.BXHtmlEditor.BXEditorParser = BXEditorParser;
		window.BXHtmlEditor.BXEditorPhpParser = BXEditorPhpParser;
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