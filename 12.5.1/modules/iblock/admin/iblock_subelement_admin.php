<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/iblock.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/prolog.php");
IncludeModuleLangFile(__FILE__);

CUtil::JSPostUnescape();
/*
 * this page only for actions and get info
 *
 */
define('B_ADMIN_SUBELEMENTS',1);
define('B_ADMIN_SUBELEMENTS_LIST',true);

$boolSubBizproc = CModule::IncludeModule("bizproc");
$boolSubWorkFlow = CModule::IncludeModule("workflow");

global $APPLICATION;

$strSubTMP_ID = intval($_REQUEST['TMP_ID']);

$strSubIBlockType = trim($type);

$arSubIBlockType = CIBlockType::GetByIDLang($strSubIBlockType, LANGUAGE_ID);
if(false === $arSubIBlockType)
	$APPLICATION->AuthForm(GetMessage("IBLOCK_BAD_BLOCK_TYPE_ID"));

$intSubIBlockID = IntVal($IBLOCK_ID);
//$strSubIBlockPerm = "D";

$bBadBlock = true;
$arSubIBlock = CIBlock::GetArrayByID($intSubIBlockID);
if ($arSubIBlock)
{
	$bBadBlock = !CIBlockRights::UserHasRightTo($intSubIBlockID, $intSubIBlockID, "iblock_admin_display");;
}

if ($bBadBlock)
{
	$APPLICATION->SetTitle($arSubIBlockType["NAME"]);
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

	echo ShowError(GetMessage("IBLOCK_BAD_IBLOCK"));?>
	<a href="/bitrix/admin/iblock_admin.php?lang=<?echo LANGUAGE_ID?>&amp;type=<?echo htmlspecialcharsbx($strSubIBlockType)?>"><?echo GetMessage("IBLOCK_BACK_TO_ADMIN")?></a>
	<?
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
	die();
}

$arSubIBlock["SITE_ID"] = array();
$rsSites = CIBlock::GetSite($intSubIBlockID);
while($arSite = $rsSites->Fetch())
	$arSubIBlock["SITE_ID"][] = $arSite["LID"];

$boolSubWorkFlow = $boolSubBizproc && (CIBlock::GetArrayByID($intSubIBlockID, "WORKFLOW") != "N");
$boolSubBizproc = $boolSubBizproc && (CIBlock::GetArrayByID($intSubIBlockID, "BIZPROC") != "N");

$boolSubCatalog = false;
$bCatalog = CModule::IncludeModule("catalog");
if ($bCatalog)
{
	$arSubCatalog = CCatalog::GetByID($arSubIBlock["ID"]);
	$boolSubCatalog = (is_array($arSubCatalog) ? true : false);
	if (!$boolSubCatalog)
	{
		require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
		require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
		die();
	}
	if (!($USER->CanDoOperation('catalog_read') || $USER->CanDoOperation('catalog_price')))
		$boolSubCatalog = false;
}

$intSubPropValue = intval($_REQUEST['find_el_property_'.$arSubCatalog['SKU_PROPERTY_ID']]);
if (0 >= $intSubPropValue)
{
	if ('' == $strSubTMP_ID)
	{
		require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
		require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
		die();
	}
}
$strSubElementAjaxPath = '/bitrix/admin/iblock_subelement_admin.php?WF=Y&IBLOCK_ID='.$intSubIBlockID.'&type='.urlencode($strSubIBlockType).'&lang='.LANGUAGE_ID.'&find_section_section=0&find_el_property_'.$arSubCatalog['SKU_PROPERTY_ID'].'='.$intSubPropValue.'&TMP_ID='.urlencode($strSubTMP_ID);
require($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/iblock/admin/templates/iblock_subelement_list.php');
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");
?>