<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('sale'))
{
	ShowError(GetMessage("SMOH_SALE_NOT_INSTALLED"));
	return;
}

if (isset($_REQUEST['id']))
	$orderId = $_REQUEST['id'];
else
	return;

$bUserCanViewOrder = CSaleOrder::CanUserViewOrder($orderId, $GLOBALS["USER"]->GetUserGroupArray(), $GLOBALS["USER"]->GetID());

if(!$bUserCanViewOrder)
{
	echo ShowError(GetMessage("SMOH_NO_PERMS2VIEW"));
	return;
}

if (!CModule::IncludeModule('mobileapp'))
{
	ShowError("SMOH_MOBILEAPP_NOT_INSTALLED");
	return;
}

$arResult["ORDER"] = CSaleMobileOrderUtils::getOrderInfoDetail($orderId);

$dbHistory = CSaleOrder::GetHistoryList(
	array("H_DATE_INSERT" => "DESC"),
	array("H_ORDER_ID" => $orderId),
	false,
	false,
	array()
);

$arResult["STATUSES"] = array();
$dbStatusList = CSaleStatus::GetList(
	array("SORT" => "ASC"),
	array("LID" => LANGUAGE_ID),
	false,
	false,
	array("ID", "NAME")
);

while ($arStatusList = $dbStatusList->Fetch())
	$arResult["STATUSES"][htmlspecialcharsbx($arStatusList["ID"])] = htmlspecialcharsbx($arStatusList["NAME"]);

$arResult["PAY_SYSTEMS"] = array();
$dbPaySystemList = CSalePaySystem::GetList(
		array("SORT"=>"ASC"),
		array()
		);
while ($arPaySystemList = $dbPaySystemList->Fetch())
	$arResult["PAY_SYSTEMS"][$arPaySystemList["ID"]] = htmlspecialcharsbx($arPaySystemList["NAME"]);

$userCache = array();
$deliveryCache = array();

while ($arHistory = $dbHistory->Fetch())
{
	if(isset($userCache[$arResult["ORDER"]["USER_ID"]]))
	{
		$arHistory["USER"] = $userCache[$arResult["ORDER"]["USER_ID"]];
	}
	else
	{
		$dbUser = CUser::GetByID($arResult["ORDER"]["USER_ID"]);

		if($arUser = $dbUser->Fetch())
		{

			$arHistory["USER"]["LOGIN"] = $arUser["LOGIN"];
			$arHistory["USER"]["NAME"] = htmlspecialcharsbx($arUser["NAME"]);
			$arHistory["USER"]["LAST_NAME"] = htmlspecialcharsbx($arUser["LAST_NAME"]);

			$userCache[$arResult["ORDER"]["USER_ID"]] = $arHistory["USER"];
		}
	}

	if($arHistory["DELIVERY_ID"] != null )
	{
		$arTmpDelivery = CSaleMobileOrderUtils::getDeliveriesInfo(array($arHistory["DELIVERY_ID"]));
		$arHistory["DELIVERY"] = $arTmpDelivery[$arHistory["DELIVERY_ID"]];
	}

	$arResult["HISTORY"][] = $arHistory;
}

$this->IncludeComponentTemplate();
?>
