<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<table class="table-basket-line">
	<?
	if (IntVal($arResult["NUM_PRODUCTS"])>0)
	{
		?>
		<tr>
			<td><a href="<?=$arParams["PATH_TO_BASKET"]?>" class="basket-line-basket"></a></td>
			<td><a href="<?=$arParams["PATH_TO_BASKET"]?>"><?=$arResult["PRODUCTS"];?></a></td>
		</tr>
		<?
	}
	else
	{
		?><tr>
			<td><div class="basket-line-basket"></div></td>
			<td><?=$arResult["ERROR_MESSAGE"]?></td>
		</tr><?
	}
	if($arParams["SHOW_PERSONAL_LINK"] == "Y")
	{
		?>
		<tr>
			<td><a href="<?=$arParams["PATH_TO_PERSONAL"]?>" class="basket-line-personal"></a></td>
			<td><a href="<?=$arParams["PATH_TO_PERSONAL"]?>"><?= GetMessage("TSB1_PERSONAL") ?></a></td>
		</tr>
		<?
	}
	?>
</table>