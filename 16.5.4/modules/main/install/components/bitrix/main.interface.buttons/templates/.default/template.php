<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)die();
use \Bitrix\Main\Localization\Loc;

CJSCore::Init(array('ajax', 'popup'));

/**
 * Main buttons container required attrs
 * @property {string} id Container id
 * 
 */

/**
 * Main buttons item required attrs
 * @property {string} id
 *
 * Main buttons item data- attrs
 * @property {boolean} disabled @required
 * @property {string} class @required icon class
 * @property {string} onclick
 * @property {string} url
 * @property {string} text Text for submenu item
 * @property {number} counter Counter value
 * @property {boolean} locked Is locked
 */
?>

<div class="main-buttons" id="<?=$arResult["ID"]?>">
	<? foreach ($arResult["ITEMS"] as $key => $arItem) : ?>
		<div class="main-buttons-item <?=$arItem["CLASS"]?><?=$arItem["IS_ACTIVE"] ? ' '.$arParams["CLASS_ITEM_ACTIVE"] : ''?>"
			 id="<?=$arItem["ID"]?>"
			 data-disabled="<?=$arItem["IS_DISABLED"]?>"
			 data-class="<?=$arItem["CLASS_SUBMENU_ITEM"]?>"
			 data-onclick="<?=$arItem["ON_CLICK"]?>"
			 data-url="<?=$arItem["URL"]?>"
			 data-text="<?=$arItem["TEXT"]?>"
			 data-counter="<?=$arItem["COUNTER"]?>"
			 data-locked="<?=$arItem["IS_LOCKED"]?>">
			<? if (!$arItem["HTML"]) :?>
				<a class="main-buttons-item-link<?=$arParams["CLASS_ITEM_LINK"] ? " ".$arParams["CLASS_ITEM_LINK"] : ""?>" 
				   href="<?=$arItem["URL"]?>">
					<span class="main-buttons-item-icon<?=$arParams["CLASS_ITEM_ICON"] ? " ".$arParams["CLASS_ITEM_ICON"] : ""?>"></span><?
					?><span class="main-buttons-item-text<?=$arParams["CLASS_ITEM_TEXT"] ? " ".$arParams["CLASS_ITEM_TEXT"] : ""?>">
						<?=$arItem["TEXT"]?>
						<span class="main-buttons-item-text-marker"></span>
					</span><?
					?><span class="main-buttons-item-counter<?=$arParams["CLASS_ITEM_COUNTER"] ? " ".$arParams["CLASS_ITEM_COUNTER"] : ""?>"><?=$arItem["COUNTER"] > 99 ? '99+' : $arItem["COUNTER"]?></span>
				</a>
				<? if ($arItem["SUB_LINK"]) : ?>
					<a class="main-buttons-item-sublink<?=" ".$arItem["SUB_LINK"]["CLASS"]?>" href="<?=$arItem["SUB_LINK"]["URL"]?>"></a>
				<? endif; ?>
			<? else : ?>
				<?=$arItem["HTML"]?>
			<? endif; ?>
		</div>
	<? endforeach; ?>
	<div class="main-buttons-item <?=$arResult["MORE_BUTTON"]["CLASS"]?>">
		<? if (!$arResult["MORE_BUTTON"]["HTML"]) : ?>
			<a href="#" class="main-buttons-item-link<?=$arParams["CLASS_ITEM_LINK"] ? " ".$arParams["CLASS_ITEM_LINK"] : ""?>">
				<span class="main-buttons-item-icon<?=$arParams["CLASS_ITEM_ICON"] ? " ".$arParams["CLASS_ITEM_ICON"] : ""?>"></span><?
				?><span class="main-buttons-item-text<?=$arParams["CLASS_ITEM_TEXT"] ? " ".$arParams["CLASS_ITEM_TEXT"] : ""?>"><?=$arResult["MORE_BUTTON"]["TEXT"]?></span>
			</a>
		<? else : ?>
			<?=$arResult["MORE_BUTTON"]["HTML"]?>
		<? endif; ?>
	</div>
</div>

<script>
	/**
	 * First arg. is params object
	 * @property {string} containerId @required
	 * @property {object} classes
	 * @property {string} classes.item Class for list item
	 * @property {string} classes.itemSublink Class for sublink (ex. Add link)
	 * @property {string} classes.itemText Class for item text and submenu item text
	 * @property {string} classes.itemCounter Class for list item counter and submenu item counter
	 * @property {string} classes.itemIcon Class for list item icon and submenu item icon
	 * @property {string} classes.itemMore Class for more button
	 * @property {string} classes.itemOver Class for hovered item
	 * @property {string} classes.itemActive Class for active item
	 * @property {string} classes.itemDisabled Class for disabled elements
	 * @property {string} classes.itemLocked Class for locked item. Added for list and submenu item
	 * @property {string} classes.onDrag Class added for container on dragstart event and removed on drag end event
	 * @property {string} classes.dropzone Class for dropzone in submenu 
	 * @property {string} classes.seporator Class for submenu seporator before diabled items
	 * @property {string} classes.submenuItem Class for submenu item
	 * @property {string} classes.submenu Class for submenu container
	 * @property {string} classes.secret Class for hidden alias items (set display: none; for items)
	 * @property {object} messages Messages object. Contains localization strings 
	 * @property {string} messages.MIB_DROPZONE_TEXT Dropzone text
	 * @property {string} messages.MIB_LICENSE_BUY_BUTTON License window Buy button text
	 * @property {string} messages.MIB_LICENSE_TRIAL_BUTTON License window Trial button text
	 * @property {string} messages.MIB_LICENSE_WINDOW_HEADER_TEXT License window header text
	 * @property {string} messages.MIB_LICENSE_WINDOW_TEXT License window content text
	 * @property {string} messaget.MIB_LICENSE_WINDOW_TRIAL_SUCCESS_TEXT Trial success text
	 * @property {string} licenseWindow Settings for license window 
	 * @property {string} licenseWindow.isFullDemoExists Y|N
	 * @property {string} licenseWindow.hostname Hostname for license window ajax calls
	 * @property {string} licenseWindow.ajaxUrl Ajax handler url
	 * @property {string} licenseWindow.licenseAllPath
	 * @property {string} licenseWindow.licenseDemoPath
	 * @property {string} licenseWindow.featureGroupName 
	 * @property {string} licenseWindow.ajaxActionsUrl
	 */
	BX.Main.interfaceButtonsManager.init({
		containerId: '<?=$arResult["ID"]?>',
		classes: {
			itemMore: '<?=$arResult["MORE_BUTTON"]["CLASS"]?>',
			itemActive: '<?=$arParams["CLASS_ITEM_ACTIVE"]?>'
		},
		licenseWindow: {
			isFullDemoExists: 'Y',
			hostname: '',
			ajaxUrl: '',
			licenseAllPath: '',
			licenseDemoPath: '',
			featureGroupname: '',
			ajaxActionsUrl: ''
		},
		messages: {
			MIB_DROPZONE_TEXT: '<?=Loc::getMessage("MIB_DROPZONE_TEXT")?>', 
			MIB_LICENSE_BUY_BUTTON: '<?=Loc::getMessage("MIB_LICENSE_BUY_BUTTON")?>',
			MIB_LICENSE_TRIAL_BUTTON: '<?=Loc::getMessage("MIB_LICENSE_TRIAL_BUTTON")?>',
			MIB_LICENSE_WINDOW_HEADER_TEXT: '<?=Loc::getMessage("MIB_LICENSE_WINDOW_HEADER_TEXT")?>',
			MIB_LICENSE_WINDOW_TEXT: '<?=Loc::getMessage("MIB_LICENSE_WINDOW_TEXT")?>',
			MIB_LICENSE_WINDOW_TRIAL_SUCCESS_TEXT: ''
		}
	});
</script>