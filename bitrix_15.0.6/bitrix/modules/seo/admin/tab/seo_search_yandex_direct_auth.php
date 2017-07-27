<?php
/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global bool $bNeedAuth
 * @global array $currentUser
 * @global Bitrix\Seo\Engine\YandexDirect $engine
 */
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\Converter;

$request = Bitrix\Main\Context::getCurrent()->getRequest();

if(
	$request->isPost() && isset($request['CODE']) && check_bitrix_sessid())
{
	try
	{
		$engine->getAuth($request['CODE']);
		LocalRedirect($APPLICATION->GetCurPageParam('oauth=yes', array('CODE', 'oauth')));
	}
	catch (Exception $e)
	{
		$message = new CAdminMessage(Loc::getMessage('SEO_ERROR_GET_ACCESS', array("#ERROR_TEXT#" => $e->getMessage())));
		echo $message->Show();
	}
}

?>

<?=BeginNote();?>
	<div id="auth_button" style="<?=$bNeedAuth ? 'display:block' : 'display:none'?>;">
		<p><?=Loc::getMessage('SEO_AUTH_HINT')?></p>
		<input type=button onclick="makeAuth()" value="<?=Loc::getMessage('SEO_AUTH_YANDEX')?>" />
	</div>
	<div id="auth_code" style="display: none;">
		<form name="auth_code_form" action="<?=Converter::getHtmlConverter()->encode($APPLICATION->getCurPageParam("", array("CODE", "oauth")))?>" method="POST"><?=bitrix_sessid_post();?><?=Loc::getMessage('SEO_AUTH_CODE')?>: <input type="text" name="CODE" style="width: 200px;" /> <input type="submit" name="send_code" value="<?=Loc::getMessage('SEO_AUTH_CODE_SUBMIT')?>"></form></div>
<?
if(!$bNeedAuth)
{
	if(is_array($currentUser))
	{
		?>
		<div id="auth_result" class="seo-auth-result">
			<b><?=Loc::getMessage('SEO_AUTH_CURRENT')?>:</b><div style="width: 300px; padding: 10px 0 0 0;">
				<?=Converter::getHtmlConverter()->encode($currentUser['real_name'].' ('.$currentUser['display_name'].')')?><br />
				<a href="javascript:void(0)" onclick="makeNewAuth()"><?=Loc::getMessage('SEO_AUTH_CANCEL')?></a>
				<div style="clear: both;"></div>
			</div>
		</div>
	<?
	}
}
?>
<?=EndNote();?>

<script type="text/javascript">
	function makeNewAuth()
	{
		BX.showWait(BX('auth_result'));
		BX.ajax.loadJSON('/bitrix/tools/seo_yandex_direct.php?action=nullify_auth&sessid=' + BX.bitrix_sessid(), function(){
			window.lastSeoResult = null;
			BX.closeWait(BX('auth_result'));
			BX('auth_result').style.display = 'none';
			BX('auth_button').style.display = 'block';
		});
	}

	function makeAuth()
	{
		BX('auth_button').style.display = 'none';
		BX('auth_code').style.display = 'block';
		BX.util.popup('<?=CUtil::JSEscape($engine->getAuthUrl())?>', 700, 500);
	}
</script>