<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if ($this->__page == "result")
{
	?><div class="bx-vote-block bx-vote-block-result"><?
}
else
{
	?><div class="bx-vote-block"><?
}
if(isset($_REQUEST["AUTH_FORM"]) && $_REQUEST["AUTH_FORM"] <> '')
{
	$_REQUEST["AJAX_POST"] = "N";
}
if ($_REQUEST["VOTE_ID"] == $arParams["VOTE_ID"] && $_REQUEST["AJAX_POST"] == "Y" && check_bitrix_sessid())
{
	ob_start();
}
?>