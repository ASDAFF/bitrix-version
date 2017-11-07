BX.namespace("BX.Helper");

BX.Helper =
{
	frameOpenUrl : '',
	frameCloseUrl : '',
	isOpen : false,
	frameNode : null,
	popupNodeWrap : null,
	curtainNode : null,
	popupNode : null,
	closeBtn : null,
	openBtn : null,
	popupLoader : null,
	topBar : null,
	topBarHtml : null,
	langId: null,
	reloadPath: null,
	ajaxUrl: '',
	currentStepId: '',
	notifyBlock : null,
	notifyNum: '',
	notifyText: '',
	notifyId: 0,
	notifyButton: '',
	topPaddingNode : null,
	isAdmin: "N",

	init : function (params)
	{
		this.frameOpenUrl = params.frameOpenUrl || '';
		this.frameCloseUrl = params.frameCloseUrl || '';
		this.helpUpBtnText = params.helpUpBtnText || '';
		this.langId = params.langId || '';
		this.openBtn = params.helpBtn;
		this.notifyBlock = params.notifyBlock;
		this.reloadPath = params.reloadPath || '';
		this.ajaxUrl = params.ajaxUrl || '';
		this.currentStepId = params.currentStepId || '';
		this.notifyData = params.notifyData || null;
		this.runtimeUrl = params.runtimeUrl || null;
		this.notifyUrl = params.notifyUrl || '';
		this.helpUrl = params.helpUrl || '';
		this.notifyNum = params.notifyNum || '';
		this.topPaddingNode = params.topPaddingNode || null;
		this.isAdmin = (params.isAdmin && params.isAdmin == 'Y') ? 'Y' : 'N';

		this.popupLoader = BX.create('div',{
			attrs:{className:'bx-help-popup-loader'},
			children : [BX.create('div', {
				attrs:{className:'bx-help-popup-loader-text'},
				text : BX.message("HELPER_LOADER")
			})]
		});

		this.topBarHtml = '<div class="bx-help-menu-title" onclick="BX.Helper.reloadFrame(\'' + this.reloadPath + '\')">'+BX.message("HELPER_TITLE")+'<span class="bx-help-blue">24</span></div>';

		this.topBar = BX.create('div',{
			attrs:{className:'bx-help-nav-wrap'},
			html : this.topBarHtml
		});

		this.createFrame();
		this.closeBtnHandler();
		this.createPopup();

		BX.bind(this.openBtn, 'click', BX.proxy(this.show, this));
		BX.bind(this.openBtn, 'click', BX.proxy(this.setBlueHeroView, this));

		BX.bind(window, 'message', BX.proxy(function(event)
		{
			event = event || window.event;
			if(typeof(event.data.action) == "undefined")
			{
				if(event.data.height && this.isOpen)
					this.frameNode.style.height = event.data.height + 'px';
				this.insertTopBar(typeof(event.data) == 'object' ? event.data.title : event.data);
				this._showContent();
			}

			if(event.data.action == "CloseHelper")
			{
				this.closePopup();
			}

			if(event.data.action == "ChangeHeight")
			{
				if(event.data.height > 0)
				{
					this.changeHeight(event.data.height);
				}
			}

			if(event.data.action == "SetCounter")
			{
				BX.Helper.showNotification(event.data.num);
			}
		}, this));

		BX.addCustomEvent("onTopPanelCollapse", function(){
			if(BX.Helper.isOpen)
			{
				BX.Helper.show();
			}
		});

		if (params.needCheckNotify == "Y")
		{
			this.checkNotification();
		}
	},

	setBlueHeroView : function()
	{
		if (!this.currentStepId)
			return;

		BX.ajax.post(
			this.ajaxUrl,
			{
				sessid:  BX.bitrix_sessid(),
				action: "setView",
				currentStepId: this.currentStepId
			},
			function() {}
		);
	},

	createFrame : function ()
	{
		this.frameNode = BX.create('iframe', {
			attrs: {
				className: 'bx-help-frame',
				frameborder: 0,
				name: 'help',
				id: 'help-frame'
			}
		});

		BX.bind(this.frameNode, 'load',BX.proxy(function(){
			this.popupNode.scrollTop = 0;
		}, this));
	},

	_showContent : function()
	{
		this.frameNode.style.opacity = 1;

		if(this.topBar.classList)
		{
			this.topBar.classList.add('bx-help-nav-fixed');
			this.topBar.classList.add('bx-help-nav-show');
		}
		else {
			BX.addClass(this.topBar,'bx-help-nav-fixed');
			BX.addClass(this.topBar, 'bx-help-nav-show');
		}

		this.popupLoader.classList.remove('bx-help-popup-loader-show');
	},

	_setPosFixed : function ()
	{
		document.body.style.width = document.body.offsetWidth + 'px';
		document.body.style.overflow = 'hidden';
	},

	_clearPosFixed : function()
	{
		document.body.style.width = 'auto';
		document.body.style.overflow = '';
	},

	closeBtnHandler : function()
	{
		if(this.isAdmin == 'N')
		{
			this.closeBtn = BX.create('div', {
				attrs: {
					className: 'bx-help-close'
				},
				children : [BX.create('div', {attrs: {className: 'bx-help-close-inner'}})]
			});

		}
	},

	insertTopBar : function(node)
	{
		this.topBar.innerHTML= this.topBarHtml + node;
	},

	createPopup : function()
	{
		this.curtainNode = BX.create('div', {
			attrs: {
				"className": 'bx-help-curtain'
			}
		});

		this.popupNode = BX.create('div', {
			children: [
				this.frameNode,
				this.topBar,
				this.popupLoader
			],
			attrs: {
				className: 'bx-help-main'
			}
		});

		BX.bind(this.popupNode, 'click', function(e)
		{
			BX.PreventDefault(e)
		});

		document.body.appendChild(this.curtainNode);
		document.body.appendChild(this.popupNode);

		if(this.isAdmin == 'N')
			document.body.appendChild(this.closeBtn);
	},

	closePopup : function ()
	{
		clearTimeout(this.shadowTimer);
		clearTimeout(this.helpTimer);
		BX.unbind(this.popupNode, 'transitionend', BX.proxy(this.loadFrame, this));

		BX.unbind(document, 'keydown', BX.proxy(this._close, this));
		BX.unbind(document, 'click', BX.proxy(this._close, this));

		if(this.popupNode.style.transition !== undefined)
			BX.bind(this.popupNode, 'transitionend', BX.proxy(this._clearPosFixed, this));
		else
			this._clearPosFixed();


		this.popupNode.style.width = 0;
		this.topBar.style.width = 0;

		BX.removeClass(this.topBar, 'bx-help-nav-fixed');

		if(this.isAdmin == 'N')
			BX.removeClass(this.closeBtn, 'bx-help-close-anim');


		this.topBar.style.top = this.getCord().top + 'px';

		this.helpTimer = setTimeout(BX.proxy(function()
		{
			this.curtainNode.style.opacity = 0;

			if(this.isAdmin == 'N')
				this.closeBtn.style.display = 'none';

			BX.removeClass(this.openBtn, 'help-block-active')

		}, this),500);

		this.shadowTimer = setTimeout(BX.proxy(function()
		{
			this.frameNode.src = this.frameCloseUrl;
			this.popupNode.style.display = 'none';
			this.curtainNode.style.display = 'none';
			this.frameNode.style.opacity = 0;
			this.frameNode.style.height = 0;
			BX.removeClass(this.popupLoader, 'bx-help-popup-loader-show');
			BX.unbind(this.popupNode, 'transitionend', BX.proxy(this._clearPosFixed, this));

			if(this.topBar.classList)
				this.topBar.classList.remove('bx-help-nav-show');
			else
				BX.removeClass(this.topBar, 'bx-help-nav-show');
			this.isOpen = false;

		},this),800);
	},

	showContent : function(additionalParam)
	{
		if (typeof additionalParam === "string")
		{
			this.frameOpenUrl = this.frameOpenUrl + "&" + additionalParam;
		}

		var top = this.getCord().top;
		var right = this.getCord().right;
		clearTimeout(this.shadowTimer);
		clearTimeout(this.helpTimer);

		this._setPosFixed();

		this.curtainNode.style.top = top +'px';
		this.curtainNode.style.width = this.getCord().right + 'px';
		this.curtainNode.style.display = 'block';
		this.popupNode.style.display = 'block';
		this.popupNode.style.paddingTop = top + 'px';
		this.topBar.style.top = top + 'px';
		this.popupLoader.style.top = top + 'px';

		if(this.isAdmin == 'N')
		{
			this.closeBtn.style.top = (top - 63) + 'px';
			this.closeBtn.style.left = (right - 63) + 'px';
			this.closeBtn.style.display = 'block';
		}

		BX.addClass(this.openBtn, 'help-block-active')

		if(this.popupNode.style.transition !== undefined){
			BX.bind(this.popupNode, 'transitionend', BX.proxy(this.loadFrame, this));
		}else {
			this.loadFrame(null);
		}

		this.shadowTimer = setTimeout(BX.proxy(function()
		{
			this.curtainNode.style.opacity = 1;

			BX.addClass(this.closeBtn, 'bx-help-close-anim');

		}, this),25);

		this.helpTimer = setTimeout(BX.proxy(function()
		{
			this.popupNode.style.width = 860 + 'px';
			this.topBar.style.width = 860 + 'px';
			BX.addClass(this.popupLoader, 'bx-help-popup-loader-show');

			BX.bind(document, 'keydown', BX.proxy(this._close, this));
			BX.bind(document, 'click', BX.proxy(this._close, this));
			this.isOpen = true;

		}, this),300);
	},

	show : function(additionalParam)
	{
		var windowScroll = BX.GetWindowScrollPos();
		if (windowScroll.scrollTop !== 0)
		{
			(new BX.easing({
				duration: 500,
				start: {scroll: windowScroll.scrollTop},
				finish: {scroll: 0},
				transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
				step: function (state) {
					window.scrollTo(0, state.scroll);
				},
				complete: BX.proxy(function () {
					this.showContent(additionalParam);
				}, this)
			})).animate();
		}
		else
		{
			this.showContent(additionalParam);
		}
	},

	_close : function(event)
	{
		event = event || window.event;
		BX.PreventDefault(event);

		if(event.type == 'click' || event.keyCode == 27)
			this.closePopup();
	},

	loadFrame : function(event)
	{
		if(event !== null){
			event = event || window.event;
			var target = event.target || event.srcElement;

			if(target == this.popupNode)
				this.frameNode.src = this.frameOpenUrl;
		}else {
			this.frameNode.src = this.frameOpenUrl;
		}
	},

	reloadFrame : function(url)
	{
		this.frameNode.style.opacity = 0;
		this.frameNode.src = url;

		if(this.topBar.classList)
			this.topBar.classList.remove('bx-help-nav-show');
		else
			BX.removeClass(this.topBar, 'bx-help-nav-show');

		this.popupNode.scrollTop = 0;
	},
	getCord : function()
	{
		var pos,
			obj = {top : 0, right : 0};

		if(this.topPaddingNode)
		{
			pos = BX.pos(this.topPaddingNode);
			obj.top = pos.bottom;
			obj.right = pos.right;
		}
		else {
			pos = BX.pos(document.body);
			obj.right = pos.right;
		}

		return obj;
	},

	changeHeight : function(height)
	{
		if(height > 0)
			this.frameNode.style.height = height + 'px';
	},

	showNotification : function(num)
	{
		if (!isNaN(parseFloat(num)) && isFinite(num) && num > 0)
		{
			var numBlock = '<div class="help-block-counter">' + (num > 99 ? '99+' : num) + '</div>';
		}
		else
		{
			numBlock = "";
		}
//		this.notifyBlock.innerHTML = numBlock;

		this.setNotification(num);
	},

	showAnimateHero : function(url)
	{
		if (!url)
			return;

		BX.ajax({
			method : "GET",
			dataType: 'html',
			url: this.helpUrl + url,
			data: {},
			onsuccess: BX.proxy(function(res)
			{
				if (res)
				{
					BX.load([this.runtimeUrl], function () {
						eval(res);
					});
				}
			}, this)
		});
	},

	setNotification : function(num, time)
	{
		BX.ajax({
			method: "POST",
			dataType: 'json',
			url: this.ajaxUrl,
			data:
			{
				sessid:  BX.bitrix_sessid(),
				action: "setNotify",
				num: num,
				time: time
			},
			onsuccess: BX.proxy(function (res) {

			}, this)
		});
	},

	checkNotification : function()
	{
		BX.ajax({
			method : "POST",
			dataType: 'json',
			url: this.notifyUrl,
			data: this.notifyData,
			onsuccess: BX.proxy(function(res)
			{
				if (!isNaN(res.num))
				{
					this.showNotification(res.num);

					if (res.id)
					{
						this.notifyId = res.id;
						this.notifyText = res.body;
						this.notifyButton = res.button;
					}

					if (res.url)
						this.showAnimateHero(res.url);
				}
				else
				{
					this.setNotification('', 'hour');
				}
			}, this),
			onfailure: BX.proxy(function(){
				this.setNotification('', 'hour');
			}, this)
		});
	},

	showAnimatedHero : function()
	{
		if (!BX.browser.IsIE8())
		{
			BX.load(["/bitrix/js/main/helper/runtime.js", "/bitrix/js/main/helper/hero_object.js"], function() {
				var block = BX.create("div", {attrs: {"className": "bx-help-start", "id": "bx-help-start"}});

				if(BX.admin && BX.admin.panel)
				{
					block.style.top = BX.admin.panel.DIV.offsetHeight+50+"px";
				}

				document.body.appendChild(block);
				var stage = new swiffy.Stage(block, swiffyobject, {});
				stage.setBackground(null);

				setTimeout(function(){
					stage.start();
				}, 300);

				setTimeout(function(){
					block.style.display = 'none';
				},7300);
			});
		}
	}
};
