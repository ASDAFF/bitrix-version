/*
 * Event list:
 * onOpenPageAfter
 * onOpenPageBefore
 * onHidePageAfter
 * onHidePageBefore
 * UIApplicationDidBecomeActiveNotification
 * onInternetStatusChange
 * onOpenPush
 * onKeyboardWillShow
 * onKeyboardWillHide
 * onKeyboardDidHide
 * onKeyboardDidShow
 *
 * */


var app = false;
/**
 * BitrixMobile constructor
 * @constructor
 */
BitrixMobile = function ()
{
	this.callbacks = {};
	this.callbackIndex = 0;
	this.dataBrigePath = "/mobile/";
	this.contacts = BMContacts();
	this.available = false;
	this.platform = null;
};


//#############################
//#####--api version 6--#######
//#############################

/**
 * Opens barcode scanner
 *
 * @example
 * app.openBarCodeScanner({
 *     callback:function(data){
 *          //handle data (example of the data  - {type:"SSD", canceled:0, text:"8293473200"})
 *     }
 * })
 * @param params The parameters
 * @param params.callback The handler
 *
 * @returns {*}
 */
BitrixMobile.prototype.openBarCodeScanner = function (params)
{
	return this.exec("openBarCodeScanner", params);
};

/**
 * Shows photo controller
 * @example
 * <pre>
 *     app.openPhotos({
 *        "photos":[
 *            {
 *                "url":"http://mysite.com/sample.jpg",
 *                "description": "description text"
 *            },
 *            {
 *                "url":"/sample2.jpg",
 *                "description": "description text 2"
 *            }
 *            ...
 *       ]
 *  });
 *  </pre>
 * @param params The parameters
 * @param {array} params.photos The array of photos
 *
 * @returns {*}
 */
BitrixMobile.prototype.openPhotos = function (params)
{
	return this.exec("openPhotos", params);
};

/**
 * Removes all application controller cache (iOS)
 * @param params The parameters. Empty yet.
 * @returns {*}
 */
BitrixMobile.prototype.removeAllCache = function (params)
{
	return this.exec("removeAllCache", params);
};

/**
 * Add the page with passed url address to navigation stack
 * @param params  The parameters
 * @param params.url The page url
 * @param [params.data] The data that will be saved for the page. Use getPageParams() to get stored data.
 * @param [params.title] The title that will be placed in the center in navigation bar
 * @returns {*}
 */
BitrixMobile.prototype.loadPageBlank = function (params)
{
	return this.exec("openNewPage", params);
};

/**
 * Loads the page as the first page in navigation chain.
 * @param params The parameters
 * @param params.url The absolute path of the page or url (http://example.com)
 * @param [params.page_id] Identifier of the page, if this parameter will defined the page will be cached.
 * @param [params.title] The title that will placed in the center of navigation bar
 * @returns {*}
 */
BitrixMobile.prototype.loadPageStart = function (params)
{
	if (params.url && !params.page_id && !params.title)
		return this.exec("loadPage", params.url);
	return this.exec("loadPage", params);
}

/**
 * shows confirm alert
 * @param params
 */
BitrixMobile.prototype.confirm = function (params)
{
	if (!this.available)
	{
		document.addEventListener("deviceready", BX.proxy(function ()
		{
			this.confirm(params)
		}, this), false);
		return;
	}

	var confirmData = {
		callback: function ()
		{
		},
		title: "",
		text: "",
		buttons: "OK"
	};
	if (params)
	{
		if (params.title)
			confirmData.title = params.title;
		if (params.buttons && params.buttons.length > 0)
		{
			confirmData.buttons = "";
			for (var i = 0; i < params.buttons.length; i++)
			{
				if (confirmData.buttons.length > 0)
				{
					confirmData.buttons += "," + params.buttons[i];
				}
				else
					confirmData.buttons = params.buttons[i];
			}
		}
		confirmData.accept = params.accept;

		if (params.text)
			confirmData.text = params.text;
		if (params.callback && typeof(params.callback) == "function")
			confirmData.callback = params.callback;
	}

	navigator.notification.confirm(
		confirmData.text,
		confirmData.callback,
		confirmData.title,
		confirmData.buttons
	);

};
/**
 * shows alert with custom title
 * @param params
 */
BitrixMobile.prototype.alert = function (params)
{

	if (!this.available)
	{
		document.addEventListener("deviceready", BX.proxy(function ()
		{
			this.alert(params)
		}, this), false);
		return;
	}

	var alertData = {
		callback: function ()
		{
		},
		title: "",
		button: "",
		text: ""
	};
	if (params)
	{
		if (params.title)
			alertData.title = params.title;
		if (params.button)
			alertData.button = params.button;
		if (params.text)
			alertData.text = params.text;
		if (params.callback && typeof(params.callback) == "function")
			alertData.callback = params.callback;
	}

	navigator.notification.alert(
		alertData.text,
		alertData.callback,
		alertData.title,
		alertData.button
	);

};

/**
 * opens left slider
 * @returns {*}
 */
BitrixMobile.prototype.openLeft = function ()
{
	return this.exec("openMenu");
};
/**
 * sets title of the current page
 * @param params
 * title - text title
 * @returns {*}
 */
BitrixMobile.prototype.setPageTitle = function (params)
{
	return this.exec("setPageTitle", params);
};

//#############################
//#####--api version 5--#######
//#############################
/**
 * removes cache of table by id
 * in next time a table appear it will be reloaded
 * @param tableId
 * @returns {*}
 */
BitrixMobile.prototype.removeTableCache = function (tableId)
{
	return this.exec("removeTableCache", {"table_id": tableId});
};

/** shows native datetime picker
 * @param params
 * @param params.format {string} date's format
 * @param params.type {string} "datetime"|"time"|"date"
 * @param params.callback {string}  The handler on date select event
 * @returns {*}
 */
BitrixMobile.prototype.showDatePicker = function (params)
{
	return this.exec("showDatePicker", params);
};

/**
 * hides native datetime picker
 * @returns {*}
 */
BitrixMobile.prototype.hideDatePicker = function ()
{

	return this.exec("hideDatePicker");
};

//#############################
//#####--api version 4--#######
//#############################

/**
 * Shows native input panel
 * @param params
 * @param {string} params.placeholder  Text for the placeholder
 * @param {string} params.button_name  Label of the button
 * @param {function} params.action Onclick-handler for the button
 * @example
 * app.showInput({
 *				placeholder:"New message...",
 *				button_name:"Send",
 *				action:function(text)
 *				{
 *					app.clearInput();
 *					alert(text);
 *				},
 *			});
 * @returns {*}
 */
BitrixMobile.prototype.showInput = function (params)
{
	return this.exec("showInput", params);
};

/**
 * use it to disable with activity indicator or enable button
 * @param {boolean} loading_status
 * @returns {*}
 */
BitrixMobile.prototype.showInputLoading = function (loading_status)
{
	if (loading_status && loading_status !== true)
		loading_status = false;
	return this.exec("showInputLoading", {"status": loading_status});

};

/**
 * clears native input
 * @returns {*}
 */
BitrixMobile.prototype.clearInput = function ()
{
	return this.exec("clearInput");
};

/**
 * hides native input
 * @returns {*}
 */
BitrixMobile.prototype.hideInput = function ()
{
	return this.exec("hideInput");
};

//#############################
//#####--api version 3--#######
//#############################

/**
 * reloads page
 * @param params
 */
BitrixMobile.prototype.reload = function (params)
{
	var params = params || {url: document.location.href};

	if (window.platform == 'android')
		this.exec('reload', params);
	else
	{
		document.location.href = params.url;
	}
};

/**
 * makes flip-screen effect
 * @returns {*}
 */
BitrixMobile.prototype.flipScreen = function ()
{
	return this.exec("flipScreen");
};

/**
 * removes buttons of the page
 * @param params
 * @param {string} params.position Position of button
 * @returns {*}
 */
BitrixMobile.prototype.removeButtons = function (params)
{
	return this.exec("removeButtons", params);
};

/**
 *
 * @param {object} params Settings of the table
 * @param {string} params.url The url to download json-data
 * @param {boolean} [params.isroot] If true the table will be opened as first screen
 * @param {object} params.TABLE_SETTINGS  Start settings of the table, it can be overwritten after download json data
 * @description TABLE_SETTINGS
 *     callback: handler on ok-button tap action, it works only when 'markmode' is true
 *     markmode: set it true to turn on mark mode, false - by default
 *     modal: if true your table will be opened in modal dialog, false - by default
 *     multiple: it works if 'markmode' is true, set it false to turn off multiple selection
 *     okname - name of ok button
 *     cancelname - name of cancel button
 *     showtitle: true - to make title visible, false - by default
 *     alphabet_index: if true - table will be divided on alphabetical sections
 *     selected: this is a start selected data in a table, for example {users:[1,2,3,4],groups:[1,2,3]}
 *     button:{
 *                name: "name",
 *                type: "plus",
 *                callback:function(){
 *                    //your code
 *                }
 *     };
 * @returns {*}
 */
BitrixMobile.prototype.openBXTable = function (params)
{
	return this.exec("openBXTable", params);
};

/**
 * Open document in separated window
 * @param params
 * @param {string} params.url  The document url
 * @example
 * app.openDocument({"url":"/upload/123.doc"});
 * @returns {*}
 */
BitrixMobile.prototype.openDocument = function (params)
{
	return this.exec("openDocument", params);
};

/**
 * Shows the small loader in the center of the screen
 * The loader will be automatically hided when "back" button pressed
 * @param params - settings
 * @param params.text The text of the loader
 * @returns {*}
 */
BitrixMobile.prototype.showPopupLoader = function (params)
{
	return this.exec("showPopupLoader", params);
};

/**
 * Hides the small loader
 * @param params The parameters
 * @returns {*}
 */
BitrixMobile.prototype.hidePopupLoader = function (params)
{
	return this.exec("hidePopupLoader", params);
};

/**
 * Changes the parameters of the current page, that can be getted by getPageParams()
 * @param params - The parameters
 * @param params.data any mixed data
 * @param {function} params.callback The callback-handler
 * @returns {*}
 */
BitrixMobile.prototype.changeCurPageParams = function (params)
{
	return this.exec("changeCurPageParams", params);
};

/**
 * Gets the parameters of the page
 * @param params The parameters
 * @param {function} params.callback The handler
 * @returns {*}
 */
BitrixMobile.prototype.getPageParams = function (params)
{

	if (!this.enableInVersion(3))
		return false;

	return this.exec("getPageParams", params);
};

/**
 * Creates the ontext menu of the page
 * @example
 * Parameters example:
 * <pre>
 *params =
 *{
*   			items:[
*				{
*					name:"Post message",
*					action:function() { postMessage();},
*					image: "/upload/post_message_icon.phg"
*				},
*				{
*					name:"To Bitrix!",
*					url:"http://bitrix.ru",
*					icon: 'settings'
*				}
*			]
 *}
 *
 * </pre>
 * @param params The parameters
 * @returns {*}
 */
BitrixMobile.prototype.menuCreate = function (params)
{
	return this.exec("menuCreate", params);
};

/**
 * Shows the context menu
 * @returns {*}
 */
BitrixMobile.prototype.menuShow = function ()
{
	return this.exec("menuShow");
};

/**
 * Hides the context menu
 * @returns {*}
 */
BitrixMobile.prototype.menuHide = function ()
{
	return this.exec("menuHide");
};

//#############################
//#####--api version 2--#######
//#############################

/**
 * Checks if it's required application version or not
 * @param ver The version of API
 * @param [strict]
 * @returns {boolean}
 */
BitrixMobile.prototype.enableInVersion = function (ver, strict)
{
	//check api version
	strict = strict == true ? true : false;

	var api_version = 1;
	try
	{
		api_version = appVersion;
	} catch (e)
	{
		//do nothing
	}

	return strict ? (parseInt(api_version) == parseInt(ver) ? true : false) : (parseInt(api_version) >= parseInt(ver) ? true : false);
};


/**
 * Checks if the page is visible in this moment
 * @param params The parameters
 * @param params.callback The handler
 * @returns {*}
 */
BitrixMobile.prototype.checkOpenStatus = function (params)
{
	return this.exec("checkOpenStatus", params);
};

BitrixMobile.prototype.asyncRequest = function (params)
{
	//native asyncRequest
	//params.url
	return this.exec("asyncRequest", params);
};

//#############################
//#####--api version 1--#######
//#############################

/**
 * Opens url in external browser
 * @param url
 * @returns {*}
 */
BitrixMobile.prototype.openUrl = function (url)
{
	//open url in external browser
	return this.exec("openUrl", url);
};

/**
 * Register a callback
 * @param {function} func The callback function
 * @returns {number}
 * @constructor
 */
BitrixMobile.prototype.RegisterCallBack = function (func)
{
	if (typeof(func) == "function")
	{
		this.callbackIndex++;

		this.callbacks["callback" + this.callbackIndex] = func;

		return this.callbackIndex;
	}

};

/**
 * Execute registered callback function by index
 * @param index The index of callback function
 * @param result The parameters that will be passed to callback as a first argument
 * @constructor
 */
BitrixMobile.prototype.CallBackExecute = function (index, result)
{
	if (this.callbacks["callback" + index] && (typeof this.callbacks["callback" + index]) === "function")
	{
		this.callbacks["callback" + index](result);
	}
};

/**
 * Generates the javascript-event
 * that can be caught by any application browsers
 * except current browser
 * @param eventName
 * @param params
 * @param where
 * @returns {*|Array|{index: number, input: string}}
 */
BitrixMobile.prototype.onCustomEvent = function (eventName, params, where)
{

	if (!this.available)
	{
		document.addEventListener("deviceready", BX.delegate(function ()
		{
			this.onCustomEvent(eventName, params, where);
		}, this), false);

		return;
	}

	params = this.prepareParams(params);
	if (typeof(params) == "object")
		params = JSON.stringify(params);

	if (device.platform.toUpperCase() == "ANDROID")
	{
		var params_pre = {
			"eventName": eventName,
			"params": params
		};
		return Cordova.exec(null, null, "BitrixMobile", "onCustomEvent", [params_pre]);
	}
	else
		return Cordova.exec("BitrixMobile.onCustomEvent", eventName, params, where);
};

/**
 * Gets javascript variable from current and left
 * @param params The parameters
 * @param params.callback The handler
 * @param params.var The variable's name
 * @param params.from The browser ("left"|"current")
 * @returns {*}
 */
BitrixMobile.prototype.getVar = function (params)
{
	return this.exec("getVar", params);
};

/**
 *
 * @param variable
 * @param key
 * @returns {*}
 */
BitrixMobile.prototype.passVar = function (variable, key)
{

	try
	{
		evalVar = window[variable];
		if (!evalVar)
			evalVar = "empty"
	}
	catch (e)
	{
		evalVar = "empty"
	}

	if (evalVar)
	{

		if (typeof(evalVar) == "object")
			evalVar = JSON.stringify(evalVar);

		if (platform.toUpperCase() == "ANDROID")
		{

			key = key || false;
			if (key)
				Bitrix24Android.receiveStringValue(JSON.stringify({variable: evalVar, key: key}));
			else
				Bitrix24Android.receiveStringValue(evalVar);
		} else
		{
			return evalVar;
		}
	}
};

BitrixMobile.prototype.prepareParams = function (params)
{
	//prepare params
	if (params && typeof(params) == "object")
	{
		for (var key in params)
		{
			if (typeof(params[key]) == "object")
				params[key] = this.prepareParams(params[key]);
			if (typeof(params[key]) == "function")
				params[key] = this.RegisterCallBack(params[key]);
			else if (params[key] === true)
				params[key] = "YES";
			else if (params[key] === false)
				params[key] = "NO";
		}
	}
	else
	{
		if (typeof(params) == "function")
			params = this.RegisterCallBack(params[key]);
		else if (params === true)
			params = "YES";
		else if (params === false)
			params = "NO";
	}

	return params;
};

BitrixMobile.prototype.exec = function (funcName, params)
{

	if (!this.available)
	{
		document.addEventListener("deviceready", BX.proxy(function ()
		{
			this.exec(funcName, params);
		}, this), false);
		return false;
	}

	if (typeof(params) != "undefined")
	{
		params = this.prepareParams(params);

		if (typeof(params) == "object")
			params = JSON.stringify(params);
	}
	else
		params = "empty";

	if (device.platform.toUpperCase() == "ANDROID")
		return Cordova.exec(null, null, "BitrixMobile", funcName, [params]);
	else
		return Cordova.exec("BitrixMobile." + funcName, params);

};

/**
 * Opens the camera/albums dialog
 * @param options The parameters
 * @param options.source  0 - albums, 1 - camera
 * @param options.callback The event handler that will be fired when the photo will have selected. Photo will be passed into the callback in base64 as a first parameter.
 */
BitrixMobile.prototype.takePhoto = function (options)
{

	//open picture dialog or camera
	//options.source 0 - albums
	//options.source 1 - camera
	//options.callback - callback handler on event selecting of photo . Photo will be passed into the callback in base64 as a first parameter.
	navigator.camera.getPicture(
		options.callback, onFail, {
			quality: (options.quality || (this.enableInVersion(2) ? 40 : 10)),
			correctOrientation: (options.correctOrientation || false),
			targetWidth: (options.targetWidth || false),
			targetHeight: (options.targetHeight || false),
			destinationType: Camera.DestinationType.FILE_URI,
			sourceType: (options.source || 0)
		});

	function onFail(data)
	{
		//error
	}
};
/**
 * Opens left screen of the slider
 * @deprecated It is deprecated. Use BitrixMobile.openLeft.
 * @see BitrixMobile.openLeft
 * @returns {*}
 */
BitrixMobile.prototype.openMenu = function ()
{
	return this.exec("openMenu");
};

/**
 * Opens page in modal dialog
 * @param options The parameters
 * @param options.url The page url
 * @returns {*}
 */
BitrixMobile.prototype.showModalDialog = function (options)
{
	return this.exec("showModalDialog", options);
};

/**
 * Closes current modal dialog
 * @param options
 * @returns {*}
 */
BitrixMobile.prototype.closeModalDialog = function (options)
{
	return this.exec("closeModalDialog", options);
};

/**
 * Closes current controller
 * @param [params] The parameters
 * @param {boolean} [params.drop] It works on <b>Android</b> only. <u>true</u> - the controller will be dropped after it has disappeared, <u>false</u> - the controller will not be dropped after it has disappeared.
 * @returns {*}
 */
BitrixMobile.prototype.closeController = function (params)
{
	return this.exec("closeController", params);
};

/**
 * Adds buttons to the navigation panel.
 * @param buttons The parameters
 * @param buttons.callback The onclick handler
 * @param buttons.type  The type of the button (plus|back|refresh|right_text|back_text|users|cart)
 * @param buttons.name The name of the button
 * @param buttons.bar_type The panel type ("toolbar"|"navbar")
 * @param buttons.position The position of the button ("left"|"right")
 * @returns {*}
 */
BitrixMobile.prototype.addButtons = function (buttons)
{
	return this.exec("addButtons", buttons);
};

/**
 * Opens the center of the slider
 * @returns {*}
 */
BitrixMobile.prototype.openContent = function ()
{
	return this.exec("openContent");
};

/**
 * Opens the left side of the slider
 * @deprecated Use closeLeft()
 * @returns {*}
 */
BitrixMobile.prototype.closeMenu = function ()
{
	return this.exec("closeMenu");
};

/**
 * Opens the page as the first page in the navigation stack
 * @deprecated Use loadStartPage(params).
 * @param url
 * @param page_id
 * @returns {*}
 */
BitrixMobile.prototype.loadPage = function (url, page_id)
{
	//open page from menu
	if (this.enableInVersion(2) && page_id)
	{
		params = {
			url: url,
			page_id: page_id
		};
		return this.exec("loadPage", params);
	}
	this.openContent();
	return this.exec("loadPage", url);
};

/**
 * Sets identifier of the page
 * @param pageID
 * @returns {*}
 */
BitrixMobile.prototype.setPageID = function (pageID)
{
	return this.exec("setPageID", pageID);
};

/**
 * Opens the new page with slider effect
 * @deprecated Use loadPageBlank(params)
 * @param url
 * @param data
 * @param title
 * @returns {*}
 */
BitrixMobile.prototype.openNewPage = function (url, data, title)
{

	if (this.enableInVersion(3))
	{
		var params = {
			url: url,
			data: data,
			title: title
		};

		return this.exec("openNewPage", params);
	}
	else
		return this.exec("openNewPage", url);
};

/**
 * Loads the page into the left side of the slider using the url
 * @deprecated
 * @param url
 * @returns {*}
 */
BitrixMobile.prototype.loadMenu = function (url)
{
	return this.exec("loadMenu", url);
};

/**
 * Opens the list
 * @deprecated Use openBXTable();
 * @param options
 * @returns {*}
 */
BitrixMobile.prototype.openTable = function (options)
{
	return this.exec("openTable", options);
};

/**
 * @deprecated Use openBXTable()
 *  <b>PLEASE, DO NOT USE IT!!!!</b>
 * It is simple wrapper of openBXTable()
 * @see BitrixMobile.openBXTable
 * @param options The parameter.
 * @returns {*}
 */
BitrixMobile.prototype.openUserList = function (options)
{
	return this.exec("openUserList", options);
};

BitrixMobile.prototype.addUserListButton = function (options)
{
	//open table controller
	//options.url
	return this.exec("addUserListButton", options);
};

BitrixMobile.prototype.pullDown = function (params)
{
	//on|off pull down action on the current page
	//params.pulltext, params.downtext, params.loadtext
	//params.callback - action on pull-down-refresh
	//params.enable - true|false
	return this.exec("pullDown", params);
};

BitrixMobile.prototype.pullDownLoadingStop = function ()
{

	return this.exec("pullDownLoadingStop");
};

/**
 * Enables or disables scroll ability of the current page
 * @param enable_status The scroll ability status
 * @returns {*}
 */
BitrixMobile.prototype.enableScroll = function (enable_status)
{
	//enable|disable scroll on the current page
	var enable_status = enable_status || false;
	return this.exec("enableScroll", enable_status);
};

/**
 * Enables or disables firing events of  hiding/showing  of soft keyboard
 * @param enable_status
 * @returns {*}
 */
BitrixMobile.prototype.enableCaptureKeyboard = function (enable_status)
{
	//enable|disable capture keyboard event on the current page
	var enable_status = enable_status || false;
	return this.exec("enableCaptureKeyboard", enable_status);
};

/**
 * Enables or disables the ability of automatic showing/hiding of the loading screen at the current page
 * when it has started or has finished loading process
 *
 * @param enable_status The ability status
 * @returns {*}
 */
BitrixMobile.prototype.enableLoadingScreen = function (enable_status)
{
	//enable|disable autoloading screen on the current page
	var enable_status = enable_status || false;
	return this.exec("enableLoadingScreen", enable_status);
};


/**
 *
 * Shows the loading screen at the page
 * @returns {*}
 */
BitrixMobile.prototype.showLoadingScreen = function ()
{
	//show loading screen
	return this.exec("showLoadingScreen");
};

/**
 * Hides the loadding screen at the page
 * @returns {*}
 */
BitrixMobile.prototype.hideLoadingScreen = function ()
{
	//hide loading screen
	return this.exec("hideLoadingScreen");
};


/**
 * Sets visibility status of the navigation bar
 * @param {boolean} visible The visibility status
 * @returns {*}
 */
BitrixMobile.prototype.visibleNavigationBar = function (visible)
{
	//visibility status of the native navigation bar
	var visible = visible || false;
	return this.exec("visibleNavigationBar", visible);
};

/**
 * Sets visibility status of the bottom bar
 * @param {boolean} visible The visibility status
 * @returns {*}
 */
BitrixMobile.prototype.visibleToolBar = function (visible)
{
	//visibility status of toolbar at the bottom
	var visible = visible || false;
	return this.exec("visibleToolBar", visible);
};

BitrixMobile.prototype.enableSliderMenu = function (enable)
{
	//lock|unlock slider menu
	var enable = enable || false;
	return this.exec("enableSliderMenu", enable);
};

BitrixMobile.prototype.setCounters = function (counters)
{
	//set counters values on the navigation bar
	//counters.messages,counters.notifications
	return this.exec("setCounters", counters);
};

BitrixMobile.prototype.setBadge = function (number)
{
	//application's badge number on the dashboard
	return this.exec("setBadge", number);
};

BitrixMobile.prototype.refreshPanelPage = function (pagename)
{
	//set counters values on the navigation bar
	//counters.messages,counters.notifications

	if (!pagename)
		pagename = "";
	var options = {
		page: pagename
	};
	return this.exec("refreshPanelPage", options);
};


/**
 * Sets page urls for the notify popup window and the messages popup window
 * @param pages
 * @returns {*}
 */
BitrixMobile.prototype.setPanelPages = function (pages)
{
	//pages for notify panel
	//pages.messages_page, pages.notifications_page,
	//pages.messages_open_empty, pages.notifications_open_empty
	return this.exec("setPanelPages", pages);
};

/**
 * Gets the token from the current device. You may use the token to send push-notifications to the device.
 * @returns {*}
 */
BitrixMobile.prototype.getToken = function ()
{
	//get device token
	var dt = "APPLE";
	if (platform != "ios")
		dt = "GOOGLE";
	params = {
		callback: function (token)
		{
			BX.proxy(
				BX.ajax.post(
					app.dataBrigePath,
					{
						mobile_action: "save_device_token",
						device_name: device.name,
						uuid: device.uuid,
						device_token: token,
						device_type: dt
					},
					function (data)
					{
					}), this);
		}
	};

	return this.exec("getToken", params);
};

/**
 * Executes a request by the check_url with Basic Authorization header
 * @param params The parameters
 * @param params.success The success javascript handler
 * @param params.check_url The check url
 * @returns {*}
 * @constructor
 */
BitrixMobile.prototype.BasicAuth = function (params)
{

	//basic autorization
	//params.success, params.check_url
	params = params || {};
	if (params.failture && typeof(params.failture) == "function")
		failture = params.failture;
	params.failture = function (data)
	{
		if (data.status == "failed")
			this.showAuthForm();
		else
			failture();
	}
	return this.exec("BasicAuth", params);
};

/**
 * Logout
 * @deprecated DO NOT USE IT ANY MORE!!!!
 * @see BitrixMobile#asyncRequest
 * @see BitrixMobile#showAuthForm
 * @returns {*}
 */
BitrixMobile.prototype.logOut = function ()
{
	//logout
	//request to mobile.data with mobile_action=logout
	if (this.enableInVersion(2))
	{
		this.asyncRequest({ url: this.dataBrigePath + "?mobile_action=logout&uuid=" + device.uuid});
		return this.exec("showAuthForm");
	}

	var xhr = new XMLHttpRequest();
	xhr.open("GET", this.dataBrigePath + "?mobile_action=logout&uuid=" + device.uuid, true);
	xhr.onreadystatechange = function ()
	{
		if (xhr.readyState == 4 && xhr.status == "200")
		{
			//console.log(xhr.responseText);
			return app.exec("showAuthForm");
		}

	}
	xhr.send(null);
};
/**
 * Get location data
 * @param options
 */
BitrixMobile.prototype.getCurrentLocation = function (options)
{

	//get geolocation data
	var geolocationSuccess;
	var geolocationError;
	if (options)
	{
		geolocationSuccess = options.onsuccess;
		geolocationError = options.onerror;
	}
	navigator.geolocation.getCurrentPosition(
		geolocationSuccess, geolocationError);
};

BitrixMobile.prototype.setVibrate = function (ms)
{
	// vibrate (ms)
	ms = ms || 500;
	navigator.notification.vibrate(parseInt(ms));
};

BitrixMobile.prototype.bindloadPageBlank = function ()
{
	//Hack for Android Platform
	document.addEventListener(
		"DOMContentLoaded",
		function ()
		{
			document.body.addEventListener(
				"click",
				function (e)
				{
					var intentLink = null;
					var hash = "__bx_android_click_detect__";
					if (e.target.tagName.toUpperCase() == "A")
						intentLink = e.target;
					else
						intentLink = BX.findParent(e.target, { tagName: "A"}, 10);

					if (intentLink && intentLink.href && intentLink.href.length > 0)
					{
						if (intentLink.href.indexOf(hash) == -1 && intentLink.href.indexOf("javascript") != 0)
						{
							if (intentLink.href.indexOf("#") == -1)
								intentLink.href += "#" + hash;
							else
								intentLink.href += "&" + hash;
						}

					}

				},
				false
			);
		},
		false
	);

};

//<--end of BitrixMobile plugin


BitrixMobile.Utils = {

	autoResizeForm: function (textarea, pageContainer, maxHeight)
	{
		if (!textarea || !pageContainer)
			return;

		var formContainer = textarea.parentNode;
		maxHeight = maxHeight || 126;

		var origTextareaHeight = (textarea.ownerDocument || document).defaultView.getComputedStyle(textarea, null).getPropertyValue("height");
		var origFormContainerHeight = (formContainer.ownerDocument || document).defaultView.getComputedStyle(formContainer, null).getPropertyValue("height");

		origTextareaHeight = parseInt(origTextareaHeight); //23
		origFormContainerHeight = parseInt(origFormContainerHeight); //51
		textarea.setAttribute("data-orig-height", origTextareaHeight);
		formContainer.setAttribute("data-orig-height", origFormContainerHeight);

		var currentTextareaHeight = origTextareaHeight;
		var hiddenTextarea = document.createElement("textarea");
		hiddenTextarea.className = "send-message-input";
		hiddenTextarea.style.height = currentTextareaHeight + "px";
		hiddenTextarea.style.visibility = "hidden";
		hiddenTextarea.style.position = "absolute";
		hiddenTextarea.style.left = "-300px";

		document.body.appendChild(hiddenTextarea);

		textarea.addEventListener("change", resize, false);
		textarea.addEventListener("cut", resizeDelay, false);
		textarea.addEventListener("paste", resizeDelay, false);
		textarea.addEventListener("drop", resizeDelay, false);
		textarea.addEventListener("keyup", resize, false);

		if (window.platform == "android")
			textarea.addEventListener("keydown", resizeDelay, false);

		function resize()
		{
			hiddenTextarea.value = textarea.value;
			var scrollHeight = hiddenTextarea.scrollHeight;
			if (scrollHeight > maxHeight)
				scrollHeight = maxHeight;

			if (currentTextareaHeight != scrollHeight)
			{
				currentTextareaHeight = scrollHeight;
				textarea.style.height = scrollHeight + "px";
				formContainer.style.height = origFormContainerHeight + (scrollHeight - origTextareaHeight) + "px";
				pageContainer.style.bottom = origFormContainerHeight + (scrollHeight - origTextareaHeight) + "px";

				if (window.platform == "android")
					window.scrollTo(0, document.documentElement.scrollHeight);
			}
		}

		function resizeDelay()
		{
			setTimeout(resize, 0);
		}

	},

	resetAutoResize: function (textarea, pageContainer)
	{

		if (!textarea || !pageContainer)
			return;

		var formContainer = textarea.parentNode;

		var origTextareaHeight = textarea.getAttribute("data-orig-height");
		var origFormContainerHeight = formContainer.getAttribute("data-orig-height");

		textarea.style.height = origTextareaHeight + "px";
		formContainer.style.height = origFormContainerHeight + "px";
		pageContainer.style.bottom = origFormContainerHeight + "px";
	},

	showHiddenImages: function ()
	{
		var images = document.getElementsByTagName("img");
		for (var i = 0; i < images.length; i++)
		{
			var image = images[i];
			var realImage = image.getAttribute("data-src");
			if (!realImage)
				continue;

			if (BitrixMobile.Utils.isElementVisibleOnScreen(image))
			{
				image.src = realImage;
				image.setAttribute("data-src", "");
			}
		}
	},

	isElementVisibleOnScreen: function (element)
	{
		var coords = BitrixMobile.Utils.getElementCoords(element);

		var windowTop = window.pageYOffset || document.documentElement.scrollTop;
		var windowBottom = windowTop + document.documentElement.clientHeight;

		coords.bottom = coords.top + element.offsetHeight;

		var topVisible = coords.top > windowTop && coords.top < windowBottom;
		var bottomVisible = coords.bottom < windowBottom && coords.bottom > windowTop;

		return topVisible || bottomVisible;
	},

	isElementVisibleOn2Screens: function (element)
	{
		var coords = BitrixMobile.Utils.getElementCoords(element);

		var windowHeight = document.documentElement.clientHeight;
		var windowTop = window.pageYOffset || document.documentElement.scrollTop;
		var windowBottom = windowTop + windowHeight;

		coords.bottom = coords.top + element.offsetHeight;

		windowTop -= windowHeight;
		windowBottom += windowHeight;

		var topVisible = coords.top > windowTop && coords.top < windowBottom;
		var bottomVisible = coords.bottom < windowBottom && coords.bottom > windowTop;

		return topVisible || bottomVisible;

	},

	getElementCoords: function (element)
	{
		var box = element.getBoundingClientRect();

		return {
			originTop: box.top,
			originLeft: box.left,
			top: box.top + window.pageYOffset,
			left: box.left + window.pageXOffset
		};
	}
};

BMContacts = function ()
{
	if (!navigator.contacts)
		return false;
};

BMContacts.prototype.AddContact = function (fields, callback)
{

	//add contact to device address book
	var contact = navigator.contacts.create();
	var phoneNumbers = [];
	phoneNumbers[0] = new ContactField('work', fields.phone_number.work, false);
	phoneNumbers[1] = new ContactField('mobile', fields.phone_number.mobile, true); // preferred number
	phoneNumbers[2] = new ContactField('home', fields.phone_number.home, false);
	//email
	var emails = [];
	emails[0] = new ContactField('work', fields.email, true);

	var photos = [];
	photos[0] = new ContactField("url", fields.photo, true);
	//contact name
	var name = new ContactName();
	name.givenName = fields.firstname;
	name.familyName = fields.secondname;
	contact.name = name;

	contact.name = name;
	contact.photos = photos;
	contact.phoneNumbers = phoneNumbers;
	contact.emails = emails;
	callback = callback ||
		function ()
		{
			alert("User is added to contact list!")
		};
	contact.save(
		callback, function ()
		{ //error
		});
};

BMContacts.prototype.FindContact = function (filter, callback)
{

	//find contact in device address book
	var options = new ContactFindOptions();
	options.filter = filter;

	var fields = ["displayName", "name"];
	navigator.contacts.find(fields, callback, function ()
	{
	}, options);
};

function ReadyDevice(func)
{
	document.addEventListener("deviceready", func, false);
};


var BitrixAnimation = {

	animate: function (options)
	{
		if (!options || !options.start || !options.finish ||
			typeof(options.start) != "object" || typeof(options.finish) != "object"
			)
			return null;

		for (var propName in options.start)
		{
			if (!options.finish[propName])
			{
				delete options.start[propName];
			}
		}

		options.progress = function (progress)
		{
			var state = {};
			for (var propName in this.start)
				state[propName] = Math.round(this.start[propName] + (this.finish[propName] - this.start[propName]) * progress);

			if (this.step)
				this.step(state);
		};

		return BitrixAnimation.animateProgress(options);
	},

	animateProgress: function (options)
	{
		var start = new Date();
		var delta = options.transition || BitrixAnimation.transitions.linear;
		var duration = options.duration || 1000;

		var timer = setInterval(function ()
		{

			var progress = (new Date() - start) / duration;
			if (progress > 1)
				progress = 1;

			options.progress(delta(progress));

			if (progress == 1)
			{
				clearInterval(timer);
				options.complete && options.complete();
			}

		}, options.delay || 13);

		return timer;
	},

	makeEaseInOut: function (delta)
	{
		return function (progress)
		{
			if (progress < 0.5)
				return delta(2 * progress) / 2;
			else
				return (2 - delta(2 * (1 - progress))) / 2;
		}
	},

	makeEaseOut: function (delta)
	{
		return function (progress)
		{
			return 1 - delta(1 - progress);
		};
	},

	transitions: {

		linear: function (progress)
		{
			return progress;
		},

		elastic: function (progress)
		{
			return Math.pow(2, 10 * (progress - 1)) * Math.cos(20 * Math.PI * 1.5 / 3 * progress);
		},

		quad: function (progress)
		{
			return Math.pow(progress, 2);
		},

		cubic: function (progress)
		{
			return Math.pow(progress, 3);
		},

		quart: function (progress)
		{
			return Math.pow(progress, 4);
		},

		quint: function (progress)
		{
			return Math.pow(progress, 5);
		},

		circ: function (progress)
		{
			return 1 - Math.sin(Math.acos(progress));
		},

		back: function (progress)
		{
			return Math.pow(progress, 2) * ((1.5 + 1) * progress - 1.5);
		},

		bounce: function (progress)
		{
			for (var a = 0, b = 1; 1; a += b, b /= 2)
			{
				if (progress >= (7 - 4 * a) / 11)
				{
					return -Math.pow((11 - 6 * a - 11 * progress) / 4, 2) + Math.pow(b, 2);
				}
			}
		}
	}
};
document.addEventListener("deviceready", function ()
{
	app.available = true;

}, false);
app = new BitrixMobile;
window.app = app;

MobileAjaxWrapper = function ()
{
	this.type = null;
	this.method = null;
	this.url = null;
	this.callback = null;
	this.failure_callback = null;
	this.progress_callback = null;
	this.offline = null;
	this.processData = null;
	this.xhr = null;
};

MobileAjaxWrapper.prototype.Init = function (params)
{
	if (params.type != 'json')
		params.type = 'html';

	if (params.method != 'POST')
		params.method = 'GET';

	if (params.processData == 'undefined')
		params.processData = true;

	this.type = params.type;
	this.method = params.method;
	this.url = params.url;
	this.data = params.data;
	this.processData = params.processData;
	this.callback = params.callback;

	if (params.callback_failure != 'undefined')
		this.failure_callback = params.callback_failure;
	if (params.callback_progress != 'undefined')
		this.progress_callback = params.callback_progress;
	if (params.callback_loadstart != 'undefined')
		this.loadstart_callback = params.callback_loadstart;
	if (params.callback_loadend != 'undefined')
		this.loadend_callback = params.callback_loadend;
}

MobileAjaxWrapper.prototype.Wrap = function (params)
{
	this.Init(params);

	if (this.offline === true)
	{
		this.failure_callback();
		this.OfflineAlert();
		return;
	}

	this.xhr = BX.ajax({
		'timeout': 30,
		'method': this.method,
		'dataType': this.type,
		'url': this.url,
		'data': this.data,
		'processData': this.processData,
		'onsuccess': BX.delegate(
			function (response)
			{
				if (this.type == 'json')
					var bFailed = (response.status == 'failed');
				else if (this.type == 'html')
					var bFailed = (response == '{"status":"failed"}');

				if (bFailed)
				{
					app.BasicAuth({
						'success': BX.delegate(
							function (auth_data)
							{
								this.data.sessid = auth_data.sessid_md5;
								this.xhr = BX.ajax({
									'timeout': 30,
									'method': this.method,
									'dataType': this.type,
									'url': this.url,
									'data': this.data,
									'onsuccess': BX.delegate(
										function(response_ii)
										{
											if (this.type == 'json')
												var bFailed = (response_ii.status == 'failed');
											else if (this.type == 'html')
												var bFailed = (response_ii == '{"status":"failed"}');

											if (bFailed)
												this.failure_callback();
											else
												this.callback(response_ii);
										},
										this
									),
									'onfailure': BX.delegate( function() { this.failure_callback(); }, this)
								});
							},
							this
						),
						'failture': BX.delegate(function ()
						{
							this.failure_callback();
						}, this)
					});
				}
				else
					this.callback(response);
			},
			this
		),
		'onfailure': BX.delegate(function ()
		{
			this.failure_callback();
		}, this)
	});

	if (this.progress_callback != null)
		BX.bind(this.xhr, "progress", this.progress_callback);

	if (this.load_callback != null)
		BX.bind(this.xhr, "load", this.load_callback);

	if (this.loadstart_callback != null)
		BX.bind(this.xhr, "loadstart", this.loadstart_callback);

	if (this.loadend_callback != null)
		BX.bind(this.xhr, "loadend", this.loadend_callback);

	if (this.error_callback != null)
		BX.bind(this.xhr, "error", this.error_callback);

	if (this.abort_callback != null)
		BX.bind(this.xhr, "abort", this.abort_callback);
}

MobileAjaxWrapper.prototype.OfflineAlert = function (callback)
{
	navigator.notification.alert(BX.message('MobileAppOfflineMessage'), (callback || BX.DoNothing), BX.message('MobileAppOfflineTitle'));
}

BMAjaxWrapper = new MobileAjaxWrapper;

document.addEventListener("offline", function ()
{
	BMAjaxWrapper.offline = true;
}, false);
document.addEventListener("online", function ()
{
	BMAjaxWrapper.offline = false;
}, false);

document.addEventListener('DOMContentLoaded', function ()
{
	BX.addCustomEvent("UIApplicationDidBecomeActiveNotification", function (params)
	{
		var networkState = navigator.network.connection.type;
		BMAjaxWrapper.offline = (networkState == Connection.UNKNOWN || networkState == Connection.NONE);
	});
}, false);

(function ()
{

	function addListener(el, type, listener, useCapture)
	{
		if (el.addEventListener)
		{
			el.addEventListener(type, listener, useCapture);
			return {
				destroy: function ()
				{
					el.removeEventListener(type, listener, useCapture);
				}
			};
		} else
		{
			var handler = function (e)
			{
				listener.handleEvent(window.event, listener);
			}
			el.attachEvent('on' + type, handler);

			return {
				destroy: function ()
				{
					el.detachEvent('on' + type, handler);
				}
			};
		}
	}

	var isTouch = true;

	/* Construct the FastButton with a reference to the element and click handler. */
	this.FastButton = function (element, handler, useCapture)
	{
		// collect functions to call to cleanup events
		this.events = [];
		this.touchEvents = [];
		this.element = element;
		this.handler = handler;
		this.useCapture = useCapture;
		if (isTouch)
			this.events.push(addListener(element, 'touchstart', this, this.useCapture));
		this.events.push(addListener(element, 'click', this, this.useCapture));
	};

	/* Remove event handling when no longer needed for this button */
	this.FastButton.prototype.destroy = function ()
	{
		for (i = this.events.length - 1; i >= 0; i -= 1)
			this.events[i].destroy();
		this.events = this.touchEvents = this.element = this.handler = this.fastButton = null;
	};

	/* acts as an event dispatcher */
	this.FastButton.prototype.handleEvent = function (event)
	{
		switch (event.type)
		{
			case 'touchstart':
				this.onTouchStart(event);
				break;
			case 'touchmove':
				this.onTouchMove(event);
				break;
			case 'touchend':
				this.onClick(event);
				break;
			case 'click':
				this.onClick(event);
				break;
		}
	};


	this.FastButton.prototype.onTouchStart = function (event)
	{
		event.stopPropagation ? event.stopPropagation() : (event.cancelBubble = true);
		this.touchEvents.push(addListener(this.element, 'touchend', this, this.useCapture));
		this.touchEvents.push(addListener(document.body, 'touchmove', this, this.useCapture));
		this.startX = event.touches[0].clientX;
		this.startY = event.touches[0].clientY;
	};


	this.FastButton.prototype.onTouchMove = function (event)
	{
		if (Math.abs(event.touches[0].clientX - this.startX) > 10 || Math.abs(event.touches[0].clientY - this.startY) > 10)
		{
			this.reset(); //if he did, then cancel the touch event
		}
	};


	this.FastButton.prototype.onClick = function (event)
	{
		event.stopPropagation ? event.stopPropagation() : (event.cancelBubble = true);
		this.reset();

		var result = this.handler.call(this.element, event);
		if (event.type == 'touchend')
			clickbuster.preventGhostClick(this.startX, this.startY);
		return result;
	};

	this.FastButton.prototype.reset = function ()
	{
		for (i = this.touchEvents.length - 1; i >= 0; i -= 1)
			this.touchEvents[i].destroy();
		this.touchEvents = [];
	};

	this.clickbuster = function ()
	{
	}

	this.clickbuster.preventGhostClick = function (x, y)
	{
		clickbuster.coordinates.push(x, y);
		window.setTimeout(clickbuster.pop, 2500);
	};

	this.clickbuster.pop = function ()
	{
		clickbuster.coordinates.splice(0, 2);
	};


	this.clickbuster.onClick = function (event)
	{
		for (var i = 0; i < clickbuster.coordinates.length; i += 2)
		{
			var x = clickbuster.coordinates[i];
			var y = clickbuster.coordinates[i + 1];
			if (Math.abs(event.clientX - x) < 25 && Math.abs(event.clientY - y) < 25)
			{
				event.stopPropagation ? event.stopPropagation() : (event.cancelBubble = true);
				event.preventDefault ? event.preventDefault() : (event.returnValue = false);
			}
		}
	};

	if (isTouch)
	{
		document.addEventListener('click', clickbuster.onClick, true);
		clickbuster.coordinates = [];
	}
})(this);