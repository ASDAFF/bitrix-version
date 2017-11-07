<?php
$push_default_option = array(
	'path_to_listener' => (CMain::IsHTTPS() ? "https" : "http")."://#DOMAIN#".(CMain::IsHTTPS() ? ":8894" : ":8893").(BX_UTF ? '/bitrix/sub/' : '/bitrix/subwin/'),
	'path_to_publish' => 'http://127.0.0.1:8895/bitrix/pub/',
	'nginx' => 'N',
	'push' => 'N',
);
?>