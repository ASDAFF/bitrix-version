<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/classes/general/message.php");

class CForumMessage extends CAllForumMessage
{
	function Add($arFields, $strUploadDir = false, $arParams = array())
	{
		global $DB;

		$strUploadDir = ($strUploadDir === false ? "forum/upload" : $strUploadDir);

		if (!CForumMessage::CheckFields("ADD", $arFields))
			return false;

		$arForum = CForumNew::GetByID($arFields["FORUM_ID"]);
		$arParams["SKIP_STATISTIC"] = ($arParams["SKIP_STATISTIC"] == "Y" ? "Y" : "N");
		$arParams["SKIP_INDEXING"] = ($arParams["SKIP_INDEXING"] == "Y" || $arForum["INDEXATION"] != "Y" ? "Y" : "N");

		$POST_MESSAGE = $arFields["POST_MESSAGE"];
		$parser = new forumTextParser(LANGUAGE_ID);
		$allow = forumTextParser::GetFeatures($arForum);
		$allow['SMILES'] = (($arFields["USE_SMILES"] != "Y") ? 'N' : $allow['SMILES']);
		if (COption::GetOptionString("forum", "FILTER", "Y") == "Y")
		{
			$POST_MESSAGE = CFilterUnquotableWords::Filter($POST_MESSAGE);
			$arFields["POST_MESSAGE_FILTER"] = (empty($POST_MESSAGE) ? "*" : $POST_MESSAGE);
		}
/***************** Attach ******************************************/
		$arFiles = array();
		if (is_array($arFields["ATTACH_IMG"]))
			$arFields["FILES"] = array($arFields["ATTACH_IMG"]);
		unset($arFields["ATTACH_IMG"]);
		if (is_array($arFields["FILES"]) && !empty($arFields["FILES"]))
		{
			$res = array("FORUM_ID" => $arFields["FORUM_ID"], "USER_ID" => $arFields["AUTHOR_ID"], "upload_dir" => $strUploadDir);
			$arFiles = CForumFiles::Save($arFields["FILES"], $res, false);
			if (!empty($arFiles))
			{
				$arFiles = array_keys($arFiles);
				sort($arFiles);
				$arFields["ATTACH_IMG"] = $arFiles[0];
				$arFields["ATTACHED_FILES"] = $arFiles;
			}
			unset($arFields["FILES"]);
		}
/***************** Attach/******************************************/
		if (COption::GetOptionString("forum", "MESSAGE_HTML", "N") == "Y")
			$POST_MESSAGE = $parser->convert($POST_MESSAGE, $allow, "html", $arFiles);
		$arFields["POST_MESSAGE_HTML"] = $POST_MESSAGE;
/***************** Event onBeforeMessageAdd ************************/
		$events = GetModuleEvents("forum", "onBeforeMessageAdd");
		while ($arEvent = $events->Fetch())
		{
			if (ExecuteModuleEventEx($arEvent, array(&$arFields, &$strUploadDir)) === false)
				return false;
		}
/***************** /Event ******************************************/
		if (empty($arFields))
			return false;
		$arInsert = $DB->PrepareInsert("b_forum_message", $arFields, $strUploadDir);

		$strDatePostField = "";
		$strDatePostValue = "";
		if (!is_set($arFields, "POST_DATE"))
		{
			$strDatePostField = ", POST_DATE";
			$strDatePostValue = ", ".$DB->GetNowFunction()."";
		}

		$strSql = "INSERT INTO b_forum_message(".$arInsert[0].$strDatePostField.") VALUES(".$arInsert[1].$strDatePostValue.")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$ID = intVal($DB->LastID());
/***************** Attach ******************************************/
		if (!empty($arFiles))
			CForumFiles::UpdateByID($arFiles, array("FORUM_ID" => $arFields["FORUM_ID"],
				"TOPIC_ID" => $arFields["TOPIC_ID"], "MESSAGE_ID" => $ID));
/***************** Attach/******************************************/
/***************** Quota *******************************************/
		$_SESSION["SESS_RECOUNT_DB"] = "Y";
/***************** Events onAfterMessageAdd ************************/
		$events = GetModuleEvents("forum", "onAfterMessageAdd");
		while ($arEvent = $events->Fetch())
			ExecuteModuleEventEx($arEvent, array(&$ID, &$arFields));
/***************** /Events *****************************************/
		if ($arParams["SKIP_STATISTIC"] == "Y" && $arParams["SKIP_INDEXING"] == "Y")
			return $ID;
		$arMessage = CForumMessage::GetByIDEx($ID, array("GET_FORUM_INFO" => "N", "GET_TOPIC_INFO" => "Y", "FILTER" => "Y"));

		if ($arParams["SKIP_STATISTIC"] != "Y")
		{
			if (intVal($arMessage["AUTHOR_ID"]) > 0)
			{
				CForumUser::SetStat($arMessage["AUTHOR_ID"], array("MESSAGE" => $arMessage));
			}
			CForumTopic::SetStat($arMessage["TOPIC_ID"],  array("MESSAGE" => $arMessage));
			CForumNew::SetStat($arMessage["FORUM_ID"],  array("MESSAGE" => $arMessage));
		}
		if ($arMessage["APPROVED"] == "Y")
		{
			if ($arParams["SKIP_INDEXING"] != "Y" && CModule::IncludeModule("search"))
			{
				$arMessage["POST_MESSAGE"] = (COption::GetOptionString("forum", "FILTER", "Y") == "Y" ?
					$arMessage["POST_MESSAGE_FILTER"] : $arMessage["POST_MESSAGE"]);
				$arParams = array(
					"PERMISSION" => array(),
					"SITE" => CForumNew::GetSites($arMessage["FORUM_ID"]),
					"DEFAULT_URL" => "/");

				$arGroups = CForumNew::GetAccessPermissions($arMessage["FORUM_ID"]);
				for ($i = 0; $i < count($arGroups); $i++)
				{
					if ($arGroups[$i][1] >= "E")
					{
						$arParams["PERMISSION"][] = $arGroups[$i][0];
						if ($arGroups[$i][0] == 2)
							break;
					}
				}

				$arSearchInd = array(
					"LID" => array(),
					"LAST_MODIFIED" => $arMessage["POST_DATE"],
					"PARAM1" => $arMessage["FORUM_ID"],
					"PARAM2" => $arMessage["TOPIC_ID"],
					"ENTITY_TYPE_ID"  => ($arMessage["NEW_TOPIC"] == "Y"? "FORUM_TOPIC": "FORUM_POST"),
					"ENTITY_ID" => ($arMessage["NEW_TOPIC"] == "Y"? $arMessage["TOPIC_ID"]: $ID),
					"USER_ID" => $arMessage["AUTHOR_ID"],
					"PERMISSIONS" => $arParams["PERMISSION"],
					"TITLE" => $arMessage["TOPIC_INFO"]["TITLE"].($arMessage["NEW_TOPIC"] == "Y" && !empty($arMessage["TOPIC_INFO"]["DESCRIPTION"]) ?
						", ".$arMessage["TOPIC_INFO"]["DESCRIPTION"] : ""),
					"TAGS" => ($arMessage["NEW_TOPIC"] == "Y" ? $arMessage["TOPIC_INFO"]["TAGS"] : ""),
					"BODY" => GetMessage("AVTOR_PREF")." ".$arMessage["AUTHOR_NAME"].". ".(textParser::killAllTags($arMessage["POST_MESSAGE"])),
					"URL" => "",
					"INDEX_TITLE" => $arMessage["NEW_TOPIC"] == "Y",
				);

				foreach ($arParams["SITE"] as $key => $val)
				{
					$arSearchInd["LID"][$key] = CForumNew::PreparePath2Message($val,
						array("FORUM_ID" => $arMessage["FORUM_ID"], "TOPIC_ID" => $arMessage["TOPIC_ID"], "MESSAGE_ID" => $arMessage["ID"],
							"SOCNET_GROUP_ID" => $arMessage["TOPIC_INFO"]["SOCNET_GROUP_ID"], "OWNER_ID" => $arMessage["TOPIC_INFO"]["OWNER_ID"],
							"PARAM1" => $arMessage["PARAM1"], "PARAM2" => $arMessage["PARAM2"]));
					if (empty($arSearchInd["URL"]) && !empty($arSearchInd["LID"][$key]))
						$arSearchInd["URL"] = $arSearchInd["LID"][$key];
				}

				if (empty($arSearchInd["URL"]))
				{
					foreach ($arParams["SITE"] as $key => $val):
						$db_lang = CLang::GetByID($key);
						if ($db_lang && $ar_lang = $db_lang->Fetch()):
							$arParams["DEFAULT_URL"] = $ar_lang["DIR"];
							break;
						endif;
					endforeach;
					$arParams["DEFAULT_URL"] .= COption::GetOptionString("forum", "REL_FPATH", "").
						"forum/read.php?FID=#FID#&TID=#TID#&MID=#MID##message#MID#";

					$arSearchInd["URL"] = CForumNew::PreparePath2Message($arParams["DEFAULT_URL"],
						array("FORUM_ID" => $arMessage["FORUM_ID"], "TOPIC_ID" => $arMessage["TOPIC_ID"], "MESSAGE_ID" => $arMessage["ID"],
							"SOCNET_GROUP_ID" => $arMessage["TOPIC_INFO"]["SOCNET_GROUP_ID"], "OWNER_ID" => $arMessage["TOPIC_INFO"]["OWNER_ID"],
							"PARAM1" => $arMessage["PARAM1"], "PARAM2" => $arMessage["PARAM2"]));
				}
				CSearch::Index("forum", $ID, $arSearchInd);
			}
		}
		return $ID;
	}

	function GetList($arOrder = Array("ID"=>"ASC"), $arFilter = Array(), $bCount = false, $iNum = 0, $arAddParams = array())
	{
		global $DB;
		$arSqlSearch = array();
		$arSqlOrder = array();
		$strSqlSearch = "";
		$strSqlOrder = "";
		$arFilter = (is_array($arFilter) ? $arFilter : array());
		$arAddParams = (is_array($arAddParams) ? $arAddParams : array());

		foreach ($arFilter as $key => $val)
		{
			$key_res = CForumNew::GetFilterOperation($key);
			$key = strtoupper($key_res["FIELD"]);
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];

			switch ($key)
			{
				case "PARAM1":
				case "AUTHOR_NAME":
				case "POST_MESSAGE_CHECK":
				case "APPROVED":
				case "NEW_TOPIC":
					if (strlen($val)<=0)
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(FM.".$key." IS NULL OR LENGTH(FM.".$key.")<=0)";
					else
						$arSqlSearch[] = ($strNegative=="Y"?" FM.".$key." IS NULL OR NOT ":"")."(FM.".$key." ".$strOperation." '".$DB->ForSql($val)."' )";
					break;
				case "PARAM2":
				case "ID":
				case "AUTHOR_ID":
				case "FORUM_ID":
				case "TOPIC_ID":
				case "ATTACH_IMG":
					if ( ($strOperation == "IN") && (!is_array($val)) && (strpos($val,",")>0) )
						$val = explode(",", $val);
					if (($strOperation!="IN") && (intVal($val) > 0))
						$arSqlSearch[] = ($strNegative=="Y"?" FM.".$key." IS NULL OR NOT ":"")."(FM.".$key." ".$strOperation." ".intVal($val)." )";
					elseif (($strOperation =="IN") && ((is_array($val) && sizeof($val)>0 && (array_sum($val) > 0)) || (strlen($val) > 0) ))
					{
						if (is_array($val))
						{
							$val_int = array();
							foreach ($val as $v)
								$val_int[] = intVal($v);
							$val = implode(", ", $val_int);
						}
						else
						{
							$val = intval($val);
						}
						$arSqlSearch[] = ($strNegative=="Y"?" NOT ":"")."(FM.".$key." IN (".$DB->ForSql($val).") )";
					}
					else
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(FM.".$key." IS NULL OR FM.".$key."<=0)";
					break;
				case "EDIT_DATE":
				case "POST_DATE":
					if (strlen($val)<=0)
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(FM.".$key." IS NULL OR LENGTH(FM.".$key.")<=0)";
					else
						$arSqlSearch[] = ($strNegative=="Y"?" FM.".$key." IS NULL OR NOT ":"")."(FM.".$key." ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "FULL")." )";
					break;
				case "PERMISSION":
					if ((is_array($val)) && (count($val)>0))
					{
						$return = array();
						foreach ($val as $value)
						{
							$str = array();
							foreach ($value as $k => $v)
							{
								$k_res = CForumNew::GetFilterOperation($k);
								$k = strToUpper($k_res["FIELD"]);
								$strNegative = $k_res["NEGATIVE"];
								$strOperation = $k_res["OPERATION"];
								switch ($k)
								{
									case "TOPIC_ID":
									case "FORUM_ID":
										if (intVal($v)<=0)
											$str[] = ($strNegative=="Y"?"NOT":"")."(FM.".$k." IS NULL OR FM.".$k."<=0)";
										else
											$str[] = ($strNegative=="Y"?" FM.".$k." IS NULL OR NOT ":"")."(FM.".$k." ".$strOperation." ".intVal($v)." )";
										break;
									case "APPROVED":
										if (strlen($v)<=0)
											$str[] = ($strNegative=="Y"?"NOT":"")."(FM.APPROVED IS NULL OR LENGTH(FM.APPROVED)<=0)";
										else
											$str[] = ($strNegative=="Y"?" FM.APPROVED IS NULL OR NOT ":"")."FM.APPROVED ".$strOperation." '".$DB->ForSql($v)."' ";
										break;
								}
							}
							$return[] = implode(" AND ", $str);
						}
						if (count($return)>0)
							$arSqlSearch[] = "(".implode(") OR (", $return).")";
					}
					break;
			}
		}
		if (count($arSqlSearch) > 0)
			$strSqlSearch = " AND (".implode(") AND (", $arSqlSearch).") ";

		if ($bCount || (is_array($arAddParams) && is_set($arAddParams, "bDescPageNumbering") && (intVal($arAddParams["nTopCount"])<=0)))
		{
			if ($bCount === "cnt_not_approved")
			{
				$strSql =
					"SELECT COUNT(FM.ID) as CNT, MAX(FM.ID) AS ABS_LAST_MESSAGE_ID,
						MIN(FM.ID) AS ABS_FIRST_MESSAGE_ID,
						MIN(CASE WHEN FM.NEW_TOPIC='Y' THEN FM.ID ELSE NULL END) AS FIRST_MESSAGE_ID,
						SUM(CASE WHEN FM.APPROVED!='Y' THEN 1 ELSE 0 END) as CNT_NOT_APPROVED,
						MAX(CASE WHEN FM.APPROVED='Y' THEN FM.ID ELSE 0 END) AS LAST_MESSAGE_ID
					FROM b_forum_message FM
					WHERE 1 = 1 ".$strSqlSearch;
				$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				if ($db_res && $ar_res = $db_res->Fetch()):
					return $ar_res;
				endif;
				return false;
			}
			else
			{
				$strSql = "SELECT COUNT(FM.ID) as CNT".(
					$bCount === "cnt_and_last_mid" ? ", MAX(FM.ID) as LAST_MESSAGE_ID" : "")."
					FROM b_forum_message FM
					WHERE 1 = 1 ".$strSqlSearch;
				$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				$iCnt = 0;
				$ar_res = array();
				if ($db_res && $ar_res = $db_res->Fetch())
					$iCnt = intVal($ar_res["CNT"]);
				if ($bCount === "cnt_and_last_mid"):
					return $ar_res;
				elseif ($bCount):
					return $iCnt;
				endif;
			}
		}

		foreach ($arOrder as $by=>$order)
		{
			$by = strtoupper($by); $order = strtoupper($order);
			if ($order!="ASC") $order = "DESC";
			if ($by == "AUTHOR_NAME") $arSqlOrder[] = " FM.AUTHOR_NAME ".$order." ";
			elseif ($by == "EDIT_DATE") $arSqlOrder[] = " FM.EDIT_DATE ".$order." ";
			elseif ($by == "POST_DATE") $arSqlOrder[] = " FM.POST_DATE ".$order." ";
			elseif ($by == "FORUM_ID") $arSqlOrder[] = " FM.FORUM_ID ".$order." ";
			elseif ($by == "TOPIC_ID") $arSqlOrder[] = " FM.TOPIC_ID ".$order." ";
			elseif ($by == "NEW_TOPIC") $arSqlOrder[] = " FM.NEW_TOPIC ".$order." ";
			elseif ($by == "APPROVED") $arSqlOrder[] = " FM.APPROVED ".$order." ";
			else
			{
				$arSqlOrder[] = " FM.ID ".$order." ";
				$by = "ID";
			}
		}
		$arSqlOrder = array_unique($arSqlOrder);
		DelDuplicateSort($arSqlOrder);
		if(count($arSqlOrder) > 0)
			$strSqlOrder = " ORDER BY ".implode(", ", $arSqlOrder);

		$strSql =
			"SELECT FM.ID,
				FM.AUTHOR_ID, FM.AUTHOR_NAME, FM.AUTHOR_EMAIL, FM.AUTHOR_IP,
				FM.USE_SMILES, FM.POST_MESSAGE, FM.POST_MESSAGE_HTML, FM.POST_MESSAGE_FILTER,
				FM.FORUM_ID, FM.TOPIC_ID, FM.NEW_TOPIC,
				FM.APPROVED, FM.SOURCE_ID, FM.POST_MESSAGE_CHECK, FM.GUEST_ID, FM.AUTHOR_REAL_IP, FM.ATTACH_IMG, FM.XML_ID,
				".$DB->DateToCharFunction("FM.POST_DATE", "FULL")." as POST_DATE,
				FM.EDITOR_ID, FM.EDITOR_NAME, FM.EDITOR_EMAIL, FM.EDIT_REASON,
				FU.SHOW_NAME, U.LOGIN, U.NAME, U.SECOND_NAME, U.LAST_NAME,
				".$DB->DateToCharFunction("FM.EDIT_DATE", "FULL")." as EDIT_DATE, FM.PARAM1, FM.PARAM2, FM.HTML, FM.MAIL_HEADER".
				(!empty($arAddParams["sNameTemplate"]) ?
					",\n\t".CForumUser::GetFormattedNameFieldsForSelect(array_merge(
						$arAddParams, array(
						"sUserTablePrefix" => "U.",
						"sForumUserTablePrefix" => "FU.",
						"sFieldName" => "AUTHOR_NAME_FRMT")), false) : "")."
			FROM b_forum_message FM
				LEFT JOIN b_forum_user FU ON (FM.AUTHOR_ID = FU.USER_ID)
				LEFT JOIN b_user U ON (FM.AUTHOR_ID = U.ID)
			WHERE 1 = 1
			".$strSqlSearch."
			".$strSqlOrder;

		$iNum = intVal($iNum);
		if (($iNum>0) || (is_array($arAddParams) && (intVal($arAddParams["nTopCount"])>0)))
		{
			$iNum = ($iNum > 0) ? $iNum : intVal($arAddParams["nTopCount"]);
			$strSql .= " LIMIT 0,".$iNum;
		}
		if (!$iNum && is_array($arAddParams) && is_set($arAddParams, "bDescPageNumbering") && (intVal($arAddParams["nTopCount"])<=0))
		{
			$db_res =  new CDBResult();
			$db_res->NavQuery($strSql, $iCnt, $arAddParams);
		}
		else
		{
			$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return new _CMessageDBResult($db_res, $arAddParams);
	}

	function GetListEx($arOrder = Array("ID"=>"ASC"), $arFilter = Array(), $bCount = false, $iNum = 0, $arAddParams = array())
	{
		global $DB;
		$arSqlSearch = array();
		$arSqlOrder = array();
		$arSqlFrom = array();
		$arSqlSelect = array();
		$arSqlGroup = array();
		$strSqlSearch = "";
		$strSqlOrder = "";
		$strSqlFrom = "";
		$strSqlSelect = "";
		$strSqlGroup = "";
		$UseGroup = false;
		$arFilter = (is_array($arFilter) ? $arFilter : array());
		$arAddParams = (is_array($arAddParams) ? $arAddParams : array());

		foreach ($arFilter as $key => $val)
		{
			$key_res = CForumNew::GetFilterOperation($key);
			$key = strtoupper($key_res["FIELD"]);
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];

			switch ($key)
			{
				case "PARAM1":
				case "AUTHOR_NAME":
				case "POST_MESSAGE_CHECK":
				case "APPROVED":
				case "NEW_TOPIC":
				case "POST_MESSAGE":
					if ($strOperation == "LIKE")
						$val = "%".$val."%";
					if (strlen($val)<=0)
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(FM.".$key." IS NULL OR LENGTH(FM.".$key.")<=0)";
					else
						$arSqlSearch[] = ($strNegative=="Y"?" FM.".$key." IS NULL OR NOT ":"")."(FM.".$key." ".$strOperation." '".$DB->ForSql($val)."' )";
					break;
				case "PARAM2":
				case "ID":
				case "AUTHOR_ID":
				case "FORUM_ID":
				case "TOPIC_ID":
				case "ATTACH_IMG":
					if ( ($strOperation == "IN") && (!is_array($val)) && (strpos($val,",")>0) )
						$val = explode(",", $val);
					if (($strOperation!="IN") && (intVal($val) > 0))
						$arSqlSearch[] = ($strNegative=="Y"?" FM.".$key." IS NULL OR NOT ":"")."(FM.".$key." ".$strOperation." ".intVal($val)." )";
					elseif (($strOperation =="IN") && ((is_array($val) && (array_sum($val) > 0)) || (strlen($val) > 0) ))
					{
						if (is_array($val))
						{
							$val_int = array();
							foreach ($val as $v)
								$val_int[] = intVal($v);
							$val = implode(", ", $val_int);
						}
						else
						{
							$val = intval($val);
						}
						$arSqlSearch[] = ($strNegative=="Y"?" NOT ":"")."(FM.".$key." IN (".$DB->ForSql($val).") )";
					}
					else
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(FM.".$key." IS NULL OR FM.".$key."<=0)";
					break;
				case "POINTS_TO_AUTHOR_ID":
					if (intVal($val) > 0)
					{
						$arSqlSelect["FR.POINTS"] = "FR.POINTS";
						$arSqlSelect["FR.DATE_UPDATE"] = "FR.DATE_UPDATE";
						$arSqlFrom["FR"] = "LEFT JOIN b_forum_user_points FR ON ((FM.AUTHOR_ID = FR.TO_USER_ID) AND (FR.FROM_USER_ID=".intVal($val)."))";
					}
					break;
				case "POST_DATE":
					if (strlen($val)<=0)
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(FM.".$key." IS NULL OR LENGTH(FM.".$key.")<=0)";
					else
						$arSqlSearch[] = ($strNegative=="Y"?" FM.".$key." IS NULL OR NOT ":"")."(FM.".$key." ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "FULL").")";
					break;
				case "USER_ID":
//					$arSqlSelect["LAST_VISIT"] = $DB->DateToCharFunction("FUT.LAST_VISIT", "FULL");
					if(intVal($val) > 0)
					{
						$arSqlFrom["FUT"] = "
							LEFT JOIN b_forum_user_topic FUT ON (FT.ID = FUT.TOPIC_ID AND FUT.USER_ID=".intVal($val).")";
					}
					break;
				case "NEW_MESSAGE":
					if (strLen($val) > 0 && intVal($arFilter["USER_ID"]) > 0)
					{
						$arSqlFrom["FUT"] = "
						LEFT JOIN b_forum_user_topic FUT ON (FM.TOPIC_ID = FUT.TOPIC_ID AND FUT.USER_ID=".intVal($arFilter["USER_ID"]).")";
						$arSqlSearch[] = "
							(FUT.LAST_VISIT IS NOT NULL AND FM.POST_DATE > FUT.LAST_VISIT)
							OR
							(FUT.LAST_VISIT IS NULL AND FM.POST_DATE ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "FULL").")
							";
					}
				break;
				case "USER_GROUP":
					if (!empty($val))
					{
						if (!is_array($val))
							$val = explode(",", $val);
						if (!in_array(2, $val))
							$val[] = 2;
						$val = implode(",", $val);
						$arSqlFrom["FP"] = "LEFT JOIN b_forum_perms FP ON (FP.FORUM_ID=FM.FORUM_ID)";
						$arSqlSearch[] = "FP.GROUP_ID IN (".$DB->ForSql($val).") AND ((FP.PERMISSION IN ('E','I','M') AND FM.APPROVED='Y') OR (FP.PERMISSION IN ('Q','U','Y')))";
						$UseGroup = true;
					}
				break;
				case "TOPIC_SOCNET_GROUP_ID":
						$arSqlFrom["FT"] = "
							LEFT JOIN b_forum_topic FT ON (FT.ID = FM.TOPIC_ID)";
						$arSqlSearch[] = "FT.SOCNET_GROUP_ID = ".IntVal($val);
						$arSqlSelect[] = "FT.SOCNET_GROUP_ID as TOPIC_SOCNET_GROUP_ID";
					break;
				case "TOPIC_OWNER_ID":
						$arSqlFrom["FT"] = "
							LEFT JOIN b_forum_topic FT ON (FT.ID = FM.TOPIC_ID)";
						$arSqlSearch[] = "FT.OWNER_ID = ".IntVal($val);
						$arSqlSelect[] = "FT.OWNER_ID as TOPIC_OWNER_ID";
					break;
				case "TOPIC":
						$arSqlFrom["FT"] = "
							LEFT JOIN b_forum_topic FT ON (FT.ID = FM.TOPIC_ID)";
						$arSqlSelect[] = "FT.TITLE";
						$arSqlSelect[] = "FT.DESCRIPTION AS TOPIC_DESCRIPTION";
						$arSqlSelect[] = $DB->DateToCharFunction("FT.START_DATE", "FULL")." as START_DATE";
						$arSqlSelect[] = "FT.USER_START_NAME";
						$arSqlSelect[] = "FT.USER_START_ID";
				break;
				case "TOPIC_TITLE":
				case "TITLE":
					$arSqlFrom["FT"] = "
						LEFT JOIN b_forum_topic FT ON (FT.ID = FM.TOPIC_ID)";
					$key = "TITLE";
					if ($strOperation == "LIKE")
						$val = "%".$val."%";
					if (strlen($val)<=0)
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(FT.".$key." IS NULL OR LENGTH(FT.".$key.")<=0)";
					else
						$arSqlSearch[] = ($strNegative=="Y"?" FT.".$key." IS NULL OR NOT ":"")."(FT.".$key." ".$strOperation." '".$DB->ForSql($val)."' )";
				break;
			}
		}
		foreach ($arOrder as $by=>$order)
		{
			$by = strtoupper($by); $order = strtoupper($order);
			if ($order!="ASC") $order = "DESC";
			if (in_array($by, array("FORUM_ID", "TOPIC_ID", "USE_SMILES", "NEW_TOPIC", "APPROVED",
				"POST_DATE", "POST_MESSAGE", "ATTACH_IMG", "PARAM1", "PARAM2",
				"AUTHOR_ID", "AUTHOR_NAME", "AUTHOR_EMAIL", "AUTHOR_IP", "AUTHOR_REAL_IP",  "GUEST_ID",
				"EDITOR_ID", "EDITOR_NAME", "EDITOR_EMAIL", "EDIT_REASON", "EDIT_DATE", "HTML"))):
				$arSqlOrder[] = "FM.".$by." ".$order;
			elseif ($by == "SORT" || $by == "NAME"):
				$arSqlFrom["F"] = "
				LEFT JOIN b_forum F ON (F.ID = FM.FORUM_ID)";
				$arSqlSelect["F.".$by] = "F.".$by;
				$arSqlOrder[] = "F_M.".$by." ".$order;
			else:
				$arSqlOrder[] = "FM.ID ".$order;
				$by = "ID";
			endif;
		}
		$arSqlOrder = array_unique($arSqlOrder);
		DelDuplicateSort($arSqlOrder);
		if(count($arSqlOrder) > 0)
			$strSqlOrder = " ORDER BY ".implode(", ", $arSqlOrder);

		if (count($arSqlSearch) > 0)
			$strSqlSearch = " AND (".implode(") AND (", $arSqlSearch).") ";
		if (count($arSqlSelect) > 0)
			$strSqlSelect = ",\n\t".implode(", ", $arSqlSelect);
		if (count($arSqlFrom) > 0)
			$strSqlFrom .= "\n\t".implode("\n\t", $arSqlFrom);
		if ($UseGroup)
		{
			foreach ($arSqlSelect as $key => $val)
			{
				if (substr($key, 0, 1) != "!")
					$arSqlGroup[$key] = $val;
			}
			if (!empty($arSqlGroup)):
				$strSqlGroup = ", ".implode(", ", $arSqlGroup);
			endif;
		}

		if ($bCount || (is_set($arAddParams, "bDescPageNumbering") && intVal($arAddParams["nTopCount"]) <= 0))
		{
			$strSql =
				"SELECT
					COUNT(FM.ID) as CNT,
					MAX(FM.ID) AS LAST_MESSAGE_ID
				FROM b_forum_message FM
					".$strSqlFrom."
				WHERE 1 = 1
					".$strSqlSearch;

			if ($bCount === 3)
				$strSql .= "GROUP BY FM.TOPIC_ID";
			$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($bCount === 3)
				return $db_res;
			$iCnt = 0; $iLAST_MESSAGE_ID = 0;
			if ($ar_res = $db_res->Fetch())
			{
				$iCnt = intVal($ar_res["CNT"]);
				$iLAST_MESSAGE_ID = intVal($ar_res["LAST_MESSAGE_ID"]);
			}
			if ($bCount === 4)
				return array("CNT" => $iCnt, "LAST_MESSAGE_ID" => $iLAST_MESSAGE_ID);

			if ($bCount)
				return $iCnt;
		}

		if ($UseGroup)
		{
			$strSql =
				"SELECT F_M.*, FM.FORUM_ID, FM.TOPIC_ID, FM.USE_SMILES, FM.NEW_TOPIC, \n".
				"	FM.APPROVED, FM.SOURCE_ID, \n".
				"	".$DB->DateToCharFunction("FM.POST_DATE", "FULL")." as POST_DATE, \n".
				"	FM.POST_MESSAGE, FM.POST_MESSAGE_HTML, FM.POST_MESSAGE_FILTER, \n".
				"	FM.ATTACH_IMG, FM.XML_ID, FM.PARAM1, FM.PARAM2, \n".
				"	FM.AUTHOR_ID, FM.AUTHOR_NAME, FM.AUTHOR_EMAIL, \n".
				"	FM.AUTHOR_IP, FM.AUTHOR_REAL_IP, FM.GUEST_ID, \n".
				"	FM.EDITOR_ID, FM.EDITOR_NAME, FM.EDITOR_EMAIL, FM.EDIT_REASON, \n".
				"	".$DB->DateToCharFunction("FM.EDIT_DATE", "FULL")." as EDIT_DATE, \n".
				"	FM.HTML, FM.MAIL_HEADER, \n".
				"	FU.SHOW_NAME, FU.DESCRIPTION, FU.NUM_POSTS, FU.POINTS as NUM_POINTS, FU.SIGNATURE, FU.AVATAR, \n".
				"	".$DB->DateToCharFunction("FU.DATE_REG", "SHORT")." as DATE_REG, \n".
				"	U.LOGIN, U.NAME, U.SECOND_NAME, U.LAST_NAME, FU.RANK_ID, U.PERSONAL_WWW, U.PERSONAL_GENDER, \n".
				"	U.EMAIL, U.PERSONAL_ICQ, U.PERSONAL_CITY, U.PERSONAL_COUNTRY".
				(!empty($arAddParams["sNameTemplate"]) ?
					",\n\t".CForumUser::GetFormattedNameFieldsForSelect(array_merge(
						$arAddParams, array(
						"sUserTablePrefix" => "U.",
						"sForumUserTablePrefix" => "FU.",
						"sFieldName" => "AUTHOR_NAME_FRMT")), false) : "")." \n".
				"FROM ( \n".
				"		SELECT FM.ID".$strSqlSelect." \n".
				"		FROM b_forum_message FM \n".
				"			LEFT JOIN b_forum_user FU ON FM.AUTHOR_ID = FU.USER_ID \n".
				"			LEFT JOIN b_user U ON FM.AUTHOR_ID = U.ID \n".
				"			".$strSqlFrom." \n".
				"		WHERE (1=1 ".$strSqlSearch.") \n".
				"		GROUP BY FM.ID".$strSqlGroup." \n".
				"	) F_M \n".
				"	INNER JOIN b_forum_message FM ON (F_M.ID = FM.ID) \n".
				"	LEFT JOIN b_forum_user FU ON (FM.AUTHOR_ID = FU.USER_ID) \n".
				"	LEFT JOIN b_user U ON (FM.AUTHOR_ID = U.ID) \n".
				$strSqlOrder;
		}
		else
		{
			$strSql =
				"SELECT FM.ID, FM.FORUM_ID, FM.TOPIC_ID, FM.USE_SMILES, FM.NEW_TOPIC, \n".
				"	FM.APPROVED, FM.SOURCE_ID, \n".
				"	".$DB->DateToCharFunction("FM.POST_DATE", "FULL")." as POST_DATE, \n".
				"	FM.POST_MESSAGE, FM.POST_MESSAGE_HTML, FM.POST_MESSAGE_FILTER, \n".
				"	FM.ATTACH_IMG, FM.XML_ID, FM.PARAM1, FM.PARAM2, \n".
				"	FM.AUTHOR_ID, FM.AUTHOR_NAME, FM.AUTHOR_EMAIL, \n".
				"	FM.AUTHOR_IP, FM.AUTHOR_REAL_IP, FM.GUEST_ID, \n".
				"	FM.EDITOR_ID, FM.EDITOR_NAME, FM.EDITOR_EMAIL, FM.EDIT_REASON, \n".
				"	".$DB->DateToCharFunction("FM.EDIT_DATE", "FULL")." as EDIT_DATE, \n".
				"	FM.HTML, FM.MAIL_HEADER, \n".
				"	FU.SHOW_NAME, FU.DESCRIPTION, FU.NUM_POSTS, FU.POINTS as NUM_POINTS, FU.SIGNATURE, FU.AVATAR, \n".
				"	".$DB->DateToCharFunction("FU.DATE_REG", "SHORT")." as DATE_REG, \n".
				"	U.LOGIN, U.NAME, U.SECOND_NAME, U.LAST_NAME, FU.RANK_ID, U.PERSONAL_WWW, U.PERSONAL_GENDER, \n".
				"	U.EMAIL, U.PERSONAL_ICQ, U.PERSONAL_CITY, U.PERSONAL_COUNTRY".
				(!empty($arAddParams["sNameTemplate"]) ?
					",\n\t".CForumUser::GetFormattedNameFieldsForSelect(array_merge(
						$arAddParams, array(
						"sUserTablePrefix" => "U.",
						"sForumUserTablePrefix" => "FU.",
						"sFieldName" => "AUTHOR_NAME_FRMT")), false)."\n" : "").$strSqlSelect."\n".
				"FROM b_forum_message FM \n".
				"	LEFT JOIN b_forum_user FU ON (FM.AUTHOR_ID = FU.USER_ID) \n".
				"	LEFT JOIN b_user U ON (FM.AUTHOR_ID = U.ID) \n".
				"	".$strSqlFrom." \n".
				"WHERE 1 = 1 ".$strSqlSearch." \n".
				$strSqlOrder;
		}

		$iNum = intVal($iNum);
		if ($iNum > 0 || intVal($arAddParams["nTopCount"]) > 0):
			$iNum = ($iNum > 0) ? $iNum : intVal($arAddParams["nTopCount"]);
			$strSql .= "\nLIMIT 0,".$iNum;
		endif;

		if (!$iNum && is_array($arAddParams) && is_set($arAddParams, "bDescPageNumbering") && (intVal($arAddParams["nTopCount"])<=0))
		{
			$db_res =  new CDBResult();
			$db_res->NavQuery($strSql, $iCnt, $arAddParams);
		}
		else
		{
			$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		return new _CMessageDBResult($db_res, $arAddParams);
	}

	function QueryFirstUnread($arFilter) // out-of-date function
	{
		$db_res = CForumMessage::GetList(array("ID"=>"ASC"), $arFilter, false, 1);
		return $db_res;
	}
}

class CForumFiles extends CAllForumFiles
{
	function GetList($arOrder = Array("ID"=>"ASC"), $arFilter = Array(), $iNum = 0, $arAddParams = array())
	{
		global $DB;
		$arSqlSearch = array();
		$arSqlOrder = array();
		$strSqlSearch = "";
		$strSqlOrder = "";
		$iCnt = 0;
		$iNum = intVal($iNum);
		$arFilter = (is_array($arFilter) ? $arFilter : array());
		$arAddParams = (is_array($arAddParams) ? $arAddParams : array());
		if (intVal($arAddParams["nTopCount"]) > 0)
			unset($arAddParams["bDescPageNumbering"]);

		foreach ($arFilter as $key => $val)
		{
			$key_res = CForumNew::GetFilterOperation($key);
			$key = strtoupper($key_res["FIELD"]);
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];

			switch ($key)
			{
				case "PARAM1":
				case "AUTHOR_NAME":
				case "POST_MESSAGE_CHECK":
				case "APPROVED":
					if (strlen($val)<=0)
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(FM.".$key." IS NULL OR LENGTH(FM.".$key.")<=0)";
					else
						$arSqlSearch[] = ($strNegative=="Y"?" FM.".$key." IS NULL OR NOT ":"")."(FM.".$key." ".$strOperation." '".$DB->ForSql($val)."' )";
					break;
				// to table b_forum_message
				case "PARAM2":
				case "FORUM_ID":
				case "TOPIC_ID":
				case "AUTHOR_ID":
					if (($strOperation!="IN") && (intVal($val) > 0))
						$arSqlSearch[] = ($strNegative=="Y"?" FM.".$key." IS NULL OR NOT ":"")."(FM.".$key." ".$strOperation." ".intVal($val)." )";
					elseif ($strOperation == "IN" && (is_array($val) && array_sum($val) > 0 || is_string($val) && strlen($val) > 0))
					{
						if (is_array($val))
						{
							$val_int = array();
							foreach ($val as $v)
								$val_int[] = intVal($v);
							$val = implode(", ", $val_int);
						}
						$arSqlSearch[] = ($strNegative=="Y"?" NOT ":"")."(FM.".$key." IN (".$DB->ForSql($val).") )";
					}
					else
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(FM.".$key." IS NULL OR FM.".$key."<=0)";
					break;
				// to table b_forum_file
				case "FILE_FORUM_ID":
				case "FILE_TOPIC_ID":
				case "FILE_MESSAGE_ID":
					$key = substr($key, 5);
					if ($strOperation != "IN" && intVal($val) > 0)
					{
						$res = ($strNegative=="Y"?" FF.".$key." IS NULL OR NOT ":"")."(FF.".$key." ".$strOperation." ".intVal($val)." ) OR ".
							"".($strNegative=="Y"?"NOT":"")."(FF.".$key." IS NULL OR FF.".$key."<=0)";
						$arSqlSearch[] = $res;
						break;
					}
					elseif ($strOperation == "IN" && (is_array($val) && array_sum($val) > 0 || is_string($val) && strlen($val) > 0))
					{
						$val = (!is_array($val) ? explode(",", $val) : $val);
						$val_int = array();
						foreach ($val as $k => $v):
							$val_int[] = intVal($v);
						endforeach;
						$val = implode(",", $val_int);
						if (strLen($val) > 0)
						{
							$arSqlSearch[] = ($strNegative=="Y"?" NOT ":"")."(FF.".$key." IN (".$DB->ForSql($val).") )";
							break;
						}
					}
					$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(FF.".$key." IS NULL OR FF.".$key."<=0)";
					break;
				case "FILE_ID":
				case "MESSAGE_ID":
				case "USER_ID":
					if (($strOperation!="IN") && (intVal($val) > 0 || $val === 0) )
					{
						$arSqlSearch[] = ($strNegative=="Y"?" FF.".$key." IS NULL OR NOT ":"")."(FF.".$key." ".$strOperation." ".intVal($val)." )";
						break;
					}
					elseif ($strOperation =="IN" && (is_array($val) && array_sum($val) > 0 || strlen($val) > 0))
					{
						$val = (!is_array($val) ? explode(",", $val) : $val);
						$val_int = array();
						foreach ($val as $k => $v):
							$val_int[] = intVal($v);
						endforeach;
						$val = implode(",", $val_int);
						if (strLen($val) > 0)
						{
							$arSqlSearch[] = ($strNegative=="Y"?" NOT ":"")."(FF.".$key." IN (".$DB->ForSql($val).") )";
							break;
						}
					}
					$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(FF.".$key." IS NULL OR FF.".$key."<=0)";
					break;
				case "EDIT_DATE":
				case "POST_DATE":
					if (strlen($val)<=0)
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(FM.".$key." IS NULL OR LENGTH(FM.".$key.")<=0)";
					else
						$arSqlSearch[] = ($strNegative=="Y"?" FM.".$key." IS NULL OR NOT ":"")."(FM.".$key." ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "FULL")." )";
					break;
				case "PERMISSION":
					if ((is_array($val)) && (count($val)>0))
					{
						$return = array();
						foreach ($val as $value)
						{
							$str = array();
							foreach ($value as $k => $v)
							{
								$k_res = CForumNew::GetFilterOperation($k);
								$k = strToUpper($k_res["FIELD"]);
								$strNegative = $k_res["NEGATIVE"];
								$strOperation = $k_res["OPERATION"];
								switch ($k)
								{
									case "TOPIC_ID":
									case "FORUM_ID":
										if (intVal($v)<=0)
											$str[] = ($strNegative=="Y"?"NOT":"")."(FM.".$k." IS NULL OR FM.".$k."<=0)";
										else
											$str[] = ($strNegative=="Y"?" FM.".$k." IS NULL OR NOT ":"")."(FM.".$k." ".$strOperation." ".intVal($v)." )";
										break;
									case "APPROVED":
										if (strlen($v)<=0)
											$str[] = ($strNegative=="Y"?"NOT":"")."(FM.APPROVED IS NULL OR LENGTH(FM.APPROVED)<=0)";
										else
											$str[] = ($strNegative=="Y"?" FM.APPROVED IS NULL OR NOT ":"")."FM.APPROVED ".$strOperation." '".$DB->ForSql($v)."' ";
										break;
								}
							}
							$return[] = implode(" AND ", $str);
						}
						if (count($return)>0)
							$arSqlSearch[] = "(".implode(") OR (", $return).")";
					}
					break;
			}
		}
		if (count($arSqlSearch) > 0)
			$strSqlSearch = " AND (".implode(") AND (", $arSqlSearch).") ";

		foreach ($arOrder as $by=>$order)
		{
			$by = strtoupper($by); $order = strtoupper($order);
			if ($order!="ASC") $order = "DESC";
			if ($by == "FILE_ID") $arSqlOrder[] = " FF.FILE_ID ".$order." ";
			elseif ($by == "FORUM_ID") $arSqlOrder[] = " FF.FORUM_ID ".$order." ";
			elseif ($by == "TOPIC_ID") $arSqlOrder[] = " FF.TOPIC_ID ".$order." ";
			elseif ($by == "MESSAGE_ID") $arSqlOrder[] = " FF.MESSAGE_ID ".$order." ";
			else
			{
				$arSqlOrder[] = " FF.FILE_ID ".$order." ";
				$by = "FILE_ID";
			}
		}
		DelDuplicateSort($arSqlOrder);
		if(count($arSqlOrder) > 0)
			$strSqlOrder = " ORDER BY ".implode(", ", $arSqlOrder);

		$strSql =
			"SELECT BF.ID, BF.HEIGHT, BF.WIDTH, BF.FILE_SIZE, BF.CONTENT_TYPE, BF.SUBDIR, BF.FILE_NAME,
				BF.ORIGINAL_NAME, FF.FILE_ID, FF.FORUM_ID,  FF.TOPIC_ID,  FF.MESSAGE_ID,  FF.USER_ID, FF.HITS,
				".$DB->DateToCharFunction("FF.TIMESTAMP_X", "FULL")." as TIMESTAMP_X, BF.HANDLER_ID
			FROM b_forum_file FF
				INNER JOIN b_file BF ON (BF.ID = FF.FILE_ID)
				LEFT JOIN b_forum_message FM ON (FM.ID=FF.MESSAGE_ID)
			WHERE 1 = 1
			".$strSqlSearch."
			".$strSqlOrder;
		if ($iNum > 0 || intVal($arAddParams["nTopCount"]) > 0)
		{
			$iNum = ($iNum > 0) ? $iNum : intVal($arAddParams["nTopCount"]);
			$strSql = "SELECT * FROM(".$strSql.") WHERE ROWNUM<=".$iNum;
		}
		elseif (is_set($arAddParams, "bDescPageNumbering"))
		{
			$iCnt = 0;
			$strSql1 = "SELECT COUNT(FM.ID) as CNT FROM b_forum_message FM WHERE 1 = 1 ".$strSqlSearch;
			$db_res = $DB->Query($strSql1, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($ar_res = $db_res->Fetch())
				$iCnt = intVal($ar_res["CNT"]);
			$db_res =  new CDBResult();
			$db_res->NavQuery($strSql, $iCnt, $arAddParams);
		}
		else
		{
			$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		return $db_res;
	}

	function CleanUp()
	{
		global $DB;
		$period = 24*3600;
		$db_res = $DB->Query("SELECT FF.FILE_ID FROM b_forum_file FF WHERE ((UNIX_TIMESTAMP(CURRENT_TIMESTAMP) - UNIX_TIMESTAMP(FF.TIMESTAMP_X)) >= ".$period.
			" AND (FF.TOPIC_ID IS NULL OR FF.TOPIC_ID <= 0) AND (FF.MESSAGE_ID IS NULL OR FF.MESSAGE_ID <= 0))", false, "FILE: ".__FILE__." LINE:".__LINE__);
		if ($db_res && $res = $db_res->Fetch())
		{
			do
			{
//				$DB->Query("DELETE FROM b_forum_file WHERE FILE_ID=".$res["FILE_ID"], false, "FILE: ".__FILE__." LINE:".__LINE__);
				CFile::Delete($res["FILE_ID"]);
			} while ($res = $db_res->Fetch());
		}
		return "CForumFiles::CleanUp();";
	}
}
?>