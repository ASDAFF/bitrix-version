<?php
namespace Bitrix\Main\DB;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Diag;
use Bitrix\Main\Entity;

class MssqlConnection extends Connection
{
	/**
	 * @return SqlHelper
	 */
	protected function createSqlHelper()
	{
		return new MssqlSqlHelper($this);
	}

	protected function connectInternal()
	{
		if ($this->isConnected)
			return;

		$connectionInfo = array(
			"UID" => $this->login,
			"PWD" => $this->password,
			"Database" => $this->database,
			"ReturnDatesAsStrings" => true,
			/*"CharacterSet" => "utf-8",*/
		);

		if (($this->options & self::PERSISTENT) != 0)
			$connectionInfo["ConnectionPooling"] = true;
		else
			$connectionInfo["ConnectionPooling"] = false;

		$connection = sqlsrv_connect($this->host, $connectionInfo);

		if (!$connection)
			throw new ConnectionException('MS Sql connect error', $this->getErrorMessage());

		$this->resource = $connection;
		$this->isConnected = true;

		// hide cautions
		sqlsrv_configure ("WarningsReturnAsErrors", 0);

		global $DB, $USER, $APPLICATION;
		if ($fn = \Bitrix\Main\Loader::getPersonal("php_interface/after_connect_d7.php"))
			include($fn);
	}

	protected function disconnectInternal()
	{
		if (!$this->isConnected)
			return;

		$this->isConnected = false;
		sqlsrv_close($this->resource);
	}

	/**
	 * @param                           $sql
	 * @param array|null                $arBinds
	 * @param Diag\SqlTrackerQuery|null $trackerQuery
	 *
	 * @throws SqlQueryException
	 * @return mixed
	 */
	protected function queryInternal($sql, array $arBinds = null, Diag\SqlTrackerQuery $trackerQuery = null)
	{
		$this->connectInternal();

		if ($trackerQuery != null)
			$trackerQuery->startQuery($sql, $arBinds);

		$result = sqlsrv_query($this->resource, $sql, array(), array("Scrollable" => 'forward'));

		if ($trackerQuery != null)
			$trackerQuery->finishQuery();

		$this->lastQueryResult = $result;

		if (!$result)
			throw new SqlQueryException('MS Sql query error', $this->getErrorMessage(), $sql);

		return $result;
	}

	/**
	 * @param $result
	 * @param \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery
	 * @return Result
	 */
	protected function createResult($result, \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery = null)
	{
		return new MssqlResult($result, $this, $trackerQuery);
	}

	/**
	 * @return integer
	 */
	public function getInsertedId()
	{
		return $this->queryScalar("SELECT @@IDENTITY as ID");
	}

	public function getAffectedRowsCount()
	{
		return sqlsrv_rows_affected($this->lastQueryResult);
	}

	/*********************************************************
	 * DDL
	 *********************************************************/
	public function isTableExists($tableName)
	{
		$tableName = preg_replace("/[^A-Za-z0-9%_]+/i", "", $tableName);
		$tableName = Trim($tableName);

		if (strlen($tableName) <= 0)
			return false;

		$result = $this->queryScalar(
			"SELECT COUNT(TABLE_NAME) ".
			"FROM INFORMATION_SCHEMA.TABLES ".
			"WHERE TABLE_NAME LIKE '".$this->getSqlHelper()->forSql($tableName)."'"
		);
		return ($result > 0);
	}

	public function isIndexExists($tableName, array $arColumns)
	{
		return $this->getIndexName($tableName, $arColumns) !== null;
	}

	public function getIndexName($tableName, array $arColumns, $strict = false)
	{
		if (!is_array($arColumns) || count($arColumns) <= 0)
			return null;

		//2005
		//$rs = $this->query("SELECT index_id, COL_NAME(object_id, column_id) AS column_name, key_ordinal FROM SYS.INDEX_COLUMNS WHERE object_id=OBJECT_ID('".$this->forSql($tableName)."')", true);

		//2000
		$rs = $this->query(
			"SELECT s.indid as index_id, s.keyno as key_ordinal, c.name column_name, si.name index_name ".
			"FROM sysindexkeys s ".
			"   INNER JOIN syscolumns c ON s.id = c.id AND s.colid = c.colid ".
			"   INNER JOIN sysobjects o ON s.id = o.Id AND o.xtype = 'U' ".
			"   LEFT JOIN sysindexes si ON si.indid = s.indid AND si.id = s.id ".
			"WHERE o.name = UPPER('".$this->getSqlHelper()->forSql($tableName)."')");

		$arIndexes = array();
		while ($ar = $rs->fetch())
			$arIndexes[$ar["index_name"]][$ar["key_ordinal"] - 1] = $ar["column_name"];

		$strColumns = implode(",", $arColumns);
		foreach ($arIndexes as $key => $keyColumn)
		{
			ksort($keyColumn);
			$strKeyColumns = implode(",", $keyColumn);
			if ($strict)
			{
				if ($strKeyColumns === $strColumns)
					return $key;
			}
			else
			{
				if (substr($strKeyColumns, 0, strlen($strColumns)) === $strColumns)
					return $key;
			}
		}

		return null;
	}

	public function getTableFields($tableName)
	{
		if (!isset($this->tableColumnsCache[$tableName]))
		{
			$this->connectInternal();

			$query = $this->queryInternal("SELECT TOP 0 * FROM ".$this->getSqlHelper()->quote($tableName));

			$result = $this->createResult($query);

			$this->tableColumnsCache[$tableName] = $result->getFields();
		}
		return $this->tableColumnsCache[$tableName];
	}

	public function renameTable($currentName, $newName)
	{
		$this->query('EXEC sp_rename '.$this->getSqlHelper()->quote($currentName).', '.$this->getSqlHelper()->quote($newName));
	}

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
	public function createTable($tableName, $fields, $primary = array(), $autoincrement = array())
	{
		$sql = 'CREATE TABLE '.$this->getSqlHelper()->quote($tableName).' (';
		$sqlFields = array();

		foreach ($fields as $columnName => $field)
		{
			if (!($field instanceof Entity\ScalarField))
			{
				throw new ArgumentException(sprintf(
					'Field `%s` should be an Entity\ScalarField instance', $columnName
				));
			}

			$sqlFields[] = $this->getSqlHelper()->quote($columnName)
				. ' ' . $this->getSqlHelper()->getColumnTypeByField($field)
				. ' NOT NULL'
				. (in_array($columnName, $autoincrement, true) ? ' IDENTITY (1, 1)' : '')
			;
		}

		$sql .= join(', ', $sqlFields);

		if (!empty($primary))
		{
			foreach ($primary as &$primaryColumn)
			{
				$primaryColumn = $this->getSqlHelper()->quote($primaryColumn);
			}

			$sql .= ', PRIMARY KEY('.join(', ', $primary).')';
		}

		$sql .= ')';

		$this->query($sql);
	}

	public function dropTable($tableName)
	{
		$this->query('DROP TABLE '.$this->getSqlHelper()->quote($tableName));
	}

	/*********************************************************
	 * Transaction
	 *********************************************************/
	public function startTransaction()
	{
		$this->connectInternal();
		sqlsrv_begin_transaction($this->resource);
	}

	public function commitTransaction()
	{
		$this->connectInternal();
		sqlsrv_commit($this->resource);
	}

	public function rollbackTransaction()
	{
		$this->connectInternal();
		sqlsrv_rollback($this->resource);
	}

	/*********************************************************
	 * Type, version, cache, etc.
	 *********************************************************/
	public function getType()
	{
		return "mssql";
	}

	public function getVersion()
	{
		if ($this->version == null)
		{
			$version = $this->queryScalar("SELECT @@VERSION");
			if ($version != null)
			{
				$version = trim($version);
				$this->versionExpress = (strpos($version, "Express Edition") > 0);
				preg_match("#[0-9]+\\.[0-9]+\\.[0-9]+#", $version, $arr);
				$this->version = $arr[0];
			}
		}

		return array($this->version, $this->versionExpress);
	}

	protected function getErrorMessage()
	{
		$errors = "";

		$arErrors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
		foreach ($arErrors as $error)
			$errors .= "SQLSTATE: ".$error['SQLSTATE'].";"." code: ".$error['code']."; message: ".$error[ 'message']."\n";

		return $errors;
	}
}
