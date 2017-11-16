<?
define("NO_KEEP_STATISTIC", true);
define("BX_STATISTIC_BUFFER_USED", false);
define("NO_LANG_FILES", true);
define("NOT_CHECK_PERMISSIONS", true);
define("BX_PUBLIC_TOOLS", true);

$site_id = (isset($_POST["site"]) && is_string($_POST["site"])) ? trim($_POST["site"]) : "";
$site_id = substr(preg_replace("/[^a-z0-9_]/i", "", $site_id), 0, 2);
$group_id = intval($_POST["GROUP_ID"]);
$arUserID = $_POST["USER_ID"];

define("SITE_ID", $site_id);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/bx_root.php");

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

$rsSite = CSite::GetByID($site_id);
if ($arSite = $rsSite->Fetch())
	define("LANGUAGE_ID", $arSite["LANGUAGE_ID"]);
else
	define("LANGUAGE_ID", "en");

__IncludeLang(dirname(__FILE__)."/lang/".LANGUAGE_ID."/ajax.php");

if(CModule::IncludeModule("compression"))
	CCompress::Disable2048Spaces();

if (!CModule::IncludeModule("socialnetwork"))
{
	echo CUtil::PhpToJsObject(Array('ERROR' => 'SONET_MODULE_NOT_INSTALLED'));
	die();
}

if (!$GLOBALS["USER"]->IsAuthorized())
{
	echo CUtil::PhpToJsObject(Array('ERROR' => 'CURRENT_USER_NOT_AUTH'));
	die();
}

if ($group_id <= 0)
{
	echo CUtil::PhpToJsObject(Array('ERROR' => 'GROUP_ID_NOT_DEFINED'));
	die();
}
else
{
	$arGroup = CSocNetGroup::GetByID($group_id);
	if (!$arGroup)
	{
		echo CUtil::PhpToJsObject(Array('ERROR' => 'GROUP_ID_NOT_DEFINED'));
		die();
	}
}

if (!is_array($arUserID) || count($arUserID) <= 0)
{
	echo CUtil::PhpToJsObject(Array('ERROR' => 'USER_ID_NOT_DEFINED'));
	die();
}

if (check_bitrix_sessid())
{
	$arCurrentUserPerms = CSocNetUserToGroup::InitUserPerms($GLOBALS["USER"]->GetID(), $arGroup, CSocNetUser::IsCurrentUserModuleAdmin());
	if (!$arCurrentUserPerms || !$arCurrentUserPerms["UserCanViewGroup"] || !$arCurrentUserPerms["UserCanModifyGroup"])
	{
		echo CUtil::PhpToJsObject(Array('ERROR' => 'USER_GROUP_NO_PERMS'));
		die();
	}

	$arRelationID = array();
	$arRelalationData = array();
	$rsRelation = CSocNetUserToGroup::GetList(
		array("ID" => "DESC"), 
		array(
			"USER_ID" => $arUserID, 
			"GROUP_ID" => $arGroup["ID"]
		), 
		false, 
		false, 
		array("ID", "USER_ID", "GROUP_ID", "ROLE")
	);
	while($arRelation = $rsRelation->Fetch())
	{
		$arRelationID[] = $arRelation["ID"];
		$arRelalationData[] = $arRelation;
	}

	if ($_POST['ACTION'] == 'U2M' && !CSocNetUserToGroup::TransferMember2Moderator($GLOBALS["USER"]->GetID(), $arGroup["ID"], $arRelationID, CSocNetUser::IsCurrentUserModuleAdmin()))
	{
		echo CUtil::PhpToJsObject(Array('ERROR' => 'USER_ACTION_FAILED: '.(($e = $APPLICATION->GetException()) ? $e->GetString() : "")));
		die();		
	}
	elseif ($_POST['ACTION'] == 'M2U' && !CSocNetUserToGroup::TransferModerator2Member($GLOBALS["USER"]->GetID(), $arGroup["ID"], $arRelationID, CSocNetUser::IsCurrentUserModuleAdmin()))
	{
		echo CUtil::PhpToJsObject(Array('ERROR' => 'USER_ACTION_FAILED: '.(($e = $APPLICATION->GetException()) ? $e->GetString() : "")));
		die();		
	}
	elseif ($_POST['ACTION'] == 'SETOWNER' && !CSocNetUserToGroup::SetOwner($arUserID[0], $arGroup["ID"], $arGroup))
	{
		echo CUtil::PhpToJsObject(Array('ERROR' => 'USER_ACTION_FAILED: '.(($e = $APPLICATION->GetException()) ? $e->GetString() : "")));
		die();		
	}
	elseif ($_POST['ACTION'] == 'BAN' && !CSocNetUserToGroup::BanMember($GLOBALS["USER"]->GetID(), $arGroup["ID"], $arRelationID, CSocNetUser::IsCurrentUserModuleAdmin()))
	{
		echo CUtil::PhpToJsObject(Array('ERROR' => 'USER_ACTION_FAILED: '.(($e = $APPLICATION->GetException()) ? $e->GetString() : "")));
		die();		
	}
	elseif ($_POST['ACTION'] == 'UNBAN' && !CSocNetUserToGroup::UnBanMember($GLOBALS["USER"]->GetID(), $arGroup["ID"], $arRelationID, CSocNetUser::IsCurrentUserModuleAdmin()))
	{
		echo CUtil::PhpToJsObject(Array('ERROR' => 'USER_ACTION_FAILED: '.(($e = $APPLICATION->GetException()) ? $e->GetString() : "")));
		die();		
	}
	elseif ($_POST['ACTION'] == 'EX')
	{
		foreach($arRelalationData as $relationData)
		{
			//group owner can't exclude himself from the group
			if ($relationData["ROLE"] == SONET_ROLES_OWNER)
			{
				echo CUtil::PhpToJsObject(Array('ERROR' => 'SONET_GUE_T_OWNER_CANT_EXCLUDE_HIMSELF'));
				die();
			}

			if (!CSocNetUserToGroup::Delete($relationData["ID"], true))
			{
				echo CUtil::PhpToJsObject(Array('ERROR' => 'USER_ACTION_FAILED: '.(($e = $APPLICATION->GetException()) ? $e->GetString() : "")));
				die();
			}
		}
	}

	echo CUtil::PhpToJsObject(Array('SUCCESS' => 'Y'));
}
else
	echo CUtil::PhpToJsObject(Array('ERROR' => 'SESSION_ERROR'));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>