<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sender;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class TemplateTable extends Entity\DataManager
{
	const LOCAL_DIR_IMG = '/images/sender/preset/template/';

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function onPresetTemplateList()
	{
		$resultList = array();

		$localPathOfIcon = static::LOCAL_DIR_IMG . 'my.png';
		$fullPathOfIcon = \Bitrix\Main\Loader::getLocal($localPathOfIcon);

		$templateDb = static::getList(array('filter' => array('ACTIVE' => 'Y')));
		while($template = $templateDb->fetch())
		{
			$resultList[] = array(
				'TYPE' => 'USER',
				'NAME' => $template['NAME'],
				'ICON' => (!empty($fullPathOfIcon) ? '/bitrix'.$localPathOfIcon : ''),
				'HTML' => $template['CONTENT']
			);
		}

		return $resultList;
	}

	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sender_preset_template';
	}

	/**
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'autocomplete' => true,
				'primary' => true,
			),
			'ACTIVE' => array(
				'data_type' => 'string',
				'required' => true,
				'default_value' => 'Y',
			),
			'NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage('SENDER_ENTITY_TEMPLATE_FIELD_TITLE_NAME')
			),
			'CONTENT' => array(
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage('SENDER_ENTITY_TEMPLATE_FIELD_TITLE_CONTENT')
			),
		);
	}


	/**
	 * @param array $arParams
	 * @return string
	 */
	public static function initEditor(array $arParams)
	{
		$str_MESSAGE = $arParams['FIELD_VALUE'];
		$isUserHavePhpAccess = $arParams['HAVE_USER_ACCESS'];
		$showSaveTemplate = isset($arParams['SHOW_SAVE_TEMPLATE']) ? $arParams['SHOW_SAVE_TEMPLATE'] : true;

		\CJSCore::RegisterExt("editor_mailblock", Array(
			"js" =>    "/bitrix/js/sender/editor_mailblock.js",
			"rel" =>   array()
		));
		\CJSCore::Init(array("editor_mailblock"));

		ob_start();
		?>
		<script>
			BX.message({"BXEdMailBlocksTitle" : "<?=Loc::getMessage('SENDER_TEMPLATE_EDITOR_MAILBLOCK')?>"});
			BX.message({"BXEdMailBlocksSearchPlaceHolder" : "<?=Loc::getMessage('SENDER_TEMPLATE_EDITOR_MAILBLOCK_SEARCH')?>"});

			function ToggleTemplateSaveDialog()
			{
				BX('TEMPLATE_ACTION_SAVE_NAME_CONT').value = '';

				var currentDisplay =  BX('TEMPLATE_ACTION_SAVE_NAME_CONT').style.display;
				BX('TEMPLATE_ACTION_SAVE_NAME_CONT').style.display = BX.toggle(currentDisplay, ['inline', 'none']);
			}

			BX.addCustomEvent('OnEditorInitedAfter', function(editor){editor.components.SetComponentIcludeMethod('EventMessageThemeCompiler::includeComponent'); });

			BX.addCustomEvent('OnEditorInitedAfter', function(editor){
				editor.config.mailblocks = <?=\CUtil::PhpToJSObject(\Bitrix\Sender\Preset\MailBlock::getBlockForVisualEditor());?>;
				editor.mailblocks = new BXHtmlEditor.BXEditorMailBlocks(editor);
				editor.mailblocksTaskbar = new BXHtmlEditor.MailBlocksControl(editor, editor.taskbarManager);
				editor.taskbarManager.AddTaskbar(editor.mailblocksTaskbar);
				editor.taskbarManager.ShowTaskbar(editor.mailblocksTaskbar.GetId());
			});
		</script>
		<script>
			function PutStringToMessageContainer(str, bChangeAllContent)
			{
				var bMessageHtmlEditorVisible = false;
				var messageHtmlEditor = window.BXHtmlEditor.Get('MESSAGE');
				var messageContainer = BX('bxed_MESSAGE');
				if(messageHtmlEditor) bMessageHtmlEditorVisible = messageHtmlEditor.IsShown();


				if(bMessageHtmlEditorVisible)
				{
					if(bChangeAllContent)
					{
						messageHtmlEditor.SetContent(str, true);
					}
					else
					{
						messageHtmlEditor.InsertHtml(str);
					}
				}
				else
				{
					if(bChangeAllContent)
					{
						messageContainer.value = str;
					}
					else
					{
						messageContainer.value += str;
					}


					BX.fireEvent(messageContainer, 'change');
				}
			}

			function ToggleTemplateSaveDialog()
			{
				BX('TEMPLATE_ACTION_SAVE_NAME_CONT').value = '';

				var currentDisplay =  BX('TEMPLATE_ACTION_SAVE_NAME_CONT').style.display;
				BX('TEMPLATE_ACTION_SAVE_NAME_CONT').style.display = BX.toggle(currentDisplay, ['inline', 'none']);
			}
		</script>
		<?\CFileMan::AddHTMLEditorFrame(
			'MESSAGE',
			$str_MESSAGE,
			false,
			"html",
			array(
				'height' => 650,
				'width' => '100%'
			),
			"N",
			0,
			"",
			"onfocus=\"t=this\"",
			false,
			!$isUserHavePhpAccess,
			false,
			array(
				//'templateID' => $str_SITE_TEMPLATE_ID,
				'componentFilter' => array('TYPE' => 'mail'),
				'limit_php_access' => !$isUserHavePhpAccess
			)
		);?>

		<?
		if($showSaveTemplate):
		?>
		<div class="adm-detail-content-item-block-save">
			<span>
				<input type="checkbox" value="Y" name="TEMPLATE_ACTION_SAVE" id="TEMPLATE_ACTION_SAVE" onclick="ToggleTemplateSaveDialog();">
				<label for="TEMPLATE_ACTION_SAVE"><?=Loc::getMessage('SENDER_TEMPLATE_EDITOR_SAVE')?></label>
			</span>
			<span id="TEMPLATE_ACTION_SAVE_NAME_CONT" style="display: none;"> <?=Loc::getMessage('SENDER_TEMPLATE_EDITOR_SAVE_NAME')?> <input type="text" name="TEMPLATE_ACTION_SAVE_NAME"></span>
		</div>
		<?
		endif;

		return ob_get_clean();
	}
}