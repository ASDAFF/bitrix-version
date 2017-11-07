<?php
namespace Bitrix\Main\DB;

use Bitrix\Main;
use Bitrix\Main\Type;
use Bitrix\Main\Entity;

class OracleSqlHelper extends SqlHelper
{
	/**
	 * Identificator escaping - left char
	 * @return string
	 */
	public function getLeftQuote()
	{
		return '"';
	}

	/**
	 * Identificator escaping - left char
	 * @return string
	 */
	public function getRightQuote()
	{
		return '"';
	}

	public function getAliasLength()
	{
		return 30;
	}

	public function quote($identifier)
	{
		return parent::quote(strtoupper($identifier));
	}

	public function getQueryDelimiter()
	{
		return "(?<!\\*)/(?!\\*)";
	}

	function forSql($value, $maxLength = 0)
	{
		if ($maxLength <= 0 || $maxLength > 2000)
			$maxLength = 2000;

		$value = substr($value, 0, $maxLength);

		if (\Bitrix\Main\Application::isUtfMode())
		{
			// From http://w3.org/International/questions/qa-forms-utf-8.html
			// This one can crash php with segmentation fault on large input data (over 20K)
			// https://bugs.php.net/bug.php?id=60423
			if (preg_match_all('%(
				[\x00-\x7E]                        # ASCII
				|[\xC2-\xDF][\x80-\xBF]            # non-overlong 2-byte
				|\xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
				|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte
				|\xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
				|\xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
				|[\xF1-\xF3][\x80-\xBF]{3}         # planes 4-15
				|\xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
			)+%x', $value, $match))
				$value = implode(' ', $match[0]);
			else
				return ''; //There is no valid utf at all
		}

		return str_replace("'", "''", $value);
	}

	public function getCurrentDateTimeFunction()
	{
		return "SYSDATE";
	}

	public function getCurrentDateFunction()
	{
		return "TRUNC(SYSDATE)";
	}

	public function addSecondsToDateTime($seconds, $from = null)
	{
		if ($from === null)
		{
			$from = static::getCurrentDateTimeFunction();
		}

		return '('.$from.'+'.$seconds.'/86400)';
	}

	public function getDatetimeToDateFunction($value)
	{
		return 'TRUNC('.$value.')';
	}

	public function formatDate($format, $field = null)
	{
		$format = str_replace("HH", "HH24", $format);
		$format = str_replace("GG", "HH24", $format);

		if (strpos($format, 'HH24') === false)
		{
			$format = str_replace("H", "HH", $format);
		}

		$format = str_replace("G", "HH", $format);

		$format = str_replace("MI", "II", $format);

		if (strpos($format, 'MMMM') !== false)
		{
			$format = str_replace("MMMM", "MONTH", $format);
		}
		elseif (strpos($format, 'MM') === false)
		{
			$format = str_replace("M", "MON", $format);
		}

		$format = str_replace("II", "MI", $format);

		$format = str_replace("TT", "AM", $format);
		$format = str_replace("T", "AM", $format);

		if($field === false)
		{
			return $format;
		}
		else
		{
			return "TO_CHAR(".$field.", '".$format."')";
		}
	}

	public function getConcatFunction()
	{
		$str = "";
		$ar = func_get_args();
		if (is_array($ar))
			$str .= implode(" || ", $ar);
		return $str;
	}

	public function getIsNullFunction($expression, $result)
	{
		return "NVL(".$expression.", ".$result.")";
	}

	public function getLengthFunction($field)
	{
		return "LENGTH(".$field.")";
	}

	public function getCharToDateFunction($value)
	{
		return "TO_DATE('".$value."', 'YYYY-MM-DD HH24:MI:SS')";
	}

	public function getDateToCharFunction($fieldName)
	{
		return "TO_CHAR(".$fieldName.", 'YYYY-MM-DD HH24:MI:SS')";
	}

	/**
	 * Performs additional processing of CLOB fields.
	 *
	 * @param Entity\ScalarField[] $tableFields Table fields
	 * @param array $fields Data fields
	 * @return array
	 */
	protected function prepareBinds(array $tableFields, array $fields)
	{
		$binds = array();

		foreach($tableFields as $columnName => $tableField)
		{
			if(isset($fields[$columnName]) && !($fields[$columnName] instanceof SqlExpression))
			{
				if($tableField instanceof Entity\TextField && $fields[$columnName] <> '')
				{
					$binds[$columnName] = $fields[$columnName];
				}
			}
		}

		return $binds;
	}

	public function convertFromDb($value, Entity\ScalarField $field)
	{
		if($value !== null)
		{
			if($field instanceof Entity\DatetimeField)
			{
				if(strlen($value) == 19)
				{
					//preferable format: NLS_DATE_FORMAT='YYYY-MM-DD HH24:MI:SS'
					$value = new Type\DateTime($value, "Y-m-d H:i:s");
				}
				else
				{
					//default Oracle date format: 03-MAR-14
					$value = new Type\DateTime($value." 00:00:00", "d-M-y H:i:s");
				}
			}
			elseif($field instanceof Entity\TextField)
			{
				if (is_object($value))
				{
					/** @var \OCI_Lob $value */
					$value = $value->load();
				}
			}
			elseif($field instanceof Entity\StringField)
			{
				if ((strlen($value) == 19) && preg_match("#^\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2}$#", $value))
				{
					$value = new Type\DateTime($value, "Y-m-d H:i:s");
				}
			}
		}

		return $value;
	}

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
		elseif($field instanceof Entity\TextField)
		{
			if (empty($value))
			{
				$result = "NULL";
			}
			else
			{
				$result = "EMPTY_CLOB()";
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

	public function getColumnTypeByField(Entity\ScalarField $field)
	{
		if ($field instanceof Entity\IntegerField)
		{
			return 'number(18)';
		}
		elseif ($field instanceof Entity\FloatField)
		{
			$scale = $field->getScale();
			return 'number'.($scale !== null? "(*,".$scale.")":"");
		}
		elseif ($field instanceof Entity\DatetimeField)
		{
			return 'date';
		}
		elseif ($field instanceof Entity\DateField)
		{
			return 'date';
		}
		elseif ($field instanceof Entity\TextField)
		{
			return 'clob';
		}
		elseif ($field instanceof Entity\BooleanField)
		{
			$values = $field->getValues();

			if (ctype_digit($values[0]) && ctype_digit($values[1]))
			{
				return 'number(1)';
			}
			else
			{
				return 'varchar2('.max(strlen($values[0]), strlen($values[1])).' char)';
			}
		}
		elseif ($field instanceof Entity\EnumField)
		{
			return 'varchar2('.max(array_map('strlen', $field->getValues())).' char)';
		}
		else
		{
			// string by default
			return 'varchar2(255 char)';
		}
	}

	public function getFieldByColumnType($name, $type, array $parameters = null)
	{
		switch($type)
		{
			case "DATE":
				return new Entity\DatetimeField($name);

			case "NCLOB":
			case "CLOB":
			case "BLOB":
				return new Entity\TextField($name);

			case "FLOAT":
			case "BINARY_FLOAT":
			case "BINARY_DOUBLE":
				return new Entity\FloatField($name);

			case "NUMBER":
				if($parameters["precision"] == '' && $parameters["scale"] == '')
				{
					//NUMBER
					return new Entity\FloatField($name);
				}
				if(intval($parameters["scale"]) <= 0)
				{
					//NUMBER(18)
					//NUMBER(18,-2)
					return new Entity\IntegerField($name);
				}
				//NUMBER(*,2)
				return new Entity\FloatField($name, array("scale" => $parameters["scale"]));
		}
		//LONG
		//VARCHAR2(size [BYTE | CHAR])
		//NVARCHAR2(size)
		//TIMESTAMP [(fractional_seconds_precision)]
		//TIMESTAMP [(fractional_seconds)] WITH TIME ZONE
		//TIMESTAMP [(fractional_seconds)] WITH LOCAL TIME ZONE
		//INTERVAL YEAR [(year_precision)] TO MONTH
		//INTERVAL DAY [(day_precision)] TO SECOND [(fractional_seconds)]
		//RAW(size)
		//LONG RAW
		//ROWID
		//UROWID [(size)]
		//CHAR [(size [BYTE | CHAR])]
		//NCHAR[(size)]
		//BFILE
		return new Entity\StringField($name, array("size" => $parameters["size"]));
	}

	public function getTopSql($sql, $limit, $offset = 0)
	{
		$offset = intval($offset);
		$limit = intval($limit);

		if ($offset > 0 && $limit <= 0)
			throw new \Bitrix\Main\ArgumentException("Limit must be set if offset is set");

		if ($limit > 0)
		{
			if ($offset <= 0)
			{
				$sql =
					"SELECT * ".
					"FROM (".$sql.") ".
					"WHERE ROWNUM <= ".$limit;
			}
			else
			{
				$sql =
					"SELECT * ".
					"FROM (".
					"   SELECT rownum_query_alias.*, ROWNUM rownum_alias ".
					"   FROM (".$sql.") rownum_query_alias ".
					"   WHERE ROWNUM <= ".($offset + $limit - 1)." ".
					") ".
					"WHERE rownum_alias >= ".$offset;
			}
		}
		return $sql;
	}

	public function getAscendingOrder()
	{
		return 'ASC NULLS FIRST';
	}

	public function getDescendingOrder()
	{
		return 'DESC NULLS LAST';
	}
}
