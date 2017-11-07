<?
/*patchlimitationmutatormark1*/
CModule::AddAutoloadClasses(
	"fileman",
	array(
		"CLightHTMLEditor" => "classes/general/light_editor.php",
		"CEditorUtils" => "classes/general/editor_utils.php",
		"CMedialib" => "classes/general/medialib.php",
		"CEventFileman" => "classes/general/fileman_event_list.php",
		"CCodeEditor" => "classes/general/code_editor.php",
		"CFileInput" => "classes/general/file_input.php",
		"CMedialibTabControl" => "classes/general/medialib.php",
		"CSticker" => "classes/general/sticker.php",
		//"CAdminContextMenuML" => "classes/general/medialib_admin.php"
	)
);

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/fileman/lang.php");
include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin_tools.php");
include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/fileman/fileman.php");
include_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/".$GLOBALS["DBType"]."/favorites.php");
include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/fileman/properties.php");
/*patchlimitationmutatormark2*/

CJSCore::RegisterExt('file_input', array(
	'js' => '/bitrix/js/fileman/core_file_input.js',
	'lang' => '/bitrix/modules/fileman/lang/'.LANGUAGE_ID.'/classes/general/file_input.php'
));
?>
