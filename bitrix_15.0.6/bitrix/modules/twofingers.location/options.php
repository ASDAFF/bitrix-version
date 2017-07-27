<?
$module_id = "twofingers.location";
IncludeModuleLangFile(__FILE__);
include_once($GLOBALS["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/include.php");
CModule::Includemodule('iblock');
if(isset($_POST)) {
	if (isset($_POST['TF_LOCATION_SAVE_SETTINGS'])) {
		TF_LOCATION_Settings::SetSettings($_POST);
	}
}
CJSCore::Init(array("jquery"));
$settings = TF_LOCATION_Settings::GetSettings();
$MOD_RIGHT = $APPLICATION->GetGroupRight($module_id);
if($MOD_RIGHT>="W" && true):
	$aTabs = array(0=>array('TAB'=>GetMessage('TF_LOCATION_SETTINGS_TAB'), 'DIV'=>'edit1', 'TITLE' => GetMessage('TF_LOCATION_SETTINGS_TAB_TITLE'),));
	$tab = new CAdminTabControl('TwoFingers_Settings_tab', $aTabs);
	$tab->Begin();
?>
	<script>
		function setCity(cityID, cityNAME) {
			$('.tf_location_cities').append('<li data-id="'+cityID+'">'+cityNAME+'<input type="hidden" value="'+cityID+'" name="TF_LOCATION_DEFAULT_CITIES[]"><i></i></li>');
			$('#LOCATION_tmp select').not('#COUNTRY_tmptmp').remove();
			$('#COUNTRY_tmptmp option').first().attr('selected', 'selected');
		}
		$().ready(function() {
			$(document).delegate('.tf_location_cities li i', 'click', function() {
				$(this).parent().remove();
			});
		});
	</script>
	<style>
		.tf_location_cities {
			font-weight: bold;
			list-style: none outside none;
			margin: 0;
			padding: 0;
		}
		.tf_location_cities li {
			margin-bottom: 3px;
		}
		.tf_location_cities li i {
			background: url("/bitrix/panel/main/images/popup_menu_sprite_1.png") no-repeat scroll -8px -787px rgba(0, 0, 0, 0);
			cursor: pointer;
			display: inline-block;
			height: 15px;
			margin-bottom: -2px;
			margin-left: 5px;
			position: relative;
			width: 15px;
		}
		#LOCATION_tmp > select {
			display: block;
			margin-bottom: 5px;
		}
	</style>
	<form method="post">
<?	$tab->BeginNextTab();?>
		<tr class="heading">
			<td colspan="2"><?=GetMessage('TF_LOCATION_MAIN_SECTIONS')?></td>
		</tr>
		<tr>
			<td width="40%" class="adm-detail-content-cell-l">
				<label for="TF_LOCATION_DE"><?=GetMessage('TF_LOCATION_DE') ?>:</label>
			</td>
			<td width="60%" class="adm-detail-content-cell-r">
				<input type="checkbox" name="TF_LOCATION_DELIVERY" id="TF_LOCATION_DE" value="Y" <?if ($settings['TF_LOCATION_DELIVERY'] == 'Y'):?> checked<?endif?>>
			</td>
        </tr>
		<tr>
			<td width="40%" class="adm-detail-content-cell-l">
				<label for="TF_LOCATION_TEMPLATE"><?=GetMessage('TF_LOCATION_TEMPLATE') ?>:</label>
			</td>
			<td width="60%" class="adm-detail-content-cell-r">
				<input type="checkbox" name="TF_LOCATION_TEMPLATE" id="TF_LOCATION_TEMPLATE" value="Y" <?if ($settings['TF_LOCATION_TEMPLATE'] == 'Y'):?> checked<?endif?>>
			</td>
        </tr>
		<tr>
			<td width="40%" class="adm-detail-content-cell-l">
				<label for="TF_LOCATION_ONUNKNOWN"><?=GetMessage('TF_LOCATION_ONUNKNOWN') ?>:</label>
			</td>
			<td width="60%" class="adm-detail-content-cell-r">
				<input type="checkbox" name="TF_LOCATION_ONUNKNOWN" id="TF_LOCATION_ONUNKNOWN" value="Y" <?if ($settings['TF_LOCATION_ONUNKNOWN'] == 'Y'):?> checked<?endif?>>
			</td>
        </tr>
		<tr>
			<td width="40%" class="adm-detail-content-cell-l">
				<label for="TF_LOCATION_CALLBACK"><?=GetMessage('TF_LOCATION_CALLBACK') ?>:</label>
			</td>
			<td width="60%" class="adm-detail-content-cell-r">
				<input size="40" type="text" name="TF_LOCATION_CALLBACK" id="TF_LOCATION_CALLBACK" value="<?=$settings['TF_LOCATION_CALLBACK']?>">
			</td>
        </tr>
		<tr>
			<td width="40%" class="adm-detail-content-cell-l">
				<label for="TF_LOCATION_ORDERLINK_CLASS"><?=GetMessage('TF_LOCATION_ORDERLINK_CLASS') ?>:</label>
			</td>
			<td width="60%" class="adm-detail-content-cell-r">
				<input size="40" type="text" name="TF_LOCATION_ORDERLINK_CLASS" id="TF_LOCATION_ORDERLINK_CLASS" value="<?=$settings['TF_LOCATION_ORDERLINK_CLASS']?>">
			</td>
        </tr>
		<tr>
			<td width="40%" class="adm-detail-content-cell-l">
				<label for="TF_LOCATION_HEADLINK_CLASS"><?=GetMessage('TF_LOCATION_HEADLINK_CLASS') ?>:</label>
			</td>
			<td width="60%" class="adm-detail-content-cell-r">
				<input size="40" type="text" name="TF_LOCATION_HEADLINK_CLASS" id="TF_LOCATION_HEADLINK_CLASS" value="<?=$settings['TF_LOCATION_HEADLINK_CLASS']?>">
			</td>
        </tr>
		<tr>
			<td width="40%" class="adm-detail-content-cell-l">
				<label for="TF_LOCATION_HEADLINK_TEXT"><?=GetMessage('TF_LOCATION_HEADLINK_TEXT') ?>:</label>
			</td>
			<td width="60%" class="adm-detail-content-cell-r">
				<input size="40" type="text" name="TF_LOCATION_HEADLINK_TEXT" id="TF_LOCATION_HEADLINK_TEXT" value="<?=$settings['TF_LOCATION_HEADLINK_TEXT']?>">
			</td>
        </tr>
		<tr class="heading">
			<td colspan="2"><?=GetMessage('TF_LOCATION_DEFAUL_LOCATION')?></td>
		</tr>
		<tr>
			<td width="40%" class="adm-detail-content-cell-l">
				<label for="TF_LOCATION_POPUP_RADIUS"><?=GetMessage('TF_LOCATION_POPUP_RADIUS') ?>:</label>
			</td>
			<td width="60%" class="adm-detail-content-cell-r">
				<input size="2" type="text" name="TF_LOCATION_POPUP_RADIUS" id="TF_LOCATION_POPUP_RADIUS" value="<?=$settings['TF_LOCATION_POPUP_RADIUS']?>"> px.
			</td>
        </tr>
		<tr>
			<td width="40%" class="adm-detail-content-cell-l" valign="top">
				<label for="TF_LOCATION_CHOOSE_CITY"><?=GetMessage('TF_LOCATION_CHOOSE_CITY')?>:</label>
			</td>
			<td width="60%" class="adm-detail-content-cell-r" valign="top">
				<ul class="tf_location_cities">
<?
	CModule::IncludeModule('sale');
	$db_vars = CSaleLocation::GetList(array("CITY_NAME_LANG"=>"ASC"), array("LID" => LANGUAGE_ID, "ID" => $settings['TF_LOCATION_DEFAULT_CITIES']), false, false, array());
	while ($vars = $db_vars->Fetch()):
?>
					<li data-id="<?=$vars['ID']?>"><?=$vars['CITY_NAME']?><input type="hidden" value="<?=$vars['ID']?>" name="TF_LOCATION_DEFAULT_CITIES[]"><i></i></li>
<?
	endwhile;
?>
				</ul>
			</td>
        </tr>			
		<tr class="">
			<td width="40%" class="adm-detail-content-cell-l" valign="top">
				<?=GetMessage('TF_LOCATION_ADD_CITY');?>:
			</td>
			<td width="60%" class="adm-detail-content-cell-r" valign="top">
<?
	$APPLICATION->IncludeComponent(
		"bitrix:sale.ajax.locations",
		"",
		array(
			"AJAX_CALL" => "N",
			"COUNTRY_INPUT_NAME" => "COUNTRY_tmp",
			"REGION_INPUT_NAME" => "REGION_tmp",
			"CITY_INPUT_NAME" => "tmp",
			"CITY_OUT_LOCATION" => "Y",
			"LOCATION_VALUE" => "",
			"ONCITYCHANGE" => "setCity($(this).val(), $(this).find('option:selected').text())",
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);
?>
			</td>
		</tr>
<?	$tab->EndTab();?>
<?	$tab->Buttons();?>
		<input type="submit" name="TF_LOCATION_SAVE_SETTINGS" class="adm-btn-save"  value="<?=GetMessage('TF_LOCATION_SAVE') ?>" title="<?=GetMessage('TF_LOCATION_SAVE_TITLE') ?>" />
		<input type="button" onclick="window.document.location = '?lang=<?=LANGUAGE_ID ?>'" value="<?=GetMessage('TF_LOCATION_CANCEL') ?>" title="<?=GetMessage('TF_LOCATION_CANCEL_TITLE') ?>" />
	</form>
<?$tab->End();?>
<?endif;?>