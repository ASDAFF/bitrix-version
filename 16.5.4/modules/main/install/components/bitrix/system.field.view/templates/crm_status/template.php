<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
CModule::IncludeModule('crm');

$entityType = $arParams['arUserField']['SETTINGS']['ENTITY_TYPE'];
//fool-proof
if(is_array($entityType))
	$entityType = isset($entityType['ID']) ? $entityType['ID'] : '';
$ar = CCrmStatus::GetStatusList($entityType);
$first = true;
foreach ($arResult["VALUE"] as $res):
	if (!$first):
		?><span class="fields separator"></span><?
	else:
		$first = false;	
	endif;
	?><span class="fields crm_status"><?=(isset($ar[$res])? htmlspecialcharsbx($ar[$res]): '')?></span><?
endforeach;	
?>