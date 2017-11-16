<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CBPSetStateActivity
	extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array("Title" => "", "TargetStateName" => "");
	}

	public function Execute()
	{
		$stateActivity = $this;
		while ($stateActivity != null && !is_a($stateActivity, "CBPStateActivity"))
			$stateActivity = $stateActivity->parent;

		$stateActivity->SetNextStateName($this->TargetStateName);

		return CBPActivityExecutionStatus::Closed;
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = array();

		if (strlen($arTestProperties["TargetStateName"]) <= 0)
		{
			$arErrors[] = array("code" => "emptyState", "parameter" => "TargetStateName", "message" => "Bad target state.");
		}

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "")
	{
		$runtime = CBPRuntime::GetRuntime();

		$arStates = CBPWorkflowTemplateLoader::GetStatesOfTemplate($arWorkflowTemplate);

		if (!is_array($arCurrentValues))
		{
			$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
			if (is_array($arCurrentActivity["Properties"]) && array_key_exists("TargetStateName", $arCurrentActivity["Properties"]))
				$arCurrentValues["target_state_name"] = $arCurrentActivity["Properties"]["TargetStateName"];
		}

		return $runtime->ExecuteResourceFile(
			__FILE__,
			"properties_dialog.php",
			array(
				"arStates" => $arStates,
				"arCurrentValues" => $arCurrentValues,
			)
		);
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		$arErrors = array();

		$runtime = CBPRuntime::GetRuntime();

		$state = ((strlen($arCurrentValues["target_state_name_1"]) > 0) ? $arCurrentValues["target_state_name_1"] : $arCurrentValues["target_state_name"]);
		$arProperties = array("TargetStateName" => $state);

		$arErrors = self::ValidateProperties($arProperties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($arErrors) > 0)
			return false;

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $arProperties;

		return true;
	}

}
?>