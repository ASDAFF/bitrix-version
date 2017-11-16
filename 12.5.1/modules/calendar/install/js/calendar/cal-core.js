function JCEC(Params) // Javascript Class Event Calendar
{
	this.arConfig = Params;
	top.BXCRES = {};

	// Data
	this.id = Params.id;
	this.pCalCnt = BX(this.id + '_bxcal');
	//this.arEvents = Params.events;
	//this.arAttendees = Params.attendees;
	this.arEvents = [];
	this.arAttendees = {};
	this.arSections = Params.sections;
	this.sectionsIds = Params.sectionsIds;
	this.type = Params.type;
	this.bSuperpose = Params.bSuperpose || false;
	this.canAddToSuperpose = this.bSuperpose && Params.canAddToSuperpose;
	this.bTasks = Params.bTasks || false;
	this.userId = Params.userId;
	this.userName = Params.userName;
	this.ownerId = Params.ownerId || false;
	this.sectionControlsDOMId = Params.sectionControlsDOMId;
	this.PERM = Params.perm;
	this.permEx = Params.permEx;
	this.settings = Params.settings;
	this.userSettings = Params.userSettings;
	this.pathToUser = Params.pathToUser;
	this.bIntranet = !!Params.bIntranet;
	this.allowMeetings = !!Params.bSocNet && this.bIntranet;
	this.allowReminders = !!Params.bSocNet && this.bIntranet;
	this.plannerSettings = Params.plannerSettings;
	this.days = Params.days;
	this.showBanner = !!Params.bShowBanner;
	this.bReadOnly = !!Params.readOnly;
	this.bAnonym = !!Params.bAnonym;
	this.startupEvent = Params.startupEvent;
	this.accessColors = Params.accessColors;
	this.initMonth = Params.init_month;
	this.initYear = Params.init_year;
	this.weekHolidays = Params.week_holidays;
	this.yearHolidays = Params.year_holidays;
	this.yearWorkdays = Params.year_workdays;
	this.new_section_access = Params.new_section_access || {};
	this.bExtranet = !!Params.bExtranet;
	this.Colors = Params.arCalColors;
	this.bAMPM = Params.bAMPM;
	this.bWideDate = Params.bWideDate;
	this.weekStart = Params.week_start;
	this.weekDays = Params.week_days;
	this.lastSection = Params.lastSection;

	this.bCalDAV = !!Params.bCalDAV;
	if (this.bCalDAV)
		this.arConnections = Params.connections;

	// Init vars
	this.arSectionsInd = {};
	this.oActiveSections = {};
	this.dayHeight = 100;
	this.darkColor = '#E6E6E6';
	this.brightColor = '#000000';
	this.arMenuItems = {};
	this.newEventUF = {};

	// Access names
	this.arNames = {};
	this.HandleAccessNames(Params.accessNames);

	var ind, sectId;
	for (ind in this.arSections)
	{
		if (this.arSections[ind].EXPORT && !this.arSections[ind].EXPORT.ALLOW)
			this.arSections[ind].EXPORT = false;
		if (!this.arSections[ind].TEXT_COLOR)
			this.arSections[ind].TEXT_COLOR = '';
		sectId = this.arSections[ind].ID;
		this.arSectionsInd[sectId] = ind;
		this.oActiveSections[sectId] = true;
	}

	this.bOnunload = false;
	this.actionUrl = Params.page;
	this.path = Params.path;
	this.bUser = this.type == 'user';
	this.meetingRooms = Params.meetingRooms || [];
	this.allowResMeeting = !!Params.allowResMeeting;
	this.allowVideoMeeting = !!Params.allowVideoMeeting;
	this.bUseMR = (this.allowResMeeting || this.allowVideoMeeting) && this.meetingRooms.length > 0;

	if (this.bTasks)
	{
		this.taskBgColor = "#F5B39A";
		this.taskTextColor = "#000000";

		//Event handlers for handle result after Adding, Editing and Deleting of tasks from popups
		BX.addCustomEvent('onCalendarPopupTaskAdded', BX.delegate(this.OnTaskChanged, this));
		BX.addCustomEvent('onCalendarPopupTaskChanged', BX.delegate(this.OnTaskChanged, this));
		BX.addCustomEvent('onCalendarPopupTaskDeleted', BX.delegate(this.OnTaskKilled, this));

		this.oActiveSections.tasks = true;// !Params.hiddenSections['tasks'];
	}

	// Set hidden sections
	for (ind in Params.hiddenSections)
		this.oActiveSections[Params.hiddenSections[ind]] = false;

	if (this.PERM.access)
		this.typeAccess = Params.TYPE_ACCESS || {};

	this.Init();
}

JCEC.prototype = {
Init: function()
{
	this.DaysTitleCont = BX(this.id + '_days_title');
	this.DaysGridCont = BX(this.id + '_days_grid');

	//Prevent selection while drag
	DenyDragEx(this.DaysGridCont);

	this.maxEventCount = 3; // max count of visible events in day
	this.activeDateDays = {};
	this._bScelTableSixRows = false;
	this.oDate = new Date();

	this.currentDate =
	{
		date: this.oDate.getDate(),
		day: this.ConvertDayIndex(this.oDate.getDay()),
		month: this.oDate.getMonth(),
		year: this.oDate.getFullYear()
	};

	this.activeDate = BX.clone(this.currentDate);

	if (this.initMonth && this.initYear)
	{
		this.activeDate.month = this.initMonth - 1;
		this.activeDate.year = this.initYear;
	}

	this.activeDate.week = this.GetWeekByDate(this.activeDate);
	this.arLoadedMonth = {};
	this.arLoadedMonth[this.activeDate.month + '.' + this.activeDate.year] = true;
	this.arLoadedEventsId = {};
	this.arLoadedParentId = {};
	this.Event = new window.JSECEvent(this);

	this.HandleEvents(this.arConfig.events, this.arConfig.attendees);

	var _this = this;

	//Days selection init
	this.selectDaysMode = false;
	this.selectDaysStartObj = false;
	this.selectDaysEndObj = false;
	this.curTimeSelection = {};
	this.curDayTSelection = {};

	this.week_holidays = {};
	for (i = 0; i < this.weekHolidays.length; i++)
		this.week_holidays[this.weekHolidays[i]] = true;

	this.year_holidays = {};
	for (i in this.yearHolidays)
		this.year_holidays[this.yearHolidays[i]] = true;

	this.year_workdays = {};
	for (i in this.yearWorkdays)
		this.year_workdays[this.yearWorkdays[i]] = true;

	window.onbeforeunload = function(){_this.bOnunload = true;};

	this.BuildSectionBlock();
	this.Selector = new ECMonthSelector(this);

	this.ColorPicker = new ECColorPicker({});

	this.BuildButtonsCont();
	this.pCalCnt.className = "bxcal";
	this.InitTabControl();

	setTimeout(function(){BX.bind(window, "resize", BX.proxy(_this.OnResize, _this))},200);

	if (this.showBanner && this.userSettings.showBanner)
		new ECBanner(this);

	if (this.arConfig.showNewEventDialog && !this.bReadOnly)
		this.ShowEditEventDialog({bChooseMR: this.arConfig.bChooseMR});
},

InitTabControl: function()
{
	this.Tabs = {};

	this.InitTab({id: 'month', tabContId: this.id + '_tab_month', bodyContId: this.id + '_scel_table_month'});
	this.InitTab({id: 'week', tabContId: this.id + '_tab_week', bodyContId: this.id + '_scel_table_week', daysCount: 7});
	this.InitTab({id: 'day', tabContId: this.id + '_tab_day', bodyContId: this.id + '_scel_table_day', daysCount: 1});

	this.SetTab(this.userSettings.tabId, true);
},

InitTab : function(arParams)
{
	var pTabCont = BX(arParams.tabContId);
	if (!pTabCont)
		return;

	var _this = this;
	pTabCont.onclick = function() {_this.SetTab(arParams.id);};

	this.Tabs[arParams.id] = {
		id : arParams.id,
		pTabCont : pTabCont,
		bodyContId : arParams.bodyContId,
		daysCount : arParams.daysCount || false,
		needRefresh: false,
		setActiveDate : false
	}
},

SetTab : function(tabId, bFirst, P)
{
	var
		_this = this,
		oTab = this.Tabs[tabId];
	if (tabId == this.activeTabId)
		return;

	var
		prevTabId = this.activeTabId,
		tblDis = '';

	if (!oTab.bLoaded || bFirst)
	{
		oTab.pBodyCont = BX(oTab.bodyContId);
		BX.bind(oTab.pBodyCont, 'click', BX.proxy(this.EventClick, this));
		DenyDragEx(oTab.pBodyCont); //Prevent selection while drag
	}

	if (this.activeTabId)
	{
		BX.removeClass(this.Tabs[this.activeTabId].pTabCont, 'bxec-tab-div-act'); // Deactivate TAB
		this.Tabs[this.activeTabId].pBodyCont.style.display = 'none'; // Hide body cont
	}

	BX.addClass(oTab.pTabCont, 'bxec-tab-div-act'); // Activate cur tab
	this.activeTabId = tabId;
	this.Selector.ChangeMode(tabId);

	if (!oTab.bLoaded || bFirst) // Called ONCE!
	{
		var
			ad = this.activeDate,
			cd = this.currentDate,
			d, w, m, y;

		if ((ad.month && ad.month != cd.month) || (ad.year && ad.year != cd.year))
		{
			d = 1;
			w = 0;
			m = ad.month;
			y = ad.year;
		}
		else
		{
			var xd = (prevTabId == 'day' && ad) ? ad : cd;
			w = this.GetWeekByDate(xd);
			d = xd.date;
			m = xd.month;
			y = xd.year;
		}

		switch (tabId)
		{
			case 'month':
				setTimeout(BX.delegate(this.BuildDaysTitle, this), 50);
				this.SetMonth(m, y);
				break;
			case 'week':
				this.BuildWeekDaysTable();
				this.SetWeek(w, m, y);
				break;
			case 'day':
				this.BuildSingleDayTable();
				if (!P || P.bSetDay !== false)
					this.SetDay(d, m, y);
				break;
		}
		oTab.bLoaded = true;
	}
	else if(!P || P.bSetDay !== false)
	{
		if (prevTabId == 'day' && tabId == 'week')
			oTab.setActiveDate = true;

		if (oTab.needRefresh)
		{
			if (tabId == 'month')
				this.DisplayEventsMonth(true);
			else
				setTimeout(function(){_this.ReBuildEvents(tabId);}, 20);
		}
		else if (oTab.setActiveDate)
		{
			switch (tabId)
			{
				case 'month':
					this.SetMonth(this.activeDate.month, this.activeDate.year);
					break;
				case 'week':
					this.SetWeek(this.GetWeekByDate(this.activeDate), this.activeDate.month, this.activeDate.year);
					break;
				case 'day':
					this.SetDay(1, this.activeDate.month, this.activeDate.year);
					break;
			}
		}
	}

	if (this.startupEvent && !this.startupEvent.viewed)
		this.ShowStartUpEvent();

	oTab.needRefresh = false;
	oTab.setActiveDate = false;

	this.Selector.Show(tabId);

	oTab.pBodyCont.style.display = tblDis; // Show tab content
	oTab.bLoaded = true;

	if (this._bScelTableSixRows)
	{
		if (this.activeTabId == 'month')
			BX.addClass(this.pCalCnt, 'BXECSceleton-six-rows');
		else
			BX.removeClass(this.pCalCnt, 'BXECSceleton-six-rows');
	}

	if (!bFirst)
		BX.userOptions.save('calendar', 'user_settings', 'tabId', tabId);
},

GetWeekByDate : function(oDate)
{
	var D1 = new Date();
	D1.setFullYear(oDate.year, oDate.month, 1); // 1'st day of month
	return Math.floor((oDate.date + this.ConvertDayIndex(D1.getDay()) - 1) / 7);
},

SetTabNeedRefresh : function(tabId, bNewDate)
{
	var i, Tab;
	for (i in this.Tabs)
	{
		Tab = this.Tabs[i];
		if (typeof Tab != 'object' || Tab.id == tabId)
			continue;
		if (!bNewDate && Tab.needRefresh === false)
			Tab.needRefresh = true;
		else if (bNewDate && Tab.setActiveDate === false)
			Tab.setActiveDate = true;
	}
},

BuildButtonsCont : function()
{
	this.ButtonsCont = BX(this.id + '_buttons_cont');
	var
		addSeparator = false,
		_this = this;

	if (!this.bReadOnly)
	{
		var
			pAddBut = this.ButtonsCont.appendChild(BX.create('SPAN', {props: {className: 'bxec-add-but', title: EC_MESS.AddNewEvent}})),
			pIcon = pAddBut.appendChild(BX.create('I')),
			pText = pAddBut.appendChild(BX.create('SPAN', {props: {}, html: EC_MESS.Add})),
			pMore = pAddBut.appendChild(BX.create('A', {props: {href: 'javascript: void(0);', className: 'bxec-add-more'}}));

		addSeparator = true;

		pIcon.onclick = pText.onclick = BX.proxy(this.ShowEditEventDialog, this);
		var arMenuItems = [];
		arMenuItems.push({
			text : EC_MESS.Event,
			title : EC_MESS.AddNewEvent,
			className : "bxec-menu-add-event",
			onclick: function(){_this.ClosePopupMenu();_this.ShowEditEventDialog();}
		});

		if (this.allowMeetings)
		{
			arMenuItems.push({
				text : EC_MESS.EventPl,
				title : EC_MESS.AddNewEventPl,
				className : "bxec-menu-add-pl",
				onclick: function(){_this.ClosePopupMenu();_this.ShowEditEventDialog({bRunPlanner: true});}
			});
		}

		if (this.bTasks)
		{
			arMenuItems.push({
				text : EC_MESS.NewTask,
				title : EC_MESS.NewTaskTitle,
				className : "bxec-menu-add-task",
				onclick: function(){_this.ClosePopupMenu();_this.Event.Edit({bTasks: true});}
			});
		}

		//if (this.type == 'user' && this.userId == this.ownerId || this.type != 'user' /* &&  .... */)
		if (this.type == 'user' && this.userId == this.ownerId || this.permEx.edit_section)
		{
			arMenuItems.push({
				text : EC_MESS.NewSect,
				title : EC_MESS.NewSectTitle,
				className : "bxec-menu-add-sect",
				onclick: function(){_this.ClosePopupMenu();_this.ShowSectionDialog();}
			});
		}

		// External - only for users calendars and only for owner
		if (this.bCalDAV && this.type == 'user' && this.userId == this.ownerId)
		{
			arMenuItems.push({
				text : EC_MESS.NewExtSect,
				title : EC_MESS.NewExtSectTitle,
				className : "bxec-menu-add-sect-ex",
				onclick: function(){_this.ClosePopupMenu();_this.ShowExternalDialog({});}
			});
		}

		pMore.onclick = function()
		{
			BX.PopupMenu.show('bxec_add_menu', pMore, arMenuItems, {events: {onPopupClose: function() {BX.removeClass(this.bindElement, "bxec-add-more-over");}}, offsetLeft: -(pAddBut.offsetWidth - 15)});
			BX.addClass(pMore, "bxec-add-more-over");
		};
	}

	if(!this.bAnonym)
	{
		// User settings
		if (addSeparator)
			this.ButtonsCont.appendChild(BX.create('SPAN', {props: {className: 'bxec-but-sep'}}));

		this.ButtonsCont.appendChild(BX.create('SPAN', {props: {className: 'bxec-settings-but', title: EC_MESS.Settings}, events: {click: BX.proxy(this.ShowSetDialog, this)}}));
	}
},

ClosePopupMenu: function()
{
	if (BX.PopupMenu && BX.PopupMenu.currentItem && BX.PopupMenu.currentItem.popupWindow)
		BX.PopupMenu.currentItem.popupWindow.close();
},

SetView : function(P)
{
	if (!bxInt(P.week) && P.week !== 0)
		P.week = this.activeDate.week;
	if (!bxInt(P.date))
		P.date = this.activeDate.date;

	switch (this.activeTabId)
	{
		case 'month':
			return this.SetMonth(P.month, P.year);
		case 'week':
			return this.SetWeek(P.week, P.month, P.year);
		case 'day':
			return this.SetDay(P.date || 1, P.month, P.year);
	}
},

SetMonth : function(m, y)
{
	if (!this.arLoadedMonth[m + '.'+ y])
		return this.LoadEvents(m, y);
	var bSetActiveDate = this.activeDate.month != m || this.activeDate.year != y;
	this.activeDate.month = m;
	this.activeDate.year = y;
	if (!this.activeDate.week)
		this.activeDate.week = 0;
	if (bSetActiveDate)
		this.SetTabNeedRefresh('month', true);

	this.Selector.OnChange(y, m);

	this.BuildDaysGrid(m, y);
},

BuildDaysTitle : function()
{
	var
		i, day,
		w = this.DaysTitleCont.offsetWidth / 7;

	w = Math.round(w * 10) / 10;
	for (i = 0; i < 7; i++)
	{
		day = this.DaysTitleCont.childNodes[i];
		day.style.width = w + 'px';

		if (i == 6)
			day.style.width = Math.abs(w - 2) + 'px';
	}

	this.DaysTitleCont.style.visibility = 'visible';
},

BuildDaysGrid : function(month, year)
{
	BX.cleanNode(this.DaysGridCont);
	var oDate = new Date();
	oDate.setFullYear(year, month, 1);

	this.activeDateDaysAr = [];
	this.activeDateDaysArO = [];
	this.arWeeks = [];

	this.oDaysGridTable = BX.create('TABLE', {props: {className : 'bxec-days-grid-table', cellPadding: 0, cellSpacing: 0}});

	if (this.GetWeekStart() != this.GetWeekDayByInd(oDate.getDay()))
		this.BuildPrevMonthDays(this.GetWeekDayByInd(oDate.getDay()), month, year);

	var date, day;
	while(oDate.getMonth() == month)
	{
		date = oDate.getDate();
		this.BuildDayCell(date, this.GetWeekDayByInd(oDate.getDay()), true, month, year);
		oDate.setDate(date + 1);
	}

	this.BuildNextMonthDays(this.GetWeekDayByInd(oDate.getDay()), month, year);

	//this.maxEventCount = this.oDaysGridTable.rows.length > 5 ? 2 : 3;

	this.DaysGridCont.appendChild(this.oDaysGridTable);
	var rowLength = this.oDaysGridTable.rows.length;
	if (rowLength == 6 && !this._bScelTableSixRows)
	{
		this._bScelTableSixRows = true;
		BX.addClass(this.pCalCnt, 'BXECSceleton-six-rows');
	}
	else if(this.pCalCnt && this._bScelTableSixRows && rowLength < 6)
	{
		this._bScelTableSixRows = false;
		BX.removeClass(this.pCalCnt, 'BXECSceleton-six-rows');
	}

	this.BuildEventHolder();
},

BuildPrevMonthDays : function(day, curMonth, curYear)
{
	var
		i,
		dayOffset = this.GetWeekDayOffset(day),
		oDate = new Date();

	oDate.setFullYear(curYear, curMonth, 1);
	oDate.setDate(oDate.getDate() - dayOffset);

	for (i = 0; i < dayOffset; i++)
	{
		this.BuildDayCell(oDate.getDate(), this.GetWeekDayByInd(oDate.getDay()), false, oDate.getMonth(), oDate.getFullYear());
		oDate.setDate(oDate.getDate() + 1);
	}
},

BuildNextMonthDays : function(day, curMonth, curYear)
{
	if (this.GetWeekStart() != day)
	{
		var i, dayOffset = this.GetWeekDayOffset(day);
		var oDate = new Date();
		oDate.setFullYear(curYear, curMonth + 1, 1);
		for (i = dayOffset; i < 7; i++)
		{
			this.BuildDayCell(oDate.getDate(), this.GetWeekDayByInd(oDate.getDay()), false, oDate.getMonth(), oDate.getFullYear());
			oDate.setDate(oDate.getDate() + 1);
		}
	}
},

BuildDayCell : function(date, day, bCurMonth, month, year)
{
	var oDay, cn, _this = this;
	if (this.GetWeekStart() == day)
		this._curRow = this.oDaysGridTable.insertRow(-1);

	var dayInd = this.activeDateDaysAr.length;

	// Make className
	//It's Holliday
	var bHol = (this.week_holidays[{MO: 0,TU: 1,WE: 2, TH: 3,FR: 4,SA: 5,SU: 6}[day]] || this.year_holidays[date + '.' + month]) && !this.year_workdays[date + '.' + month];

	cn = 'bxec-day';
	if (!bCurMonth && !bHol)
		cn += ' bxec-day-past';
	else if(!bCurMonth)
		cn += ' bxec-day-past-hol';
	else if (bHol)
		cn += ' bxec-holiday';

	if (date == this.currentDate.date && month == this.currentDate.month && year == this.currentDate.year)
		cn += ' bxec-current-day';

	oDay = this._curRow.insertCell(-1);
	oDay.id = 'bxec_ind_' + dayInd;
	oDay.className = cn;

	var
		dayCont = oDay.appendChild(BX.create('DIV', {props: {className: 'bxc-day'}, style: {height: this.dayHeight + 'px'}})),
		title = dayCont.appendChild(BX.create('DIV', {props: {className: 'bxc-day-title'}})),
		link = title.appendChild(BX.create('A', {props: {href: 'javascript:void(0)', className: 'bxc-day-link', title: EC_MESS.GoToDay, id: 'bxec-day-lnk-' + dayInd}, html: date}));

	link.onmousedown = function(e){return BX.PreventDefault(e);};
	link.onclick = function(e)
	{
		var date = _this.activeDateDaysAr[this.id.substr('bxec-day-lnk-'.length)];
		_this.SetTab('day', false, {bSetDay: false});
		_this.SetDay(date.getDate(), date.getMonth(), date.getFullYear());
		return BX.PreventDefault(e);
	};

	if (this.GetWeekDayOffset(day) == 6) // Layout hack
		oDay.style.borderRight = '0px';

	if (!this.bReadOnly)
	{
		oDay.onmouseover = function(){_this.oDayOnMouseOver(this);};
		oDay.onmousedown = function(){_this.oDayOnMouseDown(this)};
		oDay.onmouseup = function() {_this.oDayOnMouseUp(this)};
	}

	this.activeDateDaysAr.push(new Date(year, month, date));
	this.activeDateDaysArO.push(
	{
		pDiv: oDay,
		pDayCont: dayCont,
		arEvents: {begining : [], all : []}
	});
},

oDayOnMouseOver : function(pDay)
{
	if (this.selectDaysMode)
	{
		this.selectDaysEndObj = pDay;
		this.SelectDays();
	}
},

oDayOnMouseDown : function(pDay)
{
	this.selectDaysMode = true;
	this.selectDaysStartObj = this.selectDaysEndObj = pDay;
	if (pDay.className.indexOf('bxec-day-selected') == -1)
		return this.SelectDays();
	this.selectDaysMode = false;
	this.DeSelectDays();
	this.CloseAddEventDialog();
},

oDayOnMouseUp : function(pDay)
{
	if (!this.selectDaysMode)
		return;
	this.selectDaysEndObj = pDay;
	this.SelectDays();

	this.ShowAddEventDialog();

	this.selectDaysMode = false;
},

oDayOnDoubleClick : function(pDay) {},
oDayOnContextMenu : function(pDay) {},

RefreshEventsOnWeeks : function(arWeeks)
{
	for (var i = 0, l = arWeeks.length; i < l; i++)
		this.RefreshEventsOnWeek(arWeeks[i]);
},

RefreshEventsOnWeek : function(ind)
{
	var
		startDayInd = ind * 7,
		endDayInd = (ind + 1) * 7,
		day, i, arEv, j, ev, arAll, displ, arHid,
		slots = [],
		step = 0;

	for(j = 0; j < this.maxEventCount; j++)
		slots[j] = 0;

	for (i = startDayInd; i < endDayInd; i++)
	{
		day = this.activeDateDaysArO[i];

		if (!day)
			continue;
		day.arEvents.hidden = [];
		arEv = day.arEvents.begining;
		arHid = [];

		if (arEv.length > 0)
		{
			arEv.sort(function(a, b)
			{
				if (b.daysCount == a.daysCount && a.daysCount == 1)
					return a.oEvent.DT_FROM_TS - b.oEvent.DT_FROM_TS;
				return b.daysCount - a.daysCount;
			});

			eventloop:
			for(k = 0; k < arEv.length; k++)
			{
				ev = arEv[k];
				if (!ev)
					continue;

				if (!this.arEvents[ev.oEvent.ind])
				{
					day.arEvents.begining = arEv = BX.util.deleteFromArray(arEv, k);
					ev = arEv[k];
					if (!ev)
						continue; //break ?
				}

				for(j = 0; j < this.maxEventCount; j++)
				{
					if (slots[j] - step <= 0)
					{
						slots[j] = step + ev.daysCount;
						this.ShowEventOnLevel(ev.oEvent.oParts[ev.partInd], j, ind);
						continue eventloop;
					}
				}
				arHid[ev.oEvent.ID] = true;
				day.arEvents.hidden.push(ev);
			}
		}
		// For all events in the day
		arAll = day.arEvents.all;
		for (var x = 0; x < arAll.length; x++)
		{
			ev = arAll[x];
			if (!ev || arHid[ev.oEvent.ID])
				continue;
			if (!this.arEvents[ev.oEvent.ind])
			{
				day.arEvents.all = arAll = BX.util.deleteFromArray(arAll, x);
				ev = arAll[x];
				if (!ev)
					continue;
			}

			if (ev.oEvent.oParts && ev.partInd != undefined && ev.oEvent.oParts[ev.partInd] && ev.oEvent.oParts[ev.partInd].style.display == 'none')
				day.arEvents.hidden.push(ev);
		}
		this.ShowMoreEventsSelect(day);
		step++;
	}
},

ShowEventOnLevel : function(pDiv, level, week)
{
	if (!this.arWeeks[week])
		this.arWeeks[week] = {top: parseInt(this.oDaysGridTable.rows[week].cells[0].offsetTop) + 22};

	var top = this.arWeeks[week].top + level * 18;
	pDiv.style.display = 'block';
	pDiv.style.top = top + 'px';
},

ShowMoreEventsSelect : function(oDay)
{
	var
		arEv = oDay.arEvents.hidden,
		l = arEv.length;

	if (arEv.length <= 0)
	{
		if(oDay.pMoreDiv)
			oDay.pMoreDiv.style.display = 'none';
		return; // Exit
	}

	if (!oDay.pMoreDiv)
		oDay.pMoreDiv = oDay.pDayCont.appendChild(BX.create('DIV', {props: {className: 'bxc-day-more'}}));

	var
		_this = this,
		i, el, part, arHidden = [];

	for (i = 0; i < arEv.length; i++)
	{
		el = arEv[i];
		part = el.oEvent.oParts[el.partInd];
		part.style.display = "none"; // Hide event from calendar grid

		if (!el.oEvent.pMoreDivs)
			el.oEvent.pMoreDivs = [];
		el.oEvent.pMoreDivs.push(oDay.pMoreDiv);
		arHidden.push({pDiv: part, oEvent: el.oEvent});
	}

	BX.adjust(oDay.pMoreDiv, {
		style: {display: 'block'},
		html: EC_MESS.MoreEvents + ' (' + arHidden.length + ' ' + EC_MESS.Item + ')'
	});

	oDay.pMoreDiv.onmousedown = function(e){if(!e) e = window.event; BX.PreventDefault(e);};
	oDay.pMoreDiv.onclick = function(){_this.ShowMoreEventsWin({Events: arHidden, id: oDay.pDiv.id, pDay: oDay.pDiv, pSelect: oDay.pMoreDiv});};
},

SelectDays : function()
{
	if (!this.arSelectedDays)
		this.arSelectedDays = [];
	this.bInvertedDaysSelection = false;

	if (this.arSelectedDays.length > 0)
		this.DeSelectDays();

	if (!this.selectDaysStartObj || !this.selectDaysEndObj)
		return;

	var
		start_ind = parseInt(this.selectDaysStartObj.id.substr(9)),
		end_ind = parseInt(this.selectDaysEndObj.id.substr(9)),
		el, i, _a;

	if (start_ind > end_ind) // swap start_ind and end_ind
	{
		_a = end_ind;
		end_ind = start_ind;
		start_ind = _a;
		this.bInvertedDaysSelection = true;
	}

	for (i = start_ind; i <= end_ind; i++)
	{
		el = this.activeDateDaysArO[i];
		if (!el || !el.pDiv)
			continue;
		BX.addClass(el.pDiv, 'bxec-day-selected');
		this.arSelectedDays.push(el.pDiv);
	}
},

DeSelectDays : function()
{
	if (!this.arSelectedDays)
		return;
	var el, i, l;
	for (i = 0, l = this.arSelectedDays.length; i < l; i++)
		BX.removeClass(this.arSelectedDays[i], 'bxec-day-selected');
	this.arSelectedDays = [];
},

DisplayError : function(str, bReloadPage)
{
	var _this = this;
	setTimeout(function(){
		if (!_this.bOnunload)
		{
			alert(str || '[Event Calendar] Error!');
			if (bReloadPage)
				window.location = window.location;
		}
	}, 200);
},

BuildSectionBlock : function()
{
	this.oSections = {};

	var bMove = (this.sectionControlsDOMId && (this.pSidebar = BX(this.sectionControlsDOMId)));
	this.pSectCont = BX(this.id + '_sect_cont');

	if (!this.pSectCont)
		return;

	if (bMove)
	{
		if (this.pSidebar.firstChild)
			this.pSidebar.insertBefore(this.pSectCont, this.pSidebar.firstChild);
		else
			this.pSidebar.appendChild(this.pSectCont);

		BX.addClass(this.pSectCont, "bxec-sect-cont-side");
		this.OnResize(350);
	}
	else
	{
		BX.addClass(this.pSectCont, "bxec-sect-cont-top");
	}

	var _this = this;
	setTimeout(function(){_this.pSectCont.style.display = "block";}, 200);
	if (this.arSections.length < 1 && this.bReadOnly)
		return;

	this.pOwnerSectCont = BX(this.id + 'sections');
	if(this.pOwnerSectCont)
	{
		this.pOwnerSectCont.onmouseover = function(){if(_this._sect_over_timeout){clearInterval(_this._sect_over_timeout);} BX.addClass(_this.pOwnerSectCont, 'bxec-hover');};
		this.pOwnerSectCont.onmouseout = function(){_this._sect_over_timeout = setTimeout(function(){BX.removeClass(_this.pOwnerSectCont, 'bxec-hover');}, 100);};
	}
	this.pOwnerSectBlock = BX(this.id + 'sections-cont');

	if (!this.pOwnerSectBlock)
		return;

	BX.cleanNode(this.pOwnerSectBlock);
	this.pOwnerSectBlock.style.display = '';

	// Prepare block for superposed sections
	if (this.bSuperpose)
	{
		this.pSPSectCont = BX(this.id + 'sp-sections');
		this.pSPSectCont.onmouseover = function(){if(_this._sect_over_timeout){clearInterval(_this._sect_over_timeout);} BX.addClass(_this.pSPSectCont, 'bxec-hover');};
		this.pSPSectCont.onmouseout = function(){_this._sect_over_timeout = setTimeout(function(){BX.removeClass(_this.pSPSectCont, 'bxec-hover');}, 100);};

		this.pSpSectBlock = BX(this.id + 'sp-sections-cont');

		var pManageSPBut = BX(this.id + '-manage-superpose');
		pManageSPBut.onclick = function(){_this.ShowSuperposeDialog()};
	}

	this.BuildSectionElements();

	var pAddSectBut = BX(this.id + '-add-section');
	//if (this.Personal() || !this.bReadOnly)
	if (this.Personal() || this.permEx.section_edit)
	{
		if (pAddSectBut)
			pAddSectBut.onclick = function(){_this.ShowSectionDialog();};
	}
	else
	{
		if (pAddSectBut)
			BX.cleanNode(pAddSectBut, true);
	}
},

BuildSectionElements : function()
{
	var
		bShowOwnerSection = false,
		bShowSuperpose = false,
		i, l = this.arSections.length, oSect;

	for (i = 0; i < l; i++)
	{
		oSect = this.arSections[i];
		if (!oSect.DOM)
			oSect.DOM = {}

		// Add to owner's sections only if section added first time
		if (!this.bSuperpose || (oSect.CAL_TYPE == this.type && oSect.OWNER_ID == this.ownerId))
		{
			if (oSect.DOM.pEl)
				this.BuildSectionMenu(oSect.ID);
			else
				this.BuildSectionElement(oSect, this.oActiveSections[oSect.ID]);

			if (!bShowOwnerSection)
				bShowOwnerSection = true;
		}

		// Add to superposed section
		if (this.bSuperpose)
		{
			// Add to superpose block
			if (oSect.SUPERPOSED)
			{
				if (oSect.DOM.pSPEl)
					this.BuildSectionMenu(oSect.ID, true);
				else
					this.BuildSectionElement(oSect, this.oActiveSections[oSect.ID], true);
			}
			// Section was superposed, but now we have to remove it from superposed
			else if(!oSect.SUPERPOSED && oSect.DOM.pSPEl)
			{
				// Clean DOM and vars
				if (oSect.DOM.pSPEl.parentNode)
					oSect.DOM.pSPEl.parentNode.removeChild(oSect.DOM.pSPEl);

				var menuId = 'bxec-sect-sp-' + oSect.ID;
				if (this.arMenuItems[menuId])
				{
					if (BX.PopupMenu.Data[menuId])
					{
						BX.PopupMenu.Data[menuId].popupWindow.destroy();
						BX.PopupMenu.Data[menuId] = false;
					}
					this.arMenuItems[menuId] = null;
					delete this.arMenuItems[menuId];
				}

				oSect.DOM.pSPEl = oSect.DOM.pSPWrap = oSect.DOM.pSPText = null;
				delete oSect.DOM.pSPEl;
				delete oSect.DOM.pSPWrap;
				delete oSect.DOM.pSPText;
			}

			if (oSect.SUPERPOSED && !bShowSuperpose)
				bShowSuperpose = true;
		}
	}

	if (this.bTasks && !this.oSections['tasks'])
	{
		bShowOwnerSection = true;
		this.BuildSectionElement({
			ID: 'tasks',
			CAL_TYPE : 'user',
			COLOR : this.taskBgColor,
			CREATED_BY : this.userId,
			DESCRIPTION : EC_MESS.MyTasks,
			DOM : {},
			NAME : EC_MESS.MyTasks,
			OWNER_ID : this.userId,
			PERM : {},
			SORT : 100,
			SUPERPOSED : false,
			TEXT_COLOR : this.taskTextColor
		}, this.oActiveSections.tasks);
	}

	if (this.pSPSectCont)
		this.pSPSectCont.style.display = bShowSuperpose ? "" : "none";

	this.pOwnerSectCont.style.display = bShowOwnerSection ? "" : "none";

	return true;
},

BuildSectionElement : function(el, bChecked, bSuperpose)
{
	bSuperpose = !!bSuperpose;
	if (!el.DOM)
		el.DOM = {};

	// Determine container
	var
		isGoogle = el.CAL_DAV_CAL && el.CAL_DAV_CON,
		isTask = this.bTasks && el.ID == 'tasks',
		pCont = this.pOwnerSectBlock;

	if (bSuperpose) // Superposed
	{
		pCont = this.pSpSectBlock;
	}
	else
	{
		if (isTask) // My tasks
		{
			pCont = BX(this.id + 'tasks-sections-cont');
		}
		else
		{
			if (!this.pSectSubCont)
				this.pSectSubCont = this.pOwnerSectBlock.appendChild(BX.create("DIV"));
			pCont = this.pSectSubCont;
		}
	}

	el.bDark = this.ColorIsDark(el.COLOR);
	var
		_this = this,
		menu = [],
		menuId = 'bxec-sect-' + (bSuperpose ? 'sp-' : '') + el.ID,
		//bActive = !this.bReadOnly || el.EXPORT,
		pEl = pCont.appendChild(BX.create('DIV', {props: {id: 'el-' + menuId, className: 'bxec-sect-el'}})),
		pWrap = pEl.appendChild(BX.create("DIV", {props: {className: 'bxec-sect-el-wrap'  + (isTask ? ' bxec-task-el-wrap' : '')}})),
		pCh = pWrap.appendChild(BX.create("SPAN", {props: {className: 'bxec-spr bxec-checkbox'}}));

	if(isTask)
		pWrap.appendChild(BX.create("SPAN", {props: {className: 'bxec-spr bxec-tasks-sect'}}));

	if (isGoogle)
		pWrap.appendChild(BX.create("SPAN", {props: {className: 'bxec-spr bxec-cal-dav-google'}}));

	var
		pText = pWrap.appendChild(BX.create("DIV", {text: el.NAME, props: {className: 'bxc-sect-text-wrap'}})),
		pMenu = pEl.appendChild(BX.create("A", {props: {id: menuId, href: "javascript: void(0);", className: 'bxec-spr bxec-sect-menu', hidefocus: true}}));

	pMenu.onclick = function(e){_this.ShowCPopup(this.id, this);return BX.PreventDefault(e);};

	if (bSuperpose)  // For superposed
	{
		el.DOM.pSPEl = pEl;
		el.DOM.pSPWrap = pWrap;
		el.DOM.pSPText = pText;
	}
	else
	{
		el.DOM.pEl = pEl;
		el.DOM.pWrap = pWrap;
		el.DOM.pText = pText;
	}

	pEl.onclick = function() {_this.ShowCalendar(el, this.className.indexOf('bxec-sect-el-checked') == -1);};
	this.oSections[el['ID']] = el;
	this.oActiveSections[el['ID']] = bChecked;
	this.BuildSectionMenu(el['ID'], bSuperpose);

	this.ShowCalendar(el, bChecked, true);
},

BuildSectionMenu : function(sectionId, bSuperpose)
{
	var el = this.oSections[sectionId];

	if (!el || (this.bTasks && el.ID == 'tasks'))
		return false;

	var
		_this = this,
		menu = [],
		isGoogle = el.CAL_DAV_CAL && el.CAL_DAV_CON,
		pEl = bSuperpose ? el.DOM.pSPEl : el.DOM.pEl,
		menuId = 'bxec-sect-' + (bSuperpose ? 'sp-' : '') + el.ID;

	if (BX.PopupMenu.Data[menuId] && BX.PopupMenu.Data[menuId].popupWindow)
	{
		BX.PopupMenu.Data[menuId].popupWindow.destroy();
		BX.PopupMenu.Data[menuId] = false;
	}

	if (el.PERM.edit_section && !isGoogle && !bSuperpose)
	{
		menu.push({
			text : EC_MESS.Edit,
			title : EC_MESS.EditCalendarTitle,
			className : "bxec-menu-sect-edit",
			onclick: function(){_this.CloseCPopup();_this.ShowSectionDialog(el);}
		});
	}

	if (!el.SUPERPOSED && this.canAddToSuperpose)
	{
		menu.push({
			text : EC_MESS.CalAdd2SP,
			title : EC_MESS.CalAdd2SPTitle,
			className : "bxec-menu-sect-add2sp",
			onclick: function(){_this.CloseCPopup();_this.SetSuperposed(el, true);}
		});
	}
	else if(el.SUPERPOSED)
	{
		menu.push({
			text : EC_MESS.CalHide,
			title : EC_MESS.CalHideTitle,
			className : "bxec-menu-sect-del-from-sp",
			onclick: function(){_this.CloseCPopup();_this.SetSuperposed(el, false);}
		});
	}

	if (el.OUTLOOK_JS  && !isGoogle)
	{
		menu.push({
			text : EC_MESS.ConnectToOutlook,
			title : EC_MESS.ConnectToOutlookTitle,
			className : "bxec-menu-sect-outlook",
			onclick: function(){
				_this.CloseCPopup();
				if (!window.jsOutlookUtils)
					BX.loadScript('/bitrix/js/calendar/outlook.js', function(){try{eval(el.OUTLOOK_JS);}catch(e){}});
				else
					try{eval(el.OUTLOOK_JS);}catch(e){};
			}
		});
	}

	if (el.EXPORT && el.EXPORT.ALLOW)
	{
		menu.push({
			text : EC_MESS.Export,
			title : EC_MESS.ExportTitle,
			className : "bxec-menu-sect-export",
			onclick: function(){_this.CloseCPopup();_this.ShowExportDialog(el);}
		});
	}

	if (el.PERM.edit_section && !isGoogle  && !bSuperpose)
	{
		menu.push({
			text : EC_MESS.Delete,
			title : EC_MESS.DelCalendarTitle,
			className : "bxec-menu-sect-del",
			onclick: function(){_this.CloseCPopup();_this.DeleteSection(el);}
		});
	}

	if (isGoogle  && !bSuperpose)
	{
		menu.push({
			text : EC_MESS.Refresh,
			className : "bxec-menu-sect-edit",
			onclick: function(){
				_this.CloseCPopup();
				_this.bSyncGoogle = true;
				_this.Event.ReloadAll();
			}
		});

		if (el.PERM.edit_section)
			menu.push({
				text : EC_MESS.Adjust,
				title : EC_MESS.CalDavDialogTitle,
				className : "bxec-menu-sect-edit",
				onclick: function(){_this.CloseCPopup();_this.ShowExternalDialog({});}
			});
	}

	this.arMenuItems[menuId] = menu;

	if (menu.length > 0)
	{
		pEl.onmouseover = function(){if(_this['_sect_el_over_timeout' + this.id]){clearInterval(_this['_sect_el_over_timeout' + this.id]);} BX.addClass(pEl, 'bxec-sect-el-hover');};
		pEl.onmouseout = function(){_this['_sect_el_over_timeout' + this.id] = setTimeout(function(){BX.removeClass(pEl, 'bxec-sect-el-hover');}, 100);};
	}
	else
	{
		pEl.onmouseover = BX.False;
		pEl.onmouseout = BX.False;
	}
},

ShowCPopup: function(menuId, pEl)
{
	if (this.arMenuItems[menuId])
	{
		BX.PopupMenu.show(menuId, pEl, this.arMenuItems[menuId], {events: {onPopupClose: function(){BX.removeClass(this.bindElement, "bxec-menu-over");}}});
		BX.addClass(pEl, "bxec-menu-over");
	}
},

CloseCPopup: function()
{
	BX.PopupMenu.currentItem.popupWindow.close();
},

ColorIsDark: function(color)
{
	if (!color)
		return false;

	if (color.charAt(0) == "#")
		color = color.substring(1, 7);
	var
		r = parseInt(color.substring(0, 2), 16),
		g = parseInt(color.substring(2, 4), 16),
		b = parseInt(color.substring(4, 6), 16),
		light = (r * 0.8 + g + b * 0.2) / 510 * 100;
	return light < 50;
},

AppendCalendarHint: function(el, bSuperpose)
{
	if (el.oHint && el.oHint.Destroy)
		el.oHint.Destroy();

	//append Hint
	var hintContent;
	if (bSuperpose && el.SP_PARAMS)
		hintContent = '<b>' + el.SP_PARAMS.GROUP_TITLE + ' > ' + el.SP_PARAMS.NAME + ' > ' + el.NAME + '</b>';
	else
		hintContent = '<b>' + el.NAME + '</b>';

	var desc_len = el.DESCRIPTION.length, max_len = 350;
	if (desc_len > 0)
	{
		if (desc_len < max_len)
			hintContent += "<br>" + el.DESCRIPTION;
		else
			hintContent += "<br>" + el.DESCRIPTION.substr(0, max_len) + '...';
	}

	el.oHint = new BX.CHintSimple({parent: el._pElement, hint: hintContent});
},

ShowCalendar : function(el, bShow, bDontReload, bEffect2Bro)
{
	if (!el)
		return;

	if (bShow)
	{
		if (el.DOM.pWrap)
			el.DOM.pWrap.style.backgroundColor = el.COLOR;

		// text color
		var txtColor = el.TEXT_COLOR;
		if (!txtColor)
			txtColor = el.bDark ? this.darkColor : this.brightColor;
		if (el.DOM.pText)
			el.DOM.pText.style.color = txtColor;
		if (el.DOM.pEl)
			BX.addClass(el.DOM.pEl, 'bxec-sect-el-checked');

		// For superposed
		if (el.DOM.pSPEl)
			BX.addClass(el.DOM.pSPEl, 'bxec-sect-el-checked');
		if (el.DOM.pSPText)
			el.DOM.pSPText.style.color = txtColor;
		if (el.DOM.pSPWrap)
			el.DOM.pSPWrap.style.backgroundColor = el.COLOR;
	}
	else
	{
		if (el.DOM.pEl)
			BX.removeClass(el.DOM.pEl, 'bxec-sect-el-checked');
		if (el.DOM.pWrap)
			el.DOM.pWrap.style.backgroundColor = 'transparent';
		if (el.DOM.pText)
			el.DOM.pText.style.color = '#484848';

		// For superposed
		if (el.DOM.pSPEl)
			BX.removeClass(el.DOM.pSPEl, 'bxec-sect-el-checked');
		if (el.DOM.pSPWrap)
			el.DOM.pSPWrap.style.backgroundColor = 'transparent';
		if (el.DOM.pSPText)
			el.DOM.pSPText.style.color = '#484848';
	}
	this.oActiveSections[el.ID] = el.bShowed = !!bShow

	if (!bDontReload)
	{
		this.SetTabNeedRefresh(this.activeTabId);
		this.Event.ReloadAll();
	}
},

SaveSection : function()
{
	var
		D = this.oSectDialog,
		oSect = D.CAL.oSect;

	D.CAL.DOM.Name.value = BX.util.trim(D.CAL.DOM.Name.value);
	if (D.CAL.DOM.Name.value == "")
	{
		alert(EC_MESS.CalenNameErr);
		this.bEditCalDialogOver = true;
		return false;
	}

	var postData = this.GetReqData('section_edit', {
		name : D.CAL.DOM.Name.value,
		desc : D.CAL.DOM.Desc.value,
		//color : D.CAL.DOM.Color.value
		color : D.CAL.Color,
		text_color : D.CAL.TextColor
	});

	if (D.CAL.Access)
		postData.access = D.CAL.Access.GetValues();

	if (oSect.ID)
		postData.id = bxInt(oSect.ID);

	if (D.CAL.DOM.Exch)
		postData.is_exchange = D.CAL.DOM.Exch.checked ? 'Y' : 'N';

	//if (this.bUser)
	//	postData.private_status = D.CAL.DOM.Status.value;

	if (this.bUser && this.Personal() && D.CAL.DOM.MeetingCalendarCh.checked)
		postData.is_def_meet_calendar = 'Y';

	if (D.CAL.DOM.ExpAllow.checked)
	{
		postData['export'] = 'Y';
		postData.exp_set = D.CAL.DOM.ExpSet ? D.CAL.DOM.ExpSet.value : 'all';
	}

	var _this = this;
	this.Request({
		postData: postData,
		errorText: EC_MESS.CalenSaveErr,
		handler: function(oRes)
		{
			if (oRes && oRes.calendar && oRes.calendar.ID)
			{
				if (oRes.accessNames)
					_this.HandleAccessNames(oRes.accessNames);

				_this.SaveSectionClientSide(oRes.calendar);
				if (_this.bUser &&  _this.Personal() && _this.oSectDialog.CAL.DOM.MeetingCalendarCh.checked && _this.userSettings.meetSection != oRes.calendar.ID)
				{
					_this.userSettings.meetSection = oRes.calendar.ID;
					_this.Event.ReloadAll();
				}
				return true;
			}
			return false;
		}
	});
	return true;
},

SaveSectionClientSide : function(oSect)
{
	if (oSect.EXPORT && !oSect.EXPORT.ALLOW)
		oSect.EXPORT = false;

	// It's new sections
	if (typeof this.arSectionsInd[oSect.ID] == 'undefined')
	{
		this.arSections.push(oSect);
		this.arSectionsInd[oSect.ID] = this.arSections.length - 1;
		this.BuildSectionElement(oSect, true);
		// Feature - we set new section as default for new events.
		this.SaveLastSection(oSect.ID);

		if (this.bSuperpose && this.oSectDialog.CAL.DOM.add2SP)
			this.SetSuperposed(oSect, (!this.oSectDialog || this.oSectDialog.CAL.DOM.add2SP.checked));
	}
	else // Save and update section
	{
		var
			key,
			exSect = this.arSections[this.arSectionsInd[oSect.ID]];
			bCol = !exSect || oSect.COLOR != exSect.COLOR || oSect.TEXT_COLOR != exSect.TEXT_COLOR;

		if (!exSect)
			return;

		// Copy all properties
		for (key in oSect)
			exSect[key] = oSect[key];

		// Rename
		exSect.DOM.pText.innerHTML = BX.util.htmlspecialchars(exSect.NAME);
		if (exSect.DOM.pSPText)
			exSect.DOM.pSPText.innerHTML = BX.util.htmlspecialchars(exSect.NAME);
		exSect.bDark = this.ColorIsDark(exSect.COLOR);

		//if (this.bSuperpose && this.oSectDialog.CAL.DOM.add2SP)
		this.BuildSectionMenu(oSect.ID);
		if (exSect.DOM.pSPEl)
			this.BuildSectionMenu(oSect.ID, true);

		this.UpdateSectionColor(exSect);
		this.ShowCalendar(exSect, exSect.bShowed, true);
	}
},

DeleteSection : function(el)
{
	if (!el.ID || !confirm(EC_MESS.DelCalendarConfirm))
		return false;
	var _this = this;
	this.Request({
		getData: this.GetReqData('section_delete', {id : el.ID}),
		errorText: EC_MESS.DelCalendarErr,
		handler: function(oRes)
		{
			return oRes.result ? _this.DeleteSectionClientSide(el) : false;
		}
	});

	return true;
},

DeleteSectionClientSide : function(oSect)
{
	BX.cleanNode(oSect.DOM.pEl, true);

	if (oSect.DOM.pSPEl)
		BX.cleanNode(oSect.DOM.pSPEl, true);

	var i, l = this.arSections.length;
	for (i = 0; i < l; i++)
	{
		if (this.arSections[i].ID == oSect.ID)
		{
			this.arSections = BX.util.deleteFromArray(this.arSections, i);
			break;
		}
	}

	delete this.oActiveSections[oSect.ID];
	delete this.oSections[oSect.ID];
	this.Event.ReloadAll();
},

UpdateSectionColor : function(oSect)
{
	if (!oSect)
		return;

	var
		color = oSect.COLOR,
		txtColor = oSect.TEXT_COLOR ? oSect.TEXT_COLOR : (oSect.bDark ? this.darkColor : this.brightColor);

	oSect.DOM.pWrap.style.backgroundColor = color;
	oSect.DOM.pText.style.color = txtColor;

	var
		keys = [['oTLParts', 'week'], ['oTLParts', 'day'], ['oDaysT', 'week'], ['oDaysT', 'day']],
		i, l = this.arEvents.length, ev, j, n, x, y;

	for (i = 0; i < l; i++)
	{
		ev = this.arEvents[i];
		if (!ev)
			continue;
		if (ev.SECT_ID != oSect.ID)
			continue;

		// Month
		n = ev.oParts.length;
		for (j = 0; j < n; j++)
		{
			ev.oParts[j].style.backgroundColor = color;
			ev.oParts[j].style.color = txtColor;
		}

		n = keys.length;
		for (j = 0; j < n; j++)
		{
			if (ev[keys[j][0]] && ev[keys[j][0]][keys[j][1]])
			{
				y = ev[keys[j][0]][keys[j][1]];
				if (typeof y == 'object' && y.nodeType)
				{
					y.style.backgroundColor = color;
					y.style.color = txtColor;
				}
				else
				{
					for (x = 0; x < y.length; x++)
					{
						y[x].style.backgroundColor = color;
						y[x].style.color = txtColor;
					}
				}
			}
		}
		ev.displayColor = color;
	}
},

InitCalBarGlobChecker : function(bSP)
{
	return;
	var id, GlCh;
	if (bSP)
	{
		id = this.id + '_sp_cal_bar_check';
		GlCh = 'CalBarGlobCheckerSP';
	}
	else
	{
		id = this.id + '_cal_bar_check';
		GlCh = 'CalBarGlobChecker';
	}

	this[GlCh] = {};
	this[GlCh].pWnd = BX(id);

	this[GlCh].flag = false; //
	this[GlCh].pWnd.title = EC_MESS.DeSelectAll; //

	var _this = this;
	this[GlCh].pWnd.onclick = function()
	{
		if (_this[GlCh].flag) // Show
		{
			_this[GlCh].flag = false;
			_this.ShowAllCalendars(true, bSP);
			_this[GlCh].pWnd.className = 'bxec-iconkit bxec-cal-bar-check';
			_this[GlCh].pWnd.title = EC_MESS.DeSelectAll;
		}
		else // Hide
		{
			_this[GlCh].flag = true;
			_this.ShowAllCalendars(false, bSP);
			_this[GlCh].pWnd.className = 'bxec-iconkit bxec-cal-bar-uncheck';
			_this[GlCh].pWnd.title = EC_MESS.SelectAll;
		}

	};
},

ShowAllCalendars : function(bShow, bSP)
{
	var arCals = bSP ? this.arSPCalendarsShow : this.arSections;
	var i, l = arCals.length;
	for (i = 0; i < l; i++)
	{
		el = arCals[i];
		this.ShowCalendar(el, bShow, true, !bSP);
	}
	this.Event.ReloadAll();
},

CheckCalBarGlobChecker : function(bCheck, bSP)
{
	var GlCh = bSP ? 'CalBarGlobCheckerSP' : 'CalBarGlobChecker';

	if (bCheck == 'none')
	{
		this[GlCh].pWnd.className = 'bxec-cal-bar-none';
		this[GlCh].pWnd.title = '';
	}
	else if (bCheck)
	{

		this[GlCh].flag = false;
		this[GlCh].pWnd.className = 'bxec-iconkit bxec-cal-bar-check';
		this[GlCh].pWnd.title = EC_MESS.DeSelectAll;
	}
	else
	{
		this[GlCh].flag = true;
		this[GlCh].pWnd.className = 'bxec-iconkit bxec-cal-bar-uncheck';
		this[GlCh].pWnd.title = EC_MESS.SelectAll;
	}
},

// * * * *  * * * *  * * * * SUPERPOSED CALENDARS, EVENTS  * * * *  * * * *  * * * *
SetSuperposed : function(oSect, bAdd)
{
	if(oSect)
		oSect.SUPERPOSED = !!bAdd;

	var
		_this = this, i, arSPIds = [];

	for (i = 0; i < this.arSections.length; i++)
		if (this.arSections[i].SUPERPOSED)
			arSPIds.push(parseInt(this.arSections[i].ID));

	this.Request({
		getData: this.GetReqData('set_superposed', {sect: arSPIds, trackedUser: oSect && oSect.CAL_TYPE == 'user' ? oSect.OWNER_ID : 0}),
		errorText: EC_MESS.AppendSPCalendarErr,
		handler: function(res)
		{
			if (res.result)
				return _this.BuildSectionElements();
			return  false;
		}
	});
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

GetCenterWindowPos : function(w, h)
{
	if (!w) w = 400;
	if (!h) h = 300;
	var S = BX.GetWindowSize(document);
	var top = bxInt(bxInt(S.scrollTop) + (S.innerHeight - h) / 2 - 30);
	var left = bxInt(bxInt(S.scrollLeft) + (S.innerWidth - w) / 2 - 30);
	return {top: top, left: left};
},

ShowWaitWindow : function()
{
	BX.showWait(this.pCalCnt);
},

CloseWaitWindow : function()
{
	BX.closeWait(this.pCalCnt);
},

ShowStartUpEvent : function()
{
	for (var i = 0; i < this.arEvents.length; i++)
	{
		if (this.startupEvent.ID == this.arEvents[i].ID)
		{
			var _this = this;
			setTimeout(function(){_this.Event.View(_this.arEvents[i]);}, 50);
			this.startupEvent.viewed = true;
			return;
		}
	}
},

InitFliper : function(pFliper, strCont)
{
	return;
	var
		_this = this,
		td = pFliper.parentNode,
		tr = _this[strCont].parentNode.parentNode,
		tbl = BX.findParent(tr, {tagName: 'TABLE'}),
		flag = 'b' + strCont + 'Hidden';

	td.title = EC_MESS.FlipperHide;
	_this[flag] = this.arConfig.Settings[strCont];
	var Hide = function(flag)
	{
		if (_this[flag])
		{
			pFliper.className = 'bxec-iconkit bxec-hide-arrow';
			tbl.style.width = null;
			tr.style.display = BX.browser.IsIE() ? 'inline' : 'table-row';
			td.title = EC_MESS.FlipperHide;
		}
		else
		{
			pFliper.className = 'bxec-iconkit bxec-show-arrow';
			tbl.style.width = tbl.offsetWidth + 'px';
			tr.style.display = 'none';
			td.title = EC_MESS.FlipperShow;
		}
		_this[flag] = !_this[flag];
	};
	td.onclick = function() {Hide(flag); _this.SaveSettings();};
	if (_this[flag])
	{
		_this[flag] = false;
		Hide(flag);
	}
},

SaveSettings : function()
{
	var D = this.oSetDialog;

	// Save user settings
	if (D.CAL.inPersonal)
	{
		this.userSettings.blink = D.CAL.DOM.Blink.checked ? 1 : 0;
		this.userSettings.showBanner = D.CAL.DOM.ShowBanner.checked ? 1 : 0;
		this.userSettings.showDeclined = D.CAL.DOM.ShowDeclined.checked ? 1 : 0;
		this.userSettings.meetSection = D.CAL.DOM.SectSelect.value;
	}
	this.userSettings.showMuted = D.CAL.DOM.ShowMuted.checked ? 1 : 0;

	// Save settings
	var postData = this.GetReqData('save_settings',
		{user_settings: this.userSettings});

	if (this.PERM.access)
	{
		postData.type_access = D.CAL.Access.GetValues();
		// Set access for calendar type
		D.CAL.Access.SetSelected(this.typeAccess);

		this.settings.work_time_start = D.CAL.DOM.WorkTimeStart.value;
		this.settings.work_time_end = D.CAL.DOM.WorkTimeEnd.value;

		this.settings.week_holidays = [];
		for(var i = 0, l = D.CAL.DOM.WeekHolidays.options.length; i < l; i++)
			if (D.CAL.DOM.WeekHolidays.options[i].selected)
				this.settings.week_holidays.push(D.CAL.DOM.WeekHolidays.options[i].value);

		this.settings.year_holidays = D.CAL.DOM.YearHolidays.value;
		this.settings.year_workdays = D.CAL.DOM.YearWorkdays.value;
		//this.settings.week_start = D.CAL.DOM.WeekStart.value;
		postData.settings = this.settings;
	}

	this.Request({
		postData: postData,
		handler: function(oRes)
		{
			window.location = window.location;
		}
	});
},

ClearPersonalSettings: function()
{
	this.Request({
		postData: this.GetReqData('save_settings', {clear_all: 1}),
		handler: function(){window.location = window.location;}
	});
},

GetUserHref: function(userId)
{
	return this.pathToUser.replace(/#user_id#/ig, userId);
},

GetUserProfileLink : function(uid, bHtml, User, cn, bOwner)
{
	if (User.type == 'ext')
	{
		var html = '';
		if (User.email)
			html = BX.util.htmlspecialchars(User.email);
		else if (User.name)
			html = BX.util.htmlspecialchars(User.name);

		return html;
	}
	else
	{
		var path = this.arConfig.pathToUser.toLowerCase();
		path = path.replace('#user_id#', uid);

		cn = cn ? ' class="' + cn + '"' : '';

		if (!bHtml)
			return path;

		var html = BX.util.htmlspecialchars(User.name);
		if (bOwner)
			html += ' <span style="font-weight: normal !important;">(' + EC_MESS.Host + ')</span>';

		return '<a' + cn + ' href="' + path + '" target="_blank" title="' + EC_MESS.UserProfile + ': ' + BX.util.htmlspecialchars(User.name) + '" >' + html + '</a>';
	}
},

Day : function(day)
{
	return this.days[{MO: 0,TU: 1,WE: 2, TH: 3,FR: 4,SA: 5,SU: 6}[day]];
},

GetWeekDayByInd : function(i)
{
	return ['SU','MO','TU','WE','TH','FR','SA'][i];
},

ConvertDayIndex : function(i)
{
	if (i == 0)
		return 6;
	return i - 1;
},

Request : function(P)
{
	if (!P.url)
		P.url = this.actionUrl;
	if (P.bIter !== false)
		P.bIter = true;

	if (!P.postData && !P.getData)
		P.getData = this.GetReqData();

	var errorText;
	if (!P.errorText)
		errorText = false;

	var reqId = P.getData ? P.getData.reqId : P.postData.reqId;

	var _this = this, iter = 0;
	var handler = function(result)
	{
		var handleRes = function()
		{
			_this.CloseWaitWindow();
			var erInd = result.toLowerCase().indexOf('bx_event_calendar_action_error');
			if (!result || result.length <= 0 || erInd != -1)
			{
				var errorText = '';
				if (erInd >= 0)
				{
					var
						ind1 = erInd + 'BX_EVENT_CALENDAR_ACTION_ERROR:'.length,
						ind2 = result.indexOf('-->', ind1);
					errorText = result.substr(ind1, ind2 - ind1);
				}
				if (P.onerror && typeof P.onerror == 'function')
					P.onerror();

				return _this.DisplayError(errorText || P.errorText || '');
			}

			var res = P.handler(_this.GetRequestRes(reqId), result);
			if(res === false && ++iter < 20 && P.bIter)
				setTimeout(handleRes, 5);
			else
				_this.ClearRequestRes(reqId);
		};
		setTimeout(handleRes, 50);
	};
	this.ShowWaitWindow();

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

ExtendUserSearchInput : function()
{
	if (!window.SonetTCJsUtils)
		return;
	var _this = this;
	if (!SonetTCJsUtils.EC__GetRealPos)
		SonetTCJsUtils.EC__GetRealPos = SonetTCJsUtils.GetRealPos;

	SonetTCJsUtils.GetRealPos = function(el)
	{
		var res = SonetTCJsUtils.EC__GetRealPos(el);
		if (_this.oSuperposeDialog && _this.oSuperposeDialog.bShow)
		{
			scrollTop = _this.oSuperposeDialog.oCont.scrollTop;
			res.top = bxInt(res.top) - scrollTop;
			res.bottom = bxInt(res.bottom) - scrollTop;
		}
		return res;
	}
},

ParseLocation : function(str, bGetMRParams)
{
	if (!str)
		str = '';

	var res = {mrid : false, mrevid : false, str : str};
	if (str.length > 5 && str.substr(0, 5) == 'ECMR_')
	{
		var ar_ = str.split('_');
		if (ar_.length >= 2)
		{
			if (!isNaN(parseInt(ar_[1])) && parseInt(ar_[1]) > 0)
				res.mrid = parseInt(ar_[1]);
			if (!isNaN(parseInt(ar_[2])) && parseInt(ar_[2]) > 0)
				res.mrevid = parseInt(ar_[2]);
		}
	}

	if (res.mrid && bGetMRParams === true)
	{
		for (var i = 0, l = this.meetingRooms.length; i < l; i++)
		{
			if (this.meetingRooms[i].ID == res.mrid)
			{
				res.mrind = i;
				res.MR = this.meetingRooms[i];
				break;
			}
		}
	}
	return res;
},

RunPlanner: function(params)
{
	if (!params)
		params = {};

	if (!window.ECPlanner)
		return BX.loadScript(this.arConfig.planner_js_src, BX.delegate(function(){this.RunPlanner(params);}, this));

	if (!this.Planner)
	{
		this.Planner = new ECPlanner({
			id: this.id,
			workTime: this.arConfig.workTime,
			meetingRooms: this.bUseMR ? this.meetingRooms : false,
			currentDate: this.currentDate,
			actionUrl : this.actionUrl,
			config: {
				days: this.days,
				week_holidays: this.week_holidays,
				year_holidays: this.year_holidays
			},
			settings: this.plannerSettings,
			bAddGroupMembers: !this.bExtranet && this.type == 'group',
			AddGroupMembers: BX.proxy(this.AddGroupMembers, this),
			bAMPM: this.bAMPM,
			minWidth: this.bWideDate ? 880 : 760,
			minHeight: this.bWideDate ? 430 : 300
		});

		var _this = this;
		BX.addCustomEvent(this.Planner, 'onSubmit', function(Params)
		{
			var D = _this.oEditEventDialog;
			D.CAL._FromDateValue = D.CAL.DOM.FromDate.value = Params.fromDate;
			D.CAL.DOM.ToDate.value = Params.toDate;
			D.CAL._FromTimeValue = D.CAL.DOM.FromTime.value = Params.fromTime;
			D.CAL.DOM.ToTime.value = Params.toTime;

			if(parseInt(Params.locInd) != Params.locInd)
				Params.locInd = false;
			D.CAL.Location.Set(Params.locInd, Params.locValue || '');

			var k, values = [];
			for(k in Params.attendees)
				if (Params.attendees[k] && Params.attendees[k].User)
					values.push(Params.attendees[k].User);

			D.CAL.UserControl.SetValues(values);

			D.popupContainer.style.visibility = 'visible';
		});
	}

	this.Planner.OpenDialog(params);
},

OnResize: function(timeout)
{
	if (this._resizeTimeout)
		this._resizeTimeout = clearTimeout(this._resizeTimeout);

	var _this = this;
	if (timeout !== false)
	{
		this._resizeTimeout = setTimeout(function(){_this.OnResize(false);}, timeout || 200);
		return;
	}
	else
	{
		switch (this.activeTabId)
		{
			case 'month':
				setTimeout(BX.delegate(this.BuildDaysTitle, this), 100);
				break;
			case 'week':
			case 'day':
				this.ResizeTabTitle(this.Tabs[this.activeTabId]);
				break;
		}

		this.bJustRedraw = true;
		this.SetView({month: this.activeDate.month, year: this.activeDate.year});
		setTimeout(function(){_this.bJustRedraw = false;}, 500);
	}
},

CreateStrut: function(width)
{
	return BX.create("IMG", {props: {src: '/bitrix/images/1.gif'}, style: {width: width + 'px', height: '1px'}});
},

CheckMouseInCont: function(pWnd, e, d)
{
	var
		pos = BX.pos(pWnd),
		wndSize = BX.GetWindowScrollPos(),
		x = e.clientX + wndSize.scrollLeft,
		y = e.clientY + wndSize.scrollTop;

	if (typeof d == 'undefined')
		d = 0;

	return (x >= pos.left - d && x <= pos.right + d && y <= pos.bottom + d && y >= pos.top - d);
},

SaveConnections: function(Calback, onError)
{
	var connections = [], i, l = this.arConnections.length, con;
	for (i = 0; i < l; i++)
	{
		con = this.arConnections[i];
		connections.push({
			id: con.id || 0,
			name: con.name,
			link: con.link,
			user_name: con.user_name,
			pass: typeof con.pass == 'undefined' ? 'bxec_not_modify_pass' : con.pass,
			del: con.del ? 'Y' : 'N',
			del_calendars: con.pDelCalendars.checked ? 'Y' : 'N'
		});
	}

	this.Request({
		postData: this.GetReqData('connections_edit', {connections : connections}),
		handler: function()
		{
			setTimeout(function(){
				if (Calback && typeof Calback == 'function')
					Calback(true);
			}, 100);
		},
		onerror: function()
		{
			if (onError && typeof onError == 'function')
				onError();
		}
	});
	return true;
},

IsDavCalendar: function(id)
{
	return this.oSections[id] && (this.oSections[id].IS_EXCHANGE || this.oSections[id].CAL_DAV_CON);
},

SyncExchange: function()
{
	this.Request({
		postData: this.GetReqData('exchange_sync'),
		handler: function(oRes)
		{
			var res = oRes.result;
			setTimeout(function(){
				if (res === true)
					top.window.location = top.window.location;
				else if (res === false)
					alert(EC_MESS.ExchNoSync);
			}, 100);
		}
	});
},

Section: function(id)
{
	var s = {};
	if (this.arSectionsInd[id] && this.arSections[this.arSectionsInd[id]])
		s = this.arSections[this.arSectionsInd[id]];
	return s;
},

CanDo: function(action, id)
{
	var S = this.Section(id);
	return S.ID && S.PERM[action];
},

// DefaultAction() - for check and reset
// DefaultAction(false) - for prevent default action
DefaultAction: function(mod)
{
	if(typeof mod == 'undefined' && !this.bDoDefault) //
	{
		this.bDoDefault = true;
		return false;
	}

	if(mod === false) // Custom handler set state
		this.bDoDefault = false;

	return true;
},

OnTaskChanged : function(arTask)
{
	if (!this.oActiveSections['tasks']) // Show tasks
		return this.ShowCalendar(this.oSections['tasks'], true);

	this.Event.ReloadAll();
},

OnTaskKilled : function(taskId)
{
	for (var i = 0, l = this.arEvents.length; i < l; i++)
	{
		if (this.arEvents[i]['~TYPE'] == 'tasks' && this.arEvents[i].ID == taskId)
		{
			this.Event.UnDisplay(this.arEvents[i]);
			break;
		}
	}
},

HandleAccessNames: function(arNames)
{
	for (var code in arNames)
		this.arNames[code] = arNames[code];
},

GetAccessName: function(code)
{
	return this.arNames[code] || code;
},

GetMeetingSection: function()
{
	if (this.userSettings.meetSection && this.oSections[this.userSettings.meetSection])
		return this.userSettings.meetSection;

	return this.arSections[0]['ID'];
},

CheckMeetingRoom: function(Params, callback)
{
	this.Request({
		postData: this.GetReqData('check_meeting_room', Params),
		handler: function(oRes)
		{
			if (oRes)
			{
				if (callback && typeof callback == 'function')
					callback(oRes.check);
				return true;
			}
		}
	});
},

AddGroupMembers : function(Params)
{
	var _this = this, arPost = {};

	this.Request({
		postData: this.GetReqData('get_group_members', arPost),
		handler: function(oRes)
		{
			if (oRes)
			{
				if (oRes.users)
					BX.onCustomEvent(_this, 'onGetGroupMembers', [oRes.users]);
				return true;
			}
		}
	});
},

ItsYou: function(userId)
{
	if (userId == this.userId)
		return '<span class="bxc-it-is-you"> (' + EC_MESS.ItIsYou + ')</span>';
	return '';
},

Personal: function()
{
	return this.type == 'user' && this.ownerId == this.userId;
},

GetFreeDialogColor: function()
{
	var
		result = this.Colors[0],
		ind, colorMap = {};

	for (ind in this.Colors)
		colorMap[this.Colors[ind]] = true;

	for (ind in this.arSections)
	{
		color = this.arSections[ind].COLOR;
		if (colorMap[color])
			colorMap[color] = false;
	}

	for (ind in colorMap)
	{
		if (colorMap[ind])
		{
			result = ind;
			break;
		}
	}

	return result;
},

FormatTimeByNum: function(h, m)
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
	if (isNaN(h))
		h = 0;

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

		res = h.toString() + ':' + m.toString() + ' ' + ampm;
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

		if (h.toString().length > 2)
			h = parseInt(h.toString().substr(0, 2));
		m = parseInt(m);
	}

	if (isNaN(h) || h > 24)
		h = 0;
	if (isNaN(m) || m > 60)
		m = 0;

	return {h: h, m: m};
},

CheckType: function(type, ownerId)
{
	return this.type == type && this.ownerId == ownerId;
},

CheckSectionsCount: function()
{
	var i;
	for (i = 0; i < this.arSections.length; i++)
	{
		if (this.arSections[i].PERM.edit_section && this.IsCurrentViewSect(this.arSections[i]))
			return true;
	}
	return false;
},

GetWeekStart: function()
{
	return this.weekStart;
},

GetWeekDayOffset: function(day)
{
	if (!this.weekDayOffsetIndex)
	{
		this.weekDayOffsetIndex = {};
		for(var i = 0; i < this.weekDays.length; i++)
			this.weekDayOffsetIndex[this.weekDays[i][2]] = i;
	}
	return this.weekDayOffsetIndex[day];
},

SaveLastSection: function(sectionId)
{
	this.lastSection = parseInt(sectionId);
	BX.userOptions.save('calendar', 'last_section', this.type + '_' + this.ownerId, this.lastSection);
},

GetLastSection: function()
{
	return this.lastSection;
}
};



window.bxInt = function(x)
{
	return parseInt(x, 10);
}

window.bxIntEx = function(x)
{
	x = parseInt(x, 10);
	if (isNaN(x)) x = 0;
	return x;
}

window.bxSpCh = function(str)
{
	if (!str)
		return '';
	str = str.replace(/script_>/g, 'script>');
	str = str.replace(/&/g, '&amp;');
	str = str.replace(/"/g, '&quot;');
	str = str.replace(/</g, '&lt;');
	str = str.replace(/>/g, '&gt;');
	return str;
}

window.bxSpChBack = function(str)
{
	if (!str)
		return '';
	str = str.replace(/&lt;/g, '<');
	str = str.replace(/&gt;/g, '>');
	str = str.replace(/&quot;/g, '"');
	str = str.replace(/&amp;/g, '&');
	str = str.replace(/script_>/g, 'script>');
	return str;
}

window.EnterAndNotTextArea = function(e, id)
{
	if(e.keyCode == 13)
	{
		var targ = e.target || e.srcElement;
		if (targ && targ.nodeName && targ.nodeName.toLowerCase() != 'textarea' && targ.id.indexOf(id) == -1)
		{
			BX.PreventDefault(e);
			return true;
		}
	}
	return false;
}

function bxGetDateFromTS(ts, getObject)
{
//	if(!this.browserOffset)
//		this.browserOffset = new Date().getTimezoneOffset() * 60000;
	//var oDate = new Date(ts - this.browserOffset);

	var oDate = new Date(ts);
	if (!getObject)
	{
		var
			ho = oDate.getHours() || 0,
			mi = oDate.getMinutes() || 0;

		oDate = {
			date: oDate.getDate(),
			month: oDate.getMonth() + 1,
			year: oDate.getFullYear(),
			bTime: !!(ho || mi),
			oDate: oDate
		};

		if (oDate.bTime)
		{
			oDate.hour = ho;
			oDate.min = mi;
		}
	}

	return oDate;
}

window.bxFormatDate = function(d, m, y)
{
	var str = BX.message.FORMAT_DATE;

	str = str.replace(/YY(YY)?/ig, y);
	str = str.replace(/MMMM/ig, BX.message('MONTH_'+this.Number(m)));
	str = str.replace(/MM/ig, zeroInt(m));
	str = str.replace(/M/ig, BX.message('MON_' + this.Number(m)));
	str = str.replace(/DD/ig, zeroInt(d));

	return str;
}

window.bxGetPixel = function(bFlip)
{
	var q = BX.browser.IsIE() || BX.browser.IsOpera();
	if (bFlip)
		q = !q;
	return q ? 0 : 1;
}

window.zeroInt = function(x)
{
	x = bxInt(x);
	if (isNaN(x))
		x = 0;
	return x < 10 ? '0' + x.toString() : x.toString();
}

window.DenyDragEx = function(pEl)
{
	pEl.style.MozUserSelect = 'none';
	pEl.ondrag = BX.False;
	pEl.ondragstart = BX.False;
	pEl.onselectstart = BX.False;
}

JCEC.prototype.LoadEvents = function(m, y, P)
{
	if (m == undefined)
		m = this.activeDate.month;
	if (y == undefined)
		y = this.activeDate.year;
	if (P == undefined)
		P = {};

	var
		ameetid = [],
		i, _this = this,
		active = [],
		hidden = [];

	for (i in this.oActiveSections)
	{
		if (i != 'tasks')
		{
			i = parseInt(i);
			if (i < 0 || isNaN(i))
				continue;
		}

		if (this.oActiveSections[i])
		{
			active.push(i);
			if (this.oSections[i] && this.oSections[i]['~IS_MEETING_FOR_OWNER'])
				ameetid.push({ID: this.oSections[i]['OWNER_ID'], SECTION_ID: this.oSections[i]['ID']});
		}
		else
		{
			hidden.push(i);
		}
	}

	this.Request({
		getData: this.GetReqData('load_events', {
			month: parseInt(m, 10) + 1,
			year: y,
			usecl: 'Y',
			ameetid: ameetid,
			sa: active, // section - active
			sh: hidden, // section - hidden,
			cal_dav_data_sync: this.bSyncGoogle ? 'Y' : 'N'
		}),
		errorText: EC_MESS.LoadEventsErr,
		handler: function(oRes)
		{
			_this.bSyncGoogle = false;
			_this.HandleLoadedEvents({
				events: oRes.events,
				attendees: oRes.attendees,
				month: m,
				year: y,
				Params: P
			});
		}
	});
};

JCEC.prototype.HandleLoadedEvents = function(P)
{
	this.HandleEvents(P.events, P.attendees);

	this.arLoadedMonth[P.month + '.' + P.year] = true;
	if (!P.Params)
		P.Params = {};
	if (isNaN(bxInt(P.Params.month)))
		P.Params.month = P.month;
	if (isNaN(bxInt(P.Params.year)))
		P.Params.year = P.year;

	this.SetView(P.Params);
};

JCEC.prototype.HandleEvents = function(events, attendees)
{
	var i, e, a, sid, id;
	if (events && events.length)
	{
		for (i = 0; i < events.length; i++)
		{
			e = this.Event.PreHandle(events[i]);
			sid = this.Event.SmartId(e);
			if (!e.ID)
				continue;

			if (this.arLoadedEventsId[sid])
				continue;

			this.arEvents.push(e);
			this.arLoadedEventsId[sid] = true;
		}
	}

	if(attendees)
	{
		for (i in attendees)
		{
			id = parseInt(i, 10);
			a = attendees[i];
			if (!isNaN(id) && a && a.length)
				this.arAttendees[id] = a;
		}
	}
};

// BUILDING MONTH
JCEC.prototype.BuildEventHolder = function()
{
	if (this.EventHolderCont)
	{
		BX.cleanNode(this.EventHolderCont, true);
		this.EventHolderCont = null;
	}
	this.EventHolderCont = this.DaysGridCont.appendChild(BX.create('DIV', {props: {className : 'bxec-event-holder'}}));

	var _this = this;
	var c = this.oDaysGridTable.rows[0].cells[0];

	setTimeout(function()
	{
		_this.arCellCoords = {};
		for (var d = 0; d < 7; d ++)
		{
			_this.arCellCoords[d] = {
				left: bxInt(_this.oDaysGridTable.rows[0].cells[d].offsetLeft),
				width: bxInt(_this.oDaysGridTable.rows[0].cells[d].offsetWidth) + bxGetPixel(true)
			};
			if (d / 2 == Math.round(d / 2))
				_this.arCellCoords[d].width += bxGetPixel();
		}
		_this.dayCellHeight = parseInt(c.offsetHeight);
		_this.dayCellWidth = parseInt(c.offsetWidth);

		_this.DisplayEventsMonth();
	}, 10);
}

JCEC.prototype.EventClick = function(e)
{
	if (!e)
		e = window.event;

	var
		ind, action, ev_action, oEvent,
		o = e.target || e.srcElement;

	while(o)
	{
		if (o.getAttribute)
		{
			ind = parseInt(o.getAttribute('data-bx-event-ind'));
			action = o.getAttribute('data-bx-event-action');
			if (action)
				ev_action = action;

			if (!isNaN(ind) && this.arEvents[ind])
			{
				oEvent = this.arEvents[ind];
				if (!ev_action || ev_action == 'view')
				{
					this.Event.View(oEvent);
				}
				else if(ev_action == 'edit')
				{
					this.Event.Edit({oEvent: oEvent});
				}
				else if(ev_action == 'del')
				{
					if (this.Event.IsAttendee(oEvent) && !this.Event.IsHost(oEvent))
					{
						if(oEvent.USER_MEETING.STATUS != 'N')
							this.Event.SetMeetingStatus(false, {eventId: bxInt(oEvent.ID), comment: ''});
					}
					else if(oEvent['~TYPE'] != 'tasks')
					{
						this.Event.Delete(oEvent);
					}
				}

				if (this.MoreEventsWin)
					this.MoreEventsWin.close();

				break;
			}
		}
		o = o.parentNode;
	}
}

JCEC.prototype.DisplayEventsMonth = function(bRefresh)
{
	var i, l;
	if (bRefresh || this.bJustRedraw) // Redisplay all events
	{
		BX.cleanNode(this.EventHolderCont);
		for (i = 0, l = this.activeDateDaysArO.length; i < l; i++)
			this.activeDateDaysArO[i].arEvents = {begining : [], all : []};
	}
	else
	{
		this.activeFirst = this.activeDateDaysAr[0].getTime();
		this.activeLast = this.activeDateDaysAr[this.activeDateDaysAr.length - 1].getTime();
	}

	for (i = 0, l = this.arEvents.length; i < l; i++)
		if (this.arEvents[i])
			this.HandleEventMonth(this.arEvents[i], i);

	this.RefreshEventsOnWeeks([0, 1, 2, 3, 4, 5]);
}

JCEC.prototype.HandleEventMonth = function(el, ind, arPrehandle)
{
	var d_from, d_to, _d_from, _d_to;
	//this.arLoadedEventsId[this.Event.SmartId(el)] = true;

	el = this.HandleEventCommon(el, ind);
	if (!el)
		return;
	el.oParts = [];
	el.oWeeks = [];

	if (!arPrehandle)
	{
		d_from = bxGetDateFromTS(el.DT_FROM_TS);
		d_to = bxGetDateFromTS(el.DT_TO_TS);

		// Works only for events with 24:00 ent time - in the end of the day for  correct displaying
		if (d_from.bTime && !d_to.bTime)
			d_to = bxGetDateFromTS(el.DT_TO_TS - 60 * 60 * 24);

		d_from = {
			date: d_from.date,
			month: d_from.month - 1,
			year: d_from.year
		};

		d_to = {
			date: d_to.date,
			month: d_to.month - 1,
			year: d_to.year
		};

		_d_from = new Date(d_from.year, d_from.month, d_from.date).getTime();
		_d_to = new Date(d_to.year, d_to.month, d_to.date).getTime();
	}
	else
	{
		d_from = arPrehandle.d_from;
		d_to = arPrehandle.d_to;
		_d_from = arPrehandle._d_from;
		_d_to = arPrehandle._d_to;
	}

	if (_d_from > _d_to || _d_to < this.activeFirst || _d_from > this.activeLast)
		return;

	var arInit = {
		real_from: d_from,
		real_to: d_to,
		from: _d_from,
		to: _d_to,
		real_from_t: _d_from,
		real_to_t: _d_to
	};

	if (_d_from < this.activeFirst && _d_to < this.activeLast) // event started earlier but ends in the active period
	{
		arInit.from = this.activeFirst;
	}
	else if (_d_from > this.activeFirst && _d_to > this.activeLast) // The event began in the active period, but will end in the future
	{
		arInit.to = this.activeLast;
	}
	else if (_d_from < this.activeFirst && _d_to > this.activeLast) // Event started earlier and ends later
	{
		arInit.from = this.activeFirst;
		arInit.to = this.activeLast;
	}

	el.display = true;
	var bInPast = new Date(d_to.year, d_to.month, parseInt(d_to.date,10) + 1).getTime() < new Date().getTime();

	if (this.userSettings.showMuted && bInPast)
		el.bMuted = true;

	this.DisplayEvent_M(arInit, el);

	if (!bInPast)
		this.Event.Blink(el, true, true);
}

JCEC.prototype.HandleEventCommon = function(ev, ind)
{
	if(!this.userSettings.showDeclined && ev.USER_MEETING && ev.USER_MEETING.STATUS == 'N' && ev.MEETING_HOST != this.userId && (!this.startupEvent || (this.startupEvent && this.startupEvent.ID != ev.ID)))
		return false;

	if (!ev.oParts)
		ev.oParts = [];
	if (!ev.oWeeks)
		ev.oWeeks = [];

	ev.ind = ind;
	ev = this.Event.SetColor(ev);

	return ev;
}

JCEC.prototype.DisplayEvent_M = function(arInit, oEvent)
{
	var
		date, j, n,
		dayOffset,
		arEvParams = {partDaysCount: 0},
		bEventStart = false,
		bEventEnd = false;

	for (j = 0, n = this.activeDateDaysAr.length; j < n; j++)
	{
		date = this.activeDateDaysAr[j];
		dayOffset = this.GetWeekDayOffset(this.GetWeekDayByInd(date.getDay()));

		if (date.getTime() == arInit.from)
		{
			bEventStart = true;
			arEvParams = {left: this.arCellCoords[dayOffset].left + 1, arInit: arInit, dayIndex: j, partDaysCount: 0};
		}
		arEvParams.partDaysCount++;

		if (!bEventStart)
			continue;

		this.activeDateDaysArO[j].arEvents.all.push({oEvent: oEvent, partInd: oEvent.oParts.length, daysCount: arEvParams.partDaysCount});
		if (dayOffset == 6)
		{
			bEventEnd = date.getTime() == arInit.to;
			arEvParams.width = this.arCellCoords[dayOffset].left + this.arCellCoords[dayOffset].width - arEvParams.left - 3;
			arEvParams.bEnd = bEventEnd && arInit.to == arInit.real_to_t;
			this.BuildEventDiv(arEvParams, oEvent);
			if (bEventEnd)
				break;
		}

		if (!bEventEnd && dayOffset == 0 && date.getTime() != arInit.from)
			arEvParams = {left: this.arCellCoords[0].left + 1, arInit: arInit, dayIndex: j, partDaysCount: 1};

		if (date.getTime() == arInit.to)
		{
			bEventEnd = true;
			arEvParams.width = this.arCellCoords[dayOffset].left + this.arCellCoords[dayOffset].width - arEvParams.left - 3;
			arEvParams.bEnd = true;
			this.BuildEventDiv(arEvParams, oEvent);
			break;
		}
	}
}

JCEC.prototype.BuildEventDiv = function(arAtr, oEvent)
{
	if (parseInt(arAtr.width) <= 0)
		return;

	var oDiv, t, r, c;
	this.activeDateDaysArO[arAtr.dayIndex].arEvents.begining.push({oEvent: oEvent, partInd: oEvent.oParts.length, daysCount: arAtr.partDaysCount});

	var
		isTask = this.Event.IsTask(oEvent),
		isCrm = this.Event.IsCrm(oEvent);

	var cn = 'bxec-event';
	// if (oEvent.bDark)
		// cn += ' bxec-dark';

	if(oEvent.bMuted)
		cn += ' bxec-event-muted';

	oDiv = BX.create('DIV', {props: {className : cn}, style: {left: arAtr.left + 'px', width: bxInt(arAtr.width) + 'px', display: 'none', backgroundColor: oEvent.displayColor, color: oEvent.displayTextColor}});

	t = oDiv.appendChild(BX.create('TABLE'));
	r = t.insertRow(-1);

	var _this = this;
	if (oEvent.oParts.length > 0 || arAtr.arInit.real_from_t < arAtr.arInit.from)
		BX.adjust(r.insertCell(-1), {props: {className: 'bxec-event-ar'}, html: '<i></i>'});

	var
		bEnc = this.Event.IsMeeting(oEvent),
		statQ = this.Event.GetQuestIcon(oEvent),
		titleCell = r.insertCell(-1),
		typeIcon = '';

	if (bEnc)
		typeIcon = '<i class="bxc-e-meeting"></i>';
	if (isTask)
		typeIcon = '<i class="bxc-e-task"></i>';
	if (isCrm && !isTask)
		typeIcon = '<i class="bxc-e-crm"></i>';

	titleCell.innerHTML = '<div class="bxec-event-title">' + typeIcon + '<span class="bxec-event-label"' + this.Event.GetLabelStyle(oEvent) + '>' + statQ + BX.util.htmlspecialchars(oEvent.NAME) + '</span></div>';

	this.Event.BuildActions({cont: titleCell, oEvent: oEvent, evCont: oDiv});
	if (!arAtr.bEnd)
		BX.adjust(r.insertCell(-1), {props: {className: 'bxec-event-ar'}, html: '<b></b>'});

	oDiv.onmouseover = function(){_this.HighlightEvent_M(oEvent, this);};
	oDiv.onmouseout = function(){_this.HighlightEvent_M(oEvent, this, true);}
	oDiv.ondblclick = function(){_this.Event.View(oEvent);};

	oDiv.setAttribute('data-bx-event-ind', oEvent.ind);

	oEvent.oWeeks.push({dayIndex: arAtr.dayIndex, bEnd: arAtr.bEnd});
	oEvent.oParts.push(oDiv);

	this.EventHolderCont.appendChild(oDiv);
}

JCEC.prototype.HighlightEvent_M = function(oEvent, pEl, bUn)
{
	if (!oEvent || !oEvent.oParts || oEvent.oParts.length == 0)
		return;

	var i, l, f = bUn ? BX.removeClass : BX.addClass;

	for (i = 0, l = oEvent.oParts.length; i < l; i++)
		f(oEvent.oParts[i], 'bxec-event-over');

	if (pEl)
		f(pEl, 'bxec-event-over');

	if (oEvent.pMoreDivs)
		for (i = 0, l = oEvent.pMoreDivs.length; i < l; i++)
			f(oEvent.pMoreDivs[i], 'bxec-event-over');
}

JCEC.prototype.GetEventWeeks = function(oEvent)
{
	var dind, j, arWeeks = [], i, l;
	for (i = 0, l = oEvent.oParts.length; i < l; i++)
	{
		dind = oEvent.oWeeks[i].dayIndex;
		for (j = 0; j < 6; j++)
		{
			if (dind >= j * 7 && dind < (j + 1) * 7)
			{
				arWeeks.push(j);
				break;
			}
		}
	}
	return arWeeks;
}

// ####################################################################################

JCEC.prototype.BuildWeekEventHolder = function()
{
	if (this._bBETimeOut)
		clearTimeout(this._bBETimeOut);

	var _this = this;

	this._bBETimeOut = setTimeout(
		function()
		{
			var Tab = _this.Tabs[_this.activeTabId || _this.userSettings.tabId];
			// Days title event holder;
			if (!Tab.pEventHolder)
				Tab.pEventHolder = Tab.pBodyCont.rows[0].cells[0].firstChild;

			if (_this.bJustRedraw)
				_this.ReBuildEvents(Tab.id);
			else
				_this.DisplayWeekEvents(Tab);
		},
		50
	);
}

JCEC.prototype.DisplayWeekEvents = function(Tab)
{
	BX.cleanNode(Tab.pEventHolder);

	for (var i = 0, l = this.arEvents.length; i < l; i++)
		if (this.arEvents[i])
			this.HandleEventWeek({Tab : Tab, Event: this.arEvents[i], ind: i});

	var _this = this;
	setTimeout(function()
	{
		_this.RefreshEventsInDayT(Tab);
		_this.ArrangeEventsInTL(Tab);
	}, 50);
}

JCEC.prototype.ReBuildEvents = function(tabId)
{
	var
		Tab = this.Tabs[tabId],
		cont = Tab.pTimelineCont,
		node, i, l, oDay;

	BX.cleanNode(Tab.pEventHolder);

	for (i = 0; i < Tab.daysCount; i++) // Clean days params
	{
		oDay = Tab.arDays[i];
		oDay.TLine = {};
		oDay.Events = {begining: [], hidden: [], all: []};
		oDay.EventsCount = 0;
	}

	l = cont.childNodes.length;
	i = 0;
	while (i < l)
	{
		node = cont.childNodes[i];
		if (node.className.toString().indexOf('bxec-tl-event') == -1)
		{
			i++;
			continue;
		}
		cont.removeChild(node);
		l = cont.childNodes.length;
	}
	this.DisplayWeekEvents(Tab);
}

JCEC.prototype.HandleEventWeek = function(P)
{
	var ev = this.HandleEventCommon(P.Event, P.ind);
	if (!ev)
		return;

	if (!ev.oDaysT)
		ev.oDaysT = {};
	if (!ev.oTLParts)
		ev.oTLParts = {};

	ev.oTLParts[P.Tab.id] = [];

	var
		_d_from = ev.DT_FROM_TS,
		_d_to = ev.DT_TO_TS,
		d_from = bxGetDateFromTS(_d_from),
		d_to = bxGetDateFromTS(_d_to);

	// Event is out of view area
	if (_d_to < P.Tab.activeFirst || _d_from > P.Tab.activeLast)
		return;

	// for excluding displaying events started in the 00:00
	if (ev.DT_SKIP_TIME != 'Y' && _d_to == P.Tab.activeFirst)
		return;

	// Works only for events with 24:00 ent time - in the end of the day for  correct displaying
	if (d_from.bTime && !d_to.bTime)
		_d_to -= 1000;

	var arInit = {
		real_from: d_from,
		real_to: d_to,
		from: _d_from,
		to: _d_to,
		real_from_t: _d_from,
		real_to_t: _d_to
	};

	if (_d_from < P.Tab.activeFirst && _d_to <= P.Tab.activeLast) // event started earlier but ends in the active period
	{
		arInit.from = P.Tab.activeFirst;
	}
	else if (_d_from >= P.Tab.activeFirst && _d_to > P.Tab.activeLast) // The event began in the active period, but will end in the future
	{
		arInit.to = P.Tab.activeLast;
	}
	else if (_d_from < P.Tab.activeFirst && _d_to > P.Tab.activeLast) // Event started earlier and ends later
	{
		arInit.from = P.Tab.activeFirst;
		arInit.to = P.Tab.activeLast;
	}

	ev.display = true;
	var bInPast = new Date(d_to.year, d_to.month - 1, parseInt(d_to.date, 10) + 1).getTime() < new Date().getTime();

	if (this.userSettings.showMuted && bInPast)
		ev.bMuted = true;

	if(!d_from.bTime && !d_to.bTime) // Display event on the top sector
		this.DisplayEvent_DT(arInit, ev, P.Tab);
	else  // Display event on the TIMELINE
		this.DisplayEvent_TL(arInit, ev, P.Tab);

	if (!bInPast)
		this.Event.Blink(ev, true, true);
}

JCEC.prototype.DisplayEvent_DT = function(arInit, oEvent, Tab)
{
	var
		_this = this,
		bEventStart = false,
		day_from = this.ConvertDayIndex(new Date(arInit.from).getDay()),
		day_to = this.ConvertDayIndex(new Date(arInit.to).getDay()),
		_event = {oEvent : oEvent, daysCount: day_to - day_from + 1},
		startDay,
		endDay,
		isTask = this.Event.IsTask(oEvent),
		isCrm = this.Event.IsCrm(oEvent),
		typeIcon = '',
		i, oDay;

	for (i = 0; i < Tab.daysCount; i++)
	{
		oDay = Tab.arDays[i];
		if (oDay.day == day_from)
		{
			startDay = oDay;
			bEventStart = true;
			oDay.Events.begining.push(_event);
		}
		if (!bEventStart)
			continue;
		oDay.Events.all.push(_event);
		oDay.EventsCount++;
		if (oDay.day == day_to)
		{
			endDay = oDay;
			break;
		}
	}

	var
		left = bxInt(startDay.pWnd.offsetLeft) + 2 - bxGetPixel(),
		right = bxInt(endDay.pWnd.offsetLeft) + bxInt(endDay.pWnd.offsetWidth),
		width = right - left - 5,

		// Build div
		oDiv = BX.create('DIV', {props: {className : 'bxec-event'}, style: {left: left.toString()+ 'px', width: width.toString() + 'px', backgroundColor: oEvent.displayColor, color: oEvent.displayTextColor}}),
		t = oDiv.appendChild(BX.create('TABLE')),
		r = t.insertRow(-1);
	oEvent.oDaysT[Tab.id] = oDiv;

	oDiv.setAttribute('data-bx-event-ind', oEvent.ind);

	if(oEvent.bMuted)
		BX.addClass(oDiv, 'bxec-event-muted');

	if (arInit.real_from_t < arInit.from)
	{
		c = r.insertCell(-1);
		c.innerHTML = '<img class="bxec-iconkit" src="/bitrix/images/1.gif">';
		c.className = 'bxec-event-ar-l';
	}

	if (this.Event.IsMeeting(oEvent))
		typeIcon = '<i class="bxc-e-meeting"></i>';
	if (isTask)
		typeIcon = '<i class="bxc-e-task"></i>';
	if (isCrm && !isTask)
		typeIcon = '<i class="bxc-e-crm"></i>';

	var
		statQ = this.Event.GetQuestIcon(oEvent),
		titleCell = r.insertCell(-1);
	titleCell.innerHTML = '<div class="bxec-event-title">' + typeIcon + '<span class="bxec-event-label"' + this.Event.GetLabelStyle(oEvent) + '>' + statQ + BX.util.htmlspecialchars(oEvent.NAME) + '</span></div>';

	this.Event.BuildActions({cont: titleCell, oEvent: oEvent, evCont: oDiv});
	oDiv.onmouseover = function(){_this.HighlightEvent_DT(this);};
	oDiv.onmouseout = function(){_this.HighlightEvent_DT(this, true);}
	oDiv.ondblclick = function(){_this.Event.View(oEvent);};

	Tab.pEventHolder.appendChild(oDiv);
}

JCEC.prototype.DisplayEvent_TL = function(arInit, oEvent, Tab)
{
	var
		bEventStart = false,
		nd_f = new Date(arInit.from),
		nd_t = new Date(arInit.to),
		day_from = this.ConvertDayIndex(nd_f.getDay()),
		day_to = this.ConvertDayIndex(nd_t.getDay()),
		h_from = nd_f.getHours() || 0,
		m_from = nd_f.getMinutes() || 0,
		h_to = nd_t.getHours(),
		m_to = nd_t.getMinutes(),
		startDay,
		endDay,
		i, oDay;

	if (!nd_t)
	{
		h_to = 23;
		m_to = 59;
	}
	else if (arInit.from == arInit.to)
	{
		if (m_to == 59)
		{
			h_to++;
			m_to = 00;
		}
		else
		{
			m_to++;
		}
	}
	else
	{
		if (m_to == 0)
		{
			h_to--;
			m_to = 59;
		}
		else if(m_to > 1)
		{
			m_to--;
		}
	}

	for (i = 0; i < Tab.daysCount; i++)
	{
		oDay = Tab.arDays[i];
		if (oDay.day == day_from)
		{
			startDay = oDay;
			bEventStart = true;
		}
		if (!bEventStart)
			continue;

		if (oDay.day == day_to)
		{
			endDay = oDay;
			break;
		}
	}

	if (startDay && endDay)
	{
		this._SetTimeEvent(startDay, h_from, m_from, {oEvent : oEvent, bStart: true, arInit: arInit});
		this._SetTimeEvent(endDay, h_to, m_to, {oEvent : oEvent, bStart: false, arInit: arInit});
	}
}

JCEC.prototype._SetTimeEvent = function(oDay, h, m, oEv)
{
	if (!oDay.TLine)
		oDay.TLine = {};
	h = bxInt(h);
	m = bxInt(m);

	if (!oDay.TLine[h])
		oDay.TLine[h] = {};
	if (!oDay.TLine[h][m])
		oDay.TLine[h][m] = [];

	oDay.TLine[h][m].push(oEv);
}

JCEC.prototype.HighlightEvent_DT = function(pWnd, bHide)
{
	var f = bHide ? BX.removeClass : BX.addClass;
	f(pWnd, 'bxec-event-over');
}

JCEC.prototype.RefreshEventsInDayT = function(Tab)
{
	var
		slots = [],
		step = 0,
		max = 3,
		day, i, arEv, j, ev, arAll, dis, arHid, top;

	for(j = 0; j < max; j++)
		slots[j] = 0;


	for (i = 0; i < Tab.daysCount; i++)
	{
		day = Tab.arDays[i];
		arEv = day.Events.begining;
		n = arEv.length;
		arHid = [];
		if (n > 0)
		{
			arEv.sort(function(a, b){return b.daysCount - a.daysCount});
			eventloop:
			for(k = 0; k < n; k++)
			{
				ev = arEv[k];
				if (!ev)
					continue;

				if (!this.arEvents[ev.oEvent.ind])
				{
					day.Events.begining = arEv = BX.util.deleteFromArray(arEv, k);
					ev = arEv[k];
					if (!ev)
						continue;
				}

				for(j = 0; j < max; j++)
				{
					if (slots[j] - step <= 0)
					{
						slots[j] = step + ev.daysCount;
						top = 21 + j * 18;
						ev.oEvent.oDaysT[Tab.id].style.top = (21 + j * 18).toString() + 'px';
						continue eventloop;
					}
				}
				arHid[ev.oEvent.ID] = true;
				day.Events.hidden.push(ev);
			}
		}
		// For all events in the day
		arAll = day.Events.all;
		for (var x = 0, f = arAll.length; x < f; x++)
		{
			ev = arAll[x];
			if (!ev || arHid[ev.oEvent.ID])
				continue;
			if (!this.arEvents[ev.oEvent.ind])
			{
				day.Events.all = arAll = BX.util.deleteFromArray(arAll, x);
				ev = arAll[x];
				if (!ev)
					continue;
			}
			dis = ev.oEvent.oDaysT[Tab.id].style.display;
			if (dis && dis.toLowerCase() == 'none')
				day.Events.hidden.push(ev);
		}
		this.ShowMoreEventsSelectWeek(day, Tab.id);
		step++;
	}
}

JCEC.prototype.ShowMoreEventsSelectWeek = function(oDay, tabId)
{
	var
		_this = this,
		arEv = oDay.Events.hidden,
		l = arEv.length,
		arHidden = [],
		pMoreDiv = oDay.pMoreEvents.firstChild,
		i, el, p;

	if (l <= 0)
	{
		pMoreDiv.style.display = 'none';
		return;
	}

	for (i = 0; i < l; i++)
	{
		el = arEv[i];
		p = el.oEvent.oDaysT[tabId];
		p.style.display = "none"; // Hide event
		arHidden.push({pDiv: p, oEvent: el.oEvent});
	}

	pMoreDiv.style.display = 'block';
	pMoreDiv.innerHTML = EC_MESS.MoreEvents + ' (' + l + ' ' + EC_MESS.Item + ')';
	pMoreDiv.onmousedown = function(e){if(!e) e = window.event; BX.PreventDefault(e);};
	pMoreDiv.onclick = function(e){_this.ShowMoreEventsWin({Events: arHidden, id: 'day_t_' + tabId + oDay.day, pDay: oDay.pWnd, mode: 'day_t', pSelect: pMoreDiv});};
}

JCEC.prototype.ArrangeEventsInTL = function(Tab)
{
	try{ //
	var
		bStarted = false,
		h, m, e, pDiv, _e, leftDrift,
		arProceed = {},
		procCnt = 0,
		procRows = 0,
		_row,
		RowSet,
		Rows,
		Row,
		bClosedAllRows, // All rows finished, start new row in rowset
		startedEvents = {},
		startedEventsCount = 0,
		arAll = [],
		Day, i, arEv, ev;

	for (i = 0; i < Tab.daysCount; i++) // For every day
	{
		Day = Tab.arDays[i];
		RowSet = [];

		if (startedEventsCount > 0)
		{
			if (!Day.TLine)
				Day.TLine = {};
			for (_e in startedEvents)
			{
				if (startedEvents[_e] && typeof startedEvents[_e] == 'object' && startedEvents[_e].oEvent)
				{
					if (!Day.TLine['0'])
						Day.TLine['0'] = {'0' : []};
					Day.TLine['0']['0'].push({oEvent : startedEvents[_e].oEvent, bStart: true, dontClose: false, arInit: startedEvents[_e].arInit});
				}
			}
		}
		if (!bStarted && !Day.TLine)
			continue;
		bClosedAllRows = true;

		if (Day.TLine) // some events starts or ends in this day
		{
			for (h = 0; h <= 23; h++) // hour loop
			{
				if (!Day.TLine[h] && h != 23)
					continue;
				for (m = 0; m < 60; m++) // minutes loop
				{
					arEv = Day.TLine[h] && Day.TLine[h][m] ? Day.TLine[h][m] : false;
					if (h == 23 && m == 59)
					{
						if (arEv === false)
							arEv = [];
						for (_e in startedEvents)
						{
							if (startedEvents[_e] && typeof startedEvents[_e] == 'object' && startedEvents[_e].oEvent)
								arEv.push({oEvent : startedEvents[_e].oEvent, bStart: false, dontClose: true, arInit: startedEvents[_e].arInit});
						}
					}

					if (!arEv)
						continue;

					// TODO: Sort by event length
					for (e = 0; e < arEv.length; e++) // events in current moment
					{
						ev = arEv[e];
						if (ev.bStart) // Event START
						{
							startedEvents[ev.oEvent.ID] = ev;
							startedEventsCount++;
							if (bClosedAllRows)
								RowSet.push([]);
							Rows = RowSet[RowSet.length - 1];
							freeRowId = false;
							bClosedAllRows = false;
							if (Rows.length > 1)
							{
								for(r = 0, rl = Rows.length; r < rl; r++)
								{
									Row = Rows[r];
									if (!Row.bFilled)
									{
										freeRowId = r;
										break;
									}
								}
							}
							_row = {
								bFilled: true,
								evId: ev.oEvent.ID,
								h_f: h,
								m_f: m
							};
							if (freeRowId !== false) // we have free row
							{
								_row.arEvents = Rows[freeRowId].arEvents;
								Rows[freeRowId] = _row;
							}
							else // push new row
							{
								Rows.push(_row);
							}
						}
						else // Event END
						{
							bClosedAllRows = true;
							if (!ev.dontClose)
							{
								startedEvents[ev.oEvent.ID] = false;
								startedEventsCount--;
							}

							for(r = 0, rl = Rows.length; r < rl; r++)
							{
								Row = Rows[r];
								if (Row.bFilled && Row.evId == ev.oEvent.ID)
								{
									Row.bFilled = false;
									pDiv = this.BuildEventDiv_TL(
										{
											Tab: Tab,
											dayInd: i,
											from: {h: Row.h_f, m: Row.m_f},
											to: {h: h, m: m},
											oEvent: ev.oEvent,
											arInit: ev.arInit
										}
									); // Build div
									if (!Row.arEvents)
										Row.arEvents = [pDiv];
									else
										Row.arEvents.push(pDiv);
								}
								if (Row.bFilled && bClosedAllRows)
									bClosedAllRows = false;
							}
						}
					}
				}
			}
		}

		var
			cell = Tab.pTimelineTable.rows[0].cells[i + 1],
			arRS, rs, rsl, rowsCount, rowWidth, r, rl, rw,
			sWidth = cell.offsetWidth - 15;

		for (rs = 0, rsl = RowSet.length; rs < rsl; rs++) // For each rowset
		{
			arRS = RowSet[rs];
			rowsCount = arRS.length;
			rowWidth = Math.round((sWidth - rowsCount) / rowsCount);
			for (r = 0; r < arRS.length; r++) // For each row
			{
				Row = arRS[r];
				if (r == 0) // first row
				{
					rw = rowWidth;
					leftDrift = bxInt(Row.arEvents[0].style.left);
					rl = false;
				}
				else
				{
					leftDrift += rowWidth + 1;
					rl = leftDrift;
					if (r == arRS.length- 1) // last row
						rw = sWidth - (rowWidth + 1) * (arRS.length- 1) - 1;
					else
						rw = rowWidth;
				}
				for (e = 0; e < Row.arEvents.length; e++) // For each event
				{
					pEv = Row.arEvents[e];
					pEv.style.width = rw + 'px';
					if (rl !== false)
						pEv.style.left = rl + 'px';
				}
			}
		}
	}
	}catch(e){}
}

JCEC.prototype.BuildEventDiv_TL = function(P)
{
	var
		_this = this,
		oEvent = P.oEvent,
		isTask = this.Event.IsTask(oEvent),
		isCrm = this.Event.IsCrm(oEvent),
		m_f = P.from.m,
		m_t = P.to.m,
		typeIcon = '',
		rowInd_f = Math.floor((P.from.h + m_f / 60) * 2),
		rowInd_t = Math.floor((P.to.h + m_t / 60) * 2),
		cellStart = P.Tab.pTimelineTable.rows[rowInd_f].cells[this.__ConvertCellIndex(rowInd_f, P.dayInd + 1, true)],
		cellEnd = P.Tab.pTimelineTable.rows[rowInd_t].cells[this.__ConvertCellIndex(rowInd_t, P.dayInd + 1, true)],
		top = bxInt(cellStart.offsetTop) + 1 + bxGetPixel(true),
		bottom = bxInt(cellEnd.offsetTop) - 1 - bxGetPixel(),
		left = bxInt(cellStart.offsetLeft) + 2 - bxGetPixel(),
		// Build div
		oDiv = BX.create('DIV', {
			props: {className : 'bxec-tl-event'},
			style: {left: left + 'px', backgroundColor: oEvent.displayColor, color: oEvent.displayTextColor},
			events: {
				mouseover: function(e) {_this.HighlightEvent_TL(oEvent, this, false, P.Tab.id, e || window.event);},
				mouseout: function(e) {_this.HighlightEvent_TL(oEvent, this, true, P.Tab.id, e || window.event);},
				dblclick: function() {_this.Event.View(oEvent);}
			}
		});

	oDiv.setAttribute('data-bx-event-ind', oEvent.ind);
	oDiv.setAttribute('data-bx-original-width', '');
	oDiv.setAttribute('data-bx-original-height', '');

	oEvent._eventViewed = false;
	oEvent._contentSpan = false;

	if (this.Event.IsMeeting(oEvent))
		typeIcon = '<i class="bxc-e-meeting"></i>';
	if (isTask)
		typeIcon = '<i class="bxc-e-task"></i>';
	if (isCrm && !isTask)
		typeIcon = '<i class="bxc-e-crm"></i>';

	var
		rf = P.arInit.real_from,
		rt = P.arInit.real_to,
		statQ = this.Event.GetQuestIcon(oEvent),
		innerHTML = typeIcon + statQ+ '<u ' + this.Event.GetLabelStyle(oEvent) + '>' + BX.util.htmlspecialchars(oEvent.NAME) + '</u><br />',
		t1 = this.FormatTimeByNum(rf.hour, rf.min),
		t2 = this.FormatTimeByNum(rt.hour, rt.min);

	 // consider minutes
	if (m_f != 30 && m_f != 0)
		top += Math.round((m_f > 30 ? m_f - 30 : m_f) * 40 / 60) - 1;
	if (m_t != 30 && m_t != 0)
		bottom += Math.round((m_t > 30 ? m_t - 30 : m_t) * 40 / 60) + 2;
	var height = bottom - top;

	if (height <= 17)
		height = 17;

	oDiv.style.top = top + 'px';
	oDiv.style.height = height + 'px';

	if (rf.year == rt.year && rf.month == rt.month && rf.date == rt.date) // during one day
		innerHTML += t1 + ' &mdash; ' + t2;
	else
		innerHTML += bxFormatDate(rf.date, rf.month, rf.year) + ' ' + t1 + ' &mdash; ' + bxFormatDate(rt.date, rt.month, rt.year) + ' ' +  t2;

	oDiv.appendChild(BX.create("DIV", {children: [BX.create("SPAN", {props: {className: 'bxec-cnt-sp'}, html: innerHTML})]}));

	this.Event.BuildActions({cont: oDiv, oEvent: oEvent, evCont: oDiv, bTimeline: true});

	P.Tab.pTimelineCont.appendChild(oDiv);

	if (!oEvent.oTLParts[P.Tab.id])
		oEvent.oTLParts[P.Tab.id] = [];
	oEvent.oTLParts[P.Tab.id].push(oDiv);
	return oDiv;
}

JCEC.prototype.HighlightEvent_TL = function(oEvent, pWnd, bHide, tabId, e)
{
	var _this = this;
	var originalWidth = pWnd.getAttribute('data-bx-original-width');
	var originalHeight = pWnd.getAttribute('data-bx-original-height');

	if (!bHide && !oEvent._eventViewed)
	{
		if (this._highlightIntKeypWnd == pWnd && this._highlightInt)
			return;

		if (this._highlightInt)
			clearInterval(this._highlightInt);

		if (!originalWidth)
		{
			originalWidth = parseInt(pWnd.style.width);
			pWnd.setAttribute('data-bx-original-width', originalWidth);
		}
		if (!originalHeight)
		{
			originalHeight = parseInt(pWnd.style.height);
			pWnd.setAttribute('data-bx-original-height', originalHeight);
		}

		oEvent._contentSpan = BX.findChild(pWnd, {className: 'bxec-cnt-sp'}, true);

		var
			d = 0,
			w1 = originalWidth,
			w2 = parseInt(oEvent._contentSpan.offsetWidth) + 20,
			h1 = originalHeight,
			h2 = parseInt(oEvent._contentSpan.offsetHeight) + 30;

		if (w2 <= 60)
			w2 = 60;

		if (h2 <= 55)
			h2 = 55;

		if (w2 - w1 > 0 || h2 - h1 > 0)
		{
			this._highlightIntKeypWnd = pWnd;
			this._highlightInt = setInterval(function(){
				var
					bWidth = (w2 - w1) <= 0,
					bHeight = (h2 - h1) <= 0;

				if (bWidth && bHeight)
				{
					oEvent._eventViewed = true;
					return clearInterval(_this._highlightInt);
				}

				d += 12;
				if (!bWidth)
				{
					w1 += d;
					if (w1 > w2)
						w1 = w2 + 2;
					pWnd.style.width = w1 + 'px';
				}

				if (!bHeight)
				{
					h1 += d;
					if (h1 > h2)
						h1 = h2 + 2;
					pWnd.style.height = h1 + 'px';
				}
			}, 5);
		}
	}
	else
	{
		if (this.CheckMouseInCont(pWnd, e, -2))
			return true;

		this._highlightIntKeypWnd = false;
		if (this._highlightInt)
			clearInterval(this._highlightInt);
		this._highlightInt = false;

		if (originalWidth)
		{
			pWnd.style.width = originalWidth + "px";
			oEvent._eventViewed = false;
		}

		if (originalHeight)
		{
			pWnd.style.height = originalHeight + "px";
			oEvent._eventViewed = false;
		}
	}

	if (bHide)
		BX.removeClass(pWnd, 'bxec-tl-ev-hlt');
	else
		BX.addClass(pWnd, 'bxec-tl-ev-hlt');

	if (oEvent.oTLParts && oEvent.oTLParts[tabId])
	{
		var arParts = oEvent.oTLParts[tabId], pl = arParts.length, p, ow, oh;
		for (p = 0; p < pl; p++)
		{
			if (arParts[p] == pWnd)
				continue;

			if (bHide)
			{
				BX.removeClass(arParts[p], 'bxec-tl-ev-hlt');

				ow = arParts[p].getAttribute('data-bx-original-width');
				oh = arParts[p].getAttribute('data-bx-original-height');
				if (ow)
					arParts[p].style.width = ow + "px";
				if (oh)
					arParts[p].style.height = oh + "px";
			}
			else
			{
				BX.addClass(arParts[p], 'bxec-tl-ev-hlt');
			}
		}
	}
}

JCEC.prototype.SimpleSaveNewEvent = function(arParams)
{
	var D = this.oAddEventDialog;
	D.CAL.DOM.Name.value = BX.util.trim(D.CAL.DOM.Name.value);
	if (D.CAL.DOM.Name.value == "")
	{
		D.CAL.bHold = true;
		alert(EC_MESS.EventNameError);
		setTimeout(function(){D.CAL.bHold = false;}, 100);
		return false;
	}

	var
		fd = D.CAL.Params.from,
		td = D.CAL.Params.to,
		res = {
			name: D.CAL.DOM.Name.value,
			desc: '',//Ob.oDesc.value,
			calendar: D.CAL.DOM.SectSelect.value,
			from: BX.date.getServerTimestamp(fd.getTime()),
			to: BX.date.getServerTimestamp(td.getTime()),
			skip_time: (fd.getHours() == 0 && fd.getMinutes() == 0 && td.getHours() == 0 && td.getMinutes() == 0) ? 'Y' : 'N'
		};

	if (D.CAL.DOM.Accessibility)
		res.accessibility = D.CAL.DOM.Accessibility.value;

	this.Event.Save(res);
	return true;
}

JCEC.prototype.ExtendedSaveEvent = function(Params)
{
	var
		D = this.oEditEventDialog,
		CE = D.CAL.oEvent,
		_this = this, i,
		err = function(str){alert(str); this.bEditEventDialogOver = true; return false;};

	if (D.CAL.DOM.Name.value.length <= 0)
		return err(EC_MESS.EventNameError);

	var res = {
		name: D.CAL.DOM.Name.value,
		calendar: D.CAL.DOM.SectSelect.value,
		desc: window.pLHEEvDesc.GetContent(),
		guests: [],
		arGuests: [],
		color: '',
		text_color: ''
	};

	if (res.calendar && this.oSections[res.calendar])
	{
		if (this.oSections[res.calendar].COLOR && this.oSections[res.calendar].COLOR.toLowerCase() != D.CAL.Color.toLowerCase())
			res.color = D.CAL.Color;

		if (D.CAL.TextColor && this.oSections[res.calendar].TEXT_COLOR.toLowerCase() != D.CAL.TextColor.toLowerCase())
			res.text_color = D.CAL.TextColor;
	}

	// Get HTML Editor content
	res.desc = window.pLHEEvDesc.GetContent();

	// Datetime limits
	var fd = BX.parseDate(D.CAL.DOM.FromDate.value);
	if (!fd)
		return err(EC_MESS.EventDiapStartError);

	res.skip_time = D.CAL.DOM.FullDay.checked;
	if (res.skip_time)
		D.CAL._FromTimeValue = D.CAL.DOM.FromTime.value = D.CAL.DOM.ToTime.value = '';

	var fromTime = this.ParseTime(D.CAL.DOM.FromTime.value);
	fd.setHours(fromTime.h);
	fd.setMinutes(fromTime.m);
	res.from = BX.date.getServerTimestamp(fd.getTime());

	var td = BX.parseDate(D.CAL.DOM.ToDate.value);
	if (td)
	{
		var toTime = this.ParseTime(D.CAL.DOM.ToTime.value);
		td.setHours(toTime.h);
		td.setMinutes(toTime.m);
		res.to = BX.date.getServerTimestamp(td.getTime());

		if (res.from == res.to && toTime.h == 0 && toTime.m == 0)
		{
			fd.setHours(0);
			fd.setMinutes(0);
			td.setHours(0);
			td.setMinutes(0);

			res.from = BX.date.getServerTimestamp(fd.getTime());
			res.to = BX.date.getServerTimestamp(td.getTime());
		}
	}
	else
	{
		if (CE.ID)
			return err(EC_MESS.EventDiapEndError);
		else
			res.to = res.from;
	}

	if (res.from > res.to) // Date To earlier Date From - send error
		return err(EC_MESS.EventDatesError);

	if (!res.skip_time)
	{
		res.skip_time = fd.getHours() == 0 && fd.getMinutes() == 0;
		if (res.skip_time && td && td.getHours && td.getMinutes)
			res.skip_time = td.getHours() == 0 && td.getMinutes() == 0;
	}
	res.skip_time = res.skip_time ? 'Y' : 'N';

	res.guests = [];
	res.arGuests = [];

	if (this.allowMeetings)
	{
		// ***** MEETING *****
		var Attendees = D.CAL.UserControl.GetValues();
		for(i in Attendees)
		{
			if (Attendees[i] && typeof Attendees[i] == 'object' && Attendees[i].User)
			{
				if (Attendees[i].User.type == 'ext')
					res.guests.push("BXEXT:" + i);
				else
					res.guests.push(bxInt(i));
			}
		}

		res.isMeeting = CE.IS_MEETING || res.guests.length > 0;
		res.meeting = {
			host: this.userId,
			text: BX.util.trim(D.CAL.DOM.MeetText.value),
			open: !!D.CAL.DOM.OpenMeeting.checked ? 1 : 0,
			notify: D.CAL.DOM.NotifyStatus.checked ? 1 : 0,
			reinvite: !!D.CAL.DOM.Reinvite.checked ? 1 : 0
		};
	}

	if (CE.ID)
		res.id = CE.ID;

	// Location
	res.location = {
		OLD: D.CAL.Loc.OLD || false,
		NEW: D.CAL.Loc.NEW,
		CHANGED: D.CAL.Loc.CHANGED || (res.from != CE.DT_FROM || res.to != CE.DT_TO)
	};

	if (D.CAL.Loc.NEW.substr(0, 5) == 'ECMR_' && D.CAL.DOM.RepeatSelect.value != 'NONE')
		return err(EC_MESS.EventMRCheckWarn);

	if (D.CAL.DOM.RepeatSelect.value != 'NONE')
	{
		var FREQ = D.CAL.DOM.RepeatSelect.value;
		res.RRULE = {
			FREQ : FREQ,
			INTERVAL : D.CAL.DOM.RepeatCount.value
		};

		if (D.CAL.DOM.RepeatDiapTo.value != EC_MESS.NoLimits)
		{
			var until = BX.parseDate(D.CAL.DOM.RepeatDiapTo.value);
			if (until && until.getTime)
				res.RRULE.UNTIL = BX.date.getServerTimestamp(until.getTime());
		}

		//if (Ob.bRepSetDiapFrom)
		//	res.per_from = res.from;
		//else
		//	res.per_from = Ob.oRepeatDiapFrom;
		if (FREQ == 'WEEKLY')
		{
			var ar = [];
			for (i = 0; i < 7; i++)
				if (D.CAL.DOM.RepeatWeekDaysCh[i].checked)
					ar.push(D.CAL.DOM.RepeatWeekDaysCh[i].value);

			if (ar.length == 0)
				delete res.RRULE;
			else
				res.RRULE.BYDAY = ar.join(',');
				//res.per_week_days = ar.join(',');
		}
	}

	// Check Meeting and Video Meeting rooms accessibility
	if (res.location.NEW.substr(0, 5) == 'ECMR_' && !Params.bLocationChecked)
	{
		this.CheckMeetingRoom(
			{
				id : res.id || 0,
				from : res.from,
				to : res.to,
				location_new : res.location.NEW,
				location_old : res.location.OLD || ''
			},
			function(check){
				if (!check)
					return alert(EC_MESS.MRReserveErr);
				if (check == 'reserved')
					return alert(EC_MESS.MRNotReservedErr);

				Params.bLocationChecked = true;
				_this.ExtendedSaveEvent(Params);
			}
		);
		return false;
	}

	// Reminders
	if (this.allowReminders)
	{
		res.remind = D.CAL.DOM.RemCheck.checked;
		res.remind_count = D.CAL.DOM.RemCount.value || '';
		res.remind_count = res.remind_count.replace(/,/g, '.');
		res.remind_count = res.remind_count.replace(/[^\d|\.]/g, '');
		res.remind_type = D.CAL.DOM.RemType.value;
	}

	// Other
	if (D.CAL.DOM.Importance)
		res.importance = D.CAL.DOM.Importance.value;

	if (D.CAL.DOM.Accessibility)
		res.accessibility = D.CAL.DOM.Accessibility.value;

	if (D.CAL.DOM.Private)
		res.private_event = D.CAL.DOM.Private.checked;

	if (!D.CAL.New)
	{
		res.id = CE.ID;
		if (CE.STATUS)
			res.status = CE.STATUS;
	}
	res.oEvent = CE;

	// Here we save form with userfields but we will post it ONLY AFTER saving main event
	if (D.CAL.DOM.UFForm)
		res.UFForm = D.CAL.DOM.UFForm;

	this.Event.Save(res);

	if (Params.callback)
		Params.callback();
}

// More events window
JCEC.prototype.ShowMoreEventsWin = function(P)
{
	var _this = this;

	if(this.MoreEventsWin)
	{
		this.MoreEventsWin.close();
		this.MoreEventsWin.destroy();
		this.MoreEventsWin = null;
		return;
	}

	this.MoreEventsWin = BX.PopupWindowManager.create(this.id + "bxc-month-sel" + P.id, P.pSelect, {
		autoHide : true,
		closeByEsc : true,
		offsetTop : -1,
		offsetLeft : 1,
		lightShadow : true,
		content : BX.create('DIV', {props:{id: 'bxc-more-' + this.id + P.id, className : 'bxec-more-event-popup'}})
	});

	BX.addCustomEvent(this.MoreEventsWin, 'onPopupClose', function(){
		if(_this.MoreEventsWin && _this.MoreEventsWin.destroy)
			_this.MoreEventsWin.destroy();
	});

	var
		pWnd = BX('bxc-more-' + this.id + P.id),
		pNewDiv, pOldDiv, i;

	BX.bind(pWnd, 'click', BX.proxy(this.EventClick, this));

	pWnd.innerHTML = "";

	for (i = 0; i < P.Events.length; i++)
	{
		pOldDiv = P.Events[i].pDiv;
		pNewDiv = pOldDiv.cloneNode(true);

		BX.addClass(pNewDiv, 'bxec-event-static');
		pNewDiv.onmouseover = pOldDiv.onmouseover;
		pNewDiv.onmouseout = pOldDiv.onmouseout;
		pNewDiv.ondblclick = pOldDiv.ondblclick;
		pWnd.appendChild(pNewDiv);
	}

	this.MoreEventsWin.show(true); // Show window
}

JCEC.prototype.GetUsableDateTime = function(timestamp, roundMin)
{
	var date = bxGetDateFromTS(timestamp);
	if (!roundMin)
		roundMin = 10;

	date.min = Math.ceil(date.min / roundMin) * roundMin;

	if (date.min == 60)
	{
		if (date.hour == 23)
			date.bTime = false;
		else
			date.hour++;
		date.min = 0;
	}

	date.oDate.setHours(date.hour);
	date.oDate.setMinutes(date.min);
	return date;
}

