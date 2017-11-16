<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
$APPLICATION->setAdditionalCSS('/bitrix/components/bitrix/idea/templates/.default/style.css');
$APPLICATION->SetAdditionalCSS("/bitrix/components/bitrix/rating.vote/templates/like/popup.css");
$APPLICATION->SetAdditionalCSS('/bitrix/components/bitrix/rating.vote/templates/'.$arParams["RATING_TEMPLATE"].'/style.css');
?>
<div class="idea-side-button-wrapper" id="idea-side-button">
    <div class="idea-side-button-inner" id="idea-side-button-inner">
        <img src="<?=$this->__folder?>/images/idea<?=LANGUAGE_ID=='ru'?'':'_lang'?>.png">
    </div>
    <div class="idea-side-button-t"></div>
    <div class="idea-side-button-b"></div>
</div>
<script type="text/javascript">
    BX.message({IDEA_POPUP_LEAVE_IDEA: '<?=GetMessage("IDEA_POPUP_LEAVE_IDEA")?>'});
    <?if(strlen($arParams["BUTTON_COLOR"])>0):?>BX('idea-side-button-inner').style.backgroundColor = '<?=$arParams["BUTTON_COLOR"];?>';<?endif;?>
</script>