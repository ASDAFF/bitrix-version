<?
/*
##############################################
# Bitrix: SiteManager                        #
# Copyright (c) 2002-2007 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:sources@bitrixsoft.com              #
##############################################
*/

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/controller/prolog.php");

$MOD_RIGHT = $APPLICATION->GetGroupRight("controller");
if($MOD_RIGHT<"W") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
IncludeModuleLangFile(__FILE__);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/controller/include.php");

$err_mess = "File: ".__FILE__."<br>Line: ";

$arThirdSettings = CControllerGroupSettings::Get3rdPartyOptions();

$subordinate_id = COperation::GetIDByName('edit_subordinate_users');
$arMainSubordinateTask = Array();
$db_task = CTask::GetList(Array("MODULE_ID" => "asc", "LETTER" => "asc"), Array("BINDING"=>'module'));
while($ar_task = $db_task->GetNext())
{
	if(!isset($arTasksModules[$ar_task['MODULE_ID']]))
		$arTasksModules[$ar_task['MODULE_ID']] = Array("reference"=>Array(), "reference_id"=>Array());

	$arTasksModules[$ar_task['MODULE_ID']]["reference"][] = '['.($ar_task['LETTER'] ? $ar_task['LETTER'] : '..').'] '.CTask::GetLangTitle($ar_task['NAME']);
	$arTasksModules[$ar_task['MODULE_ID']]["reference_id"][] = $ar_task['NAME'];
	if($ar_task['MODULE_ID']=='main')
	{
		$arOpInTask = CTask::GetOperations($ar_task['ID']);
		if(in_array($subordinate_id, $arOpInTask))
			$arMainSubordinateTask[] = $ar_task['NAME'];
	}
}

if($REQUEST_METHOD=="POST" && $COUNTER_UPDATE_PERIOD_TYPE!='' && (strlen($save)>0 || strlen($apply)>0) && $MOD_RIGHT>="W")
{
	if($COUNTER_UPDATE_PERIOD_TYPE == 'H')
		$COUNTER_UPDATE_PERIOD = $COUNTER_UPDATE_PERIOD * 60;
	elseif($COUNTER_UPDATE_PERIOD_TYPE == 'D')
		$COUNTER_UPDATE_PERIOD = $COUNTER_UPDATE_PERIOD * 60 * 24;
	elseif($COUNTER_UPDATE_PERIOD_TYPE == 'W')
		$COUNTER_UPDATE_PERIOD = $COUNTER_UPDATE_PERIOD * 60 * 24 * 7;
	elseif($COUNTER_UPDATE_PERIOD_TYPE == 'N')
		$COUNTER_UPDATE_PERIOD = $COUNTER_UPDATE_PERIOD * 60 * 24 * 30;
}


$strError="";
$ID = intval($ID);
$bVarsFromForm = false;
if($REQUEST_METHOD=="POST" && (strlen($save)>0 || strlen($apply)>0) && $MOD_RIGHT>="W" && check_bitrix_sessid())
{
	$arSettings = Array();
	$arOptions = $_REQUEST['OPTIONS'];
	$arSettings["default"] = Array();
	$arDefaultOptions = $arOptions['default'];

	if(isset($arDefaultOptions["modules"]))
	{
		if(!is_array($arDefaultOptions["modules"]))
			$arDefaultOptions["modules"] = Array($arDefaultOptions["modules"]);

		$arSettings["default"]["modules"] = Array();
		$arModules = CControllerGroupSettings::GetModules();
		foreach($arModules as $module_id=>$name)
		{
			if($module_id == "main")
				continue;
			if(in_array($module_id, $arDefaultOptions["modules"]))
				$arSettings["default"]["modules"][] = $module_id;
		}
	}

	$arSettings["default"]["options"] = Array();
	$arModuleOptions = CControllerGroupSettings::GetData();
	foreach($arModuleOptions as $id=>$info)
	{
		if(!is_array($info["options"]) || count($info["options"])<=0)
			continue;
		$arSettings["default"]["options"][$id] = Array();
		foreach($info["options"] as $option_id=>$option_ar)
			if(isset($arDefaultOptions[$option_id]))
				$arSettings["default"]["options"][$id][$option_id] = $arDefaultOptions[$option_id];
	}

	if(isset($arDefaultOptions["security"]))
	{
		if(!is_array($arDefaultOptions["security"]))
			$arDefaultOptions["security"] = Array($arDefaultOptions["security"]);

		$arSettings["default"]["security"] = Array();
		if($arDefaultOptions["security"]["limit_admin"] == "Y")
			$arSettings["default"]["security"]["limit_admin"] = "Y";
	}

	$arSettings["default"]["security"]["groups"] = Array();
	foreach($_REQUEST['SECURITY'] as $i=>$arSec)
	{
		if(strlen($arSec["GROUP"])>0)
		{
			$arSettings["default"]["security"]["groups"][$arSec["GROUP"]] = $arSec["RIGHTS"];
			if(isset($_REQUEST['SUB_SECURITY'][$i]) && in_array($arSec["RIGHTS"]['main'], $arMainSubordinateTask))
				$arSettings["default"]["security"]["subord_groups"][$arSec["GROUP"]] = $_REQUEST['SUB_SECURITY'][$i];
		}
	}

	foreach($arThirdSettings as $obOtherOption)
	{
		$arSettings[$obOtherOption->id] = Array();
		$arOtherOptions = $obOtherOption->GetOptionArray();
		foreach($arOtherOptions as $option_id=>$arOptionParams)
		{
			if(isset($arOptions[$obOtherOption->id][$option_id]))
				$arSettings[$obOtherOption->id][$option_id] = $arOptions[$obOtherOption->id][$option_id];
		}
	}

	$INSTALL_INFO = serialize($arSettings);
	$arFields = Array(
		"NAME"					=> $NAME,
		"DESCRIPTION"			=> $DESCRIPTION,
		"INSTALL_PHP" 			=> $INSTALL_PHP,
		"UPDATE_PERIOD"			=> $UPDATE_PERIOD,
		"TRIAL_PERIOD"			=> $TRIAL_PERIOD,
		"UNINSTALL_PHP"			=> $UNINSTALL_PHP,
		"INSTALL_INFO"			=> $INSTALL_INFO,
		"CHECK_COUNTER_FREE_SPACE" => $CHECK_COUNTER_FREE_SPACE,
		"CHECK_COUNTER_SITES" => $CHECK_COUNTER_SITES,
		"CHECK_COUNTER_USERS" => $CHECK_COUNTER_USERS,
		"CHECK_COUNTER_LAST_AUTH" => $CHECK_COUNTER_LAST_AUTH,
		"COUNTER_UPDATE_PERIOD" => $COUNTER_UPDATE_PERIOD,
		);

	if($ID>0)
	{
		$res = CControllerGroup::Update($ID, $arFields);
		if($_REQUEST["UPDATE_NOW"]=="Y")
			CControllerGroup::SetGroupSettings($ID);
	}
	else
	{
		$ID = CControllerGroup::Add($arFields);
		$res = ($ID>0);
	}

	if(!$res)
	{
		if($e = $APPLICATION->GetException())
			$message = new CAdminMessage(GetMessage("CTRLR_GR_ED_ER1"), $e);
		$bVarsFromForm = true;
	}
	else
	{
		CControllerCounter::SetGroupCounters($ID, $_POST["CONTROLLER_COUNTER_ID"]);

		$tabControl = new CAdminTabControl("tabControl", array());

		if(strlen($save)>0)
			LocalRedirect("controller_group_admin.php?lang=".LANG);
		else
			LocalRedirect($APPLICATION->GetCurPage()."?lang=".LANG."&ID=".$ID."&".$tabControl->ActiveTabParam());
	}
}

ClearVars();
$str_UPDATE_PERIOD = 0;
$str_COUNTER_UPDATE_PERIOD = 1440;

$mb = CControllerGroup::GetByID($ID);
if(($arGroupFields = $mb->ExtractFields("str_"))===false)
	$ID=0;

if ($message)
	$DB->InitTableVarsForEdit("b_controller_group", "", "str_");

$sDocTitle = ($ID>0) ? preg_replace("'#ID#'i", $ID, GetMessage("CTRLR_GR_ED_TITLE_1")) : GetMessage("CTRLR_GR_ED_TITLE_2");
$APPLICATION->SetTitle($sDocTitle);

/***************************************************************************
				HTML form
****************************************************************************/

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");
$aMenu = array(
	array(
		"ICON" => "btn_list",
		"TEXT"=>GetMessage("CTRLR_GR_ED_LINK_BACK"),
		"LINK"=>"controller_group_admin.php?lang=".LANG
	)
);

if($ID>0)
{
	$aMenu[] = array("SEPARATOR"=>"Y");
	$aMenu[] = array(
		"ICON" => "btn_new",
		"TEXT"=>GetMessage("CTRLR_GR_ED_LINK_NEW"),
		"LINK"=>"controller_group_edit.php?lang=".LANG
	);

	if ($MOD_RIGHT>="W")
	{
		$aMenu[] = array(
			"TEXT"=>GetMessage("CTRLR_GR_ED_LINK_DEL"),
			"ICON" => "btn_delete",
			"LINK"=>"javascript:if(confirm('".GetMessage("CTRLR_GR_ED_LINK_DEL_CONFIRM")."'))window.location='controller_group_admin.php?action=delete&ID=".$ID."&lang=".LANG."&".bitrix_sessid_get()."';",
		);
	}
}

$context = new CAdminContextMenu($aMenu);
$context->Show();

$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("CTRLR_GR_ED_TAB1"), "ICON"=>"controller_group_edit", "TITLE"=>$sDocTitle),
	array("DIV" => "edit2", "TAB" => GetMessage("CTRLR_GR_ED_TAB2"), "ICON"=>"controller_group_edit_2", "TITLE"=>GetMessage("CTRLR_GR_ED_TAB2_TITLE")),
	array("DIV" => "edit3", "TAB" => GetMessage("CTRLR_GR_ED_TAB3"), "ICON"=>"controller_group_edit_3", "TITLE"=>GetMessage("CTRLR_GR_ED_TAB3")),
	array("DIV" => "edit4", "TAB" => GetMessage("CTRLR_GR_ED_TAB4"), "ICON"=>"controller_group_edit_4", "TITLE"=>GetMessage("CTRLR_GR_ED_TAB4_TITLE")),
	array("DIV" => "edit5", "TAB" => GetMessage("CTRL_GR_ED_TAB5"), "ICON"=>"controller_group_edit_5", "TITLE"=>GetMessage("CTRL_GR_ED_TAB5_TITLE")),
);


$opt_cnt = 6;
foreach($arThirdSettings as $obOption)
{
	$aTabs[] = array("DIV" => "edit".$opt_cnt, "TAB" => $obOption->GetName(), "ICON"=>$obOption->GetIcon(), "TITLE"=>$obOption->GetTitle());
	$opt_cnt++;
}

$aTabs[] = array("DIV" => "edit0", "TAB" => GetMessage("CTRLR_GR_ED_TAB5"), "ICON"=>"controller_group_edit_0", "TITLE"=>GetMessage("CTRLR_GR_ED_TAB5_TITLE"));
$tabControl = new CAdminTabControl("tabControl", $aTabs);

if ($message)
	echo $message->Show();
?>
<form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?lang=<?=LANG?>&ID=<?=$ID?>" name="form1">
<?=bitrix_sessid_post()?>
<?echo GetFilterHiddens("find_");?>
<?$tabControl->Begin();?>
<?$tabControl->BeginNextTab();?>
	<?if($ID>0):?>
	<tr>
		<td>ID:</td>
		<td><?echo $str_ID?></td>
	</tr>
	<?endif?>
	<?if(strlen($str_DATE_CREATE)>0):?>
	<tr>
		<td><?echo GetMessage("CTRLR_GR_ED_CREATED")?></td>
		<td><?echo $str_DATE_CREATE?><?if($str_CREATED_BY_LOGIN<>'')echo ' ('.$str_CREATED_BY_LOGIN.') '.$str_CREATED_BY_NAME.' '.$str_CREATED_BY_LAST_NAME;?></td>
	</tr>
	<? endif; ?>
	<?if(strlen($str_TIMESTAMP_X)>0):?>
	<tr>
		<td><?echo GetMessage("CTRLR_GR_ED_MODIFIED")?></td>
		<td><?echo $str_TIMESTAMP_X?><?if($str_MODIFIED_BY_LOGIN<>'')echo ' ('.$str_MODIFIED_BY_LOGIN.') '.$str_MODIFIED_BY_NAME.' '.$str_MODIFIED_BY_LAST_NAME;?></td>
	</tr>
	<? endif; ?>
	<tr class="adm-detail-required-field">
		<td width="40%"><?echo GetMessage("CTRLR_GR_ED_NAME")?></td>
		<td width="60%"><input type="text" name="NAME" size="53" maxlength="255" value="<?=$str_NAME?>"></td>
	</tr>
	<tr>
		<td class="adm-detail-valign-top"><?echo GetMessage("CTRLR_GR_ED_DESC")?></td>
		<td><textarea name="DESCRIPTION" cols="40" rows="5"><?echo $str_DESCRIPTION?></textarea></td>
	</tr>
	<tr class="heading">
		<td colspan="2"><?echo GetMessage("CTRLR_GR_ED_UPD")?></td>
	</tr>
	<tr>
		<td><?echo GetMessage("CTRLR_GR_ED_AUTOUPD")?><br />
			<?echo GetMessage("CTRLR_GR_ED_AUTOUPD_HELP")?></td>
		<td><input type="text" name="UPDATE_PERIOD" size="6" maxlength="6" value="<?=($str_UPDATE_PERIOD<0 || trim($str_UPDATE_PERIOD)=='' ? '' : $str_UPDATE_PERIOD)?>"></td>
	</tr>
	<?if($ID>0):?>
	<tr>
		<td>
			<label for="UPDATE_NOW"><?echo GetMessage("CTRLR_GR_ED_UPD_NOW")?></label>
		</td>
		<td><input type="checkbox" name="UPDATE_NOW"  id="UPDATE_NOW" value="Y" title="<?echo GetMessage("CTRLR_GR_ED_TO_TASKS")?>">
	</td></tr>
	<?endif?>

<?$tabControl->BeginNextTab();?>
<?
if($bVarsFromForm)
{
	$arGroupOptions =  Array();
	if(is_array($_REQUEST['OPTIONS']))
	foreach($_REQUEST['OPTIONS'] as $ns=>$val)
	{
		if($ns=='default')
		{
			$arGroupOptions[$ns]["modules"] = $val["modules"];
			$arGroupOptions[$ns]["security"] = $val["security"];
			$arGroupOptions[$ns]["security"]["groups"] = Array();
			foreach($_REQUEST['SECURITY'] as $i=>$arSec)
			{
				if(strlen($arSec["GROUP"])>0)
				{
					$arGroupOptions[$ns]["security"]["groups"][$arSec["GROUP"]] = $arSec["RIGHTS"];
					if(isset($_REQUEST['SUB_SECURITY'][$i]))
						$arGroupOptions[$ns]["security"]["subord_groups"][$arSec["GROUP"]] = $_REQUEST['SUB_SECURITY'][$i];
				}
			}

			$arModuleOptions = CControllerGroupSettings::GetData();
			foreach($arModuleOptions as $module_id=>$info)
			{
				if(!is_array($info["options"]) || count($info["options"])<=0)
					continue;
				$arOptions = $info["options"];
				foreach($arOptions as $id=>$arOptionParams)
				{
					if(isset($val[$id]))
						$arGroupOptions[$ns]["options"][$module_id][$id] = $val[$id];
				}
			}
		}
		else
		{
			$arGroupOptions[$ns] = $val;
		}
	}
}
else
{
	$arGroupOptions = unserialize($arGroupFields["INSTALL_INFO"]);
}

$arDefGroupOptions = $arGroupOptions["default"];
//echo '<pre>';print_r($arGroupOptions);echo '</pre>';
//echo CControllerGroupSettings::GeneratePHPInstall($arGroupOptions);
?>
	<tr>
		<td width="40%" class="adm-detail-valign-top"><?echo GetMessage("CTRLR_GR_ED_INSTALLED")?></td>
		<td width="60%"><input type="checkbox" <?if(isset($arDefGroupOptions["modules"]))echo "checked";?> name="OPTIONS[default][modules]" id="ACT_modules" value="Y" onclick="document.getElementById('modules').disabled=!this.checked;" title="<?echo GetMessage("CTRLR_GR_ED_CHCKBOX")?>"><br>
			<?$arModules = CControllerGroupSettings::GetModules();?>
			<select name="OPTIONS[default][modules][]" size="24" multiple="Y" id="modules"<?if(!isset($arDefGroupOptions["modules"]))echo" disabled";?>>
			<?foreach($arModules as $module_id=>$name):
				if($module_id == "main")
					continue;
				?>
				<option value="<?=htmlspecialcharsbx($module_id)?>"<?if(is_array($arDefGroupOptions["modules"]) && in_array($module_id, $arDefGroupOptions["modules"]))echo ' selected';?>><?=htmlspecialcharsbx($name)?></option>
			<?endforeach;?>
			</select>
		</td>
	</tr>


<?$tabControl->BeginNextTab();?>
<?
$arModuleOptions = CControllerGroupSettings::GetData();
foreach($arModuleOptions as $module_id=>$info):
	if(!is_array($info["options"]) || count($info["options"])<=0)
		continue;
	?>
	<tr class="heading">
		<td colspan="2"><?=htmlspecialcharsex($info["name"])?></td>
	</tr>
	<?
	$arOptions = $info["options"];
	foreach($arOptions as $id=>$arOptionParams):

		if(substr($id, 0, 2)=="__"):
			?>
			<tr>
				<td colspan="2" align="center"><?=htmlspecialcharsex($arOptionParams)?>:</td>
			</tr>
			<?
		else:
			if(isset($arDefGroupOptions["options"][$module_id][$id]))
				$OptionValue = $arDefGroupOptions["options"][$module_id][$id];
			else
				$OptionValue = false;
			?>
			<tr>
				<td width="40%"><label for="ACT_<?=htmlspecialcharsbx($id)?>"><?=htmlspecialcharsex($arOptionParams[0])?>:</label></td>
				<td width="60%"><?=CControllerGroupSettings::GenerateInput($id, $arOptionParams, $OptionValue)?></td>
			</tr>
		<?endif;?>
	<?endforeach;?>
<?endforeach;?>

<?
$tabControl->BeginNextTab();

$security = $arDefGroupOptions["security"];

$arModuleRights = Array();
$arModules = CControllerGroupSettings::GetModules();
foreach($arModules as $module_id=>$name)
{
	if($module_id!='main')
	{
		if(!file_exists($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/".$module_id."/install/index.php"))
			continue;

		include_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/".$module_id."/install/index.php");
		if(!class_exists($module_id))
			continue;

		$module = new $module_id;
		if($module->MODULE_GROUP_RIGHTS!="Y")
			continue;
	}

	if(isset($arTasksModules[$module_id]))
		$arModuleRights[$module_id] = $arTasksModules[$module_id];
	else
	{
		if (method_exists($module, "GetModuleRightList"))
			$arModuleRights[$module_id] = call_user_func(array($module_id, "GetModuleRightList"));
		else
			$arModuleRights[$module_id] = $APPLICATION->GetDefaultRightList();
	}
}

// prepare portion of html to use in js
ob_start();
?>
		<br>
		<table class="internal">
		<tr><td colspan="2"><?echo GetMessage("CTRLR_GR_ED_GROUP")?>: <input type="text" name="SECURITY[#I#][GROUP]" size="15" value="#GROUP_NAME#"></td></tr>
		<tr>
			<td align="center"><?echo GetMessage("CTRLR_GR_ED_MODULE")?></td><td align="center"><?echo GetMessage("CTRLR_GR_ED_LEVEL")?></td>
		</tr>
		<?
		foreach($arModuleRights as $module_id=>$permissions):
			$fieldname = 'SECURITY[#I#][RIGHTS]['.htmlspecialcharsbx($module_id).']';
		?>
		<tr>
			<td><label for="ACT_<?=$fieldname?>"><?=htmlspecialcharsbx($arModules[$module_id])?>: </label></td>
				<td nowrap>
					<input type="checkbox" name="<?=$fieldname?>" id="ACT_<?=$fieldname?>" value="Y" title="<?echo GetMessage("CTRLR_GR_ED_CHCKBOX")?>"
						onclick="document.getElementById('E_<?=$fieldname?>').disabled=!this.checked;if(this.checked)document.getElementById('E_<?=$fieldname?>').focus();"
					/>&nbsp;
					<select name="<?=$fieldname?>" <?if($module_id=='main'):?>onchange="document.getElementById('SUB_<?=$fieldname?>').style.display = (!__CheckTaskSubord(this.value)?'none':'inline');" <?endif?> id="E_<?=$fieldname?>" disabled>
						<option value=""><?=GetMessage("CTRLR_GR_ED_LEVEL_DEF")?></option>
						<?foreach($permissions["reference"] as $j => $reference):?>
							<option value="<?=$permissions["reference_id"][$j]?>"><?=htmlspecialcharsex($reference)?></option>
						<?endforeach?>
					</select>
					<?if($module_id=='main'):?>
						<br><textarea name="SUB_SECURITY[#I#]" title="<?echo GetMessage("CTRLR_GR_ED_SUBORD")?>" rows="3" cols="40" id="SUB_<?=$fieldname?>" style="display:none;"></textarea>
					<?endif?>
				</td>
			</tr>
		<?endforeach;?>
		</table>
	<?
	$content = ob_get_contents();
	ob_end_clean();
?>
<script>
function __CheckNewAdmin()
{
	var i, el, new_i = document.getElementById('SECURITY_COUNT').value;
	for(i=0; i<=new_i; i++)
	{
		el = document.getElementById('SECURITY['+i+'][GROUP]');
		if(el && el.value.toLowerCase() == 'administrators')
			return true;
	}
	return false;
}

function __NewSecGroup(group_name, to_begin)
{
	var link_from = document.getElementById('security_add_link');
	if(to_begin)
		link_from = document.getElementById('begin_p');
	var oDiv = document.createElement('DIV');
	link_from.parentNode.insertBefore(oDiv, link_from);

	var new_i = document.getElementById('SECURITY_COUNT').value;
	new_i++;
	document.getElementById('SECURITY_COUNT').value = new_i;
	var html = '<?=CUtil::JSEscape($content)?>';
	html = html.replace(new RegExp("#I#", "g"), new_i);
	html = html.replace(new RegExp("#GROUP_NAME#", "g"), group_name);
	oDiv.innerHTML = html;

	setTimeout(function() {
		var r = BX.findChildren(oDiv, {tag: /^(input|select|textarea)$/i}, true);
		if (r && r.length > 0)
		{
			for (var i=0,l=r.length;i<l;i++)
			{
				if (r[i].form && r[i].form.BXAUTOSAVE)
					r[i].form.BXAUTOSAVE.RegisterInput(r[i]);
				else
					break;
			}
		}
	}, 10);
}

function __CheckTaskSubord(task_id)
{
	var tasks = <?=CUtil::PhpToJSObject($arMainSubordinateTask)?>;
	var i;
	for(i=0; i<tasks.length; i++)
		if(task_id == tasks[i])
			return true;
	return false;
}

BX.ready(function(){
	BX.addCustomEvent(document.forms.form1, 'onAutoSaveRestore', function(ob, data) {
		while (data['SECURITY[' + BX('SECURITY_COUNT').value + '][GROUP]'])
			__NewSecGroup('');
	})
});
</script>
	<tr>
		<td width="40%"><?echo GetMessage("CTRLR_GR_ED_NOADMIN")?></td>
		<td width="60%"><input onclick="if(this.checked && !__CheckNewAdmin())__NewSecGroup('Administrators', true);" type="checkbox" name="OPTIONS[default][security][limit_admin]" value="Y"<?if($security["limit_admin"] == "Y")echo ' checked';?>></td>
	</tr>
	<tr>
		<td class="adm-detail-valign-top"><?echo GetMessage("CTRLR_GR_ED_PERM")?></td>
		<td>
			<?
			$arSecurityGroups = $arDefGroupOptions["security"]["groups"];
			$arSecuritySubordGroups = $arDefGroupOptions["security"]["subord_groups"];
			$arSecurityGroups["__Group_ID__"] = Array();

			$i=-1;
			foreach($arSecurityGroups as $group_id=>$arSecOptions):
				$i++;
			?>
			<input type="hidden" id="begin_p">
			<table class="internal">
				<tr><td colspan="2"><?echo GetMessage("CTRLR_GR_ED_GROUP")?>: <input type="text" name="SECURITY[<?=$i?>][GROUP]" size="15" value="<?=($group_id=='__Group_ID__'?'':htmlspecialcharsbx($group_id))?>"></td></tr>
				<tr><td align="center"><?echo GetMessage("CTRLR_GR_ED_MODULE")?></td><td align="center"><?echo GetMessage("CTRLR_GR_ED_LEVEL")?></td></tr>
				<?
				foreach($arModuleRights as $module_id=>$permissions):
					$fieldname = 'SECURITY['.$i.'][RIGHTS]['.htmlspecialcharsbx($module_id).']';
					?>
					<tr>
						<td><label for="ACT_<?=$fieldname?>"><?=htmlspecialcharsbx($arModules[$module_id])?>: </label></td>
						<td nowrap>
							<input type="checkbox" name="<?=$fieldname?>" id="ACT_<?=$fieldname?>" value="Y" title="<?echo GetMessage("CTRLR_GR_ED_CHCKBOX")?>"
								onclick="document.getElementById('E_<?=$fieldname?>').disabled=!this.checked;if(this.checked)document.getElementById('E_<?=$fieldname?>').focus();"
								<?if(isset($arSecOptions[$module_id]))echo "checked";?>
							/>
							<select name="<?=$fieldname?>" <?if($module_id=='main'):?>onchange="document.getElementById('SUB_<?=$fieldname?>').style.display = (!__CheckTaskSubord(this.value)?'none':'inline');" <?endif?>id="E_<?=$fieldname?>" <?if(!isset($arSecOptions[$module_id]))echo "disabled";?>>
								<option value=""><?=GetMessage("CTRLR_GR_ED_LEVEL_DEF")?></option>
								<?foreach($permissions["reference_id"] as $j => $reference_id):?>
									<option value="<?=$reference_id?>" <?
										if($arSecOptions[$module_id]==$reference_id)
											echo "selected";?>><?=htmlspecialcharsex($permissions["reference"][$j])?></option>
								<?endforeach?>
							</select>
							<?if($module_id=='main'):?>
								<br><textarea name="SUB_SECURITY[<?=$i?>]" title="<?echo GetMessage("CTRLR_GR_ED_SUBORD")?>" rows="3" cols="40" id="SUB_<?=$fieldname?>"<?if(!in_array($arSecOptions[$module_id], $arMainSubordinateTask)):?> style="display:none;"<?endif?>><?=htmlspecialcharsex($arSecuritySubordGroups[$group_id])?></textarea>
							<?endif?>
						</td>
					</tr>
				<?endforeach;?>
			</table>
			<?endforeach;?>
			<a href="javascript:__NewSecGroup('')" id="security_add_link"><?echo GetMessage("CTRLR_GR_ED_GROUP_MORE")?></a>
			<input type="hidden" name="SECURITY_COUNT" id="SECURITY_COUNT" value="<?=$i?>">
		</td>
	</tr>
<?$tabControl->BeginNextTab();?>
<?
if($str_COUNTER_UPDATE_PERIOD % (60*24*30) == 0)
{
	$COUNTER_UPDATE_PERIOD_TYPE = "N";
	$str_COUNTER_UPDATE_PERIOD = $str_COUNTER_UPDATE_PERIOD / (60*24*30);
}
elseif($str_COUNTER_UPDATE_PERIOD % (60*24*7) == 0)
{
	$COUNTER_UPDATE_PERIOD_TYPE = "W";
	$str_COUNTER_UPDATE_PERIOD = $str_COUNTER_UPDATE_PERIOD / (60*24*7);
}
elseif($str_COUNTER_UPDATE_PERIOD % (60*24) == 0)
{
	$COUNTER_UPDATE_PERIOD_TYPE = "D";
	$str_COUNTER_UPDATE_PERIOD = $str_COUNTER_UPDATE_PERIOD / (60*24);
}
elseif($str_COUNTER_UPDATE_PERIOD % 60 == 0)
{
	$COUNTER_UPDATE_PERIOD_TYPE = "H";
	$str_COUNTER_UPDATE_PERIOD = $str_COUNTER_UPDATE_PERIOD / (60);
}
else
	$COUNTER_UPDATE_PERIOD_TYPE = "M";
?>
	<tr>
		<td width="40%"><?echo GetMessage("CTRL_GR_ED_COUNTERS_REF")?> </td>
		<td width="60%"><input type="text" name="COUNTER_UPDATE_PERIOD" size="5" value="<?echo $str_COUNTER_UPDATE_PERIOD?>">
		<select name="COUNTER_UPDATE_PERIOD_TYPE">
			<option value="M"<?if($COUNTER_UPDATE_PERIOD_TYPE=="M")echo ' selected'?>><?echo GetMessage("CTRL_GR_ED_COUNTERS_REF_MI")?></option>
			<option value="H"<?if($COUNTER_UPDATE_PERIOD_TYPE=="H")echo ' selected'?>><?echo GetMessage("CTRL_GR_ED_COUNTERS_REF_HO")?></option>
			<option value="D"<?if($COUNTER_UPDATE_PERIOD_TYPE=="D")echo ' selected'?>><?echo GetMessage("CTRL_GR_ED_COUNTERS_REF_DA")?></option>
			<option value="W"<?if($COUNTER_UPDATE_PERIOD_TYPE=="W")echo ' selected'?>><?echo GetMessage("CTRL_GR_ED_COUNTERS_REF_WE")?></option>
			<option value="N"<?if($COUNTER_UPDATE_PERIOD_TYPE=="N")echo ' selected'?>><?echo GetMessage("CTRL_GR_ED_COUNTERS_REF_MO")?></option>
		</select>
		</td>
	</tr>

	<tr class="heading">
		<td colspan="2"><?echo GetMessage("CTRL_GR_ED_COUNTERS_TITLE")?></td>
	</tr>

	<tr>
		<td><label for="COUNTER_FREE_SPACE"><?echo GetMessage("CTRL_GR_ED_COUNTERS_FREE")?></label></td>
		<td>
			<input type="checkbox" id="COUNTER_FREE_SPACE" name="CHECK_COUNTER_FREE_SPACE" value="Y"<?if($str_CHECK_COUNTER_FREE_SPACE=="Y")echo ' checked'?>>
		</td>
	</tr>

	<tr>
		<td><label for="COUNTER_SITES"><?echo GetMessage("CTRL_GR_ED_COUNTERS_SITES")?></label></td>
		<td>
			<input type="checkbox" id="COUNTER_SITES" name="CHECK_COUNTER_SITES" value="Y"<?if($str_CHECK_COUNTER_SITES=="Y")echo ' checked'?>>
		</td>
	</tr>

	<tr>
		<td><label for="COUNTER_USERS"><?echo GetMessage("CTRL_GR_ED_COUNTERS_USERS")?></label></td>
		<td>
			<input type="checkbox" id="COUNTER_USERS" name="CHECK_COUNTER_USERS" value="Y"<?if($str_CHECK_COUNTER_USERS=="Y")echo ' checked'?>>
		</td>
	</tr>

	<tr>
		<td><label for="COUNTER_LAST_AUTH"><?echo GetMessage("CTRL_GR_ED_COUNTERS_LAST_AU")?></label></td>
		<td>
			<input type="checkbox" id="COUNTER_LAST_AUTH" name="CHECK_COUNTER_LAST_AUTH" value="Y"<?if($str_CHECK_COUNTER_LAST_AUTH=="Y")echo ' checked'?>>
		</td>
	</tr>
	<?
	$arGroupCounters = array();
	$rsCounters = CControllerCounter::GetList(array(), array("CONTROLLER_GROUP_ID" => $ID));
	while($arCounter = $rsCounters->Fetch())
		$arGroupCounters[$arCounter["ID"]] = $arCounter["ID"];

	$rsCounters = CControllerCounter::GetList(array("NAME" => "ASC"), array());
	while($arCounter = $rsCounters->Fetch())
	{?>
	<tr>
		<td><label for="COUNTER_<?echo $arCounter["ID"]?>"><?echo htmlspecialcharsex($arCounter["NAME"])?>:</label></td>
		<td>
			<input type="checkbox" id="COUNTER_<?echo $arCounter["ID"]?>" name="CONTROLLER_COUNTER_ID[]" value="<?echo $arCounter["ID"]?>"<?if(array_key_exists($arCounter["ID"], $arGroupCounters))echo ' checked'?>>
		</td>
	</tr>
	<?}
	?>

<?
foreach($arThirdSettings as $obOption):

	$tabControl->BeginNextTab();

	$arOptions = $obOption->GetOptionArray();
	foreach($arOptions as $id=>$arOptionParams):

		if(substr($id, 0, 2)=="__"):
			?>
			<tr>
				<td colspan="2" align="center"><?=htmlspecialcharsex($arOptionParams)?>:</td>
			</tr>
			<?
		else:
			$OptionValue = $arGroupOptions[$obOption->id][$id];
			?>
			<tr>
				<td width="40%"><label for="ACT_<?=htmlspecialcharsbx($id)?>"><?=htmlspecialcharsex($arOptionParams[0])?>:</label></td>
				<td width="60%"><?=CControllerGroupSettings::GenerateInput($id, $arOptionParams, $OptionValue, $obOption->id)?></td>
			</tr>
		<?endif;?>
	<?endforeach;?>
<?endforeach;?>

<?$tabControl->BeginNextTab();?>
	<tr>
		<td width="40%" class="adm-detail-valign-top"><?echo GetMessage("CTRLR_GR_ED_PHP_INST")?></td>
		<td width="60%"><textarea style="width:100%" name="INSTALL_PHP" cols="40" rows="5"><?echo $str_INSTALL_PHP?></textarea>
		</td>
	</tr>

	<tr>
		<td class="adm-detail-valign-top"><?echo GetMessage("CTRLR_GR_ED_PHP_UNINST")?></td>
		<td><textarea style="width:100%" name="UNINSTALL_PHP" cols="40" rows="5"><?echo $str_UNINSTALL_PHP?></textarea>
		</td>
	</tr>
<?$tabControl->EndTab();?>

<?$tabControl->Buttons(Array("disabled"=>$MOD_RIGHT<"W","back_url" =>"controller_group_admin.php?lang=".LANG));?>
<?$tabControl->End();?>
<input type="hidden" value="Y" name="apply">
</form>
<?$tabControl->ShowWarnings("form1", $message);?>

<?require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");?>
