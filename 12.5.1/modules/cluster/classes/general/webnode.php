<?
IncludeModuleLangFile(__FILE__);

class CClusterWebnode
{
	function Add($arFields)
	{
		global $DB;

		if(!$this->CheckFields($arFields, 0))
			return false;

		$ID = $DB->Add("b_cluster_webnode", $arFields);

		return $ID;
	}

	function Delete($ID)
	{
		global $DB;
		$ID = intval($ID);

		$res = $DB->Query("DELETE FROM b_cluster_webnode WHERE ID = ".$ID, false, '', array('fixed_connection'=>true));

		return $res;
	}

	function Update($ID, $arFields)
	{
		global $DB;
		$ID = intval($ID);

		if($ID <= 0)
			return false;

		if(!$this->CheckFields($arFields, $ID))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_cluster_webnode", $arFields);
		if(strlen($strUpdate) > 0)
		{
			$strSql = "
				UPDATE b_cluster_webnode SET
				".$strUpdate."
				WHERE ID = ".$ID."
			";
			if(!$DB->Query($strSql, false, '', array('fixed_connection'=>true)))
				return false;
		}

		return true;
	}

	function CheckFields(&$arFields, $ID)
	{
		global $APPLICATION;
		$aMsg = array();

		$bHost = false;
		if(isset($arFields["HOST"]))
		{
			if(preg_match("/^([0-9a-zA-Z-_.]+)\$/", $arFields["HOST"], $match))
				$bHost = true;

			if(!$bHost)
				$aMsg[] = array("id" => "HOST", "text" => GetMessage("CLU_WEBNODE_WRONG_IP"));
		}

		$bStatus = true;
		if($bHost && isset($arFields["PORT"]))
		{
			if(strlen($arFields["STATUS_URL"]))
			{
				$arStatus = $this->GetStatus($arFields["HOST"], $arFields["PORT"], $arFields["STATUS_URL"]);
				$bStatus = is_array($arStatus);
			}
		}

		if(!$bStatus)
		{
			$aMsg[] = array("id" => "STATUS_URL", "text" => GetMessage("CLU_WEBNODE_WRONG_STATUS_URL"));
		}

		if(!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}
		return true;
	}

	function GetList($arOrder=false, $arFilter=false, $arSelect=false)
	{
		global $DB;

		if(!is_array($arSelect))
			$arSelect = array();
		if(count($arSelect) < 1)
			$arSelect = array(
				"ID",
				"NAME",
				"DESCRIPTION",
				"HOST",
				"PORT",
				"STATUS_URL",
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
				case "NAME":
				case "DESCRIPTION":
				case "HOST":
				case "PORT":
				case "STATUS_URL":
					$arQuerySelect[$strColumn] = "w.".$strColumn;
					break;
			}
		}
		if(count($arQuerySelect) < 1)
			$arQuerySelect = array("ID"=>"w.ID");

		$obQueryWhere = new CSQLWhere;
		$arFields = array(
			"ID" => array(
				"TABLE_ALIAS" => "w",
				"FIELD_NAME" => "w.ID",
				"FIELD_TYPE" => "int",
				"JOIN" => false,
			),
			"GROUP_ID" => array(
				"TABLE_ALIAS" => "w",
				"FIELD_NAME" => "w.GROUP_ID",
				"FIELD_TYPE" => "int",
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
				b_cluster_webnode w
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

	function GetStatus($host, $port, $url)
	{
		$FP = fsockopen($host, $port, $errno, $errstr, 2);
		if($FP)
		{
			$strVars = $url;
			$strRequest = "GET ".$url." HTTP/1.0\r\n";
			$strRequest.= "User-Agent: BitrixSMCluster\r\n";
			$strRequest.= "Accept: */*\r\n";
			$strRequest.= "Host: $host\r\n";
			$strRequest.= "Accept-Language: en\r\n";
			$strRequest.= "\r\n";
			fputs($FP, $strRequest);

			$headers = "";
			while(!feof($FP))
			{
				$line = fgets($FP, 4096);
				if($line == "\r\n")
					break;
				$headers .= $line;
			}

			$text = "";
			while(!feof($FP))
				$text .= fread($FP, 4096);

			fclose($FP);

			if(preg_match_all('#<dt>(.*?)\\s*:\\s*(.*?)</dt>#', $text, $match))
			{
				$arResult = array();
				foreach($match[0] as $i => $m0)
				{
					$key = $match[1][$i];
					$value = $match[2][$i];
					if($key == 'Total accesses')
					{
						if(preg_match('/^(.*) - (.*)\\s*:\\s*(.*)$/', $value, $m))
						{
							$value = $m[1];
							$arResult[$m[2]] = $m[3];
						}
					}
					$arResult[$key] = $value;
				}
				return $arResult;
			}
		}

		return false;
	}

	function ParseDateTime($str)
	{
		static $search = false;
		static $replace = false;
		if($search === false)
		{
			$search = array();
			$replace = array();
			for($i = 1; $i <= 12; $i++)
			{
				$time = mktime(0, 0, 0, $i, 1, 2010);
				$search[] = date("M", $time);
				$replace[] = date("m", $time);
			}
		}

		$str = str_replace($search, $replace, $str);
		if(preg_match('/(\\d{2}-\\d{2}-\\d{4} \\d{2}:\\d{2}:\\d{2})/', $str, $match))
			return MakeTimeStamp($match[1], "DD-MM-YYYY HH:MI:SS");
		else
			return false;
	}
}
?>