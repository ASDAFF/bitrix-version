<?
/**
* Workflow runtime.
*/
class CBPRuntime
{
	private $isStarted = false;
	private static $instance;

	private $arServices = array(
		"SchedulerService" => null,
		"StateService" => null,
		"TrackingService" => null,
		"TaskService" => null,
		"HistoryService" => null,
		"DocumentService" => null,
	);
	private $arWorkflows = array();

	private $arLoadedActivities = array();

	private $arActivityFolders = array();

	/*********************  SINGLETON PATTERN  **************************************************/

	/**
	* Private constructor prevents from instantiating this class. Singleton pattern.
	* 
	*/
	private function __construct()
	{
		$this->isStarted = false;
		$this->arWorkflows = array();
		$this->arServices = array(
			"SchedulerService" => null,
			"StateService" => null,
			"TrackingService" => null,
			"TaskService" => null,
			"HistoryService" => null,
			"DocumentService" => null,
		);
		$this->arLoadedActivities = array();
		$this->arActivityFolders = array(
			$_SERVER["DOCUMENT_ROOT"].BX_ROOT."/activities/custom",
			$_SERVER["DOCUMENT_ROOT"].BX_ROOT."/activities/bitrix",
			$_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/bizproc/activities",
		);
	}

	/**
	* Static method returns runtime object. Singleton pattern.
	* 
	* @return CBPRuntime
	*/
	public static function GetRuntime() 
	{
		if (!isset(self::$instance))
		{
			$c = __CLASS__;
			self::$instance = new $c;
		}

		return self::$instance;
	}

	public function __clone()
	{
		trigger_error('Clone in not allowed.', E_USER_ERROR);
	}

	/*********************  START / STOP RUNTIME  **************************************************/

	/**
	* Public method starts runtime
	* 
	*/
	public function StartRuntime()
	{
		if ($this->isStarted)
			return;

		if ($this->arServices["SchedulerService"] == null)
			$this->arServices["SchedulerService"] = new CBPSchedulerService();
		if ($this->arServices["StateService"] == null)
			$this->arServices["StateService"] = new CBPStateService();
		if ($this->arServices["TrackingService"] == null)
			$this->arServices["TrackingService"] = new CBPTrackingService();
		if ($this->arServices["TaskService"] == null)
			$this->arServices["TaskService"] = new CBPTaskService();
		if ($this->arServices["HistoryService"] == null)
			$this->arServices["HistoryService"] = new CBPHistoryService();
		if ($this->arServices["DocumentService"] == null)
			$this->arServices["DocumentService"] = new CBPDocumentService();

		foreach ($this->arServices as $serviceId => $service)
			$service->Start($this);

		$this->isStarted = true;
	}

	/**
	* Public method stops runtime
	* 
	*/
	public function StopRuntime()
	{
		if (!$this->isStarted)
			return;

		foreach ($this->arWorkflows as $key => $workflow)
			$workflow->OnRuntimeStopped();

		foreach ($this->arServices as $serviceId => $service)
			$service->Stop();

		$this->isStarted = false;
	}

	/*******************  PROCESS WORKFLOWS  *********************************************************/

	/**
	* Creates new workflow instance from the specified template.
	* 
	* @param int $workflowTemplateId - ID of the workflow template
	* @param string $documentId - ID of the document
	* @param mixed $workflowParameters - Optional parameters of the created workflow instance
	* @return CBPWorkflow
	*/
	public function CreateWorkflow($workflowTemplateId, $documentId, $workflowParameters = array())
	{
		$workflowTemplateId = intval($workflowTemplateId);
		if ($workflowTemplateId <= 0)
			throw new Exception("workflowTemplateId");

		$arDocumentId = CBPHelper::ParseDocumentId($documentId);

		if (!$this->isStarted)
			$this->StartRuntime();

		$workflowId = uniqid("", true);

		$workflow = new CBPWorkflow($workflowId, $this);

		$loader = CBPWorkflowTemplateLoader::GetLoader();

		list($rootActivity, $workflowVariablesTypes, $workflowParametersTypes) = $loader->LoadWorkflow($workflowTemplateId);

		if ($rootActivity == null)
			throw new Exception("EmptyRootActivity");
		//if (!is_a($rootActivity, "IBPRootActivity"))
		//	throw new Exception("RootActivityIsNotAIBPRootActivity");

		$events = GetModuleEvents("bizproc", "OnCreateWorkflow");
		while ($arEvent = $events->Fetch())
			ExecuteModuleEventEx($arEvent, array($workflowTemplateId, $documentId, &$workflowParameters));

		$workflow->Initialize($rootActivity, $arDocumentId, $workflowParameters, $workflowVariablesTypes, $workflowParametersTypes);

		$starterUserId = 0;
		if (array_key_exists("TargetUser", $workflowParameters))
			$starterUserId = intval(substr($workflowParameters["TargetUser"], strlen("user_")));

		$this->arServices["StateService"]->AddWorkflow($workflowId, $workflowTemplateId, $arDocumentId, $starterUserId);

		$this->arWorkflows[$workflowId] = $workflow;
		return $workflow;
	}

	/**
	* Returns existing workflow instance by its ID
	* 
	* @param mixed $instanceId - ID of the workflow instance
	* @return CBPWorkflow
	*/
	public function GetWorkflow($workflowId)
	{
		if (strlen($workflowId) <= 0)
			throw new Exception("workflowId");

		if (!$this->isStarted)
			$this->StartRuntime();

		if (array_key_exists($workflowId, $this->arWorkflows))
			return $this->arWorkflows[$workflowId];

		$workflow = new CBPWorkflow($workflowId, $this);

		$persister = CBPWorkflowPersister::GetPersister();
		$rootActivity = $persister->LoadWorkflow($workflowId);
		if ($rootActivity == null)
			throw new Exception("Empty root activity");

		$workflow->Reload($rootActivity);

		$this->arWorkflows[$workflowId] = $workflow;
		return $workflow;
	}

	/*******************  SERVICES  *********************************************************/

	/**
	* Returns service instance by its code.
	* 
	* @param mixed $name - Service code.
	* @return mixed - Service instance or null if service is not found.
	*/
	public function GetService($name)
	{
		if (array_key_exists($name, $this->arServices))
			return $this->arServices[$name];

		return null;
	}

	/**
	* Adds new service to runtime. Runtime should be stopped.
	* 
	* @param string $name - Service code.
	* @param CBPRuntimeService $service - Service object.
	*/
	public function AddService($name, CBPRuntimeService $service)
	{
		if ($this->isStarted)
			throw new Exception("Runtime is started");

		$name = trim($name);
		if (strlen($name) <= 0)
			throw new Exception("Service code is empty");
		if (!$service)
			throw new Exception("Service is null");

		if (array_key_exists($name, $this->arServices))
			throw new Exception("Service is already exists");

		$this->arServices[$name] = $service;
	}

	/*******************  EVENTS  ******************************************************************/

	/**
	* Static method transfer event to the specified workflow instance.
	* 
	* @param mixed $workflowId - ID of the workflow instance.
	* @param mixed $eventName - Event name.
	* @param mixed $arEventParameters - Event parameters.
	*/
	public static function SendExternalEvent($workflowId, $eventName, $arEventParameters = array())
	{
		$runtime = CBPRuntime::GetRuntime();
		$workflow = $runtime->GetWorkflow($workflowId);
		if ($workflow)
			$workflow->SendExternalEvent($eventName, $arEventParameters);
	}

	/*******************  UTILITIES  ***************************************************************/

	/**
	* Includes activity file by activity code.
	* 
	* @param string $code - Activity code.
	*/
	public function IncludeActivityFile($code)
	{
		if (in_array($code, $this->arLoadedActivities))
			return true;

		$code = preg_replace("[^a-zA-Z0-9]", "", $code);
		if (strlen($code) <= 0)
			return false;

		$code = strtolower($code);
		if (substr($code, 0, 3) == "cbp")
			$code = substr($code, 3);
		if (strlen($code) <= 0)
			return false;
		if (in_array($code, $this->arLoadedActivities))
			return true;

		$filePath = "";
		$fileDir = "";
		foreach ($this->arActivityFolders as $folder)
		{
			if (file_exists($folder."/".$code."/".$code.".php") && is_file($folder."/".$code."/".$code.".php"))
			{
				$filePath = $folder."/".$code."/".$code.".php";
				$fileDir = $folder."/".$code;
				break;
			}
		}

		if (strlen($filePath) > 0)
		{
			$this->LoadActivityLocalization($fileDir, $code.".php");
			include_once($filePath);
			$this->arLoadedActivities[] = $code;
			return true;
		}

		return false;
	}

	public function GetActivityDescription($code)
	{
		$code = preg_replace("[^a-zA-Z0-9]", "", $code);
		if (strlen($code) <= 0)
			return null;

		$code = strtolower($code);
		if (substr($code, 0, 3) == "cbp")
			$code = substr($code, 3);
		if (strlen($code) <= 0)
			return null;

		$filePath = "";
		$fileDir = "";
		foreach ($this->arActivityFolders as $folder)
		{
			if (file_exists($folder."/".$code."/".$code.".php") && is_file($folder."/".$code."/".$code.".php"))
			{
				$filePath = $folder."/".$code."/.description.php";
				$fileDir = $folder."/".$code;
				break;
			}
		}

		if (strlen($filePath) > 0)
		{
			$arActivityDescription = array();
			if (file_exists($filePath) && is_file($filePath))
			{
				$this->LoadActivityLocalization($fileDir, ".description.php");
				include($filePath);
			}
			$arActivityDescription["PATH_TO_ACTIVITY"] = $fileDir;

			return $arActivityDescription;
		}

		return null;
	}

	private function LoadActivityLocalization($path, $file, $lang = false)
	{
		global $MESS;

		if ($lang === false)
			$lang = LANGUAGE_ID;

		$p = $path."/lang/".$lang."/".$file;
		$pe = $path."/lang/en/".$file;

		if (file_exists($p) && is_file($p))
			include($p);
		elseif (file_exists($pe) && is_file($pe))
			include($pe);
	}

	public function GetResourceFilePath($activityPath, $filePath)
	{
		$path = str_replace("\\", "/", $activityPath);
		$path = substr($path, 0, strrpos($path, "/") + 1);

		$filePath = str_replace("\\", "/", $filePath);
		$filePath = ltrim($filePath, "/");

		if (file_exists($path.$filePath) && is_file($path.$filePath))
			return array($path.$filePath, $path);
		else
			return null;
	}

	public function ExecuteResourceFile($activityPath, $filePath, $arParameters = array())
	{
		$result = null;
		$path = $this->GetResourceFilePath($activityPath, $filePath);
		if ($path != null)
		{
			ob_start();

			foreach ($arParameters as $key => $value)
				${$key} = $value;

			$this->LoadActivityLocalization($path[1], $filePath);
			include($path[0]);
			$result = ob_get_contents();
			ob_end_clean();
		}
		return $result;
	}

	public function SearchActivitiesByType($type)
	{
		$type = strtolower(trim($type));
		if (strlen($type) <= 0)
			return false;

		$arProcessedDirs = array();
		foreach ($this->arActivityFolders as $folder)
		{
			if ($handle = @opendir($folder))
			{
				while (false !== ($dir = readdir($handle)))
				{
					if ($dir == "." || $dir == "..")
						continue;
					if (!is_dir($folder."/".$dir))
						continue;
					if (array_key_exists($dir, $arProcessedDirs))
						continue;
					if (!file_exists($folder."/".$dir."/.description.php"))
						continue;

					$arActivityDescription = array();
					$this->LoadActivityLocalization($folder."/".$dir, ".description.php");
					include($folder."/".$dir."/.description.php");
					if (strtolower($arActivityDescription["TYPE"]) == $type)
					{
						$arProcessedDirs[$dir] = $arActivityDescription;
						$arProcessedDirs[$dir]["PATH_TO_ACTIVITY"] = $folder."/".$dir;
					}
				}
				closedir($handle);
			}
		}

		return $arProcessedDirs;
	}

//	public function GetAvailableStateEvents($workflowId, $workflowTemplateId)
//	{
//		$workflowId = trim($workflowId);

//		if (strlen($workflowId) > 0)
//		{
//			$workflow = $this->GetWorkflow($workflowId);
//			$arResult = $workflow->GetAvailableStateEvents();
//		}
//		else
//		{
//			$loader = CBPWorkflowTemplateLoader::GetLoader();
//			$arResult = $loader->GetAvailableStateEvents($workflowTemplateId);
//		}

//		return $arResult;
//	}
}
?>