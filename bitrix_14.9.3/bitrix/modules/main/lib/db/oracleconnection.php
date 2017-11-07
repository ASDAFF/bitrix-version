<?php
namespace Bitrix\Main\DB;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Diag;
use Bitrix\Main\Entity;

class OracleConnection extends Connection
{
	private $transaction = OCI_COMMIT_ON_SUCCESS;

	protected $lastInsertedId;

	/**********************************************************
	 * SqlHelper
	 **********************************************************/

	/**
	 * @return SqlHelper
	 */
	protected function createSqlHelper()
	{
		return new OracleSqlHelper($this);
	}


	/***********************************************************
	 * Connection and disconnection
	 ***********************************************************/

	protected function connectInternal()
	{
		if ($this->isConnected)
			return;

		if (($this->options & self::PERSISTENT) != 0)
			$connection = oci_pconnect($this->login, $this->password, $this->database);
		else
			$connection = oci_connect($this->login, $this->password, $this->database);

		if (!$connection)
			throw new ConnectionException('Oracle connect error', $this->getErrorMessage());

		$this->isConnected = true;
		$this->resource = $connection;

		/** @noinspection PhpUnusedLocalVariableInspection */
		global $DB, $USER, $APPLICATION;
		if ($fn = \Bitrix\Main\Loader::getPersonal("php_interface/after_connect_d7.php"))
			include($fn);
	}

	protected function disconnectInternal()
	{
		if (!$this->isConnected)
			return;

		$this->isConnected = false;
		oci_close($this->resource);
	}


	/*********************************************************
	 * Query
	 *********************************************************/

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

		if (!empty($arBinds))
		{
			$binds1 = $binds2 = "";
			foreach ($arBinds as $key => $value)
			{
				if (strlen($value) > 0)
				{
					if ($binds1 != "")
					{
						$binds1 .= ",";
						$binds2 .= ",";
					}

					$binds1 .= $key;
					$binds2 .= ":".$key;
				}
			}

			if ($binds1 != "")
				$sql .= " RETURNING ".$binds1." INTO ".$binds2;
		}

		return parent::query($sql, $arBinds, $offset, $limit);
	}


	/**
	 * @param                           $sql
	 * @param array|null                $arBinds
	 * @param Diag\SqlTrackerQuery|null $trackerQuery
	 *
	 * @throws SqlQueryException
	 * @return resource
	 */
	protected function queryInternal($sql, array $arBinds = null, Diag\SqlTrackerQuery $trackerQuery = null)
	{
		$this->connectInternal();

		$bindsKeys = array();
		if (!empty($arBinds))
		{
			foreach ($arBinds as $key => $value)
			{
				if (strlen($value) > 0)
				{
					$bindsKeys[] = $key;
				}
			}
		}

		if ($trackerQuery != null)
			$trackerQuery->startQuery($sql, $arBinds);

		$result = oci_parse($this->resource, $sql);

		if (!$result)
		{
			if ($trackerQuery != null)
				$trackerQuery->finishQuery();

			throw new SqlQueryException("", $this->getErrorMessage($this->resource), $sql);
		}

		$executionMode = $this->transaction;

		/** @var \OCI_Lob[] $clob */
		$clob = array();

		if (!empty($arBinds))
		{
			$executionMode = OCI_DEFAULT;
			foreach ($bindsKeys as $key)
			{
				$clob[$key] = oci_new_descriptor($this->resource, OCI_DTYPE_LOB);
				oci_bind_by_name($result, ":".$key, $clob[$key], -1, OCI_B_CLOB);
			}
		}

		if (!oci_execute($result, $executionMode))
		{
			if ($trackerQuery != null)
			{
				$trackerQuery->finishQuery();
			}

			throw new SqlQueryException("", $this->getErrorMessage($result), $sql);
		}

		if (!empty($arBinds))
		{
			if (oci_num_rows($result) > 0)
			{
				foreach ($bindsKeys as $key)
				{
					$clob[$key]->save($arBinds[$key]);
				}
			}

			if ($this->transaction == OCI_COMMIT_ON_SUCCESS)
			{
				oci_commit($this->resource);
			}

			foreach ($bindsKeys as $key)
			{
				$clob[$key]->free();
			}
		}

		if ($trackerQuery != null)
		{
			$trackerQuery->finishQuery();
		}

		$this->lastQueryResult = $result;

		return $result;
	}

	/**
	 * Adds row to table and returns ID of added row
	 *
	 * @param string $tableName
	 * @param array $data
	 * @param string $identity
	 * @return integer
	 */
	public function add($tableName, array $data, $identity = "ID")
	{
		if($identity !== null && !isset($data[$identity]))
			$data[$identity] = $this->getNextId("sq_".$tableName);

		$insert = $this->getSqlHelper()->prepareInsert($tableName, $data);

		$binds = $insert[2];

		$sql =
			"INSERT INTO ".$tableName."(".$insert[0].") ".
			"VALUES (".$insert[1].")";

		$this->queryExecute($sql, $binds);

		$this->lastInsertedId = $data[$identity];

		return $data[$identity];
	}

	/**
	 * @param string $name
	 *
	 * @return mixed|null
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public function getNextId($name = "")
	{
		$name = preg_replace("/[^A-Za-z0-9_]+/i", "", $name);
		$name = trim($name);

		if($name == '')
			throw new \Bitrix\Main\ArgumentNullException("name");

		$sql = "SELECT ".$this->getSqlHelper()->quote($name).".NEXTVAL FROM DUAL";

		$result = $this->query($sql);

		$return = null;
		if ($ar = $result->fetch())
			$return = array_shift($ar);

		return $return;
	}

	public function getInsertedId()
	{
		return $this->lastInsertedId;
	}

	/**
	 * @param $result
	 * @param \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery
	 * @return OracleResult
	 */
	protected function createResult($result, \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery = null)
	{
		return new OracleResult($result, $this, $trackerQuery);
	}

	public function getAffectedRowsCount()
	{
		return oci_num_rows($this->lastQueryResult);
	}

	/*********************************************************
	 * DDL
	 *********************************************************/

	public function isTableExists($tableName)
	{
		if (empty($tableName))
			return false;

		$result = $this->queryScalar("
			SELECT COUNT(TABLE_NAME)
			FROM USER_TABLES
			WHERE TABLE_NAME LIKE UPPER('".$this->getSqlHelper()->forSql($tableName)."')
		");
		return ($result > 0);
	}

	public function isIndexExists($tableName, array $arColumns)
	{
		return $this->getIndexName($tableName, $arColumns) !== null;
	}

	public function getIndexName($tableName, array $arColumns, $strict = false)
	{
		if (!is_array($arColumns) || empty($arColumns))
			return null;

		$isFunc = false;
		$arIndexes = array();

		$result = $this->query("SELECT * FROM USER_IND_COLUMNS WHERE TABLE_NAME = upper('".$this->getSqlHelper()->forSql($tableName)."')");
		while ($ar = $result->fetch())
		{
			$arIndexes[$ar["INDEX_NAME"]][$ar["COLUMN_POSITION"] - 1] = $ar["COLUMN_NAME"];
			if (strncmp($ar["COLUMN_NAME"], "SYS_NC", 6) === 0)
				$isFunc = true;
		}

		if ($isFunc)
		{
			$result = $this->query("SELECT * FROM USER_IND_EXPRESSIONS WHERE TABLE_NAME = upper('".$this->getSqlHelper()->forSql($tableName)."')");
			while ($ar = $result->fetch())
				$arIndexes[$ar["INDEX_NAME"]][$ar["COLUMN_POSITION"] - 1] = $ar["COLUMN_EXPRESSION"];
		}

		$columns = implode(",", $arColumns);
		foreach ($arIndexes as $key => $arKeyColumn)
		{
			ksort($arKeyColumn);
			$keyColumn = implode(",", $arKeyColumn);
			if ($strict)
			{
				if ($keyColumn === $columns)
					return $key;
			}
			else
			{
				if (substr($keyColumn, 0, strlen($columns)) === $columns)
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

			$query = $this->queryInternal("SELECT * FROM ".$this->getSqlHelper()->quote($tableName)." WHERE ROWNUM = 0");

			$result = $this->createResult($query);

			$this->tableColumnsCache[$tableName] = $result->getFields();
		}
		return $this->tableColumnsCache[$tableName];
	}

	public function renameTable($currentName, $newName)
	{
		$this->query('RENAME '.$this->getSqlHelper()->quote($currentName).' TO '.$this->getSqlHelper()->quote($newName));

		// handle auto increment: rename primary sequence for ID
		// properly we should check PRIMARY fields instead of ID: $aiName = $currentName.'_'.$fieldName, see createTable
		$aiName = $currentName;

		if ($this->queryScalar("SELECT 1 FROM user_sequences WHERE sequence_name=upper('".$this->getSqlHelper()->forSql('sq_'.$aiName)."')"))
		{
			// for fields excpet for ID here should be $newName.'_'.$fieldName, see createTable
			$newAiName = $newName;

			// rename sequence
			$this->query('RENAME '.$this->getSqlHelper()->quote('sq_'.$aiName).' TO '.$this->getSqlHelper()->quote('sq_'.$newAiName));

			// recreate trigger
			$this->query('DROP TRIGGER '.$this->getSqlHelper()->quote($aiName.'_insert'));

			$this->query('CREATE OR REPLACE TRIGGER '.$this->getSqlHelper()->quote($newAiName.'_insert').'
						BEFORE INSERT
						ON '.$this->getSqlHelper()->quote($newName).'
						FOR EACH ROW
							BEGIN
							IF :NEW.'.$this->getSqlHelper()->quote('ID').' IS NULL THEN
								SELECT '.$this->getSqlHelper()->quote('sq_'.$newAiName).'.NEXTVAL
									INTO :NEW.'.$this->getSqlHelper()->quote('ID').' FROM dual;
							END IF;
						END;'
			);
		}
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
				. ' ' . (in_array($columnName, $primary, true) ? 'NOT NULL' : 'NULL')
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

		// autoincrement field
		if (!empty($autoincrement))
		{
			foreach ($autoincrement as $autoincrementColumn)
			{
				if ($autoincrementColumn == 'ID')
				{
					// old-school hack
					$aiName = $tableName;
				}
				else
				{
					$aiName = $tableName.'_'.$autoincrementColumn;
				}

				$this->query('CREATE SEQUENCE '.$this->getSqlHelper()->quote('sq_'.$aiName));

				$this->query('CREATE OR REPLACE TRIGGER '.$this->getSqlHelper()->quote($aiName.'_insert').'
						BEFORE INSERT
						ON '.$this->getSqlHelper()->quote($tableName).'
						FOR EACH ROW
							BEGIN
							IF :NEW.'.$this->getSqlHelper()->quote($autoincrementColumn).' IS NULL THEN
								SELECT '.$this->getSqlHelper()->quote('sq_'.$aiName).'.NEXTVAL
									INTO :NEW.'.$this->getSqlHelper()->quote($autoincrementColumn).' FROM dual;
							END IF;
						END;'
				);
			}
		}
	}

	public function dropTable($tableName)
	{
		$this->query('DROP TABLE '.$this->getSqlHelper()->quote($tableName).' CASCADE CONSTRAINTS');

		// handle auto increment: delete primary sequence for ID
		// properly we should check PRIMARY fields instead of ID: $aiName = $currentName.'_'.$fieldName, see createTable
		$aiName = $tableName;

		if ($this->queryScalar("SELECT 1 FROM user_sequences WHERE sequence_name=upper('".$this->getSqlHelper()->forSql('sq_'.$aiName)."')"))
		{
			$this->query('DROP SEQUENCE '.$this->getSqlHelper()->quote('sq_'.$aiName));
		}
	}

	/*********************************************************
	 * Transaction
	 *********************************************************/

	public function startTransaction()
	{
		$this->transaction = OCI_DEFAULT;
	}

	public function commitTransaction()
	{
		$this->connectInternal();
		OCICommit($this->resource);
		$this->transaction = OCI_COMMIT_ON_SUCCESS;
	}

	public function rollbackTransaction()
	{
		$this->connectInternal();
		OCIRollback($this->resource);
		$this->transaction = OCI_COMMIT_ON_SUCCESS;
	}


	/*********************************************************
	 * Type, version, cache, etc.
	 *********************************************************/

	protected function getErrorMessage($resource = null)
	{
		if ($resource)
			$error = oci_error($resource);
		else
			$error = oci_error();

		if (!$error)
			return "";

		$result = sprintf("[%s] %s", $error["code"], $error["message"]);
		if (!empty($error["sqltext"]))
			$result .= sprintf(" (%s)", $error["sqltext"]);

		return $result;
	}

	public function getType()
	{
		return "oracle";
	}

	public function getVersion()
	{
		if ($this->version == null)
		{
			$version = $this->queryScalar('SELECT BANNER FROM v$version');
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

}
