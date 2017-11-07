<?php
namespace Bitrix\Main\DB;

class ConnectionException
	extends DbException
{
	public function __construct($message = "", $databaseMessage = "", \Exception $previous = null)
	{
		parent::__construct($message, $databaseMessage, $previous);
	}
}
