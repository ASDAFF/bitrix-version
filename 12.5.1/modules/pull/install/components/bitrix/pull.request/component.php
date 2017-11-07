<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (isset($_REQUEST['AJAX_CALL']) && $_REQUEST['AJAX_CALL'] == 'Y')
	return;

if (defined('BX_SKIP_PULL_INIT'))
	return;
else
	define("BX_SKIP_PULL_INIT", true);

if (intval($USER->GetID()) <= 0)
	return;

if (!CModule::IncludeModule('pull'))
	return;

CJSCore::Init(array('pull'));

return Array();
?>