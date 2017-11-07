var spellcheck_js = true;
//**********************************************************
// BXSpellChecker
//**********************************************************
function BXSpellChecker(pMainObj,BXLang,usePspell,useCustomSpell)
{
	this.pMainObj = pMainObj;
	this.wordList = [];
	this.nodesMap = [];
	this.BXLang = BXLang;
	this.usePspell = usePspell;
	this.useCustomSpell = useCustomSpell;
	mObj = this;

	//Skip
	BX("BX_dialog_butSkip").onclick = function()
	{
		mObj.pMainObj.SetFocus();
		mObj.skipWord();
	};
	BX("BX_dialog_suggestionsBox").onclick = function()
	{
		mObj.changeReplacementValue();
	};
	BX("BX_dialog_butSkipAll").onclick = function()
	{
		mObj.pMainObj.SetFocus();
		mObj.findSimilarWords('skip');
	};
	BX("BX_dialog_butReplace").onclick = function()
	{
		mObj.pMainObj.SetFocus();
		mObj.replaceWord();
		mObj.skipWord();
	};
	BX("BX_dialog_butReplaceAll").onclick = function()
	{
		mObj.pMainObj.SetFocus();
		mObj.findSimilarWords('replace');
	};
	BX("BX_dialog_butAdd").onclick = function()
	{
		mObj.pMainObj.SetFocus();
		mObj.addWord();
	};
}


BXSpellChecker.prototype.parseDocument = function()
{
	var root = this.pMainObj.pEditorDocument.body;
	this.processChildren(root,this.handleNodeValue);
};

BXSpellChecker.prototype.processChildren = function(node,callBackFunction)
{
	if (node.childNodes.length>0)
	{
		var children = node.childNodes;
		for (var ind = 0; ind<children.length; ind++)
		{
			//check if it's element node
			if (children[ind].nodeType == 1)
				this.processChildren(children[ind],callBackFunction);
			else if (children[ind].nodeType == 3)
				if (children[ind].nodeValue)
					if (children[ind].nodeValue.indexOf("IncludeFile") == -1)
						callBackFunction.apply(this,[children[ind]])
		}
	}
};

BXSpellChecker.prototype.handleNodeValue = function(obj)
{
	var separator = new RegExp("[\000-\100\133-\140\173-\177\230\236\246-\377\240]+","i");
	var arrWords = obj.nodeValue.split(separator);
	var i = 0;
	while (i<arrWords.length)
	{
		if (arrWords[i].length <= 1)
			arrWords.splice(i,1);
		else
			i++;
	}
	if (arrWords.length>0)
	{
		this.wordList = this.wordList.concat(arrWords);
		var maxInd = this.wordList.length;
		var nodesMapElement = [];
		nodesMapElement.obj = obj;
		nodesMapElement.maxInd = maxInd;
		this.nodesMap.push(nodesMapElement);
	}
};

BXSpellChecker.prototype.spellCheck = function()
{
	if (this.wordList.length > 0)
	{
		var strWordList = this.wordList.join(",");
		var postData = "wordlist="+encodeURIComponent(strWordList);
		var url = "/bitrix/admin/fileman_spell_checking.php?BXLang="+this.BXLang+"&useCustomSpell="+this.useCustomSpell+"&usePspell="+this.usePspell;
		this.ajaxConnect(url, postData, this.spellResultHandle,true);
	}
	else
		this.spellResult = [];
};

BXSpellChecker.prototype.spellResultHandle = function(elArr)
{
	if (elArr[0] && elArr[0].firstChild && elArr[0].firstChild.firstChild && elArr[0].firstChild.firstChild.data != "error")
	{
		elCount = elArr.length;
		this.spellResult = [];
		for (var i=0; i<elCount; i++)
		{
			var el = [];
			var ind = elArr[i].childNodes[0].firstChild.data;
			el.word = this.wordList[ind];
			el.obj = this.findObjLink(ind);
			el.suggestions = (elArr[i].childNodes[1].firstChild.data=='none') ? [] :  elArr[i].childNodes[1].firstChild.data.split(",");
			this.spellResult.push(el);
			el = null;
		}
		this.wordList = null;
		this.nodesMap = null;
		this.showResult();
	}
	else
		this.showResult('error');
};


BXSpellChecker.prototype.showResult = function()
{
	if (this.showResult.arguments[0]=='error')
	{
		alert(BX_MESS.DIC_ISNT_INSTALED);
		BXClearSelection(pObj.pMainObj.pEditorDocument,pObj.pMainObj.pEditorWindow);
		window.oBXEditorDialog.Close();
		return;
	}
	var okMessWin = BX("BX_dialog_okMessWin");
	var waitWin = BX("BX_dialog_waitWin");
	var spellResultWin = BX("BX_dialog_spellResultWin");
	waitWin.style.display = "none";
	if (this.spellResult.length > 0)
	{
		spellResultWin.style.display = "block";
		this.pasteFirstWordInDialog();
	}
	else
		okMessWin.style.display = "block";
};

BXSpellChecker.prototype.findObjLink = function(ind)
{
	for (var line in this.nodesMap)
		if (ind<this.nodesMap[line].maxInd)
			return this.nodesMap[line].obj;
};

/* mode = 	true if it's asynchronous mode
**		false if it's synchronous mode
*/
BXSpellChecker.prototype.ajaxConnect = function(url, postData, callBackFunction, mode)
{
	var xmlObj = new Object();
	oSC = this;
	if (window.XMLHttpRequest)
		xmlObj = new XMLHttpRequest();
	else if (window.ActiveXObject)
		xmlObj = new ActiveXObject("Microsoft.XMLHTTP");
	else
	{
		_alert("Error initializing XMLHttpRequest");
		return;
	}

	xmlObj.onreadystatechange = function()
	{
		if (mode)
		{
			if(xmlObj.readyState == 4)
			{
				if (xmlObj.status == 200)
				{
					var elArr = xmlObj.responseXML.getElementsByTagName('root')[0].childNodes;
					if (callBackFunction)
					{
						callBackFunction.apply(oSC,[elArr]);
						oSC = null;
					}
				}
				else
				{
					_alert("There was a problem retrieving the XML data");
					return false;
				}
 	    	}
		}
	}

	xmlObj.open("POST",url,mode);
	xmlObj.setRequestHeader("Content-type","application/x-www-form-urlencoded");
	if (xmlObj.overrideMimeType)
		xmlObj.setRequestHeader("Connection","close");

	xmlObj.send(postData);
	if (!mode)
	{
		elArr = xmlObj.responseXML.getElementsByTagName('root')[0].childNodes;
		if (callBackFunction)
		{
			_alert('BXSpellChecker.prototype.ajaxConnect :: 2');
			callBackFunction.apply(this,[elArr]);
		}
	}
};

//--------------------------------------------------------------------------------
//Working with dialog
//--------------------------------------------------------------------------------
BXSpellChecker.prototype.pasteFirstWordInDialog = function()
{
	this.highlightWord();
	var wordBox = BX("BX_dialog_wordBox");
	var suggestionsBox = BX("BX_dialog_suggestionsBox");
	wordBox.value=this.spellResult[0].word;
	suggestionsBox.innerHTML="";
	for (var i in this.spellResult[0].suggestions)
	{
		var suggestionOpt = document.createElement("option");
		suggestionOpt.selected = (i==0) ? "selected" : "";
		suggestionOpt.innerHTML=this.spellResult[0].suggestions[i];
		suggestionOpt.value=this.spellResult[0].suggestions[i];
		suggestionsBox.appendChild(suggestionOpt);
	}
};


BXSpellChecker.prototype.skipWord = function()
{
	this.spellResult.splice(0,1);
	if (this.spellResult.length > 0)
	{
		this.pasteFirstWordInDialog();
	}
	else
	{
		BXClearSelection(pObj.pMainObj.pEditorDocument,pObj.pMainObj.pEditorWindow);
		window.oBXEditorDialog.Close();
	}
};


BXSpellChecker.prototype.changeReplacementValue = function()
{
	var suggestionsBox = BX("BX_dialog_suggestionsBox");
	var wordBox = BX("BX_dialog_wordBox");
	if (suggestionsBox.length > 0)
		wordBox.value = suggestionsBox[suggestionsBox.selectedIndex].value;
};

// Replace word in document to value of wordBox
// Can take one or two arguments: 	arguments[0] - index of element in spellResult array (default - 0)
//									arguments[1] - replacement value (default - wordBox.value)
BXSpellChecker.prototype.replaceWord = function()
{
	var ind = (arguments[0]) ? arguments[0] : 0;
	//run changeReplacementValue() if user click 'Replace' or 'Replace All' button before clickin' to some value in
	//suggestionsBox. (4 ex. if user want to replace word to 1st suggestion)
	var wordBox = BX("BX_dialog_wordBox");
	if (wordBox.value == this.spellResult[ind].word)
		this.changeReplacementValue();

	var newValue = (arguments[1]) ? arguments[1] : wordBox.value;
	var oldValueRE = new RegExp(this.spellResult[ind].word,"ig");
	this.spellResult[ind].obj.nodeValue = this.spellResult[ind].obj.nodeValue.replace(oldValueRE,newValue);
};

//Realize funtionality of 'Replace All' and 'Skip All' operations:
//Find all similar to spelling words and replace them (if mode='replace') or simply remove from spellResult array
BXSpellChecker.prototype.findSimilarWords = function(mode)
{
	var wordBox = BX("BX_dialog_wordBox");
	var ind = 1;
	//replacing 1st word
	if (mode=="replace")
	{
		if (wordBox.value == this.spellResult[ind].word)
			this.changeReplacementValue();

		var newValue = wordBox.value;
		this.replaceWord(0,newValue);
	}
	//[replacin' and] deletin' from spellResult similar words
	while (ind < this.spellResult.length)
	{
		if (this.spellResult[ind].word == this.spellResult[0].word)
		{
			if (mode=="replace")
			{
				this.replaceWord(ind,newValue);
			}
			this.spellResult.splice(ind,1);
		}
		else
			ind++;
	}
	//deletion' 1st word from result and 'refreshing' dialog
	this.skipWord();
};

// Add word to user's dictionary
BXSpellChecker.prototype.addWord = function()
{
	var wordBox = BX("BX_dialog_wordBox");
	var word = wordBox.value;
	var pSessid = BX("sessid");
	var post_data = "sessid=" + pSessid.value + "&word="+encodeURIComponent(word);
	var url = "/bitrix/admin/fileman_spell_addWord.php?BXLang="+this.BXLang+"&useCustomSpell="+this.useCustomSpell+"&usePspell="+this.usePspell;
	this.ajaxConnect(url, postData, false,true);
	this.findSimilarWords("skip");
};

//Highlight spelling word using selection
BXSpellChecker.prototype.highlightWord = function()
{
	var word = this.spellResult[0].word;
	var amount = word.length;
	var value = new RegExp(word,"i");
	var d = this.spellResult[0].obj.parentNode;
	var textData = (d.innerText) ? d.innerText : d.textContent;

	try{
		if (this.pMainObj.pEditorDocument.createRange)
		{
			//FF, Opera
			var ind = this.spellResult[0].obj.nodeValue.search(value);
			var oRange_local = this.pMainObj.pEditorDocument.createRange();
			oRange_local.setStart(this.spellResult[0].obj,ind);
			oRange_local.setEnd(this.spellResult[0].obj,ind+amount);
			//Now highlight using Mozilla style selections
			var wordSelection = this.pMainObj.pEditorWindow.getSelection();
			wordSelection.removeAllRanges();
			wordSelection.addRange(oRange_local);
		}
		else
		{
			//IE
			var ind =textData.search(value);
			this.pMainObj.pEditorDocument.selection.empty();
			var oRange_local = this.pMainObj.pEditorDocument.selection.createRange();
			this.pMainObj.SetFocus();
			oRange_local.moveToElementText(d);
			oRange_local.moveStart("character", ind);
			oRange_local.moveEnd("character", amount - oRange_local.text.length);
			oRange_local.select();
			d.focus();
		}
	}
	catch(e){}
};

//************************************************************
//Spell Checking. with MS Word
//************************************************************
function SpellCheck_MS(root)
{
	try{var Word = new ActiveXObject("Word.Application");}catch(e){return false;}
	Word.Quit(0);
	Word = new ActiveXObject("Word.Application");

	Word.Visible = false;
	var Doc = Word.Documents.Add();
	var prevpos = Word.Top;
	var prevstate = Word.WindowState;
	var prevstats = Word.Options.ShowReadabilityStatistics;
	Word.Options.ShowReadabilityStatistics = false;
	Word.WindowState = 0;
	Word.Top = -3000;
	SpellCheckTag(Word, root);
	window.focus();
	Doc.Close(0);
	Word.Top = prevpos;
	Word.WindowState = prevstate;
	Word.Options.ShowReadabilityStatistics = prevstats;
	Word.NormalTemplate.Saved = true;
	Word.Quit(0);
	alert(BX_MESS.SpellCheckComplete);
	return true;
};

function SpellCheckTag(Word, Tag)
{
	if(Tag.nodeType == 3 && Tag.nodeValue != "")
	{
		var txt = Tag.nodeValue;
		Word.Selection.Text = txt;
		var res = Word.Dialogs(828).Show();
		Word.ActiveWindow.Visible = false;
		if(res==0)
			return false;
		if(res==-1)
			return true;
		if(Word.Selection.Text!=txt)
			Tag.nodeValue = Word.Selection.Text;
	}
	else
	{
		var childs = Tag.childNodes;
		var l = childs.length;
		for(var i=0; i<l; i++)
			if(!SpellCheckTag(Word, childs[i]))
				return false;
	}
	return true;
};