<?php
namespace Bitrix\Main\DB;

use Bitrix\Main;
use Bitrix\Main\Type;
use Bitrix\Main\Entity;

abstract class MysqlCommonSqlHelper extends SqlHelper
{
	/**
	 * Identificator escaping - left char
	 * @return string
	 */
	public function getLeftQuote()
	{
		return '`';
	}

	/**
	 * Identificator escaping - left char
	 * @return string
	 */
	public function getRightQuote()
	{
		return '`';
	}

	public function getQueryDelimiter()
	{
		return ';';
	}

	public function getAliasLength()
	{
		return 256;
	}

	public function getCurrentDateTimeFunction()
	{
		return "NOW()";
	}

	public function getCurrentDateFunction()
	{
		return "CURDATE()";
	}

	public function addSecondsToDateTime($seconds, $from = null)
	{
		if ($from === null)
		{
			$from = static::getCurrentDateTimeFunction();
		}

		return 'DATE_ADD('.$from.', INTERVAL '.$seconds.' SECOND)';
	}

	public function getConcatFunction()
	{
		$str = "";
		$ar = func_get_args();
		if (is_array($ar))
			$str .= implode(", ", $ar);
		if (strlen($str) > 0)
			$str = "CONCAT(".$str.")";
		return $str;
	}

	public function getIsNullFunction($expression, $result)
	{
		return "IFNULL(".$expression.", ".$result.")";
	}

	public function getLengthFunction($field)
	{
		return "LENGTH(".$field.")";
	}

	public function getCharToDateFunction($value)
	{
		return "'".$value."'";
	}

	public function getDateToCharFunction($fieldName)
	{
		return $fieldName;
	}

	public function getDatetimeToDateFunction($value)
	{
		return 'DATE('.$value.')';
	}

	public function formatDate($format, $field = null)
	{
		static $search  = array(
			"YYYY",
			"MMMM",
			"MM",
			"MI",
			"DD",
			"HH",
			"GG",
			"G",
			"SS",
			"TT",
			"T"
		);
		static $replace = array(
			"%Y",
			"%M",
			"%m",
			"%i",
			"%d",
			"%H",
			"%h",
			"%l",
			"%s",
			"%p",
			"%p"
		);

		foreach ($search as $k=>$v)
		{
			$format = str_replace($v, $replace[$k], $format);
		}

		if (strpos($format, '%H') === false)
		{
			$format = str_replace("H", "%h", $format);
		}

		if (strpos($format, '%M') === false)
		{
			$format = str_replace("M", "%b", $format);
		}

		if($field === null)
		{
			return $format;
		}
		else
		{
			return "DATE_FORMAT(".$field.", '".$format."')";
		}
	}

	public function convertFromDb($value, Entity\ScalarField $field)
	{
		if($value !== null)
		{
			if($field instanceof Entity\DatetimeField)
			{
				$value = new Type\DateTime($value, "Y-m-d H:i:s");
			}
			elseif($field instanceof Entity\DateField)
			{
				$value = new Type\Date($value, "Y-m-d");
			}
		}

		return $value;
	}

	public function getColumnTypeByField(Entity\ScalarField $field)
	{
		if ($field instanceof Entity\IntegerField)
		{
			return 'int';
		}
		elseif ($field instanceof Entity\FloatField)
		{
			return 'double';
		}
		elseif ($field instanceof Entity\DatetimeField)
		{
			return 'datetime';
		}
		elseif ($field instanceof Entity\DateField)
		{
			return 'date';
		}
		elseif ($field instanceof Entity\TextField)
		{
			return 'text';
		}
		elseif ($field instanceof Entity\BooleanField)
		{
			$values = $field->getValues();

			if (ctype_digit($values[0]) && ctype_digit($values[1]))
			{
				return 'int';
			}
			else
			{
				return 'varchar('.max(strlen($values[0]), strlen($values[1])).')';
			}
		}
		elseif ($field instanceof Entity\EnumField)
		{
			return 'varchar('.max(array_map('strlen', $field->getValues())).')';
		}
		else
		{
			// string by default
			return 'varchar(255)';
		}
	}

	public function getTopSql($sql, $limit, $offset = 0)
	{
		$offset = intval($offset);
		$limit = intval($limit);

		if ($offset > 0 && $limit <= 0)
			throw new \Bitrix\Main\ArgumentException("Limit must be set if offset is set");

		if ($limit > 0)
		{
			$sql .= "\nLIMIT ".$offset.", ".$limit."\n";
		}

		return $sql;
	}
}
