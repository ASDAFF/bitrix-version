<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<?
?>
<tr>
	<td align="right" width="40%"><?= GetMessage("BPSFA_PD_STATE") ?>:</td>
	<td width="60%">
		<?=CBPDocument::ShowParameterField("string", 'target_state_title', $arCurrentValues['target_state_title'], Array('size'=>'50'))?>
	</td>
</tr>
