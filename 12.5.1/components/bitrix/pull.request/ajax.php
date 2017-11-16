<?
if (!defined('PULL_AJAX_INIT'))
{
	define("PULL_AJAX_INIT", true);
	define("PUBLIC_AJAX_MODE", true);
	define("NO_KEEP_STATISTIC", "Y");
	define("NO_AGENT_STATISTIC","Y");
	define("NO_AGENT_CHECK", true);
	define("DisableEventsCheck", true);
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
}
header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

// NOTICE
// Before execute next code, execute file /module/pull/ajax_hit.php
// for skip onProlog events

if (!CModule::IncludeModule("pull"))
{
	echo CUtil::PhpToJsObject(Array('ERROR' => 'PULL_MODULE_IS_NOT_INSTALLED'));
	die();
}
if (intval($USER->GetID()) <= 0)
{
	echo CUtil::PhpToJsObject(Array('ERROR' => 'AUTHORIZE_ERROR'));
	die();
}
if (check_bitrix_sessid())
{
	if ($_POST['PULL_GET_CHANNEL'] == 'Y')
	{
		$arChannel = CPullChannel::Get($USER->GetId(), true);
		if (is_array($arChannel))
		{
			echo CUtil::PhpToJsObject(Array(
				'CHANNEL_ID' => $arChannel['CHANNEL_ID'],
				'LAST_ID' => $arChannel['LAST_ID'],
				'PATH' => $arChannel['PATH'],
				'METHOD' => $arChannel['METHOD'],
				'ERROR' => '',
			));
		}
		else
			echo CUtil::PhpToJsObject(Array('ERROR' => 'ERROR_OPEN_CHANNEL'));
	}
	elseif ($_POST['PULL_UPDATE_WATCH'] == 'Y')
	{
		foreach ($_POST['WATCH'] as $tag)
			CPullWatch::Extend($USER->GetID(), $tag);

		echo CUtil::PhpToJsObject(Array('ERROR' => ''));
	}
	elseif ($_POST['PULL_UPDATE_STATE'] == 'Y')
	{
		$arMessage = CPullStack::Get($_POST['CHANNEL_ID'], intval($_POST['CHANNEL_LAST_ID']));
		echo CUtil::PhpToJsObject(Array('MESSAGE' => $arMessage, 'ERROR' => ''));
	}
	else
	{
		echo CUtil::PhpToJsObject(Array('ERROR' => 'UNKNOWN_ERROR'));
	}
}
else
{
	echo CUtil::PhpToJsObject(Array(
		'BITRIX_SESSID' => bitrix_sessid(),
		'ERROR' => 'SESSION_ERROR'
	));
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>