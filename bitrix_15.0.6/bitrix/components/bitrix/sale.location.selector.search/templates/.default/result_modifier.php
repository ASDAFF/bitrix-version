<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(is_array($arResult['LOCATION']) && !empty($arResult['LOCATION']))
{
	$path = array();
	$pathNames = array();
	foreach($arResult['PATH'] as $location)
	{
		if($location['ID'] != $arResult['LOCATION']['ID'])
			$path[] = $location['ID'];
		$pathNames[$location['ID']] = $location['NAME'];
	}

	$arResult['LOCATION']['PATH'] = array_reverse($path);
	$arResult['LOCATION']['VALUE'] = $arResult['LOCATION']['ID'];
	$arResult['LOCATION']['DISPLAY'] = $arResult['LOCATION']['NAME'];

	$arResult['PATH_NAMES'] = $pathNames;

	// prevent garbage from figuring at in-page JSON
	unset($arResult['LOCATION']['LATITUDE']);
	unset($arResult['LOCATION']['LONGITUDE']);
	unset($arResult['LOCATION']['SORT']);
	unset($arResult['LOCATION']['PARENT_ID']);
	unset($arResult['LOCATION']['ID']);
	unset($arResult['LOCATION']['NAME']);
	unset($arResult['LOCATION']['SHORT_NAME']);

	unset($arResult['LOCATION']['LEFT_MARGIN']);
	unset($arResult['LOCATION']['RIGHT_MARGIN']);
}

$arResult['RANDOM_TAG'] = rand(999, 99999);
$this->arResult['ADMIN_MODE'] = ADMIN_SECTION == 1;

// modes
$modes = array();
if(ADMIN_SECTION == 1 || $arParams['ADMIN_MODE'] == 'Y')
	$modes[] = 'admin';

foreach($modes as &$mode)
	$mode = 'bx-'.$mode.'-mode';

$arResult['MODE_CLASSES'] = implode(' ', $modes);