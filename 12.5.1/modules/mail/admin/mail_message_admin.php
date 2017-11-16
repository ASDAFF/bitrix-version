<?
/*
##############################################
# Bitrix: SiteManager                        #
# Copyright (c) 2002-2004 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:admin@bitrixsoft.com                #
##############################################
*/

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mail/prolog.php");

$MOD_RIGHT = $APPLICATION->GetGroupRight("mail");
if($MOD_RIGHT<"R") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
include(GetLangFileName($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/mail/lang/", "/admin/mail_message_admin.php"));
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mail/include.php");

$err_mess = "File: ".__FILE__."<br>Line: ";
$APPLICATION->SetTitle(GetMessage("MAIL_MSG_ADM_TITLE"));


$sTableID = "t_message_admin";
$oSort = new CAdminSorting($sTableID, "field_date", "desc");// инициализация сортировки
$lAdmin = new CAdminList($sTableID, $oSort);// инициализация списка


$filter = new CAdminFilter(
	$sTableID."_filter_id",
	array(
		"ID",
		GetMessage("MAIL_MSG_ADM_MAILBOX"),
		GetMessage("MAIL_MSG_ADM_MARKEDSPAM"),
		GetMessage("MAIL_MSG_ADM_FILTER_READ"),
		GetMessage("MAIL_MSG_ADM_FILTER_FROM"),
		GetMessage("MAIL_MSG_ADM_FILTER_TO"),
		GetMessage("MAIL_MSG_ADM_FILTER_SUBJECT"),
		GetMessage("MAIL_MSG_ADM_FILTER_TEXT"),
	)
);

$arFilterFields = Array(
	"find_all",
	"find_id",
	"find_mailbox_id",
	"find_spam",
	"find_new",
	"find_from",
	"find_to",
	"find_subject",
	"find_body",
);

$lAdmin->InitFilter($arFilterFields);//инициализация фильтра

$arFilter = Array(
	"ID"=>$find_id,
	"MAILBOX_ID"=>$find_mailbox_id,
	"SENDER"=>$find_from,
	"RECIPIENT"=>$find_to,
	"SUBJECT"=>$find_subject,
	"BODY"=>$find_body,
	"ALL"=>$find_all,
	"NEW_MESSAGE"=>$find_new,
	"SPAM"=>$find_spam
	);

// обработка действий групповых и одиночных
if($MOD_RIGHT=="W" && $arID = $lAdmin->GroupAction())
{
	if($_REQUEST['action_target']=='selected')
	{
		$FilterTmp = Array();
		switch($_REQUEST['action'])
		{
			case "mark_as_spam":
				$FilterTmp["SPAM"]="~Y";
				break;

			case "mark_as_notspam":
				$FilterTmp["SPAM"]="Y";
				break;

			case "mark_as_read":
				$FilterTmp["NEW_MESSAGE"]="Y";
				break;

			case "mark_as_unread":
				$FilterTmp["NEW_MESSAGE"]="~Y";
		}
		$FilterTmp = $arFilter + $FilterTmp;

		$rsData = CMailMessage::GetList(Array($by=>$order), $FilterTmp);
		while($arRes = $rsData->Fetch())
			$arID[] = $arRes['ID'];
	}

	$filter_id = false;
	if(substr($_REQUEST['action'], 0, strlen("refilter_num_")) == "refilter_num_")
	{
		$filter_id = substr($_REQUEST['action'], strlen("refilter_num_"));
		$_REQUEST['action'] = "refilter";
	}

	foreach($arID as $ID)
	{
		if(strlen($ID)<=0)
			continue;

		$ID = intval($ID);

		switch($_REQUEST['action'])
		{
			case "mark_as_spam":
				CMailMessage::MarkAsSpam($ID, true);
				break;
			case "mark_as_notspam":
				CMailMessage::MarkAsSpam($ID, false);
				break;
			case "mark_as_read":
					CMailMessage::Update($ID, Array("NEW_MESSAGE"=>"N"));
				break;
			case "mark_as_unread":
					CMailMessage::Update($ID, Array("NEW_MESSAGE"=>"Y"));
				break;
			case "delete":
				CMailMessage::Delete($ID);
				break;
			case "refilter":
				// применить ручные фильтры
				CMailFilter::FilterMessage($ID, "M", $filter_id);
				break;
		}
	}
}


$rsData = CMailMessage::GetList(Array($by=>$order), $arFilter);
$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart(50);

// установка строки навигации
$lAdmin->NavText($rsData->GetNavPrint(GetMessage("MAIL_MSG_ADM_NAVIGATION")));

$arHeaders = Array();
$arHeaders[] = Array("id"=>"SUBJECT", "content"=>GetMessage("MAIL_MSG_ADM_SUBJECT"), "default"=>true, "sort" => "subject");
$arHeaders[] = Array("id"=>"FIELD_FROM", "content"=>GetMessage("MAIL_MSG_ADM_FROM"), "default"=>true, "sort" => "field_from");
$arHeaders[] = Array("id"=>"FIELD_REPLY_TO", "content"=>GetMessage("MAIL_MSG_ADM_REPLY_TO"), "sort" => "field_reply_to");
$arHeaders[] = Array("id"=>"FIELD_CC", "content"=>GetMessage("MAIL_MSG_ADM_CC"), "sort" => "field_cc");
$arHeaders[] = Array("id"=>"FIELD_BCC", "content"=>GetMessage("MAIL_MSG_ADM_BCC"), "sort" => "field_bcc");
$arHeaders[] = Array("id"=>"FIELD_DATE", "content"=>GetMessage("MAIL_MSG_ADM_DATE"), "default"=>true, "sort" => "field_date");
$arHeaders[] = Array("id"=>"DATE_INSERT", "content"=>GetMessage("MAIL_MSG_ADM_RECEIVED"), "sort" => "date_insert");
$arHeaders[] = Array("id"=>"MAILBOX_NAME", "content"=>GetMessage("MAIL_MSG_ADM_MBOX"), "default"=>true, "sort" => "mailbox_name");
$arHeaders[] = Array("id"=>"MESSAGE_SIZE", "content"=>GetMessage("MAIL_MSG_ADM_SIZE"), "default"=>true, "sort" => "size", "align" => "right");

$arHeaders[] = Array("id"=>"SPAM_RATING", "content"=>GetMessage("MAIL_MSG_ADM_SPAM")."<br>".GetMessage("MAIL_MSG_ADM_SPAM_R"), "default"=>true, "sort" => "size");

$arHeaders[] = Array("id"=>"ATTACHMENTS", "content"=>GetMessage("MAIL_MSG_ADM_SPAM_ATTCH"), "default"=>true, "sort" => "attachments");

$arHeaders[] = Array("id"=>"ID", "content"=>"ID", "sort" => "id");
$arHeaders[] = Array("id"=>"MSG_ID", "content"=>"Message-ID");

$lAdmin->AddHeaders($arHeaders);

// построение списка
while($arRes = $rsData->NavNext(true, "f_"))
{
	$row =& $lAdmin->AddRow($f_ID, $arRes);

	$str = "";
	if($f_SPAM=="Y"):

		if($f_NEW_MESSAGE!="Y"):
			$str .= '<div class="mail-message-spam" title="'.GetMessage("MAIL_MSG_ADM_READ_SPAM").'"></div>';
		else:
			$str .= '<div class="mail-message-unread-spam" title="'.GetMessage("MAIL_MSG_ADM_NOTREAD_SPAM").'"></div>';
		endif;

	elseif($f_SPAM=="N"):

		if($f_NEW_MESSAGE!="Y"):
			$str .= '<div class="mail-message-notspam" title="'.GetMessage("MAIL_MSG_ADM_READ_NOTSPAM").'"></div>';
		else:
			$str .= '<div class="mail-message-unread-notspam" title="'.GetMessage("MAIL_MSG_ADM_NOTREAD_NOTSPAM").'"></div>';
		endif;

	else:

		if($f_NEW_MESSAGE!="Y"):
			$str .= '<div class="mail-message" title="'.GetMessage("MAIL_MSG_ADM_READ").'"></div>';
		else:
			$str .= '<div class="mail-message-unread" title="'.GetMessage("MAIL_MSG_ADM_NOTREAD").'"></div>';
		endif;

	endif;
	$str .= '<a href="mail_message_view.php?lang='.LANG.'&amp;ID='.$f_ID.'">'.(strlen($f_SUBJECT)>0?$f_SUBJECT:GetMessage("MAIL_MSG_ADM_NOSUBJ"));

	$row->AddViewField("SUBJECT", $str);

	$str = $f_MAILBOX_NAME.' [<a title="'.GetMessage("MAIL_MSG_ADM_CHANGE_MBOX").'" href="mail_mailbox_edit.php?ID='.$f_MAILBOX_ID.'&lang='.LANG.'">'.$f_MAILBOX_ID.'</a>]';

	$row->AddViewField("MAILBOX_NAME", $str);
	$row->AddViewField("MESSAGE_SIZE", CFile::FormatSize($f_MESSAGE_SIZE));

	$arRes["SPAM_RATING"] = CMailMessage::GetSpamRating($f_ID, $arRes);
	$str = Round($arRes["SPAM_RATING"], 2)."%";
	$row->AddViewField("SPAM_RATING", $str);

	$arActions = Array();

	$arActions[] = array(
			"DEFAULT" => "Y",
			"TEXT"=>GetMessage("MAIL_MSG_ADM_VIEW"),
			"ACTION"=>$lAdmin->ActionRedirect('mail_message_view.php?lang='.LANG.'&ID='.$f_ID)
		);

	if ($MOD_RIGHT=="W")
	{
		$arActions[] = Array("SEPARATOR" => true);

		if ($f_SPAM != "Y")
		{
			$arActions[] = array(
				"TEXT"=>GetMessage("MAIL_MSG_ADM_PROC_ACT_SPAM"),
				"ACTION"=>$lAdmin->ActionAjaxReload($APPLICATION->GetCurPage()."?action=mark_as_spam&ID=".$f_ID."&lang=".LANG."&".bitrix_sessid_get())
			);
		}

		if ($f_SPAM != "N")
		{
			$arActions[] = array(
				"TEXT"=>GetMessage("MAIL_MSG_ADM_PROC_ACT_NOTSPAM"),
				"ACTION"=>$lAdmin->ActionAjaxReload($APPLICATION->GetCurPage()."?action=mark_as_notspam&ID=".$f_ID."&lang=".LANG."&".bitrix_sessid_get())
			);
		}

		if ($f_NEW_MESSAGE == "Y")
		{
			$arActions[] = array(
				"TEXT"=>GetMessage("MAIL_MSG_ADM_PROC_ACT_READ"),
				"ACTION"=>$lAdmin->ActionAjaxReload($APPLICATION->GetCurPage()."?action=mark_as_read&ID=".$f_ID."&lang=".LANG."&".bitrix_sessid_get())
			);
		}
		else
		{
			$arActions[] = array(
				"TEXT"=>GetMessage("MAIL_MSG_ADM_PROC_ACT_NOTREAD"),
				"ACTION"=>$lAdmin->ActionAjaxReload($APPLICATION->GetCurPage()."?action=mark_as_unread&ID=".$f_ID."&lang=".LANG."&".bitrix_sessid_get())
			);
		}


		$arActions[] = Array("SEPARATOR" => true);

		$arActions[] = array(
				"ICON" => "delete",
				"TEXT"=>GetMessage("MAIL_MSG_ADM_PROC_ACT_DELETE"),
				"ACTION"=>"if(confirm('".GetMessage('MAIL_MSG_ADM_FILTER_CONFIRM10')."')) ".$lAdmin->ActionDoGroup($f_ID, "delete")
				//"ACTION"=>$lAdmin->ActionAjaxReload($APPLICATION->GetCurPage()."?action=delete&ID=".$f_ID."&lang=".LANG)
			);
	}

	$row->AddActions($arActions);
}

// "подвал" списка
$lAdmin->AddFooter(
	array(
		array("title"=>GetMessage("MAIN_ADMIN_LIST_SELECTED"), "value"=>$rsData->SelectedRowsCount()),
		array("counter"=>true, "title"=>GetMessage("MAIN_ADMIN_LIST_CHECKED"), "value"=>"0"),
	)
);


$arActions = Array();
$arActions["mark_as_spam"] = GetMessage("MAIL_MSG_ADM_PROC_ACT_SPAM");
$arActions["mark_as_notspam"] = GetMessage("MAIL_MSG_ADM_PROC_ACT_NOTSPAM");
$arActions["mark_as_read"] = GetMessage("MAIL_MSG_ADM_PROC_ACT_READ");
$arActions["mark_as_unread"] = GetMessage("MAIL_MSG_ADM_PROC_ACT_NOTREAD");
$arActions["delete"] = GetMessage("MAIL_MSG_ADM_PROC_ACT_DELETE");
$arActions["refilter"] = GetMessage("MAIL_MSG_ADM_PROC_ACT_RULES");

$res = CMailFilter::GetList(Array("NAME"=>"ASC"), Array("ACTIVE"=>"Y", "WHEN_MANUALLY_RUN"=>"Y"));
while($flt_arr = $res->Fetch())
	$arActions["refilter_num_".$flt_arr["ID"]] = GetMessage("MAIL_MSG_ADM_PROC_ACT_RULE")." ".htmlspecialcharsbx(substr($flt_arr["NAME"], 0, 30));

if ($MOD_RIGHT=="W")
	$lAdmin->AddGroupActionTable($arActions);

ob_start();
?>
<form action="mail_check_new_messages.php" method="get">
<table cellspacing="0">
	<tr>
		<td style="padding-left:5px;"><?echo GetMessage("MAIL_MSG_ADM_GETMAIL")?></td>
		<td style="padding-left:5px;">
			<select name="mailbox_id" class="form-select">
				<option value=""><?echo GetMessage("MAIL_MSG_ADM_ALLMAILBOXES")?></option>
				<?
				ClearVars("mb_");
				$l = CMailbox::GetList(Array("NAME"=>"ASC", "ID"=>"ASC"), Array("VISIBLE"=>"Y"));
				while($l->ExtractFields("mb_")):
					?><option value="<?echo $mb_ID?>"<?if($find_mailbox_id==$mb_ID)echo " selected"?>><?echo $mb_NAME?></option><?
				endwhile;
				?>
			</select>
		</td>
		<td style="padding-left:3px; padding-right:3px;">
			<input class="form-button" type="submit" name="make_action" value="<?echo GetMessage("MAIL_MSG_ADM_OK")?>">
			<input type="hidden" name="lang" value="<?echo LANG?>">
			<?echo bitrix_sessid_post();?>
		</td>
	</tr>
</table>
</form>
<?
$s = ob_get_contents();
ob_end_clean();

$aContext = array(array("HTML"=>$s));
$lAdmin->AddAdminContextMenu($aContext);

$lAdmin->CheckListMode();

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
//$mailmessages = CMailMessage::GetList(Array($by=>$order), $arFilter);
//$is_filtered = $mailmessages->is_filtered;
?>

<form name="form1" method="GET" action="<?echo $APPLICATION->GetCurPage()?>?">
<?$filter->Begin();?>
<tr>
	<td nowrap><?echo GetMessage("MAIL_MSG_ADM_FILTER_ANYWHERE")?>:</td>
	<td nowrap><input type="text" name="find_all" value="<?echo htmlspecialcharsbx($find_all)?>" size="47"><?=ShowFilterLogicHelp()?></td>
</tr>

<tr>
	<td nowrap>ID:</td>
	<td nowrap><input type="text" name="find_id" value="<?echo htmlspecialcharsbx($find_id)?>" size="47"><?=ShowFilterLogicHelp()?></td>
</tr>
<tr>
	<td nowrap><?echo GetMessage("MAIL_MSG_ADM_MAILBOX")?>:</td>
	<td nowrap>
		<select name="find_mailbox_id">
			<option value=""><?echo GetMessage("MAIL_MSG_ADM_ANY")?></option>
			<?
			ClearVars("mb_");
			$l = CMailbox::GetList(Array("NAME"=>"ASC", "ID"=>"ASC"), Array("VISIBLE"=>"Y"));
			while($l->ExtractFields("mb_")):
				?><option value="<?echo $mb_ID?>"<?if($find_mailbox_id==$mb_ID)echo " selected"?>><?echo $mb_NAME?></option><?
			endwhile;
			?>
		</select>
		</td>
</tr>
<tr>
	<td nowrap><?echo GetMessage("MAIL_MSG_ADM_MARKEDSPAM")?>:</td>
	<td nowrap>
		<select name="find_spam">
			<option value=""><?echo GetMessage("MAIL_MSG_ADM_FILTER_ANY")?></option>
			<option value="Y"<?if($find_spam=="Y")echo " selected"?>><?echo GetMessage("MAIL_MSG_ADM_FILTER_YES")?></option>
			<option value="~Y"<?if($find_spam=="~Y")echo " selected"?>><?echo GetMessage("MAIL_MSG_ADM_FILTER_NO")?></option>
		</select>
		</td>
</tr>
<tr>
	<td nowrap><?echo GetMessage("MAIL_MSG_ADM_FILTER_READ")?>:</td>
	<td nowrap>
		<select name="find_new">
			<option value=""><?echo GetMessage("MAIL_MSG_ADM_FILTER_ANY")?></option>
			<option value="Y"<?if($find_new=="Y")echo " selected"?>><?echo GetMessage("MAIL_MSG_ADM_FILTER_NEW")?></option>
			<option value="~Y"<?if($find_new=="~Y")echo " selected"?>><?echo GetMessage("MAIL_MSG_ADM_FILTER_OLD")?></option>
		</select>
		</td>
</tr>
<tr>
	<td nowrap><?echo GetMessage("MAIL_MSG_ADM_FILTER_FROM")?>:</td>
	<td nowrap><input type="text" name="find_from" value="<?echo htmlspecialcharsbx($find_from)?>" size="47"><?=ShowFilterLogicHelp()?></td>
</tr>
<tr>
	<td nowrap><?echo GetMessage("MAIL_MSG_ADM_FILTER_TO")?>:</td>
	<td nowrap><input type="text" name="find_to" value="<?echo htmlspecialcharsbx($find_to)?>" size="47"><?=ShowFilterLogicHelp()?></td>
</tr>
<tr>
	<td nowrap><?echo GetMessage("MAIL_MSG_ADM_FILTER_SUBJECT")?>:</td>
	<td nowrap><input type="text" name="find_subject" value="<?echo htmlspecialcharsbx($find_subject)?>" size="47"><?=ShowFilterLogicHelp()?></td>
</tr>
<tr>
	<td nowrap><?echo GetMessage("MAIL_MSG_ADM_FILTER_TEXT")?>:</td>
	<td nowrap><input type="text" name="find_body" value="<?echo htmlspecialcharsbx($find_body)?>" size="47"><?=ShowFilterLogicHelp()?></td>
</tr>
<?$filter->Buttons(array("table_id"=>$sTableID, "url"=>$APPLICATION->GetCurPage(), "form"=>"form1"));$filter->End();?>
</form>

<?
$lAdmin->DisplayList();
?>

<?echo BeginNote();?>
<table border="0" cellspacing="3" cellpadding="0">
	<tr>
		<td><div class="mail-message-spam"></div></td>
		<td><?echo GetMessage("MAIL_MSG_ADM_READ_SPAM")?></td>
	</tr>
	<tr>
		<td><div class="mail-message-unread-spam"></div></td>
		<td><?echo GetMessage("MAIL_MSG_ADM_NOTREAD_SPAM")?></td>
	</tr>
	<tr>
		<td><div class="mail-message-notspam"></div></td>
		<td><?echo GetMessage("MAIL_MSG_ADM_READ_NOTSPAM")?></td>
	</tr>
	<tr>
		<td><div class="mail-message-unread-notspam"></div></td>
		<td><?echo GetMessage("MAIL_MSG_ADM_NOTREAD_NOTSPAM")?></td>
	</tr>
	<tr>
		<td><div class="mail-message"></div></td>
		<td><?echo GetMessage("MAIL_MSG_ADM_READ")?></td>
	</tr>
	<tr>
		<td><div class="mail-message-unread"></div></td>
		<td><?echo GetMessage("MAIL_MSG_ADM_NOTREAD")?></td>
	</tr>
</table>
<?echo EndNote();?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>
