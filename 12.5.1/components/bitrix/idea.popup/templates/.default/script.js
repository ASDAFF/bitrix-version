if(typeof JSIdeaDialog != 'object')
{    
    
var JSIdeaDialog = {
    Dialog: {
        id: 'idea-side-dialog',
        content_id: 'idea-side-dialog-content',
        closeButtonBinded: false,
        closeEscBinded: false,
        
        Editor: function(){return window.oIdeaLHELight;},
        EditorRestart: function(){
            try{
                JSIdeaDialog.Dialog.Editor().SetContent("");
                BX('POST_TITLE').value = '';
                BX('POST_TITLE').focus();
                JSIdeaDialog.Dialog.Editor().SetEditorContent("");
            }
            catch(e){}
        },
        
        Show: function(){
            JSIdeaDialog.Overlay.Show();
            
            var WindowSize = BX.GetWindowSize();
            
            var oDialog = BX(this.id);
            oDialog.style.display = 'block';
            oDialog.style.zIndex = JSIdeaDialog.Overlay.zIndex + 2;
            var Left = Math.round(WindowSize.innerWidth/2) - 400;
            if(Left < 0)
                Left = 0;
            oDialog.style.left = Left + 'px';
            oDialog.style.top = (WindowSize.scrollTop + 100) + 'px';
            
            
            if(JSIdeaDialog.Dialog.Editor())
            {
                setTimeout(function(){
                    //JSIdeaDialog.Dialog.Editor().SetContent(JSIdeaDialog.Dialog.Editor().GetContent());
                    //JSIdeaDialog.Dialog.Editor().CreateFrame();
                    //alert(JSIdeaDialog.Dialog.Editor().GetContent());
                    //JSIdeaDialog.Dialog.Editor().SetEditorContent(JSIdeaDialog.Dialog.Editor().GetContent());
                    BX('POST_TITLE').focus();
                }, 100);
            }

            if(!this.closeButtonBinded)
            {
                var CloseButton = BX.findChild(BX(this.id), {className:'idea-side-dialog-close-button-wrapper'}, true, false);
                if(CloseButton)
                {
                    BX.bind(CloseButton, 'click', function(){
                        JSIdeaDialog.Dialog.Close();
                    });
                    this.closeButtonBinded = true;
                }
            }
            
            if(!this.closeEscBinded)
            {
                BX.bind(document, "keyup", BX.proxy(this._onEsc), this);
                this.closeEscBinded = true;
            }
        },
        
        Close: function()
        {
            JSIdeaDialog.Overlay.Hide();
            if(JSIdeaDialog.Dialog.Editor())
                JSIdeaDialog.Dialog.Editor().SaveContent();
            
            var oDialog = BX(this.id);
            oDialog.style.display = 'none';
            
            BX.unbind(document, "keyup", BX.proxy(this._onEsc, this));
            
            var DialogNoteBox = BX('idea-ajax-notification');
            if(DialogNoteBox)
                DialogNoteBox.innerHTML = '';
        },
        
        _onEsc: function(event)
        {
            event = event || window.event;
            if (event.keyCode == 27)
                JSIdeaDialog.Dialog.Close();
        },
        
        SetContent: function(content, content_id){
            var DialogNoteBox = BX('idea-ajax-notification');
            if(DialogNoteBox)
                DialogNoteBox.innerHTML = '';
            
            var DialogContentNode = BX.findChild(this.GetContent(), {}, false, true);
            var bCache = false;
            if(DialogContentNode)
            {
                for(i=0, n=DialogContentNode.length; i<n; i++)
                {
                    if(content_id && DialogContentNode[i].id && DialogContentNode[i].id == content_id)
                    {
                        DialogContentNode[i].style.display = 'block';
                        bCache = true;
                    }
                    else
                        DialogContentNode[i].style.display = 'none';
                }
            }
            
            if(bCache)
                return;
            
            if (BX.type.isNotEmptyString(content))
            {
                BX(this.content_id).innerHTML = content;
            }
            else if(BX.type.isDomNode(content))
            {
                BX(this.content_id).appendChild(content);
            }
        }, 
        
        GetContent: function()
        {
            return BX(this.content_id);
        }
    },
    
    Overlay: {
        id: 'bx-idea-overlay',
        zIndex: 900,
        
        Create: function ()
	{
		this.bCreated = true;
		this.bShowed = false;
		var windowSize = BX.GetWindowScrollSize();
		this.pWnd = document.body.appendChild(BX.create("DIV", {props: {id: this.id, className: "bx-idea-overlay"}, style:{zIndex: this.zIndex, width: windowSize.scrollWidth + "px", height: windowSize.scrollHeight + "px"}, events: {drag: BX.False, selectstart: BX.False}}));

		var _this = this;
		window[this.id + '_resize'] = function(){_this.Resize();};
	},

	Show: function(arParams)
	{
		if (!this.bCreated)
			this.Create();
		this.bShowed = true;

		var windowSize = BX.GetWindowScrollSize();

		this.pWnd.style.display = 'block';
		this.pWnd.style.width = windowSize.scrollWidth + "px";
		this.pWnd.style.height = windowSize.scrollHeight + "px";

		if (!arParams)
			arParams = {};

		if (arParams.clickCallback)
		{
			this.pWnd.onclick = function(e)
			{
				var
					clbck = arParams.clickCallback,
					p = clbck.params || [];
				if (clbck.obj)
					clbck.func.apply(clbck.obj, p);
				else
					clbck.func(p);
				return BX.PreventDefault(e);
			};
		}

		if (arParams.zIndex)
			this.pWnd.style.zIndex = arParams.zIndex;

		BX.bind(window, "resize", window[this.id + '_resize']);
		return this.pWnd;
	},

	Hide: function ()
	{
		if (!this.bShowed)
			return;
		this.bShowed = false;
		this.pWnd.style.display = 'none';
		BX.unbind(window, "resize", window[this.id + '_resize']);
		this.pWnd.onclick = null;
	},

	Resize: function ()
	{
		if (this.bCreated)
			this.pWnd.style.width = BX.GetWindowScrollSize().scrollWidth + "px";
	},

	Remove: function ()
	{
		this.Hide();
		if (this.pWnd.parentNode)
			this.pWnd.parentNode.removeChild(this.pWnd);
	}
    }
};

//Init
BX.ready(function(){
    document.body.appendChild(
        BX.create(
            "DIV", 
            {
                props: {
                    id: 'idea-side-dialog'
                },
                html: '<div class="idea-side-dialog-title">' +
                    '<div class="idea-side-dialog-title-l"></div>' +
                    '<div class="idea-side-dialog-title-r"></div>' +
                    '<div class="idea-side-dialog-close-button-wrapper"><i></i></div>' +
                    '<div class="idea-side-dialog-title-c">' + BX.message('IDEA_POPUP_LEAVE_IDEA') + '</div>' +
                '</div>' +
                '<div class="idea-side-dialog-content">' +
                    '<div class="idea-side-dialog-content-l"></div>' +
                    '<div class="idea-side-dialog-content-r"></div>' +
                    '<div class="idea-side-dialog-content-c" id="idea-side-dialog-content"><div id="idea-loader"></div></div>' +
                '</div>' +
                '<div style="position: relative; zoom:1;">' +
                    '<div class="idea-side-dialog-footer-l"></div>' +
                    '<div class="idea-side-dialog-footer-r"></div>' +
                    '<div class="idea-side-dialog-footer-c"></div>' +
                '</div>'
            }
        )
    );
    
    BX.bind(
        BX('idea-side-button'),
        'click',
        function(){
            JSIdeaDialog.Dialog.SetContent(false, 'idea-loader');
            if(!BX('idea-list-container'))
            {
                BX.ajax.post(
                    window.location.href,
                    {AJAX:'Y', ACTION:'GET_LIST'},
                    function(data)
                    {
                        var div = BX.create('div');
                        div.innerHTML = data;
                        var content = BX.findChild(div, {id:'idea-list-container'}, false, false);
                        JSIdeaDialog.Dialog.SetContent(content, 'idea-list-container');
                        BX.cleanNode(div, true);
                        
                        var IdeaListTabs = BX.findChild(BX('idea-list-container'), {className:'status-item-categoty'}, true, true);
                        if(IdeaListTabs)
                        {
                            for(i=0, n=IdeaListTabs.length; i<n; i++)
                            {
                                if(IdeaListTabs[i].id)
                                {
                                    BX.bind(
                                        BX(IdeaListTabs[i].id),
                                        'click',
                                        function(){
                                            //Hide Selection Menu
                                            var IdeaListTabs = BX.findChild(BX('idea-list-container'), {className:'status-item-selected'}, true, true);
                                            if(IdeaListTabs)
                                            {
                                                for(a=0, n=IdeaListTabs.length; a<n; a++)
                                                {
                                                    if(IdeaListTabs[a].id)
                                                    {
                                                        BX.removeClass(BX(IdeaListTabs[a].id), 'status-item-selected');
                                                        if(!BX.hasClass(BX(IdeaListTabs[a].id), 'status-item'))
                                                            BX.addClass(BX(IdeaListTabs[a].id), 'status-item');
                                                    }
                                                }
                                            }

                                            //Content
                                            var IdeaListContent = BX.findChild(BX('idea-category-list-box'), {className:'idea-category-list'}, true, true);
                                            if(IdeaListContent)
                                            {
                                                //Hide Content
                                                for(j=0, k=IdeaListContent.length; j<k; j++)
                                                    IdeaListContent[j].style.display = 'none';
                                                //Show Content
                                                var ContentNode = BX(this.id + '-content');
                                                if(ContentNode)
                                                    ContentNode.style.display = 'block';

                                                BX.removeClass(BX(this.id), 'status-item');
                                                BX.addClass(BX(this.id), 'status-item-selected');
                                            }
                                        }
                                    );
                                }
                            }
                        }
                        
                        BX.bind(
                            BX('idea-field-common-show-add-form'),
                            'click',
                            function(){
                                JSIdeaDialog.Dialog.SetContent(false, 'idea-loader');
                                if(!BX('idea-editor-container'))
                                {
                                    BX.ajax.post(
                                        window.location.href,
                                        {AJAX:'Y', ACTION:'GET_ADD_FORM'},
                                        function(data)
                                        {
                                            var div = BX.create('div');
                                            div.innerHTML = data;
                                            var content = BX.findChild(div, {id:'idea-list-container'}, false, false);
                                            JSIdeaDialog.Dialog.SetContent(content, 'idea-editor-container');
                                            BX.cleanNode(div, true);
                                        }
                                    );
                                }
                                else
                                    JSIdeaDialog.Dialog.SetContent(BX('idea-editor-container'), 'idea-editor-container');
                                
                                JSIdeaDialog.Dialog.Show();
                            }
                        );
                    }
                );
            }
            else
                JSIdeaDialog.Dialog.SetContent(BX('idea-list-container'), 'idea-list-container');
            
            JSIdeaDialog.Dialog.Show();
        }
    );
});

}