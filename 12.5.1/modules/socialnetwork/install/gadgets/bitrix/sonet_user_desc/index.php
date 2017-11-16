<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if(!CModule::IncludeModule("socialnetwork"))
	return false;

$sRatingTemplate = ($arParams["RATING_TYPE"] == "") ? COption::GetOptionString("main", "rating_vote_type", "standart") : $arParams["RATING_TYPE"];

?><h4 class="bx-sonet-user-desc-username"><?=htmlspecialcharsback($arGadgetParams['USER_NAME'])?></h4><?
			
if ($arGadgetParams['CAN_VIEW_PROFILE']):

	?><table width="100%" cellspacing="2" cellpadding="3"><?
	if ($arGadgetParams['FIELDS_MAIN_SHOW'] == "Y"):
		foreach ($arGadgetParams['FIELDS_MAIN_DATA'] as $fieldName => $arUserField):
			if (StrLen($arUserField["VALUE"]) > 0):
				?><tr valign="top">
					<td width="40%"><?= $arUserField["NAME"] ?>:</td>
					<td width="60%"><?if (StrLen($arUserField["SEARCH"]) > 0):?><a href="<?= $arUserField["SEARCH"] ?>"><?endif;?><?= $arUserField["VALUE"] ?><?if (StrLen($arUserField["SEARCH"]) > 0):?></a><?endif;?></td>
				</tr><?
			endif;
		endforeach;
	endif;
?>
<?
	if ($arGadgetParams['PROPERTIES_MAIN_SHOW'] == "Y"):
		foreach ($arGadgetParams['PROPERTIES_MAIN_DATA'] as $fieldName => $arUserField):
			if (is_array($arUserField["VALUE"]) && count($arUserField["VALUE"]) > 0 || !is_array($arUserField["VALUE"]) && StrLen($arUserField["VALUE"]) > 0):
				?><tr valign="top">
					<td width="40%"><?=$arUserField["EDIT_FORM_LABEL"]?>:</td>
					<td width="60%"><?
					$bInChain = ($fieldName == "UF_DEPARTMENT" ? "Y" : "N");
					$GLOBALS["APPLICATION"]->IncludeComponent(
						"bitrix:system.field.view", 
						$arUserField["USER_TYPE"]["USER_TYPE_ID"], 
						array("arUserField" => $arUserField, "inChain" => $bInChain),
						null,
						array("HIDE_ICONS"=>"Y")
					);
					?></td>
				</tr><?
			endif;
		endforeach;
	endif;
?>
<?
	if(is_array($arGadgetParams['MANAGERS']) && !empty($arGadgetParams['MANAGERS'])):
?>
				<tr valign="top">
					<td width="40%"><?echo GetMessage("GD_SONET_USER_DESC_MANAGER")?></td>
					<td width="60%">
<?
foreach($arGadgetParams['MANAGERS'] as $manager):
?>
<div style="margin-bottom:4px;">
<?$APPLICATION->IncludeComponent("bitrix:main.user.link",
		'',
		array(
			"ID" => $manager["ID"],
			"PATH_TO_SONET_MESSAGES_CHAT" => $arParams["PATH_TO_MESSAGES_CHAT"],
			"PATH_TO_SONET_USER_PROFILE" => $arParams["PATH_TO_USER"],
			"PATH_TO_VIDEO_CALL" => $arParams["PATH_TO_VIDEO_CALL"],
			"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
			"SHOW_YEAR" => $arParams["SHOW_YEAR"],
			"CACHE_TYPE" => $arParams["CACHE_TYPE"],
			"CACHE_TIME" => $arParams["CACHE_TIME"],
			"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
			"PATH_TO_CONPANY_DEPARTMENT" => $arParams["~PATH_TO_CONPANY_DEPARTMENT"],
		),
		false, 
		array("HIDE_ICONS" => "Y")
	);
?>
</div>
<?
endforeach;
?>
					</td>
				</tr>
<?
	endif;
?>
<?
	if(is_array($arGadgetParams['DEPARTMENTS']) && !empty($arGadgetParams['DEPARTMENTS'])):
?>
				<tr valign="top">
					<td width="40%"><?echo GetMessage("GD_SONET_USER_DESC_EMPLOYEES")?></td>
					<td width="60%">
<?
foreach($arGadgetParams['DEPARTMENTS'] as $dep):
?>
<a href="<?=$dep['URL']?>"><?=$dep['NAME']?></a><?if($dep['EMPLOYEE_COUNT'] > 0):?><span title="<?echo GetMessage("GD_SONET_USER_DESC_EMPLOYEES_NUM")?>"> (<?=$dep['EMPLOYEE_COUNT']?>)<span><?endif?><br>
<?
endforeach;
?>
					</td>
				</tr>
<?
	endif;
?>
<?

	if ($sRatingTemplate == "standart")
	{
		if (
			array_key_exists("RATING_MULTIPLE", $arGadgetParams)
			&& is_array($arGadgetParams["RATING_MULTIPLE"])
			&& count($arGadgetParams["RATING_MULTIPLE"]) > 0
		):
			foreach($arGadgetParams["RATING_MULTIPLE"] as $arRating):
				?>
				<tr valign="top">
					<td width="40%"><?=$arRating["NAME"]?>:</td>
					<td width="60%"><?=$arRating["VALUE"]?></td>
				</tr>
				<?
			endforeach;
			?>
			<tr valign="top">
				<td width="40%"><?=GetMessage("GD_SONET_USER_DESC_VOTE")?>:</td>
				<td width="60%">
					<?$APPLICATION->IncludeComponent("bitrix:rating.vote","",
						array(
							"ENTITY_TYPE_ID" => "USER",
							"ENTITY_ID" => $arParams["USER_ID"],
							"OWNER_ID" => $arParams["USER_ID"]
						),
						null,
						array("HIDE_ICONS" => "Y")
					);?>
				</td>
			</tr>
			<?
		elseif (strlen($arGadgetParams['RATING_NAME']) > 0):
			?>
			<tr valign="top">
				<td width="40%"><?=$arGadgetParams['RATING_NAME']?>:</td>
				<td width="60%"><?=$arGadgetParams['RATING_VALUE']?></td>
			</tr>
			<tr valign="top">
				<td width="40%"><?=GetMessage("GD_SONET_USER_DESC_VOTE")?>:</td>
				<td width="60%">
					<?$APPLICATION->IncludeComponent("bitrix:rating.vote","",
						array(
							"ENTITY_TYPE_ID" => "USER",
							"ENTITY_ID" => $arParams["USER_ID"],
							"OWNER_ID" => $arParams["USER_ID"]
						),
						null,
						array("HIDE_ICONS" => "Y")
					);?>
				</td>
			</tr>
			<?
		endif;
	}

	?></table>

	<h4 class="bx-sonet-user-desc-contact"><?= GetMessage("GD_SONET_USER_DESC_CONTACT_TITLE") ?></h4>
	<table width="100%" cellspacing="2" cellpadding="3"><?
	if ($arGadgetParams['CAN_VIEW_CONTACTS']):
		$bContactsEmpty = true;
		if ($arGadgetParams['FIELDS_CONTACT_SHOW'] == "Y"):
			foreach ($arGadgetParams['FIELDS_CONTACT_DATA'] as $fieldName => $arUserField):
				if (StrLen($arUserField["VALUE"]) > 0):
					?><tr valign="top">
						<td width="40%"><?= $arUserField["NAME"] ?>:</td>
						<td width="60%"><?if (StrLen($arUserField["SEARCH"]) > 0):?><a href="<?= $arUserField["SEARCH"] ?>"><?endif;?><?= $arUserField["VALUE"] ?><?if (StrLen($arUserField["SEARCH"]) > 0):?></a><?endif;?></td>
					</tr><?
					$bContactsEmpty = false;
				endif;
			endforeach;
		endif;

		if ($arGadgetParams['PROPERTIES_CONTACT_SHOW'] == "Y"):
			foreach ($arGadgetParams['PROPERTIES_CONTACT_DATA'] as $fieldName => $arUserField):
				if (is_array($arUserField["VALUE"]) && count($arUserField["VALUE"]) > 0 || !is_array($arUserField["VALUE"]) && StrLen($arUserField["VALUE"]) > 0):
					?><tr valign="top">
						<td width="40%"><?=$arUserField["EDIT_FORM_LABEL"]?>:</td>
						<td width="60%"><?
						$bInChain = ($fieldName == "UF_DEPARTMENT" ? "Y" : "N");
						$GLOBALS["APPLICATION"]->IncludeComponent(
							"bitrix:system.field.view", 
							$arUserField["USER_TYPE"]["USER_TYPE_ID"], 
							array("arUserField" => $arUserField, "inChain" => $bInChain),
							null,
							array("HIDE_ICONS"=>"Y")
						);
						?></td>
					</tr><?
					$bContactsEmpty = false;
				endif;
			endforeach;
		endif;
		if ($bContactsEmpty):
			?><tr>
				<td colspan="2"><?= GetMessage("GD_SONET_USER_DESC_CONTACT_UNSET") ?></td>
			</tr><?
		endif;

	else:
		?><tr>
			<td colspan="2"><?= GetMessage("GD_SONET_USER_DESC_CONTACT_UNAVAIL") ?></td>
		</tr><?
	endif;
	?></table><?
	
	if ($arGadgetParams['FIELDS_PERSONAL_SHOW'] == "Y" || $arGadgetParams['PROPERTIES_PERSONAL_SHOW'] == "Y"):
		?><h4 class="bx-sonet-user-desc-personal"><?= GetMessage("GD_SONET_USER_DESC_PERSONAL_TITLE") ?></h4>
		<table width="100%" cellspacing="2" cellpadding="3"><?
		$bNoPersonalInfo = true;
		if ($arGadgetParams['FIELDS_PERSONAL_SHOW'] == "Y"):
			foreach ($arGadgetParams['FIELDS_PERSONAL_DATA'] as $fieldName => $arUserField):
				if (StrLen($arUserField["VALUE"]) > 0):
					?><tr valign="top">
						<td width="40%"><?= $arUserField["NAME"] ?>:</td>
						<td width="60%"><?if (StrLen($arUserField["SEARCH"]) > 0):?><a href="<?= $arUserField["SEARCH"] ?>"><?endif;?><?= $arUserField["VALUE"] ?><?if (StrLen($arUserField["SEARCH"]) > 0):?></a><?endif;?></td>
					</tr><?
					$bNoPersonalInfo = false;
				endif;
			endforeach;
		endif;
		if ($arGadgetParams['PROPERTIES_PERSONAL_SHOW'] == "Y"):
			foreach ($arGadgetParams['PROPERTIES_PERSONAL_DATA'] as $fieldName => $arUserField):
				if (is_array($arUserField["VALUE"]) && count($arUserField["VALUE"]) > 0 || !is_array($arUserField["VALUE"]) && StrLen($arUserField["VALUE"]) > 0):
					?><tr valign="top">
						<td width="40%"><?=$arUserField["EDIT_FORM_LABEL"]?>:</td>
						<td width="60%">
						<?
						$bInChain = ($fieldName == "UF_DEPARTMENT" ? "Y" : "N");
						$GLOBALS["APPLICATION"]->IncludeComponent(
							"bitrix:system.field.view", 
							$arUserField["USER_TYPE"]["USER_TYPE_ID"], 
							array("arUserField" => $arUserField, "inChain" => $bInChain),
							null,
							array("HIDE_ICONS"=>"Y")
						);
						?>
						</td>
					</tr><?
					$bNoPersonalInfo = false;
				endif;
			endforeach;
		endif;
		if ($bNoPersonalInfo):
			?><tr>
				<td colspan="2"><?= GetMessage("GD_SONET_USER_DESC_PERSONAL_UNAVAIL") ?></td>
			</tr><?
		endif;
		?></table><?
	endif;
endif;	
?>