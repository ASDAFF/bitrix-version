<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arParams["LINE_ELEMENT_COUNT"] = intval($arParams["LINE_ELEMENT_COUNT"]);
if($arParams["LINE_ELEMENT_COUNT"] <= 0)
	$arParams["LINE_ELEMENT_COUNT"] = 3;

$imageSize = 150;
$arResult["TD_WIDTH"] = round(100/$arParams["LINE_ELEMENT_COUNT"])."%";

$arResult["ROWS"] = array();
while(count($arResult["ITEMS"]) > 0)
{
	$arRow = array_splice($arResult["ITEMS"], 0, $arParams["LINE_ELEMENT_COUNT"]);
	foreach($arRow as $i => $arList)
	{
		$arRow[$i]["IMAGE"] = false;
		if($arList["PICTURE"] > 0)
		{
			$imageFile = CFile::GetFileArray($arList["PICTURE"]);
			if($imageFile !== false)
			{
				$arFileTmp = CFile::ResizeImageGet(
					$imageFile,
					array("width" => $imageSize, "height" => $imageSize),
					BX_RESIZE_IMAGE_PROPORTIONAL,
					false
				);
				$arRow[$i]["IMAGE"] = CFile::ShowImage($arFileTmp["src"], $imageSize, $imageSize, "border=0", "", false);
			}
		}

		if(!$arRow[$i]["IMAGE"])
			$arRow[$i]["IMAGE"] = "<img src=\"/bitrix/images/lists/nopic_list_150.png\" width=\"".$imageSize."\" height=\"".$imageSize."\" border=\"0\" alt=\"\" />";
	}

	while(count($arRow) < $arParams["LINE_ELEMENT_COUNT"])
		$arRow[] = false;

	$arResult["ROWS"][] = $arRow;
}
?>
