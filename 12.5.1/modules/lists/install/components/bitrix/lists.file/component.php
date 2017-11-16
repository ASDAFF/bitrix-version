<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(!CModule::IncludeModule('lists'))
{
	ShowError(GetMessage("CC_BLF_MODULE_NOT_INSTALLED"));
	return;
}
$IBLOCK_ID = is_array($arParams["~IBLOCK_ID"])? 0: intval($arParams["~IBLOCK_ID"]);
$ELEMENT_ID = is_array($arParams["~ELEMENT_ID"])? 0: intval($arParams["~ELEMENT_ID"]);
$SECTION_ID = is_array($arParams["~SECTION_ID"])? 0: intval($arParams["~SECTION_ID"]);

$lists_perm = CListPermissions::CheckAccess(
	$USER,
	$arParams["~IBLOCK_TYPE_ID"],
	$IBLOCK_ID,
	$arParams["~SOCNET_GROUP_ID"]
);
if($lists_perm < 0)
{
	switch($lists_perm)
	{
	case CListPermissions::WRONG_IBLOCK_TYPE:
		ShowError(GetMessage("CC_BLF_WRONG_IBLOCK_TYPE"));
		return;
	case CListPermissions::WRONG_IBLOCK:
		ShowError(GetMessage("CC_BLF_WRONG_IBLOCK"));
		return;
	case CListPermissions::LISTS_FOR_SONET_GROUP_DISABLED:
		ShowError(GetMessage("CC_BLF_LISTS_FOR_SONET_GROUP_DISABLED"));
		return;
	default:
		ShowError(GetMessage("CC_BLF_UNKNOWN_ERROR"));
		return;
	}
}
elseif(
	$ELEMENT_ID > 0
	&& $lists_perm <= CListPermissions::CAN_READ
	&& !CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $ELEMENT_ID, "element_read")
)
{
	ShowError(GetMessage("CC_BLF_ACCESS_DENIED"));
	return;
}
elseif(
	$SECTION_ID > 0
	&& $lists_perm <= CListPermissions::CAN_READ
	&& !CIBlockSectionRights::UserHasRightTo($IBLOCK_ID, $SECTION_ID, "section_read")
)
{
	ShowError(GetMessage("CC_BLF_ACCESS_DENIED"));
	return;
}

$arIBlock = CIBlock::GetArrayByID(intval($arParams["~IBLOCK_ID"]));

$arResult["FILES"] = array();
$arResult["ELEMENT"] = false;
$arResult["SECTION"] = false;

if($ELEMENT_ID > 0)
{
	$rsElement = CIBlockElement::GetList(
		array(),
		array(
			"IBLOCK_ID" => $arIBlock["ID"],
			"=ID" => $ELEMENT_ID,
			"CHECK_PERMISSIONS" => "N",
		),
		false,
		false,
		array("ID", $arParams["FIELD_ID"])
	);
	while($ar = $rsElement->GetNext())
	{
		if(isset($ar[$arParams["FIELD_ID"]]))
		{
			$arResult["FILES"][] = $ar[$arParams["FIELD_ID"]];
		}
		elseif(isset($ar[$arParams["FIELD_ID"]."_VALUE"]))
		{
			if(is_array($ar[$arParams["FIELD_ID"]."_VALUE"]))
				$arResult["FILES"] = array_merge($arResult["FILES"], $ar[$arParams["FIELD_ID"]."_VALUE"]);
			else
				$arResult["FILES"][] = $ar[$arParams["FIELD_ID"]."_VALUE"];
		}
		$arResult["ELEMENT"] = $ar;
	}
}
elseif($SECTION_ID > 0)
{
	$rsSection = CIBlockSection::GetList(
		array(),
		array(
			"IBLOCK_ID" => $arIBlock["ID"],
			"=ID" => $SECTION_ID,
			"GLOBAL_ACTIVE"=>"Y",
			"CHECK_PERMISSIONS" => "N",
		),
		false,
		array("ID", $arParams["FIELD_ID"])
	);
	while($ar = $rsSection->GetNext())
	{
		if(isset($ar[$arParams["FIELD_ID"]]))
		{
			$arResult["FILES"][] = $ar[$arParams["FIELD_ID"]];
		}
		$arResult["SECTION"] = $ar;
	}
}

if(!in_array($arParams["FILE_ID"], $arResult["FILES"]))
{
	ShowError(GetMessage("CC_BLF_WRONG_FILE"));
}
else
{
	$arFile = CFile::GetFileArray($arParams["FILE_ID"]);
	if(is_array($arFile))
		CFile::ViewByUser($arParams["FILE_ID"], array(
			"content_type" => $arFile["CONTENT_TYPE"],
			"force_download" => isset($_REQUEST["download"]) && $_REQUEST["download"] === "y",
		));
}
?>
