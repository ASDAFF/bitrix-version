<?php
namespace Bitrix\Main\DB;

class ArrayResult extends Result
{
	public function __construct($result)
	{
		parent::__construct($result);
	}

	public function getSelectedRowsCount()
	{
		return count($this->resource);
	}

	public function getFields()
	{
		return null;
	}

	protected function fetchRowInternal()
	{
		$val = current($this->resource);
		next($this->resource);
		return $val;
	}
}
