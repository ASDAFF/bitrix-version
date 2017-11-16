<?
IncludeModuleLangFile(__FILE__);

global $SUPPORT_CACHE_USER_ROLES;
$SUPPORT_CACHE_USER_ROLES  = Array();

class CAllTicket
{

	const ADD = 1;
	const DELETE = 2;
	const CURRENT_DATE = 3;
	const IGNORE = 4;
	
	function err_mess()
	{
		$module_id = "support";
		@include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/install/version.php");
		return "<br>Module: ".$module_id." (".$arModuleVersion["VERSION"].")<br>Class: CAllTicket<br>File: ".__FILE__;
	}

	/***************************************************************

	������ ������� �� ������ � ������ �� ������

	�������������� �����:

	D - ������ ������
	R - ������ ������������
	T - ��������� ������������
	V - ����-������
	W - ������������� ������������

	*****************************************************************/

	function GetDeniedRoleID()
	{
		return "D";
	}

	function GetSupportClientRoleID()
	{
		return "R";
	}

	function GetSupportTeamRoleID()
	{
		return "T";
	}

	function GetDemoRoleID()
	{
		return "V";
	}

	function GetAdminRoleID()
	{
		return "W";
	}

	// ���������� true ���� �������� ������������ ����� �������� ���� �� ������
	function HaveRole($role, $userID=false)
	{
		global $DB, $USER, $APPLICATION, $SUPPORT_CACHE_USER_ROLES;
		if (!is_object($USER)) $USER = new CUser;

		if ($userID===false && is_object($USER))
			$uid = $USER->GetID();
		else
			$uid = $userID;

		$arRoles = Array();
		if (array_key_exists($uid, $SUPPORT_CACHE_USER_ROLES) && is_array($SUPPORT_CACHE_USER_ROLES[$uid]))
		{
			$arRoles = $SUPPORT_CACHE_USER_ROLES[$uid];
		}
		else
		{
			$arrGroups = Array();
			if ($userID===false && is_object($USER))
				$arrGroups = $USER->GetUserGroupArray();
			else
				$arrGroups = CUser::GetUserGroup($userID);

			sort($arrGroups);
			$arRoles = $APPLICATION->GetUserRoles("support", $arrGroups);
			$SUPPORT_CACHE_USER_ROLES[$uid] = $arRoles;
		}

		if (in_array($role, $arRoles))
			return true;

		return false;

	}

	// true - ���� ������������ ����� ���� "������������� ������������"
	// false - � ��������� ������
	function IsAdmin($userID=false)
	{
		global $USER;

		if ($userID===false && is_object($USER))
		{
			if ($USER->IsAdmin()) return true;
		}
		return CTicket::HaveRole(CTicket::GetAdminRoleID(), $userID);
	}

	// true - ���� ������������ ����� ���� "����-������"
	// false - � ��������� ������
	function IsDemo($userID=false)
	{
		return CTicket::HaveRole(CTicket::GetDemoRoleID(), $userID);
	}

	// true - ���� ������������ ����� ���� "��������� ������������"
	// false - � ��������� ������
	function IsSupportTeam($userID=false)
	{
		return CTicket::HaveRole(CTicket::GetSupportTeamRoleID(), $userID);
	}

	// true - ���� ������������ ����� ���� "��������� ������������"
	// false - � ��������� ������
	function IsSupportClient($userID=false)
	{
		return CTicket::HaveRole(CTicket::GetSupportClientRoleID(), $userID);
	}

	function IsOwner($ticketID, $userID=false)
	{
		$err_mess = (CAllTicket::err_mess())."<br>Function: IsOwner<br>Line: ";
		global $DB, $USER;
		if ($userID===false && is_object($USER)) $userID = $USER->GetID();
		$userID = intval($userID);
		$ticketID = intval($ticketID);
		if ($userID<=0 || $ticketID<=0) return false;

		$strSql = "SELECT 'x' FROM b_ticket WHERE ID=$ticketID and (OWNER_USER_ID=$userID or CREATED_USER_ID=$userID)";
		$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
		if ($ar = $rs->Fetch()) return true;

		return false;
	}

	// ���������� ���� ��������� ������������
	function GetRoles(&$isDemo, &$isSupportClient, &$isSupportTeam, &$isAdmin, &$isAccess, &$userID, $checkRights=true)
	{
		global $DB, $USER, $APPLICATION;
		static $arTicketUserRoles;
		$isDemo = $isSupportClient = $isSupportTeam = $isAdmin = $isAccess = false;
		if (is_object($USER)) $userID = intval($USER->GetID()); else $userID = 0;
		if ($checkRights)
		{
			if ($userID>0)
			{
				if (is_array($arTicketUserRoles) && in_array($userID, array_keys($arTicketUserRoles)))
				{
					$isDemo = $arTicketUserRoles[$userID]["isDemo"];
					$isSupportClient = $arTicketUserRoles[$userID]["isSupportClient"];
					$isSupportTeam = $arTicketUserRoles[$userID]["isSupportTeam"];
					$isAdmin = $arTicketUserRoles[$userID]["isAdmin"];
				}
				else
				{
					$isDemo = CTicket::IsDemo($userID);
					$isSupportClient = CTicket::IsSupportClient($userID);
					$isSupportTeam = CTicket::IsSupportTeam($userID);
					$isAdmin = CTicket::IsAdmin($userID);
					$arTicketUserRoles[$userID] = array(
						"isDemo"			=> $isDemo,
						"isSupportClient"	=> $isSupportClient,
						"isSupportTeam"		=> $isSupportTeam,
						"isAdmin"			=> $isAdmin,
						);
				}
			}
		}
		else $isAdmin = true;

		if ($isDemo || $isSupportClient || $isSupportTeam || $isAdmin) $isAccess = true;
	}

	// ���������� ������ ID ����� ��� ������� ������ ����
	// $role - ������������� ����
	function GetGroupsByRole($role)
	{
		//Todo: ������������ � �������� �� ���������

		global $APPLICATION, $USER;
		if (!is_object($USER)) $USER = new CUser;

		$arGroups = array(); $arBadGroups = Array();
		$res = $APPLICATION->GetGroupRightList(Array("MODULE_ID" => "support"/*, "G_ACCESS" => $role*/));
		while($ar = $res->Fetch())
		{
			if ($ar["G_ACCESS"] == $role)
				$arGroups[] = $ar["GROUP_ID"];
			else
				$arBadGroups[] = $ar["GROUP_ID"];
		}

		$right = COption::GetOptionString("support", "GROUP_DEFAULT_RIGHT", "D");
		if ($right == $role)
		{
			$res = CGroup::GetList($v1="dropdown", $v2="asc", array("ACTIVE" => "Y"));
			while ($ar = $res->Fetch())
			{
				if (!in_array($ar["ID"],$arGroups) && !in_array($ar["ID"],$arBadGroups))
					$arGroups[] = $ar["ID"];
			}
		}

		return $arGroups;

		/*$arGroups = array();

		$z = CGroup::GetList($v1="dropdown", $v2="asc", array("ACTIVE" => "Y"));
		while($zr = $z->Fetch())
		{
			$arRoles = $APPLICATION->GetUserRoles("support", array(intval($zr["ID"])), "Y", "N");
			if (in_array($role, $arRoles)) $arGroups[] = intval($zr["ID"]);
		}

		return array_unique($arGroups);*/
	}

	// ���������� ������ ����� � ����� "������������� ������������"
	function GetAdminGroups()
	{
		return CTicket::GetGroupsByRole(CTicket::GetAdminRoleID());
	}

	// ���������� ������ ����� � ����� "��������� ������������"
	function GetSupportTeamGroups()
	{
		return CTicket::GetGroupsByRole(CTicket::GetSupportTeamRoleID());
	}

	// ���������� ������ EMail ������� ���� ������������� ������� �������� ����
	function GetEmailsByRole($role)
	{
		global $DB, $APPLICATION, $USER;
		if (!is_object($USER)) $USER = new CUser;
		$arEmail = array();
		$arGroups = CTicket::GetGroupsByRole($role);
		if (is_array($arGroups) && count($arGroups)>0)
		{
			$rsUser = CUser::GetList($v1="id", $v2="desc", array("ACTIVE" => "Y", "GROUPS_ID" => $arGroups));
			while ($arUser = $rsUser->Fetch()) $arEmail[$arUser["EMAIL"]] = $arUser["EMAIL"];
		}
		return array_unique($arEmail);
	}

	// ���������� ������ EMail'�� ���� ������������� ������� ���� "�������������"
	function GetAdminEmails()
	{
		return CTicket::GetEmailsByRole(CTicket::GetAdminRoleID());
	}

	// ���������� ������ EMail'�� ���� ������������� ������� ���� "��������� ������������"
	function GetSupportTeamEmails()
	{
		return CTicket::GetEmailsByRole(CTicket::GetSupportTeamRoleID());
	}
	
	function GetSupportTeamAndAdminUsers()
	{
		$arUser = array();
		$stg = CTicket::GetGroupsByRole(CTicket::GetSupportTeamRoleID());
		$sag = CTicket::GetGroupsByRole(CTicket::GetAdminRoleID());
		$sg = array();
		if(is_array($stg)) 
		{
			$sg = array_merge($sg, $stg);
		}
		if(is_array($sag)) 
		{
			$sg = array_merge($sg, $sag);
		}
		if(count($sg) > 0)
		{
			$cU = CUser::GetList($v1="id", $v2="asc", array("ACTIVE" => "Y", "GROUPS_ID" => $sg));
			while($arU = $cU->Fetch()) $arUser[] = intval($arU["ID"]);
		}
		if(count($arUser) <= 0)
		{
			$arUser[] = 1;
		}
		return array_unique($arUser);
	}

	/*****************************************************************
				������ ������� ����� ��� ���� �������
	*****************************************************************/

	// �������� ����� �������
	function CheckFilter($arFilter)
	{
		$err_mess = (CAllTicket::err_mess())."<br>Function: CheckFilter<br>Line: ";
		global $DB, $USER, $APPLICATION;
		$str = "";
		$arMsg = Array();

		$arDATES = array(
			"DATE_MODIFY_1",
			"DATE_MODIFY_2",
			"DATE_CREATE_1",
			"DATE_CREATE_2",
			);
		foreach($arDATES as $key)
		{
			if (is_set($arFilter, $key) && strlen($arFilter[$key])>0 && !CheckDateTime($arFilter[$key]))
				$arMsg[] = array("id"=>$key, "text"=> GetMessage("SUP_ERROR_REQUIRED_".$key));
				//$str.= GetMessage("SUP_ERROR_INCORRECT_".$key)."<br>";
		}

		if(!empty($arMsg))
		{
			$e = new CAdminException($arMsg);
			$GLOBALS["APPLICATION"]->ThrowException($e);
			return false;
		}

		return true;
	}

	// �������� ����� ����� �������� � ���� ������
	function CheckFields($arFields, $id, $arRequired)
	{
		global $DB, $USER, $APPLICATION, $MESS;

		$arMsg = Array();

		// ��������� ��������� ������������ ����
		if (is_array($arRequired) && count($arRequired)>0)
		{
			foreach($arRequired as $key)
			{
				if ($id<=0 || ($id>0 && is_set($arFields, $key)))
				{
					if (!is_array($arFields[$key]) && (strlen($arFields[$key])<=0 || $arFields[$key]=="NOT_REF"))
					{
						$arMsg[] = array("id"=>$key, "text"=> GetMessage("SUP_ERROR_REQUIRED_".$key));
						//$str.= GetMessage("SUP_ERROR_REQUIRED_".$key)."<br>";
					}
				}
			}
		}

		// ��������� ������������ ���
		$arDate = array(
			"DATE_CREATE",
			"DATE_MODIFY",
			"LAST_MESSAGE_DATE",
			);
		foreach($arDate as $key)
		{
			if (strlen($arFields[$key])>0)
			{
				if (!CheckDateTime($arFields[$key]))
					$arMsg[] = array("id"=>$key, "text"=> GetMessage("SUP_ERROR_INCORRECT_".$key));
					//$str.= GetMessage("SUP_ERROR_INCORRECT_".$key)."<br>";
			}
		}

		$arEmail = array(
			"EMAIL",
			);
		foreach($arEmail as $key)
		{
			if (strlen($arFields[$key])>0)
			{
				if (!check_email($arFields[$key]))
					$arMsg[] = array("id"=>$key, "text"=> GetMessage("SUP_ERROR_INCORRECT_".$key));
					//$str.= GetMessage("SUP_ERROR_INCORRECT_".$key)."<br>";
			}
		}

		if(!empty($arMsg))
		{
			$e = new CAdminException($arMsg);
			$GLOBALS["APPLICATION"]->ThrowException($e);
			return false;
		}

		return true;
	}

	// �������������� ������������ ������ �������� ��� ������� � ���� ������
	function PrepareFields($arFields, $table, $id)
	{
		global $DB, $USER, $APPLICATION;

		$id = intval($id);
		$arFields_i = array();

		// �����
		$arrNUMBER = array(
			"SLA_ID",
			"AGENT_ID",
			"CATEGORY_ID",
			"CRITICALITY_ID",
			"STATUS_ID",
			"MARK_ID",
			"SOURCE_ID",
			"DIFFICULTY_ID",
			"DICTIONARY_ID",
			"TICKET_ID",
			"MESSAGE_ID",
			"AUTO_CLOSE_DAYS",
			"MESSAGES",
			"OVERDUE_MESSAGES",
			"EXTERNAL_ID",
			"OWNER_USER_ID",
			"OWNER_GUEST_ID",
			"CREATED_USER_ID",
			"CREATED_GUEST_ID",
			"MODIFIED_USER_ID",
			"MODIFIED_GUEST_ID",
			"RESPONSIBLE_USER_ID",
			"LAST_MESSAGE_USER_ID",
			"LAST_MESSAGE_GUEST_ID",
			"CURRENT_RESPONSIBLE_USER_ID",
			"USER_ID",
			"C_NUMBER",
			"C_SORT",
			"PRIORITY",
			"RESPONSE_TIME",
			"NOTICE_TIME",
			"WEEKDAY_NUMBER",
			"MINUTE_FROM",
			"MINUTE_TILL",
			"TIMETABLE_ID"
			);
		foreach($arrNUMBER as $key)
			if (is_set($arFields, $key))
				$arFields_i[$key] = (strlen($arFields[$key])>0) ? intval($arFields[$key]) : "null";

		// ��� ������
		$arrTYPE = array(
			"PREVIEW_TYPE",
			"DESCRIPTION_TYPE",
			);
		foreach($arrTYPE as $key)
			if (is_set($arFields, $key))
				$arFields_i[$key] = $arFields[$key]=="text" ? "'text'" : "'html'";

		// �������
		$arrBOOLEAN = array(
			"AUTO_CLOSED",
			"IS_SPAM",
			"LAST_MESSAGE_BY_SUPPORT_TEAM",
			"IS_HIDDEN",
			"IS_LOG",
			"IS_OVERDUE",
			"IS_SPAM",
			"MESSAGE_BY_SUPPORT_TEAM",
			"SET_AS_DEFAULT",
			"AUTO_CLOSED",
			"HOLD_ON",
			"NOT_CHANGE_STATUS",
			);
		foreach($arrBOOLEAN as $key)
			if (is_set($arFields, $key))
				$arFields_i[$key] = $arFields[$key]=="Y" ? "'Y'" : "'N'";

		// �����
		$arrTEXT = array(
			"OWNER_SID",
			"LAST_MESSAGE_SID",
			"SUPPORT_COMMENTS",
			"MESSAGE",
			"MESSAGE_SEARCH",
			"EXTERNAL_FIELD_1",
			"DESCR",
			"DESCRIPTION",
			);
		foreach($arrTEXT as $key)
			if (is_set($arFields, $key))
				$arFields_i[$key] = (strlen($arFields[$key])>0) ? "'".$DB->ForSql($arFields[$key])."'" : "null";

		// ������
		$arrSTRING = array(
			"NAME",
			"TITLE",
			"CREATED_MODULE_NAME",
			"MODIFIED_MODULE_NAME",
			"HASH",
			"EXTENSION_SUFFIX",
			"C_TYPE",
			"SID",
			"EVENT1",
			"EVENT2",
			"EVENT3",
			"RESPONSE_TIME_UNIT",
			"NOTICE_TIME_UNIT",
			"OPEN_TIME",
			);
		foreach($arrSTRING as $key)
			if (is_set($arFields, $key))
				$arFields_i[$key] = (strlen($arFields[$key])>0) ? "'".$DB->ForSql($arFields[$key], 255)."'" : "null";

		// ����
		$arDate = array(
			"TIMESTAMP_X",
			"DATE_CLOSE",
			"LAST_MESSAGE_DATE",
			);
		foreach($arDate as $key)
			if (is_set($arFields, $key))
				$arFields_i[$key] = (strlen($arFields[$key])>0) ? $DB->CharToDateFunction($arFields[$key]) : "null";

		/* �����������
		$arIMAGE = array();
		foreach($arIMAGE as $key)
		{
			if (is_set($arFields, $key))
			{
				if (is_array($arFields[$key]))
				{
					$arIMAGE = $arFields[$key];
					$arIMAGE["MODULE_ID"] = "support";
					$arIMAGE["del"] = $_POST[$key."_del"];
					if ($id>0)
					{
						$rs = $DB->Query("SELECT $key FROM $table WHERE ID=$id", false, $err_mess.__LINE__);
						$ar = $rs->Fetch();
						$arIMAGE["old_file"] = $ar[$key];
					}
					if (strlen($arIMAGE["name"])>0 || strlen($arIMAGE["del"])>0)
					{
						$fid = CFile::SaveFile($arIMAGE, "support");
						$arFields_i[$key] = (intval($fid)>0) ? intval($fid) : "null";
					}
				}
				else
				{
					if ($id>0)
					{
						$rs = $DB->Query("SELECT $key FROM $table WHERE ID=$id", false, $err_mess.__LINE__);
						$ar = $rs->Fetch();
						if (intval($ar[$key])>0) CFile::Delete($ar[$key]);
					}
					$arFields_i[$key] = intval($arFields[$key]);
				}
			}
		}*/

		if (is_set($arFields, "CREATED_USER_ID"))
		{
			if (intval($arFields["CREATED_USER_ID"])>0) $arFields_i["CREATED_USER_ID"] = intval($arFields["CREATED_USER_ID"]);
		}
		elseif($id<=0 && $USER->IsAuthorized()) $arFields_i["CREATED_USER_ID"] = intval($USER->GetID());

		if (is_set($arFields, "CREATED_GUEST_ID"))
		{
			if (intval($arFields["CREATED_GUEST_ID"])>0) $arFields_i["CREATED_GUEST_ID"] = intval($arFields["CREATED_GUEST_ID"]);
		}
		elseif($id<=0 && array_key_exists('SESS_GUEST_ID', $_SESSION)) $arFields_i["CREATED_GUEST_ID"] = intval($_SESSION["SESS_GUEST_ID"]);

		if (is_set($arFields, "MODIFIED_USER_ID"))
		{
			if (intval($arFields["MODIFIED_USER_ID"])>0) $arFields_i["MODIFIED_USER_ID"] = intval($arFields["MODIFIED_USER_ID"]);
		}
		elseif ($USER->IsAuthorized()) $arFields_i["MODIFIED_USER_ID"] = intval($USER->GetID());

		if (is_set($arFields, "MODIFIED_GUEST_ID"))
		{
			if (intval($arFields["MODIFIED_GUEST_ID"])>0) $arFields_i["MODIFIED_GUEST_ID"] = intval($arFields["MODIFIED_GUEST_ID"]);
		}
		elseif (array_key_exists('SESS_GUEST_ID', $_SESSION)) $arFields_i["MODIFIED_GUEST_ID"] = intval($_SESSION["SESS_GUEST_ID"]);

		if (is_set($arFields, "DATE_CREATE"))
		{
			if (strlen($arFields["DATE_CREATE"])>0) $arFields_i["DATE_CREATE"] = $DB->CharToDateFunction($arFields["DATE_CREATE"]);
		}
		elseif ($id<=0) $arFields_i["DATE_CREATE"] = $DB->CurrentTimeFunction();


		if (is_set($arFields, "LAST_MESSAGE_DATE"))
		{
			if (strlen($arFields["LAST_MESSAGE_DATE"])>0) $arFields_i["LAST_MESSAGE_DATE"] = $DB->CharToDateFunction($arFields["LAST_MESSAGE_DATE"]);
		}
		elseif ($id<=0) $arFields_i["LAST_MESSAGE_DATE"] = $DB->CurrentTimeFunction();



		if (is_set($arFields, "DATE_MODIFY"))
		{
			if (strlen($arFields["DATE_MODIFY"])>0) $arFields_i["DATE_MODIFY"] = $DB->CharToDateFunction($arFields["DATE_MODIFY"]);
		}
		else $arFields_i["DATE_MODIFY"] = $DB->CurrentTimeFunction();

		// ������� ������ ���� ��� ��������� �������
		unset($arFields_i["ID"]);
		$ar1 = $DB->GetTableFieldsList($table);
		$ar2 = array_keys($arFields_i);
		$arDiff = array_diff($ar2, $ar1);
		if (is_array($arDiff) && count($arDiff)>0) foreach($arDiff as $value) unset($arFields_i[$value]);

		return $arFields_i;
	}

	function SplitTicket($arParam)
	{
		global $DB;

		$intLastTicketID 	 = IntVal($arParam['SOURCE_TICKET_ID']);
		$stLastTicketTitle	 = htmlspecialcharsEx($arParam['SOURCE_TICKET_TITLE']);
		$intSplitMesageID	 = IntVal($arParam['SOURCE_MESSAGE_NUM']);
		$stSplitMesageDate	 = MakeTimeStamp($arParam['SOURCE_MESSAGE_DATE'], "DD.MM.YYYY HH:MI:SS") ? $arParam['SOURCE_MESSAGE_DATE'] : '';

		// add to the previous post about ticket allocation of posts in a separate branch
		$arFields = array(
			"MESSAGE_CREATED_USER_ID"		=> $arParam['SPLIT_MESSAGE_USER_ID'],
			"MESSAGE_CREATED_MODULE_NAME"	=> "support",
			"MESSAGE_CREATED_GUEST_ID"		=> "null",
			"MESSAGE_SOURCE_ID"				=> $arParam['SOURCE_MESSAGE_ID'],
			"MESSAGE"						=> GetMessage("SUP_SPLIT_MESSAGE_USER_1", array("#MESSAGE_DATE#" => $stSplitMesageDate, "#TITLE#" => '# '.$arParam['SPLIT_TICKET_ID'].' "'.$arParam['SPLIT_TICKET_TITLE'].'"')),
			"LOG"							=> "N",
			"HIDDEN"						=> "N",
			"NOT_CHANGE_STATUS"				=> "Y",
			"MESSAGE_AUTHOR_USER_ID"		=> $arParam['SPLIT_MESSAGE_USER_ID'],
		);
		CTicket::AddMessage($intLastTicketID, $arFields, $arFiles=Array(), "N");

		// add to the previous post about ticket allocation of posts in a separate branch (support log)
		$arFields_log = array(
			"MESSAGE_CREATED_USER_ID"		=> $arParam['SPLIT_MESSAGE_USER_ID'],
			"MESSAGE_CREATED_MODULE_NAME"	=> "support",
			"MESSAGE_CREATED_GUEST_ID"		=> "null",
			"MESSAGE_SOURCE_ID"				=> $arParam['SOURCE_MESSAGE_ID'],
			"MESSAGE"						=> GetMessage("SUP_SPLIT_MESSAGE_LOG_1", array("#MESSAGE_ID#" => $intSplitMesageID, "#TITLE#" => '<a href="ticket_edit.php?ID='.$arParam['SPLIT_TICKET_ID'].'&lang='.LANGUAGE_ID.'"> # '.$arParam['SPLIT_TICKET_ID'].' "'.$arParam['SPLIT_TICKET_TITLE'].'"</a>')),
			"LOG"							=> "Y",
		);
		CTicket::AddMessage($intLastTicketID, $arFields_log, $arFiles_log=Array(), "N");

		// add a new ticket allocation message posted in a separate branch
		$arFields = array(
			"MESSAGE_CREATED_USER_ID"		=> $arParam['SPLIT_MESSAGE_USER_ID'],
			"MESSAGE_CREATED_MODULE_NAME"	=> "support",
			"MESSAGE_CREATED_GUEST_ID"		=> "null",
			"MESSAGE_SOURCE_ID"				=> $arParam['SOURCE_MESSAGE_ID'],
			"MESSAGE"						=> GetMessage("SUP_SPLIT_MESSAGE_USER_2", array("#MESSAGE_DATE#" => $stSplitMesageDate, "#TITLE#" => '# '.$intLastTicketID.' "'.$stLastTicketTitle.'"')),
			"LOG"							=> "N",
			"HIDDEN"						=> "N",
			"NOT_CHANGE_STATUS"				=> "Y",
			"MESSAGE_AUTHOR_USER_ID"		=> $arParam['SPLIT_MESSAGE_USER_ID'],
		);
		CTicket::AddMessage($arParam['SPLIT_TICKET_ID'], $arFields, $arFiles=Array(), "N");

		// add a new ticket allocation message posted in a separate branch (support log)
		$arFields_log = array(
			"MESSAGE_CREATED_USER_ID"		=> $arParam['SPLIT_MESSAGE_USER_ID'],
			"MESSAGE_CREATED_MODULE_NAME"	=> "support",
			"MESSAGE_CREATED_GUEST_ID"		=> "null",
			"MESSAGE_SOURCE_ID"				=> $arParam['SOURCE_MESSAGE_ID'],
			"MESSAGE"						=> GetMessage("SUP_SPLIT_MESSAGE_LOG_2", array("#MESSAGE_ID#" => $intSplitMesageID, "#TITLE#" => '<a href="ticket_edit.php?ID='.$intLastTicketID.'&lang='.LANGUAGE_ID.'"> # '.$intLastTicketID.' "'.$stLastTicketTitle.'"</a>')),
			"LOG"							=> "Y",
		);
		CTicket::AddMessage($arParam['SPLIT_TICKET_ID'], $arFields_log, $arFiles_log=Array(), "N");

		// If the message that we want to separate, there are attached files, copy them
		if (isset($arParam['SPLIT_ATTACH_FILE']))
		{
			$res = CTicket::GetMessageList($by='ID', $order='ASC', array('TICKET_ID'=>$arParam['SPLIT_TICKET_ID']), $is_filtered = false);
			$MESSAGE = $res->Fetch();
			foreach($arParam['SPLIT_ATTACH_FILE'] as $key => $iAttachFile)
			{
				$fid = CFile::CopyFile(intval($iAttachFile));
				if ($fid>0)
				{
					$arFields_fi = array(
						"HASH"				=> "'".$DB->ForSql(md5(uniqid(mt_rand(), true).time()), 255)."'",
						"MESSAGE_ID"		=> $MESSAGE['ID'],
						"FILE_ID"			=> $fid,
						"TICKET_ID"			=> $arParam['SPLIT_TICKET_ID'],
						"EXTENSION_SUFFIX"	=> "null"
					);
					$DB->Insert("b_ticket_message_2_file",$arFields_fi, $err_mess.__LINE__);
				}
			}
		}
	}

	/*****************************************************************
					������ ������� �� ������ �� ������
	*****************************************************************/

	function MarkMessageAsSpam($messageID, $exactly="Y", $checkRights="Y")
	{
		$err_mess = (CAllTicket::err_mess())."<br>Function: MarkMessageAsSpam<br>Line: ";
		global $DB, $USER;
		$messageID = intval($messageID);
		if ($messageID<=0) return;

		$bAdmin = "N";
		$bSupportTeam = "N";
		if ($checkRights=="Y")
		{
			$bAdmin = (CTicket::IsAdmin()) ? "Y" : "N";
			$bSupportTeam = (CTicket::IsSupportTeam()) ? "Y" : "N";
		}
		else
		{
			$bAdmin = "Y";
			$bSupportTeam = "Y";
		}

		if (($bAdmin=="Y" || $bSupportTeam=="Y") && CModule::IncludeModule("mail"))
		{
			$exactly = ($exactly=="Y" && $bAdmin=="Y") ? "Y" : "N";
			if ($rsMessage = CTicket::GetMessageByID($messageID, $checkRights))
			{
				if ($arMessage = $rsMessage->Fetch())
				{
					if ($arMessage["IS_LOG"]!="Y")
					{
						$email_id = intval($arMessage["EXTERNAL_ID"]);
						$header = $arMessage["EXTERNAL_FIELD_1"];
						$arFields = array("IS_SPAM" => "'".$exactly."'");
						$DB->Update("b_ticket_message",$arFields,"WHERE ID=".$messageID,$err_mess.__LINE__);

						$exactly = ($exactly=="Y") ? true : false;
						$rsEmail = CMailMessage::GetByID($email_id);
						if ($rsEmail->Fetch())
						{
							CMailMessage::MarkAsSpam($email_id, $exactly);
						}
						else
						{
							CmailFilter::MarkAsSpam($header." \n\r ".$arMessage["MESSAGE"], $exactly);
						}
					}
				}
			}
		}
	}

	function UnMarkMessageAsSpam($messageID, $checkRights="Y")
	{
		$err_mess = (CAllTicket::err_mess())."<br>Function: UnMarkMessageAsSpam<br>Line: ";
		global $DB, $USER;
		$messageID = intval($messageID);
		if ($messageID<=0) return;

		$bAdmin = "N";
		$bSupportTeam = "N";
		if ($checkRights=="Y")
		{
			$bAdmin = (CTicket::IsAdmin()) ? "Y" : "N";
			$bSupportTeam = (CTicket::IsSupportTeam()) ? "Y" : "N";
		}
		else
		{
			$bAdmin = "Y";
			$bSupportTeam = "Y";
		}

		if (($bAdmin=="Y" || $bSupportTeam=="Y") && CModule::IncludeModule("mail"))
		{
			$rsMessage = CTicket::GetMessageByID($messageID, $checkRights);
			if ($arMessage = $rsMessage->Fetch())
			{
				$arFields = array("IS_SPAM" => "null");
				$DB->Update("b_ticket_message", $arFields, "WHERE ID=".$messageID, $err_mess.__LINE__);

				$email_id = intval($arMessage["EXTERNAL_ID"]);
				$header = $arMessage["EXTERNAL_FIELD_1"];
				$rsEmail = CMailMessage::GetByID($email_id);
				if ($rsEmail->Fetch())
				{
					CMailMessage::MarkAsSpam($email_id, false);
				}
				else
				{
					CmailFilter::DeleteFromSpamBase($header." \n\r ".$arMessage["MESSAGE"], true);
					CmailFilter::MarkAsSpam($header." \n\r ".$arMessage["MESSAGE"], false);
				}
			}
		}
	}

	function MarkAsSpam($ticketID, $exactly="Y", $checkRights="Y")
	{
		$err_mess = (CAllTicket::err_mess())."<br>Function: MarkAsSpam<br>Line: ";
		global $DB, $USER;
		$ticketID = intval($ticketID);
		if ($ticketID<=0) return;

		$bAdmin = "N";
		$bSupportTeam = "N";
		if ($checkRights=="Y")
		{
			$bAdmin = (CTicket::IsAdmin()) ? "Y" : "N";
			$bSupportTeam = (CTicket::IsSupportTeam()) ? "Y" : "N";
		}
		else
		{
			$bAdmin = "Y";
			$bSupportTeam = "Y";
		}

		if ($bAdmin=="Y" || $bSupportTeam=="Y")
		{
			$exactly = ($exactly=="Y" && $bAdmin=="Y") ? "Y" : "N";

			$arFilter = array("TICKET_ID" => $ticketID, "TICKET_ID_EXACT_MATCH" => "Y", "IS_LOG" => "N");
			if ($rsMessages = CTicket::GetMessageList($a, $b, $arFilter, $c, $checkRights))
			{
				// �������� �������� ���������
				if ($arMessage = $rsMessages->Fetch())
				{
					CTicket::MarkMessageAsSpam($arMessage["ID"], $exactly, $checkRights);
				}
			}
			$arFields = array("IS_SPAM" => "'".$exactly."'");
			$DB->Update("b_ticket",$arFields,"WHERE ID=".$ticketID,$err_mess.__LINE__);
		}
	}

	function UnMarkAsSpam($ticketID, $checkRights="Y")
	{
		$err_mess = (CAllTicket::err_mess())."<br>Function: UnMarkAsSpam<br>Line: ";
		global $DB, $USER;
		$ticketID = intval($ticketID);
		if ($ticketID<=0) return;

		if ($checkRights=="Y")
		{
			$bAdmin = (CTicket::IsAdmin()) ? "Y" : "N";
			$bSupportTeam = (CTicket::IsSupportTeam()) ? "Y" : "N";
		}
		else
		{
			$bAdmin = "Y";
			$bSupportTeam = "Y";
		}

		if ($bAdmin=="Y" || $bSupportTeam=="Y")
		{
			$arFilter = array("TICKET_ID" => $ticketID, "TICKET_ID_EXACT_MATCH" => "Y");
			if ($rsMessages = CTicket::GetMessageList($a, $b, $arFilter, $c, $checkRights))
			{
				// ������� ������� � ����� ������ � ������� ���������
				if ($arMessage = $rsMessages->Fetch())
				{
					CTicket::UnMarkMessageAsSpam($arMessage["ID"], $checkRights);
				}
			}
			$arFields = array("IS_SPAM" => "null");
			$DB->Update("b_ticket",$arFields,"WHERE ID=".$ticketID,$err_mess.__LINE__);
		}
	}


	/*****************************************************************
					������ ������� �� ���������� �����������
	*****************************************************************/

	function UpdateLastParams($ticketID, $resetAutoClose=false, $changeLastMessageDate = true, $setReopenDefault = true)
	{	
		$err_mess = (CAllTicket::err_mess())."<br>Function: UpdateLastParams<br>Line: ";
		global $DB, $USER;
		$ticketID = intval($ticketID);
		if ($ticketID<=0) return;

		$arFields = array();
		if ($resetAutoClose=="Y") $arFields["AUTO_CLOSE_DAYS"] = "null";

		
		// ��������� ���������� ������
		$strSql = "
			SELECT
				ID,
				".$DB->DateToCharFunction("DATE_CREATE","FULL")." DATE_CREATE,
				OWNER_USER_ID,
				OWNER_GUEST_ID,
				OWNER_SID
			FROM
				b_ticket_message
			WHERE
				TICKET_ID=$ticketID
			and (NOT_CHANGE_STATUS='N')
			and (IS_HIDDEN='N' or IS_HIDDEN is null or ".$DB->Length("IS_HIDDEN")."<=0)
			and (IS_LOG='N' or IS_LOG is null or ".$DB->Length("IS_LOG")."<=0)
			and (IS_OVERDUE='N' or IS_OVERDUE is null or ".$DB->Length("IS_OVERDUE")."<=0)
			ORDER BY
				C_NUMBER desc
			";
		$rs = $DB->Query($strSql,false,$err_mess.__LINE__);
		if ($arLastMess = $rs->Fetch())
		{
			$arFields["LAST_MESSAGE_USER_ID"] = $arLastMess["OWNER_USER_ID"];
			if ($changeLastMessageDate)
			{
				$arFields["LAST_MESSAGE_DATE"] = $DB->CharToDateFunction($arLastMess["DATE_CREATE"]);//NN
			}
			$arFields["LAST_MESSAGE_GUEST_ID"] = intval($arLastMess["OWNER_GUEST_ID"]);
			$arFields["LAST_MESSAGE_SID"] = "'".$DB->ForSql($arLastMess["OWNER_SID"],255)."'";
		}

		// ��������� ���������� ���������
		$strSql = "
			SELECT
				SUM(CASE WHEN IS_HIDDEN='Y' THEN 0 ELSE 1 END) MESSAGES,
				SUM(TASK_TIME) ALL_TIME
			FROM
				b_ticket_message
			WHERE
				TICKET_ID = $ticketID
			and (IS_LOG='N' or IS_LOG is null or ".$DB->Length("IS_LOG")."<=0)
			and (IS_OVERDUE='N' or IS_OVERDUE is null or ".$DB->Length("IS_OVERDUE")."<=0)
			";
		$z = $DB->Query($strSql, false, $err_mess.__LINE__);
		$zr = $z->Fetch();
		$arFields["MESSAGES"] = intval($zr["MESSAGES"]);
		$arFields["PROBLEM_TIME"] = intval($zr["ALL_TIME"]);
		
		
		if ($setReopenDefault)
			$arFields["REOPEN"] = "'N'";
			
		/*
		AUTO_CLOSE_DAYS
		LAST_MESSAGE_DATE
		LAST_MESSAGE_USER_ID
		LAST_MESSAGE_GUEST_ID
		LAST_MESSAGE_SID
		MESSAGES
		REOPEN
		PROBLEM_TIME
		*/
				
		$DB->Update("b_ticket",$arFields,"WHERE ID='".$ticketID."'",$err_mess.__LINE__);		
	}
	
	//$dateType = CTicket::ADD, CTicket::DELETE, CTicket::CURRENT_DATE
	function UpdateLastParams2($ticketID, $dateType)
	{
		global $DB;
		$strUsers = implode(",", CTicket::GetSupportTeamAndAdminUsers());
		$err_mess = (CAllTicket::err_mess())."<br>Function: UpdateLastParams2<br>Line: ";
		$arFields=array();
		$arFields["D_1_USER_M_AFTER_SUP_M"] = "null";
		$arFields["ID_1_USER_M_AFTER_SUP_M"] = "null";
		$arFields["LAST_MESSAGE_BY_SUPPORT_TEAM"] = "'Y'";
		$arFields["SUPPORT_DEADLINE_NOTIFY"] = "null";
		$arFields["SUPPORT_DEADLINE"] = "null";
		$arFields["IS_OVERDUE"] = "'N'";
		$arFields["IS_NOTIFIED"] = "'N'";
				
		// Get last support response
		$M_ID = 0;
		$strSql = "
			SELECT
				T.ID ID,
				MAX(TM.ID) M_ID
			FROM
				b_ticket T
				INNER JOIN b_ticket_message TM
					ON T.ID = TM.TICKET_ID
						AND T.ID = $ticketID
						AND (TM.IS_LOG='N' OR TM.IS_LOG IS NULL OR " . $DB->Length("TM.IS_LOG") . " <= 0)
						AND TM.OWNER_USER_ID IN($strUsers)
				
			GROUP BY
				T.ID";
				
		$rs = $DB->Query($strSql, false, $err_mess . __LINE__);
		if($arrRs = $rs->Fetch()) if(intval($arrRs["M_ID"]) > 0) $M_ID = intval($arrRs["M_ID"]);
		
		// Get first user request after last support response
		$strSql = "
			SELECT
				T.SLA_ID,
				T.DATE_CLOSE,
				SLA.RESPONSE_TIME_UNIT,
				SLA.RESPONSE_TIME,
				SLA.NOTICE_TIME_UNIT,
				SLA.NOTICE_TIME,
				PZ2.M_ID,
				PZ2.D_1_USER_M_AFTER_SUP_M,
				" . $DB->DateToCharFunction("T.D_1_USER_M_AFTER_SUP_M", "FULL") . " DATE_OLD
			FROM
				b_ticket T
				INNER JOIN b_ticket_sla SLA
					ON T.SLA_ID = SLA.ID
						AND T.ID = $ticketID
				LEFT JOIN (SELECT
					TM.ID M_ID,
					TM.TICKET_ID,
					" . $DB->DateToCharFunction("TM.DATE_CREATE", "FULL") . " D_1_USER_M_AFTER_SUP_M
				FROM
					b_ticket_message TM
					INNER JOIN (SELECT
							T.ID ID,
							MIN(TM.ID) M_ID
						FROM
							b_ticket T
							INNER JOIN b_ticket_message TM
								ON T.ID = TM.TICKET_ID
								AND T.ID = $ticketID
								AND (NOT(TM.IS_LOG='Y'))
								AND TM.ID > $M_ID
							
						GROUP BY
							T.ID) PZ
						ON TM.ID = PZ.M_ID) PZ2
						ON T.ID = PZ2.TICKET_ID
						
		";
		//AND (NOT(TM.IS_HIDDEN='Y'))
		$rs = $DB->Query($strSql, false, $err_mess . __LINE__);
		if(!($arrRs = $rs->Fetch()))
		{
			return;
		}
		
		if(intval($arrRs["M_ID"]) > 0)
		{
			$arFields["D_1_USER_M_AFTER_SUP_M"] = $DB->CharToDateFunction($arrRs["D_1_USER_M_AFTER_SUP_M"]);
			$arFields["ID_1_USER_M_AFTER_SUP_M"] = intval($arrRs["M_ID"]);
			$arFields["LAST_MESSAGE_BY_SUPPORT_TEAM"] = "'N'";
		}
				
		if( intval($arrRs["DATE_CLOSE"]) <= 0 && $arFields["LAST_MESSAGE_BY_SUPPORT_TEAM"] == "'N'")
		{
			$arrRs["ID"] =  $ticketID;
			CTicketReminder::RecalculateSupportDeadlineForOneTicket($arrRs, $arFields, $dateType);
		}
		else
		{
			$DB->Update("b_ticket", $arFields, "WHERE ID='" . $ticketID . "'", $err_mess . __LINE__);
		}
				
	}

	function UpdateMessages($ticketID)
	{
		$err_mess = (CAllTicket::err_mess())."<br>Function: UpdateMessages<br>Line: ";
		global $DB;
		$ticketID = intval($ticketID);
		if ($ticketID<=0) return;

		$arFields = array();

		// ��������� ���������� ���������
		$strSql = "
			SELECT
				SUM(CASE WHEN IS_HIDDEN='Y' THEN 0 ELSE 1 END) MESSAGES,
				SUM(TASK_TIME) ALL_TIME
			FROM
				b_ticket_message
			WHERE
				TICKET_ID = $ticketID
			and (IS_LOG='N' or IS_LOG is null or ".$DB->Length("IS_LOG")."<=0)
			and (IS_OVERDUE='N' or IS_OVERDUE is null or ".$DB->Length("IS_OVERDUE")."<=0)
			";
		$z = $DB->Query($strSql, false, $err_mess.__LINE__);
		$zr = $z->Fetch();
		$arFields["MESSAGES"] = intval($zr["MESSAGES"]);
		$arFields["PROBLEM_TIME"] = intval($zr["ALL_TIME"]);

		$DB->Update("b_ticket",$arFields,"WHERE ID='".$ticketID."'",$err_mess.__LINE__);
	}

	function GetFileList(&$by, &$order, $arFilter=array())
	{
		$err_mess = (CAllTicket::err_mess())."<br>Function: GetFileList<br>Line: ";
		global $DB, $USER;
		$arSqlSearch = Array();
		$strSqlSearch = "";
		if (is_array($arFilter))
		{
			$filter_keys = array_keys($arFilter);
			for ($i=0; $i<count($filter_keys); $i++)
			{
				$key = $filter_keys[$i];
				$val = $arFilter[$filter_keys[$i]];
				if ((is_array($val) && count($val)<=0) || (!is_array($val) && (strlen($val)<=0 || $val==='NOT_REF')))
					continue;
				$match_value_set = (in_array($key."_EXACT_MATCH", $filter_keys)) ? true : false;
				$key = strtoupper($key);
				switch($key)
				{
					case "LINK_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("MF.ID",$val,$match);
						break;
					case "MESSAGE":
					case "TICKET_ID":
					case "FILE_ID":
					case "HASH":
					case "MESSAGE_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("MF.".$key,$val,$match);
						break;
				}
			}
		}
		if ($by == "s_id")				$strSqlOrder = "ORDER BY MF.ID";
		elseif ($by == "s_file_id")		$strSqlOrder = "ORDER BY F.ID";
		elseif ($by == "s_message_id")	$strSqlOrder = "ORDER BY MF.MESSAGE_ID";
		else
		{
			$by = "s_id";
			$strSqlOrder = "ORDER BY MF.ID";
		}
		if ($order=="desc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}
		else
		{
			$strSqlOrder .= " asc ";
			$order="asc";
		}
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
			SELECT
				F.*, ".$DB->DateToCharFunction("F.TIMESTAMP_X")." as TIMESTAMP_X,
				MF.ID as LINK_ID,
				MF.HASH,
				MF.MESSAGE_ID,
				MF.TICKET_ID,
				MF.EXTENSION_SUFFIX
			FROM
				b_ticket_message_2_file MF
			INNER JOIN b_file F ON (MF.FILE_ID = F.ID)
			WHERE
				$strSqlSearch
			$strSqlOrder
		";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}

	function GetMessageByID($id, $checkRights="Y", $get_user_name="Y")
	{
		return CTicket::GetMessageList($by, $order, array("ID" => $id, "ID_EXACT_MATCH" => "Y"), $is_filtered, $checkRights, $get_user_name);
	}

	function GetByID($id, $lang=LANG, $checkRights="Y", $get_user_name="Y", $get_extra_names="Y", $arParams = Array())
	{
		return CTicket::GetList($by, $order, array("ID" => $id, "ID_EXACT_MATCH" => "Y"), $is_filtered, $checkRights, $get_user_name, $get_extra_names, $lang, $arParams);
	}

	function Delete($ticketID, $checkRights="Y")
	{
		$err_mess = (CAllTicket::err_mess())."<br>Function: Delete<br>Line: ";
		global $DB, $USER;
		$ticketID = intval($ticketID);
		if ($ticketID<=0) return;
		$bAdmin = "N";
		if ($checkRights=="Y")
		{
			$bAdmin = (CTicket::IsAdmin()) ? "Y" : "N";
		}
		else
		{
			$bAdmin = "Y";
		}
		if ($bAdmin=="Y")
		{
			if (CTicket::ExecuteEvents('OnBeforeTicketDelete', $ticketID, false) === false)
				return false;
			CTicket::ExecuteEvents('OnTicketDelete', $ticketID, false);

			$strSql = "
				SELECT
					F.ID
				FROM
					b_ticket_message_2_file MF,
					b_file F
				WHERE
					MF.TICKET_ID = '$ticketID'
				and F.ID=MF.FILE_ID
				";
			$z = $DB->Query($strSql, false, $err_mess.__LINE__);
			while ($zr = $z->Fetch()) CFile::Delete($zr["ID"]);

			//CTicketReminder::Delete($ticketID);
			$DB->Query("DELETE FROM b_ticket_message_2_file WHERE TICKET_ID='$ticketID'", false, $err_mess.__LINE__);
			$DB->Query("DELETE FROM b_ticket_search WHERE MESSAGE_ID IN(SELECT ID FROM b_ticket_message WHERE TICKET_ID = '$ticketID')", false, $err_mess.__LINE__);
			$DB->Query("DELETE FROM b_ticket_message WHERE TICKET_ID='$ticketID'", false, $err_mess.__LINE__);
			$GLOBALS["USER_FIELD_MANAGER"]->Delete("SUPPORT", $ticketID);
			$DB->Query("DELETE FROM b_ticket WHERE ID='$ticketID'", false, $err_mess.__LINE__);
		}
	}

	function UpdateOnline($ticketID, $userID=false, $currentMode="")
	{
		$err_mess = (CAllTicket::err_mess())."<br>Function: UpdateOnline<br>Line: ";
		global $DB, $USER;
		if ($userID===false && is_object($USER)) $userID = $USER->GetID();
		$ticketID = intval($ticketID);
		$userID = intval($userID);
		if ($ticketID<=0 || $userID<=0) return;
		$arFields = array(
			"TIMESTAMP_X"	=> $DB->GetNowFunction(),
			"TICKET_ID"		=> $ticketID,
			"USER_ID"		=> $userID,
			);
		if ($currentMode!==false)
		{
			$arFields["CURRENT_MODE"] = strlen($currentMode)>0 ? "'".$DB->ForSQL($currentMode, 20)."'" : "null";
		}
		$rows = $DB->Update("b_ticket_online", $arFields, "WHERE TICKET_ID=$ticketID and USER_ID=$userID", $err_mess.__LINE__);
		if (intval($rows)<=0)
		{
			$DB->Insert("b_ticket_online",$arFields, $err_mess.__LINE__);
		}
	}

	function SetTicket($arFields, $ticketID="", $checkRights="Y", $sendEmailToAuthor="Y", $sendEmailToTechsupport="Y")
	{
		//global $DB;
		//$DB->DebugToFile = true;
		$x = CTicket::Set($arFields, $messageID, $ticketID, $checkRights, $sendEmailToAuthor, $sendEmailToTechsupport);
		//$DB->DebugToFile = false;
		return $x;
	}
	
	/*****************************************************************
									SET
	*****************************************************************/
	
	static function addSupportText($cn)
	{
		if($cn > 0 && (CTicket::IsSupportTeam($cn) || CTicket::IsAdmin($cn))) return " " . GetMessage("SUP_TECHSUPPORT_HINT");
		return "";
	}
	
	static function EmailsFromStringToArray($emails, $res = null)
	{
		if(!is_array($res)) $res = array();
		$arEmails = explode(",", $emails);
		if(is_array($arEmails) && count($arEmails) > 0)
		{
			foreach($arEmails as $email)
			{
				$email = trim($email);
				if(strlen($email) > 0)
				{
					preg_match_all("#[<\[\(](.*?)[>\]\)]#i".BX_UTF_PCRE_MODIFIER, $email, $arr);
					if(is_array($arr[1]) && count($arr[1]) > 0)
					{
						foreach($arr[1] as $email)
						{
							$email = trim($email);
							if(strlen($email) > 0 && !in_array($email, $res) && check_email($email)) $res[] = $email;
						}
					}
					elseif(!in_array($email, $res) && check_email($email)) $res[] = $email;
				}
			}
		}
		TrimArr($res);
		return $res;
	}
	
	static function GetCSupportTableFields($name, $arrOrTable = CSupportTableFields::C_Array)
	{
		$n = CSupportTableFields::VT_NUMBER;
		$s = CSupportTableFields::VT_STRING;
		$yn = CSupportTableFields::VT_Y_N;
		$d = CSupportTableFields::VT_DATE;
		$dt = CSupportTableFields::VT_DATE_TIME;
		$tables = array(
			"b_ticket" => array(
				"ID" =>								array("TYPE" => $n,	"DEF_VAL" => 0,		"AUTO_CALCULATED" => true),
				"SITE_ID" =>						array("TYPE" => $s,	"DEF_VAL" => "", 	"MAX_STR_LEN" => 2),
				"DATE_CREATE" =>					array("TYPE" => $dt,	"DEF_VAL" => null	),
				"DAY_CREATE" =>						array("TYPE" => $d,	"DEF_VAL" => null	),
				"TIMESTAMP_X" =>					array("TYPE" => $dt,	"DEF_VAL" => null	),
				"DATE_CLOSE" =>						array("TYPE" => $dt,	"DEF_VAL" => null	),
				"AUTO_CLOSED" =>					array("TYPE" => $yn,	"DEF_VAL" => null	),
				"AUTO_CLOSE_DAYS" =>				array("TYPE" => $n,	"DEF_VAL" => null	),
				"SLA_ID" =>							array("TYPE" => $n,	"DEF_VAL" => 1		),
				"NOTIFY_AGENT_ID" =>				array("TYPE" => $n,	"DEF_VAL" => null	),
				"EXPIRE_AGENT_ID" =>				array("TYPE" => $n,	"DEF_VAL" => null	),
				"OVERDUE_MESSAGES" =>				array("TYPE" => $n,	"DEF_VAL" => 0		),
				"IS_NOTIFIED" =>					array("TYPE" => $yn,	"DEF_VAL" => "N"	),
				"IS_OVERDUE" =>						array("TYPE" => $yn,	"DEF_VAL" => "N"	),
				"CATEGORY_ID" =>					array("TYPE" => $n,	"DEF_VAL" => null	),
				"CRITICALITY_ID" =>					array("TYPE" => $n,	"DEF_VAL" => null	),
				"STATUS_ID" =>						array("TYPE" => $n,	"DEF_VAL" => null	),
				"MARK_ID" =>						array("TYPE" => $n,	"DEF_VAL" => null	),
				"SOURCE_ID" =>						array("TYPE" => $n,	"DEF_VAL" => null	),
				"DIFFICULTY_ID" =>					array("TYPE" => $n,	"DEF_VAL" => null	),
				"TITLE" =>							array("TYPE" => $s,	"DEF_VAL" => "", 	"MAX_STR_LEN" => 255),
				"MESSAGES" =>						array("TYPE" => $n,	"DEF_VAL" => 0		),
				"IS_SPAM" =>						array("TYPE" => $yn,	"DEF_VAL" => null	),
				"OWNER_USER_ID" =>					array("TYPE" => $n,	"DEF_VAL" => null	),
				"OWNER_GUEST_ID" =>					array("TYPE" => $n,	"DEF_VAL" => null	),
				"OWNER_SID" =>						array("TYPE" => $s,	"DEF_VAL" => null, 	"MAX_STR_LEN" => 255),
				"CREATED_USER_ID" =>				array("TYPE" => $n,	"DEF_VAL" => null	),
				"CREATED_GUEST_ID" =>				array("TYPE" => $n,	"DEF_VAL" => null	),
				"CREATED_MODULE_NAME" =>			array("TYPE" => $s,	"DEF_VAL" => "support", 	"MAX_STR_LEN" => 255),
				"RESPONSIBLE_USER_ID" =>			array("TYPE" => $n,	"DEF_VAL" => null	),
				"MODIFIED_USER_ID" =>				array("TYPE" => $n,	"DEF_VAL" => null	),
				"MODIFIED_GUEST_ID" =>				array("TYPE" => $n,	"DEF_VAL" => null	),
				"MODIFIED_MODULE_NAME" =>			array("TYPE" => $s,	"DEF_VAL" => null, 	"MAX_STR_LEN" => 255),
				"LAST_MESSAGE_USER_ID" =>			array("TYPE" => $n,	"DEF_VAL" => null	),
				"LAST_MESSAGE_GUEST_ID" =>			array("TYPE" => $n,	"DEF_VAL" => null	),
				"LAST_MESSAGE_SID" =>				array("TYPE" => $s,	"DEF_VAL" => null, 	"MAX_STR_LEN" => 255),
				"LAST_MESSAGE_BY_SUPPORT_TEAM" =>	array("TYPE" => $yn,	"DEF_VAL" => "N"	),
				"LAST_MESSAGE_DATE" =>				array("TYPE" => $dt,	"DEF_VAL" => null	),
				"SUPPORT_COMMENTS" =>				array("TYPE" => $s,	"DEF_VAL" => null, 	"MAX_STR_LEN" => 255),
				"PROBLEM_TIME" =>					array("TYPE" => $n,	"DEF_VAL" => null	),
				"HOLD_ON" =>						array("TYPE" => $yn,	"DEF_VAL" => "N"	),
				"REOPEN" =>							array("TYPE" => $yn,	"DEF_VAL" => "N"	),
				"COUPON" =>							array("TYPE" => $s,	"DEF_VAL" => null, 	"MAX_STR_LEN" => 255),
			),
			
			"EventFields" => array(
				"ID" =>								array("TYPE" => $n,	"DEF_VAL" => null	),
				"LANGUAGE" =>						array("TYPE" => $n,	"DEF_VAL" => null	),
				"LANGUAGE_ID" =>					array("TYPE" => $n,	"DEF_VAL" => null	),
				"WHAT_CHANGE" =>					array("TYPE" => $s,	"DEF_VAL" => null	),
				"DATE_CREATE" =>					array("TYPE" => $s,	"DEF_VAL" => null	),
				"TIMESTAMP" =>						array("TYPE" => $s,	"DEF_VAL" => null	),
				"DATE_CLOSE" =>						array("TYPE" => $s,	"DEF_VAL" => null	),
				"TITLE" =>							array("TYPE" => $s,	"DEF_VAL" => null	),
				"STATUS" =>							array("TYPE" => $s,	"DEF_VAL" => null	),
				"DIFFICULTY" =>						array("TYPE" => $s,	"DEF_VAL" => null	),
				"CATEGORY" =>						array("TYPE" => $s,	"DEF_VAL" => null	),
				"CRITICALITY" =>					array("TYPE" => $s,	"DEF_VAL" => null	),
				"RATE" =>							array("TYPE" => $s,	"DEF_VAL" => null	),
				"SLA" =>							array("TYPE" => $s,	"DEF_VAL" => null	),
				"SOURCE" =>							array("TYPE" => $s,	"DEF_VAL" => null	),
				"MESSAGES_AMOUNT" =>				array("TYPE" => $s,	"DEF_VAL" => null	),
				"SPAM_MARK" =>						array("TYPE" => $s,	"DEF_VAL" => null	),
				"ADMIN_EDIT_URL" =>					array("TYPE" => $s,	"DEF_VAL" => null	),
				"PUBLIC_EDIT_URL" =>				array("TYPE" => $s,	"DEF_VAL" => null	),
				"OWNER_EMAIL" =>					array("TYPE" => $s,	"DEF_VAL" => null	),
				"OWNER_USER_ID" =>					array("TYPE" => $s,	"DEF_VAL" => null	),
				"OWNER_USER_NAME" =>				array("TYPE" => $s,	"DEF_VAL" => null	),
				"OWNER_USER_LOGIN" =>				array("TYPE" => $s,	"DEF_VAL" => null	),
				"OWNER_USER_EMAIL" =>				array("TYPE" => $s,	"DEF_VAL" => null	),
				"OWNER_TEXT" =>						array("TYPE" => $s,	"DEF_VAL" => null	),
				"OWNER_SID" =>						array("TYPE" => $s,	"DEF_VAL" => null	),
				"SUPPORT_EMAIL" =>					array("TYPE" => $s,	"DEF_VAL" => null	),
				"RESPONSIBLE_USER_ID" =>			array("TYPE" => $n,	"DEF_VAL" => null	),
				"RESPONSIBLE_USER_NAME" =>			array("TYPE" => $s,	"DEF_VAL" => null	),
				"RESPONSIBLE_USER_LOGIN" =>			array("TYPE" => $s,	"DEF_VAL" => null	),
				"RESPONSIBLE_USER_EMAIL" =>			array("TYPE" => $s,	"DEF_VAL" => null	),
				"RESPONSIBLE_TEXT" =>				array("TYPE" => $s,	"DEF_VAL" => null	),
				"SUPPORT_ADMIN_EMAIL" =>			array("TYPE" => $s,	"DEF_VAL" => null	),
				"CREATED_USER_ID" =>				array("TYPE" => $n,	"DEF_VAL" => null	),
				"CREATED_USER_LOGIN" =>				array("TYPE" => $s,	"DEF_VAL" => null	),
				"CREATED_USER_EMAIL" =>				array("TYPE" => $s,	"DEF_VAL" => null	),
				"CREATED_USER_NAME" =>				array("TYPE" => $s,	"DEF_VAL" => null	),
				"CREATED_MODULE_NAME" =>			array("TYPE" => $s,	"DEF_VAL" => null	),
				"CREATED_TEXT" =>					array("TYPE" => $s,	"DEF_VAL" => null	),
				"MODIFIED_USER_ID" =>				array("TYPE" => $n,	"DEF_VAL" => null	),
				"MODIFIED_USER_LOGIN" =>			array("TYPE" => $s,	"DEF_VAL" => null	),
				"MODIFIED_USER_EMAIL" =>			array("TYPE" => $s,	"DEF_VAL" => null	),
				"MODIFIED_USER_NAME" =>				array("TYPE" => $s,	"DEF_VAL" => null	),
				"MODIFIED_MODULE_NAME" =>			array("TYPE" => $s,	"DEF_VAL" => null	),
				"MODIFIED_TEXT" =>					array("TYPE" => $s,	"DEF_VAL" => null	),
				"MESSAGE_AUTHOR_USER_ID" =>			array("TYPE" => $n,	"DEF_VAL" => null	),
				"MESSAGE_AUTHOR_USER_NAME" =>		array("TYPE" => $s,	"DEF_VAL" => null	),
				"MESSAGE_AUTHOR_USER_LOGIN" =>		array("TYPE" => $s,	"DEF_VAL" => null	),
				"MESSAGE_AUTHOR_USER_EMAIL" =>		array("TYPE" => $s,	"DEF_VAL" => null	),
				"MESSAGE_AUTHOR_TEXT" =>			array("TYPE" => $s,	"DEF_VAL" => null	),
				"MESSAGE_AUTHOR_SID" =>				array("TYPE" => $s,	"DEF_VAL" => null	),
				"MESSAGE_SOURCE" =>					array("TYPE" => $s,	"DEF_VAL" => null	),
				"MESSAGE_HEADER" =>					array("TYPE" => $s,	"DEF_VAL" => null	),
				"MESSAGE_BODY" =>					array("TYPE" => $s,	"DEF_VAL" => null	),
				"MESSAGE_FOOTER" =>					array("TYPE" => $s,	"DEF_VAL" => null	),
				"FILES_LINKS" =>					array("TYPE" => $s,	"DEF_VAL" => null	),
				"IMAGE_LINK" =>						array("TYPE" => $s,	"DEF_VAL" => null	),
				"SUPPORT_COMMENTS" =>				array("TYPE" => $s,	"DEF_VAL" => null	),
			),
			
		);
		
		if(!array_key_exists($name, $tables)) return null;
		
		return new CSupportTableFields($tables[$name], $arrOrTable);
	}
		
	function Set_getFilesLinks($arFiles, $lID)
	{
		// ���������� ������ �� ������������ �����
		$fl = null;
		if(is_array($arFiles) && count($arFiles) > 0)
		{
			$fl = GetMessage("SUP_ATTACHED_FILES")."\n";
			foreach($arFiles as $arFile)
			{
				$fl .= (CMain::IsHTTPS()? "https" : "http")."://" . $_SERVER["HTTP_HOST"] . "/bitrix/tools/ticket_show_file.php?hash=" . $arFile["HASH"] . "&action=download&lang=" . $lID . "\n";
			}
			if (strlen($fl) > 0) $fl .= "\n";
		}
		return $fl;
	}
	
	function Set_WriteLog($nf, $v, $mf)
	{
		
		$change_log = "";
		$v->change = "";
		$v->change_hidden = "";
				
		if($v->isNew) // NEW
		{
			$v->arChange = array(); 
			if(strlen($nf->SLA_NAME) > 0)			$v->arChange["SLA_ID"] = "Y";
			if(strlen($nf->CATEGORY_NAME) > 0)		$v->arChange["CATEGORY_ID"] = "Y";
			if(strlen($nf->CRITICALITY_NAME) > 0)	$v->arChange["CRITICALITY_ID"] = "Y";				
			if(strlen($nf->STATUS_NAME) > 0)			$v->arChange["STATUS_ID"] = "Y";			
			if(strlen($nf->DIFFICULTY_NAME) > 0)		$v->arChange["DIFFICULTY_ID"] = "Y";
			if(strlen($mf->RESPONSIBLE_TEXT) > 0)	$v->arChange["RESPONSIBLE_USER_ID"] = "Y";
			if($v->bActiveCoupon) $change_log .= "<li>" . htmlspecialcharsEx(GetMessage('SUP_IS_SUPER_COUPON', array('#COUPON#' => $v->V_COUPON)));
		}
		
		if(!is_array($v->arChange) || count($v->arChange) <= 0) return;
				
		foreach($v->arChange as $key => $value)
		{
			if ($value != "Y") continue;
			
			switch ($key)
			{
				case "CLOSE":
					$v->change .= GetMessage("SUP_REQUEST_CLOSED")."\n";
					$change_log .= "<li>".GetMessage("SUP_REQUEST_CLOSED_LOG");
					break;
				case "OPEN":
					$v->change .= GetMessage("SUP_REQUEST_OPENED")."\n";
					$change_log .= "<li>".GetMessage("SUP_REQUEST_OPENED_LOG");
					break;
					
				case "HOLD_ON_ON":
					$v->change .= GetMessage("SUP_HOLD_ON_ON") . "\n";
					$change_log .= "<li>" . GetMessage("SUP_HOLD_ON_ON_LOG");
					break;
				case "HOLD_ON_OFF":
					$v->change .= GetMessage("SUP_HOLD_ON_OFF") . "\n";
					$change_log .= "<li>" . GetMessage("SUP_HOLD_ON_OFF_LOG");
					break;
					
				case "RESPONSIBLE_USER_ID":
					$v->change .= GetMessage("SUP_RESPONSIBLE_CHANGED") . "\n";
					$change_log .= "<li>" . htmlspecialcharsEx(GetMessage("SUP_RESPONSIBLE_CHANGED_LOG", array("#VALUE#" => $mf->RESPONSIBLE_TEXT)));
					break;
				case "CATEGORY_ID":
					$v->change .= GetMessage("SUP_CATEGORY_CHANGED") . "\n";
					$change_log .= "<li>" . htmlspecialcharsEx(GetMessage("SUP_CATEGORY_CHANGED_LOG", array("#VALUE#" => $nf->CATEGORY_NAME)));
					break;
				case "CRITICALITY_ID":
					$v->change .= GetMessage("SUP_CRITICALITY_CHANGED") . "\n";
					$change_log .= "<li>" . htmlspecialcharsEx(GetMessage("SUP_CRITICALITY_CHANGED_LOG", array("#VALUE#" => $nf->CRITICALITY_NAME)));
					break;
				case "STATUS_ID":
					$v->change .= GetMessage("SUP_STATUS_CHANGED") . "\n";
					$change_log .= "<li>" . htmlspecialcharsEx(GetMessage("SUP_STATUS_CHANGED_LOG", array("#VALUE#" => $nf->STATUS_NAME)));
					break;
				case "DIFFICULTY_ID":
					$v->change_hidden .= GetMessage("SUP_DIFFICULTY_CHANGED") . "\n";
					$change_log .= "<li>" . htmlspecialcharsEx(GetMessage("SUP_DIFFICULTY_CHANGED_LOG", array("#VALUE#" => $nf->DIFFICULTY_NAME)));
					break;
				case "MARK_ID":
					$v->change .= GetMessage("SUP_MARK_CHANGED") . "\n";
					$change_log .= "<li>" . htmlspecialcharsEx(GetMessage("SUP_MARK_CHANGED_LOG", array("#VALUE#" => $nf->MARK_NAME)));
					break;
				case "SLA_ID":
					$v->change .= GetMessage("SUP_SLA_CHANGED") . "\n";
					$change_log .= "<li>" . htmlspecialcharsEx(GetMessage("SUP_SLA_CHANGED_LOG", array("#VALUE#" => $nf->SLA_NAME)));
					break;
				case "MESSAGE":
					$v->change .= GetMessage("SUP_NEW_MESSAGE") . "\n";
					break;
				case "HIDDEN_MESSAGE":
					$v->change_hidden .= GetMessage("SUP_NEW_HIDDEN_MESSAGE")."\n";
					$line1 = str_repeat("=", 20);
					$line2 = str_repeat("=", 30);
					$mf->MESSAGE_HEADER = $line1 . " " . GetMessage("SUP_MAIL_HIDDEN_MESSAGE") . " " . $line2;
					break;
			}
		}

		if(!$v->isNew) $mf->WHAT_CHANGE = $v->change; // UPDATE
				
		// ������� ��������� � ���
		if(strlen($change_log) > 0)
		{
			$arFields_log = $v->arFields_log;
			$arFields_log["MESSAGE"] = $change_log;
			$q = null;
			$arFields_log["IS_LOG"] = "Y";
			CTicket::AddMessage($nf->ID, $arFields_log, $q, "N", $v->newSLA);
		}
			
	}
	
	function Set_sendMails($nf, $v, $arFields)
	{
		$I_Email = null;
		$U_Email = null;
		if(!$v->isNew) $U_Email = "Y"; // UPDATE
		else $I_Email = "Y";
		
		IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/support/classes/general/messages.php", $v->arrSite["LANGUAGE_ID"]);
					
		$mf = self::GetCSupportTableFields("EventFields");
		
		$mf->ADMIN_EDIT_URL = "/bitrix/admin/ticket_edit.php";
		$mf->LANGUAGE = $v->arrSite["LANGUAGE_ID"];
		$mf->LANGUAGE_ID = $v->arrSite["LANGUAGE_ID"];
		
		$arrSet = array(
			"ID"						=> "ID",
			"DATE_CREATE"				=> "DATE_CREATE",
			"TIMESTAMP"					=> "TIMESTAMP_X",
			"DATE_CLOSE"				=> "DATE_CLOSE",
			"TITLE"						=> "TITLE",
			"CATEGORY"					=> "CATEGORY_NAME",
			"CRITICALITY"				=> "CRITICALITY_NAME",
			"DIFFICULTY"				=> "DIFFICULTY_NAME",
			"STATUS"					=> "STATUS_NAME",
			"SLA"						=> "SLA_NAME",
			"OWNER_USER_ID"				=> "OWNER_USER_ID",
			"OWNER_USER_NAME"			=> "OWNER_NAME",
			"OWNER_USER_LOGIN"			=> "OWNER_LOGIN",
			"OWNER_USER_EMAIL"			=> "OWNER_EMAIL",
			"OWNER_SID"					=> "OWNER_SID",
			"RESPONSIBLE_USER_ID"		=> "RESPONSIBLE_USER_ID",
			"RESPONSIBLE_USER_NAME"		=> "RESPONSIBLE_NAME",
			"RESPONSIBLE_USER_LOGIN"	=> "RESPONSIBLE_LOGIN",
			"RESPONSIBLE_USER_EMAIL"	=> "RESPONSIBLE_EMAIL",
			"CREATED_USER_ID"			=> "CREATED_USER_ID",
			"CREATED_USER_LOGIN"		=> "CREATED_LOGIN",
			"CREATED_USER_EMAIL"		=> "CREATED_EMAIL",
			"CREATED_USER_NAME"			=> "CREATED_NAME"
		);
		
		if(!$v->isNew) // UPDATE
		{
			$arrSet["MODIFIED_USER_ID"]			= "MODIFIED_USER_ID";
			$arrSet["MODIFIED_USER_LOGIN"]		= "MODIFIED_LOGIN";
			$arrSet["MODIFIED_USER_EMAIL"]		= "MODIFIED_EMAIL";
			$arrSet["MODIFIED_USER_NAME"]		= "MODIFIED_NAME";
			$arrSet["RATE"]						= "MARK_NAME";
			$arrSet["MESSAGES_AMOUNT"]			= "MESSAGES";
		}
						
		
		$mf->FromArray((array)$nf, $arrSet);
		
		$mf->FILES_LINKS = self::Set_getFilesLinks($v->arrFILES, $v->arrSite["LANGUAGE_ID"]);
		$mf->IMAGE_LINK = $mf->FILES_LINKS;
		
		$mf->MESSAGE_BODY = PrepareTxtForEmail($arFields["MESSAGE"], $v->arrSite["LANGUAGE_ID"], false, false);
		if(strlen($mf->MESSAGE_BODY) > 0) $mf->MESSAGE_BODY = (strlen($mf->FILES_LINKS) > 0 ? "\n" : "\n\n") . $mf->MESSAGE_BODY . "\n";
					
		// ���������� email ������
		// �������: "TICKET_NEW_FOR_AUTHOR"					- "EMAIL_TO" = "OWNER_EMAIL"
		// �������: "TICKET_CHANGE_BY_AUTHOR_FOR_AUTHOR"	- "EMAIL_TO" = "OWNER_EMAIL"
		// �������: "TICKET_CHANGE_BY_SUPPORT_FOR_AUTHOR"	- "EMAIL_TO" = "OWNER_EMAIL"
		$arrOwnerEmails = self::EmailsFromStringToArray($mf->OWNER_SID,  array($nf->OWNER_EMAIL));
		if(intval($mf->OWNER_USER_ID) > 0)
		{
			$rs = CTicket::GetResponsibleList($mf->OWNER_USER_ID, $I_Email, $U_Email);
			while($arr0 = $rs->Fetch()) if(strlen($arr0['EMAIL']) > 0) $arrOwnerEmails[] = $arr0['EMAIL'];
		}
		$mf->OWNER_EMAIL = implode(", ", array_unique($arrOwnerEmails));
		
		// �������� ���������������� email'�
		$arrAdminEMails = CTicket::GetAdminEmails();
		if(!is_array($arrAdminEMails)) $arrAdminEMails = array();
		TrimArr($arrAdminEMails);
	
		// ���������� email ������������
		// �������: "TICKET_NEW_FOR_TECHSUPPORT"	- "EMAIL_TO" = "SUPPORT_EMAIL"
		// �������: "TICKET_CHANGE_FOR_TECHSUPPORT"	- "EMAIL_TO" = "SUPPORT_EMAIL"
		$arrSupportEmails = array();
		if(strlen($nf->RESPONSIBLE_EMAIL) > 0) $arrSupportEmails[] = $nf->RESPONSIBLE_EMAIL;
		if(count($arrSupportEmails) <= 0) $arrSupportEmails = $arrAdminEMails;
		if(count($arrSupportEmails) <= 0)
		{
			$se = COption::GetOptionString("main", "email_from", "");
			if(strlen($se) > 0) $arrSupportEmails[] = $se;
		}
				
		// �� ������ �������������, �������� ���� ������������� ��� ����� ������ � ��������
		if($mf->RESPONSIBLE_USER_ID)
		{
			$rs = CTicket::GetResponsibleList($mf->RESPONSIBLE_USER_ID, $I_Email, $U_Email, "Y");
			while($arr0 = $rs->Fetch()) if(strlen($arr0['EMAIL']) > 0) $arrSupportEmails[] = $arr0['EMAIL'];
		}
		TrimArr($arrSupportEmails);
		$mf->SUPPORT_EMAIL = count($arrSupportEmails) > 0 ? TrimEx(implode(",", array_unique($arrSupportEmails)), ",") : "";
		
		// ������ ���������������� ������ �� ������� #SUPPORT_ADMIN_EMAIL#
		if(count($arrSupportEmails) > 0) foreach($arrSupportEmails as $e) unset($arrAdminEMails[$e]);
		$mf->SUPPORT_ADMIN_EMAIL = count($arrAdminEMails) > 0 ? TrimEx(implode(",", $arrAdminEMails), ",") : "";
	
		if(array_key_exists('PUBLIC_EDIT_URL', $arFields) && strlen($arFields['PUBLIC_EDIT_URL']) > 0)
		{
			$mf->PUBLIC_EDIT_URL = $arFields['PUBLIC_EDIT_URL'];
		}
		else
		{
			$peurl = COption::GetOptionString("support", "SUPPORT_DIR");
			$peurl = str_replace("#LANG_DIR#", $v->arrSite["DIR"], $peurl); // �������������
			$peurl = str_replace("#SITE_DIR#", $v->arrSite["DIR"], $peurl);
			$peurl = str_replace("\\", "/", $peurl);
			$peurl = str_replace("//", "/", $peurl);
			$peurl = TrimEx($peurl, "/");
			$mf->PUBLIC_EDIT_URL = "/".$peurl."/".COption::GetOptionString("support", "SUPPORT_EDIT");
		}
		
		$mf->SUPPORT_COMMENTS = PrepareTxtForEmail($arFields["SUPPORT_COMMENTS"], $v->arrSite["LANGUAGE_ID"]);
		if(strlen($mf->SUPPORT_COMMENTS) > 0) $mf->SUPPORT_COMMENTS = "\n\n" . $mf->SUPPORT_COMMENTS . "\n";
		
		$mf->SOURCE = strlen($nf->SOURCE_NAME) <= 0 ? "" : "[" . $nf->SOURCE_NAME . "] ";
		if($mf->OWNER_USER_ID > 0 || strlen(trim($mf->OWNER_USER_LOGIN)) > 0)
		{
			$mf->OWNER_TEXT = "[" . $mf->OWNER_USER_ID . "] (" . $mf->OWNER_USER_LOGIN . ") " . $mf->OWNER_USER_NAME;
			if(strlen(trim($mf->OWNER_SID)) > 0 && $mf->OWNER_SID != null) $mf->OWNER_TEXT = " / " . $mf->OWNER_TEXT;
			$mf->OWNER_TEXT .= self::addSupportText($mf->OWNER_USER_ID);
		}
		
		if($nf->CREATED_MODULE_NAME == "support")
		{
			$mf->CREATED_MODULE_NAME = "";
			if($mf->CREATED_USER_ID > 0) $mf->CREATED_TEXT = "[" . $mf->CREATED_USER_ID . "] (" . $mf->CREATED_USER_LOGIN . ") " . $mf->CREATED_USER_NAME . self::addSupportText($mf->CREATED_USER_ID);
		}
		else $mf->CREATED_MODULE_NAME = "[" . $nf->CREATED_MODULE_NAME . "]";
		
		if(!$v->isNew) // UPDATE
		{
			if($nf->MODIFIED_MODULE_NAME == "support" && strlen($nf->MODIFIED_MODULE_NAME) > 0)
			{
				$mf->MODIFIED_MODULE_NAME = "";
				if($mf->MODIFIED_USER_ID > 0)
				{
					$mf->MODIFIED_TEXT = "[" . $mf->MODIFIED_USER_ID . "] (" . $mf->MODIFIED_USER_LOGIN . ") " . $mf->MODIFIED_USER_NAME;
					$mf->MODIFIED_TEXT .= self::addSupportText($mf->MODIFIED_USER_ID);
				}
			}
			else $mf->MODIFIED_MODULE_NAME = "[" . $nf->MODIFIED_MODULE_NAME . "]";
			
			
			$mf->MESSAGE_SOURCE = "";
			if($rsSource = CTicketDictionary::GetByID($arFields["MESSAGE_SOURCE_ID"]))
			{
				$arSource = $rsSource->Fetch();
				$mf->MESSAGE_SOURCE = (array_key_exists("NAME", $arSource) && strlen($arSource["NAME"]) > 0) ? "[" . $arSource["NAME"] . "] " : "";
			}

			if((strlen(trim($arFields["MESSAGE_AUTHOR_SID"])) > 0 || intval($arFields["MESSAGE_AUTHOR_USER_ID"]) > 0) && $v->bSupportTeam)
			{
				$mf->MESSAGE_AUTHOR_USER_ID = $arFields["MESSAGE_AUTHOR_USER_ID"];
				$mf->MESSAGE_AUTHOR_SID = $arFields["MESSAGE_AUTHOR_SID"];
			}
			else $mf->MESSAGE_AUTHOR_USER_ID = $v->uid;
			
			$arMA = array();
			if($rsMA = CUser::GetByID($mf->MESSAGE_AUTHOR_USER_ID)) $arMA = $rsMA->Fetch();
			
			if($mf->MESSAGE_AUTHOR_USER_ID > 0 || strlen(trim($arMA["LOGIN"])) > 0)
			{
				$mf->MESSAGE_AUTHOR_TEXT = "[" . $mf->MESSAGE_AUTHOR_USER_ID . "] (" . $arMA["LOGIN"] . ") " . $arMA["NAME"] . " " . $arMA["LAST_NAME"];
				if(strlen(trim($arFields["MESSAGE_AUTHOR_SID"])) > 0) $mf->MESSAGE_AUTHOR_TEXT = " / " . $mf->MESSAGE_AUTHOR_TEXT;
				if($mf->MESSAGE_AUTHOR_USER_ID > 0) $mf->MESSAGE_AUTHOR_TEXT .= self::addSupportText($mf->MESSAGE_AUTHOR_USER_ID);
			}
			
			if(strlen(trim($arMA["NAME"])) > 0 || strlen(trim($arMA["LAST_NAME"])) > 0) $mf->MESSAGE_AUTHOR_USER_NAME	= trim($arMA["NAME"]) . " ". trim($arMA["LAST_NAME"]);
			if(strlen(trim($arMA["LOGIN"])) > 0) $mf->MESSAGE_AUTHOR_USER_LOGIN	= $arMA["LOGIN"];
			if(strlen(trim($arMA["EMAIL"])) > 0) $mf->MESSAGE_AUTHOR_USER_EMAIL	= $arMA["EMAIL"];
			
			$mf->MESSAGE_HEADER = str_repeat("=", 23) . " " . GetMessage("SUP_MAIL_MESSAGE") . " " . str_repeat("=", 34);
			
		
		}
	
		if($mf->RESPONSIBLE_USER_ID > 0) 
		{
			$mf->RESPONSIBLE_TEXT = "[" . $mf->RESPONSIBLE_USER_ID . "] (" . $nf->RESPONSIBLE_LOGIN . ") " . $nf->RESPONSIBLE_NAME;
			$mf->RESPONSIBLE_TEXT .= self::addSupportText($mf->RESPONSIBLE_USER_ID);
		}
		
		$mf->SPAM_MARK = "";
		if(strlen($nf->IS_SPAM) > 0)
		{
			if($nf->IS_SPAM == "Y") $mf->SPAM_MARK = "\n" . GetMessage("SUP_EXACTLY_SPAM") . "\n";
			else $mf->SPAM_MARK = "\n" . GetMessage("SUP_POSSIBLE_SPAM") . "\n";
		}
		
		self::Set_WriteLog($nf, $v, $mf);
		//$v  +change, +change_hidden
				
		if(!$v->isNew) // UPDATE
		{
			$mf->MESSAGE_FOOTER = str_repeat("=", strlen($mf->MESSAGE_HEADER));
		}
		
		if ($v->isNew && $v->bActiveCoupon) $mf->COUPON = $v->V_COUPON;
		
		$arEventFields_author = $mf->ToArray(CSupportTableFields::ALL, array(CSupportTableFields::NOT_NULL));
		$arEventFields_support = $arEventFields_author;
		
		// �������� ������ ������
		if($v->SEND_EMAIL_TO_AUTHOR == "Y" && ($v->isNew || strlen($v->change) > 0))
		{
			$EventType = "TICKET_NEW_FOR_AUTHOR";
			if(!$v->isNew) // UPDATE
			{
				// HIDDEN
				if($arFields["HIDDEN"] == "Y")
				{
					$arrUnsetHidden = array("MESSAGE_BODY", "IMAGE_LINK");
					foreach($arrUnsetHidden as $value) $arEventFields_author[$value] = "";
				}
				$EventType = "TICKET_CHANGE_BY_AUTHOR_FOR_AUTHOR";
				if(CTicket::IsSupportTeam($mf->MESSAGE_AUTHOR_USER_ID) || CTicket::IsAdmin($mf->MESSAGE_AUTHOR_USER_ID)) 
					$EventType = "TICKET_CHANGE_BY_SUPPORT_FOR_AUTHOR";
			}
			$arEventFields_author = CTicket::ExecuteEvents('OnBeforeSendMailToAuthor' , $arEventFields_author, $v->isNew);
			if ($arEventFields_author) CEvent::Send($EventType, $v->arrSite["ID"], $arEventFields_author);
		}
				
		// �������� ������ ������������
		if($v->SEND_EMAIL_TO_TECHSUPPORT == "Y" && ($v->isNew || strlen($v->change) > 0 || strlen($v->change_hidden) > 0))
		{
			$EventType = "TICKET_NEW_FOR_TECHSUPPORT";
			if(!$v->isNew) // UPDATE
			{
				$arEventFields_support["WHAT_CHANGE"] .= $v->change_hidden;
				$EventType = "TICKET_CHANGE_FOR_TECHSUPPORT";
			}
			$arEventFields_support = CTicket::ExecuteEvents('OnBeforeSendMailToSupport', $arEventFields_support, $v->isNew);
			if ($arEventFields_support) CEvent::Send($EventType, $v->arrSite["ID"], $arEventFields_support);
		}
		
		
	}
	
	function Set_getResponsibleUser($v, $f, &$arFields)
	{
		global $DB;
		$err_mess = (CAllTicket::err_mess()) . "<br>Function: Set_getResponsibleUser<br>Line: ";
		
		// ���� ��������� ��������� ����������� ������������, ��������������� ��� ���� �������������
		$f->RESPONSIBLE_USER_ID = null;
		if($v->bSupportTeam || $v->bAdmin || $v->Demo) $f->FromArray($arFields, "RESPONSIBLE_USER_ID", array(CSupportTableFields::MORE0));
		if($f->RESPONSIBLE_USER_ID == null) unset($arFields["RESPONSIBLE_USER_ID"]);
			
		/*
		������� �������������� ������� � �������������� � ����������� ��
			1) ���������
			2) �����������
			3) ���������
		*/
		$strSql = "
			SELECT ID, C_TYPE, RESPONSIBLE_USER_ID, EVENT1, EVENT2, EVENT3
			FROM b_ticket_dictionary
			WHERE
				(ID=" . $f->CATEGORY_ID		. " AND C_TYPE='C') OR
				(ID=" . $f->CRITICALITY_ID	. " AND C_TYPE='K') OR
				(ID=" . $f->SOURCE_ID		. " AND C_TYPE='SR')
			ORDER BY
				C_TYPE
		";
		$z = $DB->Query($strSql, false, $err_mess . __LINE__);
		$v->category_set = false;
		while($zr = $z->Fetch())
		{
			// ����
			//    1) ������������� ��������� � �����������
			//    2) �� ��� ��� �� �� ��� ���������
			//    3) �� ��� ����� ���� ������������� ������� �� ��� �����
			if ($zr["C_TYPE"]=="C")
			{
				$v->T_EVENT1 = trim($zr["EVENT1"]);
				$v->T_EVENT2 = trim($zr["EVENT2"]);
				$v->T_EVENT3 = trim($zr["EVENT3"]);
				$v->category_set = true;
			}
			if($f->RESPONSIBLE_USER_ID == null && intval($zr["RESPONSIBLE_USER_ID"]) > 0)
			{
				$RU_ID = intval($zr["RESPONSIBLE_USER_ID"]);
				if(CTicket::IsSupportTeam($RU_ID) || CTicket::IsAdmin($RU_ID)) $f->RESPONSIBLE_USER_ID = $RU_ID;
				break;
			}
		}
		
		
		// ���� ������������� ���� �� ��������� ��
		if($f->RESPONSIBLE_USER_ID == null)
		{
			// ������������� �� �������� SLA
			$rsSLA = CTicketSLA::GetByID($f->SLA_ID);
			if($arSLA = $rsSLA->Fetch()) if(intval($arSLA["RESPONSIBLE_USER_ID"]) > 0) $f->RESPONSIBLE_USER_ID = $arSLA["RESPONSIBLE_USER_ID"];
		}
		
		// ������������� �� �������� ������
		if ($f->RESPONSIBLE_USER_ID == null)
		{
			// ����� �� �������� ������ �������������� �� ���������
			$RU_ID = intval(COption::GetOptionString("support", "DEFAULT_RESPONSIBLE_ID"));
			if($f->RESPONSIBLE_USER_ID > 0) $f->RESPONSIBLE_USER_ID = $RU_ID;
		}
		
		
	}
	
	function Set_getCOUPONandSLA($v, $f, $arFields)
	{
		global $APPLICATION;
		// ��������� ������
		if(array_key_exists('COUPON', $arFields) && strlen($arFields['COUPON']) > 0)
		{
			$v->bActiveCoupon = CSupportSuperCoupon::UseCoupon($arFields['COUPON']);
			if($v->bActiveCoupon)
			{
				$v->V_COUPON = $arFields['COUPON'];
				$rsCoupon = CSupportSuperCoupon::GetList(false, array('COUPON' => $arFields['COUPON']));
				if($arCoupon = $rsCoupon->Fetch() && intval($arCoupon['SLA_ID']) > 0) $arFields['SLA_ID'] = intval($arCoupon['SLA_ID']);
			}
			else
			{
					$APPLICATION->ThrowException(GetMessage('SUP_ERROR_INVALID_COUPON'));
					return false;
			}
		}
		// �������� SLA
		if($v->bSupportTeam || $v->bAdmin || $v->bDemo || $v->bActiveCoupon) $f->FromArray($arFields, "SLA_ID", array(CSupportTableFields::MORE0));
		//elseif(intval($arFields["SLA_ID"]) <= 0) $f->SLA_ID = CTicketSLA::GetForUser($f->SITE_ID, $f->OWNER_USER_ID);
		else $f->SLA_ID = CTicketSLA::GetSLA($f->SITE_ID, $f->OWNER_USER_ID, $f->CATEGORY_ID, ($v->bActiveCoupon ? $v->V_COUPON : "") );
		
		return true;			
	}
	
	function Set_InitVar(&$arFields, $id, $checkRights, $sendEmailToAuthor, $sendEmailToTechsupport)
	{
		global $APPLICATION, $USER, $DB;
				
		$f = self::GetCSupportTableFields("b_ticket");
		$v = (object)array();
		
		if(!is_object($USER)) $USER = new CUser;
		
		$f->ID = intval($id);	
		$v->isNew = ($f->ID <= 0);
		
		$v->CHECK_RIGHTS = ($checkRights == "Y") ? "Y" : "N";
		$v->SEND_EMAIL_TO_AUTHOR = ($sendEmailToAuthor == "Y") ? "Y" : "N";
		$v->SEND_EMAIL_TO_TECHSUPPORT = ($sendEmailToTechsupport == "Y") ? "Y" : "N";
		
		$v->newSLA = false;
		
		// ��������� � ��������� - ������������ ���� ��� ������ ���������
		if($v->isNew)
		{
			if(strlen($arFields["TITLE"]) <= 0)
			{
				$APPLICATION->ThrowException(GetMessage('SUP_ERROR_EMPTY_TITLE'));
				return false;
			}

			if(strlen($arFields["MESSAGE"]) <= 0)
			{
				$APPLICATION->ThrowException(GetMessage('SUP_ERROR_EMPTY_MESSAGE'));
				return false;
			}
		}
		
		if(is_object($APPLICATION))
		{
			$APPLICATION->ResetException();
		}
		if(!$GLOBALS["USER_FIELD_MANAGER"]->CheckFields("SUPPORT", $f->ID, $arFields))
		{
			if(is_object($APPLICATION) && $APPLICATION->GetException())
			{
				return false;
			}
			else 
			{
				$APPLICATION->ThrowException("Unknown error. ");
				return false;
			}
		}
		
		// ��������� ����
		$v->bAdmin = $v->bSupportTeam = $v->bSupportClient = $v->bDemo = $v->bOwner = false;
		if($v->CHECK_RIGHTS == "Y")
		{
			$v->bAdmin = CTicket::IsAdmin();
			$v->bSupportTeam = CTicket::IsSupportTeam();
			$v->bSupportClient = CTicket::IsSupportClient();
			$v->bDemo = CTicket::IsDemo();
			$v->uid = intval($USER->GetID());
			if($v->isNew) $v->bOwner = true;
			else $v->bOwner = CTicket::IsOwner($f->ID, $v->uid);
		}
		else
		{
			$v->bAdmin = $v->bSupportTeam = $v->bSupportClient = $v->bDemo = $v->bOwner = true;
			$v->uid = 0;
		}
		if(!$v->bAdmin && !$v->bSupportTeam && !$v->bSupportClient) return false;
		
		// ��� ����?
		$f->FromArray($arFields, "IS_SPAM");
		
		$v->bActiveCoupon = false;
				
		$f->FromArray($_SESSION, array("MODIFIED_GUEST_ID" => "SESS_GUEST_ID"), array(CSupportTableFields::MORE0));
		$f->FromArray($arFields, "OWNER_USER_ID,OWNER_SID,HOLD_ON", array(CSupportTableFields::MORE0, CSupportTableFields::NOT_EMTY_STR));
		
		// ������� SITE_ID
		if(strlen($arFields["SITE_ID"]) > 0) $f->SITE_ID = $arFields["SITE_ID"];
		elseif(strlen($arFields["SITE"]) > 0) $f->SITE_ID = $arFields["SITE"];
		elseif(strlen($arFields["LANG"]) > 0) $f->SITE_ID = $arFields["LANG"];  // ������������� �� ������ �������
		else $f->SITE_ID = SITE_ID;
		
		// �������� ID ������� ����������� �� SID
		$arr = array(
			"CATEGORY"			=> "C",
			"CRITICALITY"		=> "K",
			"STATUS"			=> "S",
			"MARK"				=> "M",
			"SOURCE"			=> "SR",
			"MESSAGE_SOURCE"	=> "SR",
			"DIFFICULTY" => "D"
		);
		foreach($arr as $key => $value)
		{
			if ((array_key_exists($key . "_ID", $arFields) || intval($arFields[ $key . "_ID" ]) <= 0) && array_key_exists($key . "_SID", $arFields) && strlen($arFields[ $key . "_SID" ]) > 0)
			{
				$z = CTicketDictionary::GetBySID($arFields[ $key . "_SID" ], $value,  $f->SITE_ID);
				$zr = $z->Fetch();
				$arFields[$key."_ID"] = $zr["ID"];
			}
		}		
		return array("v" => $v, "f" => $f);
	}
	
	function Set($arFields, &$MID, $id="", $checkRights="Y", $sendEmailToAuthor="Y", $sendEmailToTechsupport="Y")
	{						
		global $DB, $APPLICATION, $USER;
		
		$err_mess = (CAllTicket::err_mess()) . "<br>Function: Set<br>Line: ";
		
		$v0 = self::Set_InitVar($arFields, $id, $checkRights, $sendEmailToAuthor, $sendEmailToTechsupport);
		if(!is_array($v0)) return $v0;
		$v = $v0["v"]; /* isNew, CHECK_RIGHTS, SEND_EMAIL_TO_AUTHOR, SEND_EMAIL_TO_TECHSUPPORT, bAdmin, bSupportTeam, bSupportClient, bDemo, bOwner, uid, bActiveCoupon, IsSpam */
		$f = $v0["f"]; /* ID, SITE_ID, MODIFIED_GUEST_ID, OWNER_USER_ID, OWNER_SID, HOLD_ON, IS_SPAM */

		// ���� ������������ ��������� ��
		if(!$v->isNew)
		{
			unset($arFields['COUPON']);
			$arFields['ID'] = $f->ID;
			$arFields = CTicket::ExecuteEvents('OnBeforeTicketUpdate', $arFields, false);
			$v->closeDate = (isset($arFields["CLOSE"]) && $arFields["CLOSE"] == "Y"); //$close
			
			// ���������� ���������� ������ ��������
			$v->arrOldFields = array();
			$arr = array(
				"RESPONSIBLE_USER_ID",
				"SLA_ID",
				"CATEGORY_ID",
				"CRITICALITY_ID",
				"STATUS_ID",
				"MARK_ID",
				"DIFFICULTY_ID",
				"DATE_CLOSE",
				"HOLD_ON"
				);
			$str = "ID";
			foreach ($arr as $s) $str .= "," . $s;
			$strSql = "SELECT " . $str . ", SITE_ID FROM b_ticket WHERE ID='" . $f->ID . "'";
			$z = $DB->Query($strSql, false, $err_mess . __LINE__);
			if($zr=$z->Fetch())
			{
				$f->SITE_ID = $zr["SITE_ID"];
				if(intval($v->uid) == $zr["RESPONSIBLE_USER_ID"]) $v->bSupportTeam = "Y";
				foreach ($arr as $key) $v->arrOldFields[$key] = $zr[$key];
			}
						
			$f->FromArray(
				$arFields,
				"SITE_ID,MODIFIED_MODULE_NAME,SUPPORT_COMMENTS,SLA_ID,SOURCE_ID",
				array(CSupportTableFields::MORE0,CSupportTableFields::NOT_EMTY_STR)
			);
			$f->FromArray(
				$arFields,
				"CATEGORY_ID,RESPONSIBLE_USER_ID,STATUS_ID,DIFFICULTY_ID,CRITICALITY_ID"
			);
			$f->set("MODIFIED_USER_ID", $v->uid, array(CSupportTableFields::MORE0));
			$f->setCurrentTime("TIMESTAMP_X");
			if($v->closeDate)
			{
				$f->setCurrentTime("DATE_CLOSE");
			}
						
			// ?remake? {
			$v->IS_GROUP_USER = 'N';
			if($v->bAdmin) $IS_GROUP_USER = 'Y';
			elseif($v->CHECK_RIGHTS == 'Y' && ($v->bSupportClient || $v->bSupportTeam))
			{
				if($v->bSupportTeam) $join_query = '(T.RESPONSIBLE_USER_ID IS NOT NULL AND T.RESPONSIBLE_USER_ID=O.USER_ID)';
				else $join_query = '(T.OWNER_USER_ID IS NOT NULL AND T.OWNER_USER_ID=O.USER_ID)';
				
				$strSql = "SELECT 'x'
				FROM b_ticket T
				INNER JOIN b_ticket_user_ugroup O ON $join_query
				INNER JOIN b_ticket_user_ugroup C ON (O.GROUP_ID=C.GROUP_ID)
				INNER JOIN b_ticket_ugroups G ON (O.GROUP_ID=G.ID)
				WHERE T.ID='" . $f->ID . "' AND C.USER_ID='" . $v->uid . "' AND C.CAN_VIEW_GROUP_MESSAGES='Y' AND G.IS_TEAM_GROUP='" . ($v->bSupportTeam ? "Y" : "N") . "'";
				$z = $DB->Query($strSql);
				if($zr = $z->Fetch()) $v->IS_GROUP_USER = 'Y';
			}
			// }
			
			if(isset($arFields["AUTO_CLOSE_DAYS"]) &&
				intval($arFields["AUTO_CLOSE_DAYS"]) > 0 &&
				strlen($arFields["MESSAGE"]) > 0 &&
				$arFields["HIDDEN"] != "Y" &&
				$arFields["NOT_CHANGE_STATUS"] != "Y"
			) $f->AUTO_CLOSE_DAYS = $arFields["AUTO_CLOSE_DAYS"];
			
			if(is_array($v->arrOldFields) && is_array($arFields) && $arFields["CLOSE"] == "N" && strlen($v->arrOldFields["DATE_CLOSE"] ) > 0)
			{
				$f->DATE_CLOSE = null;
				$f->REOPEN = "Y";
			}
				
			// ���� ���� ��� � �� ������ ��� �� ������ ��, ������� � ����
			$v->FirstUpdateRes = false;
			
			if($v->bSupportTeam || $v->bAdmin)
			{
				$arFields_i = $f->ToArray(CSupportTableFields::ALL, array(CSupportTableFields::ONLY_CHANGED), true);
				if(count($arFields_i) > 0)
				{
					$v->SupportTeamUpdateRes = $DB->Update("b_ticket", $arFields_i, "WHERE ID='" . $f->ID . "'", $err_mess . __LINE__); //$rows1
					$GLOBALS["USER_FIELD_MANAGER"]->Update("SUPPORT", $f->ID, $arFields);
					
					// ���� ������� ������� � ����� �� ��������� ������� � �����
					if (strlen($f->IS_SPAM) > 0) CTicket::MarkAsSpam($f->ID, $f->IS_SPAM, $v->CHECK_RIGHTS);
					
					$v->newSLA = (isset($arFields_i["SLA_ID"]) && $v->arrOldFields["SLA_ID"] != $arFields_i["SLA_ID"]);
				}
			}
			elseif($v->bOwner || $v->bSupportClient)
			{
				$arFields_i = $f->ToArray("TIMESTAMP_X,DATE_CLOSE,CRITICALITY_ID,MODIFIED_USER_ID,MODIFIED_GUEST_ID,MODIFIED_MODULE_NAME,REOPEN", array(CSupportTableFields::ONLY_CHANGED), true);
				$arFields_i["MARK_ID"] = intval($arFields["MARK_ID"]);
				if(count($arFields_i) > 0)
				{
					$v->SupportClientUpdateRes = $DB->Update("b_ticket",
												$arFields_i,
												"WHERE ID='" . $f->ID . "' AND (OWNER_USER_ID='" . $v->uid . "' OR CREATED_USER_ID='" . $v->uid . "' OR '" . $v->CHECK_RIGHTS . "'='N' OR '" . $v->IS_GROUP_USER . "'='Y')",
												$err_mess . __LINE__
					);
					$GLOBALS["USER_FIELD_MANAGER"]->Update("SUPPORT", $f->ID, $arFields);
				}
			}
			
			// ���� ��� ������ ����
			/*$arFields_log = array(
				"LOG"							=> "Y",
				"MESSAGE_CREATED_USER_ID"		=> $MODIFIED_USER_ID,
				"MESSAGE_CREATED_MODULE_NAME"	=> $MODIFIED_MODULE_NAME,
				"MESSAGE_CREATED_GUEST_ID"		=> $MODIFIED_GUEST_ID,
				"MESSAGE_SOURCE_ID"				=> intval($arFields["SOURCE_ID"])
			);*/
			
			// ���� ���������� ��������� ����� ��
			if($v->CHECK_RIGHTS == "Y")
			{
				// ���� update ������������ �� ������ ��
				if(intval($v->SupportTeamUpdateRes) <= 0)
				{
					// ������� �� ������� �������� �������� �� ��� ����� ������ ������ ������������
					unset($v->arrOldFields["RESPONSIBLE_USER_ID"]);
					unset($v->arrOldFields["SLA_ID"]);
					unset($v->arrOldFields["CATEGORY_ID"]);
					unset($v->arrOldFields["DIFFICULTY_ID"]);
					unset($v->arrOldFields["STATUS_ID"]);
				}
				// ���� update ������ �� ������ ��
				if (intval($v->SupportClientUpdateRes) <=0)
				{
					// ������� �� ������� �������� �������� �� ��� ����� ������ ������ �����
					unset($v->arrOldFields["MARK_ID"]);
				}
			}
			
			// ���� ��������� ���� �� updat'�� ��
			if(intval($v->SupportTeamUpdateRes) > 0 || intval($v->SupportClientUpdateRes) > 0)
			{
				
				// ��������� ���������
				$arFields["MESSAGE_CREATED_MODULE_NAME"] = $arFields["MODIFIED_MODULE_NAME"];
				if(is_set($arFields, "IMAGE")) $arFields["FILES"][] = $arFields["IMAGE"];
				$MID = CTicket::AddMessage($f->ID, $arFields, $arFiles, $v->CHECK_RIGHTS);
				$MID = intval($MID);
				
				$dateType = CTicket::ADD;
				if($v->newSLA || $f->REOPEN == "Y") 
				{
					$dateType = CTicket::CURRENT_DATE;
				}
				CTicket::UpdateLastParams2($f->ID, $dateType);

				/*// ���� ��������� ��������� ��
				if($v->closeDate)
				{
					// ������ �������-��������������� � ������� ��������� ���������
					CTicketReminder::Remove($f->ID);
				}*/
				
				if(is_array($v->arrOldFields) && is_array($arFields))
				{
					// ���������� ��� ����������
					$v->arChange = array();
					if ($MID > 0)
					{
						if($arFields["HIDDEN"] != "Y") $v->arChange["MESSAGE"] = "Y";
						else $v->arChange["HIDDEN_MESSAGE"] = "Y";
					}
					if($arFields["CLOSE"] == "Y" && strlen($v->arrOldFields["DATE_CLOSE"]) <= 0)
					{
						$v->arChange["CLOSE"] = "Y";
					}
					elseif($arFields["CLOSE"] == "N" && strlen($v->arrOldFields["DATE_CLOSE"]) > 0)
					{
						$v->arChange["OPEN"] = "Y";
					}
					
					if(array_key_exists("HOLD_ON", $arFields))
					{
						if($v->arrOldFields["HOLD_ON"] == null)
						{
							$v->arrOldFields["HOLD_ON"] = 'N';
						}
						if($arFields["HOLD_ON"] == null)
						{
							$arFields["HOLD_ON"] = 'N';
						}
						if($v->arrOldFields["HOLD_ON"] != $arFields["HOLD_ON"])
						{
							if($arFields["HOLD_ON"] == "Y")
							{
								$v->arChange["HOLD_ON_ON"] = "Y";
							}
							else
							{
								$v->arChange["HOLD_ON_OFF"] = "Y";
							}
							
						}
						unset($v->arrOldFields["HOLD_ON"]);
					}
							
					foreach($v->arrOldFields as $key => $value)
					{
						if(isset($arFields[$key]) && intval($value) != intval($arFields[$key]))
						{
							$v->arChange[$key] = "Y";
						}
					}
					
					// ������� ������� �������� ���������
					CTimeZone::Disable();
					$z = CTicket::GetByID($f->ID, $f->SITE_ID, "N");
					CTimeZone::Enable();

					if($zr = $z->Fetch())
					{
						$nf = (object)$zr;
					
						$rsSite = CSite::GetByID($nf->SITE_ID);
						$v->arrSite = $rsSite->Fetch();
						
						self::Set_sendMails($nf, $v, $arFields);
						
						//if ($v->arChange['SLA_ID'] == 'Y' || $v->arChange['OPEN'] == 'Y') CTicketReminder::Update($nf->ID, true);
					}
				}
				CTicket::ExecuteEvents('OnAfterTicketUpdate', $arFields, false);
			}
		}
		else
		{
			$arFields = CTicket::ExecuteEvents('OnBeforeTicketAdd', $arFields, false);
			if(!$arFields) return false;
			
						
			if(!((strlen(trim($arFields["OWNER_SID"])) > 0 || intval($arFields["OWNER_USER_ID"]) > 0) && ($v->bSupportTeam || $v->bAdmin)))
			{
				$f->OWNER_USER_ID = ($v->uid > 0) ? $v->uid : null;
				$f->OWNER_SID = null;
				$f->OWNER_GUEST_ID = intval($_SESSION["SESS_GUEST_ID"]) > 0 ? intval($_SESSION["SESS_GUEST_ID"]) : null;
			}
						
			$f->FromArray($arFields, "CREATED_MODULE_NAME,CATEGORY_ID,STATUS_ID,DIFFICULTY_ID,CRITICALITY_ID,SOURCE_ID,TITLE", array(CSupportTableFields::MORE0,CSupportTableFields::NOT_EMTY_STR));
			$f->set("CREATED_USER_ID", $v->uid, array(CSupportTableFields::MORE0));
			$f->setCurrentTime("LAST_MESSAGE_DATE,DAY_CREATE,TIMESTAMP_X");
			
			$f->DATE_CREATE = time() + CTimeZone::GetOffset();
			
			// ���� ��������� ��������� ����������� ������������, ��������������� ��� ���� �������������
			if($v->bSupportTeam || $v->bAdmin || $bv->Demo)
			{
				$f->FromArray($arFields, "SUPPORT_COMMENTS", array(CSupportTableFields::NOT_EMTY_STR));
			}
			
			if(!self::Set_getCOUPONandSLA($v, $f, $arFields)) return false;
			// $f +SLA_ID $v +V_COUPON +bActiveCoupon
			
			if ($v->bActiveCoupon) $f->COUPON = $v->V_COUPON;
			
			self::Set_getResponsibleUser($v, $f, $arFields);
			// $f +RESPONSIBLE_USER_ID  $v +T_EVENT1 +T_EVENT2 +T_EVENT3
			
			// ���� ��� ������ ����
			$v->arFields_log = array(
				"LOG"							=> "Y",
				"MESSAGE_CREATED_USER_ID"		=> $f->CREATED_USER_ID,
				"MESSAGE_CREATED_MODULE_NAME"	=> $f->CREATED_MODULE_NAME,
				"MESSAGE_CREATED_GUEST_ID"		=> $f->MODIFIED_GUEST_ID,
				"MESSAGE_SOURCE_ID"				=> $f->SOURCE_ID
			);
			
			
			$acd0 = intval(COption::GetOptionString("support", "DEFAULT_AUTO_CLOSE_DAYS"));
			$f->AUTO_CLOSE_DAYS = (($acd0 <= 0) ? 7 : $acd0);
			$arFields["AUTO_CLOSE_DAYS"] = $f->AUTO_CLOSE_DAYS;
			
			$arFields_i = $f->ToArray(CSupportTableFields::ALL, array(CSupportTableFields::NOT_NULL,CSupportTableFields::NOT_DEFAULT), true);
			$id = $DB->Insert("b_ticket", $arFields_i, $err_mess . __LINE__);
			if(!($id > 0)) return $id;
			$f->ID = $id;
			$GLOBALS["USER_FIELD_MANAGER"]->Update("SUPPORT", $f->ID, $arFields);
						
			$arFields["MESSAGE_AUTHOR_SID"] = $f->OWNER_SID;
			$arFields["MESSAGE_AUTHOR_USER_ID"] = $f->OWNER_USER_ID;
			$arFields["MESSAGE_CREATED_MODULE_NAME"] = $f->CREATED_MODULE_NAME;
			$arFields["MESSAGE_SOURCE_ID"] = $f->SOURCE_ID;
			$arFields["HIDDEN"] = "N";
			$arFields["LOG"] = "N";
			$arFields["IS_LOG"] = "N";
					
			if (is_set($arFields, "IMAGE")) $arFields["FILES"][] = $arFields["IMAGE"];
			$arFiles = null;
			$MID = CTicket::AddMessage($f->ID, $arFields, $arFiles, $v->CHECK_RIGHTS);
			$v->arrFILES = $arFiles;
			$MID = intval($MID);
			
			if(intval($MID) > 0)
			{
				CTicket::UpdateLastParams2($f->ID, CTicket::ADD);
				
				// ���� ������� ������� � ����� �� ��������� ������� � �����
				if (strlen($f->IS_SPAM) > 0) CTicket::MarkAsSpam($f->ID, $f->IS_SPAM, $v->CHECK_RIGHTS);
				
				/********************************************
					$nf - ������ ����������� �� ���� ����
				********************************************/

				CTimeZone::Disable();
				$z = CTicket::GetByID($f->ID, $f->SITE_ID, "N");
				CTimeZone::Enable();
				
				if($zr = $z->Fetch())
				{
					$nf = (object)$zr;
					
					$rsSite = CSite::GetByID($nf->SITE_ID);
					$v->arrSite = $rsSite->Fetch();
															
					self::Set_sendMails($nf, $v, $arFields);
					
					// ������� ������� � ������ ����������
					if(CModule::IncludeModule("statistic"))
					{
						if(!$v->category_set)
						{
							$v->T_EVENT1 = "ticket";
							$v->T_EVENT2 = "";
							$v->T_EVENT3 = "";
						}
						if(strlen($v->T_EVENT3) <= 0) $v->T_EVENT3 = "http://" . $_SERVER["HTTP_HOST"] . "/bitrix/admin/ticket_edit.php?ID=" . $f->ID . "&lang=" . $v->arrSite["LANGUAGE_ID"];
						CStatEvent::AddCurrent($v->T_EVENT1, $v->T_EVENT2, $v->T_EVENT3);
					}
					
				}
			}
			// !!! ��������� $arFields ����� �� ��� $arFields[..] = .. ����� �� ��� � ��������� !!!
			$arFields['ID'] = $f->ID;
			$arFields['MID'] = $MID;
			CTicket::ExecuteEvents('OnAfterTicketAdd', $arFields, true);

		}
		return $f->ID;	
	}

	/***********************************************
			������ ������� ��� �������������
	***********************************************/

	function GetFUA($site_id)
	{
		$err_mess = (CAllTicket::err_mess())."<br>Function: GetFUA<br>Line: ";
		global $DB;
		if ($site_id=="all") $site_id = "";
		$arFilter = array("TYPE" => "F", "SITE" => $site_id);
		$rs = CTicketDictionary::GetList(($v1="s_dropdown"), $v2, $arFilter, $v3);
		return $rs;
	}

	function GetRefBookValues($type, $site_id=false)
	{
		$err_mess = (CAllTicket::err_mess())."<br>Function: GetRefBookValues<br>Line: ";
		global $DB;
		if ($site_id=="all") $site_id = "";
		$arFilter = array("TYPE" => $type, "SITE" => $site_id);
		$rs = CTicketDictionary::GetList(($v1="s_dropdown"), $v2, $arFilter, $v3);
		return $rs;
	}

	function GetMessages($ticketID, $arFilter=array(), $checkRights="Y")
	{
		$arFilter["TICKET_ID"] = $ticketID;
		$arFilter["TICKET_ID_EXACT_MATCH"] = "Y";
		return CTicket::GetMessageList($by, $order, $arFilter, $is_filtered, $checkRights, "Y");
	}

	function GetResponsible()
	{
		return CTicket::GetSupportTeamList();
	}

	function IsResponsible($userID=false)
	{
		return CTicket::IsSupportTeam($userID);
	}

	function ExecuteEvents($message, $arFields, $isNew)
	{
		$rs = GetModuleEvents('support', $message);
		while ($arr = $rs->Fetch())
		{
			$arFields = ExecuteModuleEventEx($arr, array($arFields, $isNew));
		}

		return $arFields;
	}
	
	function GetResponsibleList($userID, $CMGM = null, $CMUGM = null, $SG = null)
	{
				
		$condition = "";
		if($CMGM != null) $condition .= "
							AND TUG2.CAN_MAIL_GROUP_MESSAGES = '" . ($CMGM == "Y" ? "Y" : "N") . "'";
		if($CMUGM != null) $condition .= "
							AND TUG2.CAN_MAIL_UPDATE_GROUP_MESSAGES = '" . ($CMUGM == "Y" ? "Y" : "N") . "'";
		
		$condition2 = "";
		if($SG != null) $condition2 .= "
							AND TG.IS_TEAM_GROUP = '" . ($SG == "Y" ? "Y" : "N") . "'";
		
		
		$err_mess = (CTicket::err_mess())."<br>Function: GetSupportTeamMailList<br>Line: ";
		global $DB;
		$strSql = "
			SELECT
				U.ID as ID,
				U.LOGIN as LOGIN,
				" . CTicket::isnull("U.LAST_NAME", "''") . " + ' ' + " . CTicket::isnull("U.NAME", "''") . " + ' (' + U.LOGIN + ')' as NAME,
				U.EMAIL as EMAIL
			FROM
				(
				SELECT
					TUG2.USER_ID AS USER_ID				
				FROM
					b_ticket_ugroups TG
					INNER JOIN b_ticket_user_ugroup TUG
						ON TG.ID = TUG.GROUP_ID" . $condition2 . "
					INNER JOIN b_ticket_user_ugroup TUG2
						ON TUG.USER_ID = '" . intval($userID) . "'
							AND TUG.GROUP_ID = TUG2.GROUP_ID" . $condition . "
				GROUP BY
					TUG2.USER_ID
				) TU
				INNER JOIN b_user U
					ON TU.USER_ID = U.ID
				ORDER BY
					U.ID
	
			";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}
	
	
	
}

?>
