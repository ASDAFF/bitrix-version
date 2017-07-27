<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Main\Entity;

/**
 * Entity field class for string data type
 * @package bitrix
 * @subpackage main
 */
class StringField extends ScalarField
{
	/**
	 * Shortcut for Regexp validator
	 * @var null|string
	 */
	protected $format = null;

	/** @var int|null  */
	protected $size = null;

	function __construct($name, $parameters = array())
	{
		parent::__construct($name, $parameters);

		if (!empty($parameters['format']))
		{
			$this->format = $parameters['format'];
		}
		if(isset($parameters['size']) && intval($parameters['size']) > 0)
		{
			$this->size = intval($parameters['size']);
		}
	}

	/**
	 * Shortcut for Regexp validator
	 * @return null|string
	 */
	public function getFormat()
	{
		return $this->format;
	}

	public function getValidators()
	{
		$validators = parent::getValidators();

		if ($this->format !== null)
		{
			$validators[] = new Validator\RegExp($this->format);
		}

		return $validators;
	}

	/**
	 * Returns the size of the field in a database (in characters).
	 * @return int|null
	 */
	public function getSize()
	{
		return $this->size;
	}
}