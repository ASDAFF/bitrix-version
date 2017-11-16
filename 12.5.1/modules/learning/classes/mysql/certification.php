<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/learning/classes/general/certification.php");

// 2012-04-10 Checked/modified for compatibility with new data model
class CCertification extends CAllCertification
{
	// 2012-04-10 Checked/modified for compatibility with new data model
	function GetList($arOrder=Array(), $arFilter=Array())
	{
		global $DB, $USER, $APPLICATION;

		$oPermParser = new CLearnParsePermissionsFromFilter ($arFilter);
		$arSqlSearch = CCertification::GetFilter($arFilter);

		$strSqlSearch = "";
		for($i=0; $i<count($arSqlSearch); $i++)
			if(strlen($arSqlSearch[$i])>0)
				$strSqlSearch .= " AND ".$arSqlSearch[$i]." ";

		$strSql =
		"SELECT DISTINCT C.*, CER.*, C.NAME as COURSE_NAME, COURSEOLD.ID as COURSE_ID, "
		. "COURSEOLD.ACTIVE_FROM as ACTIVE_FROM, COURSEOLD.ACTIVE_TO as ACTIVE_TO, COURSEOLD.RATING as RATING, "
		. "COURSEOLD.RATING_TYPE as RATING_TYPE, COURSEOLD.SCORM as SCORM, "
		. $DB->Concat("'('",'U.LOGIN',"') '","CASE WHEN U.NAME IS NULL THEN '' ELSE U.NAME END","' '", "CASE WHEN U.LAST_NAME IS NULL THEN '' ELSE U.LAST_NAME END")." as USER_NAME, U.ID as USER_ID, ".
		$DB->DateToCharFunction("CER.TIMESTAMP_X")." as TIMESTAMP_X, ".
		$DB->DateToCharFunction("CER.DATE_CREATE")." as DATE_CREATE ".
		"FROM b_learn_certification CER ".
		"INNER JOIN b_learn_course COURSEOLD ON CER.COURSE_ID = COURSEOLD.ID ".
		"INNER JOIN b_learn_lesson C ON C.ID = COURSEOLD.LINKED_LESSON_ID ".
		"INNER JOIN b_user U ON U.ID = CER.STUDENT_ID ".
		"WHERE 1=1 ";

		if ($oPermParser->IsNeedCheckPerm())
			$strSql .= " AND C.ID IN (" . $oPermParser->SQLForAccessibleLessons() . ") ";

		$strSql .= $strSqlSearch;

		/* was:
		$bCheckPerm = ($APPLICATION->GetUserRight("learning") < "W" && !$USER->IsAdmin() && $arFilter["CHECK_PERMISSIONS"] != "N");

		$strSql =
		"SELECT DISTINCT C.*, CER.*, C.NAME as COURSE_NAME, COURSEOLD.ID as COURSE_ID, "
		. "COURSEOLD.ACTIVE_FROM as ACTIVE_FROM, COURSEOLD.ACTIVE_TO as ACTIVE_TO, COURSEOLD.RATING as RATING, "
		. "COURSEOLD.RATING_TYPE as RATING_TYPE, COURSEOLD.SCORM as SCORM, "
		. $DB->Concat("'('",'U.LOGIN',"') '","CASE WHEN U.NAME IS NULL THEN '' ELSE U.NAME END","' '", "CASE WHEN U.LAST_NAME IS NULL THEN '' ELSE U.LAST_NAME END")." as USER_NAME, U.ID as USER_ID, ".
		$DB->DateToCharFunction("CER.TIMESTAMP_X")." as TIMESTAMP_X, ".
		$DB->DateToCharFunction("CER.DATE_CREATE")." as DATE_CREATE ".
		"FROM b_learn_certification CER ".
		"INNER JOIN b_learn_course COURSEOLD ON CER.COURSE_ID = COURSEOLD.ID ".
		"INNER JOIN b_learn_lesson C ON C.ID = COURSEOLD.LINKED_LESSON_ID ".
		"INNER JOIN b_user U ON U.ID = CER.STUDENT_ID ".
		($bCheckPerm ? "LEFT JOIN b_learn_course_permission CP ON CP.COURSE_ID = COURSEOLD.ID " : "").
		"WHERE 1=1 ".
		(!$bCheckPerm?"":
		"AND CP.USER_GROUP_ID IN (".$USER->GetGroups().") ".
		"AND CP.PERMISSION >= '".(strlen($arFilter["MIN_PERMISSION"])==1 ? $arFilter["MIN_PERMISSION"] : "R")."' ".
		"AND (CP.PERMISSION='X' OR C.ACTIVE='Y')"
		).
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

			if ($by == "id")						$arSqlOrder[] = " CER.ID ".$order." ";
			elseif ($by == "student_id")	$arSqlOrder[] = " CER.STUDENT_ID ".$order." ";
			elseif ($by == "course_id")		$arSqlOrder[] = " CER.COURSE_ID ".$order." ";
			elseif ($by == "sort")				$arSqlOrder[] = " CER.SORT ".$order." ";
			elseif ($by == "active")			$arSqlOrder[] = " CER.ACTIVE ".$order." ";
			elseif ($by == "from_online")	$arSqlOrder[] = " CER.FROM_ONLINE ".$order." ";
			elseif ($by == "public_profile")	$arSqlOrder[] = " CER.PUBLIC ".$order." ";
			elseif ($by == "date_create")	$arSqlOrder[] = " CER.DATE_CREATE ".$order." ";
			elseif ($by == "summary")		$arSqlOrder[] = " CER.SUMMARY ".$order." ";
			elseif ($by == "max_summary")$arSqlOrder[] = " CER.MAX_SUMMARY ".$order." ";
			elseif ($by == "timestamp_x")	$arSqlOrder[] = " CER.TIMESTAMP_X ".$order." ";
			else
			{
				$arSqlOrder[] = " CER.ID ".$order." ";
				$by = "id";
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
}
?>