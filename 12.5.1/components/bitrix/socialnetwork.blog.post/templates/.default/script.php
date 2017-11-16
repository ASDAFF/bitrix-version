<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?CUtil::InitJSCore(array("tooltip"));?>
<script>
function showHiddenDestination(cont, el)
{
	BX.hide(el);
	BX('blog-destination-hidden-'+cont).style.display = 'inline';
}

function showBlogPost(id, source)
{
	var el = BX.findChild(BX('blg-post-' + id), {className: 'feed-post-text-block-inner'}, true, false);
	el2 = BX.findChild(BX('blg-post-' + id), {className: 'feed-post-text-block-inner-inner'}, true, false);
	BX.remove(source);

	if(el)
	{
		var fxStart = 300;
		var fxFinish = el2.offsetHeight;
		(new BX.fx({
			time: 1.0 * (fxFinish - fxStart) / (1200-fxStart),
			step: 0.05,
			type: 'linear',
			start: fxStart,
			finish: fxFinish,
			callback: BX.delegate(__blogExpandSetHeight, el),
			callback_complete: BX.delegate(function() {})
		})).start();								
	}
}

function __blogExpandSetHeight(height)
{
	this.style.maxHeight = height + 'px';
}

function deleteBlogPost(id)
{
	url = '<?=$arResult["urlToDelete"]?>';
	url1 = url.replace('#del_post_id#', id);

	if(BX.findChild(BX('blg-post-'+id), {'attr': {id: 'form_c_del'}}, true, false))
	{
		BX.hide(BX('form_c_del'));
		BX(BX('blg-post-'+id).parentNode.parentNode).appendChild(BX('form_c_del')); // Move form
	}

	BX.ajax.get(url1, function(data){
		if(window.deletePostEr && window.deletePostEr == "Y")
		{
			var el = BX('blg-post-'+id);
			BX.findChild(el, {className: 'feed-post-cont-wrap'}, true, false).insertBefore(data, BX.findChild(el, {className: 'feed-user-avatar'}, true, false));
		}
		else
		{
			BX('blg-post-'+id).parentNode.innerHTML = data;
		}
		__blogCloseWait();
	});
	
	return false;
}

var waitPopupBlogImage = null;
function blogShowImagePopup(src)
{
	if(!waitPopupBlogImage)
	{
		waitPopupBlogImage = new BX.PopupWindow('blogwaitPopupBlogImage', window, {
			autoHide: true,
			lightShadow: false,
			zIndex: 2,
			content: BX.create('IMG', {props: {src: src, id: 'blgimgppp'}}),
			closeByEsc: true,
			closeIcon: true
		});
	}
	else
	{
		BX('blgimgppp').src = '/bitrix/images/1.gif';
		BX('blgimgppp').src = src;
	}

	waitPopupBlogImage.setOffset({
		offsetTop: 0,
		offsetLeft: 0
	});

	setTimeout(function(){waitPopupBlogImage.adjustPosition()}, 100);	
	waitPopupBlogImage.show();

}

function __blogPostSetFollow(log_id)
{
	var strFollowOld = (BX("log_entry_follow_" + log_id, true).getAttribute("data-follow") == "Y" ? "Y" : "N");
	var strFollowNew = (strFollowOld == "Y" ? "N" : "Y");	

	if (BX("log_entry_follow_" + log_id, true))
	{
		BX.findChild(BX("log_entry_follow_" + log_id, true), { tagName: 'a' }).innerHTML = BX.message('sonetBPFollow' + strFollowNew);
		BX("log_entry_follow_" + log_id, true).setAttribute("data-follow", strFollowNew);
	}
				
	BX.ajax({
		url: BX.message('sonetBPSetPath'),
		method: 'POST',
		dataType: 'json',
		data: {
			"log_id": log_id,
			"action": "change_follow",
			"follow": strFollowNew,
			"sessid": BX.bitrix_sessid(),
			"site": BX.message('sonetBPSiteId')
		},
		onsuccess: function(data) {
			if (
				data["SUCCESS"] != "Y"
				&& BX("log_entry_follow_" + log_id, true)
			)
			{
				BX.findChild(BX("log_entry_follow_" + log_id, true), { tagName: 'a' }).innerHTML = BX.message('sonetBPFollow' + strFollowOld);
				BX("log_entry_follow_" + log_id, true).setAttribute("data-follow", strFollowOld);
			}
		},
		onfailure: function(data) {
			if (BX("log_entry_follow_" +log_id, true))
			{
				BX.findChild(BX("log_entry_follow_" + log_id, true), { tagName: 'a' }).innerHTML = BX.message('sonetBPFollow' + strFollowOld);
				BX("log_entry_follow_" + log_id, true).setAttribute("data-follow", strFollowOld);
			}		
		}
	});
	return false;
}
</script>