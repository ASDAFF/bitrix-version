<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (is_array($arResult['VALUE']) && count($arResult['VALUE']) > 0)
{
	if(!CModule::IncludeModule("crm"))
		return;


		
	$arParams['ENTITY_TYPE'] = Array();
	if ($arParams['arUserField']['SETTINGS']['LEAD'] == 'Y')
		$arParams['ENTITY_TYPE'][] = 'LEAD';
	if ($arParams['arUserField']['SETTINGS']['CONTACT'] == 'Y')
		$arParams['ENTITY_TYPE'][] = 'CONTACT';
	if ($arParams['arUserField']['SETTINGS']['COMPANY'] == 'Y')
		$arParams['ENTITY_TYPE'][] = 'COMPANY';	
	if ($arParams['arUserField']['SETTINGS']['DEAL'] == 'Y')
		$arParams['ENTITY_TYPE'][] = 'DEAL';

	$arParams['PREFIX'] = false;
	if (count($arParams['ENTITY_TYPE']) > 1)
		$arParams['PREFIX'] = true;

	$arValue = Array();	
	foreach ($arResult['VALUE'] as $value)
	{
		if($arParams['PREFIX'])
		{
			$ar = explode('_', $value);
			$arValue[CUserTypeCrm::GetLongEntityType($ar[0])][] = intval($ar[1]);
		}
		else
		{
			if (is_numeric($value))
				$arValue[$arParams['ENTITY_TYPE'][0]][] = $value;
			else
			{
				$ar = explode('_', $value);
				$arValue[CUserTypeCrm::GetLongEntityType($ar[0])][] = intval($ar[1]);
			}
		}
	}

	$arResult['VALUE'] = Array();
	if ($arParams['arUserField']['SETTINGS']['LEAD'] == 'Y' && isset($arValue['LEAD']) && !empty($arValue['LEAD']))
	{
		$dbRes = CCrmLead::GetListEx(
			array('TITLE' => 'ASC'),
			array('=ID' => $arValue['LEAD']),
			false,
			false,
			array('ID', 'TITLE')
		);
		while ($arRes = $dbRes->Fetch())
		{
			$arResult['VALUE']['LEAD'][$arRes['ID']] = Array(
				'ENTITY_TITLE' => $arRes['TITLE'],
				'ENTITY_LINK' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_lead_show'), array('lead_id' => $arRes['ID']))
			);
		}
	}
	if ($arParams['arUserField']['SETTINGS']['CONTACT'] == 'Y' && isset($arValue['CONTACT']) && !empty($arValue['CONTACT']))
	{
		$hasNameFormatter = method_exists("CCrmContact", "PrepareFormattedName");
		$dbRes = CCrmContact::GetListEx(
			array('LAST_NAME'=>'ASC', 'NAME' => 'ASC'),
			array('=ID' => $arValue['CONTACT']),
			false,
			false,
			$hasNameFormatter
				? array('ID', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME')
				: array('ID', 'FULL_NAME')
		);
		while ($arRes = $dbRes->Fetch())
		{
			if($hasNameFormatter)
			{
				$title = CCrmContact::PrepareFormattedName(
					array(
						'HONORIFIC' => isset($arRes['HONORIFIC']) ? $arRes['HONORIFIC'] : '',
						'NAME' => isset($arRes['NAME']) ? $arRes['NAME'] : '',
						'SECOND_NAME' => isset($arRes['SECOND_NAME']) ? $arRes['SECOND_NAME'] : '',
						'LAST_NAME' => isset($arRes['LAST_NAME']) ? $arRes['LAST_NAME'] : ''
					)
				);
			}
			else
			{
				$title = isset($arRes['FULL_NAME']) ? $arRes['FULL_NAME'] : '';
			}

			$arResult['VALUE']['CONTACT'][$arRes['ID']] = Array(
				'ENTITY_TITLE' => $title,
				'ENTITY_LINK' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_contact_show'), array('contact_id' => $arRes['ID']))
			);
		}
	}
	if ($arParams['arUserField']['SETTINGS']['COMPANY'] == 'Y'
	&& isset($arValue['COMPANY']) && !empty($arValue['COMPANY']))
	{
		$dbRes = CCrmCompany::GetListEx(array('TITLE'=>'ASC'), array('ID' => $arValue['COMPANY']));
		while ($arRes = $dbRes->Fetch())
		{
			$arResult['VALUE']['COMPANY'][$arRes['ID']] = Array(
				'ENTITY_TITLE' => $arRes['TITLE'],
				'ENTITY_LINK' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_company_show'), array('company_id' => $arRes['ID']))
			);
		}
	}
	if ($arParams['arUserField']['SETTINGS']['DEAL'] == 'Y'
	&& isset($arValue['DEAL']) && !empty($arValue['DEAL']))
	{
		$dbRes = CCrmDeal::GetListEx(array('TITLE'=>'ASC'), array('ID' => $arValue['DEAL']));
		while ($arRes = $dbRes->Fetch())
		{
			$arResult['VALUE']['DEAL'][$arRes['ID']] = Array(
				'ENTITY_TITLE' => $arRes['TITLE'],
				'ENTITY_LINK' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_deal_show'), array('deal_id' => $arRes['ID']))
			);
		}
	}

}

?>