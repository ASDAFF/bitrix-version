<?php
namespace Bitrix\Main\DB;

use Bitrix\Main\Entity;

class MysqlSqlHelper extends MysqlCommonSqlHelper
{
	public function forSql($value, $maxLength = 0)
	{
		if ($maxLength > 0)
			$value = substr($value, 0, $maxLength);

		return mysql_real_escape_string($value, $this->connection->getResource());
	}

	public function getFieldByColumnType($name, $type, array $parameters = null)
	{
		switch ($type)
		{
			case "int":
				return new Entity\IntegerField($name);

			case "real":
				return new Entity\FloatField($name);

			case "datetime":
			case "timestamp":
				return new Entity\DatetimeField($name);

			case "date":
				return new Entity\DateField($name);
		}
		return new Entity\StringField($name);
	}
}
