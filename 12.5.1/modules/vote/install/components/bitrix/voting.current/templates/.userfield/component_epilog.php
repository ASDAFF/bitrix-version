<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$uid = $this->params["uid"];
$controller = $this->params["controller"];
$lastVote = intval($this->params["lastVote"]);
?>
<script type="text/javascript">
<?if ($arParams['AJAX_MODE'] == 'Y'){?>
	BX.loadCSS('/bitrix/components/bitrix/voting.current/templates/.userfield/style.css'); <?}
?>
window.__votevar<?=$uid?> = 0;
window.__vote<?=$uid?> = function() {
	if (! <?=$controller?>) {
		window.__votevar<?=$uid?>++;
		if (window.__votevar<?=$uid?> <= 100)
			setTimeout(__vote<?=$uid?>, 10);
		return false;
	}

	if (! <?=$controller?>.loaded) {
		BX.loadScript(
				[
					'<?=CUtil::GetAdditionalFileURL('/bitrix/components/bitrix/voting.current/templates/.userfield/script.js')?>',
					'/bitrix/js/main/core/core_ajax.js',
					'/bitrix/js/main/core/core_dd.js',
					'/bitrix/js/main/core/core_popup.js'
				], function() {
					BX.loadCSS('/bitrix/components/bitrix/rating.vote/templates/like/popup.css');
			<?=$controller?>.loaded = true;
					BVote<?=$uid?> = new BVotedUser({
						'CID' : '<?=$uid?>',
						'controller': <?=$controller?>,
						'urlTemplate' : "<?=CUtil::JSEscape($arParams["~PATH_TO_USER"]);?>",
						'nameTemplate' : "<?=CUtil::JSEscape($arParams["~NAME_TEMPLATE"]);?>",
						'url' : "<?=CUtil::JSEscape(htmlspecialcharsback(POST_FORM_ACTION_URI))?>",
						'voteId' : <?=$arParams["VOTE_ID"]?>,
						'startCheck' : <?=$lastVote?>
					});
				}
		);
	}
}
window.__vote<?=$uid?>();
</script>
<?if ($_REQUEST["VOTE_ID"] == $arParams["VOTE_ID"] && $_REQUEST["AJAX_POST"] == "Y" && check_bitrix_sessid()):
	$res = ob_get_clean();
	$APPLICATION->RestartBuffer();
	echo $res;
	die();
endif;
?>
</div>