<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule("bizproc") || !CModule::IncludeModule("iblock"))
	return false;

if (!$GLOBALS["USER"]->IsAuthorized())
{
	$GLOBALS["APPLICATION"]->AuthForm("");
	die();
}

if (strlen($arParams["TASK_ID"]) > 0 && !is_numeric($arParams["TASK_ID"]))
{
	$arParams["WORKFLOW_ID"] = $arParams["TASK_ID"];
	$arParams["TASK_ID"] = 0;
}

$arParams["TASK_ID"] = intval($arParams["TASK_ID"]);
if ($arParams["TASK_ID"] <= 0)
	$arParams["TASK_ID"] = intval($_REQUEST["TASK_ID"]);
if ($arParams["TASK_ID"] <= 0)
	$arParams["TASK_ID"] = intval($_REQUEST["task_id"]);

$arParams["USER_ID"] = intval(empty($arParams["USER_ID"]) ? $GLOBALS["USER"]->GetID() : $arParams["USER_ID"]);
$arParams["WORKFLOW_ID"] = (empty($arParams["WORKFLOW_ID"]) ? $_REQUEST["WORKFLOW_ID"] : $arParams["WORKFLOW_ID"]);

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? COption::GetOptionString("bizproc", "name_template", CSite::GetNameFormat(false), SITE_ID) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$arResult["backUrl"] = $_REQUEST["back_url"];

$arParams["TASK_EDIT_URL"] = trim($arParams["TASK_EDIT_URL"]);
if (empty($arParams["TASK_EDIT_URL"])):
	$arParams["TASK_EDIT_URL"] = $APPLICATION->GetCurPage()."?PAGE_NAME=task_edit&ID=#ID#&back_url=".urlencode($arResult["backUrl"]);
else:
	$arParams["TASK_EDIT_URL"] .= (strpos($arParams["TASK_EDIT_URL"], "?") === false ? "?" : "&")."back_url=".urlencode($arResult["backUrl"]);
endif;
$arParams["~TASK_EDIT_URL"] = $arParams["TASK_EDIT_URL"];
$arParams["TASK_EDIT_URL"] = htmlspecialcharsbx($arParams["~TASK_EDIT_URL"]);

$arParams["SET_TITLE"] = ($arParams["SET_TITLE"] == "N" ? "N" : "Y"); //Turn on by default
$arParams["SET_NAV_CHAIN"] = ($arParams["SET_NAV_CHAIN"] == "N" ? "N" : "Y"); //Turn on by default


$arResult["ERROR_MESSAGE"] = "";

$arResult["TaskFormButtons"] = "";
$arResult["TaskForm"] = "";
$arResult["ShowMode"] = "Form";

$arResult["TASK"] = false;

if ($arParams["TASK_ID"] > 0)
{
	$dbTask = CBPTaskService::GetList(
		array(),
		array("ID" => $arParams["TASK_ID"], "USER_ID" => $arParams["USER_ID"]),
		false,
		false,
		array("ID", "WORKFLOW_ID", "ACTIVITY", "ACTIVITY_NAME", "MODIFIED", "OVERDUE_DATE", "NAME", "DESCRIPTION", "PARAMETERS")
	);
	$arResult["TASK"] = $dbTask->GetNext();
}

if (!$arTask && strlen($arParams["WORKFLOW_ID"]) > 0)
{
	$dbTask = CBPTaskService::GetList(
		array(),
		array("WORKFLOW_ID" => $arParams["WORKFLOW_ID"], "USER_ID" => $arParams["USER_ID"]),
		false,
		false,
		array("ID", "WORKFLOW_ID", "ACTIVITY", "ACTIVITY_NAME", "MODIFIED", "OVERDUE_DATE", "NAME", "DESCRIPTION", "PARAMETERS")
	);
	$arResult["TASK"] = $dbTask->GetNext();
}

if (!$arResult["TASK"])
{
	ShowError(GetMessage("BPAT_NO_TASK"));
	return false;
}

$arResult["TASK"]["MODULE_ID"] = $arResult["TASK"]["PARAMETERS"]["DOCUMENT_ID"][0];
$arResult["TASK"]["ENTITY"] = $arResult["TASK"]["PARAMETERS"]["DOCUMENT_ID"][1];
$arResult["TASK"]["DOCUMENT_ID"] = $arResult["TASK"]["PARAMETERS"]["DOCUMENT_ID"][2];

$arParams["DOCUMENT_URL"] = trim($arParams["DOCUMENT_URL"]);
if (empty($arParams["DOCUMENT_URL"]))
	$arParams["DOCUMENT_URL"] = CBPDocument::GetDocumentAdminPage($arResult["TASK"]["PARAMETERS"]["DOCUMENT_ID"]);
else
	$arParams["DOCUMENT_URL"] = CComponentEngine::MakePathFromTemplate($arParams["DOCUMENT_URL"], $arResult["TASK"]);

$arResult["TASK"]["URL"] = array(
	"VIEW" => htmlspecialcharsbx($arParams["DOCUMENT_URL"]),
	"~VIEW" => $arParams["DOCUMENT_URL"]
);

if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["action"] == "doTask" && check_bitrix_sessid())
{
	$arErrorsTmp = array();
	if (CBPDocument::PostTaskForm($arResult["TASK"], $GLOBALS["USER"]->GetID(), $_REQUEST + $_FILES, $arErrorsTmp, $GLOBALS["USER"]->GetFormattedName(false)))
	{
		$arResult["ShowMode"] = "Success";

		$d = CBPTaskService::GetList(
			array(),
			array("WORKFLOW_ID" => $arResult["TASK"]["WORKFLOW_ID"], "USER_ID" => $arParams["USER_ID"]),
			false,
			false,
			array("ID")
		);
		if ($r = $d->Fetch())
			$backUrl = CComponentEngine::MakePathFromTemplate($arParams["TASK_EDIT_URL"], array("ID" => $r["ID"], "task_id" => $r["ID"]));
		else
			$backUrl = $arResult["backUrl"];

		if (strlen($backUrl) > 0)
		{
			LocalRedirect($backUrl);
			die();
		}
	}
	else
	{
		$arError = array();
		foreach ($arErrorsTmp as $e)
			$arError[] = array(
				"id" => "bad_task", 
				"text" => $e["message"]);
		$e = new CAdminException($arError);
		$arResult["ERROR_MESSAGE"] = $e->GetString();
	}
}

if ($arResult["ShowMode"] != "Success")
{
	$runtime = CBPRuntime::GetRuntime();
	$runtime->StartRuntime();
	$documentService = $runtime->GetService("DocumentService");
	$documentType = $documentService->GetDocumentType($arResult["TASK"]["PARAMETERS"]["DOCUMENT_ID"]);
	if (!array_key_exists("BP_AddShowParameterInit_".$documentType[0]."_".$documentType[1]."_".$documentType[2], $GLOBALS))
	{
		$GLOBALS["BP_AddShowParameterInit_".$documentType[0]."_".$documentType[1]."_".$documentType[2]] = 1;
		CBPDocument::AddShowParameterInit($documentType[0], "only_users", $documentType[2], $documentType[1]);
	}

	list($arResult["TaskForm"], $arResult["TaskFormButtons"]) = CBPDocument::ShowTaskForm(
		$arResult["TASK"],
		$arParams["USER_ID"],
		"",
		($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["action"] == "doTask") ? $_REQUEST : null
	);
}

$this->IncludeComponentTemplate();

if ($arParams["SET_TITLE"] == "Y")
	$APPLICATION->SetTitle(GetMessage("BPAT_TITLE"));
if ($arParams["SET_NAV_CHAIN"] == "Y")
	$APPLICATION->AddChainItem(GetMessage("BPAT_TITLE"));
?>