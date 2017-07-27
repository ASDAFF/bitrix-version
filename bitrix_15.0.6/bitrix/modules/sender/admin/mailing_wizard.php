<?
define("ADMIN_MODULE_NAME", "sender");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

use \Bitrix\Sender\MailingChainTable;
use \Bitrix\Sender\PostingTable;
use \Bitrix\Sender\PostingRecipientTable;

if(!\Bitrix\Main\Loader::includeModule("sender"))
	ShowError(\Bitrix\Main\Localization\Loc::getMessage("MAIN_MODULE_NOT_INSTALLED"));

IncludeModuleLangFile(__FILE__);

$POST_RIGHT = $APPLICATION->GetGroupRight("sender");
if($POST_RIGHT<"W")
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
	$message = new CAdminMessage(GetMessage("sender_wizard_access_denied"));
	$APPLICATION->SetTitle(GetMessage("sender_wizard_title"));
	echo $message->Show();
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
}

$APPLICATION->SetTitle(GetMessage("sender_wizard_title"));

$MAILING_ID = intval($_REQUEST['MAILING_ID']);
$MAILING_CHAIN_ID = intval($_REQUEST['MAILING_CHAIN_ID']);
$arError = array();
$isPostedFormProcessed = false;
if(empty($step))
	$step='mailing';
if(empty($ACTIVE) || $ACTIVE!='Y')
	$ACTIVE = 'N';


if($step=='mailing')
{
	IncludeModuleLangFile(dirname(__FILE__)."/mailing_edit.php");
	if($REQUEST_METHOD == "POST" && !$isPostedFormProcessed && check_bitrix_sessid())
	{
		$NAME = trim($NAME);
		if($MAILING_TYPE == 'NEW')
		{
			$arFields = Array(
				"ACTIVE"	=> ($ACTIVE <> "Y"? "N":"Y"),
				"TRACK_CLICK"	=> ($TRACK_CLICK <> "Y"? "N":"Y"),
				"NAME"		=> $NAME,
				"DESCRIPTION"	=> $DESCRIPTION,
				"SITE_ID" => $SITE_ID,
			);


			$mailingAddDb = \Bitrix\Sender\MailingTable::add($arFields);
			if($mailingAddDb->isSuccess())
			{
				$MAILING_ID = $mailingAddDb->getId();
			}
			else
			{
				$arError = $mailingAddDb->getErrorMessages();
			}
		}
		else
		{
			$mailing = \Bitrix\Sender\MailingTable::getRowById($MAILING_ID);
			if(!$mailing)
				$arError[] = GetMessage("sender_wizard_step_mailing_existed_not_selected");
		}

		if(empty($arError))
		{
			if($MAILING_TYPE == 'NEW')
				$step = 'mailing_group';
			else
				$step = 'chain';

			$isPostedFormProcessed = true;

			LocalRedirect('sender_mailing_wizard.php?step='.$step.'&MAILING_ID='.$MAILING_ID."&lang=".LANGUAGE_ID);
		}
		else
		{
			$DB->InitTableVarsForEdit("b_sender_mailing", "", "str_");
		}
	}
	else
	{
		$str_ACTIVE = 'Y';
	}

	$arMailingList = array();
	$groupDb = \Bitrix\Sender\MailingTable::getList(array(
		'select' => array('NAME', 'ID'),
		'order' => array('NAME' => 'ASC'),
	));
	while($arMailing = $groupDb->fetch())
	{
		$arMailingList[] = $arMailing;
	}

	if(empty($arMailingList)) $MAILING_TYPE = 'NEW';
}

if($step=='group')
{
	IncludeModuleLangFile(dirname(__FILE__)."/group_edit.php");
	if(!isset($group_create) && $REQUEST_METHOD == "POST" && !$isPostedFormProcessed && check_bitrix_sessid())
	{
		$res = false;
		$NAME = trim($NAME);
		if(isset($popup_create_group) && $popup_create_group == 'Y')
		{
			require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");
			$NAME = $APPLICATION->ConvertCharset($NAME, "UTF-8", LANG_CHARSET);
			$DESCRIPTION = $APPLICATION->ConvertCharset($DESCRIPTION, "UTF-8", LANG_CHARSET);
		}

		$arFields = Array(
			"ACTIVE"	=> ($ACTIVE <> "Y"? "N":"Y"),
			"NAME"		=> $NAME,
			"SORT"		=> $SORT,
			"DESCRIPTION"	=> $DESCRIPTION,
		);
		$groupAddDb = \Bitrix\Sender\GroupTable::add($arFields);
		if($groupAddDb->isSuccess())
		{
			$ID = $groupAddDb->getId();
			$res = ($ID > 0);
		}
		else
		{
			$arError = $groupAddDb->getErrorMessages();
		}

		if($res)
		{
			if(is_array($CONNECTOR_SETTING))
			{
				$groupConnectorsDataCount = 0;
				\Bitrix\Sender\GroupConnectorTable::delete(array('GROUP_ID' => $ID));
				$arEndpointList = \Bitrix\Sender\ConnectorManager::getEndpointFromFields($CONNECTOR_SETTING);
				foreach ($arEndpointList as $endpoint)
				{
					$connector = \Bitrix\Sender\ConnectorManager::getConnector($endpoint);
					if ($connector)
					{
						$connector->setFieldValues($endpoint['FIELDS']);
						$connectorDataCount = $connector->getDataCount();
						$arGroupConnectorAdd = array(
							'GROUP_ID' => $ID,
							'NAME' => $connector->getName(),
							'ENDPOINT' => $endpoint,
							'ADDRESS_COUNT' => $connectorDataCount
						);

						$groupConnectorAddDb = \Bitrix\Sender\GroupConnectorTable::add($arGroupConnectorAdd);
						if($groupConnectorAddDb->isSuccess())
						{
							$groupConnectorsDataCount += $connectorDataCount;
						}
					}
				}
				\Bitrix\Sender\GroupTable::update($ID, array('ADDRESS_COUNT' => $groupConnectorsDataCount));
			}
		}

		if(empty($arError))
		{
			$step = 'mailing_group';
			$isPostedFormProcessed = true;

			if(isset($popup_create_group) && $popup_create_group == 'Y')
			{
				?>
				<script type="text/javascript">
					top.BX.WindowManager.Get().Close();
					top.location.reload();
				</script>
				<?
				require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");
				exit();
			}
			else
			{
				LocalRedirect('sender_mailing_wizard.php?step='.$step.'&MAILING_ID='.$MAILING_ID."&lang=".LANGUAGE_ID);
			}
		}
		else
		{
			$DB->InitTableVarsForEdit("b_sender_group", "", "str_");
		}
	}
	else
	{
		$str_ACTIVE = 'Y';
		$str_SORT = 100;
	}


	if(isset($CONNECTOR_SETTING))
		$arConnectorSettings = $CONNECTOR_SETTING;
	else
		$arConnectorSettings = array();


	if(count($endpointList)>0)
	{
		$arConnectorSettings = \Bitrix\Sender\ConnectorManager::getFieldsFromEndpoint($endpointList);
	}

	$arAvailableConnectors = array();
	$arExistedConnectors = array();
	$arConnector = \Bitrix\Sender\ConnectorManager::getConnectorList();
	/** @var \Bitrix\Sender\Connector $connector */
	foreach($arConnector as $connector)
	{
		if(array_key_exists($connector->getModuleId(), $arConnectorSettings))
			$arFieldsValues = $arConnectorSettings[$connector->getModuleId()][$connector->getCode()];
		else
			$arFieldsValues = array();

		$connector->setFieldPrefix('CONNECTOR_SETTING');
		$connectorIdCount = 0;

		$arAvailableConnectors[$connector->getId()] = array(
			'ID' => $connector->getId(),
			'NAME' => $connector->getName(),
			'FORM' => $connector->getForm()
		);

		if( array_key_exists($connector->getModuleId(), $arConnectorSettings) )
		{
			if( array_key_exists($connector->getCode(), $arConnectorSettings[$connector->getModuleId()]) )
			{
				$connectorIdCount = 0;
				$arFieldsValuesConnector = $arConnectorSettings[$connector->getModuleId()][$connector->getCode()];
				foreach($arFieldsValuesConnector as $fieldValues)
				{
					$connector->setFieldFormName('post_form');
					$connector->setFieldValues($fieldValues);
					$arExistedConnectors[] = array(
						'ID' => $connector->getId(),
						'NAME' => $connector->getName(),
						'FORM' => str_replace('%CONNECTOR_NUM%', $connectorIdCount, $connector->getForm()),
						'COUNT' => $connector->getDataCount()
					);

					$connectorIdCount++;
				}
			}
		}
	}
}


if($step=='mailing_group')
{
	IncludeModuleLangFile(dirname(__FILE__)."/mailing_edit.php");
	if($REQUEST_METHOD == "POST" && !$isPostedFormProcessed && check_bitrix_sessid())
	{
		$ID = $MAILING_ID;
		$GROUP = array();
		if(isset($GROUP_INCLUDE))
		{
			$GROUP_INCLUDE = explode(',', $GROUP_INCLUDE);
			trimArr($GROUP_INCLUDE);
		}
		else
			$GROUP_INCLUDE = array();

		if(isset($GROUP_EXCLUDE))
		{
			$GROUP_EXCLUDE = explode(',', $GROUP_EXCLUDE);
			trimArr($GROUP_EXCLUDE);
		}
		else
			$GROUP_EXCLUDE = array();

		if($MAILING_ID>0)
		{
			foreach($GROUP_INCLUDE as $groupId)
			{
				if (is_numeric($groupId))
				{
					$GROUP[] = array('MAILING_ID' => $ID, 'GROUP_ID' => $groupId, 'INCLUDE' => true);
				}
			}

			foreach($GROUP_EXCLUDE as $groupId)
			{
				if (is_numeric($groupId))
				{
					$GROUP[] = array('MAILING_ID' => $ID, 'GROUP_ID' => $groupId, 'INCLUDE' => false);
				}
			}

			\Bitrix\Sender\MailingGroupTable::delete(array('MAILING_ID' => $ID));
			foreach($GROUP as $arGroup)
			{
				\Bitrix\Sender\MailingGroupTable::add($arGroup);
			}
		}

		if(empty($arError))
		{
			$step = 'chain';
			$isPostedFormProcessed = true;
			LocalRedirect('sender_mailing_wizard.php?step='.$step.'&MAILING_ID='.$MAILING_ID."&lang=".LANGUAGE_ID);
		}
	}
	else
	{
		$ID = $MAILING_ID;

		$GROUP_EXCLUDE = $GROUP_INCLUDE = array();
		$groupDb = \Bitrix\Sender\MailingGroupTable::getList(array(
			'select' => array('ID' => 'GROUP_ID', 'INCLUDE'),
			'filter' => array('MAILING_ID' => $ID),
		));
		while($arGroup = $groupDb->fetch())
		{
			if($arGroup['INCLUDE'])
				$GROUP_INCLUDE[] = $arGroup['ID'];
			else
				$GROUP_EXCLUDE[] = $arGroup['ID'];
		}
	}

	$GROUP_EXIST = array();
	$groupDb = \Bitrix\Sender\GroupTable::getList(array(
		'select' => array('NAME', 'ID', 'ADDRESS_COUNT'),
		'filter' => array('ACTIVE' => 'Y'),
		'order' => array('SORT' => 'ASC', 'NAME' => 'ASC'),
	));
	while($arGroup = $groupDb->fetch())
	{
		$GROUP_EXIST[] = $arGroup;
	}
}

if($step=='chain')
{
	IncludeModuleLangFile(dirname(__FILE__)."/mailing_chain_edit.php");

	$isUserHavePhpAccess = $USER->CanDoOperation('edit_php');

	if($REQUEST_METHOD == "POST" && !$isPostedFormProcessed && check_bitrix_sessid())
	{
		if($MAILING_CHAIN_ID <= 0)
		{
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

			if(empty($arError))
			{
				$mailingAddDb = \Bitrix\Sender\MailingChainTable::add($arFields);
				if ($mailingAddDb->isSuccess())
				{
					$ID = $mailingAddDb->getId();
					\Bitrix\Sender\MailingChainTable::initPosting($ID);
					$res = ($ID > 0);
					$MAILING_CHAIN_ID = $ID;
				}
				else
				{
					$arError = $mailingAddDb->getErrorMessages();
				}
			}
		}
		if(empty($arError))
		{
			if($MAILING_CHAIN_ID > 0)
			{
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
			}

			$step = 'chain_send_type';
			$isPostedFormProcessed = true;
			LocalRedirect('sender_mailing_wizard.php?step='.$step.'&MAILING_ID='.$MAILING_ID."&MAILING_CHAIN_ID=".$MAILING_CHAIN_ID."&lang=".LANGUAGE_ID);
		}
		else
		{
			$DB->InitTableVarsForEdit("b_sender_mailing_chain", "", "str_");
		}
	}
	else
	{

	}

	$templateListHtml = \Bitrix\Sender\Preset\Template::getTemplateListHtml();
}

if($step=='chain_send_type')
{
	$ID = $MAILING_CHAIN_ID;
	IncludeModuleLangFile(dirname(__FILE__)."/mailing_chain_edit.php");
	$DAYS_OF_WEEK = empty($DAYS_OF_WEEK) ? '' : implode(',',$DAYS_OF_WEEK);
	if($REQUEST_METHOD == "POST" && !$isPostedFormProcessed && check_bitrix_sessid())
	{
		$arFields = Array(
			"REITERATE" => "N",
			"AUTO_SEND_TIME" => "",
			"DAYS_OF_WEEK" => "",
			"DAYS_OF_MONTH" => "",
			"TIMES_OF_DAY" => "",
		);

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
				$arFields["DAYS_OF_WEEK"] = $DAYS_OF_WEEK;
				$arFields["DAYS_OF_MONTH"] = $DAYS_OF_MONTH;
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
			$mailingUpdateDb = \Bitrix\Sender\MailingChainTable::update(array('ID' => $ID), $arFields);
			if ($mailingUpdateDb->isSuccess())
			{
				//\Bitrix\Sender\MailingChainTable::initPosting($ID);
			}
			else
			{
				$arError = $mailingUpdateDb->getErrorMessages();
			}
		}

		if(empty($arError))
		{
			LocalRedirect('sender_mailing_chain_edit.php?MAILING_ID='.$MAILING_ID.'&ID='.$ID.'&lang='.LANGUAGE_ID);
		}
		else
		{
			$DB->InitTableVarsForEdit("b_sender_mailing_chain", "", "str_");

			if(!isset($SEND_TYPE))
			{
				if ($str_REITERATE == 'Y')
					$SEND_TYPE = 'REITERATE';
				elseif (!empty($str_AUTO_SEND_TIME))
					$SEND_TYPE = 'TIME';
				elseif ($ID > 0)
					$SEND_TYPE = 'MANUAL';
			}
		}
	}
	else
	{

	}
}

if(!empty($arError))
	$message = new CAdminMessage(implode("<br>", $arError));

\CJSCore::Init(array("sender_admin"));
$title = GetMessage("sender_wizard_step_".$step."_title");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
?>
<div class="adm-email-master-container">
	<div class="adm-white-container">
		<div class="adm-email-master" id="sender_wizard_status">
			<div class="adm-email-master-step adm-email-master-step-addmail sender-step-mailing sender-step-passed-mailing_group sender-step-passed-group sender-step-passed-chain sender-step-passed-chain_send_type">
				<div class="adm-email-master-step-divider"></div>
				<div class="adm-email-master-step-icon"></div>
				<div class="adm-email-master-step-title"><?=GetMessage("sender_wizard_status_mailing")?></div>
			</div>
			<div class="adm-email-master-step adm-email-master-step-addgroup sender-step-mailing_group sender-step-group  sender-step-passed-chain sender-step-passed-chain_send_type">
				<div class="adm-email-master-step-divider"></div>
				<div class="adm-email-master-step-icon"></div>
				<div class="adm-email-master-step-title"><?=GetMessage("sender_wizard_status_group")?></div>
			</div>
			<div class="adm-email-master-step adm-email-master-step-addissue sender-step-chain sender-step-passed-chain_send_type">
				<div class="adm-email-master-step-divider"></div>
				<div class="adm-email-master-step-icon"></div>
				<div class="adm-email-master-step-title"><?=GetMessage("sender_wizard_status_chain")?></div>
			</div>
			<div class="adm-email-master-step adm-email-master-step-timingmail sender-step-chain_send_type">
				<div class="adm-email-master-step-divider"></div>
				<div class="adm-email-master-step-icon"></div>
				<div class="adm-email-master-step-title"><?=GetMessage("sender_wizard_status_chain_send_type")?></div>
			</div>
			<div class="adm-email-master-step adm-email-master-step-done">
				<div class="adm-email-master-step-divider"></div>
				<div class="adm-email-master-step-icon"></div>
				<div class="adm-email-master-step-title"><?=GetMessage("sender_wizard_status_final")?></div>
			</div>
		</div>
	</div>

<?
	if(isset($popup_create_group) && $popup_create_group == 'Y')
	{
		$APPLICATION->RestartBuffer();
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");
		?><div class="sender-wizard-popup"><?
	}
?>

<?
if(!empty($message))
{
	echo $message->show();
}
?>

<div class="adm-white-container">
	<?if(isset($popup_create_group) && $popup_create_group == 'Y'):?>
		<script>
			BX.WindowManager.Get().SetTitle('<?=htmlspecialcharsbx($title)?>');
		</script>
	<?else:?>
	<h2 class="adm-white-container-title"><?=htmlspecialcharsbx($title)?></h2>
	<?endif;?>
	<form name="post_form" method="post" action="<?=$APPLICATION->GetCurPage()?>?lang=<?=LANGUAGE_ID?>">
		<input type="hidden" id="step" name="step" value="<?=htmlspecialcharsbx($step)?>">
		<input type="hidden" name="MAILING_ID" value="<?=$MAILING_ID?>">
		<input type="hidden" name="MAILING_CHAIN_ID" value="<?=$MAILING_CHAIN_ID?>">
		<?=bitrix_sessid_post()?>
		<script>
			var senderWizardStatusList = BX.findChildren(BX('sender_wizard_status'), {'className': 'sender-step-<?=htmlspecialcharsbx($step)?>'}, true);
			for(var i in senderWizardStatusList)
				BX.addClass(senderWizardStatusList[i], 'active');

			var senderWizardStatusList = BX.findChildren(BX('sender_wizard_status'), {'className': 'sender-step-passed-<?=htmlspecialcharsbx($step)?>'}, true);
			for(var i in senderWizardStatusList)
				BX.addClass(senderWizardStatusList[i], 'passed');
		</script>
		<div>
	<?
	if($step=='mailing_group'):
	?>

		<?
		function ShowGroupControl($controlName, $controlValues, $controlSelectedValues)
		{
			?>
			<td>
				<select multiple style="width:350px; height:300px;" id="<?=$controlName?>_EXISTS" ondblclick="GroupManager(true, '<?=$controlName?>');">
					<?
					foreach($controlValues as $arGroup)
					{
						?><option value="<?=htmlspecialcharsbx($arGroup['ID'])?>"><?=htmlspecialcharsbx($arGroup['NAME'].' ('.$arGroup['ADDRESS_COUNT'].')')?></option><?
					}
					?>
				</select>
			</td>
			<td class="sender-mailing-group-block-sect-delim">
				<span class="adm-btn-input-container"  onClick="GroupManager(true, '<?=$controlName?>');">
					<input type="button" value="" class="adm-btn adm-btn-grey">
					<span></span>
				</span>
				<br>
				<span class="adm-btn-input-container left-input-container" onClick="GroupManager(false, '<?=$controlName?>');">
					<input type="button" value="" class="adm-btn adm-btn-grey">
					<span></span>
				</span>
			</td>
			<td>
				<select id="<?=$controlName?>" multiple="multiple" style="width:350px; height:300px;" ondblclick="GroupManager(false, '<?=$controlName?>');">
					<?
					$arGroupId = array();
					foreach($controlValues as $arGroup)
					{
						if(!in_array($arGroup['ID'], $controlSelectedValues))
							continue;

						$arGroupId[] = $arGroup['ID'];
						?><option value="<?=htmlspecialcharsbx($arGroup['ID'])?>"><?=htmlspecialcharsbx($arGroup['NAME'].' ('.$arGroup['ADDRESS_COUNT'].')')?></option><?
					}
					?>
				</select>
				<input type="hidden" name="<?=$controlName?>" id="<?=$controlName?>_HIDDEN" value="<?=implode(',', $arGroupId)?>">
			</td>
		<?
		}
		?>

		<!--
		<input name="group_create" type="button" value="<?=GetMessage("sender_wizard_step_mailing_group_field_bnt_add");?>" onclick="window.location='<?=$APPLICATION->GetCurPage().'?step=group'.'&MAILING_ID='.$MAILING_ID."&lang=".LANGUAGE_ID?>'" class="adm-btn adm-btn-save">
		-->
		<script>
			function SenderWizardShowDlgGroup()
			{
				var dlgParams ={
					'content_url':'sender_mailing_wizard.php?popup_create_group=Y&step=group&MAILING_ID=0&lang=ru',
					'content_post' : 'group_create=Y',
					'width':'800',
					'height':'600',
					'resizable':false
				};
				new BX.CAdminDialog(dlgParams).Show();
			}
		</script>

		<table class="adm-detail-content-table edit-table">
		<tr>
			<td style="text-align: left;"><p class="adm-detail-content-item-block-title"><?=GetMessage("sender_wizard_step_mailing_group_title_sub");?></p></td>
			<td style="text-align: right; vertical-align: middle">
				<input type="button"  value="<?=GetMessage("sender_wizard_step_mailing_group_field_bnt_add");?>" onclick="SenderWizardShowDlgGroup();" class="adm-btn-green adm-btn-add" name="group_create">
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<div class="sender-mailing-group-container sender-mailing-group-add">
					<span class="sender-mailing-group-container-title"><span><?=GetMessage("sender_mailing_edit_grp_add");?></span></span>
					<span class="adm-white-container-p"><span><?=GetMessage("sender_mailing_edit_grp_add_desc");?></span></span>
				</div>

			</td>
		</tr>
		<tr>
			<td colspan="2">
				<table class="sender-mailing-group">
					<tr>
						<td><span class="sender-mailing-group-block-all"><?=GetMessage("sender_mailing_edit_grp_all");?></td>
						<td class="sender-mailing-group-block-sect-delim"></td>
						<td><span class="sender-mailing-group-block-sel"><?=GetMessage("sender_mailing_edit_grp_sel");?></td>
					</tr>
					<tr>
						<?ShowGroupControl('GROUP_INCLUDE', $GROUP_EXIST, $GROUP_INCLUDE)?>
					</tr>
				</table>
			</td>
		</tr>
		<tr><td colspan="2">&nbsp;</td></tr>
		<tr>
			<td colspan="2">
				<div class="sender-mailing-group-container sender-mailing-group-del">
					<span class="sender-mailing-group-container-title"><span><?=GetMessage("sender_mailing_edit_grp_del");?></span></span>
					<span class="adm-white-container-p"><span><?=GetMessage("sender_mailing_edit_grp_del_desc");?></span></span>
				</div>

			</td>
		</tr>
		<tr>
			<td colspan="2">
				<table class="sender-mailing-group">
					<tr>
						<td><span class="sender-mailing-group-block-all"><?=GetMessage("sender_mailing_edit_grp_all");?></td>
						<td class="sender-mailing-group-block-sect-delim"></td>
						<td><span class="sender-mailing-group-block-sel"><?=GetMessage("sender_mailing_edit_grp_sel");?></td>
					</tr>
					<tr>
						<?ShowGroupControl('GROUP_EXCLUDE', $GROUP_EXIST, $GROUP_EXCLUDE)?>
					</tr>
				</table>
			</td>
		</tr>
		</table>

		<script type="text/template" id="connector-template">
			<?
			ob_start();
			?><div class="sender-box-list-item sender-box-list-item-hidden connector_form">
				<div class="sender-box-list-item-caption" onclick='ConnectorSettingShowToggle(this);'>
					<span class="sender-box-list-item-caption-image" ></span>
					<span class="sender-box-list-item-caption-name" >%CONNECTOR_NAME%</span>
					<span class="sender-mailing-sprite sender-box-list-item-caption-delete" onclick='ConnectorSettingDelete(this);'></span>
						<span class="sender-box-list-item-caption-additional">
							<span class="sender-box-list-item-caption-additional-less"><?=GetMessage('sender_group_conn_cnt')?>: </span>
							<span class="connector_form_counter">%CONNECTOR_COUNT%</span>
						</span>
				</div>
				<div class="sender-box-list-item-block connector_form_container">
					<div class="sender-box-list-item-block-item">%CONNECTOR_FORM%</div>
				</div>
			</div>
			<?
			$connectorTemplate = ob_get_clean();
			echo $connectorTemplate;
			?>
		</script>
	<?
	elseif($step=='group'):
	?>
		<table class="adm-detail-content-table edit-table">
			<tr>
				<td width="40%"><?echo GetMessage("sender_group_field_active")?></td>
				<td width="60%"><input type="checkbox" id="ACTIVE" name="ACTIVE" value="Y"<?if($str_ACTIVE == "Y") echo " checked"?>></td>
			</tr>
			<tr class="adm-detail-required-field">
				<td><?echo GetMessage("sender_group_field_name")?></td>
				<td><input type="text" name="NAME" value="<?echo $str_NAME;?>" size="45" maxlength="100"></td>
			</tr>
			<tr class="adm-detail-required-field">
				<td><?echo GetMessage("sender_group_field_sort")?></td>
				<td><input type="text" name="SORT" value="<?echo $str_SORT;?>" size="6"></td>
			</tr>
			<tr>
				<td class="adm-detail-valign-top"><?echo GetMessage("sender_group_field_desc")?></td>
				<td><textarea class="typearea" name="DESCRIPTION" cols="45" rows="5" wrap="VIRTUAL" style="width:100%"><?echo $str_DESCRIPTION; ?></textarea></td>
			</tr>


			<tr>
				<td colspan="2">
					<p class="sender-text-description-header">
						<?echo GetMessage("sender_group_conn_title")?>
					</p>
					<p class="sender-text-description-detail">
						<?echo GetMessage("sender_group_conn_desc")?>
					</p>
					<p class="sender-text-description-detail">
						<?echo GetMessage("sender_group_conn_desc_example")?>
					</p>
					<br/>
				</td>
			</tr>

			<tr class="adm-detail-required-field">
			<td colspan="2">

			<script>
				var connectorListToAdd = <?=CUtil::PhpToJSObject($arAvailableConnectors)?>;
				BX.ready(function(){
					ConnectorSettingWatch();
				});
			</script>


			<div class="sender-box-selector">
				<div class="sender-box-selector-control">
					<select id="connector_list_to_add">
						<?
						if(count($arAvailableConnectors)<=0)
						{
							echo GetMessage('sender_group_conn_not_availabe');
						}
						else
						{
							foreach ($arAvailableConnectors as $connectorId => $availableConnector)
							{
								?>
								<option value="<?= htmlspecialcharsbx($availableConnector['ID']) ?>">
									<?= htmlspecialcharsbx($availableConnector['NAME']) ?>
								</option>
							<?
							}
						}
						?>
					</select> &nbsp; <input type="button" value="<?=GetMessage('sender_group_conn_add')?>" onclick="addNewConnector();">
				</div>
			</div>
			<div id="connector_form_container" class="sender-box-list">
				<?
				$groupAddressCount = 0;
				foreach($arExistedConnectors as $existedConnector)
				{
					$existedConnectorTemplateValues = array(
						'%CONNECTOR_NAME%' => $existedConnector['NAME'],
						'%CONNECTOR_COUNT%' => $existedConnector['COUNT'],
						'%CONNECTOR_FORM%' => $existedConnector['FORM'],
					);
					echo str_replace(
						array_keys($existedConnectorTemplateValues),
						array_values($existedConnectorTemplateValues),
						$connectorTemplate
					);

					$groupAddressCount += $existedConnector['COUNT'];
				}
				?>
			</div>
			<div class="sender-group-address-counter">
				<span class="sender-mailing-sprite sender-group-address-counter-img"></span>
				<span class="sender-group-address-counter-text"><?=GetMessage('sender_group_conn_cnt_all')?></span>
				<span id="sender_group_address_counter" class="sender-group-address-counter-cnt"><?=$groupAddressCount?></span>
			</div>
			</td>
			</tr>
		</table>
		<?if(isset($popup_create_group) && $popup_create_group == 'Y'):?>
			<script type="text/javascript">
				BX.WindowManager.Get().SetButtons([BX.CDialog.prototype.btnSave, BX.CDialog.prototype.btnCancel]);
			</script>
		<?endif;?>
	<?
	elseif($step=='chain'):

		if(empty($templateListHtml) && empty($str_MESSAGE)) $str_MESSAGE = ' ';
	?>
		<script>
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
		<table class="adm-detail-content-table edit-table" id="tabControl_layout">
			<?if(!empty($templateListHtml)):?>
				<tr class="show-when-show-template-list" <?=(!empty($str_MESSAGE) ? 'style="display: none;"' : '')?>>
					<td colspan="2" align="left">
						<?=$templateListHtml;?>
					</td>
				</tr>
				<tr class="hidden-when-show-template-list" <?=(empty($str_MESSAGE) ? 'style="display: none;"' : '')?>>
					<td colspan="2">
						<p class="adm-white-container-subtitle"><?echo GetMessage("sender_wizard_step_chain_title_sub")?></p>
						<p class="adm-white-container-p"><?echo GetMessage("sender_wizard_step_chain_title_sub_desc")?></p>
					</td>
				</tr>
				<tr class="hidden-when-show-template-list" <?=(empty($str_MESSAGE) ? 'style="display: none;"' : '')?>>
					<td><?echo GetMessage("sender_chain_edit_field_sel_templ")?></td>
					<td>
						<span class="hidden-when-show-template-list-name" id="TEMPLATE_SELECTED_TITILE"></span> <a class="sender-link-email" href="javascript: void(0);" onclick="ShowTemplateList();"><?echo GetMessage("sender_chain_edit_field_sel_templ_another")?></a>
					</td>
				</tr>
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
					<span style="cursor: pointer;" class="hidden-when-show-template-list-info" onclick="BX.PopupWindowManager.create('sender_personalize_help', this, {'darkMode': false, 'closeIcon': true, 'content': '<div style=\'margin: 7px;\'><?=GetMessage('sender_chain_edit_pers_help')?></span>'}).show();">&nbsp;</div>
				</td>
			</tr>

			<tr class="adm-detail-required-field hidden-when-show-template-list" <?=(empty($str_MESSAGE) ? 'style="display: none;"' : '')?>>
				<td>
					<?echo GetMessage("sender_chain_edit_field_email_from")?>
					<br/>
					<span class="adm-fn"><?=GetMessage('sender_chain_edit_field_email_from_desc')?></span>
				</td>
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
					<div class="adm-detail-content-item-block">
						<span class="adm-detail-content-item-block-span"><?=GetMessage("sender_chain_edit_field_message")?></span>
						<?=\Bitrix\Sender\TemplateTable::initEditor(array('FIELD_VALUE' => $str_MESSAGE, 'HAVE_USER_ACCESS' => $isUserHavePhpAccess));?>
						<input type="hidden" name="IS_TEMPLATE_LIST_SHOWN" id="IS_TEMPLATE_LIST_SHOWN" value="<?=(empty($str_MESSAGE) ?"Y":"N")?>">
					</div>
				</td>
			</tr>
		</table>
	<?
	elseif($step=='chain_send_type'):
	?>
		<table class="adm-detail-content-table edit-table">
			<tr>
				<td colspan="2">
					<p class="adm-white-container-p"><?=GetMessage("sender_chain_edit_field_send_type_desc");?></p>
				</td>
			</tr>
			<tr>
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
					<?if($str_STATUS != \Bitrix\Sender\MailingChainTable::STATUS_SEND):?>
						<div class="sender-box-selector">
							<div class="sender-box-selector-caption"><?=GetMessage('sender_chain_edit_field_send_type_selector')?></div>
							<div class="sender-box-selector-control">
								<select id="chain_send_type" name="chain_send_type"  <?=(!empty($SEND_TYPE)?'disabled':'')?>>
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
								<?=GetMessage('sender_chain_edit_field_send_type_MANUAL')?>
								<span class="sender-mailing-sprite sender-box-list-item-caption-delete" onclick="DeleteSelectedSendType(this);" style="height: 20px; width: 23px; margin-top: -2px;"></span>
							</div>
							<div class="sender-box-list-item-block">
								<div class="sender-box-list-item-block-item">
									<span><?=GetMessage('sender_chain_edit_field_send_type_MANUAL_desc')?></span>
								</div>
							</div>
						</div>
						<div id="chain_send_type_TIME" class="sender-box-list-item" <?=($SEND_TYPE=='TIME'?'':'style="display: none;"')?>>
							<div class="sender-box-list-item-caption">
								<?=GetMessage('sender_chain_edit_field_send_type_TIME')?>
								<span class="sender-mailing-sprite sender-box-list-item-caption-delete" onclick="DeleteSelectedSendType(this);" style="height: 20px; width: 23px; margin-top: -2px;"></span>
							</div>
							<div class="sender-box-list-item-block">
								<div class="sender-box-list-item-block-item">
									<table>
										<tr>
											<td><?=GetMessage('sender_chain_edit_field_AUTO_SEND_TIME')?></td>
											<td>
												<?echo CalendarDate("AUTO_SEND_TIME", $str_AUTO_SEND_TIME, "post_form", "20")?>
											</td>
									</table>
								</div>
							</div>
						</div>
						<div id="chain_send_type_REITERATE" class="sender-box-list-item" <?=($SEND_TYPE=='REITERATE'?'':'style="display: none;"')?>>
							<div class="sender-box-list-item-caption">
								<?=GetMessage('sender_chain_edit_field_send_type_REITERATE')?>
								<span class="sender-mailing-sprite sender-box-list-item-caption-delete" onclick="DeleteSelectedSendType(this);" style="height: 20px; width: 23px; margin-top: -2px;"></span>
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
		</table>
	<?
	else:
	?>
		<script>
			function ShowMailingType(isShowNew)
			{
				if(isShowNew)
				{
					BX('MAILING_TYPE_EXIST_CONTAINER').style.display = 'none';
					BX('MAILING_TYPE_NEW_CONTAINER').style.display = 'block';
				}
				else
				{
					BX('MAILING_TYPE_EXIST_CONTAINER').style.display = 'block';
					BX('MAILING_TYPE_NEW_CONTAINER').style.display = 'none';
				}
			}
		</script>

		<p class="adm-white-container-p">
			<?=GetMessage("sender_wizard_text");?>
		</p>
		<div style="margin-bottom: 20px;">
			<span>
				<input class="sender-wizard-radio" type="radio" value="EXIST" name="MAILING_TYPE" id="MAILING_TYPE_EXIST" onclick="ShowMailingType(false)" <?=($MAILING_TYPE!='NEW' ? 'checked':'')?> <?=(empty($arMailingList) ? 'disabled' : '')?>>
				<label class="sender-wizard-radio-label" for="MAILING_TYPE_EXIST"><span></span><?=GetMessage("sender_wizard_step_mailing_field_exist")?></label>
			</span>
			<span>
				<input class="sender-wizard-radio" type="radio" value="NEW" name="MAILING_TYPE" id="MAILING_TYPE_NEW" onclick="ShowMailingType(true)" <?=($MAILING_TYPE=='NEW' ? 'checked':'')?>>
				<label class="sender-wizard-radio-label" for="MAILING_TYPE_NEW"><span></span><?=GetMessage("sender_wizard_step_mailing_field_new")?></label>
			</span>
		</div>

		<div class="" id="MAILING_TYPE_EXIST_CONTAINER" <?=($MAILING_TYPE=='NEW' ? 'style="display:none;"':'')?>>
			<div class="adm-detail-content-item-block">
				<select name="MAILING_ID">
					<option value=""><?=GetMessage("sender_wizard_step_mailing_field_exist_list")?></option>
					<?foreach($arMailingList as $arMailing):?>
						<option value="<?=intval($arMailing['ID'])?>"><?=htmlspecialcharsbx($arMailing['NAME'])?></option>
					<?endforeach?>
				</select>
			</div>
		</div>
		<div class="" id="MAILING_TYPE_NEW_CONTAINER" <?=($MAILING_TYPE!='NEW' ? 'style="display:none;"':'')?>>
			<p class="adm-white-container-p">
				<?=GetMessage("sender_mailing_edit_main");?>
			</p>
			<div class="adm-detail-content-item-block">
				<p class="adm-detail-content-item-block-title"><?=GetMessage("sender_wizard_step_mailing_title_sub");?></p>
				<table class="adm-detail-content-table edit-table">
					<tr>
						<td width="40%" class="adm-detail-valign-top"><?echo GetMessage("sender_mailing_edit_field_active")?></td>
						<td width="60%" style="padding-top: 11px;">
							<input class="adm-designed-checkbox" type="checkbox" id="ACTIVE" name="ACTIVE" value="Y"<?if($str_ACTIVE == "Y") echo " checked"?>>
							<label for="ACTIVE" class="adm-designed-checkbox-label"></label>
						</td>
					</tr>
					<tr>
						<td><?echo GetMessage("sender_mailing_edit_field_site")?></td>
						<td><?echo CLang::SelectBox("SITE_ID", $str_SITE_ID);?></td>
					</tr>
					<tr class="adm-detail-required-field">
						<td><?echo GetMessage("sender_mailing_edit_field_name")?>
							<br/>
							<span class="adm-fn"><?=GetMessage('sender_mailing_edit_field_name_desc')?></span>
						</td>
						<td><input type="text" name="NAME" value="<?echo $str_NAME;?>" size="45" maxlength="100"></td>
					</tr>
					<tr>
						<td class="adm-detail-valign-top">
							<?echo GetMessage("sender_mailing_edit_field_desc")?>
							<br/>
							<span class="adm-fn"><?=GetMessage('sender_mailing_edit_field_desc_desc')?></span>
						</td>
						<td><textarea class="typearea" name="DESCRIPTION" cols="45" rows="5" wrap="VIRTUAL" style="width:100%"><?echo $str_DESCRIPTION; ?></textarea></td>
					</tr>
					<tr>
						<td class="adm-detail-valign-top"><?echo GetMessage("sender_mailing_edit_field_track_click")?></td>
						<td style="padding-top: 11px;">
							<input class="adm-designed-checkbox" type="checkbox" id="TRACK_CLICK" name="TRACK_CLICK" value="Y"<?if($str_TRACK_CLICK == "Y") echo " checked"?>>
							<label for="TRACK_CLICK" class="adm-designed-checkbox-label"></label>
						</td>
					</tr>
				</table>
			</div>
		</div>
	<?
	endif;
	?>
		</div>
		<?if(isset($popup_create_group) && $popup_create_group == 'Y'):?>
		<?else:?>
			<div class="sender-wizard-btn-cont">
			<?if($step=='chain_send_type'):?>
				<a href="javascript: BX.submit(document.forms['post_form'])" class="adm-btn adm-btn-save"><?=GetMessage("sender_wizard_step_mailing_bnt_end")?></a>
			<?else:?>
				<a href="javascript: BX.submit(document.forms['post_form'])" class="adm-btn adm-btn-grey"><?=GetMessage("sender_wizard_step_mailing_bnt_next")?></a>
			<?endif;?>
			</div>
		<?endif?>
	</form>
</div>

<?
	if(isset($popup_create_group) && $popup_create_group == 'Y'):
		?></div><?
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");
		exit();
	endif;
?>
</div>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>