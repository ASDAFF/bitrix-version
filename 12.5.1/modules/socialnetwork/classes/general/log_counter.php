<?
class CAllSocNetLogCounter
{
	function GetSubSelect($log_id, $entity_type = false, $entity_id = false, $event_id = false, $created_by_id = false, $arOfEntities = false, $arAdmin = false, $transport = false, $visible = "Y", $type = "L")
	{
		global $DB;

		if (intval($log_id) <= 0)
			return false;

		$bGroupCounters = ($type === "group");

		if (!$entity_type || !$entity_id || !$created_by_id)
		{
			if ($type == "L" && ($arLog = CSocNetLog::GetByID($log_id)))
			{
				$entity_type = $arLog["ENTITY_TYPE"];
				$entity_id = $arLog["ENTITY_ID"];
				$event_id = $arLog["EVENT_ID"];
				$created_by_id = $arLog["USER_ID"];
			}
			elseif ($type == "LC" && ($arLogComment = CSocNetLogComments::GetByID($log_id)))
			{
				$entity_type = $arLogComment["ENTITY_TYPE"];
				$entity_id = $arLogComment["ENTITY_ID"];
				$event_id = $arLogComment["EVENT_ID"];
				$created_by_id = $arLogComment["USER_ID"];
				$log_id = $arLogComment["LOG_ID"];
			}
		}

		if (!in_array($entity_type, $GLOBALS["arSocNetAllowedSubscribeEntityTypes"]))
			return false;

		if (intval($entity_id) <= 0)
			return false;

		if (strlen($event_id) <= 0)
			return false;

		if (!$arOfEntities)
		{
			if (
				array_key_exists($entity_type, $GLOBALS["arSocNetAllowedSubscribeEntityTypesDesc"])
				&& array_key_exists("HAS_MY", $GLOBALS["arSocNetAllowedSubscribeEntityTypesDesc"][$entity_type])
				&& $GLOBALS["arSocNetAllowedSubscribeEntityTypesDesc"][$entity_type]["HAS_MY"] == "Y"
				&& array_key_exists("CLASS_OF", $GLOBALS["arSocNetAllowedSubscribeEntityTypesDesc"][$entity_type])
				&& array_key_exists("METHOD_OF", $GLOBALS["arSocNetAllowedSubscribeEntityTypesDesc"][$entity_type])
				&& strlen($GLOBALS["arSocNetAllowedSubscribeEntityTypesDesc"][$entity_type]["CLASS_OF"]) > 0
				&& strlen($GLOBALS["arSocNetAllowedSubscribeEntityTypesDesc"][$entity_type]["METHOD_OF"]) > 0
				&& method_exists($GLOBALS["arSocNetAllowedSubscribeEntityTypesDesc"][$entity_type]["CLASS_OF"], $GLOBALS["arSocNetAllowedSubscribeEntityTypesDesc"][$entity_type]["METHOD_OF"])
			)
				$arOfEntities = call_user_func(array($GLOBALS["arSocNetAllowedSubscribeEntityTypesDesc"][$entity_type]["CLASS_OF"], $GLOBALS["arSocNetAllowedSubscribeEntityTypesDesc"][$entity_type]["METHOD_OF"]), $entity_id);
			else
				$arOfEntities = array();
		}

		if ((!defined("DisableSonetLogVisibleSubscr") || DisableSonetLogVisibleSubscr !== true) && $visible && strlen($visible) > 0)
		{
			$key_res = CSocNetGroup::GetFilterOperation($visible);
			$strField = $key_res["FIELD"];
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];
			$visibleFilter = "AND (".($strNegative == "Y" ? " SLE.VISIBLE IS NULL OR NOT " : "")."(SLE.VISIBLE ".$strOperation." '".$DB->ForSql($strField)."'))";

			$transportFilter = "";
		}
		else
		{
			$visibleFilter = "";

			if ($transport && strlen($transport) > 0)
			{
				$key_res = CSocNetGroup::GetFilterOperation($transport);
				$strField = $key_res["FIELD"];
				$strNegative = $key_res["NEGATIVE"];
				$strOperation = $key_res["OPERATION"];
				$transportFilter = "AND (".($strNegative == "Y" ? " SLE.TRANSPORT IS NULL OR NOT " : "")."(SLE.TRANSPORT ".$strOperation." '".$DB->ForSql($strField)."'))";
			}
			else
				$transportFilter = "";
		}

		if (
			$type == "LC" 
			&& (
				!defined("DisableSonetLogFollow") 
				|| DisableSonetLogFollow !== true)
			)
		{
			$default_follow = COption::GetOptionString("socialnetwork", "follow_default_type", "Y");

			if ($default_follow == "Y")
			{
				$followJoin = " LEFT JOIN b_sonet_log_follow LFW ON LFW.USER_ID = U.ID AND (LFW.CODE = 'L".$log_id."' OR LFW.CODE = '**') ";
				$followWhere = "AND (LFW.USER_ID IS NULL OR LFW.TYPE = 'Y')";
			}
			else
			{
				$followJoin = " INNER JOIN b_sonet_log_follow LFW ON LFW.USER_ID = U.ID AND (LFW.CODE = 'L".$log_id."' OR LFW.CODE = '**') ";
				$followWhere = "AND (LFW.USER_ID IS NOT NULL AND LFW.TYPE = 'Y')";
			}
		}

		if (
			is_array($arOfEntities)
			&& count($arOfEntities) > 0
		)
			$strOfEntities = "U.ID IN (".implode(",", $arOfEntities).")";
		else
			$strOfEntities = "";

		$strSQL = "
		SELECT DISTINCT
			U.ID as ID
			,1
			,".$DB->IsNull("SLS.SITE_ID", "'**'")." as SITE_ID
			,".($bGroupCounters? "SLR0.GROUP_CODE": "'**'")." as CODE
		FROM
			b_user U 
			INNER JOIN b_sonet_log_right SLR ON SLR.LOG_ID = ".$log_id."
			".($bGroupCounters? "INNER JOIN b_sonet_log_right SLR0 ON SLR0.LOG_ID = SLR.LOG_ID ": "")."
			INNER JOIN b_user_access UA ON UA.USER_ID = U.ID
			LEFT JOIN b_sonet_log_site SLS ON SLS.LOG_ID = SLR.LOG_ID
			".(strlen($followJoin) > 0 ? $followJoin : "")."
		WHERE
			U.ACTIVE = 'Y'
			".(
				array_key_exists("USE_CB_FILTER", $GLOBALS["arSocNetAllowedSubscribeEntityTypesDesc"][$entity_type]) 
				&& $GLOBALS["arSocNetAllowedSubscribeEntityTypesDesc"][$entity_type]["USE_CB_FILTER"] == "Y" 
				&& intval($created_by_id) > 0 
					? "AND U.ID <> ".$created_by_id 
					: ""
			)."
			".($bGroupCounters? "AND (SLR0.GROUP_CODE like 'SG%' AND SLR0.GROUP_CODE NOT LIKE 'SG%\_%')": "")."
			AND (
				0=1 
				OR (SLR.GROUP_CODE IN ('AU', 'G2'))
				OR (UA.ACCESS_CODE = SLR.GROUP_CODE)
			)
			".(strlen($followWhere) > 0 ? $followWhere : "")."
		";

		if($bGroupCounters)
			return $strSQL;

		if (
			strlen($visibleFilter) > 0 
			|| strlen($transportFilter) > 0
		)
		{
			$strSQL .= "
				AND	
				(
					EXISTS(
						SELECT ID
						FROM b_sonet_log_events SLE
						WHERE
							SLE.USER_ID = U.ID
							AND SLE.ENTITY_TYPE = '".$entity_type."'
							AND SLE.ENTITY_CB = 'N'
							AND SLE.ENTITY_ID = ".$entity_id."
							AND SLE.EVENT_ID = '".$event_id."'
							".$transportFilter."
							".$visibleFilter."
					)";

			if (
				array_key_exists("USE_CB_FILTER", $GLOBALS["arSocNetAllowedSubscribeEntityTypesDesc"][$entity_type])
				&& $GLOBALS["arSocNetAllowedSubscribeEntityTypesDesc"][$entity_type]["USE_CB_FILTER"] == "Y"
				&& intval($created_by_id) > 0
			)
			{
				$strSQL .= "
				OR
				(
					EXISTS(
						SELECT ID
						FROM b_sonet_log_events SLE
						WHERE
							SLE.USER_ID = U.ID
							AND SLE.ENTITY_CB = 'Y'
							AND SLE.ENTITY_ID = ".$created_by_id."
							AND SLE.EVENT_ID = '".$event_id."'
							".$transportFilter."
							".$visibleFilter."
					)
				)";
			}

			$strSQL .= "
			OR
			(
				(
					NOT EXISTS(
						SELECT ID
						FROM b_sonet_log_events SLE
						WHERE
							SLE.USER_ID = U.ID
							AND SLE.ENTITY_TYPE = '".$entity_type."'
							AND SLE.ENTITY_CB = 'N'
							AND SLE.ENTITY_ID = ".$entity_id."
							AND SLE.EVENT_ID = '".$event_id."'
					)
					OR
					EXISTS(
						SELECT ID
						FROM b_sonet_log_events SLE
						WHERE
							SLE.USER_ID = U.ID
							AND SLE.ENTITY_TYPE = '".$entity_type."'
							AND SLE.ENTITY_CB = 'N'
							AND SLE.ENTITY_ID = ".$entity_id."
							AND SLE.EVENT_ID = '".$event_id."'
							AND ".($visibleFilter ? "SLE.VISIBLE = 'I'" : "SLE.TRANSPORT = 'I'")."
					)
				)";

			if (
				array_key_exists("USE_CB_FILTER", $GLOBALS["arSocNetAllowedSubscribeEntityTypesDesc"][$entity_type])
				&& $GLOBALS["arSocNetAllowedSubscribeEntityTypesDesc"][$entity_type]["USE_CB_FILTER"] == "Y"
				&& intval($created_by_id) > 0
			)
			{
				$strSQL .= "
				AND
				(
					NOT EXISTS(
						SELECT ID
						FROM b_sonet_log_events SLE
						WHERE
							SLE.USER_ID = U.ID
							AND SLE.ENTITY_CB = 'Y'
							AND SLE.ENTITY_ID = ".$created_by_id."
							AND SLE.EVENT_ID = '".$event_id."'
					)
					OR
					EXISTS(
						SELECT ID
						FROM b_sonet_log_events SLE
						WHERE
							SLE.USER_ID = U.ID
							AND SLE.ENTITY_CB = 'Y'
							AND SLE.ENTITY_ID = ".$created_by_id."
							AND SLE.EVENT_ID = '".$event_id."'
							AND ".($visibleFilter ? "SLE.VISIBLE = 'I'" : "SLE.TRANSPORT = 'I'")."
					)

				)";
			}

			$strSQL .= "
				AND
				(
					EXISTS(
						SELECT ID
						FROM b_sonet_log_events SLE
						WHERE
							SLE.USER_ID = U.ID
							AND SLE.ENTITY_TYPE = '".$entity_type."'
							AND SLE.ENTITY_CB = 'N'
							AND SLE.ENTITY_ID = ".$entity_id."
							AND SLE.EVENT_ID = 'all'
							".$transportFilter."
							".$visibleFilter."
					)";

			if (
				array_key_exists("USE_CB_FILTER", $GLOBALS["arSocNetAllowedSubscribeEntityTypesDesc"][$entity_type])
				&& $GLOBALS["arSocNetAllowedSubscribeEntityTypesDesc"][$entity_type]["USE_CB_FILTER"] == "Y"
				&& intval($created_by_id) > 0
			)
			{
				$strSQL .= "
					OR
					(
						EXISTS(
							SELECT ID
							FROM b_sonet_log_events SLE
							WHERE
								SLE.USER_ID = U.ID
								AND SLE.ENTITY_CB = 'Y'
								AND SLE.ENTITY_ID = ".$created_by_id."
								AND SLE.EVENT_ID = 'all'
								".$transportFilter."
								".$visibleFilter."
						)
					)";
			}

			$strSQL .= "
					OR
					(
						(
							NOT EXISTS(
								SELECT ID
								FROM b_sonet_log_events SLE
								WHERE
									SLE.USER_ID = U.ID
									AND SLE.ENTITY_TYPE = '".$entity_type."'
									AND SLE.ENTITY_CB = 'N'
									AND SLE.ENTITY_ID = ".$entity_id."
									AND SLE.EVENT_ID = 'all'
							)
							OR
							EXISTS(
								SELECT ID
								FROM b_sonet_log_events SLE
								WHERE
									SLE.USER_ID = U.ID
									AND SLE.ENTITY_TYPE = '".$entity_type."'
									AND SLE.ENTITY_CB = 'N'
									AND SLE.ENTITY_ID = ".$entity_id."
									AND SLE.EVENT_ID = 'all'
									AND ".($visibleFilter ? "SLE.VISIBLE = 'I'" : "SLE.TRANSPORT = 'I'")."
							)
						)
						AND ";

			if (
				array_key_exists("USE_CB_FILTER", $GLOBALS["arSocNetAllowedSubscribeEntityTypesDesc"][$entity_type])
				&& $GLOBALS["arSocNetAllowedSubscribeEntityTypesDesc"][$entity_type]["USE_CB_FILTER"] == "Y"
				&& intval($created_by_id) > 0
			)
			{
				$strSQL .= "
						(
							NOT EXISTS(
								SELECT ID
								FROM b_sonet_log_events SLE
								WHERE
									SLE.USER_ID = U.ID
									AND SLE.ENTITY_CB = 'Y'
									AND SLE.ENTITY_ID = ".$created_by_id."
									AND SLE.EVENT_ID = 'all'
							)
							OR
							EXISTS(
								SELECT ID
								FROM b_sonet_log_events SLE
								WHERE
									SLE.USER_ID = U.ID
									AND SLE.ENTITY_CB = 'Y'
									AND SLE.ENTITY_ID = ".$created_by_id."
									AND SLE.EVENT_ID = 'all'
									AND ".($visibleFilter ? "SLE.VISIBLE = 'I'" : "SLE.TRANSPORT = 'I'")."
							)
						)
						AND
						(
						";
			}

			if (strlen($strOfEntities) > 0)
			{
					$strSQL .= "
						(
							".$strOfEntities."
							AND
							(
								EXISTS(
									SELECT ID
									FROM b_sonet_log_events SLE
									WHERE
										SLE.USER_ID = U.ID
										AND SLE.ENTITY_TYPE = '".$entity_type."'
										AND SLE.ENTITY_ID = 0
										AND SLE.ENTITY_MY = 'Y'
										AND SLE.EVENT_ID = '".$event_id."'
										".$transportFilter."
										".$visibleFilter."
								)
								OR
								(
									(
										EXISTS(
											SELECT ID
											FROM b_sonet_log_events SLE
											WHERE
												SLE.USER_ID = U.ID
												AND SLE.ENTITY_TYPE = '".$entity_type."'
												AND SLE.ENTITY_ID = 0
												AND SLE.ENTITY_MY = 'Y'
												AND SLE.EVENT_ID = '".$event_id."'
												AND ".($visibleFilter ? "SLE.VISIBLE = 'I'" : "SLE.TRANSPORT = 'I'")."
										)
										OR
										NOT EXISTS(
											SELECT ID
											FROM b_sonet_log_events SLE
											WHERE
												SLE.USER_ID = U.ID
												AND SLE.ENTITY_TYPE = '".$entity_type."'
												AND SLE.ENTITY_ID = 0
												AND SLE.ENTITY_MY = 'Y'
												AND SLE.EVENT_ID = '".$event_id."'
										)
									)
									AND
									(
										EXISTS(
											SELECT ID
											FROM b_sonet_log_events SLE
											WHERE
												SLE.USER_ID = U.ID
												AND SLE.ENTITY_TYPE = '".$entity_type."'
												AND SLE.ENTITY_ID = 0
												AND SLE.ENTITY_MY = 'Y'
												AND SLE.EVENT_ID = 'all'
												".$transportFilter."
												".$visibleFilter."
										)
									)
								)
							)
						)
						OR
					";
			}

			$strSQL .=	"
							(
								EXISTS(
									SELECT ID
									FROM b_sonet_log_events SLE
									WHERE
										SLE.USER_ID = U.ID
										AND SLE.ENTITY_TYPE = '".$entity_type."'
										AND SLE.ENTITY_ID = 0
										AND SLE.ENTITY_MY = 'N'
										AND SLE.EVENT_ID = '".$event_id."'
										".$transportFilter."
										".$visibleFilter."
								)
								OR
								(
									(
										EXISTS(
											SELECT ID
											FROM b_sonet_log_events SLE
											WHERE
												SLE.USER_ID = U.ID
												AND SLE.ENTITY_TYPE = '".$entity_type."'
												AND SLE.ENTITY_ID = 0
												AND SLE.ENTITY_MY = 'N'
												AND SLE.EVENT_ID = '".$event_id."'
												AND ".($visibleFilter ? "SLE.VISIBLE = 'I'" : "SLE.TRANSPORT = 'I'")."
											)
										OR
										NOT EXISTS(
											SELECT ID
											FROM b_sonet_log_events SLE
											WHERE
												SLE.USER_ID = U.ID
												AND SLE.ENTITY_TYPE = '".$entity_type."'
												AND SLE.ENTITY_ID = 0
												AND SLE.ENTITY_MY = 'N'
												AND SLE.EVENT_ID = '".$event_id."'
										)
									)
									AND
									(
										EXISTS(
											SELECT ID
											FROM b_sonet_log_events SLE
											WHERE
												SLE.USER_ID = U.ID
												AND SLE.ENTITY_TYPE = '".$entity_type."'
												AND SLE.ENTITY_ID = 0
												AND SLE.ENTITY_MY = 'N'
												AND SLE.EVENT_ID = 'all'
										".$transportFilter."
										".$visibleFilter."
										)
										OR
										EXISTS(
											SELECT ID
											FROM b_sonet_log_events SLE
											WHERE
												SLE.USER_ID = U.ID
												AND SLE.ENTITY_TYPE = '".$entity_type."'
												AND SLE.ENTITY_ID = 0
												AND SLE.ENTITY_MY = 'N'
												AND SLE.EVENT_ID = 'all'
												AND ".($visibleFilter ? "SLE.VISIBLE = 'I'" : "SLE.TRANSPORT = 'I'")."
										)
										OR
										NOT EXISTS(
											SELECT ID
											FROM b_sonet_log_events SLE
											WHERE
												SLE.USER_ID = U.ID
												AND SLE.ENTITY_TYPE = '".$entity_type."'
												AND SLE.ENTITY_ID = 0
												AND SLE.ENTITY_MY = 'N'
												AND SLE.EVENT_ID = 'all'
										)
									)
								)
							)";

			if (
				array_key_exists("USE_CB_FILTER", $GLOBALS["arSocNetAllowedSubscribeEntityTypesDesc"][$entity_type])
				&& $GLOBALS["arSocNetAllowedSubscribeEntityTypesDesc"][$entity_type]["USE_CB_FILTER"] == "Y"
				&& intval($created_by_id) > 0
			)
				$strSQL .="
						)";

			$strSQL .="
					)
				)
			)

			)";
		}

		return $strSQL;
	}

	function GetValueByUserID($user_id, $site_id = SITE_ID)
	{
		global $DB;
		$user_id = intval($user_id);

		if ($user_id <= 0)
			return false;

		$strSQL = "
			SELECT SUM(CNT) CNT
			FROM b_sonet_log_counter
			WHERE USER_ID = ".$user_id."
			AND (SITE_ID = '".$site_id."' OR SITE_ID = '**')
			AND CODE = '**'
		";

		$dbRes = $DB->Query($strSQL, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
			return $arRes["CNT"];
		else
			return 0;
	}

	function GetCodeValuesByUserID($user_id, $site_id = SITE_ID)
	{
		global $DB;
		$result = array();
		$user_id = intval($user_id);

		if($user_id > 0)
		{
			$strSQL = "
				SELECT CODE, SUM(CNT) CNT
				FROM b_sonet_log_counter
				WHERE USER_ID = ".$user_id."
				AND (SITE_ID = '".$site_id."' OR SITE_ID = '**')
				GROUP BY CODE
			";

			$dbRes = $DB->Query($strSQL, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			while ($arRes = $dbRes->Fetch())
				$result[$arRes["CODE"]] = $arRes["CNT"];
		}

		return $result;
	}

	function GetLastDateByUserAndCode($user_id, $site_id = SITE_ID, $code = "**")
	{
		global $DB;
		$result = 0;
		$user_id = intval($user_id);

		if($user_id > 0)
		{
			$strSQL = "
				SELECT ".$DB->DateToCharFunction("LAST_DATE", "FULL")." LAST_DATE
				FROM b_sonet_log_counter
				WHERE USER_ID = ".$user_id."
				AND (SITE_ID = '".$DB->ForSql($site_id)."' OR SITE_ID = '**')
				AND CODE = '".$DB->ForSql($code)."'
			";

			$dbRes = $DB->Query($strSQL, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				$result = MakeTimeStamp($arRes["LAST_DATE"]);
		}

		return $result;
	}

	function GetList($arFilter = Array(), $arSelectFields = array())
	{
		global $DB;

		if (count($arSelectFields) <= 0)
			$arSelectFields = array("LAST_DATE", "PAGE_SIZE", "PAGE_LAST_DATE_1");

		// FIELDS -->
		$arFields = array(
			"USER_ID" => Array("FIELD" => "SLC.USER_ID", "TYPE" => "int"),
			"SITE_ID" => Array("FIELD" => "SLC.SITE_ID", "TYPE" => "string"),
			"CODE" => Array("FIELD" => "SLC.CODE", "TYPE" => "string"),
			"LAST_DATE" => Array("FIELD" => "SLC.LAST_DATE", "TYPE" => "datetime"),
			"PAGE_SIZE" => array("FIELD" => "SLC.PAGE_SIZE", "TYPE" => "int"),
			"PAGE_LAST_DATE_1" => Array("FIELD" => "SLC.PAGE_LAST_DATE_1", "TYPE" => "datetime"),
		);
		// <-- FIELDS

		$arSqls = CSocNetGroup::PrepareSql($arFields, array(), $arFilter, false, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		$strSql =
			"SELECT ".$arSqls["SELECT"]." ".
			"FROM b_sonet_log_counter SLC ".
			"	".$arSqls["FROM"]." ";
		if (strlen($arSqls["WHERE"]) > 0)
			$strSql .= "WHERE ".$arSqls["WHERE"]." ";

		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $dbRes;
	}
}
?>