<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/fileman/prolog.php");
if (!$USER->CanDoOperation('fileman_edit_existent_files')) die();
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/fileman/include.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/fileman/admin/fileman_spellChecker.php");

$wordList = (isset($_POST['wordlist'])) ? explode(",",$_POST['wordlist']) : false;
$arr = explode(",",$_POST['wordlist']);
$use_pspell = (isset($_GET['usePspell'])) ? $_GET['usePspell'] : "Y";
$use_custom_spell = (isset($_GET['useCustomSpell'])) ? $_GET['useCustomSpell'] : "Y";

$SC = new spellChecker();

$path = $_SERVER["DOCUMENT_ROOT"].COption::GetOptionString('fileman', "user_dics_path", "/bitrix/modules/fileman/u_dics");
if (!is_dir($path))
	mkdir($path, BX_DIR_PERMISSIONS);

$lang = "en";
if (isset($_GET['BXLang']))
{
	$rsLang = CLanguage::GetList($by="sort", $order="desc");
	while ($arLang = $rsLang->Fetch())
	{
		if ($_GET['BXLang'] == $arLang["LID"])
		{
			$lang = $_GET['BXLang'];
			break;
		}
	}
}

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

if (is_dir($path))
{
	$SC->init($lang,2,$use_pspell,$use_custom_spell,$path,PSPELL_FAST,$path."/custom.pws");
	$SC->checkArr($wordList);

	$node = '';	
	foreach ($SC->wrongWordArr as $resultElement) 
	{
		$node .= '<el>';
		$node .= '<ind>'.$resultElement[0].'</ind>';
		if (!$resultElement[1]) 
		{
			$node .= '<sug>none</sug>';
		} 
		else 
		{
			$node .= '<sug>';
			foreach ($resultElement[1] as $suggestion) 
			{
				$node .= $suggestion.',';
			}
			$node = substr($node, 0, -1);
			$node .= '</sug>';
		}
		$node .= '</el>';
	}
}
else
{
	$node = "<el><ind>error</ind><sug>none</sug></el>";
}
//$node = "<el><ind>".$lang." ::: ".COption::GetOptionString('fileman', "use_separeted_dics", "+++")."</ind><sug>none</sug></el>";
//$node = "<el><ind>".$user_path."/custom.pws :: ".COption::GetOptionString($module_id, "use_separeted_dics", "Y")."</ind><sug>none</sug></el>";
//$node = "<el><ind>".$wordList[0]."</ind><sug>none</sug></el>";
//$node = "<el><ind>".count($arr)."</ind><sug>none</sug></el>";
//$node = "<el><ind>".count($SC->wrongWordArr)."</ind><sug>none</sug></el>";
//$node = "<el><ind>".count($SC->wrongWordArr[0])."</ind><sug>none</sug></el>";
//$node = "<el><ind>use_pspell = ".$use_pspell."use_custom_spell = ".$use_custom_spell."</ind><sug>none</sug></el>";
//$encoding = "UTF-8";
$encoding = "Windows-1251";
header('Content-Type: text/xml');
echo '<?xml version="1.0" encoding="'.$encoding.'" standalone="yes"?>';	
echo '<root>'.$node.'</root>';
?>