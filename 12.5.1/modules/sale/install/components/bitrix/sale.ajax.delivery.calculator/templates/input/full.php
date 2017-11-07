<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<?if ($arResult["B_ADMIN"] == "Y"):?>
<script language="JavaScript" src="/bitrix/js/main/cphttprequest.js"></script>
<script language="JavaScript" src="<?=$arResult["PATH"]?>proceed.js"></script>
<?endif?>
<div id="delivery_info_<?=$arParams["INPUT_NAME"]?>">
<input type="text" name="<?=$arParams["INPUT_NAME"]?>" value="<?=roundEx($arParams["START_VALUE"], SALE_VALUE_PRECISION)?>" <?=$arParams["INPUT_ADDITIONAL"]?> />
</div><div id="wait_container_<?=$arParams["INPUT_NAME"]?>" style="display: none;"></div>