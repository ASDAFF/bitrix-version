<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

$MOD_RIGHT = $APPLICATION->GetGroupRight("controller");
if($MOD_RIGHT < "W" || !CModule::IncludeModule("controller"))
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

IncludeModuleLangFile(__FILE__);

$message = null;
$ID = intval($ID);

if(
	$_SERVER["REQUEST_METHOD"] == "POST"
	&& check_bitrix_sessid()
	&& (
		isset($_POST["save"])
		|| isset($_POST["apply"])
		|| isset($_POST["delete"])
	)
)
{
	if(isset($_POST["delete"]) && $_POST["delete"]==="y")
	{
		CControllerCounter::Delete($ID);

		if($back_url == '')
			LocalRedirect("controller_counter_admin.php?lang=".LANGUAGE_ID);
		else
			LocalRedirect($back_url);

	}
	else
	{
		$arFields = array(
			"COUNTER_TYPE" => $_POST["COUNTER_TYPE"],
			"COUNTER_FORMAT" => $_POST["COUNTER_FORMAT"],
			"NAME" => $_POST["NAME"],
			"COMMAND" => $_POST["COMMAND"],
			"CONTROLLER_GROUP_ID" => $_POST["CONTROLLER_GROUP_ID"],
		);

		$obCounter = new CControllerCounter;

		if($ID > 0)
			$res = $obCounter->Update($ID, $arFields);
		else
			$res = $ID = $obCounter->Add($arFields);

		if(!$res)
		{
			if($e = $APPLICATION->GetException())
				$message = new CAdminMessage(GetMessage("CTRL_COUNTER_EDIT_ERROR"), $e);
		}
		else
		{
			if(isset($_POST["save"]))
			{
				if($back_url == '')
					LocalRedirect("controller_counter_admin.php?lang=".LANGUAGE_ID);
				else
					LocalRedirect($back_url);
			}
			else
			{
				LocalRedirect("controller_counter_edit.php?lang=".LANGUAGE_ID."&ID=".$ID);
			}
		}
	}
}

$arCounter = CControllerCounter::GetArrayByID($ID);
if(!is_array($arCounter))
	$ID = 0;

if($message !== null)
{
	$arCounter = array(
		"COUNTER_TYPE" => $_POST["COUNTER_TYPE"],
		"COUNTER_FORMAT" => $_POST["COUNTER_FORMAT"],
		"NAME" => $_POST["NAME"],
		"COMMAND" => $_POST["COMMAND"],
		"CONTROLLER_GROUP_ID" => is_array($_POST["CONTROLLER_GROUP_ID"])? $_POST["CONTROLLER_GROUP_ID"]: array(),
	);
}
elseif($ID <= 0)
{
	$arCounter = array(
		"COUNTER_TYPE" => "F",
		"COUNTER_FORMAT" => "",
		"NAME" => "",
		"COMMAND" => "",
		"CONTROLLER_GROUP_ID" => array(),
	);
}

$sDocTitle = $ID > 0? GetMessage("CTRL_CNT_EDIT_TITLE", array("#ID#" => $ID)) : GetMessage("CTRL_CNT_EDIT_TITLE_NEW");
$APPLICATION->SetTitle($sDocTitle);

/***************************************************************************
				HTML form
****************************************************************************/

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");
$aMenu = array(
	array(
		"ICON" => "btn_list",
		"TEXT" => GetMessage("CTRL_COUNTER_EDIT_TOOLBAR_LIST"),
		"LINK" => "controller_counter_admin.php?lang=".LANGUAGE_ID
	)
);

if($ID > 0)
{
	$aMenu[] = array("SEPARATOR"=>"Y");

	$aMenu[] = array(
		"ICON" => "btn_new",
		"TEXT" => GetMessage("CTRL_COUNTER_EDIT_TOOLBAR_NEW"),
		"LINK" => "controller_counter_edit.php?lang=".LANGUAGE_ID
	);

	$aMenu[] = array(
		"TEXT"=>GetMessage("CTRL_COUNTER_EDIT_TOOLBAR_DELETE"),
		"ICON" => "btn_delete",
		"LINK" => "javascript:jsDelete('form1', '".GetMessage("CTRL_COUNTER_EDIT_TOOLBAR_DELETE_CONFIRM")."')",
	);
}

$context = new CAdminContextMenu($aMenu);
$context->Show();

$aTabs = array(
	array(
		"DIV" => "edit1",
		"TAB" => GetMessage("CTRL_COUNTER_EDIT_TAB1"),
		"TITLE" => GetMessage("CTRL_COUNTER_EDIT_TAB1_TITLE"),
	),
);

$tabControl = new CAdminTabControl("tabControl", $aTabs);

if ($message)
	echo $message->Show();

?>
<script>
function jsDelete(form_id, message)
{
	var _form = BX(form_id);
	var _flag = BX('delete');
	if(_form && _flag)
	{
		if(confirm(message))
		{
			_flag.value = 'y';
			_form.submit();
		}
	}
}
</script>

<form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?lang=<?=LANGUAGE_ID?>&amp;ID=<?=$ID?>" name="form1" id="form1">
<?$tabControl->Begin();?>
<?$tabControl->BeginNextTab();?>
	<tr class="adm-detail-required-field">
		<td width="40%"><?echo GetMessage("CTRL_COUNTER_EDIT_NAME")?>:</td>
		<td width="60%"><input type="text" name="NAME" size="53" maxlength="255" value="<?echo htmlspecialcharsbx($arCounter["NAME"])?>"></td>
	</tr>
	<tr class="adm-detail-required-field">
		<td><?echo GetMessage("CTRL_COUNTER_EDIT_COUNTER_TYPE")?>:</td>
		<td><select name="COUNTER_TYPE">
			<?foreach(CControllerCounter::GetTypeArray() as $key => $value):?>
				<option value="<?echo htmlspecialcharsbx($key)?>"<?if($arCounter["COUNTER_TYPE"] == $key) echo " selected"?>><?echo htmlspecialcharsex($value)?></option>
			<?endforeach;?>
			</select>
		</td>
	</tr>
	<tr>
		<td><?echo GetMessage("CTRL_COUNTER_EDIT_COUNTER_FORMAT")?>:</td>
		<td><select name="COUNTER_FORMAT">
			<?foreach(CControllerCounter::GetFormatArray() as $key => $value):?>
				<option value="<?echo htmlspecialcharsbx($key)?>"<?if($arCounter["COUNTER_FORMAT"] == $key) echo " selected"?>><?echo htmlspecialcharsex($value)?></option>
			<?endforeach;?>
			</select>
		</td>
	</tr>
	<tr valign="top">
		<td class="adm-detail-valign-top"><?echo GetMessage("CTRL_COUNTER_EDIT_CONTROLLER_GROUP")?>:</td>
		<td>
			<div class="adm-list">
			<?
			$dbr_group = CControllerGroup::GetList(Array("SORT"=>"ASC"));
			while($ar_group = $dbr_group->GetNext()):
			?>
				<div class="adm-list-item"><div class="adm-list-control"><input type="checkbox" name="CONTROLLER_GROUP_ID[]" id="CONTROLLER_GROUP_ID_<?echo htmlspecialcharsbx($ar_group["ID"])?>" value="<?echo htmlspecialcharsbx($ar_group["ID"])?>"<?if(in_array($ar_group["ID"], $arCounter["CONTROLLER_GROUP_ID"])) echo " checked"?>></div><div class="adm-list-label"><label for="CONTROLLER_GROUP_ID_<?echo htmlspecialcharsbx($ar_group["ID"])?>"><?echo htmlspecialcharsex($ar_group["NAME"])?></label></div></div>
			<?endwhile;?>
			</div>
		</td>
	</tr>
	<tr valign="top" class="adm-detail-required-field">
		<td class="adm-detail-valign-top"><?echo GetMessage("CTRL_COUNTER_EDIT_COMMAND")?>:</td>
		<td><textarea name="COMMAND" style="width:100%" rows="20"><?echo htmlspecialcharsbx($arCounter["COMMAND"])?></textarea>
		</td>
	</tr>
<?$tabControl->EndTab();?>
<?$tabControl->Buttons(array(
	"back_url" => $back_url? $back_url: "controller_counter_admin.php?lang=".LANGUAGE_ID,
));?>
<?$tabControl->End();?>
<?echo bitrix_sessid_post();?>
<input type="hidden" name="lang" value="<?echo LANGUAGE_ID?>">
<?if($ID > 0):?>
	<input type="hidden" name="ID" value="<?=$ID?>">
	<input type="hidden" name="delete" id="delete" value="">
<?endif;?>
<?if($back_url!=''):?>
	<input type="hidden" name="back_url" value="<?echo htmlspecialcharsbx($back_url)?>">
<?endif?>
<input type="hidden" value="Y" name="apply">
</form>

<?require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");?>
