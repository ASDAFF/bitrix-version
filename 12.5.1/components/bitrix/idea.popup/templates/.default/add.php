<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
$APPLICATION->RestartBuffer();
?>
<div id="idea-editor-container">
    <?$APPLICATION->IncludeComponent(
            "bitrix:idea.edit", 
            "light", 
            Array(
                "BLOG_URL"					=> $arParams["BLOG_URL"],
                "PATH_TO_POST"			=> $arParams["PATH_IDEA_POST"],
                "SET_TITLE"					=> "N",
                "SET_NAV_CHAIN"				=> "N",
                "POST_PROPERTY"				=> CIdeaManagment::getInstance()->GetUserFieldsArray(),
                "SMILES_COLS" 				=> $arParams["SMILES_COLS"],
                "SMILES_COUNT" 				=> 1,
                //"SHOW_LOGIN" 				=> $arParams["SHOW_LOGIN"],
                "EDITOR_RESIZABLE" => "N",
                "EDITOR_DEFAULT_HEIGHT" => "200",
                //"EDITOR_CODE_DEFAULT" => $arParams["EDITOR_CODE_DEFAULT"],
                //"USE_GOOGLE_CODE" => $arParams["USE_GOOGLE_CODE"],
                "SHOW_RATING" => $arParams["SHOW_RATING"],
            ),
            $component
    );
    ?>
</div>

<script>
    //BX.loadCSS(['<?=join("','", $APPLICATION->GetCSSArray())?>']);
    BX('POST_TITLE').focus();
</script>