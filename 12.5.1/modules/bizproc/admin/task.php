<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/bizproc/include.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/bizproc/prolog.php");

IncludeModuleLangFile(__FILE__);

$errorMessage = "";

$allowAdminAccess = $USER->IsAdmin();

$taskId = intval($_REQUEST["id"]);
$userId = intval($_REQUEST["uid"]);
if ($allowAdminAccess)
{
	if ($userId <= 0)
		$userId = $USER->GetID();
}
else
{
	$userId = $USER->GetID();
}

$arTask = false;
if ($taskId > 0)
{
	$dbTask = CBPTaskService::GetList(
		array(),
		array("ID" => $taskId, "USER_ID" => $allowAdminAccess ? $userId : $USER->GetID()),
		false,
		false,
		array("ID", "WORKFLOW_ID", "ACTIVITY", "ACTIVITY_NAME", "MODIFIED", "OVERDUE_DATE", "NAME", "DESCRIPTION", "PARAMETERS", "USER_ID")
	);
	$arTask = $dbTask->GetNext();
}

if (!$arTask)
{
	$workflowId = trim($_REQUEST["workflow_id"]);

	if (strlen($workflowId) > 0)
	{
		$dbTask = CBPTaskService::GetList(
			array(),
			array("WORKFLOW_ID" => $workflowId, "USER_ID" => $allowAdminAccess ? $userId : $USER->GetID()),
			false,
			false,
			array("ID", "WORKFLOW_ID", "ACTIVITY", "ACTIVITY_NAME", "MODIFIED", "OVERDUE_DATE", "NAME", "DESCRIPTION", "PARAMETERS", "USER_ID")
		);
		$arTask = $dbTask->GetNext();
	}
}

if (!$arTask)
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

	$APPLICATION->SetTitle(GetMessage("BPAT_NO_TASK"));
	CAdminMessage::ShowMessage(GetMessage("BPAT_NO_TASK").". ");
}
else
{
	$backUrl = "/".ltrim(trim($_REQUEST["back_url"]), "\\/");
	if (strlen($backUrl) <= 0)
		$backUrl = "/bitrix/admin/bizproc_task_list.php?lang=".LANGUAGE_ID;
	if (strlen($backUrl) <= 0 && isset($arTask["PARAMETERS"]["DOCUMENT_ID"]))
		$backUrl = CBPDocument::GetDocumentAdminPage($arTask["PARAMETERS"]["DOCUMENT_ID"]);

	$showType = "Form";
	if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["action"] == "doTask" && check_bitrix_sessid())
	{
		$arErrorsTmp = array();
		if (CBPDocument::PostTaskForm($arTask, $allowAdminAccess ? $userId : $USER->GetID(), $_REQUEST + $_FILES, $arErrorsTmp, $USER->GetFormattedName(false)))
		{
			$showType = "Success";
			if (strlen($backUrl) > 0)
			{
				LocalRedirect($backUrl);
				die();
			}
		}
		else
		{
			foreach ($arErrorsTmp as $e)
				$errorMessage .= $e["message"].".<br />";
		}
	}

	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

	$aMenu = array(
		array(
			"TEXT" => GetMessage("BPAT_BACK"),
			"LINK" => $backUrl,
			"ICON" => "btn_list",
		)
	);
	$context = new CAdminContextMenu($aMenu);
	$context->Show();

	$APPLICATION->SetTitle(str_replace("#ID#", $taskId, GetMessage("BPAT_TITLE")));

	if (strlen($errorMessage) > 0)
		CAdminMessage::ShowMessage($errorMessage);

	$runtime = CBPRuntime::GetRuntime();
	$runtime->StartRuntime();
	$documentService = $runtime->GetService("DocumentService");
	$documentType = $documentService->GetDocumentType($arTask["PARAMETERS"]["DOCUMENT_ID"]);
	if (!array_key_exists("BP_AddShowParameterInit_".$documentType[0]."_".$documentType[1]."_".$documentType[2], $GLOBALS))
	{
		$GLOBALS["BP_AddShowParameterInit_".$documentType[0]."_".$documentType[1]."_".$documentType[2]] = 1;
		CBPDocument::AddShowParameterInit($documentType[0], "only_users", $documentType[2], $documentType[1]);
	}

	list($taskForm, $taskFormButtons) = array("", "");
	if ($showType != "Success")
		list($taskForm, $taskFormButtons) = CBPDocument::ShowTaskForm($arTask, $allowAdminAccess ? $userId : $USER->GetID(), "", ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["action"] == "doTask") ? $_REQUEST : null);

	?>
	<form method="post" name="task_form1" action="<?= GetPagePath(false, true) ?>" enctype="multipart/form-data">
		<input type="hidden" name="action" value="doTask">
		<input type="hidden" name="id" value="<?= intval($arTask["ID"]) ?>">
		<input type="hidden" name="workflow_id" value="<?= htmlspecialcharsbx($arTask["WORKFLOW_ID"]) ?>">
		<input type="hidden" name="back_url" value="<?= htmlspecialcharsbx($backUrl) ?>">
		<?= bitrix_sessid_post() ?>
		<?
		if ($allowAdminAccess)
			echo '<input type="hidden" name="uid" value="'.intval($arTask["USER_ID"]).'">';

		$aTabs = array(
			array("DIV" => "edit1", "TAB" => GetMessage("BPAT_TAB"), "ICON" => "bizproc", "TITLE" => GetMessage("BPAT_TAB_TITLE"))
		);

		$tabControl = new CAdminTabControl("tabControl", $aTabs);

		$tabControl->Begin();
		$tabControl->BeginNextTab();
		?>
			<?if ($allowAdminAccess):?>
			<tr>
				<td align="right" valign="top" width="40%"><?= GetMessage("BPAT_USER") ?>:</td>
				<td width="60%" valign="top">
					<?
					$dbUserTmp = CUser::GetByID($arTask["USER_ID"]);
					$arUserTmp = $dbUserTmp->GetNext();
					$str = CUser::FormatName(COption::GetOptionString("bizproc", "name_template", CSite::GetNameFormat(false), SITE_ID), $arUserTmp, true);
					$str .= " [".$arTask["USER_ID"]."]";
					echo $str;
					?>
				</td>
			</tr>
			<?endif;?>
			<tr>
				<td align="right" valign="top" width="40%"><?= GetMessage("BPAT_NAME") ?>:</td>
				<td width="60%" valign="top"><?= $arTask["NAME"] ?></td>
			</tr>
			<tr>
				<td align="right" valign="top" width="40%"><?= GetMessage("BPAT_DESCR") ?>:</td>
				<td width="60%" valign="top"><?= nl2br($arTask["DESCRIPTION"]) ?></td>
			</tr>
			<?if (strlen($arTask["PARAMETERS"]["DOCUMENT_URL"]) > 0):?>
			<tr>
				<td align="right" valign="top" width="40%">&nbsp;</td>
				<td width="60%" valign="top"><a href="<?= $arTask["PARAMETERS"]["DOCUMENT_URL"] ?>" target="_blank"><?= GetMessage("BPAT_GOTO_DOC") ?></a></td>
			</tr>
			<?endif;?>
			<?= $taskForm; ?>
		<?
		$tabControl->Buttons();
		?>
			<?= $taskFormButtons ?>
		<?
		$tabControl->End();

		?>
	</form>
	<?
}
?>

<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>
