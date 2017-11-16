<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mail/prolog.php");

$MOD_RIGHT = $APPLICATION->GetGroupRight("mail");
if($MOD_RIGHT<"R")
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

IncludeModuleLangFile(__FILE__);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mail/include.php");

$dbr = $DB->Query("SELECT * FROM b_mail_msg_attachment WHERE ID=".intval($ID));
if($dbr_arr = $dbr->Fetch())
{
	header("Content-Type: application/force-download; name=\"".$dbr_arr["FILE_NAME"]."\"");
	header("Content-Transfer-Encoding: binary");
	header("Content-Length: ".$dbr_arr["FILE_SIZE"]);
	header("Content-Disposition: attachment; filename=\"".$dbr_arr["FILE_NAME"]."\"");
	header("Expires: 0");
	header("Cache-Control: no-cache, must-revalidate");
	header("Pragma: no-cache");

	echo $dbr_arr["FILE_DATA"];
	die();
}

$APPLICATION->SetTitle(GetMessage("EDIT_MESSAGE_TITLE"));

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");

$aMenu = array(
	array(
		"TEXT"=>GetMessage("MAIL_ATTACH_BACKLINK"),
		"LINK"=>"mail_message_admin.php?lang=".LANG
	)
);

$context = new CAdminContextMenu($aMenu);
$context->Show();

CAdminMessage::ShowMessage(GetMessage("MAIL_ATTACH_ERROR"));

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");?>