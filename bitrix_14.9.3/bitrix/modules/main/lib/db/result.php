<?php
namespace Bitrix\Main\DB;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity;
use Bitrix\Main\Type;

abstract class Result
{
	/** @var Connection */
	protected $connection;
	protected $resource;

	/**@var \Bitrix\Main\Diag\SqlTrackerQuery */
	protected $trackerQuery;

	protected $arReplacedAliases = array();
	protected $arSerializedFields = array();

	/** @var callable[] */
	protected $fetchDataModifiers = array();

	/**
	 * @param resource $result Database-specific query result
	 * @param Connection $dbConnection Connection object
	 * @param \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery
	 */
	public function __construct($result, Connection $dbConnection = null, \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery = null)
	{
		$this->resource = $result;
		$this->connection = $dbConnection;
		$this->trackerQuery = $trackerQuery;
	}

	/**
	 * Returns database-specific resource of the result.
	 *
	 * @return mixed
	 */
	public function getResource()
	{
		return $this->resource;
	}

	public function setReplacedAliases(array $arReplacedAliases)
	{
		$this->arReplacedAliases = $arReplacedAliases;
	}

	public function addReplacedAliases(array $arReplacedAliases)
	{
		$this->arReplacedAliases = array_merge($this->arReplacedAliases, $arReplacedAliases);
	}

	public function setSerializedFields(array $arSerializedFields)
	{
		$this->arSerializedFields = $arSerializedFields;
	}

	/**
	 * Modifier should accept once fetched array as an argument, then modify by link or return new array:
	 * - function (&$data) { $data['AGE'] -= 7; }
	 * - function ($data) { $data['AGE'] -= 7; return $data; }
	 *
	 * @param callable $fetchDataModifier
	 *
	 * @throws ArgumentException
	 */
	public function addFetchDataModifier($fetchDataModifier)
	{
		if (!is_callable($fetchDataModifier))
		{
			throw new ArgumentException('Data Modifier should be a callback');
		}

		$this->fetchDataModifiers[] = $fetchDataModifier;
	}

	/**
	 * Fetches one row of the query result and returns it in the associative array or false on empty data
	 *
	 * @param \Bitrix\Main\Text\Converter $converter Optional converter to encode data on fetching
	 * @return array|bool
	 */
	public function fetch(\Bitrix\Main\Text\Converter $converter = null)
	{
		if ($this->trackerQuery != null)
			$this->trackerQuery->restartQuery();

		$dataTmp = $this->fetchRowInternal();

		if ($this->trackerQuery != null)
			$this->trackerQuery->refinishQuery();

		if (!$dataTmp)
			return false;

		$resultFields = $this->getFields();

		if($resultFields !== null)
		{
			$helper = $this->connection->getSqlHelper();
			$data = array();
			foreach ($dataTmp as $key => $value)
			{
				if(isset($resultFields[$key]))
				{
					$data[$key] = $helper->convertFromDb($value, $resultFields[$key]);
				}
				else
				{
					$data[$key] = $value;
				}
			}
		}
		else
		{
			$data = $dataTmp;
		}

		if (!empty($this->arSerializedFields))
		{
			foreach ($this->arSerializedFields as $field)
			{
				if (isset($data[$field]))
					$data[$field] = unserialize($data[$field]);
			}
		}

		if (!empty($this->arReplacedAliases))
		{
			foreach ($this->arReplacedAliases as $tech => $human)
			{
				$data[$human] = $data[$tech];
				unset($data[$tech]);
			}
		}

		if (!empty($this->fetchDataModifiers))
		{
			foreach ($this->fetchDataModifiers as $fetchDataModifier)
			{
				$result = call_user_func_array($fetchDataModifier, array(&$data));

				if (is_array($result))
				{
					$data = $result;
				}
			}
		}

		if ($converter != null)
		{
			foreach ($data as $key => $val)
			{
				$data[$key] = $converter->encode(
					$val,
					(isset($data[$key."_TYPE"])? $data[$key."_TYPE"] : \Bitrix\Main\Text\Converter::TEXT)
				);
			}
		}

		return $data;
	}

	/**
	 * Fetches all the rows of the query result and returns it in the array of associative arrays

	 * @param \Bitrix\Main\Text\Converter $converter Optional converter to encode data on fetching
	 * @return array
	 */
	public function fetchAll(\Bitrix\Main\Text\Converter $converter = null)
	{
		$res = array();
		while ($ar = $this->fetch($converter))
		{
			$res[] = $ar;
		}
		return $res;
	}

	/**
	 * Returns an array of fields according to columns in the result.
	 *
	 * @return Entity\ScalarField[]
	 */
	abstract public function getFields();

	/**
	 * Returns the number of rows in the result.
	 *
	 * @return int
	 */
	abstract public function getSelectedRowsCount();

	abstract protected function fetchRowInternal();

	/**
	 * @return \Bitrix\Main\Diag\SqlTrackerQuery
	 */
	public function getTrackerQuery()
	{
		return $this->trackerQuery;
	}
}
