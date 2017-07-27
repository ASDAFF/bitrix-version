<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
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
if ($arResult['IS_ADMIN_SECTION']):
$maxImageSize = array(
	"W" => \COption::GetOptionString("iblock", "list_image_size"),
	"H" => \COption::GetOptionString("iblock", "list_image_size")
);

function getTreeOffsetWidth($level = 0)
{
	// Some magic numbers
	return 30 + $level * 21;
}

function renderTree($sections, $level = 0, $tableId)
{
	$content = '';
	$level = (int)$level;

	foreach ($sections AS $section)
	{
		$bSubmenu = $section["dynamic"];
		$bSectionActive = $section["open"];

		$icon = isset($section["icon"]) && $section["icon"] <> ""
			? '<span class="adm-submenu-item-link-icon ' . $section["icon"] . '"></span>' : '';
		$id = $tableId . '_section_' . $section['id'];
		$onclick = '';
		if ($bSubmenu)
		{
			$onclick = $tableId . "_helper.toggleDynSection(" . getTreeOffsetWidth($level) . ", this.parentNode.parentNode, '"
				. (int)$section["id"] . "', '" . ($level + 1) . "')";
		}
		$content .= '<div
				class="adm-sub-submenu-block' . ($level > 0 ? ' adm-submenu-level-' . ($level + 1) : '') . ($bSectionActive ? ' adm-sub-submenu-open' : '')
			. ($section["active"] ? ' adm-submenu-item-active' : '') . '">
				<div class="adm-submenu-item-name' . (!$bSubmenu ? ' adm-submenu-no-children' : '') . '"
					id="' . $id . '" data-level="' . $level . '" data-offset="' . getTreeOffsetWidth($level) . '"
					tabindex="2"><span class="adm-submenu-item-arrow"' . ($level > 0 ? ' style="width:' . getTreeOffsetWidth($level) . 'px;"' : '')
			. ($onclick ? ' onclick="' . $onclick . '"' : '') . '>
					<span class="adm-submenu-item-arrow-icon"></span></span><a
						class="adm-submenu-item-name-link"' . ($level > 0 ? ' style="padding-left:' . (getTreeOffsetWidth($level) + 8) . 'px;"' : '') . '
						href="#" . " onclick="return '.$tableId . '_helper.onSectionClick(\'' . $section["id"] . '\')">' . $icon . '
						<span class="adm-submenu-item-name-link-text">' . $section["text"] . '</span></a></div>';
		$content .= '<div class="adm-sub-submenu-block-children">' . ($bSubmenu ? renderTree($section["items"], $level + 1, $tableId) : '') . '</div>';
		$content .= '</div>';
	}
	return $content;
}

/**
 * @param $name
 * @param $property_fields
 * @param $values
 * @return bool|string
 */
function _ShowGroupPropertyFieldList($name, $property_fields, $values)
{
	if (!is_array($values)) $values = array();

	$options = "";
	$result = "";
	$bWas = false;
	$sections = ProductSearchComponent::getPropertyFieldSections($property_fields["LINK_IBLOCK_ID"]);
	if (!empty($sections) && is_array($sections))
	{
		foreach ($sections as &$section)
		{
			$options .= '<option value="' . $section["ID"] . '"';
			if (in_array($section["ID"], $values))
			{
				$bWas = true;
				$options .= ' selected';
			}
			$options .= '>' . str_repeat(" . ", $section["DEPTH_LEVEL"]) . $section["NAME"] . '</option>';
		}
		unset($section);
	}
	$result .= '<select name="' . $name . '[]" size="' . ($property_fields["MULTIPLE"] == "Y" ? "5" : "1") . '" ' . ($property_fields["MULTIPLE"] == "Y" ? "multiple" : "") . '>';
	$result .= '<option value=""' . (!$bWas ? ' selected' : '') . '>' . GetMessage("SPS_A_PROP_NOT_SET") . '</option>';
	$result .= $options;
	$result .= '</select>';
	return $result;
}

function addPropsCell(&$row, &$arSelectedProps, &$arItems)
{
	$arProperties = $arItems['PROPERTIES'];
	foreach ($arSelectedProps as $aProp)
	{

		if (empty($arProperties[$aProp['ID']])) continue;
		$v = '';
		foreach ($arProperties[$aProp['ID']] as $property_value_id => $property_value)
		{
			$res = '';
			if ($aProp['PROPERTY_TYPE'] == 'F')
				$res = getImageField($property_value_id, $property_value);
			elseif ($aProp['PROPERTY_TYPE'] == 'G')
			{
				$res = ProductSearchComponent::getSectionName($property_value);
			}
			elseif ($aProp['PROPERTY_TYPE'] == 'E')
			{
				$res = ProductSearchComponent::getElementName($property_value);
			}
			else
			{
				$res = htmlspecialcharsex($property_value);
			}
			if ($res != "")
				$v .= ($v != '' ? ' / ' : '') . $res;
		}
		if ($v != "")
			$row->AddViewField("PROPERTY_" . $aProp['ID'], $v);
	}
}

function getImageField($property_value_id,$property_value)
{
	$res = CFileInput::Show('NO_FIELDS[' . $property_value_id . ']', $property_value, array(
			"IMAGE" => "Y",
			"PATH" => false,
			"FILE_SIZE" => false,
			"DIMENSIONS" => false,
			"IMAGE_POPUP" => false,
			"MAX_SIZE" => array("W" => 50, "H" => 50),
			"MIN_SIZE" => array("W" => 1, "H" => 1),
		), array(
			'upload' => false,
			'medialib' => false,
			'file_dialog' => false,
			'cloud' => false,
			'del' => false,
			'description' => false,
		)
	);
	$res = preg_replace('!<script[^>]*>.*</script>!isU','', $res);
	return $res;
}

if (!empty($arResult['OPEN_SECTION_MODE']))
{
	echo renderTree($arResult['SECTIONS'],$arResult['LEVEL'],$arResult['TABLE_ID']);
}
else
{
	$arProps = $arResult['PROPS'];
	$arSKUProps = $arResult['SKU_PROPS'];
	$arFilter = $arResult['FILTER'];

	$arHeaders = $arResult['HEADERS'];
	$arPrices = $arResult['PRICES'];

	$tableId = CUtil::JSEscape($arResult['TABLE_ID']);

	// START TEMPLATE

	$lAdmin = new CAdminList($arResult['TABLE_ID'], new CAdminSorting($arResult['TABLE_ID'], "ID", "ASC"));
	$lAdmin->InitFilter($arResult['FILTER_FIELDS']);

	// fix
	$_REQUEST['admin_history'] = 1;
	$lAdmin->NavText($arResult['DB_RESULT_LIST']->GetNavPrint(GetMessage("SPS_NAV_LABEL")));

	$lAdmin->AddHeaders($arHeaders);

	$arSelectedFields = $lAdmin->GetVisibleHeaderColumns();
	$arSelectedProps = array();

	$allProps = array_merge($arProps, $arSKUProps);
	foreach ($allProps as $prop)
	{
		if ($key = array_search("PROPERTY_" . $prop['ID'], $arSelectedFields))
		{
			$arSelectedProps[] = $prop;
			unset($arSelectedFields[$key]);
		}
	}
	$allProps = null;
	$arSelectedFields = null;
	$arSku = array();

	//Add 'Level Up' row to grid
	if ($arResult['PARENT_SECTION_ID'] >= 0)
	{
		$row =& $lAdmin->AddRow(0, array());
		$row->AddViewField('NAME', '<a class="adm-list-table-link"><span class="bx-s-iconset folder"></span>..</a>');
		$row->AddActions(array(array(
			"TEXT" => GetMessage("SPS_GOTO_PARENT_SECTION"),
			"DEFAULT" => "Y",
			"ACTION" => $tableId . '_helper.onSectionClick(' . (int)$arResult['PARENT_SECTION_ID'] . ');'
		)));
	}
	foreach ($arResult['PRODUCTS'] as $productId => $arItems)
	{
		$arCatalogProduct = $arItems['PRODUCT'];

		$row =& $lAdmin->AddRow($arItems["ID"], $arItems);

		$row->AddField("ACTIVE", $arItems["ACTIVE"] == 'Y' ? GetMessage('SPS_PRODUCT_ACTIVE') : GetMessage('SPS_PRODUCT_NO_ACTIVE'));
		$row->AddViewField("PREVIEW_PICTURE", getImageField('NO_FIELDS[' . $arItems['ID'] . '][PREVIEW_PICTURE]', $arItems['PREVIEW_PICTURE']));
		$row->AddViewField("DETAIL_PICTURE", getImageField('NO_FIELDS[' . $arItems['ID'] . '][DETAIL_PICTURE]', $arItems['DETAIL_PICTURE']));
		$arActions = array();
		$icon = $arCatalogProduct['TYPE'] == CCatalogProduct::TYPE_SET ? 'f2' : 'f1';

		if (!empty($arItems['SKU_ITEMS']) && !empty($arItems['SKU_ITEMS']["SKU_ELEMENTS"]))
		{
			$icon = 'f3';
			$arSkuResult = $arItems['SKU_ITEMS'];

			$arParams = array(
				'id' => $arItems['ID'],
				'type' => $arCatalogProduct['TYPE'],
				'name' => $arItems['NAME']
			);
			$jsClick = $tableId.'_helper.SelEl('.CUtil::PhpToJSObject($arParams, false, true, true).', this);';
			if ($arResult['CALLER'] == 'discount')
			{
				$row->AddField("ACTION", '<a href="javascript:void()" onclick="'.$jsClick.'">'.GetMessage('SPS_SELECT').'</a>');
			}
			$row->AddViewField("EXPAND", '<a class="expand-sku">' . GetMessage('SPS_EXPAND') . '</a><a class="collapse-sku">' . GetMessage('SPS_COLLAPSE') . '</a>');

			$arActions[] = array(
				"ICON" => "view",
				"TEXT" => GetMessage("SPS_SKU_SHOW"),
				"DEFAULT" => "Y",
				"ACTION" => $tableId . '_helper.fShowSku(' . CUtil::PhpToJSObject($arSkuResult["SKU_ELEMENTS"]) . ', this);'
			);
			if ($arResult['CALLER'] == 'discount')
			{
				$arActions[] = array(
					"TEXT" => GetMessage("SPS_SELECT"),
					"DEFAULT" => "N",
					"ACTION" => $jsClick
				);
			}
			unset($jsClick, $arParams);

			foreach ($arSkuResult["SKU_ELEMENTS"] as $val)
			{
				$arSku[] = $val["ID"];
				$rowSku =& $lAdmin->AddRow($val["ID"], $val);
				$skuProperty = "";
				foreach ($val['PROPERTIES_SHOW'] as $name => $value)
				{
					if ($skuProperty != "")
						$skuProperty .= " <br> ";
					$skuProperty .= '<span style="color: grey;">' . $name . '</span>: ' . $value;
				}

				$arSkuActions = array();
				$rowSku->AddField("NAME", '<div class="sku-item-name">' . $skuProperty . '</div>' . '<input type="hidden" name="prd" id="' . $tableId . '_sku-' . $val["ID"] . '">');

				$rowSku->AddViewField("DETAIL_PICTURE", getImageField('NO_FIELDS[' . $val['ID'] . '][DETAIL_PICTURE]', $val['DETAIL_PICTURE']));
				$rowSku->AddViewField("PREVIEW_PICTURE", getImageField('NO_FIELDS[' . $val['ID'] . '][PREVIEW_PICTURE]', $val['PREVIEW_PICTURE']));

				$rowSku->AddField("ID", $arItems["ID"] . "-" . $val["ID"]);
				foreach ($arPrices as $price)
				{
					$rowSku->AddViewField("PRICE" . $price['ID'], CCurrencyLang::CurrencyFormat($arResult['SKU_PRICES'][$price['ID']][$val["ID"]]['PRICE'], $arResult['SKU_PRICES'][$price['ID']][$val["ID"]]['CURRENCY'], true));
				}

				$balance = FloatVal($val["BALANCE"]);

				$arParams = array(
					'id' => $val["ID"],
					'type' => $val["TYPE"],
					'name' => $val['NAME']
				);

				$active = GetMEssage('SPS_PRODUCT_ACTIVE');
				$arSkuActions[] = array(
					"TEXT" => GetMessage("SPS_SELECT"),
					"DEFAULT" => "Y",
					"ACTION" => $tableId . '_helper.SelEl(' . CUtil::PhpToJSObject($arParams) . ', this);'
				);


				$active = ($val["ACTIVE"] == 'Y' ? GetMEssage('SPS_PRODUCT_ACTIVE') : GetMEssage('SPS_PRODUCT_NO_ACTIVE'));

				$rowSku->AddActions($arSkuActions);
				$rowSku->AddField("BALANCE", $balance);
				$rowSku->AddField("ACTIVE", $active);

				$rowSku->AddField("QUANTITY", '<input type="text" id="'.$tableId.'_qty_'.$val["ID"].'" value="1" size="3" />');
				$rowSku->AddField("ACTION", '<a class="select-sku">' . GetMessage('SPS_SELECT') . '</a>');

				addPropsCell($rowSku, $arSelectedProps, $val);
			}
		}
		else
		{
			if ($arItems['TYPE'] == 'S')
			{
				$icon = 'folder';
			}
			elseif (!empty($arCatalogProduct['IS_GROUP']))
				$icon = 'f4';

			$balance = isset($arCatalogProduct["STORE_AMOUNT"]) ? FloatVal($arCatalogProduct["QUANTITY"]) . " / " . FloatVal($arCatalogProduct["STORE_AMOUNT"]) : FloatVal($arCatalogProduct["QUANTITY"]);
			$row->AddField("BALANCE", $arItems['TYPE'] != 'S' ? $balance : '');

			if ($arItems['TYPE'] != 'S')
			{
				$arParams = array(
					'id' => $arItems["ID"],
					'type' => $arCatalogProduct["TYPE"],
					'name' => $arItems['NAME']
				);

				$arActions[] = array(
					"TEXT" => GetMessage("SPS_SELECT"),
					"DEFAULT" => "Y",
					"ACTION" => $tableId . '_helper.SelEl(' . CUtil::PhpToJSObject($arParams) . ', this);'
				);

				$row->AddField("QUANTITY", '<input type="text" id="'.$tableId.'_qty_'.$arItems["ID"].'" value="1" size="3" />');
				$row->AddField("ACTION", '<a class="select-sku">' . GetMessage('SPS_SELECT') . '</a>');
			}
			else
			{
				$arActions[] = array(
					"TEXT" => GetMessage("SPS_SELECT"),
					"DEFAULT" => "Y",
					"ACTION" => $tableId . '_helper.onSectionClick(' . $arItems["ID"] . ',\'' . CUtil::JSEscape($arItems['NAME']) . '\');'
				);
			}
			foreach ($arPrices as $price)
			{
				$row->AddViewField("PRICE" . $price['ID'], CCurrencyLang::CurrencyFormat($arItems['PRICES'][$price['ID']]['PRICE'], $arItems['PRICES'][$price['ID']]['CURRENCY'], true));
			}
		}
		addPropsCell($row, $arSelectedProps, $arItems);

		$row->AddViewField('NAME', '<a class="adm-list-table-link"><span class="bx-s-iconset ' . $icon . '"></span>' . $arItems['NAME'] . '</a>');
		$row->AddActions($arActions);
	}

	$lAdmin->BeginEpilogContent();
	?>
	<script type="text/javascript">
		<?foreach($arSku as $k => $v)
			{
				?>
		if (BX('<?=$tableId?>_sku-<?=$v?>'))
		{
			var skuRow = BX('<?=$tableId?>_sku-<?=$v?>').parentNode.parentNode;
			BX.addClass(skuRow, 'is-sku-row');
			BX.hide(skuRow);
		}
		<?
	}
	?>
		// double click patch
		var rows = BX.findChildren(BX('<?=$tableId?>'), {className: 'adm-list-table-row'}, true);
		if (rows) {
			var i;
			for (i = 0; i < rows.length; ++i) {

				var isExpandable = BX.findChildren(rows[i], {className: 'expand-sku'}, true);
				if (isExpandable.length==0)
				{
					rows[i].onclick = function () {
						BX.toggleClass(this, 'row-sku-selected')
					};
				}
				else
				{
					rows[i].onclick = function () {
						BX.toggleClass(this, 'row-sku-selected');
						this.ondblclick();
					};
				}

				var hasActionButton = BX.findChildren(rows[i], {className: 'select-sku'}, true);
				if (hasActionButton.length > 0)
				{
					hasActionButton[0].onclick = rows[i].ondblclick;
				}
			}
		}
		if (typeof <?=$tableId?>_helper != 'undefined')
		{
			<?=$tableId?>_helper.setBreadcrumbs(<?=CUtil::PhpToJSObject($arResult['BREADCRUMBS'])?>);
			<?if (!empty($_REQUEST['set_filter']) && $_REQUEST['set_filter'] == 'Y'):?>
			<?=$tableId?>_helper.setIgnoreFilter(false);
			<?elseif (!empty($_REQUEST['del_filter']) && $_REQUEST['del_filter'] == 'Y'):?>
			<?=$tableId?>_helper.setIgnoreFilter(true);
			<?endif?>
		}
		BX('form_<?=$tableId?>').style.overflow = 'auto';
	</script>
	<?
	$lAdmin->EndEpilogContent();
	$lAdmin->AddAdminContextMenu(array(), false);
	$lAdmin->CheckListMode();

	?>
	<!-- START HTML -->
	<? if (!$arResult['RELOAD']): ?>
	<div id="<?= $tableId ?>_reload_container" class="catalog-product-search-dialog">
		<? if ($arResult['IS_EXTERNALCONTEXT']):
			$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/panel/main/admin.css');
		endif;
	endif ?>
	<div class="adm-s-search-sidebar-container-left " style="width: 20%;padding-bottom: 28px">
		<table class="adm-main-wrap" style="min-width:10px;">
			<tr>
				<td class="adm-left-side-wrap" style="background: none;" id="<?= $tableId ?>_resizable">
					<div class="adm-left-side" style="width:300px;">
						<div class="adm-submenu" id="adm-submenu">
							<div class="adm-submenu-items-wrap" id="adm-submenu-favorites">
								<div class="adm-submenu-items-stretch-wrap">
									<table class="adm-submenu-items-stretch">
										<tr>
											<td class="adm-submenu-items-stretch-cell">
												<div class="adm-submenu-items-block" id="<?= $tableId ?>_catalog_tree_wrap">

													<div
														class="adm-sub-submenu-block adm-sub-submenu-open root-submenu <?= empty($arResult["SECTION_ID"]) ? 'adm-submenu-item-active' : '' ?>">
														<div class="adm-submenu-item-name"
															id="<?= $tableId ?>_section_0">
															<a
																href="#" class="adm-submenu-item-name-link"
																onclick="return <?= $tableId ?>_helper.onSectionClick('0')"><span
																	class="adm-submenu-item-link-icon icon-default fileman_menu_icon"></span>
																	<span class="adm-submenu-item-name-link-text" title="<?= $arResult['IBLOCKS'][$arResult['IBLOCK_ID']]['NAME'] ?>">
																		<?= $arResult['IBLOCKS'][$arResult['IBLOCK_ID']]['NAME'] ?>
																	</span>
																<?if (sizeof($arResult['IBLOCKS']) > 1):?>
																<span class="adm-s-arrow-cont" title="<?=GetMessage('SPS_CHOOSE_CATALOG')?>" id="<?= $tableId ?>_iblock_menu_opener"></span>
																<?endif?>
															</a>
														</div>
														<div
															class="adm-sub-submenu-block-children"><?= renderTree($arResult['SECTIONS'], 1, $arResult['TABLE_ID']) ?></div>
													</div>

												</div>
											</td>
										</tr>
									</table>
								</div>
							</div>
						</div>
					</div>
				</td>
				<td class="adm-workarea-wrap"></td>
			</tr>
		</table>
<!--		<div class="adm-submenu-separator"></div>-->
	</div>
	<div class="adm-s-search-content-container-right" style="width: 80%;">
		<div class="adm-s-content">
			<div class="adm-s-search-container">
				<div class="adm-s-search-box">
					<table>
						<tr>
							<td class="adm-s-search-tag-cell"><span class="adm-s-search-tag"
																	id="<?= $tableId ?>_section_label"
																	style="<?= $arResult['SECTION_LABEL'] ? '' : 'display:none' ?>"><?= $arResult['SECTION_LABEL'] ?>
									<span class="adm-s-search-tag-del" onclick="return <?= $tableId ?>_helper.onSectionClick('0')"></span></span>
							</td>
							<td class="adm-s-search-input-cell"><input type="text" value="<?= htmlspecialcharsbx($arFilter['QUERY']) ?>" id="<?= $tableId ?>_query" onkeyup="<?= $tableId ?>_helper.onSearch(this.value)">
							</td>
						</tr>
					</table>

				</div>
				<div class="adm-s-search-control-box">
					<input class="adm-s-search-submit" type="submit" value="">
					<span class="adm-s-search-box-separator" id="<?= $tableId ?>_query_clear_separator" style="<?= $arFilter['QUERY'] ? '' : 'display:none' ?>"></span>
					<input class="adm-s-search-reset" id="<?= $tableId ?>_query_clear" type="reset" value="" style="<?= $arFilter['QUERY'] ? '' : 'display:none' ?>" onclick="return <?= $tableId ?>_helper.clearQuery()">
				</div>
			</div>

			<form name="<?= $tableId ?>_find_form" method="GET" action="<? echo $APPLICATION->GetCurPage() ?>?" accept-charset="<? echo LANG_CHARSET; ?>" id="<?= $tableId ?>_form">
				<input type="hidden" name="mode" value="list">
				<input type="hidden" name="SECTION_ID" value="<?= (int)$arResult['SECTION_ID'] ?>" id="<?= $tableId ?>_section_id">
				<input type="hidden" name="QUERY" value="<?= htmlspecialcharsbx($arFilter['QUERY']) ?>" id="<?= $tableId ?>_query_value">
				<input type="hidden" name="func_name" value="<? echo htmlspecialcharsbx($arResult['JS_CALLBACK']) ?>">
				<input type="hidden" name="lang" value="<? echo LANGUAGE_ID ?>">
				<input type="hidden" name="LID" value="<?= $arResult['LID'] ?>">
				<input type="hidden" name="caller" value="<?= $arResult['CALLER'] ?>">
				<input type="hidden" name="IBLOCK_ID" value="<?= (int)$arResult['IBLOCK_ID'] ?>" id="<?= $tableId ?>_iblock"/>
				<input type="hidden" name="subscribe" value="<?= $arResult['SUBSCRIPTION']? 'Y' : 'N' ?>">

				<?
				$oFilter = new CAdminFilter(
					$arResult['TABLE_ID'] .'_iblock_'.(int)$arResult['IBLOCK_ID']. "_filter",
					$arResult['FILTER_LABELS']
				);
				$oFilter->SetDefaultRows("find_code");
				$oFilter->Begin();
				?>
				<tr>
					<td nowrap><?= GetMessage("SPS_CODE") ?>:</td>
					<td nowrap>
						<input type="text" name="filter_code" size="50" value="<? echo htmlspecialcharsex($_REQUEST["filter_code"]) ?>">
					</td>
				</tr>
				<tr>
					<td nowrap><?= GetMessage("SPS_TIMESTAMP") ?>:</td>
					<td nowrap><? echo CalendarPeriod("filter_timestamp_from", htmlspecialcharsex($_REQUEST['filter_timestamp_from']), "filter_timestamp_to", htmlspecialcharsex($_REQUEST['filter_timestamp_to']), "form1") ?></td>
				</tr>
				<tr>
					<td nowrap><?= GetMessage("SPS_ACTIVE") ?>:</td>
					<td nowrap>
						<select name="filter_active">
							<option value=""><?= htmlspecialcharsex("(" . GetMessage("SPS_ANY") . ")") ?></option>
							<option
								value="Y"<? if ($_REQUEST['filter_active'] == "Y" || empty($_REQUEST['filter_active'])) echo " selected" ?>><?= htmlspecialcharsex(GetMessage("SPS_YES")) ?></option>
							<option
								value="N"<? if ($_REQUEST['filter_active'] == "N") echo " selected" ?>><?= htmlspecialcharsex(GetMessage("SPS_NO")) ?></option>
						</select>
					</td>
				</tr>

				<?foreach ($arProps as $arProp):
					if ($arProp["FILTRABLE"] == "Y" && $arProp["PROPERTY_TYPE"] != "F")
					{
						?>
						<tr>
							<td><?= $arProp["NAME"] ?>:</td>
							<td>
								<?if (array_key_exists("GetAdminFilterHTML", $arProp["PROPERTY_USER_TYPE"])):
									echo "<script type='text/javascript'>var arClearHiddenFields = [];</script>";
									echo call_user_func_array($arProp["PROPERTY_USER_TYPE"]["GetAdminFilterHTML"], array(
										$arProp,
										array("VALUE" => "_REQUEST[filter_el_property_" . $arProp["ID"] . ']'),
									));
								elseif ($arProp["PROPERTY_TYPE"] == 'S'):?>
									<input type="text" name="filter_el_property_<?= $arProp["ID"] ?>" value="<? echo htmlspecialcharsex($_REQUEST["filter_el_property_" . $arProp["ID"]]) ?>" size="30">&nbsp;<?= ShowFilterLogicHelp() ?>
								<?
								elseif ($arProp["PROPERTY_TYPE"] == 'N' || $arProp["PROPERTY_TYPE"] == 'E'): ?>
									<input type="text" name="filter_el_property_<?= $arProp["ID"] ?>" value="<? echo htmlspecialcharsex($_REQUEST["filter_el_property_" . $arProp["ID"]]) ?>" size="30">
								<?
								elseif ($arProp["PROPERTY_TYPE"] == 'L'): ?>
									<select name="filter_el_property_<?= $arProp["ID"] ?>">
										<option value=""><? echo GetMessage("SPS_VALUE_ANY") ?></option>
										<option value="NOT_REF"><? echo GetMessage("SPS_A_PROP_NOT_SET") ?></option><?
										$dbrPEnum = CIBlockPropertyEnum::GetList(Array("SORT" => "ASC", "NAME" => "ASC"), Array("PROPERTY_ID" => $arProp["ID"]));
										while ($arPEnum = $dbrPEnum->GetNext()):
											?>
											<option
												value="<?= $arPEnum["ID"] ?>"<? if ($_REQUEST["filter_el_property_" . $arProp["ID"]] == $arPEnum["ID"]) echo " selected" ?>><?= $arPEnum["VALUE"] ?></option>
										<?
										endwhile;
										?></select>
								<?
								elseif ($arProp["PROPERTY_TYPE"] == 'G'):
									echo _ShowGroupPropertyFieldList('filter_el_property_' . $arProp["ID"], $arProp, $_REQUEST['filter_el_property_' . $arProp["ID"]]);
								endif;
								?>
							</td>
						</tr>
					<?
					}
					endforeach;

				foreach ($arSKUProps as $arProp)
				{
					if ($arProp["FILTRABLE"] == "Y" && $arProp["PROPERTY_TYPE"] != "F" && $arResult['SKU_CATALOG']['SKU_PROPERTY_ID'] != $arProp['ID'])
					{
						?>
						<tr>
							<td><? echo $arProp["NAME"] ?> (<?=GetMessage("SPS_OFFER")?>):</td>
							<td>
								<?if (array_key_exists("GetAdminFilterHTML", $arProp["PROPERTY_USER_TYPE"])):
									echo "<script type='text/javascript'>var arClearHiddenFields = [];</script>";
									echo call_user_func_array($arProp["PROPERTY_USER_TYPE"]["GetAdminFilterHTML"], array(
										$arProp,
										array("VALUE" => "_REQUEST[find_sub_el_property_" . $arProp["ID"] . ']'),
									));
								elseif ($arProp["PROPERTY_TYPE"] == 'S'):?>
									<input type="text" name="filter_sub_el_property_<?= $arProp["ID"] ?>" value="<? echo htmlspecialcharsex($_REQUEST["filter_sub_el_property_" . $arProp["ID"]]) ?>" size="30">&nbsp;<?= ShowFilterLogicHelp() ?>
								<?
								elseif ($arProp["PROPERTY_TYPE"] == 'N' || $arProp["PROPERTY_TYPE"] == 'E'): ?>
									<input type="text" name="filter_sub_el_property_<?= $arProp["ID"] ?>" value="<? echo htmlspecialcharsex($_REQUEST["filter_sub_el_property_" . $arProp["ID"]]) ?>" size="30">
								<?
								elseif ($arProp["PROPERTY_TYPE"] == 'L'): ?>
									<select name="filter_sub_el_property_<?= $arProp["ID"] ?>">
										<option value=""><? echo GetMessage("SPS_VALUE_ANY") ?></option>
										<option value="NOT_REF"><? echo GetMessage("SPS_A_PROP_NOT_SET") ?></option><?
										$dbrPEnum = CIBlockPropertyEnum::GetList(Array("SORT" => "ASC", "NAME" => "ASC"), Array("PROPERTY_ID" => $arProp["ID"]));
										while ($arPEnum = $dbrPEnum->GetNext()):
											?>
											<option
												value="<?= $arPEnum["ID"] ?>"<? if ($_REQUEST["filter_sub_el_property_" . $arProp["ID"]] == $arPEnum["ID"]) echo " selected" ?>><?= $arPEnum["VALUE"] ?></option>
										<?
										endwhile;
										?></select>
								<?
								elseif ($arProp["PROPERTY_TYPE"] == 'G'):
									echo _ShowGroupPropertyFieldList('filter_sub_el_property_' . $arProp["ID"], $arProp, $_REQUEST['filter_sub_el_property_' . $arProp["ID"]]);
								endif;
								?>
							</td>
						</tr>
					<?
					}
				}

				$oFilter->Buttons(
					array(
						"table_id" => $arResult['TABLE_ID'],
						"url" => $APPLICATION->GetCurPage(),
						"form" => $tableId."_find_form"
					)
				);

				$oFilter->End();
				?>
			</form>
			<div class="adm-navchain" style="vertical-align: middle; margin-left: 0;" id="<?= $tableId ?>_breadcrumbs">

			</div>
			<?
			$lAdmin->DisplayList();
			?>
		</div>
	</div>
	<? if (!$arResult['RELOAD']): ?>
	</div>
	<script type="text/javascript">
		<?=$tableId?>_helper = new BX.Catalog.ProductSearchDialog({
			tableId: '<?=$tableId?>',<?
			if ($arResult['JS_CALLBACK'] != '' || $arResult['JS_EVENT'] != '')
			{
				if ($arResult['JS_CALLBACK'] != '')
				{
					?>
			callback: '<?= $arResult['JS_CALLBACK'] ?>',<?
				}
				if ($arResult['JS_EVENT'] != '')
				{
					?>
			event: '<?= $arResult['JS_EVENT'] ?>',<?
				}
			}
			?>
			callerName: '<?=CUtil::JSEscape($arResult['CALLER'])?>',
			currentUri: '<?=CUtil::JSEscape($APPLICATION->GetCurPage())?>',
			popup: BX.WindowManager.Get(),
			iblockName: '<?=CUtil::JSEscape($arResult['IBLOCKS'][$arResult['IBLOCK_ID']]['NAME'])?>'
		});
		<?=$tableId?>_helper.setBreadcrumbs(<?=CUtil::PhpToJSObject($arResult['BREADCRUMBS'])?>);
		BX('<?=$tableId?>_query').focus();
	</script>
<? endif ?>
	<script type="text/javascript">

		<?
		if (sizeof($arResult['IBLOCKS']) > 1):
			$iblockMenu = array(array(
				'TEXT' => '<b>'.GetMessage('SPS_CHOOSE_CATALOG').':</b>',
				'CLOSE_ON_CLICK' => false
			), array('SEPARATOR' => true));
			foreach ($arResult['IBLOCKS'] AS $arIblock)
			{
				$iblockMenu[] = array(
					'TEXT' => '<span class="psd-catalog-menu-name" title="'.htmlspecialcharsbx($arIblock['NAME']).'">'.htmlspecialcharsbx($arIblock['NAME']).'</span><span class="psd-catalog-menu-lid" title="'.htmlspecialcharsbx($arIblock['SITE_NAME']).'">'.htmlspecialcharsbx($arIblock['SITE_NAME']).'</span>',
					'ONCLICK' => $tableId.'_helper.onIblockChange('.(int)$arIblock['ID'].',\''.CUtil::JSEscape($arIblock['NAME']).'\')',
				);
			}
			?>
			new BX.COpener({
				DIV: '<?=$tableId?>_iblock_menu_opener',
				MENU: <?=CUtil::PhpToJSObject($iblockMenu)?>
			});
		<?endif?>
		// override SaveSetting to fix URL
		<?=$tableId?>.SaveSettings = function (el) {
			var sCols = '', sBy = '', sOrder = '', sPageSize;

			var oSelect = document.list_settings.selected_columns;
			var n = oSelect.length;
			for (var i = 0; i < n; i++)
				sCols += (sCols != '' ? ',' : '') + oSelect[i].value;

			oSelect = document.list_settings.order_field;
			if (oSelect)
				sBy = oSelect[oSelect.selectedIndex].value;

			oSelect = document.list_settings.order_direction;
			if (oSelect)
				sOrder = oSelect[oSelect.selectedIndex].value;

			oSelect = document.list_settings.nav_page_size;
			sPageSize = oSelect[oSelect.selectedIndex].value;

			var bCommon = (document.list_settings.set_default && document.list_settings.set_default.checked);

			BX.userOptions.save('list', this.table_id, 'columns', sCols, bCommon);
			BX.userOptions.save('list', this.table_id, 'by', sBy, bCommon);
			BX.userOptions.save('list', this.table_id, 'order', sOrder, bCommon);
			BX.userOptions.save('list', this.table_id, 'page_size', sPageSize, bCommon);
			//>>>patch start
			var url = <?=$tableId?>_helper.buildUrl();
			//<<<patch end

			BX.WindowManager.Get().showWait(el);
			BX.userOptions.send(BX.delegate(function () {
				this.GetAdminList(
					url,
					function () {
						BX.WindowManager.Get().closeWait(el);
						BX.WindowManager.Get().Close();
					}
				);
			}, this));
		};

		<?=$tableId?>.ShowSettings = function(url)
		{
			(new BX.CDialog({
				content_url: url,
				resizable: false,
				resize_id: '<?=$tableId?>_settings',
				height: 475,
				width: 560
			})).Show();
		};

		<?=$tableId?>.DeleteSettings = function(bCommon)
		{
			BX.showWait();
			//>>>patch start
			var url = <?=$tableId?>_helper.buildUrl();
			//<<<patch end
			BX.userOptions.del('list', this.table_id, bCommon, BX.delegate(function(){
				BX.closeWait();
				this.GetAdminList(
					url,
					function(){BX.WindowManager.Get().Close();}
				);
			}, this));
		};

	</script>
<?
}
endif;