<?
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("NOT_CHECK_PERMISSIONS", true);
define("DisableEventsCheck", true);
define("NO_AGENT_CHECK", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

header('Content-Type: image/gif');
echo base64_decode("R0lGODdhAQABAIAAAPxqbAAAACwAAAAAAQABAAACAkQBADs=");

$tag = $_GET['tag'];
$arTag = \Bitrix\Main\Mail\Tracking::parseTag($_GET['tag']);
$arTag['FIELDS']['IP'] = $_SERVER['REMOTE_ADDR'];
\Bitrix\Main\Mail\Tracking::read($arTag);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");