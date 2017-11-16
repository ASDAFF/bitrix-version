<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
//$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/adminstyles.css");
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/pubstyles.css");
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/jspopup.css");
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/calendar.css");
$APPLICATION->AddHeadScript('/bitrix/js/main/utils.js');
$APPLICATION->AddHeadScript('/bitrix/js/main/popup_menu.js');
$APPLICATION->AddHeadScript('/bitrix/js/main/admin_tools.js');
CUtil::InitJSCore(array("window", "ajax")); 
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/admin_lib.php");
//////////////////////////////////////////////////////////////////////////////

$ID = $arResult["ID"];

$aMenu = Array();
$aMenu[] = array(
	"TEXT"=>GetMessage("BIZPROC_WFEDIT_MENU_PARAMS"),
	"TITLE"=>GetMessage("BIZPROC_WFEDIT_MENU_PARAMS_TITLE"),
	"LINK"=>"javascript:BCPShowParams();",
	"ICON"=>"btn_settings",
);

$aMenu[] = array("SEPARATOR"=>"Y");

$aMenu[] = array(
	"TEXT"=>((strlen($arParams["BIZPROC_EDIT_MENU_LIST_MESSAGE"]) > 0) ? htmlspecialcharsbx($arParams["BIZPROC_EDIT_MENU_LIST_MESSAGE"]) : GetMessage("BIZPROC_WFEDIT_MENU_LIST")),
	"TITLE"=>((strlen($arParams["BIZPROC_EDIT_MENU_LIST_TITLE_MESSAGE"]) > 0) ? htmlspecialcharsbx($arParams["BIZPROC_EDIT_MENU_LIST_TITLE_MESSAGE"]) : GetMessage("BIZPROC_WFEDIT_MENU_LIST_TITLE")),
	"LINK"=>$arResult['LIST_PAGE_URL'],
	"ICON"=>"btn_list",
);

if (!array_key_exists("SKIP_BP_TYPE_SELECT", $arParams) || $arParams["SKIP_BP_TYPE_SELECT"] != "Y")
{
	$arSubMenu = Array();

	$arSubMenu[] = array(
		"TEXT"	=> GetMessage("BIZPROC_WFEDIT_MENU_ADD_STATE"),
		"TITLE"	=> GetMessage("BIZPROC_WFEDIT_MENU_ADD_STATE_TITLE"),
		"ONCLICK"=> "if(confirm('".GetMessage("BIZPROC_WFEDIT_MENU_ADD_WARN")."'))window.location='".str_replace("#ID#", "0", $arResult["EDIT_PAGE_TEMPLATE"]).(strpos($arResult["EDIT_PAGE_TEMPLATE"], "?")?"&":"?")."init=statemachine';"
	);

	$arSubMenu[] = array(
		"TEXT"	=> GetMessage("BIZPROC_WFEDIT_MENU_ADD_SEQ"),
		"TITLE"	=> GetMessage("BIZPROC_WFEDIT_MENU_ADD_SEQ_TITLE"),
		"ONCLICK" => "if(confirm('".GetMessage("BIZPROC_WFEDIT_MENU_ADD_WARN")."'))window.location='".str_replace("#ID#", "0", $arResult["EDIT_PAGE_TEMPLATE"]).(strpos($arResult["EDIT_PAGE_TEMPLATE"], "?")?"&":"?")."';"
	);

	$aMenu[] = array(
		"TEXT"=>GetMessage("BIZPROC_WFEDIT_MENU_ADD"),
		"TITLE"=>GetMessage("BIZPROC_WFEDIT_MENU_ADD_TITLE"),
		"ICON"=>"btn_new",
		"MENU"=>$arSubMenu
	);
}

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

?>
<script>
function BCPProcessExport()
{
	<?$v = str_replace("&amp;", "&", str_replace("#ID#", $ID, $arResult["EDIT_PAGE_TEMPLATE"]));?>
	window.open('<?=$v?><?if(strpos($v, "?")):?>&<?else:?>?<?endif?>export_template=Y&<?=bitrix_sessid_get()?>');
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
		content: '<form action="<?= POST_FORM_ACTION_URI ?>" method="POST" id="import_template_form" enctype="multipart/form-data"><table cellspacing="0" cellpadding="0" border="0" width="100%"><tr valign="top"><td width="50%" align="right"><?= GetMessage("BIZPROC_IMPORT_FILE") ?>:</td><td width="50%" align="left"><input type="file" size="35" name="import_template_file" value=""></td></tr></table><input type="hidden" name="import_template" value="Y"><input type="hidden" id="id_import_template_name" name="import_template_name" value=""><input type="hidden" name="import_template_description" id="id_import_template_description" value=""><input type="hidden" id="id_import_template_autostart" name="import_template_autostart" value=""><?= bitrix_sessid_post() ?></form>',
		buttons: [btnOK, BX.CDialog.btnCancel],
		width: 500,
		height: 150
	}).Show();
}

function BCPSaveTemplateComplete()
{
}

<?$v = str_replace("&amp;", "&", POST_FORM_ACTION_URI);?>

function BCPSaveUserParams()
{
	var data = JSToPHP(arUserParams, 'USER_PARAMS');

	jsExtLoader.onajaxfinish = BCPSaveTemplateComplete;
	jsExtLoader.startPost('<?=$v?><?if(strpos($v, "?")):?>&<?else:?>?<?endif?><?=bitrix_sessid_get()?>&saveajax=Y&saveuserparams=Y', data);
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
	jsExtLoader.startPost('<?=$v?><?if(strpos($v, "?")):?>&<?else:?>?<?endif?><?=bitrix_sessid_get()?>&saveajax=Y'+
		(save ? '': '&apply=Y'),
		data);

/*	jsExtLoader.startPost('<?=str_replace("#ID#", intval($ID), $arResult["EDIT_PAGE_TEMPLATE"])?><?if(strpos($arResult["EDIT_PAGE_TEMPLATE"], "?")):?>&<?else:?>?<?endif?>'+
		'saveajax=Y'+
		(save ? '': '&apply=Y')
		, data);*/
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
	'height': 500,
	'width': 800,
	'resizable' : false
	})).Show(); 
}
</script>
<div style="background-color: #FFFFFF;">
<?
if($arParams['SHOW_TOOLBAR']=='Y'):
?>
<?
	$APPLICATION->IncludeComponent(
		"bitrix:main.interface.toolbar",
		"",
		array(
			"BUTTONS"=>$aMenu,
		),
		$component, array("HIDE_ICONS" => "Y")
	);
?>
<?endif?>

<style>
div#bx_admin_form table.edit-tab td div.edit-tab-inner {height: 310px;}
a.activitydel, a.activityset, a.activitymin {width:11px; height: 11px; float: right; cursor: pointer; margin: 4px;}
.activity a.activitydel {background: url(/bitrix/images/bizproc/act_button_del.gif) 50% center no-repeat;}
.activity a.activityset {background: url(/bitrix/images/bizproc/act_button_sett.gif) 50% center no-repeat;}
.activity a.activitymin {background: url(/bitrix/images/bizproc/act_button_min.gif) 50% center no-repeat;}

a.activitydel:hover {border: 1px #999999 solid; margin: 3px;}
a.activityset:hover {border: 1px #999999 solid; margin: 3px;}
a.activitymin:hover {border: 1px #999999 solid; margin: 3px;}

.parallelcontainer {position: relative; top: -12px;}

.btn_settings {background-image:url(/bitrix/images/bizproc/settings.gif);}
.btn_list {background-image:url(/bitrix/images/bizproc/list.gif);}
.btn_new {background-image:url(/bitrix/images/bizproc/new.gif);}

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

</style>
<script src="/bitrix/js/main/public_tools.js"></script>
<script src="/bitrix/js/bizproc/bizproc.js"></script>

<?
global $JSMESS;
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

foreach($arResult['ACTIVITIES'] as $actId => $actProps)
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
		$arResult['ACTIVITIES'][$actId]['ICON'] = $actPath.'/icon.gif';

	unset($arResult['ACTIVITIES'][$actId]['PATH_TO_ACTIVITY']);
}
?>
<script>
var arAllActivities = <?=CUtil::PhpToJSObject($arResult['ACTIVITIES'])?>;
var arAllActGroups = <?=CUtil::PhpToJSObject($arResult['ACTIVITY_GROUPS'])?>;
var arWorkflowParameters = <?=CUtil::PhpToJSObject($arResult['PARAMETERS'])?>;
var arWorkflowVariables = <?=CUtil::PhpToJSObject($arResult['VARIABLES'])?>;
var arWorkflowTemplate = <?=CUtil::PhpToJSObject($arResult['TEMPLATE'][0])?>;

var workflowTemplateName = <?=CUtil::PhpToJSObject($arResult['TEMPLATE_NAME'])?>;
var workflowTemplateDescription = <?=CUtil::PhpToJSObject($arResult['TEMPLATE_DESC'])?>;
var workflowTemplateAutostart = <?=CUtil::PhpToJSObject($arResult['TEMPLATE_AUTOSTART'])?>;

var document_type = <?=CUtil::PhpToJSObject($arResult['DOCUMENT_TYPE'])?>;
var MODULE_ID = <?=CUtil::PhpToJSObject(MODULE_ID)?>;
var ENTITY = <?=CUtil::PhpToJSObject(ENTITY)?>;
var BPMESS = <?=CUtil::PhpToJSObject($JSMESS)?>;

var CURRENT_SITE_ID = <?=CUtil::PhpToJSObject(SITE_ID)?>;

var arUserParams = <?=CUtil::PhpToJSObject($arResult['USER_PARAMS'])?>;


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

<div id="wf1" style="width: 100%; border-bottom: 2px #efefef dotted; " ></div>

<div id="bizprocsavebuttons">
<br>
<input type="button" onclick="BCPSaveTemplate(true);" value="<?echo GetMessage("BIZPROC_WFEDIT_SAVE_BUTTON")?>">
<input type="button" onclick="BCPSaveTemplate();" value="<?echo GetMessage("BIZPROC_WFEDIT_APPLY_BUTTON")?>">
<input type="button" onclick="window.location='<?=htmlspecialcharsbx(CUtil::JSEscape($arResult['LIST_PAGE_URL']))?>';" value="<?echo GetMessage("BIZPROC_WFEDIT_CANCEL_BUTTON")?>">
</div>

</form>
</div>
