<?
class TF_LOCATION_Events {
	function setDefaultLocation(&$arResult, &$arUserResult, &$arParams) {
		$settings = TF_LOCATION_Settings::GetSettings();
		if ($settings['TF_LOCATION_TEMPLATE'] == 'Y') {
			$arParams['TEMPLATE_LOCATION'] = 'tf_location';
		}
		if ($settings['TF_LOCATION_DELIVERY'] == 'Y') {		
			foreach($arResult['ORDER_PROP']['USER_PROPS_Y'] as $arFind) {
				if ($arFind['TYPE']=='LOCATION') {
					$i=$arFind['ID'];
					break;
				}
			};
			if (!isset($_REQUEST['ORDER_PROP_'.$i])) {
				$arUserResult['DELIVERY_LOCATION'] = $_SESSION['TF_LOCATION_SELECTED_CITY'];
				$arResult['ORDER_PROP']['USER_PROPS_Y'][$i]['DEFAULT_VALUE'] = $_SESSION['TF_LOCATION_SELECTED_CITY'];
				foreach($arResult['ORDER_PROP']['USER_PROPS_Y'][$i]['VARIANTS'] as $key=>$arLocation) {
					if ($arLocation['SELECTED'] == 'Y') {
						$arResult['ORDER_PROP']['USER_PROPS_Y'][$i]['VARIANTS'][$key]['SELECTED'] = 'N';
					}
				};
				foreach($arResult['ORDER_PROP']['USER_PROPS_Y'][$i]['VARIANTS'] as $key=>$arLocation) {
					if($arLocation['ID']==$_SESSION['TF_LOCATION_SELECTED_CITY']) {
						$arResult['ORDER_PROP']['USER_PROPS_Y'][$i]['VARIANTS'][$key]['SELECTED'] = 'Y';
					};
				};
			}
		};
		
	}
}	
?>