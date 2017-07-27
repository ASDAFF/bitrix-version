<?php
namespace Bitrix\Seo\Engine;

use Bitrix\Main\SystemException;

class YandexDirectException extends SystemException
{
	public function __construct(array $queryResult, \Exception $previous = null)
	{
		$errorMessage = $queryResult['error_str'];
		if(strlen($errorMessage) > 0 && strlen($queryResult['error_detail']) > 0)
		{
			$errorMessage .= ": ";
		}
		$errorMessage .= $queryResult['error_detail'];

		parent::__construct($errorMessage, $queryResult['error_code']);
	}
}
