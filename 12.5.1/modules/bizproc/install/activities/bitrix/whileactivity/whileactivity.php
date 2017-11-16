<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CBPWhileActivity
	extends CBPCompositeActivity
	implements IBPActivityEventListener
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array("Title" => "", "Condition" => null);
	}

	protected function GetACNames()
	{
		$ar = parent::GetACNames();
		if ($this->arProperties["Condition"] != null)
			$ar[] = substr(get_class($this->arProperties["Condition"]), 3);
		return $ar;
	}

	public function InitializeFromArray($arParams)
	{
		if (is_array($arParams))
		{
			foreach ($arParams as $key => $value)
			{
				$this->arProperties["Condition"] = $this->CreateCondition($key, $value);
				if ($this->arProperties["Condition"] != null)
					break;
			}
			if ($this->arProperties["Condition"] == null)
				throw new Exception(GetMessage("BPWA_NO_CONDITION"));
		}
	}

	private function CreateCondition($conditionCode, $data)
	{
		$runtime = CBPRuntime::GetRuntime();
		if ($runtime->IncludeActivityFile($conditionCode))
			return CBPActivityCondition::CreateInstance($conditionCode, $data);
		else
			return null;
	}

	public function Execute()
	{
		if ($this->TryNextIteration())
			return CBPActivityExecutionStatus::Executing;

		return CBPActivityExecutionStatus::Closed;
	}

	public function Cancel()
	{
		if (count($this->arActivities) == 0)
			return CBPActivityExecutionStatus::Closed;

		$activity = $this->arActivities[0];
		if ($activity->executionStatus == CBPActivityExecutionStatus::Executing)
			$this->workflow->CancelActivity($activity);

		return CBPActivityExecutionStatus::Canceling;
	}

	public function OnEvent(CBPActivity $sender, $arEventParameters = array())
	{
		if ($sender == null)
			throw new Exception("sender");

		$sender->RemoveStatusChangeHandler(self::ClosedEvent, $this);

		if (!$this->TryNextIteration())
			$this->workflow->CloseActivity($this);
	}

	private function TryNextIteration()
	{
		if (($this->executionStatus == CBPActivityExecutionStatus::Canceling) || ($this->executionStatus == CBPActivityExecutionStatus::Faulting))
			return false;

		if	(!$this->Condition->Evaluate($this))
			return false;

		if (count($this->arActivities) > 0)
		{
			$activity = $this->arActivities[0];
			$activity->ReInitialize();
			$activity->AddStatusChangeHandler(self::ClosedEvent, $this);
			$this->workflow->ExecuteActivity($activity);
		}
		return true;
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "")
	{
		if (!is_array($arWorkflowParameters))
			$arWorkflowParameters = array();
		if (!is_array($arWorkflowVariables))
			$arWorkflowVariables = array();

		$runtime = CBPRuntime::GetRuntime();
		$arActivities = $runtime->SearchActivitiesByType("condition");

		$defaultCondition = "";
		$defaultConditionValue = null;
		if (!is_array($arCurrentValues))
		{
			$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
			if (is_array($arCurrentActivity["Properties"]))
			{
				foreach ($arCurrentActivity["Properties"] as $key => $value)
				{
					if (array_key_exists($key, $arActivities))
					{
						$defaultCondition = $key;
						$defaultConditionValue = $value;
						break;
					}
				}
			}
		}

		$firstConditionType = "";
		$arActivityKeys = array_keys($arActivities);
		foreach ($arActivityKeys as $activityKey)
		{
			$runtime->IncludeActivityFile($activityKey);
			$v = CBPActivityCondition::CallStaticMethod(
				$activityKey,
				"GetPropertiesDialog",
				array($documentType, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, (($defaultCondition == $activityKey) ? $defaultConditionValue : null), $arCurrentValues, $formName)
			);
			if ($v == null)
			{
				unset($arActivities[$activityKey]);
				continue;
			}

			$arActivities[$activityKey]["PROPERTIES_DIALOG"] = $v;
			if (strlen($firstConditionType) <= 0)
				$firstConditionType = $activityKey;
		}

		if (!is_array($arCurrentValues))
			$arCurrentValues = array("condition_type" => $defaultCondition);

		return $runtime->ExecuteResourceFile(
			__FILE__,
			"properties_dialog.php",
			array(
				"arActivities" => $arActivities,
				"arCurrentValues" => $arCurrentValues,
				"firstConditionType" => $firstConditionType
			)
		);
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = array();

		$runtime = CBPRuntime::GetRuntime();
		$arActivities = $runtime->SearchActivitiesByType("condition");

		foreach ($arTestProperties as $key => $value)
		{
			if (array_key_exists($key, $arActivities))
			{
				$runtime->IncludeActivityFile($key);

				$arErrors = array_merge(
					CBPActivityCondition::CallStaticMethod(
						$key,
						"ValidateProperties",
						array($value, $user)
					),
					$arErrors
				);
			}
		}

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		$runtime = CBPRuntime::GetRuntime();
		$arActivities = $runtime->SearchActivitiesByType("condition");

		if (!array_key_exists($arCurrentValues["condition_type"], $arActivities))
		{
			$arErrors[] = array(
				"code" => "",
				"message" => GetMessage("BPWA_INVALID_CONDITION_TYPE"),
			);
			return false;
		}

		$condition = CBPActivityCondition::CallStaticMethod(
			$arCurrentValues["condition_type"],
			"GetPropertiesDialogValues",
			array($documentType, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues, &$arErrors)
		);

		if ($condition != null)
		{
			$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
			//if (!is_array($arCurrentActivity["Properties"]))
				$arCurrentActivity["Properties"] = array();

			$arCurrentActivity["Properties"][$arCurrentValues["condition_type"]] = $condition;

			return true;
		}

		return false;
	}
}
?>