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
	* Метод возвращает свойства (поля) документа в виде ассоциативного массива вида array(код_свойства => значение, ...). Определены все свойства, которые возвращает метод GetDocumentFields.
	*
	* @param string $documentId - код документа.
	* @return array - массив свойств документа.
	*/
	static public function GetDocument($documentId);

	/**
	* Метод возвращает массив свойств (полей), которые имеет документ данного типа. Метод GetDocument возвращает значения свойств для заданного документа.
	*
	* @param string $documentType - тип документа.
	* @return array - массив свойств вида array(код_свойства => array("NAME" => название_свойства, "TYPE" => тип_свойства), ...).
	*/
	static public function GetDocumentFields($documentType);

	/**
	* Метод создает новый документ с указанными свойствами (полями).
	*
	* @param array $arFields - массив значений свойств документа в виде array(код_свойства => значение, ...). Коды свойств соответствуют кодам свойств, возвращаемым методом GetDocumentFields.
	* @return int - код созданного документа.
	*/
	static public function CreateDocument($parentDocumentId, $arFields);

	/**
	* Метод изменяет свойства (поля) указанного документа на указанные значения.
	*
	* @param string $documentId - код документа.
	* @param array $arFields - массив новых значений свойств документа в виде array(код_свойства => значение, ...). Коды свойств соответствуют кодам свойств, возвращаемым методом GetDocumentFields.
	*/
	static public function UpdateDocument($documentId, $arFields);

	/**
	* Метод удаляет указанный документ.
	*
	* @param string $documentId - код документа.
	*/
	static public function DeleteDocument($documentId);

	/**
	* Метод публикует документ. То есть делает его доступным в публичной части сайта.
	*
	* @param string $documentId - код документа.
	*/
	static public function PublishDocument($documentId);

	/**
	* Метод снимает документ с публикации. То есть делает его недоступным в публичной части сайта.
	*
	* @param string $documentId - код документа.
	*/
	static public function UnpublishDocument($documentId);

	/**
	* Метод блокирует указанный документ для указанного рабочего потока. Заблокированый документ может изменяться только указанным рабочим потоком.
	*
	* @param string $documentId - код документа
	* @param string $workflowId - код рабочего потока
	* @return bool - если удалось заблокировать документ, то возвращается true, иначе - false.
	*/
	static public function LockDocument($documentId, $workflowId);

	/**
	* Метод разблокирует указанный документ. При разблокировке вызываются обработчики события вида "Сущность_OnUnlockDocument", которым входящим параметром передается код документа.
	*
	* @param string $documentId - код документа
	* @param string $workflowId - код рабочего потока
	* @return bool - если удалось разблокировать документ, то возвращается true, иначе - false.
	*/
	static public function UnlockDocument($documentId, $workflowId);

	/**
	* Метод проверяет, заблокирован ли указанный документ для указанного рабочего потока. Т.е. если для данного рабочего потока документ не доступен для записи из-за того, что он заблокирован другим рабочим потоком, то метод должен вернуть true, иначе - false.
	*
	* @param string $documentId - код документа
	* @param string $workflowId - код рабочего потока
	* @return bool
	*/
	static public function IsDocumentLocked($documentId, $workflowId);

	/**
	* Метод проверяет права на выполнение операций над заданным документом. Проверяются операции 0 - просмотр данных рабочего потока, 1 - запуск рабочего потока, 2 - право изменять документ, 3 - право смотреть документ.
	*
	* @param int $operation - операция.
	* @param int $userId - код пользователя, для которого проверяется право на выполнение операции.
	* @param string $documentId - код документа, к которому применяется операция.
	* @param array $arParameters - ассициативный массив вспомогательных параметров. Используется для того, чтобы не рассчитывать заново те вычисляемые значения, которые уже известны на момент вызова метода. Стандартными являются ключи массива DocumentStates - массив состояний рабочих потоков данного документа, WorkflowId - код рабочего потока (если требуется проверить операцию на одном рабочем потоке). Массив может быть дополнен другими произвольными ключами.
	* @return bool
	*/
	static public function CanUserOperateDocument($operation, $userId, $documentId, $arParameters = array());

	/**
	* Метод проверяет права на выполнение операций над документами заданного типа. Проверяются операции 4 - право изменять шаблоны рабочий потоков для данного типа документа.
	*
	* @param int $operation - операция.
	* @param int $userId - код пользователя, для которого проверяется право на выполнение операции.
	* @param string $documentId - код типа документа, к которому применяется операция.
	* @param array $arParameters - ассициативный массив вспомогательных параметров. Используется для того, чтобы не рассчитывать заново те вычисляемые значения, которые уже известны на момент вызова метода. Стандартными являются ключи массива DocumentStates - массив состояний рабочих потоков данного документа, WorkflowId - код рабочего потока (если требуется проверить операцию на одном рабочем потоке). Массив может быть дополнен другими произвольными ключами.
	* @return bool
	*/
	static public function CanUserOperateDocumentType($operation, $userId, $documentType, $arParameters = array());

	/**
	* Метод по коду документа возвращает ссылку на страницу документа в административной части.
	*
	* @param string $documentId - код документа.
	* @return string - ссылка на страницу документа в административной части.
	*/
	static public function GetDocumentAdminPage($documentId);

	/**
	* Метод возвращает массив произвольной структуры, содержащий всю информацию о документе. По этому массиву документ восстановливается методом RecoverDocumentFromHistory.
	*
	* @param string $documentId - код документа.
	* @return array - массив документа.
	*/
	static public function GetDocumentForHistory($documentId, $historyIndex);

	/**
	* Метод восстанавливает указанный документ из массива. Массив создается методом RecoverDocumentFromHistory.
	*
	* @param string $documentId - код документа.
	* @param array $arDocument - массив.
	*/
	static public function RecoverDocumentFromHistory($documentId, $arDocument);

	// array("read" => "Ета чтение", "write" => "Ета запысь")
	static public function GetAllowableOperations($documentType);
	// array("1" => "Админы", 2 => "Гости", 3 => ..., "Author" => "Афтар")
	static public function GetAllowableUserGroups($documentType);
	static public function GetUsersFromUserGroup($group, $documentId);
}
?>