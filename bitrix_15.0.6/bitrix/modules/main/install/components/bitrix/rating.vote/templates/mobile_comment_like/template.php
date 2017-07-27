<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH."/components/bitrix/rating.vote/mobile_comment_like/script_attached.js");

?><script>
BX.message({
	RVCSessID: '<?=CUtil::JSEscape(bitrix_sessid())?>',
	RVCPathToUserProfile: '<?=CUtil::JSEscape(htmlspecialcharsbx(str_replace("#", "(_)", $arResult['PATH_TO_USER_PROFILE'])))?>',
	RVCListBack: '<?=GetMessageJS("RATING_COMMENT_LIST_BACK")?>',
	RVCText: '<?=GetMessageJS("RATING_COMMENT_LIKE")?>',
	RVCText2: '<?=GetMessageJS("RATING_COMMENT_LIKE2")?>'
});
</script><?
?><div class="post-comment-likes<?=($arResult['USER_HAS_VOTED'] == "N" ? "": "-liked")?>" id="bx-ilike-button-<?=CUtil::JSEscape(htmlspecialcharsbx($arResult['VOTE_ID']))?>"><?
	if (
		intval($arResult["TOTAL_VOTES"]) > 1
		|| (
			intval($arResult["TOTAL_VOTES"]) == 1
			&& $arResult['USER_HAS_VOTED'] == "N"
		)
	)
	{
		?><div class="post-comment-likes-text"><?=GetMessage('RATING_COMMENT_LIKE2')?></div><?
		?><div class="post-comment-likes-counter" id="bx-ilike-count-<?=CUtil::JSEscape(htmlspecialcharsbx($arResult['VOTE_ID']))?>"><?
			?><?=htmlspecialcharsEx($arResult['TOTAL_VOTES'])?><?
		?></div><?
	}
	else
	{
		?><div class="post-comment-likes-text"><?=GetMessage('RATING_COMMENT_LIKE')?></div><?
		?><div class="post-comment-likes-counter" id="bx-ilike-count-<?=CUtil::JSEscape(htmlspecialcharsbx($arResult['VOTE_ID']))?>" style="display: none;"><?
			?><?=htmlspecialcharsEx($arResult['TOTAL_VOTES'])?><?
		?></div><?
	}
?></div><?
?><script type="text/javascript">
BX.ready(function() {
	if (
		typeof MBTasks != 'undefined'
		|| BX.message('MSLPageId')
	)
	{
		if (
			!window.RatingLikeComments 
			&& top.RatingLikeComments
		)
		{
			RatingLikeComments = top.RatingLikeComments;
		}

		RatingLikeComments.Set(
			'<?=CUtil::JSEscape(htmlspecialcharsbx($arResult['VOTE_ID']))?>', 
			'<?=CUtil::JSEscape(htmlspecialcharsbx($arResult['ENTITY_TYPE_ID']))?>', 
			'<?=IntVal($arResult['ENTITY_ID'])?>', 
			'<?=CUtil::JSEscape(htmlspecialcharsbx($arResult['VOTE_AVAILABLE']))?>'
		);

		if (app.enableInVersion(2))
		{
			BX.bind(BX('bx-ilike-count-<?=CUtil::JSEscape(htmlspecialcharsbx($arResult['VOTE_ID']))?>'), 'click', function(e) 
			{
				RatingLikeComments.List('<?=CUtil::JSEscape(htmlspecialcharsbx($arResult['VOTE_ID']))?>');		
				BX.PreventDefault(e);
			});
		}
	}
});
</script>