<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CBPRequestInformationActivity
	extends CBPActivity
	implements IBPEventActivity, IBPActivityExternalEventListener
{
	private $taskId = 0;
	private $isInEventActivityMode = false;

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"Title" => "",
			"Users" => null,
			"Name" => null,
			"Description" => null,
			"Parameters" => null,
			"OverdueDate" => null,
			"RequestedInformation" => null,
			"ResponcedInformation" => null,
			"Comments" => "",
			"TaskButtonMessage" => "",
			"CommentLabelMessage" => "",
			"ShowComment" => "Y",
			"StatusMessage" => "",
			"SetStatusMessage" => "Y",
			"InfoUser" => null
		);
	}

	public function Execute()
	{
		if ($this->isInEventActivityMode)
			return CBPActivityExecutionStatus::Closed;

		$this->Subscribe($this);

		$this->isInEventActivityMode = false;
		return CBPActivityExecutionStatus::Executing;
	}

	public function Subscribe(IBPActivityExternalEventListener $eventHandler)
	{
		if ($eventHandler == null)
			throw new Exception("eventHandler");

		$this->isInEventActivityMode = true;

		$arUsersTmp = $this->Users;
		if (!is_array($arUsersTmp))
			$arUsersTmp = array($arUsersTmp);

		$this->WriteToTrackingService(str_replace("#VAL#", "{=user:".implode("}, {=user:", $arUsersTmp)."}", GetMessage("BPRIA_ACT_TRACK1")));

		$rootActivity = $this->GetRootActivity();
		$documentId = $rootActivity->GetDocumentId();

		$arUsers = CBPHelper::ExtractUsers($arUsersTmp, $documentId, false);

		$arParameters = $this->Parameters;
		if (!is_array($arParameters))
			$arParameters = array($arParameters);

		$runtime = CBPRuntime::GetRuntime();
		$documentService = $runtime->GetService("DocumentService");

		$arParameters["DOCUMENT_ID"] = $documentId;
		$arParameters["DOCUMENT_URL"] = $documentService->GetDocumentAdminPage($documentId);
		$arParameters["DOCUMENT_TYPE"] = $this->GetDocumentType();
		$arParameters["FIELD_TYPES"] = $documentService->GetDocumentFieldTypes($arParameters["DOCUMENT_TYPE"]);
		$arParameters["REQUEST"] = array();
		$arParameters["TaskButtonMessage"] = $this->IsPropertyExists("TaskButtonMessage") ? $this->TaskButtonMessage : GetMessage("BPRIA_ACT_BUTTON1");
		if (strlen($arParameters["TaskButtonMessage"]) <= 0)
			$arParameters["TaskButtonMessage"] = GetMessage("BPRIA_ACT_BUTTON1");
		$arParameters["CommentLabelMessage"] = $this->IsPropertyExists("CommentLabelMessage") ? $this->CommentLabelMessage : GetMessage("BPRIA_ACT_COMMENT");
		if (strlen($arParameters["CommentLabelMessage"]) <= 0)
			$arParameters["CommentLabelMessage"] = GetMessage("BPRIA_ACT_COMMENT");
		$arParameters["ShowComment"] = $this->IsPropertyExists("ShowComment") ? $this->ShowComment : "Y";
		if ($arParameters["ShowComment"] != "Y" && $arParameters["ShowComment"] != "N")
			$arParameters["ShowComment"] = "Y";

		$requestedInformation = $this->RequestedInformation;
		if ($requestedInformation && is_array($requestedInformation) && count($requestedInformation) > 0)
		{
			foreach ($requestedInformation as $v)
				$arParameters["REQUEST"][] = $v;
		}

		$taskService = $this->workflow->GetService("TaskService");
		$this->taskId = $taskService->CreateTask(
			array(
				"USERS" => $arUsers,
				"WORKFLOW_ID" => $this->GetWorkflowInstanceId(),
				"ACTIVITY" => "RequestInformationActivity",
				"ACTIVITY_NAME" => $this->name,
				"OVERDUE_DATE" => $this->OverdueDate,
				"NAME" => $this->Name,
				"DESCRIPTION" => $this->Description,
				"PARAMETERS" => $arParameters,
			)
		);

		if (!$this->IsPropertyExists("SetStatusMessage") || $this->SetStatusMessage == "Y")
		{
			$message = ($this->IsPropertyExists("StatusMessage") && strlen($this->StatusMessage) > 0) ? $this->StatusMessage : GetMessage("BPRIA_ACT_INFO");
			$this->SetStatusTitle($message);
		}

		$this->workflow->AddEventHandler($this->name, $eventHandler);
	}

	public function Unsubscribe(IBPActivityExternalEventListener $eventHandler)
	{
		if ($eventHandler == null)
			throw new Exception("eventHandler");

		$taskService = $this->workflow->GetService("TaskService");
		$taskService->DeleteTask($this->taskId);

		$this->workflow->RemoveEventHandler($this->name, $eventHandler);

		$this->taskId = 0;
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

	public function Cancel()
	{
		if (!$this->isInEventActivityMode && $this->taskId > 0)
			$this->Unsubscribe($this);

		return CBPActivityExecutionStatus::Closed;
	}

	public function OnExternalEvent($arEventParameters = array())
	{
		if ($this->executionStatus == CBPActivityExecutionStatus::Closed)
			return;

		if (!array_key_exists("USER_ID", $arEventParameters) || intval($arEventParameters["USER_ID"]) <= 0)
			return;

		$rootActivity = $this->GetRootActivity();
		$documentId = $rootActivity->GetDocumentId();

		$runtime = CBPRuntime::GetRuntime();
		$documentService = $runtime->GetService("DocumentService");

		$arUsers = CBPHelper::ExtractUsers($this->Users, $this->GetDocumentId(), false);

		$arEventParameters["USER_ID"] = intval($arEventParameters["USER_ID"]);
		if (!in_array($arEventParameters["USER_ID"], $arUsers))
			return;

		$taskService = $this->workflow->GetService("TaskService");
		$taskService->MarkCompleted($this->taskId, $arEventParameters["USER_ID"]);

		$this->Comments = $arEventParameters["COMMENT"];

		if ($this->IsPropertyExists("InfoUser"))
			$this->InfoUser = "user_".$arEventParameters["USER_ID"];

		$this->WriteToTrackingService(
			str_replace(
				array("#PERSON#", "#COMMENT#"),
				array("{=user:user_".$arEventParameters["USER_ID"]."}", (strlen($arEventParameters["COMMENT"]) > 0 ? ": ".$arEventParameters["COMMENT"] : "")),
				GetMessage("BPRIA_ACT_APPROVE_TRACK")
			),
			$arEventParameters["USER_ID"]
		);

		$this->ResponcedInformation = $arEventParameters["RESPONCE"];
		//$rootActivity->SetVariablesTypes($arEventParameters["RESPONCE_TYPES"]);
		$rootActivity->SetVariables($arEventParameters["RESPONCE"]);

		$this->Unsubscribe($this);
		//$this->SetStatusTitle();

		$this->workflow->CloseActivity($this);
	}

	protected function OnEvent(CBPActivity $sender)
	{
		$sender->RemoveStatusChangeHandler(self::ClosedEvent, $this);
		$this->workflow->CloseActivity($this);
	}

	public static function ShowTaskForm($arTask, $userId, $userName = "", $arRequest = null)
	{
		$form = '';

		$runtime = CBPRuntime::GetRuntime();
		$runtime->StartRuntime();
		$documentService = $runtime->GetService("DocumentService");

		if ($arTask["PARAMETERS"] && is_array($arTask["PARAMETERS"]) && count($arTask["PARAMETERS"]) > 0
			&& $arTask["PARAMETERS"]["REQUEST"] && is_array($arTask["PARAMETERS"]["REQUEST"]) && count($arTask["PARAMETERS"]["REQUEST"]) > 0)
		{
			foreach ($arTask["PARAMETERS"]["REQUEST"] as $parameter)
			{
				if (strlen($parameter["Name"]) <= 0)
					continue;

				$form .=
					'<tr><td valign="top" width="40%" align="right" class="bizproc-field-name">'.($parameter["Required"] ? '<span style="color:#FF0000;">*</span> ' : '').''.$parameter["Title"].':</td>'.
					'<td valign="top" width="60%" class="bizproc-field-value">';

				if ($arRequest === null)
					$realValue = $parameter["Default"];
				else
					$realValue = $arRequest[$parameter["Name"]];

				$form .= $documentService->GetFieldInputControl(
					$arTask["PARAMETERS"]["DOCUMENT_TYPE"],
					$parameter,
					array("task_form1", $parameter["Name"]),
					$realValue,
					false,
					true
				);

				$form .= '</td></tr>';
			}
		}

		if (!array_key_exists("ShowComment", $arTask["PARAMETERS"]) || ($arTask["PARAMETERS"]["ShowComment"] != "N"))
		{
			$form .=
				'<tr><td valign="top" width="40%" align="right" class="bizproc-field-name">'.(strlen($arTask["PARAMETERS"]["CommentLabelMessage"]) > 0 ? $arTask["PARAMETERS"]["CommentLabelMessage"] : GetMessage("BPRIA_ACT_COMMENT")).':</td>'.
				'<td valign="top" width="60%" class="bizproc-field-value">'.
				'<textarea rows="3" cols="50" name="task_comment"></textarea>'.
				'</td></tr>';
		}

		$buttons =
			'<input type="submit" name="approve" value="'.(strlen($arTask["PARAMETERS"]["TaskButtonMessage"]) > 0 ? $arTask["PARAMETERS"]["TaskButtonMessage"] : GetMessage("BPRIA_ACT_BUTTON1")).'"/>';

		return array($form, $buttons);
	}

	public static function PostTaskForm($arTask, $userId, $arRequest, &$arErrors, $userName = "")
	{
		$arErrors = array();

		$runtime = CBPRuntime::GetRuntime();
		$runtime->StartRuntime();
		$documentService = $runtime->GetService("DocumentService");

		try
		{
			$userId = intval($userId);
			if ($userId <= 0)
				throw new CBPArgumentNullException("userId");

			$arEventParameters = array(
				"USER_ID" => $userId,
				"USER_NAME" => $userName,
				"COMMENT" => $arRequest["task_comment"],
				"RESPONCE" => array(),
				//"RESPONCE_TYPES" => array(),
			);

			if ($arTask["PARAMETERS"] && is_array($arTask["PARAMETERS"]) && count($arTask["PARAMETERS"]) > 0
				&& $arTask["PARAMETERS"]["REQUEST"] && is_array($arTask["PARAMETERS"]["REQUEST"]) && count($arTask["PARAMETERS"]["REQUEST"]) > 0)
			{
				$arRequest = $_REQUEST;

				foreach ($_FILES as $k => $v)
				{
					if (array_key_exists("name", $v))
					{
						if (is_array($v["name"]))
						{
							$ks = array_keys($v["name"]);
							for ($i = 0, $cnt = count($ks); $i < $cnt; $i++)
							{
								$ar = array();
								foreach ($v as $k1 => $v1)
									$ar[$k1] = $v1[$ks[$i]];

								$arRequest[$k][] = $ar;
							}
						}
						else
						{
							$arRequest[$k] = $v;
						}
					}
				}

				foreach ($arTask["PARAMETERS"]["REQUEST"] as $parameter)
				{
					$arErrorsTmp = array();

					$arEventParameters["RESPONCE"][$parameter["Name"]] = $documentService->GetFieldInputValue(
						$arTask["PARAMETERS"]["DOCUMENT_TYPE"],
						$parameter,
						$parameter["Name"],
						$arRequest,
						$arErrorsTmp
					);

					if (count($arErrorsTmp) > 0)
					{
						$m = "";
						foreach ($arErrorsTmp as $e)
							$m .= $e["message"]."<br />";
						throw new CBPArgumentException($m);
					}

					if ($parameter["Required"] 
						&& (is_array($arEventParameters["RESPONCE"][$parameter["Name"]]) && count($arEventParameters["RESPONCE"][$parameter["Name"]]) <= 0 || !is_array($arEventParameters["RESPONCE"][$parameter["Name"]]) && $arEventParameters["RESPONCE"][$parameter["Name"]] === null)
						)
						throw new CBPArgumentNullException($parameter["Name"], str_replace("#PARAM#", htmlspecialcharsbx($parameter["Title"]), GetMessage("BPRIA_ARGUMENT_NULL")));

					//$arEventParameters["RESPONCE_TYPES"][$parameter["Name"]] = array("Type" => $parameter["Type"]);
				}
			}

			CBPRuntime::SendExternalEvent($arTask["WORKFLOW_ID"], $arTask["ACTIVITY_NAME"], $arEventParameters);

			return true;
		}
		catch (Exception $e)
		{
			$arErrors[] = array(
				"code" => $e->getCode(),
				"message" => $e->getMessage(),
				"file" => $e->getFile()." [".$e->getLine()."]",
			);
		}

		return false;
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = array();

		if (!array_key_exists("Users", $arTestProperties))
		{
			$bUsersFieldEmpty = true;
		}
		else
		{
			if (!is_array($arTestProperties["Users"]))
				$arTestProperties["Users"] = array($arTestProperties["Users"]);

			$bUsersFieldEmpty = true;
			foreach ($arTestProperties["Users"] as $userId)
			{
				if (!is_array($userId) && (strlen(trim($userId)) > 0) || is_array($userId) && (count($userId) > 0))
				{
					$bUsersFieldEmpty = false;
					break;
				}
			}
		}

		if ($bUsersFieldEmpty)
			$arErrors[] = array("code" => "NotExist", "parameter" => "Users", "message" => GetMessage("BPRIA_ACT_PROP_EMPTY1"));

		if (!array_key_exists("Name", $arTestProperties) || strlen($arTestProperties["Name"]) <= 0)
			$arErrors[] = array("code" => "NotExist", "parameter" => "Name", "message" => GetMessage("BPRIA_ACT_PROP_EMPTY4"));

		if (!array_key_exists("RequestedInformation", $arTestProperties) || !is_array($arTestProperties["RequestedInformation"]) || count($arTestProperties["RequestedInformation"]) <= 0)
			$arErrors[] = array("code" => "NotExist", "parameter" => "RequestedInformation", "message" => GetMessage("BPRIA_ACT_PROP_EMPTY2"));

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "", $popupWindow = null)
	{
		$runtime = CBPRuntime::GetRuntime();
		$documentService = $runtime->GetService("DocumentService");

		$arMap = array(
			"Users" => "requested_users",
			"OverdueDate" => "requested_overdue_date",
			"Name" => "requested_name",
			"Description" => "requested_description",
			"Parameters" => "requested_parameters",
			"RequestedInformation" => "requested_information",
			"TaskButtonMessage" => "task_button_message",
			"CommentLabelMessage" => "comment_label_message",
			"ShowComment" => "show_comment",
			"StatusMessage" => "status_message",
			"SetStatusMessage" => "set_status_message",
		);

		if (!is_array($arWorkflowParameters))
			$arWorkflowParameters = array();
		if (!is_array($arWorkflowVariables))
			$arWorkflowVariables = array();

		if (!is_array($arCurrentValues))
		{
			$arCurrentValues = array();
			$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
			if (is_array($arCurrentActivity["Properties"]))
			{
				foreach ($arMap as $k => $v)
				{
					if (array_key_exists($k, $arCurrentActivity["Properties"]))
					{
						if ($k == "Users")
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

		$arFieldTypes = $documentService->GetDocumentFieldTypes($documentType);
		$arDocumentFields = $documentService->GetDocumentFields($documentType);

		$ar = array();
		$j = -1;
		if (array_key_exists("requested_information", $arCurrentValues) && is_array($arCurrentValues["requested_information"]))
		{
			for ($i = 0, $cnt = count($arCurrentValues["requested_information"]) + 1; $i < $cnt; $i++)
			{
				if (strlen($arCurrentValues["requested_information"][$i]["Name"]) <= 0)
					continue;

				$j++;
				$ar[$j] = $arCurrentValues["requested_information"][$i];
				$ar[$j]["Required"] = ($ar[$j]["Required"] ? "Y" : "N");
				$ar[$j]["Multiple"] = ($ar[$j]["Multiple"] ? "Y" : "N");
			}
		}

		$arCurrentValues["requested_information"] = $ar;
		if (strlen($arCurrentValues['comment_label_message']) <= 0)
			$arCurrentValues['comment_label_message'] = GetMessage("BPRIA_ACT_COMMENT");
		if (strlen($arCurrentValues['task_button_message']) <= 0)
			$arCurrentValues['task_button_message'] = GetMessage("BPRIA_ACT_BUTTON1");
		if (strlen($arCurrentValues['status_message']) <= 0)
			$arCurrentValues['status_message'] = GetMessage("BPRIA_ACT_INFO");

		$javascriptFunctions = $documentService->GetJSFunctionsForFields($documentType, "objFields", $arDocumentFields, $arFieldTypes);

		return $runtime->ExecuteResourceFile(
			__FILE__,
			"properties_dialog.php",
			array(
				"arCurrentValues" => $arCurrentValues,
				"arDocumentFields" => $arDocumentFields,
				"arFieldTypes" => $arFieldTypes,
				"javascriptFunctions" => $javascriptFunctions,
				"formName" => $formName,
				"popupWindow" => &$popupWindow,
			)
		);
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		$arErrors = array();

		$runtime = CBPRuntime::GetRuntime();

		$arMap = array(
			"requested_users" => "Users",
			"requested_overdue_date" => "OverdueDate",
			"requested_name" => "Name",
			"requested_description" => "Description",
			"requested_parameters" => "Parameters",
			"requested_information" => "RequestedInformation",
			"task_button_message" => "TaskButtonMessage",
			"comment_label_message" => "CommentLabelMessage",
			"show_comment" => "ShowComment",
			"status_message" => "StatusMessage",
			"set_status_message" => "SetStatusMessage",
		);

		$arProperties = array();
		foreach ($arMap as $key => $value)
		{
			if ($key == "requested_users")
				continue;
			$arProperties[$value] = $arCurrentValues[$key];
		}

		$arProperties["Users"] = CBPHelper::UsersStringToArray($arCurrentValues["requested_users"], $documentType, $arErrors);
		if (count($arErrors) > 0)
			return false;

		$ar = array();
		$j = -1;

		if (array_key_exists("RequestedInformation", $arProperties) && is_array($arProperties["RequestedInformation"]))
		{
			foreach ($arProperties["RequestedInformation"] as $arRI)
			{
				if (strlen($arRI["Name"]) <= 0)
					continue;

				$j++;
				$ar[$j] = $arRI;
				$ar[$j]["Required"] = ($arRI["Required"] == "Y");
				$ar[$j]["Multiple"] = ($arRI["Multiple"] == "Y");
			}
		}

		$arProperties["RequestedInformation"] = $ar;

		$arErrors = self::ValidateProperties($arProperties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($arErrors) > 0)
			return false;

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $arProperties;

		if (is_array($arProperties["RequestedInformation"]))
		{
			foreach ($arProperties["RequestedInformation"] as $v)
			{
				$arWorkflowVariables[$v["Name"]] = $v;
				$arWorkflowVariables[$v["Name"]]["Name"] = $v["Title"];
			}
		}

		return true;
	}
}
?>