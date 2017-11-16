<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("blog"))
{
	ShowError(GetMessage("BLOG_MODULE_NOT_INSTALL"));
	return;
}
if (!CModule::IncludeModule("socialnetwork"))
{
	ShowError(GetMessage("SONET_MODULE_NOT_INSTALL"));
	return;
}

$arParams["MESSAGE_COUNT"] = IntVal($arParams["MESSAGE_COUNT"])>0 ? IntVal($arParams["MESSAGE_COUNT"]): 20;
$arParams["SORT_BY1"] = (strlen($arParams["SORT_BY1"])>0 ? $arParams["SORT_BY1"] : "DATE_PUBLISH");
$arParams["SORT_ORDER1"] = (strlen($arParams["SORT_ORDER1"])>0 ? $arParams["SORT_ORDER1"] : "DESC");
$arParams["SORT_BY2"] = (strlen($arParams["SORT_BY2"])>0 ? $arParams["SORT_BY2"] : "ID");
$arParams["SORT_ORDER2"] = (strlen($arParams["SORT_ORDER2"])>0 ? $arParams["SORT_ORDER2"] : "DESC");

$arParams["BLOG_URL"] = preg_replace("/[^a-zA-Z0-9_-]/is", "", Trim($arParams["BLOG_URL"]));
$arParams["YEAR"] = (IntVal($arParams["YEAR"])>0 ? IntVal($arParams["YEAR"]) : false);
$arParams["MONTH"] = (IntVal($arParams["MONTH"])>0 ? IntVal($arParams["MONTH"]) : false);
$arParams["DAY"] = (IntVal($arParams["DAY"])>0 ? IntVal($arParams["DAY"]) : false);
$arParams["CATEGORY_ID"] = (IntVal($arParams["CATEGORY_ID"])>0 ? IntVal($arParams["CATEGORY_ID"]) : false);
$arParams["NAV_TEMPLATE"] = (strlen($arParams["NAV_TEMPLATE"])>0 ? $arParams["NAV_TEMPLATE"] : "");
if(!is_array($arParams["GROUP_ID"]))
	$arParams["GROUP_ID"] = array($arParams["GROUP_ID"]);
foreach($arParams["GROUP_ID"] as $k=>$v)
	if(IntVal($v) <= 0)
		unset($arParams["GROUP_ID"][$k]);

$arParams["USER_ID"] = IntVal($arParams["USER_ID"]);
$arParams["SOCNET_GROUP_ID"] = IntVal($arParams["SOCNET_GROUP_ID"]);

if ($arParams["CACHE_TYPE"] == "Y" || ($arParams["CACHE_TYPE"] == "A" && COption::GetOptionString("main", "component_cache_on", "Y") == "Y"))
{
	$arParams["CACHE_TIME"] = intval($arParams["CACHE_TIME"]);
	$arParams["CACHE_TIME_LONG"] = intval($arParams["CACHE_TIME_LONG"]);
	if(IntVal($arParams["CACHE_TIME_LONG"]) <= 0 && IntVal($arParams["CACHE_TIME"]) > 0)
		$arParams["CACHE_TIME_LONG"] = $arParams["CACHE_TIME"];

}
else
{
	$arParams["CACHE_TIME"] = 0;
	$arParams["CACHE_TIME_LONG"] = 0;

}
$arParams["POST_PROPERTY"] = array("UF_BLOG_POST_FILE", "UF_BLOG_POST_DOC");
$arParams["DATE_TIME_FORMAT"] = trim(empty($arParams["DATE_TIME_FORMAT"]) ? $DB->DateFormatToPHP(CSite::GetDateFormat("FULL")) : $arParams["DATE_TIME_FORMAT"]);

// activation rating
CRatingsComponentsMain::GetShowRating($arParams);

$arParams["ALLOW_POST_CODE"] = $arParams["ALLOW_POST_CODE"] !== "N";

$SORT = Array($arParams["SORT_BY1"]=>$arParams["SORT_ORDER1"], $arParams["SORT_BY2"]=>$arParams["SORT_ORDER2"]);

CpageOption::SetOptionString("main", "nav_page_in_session", "N");

if(strLen($arParams["BLOG_VAR"])<=0)
	$arParams["BLOG_VAR"] = "blog";
if(strLen($arParams["PAGE_VAR"])<=0)
	$arParams["PAGE_VAR"] = "page";
if(strLen($arParams["USER_VAR"])<=0)
	$arParams["USER_VAR"] = "id";
if(strLen($arParams["POST_VAR"])<=0)
	$arParams["POST_VAR"] = "id";

$arParams["PATH_TO_BLOG"] = trim($arParams["PATH_TO_BLOG"]);
if(strlen($arParams["PATH_TO_BLOG"])<=0)
	$arParams["PATH_TO_BLOG"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=blog&".$arParams["BLOG_VAR"]."=#blog#");

$arParams["PATH_TO_BLOG_CATEGORY"] = trim($arParams["PATH_TO_BLOG_CATEGORY"]);
if(strlen($arParams["PATH_TO_BLOG_CATEGORY"])<=0)
	$arParams["PATH_TO_BLOG_CATEGORY"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=blog&".$arParams["BLOG_VAR"]."=#blog#"."&category=#category_id#");

$arParams["PATH_TO_POST"] = trim($arParams["PATH_TO_POST"]);
if(strlen($arParams["PATH_TO_POST"])<=0)
	$arParams["PATH_TO_POST"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=post&".$arParams["BLOG_VAR"]."=#blog#&".$arParams["POST_VAR"]."=#post_id#");

$arParams["PATH_TO_POST_EDIT"] = trim($arParams["PATH_TO_POST_EDIT"]);
if(strlen($arParams["PATH_TO_POST_EDIT"])<=0)
	$arParams["PATH_TO_POST_EDIT"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=post_edit&".$arParams["BLOG_VAR"]."=#blog#&".$arParams["POST_VAR"]."=#post_id#");

$arParams["PATH_TO_USER"] = trim($arParams["PATH_TO_USER"]);
if(strlen($arParams["PATH_TO_USER"])<=0)
	$arParams["PATH_TO_USER"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=user&".$arParams["USER_VAR"]."=#user_id#");

$arParams["PATH_TO_SMILE"] = strlen(trim($arParams["PATH_TO_SMILE"]))<=0 ? false : trim($arParams["PATH_TO_SMILE"]);

$arParams["IMAGE_MAX_WIDTH"] = IntVal($arParams["IMAGE_MAX_WIDTH"]);
$arParams["IMAGE_MAX_HEIGHT"] = IntVal($arParams["IMAGE_MAX_HEIGHT"]);
$arParams["ALLOW_POST_CODE"] = $arParams["ALLOW_POST_CODE"] !== "N";
$bGroupMode = false;
if(IntVal($arParams["SOCNET_GROUP_ID"]) > 0)
	$bGroupMode = true;

$feature = "blog";
if (($bGroupMode && CSocNetFeatures::IsActiveFeature(SONET_ENTITY_GROUP, $arParams["SOCNET_GROUP_ID"], $feature)) || CSocNetFeatures::IsActiveFeature(SONET_ENTITY_USER, $arParams["USER_ID"], $feature))
{
	if(strlen($arParams["FILTER_NAME"])<=0 || !preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/i", $arParams["FILTER_NAME"]))
		$arFilter = array();
	else
	{
		global $$arParams["FILTER_NAME"];
		$arFilter = ${$arParams["FILTER_NAME"]};
		if(!is_array($arFilter))
			$arFilter = array();
	}

	$arResult["ERROR_MESSAGE"] = Array();
	$arResult["OK_MESSAGE"] = Array();
	$user_id = IntVal($USER->GetID());
	if(IntVal($arParams["USER_ID"]) > 0 || $bGroupMode)
	{
		$bCurrentUserIsAdmin = CSocNetUser::IsCurrentUserModuleAdmin();
		$arResult["perms"] = BLOG_PERMS_DENY;
		if($bGroupMode)
		{
			if (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_GROUP, $arParams["SOCNET_GROUP_ID"], "blog", "full_post", $bCurrentUserIsAdmin) || $APPLICATION->GetGroupRight("blog") >= "W")
				$arResult["perms"] = BLOG_PERMS_FULL;
			elseif (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_GROUP, $arParams["SOCNET_GROUP_ID"], "blog", "moderate_post", $bCurrentUserIsAdmin))
				$arResult["perms"] = BLOG_PERMS_MODERATE;
			elseif (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_GROUP, $arParams["SOCNET_GROUP_ID"], "blog", "write_post", $bCurrentUserIsAdmin))
				$arResult["perms"] = BLOG_PERMS_WRITE;
			elseif (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_GROUP, $arParams["SOCNET_GROUP_ID"], "blog", "premoderate_post", $bCurrentUserIsAdmin))
				$arResult["perms"] = BLOG_PERMS_PREMODERATE;
			elseif (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_GROUP, $arParams["SOCNET_GROUP_ID"], "blog", "view_post", $bCurrentUserIsAdmin))
				$arResult["perms"] = BLOG_PERMS_READ;
		}
		else
		{
			if (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_USER, $arParams["USER_ID"], "blog", "view_post", $bCurrentUserIsAdmin))
				$arResult["perms"] = BLOG_PERMS_READ;
		}
		if($arResult["perms"] >= BLOG_PERMS_READ)
		{

			//Message delete
			if (IntVal($_GET["del_id"]) > 0)
			{
				if($_GET["success"] == "Y")
					$arResult["OK_MESSAGE"][] = GetMessage("BLOG_BLOG_BLOG_MES_DELED");
				else
				{
					if (check_bitrix_sessid())
					{
						$del_id = IntVal($_GET["del_id"]);
						$bCanDelete = false;
						if($arResult["perms"] >= BLOG_PERMS_FULL)
							$bCanDelete = true;
						if(!$bCanDelete && !$bGroupMode)
							if(CBlogPost::GetSocNetPostPerms($del_id, true) >= BLOG_PERMS_FULL)
								$bCanDelete = true;
						if($bCanDelete)
						{
							if(CBlogPost::GetByID($del_id))
							{
								CBlogPost::DeleteLog($del_id);
								if (CBlogPost::Delete($del_id))
								{
									if ($bGroupMode)
										CSocNetGroup::SetLastActivity($arParams["SOCNET_GROUP_ID"]);
									LocalRedirect($APPLICATION->GetCurPageParam("del_id=".$del_id."&success=Y", Array("del_id", "hide_id", "sessid", "success")));
								}
								else
									$arResult["ERROR_MESSAGE"][] = GetMessage("BLOG_BLOG_BLOG_MES_DEL_ERROR");
							}
						}
						else
							$arResult["ERROR_MESSAGE"][] = GetMessage("BLOG_BLOG_BLOG_MES_DEL_NO_RIGHTS");
					}
					else
						$arResult["ERROR_MESSAGE"][] = GetMessage("BLOG_BLOG_SESSID_WRONG");
				}
			}
			elseif (IntVal($_GET["hide_id"]) > 0)
			{
				if($_GET["success"] == "Y")
					$arResult["OK_MESSAGE"][] = GetMessage("BLOG_BLOG_BLOG_MES_HIDED");
				else
				{
					if (check_bitrix_sessid())
					{
						$hide_id = IntVal($_GET["hide_id"]);
						$bCanHide = false;
						if($arResult["perms"] >= BLOG_PERMS_MODERATE)
							$bCanHide = true;
						if(!$bCanHide && !$bGroupMode)
							if(CBlogPost::GetSocNetPostPerms($hide_id, true) >= BLOG_PERMS_MODERATE)
								$bCanHide = true;

						if($bCanHide)
						{
							if(CBlogPost::GetByID($hide_id))
							{
								if(CBlogPost::Update($hide_id, Array("PUBLISH_STATUS" => BLOG_PUBLISH_STATUS_READY)))
								{
									CBlogPost::DeleteLog($hide_id);
									LocalRedirect($APPLICATION->GetCurPageParam("hide_id=".$hide_id."&success=Y", Array("del_id", "hide_id", "sessid", "success")));
								}
								else
									$arResult["ERROR_MESSAGE"][] = GetMessage("BLOG_BLOG_BLOG_MES_HIDE_ERROR");
							}
						}
						else
							$arResult["ERROR_MESSAGE"][] = GetMessage("BLOG_BLOG_BLOG_MES_HIDE_NO_RIGHTS");
					}
					else
						$arResult["ERROR_MESSAGE"][] = GetMessage("BLOG_BLOG_SESSID_WRONG");
				}
			}

			$arFilter["PUBLISH_STATUS"] = BLOG_PUBLISH_STATUS_PUBLISH;
			$arFilter["BLOG_USE_SOCNET"] = "Y";
			$arFilter["GROUP_ID"] = $arParams["GROUP_ID"];
			$arFilter["GROUP_SITE_ID"] = SITE_ID;

			if(IntVal($arParams["USER_ID"]) > 0 && $arParams["USER_ID"] == $user_id) // in own profile
			{
				if($arParams["4ME"] == "ALL")
				{
					$arFilter["FOR_USER"] = $user_id;
					$arFilter["FOR_USER_TYPE"] = "ALL";
				}
				elseif($arParams["4ME"] == "Y")
				{
					$arFilter["FOR_USER"] = $user_id;
					$arFilter["!AUTHOR_ID"] = $user_id;
					$arFilter["FOR_USER_TYPE"] = "SELF";
				}
				elseif($arParams["4ME"] == "DR")
				{
					$arFilter["FOR_USER"] = $user_id;
					$arFilter["!AUTHOR_ID"] = $user_id;
					$arFilter["FOR_USER_TYPE"] = "DR";
				}
				else
					$arFilter["AUTHOR_ID"] = $user_id;
			}
			elseif(IntVal($arParams["USER_ID"]) > 0 && $arParams["USER_ID"] != $user_id) // in other user profile
			{
				$arFilter["AUTHOR_ID"] = $arParams["USER_ID"];
				$arFilter["FOR_USER"] = IntVal($user_id);
			}
			elseif(IntVal($arParams["SOCNET_GROUP_ID"]) > 0) // socialnetwork group
			{
				$arFilter["SOCNET_GROUP_ID"] = $arParams["SOCNET_GROUP_ID"];
			}

			if($arParams["YEAR"] && $arParams["MONTH"] && $arParams["DAY"])
			{
				$from = mktime(0, 0, 0, $arParams["MONTH"], $arParams["DAY"], $arParams["YEAR"]);
				$to = mktime(0, 0, 0, $arParams["MONTH"], ($arParams["DAY"]+1), $arParams["YEAR"]);
				if($to > ($t = time()+CTimeZone::GetOffset()))
					$to = $t;
				$arFilter[">=DATE_PUBLISH"] = ConvertTimeStamp($from, "FULL");
				$arFilter["<DATE_PUBLISH"] = ConvertTimeStamp($to, "FULL");
			}
			elseif($arParams["YEAR"] && $arParams["MONTH"])
			{
				$from = mktime(0, 0, 0, $arParams["MONTH"], 1, $arParams["YEAR"]);
				$to = mktime(0, 0, 0, ($arParams["MONTH"]+1), 1, $arParams["YEAR"]);
				if($to > ($t = time()+CTimeZone::GetOffset()))
					$to = $t;
				$arFilter[">=DATE_PUBLISH"] = ConvertTimeStamp($from, "FULL");
				$arFilter["<DATE_PUBLISH"] = ConvertTimeStamp($to, "FULL");
			}
			elseif($arParams["YEAR"])
			{
				$from = mktime(0, 0, 0, 1, 1, $arParams["YEAR"]);
				$to = mktime(0, 0, 0, 1, 1, ($arParams["YEAR"]+1));
				if($to > ($t = time()+CTimeZone::GetOffset()))
					$to = $t;
				$arFilter[">=DATE_PUBLISH"] = ConvertTimeStamp($from, "FULL");
				$arFilter["<DATE_PUBLISH"] = ConvertTimeStamp($to, "FULL");
			}
			else
				$arFilter["<=DATE_PUBLISH"] = ConvertTimeStamp(time()+CTimeZone::GetOffset(), "FULL");

			if(IntVal($arParams["CATEGORY_ID"])>0)
			{
				$arFilter["CATEGORY_ID_F"] = $arParams["CATEGORY_ID"];
				if($arParams["SET_TITLE"] == "Y")
				{
					$arCat = CBlogCategory::GetByID($arFilter["CATEGORY_ID"]);
					$arResult["title"]["category"] = CBlogTools::htmlspecialcharsExArray($arCat);
				}
			}

			$arResult["filter"] = $arFilter;

			$dbPost = CBlogPost::GetList(
				$SORT,
				$arFilter,
				false,
				array("bDescPageNumbering"=>true, "nPageSize"=>$arParams["MESSAGE_COUNT"], "bShowAll" => false),
				array("ID", "TITLE", "BLOG_ID", "AUTHOR_ID", "DETAIL_TEXT", "DETAIL_TEXT_TYPE", "DATE_PUBLISH", "PUBLISH_STATUS", "ENABLE_COMMENTS", "VIEWS", "NUM_COMMENTS", "CATEGORY_ID", "CODE", "BLOG_OWNER_ID", "BLOG_GROUP_ID", "BLOG_GROUP_SITE_ID", "MICRO")
			);

			$arResult["NAV_STRING"] = $dbPost->GetPageNavString(GetMessage("MESSAGE_COUNT"), $arParams["NAV_TEMPLATE"]);
			$arResult["POST"] = Array();
			$arResult["IDS"] = Array();

			while($arPost = $dbPost->GetNext())
			{
				$arPost["perms"] = $arResult["perms"];
				if(!$bGroupMode && $arParams["USER_ID"] == $user_id && (empty($arParams["4ME"]) || $arPost["AUTHOR_ID"] == $user_id))
					$arPost["perms"] = BLOG_PERMS_FULL;
				elseif((!$bGroupMode && $arParams["USER_ID"] != $user_id) || strlen($arParams["4ME"]) > 0)
					$arPost["perms"] = CBlogPost::GetSocNetPostPerms($arPost["ID"], true);

				$arPost["urlToPost"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_POST"], array("post_id"=> CBlogPost::GetPostID($arPost["ID"], $arPost["CODE"], $arParams["ALLOW_POST_CODE"]), "user_id" => $arPost["BLOG_OWNER_ID"]));
				if($arPost["perms"] >= BLOG_PERMS_WRITE)
				{
					if($arPost["perms"] >= BLOG_PERMS_FULL || ($arPost["perms"] >= BLOG_PERMS_WRITE && $arPost["AUTHOR_ID"] == $user_id))
						$arPost["urlToEdit"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_POST_EDIT"], array("post_id"=>$arPost["ID"], "user_id" => $arPost["AUTHOR_ID"]));
					if($arPost["perms"] >= BLOG_PERMS_MODERATE)
						$arPost["urlToHide"] = htmlspecialcharsex($APPLICATION->GetCurPageParam("hide_id=".$arPost["ID"]."&".bitrix_sessid_get(), Array("del_id", "sessid", "success", "hide_id")));
					if($arPost["perms"] >= BLOG_PERMS_FULL)
						$arPost["urlToDelete"] = htmlspecialcharsex($APPLICATION->GetCurPageParam("del_id=".$arPost["ID"]."&".bitrix_sessid_get(), Array("del_id", "sessid", "success", "hide_id")));
				}

				$arResult["POST"][] = $arPost;
				$arResult["IDS"][] = $arPost["ID"];
			}

			if($arParams["SHOW_RATING"] == "Y" && !empty($arResult["IDS"]))
				$arResult["RATING"] = CRatings::GetRatingVoteResult('BLOG_POST', $arResult["IDS"]);
		}

		if($arResult["perms"] < BLOG_PERMS_READ)
			$arResult["MESSAGE"][] = GetMessage("BLOG_BLOG_BLOG_FRIENDS_ONLY");
	}
	else
	{
		$arResult["ERROR_MESSAGE"][] = GetMessage("BLOG_BLOG_BLOG_NO_BLOG");
		CHTTP::SetStatus("404 Not Found");
	}
}
else
	$arResult["ERROR_MESSAGE"][] = GetMessage("BLOG_SONET_MODULE_NOT_AVAIBLE");

$this->IncludeComponentTemplate();
?>
