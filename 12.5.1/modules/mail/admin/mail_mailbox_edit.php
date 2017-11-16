<?
/*
##############################################
# Bitrix: SiteManager                        #
# Copyright (c) 2002-2005 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:admin@bitrixsoft.com                #
##############################################
*/
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/mail/prolog.php");

ClearVars();

$message = null;
$MOD_RIGHT = $APPLICATION->GetGroupRight("mail");
if($MOD_RIGHT<"R") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
include(GetLangFileName($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/mail/lang/", "/admin/mail_mailbox_edit.php"));

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mail/include.php");

$err_mess = "File: ".__FILE__."<br>Line: ";

$strError="";
$ID = intval($ID);

$bCanUseTLS = (defined('BX_MAIL_FORCE_USE_TLS') && BX_MAIL_FORCE_USE_TLS === true) || function_exists('openssl_open');

if($REQUEST_METHOD=="POST" && (strlen($save)>0 || strlen($save_ext)>0 || strlen($apply)>0) && $MOD_RIGHT=="W" && check_bitrix_sessid())
{
	$arFields = Array(
		"ACTIVE"		=> $ACTIVE,
		"LID"			=> $LID,
		"NAME"			=> $NAME,
		"SERVER"		=> $SERVER,
		"PORT"			=> $PORT,
		"RELAY"			=> $RELAY,
		"AUTH_RELAY"	=> $AUTH_RELAY,
		"DOMAINS"		=> $DOMAINS,
		"SERVER_TYPE"	=> $SERVER_TYPE,
		"LOGIN"			=> $LOGIN,
		"PASSWORD"		=> $PASSWORD,
		"CHARSET"		=> $CHARSET,
		"USE_MD5"		=> $USE_MD5,
		"DELETE_MESSAGES"=>$DELETE_MESSAGES,
		"PERIOD_CHECK"	=> $PERIOD_CHECK,
		"DESCRIPTION"	=> $DESCRIPTION,
		"MAX_MSG_COUNT"	=> $MAX_MSG_COUNT,
		"MAX_MSG_SIZE"	=> $MAX_MSG_SIZE*1024,
		"MAX_KEEP_DAYS"	=> $MAX_KEEP_DAYS,
		"USE_TLS"		=> $bCanUseTLS ? $USE_TLS : 'N'
		);

	if($ID>0)
		$res = CMailbox::Update($ID, $arFields);
	else
	{
		$ID = CMailbox::Add($arFields);
		$res = ($ID>0);
	}

	if(!$res)
	{
		if($e = $APPLICATION->GetException())
			$message = new CAdminMessage(GetMessage("MAIL_MBOX_EDT_ERROR"), $e);
	}
	else
	{
		//$strError .= CMailError::GetErrorsText();
		//if (strlen($strError)<=0)
		//{
			if(strlen($save_ext)>0 && $filter_type!="")
				LocalRedirect("mail_filter_edit.php?lang=".LANG."&filter_type=".$filter_type."&find_mailbox_id=".$ID);
			elseif(strlen($save)>0)
				LocalRedirect("mail_mailbox_admin.php?lang=".LANG);
			else
				LocalRedirect($APPLICATION->GetCurPage()."?lang=".LANG."&ID=".$ID);
		//}
	}

}

$str_ACTIVE="Y";
$str_PORT="110";
$str_AUTH_RELAY="Y";
$str_RELAY="Y";
$mb = CMailbox::GetByID($ID);
if(!$mb->ExtractFields("str_"))
	$ID=0;

if ($message)
	$DB->InitTableVarsForEdit("b_mail_mailbox", "", "str_");

$sDocTitle = ($ID>0) ? preg_replace("'#ID#'i", $ID, GetMessage("MAIL_MBOX_EDT_TITLE_1")) : GetMessage("MAIL_MBOX_EDT_TITLE_2");
$APPLICATION->SetTitle($sDocTitle);

/***************************************************************************
							   HTML форма
****************************************************************************/

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");
$aMenu = array(
	array(
		"ICON" => "btn_list",
		"TEXT"=>GetMessage("MAIL_MBOX_EDT_BACK_LINK"),
		"LINK"=>"mail_mailbox_admin.php?lang=".LANG
	)
);

if($ID>0)
{
	$aMenu[] = array("SEPARATOR"=>"Y");
	$aMenu[] = array(
		"ICON" => "btn_new",
		"TEXT"=>GetMessage("MAIL_MBOX_EDT_NEW"),
		"LINK"=>"mail_mailbox_edit.php?lang=".LANG
	);

	if ($MOD_RIGHT=="W")
	{
		$aMenu[] = array(
			"TEXT"=>GetMessage("MAIL_MBOX_EDT_DEL"),
			"ICON" => "btn_delete",
			"LINK"=>"javascript:if(confirm('".GetMessage("MAIL_MBOX_EDT_DEL_CONFIRM")."'))window.location='mail_mailbox_admin.php?action=delete&ID=".$ID."&lang=".LANG."&".bitrix_sessid_get()."';",
		);
	}
}
//echo ShowSubMenu($aMenu);

$context = new CAdminContextMenu($aMenu);
$context->Show();

$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("MAIL_MBOX_EDT_TAB"), "ICON"=>"mail_mailbox_edit", "TITLE"=>$sDocTitle),
	array("DIV" => "edit2", "TAB" => GetMessage("MAIL_MBOX_EDT_TAB2"), "ICON"=>"mail_mailbox_edit", "TITLE"=>$sDocTitle),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);


?>

<?
if ($message)
	echo $message->Show();
?>
<form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?lang=<?=LANG?>&ID=<?=$ID?>" name="form1">
<?=bitrix_sessid_post()?>
<?echo GetFilterHiddens("find_");?>

<?$tabControl->Begin();?>
<?$tabControl->BeginNextTab();?>
	<?if($ID>0):?>
	<tr valign="top">
		<td><?echo GetMessage("MAIL_MBOX_EDT_ID")?></td>
		<td><?echo $str_ID?></td>
	</tr>
	<?endif?>
	<?if(strlen($str_TIMESTAMP_X)>0):?>
	<tr>
		<td><?echo GetMessage("MAIL_MBOX_EDT_DATECH")?></td>
		<td><?echo $str_TIMESTAMP_X?></td>
	</tr>
	<? endif; ?>
	<tr>
		<td width="40%"><?echo GetMessage("MAIL_MBOX_EDT_LANG")?> </td>
		<td width="60%">
		<?$l = CLang::GetList($lby="sort", $lorder="asc");?>
		<select name="LID">
		<?
			while($l->ExtractFields("l_")):
				?><option value="<?echo $l_LID?>"<?if($str_LID==$l_LID)echo " selected"?>><?echo $l_NAME?></option><?
			endwhile;
		?>
		</select>
		</td>
	</tr>
	<tr>
		<td><?echo GetMessage("MAIL_MBOX_EDT_ACT")?></td>
		<td><input type="checkbox" name="ACTIVE" value="Y"<?if($str_ACTIVE=="Y")echo " checked"?>></td>
	</tr>
	<tr class="adm-detail-required-field">
		<td><?echo GetMessage("MAIL_MBOX_EDT_NAME")?></td>
		<td><input type="text" name="NAME" size="53" maxlength="255" value="<?=$str_NAME?>"></td>
	</tr>
	<tr>
		<td class="adm-detail-valign-top"><?echo GetMessage("MAIL_MBOX_EDT_DESC")?></td>
		<td><textarea name="DESCRIPTION" cols="40" rows="5"><?echo $str_DESCRIPTION?></textarea>
		</td>
	</tr>
	<script type="text/javascript">
	function change_type()
	{
		var i, d, pop = (document.getElementById('SERVER_TYPE').selectedIndex==0);
		if(pop)
		{
			if(document.getElementById('PORT_PORT').value=='25')
				change_port();
		}
		else
		{
			if(document.getElementById('PORT_PORT').value=='110' || document.getElementById('PORT_PORT').value=='995')
				document.getElementById('PORT_PORT').value = '25';
		}

		for(i=0; i<10; i++)
		{
			d = document.getElementById('smtp'+i);
			if(!d)
				break;
			if(!pop)
				d.style.display = '';
			else
				d.style.display = 'none';
		}

		for(i=0; i<10; i++)
		{
			d = document.getElementById('pop'+i);
			if(!d)
				break;
			if(pop)
				d.style.display = '';
			else
				d.style.display = 'none';
		}
	}
	setTimeout(change_type, 0);
	</script>
	<tr>
		<td class="adm-detail-valign-top"><?echo GetMessage("MAIL_MBOX_SERVER_TYPE")?></td>
		<td>
			<select onchange="change_type()" name="SERVER_TYPE" id="SERVER_TYPE">
				<option value="pop7"><?echo GetMessage("MAIL_MBOX_SERVER_TYPE_POP3")?></option>
				<option value="smtp"<?if($str_SERVER_TYPE=="smtp")echo " selected"?>><?echo GetMessage("MAIL_MBOX_SERVER_TYPE_SMTP")?></option>
			</select><br>
			<div id="pop0"><?echo GetMessage("MAIL_MBOX_SERVER_TYPE_POP3_DESC")?></div>
			<div id="smtp0"><?echo GetMessage("MAIL_MBOX_SERVER_TYPE_SMTP_DESC")?><br><?echo GetMessage("MAIL_MBOX_SERVER_TYPE_SMTP_A")?></div>
		</td>
	</tr>
	<tr class="adm-detail-valign-top adm-detail-required-field">
		<td>
			<div id="pop7"><?echo GetMessage("MAIL_MBOX_EDT_SERVER")?></div>
			<div id="smtp3"><?echo GetMessage("MAIL_MBOX_SERVER_HOST")?><br><?echo GetMessage("MAIL_MBOX_SERVER_HOST_AST")?></div>
			</td>
		<td><input type="text" name="SERVER" size="42" maxlength="255" value="<?=$str_SERVER?>">:<input type="text" id="PORT_PORT" name="PORT" size="4" maxlength="5" value="<?=$str_PORT?>"></td>
	</tr>
<script type="text/javascript">
function chdom()
{
	document.getElementById('s2').disabled = (document.getElementById('DOMAINS').value.length<=0);
	document.getElementById('s3').disabled = (document.getElementById('DOMAINS').value.length<=0);
	document.getElementById('s4').disabled = (document.getElementById('DOMAINS').value.length<=0);
	document.getElementById('s5').disabled = (document.getElementById('DOMAINS').value.length<=0);
	chrelay();
}

function chrelay()
{
	document.getElementById('s3').disabled = (!document.getElementById('s2').checked || document.getElementById('s2').disabled);
	document.getElementById('s5').disabled = (!document.getElementById('s2').checked || document.getElementById('s2').disabled);
}
setTimeout(chdom, 0);
</script>

	<tr id="smtp1">
		<td class="adm-detail-valign-top"><?echo GetMessage("MAIL_MBOX_DOM")?><br> <?echo GetMessage("MAIL_MBOX_DOM_EMPTY")?></td>
		<td><textarea id="DOMAINS" name="DOMAINS" onkeyup="chdom()" cols="40" rows="4" onchange="chdom()"><?=$str_DOMAINS?></textarea></td>
	</tr>
	<tr id="smtp2">
		<td></td>
		<td>
			<input type="hidden" name="RELAY" value="N"><input type="hidden" name="AUTH_RELAY" value="N">
			<input type="checkbox" id="s2" name="RELAY" onclick="chrelay()" value="Y"<?if($str_RELAY=="Y")echo " checked"?>> <label for="s2" id="s4"><?echo GetMessage("MAIL_MBOX_RELAY")?></label><br>
			&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" id="s3" name="AUTH_RELAY" value="Y"<?if($str_AUTH_RELAY=="Y")echo " checked"?>> <label for="s3" id="s5"><?echo GetMessage("MAIL_MBOX_RELAY_AUTH")?></label><br>
		<td>
	</tr>


<script type="text/javascript">

function change_port()
{
	var t=document.getElementById('USE_TLS');
	document.getElementById('PORT_PORT').value = t.checked ? '995' : '110';
}

</script>

	<tr id="pop1">
		<td><?echo GetMessage("MAIL_MBOX_EDT_USE_TLS")?><span class="required"><sup>1</sup></span></td>
		<td><input type="checkbox" name="USE_TLS" id="USE_TLS" value="Y"<?if($str_USE_TLS=="Y")echo " checked"?> onclick="change_port();"<?if (!$bCanUseTLS){?> disabled<?}?>></td>
	</tr>
	<tr id="pop2">
		<td class="adm-detail-required-field"><?echo GetMessage("MAIL_MBOX_EDT_LOGIN")?></td>
		<td><input type="text" name="LOGIN" size="53" maxlength="255" value="<?=$str_LOGIN?>"></td>
	</tr>
	<tr id="pop3">
		<td class="adm-detail-required-field"><?echo GetMessage("MAIL_MBOX_EDT_PASSWORD")?></td>
		<td><input type="password" name="PASSWORD" size="53" maxlength="255" value="<?=($MOD_RIGHT>="W"?$str_PASSWORD:"")?>"></td>
	</tr>
	<tr id="pop4">
		<td><?echo GetMessage("MAIL_MBOX_EDT_USE_APOP")?></td>
		<td><input type="checkbox" name="USE_MD5" value="Y"<?if($str_USE_MD5=="Y")echo " checked"?>></td>
	</tr>
	<tr id="pop5">
		<td><?echo GetMessage("MAIL_MBOX_EDT_DEL_AFTER_RETR")?></td>
		<td><input type="checkbox" name="DELETE_MESSAGES" value="Y"<?if($str_DELETE_MESSAGES=="Y")echo " checked"?>></td>
	</tr>
	<tr id="pop6">
		<td><?echo GetMessage("MAIL_MBOX_EDT_PERIOD")?></td>
		<td><input type="text" name="PERIOD_CHECK" size="5" maxlength="18" value="<?echo $str_PERIOD_CHECK?>"> <?echo GetMessage("MAIL_MBOX_EDT_PERIOD_MIN")?></td>
	</tr>
	
<?if($ID<=0):?>

	<tr class="heading">
		<td align="center" colspan="2"><?echo GetMessage("MAIL_MBOX_EDT_ADD_NEW_RULE")?></td>
	</tr>
	<tr>
		<td align="center" colspan="2" class="adm-detail-valign-top">
			<select name="filter_type">
				<option value="manual"<?if($filter_type==$a_ID)echo ' manual'?>><?echo GetMessage("MAIL_MBOX_EDT_ADD_NEW_RULE_MANUAL")?></option>
				<?
				$res = CMailFilter::GetFilterList();
				while($ar = $res->ExtractFields("a_"))
				{
				?><option value="<?=$a_ID?>"<?if($filter_type==$a_ID)echo ' selected'?>><?=$a_NAME?></option><?
				}
				?>
			</select>&nbsp;<input type="submit" <?if ($MOD_RIGHT<"W") echo "disabled" ?> name="save_ext" value="<?echo GetMessage("MAIL_MBOX_EDT_ADD")?>">
		</td>
	</tr>

<?endif?>

<?$tabControl->BeginNextTab();?>

	<?
	if(($db_max_allowed = $DB->Query("SHOW VARIABLES LIKE 'MAX_ALLOWED_PACKET'", true))!==false && ($ar_max_allowed = $db_max_allowed->Fetch())!==false && IntVal($ar_max_allowed["Value"])>0):
		$B_MAIL_MAX_ALLOWED = IntVal($ar_max_allowed["Value"]);
		?>
			<tr>
				<td><?echo GetMessage("MAIL_MBOX_EDT_MAX_ALLOWED")?></td>
				<td><?echo $B_MAIL_MAX_ALLOWED/1024?> <?echo GetMessage("MAIL_MBOX_EDT_MAX_ALLOWED_KB")?></td>
			</tr>
			<?
	endif;
	?>

	<tr>
		<td width="40%"><?echo GetMessage("MAIL_MBOX_EDT_MAX_MSGS")?></td>
		<td width="60%"><input type="text" name="MAX_MSG_COUNT" size="5" maxlength="18" value="<?echo $str_MAX_MSG_COUNT?>"> <?echo GetMessage("MAIL_MBOX_EDT_MAX_MSGS_CNT")?></td>
	</tr>

	<tr id="pop8">
		<td><?echo GetMessage("MAIL_MBOX_EDT_MAX_SIZE")?></td>
		<td><input type="text" name="MAX_MSG_SIZE" size="5" maxlength="18" value="<?echo intval($str_MAX_MSG_SIZE/1024)?>"> <?echo GetMessage("MAIL_MBOX_EDT_MAX_SIZE_KB")?></td>
	</tr>


	<tr>
		<td><?echo GetMessage("MAIL_MBOX_EDT_KEEP_DAYS")?></td>
		<td><input type="text" name="MAX_KEEP_DAYS" size="5" maxlength="18" value="<?echo intval($str_MAX_KEEP_DAYS)?>"> <?echo GetMessage("MAIL_MBOX_EDT_KEEP_DAYS_D")?></td>
	</tr>

	<tr>
		<td class="adm-detail-valign-top"><?echo GetMessage("MAIL_MBOX_EDT_CHARSET")?><br><?echo GetMessage("MAIL_MBOX_EDT_CHARSET_RECOMND")?></td>
		<td>
<?
$chs = Array(
	"utf-8",
	"iso-8859-1",
	"iso-8859-2",
	"iso-8859-3",
	"iso-8859-4",
	"iso-8859-5",
	"iso-8859-6",
	"iso-8859-7",
	"iso-8859-8",
	"iso-8859-9",
	"iso-8859-10",
	"iso-8859-13",
	"iso-8859-14",
	"iso-8859-15",
	"windows-1251",
	"windows-1252",
	"cp866",
	"koi8-r"
);
?>
			<select onchange="BX('CHARSET').value = this.value">
				<option value=""></option>
				<option value=""<?if($str_CHARSET=="")echo ' selected'?>><?echo GetMessage("MAIL_MBOX_EDT_CHARSET_RECOMND_TEXT")?></option>
				<?foreach($chs as $ch):?>
					<option value="<?=$ch?>"<?if(strtolower($ch) == strtolower($str_CHARSET))echo ' selected'?>><?=$ch?></option>
				<?endforeach?>
			</select>
            <input type="text" name="CHARSET" id="CHARSET" size="12" maxlength="255" value="<?=$str_CHARSET?>">

		</td>
	</tr>


<input type="hidden" value="Y" name="apply">
</div>


<?$tabControl->EndTab();?>
<?$tabControl->Buttons(Array("disabled"=>$MOD_RIGHT<"W","back_url" =>"mail_mailbox_admin.php?lang=".LANG));?>
<?$tabControl->End();?>
</form>
<?$tabControl->ShowWarnings("form1", $message);?>


<?echo BeginNote();?>
<span class="required"><sup>1</sup></span> <?=GetMessage('MAIL_MBOX_EDT_COMMENT1')?>
<?echo EndNote();?>
<?require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");?>
