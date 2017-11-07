<?if(!defined("B_PROLOG_INCLUDED")||B_PROLOG_INCLUDED!==true)die();
$arParams["QUESTION_TYPE"] = ($arParams["QUESTION_TYPE"] == "html" ? "html" : "text");
$arParams["MESSAGE_TYPE"] = ($arParams["MESSAGE_TYPE"] == "html" ? "html" : "text");
if (!function_exists("__get_vve_uid"))
{
	function __get_vve_uid()
	{
		static $arUid = array();
		$uid = randString(5);
		while (in_array($uid, $arUid))
		{
			$uid = randString(5);
		}
		$arUid[] = $uid;
		return $uid;
	}
}
/********************************************************************
				Input params
********************************************************************/
$arParams["UID"] = __get_vve_uid();
/********************************************************************
				/Input params
********************************************************************/
?>