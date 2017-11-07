<?
/*
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -l [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteCond %{REQUEST_FILENAME} [\xC2-\xDF][\x80-\xBF] [OR]
RewriteCond %{REQUEST_FILENAME} \xE0[\xA0-\xBF][\x80-\xBF] [OR]
RewriteCond %{REQUEST_FILENAME} [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} [OR]
RewriteCond %{REQUEST_FILENAME} \xED[\x80-\x9F][\x80-\xBF] [OR]
RewriteCond %{REQUEST_FILENAME} \xF0[\x90-\xBF][\x80-\xBF]{2} [OR]
RewriteCond %{REQUEST_FILENAME} [\xF1-\xF3][\x80-\xBF]{3} [OR]
RewriteCond %{REQUEST_FILENAME} \xF4[\x80-\x8F][\x80-\xBF]{2}
RewriteRule ^(.*)$ /bitrix/virtual_file_system.php [L]
*/
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$io = CBXVirtualIo::GetInstance();

$requestUri = rawurldecode($_SERVER["REQUEST_URI"]);
if (!preg_match("#([\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2})#", $requestUri))
{
	// Not utf-8 filename. Should be handled in the regular way.
	die("Filename out of range");
}

if (!defined("BX_UTF"))
{
	include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/charset_converter.php");
	$requestUri = CharsetConverter::ConvertCharset($requestUri, "utf-8", (defined("BX_DEFAULT_CHARSET")? BX_DEFAULT_CHARSET : "windows-1251"));
}

if (($pos = strpos($requestUri, "?")) !== false)
	$requestUri = substr($requestUri, 0, $pos);

$requestUri = preg_replace("/(\\.)(\\.[\\\\\/])/is", "\\1 \\2", $requestUri);
$requestUri = preg_replace("/[\\.\\/\\\\\\x20\\x22\\x3c\\x3e\\x5c]{30,}/", " X ", $requestUri);

$requestUriAbsolute = $io->RelativeToAbsolutePath($requestUri);

$documentRoot = rtrim($_SERVER["DOCUMENT_ROOT"], "/");
$documentRootLength = strlen($documentRoot) + 1;
if ($documentRootLength >= strlen($requestUriAbsolute)
	|| substr($requestUriAbsolute, 0, $documentRootLength) != $documentRoot."/"
	|| substr($requestUriAbsolute, $documentRootLength, 7) == "bitrix/")
{
	die("Path out of range");
}

if (!$io->FileExists($requestUriAbsolute))
{
	if ($io->DirectoryExists($requestUriAbsolute))
	{
		$requestUriAbsolute = $io->CombinePath($requestUriAbsolute, "index.php");
		if (!$io->FileExists($requestUriAbsolute))
			die("File is not found");
	}
	else
	{
		die("File is not found");
	}
}

if (substr($requestUriAbsolute, -4) == ".php")
{
	include($io->GetPhysicalName($requestUriAbsolute));
}
else
{
	if (CFile::IsImage($requestUriAbsolute))
	{
		$f = $io->GetFile($requestUriAbsolute);
		$fsize = $f->GetFileSize();

		$arTypes = Array("jpeg"=>"image/jpeg", "jpe"=>"image/jpeg", "jpg"=>"image/jpeg", "png"=>"image/png", "gif"=>"image/gif", "bmp"=>"image/bmp");
		$ttt = $arTypes[strtolower(substr($requestUriAbsolute, bxstrrpos($requestUriAbsolute, ".") + 1))];

		header("Content-Type: ".$ttt);
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: ".$fsize);
		header("Expires: 0");
		header("Cache-Control: no-cache, must-revalidate");
		header("Pragma: no-cache");
		$f->ReadFile();
	}
}
?>