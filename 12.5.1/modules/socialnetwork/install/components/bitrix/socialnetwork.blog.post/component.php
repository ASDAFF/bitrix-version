<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("blog"))
{
	ShowError(GetMessage("BLOG_MODULE_NOT_INSTALL"));
	return;
}
if (!CModule::IncludeModule("socialnetwork"))
	return;

$arParams["ID"] = trim($arParams["ID"]);
if(preg_match("/^[1-9][0-9]*\$/", $arParams["ID"]))
{
	$arParams["ID"] = IntVal($arParams["ID"]);
}
else
{
	$arParams["ID"] = preg_replace("/[^a-zA-Z0-9_-]/is", "", Trim($arParams["~ID"]));
	$arParams["ID"] = CBlogPost::GetID($arParams["ID"], $arBlog["ID"]);
}

if (!isset($arParams["CHECK_PERMISSIONS_DEST"]) || strLen($arParams["CHECK_PERMISSIONS_DEST"]) <= 0)
	$arParams["CHECK_PERMISSIONS_DEST"] = "N";

if($arParams["FROM_LOG"] == "Y" || $arParams["TYPE"] == "DRAFT" || $arParams["TYPE"] == "MODERATION")
	$arResult["bFromList"] = true;
else
	$arResult["bFromList"] = false;

if($arParams["ID"] == "" && !$arResult["bFromList"])
{
	ShowError(GetMessage("B_B_MES_NO_POST"));
	@define("ERROR_404", "Y");
	CHTTP::SetStatus("404 Not Found");
	return;
}

if(!is_array($arParams["GROUP_ID"]))
	$arParams["GROUP_ID"] = array($arParams["GROUP_ID"]);
foreach($arParams["GROUP_ID"] as $k=>$v)
	if(IntVal($v) <= 0)
		unset($arParams["GROUP_ID"][$k]);

if(strLen($arParams["BLOG_VAR"])<=0)
	$arParams["BLOG_VAR"] = "blog";
if(strLen($arParams["PAGE_VAR"])<=0)
	$arParams["PAGE_VAR"] = "page";
if(strLen($arParams["USER_VAR"])<=0)
	$arParams["USER_VAR"] = "id";
if(strLen($arParams["POST_VAR"])<=0)
	$arParams["POST_VAR"] = "id";

$applicationCurPage = $APPLICATION->GetCurPage();

$arParams["PATH_TO_BLOG"] = trim($arParams["PATH_TO_BLOG"]);
if(strlen($arParams["PATH_TO_BLOG"])<=0)
	$arParams["PATH_TO_BLOG"] = htmlspecialcharsbx($applicationCurPage."?".$arParams["PAGE_VAR"]."=blog&".$arParams["BLOG_VAR"]."=#blog#");

$arParams["PATH_TO_POST"] = trim($arParams["PATH_TO_POST"]);
if(strlen($arParams["PATH_TO_POST"])<=0)
	$arParams["PATH_TO_POST"] = "/company/personal/user/#user_id#/blog/#post_id#/";
	//$arParams["PATH_TO_POST"] = htmlspecialcharsbx($applicationCurPage."?".$arParams["PAGE_VAR"]."=post&".$arParams["BLOG_VAR"]."=#blog#&".$arParams["POST_VAR"]."=#post_id#");

$arParams["PATH_TO_BLOG_CATEGORY"] = trim($arParams["PATH_TO_BLOG_CATEGORY"]);
if(strlen($arParams["PATH_TO_BLOG_CATEGORY"])<=0)
	$arParams["PATH_TO_BLOG_CATEGORY"] = htmlspecialcharsbx($applicationCurPage."?".$arParams["PAGE_VAR"]."=blog&".$arParams["BLOG_VAR"]."=#blog#"."&category=#category_id#");

$arParams["PATH_TO_POST_EDIT"] = trim($arParams["PATH_TO_POST_EDIT"]);
if(strlen($arParams["PATH_TO_POST_EDIT"])<=0)
	$arParams["PATH_TO_POST_EDIT"] = "/company/personal/user/#user_id#/blog/edit/#post_id#/";
	//$arParams["PATH_TO_POST_EDIT"] = htmlspecialcharsbx($applicationCurPage."?".$arParams["PAGE_VAR"]."=post_edit&".$arParams["BLOG_VAR"]."=#blog#&".$arParams["POST_VAR"]."=#post_id#");

$arParams["PATH_TO_USER"] = trim($arParams["PATH_TO_USER"]);
if(strlen($arParams["PATH_TO_USER"])<=0)
	$arParams["PATH_TO_USER"] = htmlspecialcharsbx($applicationCurPage."?".$arParams["PAGE_VAR"]."=user&".$arParams["USER_VAR"]."=#user_id#");
if(strlen($arParams["PATH_TO_SEARCH_TAG"])<=0)
	$arParams["PATH_TO_SEARCH_TAG"] = "/search/?tags=#tag#";
$arParams["PATH_TO_SMILE"] = strlen(trim($arParams["PATH_TO_SMILE"]))<=0 ? false : trim($arParams["PATH_TO_SMILE"]);

if (!isset($arParams["PATH_TO_CONPANY_DEPARTMENT"]) || strlen($arParams["PATH_TO_CONPANY_DEPARTMENT"]) <= 0)
	$arParams["PATH_TO_CONPANY_DEPARTMENT"] = "/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#";
if (!isset($arParams["PATH_TO_MESSAGES_CHAT"]) || strlen($arParams["PATH_TO_MESSAGES_CHAT"]) <= 0)
	$arParams["PATH_TO_MESSAGES_CHAT"] = "/company/personal/messages/chat/#user_id#/";
if (!isset($arParams["PATH_TO_VIDEO_CALL"]) || strlen($arParams["PATH_TO_VIDEO_CALL"]) <= 0)
	$arParams["PATH_TO_VIDEO_CALL"] = "/company/personal/video/#user_id#/";

$arParams["CACHE_TIME"] = 3600*24*365;

if (strlen(trim($arParams["NAME_TEMPLATE"])) <= 0)
	$arParams["NAME_TEMPLATE"] = CSite::GetNameFormat();
$arParams['SHOW_LOGIN'] = $arParams['SHOW_LOGIN'] != "N" ? "Y" : "N";
$arParams['DATE_TIME_FORMAT_S'] = $arParams['DATE_TIME_FORMAT'];
$arParams["DATE_TIME_FORMAT"] = trim(!empty($arParams['DATE_TIME_FORMAT']) ? ($arParams['DATE_TIME_FORMAT'] == 'FULL' ? $GLOBALS['DB']->DateFormatToPHP(str_replace(':SS', '', FORMAT_DATETIME)) : $arParams['DATE_TIME_FORMAT']) : $GLOBALS['DB']->DateFormatToPHP(FORMAT_DATETIME));
// activation rating
CRatingsComponentsMain::GetShowRating($arParams);
$arParams["USE_CUT"] = $arParams["USE_CUT"] == "Y" ? "Y" : "N";

$arParams["IMAGE_MAX_WIDTH"] = IntVal($arParams["IMAGE_MAX_WIDTH"]);
$arParams["IMAGE_MAX_HEIGHT"] = IntVal($arParams["IMAGE_MAX_HEIGHT"]);
if(IntVal($arParams["IMAGE_MAX_WIDTH"]) <= 0)
	$arParams["IMAGE_MAX_WIDTH"] = COption::GetOptionString("blog", "image_max_width", 600);
if(IntVal($arParams["IMAGE_MAX_HEIGHT"]) <= 0)
	$arParams["IMAGE_MAX_HEIGHT"] = COption::GetOptionString("blog", "image_max_height", 600);

$arParams["ATTACHED_IMAGE_MAX_WIDTH_SMALL"] = (IntVal($arParams["ATTACHED_IMAGE_MAX_WIDTH_SMALL"]) > 0 ? IntVal($arParams["ATTACHED_IMAGE_MAX_WIDTH_SMALL"]) : 70);
$arParams["ATTACHED_IMAGE_MAX_HEIGHT_SMALL"] = (IntVal($arParams["ATTACHED_IMAGE_MAX_HEIGHT_SMALL"]) > 0 ? IntVal($arParams["ATTACHED_IMAGE_MAX_HEIGHT_SMALL"]) : 70);
$arParams["ATTACHED_IMAGE_MAX_WIDTH_FULL"] = (IntVal($arParams["ATTACHED_IMAGE_MAX_WIDTH_FULL"]) > 0 ? IntVal($arParams["ATTACHED_IMAGE_MAX_WIDTH_FULL"]) : 1000);
$arParams["ATTACHED_IMAGE_MAX_HEIGHT_FULL"] = (IntVal($arParams["ATTACHED_IMAGE_MAX_HEIGHT_FULL"]) > 0 ? IntVal($arParams["ATTACHED_IMAGE_MAX_HEIGHT_FULL"]) : 1000);

$arParams["ALLOW_POST_CODE"] = $arParams["ALLOW_POST_CODE"] !== "N";
$arParams["SMILES_COUNT"] = IntVal($arParams["SMILES_COUNT"]);

$arParams["POST_PROPERTY"] = array("UF_BLOG_POST_DOC");
if(CModule::IncludeModule("webdav"))
	$arParams["POST_PROPERTY"][] = "UF_BLOG_POST_FILE";
if(IsModuleInstalled("vote"))
	$arParams["POST_PROPERTY"][] = "UF_BLOG_POST_VOTE";
if(IsModuleInstalled("intranet"))
	$arParams["POST_PROPERTY"][] = "UF_GRATITUDE";

if (!array_key_exists("GET_FOLLOW", $arParams) || strLen($arParams["GET_FOLLOW"]) <= 0)
	$arParams["GET_FOLLOW"] = "N";

if(defined("DisableSonetLogFollow") && DisableSonetLogFollow === true)
	$arParams["GET_FOLLOW"] = "N";

$user_id = IntVal($USER->GetID());
$arResult["USER_ID"] = $user_id;

if(!$arResult["bFromList"])
{
	$arParams["USE_CUT"] = "N";

	$arFilterblg = Array(
			"ACTIVE" => "Y",
			"USE_SOCNET" => "Y",
			"GROUP_ID" => $arParams["GROUP_ID"],
			"GROUP_SITE_ID" => SITE_ID,
			"OWNER_ID" => $arParams["USER_ID"],
		);

	$cacheTtl = 3153600;
	$cacheId = 'blog_post_blog_'.md5(serialize($arFilterblg));
	$cacheDir = '/blog/form/blog/';

	$obCache = new CPHPCache;
	if($obCache->InitCache($cacheTtl, $cacheId, $cacheDir))
	{
		$arBlog = $obCache->GetVars();
	}
	else
	{
		$obCache->StartDataCache();
		
		$dbBl = CBlog::GetList(Array(), $arFilterblg);
		$arBlog = $dbBl ->Fetch();
		if (!$arBlog && IsModuleInstalled("intranet"))
			$arBlog = CBlog::GetByOwnerID($arParams["USER_ID"]);

		$obCache->EndDataCache($arBlog);
	}

	$arResult["Blog"] = $arBlog;
}

$arPost = array();
$cacheTtl = 2592000;
$cacheId = 'blog_post_socnet_general_'.$arParams["ID"].'_'.LANGUAGE_ID;
if(($tzOffset = CTimeZone::GetOffset()) <> 0)
	$cacheId .= "_".$tzOffset;
$cacheDir = '/blog/socnet_post/gen/'.$arParams["ID"];

$obCache = new CPHPCache;
if($obCache->InitCache($cacheTtl, $cacheId, $cacheDir))
{
	$arPost = $obCache->GetVars();
}
else
{
	$obCache->StartDataCache();
	$dbPost = CBlogPost::GetList(array(), array("ID" => $arParams["ID"]), false, false, array("ID", "BLOG_ID", "PUBLISH_STATUS", "TITLE", "AUTHOR_ID", "ENABLE_COMMENTS", "NUM_COMMENTS", "VIEWS", "CODE", "MICRO", "DETAIL_TEXT", "DATE_PUBLISH", "CATEGORY_ID", "HAS_SOCNET_ALL", "HAS_TAGS", "HAS_IMAGES", "HAS_PROPS", "HAS_IMAGES", "HAS_SOCNET_ALL", "HAS_COMMENT_IMAGES"));

	$arPost = $dbPost->Fetch();
	$obCache->EndDataCache($arPost);
}

if(!empty($arPost) && ($arPost["PUBLISH_STATUS"] != BLOG_PUBLISH_STATUS_PUBLISH && !in_array($arParams["TYPE"], array("DRAFT", "MODERATION"))))
	unset($arPost);

$a = new CAccess;
$a->UpdateCodes();

if(
	(!empty($arBlog) && $arBlog["ACTIVE"] == "Y")
	|| $arResult["bFromList"]
)
{
	if(!empty($arPost))
	{
		if (
			$arParams["GET_FOLLOW"] == "Y"
			&& (
				!array_key_exists("FOLLOW", $arParams)
				|| strlen($arParams["FOLLOW"]) <= 0
				|| intval($arParams["LOG_ID"]) <= 0
			)
			&& CModule::IncludeModule("socialnetwork")
		)
		{
			$rsLogSrc = CSocNetLog::GetList(
				array(),
				array(
					"EVENT_ID" => array("blog_post", "blog_post_micro"),
					"SOURCE_ID" => $arParams["ID"]
				),
				false,
				false,
				array("ID", "FOLLOW"),
				array("USE_FOLLOW" => "Y")
			);
			if ($arLogSrc = $rsLogSrc->Fetch())
			{
				$arParams["LOG_ID"] = $arLogSrc["ID"];
				$arParams["FOLLOW"] = $arLogSrc["FOLLOW"];
			}
		}

		if(!$arResult["bFromList"])
			CBlogPost::CounterInc($arPost["ID"]);

		$arPost = CBlogTools::htmlspecialcharsExArray($arPost);
		if($arPost["AUTHOR_ID"] == $user_id)
			$arPost["perms"] = $arResult["PostPerm"] = BLOG_PERMS_FULL;
		elseif(IsModuleInstalled("intranet") && $arResult["bFromList"])
		{
			$arPost["perms"] = $arResult["PostPerm"] = BLOG_PERMS_READ;
			if (CSocNetUser::IsCurrentUserModuleAdmin() || $APPLICATION->GetGroupRight("blog") >= "W")
				$arPost["perms"] = $arResult["PostPerm"] = BLOG_PERMS_FULL;
		}
		else
			$arPost["perms"] = $arResult["PostPerm"] = CBlogPost::GetSocNetPostPerms($arPost["ID"], true, $user_id, $arPost["AUTHOR_ID"]);

		$arResult["Post"] = $arPost;
		$arResult["PostSrc"] = $arPost;
		$arResult["Blog"] = $arBlog;

		$arResult["urlToPost"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_POST"], array("post_id"=>CBlogPost::GetPostID($arResult["Post"]["ID"], $arResult["Post"]["CODE"], $arParams["ALLOW_POST_CODE"]), "user_id" => $arPost["AUTHOR_ID"]));

		if ($_GET["delete"]=="Y" && !$arResult["bFromList"])
		{
			if (check_bitrix_sessid())
			{
				if($arResult["PostPerm"] >= BLOG_PERMS_FULL)
				{
					CBlogPost::DeleteLog($arParams["ID"]);

					if (CBlogPost::Delete($arParams["ID"]))
					{
						BXClearCache(True, "/".SITE_ID."/blog/popular_posts/");
						$url = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BLOG"], array("blog" => $arBlog["URL"], "user_id" => $arBlog["OWNER_ID"], "group_id" => $arBlog["SOCNET_GROUP_ID"]));
						if(strpos($url, "?") === false)
							$url .= "?";
						else
							$url .= "&";
						$url .= "del_id=".$arParams["ID"]."&success=Y";
						BXClearCache(true, "/blog/socnet_post/".$arParams["ID"]."/");

						LocalRedirect($url);
					}
					else
						$arResult["ERROR_MESSAGE"] .= GetMessage("BLOG_BLOG_BLOG_MES_DEL_ERROR").'<br />';
				}
				$arResult["ERROR_MESSAGE"] .= GetMessage("BLOG_BLOG_BLOG_MES_DEL_NO_RIGHTS").'<br />';
			}
			else
				$arResult["ERROR_MESSAGE"] .= GetMessage("BLOG_BLOG_SESSID_WRONG").'<br />';
		}
		if ($_GET["hide"]=="Y" && !$arResult["bFromList"])
		{
			if (check_bitrix_sessid())
			{
				if($arResult["PostPerm"]>=BLOG_PERMS_MODERATE)
				{
					if(CBlogPost::Update($arParams["ID"], Array("PUBLISH_STATUS" => BLOG_PUBLISH_STATUS_READY)))
					{
						BXClearCache(True, "/".SITE_ID."/blog/popular_posts/");
						BXClearCache(true, "/blog/socnet_post/".$arParams["ID"]."/");
						CBlogPost::DeleteLog($arParams["ID"]);
						$url = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BLOG"], array("user_id" => $arBlog["OWNER_ID"]));
						if(strpos($url, "?") === false)
							$url .= "?";
						else
							$url .= "&";
						$url .= "hide_id=".$arParams["ID"]."&success=Y";

						LocalRedirect($url);
					}
					else
						$arResult["ERROR_MESSAGE"] .= GetMessage("BLOG_BLOG_BLOG_MES_HIDE_ERROR").'<br />';
				}
				else
					$arResult["ERROR_MESSAGE"] .= GetMessage("BLOG_BLOG_BLOG_MES_HIDE_NO_RIGHTS").'<br />';
			}
			else
				$arResult["ERROR_MESSAGE"] .= GetMessage("BLOG_BLOG_SESSID_WRONG").'<br />';
		}

		if($arResult["PostPerm"] > BLOG_PERMS_DENY)
		{
			if($arPost["MICRO"] != "Y" && !CModule::IncludeModule("intranet") && !$arResult["bFromList"])
				$APPLICATION->SetTitle($arPost["TITLE"]);

			if($arParams["SET_NAV_CHAIN"]=="Y")
				$APPLICATION->AddChainItem($arBlog["NAME"], CComponentEngine::MakePathFromTemplate(htmlspecialcharsback($arParams["PATH_TO_BLOG"]), array("blog" => $arBlog["URL"], "user_id" => $arPost["AUTHOR_ID"], "group_id" => $arParams["SOCNET_GROUP_ID"])));

			$cache = new CPHPCache;
			$cache_id = "blog_socnet_post_".$arParams["MOBILE"]."_".$arParams["USE_CUT"];
			if(($tzOffset = CTimeZone::GetOffset()) <> 0)
				$cache_id .= "_".$tzOffset;
			$cache_path = "/blog/socnet_post/".$arPost["ID"]."/";

			if ($arParams["CACHE_TIME"] > 0 && $cache->InitCache($arParams["CACHE_TIME"], $cache_id, $cache_path))
			{
				$Vars = $cache->GetVars();
				$arResult["POST_PROPERTY"] = $Vars["POST_PROPERTY"];
				$arResult["Post"] = $Vars["Post"];
				$arResult["images"] = $Vars["images"];
				$arResult["Category"] = $Vars["Category"];
				$arResult["GRATITUDE"] = $Vars["GRATITUDE"];
				$arResult["POST_PROPERTIES"] = $Vars["POST_PROPERTIES"];
				$arResult["arUser"] = $Vars["arUser"];

				CBitrixComponentTemplate::ApplyCachedData($Vars["templateCachedData"]);
				$cache->Output();
			}
			else
			{
				if ($arParams["CACHE_TIME"] > 0)
				{
					$cache->StartDataCache($arParams["CACHE_TIME"], $cache_id, $cache_path);
					if (defined("BX_COMP_MANAGED_CACHE"))
					{
						$GLOBALS["CACHE_MANAGER"]->StartTagCache($cache_path);
						$GLOBALS["CACHE_MANAGER"]->RegisterTag("USER_CARD_".intval($arPost["AUTHOR_ID"] / 100));
					}
				}

				$p = new blogTextParser(false, $arParams["PATH_TO_SMILE"]);

				$arResult["POST_PROPERTIES"] = array("SHOW" => "N");

				$bHasImg = false;
				$bHasTag = false;
				$bHasProps = false;
				$bHasOnlyAll = false;

				if (!empty($arParams["POST_PROPERTY"]))
				{
					if($arPost["HAS_PROPS"] != "N")
					{
						$arPostFields = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("BLOG_POST", $arPost["ID"], LANGUAGE_ID);

						if (count($arParams["POST_PROPERTY"]) > 0)
						{
							foreach ($arPostFields as $FIELD_NAME => $arPostField)
							{
								if (!in_array($FIELD_NAME, $arParams["POST_PROPERTY"]))
									continue;
								elseif(
									$FIELD_NAME == "UF_GRATITUDE"
									&& array_key_exists("VALUE", $arPostField)
									&& intval($arPostField["VALUE"]) > 0
								)
								{
									$bHasProps = true;
									$gratValue = $arPostField["VALUE"];

									if (CModule::IncludeModule("iblock"))
									{
										if (
											!is_array($GLOBALS["CACHE_HONOUR"])
											|| !array_key_exists("honour_iblock_id", $GLOBALS["CACHE_HONOUR"])
											|| intval($GLOBALS["CACHE_HONOUR"]["honour_iblock_id"]) <= 0
										)
										{
											$rsIBlock = CIBlock::GetList(array(), array("=CODE" => "honour", "=TYPE" => "structure"));
											if ($arIBlock = $rsIBlock->Fetch())
												$GLOBALS["CACHE_HONOUR"]["honour_iblock_id"] = $arIBlock["ID"];
										}

										if (intval($GLOBALS["CACHE_HONOUR"]["honour_iblock_id"]) > 0)
										{
											$arGrat = array(
												"USERS" => array(),
												"USERS_FULL" => array(),
												"TYPE" => false
											);
											$rsElementProperty = CIBlockElement::GetProperty(
												$GLOBALS["CACHE_HONOUR"]["honour_iblock_id"],
												$gratValue
											);
											while ($arElementProperty = $rsElementProperty->GetNext())
											{
												if ($arElementProperty["CODE"] == "USERS")
													$arGrat["USERS"][] = $arElementProperty["VALUE"];
												elseif ($arElementProperty["CODE"] == "GRATITUDE")
												{
													$arGrat["TYPE"] = array(
														"VALUE_ENUM" => $arElementProperty["VALUE_ENUM"],
														"XML_ID" => $arElementProperty["VALUE_XML_ID"]
													);
												}
											}

											if (count($arGrat["USERS"]) > 0)
											{
												$grat_avatar_size = ($arParams["MOBILE"] == "Y" ? 58 : (count($arGrat["USERS"]) <= 4 ? 42 : 17));

												if ($arParams["CACHE_TIME"] > 0 && defined("BX_COMP_MANAGED_CACHE"))
													foreach($arGrat["USERS"] as $i => $grat_user_id)
														$GLOBALS["CACHE_MANAGER"]->RegisterTag("USER_CARD_".intval($grat_user_id / 100));

												$arGratUsers = array();

												$rsUser = CUser::GetList(
													($by = ""),
													($ord = ""),
													array(
														"ID" => implode("|", $arGrat["USERS"])
													),
													array(
														"FIELDS" => array(
															"ID",
															"PERSONAL_GENDER", "PERSONAL_PHOTO",
															"LOGIN", "NAME", "LAST_NAME", "SECOND_NAME", "EMAIL",
															"WORK_POSITION"
														)
													)
												);

												while ($arGratUser = $rsUser->Fetch())
												{
													$arGratUser["AVATAR_SRC"] = CSocNetLogTools::FormatEvent_CreateAvatar($arGratUser, array("AVATAR_SIZE" => $grat_avatar_size), "");
													$arGratUser["URL"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $arGratUser["ID"]));
													$arGratUsers[] = $arGratUser;
												}

												$arGrat["USERS_FULL"] = $arGratUsers;
											}
											if (count($arGrat["USERS_FULL"]) > 0)
												$arResult["GRATITUDE"] = $arGrat;
										}
									}
								}
								else
								{
									$arPostField["EDIT_FORM_LABEL"] = strLen($arPostField["EDIT_FORM_LABEL"]) > 0 ? $arPostField["EDIT_FORM_LABEL"] : $arPostField["FIELD_NAME"];
									$arPostField["EDIT_FORM_LABEL"] = htmlspecialcharsEx($arPostField["EDIT_FORM_LABEL"]);
									$arPostField["~EDIT_FORM_LABEL"] = $arPostField["EDIT_FORM_LABEL"];
									$arResult["POST_PROPERTIES"]["DATA"][$FIELD_NAME] = $arPostField;

									if(!empty($arPostField["VALUE"]))
										$bHasProps = true;
								}
							}
						}
						if (!empty($arResult["POST_PROPERTIES"]["DATA"]))
							$arResult["POST_PROPERTIES"]["SHOW"] = "Y";
					}
				}

				if($arPost["HAS_IMAGES"] != "N")
				{
					$res = CBlogImage::GetList(array("ID"=>"ASC"),array("POST_ID"=>$arPost['ID'], "IS_COMMENT" => "N"));
					while ($arImage = $res->Fetch())
					{
						$bHasImg = true;
						$arImages[$arImage['ID']] = $arImage['FILE_ID'];
						$arResult["images"][$arImage['ID']] = Array(
							"small" => "/bitrix/components/bitrix/blog/show_file.php?fid=".$arImage['ID']."&width=".$arParams["ATTACHED_IMAGE_MAX_WIDTH_SMALL"]."&height=".$arParams["ATTACHED_IMAGE_MAX_HEIGHT_SMALL"]."&type=square"
						);

						if ($arParams["MOBILE"] == "Y")
							$arResult["images"][$arImage['ID']]["full"] = SITE_DIR."mobile/log/blog_image.php?bfid=".$arImage['ID']."&fid=".$arImage['FILE_ID']."&width=".$arParams["ATTACHED_IMAGE_MAX_WIDTH_FULL"]."&height=".$arParams["ATTACHED_IMAGE_MAX_HEIGHT_FULL"];
						else
							$arResult["images"][$arImage['ID']]["full"] = "/bitrix/components/bitrix/blog/show_file.php?fid=".$arImage['ID']."&width=".$arParams["ATTACHED_IMAGE_MAX_WIDTH_FULL"]."&height=".$arParams["ATTACHED_IMAGE_MAX_HEIGHT_FULL"];
					}
				}

				$arParserParams = Array(
					"imageWidth" => $arParams["IMAGE_MAX_WIDTH"],
					"imageHeight" => $arParams["IMAGE_MAX_HEIGHT"],
					"pathToUser" => $arParams["PATH_TO_USER"],
				);

				$arAllow = array("HTML" => "N", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "Y", "QUOTE" => "Y", "CODE" => "Y", "FONT" => "Y", "LIST" => "Y", "SMILES" => "Y", "NL2BR" => "N", "VIDEO" => "Y", "USER" => "Y", "TAG" => "Y", "SHORT_ANCHOR" => "Y");
				if(COption::GetOptionString("blog","allow_video", "Y") != "Y")
					$arAllow["VIDEO"] = "N";

				if (is_array($arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"]))
					$p->arUserfields = array("UF_BLOG_POST_FILE" => array_merge($arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"], array("TAG" => "DOCUMENT ID")));
				$arResult["Post"]["textFormated"] = $p->convert($arPost["~DETAIL_TEXT"], ($arParams["USE_CUT"] == "Y" ? true : false), $arImages, $arAllow, $arParserParams);

				$arResult["Post"]["TITLE"] = htmlspecialcharsbx($arResult["Post"]["TITLE"]);

				if (is_array($arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"]) &&
					is_array($arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"]["VALUE"]) &&
					is_array($p->arUserfields["UF_BLOG_POST_FILE"]["PARSED"]))
					$arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"]["VALUE"] = array_diff(
						$arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"]["VALUE"],
						$p->arUserfields["UF_BLOG_POST_FILE"]["PARSED"]);

				if ($arParams["USE_CUT"] == "Y" && preg_match("/(\[CUT\])/i",$arPost['~DETAIL_TEXT']))
					$arResult["Post"]["CUT"] = "Y";

				if(!empty($p->showedImages) && !empty($arResult["images"]))
				{
					foreach($p->showedImages as $val)
					{
						if(!empty($arResult["images"][$val]))
							unset($arResult["images"][$val]);
					}
				}
				$arResult["Post"]["DATE_PUBLISH_FORMATED"] = FormatDateFromDB($arResult["Post"]["DATE_PUBLISH"], $arParams["DATE_TIME_FORMAT"], true);
				$arResult["Post"]["DATE_PUBLISH_DATE"] = FormatDateFromDB($arResult["Post"]["DATE_PUBLISH"], FORMAT_DATE);
				if (strcasecmp(LANGUAGE_ID, 'EN') !== 0 && strcasecmp(LANGUAGE_ID, 'DE') !== 0)
				{
					$arResult["Post"]["DATE_PUBLISH_FORMATED"] = ToLower($arResult["Post"]["DATE_PUBLISH_FORMATED"]);
					$arResult["Post"]["DATE_PUBLISH_DATE"] = ToLower($arResult["Post"]["DATE_PUBLISH_DATE"]);
				}
				// strip current year
				if (!empty($arParams['DATE_TIME_FORMAT_S']) && ($arParams['DATE_TIME_FORMAT_S'] == 'j F Y G:i' || $arParams['DATE_TIME_FORMAT_S'] == 'j F Y g:i a'))
				{
					$arResult["Post"]["DATE_PUBLISH_FORMATED"] = ltrim($arResult["Post"]["DATE_PUBLISH_FORMATED"], '0');
					$arResult["Post"]["DATE_PUBLISH_DATE"] = ltrim($arResult["Post"]["DATE_PUBLISH_DATE"], '0');
					$curYear = date('Y');
					$arResult["Post"]["DATE_PUBLISH_FORMATED"] = str_replace(array('-'.$curYear, '/'.$curYear, ' '.$curYear, '.'.$curYear), '', $arResult["Post"]["DATE_PUBLISH_FORMATED"]);
					$arResult["Post"]["DATE_PUBLISH_DATE"] = str_replace(array('-'.$curYear, '/'.$curYear, ' '.$curYear, '.'.$curYear), '', $arResult["Post"]["DATE_PUBLISH_DATE"]);
				}
				$arResult["Post"]["DATE_PUBLISH_TIME"] = FormatDateFromDB($arResult["Post"]["DATE_PUBLISH"], (strpos($arParams["DATE_TIME_FORMAT"], 'a') !== false || ($arParams["DATE_TIME_FORMAT"] == 'FULL' && IsAmPmMode()) !== false ? 'G:MI T' : 'GG:MI'));

				$arResult["arUser"] = CBlogUser::GetUserInfo($arPost["AUTHOR_ID"], $arParams["PATH_TO_USER"], array("AVATAR_SIZE" => $arParams["AVATAR_SIZE"], "AVATAR_SIZE_COMMENT" => $arParams["AVATAR_SIZE_COMMENT"]));

				$arResult["Post"]["urlToPost"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_POST"], array("post_id"=> CBlogPost::GetPostID($arPost["ID"], $arPost["CODE"], $arParams["ALLOW_POST_CODE"]), "user_id" => $arPost["AUTHOR_ID"]));

				if(strlen($arPost["CATEGORY_ID"])>0)
				{
					$bHasTag = true;
					$arCategory = explode(",", $arPost["CATEGORY_ID"]);
					$dbCategory = CBlogCategory::GetList(Array(), Array("@ID" => $arCategory));
					while($arCatTmp = $dbCategory->Fetch())
					{
						$arCatTmp["~NAME"] = $arCatTmp["NAME"];
						$arCatTmp["NAME"] = htmlspecialcharsbx($arCatTmp["NAME"]);
						$arCatTmp["urlToCategory"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_SEARCH_TAG"], array("tag" => $arCatTmp["NAME"]));
						$arResult["Category"][] = $arCatTmp;
					}
				}

				$bAll = false;
				$arResult["Post"]["SPERM"] = Array();
				if($arPost["HAS_SOCNET_ALL"] != "Y")
				{
					$arSPERM = CBlogPost::GetSocnetPermsName($arResult["Post"]["ID"]);
					foreach($arSPERM as $type => $v)
					{
						foreach($v as $vv)
						{
							$name = "";
							$link = "";
							$id = "";
							if($type == "SG")
							{
								if($arSocNetGroup = CSocNetGroup::GetByID($vv["ENTITY_ID"]))
								{
									$name = $arSocNetGroup["NAME"];
									$link = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP"], array("group_id" => $vv["ENTITY_ID"]));
								}
							}
							elseif($type == "U")
							{
								if(in_array("US".$vv["ENTITY_ID"], $vv["ENTITY"]))
								{
									$name = "All";
									$bAll = true;
								}
								else
								{
									$arTmpUser = array(
										"NAME" => $vv["~U_NAME"],
										"LAST_NAME" => $vv["~U_LAST_NAME"],
										"SECOND_NAME" => $vv["~U_SECOND_NAME"],
										"LOGIN" => $vv["~U_LOGIN"],
										"NAME_LIST_FORMATTED" => "",
									);
									$name = CUser::FormatName($arParams["NAME_TEMPLATE"], $arTmpUser, ($arParams["SHOW_LOGIN"] != "N" ? true : false));
									$id = $vv["ENTITY_ID"];
									$link = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $vv["ENTITY_ID"]));
								}
							}
							elseif($type == "DR")
								$name = $vv["EL_NAME"];

							if(strlen($name) > 0)
								$arResult["Post"]["SPERM"][$type][$vv["ENTITY_ID"]] = Array("NAME" => $name, "URL" => $link, "ID" => $id);
						}
					}

					if(count($arResult["Post"]["SPERM"]) == 1 && count($arResult["Post"]["SPERM"]["U"]) == 1 && $bAll)
						$bHasOnlyAll = true;
				}
				else
				{
					$arResult["Post"]["SPERM"]["U"][1] = Array("NAME" => "All", "URL" => "", "ID" => "");
				}

				$arFieldsHave = array();
				if($arPost["HAS_IMAGES"] == "")
					$arFieldsHave["HAS_IMAGES"] = ($bHasImg ? "Y" : "N");
				if($arPost["HAS_TAGS"] == "")
					$arFieldsHave["HAS_TAGS"] = ($bHasTag ? "Y" : "N");
				if($arPost["HAS_PROPS"] == "")
					$arFieldsHave["HAS_PROPS"] = ($bHasProps ? "Y" : "N");
				if($arPost["HAS_SOCNET_ALL"] == "")
					$arFieldsHave["HAS_SOCNET_ALL"] = ($bHasOnlyAll ? "Y" : "N");

				if(!empty($arFieldsHave))
					CBlogPost::Update($arPost["ID"], $arFieldsHave);

				if($bAll || $arPost["HAS_SOCNET_ALL"] == "Y")
					$arResult["Post"]["HAVE_ALL_IN_ADR"] = "Y";

				if ($arParams["CACHE_TIME"] > 0)
				{
					$arCacheData = Array(
							"templateCachedData" => $this->GetTemplateCachedData(),
							"Post" => $arResult["Post"],
							"images" => $arResult["images"],
							"Category" => $arResult["Category"],
							"GRATITUDE" => $arResult["GRATITUDE"],
							"POST_PROPERTIES" => $arResult["POST_PROPERTIES"],
							"arUser" => $arResult["arUser"],
						);
					if(defined("BX_COMP_MANAGED_CACHE"))
						$GLOBALS["CACHE_MANAGER"]->EndTagCache();
					$cache->EndDataCache($arCacheData);
				}
			}
			foreach ($arResult["Post"]["SPERM"] as $key => $value) 
			{
				foreach($value as $kk => $vv)
				{
					$arResult["PostSrc"]["SPERM"][$key][] = $kk;
				}
			}
			$arResult["PostSrc"]["HAVE_ALL_IN_ADR"] = $arResult["Post"]["HAVE_ALL_IN_ADR"];

			if ($arParams["MOBILE"] != "Y")
			{
				if (
					$arParams["CHECK_PERMISSIONS_DEST"] == "N"
					&& !CSocNetUser::IsCurrentUserModuleAdmin()
					&& is_object($GLOBALS["USER"])
				)
				{
					$arResult["Post"]["SPERM_HIDDEN"] = 0;

					$arGroupID = array();

					if (!empty($GLOBALS["SONET_GROUPS_ID_AVAILABLE"]))
						$arGroupID = $GLOBALS["SONET_GROUPS_ID_AVAILABLE"];
					else
					{
						// get tagged cached available groups and intersect
						$cache = new CPHPCache;
						$cache_id = $GLOBALS["USER"]->GetID();
						$cache_path = "/sonet/groups_available/";

						if (
							$cache->InitCache($arParams["CACHE_TIME"], $cache_id, $cache_path)
						)
						{
							$arCacheVars = $cache->GetVars();
							$arGroupID = $arCacheVars["arGroupID"];
						}
						else
						{
							$cache->StartDataCache($arParams["CACHE_TIME"], $cache_id, $cache_path);
							if (defined("BX_COMP_MANAGED_CACHE"))
							{
								$GLOBALS["CACHE_MANAGER"]->StartTagCache($cache_path);
								$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_user2group_U".$GLOBALS["USER"]->GetID());
								$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_group");
							}

							$rsGroup = CSocNetGroup::GetList(
								array(),
								array("CHECK_PERMISSIONS" => $GLOBALS["USER"]->GetID()),
								false,
								false,
								array("ID")
							);
							while($arGroup = $rsGroup->Fetch())
								$arGroupID[] = $arGroup["ID"];

							$arCacheData = array(
								"arGroupID" => $arGroupID
							);
							if(defined("BX_COMP_MANAGED_CACHE"))
								$GLOBALS["CACHE_MANAGER"]->EndTagCache();
							$cache->EndDataCache($arCacheData);
						}

						$GLOBALS["SONET_GROUPS_ID_AVAILABLE"] = $arGroupID;
					}

					foreach($arResult["Post"]["SPERM"] as $group_code => $arBlogSPerm)
					{
						foreach($arBlogSPerm as $entity_id => $arBlogSPermDesc)
						{
							if (
								$group_code == "SG"
								&& !in_array($entity_id, $arGroupID)
							)
							{
								unset($arResult["Post"]["SPERM"][$group_code][$entity_id]);
								$arResult["Post"]["SPERM_HIDDEN"]++;
							}
						}
					}
				}
			}

			if($arResult["PostPerm"] > BLOG_PERMS_MODERATE || ($arResult["PostPerm"]>=BLOG_PERMS_WRITE && $arPost["AUTHOR_ID"] == $arResult["USER_ID"]))
			{
				$arResult["urlToEdit"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_POST_EDIT"], array("post_id"=>$arPost["ID"], "user_id" => $arPost["AUTHOR_ID"]));
				if(in_array($arParams["TYPE"], array("DRAFT", "MODERATION")))
					$arResult["Post"]["urlToPost"] = $arResult["urlToEdit"];
			}

			if($arParams["FROM_LOG"] != "Y")
			{
				if($arResult["PostPerm"]>=BLOG_PERMS_MODERATE)
					$arResult["urlToHide"] = htmlspecialcharsex($APPLICATION->GetCurPageParam("hide=Y"."&".bitrix_sessid_get(), Array("sessid", "success", "hide", "delete")));
				if($arResult["PostPerm"] >= BLOG_PERMS_FULL)
				{
					if(in_array($arParams["TYPE"], array("DRAFT", "MODERATION")))
					{
						$arResult["urlToDelete"] = $arResult["urlToEdit"];
						if(strpos($arResult["urlToDelete"], "?") === false)
							$arResult["urlToDelete"] .= "?";
						else
							$arResult["urlToDelete"] .= "&";
						$arResult["urlToDelete"] .= "delete_blog_post_id=#del_post_id#&ajax_blog_post_delete=Y"."&".bitrix_sessid_get();

					}
					else
						$arResult["urlToDelete"] = htmlspecialcharsex($APPLICATION->GetCurPageParam("delete=Y"."&".bitrix_sessid_get(), Array("sessid", "delete", "hide", "success")));
					$arResult["canDelete"] = "Y";
				}
			}
			else
			{
				if($arResult["PostPerm"] >= BLOG_PERMS_FULL)
				{
					$arResult["urlToDelete"] = htmlspecialcharsex($APPLICATION->GetCurPageParam("delete_blog_post_id=#del_post_id#&ajax_blog_post_delete=Y"."&".bitrix_sessid_get(), Array("sessid", "delete", "hide", "success")));
					$arResult["canDelete"] = "Y";
				}
			}



			if($arParams["SHOW_RATING"] == "Y" && !empty($arResult["Post"]))
			{
				if (
					array_key_exists("RATING_ENTITY_ID", $arParams)
					&& intval($arParams["RATING_ENTITY_ID"]) > 0
					&& array_key_exists("RATING_TOTAL_VALUE", $arParams)
					&& is_numeric($arParams["RATING_TOTAL_VALUE"])
					&& array_key_exists("RATING_TOTAL_VOTES", $arParams)
					&& intval($arParams["RATING_TOTAL_VOTES"]) >= 0
					&& array_key_exists("RATING_TOTAL_POSITIVE_VOTES", $arParams)
					&& intval($arParams["RATING_TOTAL_POSITIVE_VOTES"]) >= 0
					&& array_key_exists("RATING_TOTAL_NEGATIVE_VOTES", $arParams)
					&& intval($arParams["RATING_TOTAL_NEGATIVE_VOTES"]) >= 0
					&& array_key_exists("RATING_USER_VOTE_VALUE", $arParams)
					&& is_numeric($arParams["RATING_USER_VOTE_VALUE"])
				)
					$arResult['RATING'][$arResult["Post"]["ID"]] = array(
						"USER_VOTE" => $arParams["RATING_USER_VOTE_VALUE"],
						"USER_HAS_VOTED" => ($arParams["RATING_USER_VOTE_VALUE"] == 0 ? "N" : "Y"),
						"TOTAL_VOTES" => $arParams["RATING_TOTAL_VOTES"],
						"TOTAL_POSITIVE_VOTES" => $arParams["RATING_TOTAL_POSITIVE_VOTES"],
						"TOTAL_NEGATIVE_VOTES" => $arParams["RATING_TOTAL_NEGATIVE_VOTES"],
						"TOTAL_VALUE" => $arParams["RATING_TOTAL_VALUE"]
					);
				else
					$arResult['RATING'][$arResult["Post"]["ID"]] = CRatings::GetRatingVoteResult('BLOG_POST', $arResult["Post"]["ID"]);
			}

			if ($arParams["IS_UNREAD"])
				$arResult["Post"]["new"] = "Y";

			if ($arParams["IS_HIDDEN"])
				$arResult["Post"]["hidden"] = "Y";
		}
		else
			$arResult["FATAL_MESSAGE"] .= GetMessage("B_B_MES_NO_RIGHTS")."<br />";
	}
	elseif(!$arResult["bFromList"])
	{
		$arResult["FATAL_MESSAGE"] = GetMessage("B_B_MES_NO_POST");
		CHTTP::SetStatus("404 Not Found");
	}
}
else
{
	$arResult["FATAL_MESSAGE"] = GetMessage("B_B_MES_NO_BLOG");
	CHTTP::SetStatus("404 Not Found");
}

$this->IncludeComponentTemplate();

if ($arParams["RETURN_DATA"] == "Y")
{
	return array(
		"BLOG_DATA" => $arResult["Blog"],
		"POST_DATA" => $arResult["PostSrc"]
	);
}
?>