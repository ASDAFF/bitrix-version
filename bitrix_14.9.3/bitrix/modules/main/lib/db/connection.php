<?php
namespace Bitrix\Main\DB;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Config;
use Bitrix\Main\Data;
use Bitrix\Main\Diag;
use Bitrix\Main\Entity;

/**
 * Class Connection
 *
 * Base abstract class for database connections.
 */
abstract class Connection extends Data\Connection
{
	/**@var SqlHelper */
	protected $sqlHelper;

	/** @var Diag\SqlTracker */
	protected $sqlTracker;
	protected $trackSql = false;

	protected $version;
	protected $versionExpress;

	protected $host;
	protected $database;
	protected $login;
	protected $password;
	protected $initCommand = 0;
	protected $options = 0;

	protected $tableColumnsCache = array();
	protected $lastQueryResult;

	/**
	 * @var bool Flag for static::query - if need to execute query or just to collect it
	 * @see $disabledQueryExecutingDump
	 */
	protected $queryExecutingEnabled = true;

	/** @var null|string[] Queries that were collected while Query Executing was Disabled */
	protected $disabledQueryExecutingDump;

	const PERSISTENT = 1;
	const DEFERRED = 2;

	public function __construct(array $configuration)
	{
		parent::__construct($configuration);

		$this->host = $configuration['host'];
		$this->database = $configuration['database'];
		$this->login = $configuration['login'];
		$this->password = $configuration['password'];
		$this->initCommand = isset($configuration['initCommand']) ? $configuration['initCommand'] : "";

		$this->options = intval($configuration['options']);
		if ($this->options < 0)
			$this->options = self::PERSISTENT | self::DEFERRED;
	}

	/**
	 * @deprecated Use getHost()
	 */
	public function getDbHost()
	{
		return $this->getHost();
	}

	/**
	 * @deprecated Use getLogin()
	 */
	public function getDbLogin()
	{
		return $this->getLogin();
	}

	/**
	 * @deprecated Use getDatabase()
	 */
	public function getDbName()
	{
		return $this->getDatabase();
	}

	/**
	 * Returns database host.
	 *
	 * @return string
	 */
	public function getHost()
	{
		return $this->host;
	}

	/**
	 * Returns database login.
	 *
	 * @return string
	 */
	public function getLogin()
	{
		return $this->login;
	}

	/**
	 * Returns database name.
	 *
	 * @return string
	 */
	public function getDatabase()
	{
		return $this->database;
	}

	/**
	 * Sets the connection resource directly.
	 *
	 * @param resource $connection
	 */
	public function setConnectionResourceNoDemand(&$connection)
	{
		$this->resource = &$connection;
		$this->isConnected = true;
	}

	/**
	 * Temprorary disables query executing. All queries being collected in disabledQueryExecutingDump
	 *
	 * @api
	 * @see enableQueryExecuting
	 * @see getDisabledQueryExecutingDump
	 *
	 * @return void
	 */
	public function disableQueryExecuting()
	{
		$this->queryExecutingEnabled = false;
	}

	/**
	 * Enables query executing after it has been temprorary disabled
	 *
	 * @api
	 * @see disableQueryExecuting
	 *
	 * @return void
	 */
	public function enableQueryExecuting()
	{
		$this->queryExecutingEnabled = true;
	}

	/**
	 * @api
	 * @see disableQueryExecuting
	 *
	 * @return bool
	 */
	public function isQueryExecutingEnabled()
	{
		return $this->queryExecutingEnabled;
	}

	/**
	 * Returns queries that were collected while Query Executing was disabled and clears the dump
	 *
	 * @api
	 * @see disableQueryExecuting
	 *
	 * @return null|\string[]
	 */
	public function getDisabledQueryExecutingDump()
	{
		$dump = $this->disabledQueryExecutingDump;
		$this->disabledQueryExecutingDump = null;

		return $dump;
	}

	/**********************************************************
	 * SqlHelper
	 **********************************************************/

	/**
	 * @return SqlHelper
	 */
	public function getSqlHelper()
	{
		if ($this->sqlHelper == null)
			$this->sqlHelper = $this->createSqlHelper();

		return $this->sqlHelper;
	}

	/**
	 * @return SqlHelper
	 */
	abstract protected function createSqlHelper();


	/***********************************************************
	 * Connection and disconnection
	 ***********************************************************/

	/**
	 * Connects to a database.
	 */
	public function connect()
	{
		$this->isConnected = false;

		if (($this->options & self::DEFERRED) != 0)
			return;

		parent::connect();
	}

	/**
	 * Disconnects from a database.
	 */
	public function disconnect()
	{
		if (($this->options & self::PERSISTENT) != 0)
			return;

		parent::disconnect();
	}


	/*********************************************************
	 * Query
	 *********************************************************/

	/**
	 * @param                      $sql
	 * @param array                $arBinds
	 * @param Diag\SqlTrackerQuery $trackerQuery
	 *
	 * @return mixed
	 */
	abstract protected function queryInternal($sql, array $arBinds = null, Diag\SqlTrackerQuery $trackerQuery = null);

	/**
	 * @param $result
	 * @param \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery
	 * @return Result
	 */
	abstract protected function createResult($result, Diag\SqlTrackerQuery $trackerQuery = null);

	/**
	 * Executes a query to database
	 * query($sql)
	 * query($sql, $limit)
	 * query($sql, $offset, $limit)
	 * query($sql, $arBinds)
	 * query($sql, $arBinds, $limit)
	 * query($sql, $arBinds, $offset, $limit)
	 *
	 * @param string $sql Sql query
	 * @param array $arBinds Array of binds
	 * @param int $offset Offset
	 * @param int $limit Limit
	 * @return Result
	 */
	public function query($sql)
	{
		list($sql, $arBinds, $offset, $limit) = self::parseQueryFunctionArgs(func_get_args());

		if($limit > 0)
		{
			$sql = $this->getSqlHelper()->getTopSql($sql, $limit, $offset);
		}

		$trackerQuery = null;

		if ($this->queryExecutingEnabled)
		{
			if ($this->trackSql)
				$trackerQuery = $this->sqlTracker->getNewTrackerQuery();

			$result = $this->queryInternal($sql, $arBinds, $trackerQuery);
		}
		else
		{
			if ($this->disabledQueryExecutingDump === null)
			{
				$this->disabledQueryExecutingDump = array();
			}

			$this->disabledQueryExecutingDump[] = $sql;
			$result = true;
		}

		return $this->createResult($result, $trackerQuery);
	}

	/**
	 * Executes a query, fetches a row and returns single field value
	 *
	 * @param string $sql
	 * @param array $arBinds
	 * @return string|null
	 */
	public function queryScalar($sql, array $arBinds = null)
	{
		$result = $this->query($sql, $arBinds, 0, 1);

		if ($row = $result->fetch())
		{
			return array_shift($row);
		}

		return null;
	}

	/**
	 * Executes a query without returning result, i.e. INSERT, UPDATE, DELETE
	 *
	 * @param string $sql
	 * @param array $arBinds
	 */
	public function queryExecute($sql, array $arBinds = null)
	{
		$this->query($sql, $arBinds);
	}

	protected static function parseQueryFunctionArgs($args)
	{
		/*
		 * query($sql)
		 * query($sql, $limit)
		 * query($sql, $offset, $limit)
		 * query($sql, $arBinds)
		 * query($sql, $arBinds, $limit)
		 * query($sql, $arBinds, $offset, $limit)
		 */
		$numArgs = count($args);
		if ($numArgs < 1)
			throw new ArgumentNullException("sql");

		$arBinds = array();
		$offset = 0;
		$limit = 0;

		if ($numArgs == 1)
		{
			$sql = $args[0];
		}
		elseif ($numArgs == 2)
		{
			if (is_array($args[1]))
				list($sql, $arBinds) = $args;
			else
				list($sql, $limit) = $args;
		}
		elseif ($numArgs == 3)
		{
			if (is_array($args[1]))
				list($sql, $arBinds, $limit) = $args;
			else
				list($sql, $offset, $limit) = $args;
		}
		else
		{
			list($sql, $arBinds, $offset, $limit) = $args;
		}

		return array($sql, $arBinds, $offset, $limit);
	}

	/**
	 * Adds row to table and returns ID of added row
	 *
	 * @param string $tableName
	 * @param array $data
	 * @param string $identity For Oracle only
	 * @return integer
	 */
	public function add($tableName, array $data, $identity = "ID")
	{
		$insert = $this->getSqlHelper()->prepareInsert($tableName, $data);

		$sql =
			"INSERT INTO ".$tableName."(".$insert[0].") ".
			"VALUES (".$insert[1].")";

		$this->queryExecute($sql);

		return $this->getInsertedId();
	}

	/**
	 * @return integer
	 */
	abstract public function getInsertedId();

	/**
	 * Parses the string containing multiple queries and executes the queries one by one.
	 *
	 * @param string $sqlBatch String with queries, separated by database-specific delimiters.
	 * @param bool $stopOnError Whether return after the first error.
	 * @return array Array of errors or empty array on success.
	 */
	public function executeSqlBatch($sqlBatch, $stopOnError = false)
	{
		$delimiter = $this->getSqlHelper()->getQueryDelimiter();

		$sqlBatch = trim($sqlBatch);

		$arSqlBatch = array();
		$sql = "";

		do
		{
			if (preg_match("%^(.*?)(['\"`#]|--|".$delimiter.")%is", $sqlBatch, $match))
			{
				//Found string start
				if ($match[2] == "\"" || $match[2] == "'" || $match[2] == "`")
				{
					$sqlBatch = substr($sqlBatch, strlen($match[0]));
					$sql .= $match[0];
					//find a qoute not preceeded by \
					if (preg_match("%^(.*?)(?<!\\\\)".$match[2]."%s", $sqlBatch, $string_match))
					{
						$sqlBatch = substr($sqlBatch, strlen($string_match[0]));
						$sql .= $string_match[0];
					}
					else
					{
						//String falled beyong end of file
						$sql .= $sqlBatch;
						$sqlBatch = "";
					}
				}
				//Comment found
				elseif ($match[2] == "#" || $match[2] == "--")
				{
					//Take that was before comment as part of sql
					$sqlBatch = substr($sqlBatch, strlen($match[1]));
					$sql .= $match[1];
					//And cut the rest
					$p = strpos($sqlBatch, "\n");
					if ($p === false)
					{
						$p1 = strpos($sqlBatch, "\r");
						if ($p1 === false)
							$sqlBatch = "";
						elseif ($p < $p1)
							$sqlBatch = substr($sqlBatch, $p);
						else
							$sqlBatch = substr($sqlBatch, $p1);
					}
					else
						$sqlBatch = substr($sqlBatch, $p);
				}
				//Delimiter!
				else
				{
					//Take that was before delimiter as part of sql
					$sqlBatch = substr($sqlBatch, strlen($match[0]));
					$sql .= $match[1];
					//Delimiter must be followed by whitespace
					if (preg_match("%^[\n\r\t ]%", $sqlBatch))
					{
						$sql = trim($sql);
						if (!empty($sql))
						{
							$arSqlBatch[] = str_replace("\r\n", "\n", $sql);
							$sql = "";
						}
					}
					//It was not delimiter!
					elseif (!empty($sqlBatch))
					{
						$sql .= $match[2];
					}
				}
			}
			else //End of file is our delimiter
			{
				$sql .= $sqlBatch;
				$sqlBatch = "";
			}
		}
		while (!empty($sqlBatch));

		$sql = trim($sql);
		if (!empty($sql))
			$arSqlBatch[] = str_replace("\r\n", "\n", $sql);

		$result = array();
		foreach ($arSqlBatch as $sql)
		{
			try
			{
				$this->queryExecute($sql);
			}
			catch (SqlException $ex)
			{
				$result[] = $ex->getMessage();
				if ($stopOnError)
					return $result;
			}
		}

		return $result;
	}

	/**
	 * Returns affected rows count from last executed query
	 *
	 * @return int
	 */
	abstract public function getAffectedRowsCount();

	/*********************************************************
	 * DDL
	 *********************************************************/

	/**
	 * Checks if a table exists.
	 *
	 * @param string $tableName The table name.
	 * @return bool
	 */
	abstract public function isTableExists($tableName);

	/**
	 * Checks if an index exists.
	 *
	 * @param string $tableName A table name.
	 * @param array $arColumns An array of columns in the index.
	 * @return bool
	 */
	abstract public function isIndexExists($tableName, array $arColumns);

	/**
	 * Returns the name of an index.
	 *
	 * @param string $tableName A table name.
	 * @param array $arColumns An array of columns in the index.
	 * @param bool $strict The flag indicating that the columns in the index must exactly match the columns in the $arColumns parameter.
	 * @return string|null Name of the index or null if the index doesn't exist.
	 */
	abstract public function getIndexName($tableName, array $arColumns, $strict = false);

	/**
	 * Returns fields objects according to the columns of a table.
	 *
	 * @param string $tableName The table name.
	 * @return Entity\ScalarField[] An array of objects with columns information.
	 */
	abstract public function getTableFields($tableName);

	/**
	 * @param string               $tableName
	 * @param Entity\ScalarField[] $fields
	 * @param string[]             $primary
	 * @param string[]             $autoincrement
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 *
	 * @return void
	 */
	abstract public function createTable($tableName, $fields, $primary = array(), $autoincrement = array());

	/**
	 * Creates primary index on column(s)
	 * @api
	 *
	 * @param string          $tableName
	 * @param string|string[] $columnNames
	 *
	 * @return Result
	 */
	public function createPrimaryIndex($tableName, $columnNames)
	{
		if (!is_array($columnNames))
		{
			$columnNames = array($columnNames);
		}

		foreach ($columnNames as &$columnName)
		{
			$columnName = $this->getSqlHelper()->quote($columnName);
		}

		$sql = 'ALTER TABLE '.$this->getSqlHelper()->quote($tableName).' ADD PRIMARY KEY('.join(', ', $columnNames).')';

		return $this->query($sql);
	}

	/**
	 * Returns an object for the single column according to the column type.
	 *
	 * @param string $tableName
	 * @param string $columnName
	 * @return Entity\ScalarField | null
	 */
	public function getTableField($tableName, $columnName)
	{
		$tableFields = $this->getTableFields($tableName);

		return (isset($tableFields[$columnName])? $tableFields[$columnName] : null);
	}

	/**
	 * Renames a table.
	 *
	 * @param string $currentName
	 * @param string $newName
	 */
	abstract public function renameTable($currentName, $newName);

	/**
	 * Drops a column.
	 *
	 * @param string $tableName
	 * @param string $columnName
	 */
	public function dropColumn($tableName, $columnName)
	{
		$this->query('ALTER TABLE '.$this->getSqlHelper()->quote($tableName).' DROP COLUMN '.$this->getSqlHelper()->quote($columnName));
	}

	/**
	 * Drops a table.
	 *
	 * @param string $tableName
	 */
	abstract public function dropTable($tableName);

	/*********************************************************
	 * Transaction
	 *********************************************************/

	abstract public function startTransaction();
	abstract public function commitTransaction();
	abstract public function rollbackTransaction();


	/*********************************************************
	 * Tracker
	 *********************************************************/

	public function startTracker($reset = false)
	{
		if ($this->sqlTracker == null)
			$this->sqlTracker = new Diag\SqlTracker();
		if ($reset)
			$this->sqlTracker->reset();

		$this->trackSql = true;
	}

	public function stopTracker()
	{
		$this->trackSql = false;
	}

	public function getTracker()
	{
		return $this->sqlTracker;
	}


	/*********************************************************
	 * Type, version, cache, etc.
	 *********************************************************/

	abstract public function getType();
	abstract public function getVersion();
	abstract protected function getErrorMessage();

	public function clearCaches()
	{
		$this->tableColumnsCache = array();
	}
}
