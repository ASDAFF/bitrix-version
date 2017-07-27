<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
if(!CModule::IncludeModule("iblock"))	return;

$arIBlockType = CIBlockParameters::GetIBlockTypes();

$arIBlock = array();
$rsIBlock = CIBlock::GetList(Array("sort" => "asc"), Array("TYPE" => $arCurrentValues["IBLOCK_TYPE"], "ACTIVE"=>"Y"));
while($arr=$rsIBlock->Fetch())
	$arIBlock[$arr["ID"]] = "[".$arr["ID"]."] ".$arr["NAME"];


$site     = ($_REQUEST['site'] <> '' ? $_REQUEST['site'] : ($_REQUEST['src_site'] <> '' ? $_REQUEST['src_site'] : false));
$arFilter = Array(
	'TYPE_ID' => 'FEEDBACK_FORM',
	'ACTIVE'  => 'Y'
);
if($site !== false)
	$arFilter['LID'] = $site;

$arEvent = Array();
$dbType  = CEventMessage::GetList($by = 'ID', $order = 'DESC', $arFilter);
while($arType = $dbType->GetNext())
	$arEvent[$arType['ID']] = '[' . $arType['ID'] . '] ' . $arType['SUBJECT'];

$arComponentParameters = array(
	'GROUPS'     => array(
		'ANTISPAM_SETTINGS' => array(
			'NAME' => GetMessage('GROUP_ANTISPAM_SETTINGS'),
			'SORT' => '490'
		),
		'JS_VALIDATE_SETTINGS' => array(
			'NAME' => GetMessage('GROUPS_JS_VALIDATE_SETTINGS'),
			'SORT' => '500'
		),
		'BRANCH_SETTINGS'      => array(
			'NAME' => GetMessage('BRANCH_SETTINGS'),
			'SORT' => '510'
		),
		'FILE_SETTINGS'        => array(
			'NAME' => GetMessage('FILE_SETTINGS'),
			'SORT' => '520'
		),
        'UUID_SETTINGS'        => array(
			'NAME' => GetMessage('UUID_SETTINGS'),
			'SORT' => '530'
		),
		'TITLE'                => array(
			'NAME' => GetMessage('MFP_USER_FIELDS_TITLE'),
			'SORT' => '540'
		),
		'CSS_MODAL_SETTINGS'   => array(
			'NAME' => GetMessage('GROUPS_CSS_MODAL_SETTINGS'),
			'SORT' => '1000'
		),
	),
	'PARAMETERS' => array(
		'IBLOCK_TYPE' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('IBLOCK_TYPE'),
			'TYPE' => 'LIST',
			'VALUES' => $arIBlockType,
			'REFRESH' => 'Y',
		),
		'IBLOCK_ID' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('IBLOCK_ID'),
			'TYPE' => 'LIST',
			'ADDITIONAL_VALUES' => 'Y',
			'VALUES' => $arIBlock,
			'REFRESH' => 'Y',
		),
		'INSTALL_IBLOCK'  => Array(
			"NAME"    => GetMessage("INSTALL_IBLOCK"),
			"TYPE"    => "CHECKBOX",
			"DEFAULT" => "N",
			"PARENT"  => "BASE",
			"REFRESH" => "Y",
		),
		'USE_CAPTCHA'                 => Array(
			'NAME'    => GetMessage('MFP_CAPTCHA'),
			'TYPE'    => 'CHECKBOX',
			'DEFAULT' => 'N',
			'PARENT'  => 'ANTISPAM_SETTINGS',
		),
		'USE_HIDDEN_PROTECTION'       => Array(
			'NAME'    => GetMessage('USE_HIDDEN_PROTECTION'),
			'TYPE'    => 'CHECKBOX',
			'DEFAULT' => 'Y',
			'PARENT'  => 'ANTISPAM_SETTINGS',
		),
		'USE_PHP_ANTISPAM'       => Array(
			'NAME'    => GetMessage('USE_PHP_ANTISPAM'),
			'TYPE'    => 'CHECKBOX',
			'DEFAULT' => 'N',
			'PARENT'  => 'ANTISPAM_SETTINGS',
		),
		'PHP_ANTISPAM_LEVEL'        => array(
			'NAME'    => GetMessage('PHP_ANTISPAM_LEVEL'),
			'TYPE'    => 'STRING',
			'DEFAULT' => 1,
			'PARENT'  => 'ANTISPAM_SETTINGS',
			'COLS'    => 5,
		),
		'REPLACE_FIELD_FROM'          => array(
			'NAME'    => GetMessage('REPLACE_FIELD_FROM'),
			'TYPE'    => 'CHECKBOX',
			'DEFAULT' => 'Y',
			'PARENT'  => 'BASE',
		),
		'INCLUDE_JQUERY'              => array(
			'NAME'    => GetMessage('INCLUDE_JQUERY'),
			'TYPE'    => 'CHECKBOX',
			'DEFAULT' => 'N',
			'PARENT'  => 'JS_VALIDATE_SETTINGS',
			'REFRESH' => 'N',
		),
		'VALIDTE_REQUIRED_FIELDS'     => array(
			'NAME'    => GetMessage('VALIDTE_REQUIRED_FIELDS'),
			'TYPE'    => 'CHECKBOX',
			'DEFAULT' => 'N',
			'PARENT'  => 'JS_VALIDATE_SETTINGS',
			'REFRESH' => 'N',
		),
		'INCLUDE_PLACEHOLDER'     => array(
			'NAME'    => GetMessage('INCLUDE_PLACEHOLDER'),
			'TYPE'    => 'CHECKBOX',
			'DEFAULT' => 'N',
			'PARENT'  => 'JS_VALIDATE_SETTINGS',
			'REFRESH' => 'N',
		),
		'INCLUDE_PRETTY_COMMENTS'     => array(
			'NAME'    => GetMessage('INCLUDE_PRETTY_COMMENTS'),
			'TYPE'    => 'CHECKBOX',
			'DEFAULT' => 'N',
			'PARENT'  => 'JS_VALIDATE_SETTINGS',
			'REFRESH' => 'N',
		),
		'INCLUDE_FORM_STYLER'         => array(
			'NAME'    => GetMessage('INCLUDE_FORM_STYLER'),
			'TYPE'    => 'CHECKBOX',
			'DEFAULT' => 'N',
			'PARENT'  => 'JS_VALIDATE_SETTINGS',
			'REFRESH' => 'N',
		),
		'HIDE_FORM_AFTER_SEND'        => array(
			'NAME'    => GetMessage('HIDE_FORM_AFTER_SEND'),
			'TYPE'    => 'CHECKBOX',
			'DEFAULT' => 'N',
			'PARENT'  => 'JS_VALIDATE_SETTINGS',
		),
		'SCROLL_TO_FORM_IF_MESSAGES'  => array(
			'NAME'    => GetMessage('SCROLL_TO_FORM_IF_MESSAGES'),
			'TYPE'    => 'CHECKBOX',
			'DEFAULT' => 'N',
			'PARENT'  => 'JS_VALIDATE_SETTINGS',
		),
		'SCROLL_TO_FORM_SPEED'        => array(
			'NAME'    => GetMessage('SCROLL_TO_FORM_SPEED'),
			'TYPE'    => 'STRING',
			'DEFAULT' => 1000,
			'PARENT'  => 'JS_VALIDATE_SETTINGS',
			'COLS'    => 5,
		),
		'UNIQUE_FORM_ID'              => Array(
			'NAME'    => GetMessage('UNIQUE_FORM_ID'),
			'TYPE'    => 'STRING',
			'DEFAULT' => uniqid(),
			'PARENT'  => 'BASE',
		),
		'OK_TEXT'                     => Array(
			'NAME'    => GetMessage('MFP_OK_MESSAGE'),
			'TYPE'    => 'STRING',
			'DEFAULT' => GetMessage('MFP_OK_TEXT'),
			'PARENT'  => 'BASE',
		),
		'EMAIL_TO'                    => Array(
			'NAME'    => GetMessage('MFP_EMAIL_TO'),
			'TYPE'    => 'STRING',
			'DEFAULT' => htmlspecialchars(COption::GetOptionString('main', 'email_from')),
			'PARENT'  => 'BASE',
		),
		'DISPLAY_FIELDS'              => array(
			'PARENT'            => 'BASE',
			'NAME'              => GetMessage('MFP_DISPLAY_FIELDS'),
			'TYPE'              => 'LIST',
			'MULTIPLE'          => 'Y',
			'VALUES'            => array(
				'AUTHOR_FIO'             => GetMessage('MFP_AUTHOR_FIO'),
				'AUTHOR_NAME'            => GetMessage('MFP_AUTHOR_NAME'),
				'AUTHOR_LAST_NAME'       => GetMessage('MFP_AUTHOR_LAST_NAME'),
				'AUTHOR_SECOND_NAME'     => GetMessage('MFP_AUTHOR_SECOND_NAME'),
				'AUTHOR_EMAIL'           => GetMessage('MFP_AUTHOR_EMAIL'),
				'AUTHOR_PERSONAL_MOBILE' => GetMessage('MFP_AUTHOR_PERSONAL_MOBILE'),
				'AUTHOR_WORK_COMPANY'    => GetMessage('MFP_AUTHOR_WORK_COMPANY'),
				'AUTHOR_POSITION'        => GetMessage('MFP_AUTHOR_POSITION'),
				'AUTHOR_PROFESSION'      => GetMessage('MFP_AUTHOR_PROFESSION'),
				'AUTHOR_STATE'           => GetMessage('MFP_AUTHOR_STATE'),
				'AUTHOR_CITY'            => GetMessage('MFP_AUTHOR_CITY'),
				'AUTHOR_WORK_CITY'       => GetMessage('MFP_AUTHOR_WORK_CITY'),
				'AUTHOR_STREET'          => GetMessage('MFP_AUTHOR_STREET'),
				'AUTHOR_ADRESS'          => GetMessage('MFP_AUTHOR_ADRESS'),
				'AUTHOR_PERSONAL_PHONE'  => GetMessage('MFP_AUTHOR_PERSONAL_PHONE'),
				'AUTHOR_WORK_PHONE'      => GetMessage('MFP_AUTHOR_WORK_PHONE'),
				'AUTHOR_FAX'             => GetMessage('MFP_AUTHOR_FAX'),
				'AUTHOR_MAILBOX'         => GetMessage('MFP_AUTHOR_MAILBOX'),
				'AUTHOR_WORK_MAILBOX'    => GetMessage('MFP_AUTHOR_WORK_MAILBOX'),
				'AUTHOR_SKYPE'           => GetMessage('MFP_AUTHOR_SKYPE'),
				'AUTHOR_ICQ'             => GetMessage('MFP_AUTHOR_ICQ'),
				'AUTHOR_WWW'             => GetMessage('MFP_AUTHOR_WWW'),
				'AUTHOR_WORK_WWW'        => GetMessage('MFP_AUTHOR_WORK_WWW'),
				'AUTHOR_MESSAGE_THEME'   => GetMessage('MFP_AUTHOR_MESSAGE_THEME'),
				'AUTHOR_MESSAGE'         => GetMessage('MFP_AUTHOR_MESSAGE'),
				'AUTHOR_NOTES'           => GetMessage('MFP_AUTHOR_NOTES')
			),
			'ADDITIONAL_VALUES' => 'N',
			'SIZE'              => 10,
			'DEFAULT'           => array("AUTHOR_NAME", "AUTHOR_EMAIL", "AUTHOR_MESSAGE"),
			'COLS'              => 25,
		),
		'REQUIRED_FIELDS'             => Array(
			'NAME'     => GetMessage('MFP_REQUIRED_FIELDS'),
			'TYPE'     => 'LIST',
			'MULTIPLE' => 'Y',
			'VALUES'   => Array(
				'NONE'                   => GetMessage('MFP_ALL_REQ'),
				'AUTHOR_FIO'             => GetMessage('MFP_AUTHOR_FIO'),
				'AUTHOR_NAME'            => GetMessage('MFP_AUTHOR_NAME'),
				'AUTHOR_LAST_NAME'       => GetMessage('MFP_AUTHOR_LAST_NAME'),
				'AUTHOR_SECOND_NAME'     => GetMessage('MFP_AUTHOR_SECOND_NAME'),
				'AUTHOR_EMAIL'           => GetMessage('MFP_AUTHOR_EMAIL'),
				'AUTHOR_PERSONAL_MOBILE' => GetMessage('MFP_AUTHOR_PERSONAL_MOBILE'),
				'AUTHOR_WORK_COMPANY'    => GetMessage('MFP_AUTHOR_WORK_COMPANY'),
				'AUTHOR_POSITION'        => GetMessage('MFP_AUTHOR_POSITION'),
				'AUTHOR_PROFESSION'      => GetMessage('MFP_AUTHOR_PROFESSION'),
				'AUTHOR_STATE'           => GetMessage('MFP_AUTHOR_STATE'),
				'AUTHOR_CITY'            => GetMessage('MFP_AUTHOR_CITY'),
                'AUTHOR_WORK_CITY'       => GetMessage('MFP_AUTHOR_WORK_CITY'),
				'AUTHOR_STREET'          => GetMessage('MFP_AUTHOR_STREET'),
				'AUTHOR_ADRESS'          => GetMessage('MFP_AUTHOR_ADRESS'),
				'AUTHOR_PERSONAL_PHONE'  => GetMessage('MFP_AUTHOR_PERSONAL_PHONE'),
				'AUTHOR_WORK_PHONE'      => GetMessage('MFP_AUTHOR_WORK_PHONE'),
				'AUTHOR_FAX'             => GetMessage('MFP_AUTHOR_FAX'),
				'AUTHOR_MAILBOX'         => GetMessage('MFP_AUTHOR_MAILBOX'),
				'AUTHOR_WORK_MAILBOX'    => GetMessage('MFP_AUTHOR_WORK_MAILBOX'),
				'AUTHOR_SKYPE'           => GetMessage('MFP_AUTHOR_SKYPE'),
				'AUTHOR_ICQ'             => GetMessage('MFP_AUTHOR_ICQ'),
				'AUTHOR_WWW'             => GetMessage('MFP_AUTHOR_WWW'),
				'AUTHOR_WORK_WWW'        => GetMessage('MFP_AUTHOR_WORK_WWW'),
				'AUTHOR_MESSAGE_THEME'   => GetMessage('MFP_AUTHOR_MESSAGE_THEME'),
				'AUTHOR_MESSAGE'         => GetMessage('MFP_AUTHOR_MESSAGE'),
				'AUTHOR_NOTES'           => GetMessage('MFP_AUTHOR_NOTES')
			),
			'SIZE'     => 10,
			'DEFAULT'  => array("AUTHOR_NAME", "AUTHOR_EMAIL", "AUTHOR_MESSAGE"),
			'COLS'     => 25,
			'PARENT'   => 'BASE',
		),
		"CUSTOM_FIELDS"               => Array(
			"NAME"     => GetMessage("CUSTOM_FIELDS"),
			"TYPE"     => "STRING",
			"MULTIPLE" => "Y",
			"COLS"     => 50,
			"PARENT"   => "BASE",
			"DEFAULT"  => Array(
				0 => GetMessage('CUSTOM_FIELDS_VALUE_0'),
				1 => GetMessage('CUSTOM_FIELDS_VALUE_1'),
				2 => GetMessage('CUSTOM_FIELDS_VALUE_2'),
				3 => GetMessage('CUSTOM_FIELDS_VALUE_3'),
				4 => GetMessage('CUSTOM_FIELDS_VALUE_4'),
				5 => GetMessage('CUSTOM_FIELDS_VALUE_5'),
				6 => GetMessage('CUSTOM_FIELDS_VALUE_6'),
				7 => GetMessage('CUSTOM_FIELDS_VALUE_7'),
				8 => GetMessage('CUSTOM_FIELDS_VALUE_8'),
				9 => GetMessage('CUSTOM_FIELDS_VALUE_9'),
				10 => GetMessage('CUSTOM_FIELDS_VALUE_10'),
				11 => GetMessage('CUSTOM_FIELDS_VALUE_11'),
				12 => GetMessage('CUSTOM_FIELDS_VALUE_12'),
			),
		),
		'BRANCH_ACTIVE'               => Array(
			"NAME"    => GetMessage("BRANCH_ACTIVE"),
			"TYPE"    => "CHECKBOX",
			"DEFAULT" => "N",
			"PARENT"  => "BRANCH_SETTINGS",
			"REFRESH" => "Y",
		),
		'SHOW_FILES'                  => array(
			'NAME'    => GetMessage('SHOW_FILES'),
			'TYPE'    => 'CHECKBOX',
			'DEFAULT' => 'N',
			'PARENT'  => 'FILE_SETTINGS',
			'REFRESH' => 'Y',
		),
		'ADMIN_EVENT_MESSAGE_ID'      => Array(
			'NAME'     => GetMessage('MFP_EMAIL_TEMPLATES'),
			'TYPE'     => 'LIST',
			'VALUES'   => $arEvent,
			'DEFAULT'  => '',
			'MULTIPLE' => 'Y',
			'COLS'     => 50,
			'PARENT'   => 'BASE',
		),
		'USER_EVENT_MESSAGE_ID'       => Array(
			'NAME'     => GetMessage('MFP_USER_EMAIL_TEMPLATES'),
			'TYPE'     => 'LIST',
			'VALUES'   => $arEvent,
			'DEFAULT'  => '',
			'MULTIPLE' => 'Y',
			'COLS'     => 50,
			'PARENT'   => 'BASE',
		),
		'HIDE_FIELD_NAME'               => array(
			'NAME'    => GetMessage('HIDE_FIELD_NAME'),
			'TYPE'    => 'CHECKBOX',
			'DEFAULT' => '',
			'PARENT'  => 'VISUAL',
		),
		'TITLE_DISPLAY'               => array(
			'NAME'    => GetMessage('MFP_FORM_TITLE_DISPLAY'),
			'TYPE'    => 'CHECKBOX',
			'DEFAULT' => '',
			'PARENT'  => 'VISUAL',
		),
		'FORM_TITLE'                  => array(
			'NAME'    => GetMessage('MFP_FORM_TITLE'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
			'COLS'    => 50,
			'PARENT'  => 'VISUAL',
		),
		'FORM_TITLE_LEVEL'            => array(
			'NAME'    => GetMessage('MFP_FORM_TITLE_LEVEL'),
			'TYPE'    => 'LIST',
			'VALUES'  => Array(
				'1' => GetMessage('MFP_FORM_TITLE_H1'),
				'2' => GetMessage('MFP_FORM_TITLE_H2'),
				'3' => GetMessage('MFP_FORM_TITLE_H3'),
				'4' => GetMessage('MFP_FORM_TITLE_H4'),
				'5' => GetMessage('MFP_FORM_TITLE_H5'),
				'6' => GetMessage('MFP_FORM_TITLE_H6'),
			),
			'DEFAULT' => '',
			'PARENT'  => 'VISUAL',
		),
		'FORM_STYLE_TITLE'            => array(
			'NAME'    => GetMessage('MFP_FORM_STYLE_TITLE'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
			'COLS'    => 50,
			'PARENT'  => 'VISUAL',
		),
		'FORM_STYLE'                  => array(
			'NAME'    => GetMessage('MFP_FORM_STYLE'),
			'TYPE'    => 'STRING',
			'DEFAULT' => 'text-align:left;',
			'COLS'    => 50,
			'PARENT'  => 'VISUAL',
		),
		'FORM_STYLE_DIV'              => array(
			'NAME'    => GetMessage('MFP_FORM_STYLE_DIV'),
			'TYPE'    => 'STRING',
			'DEFAULT' => 'overflow:hidden;padding:5px;',
			'COLS'    => 50,
			'PARENT'  => 'VISUAL',
		),
		'FORM_STYLE_LABEL'            => array(
			'NAME'    => GetMessage('MFP_FORM_STYLE_LABEL'),
			'TYPE'    => 'STRING',
			'DEFAULT' => 'display: block;min-width:175px;margin-bottom: 3px;float:left;',
			'COLS'    => 50,
			'PARENT'  => 'VISUAL',
		),
		'FORM_STYLE_TEXTAREA'         => array(
			'NAME'    => GetMessage('MFP_FORM_STYLE_TEXTAREA'),
			'TYPE'    => 'STRING',
			'DEFAULT' => 'padding:3px 5px;min-width:380px;min-height:150px;',
			'COLS'    => 50,
			'PARENT'  => 'VISUAL',
		),
		'FORM_STYLE_INPUT'            => array(
			'NAME'    => GetMessage('MFP_FORM_STYLE_INPUT'),
			'TYPE'    => 'STRING',
			'DEFAULT' => 'min-width:220px;padding:3px 5px;',
			'COLS'    => 50,
			'PARENT'  => 'VISUAL',
		),
		'FORM_STYLE_SELECT'           => array(
			'NAME'    => GetMessage('MFP_FORM_STYLE_SELECT'),
			'TYPE'    => 'STRING',
			'DEFAULT' => 'min-width:232px;padding:3px 5px;',
			'COLS'    => 50,
			'PARENT'  => 'VISUAL',
		),
		'FORM_STYLE_SUBMIT'           => array(
			'NAME'    => GetMessage('MFP_FORM_STYLE_SUBMIT'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
			'COLS'    => 50,
			'PARENT'  => 'VISUAL',
		),
		'FORM_SUBMIT_CLASS'           => array(
			'NAME'    => GetMessage('FORM_SUBMIT_CLASS'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
			'COLS'    => 50,
			'PARENT'  => 'VISUAL',
		),
		'FORM_SUBMIT_ID'           => array(
			'NAME'    => GetMessage('FORM_SUBMIT_ID'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
			'COLS'    => 50,
			'PARENT'  => 'VISUAL',
		),
		'FORM_SUBMIT_VALUE'           => array(
			'NAME'    => GetMessage('MFP_FORM_SUBMIT_VALUE'),
			'TYPE'    => 'STRING',
			'DEFAULT' => GetMessage('FORM_SUBMIT_VALUE_DEFAULT'),
			'COLS'    => 30,
			'PARENT'  => 'VISUAL',
		),
		'USER_AUTHOR_FIO'             => array(
			'NAME'    => GetMessage('MFP_AUTHOR_FIO'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
			'COLS'    => 30,
			'PARENT'  => 'TITLE',
		),
		'USER_AUTHOR_NAME'            => array(
			'NAME'    => GetMessage('MFP_AUTHOR_NAME'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
			'COLS'    => 30,
			'PARENT'  => 'TITLE',
		),
		'USER_AUTHOR_LAST_NAME'       => array(
			'NAME'    => GetMessage('MFP_AUTHOR_LAST_NAME'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
			'COLS'    => 30,
			'PARENT'  => 'TITLE',
		),
		'USER_AUTHOR_SECOND_NAME'     => array(
			'NAME'    => GetMessage('MFP_AUTHOR_SECOND_NAME'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
			'COLS'    => 30,
			'PARENT'  => 'TITLE',
		),
		'USER_AUTHOR_EMAIL'           => array(
			'NAME'    => GetMessage('MFP_AUTHOR_EMAIL'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
			'COLS'    => 30,
			'PARENT'  => 'TITLE',
		),
		'USER_AUTHOR_PERSONAL_MOBILE' => array(
			'NAME'    => GetMessage('MFP_AUTHOR_PERSONAL_MOBILE'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
			'COLS'    => 30,
			'PARENT'  => 'TITLE',
		),
		'USER_AUTHOR_WORK_COMPANY'    => array(
			'NAME'    => GetMessage('MFP_AUTHOR_WORK_COMPANY'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
			'COLS'    => 30,
			'PARENT'  => 'TITLE',
		),
		'USER_AUTHOR_POSITION'        => array(
			'NAME'    => GetMessage('MFP_AUTHOR_POSITION'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
			'COLS'    => 30,
			'PARENT'  => 'TITLE',
		),
		'USER_AUTHOR_PROFESSION'      => array(
			'NAME'    => GetMessage('MFP_AUTHOR_PROFESSION'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
			'COLS'    => 30,
			'PARENT'  => 'TITLE',
		),
		'USER_AUTHOR_STATE'           => array(
			'NAME'    => GetMessage('MFP_AUTHOR_STATE'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
			'COLS'    => 30,
			'PARENT'  => 'TITLE',
		),
		'USER_AUTHOR_CITY'            => array(
			'NAME'    => GetMessage('MFP_AUTHOR_CITY'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
			'COLS'    => 30,
			'PARENT'  => 'TITLE',
		),
        'USER_AUTHOR_WORK_CITY'            => array(
			'NAME'    => GetMessage('MFP_AUTHOR_WORK_CITY'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
			'COLS'    => 30,
			'PARENT'  => 'TITLE',
		),
		'USER_AUTHOR_STREET'          => array(
			'NAME'    => GetMessage('MFP_AUTHOR_STREET'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
			'COLS'    => 30,
			'PARENT'  => 'TITLE',
		),
		'USER_AUTHOR_ADRESS'          => array(
			'NAME'    => GetMessage('MFP_AUTHOR_ADRESS'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
			'COLS'    => 30,
			'PARENT'  => 'TITLE',
		),
		'USER_AUTHOR_PERSONAL_PHONE'  => array(
			'NAME'    => GetMessage('MFP_AUTHOR_PERSONAL_PHONE'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
			'COLS'    => 30,
			'PARENT'  => 'TITLE',
		),
		'USER_AUTHOR_WORK_PHONE'      => array(
			'NAME'    => GetMessage('MFP_AUTHOR_WORK_PHONE'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
			'COLS'    => 30,
			'PARENT'  => 'TITLE',
		),
		'USER_AUTHOR_FAX'             => array(
			'NAME'    => GetMessage('MFP_AUTHOR_FAX'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
			'COLS'    => 30,
			'PARENT'  => 'TITLE',
		),
		'USER_AUTHOR_MAILBOX'         => array(
			'NAME'    => GetMessage('MFP_AUTHOR_MAILBOX'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
			'COLS'    => 30,
			'PARENT'  => 'TITLE',
		),
		'USER_AUTHOR_WORK_MAILBOX'    => array(
			'NAME'    => GetMessage('MFP_AUTHOR_WORK_MAILBOX'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
			'COLS'    => 30,
			'PARENT'  => 'TITLE',
		),
		'USER_AUTHOR_SKYPE'           => array(
			'NAME'    => GetMessage('MFP_AUTHOR_SKYPE'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
			'COLS'    => 30,
			'PARENT'  => 'TITLE',
		),
		'USER_AUTHOR_ICQ'             => array(
			'NAME'    => GetMessage('MFP_AUTHOR_ICQ'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
			'COLS'    => 30,
			'PARENT'  => 'TITLE',
		),
		'USER_AUTHOR_WWW'             => array(
			'NAME'    => GetMessage('MFP_AUTHOR_WWW'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
			'COLS'    => 30,
			'PARENT'  => 'TITLE',
		),
		'USER_AUTHOR_WORK_WWW'        => array(
			'NAME'    => GetMessage('MFP_AUTHOR_WORK_WWW'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
			'COLS'    => 30,
			'PARENT'  => 'TITLE',
		),
		'USER_AUTHOR_MESSAGE_THEME'   => array(
			'NAME'    => GetMessage('MFP_AUTHOR_MESSAGE_THEME'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
			'COLS'    => 30,
			'PARENT'  => 'TITLE',
		),
		'USER_AUTHOR_MESSAGE'         => array(
			'NAME'    => GetMessage('MFP_AUTHOR_MESSAGE'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
			'COLS'    => 30,
			'PARENT'  => 'TITLE',
		),
		'USER_AUTHOR_NOTES'           => array(
			'NAME'    => GetMessage('MFP_AUTHOR_NOTES'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
			'COLS'    => 30,
			'PARENT'  => 'TITLE',
		),
		"AJAX_MODE"                   => array(),
		'SHOW_CSS_MODAL_AFTER_SEND'   => Array(
			'NAME'    => GetMessage('SHOW_CSS_MODAL_AFTER_SEND'),
			'TYPE'    => 'CHECKBOX',
			'DEFAULT' => 'N',
			'PARENT'  => 'CSS_MODAL_SETTINGS',
		),
		'CSS_MODAL_HEADER'            => Array(
			'NAME'    => GetMessage('CSS_MODAL_HEADER'),
			'TYPE'    => 'STRING',
			'DEFAULT' => GetMessage('CSS_MODAL_HEADER_TXT'),
			'PARENT'  => 'CSS_MODAL_SETTINGS',
		),
		'CSS_MODAL_FOOTER'            => Array(
			'NAME'    => GetMessage('CSS_MODAL_FOOTER'),
			'TYPE'    => 'STRING',
			'DEFAULT' => GetMessage('CSS_MODAL_FOOTER_TXT'),
			'PARENT'  => 'CSS_MODAL_SETTINGS',
		),
		'CSS_MODAL_CONTENT'           => Array(
			'NAME'    => GetMessage('CSS_MODAL_CONTENT'),
			'TYPE'    => 'STRING',
			'DEFAULT' => GetMessage('CSS_MODAL_CONTENT_TXT'),
			'ROWS'    => 10,
			'PARENT'  => 'CSS_MODAL_SETTINGS',
		),
        'UUID_LENGTH'            => Array(
            'NAME'    => GetMessage('UUID_LENGTH'),
            'TYPE'    => 'STRING',
            'DEFAULT' => 10,
            'COLS'    => 3,
            'PARENT'  => 'UUID_SETTINGS',
        ),
        'UUID_PREFIX'            => Array(
            'NAME'    => GetMessage('UUID_PREFIX'),
            'TYPE'    => 'STRING',
            'DEFAULT' => '',
            'PARENT'  => 'UUID_SETTINGS',
        ),
	)
);


if($arCurrentValues["BRANCH_ACTIVE"] == "Y")
{
	$arComponentParameters["PARAMETERS"]["BRANCH_BLOCK_NAME"]       = array(
		"NAME"    => GetMessage("BRANCH_BLOCK_NAME"),
		"TYPE"    => "STRING",
		"DEFAULT" => GetMessage("BRANCH_BLOCK_NAME_OTDEL"),
		"PARENT"  => "BRANCH_SETTINGS",
	);
	$arComponentParameters["PARAMETERS"]["BRANCH_FIELDS"]           = array(
		"NAME"     => GetMessage("BRANCH_FIELDS"),
		"TYPE"     => "STRING",
		"MULTIPLE" => "Y",
		"COLS"     => 50,
		"PARENT"   => "BRANCH_SETTINGS",
		"DEFAULT"  => Array(
			0 => GetMessage("BRANCH_FIELDS_TEHNICESKAA_PODDERJK") . '###test1@xmail.ru###test2@xmail.ru',
			1 => GetMessage("BRANCH_FIELDS_OTDEL_PRODAJ") . '###test1@xmail.ru',
			2 => GetMessage("BRANCH_FIELDS_OTDEL_OPLATY") . '###test1@xmail.ru',
			3 => GetMessage("BRANCH_FIELDS_OTDEL_JALOB") . ' (Abuse)###test1@xmail.ru',
			4 => GetMessage("BRANCH_FIELDS_ADMINISTRATIVNYY_OTD") . '###test1@xmail.ru',
		),
	);
	$arComponentParameters["PARAMETERS"]["MSG_PRIORITY"]            = array(
		"NAME"    => GetMessage("MSG_PRIORITY"),
		"TYPE"    => "CHECKBOX",
		"DEFAULT" => "N",
		"PARENT"  => "BRANCH_SETTINGS",
	);
	$arComponentParameters["PARAMETERS"]["MSG_PRIORITY_BLOCK_NAME"] = array(
		"NAME"    => GetMessage("BRANCH_BLOCK_NAME"),
		"TYPE"    => "STRING",
		"DEFAULT" => GetMessage("MSG_PRIORITY_BLOCK_NAME_VAJNOSTQ"),
		"PARENT"  => "BRANCH_SETTINGS",
	);
	/*$arComponentParameters["PARAMETERS"]["BCC"]                     = array(
		"NAME"    => GetMessage("API_CONSTRUCTOR_ADMINISTRATOR_V_SKRY") . " (BCC)",
		"TYPE"    => "CHECKBOX",
		"DEFAULT" => "N",
		"PARENT"  => "BRANCH_SETTINGS",
	);*/
}


if($arCurrentValues['SHOW_FILES'] == 'Y')
{

	$arComponentParameters['PARAMETERS']['DELETE_FILES_AFTER_UPLOAD']            = array(
		'NAME'    => GetMessage('DELETE_FILES_AFTER_UPLOAD'),
		'TYPE'    => 'CHECKBOX',
		'DEFAULT' => 'N',
		'PARENT'  => 'FILE_SETTINGS',
	);
	$arComponentParameters['PARAMETERS']['SEND_ATTACHMENT']            = array(
		'NAME'    => GetMessage('SEND_ATTACHMENT'),
		'TYPE'    => 'CHECKBOX',
		'DEFAULT' => 'Y',
		'PARENT'  => 'FILE_SETTINGS',
	);
	$arComponentParameters['PARAMETERS']['SET_ATTACHMENT_REQUIRED']    = array(
		'NAME'    => GetMessage('SET_ATTACHMENT_REQUIRED'),
		'TYPE'    => 'CHECKBOX',
		'DEFAULT' => 'N',
		'PARENT'  => 'FILE_SETTINGS',
	);
	$arComponentParameters['PARAMETERS']['SHOW_ATTACHMENT_EXTENSIONS'] = array(
		'NAME'    => GetMessage('SHOW_ATTACHMENT_EXTENSIONS'),
		'TYPE'    => 'CHECKBOX',
		'DEFAULT' => 'N',
		'PARENT'  => 'FILE_SETTINGS',
	);

	$arComponentParameters['PARAMETERS']['COUNT_INPUT_FILE'] = Array(
		'NAME'    => GetMessage('COUNT_INPUT_FILE'),
		'TYPE'    => 'STRING',
		'DEFAULT' => '3',
		'COLS'    => 6,
		'PARENT'  => 'FILE_SETTINGS',
	);
	$arComponentParameters['PARAMETERS']["FILE_DESCRIPTION"] = Array(
		"NAME"     => GetMessage("FILE_DESCRIPTION"),
		"TYPE"     => "STRING",
		"MULTIPLE" => "Y",
		"COLS"     => 50,
		"PARENT"   => "FILE_SETTINGS",
		"DEFAULT"  => Array(),
	);
	$arComponentParameters['PARAMETERS']['MAX_FILE_SIZE']    = Array(
		'NAME'    => GetMessage('MAX_FILE_SIZE'),
		'TYPE'    => 'STRING',
		'DEFAULT' => '10000',
		'COLS'    => 6,
		'PARENT'  => 'FILE_SETTINGS',
	);

	$arComponentParameters['PARAMETERS']['FILE_EXTENSIONS'] = Array(
		'NAME'    => GetMessage('FILE_EXTENSIONS'),
		'TYPE'    => 'STRING',
		'DEFAULT' => 'txt, rtf, doc, docx, xls, xlsx, ods, odt, jpg, jpeg, bmp, png',
		'COLS'    => 50,
		'PARENT'  => 'FILE_SETTINGS',
	);

	$arComponentParameters['PARAMETERS']['UPLOAD_FOLDER'] = Array(
		'NAME'    => GetMessage('UPLOAD_FOLDER'),
		'TYPE'    => 'STRING',
		'DEFAULT' => '/upload/feedback',
		'COLS'    => 50,
		'PARENT'  => 'FILE_SETTINGS',
	);
}

//v.1.4.2
if($arCurrentValues["INSTALL_IBLOCK"] == "Y")
{
	global $DB;
	$IBLOCK_TYPE = 'tuning_soft';
	$bIblockTypeExist = false;
	$arErrors = array();
	$arNotes = array();


	$rs_sites = CSite::GetList($by="sort", $order="asc");
	while ($ar_site = $rs_sites->Fetch())
		$arSites[] = $ar_site['ID'];


	if(!array_key_exists($IBLOCK_TYPE, $arIBlockType))
	{
		//Ищем начличие уже установленного типа инфоблока в системе
		$arFields = Array(
			'ID'       => $IBLOCK_TYPE,
			'SECTIONS' => 'Y',
			'IN_RSS'   => 'N',
			'SORT'     => 5000,
			'LANG'=>Array(
				'ru'=>Array(
					'NAME'			=> GetMessage('IT_RU_NAME'),
					'SECTION_NAME'	=> GetMessage('IT_RU_SECTION_NAME'),
					'ELEMENT_NAME'	=> GetMessage('IT_RU_ELEMENT_NAME'),
				),
				'en'=>Array(
					'NAME'			=> GetMessage('IT_EN_NAME'),
					'SECTION_NAME'	=> GetMessage('IT_EN_SECTION_NAME'),
					'ELEMENT_NAME'	=> GetMessage('IT_EN_ELEMENT_NAME'),
				)
			)
		);

		$obBlocktype = new CIBlockType;
		$DB->StartTransaction();
		$res = $obBlocktype->Add($arFields);
		if(!$res)
		{
			$DB->Rollback();
			$arErrors[] = $obBlocktype->LAST_ERROR;
		}
		else
		{
			$DB->Commit();
			$bIblockTypeExist = true;
			$arNotes[] = GetMessage('IT_CREATE_SUCCESS');
		}
	}
	else
		$bIblockTypeExist = true;

	if($bIblockTypeExist)
	{
		if(!$arNotes)
			$arNotes[] = GetMessage('IT_FOUND');

		$res_iblock = CIBlock::GetList(
			array(),
			array(
				'TYPE'    => $IBLOCK_TYPE,
				'SITE_ID' => $arSites,
				'ACTIVE'  => 'Y',
				'CODE'    => 'main-feedback-statistic'
			)
		);

		if(!$ar_res = $res_iblock->Fetch())
		{
			$ib = new CIBlock;
			$arFields = Array(
				"ACTIVE"           => "Y",
				"NAME"             => GetMessage('IBLOCK_NAME'),
				"CODE"             => "main-feedback-statistic",
				"IBLOCK_TYPE_ID"   => $IBLOCK_TYPE,
				"SITE_ID"          => $arSites,
				"SORT"             => 10,
				"LIST_PAGE_URL"    => "",
				"SECTION_PAGE_URL" => "",
				"DETAIL_PAGE_URL"  => "",
				"DESCRIPTION_TYPE" => "html",
				"RSS_ACTIVE"       => "N",
				"INDEX_ELEMENT"    => "N",
				"INDEX_SECTION"    => "N",
				"WORKFLOW"         => "N",
				"VERSION"          => 2,
				"GROUP_ID"         => Array(
					"1" => "X",
					"2" => "D",
					"3" => "D",
					"4" => "D",
					"5" => "D",
				),
				"FIELDS"           => array(
					"ACTIVE_FROM"  => array("DEFAULT_VALUE" => ""),
					"CODE"         => array(
						"DEFAULT_VALUE" => array(
							"UNIQUE"          => "N",
							"TRANSLITERATION" => "N",
						)
					),
					"SECTION_CODE" => array(
						"DEFAULT_VALUE" => array(
							"UNIQUE"          => "N",
							"TRANSLITERATION" => "N",
						)
					),
				)
			);
			$IBLOCK_ID = $ib->Add($arFields);

			if(intval($IBLOCK_ID))
			{
				$arNotes[] = GetMessage('IBLOCK_CREATE_SUCCESS');

				$obProp = new CIBlockProperty;
				$arProps = array(
					0 => array(
						"CODE"          => "TICKET_ID",
						"NAME"          => GetMessage('PROP_NAME_TICKET_ID'),
						"ACTIVE"        => "Y",
						"IS_REQUIRED"   => "Y",
						"SORT"          => "10",
						"PROPERTY_TYPE" => "S",
						"MULTIPLE"      => "N",
						"SEARCHABLE"    => "N",
						"FILTRABLE"     => "Y",
						"VERSION"       => 2,
					),
                    1 => array(
						"CODE"          => "FILES",
						"NAME"          => GetMessage('PROP_NAME_FILES'),
						"ACTIVE"        => "Y",
						"IS_REQUIRED"   => "N",
						"SORT"          => "20",
						"PROPERTY_TYPE" => "F",
						"MULTIPLE"      => "Y",
						"SEARCHABLE"    => "N",
						"FILTRABLE"     => "Y",
						"WITH_DESCRIPTION"  => "Y",
						"VERSION"       => 2,
					),
				);

				$propsCount = count($arProps);

				foreach($arProps as $arProp)
				{
					$arProp["IBLOCK_ID"] = $IBLOCK_ID;
					if($obProp->Add($arProp))
						$propsCount--;
				}

				if(!$propsCount)
					$arNotes[] = GetMessage('ALL_PROP_CREATE_SUCCESS');
				else
					$arErrors[] = GetMessage('ALL_PROP_CREATE_ERROR');
			}
			else
				$arErrors[] = $ib->LAST_ERROR;
		}
		else
			$arNotes[] = GetMessage('IBLOCK_FOUND');
	}

	if(!empty($arErrors))
		ShowError(implode('<br />',$arErrors));

	if(!empty($arNotes))
		ShowNote(implode('<br />',$arNotes));
}