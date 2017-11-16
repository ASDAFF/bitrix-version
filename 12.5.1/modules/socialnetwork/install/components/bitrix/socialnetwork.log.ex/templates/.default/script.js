BX.CLBlock = function(arParams)
{
	this.arData = new Array();
	this.arData["Subscription"] = new Array();
	this.arData["Transport"] = new Array();
	this.UTPopup = null;
	this.aUnSubscribeTransport = false;

	this.entity_type = null;
	this.entity_id = null;
	this.event_id = null;
	this.event_id_fullset = false;
	this.cb_id = null;
	this.t_val = null;
	this.ind = null;
	this.type = null;
}

BX.CLBlock.prototype.DataParser = function(str)
{
	str = str.replace(/^\s+|\s+$/g, '');
	while (str.length > 0 && str.charCodeAt(0) == 65279)
		str = str.substring(1);

	if (str.length <= 0)
		return false;

	if (str.substring(0, 1) != '{' && str.substring(0, 1) != '[' && str.substring(0, 1) != '*')
		str = '"*"';

	eval("arData = " + str);

	return arData;
}

BX.CLBlock.prototype.ShowContentTransport = function()
{
	node_code = this.entity_type + '_' + this.entity_id + '_' + this.event_id + '_' + this.ind;

	if (this.arData["Subscription"][node_code].length <= 0)
		return BX.create('DIV', {
				props: {},
				html: BX.message('sonetLNoSubscriptions')
			});

	var div = BX.create('DIV', {
		props: {
			'className': 'popup-window-content-transport-div'
		}
	} );

	div.appendChild(BX.create('div', {
		props: {
			'className': 'popup-window-content-transport-div-title'
		},
		html: BX.message('sonetLTransportTitle')
	}));
	var table = div.appendChild(BX.create('table', {
		props: {
			'width': '100%'
		}
	}));

	var tbody = table.appendChild(BX.create('tbody', {}));

	if (
		typeof this.arData["Subscription"][node_code]["EVENT"] != 'undefined'
		&& typeof this.arData["Subscription"][node_code]["EVENT"]["TITLE_1"] != 'undefined'
	)
		this.ShowContentTransportRow(tbody, this.arData, node_code, "EVENT");

	if (
		typeof this.arData["Subscription"][node_code]["ALL"] != 'undefined'
		&& typeof this.arData["Subscription"][node_code]["ALL"]["TITLE_1"] != 'undefined'
	)
		this.ShowContentTransportRow(tbody, this.arData, node_code, "ALL");

	if (
		typeof this.arData["Subscription"][node_code]["CB_ALL"] != 'undefined'
		&& typeof this.arData["Subscription"][node_code]["CB_ALL"]["TITLE_1"] != 'undefined'
	)
		this.ShowContentTransportRow(tbody, this.arData, node_code, "CB_ALL");

	return div;
}

BX.CLBlock.prototype.ShowContentTransportRow = function(tbody_ob, arData, node_code, type)
{
	var tr = false;
	var transport_hidden = false;
	var transport_div = false;
	var select = false;
	var is_selected = false;
	var transport_class = false;

	tbody_ob.appendChild(BX.create('tr', {
		props: {},
		children: [
			BX.create('td', {
				attrs: {
					'colspan': '2'
				},
				children: [
					BX.create('div', {
						props: {
							'className': 'popup-window-hr'
						},
						children: [
							BX.create('i', {})
						]
					})
				]
			})
		]
	}));

	tr = tbody_ob.appendChild(BX.create('tr', {
		props: {},
		children: [
			BX.create('td', {
				props: {
					'className': 'popup-window-content-transport-cell-title'
				}
			}),
			BX.create('td', {
				props: {
					'className': 'popup-window-content-transport-cell-control'
				}
			})
		]
	}));

	tr.firstChild.appendChild(BX.create('div', {
			props: {},
			html: arData["Subscription"][node_code][type]["TITLE_1"]
		}));


	transport_hidden = tr.firstChild.nextSibling.appendChild(BX.create('INPUT', {
			props: {
				'name': 't_lr_' + node_code + '_' + type,
				'id': 't_lr_' + node_code + '_' + type,
				'bx-type': type
			},
			attrs: {
				'type': 'hidden'
			}
		}));

	transport_div = tr.firstChild.nextSibling.appendChild(BX.create('DIV', {
			props: {
				'className': 'transport-popup-list-list'
			}
		}));

/*
	select = tr.firstChild.nextSibling.appendChild(BX.create('select', {
			props: {
				'name': 't_lr_' + node_code
			}
		}));
*/

	// inherited
	if (
		arData["Subscription"][node_code][type]["TRANSPORT_INHERITED"]
		&& arData["Subscription"][node_code][type]["TRANSPORT"] != "N"
	)
	{
		for (var i = 0; i < arData["Transport"].length; i++)
		{
			if (arData["Transport"][i]["Key"] == arData["Subscription"][node_code][type]["TRANSPORT"])
			{
				InheritedName = arData["Transport"][i]["Value"];
				break;
			}
		}

		transport_div.appendChild(BX.create('span', {
				props: {
//					'value': 'I',
					'className': 'transport-popup-list-item transport-popup-list-item-selected',
					'bx-option-value': 'I',
					'bx-hidden-id': 't_lr_' + node_code + '_' + type,
					'id': 't_lr_' + node_code + '_' + type + '_I'
//					'selected': true,
//					'defaultSelected': true
				},
				children: [
					BX.create('span', {
						props: {
							'className': 'transport-popup-list-item-left'
						}
					}),
					BX.create('span', {
						props: {
							'className': 'transport-popup-list-item-icon transport-popup-icon-' + arData["Subscription"][node_code][type]["TRANSPORT"]
						}
					}),
					BX.create('span', {
						props: {
							'className': 'transport-popup-list-item-text'
						},
						html: BX.message('sonetLInherited') + ' (' + InheritedName + ')'
					}),
					BX.create('span', {
						props: {
							'className': 'transport-popup-list-item-right'
						}
					})
				],
				'events': {
					'click': BX.delegate(this.OnTransportClick, this)
				}
		}));

		this.SetTransportHidden('t_lr_' + node_code + '_' + type, 'I');
	}

	// all transports
	for (var i = 0; i < arData["Transport"].length; i++)
	{
		if (
			arData["Subscription"][node_code][type]["TRANSPORT"] == arData["Transport"][i]["Key"]
			&&
			(
				!arData["Subscription"][node_code][type]["TRANSPORT_INHERITED"]
				|| arData["Subscription"][node_code][type]["TRANSPORT"] == "N"
			)
		)
		{
			is_selected = true;
			transport_class = 'transport-popup-list-item transport-popup-list-item-selected';
			this.SetTransportHidden('t_lr_' + node_code + '_' + type, arData["Transport"][i]["Key"]);
		}
		else
		{
			is_selected = false;
			transport_class = 'transport-popup-list-item';
		}


		transport_div.appendChild(BX.create('span', {
				props: {
//					'value': arData["Transport"][i]["Key"],
					'className': transport_class,
					'bx-option-value': arData["Transport"][i]["Key"],
					'bx-hidden-id': 't_lr_' + node_code + '_' + type,
					'id': 't_lr_' + node_code + '_' + type + '_' + arData["Transport"][i]["Key"]
//					'selected': true,
//					'defaultSelected': true
				},
				children: [
					BX.create('span', {
						props: {
							'className': 'transport-popup-list-item-left'
						}
					}),
					BX.create('span', {
						props: {
							'className': 'transport-popup-list-item-icon transport-popup-icon-' + arData["Transport"][i]["Key"]
						}
					}),
					BX.create('span', {
						props: {
							'className': 'transport-popup-list-item-text'
						},
						html: arData["Transport"][i]["Value"]
					}),
					BX.create('span', {
						props: {
							'className': 'transport-popup-list-item-right'
						}
					})
				],
				'events': {
					'click': BX.delegate(this.OnTransportClick, this)
				}

		}));

	}
}

BX.CLBlock.prototype.OnTransportClick = function()
{
	var ob = BX.proxy_context;
	this.SetTransportHidden(ob["bx-hidden-id"], ob["bx-option-value"]);

	var arItems = BX.findChildren(ob.parentNode, {'tag':'span'}, false);
	for (var i = 0; i < arItems.length; i++)
	{
		if (arItems[i].id == ob.id)
			BX.addClass(arItems[i], 'transport-popup-list-item-selected');
		else
			BX.removeClass(arItems[i], 'transport-popup-list-item-selected');
	}
}

BX.CLBlock.prototype.SetTransportHidden = function(hidden_id, val)
{
	if (BX(hidden_id))
		BX.adjust(BX(hidden_id), {
			props : {
				'value' : val
			}
		});

//		BX(hidden_id).value = val;
}



BX.CLBlock.prototype.SetTransport = function()
{
	params = 'entity_type=' + this.entity_type + '&entity_id=' + this.entity_id + '&event_id=' + this.event_id + '&cb_id=' + this.cb_id + '&ls=' + this.type + '&transport=' + this.t_newval + '&action=set';
	params += '&site=' + BX.util.urlencode(BX.message('SITE_ID'));

	var sonetLXmlHttpSet2 = new XMLHttpRequest();

	sonetLXmlHttpSet2.open(
		"get",
		BX.message('sonetLSetPath') + "?" + BX.message('sonetLSessid')
			+ "&" + params
			+ "&r=" + Math.floor(Math.random() * 1000)
	);
	sonetLXmlHttpSet2.send(null);

	sonetLXmlHttpSet2.onreadystatechange = function()
	{
		if (sonetLXmlHttpSet2.readyState == 4 && sonetLXmlHttpSet2.status == 200)
		{
			if (sonetLXmlHttpSet2.responseText && sonetLXmlHttpSet2.responseText.replace(/^\'+|\'+$/g,"").length > 0)
			{
				if (typeof sonetEventsErrorDiv != 'undefined' && sonetEventsErrorDiv != null)
				{
					sonetEventsErrorDiv.style.display = "block";
					sonetEventsErrorDiv.innerHTML = sonetEventXmlHttpSet.responseText;
				}
			}
		}
	}
}

BX.CLBlock.prototype.SetTransportFromPopup = function(arObHidden)
{
	if (arObHidden == null)
		return false;

	var params = 'entity_type=' + this.entity_type + '&entity_id=' + this.entity_id + '&event_id=' + this.event_id + '&cb_id=' + this.cb_id + '&action=set_transport_arr';

	for (var i = 0; i < arObHidden.length; i++)
	{
		var obHidden = arObHidden[i];
		if (
			obHidden.value != ""
			&& obHidden.value != null
		)
		params += '&ls_arr['+obHidden["bx-type"]+']=' + obHidden["value"];

	}

	params += '&site=' + BX.util.urlencode(BX.message('SITE_ID'));

	sonetLXmlHttpSet.open(
		"get",
		BX.message('sonetLSetPath') + "?" + BX.message('sonetLSessid')
			+ "&" + params
			+ "&r=" + Math.floor(Math.random() * 1000)
	);
	sonetLXmlHttpSet.send(null);

	sonetLXmlHttpSet.onreadystatechange = function()
	{
		if (sonetLXmlHttpSet.readyState == 4 && sonetLXmlHttpSet.status == 200)
		{
			if (sonetLXmlHttpSet.responseText && sonetLXmlHttpSet.responseText.replace(/^\'+|\'+$/g,"").length > 0)
			{
				if (typeof sonetEventsErrorDiv != 'undefined' && sonetEventsErrorDiv != null)
				{
					sonetEventsErrorDiv.style.display = "block";
					sonetEventsErrorDiv.innerHTML = sonetEventXmlHttpSet.responseText;
				}
			}
		}
	}
}

BX.CLBlock.prototype.onTransportPopupSubmit = function()
{
	var ob = BX.proxy_context;
	var formNode = BX.findParent(ob, {'tag': 'tr', 'className': 'popup-window-content-row'});
	var arItems = BX.findChildren(formNode, {'tag':'input', 'attr': {'type': 'hidden'}}, true);
	this.SetTransportFromPopup(arItems);
	this.TransportPopup.destroy();
}

function __logFilterShow()
{
	if (BX('bx_sl_filter').style.display == 'none')
	{
		BX('bx_sl_filter').style.display = 'block';
		BX('bx_sl_filter_hidden').style.display = 'none';
	}
	else
	{
		BX('bx_sl_filter').style.display = 'none';
		BX('bx_sl_filter_hidden').style.display = 'block';
	}
}

__logShowTransportDialog = function(ind, entity_type, entity_id, event_id, event_id_fullset, cb_id)
{
	if (BX.PopupMenu && BX.PopupMenu.Data["post-menu-" + ind])
		BX.PopupMenu.Data["post-menu-" + ind].popupWindow.close();

	var submitButton = new BX.PopupWindowButton(
		{
			'text': BX.message('sonetLDialogSubmit'),
			'className' : 'popup-window-button-accept',
			'id': 'bx_log_transport_popup_submit'
		}
	);

	var cancelButton = new BX.PopupWindowButtonLink(
		{
			'text': BX.message('sonetLDialogCancel'),
			'className' : 'popup-window-button-link-cancel',
			'id': 'bx_log_transport_popup_cancel'
		}
	);

	var popup = BX.PopupWindowManager.create(
		'bx_log_transport_popup',
		false,
		{
			closeIcon : true,
			offsetTop: 2,
			autoHide: true,
			buttons: [submitButton, cancelButton]
		}
	);

	BX.bind(BX('bx_log_transport_popup_submit'), "click", BX.delegate(LBlock.onTransportPopupSubmit, LBlock));
	BX.bind(BX('bx_log_transport_popup_cancel'), "click", BX.delegate(popup.close, popup));

	LBlock.entity_type = entity_type;
	LBlock.entity_id = entity_id;
	LBlock.event_id = event_id;
	if (event_id_fullset)
		LBlock.event_id_fullset = event_id_fullset;
	LBlock.cb_id = cb_id;
	LBlock.ind = ind;

	LBlock.TransportPopup = popup;

	if (
		entity_type != null
		&& entity_type != false
		&& entity_id != null
		&& entity_id != false
		&& event_id != null
		&& event_id != false
	)
	{

		var params = BX.message('sonetLGetPath') + "?" + BX.message('sonetLSessid')
			+ "&action=get_data"
			+ "&lang=" + BX.util.urlencode(BX.message('sonetLLangId'))
			+ "&site=" + BX.util.urlencode(BX.message('sonetLSiteId'))
			+ "&et=" + BX.util.urlencode(entity_type)
			+ "&eid=" + BX.util.urlencode(entity_id)
			+ "&evid=" + BX.util.urlencode(event_id)
			+ "&r=" + Math.floor(Math.random() * 1000);

		if (
			cb_id != null
			&& cb_id != false
		)
			params += "&cb_id=" + BX.util.urlencode(cb_id);

		sonetLXmlHttpGet.open(
			"get",
			params
		);
		sonetLXmlHttpGet.send(null);

		sonetLXmlHttpGet.onreadystatechange = function()
		{
			if (sonetLXmlHttpGet.readyState == 4 && sonetLXmlHttpGet.status == 200)
			{
				var data = LBlock.DataParser(sonetLXmlHttpGet.responseText);
				if (typeof(data) == "object")
				{
					if (data[0] == '*')
					{
						if (sonetLErrorDiv != null)
						{
							sonetLErrorDiv.style.display = "block";
							sonetLErrorDiv.innerHTML = sonetLXmlHttpSet.responseText;
						}
						return;
					}
					sonetLXmlHttpGet.abort();
					LBlock.arData["Subscription"][entity_type + '_' + entity_id + '_' + event_id + '_' + ind] = data["Subscription"];

					if (
						typeof LBlock.arData["Transport"] == 'undefined'
						|| LBlock.arData["Transport"].length <= 0
					)
						LBlock.arData["Transport"] = data["Transport"];

					if (popup.bindElementPos != null)
					{
						popup.setBindElement(BX('sonet_log_transport_' + ind));
						BX.cleanNode(popup.contentContainer);
					}

					var content = LBlock.ShowContentTransport(entity_type, entity_id, event_id, cb_id, ind);
					popup.setContent(content);
					popup.show();
				}
			}
		}
	}

}

if (!window.XMLHttpRequest)
{
	var XMLHttpRequest = function()
	{
		try { return new ActiveXObject("MSXML3.XMLHTTP") } catch(e) {}
		try { return new ActiveXObject("MSXML2.XMLHTTP.3.0") } catch(e) {}
		try { return new ActiveXObject("MSXML2.XMLHTTP") } catch(e) {}
		try { return new ActiveXObject("Microsoft.XMLHTTP") } catch(e) {}
	}
}

var sonetLXmlHttpGet = new XMLHttpRequest();
var sonetLXmlHttpSet = new XMLHttpRequest();

var LBlock = new BX.CLBlock();

function WriteMicroblog(val)
{
	var blink = document.getElementById('microblog-link');
	var bform = document.getElementById('microblog-form');
	
	if (
		bform == 'undefined' || !bform
		|| blink == 'undefined' || !blink
	)
		return false;

	if(val)
	{
		bform.style.display = "block";
		bform.style.overflow = 'hidden';
		blink.style.display = "none";
		bform.style.height = '33px';
		bform.style.opacity = 0.1;

		(new BX.easing({
			duration : 200,
			start : { opacity : 10, height : 33},
			finish : { opacity: 100, height : bform.scrollHeight},
			transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
			step : function(state){
				bform.style.height = state.height + "px";
				bform.style.opacity = state.opacity / 100;
			},
			complete : function(){
				BX.onCustomEvent(BX('microblog-form'), 'onFormShow');
				bform.style.height = 'auto';
			}
		})).animate();
	}
	else
	{

		blink.style.display = "block";
		bform.style.display = "none";
		
		(new BX.easing({
			duration : 200,
			start : { opacity: 100, height : bform.scrollHeight},
			finish : { opacity : 0, height : 0},
			transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
			step : function(state){
				bform.style.height = state.height + "px";
				bform.style.opacity = state.opacity / 100;
			}
		})).animate();
	}

	BX.onCustomEvent(
		'OnWriteMicroblog',
		[ val ]
	);
	
}

function __logOnAjaxInsertToNode(params) 
{
	var arPos = false;

	if (BX('sonet_log_more_container'))
	{
		nodeTmp1 = BX.findChild(BX('sonet_log_more_container'), {'tag':'span', 'className': 'feed-new-message-inf-text'}, false);
		nodeTmp2 = BX.findChild(BX('sonet_log_more_container'), {'tag':'span', 'className': 'feed-new-message-inf-text-waiting'}, false);
		if (nodeTmp1 && nodeTmp2)
		{
			nodeTmp1.style.display = 'none';
			nodeTmp2.style.display = 'inline';
		}
		arPos = BX.pos(BX('sonet_log_more_container'));
		nodeTmp1Cap = document.body.appendChild(BX.create('div', {
			style: {
				position: 'absolute',
				width: arPos.width + 'px',
				height: arPos.height + 'px',
				top: arPos.top + 'px',
				left: arPos.left + 'px',
				zIndex: 1000
			}
		}));
	}

	if (BX('sonet_log_counter_2_container'))
	{
		nodeTmp1 = BX.findChild(BX('sonet_log_counter_2_container'), {'tag':'span', 'className': 'feed-new-message-inf-text'}, false);
		nodeTmp2 = BX.findChild(BX('sonet_log_counter_2_container'), {'tag':'span', 'className': 'feed-new-message-inf-text-waiting'}, false);

		if (nodeTmp1 && nodeTmp2)
		{
			nodeTmp1.style.display = 'none';
			nodeTmp2.style.display = 'inline';
		}
		arPos = BX.pos(BX('sonet_log_more_container'));
		nodeTmp2Cap = document.body.appendChild(BX.create('div', {
			style: {
				position: 'absolute',
				width: arPos.width + 'px',
				height: arPos.height + 'px',
				top: arPos.top + 'px',
				left: arPos.left + 'px',
				zIndex: 1000
			}
		}));
	}

	BX.unbind(BX('sonet_log_counter_2_container'), 'click', __logOnAjaxInsertToNode);
}

function sonetLClearContainerExternalNew()
{
	logAjaxMode = 'new';
	BX.addCustomEvent('onAjaxSuccess', _sonetLClearContainerExternal);
}

function sonetLClearContainerExternalMore()
{
	logAjaxMode = 'more';
	BX.addCustomEvent('onAjaxSuccess', _sonetLClearContainerExternal);
}

function _sonetLClearContainerExternal(mode)
{
	if (BX('sonet_log_more_container'))
	{
		nodeTmp1 = BX.findChild(BX('sonet_log_more_container'), {'tag':'span', 'className': 'feed-new-message-inf-text'}, false);
		nodeTmp2 = BX.findChild(BX('sonet_log_more_container'), {'tag':'span', 'className': 'feed-new-message-inf-text-waiting'}, false);
		if (nodeTmp1 && nodeTmp2)
		{
			nodeTmp1.style.display = 'inline';
			nodeTmp2.style.display = 'none';
		}
	}

	if (BX('sonet_log_counter_2_container'))
	{
		BX("sonet_log_counter_2_container").style.display = "none";
		nodeTmp1 = BX.findChild(BX('sonet_log_counter_2_container'), {'tag':'span', 'className': 'feed-new-message-inf-text'}, false);
		nodeTmp2 = BX.findChild(BX('sonet_log_counter_2_container'), {'tag':'span', 'className': 'feed-new-message-inf-text-waiting'}, false);

		if (nodeTmp1 && nodeTmp2)
		{
			nodeTmp1.style.display = 'inline';
			nodeTmp2.style.display = 'none';
		}
	}

	if (nodeTmp1Cap && nodeTmp1Cap.parentNode)
		nodeTmp1Cap.parentNode.removeChild(nodeTmp1Cap);
	if (nodeTmp2Cap && nodeTmp2Cap.parentNode)
		nodeTmp2Cap.parentNode.removeChild(nodeTmp2Cap);

	if (BX("sonet_log_counter_preset") && logAjaxMode == 'new')
		BX("sonet_log_counter_preset").style.display = "none";

	BX.removeCustomEvent('onAjaxSuccess', _sonetLClearContainerExternal);
}

function __logChangeCounter(count)
{
	if (parseInt(count) > 0)
	{
		if (BX("sonet_log_counter_2"))
			BX("sonet_log_counter_2").innerHTML = count;
		if (BX("sonet_log_counter_2_container"))
			BX("sonet_log_counter_2_container").style.display = "block";
	}
	else
	{
		if (BX("sonet_log_counter_2_container"))
			BX("sonet_log_counter_2_container").style.display = "none";
		if (BX("sonet_log_counter_2"))
			BX("sonet_log_counter_2").innerHTML = "0";
	}
}

function __logChangeCounterArray(arCount)
{
	if (typeof arCount[BX.message('sonetLCounterType')] != 'undefined')
		__logChangeCounter(arCount[BX.message('sonetLCounterType')]);
}

function __logShowPostMenu(bindElement, ind, entity_type, entity_id, event_id, fullset_event_id, user_id, log_id, bFavorites)
{
	var arItems = [
		{ text : (bFavorites ? BX.message('sonetLMenuFavoritesTitleY') : BX.message('sonetLMenuFavoritesTitleN')), className : "menu-popup-no-icon", onclick : function(e) { __logChangeFavorites(log_id); return BX.PreventDefault(e); } },
		{ text : BX.message('sonetLMenuTransportTitle'), className : "menu-popup-no-icon", onclick : function(e) { __logShowTransportDialog(ind, entity_type, entity_id, event_id, fullset_event_id, user_id); return BX.PreventDefault(e); } }
	];

	if (BX.message('sonetLIsB24') == "Y")
		var arParams = {
			offsetLeft: -32,
			offsetTop: 4,
			lightShadow: false,
			angle: {position: 'top', offset : 93}
		};
	else
		var arParams = {
			offsetLeft: -32,
			offsetTop: 4,
			lightShadow: false
		};

	BX.PopupMenu.show("post-menu-" + ind, bindElement, arItems, arParams);
}

function __logCommentFormAutogrow(el)
{
	var placeNodeoffsetHeightOld = 0;

	if (el && BX.type.isDomNode(el))
		var textarea = el;
	else
	{
		var textarea = BX.proxy_context;
		var event = el || window.event;

		if ((event.keyCode == 13 || event.keyCode == 10) && event.ctrlKey)
			__logCommentAdd();
	}

	var placeNode = BX.findParent(textarea, {'className': 'sonet-log-comment-form-place'});
	if (BX(placeNode))
		placeNodeoffsetHeightOld = BX(placeNode).offsetHeight;

	var linesCount = 0;
	var lines = textarea.value.split('\n');

	for (var i=lines.length-1; i>=0; --i)
		linesCount += Math.floor((lines[i].length / CommentFormColsDefault) + 1);

	if (linesCount >= CommentFormRowsDefault)
		textarea.rows = linesCount + 1;
	else
		textarea.rows = CommentFormRowsDefault;
}

function __logGetNextPage(more_url)
{
	BX.ajax({
		url: more_url,
		method: 'GET',
		dataType: 'html',
		data: { },
		onsuccess: function(data) {
			BX.onCustomEvent(window, 'onSocNetLogMoveBody', ['sonet_log_content_xxx', 'log_external_container']); // fake ids
			BX.cleanNode('sonet_log_more_container', true);
			BX('log_internal_container').appendChild(BX.create('DIV', {props: { className: 'feed-wrap' }, html: data}));
		},
		onfailure: function(data) { }
	});

	return false;
}

function __logRefresh(refresh_url)
{
	BX.ajax({
		url: refresh_url,
		method: 'GET',
		dataType: 'html',
		data: { },
		onsuccess: function(data) {
			BX.cleanNode('log_internal_container', false);
			BX('log_internal_container').appendChild(BX.create('DIV', {props: { className: 'feed-wrap' }, html: data}));		
		},
		onfailure: function(data) { }
	});

	return false;
}

function __logChangeFavorites(log_id)
{
	var node = BX.proxy_context;

	if (!log_id)
		return;

	var sonetLXmlHttpSet5 = new XMLHttpRequest();

	sonetLXmlHttpSet5.open("POST", BX.message('sonetLESetPath'), true);
	sonetLXmlHttpSet5.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

	sonetLXmlHttpSet5.onreadystatechange = function()
	{
		if(sonetLXmlHttpSet5.readyState == 4)
		{
			if(sonetLXmlHttpSet5.status == 200)
			{
				var data = LBlock.DataParser(sonetLXmlHttpSet5.responseText);
				if (typeof(data) == "object")
				{
					if (data[0] == '*')
					{
						if (sonetLErrorDiv != null)
						{
							sonetLErrorDiv.style.display = "block";
							sonetLErrorDiv.innerHTML = sonetLXmlHttpSet5.responseText;
						}
						return;
					}
					sonetLXmlHttpSet5.abort();

					var strMessage = '';

					if (data["bResult"] != 'undefined' && (data["bResult"] == "Y" || data["bResult"] == "N"))
					{
						if (BX.hasClass(BX(node), 'menu-popup-item-text'))
							var nodeToAdjust = BX(node);
						else
							var nodeToAdjust = BX.findChild(BX(node), { 'className': 'menu-popup-item-text' });

						if (BX(nodeToAdjust))
							BX.adjust(BX(nodeToAdjust), {html: (data["bResult"] == "Y" ? BX.message('sonetLMenuFavoritesTitleY'): BX.message('sonetLMenuFavoritesTitleN'))} )
					}
				}
			}
			else
			{
				// error!
			}
		}
	}

	sonetLXmlHttpSet5.send("r=" + Math.floor(Math.random() * 1000)
		+ "&" + BX.message('sonetLSessid')
		+ "&site=" + BX.util.urlencode(BX.message('sonetLSiteId'))
		+ "&log_id=" + encodeURIComponent(log_id)
		+ "&action=change_favorites"
	);

}