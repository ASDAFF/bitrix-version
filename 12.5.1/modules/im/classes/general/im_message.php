<?
IncludeModuleLangFile(__FILE__);

class CIMMessage
{
	private $user_id = 0;
	private $bHideLink = false;

	function __construct($user_id = false, $arParams = Array())
	{
		global $USER;
		$this->user_id = intval($user_id);
		if ($this->user_id == 0)
			$this->user_id = IntVal($USER->GetID());
		if (isset($arParams['hide_link']) && $arParams['hide_link'] == true)
			$this->bHideLink = true;
	}

	public static function Add($arFields)
	{
		if (isset($arFields['MESSAGE_TYPE']) && $arFields['MESSAGE_TYPE'] == IM_MESSAGE_GROUP)
			$arFields['MESSAGE_TYPE'] = IM_MESSAGE_GROUP;
		else
			$arFields['MESSAGE_TYPE'] = IM_MESSAGE_PRIVATE;

		if (isset($arFields['MESSAGE_MODULE']))
			$arFields['NOTIFY_MODULE'] = $arFields['MESSAGE_MODULE'];
		else
			$arFields['NOTIFY_MODULE'] = "im";

		return CIMMessenger::Add($arFields);
	}

	public function GetMessage($ID)
	{
		global $DB;

		$ID = intval($ID);

		$strSql = "SELECT M.* FROM b_im_relation R, b_im_message M WHERE M.ID = ".$ID." AND R.USER_ID = ".$this->user_id." AND R.MESSAGE_TYPE = '".IM_MESSAGE_PRIVATE."' AND R.CHAT_ID = M.CHAT_ID";
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
			return $arRes;

		return false;
	}

	public function GetUnreadMessage($arParams = Array())
	{
		global $DB;

		$bSpeedCheck = isset($arParams['SPEED_CHECK']) && $arParams['SPEED_CHECK'] == 'N'? false: true;
		$lastId = !isset($arParams['LAST_ID']) || $arParams['LAST_ID'] == null? null: IntVal($arParams['LAST_ID']);
		$order = isset($arParams['ORDER']) && $arParams['ORDER'] == 'ASC'? 'ASC': 'DESC';
		$loadDepartment = isset($arParams['LOAD_DEPARTMENT']) && $arParams['LOAD_DEPARTMENT'] == 'N'? false: true;
		$bTimeZone = isset($arParams['USE_TIME_ZONE']) && $arParams['USE_TIME_ZONE'] == 'N'? false: true;
		$bGroupByChat = isset($arParams['GROUP_BY_CHAT']) && $arParams['GROUP_BY_CHAT'] == 'Y'? true: false;
		$bUserLoad = isset($arParams['USER_LOAD']) && $arParams['USER_LOAD'] == 'N'? false: true;
		$arExistUserData = isset($arParams['EXIST_USER_DATA']) && is_array($arParams['EXIST_USER_DATA'])? $arParams['EXIST_USER_DATA']: Array();

		$arMessages = Array();
		$arUnreadMessage = Array();
		$arUsersMessage = Array();

		$arResult = Array(
			'message' => Array(),
			'unreadMessage' => Array(),
			'usersMessage' => Array(),
			'users' => Array(),
			'userInGroup' => Array(),
			'woUserInGroup' => Array(),
			'countMessage' => 0,
			'result' => false
		);
		$bLoadMessage = $bSpeedCheck? CIMMessenger::SpeedFileExists($this->user_id, IM_SPEED_MESSAGE): false;
		$count = CIMMessenger::SpeedFileGet($this->user_id, IM_SPEED_MESSAGE);
		if (!$bLoadMessage || ($bLoadMessage && $count > 0))
		{
			$ssqlLastId = "R1.LAST_ID";
			$ssqlStatus = " AND R1.STATUS < ".IM_STATUS_READ;
			if (!is_null($lastId) && intval($lastId) > 0 && !CIMMessenger::CheckXmppStatusOnline())
			{
				$ssqlLastId = intval($lastId);
				$ssqlStatus = "";
			}

			if (!$bTimeZone)
				CTimeZone::Disable();
			$strSql ="
				SELECT
					M.ID,
					M.CHAT_ID,
					M.MESSAGE,
					".$DB->DateToCharFunction('M.DATE_CREATE')." DATE_CREATE,
					M.AUTHOR_ID,
					R1.USER_ID R1_USER_ID,
					R1.STATUS R1_STATUS,
					R2.USER_ID R2_USER_ID
				FROM b_im_relation R1
				INNER JOIN b_im_relation R2 on R2.CHAT_ID = R1.CHAT_ID AND R2.USER_ID != R1.USER_ID
				INNER JOIN b_im_message M ON M.ID > ".$ssqlLastId." AND M.CHAT_ID = R2.CHAT_ID AND IMPORT_ID IS NULL
				WHERE R1.USER_ID = ".$this->user_id." AND R1.USER_ID != M.AUTHOR_ID AND R1.MESSAGE_TYPE = '".IM_MESSAGE_PRIVATE."' ".$ssqlStatus."
				ORDER BY ID ".($order == "DESC"? "DESC": "ASC")."
			";
			if (!$bTimeZone)
				CTimeZone::Enable();
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			$arLastMessage = Array();
			$arMark = Array();
			$CCTP = new CTextParser();
			$CCTP->MaxStringLen = 200;
			$CCTP->allow = array("HTML" => "N", "ANCHOR" => $this->bHideLink? "N": "Y", "BIU" => "N", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => "N", "NL2BR" => "Y", "VIDEO" => "N", "TABLE" => "N", "CUT_ANCHOR" => "N", "ALIGN" => "N");
			if (!$this->bHideLink)
			{
				$CCTPM = new CTextParser();
				$CCTPM->MaxStringLen = 200;
				$CCTPM->allow = array("HTML" => "N", "ANCHOR" => "N", "BIU" => "N", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => "N", "NL2BR" => "Y", "VIDEO" => "N", "TABLE" => "N", "CUT_ANCHOR" => "N", "ALIGN" => "N");
			}

			while ($arRes = $dbRes->Fetch())
			{
				$arUsers[] = $arRes['R1_USER_ID'];
				$arUsers[] = $arRes['R2_USER_ID'];
				if ($this->user_id == $arRes['AUTHOR_ID'])
				{
					$arRes['TO_USER_ID'] = $arRes['R2_USER_ID'];
					$arRes['FROM_USER_ID'] = $arRes['R1_USER_ID'];
					$convId = $arRes['TO_USER_ID'];
				}
				else
				{
					$arRes['TO_USER_ID'] = $arRes['R1_USER_ID'];
					$arRes['FROM_USER_ID'] = $arRes['R2_USER_ID'];
					$convId = $arRes['FROM_USER_ID'];
				}

				$arMessages[$arRes['ID']] = Array(
					'id' => $arRes['ID'],
					'senderId' => $arRes['FROM_USER_ID'],
					'recipientId' => $arRes['TO_USER_ID'],
					'date' => MakeTimeStamp($arRes['DATE_CREATE']),
					'text' => $arRes['MESSAGE'],
				);
				if ($bGroupByChat)
				{
					$arMessages[$arRes['ID']]['conversation'] = $convId;
					$arMessages[$arRes['ID']]['unread'] = $this->user_id != $arRes['AUTHOR_ID']? 'Y': 'N';
				}
				else
				{
					$arUsersMessage[$convId][] = $arRes['ID'];
					if ($this->user_id != $arRes['AUTHOR_ID'])
						$arUnreadMessage[$convId][] = $arRes['ID'];
				}

				if ($arRes['R1_STATUS'] == IM_STATUS_UNREAD && (!isset($arMark[$arRes["CHAT_ID"]]) || $arMark[$arRes["CHAT_ID"]] < $arRes["ID"]))
					$arMark[$arRes["CHAT_ID"]] = $arRes["ID"];

				if (!isset($arLastMessage[$convId]) || $arLastMessage[$convId] < $arRes["ID"])
					$arLastMessage[$convId] = $arRes["ID"];
			}
			if ($bGroupByChat)
			{
				foreach ($arMessages as $key => $value)
				{
					$arMessages[$arLastMessage[$value['conversation']]]['counter']++;
					if ($arLastMessage[$value['conversation']] != $value['id'])
					{
						unset($arMessages[$key]);
					}
					else
					{
						$arMessages[$key]['text'] = $CCTP->convertText(htmlspecialcharsbx($value['text']));
						if ($this->bHideLink)
							$arMessages[$key]['text_mobile'] = $arMessages[$key]['text'];
						else
							$arMessages[$key]['text_mobile'] = $CCTPM->convertText(htmlspecialcharsbx($value['text']));

						$arUsersMessage[$value['conversation']][] = $value['id'];

						if ($value['unread'] == 'Y')
							$arUnreadMessage[$value['conversation']][] = $value['id'];

						unset($arMessages[$key]['conversation']);
						unset($arMessages[$key]['unread']);
					}
				}
			}
			else
			{
				foreach ($arMessages as $key => $value)
				{
					$arMessages[$key]['text'] = $CCTP->convertText(htmlspecialcharsbx($value['text']));
					if ($this->bHideLink)
						$arMessages[$key]['text_mobile'] = $arMessages[$key]['text'];
					else
						$arMessages[$key]['text_mobile'] = $CCTPM->convertText(htmlspecialcharsbx($value['text']));
				}
			}
			foreach ($arMark as $chatId => $lastSendId)
				self::SetLastSendId($chatId, $this->user_id, $lastSendId);

			$arResult['message'] = $arMessages;
			$arResult['unreadMessage'] = $arUnreadMessage;
			$arResult['usersMessage'] = $arUsersMessage;

			if ($bUserLoad && !empty($arUsers))
			{
				$arUserData = CIMContactList::GetUserData(Array('ID' => array_diff(array_unique($arUsers), $arExistUserData), 'DEPARTMENT' => ($loadDepartment? 'Y': 'N')));
				$arResult['users'] = $arUserData['users'];
				$arResult['userInGroup'] = $arUserData['userInGroup'];
				$arResult['woUserInGroup'] = $arUserData['woUserInGroup'];
			}
			else
			{
				$arResult['users'] = Array();
				$arResult['userInGroup'] = Array();
				$arResult['userInGroup'] = Array();
			}

			$arResult['countMessage'] = CIMMessenger::GetMessageCounter($this->user_id, $arResult);
			if (!$bGroupByChat)
				CIMMessenger::SpeedFileCreate($this->user_id, $arResult['countMessage'], IM_SPEED_MESSAGE);
			$arResult['result'] = true;
		}
		else
		{
			$arResult['countMessage'] = CIMMessenger::GetMessageCounter($this->user_id, $arResult);
		}

		return $arResult;
	}

	function GetLastMessage($toUserId, $fromUserId = false, $loadUserData = false, $bTimeZone = true)
	{
		global $DB;

		$fromUserId = IntVal($fromUserId);
		if ($fromUserId <= 0)
			$fromUserId = $this->user_id;

		$toUserId = IntVal($toUserId);
		if ($toUserId <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_EMPTY_USER_ID"), "ERROR_TO_USER_ID");
			return false;
		}

		$chatId = 0;
		$startId = 0;
		$arMessages = Array();
		$arUsersMessage = Array();

		$strSql ="
			SELECT R1.CHAT_ID, R1.START_ID
			FROM b_im_relation R1
			INNER JOIN b_im_relation R2 on R2.CHAT_ID = R1.CHAT_ID
			WHERE
				R1.USER_ID = ".$fromUserId."
				AND R1.MESSAGE_TYPE = '".IM_MESSAGE_PRIVATE."'
				AND R2.USER_ID = ".$toUserId."
		";
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
		{
			$chatId = intval($arRes['CHAT_ID']);
			$startId = intval($arRes['START_ID']);
		}

		if ($chatId > 0)
		{
			if (!$bTimeZone)
				CTimeZone::Disable();
			$strSql ="
				SELECT
					M.ID,
					M.CHAT_ID,
					M.MESSAGE,
					".$DB->DateToCharFunction('M.DATE_CREATE')." DATE_CREATE,
					M.AUTHOR_ID
				FROM b_im_message M
				WHERE M.CHAT_ID = ".$chatId."
				ORDER BY ID DESC
			";
			if (!$bTimeZone)
				CTimeZone::Enable();

			$strSql = $DB->TopSql($strSql, 20);
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			$CCTP = new CTextParser();
			$CCTP->MaxStringLen = 200;
			$CCTP->allow = array("HTML" => "N", "ANCHOR" => $this->bHideLink? "N": "Y", "BIU" => "N", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => "N", "NL2BR" => "Y", "VIDEO" => "N", "TABLE" => "N", "CUT_ANCHOR" => "N", "ALIGN" => "N");
			while ($arRes = $dbRes->Fetch())
			{
				if ($arRes['ID'] < $startId)
					continue;

				if ($fromUserId == $arRes['AUTHOR_ID'])
				{
					$arRes['TO_USER_ID'] = $toUserId;
					$arRes['FROM_USER_ID'] = $fromUserId;
					$convId = $arRes['TO_USER_ID'];
				}
				else
				{
					$arRes['TO_USER_ID'] = $fromUserId;
					$arRes['FROM_USER_ID'] = $toUserId;
					$convId = $arRes['FROM_USER_ID'];
				}

				$arMessages[$arRes['ID']] = Array(
					'id' => $arRes['ID'],
					'senderId' => $arRes['FROM_USER_ID'],
					'recipientId' => $arRes['TO_USER_ID'],
					'date' => MakeTimeStamp($arRes['DATE_CREATE']),
					'text' => $CCTP->convertText(htmlspecialcharsbx($arRes['MESSAGE']))
				);

				$arUsersMessage[$convId][] = $arRes['ID'];
			}
		}

		$arResult = Array('message' => $arMessages, 'usersMessage' => $arUsersMessage, 'users' => Array(), 'userInGroup' => Array(), 'woUserInGroup' => Array());
		if (is_array($loadUserData) || is_bool($loadUserData) && $loadUserData == true)
		{
			$bDepartment = true;
			if (is_array($loadUserData) && $loadUserData['DEPARTMENT'] == 'N')
				$bDepartment = false;

			$ar = CIMContactList::GetUserData(array(
					'ID' => Array($fromUserId, $toUserId),
					'DEPARTMENT' => ($bDepartment? 'Y': 'N'),
					'USE_CACHE' => 'N'
				)
			);
			$arResult['users'] = $ar['users'];
			$arResult['userInGroup']  = $ar['userInGroup'];
			$arResult['woUserInGroup']  = $ar['woUserInGroup'];
		}

		return $arResult;
	}

	function GetLastSendMessage($arParams)
	{
		global $DB;

		if (!isset($arParams['TO_USER_ID']))
			return false;

		$toUserId = $arParams['TO_USER_ID'];
		$fromUserId = isset($arParams['FROM_USER_ID']) && IntVal($arParams['FROM_USER_ID'])>0? IntVal($arParams['FROM_USER_ID']): $this->user_id;
		$limit = isset($arParams['LIMIT']) && IntVal($arParams['LIMIT'])>0? IntVal($arParams['LIMIT']): false;
		$order = isset($arParams['ORDER']) && $arParams['ORDER'] == 'ASC'? 'ASC': 'DESC';
		$bTimeZone = isset($arParams['USE_TIME_ZONE']) && $arParams['USE_TIME_ZONE'] == 'N'? false: true;

		$arToUserId = Array();
		if (is_array($toUserId))
		{
			foreach ($toUserId as $userId)
				$arToUserId[] = intval($userId);
		}
		else
		{
			$arToUserId[] = intval($toUserId);
		}
		if (empty($arToUserId))
			return Array();

		$sqlLimit = '';
		if ($limit)
		{
			$dbType = strtolower($DB->type);
			if ($dbType== "mysql")
				$sqlLimit = " AND M.DATE_CREATE > DATE_SUB(NOW(), INTERVAL ".$limit." DAY)";
			else if ($dbType == "mssql")
				$sqlLimit = " AND M.DATE_CREATE > dateadd(day, -".$limit.", getdate())";
			else if ($dbType == "oracle")
				$sqlLimit = " AND M.DATE_CREATE > SYSDATE-".$limit;
		}

		if (!$bTimeZone)
			CTimeZone::Disable();
		$strSql = "
			SELECT
				M.ID,
				M.CHAT_ID,
				M.MESSAGE,
				".$DB->DateToCharFunction('M.DATE_CREATE')." DATE_CREATE,
				M.AUTHOR_ID,
				R1.USER_ID R1_USER_ID,
				R2.USER_ID R2_USER_ID
			FROM b_im_relation R1
			INNER JOIN b_im_relation R2 on R2.CHAT_ID = R1.CHAT_ID
			INNER JOIN b_im_message M ON M.ID >= R1.START_ID
							AND M.ID >= R1.LAST_ID
							AND M.ID >= R2.LAST_ID
							AND M.CHAT_ID = R1.CHAT_ID
							".$sqlLimit."
			WHERE
				R1.USER_ID = ".$fromUserId."
			AND R2.USER_ID IN (".implode(",",$arToUserId).")
			AND R1.MESSAGE_TYPE = '".IM_MESSAGE_PRIVATE."'
			".($order == 'DESC'? "ORDER BY M.DATE_CREATE DESC": "");
		if (!$bTimeZone)
			CTimeZone::Enable();

		$arMessages = Array();
		$CCTP = new CTextParser();
		$CCTP->MaxStringLen = 200;
		$CCTP->allow = array("HTML" => "N", "ANCHOR" => $this->bHideLink? "N": "Y", "BIU" => "N", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => "N", "NL2BR" => "Y", "VIDEO" => "N", "TABLE" => "N", "CUT_ANCHOR" => "N", "ALIGN" => "N");
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while ($arRes = $dbRes->Fetch())
		{
			if ($fromUserId == $arRes['AUTHOR_ID'])
			{
				$arRes['TO_USER_ID'] = $arRes['R2_USER_ID'];
				$arRes['FROM_USER_ID'] = $arRes['R1_USER_ID'];
				$convId = $arRes['TO_USER_ID'];
			}
			else
			{
				$arRes['TO_USER_ID'] = $arRes['R1_USER_ID'];
				$arRes['FROM_USER_ID'] = $arRes['R2_USER_ID'];
				$convId = $arRes['FROM_USER_ID'];
			}

			$date = MakeTimeStamp($arRes['DATE_CREATE']);
			if (!isset($arMessages[$convId])
			|| (isset($arMessages[$convId]) && $arMessages[$convId]['date'] < $date))
			{

				$arMessages[$convId] = Array(
					'id' => $arRes['ID'],
					'senderId' => $arRes['FROM_USER_ID'],
					'recipientId' => $arRes['TO_USER_ID'],
					'date' => $date,
					'text' => $arRes['MESSAGE']
				);
			}
		}
		foreach ($arMessages as $key => $value)
		{
			$value['text'] = $CCTP->convertText(htmlspecialcharsbx($value['text']));
			$arMessages[$key] = $value;
		}
		return $arMessages;
	}

	public static function GetUnsendMessage()
	{
		global $DB;

		$strSql ="
			SELECT
				M.ID,
				M.CHAT_ID,
				M.MESSAGE,
				M.MESSAGE_OUT,
				".$DB->DateToCharFunction('M.DATE_CREATE')." DATE_CREATE,
				M.EMAIL_TEMPLATE,
				R.LAST_SEND_ID,
				R.USER_ID TO_USER_ID,
				U1.LOGIN TO_USER_LOGIN,
				U1.NAME TO_USER_NAME,
				U1.LAST_NAME TO_USER_LAST_NAME,
				U1.EMAIL TO_USER_EMAIL,
				U1.LID TO_USER_LID,
				M.AUTHOR_ID FROM_USER_ID,
				U2.LOGIN FROM_USER_LOGIN,
				U2.NAME FROM_USER_NAME,
				U2.LAST_NAME FROM_USER_LAST_NAME
			FROM b_im_relation R
			INNER JOIN b_im_message M ON M.ID > R.LAST_ID AND M.ID > R.LAST_SEND_ID AND M.CHAT_ID = R.CHAT_ID AND IMPORT_ID IS NULL AND R.USER_ID != M.AUTHOR_ID
			LEFT JOIN b_user U1 ON U1.ID = R.USER_ID
			LEFT JOIN b_user U2 ON U2.ID = M.AUTHOR_ID
			WHERE R.MESSAGE_TYPE = '".IM_MESSAGE_PRIVATE."' AND R.STATUS < ".IM_STATUS_NOTIFY."
			ORDER BY DATE_CREATE DESC, ID DESC
		";
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$arMessages = Array();
		while ($arRes = $dbRes->Fetch())
			$arMessages[$arRes['ID']] = $arRes;

		return $arMessages;
	}

	public function SetReadMessage($fromUserId, $lastId = null)
	{
		global $DB;

		$fromUserId = intval($fromUserId);
		if ($fromUserId <= 0)
			return false;

		$bReadMessage = false;
		if ($lastId == null)
		{
			$strSql = "
				SELECT MAX(M.ID) ID, M.CHAT_ID
				FROM b_im_relation RF
					INNER JOIN b_im_relation RT on RF.CHAT_ID = RT.CHAT_ID
					INNER JOIN b_im_message M ON M.ID >= RT.LAST_ID AND M.CHAT_ID = RT.CHAT_ID
				WHERE RT.USER_ID = ".$this->user_id."
					and RF.USER_ID = ".$fromUserId."
					and RT.MESSAGE_TYPE = '".IM_MESSAGE_PRIVATE."' and RT.STATUS < ".IM_STATUS_READ."
				GROUP BY M.CHAT_ID";
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				$bReadMessage = self::SetLastId(intval($arRes['CHAT_ID']), $this->user_id, $arRes['ID']);

			$lastId = $arRes['ID'];
		}
		else
		{
			$strSql = "
				SELECT RF.CHAT_ID
				FROM b_im_relation RF INNER JOIN b_im_relation RT on RF.CHAT_ID = RT.CHAT_ID
				WHERE RT.USER_ID = ".$this->user_id."
					and RF.USER_ID = ".$fromUserId."
					and RT.MESSAGE_TYPE = '".IM_MESSAGE_PRIVATE."'";
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				$bReadMessage = self::SetLastId(intval($arRes['CHAT_ID']), $this->user_id, intval($lastId));
		}

		if ($bReadMessage)
		{
			CIMMessenger::SpeedFileDelete($this->user_id, IM_SPEED_MESSAGE);
			if (CModule::IncludeModule("pull"))
			{
				CPushManager::DeleteFromQueue($this->user_id, 'IM_MESS_'.$fromUserId);
				CPullStack::AddByUser($this->user_id, Array(
					'module_id' => 'im',
					'command' => 'readMessage',
					'params' => Array(
						'chatId' => intval($arRes['CHAT_ID']),
						'senderId' => $this->user_id,
						'id' => $fromUserId,
						'userId' => $fromUserId,
						'lastId' => $lastId
					),
				));
			}
			return true;
		}

		return false;
	}

	public static function SetReadMessageAll($fromUserId)
	{
		global $DB;

		$fromUserId = intval($fromUserId);
		if ($fromUserId <= 0)
			return false;

		$strSql = "
			SELECT RT.ID, RT.USER_ID
			FROM b_im_relation RF
			INNER JOIN b_im_relation RT on RF.CHAT_ID = RT.CHAT_ID AND RT.ID != RF.ID
			WHERE RF.USER_ID = ".$fromUserId."
			AND RT.MESSAGE_TYPE = '".IM_MESSAGE_PRIVATE."' AND RT.STATUS < ".IM_STATUS_READ;
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
		{
			self::SetLastId(intval($arRes['CHAT_ID']), $arRes['USER_ID']);
			CIMMessenger::SpeedFileDelete($arRes['USER_ID'], IM_SPEED_MESSAGE);
		}

		return true;
	}

	public static function SetLastId($chatId, $userId, $lastId = null)
	{
		global $DB;

		if (intval($chatId) <= 0 || intval($userId) <= 0)
			return false;

		$ssqlLastId = "STATUS = ".IM_STATUS_READ;
		$ssqlWhereLastId = "";
		if (!is_null($lastId) && intval($lastId) > 0)
		{
			$ssqlLastId = "LAST_ID = ".intval($lastId).", LAST_SEND_ID = ".intval($lastId);
			$ssqlWhereLastId = "AND LAST_ID < ".intval($lastId);

			$strSql = "
				SELECT COUNT(ID) CNT
				FROM b_im_message
				WHERE ID > ".intval($lastId)." AND CHAT_ID = ".intval($chatId);
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
			{
				if ($arRes['CNT'] == 0)
					$ssqlLastId .= ", STATUS = ".IM_STATUS_READ;
			}
		}

		$strSql = "
			UPDATE b_im_relation
			SET ".$ssqlLastId."
			WHERE CHAT_ID = ".intval($chatId)." AND USER_ID = ".intval($userId)." ".$ssqlWhereLastId;
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return true;
	}

	public static function SetLastSendId($chatId, $userId, $lastSendId)
	{
		global $DB;

		if (intval($chatId) <= 0 || intval($userId) <= 0 || intval($lastSendId) <= 0)
			return false;

		$strSql = "UPDATE b_im_relation SET LAST_SEND_ID = ".intval($lastSendId).", STATUS = ".IM_STATUS_NOTIFY." WHERE CHAT_ID = ".intval($chatId)." AND USER_ID = ".intval($userId);
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return true;
	}

	public static function GetFlashMessage($arUnreadMessage)
	{
		$arFlashMessage = Array();
		if (isset($_SESSION['IM_FLASHED_MESSAGE']))
		{
			foreach ($arUnreadMessage as $key => $arUnread)
			{
				$flashMessage = array_diff($arUnread, $_SESSION['IM_FLASHED_MESSAGE']);
				$_SESSION['IM_FLASHED_MESSAGE'] = array_merge($_SESSION['IM_FLASHED_MESSAGE'], $flashMessage);

				foreach ($arUnread as $k => $value) {
					if (isset($flashMessage[$k]))
						$arFlashMessage[$key][$value] = true;
					else
						$arFlashMessage[$key][$value] = false;
				}
			}
		}
		else
		{
			$_SESSION['IM_FLASHED_MESSAGE'] = Array();
			foreach ($arUnreadMessage as $key => $arUnread)
			{
				$_SESSION['IM_FLASHED_MESSAGE'] = array_merge($_SESSION['IM_FLASHED_MESSAGE'], $arUnread);
				foreach ($arUnread as $k => $value)
					$arFlashMessage[$key][$value] = true;
			}
		}
		return $arFlashMessage;
	}

	public static function Delete($ID)
	{
		global $DB;

		$ID = intval($ID);

		$strSql = "DELETE FROM b_im_message WHERE ID = ".$ID;
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		foreach(GetModuleEvents("im", "OnAfterDeleteMessage", true) as $arEvent)
			$arFields = ExecuteModuleEventEx($arEvent, array($ID));

		return true;
	}

	public static function GetFormatMessage($arParams)
	{
		$arParams['ID'] = intval($arParams['ID']);
		$arParams['TO_USER_ID'] = isset($arParams['TO_CHAT_ID'])? intval($arParams['TO_CHAT_ID']): intval($arParams['TO_USER_ID']);
		$arParams['FROM_USER_ID'] = intval($arParams['FROM_USER_ID']);
		$arParams['MESSAGE'] = trim($arParams['MESSAGE']);
		$arParams['DATE_CREATE'] = intval($arParams['DATE_CREATE']);

		$arUsers = CIMContactList::GetUserData(Array('ID' => isset($arParams['TO_CHAT_ID'])? $arParams['FROM_USER_ID']: Array($arParams['TO_USER_ID'], $arParams['FROM_USER_ID'])));
		$arChat = Array();
		if (isset($arParams['TO_CHAT_ID']))
		{
			$arChat = CIMChat::GetChatData(array(
				'ID' => $arParams['TO_USER_ID'],
				'USE_CACHE' => 'N'
			));
		}

		$CCTP = new CTextParser();
		$CCTP->MaxStringLen = 200;
		$CCTP->allow = array("HTML" => "N", "ANCHOR" => "Y", "BIU" => "N", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => "N", "NL2BR" => "Y", "VIDEO" => "N", "TABLE" => "N", "CUT_ANCHOR" => "N", "ALIGN" => "N");

		$CCTPM = new CTextParser();
		$CCTPM->MaxStringLen = 200;
		$CCTPM->allow = array("HTML" => "N", "ANCHOR" => "N", "BIU" => "N", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => "N", "NL2BR" => "Y", "VIDEO" => "N", "TABLE" => "N", "CUT_ANCHOR" => "N", "ALIGN" => "N");

		return Array(
			'CHAT' => isset($arChat['chat'])? $arChat['chat']: Array(),
			'USER_IN_CHAT' => isset($arChat['userInChat'])? $arChat['userInChat']: Array(),
			'USERS' => $arUsers['users'],
			'MESSAGE' => Array(
				'id' => $arParams['ID'],
				'senderId' => $arParams['FROM_USER_ID'],
				'recipientId' => isset($arParams['TO_CHAT_ID'])? 'chat'.$arParams['TO_USER_ID']: $arParams['TO_USER_ID'],
				'date' => $arParams['DATE_CREATE'],
				'text' => $CCTP->convertText(htmlspecialcharsbx($arParams['MESSAGE'])),
				'text_mobile' => $CCTPM->convertText(htmlspecialcharsbx($arParams['MESSAGE']))
			),
		);
	}
}
?>