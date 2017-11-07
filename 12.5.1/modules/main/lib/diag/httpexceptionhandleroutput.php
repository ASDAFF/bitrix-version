<?php
namespace Bitrix\Main\Diag;

use \Bitrix\Main;

class HttpExceptionHandlerOutput
	implements IExceptionHandlerOutput
{
	function renderExceptionMessage(\Exception $exception, $debug = false)
	{
		if ($debug)
			echo ExceptionHandlerFormatter::format($exception, true);
		else
			echo "Call admin 2";
	}
}
