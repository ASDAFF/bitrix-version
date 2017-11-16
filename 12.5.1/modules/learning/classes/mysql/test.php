<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/learning/classes/general/test.php");

// 2012-04-13 Checked/modified for compatibility with new data model
class CTest extends CAllTest
{
	// 2012-04-13 Checked/modified for compatibility with new data model
	function GetList($arOrder=Array(), $arFilter=Array())
	{
		global $DB, $USER;

		if (!is_array($arFilter))
			$arFilter = Array();

		$oPermParser = new CLearnParsePermissionsFromFilter ($arFilter);
		$arSqlSearch = CTest::GetFilter($arFilter);

		$strSqlSearch = "";
		for($i=0; $i<count($arSqlSearch); $i++)
			if(strlen($arSqlSearch[$i])>0)
				$strSqlSearch .= " AND ".$arSqlSearch[$i]." ";

		$strSql =
			"SELECT DISTINCT T.*, ".
			$DB->DateToCharFunction("T.TIMESTAMP_X")." as TIMESTAMP_X ".
			"FROM b_learn_test T ".
			"INNER JOIN b_learn_course C ON T.COURSE_ID = C.ID ".
			"WHERE 1=1 ";

		if ($oPermParser->IsNeedCheckPerm())
			$strSql .= " AND C.LINKED_LESSON_ID IN (" . $oPermParser->SQLForAccessibleLessons() . ") ";

		$strSql .= $strSqlSearch;

		/* was:
		$bCheckPerm = ($APPLICATION->GetUserRight("learning") < "W" && !$USER->IsAdmin() && $arFilter["CHECK_PERMISSIONS"] != "N");

		$userID = $USER->GetID() ? $USER->GetID() : 0;
		$strSql =
			"SELECT DISTINCT T.*, ".
			$DB->DateToCharFunction("T.TIMESTAMP_X")." as TIMESTAMP_X ".
			"FROM b_learn_test T ".
			"INNER JOIN b_learn_course C ON T.COURSE_ID = C.ID ".
			($bCheckPerm ?
			"LEFT JOIN b_learn_course_permission CP ON CP.COURSE_ID = C.ID "
			: "").
			"WHERE 1=1 ".
			($bCheckPerm ?
			"AND CP.USER_GROUP_ID IN (".$USER->GetGroups().") ".
			"AND CP.PERMISSION >= '".(strlen($arFilter["MIN_PERMISSION"])==1 ? $arFilter["MIN_PERMISSION"] : "R")."' ".
			"AND (CP.PERMISSION='X' OR C.ACTIVE='Y') "
			:"").
			$strSqlSearch;
		*/

		if (!is_array($arOrder))
			$arOrder = Array();

		foreach($arOrder as $by=>$order)
		{
			$by = strtolower($by);
			$order = strtolower($order);
			if ($order!="asc")
				$order = "desc";

			if ($by == "id")						$arSqlOrder[] = " T.ID ".$order." ";
			elseif ($by == "name")			$arSqlOrder[] = " T.NAME ".$order." ";
			elseif ($by == "active")			$arSqlOrder[] = " T.ACTIVE ".$order." ";
			elseif ($by == "sort")				$arSqlOrder[] = " T.SORT ".$order." ";
			else
			{
				$arSqlOrder[] = " T.TIMESTAMP_X ".$order." ";
				$by = "timestamp_x";
			}
		}

		$strSqlOrder = "";
		DelDuplicateSort($arSqlOrder);
		for ($i=0; $i<count($arSqlOrder); $i++)
		{
			if($i==0)
				$strSqlOrder = " ORDER BY ";
			else
				$strSqlOrder .= ",";

			$strSqlOrder .= $arSqlOrder[$i];
		}

		$strSql .= $strSqlOrder;

		//echo $strSql;

		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	// 2012-04-13 Checked/modified for compatibility with new data model
	function GetRandFunction()
	{
		return " RAND(".rand(0, 1000000).") ";
	}

}




?>