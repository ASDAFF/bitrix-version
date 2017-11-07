<?php
namespace Bitrix\Main\DB;

use Bitrix\Main\Diag;
use Bitrix\Main\Type;
use Bitrix\Main\Entity;

class MssqlResult extends Result
{
	/** @var Entity\ScalarField[]  */
	private $resultFields = array();

	public function __construct($result, Connection $dbConnection = null, Diag\SqlTrackerQuery $trackerQuery = null)
	{
		parent::__construct($result, $dbConnection, $trackerQuery);
	}

	public function getFields()
	{
		if (empty($this->resultFields))
		{
			$helper = $this->connection->getSqlHelper();

			$fields = sqlsrv_field_metadata($this->resource);
			if ($fields)
			{
				foreach ($fields as $value)
				{
					$name = ($value["Name"] <> ''? $value["Name"] : uniqid());
					$parameters = array(
						"size" => $value["Size"],
						"scale" => $value["Scale"],
					);
					$this->resultFields[$name] = $helper->getFieldByColumnType($name, $value["Type"], $parameters);
				}
			}
		}

		return $this->resultFields;
	}

	public function getSelectedRowsCount()
	{
		return sqlsrv_num_rows($this->resource);
	}

	protected function fetchRowInternal()
	{
		return sqlsrv_fetch_array($this->resource, SQLSRV_FETCH_ASSOC);
	}
}
