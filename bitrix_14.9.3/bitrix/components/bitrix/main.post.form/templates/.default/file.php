<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$arParams["UPLOAD_FILE"]["INPUT_VALUE"] = (empty($arParams["UPLOAD_FILE"]["INPUT_VALUE"]) && !empty($arParams["UPLOAD_FILE"]["VALUE"]) ?
	$arParams["UPLOAD_FILE"]["VALUE"] : $arParams["UPLOAD_FILE"]["INPUT_VALUE"]);
/***************** Resize image for main.file.input ****************/
if (!empty($arParams["UPLOAD_FILE_PARAMS"]))
{
	$bNull = null;
	__MPF_ImageResizeHandler($bNull, $arParams["UPLOAD_FILE_PARAMS"]);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_REQUEST['mfi_mode']) && ($_REQUEST['mfi_mode'] == "upload"))
{
	AddEventHandler('main',  "main.file.input.upload", '__MPF_ImageResizeHandler');
}

/***************** Trap for CID from main.file.input ***************/
if (!empty($arParams["UPLOAD_FILE"]))
{
	ob_start();
	if (array_key_exists("USER_TYPE_ID", $arParams["UPLOAD_FILE"]) &&
		$arParams["UPLOAD_FILE"]["USER_TYPE_ID"] == "file")
	{
		if (!function_exists("__main_post_form_replace_template"))
		{
			function __main_post_form_replace_template($arResult = false, $arParams = false)
			{
				static $control_id = false;

				if ($arResult === false && $arParams === false)
					return $control_id;

				$control_id = $GLOBALS["APPLICATION"]->IncludeComponent(
					'bitrix:main.file.input',
					'drag_n_drop',
					array(
						'INPUT_NAME' => $arParams["arUserField"]["FIELD_NAME"],
						'INPUT_NAME_UNSAVED' => 'FILE_NEW_TMP',
						'INPUT_VALUE' => $arResult["VALUE"],
						'MAX_FILE_SIZE' => intval($arParams['arUserField']['SETTINGS']['MAX_ALLOWED_SIZE']),
						'MULTIPLE' => $arParams['arUserField']['MULTIPLE'],
						'MODULE_ID' => 'uf',
						'ALLOW_UPLOAD' => 'A'
					),
					null,
					array("HIDE_ICONS" => true)
				);
				return true;
			}
		}
		$eventHandlerID = AddEventHandler('main', 'system.field.edit.file', "__main_post_form_replace_template");

		$APPLICATION->IncludeComponent(
			"bitrix:system.field.edit",
			"file",
			array("arUserField" => $arParams["UPLOAD_FILE"]),
			null,
			array("HIDE_ICONS" => "Y")
		);
		RemoveEventHandler('main', 'system.field.edit.file', $eventHandlerID);
		$control_id = __main_post_form_replace_template();
	}
	elseif (!empty($arParams["UPLOAD_FILE"]["INPUT_NAME"]))
	{
		$control_id = $GLOBALS["APPLICATION"]->IncludeComponent(
			'bitrix:main.file.input',
			'drag_n_drop',
			array(
				'CONTROL_ID' => $arParams["UPLOAD_FILE"]["CONTROL_ID"],
				'INPUT_NAME' => $arParams["UPLOAD_FILE"]["INPUT_NAME"],
				'INPUT_NAME_UNSAVED' => 'FILE_NEW_TMP',
				'INPUT_VALUE' => $arParams["UPLOAD_FILE"]["INPUT_VALUE"],
				'MAX_FILE_SIZE' => $arParams["UPLOAD_FILE"]["MAX_FILE_SIZE"],
				'MULTIPLE' => $arParams["UPLOAD_FILE"]["MULTIPLE"],
				'MODULE_ID' => $arParams["UPLOAD_FILE"]["MODULE_ID"],
				'ALLOW_UPLOAD' => $arParams["UPLOAD_FILE"]["ALLOW_UPLOAD"],
				'ALLOW_UPLOAD_EXT' => $arParams["UPLOAD_FILE"]["ALLOW_UPLOAD_EXT"],
				'INPUT_CAPTION' => $arParams["UPLOAD_FILE"]["INPUT_CAPTION"]
			),
			null,
			array("HIDE_ICONS" => true)
		);
	}
	$arParams["UPLOAD_FILE_HTML"] = ob_get_clean();
	$arParams["UPLOAD_FILE_CONTROL_ID"] = $control_id;
}
/***************** Trap for CID from webdav.user.field *************/
if (!empty($arParams["UPLOAD_WEBDAV_ELEMENT"]))
{
	if (!function_exists("__main_post_form_get_cid_webdav"))
	{
		function __main_post_form_get_cid_webdav($arResult = false, $arParams = false)
		{
			static $CID = false;
			if ($arResult === false && $arParams === false)
				return $CID;
			if ($arParams['EDIT'] == 'Y')
				$CID = $arResult['UID'];
			return true;
		}
	}
	ob_start();
	$eventHandlerID = AddEventHandler("webdav", "webdav.user.field", "__main_post_form_get_cid_webdav");
	$APPLICATION->IncludeComponent(
		"bitrix:system.field.edit",
		"webdav_element",
		array("arUserField" => $arParams["UPLOAD_WEBDAV_ELEMENT"]),
		null,
		array("HIDE_ICONS" => "Y")
	);
	RemoveEventHandler("webdav", "webdav.user.field", $eventHandlerID);
	$arParams["UPLOAD_WEBDAV_ELEMENT_HTML"] = ob_get_clean();
	$arParams["UPLOAD_WEBDAV_ELEMENT_CID"] = __main_post_form_get_cid_webdav();
}

/***************** Show files from array ***************************/
$b = reset($arParams["FILES"]["VALUE"]); reset($arParams["UPLOAD_FILE"]["INPUT_VALUE"]);
$arFile = ($b ? $b : current($arParams["UPLOAD_FILE"]["INPUT_VALUE"]));
while ($arFile)
{
	$arFile = (is_array($arFile) ? $arFile :
		(array_key_exists($arFile, $arParams["UPLOAD_FILE"]["INPUT_VALUE_ARRAY"]) ? $arParams["UPLOAD_FILE"]["INPUT_VALUE_ARRAY"][$arFile] :
			CFile::GetFileArray($arFile)));
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
				' title="'.GetMessage("MPF_INSERT_FILE").'" onclick="'.$arParams["JS_OBJECT_NAME"].'.insertFile(\'postimage'.$arFile["ID"].'\');"' :
				'')?>>
				<img src="<?=$arFile["THUMBNAIL"]?>" border="0" width="90" height="90" />
			</span>
			<span class="feed-add-img-title"<?=((in_array("UploadImage", $arParams["PARSER"]) || in_array("UploadFile", $arParams["PARSER"])) ?
				' title="'.GetMessage("MPF_INSERT_FILE").'" onclick="'.$arParams["JS_OBJECT_NAME"].'.insertFile(\'postimage'.$arFile["ID"].'\');"' :
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