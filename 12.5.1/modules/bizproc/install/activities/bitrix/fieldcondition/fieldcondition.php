<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CBPFieldCondition
	extends CBPActivityCondition
{
	public $condition = null;

	public function __construct($condition)
	{
		$this->condition = $condition;
	}

	public function Evaluate(CBPActivity $ownerActivity)
	{
		if ($this->condition == null || !is_array($this->condition) || count($this->condition) <= 0)
			return true;

		if (!is_array($this->condition[0]))
			$this->condition = array($this->condition);

		$rootActivity = $ownerActivity->GetRootActivity();
		$documentId = $rootActivity->GetDocumentId();

		$documentService = $ownerActivity->workflow->GetService("DocumentService");
		$document = $documentService->GetDocument($documentId);
		$documentFields = $documentService->GetDocumentFields($documentService->GetDocumentType($documentId));

		$result = true;
		foreach ($this->condition as $cond)
		{
			if (array_key_exists($cond[0], $document))
			{
				if (!$this->CheckCondition($document[$cond[0]], $cond[1], $cond[2], $documentFields[$cond[0]]["BaseType"], $rootActivity))
				{
					$result = false;
					break;
				}
			}
		}

		return $result;
	}

	private function CheckCondition($field, $operation, $value, $type = null, $rootActivity = null)
	{
		$result = false;

		$value = $rootActivity->ParseValue($value);
		if ($type == "user")
		{
			$field = CBPHelper::ExtractUsersFromUserGroups($field, $rootActivity);
			$value = CBPHelper::ExtractUsersFromUserGroups($value, $rootActivity);
		}
		elseif ($type == "select")
		{
			if (is_array($field))
				$field = array_keys($field);
		}

		if (!is_array($field))
			$field = array($field);

		if ($operation == "in")
		{
			foreach ($field as $f)
			{
				if (is_array($value))
					$result = in_array($f, $value);
				else
					$result = (strpos($value, $f) !== false);

				if (!$result)
					break;
			}

			return $result;
		}

		if (!is_array($value))
			$value = array($value);

		$i = 0;
		$fieldCount = count($field);
		$valueCount = count($value);
		$iMax = max($fieldCount, $valueCount);
		while ($i < $iMax)
		{
			$f1 = ($fieldCount > $i) ? $field[$i] : $field[$fieldCount - 1];
			$v1 = ($valueCount > $i) ? $value[$i] : $value[$valueCount - 1];

			if ($type == "datetime")
			{
				if (($f1Tmp = MakeTimeStamp($f1, FORMAT_DATETIME)) === false)
				{
					if (($f1Tmp = MakeTimeStamp($f1, FORMAT_DATE)) === false)
					{
						if (($f1Tmp = MakeTimeStamp($f1, "YYYY-MM-DD HH:MI:SS")) === false)
						{
							if (($f1Tmp = MakeTimeStamp($f1, "YYYY-MM-DD")) === false)
								$f1Tmp = 0;
						}
					}
				}
				$f1 = $f1Tmp;

				if (($v1Tmp = MakeTimeStamp($v1, FORMAT_DATETIME)) === false)
				{
					if (($v1Tmp = MakeTimeStamp($v1, FORMAT_DATE)) === false)
					{
						if (($v1Tmp = MakeTimeStamp($v1, "YYYY-MM-DD HH:MI:SS")) === false)
						{
							if (($v1Tmp = MakeTimeStamp($v1, "YYYY-MM-DD")) === false)
								$v1Tmp = 0;
						}
					}
				}
				$v1 = $v1Tmp;
			}

			switch ($operation)
			{
				case ">":
					$result = ($f1 > $v1);
					break;
				case ">=":
					$result = ($f1 >= $v1);
					break;
				case "<":
					$result = ($f1 < $v1);
					break;
				case "<=":
					$result = ($f1 <= $v1);
					break;
				case "!=":
					$result = ($f1 != $v1);
					break;
				default:
					$result = ($f1 == $v1);
			}

			if (!$result)
				break;

			$i++;
		}

		return $result;
	}

	public static function GetPropertiesDialog($documentType, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $defaultValue, $arCurrentValues = null, $formName = "")
	{
		$runtime = CBPRuntime::GetRuntime();

		$documentService = $runtime->GetService("DocumentService");
		$arDocumentFieldsTmp = $documentService->GetDocumentFields($documentType);

		$arFieldTypes = $documentService->GetDocumentFieldTypes($documentType);

		if (!is_array($arCurrentValues))
		{
			$arCurrentValues = array();
			if (is_array($defaultValue))
			{
				$i = 0;
				foreach ($defaultValue as $value)
				{
					if (strlen($arCurrentValues["field_condition_count"]) > 0)
						$arCurrentValues["field_condition_count"] .= ",";
					$arCurrentValues["field_condition_count"] .= $i;

					$arCurrentValues["field_condition_field_".$i] = $value[0];
					$arCurrentValues["field_condition_condition_".$i] = $value[1];
					$arCurrentValues["field_condition_value_".$i] = $value[2];

					if ($arDocumentFieldsTmp[$arCurrentValues["field_condition_field_".$i]]["BaseType"] == "user")
					{
						if (!is_array($arCurrentValues["field_condition_value_".$i]))
							$arCurrentValues["field_condition_value_".$i] = array($arCurrentValues["field_condition_value_".$i]);
						$arCurrentValues["field_condition_value_".$i] = CBPHelper::UsersArrayToString($arCurrentValues["field_condition_value_".$i], $arWorkflowTemplate, $documentType);
					}

					$i++;
				}
			}
		}
		else
		{
			$arFieldConditionCount = explode(",", $arCurrentValues["field_condition_count"]);
			foreach ($arFieldConditionCount as $i)
			{
				if (intval($i)."!" != $i."!")
					continue;

				$i = intval($i);

				if (!array_key_exists("field_condition_field_".$i, $arCurrentValues) || strlen($arCurrentValues["field_condition_field_".$i]) <= 0)
					continue;

				$arErrors = array();
				$arCurrentValues["field_condition_value_".$i] = $documentService->GetFieldInputValue(
					$documentType,
					$arDocumentFieldsTmp[$arCurrentValues["field_condition_field_".$i]],
					"field_condition_value_".$i,
					$arCurrentValues,
					$arErrors
				);
			}
		}

		$arDocumentFields = array();
		foreach ($arDocumentFieldsTmp as $key => $value)
		{
			if (!$value["Filterable"])
				continue;
			$arDocumentFields[$key] = $value;
		}

		$javascriptFunctions = $documentService->GetJSFunctionsForFields($documentType, "objFieldsFC", $arDocumentFields, $arFieldTypes);

		return $runtime->ExecuteResourceFile(
			__FILE__,
			"properties_dialog.php",
			array(
				"arDocumentFields" => $arDocumentFields,
				"arCurrentValues" => $arCurrentValues,
				"formName" => $formName,
				"arFieldTypes" => $arFieldTypes,
				"javascriptFunctions" => $javascriptFunctions,
			)
		);
	}

	public static function GetPropertiesDialogValues($documentType, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		$runtime = CBPRuntime::GetRuntime();
		$arErrors = array();

		if (!array_key_exists("field_condition_count", $arCurrentValues) || strlen($arCurrentValues["field_condition_count"]) <= 0)
		{
			$arErrors[] = array(
				"code" => "",
				"message" => GetMessage("BPFC_NO_WHERE"),
			);
			return null;
		}

		$documentService = $runtime->GetService("DocumentService");
		$arDocumentFieldsTmp = $documentService->GetDocumentFields($documentType);

		$arResult = array();

		$arFieldConditionCount = explode(",", $arCurrentValues["field_condition_count"]);
		foreach ($arFieldConditionCount as $i)
		{
			if (intval($i)."!" != $i."!")
				continue;

			$i = intval($i);

			if (!array_key_exists("field_condition_field_".$i, $arCurrentValues) || strlen($arCurrentValues["field_condition_field_".$i]) <= 0)
				continue;

			$arErrors = array();
			$arCurrentValues["field_condition_value_".$i] = $documentService->GetFieldInputValue(
				$documentType,
				$arDocumentFieldsTmp[$arCurrentValues["field_condition_field_".$i]],
				"field_condition_value_".$i,
				$arCurrentValues,
				$arErrors
			);

			$arResult[] = array(
				$arCurrentValues["field_condition_field_".$i],
				htmlspecialcharsback($arCurrentValues["field_condition_condition_".$i]),
				$arCurrentValues["field_condition_value_".$i]
			);
		}

		if (count($arResult) <= 0)
		{
			$arErrors[] = array(
				"code" => "",
				"message" => GetMessage("BPFC_NO_WHERE"),
			);
			return null;
		}

		return $arResult;
	}
}
?>