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
if(CModule::IncludeModule("support") && strlen($hash) > 0)
{
	$rsFiles = CTicket::GetFileList($v1="s_id", $v2="asc", array("HASH"=>$hash));
	if ($arFile = $rsFiles->Fetch())
	{
		set_time_limit(0);

		if ($action=="download")
		{
			CFile::ViewByUser($arFile, array("force_download" => true));
		}
		else
		{
			if (
				substr($arFile["CONTENT_TYPE"],0,6) === 'image/'
				&& CFile::IsImage( $arFile["FILE_NAME"], $arFile["CONTENT_TYPE"] )
				&& (
					(!empty($arFile["SRC"]) && CFile::GetImageSize($_SERVER["DOCUMENT_ROOT"].$arFile["SRC"]))
					|| ($arFile["WIDTH"] > 0 && $arFile["HEIGHT"] > 0)
				)
			)
			{
				CFile::ViewByUser($arFile, array("content_type" => $arFile["CONTENT_TYPE"]));
			}
			else
			{
				// check extension
				$ar = pathinfo($arFile["ORIGINAL_NAME"]);
				$ext = $ar["extension"];

				switch(strtolower($ext))
				{
				case "xla":
				case "xlb":
				case "xlc":
				case "xll":
				case "xlm":
				case "xls":
				case "xlsx":
				case "xlt":
				case "xlw":
				case "dbf":
				case "csv":
					CFile::ViewByUser($arFile, array("content_type" => "application/vnd.ms-excel"));
					break;
				case "doc":
				case "docx":
				case "dot":
				case "rtf":
					CFile::ViewByUser($arFile, array("content_type" => "application/msword"));
					break;
				case "xml":
				case "pdf":
					CFile::ViewByUser($arFile, array("force_download" => true));
					break;
				case 'rar':
					CFile::ViewByUser($arFile, array("content_type" => "application/x-rar-compressed"));
					break;
				case 'zip':
					CFile::ViewByUser($arFile, array("content_type" => "application/zip"));
					break;
				default:
					CFile::ViewByUser($arFile, array("specialchars" => true));
					break;
				}
			}
		}
	}
}

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_after.php");
echo ShowError(GetMessage("SUP_ERROR_ATTACH_NOT_FOUND"));
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog.php");?>