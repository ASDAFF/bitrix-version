<?
IncludeModuleLangFile(__FILE__);

abstract class CBPActivity
{
	public $parent = null;

	public $executionStatus = CBPActivityExecutionStatus::Initialized;
	public $executionResult = CBPActivityExecutionResult::None;

	private $arStatusChangeHandlers = array();

	const StatusChangedEvent = 0;
	const ExecutingEvent = 1;
	const CancelingEvent = 2;
	const ClosedEvent = 3;
	const FaultingEvent = 4;

	protected $arProperties = array();
	protected $arPropertiesTypes = array();

	protected $name = "";
	public $workflow = null;

	public $arEventsMap = array();

	/************************  PROPERTIES  ************************************************/

	public function GetDocumentId()
	{
		$rootActivity = $this->GetRootActivity();
		return $rootActivity->GetDocumentId();
	}

	public function SetDocumentId($documentId)
	{
		$rootActivity = $this->GetRootActivity();
		$rootActivity->SetDocumentId($documentId);
	}

	public function GetDocumentType()
	{
		$rootActivity = $this->GetRootActivity();
		if (!is_array($rootActivity->documentType) || count($rootActivity->documentType) <= 0)
		{
			$documentService = $this->workflow->GetService("DocumentService");
			$rootActivity->documentType = $documentService->GetDocumentType($rootActivity->documentId);
		}
		return $rootActivity->documentType;
	}

	public function SetDocumentType($documentType)
	{
		$rootActivity = $this->GetRootActivity();
		$rootActivity->documentType = $documentType;
	}

	public function GetWorkflowStatus()
	{
		$rootActivity = $this->GetRootActivity();
		return $rootActivity->GetWorkflowStatus();
	}

	public function SetWorkflowStatus($status)
	{
		$rootActivity = $this->GetRootActivity();
		$rootActivity->SetWorkflowStatus($status);
	}

	public function SetFieldTypes($arFieldTypes = array())
	{
		if (count($arFieldTypes) > 0)
		{
			$rootActivity = $this->GetRootActivity();
			foreach ($arFieldTypes as $key => $value)
				$rootActivity->arFieldTypes[$key] = $value;
		}
	}

	/**********************************************************/
	protected function ClearProperties()
	{
		$rootActivity = $this->GetRootActivity();
		if (is_array($rootActivity->arPropertiesTypes) && count($rootActivity->arPropertiesTypes) > 0
			&& is_array($rootActivity->arFieldTypes) && count($rootActivity->arFieldTypes) > 0)
		{
			foreach ($rootActivity->arPropertiesTypes as $key => $value)
			{
				if ($rootActivity->arFieldTypes[$value["Type"]]["BaseType"] == "file")
				{
					if (is_array($rootActivity->arProperties[$key]))
					{
						foreach ($rootActivity->arProperties[$key] as $v)
							CFile::Delete($v);
					}
					else
					{
						CFile::Delete($rootActivity->arProperties[$key]);
					}
				}
			}
		}
	}

	public function GetPropertyBaseType($propertyName)
	{
		$rootActivity = $this->GetRootActivity();
		return $rootActivity->arFieldTypes[$rootActivity->arPropertiesTypes[$propertyName]["Type"]]["BaseType"];
	}

	public function SetProperties($arProperties = array())
	{
		if (count($arProperties) > 0)
		{
			foreach ($arProperties as $key => $value)
				$this->arProperties[$key] = $value;
		}
	}

	public function SetPropertiesTypes($arPropertiesTypes = array())
	{
		if (count($arPropertiesTypes) > 0)
		{
			foreach ($arPropertiesTypes as $key => $value)
				$this->arPropertiesTypes[$key] = $value;
		}
	}

	/**********************************************************/
	protected function ClearVariables()
	{
		$rootActivity = $this->GetRootActivity();
		if (is_array($rootActivity->arVariablesTypes) && count($rootActivity->arVariablesTypes) > 0
			&& is_array($rootActivity->arFieldTypes) && count($rootActivity->arFieldTypes) > 0)
		{
			foreach ($rootActivity->arVariablesTypes as $key => $value)
			{
				if ($rootActivity->arFieldTypes[$value["Type"]]["BaseType"] == "file")
				{
					if (is_array($rootActivity->arVariables[$key]))
					{
						foreach ($rootActivity->arVariables[$key] as $v)
							CFile::Delete($v);
					}
					else
					{
						CFile::Delete($rootActivity->arVariables[$key]);
					}
				}
			}
		}
	}

	public function GetVariableBaseType($variableName)
	{
		$rootActivity = $this->GetRootActivity();
		return $rootActivity->arFieldTypes[$rootActivity->arVariablesTypes[$variableName]["Type"]]["BaseType"];
	}

	public function SetVariables($arVariables = array())
	{
		if (count($arVariables) > 0)
		{
			$rootActivity = $this->GetRootActivity();
			foreach ($arVariables as $key => $value)
				$rootActivity->arVariables[$key] = $value;
		}
	}

	public function SetVariablesTypes($arVariablesTypes = array())
	{
		if (count($arVariablesTypes) > 0)
		{
			$rootActivity = $this->GetRootActivity();
			foreach ($arVariablesTypes as $key => $value)
				$rootActivity->arVariablesTypes[$key] = $value;
		}
	}

	public function SetVariable($name, $value)
	{
		$rootActivity = $this->GetRootActivity();
		$rootActivity->arVariables[$name] = $value;
	}

	public function GetVariable($name)
	{
		$rootActivity = $this->GetRootActivity();

		if (array_key_exists($name, $rootActivity->arVariables))
			return $rootActivity->arVariables[$name];

		return null;
		//else
		//	throw new Exception(str_replace("#NAME#", htmlspecialcharsbx($name), GetMessage("BPSWA_EMPTY_NAME")));
	}

	public function IsVariableExists($name)
	{
		$rootActivity = $this->GetRootActivity();
		return array_key_exists($name, $rootActivity->arVariables);
	}

	/************************************************/
	public function GetName()
	{
		return $this->name;
	}

	public function GetRootActivity()
	{
		$p = $this;
		while ($p->parent != null)
			$p = $p->parent;
		return $p;
	}

	public function SetWorkflow(CBPWorkflow $workflow)
	{
		$this->workflow = $workflow;
	}

	public function GetWorkflowInstanceId()
	{
		return $this->workflow->GetInstanceId();
	}

	public function SetStatusTitle($title = '')
	{
		$rootActivity = $this->GetRootActivity();
		$stateService = $this->workflow->GetService("StateService");
		if ($rootActivity instanceof CBPStateMachineWorkflowActivity)
		{
			$arState = $stateService->GetWorkflowState($this->GetWorkflowInstanceId());

			$arActivities = $rootActivity->CollectNestedActivities();
			foreach ($arActivities as $activity)
				if ($activity->GetName() == $arState["STATE_NAME"])
					break;

			$stateService->SetStateTitle(
				$this->GetWorkflowInstanceId(),
				$activity->Title.($title != '' ? ": ".$title : '')
			);
		}
		else
		{
			if ($title != '')
			{
				$stateService->SetStateTitle(
					$this->GetWorkflowInstanceId(),
					$title
				);
			}
		}
	}

	public function AddStatusTitle($title = '')
	{
		if ($title == '')
			return;

		$stateService = $this->workflow->GetService("StateService");

		$mainTitle = $stateService->GetStateTitle($this->GetWorkflowInstanceId());
		$mainTitle .= ((strpos($mainTitle, ": ") !== false) ? ", " : ": ").$title;

		$stateService->SetStateTitle($this->GetWorkflowInstanceId(), $mainTitle);
	}

	public function DeleteStatusTitle($title = '')
	{
		if ($title == '')
			return;

		$stateService = $this->workflow->GetService("StateService");
		$mainTitle = $stateService->GetStateTitle($this->GetWorkflowInstanceId());

		$ar1 = explode(":", $mainTitle);
		if (count($ar1) <= 1)
			return;

		$newTitle = "";

		$ar2 = explode(",", $ar1[1]);
		foreach ($ar2 as $a)
		{
			$a = trim($a);
			if ($a != $title)
			{
				if (strlen($newTitle) > 0)
					$newTitle .= ", ";
				$newTitle .= $a;
			}
		}

		$result = $ar1[0].(strlen($newTitle) > 0 ? ": " : "").$newTitle;

		$stateService->SetStateTitle($this->GetWorkflowInstanceId(), $result);
	}

	private function GetPropertyValueRecursive($val)
	{
		// array(2, 5, array("SequentialWorkflowActivity1", "DocumentApprovers"))
		// array("Document", "IBLOCK_ID")
		// array("Workflow", "id")
		// "Hello, {=SequentialWorkflowActivity1:DocumentApprovers}, {=Document:IBLOCK_ID}!"

		if (is_string($val) && preg_match("/^\{=([A-Za-z0-9_]+)\:([A-Za-z0-9_]+)\}$/i", $val, $arMatches))
			$val = array($arMatches[1], $arMatches[2]);

		if (is_array($val))
		{
			$b = true;
			$r = array();

			$keys = array_keys($val);

			$i = 0;
			foreach ($keys as $key)
			{
				if ($key."!" != $i."!")
				{
					$b = false;
					break;
				}
				$i++;
			}

			foreach ($keys as $key)
			{
				list($t, $a) = $this->GetPropertyValueRecursive($val[$key]);
				if ($b)
				{
					if ($t == 1 && is_array($a))
						$r = array_merge($r, $a);
					else
						$r[] = $a;
				}
				else
				{
					$r[$key] = $a;
				}
			}

			if (count($r) == 2)
			{
				$keys = array_keys($r);
				if ($keys[0] == 0 && $keys[1] == 1 && is_string($r[0]) && is_string($r[1]))
				{
					$result = null;
					if ($this->GetRealParameterValue($r[0], $r[1], $result, false))
						return array(1, $result);
				}
			}
			return array(2, $r);
		}
		else
		{
			if (is_string($val))
			{
				if (substr($val, 0, 1) === "=")
				{
					$calc = new CBPCalc($this);
					$r = $calc->Calculate($val);
					if ($r != null)
						return array(2, $r);
				}

				$val = preg_replace_callback(
					"/\{=([A-Za-z0-9_]+)\:([A-Za-z0-9_]+)\}/i",
					array($this, "ParseStringParameter"),
					$val
				);
			}

			return array(2, $val);
		}
	}

	private function GetRealParameterValue($objectName, $fieldName, &$result)
	{
		$return = false;

		if ($objectName == "Document")
		{
			$rootActivity = $this->GetRootActivity();
			$documentId = $rootActivity->GetDocumentId();

			$documentService = $this->workflow->GetService("DocumentService");
			$document = $documentService->GetDocument($documentId);

			if (array_key_exists($fieldName, $document))
			{
				$result = $document[$fieldName];
				$return = true;
			}
		}
		elseif ($objectName == "Template")
		{
			$rootActivity = $this->GetRootActivity();
			if (substr($fieldName, -strlen("_printable")) == "_printable")
			{
				$fieldNameTmp = substr($fieldName, 0, strlen($fieldName) - strlen("_printable"));
				$result = $rootActivity->{$fieldNameTmp};

				$rootActivity = $this->GetRootActivity();
				$documentId = $rootActivity->GetDocumentId();

				$documentService = $this->workflow->GetService("DocumentService");
				$result = $documentService->GetFieldValuePrintable($documentId, $fieldNameTmp, $rootActivity->arPropertiesTypes[$fieldNameTmp]["Type"], $result, $rootActivity->arPropertiesTypes[$fieldNameTmp]);

				if (is_array($result))
					$result = implode(", ", $result);
			}
			else
			{
				$result = $rootActivity->{$fieldName};
			}

			$return = true;
		}
		elseif ($objectName == "Variable")
		{
			$rootActivity = $this->GetRootActivity();

			if (substr($fieldName, -strlen("_printable")) == "_printable")
			{
				$fieldNameTmp = substr($fieldName, 0, strlen($fieldName) - strlen("_printable"));
				$result = $rootActivity->GetVariable($fieldNameTmp);

				$rootActivity = $this->GetRootActivity();
				$documentId = $rootActivity->GetDocumentId();

				$documentService = $this->workflow->GetService("DocumentService");
				$result = $documentService->GetFieldValuePrintable($documentId, $fieldNameTmp, $rootActivity->arVariablesTypes[$fieldNameTmp]["Type"], $result, $rootActivity->arVariablesTypes[$fieldNameTmp]);

				if (is_array($result))
					$result = implode(", ", $result);
			}
			else
			{
				$result = $rootActivity->GetVariable($fieldName);
			}

			$return = true;
		}
		elseif ($objectName == "Workflow")
		{
			$result = $this->GetWorkflowInstanceId();
			$return = true;
		}
		elseif ($objectName == "User")
		{
			$result = 0;
			if ($GLOBALS["USER"]->IsAuthorized())
				$result = "user_".$GLOBALS["USER"]->GetID();

			$return = true;
		}
		elseif ($objectName == "System")
		{
			global $DB;

			$result = null;
			if ($fieldName == "Now")
				$result = date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")));
			elseif ($fieldName == "Date")
				$result = date($DB->DateFormatToPHP(CSite::GetDateFormat("SHORT")));
			if ($result !== null)
				$return = true;
		}
		else
		{
			$activity = $this->workflow->GetActivityByName($objectName);
			if ($activity)
			{
				// _printable is not supported because mapping between activity property types
				// and document property types is not supported
				$result = $activity->{$fieldName};
				$return = true;
			}
		}

		return $return;
	}

	private function ParseStringParameter($matches)
	{
		$objectName = $matches[1];
		$fieldName = $matches[2];

		$result = "";

		if ($this->GetRealParameterValue($objectName, $fieldName, $result))
		{
			if (is_array($result))
				$result = implode(", ", $result);
		}
		else
		{
			$result = "{=".$objectName.":".$fieldName."}";
		}

		return $result;
	}

	public function ParseValue($value)
	{
		list($t, $r) = $this->GetPropertyValueRecursive($value);
		return $r;
	}

	function __get($name)
	{
		if (array_key_exists($name, $this->arProperties))
		{
			list($t, $r) = $this->GetPropertyValueRecursive($this->arProperties[$name]);
			return $r;
		}
		else
		{
			return null;
			//throw new Exception(str_replace("#NAME#", htmlspecialcharsbx($name), GetMessage("BPCGACT_NO_PROPERTY")));
		}
	}

	function __set($name, $val)
	{
		if (array_key_exists($name, $this->arProperties))
			$this->arProperties[$name] = $val;
		//else
			//throw new Exception(str_replace("#NAME#", htmlspecialcharsbx($name), GetMessage("BPCGACT_NO_PROPERTY")));
	}

	public function IsPropertyExists($name)
	{
		return array_key_exists($name, $this->arProperties);
	}

	public function CollectNestedActivities()
	{
		return null;
	}

	/************************  CONSTRUCTORS  *****************************************************/

	public function __construct($name)
	{
		$this->name = $name;
	}

	/************************  DEBUG  ***********************************************************/

	public function ToString()
	{
		return $this->name.
			" [".get_class($this)."] (status=".
			CBPActivityExecutionStatus::Out($this->executionStatus).
			", result=".
			CBPActivityExecutionResult::Out($this->executionResult).
			", count(ClosedEvent)=".
			count($this->arStatusChangeHandlers[self::ClosedEvent]).
			")";
	}

	public function Dump($level = 3)
	{
		$result = str_repeat("	", $level).$this->ToString()."\n";

		if (is_subclass_of($this, "CBPCompositeActivity"))
		{
			foreach ($this->arActivities as $activity)
				$result .= $activity->Dump($level + 1);
		}

		return $result;
	}

	/************************  PROCESS  ***********************************************************/

	public function Initialize()
	{
	}

	public function Execute()
	{
		return CBPActivityExecutionStatus::Closed;
	}

	protected function ReInitialize()
	{
		$this->executionStatus = CBPActivityExecutionStatus::Initialized;
		$this->executionResult = CBPActivityExecutionResult::None;
	}

	public function Cancel()
	{
		return CBPActivityExecutionStatus::Closed;
	}

	public function HandleFault(Exception $exception)
	{
		return CBPActivityExecutionStatus::Closed;
	}

	/************************  LOAD / SAVE  *******************************************************/

	public function FixUpParentChildRelationship(CBPActivity $nestedActivity)
	{
		$nestedActivity->parent = $this;
	}

	public static function Load($stream)
	{
		if (strlen($stream) <= 0)
			throw new Exception("stream");

		$pos = strpos($stream, ";");
		$strUsedActivities = substr($stream, 0, $pos);
		$stream = substr($stream, $pos + 1);

		$runtime = CBPRuntime::GetRuntime();
		$arUsedActivities = explode(",", $strUsedActivities);

		foreach ($arUsedActivities as $activityCode)
			$runtime->IncludeActivityFile($activityCode);

		return unserialize($stream);
	}

	protected function GetACNames()
	{
		return array(substr(get_class($this), 3));
	}

	private static function SearchUsedActivities(CBPActivity $activity, &$arUsedActivities)
	{
		$arT = $activity->GetACNames();
		foreach ($arT as $t)
		{
			if (!in_array($t, $arUsedActivities))
				$arUsedActivities[] = $t;
		}

		if ($arNestedActivities = $activity->CollectNestedActivities())
		{
			foreach ($arNestedActivities as $nestedActivity)
				self::SearchUsedActivities($nestedActivity, $arUsedActivities);
		}
	}

	public function Save()
	{
		$arUsedActivities = array();
		self::SearchUsedActivities($this, $arUsedActivities);
		$strUsedActivities = implode(",", $arUsedActivities);
		return $strUsedActivities.";".serialize($this);
	}

	/************************  STATUS CHANGE HANDLERS  **********************************************/

	public function AddStatusChangeHandler($event, $eventHandler)
	{
		if (!is_array($this->arStatusChangeHandlers))
			$this->arStatusChangeHandlers = array();

		if (!array_key_exists($event, $this->arStatusChangeHandlers))
			$this->arStatusChangeHandlers[$event] = array();

		$this->arStatusChangeHandlers[$event][] = $eventHandler;
	}

	public function RemoveStatusChangeHandler($event, $eventHandler)
	{
		if (!is_array($this->arStatusChangeHandlers))
			$this->arStatusChangeHandlers = array();

		if (!array_key_exists($event, $this->arStatusChangeHandlers))
			$this->arStatusChangeHandlers[$event] = array();

		$index = array_search($eventHandler, $this->arStatusChangeHandlers[$event], true);

		if ($index !== false)
			unset($this->arStatusChangeHandlers[$event][$index]);
	}

	/************************  EVENTS  **********************************************************************/

	private function FireStatusChangedEvents($event, $arEventParameters = array())
	{
		if (array_key_exists($event, $this->arStatusChangeHandlers) && is_array($this->arStatusChangeHandlers[$event]))
		{
			foreach ($this->arStatusChangeHandlers[$event] as $eventHandler)
				call_user_func_array(array($eventHandler, "OnEvent"), array($this, $arEventParameters));
		}
	}

	public function SetStatus($newStatus, $arEventParameters = array())
	{
		$this->executionStatus = $newStatus;
		$this->FireStatusChangedEvents(self::StatusChangedEvent, $arEventParameters);

		switch ($newStatus)
		{
			case CBPActivityExecutionStatus::Executing:
				$this->FireStatusChangedEvents(self::ExecutingEvent, $arEventParameters);
				break;

			case CBPActivityExecutionStatus::Canceling:
				$this->FireStatusChangedEvents(self::CancelingEvent, $arEventParameters);
				break;

			case CBPActivityExecutionStatus::Closed:
				$this->FireStatusChangedEvents(self::ClosedEvent, $arEventParameters);
				break;

			case CBPActivityExecutionStatus::Faulting:
				$this->FireStatusChangedEvents(self::FaultingEvent, $arEventParameters);
				break;

			default:
				return;
		}
	}

	/************************  CREATE  *****************************************************************/

	public static function IncludeActivityFile($code)
	{
		$runtime = CBPRuntime::GetRuntime();
		return $runtime->IncludeActivityFile($code);
	}

	public static function CreateInstance($code, $data)
	{
		$code = preg_replace("[^a-zA-Z0-9]", "", $code);
		$classname = 'CBP'.$code;
		if (class_exists($classname))
			return new $classname($data);
		else
			return null;
	}

	public static function CallStaticMethod($code, $method, $arParameters = array())
	{
		$runtime = CBPRuntime::GetRuntime();
		if (!$runtime->IncludeActivityFile($code))
			return array(array("code" => "ActivityNotFound", "parameter" => $code, "message" => GetMessage("BPGA_ACTIVITY_NOT_FOUND")));

		$code = preg_replace("[^a-zA-Z0-9]", "", $code);
		$classname = 'CBP'.$code;

		return call_user_func_array(array($classname, $method), $arParameters);
	}

	public function InitializeFromArray($arParams)
	{
		if (is_array($arParams))
		{
			foreach ($arParams as $key => $value)
			{
				if (array_key_exists($key, $this->arProperties))
					$this->arProperties[$key] = $value;
			}
		}
	}

	/************************  MARK  ****************************************************************/

	public function MarkCanceled($arEventParameters = array())
	{
		if ($this->executionStatus != CBPActivityExecutionStatus::Closed)
		{
			if ($this->executionStatus != CBPActivityExecutionStatus::Canceling)
				throw new Exception("InvalidCancelActivityState");

			$this->executionResult = CBPActivityExecutionResult::Canceled;
			$this->MarkClosed($arEventParameters);
		}
	}

	public function MarkCompleted($arEventParameters = array())
	{
		$this->executionResult = CBPActivityExecutionResult::Succeeded;
		$this->MarkClosed($arEventParameters);
	}

	public function MarkFaulted($arEventParameters = array())
	{
		$this->executionResult = CBPActivityExecutionResult::Faulted;
		$this->MarkClosed($arEventParameters);
	}

	private function MarkClosed($arEventParameters = array())
	{
		switch ($this->executionStatus)
		{
			case CBPActivityExecutionStatus::Executing:
			case CBPActivityExecutionStatus::Canceling:
			case CBPActivityExecutionStatus::Faulting:
			{
				if (is_subclass_of($this, "CBPCompositeActivity"))
				{
					foreach ($this->arActivities as $activity)
					{
						if (($activity->executionStatus != CBPActivityExecutionStatus::Initialized) 
							&& ($activity->executionStatus != CBPActivityExecutionStatus::Closed))
						{
							throw new Exception("ActiveChildExist");
						}
					}
				}

				$trackingService = $this->workflow->GetService("TrackingService");
				$trackingService->Write($this->GetWorkflowInstanceId(), CBPTrackingType::CloseActivity, $this->name, $this->executionStatus, $this->executionResult, ($this->IsPropertyExists("Title") ? $this->Title : ""));

				$this->SetStatus(CBPActivityExecutionStatus::Closed, $arEventParameters);

				//if ($this->parent)
				//	$this->workflow->SetCurrentActivity($this->parent);

				return;
			}
		}

		throw new Exception("InvalidCloseActivityState");
	}

	protected function WriteToTrackingService($message = "", $modifiedBy = 0, $trackingType = -1)
	{
		$trackingService = $this->workflow->GetService("TrackingService");
		if ($trackingType < 0)
			$trackingType = CBPTrackingType::Custom;
		$trackingService->Write($this->GetWorkflowInstanceId(), $trackingType, $this->name, $this->executionStatus, $this->executionResult, ($this->IsPropertyExists("Title") ? $this->Title : ""), $message, $modifiedBy);
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		return array();
	}

	public static function ValidateChild($childActivity, $bFirstChild = false)
	{
		return array();
	}

	public static function &FindActivityInTemplate(&$arWorkflowTemplate, $activityName)
	{
		return CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
	}
}
?>