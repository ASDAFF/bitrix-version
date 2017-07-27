<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

$moduleName = "twofingers.location";
define("ADMIN_MODULE_NAME", $moduleName);
define("ADMIN_MODULE_ICON", "");

include($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/twofingers.location/lang/".LANGUAGE_ID."/install/index.php");

$APPLICATION->SetTitle(GetMessage('TITLE'));
?>