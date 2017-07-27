<?
define("ADMIN_MODULE_NAME", "sender");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

if(!\Bitrix\Main\Loader::includeModule("sender"))
	ShowError(\Bitrix\Main\Localization\Loc::getMessage("MAIN_MODULE_NOT_INSTALLED"));

IncludeModuleLangFile(__FILE__);

$POST_RIGHT = $APPLICATION->GetGroupRight("sender");
if($POST_RIGHT=="D")
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$isUserHavePhpAccess = $USER->CanDoOperation('edit_php');


$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("sender_chain_edit_tab_main"), "ICON"=>"main_user_edit", "TITLE"=>GetMessage("sender_chain_edit_tab_main_title")),
	array("DIV" => "edit2", "TAB" => GetMessage("sender_chain_edit_tab_message"), "ICON"=>"main_user_edit", "TITLE"=>GetMessage("sender_chain_edit_tab_message_title")),
	array("DIV" => "edit3", "TAB" => GetMessage("sender_chain_edit_tab_send"), "ICON"=>"main_user_edit", "TITLE"=>GetMessage("sender_chain_edit_tab_send_title")),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);

$ID = intval($ID);		// Id of the edited record
$MAILING_ID = intval($MAILING_ID);
$message = null;
$bVarsFromForm = false;

if($_SERVER['REQUEST_METHOD'] == "POST" && ($save!="" || $apply!="") && $POST_RIGHT=="W" && check_bitrix_sessid())
{
	$arError = array();
	$DAYS_OF_WEEK = empty($DAYS_OF_WEEK) ? '' : implode(',',$DAYS_OF_WEEK);
	if(!$isUserHavePhpAccess)
	{
		$MESSAGE_OLD = false;
		if($ID>0)
		{
			$mailingChainOld = \Bitrix\Sender\MailingChainTable::getRowById(array('ID' => $ID));
			if($mailingChainOld)
			{
				$MESSAGE_OLD = $mailingChainOld['MESSAGE'];
			}
		}

		$MESSAGE = CMain::ProcessLPA($MESSAGE, $MESSAGE_OLD);
	}


	$arFields = Array(
		"MAILING_ID" => $MAILING_ID,
		"SUBJECT" => $SUBJECT,
		"EMAIL_FROM"	=> $EMAIL_FROM,
		"MESSAGE" => $MESSAGE,
		"CREATED_BY" => $USER->GetID(),

		"REITERATE" => "N",
		"AUTO_SEND_TIME" => "",
		"DAYS_OF_WEEK" => "",
		"DAYS_OF_MONTH" => "",
		"TIMES_OF_DAY" => "",
	);

	if(empty($MESSAGE) && isset($IS_TEMPLATE_LIST_SHOWN) && $IS_TEMPLATE_LIST_SHOWN=='Y')
	{
		$arError[] = GetMessage("sender_chain_edit_error_select_template");
	}

	switch($SEND_TYPE)
	{
		case 'MANUAL':
			break;
		case 'TIME':
			if(empty($AUTO_SEND_TIME))
				$arError[] = GetMessage("sender_chain_edit_error_empty_time");

			if(!\Bitrix\Main\Type\DateTime::isCorrect($AUTO_SEND_TIME))
				$arError[] = GetMessage("sender_chain_edit_error_time_format");
			else
				$arFields["AUTO_SEND_TIME"] = new \Bitrix\Main\Type\DateTime($AUTO_SEND_TIME);

			if ($ID <= 0)
			{
				$arFields["STATUS"] = \Bitrix\Sender\MailingChainTable::STATUS_SEND;
			}
			else
			{
				$arMailingChainOld = \Bitrix\Sender\MailingChainTable::getRowById(array('ID' => $ID));
				if($arMailingChainOld['STATUS'] == \Bitrix\Sender\MailingChainTable::STATUS_NEW)
					$arFields["STATUS"] = \Bitrix\Sender\MailingChainTable::STATUS_SEND;
			}
			break;
		case 'REITERATE':
			if(empty($DAYS_OF_WEEK) && empty($DAYS_OF_MONTH))
				$arError[] = GetMessage("sender_chain_edit_error_reiterate");

			$arFields["REITERATE"] = "Y";
			$arFields["DAYS_OF_MONTH"] = $DAYS_OF_MONTH;
			$arFields["DAYS_OF_WEEK"] = $DAYS_OF_WEEK;
			$arFields["TIMES_OF_DAY"] = $TIMES_OF_DAY;
			if ($ID <= 0)
			{
				$arFields["STATUS"] = \Bitrix\Sender\MailingChainTable::STATUS_WAIT;
			}
			else
			{
				$arMailingChainOld = \Bitrix\Sender\MailingChainTable::getRowById(array('ID' => $ID));
				if($arMailingChainOld['STATUS'] == \Bitrix\Sender\MailingChainTable::STATUS_NEW)
					$arFields["STATUS"] = \Bitrix\Sender\MailingChainTable::STATUS_SEND;
			}
			break;
		default:
			$arError[] = GetMessage("sender_chain_edit_error_send_type");
	}


	if(empty($arError))
	{
		$res = false;
		if ($ID > 0)
		{
			unset($arFields['CREATED_BY']);
			unset($arFields['MAILING_ID']);
			$mailingUpdateDb = \Bitrix\Sender\MailingChainTable::update(array('ID' => $ID), $arFields);
			$res = $mailingUpdateDb->isSuccess();
			if(!$res)
				$arError = $mailingUpdateDb->getErrorMessages();
		}
		elseif ($MAILING_ID > 0)
		{
			$mailingAddDb = \Bitrix\Sender\MailingChainTable::add($arFields);
			if ($mailingAddDb->isSuccess())
			{
				$ID = $mailingAddDb->getId();
				\Bitrix\Sender\MailingChainTable::initPosting($ID);
				$res = ($ID > 0);
			}
			else
			{
				$arError = $mailingAddDb->getErrorMessages();
			}
		}
	}

	if($res)
	{
		// save email to list of emails from
		\Bitrix\Sender\MailingChainTable::setEmailFromToList($EMAIL_FROM);

		// save template body to my templates
		if(isset($TEMPLATE_ACTION_SAVE) && $TEMPLATE_ACTION_SAVE == 'Y')
		{
			if(!empty($TEMPLATE_ACTION_SAVE_NAME) && !empty($MESSAGE))
			{
				\Bitrix\Sender\TemplateTable::add(array(
					'NAME' => $TEMPLATE_ACTION_SAVE_NAME,
					'CONTENT' => $MESSAGE
				));
			}
		}

		if($apply!="")
			LocalRedirect("/bitrix/admin/sender_mailing_chain_edit.php?MAILING_ID=".$MAILING_ID."&ID=".$ID."&lang=".LANG."&".$tabControl->ActiveTabParam());
		else
			LocalRedirect("/bitrix/admin/sender_mailing_chain_admin.php?MAILING_ID=".$MAILING_ID."&lang=".LANG);
	}
	else
	{
		if($e = $APPLICATION->GetException())
			$arError[] = GetMessage("rub_save_error");

		if(!empty($arError))
			$message = new CAdminMessage(implode('<br>', $arError));
		$bVarsFromForm = true;
	}

}


//Edit/Add part
ClearVars();
$str_SORT = 100;
$str_ACTIVE = "Y";
$str_VISIBLE = "Y";

if($ID>0)
{
	$rubric = new CDBResult(\Bitrix\Sender\MailingChainTable::getList(array(
		'select' => array('*'),
		'filter' => array('ID' => $ID)
	)));
	if(!$rubric->ExtractFields("str_"))
		$ID=0;

	if($ID>0)
	{
		$postingDb = \Bitrix\Sender\PostingTable::getList(array(
			'select' => array('*'),
			'filter' => array('ID' => $ID, '!DATE_SENT' => null),
			'order' => array('DATE_SENT' => 'DESC'),
			'limit' => 1
		));
		$arPosting = $postingDb->fetch();
		$str_DATE_SENT = $arPosting['DATE_SENT'];
	}
}

if($bVarsFromForm)
	$DB->InitTableVarsForEdit("b_sender_mailing_chain", "", "str_");

\CJSCore::Init(array("sender_admin"));
$APPLICATION->SetTitle(($ID>0? GetMessage("sender_chain_edit_title_edit").$ID : GetMessage("sender_chain_edit_title_add")));
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$aMenu = array(
	array(
		"TEXT"=>GetMessage("sender_chain_edit_list"),
		"TITLE"=>GetMessage("sender_chain_edit_list_title"),
		"LINK"=>"/bitrix/admin/sender_mailing_chain_admin.php?MAILING_ID=".$MAILING_ID."&lang=".LANG,
		"ICON"=>"btn_list",
	)
);
if($ID>0 && $POST_RIGHT>="W")
{
	$aMenu[] = array("SEPARATOR"=>"Y");
	$aMenu[] = array(
		"TEXT"=>GetMessage("sender_chain_edit_action_add"),
		"TITLE"=>GetMessage("sender_chain_edit_action_add_title"),
		"LINK"=>"/bitrix/admin/sender_mailing_chain_edit.php?MAILING_ID=".$MAILING_ID."&lang=".LANG,
		"ICON"=>"btn_new",
	);
	$aMenu[] = array(
		"TEXT"=>GetMessage("sender_chain_edit_action_del"),
		"TITLE"=>GetMessage("sender_chain_edit_action_del_title"),
		"LINK"=>"javascript:if(confirm('".GetMessage("sender_chain_edit_action_del_confirm")."'))window.location='/bitrix/admin/sender_mailing_chain_admin.php?MAILING_ID=".$MAILING_ID."&ID=".$ID."&action=delete&lang=".LANGUAGE_ID."&".bitrix_sessid_get()."';",
		"ICON"=>"btn_delete",
	);
	$aMenu[] = array("SEPARATOR"=>"Y");
}
$context = new CAdminContextMenu($aMenu);
$context->Show();

$arMailing = \Bitrix\Sender\MailingTable::getRowById($MAILING_ID);
?>

<?
if($_REQUEST["mess"] == "ok" && $ID>0)
	CAdminMessage::ShowMessage(array("MESSAGE"=>GetMessage("sender_chain_edit_saved"), "TYPE"=>"OK"));

if($message)
	echo $message->Show();


if(!isset($SEND_TYPE))
{
	if ($str_REITERATE == 'Y')
		$SEND_TYPE = 'REITERATE';
	elseif (!empty($str_AUTO_SEND_TIME))
		$SEND_TYPE = 'TIME';
	elseif ($ID > 0)
		$SEND_TYPE = 'MANUAL';
}

$templateListHtml = \Bitrix\Sender\Preset\Template::getTemplateListHtml();
?>


	<script>
		function SendTestMailing()
		{
			var data = {
				'action': 'send_to_me',
				'send_to_me_addr': BX('EMAIL_TO_ME').value
			};
			var url = '/bitrix/admin/sender_mailing_chain_admin.php?MAILING_ID=<?echo $MAILING_ID?>&ID=<?echo $ID?>&lang=<?echo LANGUAGE_ID?>&<?echo bitrix_sessid_get()?>&action=js_send';
			ShowWaitWindow();
			BX.ajax.post(
				url,
				data,
				function(result){
					CloseWaitWindow();
					document.getElementById('test_mailing_cont').innerHTML = result;
				}
			);
		}


		BX.message({"SENDER_SHOW_TEMPLATE_LIST" : "<?=GetMessage('SENDER_SHOW_TEMPLATE_LIST')?>"});
		function ShowTemplateList()
		{
			if(confirm(BX.message("SENDER_SHOW_TEMPLATE_LIST")))
			{
				ChangeTemplateList('BASE');
				var tmplTypeContList = BX.findChildren(BX('tabControl_layout'), {'className': 'hidden-when-show-template-list'}, true);
				for (i in tmplTypeContList)
					tmplTypeContList[i].style.display = 'none';

				tmplTypeContList = BX.findChildren(BX('tabControl_layout'), {'className': 'show-when-show-template-list'}, true);
				for (i in tmplTypeContList)
					tmplTypeContList[i].style.display = 'table-row';

				BX('IS_TEMPLATE_LIST_SHOWN').value = 'Y';
			}
		}
	</script>

<form method="POST" Action="<?echo $APPLICATION->GetCurPage()?>?MAILING_ID=<?=$MAILING_ID?>&ID=<?=$ID?>" name="post_form">
<?
$tabControl->Begin();
?>
<?
$tabControl->BeginNextTab();
?>
	<tr>
		<td colspan="2">
			<div class="adm-info-message"><?=GetMessage("sender_chain_edit_maintext");?></div>
		</td>
	</tr>
	<tr class="heading">
		<td colspan="2"><?=GetMessage("sender_chain_edit_state");?></td>
	</tr>
	<tr class="adm-detail-required-field">
		<td><?echo GetMessage("sender_chain_edit_field_status")?></td>
		<td>
			<div class="sender-mailing-status">
				<span class="sender-mailing-sprite sender-mailing-status-img sender-mailing-status-img-<?=(strtolower($str_STATUS))?>"></span>
				<span class="sender-mailing-status-text sender-mailing-status-text-<?=(strtolower($str_STATUS))?>"" >
					<span>
						<?
						if(!empty($str_STATUS))
						{
							$arStatus = \Bitrix\Sender\MailingChainTable::getStatusList();
							echo $arStatus[$str_STATUS];
						}
						else
						{
							echo GetMessage("sender_chain_edit_field_status_def");
						}
						?>
					</span>
					<?if(!empty($str_DATE_SENT) && in_array($str_STATUS, array(\Bitrix\Sender\MailingChainTable::STATUS_END))):?>
						<span class="sender-mailing-status-text-date"><?=$str_DATE_SENT?></span>
					<?endif;?>
					<?if(!empty($str_CREATED_BY)):?>
						<span class="sender-mailing-status-creator">
							<?=GetMessage("sender_chain_edit_field_author")?> <?$arUser = \Bitrix\Main\UserTable::getRowById($str_CREATED_BY);echo htmlspecialcharsbx($arUser['NAME'].' '.$arUser['LAST_NAME']);?>
						</span>
					<?endif;?>
				</span>
				<span>
					<?if($ID>0 && $POST_RIGHT>="W" && \Bitrix\Sender\MailingChainTable::isReadyToSend($ID)):?>
						<input style="margin-left: 80px;" type="button"
							value="<?echo GetMessage("sender_chain_edit_btn_send")?>"
							onclick="window.location='/bitrix/admin/sender_mailing_chain_admin.php?MAILING_ID=<?=$MAILING_ID?>&ID=<?=$ID?>&action=send&lang=<?=LANGUAGE_ID?>'"
							title="<?echo GetMessage("sender_chain_edit_btn_send_desc")?>" />
					<?endif;?>
					<?if($ID>0 && $POST_RIGHT>="W" && \Bitrix\Sender\MailingChainTable::isManualSentPartly($ID)):?>
						<input style="margin-left: 80px;" type="button"
							value="<?echo GetMessage("sender_chain_edit_btn_send2")?>"
							onclick="window.location='/bitrix/admin/sender_mailing_chain_admin.php?MAILING_ID=<?=$MAILING_ID?>&ID=<?=$ID?>&action=send&lang=<?=LANGUAGE_ID?>'"
							title="<?echo GetMessage("sender_chain_edit_btn_send2_desc")?>" />
					<?endif;?>
				</span>
			</div>
		</td>
	</tr>
	<?if($ID>0 || $ID<=0):?>
	<tr class="adm-detail-required-field">
		<td colspan="2">
			<br>
			<?
			$arEmailFromList = \Bitrix\Sender\MailingChainTable::getEmailToMeList();
			if(!in_array($USER->GetEmail(), $arEmailFromList))
				$arEmailFromList[] = $USER->GetEmail();
			?>
			<table class="sender-test-send">
				<tr>
					<td class="sender-test-send-header"><span class="sender-mailing-sprite sender-test-send-header-img"></span></td>
					<td class="sender-test-send-header">
						<span><?echo GetMessage("sender_chain_edit_test_title")?></span>
						<br>
						<span class="sender-test-send-header-grey"><?=GetMessage("sender_chain_edit_test_desc")?></span>
					</td>
				</tr>
				<tr>
					<td></td>
					<td class="sender-test-caption"><span><?echo GetMessage("sender_chain_edit_test_field_recipient")?></span></td>
				</tr>
				<tr>
					<td></td>
					<td>
						<input type="text" id="EMAIL_TO_ME" name="EMAIL_TO_ME"
							value=""
							placeholder="<?=GetMessage("sender_chain_edit_test_field_recipient_desc")?>"
							style="width: 600px;"
							>
					</td>
				</tr>
				<tr>
					<td class="sender-test-recent"></td>
					<td class="sender-test-recent"> <?=GetMessage("sender_chain_edit_test_last_recipient")?>
						<?foreach($arEmailFromList as $email):?>
							<a class="sender-link-email"
								onclick="AddAddressToControl('EMAIL_TO_ME', '<?=CUtil::AddSlashes(htmlspecialcharsbx($email))?>')"
								ondblclick="DeleteAddressFromControl('EMAIL_TO_ME', '<?=CUtil::AddSlashes(htmlspecialcharsbx($email))?>')"
								>
								<?=htmlspecialcharsbx($email)?>
							</a><?=(end($arEmailFromList)==$email ? '' : ',')?>
						<?endforeach?>
					</td>
				</tr>
				<tr>
					<td></td>
					<td>
						<input type="button" value="<?=GetMessage("sender_chain_edit_test_btn")?>" onclick="SendTestMailing();" <?=($POST_RIGHT>="W" ? "" : "disabled")?>>
					</td>
				</tr>
				<tr>
					<td></td>
					<td>
						<div id="test_mailing_cont"></div>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<?endif;?>

<?
$tabControl->BeginNextTab();
?>
	<?if(!empty($templateListHtml)):?>
	<tr class="adm-detail-required-field  show-when-show-template-list" <?=(!empty($str_MESSAGE) ? 'style="display: none;"' : '')?>>
		<td colspan="2">
			<?=$templateListHtml;?>
		</td>
	</tr>
	<tr class="hidden-when-show-template-list" <?=(empty($str_MESSAGE) ? 'style="display: none;"' : '')?>>
		<td><?echo GetMessage("sender_chain_edit_field_sel_templ")?></td>
		<td>
			<span id="TEMPLATE_SELECTED_TITILE"></span> <a class="sender-link-email" href="javascript: void(0);" onclick="ShowTemplateList();"><?echo GetMessage("sender_chain_edit_field_sel_templ_another")?></a>
		</td>
	</tr>
	<tr class="hidden-when-show-template-list"><td colspan="2">&nbsp;</td></tr>
	<?endif;?>
	<tr class="adm-detail-required-field hidden-when-show-template-list" <?=(empty($str_MESSAGE) ? 'style="display: none;"' : '')?>>
		<td><?echo GetMessage("sender_chain_edit_field_subject")?></td>
		<td>
			<input type="text" id="SUBJECT" name="SUBJECT" value="<?=$str_SUBJECT?>" style="width: 450px;">
		</td>
	</tr>

	<tr class="hidden-when-show-template-list" <?=(empty($str_MESSAGE) ? 'style="display: none;"' : '')?>>
		<td>&nbsp;</td>
		<td>
			<?
			$arPersonalizeList = \Bitrix\Sender\PostingRecipientTable::getPersonalizeList();
			?>
			<?echo GetMessage("sender_chain_edit_field_subject_personalize")?>
			<?foreach($arPersonalizeList as $arPersonalize):?>
			<a class="sender-link-email" onclick="SetAddressToControl('SUBJECT', ' #<?=htmlspecialcharsbx($arPersonalize['CODE'])?>#', true)" title="<?=htmlspecialcharsbx($arPersonalize['DESC'])?>">
				<?=htmlspecialcharsbx($arPersonalize['NAME'])?>
				</a><?=(end($arPersonalizeList)===$arPersonalize ? '' : ',')?>
			<?endforeach?>
			<span style="cursor: pointer;" class="hidden-when-show-template-list-info" onclick="BX.PopupWindowManager.create('sender_personalize_help', this, {'darkMode': false, 'closeIcon': true, 'content': '<div style=\'margin: 7px;\'><?=GetMessage('sender_chain_edit_pers_help')?></div>'}).show();">&nbsp;</span>
		</td>
	</tr>

	<tr class="hidden-when-show-template-list"><td colspan="2">&nbsp;</td></tr>

	<tr class="adm-detail-required-field hidden-when-show-template-list" <?=(empty($str_MESSAGE) ? 'style="display: none;"' : '')?>>
		<td><?echo GetMessage("sender_chain_edit_field_email_from")?></td>
		<td>
			<input type="text" id="EMAIL_FROM" name="EMAIL_FROM" value="<?=$str_EMAIL_FROM?>">
		</td>
	</tr>

	<tr class="hidden-when-show-template-list" <?=(empty($str_MESSAGE) ? 'style="display: none;"' : '')?>>
		<td>&nbsp;</td>
		<td>
			<?
			$arEmailFromList = \Bitrix\Sender\MailingChainTable::getEmailFromList();
			?>
			<?echo GetMessage("sender_chain_edit_field_email_from_last")?>
			<?foreach($arEmailFromList as $email):?>
			<a class="sender-link-email" onclick="SetAddressToControl('EMAIL_FROM', '<?=CUtil::AddSlashes(htmlspecialcharsbx($email))?>')">
				<?=htmlspecialcharsbx($email)?>
				</a><?=(end($arEmailFromList)==$email ? '' : ',')?>
			<?endforeach?>
		</td>
	</tr>

	<tr class="hidden-when-show-template-list" <?=(empty($str_MESSAGE) ? 'style="display: none;"' : '')?>>
		<td colspan="2">&nbsp;</td>
	</tr>

	<tr class="adm-detail-required-field hidden-when-show-template-list" <?=(empty($str_MESSAGE) ? 'style="display: none;"' : '')?>>
		<td colspan="2" align="left">
			<b><?=GetMessage("sender_chain_edit_field_message")?></b>
			<?=\Bitrix\Sender\TemplateTable::initEditor(array('FIELD_VALUE' => $str_MESSAGE, 'HAVE_USER_ACCESS' => $isUserHavePhpAccess));?>
			<input type="hidden" name="IS_TEMPLATE_LIST_SHOWN" id="IS_TEMPLATE_LIST_SHOWN" value="<?=(empty($str_MESSAGE) ?"Y":"N")?>">
		</td>
	</tr>
<?
$tabControl->BeginNextTab();
?>
	<tr>
		<td colspan="2">
			<p class="adm-white-container-p"><?=GetMessage("sender_chain_edit_field_send_type_desc");?></p>
		</td>
	</tr>
	<tr>
		<td colspan="2">

			<input type="hidden" name="SEND_TYPE" id="SEND_TYPE" value="<?=htmlspecialcharsbx($SEND_TYPE)?>">
			<?
				$arSendType = array(
					'MANUAL' => GetMessage('sender_chain_edit_field_send_type_MANUAL'),
					'TIME' => GetMessage('sender_chain_edit_field_send_type_TIME'),
					'REITERATE' => GetMessage('sender_chain_edit_field_send_type_REITERATE'),
				);
			?>
			<?if($ID<=0 || $str_STATUS == \Bitrix\Sender\MailingChainTable::STATUS_NEW):?>
			<div class="sender-box-selector">
				<div class="sender-box-selector-caption"><?=GetMessage('sender_chain_edit_field_send_type_selector')?></div>
				<div class="sender-box-selector-control">
					<select id="chain_send_type" name="chain_send_type" <?=(!empty($SEND_TYPE)?'disabled':'')?>>
						<?foreach($arSendType as $sendTypeCode => $sendTypeName):?>
							<option value="<?=$sendTypeCode?>" <?=($sendTypeCode==$SEND_TYPE ? 'selected' : '')?>>
								<?=htmlspecialcharsbx($sendTypeName)?>
							</option>
						<?endforeach?>
					</select> &nbsp; <input id="sender_wizard_chain_send_type_btn" type="button" class="adm-btn-green adm-btn-add" value="<?=GetMessage('sender_chain_edit_field_send_type_button')?>" onclick="SetSendType();" <?=(!empty($SEND_TYPE)?'disabled':'')?>>
				</div>
			</div>
			<?endif;?>
			<div class="sender-box-list" id="chain_send_type_list_container">

				<div id="chain_send_type_NONE" class="sender-box-list-item" <?=($SEND_TYPE=='NONE'?'':'style="display: none;"')?>>
					<div class="sender-box-list-item-block">
						<div class="sender-box-list-item-block-item">
							<span><?=GetMessage('sender_chain_edit_field_send_type_EMPTY')?></span>
						</div>
					</div>
				</div>

				<div id="chain_send_type_MANUAL" class="sender-box-list-item" <?=($SEND_TYPE=='MANUAL'?'':'style="display: none;"')?>>
					<div class="sender-box-list-item-caption">
						<div>
							<span class="sender-box-list-item-caption-image" style="opacity: 0; width: 0px;"></span>
							<span class="sender-box-list-item-caption-name"><?=GetMessage('sender_chain_edit_field_send_type_MANUAL')?></span>
							<?if($ID<=0 || $str_STATUS == \Bitrix\Sender\MailingChainTable::STATUS_NEW):?>
								<span class="sender-mailing-sprite sender-box-list-item-caption-delete" onclick="DeleteSelectedSendType(this);"></span>
							<?endif;?>
						</div>
					</div>
					<div class="sender-box-list-item-block">
						<div class="sender-box-list-item-block-item">
							<span><?=GetMessage('sender_chain_edit_field_send_type_MANUAL_desc')?></span>
						</div>
					</div>
				</div>
				<div id="chain_send_type_TIME" class="sender-box-list-item" <?=($SEND_TYPE=='TIME'?'':'style="display: none;"')?>>
					<div class="sender-box-list-item-caption">
						<div>
							<span class="sender-box-list-item-caption-image" style="opacity: 0; width: 0px;"></span>
							<span class="sender-box-list-item-caption-name"><?=GetMessage('sender_chain_edit_field_send_type_TIME')?></span>
							<?if($ID<=0 || $str_STATUS == \Bitrix\Sender\MailingChainTable::STATUS_NEW):?>
								<span class="sender-mailing-sprite sender-box-list-item-caption-delete" onclick="DeleteSelectedSendType(this);"></span>
							<?endif;?>
						</div>
					</div>
					<div class="sender-box-list-item-block">
						<div class="sender-box-list-item-block-item">
							<table>
							<tr>
								<td><?=GetMessage('sender_chain_edit_field_AUTO_SEND_TIME')?></td>
								<td>
									<?if($ID>0 && $str_STATUS == \Bitrix\Sender\MailingChainTable::STATUS_END):?>
										<?=$str_AUTO_SEND_TIME?>
										<input type="hidden" name="AUTO_SEND_TIME" value="<?=$str_AUTO_SEND_TIME?>">
									<?else:?>
										<?echo CalendarDate("AUTO_SEND_TIME", $str_AUTO_SEND_TIME, "post_form", "20")?>
									<?endif;?>
								</td>
							</table>
						</div>
					</div>
				</div>
				<div id="chain_send_type_REITERATE" class="sender-box-list-item" <?=($SEND_TYPE=='REITERATE'?'':'style="display: none;"')?>>
					<div class="sender-box-list-item-caption">
						<div>
							<span class="sender-box-list-item-caption-image" style="opacity: 0; width: 0px;"></span>
							<span class="sender-box-list-item-caption-name"><?=GetMessage('sender_chain_edit_field_send_type_REITERATE')?></span>
							<?if($ID<=0 || $str_STATUS == \Bitrix\Sender\MailingChainTable::STATUS_NEW):?>
								<span class="sender-mailing-sprite sender-box-list-item-caption-delete" onclick="DeleteSelectedSendType(this);"></span>
							<?endif;?>
						</div>
					</div>
					<div class="sender-box-list-item-block">
						<div class="sender-box-list-item-block-item">
							<table>
							<tr>
								<td><?echo GetMessage("rub_dom")?></td>
								<td><input class="typeinput" type="text" name="DAYS_OF_MONTH" value="<?echo $str_DAYS_OF_MONTH;?>" size="30" maxlength="100"></td>
							</tr>
							<tr>
								<td class="adm-detail-valign-top"><?echo GetMessage("rub_dow")?></td>
								<td>
									<table cellspacing=1 cellpadding=0 border=0 class="internal">
										<?	$arDoW = array(
											"1"	=> GetMessage("rubric_mon"),
											"2"	=> GetMessage("rubric_tue"),
											"3"	=> GetMessage("rubric_wed"),
											"4"	=> GetMessage("rubric_thu"),
											"5"	=> GetMessage("rubric_fri"),
											"6"	=> GetMessage("rubric_sat"),
											"7"	=> GetMessage("rubric_sun")
										);
										?>
										<tr class="heading"><?foreach($arDoW as $strVal=>$strDoW):?>
												<td><?=$strDoW?></td>
											<?endforeach;?>
										</tr>
										<tr>
											<?foreach($arDoW as $strVal=>$strDoW):?>
												<td style="text-align:center"><input type="checkbox" name="DAYS_OF_WEEK[]" value="<?=$strVal?>"<?if(array_search($strVal, explode(',',$str_DAYS_OF_WEEK)) !== false) echo " checked"?>></td>
											<?endforeach;?>
										</tr>
									</table>
								</td>
							</tr>
							<tr class="adm-detail-required-field">
								<td><?echo GetMessage("rub_tod")?></td>
								<td>
									<select name="TIMES_OF_DAY">
										<?
										$timesOfDayHours = array('00', '30');
										for($hour=0; $hour<24; $hour++):
											$hourPrint = str_pad($hour, 2, "0", STR_PAD_LEFT);
											foreach($timesOfDayHours as $timePartHour):
												$hourFullPrint = $hourPrint.":".$timePartHour;
												?>
												<option value="<?=$hourFullPrint?>" <?=($hourFullPrint==$str_TIMES_OF_DAY ? 'selected': '')?>><?=$hourFullPrint?></option>
												<?
											endforeach;
										endfor;?>
									</select>
								</td>
							</tr>
							</table>
						</div>
					</div>
				</div>
			</div>
		</td>
	</tr>

<?

$tabControl->Buttons(array(
	"disabled"=>($POST_RIGHT<"W"),
	"back_url"=>"/bitrix/admin/sender_mailing_chain_admin.php?MAILING_ID=".$MAILING_ID."&lang=".LANG,
));
?>

<?echo bitrix_sessid_post();?>
<input type="hidden" name="lang" value="<?=LANG?>">
<?
$tabControl->End();
?>

<?
$tabControl->ShowWarnings("post_form", $message);
?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>