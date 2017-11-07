<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<script type="text/javascript">
	BX.bind(window, "load", function() { BX.PULL.start(<?=(empty($arResult)? '': CUtil::PhpToJsObject($arResult))?>); });
</script>