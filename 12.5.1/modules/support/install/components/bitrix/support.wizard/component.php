<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arDefaultUrlTemplates404 = array(
	"ticket_list" => "index.php",
	"ticket_edit" => "#ID#.php",
);

$arDefaultVariableAliases = Array(
	"ID" => "ID",
);

$arDefaultVariableAliases404 = Array(
);

$arComponentVariables = Array("ID");

$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases, $arParams["VARIABLE_ALIASES"]);
CComponentEngine::InitComponentVariables(false, $arComponentVariables, $arVariableAliases, $arVariables);

$componentPage = "";
$arResult = array();

$end_wizard = $_REQUEST['end_wizard'] || $_REQUEST['end_wizard_x'];

if ($_REQUEST['show_wizard']=='Y' && !$end_wizard)
	$componentPage = "wizard";
elseif (isset($arVariables["UID"]) && intval($arVariables["UID"]) > 0 && 
	(isset($arVariables["ID"]) && intval($arVariables["ID"]) >= 0))
	$componentPage = "profile_view";
elseif ($end_wizard || (isset($arVariables["ID"]) && intval($arVariables["ID"]) >= 0))
{
	$componentPage = "ticket_edit";

	if ($_POST['LAST_SECTION_ID'])
	{
		if (!CModule::IncludeModule('iblock'))
		{	
			ShowError(GetMessage('ERR_NO_IBLOCK'));
			return;
		}
		$rs = CIBlockSection::GetNavChain($arParams['IBLOCK_ID'], $_POST['LAST_SECTION_ID']);
		while($f=$rs->Fetch())
		{
			if ($arParams['INCLUDE_IBLOCK_INTO_CHAIN']=='Y')
				$APPLICATION->AddChainItem($f['NAME']);			

			if (is_array($arParams['SELECTED_SECTIONS']) && $arParams['SECTIONS_TO_CATEGORIES']=='Y')
			{
				foreach($arParams['SELECTED_SECTIONS'] as $k)
					if ($f['ID']==$k)
						$_REQUEST['CATEGORY_ID'] = $arParams['SECTION_'.$k];
			}

			$arResult['PATH'][] = $f['NAME'];

			$arFilter=array(
				"IBLOCK_ID" => $arParams['IBLOCK_ID'],
				"SECTION_ID" => $f['ID'],
				"INCLUDE_SUBSECTIONS" => "N"
			);
			$rsEl = CIBlockElement::GetList(array("sort"=>"asc"),$arFilter);
			while($obEl = $rsEl->GetNextElement())
			{
				$answer = '';
				$arFields = $obEl->GetFields();
				$id = 'wizard_field_'.$arFields['ID'];
				if ($_POST['wizard'][$id])
				{
					if (is_array($_POST['wizard'][$id]))
					{
						$arProp = $obEl->GetProperties();
						foreach($arProp[$arParams['PROPERTY_FIELD_VALUES']]['VALUE'] as $k=>$v)
							$answer[$k] = array($v,$_POST['wizard'][$id][$k]);
					}
					else
						$answer = $_POST['wizard'][$id];
				}
				$arResult['FIELDS'][] = array($arFields['NAME'],$answer);
			}
		}
		if (is_array($arResult['PATH']))
		{
			$arResult['MESSAGE'] .= "<i>" . implode(' > ',$arResult['PATH']) . "</i>\n\n";
			$arResult['DISPLAY_MESSAGE'] .= "<i>" . htmlspecialcharsbx(implode(' > ',$arResult['PATH'])) . "</i>\n\n";
		}

		if (is_array($arResult['FIELDS']))
			foreach($arResult['FIELDS'] as $arField)
			{
				$arResult['MESSAGE'] .= "<b>".$arField[0]."</b>\n";
				$arResult['DISPLAY_MESSAGE'] .= "<b>".htmlspecialcharsbx($arField[0])."</b>\n";

				if (!is_array($arField[1]))
				{
					$arResult['MESSAGE'] .= (trim($arField[1])?$arField[1]:GetMessage('WZ_NOT_SET'))."\n\n";
					$arResult['DISPLAY_MESSAGE'] .= (trim($arField[1])?htmlspecialcharsbx($arField[1]):GetMessage('WZ_NOT_SET'))."\n\n";
				}
				else
				{
					foreach($arField[1] as $vals)
					{
						$arResult['MESSAGE'] .= "\t".$vals[0].": ".$vals[1]."\n";
						$arResult['DISPLAY_MESSAGE'] .= "\t".htmlspecialcharsbx($vals[0].": ".$vals[1])."\n";
					}
					$arResult['MESSAGE'] .= "\n";
					$arResult['DISPLAY_MESSAGE'] .= "\n";
				}
			}

	$_REQUEST['MESSAGE'] = $arResult['MESSAGE'];
	$arResult['DISPLAY_MESSAGE'] = nl2br(str_replace("\t","&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$arResult['DISPLAY_MESSAGE']));

	if ($_POST['wizard']['wz_title'])
		$_REQUEST['TITLE'] = $_POST['wizard']['wz_title'];

	if ($_POST['wizard']['wz_coupon'])
		$_REQUEST['COUPON'] = $_POST['wizard']['wz_coupon'];
	}
}
else
	$componentPage = "ticket_list";

$arResult = array_merge($arResult,
	array(
		"FOLDER" => "",
		"URL_TEMPLATES" => Array(
			"ticket_edit" => htmlspecialcharsbx($APPLICATION->GetCurPage())."?".$arVariableAliases["ID"]."=#ID#",
			"ticket_list" => htmlspecialcharsbx($APPLICATION->GetCurPage()),
		),
		"VARIABLES" => $arVariables, 
		"ALIASES" => $arVariableAliases,
		"BACK_URL"	=>	htmlspecialcharsbx($APPLICATION->GetCurPage()),
		"NEXT_URL"	=>	htmlspecialcharsbx($APPLICATION->GetCurPage())."?".$arVariableAliases["ID"]."=0",
	)
);

$this->IncludeComponentTemplate($componentPage);
?>
