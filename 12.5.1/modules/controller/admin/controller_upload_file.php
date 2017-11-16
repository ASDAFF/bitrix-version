<?
require_once(dirname(__FILE__)."/../../main/include/prolog_admin_before.php");
require_once ($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/filter_tools.php");

CModule::IncludeModule("controller");
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/controller/prolog.php");

$MOD_RIGHT = $APPLICATION->GetGroupRight("controller");
if($MOD_RIGHT<"V") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

IncludeModuleLangFile(__FILE__);
$sTableID = "tbl_controller_upload";
$lAdmin = new CAdminList($sTableID);

$filename = Rel2Abs("/", trim($_REQUEST['filename']));
//Trailing slash indicates that we have a directory here
//never remove it due to security reasons
$path_to = Rel2Abs("/", trim($_REQUEST['path_to'])."/");

if(
	strlen($filename) > 0
	&& strlen($path_to) > 0
	&& $USER->IsAdmin()
	&& check_bitrix_sessid()
)
{
	$lAdmin->BeginPrologContent();
	$arFilter = Array(
		"CONTROLLER_GROUP_ID" => $_REQUEST['controller_group_id'],
		"DISCONNECTED" => "N",
	);

	$arFilter["ID"] = $_REQUEST['controller_member_id'];
	if(!is_array($arFilter["ID"]))
	{
		$IDs = explode(" ", $arFilter["ID"]);
		$arFilter["ID"] = Array();

		foreach($IDs as $id)
		{
			$id = intval(trim($id));
			if($id>0)
				$arFilter["ID"][] = $id;
		}

	}
	if(is_array($arFilter["ID"]) && count($arFilter["ID"])<=0)
		unset($arFilter["ID"]);

	$sendfile = false;
	if(file_exists($_SERVER['DOCUMENT_ROOT'].$filename))
	{
		$sendfile = CControllerTools::PackFileArchive($_SERVER['DOCUMENT_ROOT'].$filename);
		if($sendfile !== false)
		{
			$arParams = array(
				'FILE' => $sendfile,
				'PATH_TO' => $path_to,
			);

			$cnt = 0;
			$dbr_members = CControllerMember::GetList(Array("ID"=>"ASC"), $arFilter);
			while($ar_member = $dbr_members->GetNext())
			{
				$cnt++;
				echo BeginNote();
				echo "<b><u>".$ar_member["NAME"].":</u></b><br>";
				$result = CControllerMember::RunCommandWithLog($ar_member["ID"], " ", $arParams, false, 'sendfile');
				if($result===false)
				{
					$e = $APPLICATION->GetException();
					echo "Error: ".$e->GetString();
				}
				else
					echo nl2br($result);
				echo EndNote();
			}

			if($cnt<=0)
			{
				echo BeginNote();
				echo GetMessage("CTRLR_UPLOAD_ERR_NSELECTED");
				echo EndNote();
			}
		}
		else
		{
			ShowError(GetMessage("CTRLR_UPLOAD_ERR_PACK"));
		}
	}
	else
	{
		ShowError(GetMessage("CTRLR_UPLOAD_ERR_FILE"));
	}

	$lAdmin->EndPrologContent();
}

$lAdmin->BeginEpilogContent();
?>
	<input type="hidden" name="controller_member_id" id="controller_member_id" value="<?=htmlspecialcharsbx($controller_member_id)?>">
	<input type="hidden" name="controller_group_id" id="controller_group_id" value="<?=htmlspecialcharsbx($controller_group_id)?>">
	<input type="hidden" name="filename" id="filename" value="<?=htmlspecialcharsbx($filename);?>">
	<input type="hidden" name="path_to" id="path_to" value="<?=htmlspecialcharsbx($path_to);?>">
<?
$lAdmin->EndEpilogContent();

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("CTRLR_UPLOAD_TITLE"));

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");
?>
<script>
function __FPHPSubmit()
{
	if(confirm('<?echo GetMessage("CTRLR_UPLOAD_CONFIRM")?>'))
	{
		document.getElementById('controller_member_id').value = document.getElementById('fcontroller_member_id').value;
		document.getElementById('controller_group_id').value = document.getElementById('fcontroller_group_id').value;

		var filename = document.getElementById('ffilename').value;
		var path_to = document.getElementById('fpath_to').value;

		if(filename.length > 0 && path_to.length <= 0)
		{
			alert('<?=CUtil::addslashes(GetMessage('CTRLR_UPLOAD_ERROR_NO_PATH_TO'))?>');
			document.getElementById('fpath_to').focus();
			return;
		}

		document.getElementById('filename').value = filename;
		document.getElementById('path_to').value = path_to;

		<?=$lAdmin->ActionPost($APPLICATION->GetCurPageParam("mode=frame", Array("mode", "PAGEN_1")))?>
	}
}
</script>
<?
$aTabs = array(
	array("DIV"=>"tab1", "TAB"=>GetMessage("CTRLR_UPLOAD_FILE_TAB"), "TITLE"=>GetMessage("CTRLR_UPLOAD_FILE_TAB_TITLE")),
);
$editTab = new CAdminTabControl("editTab", $aTabs, true, true);
?>
<form name="form1" action="<?echo $APPLICATION->GetCurPage()?>" method="POST">
<input type="hidden" name="lang" value="<?=LANG?>">
<?
$arGroups = Array();
$dbr_groups = CControllerGroup::GetList(Array("SORT"=>"ASC"));
while($ar_groups = $dbr_groups->GetNext())
	$arGroups[$ar_groups["ID"]] = $ar_groups["NAME"];


$filter = new CAdminFilter(
	$sTableID."_filter_id",
	Array(GetMessage("CTRLR_UPLOAD_FILTER_GROUP"))
);

$filter->Begin();
?>
<tr>
	<td nowrap><?=GetMessage("CTRLR_UPLOAD_FILTER_SITE")?>:</td>
	<td nowrap>
		<?
		$dbr_members = CControllerMember::GetList(Array("SORT"=>"ASC"), Array("DISCONNECTED"=>"N"));
		if($dbr_members->SelectedRowsCount()<=50):?>
			<select name="fcontroller_member_id" id="fcontroller_member_id">
				<option value=""><?echo GetMessage("CTRLR_UPLOAD_FILTER_SITE_ALL")?></option>
				<?
				while($ar_member = $dbr_members->GetNext()):
				?>
					<option value="<?=$ar_member["ID"]?>"<?if($controller_member_id==$ar_member["ID"])echo ' selected';?>><?=$ar_member["NAME"]?> [<?=$ar_member["ID"]?>]</option>
				<?endwhile?>
			</select>
		<?else:?>
			<input type="text" name="fcontroller_member_id" value="<?echo htmlspecialcharsbx($controller_member_id)?>" size="47">
		<?endif?>
	</td>
</tr>
<tr>
	<td nowrap><?echo htmlspecialcharsEx(GetMessage("CTRLR_UPLOAD_FILTER_GROUP"))?>:</td>
	<td nowrap><?echo htmlspecialcharsEx($controller_group_id)?>
	<select name="fcontroller_group_id" id="fcontroller_group_id">
		<option value=""><?echo GetMessage("CTRLR_UPLOAD_FILTER_GROUP_ANY")?></option>
	<?foreach($arGroups as $group_id=>$group_name):?>
		<option value="<?=$group_id?>" <?if($group_id==$controller_group_id)echo "selected"?>><?=$group_name?></option>
	<?endforeach;?>
	</select>
	</td>
</tr>
<?
$filter->Buttons();
?>
<?
$filter->End();
?>


<?=bitrix_sessid_post()?>
<?
$editTab->Begin();
$editTab->BeginNextTab();
?>
<tr>
	<td width="40%">
		<?=GetMessage('CTRLR_UPLOAD_SEND_FILE_FROM')?>
	</td>
	<td width="60%">
		<input type="text" id="ffilename" name="ffilename" value="">
		<script>
		function setFile(filename, path, site)
		{
			if(filename == path)
			{
				document.getElementById('ffilename').value = filename;
			}
			else
			{
				if(path != '/')
					path += '/';
				document.getElementById('ffilename').value = path + filename;
			}
		}
		</script><?
		CAdminFileDialog::ShowScript(
			Array
			(
				"event" => "OpenFileBrowserWindFile",
				"arResultDest" => Array("FUNCTION_NAME" => 'setFile'),
				//"arPath" => Array("SITE" => "ru", 'PATH' => "/"),
				"select" => 'DF',// F - file only, D - folder only, DF - files & dirs
				"operation" => 'O',// O - open, S - save
				"showUploadTab" => true,
				"showAddToMenuTab" => true,
				//"fileFilter" => '',
				"allowAllFiles" => true,
				"SaveConfig" => true
			)
		);
		?><input type="button" onclick="OpenFileBrowserWindFile();" value="<?echo GetMessage("CTRLR_UPLOAD_OPEN_FILE_BUTTON")?>">
	</td>
</tr>
<tr>
	<td>
		<?=GetMessage('CTRLR_UPLOAD_SEND_FILE_TO')?>
	</td>
	<td>
		<input type="text" id="fpath_to" name="fpath_to">
	</td>
</tr>

<?$editTab->Buttons();?>
<input <?if (!$USER->IsAdmin()) echo "disabled"?> type="button" accesskey="x" name="execute" value="<?echo GetMessage("CTRLR_UPLOAD_BUTT_RUN")?>" onclick="return __FPHPSubmit();" class="adm-btn-save">
<input type="reset" value="<?echo GetMessage("CTRLR_UPLOAD_BUTT_CLEAR")?>">
<?
$editTab->End();
?>
</form>
<?
$lAdmin->DisplayList();

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
?>
