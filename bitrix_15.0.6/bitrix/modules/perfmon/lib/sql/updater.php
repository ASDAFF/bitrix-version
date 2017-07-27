<?php
namespace Bitrix\Perfmon\Sql;

use Bitrix\Main\NotSupportedException;

class Updater
{
	protected $dbType = '';
	protected $delimiter = '';

	private  $conditions = array();

	/**
	 * Sets database type. Currently supported:
	 * - MYSQL
	 * - ORACLE
	 * - MSSQL
	 *
	 * @param string $dbType Database type.
	 *
	 * @return Updater
	 */
	public function setDbType($dbType = '')
	{
		$this->dbType = (string)$dbType;
		return $this;
	}

	/**
	 * Sets DDL delimiter for parsing.
	 *
	 * @param string $delimiter DDL statements delimiter.
	 *
	 * @return Updater
	 */
	public function setDelimiter($delimiter = '')
	{
		$this->delimiter = (string)$delimiter;
		return $this;
	}

	/**
	 * Produces updater code.
	 *
	 * @param string $sourceSql Source DDL statements.
	 * @param string $targetSql Target DDL statements.
	 *
	 * @return string
	 * @throws NotSupportedException
	 */
	public function generate($sourceSql, $targetSql)
	{
		$source = new Schema;
		$source->createFromString($sourceSql, $this->delimiter);

		$target = new Schema;
		$target->createFromString($targetSql, $this->delimiter);

		$diff = Compare::diff($source ,$target);
		if ($diff)
		{
			$sourceTables = $source->tables->getList();
			if ($sourceTables)
			{
				$tableCheck = array_shift($sourceTables);
			}
			else
			{
				$targetTables = $target->tables->getList();
				if ($targetTables)
				{
					$tableCheck = array_shift($targetTables);
				}
				else
				{
					$tableCheck = null;
				}
			}

			if (!$tableCheck)
				throw new NotSupportedException("no CHECK TABLE found.");
			return
				"if (\$updater->CanUpdateDatabase() && \$DB->TableExists('".EscapePHPString($tableCheck->name)."'))\n".
				"{\n".
				"\tif (\$DB->type == \"".EscapePHPString($this->dbType)."\")\n".
				"\t{\n".
				$this->handle($diff).
				"\t}\n".
				"}\n";
		}
		else
		{
			return "";
		}
	}

	/**
	 * @param array $diff Difference pairs.
	 *
	 * @return string
	 */
	protected function handle(array $diff)
	{
		$this->conditions = array();
		foreach ($diff as $pair)
		{
			if (!isset($pair[0]))
			{
				$this->handleCreate($pair[1]);
			}
			elseif (!isset($pair[1]))
			{
				$this->handleDrop($pair[0]);
			}
			else
			{
				$this->handleChange($pair[0], $pair[1]);
			}
		}

		$result = "";
		foreach ($this->conditions as $condition => $statements)
		{
			$result .= $condition;
			if ($condition)
				$result .= "\t\t{\n";
			$result .= implode("", $statements);
			if ($condition)
				$result .= "\t\t}\n";
		}

		return $result;
	}

	/**
	 * @param BaseObject $object Database schema object.
	 *
	 * @return void
	 */
	protected function handleCreate(BaseObject $object)
	{
		if ($object instanceof Sequence || $object instanceof Procedure)
		{
			$this->conditions[""][] = $this->multiLinePhp("\t\t\$DB->Query(\"", $object->getCreateDdl($this->dbType), "\", true);\n");
		}
		elseif ($object instanceof Table)
		{
			$this->conditions["\t\tif (!\$DB->TableExists(\"".EscapePHPString($object->name)."\"))\n"][] =
				$this->multiLinePhp("\t\t\t\$DB->Query(\"\n\t\t\t\t", str_replace("\n", "\n\t\t\t\t", $object->getCreateDdl($this->dbType)), "\n\t\t\t\");\n");
		}
		elseif ($object instanceof Column)
		{
			$this->conditions["\t\tif (\$DB->TableExists(\"".EscapePHPString($object->parent->name)."\"))\n"][] =
				"\t\t\tif (!\$DB->Query(\"SELECT ".EscapePHPString($object->name)." FROM ".EscapePHPString($object->parent->name)." WHERE 1=0\", true))\n".
				"\t\t\t{\n".
				$this->multiLinePhp("\t\t\t\t\$DB->Query(\"", $object->getCreateDdl($this->dbType), "\");\n").
				"\t\t\t}\n";
		}
		elseif ($object instanceof Index)
		{
			$this->conditions["\t\tif (\$DB->TableExists(\"".EscapePHPString($object->parent->name)."\"))\n"][] =
				"\t\t\tif (!\$DB->IndexExists(\"".EscapePHPString($object->parent->name)."\", array(".$this->multiLinePhp("\"", $object->columns, "\", ").")))\n".
				"\t\t\t{\n".
				$this->multiLinePhp("\t\t\t\t\$DB->Query(\"", $object->getCreateDdl($this->dbType), "\");\n").
				"\t\t\t}\n";
		}
		elseif ($object instanceof Trigger || $object instanceof Constraint)
		{
			$this->conditions["\t\tif (\$DB->TableExists(\"".EscapePHPString($object->parent->name)."\"))\n"][] =
				$this->multiLinePhp("\t\t\t\$DB->Query(\"", $object->getCreateDdl($this->dbType), "\", true);\n");
		}
		else
		{
			$this->conditions[""][] = "\t\t//create for ".get_class($object)." not supported yet\n";
		}
	}

	/**
	 * @param BaseObject $object Database schema object.
	 *
	 * @return void
	 */
	protected function handleDrop(BaseObject $object)
	{
		if ($object instanceof Sequence || $object instanceof Procedure)
		{
			$this->conditions[""][] = "\t\t\$DB->Query(\"".EscapePHPString($object->getDropDdl($this->dbType))."\", true);\n";
		}
		elseif ($object instanceof Table)
		{
			$this->conditions["\t\tif (\$DB->TableExists(\"".EscapePHPString($object->name)."\"))\n"][] =
				$this->multiLinePhp("\t\t\t\$DB->Query(\"", $object->getDropDdl($this->dbType), "\");\n");
		}
		elseif ($object instanceof Column)
		{
			$this->conditions["\t\tif (\$DB->TableExists(\"".EscapePHPString($object->parent->name)."\"))\n"][] =
				"\t\t\tif (\$DB->Query(\"SELECT ".EscapePHPString($object->name)." FROM ".EscapePHPString($object->parent->name)." WHERE 1=0\", true))\n".
				"\t\t\t{\n".
				$this->multiLinePhp("\t\t\t\t\$DB->Query(\"", $object->getDropDdl($this->dbType), "\");\n").
				"\t\t\t}\n";
		}
		elseif ($object instanceof Index)
		{
			$this->conditions["\t\tif (\$DB->TableExists(\"".EscapePHPString($object->parent->name)."\"))\n"][] =
				"\t\t\tif (\$DB->IndexExists(\"".EscapePHPString($object->parent->name)."\", array(".$this->multiLinePhp("\"", $object->columns, "\", ").")))\n".
				"\t\t\t{\n".
				$this->multiLinePhp("\t\t\t\t\$DB->Query(\"", $object->getDropDdl($this->dbType), "\");\n").
				"\t\t\t}\n";
		}
		elseif ($object instanceof Trigger || $object instanceof Constraint)
		{
			$this->conditions["\t\tif (\$DB->TableExists(\"".EscapePHPString($object->parent->name)."\"))\n"][] =
				$this->multiLinePhp("\t\t\t\$DB->Query(\"", $object->getDropDdl($this->dbType), "\", true);\n");
		}
		else
		{
			$this->conditions[""][] = "\t\t//drop for ".get_class($object)." not supported yet\n";
		}
	}

	/**
	 * @param BaseObject $source Source object.
	 * @param BaseObject $target Target object.
	 *
	 * @return void
	 */
	protected function handleChange(BaseObject $source, BaseObject $target)
	{
		if ($source instanceof Sequence || $source instanceof Procedure)
		{
			$this->conditions[""][] =
				$this->multiLinePhp("\t\t\$DB->Query(\"", $source->getDropDdl($this->dbType), "\", true);\n").
				$this->multiLinePhp("\t\t\$DB->Query(\"", $target->getCreateDdl($this->dbType), "\", true);\n");
		}
		elseif ($target instanceof Column)
		{
			$this->conditions["\t\tif (\$DB->TableExists(\"".EscapePHPString($source->parent->name)."\"))\n"][] =
				"\t\t\tif (\$DB->Query(\"SELECT ".EscapePHPString($source->name)." FROM ".EscapePHPString($source->parent->name)." WHERE 1=0\", true))\n".
				"\t\t\t{\n".
				$this->multiLinePhp("\t\t\t\t\$DB->Query(\"", $source->getModifyDdl($target, $this->dbType), "\");\n").
				"\t\t\t}\n";
		}
		elseif ($source instanceof Index)
		{
			$this->conditions["\t\tif (\$DB->TableExists(\"".EscapePHPString($source->parent->name)."\"))\n"][] =
				"\t\t\tif (\$DB->IndexExists(\"".EscapePHPString($source->parent->name)."\", array(".$this->multiLinePhp("\"", $source->columns, "\", ").")))\n".
				"\t\t\t{\n".
				$this->multiLinePhp("\t\t\t\t\$DB->Query(\"", $source->getDropDdl($this->dbType), "\");\n").
				$this->multiLinePhp("\t\t\t\t\$DB->Query(\"", $target->getCreateDdl($this->dbType), "\");\n").
				"\t\t\t}\n";
		}
		elseif ($source instanceof Trigger || $source instanceof Constraint)
		{
			$this->conditions["\t\tif (\$DB->TableExists(\"".EscapePHPString($source->parent->name)."\"))\n"][] =
				$this->multiLinePhp("\t\t\t\$DB->Query(\"", $source->getModifyDdl($target, $this->dbType), "\", true);\n");
		}
		else
		{
			$this->conditions[""][] = "\t\t//change for ".get_class($source)." not supported yet\n";
		}
	}

	/**
	 * Returns escaped php code repeated for body? prefixed with $prefix and suffixed with $suffix.
	 *
	 * @param string $prefix Prefix string for each from body.
	 * @param array|string $body Strings to be escaped.
	 * @param string $suffix Suffix string for each from body.
	 *
	 * @return string
	 */
	protected function multiLinePhp($prefix, $body, $suffix)
	{
		$result  = array();
		if (is_array($body))
		{
			foreach ($body as $line)
			{
				$result[] = $prefix.EscapePHPString($line).$suffix;
			}
		}
		else
		{
			$result[] = $prefix.EscapePHPString($body).$suffix;
		}
		return implode("", $result);
	}
}
