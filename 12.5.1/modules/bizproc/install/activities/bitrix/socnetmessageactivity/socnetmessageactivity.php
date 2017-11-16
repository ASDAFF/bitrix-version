<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CBPSocNetMessageActivity
	extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"Title" => "",
			"MessageUserFrom" => "",
			"MessageUserTo" => "",
			"MessageText" => "",
		);
	}

	public function Execute()
	{
		if (!CModule::IncludeModule("socialnetwork"))
			return CBPActivityExecutionStatus::Closed;

		$rootActivity = $this->GetRootActivity();
		$documentId = $rootActivity->GetDocumentId();

		$arMessageUserFrom = CBPHelper::ExtractUsers($this->MessageUserFrom, $documentId, true);
		$arMessageUserTo = CBPHelper::ExtractUsers($this->MessageUserTo, $documentId, false);

		$arMessageFields = array(
			"=DATE_CREATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
			"MESSAGE_TYPE" => SONET_MESSAGE_SYSTEM,
			"FROM_USER_ID" => $arMessageUserFrom,
			"MESSAGE" => CBPHelper::ConvertTextForMail($this->MessageText),
		);
		$ar = array();
		foreach ($arMessageUserTo as $userTo)
		{
			if (in_array($userTo, $ar))
				continue;

			$ar[] = $userTo;
			$arMessageFields["TO_USER_ID"] = $userTo;
			CSocNetMessages::Add($arMessageFields);
		}

		return CBPActivityExecutionStatus::Closed;
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = array();

		if (!array_key_exists("MessageUserFrom", $arTestProperties) || count($arTestProperties["MessageUserFrom"]) <= 0)
			$arErrors[] = array("code" => "NotExist", "parameter" => "MessageUserFrom", "message" => GetMessage("BPSNMA_EMPTY_FROM"));
		if (!array_key_exists("MessageUserTo", $arTestProperties) || count($arTestProperties["MessageUserTo"]) <= 0)
			$arErrors[] = array("code" => "NotExist", "parameter" => "MessageUserTo", "message" => GetMessage("BPSNMA_EMPTY_TO"));
		if (!array_key_exists("MessageText", $arTestProperties) || strlen($arTestProperties["MessageText"]) <= 0)
			$arErrors[] = array("code" => "NotExist", "parameter" => "MessageText", "message" => GetMessage("BPSNMA_EMPTY_MESSAGE"));

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "")
	{
		$runtime = CBPRuntime::GetRuntime();

		$arMap = array(
			"MessageUserFrom" => "message_user_from",
			"MessageUserTo" => "message_user_to",
			"MessageText" => "message_text",
		);

		if (!is_array($arWorkflowParameters))
			$arWorkflowParameters = array();
		if (!is_array($arWorkflowVariables))
			$arWorkflowVariables = array();

		if (!is_array($arCurrentValues))
		{
			$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
			if (is_array($arCurrentActivity["Properties"]))
			{
				foreach ($arMap as $k => $v)
				{
					if (array_key_exists($k, $arCurrentActivity["Properties"]))
					{
						if ($k == "MessageUserFrom" || $k == "MessageUserTo")
							$arCurrentValues[$arMap[$k]] = CBPHelper::UsersArrayToString($arCurrentActivity["Properties"][$k], $arWorkflowTemplate, $documentType);
						else
							$arCurrentValues[$arMap[$k]] = $arCurrentActivity["Properties"][$k];
					}
					else
					{
						$arCurrentValues[$arMap[$k]] = "";
					}
				}
			}
			else
			{
				foreach ($arMap as $k => $v)
					$arCurrentValues[$arMap[$k]] = "";
			}
		}

		return $runtime->ExecuteResourceFile(
			__FILE__,
			"properties_dialog.php",
			array(
				"arCurrentValues" => $arCurrentValues,
				"formName" => $formName,
			)
		);
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		$arErrors = array();

		$runtime = CBPRuntime::GetRuntime();

		$arMap = array(
			"message_user_from" => "MessageUserFrom",
			"message_user_to" => "MessageUserTo",
			"message_text" => "MessageText",
		);

		$arProperties = array();
		foreach ($arMap as $key => $value)
		{
			if ($key == "message_user_from" || $key == "message_user_to")
				continue;
			$arProperties[$value] = $arCurrentValues[$key];
		}

		global $USER;
		if ($USER->IsAdmin() || (CModule::IncludeModule("bitrix24") && CBitrix24::IsPortalAdmin($USER->GetID())))
		{
			$arProperties["MessageUserFrom"] = CBPHelper::UsersStringToArray($arCurrentValues["message_user_from"], $documentType, $arErrors);
			if (count($arErrors) > 0)
				return false;
		}
		else
		{
			$arProperties["MessageUserFrom"] = "user_".$USER->GetID();
		}

		//global $USER;
		//if (!$USER->IsAdmin())
		//	$arProperties["MessageUserFrom"] = "user_".$USER->GetID();

		$arProperties["MessageUserTo"] = CBPHelper::UsersStringToArray($arCurrentValues["message_user_to"], $documentType, $arErrors);
		if (count($arErrors) > 0)
			return false;

		$arErrors = self::ValidateProperties($arProperties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($arErrors) > 0)
			return false;

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $arProperties;

		return true;
	}
}
?>