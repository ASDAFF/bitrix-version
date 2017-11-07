<?php
namespace Bitrix\Main\DB;

use Bitrix\Main\Entity;

class MysqliResult extends Result
{
	/** @var \mysqli_result */
	protected $resource;

	/** @var Entity\ScalarField[]  */
	private $resultFields = array();

	public function __construct($result, Connection $dbConnection = null, \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery = null)
	{
		parent::__construct($result, $dbConnection, $trackerQuery);
	}

	public function getSelectedRowsCount()
	{
		return $this->resource->num_rows;
	}

	public function getFields()
	{
		if (empty($this->resultFields))
		{
			$helper = $this->connection->getSqlHelper();

			$resultFields = $this->resource->fetch_fields();
			foreach ($resultFields as $field)
			{
				$this->resultFields[$field->name] = $helper->getFieldByColumnType($field->name, $field->type);
			}
		}

		return $this->resultFields;
	}

	protected function fetchRowInternal()
	{
		return $this->resource->fetch_assoc();
	}
}
