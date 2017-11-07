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
		"CSnippets" => "classes/general/snippets.php",
		"CAdminContextMenuML" => "classes/general/medialib_admin.php",
		"CHTMLEditor" => "classes/general/html_editor.php",
		"CComponentParamsManager" => "classes/general/component_params_manager.php"
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

//on update method still not exist
if(method_exists($GLOBALS["APPLICATION"], 'AddJSKernelInfo'))
{
	$GLOBALS["APPLICATION"]->AddJSKernelInfo(
		'fileman',
		array(
			'/bitrix/js/fileman/light_editor/le_dialogs.js', '/bitrix/js/fileman/light_editor/le_controls.js',
			'/bitrix/js/fileman/light_editor/le_toolbarbuttons.js', '/bitrix/js/fileman/light_editor/le_core.js'
		)
	);

	$GLOBALS["APPLICATION"]->AddCSSKernelInfo('fileman',array('/bitrix/js/fileman/light_editor/light_editor.css'));
}
