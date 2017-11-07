<?php
namespace Bitrix\Main\DB;

use Bitrix\Main;
use Bitrix\Main\Type;
use Bitrix\Main\Entity;

class MssqlSqlHelper extends SqlHelper
{
	/**
	 * Identificator escaping - left char
	 * @return string
	 */
	public function getLeftQuote()
	{
		return '[';
	}

	/**
	 * Identificator escaping - left char
	 * @return string
	 */
	public function getRightQuote()
	{
		return ']';
	}

	public function getAliasLength()
	{
		return 28;
	}

	public function getQueryDelimiter()
	{
		return "\nGO";
	}

	function forSql($value, $maxLength = 0)
	{
		return str_replace("\x00", "", ($maxLength > 0) ? str_replace("'", "''", substr($value, 0, $maxLength)) : str_replace("'", "''", $value));
	}

	public function getCurrentDateTimeFunction()
	{
		return "GETDATE()";
	}

	public function getSubstrFunction($str, $from, $length = null)
	{
		$sql = 'SUBSTRING('.$str.', '.$from;

		if (!is_null($length))
			$sql .= ', '.$length;
		else
			$sql .= ', LEN('.$str.') + 1 - '.$from;

		return $sql.')';
	}

	public function getCurrentDateFunction()
	{
		return "convert(datetime, cast(year(getdate()) as varchar(4)) + '-' + cast(month(getdate()) as varchar(2)) + '-' + cast(day(getdate()) as varchar(2)), 120)";
	}

	public function addSecondsToDateTime($seconds, $from = null)
	{
		if ($from === null)
		{
			$from = static::getCurrentDateTimeFunction();
		}

		return 'DATEADD(second, '.$seconds.', '.$from.')';
	}

	public function getDatetimeToDateFunction($value)
	{
		return 'DATEADD(dd, DATEDIFF(dd, 0, '.$value.'), 0)';
	}

	public function formatDate($format, $field = null)
	{
		if ($field === null)
		{
			return '';
		}

		$result = array();

		foreach (preg_split("#(YYYY|MMMM|MM|MI|M|DD|HH|H|GG|G|SS|TT|T)#", $format, -1, PREG_SPLIT_DELIM_CAPTURE) as $part)
		{
			switch ($part)
			{
				case "YYYY":
					$result[] = "\n\tCONVERT(varchar(4),DATEPART(yyyy, $field))";
					break;
				case "MMMM":
					$result[] = "\n\tdatename(mm, $field)";
					break;
				case "MM":
					$result[] = "\n\tREPLICATE('0',2-LEN(DATEPART(mm, $field)))+CONVERT(varchar(2),DATEPART(mm, $field))";
					break;
				case "MI":
					$result[] = "\n\tREPLICATE('0',2-LEN(DATEPART(mi, $field)))+CONVERT(varchar(2),DATEPART(mi, $field))";
					break;
				case "M":
					$result[] = "\n\tCONVERT(varchar(3), $field,7)";
					break;
				case "DD":
					$result[] = "\n\tREPLICATE('0',2-LEN(DATEPART(dd, $field)))+CONVERT(varchar(2),DATEPART(dd, $field))";
					break;
				case "HH":
					$result[] = "\n\tREPLICATE('0',2-LEN(DATEPART(hh, $field)))+CONVERT(varchar(2),DATEPART(hh, $field))";
					break;
				case "H":
					$result[] = "\n\tCASE WHEN DATEPART(HH, $field) < 13 THEN RIGHT(REPLICATE('0',2) + CAST(datepart(HH, $field) AS VARCHAR(2)),2) ELSE RIGHT(REPLICATE('0',2) + CAST(datepart(HH, dateadd(HH, -12, $field)) AS VARCHAR(2)), 2) END";
					break;
				case "GG":
					$result[] = "\n\tREPLICATE('0',2-LEN(DATEPART(hh, $field)))+CONVERT(varchar(2),DATEPART(hh, $field))";
					break;
				case "G":
					$result[] = "\n\tCASE WHEN DATEPART(HH, $field) < 13 THEN RIGHT(REPLICATE('0',2) + CAST(datepart(HH, $field) AS VARCHAR(2)),2) ELSE RIGHT(REPLICATE('0',2) + CAST(datepart(HH, dateadd(HH, -12, $field)) AS VARCHAR(2)), 2) END";
					break;
				case "SS":
					$result[] = "\n\tREPLICATE('0',2-LEN(DATEPART(ss, $field)))+CONVERT(varchar(2),DATEPART(ss, $field))";
					break;
				case "TT":
					$result[] = "\n\tCASE WHEN DATEPART(HH, $field) < 12 THEN 'AM' ELSE 'PM' END";
					break;
				case "T":
					$result[] = "\n\tCASE WHEN DATEPART(HH, $field) < 12 THEN 'AM' ELSE 'PM' END";
					break;
				default:
					$result[] = "'".$this->forSql($part)."'";
					break;
			}
		}

		return join("+", $result);
	}

	public function getConcatFunction()
	{
		$str = "";
		$ar = func_get_args();
		if (is_array($ar))
			$str .= implode(" + ", $ar);
		return $str;
	}

	public function getIsNullFunction($expression, $result)
	{
		return "ISNULL(".$expression.", ".$result.")";
	}

	public function getLengthFunction($field)
	{
		return "LEN(".$field.")";
	}

	public function getCharToDateFunction($value)
	{
		return "CONVERT(datetime, '".$value."', 120)";
	}

	public function getDateToCharFunction($fieldName)
	{
		return "CONVERT(varchar(19), ".$fieldName.", 120)";
	}

	public function convertFromDb($value, Entity\ScalarField $field)
	{
		if($value !== null)
		{
			if($field instanceof Entity\DatetimeField)
			{
				$value = new Type\DateTime(substr($value, 0, 19), "Y-m-d H:i:s");
			}
			elseif($field instanceof Entity\DateField)
			{
				$value = new Type\Date($value, "Y-m-d");
			}
			elseif($field instanceof Entity\StringField)
			{
				if((strlen($value) == 19) && preg_match("#^\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2}$#", $value))
				{
					$value = new Type\DateTime($value, "Y-m-d H:i:s");
				}
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
			return 'float';
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

	public function getFieldByColumnType($name, $type, array $parameters = null)
	{
		switch($type)
		{
			case 4:
			case 5:
			case -6:
				//int SQL_INTEGER (4)
				//smallint SQL_SMALLINT (5)
				//tinyint SQL_TINYINT (-6)
				return new Entity\IntegerField($name);

			case 2:
			case 3:
			case 6:
			case 7:
				//numeric SQL_NUMERIC (2)
				//decimal SQL_DECIMAL (3)
				//smallmoney SQL_DECIMAL (3)
				//money SQL_DECIMAL (3)
				//float SQL_FLOAT (6)
				//real SQL_REAL (7)
				return new Entity\FloatField($name, array("scale" => $parameters["scale"]));

			case 93:
				//datetime - SQL_TYPE_TIMESTAMP (93)
				//datetime2 - SQL_TYPE_TIMESTAMP (93)
				//smalldatetime - SQL_TYPE_TIMESTAMP (93)
				return new Entity\DatetimeField($name);

			case 91:
				//date - SQL_TYPE_DATE (91)
				return new Entity\DateField($name);
		}
		//bigint SQL_BIGINT (-5)
		//binary SQL_BINARY (-2)
		//bit SQL_BIT (-7)
		//char SQL_CHAR (1)
		//datetimeoffset SQL_SS_TIMESTAMPOFFSET (-155)
		//image SQL_LONGVARBINARY (-4)
		//nchar SQL_WCHAR (-8)
		//ntext SQL_WLONGVARCHAR (-10)
		//nvarchar SQL_WVARCHAR (-9)
		//text SQL_LONGVARCHAR (-1)
		//time SQL_SS_TIME2 (-154)
		//timestamp SQL_BINARY (-2)
		//udt SQL_SS_UDT (-151)
		//uniqueidentifier SQL_GUID (-11)
		//varbinary SQL_VARBINARY (-3)
		//varchar SQL_VARCHAR (12)
		//xml SQL_SS_XML (-152)
		return new Entity\StringField($name, array("size" => $parameters["size"]));
	}

	public function getTopSql($sql, $limit, $offset = 0)
	{
		$offset = intval($offset);
		$limit = intval($limit);

		if ($offset > 0 && $limit <= 0)
			throw new Main\ArgumentException("Limit must be set if offset is set");

		if ($limit > 0)
		{
			if ($offset <= 0)
			{
				$sql = preg_replace("/^\\s*SELECT/i", "SELECT TOP ".$limit, $sql);
			}
			else
			{
				$orderBy = '';
				$sqlTmp = $sql;

				preg_match_all("#\\sorder\\s+by\\s#i", $sql, $matches, PREG_OFFSET_CAPTURE);
				if (isset($matches[0]) && is_array($matches[0]) && count($matches[0]) > 0)
				{
					$idx = $matches[0][count($matches[0]) - 1][1];
					$s = substr($sql, $idx);
					if (substr_count($s, '(') === substr_count($s, ')'))
					{
						$orderBy = $s;
						$sqlTmp = substr($sql, 0, $idx);
					}
				}

				if ($orderBy === '')
				{
					$orderBy = "ORDER BY (SELECT 1)";
					$sqlTmp = $sql;
				}

				$sqlTmp = preg_replace(
					"/^\\s*SELECT/i",
					"SELECT ROW_NUMBER() OVER (".$orderBy.") AS ROW_NUMBER_ALIAS,",
					$sqlTmp
				);

				$sql =
					"WITH ROW_NUMBER_QUERY_ALIAS AS (".$sqlTmp.") ".
					"SELECT * ".
					"FROM ROW_NUMBER_QUERY_ALIAS ".
					"WHERE ROW_NUMBER_ALIAS BETWEEN ".$offset." AND ".($offset + $limit - 1)."";
			}
		}
		return $sql;
	}
}
