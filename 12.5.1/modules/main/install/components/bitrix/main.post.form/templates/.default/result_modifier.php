<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!function_exists("__MPF_ImageResizeHandler"))
{
	function __MPF_ImageResizeHandler(&$arCustomFile, $arParams = null)
	{
		static $arResizeParams = array();

		if ($arParams !== null)
		{
			if (is_array($arParams) && array_key_exists("width", $arParams) && array_key_exists("height", $arParams))
			{
				$arResizeParams = $arParams;
			}
			elseif(intVal($arParams) > 0)
			{
				$arResizeParams = array("width" => intVal($arParams), "height" => intVal($arParams));
			}
		}

		if ((!is_array($arCustomFile)) || !isset($arCustomFile['fileID']))
			return false;

		$fileID = $arCustomFile['fileID'];

		$arFile = CFile::MakeFileArray($fileID);
		if (CFile::CheckImageFile($arFile) === null)
		{
			$aImgThumb = CFile::ResizeImageGet(
				$fileID,
				array("width" => 90, "height" => 90),
				BX_RESIZE_IMAGE_EXACT,
				true
			);
			$arCustomFile['img_thumb_src'] = $aImgThumb['src'];

			if (!empty($arResizeParams))
			{
				$aImgSource = CFile::ResizeImageGet(
					$fileID,
					array("width" => $arResizeParams["width"], "height" => $arResizeParams["height"]),
					BX_RESIZE_IMAGE_PROPORTIONAL,
					true
				);
				$arCustomFile['img_source_src'] = $aImgSource['src'];
				$arCustomFile['img_source_width'] = $aImgSource['width'];
				$arCustomFile['img_source_height'] = $aImgSource['height'];
			}
		}

	}
}

if (!empty($arParams["UPLOAD_FILE_PARAMS"]))
{
	$bNull = null;
	__MPF_ImageResizeHandler($bNull, $arParams["UPLOAD_FILE_PARAMS"]);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_REQUEST['mfi_mode']) && ($_REQUEST['mfi_mode'] == "upload"))
{
	AddEventHandler('main',  "main.file.input.upload", '__MPF_ImageResizeHandler');
}
/********************************************************************
				Input params
 ********************************************************************/
/***************** BASE ********************************************/
$arParams["IS_BLOG"] = ($arParams["IS_BLOG"] === true);

$arParams["FORM_ID"] = (!empty($arParams["FORM_ID"]) ? $arParams["FORM_ID"] : "POST_FORM");
$arParams["JS_OBJECT_NAME"] = "PlEditor".$arParams["FORM_ID"];
$arParams["LHE"] = (is_array($arParams['~LHE']) ? $arParams['~LHE'] : array());
$arParams["LHE"]["id"] = (empty($arParams["LHE"]["id"]) ? "idLHE_".$arParams["FORM_ID"] : $arParams["LHE"]["id"]);
$arParams["LHE"]["jsObjName"] = (empty($arParams["LHE"]["jsObjName"]) ? "oLHE".$arParams["FORM_ID"] : $arParams["LHE"]["jsObjName"]);

$arParams["PARSER"] = array_unique(is_array($arParams["PARSER"]) ? $arParams["PARSER"] : array());
$arParams["BUTTONS"] = is_array($arParams["BUTTONS"]) ? $arParams["BUTTONS"] : array();
$arParams["BUTTONS"] = (in_array("MentionUser", $arParams["BUTTONS"]) && !IsModuleInstalled("socialnetwork") ?
	array_diff($arParams["BUTTONS"], array("MentionUser")) : $arParams["BUTTONS"]);
$arParams["BUTTONS"] = array_values($arParams["BUTTONS"]);
$arParams["BUTTONS_HTML"] = is_array($arParams["BUTTONS_HTML"]) ? $arParams["BUTTONS_HTML"] : array();

$arParams["TEXT"] = (is_array($arParams["~TEXT"]) ? $arParams["~TEXT"] : array());
$arParams["TEXT"]["ID"] = (!empty($arParams["TEXT"]["ID"]) ? $arParams["TEXT"]["ID"] : "POST_MESSAGE");
$arParams["TEXT"]["NAME"] = (!empty($arParams["TEXT"]["NAME"]) ? $arParams["TEXT"]["NAME"] : "POST_MESSAGE");
$arParams["TEXT"]["TABINDEX"] = intval($arParams["TEXT"]["TABINDEX"] <= 0 ? 10 : $arParams["TEXT"]["TABINDEX"]);

$arParams["ADDITIONAL"] = (is_array($arParams["~ADDITIONAL"]) ? $arParams["~ADDITIONAL"] : array());
$arParams["ADDITIONAL"][] =
	"{ text : '".GetMessage("MPF_EDITOR")."', onclick : function() {window['".$arParams["JS_OBJECT_NAME"]."'].showPanelEditor(); this.popupWindow.close();}, className: 'blog-post-popup-menu', id: 'bx-html'}";

//$arParams["HTML_BEFORE_TEXTAREA"] = "";
//$arParams["HTML_AFTER_TEXTAREA"] = "";

$arParams["UPLOAD_FILE"] = (is_array($arParams["UPLOAD_FILE"]) ? $arParams["UPLOAD_FILE"] : array());
$arParams["UPLOAD_FILE"]["INPUT_VALUE"] = (is_array($arParams["UPLOAD_FILE"]["INPUT_VALUE"]) ? $arParams["UPLOAD_FILE"]["INPUT_VALUE"] : array());
$arRes = array();
if (!empty($arParams["UPLOAD_FILE"]["INPUT_VALUE"])):
	foreach ($arParams["UPLOAD_FILE"]["INPUT_VALUE"] as $key => $arFile):
		if (!is_array($arFile))
			$arFile = CFile::GetFileArray($arFile);
		$arRes[$arFile["ID"]] = $arFile;
	endforeach;
	$arParams["UPLOAD_FILE"]["INPUT_VALUE"] = array_keys($arRes);
endif;
$arParams["UPLOAD_WEBDAV_ELEMENT"] = (is_array($arParams["UPLOAD_WEBDAV_ELEMENT"]) ? $arParams["UPLOAD_WEBDAV_ELEMENT"] : array());

$arParams["FILES"] = (is_array($arParams["FILES"]) ? $arParams["FILES"] : array());
$arParams["FILES"]["VALUE"] = (is_array($arParams["FILES"]["VALUE"]) ? $arParams["FILES"]["VALUE"] : array());
$arParams["FILES"]["SHOW"] = (empty($arParams["FILES"]["VALUE"]) ? "N" : $arParams["FILES"]["SHOW"]);
$arParams["FILES"]["POSTFIX"] = trim($arParams["FILES"]["POSTFIX"]);
$arParams["FILES"]["VALUE_JS"] = array();
$arParams["FILES"]["VALUE_HTML"] = array();
$arParams["FILES"]["DEL_LINK"] = trim($arParams["FILES"]["DEL_LINK"]);

$arParams["DESTINATION"] = (is_array($arParams["DESTINATION"]) && IsModuleInstalled("socialnetwork") ? $arParams["DESTINATION"] : array());
$arParams["DESTINATION_SHOW"] = (array_key_exists("SHOW", $arParams["DESTINATION"]) ? $arParams["DESTINATION"]["SHOW"] : $arParams["DESTINATION_SHOW"]);
$arParams["DESTINATION_SHOW"] = ($arParams["DESTINATION_SHOW"] == "Y" ? "Y" : "N");
$arParams["DESTINATION"] = (array_key_exists("VALUE", $arParams["DESTINATION"]) ? $arParams["DESTINATION"]["VALUE"] : $arParams["DESTINATION"]);

$arParams["TAGS"] = (is_array($arParams["TAGS"]) ? $arParams["TAGS"] : array());
if (!empty($arParams["TAGS"]))
	$arParams["TAGS"]["VALUE"] = (is_array($arParams["TAGS"]["VALUE"]) ? $arParams["TAGS"]["VALUE"] : array());

$arParams["SMILES_COUNT"] = intVal($arParams["SMILES_COUNT"]);
$arParams["SMILES"] = (is_array($arParams["SMILES"]) ? $arParams["SMILES"] : array());
if (!empty($arParams["SMILES"]) && !in_array("SmileList", $arParams["PARSER"]))
{
	$arParams["PARSER"][] = "SmileList";
	$arParams["BUTTONS"][] = "SmileListHide";
}

$arParams["CUSTOM_TEXT"] = (is_array($arParams["CUSTOM_TEXT"]) ? $arParams["CUSTOM_TEXT"] : array());
$arParams["CUSTOM_TEXT_HASH"] = (!empty($arParams["CUSTOM_TEXT"]) ? md5(implode("", $arParams["CUSTOM_TEXT"])) : "");

$arParams["IMAGE_THUMB"] = array("WIDTH" => 90, "HEIGHT" => 90);
$arParams["IMAGE"] = array("WIDTH" => 90, "HEIGHT" => 90);
/********************************************************************
				/Input params
 ********************************************************************/
$b = reset($arParams["FILES"]["VALUE"]); reset($arParams["UPLOAD_FILE"]["INPUT_VALUE"]);
$arFile = ($b ? $b : current($arParams["UPLOAD_FILE"]["INPUT_VALUE"]));
while ($arFile)
{
	$arFile = (is_array($arFile) ? $arFile : (array_key_exists($arFile, $arRes) ? $arRes[$arFile] : CFile::GetFileArray($arFile)));
	$arFile["THUMBNAIL"] = (isset($arFile["src"]) ? $arFile["src"] : $arFile["THUMBNAIL"]); // for Blog only
	if ((substr($arFile["CONTENT_TYPE"], 0, 6) == "image/") && empty($arFile["THUMBNAIL"]))
	{
		$tmp = array("fileID" => $arFile["ID"], "fileContentType" => $arFile["CONTENT_TYPE"]);
		__MPF_ImageResizeHandler($tmp);
		if (!empty($tmp['img_thumb_src']))
			$arFile["THUMBNAIL"] = $tmp['img_thumb_src'];
		if (!empty($tmp['img_source_src']))
		{
			$arFile["~SRC"] = $arFile["SRC"];
			$arFile["SRC"] = $tmp['img_source_src'];
		}
	}

	$arParams["FILES"]["VALUE_JS"][strVal($arFile["ID"])] = array(
		"element_id" => $arFile["ID"],
		"element_name" => $arFile["ORIGINAL_NAME"],
		"element_size" => $arFile["FILE_SIZE"],
		"element_url" => $arFile["URL"],
		"element_content_type" => $arFile["CONTENT_TYPE"],
		"element_thumbnail" => $arFile["SRC"],
		"element_image" => $arFile["THUMBNAIL"],
		"isImage" => (substr($arFile["CONTENT_TYPE"], 0, 6) == "image/")
	);

	$arParams["FILES"]["VALUE_HTML"][intVal($arFile["ID"])] = "";

	if ($b)
	{
		if ($arParams["FILES"]["SHOW"] != "N" && !empty($arFile["THUMBNAIL"]))
		{
		ob_start();
		?><span class="feed-add-photo-block">
			<span class="feed-add-img-wrap"<?=((in_array("UploadImage", $arParams["PARSER"]) || in_array("UploadFile", $arParams["PARSER"])) ?
				' title="'.GetMessage("MPF_INSERT_FILE").'" onclick="'.$arParams["JS_OBJECT_NAME"].'.insertFile(\''.$arFile["ID"].'\');"' :
				'')?>>
				<img src="<?=$arFile["THUMBNAIL"]?>" border="0" width="90" height="90" />
			</span>
			<span class="feed-add-img-title"<?=((in_array("UploadImage", $arParams["PARSER"]) || in_array("UploadFile", $arParams["PARSER"])) ?
				' title="'.GetMessage("MPF_INSERT_FILE").'" onclick="'.$arParams["JS_OBJECT_NAME"].'.insertFile(\''.$arFile["ID"].'\');"' :
				'')?>><?=(!empty($arFile["NAME"]) ? $arFile["NAME"] : $arFile["ORIGINAL_NAME"])?></span>
			<?=(empty($arFile["DEL_URL"]) ? '' : '<span class="feed-add-post-del-but" onclick="'.$arParams["JS_OBJECT_NAME"].'.deleteFile(\''.$arFile["ID"].'\', \''.CUtil::JSEscape($arFile["DEL_URL"]).'\', this); "></span>')?>
		</span><?
			$arParams["FILES"]["VALUE_HTML"][intVal($arFile["ID"])] = ob_get_clean();
		}
		$b = next($arParams["FILES"]["VALUE"]);
		$arFile = ($b ? $b : current($arParams["UPLOAD_FILE"]["INPUT_VALUE"]));
	}
	else
		$arFile = next($arParams["UPLOAD_FILE"]["INPUT_VALUE"]);
}
?>