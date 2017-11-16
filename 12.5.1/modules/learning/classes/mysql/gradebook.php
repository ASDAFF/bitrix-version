<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/learning/classes/general/gradebook.php");

// 2012-04-10 Checked/modified for compatibility with new data model
class CGradeBook extends CAllGradeBook
{
	// 2012-04-10 Checked/modified for compatibility with new data model
	function GetList($arOrder=Array(), $arFilter=Array())
	{
		global $DB, $USER, $APPLICATION;

		$oPermParser = new CLearnParsePermissionsFromFilter ($arFilter);
		$arSqlSearch = CGradeBook::GetFilter($arFilter);

		$strSqlSearch = "";
		for($i=0; $i<count($arSqlSearch); $i++)
			if(strlen($arSqlSearch[$i])>0)
				$strSqlSearch .= " AND ".$arSqlSearch[$i]." ";

		//Sites
		$SqlSearchLang = "''";
		if (array_key_exists("SITE_ID", $arFilter))
		{
			$arLID = Array();

			if(is_array($arFilter["SITE_ID"]))
				$arLID = $arFilter["SITE_ID"];
			else
			{
				if (strlen($arFilter["SITE_ID"]) > 0)
					$arLID[] = $arFilter["SITE_ID"];
			}

			foreach($arLID as $v)
				$SqlSearchLang .= ", '".$DB->ForSql($v, 2)."'";
		}

		$strSql =
		"SELECT DISTINCT G.*, T.NAME as TEST_NAME, T.COURSE_ID as COURSE_ID, 
		(T.ATTEMPT_LIMIT + G.EXTRA_ATTEMPTS) AS ATTEMPT_LIMIT, TUL.NAME as COURSE_NAME, 
		C.LINKED_LESSON_ID AS LINKED_LESSON_ID, ".
		$DB->Concat("'('",'U.LOGIN',"') '","CASE WHEN U.NAME IS NULL THEN '' ELSE U.NAME END","' '", "CASE WHEN U.LAST_NAME IS NULL THEN '' ELSE U.LAST_NAME END")." as USER_NAME, U.ID as USER_ID ".
		"FROM b_learn_gradebook G ".
		"INNER JOIN b_learn_test T ON G.TEST_ID = T.ID ".
		"INNER JOIN b_user U ON U.ID = G.STUDENT_ID ".
		"LEFT JOIN b_learn_course C ON C.ID = T.COURSE_ID ".
		"LEFT JOIN b_learn_lesson TUL ON TUL.ID = C.LINKED_LESSON_ID ".
		"LEFT JOIN b_learn_test_mark TM ON G.TEST_ID = TM.TEST_ID ".
		(strlen($SqlSearchLang) > 2 ? "LEFT JOIN b_learn_course_site CS ON C.ID = CS.COURSE_ID " : "")
		. "WHERE 
			(TM.SCORE IS NULL 
			OR TM.SCORE = 
				(SELECT SCORE 
				FROM b_learn_test_mark 
				WHERE SCORE >= (G.RESULT/G.MAX_RESULT*100) 
				ORDER BY SCORE ASC 
				LIMIT 1)
			) ";

		if ($oPermParser->IsNeedCheckPerm())
			$strSql .= " AND TUL.ID IN (" . $oPermParser->SQLForAccessibleLessons() . ") ";

		$strSql .= $strSqlSearch;
		
		if (strlen($SqlSearchLang) > 2)
			$strSql .= " AND CS.SITE_ID IN (" . $SqlSearchLang . ")";


		/* was:
		$bCheckPerm = ($APPLICATION->GetUserRight("learning") < "W" && !$USER->IsAdmin() && $arFilter["CHECK_PERMISSIONS"] != "N");

		$strSql =
		"SELECT DISTINCT G.*, T.NAME as TEST_NAME, T.COURSE_ID as COURSE_ID, (T.ATTEMPT_LIMIT + G.EXTRA_ATTEMPTS) AS ATTEMPT_LIMIT, C.NAME as COURSE_NAME, C.LINKED_LESSON_ID AS LINKED_LESSON_ID ".
		$DB->Concat("'('",'U.LOGIN',"') '","CASE WHEN U.NAME IS NULL THEN '' ELSE U.NAME END","' '", "CASE WHEN U.LAST_NAME IS NULL THEN '' ELSE U.LAST_NAME END")." as USER_NAME, U.ID as USER_ID ".
		"FROM b_learn_gradebook G ".
		"INNER JOIN b_learn_test T ON G.TEST_ID = T.ID ".
		"INNER JOIN b_user U ON U.ID = G.STUDENT_ID ".
		"LEFT JOIN b_learn_course C ON C.ID = T.COURSE_ID ".
		"LEFT JOIN b_learn_lesson TUL ON TUL.ID = C.LINKED_LESSON_ID ".
		"LEFT JOIN b_learn_test_mark TM ON G.TEST_ID = TM.TEST_ID ".
		(strlen($SqlSearchLang) > 2 ? "LEFT JOIN b_learn_course_site CS ON C.ID = CS.COURSE_ID " : "").
		($bCheckPerm ? "LEFT JOIN b_learn_course_permission CP ON CP.COURSE_ID = C.ID " : "").
		"WHERE 1=1 ".
		"AND (TM.SCORE IS NULL OR TM.SCORE = (SELECT SCORE FROM b_learn_test_mark WHERE SCORE >= (G.RESULT/G.MAX_RESULT*100) ORDER BY SCORE ASC LIMIT 1)) ".
		($bCheckPerm ?
		"AND CP.USER_GROUP_ID IN (".$USER->GetGroups().") ".
		"AND CP.PERMISSION >= '".(strlen($arFilter["MIN_PERMISSION"])==1 ? $arFilter["MIN_PERMISSION"] : "R")."' ".
		"AND (CP.PERMISSION='X' OR TUL.ACTIVE='Y')"
		:"").
		$strSqlSearch.
		(strlen($SqlSearchLang) > 2 ? " AND CS.SITE_ID IN (".$SqlSearchLang.")" : "");
		*/

		if (!is_array($arOrder))
			$arOrder = Array();

		foreach($arOrder as $by=>$order)
		{
			$by = strtolower($by);
			$order = strtolower($order);
			if ($order!="asc")
				$order = "desc";

			if ($by == "id")							$arSqlOrder[] = " G.ID ".$order." ";
			elseif ($by == "student_id")		$arSqlOrder[] = " G.STUDENT_ID ".$order." ";
			elseif ($by == "test_id")				$arSqlOrder[] = " G.TEST_ID ".$order." ";
			elseif ($by == "completed")		$arSqlOrder[] = " G.COMPLETED ".$order." ";
			elseif ($by == "result")				$arSqlOrder[] = " G.RESULT ".$order." ";
			elseif ($by == "max_result")		$arSqlOrder[] = " G.MAX_RESULT ".$order." ";
			elseif ($by == "user_name")		$arSqlOrder[] = " USER_NAME ".$order." ";
			elseif ($by == "test_name")		$arSqlOrder[] = " TEST_NAME ".$order." ";
			else
			{
				$arSqlOrder[] = " G.ID ".$order." ";
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
