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
	$arParams["ITEMS_COUNT"] = 10;

$arParams["THUMBNAIL_LIST_SIZE"] = IntVal($arParams["THUMBNAIL_LIST_SIZE"]);
if ($arParams["THUMBNAIL_LIST_SIZE"] <= 0)
	$arParams["THUMBNAIL_LIST_SIZE"] = 30;

$arParams["NAME_TEMPLATE"] = $arParams["NAME_TEMPLATE"] ? $arParams["NAME_TEMPLATE"] : CSite::GetNameFormat();
$arParams["NAME_TEMPLATE_WO_NOBR"] = str_replace(
	array("#NOBR#", "#/NOBR#"), 
	array("", ""), 
	$arParams["NAME_TEMPLATE"]
);
$bUseLogin = $arParams["SHOW_LOGIN"] != "N" ? true : false;

$arParams["PATH_TO_SMILE"] = Trim($arParams["PATH_TO_SMILE"]);

if (!$GLOBALS["USER"]->IsAuthorized())
	$arResult["NEED_AUTH"] = "Y";
else
{
	$arGroup = CSocNetGroup::GetByID($arParams["GROUP_ID"]);

	if (
		!$arGroup 
		|| !is_array($arGroup) 
		|| $arGroup["ACTIVE"] != "Y" 
	)
		$arResult["FatalError"] = GetMessage("SONET_GRE_NO_GROUP");
	else
	{
		$arGroupSites = array();
		$rsGroupSite = CSocNetGroup::GetSite($arGroup["ID"]);
		while ($arGroupSite = $rsGroupSite->Fetch())
			$arGroupSites[] = $arGroupSite["LID"];

		if (!in_array(SITE_ID, $arGroupSites))
			$arResult["FatalError"] = GetMessage("SONET_GRE_NO_GROUP");
		else
		{
			$arResult["Group"] = $arGroup;
			$arResult["CurrentUserPerms"] = CSocNetUserToGroup::InitUserPerms($GLOBALS["USER"]->GetID(), $arResult["Group"], CSocNetUser::IsCurrentUserModuleAdmin());

			if (!$arResult["CurrentUserPerms"] || !$arResult["CurrentUserPerms"]["UserCanViewGroup"])
				$arResult["FatalError"] = GetMessage("SONET_GRE_NO_PERMS").". ";
			else
			{
				$arResult["Urls"]["Group"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP"], array("group_id" => $arResult["Group"]["ID"]));
				$arResult["Urls"]["GroupEdit"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP_EDIT"], array("group_id" => $arResult["Group"]["ID"]));

				if ($arParams["SET_TITLE"] == "Y")
					$APPLICATION->SetTitle($arResult["Group"]["NAME"].": ".GetMessage("SONET_GRE_TITLE"));

				if ($arParams["SET_NAV_CHAIN"] != "N")
				{
					$APPLICATION->AddChainItem($arResult["Group"]["NAME"], $arResult["Urls"]["Group"]);
					$APPLICATION->AddChainItem(GetMessage("SONET_GRE_TITLE"));
				}

				if (!$arResult["CurrentUserPerms"]["UserCanInitiate"])
					$arResult["FatalError"] = GetMessage("SONET_GRE_CANT_INVITE").". ";
				else
				{
					if (
						$_SERVER["REQUEST_METHOD"] == "POST" 
						&& (in_array($_POST["action"], array("accept", "reject"))) 
						&& check_bitrix_sessid()
					)
					{
						$errorMessage = "";

						$arIDs = array();
						if (strlen($errorMessage) <= 0)
						{
							for ($i = 0; $i <= IntVal($_POST["max_count"]); $i++)
							{
								if ($_POST["checked_".$i] == "Y")
									$arIDs[] = IntVal($_POST["id_".$i]);
							}

							if (count($arIDs) <= 0)
								$errorMessage .= GetMessage("SONET_GRE_NOT_SELECTED").". ";
						}

						if (strlen($errorMessage) <= 0)
						{
							$type = ($_POST["type"] == "out" ? "out" : "in");
							if ($type == "in")
							{
								if ($_POST["action"] == "accept")
								{
									if (
										!CSocNetUserToGroup::ConfirmRequestToBeMember($GLOBALS["USER"]->GetID(), $arResult["Group"]["ID"], $arIDs, false)
										&& ($e = $APPLICATION->GetException())
									)
										$errorMessage .= $e->GetString();
								}
								elseif ($_POST["action"] == "reject")
								{
									if (
										!CSocNetUserToGroup::RejectRequestToBeMember($GLOBALS["USER"]->GetID(), $arResult["Group"]["ID"], $arIDs)
										&& ($e = $APPLICATION->GetException())
									)
										$errorMessage .= $e->GetString();
								}
							}
							else
							{
								if ($_POST["action"] == "reject")
								{
									$errorMessage = "";
									foreach($arIDs as $relation_id)
									{
										$arRelation = CSocNetUserToGroup::GetByID($relation_id);
										if (!$arRelation)
											continue;

										if (!CSocNetUserToGroup::Delete($arRelation["ID"]))
										{
											if ($e = $APPLICATION->GetException())
												$errorMessage .= $e->GetString();
											if (StrLen($errorMessage) <= 0)
												$errorMessage .= str_replace("#RELATION_ID#", $arRelation["ID"], GetMessage("SONET_GRE_CANT_DELETE_INVITATION"));
										}
									}
									$GLOBALS["APPLICATION"]->ThrowException($errorMessage, "ERROR_DELETE_RELATION");
								}
							}
						}

						if (strlen($errorMessage) > 0)
							$arResult["ErrorMessage"] = $errorMessage;
					}
					elseif (
						CModule::IncludeModule('extranet') 
						&& in_array(CExtranet::GetExtranetSiteID(), $arGroupSites)
						&& intval($_REQUEST["invite_user_id"]) > 0
						&& check_bitrix_sessid()
					)
					{
						$errorMessage = "";

						$rsInvitedUser = CUser::GetByID(intval($_REQUEST["invite_user_id"]));
						if (
							($arInvitedUser = $rsInvitedUser->Fetch()) 
							&& (
								!is_array($arInvitedUser["UF_DEPARTMENT"]) 
								|| intval($arInvitedUser["UF_DEPARTMENT"][0]) <= 0
							)
						)
						{
							if (
								strlen($arInvitedUser["LAST_LOGIN"]) <= 0 
								&& strlen($arInvitedUser["LAST_ACTIVITY_DATE"]) <= 0
							)
							{
								$checkword = randString(8);

								$tmpUser = new CUser;
								$arFields = array("CHECKWORD" => $checkword);
								$tmpUser->Update($arInvitedUser["ID"], $arFields);
								$errorMessage .= $tmpUser->LAST_ERROR;

								if (strlen($errorMessage) <= 0)
								{
									$event = new CEvent;
									$arFields = Array(
										"USER_ID" => $arInvitedUser["ID"],
										"CHECKWORD" => $checkword,
										"EMAIL" => $arInvitedUser["EMAIL"]
									);

									$event->Send("EXTRANET_INVITATION", CExtranet::GetExtranetSiteID(), $arFields);
								}	

								if (strlen($errorMessage) > 0)
									$arResult["ErrorMessage"] = $errorMessage;
								else
									LocalRedirect($APPLICATION->GetCurPageParam("invite_sent=Y", array("invite_user_id", "employee")));
							}
						}
					}

					$arNavParams = array("nPageSize" => $arParams["ITEMS_COUNT"], "bDescPageNumbering" => false);
					$parser = new CSocNetTextParser(LANGUAGE_ID, $arParams["PATH_TO_SMILE"]);

					$arResult["Requests"] = false;
					$dbRequests = CSocNetUserToGroup::GetList(
						array("DATE_CREATE" => "ASC"),
						array(
							"GROUP_ID" => $arResult["Group"]["ID"],
							"ROLE" => SONET_ROLES_REQUEST,
							"INITIATED_BY_TYPE" => SONET_INITIATED_BY_USER
						),
						false,
						$arNavParams,
						array("ID", "USER_ID", "DATE_CREATE", "DATE_UPDATE", "MESSAGE", "USER_NAME", "USER_LAST_NAME", "USER_SECOND_NAME", "USER_LOGIN", "USER_PERSONAL_PHOTO", "USER_PERSONAL_GENDER")
					);
					if ($dbRequests)
					{
						$arResult["Requests"] = array();
						$arResult["Requests"]["List"] = false;

						while ($arRequests = $dbRequests->GetNext())
						{
							if ($arResult["Requests"]["List"] == false)
								$arResult["Requests"]["List"] = array();

							$pu = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $arRequests["USER_ID"]));
							$canViewProfile = CSocNetUserPerms::CanPerformOperation($GLOBALS["USER"]->GetID(), $arRequests["USER_ID"], "viewprofile", CSocNetUser::IsCurrentUserModuleAdmin());

							$arImage = array();

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

							$arResult["Requests"]["List"][] = array(
								"ID" => $arRequests["ID"],
								"USER_ID" => $arRequests["USER_ID"],
								"USER_NAME" => $arRequests["USER_NAME"],
								"USER_LAST_NAME" => $arRequests["USER_LAST_NAME"],
								"USER_SECOND_NAME" => $arRequests["USER_SECOND_NAME"],
								"USER_LOGIN" => $arRequests["USER_LOGIN"],
								"USER_NAME_FORMATTED" => $NameFormatted,
								"USER_PERSONAL_PHOTO" => $arRequests["USER_PERSONAL_PHOTO"],
								"USER_PERSONAL_PHOTO_IMG" => $arImage,
								"USER_PROFILE_URL" => $pu,
								"SHOW_PROFILE_LINK" => $canViewProfile,
								"DATE_CREATE" => $arRequests["DATE_CREATE"],
								"MESSAGE" => $parser->convert(
									$arRequests["~MESSAGE"],
									false,
									array(),
									array(
										"HTML" => "N",
										"ANCHOR" => "Y",
										"BIU" => "Y",
										"IMG" => "Y",
										"LIST" => "Y",
										"QUOTE" => "Y",
										"CODE" => "Y",
										"FONT" => "Y",
										"SMILES" => "Y",
										"UPLOAD" => "N",
										"NL2BR" => "N"
									)
								)
							);
						}
						$arResult["Requests"]["NAV_STRING"] = $dbRequests->GetPageNavStringEx($navComponentObject, GetMessage("SONET_GRE_NAV"), "", false);
					}

					$arResult["RequestsOut"] = false;
					$dbRequests = CSocNetUserToGroup::GetList(
						array("DATE_CREATE" => "ASC"),
						array(
							"GROUP_ID" => $arResult["Group"]["ID"],
							"ROLE" => SONET_ROLES_REQUEST,
							"INITIATED_BY_TYPE" => SONET_INITIATED_BY_GROUP
						),
						false,
						$arNavParams,
						array("ID", "USER_ID", "DATE_CREATE", "DATE_UPDATE", "MESSAGE", "USER_NAME", "USER_LAST_NAME", "USER_SECOND_NAME", "USER_LOGIN", "USER_PERSONAL_PHOTO", "USER_PERSONAL_GENDER")
					);
					if ($dbRequests)
					{
						$arResult["RequestsOut"] = array();
						$arResult["RequestsOut"]["List"] = false;

						while ($arRequests = $dbRequests->GetNext())
						{
							if ($arResult["RequestsOut"]["List"] == false)
								$arResult["RequestsOut"]["List"] = array();

							$pu = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $arRequests["USER_ID"]));
							$canViewProfile = CSocNetUserPerms::CanPerformOperation($GLOBALS["USER"]->GetID(), $arRequests["USER_ID"], "viewprofile", CSocNetUser::IsCurrentUserModuleAdmin());

							$arImage = array();

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

							$arResult["RequestsOut"]["List"][] = array(
								"ID" => $arRequests["ID"],
								"USER_ID" => $arRequests["USER_ID"],
								"USER_NAME" => $arRequests["USER_NAME"],
								"USER_LAST_NAME" => $arRequests["USER_LAST_NAME"],
								"USER_SECOND_NAME" => $arRequests["USER_SECOND_NAME"],
								"USER_LOGIN" => $arRequests["USER_LOGIN"],
								"USER_NAME_FORMATTED" => $NameFormatted,
								"USER_PERSONAL_PHOTO" => $arRequests["USER_PERSONAL_PHOTO"],
								"USER_PERSONAL_PHOTO_IMG" => $arImage,
								"USER_PROFILE_URL" => $pu,
								"SHOW_PROFILE_LINK" => $canViewProfile,
								"DATE_CREATE" => $arRequests["DATE_CREATE"],
								"MESSAGE" => $parser->convert(
									$arRequests["~MESSAGE"],
									false,
									array(),
									array(
										"HTML" => "N",
										"ANCHOR" => "Y",
										"BIU" => "Y",
										"IMG" => "Y",
										"LIST" => "Y",
										"QUOTE" => "Y",
										"CODE" => "Y",
										"FONT" => "Y",
										"SMILES" => "Y",
										"UPLOAD" => "N",
										"NL2BR" => "N"
									)
								)
							);
						}
						$arResult["RequestsOut"]["NAV_STRING"] = $dbRequests->GetPageNavStringEx($navComponentObject, GetMessage("SONET_GRE_NAV"), "", false);
					}				
				}
			}
		}
	}
}
$this->IncludeComponentTemplate();
?>