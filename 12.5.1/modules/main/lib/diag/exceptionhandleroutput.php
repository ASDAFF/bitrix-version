<?php
namespace Bitrix\Main\Diag;

use \Bitrix\Main;

class ExceptionHandlerOutput
	implements IExceptionHandlerOutput
{
	function renderExceptionMessage(\Exception $exception, $debug = false)
	{
		if ($debug)
			echo ExceptionHandlerFormatter::format($exception, false);
		else
			echo "Call admin 1";
	}
}
