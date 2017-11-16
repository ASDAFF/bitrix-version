<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CBPDelayActivity
	extends CBPActivity
	implements IBPEventActivity, IBPActivityExternalEventListener
{
	private $subscriptionId = 0;
	private $isInEventActivityMode = false;

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"Title" => "",
			"TimeoutDuration" => null,
			"TimeoutDurationType" => "s",
			"TimeoutTime" => null,
		);
	}

	public function Cancel()
	{
		if (!$this->isInEventActivityMode && $this->subscriptionId > 0)
			$this->Unsubscribe($this);

		return CBPActivityExecutionStatus::Closed;
	}

	public function Execute()
	{
		if ($this->isInEventActivityMode)
			return CBPActivityExecutionStatus::Closed;

		if ($this->TimeoutTime != null && (intval($this->TimeoutTime)."|" != $this->TimeoutTime."|"))
			$this->TimeoutTime = MakeTimeStamp($this->TimeoutTime);

		$this->Subscribe($this);

		if ($this->TimeoutDuration != null)
		{
			$timeoutDuration = $this->CalculateTimeoutDuration();
			$this->WriteToTrackingService(str_replace("#PERIOD#", CBPHelper::FormatTimePeriod($timeoutDuration), GetMessage("BPDA_TRACK")));
		}
		elseif ($this->TimeoutTime != null)
		{
			$this->WriteToTrackingService(str_replace("#PERIOD#", ConvertTimeStamp($this->TimeoutTime, "FULL"), GetMessage("BPDA_TRACK1")));
		}
		else
		{
			$this->WriteToTrackingService(GetMessage("BPDA_TRACK2"));
		}

		$this->isInEventActivityMode = false;
		return CBPActivityExecutionStatus::Executing;
	}

	public function Subscribe(IBPActivityExternalEventListener $eventHandler)
	{
		if ($eventHandler == null)
			throw new Exception("eventHandler");

		$this->isInEventActivityMode = true;
		if ($this->TimeoutDuration != null)
			$expiresAt = time() + $this->CalculateTimeoutDuration();
		elseif ($this->TimeoutTime != null)
			$expiresAt = $this->TimeoutTime;
		else
			$expiresAt = time();

		$schedulerService = $this->workflow->GetService("SchedulerService");
		$this->subscriptionId = $schedulerService->SubscribeOnTime($this->workflow->GetInstanceId(), $this->name, $expiresAt);

		$this->workflow->AddEventHandler($this->name, $eventHandler);
	}

	public function Unsubscribe(IBPActivityExternalEventListener $eventHandler)
	{
		if ($eventHandler == null)
			throw new Exception("eventHandler");

		$schedulerService = $this->workflow->GetService("SchedulerService");
		$schedulerService->UnSubscribeOnTime($this->subscriptionId);

		$this->workflow->RemoveEventHandler($this->name, $eventHandler);

		$this->subscriptionId = 0;
	}

	public function OnExternalEvent($arEventParameters = array())
	{
		if ($this->executionStatus != CBPActivityExecutionStatus::Closed)
		{
			$this->Unsubscribe($this);
			$this->workflow->CloseActivity($this);
		}
	}

	public function HandleFault(Exception $exception)
	{
		if ($exception == null)
			throw new Exception("exception");

		$status = $this->Cancel();
		if ($status == CBPActivityExecutionStatus::Canceling)
			return CBPActivityExecutionStatus::Faulting;

		return $status;
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = array();

		if (
			(!array_key_exists("TimeoutDuration", $arTestProperties)
			|| (intval($arTestProperties["TimeoutDuration"]) <= 0 
				&& !preg_match('#^{=[A-Za-z0-9_]+:[A-Za-z0-9_]+}$#i', $arTestProperties["TimeoutDuration"])
				&& (substr($arTestProperties["TimeoutDuration"], 0, 1) != "=")))
			&&
			(!array_key_exists("TimeoutTime", $arTestProperties)
			|| (intval($arTestProperties["TimeoutTime"]) <= 0
				&& !preg_match('#^{=[A-Za-z0-9_]+:[A-Za-z0-9_]+}$#i', $arTestProperties["TimeoutTime"])
				&& (substr($arTestProperties["TimeoutTime"], 0, 1) != "=")))
			)
		{
			$arErrors[] = array("code" => "NotExist", "parameter" => "TimeoutDuration", "message" => GetMessage("BPDA_EMPTY_PROP"));
		}

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}

	private function CalculateTimeoutDuration()
	{
		$timeoutDuration = ($this->IsPropertyExists("TimeoutDuration") ? $this->TimeoutDuration : 0);

		$timeoutDurationType = ($this->IsPropertyExists("TimeoutDurationType") ? $this->TimeoutDurationType : "s");
		$timeoutDurationType = strtolower($timeoutDurationType);
		if (!in_array($timeoutDurationType, array("s", "d", "h", "m")))
			$timeoutDurationType = "s";

		$timeoutDuration = intval($timeoutDuration);
		switch ($timeoutDurationType)
		{
			case 'd':
				$timeoutDuration *= 3600 * 24;
				break;
			case 'h':
				$timeoutDuration *= 3600;
				break;
			case 'm':
				$timeoutDuration *= 60;
				break;
			default:
				break;
		}

		return $timeoutDuration;
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "")
	{
		$runtime = CBPRuntime::GetRuntime();

		if (!is_array($arCurrentValues))
		{
			$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);

			if (is_array($arCurrentActivity["Properties"]))
			{
				if (array_key_exists("TimeoutDuration", $arCurrentActivity["Properties"]) && !is_null($arCurrentActivity["Properties"]["TimeoutDuration"]))
					$arCurrentValues["delay_time"] = $arCurrentActivity["Properties"]["TimeoutDuration"];
				if (array_key_exists("TimeoutDurationType", $arCurrentActivity["Properties"]) && !is_null($arCurrentActivity["Properties"]["TimeoutDurationType"]))
					$arCurrentValues["delay_type"] = $arCurrentActivity["Properties"]["TimeoutDurationType"];
				if (array_key_exists("TimeoutTime", $arCurrentActivity["Properties"]) && !is_null($arCurrentActivity["Properties"]["TimeoutTime"]))
				{
					$arCurrentValues["delay_date"] = $arCurrentActivity["Properties"]["TimeoutTime"];
					if (!preg_match("#^\{=[a-z0-9_]+:[a-z0-9_]+\}$#i", $arCurrentValues["delay_date"]) && (substr($arCurrentValues["delay_date"], 0, 1) != "="))
						$arCurrentValues["delay_date"] = ConvertTimeStamp($arCurrentValues["delay_date"], "FULL");
				}
			}

			if (is_array($arCurrentValues)
				&& array_key_exists("delay_time", $arCurrentValues)
				&& (intval($arCurrentValues["delay_time"]) > 0)
				&& !array_key_exists("delay_type", $arCurrentValues))
			{
				$arCurrentValues["delay_time"] = intval($arCurrentValues["delay_time"]);

				$arCurrentValues["delay_type"] = "s";
				if ($arCurrentValues["delay_time"] % (3600 * 24) == 0)
				{
					$arCurrentValues["delay_time"] = $arCurrentValues["delay_time"] / (3600 * 24);
					$arCurrentValues["delay_type"] = "d";
				}
				elseif ($arCurrentValues["delay_time"] % 3600 == 0)
				{
					$arCurrentValues["delay_time"] = $arCurrentValues["delay_time"] / 3600;
					$arCurrentValues["delay_type"] = "h";
				}
				elseif ($arCurrentValues["delay_time"] % 60 == 0)
				{
					$arCurrentValues["delay_time"] = $arCurrentValues["delay_time"] / 60;
					$arCurrentValues["delay_type"] = "m";
				}
			}
		}

		if (!is_array($arCurrentValues) || !array_key_exists("delay_type", $arCurrentValues))
			$arCurrentValues["delay_type"] = "s";
		if (!is_array($arCurrentValues) || !array_key_exists("delay_time", $arCurrentValues) && !array_key_exists("delay_date", $arCurrentValues))
		{
			$arCurrentValues["delay_time"] = 1;
			$arCurrentValues["delay_type"] = "h";
		}

		return $runtime->ExecuteResourceFile(
			__FILE__,
			"properties_dialog.php",
			array(
				"arCurrentValues" => $arCurrentValues,
				"formName" => $formName
			)
		);
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		$arErrors = Array();

		$runtime = CBPRuntime::GetRuntime();

		$arProperties = array();

		if ($arCurrentValues["time_type_selector"] == "time")
		{
			if ((strlen($arCurrentValues["delay_date"]) > 0)
				&& ($d = MakeTimeStamp($arCurrentValues["delay_date"])))
			{
				$arProperties["TimeoutTime"] = $d;
			}
			elseif ((strlen($arCurrentValues["delay_date_x"]) > 0)
				&& (preg_match('#^{=[A-Za-z0-9_]+:[A-Za-z0-9_]+}$#i', $arCurrentValues["delay_date_x"]) || (substr($arCurrentValues["delay_date_x"], 0, 1) == "=")))
			{
				$arProperties["TimeoutTime"] = $arCurrentValues["delay_date_x"];
			}
		}
		else
		{
			$arProperties["TimeoutDuration"] = $arCurrentValues["delay_time"];
			$arProperties["TimeoutDurationType"] = $arCurrentValues["delay_type"];
		}

		$arErrors = self::ValidateProperties($arProperties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($arErrors) > 0)
			return false;

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $arProperties;

		return true;
	}
}
?>
