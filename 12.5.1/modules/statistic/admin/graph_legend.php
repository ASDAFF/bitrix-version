<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/tools.php");
include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/statistic/colors.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/img.php");

// create an image canvas
$ImageHandle = CreateImageHandle(45, 2);
ImageFill($ImageHandle, 0, 0, ImageColorAllocate($ImageHandle, 255, 255, 255));

if (isset($_REQUEST["color"]))
	$dec = ReColor($_REQUEST["color"]);
else
	$dec = 0;

if (is_array($dec))
	$color = ImageColorAllocate($ImageHandle, $dec[0], $dec[1], $dec[2]);
else
	$color = ImageColorAllocate($ImageHandle, 0, 0, 0);

if (isset($_REQUEST["dash"]) && $_REQUEST["dash"] == "Y")
{
	$style = array(
		$color,
		$color,
		IMG_COLOR_TRANSPARENT,
		IMG_COLOR_TRANSPARENT,
		IMG_COLOR_TRANSPARENT,
	);
	ImageSetStyle($ImageHandle, $style);
	ImageLine($ImageHandle, 1, 0, 45, 0, IMG_COLOR_STYLED);
	ImageLine($ImageHandle, 1, 1, 45, 1, IMG_COLOR_STYLED);
}
else
{
	ImageLine($ImageHandle, 0, 0, 45, 0, $color);
	ImageLine($ImageHandle, 0, 1, 45, 1, $color);
}

ShowImageHeader($ImageHandle);
?>