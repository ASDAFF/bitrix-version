<?
require($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule('twofingers.location');
$settings = TF_LOCATION_Settings::GetSettings();
if ($_REQUEST['request'] == 'getcities') {
	if (CModule::IncludeModule('sale')) {
		$db_vars = CSaleLocation::GetList(array("CITY_NAME_LANG"=>"ASC"), array("LID" => LANGUAGE_ID), false, false, array());
		while ($vars = $db_vars->Fetch()) {
			if ($vars['CITY_ID'] > 0) {
				$cities[] = Array(
					'NAME' => iconv(LANG_CHARSET, 'UTF-8', $vars['CITY_NAME']),
					'ID' => $vars['ID']
				);
				if (in_array($vars['ID'], $settings['TF_LOCATION_DEFAULT_CITIES'])) {
					$arr['DEFAULT_CITIES'][] = Array(
						'NAME' => iconv(LANG_CHARSET, 'UTF-8', $vars['CITY_NAME']),
						'ID' => $vars['ID']
					);
				}
			}
		}
		$arr['CITIES'] = $cities;
		print json_encode($arr);
	}
}
if ($_REQUEST['request'] == 'setcity') {
	if (CModule::IncludeModule('sale')) {
		$db_vars = CSaleLocation::GetList(array("CITY_NAME_LANG"=>"ASC"), array("LID" => LANGUAGE_ID), false, false, array());
		while ($vars = $db_vars->Fetch()) {
			if ($vars['ID'] == intval($_REQUEST['city'])) {
				$_SESSION['TF_LOCATION_SELECTED_CITY'] = $_REQUEST['city'];
				$_SESSION['TF_LOCATION_SELECTED_CITY_NAME'] = $vars['CITY_NAME'];
			}
		}
	}
	
}
?>