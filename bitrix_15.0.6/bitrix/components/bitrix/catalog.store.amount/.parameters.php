<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use \Bitrix\Main;
use \Bitrix\Main\Loader;

Loader::includeModule("catalog");

global $USER_FIELD_MANAGER;

$storeIterator = CCatalogStore::GetList(
	array(),
	array('SHIPPING_CENTER' => 'Y'), // Main\Application::getInstance()->getContext()->getSite()),
	false,
	false,
	array('ID', 'TITLE')
);
while ($store = $storeIterator->GetNext())
	$arStore[$store['ID']] = "[".$store['ID']."] ".$store['TITLE'];

$userFields = $USER_FIELD_MANAGER->GetUserFields("CAT_STORE", 0, Main\Application::getInstance()->getContext()->getLanguage());
$propertyUF = array();

foreach($userFields as $fieldName => $userField)
	$propertyUF[$fieldName] = $userField["LIST_COLUMN_LABEL"] ? $userField["LIST_COLUMN_LABEL"] : $fieldName;

$arComponentParameters = array(
	'GROUPS' => array(
		'STORE' => array(
			'NAME' => GetMessage('CP_CSA_GROUP_STORE')
		)
	),
	'PARAMETERS' => array(
		'STORES' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CP_CSA_PARAM_STORES'),
			'TYPE' => 'LIST',
			'MULTIPLE' => 'Y',
			'VALUES' => $arStore
		),
		'ELEMENT_ID' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CP_CSA_PARAM_ELEMENT_ID'),
			'TYPE' => 'STRING',
			'DEFAULT' => ''
		),
		'ELEMENT_CODE' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CP_CSA_PARAM_ELEMENT_CODE'),
			'TYPE' => 'STRING',
			'DEFAULT' => ''
		),
		'STORE_PATH' => array(
			'PARENT' => 'URL_TEMPLATES',
			'NAME' => GetMessage('CP_CSA_PARAM_STORE_PATH'),
			'TYPE' => 'STRING',
			'DEFAULT' => ''
		),
		"USER_FIELDS" => array(
			"PARENT" => "STORE",
			"NAME" => GetMessage("CP_CSA_PARAM_USER_FIELDS"),
			"TYPE" => "LIST",
			"MULTIPLE" => "Y",
			"ADDITIONAL_VALUES" => "Y",
			"VALUES" => $propertyUF,
		),
		"FIELDS" => array(
			'NAME' => GetMessage('CP_CSA_PARAM_FIELDS'),
			'PARENT' => 'STORE',
			'TYPE'  => 'LIST',
			'MULTIPLE' => 'Y',
			'ADDITIONAL_VALUES' => 'Y',
			'VALUES'    => Array(
				'TITLE'  => GetMessage('CP_CSA_PARAM_TITLE'),
				'ADDRESS'  => GetMessage('CP_CSA_PARAM_ADDRESS'),
				'DESCRIPTION'  => GetMessage('CP_CSA_PARAM_DESCRIPTION'),
				'PHONE'  => GetMessage('CP_CSA_PARAM_PHONE'),
				'EMAIL'  => GetMessage('CP_CSA_PARAM_EMAIL'),
				'IMAGE_ID'  => GetMessage('CP_CSA_PARAM_IMAGE_ID'),
				'COORDINATES'  => GetMessage('CP_CSA_PARAM_COORDINATES'),
				'SCHEDULE'  => GetMessage('CP_CSA_PARAM_SCHEDULE')
			)
		),
		'SHOW_EMPTY_STORE' => array(
			'PARENT' => 'STORE',
			'NAME' => GetMessage('CP_CSA_PARAM_SHOW_EMPTY_STORE'),
			'TYPE' => 'CHECKBOX',
			'DEFAULT' => 'Y',
		),
		'USE_MIN_AMOUNT' => array(
			'PARENT' => 'STORE',
			'NAME' => GetMessage('CP_CSA_PARAM_USE_MIN_AMOUNT'),
			'TYPE' => 'CHECKBOX',
			'DEFAULT' => 'Y',
			'REFRESH' => 'Y'
		),
		'SHOW_GENERAL_STORE_INFORMATION' => array(
			'PARENT' => 'STORE',
			'NAME' => GetMessage('CP_CSA_SHOW_GENERAL_STORE_INFORMATION'),
			'TYPE' => 'CHECKBOX',
			'DEFAULT' => 'N',
			'REFRESH' => 'Y'
		),
		'MAIN_TITLE' => array(
			'NAME' => GetMessage('CP_CSA_PARAM_MAIN_TITLE'),
			'TYPE' => 'STRING',
			'DEFAULT' => ''
		),
		'CACHE_TIME'  =>  array('DEFAULT' => 36000),
	)
);

if (!isset($arCurrentValues['USE_MIN_AMOUNT']) || $arCurrentValues['USE_MIN_AMOUNT'] == 'Y')
{
	$arComponentParameters['PARAMETERS']['MIN_AMOUNT'] = array(
		'PARENT' => 'STORE',
		'NAME' => GetMessage('CP_CSA_PARAM_MIN_AMOUNT'),
		'TYPE' => 'STRING',
		'DEFAULT' => '0',
	);
}