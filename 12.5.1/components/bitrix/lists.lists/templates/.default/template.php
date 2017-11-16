<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if($arParams['CAN_EDIT'])
{
	$APPLICATION->IncludeComponent(
		"bitrix:main.interface.toolbar",
		"",
		array(
			"BUTTONS"=>array(
				array(
					"TEXT"=>GetMessage("CT_BLL_TOOLBAR_ADD"),
					"TITLE"=>GetMessage("CT_BLL_TOOLBAR_ADD_TITLE"),
					"LINK"=>$arResult["LIST_EDIT_URL"],
					"ICON"=>"btn-new",
				),
			),
		),
		$component, array("HIDE_ICONS" => "Y")
	);
}
?>
<table cellpadding="0" cellspacing="0" border="0" class="lists-table">
<?foreach($arResult["ROWS"] as $arRow):?>
	<tr class="lists-table-tr">
	<?foreach($arRow as $arList):?>
		<?if(is_array($arList)):?>
			<td align="center" width="<?=$arResult["TD_WIDTH"]?>" class="lists-table-td">
				<div class="lists-list-image"><a href="<?echo $arList["LIST_URL"]?>"><?echo $arList["IMAGE"]?></a></div>
				<a href="<?echo $arList["LIST_URL"]?>"><?echo $arList["NAME"]?></a>
			</td>
		<?else:?>
			<td width="<?=$arResult["TD_WIDTH"]?>">
				&nbsp;
			</td>
		<?endif;?>
	<?endforeach;?>
	</tr>
<?endforeach;?>
</table>