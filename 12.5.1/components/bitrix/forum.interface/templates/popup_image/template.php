<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
// ************************* Input params***************************************************************
// ************************* BASE **********************************************************************
$arParams["URL"] = trim($arParams["~URL"]);
if (empty($arParams["URL"]))
	return false;
if ($arParams["CONVERT"] == "Y")
	$arParams["URL"] = htmlspecialcharsEx($arParams["URL"]);
// *************************/BASE **********************************************************************
// ************************* ADDITIONAL ****************************************************************
$arParams["WIDTH"] = (intVal($arParams["WIDTH"]) > 0 ? intVal($arParams["WIDTH"]) : 100);
$arParams["HEIGHT"] = (intVal($arParams["HEIGHT"]) > 0 ? intVal($arParams["HEIGHT"]) : $arParams["WIDTH"]);
$arParams["FAMILY"] = trim($arParams["FAMILY"]);
$arParams["FAMILY"] = strtolower(empty($arParams["FAMILY"]) ? "forum" : $arParams["FAMILY"]);
$arParams["FAMILY"] = preg_replace("/[^a-z]/is", "", $arParams["FAMILY"]);
$arParams["RETURN"] = ($arParams["RETURN"] == "Y" ? "Y" : "N");
$arParams["MODE"] = trim($arParams["MODE"]);
// *************************/ADDITIONAL ****************************************************************
// *************************/Input params***************************************************************

$strImage2 = $arParams["URL"];
$strImage1 = $strImage2.(strpos($strImage2, '?') !== false ? '&' : '?')."width=".$arParams['WIDTH']."&height=".$arParams['HEIGHT'];
$imgWidth = (isset($arParams['IMG_WIDTH']) && (intval($arParams['IMG_WIDTH']) > 0)) ? intval($arParams['IMG_WIDTH']) : 0;
$imgHeight = (isset($arParams['IMG_HEIGHT']) && (intval($arParams['IMG_HEIGHT']) > 0)) ? intval($arParams['IMG_HEIGHT']) : 0;

if(
	$arParams["WIDTH"] > 0 && $arParams["HEIGHT"] > 0
	&& ($imgWidth > $arParams["WIDTH"] || $imgHeight > $arParams["HEIGHT"])
)
{
	$coeff = max($imgWidth/$arParams["WIDTH"], $imgHeight/$arParams["HEIGHT"]);
	$iHeight = intval(roundEx($imgHeight/$coeff));
	$iWidth = intval(roundEx($imgWidth/$coeff));
}
else
{
	$coeff = 1;
	$iHeight = $imgHeight;
	$iWidth = $imgWidth;
}
if ($arParams['MODE'] == 'RSS')
{
	ob_start();
	if ($coeff == 1) {
		?><img src="<?=$strImage1?>" width="<?=$imgWidth?>" height="<?=$imgHeight?>" /><? } else {
		?><a href="<?=$strImage2?>" target="_blank"><?
		?><img src="<?=$strImage1?>" width="<?=$iWidth?>" height="<?=$iHeight?>" /><?
		?></a><?
	}
	$arParams["RETURN_DATA"] = ob_get_clean();
}
elseif ($arParams['MODE'] == 'SHOW2IMAGES')
{
	CFile::OutputJSImgShw();
	ob_start();
	if ($coeff == 1) {
		?><img src="<?=$strImage1?>" width="<?=$imgWidth?>" height="<?=$imgHeight?>" /><? } else {
	?><a title="<?=$sPopupTitle?>"<?
		?> onclick="ImgShw('<?=CUtil::addslashes($strImage2)?>','<?=$imgWidth?>','<?=$imgHeight?>', '<?=CUtil::addslashes(htmlspecialcharsEx(htmlspecialcharsEx($strAlt2)))?>'); return false;"<?
		?> href="<?=$strImage2?>" target="_blank"><?
		?><img style="border:none;" src="<?=$strImage1?>" width="<?=$iWidth?>" height="<?=$iHeight?>" /><?
	?></a><?
	}
	$arParams["RETURN_DATA"] = ob_get_clean();
}
else
{
	CUtil::InitJSCore();
	if (!function_exists("__GetPopupID"))
	{
		function __GetPopupID()
		{
			static $ImageId = array();
			$sId = rand();
			while (in_array($sId, $ImageId))
			{
				$sId = rand();
			}
			$ImageId[] = $sId;
			return $sId;
		}
	}

	$id = "popup_".__GetPopupID();
	ob_start();
	?><script>addForumImagesShow('<?=$id?>');</script><?
	?><img src="<?=$arParams["URL"]?>" id="<?=$id?>" border="0" <?
		?>onload="try{window.onForumImageLoad(this, '<?=$arParams["WIDTH"]?>', '<?=$arParams["HEIGHT"]?>', '<?=$arParams["FAMILY"]?>');}catch(e){}" /><?
	$arParams["RETURN_DATA"] = ob_get_clean();
}
if ($arParams["RETURN"] == "Y")
	$this->__component->arParams["RETURN_DATA"] = $arParams["RETURN_DATA"];
else
	echo $arParams["RETURN_DATA"];
?>