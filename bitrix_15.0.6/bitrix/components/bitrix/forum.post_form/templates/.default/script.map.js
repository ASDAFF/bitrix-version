{"version":3,"file":"script.min.js","sources":["script.js"],"names":["BX","Forum","transliterate","repo","node","onblur","clearInterval","bxfInterval","setInterval","value","bxValue","translit","max_len","change_case","replace_space","replace_other","delete_repeat_replace","use_google","callback","result","nextSibling","AddTags","a","parentNode","div","previousSibling","switcher","show","remove","innerHTML","inputs","getElementsByTagName","i","length","type","toUpperCase","CorrectTags","focus","oObj","id","fTextToNode","text","tmpdiv","create","childNodes","PostFormAjaxStatus","status","arNote","findChild","document","className","arMsgBox","findChildren","msgBox","statusDIV","beforeDivs","tmp","nodeType","insert","hasOwnProperty","hasClass","insertBefore","PostFormAjaxNavigation","navString","pageNumber","navDIV","navPlaceholders","window","PostFormAjaxMsgStart","msg","msgNode","navPlaceholder","fReplaceOrInsertNode","sourceNode","targetNode","parentTargetNode","beforeTargetNode","nextNode","isDomNode","isArray","removeChild","appendChild","fRunScripts","ob","processHTML","ajax","processScripts","SCRIPT","PostFormAjaxResponse","response","postform","forumAjaxPostTmp","reload","arForumlist","forumlist","formlist","tagName","message","listparent","navigation","ClearForumPostForm","allMessages","lastMessage","footerActions","previewDIV","previewParent","scrollToNode","arr","butt","getAttribute","disabled","attr","name","form","LHEPostForm","reinitDataBefore","editor","getEditor","handler","getHandler","CheckAndReInit","fAutosave","bind","pEditorDocument","proxy","Init","arFiles","hide","attachNodes","attachNode","pop","captchaIMAGE","captchaHIDDEN","captchaINPUT","captchaDIV","tag","getCaptcha","src","SetForumAjaxPostTmp","ValidateForm","ajax_post","Get","SaveContent","errors","Message","GetContent","MessageLength","MessageMax","TITLE","replace","alert","oEls","oEl","ii","push","adjust","clone","attrs","pageNumberInput","props","setTimeout","submit","ShowLastEditReason","checked","ShowVote","vote_remove_answer","obj","vote_add_answer","answer","firstChild","regexp","number","exec","q","parseInt","confirm","bFromRemoveAnswerFunction","ol","lastChild","html","vote_remove_question","anchor","question","vote_add_question","iQuestion","quoteMessageEx","mid","selection","toolbar","controls","Quote","range","GetRange","GetSelection","collapsed","tmpDiv","toHtml","GetIframeDoc","util","GetTextContentEx","DoNothing","videoWMV","str","p1","rWmv","res","rFlv","search","regexReplaceTableTag","s","replacement","re_match","RegExp","re_replace","ij","browser","IsIE","author","hasAttribute","GetViewMode","bbCode","SetBxTag","params","ParseContentFromBbCode","action","actions","quote","setExternalSelection","Exec","reply2author","textareaView","WrapWith","InsertHtml","Focus","defer","addCustomEvent","AddButton","iconClassName","disabledForTextarea","toolbarSort","textbody","bTranslited","arStack","bPushTag","offset","bPopTag","shift","r","capitEngLettersReg","capitRusLetters","smallEngLettersReg","smallRusLetters","capitRusLettersReg","capitEngLetters","smallRusLettersReg","smallEngLetters","content","SetContent","insertImageAfterUpload","formID","forms","e","PreventDefault","oCaptcha","Captcha","Show","ready","Update","this","input","hidden","image","prototype","iframe","_checkDisplay","d","style","display","UpdateControls","data"],"mappings":"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"}