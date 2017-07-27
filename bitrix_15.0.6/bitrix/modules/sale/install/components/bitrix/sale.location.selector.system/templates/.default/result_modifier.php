<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// prepare data for inline js, try to make it smaller
$pathNames = array();

// initial struct
$arResult['FOR_JS'] = array(
	'DATA' => array(
		'LOCATION' => array(),
		'PATH_NAMES' => array(),
	),
	'CONNECTED' => array(
		'LOCATION' => array(),
		'GROUP' => array()
	)
);

if(is_array($arResult['CONNECTIONS']['LOCATION']))
	$arResult['FOR_JS']['DATA']['LOCATION'] = $arResult['CONNECTIONS']['LOCATION'];

foreach($arResult['FOR_JS']['DATA']['LOCATION'] as &$location)
{
	$pathIds = array();
	if(is_array($location['PATH']))
	{
		$name = current($location['PATH']);
		$location['NAME'] = $name['NAME'];

		foreach($location['PATH'] as $id => $pathElem)
		{
			$pathIds[] = $id;
			$pathNames[$id] = $pathElem['NAME'];
		}

		array_shift($pathIds);
		$location['PATH'] = $pathIds;
	}

	unset($location['SORT']);

	//unset($location['CODE']);
	//else PATH is supposed to be downloaded on-demand
}
unset($location);

$arResult['FOR_JS']['DATA']['PATH_NAMES'] = $pathNames;

// groups
if(is_array($arResult['CONNECTIONS']['GROUP']))
	$arResult['FOR_JS']['DATA']['GROUPS'] = $arResult['CONNECTIONS']['GROUP'];

// connected

if(is_array($arResult['CONNECTIONS']['LOCATION']) && !empty($arResult['CONNECTIONS']['LOCATION']))
	$arResult['FOR_JS']['CONNECTED']['LOCATION'] = array_keys($arResult['CONNECTIONS']['LOCATION']);

if(is_array($arResult['CONNECTIONS']['LOCATION']) && !empty($arResult['CONNECTIONS']['GROUP']))
	$arResult['FOR_JS']['CONNECTED']['GROUP'] = array_keys($arResult['CONNECTIONS']['GROUP']);