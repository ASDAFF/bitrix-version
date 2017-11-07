<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if ('Y' == $arParams['SEPARATE_BUTTON'])
{
	?><div class="mea-cont"><?php
}
?><input class="mes-button" type="button" value="<?php echo ('' != $arParams['BUTTON_CAPTION'] ? $arParams['BUTTON_CAPTION'] : '...'); ?>" title="<?php echo ('' != $arParams['BUTTON_TITLE'] ? $arParams['BUTTON_TITLE'] : ''); ?>" onClick="<?php
	if ('Y' == $arParams['HIDDEN_WINDOW'])
		echo htmlspecialcharsbx($arParams['~CONTENT_URL']);
	else
		echo htmlspecialcharsbx("jsUtils.OpenWindow('".CUtil::JSEscape($arParams['~CONTENT_URL'])."', 800, 500);");
?>"><?php
if ('Y' == $arParams['SEPARATE_BUTTON'])
{
	?></div><?php
}