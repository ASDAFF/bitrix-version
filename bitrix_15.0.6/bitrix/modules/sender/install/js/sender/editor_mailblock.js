/**
 * Bitrix HTML Editor 3.0
 * Date: 24.04.13
 * Time: 4:23
 *
 * Snippets class
 */
(function()
{
function __runmailblocks()
{
	function BXEditorMailBlocks(editor)
	{
		this.editor = editor;
		this.listLoaded = false;
		this.mailblocks = this.editor.config.mailblocks;
		this.HandleList();
		this.Init();
	}

    BXEditorMailBlocks.prototype = {
		Init: function()
		{
			BX.addCustomEvent(this.editor, "OnApplySiteTemplate", BX.proxy(this.OnTemplateChanged, this));
		},

		GetList: function()
		{
			return this.mailblocks;
		},

		HandleList: function()
		{
			var
				i,
				items = this.GetList().items;
			if (items)
			{
				for (i in items)
				{
					if (items.hasOwnProperty(i))
					{
						items[i].key = items[i].path.replace('/', ',');
					}
				}
			}
		},

		ReloadList: function(clearCache)
		{
		},

		FetchPlainListOfCategories: function(list, level, result)
		{
			var i, l = list.length;
			for (i = 0; i < l; i++)
			{
				result.push({
					level: level,
					key: list[i].key,
					section: list[i].section
				});

				if (list[i].children && list[i].children.length > 0)
				{
					this.FetchPlainListOfCategories(list[i].children, level + 1, result);
				}
			}
		},

		OnTemplateChanged: function(templateId)
		{
			this.ReloadList(false);
		}
	};

	function MailBlocksControl(editor)
	{
		// Call parrent constructor
		MailBlocksControl.superclass.constructor.apply(this, arguments);

		this.id = 'mailblocks';
		this.mailblocks = this.editor.config.mailblocks;
		this.templateId = this.editor.templateId;
		this.title = BX.message('BXEdMailBlocksTitle');
		this.searchPlaceholder = BX.message('BXEdMailBlocksSearchPlaceHolder');
		this.uniqueId = 'taskbar_' + this.editor.id + '_' + this.id;

		this.Init();
	}

	BX.extend(MailBlocksControl, window.BXHtmlEditor.Taskbar);

	MailBlocksControl.prototype.Init = function()
	{
		this.BuildSceleton();

		// Build structure
		if (this.mailblocks)
		{
			this.BuildTree(this.mailblocks.groups, this.mailblocks.items);
		}

		var _this = this;
		_this.editor.phpParser.AddBxNode('mailblock_icon',
			{
				Parse: function(params)
				{
					return params.code || '';
				}
			}
		);
	};

    MailBlocksControl.prototype.GetMenuItems = function()
	{
		var _this = this;

		var arItems = [
            {
                text : BX.message('RefreshTaskbar'),
                title : BX.message('RefreshTaskbar'),
                className : "",
                onclick: function()
                {
                    _this.editor.snippets.ReloadList(true);
                    BX.PopupMenu.destroy(_this.uniqueId + "_menu");
                }
            }
        ];
		return arItems;
	};

    MailBlocksControl.prototype.HandleElementEx = function(wrap, dd, params)
	{
		this.editor.SetBxTag(dd, {tag: "mailblock_icon", params: params});
		wrap.title = params.description || params.title;
	};



    MailBlocksControl.prototype.BuildTree = function(sections, elements)
	{
		// Call parent method
		MailBlocksControl.superclass.BuildTree.apply(this, arguments);
		if ((!sections || sections.length == 0) && (!elements || elements.length == 0))
		{
			this.pTreeCont.appendChild(BX.create('DIV', {props: {className: 'bxhtmled-no-snip'}, text: BX.message('BXEdSnipNoSnippets')}));
		}
	};


	window.BXHtmlEditor.MailBlocksControl = MailBlocksControl;
	window.BXHtmlEditor.BXEditorMailBlocks = BXEditorMailBlocks;
}

	if (window.BXHtmlEditor && window.BXHtmlEditor.dialogs)
		__runmailblocks();
	else
		BX.addCustomEvent(window, "OnEditorBaseControlsDefined", __runmailblocks);

})();