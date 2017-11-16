<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$GLOBALS["CurUserCanAddComments"] = array();

if (!function_exists('__SLLogUpDateTSSort'))
{
	function __SLLogUpDateTSSort($a, $b)
	{
		if ($a["LOG_UPDATE_TS"] == $b["LOG_UPDATE_TS"])
		{
			if (array_key_exists("EVENT", $a))
				return ($a["EVENT"]["ID"] > $b["EVENT"]["ID"]) ? -1 : 1;
			else
				return 0;
		}

		return ($a["LOG_UPDATE_TS"] > $b["LOG_UPDATE_TS"]) ? -1 : 1;
	}
}
?>