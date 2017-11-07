<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
if (!function_exists("__show_image_script_popup"))
{
	function __show_image_script_popup()
	{
		static $shown = false;
		if ($shown) return;
		ob_start();
?>
<!--LOAD_SCRIPT-->
<script src="/bitrix/js/main/utils.js"></script>
<script src="/bitrix/js/forum/popup_image.js"></script>
<!--END_LOAD_SCRIPT-->
<?
		$script = ob_get_clean();
		$GLOBALS['APPLICATION']->AddHeadString($script);
		$shown = true;
	}
}

__show_image_script_popup();
?>