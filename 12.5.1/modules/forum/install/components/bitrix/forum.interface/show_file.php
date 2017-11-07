<?
define("STOP_STATISTICS", true);
define("NO_AGENT_STATISTIC","Y");
define("NO_AGENT_CHECK", true);
define("DisableEventsCheck", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?><?

$MESS = array();
$path = str_replace(array("\\", "//"), "/", dirname(__FILE__)."/lang/".LANGUAGE_ID."/show_file.php");
include_once($path);
$MESS1 =& $MESS;
$GLOBALS["MESS"] = $MESS1 + $GLOBALS["MESS"];

CModule::IncludeModule("forum");
// ************************* Input params***************************************************************
// ************************* BASE **********************************************************************
$arParams = array(
	"FILE_ID" => intval($_REQUEST["fid"]),
	"WIDTH" => intval($_REQUEST['width']),
	"HEIGHT" => intval($_REQUEST['height']),
	"ACTION" => ($_REQUEST["action"] == "download" ? "download" : "view"),
	"PERMISSION" => false
);
// *************************/Input params***************************************************************
// ************************* Default params*************************************************************
$arResult = array(
	"MESSAGE" => array(),
	"FILE" => array()
);
$arError = array();
if (intVal($arParams["FILE_ID"]) > 0)
{
	$db_res = CForumFiles::GetList(array("ID" => "ASC"), array("FILE_ID" => $arParams["FILE_ID"]));
	if ($db_res && ($arResult["FILE"] = $db_res->GetNext())) {
		$res = CFile::GetFileArray($arParams["FILE_ID"]);
		if (!!$res) { $arResult["FILE"] += $res; }
	}
}

if (empty($arResult["FILE"]))
{
	$arError = array(
		"code" => "EMPTY FILE",
		"title" => GetMessage("F_EMPTY_FID"));
}
elseif (intVal($arResult["FILE"]["MESSAGE_ID"]) > 0)
{
	$arResult["MESSAGE"] = CForumMessage::GetByIDEx($arResult["FILE"]["MESSAGE_ID"], array("GET_FORUM_INFO" => "Y", "GET_TOPIC_INFO" => "Y"));
	$arResult["TOPIC"] = $arResult["MESSAGE"]["TOPIC_INFO"];
	$arResult["FORUM"] = $arResult["MESSAGE"]["FORUM_INFO"];

	if (IsModuleInstalled('meeting') && CModule::IncludeModule('meeting'))
	{
		$forumId = COption::GetOptionInt('meeting', 'comments_forum_id', 0, SITE_ID);
		if ($arResult['FORUM']['ID'] == $forumId)
		{
			$meetingID = false;
			$xmlID = $arResult['MESSAGE']['FT_XML_ID'];
			preg_match('/MEETING_ITEM_([0-9]+)/', $xmlID, $matches);
			if (sizeof($matches) > 0)
			{
				$meetingItemID = $matches[1];
				if (CMeetingItem::HasAccess($meetingItemID))
					$arParams['PERMISSION'] = 'M';
			}

			preg_match('/MEETING_([0-9]+)/', $xmlID, $matches);
			if (sizeof($matches) > 0)
			{
				$meetingID = $matches[1];
				if (CMeeting::GetUserRole($meetingID) !== false)
					$arParams['PERMISSION'] = 'M';
			}

		}
	}

	if (IsModuleInstalled('tasks') && CModule::IncludeModule('tasks'))
	{
		$tasksIsTasksJurisdiction = false;

		// Insurance for cross-modules version compatibility
		if (method_exists('CTasksTools','ListTasksForumsAsArray'))
		{
			try
			{
				$arTasksForums = CTasksTools::ListTasksForumsAsArray();

				if (in_array((int) $arResult['FORUM']['ID'], $arTasksForums, true))
					$tasksIsTasksJurisdiction = true;
			}
			catch (TasksException $e)
			{
				// do nothing
			}
		}
		else
		{
			// TODO: this old code section to be removed in next versions.
			$forumId = COption::GetOptionString('tasks', 'task_forum_id', -1);
			if (
				($forumId !== (-1)) 
				&& ((int) $arResult['FORUM']['ID'] === (int) $forumId)
			)
			{
				$tasksIsTasksJurisdiction = true;
			}
		}

		if ($tasksIsTasksJurisdiction)
		{
			$arParams['PERMISSION'] = 'D';

			if (CTasks::CanCurrentUserViewTopic($arResult['TOPIC']['ID']))
				$arParams['PERMISSION'] = 'M';
		}
	}

	if (empty($arParams["PERMISSION"]))
	{
		$arParams["PERMISSION"] = CForumNew::GetUserPermission($arResult["MESSAGE"]["FORUM_ID"], $USER->GetUserGroupArray());

		if ($arParams["PERMISSION"] < "E" && (intVal($arResult["TOPIC"]["SOCNET_GROUP_ID"]) > 0 ||
			intVal($arResult["TOPIC"]["OWNER_ID"]) > 0) && CModule::IncludeModule("socialnetwork"))
		{
			$sPermission = $arParams["PERMISSION"];
			$user_id = $USER->GetID();
			$group_id = intVal($arResult["TOPIC"]["SOCNET_GROUP_ID"]);
			$owner_id = intVal($arResult["TOPIC"]["OWNER_ID"]);

			if ($group_id):

				$arSonetGroup = CSocNetGroup::GetByID($group_id);
				if ($arSonetGroup)
					$site_id_tmp = $arSonetGroup["SITE_ID"];
				else
					$site_id_tmp = false;

				$bIsCurrentUserModuleAdmin = CSocNetUser::IsCurrentUserModuleAdmin($site_id_tmp);

				if (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_GROUP, $group_id, "forum", "full", $bIsCurrentUserModuleAdmin)):
					$arParams["PERMISSION"] = "Y";
				elseif (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_GROUP, $group_id, "forum", "newtopic", $bIsCurrentUserModuleAdmin)):
					$arParams["PERMISSION"] = "M";
				elseif (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_GROUP, $group_id, "forum", "answer", $bIsCurrentUserModuleAdmin)):
					$arParams["PERMISSION"] = "I";
				elseif (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_GROUP, $group_id, "forum", "view", $bIsCurrentUserModuleAdmin)):
					$arParams["PERMISSION"] = "E";
				endif;
			else:

				$arForumSites = CForumNew::GetSites($arResult["FORUM"]["ID"]);
				if (count($arForumSites) > 0)
				{
					list($key, $val) = each($arForumSites);
					if (strlen($key) > 0)
						$site_id_tmp = $key;
					else
						$site_id_tmp = false;
				}
				else
					$site_id_tmp = false;

				$bIsCurrentUserModuleAdmin = CSocNetUser::IsCurrentUserModuleAdmin($site_id_tmp);

				if (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_USER, $owner_id, "forum", "full", $GLOBALS['USER']->IsAdmin())):
					$arParams["PERMISSION"] = "Y";
				elseif (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_USER, $owner_id, "forum", "newtopic", $bIsCurrentUserModuleAdmin)):
					$arParams["PERMISSION"] = "M";
				elseif (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_USER, $owner_id, "forum", "answer", $bIsCurrentUserModuleAdmin)):
					$arParams["PERMISSION"] = "I";
				elseif (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_USER, $owner_id, "forum", "view", $bIsCurrentUserModuleAdmin)):
					$arParams["PERMISSION"] = "E";
				endif;
			endif;

			$arParams["PERMISSION"] = ($arParams["PERMISSION"] < $sPermission ? $sPermission : $arParams["PERMISSION"]);
		}
	}

	if (empty($arResult["MESSAGE"]))
	{
		$arError = array(
			"code" => "EMPTY MESSAGE",
			"title" => GetMessage("F_EMPTY_MID"));
	}
	elseif ($arParams["PERMISSION"])
	{
		if ($arParams["PERMISSION"] < "E")
			$arError = array(
				"code" => "NOT RIGHT",
				"title" => GetMessage("F_NOT_RIGHT"));
	}
	elseif (ForumCurrUserPermissions($arResult["MESSAGE"]["FORUM_ID"]) < "E")
	{
		$arError = array(
			"code" => "NOT RIGHT",
			"title" => GetMessage("F_NOT_RIGHT"));
	}
}


if (!empty($arError))
{
	require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_after.php");
	echo ShowError((!empty($arError["title"]) ? $arError["title"] : $arError["code"]));
	require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog.php");
	die();
}
// *************************/Default params*************************************************************

set_time_limit(0);
if ($arParams["ACTION"] == "download")
{
	CFile::ViewByUser($arResult["FILE"], array("force_download" => true));
}
else
{
	if ((CFile::CheckImageFile(CFile::MakeFileArray($arResult["FILE"]["FILE_ID"])) === null)
		&& (
			(
				file_exists($_SERVER["DOCUMENT_ROOT"].$arResult["FILE"]["SRC"])
				&& CFile::GetImageSize($_SERVER["DOCUMENT_ROOT"].$arResult["FILE"]["SRC"])
			) || (
				$arResult["FILE"]["WIDTH"] > 0
				&& $arResult["FILE"]["HEIGHT"] > 0
			)
		)
	)
	{
		if ($arParams['WIDTH'] > 0 && $arParams['HEIGHT'] > 0)
		{
			$imageFile = $arResult['FILE'];

			$arFileTmp = CFile::ResizeImageGet(
				$imageFile,
				array("width" => $arParams["WIDTH"], "height" => $arParams["HEIGHT"]),
				BX_RESIZE_IMAGE_PROPORTIONAL,
				true
			);

			CFile::ViewByUser(array(
				'ORIGINAL_NAME' => $imageFile['ORIGINAL_NAME'],
				'FILE_SIZE' => $arFileTmp['size'],
				'SRC' => $arFileTmp['src'],
			), array(
				"content_type" => $arResult["FILE"]["CONTENT_TYPE"],
			));
		}
		else
		{
			CFile::ViewByUser($arResult["FILE"], array("content_type" => $arResult["FILE"]["CONTENT_TYPE"]));
		}
	}
	else
	{
		$ct = strtolower($arResult["FILE"]["CONTENT_TYPE"]);
		if (strpos($ct, "excel") !== false)
			CFile::ViewByUser($arResult["FILE"], array("content_type" => "application/vnd.ms-excel"));
		elseif (strpos($ct, "word") !== false)
			CFile::ViewByUser($arResult["FILE"], array("content_type" => "application/msword"));
		else
			CFile::ViewByUser($arResult["FILE"], array("content_type" => "application/octet-stream", "force_download" => true));
	}
}
// *****************************************************************************************
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_after.php");
echo ShowError(GetMessage("F_ATTACH_NOT_FOUND"));
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog.php");
// *****************************************************************************************
?>