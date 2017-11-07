<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?><?

/* @var \CMain $APPLICATION*/
$APPLICATION->SetTitle(GetMessage('MAIN_MAIL_UNSUBSCRIBE_TITLE'));

$messageDictionary = array(
	'1000' => GetMessage('MAIN_MAIL_UNSUBSCRIBE_ERROR_UNSUB'),
	'1001' => GetMessage('MAIN_MAIL_UNSUBSCRIBE_ERROR_NOT_SELECTED'),
);

$this->setFrameMode(false);

try
{
	$arTag = \Bitrix\Main\Mail\Tracking::parseSignedTag(is_string($_REQUEST['tag']) ? $_REQUEST['tag'] : '');
	$arTag['IP'] = $_SERVER['REMOTE_ADDR'];

	$arResult = array();
	$arResult['FORM_URL'] = $APPLICATION->getCurPageParam("",array('success'));
	$arResult['LIST'] = \Bitrix\Main\Mail\Tracking::getSubscriptionList($arTag);

	if($_SERVER['REQUEST_METHOD'] == 'POST' && array_key_exists('MAIN_MAIL_UNSUB_BUTTON', $_POST) && check_bitrix_sessid())
	{
		$unsubscribeListFromForm = is_array($_POST['MAIN_MAIL_UNSUB']) ? $_POST['MAIN_MAIL_UNSUB'] : array();

		$arUnsubscribeList = array();
		foreach($arResult['LIST'] as $key => $unsubItem)
		{
			if(in_array($unsubItem['ID'], $unsubscribeListFromForm))
			{
				$arUnsubscribeList[] = $unsubItem['ID'];
				$arSubList[$key]['SELECTED'] = true;
			}
			else
			{
				$arResult['LIST'][$key]['SELECTED'] = false;
			}
		}

		$messageResult = null;
		if(!empty($arUnsubscribeList))
		{
			$arTag['FIELDS']['UNSUBSCRIBE_LIST'] = $arUnsubscribeList;
			$result = \Bitrix\Main\Mail\Tracking::unsubscribe($arTag);
			if ($result)
			{
				$messageResult = '0';
			}
			else
			{
				$messageResult = '1000';
			}
		}
		else
		{
			$messageResult = '1001';
		}

		if($messageResult !== null)
		{
			LocalRedirect($APPLICATION->GetCurPageParam("unsubscribe_result=" . $messageResult, array("unsubscribe_result")));
		}
	}
	else
	{
		if(isset($_REQUEST['unsubscribe_result']) && is_numeric($_REQUEST['unsubscribe_result']))
		{
			if($_REQUEST['unsubscribe_result'] == '0')
			{
				$arResult['DATA_SAVED'] = 'Y';
			}
			elseif(isset($messageDictionary[$_REQUEST['unsubscribe_result']]))
			{
				$arResult['ERROR'] = $messageDictionary[$_REQUEST['unsubscribe_result']];
			}
		}
	}
}
catch (\Bitrix\Main\Security\Sign\BadSignatureException $exception)
{
	$arResult['ERROR'] = GetMessage('MAIN_MAIL_UNSUBSCRIBE_ERROR_SECURITY');
}

$this->IncludeComponentTemplate();