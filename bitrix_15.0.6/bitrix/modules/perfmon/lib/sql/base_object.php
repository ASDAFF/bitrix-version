<?php
namespace Bitrix\Perfmon\Sql;

/**
 * Class BaseObject
 * Base class for all schema objects such as tables, columns, indexes, etc.
 * @package Bitrix\Perfmon\Sql
 */
abstract class BaseObject
{
	/** @var BaseObject|null */
	public $parent = null;
	public $name = '';
	public $body = '';
	protected $ciName = '';

	/**
	 * @param string $name Name of the table.
	 */
	function __construct($name = '')
	{
		$this->name = (string)$name;
		$this->ciName = $this->getCompareName($this->name);
	}

	/**
	 * Sets source code for object.
	 *
	 * @param string $body The body.
	 *
	 * @return BaseObject
	 */
	public function setBody($body)
	{
		$this->body = trim($body);
		return $this;
	}

	/**
	 * Sets parent for object.
	 * <p>
	 * For example Table for Column.
	 *
	 * @param BaseObject $parent Parent object.
	 *
	 * @return BaseObject
	 */
	public function setParent(BaseObject $parent = null)
	{
		$this->parent = $parent;
		return $this;
	}

	/**
	 * Returns "normalized" name of the table.
	 * <p>
	 * If name is not quoted then it made uppercase.
	 *
	 * @param string $name Table name.
	 * @return string
	 */
	final public static function getCompareName($name)
	{
		if ($name[0] == '`')
			return $name;
		elseif ($name[0] == '"')
			return $name;
		elseif ($name[0] == '[')
			return $name;
		else
			return strtoupper($name);
	}

	/**
	 * Compares name of the table with given.
	 * <p>
	 * If name has no quotes when comparison is case insensitive.
	 *
	 * @param string $name Table name to compare.
	 * @return int
	 * @see strcmp
	 */
	final public function compareName($name)
	{
		return strcmp($this->ciName, $this->getCompareName($name));
	}

	/**
	 * Return DDL or commentary for object creation.
	 *
	 * @param string $dbType Database type.
	 *
	 * @return array|string
	 */
	public function getCreateDdl($dbType = '')
	{
		return "// ".get_class($this).":getCreateDdl not implemented";
	}

	/**
	 * Return DDL or commentary for object destruction.
	 *
	 * @param string $dbType Database type.
	 *
	 * @return array|string
	 */
	public function getDropDdl($dbType = '')
	{
		return "// ".get_class($this).":getDropDdl not implemented";
	}

	/**
	 * Return DDL or commentary for object modification.
	 *
	 * @param BaseObject $target Target object.
	 * @param string $dbType Database type.
	 *
	 * @return array|string
	 */
	public function getModifyDdl(BaseObject $target, $dbType = '')
	{
		return "// ".get_class($this).":getModifyDdl not implemented";
	}
}
