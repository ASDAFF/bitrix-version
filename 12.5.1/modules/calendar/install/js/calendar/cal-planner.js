// # # #  #  #  # Planner for Event Calendar  # # #  #  #  #
(function(window) {
window.ECPlanner = function(Params)
{
	window._bx_plann_events = {};
	window._bx_plann_mr = {};

	this.id = Params.id;

	this.bOpened = false;
	this.bMRShowed = false;
	this.bFreezed = true;

	this.accData = {};
	this.accDataMR = {};
	this.accIndex = {};
	this.bAMPM = Params.bAMPM;

	this.minWidth = Params.minWidth || 800;
	this.minHeight = Params.minHeight || 300;
	this.cellWidth = 80;

	this.workTime = Params.workTime || [];
	this.config = Params.config || {};
	this.settings = Params.settings || {};
	this.meetingRooms = Params.meetingRooms || false;
	this.actionUrl = Params.actionUrl || '';

	this.scale = parseInt(this.settings.scale) || 1; // 0 - 30 min;   1 - 1 hour; 2 - 2 hour; 3 - 1day
	this.width = parseInt(this.settings.width) || 700;
	this.height = parseInt(this.settings.height) || 500;

	if (this.width < this.minWidth)
		this.width = this.minWidth;
	if (this.height < this.minHeight)
		this.height = this.minHeight;

	this.bOnlyWorkTime = true;
	this.preFetch = {back: 8, forward: 26};

	this.bAddGroupMembers = !!Params.bAddGroupMembers;
	if (this.bAddGroupMembers)
	{
		var _this = this;
		this._AddGroupMembers = Params.AddGroupMembers;
		BX.addCustomEvent(window, "onGetGroupMembers", function(users)
		{
			var k, values = [];
			for(k in this.Attendees)
				values.push(this.Attendees[k].User);
			for(k in users)
				values.push({id: users[k].id, name: users[k].name});
			_this.SetValues(values);
		});
	}

	if (this.bOnlyWorkTime)
	{
		var
			arTF = this.workTime[0].split('.'),
			arTT = this.workTime[1].split('.');
		this.oTime = {from: {h: bxIntEx(arTF[0]), m: bxIntEx(arTF[1])}, to: {h: bxIntEx(arTT[0]), m: bxIntEx(arTT[1])}};
		this.oTime.count = this.oTime.to.h - this.oTime.from.h;
	}
	else
	{
		this.oTime = {from: {h: 0, m: 0}, to: {h: 24, m: 0}, count: 24};
	}
};

ECPlanner.prototype = {
Freeze: function(bFreeze)
{
	this.bFreezed = bFreeze;
	if (bFreeze)
		BX.addClass(this.pCont, 'bxecpl-empty');
	else
		BX.removeClass(this.pCont, 'bxecpl-empty');

	if (BX.browser.IsIE()) // Fix IE Bug
	{
		var _this = this;
		setTimeout(function(){_this.BuildGridTitle();}, 1000);
	}
},

OpenDialog: function(Params)
{
	var _this = this;

	this.curEventId = Params.curEventId || false;
	this.oldLocationMRId = Params.oldLocationMRId || false;
	this.initDate = Params.fromDate ? BX.parseDate(Params.fromDate) : new Date();
	this.accIndex = {};

	this.SetCurrentDate(this.initDate);

	if (!this.pWnd)
		this.CreateDialog();

	this.pWnd.show();

	if (BX.browser.IsIE())
		setTimeout(function(){_this.BuildGridTitle();}, 1000);
	else
		this.BuildGridTitle();

	this.ClearUserList(false);

	// Set From - To
	this.pFrom.value = Params.fromDate || '';
	this.pTo.value = Params.toDate || '';
	this.pFromTime.value = Params.fromTime || '';
	this.pToTime.value = Params.toTime || '';

	setTimeout(BX.proxy(this.FieldDatesOnChange, this), 100);

	// Set location;
	if(parseInt(Params.locationMrind) != Params.locationMrind)
		Params.locationMrind = false;
	this.Location.Set(Params.locationMrind, Params.location || '');

	// Set attendees
	if (Params.attendees)
		this.SetValues(Params.attendees);

	this.DisplayDiagram(false, true);

	setTimeout(function()
	{
		_this.Resize(_this.width, _this.height);
		_this.oSel.Adjust();
	}, 100);

	this.bOpened = true;
},

CreateDialog: function()
{
	var _this = this;
	this.pWnd = new BX.PopupWindow("BXCPlanner", null, {
		autoHide: false,
		zIndex: 0,
		offsetLeft: 0,
		offsetTop: 0,
		draggable: true,
		closeByEsc : true,
		titleBar: {content: BX.create("span", {html: BXPL_MESS.Planner})},
		closeIcon: { right : "12px", top : "10px"},
		className: "bxc-popup-window",
		buttons: [
			new BX.PopupWindowButton({
				text: BXPL_MESS.Next,
				className: "popup-window-button-accept",
				events: {click : function(){
					_this.Submit();
					_this.Close(true);
				}}
			}),
			new BX.PopupWindowButtonLink({
				id: this.id + 'bcpl-cancel',
				text: BXPL_MESS.Close,
				className: "popup-window-button-link-cancel",
				events: {
					click : function(){_this.Close(true);}
				}
			}),
			BX.create("DIV")
		],
		content: BX('bx-planner-popup' + this.id),
		events: {}
	});
	BX.addCustomEvent(this.pWnd, 'onPopupClose', BX.proxy(this.Close, this));

	this.BuildCore();
	this.pDuration = new ECPlDuration(this);

	this.Location = new BXInputPopup({
		id: this.id + 'loc_2',
		values: this.meetingRooms,
		input: BX(this.id + '_planner_location2'),
		defaultValue: BXPL_MESS.SelectMR,
		openTitle: BXPL_MESS.OpenMRPage
	});
	BX.addCustomEvent(this.Location, 'onInputPopupChanged', BX.proxy(this.LocationOnChange, this));

	this.pResizer = this.pWnd.buttonsContainer.appendChild(BX.create("DIV", {props: {className: 'bxec-plan-resizer'}, events: {mousedown: BX.proxy(this.ResizerMouseDown, this), drag: BX.False}}));

	this.pPopupCont = this.pWnd.popupContainer;
},

Close: function(bClosePopup)
{
	if (bClosePopup === true)
		this.pWnd.close();
},

CloseDialog: function()
{
	this.bOpened = false;
},

BuildCore: function()
{
	var
		id = this.id,
		_this = this;

	this.pCont = BX(id + '_plan_cont');
	this.pGridCont = BX(id + '_plan_grid_cont');
	this.pGridTbl = this.pGridCont.firstChild;

	if (this.bAMPM)
		BX.addClass(this.pCont, 'bxec-plan-cont-ampm');

	this.pUserListCont = this.pGridTbl.rows[2].cells[0];
	this.pGridTitleCont = this.pGridTbl.rows[0].cells[2];
	this.pGridCellCont = this.pGridTbl.rows[2].cells[2];

	this.pUserListDiv = this.pUserListCont.firstChild;
	this.pGridTitleDiv = this.pGridTitleCont.firstChild;
	this.pGridDiv = this.pGridCellCont.firstChild;
	this.pGAccCont = this.pGridDiv.firstChild;

	this.pUserListTable = this.pUserListDiv.appendChild(BX.create("TABLE", {props: {className: 'bxec-user-list'}}));
	this.pGridTitleTable = this.pGridTitleDiv.appendChild(BX.create("TABLE", {props: {className: 'bxec-grid-cont-tbl'}}));
	this.pGridTable = this.pGridDiv.appendChild(BX.create("TABLE", {props: {className: 'bxec-grid-bg-tbl'}}));

	if (BX.browser.IsIE())
		BX.addClass(this.pGridTitleTable, BX.browser.IsDoctype() ? 'bxec-iehack0': 'bxec-iehack');

	DenyDragEx(this.pGridTable);
	this.oSel = new ECPlSelection(this);

	var scrollTmt;
	this.pGridDiv.onscroll = function()
	{
		_this.pGridTitleTable.style.left = '-' + parseInt(this.scrollLeft) + 'px'; // Synchronized scrolling with title
		_this.pUserListTable.style.top = '-' + parseInt(this.scrollTop) + 'px'; // Synchronized scrolling with userlist

		if (_this.oSel._bScrollMouseDown && BX.browser.IsIE())
		{
			if (scrollTmt)
				clearTimeout(scrollTmt);

			scrollTmt = setTimeout(
				function()
				{
					var sl = parseInt(_this.pGridDiv.scrollLeft);
					if (!_this.oSel || sl != _this.oSel._gridScrollLeft)
						_this.GridSetScrollLeft(_this.CheckScrollLeft(sl));
					_this.oSel._bGridMouseDown = false;
					_this.oSel._bScrollMouseDown = false;
				}, 1000
			);
		}
	};

	// Add users block
	this.InitUserControll();

	this.pScale = BX(id + '_plan_scale_sel');
	this.pScale.value = this.scale;
	this.pScale.onchange = function(e)
	{
		if (_this.bFreezed)
		{
			this.value = _this.scale;
			return BX.PreventDefault(e);
		}
		_this.ChangeScale(this.value);
	};

	// From / To Limits
	this.pFrom = BX(this.id + 'planner-from');
	this.pTo = BX(this.id + 'planner-to');
	this.pFromTime = BX(this.id + 'planner_from_time');
	this.pToTime = BX(this.id + 'planner_to_time');

	this.pFrom.onchange = this.pFromTime.onchange = function(){_this.FieldDatesOnChange(true, true);};
	this.pTo.onchange = this.pToTime.onchange = function(){_this.FieldDatesOnChange(true);};

	//var ts = new Date().getTime() / 1000 ^ 0;
	this.pFrom.onclick = function(){BX.calendar({node: this.parentNode, field: this, bTime: false});};
	this.pTo.onclick = function(){BX.calendar({node: this.parentNode, field: this, bTime: false});};
	this.pFromTime.onclick = window['bxShowClock_' + this.id + 'planner_from_time'];
	this.pToTime.onclick = window['bxShowClock_' + this.id + 'planner_to_time'];
},

Submit: function()
{
	var Params = {
		fromDate: this.pFrom.value,
		toDate: this.pTo.value,
		fromTime: this.pFromTime.value,
		toTime: this.pToTime.value,
		locInd: this.curLocationInd,
		locValue: this.curLocationValue,
		attendees: this.Attendees
	};

	BX.onCustomEvent(this, 'onSubmit', [Params]);
},

CheckSubmit: function()
{
	if (!_this.pFrom.value || !_this.pTo.value)
	{
		alert(BXPL_MESS.NoFromToErr);
		return false;
	}

	if (_this.Attendees.length == 0)
	{
		alert(BXPL_MESS.NoGuestsErr);
		return false;
	}

	return true;
},

ChangeScale: function(scale)
{
	this.scale = parseInt(scale, 10); // Set new scale

	// # CLEANING #
	while(this.pGridTitleTable.rows[0])
		this.pGridTitleTable.deleteRow(0);

	// # BUILDING #
	this.BuildGridTitle();
	this.BuildGrid(this.Attendees.length);

	this.GetTimelineLimits(true);

	this.DisplayDiagram(false, true);
	this.DisplayMRDiagram(false, true);

	if (this.oSel.pDiv)
	{
		this.oSel.Make({bFromTimeLimits: true, bSetTimeline: false});
		var _this = this;
		setTimeout(function(){_this.FieldDatesOnChange(true, true);}, 500);
	}

	BX.userOptions.save('calendar_planner', 'settings', 'scale', this.scale);
},

AddGroupMembers: function()
{
	if (this.bAddGroupMembers && this._AddGroupMembers && typeof this._AddGroupMembers == 'function')
		this._AddGroupMembers();
},

GetAccessibility: function(users)
{
	if (!users || !users.length)
		return;

	var
		_this = this,
		from, to,
		cd = this.currentDate,
		fromD = new Date(),
		toD = new Date();

	fromD.setFullYear(cd.Y, cd.M, cd.D - this.preFetch.back);
	toD.setFullYear(cd.Y, cd.M, cd.D + this.preFetch.forward);
	this.LoadedLimits = {
		from: fromD.getTime(),
		to: toD.getTime()
	};

	from = bxFormatDate(fromD.getDate(), fromD.getMonth() + 1, fromD.getFullYear());
	to = bxFormatDate(toD.getDate(), toD.getMonth() + 1, toD.getFullYear());

	this.Request({
		postData: this.GetReqData('get_accessibility',
			{
				users: users,
				from: from,
				to: to,
				cur_event_id: this.curEventId
			}
		),
		handler: function(oRes)
		{
			for (var id in oRes.data)
				if (typeof oRes.data[id] == 'object')
					_this.accData[id] = oRes.data[id];

			_this.DisplayDiagram(false, true);
			return true;
		}
	});
},

CheckAccessibility: function(bTimeout)
{
	if (bTimeout === true)
	{
		if (this._check_acc_timeout)
			this._check_acc_timeout = clearTimeout(this._check_acc_timeout);

		this._check_acc_timeout = setTimeout(BX.proxy(this.CheckAccessibility, this), 1500);
		return;
	}

	var users = [], i, uid;
	for (i = 0; i < this.Attendees.length; i++)
	{
		uid = this.Attendees[i].User.id;
		if (uid && !this.accIndex[uid])
		{
			users.push(uid);
			this.accIndex[uid] = true;
		}
	}
	this.GetAccessibility(users);
},

GetMRAccessibility: function(ind)
{
	var
		_this = this,
		mrid = this.Location.Get(ind),
		from, to,
		cd = this.currentDate,
		fromD = new Date(),
		toD = new Date();

	if (mrid === false)
		return;

	fromD.setFullYear(cd.Y, cd.M, cd.D - this.preFetch.back);
	toD.setFullYear(cd.Y, cd.M, cd.D + this.preFetch.forward);
	this.MRLoadedLimits = {from: fromD.getTime(), to: toD.getTime()};

	from = bxFormatDate(fromD.getDate(), fromD.getMonth() + 1, fromD.getFullYear());
	to = bxFormatDate(toD.getDate(), toD.getMonth() + 1, toD.getFullYear());

	this.Request({
		postData: this.GetReqData('get_mr_accessibility',
		{
			id: mrid,
			from: from,
			to: to,
			cur_event_id: this.oldLocationMRId
		}),
		handler: function(oRes)
		{
			if (typeof oRes.data == 'object')
				_this.accDataMR[mrid] = oRes.data;
			_this.DisplayMRDiagram(_this.accDataMR[mrid], true);
		}
	});
},

DisplayDiagram: function(data, bClean)
{
	var i;
	if (bClean)
	{
		var el;
		for (i = this.pGAccCont.childNodes.length; i > 0; i--)
		{
			el = this.pGAccCont.childNodes[i - 1];
			if (el.getAttribute('data-bx-plan-type') == 'user')
				this.pGAccCont.removeChild(el);
		}
	}

	if (!data)
		data = this.accData;

	this.arACC = [];
	var uid;
	for (i = 0; i < this.Attendees.length; i++)
	{
		uid = this.Attendees[i].User.key;
		if (data[uid])
			this.DisplayAccRow({ind: i, events: data[uid], uid: uid});
	}

	if (this.oSel)
		this.oSel.TimeoutCheck();
},

DisplayMRDiagram: function(arEvents, bClean)
{
	if (!this.bMRShowed)
		return;

	if (bClean) // Clean only MR diagram
		this.CleanMRDiagram();

	this.arMRACC = [];
	var mrid = this.Location.Get();
	if (!arEvents && mrid !== false && this.accDataMR[mrid])
		arEvents = this.accDataMR[mrid];

	if (!arEvents)
		arEvents = {};

	this.DisplayAccRow({events: arEvents, ind: this.Attendees.length + 2, bMR: true});
},

CleanMRDiagram: function()
{
	if (typeof this.arMRACC == 'object')
	{
		var i, el;
		for (i = this.pGAccCont.childNodes.length; i > 0; i--)
		{
			el = this.pGAccCont.childNodes[i - 1];
			if (el.getAttribute('data-bx-plan-type') == 'meeting_room')
				this.pGAccCont.removeChild(el);
		}
	}
	this.arMRACC = [];
},

DisplayDiagramEx: function()
{
	var tl = this.GetTimelineLimits();

	if (!this.LoadedLimits || !tl)
		return;

	if (tl.from.getTime() < this.LoadedLimits.from || tl.to.getTime() > this.LoadedLimits.to)
		this.GetAccessibility(this.AttendeesIds);
	else
		this.DisplayDiagram(false, true);

	if (this.bMRShowed && (tl.from.getTime() < this.MRLoadedLimits.from || tl.to.getTime() > this.MRLoadedLimits.to))
		this.GetMRAccessibility();
	else
		this.DisplayMRDiagram(false, true);
},

DisplayAccRow: function(Params)
{
	if (typeof Params.events != 'object')
		return false;

	var
		tlLimits = this.GetTimelineLimits(),
		limFrom = tlLimits.from.getTime(),
		limTo = tlLimits.to.getTime(),
		top = (Params.ind * 20 + 0) + 'px', // Get top
		event, df, dt, cn, title, rtf, rtt,
		from, to, rdf, rdt,
		from_ts, to_ts,
		dayLen = 86400000,
		dispTimeF = this.oTime.from.h + this.oTime.from.m / 60,
		dispTimeT = this.oTime.to.h + this.oTime.to.m / 60,
		dayCW = this.GetDayCellWidth(),
		width, left, right, i, l = Params.events.length;

	for (i = 0; i < l; i++)
	{
		event = Params.events[i];
		from_ts = from = BX.date.getBrowserTimestamp(event.FROM);
		to_ts = to = BX.date.getBrowserTimestamp(event.TO);
		rdf = rdt = false;

		if (to < limFrom || from > limTo)
			continue;

		if (from < limFrom)
		{
			from = limFrom;
			rdf = new Date(from_ts);
		}
		if (to > limTo)
		{
			to = limTo;
			rdt = new Date(to_ts);
		}

		df = new Date(from);
		dt = new Date(to);

		// 1. Days count from limitFrom
		left = dayCW * Math.floor((from - limFrom) / dayLen);
		var dfTime = df.getHours() + df.getMinutes() / 60;
		var time = dfTime - dispTimeF;
		if (time > 0)
			left += Math.round((dayCW * time) / this.oTime.count);

		if (event.FROM == event.TO) // One full day event
		{
			width = dayCW - 1;
		}
		else
		{
			right = dayCW * Math.floor((to - limFrom) / dayLen);
			if (this.CheckBTime(dt))
				right += dayCW;

			var dtTime = dt.getHours() + dt.getMinutes() / 60;
			if (dtTime > dispTimeT)
				dtTime = dispTimeT;
			var time2 = dtTime - dispTimeF;
			if (time2 > 0)
				right += Math.round((dayCW * time2) / this.oTime.count);

			width = (right - left) - 1;
		}

		// Display event
		if (width > 0)
		{
			cn = 'bxec-gacc-el';
			if (!Params.bMR && event.ACCESSIBILITY != 'busy')
				cn += ' bxec-gacc-' + event.ACCESSIBILITY;

			if (!rdf)
				rdf = df;
			if (!rdt)
				rdt = dt;

			// Make title:
			rtf = zeroInt(rdf.getHours()) + ':' + zeroInt(rdf.getMinutes());
			rtt = zeroInt(rdt.getHours()) + ':' + zeroInt(rdt.getMinutes());
			rtf = (rtf == '00:00') ? '' : ' ' + rtf;
			rtt = (rtt == '00:00') ? '' : ' ' + rtt;

			title = Params.bMR ? event.NAME + ";\n " : '';
			title += BX.util.trim(bxFormatDate(rdf.getDate(), rdf.getMonth() + 1, rdf.getFullYear()) + ' ' + this.FormatTime(rdf.getHours(), rdf.getMinutes(), true, true)) + ' - ' + BX.util.trim(bxFormatDate(rdt.getDate(), rdt.getMonth() + 1, rdt.getFullYear()) + ' ' + this.FormatTime(rdt.getHours(), rdt.getMinutes(), true, true));

			if (!Params.bMR)
			{
				if (event.ACCESSIBILITY)
					title += ";\n " + BXPL_MESS.UserAccessibility + ': '+ BXPL_MESS['Acc_' + event.ACCESSIBILITY].toLowerCase();
				if(event.IMPORTANCE)
					title += ";\n " + BXPL_MESS.Importance + ': ' + BXPL_MESS['Importance_' + event.IMPORTANCE].toLowerCase();
			}

			if (event.FROM_HR)
				title += ";\n (" + BXPL_MESS.FromHR + ")";

			var pDiv = this.pGAccCont.appendChild(BX.create("DIV", {props: {className: cn, title: title}, style: {top: top, left: left + 'px', width: width + 'px'}}));

			pDiv.setAttribute('data-bx-plan-type', Params.bMR ? 'meeting_room' : 'user');

			if (!rtf && !rtt)
				to += dayLen;

			if (Params.bMR)
				this.arMRACC.push({div: pDiv, from: from, to: to});
			else
				this.arACC.push({div: pDiv, from: from, to: to, uid: Params.uid, aac: event.ACCESSIBILITY});
		}
	}
},

BlinkDiagramDiv: function(div)
{
	var
		iter = 0,
		origClass = div.className,
		warnClass = "bxec-gacc-el bxec-gacc-warn";

	if (origClass != warnClass)
	{
		var blinkInterval = setInterval(
			function()
			{
				div.className = (div.className == warnClass) ? origClass : warnClass;
				if (++iter > 5)
					clearInterval(blinkInterval);
			},250
		);
	}
},

BuildGridTitle: function()
{
	if (this.pGridTitleTable.rows.length > 0)
		BX.cleanNode(this.pGridTitleTable);

	var
		r_day = this.pGridTitleTable.insertRow(-1),
		r_time = this.pGridTitleTable.insertRow(-1),
		c_day, c_time,
		l = this.GetDaysCount(),
		j, i, arCell;

	r_time.className = 'bxec-pl-time-row bxecpl-s' + this.scale;
	r_day.className = 'bxec-plan-grid-day-row';
	this.pGTCells = [];

	// Each day
	for (i = 0; i < l; i++)
	{
		c_day = r_day.insertCell(-1);
		c_day.innerHTML = '<img src="/bitrix/images/1.gif" class="day-t-left"/><div></div><img src="/bitrix/images/1.gif" class="day-t-right"/>';
		arCell = {pDay: c_day, pTitle: c_day.childNodes[1]};

		this.SetDayInCell(c_day, arCell.pTitle, i);

		if (this.scale == 0)
			c_day.colSpan = this.oTime.count * 2;
		else if (this.scale == 1)
			c_day.colSpan = this.oTime.count;
		else if (this.scale == 2)
			c_day.colSpan = Math.ceil(this.oTime.count / 2);

		if (this.scale != 3)
		{
			for (j = this.oTime.from.h; j < this.oTime.to.h; j++)
			{
				c_time = r_time.insertCell(-1);
				c_time.innerHTML = '<div>' + this.FormatTime(j, 0, false, false, true) + '</div>';
				c_time.title = this.FormatTime(i);

				if (this.scale == 2)
					j++;

				if (this.scale == 0)
				{
					c_time = r_time.insertCell(-1);
					c_time.className = 'bxecpl-half-t-cell';
					c_time.title = this.FormatTime(j, 30);

					if (this.bAMPM)
						c_time.innerHTML = '<div></div>';
					else
						c_time.innerHTML = '<div>' + this.FormatTime(j, 30) + '</div>';
				}
			}
		}
		else
		{
			c_time = r_time.insertCell(-1);
			c_time.innerHTML = '<div>' + this.FormatTime(this.oTime.from.h, 0, false, false, true) + ' - ' + this.FormatTime(this.oTime.to.h, 0, false, false, true) + '</div>';
			c_time.title = this.FormatTime(this.oTime.from.h) + ' - ' + this.FormatTime(this.oTime.to.h);

			arCell.pTime = c_time;
		}

		this.pGTCells.push(arCell);
	}
},

FormatTime: function(h, m, addSpace, bSkipZero, skipMinutes)
{
	var res = '';
	if (m == undefined)
		m = '00';
	else
	{
		m = parseInt(m, 10);
		if (isNaN(m))
			m = '00';
		else
		{
			if (m > 59)
				m = 59;
			m = (m < 10) ? '0' + m.toString() : m.toString();
		}
	}

	h = parseInt(h, 10);
	if (h > 24)
		h = 24;

	if (bSkipZero === true && h == 0 && m == '00')
		return '';

	if (this.bAMPM)
	{
		var ampm = 'am';
		if (h == 0)
		{
			h = 12;
		}
		else if (h == 12)
		{
			ampm = 'pm';
		}
		else if (h > 12)
		{
			ampm = 'pm';
			h -= 12;
		}

		if (skipMinutes && m.toString() == '00')
			res = h.toString();
		else
			res = h.toString() + ':' + m.toString();

		res += (addSpace ? ' ' : '') + ampm;
	}
	else
	{
		res = ((h < 10) ? '0' : '') + h.toString() + ':' + m.toString();
	}

	return res;
},

ParseTime: function(str)
{
	var h, m, arTime;
	str = BX.util.trim(str);
	str = str.toLowerCase();

	if (this.bAMPM)
	{
		var ampm = 'pm';
		if (str.indexOf('am') != -1)
			ampm = 'am';

		str = str.replace(/[^\d:]/ig, '');
		arTime = str.split(':');
		h = parseInt(arTime[0] || 0, 10);
		m = parseInt(arTime[1] || 0, 10);

		if (h == 12)
		{
			if (ampm == 'am')
				h = 0;
			else
				h = 12;
		}
		else if (h != 0)
		{
			if (ampm == 'pm' && h < 12)
			{
				h += 12;
			}
		}
	}
	else
	{
		arTime = str.split(':');
		h = arTime[0] || 0;
		m = arTime[1] || 0;
	}

	return {h: h, m: m};
},

SetDayInCell: function(pCell, pTitle, ind)
{
	var
		realInd = ind - (this.scale == 3 ? 2 : 1),
		oDate = new Date();

	oDate.setFullYear(this.currentDate.Y, this.currentDate.M, this.currentDate.D + realInd);

	var
		day = this.ConvertDayIndex(oDate.getDay()),
		date = oDate.getDate(),
		month = oDate.getMonth(),
		year = oDate.getFullYear(),
		str = bxFormatDate(date, month + 1, year),
		CD = this.currentDate,
		bHol = this.config.week_holidays[day] || this.config.year_holidays[date + '.' + month], //It's Holliday
		bCur = date == CD.date && month == CD.month && year == CD.year;

	if (this.scale == 3 && BX.message.FORMAT_DATE.indexOf('MMMM') != -1)
		str = zeroInt(date) + '.' + zeroInt(month + 1) + '.' + year;

	if (bHol && bCur)
		pCell.className = 'cur-hol-day';
	else if(bHol)
		pCell.className = 'hol-day';
	else if(bCur)
		pCell.className = 'cur-day';
	else
		pCell.className = '';

	pTitle.innerHTML = str;
	pCell.title = this.config.days[this.ConvertDayIndex(oDate.getDay())][0] + ', ' + str;
},

BuildGrid : function(length)
{
	var
		_this = this,
		oRow = this.pGridTable.rows[0] || this.pGridTable.insertRow(-1),
		dayWidth,
		cellWidth = this.cellWidth + 1,
		l = this.GetDaysCount(),
		h = length * 20;

	oRow.className = 'bxecp-bg-grid-row bxecpl-s' + this.scale;

	if (this.scale == 0)
		dayWidth = (cellWidth + 1) * this.oTime.count;
	else if(this.scale == 1)
		dayWidth = (cellWidth + 1) * this.oTime.count / 2;
	else if(this.scale == 2)
		dayWidth = (cellWidth + 1) * this.oTime.count / 4;
	else // this.scale == 3
		dayWidth = cellWidth;

	if (!this.oneGridDiv)
		this.oneGridDiv = oRow.insertCell(-1).appendChild(BX.create('DIV'));

	this.oneGridDiv.style.width = dayWidth * l + 'px';

	if (this.bMRShowed)
	{
		setTimeout(function(){_this.AdjustMRStub(true);}, 100);
		h += 60;
	}
	this.oneGridDiv.style.height = h + 'px';

	setTimeout(function(){_this.GridSetScrollLeft(_this.CheckScrollLeft(0, false));}, 100);
},

CheckScrollLeft: function(sl, bOffset)
{
	sl = parseInt(sl);
	var minS;

	if (this.scale == 0)
		minS = this.cellWidth * 2 * this.oTime.count / 2;
	else if(this.scale == 1)
		minS = this.cellWidth * this.oTime.count / 2;
	else if(this.scale == 2)
		minS = this.cellWidth * this.oTime.count / 4;
	else // this.scale == 3
		minS = this.cellWidth * 2;

	var maxS = Math.abs(parseInt(this.pGridDiv.scrollWidth) - this.gridDivWidth - minS);

	if (sl < minS)
	{
		sl = minS + sl;
		if (bOffset !== false)
			this.OffsetCurrentDate(-this.GetScrollOffset());
	}
	else if (sl > maxS)
	{
		sl = sl - minS;
		if (bOffset !== false)
			this.OffsetCurrentDate(this.GetScrollOffset());
	}

	return sl;
},

GridSetScrollLeft: function(sl)
{
	this.pGridTitleTable.style.left = '-' + sl + 'px';
	this.pGridDiv.scrollLeft = sl;
},

OffsetCurrentDate: function(offset, bMakeSel)
{
	var
		It, i, l = this.GetDaysCount(),
		oDate = new Date();

	oDate.setFullYear(this.currentDate.Y, this.currentDate.M, this.currentDate.D + offset);
	this.SetCurrentDate(oDate);
	this.GetTimelineLimits(true);
	this.DisplayDiagramEx();

	if (bMakeSel !== false && this.oSel.pDiv)
		this.oSel.Make({bFromTimeLimits : true, bSetTimeline: false});

	for (i = 0; i < l; i++)
	{
		It = this.pGTCells[i];
		this.SetDayInCell(It.pDay, It.pTitle, i);
	}
},

Resize: function(w, h)
{
	if (w < this.minWidth)
		w = this.minWidth;
	if (h < this.minHeight)
		h = this.minHeight;

	this.width = w;
	this.height = h;

	// Container
	this.pCont.style.width = (w - 22) + 'px';
	this.pCont.style.height = (h - 70) + 'px';

	// Grid container
	var
		gridH = h - 70 - 60 /*top cont*/ - 32/*bottom cont*/,
		gridW = w - 20;

	this.pGridCont.style.height = gridH + 'px';
	this.pGridTbl.style.height = gridH + 'px';

	//this.pGridTitle.style.width = (gridW - 180) + 'px';
	this.pUserListCont.style.height = (gridH - 45) + 'px';
	//this.pUserListDiv.style.height = (gridH - 40) + 'px';
	this.pGridCellCont.style.height = (gridH - 45) + 'px';

	this.gridDivWidth = gridW - 180 - 5;
	this.pGridDiv.style.width = (gridW - 180 - 5) + 'px';
	this.pGridTitleDiv.style.width = (gridW - 180 - 5) + 'px';

},

ResizerMouseDown: function()
{
	this.oPos = {top: parseInt(this.pPopupCont.style.top, 10), left: parseInt(this.pPopupCont.style.left, 10)};

	BX.bind(document, "mouseup", BX.proxy(this.ResizerMouseUp, this));
	BX.bind(document, "mousemove", BX.proxy(this.ResizerMouseMove, this));
},

ResizerMouseUp: function()
{
	BX.unbind(document, "mouseup", BX.proxy(this.ResizerMouseUp, this));
	BX.unbind(document, "mousemove", BX.proxy(this.ResizerMouseMove, this));

	this.oSel.Adjust();
	BX.userOptions.save('calendar_planner', 'settings', 'width', this.width);
	BX.userOptions.save('calendar_planner', 'settings', 'height', this.height);
},

ResizerMouseMove: function(e)
{
	var
		windowSize = BX.GetWindowSize(document),
		mouseX = e.clientX + windowSize.scrollLeft,
		mouseY = e.clientY + windowSize.scrollTop,
		w = mouseX - this.oPos.left,
		h = mouseY - this.oPos.top;

	this.Resize(w, h);
},

SetUsersInfo: function()
{

},

SetCurrentDate: function(oDate)
{
	this.currentDate = {oDate: oDate, Y: oDate.getFullYear(), M: oDate.getMonth(), D: oDate.getDate()};
},

GetGridCellWidth: function()
{
	return this.scale == 3 ? this.cellWidth + 1 : this.cellWidth / 2 + 1;
},

GetTimelineLimits: function(bRecalc)
{
	if (bRecalc || !this.TimelineLimits)
	{
		var
			offset = this.GetScrollOffset(),
			cd = this.currentDate,
			D1 = new Date(), D2 = new Date();

		D1.setFullYear(cd.Y, cd.M, cd.D - offset);
		D2.setFullYear(cd.Y, cd.M, cd.D + (this.GetDaysCount() - offset - 1));
		D1.setHours(0, 0, 0, 0);
		D2.setHours(23, 59, 59, 999);
		this.TimelineLimits = {from: D1, to: D2};
	}

	return this.TimelineLimits;
},

GetScrollOffset: function()
{
	return this.scale == 3 ? 2 : 1;
},

GetDaysCount: function()
{
	if (this.scale == 2)
		return 15;
	if (this.scale == 3)
		return 20;
	return 10;
},

GetDayCellWidth: function()
{
	var
		tc = this.oTime.count,
		cw = this.GetGridCellWidth();

	switch(parseInt(this.scale))
	{
		case 0:
			return cw * tc * 2;
		case 1:
			return cw * tc;
		case 2:
			return Math.ceil(cw * tc / 2);
		case 3:
			return cw;
	}
},

SetFields: function(Params)
{
	var
		F = Params.from,
		T = Params.to,
		Ftime = this.FormatTime(F.getHours(), F.getMinutes(), true, true),
		Ttime = this.FormatTime(T.getHours(), T.getMinutes(), true, true);

	if (!F || isNaN(F.getDate()) || !T || isNaN(T.getDate()))
		return;

	this.oSel.curSelFT = {from: F, to: T};
	if (F && T)
	{
		this.pFrom.value = bxFormatDate(F.getDate(), F.getMonth() + 1, F.getFullYear());
		this.pTo.value = bxFormatDate(T.getDate(), T.getMonth() + 1, T.getFullYear());

		this.pFromTime.value = Ftime;
		this.pToTime.value = Ttime;

		this.pDuration.Set(T.getTime() - F.getTime());
	}
	else
	{
		this.pFrom.value = this.pTo.value = this.pFromTime.value = this.pToTime.value = '';
	}
},

GetFieldDate: function(type)
{
	var oDate = BX.parseDate(type == 'from' ? this.pFrom.value : this.pTo.value);
	if (oDate)
	{
		var time = this.ParseTime(type == 'from' ? this.pFromTime.value : this.pToTime.value);
		oDate.setHours(time.h);
		oDate.setMinutes(time.m);
	}

	return oDate;
},

FieldDatesOnChange: function(bRefreshDur, bFrom)
{
	if (this.bFreezed)
		return false;

	if (bFrom && this.oSel)
		this.bFocusSelection = true;

	if (bFrom && !isNaN(parseInt(this.pDuration.pInp.value)))
		return this.pDuration.OnChange();

	var
		time,
		F = BX.parseDate(this.pFrom.value),
		T = BX.parseDate(this.pTo.value);

	if (F)
	{
		time = this.ParseTime(this.pFromTime.value);
		F.setHours(time.h);
		F.setMinutes(time.m);
	}

	if (T)
	{
		time = this.ParseTime(this.pToTime.value);
		T.setHours(time.h);
		T.setMinutes(time.m);
	}

	if (F && T)
	{
		if (bRefreshDur !== false)
			this.pDuration.Set(T.getTime() - F.getTime());
		this.oSel.Make({bFromTimeLimits : true, from: F, to: T, bSetFields: false});
	}
	else
	{
		this.oSel.Hide();
	}
},

CheckBTime: function(date)
{
	return date.getHours() == 0 && date.getMinutes() == 0;
},

ReColourTable: function()
{
	var i, l = this.pUserListTable.rows.length;
	if (this.bMRShowed)
	{
		l -= 2;
		this.MRControll.pLoc.className = (l / 2 == Math.round(l / 2)) ? '' : 'bx-grey';
	}

	for (i = 0; i < l; i++)
		this.pUserListTable.rows[i].className = (i / 2 == Math.round(i / 2)) ? '' : 'bx-grey';
},

LocationOnChange: function(oLoc, ind, value)
{
	this.curLocationInd = ind;
	this.curLocationValue = value;

	if (ind === false)
	{
		this.ShowMRControll(false);
	}
	else
	{
		this.AddMR(ind);
		this.ShowMRControll();
	}
},

AddMR: function(ind)
{
	if (!this.meetingRooms)
		return;
	var
		_this = this,
		oMR = this.meetingRooms[ind];

	if (!oMR)
		return;

	if (!this.MRControll)
	{
		var
			r = this.pUserListTable.insertRow(-1),
			c = r.insertCell(-1);
		r.className = 'bxec-mr-title';
		c.colSpan = "2";
		c.innerHTML = '<b>' + BXPL_MESS.Location + '</b>';

		var
			r1 = this.pUserListTable.insertRow(-1),
			c1 = r1.insertCell(-1),
			c2 = r1.insertCell(-1);

		c1.innerHTML = '<img src="/bitrix/images/1.gif"/>';
		c1.className = 'bxecp-mr-icon';
		c2.onmouseover = function(){this.className = 'bxex-pl-u-over';};
		c2.onmouseout = function(){this.className = '';};

		var mrStubDiv = this.pGridDiv.appendChild(BX.create('DIV', {props:{className: 'bxecpl-mr-stub'}}));
		this.MRControll = {pTitle: r, pLoc: r1, pLocName: c2, stub: mrStubDiv};
	}

	this.MRControll.pLocName.innerHTML = '<div>' + (oMR.URL ? '<a href="' + oMR.URL+ '" target="_blank">' + BX.util.htmlspecialchars(oMR.NAME) + '</a>' : BX.util.htmlspecialchars(oMR.NAME)) + '</div>';
	var pDel = this.MRControll.pLocName.appendChild(BX.create('IMG', {props: {src: '/bitrix/images/1.gif', title: BXPL_MESS.FreeMR, className: 'bxecp-del'}}));
	pDel.onclick = function(){_this.Location.Set(false, '');};

	this.MRControll.pLoc.title = oMR.NAME;

	this.GetMRAccessibility(ind);
},

ShowMRControll: function(bShow)
{
	var
		dis = 'none',
		l1 = this.Attendees.length || 0,
		h = l1 * 20;
	bShow = bShow !== false;
	this.bMRShowed = bShow;

	if (bShow)
	{
		h += 60;
		dis = BX.browser.IsIE() ? 'inline' : 'table-row';
	}
	else
	{
		this.CleanMRDiagram();
	}

	if (this.oneGridDiv)
		this.oneGridDiv.style.height = h + 'px';

	this.oSel.Adjust();
	if (this.MRControll)
	{
		this.AdjustMRStub(bShow);
		this.MRControll.pLoc.className = (l1 / 2 == Math.round(l1 / 2)) ? '' : 'bx-grey';
		this.MRControll.pTitle.style.display = this.MRControll.pLoc.style.display = dis;
		this.MRControll.pTitle.className = 'bxec-mr-title';
	}
},

AdjustMRStub: function(bShow)
{
	if (this.MRControll && this.MRControll.stub)
	{
		this.MRControll.stub.style.display = bShow ? 'block' : 'none';
		if (bShow)
		{
			var w = parseInt(this.pGridTable.offsetWidth) - 1;
			if (isNaN(w) || w <= 0)
			{
				var _this = this;
				return setTimeout(function(){_this.AdjustMRStub(bShow);}, 100);
			}

			this.MRControll.stub.style.top = parseInt(this.Attendees.length) * 20 + 'px';
			this.MRControll.stub.style.width = (parseInt(this.pGridTable.offsetWidth) - 1) + 'px';
		}
	}
},

GetScrollBarSize: function()
{
	if (!this._sbs)
	{
		var div = this.pPopupCont.appendChild(BX.create('DIV', {props: {className: 'bxex-sbs'}, html: '&nbsp;'}));
		this._sbs = div.offsetWidth - div.clientWidth;
		setTimeout(function(){div.parentNode.removeChild(div);},50);
	}
	return this._sbs || 20;
},

ConvertDayIndex : function(i)
{
	if (i == 0)
		return 6;
	return i - 1;
},

GetReqData : function(action, O)
{
	if (!O)
		O = {};
	if (action)
		O.action = action;
	O.sessid = BX.bitrix_sessid();
	O.bx_event_calendar_request = 'Y';
	O.reqId = Math.round(Math.random() * 1000000);
	return O;
},

Request : function(P)
{
	if (!P.url)
		P.url = this.actionUrl;
	if (P.bIter !== false)
		P.bIter = true;

	if (!P.postData && !P.getData)
		P.getData = this.GetReqData();

	var
		_this = this, iter = 0,
		reqId = P.getData ? P.getData.reqId : P.postData.reqId;

	var handler = function(result)
	{
		var handleRes = function()
		{
			BX.closeWait(_this.pPopupCont);
			var res = P.handler(_this.GetRequestRes(reqId), result);
			if(res === false && ++iter < 20 && P.bIter)
				setTimeout(handleRes, 5);
			else
				_this.ClearRequestRes(reqId);
		};
		setTimeout(handleRes, 20);
	};
	BX.showWait(this.pPopupCont);

	if (P.postData)
		BX.ajax.post(P.url, P.postData, handler);
	else
		BX.ajax.get(P.url, P.getData, handler);
},

GetRequestRes: function(key)
{
	if (top.BXCRES && typeof top.BXCRES[key] != 'undefined')
		return top.BXCRES[key];

	return {};
},

ClearRequestRes: function(key)
{
	if (top.BXCRES)
	{
		top.BXCRES[key] = null;
		delete top.BXCRES[key];
	}
},

InitUserControll: function(Params)
{
	var _this = this;

	this.pAddUserLinkCont = BX(this.id + 'pl_user_control_link');
	this.pCount = BX(this.id + 'pl-count');

	// Clear all users list
	BX(this.id + '_planner_del_all').onclick = BX.proxy(this.ClearUserList, this);

	var
		pIcon = this.pAddUserLinkCont.appendChild(BX.create("I")),
		pTitle = this.pAddUserLinkCont.appendChild(BX.create("SPAN", {text: BXPL_MESS.AddAttendees}));

	pIcon.onclick = pTitle.onclick = BX.proxy(this.OpenSelectUser, this);

	var arMenuItems = [{text : BXPL_MESS.AddGuestsDef, onclick: BX.proxy(this.OpenSelectUser, this)}];

	if (this.bAddGroupMembers)
		arMenuItems.push({text : BXPL_MESS.AddGroupMemb, title: BXPL_MESS.AddGroupMembTitle, onclick: BX.proxy(this.AddGroupMembers, this)});
	//arMenuItems.push({text : BXPL_MESS.AddGuestsEmail,onclick: BX.proxy(this.AddByEmail, this)});

	if (arMenuItems.length > 1)
	{
		pMore = this.pAddUserLinkCont.appendChild(BX.create("A", {props: {href: 'javascript: void(0);', className: 'bxec-add-more'}}));
		pMore.onclick = function()
		{
			BX.PopupMenu.show('bxec_add_guest_menu', _this.pAddUserLinkCont, arMenuItems, {events: {onPopupClose: function() {BX.removeClass(pMore, "bxec-add-more-over");}}});
			BX.addClass(pMore, "bxec-add-more-over");
		};
	}

	BX.addCustomEvent(window, "onPlannerAttendeeOnChange", BX.proxy(this.UserOnChange, this));
},

SetValues: function(Attendees)
{
	var i, l = Attendees.length, User;

	for(i = 0; i < l; i++)
	{
		User = Attendees[i];
		User.key = User.id || User.email;
		if (User && User.key && !this.oAttendees[User.key])
			this.DisplayAttendee(User);
	}

	this.DisableUserOnChange(true, true);
	O_BXPlannerUserSelect.setSelected(Attendees);

	this.UpdateCount();
},

UpdateCount: function()
{
	this.BuildGrid(this.count);
	this.ReColourTable();
	this.oSel.Adjust();

	if (this.count == 0)
	{
		this.pCount.innerHTML = '';
		this.Freeze(true);
	}
	else
	{
		this.pCount.innerHTML = ' (' + this.count + ')';
		this.Freeze(false);
		this.CheckAccessibility(true);
	}
},

OpenSelectUser : function(e)
{
	if (BX.PopupMenu && BX.PopupMenu.currentItem)
		BX.PopupMenu.currentItem.popupWindow.close();

	if(!e) e = window.event;
	if (!this.SelectUserPopup)
	{
		var _this = this;
		this.SelectUserPopup = BX.PopupWindowManager.create("bxc-user-popup-plan", this.pAddUserLinkCont, {
			offsetTop : 1,
			autoHide : true,
			closeByEsc : true,
			content : BX("BXPlannerUserSelect_selector_content"),
			className: 'bxc-popup-user-select',
			closeIcon: { right : "12px", top : "5px"},

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
								if (!_this.oAttendees[id] && _this.selectedUsers[id]) // Add new user
								{
									_this.selectedUsers[id].key = id;
									_this.DisplayAttendee(_this.selectedUsers[id]);
								}
								else if(_this.oAttendees[id] && !_this.selectedUsers[id]) // Del user from our list
								{
									_this.RemoveAttendee(id);
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

	this.selectedUsers = {};
	var Attendees = [], key;
	for (key in this.oAttendees)
	{
		if (this.oAttendees[key] && this.oAttendees[key].type != 'ext')
			Attendees.push(this.oAttendees[key].User);
	}
	O_BXPlannerUserSelect.setSelected(Attendees);

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
		var pDiv = BX.create("DIV", {props:{className: 'bxc-email-cont'}, html: '<label class="bxc-email-label">' + BXPL_MESS.UserEmail + ':</label>'});
		this.pEmailValue = pDiv.appendChild(BX.create('INPUT', {props: {className: 'bxc-email-input'}}));

		this.EmailPopup = BX.PopupWindowManager.create("bxc-user-popup-email", this.pAddUserLinkCont, {
			offsetTop : 1,
			autoHide : true,
			content : pDiv,
			className: 'bxc-popup-user-select-email',
			closeIcon: { right : "12px", top : "5px"},
			closeByEsc : true,
			buttons: [
			new BX.PopupWindowButton({
				text: BXPL_MESS.Add,
				className: "popup-window-button-accept",
				events: {click : function(){
					var email = BX.util.trim(_this.pEmailValue.value);
					if (email != "" && !_this.oAttendees[email])
					{
						var User = {name: email, key: email, type: 'ext', status: 'Y'};
						_this.DisplayAttendee(User);
						_this.UpdateCount();
					}
					_this.EmailPopup.close();
				}}
			}),
			new BX.PopupWindowButtonLink({
				text: BXPL_MESS.Close,
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
		setTimeout(BX.proxy(this.DisableUserOnChange, this), 200);
},

UserOnChange: function(arUsers)
{
	if (this.bDisableUserOnChange)
		return;

	this.selectedUsers = arUsers;
},

DisplayAttendee: function(User)
{
	var _this = this;
	this.count++;

	if (User.id && !this.oAttendees[User.id])
	{
		var pRow = this.pUserListTable.insertRow(this.count - 1);
		pRow.id = 'ec_pl_u_' + User.id;

		// Icon
		var c1 = pRow.insertCell(-1);
		c1.className = 'bxecp-user-icon';
		c1.title = BXPL_MESS.ImpGuest;
		c1.innerHTML = '<img src="/bitrix/images/1.gif"/>';

		// User name
		c2 = pRow.insertCell(-1);
		c2.innerHTML = '<div>' + BX.util.htmlspecialchars(User.name) + '</div>';
		c2.onmouseover = function(){this.className = 'bxex-pl-u-over';};
		c2.onmouseout = function(){this.className = '';};

		pDel = c2.appendChild(BX.create('I', {props: {id: 'bxc-att-key-' + User.key, title: BXPL_MESS.DelGuestTitle, className: 'bxplan-del'}}));
		pDel.onclick = function(){_this.RemoveAttendee(this.id.substr('bxc-att-key-'.length));};

		this.oAttendees[User.key] = {
			User : User,
			pRow: pRow,
			ind: this.Attendees.length
		};
		this.Attendees.push(this.oAttendees[User.key]);

		if (User.id > 0)
			this.AttendeesIds.push(User.id);
	}
},

RemoveAttendee: function(key)
{
	if (!this.oAttendees[key])
		return;

	this.oAttendees[key].pRow.parentNode.removeChild(this.oAttendees[key].pRow);

	this.count--;
	this.oAttendees[key] = null;
	delete this.oAttendees[key];

	var Attendees = [];
	this.Attendees = [];
	this.AttendeesIds = [];
	for (key in this.oAttendees)
	{
		if (this.oAttendees[key])
		{
			if (this.oAttendees[key].type != 'ext')
			{
				Attendees.push(this.oAttendees[key].User);
				this.AttendeesIds.push(this.oAttendees[key].User.id);
			}

			this.Attendees.push(this.oAttendees[key]);
		}
	}
	this.DisableUserOnChange(true, true);
	O_BXPlannerUserSelect.setSelected(Attendees);

	// Decrease grid height
	this.UpdateCount();
	this.DisplayDiagram(false, true);
	this.DisplayMRDiagram(false);
},

ClearUserList: function(bConfirm)
{
	if (bConfirm !== false && !confirm(BXPL_MESS.DelAllGuestsConf))
		return;

	var row = true, rowIndex = 0;
	while(rowIndex < this.pUserListTable.rows.length)
	{
		row = this.pUserListTable.rows[rowIndex];
		if (row && ~row.id.indexOf('ec_pl_u_'))
			row.parentNode.removeChild(row);
		else
			rowIndex++;
	}

	this.count = 0;
	this.oAttendees = {};
	var Attendees = [];
	this.Attendees = [];
	this.AttendeesIds = [];

	this.DisableUserOnChange(true, true);
	O_BXPlannerUserSelect.setSelected(Attendees);

	// Decrease grid height
	this.UpdateCount();
	this.DisplayDiagram(false, true);
	this.DisplayMRDiagram(false);

	var i, l1 = this.Attendees.length;
	for (i = 0; i < l1; i++)
	{
		if (this.Attendees[i].id == id)
		{
			if (this.Attendees[i].bDel === false)
			{
				if (confirm(BXPL_MESS.DelOwnerConfirm))
					this.DelAllGuests();
				return true;
			}

			// Del from list
			pRow.parentNode.removeChild(pRow);
			// Del from arrays
			this.Attendees = BX.util.deleteFromArray(this.Attendees, i);
			break;
		}
	}
}
};

function ECPlSelection(oPlanner)
{
	this.oPlanner = oPlanner;
	this.id = this.oPlanner.id;
	this.pGrid = oPlanner.pGridDiv;

	this.pGrid.onmousedown = BX.proxy(this.MouseDown, this);
	this.pGrid.onmouseup = BX.proxy(this.MouseUp, this);
}

ECPlSelection.prototype = {
Make: function(Params)
{
	var
		left, width,tl,
		cellW = this.oPlanner.GetGridCellWidth(),
		_a,
		from = Params.from,
		to = Params.to;

	if (!this.pDiv)
		this.Create();

	this.pDiv.style.display = 'block';
	if (Params.bFromTimeLimits)
	{
		Params.bSetTimeline = Params.bSetTimeline !== false;
		tl = this.oPlanner.GetTimelineLimits(true);
		if (!from)
			from = this.curSelFT.from;
		if (!to)
			to = this.curSelFT.to;

		var
			off, offms,
			bOutOfLimits1 = from.getTime() < tl.from.getTime(),
			bOutOfLimits2 = to.getTime() > tl.to.getTime();
		if (bOutOfLimits1 || bOutOfLimits2)
		{
			if (Params.bSetTimeline)
			{
				// Get offset
				if (bOutOfLimits1)
					off = Math.round((from.getTime() - tl.from.getTime()) / 86400000) - 2;
				else
					off = Math.round((from.getTime() - tl.to.getTime()) / 86400000) + 5;

				this.oPlanner.OffsetCurrentDate(off, false);
			}
			else
			{
				this.Hide();
			}
		}

		tl = this.oPlanner.GetTimelineLimits(true);
		var
			dcw = this.oPlanner.GetDayCellWidth(),
			x1 = this._GetXByDate({date: from, tl: tl, dcw: dcw}),
			x2 = this._GetXByDate({date: to, tl: tl, dcw: dcw});

		if (this.oPlanner.CheckBTime(to) || x1 == x2)
			x2 = x2 + dcw;

		left = x1;
		width = x2 - x1 - 1;

		if (width <= 0)
			return false;
		this.curSelFT = {from: from, to: to};
	}
	else
	{
		if (from > to) // swap start_ind and end_ind
		{
			_a = from;
			from = to;
			to = _a;
		}

		left = (from - 1) * cellW;
		width = (to) * cellW - left - 1;
	}

	this.pDiv.style.left = left + 'px'; // Set left
	this.pDiv.style.width = width + 'px'; // Set width
	this.Check(this.GetCurrent(), false, Params.bSetFields !== false);
	this.pMover.style.left = (Math.round(width / 2) - 6) + 'px'; // Set Mover

	// Focus
	if (this.oPlanner.bFocusSelection)
	{
		this.pGrid.scrollLeft = left - 50;
		this._bScrollMouseDown = true;
		this.MouseUp();
	}
	this.oPlanner.bFocusSelection = false;
},

Hide: function()
{
	if (this.pDiv)
		this.pDiv.style.display = 'none';
},

_GetXByDate: function(Params)
{
	var
		oTime = this.oPlanner.oTime,
		dayLen = 86400000,
		limFrom = Params.tl.from.getTime(),
		ts = Params.date.getTime(),
		dispTimeF = oTime.from.h + oTime.from.m / 60,
		x = Params.dcw * Math.floor((ts - limFrom) / dayLen),
		dfTime = Params.date.getHours() + Params.date.getMinutes() / 60,
		time = dfTime - dispTimeF;

	if (time > 0)
		x += Math.round((Params.dcw * time) / oTime.count);
	return x;
},

Create: function()
{
	this.pDiv = BX(this.id + '_plan_selection');
	var
		_this = this,
		imgL = this.pDiv.childNodes[0],
		imgR = this.pDiv.childNodes[1];

	imgL.onmousedown = function(e){_this.StartTransform({e: e, bLeft: true}); return BX.PreventDefault(e);};
	imgR.onmousedown = function(e){_this.StartTransform({e: e, bLeft: false}); return BX.PreventDefault(e);};

	this.pMover = this.pDiv.childNodes[2];
	this.pMover.onmousedown = function(e){_this.StartTransform({e: e, bMove: true}); return BX.PreventDefault(e);};

	this.bDenied = false;
	this.curSelFT = {};

	DenyDragEx(imgL);
	DenyDragEx(imgR);
	DenyDragEx(this.pDiv);

	this.Adjust();
},

Adjust: function()
{
	if (!this.pDiv)
		return;

	var
		h1 = parseInt(this.oPlanner.pGridTable.offsetHeight),
		h2 = parseInt(this.oPlanner.pGridCellCont.offsetHeight) - this.oPlanner.GetScrollBarSize();

	this.pDiv.style.height = (Math.max(h1, h2) - 2) + 'px';
},

MouseDown: function(e)
{
	if (this.MoveParams)
		return;

	// Remember  scroll pos
	this._gridScrollLeft = parseInt(this.pGrid.scrollLeft);

	var
		grigPos = BX.pos(this.pGrid),
		mousePos = this.GetMouseXY(e);

	// Click on the scrollbar
	if ((grigPos.top + parseInt(this.pGrid.offsetHeight) - mousePos.y < this.oPlanner.GetScrollBarSize()) // Hor scroll
		|| (grigPos.left + parseInt(this.pGrid.offsetWidth) - mousePos.x < this.oPlanner.GetScrollBarSize())) // Vert scroll
	{
		this._bScrollMouseDown = true;
		return true;
	}

	this._bGridMouseDown = true;
	var ind = this.GetOverCellIndex({mousePos: mousePos, grigPos: grigPos});

	// Remember grigPos
	this.grigPos = grigPos;
	this.curSelection = {from: ind, to: ind};

	// Add mouse move handler
	BX.unbind(document, "mousemove", BX.proxy(this.MouseMove, this));
	BX.bind(document, "mousemove", BX.proxy(this.MouseMove, this));

	this.Make(this.curSelection);
},

MouseMove: function(e)
{
	if (this.MoveParams)
	{
		this.Transform({mousePos: this.GetMouseXY(e), grigPos: this.grigPos, MoveParams: this.MoveParams});
		this.TimeoutCheck();
	}
	else
	{
		var ind = this.GetOverCellIndex({mousePos: this.GetMouseXY(e), grigPos: this.grigPos});

		if (this.curSelection && ind != this.curSelection.to)
		{
			this.curSelection.to = ind;
			this.Make(this.curSelection);
		}
	}
},

MouseUp: function()
{
	if (this._bGridMouseDown)
	{
		BX.unbind(document, "mousemove", BX.proxy(this.MouseMove, this));
		if (this.MoveParams)
			this.MoveParams = false;

		this.Check(this.GetCurrent());
	}
	else if (this._bScrollMouseDown)
	{
		var sl = parseInt(this.pGrid.scrollLeft);
		if (sl != this._gridScrollLeft) // User move scroller - and we check and set correct 'middle' - position
			this.oPlanner.GridSetScrollLeft(this.oPlanner.CheckScrollLeft(sl));
	}

	this._bGridMouseDown = false;
	this._bScrollMouseDown = false;
},

StartTransform: function(Params)
{
	if (!Params.bMove && this.oPlanner.pDuration.bLocked)
	{
		this.oPlanner.pDuration.LockerBlink();
		Params.bMoveBySide = !!Params.bLeft;
		Params.bLeft = null;
		Params.bMove = true;
	}
	this.MoveParams = Params;

	// Remember  scroll pos
	this._gridScrollLeft = parseInt(this.pGrid.scrollLeft);
	this._bGridMouseDown = true;

	var
		grigPos = BX.pos(this.pGrid),
		mousePos = this.GetMouseXY(Params.e);

	if (grigPos.top + parseInt(this.pGrid.offsetHeight) - mousePos.y < this.oPlanner.GetScrollBarSize()) // Click on the scrollbar
		return true;

	// Remember grigPos
	this.grigPos = grigPos;
	this.divCurPar = {left: parseInt(this.pDiv.style.left, 10), width: parseInt(this.pDiv.style.width, 10)};
	this.curSelection = false;

	// Add mouse move handler
	BX.unbind(document, "mousemove", BX.proxy(this.MouseMove, this));
	BX.bind(document, "mousemove", BX.proxy(this.MouseMove, this));
},

Transform: function(Params)
{
	if (!this.pDiv)
		return false;

	var newLeft, newWidth;
	if (Params.MoveParams.bLeft) // Move left slider
	{
		newLeft = parseInt(this.pGrid.scrollLeft) + (Params.mousePos.x - Params.grigPos.left);
		if (newLeft < 0)
			newLeft = 0;
		if (newLeft > this.divCurPar.left + this.divCurPar.width - 10)
			newLeft = this.divCurPar.left + this.divCurPar.width - 10;

		newWidth = this.divCurPar.width + this.divCurPar.left - newLeft;

		this.pDiv.style.left = newLeft + 'px'; // Set new left
		this.pDiv.style.width = newWidth + 'px'; // Set new width
		this.pMover.style.left = (Math.round(newWidth / 2) - 6) + 'px'; // Set Mover
	}
	else if (!Params.MoveParams.bMove)// Move right slider
	{
		newWidth = parseInt(this.pGrid.scrollLeft) + (Params.mousePos.x - Params.grigPos.left) - this.divCurPar.left;
		if (newWidth < 10)
			newWidth = 10;

		this.pDiv.style.width = newWidth + 'px'; // Set new width
		this.pMover.style.left = (Math.round(newWidth / 2) - 6) + 'px'; // Set Mover
	}
	else if (Params.MoveParams.bMove) // Move whole selection
	{
		var
			w = this.divCurPar.width / 2,
			mbs = Params.MoveParams.bMoveBySide;

		if (mbs === true) // left
			w =  0;
		else if(mbs === false)
			w =  this.divCurPar.width;

		newLeft = Math.round(parseInt(this.pGrid.scrollLeft) + (Params.mousePos.x - Params.grigPos.left) - w);
		if (newLeft < 0)
			newLeft = 0;
		this.pDiv.style.left = newLeft + 'px'; // Set new left
	}
},

GetOverCellIndex: function(Params)
{
	var grigPos = Params.grigPos || BX.pos(this.pGrid);
	return Math.ceil((parseInt(this.pGrid.scrollLeft) + (Params.mousePos.x - grigPos.left)/*dx*/) / this.oPlanner.GetGridCellWidth());
},

GetCurrent: function()
{
	if (!this.pDiv)
		return;
	var
		tl = this.oPlanner.GetTimelineLimits(),
		dcw = this.oPlanner.GetDayCellWidth(),
		left = parseInt(this.pDiv.style.left, 10),
		width = parseInt(this.pDiv.style.width, 10) + 0.5;

	return {
		from: this._GetDateByX({x: left, fromD: tl.from, dcw: dcw}),
		to: this._GetDateByX({x: left + width, fromD: tl.from, dcw: dcw})
	};
},

_GetDateByX: function(Params)
{
	var
		oTime = this.oPlanner.oTime,
		day = Math.floor(Params.x / Params.dcw),
		time = oTime.count * (Params.x - day * Params.dcw) / Params.dcw,
		timeH = Math.floor(time),
		hour = oTime.from.h + timeH,
		_k = this.oPlanner.scale == 3 ? 10 : 5,
		min = Math.round((time - timeH) * 60 / _k) * _k,
		D = new Date(),
		Df = Params.fromD;

	D.setFullYear(Df.getFullYear(), Df.getMonth(), Df.getDate() + day);
	D.setHours(hour, min, 0, 0);

	return D;
},

Check: function(curSel, bBlink, bSetFields)
{
	if (!this.oPlanner.arACC || !this.pDiv)
		return;

	var
		bDeny = false, i, l,
		aac = this.oPlanner.arACC,
		f = curSel.from.getTime() + 1,
		t = curSel.to.getTime() - 1;

	this.arBusyGuests = {};
	if (this.oPlanner.bMRShowed && typeof this.oPlanner.arMRACC == 'object')
		aac = aac.concat(this.oPlanner.arMRACC);

	l = aac.length;

	for (i = 0; i < l; i++)
	{
		if (aac[i].from < t && aac[i].to > f)
		{
			bDeny = true;

			if (aac[i].uid > 0)
				this.arBusyGuests[aac[i].uid] = aac[i].acc || 'busy';

			if (bBlink !== false)
				this.oPlanner.BlinkDiagramDiv(aac[i].div);
		}
	}

	if (bSetFields !== false)
		this.oPlanner.SetFields(curSel);

	this.SetDenied(bDeny);
},

SetDenied: function(bDeny)
{
	if (!this.pDiv || this.bDenied == bDeny)
		return;

	this.bDenied = bDeny;
	if (bDeny)
		BX.addClass(this.pDiv, 'bxecp-sel-deny');
	else
		BX.removeClass(this.pDiv, 'bxecp-sel-deny');
},

TimeoutCheck: function()
{
	if (!this.bTimeoutCheck)
	{
		var _this = this;
		this.bTimeoutCheck = true;
		setTimeout(
			function()
			{
				_this.Check(_this.GetCurrent(), false);
				_this.bTimeoutCheck = false;
			},
			200
		);
	}
},

GetMouseXY: function(e)
{
	if (!e)
		e = window.event;

	var x = 0, y = 0;
	if (e.pageX || e.pageY)
	{
		x = e.pageX;
		y = e.pageY;
	}
	else if (e.clientX || e.clientY)
	{
		x = e.clientX + (document.documentElement.scrollLeft || document.body.scrollLeft) - document.documentElement.clientLeft;
		y = e.clientY + (document.documentElement.scrollTop || document.body.scrollTop) - document.documentElement.clientTop;
	}

	return {x: x, y: y};
}
};

function ECPlDuration(oPlanner)
{
	this.oPlanner = oPlanner;
	this.id = this.oPlanner.id;
	var _this = this;

	this.pInp = BX(this.id + '_pl_dur');
	this.pType = BX(this.id + '_pl_dur_type');
	this.pLock = BX(this.id + '_pl_dur_lock');

	this.bLocked = false;
	this.pLock.onclick = function(){_this.Lock();};
	this.pLock.onmouseover = function(){BX.addClass(this, 'icon-hover');};
	this.pLock.onmouseout = function(){BX.removeClass(this, 'icon-hover');};

	this.pInp.onclick = function(){_this.ShowPopup();};

	this.pType.onchange = this.pInp.onchange = function(){_this.OnChange();};
}

ECPlDuration.prototype = {
Set: function(ms)
{
	var
		days,days2,
		type = 'min',
		val = Math.round(ms / (1000 * 60 * 5)) * 5,
		hours = val / 60;

	if (val <= 0)
		return false;

	if (hours == Math.round(hours))
	{
		val = hours;
		type = 'hour';
		days = hours / this.oPlanner.oTime.count;
		days2 = hours / 24;

		if (days == Math.round(days))
		{
			type = 'day';
			val = days;
		}
		else if(days2 == Math.round(days2))
		{
			type = 'day';
			val = days2;
		}
	}

	this.pInp.value = val;
	this.pType.value = type;
},

Lock: function()
{
	this.bLocked = !this.bLocked;
	if (this.bLocked)
		BX.addClass(this.pLock, 'bxecpl-lock-pushed');
	else
		BX.removeClass(this.pLock, 'bxecpl-lock-pushed');
},

LockerBlink: function()
{
	if (!this.bLocked)
		return;
	var
		pel = this.pLock,
		iter = 0,
		origClass = 'bxecpl-lock-dur bxecpl-lock-pushed',
		warnClass = "bxecpl-lock-dur icon-blink";

	if (origClass != warnClass)
	{
		var blInt = setInterval(
			function()
			{
				pel.className = (pel.className == warnClass) ? origClass : warnClass;
				if (++iter > 5)
					clearInterval(blInt);
			},250
		);
	}
},

OnChange: function()
{
	var
		dur, // duration in minutes
		Date = this.oPlanner.GetFieldDate('from', false),
		count = parseInt(this.pInp.value, 10),
		type = this.pType.value;

	if (isNaN(count) || count <= 0)
		count = 1;
	else if (type == 'min')
		count = Math.round(count / 5) * 5;

	this.pInp.value = count;

	if (Date)
	{
		if (type == 'min')
			dur = count;
		if (type == 'hour')
			dur = count * 60;
		else if (type == 'day')
			dur = count * 60 * 24;

		Date.setTime(Date.getTime() + dur * 60 * 1000); // Set end of the event
		this.oPlanner.pTo.value = bxFormatDate(Date.getDate(), Date.getMonth() + 1, Date.getFullYear());
		var Ttime = zeroInt(Date.getHours()) + ':' + zeroInt(Date.getMinutes());
		this.oPlanner.pToTime.value = Ttime == '00:00' ? '' : Ttime;
	}

	this.oPlanner.FieldDatesOnChange(false);
},

ShowPopup: function()
{
	var _this = this;
	this.pInp.select();

	if (this.bPopupShowed)
		return this.ClosePopup();

	if (!this.Popup)
		this.CreatePopup();

	this.Popup.style.display = 'block';
	this.bPopupShowed = true;
	this.oPlanner.bDenyClose = true;

	this.Popup.style.zIndex = 1000;
	var pos = BX.pos(this.pInp);
	jsFloatDiv.Show(this.Popup, pos.left + 2, pos.top + 22, 5, false, false);

	// Add events
	BX.bind(document, "keypress", window['BXEC_DURDEF_CLOSE_' + this.id]);
	setTimeout(function(){BX.bind(document, "click", window['BXEC_DURDEF_CLOSE_' + _this.id]);}, 1);
},

ClosePopup: function()
{
	this.Popup.style.display = 'none';
	this.bPopupShowed = false;
	this.oPlanner.bDenyClose = false;
	jsFloatDiv.Close(this.Popup);
	BX.unbind(document, "keypress", window['BXEC_DURDEF_CLOSE_' + this.id]);
	BX.unbind(document, "click", window['BXEC_DURDEF_CLOSE_' + this.id]);
},

CreatePopup: function()
{
	this.arDefValues = [
		{val: 15, type: 'min', title: '15 ' + BXPL_MESS.DurDefMin},
		{val: 30, type: 'min', title: '30 ' + BXPL_MESS.DurDefMin},
		{val: 1, type: 'hour', title: '1 ' + BXPL_MESS.DurDefHour1},
		{val: 2, type: 'hour', title: '2 ' + BXPL_MESS.DurDefHour2},
		{val: 3, type: 'hour', title: '3 ' + BXPL_MESS.DurDefHour2},
		{val: 4, type: 'hour', title: '4 ' + BXPL_MESS.DurDefHour2},
		{val: 1, type: 'day', title: '1 ' + BXPL_MESS.DurDefDay}
	];

	var
		_this = this,
		pRow, i, l = this.arDefValues.length;

	this.Popup = document.body.appendChild(BX.create("DIV", {props: {className: "bxecpl-dur-popup"}}));

	for (i = 0; i < l; i++)
	{
		pRow = this.Popup.appendChild(BX.create("DIV", {props: {id: 'ecpp_' + i, title: this.arDefValues[i].title}, text: this.arDefValues[i].title}));

		pRow.onmouseover = function(){this.className = 'bxecpldur-over';};
		pRow.onmouseout = function(){this.className = '';};
		pRow.onclick = function()
		{
			var cur = _this.arDefValues[this.id.substr('ecpp_'.length)];
			_this.pInp.value = cur.val;
			_this.pType.value = cur.type;
			_this.OnChange();
			_this.ClosePopup();
		};
	}

	window['BXEC_DURDEF_CLOSE_' + this.id] = function(e){_this.ClosePopup();};
}
};

})(window);