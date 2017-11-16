<?
if (!defined('IM_AJAX_INIT'))
{
	define("IM_AJAX_INIT", true);
	define("PUBLIC_AJAX_MODE", true);
	define("NO_KEEP_STATISTIC", "Y");
	define("NO_AGENT_STATISTIC","Y");
	define("NO_AGENT_CHECK", true);
	define("DisableEventsCheck", true);
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
}
header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

// NOTICE
// Before execute next code, execute file /module/im/ajax_hit.php
// for skip onProlog events

if (!CModule::IncludeModule("im"))
{
	echo CUtil::PhpToJsObject(Array('ERROR' => 'IM_MODULE_NOT_INSTALLED'));
	die();
}

if (intval($USER->GetID()) <= 0)
{
	echo CUtil::PhpToJsObject(Array('ERROR' => 'AUTHORIZE_ERROR'));
	die();
}

if (check_bitrix_sessid())
{
	if ($_POST['IM_UPDATE_STATE'] == 'Y')
	{
		if (isset($_POST['DESKTOP']) && $_POST['DESKTOP'] == 'Y')
			CIMMessenger::SetDesktopStatusOnline();

		CIMContactList::SetOnline();

		if (isset($_POST['FN']))
		{
			$_POST['FN'] = CUtil::JsObjectToPhp($_POST['FN']);
			if (is_array($_POST['FN']))
			{
				foreach ($_POST['FN'] as $key => $value)
					$_SESSION['IM_FLASHED_NOTIFY'][] = $key;
			}
		}

		if (isset($_POST['FM']))
		{
			$_POST['FM'] = CUtil::JsObjectToPhp($_POST['FM']);
			if (is_array($_POST['FM']))
			{
				foreach ($_POST['FM'] as $userId => $data)
					foreach ($data as $key => $value)
						$_SESSION['IM_FLASHED_MESSAGE'][] = $key;
			}
		}

		$bOpenMessenger = isset($_POST['OPEN_MESSENGER']) && intval($_POST['OPEN_MESSENGER']) == 1? true: false;
		$bOpenContactList = isset($_POST['OPEN_CONTACT_LIST']) && intval($_POST['OPEN_CONTACT_LIST']) == 1? true: false;

		// Online
		$arOnline = Array();
		if ($bOpenMessenger || $bOpenContactList)
		{
			$CIMContactList = new CIMContactList();
			$arOnline = $CIMContactList->GetStatus();
		}

		// Counters
		$arResult["COUNTERS"] = CUserCounter::GetValues($USER->GetID(), $_POST['SITE_ID']);

		// Exchange
		$arResult["MAIL_COUNTER"] = 0;
		if (CModule::IncludeModule("dav"))
		{
			$ar = CDavExchangeMail::GetTicker($GLOBALS["USER"]);
			if ($ar !== null)
				$arResult["MAIL_COUNTER"] = intval($ar["numberOfUnreadMessages"]);
		}

		$arSend = Array(
			'REVISION' => IM_REVISION,
			'USER_ID' => $USER->GetId(),
			'ONLINE' => !empty($arOnline)? $arOnline['users']: array(),
			'COUNTERS' => $arResult["COUNTERS"],
			'MAIL_COUNTER' => $arResult["MAIL_COUNTER"],
			'SERVER_TIME' => time(),
			'ERROR' => ""
		);

		if (CModule::IncludeModule('pull'))
		{
			$arChannel = CPullChannel::Get($USER->GetId());
			if (is_array($arChannel))
			{
				$arSend['PULL_CONFIG'] = Array(
					'CHANNEL_ID' => $arChannel['CHANNEL_ID'],
					'LAST_ID' => $arChannel['LAST_ID'],
					'PATH' => $arChannel['PATH'],
					'PATH_WS' => $arChannel['PATH_WS'],
					'METHOD' => $arChannel['METHOD'],
					'ERROR' => '',
				);
			}
		}

		$CIMMessage = new CIMMessage();
		$arMessage = $CIMMessage->GetUnreadMessage(Array(
			'USE_TIME_ZONE' => 'N',
			'ORDER' => 'ASC'
		));
		if ($arMessage['result'])
		{
			CIMMessage::GetFlashMessage($arMessage['unreadMessage']);
			$readMessageUserId = $_POST['TAB'];
			if ($bOpenMessenger && intval($readMessageUserId) > 0)
				CIMMessenger::SetCurrentTab($readMessageUserId);

			$arSend['MESSAGE'] = $arMessage['message'];
			$arSend['UNREAD_MESSAGE'] = CIMMessenger::CheckXmppStatusOnline()? array(): $arMessage['unreadMessage'];
			$arSend['USERS_MESSAGE'] = $arMessage['usersMessage'];
			$arSend['USERS'] = $arMessage['users'];
			$arSend['USER_IN_GROUP'] = $arMessage['userInGroup'];
			$arSend['WO_USER_IN_GROUP'] = $arMessage['woUserInGroup'];
			$arSend['ERROR'] = '';
		}

		$CIMChat = new CIMChat();
		$arMessage = $CIMChat->GetUnreadMessage(Array(
			'USE_TIME_ZONE' => 'N',
			'ORDER' => 'ASC'
		));
		if ($arMessage['result'])
		{
			CIMMessage::GetFlashMessage($arMessage['unreadMessage']);
			$readMessageUserId = $_POST['TAB'];
			if ($bOpenMessenger && intval($readMessageUserId) > 0)
				CIMMessenger::SetCurrentTab($readMessageUserId);

			foreach ($arMessage['message'] as $id => $ar)
			{
				$ar['recipientId'] = 'chat'.$ar['recipientId'];
				$arSend['MESSAGE'][$id] = $ar;
			}

			foreach ($arMessage['usersMessage'] as $chatId => $ar)
				$arSend['USERS_MESSAGE']['chat'.$chatId] = $ar;

			if (!CIMMessenger::CheckXmppStatusOnline())
			{
				foreach ($arMessage['unreadMessage'] as $chatId => $ar)
					$arSend['UNREAD_MESSAGE']['chat'.$chatId] = $ar;
			}

			foreach ($arMessage['users'] as $key => $value)
				$arSend['USERS'][$key] = $value;

			foreach ($arMessage['userInGroup'] as $key => $value)
				$arSend['USER_IN_GROUP'][$key] = $value;

			foreach ($arMessage['woUserInGroup'] as $key => $value)
				$arSend['WO_USER_IN_GROUP'][$key] = $value;

			$arSend['CHAT'] = $arMessage['chat'];
			$arSend['USER_IN_CHAT'] = $arMessage['userInChat'];

			$arSend['ERROR'] = '';
		}

		// Notify
		$CIMNotify = new CIMNotify();
		$arNotify = $CIMNotify->GetUnreadNotify(Array('USE_TIME_ZONE' => 'N'));
		if ($arNotify['result'])
		{
			$arSend['NOTIFY'] = $arNotify['notify'];
			$arSend['UNREAD_NOTIFY'] = $arNotify['unreadNotify'];
			$arSend['FLASH_NOTIFY'] = CIMNotify::GetFlashNotify($arNotify['unreadNotify']);
			$arSend['ERROR'] = '';
		}
		$arSend['XMPP_STATUS'] = CIMMessenger::CheckXmppStatusOnline()? 'Y':'N';
		$arSend['DESKTOP_STATUS'] = CIMMessenger::CheckDesktopStatusOnline()? 'Y':'N';

		echo CUtil::PhpToJsObject($arSend);
	}
	else if ($_POST['IM_UPDATE_STATE_LIGHT'] == 'Y')
	{
		$errorMessage = "";

		CIMContactList::SetOnline();

		$arResult["REVISION"] = IM_REVISION;
		$arResult['SERVER_TIME'] = time();
		if (isset($_POST['NOTIFY']))
		{
			$CIMNotify = new CIMNotify();
			$arNotify = $CIMNotify->GetUnreadNotify(Array('SPEED_CHECK' => 'N', 'USE_TIME_ZONE' => 'N'));

			$arResult['COUNTER_NOTIFICATIONS'] = count($arNotify['notify']);
			$arResult['NOTIFY_LAST_ID'] = $arNotify['maxNotify'];
		}
		if (isset($_POST['MESSAGE']))
		{
			$CIMMessage = new CIMMessage();
			$arMessage = $CIMMessage->GetUnreadMessage(Array(
				'SPEED_CHECK' => 'N',
				'LOAD_DEPARTMENT' => 'N',
				'ORDER' => 'ASC',
				'GROUP_BY_CHAT' => 'Y',
			));
			$arResult['COUNTER_MESSAGES'] = count($arMessage['message']);
			$arData = Array();
			$arUnread = Array();
			foreach ($arMessage['message'] as $data)
			{
				$arUnread[$data['senderId']]['MESSAGE'] = $data;
				$arUnread[$data['senderId']]['USER'] = $arMessage['users'][$data['senderId']];
			}
			foreach ($arUnread as $userId => $data)
			{
				$arData[$userId] = $data;
			}
			uasort($arData, create_function('$a, $b', 'if($a["MESSAGE"]["date"] < $b["MESSAGE"]["date"] ) return 1; elseif($a["MESSAGE"]["date"]  > $b["MESSAGE"]["date"] ) return -1; else return 0;'));
			$arResult['COUNTER_UNREAD_MESSAGES'] = $arData;
		}

		if (CModule::IncludeModule('pull'))
		{
			$arChannel = CPullChannel::Get($USER->GetId());
			if (is_array($arChannel))
			{
				$arResult['PULL_CONFIG'] = Array(
					'CHANNEL_ID' => $arChannel['CHANNEL_ID'],
					'LAST_ID' => $arChannel['LAST_ID'],
					'PATH' => $arChannel['PATH'],
					'PATH_WS' => $arChannel['PATH_WS'],
					'METHOD' => $arChannel['METHOD'],
					'ERROR' => '',
				);
			}
		}
		// Counters
		$arResult["COUNTERS"] = CUserCounter::GetValues($USER->GetID(), $_POST['SITE_ID']);

		$arResult["ERROR"] = $errorMessage;
		echo CUtil::PhpToJsObject($arResult);
	}
	else if ($_POST['IM_NOTIFY_LOAD'] == 'Y')
	{
		$CIMNotify = new CIMNotify();
		$arNotify = $CIMNotify->GetUnreadNotify(Array('SPEED_CHECK' => 'N', 'USE_TIME_ZONE' => 'N'));
		if ($arNotify['result'])
		{
			$arSend['NOTIFY'] = $arNotify['notify'];
			$arSend['UNREAD_NOTIFY'] = $arNotify['unreadNotify'];
			$arSend['FLASH_NOTIFY'] = CIMNotify::GetFlashNotify($arNotify['unreadNotify']);
			$arSend['ERROR'] = '';

			if ($arNotify['maxNotify'] > 0)
				$CIMNotify->MarkNotifyRead($arNotify['maxNotify'], true);
		}
		echo CUtil::PhpToJsObject($arSend);
	}
	else if ($_POST['IM_NOTIFY_HISTORY_LOAD_MORE'] == 'Y')
	{
		$errorMessage = "";

		$CIMNotify = new CIMNotify();
		$arNotify = $CIMNotify->GetNotifyList(Array('PAGE' => $_POST['PAGE'], 'USE_TIME_ZONE' => 'N'));

		echo CUtil::PhpToJsObject(Array(
			'NOTIFY' => $arNotify,
			'ERROR' => $errorMessage
		));
	}
	else if ($_POST['IM_SEND_MESSAGE'] == 'Y')
	{
		CUtil::decodeURIComponent($_POST);

		$tmpID = $_POST['ID'];

		if ($_POST['CHAT'] == 'Y')
		{
			$ar = Array(
				"FROM_USER_ID" => intval($USER->GetID()),
				"TO_CHAT_ID" => intval(substr($_POST['RECIPIENT_ID'], 4)),
				"MESSAGE" 	 => $_POST['MESSAGE'],
				"MESSAGE_TYPE" => IM_MESSAGE_GROUP
			);
		}
		else
		{
			$ar = Array(
				"FROM_USER_ID" => intval($USER->GetID()),
				"TO_USER_ID" => intval($_POST['RECIPIENT_ID']),
				"MESSAGE" 	 => $_POST['MESSAGE'],
			);
		}

		$errorMessage = "";
		if(!($insertID = CIMMessage::Add($ar)))
		{
			if ($e = $GLOBALS["APPLICATION"]->GetException())
				$errorMessage = $e->GetString();
			if (StrLen($errorMessage) == 0)
				$errorMessage = GetMessage('UNKNOWN_ERROR');
		}
		$CCTP = new CTextParser();
		$CCTP->MaxStringLen = 200;
		$CCTP->allow = array("HTML" => "N", "ANCHOR" => (isset($_POST['MOBILE'])?"N": "Y"), "BIU" => "N", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => "N", "NL2BR" => "Y", "VIDEO" => "N", "TABLE" => "N", "CUT_ANCHOR" => "N", "ALIGN" => "N");

		$userTzOffset = isset($_POST['USER_TZ_OFFSET'])? intval($_POST['USER_TZ_OFFSET']): CTimeZone::GetOffset();
		$arResult = Array(
			'TMP_ID' => $tmpID,
			'ID' => $insertID,
			'SEND_DATE' => time()+$userTzOffset,
			'SEND_MESSAGE' => $CCTP->convertText(htmlspecialcharsbx($ar['MESSAGE'])),
			'SENDER_ID' => intval($USER->GetID()),
			'RECIPIENT_ID' => $_POST['CHAT'] == 'Y'? htmlspecialcharsbx($_POST['RECIPIENT_ID']): intval($_POST['RECIPIENT_ID']),
			'ERROR' => $errorMessage
		);
		if (isset($_POST['MOBILE']))
		{
			$arFormat = Array(
				"today" => "today, ".GetMessage('IM_MESSAGE_FORMAT_TIME'),
				"" => GetMessage('IM_MESSAGE_FORMAT_DATE')
			);
			$arResult['SEND_DATE_FORMAT'] = FormatDate($arFormat, time()+$userTzOffset);
		}
		echo CUtil::PhpToJsObject($arResult);

		CIMContactList::SetOnline(null, true);
		CIMMessenger::SetCurrentTab(intval($_POST['TAB']));
	}
	else if ($_POST['IM_READ_MESSAGE'] == 'Y')
	{
		$errorMessage = "";

		if (substr($_POST['USER_ID'], 0, 4) == 'chat')
		{
			$CIMChat = new CIMChat();
			$CIMChat->SetReadMessage(intval(substr($_POST['USER_ID'],4)), (isset($_POST['LAST_ID']) && intval($_POST['LAST_ID'])>0 ? $_POST['LAST_ID']: null));
		}
		else
		{
			$CIMMessage = new CIMMessage();
			$CIMMessage->SetReadMessage($_POST['USER_ID'], (isset($_POST['LAST_ID']) && intval($_POST['LAST_ID'])>0 ? $_POST['LAST_ID']: null));
		}
		CIMMessenger::SetCurrentTab(intval($_POST['TAB']));

		CIMContactList::SetOnline(null, true);

		echo CUtil::PhpToJsObject(Array(
			'USER_ID' => htmlspecialcharsbx($_POST['USER_ID']),
			'ERROR' => $errorMessage
		));
	}
	else if ($_POST['IM_LOAD_LAST_MESSAGE'] == 'Y')
	{
		$arMessage = Array();
		if ($_POST['CHAT'] == 'Y')
		{
			$chatId = intval(substr($_POST['USER_ID'], 4));

			$CIMChat = new CIMChat();
			$arMessage = $CIMChat->GetLastMessage($chatId, false, ($_POST['USER_LOAD'] == 'Y'? true: false), false);
			if (isset($arMessage['message']))
			{
				foreach ($arMessage['message'] as $id => $ar)
					$arMessage['message'][$id]['recipientId'] = 'chat'.$ar['recipientId'];

				$arMessage['usersMessage']['chat'.$chatId] = $arMessage['usersMessage'][$chatId];
				unset($arMessage['usersMessage'][$chatId]);
				if (isset($_POST['READ']) && $_POST['READ'] == 'Y')
					$CIMChat->SetReadMessage($chatId);
			}
		}
		else
		{
			if (CIMContactList::AllowToSend(Array('TO_USER_ID' => $_POST['USER_ID'])))
			{
				$CIMMessage = new CIMMessage();
				$arMessage = $CIMMessage->GetLastMessage(intval($_POST['USER_ID']), false, ($_POST['USER_LOAD'] == 'Y'? true: false), false);
				if (isset($_POST['READ']) && $_POST['READ'] == 'Y')
					$CIMMessage->SetReadMessage(intval($_POST['TAB']));
			}
		}
		echo CUtil::PhpToJsObject(Array(
			'USER_ID' => $_POST['CHAT'] == 'Y'? htmlspecialcharsbx($_POST['USER_ID']): intval($_POST['USER_ID']),
			'MESSAGE' => isset($arMessage['message'])? $arMessage['message']: Array(),
			'USERS_MESSAGE' => isset($arMessage['usersMessage'])? $arMessage['usersMessage']: Array(),
			'USERS' => isset($arMessage['users'])? $arMessage['users']: Array(),
			'USER_IN_GROUP' => isset($arMessage['userInGroup'])? $arMessage['userInGroup']: Array(),
			'WO_USER_IN_GROUP' => isset($arMessage['woUserInGroup'])? $arMessage['woUserInGroup']: Array(),
			'CHAT' => isset($arMessage['chat'])? $arMessage['chat']: Array(),
			'USER_IN_CHAT' => isset($arMessage['userInChat'])? $arMessage['userInChat']: Array(),
			'USER_LOAD' => $_POST['USER_LOAD'] == 'Y'? 'Y': 'N',
			'ERROR' => ''
		));
	}
	else if ($_POST['IM_HISTORY_LOAD'] == 'Y')
	{
		$arMessage = Array();
		$chatId = 0;
		if (substr($_POST['USER_ID'], 0, 4) == 'chat')
		{
			$chatId = intval(substr($_POST['USER_ID'], 4));

			$CIMChat = new CIMChat();
			$arMessage = $CIMChat->GetLastMessage($chatId, false, ($_POST['USER_LOAD'] == 'Y'? true: false), false);
			if (isset($arMessage['message']))
			{
				foreach ($arMessage['message'] as $id => $ar)
					$arMessage['message'][$id]['recipientId'] = 'chat'.$ar['recipientId'];

				$arMessage['usersMessage']['chat'.$chatId] = $arMessage['usersMessage'][$chatId];
				unset($arMessage['usersMessage'][$chatId]);
			}

		}
		else
		{
			if (CIMContactList::AllowToSend(Array('TO_USER_ID' => $_POST['USER_ID'])))
			{
				$CIMMessage = new CIMMessage();
				$arMessage = $CIMMessage->GetLastMessage(intval($_POST['USER_ID']), false, ($_POST['USER_LOAD'] == 'Y'? true: false), false);
			}
		}
		echo CUtil::PhpToJsObject(Array(
			'USER_ID' => $chatId>0? htmlspecialcharsbx($_POST['USER_ID']): intval($_POST['USER_ID']),
			'MESSAGE' => isset($arMessage['message'])? $arMessage['message']: Array(),
			'USERS_MESSAGE' => isset($arMessage['message'])? $arMessage['usersMessage']: Array(),
			'USERS' => isset($arMessage['users'])? $arMessage['users']: Array(),
			'USER_IN_GROUP' => isset($arMessage['userInGroup'])? $arMessage['userInGroup']: Array(),
			'WO_USER_IN_GROUP' => isset($arMessage['woUserInGroup'])? $arMessage['woUserInGroup']: Array(),
			'CHAT' => isset($arMessage['chat'])? $arMessage['chat']: Array(),
			'USER_IN_CHAT' => isset($arMessage['userInChat'])? $arMessage['userInChat']: Array(),
			'ERROR' => ''
		));
	}
	else if ($_POST['IM_HISTORY_LOAD_MORE'] == 'Y')
	{
		$arMessage = Array();
		if (CIMContactList::AllowToSend(Array('TO_USER_ID' => $_POST['USER_ID'])))
		{
			$CIMHistory = new CIMHistory(false, Array(
				'hide_link' => (isset($_POST['MOBILE'])?true: false)
			));
			if (substr($_POST['USER_ID'], 0, 4) == 'chat')
			{
				$chatId = substr($_POST['USER_ID'],4);
				$arMessage = $CIMHistory->GetMoreChatMessage(intval($_POST['PAGE_ID']), $chatId, false);
				if (!empty($arMessage['message']))
				{
					foreach ($arMessage['message'] as $id => $ar)
						$arMessage['message'][$id]['recipientId'] = 'chat'.$ar['recipientId'];

					$arMessage['usersMessage']['chat'.$chatId] = $arMessage['usersMessage'][$chatId];
					unset($arMessage['usersMessage'][$chatId]);
				}
			}
			else
				$arMessage = $CIMHistory->GetMoreMessage(intval($_POST['PAGE_ID']), intval($_POST['USER_ID']), false, false);
		}

		echo CUtil::PhpToJsObject(Array(
			'MESSAGE' => $arMessage['message'],
			'USERS_MESSAGE' => $arMessage['usersMessage'],
			'ERROR' => ''
		));
	}
	else if ($_POST['IM_HISTORY_REMOVE_ALL'] == 'Y')
	{
		$errorMessage = "";

		$CIMHistory = new CIMHistory();
		if (substr($_POST['USER_ID'], 0, 4) == 'chat')
			$CIMHistory->HideAllChatMessage(substr($_POST['USER_ID'],4));
		else
			$CIMHistory->RemoveAllMessage($_POST['USER_ID']);

		echo CUtil::PhpToJsObject(Array(
			'USER_ID' => htmlspecialcharsbx($_POST['USER_ID']),
			'ERROR' => $errorMessage
		));
	}
	else if ($_POST['IM_HISTORY_REMOVE_MESSAGE'] == 'Y')
	{
		$errorMessage = "";

		$CIMHistory = new CIMHistory();
		$CIMHistory->RemoveMessage($_POST['MESSAGE_ID']);

		echo CUtil::PhpToJsObject(Array(
			'MESSAGE_ID' => intval($_POST['MESSAGE_ID']),
			'ERROR' => $errorMessage
		));
	}
	else if ($_POST['IM_HISTORY_SEARCH'] == 'Y')
	{
		CUtil::decodeURIComponent($_POST);

		$CIMHistory = new CIMHistory();
		if (substr($_POST['USER_ID'], 0, 4) == 'chat')
		{
			$chatId = substr($_POST['USER_ID'],4);
			$arMessage = $CIMHistory->SearchChatMessage($_POST['SEARCH'], $chatId, false);
			if (!empty($arMessage['message']))
			{
				foreach ($arMessage['message'] as $id => $ar)
					$arMessage['message'][$id]['recipientId'] = 'chat'.$ar['recipientId'];

				$arMessage['usersMessage']['chat'.$chatId] = $arMessage['usersMessage'][$chatId];
				unset($arMessage['usersMessage'][$chatId]);
			}
		}
		else
			$arMessage = $CIMHistory->SearchMessage($_POST['SEARCH'], intval($_POST['USER_ID']), false, false);

		echo CUtil::PhpToJsObject(Array(
			'MESSAGE' => $arMessage['message'],
			'USERS_MESSAGE' => $arMessage['usersMessage'],
			'USER_ID' => htmlspecialcharsbx($_POST['USER_ID']),
			'ERROR' => ''
		));
	}
	else if ($_POST['IM_CONTACT_LIST'] == 'Y')
	{
		$CIMContactList = new CIMContactList();
		$arContactList = $CIMContactList->GetList();

		echo CUtil::PhpToJsObject(Array(
			'USER_ID' => $USER->GetId(),
			'USERS' => $arContactList['users'],
			'GROUPS' => $arContactList['groups'],
			'USER_IN_GROUP' => $arContactList['userInGroup'],
			'WO_GROUPS' => $arContactList['woGroups'],
			'WO_USER_IN_GROUP' => $arContactList['woUserInGroup'],
			'ERROR' => ''
		));
	}
	else if ($_POST['IM_RECENT_LIST'] == 'Y')
	{
		$ar = CIMContactList::GetRecentList(Array(
			'USE_TIME_ZONE' => 'N'
		));
		$arRecent = Array();
		$arUsers = Array();
		$arChat = Array();
		foreach ($ar as $userId => $value)
		{
			$value['MESSAGE']['text_mobile'] = $value['MESSAGE']['text'];
			if ($value['TYPE'] == IM_MESSAGE_GROUP)
			{
				$arChat[$value['CHAT']['id']] = $value['CHAT'];
				$value['MESSAGE']['userId'] = $userId;
				$value['MESSAGE']['recipientId'] = $userId;
			}
			else
			{
				$value['MESSAGE']['userId'] = $userId;
				$value['MESSAGE']['recipientId'] = $userId;
				$arUsers[$value['USER']['id']] = $value['USER'];
			}
			$arRecent[] = $value['MESSAGE'];
		}
		echo CUtil::PhpToJsObject(Array(
			'USER_ID' => $USER->GetId(),
			'RECENT' => $arRecent,
			'USERS' => $arUsers,
			'CHAT' => $arChat,
			'ERROR' => ''
		));

	}
	else if ($_POST['IM_NOTIFY_VIEWED'] == 'Y')
	{
		$errorMessage = "";

		$CIMNotify = new CIMNotify();
		$CIMNotify->MarkNotifyRead($_POST['MAX_ID'], true);

		echo CUtil::PhpToJsObject(Array(
			'ERROR' => $errorMessage
		));
	}
	else if ($_POST['IM_NOTIFY_VIEW'] == 'Y')
	{
		$errorMessage = "";

		$CIMNotify = new CIMNotify();
		$CIMNotify->MarkNotifyRead($_POST['ID']);

		echo CUtil::PhpToJsObject(Array(
			'ERROR' => $errorMessage
		));
	}
	else if ($_POST['IM_NOTIFY_CONFIRM'] == 'Y')
	{
		$errorMessage = "";

		$CIMNotify = new CIMNotify();
		$CIMNotify->Confirm($_POST['NOTIFY_ID'], $_POST['NOTIFY_VALUE']);

		echo CUtil::PhpToJsObject(Array(
			'NOTIFY_ID' => intval($_POST['NOTIFY_ID']),
			'NOTIFY_VALUE' => $_POST['NOTIFY_VALUE'],
			'ERROR' => $errorMessage
		));
	}
	else if ($_POST['IM_NOTIFY_REMOVE'] == 'Y')
	{
		$errorMessage = "";

		$CIMNotify = new CIMNotify();
		$CIMNotify->DeleteWithCheck($_POST['NOTIFY_ID']);

		echo CUtil::PhpToJsObject(Array(
			'NOTIFY_ID' => intval($_POST['NOTIFY_ID']),
			'ERROR' => $errorMessage
		));
	}
	else if ($_POST['IM_NOTIFY_GROUP_REMOVE'] == 'Y')
	{
		$errorMessage = "";

		$CIMNotify = new CIMNotify();
		if ($arNotify = $CIMNotify->GetNotify($_POST['NOTIFY_ID']))
			CIMNotify::DeleteByTag($arNotify['NOTIFY_TAG']);

		echo CUtil::PhpToJsObject(Array(
			'NOTIFY_ID' => intval($_POST['NOTIFY_ID']),
			'ERROR' => $errorMessage
		));
	}
	else if ($_POST['IM_RECENT_HIDE'] == 'Y')
	{
		if (substr($_POST['USER_ID'], 0, 4) == 'chat')
			CIMContactList::DeleteRecent(substr($_POST['USER_ID'], 4), true);
		else
			CIMContactList::DeleteRecent($_POST['USER_ID']);

		echo CUtil::PhpToJsObject(Array(
			'USER_ID' => htmlspecialcharsbx($_POST['USER_ID']),
			'ERROR' => ''
		));
	}
	else if ($_POST['IM_CHAT_ADD'] == 'Y')
	{
		$_POST['USERS'] = CUtil::JsObjectToPhp($_POST['USERS']);

		$errorMessage = "";
		$CIMChat = new CIMChat();
		$chatId = $CIMChat->Add('', $_POST['USERS']);
		if (!$chatId)
		{
			if ($e = $GLOBALS["APPLICATION"]->GetException())
				$errorMessage = $e->GetString();
		}
		echo CUtil::PhpToJsObject(Array(
			'CHAT_ID' => intval($chatId),
			'ERROR' => $errorMessage
		));
	}
	else if ($_POST['IM_CHAT_EXTEND'] == 'Y')
	{
		$_POST['USERS'] = CUtil::JsObjectToPhp($_POST['USERS']);

		$errorMessage = "";
		$CIMChat = new CIMChat();
		$result = $CIMChat->AddUser($_POST['CHAT_ID'], $_POST['USERS']);
		if (!$result)
		{
			if ($e = $GLOBALS["APPLICATION"]->GetException())
				$errorMessage = $e->GetString();
		}
		echo CUtil::PhpToJsObject(Array(
			'ERROR' => $errorMessage
		));
	}
	else if ($_POST['IM_CHAT_LEAVE'] == 'Y')
	{
		$CIMChat = new CIMChat();
		$result = $CIMChat->DeleteUser($_POST['CHAT_ID'], intval($_POST['USER_ID']) > 0? intval($_POST['USER_ID']): $USER->GetID());
		echo CUtil::PhpToJsObject(Array(
			'CHAT_ID' => intval($_POST['CHAT_ID']),
			'USER_ID' => intval($_POST['USER_ID']),
			'ERROR' => $result? '': 'AUTHORIZE_ERROR'
		));
	}
	else if ($_POST['IM_CHAT_RENAME'] == 'Y')
	{
		CUtil::decodeURIComponent($_POST);

		$CIMChat = new CIMChat();
		$CIMChat->Rename($_POST['CHAT_ID'], $_POST['CHAT_TITLE']);

		echo CUtil::PhpToJsObject(Array(
			'CHAT_ID' => intval($_POST['CHAT_ID']),
			'CHAT_TITLE' => $_POST['CHAT_TITLE'],
			'ERROR' => ''
		));
	}

	else if ($_POST['IM_CALL'] == 'Y')
	{
		$errorMessage = "";

		if ($_POST['COMMAND'] == 'invite')
			CIMMessenger::CallCommand($_POST['RECIPIENT_ID'], $_POST['COMMAND'], Array('video' => ($_POST['VIDEO'] == 'Y')));
		else if ($_POST['COMMAND'] == 'signaling')
			CIMMessenger::CallCommand($_POST['RECIPIENT_ID'], $_POST['COMMAND'], Array('peer' => $_POST['PEER']));
		else
			CIMMessenger::CallCommand($_POST['RECIPIENT_ID'], $_POST['COMMAND']);

		echo CUtil::PhpToJsObject(Array(
			'ERROR' => $errorMessage
		));
	}


	else if ($_POST['IM_START_WRITING'] == 'Y')
	{
		$errorMessage = "";
		CIMMessenger::StartWriting($_POST['RECIPIENT_ID']);

		echo CUtil::PhpToJsObject(Array(
			'ERROR' => $errorMessage
		));
	}
	else if ($_POST['IM_DESKTOP_LOGOUT'] == 'Y')
	{
		$errorMessage = "";

		CIMMessenger::RemoveDesktopStatusOnline();
		CIMContactList::SetOffline();

		echo CUtil::PhpToJsObject(Array(
			'ERROR' => $errorMessage
		));
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