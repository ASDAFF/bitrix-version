<?php
namespace Bitrix\Main\DB;

use Bitrix\Main\Type;
use Bitrix\Main\Entity;

class OracleResult extends Result
{
	/** @var Entity\ScalarField[]  */
	private $resultFields = array();

	public function __construct($result, Connection $dbConnection = null, \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery = null)
	{
		parent::__construct($result, $dbConnection, $trackerQuery);
	}

	public function getSelectedRowsCount()
	{
		return oci_num_rows($this->resource);
	}

	public function getFields()
	{
		if (empty($this->resultFields))
		{
			$helper = $this->connection->getSqlHelper();

			$numFields = oci_num_fields($this->resource);
			for ($i = 1; $i <= $numFields; $i++)
			{
				$name = oci_field_name($this->resource, $i);
				$type = oci_field_type($this->resource, $i);
				$parameters = array(
					"precision" => oci_field_precision($this->resource, $i),
					"scale" => oci_field_scale($this->resource, $i),
					"size" => oci_field_size($this->resource, $i),
				);

				$this->resultFields[$name] = $helper->getFieldByColumnType($name, $type, $parameters);
			}
		}

		return $this->resultFields;
	}

	protected function fetchRowInternal()
	{
		return oci_fetch_array($this->resource, OCI_ASSOC + OCI_RETURN_NULLS + OCI_RETURN_LOBS);
	}
}
