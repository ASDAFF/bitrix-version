<?
define("ADMIN_MODULE_NAME", "fileman");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

if(!\Bitrix\Main\Loader::includeModule("fileman"))
	ShowError(\Bitrix\Main\Localization\Loc::getMessage("MAIN_MODULE_NOT_INSTALLED"));

$isAdmin = $USER->CanDoOperation('lpa_template_edit');
$isUserHavePhpAccess = $USER->CanDoOperation('edit_php');
$POST_RIGHT = $APPLICATION->GetGroupRight("fileman");
if($POST_RIGHT=="D" || (!$isAdmin && !$isUserHavePhpAccess))
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");
$request = \Bitrix\Main\Context::getCurrent()->getRequest();
switch($request->get('action'))
{
	case 'save_file':

		$result = array();
		$fileList = array();

		//New from media library and file structure
		$requestFiles = $request->getPost('NEW_FILE_EDITOR');
		if($requestFiles && is_array($requestFiles))
		{
			foreach($requestFiles as $index=>$value)
			{
				if(is_array($value))
				{
					$filePath = urldecode($value['tmp_name']);
				}
				else
				{
					continue;
				}

				$isCheckedSuccess = false;
				$io = CBXVirtualIo::GetInstance();
				if(strpos($filePath, CTempFile::GetAbsoluteRoot()) === 0)
				{
					$absPath = $filePath;
					$normPath = $filePath;
				}
				else
				{
					$normPath = $io->CombinePath("/", $filePath);
					$absPath = $io->CombinePath($_SERVER['DOCUMENT_ROOT'], $normPath);
				}

				if ($io->ValidatePathString($absPath) && $io->FileExists($absPath))
				{
					$perm = $APPLICATION->GetFileAccessPermission($normPath);
					if ($perm >= "W")
					{
						$isCheckedSuccess = true;
					}
				}

				if($isCheckedSuccess)
				{
					$fileList[$filePath] = CFile::MakeFileArray($io->GetPhysicalName($absPath));
					if(isset($value['name']))
					{
						$fileList[$filePath]['name'] = $value['name'];
					}
				}
			}
		}


		foreach($fileList as $tmpFileName => $file)
		{
			if(strlen($file["name"]) <= 0 || intval($file["size"]) <= 0)
			{
				continue;
			}

			$resultInsertAttachFile = false;
			$file["MODULE_ID"] = "fileman";
			$fid = intval(CFile::SaveFile($file, "fileman", true));
			if($fid > 0 && ($filePath = CFile::GetPath($fid)) && strlen($filePath) > 0)
			{
				$result[$tmpFileName] = $filePath;
			}
		}

		echo CUtil::PhpToJSObject($result);
		break;


	case 'set':

		if($request->isPost() && check_bitrix_sessid())
		{
			$src = $request->getPost('src');
			if(\Bitrix\Main\Text\Encoding::detectUtf8($src))
			{
				$src = \Bitrix\Main\Text\Encoding::convertEncodingToCurrent($src);
			}

			// TODO: POST FORM IN IFRAME WITH CONTENT WITHOUT this "set" request
			$_SESSION['bx_block_editor_temp_template'] = $src;
		}
		break;


	case 'preview_mail':

		$request = \Bitrix\Main\Context::getCurrent()->getRequest();
		$previewParams = array(
			'CAN_EDIT_PHP' => $GLOBALS["USER"]->CanDoOperation('edit_php'),
			'SITE' => $request->get('site_id'),
			'HTML' => $_SESSION['bx_block_editor_temp_template'],
			'FIELDS' => array(
				'SENDER_CHAIN_CODE' => 'sender_chain_item_0',
			),
		);
		echo \Bitrix\Fileman\Block\EditorMail::getPreview($previewParams);
		break;
}


require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_after.php");