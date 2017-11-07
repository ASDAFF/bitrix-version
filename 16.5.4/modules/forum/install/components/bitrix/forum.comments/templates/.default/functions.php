<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
function forumCommentsCommentWeb(
	array $comment,
	array $arParams,
	array $arResult,
	ForumCommentsComponent $component)
{
	global $APPLICATION;
	$arParams["AVATAR_SIZE"] = (intval($arParams["AVATAR_SIZE"]) ?: 58);

	$res = array(
		"ID" => $comment["ID"],
		"NEW" => ($comment["NEW"] == "Y" ? "Y" : "N"),
		"APPROVED" => $comment["APPROVED"],
		"POST_TIMESTAMP" => $comment["POST_TIMESTAMP"],
	//	"POST_TIME" => $comment["POST_TIME"],
	//	"POST_DATE" => $comment["POST_DATE"],
		"AUTHOR" => array(
			"ID" => $comment["AUTHOR_ID"],
			"NAME" => $comment["~NAME"],
			"LAST_NAME" => $comment["~LAST_NAME"],
			"SECOND_NAME" => $comment["~SECOND_NAME"],
			"LOGIN" => $comment["~LOGIN"],
			"AVATAR" => ($comment["AVATAR"] && $comment["AVATAR"]["FILE"] ? $comment["AVATAR"]["FILE"]['src'] : "")
		),
		"FILES" => $comment["FILES"],
		"UF" => $comment["PROPS"],
		"POST_MESSAGE_TEXT" => $comment["POST_MESSAGE_TEXT"],
		"~POST_MESSAGE_TEXT" => $comment["~POST_MESSAGE_TEXT"],
		"CLASSNAME" => "",
		"BEFORE_HEADER" => "",
		"BEFORE_ACTIONS" => "",
		"AFTER_ACTIONS" => "",
		"AFTER_HEADER" => "",
		"BEFORE" => "",
		"AFTER" => "",
		"BEFORE_RECORD" => "",
		"AFTER_RECORD" => ""
	);

	if (!empty($res["FILES"]))
	{
		foreach ($res["FILES"] as $key => $file)
		{
			$res["FILES"][$key]["URL"] = "/bitrix/components/bitrix/forum.interface/show_file.php?fid=".$file["ID"];
			if (CFile::IsImage($file["SRC"], $file["CONTENT_TYPE"]))
			{
				$res["FILES"][$key]["THUMBNAIL"] = "/bitrix/components/bitrix/forum.interface/show_file.php?fid=".$file["ID"]."&width=90&height=90";
				$res["FILES"][$key]["SRC"] = "/bitrix/components/bitrix/forum.interface/show_file.php?fid=".$file["ID"];
			}
		}
	}

	return $res;
}