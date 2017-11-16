<?
define("STOP_STATISTICS", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if(!CModule::IncludeModule("bizproc"))
	die();

if (!$GLOBALS["USER"]->IsAuthorized())
	die();

$fileName = preg_replace("/[^A-Za-z0-9_.-]+/i", "", trim($_REQUEST["f"]));
$fileId = intval($_REQUEST["i"]);
$fileAction = ($_REQUEST["act"] == "v" ? "view" : "download");

if (strlen($fileName) <= 0 || $fileId <= 0 || strlen($fileAction) <= 0)
	die("Error1");

$arImg = CFile::GetFileArray($fileId);
 if (!$arImg)
	die("Error2");

if (strlen($arImg["FILE_NAME"]) != strlen($fileName) || $arImg["FILE_NAME"] != $fileName)
	die("Error3");

if (strlen($arImg["SUBDIR"]) <= 0 || substr($arImg["SUBDIR"], 0, strlen("bizproc_wf/")) != "bizproc_wf/")
	die("Error4");

set_time_limit(0);

if ($fileAction == "download")
{
	CFile::ViewByUser($arImg, array("force_download" => true));
}
else
{
	$contentType = strtolower($arImg["CONTENT_TYPE"]);

	if (
		strpos($contentType, "image/")!==false
		&& strpos($contentType, "html")===false
		&& (
			CFile::GetImageSize($_SERVER["DOCUMENT_ROOT"].$arImg["SRC"])
			|| ($arFile["WIDTH"] > 0 && $arImg["HEIGHT"] > 0)
		)
	)
		$contentType = $contentType;
	elseif (strpos($contentType, "excel") !== false)
		$contentType = "application/vnd.ms-excel";
	elseif (strpos($contentType, "word") !== false)
		$contentType = "application/msword";
	//elseif (strpos($contentType, "flash") !== false)
	//	$contentType = "application/x-shockwave-flash";
	//elseif (strpos($contentType, "pdf") !== false)
	//	$contentType = "application/pdf";
	//elseif (strpos($contentType, "text") !== false)
	//	$contentType = "text/xml";
	else
		$contentType = "application/octet-stream";

	CFile::ViewByUser($arImg, array("content_type" => $contentType));
}
?>