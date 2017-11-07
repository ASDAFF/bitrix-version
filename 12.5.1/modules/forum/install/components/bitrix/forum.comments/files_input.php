<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$this->IncludeComponentLang("files.php");
class CCommentFiles
{
	var $imageSize = 100;
	var $component = null;

	function __construct(&$component)
	{
		global $APPLICATION;
		$this->component = &$component;
		$arResult =& $component->arResult;
		$arParams =& $component->arParams;

		$_REQUEST["FILES"] = is_array($_REQUEST["FILES"]) ? $_REQUEST["FILES"] : array();
		$_REQUEST["FILE_NEW"] = is_array($_REQUEST["FILE_NEW"]) ? $_REQUEST["FILE_NEW"] : array();
		$_REQUEST["FILES_TO_UPLOAD"] = is_array($_REQUEST["FILES_TO_UPLOAD"]) ? $_REQUEST["FILES_TO_UPLOAD"] : array();
		
		if (isset($arParams['IMAGE_SIZE']) && (intval($arParams['IMAGE_SIZE']) > 0 || $arParams['IMAGE_SIZE']===0))
			$this->imageSize = intval($arParams['IMAGE_SIZE']);

		$APPLICATION->AddHeadScript("/bitrix/js/main/utils.js");
		$APPLICATION->AddHeadScript("/bitrix/js/forum/popup_image.js");

		AddEventHandler("forum", "OnPrepareComments", Array(&$this, "OnPrepareComments"));
		AddEventHandler("forum", "OnCommentDispay", Array(&$this, "OnCommentDispay"));
		
		if ($arResult["FORUM"]["ALLOW_UPLOAD"] !== "N")
		{
			AddEventHandler("forum", "OnCommentsInit", Array(&$this, "OnCommentsInit"));
			AddEventHandler("forum", "OnCommentAdd", Array(&$this, "OnCommentAdd"));
			AddEventHandler("forum", "OnCommentPreview", Array(&$this, "OnCommentPreview"));
			AddEventHandler("forum", "OnCommentFormDisplay", Array(&$this, "OnCommentFormDisplay"));
			AddEventHandler("forum", "OnCommentPreviewDisplay", Array(&$this, "OnCommentPreviewDisplay"));
		}
	}

	function OnPrepareComments()
	{
		$arResult =& $this->component->arResult;
		$arParams =& $this->component->arParams;

		$arMessages = &$arResult['MESSAGES'];
		if (!empty($arMessages))
		{
			$res = array_keys($arMessages);
			$arFilter = array("FORUM_ID" => $arParams["FORUM_ID"], "TOPIC_ID" => $arResult["FORUM_TOPIC_ID"],
				"APPROVED" => "Y", ">MESSAGE_ID" => intVal(min($res)) - 1, "<MESSAGE_ID" => intVal(max($res)) + 1);
			$db_files = CForumFiles::GetList(array("MESSAGE_ID" => "ASC"), $arFilter);
			if ($db_files && $res = $db_files->Fetch())
			{
				do
				{
					$res["SRC"] = CFile::GetFileSRC($res);
					if ($arMessages[$res["MESSAGE_ID"]]["~ATTACH_IMG"] == $res["FILE_ID"])
					{
						// attach for custom
						$arMessages[$res["MESSAGE_ID"]]["~ATTACH_FILE"] = $res;
						$arMessages[$res["MESSAGE_ID"]]["ATTACH_IMG"] = CFile::ShowFile($res["FILE_ID"], 0,
							$this->imageSize, $this->imageSize, true, "border=0", false);
						$arMessages[$res["MESSAGE_ID"]]["ATTACH_FILE"] = $arMessages[$res["MESSAGE_ID"]]["ATTACH_IMG"];
					}
					$arMessages[$res["MESSAGE_ID"]]["FILES"][$res["FILE_ID"]] = $res;
				} while ($res = $db_files->Fetch());
			}
		}
	}

	function OnCommentPreview()
	{
		$arResult =& $this->component->arResult;
		$arParams =& $this->component->arParams;

		$arDummy = array();
		$this->OnCommentAdd(null, null, $arDummy);

		$arResult["MESSAGE_VIEW"]["FILES"] = array_merge($_REQUEST["FILE_NEW"], $_REQUEST['FILES_TO_UPLOAD']);

		$arResult["REVIEW_FILES"] = array();
		foreach ($_REQUEST["FILE_NEW"] as $key => $val)
			$arResult["REVIEW_FILES"][$val] = CFile::GetFileArray($val);
		foreach ($_REQUEST["FILES_TO_UPLOAD"] as $key => $val)
			$arResult["REVIEW_FILES"][$val] = CFile::GetFileArray($val);
	}

	function OnCommentPreviewDisplay()
	{
		$arResult =& $this->component->arResult;
		$arParams =& $this->component->arParams;

		if (empty($arResult["REVIEW_FILES"]))
			return null;

		ob_start();
		if (!empty($arResult["REVIEW_FILES"]))
		{
?>
			<div class="comments-post-attachments">
				<label><?=GetMessage("F_ATTACH_FILES")?></label>
<?
			$parentComponent = null;
			if (isset($GLOBALS['forumComponent']) && is_object($GLOBALS['forumComponent']))
				$parentComponent =&$GLOBALS['forumComponent'];
				foreach ($arResult["REVIEW_FILES"] as $arFile)
				{
?>
					<div class="comments-post-attachment"><?
					?><?$GLOBALS["APPLICATION"]->IncludeComponent(
						"bitrix:forum.interface", "show_file",
						Array(
							"FILE" => $arFile,
							"WIDTH" => $arResult["PARSER"]->image_params["width"],
							"HEIGHT" => $arResult["PARSER"]->image_params["height"],
							"CONVERT" => "N",
							"FAMILY" => "FORUM",
							"SINGLE" => "Y",
							"RETURN" => "N",
							"SHOW_LINK" => "Y"),
						$parentComponent,
						array("HIDE_ICONS" => "Y"));
					?></div>
<?				}?>
			</div>
<?		}
		return array(array('DISPLAY' => 'AFTER', 'SORT' => '50', 'TEXT' => ob_get_clean()));
	}

	function OnCommentsInit()
	{
		$arResult =& $this->component->arResult;
		if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_REQUEST['mfi_save']))
			$arResult['DO_NOT_CACHE'] = true;
	}
	
	function OnCommentDispay($arComment)
	{
		$arResult =& $this->component->arResult;
		$arParams =& $this->component->arParams;

		if (empty($arComment["FILES"])) 
			return null;

		ob_start();
		foreach ($arComment["FILES"] as $arFile)
		{
			?><div class="comments-message-img"><?
			?><?$GLOBALS["APPLICATION"]->IncludeComponent(
				"bitrix:forum.interface", "show_file",
				Array(
					"FILE" => $arFile,
					"WIDTH" => $arResult["PARSER"]->image_params["width"],
					"HEIGHT" => $arResult["PARSER"]->image_params["height"],
					"CONVERT" => "N",
					"FAMILY" => "FORUM",
					"SINGLE" => "Y",
					"RETURN" => "N",
					"SHOW_LINK" => "Y"),
				$component,
				array("HIDE_ICONS" => "Y"));
			?></div><?
		}
		return array(array('DISPLAY' => 'AFTER', 'SORT' => '50', 'TEXT' => ob_get_clean()));	
	}

	function OnCommentFormDisplay()
	{
		$arResult =& $this->component->arResult;
		$arParams =& $this->component->arParams;

		ob_start();
?>
		<div class="comments-reply-field comments-reply-field-upload">
<?
			if (!empty($arResult["REVIEW_FILES"]))
			{
				foreach ($arResult["REVIEW_FILES"] as $key => $val)
				{
					$iCount++;
					$sFileSize = CFile::FormatSize(intval($val["FILE_SIZE"]));
?>
					<div class="comments-uploaded-file">
						<input type="hidden" name="FILES[<?=$key?>]" value="<?=$key?>" />
						<input type="checkbox" name="FILES_TO_UPLOAD[<?=$key?>]" id="FILES_TO_UPLOAD_<?=$key?>" value="<?=$key?>" checked="checked" />
						<label for="FILES_TO_UPLOAD_<?=$key?>"><?=$val["ORIGINAL_NAME"]?> (<?=$val["CONTENT_TYPE"]?>) <?=$sFileSize?>
							( <a href="/bitrix/components/bitrix/forum.interface/show_file.php?action=download&amp;fid=<?=$key?>"><?=GetMessage("F_DOWNLOAD")?></a> )
						</label>
					</div>
<?
				}
			}

			$iFileSize = intval(COption::GetOptionString("forum", "file_max_size", 50000));
			$sFileSize = CFile::FormatSize($iFileSize);
?>
			<div class="comments-upload-info" id="upload_files_info_<?=$arParams["form_index"]?>">
<?  		if ($arParams["FORUM"]["ALLOW_UPLOAD"] == "F") { ?>
				<span><?=str_replace("#EXTENSION#", $arParams["FORUM"]["ALLOW_UPLOAD_EXT"], GetMessage("F_FILE_EXTENSION"))?></span>
<?  		} ?>
				<span><?=str_replace("#SIZE#", $sFileSize, GetMessage("F_FILE_SIZE"))?></span>
			</div>
<?
			$componentParams = array(
				'INPUT_NAME' => 'FILE_NEW',
				'INPUT_NAME_UNSAVED' => 'FILE_NEW_TMP',
				'INPUT_VALUE' => array(),
				'MAX_FILE_SIZE' => $iFileSize,
				'MODULE_ID' => 'forum'
			);
			if ($arResult['FORUM']['ALLOW_UPLOAD'] == 'Y')
			{
				$componentParams['ALLOW_UPLOAD'] = 'I';
			}
			elseif($arResult['FORUM']['ALLOW_UPLOAD'] == 'F')
			{
				$componentParams['ALLOW_UPLOAD'] = 'F';
				$componentParams['ALLOW_UPLOAD_EXT'] = $arResult['FORUM']['ALLOW_UPLOAD_EXT'];
			}
			elseif ($arResult['FORUM']['ALLOW_UPLOAD'] == 'A')
			{
				$componentParams['ALLOW_UPLOAD'] = 'A';
			}
			$GLOBALS['APPLICATION']->IncludeComponent('bitrix:main.file.input', '', $componentParams, $this->component);
?>
		</div>
<?
		return array(array('DISPLAY' => 'AFTER', 'SORT' => '50', 'TEXT' => ob_get_clean()));
	}

	function OnCommentAdd($entityType, $entityID, &$arPost)
	{
		global $USER;
		$arParams =& $this->component->arParams;
		$arResult =& $this->component->arResult;
		$arForum =& $arResult['FORUM'];
		$iFileSize = intval(COption::GetOptionString("forum", "file_max_size", 50000));

		$arCommentParams = array(
			"FORUM_ID" => $arParams["FORUM_ID"],
			"TOPIC_ID" => null,
			"USER_ID" => ($USER->IsAuthorized() ? $USER->GetID() : null)
		);

		$arFiles = $arNewFiles = array();
		if (isset($_REQUEST['FILE_NEW']) && is_array($_REQUEST['FILE_NEW']))
			foreach($_REQUEST['FILE_NEW'] as $val)
				$arNewFiles[$val] = array("FILE_ID" => $val);

		if ((isset($_REQUEST['FILES']) && is_array($_REQUEST['FILES'])))
		{
			foreach($_REQUEST['FILES'] as $val)
				if (in_array($val, $_REQUEST["FILES_TO_UPLOAD"]))
					$arFiles[$val] = array("FILE_ID" => $val);
		}

		if (!empty($arNewFiles))
			CForumFiles::Add(array_keys($arNewFiles), $arCommentParams);

		$arFiles = $arFiles + $arNewFiles;
		if (!isset($arPost['FILES']))
			$arPost['FILES'] = array();
		$arPost['FILES'] = array_merge($arPost['FILES'], $arFiles);

		foreach ($arPost['FILES'] as $fileIndex => $fileArr)
		{
			$fileID = $fileArr['FILE_ID'];
			$attach_file = CFile::MakeFileArray(intval($fileID));
			$attach = "";
			if ($attach_file && is_set($attach_file, "name"))
			{
				// Y - Image files		F - Files of specified type		A - All files
				if ($arForum["ALLOW_UPLOAD"]=="Y")
					$attach = CFile::CheckImageFile($attach_file, $iFileSize, 0, 0);
				elseif ($arForum["ALLOW_UPLOAD"]=="F")
					$attach = CFile::CheckFile($attach_file, $iFileSize, false, $arForum["ALLOW_UPLOAD_EXT"]);
				elseif ($arForum["ALLOW_UPLOAD"]=="A")
					$attach = CFile::CheckFile($attach_file, $iFileSize, false, false);
				if ($attach != '')
				{
					unset($arPost['FILES'][$fileIndex]);
					$arPost['ERROR'] = $attach_file['name'].': '.$attach;
					return false;
				}
			}
		}
	}
}
?>
