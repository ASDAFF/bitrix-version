<?
IncludeModuleLangFile(__FILE__);

global $APPLICATION, $DBType;

CModule::AddAutoloadClasses(
	"pull",
	array(
		"CPullChannel" => "classes/general/pull_channel.php",
		"CPullStack" => "classes/".$DBType."/pull_stack.php",
		"CPullWatch" => "classes/".$DBType."/pull_watch.php",
		"CPullOptions" => "classes/general/pull_options.php",
		"CPullTableSchema" => "classes/general/pull_table_schema.php",

		"CPullPush" => "classes/general/pull_push.php",
		"CPushManager" => "classes/general/pull_push.php",
		"CAppleMessage" => "classes/general/pushservices/apple_push.php",
		"CApplePush" => "classes/general/pushservices/apple_push.php",
		"CGoogleMessage" => "classes/general/pushservices/google_push.php",
		"CGooglePush" => "classes/general/pushservices/google_push.php",

	)
);

CJSCore::RegisterExt('pull', array(
	'js' => '/bitrix/js/pull/pull.js',
	'rel' => defined('BX_PULL_SKIP_LS')? array('ajax'): array('ajax', 'ls')
));
?>
