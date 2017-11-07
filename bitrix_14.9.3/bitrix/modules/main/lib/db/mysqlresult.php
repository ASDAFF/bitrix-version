<?php
namespace Bitrix\Main\DB;

use Bitrix\Main\Entity;

class MysqlResult extends Result
{
	/** @var Entity\ScalarField[]  */
	protected $resultFields = array();

	public function __construct($result, Connection $dbConnection, \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery = null)
	{
		parent::__construct($result, $dbConnection, $trackerQuery);
	}

	public function getSelectedRowsCount()
	{
		return mysql_num_rows($this->resource);
	}

	protected function getErrorMessage()
	{
		return sprintf("[%s] %s", mysql_errno($this->connection->getResource()), mysql_error($this->connection->getResource()));
	}

	public function getFields()
	{
		if (empty($this->resultFields))
		{
			$helper = $this->connection->getSqlHelper();

			$numFields = mysql_num_fields($this->resource);
			for ($i = 0; $i < $numFields; $i++)
			{
				$name = mysql_field_name($this->resource, $i);
				$type = mysql_field_type($this->resource, $i);

				$this->resultFields[$name] = $helper->getFieldByColumnType($name, $type);
			}
		}
		return $this->resultFields;
	}

	protected function fetchRowInternal()
	{
		return mysql_fetch_assoc($this->resource);
	}
}
