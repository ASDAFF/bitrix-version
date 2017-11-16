<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?if($arResult["CanUserComment"])
{
?>
<div id="form_comment_" style="display:none;">
<div id="form_c_del" style="display:none;">
<div class="blog-comment-edit feed-com-add-block blog-post-edit">
<?
$arSmiles = array();
if(!empty($arResult["Smiles"]))
{
	foreach($arResult["Smiles"] as $arSmile)
	{
		$arSmiles[] = array(
			'name' => $arSmile["~LANG_NAME"],
			'path' => "/bitrix/images/blog/smile/".$arSmile["IMAGE"],
			'code' => str_replace("\\\\","\\",$arSmile["TYPE"]),
			'codes' => str_replace("\\\\","\\",$arSmile["TYPING"])
		);
	}
}
$arParams["FORM_ID"] = "blogCommentForm".randString(4);
$formParams = Array(
	"FORM_ID" => $arParams["FORM_ID"],
	"SHOW_MORE" => "Y",
	"PARSER" => Array(
		"Bold", "Italic", "Underline", "Strike", "ForeColor",
		"FontList", "FontSizeList", "RemoveFormat", "Quote",
		"Code", ((!$arResult["NoCommentUrl"]) ? 'CreateLink' : ''),
		"Image", (($arResult["allowImageUpload"] == "Y") ? 'UploadImage' : ''),
		(($arResult["allowVideo"] == "Y") ? "InputVideo" : ""),
		"Table", "Justify", "InsertOrderedList",
		"InsertUnorderedList",
		"MentionUser", "Source"),
	"BUTTONS" => Array(
		((in_array("UF_BLOG_COMMENT_FILE", $arParams["COMMENT_PROPERTY"]) || in_array("UF_BLOG_COMMENT_DOC", $arParams["COMMENT_PROPERTY"])) ? "UploadFile" : ""),
		((!$arResult["NoCommentUrl"]) ? 'CreateLink' : ''),
		//(($arResult["allowImageUpload"] == "Y") ? 'UploadImage' : ''),
		(($arResult["allowVideo"] == "Y") ? "InputVideo" : ""),
		"Quote",
		"MentionUser"/*, "BlogTag"*/
		),
	"TEXT" => Array(
		"NAME" => "comment",
		"VALUE" => "",
		"HEIGHT" => "80px"
	),
	"DESTINATION" => Array(
		"VALUE" => $arResult["FEED_DESTINATION"],
		"SHOW" => "N",
	),
	"UPLOAD_FILE" => (!empty($arResult["COMMENT_PROPERTIES"]["DATA"]["UF_BLOG_COMMENT_FILE"]) ? false :
		$arResult["COMMENT_PROPERTIES"]["DATA"]["UF_BLOG_COMMENT_DOC"]),
	"UPLOAD_WEBDAV_ELEMENT" => $arResult["COMMENT_PROPERTIES"]["DATA"]["UF_BLOG_COMMENT_FILE"],
	"UPLOAD_FILE_PARAMS" => array("width" => 400, "height" => 400),
	"FILES" => Array(
		"VALUE" => array(),
		"DEL_LINK" => $arResult["urlToDelImage"],
		"SHOW" => "N",
		"POSTFIX" => "file"
	),

	"SMILES" => Array("VALUE" => $arSmiles),
	"LHE" => array(
		"documentCSS" => "body {color:#434343;}",
		"ctrlEnterHandler" => "submitComment",
		"jsObjName" => "oPostFormLHE_".$arParams["FORM_ID"],
		"fontFamily" => "'Helvetica Neue', Helvetica, Arial, sans-serif",
		"fontSize" => "12px",
	),
	"IS_BLOG" => true,
);

//===WebDav===
if(!array_key_exists("USER", $GLOBALS) || !$GLOBALS["USER"]->IsAuthorized())
{
	unset($formParams["UPLOAD_WEBDAV_ELEMENT"]);
	foreach($formParams["BUTTONS"] as $keyT => $valT)
	{
		if($valT == "UploadFile")
		{
			unset($formParams["BUTTONS"][$keyT]);
		}
	}
}
//===WebDav===

?>
	<form action="/bitrix/urlrewrite.php?SEF_APPLICATION_CUR_PAGE_URL=<?=urlencode($arResult["urlToPost"])?>" id="<?=$formParams["FORM_ID"]?>" name="<?=$formParams["FORM_ID"]?>" <?
		?>method="POST" enctype="multipart/form-data" target="_self">
		<input type="hidden" name="comment_post_id" id="postId" value="" />
		<input type="hidden" name="log_id" id="logId" value="" />
		<input type="hidden" name="parentId" id="parentId" value="" />
		<input type="hidden" name="edit_id" id="edit_id" value="" />
		<input type="hidden" name="act" id="act" value="add" />
		<input type="hidden" name="as" id="as" value="<?=$arParams['AVATAR_SIZE_COMMENT']?>" />
		<input type="hidden" name="post" id="" value="Y" />
		<?=bitrix_sessid_post();?>
<?
if(empty($arResult["User"]))
{
?>
	<div class="blog-comment-field blog-comment-field-user">
		<div class="blog-comment-field blog-comment-field-author"><div class="blog-comment-field-text"><?
			?><label for="user_name"><?=GetMessage("B_B_MS_NAME")?></label><?
			?><span class="blog-required-field">*</span></div><span><?
			?><input maxlength="255" size="30" tabindex="3" type="text" name="user_name" id="user_name" value="<?=htmlspecialcharsEx($_SESSION["blog_user_name"])?>"></span></div>
		<div class="blog-comment-field-user-sep">&nbsp;</div>
		<div class="blog-comment-field blog-comment-field-email"><div class="blog-comment-field-text"><label for="">E-mail</label></div><span><input maxlength="255" size="30" tabindex="4" type="text" name="user_email" id="user_email" value="<?=htmlspecialcharsEx($_SESSION["blog_user_email"])?>"></span></div>
		<div class="blog-clear-float"></div>
	</div>
<?
}
?>
		<div id="blog-post-autosave-hidden" <?/*?>style="display:none;"<?*/?>></div>
<?$APPLICATION->IncludeComponent("bitrix:main.post.form", "", $formParams, false, Array("HIDE_ICONS" => "Y"));?>
<?
if($arResult["use_captcha"]===true)
{
?>
		<div class="blog-comment-field blog-comment-field-captcha">
		<div class="blog-comment-field-captcha-label">
			<label for="captcha_word"><?=GetMessage("B_B_MS_CAPTCHA_SYM")?></label><span class="blog-required-field">*</span><br>
			<input type="hidden" name="captcha_code" id="captcha_code" value="<?=$arResult["CaptchaCode"]?>">
			<input type="text" size="30" name="captcha_word" id="captcha_word" value=""  tabindex="7">
			</div>
		<div class="blog-comment-field-captcha-image"><div id="div_captcha"></div></div>
	</div>
	<div id="captcha_del">
	<script>
		<!--
		var cc;
		if(document.cookie.indexOf('<?=session_name()?>=') == -1)
			cc = Math.random();
		else
			cc ='<?=$arResult["CaptchaCode"]?>';

		document.write('<img src="/bitrix/tools/captcha.php?captcha_code='+cc+'" width="180" height="40" id="captcha" style="display:none;">');
		document.getElementById('captcha_code').value = cc;
		//-->
	</script>
	</div>
<?
}
?>
	<a class="feed-add-button feed-add-com-button" href="javascript:void(0)" id="blog-submit-button-save_comment" <?
		?>onmousedown="BX.addClass(this, 'feed-add-button-press')" onmouseup="BX.removeClass(this,'feed-add-button-press')" onclick="submitComment();"><?
		?><span class="feed-add-button-left"></span><?
		?><span class="feed-add-button-text" id="blg-com-btn"><?=GetMessage("BLOG_C_BUTTON_SEND")?></span><span class="feed-add-button-right"></span></a>
	<a class="feed-cancel-com" href="javascript:void(0)" id="blog-submit-button-cancel_comment" <?
		?>onclick="cancelComment();"><?=GetMessage("BLOG_C_BUTTON_CANCEL")?></a>
	<input type="hidden" name="blog_upload_cid" id="upload-cid" value="">
</form>
</div>
</div>
</div>
<?}?>
<script>
var lastPostComment;
var lastPostCommentId;
var actionUrl = '/bitrix/urlrewrite.php?SEF_APPLICATION_CUR_PAGE_URL=<?=str_replace("%23", "#", urlencode($arResult["urlToPost"]))?>';
function showComment(key, postId, error, userName, userEmail, needData, bEdit, logId)
{
	actUrl = actionUrl;
	actUrl = actUrl.replace(/#source_post_id#/, postId);
	BX('<?=$formParams["FORM_ID"]?>').action = actUrl;

	if(lastPostComment > 0 && BX('blg-post-'+lastPostComment))
	{
		BX.show(BX.findChild(BX('blg-post-'+lastPostComment), {className: 'feed-com-footer'}, true, false));
		if(BX('err_comment_'+lastPostComment+'_0'))
			BX.hide(BX('err_comment_'+lastPostComment+'_0'));
	}
	if(lastPostCommentId > 0)
	{
		BX.hide(BX('err_comment_'+lastPostCommentId));
		if(BX('err_comment_'+lastPostCommentId+'_0'))
			BX.hide(BX('err_comment_'+lastPostCommentId+'_0'));
	}
	<?
	if($arResult["use_captcha"]===true)
	{
		?>
		var im = BX('captcha');
		BX('captcha_del').appendChild(im);
		<?
	}
	?>
	comment = '';
	arFiles = [];

	if(needData == "Y" || bEdit == "Y")
	{
		comment = window["text"+key];
		arFiles = window["arComFiles"+key];
	}
	
	var pFormCont = BX('form_c_del');
	form_comment_id = 'form_comment_' + key;
	if(key == 0)
		form_comment_id = 'form_comment_'+ postId + '_' + key;
	
	BX(form_comment_id).appendChild(pFormCont); // Move form

	BX('parentId').value = key;
	BX('postId').value = postId;
	BX('edit_id').value = '';
	BX('act').value = 'add';

	if (
		logId 
		&& parseInt(logId) > 0
	)
		BX('logId').value = logId;

	if(bEdit == 'Y')
	{
		BX('edit_id').value = key;
		BX('act').value = 'edit';
	}
	<?
	if($arResult["use_captcha"]===true)
	{
		?>
		var im = BX('captcha');
		BX('div_captcha').appendChild(im);
		im.style.display = "block";
		<?
	}
	?>

	if(error == "Y")
	{
		if(comment.length > 0)
		{
			comment = comment.replace(/\/</gi, '<');
			comment = comment.replace(/\/>/gi, '>');
		}
		if(userName.length > 0)
		{
			userName = userName.replace(/\/</gi, '<');
			userName = userName.replace(/\/>/gi, '>');
			BX('user_name').value = userName;
		}
		if(userEmail.length > 0)
		{
			userEmail = userEmail.replace(/\/</gi, '<');
			userEmail = userEmail.replace(/\/>/gi, '>');
			BX('user_email').value = userEmail;
		}
	}

	files = BX('<?=$formParams["FORM_ID"]?>')["UF_BLOG_COMMENT_FILE[]"];
	var form = BX('<?=$formParams["FORM_ID"]?>'),
			files = form["UF_BLOG_COMMENT_FILE[]"];
	if(files !== null && typeof files != 'undefined')
	{
		var end = false, file = false;
		do
		{
			if (!!form["UF_BLOG_COMMENT_FILE[]"][0]) {
				file = form["UF_BLOG_COMMENT_FILE[]"][0];
			} else {
				file = form["UF_BLOG_COMMENT_FILE[]"];
				end = true;
			}
			BX.remove(file);
		} while (!end);
	}

	filesForm = BX.findChild(BX('<?=$formParams["FORM_ID"]?>'), {'className': 'wduf-placeholder-tbody' }, true, false);
	if(filesForm !== null && typeof filesForm != 'undefined')
		BX.cleanNode(filesForm, false);

	filesForm = BX.findChild(BX('<?=$formParams["FORM_ID"]?>'), {'className': 'wduf-selectdialog' }, true, false)
	if(filesForm !== null && typeof filesForm != 'undefined')
		BX.hide(filesForm);

	files = form["UF_BLOG_COMMENT_DOC[]"];
	if(files !== null && typeof files != 'undefined')
	{
		var end = false, file = false;
		do
		{
			if (!!form["UF_BLOG_COMMENT_DOC[]"][0]) {
				file = form["UF_BLOG_COMMENT_DOC[]"][0];
			} else {
				file = form["UF_BLOG_COMMENT_DOC[]"];
				end = true;
			}
			BX.remove(file);
		} while (!end);
	}
	filesForm = BX.findChild(BX('<?=$formParams["FORM_ID"]?>'), {'className': 'file-placeholder-tbody' }, true, false);
	if(filesForm !== null && typeof filesForm != 'undefined')
		BX.cleanNode(filesForm, false);

	filesForm = BX.findChild(BX('<?=$formParams["FORM_ID"]?>'), {'className': 'feed-add-photo-block' }, true, true);
	if(filesForm !== null && typeof filesForm != 'undefined')
	{
		for(i = 0; i < filesForm.length; i++)
		{
			if(BX(filesForm[i]).parentNode.id != 'file-image-template')
				BX.remove(BX(filesForm[i]));
		}
	}

	filesForm = BX.findChild(BX('<?=$formParams["FORM_ID"]?>'), {'className': 'file-selectdialog' }, true, false)
	if(filesForm !== null && typeof filesForm != 'undefined')
		BX.hide(filesForm);

	onLightEditorShow(comment, arFiles);

	pFormCont.style.display = "block";
	pFormCont.style.overflow = "hidden";
	pFormCont.style.height = 0;
	
	(new BX.easing({
		duration : 200,
		start : { opacity : 0, height : 0},
		finish : { opacity: 100, height : pFormCont.scrollHeight},
		transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
		step : function(state){
			pFormCont.style.height = state.height + "px";
			pFormCont.style.opacity = state.opacity / 100;
		},
		complete : function(){
			pFormCont.style.cssText = '';
		}
	})).animate();

	
	BX.hide(BX.findChild(BX('blg-post-'+postId), {className: 'feed-com-footer'}, true, false));
	lastPostComment = postId;
	lastPostCommentId = key;
	return false;
}

function waitResult(id, data, idA)
{
	// var ob = BX('new_comment_' + id); 
	if(data.length < 1)
		return;

	var obNew = BX.processHTML(data, true);
	scripts = obNew.SCRIPT;
	BX.ajax.processScripts(scripts, true);

	if(window.commentEr && window.commentEr == "Y")
	{
		BX('err_comment_'+id).innerHTML = data;
		BX.show(BX('err_comment_'+id));
	}
	else
	{
		if(BX('edit_id').value > 0)
		{
			var oldComment = BX('blg-comment-'+id+'old');
			if(BX.findChild(oldComment, {'attr': {id: 'form_c_del'}}, true, false))
			{
				BX.hide(BX('form_c_del'));
				BX(oldComment.parentNode.parentNode).appendChild(BX('form_c_del')); // Move form
			}

			oldComment.innerHTML = data;
			oldComment.onmouseout = null;
			oldComment.onmouseover = null;
			
			if(BX.browser.IsIE()) //for IE, numbered list not rendering well
				setTimeout(function (){BX('blg-comment-'+id).innerHTML = BX('blg-comment-'+id).innerHTML}, 10);
		}
		else
		{
			var newCont = BX('new_comment_cont_'+id);
			var startHeight = newCont.offsetHeight;

			newCont.style.overflow = 'hidden';
			newCont.style.height = startHeight + 'px';

			newCont.appendChild(BX.create('div', {html: data}));
			if(BX.browser.IsIE()) //for IE, numbered list not rendering well
				setTimeout(function (){BX('new_comment_cont_'+id).innerHTML = BX('new_comment_cont_'+id).innerHTML}, 10);

			(new BX.easing({
				duration : 500,
				start : { height : startHeight},
				finish : { height : newCont.scrollHeight},
				transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
				step : function(state){
					newCont.style.height = state.height + "px";
				},
				complete : function(){
					newCont.style.cssText = '';
				}
			})).animate();
		}

	var pFormCont = BX('form_c_del');
	pFormCont.style.overflow = 'hidden';

	(new BX.easing({
		duration : 200,
		start : { opacity : 100},
		finish : { opacity: 0},
		transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
		step : function(state){
			// pFormCont.style.height = state.height + "px";
			pFormCont.style.opacity = state.opacity / 100;
		},
		complete : function(){
			pFormCont.style.cssText = '';
			pFormCont.style.display = "none";
		}
	})).animate();

		if(lastPostComment > 0)
		{
			var el = BX.findChild(BX('blg-post-'+lastPostComment), {className: 'feed-com-footer'}, true, false);
			BX.show(el);
			BX.findChild(el, {tag: 'a'}, true, false).focus();
		}
	}
	window.commentEr = false;
	bCommentSubmit = false;
	MPFbuttonCloseWait();
	CFbuttonCloseWait();

	BX(idA).removeAttribute('data-send');
}

var bCommentSubmit = false;
function submitComment()
{
	if(bCommentSubmit)
		return false;
	bCommentSubmit = true;
	obForm = BX('<?=$formParams["FORM_ID"]?>');
	
	if(BX('edit_id').value > 0)
	{
		var val = BX('edit_id').value;
		var commentDiv = BX('blg-comment-'+val);
		if(commentDiv)
			commentDiv.id = 'blg-comment-'+val+'old';
	}
	else
		val = BX('parentId').value;
		
	prefix = val;
	if(val == 0)
		prefix = BX('postId').value + '_' + val;

	id = 'new_comment_' + prefix;
	if(BX('err_comment_'+prefix))
		BX('err_comment_'+prefix).innerHTML = '';

	MPFbuttonShowWait(BX('blg-com-btn'));
		
	obForm.target = '';
	BX(id).setAttribute('data-send', 'Y');
	BX.ajax.submit(obForm, function(data){waitResult(prefix, data, id);});
	
	if(
		BX('logId').value > 0
		&& BX("log_entry_follow_" + log_id, true)
	)
	{
		var log_id = BX('logId').value;
		if(BX("log_entry_follow_" + log_id, true))
		{
			var strFollowOld = (BX("log_entry_follow_" + log_id, true).getAttribute("data-follow") == "Y" ? "Y" : "N");
			if (strFollowOld == "N")
			{
				BX.findChild(BX("log_entry_follow_" + log_id, true), { tagName: 'a' }).innerHTML = BX.message('sonetLFollowY');
				BX("log_entry_follow_" + log_id, true).setAttribute("data-follow", "Y");
			}
		}
	}
}

function hideShowComment(id, postId, hide)
{
	urlToHide = '<?=CUtil::JSEscape($arResult["urlToHide"])?>';
	urlToShow = '<?=CUtil::JSEscape($arResult["urlToShow"])?>';
	if(hide)
		url = urlToHide;
	else
		url = urlToShow;
	url = url.replace(/#comment_id#/, id);
	url = url.replace(/#post_id#/, postId);
	url = url.replace(/#source_post_id#/, postId);
	
	var bcn = BX('blg-comment-'+id);
	bcn.id = 'blg-comment-'+id+'old';
	BX('err_comment_'+id).innerHTML = '';

	BX.ajax.get(url, function(data) {
		CFbuttonCloseWait();

		var obNew = BX.processHTML(data, true);
		scripts = obNew.SCRIPT;
		BX.ajax.processScripts(scripts, true);
		var nc = BX('new_comment_'+id);
		var bc = BX('blg-comment-'+id+'old');
		
		nc.style.display = "none";
		nc.innerHTML = data;
		
		if(BX('blg-comment-'+id))
		{
			bc.innerHTML = BX('blg-comment-'+id).innerHTML;
		}
		else
		{
			BX('err_comment_'+id).innerHTML = nc.innerHTML;
		}
		BX('blg-comment-'+id+'old').id = 'blg-comment-'+id;
	});

	return false;
}

function deleteComment(id, postId)
{
	urlToDelete = '<?=CUtil::JSEscape($arResult["urlToDelete"])?>';
	url = urlToDelete.replace(/#comment_id#/, id);
	url = url.replace(/#post_id#/, postId);
	url = url.replace(/#source_post_id#/, postId);

	BX.ajax.get(url, function(data) {
		CFbuttonCloseWait();

		var obNew = BX.processHTML(data, true);
		scripts = obNew.SCRIPT;
		BX.ajax.processScripts(scripts, true);

		var nc = BX('new_comment_'+id);
		nc.style.display = "none";
		nc.innerHTML = data;

		if(BX('blg-com-err'))
		{
			BX('err_comment_'+id).innerHTML = nc.innerHTML;
		}
		else
		{
			var el = BX('blg-comment-'+id);
			el.innerHTML = nc.innerHTML;
			el.onmouseout = null;
			el.onmouseover = null;

			var el = BX('blg-comment-'+id+'old');
			if(el)
			{
				el.onmouseout = null;
				el.onmouseover = null;
			}
		}
		nc.innerHTML = '';
	});

	return false;
}

function showHiddenComments(id, source, comment, startHeight)
{
	if(comment)
	{
		var el = BX.findChild(BX('blg-comment-' + comment), {className: 'feed-com-text-inner'}, true, false);
		el2 = BX.findChild(BX('blg-comment-' + comment), {className: 'feed-com-text-inner-inner'}, true, false);
		var heightFull = el2.offsetHeight;
		BX.remove(source);
		var el3 = BX.findParent(BX('blg-comment-' + comment), {attr: {id: 'blog-comment-hidden-'+id}}, true, false);
		if(!!el3)
			el3.style.maxHeight = (el3.offsetHeight+heightFull - 200)+'px';
	}
	else
	{
		var el = BX('blog-comment-hidden-' + id);
	}
	if(el)
	{
		var elC = 'N';
		if(BX('comshowend-'+id))
			elC = BX('comshowend-'+id).value;
		if(el.style.display == "none" || elC == "N" || comment)
		{
			if(!comment)
			{
				el.style.maxHeight = '100%';
				el.style.display = "block";
				var heightFull = el.offsetHeight;
			}
			if(!startHeight)
				startHeight = 0;

			var fxStart = startHeight;
			var fxFinish = heightFull;

			if(comment)
			{
				var fxStart = 200;
				var start1 = {height:fxStart};
				var finish1 = {height:fxFinish};
			}
			else
			{
				var start1 = {height:fxStart, opacity:0};
				var finish1 = {height:fxFinish, opacity:100};
			}

			var time = 1.0 * (fxFinish - fxStart) / (2000 - fxStart);
			if(time < 0.3)
				time = 0.3;			
			if(time > 0.8)
				time = 0.8;

			el.style.maxHeight = start1.height+'px';
			el.style.overflow = 'hidden';

			(new BX.easing({
				duration : time*1000,
				start : start1,
				finish : finish1,
				transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
				step : function(state){
					el.style.maxHeight = state.height + "px";
					el.style.opacity = state.opacity / 100;
				},
				complete : function(){
					el.style.cssText = '';
					el.style.maxHeight = 'none';
				}
			})).animate();

			if(!comment && BX('comshowend-'+id).value == "Y")						
			{
				BX.findChild(source, {'className': 'feed-com-all-hide' }, true, false).style.display = "inline-block";
				BX.findChild(source, {'className': 'feed-com-all-text' }, true, false).style.display = "none";
				BX.addClass(source.parentNode, "feed-com-all-expanded");
			}
		}
		else
		{
			if(!comment)
			{
				var heightFull = el.offsetHeight;
				BX.removeClass(source, "feed-com-all-expanded");
			}
			var fxStart = heightFull;
			var fxFinish = 0;
			var time = 1.0 * fxStart / 2000;
			if(time < 0.3)
				time = 0.3;			
			if(time > 0.5)
				time = 0.5;

			(new BX.easing({
				duration : time*1000,
				start : {height:fxStart, opacity:100},
				finish : {height:fxFinish, opacity:0},
				transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
				step : function(state){
					el.style.maxHeight = state.height + "px";
					el.style.opacity = state.opacity / 100;
				},
				complete : function(){
					el.style.cssText = '';
					if(!comment)
					{
						el.style.maxHeight = fxStart+'px';
						el.style.display = "none";
					}
				}
			})).animate();

			if(!comment)
			{
				BX.findChild(source, {'className': 'feed-com-all-text' }, true, false).style.display = "inline-block";
				BX.findChild(source, {'className': 'feed-com-all-hide' }, true, false).style.display = "none";
				BX.removeClass(source.parentNode, "feed-com-all-expanded");
			}
		}
	}
}

function onLightEditorShow(content, arFiles)
{
	<?
	if(strlen($formParams["LHE"]["jsObjName"]) > 0)
	{
		?>
		if (!window.<?=$formParams["LHE"]["jsObjName"]?>)
			return BX.addCustomEvent(window, 'LHE_OnInit', function(){setTimeout(function(){onLightEditorShow(content, arFiles);}, 500);});

		
		window['PlEditor<?=$formParams["FORM_ID"]?>'].arFiles = {};
		if(arFiles.length > 0)
		{
			for(i = 0; i < arFiles.length; i++)
			{
				window['PlEditor<?=$formParams["FORM_ID"]?>'].arFiles[arFiles[i].id] = arFiles[i];
			}
		}

		<?=$formParams["LHE"]["jsObjName"]?>.SetContent(content || '');
		<?=$formParams["LHE"]["jsObjName"]?>.CreateFrame(); // We need to recreate editable frame after reappending editor container
		<?=$formParams["LHE"]["jsObjName"]?>.SetEditorContent(<?=$formParams["LHE"]["jsObjName"]?>.content);
		<?=$formParams["LHE"]["jsObjName"]?>.pFrame.style.height = <?=$formParams["LHE"]["jsObjName"]?>.arConfig.height;
		<?=$formParams["LHE"]["jsObjName"]?>.ResizeFrame();
		<?=$formParams["LHE"]["jsObjName"]?>.AutoResize();
		<?=$formParams["LHE"]["jsObjName"]?>.SetFocus();
		<?
	}
	?>
}

function cancelComment()
{
	var pFormCont = BX('form_c_del');
	pFormCont.style.overflow = 'hidden';

	(new BX.easing({
		duration : 200,
		start : { opacity : 0, height : pFormCont.scrollHeight},
		finish : { opacity: 100, height : 0},
		transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
		step : function(state){
			pFormCont.style.height = state.height + "px";
			pFormCont.style.opacity = state.opacity / 100;
		},
		complete : function(){
			pFormCont.style.cssText = '';
			pFormCont.style.display = "none";
		}
	})).animate();

	if(lastPostComment > 0)
	{
		BX.show(BX.findChild(BX('blg-post-'+lastPostComment), {className: 'feed-com-footer'}, true, false));
		if(!BX.findChild(BX('blg-post-'+lastPostComment), {className: 'feed-com-block'}, true, false))
		{
			BX.hide(BX.findChild(BX('blg-post-'+lastPostComment), {className: 'feed-comments-block'}, true, false));
		}
		BX.hide(BX('err_comment_'+lastPostComment+'_0'));
	}

	MPFbuttonCloseWait();
}

function showMoreComments(id, source)
{
	var lastComment = BX('comcntshow-'+id).value;
	var el = BX('blog-comment-hidden-' + id);
	if(lastComment == 0 && el.innerHTML.length > 0)
	{
		showHiddenComments(id, source, false);
	}
	else
	{
		urlToMore = '<?=CUtil::JSEscape($arResult["urlToMore"])?>';
		url = urlToMore.replace(/#comment_id#/, lastComment);
		url = url.replace(/#post_id#/, id);
		url = url.replace(/#source_post_id#/, id);

		BX.ajax.get(url, function(data) {

			var obNew = BX.processHTML(data, true);
			scripts = obNew.SCRIPT;
			BX.ajax.processScripts(scripts, true);

			var el = BX('blog-comment-hidden-' + id);
			var height = el.offsetHeight;
			el.innerHTML = data + el.innerHTML;
			showHiddenComments(id, source, false, height);
		});
	}
}

var lastWaitElement = null;
CFbuttonShowWait = function(el)
{
	if (el && !BX.type.isElementNode(el))
		el = null;
	el = el || this;

	if (BX.type.isElementNode(el))
	{
		BX.defer(function(){el.disabled = true})();

		var
			waiter_parent = BX.findParent(el, BX.is_relative),
			pos = BX.pos(el, !!waiter_parent);

		el.bxwaiter = (waiter_parent || document.body).appendChild(BX.create('DIV', {
			props: {className: 'blog-comment-wait'},
			style: {top: '5px', right: '5px', position: 'absolute'}
		}));
		lastWaitElement = el;

		return el.bxwaiter;
	}
}

CFbuttonCloseWait = function(el)
{
	if (el && !BX.type.isElementNode(el))
		el = null;
	el = el || lastWaitElement || this;

	if (BX.type.isElementNode(el))
	{
		if (el.bxwaiter && el.bxwaiter.parentNode)
		{
			el.bxwaiter.parentNode.removeChild(el.bxwaiter);
			el.bxwaiter = null;
		}

		el.disabled = false;
		if (lastWaitElement == el)
			lastWaitElement = null;
	}
}

<?
if(strlen($formParams["FORM_ID"]) > 0)
{
	?>
	bShow<?=$formParams["FORM_ID"]?> = false;

	function mpfReInitLHE<?=$formParams["FORM_ID"]?>(p, p1)
	{
		if(bShow<?=$formParams["FORM_ID"]?>)
		{
			if(p1 == 'log_external_container' && p.substr(0, 17) == 'sonet_log_content')
			{
				window.<?=$formParams["LHE"]["jsObjName"]?>.ReInit('<?=CUtil::JSEscape($formParams["TEXT"]["VALUE"])?>');
				BX.removeCustomEvent(window, 'onSocNetLogMoveBody', mpfReInitLHE<?=$formParams["FORM_ID"]?>);
			}
		}
		else
			setTimeout(function(){mpfReInitLHE<?=$formParams["FORM_ID"]?>(p, p1);}, 50);
	}
	BX.addCustomEvent(window, 'onSocNetLogMoveBody', mpfReInitLHE<?=$formParams["FORM_ID"]?>);	
	<?
}
?>
<?if(CModule::IncludeModule("pull") && IntVal($arResult["userID"]) > 0 && !$arParams["bFromList"] /*(!$arParams["bFromList"] || CModule::IncludeModule("bitrix24"))*/):?>
function showNewComment(id, postId)
{
	if(!BX('blg-comment-'+id))
	{
		urlToNew = '<?=CUtil::JSEscape($arResult["urlToNew"])?>';
		url = urlToNew.replace(/#comment_id#/, id);
		url = url.replace(/#post_id#/, postId);
		url = url.replace(/#source_post_id#/, postId);

		BX.ajax.get(url, function(data) {
			BX.show(BX.findChild(BX('blg-post-'+postId), {className: 'feed-comments-block'}, true, false));
			var obNew = BX.processHTML(data, true);
			scripts = obNew.SCRIPT;
			BX.ajax.processScripts(scripts, true);
			dataDiv = document.createElement('div');
			dataDiv.innerHTML = data;
			BX('new_comment_cont_' + postId + '_0').appendChild(dataDiv);

			dataDiv.style.overflow = 'hidden';
			dataDiv.style.height = 0;

			(new BX.easing({
				duration : 1000,
				start : { opacity : 0, height : 0},
				finish : { opacity: 100, height : dataDiv.scrollHeight},
				transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
				step : function(state){
					dataDiv.style.height = state.height + "px";
					dataDiv.style.opacity = state.opacity / 100;
				},
				complete : function(){
					dataDiv.style.cssText = '';
				}
			})).animate();

			BX.fx.colorAnimate.addRule('animationRule',"#FFF","#fbf2c8", "background-color", 100, 20, false);
			BX.fx.colorAnimate.addRule('animationRule2',"#fbf2c8","#FFF", "background-color", 100, 20, false);

			BX.fx.colorAnimate(BX.findChild(BX('blg-comment-'+id), {className: 'feed-com-block'}, true, false), 'animationRule');
			setTimeout(function(){BX.fx.colorAnimate(BX.findChild(BX('blg-comment-'+id), {className: 'feed-com-block'}, true, false), 'animationRule2');}, 30000);
		});
	}
}

BX.addCustomEvent("onPullEvent", function(module_id,command,params) {
	if (module_id == "blog" && command == 'comment')
	{
		if(
				!BX('blg-comment-'+params["ID"]) && 
				(
					!BX('new_comment_'+params["POST_ID"]+'_0') ||
					(BX('new_comment_'+params["POST_ID"]+'_0') && BX('new_comment_'+params["POST_ID"]+'_0').getAttribute('data-send') != "Y")
				)
			)
		{
			showNewComment(params["ID"], params["POST_ID"]);
		}
	}
});

<?endif;?>
<?
if (IsModuleInstalled("webdav"))
{
	?>
	BX.addCustomEvent(
		BX.findChild(BX('<?=$formParams["FORM_ID"]?>'), {'className': 'feed-add-post' }, true, false),
		'BFileDLoadFormController',
		function(){
			setTimeout(function (){
			BX.findChild(BX('<?=$formParams["FORM_ID"]?>'), {'className': 'file-label' }, true, false).innerHTML = '<?=GetMessageJS("BLOG_P_PHOTO")?>';
		}, 500);
		}
		);
	<?
}
?>
</script>