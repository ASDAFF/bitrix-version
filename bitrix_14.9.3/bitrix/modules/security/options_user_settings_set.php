<?
$securityWarningTmp = "";

if(CModule::IncludeModule("security") && check_bitrix_sessid() && $USER->CanDoOperation('security_edit_user_otp')):

	$arSecurityFields = Array(
		"USER_ID" => $ID,
		"ACTIVE" => $security_ACTIVE,
		"SECRET" => $security_SECRET,
		"SYNC1" => $security_SYNC1,
		"SYNC2" => $security_SYNC2,
	);

	$security_res = CSecurityUser::update($arSecurityFields);
endif;
?>