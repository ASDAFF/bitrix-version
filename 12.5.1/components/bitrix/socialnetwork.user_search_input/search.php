<?Define("STOP_STATISTICS", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if (CModule::IncludeModule("socialnetwork"))
{
	if ($GLOBALS["USER"]->IsAuthorized())
	{
		$bIntranet = IsModuleInstalled('intranet');

		
		if (!Function_Exists("__UnEscapeTmp"))
		{
			function __UnEscapeTmp(&$item, $key)
			{
				if (Is_Array($item))
					Array_Walk($item, '__UnEscapeTmp');
				else
				{
					if (StrPos($item, "%u") !== false)
						$item = $GLOBALS["APPLICATION"]->UnJSEscape($item);
				}
			}
		}

		Array_Walk($_REQUEST, '__UnEscapeTmp');
		$arParams = array();
		$params = Explode(",", $_REQUEST["params"]);
		foreach ($params as $param)
		{
			list($key, $val) = Explode(":", $param);
			$arParams[$key] = $val;
		}
		$arParams["pe"] = IntVal($arParams["pe"]);
		if ($arParams["pe"] <= 0 || $arParams["pe"] > 50)
			$arParams["pe"] = 10;
		$arParams["gf"] = IntVal($arParams["gf"]);

		if (strlen(trim($arParams["nt"])) > 0)
		{
			$arParams["NAME_TEMPLATE"] = trim($arParams["nt"]);
			$arParams["NAME_TEMPLATE"] = str_replace(
				array("#NOBR#", "#/NOBR#", "#COMMA#"), 
				array("", "", ","), 
				$arParams["NAME_TEMPLATE"]
			);
		}
		else
		{
			$arParams["NAME_TEMPLATE"] = str_replace("#COMMA#",",", CSite::GetNameFormat(false));
		}

		$arParams['NAME_TEMPLATE'] .= ($bIntranet ? ' <#EMAIL#>' : '');
		$arParams['NAME_TEMPLATE'] .= " [#ID#]";

		if (trim($arParams["sl"]) != "N")
			$bUseLogin = true;
		else
			$bUseLogin = false;

		if (strlen($arParams["ex"]) > 0 && $arParams["ex"] == "E" && strlen($arParams["site"]) > 0 && CModule::IncludeModule('extranet') && CExtranet::IsExtranetUser())
		{
			$arUsersInMyGroupsID = CExtranet::GetMyGroupsUsers($arParams["site"]);
			$arPublicUsersID = CExtranet::GetPublicUsers();
			$arUsersToFilter = array_merge($arUsersInMyGroupsID, $arPublicUsersID);
		}
		elseif (strlen($arParams["ex"]) > 0 && $arParams["ex"] == "EA" && $GLOBALS["APPLICATION"]->GetGroupRight("socialnetwork", false, "Y", "Y", array($arParams["site"], false)) >= "K" && CModule::IncludeModule('extranet'))
		{
			$arExtranetUsersID = CExtranet::GetExtranetGroupUsers();
			$arIntranetUsersID = CExtranet::GetIntranetUsers();
			$arUsersToFilter = array_diff($arExtranetUsersID, $arIntranetUsersID);
		}
		elseif (strlen($arParams["ex"]) > 0 && $arParams["ex"] == "EA" && CModule::IncludeModule('extranet'))
		{
			$arUsersInMyGroupsID = CExtranet::GetMyGroupsUsers($arParams["site"]);
			$arIntranetUsersID = CExtranet::GetIntranetUsers();
			$arUsersToFilter = array_diff($arUsersInMyGroupsID, $arIntranetUsersID);
		}
		elseif (CModule::IncludeModule('extranet') && CExtranet::IsIntranetUser($arParams["site"]))
			$arUsersToFilter = CExtranet::GetIntranetUsers();
		elseif (CModule::IncludeModule('extranet'))
		{
			require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
			die();	
		}

		$arResult = array();

		$dbUsers = CSocNetUser::SearchUsers($_REQUEST["search"], $arParams["gf"], $arParams["pe"]);
		if ($dbUsers && ($arUser = $dbUsers->Fetch()))
		{
			do
			{
				$formatName = CUser::FormatName($arParams['NAME_TEMPLATE'], $arUser, $bUseLogin);
				
				if (strlen($arParams["ex"]) > 0 && ($arParams["ex"] == "E" || $arParams["ex"] == "EA" || $arParams["ex"] == "I") && strlen($arParams["site"]) > 0)
				{
					if (count($arUsersToFilter) > 0 && in_array($arUser["ID"], $arUsersToFilter))
						$arResult[] = array("NAME" => $formatName);
				}
				elseif (CModule::IncludeModule('extranet') && CExtranet::IsIntranetUser($arParams["site"]) || !CModule::IncludeModule('extranet'))
					$arResult[] = array("NAME" => $formatName);
			}
			while ($arUser = $dbUsers->Fetch());
		}
		?><?=CUtil::PhpToJSObject($arResult)?><?
		require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
		die();
	}
}
?>