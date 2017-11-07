<?
if($_SERVER["REQUEST_METHOD"] == "POST" && array_key_exists("PULL_AJAX_CALL", $_REQUEST) && $_REQUEST["PULL_AJAX_CALL"] === "Y")
{
	$arResult = array();
	global $USER, $APPLICATION, $DB;
	include($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/pull.request/ajax.php");
	die();
}
else if($_SERVER["REQUEST_METHOD"] == "POST" && array_key_exists("NPULL_AJAX_CALL", $_REQUEST) && $_REQUEST["NPULL_AJAX_CALL"] === "Y")
{
	$arResult = array();
	global $USER, $APPLICATION, $DB;
	include($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/pull.request/najax.php");
	die();
}
else if (!defined('BX_PULL_SKIP_INIT') && !(isset($_REQUEST['AJAX_CALL']) && $_REQUEST['AJAX_CALL'] == 'Y')
		&& intval($GLOBALS['USER']->GetID()) > 0 && CModule::IncludeModule('pull'))
{
	define("BX_PULL_SKIP_INIT", true);

	if (CPullOptions::CheckNeedRun())
	{
		CJSCore::Init(array('pull'));

		$pullConfig = CPullChannel::GetUserConfig($GLOBALS['USER']->GetID());

		global $APPLICATION;
		$APPLICATION->AddAdditionalJS('<script type="text/javascript">BX.bind(window, "load", function() { BX.PULL.start('.(empty($pullConfig)? '': CUtil::PhpToJsObject($pullConfig)).'); });</script>');
		/*
		if(!defined("BX_DESKTOP") && !defined("BX_MOBILE") && !defined("ADMIN_SECTION") && !IsModuleInstalled('b24network') && IsModuleInstalled('bitrix24') && (COption::GetOptionString('bitrix24', 'network', 'N') == 'Y'))
		{
			CJSCore::Init(array('npull'));
			$APPLICATION->AddAdditionalJS('<script type="text/javascript">BX.bind(window, "load", function() { BX.NPULL.start(); });</script>');
		}
		*/
	}
}
?>
