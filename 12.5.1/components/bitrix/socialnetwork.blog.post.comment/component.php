<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!CModule::IncludeModule("blog"))
{
	ShowError(GetMessage("BLOG_MODULE_NOT_INSTALL"));
	return;
}
$arParams["SOCNET_GROUP_ID"] = IntVal($arParams["SOCNET_GROUP_ID"]);

CModule::IncludeModule("socialnetwork");

$arParams["ID"] = trim($arParams["ID"]);
if(preg_match("/^[1-9][0-9]*\$/", $arParams["ID"]))
{
	$arParams["ID"] = IntVal($arParams["ID"]);
	$bIDbyCode = false;
}
else
{
	$arParams["ID"] = preg_replace("/[^a-zA-Z0-9_-]/is", "", Trim($arParams["~ID"]));
	$bIDbyCode = true;
}

$arParams["BLOG_URL"] = preg_replace("/[^a-zA-Z0-9_-]/is", "", Trim($arParams["BLOG_URL"]));
if(!is_array($arParams["GROUP_ID"]))
	$arParams["GROUP_ID"] = array($arParams["GROUP_ID"]);
foreach($arParams["GROUP_ID"] as $k=>$v)
	if(IntVal($v) <= 0)
		unset($arParams["GROUP_ID"][$k]);

if ($arParams["CACHE_TYPE"] == "Y" || ($arParams["CACHE_TYPE"] == "A" && COption::GetOptionString("main", "component_cache_on", "Y") == "Y"))
	$arParams["CACHE_TIME"] = intval($arParams["CACHE_TIME"]);
else
	$arParams["CACHE_TIME"] = 0;

if(strLen($arParams["BLOG_VAR"])<=0)
	$arParams["BLOG_VAR"] = "blog";
if(strLen($arParams["PAGE_VAR"])<=0)
	$arParams["PAGE_VAR"] = "page";
if(strLen($arParams["USER_VAR"])<=0)
	$arParams["USER_VAR"] = "id";
if(strLen($arParams["POST_VAR"])<=0)
	$arParams["POST_VAR"] = "id";
if(strLen($arParams["NAV_PAGE_VAR"])<=0)
	$arParams["NAV_PAGE_VAR"] = "pagen";
if(strLen($arParams["COMMENT_ID_VAR"])<=0)
	$arParams["COMMENT_ID_VAR"] = "commentId";
if(IntVal($_GET[$arParams["NAV_PAGE_VAR"]])>0)
	$pagen = IntVal($_REQUEST[$arParams["NAV_PAGE_VAR"]]);
else
	$pagen = 1;

if(IntVal($arParams["COMMENTS_COUNT"])<=0)
	$arParams["COMMENTS_COUNT"] = 25;

if($arParams["USE_ASC_PAGING"] != "Y")
	$arParams["USE_DESC_PAGING"] = "Y";

$applicationPage = $APPLICATION->GetCurPage();

$arParams["PATH_TO_BLOG"] = trim($arParams["PATH_TO_BLOG"]);
if(strlen($arParams["PATH_TO_BLOG"])<=0)
	$arParams["PATH_TO_BLOG"] = htmlspecialcharsbx($applicationPage."?".$arParams["PAGE_VAR"]."=blog&".$arParams["BLOG_VAR"]."=#blog#");

$arParams["PATH_TO_USER"] = trim($arParams["PATH_TO_USER"]);
if(strlen($arParams["PATH_TO_USER"])<=0)
	$arParams["PATH_TO_USER"] = htmlspecialcharsbx($applicationPage."?".$arParams["PAGE_VAR"]."=user&".$arParams["USER_VAR"]."=#user_id#");

$arParams["PATH_TO_POST"] = trim($arParams["PATH_TO_POST"]);
if(strlen($arParams["PATH_TO_POST"])<=0)
	$arParams["PATH_TO_POST"] = htmlspecialcharsbx($applicationPage."?".$arParams["PAGE_VAR"]."=post&".$arParams["BLOG_VAR"]."=#blog#"."&".$arParams["POST_VAR"]."=#post_id#");

$arParams["PATH_TO_SMILE"] = strlen(trim($arParams["PATH_TO_SMILE"]))<=0 ? false : trim($arParams["PATH_TO_SMILE"]);

if (!isset($arParams["PATH_TO_CONPANY_DEPARTMENT"]) || $arParams["PATH_TO_CONPANY_DEPARTMENT"] == "")
	$arParams["PATH_TO_CONPANY_DEPARTMENT"] = "/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#";
if (!isset($arParams["PATH_TO_MESSAGES_CHAT"]) || $arParams["PATH_TO_MESSAGES_CHAT"] == "")
	$arParams["PATH_TO_MESSAGES_CHAT"] = "/company/personal/messages/chat/#user_id#/";
if (!isset($arParams["PATH_TO_VIDEO_CALL"]) || $arParams["PATH_TO_VIDEO_CALL"] == "")
	$arParams["PATH_TO_VIDEO_CALL"] = "/company/personal/video/#user_id#/";

if (strlen(trim($arParams["NAME_TEMPLATE"])) <= 0)
	$arParams["NAME_TEMPLATE"] = CSite::GetNameFormat();
$arParams['SHOW_LOGIN'] = $arParams['SHOW_LOGIN'] != "N" ? "Y" : "N";
$arParams["IMAGE_MAX_WIDTH"] = IntVal($arParams["IMAGE_MAX_WIDTH"]);
$arParams["IMAGE_MAX_HEIGHT"] = IntVal($arParams["IMAGE_MAX_HEIGHT"]);
$arParams["ALLOW_POST_CODE"] = $arParams["ALLOW_POST_CODE"] !== "N";

$arParams["ATTACHED_IMAGE_MAX_WIDTH_SMALL"] = (IntVal($arParams["ATTACHED_IMAGE_MAX_WIDTH_SMALL"]) > 0 ? IntVal($arParams["ATTACHED_IMAGE_MAX_WIDTH_SMALL"]) : 70);
$arParams["ATTACHED_IMAGE_MAX_HEIGHT_SMALL"] = (IntVal($arParams["ATTACHED_IMAGE_MAX_HEIGHT_SMALL"]) > 0 ? IntVal($arParams["ATTACHED_IMAGE_MAX_HEIGHT_SMALL"]) : 70);
$arParams["ATTACHED_IMAGE_MAX_WIDTH_FULL"] = (IntVal($arParams["ATTACHED_IMAGE_MAX_WIDTH_FULL"]) > 0 ? IntVal($arParams["ATTACHED_IMAGE_MAX_WIDTH_FULL"]) : 1000);
$arParams["ATTACHED_IMAGE_MAX_HEIGHT_FULL"] = (IntVal($arParams["ATTACHED_IMAGE_MAX_HEIGHT_FULL"]) > 0 ? IntVal($arParams["ATTACHED_IMAGE_MAX_HEIGHT_FULL"]) : 1000);

$commentUrlID = IntVal($_REQUEST[$arParams["COMMENT_ID_VAR"]]);

$arParams["DATE_TIME_FORMAT_S"] = $arParams["DATE_TIME_FORMAT"];
$arParams["DATE_TIME_FORMAT"] = trim(!empty($arParams['DATE_TIME_FORMAT']) ? ($arParams['DATE_TIME_FORMAT'] == 'FULL' ? $GLOBALS['DB']->DateFormatToPHP(str_replace(':SS', '', FORMAT_DATETIME)) : $arParams['DATE_TIME_FORMAT']) : $GLOBALS['DB']->DateFormatToPHP(FORMAT_DATETIME));
CRatingsComponentsMain::GetShowRating($arParams);

$arParams["ALLOW_VIDEO"] = ($arParams["ALLOW_VIDEO"] == "Y" ? "Y" : "N");
if(COption::GetOptionString("blog","allow_video", "Y") == "Y" && $arParams["ALLOW_VIDEO"] == "Y")
	$arResult["allowVideo"] = true;

if($arParams["ALLOW_IMAGE_UPLOAD"] == "A" || ($arParams["ALLOW_IMAGE_UPLOAD"] == "R" && $USER->IsAuthorized()) || empty($arParams["ALLOW_IMAGE_UPLOAD"]))
	$arResult["allowImageUpload"] = true;

$arResult["userID"] = $user_id = $USER->GetID();
$arResult["canModerate"] = false;
$arResult["ajax_comment"] = 0;
$arResult["is_ajax_post"] = "N";

$arResult["TZ_OFFSET"] = CTimeZone::GetOffset();

$a = new CAccess;
$a->UpdateCodes();

$arParams["PAGE_SIZE"] = intval($arParams["PAGE_SIZE"]);
if($arParams["PAGE_SIZE"] <= 0)
	$arParams["PAGE_SIZE"] = 20;

if($arParams["NO_URL_IN_COMMENTS"] == "L")
{
	$arResult["NoCommentUrl"] = true;
	$arResult["NoCommentReason"] = GetMessage("B_B_PC_MES_NOCOMMENTREASON_L");
}
if(!$USER->IsAuthorized() && $arParams["NO_URL_IN_COMMENTS"] == "A")
{
	$arResult["NoCommentUrl"] = true;
	$arResult["NoCommentReason"] = GetMessage("B_B_PC_MES_NOCOMMENTREASON_A");
}

if(is_numeric($arParams["NO_URL_IN_COMMENTS_AUTHORITY"]))
{
	$arParams["NO_URL_IN_COMMENTS_AUTHORITY"] = floatVal($arParams["NO_URL_IN_COMMENTS_AUTHORITY"]);
	$arParams["NO_URL_IN_COMMENTS_AUTHORITY_CHECK"] = "Y";
	if($USER->IsAuthorized())
	{
		$authorityRatingId = CRatings::GetAuthorityRating();
		$arRatingResult = CRatings::GetRatingResult($authorityRatingId, $user_id);
		if($arRatingResult["CURRENT_VALUE"] < $arParams["NO_URL_IN_COMMENTS_AUTHORITY"])
		{
			$arResult["NoCommentUrl"] = true;
			$arResult["NoCommentReason"] = GetMessage("B_B_PC_MES_NOCOMMENTREASON_R");
		}
	}
}
$arParams["COMMENT_PROPERTY"] = array("UF_BLOG_COMMENT_DOC");
if(CModule::IncludeModule("webdav"))
	$arParams["COMMENT_PROPERTY"][] = "UF_BLOG_COMMENT_FILE";

$arBlog = $arParams["BLOG_DATA"];
$arPost = $arParams["POST_DATA"];

$arResult["Perm"] = BLOG_PERMS_DENY;
$arResult["PostPerm"] = BLOG_PERMS_DENY;
if(IntVal($_REQUEST["comment_post_id"]) > 0)
{
	$arParams["ID"] = IntVal($_REQUEST["comment_post_id"]);
	$arPost = CBlogPost::GetById($arParams["ID"]);
	$arPost = CBlogTools::htmlspecialcharsExArray($arPost);
	$arBlog = CBlog::GetById($arPost["BLOG_ID"]);
	$arBlog = CBlogTools::htmlspecialcharsExArray($arBlog);

	if($arPost["AUTHOR_ID"] == $user_id)
	{
		$arResult["Perm"] = BLOG_PERMS_FULL;
		$arResult["PostPerm"] = BLOG_PERMS_FULL;
	}
	else
	{
		$arResult["PostPerm"] = CBlogPost::GetSocNetPostPerms($arParams["ID"]);
		if($arResult["PostPerm"] > BLOG_PERMS_DENY)
			$arResult["Perm"] = CBlogComment::GetSocNetUserPerms($arParams["ID"], $arPost["AUTHOR_ID"]);
	}

	$arResult["is_ajax_post"] = "Y";
}
else
{
	if(strlen($arParams["POST_DATA"]["perms"]) <= 0)
		$arResult["PostPerm"] = CBlogPost::GetSocNetPostPerms($arParams["ID"]);
	else
		$arResult["PostPerm"] = $arParams["POST_DATA"]["perms"];

	if($arResult["PostPerm"] > BLOG_PERMS_DENY)
	{
		if(IsModuleInstalled("intranet") && IsModuleInstalled("bitrix24") && $arParams["POST_DATA"]["HAVE_ALL_IN_ADR"] == "Y")
		{
			if($arPost["AUTHOR_ID"] != $user_id)
				$arResult["Perm"] = BLOG_PERMS_WRITE;
			else
				$arResult["Perm"] = BLOG_PERMS_FULL;
		}
		else
			$arResult["Perm"] = CBlogComment::GetSocNetUserPerms($arParams["ID"], $arPost["AUTHOR_ID"]);
	}
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_REQUEST['mfi_mode']) && ($_REQUEST['mfi_mode'] == "upload"))
{
	CBlogImage::AddImageResizeHandler(array("width" => 400, "height" => 400));
}

if(!empty($arPost) && $arPost["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_PUBLISH && $arPost["ENABLE_COMMENTS"] == "Y")
{
	//Comment delete
	if(IntVal($_GET["delete_comment_id"])>0)
	{
		if($_GET["success"] == "Y")
		{
			$arResult["MESSAGE"] = GetMessage("B_B_PC_MES_DELED");
		}
		else
		{
			$arComment = CBlogComment::GetByID(IntVal($_GET["delete_comment_id"]));
			if(($arResult["Perm"]>=BLOG_PERMS_MODERATE || (IntVal($user_id) > 0 && $arComment["AUTHOR_ID"] == $user_id)) && !empty($arComment))
			{
				if(check_bitrix_sessid())
				{
					if(CBlogComment::Delete(IntVal($_GET["delete_comment_id"])))
					{
						BXClearCache(true, "/blog/comment/".$arParams["ID"]."/");
						CBlogComment::DeleteLog(IntVal($_GET["delete_comment_id"]));

						$arResult["ajax_comment"] = IntVal($_GET["delete_comment_id"]);
						$arResult["MESSAGE"] = GetMessage("B_B_PC_MES_DELED");
					}
				}
				else
					$arResult["ERROR_MESSAGE"] = GetMessage("B_B_PC_MES_ERROR_SESSION");
			}
			if(IntVal($arResult["ajax_comment"]) <= 0 && strlen($arResult["ERROR_MESSAGE"]) <= 0)
				$arResult["ERROR_MESSAGE"] = GetMessage("B_B_PC_MES_ERROR_DELETE");
		}
	}
	elseif(IntVal($_GET["show_comment_id"])>0)
	{
		$arComment = CBlogComment::GetByID(IntVal($_GET["show_comment_id"]));
		if($arResult["Perm"]>=BLOG_PERMS_MODERATE && !empty($arComment))
		{
			if($arComment["PUBLISH_STATUS"] != BLOG_PUBLISH_STATUS_READY)
			{
				$arResult["ERROR_MESSAGE"] = GetMessage("B_B_PC_MES_ERROR_SHOW");
			}
			else
			{
				if(check_bitrix_sessid())
				{
					if($commentID = CBlogComment::Update($arComment["ID"], Array("PUBLISH_STATUS" => BLOG_PUBLISH_STATUS_PUBLISH)))
					{
						BXClearCache(true, "/blog/comment/".$arParams["ID"]."/");
						$parserBlog = new blogTextParser(false, $arParams["PATH_TO_SMILE"]);
						$arFil = array("EVENT_ID" => "blog_post", "SOURCE_ID" => $arComment["POST_ID"]);

						$dbRes = CSocNetLog::GetList(
							array("ID" => "DESC"),
							$arFil,
							false,
							false,
							array("ID", "TMP_ID")
						);
						if ($arRes = $dbRes->Fetch())
						{
							$log_id = $arRes["TMP_ID"];

							$arParserParams = Array(
								"imageWidth" => $arParams["IMAGE_MAX_WIDTH"],
								"imageHeight" => $arParams["IMAGE_MAX_HEIGHT"],
							);

							$arImages = Array();
							$res = CBlogImage::GetList(array("ID"=>"ASC"), array("POST_ID"=>$arPost["ID"], "BLOG_ID"=>$arPost["BLOG_ID"], "IS_COMMENT" => "Y", "COMMENT_ID" => $arComment["ID"]));
							while ($arImage = $res->Fetch())
								$arImages[$arImage["ID"]] = $arImage["FILE_ID"];

							$arAllow = array("HTML" => "N", "ANCHOR" => "N", "BIU" => "N", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "TABLE" => "N", "LIST" => "N", "SMILES" => "N", "NL2BR" => "N", "VIDEO" => "N");
							$text4message = $parserBlog->convert($arComment["POST_TEXT"], false, $arImages, $arAllow, array("isSonetLog"=>true));

							$text4mail = $parserBlog->convert4mail($arComment["POST_TEXT"]);
							$commentUrl = CComponentEngine::MakePathFromTemplate(htmlspecialcharsBack($arParams["PATH_TO_POST"]), array("post_id"=> CBlogPost::GetPostID($arPost["ID"], $arPost["CODE"], $arParams["ALLOW_POST_CODE"]), "user_id" => $arPost["AUTHOR_ID"]));

							$arFieldsForSocnet = array(
								"ENTITY_TYPE" => SONET_ENTITY_USER,
								"ENTITY_ID" => $arPost["AUTHOR_ID"],
								"EVENT_ID" => "blog_comment",
								"=LOG_DATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
								"MESSAGE" => $text4message,
								"TEXT_MESSAGE" => $text4mail,
								"URL" => $commentUrl,
								"MODULE_ID" => false,
								"SOURCE_ID" => $arComment["ID"],
								"LOG_ID" => $log_id,
								"RATING_TYPE_ID" => "BLOG_COMMENT",
								"RATING_ENTITY_ID" => intval($arComment["ID"])
							);

							if (intval($arComment["AUTHOR_ID"]) > 0)
								$arFieldsForSocnet["USER_ID"] = $arComment["AUTHOR_ID"];

							CSocNetLogComments::Add($arFieldsForSocnet);
						}
						$arResult["ajax_comment"] = $arComment["ID"];
					}
				}
				else
					$arResult["ERROR_MESSAGE"] = GetMessage("B_B_PC_MES_ERROR_SESSION");
			}
		}
		if(IntVal($arResult["ajax_comment"]) <= 0)
			$arResult["ERROR_MESSAGE"] = GetMessage("B_B_PC_MES_ERROR_SHOW");
	}
	elseif(IntVal($_GET["hide_comment_id"])>0)
	{
		$arComment = CBlogComment::GetByID(IntVal($_GET["hide_comment_id"]));
		if($arResult["Perm"]>=BLOG_PERMS_MODERATE && !empty($arComment))
		{
			if($arComment["PUBLISH_STATUS"] != BLOG_PUBLISH_STATUS_PUBLISH)
			{
				$arResult["ERROR_MESSAGE"] = GetMessage("B_B_PC_MES_ERROR_SHOW");
			}
			else
			{
				if(check_bitrix_sessid())
				{
					if($commentID = CBlogComment::Update($arComment["ID"], Array("PUBLISH_STATUS" => BLOG_PUBLISH_STATUS_READY)))
					{
						BXClearCache(true, "/blog/comment/".$arParams["ID"]."/");
						CBlogComment::DeleteLog($arComment["ID"]);
						$arResult["ajax_comment"] = $arComment["ID"];
					}
				}
				else
					$arResult["ERROR_MESSAGE"] = GetMessage("B_B_PC_MES_ERROR_SESSION");
			}
		}
		if(IntVal($arResult["ajax_comment"]) <= 0 && strlen($arResult["ERROR_MESSAGE"]) <= 0)
			$arResult["ERROR_MESSAGE"] = GetMessage("B_B_PC_MES_ERROR_HIDE");
	}
	elseif(IntVal($_GET["hidden_add_comment_id"])>0)
	{
		$arResult["MESSAGE"] = GetMessage("B_B_PC_MES_HIDDEN_ADDED");
	}

	$arResult["CanUserComment"] = false;
	$arResult["canModerate"] = false;
	if($arResult["Perm"] >= BLOG_PERMS_PREMODERATE)
		$arResult["CanUserComment"] = true;
	if($arResult["Perm"] >= BLOG_PERMS_MODERATE)
		$arResult["canModerate"] = true;

	if(IntVal($user_id)>0)
	{
		$arResult["User"]["ID"] = $user_id;
	}

	$arResult["use_captcha"] = false;
	if($arResult["CanUserComment"])
	{
		if(!$USER->IsAuthorized())
		{

			if(!empty($arBlog))
			{
				$GLOBALS["BLOG_POST"]["BLOG_PC_".$arPost["BLOG_ID"]] = $arBlog;
			}
			else
			{
				if (isset($GLOBALS["BLOG_POST"]["BLOG_PC_".$arPost["BLOG_ID"]]) && !empty($GLOBALS["BLOG_POST"]["BLOG_PC_".$arPost["BLOG_ID"]]))
				{
					$arBlog = $GLOBALS["BLOG_POST"]["BLOG_PC_".$arPost["BLOG_ID"]];
				}
				else
				{
					if(empty($arBlog))
						$arBlog = CBlog::GetByID($arPost["BLOG_ID"]);
					$GLOBALS["BLOG_POST"]["BLOG_PC_".$arPost["BLOG_ID"]] = $arBlog;
				}
			}

			$useCaptcha = COption::GetOptionString("blog", "captcha_choice", "U");
			if($useCaptcha == "U")
				$arResult["use_captcha"] = ($arBlog["ENABLE_IMG_VERIF"]=="Y")? true : false;
			elseif($useCaptcha == "A")
				$arResult["use_captcha"] = true;
			else
				$arResult["use_captcha"] = false;
		}
	}

	if(strlen($arPost["ID"])>0 && $_SERVER["REQUEST_METHOD"]=="POST" && strlen($_POST["post"]) > 0)
	{
		if ($_POST["decode"] == "Y")
			CUtil::JSPostUnescape();

		if($arResult["Perm"] >= BLOG_PERMS_PREMODERATE)
		{
			if(check_bitrix_sessid())
			{

				if(!empty($arBlog))
				{
					$GLOBALS["BLOG_POST"]["BLOG_PC_".$arPost["BLOG_ID"]] = $arBlog;
				}
				else
				{
					if (isset($GLOBALS["BLOG_POST"]["BLOG_PC_".$arPost["BLOG_ID"]]) && !empty($GLOBALS["BLOG_POST"]["BLOG_PC_".$arPost["BLOG_ID"]]))
					{
						$arBlog = $GLOBALS["BLOG_POST"]["BLOG_PC_".$arPost["BLOG_ID"]];
					}
					else
					{
						if(empty($arBlog))
							$arBlog = CBlog::GetByID($arPost["BLOG_ID"]);
						$GLOBALS["BLOG_POST"]["BLOG_PC_".$arPost["BLOG_ID"]] = $arBlog;
					}
				}
				$strErrorMessage = '';
				if ($_POST["blog_upload_image_comment"] == "Y")
				{
					if ($_FILES["BLOG_UPLOAD_FILE"]["size"] > 0)
					{
						$arResult["imageUploadFrame"] = "Y";
						$APPLICATION->RestartBuffer();
						header("Pragma: no-cache");

						$arFields = array(
							"MODULE_ID" => "blog",
							"BLOG_ID"	=> $arBlog["ID"],
							"POST_ID"	=> $arPost["ID"],
							"=TIMESTAMP_X"	=> $DB->GetNowFunction(),
							"TITLE"		=> "",
							"IMAGE_SIZE"	=> $_FILES["BLOG_UPLOAD_FILE"]["size"],
							"IS_COMMENT" => "Y",
							"URL" => $arBlog["URL"],
							"USER_ID" => IntVal($user_id),
						);
						$arFields["FILE_ID"] = array_merge(
								$_FILES["BLOG_UPLOAD_FILE"],
								array(
									"MODULE_ID" => "blog",
									"del" => "Y",
								)
							);

						if ($imgID = CBlogImage::Add($arFields))
						{
							$aImg = CBlogImage::GetByID($imgID);
							$aImg["PARAMS"] = CFile::_GetImgParams($aImg["FILE_ID"]);
							$arResult["Image"] = Array("ID" => $aImg["ID"], "SRC" => $aImg["PARAMS"]["SRC"], "WIDTH" => $aImg["PARAMS"]["WIDTH"], "HEIGHT" => $aImg["PARAMS"]["HEIGHT"]);
						}
						else
						{
							if ($ex = $APPLICATION->GetException())
								$arResult["ERROR_MESSAGE"] = $ex->GetString();
						}
						$this->IncludeComponentTemplate();
						return;
					}
				}

				if($_POST["act"] != "edit")
				{
					if ($arResult["use_captcha"])
					{
						include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/captcha.php");
						$captcha_code = $_POST["captcha_code"];
						$captcha_word = $_POST["captcha_word"];
						$cpt = new CCaptcha();
						$captchaPass = COption::GetOptionString("main", "captcha_password", "");
						if (strlen($captcha_code) > 0)
						{
							if (!$cpt->CheckCodeCrypt($captcha_word, $captcha_code, $captchaPass))
								$strErrorMessage .= GetMessage("B_B_PC_CAPTCHA_ERROR")."<br />";
						}
						else
							$strErrorMessage .= GetMessage("B_B_PC_CAPTCHA_ERROR")."<br />";
					}

					$UserIP = CBlogUser::GetUserIP();
					$arFields = Array(
								"POST_ID" => $arPost["ID"],
								"BLOG_ID" => $arBlog["ID"],
								"TITLE" => trim($_POST["subject"]),
								"POST_TEXT" => trim($_POST["comment"]),
								"DATE_CREATE" => ConvertTimeStamp(time() + $arResult["TZ_OFFSET"], "FULL"),
								"AUTHOR_IP" => $UserIP[0],
								"AUTHOR_IP1" => $UserIP[1],
								"URL" => $arBlog["URL"],
								"PARENT_ID" => false,
								);
					if($arResult["Perm"] == BLOG_PERMS_PREMODERATE)
						$arFields["PUBLISH_STATUS"] = BLOG_PUBLISH_STATUS_READY;

					if(IntVal($user_id)>0)
						$arFields["AUTHOR_ID"] = $user_id;
					else
					{
						$arFields["AUTHOR_NAME"] = trim($_POST["user_name"]);
						if(strlen(trim($_POST["user_email"]))>0)
							$arFields["AUTHOR_EMAIL"] = trim($_POST["user_email"]);
						if(strlen($arFields["AUTHOR_NAME"])<=0)
							$strErrorMessage .= GetMessage("B_B_PC_NO_ANAME")."<br />";
						$_SESSION["blog_user_name"] = $_POST["user_name"];
						$_SESSION["blog_user_email"] = $_POST["user_email"];
					}

					if(strlen($_POST["comment"])<=0)
						$strErrorMessage .= GetMessage("B_B_PC_NO_COMMENT")."<br />";

					if (IntVal($_REQUEST["as"])>0)
						$arParams["AVATAR_SIZE_COMMENT"] = IntVal($_REQUEST["as"]);

					if(strlen($strErrorMessage)<=0)
					{
						$fieldName = 'UF_BLOG_COMMENT_DOC';
						if (isset($GLOBALS[$fieldName]) && is_array($GLOBALS[$fieldName]))
						{
							$arAttachedFiles = array();
							foreach($GLOBALS[$fieldName] as $fileID)
							{
								$fileID = intval($fileID);
								if ($fileID <= 0 || !in_array($fileID, $_SESSION["MFI_UPLOADED_FILES_".$_POST["blog_upload_cid"]]))
									continue;

								$arFile = CFile::GetFileArray($fileID);
								if (CFile::CheckImageFile(CFile::MakeFileArray($fileID)) === null)
								{
									$arImgFields = array(
										"BLOG_ID"	=> $arBlog["ID"],
										"POST_ID"	=> $arPost["ID"],
										"USER_ID"	=> $user_id,
										"COMMENT_ID" => 0,
										"=TIMESTAMP_X"	=> $DB->GetNowFunction(),
										"TITLE"		=> $arFile["FILE_NAME"],
										"IMAGE_SIZE"	=> $arFile["FILE_SIZE"],
										"FILE_ID" => $fileID,
										"IS_COMMENT" => "Y",
										"URL" => $arBlog["URL"],
										"USER_ID" => IntVal($user_id),
										"IMAGE_SIZE_CHECK" => "N",
									);
									$imgID = CBlogImage::Add($arImgFields);
									if (intval($imgID) <= 0)
									{
										$GLOBALS["APPLICATION"]->ThrowException("Error Adding file by CBlogImage::Add");
									}
									else
									{
										$arFields["POST_TEXT"] = str_replace("[IMG ID=".$fileID."file", "[IMG ID=".$imgID."", $arFields["POST_TEXT"]);
									}
								}
								else
								{
									$arAttachedFiles[] = $fileID;
								}
							}
							$GLOBALS[$fieldName] = $arAttachedFiles;
						}

						if (count($arParams["COMMENT_PROPERTY"]) > 0)
							$GLOBALS["USER_FIELD_MANAGER"]->EditFormAddFields("BLOG_COMMENT", $arFields);

						$commentUrl = CComponentEngine::MakePathFromTemplate(htmlspecialcharsBack($arParams["PATH_TO_POST"]), array("blog" => $arBlog["URL"], "post_id"=> CBlogPost::GetPostID($arPost["ID"], $arPost["CODE"], $arParams["ALLOW_POST_CODE"]), "user_id" => $arBlog["OWNER_ID"], "group_id" => $arParams["SOCNET_GROUP_ID"]));

						$arFields["PATH"] = $commentUrl;
						if(strpos($arFields["PATH"], "?") !== false)
							$arFields["PATH"] .= "&";
						else
							$arFields["PATH"] .= "?";
						$arFields["PATH"] .= $arParams["COMMENT_ID_VAR"]."=#comment_id###comment_id#";

						if($commentId = CBlogComment::Add($arFields))
						{
							BXClearCache(true, "/blog/comment/".$arParams["ID"]."/");
							$images = Array();

							$DB->Query("UPDATE b_blog_image SET COMMENT_ID=".IntVal($commentId)." WHERE BLOG_ID=".IntVal($arBlog["ID"])." AND POST_ID=".IntVal($arPost["ID"])." AND IS_COMMENT = 'Y' AND (COMMENT_ID = 0 OR COMMENT_ID is null) AND USER_ID=".IntVal($user_id)."", true);
							
							$parserBlog = new blogTextParser(false, $arParams["PATH_TO_SMILE"]);
							$arParserParams = Array(
								"imageWidth" => $arParams["IMAGE_MAX_WIDTH"],
								"imageHeight" => $arParams["IMAGE_MAX_HEIGHT"],
							);

							if(strpos($commentUrl, "?") !== false)
								$commentUrl .= "&";
							else
								$commentUrl .= "?";
							if(strlen($arFields["PUBLISH_STATUS"]) > 0 && $arFields["PUBLISH_STATUS"] != BLOG_PUBLISH_STATUS_PUBLISH)
								$commentAddedUrl = $commentUrl.$arParams["COMMENT_ID_VAR"]."=".$commentId."&hidden_add_comment_id=".$commentId;
							$commentUrl .= $arParams["COMMENT_ID_VAR"]."=".$commentId."#".$commentId;

							if($arFields["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_PUBLISH || strlen($arFields["PUBLISH_STATUS"]) <= 0)
							{
								$arFil = array("EVENT_ID" => "blog_post", "SOURCE_ID" =>$arPost["ID"]);

								$dbRes = CSocNetLog::GetList(
									array("ID" => "DESC"),
									$arFil,
									false,
									false,
									array("ID", "TMP_ID")
								);
								if ($arRes = $dbRes->Fetch())
								{
									$log_id = $arRes["TMP_ID"];
								}
								else
								{
									$arParamsNotify = Array(
										"bSoNet" => true,
										"UserID" => $arParams["USER_ID"],
										"allowVideo" => $arResult["allowVideo"],
										"bGroupMode" => $arResult["bGroupMode"],
										"PATH_TO_SMILE" => $arParams["PATH_TO_SMILE"],
										"PATH_TO_POST" => $arParams["PATH_TO_POST"],
										"SOCNET_GROUP_ID" => $arParams["SOCNET_GROUP_ID"],
										"user_id" => $user_id,
										"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
										"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
										);
									$log_id = CBlogPost::Notify($arPost, $arBlog, $arParamsNotify);
								}

								if (intval($log_id) > 0)
								{
									$arImages = Array();
									$res = CBlogImage::GetList(array("ID"=>"ASC"), array("POST_ID"=>$arPost["ID"], "BLOG_ID"=>$arBlog["ID"], "IS_COMMENT" => "Y", "COMMENT_ID" => $commentId));
									while ($arImage = $res->Fetch())
										$arImages[$arImage["ID"]] = $arImage["FILE_ID"];

									$arAllow = array("HTML" => "N", "ANCHOR" => "N", "BIU" => "N", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "TABLE" => "N", "LIST" => "N", "SMILES" => "N", "NL2BR" => "N", "VIDEO" => "N");
									$text4message = $parserBlog->convert($_POST['comment'], false, $arImages, $arAllow, array("isSonetLog"=>true));
									$text4mail = $parserBlog->convert4mail($_POST['comment'], $arImages);

									$arFieldsForSocnet = array(
										"ENTITY_TYPE" => SONET_ENTITY_USER,
										"ENTITY_ID" => $arBlog["OWNER_ID"],
										"EVENT_ID" => "blog_comment",
										"=LOG_DATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
										"MESSAGE" => $text4message,
										"TEXT_MESSAGE" => $text4mail,
										"URL" => $commentUrl,
										"MODULE_ID" => false,
										"SOURCE_ID" => $commentId,
										"LOG_ID" => $log_id,
										"RATING_TYPE_ID" => "BLOG_COMMENT",
										"RATING_ENTITY_ID" => intval($commentId)
									);

									if (intval($user_id) > 0)
										$arFieldsForSocnet["USER_ID"] = $user_id;

									CSocNetLogComments::Add($arFieldsForSocnet);
								}

								$arPSR = CBlogPost::GetSocnetPerms($arPost["ID"]);
								$arUsrS = array();
								if(!empty($arPSR["U"]))
								foreach($arPSR["U"] as $k => $v)
									$arUsrS[] = "U".$k;

								preg_match_all("/\[user\s*=\s*([^\]]*)\](.+?)\[\/user\]/ies".BX_UTF_PCRE_MODIFIER, $_POST['comment'], $arMention);

								$arFieldsIM = Array(
									"TYPE" => "COMMENT",
									"TITLE" => $arPost["TITLE"],
									"URL" => $commentUrl,
									"ID" => $arPost["ID"],
									"FROM_USER_ID" => $user_id,
									"TO_USER_ID" => array($arPost["AUTHOR_ID"]),
									"TO_SOCNET_RIGHTS" => $arUsrS,
									"TO_SOCNET_RIGHTS_OLD" => array(),
									"AUTHOR_ID" => $arPost["AUTHOR_ID"],
								);
								//if(!empty($arMentionOld))
								//	$arFieldsIM["MENTION_ID_OLD"] = $arMentionOld[1];
								if(!empty($arMention))
									$arFieldsIM["MENTION_ID"] = $arMention[1];

								// if(CModule::IncludeModule("pull"))
									// $arFieldsIM["EXCLUDE_USERS"] = CPullWatch::GetUserList("BLOG_POST_".$arPost["ID"]);

								CBlogPost::NotifyIm($arFieldsIM);
							}

							$res = CBlogImage::GetList(array(), array("POST_ID"=>$arPost["ID"], "BLOG_ID" => $arBlog["ID"], "IS_COMMENT" => "Y", "COMMENT_ID" => false, "<=TIMESTAMP_X" => ConvertTimeStamp(AddToTimeStamp(Array("HH" => -3)), "FULL")));
							while($aImg = $res->Fetch())
								CBlogImage::Delete($aImg["ID"]);

							if(strlen($arFields["PUBLISH_STATUS"]) > 0 && $arFields["PUBLISH_STATUS"] != BLOG_PUBLISH_STATUS_PUBLISH)
								$arResult["MESSAGE"] = GetMessage("B_B_PC_MES_HIDDEN_ADDED");
							$arResult["ajax_comment"] = $commentId;
							if(CModule::IncludeModule("pull"))
							{
								CPullWatch::AddToStack('BLOG_POST_'.$arPost["ID"],
										Array(
											'module_id' => 'blog',
											'command' => 'comment',
											'params' => Array("AUTHOR_ID" => $user_id, "ID" => $commentId, "POST_ID" => $arPost["ID"], "TS" => time())
										)
									);
							}

							$bHasImg = false;
							$bHasProps = false;

							$dbImg = CBlogImage::GetList(Array(), Array("BLOG_ID" => $arBlog["ID"], "POST_ID" => $arPost["ID"], "IS_COMMENT" => "Y"), false, false, Array("ID"));
							if($dbImg->Fetch())
								$bHasImg = true;

							$arPostFields = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("BLOG_COMMENT", $commentId, LANGUAGE_ID);
							foreach ($arPostFields as $FIELD_NAME => $arPostField)
							{
								if(!empty($arPostField["VALUE"]) > 0)
								{
									$bHasProps = true;
									break;
								}
							}

							$arFieldsHave = array(
									"HAS_PROPS" => ($bHasProps ? "Y" : "N"),
								);
							CBlogComment::Update($commentId, $arFieldsHave);

							$arFieldsHave = array(
									"HAS_COMMENT_IMAGES" => ($bHasImg ? "Y" : "N"),
								);
							if($arFieldsHave["HAS_COMMENT_IMAGES"] != $arPost["HAS_COMMENT_IMAGES"])
								CBlogPost::Update($arPost["ID"], $arFieldsHave);

						}
						else
						{
							if ($e = $APPLICATION->GetException())
								$arResult["COMMENT_ERROR"] = "<b>".GetMessage("B_B_PC_COM_ERROR")."</b><br />".$e->GetString();
						}
					}
					else
					{
						if ($e = $APPLICATION->GetException())
							$arResult["COMMENT_ERROR"] = "<b>".GetMessage("B_B_PC_COM_ERROR")."</b><br />".$e->GetString();
						if(strlen($strErrorMessage)>0)
							$arResult["COMMENT_ERROR"] = "<b>".GetMessage("B_B_PC_COM_ERROR")."</b><br />".$strErrorMessage;
					}
				}
				else //update comment
				{
					$commentID = IntVal($_POST["edit_id"]);
					$arOldComment = CBlogComment::GetByID($commentID);
					if($commentID <= 0 || empty($arOldComment))
						$arResult["COMMENT_ERROR"] = "<b>".GetMessage("B_B_PC_COM_ERROR_EDIT")."</b><br />".GetMessage("B_B_PC_COM_ERROR_LOST");
					elseif($arOldComment["AUTHOR_ID"] == $user_id || $arResult["Perm"] >= BLOG_PERMS_FULL)
					{
						$arFields = Array(
								"POST_TEXT" => $_POST["comment"],
								"URL" => $arBlog["URL"],
							);
						if($arResult["Perm"] == BLOG_PERMS_PREMODERATE)
							$arFields["PUBLISH_STATUS"] = BLOG_PUBLISH_STATUS_READY;

						$fieldName = 'UF_BLOG_COMMENT_DOC';
						if (isset($GLOBALS[$fieldName]) && is_array($GLOBALS[$fieldName]))
						{
							$arAttachedFiles = array();
							foreach($GLOBALS[$fieldName] as $fileID)
							{
								$fileID = intval($fileID);
								if ($fileID <= 0 || !in_array($fileID, $_SESSION["MFI_UPLOADED_FILES_".$_POST["blog_upload_cid"]]))
									continue;

								$arFile = CFile::GetFileArray($fileID);
								if (CFile::CheckImageFile(CFile::MakeFileArray($fileID)) === null)
								{
									$arImgFields = array(
										"BLOG_ID"	=> $arBlog["ID"],
										"POST_ID"	=> $arPost["ID"],
										"USER_ID"	=> $arResult["UserID"],
										"COMMENT_ID" => $commentID,
										"=TIMESTAMP_X"	=> $DB->GetNowFunction(),
										"TITLE"		=> $arFile["FILE_NAME"],
										"IMAGE_SIZE"	=> $arFile["FILE_SIZE"],
										"FILE_ID" => $fileID,
										"IS_COMMENT" => "Y",
										"URL" => $arBlog["URL"],
										"USER_ID" => IntVal($user_id),
										"IMAGE_SIZE_CHECK" => "N",
									);
									$imgID = CBlogImage::Add($arImgFields);
									if (intval($imgID) <= 0)
									{
										$GLOBALS["APPLICATION"]->ThrowException("Error Adding file by CBlogImage::Add");
									}
									else
									{
										$arFields["POST_TEXT"] = str_replace("[IMG ID=".$fileID."file", "[IMG ID=".$imgID."", $arFields["POST_TEXT"]);
									}
								}
								else
								{
									$arAttachedFiles[] = $fileID;
								}
							}
							$GLOBALS[$fieldName] = $arAttachedFiles;
						}
						if (count($arParams["COMMENT_PROPERTY"]) > 0)
							$GLOBALS["USER_FIELD_MANAGER"]->EditFormAddFields("BLOG_COMMENT", $arFields);

						$commentUrl = CComponentEngine::MakePathFromTemplate(htmlspecialcharsBack($arParams["PATH_TO_POST"]), array("blog" => $arBlog["URL"], "post_id"=> CBlogPost::GetPostID($arPost["ID"], $arPost["CODE"], $arParams["ALLOW_POST_CODE"]), "user_id" => $arBlog["OWNER_ID"], "group_id" => $arParams["SOCNET_GROUP_ID"]));

						$arFields["PATH"] = $commentUrl;
						if(strpos($arFields["PATH"], "?") !== false)
							$arFields["PATH"] .= "&";
						else
							$arFields["PATH"] .= "?";
						$arFields["PATH"] .= $arParams["COMMENT_ID_VAR"]."=".$commentID."#".$commentID;

						$dbComment = CBlogComment::GetList(array(), Array("POST_ID" => $arPost["ID"], "BLOG_ID" => $arBlog["ID"], ">ID" => $commentID));
						if($dbComment->Fetch() && $arResult["Perm"] < BLOG_PERMS_FULL && !IsModuleInstalled("intranet"))
						{
							$arResult["COMMENT_ERROR"] = "<b>".GetMessage("B_B_PC_COM_ERROR_EDIT")."</b><br />".GetMessage("B_B_PC_EDIT_ALREADY_COMMENTED");
						}
						else
						{
							if($commentID = CBlogComment::Update($commentID, $arFields))
							{
								BXClearCache(true, "/blog/comment/".$arParams["ID"]."/");
								$images = Array();
								$res = CBlogImage::GetList(array(), array("POST_ID"=>$arPost["ID"], "BLOG_ID" => $arBlog["ID"], "COMMENT_ID" => $commentID, "IS_COMMENT" => "Y"));
								while($aImg = $res->Fetch())
									$images[$aImg["ID"]] = $aImg["FILE_ID"];

								$arParamsUpdateLog = array(
									"PATH_TO_SMILE" => $arParams["PATH_TO_SMILE"],
									"IMAGES" => $images,
								);

								if(IntVal($user_id)>0)
								{
									$arResult["BlogUser"] = CBlogUser::GetByID($user_id, BLOG_BY_USER_ID);
									$arResult["BlogUser"] = CBlogTools::htmlspecialcharsExArray($arResult["BlogUser"]);
									$dbUser = CUser::GetByID($user_id);
									$arResult["arUser"] = $dbUser->GetNext();
									$arResult["User"]["NAME"] = CBlogUser::GetUserName($arResult["BlogUser"]["ALIAS"], $arResult["arUser"]["NAME"], $arResult["arUser"]["LAST_NAME"], $arResult["arUser"]["LOGIN"]);
								}

								CBlogComment::UpdateLog($commentID, $arResult["BlogUser"], $arResult["User"], $arFields, $arPost, $arParamsUpdateLog);

								$res = CBlogImage::GetList(array(), array("POST_ID"=>$arPost["ID"], "BLOG_ID" => $arBlog["ID"], "IS_COMMENT" => "Y", "COMMENT_ID" => false, "<=TIMESTAMP_X" => ConvertTimeStamp(AddToTimeStamp(Array("HH" => -3)), "FULL")));
								while($aImg = $res->Fetch())
									CBlogImage::Delete($aImg["ID"]);

								$commentUrl = CComponentEngine::MakePathFromTemplate(htmlspecialcharsBack($arParams["PATH_TO_POST"]), array("post_id" => CBlogPost::GetPostID($arPost["ID"], $arPost["CODE"], $arParams["ALLOW_POST_CODE"]), "user_id" => $arBlog["OWNER_ID"]));
								if(strpos($commentUrl, "?") !== false)
									$commentUrl .= "&";
								else
									$commentUrl .= "?";

								if(strlen($arFields["PUBLISH_STATUS"]) > 0 && $arFields["PUBLISH_STATUS"] != BLOG_PUBLISH_STATUS_PUBLISH)
									$arResult["MESSAGE"] = GetMessage("B_B_PC_MES_HIDDEN_EDITED");
								$arResult["ajax_comment"] = $commentID;

								$bHasImg = false;
								$bHasProps = false;

								$dbImg = CBlogImage::GetList(Array(), Array("BLOG_ID" => $arBlog["ID"], "POST_ID" => $arPost["ID"], "IS_COMMENT" => "Y"), false, false, Array("ID"));
								if($dbImg->Fetch())
									$bHasImg = true;

								$arPostFields = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("BLOG_COMMENT", $commentID, LANGUAGE_ID);
								foreach ($arPostFields as $FIELD_NAME => $arPostField)
								{
									if(!empty($arPostField["VALUE"]) > 0)
									{
										$bHasProps = true;
										break;
									}
								}

								$arFieldsHave = array(
										"HAS_PROPS" => ($bHasProps ? "Y" : "N"),
									);
								CBlogComment::Update($commentID, $arFieldsHave);

								$arFieldsHave = array(
										"HAS_COMMENT_IMAGES" => ($bHasImg ? "Y" : "N"),
									);
								if($arFieldsHave["HAS_COMMENT_IMAGES"] != $arPost["HAS_COMMENT_IMAGES"])
									CBlogPost::Update($arPost["ID"], $arFieldsHave);
							}
							else
							{
								if ($e = $APPLICATION->GetException())
									$arResult["COMMENT_ERROR"] = "<b>".GetMessage("B_B_PC_COM_ERROR_EDIT")."</b><br />".$e->GetString();
							}
						}
					}
					else
					{
						$arResult["COMMENT_ERROR"] = "<b>".GetMessage("B_B_PC_COM_ERROR_EDIT")."</b><br />".GetMessage("B_B_PC_NO_RIGHTS_EDIT");
					}
				}
			}
			else
				$arResult["COMMENT_ERROR"] = GetMessage("B_B_PC_MES_ERROR_SESSION");
		}
		else
			$arResult["COMMENT_ERROR"] = GetMessage("B_B_PC_NO_RIGHTS");
	}

	//Comments output
	if($arResult["Perm"]>=BLOG_PERMS_READ)
	{
		/////////////////////////////////////////////////////////////////////////////////////

		$tmp = Array();
		$tmp["MESSAGE"] = $arResult["MESSAGE"];
		$tmp["ERROR_MESSAGE"] = $arResult["ERROR_MESSAGE"];
		if((strlen($arResult["COMMENT_ERROR"]) > 0 || strlen($arResult["ERROR_MESSAGE"]) > 0))
		{
			$arResult["is_ajax_post"] = "Y";
		}
		else
		{
			if(IntVal($_REQUEST["new_comment_id"]) > 0) // for push&pull
				$arResult["ajax_comment"] = IntVal($_REQUEST["new_comment_id"]);

			if(IntVal($arParams["ID"]) > 0 && IntVal($arPost["NUM_COMMENTS"]) > 0 || $arResult["ajax_comment"] > 0)
			{
				$cache = new CPHPCache;
				$cache_id = "blog_comment_".$USER->IsAuthorized()."_".LANGUAGE_ID.$arParams["MOBILE"];
				if($arResult["TZ_OFFSET"] <> 0)
					$cache_id .= "_".$tzOffset;
				$cache_path = "/blog/comment/".$arParams["ID"]."/";

				if(IntVal($arResult["ajax_comment"]) > 0)
				{
					$arResult["is_ajax_post"] = "Y";
					$cache_id .= $arResult["ajax_comment"];
					if(IntVal($_REQUEST["new_comment_id"]) <= 0) // for push&pull
						$arParams["CACHE_TIME"] = 0;
				}

				if ($arParams["CACHE_TIME"] > 0 && $cache->InitCache($arParams["CACHE_TIME"], $cache_id, $cache_path))
				{
					$Vars = $cache->GetVars();
					$arResult = array_merge($Vars["arResult"], $arResult);
					CBitrixComponentTemplate::ApplyCachedData($Vars["templateCachedData"]);
					$cache->Output();
				}
				else
				{
					if ($arParams["CACHE_TIME"] > 0)
						$cache->StartDataCache($arParams["CACHE_TIME"], $cache_id, $cache_path);

					$arResult["Images"] = Array();
					$arResult["CommentsResult"] = Array();
					$arResult["IDS"] = Array();


					$arOrder = Array("DATE_CREATE" => "ASC", "ID" => "ASC");
					$arFilter = Array("POST_ID" => $arParams["ID"], "BLOG_ID" => $arPost["BLOG_ID"]);
					if($arResult["is_ajax_post"] == "Y" && IntVal($arResult["ajax_comment"]) > 0)
					{
						$arFilter["ID"] = $arResult["ajax_comment"];
					}
					$arSelectedFields = Array("ID", "BLOG_ID", "POST_ID", "AUTHOR_ID", "AUTHOR_NAME", "AUTHOR_EMAIL", "POST_TEXT", "DATE_CREATE", "PUBLISH_STATUS", "HAS_PROPS");

					if ($GLOBALS["DB"]->type == "MYSQL")
						$arSelectedFields[] = "DATE_CREATE_TS";

					$dbComment = CBlogComment::GetList($arOrder, $arFilter, false, false, $arSelectedFields);
					$resComments = Array();

					if($arComment = $dbComment->Fetch())
					{

						$p = new blogTextParser(false, $arParams["PATH_TO_SMILE"]);
						$arParserParams = Array(
							"imageWidth" => $arParams["IMAGE_MAX_WIDTH"],
							"imageHeight" => $arParams["IMAGE_MAX_HEIGHT"],
							"pathToUser" => $arParams["PATH_TO_USER"],
						);

						$bHasImg = false;
						if($arPost["HAS_COMMENT_IMAGES"] != "N")
						{
							$res = CBlogImage::GetList(array("ID"=>"ASC"), array("POST_ID"=>$arPost['ID'], "BLOG_ID"=>$arPost['BLOG_ID'], "IS_COMMENT" => "Y"), false, false, Array("ID", "FILE_ID", "POST_ID", "BLOG_ID", "USER_ID", "TITLE", "COMMENT_ID", "IS_COMMENT"));
							while ($aImg = $res->Fetch())
							{
								$bHasImg = true;
								$arImages[$aImg['ID']] = $aImg['FILE_ID'];
								if($arResult["allowImageUpload"])
								{
									$aImgNew = CFile::ResizeImageGet(
										$aImg["FILE_ID"],
										array("width" => 90, "height" => 90),
										BX_RESIZE_IMAGE_EXACT,
										true
									);
									$aImgNew["source"] = CFile::ResizeImageGet(
										$aImg["FILE_ID"],
										array("width" => $arParams["IMAGE_MAX_WIDTH"], "height" => $arParams["IMAGE_MAX_HEIGHT"]),
										BX_RESIZE_IMAGE_EXACT,
										true
									);
									$aImgNew["ID"] = $aImg["ID"];
									$aImgNew["fileName"] = substr($aImgNew["src"], strrpos($aImgNew["src"], "/")+1);
									$arResult["Images"][$aImg['ID']] = $aImgNew;
								}
								$arResult["arImages"][$aImg["COMMENT_ID"]][$aImg['ID']] = Array(
									"small" => "/bitrix/components/bitrix/blog/show_file.php?fid=".$aImg['ID']."&width=".$arParams["ATTACHED_IMAGE_MAX_WIDTH_SMALL"]."&height=".$arParams["ATTACHED_IMAGE_MAX_HEIGHT_SMALL"]."&type=square"
								);

								if ($arParams["MOBILE"] == "Y")
									$arResult["arImages"][$aImg["COMMENT_ID"]][$aImg['ID']]["full"] = SITE_DIR."mobile/log/blog_image.php?bfid=".$aImg['ID']."&fid=".$aImg['FILE_ID']."&width=".$arParams["ATTACHED_IMAGE_MAX_WIDTH_FULL"]."&height=".$arParams["ATTACHED_IMAGE_MAX_HEIGHT_FULL"];
								else
									$arResult["arImages"][$aImg["COMMENT_ID"]][$aImg['ID']]["full"] = "/bitrix/components/bitrix/blog/show_file.php?fid=".$aImg['ID']."&width=".$arParams["ATTACHED_IMAGE_MAX_WIDTH_FULL"]."&height=".$arParams["ATTACHED_IMAGE_MAX_HEIGHT_FULL"];
							}
						}

						$arFieldsHave = array();
						if($arPost["HAS_COMMENT_IMAGES"] == "")
							$arFieldsHave["HAS_COMMENT_IMAGES"] = ($bHasImg ? "Y" : "N");

						if(!empty($arFieldsHave))
							CBlogPost::Update($arPost["ID"], $arFieldsHave);

						do
						{
							if(IntVal($arComment["AUTHOR_ID"])>0)
							{
								if(empty($arResult["userCache"][$arComment["AUTHOR_ID"]]))
									$arResult["userCache"][$arComment["AUTHOR_ID"]] = CBlogUser::GetUserInfo($arComment["AUTHOR_ID"], $arParams["PATH_TO_USER"], array("AVATAR_SIZE" => $arParams["AVATAR_SIZE"], "AVATAR_SIZE_COMMENT" => $arParams["AVATAR_SIZE_COMMENT"]));
								if($arComment["AUTHOR_ID"] == $arPost["AUTHOR_ID"])
									$arComment["AuthorIsPostAuthor"] = "Y";

								$arTmpUser = array(
										"NAME" => $arResult["userCache"][$arComment["AUTHOR_ID"]]["~NAME"],
										"LAST_NAME" => $arResult["userCache"][$arComment["AUTHOR_ID"]]["~LAST_NAME"],
										"SECOND_NAME" => $arResult["userCache"][$arComment["AUTHOR_ID"]]["~SECOND_NAME"],
										"LOGIN" => $arResult["userCache"][$arComment["AUTHOR_ID"]]["~LOGIN"],
										"NAME_LIST_FORMATTED" => "",
									);
								$arResult["userCache"][$arComment["AUTHOR_ID"]]["NAME_FORMATED"] = CUser::FormatName($arParams["NAME_TEMPLATE"], $arTmpUser, ($arParams["SHOW_LOGIN"] != "N" ? true : false));
								$p->authorName = $arResult["userCache"][$arComment["AUTHOR_ID"]]["NAME_FORMATED"];

							}
							else
							{
								$arComment["AuthorName"]  = htmlspecialcharsbx($arComment["AUTHOR_NAME"]);
								$arComment["AuthorEmail"]  = htmlspecialcharsbx($arComment["AUTHOR_EMAIL"]);
								$p->authorName = $arComment["AuthorName"];
							}
							unset($arComment["AUTHOR_NAME"]);
							unset($arComment["AUTHOR_EMAIL"]);

							$arAllow = array("HTML" => "N", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "Y", "QUOTE" => "Y", "CODE" => "Y", "FONT" => "Y", "LIST" => "Y", "SMILES" => "Y", "NL2BR" => "N", "VIDEO" => "Y", "SHORT_ANCHOR" => "Y");
							if(COption::GetOptionString("blog","allow_video", "Y") != "Y" || $arParams["ALLOW_VIDEO"] != "Y")
								$arAllow["VIDEO"] = "N";

							if($arParams["NO_URL_IN_COMMENTS"] == "L" || (IntVal($arComment["AUTHOR_ID"]) <= 0  && $arParams["NO_URL_IN_COMMENTS"] == "A"))
								$arAllow["CUT_ANCHOR"] = "Y";

							if($arParams["NO_URL_IN_COMMENTS_AUTHORITY_CHECK"] == "Y" && $arAllow["CUT_ANCHOR"] != "Y" && IntVal($arComment["AUTHOR_ID"]) > 0)
							{
								$authorityRatingId = CRatings::GetAuthorityRating();
								$arRatingResult = CRatings::GetRatingResult($authorityRatingId, $arComment["AUTHOR_ID"]);
								if($arRatingResult["CURRENT_VALUE"] < $arParams["NO_URL_IN_COMMENTS_AUTHORITY"])
									$arAllow["CUT_ANCHOR"] = "Y";
							}

							$bHasProps = false;
							if (!empty($arParams["COMMENT_PROPERTY"]))
							{
								if($arComment["HAS_PROPS"] != "N")
								{
									$arPostFields = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("BLOG_COMMENT", $arComment["ID"], LANGUAGE_ID);

									if (count($arPostFields) > 0)
									{
										foreach ($arPostFields as $FIELD_NAME => $arPostField)
										{
											if (!in_array($FIELD_NAME, $arParams["COMMENT_PROPERTY"]))
												continue;
											$arPostField["EDIT_FORM_LABEL"] = strLen($arPostField["EDIT_FORM_LABEL"]) > 0 ? $arPostField["EDIT_FORM_LABEL"] : $arPostField["FIELD_NAME"];
											$arPostField["EDIT_FORM_LABEL"] = htmlspecialcharsEx($arPostField["EDIT_FORM_LABEL"]);
											$arPostField["~EDIT_FORM_LABEL"] = $arPostField["EDIT_FORM_LABEL"];
											$arComment["COMMENT_PROPERTIES"]["DATA"][$FIELD_NAME] = $arPostField;

											if(!empty($arPostField["VALUE"]))
												$bHasProps = true;
										}
									}
									if (!empty($arComment["COMMENT_PROPERTIES"]["DATA"]))
										$arComment["COMMENT_PROPERTIES"]["SHOW"] = "Y";
								}
							}

							if (is_array($arComment["COMMENT_PROPERTIES"]["DATA"]["UF_BLOG_COMMENT_FILE"]))
								$p->arUserfields = array("UF_BLOG_COMMENT_FILE" => array_merge($arComment["COMMENT_PROPERTIES"]["DATA"]["UF_BLOG_COMMENT_FILE"], array("TAG" => "DOCUMENT ID")));
							$arComment["TextFormated"] = $p->convert($arComment["POST_TEXT"], false, $arImages, $arAllow, $arParserParams);
							if (is_array($arComment["COMMENT_PROPERTIES"]["DATA"]["UF_BLOG_COMMENT_FILE"]) &&
								is_array($arComment["COMMENT_PROPERTIES"]["DATA"]["UF_BLOG_COMMENT_FILE"]["VALUE"]) &&
								is_array($p->arUserfields["UF_BLOG_COMMENT_FILE"]["PARSED"]))
								$arComment["COMMENT_PROPERTIES"]["DATA"]["UF_BLOG_COMMENT_FILE"]["VALUE"] = array_diff(
									$arComment["COMMENT_PROPERTIES"]["DATA"]["UF_BLOG_COMMENT_FILE"]["VALUE"],
									$p->arUserfields["UF_BLOG_COMMENT_FILE"]["PARSED"]);
							$arComment["showedImages"] = $p->showedImages;
							if(!empty($p->showedImages))
							{
								foreach($p->showedImages as $val)
								{
									if(!empty($arResult["arImages"][$arComment["ID"]][$val]))
										unset($arResult["arImages"][$arComment["ID"]][$val]);
								}
							}

							$arComment["DateFormated"] = FormatDateFromDB($arComment["DATE_CREATE"], $arParams["DATE_TIME_FORMAT"], true);
							$arComment["DATE_CREATE_DATE"] = FormatDateFromDB($arComment["DATE_CREATE"], FORMAT_DATE);
							if (strcasecmp(LANGUAGE_ID, 'EN') !== 0 && strcasecmp(LANGUAGE_ID, 'DE') !== 0)
							{
								$arComment["DateFormated"] = ToLower($arComment["DateFormated"]);
								$arComment["DATE_CREATE_DATE"] = ToLower($arComment["DATE_CREATE_DATE"]);
							}
							// strip current year
							if (!empty($arParams['DATE_TIME_FORMAT_S']) && ($arParams['DATE_TIME_FORMAT_S'] == 'j F Y G:i' || $arParams['DATE_TIME_FORMAT_S'] == 'j F Y g:i a'))
							{
								$arComment["DateFormated"] = ltrim($arComment["DateFormated"], '0');
								$arComment["DATE_CREATE_DATE"] = ltrim($arComment["DATE_CREATE_DATE"], '0');
								$curYear = date('Y');
								$arComment["DateFormated"] = str_replace(array('-'.$curYear, '/'.$curYear, ' '.$curYear, '.'.$curYear), '', $arComment["DateFormated"]);
								$arComment["DATE_CREATE_DATE"] = str_replace(array('-'.$curYear, '/'.$curYear, ' '.$curYear, '.'.$curYear), '', $arComment["DATE_CREATE_DATE"]);
							}
							if ($arParams["MOBILE"] == "Y")
							{
								$timestamp = MakeTimeStamp($arComment["DATE_CREATE"]);
								$arComment["DATE_CREATE_TIME"] = FormatDate(GetMessage("SONET_SBPC_MOBILE_FORMAT_TIME"), $timestamp);
							}
							else
								$arComment["DATE_CREATE_TIME"] = FormatDateFromDB($arComment["DATE_CREATE"], (strpos($arParams["DATE_TIME_FORMAT"], 'a') !== false || ($arParams["DATE_TIME_FORMAT"] == 'FULL' && IsAmPmMode()) !== false ? 'G:MI T' : 'GG:MI'));

							$arResult["CommentsResult"][] = $arComment;
							$arResult["IDS"][] = $arComment["ID"];

							$arFieldsHave = array();
							if($arComment["HAS_PROPS"] == "")
								$arFieldsHave["HAS_PROPS"] = ($bHasProps ? "Y" : "N");

							if(!empty($arFieldsHave))
								CBlogComment::Update($arComment["ID"], $arFieldsHave);
						}
						while($arComment = $dbComment->Fetch());
					}
					unset($arResult["MESSAGE"]);
					unset($arResult["ERROR_MESSAGE"]);

					if ($arParams["CACHE_TIME"] > 0)
						$cache->EndDataCache(array("templateCachedData" => $this->GetTemplateCachedData(), "arResult" => $arResult));
				}
			}

			$arResult["MESSAGE"] = $tmp["MESSAGE"];
			$arResult["ERROR_MESSAGE"] = $tmp["ERROR_MESSAGE"];
		}

		$arResult["commentUrl"] = CComponentEngine::MakePathFromTemplate(htmlspecialcharsBack($arParams["PATH_TO_POST"]), array("post_id" => CBlogPost::GetPostID($arPost["ID"], $arPost["CODE"], $arParams["ALLOW_POST_CODE"]), "user_id" => $arPost["AUTHOR_ID"]));
		if(strpos($arResult["commentUrl"], "?") !== false)
			$arResult["commentUrl"] .= "&";
		else
			$arResult["commentUrl"] .= "?";
		$arResult["commentUrl"] .= $arParams["COMMENT_ID_VAR"]."=#comment_id###comment_id#";

		if($arResult["use_captcha"])
		{
			include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/captcha.php");
			$cpt = new CCaptcha();
			$captchaPass = COption::GetOptionString("main", "captcha_password", "");
			if (strlen($captchaPass) <= 0)
			{
				$captchaPass = randString(10);
				COption::SetOptionString("main", "captcha_password", $captchaPass);
			}
			$cpt->SetCodeCrypt($captchaPass);
			$arResult["CaptchaCode"] = htmlspecialcharsbx($cpt->GetCodeCrypt());
		}

		if(is_array($arResult["CommentsResult"]))
		{
			$arResult["newCount"] = 0;
			$arResult["newCountWOMark"] = 0;
			foreach($arResult["CommentsResult"] as $k1 => $v1)
			{
				if(IntVal($commentUrlID) > 0 && $commentUrlID == $v1["ID"] && $v1["AUTHOR_ID"] == $user_id && $v1["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_READY)
					$arResult["MESSAGE"] = GetMessage("B_B_PC_HIDDEN_POSTED");
				$bAuthor = false;
				if(IntVal($v1["AUTHOR_ID"])>0 && $v1["AUTHOR_ID"] == $user_id)
					$bAuthor = true;

				if((IsModuleInstalled("intranet") && $bAuthor) || ($arResult["Perm"] >= BLOG_PERMS_FULL && !IsModuleInstalled("intranet")))
				{
					$arResult["CommentsResult"][$k1]["CAN_DELETE"] = "Y";
					$arResult["CommentsResult"][$k1]["CAN_EDIT"] = "Y";
				}

				if(!IsModuleInstalled("intranet") & $arResult["Perm"] < BLOG_PERMS_FULL && !empty($arResult["CommentsResult"][$k1-1]))
					$arResult["CommentsResult"][$k1-1]["CAN_EDIT"] = "N";

				if (intval($arParams["CREATED_BY_ID"]) > 0)
				{
					if ($v1["AUTHOR_ID"] != $arParams["CREATED_BY_ID"])
						unset($arResult["CommentsResult"][$k1]);
					else
						$arResult["newCount"]++;
				}
				else
				{
					if (isset($v1["DATE_CREATE_TS"]))
						$comment_date_create_ts = ($v1["DATE_CREATE_TS"] + $arResult["TZ_OFFSET"]);
					else
						$comment_date_create_ts = MakeTimeStamp($v1["DATE_CREATE"]);

					if(
						strlen($arParams["LAST_LOG_TS"]) > 0
						&& !empty($arResult["CommentsResult"][$k1])
						&& $arParams["LAST_LOG_TS"] < $comment_date_create_ts
					)
					{
						if ($arParams["MARK_NEW_COMMENTS"] == "Y")
						{
							if($v1["AUTHOR_ID"] != $user_id)
								$arResult["CommentsResult"][$k1]["NEW"] = "Y";
							$arResult["newCount"]++;
						}
						elseif($v1["AUTHOR_ID"] != $user_id)
							$arResult["newCountWOMark"]++;
					}
				}

				if($arResult["Perm"] >= BLOG_PERMS_MODERATE && $arParams["FROM_LOG"] != "Y")
				{
					if($v1["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_PUBLISH)
						$arResult["CommentsResult"][$k1]["CAN_HIDE"] = "Y";
					else
						$arResult["CommentsResult"][$k1]["CAN_SHOW"] = "Y";
				}
				else
				{
					if($v1["PUBLISH_STATUS"] != BLOG_PUBLISH_STATUS_PUBLISH)
					{
						unset($arResult["CommentsResult"][$k1]);
					}
				}
			}
			if($arResult["newCount"] < 3)
				$arResult["newCount"] = 3;
			if(IntVal($commentUrlID) > 0)
				$arResult["newCount"] = count($arResult["CommentsResult"]);
			if($arParams["SHOW_RATING"] == "Y" && !empty($arResult["IDS"]))
				$arResult['RATING'] = CRatings::GetRatingVoteResult('BLOG_COMMENT', $arResult["IDS"]);
		}

		$arResult["urlToPost"] = CComponentEngine::MakePathFromTemplate(htmlspecialcharsBack($arParams["PATH_TO_POST"]), array("post_id" => "#source_post_id#", "user_id" => $arPost["AUTHOR_ID"]));
		if(strpos($arResult["urlToPost"], "?") !== false)
			$arResult["urlToPost"] .= "&";
		else
			$arResult["urlToPost"] .= "?";

		if($USER->IsAuthorized())
		{
			$arResult["urlToDelete"] = $arResult["urlToPost"]."delete_comment_id=#comment_id#&comment_post_id=#post_id#&".bitrix_sessid_get();
			$arResult["urlToHide"] = $arResult["urlToPost"]."hide_comment_id=#comment_id#&comment_post_id=#post_id#&".bitrix_sessid_get();
			$arResult["urlToShow"] = $arResult["urlToPost"]."show_comment_id=#comment_id#&comment_post_id=#post_id#&".bitrix_sessid_get();
		}

		$arResult["urlToMore"] = $arResult["urlToPost"]."last_comment_id=#comment_id#&comment_post_id=#post_id#&IFRAME=Y";
		$arResult["urlToNew"] = $arResult["urlToPost"]."new_comment_id=#comment_id#&comment_post_id=#post_id#&IFRAME=Y";

		include_once($_SERVER["DOCUMENT_ROOT"].$componentPath."/component_adit.php");

		if(CModule::IncludeModule("pull") && IntVal($arResult["userID"]) > 0 && $arResult["is_ajax_post"] != "Y" && !$arParams["bFromList"]/*(!$arParams["bFromList"] || CModule::IncludeModule("bitrix24"))*/)
			CPullWatch::Add($arResult["userID"], 'BLOG_POST_'.$arPost["ID"]);
	}

	$arResult["Post"] = $arPost;

	$this->IncludeComponentTemplate();

	return array(
		"CanUserComment" => $arResult["CanUserComment"],
		"newCountWOMark" => $arResult["newCountWOMark"]
	);
}
?>