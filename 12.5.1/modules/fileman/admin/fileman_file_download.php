<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/fileman/prolog.php");

if (!$USER->CanDoOperation('fileman_view_file_structure'))
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/fileman/include.php");
IncludeModuleLangFile(__FILE__);

/**
* Return right number.
* It's Replace G-> Gigabytes (* 1024) M->Megabytes ... K-> Kilobytes ...
* @param string $mixedValue - for example 128M
* @return int 134217728
*/
function GetSize($mixedVal)
{
	$retVal = trim($mixedVal);
	$last = strtolower($retVal[strlen($retVal)-1]);
	switch($last)
	{
		case 't':
			$retVal *= 1024;
		case 'g':
			$retVal *= 1024;
		case 'm':
			$retVal *= 1024;
		case 'k':
			$retVal *= 1024;
	}

	return $retVal;
}


$strWarning = "";
$site = CFileMan::__CheckSite($site);
$io = CBXVirtualIo::GetInstance();
$arFile = CFile::MakeFileArray($path);
$arFile["tmp_name"] = CBXVirtualIoFileSystem::ConvertCharset($arFile["tmp_name"], CBXVirtualIoFileSystem::directionDecode);
$path = $io->CombinePath("/", $path);
$path = $GLOBALS["APPLICATION"]->ConvertCharset($path, "UTF-8", LANG_CHARSET);
$arPath = Array($site, $path);

if(!$USER->CanDoFileOperation('fm_download_file', $arPath))
	$strWarning = GetMessage("ACCESS_DENIED");
else if(!$io->FileExists($arFile["tmp_name"]))
	$strWarning = GetMessage("FILEMAN_FILENOT_FOUND")." ";
elseif(!$USER->CanDoOperation('edit_php') && (HasScriptExtension($path) || substr(CFileman::GetFileName($path), 0, 1) == "."))
	$strWarning .= GetMessage("FILEMAN_FILE_DOWNLOAD_PHPERROR")."\n";

if(strlen($strWarning) <= 0)
{

	$flTmp = $io->GetFile($arFile["tmp_name"]);
	$fsize = $flTmp->GetFileSize();
	$memoryLimit = (GetSize(ini_get("memory_limit"))-memory_get_usage())/10; //http://jabber.bx/view.php?id=16063

	if($fsize<=($memoryLimit))
		$bufSize = $fsize;
	else
		$bufSize = $memoryLimit;

	session_write_close();
	set_time_limit(0);

	header("Content-Type: application/force-download; name=\"".$arFile["name"]."\"");
	header("Content-Transfer-Encoding: binary");
	header("Content-Length: ".$fsize);
	header("Content-Disposition: attachment; filename=\"".$arFile["name"]."\"");
	header("Expires: 0");
	header("Cache-Control: no-cache, must-revalidate");
	header("Pragma: no-cache");
	header('Connection: close');

	$f=fopen($arFile["tmp_name"], 'rb');

	while(!feof($f))
	{
		echo fread($f, $bufSize);
		ob_flush();
		flush();
		ob_end_clean ();
	}

	fclose($f);
	die();
}

$APPLICATION->SetTitle(GetMessage("FILEMAN_FILEDOWNLOAD")." \"".$arFile["name"]."\"");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
?>
<font class="text"><?=$arFile["name"]?></font><br><br>
<?
ShowError($strWarning);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>
