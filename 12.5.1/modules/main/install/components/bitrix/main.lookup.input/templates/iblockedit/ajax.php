<?
define("STOP_STATISTICS", true);
define("BX_SECURITY_SHOW_MESSAGE", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

global $APPLICATION;

if(!CModule::IncludeModule('iblock'))
{
	echo GetMessage("BT_COMP_MLI_AJAX_ERR_MODULE_ABSENT");
	die();
}

CUtil::JSPostUnescape();

$IBlockID = intval($_REQUEST["IBLOCK_ID"]);
if(!CIBlockRights::UserHasRightTo($IBlockID, $IBlockID, "iblock_admin_display"))
{
	echo GetMessage('BT_COMP_MLI_AJAX_ERR_IBLOCK_ACCESS_DENIED');
	die();
}

$arIBlock = CIBlock::GetArrayByID($IBlockID);

$strBanSym = trim($_REQUEST['BAN_SYM']);
$arBanSym = str_split($strBanSym,1);
$strRepSym = trim($_REQUEST['REP_SYM']);
$arRepSym = array_fill(0,sizeof($arBanSym),$strRepSym);

if($_REQUEST['MODE'] == 'SEARCH')
{
	$APPLICATION->RestartBuffer();

	$arResult = array();
	$search = trim($_REQUEST['search']);

	$matches = array();
	if(preg_match('/^(.*?)\[([\d]+?)\]/i', $search, $matches))
	{
		$matches[2] = intval($matches[2]);
		if($matches[2] > 0)
		{
			$dbRes = CIBlockElement::GetList(
				array(),
				array(
					"IBLOCK_ID" => $arIBlock["ID"],
					"=ID" => $matches[2],
					"CHECK_PERMISSIONS" => "Y",
					"MIN_PERMISSION" => "R",
				),
				false,
				false,
				array("ID", "NAME")
			);
			if($arRes = $dbRes->Fetch())
			{
				$arResult[] = array(
					'ID' => $arRes['ID'],
					'NAME' => str_replace($arBanSym,$arRepSym,$arRes['NAME']),
					'READY' => 'Y',
				);

				Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
				echo CUtil::PhpToJsObject($arResult);
				die();
			}
		}
		elseif(strlen($matches[1]) > 0)
		{
			$search = $matches[1];
		}
	}

	$dbRes = CIBlockElement::GetList(
		array(),
		array(
			"IBLOCK_ID" => $arIBlock["ID"],
			"%NAME" => $search,
			"CHECK_PERMISSIONS" => "Y",
			"MIN_PERMISSION" => "R",
		),
		false,
		array("nTopCount" => 20),
		array("ID", "NAME")
	);

	while($arRes = $dbRes->Fetch())
	{
		$arResult[] = array(
			'ID' => $arRes['ID'],
			'NAME' => str_replace($arBanSym,$arRepSym,$arRes['NAME']),
		);
	}

	Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo CUtil::PhpToJsObject($arResult);
	die();
}