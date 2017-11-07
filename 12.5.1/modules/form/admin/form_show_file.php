<?
/*
##############################################
# Bitrix: SiteManager                        #
# Copyright (c) 2002 - 2011 Bitrix           #
# http://www.bitrixsoft.com                  #
# mailto:admin@bitrixsoft.com                #
##############################################
*/
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if (!CModule::IncludeModule("form"))
	die();

if (strlen($_REQUEST["hash"]) > 0)
{
	$arFile = CFormResult::GetFileByHash($_REQUEST["rid"], $_REQUEST["hash"]);
	if ($arFile)
	{
		set_time_limit(0);
		// if we need "download"
		if ($_REQUEST["action"]=="download")
		{
			// download
			CFile::ViewByUser($arFile, array("force_download" => true));
		}
		else // otherwise just view
		{
			if (CFile::CheckImageFile(CFile::MakeFileArray($arFile['FILE_ID'])) === null)
			{
				// display as image
				CFile::ViewByUser($arFile, array("content_type" => $arFile["CONTENT_TYPE"]));
			}
			else // otherwise
			{
				// check extension
				$ar = pathinfo($arFile["ORIGINAL_NAME"]);
				$ext = $ar["extension"];

				// and choose mime-type
				switch(strtolower($ext))
				{
					case "xla":
					case "xlb":
					case "xlc":
					case "xll":
					case "xlm":
					case "xls":
					case "xlt":
					case "xlw":
					case "dbf":
					case "csv":
						CFile::ViewByUser($arFile, array("content_type" => "application/vnd.ms-excel"));
						break;
					case "doc":
					case "dot":
					case "rtf":
						CFile::ViewByUser($arFile, array("content_type" => "application/msword"));
						break;
					// it's better not to set mime for xml and pdf. may be vulnerable
					case "xml":
					case "pdf":
					default:
						CFile::ViewByUser($arFile, array("force_download" => true));
				}
			}
		}
	}
}

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_after.php");
echo ShowError(GetMessage("FORM_ERROR_FILE_NOT_FOUND"));
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog.php");?>