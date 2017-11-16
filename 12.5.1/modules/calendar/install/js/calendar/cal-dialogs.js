// # # #  #  #  # Add Event Dialog  # # #  #  #  #
JCEC.prototype.ShowAddEventDialog = function(bShowCalendars)
{
	var _this = this;
	if (this.bReadOnly)
		return;

	if (!this.CheckSectionsCount())
		return alert(EC_MESS.NoCalendarsAlert);

	var D = this.oAddEventDialog;
	if (!D)
	{
		D = new BX.PopupWindow("BXCAddEvent", null, {
			autoHide: true,
			closeByEsc : true,
			zIndex: 0,
			offsetLeft: 0,
			offsetTop: 0,
			draggable: true,
			bindOnResize: false,
			titleBar: {content: BX.create("span", {html: EC_MESS.NewEvent})},
			closeIcon: { right : "12px", top : "10px"},
			className: 'bxc-popup-window',
			buttons: [
				new BX.PopupWindowButton({
					text: EC_MESS.GoExt,
					title: EC_MESS.GoExtTitle,
					className: "bxec-popup-link-icon bxec-popup-add-ex",
					events: {click : function(){_this.OpenExFromSimple();}}
				}),
				new BX.PopupWindowButton({
					text: EC_MESS.Add,
					className: "popup-window-button-accept",
					events: {click : function(){
						if (_this.SimpleSaveNewEvent())
							_this.CloseAddEventDialog(true);
					}}
				}),
				new BX.PopupWindowButtonLink({
					text: EC_MESS.Close,
					className: "popup-window-button-link-cancel",
					events: {click : function(){_this.CloseAddEventDialog(true);}}
				})
			],
			content: BX('bxec_add_ed_' + this.id),
			events: {}
		});

		D.CAL = {
			DOM: {
				Name: BX(this.id + '_add_ed_name'),
				PeriodText: BX(this.id + '_add_ed_per_text'),
				SectSelect: BX(this.id + '_add_ed_calend_sel'),
				Warn: BX(this.id + '_add_sect_sel_warn')
			}
		};

		if (this.bIntranet && (this.Personal() || this.type != 'user'))
		{
			D.CAL.DOM.Accessibility = BX(this.id + '_add_ed_acc');
			if (D.CAL.DOM.Accessibility && BX.browser.IsIE())
				D.CAL.DOM.Accessibility.style.width = '250px';
		}
		this.oAddEventDialog = D;

		D.CAL.DOM.SectSelect.onchange = function()
		{
			_this.SaveLastSection(this.value);
			D.CAL.DOM.Warn.style.display = _this.oActiveSections[D.CAL.DOM.SectSelect.value] ? 'none' : 'block';
		};

		BX.addCustomEvent(D, 'onPopupClose', BX.proxy(this.CloseAddEventDialog, this));
	}

	var
		f, t, cts, a, cdts, perHTML,
		time_f = '', time_t = '';

	D.CAL.DOM.Name.value = '';
	this.BuildSectionSelect(D.CAL.DOM.SectSelect, this.GetLastSection());
	D.CAL.DOM.Warn.style.display = _this.oActiveSections[D.CAL.DOM.SectSelect.value] ? 'none' : 'block';

	if (this.selectDaysMode) // Month view
	{
		var
			start_ind = parseInt(this.selectDaysStartObj.id.substr(9)),
			end_ind = parseInt(this.selectDaysEndObj.id.substr(9));
		if (start_ind > end_ind) // swap start_ind and end_ind
		{
			a = end_ind;
			end_ind = start_ind;
			start_ind = a;
		}

		f = this.activeDateDaysAr[start_ind];
		t = this.activeDateDaysAr[end_ind];
	}
	else if (this.selectTimeMode) // Week view - time select
	{
		cts = this.curTimeSelection;
		f = new Date(cts.sDay.year, cts.sDay.month, cts.sDay.date, cts.sHour, cts.sMin);
		t = new Date(cts.eDay.year, cts.eDay.month, cts.eDay.date, cts.eHour, cts.eMin);

		if (f.getTime() > t.getTime())
		{
			a = f;
			f = t;
			t = a; // swap "f" and "t"
		}
	}
	else if (this.selectDayTMode) // Week view - days select
	{
		cdts = this.curDayTSelection;
		f = new Date(cdts.sDay.year, cdts.sDay.month, cdts.sDay.date);
		t = new Date(cdts.eDay.year, cdts.eDay.month, cdts.eDay.date);
	}
	else
		return;

	var
		f_day = this.ConvertDayIndex(f.getDay()),
		t_day = this.ConvertDayIndex(t.getDay());

	if (f.getTime() == t.getTime()) // one day
	{
		perHTML = this.days[f_day][0] + ' ' + bxFormatDate(f.getDate(), f.getMonth() + 1, f.getFullYear());
	}
	else
	{
		var
			d_f = f.getDate(), m_f = f.getMonth() + 1, y_f = f.getFullYear(), h_f = f.getHours(), mi_f = f.getMinutes(),
			d_t = t.getDate(), m_t = t.getMonth() + 1, y_t = t.getFullYear(), h_t = t.getHours(), mi_t = t.getMinutes(),
			bTime = !(h_f == h_t && h_f == 0 && mi_f == mi_t && mi_f == 0);

		if (bTime)
		{
			time_f = this.FormatTimeByNum(h_f, mi_f);
			time_t = this.FormatTimeByNum(h_t, mi_t);
		}

		if (m_f == m_t && y_f == y_t && d_f == d_t && bTime) // Same day, different time
			perHTML = this.days[f_day][0] + ' ' + bxFormatDate(d_f, m_f, y_f) + ', ' + time_f + ' &mdash; ' + time_t;
		else
			perHTML = this.days[f_day][0] + ' ' + bxFormatDate(d_f, m_f, y_f) + ' ' +  time_f + ' &mdash; ' +
				this.days[t_day][0] + ' ' + bxFormatDate(d_t, m_t, y_t) + ' ' + time_t;
	}

	D.CAL.DOM.PeriodText.innerHTML = perHTML;
	D.CAL.Params = {
		from: f,
		to: t,
		time_f: time_f || '',
		time_t: time_t || ''
	};
	setTimeout(function(){BX.focus(D.CAL.DOM.Name);}, 500);

	if (this.bIntranet && (this.Personal() || this.type != 'user'))
		D.CAL.DOM.Accessibility.value = 'busy';

	pos = this.GetAddDialogPosition();
	if (pos)
	{
		D.popupContainer.style.top = pos.top + "px";
		D.popupContainer.style.left = pos.left + "px";
	}

	D.show();
}

JCEC.prototype.OpenExFromSimple = function(bCallback)
{
	this.CloseAddEventDialog(true);
	if (!bCallback)
		return this.ShowEditEventDialog({bExFromSimple: true});

	var
		D1 = this.oAddEventDialog,
		D2 = this.oEditEventDialog,
		f = D1.CAL.Params.from,
		t = D1.CAL.Params.to;

	D2.CAL._FromDateValue = D2.CAL.DOM.FromDate.value = bxFormatDate(f.getDate(), f.getMonth() + 1, f.getFullYear());
	D2.CAL.DOM.ToDate.value = bxFormatDate(t.getDate(), t.getMonth() + 1, t.getFullYear());

	D2.CAL._FromTimeValue = D2.CAL.DOM.FromTime.value = D1.CAL.Params.time_f || '';
	D2.CAL.DOM.ToTime.value = D1.CAL.Params.time_t || '';

	D2.CAL.DOM.FullDay.checked = !D1.CAL.Params.time_f && !D1.CAL.Params.time_t;
	D2.CAL.DOM.FullDay.onclick();

	D2.CAL.DOM.Name.value = D1.CAL.DOM.Name.value;

	if (this.bIntranet && D2.CAL.DOM.Accessibility && D1.CAL.DOM.Accessibility)
		D2.CAL.DOM.Accessibility.value = D1.CAL.DOM.Accessibility.value;

	//Set WUSIWUG Editor Content
	setTimeout(function(){window.pLHEEvDesc.SetEditorContent('');}, 100);

	if (D1.CAL.DOM.SectSelect.value)
	{
		D2.CAL.DOM.SectSelect.value = D1.CAL.DOM.SectSelect.value;
		if (D2.CAL.DOM.SectSelect.onchange)
			D2.CAL.DOM.SectSelect.onchange();
	}
}

JCEC.prototype.CloseAddEventDialog = function(bClosePopup)
{
	if (!this.oAddEventDialog)
		return;
	switch (this.activeTabId)
	{
		case 'month':
			this.DeSelectDays();
			break;
		case 'week':
			this.DeSelectTime(this.activeTabId);
			this.DeSelectDaysT();
			break;
		case 'day':
			break;
	}
	if (bClosePopup === true)
		this.oAddEventDialog.close();
}

JCEC.prototype.GetAddDialogPosition = function()
{
	if (this.activeTabId == 'month')
	{
		var last_selected = this.arSelectedDays[this.bInvertedDaysSelection ? 0 : this.arSelectedDays.length - 1];
		if (!last_selected)
			return false;

		var pos = BX.pos(last_selected);
		pos.top += parseInt(this.dayCellHeight / 2) + 20;
		pos.left += parseInt(this.dayCellWidth / 2) + 20;

		pos.right = pos.left;
		pos.bottom = pos.top;
		pos = BX.align(pos, 360, 180);
		return pos;
	}
	else //if (this.activeTabId == 'week')
	{
		return false;
	}
}

// # # #  #  #  # Edit Event Dialog  # # #  #  #  #
JCEC.prototype.CreateEditEventDialog = function(bCheck)
{
	var
		_this = this,
		pTitle = BX.create("span", {html: EC_MESS.NewEvent});

	var D = new BX.PopupWindow("BXCEditEvent", null, {
		autoHide: false,
		closeByEsc : true,
		zIndex: 0,
		offsetLeft: 0,
		offsetTop: 0,
		draggable: true,
		titleBar: {content: pTitle},
		closeIcon: { right : "12px", top : "10px"},
		className: "bxc-popup-tabed bxc-popup-window",
		buttons: [
			new BX.PopupWindowButton({
				text: EC_MESS.Delete,
				id: this.id + 'ed-del-button',
				className: "bxec-popup-link-icon bxec-popup-del-ex",
				events: {click : function(){
					if (_this.Event.Delete(D.CAL.oEvent))
						_this.CloseEditEventDialog(true);
				}}
			}),
			new BX.PopupWindowButton({
				text: EC_MESS.Save,
				className: "popup-window-button-accept",
				events: {click : function(){
					if (_this.oEditEventDialog.CAL.bMeetingStyleFields)
					{
						_this.Event.SetMeetingParams({callback: function(){_this.CloseEditEventDialog(true);}, bLocationChecked: false});
					}
					else
					{
						if (window.pLHEEvDesc)
							window.pLHEEvDesc.SaveContent();
						_this.ExtendedSaveEvent({callback: function(){_this.CloseEditEventDialog(true);}, bLocationChecked: false});
					}
				}}
			}),
			new BX.PopupWindowButtonLink({
				text: EC_MESS.Close,
				className: "popup-window-button-link-cancel",
				events: {click : function(){_this.CloseEditEventDialog(true);}}
			})
		],
		content: BX('bxec_edit_ed_' + this.id),
		events: {}
	});

	BX.addCustomEvent(D, 'onPopupClose', BX.proxy(this.CloseEditEventDialog, this));

	D.CAL = {
		DOM: {
			Title: pTitle,
			DelBut: BX(this.id + 'ed-del-button'),
			pTabs: BX(this.id + '_edit_tabs'),
			FromDate: BX(this.id + 'edev-from'),
			ToDate: BX(this.id + 'edev-to'),
			ToTime: BX(this.id + 'edev_to_time'),
			FromTime: BX(this.id + 'edev_from_time'),
			FullDay: BX(this.id + '_full_day'),

			Name: BX(this.id + '_edit_ed_name'),
			Desc: BX(this.id + '_edit_ed_desc'),

			RepeatSelect: BX(this.id + '_edit_ed_rep_sel'),
			RepeatSect: BX(this.id + '_edit_ed_repeat_sect'),
			RepeatPhrase1: BX(this.id + '_edit_ed_rep_phrase1'),
			RepeatPhrase2: BX(this.id + '_edit_ed_rep_phrase2'),
			RepeatWeekDays: BX(this.id + '_edit_ed_rep_week_days'),
			RepeatCount: BX(this.id + '_edit_ed_rep_count'),

			RemindCnt: BX(this.id + '_remind_cnt'),
			RepeatDiapTo: BX(this.id + 'edit-ev-rep-diap-to'),

			dialogTitle: BX(this.id + '_edit_ed_d_title'),
			SectSelect: BX(this.id + '_edit_ed_calend_sel'),
			Warn: BX(this.id + '_edit_sect_sel_warn'),
			Importance: BX(this.id + '_bxec_importance'),

			UFGroup: BX(this.id + 'bxec_uf_group'),
			UFCont: BX(this.id + 'bxec_uf_cont')
		},
		Location: new BXInputPopup({
			id: this.id + 'loc_1',
			values: this.bUseMR ? this.meetingRooms : false,
			input: BX(this.id + '_planner_location1'),
			defaultValue: EC_MESS.SelectMR,
			openTitle: EC_MESS.OpenMRPage
		}),
		Loc: {},
		bMeetingStyleFields: false
	};

	D.CAL.ColorControl = this.InitColorDialogControl('event', function(color, textColor){
		D.CAL.Color = color;
		D.CAL.TextColor = textColor;
	});

	BX.addCustomEvent(D.CAL.Location, 'onInputPopupChanged', BX.proxy(this._LocOnChange, this));

	if (this.allowMeetings)
	{
		D.CAL.DOM.Host = BX(this.id + 'edit_host_link');

		D.CAL.DOM.PlannerLink = BX(this.id + '_planner_link');
		D.CAL.DOM.PlannerLink.onclick = function()
		{
			var attendees = [], att = D.CAL.UserControl.GetValues(), id;
			for (id in att)
				if (att[id])
					attendees.push(att[id].User);

			if (attendees.length == 0)
				attendees.push({id: _this.userId, key: _this.userId, name: _this.userName});

			var
				loc = D.CAL.Loc.NEW,
				arLoc = _this.ParseLocation(loc, true),
				locMrind = arLoc.mrind == undefined ? false : arLoc.mrind;

			_this.RunPlanner({
				curEventId: D.CAL.bNew ? false : D.CAL.oEvent.ID,
				attendees: attendees,
				fromDate: D.CAL.DOM.FromDate.value,
				toDate: D.CAL.DOM.ToDate.value,
				fromTime: D.CAL.DOM.FromTime.value,
				toTime: D.CAL.DOM.ToTime.value,
				location: loc,
				locationMrind: locMrind,
				oldLocationMRId: D.CAL.Loc.OLD_mrevid
			});
		};

		D.CAL.UserControl = new ECUserControll({
			oEC: this,
			AddLinkCont : BX(this.id + '_user_control_link'),
			AttendeesCont : BX(this.id + '_attendees_cont'),
			AttendeesList : BX(this.id + '_attendees_list'),
			AdditionalParams: BX(this.id + '_add_meeting_params'),
			SummaryCont: BX(this.id + '_att_summary'),
			fromDateGetter: function()
			{
				var date = BX.parseDate(D.CAL.DOM.FromDate.value);
				if (date)
				{
					var time = _this.ParseTime(D.CAL.DOM.FromTime.value);
					date.setHours(time.h);
					date.setMinutes(time.m);
				}
				return date;
			},
			toDateGetter: function()
			{
				var date = BX.parseDate(D.CAL.DOM.ToDate.value);
				if (date)
				{
					var time = _this.ParseTime(D.CAL.DOM.ToTime.value);
					date.setHours(time.h);
					date.setMinutes(time.m);
				}
				return date;
			},
			eventIdGetter: function()
			{
				return D.CAL.bNew ? 0 : D.CAL.oEvent.ID;
			}
		});

		BX.addCustomEvent(D.CAL.UserControl, 'UserOnChange', function(){
			if ((D.CAL.bNew || (D.CAL.oEvent && !D.CAL.oEvent.IS_MEETING)) && !D.CAL.bHostAdded)
			{
				var attendees = D.CAL.UserControl.GetValues();
				// If no host in the list
				if (!attendees[_this.userId])
				{
					var k, values = [];
					// Add host firs of all
					values.push({id: _this.userId, key: _this.userId, name: _this.userName});

					for(k in attendees)
						values.push(attendees[k].User);

					D.CAL.UserControl.SetValues(values);
				}
				D.CAL.bHostAdded = true;
			}

			D.CAL.UserControl.CheckAccessibility({}, 1000);
		});

		if (this.type == 'group')
		{
			BX.addCustomEvent(this, "onGetGroupMembers", function(users)
			{
				var
					k, values = [],
					attendees = D.CAL.UserControl.GetValues();

				if (D.CAL.bNew && !D.CAL.bHostAdded &&!attendees[_this.userId]) // Add host firs of all
				{
					values.push({id: _this.userId, key: _this.userId, name: _this.userName});
					D.CAL.bHostAdded = true;
				}

				for(k in attendees)
					values.push(attendees[k].User);

				for(k in users)
					values.push({id: users[k].id, name: users[k].name});

				D.CAL.UserControl.SetValues(values);
			});
		}

		D.CAL.DOM.AddMeetTextLink = BX(this.id + '_add_meet_text');
		D.CAL.DOM.HideMeetTextLink = BX(this.id + '_hide_meet_text');
		D.CAL.DOM.MeetTextCont = BX(this.id + '_meet_text_cont');
		D.CAL.DOM.MeetText = BX(this.id + '_meeting_text');

		D.CAL.DOM.OpenMeeting = BX(this.id + '_ed_open_meeting');
		D.CAL.DOM.NotifyStatus = BX(this.id + '_ed_notify_status');
		D.CAL.DOM.Reinvite = BX(this.id + '_ed_reivite');
		D.CAL.DOM.ReinviteCont = BX(this.id + '_ed_reivite_cont');

		D.CAL.DOM.AddMeetTextLink.onclick = function()
		{
			this.parentNode.style.display = 'none';
			D.CAL.DOM.MeetTextCont.style.display = 'block';
			BX.focus(D.CAL.DOM.MeetText);
		};

		D.CAL.DOM.HideMeetTextLink.onclick = function()
		{
			D.CAL.DOM.AddMeetTextLink.parentNode.style.display = 'block';
			D.CAL.DOM.MeetTextCont.style.display = 'none';
		};
	}

	if (this.allowReminders)
	{
		D.CAL.DOM.RemCheck = BX(this.id + '_bxec_reminder');
		D.CAL.DOM.RemCont = BX(this.id + '_bxec_rem_cont');
		D.CAL.DOM.RemCount = BX(this.id + '_bxec_rem_count');
		D.CAL.DOM.RemType = BX(this.id + '_bxec_rem_type');
		D.CAL.DOM.RemCheck.onclick = function(){D.CAL.DOM.RemCont.style.display = this.checked ? '' : 'none';};
	}

	this.ChargePopupTabs(D, this.id + 'ed-tab-');
	// Set events for from-to date, time controls
	D.CAL.DOM.FromDate.onclick = function(){BX.calendar({node: this.parentNode, field: this, bTime: false});};
	D.CAL.DOM.ToDate.onclick = function(){BX.calendar({node: this.parentNode, field: this, bTime: false});};

	D.CAL.DOM.FromDate.onchange = function()
	{
		if(D.CAL._FromDateValue)
		{
			var
				prevF = BX.parseDate(D.CAL._FromDateValue),
				F = BX.parseDate(D.CAL.DOM.FromDate.value),
				T = BX.parseDate(D.CAL.DOM.ToDate.value);

			if (F)
			{
				var duration = T.getTime() - prevF.getTime();
				T = new Date(F.getTime() + duration);
				D.CAL.DOM.ToDate.value = bxFormatDate(T.getDate(), T.getMonth() + 1, T.getFullYear());
			}
		}

		D.CAL._FromDateValue = D.CAL.DOM.FromDate.value;
	};

	var pFromToCont = BX.findParent(D.CAL.DOM.FullDay, {className: 'bxec-popup-row-from-to'});
	D.CAL.DOM.FullDay.onclick = function()
	{
		if (this.checked)
		{
			BX.addClass(pFromToCont, "bxec-from-to-skip-time");
		}
		else
		{
			BX.removeClass(pFromToCont, "bxec-from-to-skip-time");
			BX.focus(D.CAL.DOM.FromTime);
		}
	};

	D.CAL.DOM.FromTime.parentNode.onclick = D.CAL.DOM.FromTime.onclick = function()
	{
		window['bxShowClock_' + _this.id + 'edev_from_time']();
		// Hack
		setTimeout(function(){var shad = BX(_this.id + 'edev_from_time_div_shadow');if(shad){shad.style.zIndex = '1550';}}, 200);
	};

	D.CAL.DOM.FromTime.onchange = function()
	{
		if (D.CAL.DOM.ToTime.value == "")
		{
			if(BX.util.trim(D.CAL.DOM.FromDate.value) == BX.util.trim(D.CAL.DOM.ToDate.value) && BX.util.trim(D.CAL.DOM.ToDate.value) != '')
			{
				var fromTime = _this.ParseTime(this.value);
				if (fromTime.h >= 23)
				{
					D.CAL.DOM.ToTime.value = _this.FormatTimeByNum(0, fromTime.m);
					var date = BX.parseDate(D.CAL.DOM.FromDate.value);
					if (date)
					{
						date.setDate(date.getDate() + 1);
						D.CAL.DOM.ToDate.value = bxFormatDate(date.getDate(), date.getMonth() + 1, date.getFullYear());
					}
				}
				else
				{
					D.CAL.DOM.ToTime.value = _this.FormatTimeByNum(parseInt(fromTime.h, 10) + 1, fromTime.m);
				}
			}
			else
			{
				D.CAL.DOM.ToTime.value = D.CAL.DOM.FromTime.value;
			}
		}
		else if (D.CAL.DOM.ToDate.value == '' || D.CAL.DOM.ToDate.value == D.CAL.DOM.FromDate.value)
		{
			if (D.CAL.DOM.ToDate.value == '')
				D.CAL.DOM.ToDate.value = D.CAL.DOM.FromDate.value;

			// 1. We need prev. duration
			if(D.CAL._FromTimeValue)
			{
				var
					F = BX.parseDate(D.CAL.DOM.FromDate.value),
					T = BX.parseDate(D.CAL.DOM.ToDate.value),
					prevFromTime = _this.ParseTime(D.CAL._FromTimeValue),
					fromTime = _this.ParseTime(D.CAL.DOM.FromTime.value),
					toTime = _this.ParseTime(D.CAL.DOM.ToTime.value);

				F.setHours(prevFromTime.h);
				F.setMinutes(prevFromTime.m);
				T.setHours(toTime.h);
				T.setMinutes(toTime.m);

				var duration = T.getTime() - F.getTime();
				if (duration != 0)
				{
					F.setHours(fromTime.h);
					F.setMinutes(fromTime.m);

					T = new Date(F.getTime() + duration);
					D.CAL.DOM.ToDate.value = bxFormatDate(T.getDate(), T.getMonth() + 1, T.getFullYear());
					D.CAL.DOM.ToTime.value = _this.FormatTimeByNum(T.getHours(), T.getMinutes());
				}
			}
		}

		D.CAL._FromTimeValue = D.CAL.DOM.FromTime.value;
	};

	D.CAL.DOM.ToTime.parentNode.onclick = D.CAL.DOM.ToTime.onclick = function()
	{
		window['bxShowClock_' + _this.id + 'edev_to_time']();
		// Hack
		setTimeout(function(){var shad = BX(_this.id + 'edev_to_time_div_shadow');	if(shad) {shad.style.zIndex = '1550';}}, 200);
	};

	if (this.bIntranet)
	{
		D.CAL.DOM.Accessibility = BX(this.id + '_bxec_accessibility');
		// Hide it
		if(D.CAL.DOM.Accessibility && !(this.Personal() || this.type != 'user'))
			D.CAL.DOM.Accessibility.parentNode.parentNode.style.display = 'none';
	}

	D.CAL.DOM.Private = BX(this.id + '_bxec_private');
	if (this.Personal() && !(this.Personal() || this.type != 'user'))
		D.CAL.DOM.Private.parentNode.parentNode.style.display = 'none';

	D.CAL.DOM.RepeatSelect.onchange = function() {_this.OnChangeRepeatSelect(this.value);};
	D.CAL.DOM.RepeatCount.onmousedown = function() {_this.bEditEventDialogOver = true;};

	D.CAL.DOM.SectSelect.onchange = function()
	{
		var sectId = this.value;
		if (_this.oSections[sectId])
		{
			_this.SaveLastSection(sectId);
			D.CAL.DOM.Warn.style.display = _this.oActiveSections[sectId] ? 'none' : 'block';
			D.CAL.ColorControl.Set(_this.oSections[sectId].COLOR, _this.oSections[sectId].TEXT_COLOR);
		}
	};

	D.CAL.DOM.RepeatDiapTo.onblur = D.CAL.DOM.RepeatDiapTo.onchange = function()
	{
		if (this.value)
		{
			this.style.color = '#000000';
			return;
		}
		this.value = EC_MESS.NoLimits;
		this.style.color = '#C0C0C0';
	};

	D.CAL.DOM.RepeatDiapTo.onclick = function(){BX.calendar({node: this.parentNode, field: this, bTime: false});};

	D.CAL.DOM.RepeatDiapTo.onfocus = function()
	{
		if (!this.value || this.value == EC_MESS.NoLimits)
			this.value = '';
		this.style.color = '#000000';
	};

	D.CAL.DOM.Name.onkeydown = D.CAL.DOM.Name.onchange = function()
	{
		if (D.CAL._titleTimeout)
			clearTimeout(D.CAL._titleTimeout);

		D.CAL._titleTimeout = setTimeout(
			function(){
				var
					D = _this.oEditEventDialog,
					val = BX.util.htmlspecialchars(D.CAL.DOM.Name.value);
				D.CAL.DOM.Title.innerHTML = (D.CAL.bNew ? EC_MESS.NewEvent : EC_MESS.EditEvent) + (val != '' ? ': ' + val : '');
			}, 20
		);
	};

	this.oEditEventDialog = D;
}

JCEC.prototype.ShowEditEventDialog = function(Params)
{
	if (this.bReadOnly)
		return;

	if (!this.CheckSectionsCount())
		return alert(EC_MESS.NoCalendarsAlert);

	if (!Params)
		Params = {};

	var
		_this = this,
		fd, td,
		oEvent = Params.oEvent || {},
		bNew = !oEvent.ID,
		bExFromSimple = Params.bExFromSimple;

	// Load LHE
	if (!window.pLHEEvDesc)
	{
		LoadLHE_LHEEvDesc();
		var _loadlheInterval = setInterval(function()
			{
				if (window.pLHEEvDesc)
				{
					clearInterval(_loadlheInterval);
					_this.ShowEditEventDialog(Params);
				}
			}, 50
		);
		return;
	}

	if (!this.oEditEventDialog)
		this.CreateEditEventDialog();

	var D = this.oEditEventDialog;
	D.show();
	this.SetPopupTab(0, D); // Activate first tab
	D.CAL.bNew = bNew;
	D.CAL.oEvent = oEvent;

	// *** Set reminders ***
	if(this.allowReminders)
	{
		D.CAL.DOM.RemCheck.checked = false;
		D.CAL.DOM.RemCount.value = 15;
		D.CAL.DOM.RemType.value = 'min';
		D.CAL.DOM.RemCont.style.display = 'none';
	}
	if(this.allowReminders && D.CAL.oEvent.REMIND && D.CAL.oEvent.REMIND.length > 0)
	{
		D.CAL.DOM.RemCheck.checked = true;
		D.CAL.DOM.RemCount.value = D.CAL.oEvent.REMIND[0].count;
		D.CAL.DOM.RemType.value = D.CAL.oEvent.REMIND[0].type;
		D.CAL.DOM.RemCont.style.display = '';
	}

	// Accessibility
	if (this.bIntranet && D.CAL.DOM.Accessibility)
		D.CAL.DOM.Accessibility.value = D.CAL.oEvent.ACCESSIBILITY || 'busy';

	if (this.allowMeetings)
	{
		// Edit personal meeting params by attendees
		if (this.Event.IsAttendee(oEvent) && !(this.Event.IsHost(oEvent) && this.CheckType(oEvent.CAL_TYPE, oEvent.OWNER_ID)))
		{
			D.CAL.DOM.Title.innerHTML = EC_MESS.EditEvent  + ': ' + BX.util.htmlspecialchars(oEvent.NAME);
			// Set values from oEvent.USER_MEETING
			if(this.allowReminders)
			{
				var rem = D.CAL.oEvent.USER_MEETING.REMIND;
				if (rem && rem.length > 0)
				{
					D.CAL.DOM.RemCheck.checked = true;
					D.CAL.DOM.RemCount.value = rem[0].count;
					D.CAL.DOM.RemType.value = rem[0].type;
					D.CAL.DOM.RemCont.style.display = '';
				}
				else
				{
					D.CAL.DOM.RemCheck.checked = false;
					D.CAL.DOM.RemCont.style.display = 'none';
				}
			}
			D.CAL.DOM.Accessibility.value = D.CAL.oEvent.USER_MEETING.ACCESSIBILITY || 'busy';

			this.SetEditingMeetingFields(true);
			D.CAL.DOM.DelBut.style.display = 'none';

			this.Event.Blink(oEvent, false);
			return;
		}
		this.SetEditingMeetingFields(false);

		// Deactivate meeting if it's event in non-personal user's calendar
		if (!this.Personal() && this.type == 'user')
			D.CAL.Tabs[2].tab.style.display = 'none';
		else
			D.CAL.Tabs[2].tab.style.display = '';

		if (bNew)
			D.CAL.bHostAdded = false;

		D.CAL.UserControl.SetValues(this.Event.Attendees(oEvent));
		D.CAL.UserControl.CheckAccessibility({}, 300);

		D.CAL.DOM.ReinviteCont.style.display = bNew ? 'none' : '';

		if (bNew || (oEvent && !oEvent.IS_MEETING))
		{
			D.CAL.DOM.Host.href = this.GetUserHref(this.userId);
			D.CAL.DOM.Host.innerHTML = BX.util.htmlspecialchars(this.userName);
		}
		else if(oEvent.MEETING)
		{
			D.CAL.DOM.Host.href = this.GetUserHref(oEvent.MEETING_HOST);
			D.CAL.DOM.Host.innerHTML = BX.util.htmlspecialchars(oEvent.MEETING.HOST_NAME || oEvent.MEETING_HOST);
		}

		if (!D.CAL.bNew)
		{
			D.CAL.DOM.OpenMeeting.checked = !!(oEvent.MEETING && oEvent.MEETING.OPEN);
			D.CAL.DOM.NotifyStatus.checked = !!(oEvent.MEETING && oEvent.MEETING.NOTIFY);

			if (oEvent.MEETING)
			{
				D.CAL.DOM.MeetText.value = oEvent.MEETING.TEXT || '';
				if (oEvent.MEETING.TEXT != '')
					D.CAL.DOM.AddMeetTextLink.onclick();
			}
			else
			{
				D.CAL.DOM.MeetText.value = '';
			}
			D.CAL.DOM.Reinvite.checked = true;
		}
		else
		{
			D.CAL.DOM.AddMeetTextLink.parentNode.style.display = 'block';
			D.CAL.DOM.MeetTextCont.style.display = 'none';

			if(D.CAL.DOM.MeetText)
				D.CAL.DOM.MeetText.value = '';
			if (D.CAL.DOM.HideMeetTextLink)
				D.CAL.DOM.HideMeetTextLink.onclick();
		}
	}

	if (Params.bRunPlanner)
		D.popupContainer.style.visibility = 'hidden';
	else
		D.popupContainer.style.visibility = 'visible';

	if (oEvent.DT_FROM_TS || oEvent.DT_TO_TS)
	{
		if (!this.Event.IsRecursive(oEvent))
		{
			fd = bxGetDateFromTS(oEvent.DT_FROM_TS);
			td = bxGetDateFromTS(oEvent.DT_TO_TS);
		}
		else
		{
			fd = bxGetDateFromTS(oEvent['~DT_FROM_TS']),
			td = bxGetDateFromTS(oEvent['~DT_TO_TS']);
		}
	}
	else
	{
		fd = this.GetUsableDateTime(new Date().getTime());
		td = this.GetUsableDateTime(new Date().getTime() + 3600000 /* one hour*/);
	}

	if (fd)
	{
		D.CAL._FromDateValue = D.CAL.DOM.FromDate.value = bxFormatDate(fd.date, fd.month, fd.year);
		D.CAL._FromTimeValue = D.CAL.DOM.FromTime.value = fd.bTime ? this.FormatTimeByNum(fd.hour, fd.min) : '';
	}
	else
	{
		D.CAL._FromDateValue = D.CAL._FromTimeValue = D.CAL.DOM.FromDate.value = D.CAL.DOM.FromTime.value = '';
	}

	if (td)
	{
		D.CAL.DOM.ToDate.value = bxFormatDate(td.date, td.month, td.year);
		D.CAL.DOM.ToTime.value = td.bTime ? this.FormatTimeByNum(td.hour, td.min) : '';
	}
	else
	{
		D.CAL.DOM.ToDate.value = D.CAL.DOM.ToTime.value = '';
	}

	D.CAL.DOM.FullDay.checked = !fd.bTime && !td.bTime;
	D.CAL.DOM.FullDay.onclick();

	D.CAL.DOM.Name.value = oEvent.NAME || '';
	D.CAL.DOM.Name.onchange();
	BX.focus(D.CAL.DOM.Name);

	window.pLHEEvDesc.ReInit(oEvent.DESCRIPTION || '');

	var sectId = oEvent.SECT_ID || this.GetLastSection();
	this.BuildSectionSelect(D.CAL.DOM.SectSelect, sectId);
	if (!sectId || _this.oActiveSections[sectId] == undefined)
		sectId = D.CAL.DOM.SectSelect.value;
	D.CAL.DOM.Warn.style.display = _this.oActiveSections[sectId] ? 'none' : 'block';

	if (!D.CAL.DOM.SectSelect.value && D.CAL.DOM.SectSelect.options.length > 0)
		D.CAL.DOM.SectSelect.options[0].selected = true;

	if (!oEvent.displayColor && this.oSections[sectId])
		oEvent.displayColor = this.oSections[sectId].COLOR;
	if (!oEvent.displayTextColor && this.oSections[sectId])
		oEvent.displayTextColor = this.oSections[sectId].TEXT_COLOR;
	if (oEvent.displayColor)
		D.CAL.ColorControl.Set(oEvent.displayColor, oEvent.displayTextColor);
	else if(this.oSections[sectId])
		D.CAL.ColorControl.Set(this.oSections[sectId].COLOR, this.oSections[sectId].TEXT_COLOR);

	// Set recurtion rules "RRULE"
	if (this.Event.IsRecursive(oEvent))
		D.CAL.DOM.RepeatSelect.value = oEvent.RRULE.FREQ;
	else
		D.CAL.DOM.RepeatSelect.value = 'NONE';
	D.CAL.DOM.RepeatSelect.onchange();

	if (bNew)
	{
		D.CAL.DOM.DelBut.style.display = 'none';
		D.CAL.DOM.Title.innerHTML = EC_MESS.NewEvent;
	}
	else
	{
		D.CAL.DOM.DelBut.style.display = '';
		D.CAL.DOM.Title.innerHTML = EC_MESS.EditEvent;
	}

	D.CAL.Loc = {OLD: '',NEW:'', CHANGED: false};
	if (D.CAL.bNew)
	{
		D.CAL.DOM.Importance.value = 'normal';
		if (Params.bChooseMR)
			D.CAL.Location.Set(0);
		else
			D.CAL.Location.Set(false, '');

		if (D.CAL.DOM.Accessibility)
			D.CAL.DOM.Accessibility.value = 'busy';
		if(D.CAL.DOM.Private)
			D.CAL.DOM.Private.checked = false;
	}
	else
	{
		D.CAL.DOM.Importance.value = D.CAL.oEvent.IMPORTANCE || 'normal';
		var loc = bxSpChBack(D.CAL.oEvent.LOCATION);

		D.CAL.Loc.OLD = loc;
		D.CAL.Loc.NEW = loc;

		var arLoc = this.ParseLocation(loc, true);
		if (arLoc.mrid && arLoc.mrevid)
		{
			D.CAL.Location.Set(arLoc.mrind, '');
			D.CAL.Loc.OLD_mrid = arLoc.mrid;
			D.CAL.Loc.OLD_mrevid = arLoc.mrevid;
		}
		else
		{
			D.CAL.Location.Set(false, loc);
		}

		if (D.CAL.DOM.Accessibility)
			D.CAL.DOM.Accessibility.value = D.CAL.oEvent.ACCESSIBILITY || 'busy';
		if (D.CAL.DOM.Private)
			D.CAL.DOM.Private.checked = D.CAL.oEvent.PRIVATE_EVENT || false;
	}

	// Get userfields editing html
	this.GetUserFieldsHTML(D.CAL.bNew ? {} : D.CAL.oEvent, true);

	if (Params.bRunPlanner)
		this.RunPlanner({curEventId: false, attendees: [{id: _this.userId, key: _this.userId, name: _this.userName}]});

	if (bExFromSimple)
		this.OpenExFromSimple(true);
}

JCEC.prototype.GetUserFieldsHTML = function(oEvent, bEdit)
{
	var
		eventId = oEvent.ID || 0,
		_this = this;

	if (!bEdit && eventId == 0 && this.newEventUF[bEdit ? 'edit' : 'view'])
		return Callback(this.newEventUF[bEdit ? 'edit' : 'view']);

	if (!bEdit && eventId > 0 && oEvent[bEdit ? '_UF_EDIT' : '_UF_VIEW'])
		return Callback(oEvent[bEdit ? '_UF_EDIT' : '_UF_VIEW']);

	function Callback(html)
	{
		if (bEdit)
		{
			html = BX.util.trim(html);
			var D = _this.oEditEventDialog;
			if (html != "")
			{
				D.CAL.DOM.UFGroup.style.display = "";
				D.CAL.DOM.UFCont.innerHTML = html;
				D.CAL.DOM.UFForm = document.forms['calendar-event-uf-form' + eventId]
			}
			else
			{
				D.CAL.DOM.UFGroup.style.display = "none";
				D.CAL.DOM.UFCont.innerHTML = '';
				D.CAL.DOM.UFForm = null;
			}

			if (eventId == 0)
				_this.newEventUF.edit = html;
			else
				oEvent._UF_EDIT = html;
		}
		else
		{
			html = BX.util.trim(html);
			var D = _this.oViewEventDialog;
			if (html != "")
			{
				D.CAL.DOM.UFGroup.style.display = "";
				D.CAL.DOM.UFCont.innerHTML = html;
			}
			else
			{
				D.CAL.DOM.UFGroup.style.display = "none";
				D.CAL.DOM.UFCont.innerHTML = '';
			}

			oEvent._UF_VIEW = html;
		}
		return true;
	};

	this.Request({
		getData: this.GetReqData(bEdit ? 'userfield_edit' : 'userfield_view', {event_id : eventId}),
		handler: function(oRes, html)
		{
			if (oRes)
			{
				Callback(html);
				return true;
			}
			return true;
		}
	});
}

JCEC.prototype._LocOnChange = function(oLoc, ind, value)
{
	var D = this.oEditEventDialog;
	if (ind === false)
	{
		D.CAL.Loc.NEW = value || '';
	}
	else
	{
		// Same meeting room
		if (ind != D.CAL.Loc.OLD_mrid)
			D.CAL.Loc.CHANGED = true;
		D.CAL.Loc.NEW = 'ECMR_' + this.meetingRooms[ind].ID;
	}
};

JCEC.prototype.SetEditingMeetingFields = function(bDeactivate)
{
	bDeactivate = !!bDeactivate;
	var D = this.oEditEventDialog;
	if (D.CAL.bMeetingStyleFields != bDeactivate)
	{
		D.CAL.bMeetingStyleFields = bDeactivate; //
		if (bDeactivate)
		{
			BX.addClass(D.CAL.Tabs[0].cont, 'bxc-meeting-edit-dis');
			D.CAL.DOM.pTabs.style.display = bDeactivate ? 'none' : '';
			// Move reminder params to first tab
			D.CAL.Tabs[0].cont.appendChild(D.CAL.DOM.RemindCnt);
		}
		else
		{
			BX.removeClass(D.CAL.Tabs[0].cont, 'bxc-meeting-edit-dis');
			D.CAL.DOM.pTabs.style.display = '';
			// Move reminder params back to the third tab
			D.CAL.Tabs[3].cont.insertBefore(D.CAL.DOM.RemindCnt, D.CAL.Tabs[3].cont.firstChild);
		}
	}
}

JCEC.prototype.CloseEditEventDialog = function(bClosePopup)
{
	if (!this.oEditEventDialog)
		return;
	if (bClosePopup === true)
		this.oEditEventDialog.close();
}

JCEC.prototype.OnChangeRepeatSelect = function(val)
{
	var
		D = this.oEditEventDialog,
		i, l, BYDAY, date;

	val = val.toUpperCase();

	if (val == 'NONE')
	{
		D.CAL.DOM.RepeatSect.style.display =  'none';
	}
	else
	{
		var oEvent = D.CAL.oEvent;
		D.CAL.DOM.RepeatSect.style.display =  'block';
		D.CAL.DOM.RepeatPhrase2.innerHTML = EC_MESS.DeDot; // Works only for de lang

		if (val == 'WEEKLY')
		{
			D.CAL.DOM.RepeatPhrase1.innerHTML = EC_MESS.EveryF;
			D.CAL.DOM.RepeatPhrase2.innerHTML += EC_MESS.WeekP;
			D.CAL.DOM.RepeatWeekDays.style.display = (val == 'WEEKLY') ? 'block' : 'none';
			BYDAY = {};

			if (!D.CAL.DOM.RepeatWeekDaysCh)
			{
				D.CAL.DOM.RepeatWeekDaysCh = [];
				for (i = 0; i < 7; i++)
					D.CAL.DOM.RepeatWeekDaysCh[i] = BX(this.id + 'bxec_week_day_' + i);
			}

			if (!D.CAL.bNew && oEvent && oEvent.RRULE && oEvent.RRULE.BYDAY)
			{
				BYDAY = oEvent.RRULE.BYDAY;
			}
			else
			{
				var date = BX.parseDate(D.CAL.DOM.FromDate.value);
				if (!date)
					date = bxGetDateFromTS(oEvent.DT_FROM_TS);

				if(date)
					BYDAY[this.GetWeekDayByInd(date.getDay())] = true;
			}

			for (i = 0; i < 7; i++)
				D.CAL.DOM.RepeatWeekDaysCh[i].checked = !!BYDAY[D.CAL.DOM.RepeatWeekDaysCh[i].value];
		}
		else
		{
			if (val == 'YEARLY')
				D.CAL.DOM.RepeatPhrase1.innerHTML = EC_MESS.EveryN;
			else
				D.CAL.DOM.RepeatPhrase1.innerHTML = EC_MESS.EveryM;

			if (val == 'DAILY')
				D.CAL.DOM.RepeatPhrase2.innerHTML += EC_MESS.DayP;
			else if (val == 'MONTHLY')
				D.CAL.DOM.RepeatPhrase2.innerHTML += EC_MESS.MonthP;
			else if (val == 'YEARLY')
				D.CAL.DOM.RepeatPhrase2.innerHTML += EC_MESS.YearP;

			D.CAL.DOM.RepeatWeekDays.style.display = 'none';
		}

		var bPer = oEvent && this.Event.IsRecursive(oEvent);
		D.CAL.DOM.RepeatCount.value = (D.CAL.bNew || !bPer) ? 1 : oEvent.RRULE.INTERVAL;

		if (D.CAL.bNew || !bPer)
		{
			D.CAL.DOM.RepeatDiapTo.value = '';
		}
		else
		{
			if (oEvent.RRULE.UNTIL)
			{
				var d = bxGetDateFromTS(oEvent.RRULE.UNTIL);
				if (d.date == 1 && d.month == 1 && d.year == 2038)
					D.CAL.DOM.RepeatDiapTo.value = '';
				else
					D.CAL.DOM.RepeatDiapTo.value = bxFormatDate(d.date, d.month, d.year);
			}
			else
			{
				D.CAL.DOM.RepeatDiapTo.value = '';
			}

		}
		D.CAL.DOM.RepeatDiapTo.onchange();
	}
}

// # # #  #  #  # View Event Dialog  # # #  #  #  #
JCEC.prototype.CreateViewEventDialog = function()
{
	var _this = this;
	var D = new BX.PopupWindow("BXCViewEvent", null, {
		autoHide: false,
		closeByEsc : true,
		zIndex: 0,
		offsetLeft: 0,
		offsetTop: 0,
		draggable: true,
		titleBar: {content: BX.create("span", {props: {className: 'bxec-popup-title', id: this.id + '_viewev_title'}, html: EC_MESS.NewCalenTitle})},
		closeIcon: {right : "12px", top : "10px"},
		className: "bxc-popup-tabed bxc-popup-window",
		buttons: [
			new BX.PopupWindowButton({
				text:  EC_MESS.Delete,
				id: this.id + '_viewev_del_but',
				className: "bxec-popup-link-icon bxec-popup-del-ev",
				events: {click : function(){
					if(_this.Event.Delete(D.CAL.oEvent))
						_this.CloseViewDialog(true);
				}}
			}),
			new BX.PopupWindowButton({
				text: EC_MESS.Edit,
				className: "bxec-popup-link-icon bxec-popup-ed-ev",
				id: this.id + '_viewev_edit_but',
				events: {click : function(){
					_this.ShowEditEventDialog({oEvent: D.CAL.oEvent});
					_this.CloseViewDialog(true);
				}}
			}),
			new BX.PopupWindowButton({
				text: EC_MESS.Close,
				className: "popup-window-button-accept",
				events: {click : function(){_this.CloseViewDialog(true);}}
			})
		],
		content: BX('bxec_view_ed_' + this.id),
		events: {}
	});

	BX.addCustomEvent(D, 'onPopupClose', BX.proxy(this.CloseViewDialog, this));

	D.CAL = {
		DOM: {
			pTabs: BX(this.id + '_viewev_tabs'),
			Name: BX(this.id + 'view-name-cnt'),
			Period: BX(this.id + 'view-period'),
			repRow: BX(this.id + 'view-repeat-cnt'),
			locationRow: BX(this.id + 'view-loc-cnt'),
			Location: BX(this.id + 'view-location'),
			Desc: BX(this.id + '_view_ed_desc'),
			sectRow: BX(this.id + 'view-sect-cnt'),
			sectCont: BX(this.id + 'view-ed-sect'),
			SpecRow: BX(this.id + 'view-spec-cnt'),
			ImpRow: BX(this.id + 'view-import-cnt'),
			ImpSpan: BX(this.id + '_view_ed_imp'),
			TITLE: BX(this.id + '_viewev_title'),
			UFGroup: BX(this.id + 'bxec_view_uf_group'),
			UFCont: BX(this.id + 'bxec_view_uf_cont'),
			delBut : BX(this.id + '_viewev_del_but'),
			editBut : BX(this.id + '_viewev_edit_but')
		}
	};

	if (this.bIntranet)
	{
		D.CAL.DOM.AccessabRow = BX(this.id + 'view-accessab-cnt');
		D.CAL.DOM.AccessSpan = BX(this.id + '_view_ed_accessibility');
		D.CAL.DOM.privateRow = BX(this.id + 'view-priv-cnt');
	}

	if (this.allowMeetings)
	{
		D.CAL.DOM.meetingTextRow = BX(this.id + 'view-meet-text-cnt');
		D.CAL.DOM.MeetingText = BX(this.id + '_view_ed_meet_text');

		D.CAL.UserControl = new ECUserControll({
			oEC: this,
			view: true,
			AttendeesCont : BX(this.id + 'view_att_cont'),
			AttendeesList : BX(this.id + 'view_att_list'),
			SummaryCont: BX(this.id + 'view_att_summary')
		});
		D.CAL.DOM.Host = BX(this.id + 'view_host_link');

		D.CAL.DOM.AttRow = BX(this.id + 'attendees_cnt');
		D.CAL.DOM.ConfRow = BX(this.id + 'confirm_cnt');
		D.CAL.DOM.ConfCnt1 = BX(this.id + 'status-conf-cnt1');
		D.CAL.DOM.ConfCnt2 = BX(this.id + 'status-conf-cnt2');
		D.CAL.DOM.ConfCnt3 = BX(this.id + 'status-conf-cnt3');
		D.CAL.DOM.ConfCnt4 = BX(this.id + 'status-conf-cnt4');

		D.CAL.DOM.AcceptLink2 = BX(this.id + 'accept-link-2');
		D.CAL.DOM.AcceptLink3 = BX(this.id + 'accept-link-3');
		D.CAL.DOM.AcceptLink4 = BX(this.id + 'accept-link-4');
		D.CAL.DOM.AcceptLink2.onclick =
		D.CAL.DOM.AcceptLink3.onclick =
		D.CAL.DOM.AcceptLink4.onclick = function(e){if (_this.Event.SetMeetingStatus(true)){D.close(); }; return BX.PreventDefault(e || window.event);};

		D.CAL.DOM.DeclineLink1 = BX(this.id + 'decline-link-1');
		D.CAL.DOM.DeclineLink2 = BX(this.id + 'decline-link-2');
		D.CAL.DOM.DeclineLink1.onclick =
		D.CAL.DOM.DeclineLink2.onclick = function(e){if(_this.Event.SetMeetingStatus(false)){D.close();}; return BX.PreventDefault(e || window.event);};
		D.CAL.DOM.DeclineNotice = BX(this.id + 'decline-notice');

		D.CAL.DOM.StatusComCnt = BX(this.id + 'status-conf-comment');
		D.CAL.DOM.StatusComInp = BX(this.id + 'conf-comment-inp');

		D.CAL.defStatValue = D.CAL.DOM.StatusComInp.value;
		D.CAL.DOM.StatusComInp.onclick = D.CAL.DOM.StatusComInp.onfocus = function()
		{
			if (this.value == D.CAL.defStatValue)
				this.value = "";
			BX.removeClass(this, 'bxc-st-dis');
		};
		D.CAL.DOM.StatusComInp.onblur = function()
		{
			if (BX.util.trim(this.value) == "")
				this.value = D.CAL.defStatValue;
			BX.addClass(this, 'bxc-st-dis');
		};
	}

	this.ChargePopupTabs(D, this.id + 'view-tab-');
	this.oViewEventDialog = D;
}

JCEC.prototype.ShowViewEventDialog = function(oEvent)
{
	if (!this.oViewEventDialog)
		this.CreateViewEventDialog();

	var
		D = this.oViewEventDialog,
		perHTML,
		d_from = bxGetDateFromTS(oEvent.DT_FROM_TS),
		d_to = bxGetDateFromTS(oEvent.DT_TO_TS),
		s_day_from = this.days[this.ConvertDayIndex(d_from.oDate.getDay())][0],
		s_day_to = this.days[this.ConvertDayIndex(d_to.oDate.getDay())][0];

	D.CAL.DOM.TITLE.innerHTML = EC_MESS.ViewingEvent + ': ' + BX.util.htmlspecialchars(oEvent.NAME);
	D.CAL.DOM.Name.innerHTML = '<span' + this.Event.GetLabelStyle(oEvent) + '>' + BX.util.htmlspecialchars(oEvent.NAME) + '</span>';
	D.CAL.DOM.Name.title = oEvent.NAME;

	perHTML = s_day_from + ' ' + bxFormatDate(d_from.date, d_from.month, d_from.year);
	if (d_from.bTime)
		perHTML +=  ' ' + this.FormatTimeByNum(d_from.hour, d_from.min);

	if (oEvent.DT_FROM_TS != oEvent.DT_TO_TS)
	{
		perHTML += ' - ' + s_day_to + ' ' + bxFormatDate(d_to.date, d_to.month, d_to.year);
		if (d_to.bTime)
			perHTML +=  ' ' + this.FormatTimeByNum(d_to.hour, d_to.min);
	}

	D.CAL.DOM.Period.innerHTML = perHTML;
	D.CAL.DOM.ImpSpan.innerHTML = EC_MESS['Importance_' + oEvent.IMPORTANCE];

	// Calendar
	if (this.oSections[oEvent.SECT_ID])
	{
		D.CAL.DOM.sectRow.style.display = '';
		D.CAL.DOM.sectCont.innerHTML = BX.util.htmlspecialchars(this.oSections[oEvent.SECT_ID].NAME);
	}
	else
	{
		D.CAL.DOM.sectRow.style.display = 'none';
	}

	// Description
	if (oEvent['~DESCRIPTION'] != '')
	{
		D.CAL.DOM.Desc.innerHTML = oEvent['~DESCRIPTION'];
		D.CAL.Tabs[1].tab.style.display = 'block'; // Show tab
	}
	else
	{
		D.CAL.Tabs[1].tab.style.display = 'none'; // Hide tab
	}

	// Location
	var
		lochtml = '',
		loc = this.ParseLocation(oEvent.LOCATION, true);

	if (loc.mrid == false && loc.str.length > 0)
		lochtml = BX.util.htmlspecialchars(loc.str);
	else if (loc.mrid && loc.MR)
		lochtml = loc.MR.URL ? '<a href="' + loc.MR.URL+ '" target="_blank">' + BX.util.htmlspecialchars(loc.MR.NAME) + '</a>' : BX.util.htmlspecialchars(loc.MR.NAME);

	if (lochtml.length > 0)
	{
		D.CAL.DOM.locationRow.style.display = '';
		D.CAL.DOM.Location.innerHTML = lochtml;
	}
	else
	{
		D.CAL.DOM.locationRow.style.display = 'none';
	}

	// repeating
	if (this.Event.IsRecursive(oEvent))
	{
		D.CAL.DOM.repRow.style.display = '';
		var repeatHTML = '';
		switch (oEvent.RRULE.FREQ)
		{
			case 'DAILY':
				repeatHTML += '<b>' + EC_MESS.EveryM_ + ' ' + oEvent.RRULE.INTERVAL + EC_MESS.DeDot + EC_MESS._J + ' ' + EC_MESS.DayP + '</b>';
				break;
			case 'WEEKLY':
				repeatHTML += '<b>' + EC_MESS.EveryF_ + ' ';
				if (oEvent.RRULE.INTERVAL > 1)
					repeatHTML += oEvent.RRULE.INTERVAL + EC_MESS.DeDot + EC_MESS._U + ' ';
				repeatHTML += EC_MESS.WeekP + ': ';
				var n = 0;
				for (var i in oEvent.RRULE.BYDAY)
				{
					if(oEvent.RRULE.BYDAY[i])
						repeatHTML += (n++ > 0 ? ', ' : '') + this.Day(oEvent.RRULE.BYDAY[i])[0];
				}
				repeatHTML += '</b>';
				break;
			case 'MONTHLY':
				repeatHTML += '<b>' + EC_MESS.EveryM_ + ' ';
				if (oEvent.RRULE.INTERVAL > 1)
					repeatHTML += oEvent.RRULE.INTERVAL + EC_MESS.DeDot + EC_MESS._J + ' ';
				repeatHTML +=  EC_MESS.MonthP + ', ' + EC_MESS.DeAm + bxInt(d_from.date) + EC_MESS.DeDot + EC_MESS.DateP_ + '</b>';
				break;
			case 'YEARLY':
				repeatHTML += '<b>' + EC_MESS.EveryN_ + ' ';
				if (oEvent.RRULE.INTERVAL > 1)
					repeatHTML += oEvent.RRULE.INTERVAL + EC_MESS.DeDot + EC_MESS._J + ' ';
				repeatHTML +=  EC_MESS.YearP + ', ' + EC_MESS.DeAm + bxInt(d_from.date) + EC_MESS.DeDot + EC_MESS.DateP_ + ' ' + EC_MESS.DeDes + bxInt(d_from.month) + EC_MESS.DeDot + EC_MESS.MonthP_ + '</b>';
				break;
		}

		var d = bxGetDateFromTS(oEvent['~DT_FROM_TS']);
		repeatHTML += '<br> ' + EC_MESS.From_ + ' ' + bxFormatDate(d.date, d.month, d.year);

		d = bxGetDateFromTS(oEvent.RRULE.UNTIL);
		if (d && (d.date != 1 || d.month != 1 || d.year != 2038))
			repeatHTML += ' ' + EC_MESS.To_ + ' ' + bxFormatDate(d.date, d.month, d.year);

		D.CAL.DOM.repRow.cells[1].innerHTML = repeatHTML;
	}
	else
	{
		D.CAL.DOM.repRow.style.display = 'none';
	}
	D.CAL.oEvent = oEvent;

	if (this.bIntranet)
	{
		if (this.allowMeetings)
		{
			D.CAL.DOM.meetingTextRow.style.display = 'none';
			D.CAL.DOM.AttRow.style.display = "none";
			D.CAL.DOM.ConfCnt1.style.display = "none";
			D.CAL.DOM.ConfCnt2.style.display = "none";
			D.CAL.DOM.ConfCnt3.style.display = "none";
			D.CAL.DOM.ConfCnt4.style.display = "none";
			D.CAL.DOM.StatusComCnt.style.display = "none";
		}

		if (this.allowMeetings && this.Event.IsMeeting(oEvent))
		{
			// Set host info
			if (oEvent.MEETING)
			{
				D.CAL.DOM.Host.innerHTML = BX.util.htmlspecialchars(oEvent.MEETING.HOST_NAME || oEvent.MEETING_HOST) + this.ItsYou(oEvent.MEETING_HOST);
				D.CAL.DOM.Host.href = this.GetUserHref(oEvent.MEETING_HOST);
			}

			D.CAL.DOM.AttRow.style.display = "";
			var Attendees = this.Event.Attendees(oEvent);
			D.CAL.UserControl.SetValues(Attendees);

			if (oEvent.USER_MEETING)
			{
				// User already confirmed
				var isAttendee = this.Event.IsAttendee(oEvent);

				if (isAttendee)
				{
					if (oEvent.USER_MEETING.STATUS == 'Y')
					{
						D.CAL.DOM.ConfCnt1.style.display = '';
					}
					else if(oEvent.USER_MEETING.STATUS == 'N')
					{
						D.CAL.DOM.ConfCnt4.style.display = '';
						D.CAL.DOM.DeclineNotice.style.display = (this.startupEvent && this.startupEvent.ID == oEvent.ID && !this.userSettings.showDeclined) ? '' : 'none';
					}
					else if(oEvent.USER_MEETING.STATUS == 'Q')
					{
						D.CAL.DOM.ConfCnt2.style.display = '';
						D.CAL.DOM.StatusComCnt.style.display = '';
					}
				}
			}

			if (oEvent.MEETING && oEvent.MEETING.OPEN)
			{
				var bSet = true;
				for(var ind in Attendees)
				{
					if (Attendees[ind] && Attendees[ind].id == this.userId && Attendees[ind].status != 'N')
					{
						bSet = false;
						break;
					}
				}
				if (bSet)
					D.CAL.DOM.ConfCnt3.style.display = '';
			}

			// Show invitation text
			if (oEvent.MEETING.TEXT && oEvent.MEETING.TEXT.length > 0)
			{
				var text = BX.util.htmlspecialchars(oEvent.MEETING.TEXT);
				text = text.replace(/\n/g, "<br>");
				D.CAL.DOM.MeetingText.innerHTML = text;
				D.CAL.DOM.meetingTextRow.style.display = '';
			}
		}

		if (oEvent.ACCESSIBILITY)
		{
			D.CAL.DOM.AccessabRow.style.display = '';
			D.CAL.DOM.AccessSpan.innerHTML = EC_MESS['Acc_' + oEvent.ACCESSIBILITY];
		}
		else
		{
			D.CAL.DOM.AccessabRow.style.display = 'none';
		}

		if (oEvent.PRIVATE_EVENT)
			D.CAL.DOM.privateRow.style.display = '';
		else
			D.CAL.DOM.privateRow.style.display = 'none';

	}

	// Hide edit & delete links for read only events
	if (this.bIntranet && this.Event.IsHost(oEvent))
	{
		D.CAL.DOM.delBut.style.display = "";
		D.CAL.DOM.editBut.style.display = "";
	}
	else if (this.bIntranet && this.Event.IsAttendee(oEvent))
	{
		D.CAL.DOM.delBut.style.display = "none";
		D.CAL.DOM.editBut.style.display = "";
	}
	else
	{
		if (this.Event.CanDo(oEvent, 'edit'))
		{
			D.CAL.DOM.delBut.style.display = "";
			D.CAL.DOM.editBut.style.display = "";
		}
		else
		{
			D.CAL.DOM.delBut.style.display = "none";
			D.CAL.DOM.editBut.style.display = "none";
		}
	}

	D.show();
	this.SetPopupTab(0, D); // Activate first tab

	// Get userfields html
	this.GetUserFieldsHTML(oEvent, false);

	this.Event.Blink(oEvent, false);
}

JCEC.prototype.CloseViewDialog = function(bClosePopup)
{
	if (bClosePopup === true)
		this.oViewEventDialog.close();
};

// # # #  #  #  # EDIT CALENDAR DIALOG # # #  #  #  #
JCEC.prototype.CreateSectDialog = function()
{
	var
		pTitle = BX.create("span", {html: EC_MESS.NewCalenTitle}),
		_this = this;

	var D = new BX.PopupWindow("BXCSection", null, {
		autoHide: false,
		closeByEsc : true,
		zIndex: 0,
		offsetLeft: 0,
		offsetTop: 0,
		draggable: true,
		titleBar: {content: pTitle},
		closeIcon: {right : "12px", top : "10px"},
		className: "bxc-popup-tabed bxc-popup-window",
		buttons: [
			new BX.PopupWindowButton({
				text: EC_MESS.DelSect,
				id: this.id + '_bxec_cal_del_but',
				className: "bxec-popup-link-icon bxec-popup-del-ex",
				events: {click : function(){
					if (_this.DeleteSection(D.CAL.oSect))
						_this.CloseSectDialog(true);
				}}
			}),
			new BX.PopupWindowButton({
				text: EC_MESS.Save,
				className: "popup-window-button-accept",
				events: {click : function(){if (_this.SaveSection()){_this.CloseSectDialog(true);}}}
			}),
			new BX.PopupWindowButtonLink({
				text: EC_MESS.Close,
				className: "popup-window-button-link-cancel",
				events: {click : function(){_this.CloseSectDialog(true);}}
			})
		],
		content: BX('bxec_sect_d_' + this.id),
		events: {}
	});

	BX.addCustomEvent(D, 'onPopupClose', BX.proxy(this.CloseSectDialog, this));

	D.CAL = {
		DOM: {
			Title: pTitle,
			pTabs: BX(this.id + '_editsect_tabs'),
			Name: BX(this.id + '_edcal_name'),
			Desc: BX(this.id + '_edcal_desc'),
			//Color: pColor,
			ExpAllow: BX(this.id + '_bxec_cal_exp_allow'),
			delBut: BX(this.id + '_bxec_cal_del_but')
		}
	};

	D.CAL.Access = new ECCalendarAccess({
		bind: 'calendar_section',
		GetAccessName: BX.proxy(this.GetAccessName, this),
		pCont: BX(this.id + 'access-values-cont'),
		pLink: BX(this.id + 'access-link')
	});

	D.CAL.ColorControl = this.InitColorDialogControl('sect', function(color, textColor){
		D.CAL.Color = color;
		D.CAL.TextColor = textColor;
	});

	if (this.arConfig.bExchange && this.Personal())
		D.CAL.DOM.Exch = BX(this.id + '_bxec_cal_exch');

	this.ChargePopupTabs(D, this.id + 'sect-tab-');
	this.oSectDialog = D;

	if (this.bUser && this.Personal())
		D.CAL.DOM.MeetingCalendarCh = BX(this.id + '_bxec_meeting_calendar');

	if (this.bSuperpose && this.Personal())
	{
		D.CAL.DOM.add2SPCont = BX(this.id + '_bxec_cal_add2sp_cont');
		D.CAL.DOM.add2SP = BX(this.id + '_bxec_cal_add2sp');
	}
	D.CAL.DOM.ExpAllow.onclick = function() {_this._AllowCalendarExportHandler(this.checked);};
}

JCEC.prototype.ShowSectionDialog = function(oSect)
{
	if (!this.oSectDialog)
		this.CreateSectDialog();

	var D = this.oSectDialog;
	D.show();
	this.SetPopupTab(0, D); // Activate first tab

	if (!oSect)
	{
		oSect = {
			PERM: {
				access:true,//this.PERM.access,
				add:true, edit:true, edit_section:true, view_full:true, view_time:true, view_title:true
			}
		};
		D.CAL.bNew = true;

		D.CAL.DOM.Title.innerHTML = EC_MESS.NewCalenTitle;
		D.CAL.DOM.delBut.style.display = 'none';

		oSect.COLOR = this.GetFreeDialogColor();

		D.CAL.DOM.ExpAllow.checked = true;
		this._AllowCalendarExportHandler(true);
		if (D.CAL.DOM.ExpSet)
			D.CAL.DOM.ExpSet.value = 'all';

		if (this.bSuperpose && this.Personal())
		{
			D.CAL.DOM.add2SP.checked = true;
			D.CAL.DOM.add2SPCont.style.display = BX.browser.IsIE() ? 'inline' : 'table-row';
		}

		if (this.arConfig.bExchange && this.Personal())
		{
			D.CAL.DOM.Exch.disabled = false;
			D.CAL.DOM.Exch.checked = true;
		}

		// Default access
		oSect.ACCESS = this.new_section_access;
	}
	else // Edit Section
	{
		if (this.arConfig.bExchange && this.Personal())
		{
			D.CAL.DOM.Exch.checked = !!oSect.IS_EXCHANGE;
			D.CAL.DOM.Exch.disabled = true;
		}

		D.CAL.bNew = false;
		D.CAL.DOM.Title.innerHTML = EC_MESS.EditCalenTitle;
		D.CAL.DOM.delBut.style.display = '';

		if (!oSect.COLOR)
			oSect.COLOR = this.arConfig.arCalColors[0];

		D.CAL.DOM.ExpAllow.checked = oSect.EXPORT || false;
		this._AllowCalendarExportHandler(oSect.EXPORT);
		if (oSect.EXPORT)
			D.CAL.DOM.ExpSet.value = oSect.EXPORT_SET || 'all';
		if (this.bSuperpose  && this.Personal())
			D.CAL.DOM.add2SPCont.style.display = 'none';
	}

	D.CAL.ColorControl.Set(oSect.COLOR, oSect.TEXT_COLOR);

	// Access
	this.ShowPopupTab(D.CAL.Tabs[1], oSect.PERM.access);
	if (oSect.PERM.access)
	{
		if (this.type == 'user' && this.Personal() && oSect.ACCESS['U' + this.ownerId])
			delete oSect.ACCESS['U' + this.ownerId];
		else if (this.type == 'group' && oSect.ACCESS['SG' + this.ownerId + '_A'])
			delete oSect.ACCESS['SG' + this.ownerId + '_A'];

		D.CAL.Access.SetSelected(oSect.ACCESS);
	}

	D.CAL.oSect = oSect;
	this.bEditCalDialogOver = false;

	if (this.bUser && this.Personal())
		D.CAL.DOM.MeetingCalendarCh.checked = (!D.CAL.bNew && this.userSettings.meetSection == oSect.ID);

	var _this = this;
	D.CAL.DOM.Name.value = oSect.NAME || '';
	D.CAL.DOM.Desc.value = oSect.DESCRIPTION || '';

	BX.focus(D.CAL.DOM.Name);
}

JCEC.prototype.CloseSectDialog = function(bClosePopup)
{
	if (bClosePopup === true)
		this.oSectDialog.close();
}

JCEC.prototype._AllowCalendarExportHandler = function(bAllow)
{
	if (!this.oSectDialog.CAL.DOM.ExpDiv)
		this.oSectDialog.CAL.DOM.ExpDiv = BX(this.id + '_bxec_calen_exp_div');
	if (!this.oSectDialog.CAL.DOM.ExpSet && bAllow)
		this.oSectDialog.CAL.DOM.ExpSet = BX(this.id + '_bxec_calen_exp_set');
	this.oSectDialog.CAL.DOM.ExpDiv.style.display = bAllow ? 'block' : 'none';
}

// # # #  #  #  # Export Calendar Dialog # # #  #  #  #
JCEC.prototype.CreateExportDialog = function()
{
	var _this = this;
	var pTitle = BX.create("span");
	var D = new BX.PopupWindow("BXCExportDialog", null, {
		autoHide: false,
		closeByEsc : true,
		zIndex: 0,
		offsetLeft: 0,
		offsetTop: 0,
		draggable: true,
		titleBar: {content: pTitle},
		closeIcon: {right : "12px", top : "10px"},
		className: "bxc-popup-window",
		buttons: [
			new BX.PopupWindowButton({
				text: EC_MESS.Close,
				className: "popup-window-button-accept",
				events: {click : function(){_this.CloseExportDialog(true);}}
			})
		],
		content: BX('bxec_excal_' + this.id)
	});

	BX.addCustomEvent(D, 'onPopupClose', BX.proxy(this.CloseExportDialog, this));

	D.CAL = {
		DOM: {
			Title: pTitle,
			Link: BX(this.id + '_excal_link'),
			NoticeLink: BX(this.id + '_excal_link_outlook'),
			Text: BX(this.id + '_excal_text'),
			Warn: BX(this.id + '_excal_warning')
		}
	};

	D.CAL.DOM.NoticeLink.onclick = function(){this.parentNode.className = "";};
	this.oExportDialog = D;
}

JCEC.prototype.ShowExportDialog = function(oCalen)
{
	if (oCalen && oCalen.EXPORT && !oCalen.EXPORT.ALLOW)
		return;

	if (!this.oExportDialog)
		this.CreateExportDialog();

	var D = this.oExportDialog;
	D.show();

	D.CAL.DOM.NoticeLink.parentNode.className = "bxec-excal-notice-hide"; // Hide help
	D.CAL.DOM.Warn.className = 'bxec-export-warning-hidden';

	// Create link
	var link = this.path;
	link += (link.indexOf('?') >= 0) ? '&' : '?';

	if (oCalen)
	{
		D.CAL.DOM.Title.innerHTML = EC_MESS.ExpDialTitle;
		D.CAL.DOM.Text.innerHTML = EC_MESS.ExpText;
		link += 'action=export' + oCalen.EXPORT.LINK;
	}

	var webCalLink = 'webcal' + link.substr(link.indexOf('://'));
	D.CAL.DOM.Link.onclick = function(e) {window.location.href = webCalLink; BX.PreventDefault(e);};
	D.CAL.DOM.Link.href = link;
	D.CAL.DOM.Link.innerHTML = link;

	BX.ajax.get(link + '&check=Y', "", function(result)
	{
		setTimeout(function()
		{
			BX.closeWait(D.CAL.DOM.Title);
			if (!result || result.length <= 0 || result.toUpperCase().indexOf('BEGIN:VCALENDAR') == -1)
				D.CAL.DOM.Warn.className = 'bxec-export-warning';
		}, 300);
	});
}

JCEC.prototype.CloseExportDialog = function(bClosePopup)
{
	if (bClosePopup === true)
		this.oExportDialog.close();
};

// # # #  #  #  # Superpose Calendar Dialog # # #  #  #  #
JCEC.prototype.CreateSuperposeDialog = function()
{
	var _this = this;
	var D = new BX.PopupWindow("BXCSuperpose", null, {
		autoHide: false,
		closeByEsc : true,
		zIndex: 0,
		offsetLeft: 0,
		offsetTop: 0,
		draggable: true,
		titleBar: {content: BX.create("span", {html: EC_MESS.SPCalendars})},
		closeIcon: { right : "12px", top : "10px"},
		className: "bxc-popup-window",
		buttons: [
			new BX.PopupWindowButton({
				text: EC_MESS.Save,
				className: "popup-window-button-accept",
				events: {click : function(){
					_this.SPD_SaveSuperposed();
					_this.CloseSuperposeDialog(true);
				}}
			}),
			new BX.PopupWindowButtonLink({
				text: EC_MESS.Close,
				className: "popup-window-button-link-cancel",
				events: {click : function(){_this.CloseSuperposeDialog(true);}}
			})
		],
		content: BX('bxec_superpose_' + this.id),
		events: {}
	});

	BX.addCustomEvent(D, 'onPopupClose', BX.proxy(this.CloseSuperposeDialog, this));

	D.CAL = {
		DOM: {
			UserCntInner: BX('bxec_sp_type_user_cont_' + this.id),
			GroupCnt: BX('bxec_sp_type_group_' + this.id),
			GroupCntInner: BX('bxec_sp_type_group_cont_' + this.id),
			CommonCnt: BX('bxec_sp_type_common_' + this.id),
			DelAllUsersLink: BX('bxec_sp_dell_all_sp_' + this.id),
			UserSearchCont: BX(this.id + '_sp_user_search_input_cont'),
			NotFoundNotice: BX(this.id + '_sp_user_nf_notice'),
			arCat : {}
		},
		arSect: {},
		arGroups: {},
		arCals: {},
		curTrackedUsers: {}
	};

	BX.addCustomEvent(window, "onUserSelectorOnChange", function(arUsers){D.CAL.curTrackedUsers = arUsers;});

	D.CAL.DOM.AddUsersLinkCont = BX(this.id + '_user_control_link_sp');

	D.CAL.DOM.AddUsersLinkCont.onclick = function(e)
	{
		if (BX.PopupMenu && BX.PopupMenu.currentItem)
			BX.PopupMenu.currentItem.popupWindow.close();

		if(!e)
			e = window.event;

		if (!D.CAL.DOM.SelectUserPopup)
		{
			D.CAL.DOM.SelectUserPopup = BX.PopupWindowManager.create("bxc-user-popup-sp", D.CAL.DOM.AddUsersLinkCont, {
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
							D.CAL.DOM.SelectUserPopup.close();

							var users = [], i;
							for (i in D.CAL.curTrackedUsers)
								if (D.CAL.curTrackedUsers[i] && i > 0 && parseInt(i) == i)
									users.push(i);

							_this.Request({
								postData: _this.GetReqData('spcal_user_cals', {users : users}),
								errorText: EC_MESS.CalenSaveErr,
								handler: function(oRes)
								{
									if (oRes)
									{
										if (oRes.sections)
										{
											_this.SPD_BuildSections(oRes.sections, true);
										}
										else
										{
											_this.oSupDialog.CAL.DOM.NotFoundNotice.style.visibility = 'visible';
											setTimeout(function(){_this.oSupDialog.CAL.DOM.NotFoundNotice.style.visibility = 'hidden';}, 4000);
										}
										return true;
									}
									return false;
								}
							});
						}}
					}),
					new BX.PopupWindowButtonLink({
						text: EC_MESS.Close,
						className: "popup-window-button-link-cancel",
						events: {click : function(){D.CAL.DOM.SelectUserPopup.close();}}
					})
				]
			});
		}

		D.CAL.curTrackedUsers = {};
		D.CAL.DOM.SelectUserPopup.show();
		BX.PreventDefault(e);
	};

	this.oSupDialog = D;

	if (this.arSPSections)
		this.SPD_BuildSections(this.arSPSections, false); // All sections with checkboxes and groups and categories builds here
}

JCEC.prototype.SPD_BuildSections = function(arSections, bRegister)
{
	var
		_this = this,
		D = this.oSupDialog,
		i, oSect, pCatCont, pItem, key, catTitle, id;

	for (i in arSections)
	{
		oSect = arSections[i];
		if (!oSect.ID)
			return;

		if (oSect.CAL_TYPE == 'user')
		{
			pCatCont = D.CAL.DOM.UserCntInner;
		}
		else if(oSect.CAL_TYPE == 'group')
		{
			pCatCont = D.CAL.DOM.GroupCntInner;
			D.CAL.DOM.GroupCnt.style.display = "block";
		}
		else
		{
			pCatCont = D.CAL.DOM.CommonCnt;
			D.CAL.DOM.CommonCnt.style.display = "block";
		}

		key = oSect.CAL_TYPE + oSect.OWNER_ID;
		if (!D.CAL.DOM.arCat[key] || !BX.isNodeInDom(D.CAL.DOM.arCat[key].pCat))
		{
			if (oSect.CAL_TYPE == 'user' || oSect.CAL_TYPE == 'group')
				catTitle = oSect.OWNER_NAME;
			else
				catTitle = oSect.TYPE_NAME;

			pCat = pCatCont.appendChild(BX.create("DIV", {props: {className: "bxc-spd-cat"}}));
			pCatTitle = pCat.appendChild(BX.create("DIV", {props: {className: "bxc-spd-cat-title"}, html: '<span class="bxc-spd-cat-plus"></span><span class="bxc-spd-cat-title-inner">' + BX.util.htmlspecialchars(catTitle) + '</span>'}));
			pCatSections = pCat.appendChild(BX.create("DIV", {props: {className: "bxc-spd-cat-sections"}}));
			pCatTitle.onclick = function(){BX.toggleClass(this.parentNode, "bxc-spd-cat-collapsed")}

			// Add link for del user from tracking users list
			if (oSect.CAL_TYPE == 'user' && oSect.OWNER_ID != this.userId)
			{
				pCatTitle.appendChild(BX.create("A", {props: {href: "javascript:void(0);", className: "bxc-spd-del-cat", title: EC_MESS.DeleteDynSPGroupTitle}, text: EC_MESS.DeleteDynSPGroup, events: {click: function(e){_this.SPD_DelTrackingUser(this.getAttribute('bx-data'), this); return BX.PreventDefault(e)}}})).setAttribute('bx-data', oSect.OWNER_ID);
			}

			D.CAL.DOM.arCat[key] = {
				pCat : pCat,
				pTitle : pCatTitle,
				pSections : pCatSections
			};
		}

		id = this.id + "spd-sect" + oSect.ID;
		pItem = BX.create("DIV", {props: {className: "bxc-spd-sect-cont"}});
		pCh = pItem.appendChild(BX.create("SPAN", {props: {className: "bxc-spd-sect-check"}})).appendChild(BX.create("INPUT", {props: {type: "checkbox", id: id}}));
		pLabel = pItem.appendChild(BX.create("SPAN", {props: {className: "bxc-spd-sect-label"}, html: '<label for="' + id + '"><span>' + BX.util.htmlspecialchars(oSect.NAME) + '</span></label>'}));

		D.CAL.DOM.arCat[key].pSections.appendChild(pItem);
		D.CAL.arSect[oSect.ID] = {pCh: pCh, pItem: pItem, oSect: oSect};

		if (bRegister)
			this.arSPSections.push(oSect);
	}
};

JCEC.prototype.SPD_SaveSuperposed = function()
{
	var
		i, item;

	for (i in this.oSupDialog.CAL.arSect)
	{
		item = this.oSupDialog.CAL.arSect[i];
		if (item.pCh.checked)
		{
			// Section already added to superposed
			if(this.arSectionsInd[i])
			{
				this.arSections[this.arSectionsInd[i]].SUPERPOSED = true;
			}
			else if(!this.arSectionsInd[i])
			{
				item.oSect.SUPERPOSED = true;
				this.arSections.push(item.oSect);
				this.arSectionsInd[item.oSect.ID] = this.arSections.length - 1;
			}
		}
		else
		{
			if (this.arSectionsInd[i])
			{
				this.arSections[this.arSectionsInd[i]].SUPERPOSED = false;
			}
		}
	}
	this.SetSuperposed();
};

JCEC.prototype.SPD_DelTrackingUser = function(userId, pLink)
{
	var pCont = BX.findParent(pLink, {className: 'bxc-spd-cat'});
	if (pCont)
		pCont.parentNode.removeChild(pCont);

	for (i in this.oSupDialog.CAL.arSect)
	{
		item = this.oSupDialog.CAL.arSect[i];
		if (item && item.oSect && item.oSect.CAL_TYPE=='user' && item.oSect.OWNER_ID == userId)
			item.pCh.checked = false;
	}
	this.SPD_SaveSuperposed();

	this.Request({
		postData: this.GetReqData('spcal_del_user', {userId: parseInt(userId)}),
		handler: function(oRes)
		{
			if (oRes)
				return true;
		}
	});
}

JCEC.prototype.ShowSuperposeDialog = function()
{
	var _this = this;
	if (!this.arSPSections)
	{
		return this.Request({
			getData: _this.GetReqData('get_superposed'),
			handler: function(oRes)
			{
				if (oRes)
				{
					_this.arSPSections = oRes.sections || [];
					return _this.ShowSuperposeDialog();
				}
				return false;
			}
		});
	}

	if (!this.oSupDialog)
		this.CreateSuperposeDialog();

	var D = this.oSupDialog;
	D.show();

	for (var i = 0, l = l = this.arSections.length; i < l; i++)
	{
		if (this.arSections[i].ID && D.CAL.arSect[this.arSections[i].ID])
			D.CAL.arSect[this.arSections[i].ID].pCh.checked = !!this.arSections[i].SUPERPOSED;
	}
}

JCEC.prototype.CloseSuperposeDialog = function(bClosePopup)
{
	if (bClosePopup === true)
		this.oSupDialog.close();
};

JCEC.prototype.BuildSectionSelect = function(oSel, value)
{
	oSel.options.length = 0;
	var i, opt, el, selected;
	oSel.parentNode.className = 'bxec-cal-sel-cel';
	for (i = 0; i < this.arSections.length; i++)
	{
		el = this.arSections[i];
		if (el.PERM.edit_section && this.IsCurrentViewSect(el))
		{
			selected = value == el.ID;
			opt = new Option(el.NAME, el.ID, selected, selected);
			oSel.options.add(opt);
			if(!BX.browser.IsIE())
				opt.style.backgroundColor = el.COLOR;
		}
	}

	if (oSel.options.length <= 0)
		oSel.parentNode.className = 'bxec-cal-sel-cel-empty';
};

JCEC.prototype.IsCurrentViewSect = function(el)
{
	return el.CAL_TYPE == this.type && el.OWNER_ID == this.ownerId;
};

// # # #  #  #  # User Settings Dialog # # #  #  #  #
JCEC.prototype.CreateSetDialog = function()
{
	var _this = this;
	var D = new BX.PopupWindow("BXCSettings", null, {
		autoHide: false,
		closeByEsc : true,
		zIndex: 0,
		offsetLeft: 0,
		offsetTop: 0,
		draggable: true,
		titleBar: {content: BX.create("span", {props: {className: 'bxec-popup-title', id: this.id + '_viewev_title'}, html: this.PERM.access ? EC_MESS.Settings : EC_MESS.UserSettings})},
		closeIcon: {right : "12px", top : "10px"},
		className: 'bxc-popup-tabed bxc-popup-window',
		buttons: [
			new BX.PopupWindowButton({
				text: EC_MESS.Save,
				className: "popup-window-button-accept",
				events: {click : function(){
					_this.CloseSetDialog(true);
					_this.SaveSettings();
				}}
			}),
			new BX.PopupWindowButtonLink({
				text: EC_MESS.Close,
				className: "popup-window-button-link-cancel",
				events: {click : function(){_this.CloseSetDialog(true);}}
			})
		],
		content: BX('bxec_uset_' + this.id)
	});

	BX.addCustomEvent(D, 'onPopupClose', BX.proxy(this.CloseSetDialog, this));

	D.CAL = {
		inPersonal : this.type == 'user' && this.ownerId == this.userId,
		DOM: {
			pTabs: BX(this.id + '_set_tabs'),
			ShowMuted: BX(this.id + '_show_muted')
		}
	};

	if (D.CAL.inPersonal)
	{
		D.CAL.DOM.SectSelect = BX(this.id + '_set_sect_sel');
		D.CAL.DOM.Blink = BX(this.id + '_uset_blink');
		D.CAL.DOM.ShowBanner = BX(this.id + '_show_banner');
		D.CAL.DOM.ShowDeclined = BX(this.id + '_show_declined');
	}


	if (this.PERM.access)
	{
		D.CAL.Access = new ECCalendarAccess({
			bind: 'calendar_type',
			GetAccessName: BX.proxy(this.GetAccessName, this),
			pCont: BX(this.id + 'type-access-values-cont'),
			pLink: BX(this.id + 'type-access-link')
		});

		D.CAL.DOM.WorkTimeStart = BX(this.id + 'work_time_start');
		D.CAL.DOM.WorkTimeEnd = BX(this.id + 'work_time_end');
		D.CAL.DOM.WeekHolidays = BX(this.id + 'week_holidays');
		D.CAL.DOM.YearHolidays = BX(this.id + 'year_holidays');
		D.CAL.DOM.YearWorkdays = BX(this.id + 'year_workdays');
		D.CAL.DOM.WeekStart = BX(this.id + 'week_start');
	}

	if (this.bSuperpose)
	{
		D.CAL.DOM.ManageSuperpose = BX(this.id + '-set-manage-sp');
		D.CAL.DOM.ManageSuperpose.onclick = function(){_this.ShowSuperposeDialog()};
	}

	D.CAL.DOM.ManageCalDav = BX(this.id + '_manage_caldav');
	if (D.CAL.DOM.ManageCalDav)
		D.CAL.DOM.ManageCalDav.onclick = function(){_this.ShowExternalDialog()};

	D.CAL.DOM.UsetClearAll = BX(this.id + '_uset_clear');
	if (D.CAL.DOM.UsetClearAll)
		D.CAL.DOM.UsetClearAll.onclick = function()
		{
			if (confirm(EC_MESS.ClearUserSetConf))
			{
				_this.CloseSetDialog(true);
				_this.ClearPersonalSettings();
			}
		};

	this.ChargePopupTabs(D, this.id + 'set-tab-');

	if (!this.PERM.access && D.CAL.DOM.pTabs)
		D.CAL.DOM.pTabs.style.display = 'none';

	this.oSetDialog = D;
}

JCEC.prototype.ShowSetDialog = function()
{
	if (!this.oSetDialog)
		this.CreateSetDialog();

	var D = this.oSetDialog;
	D.show();
	this.SetPopupTab(0, D); // Activate first tab

	// Set personal user settings

	if (D.CAL.inPersonal)
	{
		D.CAL.DOM.SectSelect.options.length = 0;
		var i, l = this.arSections.length, opt, el, sel = !this.userSettings.meetSection;
		D.CAL.DOM.SectSelect.options.add(new Option(' - ' + EC_MESS.FirstInList + ' - ', 0, sel, sel));
		for (i = 0; i < l; i++)
		{
			el = this.arSections[i];
			if (el.CAL_TYPE == 'user' && el.OWNER_ID == this.userId)
			{
				sel = this.userSettings.meetSection == el.ID;
				opt = new Option(el.NAME, el.ID, sel, sel);
				opt.style.backgroundColor = el.COLOR;
				D.CAL.DOM.SectSelect.options.add(opt);
			}
		}

		D.CAL.DOM.Blink.checked = !!this.userSettings.blink;
		D.CAL.DOM.ShowBanner.checked = !!this.userSettings.showBanner;
		D.CAL.DOM.ShowDeclined.checked = !!this.userSettings.showDeclined;
	}
	D.CAL.DOM.ShowMuted.checked = !!this.userSettings.showMuted;

	if (this.PERM.access)
	{
		// Set access for calendar type
		D.CAL.Access.SetSelected(this.typeAccess);
		D.CAL.DOM.WorkTimeStart.value = this.settings.work_time_start;
		D.CAL.DOM.WorkTimeEnd.value = this.settings.work_time_end;
		for(var i = 0, l = D.CAL.DOM.WeekHolidays.options.length; i < l; i++)
			D.CAL.DOM.WeekHolidays.options[i].selected = BX.util.in_array(D.CAL.DOM.WeekHolidays.options[i].value, this.settings.week_holidays);
		D.CAL.DOM.YearHolidays.value = this.settings.year_holidays;
		D.CAL.DOM.YearWorkdays.value = this.settings.year_workdays;
		D.CAL.DOM.WeekStart.value = this.settings.week_start;
	}
};

JCEC.prototype.CloseSetDialog  = function(bClosePopup)
{
	if (bClosePopup === true)
		this.oSetDialog.close();
};

// # # #  #  #  # External Calendars Dialog # # #  #  #  #
JCEC.prototype.CreateExternalDialog = function()
{
	var _this = this;
	var D = new BX.PopupWindow("BXCExternalDialog", null, {
		autoHide: false,
		closeByEsc : true,
		zIndex: 0,
		offsetLeft: 0,
		offsetTop: 0,
		draggable: true,
		titleBar: {content: BX.create("span", {html: EC_MESS.CalDavDialogTitle})},
		closeIcon: {right : "12px", top : "10px"},
		className: "bxc-popup-window",
		buttons: [
			new BX.PopupWindowButton({
				text: EC_MESS.AddCalDav,
				className: "bxec-popup-link-icon bxec-popup-add-ex",
				events: {click : function(){
					var i = D.CAL.arConnections.length;
					D.CAL.arConnections.push({bNew: true, name: EC_MESS.NewExCalendar, link: '', user_name: ''});
					_this.ExD_DisplayConnection(D.CAL.arConnections[i], i);
					_this.ExD_EditConnection(i);
				}}
			}),
			new BX.PopupWindowButton({
				text: EC_MESS.Save,
				className: "popup-window-button-accept",
				events: {click : function(){
					if (D.CAL.bLockClosing)
						return alert(EC_MESS.CalDavConWait);

					if (D.CAL.curEditedConInd !== false && D.CAL.arConnections[D.CAL.curEditedConInd])
						_this.ExD_SaveConnectionData(D.CAL.curEditedConInd);

					_this.arConnections = D.CAL.arConnections;
					D.CAL.bLockClosing = true;

					_this.SaveConnections(
						function(res)
						{
							D.CAL.bLockClosing = false;
							if (res)
							{
								_this.CloseExternalDialog(true);
								window.location = window.location;
							}
						},
						function(){D.CAL.bLockClosing = false;}
					);
				}}
			}),
			new BX.PopupWindowButtonLink({
				text: EC_MESS.Close,
				className: "popup-window-button-link-cancel",
				events: {click : function(){_this.CloseExternalDialog(true);}}
			})
		],
		content: BX('bxec_cdav_' + this.id)
	});

	BX.addCustomEvent(D, 'onPopupClose', BX.proxy(this.CloseExternalDialog, this));

	D.CAL = {
		DOM: {
			List: BX(this.id + '_bxec_dav_list'),
			EditConDiv: BX(this.id + '_bxec_dav_new'),
			EditName: BX(this.id + '_bxec_dav_name'),
			EditLink: BX(this.id + '_bxec_dav_link'),
			UserName: BX(this.id + '_bxec_dav_username'),
			Pass: BX(this.id + '_bxec_dav_password')
		}
	};

	this.oExternalDialog = D;
}

JCEC.prototype.ShowExternalDialog = function()
{
	if (!this.oExternalDialog)
		this.CreateExternalDialog();

	var D = this.oExternalDialog, i;
	D.show();
	D.CAL.curEditedConInd = false;

	BX.cleanNode(D.CAL.DOM.List);
	D.CAL.arConnections = BX.clone(this.arConnections);
	for (i = 0; i < this.arConnections.length; i++)
		this.ExD_DisplayConnection(D.CAL.arConnections[i], i);

	if (this.arConnections.length == 0) // No connections - open form to add new connection
	{
		i = D.CAL.arConnections.length;
		D.CAL.arConnections.push({bNew: true, name: EC_MESS.NewExCalendar, link: '', user_name: ''});
		this.ExD_DisplayConnection(D.CAL.arConnections[i], i);
		this.ExD_EditConnection(i);
	}
	else if (this.arConnections.length == 1)
	{
		this.ExD_EditConnection(0);
	}
};

JCEC.prototype.CloseExternalDialog = function(bClosePopup)
{
	if (bClosePopup === true)
		this.oExternalDialog.close();
};

JCEC.prototype.ExD_EditConnection = function(ind)
{
	var
		D = this.oExternalDialog,
		con = D.CAL.arConnections[ind];

	for(var _ind in D.CAL.arConnections)
	{
		if (D.CAL.arConnections[_ind] && _ind != ind && BX.hasClass(D.CAL.arConnections[_ind].pConDiv, "bxec-dav-item-edited"))
		{
			if (D.CAL.DOM.EditConDiv.parentNode == D.CAL.arConnections[_ind].pConDiv)
				this.ExD_SaveConnectionData(_ind);
			BX.removeClass(D.CAL.arConnections[_ind].pConDiv, "bxec-dav-item-edited");
		}
	}

	if (con.del || D.CAL.curEditedConInd === ind)
		return;

	if (D.CAL.curEditedConInd !== false && D.CAL.arConnections[D.CAL.curEditedConInd])
	{
		this.ExD_SaveConnectionData(D.CAL.curEditedConInd);
		BX.removeClass(D.CAL.arConnections[D.CAL.curEditedConInd].pConDiv, "bxec-dav-item-edited");
	}

	D.CAL.curEditedConInd = ind;

	D.CAL.DOM.EditName.value = con.name;
	D.CAL.DOM.EditLink.value = con.link;
	D.CAL.DOM.UserName.value = con.user_name;

	if (con.id > 0)
		this.ExD_CheckPass();
	else
		D.CAL.DOM.Pass.value = '';

	setTimeout(function(){BX.focus(D.CAL.DOM.EditLink);}, 100);

	D.CAL.DOM.EditName.onkeyup = D.CAL.DOM.EditName.onfocus = D.CAL.DOM.EditName.onblur = function()
	{
		if (D.CAL.changeNameTimeout)
			clearTimeout(D.CAL.changeNameTimeout);

		D.CAL.changeNameTimeout = setTimeout(function(){
			if (D.CAL.curEditedConInd !== false && D.CAL.arConnections[D.CAL.curEditedConInd])
			{
				var val = D.CAL.DOM.EditName.value;
				if (val.length > 25)
					val = val.substr(0, 23) + "...";
				D.CAL.arConnections[D.CAL.curEditedConInd].pText.innerHTML = BX.util.htmlspecialchars(val);
				D.CAL.arConnections[D.CAL.curEditedConInd].pText.title = D.CAL.DOM.EditName.value;
			}
		}, 50);
	};

	con.pConDiv.appendChild(D.CAL.DOM.EditConDiv);
	BX.addClass(con.pConDiv, "bxec-dav-item-edited");
};

JCEC.prototype.ExD_DisplayConnection = function(con, ind)
{
	var
		_this = this,
		D = this.oExternalDialog,
		pConDiv = D.CAL.DOM.List.appendChild(BX.create("DIV", {props: {id: 'bxec_dav_con_' + ind, className: 'bxec-dav-item' + (ind % 2 == 0 ? '' : ' bxec-dav-item-1')}})),
		pTitle = pConDiv.appendChild(BX.create("DIV", {props: {className: 'bxec-dav-item-name'}})),
		pStatus = pTitle.appendChild(BX.create("IMG", {props: {src: "/bitrix/images/1.gif", className: 'bxec-dav-item-status'}})),
		pText = pTitle.appendChild(BX.create("SPAN", {text: con.name})),
		pCount = pTitle.appendChild(BX.create("SPAN", {text: ''})),
		pEdit = pTitle.appendChild(BX.create("A", {props: {href: 'javascript: void(0);', className: 'bxec-dav-edit'}, text: EC_MESS.CalDavEdit})),
		pCol = pTitle.appendChild(BX.create("A", {props: {href: 'javascript: void(0);', className: 'bxec-dav-col'}, text: EC_MESS.CalDavCollapse})),
		pDel = pTitle.appendChild(BX.create("A", {props: {href: 'javascript: void(0);', className: 'bxec-dav-del'}, text: EC_MESS.CalDavDel})),
		pRest = pTitle.appendChild(BX.create("A", {props: {href: 'javascript: void(0);', className: 'bxec-dav-rest'}, text: EC_MESS.CalDavRestore})),
		pDelCalendars = pTitle.appendChild(BX.create("DIV", {props: {className: 'bxec-dav-del-cal'}, children: [BX.create("LABEL", {props: {htmlFor: 'bxec_dav_con_del_cal_' + ind}, text: EC_MESS.DelConCalendars})]})),
		pDelCalCh = pDelCalendars.appendChild(BX.create("INPUT", {props: {type: 'checkbox', id: 'bxec_dav_con_del_cal_' + ind, checked: true}}));

	if (con.id > 0)
	{
		var cn = 'bxec-dav-item-status', title;
		if (con.last_result.indexOf("[200]") >= 0)
		{
			cn += ' bxec-dav-ok';
			title = EC_MESS.SyncOk + '. ' + EC_MESS.SyncDate + ': ' + con.sync_date;
		}
		else
		{
			cn += ' bxec-dav-error';
			title = EC_MESS.SyncError + ': ' + con.last_result + '. '+ EC_MESS.SyncDate + ': ' + con.sync_date;
		}
		pStatus.className = cn;
		pStatus.title = title;

		var i, l = this.arSections.length, count = 0;
		for (i = 0; i < l; i++)
			if (this.arSections[i] && this.arSections[i].CAL_DAV_CON == con.id)
				count++;

		pCount.innerHTML = " (" + count + ")";
	}

	pConDiv.onmouseover = function(){BX.addClass(this, "bxec-dav-item-over");};
	pConDiv.onmouseout = function(){BX.removeClass(this, "bxec-dav-item-over");};

	pConDiv.onclick = function()
	{
		ind = parseInt(this.id.substr('bxec_dav_con_'.length));
		_this.ExD_EditConnection(ind);
	};

	pCol.onclick = function(e)
	{
		var ind = parseInt(this.parentNode.parentNode.id.substr('bxec_dav_con_'.length));
		if (D.CAL.arConnections[ind])
		{
			_this.ExD_SaveConnectionData(ind);
			BX.removeClass(D.CAL.arConnections[ind].pConDiv, "bxec-dav-item-edited");
			_this.oExternalDialog.curEditedConInd = false;
		}
		return BX.PreventDefault(e);
	};

	pDel.onclick = function(e)
	{
		var ind = parseInt(this.parentNode.parentNode.id.substr('bxec_dav_con_'.length));
		if (D.CAL.arConnections[ind])
		{
			D.CAL.arConnections[ind].del = true;
			BX.removeClass(D.CAL.arConnections[ind].pConDiv, "bxec-dav-item-edited");
			BX.addClass(D.CAL.arConnections[ind].pConDiv, "bxec-dav-item-deleted");
			_this.ExD_SaveConnectionData(ind);
			_this.oExternalDialog.curEditedConInd = false;
		}

		return BX.PreventDefault(e);
	};

	pRest.onclick = function(e)
	{
		var ind = parseInt(this.parentNode.parentNode.id.substr('bxec_dav_con_'.length));
		if (D.CAL.arConnections[ind])
		{
			D.CAL.arConnections[ind].del = false;
			BX.removeClass(D.CAL.arConnections[ind].pConDiv, "bxec-dav-item-deleted");
		}
		return BX.PreventDefault(e);
	};

	pEdit.onclick = function(e)
	{
		var ind = parseInt(this.parentNode.parentNode.id.substr('bxec_dav_con_'.length));
		if (D.CAL.arConnections[ind])
		{
			for(var _ind in D.CAL.arConnections)
			{
				if (D.CAL.arConnections[_ind] && _ind != ind && BX.hasClass(D.CAL.arConnections[_ind].pConDiv, "bxec-dav-item-edited"))
				{
					if (D.CAL.DOM.EditConDiv.parentNode == D.CAL.arConnections[_ind].pConDiv)
						_this.ExD_SaveConnectionData(_ind);
					BX.removeClass(D.CAL.arConnections[_ind].pConDiv, "bxec-dav-item-edited");
				}
			}

			var con = D.CAL.arConnections[ind];
			BX.addClass(con.pConDiv, "bxec-dav-item-edited");
			con.pConDiv.appendChild(D.CAL.DOM.EditConDiv);
			_this.oExternalDialog.curEditedConInd = true;

			D.CAL.DOM.EditName.value = con.name;
			D.CAL.DOM.EditLink.value = con.link;
			D.CAL.DOM.UserName.value = _this.CheckGmailLogin(con.user_name, con.link);

			if (con.id > 0)
				_this.ExD_CheckPass();
			else
				D.CAL.DOM.Pass.value = '';

		}
		return BX.PreventDefault(e);
	};

	con.pConDiv = pConDiv;
	con.pText = pText;
	con.pDelCalendars = pDelCalCh;
}

JCEC.prototype.ExD_SaveConnectionData = function(ind)
{
	var
		D = this.oExternalDialog,
		con = D.CAL.arConnections[ind];

	con.name = D.CAL.DOM.EditName.value;
	con.link = D.CAL.DOM.EditLink.value;
	con.user_name = this.CheckGmailLogin(D.CAL.DOM.UserName.value, con.link);
	con.pass = 'bxec_not_modify_pass';

	if (D.CAL.DOM.Pass.type.toLowerCase() == 'password' && D.CAL.DOM.Pass.title != EC_MESS.CalDavNoChange)
		con.pass = D.CAL.DOM.Pass.value;
};

JCEC.prototype.ExD_CheckPass = function()
{
	var D = this.oExternalDialog;

	if (!BX.browser.IsIE())
	{
		D.CAL.DOM.Pass.type = 'text';
		D.CAL.DOM.Pass.value = EC_MESS.CalDavNoChange;
	}
	else
	{
		D.CAL.DOM.Pass.value = '';
	}

	D.CAL.DOM.Pass.title = EC_MESS.CalDavNoChange;
	D.CAL.DOM.Pass.className = 'bxec-dav-no-change';
	D.CAL.DOM.Pass.onfocus = D.CAL.DOM.Pass.onmousedown = function()
	{
		if (!BX.browser.IsIE())
			this.type = 'password';
		this.value = '';
		this.title = '';
		this.className = '';
		this.onfocus = this.onmousedown = null;
		BX.focus(this);
	};
};

JCEC.prototype.CheckGmailLogin = function(name, link)
{
	if (
		(name && name.toLowerCase().indexOf('gmail.com') !== -1
			||
		link && link.toLowerCase().indexOf('google') !== -1
		)
		&& name.indexOf('@')
	)
	{
		var arN = name.split('@');
		name = arN[0].replace(/\./ig, '') + '@' + arN[1];
	}

	return name;
};

// # # #  #  #  # Mobile help Dialog # # #  #  #  #
JCEC.prototype.CreateMobileHelpDialog = function()
{
	var _this = this;
	var D = new BX.PopupWindow("BXCMobileHelp", null, {
		autoHide: false,
		closeByEsc : true,
		zIndex: 0,
		offsetLeft: 0,
		offsetTop: 0,
		draggable: true,
		titleBar: {content: BX.create("span", {html: EC_MESS.MobileHelpTitle})},
		closeIcon: {right : "12px", top : "10px"},
		className: "bxc-popup-window",
		buttons: [
			new BX.PopupWindowButton({
				text: EC_MESS.Close,
				className: "popup-window-button-accept",
				events: {click : function(){_this.CloseMobileHelpDialog(true);}}
			})
		],
		content: BX('bxec_mobile_' + this.id)
	});

	BX.addCustomEvent(D, 'onPopupClose', BX.proxy(this.CloseMobileHelpDialog, this));

	D.CAL = {
		DOM: {
			iPhoneLink: BX('bxec_mob_link_iphone_' + this.id),
			macLink: BX('bxec_mob_link_mac_' + this.id),
			birdLink: BX('bxec_mob_link_bird_' + this.id),
			iPhoneAllCont: BX('bxec_mobile_iphone_all' + this.id),
			iPhoneOneCont: BX('bxec_mobile_iphone_one' + this.id),
			macCont: BX('bxec_mobile_mac_cont' + this.id),
			birdAllCont: BX('bxec_mobile_sunbird_all' + this.id),
			birdOneCont: BX('bxec_mobile_sunbird_one' + this.id)
		}
	};

	D.CAL.DOM.iPhoneLink.onclick = function()
	{
		if (D.CAL.calendarId == 'all')
		{
			if (D.CAL.biPhoneAllOpened)
			{
				D.CAL.DOM.iPhoneAllCont.style.display = 'none';
				BX.addClass(this, 'bxec-link-hidden');
			}
			else
			{
				D.CAL.DOM.iPhoneAllCont.style.display = 'block';
				BX.removeClass(this, 'bxec-link-hidden');
			}
			D.CAL.biPhoneAllOpened = !D.CAL.biPhoneAllOpened;
		}
		else
		{
			if (D.CAL.biPhoneOneOpened)
			{
				D.CAL.DOM.iPhoneOneCont.style.display = 'none';
				BX.addClass(this, 'bxec-link-hidden');
			}
			else
			{
				D.CAL.DOM.iPhoneOneCont.style.display = 'block';
				BX.removeClass(this, 'bxec-link-hidden');
			}
			D.CAL.biPhoneOneOpened = !D.CAL.biPhoneOneOpened;
		}
	};

	D.CAL.DOM.macLink.onclick = function()
	{
		if (D.CAL.bMacOpened)
		{
			D.CAL.DOM.macCont.style.display = 'none';
			BX.addClass(this, 'bxec-link-hidden');
		}
		else
		{
			D.CAL.DOM.macCont.style.display = 'block';
			BX.removeClass(this, 'bxec-link-hidden');
		}
		D.CAL.bMacOpened = !D.CAL.bMacOpened;
	};

	D.CAL.DOM.birdLink.onclick = function()
	{
		if (D.CAL.calendarId == 'all')
		{
			if (D.CAL.bbirdAllOpened)
			{
				D.CAL.DOM.birdAllCont.style.display = 'none';
				BX.addClass(this, 'bxec-link-hidden');
			}
			else
			{
				D.CAL.DOM.birdAllCont.style.display = 'block';
				BX.removeClass(this, 'bxec-link-hidden');
			}
			D.CAL.bbirdAllOpened = !D.CAL.bbirdAllOpened;
		}
		else
		{
			if (D.CAL.bbirdOneOpened)
			{
				D.CAL.DOM.birdOneCont.style.display = 'none';
				BX.addClass(this, 'bxec-link-hidden');
			}
			else
			{
				D.CAL.DOM.birdOneCont.style.display = 'block';
				BX.removeClass(this, 'bxec-link-hidden');
			}
			D.CAL.bbirdOneOpened = !D.CAL.bbirdOneOpened;
		}
	};

	this.oMobileDialog = D;
}

JCEC.prototype.ShowMobileHelpDialog = function(calendarId)
{
	if (!this.oMobileDialog)
		this.CreateMobileHelpDialog();

	var D = this.oMobileDialog;
	D.show();

	D.CAL.calendarId = calendarId;
	D.CAL.DOM.iPhoneAllCont.style.display = "none";
	D.CAL.DOM.iPhoneOneCont.style.display = "none";
	D.CAL.DOM.birdAllCont.style.display = "none";
	D.CAL.DOM.birdOneCont.style.display = "none";
	D.CAL.DOM.macCont.style.display = "none";

	BX.addClass(D.CAL.DOM.birdLink, 'bxec-link-hidden');
	BX.addClass(D.CAL.DOM.iPhoneLink, 'bxec-link-hidden');
	BX.addClass(D.CAL.DOM.macLink, 'bxec-link-hidden');

	var arLinks = [], i;
	if (calendarId == 'all')
	{
		arLinks = arLinks.concat(BX.findChildren(D.CAL.DOM.iPhoneAllCont, {tagName: 'SPAN', className: 'bxec-link'}, true));
		arLinks = arLinks.concat(BX.findChildren(D.CAL.DOM.birdAllCont, {tagName: 'SPAN', className: 'bxec-link'}, true));

		for (i = 0; i < arLinks.length; i++)
			if (arLinks[i] && arLinks[i].nodeName)
				arLinks[i].innerHTML = this.arConfig.caldav_link_all;
	}
	else
	{
		arLinks = arLinks.concat(BX.findChildren(D.CAL.DOM.iPhoneOneCont, {tagName: 'SPAN', className: 'bxec-link'}, true));
		arLinks = arLinks.concat(BX.findChildren(D.CAL.DOM.birdOneCont, {tagName: 'SPAN', className: 'bxec-link'}, true));

		for (i = 0; i < arLinks.length; i++)
			if (arLinks[i] && arLinks[i].nodeName)
				arLinks[i].innerHTML = BX.util.htmlspecialchars(this.arConfig.caldav_link_one.replace('#CALENDAR_ID#', calendarId));
	}

	arLinks = BX.findChildren(D.CAL.DOM.macCont, {tagName: 'SPAN', className: 'bxec-link'}, true);
	for (i = 0; i < arLinks.length; i++)
		if (arLinks[i] && arLinks[i].nodeName)
			arLinks[i].innerHTML = this.arConfig.caldav_link_all;
};

JCEC.prototype.CloseMobileHelpDialog = function(bClosePopup)
{
	if (bClosePopup === true)
		this.oMobileDialog.close();
};

JCEC.prototype.ChargePopupTabs = function(oPopup, idPrefix)
{
	if (!oPopup || !oPopup.CAL || !oPopup.CAL.DOM || !oPopup.CAL.DOM.pTabs)
		return;

	// Set tabs
	oPopup.CAL.Tabs = [];
	var tab, oTab, _this = this;
	for (var i in oPopup.CAL.DOM.pTabs.childNodes)
	{
		tab = oPopup.CAL.DOM.pTabs.childNodes[i];
		if (tab.nodeType == '1' && tab.className  && tab.className.indexOf('bxec-d-tab') != -1)
		{
			oPopup.CAL.Tabs.push(
			{
				tab: tab,
				cont: BX(tab.id + '-cont'),
				showed: tab.style.display != 'none'
			});
			tab.onclick = function(){_this.SetPopupTab(parseInt(this.id.substr(idPrefix.length)), oPopup)};
		}
	}
}

JCEC.prototype.ShowPopupTab = function(Tab, bShow)
{
	Tab.tab.style.display = bShow ? '' : 'none';
	Tab.cont.style.display = bShow ? '' : 'none';
	Tab.showed = !!bShow;
}

JCEC.prototype.SetPopupTab = function(curInd, oPopup)
{
	var
		i, Tab, Tabs = oPopup.CAL.Tabs;

	if (Tabs && oPopup.CAL.activeTab != curInd && !Tabs[curInd].bDisabled)
	{
		for (i in Tabs)
		{
			Tab = Tabs[i];
			if (!Tab || !Tab.cont)
				continue;

			if (i == curInd)
			{
				Tab.cont.style.display = 'block';
				BX.addClass(Tab.tab, 'bxec-d-tab-act');
			}
			else
			{
				Tab.cont.style.display = 'none';
				BX.removeClass(Tab.tab, 'bxec-d-tab-act');
			}
		}
		oPopup.CAL.activeTab = curInd;
	}
}

JCEC.prototype.InitColorDialogControl = function(key, OnSetValues)
{
	var
		_this = this,
		id = this.id + '-' + key,
		colorCont = BX(id + '-color-cont'),
		pColor = BX(id + '-color-inp'),
		pTextColor = BX(id + '-text-color-inp');

	function SetColors(color, text_color, check)
	{
		if (!text_color || (check && (text_color == '#FFFFFF' || text_color == '#000000')))
			text_color = _this.ColorIsDark(color) ? '#FFFFFF' : '#000000';

		try
		{
			pColor.value = color;
			pColor.style.backgroundColor = color;
			pColor.style.color = text_color;
		}
		catch(e)
		{
			color = this.arConfig.arCalColors[0];
			pColor.style.color = '#000000';
		}

		if (OnSetValues && typeof OnSetValues == 'function')
			OnSetValues(color, text_color);
	}

	colorCont.onclick = function(e)
	{
		if (!e)
			e = window.event;
		var targ = e.target || e.srcElement;
		if (targ && targ.nodeName && targ.nodeName.toLowerCase() == 'a')
		{
			var ind = parseInt(targ.id.substr((id + '-color-').length), 10);
			if (_this.arConfig.arCalColors[ind])
				SetColors(_this.arConfig.arCalColors[ind]);
		}
	};
	pColor.onblur = pColor.onkeyup = function(){SetColors(this.value);};
	pColor.onclick = function(){_this.ColorPicker.Open(
		{
			pWnd: this,
			key: key,
			id: id + '-bg',
			onSelect: function(value){SetColors(value, pColor.style.color, true);}
		});
	};

	pTextColor.onclick = function(){_this.ColorPicker.Open(
		{
			pWnd: this,
			key: key,
			id: id + '-text',
			onSelect: function(value){SetColors(pColor.value, value, false);}
		});
	};

	return {Set: SetColors}
}