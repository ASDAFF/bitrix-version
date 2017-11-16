<?
IncludeModuleLangFile(__FILE__);

class CAllClusterDBNode
{
	function GetByID($node_id, $arVirtNode=false)
	{
		global $DB, $CACHE_MANAGER;
		static $arNodes = false;
		static $arVirtNodes = array();

		//This code sets and gets virtual nodes
		//needed to test connection just before
		//save node credentials into db
		if(preg_match('/^v(\d+)$/', $node_id))
		{
			if(is_array($arVirtNode))
			{
				$arVirtNodes[$node_id] = $arVirtNode;
				return true;
			}
			else
			{
				return $arVirtNodes[$node_id];
			}
		}

		//Normal method continues here
		$node_id = intval($node_id);
		if($arNodes === false)
		{
			$cache_id = "db_nodes";
			if(
				CACHED_b_cluster_dbnode !== false
				&& $CACHE_MANAGER->Read(CACHED_b_cluster_dbnode, $cache_id, "b_cluster_dbnode")
			)
			{
				$arNodes = $CACHE_MANAGER->Get($cache_id);
			}
			else
			{
				$arNodes = array();

				$rs = $DB->Query("SELECT * FROM b_cluster_dbnode ORDER BY ID", false, '', array('fixed_connection'=>true));
				while($ar = $rs->Fetch())
					$arNodes[intval($ar['ID'])] = $ar;

				if(CACHED_b_cluster_dbnode !== false)
					$CACHE_MANAGER->Set($cache_id, $arNodes);
			}
		}

		return $arNodes[$node_id];
	}

	function Add($arFields)
	{
		global $DB, $CACHE_MANAGER;

		if(!$this->CheckFields($arFields, 0))
			return false;

		if(!array_key_exists("ACTIVE", $arFields))
			$arFields["ACTIVE"] = "Y";

		$ID = $DB->Add("b_cluster_dbnode", $arFields);

		if(CACHED_b_cluster_dbnode !== false)
			$CACHE_MANAGER->CleanDir("b_cluster_dbnode");

		return $ID;
	}

	function Delete($ID)
	{
		global $DB, $CACHE_MANAGER;
		$ID = intval($ID);

		$res = $DB->Query("select ID from b_cluster_dbnode WHERE ID=1 OR MASTER_ID = ".$ID, false, '', array('fixed_connection'=>true));
		while($ar = $res->Fetch())
		{
			if(!CClusterSlave::Stop($ar["ID"]))
				return false;
		}

		if($res)
			$res = $DB->Query("DELETE FROM b_cluster_dbnode WHERE ID = ".$ID, false, '', array('fixed_connection'=>true));

		if(CACHED_b_cluster_dbnode !== false)
			$CACHE_MANAGER->CleanDir("b_cluster_dbnode");

		return $res;
	}

	function Update($ID, $arFields)
	{
		global $DB, $CACHE_MANAGER;
		$ID = intval($ID);

		if($ID <= 0)
			return false;

		if(!$this->CheckFields($arFields, $ID))
			return false;

		unset($arFields["GROUP_ID"]);

		$strUpdate = $DB->PrepareUpdate("b_cluster_dbnode", $arFields);
		if(strlen($strUpdate) > 0)
		{
			$strSql = "
				UPDATE b_cluster_dbnode SET
				".$strUpdate."
				WHERE ID = ".$ID."
			";
			if(!$DB->Query($strSql, false, '', array('fixed_connection'=>true)))
				return false;
		}

		if(CACHED_b_cluster_dbnode !== false)
			$CACHE_MANAGER->CleanDir("b_cluster_dbnode");

		return true;
	}

	function SetOffline($node_id)
	{
		global $DB, $CACHE_MANAGER, $APPLICATION;

		$rs = $DB->Query("
			UPDATE b_cluster_dbnode SET
			STATUS = 'OFFLINE'
			WHERE ID = ".intval($node_id)."
			AND STATUS <> 'OFFLINE'
		", false, '', array('fixed_connection'=>true));
		if($rs->AffectedRowsCount() > 0)
		{
			if(CACHED_b_cluster_dbnode !== false)
				$CACHE_MANAGER->CleanDir("b_cluster_dbnode");

			if(!CAgent::AddAgent("CClusterDBNode::BringOnline();", "cluster", "N", 10))
				$APPLICATION->ResetException();
		}
	}

	function SetOnline($node_id)
	{
		global $DB, $CACHE_MANAGER;

		$DB->Query("
			UPDATE b_cluster_dbnode SET
			STATUS = 'ONLINE'
			WHERE ID = ".intval($node_id)."
		", false, '', array('fixed_connection'=>true));

		if(CACHED_b_cluster_dbnode !== false)
			$CACHE_MANAGER->CleanDir("b_cluster_dbnode");
	}

	function BringOnline()
	{
		$rsOfflineNodes = CClusterDBNode::GetList(array(), array("=STATUS" => "OFFLINE"), array("ID"));
		if($arNode = $rsOfflineNodes->Fetch())
		{
			$i++;
			ob_start();
			$nodeDB = CDatabase::GetDBNodeConnection($arNode["ID"], true, false);
			ob_end_clean();
			if(is_object($nodeDB))
				CClusterDBNode::SetOnline($arNode["ID"]);

			return "CClusterDBNode::BringOnline();";
		}
	}

	function GetList($arOrder=false, $arFilter=false, $arSelect=false)
	{
		global $DB;

		if(!is_array($arSelect))
			$arSelect = array();
		if(count($arSelect) < 1)
			$arSelect = array(
				"ID",
				"ACTIVE",
				"ROLE_ID",
				"NAME",
				"DESCRIPTION",
				"DB_HOST",
				"DB_NAME",
				"DB_LOGIN",
				"DB_PASSWORD",
				"MASTER_ID",
				"SERVER_ID",
				"STATUS",
				"WEIGHT",
				"SELECTABLE",
			);

		if(!is_array($arOrder))
			$arOrder = array();

		$arQueryOrder = array();
		foreach($arOrder as $strColumn => $strDirection)
		{
			$strColumn = strtoupper($strColumn);
			$strDirection = strtoupper($strDirection)=="ASC"? "ASC": "DESC";
			switch($strColumn)
			{
				case "ID":
				case "NAME":
					$arSelect[] = $strColumn;
					$arQueryOrder[$strColumn] = $strColumn." ".$strDirection;
					break;
			}
		}

		$arQuerySelect = array();
		foreach($arSelect as $strColumn)
		{
			$strColumn = strtoupper($strColumn);
			switch($strColumn)
			{
				case "ID":
				case "ACTIVE":
				case "ROLE_ID":
				case "NAME":
				case "DESCRIPTION":
				case "DB_HOST":
				case "DB_NAME":
				case "DB_LOGIN":
				case "DB_PASSWORD":
				case "MASTER_ID":
				case "SERVER_ID":
				case "STATUS":
				case "WEIGHT":
				case "SELECTABLE":
					$arQuerySelect[$strColumn] = "n.".$strColumn;
					break;
			}
		}
		if(count($arQuerySelect) < 1)
			$arQuerySelect = array("ID"=>"n.ID");

		$obQueryWhere = new CSQLWhere;
		$arFields = array(
			"ID" => array(
				"TABLE_ALIAS" => "n",
				"FIELD_NAME" => "n.ID",
				"FIELD_TYPE" => "int",
				"JOIN" => false,
			),
			"GROUP_ID" => array(
				"TABLE_ALIAS" => "n",
				"FIELD_NAME" => "n.GROUP_ID",
				"FIELD_TYPE" => "string",
				"JOIN" => false,
			),
			"ROLE_ID" => array(
				"TABLE_ALIAS" => "n",
				"FIELD_NAME" => "n.ROLE_ID",
				"FIELD_TYPE" => "string",
				"JOIN" => false,
			),
			"ACTIVE" => array(
				"TABLE_ALIAS" => "n",
				"FIELD_NAME" => "n.ACTIVE",
				"FIELD_TYPE" => "string",
				"JOIN" => false,
			),
			"SERVER_ID" => array(
				"TABLE_ALIAS" => "n",
				"FIELD_NAME" => "n.SERVER_ID",
				"FIELD_TYPE" => "int",
				"JOIN" => false,
			),
			"MASTER_ID" => array(
				"TABLE_ALIAS" => "n",
				"FIELD_NAME" => "n.MASTER_ID",
				"FIELD_TYPE" => "int",
				"JOIN" => false,
			),
			"STATUS" => array(
				"TABLE_ALIAS" => "n",
				"FIELD_NAME" => "n.STATUS",
				"FIELD_TYPE" => "string",
				"JOIN" => false,
			),
		);
		$obQueryWhere->SetFields($arFields);

		if(!is_array($arFilter))
			$arFilter = array();
		$strQueryWhere = $obQueryWhere->GetQuery($arFilter);

		$bDistinct = $obQueryWhere->bDistinctReqired;

		$strSql = "
			SELECT ".($bDistinct? "DISTINCT": "")."
			".implode(", ", $arQuerySelect)."
			FROM
				b_cluster_dbnode n
			".$obQueryWhere->GetJoins()."
		";

		if($strQueryWhere)
		{
			$strSql .= "
				WHERE
				".$strQueryWhere."
			";
		}

		if(count($arQueryOrder) > 0)
		{
			$strSql .= "
				ORDER BY
				".implode(", ", $arQueryOrder)."
			";
		}

		return $DB->Query($strSql, false, '', array('fixed_connection'=>true));
	}

	function GetModules($node_id)
	{
		global $DB;
		static $arCache = false;
		if($arCache === false)
		{
			$arCache = array();
			$rs = $DB->Query("SELECT * from b_option WHERE NAME='dbnode_id'", false, '', array('fixed_connection'=>true));
			while($ar = $rs->Fetch())
				if(is_numeric($ar["VALUE"]))
					$arCache[intval($ar["VALUE"])][$ar["MODULE_ID"]] = $ar["MODULE_ID"];
		}
		if(isset($arCache[$node_id]))
			return $arCache[$node_id];
		else
			return array();
	}

	function GetListForModuleInstall()
	{
		return CClusterDBNode::GetList(
			array("NAME"=>"ASC","ID"=>"ASC")
			,array("=ACTIVE"=>"Y", "=ROLE_ID"=>"MODULE", "=STATUS"=>"READY")
			,array("ID", "NAME")
		);
	}
}
?>