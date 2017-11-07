<?php
namespace Bitrix\Main\DB;

use Bitrix\Main;
use Bitrix\Main\Type;
use Bitrix\Main\Entity;

abstract class SqlHelper
{
	protected $connection;

	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}

	/**
	 * Identificator escaping - left char
	 * @return string
	 */
	public function getLeftQuote()
	{
		return '';
	}

	/**
	 * Identificator escaping - left char
	 * @return string
	 */
	public function getRightQuote()
	{
		return '';
	}

	/**
	 * Returns maximum length of an alias in a select statement
	 * @return int
	 */
	abstract public function getAliasLength();

	/**
	 * @param $identifier string Table or Column name
	 * @return string Quoted identifier, e.g. `TITLE` for MySQL
	 */
	public function quote($identifier)
	{
		// security unshielding
		$identifier = str_replace(array($this->getLeftQuote(), $this->getRightQuote()), '', $identifier);

		// shield [[database.]tablename.]columnname
		if (strpos($identifier, '.') !== false)
		{
			$identifier = str_replace('.', $this->getRightQuote() . '.' . $this->getLeftQuote(), $identifier);
		}

		// shield general borders
		return $this->getLeftQuote() . $identifier . $this->getRightQuote();
	}

	abstract public function getQueryDelimiter();
	abstract public function forSql($value, $maxLength = 0);
	abstract public function getCharToDateFunction($value);
	abstract public function getDateToCharFunction($fieldName);
	abstract public function getCurrentDateTimeFunction();
	abstract public function getCurrentDateFunction();
	abstract public function addSecondsToDateTime($seconds, $from = null);
	abstract public function getDatetimeToDateFunction($value);
	abstract public function formatDate($format, $field = null);
	abstract public function getConcatFunction();
	abstract public function getIsNullFunction($expression, $result);
	abstract public function getLengthFunction($field);
	abstract public function getTopSql($sql, $limit, $offset = 0);

	public function getSubstrFunction($str, $from, $length = null)
	{
		$sql = 'SUBSTR('.$str.', '.$from;

		if (!is_null($length))
			$sql .= ', '.$length;

		return $sql.')';
	}

	/**
	 * Builds the strings for the SQL INSERT command for the given table.
	 *
	 * @param string $tableName A table name
	 * @param array $fields array("column" => $value)[]
	 *
	 * @return array (columnList, valueList, binds)
	 */
	public function prepareInsert($tableName, array $fields)
	{
		$columns = array();
		$values = array();

		$tableFields = $this->connection->getTableFields($tableName);

		foreach($tableFields as $columnName => $tableField)
		{
			if(isset($fields[$columnName]) || array_key_exists($columnName, $fields))
			{
				$columns[] = $this->quote($columnName);
				$values[] = $this->convertToDb($fields[$columnName], $tableField);
			}
		}

		$binds = $this->prepareBinds($tableFields, $fields);

		return array(
			implode(", ", $columns),
			implode(", ", $values),
			$binds
		);
	}

	/**
	 * Builds the strings for the SQL UPDATE command for the given table.
	 *
	 * @param string $tableName A table name
	 * @param array $fields array("column" => $value)[]
	 *
	 * @return array (update, binds)
	 */
	public function prepareUpdate($tableName, array $fields)
	{
		$update = array();

		$tableFields = $this->connection->getTableFields($tableName);

		foreach($tableFields as $columnName => $tableField)
		{
			if(isset($fields[$columnName]) || array_key_exists($columnName, $fields))
			{
				$update[] = $this->quote($columnName).' = '.$this->convertToDb($fields[$columnName], $tableField);
			}
		}

		$binds = $this->prepareBinds($tableFields, $fields);

		return array(
			implode(", ", $update),
			$binds
		);
	}

	protected function prepareBinds(array $tableFields, array $fields)
	{
		return array();
	}

	/**
	 * Builds the string for the SQL assignment operation of the given column.
	 *
	 * @param string $tableName A table name
	 * @param string $columnName A column name
	 * @param string $value A value to assign
	 * @return string
	 */
	public function prepareAssignment($tableName, $columnName, $value)
	{
		$tableField = $this->connection->getTableField($tableName, $columnName);

		return $this->quote($columnName).' = '.$this->convertToDb($value, $tableField);
	}

	/**
	 * Converts values to the string according to the column type to use it in a SQL query.
	 *
	 * @param mixed $value i.e. Type\Date or string
	 * @param Entity\ScalarField $field
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @return string Value to write to column
	 */
	public function convertToDb($value, Entity\ScalarField $field)
	{
		if ($value === null)
		{
			return "NULL";
		}

		if ($value instanceof SqlExpression)
		{
			return $value->compile();
		}

		if($field instanceof Entity\DatetimeField)
		{
			if (empty($value))
			{
				$result = "NULL";
			}
			elseif($value instanceof Type\Date)
			{
				if($value instanceof Type\DateTime)
				{
					$value = clone($value);
					$value->setDefaultTimeZone();
				}
				$result = $this->getCharToDateFunction($value->format("Y-m-d H:i:s"));
			}
			else
			{
				throw new Main\ArgumentTypeException('value', '\Bitrix\Main\Type\Date');
			}
		}
		elseif($field instanceof Entity\DateField)
		{
			if (empty($value))
			{
				$result = "NULL";
			}
			elseif($value instanceof Type\Date)
			{
				$result = $this->getCharToDateFunction($value->format("Y-m-d"));
			}
			else
			{
				throw new Main\ArgumentTypeException('value', '\Bitrix\Main\Type\Date');
			}
		}
		elseif($field instanceof Entity\IntegerField)
		{
			$result = "'".intval($value)."'";
		}
		elseif($field instanceof Entity\FloatField)
		{
			if(($scale = $field->getScale()) !== null)
			{
				$result = "'".round(doubleval($value), $scale)."'";
			}
			else
			{
				$result = "'".doubleval($value)."'";
			}
		}
		elseif($field instanceof Entity\StringField)
		{
			$result = "'".$this->forSql($value, $field->getSize())."'";
		}
		else
		{
			$result = "'".$this->forSql($value)."'";
		}

		return $result;
	}

	/**
	 * Converts string values from database to more complex types according to the column type.
	 *
	 * @param string $value
	 * @param Entity\ScalarField $field
	 * @return mixed
	 */
	abstract public function convertFromDb($value, Entity\ScalarField $field);

	/**
	 * Returns a column type according to ScalarField object.
	 *
	 * @param Entity\ScalarField $field
	 * @return string 'int', 'varchar(255)' etc.
	 */
	abstract public function getColumnTypeByField(Entity\ScalarField $field);

	/**
	 * Creates an object according to a column type.
	 *
	 * @param string $name A field name
	 * @param mixed $type A type of the field (as returned by database-specific functions)
	 * @param array $parameters Additional column information
	 * @return Entity\ScalarField Object of ScalarField subclass
	 */
	abstract public function getFieldByColumnType($name, $type, array $parameters = null);

	/**
	 * Returns ascending order specifier for ORDER BY clause
	 * @return string
	 */
	public function getAscendingOrder()
	{
		return 'ASC';
	}

	/**
	 * Returns descending order specifier for ORDER BY clause
	 * @return string
	 */
	public function getDescendingOrder()
	{
		return 'DESC';
	}
}
