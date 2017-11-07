<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);

if (empty($arResult["BRAND_BLOCKS"]))
	return;
$strRand = $this->randString();
$strObName = 'obIblockBrand_'.$strRand;
$blockID = 'bx_IblockBrand_'.$strRand;
$mouseEvents = 'onmouseover="'.$strObName.'.itemOver(this);" onmouseout="'.$strObName.'.itemOut(this)"';


if ($arParams['SINGLE_COMPONENT'] == "Y")
	echo '<div class="bx_item_detail_inc_two_'.count($arResult['BRAND_BLOCKS']).' general" id="'.$blockID.'">';
else
	echo '<div class="bx_item_detail_inc_two" id="'.$blockID.'">';

$handlerIDS = array();

foreach ($arResult["BRAND_BLOCKS"] as $blockId => $arBB)
{
	$brandID = 'brand_'.$arResult['ID'].'_'.$blockId.'_'.$strRand;
	$popupID = $brandID.'_popup';

	$usePopup = $arBB['FULL_DESCRIPTION'] !== false;
	$useLink = $arBB['LINK'] !== false;
	if ($useLink)
		$arBB['LINK'] = htmlspecialcharsbx($arBB['LINK']);

	switch ($arBB['TYPE'])
	{
		case 'ONLY_PIC':
			?><div id="<?=$brandID;?>" class="bx_item_detail_inc_one_container"<? echo ($usePopup ? ' data-popup="'.$popupID.'"' : ''); ?>><div class="wrapper">
			<?
			if ($useLink)
				echo '<a href="'.$arBB['LINK'].'">';
			?>
			<span class="bx_item_vidget"  style="background-image: url(<?=$arBB['PICT']['SRC'];?>)">
			<?
			if ($useLink)
				echo '</a>';

			if ($usePopup)
				echo '<span class="bx_popup" id="'.$popupID.'"><span class="arrow"></span><span class="text">'.$arBB['FULL_DESCRIPTION'].'</span></span>';
			?>
			</span></div></div><?
			break;
		default:
			?>
			<div id="<?=$brandID;?>" class="bx_item_detail_inc_one_container"<? echo ($usePopup ? ' data-popup="'.$popupID.'"' : ''); ?>>
				<div class="wrapper">
					<? $tagAttrs = 'id="'.$brandID.'_vidget"'.(
						empty($arBB['PICT'])
						? ' class="bx_item_vidget"'
						: ' class="bx_item_vidget icon" style="background-image:url('.$arBB['PICT']['SRC'].');"'
					);
					if ($usePopup)
						$tagAttrs .= ' data-popup="'.$popupID.'"';

					if ($useLink)
						echo '<a '.$tagAttrs.'href="'.$arBB['LINK'].'">';
					else
						echo '<span '.$tagAttrs.' >';

					if ($usePopup) :
					?>
						<span class="bx_popup" id="<?=$popupID;?>">
							<span class="arrow"></span>
							<span class="text"><?=$arBB['FULL_DESCRIPTION'];?></span>
						</span>
					<?
					endif;

					if ($arBB['DESCRIPTION'] !== false)
						echo htmlspecialcharsex($arBB['DESCRIPTION']);

					if ($useLink)
						echo '</a>';
					else
						echo '</span>';
					?>
				</div>
			</div>
			<?
	}
	if ($usePopup)
		$handlerIDS[] = $brandID;
}
?>
	</div>
	<div style="clear: both;"></div>
<?
if (!empty($handlerIDS))
{
	$jsParams = array(
		'blockID' => $blockID
	);
?>
	<script type="text/javascript">
		var <? echo $strObName; ?> = new JCIblockBrands(<? echo CUtil::PhpToJSObject($jsParams); ?>);
	</script>
<?}