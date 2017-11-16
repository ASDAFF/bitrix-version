<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<div class="subscribe-form">
<form action="<?=$arResult["FORM_ACTION"]?>">

<?foreach($arResult["RUBRICS"] as $itemID => $itemValue):?>
	<label for="sf_RUB_ID_<?=$itemValue["ID"]?>">
	<input type="checkbox" name="sf_RUB_ID[]" id="sf_RUB_ID_<?=$itemValue["ID"]?>" value="<?=$itemValue["ID"]?>"<?if($itemValue["CHECKED"]) echo " checked"?> /> <?=$itemValue["NAME"]?>
	</label><br />
<?endforeach;?>

	<table border="0" cellspacing="0" cellpadding="2" align="center">
		<tr>
			<td><input type="text" name="sf_EMAIL" size="20" value="<?=$arResult["EMAIL"]?>" title="<?=GetMessage("subscr_form_email_title")?>" /></td>
		</tr>
		<tr>
			<td align="right"><input type="submit" name="OK" value="<?=GetMessage("subscr_form_button")?>" /></td>
		</tr>
	</table>
</form>
</div>
