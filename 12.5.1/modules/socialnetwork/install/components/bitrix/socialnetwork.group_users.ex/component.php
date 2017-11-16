<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("socialnetwork"))
{
	ShowError(GetMessage("SONET_MODULE_NOT_INSTALL"));
	return;
}

$arParams["GROUP_ID"] = IntVal($arParams["GROUP_ID"]);

$arParams["SET_NAV_CHAIN"] = ($arParams["SET_NAV_CHAIN"] == "N" ? "N" : "Y");

if (strLen($arParams["USER_VAR"]) <= 0)
	$arParams["USER_VAR"] = "user_id";
if (strLen($arParams["GROUP_VAR"]) <= 0)
	$arParams["GROUP_VAR"] = "group_id";
if (strLen($arParams["PAGE_VAR"]) <= 0)
	$arParams["PAGE_VAR"] = "page";

$arParams["PATH_TO_USER"] = trim($arParams["PATH_TO_USER"]);
if (strlen($arParams["PATH_TO_USER"]) <= 0)
	$arParams["PATH_TO_USER"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=user&".$arParams["USER_VAR"]."=#user_id#");
$arParams["PATH_TO_GROUP"] = trim($arParams["PATH_TO_GROUP"]);
if (strlen($arParams["PATH_TO_GROUP"]) <= 0)
	$arParams["PATH_TO_GROUP"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=group&".$arParams["GROUP_VAR"]."=#group_id#");
$arParams["PATH_TO_GROUP_EDIT"] = trim($arParams["PATH_TO_GROUP_EDIT"]);
if (strlen($arParams["PATH_TO_GROUP_EDIT"]) <= 0)
	$arParams["PATH_TO_GROUP_EDIT"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=group_edit&".$arParams["GROUP_VAR"]."=#group_id#");

$arParams["ITEMS_COUNT"] = IntVal($arParams["ITEMS_COUNT"]);
if ($arParams["ITEMS_COUNT"] <= 0)
	$arParams["ITEMS_COUNT"] = 20;

$arParams["THUMBNAIL_LIST_SIZE"] = IntVal($arParams["THUMBNAIL_LIST_SIZE"]);
if ($arParams["THUMBNAIL_LIST_SIZE"] <= 0)
	$arParams["THUMBNAIL_LIST_SIZE"] = 42;

$arParams['NAME_TEMPLATE'] = $arParams['NAME_TEMPLATE'] ? $arParams['NAME_TEMPLATE'] : GetMessage("SONET_GUE_NAME_TEMPLATE_DEFAULT");
$arParams["NAME_TEMPLATE_WO_NOBR"] = str_replace(
	array("#NOBR#", "#/NOBR#"), 
	array("", ""), 
	$arParams["NAME_TEMPLATE"]
);
$bUseLogin = $arParams['SHOW_LOGIN'] != "N" ? true : false;
	
$arGroup = CSocNetGroup::GetByID($arParams["GROUP_ID"]);

if ($arGroup["CLOSED"] == "Y" && COption::GetOptionString("socialnetwork", "work_with_closed_groups", "N") != "Y")
	$arResult["HideArchiveLinks"] = true;

$arParams["GROUP_USE_BAN"] = 
		$arParams["GROUP_USE_BAN"] != "N" 
		&& (!CModule::IncludeModule('extranet') || (!CExtranet::IsExtranetSite() && !$arResult["HideArchiveLinks"]))
	? "Y" 
	: "N";

if (
	!$arGroup 
	|| !is_array($arGroup) 
	|| $arGroup["ACTIVE"] != "Y" 
)
	$arResult["FatalError"] = GetMessage("SONET_P_USER_NO_GROUP").". ";
else
{
	$arGroupSites = array();
	$rsGroupSite = CSocNetGroup::GetSite($arGroup["ID"]);
	while ($arGroupSite = $rsGroupSite->Fetch())
		$arGroupSites[] = $arGroupSite["LID"];

	if (!in_array(SITE_ID, $arGroupSites))
		$arResult["FatalError"] = GetMessage("SONET_P_USER_NO_GROUP");
	else
	{
		$arResult["Group"] = $arGroup;

		$arResult["CurrentUserPerms"] = CSocNetUserToGroup::InitUserPerms($GLOBALS["USER"]->GetID(), $arResult["Group"], CSocNetUser::IsCurrentUserModuleAdmin());

		if (!$arResult["CurrentUserPerms"] || !$arResult["CurrentUserPerms"]["UserCanViewGroup"])
			$arResult["FatalError"] = GetMessage("SONET_GUE_NO_PERMS").". ";
		else
		{
			$arNavParams = array("nPageSize" => $arParams["ITEMS_COUNT"], "bDescPageNumbering" => false, "bShowAll"=>false);

			$arResult["Urls"]["Group"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP"], array("group_id" => $arResult["Group"]["ID"]));
			$arResult["Urls"]["GroupEdit"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP_EDIT"], array("group_id" => $arResult["Group"]["ID"]));

			if ($arParams["SET_TITLE"] == "Y")
				$APPLICATION->SetTitle($arResult["Group"]["NAME"].": ".GetMessage("SONET_GUE_PAGE_TITLE"));

			if ($arParams["SET_NAV_CHAIN"] != "N")
			{
				$APPLICATION->AddChainItem($arResult["Group"]["NAME"], $arResult["Urls"]["Group"]);
				$APPLICATION->AddChainItem(GetMessage("SONET_GUE_PAGE_TITLE"));
			}

			$arSelect = array("ID", "USER_ID", "ROLE", "DATE_CREATE", "DATE_UPDATE", "USER_NAME", "USER_LAST_NAME", "USER_SECOND_NAME", "USER_LOGIN", "USER_PERSONAL_PHOTO", "USER_PERSONAL_GENDER", "USER_IS_ONLINE", "USER_WORK_POSITION");
			// Users
			
			$arResult["Users"] = false;
			$dbRequests = CSocNetUserToGroup::GetList(
				array("USER_LAST_NAME" => "ASC", "USER_NAME" => "ASC"),
				array(
					"GROUP_ID" => $arResult["Group"]["ID"],
					"USER_ACTIVE" => "Y",
					"<=ROLE" => SONET_ROLES_USER
				),
				false,
				$arNavParams,
				$arSelect
			);

			if ($dbRequests)
			{
				$arResult["Users"] = array();
				$arResult["Users"]["List"] = false;

				while ($arRequests = $dbRequests->GetNext())
				{
					$pu = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $arRequests["USER_ID"]));
					$canViewProfile = CSocNetUserPerms::CanPerformOperation($GLOBALS["USER"]->GetID(), $arRequests["USER_ID"], "viewprofile", CSocNetUser::IsCurrentUserModuleAdmin());

					if (intval($arParams["THUMBNAIL_LIST_SIZE"]) > 0)
					{
						if (intval($arRequests["USER_PERSONAL_PHOTO"]) <= 0)
						{
							switch ($arRequests["USER_PERSONAL_GENDER"])
							{
								case "M":
									$suffix = "male";
									break;
								case "F":
									$suffix = "female";
									break;
								default:
									$suffix = "unknown";
							}
							$arRequests["USER_PERSONAL_PHOTO"] = COption::GetOptionInt("socialnetwork", "default_user_picture_".$suffix, false, SITE_ID);
						}

						$arImage = CFile::ResizeImageGet(
							$arRequests["USER_PERSONAL_PHOTO"],
							array("width" => $arParams["THUMBNAIL_LIST_SIZE"], "height" => $arParams["THUMBNAIL_LIST_SIZE"]),
							BX_RESIZE_IMAGE_EXACT,
							false
						);
					}

					$arTmpUser = array(
						"NAME" => $arRequests["USER_NAME"],
						"LAST_NAME" => $arRequests["USER_LAST_NAME"],
						"SECOND_NAME" => $arRequests["USER_SECOND_NAME"],
						"LOGIN" => $arRequests["USER_LOGIN"],
					);
					$NameFormatted = CUser::FormatName($arParams['NAME_TEMPLATE_WO_NOBR'], $arTmpUser, $bUseLogin);

					if ($arResult["Users"]["List"] == false)
						$arResult["Users"]["List"] = array();

					$arResult["Users"]["List"][] = array(
						"ID" => $arRequests["ID"],
						"USER_ID" => $arRequests["USER_ID"],
						"USER_NAME" => $arRequests["USER_NAME"],
						"USER_LAST_NAME" => $arRequests["USER_LAST_NAME"],
						"USER_SECOND_NAME" => $arRequests["USER_SECOND_NAME"],
						"USER_LOGIN" => $arRequests["USER_LOGIN"],
						"USER_NAME_FORMATTED" => $NameFormatted,
						"USER_PERSONAL_PHOTO" => $arRequests["USER_PERSONAL_PHOTO"],
						"USER_PERSONAL_PHOTO_IMG" => $arImage,
						"USER_WORK_POSITION" => $arRequests["USER_WORK_POSITION"],						
						"USER_PROFILE_URL" => $pu,
						"SHOW_PROFILE_LINK" => $canViewProfile,
						"IS_ONLINE" => ($arRequests["USER_IS_ONLINE"] == "Y"),
					);
				}
				$arResult["Users"]["NAV_STRING"] = $dbRequests->GetPageNavStringEx($navComponentObject, GetMessage("SONET_GUE_USERS_NAV"), "", false);
			}

			// Moderators

			$arResult["Moderators"] = false;
			$dbRequests = CSocNetUserToGroup::GetList(
				array("USER_LAST_NAME" => "ASC", "USER_NAME" => "ASC"),
				array(
					"GROUP_ID" => $arResult["Group"]["ID"],
					"USER_ACTIVE" => "Y",
					"<=ROLE" => SONET_ROLES_MODERATOR
				),
				false,
				$arNavParams,
				$arSelect
			);

			if ($dbRequests)
			{
				$arResult["Moderators"] = array();
				$arResult["Moderators"]["List"] = false;

				while ($arRequests = $dbRequests->GetNext())
				{
					$pu = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $arRequests["USER_ID"]));
					$canViewProfile = CSocNetUserPerms::CanPerformOperation($GLOBALS["USER"]->GetID(), $arRequests["USER_ID"], "viewprofile", CSocNetUser::IsCurrentUserModuleAdmin());

					if (intval($arParams["THUMBNAIL_LIST_SIZE"]) > 0)
					{
						if (intval($arRequests["USER_PERSONAL_PHOTO"]) <= 0)
						{
							switch ($arRequests["USER_PERSONAL_GENDER"])
							{
								case "M":
									$suffix = "male";
									break;
								case "F":
									$suffix = "female";
									break;
								default:
									$suffix = "unknown";
							}
							$arRequests["USER_PERSONAL_PHOTO"] = COption::GetOptionInt("socialnetwork", "default_user_picture_".$suffix, false, SITE_ID);
						}

						$arImage = CFile::ResizeImageGet(
							$arRequests["USER_PERSONAL_PHOTO"],
							array("width" => $arParams["THUMBNAIL_LIST_SIZE"], "height" => $arParams["THUMBNAIL_LIST_SIZE"]),
							BX_RESIZE_IMAGE_EXACT,
							false
						);
					}

					$arTmpUser = array(
						"NAME" => $arRequests["USER_NAME"],
						"LAST_NAME" => $arRequests["USER_LAST_NAME"],
						"SECOND_NAME" => $arRequests["USER_SECOND_NAME"],
						"LOGIN" => $arRequests["USER_LOGIN"],
					);
					$NameFormatted = CUser::FormatName($arParams['NAME_TEMPLATE_WO_NOBR'], $arTmpUser, $bUseLogin);

					if ($arResult["Moderators"]["List"] == false)
						$arResult["Moderators"]["List"] = array();

					$arResult["Moderators"]["List"][] = array(
						"ID" => $arRequests["ID"],
						"USER_ID" => $arRequests["USER_ID"],
						"USER_NAME" => $arRequests["USER_NAME"],
						"USER_LAST_NAME" => $arRequests["USER_LAST_NAME"],
						"USER_SECOND_NAME" => $arRequests["USER_SECOND_NAME"],
						"USER_LOGIN" => $arRequests["USER_LOGIN"],
						"USER_NAME_FORMATTED" => $NameFormatted,
						"USER_PERSONAL_PHOTO" => $arRequests["USER_PERSONAL_PHOTO"],
						"USER_PERSONAL_PHOTO_IMG" => $arImage,
						"USER_WORK_POSITION" => $arRequests["USER_WORK_POSITION"],						
						"USER_PROFILE_URL" => $pu,
						"SHOW_PROFILE_LINK" => $canViewProfile,
						"IS_ONLINE" => ($arRequests["USER_IS_ONLINE"] == "Y"),
						"IS_OWNER" => ($arRequests["ROLE"] == SONET_ROLES_OWNER)
					);
				}
				$arResult["Moderators"]["NAV_STRING"] = $dbRequests->GetPageNavStringEx($navComponentObject, GetMessage("SONET_GUE_MODS_NAV"), "", false);
			}

			if (
				$arParams["GROUP_USE_BAN"] == "Y" 
				&& $arResult["CurrentUserPerms"] 
				&& $arResult["CurrentUserPerms"]["UserCanModerateGroup"]
			)
			{
				// Ban

				$arResult["Ban"] = false;

				$dbRequests = CSocNetUserToGroup::GetList(
					array("USER_LAST_NAME" => "ASC", "USER_NAME" => "ASC"),
					array(
						"GROUP_ID" => $arResult["Group"]["ID"],
						"USER_ACTIVE" => "Y",
						"ROLE" => SONET_ROLES_BAN
					),
					false,
					$arNavParams,
					$arSelect
				);

				if ($dbRequests)
				{
					$arResult["Ban"] = array();
					$arResult["Ban"]["List"] = false;

					while ($arRequests = $dbRequests->GetNext())
					{
						$pu = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $arRequests["USER_ID"]));
						$canViewProfile = CSocNetUserPerms::CanPerformOperation($GLOBALS["USER"]->GetID(), $arRequests["USER_ID"], "viewprofile", CSocNetUser::IsCurrentUserModuleAdmin());

						if (intval($arParams["THUMBNAIL_LIST_SIZE"]) > 0)
						{
							if (intval($arRequests["USER_PERSONAL_PHOTO"]) <= 0)
							{
								switch ($arRequests["USER_PERSONAL_GENDER"])
								{
									case "M":
										$suffix = "male";
										break;
									case "F":
										$suffix = "female";
										break;
									default:
										$suffix = "unknown";
								}
								$arRequests["USER_PERSONAL_PHOTO"] = COption::GetOptionInt("socialnetwork", "default_user_picture_".$suffix, false, SITE_ID);
							}					

							$arImage = CFile::ResizeImageGet(
								$arRequests["USER_PERSONAL_PHOTO"],
								array("width" => $arParams["THUMBNAIL_LIST_SIZE"], "height" => $arParams["THUMBNAIL_LIST_SIZE"]),
								BX_RESIZE_IMAGE_EXACT,
								false
							);
						}

						$arTmpUser = array(
							"NAME" => $arRequests["USER_NAME"],
							"LAST_NAME" => $arRequests["USER_LAST_NAME"],
							"SECOND_NAME" => $arRequests["USER_SECOND_NAME"],
							"LOGIN" => $arRequests["USER_LOGIN"],
						);
						$NameFormatted = CUser::FormatName($arParams['NAME_TEMPLATE_WO_NOBR'], $arTmpUser, $bUseLogin);

						if ($arResult["Ban"]["List"] == false)
							$arResult["Ban"]["List"] = array();

						$arResult["Ban"]["List"][] = array(
							"ID" => $arRequests["ID"],
							"USER_ID" => $arRequests["USER_ID"],
							"USER_NAME" => $arRequests["USER_NAME"],
							"USER_LAST_NAME" => $arRequests["USER_LAST_NAME"],
							"USER_SECOND_NAME" => $arRequests["USER_SECOND_NAME"],
							"USER_LOGIN" => $arRequests["USER_LOGIN"],
							"USER_NAME_FORMATTED" => $NameFormatted,
							"USER_PERSONAL_PHOTO" => $arRequests["USER_PERSONAL_PHOTO"],
							"USER_PERSONAL_PHOTO_IMG" => $arImage,
							"USER_WORK_POSITION" => $arRequests["USER_WORK_POSITION"],						
							"USER_PROFILE_URL" => $pu,
							"SHOW_PROFILE_LINK" => $canViewProfile,
							"IS_ONLINE" => ($arRequests["USER_IS_ONLINE"] == "Y"),
						);
					}
					$arResult["Ban"]["NAV_STRING"] = $dbRequests->GetPageNavStringEx($navComponentObject, GetMessage("SONET_GUE_BAN_NAV"), "", false);
				}
			}

		}
	}
}

$this->IncludeComponentTemplate();
?>