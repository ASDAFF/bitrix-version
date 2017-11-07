<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

global $APPLICATION;
global $DB;
global $USER;

if (!($USER->CanDoOperation('catalog_read') || $USER->CanDoOperation('catalog_store')))
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
$bReadOnly = !$USER->CanDoOperation('catalog_store');

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/include.php");

if ($ex = $APPLICATION->GetException())
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

	$strError = $ex->GetString();
	ShowError($strError);

	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
	die();
}

IncludeModuleLangFile(__FILE__);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/prolog.php");
ClearVars();

$docType = '';

if(isset($_REQUEST["DOCUMENT_TYPE"]))
{
	$docType = $_REQUEST["DOCUMENT_TYPE"];
}

$errorMessage = "";
$bVarsFromForm = false;
$ID = IntVal($_REQUEST["ID"]);
$userId = intval($USER->GetID());

if($_SERVER['REQUEST_METHOD'] == "GET" && $ID > 0 && ($_REQUEST["action"] == 'conduct' || $_REQUEST["action"] == 'cancellation') && check_bitrix_sessid())
{
	$result = false;
	$DB->StartTransaction();
	if($_REQUEST["action"] == 'conduct')
	{
		$result = CCatalogDocs::conductDocument($ID, $userId);
	}
	elseif($_REQUEST["action"] == 'cancellation')
	{
		$result = CCatalogDocs::cancellationDocument($ID, $userId);
	}
	if($result == true)
		$DB->Commit();
	else
		$DB->Rollback();

	if($ex = $APPLICATION->GetException())
	{
		require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
		$strError = $ex->GetString();
		if(!empty($result) && is_array($result))
		{
			$strError .= CCatalogStoreControlUtil::showErrorProduct($result);
		}
		CAdminMessage::ShowMessage($strError);
		$bVarsFromForm = true;
	}
	else
		LocalRedirect("/bitrix/admin/cat_store_document_list.php?lang=".LANG."&".GetFilterParams("filter_", false));
}

if($_SERVER['REQUEST_METHOD'] == "POST" && check_bitrix_sessid() && isset($_POST["DOCUMENT_TYPE"]))
{
	$docType = $_POST["DOCUMENT_TYPE"];
	$contractorId = intval($_REQUEST["CONTRACTOR_ID"]);
	$currency = '';
	$result = array();
	$docId = 0;

	if($_REQUEST["CAT_CURRENCY_STORE"])
		$currency = $_REQUEST["CAT_CURRENCY_STORE"];

	$arGeneral = Array(
		"DOC_TYPE" => $docType,
		"SITE_ID" => $_REQUEST["SITE_ID"],
		"DATE_DOCUMENT" => $_REQUEST["DOC_DATE"],
		"CREATED_BY" => $userId,
		"MODIFIED_BY" => $userId,
	);
	if($contractorId > 0)
		$arGeneral["CONTRACTOR_ID"] = $contractorId;
	if(strlen($currency) > 0)
		$arGeneral["CURRENCY"] = $currency;
	if(strlen($_REQUEST["CAT_DOC_TOTAL_SAVE"]) > 0)
		$arGeneral["TOTAL"] = doubleval($_REQUEST["CAT_DOC_TOTAL_SAVE"]);
	if($ID > 0)
	{
		if(CCatalogDocs::update($ID, $arGeneral))
			$docId = $ID;
	}
	else
		$docId = CCatalogDocs::add($arGeneral);
	if(isset($_POST["PRODUCT"]) && is_array($_POST["PRODUCT"]) && $docId)
	{
		if($_REQUEST["cancellation"] == 1)
		{
			LocalRedirect("/bitrix/admin/cat_store_document_edit.php?lang=".LANG."&ID=".$docId."&action=cancellation&sessid=".$_REQUEST["sessid"]."&".GetFilterParams("filter_", false));
		}

		if($ID > 0)
		{
			$dbElement = CCatalogStoreDocsElement::getList(array(), array("DOC_ID" => $ID));
			while($arElement = $dbElement->Fetch())
				CCatalogStoreDocsElement::delete($arElement["ID"]);
		}
		foreach($_POST["PRODUCT"] as $key => $val)
		{
			$storeTo = $val["CAT_DOC_STORE_TO"];
			$storeFrom = $val["CAT_DOC_STORE_FROM"];

			$arAdditional = Array(
				"AMOUNT" => $val["QUANTITY"],
				"ELEMENT_ID" => $val["PRODUCT_ID"],
				"PURCHASING_PRICE" => $val["PRICE"],
				"STORE_TO" => $storeTo,
				"STORE_FROM" => $storeFrom,
				"ENTRY_ID" => $key,
				"DOC_ID" => $docId,
			);

			$docElementId = CCatalogStoreDocsElement::add($arAdditional);

			if($docElementId && isset($val["BAR_CODE"]) && is_array($val["BAR_CODE"]))
			{
				foreach($val["BAR_CODE"] as $barCode)
				{
					if(!empty($barCode))
						CCatalogStoreDocsBarcode::add(array("BARCODE" => $barCode, "DOC_ELEMENT_ID" => $docElementId));
				}
			}
		}
		if($_REQUEST["save_and_conduct"])
		{
			LocalRedirect("/bitrix/admin/cat_store_document_edit.php?lang=".LANG."&ID=".$docId."&action=conduct&sessid=".$_REQUEST["sessid"]."&".GetFilterParams("filter_", false));
		}

	}
	if($docId)
		LocalRedirect("/bitrix/admin/cat_store_document_list.php?lang=".LANG."&".GetFilterParams("filter_", false));
}

if ($ID > 0)
{
	$arSelect = array(
		"ID",
		"SITE_ID",
		"DOC_TYPE",
		"CONTRACTOR_ID",
		"DATE_DOCUMENT",
		"CURRENCY",
		"STATUS",
	);

	$dbResult = CCatalogDocs::GetList(array(),array('ID' => $ID), false, false, $arSelect);
	if (!$dbResult->ExtractFields("str_"))
		$ID = 0;
	else
	{
		$docType = $str_DOC_TYPE;
		$bReadOnly = ($str_STATUS == 'Y') ? true : $bReadOnly;
	}
}

$requiredFields = CCatalogStoreControlUtil::getFields($docType);
if(!$requiredFields)
{
	LocalRedirect("/bitrix/admin/cat_store_document_list.php?lang=".LANG."&".GetFilterParams("filter_", false));
}

$TAB_TITLE = GetMessage("CAT_DOC_$docType");

if($ID > 0)
{
	if($bReadOnly)
	{
		$APPLICATION->SetTitle(str_replace("#ID#", $ID, GetMessage("CAT_DOC_TITLE_VIEW")).". ".$TAB_TITLE.".");
	}
	else
	{
		$APPLICATION->SetTitle(str_replace("#ID#", $ID, GetMessage("CAT_DOC_TITLE_EDIT")).". ".$TAB_TITLE.".");
	}
}
else
	$APPLICATION->SetTitle(GetMessage("CAT_DOC_NEW").". ".$TAB_TITLE.".");

$isDisabled = ($bReadOnly) ? " disabled" : "";

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

if ($bVarsFromForm)
	$DB->InitTableVarsForEdit("b_catalog_store_docs", "", "str_");

$aMenu = array(
	array(
		"TEXT" => GetMessage("CAT_DOC_LIST"),
		"ICON" => "btn_list",
		"LINK" => "/bitrix/admin/cat_store_document_list.php?lang=".LANG."&".GetFilterParams("filter_", false)
	)
);
$context = new CAdminContextMenu($aMenu);
$context->Show();
?>
	<style type="text/css">
		.cat-doc-title {font-weight: bold; text-align: center; font-size: 13px;}
		.cat-doc-hidden-div {display: none;}
		.hor-spacer {display:inline-block; width:25px;}
		.split-delete-item { width: 15px; height: 15px;  display: inline-block;}
		.split-delete-item {width: 15px; height: 15px; background:url('/bitrix/images/catalog/store/grey_del.gif'); }
		.split-delete-item:hover{background:url('/bitrix/images/catalog/store/red_del.gif') no-repeat; }
		.cat-doc-scroll-div {min-height: 50px; max-height: 300px; overflow: auto;}
		.cat-doc-status-left-Y {
			padding: 4px 0 4px 10px;
			background-color: #E1E9C5;
			-webkit-border-radius: 4px 0 0 4px;
			border-radius: 4px 0 0 4px;
			display: inline-block;
			text-align: right;
		}
		.cat-doc-status-left-N {
			padding: 4px 0 4px 10px;
			background-color: #E9A74A;
			-webkit-border-radius: 4px 0 0 4px;
			border-radius: 4px 0 0 4px;
			display: inline-block;
			text-align: right;
		}
		.cat-doc-status-right-Y {
			padding: 4px 10px;
			background-color: #E1E9C5;
			-webkit-border-radius: 0 4px 4px 0;
			border-radius: 0 4px 4px 0;
			display: inline-block;
			text-align: left;
			margin-left: -9px;
		}
		.cat-doc-status-right-N {
			padding: 4px 10px;
			background-color: #E9A74A;
			-webkit-border-radius: 0 4px 4px 0;
			border-radius: 0 4px 4px 0;
			display: inline-block;
			text-align: left;
			margin-left: -9px;
		}
	</style>
<?
$siteLID = "";
$arSiteMenu = array();
$arSitesShop = array();
$arSitesTmp = array();
$rsSites = CSite::GetList($_REQUEST["by"] = "id", $_REQUEST["order"] = "asc", Array("ACTIVE" => "Y"));
while ($arSite = $rsSites->GetNext())
{
	$site = COption::GetOptionString("sale", "SHOP_SITE_".$arSite["ID"], "");
	if ($arSite["ID"] == $site)
	{
		$arSitesShop[] = array("ID" => $arSite["ID"], "NAME" => $arSite["NAME"]);
	}
	$arSitesTmp[] = array("ID" => $arSite["ID"], "NAME" => $arSite["NAME"]);
}

$rsCount = count($arSitesShop);
if ($rsCount <= 0)
{
	$arSitesShop = $arSitesTmp;
	$rsCount = count($arSitesShop);
}

$arStores = array();
$rsStores = CCatalogStore::GetList(array(), array("ACTIVE" => "Y"));
while($arStore = $rsStores->GetNext())
{
	$arStores[] = $arStore;
}

$rsContractors = CCatalogContractor::GetList();
$arContractors = array();
while($arContractor = $rsContractors->Fetch())
{
	$arContractors[] = $arContractor;
}
?>

<?if(strlen($errorMessage) > 0)
	CAdminMessage::ShowMessage($errorMessage);?>
	<script language="JavaScript">
		var arProduct = [];
		var arProductEditCountProps = [];
		var countProduct = 0;
	</script>
<?
$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("CAT_DOC_TAB"), "ICON" => "catalog", "TITLE" => $TAB_TITLE),
);

$tabControl = new CAdminTabControl("tabControl", $aTabs);
$tabControl->Begin();
?>
	<form method="POST" action="/bitrix/admin/cat_store_document_edit.php?lang=ru" enctype="multipart/form-data" id="form_catalog_document_form" name="form_catalog_document_form"><div class="adm-detail-block" id="form_order_buyers_layout" style="min-width: 165px;">
	<input type="hidden" name="user_id" id="user_id" value="<?=$userId?>" onchange="/*fUserGetProfile(this);*/">
	<input type="hidden" name="ID" value="<?echo $ID ?>">
	<input type="hidden" name="max_table_id" id="max_table_id" value="1">
	<input type="hidden" name="CAT_DOC_TOTAL_SAVE" id="CAT_DOC_TOTAL_SAVE" value="0">
	<?

	if(isset($_REQUEST["DOCUMENT_TYPE"]) && strlen($_REQUEST["DOCUMENT_TYPE"]) > 0)
	{
		$docType = $_REQUEST["DOCUMENT_TYPE"];
	}
	?>
	<input type="hidden" name="DOCUMENT_TYPE" id="DOCUMENT_TYPE" value="<?=$docType;?>">
	<?=bitrix_sessid_post()?>
	<?
	$tabControl->BeginNextTab();
	?>
	<?if($ID > 0):?>
		<tr>
			<td width="40%" class="head"><span class="cat-doc-status-left-<?=$str_STATUS?>"><?=GetMessage('CAT_DOC_STATUS')?>:</span></td>
			<td width="60%">
	<span class="cat-doc-status-right-<?=$str_STATUS?>">
		<?=GetMessage('CAT_DOC_EXECUTION_'.$str_STATUS)?>
	</span>
			</td>
		</tr>
	<?endif;?>

	<tr>
		<td width="40%" class="head"><?=GetMessage('CAT_DOC_DATE')?>:</td>
		<td width="60%">
			<?if($bReadOnly):?>
				<?=$str_DATE_DOCUMENT?>
			<?else:?>
				<?= CalendarDate("DOC_DATE", (isset($str_DATE_DOCUMENT)) ? $str_DATE_DOCUMENT : date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")), time()), "form_catalog_document_form", "15", "class=\"typeinput\""); ?>
			<?endif;?>
		</td>
	</tr>
	<tr class="adm-detail-required-field" id="company-name-tr" >
		<td><?= GetMessage("CAT_DOC_SITE_ID") ?>:</td>
		<td>
			<select id="SITE_ID" name="SITE_ID" <?=($bReadOnly) ? " disabled" : ""?>/>
			<?foreach($arSitesShop as $key => $val)
			{
				$selected = ($val['ID'] == $str_SITE_ID) ? 'selected' : '';
				echo"<option ".$selected." value=".$val['ID'].">".$val["NAME"]." (".$val["ID"].")"."</option>";
			}
			?>
			</select>
		</td>
	</tr>
	<?if(is_set($requiredFields, "CURRENCY")):?>
		<tr class="adm-detail-required-field">
			<td><?= GetMessage("CAT_DOC_CURRENCY") ?>:</td>
			<td><? echo CCurrency::SelectBox("CAT_CURRENCY_STORE", $str_CURRENCY, "", true, "", "onChange=\"fRecalProduct(1, '', 'N', 'N');\" id='CAT_CURRENCY_STORE'".$isDisabled);?></td>
		</tr>
	<?endif;?>
	<?if((is_set($requiredFields, "CONTRACTOR"))):?>
		<tr class="adm-detail-required-field">
			<td><?= GetMessage("CAT_DOC_CONTRACTOR") ?>:</td>
			<td>
				<?if(count($arContractors) > 0 && is_array($arContractors)):?>
					<select style="max-width:300px"  name="CONTRACTOR_ID" <?=($bReadOnly) ? " disabled" : ""?>>
						<?foreach($arContractors as $key => $val)
						{
							$selected = ($val['ID'] == $str_CONTRACTOR_ID) ? 'selected' : '';
							$companyName = htmlspecialcharsbx($val["COMPANY"]);
							echo"<option ".$selected." value=".$val['ID'].">".$companyName."</option>";
						}
						?>
					</select>
				<?else:?>
					<a href="/bitrix/admin/cat_contractor_edit.php?lang=<? echo urlencode(LANGUAGE_ID); ?>"><?echo GetMessage("CAT_DOC_CONTRACTOR_ADD")?></a>
				<?endif;?>
			</td>
		</tr>
	<?endif;?>
	<?if(!$bReadOnly):?>
		<?if(is_set($requiredFields, "STORE_FROM")):?>
			<tr>
				<td><?= GetMessage("CAT_DOC_STORE_FROM") ?>:</td>
				<td>
					<select  style="max-width:300px" name="CAT_DOC_STORE_FROM" id="CAT_DOC_STORE_FROM" <?=($bReadOnly) ? " disabled" : ""?>>
						<?
						foreach($arStores as $key => $val)
						{
							$store = ($val["TITLE"] != '') ? $val["TITLE"]." (".$val["ADDRESS"].")" : $val["ADDRESS"];
							echo"<option value=".$val['ID'].">".$store."</option>";
						}
						?>
					</select>
				</td>
			</tr>
		<?endif;?>
		<?if(is_set($requiredFields, "STORE_TO")):?>
			<tr>
				<td><?= GetMessage("CAT_DOC_STORE_TO") ?>:</td>
				<td>
					<select  style="max-width:300px" name="CAT_DOC_STORE_TO" id="CAT_DOC_STORE_TO" <?=($bReadOnly) ? " disabled" : ""?>>
						<?
						foreach($arStores as $key => $val)
						{
							$store = ($val["TITLE"] != '') ? $val["TITLE"]." (".$val["ADDRESS"].")" : $val["ADDRESS"];
							echo"<option value=".$val['ID'].">".$store."</option>";
						}
						?>
					</select>
				</td>
			</tr>
		<?endif;?>
		<tr id="company-inn-tr">
			<td><?= GetMessage("CAT_DOC_BARCODE") ?>:</td>
			<td><input type="text" name="CAT_DOC_BARCODE" id="CAT_DOC_BARCODE" size="30" />
				<a href="#" onClick="ProductSearch(BX('CAT_DOC_BARCODE').value);" class="adm-btn"><?=GetMessage('CAT_DOC_BARCODE_FIND');?></a>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<div style="float:right">
					<a title="<?=GetMessage("CAT_DOC_ADD_ITEMS")?>" onClick="AddProductSearch(1);" class="adm-btn adm-btn-green adm-btn-add" href="javascript:void(0);"><?=GetMessage("CAT_DOC_ADD_ITEMS")?></a>
				</div>
			</td>
		</tr>
	<?endif;?>
	<tr>
		<td colspan="2">
			<table cellpadding="3" cellspacing="1" border="0" width="100%" class="internal" id="CAT_DOC_TABLE">
				<tr class="heading">
					<td></td>
					<td><?echo GetMessage("CAT_DOC_PRODUCT_PICTURE")?></td>
					<td><?echo GetMessage("CAT_DOC_PRODUCT_NAME")?></td>
					<?if(is_set($requiredFields, "RESERVED")):?>
						<td><?echo GetMessage("CAT_DOC_PRODUCT_RESERVED")?></td>
					<?endif;?>
					<?if(is_set($requiredFields, "AMOUNT")):?>
						<td><?echo GetMessage("CAT_DOC_PRODUCT_AMOUNT")?></td>
					<?endif;?>
					<?if(is_set($requiredFields, "NET_PRICE")):?>
						<td><?echo GetMessage("CAT_DOC_PRODUCT_PRICE")?></td>
					<?endif;?>
					<?if(is_set($requiredFields, "TOTAL")):?>
						<td><?echo GetMessage("CAT_DOC_PRODUCT_SUMM")?></td>
					<?endif;?>
					<?if(is_set($requiredFields, "STORE_FROM")):?>
						<td><?echo GetMessage("CAT_DOC_STORE_FROM")?></td>
					<?endif;?>
					<?if(is_set($requiredFields, "STORE_TO")):?>
						<td><?echo GetMessage("CAT_DOC_STORE_TO")?></td>
					<?endif;?>
					<td><?echo GetMessage("CAT_DOC_BARCODE")?></td>
				</tr>
				<tr></tr>

			</table>
			<br>
			<?if($docType === DOC_ARRIVAL):?>
				<div style="text-align: right; padding-right: 40%; font-weight:bold; font-size:14px; font-family:Tahoma,Verdana,Arial,sans-serif; word-spacing: 5px;"><?=GetMessage("CAT_DOC_TOTAL")?>:<span id="CAT_DOC_TOTAL">&#9;0</span><span style="font-size: small;" id="CAT_DOC_TOTAL_CURRENCY"></span></div>
			<?endif;?>
		</td>
	</tr>
	<tr>
		<td valign="top" align="left" colspan="2">
			<br>

			<div style="float:right">
				<script>
					function number_format( number, decimals, dec_point, thousands_sep ) {	// Format a number with grouped thousands

						var i, j, kw, kd, km;

						// input sanitation & defaults
						if( isNaN(decimals = Math.abs(decimals)) ){
							decimals = 2;
						}
						if( dec_point == undefined ){
							dec_point = ".";
						}
						if( thousands_sep == undefined ){
							thousands_sep = " ";
						}

						i = parseInt(number = (+number || 0).toFixed(decimals)) + "";

						if( (j = i.length) > 3 ){
							j = j % 3;
						} else{
							j = 0;
						}

						km = (j ? i.substr(0, j) + thousands_sep : "");
						kw = i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands_sep);

						kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, 0).slice(2) : "");


						return km + kw + kd;
					}

					function ProductSearch(barcode)
					{
						dateURL = '<?=bitrix_sessid_get()?>&ORDER_AJAX=Y&BARCODE='+barcode;

						BX.showWait();
						BX.ajax.post('/bitrix/admin/cat_store_product_search.php', dateURL, fSearchProductResult);
					}
					function fMouseOver(el)
					{
						el.className = 'tr_hover';
					}
					function fMouseOut(el)
					{
						el.className = '';
					}

					function fEditPrice(item, type)
					{

						if (type == 'on')
						{
							BX('DIV_PRICE_' + item).className = 'edit_price edit_enable';
							BX('PRODUCT['+item+'][PRICE]').focus();
						}
						if (type == 'exit')
						{
							BX('DIV_PRICE_' + item).className = 'edit_price';
						}
					}

					function AddProductSearch(index)
					{
						var quantity = 1;
						var BUYER_ID = document.form_catalog_document_form.user_id.value;
						var store = 0;
						var lid = '';
						if(BX("CAT_DOC_STORE_FROM"))
							store = BX("CAT_DOC_STORE_FROM").value;
						if(BX("SITE_ID"))
							lid = BX("SITE_ID").value;
						window.open('/bitrix/admin/cat_store_product_search.php?lang=<?=LANGUAGE_ID?>&LID='+lid+'&addDefault=N&func_name=FillProductFields&index=' + index + '&QUANTITY=' + quantity + '&BUYER_ID=' + BUYER_ID + '&STORE_FROM_ID=' + store, '', 'scrollbars=yes,resizable=yes,width=980,height=550,top='+parseInt((screen.height - 500)/2-14)+',left='+parseInt((screen.width - 840)/2-5));
					}

					function enterBarcodes(id, quantity)
					{
						var formBarcodes;
						formBarcodes = BX.PopupWindowManager.create("catalog-popup-barcodes-"+id, null, {
							offsetTop : -100,
							offsetLeft : 200,
							autoHide : false,
							closeByEsc : true,
							closeIcon : false,
							titleBar : true,
							draggable: {restrict:true},
							titleBar: {content: BX.create("span", {html: '<?=GetMessageJS('CAT_DOC_POPUP_TITLE')?>', 'props': {'className': 'cat-doc-title'}})},
							content : BX("CAT_DOC_DIV_SCROLL_"+id)
						});
						formBarcodes.setButtons([
							<?if(!$bReadOnly):?>
							new BX.PopupWindowButton({
								text : "<?=GetMessage('CAT_DOC_SAVE')?>",
								className : "",
								events : {
									click : function()
									{
										BX('CAT_DOC_DIV_MULTI_'+id).appendChild(BX("CAT_DOC_DIV_SCROLL_"+id));
										formBarcodes.close();
									}
								}
							}),
							<?endif;?>
							new BX.PopupWindowButton({
								text : "<?=GetMessage('CAT_DOC_CANCEL')?>",
								className : "",
								events : {
									click : function()
									{
										BX('CAT_DOC_DIV_MULTI_'+id).appendChild(BX("CAT_DOC_DIV_SCROLL_"+id));
										formBarcodes.close();
									}
								}
							})
						]);
						if(BX("catalog-popup-barcodes-"+id).getElementsByClassName("popup-window-content")[0].children.length <= 0)
							BX("catalog-popup-barcodes-"+id).getElementsByClassName("popup-window-content")[0].appendChild(BX("CAT_DOC_DIV_SCROLL_"+id));
						formBarcodes.show();
						if(BX('PRODUCT['+id+'][BAR_CODE_0]'))
							BX('PRODUCT['+id+'][BAR_CODE_0]').focus();
					}

				</script>
				<?
				$productAddBool = COption::GetOptionString('sale', 'SALE_ADMIN_NEW_PRODUCT', 'N');
				?>
				<?/*if ($productAddBool == "Y"):
					<a title="<?=GetMessage("SOE_NEW_ITEMS")?>" onClick="ShowProductEdit('', 'Y');" class="adm-btn adm-btn-green" href="javascript:void(0);"><?=GetMessage("SOE_NEW_ITEMS")?></a>
				endif;*/?>
				<?if(!$bReadOnly):?>
					<a title="<?=GetMessage("CAT_DOC_ADD_ITEMS")?>" onClick="AddProductSearch(1);" class="adm-btn adm-btn-green adm-btn-add" href="javascript:void(0);"><?=GetMessage("CAT_DOC_ADD_ITEMS")?></a>
				<?endif;?>
			</div>
		</td>
	</tr>
	<?$tabControl->EndTab();?>

	<?
	$tabControl->Buttons(
		array(
			"btnApply" => false,
			"disabled" => $bReadOnly,
			"back_url" => "/bitrix/admin/cat_store_document_list.php?lang=".LANG."&".GetFilterParams("filter_", false)
		)
	);
	if(!$bReadOnly)
	{
		?>
		<span class="hor-spacer"></span>
		<input type="submit" class="adm-btn-save" name="save_and_conduct" value="<?echo GetMessage("CAT_DOC_ADD_CONDUCT") ?>">
	<?
	}
	elseif($str_STATUS == "Y")
	{
		?>
		<span class="hor-spacer"></span>
		<input type="hidden" name="cancellation" id="cancellation" value = "0">
		<input type="button" class="adm-btn" onClick="if(confirm('<?=GetMessage("CAT_DOC_CANCELLATION_CONFIRM")?>')) {BX('cancellation').value = 1; BX('form_catalog_document_form').submit();}" value="<?echo GetMessage("CAT_DOC_CANCELLATION") ?>">
	<?
	}

	$tabControl->End();
	?>

	</form>
	<script>

	function FillProductFields(index, arParams, iblockID, arBarcodes)
	{
		countProduct = countProduct + 1;
		var ID = countProduct;

		var oTbl = BX("CAT_DOC_TABLE");
		if (!oTbl)
			return;

		if(document.getElementById("CAT_DOC_BARCODE"))
			var parentBarCode = document.getElementById("CAT_DOC_BARCODE").value;
		if(document.getElementById("CAT_DOC_STORE_FROM"))
			var parentStoreFrom = document.getElementById("CAT_DOC_STORE_FROM").value;
		if(document.getElementById("CAT_DOC_STORE_TO"))
			var parentStoreTo = document.getElementById("CAT_DOC_STORE_TO").value;
		if(document.getElementById("SITE_ID"))
			var sieID = document.getElementById("SITE_ID").value;


		var oRow = oTbl.insertRow(1);
		oRow.setAttribute('id','CAT_DOC_TABLE_ROW_' + ID);
		oRow.setAttribute('onmouseout','fMouseOut(this);');
		oRow.setAttribute('onmouseover','fMouseOver(this);');

		var oCellAction = oRow.insertCell(-1);
		oCellAction.setAttribute('class', 'action');
		var oCellPhoto = oRow.insertCell(-1);
		oCellPhoto.setAttribute('class','photo');
		var oCellName = oRow.insertCell(-1);
		oCellName.setAttribute('class','order_name');
		<?if(is_set($requiredFields, "RESERVED")):?>
		var oReserved = oRow.insertCell(-1);
		oReserved.setAttribute('class','reserved');
		<?endif;?>
		<?if(is_set($requiredFields, "AMOUNT")):?>
		var oCellQuantity = oRow.insertCell(-1);
		oCellQuantity.setAttribute('class','order_count');
		oCellQuantity.setAttribute('id','DIV_QUANTITY_' + ID);
		<?endif;?>
//    var oCellBalance = oRow.insertCell(-1);
//    oCellBalance.setAttribute('class','balance_count');
		<?if(is_set($requiredFields, "NET_PRICE")):?>
		var oCellPrice = oRow.insertCell(-1);
		oCellPrice.setAttribute('class','order_price');
		oCellPrice.setAttribute('align','center');
		oCellPrice.setAttribute('nowrap','nowrap');
		<?endif;?>
		<?if(is_set($requiredFields, "TOTAL")):?>
		var oCellSumma = oRow.insertCell(-1);
		oCellSumma.setAttribute('id','DIV_SUMMA_' + ID);
		oCellSumma.setAttribute('class','product_summa');
		oCellSumma.setAttribute('nowrap','nowrap');
		<?endif;?>
		<?if(is_set($requiredFields, "STORE_FROM")):?>
		var oCellStoreFrom = oRow.insertCell(-1);
		oCellStoreFrom.setAttribute('class','storeFrom');
		<?endif;?>
		<?if(is_set($requiredFields, "STORE_TO")):?>
		var oCellStoreTo = oRow.insertCell(-1);
		oCellStoreTo.setAttribute('class','storeTo');
		<?endif;?>

		var oCellBarcode = oRow.insertCell(-1);
		oCellBarcode.setAttribute('class','bar_code');
		oCellBarcode.setAttribute('align','center');
		for (key in arParams)
		{
			if (key == "id")
			{
				product_id = arParams[key];
			}
			if (key == "name")
			{
				var name = BX.util.htmlspecialchars(arParams[key]);
			}
			else if (key == "price")
			{
				var price = BX.util.htmlspecialchars(arParams[key]);
			}
			else if (key == "priceFormated")
			{
				var priceFormated = BX.util.htmlspecialchars(arParams[key]);
			}
			else if (key == "priceBase")
			{
				var priceBase = BX.util.htmlspecialchars(arParams[key]);
			}
			else if (key == "priceBaseFormat")
			{
				var priceBaseFormat = BX.util.htmlspecialchars(arParams[key]);
			}
			else if (key == "priceType")
			{
				var priceType = BX.util.htmlspecialchars(arParams[key]);
			}
			else if (key == "currency")
			{
				var currency = arParams[key];
			}
			else if (key == "priceDiscount")
			{
				var priceDiscount = BX.util.htmlspecialchars(arParams[key]);
			}
			else if (key == "quantity")
			{
				var quantity = BX.util.htmlspecialchars(arParams[key]);
			}
			else if (key == "summaFormated")
			{
				var summaFormated = BX.util.htmlspecialchars(arParams[key]);
			}
			else if (key == "weight")
			{
				var weight = BX.util.htmlspecialchars(arParams[key]);
			}
			else if (key == "vatRate")
			{
				var vatRate = arParams[key];
			}
			else if (key == "module")
			{
				var module = arParams[key];
			}
			else if (key == "valutaFormat")
			{
				var valutaFormat = arParams[key];
			}
			else if (key == "catalogXmlID")
			{
				var catalogXmlID = BX.util.htmlspecialchars(arParams[key]);
			}
			else if (key == "productXmlID")
			{
				var productXmlID = BX.util.htmlspecialchars(arParams[key]);
			}
			else if (key == "url")
			{
				var url = BX.util.htmlspecialchars(arParams[key]);
			}
			else if (key == "urlImg")
			{
				var urlImg = arParams[key];
			}
			else if (key == "urlEdit")
			{
				var urlEdit = arParams[key];
			}
			else if (key == "balance")
			{
				var balance = arParams[key];
			}
			else if (key == "priceTotalFormated")
			{
				var priceTotalFormated = arParams[key];
			}
			else if (key == "discountPercent")
			{
				var discountPercent = arParams[key];
			}
			else if (key == "orderCallback")
			{
				var orderCallback = arParams[key];
			}
			else if (key == "cancelCallback")
			{
				var cancelCallback = arParams[key];
			}
			else if (key == "payCallback")
			{
				var payCallback = arParams[key];
			}
			else if (key == "skuProps")
			{
				var skuProps = arParams[key];
				var arSkuProps = eval( '('+skuProps+')' );
			}
			else if (key == "store")
			{
				var store = arParams[key];
			}
			else if (key == "storeTo")
			{
				var storeTo = arParams[key];
			}
			else if (key == "storeFrom")
			{
				var storeFrom = arParams[key];
			}
			else if (key == "barcode")
			{
				var elementBarCode = arParams[key];
			}
			else if (key == "isMultiBarcode")
			{
				var isMultiBarcode = arParams[key];
			}
			else if (key == "reserved")
			{
				var reserved = arParams[key];
			}
		}

		var productProps = "<div id=\"PRODUCT_PROPS_USER_" + ID + "\">";
		var countProps = 1;
		var inputProps = "";
		for(var i in arSkuProps)
		{
			productProps += i+": "+arSkuProps[i]+"<br>";
			inputProps += "<input type=\"hidden\" value=\""+i+"\" name=\"PRODUCT["+ID+"][PROPS]["+countProps+"][NAME]\" id=\"PRODUCT_PROPS_NAME_"+ID+"_"+countProps+"\" >";
			inputProps += "<input type=\"hidden\" value=\""+arSkuProps[i]+"\" name=\"PRODUCT["+ID+"][PROPS]["+countProps+"][VALUE]\" id=\"PRODUCT_PROPS_VALUE_"+ID+"_"+countProps+"\" >";
			inputProps += "<input type=\"hidden\" value=\"\" name=\"PRODUCT["+ID+"][PROPS]["+countProps+"][CODE]\" id=\"PRODUCT_PROPS_CODE_"+ID+"_"+countProps+"\" >";
			inputProps += "<input type=\"hidden\" value=\""+countProps+"\" name=\"PRODUCT["+ID+"][PROPS]["+countProps+"][SORT]\" id=\"PRODUCT_PROPS_SORT_"+ID+"_"+countProps+"\" >";
			countProps++;
		}
		productProps += "</div>";
		arProductEditCountProps[ID] = countProps;

		var hiddenField = "<div align='center' id=\"product_name_" + ID + "\">";

		if (urlEdit.length > 0)
			hiddenField = hiddenField + "<a href=\""+urlEdit+"\" target=\"_blank\">";
		hiddenField = hiddenField + name;
		if (urlEdit.length > 0)
			hiddenField = hiddenField + "</a>";
		hiddenField = hiddenField + "</div>";

		hiddenField = hiddenField + "<input type=\"hidden\" name=\"PRODUCT[" + ID + "][SITEID]\" id=\"SITEID_" + ID + "\" value=\"" + sieID + "\" />\n";
		hiddenField = hiddenField + "<input type=\"hidden\" name=\"PRODUCT[" + ID + "][MODULE]\" id=\"PRODUCT[" + ID + "][MODULE]\" value=\"" + module + "\" />";
		hiddenField = hiddenField + "<input type=\"hidden\" name=\"PRODUCT[" + ID + "][CATALOG_XML_ID]\" id=\"PRODUCT[" + ID + "][CATALOG_XML_ID]\" value=\"" + catalogXmlID + "\" />";
		hiddenField = hiddenField + "<input type=\"hidden\" name=\"PRODUCT[" + ID + "][PRODUCT_XML_ID]\" id=\"PRODUCT[" + ID + "][PRODUCT_XML_ID]\" value=\"" + productXmlID + "\" />";
		hiddenField = hiddenField + "<input type=\"hidden\" name=\"PRODUCT[" + ID + "][DETAIL_PAGE_URL]\" id=\"PRODUCT[" + ID + "][DETAIL_PAGE_URL]\" value=\"" + url + "\" />";
		hiddenField = hiddenField + "<input type=\"hidden\" name=\"PRODUCT[" + ID + "][NAME]\" id=\"PRODUCT[" + ID + "][NAME]\" value=\"" + name + "\" />";
		hiddenField = hiddenField + "<input type=\"hidden\" name=\"PRODUCT[" + ID + "][PRODUCT_ID]\" id=\"PRODUCT[" + ID + "][PRODUCT_ID]\" value=\"" + product_id + "\" />";
		hiddenField = hiddenField + "<input type=\"hidden\" name=\"PRODUCT[" + ID + "][EDIT_PAGE_URL]\" id=\"PRODUCT[" + ID + "][EDIT_PAGE_URL]\" value=\"" + urlEdit + "\" />";
		hiddenField = hiddenField + "<input type=\"hidden\" name=\"PRODUCT[" + ID + "][MULTI_BARCODE]\" id=\"PRODUCT[" + ID + "][MULTI_BARCODE]\" value=\"" + isMultiBarcode + "\" />";
		hiddenField = hiddenField + "<input type=\"hidden\" name=\"edit_page_url_"+ID+"\" id=\"edit_page_url_"+ID+"\" value=\"" + urlEdit + "\" />";

		var imgSrc = "&nbsp;";
		if (urlImg != "")
		{
			imgSrc = "<img src=\""+urlImg+"\" alt=\"\" width=\"80\" border=\"0\" />";
			hiddenField = hiddenField + "<input type=\"hidden\" name=\"PRODUCT[" + ID + "][IMG_URL]\" id=\"PRODUCT[" + ID + "][IMG_URL]\" value=\"" + urlImg + "\" />";
		}
		else
			imgSrc = "<div align='center' class='no_foto'><?=GetMessage('NO_FOTO');?></div>";

		var actonHtml = "<div onclick=\"this.blur();BX.adminList.ShowMenu(this, ";
		actonHtml = actonHtml + "[{'ICON':'view','TEXT':'<?=GetMessage("CAT_DOC_DUPLICATE")?>','ACTION':'DuplicateProduct("+ID+");','DEFAULT':true}, {'ICON':'delete','TEXT':'<?=GetMessage("CAT_DOC_DEL")?>','ACTION':'DeleteProduct(this, "+ID+");fEnableSub();'}]);\" class=\"adm-list-table-popup\"></div>";

		<?if(!$bReadOnly):?>
		oCellAction.innerHTML = actonHtml;
		<?endif;?>
		oCellPhoto.innerHTML = imgSrc;
		oCellName.innerHTML = hiddenField;

		<?if(!$bReadOnly):?>
		oCellQuantity.innerHTML = "<div align='center'><input maxlength=\"7\" onChange=\"fRecalProduct(" + ID + ", '', 'N', 'N');\" type=\"text\" name=\"PRODUCT[" + ID + "][QUANTITY]\" id=\"PRODUCT[" + ID + "][QUANTITY]\" value=\"" + quantity + "\" size=\"4\"></div>";
		<?else:?>
		oCellQuantity.innerHTML = "<div align='center' id=\"PRODUCT[" + ID + "][QUANTITY_SPAN]\">" + quantity + "</div>";
		<?endif;?>

		<?if(is_set($requiredFields, "RESERVED")):?>
			oReserved.innerHTML = "<div align='center' id=\"PRODUCT[" + ID + "][QUANTITY_RESERVED_SPAN]\">" + reserved + "</div>";
		<?endif;?>


		<?if(is_set($requiredFields, "NET_PRICE")):?>
		var priceColumn = "";
		if (!valutaFormat) valutaFormat = '<?=$CURRENCY_FORMAT?>';

		priceColumn += "<div  align='center' id=\"DIV_PRICE_"+ID+"\" class=\"edit_price\">";
		priceColumn += "<span class=\"default_price_product\" id=\"default_price_"+ID+"\">";
		priceColumn += "</span>";
		priceColumn += "<span class=\"edit_price_product\" id=\"edit_price_"+ID+"\">";
		<?if(!$bReadOnly):?>
		priceColumn += "<input maxlength=\"9\" style='text-align: center;' align='center' onblur=\"fEditPrice('" + ID + "', 'exit');\" onclick=\"fEditPrice('" + ID + "', 'on');\" onchange=\"fRecalProduct('" + ID + "', 'price', 'N', 'N');\" type=\"text\" name=\"PRODUCT[" + ID + "][PRICE]\" id=\"PRODUCT[" + ID + "][PRICE]\" value=\"" + price + "\" size=\"5\" >";
		<?else:?>
		priceColumn += "<span id=\"PRODUCT[" + ID + "][PRICE_SPAN]\">"+price+"</span>";
		<?endif;?>
		priceColumn += "</span>";
		priceColumn += "<span id='currency_price_product' class='currency_price'>"+valutaFormat+"</span>";
		priceColumn += "</div>";
		priceColumn += "<div align='center' id=\"DIV_PRICE_OLD_"+ID+"\" class=\"base_price\" style=\"display:none;\">" + priceBaseFormat + " <span>"+valutaFormat+"</span></div>";

		priceColumn += "<div align='center' id=\"DIV_BASE_PRICE_WITH_DISCOUNT_"+ID+"\" class=\"base_price\">";

		if (discountPercent > 0)
			priceColumn += priceBaseFormat + "<span>"+valutaFormat+"</span>";

		priceColumn += "</div>";

		priceColumn += "<div align='center' id=\"DIV_DISCOUNT_"+ID+"\" class=\"discount\">";
		if (discountPercent > 0)
			priceColumn += "(<?=getMessage('NEWO_PRICE_DISCOUNT')?> "+discountPercent+"%)";
		priceColumn += "</div>";
		oCellPrice.innerHTML = priceColumn;
		<?endif;?>

		<?if(is_set($requiredFields, "TOTAL")):?>
		oCellSumma.innerHTML = "<div  align='center'>" + summaFormated+ "</div>";
		<?endif;?>

		<?if(is_set($requiredFields, "STORE_FROM")):?>
		oCellStoreFrom.innerHTML = "<input type='hidden' name=\"PRODUCT[" + ID + "][CAT_DOC_STORE_FROM]\" value="+parentStoreFrom+"><div align='center' id=\"DIV_STORE_FROM"+ID+"\">" + "<select style=\"max-width:300px\"  <?if((!($docType == DOC_DEDUCT)) || $bReadOnly) echo " disabled";?> id=\"PRODUCT[" + ID + "][CAT_DOC_STORE_FROM]\" name=\"PRODUCT[" + ID + "][CAT_DOC_STORE_FROM]\"><?foreach($arStores as $key => $val)
		{$store = ($val["TITLE"] != '') ? $val["TITLE"]." (".$val["ADDRESS"].")" : $val["ADDRESS"];	echo"<option value=".$val['ID'].">".($store)."</option>";}?>"+"</select><span id=\"CAT_DOC_QUANTITY_STORE_"+ID+"\"></span></div>";
		<?endif;?>
		<?if(is_set($requiredFields, "STORE_TO")):?>
		oCellStoreTo.innerHTML = "<input type='hidden' name=\"PRODUCT[" + ID + "][CAT_DOC_STORE_TO]\" value="+parentStoreTo+"><div align='center' id=\"DIV_STORE_TO"+ID+"\">" + "<select style=\"max-width:300px\" <?if($bReadOnly) echo " disabled";?> id=\"PRODUCT[" + ID + "][CAT_DOC_STORE_TO]\" name=\"PRODUCT[" + ID + "][CAT_DOC_STORE_TO]\"><?foreach($arStores as $key => $val)
		{$store = ($val["TITLE"] != '') ? $val["TITLE"]." (".$val["ADDRESS"].")" : $val["ADDRESS"];	echo"<option value=".$val['ID'].">".($store)."</option>";}?>"+"</select></div>";
		<?endif;?>

		oCellBarcode.innerHTML = "<input type='hidden' name=\"PRODUCT[" + ID + "][BAR_CODE]\" value="+parentBarCode+"><div align='center'><span name=\"PRODUCT[" + ID + "][BAR_CODE]\" id=\"PRODUCT[" + ID + "][BAR_CODE]\"></span></div>";

		if(isMultiBarcode == 'Y')
		{
			<?$action = ($bReadOnly)?"VIEW":"ENTER";?>
			if(document.getElementById("PRODUCT["+ID+"][QUANTITY]"))
				var quan = document.getElementById("PRODUCT["+ID+"][QUANTITY]").value;
			else
				var quan = document.getElementById("PRODUCT["+ID+"][QUANTITY_SPAN]").innerHTML;
			oCellBarcode.innerHTML = "<div align='center'><a href=\"javascript:void(0);\" style=\"min-width:135px;\" onClick=\"enterBarcodes("+ID+", "+quan+");\" class=\"adm-btn\"><?=GetMessage('CAT_DOC_BARCODES_'.$action);?></a></div>";
		}

		if(isMultiBarcode == 'Y')
		{
			var oCell, oCellDel;
			var divTableMulti =  oCellBarcode.appendChild(document.createElement('DIV'));
			divTableMulti.setAttribute('id', 'CAT_DOC_DIV_MULTI_'+ID);
			divTableMulti.setAttribute('class', 'cat-doc-hidden-div');
			var divTableMultiScroll =  divTableMulti.appendChild(document.createElement('DIV'));
			divTableMultiScroll.setAttribute('id', 'CAT_DOC_DIV_SCROLL_'+ID);
			divTableMultiScroll.setAttribute('class', 'cat-doc-scroll-div');
			var tableMulti = divTableMultiScroll.appendChild(document.createElement('table'));
			tableMulti.setAttribute('id', 'CAT_DOC_TABLE_MULTI_'+ID);
			tableMulti.setAttribute('class', 'someclass');
			if(arBarcodes !== undefined && arBarcodes.length > 0)
			{
				for(i = 0; i < arBarcodes.length; i++)
				{
					if(!document.getElementById("PRODUCT[" + ID + "][BAR_CODE_"+ i +"]"))
					{
						oRow = tableMulti.insertRow(i);
						oCell = oRow.insertCell(-1);
						oCell.innerHTML = "<input value='"+arBarcodes[i]+"' <?=($bReadOnly)?" disabled":""?> maxlength=\"40\" type=\"text\" size=\"13\" name=\"PRODUCT[" + ID + "][BAR_CODE]["+ i +"]\" id=\"PRODUCT[" + ID + "][BAR_CODE_"+ i +"]\">";
						<?if(!$bReadOnly):?>
						oCellDel = oRow.insertCell(-1);
						oCellDel.innerHTML = "<a class=\"split-delete-item\"  tabIndex=\"-1\" href=\"javascript:void(0);\" onclick=\"deleteBarCodeRow("+ID+", "+i+"); \" title=<?=GetMessage('CAT_DOC_DELETE_BARCODE')?>></a>";
						<?endif;?>
					}
				}
			}
		}
		//array product in busket
		arProduct[ID] = product_id;

		if(storeTo)
			setSelectByValue("PRODUCT[" + ID + "][CAT_DOC_STORE_TO]", storeTo);
		else if(parentStoreTo)
			setSelectByValue("PRODUCT[" + ID + "][CAT_DOC_STORE_TO]", parentStoreTo);

		if(storeFrom)
			setSelectByValue("PRODUCT[" + ID + "][CAT_DOC_STORE_FROM]", storeFrom);
		else if(parentStoreFrom)
			setSelectByValue("PRODUCT[" + ID + "][CAT_DOC_STORE_FROM]", parentStoreFrom);

		if(elementBarCode && isMultiBarcode == 'N')
			BX("PRODUCT[" + ID + "][BAR_CODE]").innerHTML = elementBarCode;

		fRecalProduct(ID, '', 'Y', 'N');
	}

	function setSelectByValue(selectid, value)
	{
		if(BX(selectid))
		{
			sel = BX(selectid);
			if(sel.options != null)
			for(i=0; i<sel.options.length; i++)
			{
				if(sel.options[i].value == value)
				{
					sel.selectedIndex = i;
					return;
				}
			}
		}
	}

	function DuplicateProduct(id)
	{
		var storeTo = '';
		var storeFrom = '';
		var quantity = 1;
		var price = 0;
		var img = '';
		var barcode = '';
		var isMultiBarcode = 'N';
		var reserv = 0;
		if(BX("PRODUCT[" + id + "][IMG_URL]"))
			img = BX("PRODUCT[" + id + "][IMG_URL]").value;
		if(BX("PRODUCT[" + id + "][BAR_CODE]"))
			barcode = BX("PRODUCT[" + id + "][BAR_CODE]").innerHTML;
		if(BX("PRODUCT[" + id + "][QUANTITY_RESERVED_SPAN]"))
			reserv = BX("PRODUCT[" + id + "][QUANTITY_RESERVED_SPAN]").innerHTML;
		if(BX("PRODUCT[" + id + "][MULTI_BARCODE]"))
			isMultiBarcode = BX("PRODUCT[" + id + "][MULTI_BARCODE]").value;
		if(document.getElementById("PRODUCT[" + id + "][PRICE]"))
			price = (document.getElementById("PRODUCT[" + id + "][PRICE]").value);
		if(document.getElementById("PRODUCT[" + id + "][QUANTITY]"))
			quantity =(document.getElementById("PRODUCT[" + id + "][QUANTITY]").value);
		if(document.getElementById("PRODUCT[" + id + "][CAT_DOC_STORE_FROM]"))
			storeFrom = (document.getElementById("PRODUCT[" + id + "][CAT_DOC_STORE_FROM]").value);
		if(document.getElementById("PRODUCT[" + id + "][CAT_DOC_STORE_TO]"))
			storeTo = document.getElementById("PRODUCT[" + id + "][CAT_DOC_STORE_TO]").value;
		FillProductFields(1, {
			'id': document.getElementById("PRODUCT[" + id + "][PRODUCT_ID]").value,
			'module': document.getElementById("PRODUCT[" + id + "][MODULE]").value,
			'name': document.getElementById("PRODUCT[" + id + "][NAME]").value,
			'price': price,
			'productXmlID': document.getElementById("PRODUCT[" + id + "][PRODUCT_XML_ID]").value,
			'quantity': quantity,
			'reserved': reserv,
			'summaFormated':  "0",
			'urlEdit': document.getElementById("PRODUCT[" + id + "][EDIT_PAGE_URL]").value,
			'urlImg': img,
			'storeTo': storeTo,
			'storeFrom': storeFrom,
			'barcode': barcode,
			'isMultiBarcode' : isMultiBarcode
		}, 1, '');
	}

	function DeleteProduct(el, id)
	{
		if (confirm('<?=GetMessage('CAT_DOC_CONFIRM_DELETE')?>'))
		{
			var trDel = document.getElementById("CAT_DOC_TABLE_ROW_" + id).sectionRowIndex;
			var oTbl = document.getElementById("CAT_DOC_TABLE");
			oTbl.deleteRow(trDel);
			delete arProduct[id];

			fRecalProduct('', '', 'Y', 'N');
		}

		return false;
	}

	function fRecalProduct(id, type, recommendet, recalcAll)
	{
		var totalSum = 0;
		var showPrice = 0;
		var product_quantity, product_price;
		var counter = id;
		if(BX("PRODUCT["+id+"][QUANTITY]"))
		{
			product_quantity = BX("PRODUCT["+id+"][QUANTITY]").value;
		}
		else if(BX("PRODUCT["+id+"][QUANTITY_SPAN]"))
		{
			product_quantity = BX("PRODUCT["+id+"][QUANTITY_SPAN]").innerHTML;
		}
		if(BX("PRODUCT["+id+"][PRICE]") && BX("PRODUCT["+id+"][QUANTITY]"))
		{
			product_price = BX("PRODUCT["+id+"][PRICE]").value;
			if(!isNaN(product_price * product_quantity))
				showPrice = product_price * product_quantity;
			BX('DIV_SUMMA_' + id).innerHTML = "<div align='center'><span id='CAT_DOC_SUMM_"+id+"'>" + number_format(showPrice) + "</span></div>";
		}
		else if(BX("PRODUCT["+id+"][PRICE_SPAN]") && BX("PRODUCT["+id+"][QUANTITY_SPAN]"))
		{
			product_price = BX("PRODUCT["+id+"][PRICE_SPAN]").innerHTML;
			if(!isNaN(product_price * product_quantity))
				showPrice = product_price * product_quantity;
			BX('DIV_SUMMA_' + id).innerHTML = "<div align='center'><span id='CAT_DOC_SUMM_"+id+"'>" + number_format(showPrice) + "</span></div>";
		}
		if(BX("max_table_id"))
		{
			if(id > BX("max_table_id").value)
				BX("max_table_id").value = id;
			counter = BX("max_table_id").value;
		}
		<?if(is_set($requiredFields, "TOTAL")):?>
		for(var i=1; i<=counter; i++)
		{
			if(BX('CAT_DOC_SUMM_'+i))
			{
				totalSum = totalSum + Number(BX('CAT_DOC_SUMM_' + i).innerHTML.replace(/ /gi, ""));
			}
		}
		if(isNaN(totalSum))
			totalSum = 0;
		BX('CAT_DOC_TOTAL').innerHTML = " "+number_format(totalSum);
		BX('CAT_DOC_TOTAL_SAVE').value = " "+totalSum;
		if(BX('CAT_DOC_TOTAL_CURRENCY'))
			BX('CAT_DOC_TOTAL_CURRENCY').innerHTML = " " + BX("CAT_CURRENCY_STORE").value;
		<?endif;?>

		var oTbl = BX("CAT_DOC_TABLE_MULTI_"+id);
		if (!oTbl)
			return;

		var oRow;
		var oCell;
		var oCellDel;
		var tableLen = oTbl.rows.length;
		arrMy = new Array();
		var idMy;
		arResult = new Array();
		for(var j = 0; j <= tableLen; j++)
		{
			if(oTbl.rows[j] !== undefined)
			{
				arrMy = oTbl.rows[j].cells[0].firstChild.id.split(/_/);
				idMy = arrMy[arrMy.length - 1];
				arResult[arResult.length] = idMy;
			}
		}

		for(i in arResult)
		{
			if(document.getElementById("PRODUCT[" + id + "][BAR_CODE_"+ i +"]") && (BX("PRODUCT[" + id + "][BAR_CODE_"+ i +"]").value == ''))
			{
				varRowIndex = document.getElementById("PRODUCT[" + id + "][BAR_CODE_"+ i +"]").parentNode.parentNode.rowIndex;
				oTbl.deleteRow(varRowIndex);
			}
		}
		if(product_quantity <= 0)
			product_quantity = 1;
		for(i = 0; i < product_quantity; i++)
		{
			if(!document.getElementById("PRODUCT[" + id + "][BAR_CODE_"+ i +"]"))
			{
				oRow = oTbl.insertRow(i);
				oRow.setAttribute('id','CAT_DOC_BAR_CODE_' + id);
				oCell = oRow.insertCell(-1);
				oCell.innerHTML = "<input maxlength=\"40\" type=\"text\" size=\"13\" name=\"PRODUCT[" + id + "][BAR_CODE]["+ i +"]\" id=\"PRODUCT[" + id + "][BAR_CODE_"+ i +"]\">";
				oCellDel = oRow.insertCell(-1);
				oCellDel.innerHTML = "<a class=\"split-delete-item\"  tabIndex=\"-1\" href=\"javascript:void(0);\" onclick=\"deleteBarCodeRow("+id+", "+i+"); \" title=<?=GetMessage('CAT_DOC_DELETE_BARCODE')?>></a>";
			}
		}
	}

	function deleteBarCodeRow(id, i)
	{
		BX("PRODUCT[" + id + "][BAR_CODE_"+ i +"]").value = '';
	}

	function fSearchProductResult(result)
	{
		BX.closeWait();
		BX("CAT_DOC_BARCODE").value = '';
		BX("CAT_DOC_BARCODE").focus();

		var arBarCodes = new Array();
		if (result.length > 0)
		{
			var res = eval( '('+result+')' );
			if(res['id'] > 0)
			{
				res['quantity'] = 1;
				res['summaFormated'] = 0;
				FillProductFields(1, res, 1, arBarCodes);
			}

		}
	}

	function fRecalProductResult(result)
	{
		BX.closeWait();

		if (result.length > 0)
		{
			var res = eval( '('+result+')' );

			var changePriceProduct = "N";
			for(var i in res)
			{
				if (i > 0)
				{
					BX('PRODUCT[' + i + '][PRICE]').value = res[i]["PRICE"];
					BX('formated_price_' + i).innerHTML = res[i]["PRICE_DISPLAY"];
					if (res[i]["NOTES"].length > 0)
						BX('base_price_title_' + i).innerHTML = res[i]["NOTES"];

					if (res[i]["DISCOUNT_REPCENT"] > 0)
					{
						BX('DIV_DISCOUNT_' + i).innerHTML = '(<?=GetMessage('NEWO_PRICE_DISCOUNT')?> '+res[i]["DISCOUNT_REPCENT"]+'%)';
						BX('DIV_BASE_PRICE_WITH_DISCOUNT_' + i).innerHTML = res[i]["PRICE_BASE"]+" <span>"+res[0]["CURRENCY_FORMAT"]+"</span>";
					}
					else
					{
						prOld = parseFloat(BX('PRODUCT[' + i + '][PRICE_DEFAULT]').value);

						if (res[i]["PRICE"] == prOld)
						{
							if (BX('DIV_BASE_PRICE_WITH_DISCOUNT_' + i))
								BX('DIV_BASE_PRICE_WITH_DISCOUNT_' + i).innerHTML = '';
						}
						else
						{
							changePriceProduct = "Y";
							BX.show(BX('DIV_PRICE_OLD_'+i));
							if(BX('DIV_BASE_PRICE_WITH_DISCOUNT_'+i))
								BX.hide(BX('DIV_BASE_PRICE_WITH_DISCOUNT_'+i));
						}

						if (BX('DIV_DISCOUNT_' + i))
							BX('DIV_DISCOUNT_' + i).innerHTML = '';
					}

					BX('PRODUCT[' + i + '][QUANTITY]').value = res[i]["QUANTITY"];

					BX('DIV_BALANCE_' + i).value = res[i]["BALANCE"];
					BX('currency_price_product').innerHTML = res[0]["CURRENCY_FORMAT"];
					BX('PRODUCT[' + i + '][DISCOUNT_PRICE]').value = res[i]["DISCOUNT_PRICE"];
					BX('CURRENCY_' + i).value = res[i]["CURRENCY"];
				}
			}

			BX('DELIVER_ID_DESC').innerHTML = res[0]["DELIVERY_DESCRIPTION"];
			BX('DELIVERY_ID_PRICE').value = res[0]["DELIVERY_PRICE"];
			if (res[0]["DELIVERY"].length > 0)
				BX('DELIVERY_SELECT').innerHTML = res[0]["DELIVERY"];

			if (res[0]["ORDER_ERROR"] == "N")
			{
				if (BX('town_location_'+res[0]["LOCATION_TOWN_ID"]))
				{
					if (res[0]["LOCATION_TOWN_ENABLE"] == 'Y')
						BX('town_location_'+res[0]["LOCATION_TOWN_ID"]).style.display = 'table-row';
					else
						BX('town_location_'+res[0]["LOCATION_TOWN_ID"]).style.display = 'none';
				}

				BX('ORDER_TOTAL_PRICE').innerHTML = res[0]["PRICE_TOTAL"];

				if (res[0]["DISCOUNT_PRODUCT_VALUE"] > 0)
				{
					BX('ORDER_PRICE_WITH_DISCOUNT_DESC_VISIBLE').style.display = 'table-row';
					BX('ORDER_PRICE_WITH_DISCOUNT').innerHTML = res[0]["PRICE_WITH_DISCOUNT_FORMAT"];
				}
				else
				{
					if (changePriceProduct == 'N')
						BX('ORDER_PRICE_WITH_DISCOUNT_DESC_VISIBLE').style.display = 'none';
					else
					{
						BX('ORDER_PRICE_WITH_DISCOUNT_DESC_VISIBLE').style.display = 'table-row';
						BX('ORDER_PRICE_WITH_DISCOUNT').innerHTML = res[0]["PRICE_WITH_DISCOUNT_FORMAT"];
					}
				}

				if (parseInt(res[0]["ORDER_ID"]) > 0)
				{
					if (parseFloat(res[0]["PAY_ACCOUNT_DEFAULT"]) >= parseFloat(res[0]["PRICE_TO_PAY_DEFAULT"]))
					{
						BX('PAY_CURRENT_ACCOUNT_DESC').innerHTML = res[0]["PAY_ACCOUNT"];
						BX('buyerCanBuy').style.display = 'block';
					}
					else
					{
						if (BX('buyerCanBuy'))
							BX('buyerCanBuy').style.display = 'none';
					}
				}

				BX('ORDER_DELIVERY_PRICE').innerHTML = res[0]["DELIVERY_PRICE_FORMAT"];
				BX('ORDER_TAX_PRICE').innerHTML = res[0]["PRICE_TAX"];
				BX('ORDER_WAIGHT').innerHTML = res[0]["PRICE_WEIGHT_FORMAT"];
				BX('ORDER_PRICE_ALL').innerHTML = res[0]["PRICE_TO_PAY"];
				BX('ORDER_DISCOUNT_PRICE_VALUE_VALUE').innerHTML = res[0]["DISCOUNT_VALUE_FORMATED"];

				if (parseFloat(res[0]["DISCOUNT_VALUE"]) > 0)
					BX('ORDER_DISCOUNT_PRICE_VALUE').style.display = "table-row";

				if (res[0]["RECOMMENDET_CALC"] == "Y")
				{
					if (res[0]["RECOMMENDET_PRODUCT"].length == 0)
					{
						BX('tab_1').style.display = "none";
						BX('user_recomendet').style.display = "none";

						if (BX('user_basket').style.display == "block")
							fTabsSelect('user_basket', 'tab_2');
						else if (BX('buyer_viewed').style.display == "block")
							fTabsSelect('buyer_viewed', 'tab_3');
						else if (BX('tab_2').style.display == "block")
							fTabsSelect('user_basket', 'tab_2');
						else if (BX('tab_3').style.display == "block")
							fTabsSelect('buyer_viewed', 'tab_3');
					}
					else
					{
						BX('user_recomendet').innerHTML = res[0]["RECOMMENDET_PRODUCT"];
						if (BX('user_basket').style.display != "block" && BX('buyer_viewed').style.display != "block")
							fTabsSelect('user_recomendet', 'tab_1');
						else
							BX('tab_1').style.display = "block";
					}
				}

				orderWeight = res[0]["PRICE_WEIGHT"];
				orderPrice = res[0]["PRICE_WITH_DISCOUNT"];

			}
		}
	}

	function fEnableSub()
	{
		if (document.getElementById('tbl_sale_order_edit'))
			document.getElementById('tbl_sale_order_edit').style.zIndex  = 10000;
	}
	</script>
<?if($ID > 0)
{
	$arSelect = array(
		"ID",
		"DOC_ID",
		"STORE_FROM",
		"STORE_TO",
		"ELEMENT_ID",
		"AMOUNT",
		"PURCHASING_PRICE",
		"IS_MULTIPLY_BARCODE"
	);
	$dbResult = CCatalogStoreDocsElement::GetList(array(),array('DOC_ID' => $ID), false, false, $arSelect);

	while ($product = $dbResult->GetNext())
	{
		$docElementId = $product["ID"];
		$product = array_merge($product, CCatalogStoreControlUtil::getProductInfo($product["ELEMENT_ID"]));
		$product["BARCODE"] = '';
		if($product["IS_MULTIPLY_BARCODE"] == 'N')
		{
			$dbBarCode = CCatalogStoreBarCode::getList(array(), array("PRODUCT_ID" => $product["ELEMENT_ID"]));
			if($arBarCode = $dbBarCode->GetNext())
			{
				$product["BARCODE"] = $arBarCode["BARCODE"];
			}
		}
		elseif($product["IS_MULTIPLY_BARCODE"] == 'Y')
		{

			$dbBarCodes = CCatalogStoreDocsBarcode::getList(array(), array("DOC_ELEMENT_ID" => $docElementId));
			while($arBarCode = $dbBarCodes->GetNext())
			{
				$product["BARCODE"][] = $arBarCode["BARCODE"];
			}
		}

		$storeFromName = CCatalogStoreControlUtil::getStoreName($product["STORE_FROM"]);
		$storeToName = CCatalogStoreControlUtil::getStoreName($product["STORE_TO"]);;
		?>
		<script type="text/javascript">
			var arBarCodes = new Array();
			<?
			if(is_array($product["BARCODE"]))
			{
				foreach($product["BARCODE"] as $barCode)
				{
					?>
				arBarCodes.push("<?=$barCode?>");
				<?
				}
				$product["BARCODE"] = '';
			}
			$reserved = CCatalogProduct::GetById($product["ELEMENT_ID"]);
			$reserved = $reserved["QUANTITY_RESERVED"];
			?>
			FillProductFields(1, {
				'id': "<?=CUtil::JSEscape($product["ELEMENT_ID"])?>",
				'module': "<?=CUtil::JSEscape($product["MODULE"])?>",
				'name': "<?=CUtil::JSEscape($product["NAME"])?>",
				'price': "<?=CUtil::JSEscape($product["PURCHASING_PRICE"])?>",
				'productXmlID':  "<?=CUtil::JSEscape($product["XML_ID"])?>",
				'quantity':  "<?=CUtil::JSEscape($product["AMOUNT"])?>",
				'reserved':  "<?=CUtil::JSEscape($reserved)?>",
				'summaFormated':  "0",
				'url':  "<?=CUtil::JSEscape($product["DETAIL_PAGE_URL"])?>",
				'urlEdit':  "<?=CUtil::JSEscape($product["EDIT_PAGE_URL"])?>",
				'urlImg':  "<?=CUtil::JSEscape($product["IMG_URL"])?>",
				'storeTo':  "<?=CUtil::JSEscape($product["STORE_TO"])?>",
				'storeFrom':  "<?=CUtil::JSEscape($product["STORE_FROM"])?>",
				'isMultiBarcode' : "<?=CUtil::JSEscape($product["IS_MULTIPLY_BARCODE"])?>",
				'barcode' : "<?=CUtil::JSEscape($product["BARCODE"])?>"
			}, 1, arBarCodes);

		</script>

	<?
	}//end while order
}
?>

<?if ($bVarsFromForm && $ID <= 0)
{
	?>
	<script language="JavaScript">
		<?if($_POST["CAT_DOC_STORE_FROM"]):?>
		if(document.getElementById("CAT_DOC_STORE_FROM"))
			document.getElementById("CAT_DOC_STORE_FROM").value = <?=$_POST["CAT_DOC_STORE_FROM"]?>;
		<?endif;?>

		<?if($_POST["CAT_DOC_STORE_TO"]):?>
		if(document.getElementById("CAT_DOC_STORE_TO"))
			document.getElementById("CAT_DOC_STORE_TO").value = <?=$_POST["CAT_DOC_STORE_TO"]?>;
		<?endif;?>
		<?
		if(isset($_POST["PRODUCT"]) && is_array($_POST["PRODUCT"]))
		{
			foreach($_POST["PRODUCT"] as $product)
			{
				?>
				var arBarCodes = new Array();
				<?
				if(is_array($product["BAR_CODE"]))
				{
					foreach($product["BAR_CODE"] as $barCode)
					{
						?>
						arBarCodes.push(<?=$barCode?>);
						<?
					}
				$product["BAR_CODE"] = '';
				}
				$reserved = CCatalogProduct::GetById($product["PRODUCT_ID"]);
				$reserved = $reserved["QUANTITY_RESERVED"];
				?>
				FillProductFields(1, {
					'id': "<?=$product["PRODUCT_ID"]?>",
					'module': "<?=$product["MODULE"]?>",
					'name': "<?=$product["NAME"]?>",
					'price': "<?=$product["NET_PRICE"]?>",
					'productXmlID':  "<?=$product["PRODUCT_XML_ID"]?>",
					'quantity':  "<?=$product["QUANTITY"]?>",
					'summaFormated':  "0",
					'url':  "<?=$product["DETAIL_PAGE_URL"]?>",
					'urlEdit':  "<?=$product["EDIT_PAGE_URL"]?>",
					'urlImg':  "<?=$product["IMG_URL"]?>",
					'storeTo':  "<?=$product["CAT_DOC_STORE_TO"]?>",
					'storeFrom':  "<?=$product["CAT_DOC_STORE_FROM"]?>",
					'isMultiBarcode' : "<?=$product["MULTI_BARCODE"]?>",
					'reserved' : "<?=$reserved?>",
					'barcode' : "<?=CUtil::PhpToJSObject($product["BAR_CODE"])?>"
				}, 1, arBarCodes);
				<?
			}
		}
	?>
	</script>
<?
}
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>