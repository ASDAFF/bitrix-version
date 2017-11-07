<?php
namespace Bitrix\Main\DB;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity;

abstract class MysqlCommonConnection extends Connection
{
	/*********************************************************
	 * DDL
	 *********************************************************/

	/**
	 * @param string $tableName
	 * @return array
	 */
	public function getTableFields($tableName)
	{
		if (!isset($this->tableColumnsCache[$tableName]))
		{
			$this->connectInternal();

			$query = $this->queryInternal("SELECT * FROM ".$this->getSqlHelper()->quote($tableName)." LIMIT 0");

			$result = $this->createResult($query);

			$this->tableColumnsCache[$tableName] = $result->getFields();
		}
		return $this->tableColumnsCache[$tableName];
	}

	public function isTableExists($tableName)
	{
		$tableName = preg_replace("/[^a-z0-9%_]+/i", "", $tableName);
		$tableName = trim($tableName);

		if (strlen($tableName) <= 0)
		{
			return false;
		}

		$dbResult = $this->query("SHOW TABLES LIKE '".$this->getSqlHelper()->forSql($tableName)."'");

		return (bool) $dbResult->fetch();
	}

	public function isIndexExists($tableName, array $arColumns)
	{
		return $this->getIndexName($tableName, $arColumns) !== null;
	}

	public function getIndexName($tableName, array $arColumns, $strict = false)
	{
		if (!is_array($arColumns) || count($arColumns) <= 0)
			return null;

		$tableName = preg_replace("/[^a-z0-9_]+/i", "", $tableName);
		$tableName = trim($tableName);

		$rs = $this->query("SHOW INDEX FROM `".$this->getSqlHelper()->forSql($tableName)."`");
		if (!$rs)
			return null;

		$arIndexes = array();
		while ($ar = $rs->fetch())
			$arIndexes[$ar["Key_name"]][$ar["Seq_in_index"] - 1] = $ar["Column_name"];

		$strColumns = implode(",", $arColumns);
		foreach ($arIndexes as $Key_name => $arKeyColumns)
		{
			ksort($arKeyColumns);
			$strKeyColumns = implode(",", $arKeyColumns);
			if ($strict)
			{
				if ($strKeyColumns === $strColumns)
					return $Key_name;
			}
			else
			{
				if (substr($strKeyColumns, 0, strlen($strColumns)) === $strColumns)
					return $Key_name;
			}
		}

		return null;
	}

	public function renameTable($currentName, $newName)
	{
		$this->query('RENAME TABLE '.$this->getSqlHelper()->quote($currentName).' TO '.$this->getSqlHelper()->quote($newName));
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
				. ' NOT NULL' // null for oracle if is not primary
				. (in_array($columnName, $autoincrement, true) ? ' AUTO_INCREMENT' : '')
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
		$this->query("START TRANSACTION");
	}

	public function commitTransaction()
	{
		$this->query("COMMIT");
	}

	public function rollbackTransaction()
	{
		$this->query("ROLLBACK");
	}
}