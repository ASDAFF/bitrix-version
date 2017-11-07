// Aurigma Image Uploader Dual 6.x - IUEmbed Script Library
// Version 6.0 Feb 21, 2009
// Copyright(c) Aurigma Inc. 2002-2009

/// <reference path="iuembed.Intellisense.js" />


//--------------------------------------------------------------------------
//IUCommon class
//--------------------------------------------------------------------------

IUCommon = {
    browser: new function() {
        var a = navigator.userAgent.toLowerCase();
        this.isOpera = (a.indexOf("opera") > -1);
        this.isKonq = (a.indexOf("konqueror") > -1);
        this.isChrome = (a.indexOf("chrome") > -1);
        this.isSafari = (a.indexOf("safari") > -1) && !this.isChrome;
        this.isKhtml = this.isSafari || this.isKonq || this.isChrome;
        this.isIE = (a.indexOf("msie") != -1) && !this.isOpera;
        this.isIE6XPSP2 = this.isIE && (a.indexOf("sv1") > -1);
        this.isIE7 = this.isIE && (a.indexOf("msie 7.0") > -1);
        this.isIE8 = this.isIE && (a.indexOf("msie 8.0") > -1);
        this.isBeforeIE6XPSP2 = this.isIE && !this.isIE6XPSP2 && !this.isIE7 && !this.isIE8;
        this.isWinIE = this.isIE && (a.indexOf('mac') == -1);
        this.isIE64 = this.isIE && (a.indexOf('win64') > -1);

        this.isWindowsOS = (navigator.platform.indexOf("Win") > -1);
    },

    createDelegate: function(instance, method) {
        /// <summary>Creates delegate for object instance method.</summary>
        /// <param name="instance" type="Object">An object (context) reference.</param>
        /// <param name="method" type="Function">A method (function) reference.</param>		
        /// <returns type="Function" />		

        return function() { return method.apply(instance, arguments); };
    },

    formatString: function(str) {
        /// <summary>Formats string.</summary>
        /// <param name="str" type="String">A string to format.</param>
        /// <returns type="String" />		

        var p = /\{\d+\}/g;
        var a = arguments;
        return str.replace(p, function(capture) { return a[new Number(capture.match(/\d+/)) + 1]; });
    },

    showWarning: function(message, level) {
        /// <summary>Shows warning message.</summary>
        /// <param name="message" type="String">A message to show.</param>

        if (level == undefined || level <= IUCommon.debugLevel) {
            alert("IUEmbed Warning:\n\r" + message);
        }
    },

    checkIfFileExists: function(url) {
        /// <summary>Check if file exists.</summary>
        /// <param name="message" type="String">URL to file.</param>
        url = url.split("#")[0];

        var xmlhttp;
        if (typeof XMLHttpRequest != 'undefined') {
            xmlhttp = new XMLHttpRequest();
        }
        else {
            try {
                xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
            } catch (e) {
                try {
                    xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
                } catch (E) {
                    xmlhttp = false;
                }
            }
        }
        if (xmlhttp) {
            try {
                xmlhttp.open("GET", url, false);
                xmlhttp.send(null);
                //if file not found - return false, otherwise - true
                return (xmlhttp.status == 404) ? false : true;
            }
            catch (e) { }
        }
        //if we can't make request assume file exists by default
        return true;
    },

    //based on Java Deployment Toolkit script
    javaDetector: {
        // mime-type of the DeployToolkit plugin object
        oldMimeType: 'application/npruntime-scriptable-plugin;DeploymentToolkit',
        mimeType: 'application/java-deployment-toolkit',
        list: null,

        getJREs: function() {
            if (this.list == null) {
                var list = [];

                var ua = navigator.userAgent.toLowerCase();
                if (this.isPluginInstalled()) {
                    var plugin = this.getPlugin();
					var jvms = plugin.jvms;
					var len = jvms.getLength();
                    for (var i = 0; i < len; i++) {
                        var ver = "" + jvms.get(i).version;
                        ver = ver.split("_")[0].replace(",", ".");
                        list.push(ver);
                        this.list = list;
                    }
                } else {
                    if (IUCommon.browser.isOpera) { // Opera
                        var a = window.java, c;
                        try {
                            if (typeof a != 'undefined' && typeof a.lang != "undefined" && typeof a.lang.System != "undefined") {
                                var ver = "" + a.lang.System.getProperty("java.version");
                                ver = ver.split("_")[0].replace(",", ".");
                                list.push(ver);
                            }
                        } catch (c) { }
                    } else if (IUCommon.browser.isSafari && !IUCommon.browser.isWindowsOS) { // Safari on Mac
                        if (this.testUsingPluginsArray('1.8.0') || this.testUsingMimeTypes('1.8.0')) {
                            list.push('1.8.0');
                        } else if (this.testUsingPluginsArray('1.7.0') || this.testUsingMimeTypes('1.7.0')) {
                            list.push('1.7.0');
                        } else if (this.testUsingPluginsArray('1.6.0') || this.testUsingMimeTypes('1.6.0')) {
                            list.push('1.6.0');
                        } else if (this.testUsingPluginsArray('1.5.0') || this.testUsingMimeTypes('1.5.0')) {
                            list.push('1.5.0');
                        } else if (this.testUsingPluginsArray('1.4.2') || this.testUsingMimeTypes('1.4.2')) {
                            list.push('1.4.2');
                        }
                    } else if (IUCommon.browser.isWinIE) { // IE
                        if (this.testUsingActiveX('1.8.0')) {
                            list.push('1.8.0');
                        } else if (this.testUsingActiveX('1.7.0')) {
                            list.push('1.7.0');
                        } else if (this.testUsingActiveX('1.6.0')) {
                            list.push('1.6.0');
                        } else if (this.testUsingActiveX('1.5.0')) {
                            list.push('1.5.0');
                        } else if (this.testUsingActiveX('1.4.2')) {
                            list.push('1.4.2');
                        } else if (this.testForMSVM()) {
                            list.push('1.1');
                        }
                    } else if (IUCommon.browser.isChrome || ua.indexOf('firefox') != -1 || ua.indexOf('mozilla') != -1) { //Chrome, Mozilla family
                        if (this.testUsingMimeTypes('1.8')) {
                            list.push('1.8.0');
                        } else if (this.testUsingMimeTypes('1.7')) {
                            list.push('1.7.0');
                        } else if (this.testUsingMimeTypes('1.6')) {
                            list.push('1.6.0');
                        } else if (this.testUsingMimeTypes('1.5')) {
                            list.push('1.5.0');
                        } else if (this.testUsingMimeTypes('1.4.2')) {
                            list.push('1.4.2');
                        }
                    } else { // Others browsers
                        return null;
                    }
                }
                this.list = list;
                return list;
            } else {
                return this.list;
            }
        },

        isPluginInstalled: function() {
            var plugin = this.getPlugin();
            if (plugin && plugin.jvms) {
                return true;
            } else {
                return false;
            }
        },

        getPlugin: function() {
            var ret = document.getElementById('deployJavaPlugin');
            return ret;
        },

        testUsingActiveX: function(version) {
            var objectName = 'JavaWebStart.isInstalled.' + version + '.0';

            try {
                return (new ActiveXObject(objectName) != null);
            } catch (exception) {
                return false;
            }
        },

        testForMSVM: function() {
            var clsid = '{08B0E5C0-4FCB-11CF-AAA5-00401C608500}';

            if (typeof oClientCaps != 'undefined') {
                var v = oClientCaps.getComponentVersion(clsid, "ComponentID");
                if ((v == '') || (v == '5,0,5000,0')) {
                    return false;
                } else {
                    return true;
                }
            } else {
                return false;
            }
        },

        testUsingMimeTypes: function(version) {
            var t = [];
            for (var i = 0; i < navigator.mimeTypes.length; ++i) {
                s = navigator.mimeTypes[i].type;
                t.push(s);
                var m = s.match(/^application\/x-java-applet\x3Bversion=(1\.8|1\.7|1\.6|1\.5|1\.4\.2)$/);
                if (m != null) {
                    if (this.compareVersions(m[1], version)) {
                        return true;
                    }
                }
            }
            return false;
        },

        testUsingPluginsArray: function(version) {
            var m, s;
            for (var i = 0; i < navigator.plugins.length; ++i) {
                var p = navigator.plugins[i];
                s = p.description;

                m = s.match(/^Java (1\.4\.2|1\.5|1\.6|1\.7|1\.8).* Plug-in/);
                if (m != null) {
                    if (this.compareVersions(m[1], version)) return true;
                }

                /*if (s.search(/^Java Switchable Plug-in/) != -1) {
                return true;
                }*/
            }
            return false;
        },

        // return true if 'installed' (considered as a JRE version string) is
        // greater than or equal to 'required' (again, a JRE version string).
        compareVersions: function(installed, required) {

            var a = installed.split('.');
            var b = required.split('.');

            for (var i = 0; i < a.length; ++i) {
                a[i] = Number(a[i]);
            }
            for (var i = 0; i < b.length; ++i) {
                b[i] = Number(b[i]);
            }
            if (a.length == 2) {
                a[2] = 0;
            }

            if (a[0] > b[0]) return true;
            if (a[0] < b[0]) return false;

            if (a[1] > b[1]) return true;
            if (a[1] < b[1]) return false;

            if (a[2] > b[2]) return true;
            if (a[2] < b[2]) return false;

            return true;
        },

        writePluginTag: function() {
            if (IUCommon.browser.isIE && this.isActiveXEnabled) {
                document.write('<' +
                'object classid="clsid:CAFEEFAC-DEC7-0000-0000-ABCDEFFEDCBA" ' +
                'id="deployJavaPlugin" width="0" height="0">' +
                '<' + '/' + 'object' + '>');
            } else if (IUCommon.browser.isSafari || IUCommon.browser.isOpera) {
                //don't have plugin for Safari or Opera
            } else {
                this.writeEmbedTag();
            }
        },

        writeEmbedTag: function() {
            var written = false;
            if (navigator.mimeTypes != null) {
                for (var i = 0; i < navigator.mimeTypes.length; i++) {
                    if (navigator.mimeTypes[i].type == this.mimeType) {
                        if (navigator.mimeTypes[i].enabledPlugin) {
                            document.write('<' +
                            'embed id="deployJavaPlugin" type="' +
                            this.mimeType + '" hidden="true" />');
                            written = true;
                        }
                    }
                }
                // if we ddn't find new mimeType, look for old mimeType
                if (!written) for (var i = 0; i < navigator.mimeTypes.length; i++) {
                    if (navigator.mimeTypes[i].type == this.oldMimeType) {
                        if (navigator.mimeTypes[i].enabledPlugin) {
                            document.write('<' +
                            'embed id="deployJavaPlugin" type="' +
                            this.oldMimeType + '" hidden="true" />');
                        }
                    }
                }
            }
        },

        do_initialize: function() {
            //check if ActiveX enabled
            if (typeof ActiveXObject != 'undefined') {
                try {
                    xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
                    this.isActiveXEnabled = true;
                } catch (e) {
                    try {
                        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
                        this.isActiveXEnabled = true;
                    }
                    catch (e1) {
                        this.isActiveXEnabled = false;
                    }
                }
            } else {
                this.isActiveXEnabled = false;
            }

            this.writePluginTag();
        }
    },

    debugLevel: 2 //0 - None, 1 - Critical Only, 2 - Full
}

IUCommon.javaDetector.do_initialize();

//--------------------------------------------------------------------------
//IULocalization class
//--------------------------------------------------------------------------

IULocalization = {
    addParams: function(writer) {
        for (var name in this.ImageUploaderWriter) {
            writer[name] = this.ImageUploaderWriter[name];
        }

        for (var name in this.ImageUploader) {
            if (writer.getParam(name) == undefined) {
                writer.addParam(name, this.ImageUploader[name])
            }
        }
    }
}


//--------------------------------------------------------------------------
//IUCommon.StringBuilder class
//--------------------------------------------------------------------------

IUCommon.StringBuilder = function() {
    /// <summary>Represents a mutable string of characters.</summary>	

    this._s = new Array();
}

IUCommon.StringBuilder.prototype = {
    add: function(s) {
        /// <summary>Appends a copy of the specified string to the end of this instance.</summary>	
        /// <param name="s" type="String">A string to append.</param>

        this._s.push(s);
    },

    addScriptBegin: function() {
        /// <summary>Appends the beginning of HTML [script] tag.</summary>

        this.add("<" + "script type=\"text/javascript\">");
    },

    addScriptEnd: function() {
        /// <summary>Appends the end of HTML [script] tag.</summary>

        this.add("<" + "/script>");

    },

    addCssAttr: function(name, value) {
        /// <summary>Appends a CSS attribute.</summary>	
        /// <param name="name" type="String">CSS attribute name.</param>
        /// <param name="value" type="String">CSS attribute value.</param>		

        this.add("" + name + ":" + value + ";");
    },

    addCssClass: function(name) {
        /// <summary>Appends a CSS class name.</summary>	
        if (name) {
            this.add(" class=\"" + name + "\"");
        }
    },

    toString: function() {
        /// <summary>Returns a result string.</summary>	

        return this._s.join("");
    }
}


//--------------------------------------------------------------------------
//BaseWriter class
//--------------------------------------------------------------------------

function BaseWriter(id, width, height) {
    this._params = new Object();
    this._events = new Object();
    this._extenders = new Array();
    this._autoCallback = 0;

    //Public

    this.id = (id == undefined) ? "" : id;
    this.width = (width == undefined) ? 400 : width;
    this.height = (height == undefined) ? 300 : height;

    this.activeXControlEnabled = true;
    this.activeXControlVersion = "";

    this.javaAppletEnabled = true;
    this.javaAppletCodeBase = "./";
    this.javaAppletCached = true;
    this.javaAppletVersion = "";

    this.browserNotSupported = "Your browser is not supported.";

    this.fullPageLoadListenerName = null;

    this.javaVersionRequired = "1.5.0";
}


BaseWriter.prototype = {
    _slasher: function(v) {
        return IUCommon.browser.isWinIE && this.activeXControlEnabled ? v : new String(v).replace(/\n/gi, "\\n");
    },

    _getListenerName: function(name) {
        return this._events[name].length == 1 ? this._events[name][0] : this.id + "_" + name + "_Caller";
    },

    _getObjectParamHtml: function(name, value) {
        return "<param name=\"" + name + "\" value=\"" + this._slasher(value) + "\" />";
    },

    _addObjectParamsHtml: function(sb) {
        for (var name in this._params) {
            sb.add(this._getObjectParamHtml(name, this._params[name]));
        }
    },

    _addObjectEventsHtml: function(sb) {
        for (var name in this._events) {
            sb.add(this._getObjectParamHtml(name + "Listener", this._getListenerName(name)));
        }
    },

    _getEmbedParamHtml: function(name, value) {
        return " " + name + "=\"" + this._slasher(value) + "\"";
    },

    _addEmbedParamsHtml: function(sb) {
        for (var name in this._params) {
            sb.add(this._getEmbedParamHtml(name, this._params[name]));
        }
    },

    _addEmbedEventsHtml: function(sb) {
        for (var name in this._events) {
            sb.add(this._getEmbedParamHtml(name + "Listener", this._getListenerName(name)));
        }
    },

    _addInstructionsHtml: function(sb) {
        if (this.instructionsEnabled) {
            sb.add(this.instructionsCommon);
            var isXPSP2 = (window.navigator.userAgent.indexOf("SV1") != -1) || (window.navigator.userAgent.indexOf("MSIE 7.0") != -1);
            var isVista = (window.navigator.userAgent.indexOf("Windows NT 6.0") != -1) && (window.navigator.userAgent.indexOf("MSIE 7.0") != -1);
            if (isVista) {
                sb.add(this.instructionsVista);
            }
            else if (isXPSP2) {
                sb.add(this.instructionsWinXPSP2);
            }
            else {
                sb.add(this.instructionsNotWinXPSP2);
            }
            sb.add(this.instructionsCommon2);
        }
    },

    _validateParam: function(name, value) {
        //Validate using Intellisense classes
        if (this.controlClass) {
            var c = window[this.controlClass];
            if (c && !c._params[name]) {
                IUCommon.showWarning("Parameter '" + name + "' specified in addParam method either doesn't exist or isn't allowed during control initialization.", 2);
            }
        }

        return true;
    },

    _validateParams: function() {

    },

    _getEventSignature: function(name) {
        if (name == "InitComplete" || name == "FullPageLoad") {
            return { params: "", returns: false }
        }
        else {
            return null;
        }
    },

    _createExpandoMethods: function() {
        var o = document.getElementById(this.id);
        var props = new Array();
        for (propName in o) {
            var c = propName.charAt(0);
            if (c == c.toUpperCase()) {
                props.push(propName);
            }
        }

        var createIndexedProperty = function(obj, propName) {
            obj["set" + propName] = function(i, v) { this[propName](i) = v; };
            obj["get" + propName] = function(i) { return this[propName](i); };
        };

        var createProperty = function(obj, propName) {
            obj["set" + propName] = function(v) { this[propName] = v; };
            obj["get" + propName] = function() { return this[propName]; };
        };

        for (i = 0; i < props.length; i++) {
            //Check whether property is indexed
            if (typeof (o[props[i]]) == "unknown") {
                createIndexedProperty(o, props[i]);
            }
            else {
                createProperty(o, props[i]);
            }
        }
    },

    _getObjectHtml: function() {
        //For backward compatibility
        if (this.fullPageLoadListenerName) {
            this.addEventListener("FullPageLoad", this.fullPageLoadListenerName);
        }

        var self = this;

        var ax = IUCommon.browser.isWinIE && this.activeXControlEnabled &&
			!IUCommon.browser.isIE64 /*64-bit IE doesn't work with activex version*/;

        var sb = new IUCommon.StringBuilder();

        var icln = null;

        if (ax || this._events["FullPageLoad"]) {
            icln = "__" + this.id + "_InitComplete";

            //page load flag
            var _pageLoaded = "__" + this.id + "_pageLoaded";
            //control load flag
            var _controlLoaded = "__" + this.id + "_controlLoaded";
            //FullPageLoad handler name
            var _fireFullPageLoad = "__fire_" + this.id + "_fullPageLoad";

            //InitComplete handler
            window[icln] = function() {
                if (ax) {
                    //create expand methods
                    IUCommon.createDelegate(self, self._createExpandoMethods)();
                }
                if (self._events["FullPageLoad"]) {
                    //set control loaded flag
                    window[_controlLoaded] = true;
                    //call FullPageLoad handler
                    window[_fireFullPageLoad]();
                }
            };
            this.addEventListener("InitComplete", icln);

            if (this._events["FullPageLoad"]) {
                // add full page load handlers
                window[_pageLoaded] = !!window[_pageLoaded];
                window[_controlLoaded] = false;

                var pageLoadHandlers = [];
                //save fullpageload handlers to temp array
                //don't pass this._events["FullPageLoad"] directly!
                for (var h in this._events["FullPageLoad"])
                    pageLoadHandlers.push(this._events["FullPageLoad"][h]);


                //FullPageLoad handler function
                window[_fireFullPageLoad] = function(handlers) {
                    return function() {
                        //continue if page loaded and control loaded
                        if (window[_pageLoaded] && window[_controlLoaded]) {
                            //call control FullPageLoad handlers
                            for (var z = 0; z < handlers.length; z++) {
                                if (typeof handlers[z] === "function")
                                    handlers[z]();
                                else
                                    eval(handlers[z] + "();");
                            }
                        }
                    };
                }(pageLoadHandlers);

                //load handler for page
                var pageLoadCode = function() {
                    //set page loaded flag
                    window[_pageLoaded] = true;
                    //call FullPageLoad handler
                    window[_fireFullPageLoad]();
                };
                //add page load handler to page
                if (IUCommon.browser.isWinIE) {
                    window.attachEvent("onload", pageLoadCode);
                }
                else {
                    var r = window.addEventListener ? window : (document.addEventListener ? document : null);
                    if (r)
                        r.addEventListener("load", pageLoadCode, false);
                }
            }
        }

        //ActiveX control
        if (ax) {
            var v = this.activeXControlVersion.replace(/\./g, ",")
            var cb = this.activeXControlCodeBase + (v == "" ? "" : "#version=" + v);
            //check if CAB archive exists
            //only if Full debug level (2) enabled
            if (IUCommon.debugLevel > 1 && !IUCommon.checkIfFileExists(cb)) {
                IUCommon.showWarning("Image Uploader arhive not found.\nPlease ensure \"ActiveXControlCodeBase\" property is correct.", 1);
            }

            //Event handlers
            var e = this._events;
            for (var name in this._events) {
                if (name == "FullPageLoad") {
                    continue;
                }
                var sgn = this._getEventSignature(name);
                sb.add("<" + "script for=\"" + this.id + "\" event=\"" + name + "(" + sgn.params + ")\" type=\"text/javascript\">");
                for (var z = 0; z < this._events[name].length; z++) {
                    //We return value from one event listener only
                    if (sgn.returns && (z == this._events[name].length - 1)) {
                        sb.add("return ");
                    }
                    sb.add(this._events[name][z] + "(" + sgn.params + ");");
                }
                sb.addScriptEnd();
            }

            sb.add("<object id=\"" + this.id + "\" name=\"" + this.id + "\" classid=\"clsid:" + this.activeXClassId + "\" codebase=\"" + cb + "\" width=\"" + this.width + "\" height=\"" + this.height + "\">");
            this._addInstructionsHtml(sb);
            this._addObjectParamsHtml(sb);
            sb.add("</object>");
        }
        //Java appplet
        else if (this.javaAppletEnabled) {

            //check if JAR file exists if debugLevel != none
            //only if Full debug level (2) enabled
            var exist = true; ;
            //try codebase+jarfilename path
            if (IUCommon.debugLevel > 1) {
                var url = this.javaAppletCodeBase;
                if (url.lastIndexOf("/") == url.length - 1 && this.javaAppletJarFileName.indexOf("/") == 0)
                    url = url.slice(0, url.length - 1);
                else if (url.lastIndexOf("/") != url.length - 1 && this.javaAppletJarFileName.indexOf("/") != 0)
                    url += "/";
                url += this.javaAppletJarFileName;

                exist = IUCommon.checkIfFileExists(url);
            }
            //try jarfilename path if it starts from "/" or absolute url
            if (!exist && (this.javaAppletJarFileName.indexOf("/") == 0 || this.javaAppletJarFileName.indexOf("://") > 0)) {
                exist = IUCommon.checkIfFileExists(this.javaAppletJarFileName);
            }
            //show warning if file doesn't exist
            if (!exist) {
                IUCommon.showWarning("Image uploader arhive not found.\nPlease ensure \"JavaAppletCodeBase\", \"JavaAppletJarFileName\" properties are correct.", 1);
            }

            //<object> for IE and <applet> for Safari/Konqueror
            //Use <embed> tag for Chrome to avoid bug 10901.
            if (IUCommon.browser.isWinIE || (IUCommon.browser.isKhtml && !IUCommon.browser.isChrome)) {
                if (IUCommon.browser.isWinIE) {
                    sb.add("<object id=\"" + this.id + "\" classid=\"clsid:8AD9C840-044E-11D1-B3E9-00805F499D93\" codebase=\"" + window.location.protocol + "//java.sun.com/update/1.5.0/jinstall-1_5_0-windows-i586.cab#Version=1,5,0,0\" width=\"" + this.width + "\" height=\"" + this.height + "\">");
                    //add java not installed or not enabled message
                    //we set this property from InstallationProgress
                    if (this.installJavaIEInstructions) {
                        sb.add(this.installJavaIEInstructions);
                    }
                }
                else {
                    sb.add("<applet id=\"" + this.id + "\" code=\"" + this.javaAppletClassName + "\" java_codebase=\"" + this.javaAppletCodeBase + "\" align=\"baseline\" archive=\"" + this.javaAppletJarFileName + "\" mayscript=\"true\" scriptable=\"true\" width=\"" + this.width + "\" height=\"" + this.height + "\">");
                }

                if (this.javaAppletCached && this.javaAppletVersion != "") {
                    sb.add(this._getObjectParamHtml("cache_archive", this.javaAppletJarFileName));
                    var v = this.javaAppletVersion.replace(/\,/g, ".");
                    sb.add(this._getObjectParamHtml("cache_version", v));
                }

                sb.add(this._getObjectParamHtml("id", this.id));
                sb.add(this._getObjectParamHtml("type", "application/x-java-applet;version=1.5"));
                sb.add(this._getObjectParamHtml("codebase", this.javaAppletCodeBase));
                sb.add(this._getObjectParamHtml("archive", this.javaAppletJarFileName));
                sb.add(this._getObjectParamHtml("code", this.javaAppletClassName));
                sb.add(this._getObjectParamHtml("scriptable", "true"));
                sb.add(this._getObjectParamHtml("mayscript", "true"));

                this._addObjectParamsHtml(sb);

                this._addObjectEventsHtml(sb);

                if (IUCommon.browser.isWinIE) {
                    sb.add("</object>");
                }
                else {
                    sb.add("</applet>");
                }
            }
            //<embed> for all other browsers
            else {
                sb.add("<embed id=\"" + this.id + "\" type=\"application/x-java-applet;version=1.5\" codebase=\"" + this.javaAppletCodeBase + "\" code=\"" + this.javaAppletClassName + "\" archive=\"" + this.javaAppletJarFileName + "\" width=\"" + this.width + "\" height=\"" + this.height + "\" scriptable=\"true\" mayscript=\"true\" pluginspage=\"" + window.location.protocol + "//java.sun.com/products/plugin/index.html#download\"");

                if (this.javaAppletCached && this.javaAppletVersion != "") {
                    sb.add(this._getEmbedParamHtml("cache_archive", this.javaAppletJarFileName));
                    var v = this.javaAppletVersion.replace(/\,/g, ".");
                    sb.add(this._getEmbedParamHtml("cache_version", v));
                }

                this._addEmbedParamsHtml(sb);
                this._addEmbedEventsHtml(sb);

                sb.add(">");
                sb.add("</embed>");
            }

            //Event handlers
            var e = this._events;
            for (var name in e) {
                if (name == "FullPageLoad") {
                    continue;
                }
                if (e[name].length > 1) {
                    var sgn = this._getEventSignature(name);
                    var arr = [];
                    //save events to temp array
                    //don't pass this._events[name] directly!
                    for (var h in e[name])
                        arr.push(e[name][h]);
                    //make event handler
                    window[this._getListenerName(name)] = new function(listeners, signature) {
                        return function() {
                            var result, args = [];
                            //construct parameters string for listeners
                            for (var i = 0, cnt = arguments.length; i < cnt; i++) {
                                args.push("arguments[" + i + "]");
                            }
                            //call listeners
                            for (var z = 0; z < listeners.length; z++) {
                                //We return value from last one event listener only
                                if (signature.returns && (z == listeners.length - 1))
                                    eval("result = " + listeners[z] + "(" + args.join(", ") + ");");
                                else
                                    eval(listeners[z] + "(" + args.join(", ") + ");");
                            }
                            return result;
                        }
                    } (arr, sgn);
                }
            }
        }
        else {
            sb.add(this.browserNotSupported);
        }

        if (icln) {
            this.removeEventListener("InitComplete", icln);
        }

        //For backward compatibility
        if (this.fullPageLoadListenerName) {
            this.removeEventListener("FullPageLoad", this.fullPageLoadListenerName);
        }

        this.controlType = this.getControlType();

        return sb.toString();
    },

    //Public

    addParam: function(name, value) {
        /// <summary>Adds a parameter with the specified name and value. It takes effect when writeHtml or getHtml method is run.</summary>
        /// <param name="name" type="String">A parameter name.</param>
        /// <param name="value" type="String">A parameter value.</param>			

        if (IUCommon.debugLevel > 0 && !this._validateParam(name, value)) {
            return;
        };

        if (this._params[name] == undefined) {
            this._params[name] = value;
        }
        else {
            IUCommon.showWarning("You have called more then one time addParam method for \"" + name + "\" parameter.", 2);
        }
    },

    getParam: function(name) {
        /// <summary>Removes parameter with a specified name.</summary>
        /// <param name="name" type="String">A parameter name.</param>

        return this._params[name];
    },

    removeParam: function(name) {
        /// <summary>Removes parameter with a specified name.</summary>
        /// <param name="name" type="String">A parameter name.</param>

        delete this._params[name];
    },

    addEventListener: function(name, listener) {
        /// <summary>Subscribes a specified JavaScript function to a specified event.</summary>
        /// <param name="name">An event name.</param>
        /// <param name="listener">A name or reference to the JavaScript function which is the event listener.</param>	

        var sgn = this._getEventSignature(name);
        if (sgn == null) {
            IUCommon.showWarning("Event \"" + name + "\" passed to addEventListener method isn't supported by object.", 1);
            return;
        }

        if (typeof listener != "string" && typeof listener != "function") {
            IUCommon.showWarning("listener argument passed to addEventListener method should have function or string type (function reference or function name).", 1);
            return;
        }

        var f;
        var eln;
        if (typeof listener == "string") {
            f = window[listener];
            if (typeof f != "function") {
                IUCommon.showWarning("Function \"" + listener + "\" passed to addEventListener method doesn't exist.", 1);
                return;
            }
            eln = listener;
        }
        else {
            f = listener;
            this._autoCallback = this._autoCallback + 1;
            eln = this.id + "_AutoCallback" + this._autoCallback;
            window[eln] = f;
        }

        if (arguments[2] == undefined) {
            var p = sgn.params;
            var pc = p.indexOf(",") == -1 ? (p == "" ? 0 : 1) : p.split(",").length;

            if (f.length != pc) {
                IUCommon.showWarning("Function " + (typeof listener == "string" ? "\"" + listener + "\"" : "") + "passed to addEventListener method has a wrong number of parameters. For " + name + " event it should have function(" + p + ") signature.", 1);
                return;
            }
        }

        if (this._events[name] == undefined) {
            var l = new Array();
            this._events[name] = l;
        }
        else {
            l = this._events[name];
        }
        l.push(eln);
    },

    removeEventListener: function(name, listener) {
        /// <summary>Unsubscribes a specified JavaScript function from  a specified event.</summary>
        /// <param name="name">An event name.</param>
        /// <param name="listener">A name or reference to the JavaScript function which is the event listener.</param>	

        if (typeof listener != "function" && typeof listener != "string") {
            return;
        }

        var l = this._events[name];
        if (l) {
            for (var z = 0; z < l.length; z++) {
                if ((typeof listener == "string" && l[z] == listener) || (window[l[z]] == listener)) {
                    l.splice(z, 1);
                    return;
                }
            }
        }
    },

    addExtender: function(extender) {
        /// <summary>Adds extender.</summary>
        /// <param name="extender" type="BaseExtender">A parameter name.</param>

        this._extenders.push(extender);
    },

    getActiveXInstalled: function() {
        /// <summary>Verifies whether ActiveX control is installed. If yes, it returns true; otherwise it returns false.</summary>
        /// <returns type="Boolean" />

        if (this.activeXProgId) {
            try {
                var a = new ActiveXObject(this.activeXProgId);
                return true;
            }
            catch (e) {
                return false;
            }
        }
        return false;
    },

    getActiveXInstalledToUpdate: function() {
        if (this.activeXProgId) {
            try {
                var a = new ActiveXObject(this.activeXProgId);

                //A version is installed, but is it the current one?
                var installedVersionArray = a.Version.split(',');
                var currentVersionArray = this.activeXControlVersion.split(',');

                for (var i = 0; i < 4; i++) {
                    if (parseInt(installedVersionArray[i]) < parseInt(currentVersionArray[i])) {
                        //installed version is older then the current one
                        return true;
                    }
                }
            }
            catch (e) {
            }
        }
        //no update required
        return false;
    },

    getJREVersion: function() {
        var result = IUCommon.javaDetector.getJREs();
        if (result && result.length > 0)
            return result[0];
        else
            return null;
    },

    // -2 - Java not installed or not enabled
    // -1 - Installed Java version is less then required
    //  0 - Unable determine java
    //  1 - Required java version available
    getJREInstalled: function() {
        var result = -2;
        if (navigator.javaEnabled()) {
            var ver = IUCommon.javaDetector.getJREs();
            if (ver == null) {
                //we can't get information about java in this browser
                result = 0;
            } else if (ver.length > 0) {
                //java installed and we have its version
                result = -1;
                for (var i in ver) {
                    if (IUCommon.javaDetector.compareVersions(ver[i], this.javaVersionRequired)) {
                        result = 1;
                        break;
                    }
                }
            }
        }
        return result;
    },

    getHtml: function() {
        /// <summary>Generates the HTML code which will embed Image Uploader and return it as a string. It will write all parameters added with the addParam method and append all the event handlers specified by the addEventListener method.</summary>
        /// <returns type="String" />

        if (this._getHtmlCalled) {
            IUCommon.showWarning("You can call getHtml/writeHtml method of writer object only once.", 1);
            return "";
        }

        this._getHtmlCalled = true;

        if (IUCommon.debugLevel > 0) {
            this._validateParams();
        }

        for (var i = 0; i < this._extenders.length; i++) {
            this._extenders[i]._beforeRender();
        }

        var sb = new IUCommon.StringBuilder();

        for (var i = 0; i < this._extenders.length; i++) {
            sb.add(this._extenders[i]._getBeforeHtml());
        }

        sb.add(this._getObjectHtml());

        for (var i = 0; i < this._extenders.length; i++) {
            sb.add(this._extenders[i]._getAfterHtml());
        }

        return sb.toString();
    },

    getControlType: function() {
        /// <summary>Returns a value that specifies what kind of platform is currently used - ActiveX, Java, or none (i.e. neither ActiveX nor Java can be used in the current browser).</summary>
        /// <returns type="String">return one of the following strings: ActiveX, Java, None.</returns>

        return (IUCommon.browser.isWinIE && this.activeXControlEnabled &&
			!IUCommon.browser.isIE64 /*64-bit IE doesn't supportactivex version*/) ? "ActiveX" : (this.javaAppletEnabled ? "Java" : "None");
    },

    writeHtml: function() {
        /// <summary>Generates the HTML code which will embed Image Uploader and write this code directly on the page. It will write all parameters added with the addParam method and append all the event handlers specified by the addEventListener method.</summary>	

        document.write(this.getHtml());
    }
}


//--------------------------------------------------------------------------
//BaseIUWriter class
//--------------------------------------------------------------------------

function BaseIUWriter(id, width, height) {
    BaseWriter.call(this, id, width, height);

    this.instructionsCommon = "<p>Image Uploader ActiveX control is necessary to upload "
		+ "your files quickly and easily. You will be able to select multiple images "
		+ "in user-friendly interface instead of clumsy input fields with <strong>Browse</strong> button. "
		+ "Installation will take up to a few minutes, please, be patient. To install Image Uploader, ";
    this.instructionsNotWinXPSP2 = "please, reload the page and click the <strong>Yes</strong> button "
		+ "when you see the control installation dialog.";
    this.instructionsWinXPSP2 = "please click on the <strong>Information Bar</strong> and select "
		+ "<strong>Install ActiveX Control</strong> from the dropdown menu. After page reload click <strong>Install</strong> when "
		+ "you see the control installation dialog.";
    this.instructionsVista = "please click on the <strong>Information Bar</strong> and select <strong>Install ActiveX Control</strong> "
		+ "from the dropdown menu. After page reload click <strong>Continue</strong> and then <strong>Install</strong> when "
		+ "you see the control installation dialog.";
    this.instructionsCommon2 = "</p>";

    this.activeXControlCodeBase = "ImageUploader.cab";
    this.javaAppletJarFileName = "ImageUploader.jar";
}

BaseIUWriter.prototype = new BaseWriter();
BaseIUWriter.prototype.constructor = BaseIUWriter;


//--------------------------------------------------------------------------
//ImageUploaderWriter class
//--------------------------------------------------------------------------

function ImageUploaderWriter(id, width, height) {
    /// <field name="showNonemptyResponse" type="String">
    ///   ID of the control that is used to get a reference with a help of the 
    ///   getImageUploader function.
    /// </field>

    BaseIUWriter.call(this, id, width, height);

    //Public

    this.showNonemptyResponse = "off";

    //These properties should be modified for private-label versions only
    this.activeXClassId = "83A4D5A6-E2C1-4EDD-AD48-1A1C50BD06EF";
    this.activeXProgId = "Aurigma.ImageUploader.6";
    this.javaAppletClassName = "com.aurigma.imageuploader.ImageUploader.class";
    this.controlClass = "ImageUploader";

    this._v6ClassId = this.activeXClassId;
    this._v6JarFileName = this.javaAppletJarFileName;
}

ImageUploaderWriter.prototype = new BaseIUWriter();
ImageUploaderWriter.prototype.constructor = ImageUploaderWriter;

ImageUploaderWriter.prototype._validateParam = function(name, value) {
    var r = BaseWriter.prototype._validateParam.call(this, name, value);

    if (!r) {
        return;
    }

    switch (name) {
        case "LicenseKey":
            if (this.getParam("LicenseKey") != undefined) {
                IUCommon.showWarning("You should call addParam(\"LicenseKey\", \"...\") method "
					+ "only once. If you need to add mupltiple license keys you should specify "
					+ "them separated with semicolons: addParam(\"LicenseKey\", \"key1;key2\"). ", 1);
                return false;
            }
            break;
    }
    return true;
}

ImageUploaderWriter.prototype._validateParams = function() {
    BaseWriter.prototype._validateParams.call(this);

    var local = location.hostname == "localhost";
    if (local) {
        var action = this.getParam("Action");
        if (action && (action.indexOf("http://") == 0 || action.indexOf("https://") == 0)) {
            var d = action.substring(action.indexOf("//") + 2);
            if (d != "localhost" && d.indexOf("localhost:") != 0 && d.indexOf("localhost/") != 0) {
                local = false;
            }
        }
    }

    if (!local) {
        var p = this.getParam("LicenseKey");

        if (p == undefined || p == "") {
            IUCommon.showWarning("You should specify at least one license key using addParam(\"LicenseKey\", \"...\") method in JavaScript or LicenseKey property in ASP.NET/PHP. Otherwise, upload will not work.", 1);
            return;
        }

        if (this._v6ClassId != this.activeXClassId || this._v6JarFileName != this.javaAppletJarFileName) {
            return;
        }

        var keys = new String(p).split(";");
        var t = {};
        for (var i = 0; i < keys.length; i++) {
            t[keys[i].replace(/^\s+|\s+$/g, "").substr(0, 4)] = 1;
        }

        var af = this.activeXControlEnabled && t["7106"] == undefined;
        var jf = this.javaAppletEnabled && t["7206"] == undefined;

        if (af && jf) {
            IUCommon.showWarning("You have enabled ActiveX and Java versions, but haven't specified version 6.x license key for them.", 1);
        }
        else {
            if (af) {
                IUCommon.showWarning("You have enabled ActiveX version, but haven't specified version 6.x license key for it. "
				+ "You should either add license key or disable it using activeXControlEnabled = false syntax.", 1);
            }
            if (jf) {
                IUCommon.showWarning("You have enabled Java version, but haven't specified version 6.x license key for it. "
				+ "You should either add license key or disable it using javaAppletEnabled = false syntax.", 1);
            }
        }
    }
}

ImageUploaderWriter.prototype._getEventSignature = function(name) {
    var p = "";

    switch (name) {
        case "Progress":
            p = "Status, Progress, ValueMax, Value, StatusText";
            break;
        case "InnerComplete":
            p = "Status, StatusText";
            break;
        case "AfterUpload":
            p = "Html";
            break;
        case "ViewChange":
        case "SortModeChange":
            p = "Pane";
            break;
        case "Error":
            p = "ErrorCode, HttpResponseCode, ErrorPage, AdditionalInfo";
            break;
        case "PackageBeforeUpload":
            p = "PackageIndex";
            break;
        case "PackageError":
            p = "PackageIndex, ErrorCode, HttpResponseCode, ErrorPage, AdditionalInfo";
            break;
        case "PackageComplete":
            p = "PackageIndex, ResponsePage";
            break;
        case "PackageProgress":
            p = "PackageIndex, Status, Progress, ValueMax, Value, StatusText";
            break;
        case "BeforeUpload":
        case "FolderChange":
        case "InitComplete":
        case "PaneResize":
        case "SelectionChange":
        case "UploadFileCountChange":
            p = "";
            break;
        default:
            return BaseWriter.prototype._getEventSignature.call(this, name);
    }

    return { params: p, returns: (name == "BeforeUpload" || name == "PackageComplete") }
}

ImageUploaderWriter.prototype._createExpandoMethods = function() {
    BaseWriter.prototype._createExpandoMethods.call(this);
    var o = document.getElementById(this.id);
    o.setPaneItemDesign = function(Pane, Index, Value) { this.PaneItemDesign(Pane, Index) = Value; };
    o.getPaneItemDesign = function(Pane, Index) { return this.PaneItemDesign(Pane, Index); };
    o.setPaneItemChecked = function(Pane, Index, Value) { this.PaneItemChecked(Pane, Index) = Value; };
    o.getPaneItemChecked = function(Pane, Index) { return this.PaneItemChecked(Pane, Index); };
    o.getPaneItemSelected = function(Pane, Index) { return this.PaneItemSelected(Pane, Index); }; ;
    o.setPaneItemEnabled = function(Pane, Index, Value) { this.PaneItemEnabled(Pane, Index) = Value; };
    o.getPaneItemEnabled = function(Pane, Index) { return this.PaneItemEnabled(Pane, Index); };
},

ImageUploaderWriter.prototype._getObjectHtml = function() {
    if (this.showNonemptyResponse != "off") {
        var icn = "__" + this.id + "_InnerComplete";

        if (this.showNonemptyResponse == "dump") {
            window[icn] = function(Status, StatusText) {
                if (new String(Status) == "COMPLETE" && new String(StatusText).replace(/\s*/g, "") != "") {
                    var f = document.createElement("fieldset");
                    var l = f.appendChild(document.createElement("legend"));
                    l.appendChild(document.createTextNode("Server Response"));
                    var d = f.appendChild(document.createElement("div"));
                    d.innerHTML = StatusText;
                    var b = f.appendChild(document.createElement("button"));
                    b.appendChild(document.createTextNode("Clear Server Response"));
                    b.onclick = function() { var f = this.parentNode; f.parentNode.removeChild(f) };
                    document.body.appendChild(f);
                }
            }
        }
        else {
            window[icn] = function(Status, StatusText) {
                if (new String(Status) == "COMPLETE" && new String(StatusText).replace(/\s*/g, "") != "") {
                    var s = "";
                    for (var i = 0; i < 80; i++) { s += "-"; }
                    alert(s + "\n\rServer Response\n\r" + s + "\n\r" + StatusText);
                }
            }
        }

        this.addEventListener("InnerComplete", icn);
    }

    return BaseIUWriter.prototype._getObjectHtml.call(this);
}


//--------------------------------------------------------------------------
//ThumbnailWriter class
//--------------------------------------------------------------------------

function ThumbnailWriter(id, width, height) {
    BaseIUWriter.call(this, id, width, height);

    //Public

    //These properties should be modified for private label versions only
    this.activeXClassId = "94AB719E-1300-4098-8C18-B2A765327D15";
    this.activeXProgId = "Aurigma.Thumbnail.6";
    this.javaAppletClassName = "com.aurigma.imageuploader.Thumbnail.class";
    this.controlClass = "Thumbnail";
}

ThumbnailWriter.prototype = new BaseIUWriter();
ThumbnailWriter.prototype.constructor = ThumbnailWriter;


//--------------------------------------------------------------------------
//ShellComboBoxWriter class
//--------------------------------------------------------------------------

function ShellComboBoxWriter(id, width, height) {
    BaseIUWriter.call(this, id, width, height);

    //Public

    //These properties should be modified for private label versions only
    this.activeXClassId = "BA6272FD-A7AD-4498-9476-552040B7EDD4";
    this.activeXProgId = "Aurigma.ShellCombo.6";
    this.javaAppletClassName = "com.aurigma.imageuploader.ShellComboBox.class";
    this.controlClass = "ShellComboBox";
}

ShellComboBoxWriter.prototype = new BaseIUWriter();
ShellComboBoxWriter.prototype.constructor = ShellComboBoxWriter;


//--------------------------------------------------------------------------
//UploadPaneWriter class
//--------------------------------------------------------------------------

function UploadPaneWriter(id, width, height) {
    BaseIUWriter.call(this, id, width, height);

    //Public

    //These properties should be modified for private label versions only
    this.activeXClassId = "BF357E76-2001-47F1-8057-46DEE9627DFD";
    this.activeXProgId = "Aurigma.UploadPane.6";
    this.javaAppletClassName = "com.aurigma.imageuploader.UploadPane.class";
    this.controlClass = "UploadPane";
}

UploadPaneWriter.prototype = new BaseIUWriter();
UploadPaneWriter.prototype.constructor = UploadPaneWriter;


//--------------------------------------------------------------------------
//FileDownloaderWriter class
//--------------------------------------------------------------------------

function FileDownloaderWriter(id, width, height) {
    BaseWriter.call(this, id, width, height);

    //Public

    this.instructionsCommon = "<p>File Downloader ActiveX control is necessary to download "
		+ "files quickly and easily. You will be able to select required files "
		+ "in a user-friendly interface and simply click a <strong>Download</strong> button. "
		+ "Installation will take up to a few minutes, please, be patient. To install File Downloader, ";
    this.instructionsNotWinXPSP2 = "please, reload the page and click the <strong>Yes</strong> button "
		+ "when you see the control installation dialog.";
    this.instructionsWinXPSP2 = "please click the <strong>Information Bar</strong>. After page reload click <strong>Install</strong> when "
		+ "you see the control installation dialog.";
    this.instructionsVista = "please click on the <strong>Information Bar</strong> and select <strong>Install ActiveX Control</strong> "
		+ "from the dropdown menu. After page reload click <strong>Continue</strong> and then <strong>Install</strong> when "
		+ "you see the control installation dialog.";
    this.instructionsCommon2 = "</p><p><strong>NOTE:</strong> If control fails to be installed, it may mean that it has been inserted to the page incorrectly. "
		+ "Refer File Downloader documentation for more details.</p>";

    //These properties should be modified for private label versions only
    this.activeXControlCodeBase = "FileDownloader2.cab";
    this.activeXClassId = "AAB58191-AFBE-4366-93FD-1E45F7C97FA0";
    this.activeXProgId = "Aurigma.FileDownloader.2";
    this.javaAppletEnabled = false;
    this.controlClass = "FileDownloader";
}

FileDownloaderWriter.prototype = new BaseWriter();
FileDownloaderWriter.prototype.constructor = FileDownloaderWriter;

FileDownloaderWriter.prototype._getEventSignature = function(name) {
    var p = "";

    switch (name) {
        case "DownloadComplete":
            p = "Value";
            break;
        case "DownloadItemComplete":
            p = "Result, ErrorPage, Url, FileName, ContentType, FileSize";
            break;
        case "DownloadStep":
            p = "Step";
            break;
        case "Progress":
            p = "PercentTotal, PercentCurrent, Index";
            break;
        case "Error":
            p = "ErrorCode, HttpErrorCode, ErrorPage, Url, Index";
            break;
        case "DownloadFileCountChange":
            p = "";
            break;
        default:
            return BaseWriter.prototype._getEventSignature.call(this, name);
    }

    return { params: p, returns: false }
}

//--------------------------------------------------------------------------
//VideoUploaderWriter class
//--------------------------------------------------------------------------

function VideoUploaderWriter(id, width, height) {
    BaseWriter.call(this, id, width, height);

    //Public
    this.instructionsCommon = "<p>Video Uploader ActiveX control is necessary to upload "
        + "your files quickly and easily. You will be able to select multiple images "
        + "in user-friendly interface instead of clumsy input fields with <strong>Browse</strong> button. "
        + "Installation will take up to a few minutes, please, be patient. To install Video Uploader, ";
    this.instructionsNotWinXPSP2 = "please, reload the page and click the <strong>Yes</strong> button "
		+ "when you see the control installation dialog.";
    this.instructionsWinXPSP2 = "please click the <strong>Information Bar</strong>. After page reload click <strong>Install</strong> when "
		+ "you see the control installation dialog.";
    this.instructionsVista = "please click on the <strong>Information Bar</strong> and select <strong>Install ActiveX Control</strong> "
		+ "from the dropdown menu. After page reload click <strong>Continue</strong> and then <strong>Install</strong> when "
		+ "you see the control installation dialog.";
    this.instructionsCommon2 = "</p>";

    //These properties should be modified for private label versions only
    this.activeXControlCodeBase = "VideoUploader.cab";
    this.activeXClassId = "C4773A66-74A6-4AC9-8934-3BD5F9F4B5AA";
    this.activeXProgId = "Aurigma.VideoUploader.1";
    this.javaAppletEnabled = false;
    this.controlClass = "VideoUploader";
}

VideoUploaderWriter.prototype = new BaseWriter();
VideoUploaderWriter.prototype.constructor = VideoUploaderWriter;

VideoUploaderWriter.prototype._validateParam = function(name, value) {
    var r = BaseWriter.prototype._validateParam.call(this, name, value);

    if (!r) {
        return;
    }

    switch (name) {
        case "LicenseKey":
            if (this.getParam("LicenseKey") != undefined) {
                IUCommon.showWarning("You should call addParam(\"LicenseKey\", \"...\") method "
					+ "only once. If you need to add mupltiple license keys you should specify "
					+ "them separated with semicolons: addParam(\"LicenseKey\", \"key1;key2\"). ", 1);
                return false;
            }
            break;
    }
    return true;
}

VideoUploaderWriter.prototype._validateParams = function() {
    BaseWriter.prototype._validateParams.call(this);

    var local = location.hostname == "localhost";
    if (local) {
        var action = this.getParam("Action");
        if (action && (action.indexOf("http://") == 0 || action.indexOf("https://") == 0)) {
            var d = action.substring(action.indexOf("//") + 2);
            if (d != "localhost" && d.indexOf("localhost:") != 0 && d.indexOf("localhost/") != 0) {
                local = false;
            }
        }
    }

    if (!local) {
        var p = this.getParam("LicenseKey");

        if (p == undefined || p == "") {
            IUCommon.showWarning("You should specify at least one license key using addParam(\"LicenseKey\", \"...\") method in JavaScript. Otherwise, upload will not work.", 1);
            return;
        }
    }
}

VideoUploaderWriter.prototype._createExpandoMethods = function() {
    BaseWriter.prototype._createExpandoMethods.call(this);
    var o = document.getElementById(this.id);
    o.setPaneItemDesign = function(Pane, Index, Value) { this.PaneItemDesign(Pane, Index) = Value; };
    o.getPaneItemDesign = function(Pane, Index) { return this.PaneItemDesign(Pane, Index); };
    o.setPaneItemChecked = function(Pane, Index, Value) { this.PaneItemChecked(Pane, Index) = Value; };
    o.getPaneItemChecked = function(Pane, Index) { return this.PaneItemChecked(Pane, Index); };
    o.getPaneItemSelected = function(Pane, Index) { return this.PaneItemSelected(Pane, Index); }; ;
    o.setPaneItemEnabled = function(Pane, Index, Value) { this.PaneItemEnabled(Pane, Index) = Value; };
    o.getPaneItemEnabled = function(Pane, Index) { return this.PaneItemEnabled(Pane, Index); };
}

VideoUploaderWriter.prototype._getEventSignature = function(name) {
    var p = "";

    switch (name) {
        case "Progress":
            p = "Status, Progress, ValueMax, Value, StatusText";
            break;
        case "InnerComplete":
            p = "Status, StatusText";
            break;
        case "AfterUpload":
            p = "Html";
            break;
        case "ViewChange":
        case "SortModeChange":
            p = "Pane";
            break;
        case "Error":
            p = "ErrorCode, HttpResponseCode, ErrorPage, AdditionalInfo";
            break;
        case "PackageBeforeUpload":
            p = "PackageIndex";
            break;
        case "PackageError":
            p = "PackageIndex, ErrorCode, HttpResponseCode, ErrorPage, AdditionalInfo";
            break;
        case "PackageComplete":
            p = "PackageIndex, ResponsePage";
            break;
        case "PackageProgress":
            p = "PackageIndex, Status, Progress, ValueMax, Value, StatusText";
            break;
        case "BeforeUpload":
        case "FolderChange":
        case "InitComplete":
        case "PaneResize":
        case "SelectionChange":
        case "UploadFileCountChange":
            p = "";
            break;
        default:
            return BaseWriter.prototype._getEventSignature.call(this, name);
    }

    return { params: p, returns: (name == "BeforeUpload" || name == "PackageComplete") }
}


//--------------------------------------------------------------------------
//BaseExtender class
//--------------------------------------------------------------------------

function BaseExtender(writer) {
    /// <summary>Extends Image Uploader.</summary>
    /// <param name="iu" type="ImageUploaderWriter">An instance of ImageUploaderWriter object.</param>

    if (writer != undefined) {
        this._writer = writer;
        this._writer.addExtender(this);
    }
}

BaseExtender.prototype = {
    _beforeRender: function() {

    },

    _getBeforeHtml: function() {

    },

    _getAfterHtml: function() {

    }
}


//--------------------------------------------------------------------------
//Common functions
//--------------------------------------------------------------------------

function getControlObject(id) {
    /// <summary>Returns a reference to the control by specified ID.</summary>
    /// <param name="id" type="String">An ID of the control specified in the ImageUploaderWriter constructor.</param>
    /// <returns />

    if (IUCommon.browser.isSafari) {
        return document[id];
    }
    else {
        return document.getElementById(id);
    }
}


function getImageUploader(id) {
    /// <summary>Returns a reference to the control by specified ID.</summary>
    /// <param name="id" type="String">An ID of the control specified in the ImageUploaderWriter constructor.</param>
    /// <returns type="ImageUploader" />

    return getControlObject(id);
}

function getFileDownloader(id) {
    /// <summary>Returns a reference to the control by specified ID.</summary>
    /// <param name="id" type="String">An ID of the control specified in the ImageUploaderWriter constructor.</param>
    /// <returns type="FileDownloader" />

    return getControlObject(id);
}

function getVideoUploader(id) {
    /// <summary>Returns a reference to the control by specified ID.</summary>
    /// <param name="id" type="String">An ID of the control specified in the VideoUploaderWriter constructor.</param>
    /// <returns type="VideoUploader" />

    return getControlObject(id);
}