<?
/*
##############################################
# Bitrix: SiteManager                        #
# Copyright (c) 2009 Bitrix                  #
# http://www.bitrixsoft.com                  #
# mailto:sources@bitrixsoft.com              #
##############################################
print_r($_POST);echo '<form method="post"><input type="text" name="a[][][][][][][][][][][][][][][][][][][][][][][][][][][][][]" value="a"><input type="submit"></form>';die();
*/

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/bizproc/prolog.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/bizproc/include.php");
CUtil::InitJSCore(array("window", "ajax")); 

IncludeModuleLangFile(__FILE__);

$APPLICATION->SetTitle(GetMessage("BIZPROC_WFEDIT_TITLE_EDIT"));

$document_type = preg_replace("/[^0-9A-Za-z_-]/", "", $_REQUEST['document_type']);

$strFatalError = false;
$canWrite = false;
$arTemplate = false;
$ID = IntVal($_REQUEST['ID']);
if($ID > 0)
{
	$dbTemplatesList = CBPWorkflowTemplateLoader::GetList(Array(), Array("ID"=>$ID));
	if($arTemplate = $dbTemplatesList->Fetch())
	{
		$canWrite = CBPDocument::CanUserOperateDocumentType(
			CBPCanUserOperateOperation::CreateWorkflow,
			$GLOBALS["USER"]->GetID(),
			$arTemplate["DOCUMENT_TYPE"]
		);

		$document_type = $arTemplate["DOCUMENT_TYPE"][2];

		$workflowTemplateName = $arTemplate["NAME"];
		$workflowTemplateDescription = $arTemplate["DESCRIPTION"];
		$workflowTemplateAutostart = $arTemplate["AUTO_EXECUTE"];
		$arWorkflowTemplate = $arTemplate["TEMPLATE"];
		$arWorkflowParameters = $arTemplate["PARAMETERS"];
		$arWorkflowVariables = $arTemplate["VARIABLES"];
	}
	else
		$ID = 0;
}

if($ID <= 0)
{
	if(strlen($document_type)<=0)
		$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED")." ".GetMessage("BIZPROC_WFEDIT_ERROR_TYPE"));

	$canWrite = CBPDocument::CanUserOperateDocumentType(
			CBPCanUserOperateOperation::CreateWorkflow,
			$GLOBALS["USER"]->GetID(),
			array(MODULE_ID, ENTITY, $document_type)
		);

	$workflowTemplateName = GetMessage("BIZPROC_WFEDIT_DEFAULT_TITLE");
	$workflowTemplateDescription = '';
	$workflowTemplateAutostart = 1;

	if($_GET['init']=='statemachine')
	{
		$arWorkflowTemplate = array(
			array(
				"Type" => "StateMachineWorkflowActivity",
				"Name" => "Template",
				"Properties" => array(),
				"Children" => array()
				)
			);
	}
	else
	{
		$arWorkflowTemplate = array(
			array(
				"Type" => "SequentialWorkflowActivity",
				"Name" => "Template",
				"Properties" => array(),
				"Children" => array()
				)
			);
	}

	$arWorkflowParameters =  Array();
	$arWorkflowVariables = Array();
}

if(!$canWrite)
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

//print_r($arWorkflowTemplate);
//print_r($arWorkflowParameters);
function print_rrr($var)
{
	if(is_array($var))
	{
		if($var == array_values($var))
		{
			foreach($var as $key => $value)
			{
				$var[$key] = print_rrr($value);
			}
			return "Array(".implode(", ", $var).")";
		}

		$res = "\nArray(\n";
		$first = true;
		foreach($var as $key => $value)
		{
			if($first)
				$first = false;
			else
				$res .= ",\n";
			$res .= "'".CUtil::JSEscape($key)."' => ".print_rrr($value);
		}
		$res .= "\n)";

		return $res;
	}
	elseif(is_bool($var))
	{
		if($var === true)
			return 'true';
		else
			return 'false';
	}
	else
		return "'".CUtil::JSEscape($var)."'";

}
//echo print_rrr($arTemplate);


//////////////////////////////////////////
// AJAX
//////////////////////////////////////////
$back_url = "/bitrix/admin/".MODULE_ID."_bizproc_workflow_admin.php?lang=".LANGUAGE_ID."&entity=".urlencode(ENTITY)."&document_type=".$document_type."&back_url_list=/".urlencode("/".ltrim(trim($_REQUEST["back_url_list"]), "\\/"));
if(strlen($_REQUEST["back_url"])>0)
	$back_url = "/".ltrim(trim($_REQUEST["back_url"]), "/\\");

if($_SERVER['REQUEST_METHOD']=='POST' && $_REQUEST['saveajax']=='Y' && check_bitrix_sessid())
{
	CUtil::DecodeUriComponent($_POST);

	if($_REQUEST['saveuserparams']=='Y')
	{
		CUserOptions::SetOption("~bizprocdesigner", "activity_settings", serialize($_POST['USER_PARAMS']));
		die();
	}


	if(LANG_CHARSET != "UTF-8")
	{
		if(is_array($_POST["arWorkflowParameters"]))
		{
			foreach($_POST["arWorkflowParameters"] as $name=>$param)
			{
				if(is_array($_POST["arWorkflowParameters"][$name]["Options"]))
				{
					$newarr = Array();
					foreach($_POST["arWorkflowParameters"][$name]["Options"] as $k=>$v)
						$newarr[$GLOBALS["APPLICATION"]->ConvertCharset($k, "UTF-8", LANG_CHARSET)] = $v;
					$_POST["arWorkflowParameters"][$name]["Options"] = $newarr;
				}
			}
		}
	}

	if(LANG_CHARSET != "UTF-8" && is_array($_POST["arWorkflowVariables"]))
	{
		foreach($_POST["arWorkflowVariables"] as $name=>$param)
		{
			if(is_array($_POST["arWorkflowVariables"][$name]["Options"]))
			{
				$newarr = Array();
				foreach($_POST["arWorkflowVariables"][$name]["Options"] as $k=>$v)
					$newarr[$GLOBALS["APPLICATION"]->ConvertCharset($k, "UTF-8", LANG_CHARSET)] = $v;
				$_POST["arWorkflowVariables"][$name]["Options"] = $newarr;
			}
		}
	}
	//print_r($_POST["arWorkflowTemplate"]);

	$arFields = Array(
		"DOCUMENT_TYPE" => array(MODULE_ID, ENTITY, $document_type),
//		"ACTIVE" 		=> $_POST["ACTIVE"],
		"AUTO_EXECUTE" 	=> $_POST["workflowTemplateAutostart"],
		"NAME" 			=> $_POST["workflowTemplateName"],
		"DESCRIPTION" 	=> $_POST["workflowTemplateDescription"],
		"TEMPLATE" 		=> $_POST["arWorkflowTemplate"],
		"PARAMETERS"	=> $_POST["arWorkflowParameters"],
		"VARIABLES" 	=> $_POST["arWorkflowVariables"],
		"USER_ID"		=> intval($USER->GetID()),
		"MODIFIER_USER" => new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser),
		);

	if(!is_array($arFields["VARIABLES"]))
		$arFields["VARIABLES"] = Array();

	if($arTemplate["TEMPLATE"]!=$arFields["TEMPLATE"])
		$arFields["SYSTEM_CODE"] = '';

	function wfeexception_handler($e)
	{
		// PHP 5.2.1 bug http://bugs.php.net/bug.php?id=40456
		//print_r($e);
		?>
		<script>
		alert('<?=GetMessage("BIZPROC_WFEDIT_SAVE_ERROR")?> <?=AddSlashes(htmlspecialcharsbx($e->getMessage()))?>');
		</script>
		<?
		die();
	}

	set_exception_handler('wfeexception_handler');
	try
	{
		if($ID>0)
		{
			CBPWorkflowTemplateLoader::Update($ID, $arFields);
		}
		else
			$ID = CBPWorkflowTemplateLoader::Add($arFields);
	}
	catch (Exception $e)
	{
		wfeexception_handler($e);
	}
	restore_exception_handler();
	?>
	<script type="text/javascript">
		window.location = '<?=($_REQUEST["apply"]=="Y"?Cutil::JSEscape("/bitrix/admin/".MODULE_ID."_bizproc_workflow_edit.php?lang=".LANGUAGE_ID."&entity=".AddSlashes(ENTITY)."&ID=".$ID."&back_url_list=".urlencode($_REQUEST["back_url_list"])) : Cutil::JSEscape($back_url))?>';
	</script>
	<?
	die();
}

if($_SERVER['REQUEST_METHOD']=='GET' && $_REQUEST['export_template']=='Y' && check_bitrix_sessid())
{
	$APPLICATION->RestartBuffer();
	if ($ID > 0)
	{
		$datum = CBPWorkflowTemplateLoader::ExportTemplate($ID);

		header("HTTP/1.1 200 OK");
		header("Content-Type: application/force-download; name=\"bp-".$ID.".bpt\"");
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: ".(function_exists('mb_strlen')?mb_strlen($datum, 'ISO-8859-1'):strlen($datum)));
		header("Content-Disposition: attachment; filename=\"bp-".$ID.".bpt\"");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Expires: 0");
		header("Pragma: public");

		echo $datum;
	}
	die();
}

if($_SERVER['REQUEST_METHOD']=='POST' && $_REQUEST['import_template']=='Y' && check_bitrix_sessid())
{
	$APPLICATION->RestartBuffer();
	//CUtil::DecodeUriComponent($_POST);

	$r = 0;
	$errTmp = "";
	if (is_uploaded_file($_FILES['import_template_file']['tmp_name']))
	{
		$f = fopen($_FILES['import_template_file']['tmp_name'], "rb");
		$datum = fread($f, filesize($_FILES['import_template_file']['tmp_name']));
		fclose($f);

		try
		{
			$r = CBPWorkflowTemplateLoader::ImportTemplate(
				$ID,
				array(MODULE_ID, ENTITY, $document_type),
				$_POST["import_template_autostart"],
				$_POST["import_template_name"],
				$_POST["import_template_description"],
				$datum
			);
		}
		catch (Exception $e)
		{
			$errTmp = $e->getMessage();
		}
	}
	?>
	<script>
	<?if (intval($r) <= 0):?>
		alert('<?= GetMessage("BIZPROC_WFEDIT_IMPORT_ERROR").(strlen($errTmp) > 0 ? ": ".$errTmp : "" ) ?>');
	<?else:?>
		<?$ID = $r;?>
	<?endif;?>
	window.location = '/bitrix/admin/<?=MODULE_ID?>_bizproc_workflow_edit.php?<?=($ID>0?"ID=".$ID."&":"")?>'+
		'entity=<?=AddSlashes(urlencode(ENTITY))?>&document_type=<?=AddSlashes(urlencode($document_type))?>&lang=<?=LANGUAGE_ID?>';
	</script>
	<?
	die();
}

$arAllActGroups = Array(
//		"main" => GetMessage("BIZPROC_WFEDIT_CATEGORY_MAIN"),
		"document" => GetMessage("BIZPROC_WFEDIT_CATEGORY_DOC"),
		"logic" => GetMessage("BIZPROC_WFEDIT_CATEGORY_CONSTR"),
		"interaction" => GetMessage("BIZPROC_WFEDIT_CATEGORY_INTER"),
		"other" => GetMessage("BIZPROC_WFEDIT_CATEGORY_OTHER"),
//		"favorities" => "Избранное",
	);

$runtime = CBPRuntime::GetRuntime();
$arAllActivities = $runtime->SearchActivitiesByType("activity");

$aMenu = Array();
$aMenu[] = array(
	"TEXT"=>GetMessage("BIZPROC_WFEDIT_MENU_PARAMS"),
	"TITLE"=>GetMessage("BIZPROC_WFEDIT_MENU_PARAMS_TITLE"),
	"LINK"=>"javascript:BCPShowParams();",
	"ICON"=>"btn_settings",
);

$aMenu[] = array("SEPARATOR"=>true);

$aMenu[] = array(
	"TEXT"=>GetMessage("BIZPROC_WFEDIT_MENU_LIST"),
	"TITLE"=>GetMessage("BIZPROC_WFEDIT_MENU_LIST_TITLE"),
	"LINK"=>"/bitrix/admin/".MODULE_ID."_bizproc_workflow_admin.php?lang=".LANGUAGE_ID."&entity=".AddSlashes(ENTITY)."&document_type=".AddSlashes($document_type)."",
	"ICON"=>"btn_list",
);

$arSubMenu = Array();

$arSubMenu[] = array(
	"TEXT"	    => GetMessage("BIZPROC_WFEDIT_MENU_ADD_STATE"),
	"ACTION"	=> "if(confirm('".GetMessage("BIZPROC_WFEDIT_MENU_ADD_WARN")."'))window.location='/bitrix/admin/".MODULE_ID."_bizproc_workflow_edit.php?lang=".LANGUAGE_ID."&init=statemachine&entity=".AddSlashes(ENTITY)."&document_type=".AddSlashes($document_type)."';",
	"TITLE"	    => GetMessage("BIZPROC_WFEDIT_MENU_ADD_STATE_TITLE"),
);

$arSubMenu[] = array(
	"TEXT"	=> GetMessage("BIZPROC_WFEDIT_MENU_ADD_SEQ"),
	"ACTION"	=> "if(confirm('".GetMessage("BIZPROC_WFEDIT_MENU_ADD_WARN")."'))window.location='/bitrix/admin/".MODULE_ID."_bizproc_workflow_edit.php?lang=".LANGUAGE_ID."&entity=".AddSlashes(ENTITY)."&document_type=".AddSlashes($document_type)."';",
	"TITLE"	=> GetMessage("BIZPROC_WFEDIT_MENU_ADD_SEQ_TITLE"),
);

$aMenu[] = array(
	"TEXT"=>GetMessage("BIZPROC_WFEDIT_MENU_ADD"),
	"TITLE"=>GetMessage("BIZPROC_WFEDIT_MENU_ADD_TITLE"),
	"ICON"=>"btn_new",
	"MENU"=>$arSubMenu
);

$aMenu[] = array("SEPARATOR"=>true);

$aMenu[] = array(
	"TEXT"=>GetMessage("BIZPROC_WFEDIT_MENU_EXPORT"),
	"TITLE"=>GetMessage("BIZPROC_WFEDIT_MENU_EXPORT_TITLE"),
	"LINK"=>"javascript:BCPProcessExport();",
	"ICON"=>"",
);
$aMenu[] = array(
	"TEXT"=>GetMessage("BIZPROC_WFEDIT_MENU_IMPORT"),
	"TITLE"=>GetMessage("BIZPROC_WFEDIT_MENU_IMPORT_TITLE"),
	"LINK"=>"javascript:BCPProcessImport();",
	"ICON"=>"",
);

$context = new CAdminContextMenu($aMenu);

if($ID>0)
	$APPLICATION->SetTitle(GetMessage("BIZPROC_WFEDIT_TITLE_EDIT"));
else
	$APPLICATION->SetTitle(GetMessage("BIZPROC_WFEDIT_TITLE_ADD"));

$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/pubstyles.css");
//$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/jspopup.css");
//$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/calendar.css");

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
?>
<script>
function BCPProcessExport()
{
	window.open('/bitrix/admin/<?=MODULE_ID?>_bizproc_workflow_edit.php?<?=($ID>0?"ID=".$ID."&":"")?>'+
		'entity=<?=AddSlashes(urlencode(ENTITY))?>&document_type=<?=AddSlashes(urlencode($document_type))?>&lang=<?=LANGUAGE_ID?>&<?=bitrix_sessid_get()?>&export_template=Y');
}

function BCPProcessImport()
{
	if (!confirm("<?= GetMessage("BIZPROC_WFEDIT_MENU_IMPORT_PROMT") ?>"))
		return;

	var btnOK = new BX.CWindowButton({
		'title': '<?= GetMessage("BIZPROC_IMPORT_BUTTON") ?>',
		'action': function()
		{
			BX.showWait();

			var _form = document.getElementById('import_template_form');

			var _name = document.getElementById('id_import_template_name');
			var _descr = document.getElementById('id_import_template_description');
			var _auto = document.getElementById('id_import_template_autostart');

			if (_form)
			{
				_name.value = workflowTemplateName;
				_descr.value = workflowTemplateDescription;
				_auto.value = encodeURIComponent(workflowTemplateAutostart);
				_form.submit();
			}

			this.parentWindow.Close();
		}
	});

	new BX.CDialog({
		title: '<?= GetMessage("BIZPROC_IMPORT_TITLE") ?>',
		content: '<form action="/bitrix/admin/<?=MODULE_ID?>_bizproc_workflow_edit.php?<?=($ID>0?"ID=".$ID."&":"")?>entity=<?=AddSlashes(urlencode(ENTITY))?>&document_type=<?=AddSlashes(urlencode($document_type))?>&lang=<?=LANGUAGE_ID?>" method="POST" id="import_template_form" enctype="multipart/form-data"><table cellspacing="0" cellpadding="0" border="0" width="100%"><tr valign="top"><td width="50%" align="right"><?= GetMessage("BIZPROC_IMPORT_FILE") ?>:</td><td width="50%" align="left"><input type="file" size="35" name="import_template_file" value=""></td></tr></table><input type="hidden" name="import_template" value="Y"><input type="hidden" id="id_import_template_name" name="import_template_name" value=""><input type="hidden" name="import_template_description" id="id_import_template_description" value=""><input type="hidden" id="id_import_template_autostart" name="import_template_autostart" value=""><?= bitrix_sessid_post() ?></form>',
		buttons: [btnOK, BX.CDialog.btnCancel],
		width: 500,
		height: 150
	}).Show();
}

function BCPSaveTemplateComplete()
{
}

function BCPSaveUserParams()
{
	var data = JSToPHP(arUserParams, 'USER_PARAMS');

	jsExtLoader.onajaxfinish = BCPSaveTemplateComplete;
	jsExtLoader.startPost('/bitrix/admin/<?=MODULE_ID?>_bizproc_workflow_edit.php?<?=($ID>0?"ID=".$ID."&":"")?>'+
		'entity=<?=AddSlashes(urlencode(ENTITY))?>&document_type=<?=AddSlashes(urlencode($document_type))?>&lang=<?=LANGUAGE_ID?>&<?=bitrix_sessid_get()?>&saveajax=Y&saveuserparams=Y', data);
}

function BCPSaveTemplate(save)
{
	arWorkflowTemplate = Array(rootActivity.Serialize());
	var data =
			'workflowTemplateName=' + encodeURIComponent(workflowTemplateName) + '&' +
			'workflowTemplateDescription=' + encodeURIComponent(workflowTemplateDescription) + '&' +
			'workflowTemplateAutostart=' + encodeURIComponent(workflowTemplateAutostart) + '&' +
			JSToPHP(arWorkflowParameters, 'arWorkflowParameters') + '&' +
			JSToPHP(arWorkflowVariables, 'arWorkflowVariables') + '&' +
			JSToPHP(arWorkflowTemplate, 'arWorkflowTemplate');

	jsExtLoader.onajaxfinish = BCPSaveTemplateComplete;
	// TODO: add sessid
	jsExtLoader.startPost('/bitrix/admin/<?=MODULE_ID?>_bizproc_workflow_edit.php?<?=($ID>0?"ID=".$ID."&":"")?>'+
		'entity=<?=AddSlashes(urlencode(ENTITY))?>&document_type=<?=AddSlashes(urlencode($document_type))?>&lang=<?=LANGUAGE_ID?>&<?=bitrix_sessid_get()?>&saveajax=Y'+
		(save ? '&back_url=<?=AddSlashes(urlencode($back_url))?>': '&apply=Y')
		, data);
}

function BCPShowParams()
{
	(new BX.CAdminDialog({
		'content_url': "/bitrix/admin/<?=MODULE_ID?>_bizproc_wf_settings.php?mode=public&bxpublic=Y&lang=<?=LANGUAGE_ID?>&entity=<?=ENTITY?>", 
		'content_post': 'workflowTemplateName=' 		+ encodeURIComponent(workflowTemplateName) + '&' +
			'workflowTemplateDescription=' 	+ encodeURIComponent(workflowTemplateDescription) + '&' +
			'workflowTemplateAutostart=' 	+ encodeURIComponent(workflowTemplateAutostart) + '&' +
			'document_type=' 				+ encodeURIComponent(document_type) + '&' +
			'<?= bitrix_sessid_get() ?>' + '&' +
			JSToPHP(arWorkflowParameters, 'arWorkflowParameters')  + '&' +
			JSToPHP(arWorkflowVariables, 'arWorkflowVariables')  + '&' +
			JSToPHP(Array(rootActivity.Serialize()), 'arWorkflowTemplate'), 
		'height': 400,
		'width': 900
	})).Show();
}
</script>
<?
$context->Show();
?>
<style>
div#bx_admin_form table.edit-tab td div.edit-tab-inner {height: 310px;}
a.activitydel, a.activitymin, a.activityset {width:11px; height: 11px; float: right; cursor: pointer; margin: 4px;}
.activity a.activitydel {background: url(/bitrix/images/bizproc/act_button_del.gif) 50% center no-repeat;}
.activity a.activityset {background: url(/bitrix/images/bizproc/act_button_sett.gif) 50% center no-repeat;}
.activity a.activitymin {background: url(/bitrix/images/bizproc/act_button_min.gif) 50% center no-repeat;}

a.activitydel:hover {border: 1px #999999 solid; margin: 3px;}
a.activityset:hover {border: 1px #999999 solid; margin: 3px;}
a.activitymin:hover {border: 1px #999999 solid; margin: 3px;}

.parallelcontainer {position: relative; top: -12px;}

td.statdel, td.statset {width:20px; height: 10px; cursor: pointer; margin-top: 7px; margin-right: 7px;}
td.statdel {background: url(/bitrix/images/bizproc/stat_del.gif) 50% center no-repeat;}
td.statset {background: url(/bitrix/images/bizproc/stat_sett.gif) 50% center no-repeat;}

.activity {}
.activity .activityhead {background: url(/bitrix/images/bizproc/act_h.gif) left top repeat-x; height: 17px; overflow-y: hidden; background-color: #fec260;}
.activity .activityheadr {background: url(/bitrix/images/bizproc/act_hr.gif) right top no-repeat;}
.activity .activityheadl {background: url(/bitrix/images/bizproc/act_hl.gif) left top no-repeat; height:17px; padding-left: 3px;}

.activityerr {}
.activityerr .activityhead {background: url(/bitrix/images/bizproc/err_act_h.gif) left top repeat-x; height: 17px; overflow-y: hidden; background-color: #ffb3b3;}
.activityerr .activityheadr {background: url(/bitrix/images/bizproc/err_act_hr.gif) right top no-repeat;}
.activityerr .activityheadl {background: url(/bitrix/images/bizproc/err_act_hl.gif) left top no-repeat; height:17px; padding-left: 3px;}

.activityerr a.activitydel {background: url(/bitrix/images/bizproc/err_act_button_del.gif) 50% center no-repeat;}
.activityerr a.activityset {background: url(/bitrix/images/bizproc/err_act_button_sett.gif) 50% center no-repeat;}

div.bx-core-dialog-content input {padding: 6px !important;}
div.bx-core-dialog-content input[type=button] {padding-left: 11px !important; padding-right: 11px !important;}
</style>
<script src="/bitrix/js/main/public_tools.js"></script>
<script src="/bitrix/js/bizproc/bizproc.js"></script>

<?
$JSMESS = Array();
function GetJSLangMess($f, $actId)
{
	$MESS = Array();
	if(file_exists($f."/lang/en/".$actId.".js.php"))
		include($f."/lang/en/".$actId.".js.php");
	if(file_exists($f."/lang/".LANGUAGE_ID."/".$actId.".js.php"))
		include($f."/lang/".LANGUAGE_ID."/".$actId.".js.php");

	global $JSMESS;
	foreach($MESS as $k=>$v)
		$JSMESS[$k] = $v;
}

foreach($arAllActivities as $actId => $actProps)
{
	$actPath = substr($actProps["PATH_TO_ACTIVITY"], strlen($_SERVER["DOCUMENT_ROOT"]));
	if(file_exists($actProps["PATH_TO_ACTIVITY"]."/".$actId.".js"))
	{
		echo '<script src="'.$actPath.'/'.$actId.'.js"></script>';
		GetJSLangMess($actProps["PATH_TO_ACTIVITY"], $actId);
	}

	if(file_exists($actProps["PATH_TO_ACTIVITY"]."/".$actId.".css"))
		echo '<link rel="stylesheet" type="text/css" href="'.$actPath.'/'.$actId.'.css">';

	if(file_exists($actProps["PATH_TO_ACTIVITY"]."/icon.gif"))
		$arAllActivities[$actId]['ICON'] = $actPath.'/icon.gif';

	unset($arAllActivities[$actId]['PATH_TO_ACTIVITY']);
}
?>
<script>
var arAllActivities = <?=CUtil::PhpToJSObject($arAllActivities)?>;
var arAllActGroups = <?=CUtil::PhpToJSObject($arAllActGroups)?>;
var arWorkflowParameters = <?=CUtil::PhpToJSObject($arWorkflowParameters)?>;
var arWorkflowVariables = <?=CUtil::PhpToJSObject($arWorkflowVariables)?>;
var arWorkflowTemplate = <?=CUtil::PhpToJSObject($arWorkflowTemplate[0])?>;

var workflowTemplateName = <?=CUtil::PhpToJSObject($workflowTemplateName)?>;
var workflowTemplateDescription = <?=CUtil::PhpToJSObject($workflowTemplateDescription)?>;
var workflowTemplateAutostart = <?=CUtil::PhpToJSObject($workflowTemplateAutostart)?>;

var document_type = <?=CUtil::PhpToJSObject($document_type)?>;
var MODULE_ID = <?=CUtil::PhpToJSObject(MODULE_ID)?>;
var ENTITY = <?=CUtil::PhpToJSObject(ENTITY)?>;
var BPMESS = <?=CUtil::PhpToJSObject($JSMESS)?>;

var arUserParams = <?=CUtil::PhpToJSObject(unserialize(CUserOptions::GetOption("~bizprocdesigner", "activity_settings", serialize(array("groups"=>array())))))?>;

var CURRENT_SITE_ID = <?=CUtil::PhpToJSObject(SITE_ID)?>;

var arAllId = {};
var rootActivity;

function BizProcRender(oActivity, divParent, t)
{
	rootActivity = CreateActivity(oActivity);
	rootActivity.Draw(divParent);
}

function ReDraw()
{
	var p;
	if(rootActivity.Type == 'SequentialWorkflowActivity')
	{
		if(rootActivity.swfWorkspaceDiv)
			p = rootActivity.swfWorkspaceDiv.scrollTop;

		while(rootActivity.childActivities.length>0)
			rootActivity.RemoveChild(rootActivity.childActivities[0]);

		rootActivity.Init(arWorkflowTemplate);
		rootActivity.DrawActivities();

		rootActivity.swfWorkspaceDiv.scrollTop = p;
	}
	else
	{
		if(rootActivity._redrawObject)
		{
			if(rootActivity._redrawObject.swfWorkspaceDiv)
				p = rootActivity._redrawObject.swfWorkspaceDiv.scrollTop;

			while(rootActivity._redrawObject.childActivities.length>0)
				rootActivity._redrawObject.RemoveChild(rootActivity._redrawObject.childActivities[0]);

			var act = FindActivityById(arWorkflowTemplate, rootActivity._redrawObject.Name);

			rootActivity._redrawObject.Init(act);
			rootActivity._redrawObject.DrawActivities();

			rootActivity._redrawObject.swfWorkspaceDiv.scrollTop = p;
		}
		else
		{
			var d = rootActivity.Table.parentNode;

			while(rootActivity.childActivities.length>0)
				rootActivity.RemoveChild(rootActivity.childActivities[0]);

			rootActivity.Init(arWorkflowTemplate);
			rootActivity.RemoveResources();
			rootActivity.Draw(d);
		}
	}
}


function start()
{
	var t = document.getElementById('wf1');
	if (!t)
	{
		setTimeout(function () {start();}, 1000);
		return;
	}
	BizProcRender(arWorkflowTemplate, document.getElementById('wf1'));
	<?if($ID<=0):?>
	BCPShowParams();
	<?endif;?>
}

setTimeout("start()", 0);
</script>
<form>

<div id="wf1" style="border-bottom: 2px #efefef dotted; background-color: white; border: solid 1px #DCE7ED; padding: 16px;" ></div>

<div id="bizprocsavebuttons">
<br>
<input type="button" onclick="BCPSaveTemplate(true);" value="<?echo GetMessage("BIZPROC_WFEDIT_SAVE_BUTTON")?>">
<input type="button" onclick="BCPSaveTemplate();" value="<?echo GetMessage("BIZPROC_WFEDIT_APPLY_BUTTON")?>">
<input type="button" onclick="window.location='<?=Cutil::addslashes(htmlspecialcharsbx($back_url))?>';" value="<?echo GetMessage("BIZPROC_WFEDIT_CANCEL_BUTTON")?>">
</div>

</form>
<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>
