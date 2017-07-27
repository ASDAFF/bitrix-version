<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sender\Preset;

use Bitrix\Main\Entity;
use Bitrix\Main\EventResult;
use Bitrix\Main\Event;
use Bitrix\Main\IO\File;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Template
{
	/**
	 * @return array
	 */
	public static function getListByType()
	{
		$resultTemplateList = array();
		$arTemplateList = static::getList();
		foreach($arTemplateList as $template)
			$resultTemplateList[$template['TYPE']][] = $template;

		return $resultTemplateList;
	}

	/**
	 * @return array
	 */
	public static function getTypeList()
	{
		return array(
			'BASE' => Loc::getMessage('TYPE_PRESET_TEMPLATE_BASE'),
			'USER' => Loc::getMessage('TYPE_PRESET_TEMPLATE_USER'),
			'ADDITIONAL' => Loc::getMessage('TYPE_PRESET_TEMPLATE_ADDITIONAL'),
		);
	}

	/**
	 * @return array
	 */
	public static function getList()
	{
		$resultList = array();
		$event = new Event('sender', 'OnPresetTemplateList');
		$event->send();

		if($event->getResults()) foreach ($event->getResults() as $eventResult)
		{
			if ($eventResult->getType() == EventResult::ERROR)
			{
				continue;
			}

			$eventResultParameters = $eventResult->getParameters();

			if (!empty($eventResultParameters))
			{
				$resultList = array_merge($resultList, $eventResultParameters);
			}
		}

		return $resultList;
	}

	/**
	 * @return string
	 */
	public static function getTemplateListHtml()
	{
		$arTemplateListByType = \Bitrix\Sender\Preset\Template::getListByType();
		$arTemplateTypeList = \Bitrix\Sender\Preset\Template::getTypeList();

		ob_start();
		?>
		<script>
			function ChangeTemplateList(type)
			{
				var tmplTypeContList = BX.findChildren(BX('TEMPLATE_CONTAINER'), {'className': 'sender-template-list-type-container'}, true);
				for(var i in tmplTypeContList)
					tmplTypeContList[i].style.display = 'none';

				BX('TEMPLATE_CONTAINER_'+type).style.display = 'table-row';


				var buttonList = BX.findChildren(BX('TEMPLATE_BUTTON_CONTAINER'), {'className': 'sender-template-type-selector-button'}, true);
				for(var j in buttonList)
					BX.removeClass(buttonList[j], 'sender-template-type-selector-button-selected');

				BX.addClass(BX('TEMPLATE_BUTTON_'+type), 'sender-template-type-selector-button-selected');
			}
			function SetTemplateToMessage(type, num)
			{
				BX('TEMPLATE_SELECTED_TITILE').innerHTML = templateListByType[type][num]['NAME'];
				var tmplTypeContList = BX.findChildren(BX('tabControl_layout'), {'className': 'hidden-when-show-template-list'}, true);
				for(i in tmplTypeContList)
					tmplTypeContList[i].style.display = 'table-row';

				tmplTypeContList = BX.findChildren(BX('tabControl_layout'), {'className': 'show-when-show-template-list'}, true);
				for(i in tmplTypeContList)
					tmplTypeContList[i].style.display = 'none';

				PutStringToMessageContainer(templateListByType[type][num]['HTML'], true);
				BX('IS_TEMPLATE_LIST_SHOWN').value = 'N';
			}

			var templateListByType = <?=\CUtil::PhpToJSObject($arTemplateListByType);?>;
		</script>
		<div id="TEMPLATE_CONTAINER">
			<div>
				<table>
					<tr>
					<td style="vertical-align: top;">
						<div class="sender-template-type-selector" id="TEMPLATE_BUTTON_CONTAINER">
							<?
							$firstTemplateType = null;
							foreach($arTemplateTypeList as $templateType => $templateTypeName):
								if(!$firstTemplateType) $firstTemplateType = $templateType;
								?>
								<div id="TEMPLATE_BUTTON_<?=$templateType?>" class="sender-template-type-selector-button" onclick="ChangeTemplateList('<?=htmlspecialcharsbx($templateType)?>');">
									<?=$templateTypeName?>
								</div>
							<?endforeach;?>
						</div>
					</td>
					<td style="vertical-align: top;">
						<div class="sender-template-list-container">
							<?foreach($arTemplateTypeList as $templateType => $templateTypeName):?>
								<div class="sender-template-list-type-container" id="TEMPLATE_CONTAINER_<?=$templateType?>" style="display: none;">
									<?if(isset($arTemplateListByType[$templateType])) foreach($arTemplateListByType[$templateType] as $templateNum => $arTemplate):?>
										<div class="sender-template-list-type-block">
											<div class="sender-template-list-type-block-caption">
												<a class="sender-link-email" href="javascript: void(0);"
													onclick="SetTemplateToMessage('<?=htmlspecialcharsbx($arTemplate['TYPE'])?>', <?=intval($templateNum)?>);"
													>
													<?=htmlspecialcharsbx($arTemplate['NAME'])?>
												</a>
											</div>
											<div class="sender-template-list-type-block-img" onclick="SetTemplateToMessage('<?=htmlspecialcharsbx($arTemplate['TYPE'])?>', <?=intval($templateNum)?>);">
												<?if(!empty($arTemplate['ICON'])):?>
													<img src="<?=$arTemplate['ICON']?>">
												<?endif;?>
											</div>
										</div>
									<?endforeach;?>
									<?if(empty($arTemplateListByType[$templateType])):?>
										<div class="sender-template-list-type-blockempty">
											<?=Loc::getMessage('SENDER_PRESET_TEMPLATE_NO_TMPL')?>
										</div>
									<?endif;?>
								</div>
							<?endforeach;?>
						</div>
					</td>
					</tr>
				</table>
				<script>
					ChangeTemplateList('BASE');
				</script>
			</div>
		</div>
		<?
		return ob_get_clean();
	}
}


class TemplateBase
{
	const LOCAL_DIR_TMPL = '/modules/sender/preset/template/';
	const LOCAL_DIR_IMG = '/images/sender/preset/template/';

	/**
	 * @return array
	 */
	public static function onPresetTemplateList()
	{
		$resultList = array();

		$arTemplate = static::getListName();


		foreach ($arTemplate as $templateName)
		{
			$template = static::getById($templateName);
			if($template)
				$resultList[] = $template;
		}

		return $resultList;
	}

	/**
	 * @return array
	 */
	public static function getListName()
	{
		$arTemplate = array(
			'empty',
			'1column1',
			'1column2',
			'2column1',
			'2column2',
			'2column3',
			'2column4',
			'2column5',
			'2column6',
			'3column1',
			'3column2',
			'3column3',
		);

		return $arTemplate;
	}

	/**
	 * @param $templateName
	 * @return array|null
	 */
	public static function getById($templateName)
	{
		$result = null;

		$localPathOfIcon = static::LOCAL_DIR_IMG . bx_basename($templateName) . '.png';
		$fullPathOfIcon = \Bitrix\Main\Loader::getLocal($localPathOfIcon);

		$fullPathOfFile = \Bitrix\Main\Loader::getLocal(static::LOCAL_DIR_TMPL . bx_basename($templateName) . '.php');
		if ($fullPathOfFile)
			$fileContent = File::getFileContents($fullPathOfFile);
		else
			$fileContent = '';


		if (!empty($fileContent) || $templateName == 'empty')
		{
			$fileContent = str_replace(
				array('%TEXT_UNSUB_TEXT%', '%TEXT_UNSUB_LINK%'),
				array(
					Loc::getMessage('PRESET_MAILBLOCK_unsub_TEXT_UNSUB_TEXT'),
					Loc::getMessage('PRESET_MAILBLOCK_unsub_TEXT_UNSUB_LINK')
				),
				$fileContent
			);

			$result = array(
				'TYPE' => 'BASE',
				'NAME' => Loc::getMessage('PRESET_TEMPLATE_' . $templateName),
				'ICON' => (!empty($fullPathOfIcon) ? '/bitrix'.$localPathOfIcon : ''),
				'HTML' => $fileContent
			);
		}

		return $result;
	}

	/**
	 * @param $templateName
	 * @param $html
	 * @return bool|int
	 */
	public static function update($templateName, $html)
	{
		$result = false;
		$fullPathOfFile = \Bitrix\Main\Loader::getLocal(static::LOCAL_DIR_TMPL . bx_basename($templateName) . '.php');
		if ($fullPathOfFile)
			$result = File::putFileContents($fullPathOfFile, $html);

		return $result;
	}
}