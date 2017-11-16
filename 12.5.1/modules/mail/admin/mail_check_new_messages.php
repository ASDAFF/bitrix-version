<?
/*
##############################################
# Bitrix: SiteManager                        #
# Copyright (c) 2004 Bitrix                  #
# http://www.bitrixsoft.com                  #
# mailto:admin@bitrixsoft.com                #
##############################################
*/
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/prolog_user.php");

$MOD_RIGHT = $APPLICATION->GetGroupRight("mail");
if($MOD_RIGHT<"R") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
include(GetLangFileName($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/mail/lang/", "/admin/mail_check_new_messages.php"));
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mail/include.php");

$err_mess = "File: ".__FILE__."<br>Line: ";

@set_time_limit(1800);

$APPLICATION->SetTitle(GetMessage("MAIL_CHECK_TITLE"));

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");
?>
<form action="mail_check_new_messages.php" method="get">
<table border="0" cellspacing="1">
	
	<tr>
		<td valign="top" align="left" nowrap>
		<p>
			<?echo GetMessage("MAIL_CHECK_CHECK")?>
			<select name="mailbox_id">
				<option value=""><?echo GetMessage("MAIL_CHECK_CHECK_ALL")?></option>
				<?
				$l = CMailbox::GetList(Array("NAME"=>"ASC", "ID"=>"ASC"), Array("VISIBLE"=>"Y"));
				while($l->ExtractFields("mb_")):
					?><option value="<?echo $mb_ID?>"<?if($mailbox_id==$mb_ID)echo " selected"?>><?echo $mb_NAME?></option><?
				endwhile;
				?>
			</select>
			<input type="hidden" name="lang" value="<?echo LANG?>">
			<input type="submit" name="make_action" value="<?echo GetMessage("MAIL_CHECK_CHECK_OK")?>">
			<?echo bitrix_sessid_post();?>
		</p>
	</tr>
	
</table></form>
<?
if(check_bitrix_sessid())
{
	$arFilter = Array("ACTIVE"=>"Y");
	if($mailbox_id>0)
		$arFilter["ID"] = $mailbox_id;

	$dbr = CMailBox::GetList(array(), $arFilter);
	while($res = $dbr->ExtractFields("f_"))
	{
		CMailError::ResetErrors();
		$mb = new CMailbox();

		echo '<p><b>'.GetMessage("MAIL_CHECK_TEXT").'&quot;'.$f_NAME.'&quot;:</b></p>';

		if($mb->Connect($res["ID"]))
		{
			CAdminMessage::ShowNote(GetMessage("MAIL_CHECK_CNT")." ".intval($mb->new_mess_count)." ".GetMessage("MAIL_CHECK_CNT_NEW"));
			$aContext = array();
			if($mb->new_mess_count>0)
			{
				$aContext[] = array(
					"ICON" => "btn_list",
					"TEXT"=>GetMessage("MAIL_CHECK_VIEW"),
					"LINK"=>"mail_message_admin.php?find_mailbox_id=".$f_ID."&lang=".LANG."&find_new=Y&set_filter=Y",
					"TITLE"=>GetMessage("MAIL_CHECK_VIEW")
				);
			}
			$aContext[] = array(
				"ICON" => "btn_list",
				"TEXT"=>GetMessage("MAIL_CHECK_LOG"),
				"LINK"=>"mail_log.php?set_filter=Y&find_mailbox_id=".$f_ID."&lang=".LANG,
				"TITLE"=>GetMessage("MAIL_CHECK_LOG")
			);
		}
		else
		{
			CAdminMessage::ShowMessage(GetMessage("MAIL_CHECK_ERR")." ".CMailError::GetErrorsText());
			$aContext = array(
				array(
					"ICON" => "btn_list",
					"TEXT"=>GetMessage("MAIL_CHECK_MBOX_PARAMS"),
					"LINK"=>"mail_mailbox_edit.php?ID=".$f_ID."&lang=".LANG,
					"TITLE"=>GetMessage("MAIL_CHECK_MBOX_PARAMS")
				),
				array(
					"ICON" => "btn_list",
					"TEXT"=>GetMessage("MAIL_CHECK_LOG"),
					"LINK"=>"mail_log.php?set_filter=Y&find_mailbox_id=".$f_ID."&lang=".LANG,
					"TITLE"=>GetMessage("MAIL_CHECK_LOG")
				),
			);
		}
		$context = new CAdminContextMenu($aContext);
		$context->Show();
	}
}
?>
<?require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");?>