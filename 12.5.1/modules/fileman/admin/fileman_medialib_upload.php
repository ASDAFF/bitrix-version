<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/fileman/prolog.php");

IncludeModuleLangFile(__FILE__);
CModule::IncludeModule("fileman");

if (!CMedialib::CanDoOperation('medialib_view_collection', 0))
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

// **************************** Add items to medialibrary  ****************************
if (isset($_GET['action']) && $_GET['action'] == 'upload' && check_bitrix_sessid())
{
	$collectionId = intVal($_POST['collection_id']);
	$fileCount = intVal($_POST['FileCount']);
	$firstId = false;

	if (!CMedialib::CanDoOperation("medialib_new_item", $collectionId)) // Check access
		die();

	// Save elements
	if ($fileCount > 0 && $collectionId > 0)
	{
		CMedialib::Init();
		$arExt = CMedialib::GetMediaExtentions(false);

		for ($i = 1; $i <= $fileCount; $i++)
		{
			$name = $_FILES['SourceFile_'.$i]['name'];
			if (!CMedialib::CheckFileExtention($name, $arExt))
				continue;

			$name = trim(preg_replace("/[^a-zA-Z0-9!\$&\(\)\[\]\{\}\-\.;=@\^_\~]/is", "", $name));
			if (strlen(trim(substr($name, 0, strpos($name, '.')))) <= 0)
				$name = substr(md5(uniqid(rand(), true)), 0, 8).trim($name);

			$res = CMedialibItem::Edit(array(
				'file' => $_FILES['SourceFile_'.$i],
				'arFields' => array(
					'NAME' => $name,
					'DESCRIPTION' => '',
					'KEYWORDS' => ''
				),
				'arCollections' => array($collectionId)
			));

			if ($i == 1 && $res && $res['ID'] > 0)
				$firstId = $res['ID'];
		}
	}
	die('#JS#&first_id='.$firstId.'&col_id='.$collectionId.'&ml_type='.htmlspecialcharsEx($_GET['ml_type']).'#JS#');
}
elseif(isset($_GET['action']) && $_GET['action'] == 'redirect' && check_bitrix_sessid())  //Redirect after files uploading
{
	$APPLICATION->SetTitle(GetMessage('FM_ML_UPL_TITLE2'));
	$APPLICATION->SetAdditionalCSS('/bitrix/js/fileman/medialib/medialib_admin.css');
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

	$aContext = Array();
	$aContext[] = Array(
		"TEXT" => GetMessage("FM_ML_BACK_IN_ML"),
		"ICON" => "",
		"LINK" => "/bitrix/admin/fileman_medialib_admin.php?lang=".LANGUAGE_ID."&".bitrix_sessid_get(),
		"TITLE" => GetMessage("FM_ML_BACK_IN_ML")
	);
	$menu = new CAdminContextMenu($aContext);
	$menu->Show();

	$firstId = intVal($_GET['first_id']);
	$colId = intVal($_GET['col_id']);


	// Get all items with id > $firstId
	$arItems = CMedialibItem::GetList(array(
		'arCollections' => array($colId),
		'minId' => $firstId
	));

	$len = count($arItems);

	if ($len > 0)
	{
		$res = CMedialib::GetCollectionTree(array('checkByType' => true, 'typeId' => intVal($_GET['ml_type'])));
		$strSel = '<option value="0">'.GetMessage('ML_COL_SELECT').'</option>'.CMedialib::_BuildCollectionsSelectOptions($res['Collections'], $res['arColTree']);
		$thumbWidth = COption::GetOptionInt($module_id, "ml_thumb_width", 140);
		$thumbHeight = COption::GetOptionInt($module_id, "ml_thumb_height", 105);
		$tmbWidth = ($thumbWidth + 10).'px';

		?>
		<script>window.arKeywords = {};</script>

		<form name="ml_mu_form" action="/bitrix/admin/fileman_medialib_upload.php?action=postsave" method="POST">
		<table class="mu-items-list" border="0"><?
		for($i = 0; $i < $len; $i++)
		{
			$Item = $arItems[$i];
			?>
			<tr>
				<td class="mu-label" style="width: 150px;"><label for="item_name_<?=$i?>" ><b><?= GetMessage('ML_NAME')?>:</b></label></td>
				<td><input class="mu-text-inp" id="item_name_<?=$i?>" name="item_name_<?=$i?>" type="text" value="<?= $Item['NAME']?>" size="52"></td>
				<td rowSpan="3" class="mu-thumb-cell" style="width: <?= $tmbWidth?>">
				<?if ($Item['TYPE'] == 'image'):?>
					<img src="<?= $Item['THUMB_PATH']?>"/>
				<?else:?>
					<img src="/bitrix/images/1.gif" class="ml-item-thumb ml-item-no-thumb" />
				<?endif;?>
				</td>
			</tr>
			<tr>
				<td class="mu-label" style="vertical-align: top; padding-top: 2px;"><label for="item_desc_<?=$i?>" ><?= GetMessage('ML_DESC')?>:</label></td>
				<td style="vertical-align: top;"><textarea id="item_desc_<?=$i?>" name="item_desc_<?=$i?>" rows="3" cols="40"></textarea></td>
			</tr>
			<tr>
				<td class="mu-label"><label for="item_keys_<?=$i?>" ><?= GetMessage('ML_KEYWORDS')?>:</label></td>
				<td><input class="mu-text-inp" id="item_keys_<?=$i?>" name="item_keys_<?=$i?>" type="text" value="" size="52"></td>
			</tr>
			<tr>
				<td class="mu-label" style="vertical-align: top; padding-top: 2px;"><label for="item_cols_sel_<?=$i?>" ><b><?= GetMessage('ML_COLLECTIONS')?>:</b></label></td>
				<td style="padding-bottom: 8px;"><div class="mu-col-sel"><select id="item_cols_sel_<?=$i?>" onchange="itemColsSelChange(this);"  style="margin-top: 2px"><?= $strSel?></select></div>
				<input  id="item_colls_<?=$i?>" name="item_colls_<?=$i?>" type="hidden" value="" />
				<input  name="item_id_<?=$i?>" type="hidden" value="<?=$Item['ID']?>" />
				<script>
				BX.ready(function(){
					setTimeout(function(){addCollToItem(<?=$i?>, <?= $colId?>, BX("item_cols_sel_<?=$i?>"));}, 200);
					arKeywords[<?=$i?>] = {pKeys: BX('item_keys_<?=$i?>'), bFocusKeywords: false};
					arKeywords[<?=$i?>].pKeys.onchange = arKeywords[<?=$i?>].pKeys.onblur = function(){arKeywords[<?=$i?>].bFocusKeywords = true;};
				});
				</script>
				</td>
				<td><input type="checkbox" name="item_del_<?=$i?>" id="item_del_<?=$i?>"/><label for="item_del_<?=$i?>" ><?= GetMessage('ML_DELETE')?></label></td>
			</tr>
			<tr class="mu-separator"><td colSpan="3"></td></tr>
			<?
		}
		?></table>
		<br />
		<input type="hidden" value="<?= LANGUAGE_ID?>" name="lang" />
		<input type="hidden" value="<?= $len?>" name="items_count" />
		<input type="hidden" value="<?= $firstId?>" name="first_id" />
		<input type="hidden" value="<?= $colId?>" name="col_id" />
		<?=bitrix_sessid_post()?>
		<input type="submit" title="<?= GetMessage('admin_lib_edit_save_title')?>" value="<?= GetMessage('admin_lib_edit_save')?>" name="save" />
		<input type="button" title="<?= GetMessage('admin_lib_edit_cancel_title')?>" onclick="window.location='/bitrix/admin/fileman_medialib_admin.php?lang=<?= LANGUAGE_ID?>';" name="cancel" value="<?= GetMessage('admin_lib_edit_cancel')?>" />
		</form>
		<script>
		document.forms['ml_mu_form'].onsubmit = function(e)
		{
			var res = true, pName, pColS, pColV, i, itemsCount = <?= $len?>;

			for (i = 0; i < itemsCount; i++)
			{
				pName = BX('item_name_' + i);
				if (pName.value == '')
				{
					alert('<?= GetMessage('FM_ML_UPL_NO_NAME_WARN')?>');
					pName.focus();
					res = false;
					break;
				}

				pColV = BX('item_colls_' + i);

				if (pColV.value == '' || pColV.value == ',')
				{
					pColS = BX('item_cols_sel_' + i);
					alert('<?= GetMessage('FM_ML_UPL_NO_COLS_WARN')?>');
					pColS.focus();
					res = false;
					break;
				}
			}

			return res ? true : jsUtils.PreventDefault(e);
		};

		window.oCollections = <?= CUtil::PhpToJSObject($res['Collections'])?>;

		function itemColsSelChange(pEl)
		{
			var ItemId = pEl.id.substr('item_cols_sel_'.length);
			addCollToItem(ItemId, pEl.value, pEl);
			pEl.value = 0;
		}

		function addCollToItem(ItemId, id, pSel)
		{
			var
				i,
				pEl = BX('item_colls_' + ItemId),
				curArCols = pEl.value == '' ? [] : pEl.value.split(','),
				curL = curArCols.length;

			for (i = 0; i < curL; i++)
				if (parseInt(curArCols[i], 10) == id)
					return;

			var
				oCol = oCollections[id],
				pDiv = BX.create("DIV", {props: {className: 'mu-check-col', title: oCol.NAME}}),
				pDel = pDiv.appendChild(jsUtils.CreateElement("IMG", {src: '/bitrix/images/1.gif', className: 'mu-col-del', id: 'mu_it_' + id, title: '<?= GetMessage('ML_DEL_COL2ITEM')?>'}));

			curArCols.push(id);
			pDiv.appendChild(BX.create("SPAN", {text: oCol.NAME}));
			pDiv.onmouseover = function(){BX.addClass(this, 'mu-col-over');}
			pDiv.onmouseout = function(){BX.removeClass(this, 'mu-col-over');}

			if (oCol && oCol.KEYWORDS && !arKeywords[ItemId].bFocusKeywords)
				AppendKeywords(arKeywords[ItemId].pKeys, oCol.KEYWORDS);

			pDel.onclick = function(e)
			{
				var
					cid = parseInt(this.id.substr('mu_it_'.length)),
					pCont = this.parentNode.parentNode,
					itemInd = parseInt(pCont.lastChild.id.substr('item_cols_sel_'.length)),
					pEl = BX('item_colls_' + ItemId),
					curArCols = pEl.value.split(','),
					i, l = curArCols.length, newArCols = [], cid_;

				SelectOptionInColList(BX('item_cols_sel_' + itemInd), cid, false);
				pCont.removeChild(this.parentNode);

				for (i = 0; i < l; i++)
				{
					cid_ = parseInt(curArCols[i], 10);
					if (cid_ != cid && cid_ > 0)
						newArCols.push(cid_);
				}

				pEl.value = newArCols.join(',');
			};

			pEl.value = curArCols.join(',');
			pSel.parentNode.insertBefore(pDiv, pSel);
			SelectOptionInColList(pSel, id);
		}

		function SelectOptionInColList(pSel, val, bSel)
		{
			for (var i = 0, l = pSel.options.length; i < l; i++)
			{
				if (pSel.options[i].value == val)
				{
					pSel.options[i].className = (bSel !== false) ? 'mu-opt-checked' : '';
					pSel.options[i].title = (bSel !== false) ? '<?= GetMessage('ML_CHECKED_COL_TITLE')?>' : '';
					return;
				}
			}
		}

		function AppendKeywords(pInput, value)
		{
			if (!pInput || !value)
				return;

			var
				arKeys = [],
				arKeysR = pInput.value.split(',').concat(value.split(',')),
				kw, i, l = arKeysR.length;

			for (i = 0; i < l; i++)
			{
				kw = jsUtils.trim(arKeysR[i]);
				if (kw && !jsUtils.in_array(kw, arKeys))
					arKeys.push(kw);
			}

			pInput.value = arKeys.join(', ');
		}
		</script>
		<?
	}
}
elseif(isset($_GET['action']) && $_GET['action'] == 'postsave' && check_bitrix_sessid())
{
	$itemsCount = intVal($_POST['items_count']);

	if ($itemsCount > 0)
	{
		for($i = 0; $i < $itemsCount; $i++)
		{
			if (isset($_POST['item_del_'.$i]) && $_POST['item_del_'.$i])
			{
				CMedialib::DelItem(intVal($_POST['item_id_'.$i]));
				continue;
			}

			$arCols_ = explode(',', trim($_POST['item_colls_'.$i], ' ,'));
			$arCols = array();
			for ($j = 0, $n = count($arCols_); $j < $n; $j++)
			{
				if (intVal($arCols_[$j]) > 0 && CMedialib::CanDoOperation("medialib_edit_item", $arCols_[$j])) // Check access
					$arCols[] = intVal($arCols_[$j]);
			}

			if (count($arCols) > 0)
			{
				$res = CMedialibItem::Edit(array(
					'arFields' => array(
						'ID' => intVal($_POST['item_id_'.$i]),
						'NAME' => $_POST['item_name_'.$i],
						'DESCRIPTION' => $_POST['item_desc_'.$i],
						'KEYWORDS' => $_POST['item_keys_'.$i]
					),
					'arCollections' => $arCols
				));
			}
		}
	}

	LocalRedirect("/bitrix/admin/fileman_medialib_admin.php?lang=".LANGUAGE_ID."&".bitrix_sessid_get());
}
else // ***************************** Show upploader  **************************
{
	$APPLICATION->AddHeadScript('/bitrix/image_uploader/iuembed.js');

	$APPLICATION->SetTitle(GetMessage('FM_ML_UPL_TITLE1'));
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

	$aContext = Array();
	$aContext[] = Array(
		"TEXT" => GetMessage("FM_ML_BACK_IN_ML"),
		"ICON" => "",
		"LINK" => "/bitrix/admin/fileman_medialib_admin.php?lang=".LANGUAGE_ID."&".bitrix_sessid_get(),
		"TITLE" => GetMessage("FM_ML_BACK_IN_ML")
	);

	$menu = new CAdminContextMenu($aContext);
	$menu->Show();

	$res = CMedialib::GetCollectionTree(array('checkByType' => true, 'typeId' => intVal($_GET['type'])));
	/*
	$type = intVal($_GET['type']);
	$arType = CMedialib::GetTypeById($type);

	if ($arType)
	{
		foreach ($res['Collections'] as $id => $col)
		{
			// Del collection escription if it has another type
			if (!CMedialib::CompareTypesEx($res['Collections'][$id]['ML_TYPE'], $arType))
				unset($res['Collections'][$id]);
		}
	}
	*/
	?>

<form name="ml_upload">
<div  style="margin: 10px; font-size: 13px;">
<?= GetMessage('FM_ML_UPL_LOACATE')?>: <select title="<?= GetMessage('ML_ADD_COL2ITEM')?>" name="collection_id" onchange="itemColsSelChange2(this, arguments[0] || window.event);"><?= CMedialib::_BuildCollectionsSelectOptions($res['Collections'], $res['arColTree'], 0, intVal($_GET['col_id']))?></select>
</div>
</form>

<?
include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/image_uploader/version.php");
include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/image_uploader/localization.php");
?>

<?= BeginNote().GetMessage('FM_ML_UPL_NOTICE').EndNote();?>

<div style="border: 1px solid #94918C; float: left; padding: 5px;">
<script>

//
function itemColsSelChange2(pEl, e)
{
	if (window.oColAccess[pEl.value] != '1')
		alert("<?= GetMessage('FM_ML_UPL_ACCESS_DENIED')?>");
}

<?
$strFileMask = '';
$arExt = CMedialib::GetMediaExtentions(false);
for ($i = 0, $l = count($arExt); $i < $l; $i++)
	$strFileMask .= '*.'.CUtil::JSEscape(strtolower($arExt[$i])).';';
$strFileMask = trim($strFileMask, ' ;');

$str = '';
foreach ($res['Collections'] as $id => $col)
	$str .= $col['ID'].': "'.CMedialib::CanDoOperation('medialib_new_item', $col['ID']).'",';
?>
window.oColAccess = {<?= trim($str, ', ')?>};

//Create JavaScript object that will embed Image Uploader to the page.
var iu = new ImageUploaderWriter("ImageUploaderML", 700, 600);
iu.activeXControlCodeBase = "<?=$arAppletVersion["activeXControlCodeBase"]?>";
iu.activeXClassId = "<?=$arAppletVersion["IuActiveXClassId"]?>";
iu.activeXControlVersion = "<?=$arAppletVersion["IuActiveXControlVersion"]?>";
//For Java applet only path to directory with JAR files should be specified (without file name).
iu.javaAppletCodeBase = "<?=$arAppletVersion["javaAppletCodeBase"]?>";
iu.javaAppletClassName = "<?=$arAppletVersion["javaAppletClassName"]?>";
iu.javaAppletJarFileName = "<?=$arAppletVersion["javaAppletJarFileName"]?>";
iu.javaAppletCached = false;
iu.javaAppletVersion = "<?=$arAppletVersion["IuJavaAppletVersion"]?>";
iu.addParam("LicenseKey", "Bitrix");
iu.addParam("ShowDescriptions", "false");
iu.addParam("AllowLargePreview", "true");

//iu.showNonemptyResponse = "on"; // debug
iu.showNonemptyResponse = "off";
//Configure appearance.
iu.addParam("PaneLayout", "TwoPanes");
iu.addParam("ShowDebugWindow", "true");
iu.addParam("AllowRotate", "true");
iu.addParam("BackgroundColor", "#ffffff");
//Configure URL files are uploaded to.
iu.addParam("AdditionalFormName", "ml_upload");
iu.addParam("Action", "/bitrix/admin/fileman_medialib_upload.php?action=upload&<?= bitrix_sessid_get()?>&lang=<?= LANGUAGE_ID?>&ml_type=<?= intVal($_GET['type'])?>");
iu.addParam("RedirectUrl", "");
iu.addParam("FileMask", "<?= $strFileMask?>");
language_resources.addParams(iu);

function ImageUploaderML_AfterUpload(Html)
{
	try
	{
		var
			i1 = Html.indexOf('#JS#') + 4,
			i2 = Html.lastIndexOf('#JS#'),
			sGet = (i1 != -1 && i2 != i1) ? Html.substring(i1, i2) : '';
		return jsUtils.Redirect([], "/bitrix/admin/fileman_medialib_upload.php?action=redirect&<?= bitrix_sessid_get()?>&lang=<?= LANGUAGE_ID?>" + sGet);
	}
	catch(e)
	{
		return jsUtils.Redirect([], "/bitrix/admin/fileman_medialib_upload.php?<?= bitrix_sessid_get()?>&lang=<?= LANGUAGE_ID?>");
	}
}
iu.addEventListener("AfterUpload", "ImageUploaderML_AfterUpload");

//Tell Image Uploader writer object to generate all necessary HTML code to embed Image Uploader to the page.
iu.writeHtml();
</script>
</div>
	<?
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>