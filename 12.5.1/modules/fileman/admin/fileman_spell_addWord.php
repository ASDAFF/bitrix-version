<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/fileman/prolog.php");
if (!$USER->CanDoOperation('fileman_edit_existent_files') || !check_bitrix_sessid()) die();
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/fileman/include.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/fileman/admin/fileman_spellChecker.php");

function replacer($str)
{
	$str = CFileMan::SecurePathVar($str);
	$str = preg_replace("/[^a-zA-Z0-9_\.-\+]/is", "_", $str);
	return $str;
}

$word = (isset($_POST['word'])) ? $_POST['word'] : false;
$lang = replacer((isset($_GET['BXLang'])) ? $_GET['BXLang'] : 'en');
$use_pspell = (isset($_GET['use_pspell'])) ? $_GET['use_pspell'] : true;
$use_custom_spell = (isset($_GET['use_custom_spell'])) ? $_GET['use_custom_spell'] : true;

$SC = new spellChecker();

$path = replacer($_SERVER["DOCUMENT_ROOT"].COption::GetOptionString('fileman', "user_dics_path", "/bitrix/modules/fileman/u_dics"));
if (!is_dir($path))
	mkdir($path, BX_DIR_PERMISSIONS);

$lang_path = $path.'/'.$lang;
if (!is_dir($lang_path))
	mkdir($lang_path, BX_DIR_PERMISSIONS);
if (COption::GetOptionString('fileman', "use_separeted_dics", "Y")=="Y")
{
	$user_path = $lang_path.'/'.$USER->GetID();
	if (!is_dir($user_path))
		mkdir($user_path, BX_DIR_PERMISSIONS);
	$path = $user_path;
}
else
	$path = $lang_path;

$SC->init($lang, 2, $use_pspell, $use_custom_spell, "", PSPELL_FAST, $path."/custom.pws");
$SC->addWord($word);

$node = 1;
$encoding = "Windows-1251";
header('Content-Type: text/xml');
echo '<?xml version="1.0" encoding="'.$encoding.'" standalone="yes"?>';	
echo '<root>'.$node.'</root>';
?>