<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (isset($_REQUEST['AJAX_CALL']) && $_REQUEST['AJAX_CALL'] == 'Y')
	return;

if (intval($USER->GetID()) <= 0)
	return;

if (!CModule::IncludeModule('pull'))
	return;

if (CPullOptions::CheckNeedRun())
{
	CJSCore::Init(array('pull'));

	$arResult = CPullChannel::GetUserConfig($GLOBALS['USER']->GetID());

	if (!(isset($arParams['TEMPLATE_HIDE']) && $arParams['TEMPLATE_HIDE'] == 'Y'))
		$this->IncludeComponentTemplate();
}

return $arResult;
?>