<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$runtime = CBPRuntime::GetRuntime();
$runtime->IncludeActivityFile("SequenceActivity");

class CBPSequentialWorkflowActivity
	extends CBPSequenceActivity
	implements IBPRootActivity
{
	private $documentId = array();
	protected $documentType = array();

	private $workflowStatus = CBPWorkflowStatus::Created;

	private $customStatusMode = false;

	protected $arVariables = array();
	protected $arVariablesTypes = array();

	protected $arFieldTypes = array();

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array("Title" => "", "Permission" => array());
	}

	public function GetDocumentId()
	{
		return $this->documentId;
	}

	public function SetDocumentId($documentId)
	{
		$this->documentId = $documentId;
	}

	public function GetWorkflowStatus()
	{
		return $this->workflowStatus;
	}

	public function SetWorkflowStatus($status)
	{
		$this->workflowStatus = $status;
		if ($status == CBPWorkflowStatus::Completed)
		{
			$this->ClearVariables();
			$this->ClearProperties();
		}
	}

	public function SetCustomStatusMode()
	{
		$this->customStatusMode = true;
	}

	public function ToString()
	{
		return $this->name.
			" [".get_class($this)."] (status=".
			CBPActivityExecutionStatus::Out($this->executionStatus).
			", result=".
			CBPActivityExecutionResult::Out($this->executionResult).
			", wfStatus=".
			CBPWorkflowStatus::Out($this->workflowStatus).
			", count(arEventsMap)=".
			count($this->arEventsMap).
			")";
	}

	public function Execute()
	{
		$stateService = $this->workflow->GetService("StateService");
		$stateService->SetState(
			$this->GetWorkflowInstanceId(),
			array(
				"STATE" => "InProgress",
				"TITLE" => GetMessage("BPSWA_IN_PROGRESS"),
				"PARAMETERS" => array()
			),
			$this->Permission
		);

		return parent::Execute();
	}

	protected function OnSequenceComplete()
	{
		parent::OnSequenceComplete();

		if (!$this->customStatusMode)
		{
			$stateService = $this->workflow->GetService("StateService");
			$stateService->SetState(
				$this->GetWorkflowInstanceId(),
				array(
					"STATE" => "Completed",
					"TITLE" => GetMessage("BPSWA_COMPLETED"),
					"PARAMETERS" => array()
				),
				false
			);
		}
	}
}
?>
