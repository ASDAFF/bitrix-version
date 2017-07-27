<?
class TF_LOCATION_Settings {
	const MODULE_ID = 'twofingers.location';
	
	public function GetSettings() {
		$arFields = array(
			'TF_LOCATION_FROM' => 'TEXT',
			'TF_LOCATION_CALLBACK' => 'TEXT',
			'TF_LOCATION_HEADLINK_CLASS' => 'TEXT',
			'TF_LOCATION_ORDERLINK_CLASS' => 'TEXT',
			'TF_LOCATION_POPUP_RADIUS' => 'TEXT',
			'TF_LOCATION_HEADLINK_TEXT' => 'TEXT',
			'TF_LOCATION_DEFAULT_CITIES' => 'ARRAY',
			'TF_LOCATION_ONUNKNOWN' => 'CHECKBOX',
			'TF_LOCATION_DELIVERY' => 'CHECKBOX',
			'TF_LOCATION_TEMPLATE' => 'CHECKBOX',
		);
		
		$arSettings = array();
		foreach($arFields as $code=>$type){
			$value = COption::GetOptionString(self::MODULE_ID,$code,'',SITE_ID);
			if ($type == 'ARRAY') {
				$value = json_decode($value);
			}
			$arSettings[$code] = $value;
		}
		
		return $arSettings;
	}
	public function SetSettings($arFields) {
		$arSetFields = array(
			'TF_LOCATION_FROM' => 'TEXT',
			'TF_LOCATION_CALLBACK' => 'TEXT',
			'TF_LOCATION_HEADLINK_CLASS' => 'TEXT',
			'TF_LOCATION_ORDERLINK_CLASS' => 'TEXT',
			'TF_LOCATION_HEADLINK_TEXT' => 'TEXT',
			'TF_LOCATION_POPUP_RADIUS' => 'TEXT',
			'TF_LOCATION_DEFAULT_CITIES' => 'ARRAY',
			'TF_LOCATION_DELIVERY' => 'CHECKBOX',
			'TF_LOCATION_TEMPLATE' => 'CHECKBOX',
			'TF_LOCATION_ONUNKNOWN' => 'CHECKBOX',
		);
		foreach($arSetFields as $code=>$value) {
			if ($value == 'CHECKBOX' && !isset($arFields[$code])) $val = 'N';
				elseif (isset($arFields[$code])) $val = $arFields[$code];
			if ($value == 'ARRAY') {
				$val = json_encode($arFields[$code]);
			}
			COption::SetOptionString(self::MODULE_ID, $code, $val, SITE_ID);
			
		}
	}

}
?>