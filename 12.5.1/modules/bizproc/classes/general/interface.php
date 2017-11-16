<?
interface IBPEventActivity
{
	public function Subscribe(IBPActivityExternalEventListener $eventHandler);
	public function Unsubscribe(IBPActivityExternalEventListener $eventHandler);
}

interface IBPActivityEventListener
{
	public function OnEvent(CBPActivity $sender, $arEventParameters = array());
}

interface IBPActivityExternalEventListener
{
	public function OnExternalEvent($arEventParameters = array());
}

interface IBPRootActivity
{
	public function GetDocumentId();
	public function SetDocumentId($documentId);

	public function GetWorkflowStatus();
	public function SetWorkflowStatus($status);

	public function SetProperties($arProperties = array());

	public function SetVariables($arVariables = array());
	public function SetVariable($name, $value);
	public function GetVariable($name);
	public function IsVariableExists($name);

	public function SetCustomStatusMode();
}

interface IBPWorkflowDocument
{
	/**
	* ����� ���������� �������� (����) ��������� � ���� �������������� ������� ���� array(���_�������� => ��������, ...). ���������� ��� ��������, ������� ���������� ����� GetDocumentFields.
	*
	* @param string $documentId - ��� ���������.
	* @return array - ������ ������� ���������.
	*/
	static public function GetDocument($documentId);

	/**
	* ����� ���������� ������ ������� (�����), ������� ����� �������� ������� ����. ����� GetDocument ���������� �������� ������� ��� ��������� ���������.
	*
	* @param string $documentType - ��� ���������.
	* @return array - ������ ������� ���� array(���_�������� => array("NAME" => ��������_��������, "TYPE" => ���_��������), ...).
	*/
	static public function GetDocumentFields($documentType);

	/**
	* ����� ������� ����� �������� � ���������� ���������� (������).
	*
	* @param array $arFields - ������ �������� ������� ��������� � ���� array(���_�������� => ��������, ...). ���� ������� ������������� ����� �������, ������������ ������� GetDocumentFields.
	* @return int - ��� ���������� ���������.
	*/
	static public function CreateDocument($parentDocumentId, $arFields);

	/**
	* ����� �������� �������� (����) ���������� ��������� �� ��������� ��������.
	*
	* @param string $documentId - ��� ���������.
	* @param array $arFields - ������ ����� �������� ������� ��������� � ���� array(���_�������� => ��������, ...). ���� ������� ������������� ����� �������, ������������ ������� GetDocumentFields.
	*/
	static public function UpdateDocument($documentId, $arFields);

	/**
	* ����� ������� ��������� ��������.
	*
	* @param string $documentId - ��� ���������.
	*/
	static public function DeleteDocument($documentId);

	/**
	* ����� ��������� ��������. �� ���� ������ ��� ��������� � ��������� ����� �����.
	*
	* @param string $documentId - ��� ���������.
	*/
	static public function PublishDocument($documentId);

	/**
	* ����� ������� �������� � ����������. �� ���� ������ ��� ����������� � ��������� ����� �����.
	*
	* @param string $documentId - ��� ���������.
	*/
	static public function UnpublishDocument($documentId);

	/**
	* ����� ��������� ��������� �������� ��� ���������� �������� ������. �������������� �������� ����� ���������� ������ ��������� ������� �������.
	*
	* @param string $documentId - ��� ���������
	* @param string $workflowId - ��� �������� ������
	* @return bool - ���� ������� ������������� ��������, �� ������������ true, ����� - false.
	*/
	static public function LockDocument($documentId, $workflowId);

	/**
	* ����� ������������ ��������� ��������. ��� ������������� ���������� ����������� ������� ���� "��������_OnUnlockDocument", ������� �������� ���������� ���������� ��� ���������.
	*
	* @param string $documentId - ��� ���������
	* @param string $workflowId - ��� �������� ������
	* @return bool - ���� ������� �������������� ��������, �� ������������ true, ����� - false.
	*/
	static public function UnlockDocument($documentId, $workflowId);

	/**
	* ����� ���������, ������������ �� ��������� �������� ��� ���������� �������� ������. �.�. ���� ��� ������� �������� ������ �������� �� �������� ��� ������ ��-�� ����, ��� �� ������������ ������ ������� �������, �� ����� ������ ������� true, ����� - false.
	*
	* @param string $documentId - ��� ���������
	* @param string $workflowId - ��� �������� ������
	* @return bool
	*/
	static public function IsDocumentLocked($documentId, $workflowId);

	/**
	* ����� ��������� ����� �� ���������� �������� ��� �������� ����������. ����������� �������� 0 - �������� ������ �������� ������, 1 - ������ �������� ������, 2 - ����� �������� ��������, 3 - ����� �������� ��������.
	*
	* @param int $operation - ��������.
	* @param int $userId - ��� ������������, ��� �������� ����������� ����� �� ���������� ��������.
	* @param string $documentId - ��� ���������, � �������� ����������� ��������.
	* @param array $arParameters - ������������� ������ ��������������� ����������. ������������ ��� ����, ����� �� ������������ ������ �� ����������� ��������, ������� ��� �������� �� ������ ������ ������. ������������ �������� ����� ������� DocumentStates - ������ ��������� ������� ������� ������� ���������, WorkflowId - ��� �������� ������ (���� ��������� ��������� �������� �� ����� ������� ������). ������ ����� ���� �������� ������� ������������� �������.
	* @return bool
	*/
	static public function CanUserOperateDocument($operation, $userId, $documentId, $arParameters = array());

	/**
	* ����� ��������� ����� �� ���������� �������� ��� ����������� ��������� ����. ����������� �������� 4 - ����� �������� ������� ������� ������� ��� ������� ���� ���������.
	*
	* @param int $operation - ��������.
	* @param int $userId - ��� ������������, ��� �������� ����������� ����� �� ���������� ��������.
	* @param string $documentId - ��� ���� ���������, � �������� ����������� ��������.
	* @param array $arParameters - ������������� ������ ��������������� ����������. ������������ ��� ����, ����� �� ������������ ������ �� ����������� ��������, ������� ��� �������� �� ������ ������ ������. ������������ �������� ����� ������� DocumentStates - ������ ��������� ������� ������� ������� ���������, WorkflowId - ��� �������� ������ (���� ��������� ��������� �������� �� ����� ������� ������). ������ ����� ���� �������� ������� ������������� �������.
	* @return bool
	*/
	static public function CanUserOperateDocumentType($operation, $userId, $documentType, $arParameters = array());

	/**
	* ����� �� ���� ��������� ���������� ������ �� �������� ��������� � ���������������� �����.
	*
	* @param string $documentId - ��� ���������.
	* @return string - ������ �� �������� ��������� � ���������������� �����.
	*/
	static public function GetDocumentAdminPage($documentId);

	/**
	* ����� ���������� ������ ������������ ���������, ���������� ��� ���������� � ���������. �� ����� ������� �������� ����������������� ������� RecoverDocumentFromHistory.
	*
	* @param string $documentId - ��� ���������.
	* @return array - ������ ���������.
	*/
	static public function GetDocumentForHistory($documentId, $historyIndex);

	/**
	* ����� ��������������� ��������� �������� �� �������. ������ ��������� ������� RecoverDocumentFromHistory.
	*
	* @param string $documentId - ��� ���������.
	* @param array $arDocument - ������.
	*/
	static public function RecoverDocumentFromHistory($documentId, $arDocument);

	// array("read" => "��� ������", "write" => "��� ������")
	static public function GetAllowableOperations($documentType);
	// array("1" => "������", 2 => "�����", 3 => ..., "Author" => "�����")
	static public function GetAllowableUserGroups($documentType);
	static public function GetUsersFromUserGroup($group, $documentId);
}
?>