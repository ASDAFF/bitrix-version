<?
/*
##############################################
# Bitrix Site Manager                        #
# Copyright (c) 2002-2007 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:admin@bitrixsoft.com                #
##############################################
*/
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/translate/prolog.php");
$TRANS_RIGHT = $APPLICATION->GetGroupRight("translate");
if($TRANS_RIGHT=="D") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
if (!check_bitrix_sessid()) die();
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/translate/include.php");

$arTLangs = GetTLangList();

$NO_TRANSLATE = array_key_exists('download_translate_lang', $_POST) && $_POST['download_translate_lang'] == 'N';

if(preg_match("#\.\.[\\/]#".BX_UTF_PCRE_MODIFIER, $path))
	$path = "";

	
$path = Rel2Abs("/", "/".$path."/");	
	
$IS_LANG_DIR = is_lang_dir($path);

if ($IS_LANG_DIR)
{
	//reset($arTLangs);
	foreach ($arTLangs as $hlang)
	{
		$ph = add_lang_id($path, $hlang, $arTLangs);
		if (strlen($ph)>0) GetTDirList($ph, true);
		$ph = "";
	}
}
else GetTDirList($path, true);

$arrCSV = GetTCSVArray();
$strCSV = '"file";"key";';
foreach ($arTLangs as $l)
{
	$strCSV .= '"'.$l.'";';
}
$strCSV .= "\r\n";


foreach ($arrCSV as $file => $arTranslations)
{
	foreach ($arTranslations as $key => $arLangTexts)
	{
		$_strCSV = '';
		$_strCSV .= '"'.$file.'";"'.$key.'";';
		$_noTranslate = false;
		foreach ($arTLangs as $l)
		{
			$val = str_replace('"', '""', $arLangTexts[$l]);
			$val = str_replace("\\", "\\\\", $val);
			$_strCSV .= '"'.$val.'";';
			if (empty($val)){
				$_noTranslate = true;
			}
		}
		$_strCSV .= "\r\n";
	    if (!$NO_TRANSLATE || ( $NO_TRANSLATE && $_noTranslate)) {
			$strCSV .= $_strCSV;
		}
	}
}

$csv_fn = trim(str_replace('/', '_', $path), '_') . '.csv';

ob_get_clean();

if(CModule::IncludeModule("compression"))
	CCompress::Disable2048Spaces();

header("Content-Type: text/csv; charset=".LANG_CHARSET);
header('Content-Disposition: attachment; filename="'.$csv_fn.'"');

echo $strCSV;

require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin_after.php");
?>