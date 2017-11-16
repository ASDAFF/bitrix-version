<?if(!defined("B_PROLOG_INCLUDED")||B_PROLOG_INCLUDED!==true)die();
if (!function_exists("__get_vve_uid"))
{
	function __get_vve_uid()
	{
		static $arUid = array();
		$uid = randString(5);
		while (in_array($uid, $arUid)) {
			$uid = randString(5); }
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