var ECUserControll = function(Params)
{
	this.oEC = Params.oEC;
	var _this = this;
	this.count = 0;
	this.countAgr = 0;
	this.countDec = 0;

	this.bEditMode = Params.view !== true;
	this.pAttendeesCont = Params.AttendeesCont;
	this.pAttendeesList = Params.AttendeesList;
	this.pParamsCont = Params.AdditionalParams;
	this.pSummary = Params.SummaryCont;
	//this.pOwner = Params.SummaryCont;

	//this.pSummary
	this.pCount = this.pSummary.appendChild(BX.create("A", {props: {className: 'bxc-count', href:"javascript:void(0)"}}));
	this.pCountArg = this.pSummary.appendChild(BX.create("A", {props: {className: 'bxc-count-agr', href:"javascript:void(0)"}}));
	this.pCountDec = this.pSummary.appendChild(BX.create("A", {props: {className: 'bxc-count-dec', href:"javascript:void(0)"}}));

	this.pCount.onclick = function(){_this.ListMode('all');};
	this.pCountArg.onclick = function(){_this.ListMode('agree');};
	this.pCountDec.onclick = function(){_this.ListMode('decline');};

	this._getFromDate = (Params.fromDateGetter && typeof Params.fromDateGetter == 'function') ? Params.fromDateGetter : function(){return false;};
	this._getToDate = (Params.toDateGetter && typeof Params.toDateGetter == 'function') ? Params.toDateGetter : function(){return false;};
	this._getEventId = (Params.eventIdGetter && typeof Params.eventIdGetter == 'function') ? Params.eventIdGetter : function(){return false;};

	this.ListMode('all');
	this.Attendees = {};

	// Only if we need to add or delete users
	if (this.bEditMode)
	{
		this.pLinkCont = Params.AddLinkCont;
		var
			pIcon = this.pLinkCont.appendChild(BX.create("I")),
			pTitle = this.pLinkCont.appendChild(BX.create("SPAN", {text: EC_MESS.AddAttendees}));
		pIcon.onclick = pTitle.onclick = BX.proxy(this.OpenSelectUser, this);

		var arMenuItems = [{text : EC_MESS.AddGuestsDef, onclick: BX.proxy(this.OpenSelectUser, this)}];
		if (!this.oEC.bExtranet && this.oEC.type == 'group')
			arMenuItems.push({text : EC_MESS.AddGroupMemb, title: EC_MESS.AddGroupMembTitle, onclick: BX.proxy(this.oEC.AddGroupMembers, this.oEC)});
		//arMenuItems.push({text : EC_MESS.AddGuestsEmail,onclick: BX.proxy(this.AddByEmail, this)});

		if (arMenuItems.length > 1)
		{
			pMore = this.pLinkCont.appendChild(BX.create("A", {props: {href: 'javascript: void(0);', className: 'bxec-add-more'}}));
			pMore.onclick = function()
			{
				BX.PopupMenu.show('bxec_add_guest_menu', _this.pLinkCont, arMenuItems, {events: {onPopupClose: function() {BX.removeClass(pMore, "bxec-add-more-over");}}});
				BX.addClass(pMore, "bxec-add-more-over");
			};
		}

		BX.addCustomEvent(window, "onUserSelectorOnChange", BX.proxy(this.UserOnChange, this));
	}
}

ECUserControll.prototype = {
SetValues: function(Attendees)
{
	var i, l = Attendees.length, User;

	// Clear list
	BX.cleanNode(this.pAttendeesList);
	this.Attendees = {};
	this.count = 0;
	this.countAgr = 0;
	this.countDec = 0;

	for(i = 0; i < l; i++)
	{
		User = Attendees[i];
		User.key = User.id || User.email;
		if (User && User.key && !this.Attendees[User.key])
			this.DisplayAttendee(User);
	}

	if (this.bEditMode)
	{
		this.DisableUserOnChange(true, true);
		O_BXCalUserSelect.setSelected(Attendees);
	}

	this.UpdateCount();
},

GetValues: function()
{
	// for (var key in this.Attendees)
	// {
	// }

	return this.Attendees;
},

SetEmpty: function(bEmpty)
{
	if (this.bEmpty === bEmpty)
		return;

	BX.onCustomEvent(this, 'SetEmpty', [bEmpty]);

	if (bEmpty)
	{
		BX.addClass(this.pAttendeesCont, 'bxc-att-empty');
		if (this.pParamsCont)
			this.pParamsCont.style.display = 'none';
	}
	else
	{
		BX.removeClass(this.pAttendeesCont, 'bxc-att-empty');
		if (this.pParamsCont)
			this.pParamsCont.style.display = '';
	}
	this.bEmpty = bEmpty;
},

UpdateCount: function()
{
	this.pCount.innerHTML = EC_MESS.AttSumm + ' - ' + (parseInt(this.count) || 0);

	if (this.countAgr > 0)
	{
		this.pCountArg.innerHTML = EC_MESS.AttAgr + ' - ' + parseInt(this.countAgr);
		this.pCountArg.style.display = "";
	}
	else
	{
		this.pCountArg.style.display = "none";
	}

	if (this.countDec > 0)
	{
		this.pCountDec.innerHTML = EC_MESS.AttDec + ' - ' + parseInt(this.countDec);
		this.pCountDec.style.display = "";
	}
	else
	{
		this.pCountDec.style.display = "none";
	}

	this.SetEmpty(this.count == 0);
},

OpenSelectUser : function(e)
{
	if (BX.PopupMenu && BX.PopupMenu.currentItem)
		BX.PopupMenu.currentItem.popupWindow.close();

	if(!e) e = window.event;
	if (!this.SelectUserPopup)
	{
		var _this = this;
		this.SelectUserPopup = BX.PopupWindowManager.create("bxc-user-popup", this.pLinkCont, {
			offsetTop : 1,
			autoHide : true,
			closeByEsc : true,
			content : BX("BXCalUserSelect_selector_content"),
			className: 'bxc-popup-user-select',
			buttons: [
				new BX.PopupWindowButton({
					text: EC_MESS.Add,
					events: {click : function()
					{
						_this.SelectUserPopup.close();
						for (var id in _this.selectedUsers)
						{
							id = parseInt(id);
							if (!isNaN(id) && id > 0)
							{

								if (!_this.Attendees[id] && _this.selectedUsers[id]) // Add new user
								{
									_this.selectedUsers[id].key = id;
									_this.DisplayAttendee(_this.selectedUsers[id]);
								}
								else if(_this.Attendees[id] && !_this.selectedUsers[id]) // Del user from our list
								{
									_this.RemoveAttendee(id, false);
								}
							}
						}

						BX.onCustomEvent(_this, 'UserOnChange');
						_this.UpdateCount();
					}}
				}),
				new BX.PopupWindowButtonLink({
					text: EC_MESS.Close,
					className: "popup-window-button-link-cancel",
					events: {click : function(){_this.SelectUserPopup.close();}}
				})
			]
		});
	}

	// Clean
	if (this.bEditMode)
	{
		this.selectedUsers = {};
		var Attendees = [], k;
		for (k in this.Attendees)
		{
			if (this.Attendees[k] && this.Attendees[k].type != 'ext')
				Attendees.push(this.Attendees[k].User);
		}
		O_BXCalUserSelect.setSelected(Attendees);
	}

	this.SelectUserPopup.show();
	BX.PreventDefault(e);
},

AddByEmail : function(e)
{
	var _this = this;
	if (BX.PopupMenu && BX.PopupMenu.currentItem)
		BX.PopupMenu.currentItem.popupWindow.close();

	if(!e) e = window.event;
	if (!this.EmailPopup)
	{
		var pDiv = BX.create("DIV", {props:{className: 'bxc-email-cont'}, html: '<label class="bxc-email-label">' + EC_MESS.UserEmail + ':</label>'});
		this.pEmailValue = pDiv.appendChild(BX.create('INPUT', {props: {className: 'bxc-email-input'}}));

		this.EmailPopup = BX.PopupWindowManager.create("bxc-user-popup-email", this.pLinkCont, {
			offsetTop : 1,
			autoHide : true,
			content : pDiv,
			className: 'bxc-popup-user-select-email',
			closeIcon: { right : "12px", top : "5px"},
			closeByEsc : true,
			buttons: [
			new BX.PopupWindowButton({
				text: EC_MESS.Add,
				className: "popup-window-button-accept",
				events: {click : function(){
					var email = BX.util.trim(_this.pEmailValue.value);
					if (email != "" && !_this.Attendees[email])
					{
						var User = {name: email, key: email, type: 'ext', status: 'Y'};
						_this.DisplayAttendee(User);
						_this.UpdateCount();
					}
					_this.EmailPopup.close();
				}}
			}),
			new BX.PopupWindowButtonLink({
				text: EC_MESS.Close,
				className: "popup-window-button-link-cancel",
				events: {click : function(){_this.EmailPopup.close();}}
			})
		]
		});
	}

	this.EmailPopup.show();
	setTimeout(function(){BX.focus(_this.pEmailValue);}, 50);
	BX.PreventDefault(e);
},

DisableUserOnChange: function(bDisable, bTime)
{
	this.bDisableUserOnChange = bDisable === true;
	if (bTime)
		setTimeout(BX.proxy(this.DisableUserOnChange, this), 300);
},

UserOnChange: function(arUsers)
{
	if (this.bDisableUserOnChange)
		return;

	this.selectedUsers = arUsers;
},

DisplayAttendee: function(User, bUpdate)
{
	this.count++;
	if (User.status == 'Y')
		this.countAgr++;
	else if (User.status == 'N')
		this.countDec++;
	else
		User.status = 'Q';

	if (bUpdate && User.id && this.Attendees[User.id])
	{
		// ?
	}
	else
	{
		var
			_this = this,
			pBusyInfo = false,
			status = User.status.toLowerCase(),
			pRow = this.pAttendeesList.appendChild(BX.create("SPAN", {props:{className: 'bxc-attendee-row bxc-att-row-' + status}})),
			pStatus = pRow.appendChild(BX.create("I", {props: {className: 'bxc-stat-' + status, title: EC_MESS['GuestStatus_' + status] + (User.desc ? ' - ' + User.desc : '')}}));

		if (User.type == 'ext')
			pName = pRow.appendChild(BX.create("span", {props:{className: "bxc-name"}, text: (User.name || User.email)}));
		else
			pName = pRow.appendChild(BX.create("A", {props:{href: this.oEC.GetUserHref(User.id), className: "bxc-name"}, text: User.name}));


		if (this.bEditMode && User.type != 'ext')
			pBusyInfo = pRow.appendChild(BX.create("SPAN", {props:{className: "bxc-busy"}}));
		pRow.appendChild(BX.create("SPAN", {props: {className: "bxc-comma"}, html: ','}));

		if (this.bEditMode)
		{
			pRow.appendChild(BX.create("A", {props: {id: 'bxc-att-key-' + User.key, href: 'javascript:void(0)', title: EC_MESS.Delete, className: 'bxc-del-att'}})).onclick = function(e){_this.RemoveAttendee(this.id.substr('bxc-att-key-'.length)); return BX.PreventDefault(e || window.event)};
		}

		this.Attendees[User.key] = {
			User : User,
			pRow: pRow,
			pBusyCont: pBusyInfo
		};
	}
},

RemoveAttendee: function(key, bAffectToControl)
{
	bAffectToControl = bAffectToControl !== false;

	if (this.Attendees[key])
	{
		this.Attendees[key].pRow.parentNode.removeChild(this.Attendees[key].pRow);

		if (this.Attendees[key].User.status == 'Y')
			this.countAgr--;
		if (this.Attendees[key].User.status == 'N')
			this.countDec--;
		this.count--;

		this.Attendees[key] = null;
		delete this.Attendees[key];

		if (this.bEditMode)
		{
			var Attendees = [];
			for (k in this.Attendees)
			{
				if (this.Attendees[k] && this.Attendees[k].type != 'ext')
					Attendees.push(this.Attendees[k].User);
			}

			this.DisableUserOnChange(true, true);

			if (bAffectToControl)
				O_BXCalUserSelect.setSelected(Attendees);
		}
	}

	this.UpdateCount();
},

ListMode: function(mode)
{
	if (this.mode == mode)
		return;

	if (this.mode) // In start
	{
		BX.removeClass(this.pAttendeesList, 'bxc-users-mode-' + this.mode);
		BX.removeClass(this.pSummary, 'bxc-users-mode-' + this.mode);
	}

	this.mode = mode;
	BX.addClass(this.pAttendeesList, 'bxc-users-mode-' + this.mode);
	BX.addClass(this.pSummary, 'bxc-users-mode-' + this.mode);
},

CheckAccessibility : function(Params, timeout)
{
	if (this.check_access_timeout)
		this.check_access_timeout = clearTimeout(this.check_access_timeout);

	var
		bTimeout = timeout > 0,
		_this = this;

	if (bTimeout)
	{
		this.check_access_timeout = setTimeout(function(){_this.CheckAccessibility(Params, 0);}, timeout);
		return;
	}

	var
		attendees = [],
		values = this.GetValues(),
		eventId = parseInt(this._getEventId()),
		fd = this._getFromDate(),
		td = this._getToDate();

	for(id in values)
		attendees.push(id);

	if (!fd || attendees.length <= 0)
		return false;

	var reqData = {
		event_id : eventId,
		attendees : attendees,
		from: BX.date.getServerTimestamp(fd.getTime())
	};

	if (td)
		reqData.to = BX.date.getServerTimestamp(td.getTime());

	this.oEC.Request({
		postData: this.oEC.GetReqData('check_guests', reqData),
		handler: function(oRes)
		{
			if (!oRes)
				return false;

			if (oRes.data)
			{
				var id, acc, pBusyCont;
				for (id in oRes.data)
				{
					if (!_this.Attendees[id])
						continue;
					acc = oRes.data[id];
					pBusyCont = _this.Attendees[id].pBusyCont;
					if (acc &&  pBusyCont && EC_MESS['Acc_' + acc])
					{
						pBusyCont.innerHTML = '(' + EC_MESS['Acc_' + acc] + ')';
						pBusyCont.title = EC_MESS.UserAccessibility;
						pBusyCont.style.display = '';
					}
					else
					{
						pBusyCont.style.display = 'none';
					}
				}
			}
			return true;
		}
	});
}
}

var ECBanner = function(oEC)
{
	var _this = this;
	this.oEC = oEC;

	this.pWnd = BX(this.oEC.id + 'banner');
	this.pWnd.onmouseover = function(){if(_this._sect_over_timeout){clearInterval(_this._sect_over_timeout);} BX.addClass(_this.pWnd, 'bxec-hover');};
	this.pWnd.onmouseout = function(){_this._sect_over_timeout = setTimeout(function(){BX.removeClass(_this.pWnd, 'bxec-hover');}, 100);};

	BX(this.oEC.id + '_ban_close').onclick = function(){_this.Close(); return false;};

	if (this.oEC.bIntranet)
	{
		this.pOutlSel = BX(oEC.id + '_outl_sel');
		if (this.pOutlSel && this.pOutlSel.parentNode)
		{
			this.pOutlSel.parentNode.onclick = function(){_this.ShowPopup('outlook');};
			this.pOutlSel.onmouseover = function(){BX.addClass(this, "bxec-ban-over");};
			this.pOutlSel.onmouseout = function(){BX.removeClass(this, "bxec-ban-over");};
		}
	}

	if (this.oEC.bCalDAV)
	{
		this.pMobSel = BX(oEC.id + '_mob_sel');
		if (this.pMobSel && this.pMobSel.parentNode)
		{
			this.pMobSel.parentNode.onclick = function(){_this.ShowPopup('mobile');};
			this.pMobSel.onmouseover = function(){BX.addClass(this, "bxec-ban-over");};
			this.pMobSel.onmouseout = function(){BX.removeClass(this, "bxec-ban-over");};
		}
	}

	if (this.oEC.arConfig.bExchange)
	{
		var pLink = BX(oEC.id + '_exch_sync');
		if (pLink)
			pLink.onclick = function(){_this.oEC.SyncExchange();return false;};
	}

	this.Popup = {};

	if (!window.jsOutlookUtils)
		return BX.loadScript('/bitrix/js/calendar/outlook.js', _this.outlookRun);
}

ECBanner.prototype =
{
	ShowPopup: function(type)
	{
		var _this = this;
		if (!this.Popup[type])
			this.CreatePopup(type);

		if (this.Popup[type].bShowed)
			return this.ClosePopup(type);

		this.ClosePopup(type);
		var pWnd = this.Popup[type].pWin.Get();
		this.Popup[type].bShowed = true;

		var
			rowsCount = 0,
			i, l = this.oEC.arSections.length, cal, name, pItem;

		BX.cleanNode(pWnd);

		if (type == 'mobile')
		{
			rowsCount++;
			pItem = pWnd.appendChild(BX.create("DIV", {
				props: {id: 'ecpp_all', title: EC_MESS.AllCalendars},
				style: {backgroundColor: '#F2F8D6'},
				text: EC_MESS.AllCalendars,
				events: {
					mouseover: function(){BX.addClass(this, 'bxec-over');},
					mouseout: function(){BX.removeClass(this, 'bxec-over');}
				}
			}));

			pItem.onclick = function()
			{
				_this.RunMobile(this.id.substr('ecpp_'.length));
				_this.ClosePopup();
			}
		}

		for (i = 0; i < l; i++)
		{
			cal = this.oEC.arSections[i];
			if (!this.oEC.IsCurrentViewSect(cal))
				continue;

			if(type == 'outlook' && !cal.OUTLOOK_JS)
				continue;

			rowsCount++;
			pItem = pWnd.appendChild(BX.create("DIV", {
				props: {id: 'ecpp_' + cal.ID, title: cal.NAME, className: 'bxec-text-overflow' + (cal.bDark ? ' bxec-dark' : '')},
				style: {backgroundColor: cal.COLOR},
				text: cal.NAME,
				events: {
					mouseover: function(){BX.addClass(this, 'bxec-over');},
					mouseout: function(){BX.removeClass(this, 'bxec-over');}
				}
			}));

			if (type == 'outlook')
			{
				pItem.onclick = function()
				{
					_this.RunOutlook(this.id.substr('ecpp_'.length));
					_this.ClosePopup();
				}
			}
			else if (type == 'mobile')
			{
				pItem.onclick = function()
				{
					_this.RunMobile(this.id.substr('ecpp_'.length));
					_this.ClosePopup();
				}
			}
		}

		// Add events
		if (!this.bCloseEventsAttached)
		{
			BX.bind(document, "keyup", BX.proxy(this.OnKeyUp, this));
			setTimeout(function()
			{
				_this.bPreventClickClosing = false;
				BX.bind(document, "click", BX.proxy(_this.ClosePopup, _this));
			}, 100);
			this.bCloseEventsAttached = true;
		}

		var pos = BX.pos(this.Popup[type].pSel);
		this.Popup[type].pWin.Show(true); // Show window
		pWnd.style.width = '200px';
		pWnd.style.height = '';

		// Set start position
		pWnd.style.left = (pos.left + 0) + 'px';
		pWnd.style.top = (pos.bottom + 0) + 'px';
	},

	OnKeyUp: function(e)
	{
		if(!e) e = window.event;
		if(e.keyCode == 27)
			this.ClosePopup();
	},

	ClosePopup: function()
	{
		// if (this.bPreventClickClosing)
			// return;

		for (var type in this.Popup)
		{
			this.Popup[type].pWin.Get().style.display = "none";
			this.Popup[type].bShowed = false;
			this.Popup[type].pWin.Close();
		}

		if (this.bCloseEventsAttached)
		{
			this.bCloseEventsAttached = false;
			BX.unbind(document, "keyup", BX.proxy(this.OnKeyUp, this));
			BX.unbind(document, "click", BX.proxy(this.ClosePopup, this));
		}
	},

	CreatePopup: function(type)
	{
		var _this = this;
		this.Popup[type] = {pWin: new BX.CWindow(false, 'float')};

		if (type == 'outlook')
			this.Popup[type].pSel = this.pOutlSel;
		else if (type == 'mobile')
			this.Popup[type].pSel = this.pMobSel;

		BX.addClass(this.Popup[type].pWin.Get(), "bxec-ban-popup");
	},

	Close: function(bSaveSettings)
	{
		this.pWnd .parentNode.removeChild(this.pWnd);
		if (bSaveSettings !== false)
		{
			if (BX.admin && BX.admin.panel)
				BX.admin.panel.Notify(EC_MESS.CloseBannerNotify);
			this.oEC.userSettings.showBanner = false;
			BX.userOptions.save('calendar', 'user_settings', 'showBanner', 0);
		}
	},

	RunOutlook: function(id)
	{
		var oSect = this.oEC.oSections[id];
		if(oSect && oSect.OUTLOOK_JS && oSect.OUTLOOK_JS.length > 0)
			try{eval(oSect.OUTLOOK_JS);}catch(e){};
	},

	RunMobile: function(id)
	{
		this.oEC.ShowMobileHelpDialog(id);
	}
};

var ECMonthSelector = function(oEC)
{
	this.oEC = oEC;
	this.Build();
	this.content = {month: '', week: '', day: ''};
}

ECMonthSelector.prototype = {
	Build : function()
	{
		var _this = this;
		this.pPrev = BX(this.oEC.id + "selector-prev");
		this.pNext = BX(this.oEC.id + "selector-next");
		this.pCont = BX(this.oEC.id + "selector-cont");
		this.pContInner = BX(this.oEC.id + "selector-cont-inner");

		this.pPrev.onclick = function(){_this.ChangeValue(false);};
		this.pNext.onclick = function(){_this.ChangeValue(true);};
	},

	ChangeMode : function(mode)
	{
		this.mode = mode || this.oEC.activeTabId;
		if (this.mode == 'month')
		{
			this.pCont.className = 'bxec-sel-but';
			this.pCont.onclick = BX.proxy(this.ShowMonthPopup, this);
		}
		else
		{
			this.pCont.className = 'bxec-sel-text';
			this.pCont.onclick = BX.False;
		}
	},

	OnChange : function(year, month, week, date)
	{
		month = parseInt(month, 10);
		year = parseInt(year);
		var res, dayOffset;

		this.pNext.style.marginLeft = (this.mode == 'month' && BX.browser.IsIE() && !BX.browser.IsIE9()) ? '10px' : ''; // Hack for IE 8

		if (this.mode == 'month')
		{
			if (month < 0 || month > 11)
				return alert('Error! Incorrect month');

			this.content.month = this.oEC.arConfig.month[month] + ',&nbsp;' + year + '<span class="bxec-sel-but-arr">';
		}
		else if (this.mode == 'week')
		{
			var startWeekDate = new Date();
			startWeekDate.setFullYear(year, month, 1);
			dayOffset = this.oEC.GetWeekDayOffset(this.oEC.GetWeekDayByInd(startWeekDate.getDay()));

			if(dayOffset > 0)
				startWeekDate.setDate(startWeekDate.getDate() - dayOffset); // Now it-s first day in of this week

			if (week != 0)
				startWeekDate.setDate(startWeekDate.getDate() + (7 * week));

			var oSunDate = new Date(startWeekDate.getTime());
			oSunDate.setDate(oSunDate.getDate() + 6);
			var
				content,
				month_r = this.oEC.arConfig.month_r,
				d_f = startWeekDate.getDate(),
				m_f = startWeekDate.getMonth(),
				y_f = startWeekDate.getFullYear(),
				d_t = oSunDate.getDate(),
				m_t = oSunDate.getMonth(),
				y_t = oSunDate.getFullYear();

			if (m_f == m_t)
				content = d_f + '&nbsp;-&nbsp;' + d_t + '&nbsp;' + month_r[m_f] + '&nbsp;' + y_f;
			else if(y_f == y_t)
				content = d_f + '&nbsp;' + month_r[m_f] + '&nbsp;-&nbsp;' + d_t + '&nbsp;' + month_r[m_t] + '&nbsp;' + y_f;
			else
				content = d_f + '&nbsp;' + month_r[m_f] + '&nbsp;' + y_f + '&nbsp;-&nbsp;' + d_t + '&nbsp;' + month_r[m_t] + '&nbsp;' + y_t;

			this.content.week = '<nobr>' + content + '</nobr>';
			res = {monthFrom: m_f, yearFrom: y_f, weekStartDate: startWeekDate, monthTo: m_t, yearTo: y_t, weekEndDate: oSunDate};
		}
		else if (this.mode == 'day')
		{
			var oDate = new Date();
			oDate.setFullYear(year, month, date);
			day = this.oEC.ConvertDayIndex(oDate.getDay());
			date = oDate.getDate(),
			month = oDate.getMonth(),
			year = oDate.getFullYear();

			this.content.day = '<nobr>' + this.oEC.arConfig.days[day][0] + ',&nbsp;' + date + '&nbsp;' + this.oEC.arConfig.month_r[month] + '&nbsp;' + year + '</nobr>';
			res = {date: date, month: month, year: year, oDate: oDate};
		}

		this.Show(this.mode);
		return res;
	},

	Show: function(mode)
	{
		this.pContInner.innerHTML = this.content[mode];
	},

	ChangeValue: function(bNext)
	{
		var delta = bNext ? 1 : -1;
		if (this.mode == 'month')
		{
			//IncreaseCurMonth
			var m = bxInt(this.oEC.activeDate.month) + delta;
			var y = this.oEC.activeDate.year;
			if (m < 0)
			{
				m += 12;
				y--;
			}
			else if (m > 11)
			{
				m -= 12;
				y++;
			}
			this.oEC.SetMonth(m, y);
		}
		else if (this.mode == 'week')
		{
			this.oEC.SetWeek(this.oEC.activeDate.week + delta, this.oEC.activeDate.month, this.oEC.activeDate.year);
		}
		else if (this.mode == 'day')
		{
			this.oEC.SetDay(this.oEC.activeDate.date + delta, this.oEC.activeDate.month, this.oEC.activeDate.year);
		}
	},

	ShowMonthPopup: function()
	{
		if (!this.oMonthWin)
		{
			var _this = this;
			this.oMonthWin = new BX.PopupWindow(this.oEC.id + "bxc-month-sel", this.pCont, {
				autoHide : true,
				offsetTop : 1,
				offsetLeft : 0,
				lightShadow : true,
				content : BX('bxec_month_win_' + this.oEC.id)
			});
			this.oMonthWin.CAL = {
				DOM : {
					Year: BX(this.oEC.id + 'md-year'),
					MonthList: BX(this.oEC.id + 'md-month-list')
				},
				curYear: parseInt(this.oEC.activeDate.year)
			};

			this.oMonthWin.CAL.DOM.Year.innerHTML = this.oMonthWin.CAL.curYear;
			BX(this.oEC.id + 'md-selector-prev').onclick = function(){_this.oMonthWin.CAL.DOM.Year.innerHTML = --_this.oMonthWin.CAL.curYear;};
			BX(this.oEC.id + 'md-selector-next').onclick = function(){_this.oMonthWin.CAL.DOM.Year.innerHTML = ++_this.oMonthWin.CAL.curYear;};

			var
				i, m, div,
				arM = [0, 4, 8, 1, 5, 9, 2, 6, 10, 3, 7, 11];

			for (i = 0; i < 12; i++)
			{
				m = arM[i];
				div = this.oMonthWin.CAL.DOM.MonthList.appendChild(BX.create("DIV", {
					props: {id: 'bxec_ms_m_' + arM[i], className: 'bxec-month-div' + (arM[i] == this.oEC.activeDate.month ? ' bxec-month-act' : '') + ' bxec-' + this.GetSeason(arM[i])},
					html: '<span>' + this.oEC.arConfig.month[arM[i]] + '</span>',
					events: {click: function()
					{
						//_this.MonthWinSetMonth(this);
						BX.removeClass(_this.oMonthWin.CAL.DOM.curMonth, 'bxec-month-act');
						BX.addClass(this, 'bxec-month-act');
						_this.oMonthWin.CAL.DOM.curMonth = this;
						_this.oEC.SetMonth(parseInt(this.id.substr('bxec_ms_m_'.length)), _this.oMonthWin.CAL.curYear);
						_this.oMonthWin.close();
					}}
				}));
				if (arM[i] == this.oEC.activeDate.month)
					this.oMonthWin.CAL.DOM.curMonth = div;
			}
		}

		this.oMonthWin.show();
	},

	GetSeason : function(m)
	{
		switch(m)
		{
			case 11: case 0: case 1:
				return 'winter';
			case 2: case 3: case 4:
				return 'spring';
			case 5: case 6: case 7:
				return 'summer';
			case 8: case 9: case 10:
				return 'autumn';
		}
	}
};

var ECCalendarAccess = function(Params)
{
	BX.Access.Init();
	if (!window.EC_MESS)
		EC_MESS = {};

	this.bind = Params.bind;
	this.GetAccessName = Params.GetAccessName;
	this.pTbl = Params.pCont.appendChild(BX.create("TABLE", {props: {className: "bxc-access-tbl"}}));
	this.pSel = BX('bxec-' + this.bind);
	var _this = this;
	this.delTitle = Params.delTitle || EC_MESS.Delete;
	this.noAccessRights = Params.noAccessRights || EC_MESS.NoAccessRights;

	this.inputName = Params.inputName || false;

	Params.pLink.onclick = function(){
		BX.Access.ShowForm({
			callback: BX.proxy(_this.InsertRights, _this),
			bind: _this.bind
		});
	};
}

ECCalendarAccess.prototype = {
	InsertRights: function(obSelected)
	{
		var provider, code;
		for(provider in obSelected)
			for(code in obSelected[provider])
				this.InsertAccessRow(BX.Access.GetProviderName(provider) + ' ' + obSelected[provider][code].name, code);
	},

	InsertAccessRow: function(title, code, value)
	{
		var _this = this, row, pLeft, pRight, pTaskSelect;
		if (this.pTbl.rows[0] && this.pTbl.rows[0].cells[0] && this.pTbl.rows[0].cells[0].className.indexOf('bxc-access-no-vals') != -1)
			this.DeleteRow(0);

		row = this.pTbl.insertRow(-1);
		pLeft = BX.adjust(row.insertCell(-1), {props : {className: 'bxc-access-c-l'}, html: title + ':'});
		pRight = BX.adjust(row.insertCell(-1), {props : {className: 'bxc-access-c-r'}});
		pTaskSelect = pRight.appendChild(this.pSel.cloneNode(true));
		//pTaskSelect.name = 'BXEC_ACCESS_' + code;
		pTaskSelect.id = 'BXEC_ACCESS_' + code;

		if (value)
			pTaskSelect.value = value;
		pDel = pRight.appendChild(BX.create('A', {props:{className: 'access-delete', href: 'javascript:void(0)', title: this.delTitle}, events: {click: function(){_this.DeleteRow(this.parentNode.parentNode.rowIndex);}}}));

		if (this.inputName)
		{
			pTaskSelect.name = this.inputName + '[' + code + ']';
			//pRight.appendChild(BX.create('INPUT', {props:{type: 'hidden', value: this.inputName + '[' + code + ']'}}));
		}
	},

	DeleteRow: function(rowIndex)
	{
		if (this.pTbl.rows[rowIndex])
			this.pTbl.deleteRow(rowIndex);
	},

	GetValues: function()
	{
		var
			id, taskId,
			res = {},
			arSelect = this.pTbl.getElementsByTagName("SELECT"),
			i, l = arSelect.length;

		for(i = 0; i < l; i++)
		{
			id = arSelect[i].id.substr('BXEC_ACCESS_'.length);
			taskId = arSelect[i].value;
			res[id] = taskId;
		}

		return res;
	},

	SetSelected: function(oAccess)
	{
		if (!oAccess)
			oAccess = {};

		while (this.pTbl.rows[0])
			this.pTbl.deleteRow(0);

		var
			code,
			oSelected = {};

		for (code in oAccess)
		{
			this.InsertAccessRow(this.GetTitleByCode(code), code, oAccess[code]);
			oSelected[code] = true;
		}

		// Insert 'no value'  if no permissions exists
		if (this.pTbl.rows.length <= 0)
			BX.adjust(this.pTbl.insertRow(-1).insertCell(-1), {props : {className: 'bxc-access-no-vals', colSpan: 2}, html: '<span>' + this.noAccessRights + '</span>'});

		BX.Access.SetSelected(oSelected, this.bind);
	},

	GetTitleByCode: function(code)
	{
		return this.GetAccessName(code);
	}
};

function ECColorPicker(Params)
{
	//this.bCreated = false;
	this.bOpened = false;
	this.zIndex = 5000;
	this.id = '';
	this.Popups = {};
	this.Conts = {};
}

ECColorPicker.prototype = {
	Create: function ()
	{
		var _this = this;
		var pColCont = document.body.appendChild(BX.create("DIV", {props: {className: "ec-colpick-cont"}, style: {zIndex: this.zIndex}}));

		var
			arColors = ['#FF0000', '#FFFF00', '#00FF00', '#00FFFF', '#0000FF', '#FF00FF', '#FFFFFF', '#EBEBEB', '#E1E1E1', '#D7D7D7', '#CCCCCC', '#C2C2C2', '#B7B7B7', '#ACACAC', '#A0A0A0', '#959595',
			'#EE1D24', '#FFF100', '#00A650', '#00AEEF', '#2F3192', '#ED008C', '#898989', '#7D7D7D', '#707070', '#626262', '#555', '#464646', '#363636', '#262626', '#111', '#000000',
			'#F7977A', '#FBAD82', '#FDC68C', '#FFF799', '#C6DF9C', '#A4D49D', '#81CA9D', '#7BCDC9', '#6CCFF7', '#7CA6D8', '#8293CA', '#8881BE', '#A286BD', '#BC8CBF', '#F49BC1', '#F5999D',
			'#F16C4D', '#F68E54', '#FBAF5A', '#FFF467', '#ACD372', '#7DC473', '#39B778', '#16BCB4', '#00BFF3', '#438CCB', '#5573B7', '#5E5CA7', '#855FA8', '#A763A9', '#EF6EA8', '#F16D7E',
			'#EE1D24', '#F16522', '#F7941D', '#FFF100', '#8FC63D', '#37B44A', '#00A650', '#00A99E', '#00AEEF', '#0072BC', '#0054A5', '#2F3192', '#652C91', '#91278F', '#ED008C', '#EE105A',
			'#9D0A0F', '#A1410D', '#A36209', '#ABA000', '#588528', '#197B30', '#007236', '#00736A', '#0076A4', '#004A80', '#003370', '#1D1363', '#450E61', '#62055F', '#9E005C', '#9D0039',
			'#790000', '#7B3000', '#7C4900', '#827A00', '#3E6617', '#045F20', '#005824', '#005951', '#005B7E', '#003562', '#002056', '#0C004B', '#30004A', '#4B0048', '#7A0045', '#7A0026'],
			row, cell, colorCell,
			tbl = BX.create("TABLE", {props: {className: 'ec-colpic-tbl'}}),
			i, l = arColors.length;

		row = tbl.insertRow(-1);
		cell = row.insertCell(-1);
		cell.colSpan = 8;
		var defBut = cell.appendChild(BX.create("SPAN", {props: {className: 'ec-colpic-def-but'}, text: EC_MESS.DefaultColor}));
		defBut.onmouseover = function()
		{
			this.className = 'ec-colpic-def-but ec-colpic-def-but-over';
			colorCell.style.backgroundColor = '#FF0000';
		};
		defBut.onmouseout = function(){this.className = 'ec-colpic-def-but';};
		defBut.onmousedown = function(e){_this.Select('#FF0000');}

		colorCell = row.insertCell(-1);
		colorCell.colSpan = 8;
		colorCell.className = 'ec-color-inp-cell';
		colorCell.style.backgroundColor = arColors[38];

		for(i = 0; i < l; i++)
		{
			if (Math.round(i / 16) == i / 16) // new row
				row = tbl.insertRow(-1);

			cell = row.insertCell(-1);
			cell.innerHTML = '&nbsp;';
			cell.className = 'ec-col-cell';
			cell.style.backgroundColor = arColors[i];
			cell.id = 'lhe_color_id__' + i;

			cell.onmouseover = function (e)
			{
				this.className = 'ec-col-cell ec-col-cell-over';
				colorCell.style.backgroundColor = arColors[this.id.substring('lhe_color_id__'.length)];
			};
			cell.onmouseout = function (e){this.className = 'ec-col-cell';};
			cell.onmousedown = function (e)
			{
				var k = this.id.substring('lhe_color_id__'.length);
				_this.Select(arColors[k]);
			};
		}

		pColCont.appendChild(tbl);

		this.Conts[this.id] = pColCont;
		//this.bCreated = true;
	},

	Open: function(Params)
	{
		this.id = Params.id;
		this.key = Params.key;
		this.OnSelect = Params.onSelect;

		//if (!this.bCreated)
		if (!this.Conts[this.id])
			this.Create();

		if (!this.Popups[this.id])
		{
			this.Popups[this.id] = BX.PopupWindowManager.create("bxc-color-popup" + Params.id, Params.pWnd, {
				autoHide : true,
				offsetTop : 1,
				offsetLeft : 0,
				lightShadow : true,
				content : this.Conts[this.id]
			});
		}

		this.Popups[this.id].show();
		//BX.bind(window, "keypress", BX.proxy(this.OnKeyPress, this));
		//this.bOpened = true;
	},

	Close: function ()
	{
		this.Popups[this.id].close();

		//BX.unbind(window, "keypress", BX.proxy(this.OnKeyPress, this));
		//this.bOpened = false;
	},

	OnKeyPress: function(e)
	{
		if(!e) e = window.event
		if(e.keyCode == 27)
			this.Close();
	},

	Select: function (color)
	{
		if (this.OnSelect && typeof this.OnSelect == 'function')
			this.OnSelect(color);
		this.Close();
	}
};