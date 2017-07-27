<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sender;


abstract class Connector
{
	var $fieldPrefix;
	var $fieldValues;
	var $fieldFormName;
	var $moduleId;

	/**
	 * @param $moduleId
	 */
	public function setModuleId($moduleId)
	{
		$this->moduleId = $moduleId;
	}

	/**
	 * @return mixed
	 */
	public function getModuleId()
	{
		return $this->moduleId;
	}

	/**
	 * @param $fieldFormName
	 */
	public function setFieldFormName($fieldFormName)
	{
		$this->fieldFormName = $fieldFormName;
	}
	/** @return string */
	public function getFieldFormName()
	{
		return $this->fieldFormName;
	}

	/**
	 * @param $fieldPrefix
	 */
	public function setFieldPrefix($fieldPrefix)
	{
		$this->fieldPrefix = $fieldPrefix;
	}
	/** @return string */
	public function getFieldPrefix()
	{
		return $this->fieldPrefix;
	}

	/**
	 * @param array $fieldValues
	 */
	public function setFieldValues(array $fieldValues = null)
	{
		$this->fieldValues = $fieldValues;
	}

	/** @return string */
	public function getFieldId($id)
	{
		$fieldPrefix = $this->getFieldPrefix();
		if($fieldPrefix)
		{
			return $fieldPrefix.'_'.$this->getModuleId().'_'.$this->getCode().'_%CONNECTOR_NUM%_'.$id;
		}
		else
			return $id;
	}

	/** @return string */
	public function getFieldName($name)
	{
		$fieldPrefix = $this->getFieldPrefix();
		if($fieldPrefix)
		{
			$arReturnName = array();
			$arReturnName[] = $fieldPrefix.'['.$this->getModuleId().']['.$this->getCode().'][%CONNECTOR_NUM%]';
			$arName = explode('[', $name);
			$arReturnName[] = '['.$arName[0].']';
			if(count($arName)>1)
			{
				unset($arName[0]);
				$arReturnName[] = '['.implode('[', $arName);
			}

			return implode('', $arReturnName);
		}
		else
			return $name;
	}

	/** @return mixed */
	public function getFieldValue($name, $defaultValue = null)
	{
		if($this->fieldValues && array_key_exists($name, $this->fieldValues))
			return $this->fieldValues[$name];
		else
			return $defaultValue;
	}

	/** @return string */
	public function getId()
	{
		return $this->getModuleId().'_'.$this->getCode();
	}

	/** @return integer */
	public function getDataCount()
	{
		$dataDb = $this->getData();
		/** @var \CDBResult $dataDb */
		if(!($dataDb instanceof \CDBResultMysql))
		{
			$dataDb->NavStart(0);
		}

		return $dataDb->SelectedRowsCount();
	}

	/** @return bool */
	public function requireConfigure()
	{
		return false;
	}

	/** @return string */
	public abstract function getName();

	/** @return string */
	public abstract function getCode();

	/** @return \CDBResult */
	public abstract function getData();

	/** @return string */
	public abstract function getForm();
}