<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->AddHeadScript("/bitrix/js/main/cphttprequest.js");
?>
<script type="text/javascript">
if (typeof oObject != "object")
	window.oObject = {};
document.CheckThis = function(oObj)
{
	try
	{
		if (TcLoadTI)
		{
			if (typeof window.oObject[oObj.id] != 'object')
				window.oObject[oObj.id] = new JsTc(oObj, '<?=$arParams["ADDITIONAL_VALUES"]?>');
			return;
		}
		else
		{
			setTimeout(CheckThis(oObj), 10);
		}
	}
	catch(e)
	{
		setTimeout(CheckThis(oObj), 10);
	}
}
</script>
<?
if ($arParams["SILENT"] == "Y")
	return;
?><input <?
	?>name="<?=$arResult["NAME"]?>" <?
	?>id="<?=$arResult["ID"]?>" <?
	?>value="<?=$arResult["VALUE"]?>" <?
	?>class="search-tags" <?
	?>type="text" <?
	?>autocomplete="off" <?
	?>onfocus="CheckThis(this);" <?
	?><?=$arResult["TEXT"]?> /><?

if ($arParams["TMPL_IFRAME"] != "N"):
?><IFRAME style="width:0px; height:0px; border: 0px;" src="javascript:''" name="<?=$arResult["ID"]?>_div_frame" id="<?=$arResult["ID"]?>_div_frame"></IFRAME><?
endif;
?>