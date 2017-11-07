<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
function __MPF_ImageResizeHandler(&$arCustomFile, $arParams = null)
{
	static $arResizeParams = array();

	if ($arParams !== null)
	{
		if (is_array($arParams) && array_key_exists("width", $arParams) && array_key_exists("height", $arParams))
		{
			$arResizeParams = $arParams;
		}
		elseif(intVal($arParams) > 0)
		{
			$arResizeParams = array("width" => intVal($arParams), "height" => intVal($arParams));
		}
	}

	if ((!is_array($arCustomFile)) || !isset($arCustomFile['fileID']))
		return false;

	$fileID = $arCustomFile['fileID'];

	$arFile = CFile::MakeFileArray($fileID);
	if (CFile::CheckImageFile($arFile) === null)
	{
		$aImgThumb = CFile::ResizeImageGet(
			$fileID,
			array("width" => 90, "height" => 90),
			BX_RESIZE_IMAGE_EXACT,
			true
		);
		$arCustomFile['img_thumb_src'] = $aImgThumb['src'];

		if (!empty($arResizeParams))
		{
			$aImgSource = CFile::ResizeImageGet(
				$fileID,
				array("width" => $arResizeParams["width"], "height" => $arResizeParams["height"]),
				BX_RESIZE_IMAGE_PROPORTIONAL,
				true
			);
			$arCustomFile['img_source_src'] = $aImgSource['src'];
			$arCustomFile['img_source_width'] = $aImgSource['width'];
			$arCustomFile['img_source_height'] = $aImgSource['height'];
		}
	}
}
?>