<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/include.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/prolog.php");

$crmMode = (defined("BX_PUBLIC_MODE") && BX_PUBLIC_MODE && isset($_REQUEST["CRM_MANAGER_USER_ID"]));

if ($crmMode)
{
	CUtil::DecodeUriComponent($_GET);
	CUtil::DecodeUriComponent($_POST);

	echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"/bitrix/themes/.default/sale.css\" />";
}
//double function from sale.ajax.location/process.js

?>
<script>
function getLocation(country_id, region_id, city_id, arParams, site_id)
{
	BX.showWait();

	property_id = arParams.CITY_INPUT_NAME;

	function getLocationResult(res)
	{
		BX.closeWait();

		var obContainer = document.getElementById('LOCATION_' + property_id);
		if (obContainer)
		{
			obContainer.innerHTML = res;
		}
	}

	arParams.COUNTRY = parseInt(country_id);
	arParams.REGION = parseInt(region_id);
	arParams.SITE_ID = site_id;

	var url = '/bitrix/components/bitrix/sale.ajax.locations/templates/.default/ajax.php';
	BX.ajax.post(url, arParams, getLocationResult)
}
</script>
<?

IncludeModuleLangFile(__FILE__);
ClearVars();

$ID = IntVal($ID);
$COUNT_RECOM_BASKET_PROD = 2;

$saleModulePermissions = $APPLICATION->GetGroupRight("sale");

$arStatusList = False;
$arFilter = array("LID" => LANG, "ID" => "N");
$arGroupByTmpSt = false;
if ($saleModulePermissions < "W")
{
	$arFilter["GROUP_ID"] = $GLOBALS["USER"]->GetUserGroupArray();
	$arFilter["PERM_UPDATE"] = "Y";
	$arGroupByTmpSt = array("ID", "NAME", "MAX" => "PERM_UPDATE");
}
$dbStatusList = CSaleStatus::GetList(
		array(),
		$arFilter,
		$arGroupByTmpSt,
		false,
		array("ID", "NAME")
		);
$arStatusList = $dbStatusList->Fetch();

if ($saleModulePermissions == "D" OR ($saleModulePermissions < "W" AND $arStatusList["PERM_UPDATE"] != "Y"))
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$errorMessage = "";

/*****************************************************************************/
/********************* ORDER FUNCTIONS ***************************************/
/*****************************************************************************/

if (!empty($_REQUEST["dontsave"]))
{
	CSaleOrder::UnLock($ID);


	LocalRedirect("sale_order.php?lang=".LANG.GetFilterParams("filter_", false));
}

/*
 * include functions
 */
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/admin_tool.php");

/*****************************************************************************/
/**************************** SAVE ORDER *************************************/
/*****************************************************************************/
$bVarsFromForm = false;

if ($REQUEST_METHOD == "POST" && $save_order_data == "Y" && empty($dontsave) AND $saleModulePermissions >= "U" AND check_bitrix_sessid())
{
	$ID = IntVal($ID);
	$recalcOrder = "N";

	if (defined("SALE_DEBUG") && SALE_DEBUG)
		CSaleHelper::WriteToLog("order_new.php", array("POST" => $_POST), "ORNW1");

	//buyer type, new or exist
	$btnNewBuyer = "N";
	if ($btnTypeBuyer == "btnBuyerNew")
		$btnNewBuyer = "Y";

	if (isset($_POST["storeCount"]) && intval($_POST["storeCount"]) > 0)
		$useStores = true;
	else
		$useStores = false;

	if (strlen($LID) <= 0)
		$errorMessage .= GetMessage("SOE_EMPTY_SITE")."<br>";

	$BASE_LANG_CURRENCY = CSaleLang::GetLangCurrency($LID);

	$str_PERSON_TYPE_ID = IntVal($buyer_type_id);
	if ($str_PERSON_TYPE_ID <= 0)
		$errorMessage .= GetMessage("SOE_EMPTY_PERS_TYPE")."<br>";

	if (($str_PERSON_TYPE_ID > 0) && !($arPersonType = CSalePersonType::GetByID($str_PERSON_TYPE_ID)))
		$errorMessage .= GetMessage("SOE_PERSON_NOT_FOUND")."<br>";

	$str_STATUS_ID = trim($STATUS_ID);
	if (strlen($str_STATUS_ID) > 0)
	{
		if ($saleModulePermissions < "W")
		{
			$dbStatusList = CSaleStatus::GetList(
				array(),
				array(
					"GROUP_ID" => $GLOBALS["USER"]->GetUserGroupArray(),
					"PERM_STATUS" => "Y",
					"ID" => $str_STATUS_ID
				),
				array("ID", "MAX" => "PERM_STATUS"),
				false,
				array("ID")
			);
			if (!$dbStatusList->Fetch())
				$errorMessage .= str_replace("#STATUS_ID#", $str_STATUS_ID, GetMessage("SOE_NO_STATUS_PERMS"))."<br>";
		}
	}

	$str_PAY_SYSTEM_ID = IntVal($PAY_SYSTEM_ID);
	if ($str_PAY_SYSTEM_ID <= 0)
		$errorMessage .= GetMessage("SOE_PAYSYS_EMPTY")."<br>";
	if (($str_PAY_SYSTEM_ID > 0) && !($arPaySys = CSalePaySystem::GetByID($str_PAY_SYSTEM_ID, $str_PERSON_TYPE_ID)))
		$errorMessage .= GetMessage("SOE_PAYSYS_NOT_FOUND")."<br>";

	if (count($_POST["PRODUCT"]) <= 0)
		$errorMessage .= GetMessage("SOE_EMPTY_ITEMS")."<br>";

	if (isset($DELIVERY_ID) AND $DELIVERY_ID != "")
	{
		$str_DELIVERY_ID = trim($DELIVERY_ID);
		$PRICE_DELIVERY = FloatVal($PRICE_DELIVERY);
	}

	$arCupon = fGetCupon($_POST["CUPON"]);
	if (is_array($arCupon) && count($arCupon) > 0)
		$recalcOrder = "Y";

	$str_ADDITIONAL_INFO = trim($_POST["ADDITIONAL_INFO"]);
	$str_COMMENTS = trim($_POST["COMMENTS"]);

	if (isset($_POST["btnTypeBuyer"]) && $_POST["btnTypeBuyer"] == "btnBuyerNew")
	{
		$user_id = '';
		unset($user_profile);
	}

	$profileName = "";
	if (isset($user_profile) && $user_profile != "" && $btnNewBuyer == "N")
		$userProfileID = IntVal($user_profile);

	//array field send mail
	$FIO = "";
	$rsUser = CUser::GetByID($user_id);
	if($arUser = $rsUser->Fetch())
	{
		if ($arUser["LAST_NAME"] != "")
			$FIO .= $arUser["LAST_NAME"]." ";
		if ($arUser["NAME"] != "")
			$FIO .= $arUser["NAME"];
	}

	$arUserEmail = array("PAYER_NAME" => $FIO, "USER_EMAIL" => $arUser["EMAIL"]);

	$BREAK_NAME = isset($_POST["BREAK_NAME"]) ? $_POST["BREAK_NAME"] : "";
	if ($BREAK_NAME == GetMessage('NEWO_BREAK_NAME'))
		$BREAK_NAME = "";

	$BREAK_LAST_NAME = isset($_POST["BREAK_LAST_NAME"]) ? $_POST["BREAK_LAST_NAME"] : "";
	if ($BREAK_LAST_NAME == GetMessage('NEWO_BREAK_LAST_NAME'))
		$BREAK_LAST_NAME = "";

	$BREAK_SECOND_NAME = isset($_POST["BREAK_SECOND_NAME"]) ? $_POST["BREAK_SECOND_NAME"] : "";
	if ($BREAK_SECOND_NAME == GetMessage('NEWO_BREAK_SECOND_NAME'))
		$BREAK_SECOND_NAME = "";

	if (strlen($errorMessage) <= 0)
	{
		//property order
		$arOrderPropsValues = array();
		$dbOrderProps = CSaleOrderProps::GetList(
			array("SORT" => "ASC"),
			array("PERSON_TYPE_ID" => $str_PERSON_TYPE_ID, "ACTIVE" => "Y"),
			false,
			false,
			array("ID", "NAME", "TYPE", "REQUIED", "IS_LOCATION", "IS_EMAIL", "IS_PROFILE_NAME", "IS_PAYER", "IS_LOCATION4TAX", "CODE", "SORT")
		);
		while ($arOrderProps = $dbOrderProps->Fetch())
		{
			if(!is_array(${"ORDER_PROP_".$arOrderProps["ID"]}))
				$curVal = trim($_POST["ORDER_PROP_".$arOrderProps["ID"]]);
			else
				$curVal = trim($_POST["ORDER_PROP_".$arOrderProps["ID"]]);

			if ($arOrderProps["TYPE"]=="LOCATION")
			{
				$curVal = $_POST["CITY_ORDER_PROP_".$arOrderProps["ID"]];
			}
			if ($arOrderProps["IS_PAYER"] == "Y")
			{
				if (strlen($curVal) <= 0 && strlen($BREAK_NAME) > 0 && strlen($BREAK_LAST_NAME) > 0)
					$curVal = $BREAK_NAME." ".$BREAK_LAST_NAME;
			}
			if ($arOrderProps["IS_EMAIL"] == "Y")
			{
				$arUserEmail["USER_EMAIL"] = trim($curVal);
			}
			if ($arOrderProps["IS_PROFILE_NAME"] == "Y")
			{
				$profileName = $curVal;
			}

			if (
				($arOrderProps["IS_LOCATION"]=="Y" || $arOrderProps["IS_LOCATION4TAX"]=="Y")
				&& IntVal($curVal) <= 0
				||
				($arOrderProps["IS_PROFILE_NAME"]=="Y" || $arOrderProps["IS_PAYER"]=="Y")
				&& strlen($curVal) <= 0
				||
				$arOrderProps["REQUIED"]=="Y"
				&& $arOrderProps["TYPE"]=="LOCATION"
				&& IntVal($curVal) <= 0
				||
				$arOrderProps["REQUIED"]=="Y"
				&& ($arOrderProps["TYPE"]=="TEXT" || $arOrderProps["TYPE"]=="TEXTAREA" || $arOrderProps["TYPE"]=="RADIO" || $arOrderProps["TYPE"]=="SELECT")
				&& strlen($curVal) <= 0
				||
				($arOrderProps["REQUIED"]=="Y"
				&& $arOrderProps["TYPE"]=="MULTISELECT"
				&& empty($curVal))
				)
			{
				$errorMessage .= str_replace("#NAME#", $arOrderProps["NAME"], GetMessage("SOE_EMPTY_PROP"))."<br>";
			}

			if ($arOrderProps["TYPE"] == "MULTISELECT")
			{
				$curVal = "";
				$countOrderProp = count($_POST["ORDER_PROP_".$arOrderProps["ID"]]);
				for ($i = 0; $i < $countOrderProp; $i++)
				{
					if ($i > 0)
						$curVal .= ",";

					$curVal .= $_POST["ORDER_PROP_".$arOrderProps["ID"]][$i];
				}
			}

			if ($arOrderProps["TYPE"] == "CHECKBOX" && strlen($curVal) <= 0 && $arOrderProps["REQUIED"] != "Y")
			{
				$curVal = "N";
			}

			$arOrderPropsValues[$arOrderProps["ID"]] = $curVal;
		}
	}

	//create a new user
	if ($btnNewBuyer == "Y" && strlen($errorMessage) <= 0)
	{
		if (strlen($NEW_BUYER_EMAIL) <= 0)
		{
			$emailId = '';
			$dbProperties = CSaleOrderProps::GetList(
				array("ID" => "ASC"),
				array("PERSON_TYPE_ID" => $str_PERSON_TYPE_ID, "ACTIVE" => "Y", "IS_EMAIL" => "Y"),
				false,
				false,
				array("ID")
			);
			while ($arProperties = $dbProperties->Fetch())
			{
				if ($emailId == '')
					$emailId = $arProperties["ID"];

				if ($arProperties["REQUIED"] == "Y")
					$emailId = $arProperties["ID"];
			}
			$NEW_BUYER_EMAIL = ${"ORDER_PROP_".$emailId};
		}

		if (strlen($NEW_BUYER_EMAIL) <= 0)
			$errorMessage .= GetMessage("NEWO_BUYER_REG_ERR_MAIL");

		//take default value PHONE for register user
		$dbOrderProps = CSaleOrderProps::GetList(
			array(),
			array("PERSON_TYPE_ID" => $str_PERSON_TYPE_ID, "ACTIVE" => "Y", "CODE" => "PHONE"),
			false,
			false,
			array("ID")
		);
		$arOrderProps = $dbOrderProps->Fetch();
		$NEW_BUYER_PHONE = "";
		if (count($arOrderProps) > 0)
			$NEW_BUYER_PHONE = trim($_POST["ORDER_PROP_".$arOrderProps["ID"]]);

		$NEW_BUYER_NAME = isset($_POST["NEW_BUYER_NAME"]) ? $_POST["NEW_BUYER_NAME"] : "";
		$NEW_BUYER_LAST_NAME = isset($_POST["NEW_BUYER_LAST_NAME"]) ? $_POST["NEW_BUYER_LAST_NAME"] : "";
		$NEW_BUYER_SECOND_NAME = isset($_POST["NEW_BUYER_SECOND_NAME"]) ? $_POST["NEW_BUYER_SECOND_NAME"] : "";

		if ($NEW_BUYER_NAME == "" && $NEW_BUYER_LAST_NAME == "")
		{
			$NEW_BUYER_NAME = $BREAK_NAME;
			$NEW_BUYER_LAST_NAME = $BREAK_LAST_NAME;
			$NEW_BUYER_SECOND_NAME = $BREAK_SECOND_NAME;
		}

		if ($NEW_BUYER_NAME == "" || $NEW_BUYER_LAST_NAME == "")
			$errorMessage .= GetMessage("NEWO_BUYER_REG_ERR_NAME")."<br>";

		$NEW_BUYER_FIO = $NEW_BUYER_LAST_NAME." ".$NEW_BUYER_NAME." ".$NEW_BUYER_SECOND_NAME;

		if (strlen($errorMessage) <= 0)
		{
			$userRegister = array(
				"NAME" => $NEW_BUYER_NAME,
				"LAST_NAME" => $NEW_BUYER_LAST_NAME,
				"SECOND_NAME" => $NEW_BUYER_SECOND_NAME,
				"PERSONAL_MOBILE" => $NEW_BUYER_PHONE
			);

			$arPersonal = array("PERSONAL_MOBILE" => $NEW_BUYER_PHONE);

			$user_id = CSaleUser::DoAutoRegisterUser($NEW_BUYER_EMAIL, $userRegister, $LID, $arErrors, $arPersonal);
			if (count($arErrors) > 0)
			{
				foreach($arErrors as $val)
					$errorMessage .= $val["TEXT"];
			}
			else
			{
				$userProfileID = 0;
				$rsUser = CUser::GetByID($user_id);
				$arUser = $rsUser->Fetch();

				$userNew = str_replace("#FIO#", "(".$arUser["LOGIN"].")".(($arUser["NAME"] != "") ? " ".$arUser["NAME"] : "").(($arUser["LAST_NAME"] != "") ? " ".$arUser["LAST_NAME"] : ""), GetMessage("NEWO_BUYER_REG_OK"));
			}
		}
	}

	$arUserEmail["PAYER_NAME"] = $NEW_BUYER_FIO;
	if (!isset($userProfileID))
		$profileName = "";

	$str_USER_ID = IntVal($user_id);
	if ($str_USER_ID <= 0 && strlen($errorMessage) <= 0)
	{
		$str_USER_ID = "";
		$errorMessage .= GetMessage("SOE_EMPTY_USER")."<br>";
	}
	elseif ($str_USER_ID > 0 && strlen($errorMessage) <= 0)
	{
		$rsUser = CUser::GetByID($str_USER_ID);
		if (!$rsUser->Fetch())
			$errorMessage .= GetMessage("NEWO_ERR_EMPTY_USER")."<br>";
	}

	if (isset($_POST["PRODUCT"]) && count($_POST["PRODUCT"]) > 0)
	{
		CModule::IncludeModule('catalog');
		foreach ($_POST["PRODUCT"] as $key => $val)
		{
			if (IntVal($val["PRODUCT_ID"]) > 0 && $val["MODULE"] == 'catalog')
			{
				if ($arCatalogProduct = CCatalogProduct::GetByID($val["PRODUCT_ID"]))
				{
					$dbBasketItems = CSaleBasket::GetList(array(), array("ID" => $val["ID"]), false, false, array('QUANTITY'));
					$arItems = $dbBasketItems->Fetch();

					if (floatval($val["QUANTITY"]) > floatval($arItems["QUANTITY"])
							&& $arCatalogProduct["CAN_BUY_ZERO"]!="Y"
							&& ($arCatalogProduct["QUANTITY_TRACE"]=="Y")
							//&& doubleval($arCatalogProduct["QUANTITY"])<=0)
							//TODO - QUANTITY_RESERVED
							&& floatval($val["QUANTITY"] - $arItems["QUANTITY"]) > floatval($arCatalogProduct["QUANTITY"] + $arCatalogProduct["QUANTITY_RESERVED"])
						)
					{
						$errorMessage .= str_replace("#NAME#", $val['NAME'], GetMessage("NEWO_ERR_PRODUCT_NULL_BALANCE"));
					}
				}
			}
		}
	}

	//saving
	if (strlen($errorMessage) <= 0)
	{
		//send new user mail
		if ($btnNewBuyer == "Y" && strlen($userNew) > 0)
			CUser::SendUserInfo($str_USER_ID, $LID, $userNew, true);

		$arShoppingCart = array();
		$arOrderProductPrice = fGetUserShoppingCart($_POST["PRODUCT"], $LID, $recalcOrder);

		foreach ($arOrderProductPrice as &$arItem)
		{
			$arItem["ID_TMP"] = $arItem["ID"];
			unset($arItem["ID"]);
		}
		unset($arItem);

		$tmpOrderId = ($ID == 0) ? 0 : $ID;

		$arOrderOptions = array(
			'CART_FIX' => (isset($_REQUEST['CART_FIX']) && 'Y' == $_REQUEST['CART_FIX'] ? 'Y' : 'N')
		);

		if ('Y' == $arOrderOptions['CART_FIX'])
		{
			$arShoppingCart = $arOrderProductPrice;
		}
		else
		{
			$arShoppingCart = CSaleBasket::DoGetUserShoppingCart($LID, $str_USER_ID, $arOrderProductPrice, $arErrors, $arCupon, $tmpOrderId);
		}

		foreach ($arOrderProductPrice as $key => &$arItem)
		{
			$arItem["ID"] = $arItem["ID_TMP"];
			unset($arItem["ID_TMP"]);

			//$arShoppingCart[$key]["ID"] = $arItem["ID"];
		}
		unset($arItem);

		foreach ($arShoppingCart as &$v)
		{
			$v["ID"] = $v["ID_TMP"];
			unset($v["ID_TMP"]);
		}
		unset($v);

		$arErrors = array();
		$arWarnings = array();

		if (count($arShoppingCart) > 0)
		{
			foreach($arOrderProductPrice as $key => $val)
			{
				if ($val["NAME"] != $arShoppingCart[$key]["NAME"] AND $val["PRODUCT_ID"] == $arShoppingCart[$key]["PRODUCT_ID"])
					$arShoppingCart[$key]["NAME"] = $val["NAME"];

				if ($val["NOTES"] != $arShoppingCart[$key]["NOTES"] AND $val["PRODUCT_ID"] == $arShoppingCart[$key]["PRODUCT_ID"])
					$arShoppingCart[$key]["NOTES"] = $val["NOTES"];
			}
		}

		//order parameters
		$arOrder = CSaleOrder::DoCalculateOrder(
			$LID,
			$str_USER_ID,
			$arShoppingCart,
			$str_PERSON_TYPE_ID,
			$arOrderPropsValues,
			$str_DELIVERY_ID,
			$str_PAY_SYSTEM_ID,
			$arOrderOptions,
			$arErrors,
			$arWarnings);

		//change delivery price
		if (DoubleVal($arOrder["DELIVERY_PRICE"]) != $PRICE_DELIVERY)
		{
			$arOrder["PRICE"] = ($arOrder["PRICE"] - $arOrder["DELIVERY_PRICE"]) + $PRICE_DELIVERY;
			$arOrder["DELIVERY_PRICE"] = $PRICE_DELIVERY;
			$arOrder["PRICE_DELIVERY"] = $PRICE_DELIVERY;
		}

		if (count($arShoppingCart) <= 0 && count($arOrderProductPrice) > 0)
			$errorMessage .= GetMessage('NEWO_ERR_BUSKET_NULL')."<br>";
		else
		{
			if (count($arWarnings) > 0)
			{
				foreach ($arWarnings as $val)
					$errorMessage .= $val["TEXT"]."<br>";
			}
			if (count($arErrors) > 0)
			{
				foreach ($arErrors as $val)
					$errorMessage .= $val["TEXT"]."<br>";
			}
		}
	}

	//prelimenary barcode and store quantity saving
	if (strlen($errorMessage) <= 0)
	{
		//merge quantities from the arrays with the same stores
		// if (!function_exists(MergeStoresWithSameID))
		// {
		// 	function MergeStoresWithSameID($arStores)
		// 	{
		// 		$arRes = array();
		// 		$arUsedIDs = array();

		// 		foreach ($arStores as $index => $arStore)
		// 		{
		// 			if (!array_key_exists($arStore["ID"], $arUsedIDs))
		// 			{
		// 				$arRes[$index] = $arStore;
		// 				$arUsedIDs[$arStore["ID"]] = $index;
		// 			}
		// 			else
		// 			{
		// 				$arRes[$arUsedIDs[$arStore["ID"]]]["QUANTITY"] += $arStore["QUANTITY"];
		// 				$arRes[$arUsedIDs[$arStore["ID"]]]["BARCODE"] = array_merge($arRes[$arUsedIDs[$arStore["ID"]]]["BARCODE"], $arStore["BARCODE"]);
		// 			}
		// 		}
		// 		return $arRes;
		// 	}
		// }

		//saving store / barcode data (calculating which records should be deleted / added / updated)

		$arStoreBarcodeOrderFormData = array();
		if ($useStores && (!isset($_POST["ORDER_DEDUCTED"]) || $_POST["ORDER_DEDUCTED"] == "N") && ($DEDUCTED == "Y" || $hasSavedBarcodes)) //not deducted yet
		{
			$bErrorFound = false;
			foreach ($_REQUEST["PRODUCT"] as $basketId => &$arProduct)
			{
				if (is_array($arProduct["STORES"]) && count($arProduct["STORES"]) > 0)
				{
					//check if store info contains all necessary fields
					foreach ($arProduct["STORES"] as $recId => $arRecord)
					{
						if (!isset($arRecord["STORE_ID"]) || intVal($arRecord["STORE_ID"]) < 0 || (!isset($arRecord["AMOUNT"])) || intVal($arRecord["AMOUNT"]) < 0)
						{
							$errorMessage .= GetMessage("NEWO_ERR_STORE_WRONG_INFO_SAVING", array("#PRODUCT_NAME#" => $arProduct["NAME"]))."<br>";
							$bErrorFound = true;
							break;
						}
					}
					if ($bErrorFound)
						break;

					//TODO
					// $arProduct["STORES"] = MergeStoresWithSameID($arProduct["STORES"]);

					//if array item is in the basket, not newly added product
					if (isset($arProduct["BUSKET_ID"]) && intval($arProduct["BUSKET_ID"]) > 0)
					{
						if ($arProduct["BARCODE_MULTI"] == "N") //saving only store quantity info
						{
							$arStoreSavedRecords = array();
							$arStoreFormRecords = array();
							$arStoreIDToAdd = array();
							$arStoreIDToDelete = array();

							$dbStoreBarcode = CSaleStoreBarcode::GetList(
								array(),
								array(
									"BASKET_ID" => $arProduct["BUSKET_ID"],
								),
								false,
								false,
								array("ID", "BASKET_ID", "BARCODE", "QUANTITY", "STORE_ID")
							);
							while ($arStoreBarcode = $dbStoreBarcode->GetNext())
							{
								$arStoreSavedRecords[$arStoreBarcode["STORE_ID"]] = $arStoreBarcode;
							}

							foreach ($arProduct["STORES"] as $index => $arStore)
							{
								$arStoreFormRecords[$arStore["STORE_ID"]] = $arStore;

								if (!in_array($arStore["STORE_ID"], array_keys($arStoreSavedRecords)))
									$arStoreIDToAdd[] = $arStore["STORE_ID"];
							}

							foreach ($arStoreSavedRecords as $index => $arRecord)
							{
								if (!in_array($arRecord["STORE_ID"], array_keys($arStoreFormRecords)))
									$arStoreIDToDelete[$arRecord["ID"]] = $arRecord["STORE_ID"];
							}

							foreach ($arStoreIDToDelete as $id => $storeId)
							{
								CSaleStoreBarcode::Delete($id);
							}

							foreach ($arStoreIDToAdd as $addId)
							{
								$arStoreBarcodeFields = array(
									"BASKET_ID"   => $arProduct["BUSKET_ID"],
									"BARCODE"     => "",
									"STORE_ID"    => $addId,
									"QUANTITY"    => $arStoreFormRecords[$addId]["QUANTITY"],
									"CREATED_BY"  => ((intval($GLOBALS["USER"]->GetID())>0) ? IntVal($GLOBALS["USER"]->GetID()) : ""),
									"MODIFIED_BY" => ((intval($GLOBALS["USER"]->GetID())>0) ? IntVal($GLOBALS["USER"]->GetID()) : "")
								);

								CSaleStoreBarcode::Add($arStoreBarcodeFields);
							}

							foreach ($arStoreSavedRecords as $storeId => $arStoreBarcodeRecord)
							{
								if (!in_array($storeId, $arStoreIDToAdd) && !in_array($storeId, $arStoreIDToDelete))
								{
									if ($arStoreBarcodeRecord["QUANTITY"] != $arStoreFormRecords[$arStoreBarcodeRecord["STORE_ID"]]["QUANTITY"])
									{
										CSaleStoreBarcode::Update(
											$arStoreBarcodeRecord["ID"],
											array(
												"QUANTITY" => $arStoreFormRecords[$arStoreBarcodeRecord["STORE_ID"]]["QUANTITY"],
												"MODIFIED_BY" => ((intval($GLOBALS["USER"]->GetID())>0) ? IntVal($GLOBALS["USER"]->GetID()) : "")
											)
										);
									}
								}
							}
							$arProduct["HAS_SAVED_QUANTITY"] = "Y";
						}
						else //BARCODE_MULTI = Y
						{
							$arStoreFormRecords = array();
							foreach ($arProduct["STORES"] as $index => $arStore)
							{
								$arStoreFormRecords[$arStore["STORE_ID"]] = $arStore;
							}

							//deleting all previous records
							$dbStoreBarcode = CSaleStoreBarcode::GetList(
								array(),
								array(
									"BASKET_ID" => $arProduct["BUSKET_ID"],
								),
								false,
								false,
								array("ID", "BASKET_ID", "BARCODE", "QUANTITY", "STORE_ID")
							);
							while ($arStoreBarcode = $dbStoreBarcode->GetNext())
							{
								CSaleStoreBarcode::Delete($arStoreBarcode["ID"]);
							}

							//adding new values
							foreach ($arStoreFormRecords as $arStoreFormRecord)
							{
								if (isset($arStoreFormRecord["BARCODE"]) && isset($arStoreFormRecord["BARCODE_FOUND"]))
								{
									foreach ($arStoreFormRecord["BARCODE"] as $barcodeId => $barcodeValue)
									{
										//save only non-empty and valid barcodes
										if (strlen($barcodeValue) > 0 &&  $arStoreFormRecord["BARCODE_FOUND"][$barcodeId] == "Y")
										{
											$arStoreBarcodeFields = array(
												"BASKET_ID"   => $arProduct["BUSKET_ID"],
												"BARCODE"     => $barcodeValue,
												"STORE_ID"    => $arStoreFormRecord["STORE_ID"],
												"QUANTITY"    => 1,
												"CREATED_BY"  => ((intval($GLOBALS["USER"]->GetID())>0) ? IntVal($GLOBALS["USER"]->GetID()) : ""),
												"MODIFIED_BY" => ((intval($GLOBALS["USER"]->GetID())>0) ? IntVal($GLOBALS["USER"]->GetID()) : "")
											);

											CSaleStoreBarcode::Add($arStoreBarcodeFields);
										}
									}
								}
							}
							$arProduct["HAS_SAVED_QUANTITY"] = "Y";
						}

						$arStoreBarcodeOrderFormData[$basketId] = $arProduct["STORES"];
					}
				}
			}
			unset($arProduct);
		}

		//newly added products info
		if ($useStores)
		{
			foreach ($_REQUEST["PRODUCT"] as $basketId => $arProduct)
			{
				if (isset($arProduct["NEW_PRODUCT"]))
					$arStoreBarcodeOrderFormData["new".$basketId] = $arProduct["STORES"];
			}
		}
	}

	if (strlen($errorMessage) <= 0)
	{
		//another order parameters
		$arAdditionalFields = array(
			"USER_DESCRIPTION" => $_POST["USER_DESCRIPTION"],
			"ADDITIONAL_INFO" => $str_ADDITIONAL_INFO,
			"COMMENTS" => $str_COMMENTS,
		);

		if (count($arOrder) > 0)
		{
			$arErrors = array();
			$OrderNewSendEmail = false;

			$arOldOrder = CSaleOrder::GetByID($ID);

			if ($ID <= 0 || $arOldOrder["STATUS_ID"] == $str_STATUS_ID)
				$arAdditionalFields["STATUS_ID"] = $str_STATUS_ID;

			$bSaveBarcodes = ($hasSavedBarcodes || $DEDUCTED == "Y") ? true : false;

			$tmpID = CSaleOrder::DoSaveOrder($arOrder, $arAdditionalFields, $ID, $arErrors, $arCupon, $arStoreBarcodeOrderFormData, $bSaveBarcodes);

			//delete from basket
			if ($tmpID > 0)
			{
				foreach($_POST["PRODUCT"] as $key => $val)
				{
					if (!isset($val["BUSKET_ID"]) && intVal($val["BUSKET_ID"]) <= 0)
					{
						$dbBasket = CSaleBasket::GetList(
							array(),
							array(
								"ORDER_ID" => "NULL",
								"PRODUCT_ID" => $val["PRODUCT_ID"],
								"USER_ID" => $str_USER_ID,
								"LID" => $LID
							),
							false,
							false,
							array("ID")
						);
						$arBasket = $dbBasket->Fetch();
						if (count($arBasket) > 0)
							CSaleBasket::Delete($arBasket["ID"]);
					}
				}
			}

			if ($ID <= 0)
				$OrderNewSendEmail = true;
			else
			{
				if ($arOldOrder["STATUS_ID"] != $str_STATUS_ID)
					CSaleOrder::StatusOrder($ID, $str_STATUS_ID);
			}

			$ID = $tmpID;

			if ($ID > 0 AND count($arErrors) <= 0)
			{
				$CANCELED = trim($_POST["CANCELED"]);
				$REASON_CANCELED = trim($_POST["REASON_CANCELED"]);
				if ($CANCELED != "Y")
					$CANCELED = "N";
				$arOrder2Update = Array();

				if ($arOldOrder["CANCELED"] != $CANCELED)
				{
					$bUserCanCancelOrder = CSaleOrder::CanUserCancelOrder($ID, $GLOBALS["USER"]->GetUserGroupArray(), $GLOBALS["USER"]->GetID());

					$errorMessageTmp = "";

					if (!$bUserCanCancelOrder)
						$errorMessageTmp .= GetMessage("SOD_NO_PERMS2CANCEL").". ";

					if (strlen($errorMessageTmp) <= 0)
					{
						if (!CSaleOrder::CancelOrder($ID, $CANCELED, $REASON_CANCELED))
						{
							if ($ex = $APPLICATION->GetException())
							{
								if ($ex->GetID() != "ALREADY_FLAG")
									$errorMessageTmp .= $ex->GetString();
							}
							else
								$errorMessageTmp .= GetMessage("ERROR_CANCEL_ORDER").". ";
						}
					}

					if ($errorMessageTmp != "")
						$arErrors[] = $errorMessageTmp;
				}
				else
				{
					if($arOldOrder["REASON_CANCELED"] != $REASON_CANCELED)
						$arOrder2Update["REASON_CANCELED"] = $REASON_CANCELED;
				}
			}

			if ($ID > 0 AND count($arErrors) <= 0)
			{
				$PAYED = trim($_POST["PAYED"]);
				if ($PAYED != "Y")
					$PAYED = "N";
				$PAY_VOUCHER_NUM = trim($_POST["PAY_VOUCHER_NUM"]);
				$PAY_VOUCHER_DATE = trim($_POST["PAY_VOUCHER_DATE"]);
				$PAY_FROM_ACCOUNT = trim($_POST["PAY_FROM_ACCOUNT"]);
				$PAY_FROM_ACCOUNT_BACK = trim($_POST["PAY_FROM_ACCOUNT_BACK"]);

				if ($arOldOrder["PAYED"] != $PAYED)
				{
					$bUserCanPayOrder = CSaleOrder::CanUserChangeOrderFlag($ID, "P", $GLOBALS["USER"]->GetUserGroupArray());
					$errorMessageTmp = "";

					if (!$bUserCanPayOrder)
						$errorMessageTmp .= GetMessage("SOD_NO_PERMS2PAYFLAG").". ";

					if (strlen($errorMessageTmp) <= 0)
					{
						$arAdditionalFields = array(
							"PAY_VOUCHER_NUM" => ((strlen($PAY_VOUCHER_NUM) > 0) ? $PAY_VOUCHER_NUM : False),
							"PAY_VOUCHER_DATE" => ((strlen($PAY_VOUCHER_DATE) > 0) ? $PAY_VOUCHER_DATE : False)
						);

						$bWithdraw = true;
						$bPay = true;
						if ($PAY_CURRENT_ACCOUNT == "Y")
						{
							$dbUserAccount = CSaleUserAccount::GetList(
							array(),
							array(
								"USER_ID" => $arOrder["USER_ID"],
								"CURRENCY" => $arOrder["CURRENCY"],
								)
							);
							if ($arUserAccount = $dbUserAccount->Fetch())
							{
								if (DoubleVal($arUserAccount["CURRENT_BUDGET"]) >= $arOrder["PRICE"])
									$bPay = false;
							}
						}
						if ($PAYED == "N" && $PAY_FROM_ACCOUNT_BACK != "Y")
							$bWithdraw = false;

						if (!CSaleOrder::PayOrder($ID, $PAYED, $bWithdraw, $bPay, 0, $arAdditionalFields))
						{
							if ($ex = $APPLICATION->GetException())
							{
								if ($ex->GetID() != "ALREADY_FLAG")
									$errorMessageTmp .= $ex->GetString();
							}
							else
								$errorMessageTmp .= GetMessage("ERROR_PAY_ORDER").". ";
						}

						if ($errorMessageTmp != "")
							$arErrors[] = $errorMessageTmp;
					}
				}
				else
				{
					if($arOldOrder["PAY_VOUCHER_NUM"] != $PAY_VOUCHER_NUM)
						$arOrder2Update["PAY_VOUCHER_NUM"] = ((strlen($PAY_VOUCHER_NUM) > 0) ? $PAY_VOUCHER_NUM : False);
					if($arOldOrder["PAY_VOUCHER_DATE"] != $PAY_VOUCHER_DATE)
						$arOrder2Update["PAY_VOUCHER_DATE"] = ((strlen($PAY_VOUCHER_DATE) > 0) ? $PAY_VOUCHER_DATE : False);
				}
			}

			if ($ID > 0 AND count($arErrors) <= 0)
			{
				$ALLOW_DELIVERY = trim($_POST["ALLOW_DELIVERY"]);
				if ($ALLOW_DELIVERY != "Y")
					$ALLOW_DELIVERY = "N";
				$DELIVERY_DOC_NUM = trim($_POST["DELIVERY_DOC_NUM"]);
				$DELIVERY_DOC_DATE = trim($_POST["DELIVERY_DOC_DATE"]);

				if ($arOldOrder["ALLOW_DELIVERY"] != $ALLOW_DELIVERY)
				{
					$bUserCanDeliverOrder = CSaleOrder::CanUserChangeOrderFlag($ID, "D", $GLOBALS["USER"]->GetUserGroupArray());
					$errorMessageTmp = "";

					if (!$bUserCanDeliverOrder)
						$errorMessageTmp .= GetMessage("SOD_NO_PERMS2DELIV").". ";

					if (strlen($errorMessageTmp) <= 0)
					{
						$arAdditionalFields = array(
							"DELIVERY_DOC_NUM" => ((strlen($DELIVERY_DOC_NUM) > 0) ? $DELIVERY_DOC_NUM : False),
							"DELIVERY_DOC_DATE" => ((strlen($DELIVERY_DOC_DATE) > 0) ? $DELIVERY_DOC_DATE : False)
						);

						if (!CSaleOrder::DeliverOrder($ID, $ALLOW_DELIVERY, 0, $arAdditionalFields))
						{
							if ($ex = $APPLICATION->GetException())
							{
								if ($ex->GetID() != "ALREADY_FLAG")
									$errorMessageTmp .= $ex->GetString();
							}
							else
								$errorMessageTmp .= GetMessage("ERROR_DELIVERY_ORDER").". ";
						}
					}

					if ($errorMessageTmp != "")
						$arErrors[] = $errorMessageTmp;
				}
				else
				{
					if($arOldOrder["DELIVERY_DOC_NUM"] != $DELIVERY_DOC_NUM)
						$arOrder2Update["DELIVERY_DOC_NUM"] = ((strlen($DELIVERY_DOC_NUM) > 0) ? $DELIVERY_DOC_NUM : False);
					if($arOldOrder["DELIVERY_DOC_DATE"] != $DELIVERY_DOC_DATE)
						$arOrder2Update["DELIVERY_DOC_DATE"] = ((strlen($DELIVERY_DOC_DATE) > 0) ? $DELIVERY_DOC_DATE : False);
				}
			}

			//set mark
			if ($ID > 0 AND count($arErrors) <= 0)
			{
				$MARKED = trim($_POST["MARKED"]);
				$REASON_MARKED = trim($_POST["REASON_MARKED"]);
				if ($MARKED != "Y")
					$MARKED = "N";
				$arOrder2Update = Array();

				if (($arOldOrder["MARKED"] != $MARKED) || ($arOldOrder["MARKED"] == "Y" && $arOldOrder["REASON_MARKED"] != $REASON_MARKED))
				{
					$bUserCanMarkOrder = CSaleOrder::CanUserMarkOrder($ID, $GLOBALS["USER"]->GetUserGroupArray(), $GLOBALS["USER"]->GetID());

					$errorMessageTmp = "";

					if (!$bUserCanMarkOrder)
						$errorMessageTmp .= GetMessage("SOD_NO_PERMS2MARK").". ";

					if (strlen($errorMessageTmp) <= 0)
					{
						if ($MARKED == "Y")
							$rs = CSaleOrder::SetMark($ID, $REASON_MARKED, ((intval($GLOBALS["USER"]->GetID())>0) ? IntVal($GLOBALS["USER"]->GetID()) : 0));
						else
							$rs = CSaleOrder::UnsetMark($ID, ((intval($GLOBALS["USER"]->GetID())>0) ? IntVal($GLOBALS["USER"]->GetID()) : 0));

						if (!$rs)
						{
							if ($ex = $APPLICATION->GetException())
							{
								if ($ex->GetID() != "ALREADY_FLAG")
									$errorMessageTmp .= $ex->GetString();
							}
							else
								$errorMessageTmp .= GetMessage("ERROR_MARK_ORDER").". ";
						}
					}

					if ($errorMessageTmp != "")
						$arErrors[] = $errorMessageTmp;
				}
				else
				{
					if($arOldOrder["REASON_MARKED"] != $REASON_MARKED)
						$arOrder2Update["REASON_MARKED"] = $REASON_MARKED;
				}
			}

			if ($ID > 0 AND count($arErrors) <= 0)
			{
				$DEDUCTED = trim($_POST["DEDUCTED"]);
				if ($DEDUCTED != "Y")
					$DEDUCTED = "N";
				$REASON_UNDO_DEDUCTED = trim($_POST["REASON_UNDO_DEDUCTED"]);
				$arOrder2Update = Array();

				if ((is_array($arOldOrder) && ($arOldOrder["DEDUCTED"] != $DEDUCTED)) || (!is_array($arOldOrder) && ($DEDUCTED == "Y")))
				{
					$bUserCanDeductOrder = CSaleOrder::CanUserChangeOrderFlag($ID, "PERM_DEDUCTION", $GLOBALS["USER"]->GetUserGroupArray());

					$errorMessageTmp = "";

					if (!$bUserCanDeductOrder)
						$errorMessageTmp .= GetMessage("SOD_NO_PERMS2DEDUCT").". ";

					if ($useStores)
					{
						//check if total ordered quantity = quantity on stores
						$sumQuantityOnStores = array();
						if (strlen($errorMessageTmp) <= 0 && $DEDUCTED == "Y")
						{
							foreach ($_POST["PRODUCT"] as $id => $arProduct)
							{
								if (is_array($arProduct["STORES"]) && count($arProduct["STORES"]) > 0)
								{
									$sumQuantityOnStores[$id] = 0;
									foreach ($arProduct["STORES"] as $index => $arStore)
									{
										$sumQuantityOnStores[$id] += $arStore["QUANTITY"];
									}

									if ($sumQuantityOnStores[$id] != $arProduct["QUANTITY"])
									{
										$errorMessageTmp .= GetMessage("NEWO_ERR_STORE_QUANTITY_NOT_EQUAL_TOTAL_QUANTITY", array("#PRODUCT_NAME#" => $arProduct["NAME"]))."<br>";
										break;
									}
								}
								else
								{
									$errorMessageTmp .= GetMessage("NEWO_ERR_STORE_WRONG_INFO", array("#PRODUCT_NAME#" => $arProduct["NAME"]))."<br>";
									break;
								}
							}
						}

						//check if barcodes are valid for deduction
						if (strlen($errorMessageTmp) <= 0 && $DEDUCTED == "Y") //TODO? && $_POST["HAS_PRODUCTS_WITH_BARCODE_MULTI"] == "Y")
						{
							$bErrorFound = false;
							foreach ($_POST["PRODUCT"] as $id => $arProduct)
							{
								if ($arProduct["BARCODE_MULTI"] == "Y" && is_array($arProduct["STORES"]) && count($arProduct["STORES"]) > 0)
								{
									foreach ($arProduct["STORES"] as $index => $arStore)
									{
										if (!is_array($arStore["BARCODE"]) || count($arStore["BARCODE"]) == 0)
										{
											$errorMessageTmp .= GetMessage("NEWO_ERR_STORE_NO_BARCODES", array("#PRODUCT_NAME#" => $arProduct["NAME"], "#STORE_ID#" => $arStore["STORE_ID"]))."<br>";
											$bErrorFound = true;
											break;
										}

										if (count($arStore["BARCODE"]) != $arStore["QUANTITY"])
										{
											$errorMessageTmp .= GetMessage("NEWO_ERR_STORE_QUANTITY_BARCODE", array("#PRODUCT_NAME#" => $arProduct["NAME"], "#STORE_ID#" => $arStore["STORE_ID"]))."<br>";
											$bErrorFound = true;
											break;
										}

										foreach ($arStore["BARCODE"] as $d => $bValue)
										{
											if (strlen($bValue) <= 0)
											{
												$errorMessageTmp .= GetMessage("NEWO_ERR_STORE_EMPTY_BARCODES", array("#PRODUCT_NAME#" => $arProduct["NAME"], "#STORE_ID#" => $arStore["STORE_ID"], "#BARCODE#" => $arStore["BARCODE"][$j]))."<br>";
												$bErrorFound = true;
												break;
											}
										}

										if (count($arStore["BARCODE_FOUND"]) > 0 && !$bErrorFound)
										{
											foreach ($arStore["BARCODE_FOUND"] as $j => $bfValue)
											{
												if ($bfValue == "N")
												{
													$errorMessageTmp .= GetMessage("NEWO_ERR_STORE_BARCODES", array("#PRODUCT_NAME#" => $arProduct["NAME"], "#STORE_ID#" => $arStore["STORE_ID"], "#BARCODE#" => $arStore["BARCODE"][$j]))."<br>";
													$bErrorFound = true;
													break;
												}
											}
										}
										if ($bErrorFound)
											break;
									}
								}
								else if ($arProduct["BARCODE_MULTI"] == "N" && is_array($arProduct["STORES"]) && count($arProduct["STORES"]) > 0)
								{
									//check if store info contains all necessary fields
									foreach ($arProduct["STORES"] as $recId => $arRecord)
									{
										if (!isset($arRecord["STORE_ID"]) || intVal($arRecord["STORE_ID"]) < 0 || (!isset($arRecord["AMOUNT"])) || intVal($arRecord["AMOUNT"]) < 0)
										{
											$errorMessageTmp .= GetMessage("NEWO_ERR_STORE_WRONG_INFO", array("#PRODUCT_NAME#" => $arProduct["NAME"]))."<br>";
											$bErrorFound = true;
											break;
										}
									}
								}

								if ($bErrorFound)
									break;
							}
						}
					}

					//updating tmp Id in the store data from the form to the basket ID if the product was added to the basket later
					$arNewStoreBarcodeOrderFormData = array();
					foreach ($arStoreBarcodeOrderFormData as $key => $arStoreRecord)
					{
						if (substr($key, 0, 3) == "new")
						{
							foreach ($arOrder["BASKET_ITEMS"] as $arBasketRecord)
							{
								if ($arBasketRecord["STORES"] == $arStoreRecord)
								{
									$arNewStoreBarcodeOrderFormData[$arBasketRecord["ID"]] = $arStoreRecord;
									break;
								}
							}
						}
						else
						{
							$arNewStoreBarcodeOrderFormData[$key] = $arStoreRecord;
						}
					}

					if (strlen($errorMessageTmp) <= 0)
					{
						if (!CSaleOrder::DeductOrder($ID, $DEDUCTED, $REASON_UNDO_DEDUCTED, false, $arNewStoreBarcodeOrderFormData))
						{
							if ($ex = $APPLICATION->GetException())
							{
								if ($ex->GetID() != "ALREADY_FLAG")
									$errorMessageTmp .= $ex->GetString();
							}
							else
								$errorMessageTmp .= GetMessage("ERROR_DEDUCT_ORDER").". ";
						}
					}

					if ($errorMessageTmp != "")
						$arErrors[] = $errorMessageTmp;
				}
				else
				{
					if($arOldOrder["REASON_UNDO_DEDUCTED"] != $REASON_UNDO_DEDUCTED)
						$arOrder2Update["REASON_UNDO_DEDUCTED"] = $REASON_UNDO_DEDUCTED;
				}
			}

			if ($ID > 0 AND count($arErrors) <= 0)
			{
				if(!empty($arOrder2Update))
					CSaleOrder::Update($ID, $arOrder2Update);
			}

			if ($ID > 0 AND count($arErrors) <= 0)
			{
				//profile saving
				$str_USER_ID = IntVal($str_USER_ID);

				if (isset($userProfileID))
				{
					CSaleOrderUserProps::DoSaveUserProfile($str_USER_ID, $userProfileID, $profileName, $str_PERSON_TYPE_ID, $arOrderPropsValues, $arErrors);
				}
				unset($user_profile);

				//send new order mail
				if ($OrderNewSendEmail)
				{
					$strOrderList = "";
					foreach ($arOrder["BASKET_ITEMS"] as $val)
					{
						$strOrderList .= $val["NAME"]." - ".$val["QUANTITY"]." ".GetMessage("SOA_SHT").": ".SaleFormatCurrency($val["PRICE"], $BASE_LANG_CURRENCY);
						$strOrderList .= "\n";
					}

					//send mail
					$arFields = Array(
						"ORDER_ID" => $ID,
						"ORDER_DATE" => Date($DB->DateFormatToPHP(CLang::GetDateFormat("SHORT", $LID))),
						"ORDER_USER" => $arUserEmail["PAYER_NAME"],
						"PRICE" => SaleFormatCurrency($arOrder["PRICE"], $BASE_LANG_CURRENCY),
						"BCC" => COption::GetOptionString("sale", "order_email", "order@".$SERVER_NAME),
						"EMAIL" => $arUserEmail["USER_EMAIL"],
						"ORDER_LIST" => $strOrderList,
						"SALE_EMAIL" => COption::GetOptionString("sale", "order_email", "order@".$SERVER_NAME),
						"DELIVERY_PRICE" => $arOrder["DELIVERY_PRICE"],
					);
					$eventName = "SALE_NEW_ORDER";

					$bSend = true;
					foreach(GetModuleEvents("sale", "OnOrderNewSendEmail", true) as $arEvent)
						if (ExecuteModuleEventEx($arEvent, Array($ID, &$eventName, &$arFields))===false)
							$bSend = false;

					if($bSend)
					{
						$event = new CEvent;
						$event->Send($eventName, $LID, $arFields, "N");
					}
				}
			}
			else
			{
				foreach($arErrors as $val)
				{
					if (is_array($val))
						$errorMessage .= $val["TEXT"]."<br>";
					else
						$errorMessage .= $val;
				}
			}
		}
		elseif (count($arErrors) > 0)
		{
			foreach($arErrors as $val)
			{
				if (is_array($val))
					$errorMessage .= $val["TEXT"]."<br>";
				else
					$errorMessage .= $val;
			}
		}
		else
		{
			$errorMessage .= GetMessage("SOE_SAVE_ERROR")."<br>";
		}
	}//end if save

	unset($location);
	unset($BTN_SAVE_BUYER);
	unset($buyertypechange);
	unset($userId);
	unset($user_id);

	if (strlen($errorMessage) <= 0 AND $ID > 0)
	{
		if ($crmMode)
			CRMModeOutput($ID);

		if (isset($save) AND strlen($save) > 0)
		{
			CSaleOrder::UnLock($ID);
			LocalRedirect("/bitrix/admin/sale_order.php?lang=".LANG."&LID=".CUtil::JSEscape($LID));
		}

		if (isset($apply) AND strlen($apply) > 0)
			LocalRedirect("/bitrix/admin/sale_order_new.php?lang=".LANG."&ID=".$ID."&LID=".CUtil::JSEscape($LID));
	}
	if (strlen($errorMessage) > 0)
		$bVarsFromForm = true;
}

if (!empty($dontsave))
{
	CSaleOrder::UnLock($ID);
	if ($crmMode)
		CRMModeOutput($ID);

	LocalRedirect("/bitrix/admin/sale_order.php?lang=".LANG."&LID=".CUtil::JSEscape($LID));
}

/*****************************************************************************/
/************** Processing of requests from the proxy ************************/
/*****************************************************************************/

if (isset($ORDER_AJAX) AND $ORDER_AJAX == "Y" AND check_bitrix_sessid())
{
	/*
	* location
	*/
	if (isset($location) AND !isset($product) AND !isset($locationZip))
	{
		$location = IntVal($location);
		$tmpLocation = "";

		ob_start();
		$GLOBALS["APPLICATION"]->IncludeComponent(
				'bitrix:sale.ajax.locations',
				'',
				array(
					"SITE_ID" => $LID,
					"AJAX_CALL" => "Y",
					"COUNTRY_INPUT_NAME" => "ORDER_PROP_".$locid,
					"REGION_INPUT_NAME" => "REGION_ORDER_PROP_".$locid,
					"CITY_INPUT_NAME" => "CITY_ORDER_PROP_".$locid,
					"CITY_OUT_LOCATION" => "Y",
					"ALLOW_EMPTY_CITY" => "Y",
					"LOCATION_VALUE" => $location,
					"COUNTRY" => "",
					"ONCITYCHANGE" => "fRecalProduct('', '', 'N', 'N');",
				),
				null,
				array('HIDE_ICONS' => 'Y')
		);
		$tmpLocation = ob_get_contents();
		ob_end_clean();

		$arData = array();
		if (IntVal($locid) > 0)
		{
			$arData["status"] = "ok";
			$arData["prop_id"] = $locid;
			$arData["location"] = $tmpLocation;
		}
		$result = CUtil::PhpToJSObject($arData);

		CRMModeOutput($result);
	}

	/*
	* change buyer type
	*/
	if (isset($buyertypechange))
	{
		if (!isset($ID) OR $ID == "") $ID = "";
		if (!isset($paysystemid) OR $paysystemid == "") $paysystemid = "";

		$arData = array();
		$arData["status"] = "ok";
		$arData["buyertype"] = fGetBuyerType($buyertypechange, $LID, $userId, $ID);
		$arData["buyerdelivery"] = fBuyerDelivery($buyertypechange, $paysystemid);
		$arLocation = fGetLocationID($buyertypechange);

		$arData["location_id"] = $arLocation["LOCATION_ID"];
		$arData["location_zip_id"] = $arLocation["LOCATION_ZIP_ID"];

		$result = CUtil::PhpToJSObject($arData);

		CRMModeOutput($result);
	}

	/*
	* get locationId for delivery
	*/
	if (isset($persontypeid))
	{
		$persontypeid = IntVal($persontypeid);

		$arData = array();
		$arLocation = fGetLocationID($persontypeid);

		$arData["location_id"] = $arLocation["LOCATION_ID"];
		$arData["location_zip_id"] = $arLocation["LOCATION_ZIP_ID"];

		$result = CUtil::PhpToJSObject($arData);

		CRMModeOutput($result);
	}

	/*
	* take a list profile and user basket
	*/
	if (isset($userId) AND isset($buyerType) AND (!isset($profileDefault) OR $profileDefault == ""))
	{
		$id = IntVal($id);
		$userId = IntVal($userId);
		$buyerType = IntVal($buyerType);
		$LID = trim($LID);
		$currency = trim($currency);

		$arFuserItems = CSaleUser::GetList(array("USER_ID" => $userId));
		$fuserId = $arFuserItems["ID"];
		$arData = array();
		$arErrors = array();

		$arData["status"] = "ok";
		$arData["userProfileSelect"] = fUserProfile($userId, $buyerType);
		$arData["userName"] = fGetUserName($userId);

		$arShoppingCart = CSaleBasket::DoGetUserShoppingCart($LID, $userId, $fuserId, $arErrors, array());
		$arShoppingCart = fDeleteDoubleProduct($arShoppingCart, array(), 'N');
		$arData["userBasket"] = fGetFormatedProduct($userId, $LID, $arShoppingCart, $currency, 'busket');

		$arViewed = array();
		$dbViewsList = CSaleViewedProduct::GetList(
				array("DATE_VISIT"=>"DESC"),
				array("FUSER_ID" => $fuserId, ">PRICE" => 0, "!CURRENCY" => ""),
				false,
				array('nTopCount' => 10),
				array('ID', 'PRODUCT_ID', 'LID', 'MODULE', 'NAME', 'DETAIL_PAGE_URL', 'PRICE', 'CURRENCY', 'PREVIEW_PICTURE', 'DETAIL_PICTURE')
			);
		while ($arViews = $dbViewsList->Fetch())
			$arViewed[] = $arViews;

		$arViewedResult = fDeleteDoubleProduct($arViewed, $arFilterRecomendet, 'N');
		$arData["viewed"] = fGetFormatedProduct($userId, $LID, $arViewedResult, $currency, 'viewed');


		$result = CUtil::PhpToJSObject($arData);

		CRMModeOutput($result);
	}

	/*
	* script profile autocomplete
	*/
	if (isset($userId) AND isset($buyerType) AND isset($profileDefault))
	{
		$userId = IntVal($userId);
		$buyerType = IntVal($buyerType);
		$profileDefault = IntVal($profileDefault);

		$userProfile = array();
		$userProfile = CSaleOrderUserProps::DoLoadProfiles($userId, $buyerType);
		if ($profileDefault != "" AND $profileDefault != "0")
			$arPropValuesTmp = $userProfile[$profileDefault]["VALUES"];

		$dbVariants = CSaleOrderProps::GetList(
			array("SORT" => "ASC"),
			array(
					"PERSON_TYPE_ID" => $buyerType,
					"USER_PROPS" => "Y",
					"ACTIVE" => "Y"
				)
		);
		while ($arVariants = $dbVariants->Fetch())
		{
			if (isset($arPropValuesTmp[$arVariants["ID"]]))
				$arPropValues[$arVariants["ID"]] = $arPropValuesTmp[$arVariants["ID"]];
			else
				$arPropValues[$arVariants["ID"]] = $arVariants["DEFAULT_VALUE"];

			if($arVariants["IS_EMAIL"] == "Y" || $arVariants["IS_PAYER"] == "Y")
			{
				if(strlen($arPropValues[$arVariants["ID"]]) <= 0 && IntVal($userId) > 0)
				{
					$rsUser = CUser::GetByID($userId);
					if ($arUser = $rsUser->Fetch())
					{
						if($arVariants["IS_EMAIL"] == "Y")
							$arPropValues[$arVariants["ID"]] = $arUser["EMAIL"];
						else
						{
							if (strlen($arUser["LAST_NAME"]) > 0)
								$arPropValues[$arVariants["ID"]] .= $arUser["LAST_NAME"];
							if (strlen($arUser["NAME"]) > 0)
								$arPropValues[$arVariants["ID"]] .= " ".$arUser["NAME"];
							if (strlen($arUser["SECOND_NAME"]) > 0 AND strlen($arUser["NAME"]) > 0)
								$arPropValues[$arVariants["ID"]] .= " ".$arUser["SECOND_NAME"];
						}
					}
				}
			}

		}

		$scriptExec = "<script language=\"JavaScript\">";
		foreach ($arPropValues as $key => $val):
			$val = CUtil::JSEscape(htmlspecialcharsback($val));
			$scriptExec .= "var el = document.getElementById(\"ORDER_PROP_".$key."CITY_ORDER_PROP_".$key."\");\n";
			$scriptExec .= "if(el)\n{\n";
			$scriptExec .= "BX.ajax.post('/bitrix/admin/sale_order_new.php', '".bitrix_sessid_get()."&ORDER_AJAX=Y&locid=".$key."&propID=".$buyerType."&LID=".CUtil::JSEscape($LID)."&location=".$val."', fLocationResult);\n";
			$scriptExec .= "}";
			$scriptExec .= "var el = document.getElementById(\"ORDER_PROP_".$key."\");\n";
			$scriptExec .= "if(el)\n{\n";
			$scriptExec .= "var elType = el.getAttribute('type');\n";
			$scriptExec .= "if (elType == \"text\" || elType == \"textarea\" || elType == \"select\")\n";
			$scriptExec .= "{";
				$scriptExec .= "el.value = '".$val."';\n";
			$scriptExec .= "}";
			$scriptExec .= "else if (elType == \"radio\")\n";
			$scriptExec .= "{";
				$scriptExec .= "elRadio = el.getElementsByTagName(\"input\");\n";
				$scriptExec .= "for (var i = 0; i < elRadio.length; i++)\n";
				$scriptExec .= "{";
					$scriptExec .= "if (elRadio[i].value == '".$val."')\n";
					$scriptExec .= "{";
						$scriptExec .= "elRadio[i].checked = true;\n";
					$scriptExec .= "}";
					$scriptExec .= "else {";
						$scriptExec .= "elRadio[i].checked = false;\n";
					$scriptExec .= "}";
				$scriptExec .= "}";
			$scriptExec .= "}";
			$scriptExec .= "else if (elType == \"checkbox\")\n";
			$scriptExec .= "{";
				if ($val == "Y")
				{
					$scriptExec .= "el.checked = true;\n";
				}
				else
				{
					$scriptExec .= "el.checked = false;\n";
				}
			$scriptExec .= "}";
			$scriptExec .= "else if (elType == \"multyselect\")\n";
			$scriptExec .= "{";
				if ($val != "")
				{
					$selectedVal = explode(",", $val);
					foreach ($selectedVal as $k => $v):
						$scriptExec .= "el.value = '".trim($v)."';\n";
					endforeach;
				}
				else
				{
					$scriptExec .= "el.selectedIndex = -1;";
				}
			$scriptExec .= "}\n";
			$scriptExec .= "}\n";
		endforeach;
		$scriptExec .= "fRecalProduct('', '', 'N', 'N');</script>";

		echo $scriptExec;
		die();
	}

	/*
	* get more basket
	*/
	if (isset($getmorebasket) && $getmorebasket == "Y")
	{
		$userId = IntVal($userId);
		$arFuserItems = CSaleUser::GetList(array("USER_ID" => intval($userId)));
		$fuserId = $arFuserItems["ID"];
		$arErrors = array();

		$arOrderProduct = CUtil::JsObjectToPhp($arProduct);
		$arShoppingCart = CSaleBasket::DoGetUserShoppingCart($LID, $userId, $fuserId, $arErrors, array());
		$arShoppingCart = fDeleteDoubleProduct($arShoppingCart, $arOrderProduct, $showAll);

		$result = fGetFormatedProduct($userId, $LID, $arShoppingCart, $CURRENCY, 'busket');

		CRMModeOutput($result);
	}

	/*
	* get more viewed
	*/
	if (isset($getmoreviewed) && $getmoreviewed == "Y")
	{
		$userId = IntVal($userId);
		$arFuserItems = CSaleUser::GetList(array("USER_ID" => intval($userId)));
		$fuserId = $arFuserItems["ID"];
		$arErrors = array();

		$arOrderProduct = CUtil::JsObjectToPhp($arProduct);
		$arViewed = array();
		$dbViewsList = CSaleViewedProduct::GetList(
				array("DATE_VISIT"=>"DESC"),
				array("FUSER_ID" => $fuserId, ">PRICE" => 0, "!CURRENCY" => ""),
				false,
				array('nTopCount' => 10),
				array('ID', 'PRODUCT_ID', 'LID', 'MODULE', 'NAME', 'DETAIL_PAGE_URL', 'PRICE', 'CURRENCY', 'PREVIEW_PICTURE', 'DETAIL_PICTURE')
			);
		while ($arViews = $dbViewsList->Fetch())
			$arViewed[] = $arViews;

		$arViewedCart = fDeleteDoubleProduct($arViewed, $arOrderProduct, $showAll);

		$result = fGetFormatedProduct($userId, $LID, $arViewedCart, $CURRENCY, 'viewed');

		CRMModeOutput($result);
	}

	/*
	* recalc order
	*/
	if (isset($product) AND isset($user_id))
	{
		$result = "";
		$id = IntVal($id);
		$userId = IntVal($userId);
		$paySystemId = IntVal($paySystemId);
		$buyerTypeId = IntVal($buyerTypeId);
		$location = IntVal($location);
		$locationID = IntVal($locationID);
		$locationZip = IntVal($locationZip);
		$locationZipID = IntVal($locationZipID);
		$WEIGHT_UNIT = htmlspecialcharsbx(COption::GetOptionString('sale', 'weight_unit', "", $LID));
		$WEIGHT_KOEF = htmlspecialcharsbx(COption::GetOptionString('sale', 'weight_koef', 1, $LID));
		$arDelivery = array();
		$recomMore = ($recomMore == "Y") ? "Y" : "N";
		$recalcOrder = ($recalcOrder == "Y") ? "Y" : "N";
		$cartFix = ('Y' == $cartFix ? 'Y' : 'N');

		$arOrderProduct = CUtil::JsObjectToPhp($product);

		$arCupon = fGetCupon($cupon);
		$arOrderProductPrice = fGetUserShoppingCart($arOrderProduct, $LID, $recalcOrder);

		foreach ($arOrderProductPrice as &$arItem) // tmp hack not to update basket quantity data from catalog
		{
			$arItem["ID_TMP"] = $arItem["ID"];
			unset($arItem["ID"]);
		}
		unset($arItem);

		$tmpOrderId = ($id == 0) ? 0 : $id;

		$arShoppingCart = CSaleBasket::DoGetUserShoppingCart($LID, $user_id, $arOrderProductPrice, $arErrors, $arCupon, $tmpOrderId);

		$arOrderPropsValues = array();
		if ($locationID != "" AND $location != "")
			$arOrderPropsValues[$locationID] = $location;
		if ($locationZipID != "" AND $locationZip != "")
			$arOrderPropsValues[$locationZipID] = $locationZip;

		//enable/disable town for location
		$dbProperties = CSaleOrderProps::GetList(
			array("SORT" => "ASC"),
			array("ID" => $locationID, "ACTIVE" => "Y", ">INPUT_FIELD_LOCATION" => 0),
			false,
			false,
			array("INPUT_FIELD_LOCATION")
		);
		if ($arProperties = $dbProperties->Fetch())
			$bDeleteFieldLocationID = $arProperties["INPUT_FIELD_LOCATION"];

		$rsLocationsList = CSaleLocation::GetList(
			array(),
			array("ID" => $location),
			false,
			false,
			array("ID", "CITY_ID")
		);
		$arCity = $rsLocationsList->GetNext();
		if (IntVal($arCity["CITY_ID"]) <= 0)
			$bDeleteFieldLocation = "Y";
		else
			$bDeleteFieldLocation = "N";

		$arOrderOptions = array(
			'CART_FIX' => $cartFix
		);
		$arOrder = CSaleOrder::DoCalculateOrder(
			$LID,
			$user_id,
			$arShoppingCart,
			$buyerTypeId,
			$arOrderPropsValues,
			$deliveryId,
			$paySystemId,
			$arOrderOptions,
			$arErrors,
			$arWarnings
		);

		$orderDiscount = 0;
		$arData = array();
		$arFilterRecomendet = array();
		$priceBaseTotal = 0;

		if (count($arOrder["BASKET_ITEMS"]) > 0)
		{
			foreach ($arOrder["BASKET_ITEMS"] as $val)
			{
				$priceDiscountPercent = 0;
				$arCurFormat = CCurrencyLang::GetCurrencyFormat($val["CURRENCY"]);
				$priceBase = $val["PRICE"] + $val["DISCOUNT_PRICE"];
				$priceDiscountPercent = roundEx(($val["DISCOUNT_PRICE"] * 100) / $priceBase, SALE_VALUE_PRECISION);

				$arData[$val["TABLE_ROW_ID"]]["PRICE_BASE"] = CurrencyFormatNumber($priceBase, $val["CURRENCY"]);
				$arData[$val["TABLE_ROW_ID"]]["DISCOUNT_REPCENT"] = $priceDiscountPercent;
				$arData[$val["TABLE_ROW_ID"]]["DISCOUNT_PRICE"] = $val["DISCOUNT_PRICE"];
				$arData[$val["TABLE_ROW_ID"]]["PRICE"] = $val["PRICE"];
				$arData[$val["TABLE_ROW_ID"]]["PRICE_DISPLAY"] = CurrencyFormatNumber($val["PRICE"], $val["CURRENCY"]);
				$arData[$val["TABLE_ROW_ID"]]["QUANTITY"] = $val["QUANTITY"];

				if (isset($val["QUANTITY_DEFAULT"]) && $val["QUANTITY_DEFAULT"] > 0 && $val["QUANTITY_DEFAULT"] != $val["QUANTITY"])
					$arData[$val["TABLE_ROW_ID"]]["WARNING_BALANCE"] = "Y";

				$arData[$val["TABLE_ROW_ID"]]["DISCOUNT_PRICE_DISPLAY"] = CurrencyFormatNumber($val["DISCOUNT_PRICE"], $val["CURRENCY"]);
				$arData[$val["TABLE_ROW_ID"]]["SUMMA_DISPLAY"] = CurrencyFormatNumber(($val["PRICE"] * $val["QUANTITY"]), $val["CURRENCY"]);
				$arData[$val["TABLE_ROW_ID"]]["CURRENCY"] = $val["CURRENCY"];
				$arData[$val["TABLE_ROW_ID"]]["NOTES"] = $val["NOTES"];

				$balance = 0;
				if ($val["MODULE"] == "catalog" && CModule::IncludeModule('catalog'))
				{
					$ar_res = CCatalogProduct::GetByID($val["PRODUCT_ID"]);
					$balance = FloatVal($ar_res["QUANTITY"]);
				}
				$arData[$val["TABLE_ROW_ID"]]["BALANCE"] = $balance;
				$orderDiscount += $val["DISCOUNT_PRICE"] * $val["QUANTITY"];
				$arFilterRecomendet[] = $val["PRODUCT_ID"];

				$priceBaseTotal += ($arOrderProduct[$val["TABLE_ROW_ID"]]["PRICE_DEFAULT"] * $val["QUANTITY"]);
			}
		}
		$arData[0]["ORDER_ERROR"] = "N";

		//change delivery price
		$deliveryChangePrice = false;
		if ($delpricechange == "Y")
		{
			$arOrder["PRICE"] = ($arOrder["PRICE"] - $arOrder["DELIVERY_PRICE"]) + $deliveryPrice;
			$arOrder["DELIVERY_PRICE"] = $deliveryPrice;
			$arOrder["PRICE_DELIVERY"] = $deliveryPrice;
			$deliveryChangePrice = true;
			$arDelivery["DELIVERY_DEFAULT_PRICE"] = $deliveryPrice;
			$arDelivery["DELIVERY_DEFAULT"] = "";
			$arDelivery["DELIVERY_DEFAULT_ERR"] = "";
			$arDelivery["DELIVERY_DEFAULT_DESCRIPTION"] = "";
			$arData[0]["DELIVERY"] = "";
		}
		else
			$arDelivery = fGetDelivery($location, $locationZip, $arOrder["ORDER_WEIGHT"], $arOrder["ORDER_PRICE"], $currency, $LID, $deliveryId);

		$arData[0]["ORDER_ID"] = $id;
		$arData[0]["DELIVERY"] = $arDelivery["DELIVERY"];

		if (isset($arOrder["PRICE_DELIVERY"]) && floatval($arOrder["PRICE_DELIVERY"]) >= 0 && floatval($arOrder["PRICE_DELIVERY"])."!" == $arOrder["PRICE_DELIVERY"]."!") //if number
		{
			$arData[0]["DELIVERY_PRICE"] = $arOrder["PRICE_DELIVERY"];
			$arData[0]["DELIVERY_PRICE_FORMAT"] = SaleFormatCurrency($arOrder["PRICE_DELIVERY"], $currency);
		}
		else
		{
			if ($arDelivery["CURRRENCY"] != $currency)
				$arDelivery["DELIVERY_DEFAULT_PRICE"] = roundEx(CCurrencyRates::ConvertCurrency($arDelivery["DELIVERY_DEFAULT_PRICE"], $arDelivery["CURRENCY"], $currency), SALE_VALUE_PRECISION);

			$arData[0]["DELIVERY_PRICE"] = $arDelivery["DELIVERY_DEFAULT_PRICE"];
			$arData[0]["DELIVERY_PRICE_FORMAT"] = SaleFormatCurrency($arDelivery["DELIVERY_DEFAULT_PRICE"], $currency);
		}
		$arData[0]["DELIVERY_DEFAULT"] = $arDelivery["DELIVERY_DEFAULT"];

		if (strlen($arDelivery["DELIVERY_DEFAULT_ERR"]) > 0)
		{
			$arData[0]["DELIVERY_DESCRIPTION"] = $arDelivery["DELIVERY_DEFAULT_ERR"];
			$arData[0]["ORDER_ERROR"] = "Y";
		}
		else
			$arData[0]["DELIVERY_DESCRIPTION"] = $arDelivery["DELIVERY_DEFAULT_DESCRIPTION"];

		if (!isset($arOrder["ORDER_PRICE"]) OR $arOrder["ORDER_PRICE"] == "" )
			$arOrder["ORDER_PRICE"] = 0;
		if (!isset($arOrder["PRICE"]) OR $arOrder["PRICE"] == "")
			$arOrder["PRICE"] = 0;
		if (!isset($arOrder["DISCOUNT_VALUE"]) OR $arOrder["DISCOUNT_VALUE"] == "")
			$arOrder["DISCOUNT_VALUE"] = 0;

		$arData[0]["CURRENCY_FORMAT"] = trim(str_replace("#", '', $arCurFormat["FORMAT_STRING"]));
		$arData[0]["PRICE_TOTAL"] = SaleFormatCurrency($priceBaseTotal, $currency);
		$arData[0]["PRICE_WITH_DISCOUNT_FORMAT"] = SaleFormatCurrency($arOrder["ORDER_PRICE"], $currency);
		$arData[0]["PRICE_WITH_DISCOUNT"] = roundEx($arOrder["ORDER_PRICE"]);
		$arData[0]["PRICE_TAX"] = SaleFormatCurrency(DoubleVal($arOrder["TAX_VALUE"]), $currency);
		$arData[0]["PRICE_WEIGHT_FORMAT"] = roundEx(DoubleVal($arOrder["ORDER_WEIGHT"]/$WEIGHT_KOEF), SALE_VALUE_PRECISION)." ".$WEIGHT_UNIT;
		$arData[0]["PRICE_WEIGHT"] = roundEx(DoubleVal($arOrder["ORDER_WEIGHT"]/$WEIGHT_KOEF), SALE_VALUE_PRECISION);
		$arData[0]["PRICE_TO_PAY"] = SaleFormatCurrency($arOrder["PRICE"], $currency);
		$arData[0]["PRICE_TO_PAY_DEFAULT"] = FloatVal($arOrder["PRICE"]);
		$arData[0]["PAY_ACCOUNT"] = $tmpPay["PAY_MESSAGE"];
		$arData[0]["PAY_ACCOUNT_CAN_BUY"] = $tmpPay["PAY_BUDGET"];
		$arData[0]["PAY_ACCOUNT_DEFAULT"] = FloatVal($tmpPay["CURRENT_BUDGET"]);
		$arData[0]["DISCOUNT_VALUE"] = $arOrder["DISCOUNT_VALUE"];
		$arData[0]["DISCOUNT_VALUE_FORMATED"] = SaleFormatCurrency($arOrder["DISCOUNT_VALUE"], $currency);
		$arData[0]["DISCOUNT_PRODUCT_VALUE"] = $orderDiscount;
		$arData[0]["LOCATION_TOWN_ID"] = IntVal($bDeleteFieldLocationID);
		$arData[0]["LOCATION_TOWN_ENABLE"] = $bDeleteFieldLocation;
		$tmpPay = fGetPayFromAccount($user_id, $currency);

		//recomended
		$recommendedProduct = "";
		$arProductIdInBasket = array();
		$arData[0]["RECOMMENDET_CALC"] = "N";
		if ($recommendet == "Y")
		{
			$arRecomendet = CSaleProduct::GetRecommendetProduct($userId, $LID, $arFilterRecomendet);
			$arRecomendetProduct = fDeleteDoubleProduct($arRecomendet, $arFilterRecomendet, $recomMore);

			$recommendedProduct = fGetFormatedProduct($user_id, $LID, $arRecomendetProduct, $currency, 'recom');
			$arData[0]["RECOMMENDET_CALC"] = "Y";
		}
		$arData[0]["RECOMMENDET_PRODUCT"] = $recommendedProduct;

		$result = CUtil::PhpToJSObject($arData);

		CRMModeOutput($result);
	}

	/*
	* check barcode
	*/
	if (isset($checkBarcode))
	{
		$arRes = array();
		$arBasket = array();
		$arRes["status"] = "";

		// if ($ID > 0)
		// {
		$basketItemId = (isset($_POST["basketItemId"])) ? intval($_POST["basketItemId"]) : "";
		$barcode = (isset($_POST["barcode"])) ? $_POST["barcode"] : "";
		$storeId = (isset($_POST["storeId"])) ? intval($_POST["storeId"]) : "";
		$storeId = (isset($_POST["storeId"])) ? intval($_POST["storeId"]) : "";

		if (intval($basketItemId) > 0)
		{
			$dbBasket = CSaleBasket::GetList(
				array("ID" => "DESC"),
				array("ID" => $basketItemId),
				false,
				false,
				array("ID", "PRODUCT_ID", "PRODUCT_PROVIDER_CLASS", "MODULE", "BARCODE_MULTI")
			);

			$arBasket = $dbBasket->GetNext();
		}
		else
		{
			$productId = (isset($_POST["productId"])) ? intval($_POST["productId"]) : "";
			$productProvider = (isset($_POST["productProvider"])) ? $_POST["productProvider"] : "";
			$moduleName = (isset($_POST["moduleName"])) ? $_POST["moduleName"] : "";
			$bBarcodeMulti = (isset($_POST["barcodeMulti"]) && $_POST["barcodeMulti"] == "Y") ? "Y" : "N";

			$arBasket = array(
				"PRODUCT_PROVIDER_CLASS" => $productProvider,
				"MODULE" => $moduleName,
				"PRODUCT_ID" => $productId,
				"BARCODE_MULTI" => $bBarcodeMulti
			);
		}

		if (is_array($arBasket) && count($arBasket) > 0)
		{
			/** @var $productProvider IBXSaleProductProvider */
			if ($productProvider = CSaleBasket::GetProductProvider($arBasket))
			{
				$arCheckBarcodeFields = array(
					"BARCODE"    => $barcode,
					"PRODUCT_ID" => $arBasket["PRODUCT_ID"],
					"ORDER_ID"   => $ID
				);

				if ($arBasket["BARCODE_MULTI"] == "Y")
					$arCheckBarcodeFields["STORE_ID"] = $storeId;

				$res = $productProvider::CheckProductBarcode($arCheckBarcodeFields);

				$arRes["status"] = ($res) ? "ok" : "error"; //'ok' here means product is found in the catalog
			}
			else
			{
				$arRes["status"] = "error";
			}
		}
		else
		{
			$arRes["status"] = "error";
		}

		$result = CUtil::PhpToJSObject($arRes);

		CRMModeOutput($result);
	}

}//end ORDER_AJAX=Y

/*****************************************************************************/
/**************************** FORM ORDER *************************************/
/*****************************************************************************/

//date order
$str_DATE_UPDATE = Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat("FULL", $lang)));
$str_DATE_INSERT = Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat("FULL", $lang)));
$str_PRICE = 0;
$str_DISCOUNT_VALUE = 0;

if (!function_exists("setBarcodeClass"))
{
	function setBarcodeClass($barcodeValue)
	{
		$result = "";

		if ($barcodeValue == "Y")
		{
			$result = "store_barcode_found_input";
		}
		elseif ($barcodeValue == "N")
		{
			$result = "store_barcode_not_found";
		}

		return $result;
	}
}

if (isset($ID) && $ID > 0)
{
	$dbOrder = CSaleOrder::GetList(
		array("ID" => "DESC"),
		array("ID" => $ID),
		false,
		false,
		array()
	);
	if (!($arOrderOldTmp = $dbOrder->ExtractFields("str_")))
		LocalRedirect("sale_order.php?lang=".LANG.GetFilterParams("filter_", false));
	$LID = $str_LID;
}
if (!isset($str_TAX_VALUE) OR $str_TAX_VALUE == "")
	$str_TAX_VALUE = 0;

if (IntVal($str_PERSON_TYPE_ID) <= 0)
{
	$str_PERSON_TYPE_ID = 0;
	$arFilter = array();
	$arFilter["ACTIVE"] = "Y";
	if(strlen($LID) > 0)
		$arFilter["LID"] = $LID;
	$dbPersonType = CSalePersonType::GetList(array("ID" => "ASC"), $arFilter);
	if($arPersonType = $dbPersonType->Fetch())
		$str_PERSON_TYPE_ID = $arPersonType["ID"];
}

$arFuserItems = CSaleUser::GetList(array("USER_ID" => intval($str_USER_ID)));
$FUSER_ID = $arFuserItems["ID"];

/*
 * form select site
 */
if ((!isset($LID) OR $LID == "") AND (defined('BX_PUBLIC_MODE') OR BX_PUBLIC_MODE == 1) )
{
	$arSitesShop = array();
	$arSitesTmp = array();
	$rsSites = CSite::GetList($by="id", $order="asc", Array("ACTIVE" => "Y"));
	while ($arSite = $rsSites->Fetch())
	{
		$site = COption::GetOptionString("sale", "SHOP_SITE_".$arSite["ID"], "");
		if ($arSite["ID"] == $site)
		{
			$arSitesShop[] = array("ID" => $arSite["ID"], "NAME" => $arSite["NAME"]);
		}
		$arSitesTmp[] = array("ID" => $arSite["ID"], "NAME" => $arSite["NAME"]);
	}

	$rsCount = count($arSitesShop);
	if ($rsCount <= 0)
	{
		$arSitesShop = $arSitesTmp;
		$rsCount = count($arSitesShop);
	}

	if ($rsCount === 1)
	{
		$LID = $arSitesShop[0]["ID"];
	}
	elseif ($rsCount > 1)
	{
?>
		<div id="select_lid">
			<form action="" name="select_lid">
				<div style="margin:10px auto;text-align:center;">
					<div><?=GetMessage("NEWO_SELECT_SITE")?></div><br />
					<select name="LID" onChange="fLidChange(this);">
						<option selected="selected" value=""><?=GetMessage("NEWO_SELECT_SITE")?></option>
						<?
						foreach ($arSitesShop as $key => $val)
						{
						?>
							<option value="<?=$val["ID"]?>"><? echo $val["NAME"]." (".$val["ID"].")";?></option>
						<?
						}
						?>
					</select>
				</div>
				<script>
					function fLidChange(el)
					{
						BX.showWait();
						BX.ajax.post("/bitrix/admin/sale_order_new.php", "<?=bitrix_sessid_get()?>&ORDER_AJAX=Y&lang=<?=LANGUAGE_ID?>&LID=" + el.value, fLidChangeResult);
					}
					function fLidChangeResult(result)
					{
						fLidChangeDisableButtons(false);
						BX.closeWait();
						if (result.length > 0)
						{
							document.getElementById("select_lid").innerHTML = result;
						}
					}
					function fLidChangeDisableButtons(val)
					{
						var btn = document.getElementById("btn-save");
						if (btn)
							btn.disabled = val;
						btn = document.getElementById("btn-cancel");
						if (btn)
							btn.disabled = val;
					}
					BX.ready(function(){ fLidChangeDisableButtons(true); });
				</script>
			</form>
		</div>
<?
		die();
	}
	else
	{
		echo "<div style=\"margin:10px auto;text-align:center;\">";
		echo GetMessage("NEWO_NO_SITE_SELECT");
		echo "<div>";
		die();
	}
}

if (!isset($str_CURRENCY) OR $str_CURRENCY == "")
	$str_CURRENCY = CSaleLang::GetLangCurrency($LID);

if (isset($ID) && $ID > 0)
	$title = GetMessage("SOEN_TAB_ORDER_TITLE");
else
	$title = GetMessage("SOEN_TAB_ORDER_NEW_TITLE");

$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("SOEN_TAB_ORDER"), "ICON" => "sale", "TITLE" => $title),
);
$tabControl = new CAdminForm("form_order_buyers", $aTabs, false, true);

if (isset($ID) && $ID > 0)
	$APPLICATION->SetTitle(str_replace("#ID#", $ID, GetMessage("NEWO_TITLE_EDIT")));
elseif (isset($LID) && $LID != "")
{
	$siteName = $LID;
	$dbSite = CSite::GetByID($LID);
	if($arSite = $dbSite->Fetch())
		$siteName = $arSite["NAME"]." (".$LID.")";
	$APPLICATION->SetTitle(str_replace("#LID#", $siteName, GetMessage("NEWO_TITLE_ADD")));
}
else
	$APPLICATION->SetTitle(GetMessage("NEWO_TITLE_DEFAULT"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$aMenu = array();
$aMenu = array(
	array(
		"ICON" => "btn_list",
		"TEXT" => GetMessage("SOE_TO_LIST"),
		"LINK" => "/bitrix/admin/sale_order.php?lang=".LANGUAGE_ID
	)
);
$link = urlencode(DeleteParam(array("mode")));
$link = urlencode($GLOBALS["APPLICATION"]->GetCurPage())."?mode=settings".($link <> "" ? "&".$link: "");

$bUserCanViewOrder = CSaleOrder::CanUserViewOrder($ID, $GLOBALS["USER"]->GetUserGroupArray(), $GLOBALS["USER"]->GetID());
$bUserCanDeleteOrder = CSaleOrder::CanUserDeleteOrder($ID, $GLOBALS["USER"]->GetUserGroupArray(), $GLOBALS["USER"]->GetID());
$bUserCanCancelOrder = CSaleOrder::CanUserCancelOrder($ID, $GLOBALS["USER"]->GetUserGroupArray(), $GLOBALS["USER"]->GetID());
$bUserCanDeductOrder = CSaleOrder::CanUserChangeOrderFlag($ID, "PERM_DEDUCTION", $GLOBALS["USER"]->GetUserGroupArray());
$bUserCanMarkOrder = CSaleOrder::CanUserMarkOrder($ID, $GLOBALS["USER"]->GetUserGroupArray(), $GLOBALS["USER"]->GetID());
$bUserCanPayOrder = CSaleOrder::CanUserChangeOrderFlag($ID, "P", $GLOBALS["USER"]->GetUserGroupArray());
$bUserCanDeliverOrder = CSaleOrder::CanUserChangeOrderFlag($ID, "D", $GLOBALS["USER"]->GetUserGroupArray());

if ($bUserCanViewOrder && $ID > 0)
{
	$aMenu[] = array(
		"TEXT" => GetMessage("NEWO_DETAIL"),
		"TITLE"=>GetMessage("NEWO_DETAIL_TITLE"),
		"LINK" => "/bitrix/admin/sale_order_detail.php?ID=".$ID."&lang=".LANGUAGE_ID.GetFilterParams("filter_")
	);
}

if ($ID > 0)
{
	$aMenu[] = array(
		"TEXT" => GetMessage("NEWO_TO_PRINT"),
		"TITLE"=>GetMessage("NEWO_TO_PRINT_TITLE"),
		"LINK" => "/bitrix/admin/sale_order_print.php?ID=".$ID."&lang=".LANGUAGE_ID.GetFilterParams("filter_")
	);
}

if (($saleModulePermissions == "W" || $str_PAYED != "Y") && $bUserCanDeleteOrder && $ID > 0)
{
	$aMenu[] = array(
			"TEXT" => GetMessage("NEWO_ORDER_DELETE"),
			"TITLE"=>GetMessage("NEWO_ORDER_DELETE_TITLE"),
			"LINK" => "javascript:if(confirm('".GetMessage("NEWO_CONFIRM_DEL_MESSAGE")."')) window.location='sale_order.php?ID=".$ID."&action=delete&lang=".LANG."&".bitrix_sessid_get().urlencode(GetFilterParams("filter_"))."'",
			"WARNING" => "Y"
		);
}

//delete context menu for remote query
if (!defined('BX_PUBLIC_MODE') || BX_PUBLIC_MODE != 1)
{
	$context = new CAdminContextMenu($aMenu);
	$context->Show();
}


/*********************************************************************/
/********************  BODY  *****************************************/
/*********************************************************************/

CAdminMessage::ShowMessage($errorMessage);

echo "<div id=\"form_content\">";
$tabControl->BeginEpilogContent();

if (isset($_REQUEST["user_id"]) && IntVal($_REQUEST["user_id"]) > 0 && $_POST["btnTypeBuyer"] != "btnBuyerNew")
{
	$str_USER_ID = IntVal($_REQUEST["user_id"]);
}

?>

<?=bitrix_sessid_post();?>
<input type="hidden" name="lang" value="<?=LANGUAGE_ID?>">
<input type="hidden" name="LID" value="<?=htmlspecialcharsbx($LID)?>">
<input type="hidden" name="ID" value="<?=$ID?>">
<input type="hidden" name="save_order_data" value="Y">
<input type="hidden" name="RECALC_ORDER" id="RECALC_ORDER" value="N">
<?if (isset($_REQUEST["user_id"]) && IntVal($_REQUEST["user_id"]) > 0):?>
	<input type="hidden" name="user_id" value="<?=IntVal($_REQUEST["user_id"])?>">
<?endif;?>
<?
if (isset($_REQUEST["product"]) && count($_REQUEST["product"]) > 0)
{
	foreach ($_REQUEST["product"] as $val)
	{
		if(IntVal($val) > 0)
		{
			?><input type="hidden" name="product[]" value="<?=IntVal($val)?>"><?
		}
	}
}
?><input type="hidden" name="CART_FIX" value="<? echo (0 < intval($ID) ? 'Y' : 'N'); ?>" id="CART_FIX"><?

$tabControl->EndEpilogContent();

if (!isset($LID) || $LID == "")
{
	$rsSites = CSite::GetList($by="id", $order="asc", Array("ACTIVE" => "Y", "DEF" => "Y"));
	$arSite = $rsSites->Fetch();
	$LID = $arSite["ID"];
}

$urlForm = "";
if (isset($ID) AND $ID != "")
{
	$urlForm = "&ID=".$ID."&LID=".CUtil::JSEscape($LID);
	CSaleOrder::Lock($ID);
}
$tabControl->Begin(array(
		"FORM_ACTION" => $APPLICATION->GetCurPage()."?lang=".LANG.$urlForm
));

//TAB ORDER
$tabControl->BeginNextFormTab();

$tabControl->AddSection("NEWO_TITLE_STATUS", GetMessage("NEWO_TITLE_STATUS"));

$tabControl->BeginCustomField("ORDER_STATUS", GetMessage("SOE_STATUS"), true);
?>
	<tr class="adm-detail-required-field">
		<td width="40%"><?=GetMessage("SOE_STATUS")?>:</td>
		<td width="60%">
			<?
			$arFilter = array("LID" => LANGUAGE_ID);
			$arGroupByTmp = false;

			if ($saleModulePermissions < "W")
			{
				$arFilter["GROUP_ID"] = $GLOBALS["USER"]->GetUserGroupArray();
				$arFilter["PERM_STATUS_FROM"] = "Y";
				if (strlen($str_STATUS_ID) > 0)
					$arFilter["ID"] = $str_STATUS_ID;
				$arGroupByTmp = array("ID", "NAME", "MAX" => "PERM_STATUS_FROM");
			}
			$dbStatusList = CSaleStatus::GetList(
				array(),
				$arFilter,
				$arGroupByTmp,
				false,
				array("ID", "NAME", "SORT")
			);

			if ($dbStatusList->GetNext())
			{
			?>
				<select name="STATUS_ID" id="STATUS_ID">
					<?
					$arFilter = array("LID" => LANG);
					$arGroupByTmp = false;
					if ($saleModulePermissions < "W")
					{
						$arFilter["GROUP_ID"] = $GLOBALS["USER"]->GetUserGroupArray();
						$arFilter["PERM_STATUS"] = "Y";
					}
					$dbStatusListTmp = CSaleStatus::GetList(
						array("SORT" => "ASC"),
						$arFilter,
						$arGroupByTmp,
						false,
						array("ID", "NAME", "SORT")
					);
					while($arStatusListTmp = $dbStatusListTmp->GetNext())
					{
						?><option value="<?echo $arStatusListTmp["ID"] ?>"<?if ($arStatusListTmp["ID"]==$str_STATUS_ID) echo " selected"?>><?echo $arStatusListTmp["NAME"] ?> [<?echo $arStatusListTmp["ID"] ?>]</option><?
					}
					?>
				</select>
				<?
			}
			else
			{
				$arStatusLand = CSaleStatus::GetLangByID($str_STATUS_ID, LANGUAGE_ID);
				echo htmlspecialcharsEx("[".$str_STATUS_ID."] ".$arStatusLand["NAME"]);
			}
			?>
			<input type="hidden" name="user_id" id="user_id" value="<?=$str_USER_ID?>" onChange="fUserGetProfile(this);" >
		</td>
	</tr>
<?
$tabControl->EndCustomField("ORDER_STATUS");

if(IntVal($ID) > 0)
{
	$arSitesShop = array();
	$rsSites = CSite::GetList($by="id", $order="asc", Array("ACTIVE" => "Y"));
	while ($arSite = $rsSites->Fetch())
	{
		$site = COption::GetOptionString("sale", "SHOP_SITE_".$arSite["ID"], "");
		if ($arSite["ID"] == $site)
		{
			$arSitesShop[$arSite["ID"]] = array("ID" => $arSite["ID"], "NAME" => $arSite["NAME"]);
		}
	}

	if (count($arSitesShop) > 1)
	{
		$tabControl->BeginCustomField("ORDER_SITE", GetMessage("ORDER_SITE"), true);
		?>
		<tr>
			<td width="40%">
				<?= GetMessage("ORDER_SITE") ?>:
			</td>
			<td width="60%"><?=htmlspecialcharsbx($arSitesShop[$str_LID]["NAME"])." (".$str_LID.")"?>
			</td>
		</tr>
		<?
		$tabControl->EndCustomField("ORDER_SITE");
	}

	$tabControl->BeginCustomField("ORDER_CANCEL", GetMessage("SOE_CANCELED"), true);
	?>
	<tr>
		<td width="40%">
			<?= GetMessage("SOE_CANCELED") ?>:
		</td>
		<td width="60%">
			<input type="checkbox"<?if (!$bUserCanCancelOrder) echo " disabled";?> name="CANCELED" id="CANCELED" value="Y"<?if ($str_CANCELED == "Y") echo " checked";?>>&nbsp;<label for="CANCELED"><?=GetMessage("SO_YES")?></label>
			<?if(strlen($str_DATE_CANCELED) > 0)
			{
				echo "&nbsp;(".$str_DATE_CANCELED.")";
			}
			?>
		</td>
	</tr>
	<tr>
		<td width="40%" valign="top">
			<?= GetMessage("SOE_CANCEL_REASON") ?>:
		</td>
		<td width="60%" valign="top">
			<textarea name="REASON_CANCELED"<?if (!$bUserCanCancelOrder) echo " disabled";?> rows="2" cols="40"><?= $str_REASON_CANCELED ?></textarea>
		</td>
	</tr>
	<?
	$tabControl->EndCustomField("ORDER_CANCEL");
}

$tabControl->AddSection("NEWO_TITLE_BUYER", GetMessage("NEWO_TITLE_BUYER"));

$tabControl->BeginCustomField("NEWO_BUYER", GetMessage("NEWO_BUYER"), true);
?>

<?if ($ID <= 0):?>
<tr>
	<td width="40%" align="right">
		<a onClick="fButtonCurrent('btnBuyerNew')" href="javascript:void(0);" id="btnBuyerNew" class="adm-btn<?if ($_REQUEST["btnTypeBuyer"] == 'btnBuyerNew' || !isset($_REQUEST["btnTypeBuyer"])) echo ' adm-btn-active';?>"><?=GetMessage("NEWO_BUYER_NEW")?></a>
	</td>
	<td width="60%" align="left"><a onClick="fButtonCurrent('btnBuyerExist')" href="javascript:void(0);" id="btnBuyerExist" class="adm-btn<? if ($_REQUEST["btnTypeBuyer"] == 'btnBuyerExist') echo ' adm-btn-active';?>"><?=GetMessage("NEWO_BUYER_SELECT")?></a>
		<?
		$typeBuyerTmp = "btnBuyerNew";
		if ($bVarsFromForm && isset($_REQUEST["btnTypeBuyer"]))
			$typeBuyerTmp = htmlspecialcharsbx($_REQUEST["btnTypeBuyer"]);
		?>

		<input type="hidden" name="btnTypeBuyer" id="btnTypeBuyer" value="<?=$typeBuyerTmp?>" />
	</td>
</tr>
<?endif?>

<tr>
	<td id="buyer_type_change" colspan="2">
		<?=fGetBuyerType($str_PERSON_TYPE_ID, $LID, $str_USER_ID, $ID, $bVarsFromForm);?>

		<script>
		function fButtonCurrent(el)
		{
			if (el == 'btnBuyerNew')
			{
				BX.removeClass(BX("btnBuyerExist"), 'adm-btn-active');
				BX.addClass(BX("btnBuyerNew"), 'adm-btn-active');

				BX("btnBuyerExistField").style.display = 'none';
				BX("btnBuyerNewField").style.display = 'table-row';
				BX("btnTypeBuyer").value = 'btnBuyerNew';
				BX("buyer_profile_display").style.display = 'none';

				if (BX("BREAK_NAME"))
				{
					BX("BREAK_NAME").style.display = 'block';
					BX("NO_BREAK_NAME").style.display = 'none';
				}
			}
			else if (el == 'btnBuyerExist' || el == 'btnBuyerExistRemote')
			{
				BX.addClass(BX("btnBuyerExist"), 'adm-btn-active');
				BX.removeClass(BX("btnBuyerNew"), 'adm-btn-active');

				BX("btnBuyerExistField").style.display = 'table-row';
				if(BX("btnBuyerNewField"))
					BX("btnBuyerNewField").style.display = 'none';
				if(BX("btnTypeBuyer"))
					BX("btnTypeBuyer").value = 'btnBuyerExist';

				if (BX("BREAK_NAME"))
				{
					BX("BREAK_NAME").style.display = 'none';
					BX("NO_BREAK_NAME").style.display = 'block';
				}

				if (el == 'btnBuyerExist')
					window.open('/bitrix/admin/user_search.php?lang=<?=$lang?>&FN=form_order_buyers_form&FC=user_id', '', 'scrollbars=yes,resizable=yes,width=840,height=500,top='+Math.floor((screen.height - 560)/2-14)+',left='+Math.floor((screen.width - 840)/2-5));
			}
		}

		var orderID = '<?=$ID?>';
		var orderPaySysyemID = '<?=$str_PAY_SYSTEM_ID?>';

		function fBuyerChangeType(el)
		{
			var userId = "";

			if (BX("user_id").value != "")
				userId = BX("user_id").value;

			BX.showWait();
			BX.ajax.post('/bitrix/admin/sale_order_new.php', '<?=bitrix_sessid_get()?>&ORDER_AJAX=Y&paysystemid=' + orderPaySysyemID + '&ID=' + orderID + '&LID=<?=CUtil::JSEscape($LID)?>&buyertypechange=' + el.value + '&userId=' + userId, fBuyerChangeTypeResult);
		}
		function fBuyerChangeTypeResult(res)
		{
			BX.closeWait();
			var rss = eval( '('+res+')' );

			if (rss["status"] == "ok")
			{
				BX('CART_FIX').value= 'N';

				var userEl = BX("user_id");
				var orderID = '<?=$ID?>';

				locationID = rss["location_id"];
				locationZipID = rss["location_zip_id"];

				document.getElementById("buyer_type_change").innerHTML = rss["buyertype"];
				document.getElementById("buyer_type_delivery").innerHTML = rss["buyerdelivery"];

				if (userEl.value != "" && (orderID == '' || orderID == 0))
				{
					fUserGetProfile(userEl);
				}
				else
				{
					fRecalProduct('', '', 'N', 'N');
				}
			}
		}
		function fChangeProfile(el)
		{
			var userId = document.getElementById("user_id").value;
			var buyerType = document.getElementById("buyer_type_id").value;

			if (userId != "" && buyerType != "")
			{
				fGetExecScript(userId, buyerType, el.value);
			}
			else
			{
				BX.closeWait();
			}
		}
		function fLocationResult(result)
		{
			var res = eval( '('+result+')' );

			if (res["status"] == "ok")
			{
				BX('CART_FIX').value= 'N';
				document.getElementById("LOCATION_CITY_ORDER_PROP_" + res["prop_id"]).innerHTML = res["location"];
				fRecalProduct('', '', 'N', 'N');
			}
		}
		//////////
		function fUserGetProfile(el)
		{
			var userId = el.value;
			var buyerType = document.getElementById("buyer_type_id").value;
			document.getElementById("buyer_profile_display").style.display = "none";

			if (userId != "" && buyerType != "")
			{
				BX.showWait();
				BX.ajax.post('/bitrix/admin/sale_order_new.php', '<?=bitrix_sessid_get()?>&ORDER_AJAX=Y&id=<?=$ID?>&LID=<?=CUtil::JSEscape($LID)?>&currency=<?=$str_CURRENCY?>&userId=' + userId + '&buyerType=' + buyerType, fUserGetProfileResult);
			}
		}
		function fUserGetProfileResult(res)
		{
			var rs = eval( '('+res+')' );
			if (rs["status"] == "ok")
			{
				BX.closeWait();
				document.getElementById("buyer_profile_display").style.display = "table-row";
				document.getElementById("buyer_profile_select").innerHTML = rs["userProfileSelect"];
				document.getElementById("user_name").innerHTML = rs["userName"];

				if (rs["viewed"].length > 0)
				{
					document.getElementById("buyer_viewed").innerHTML = rs["viewed"];
					fTabsSelect('buyer_viewed', 'tab_3');
				}
				else
				{
					document.getElementById("buyer_viewed").innerHTML = '';
					BX('tab_3').style.display = "none";
					BX('buyer_viewed').style.display = "none";

					if (BX('tab_1').style.display == "block")
						fTabsSelect('user_recomendet', 'tab_1');
					else if (BX('tab_2').style.display == "block")
						fTabsSelect('user_basket', 'tab_2');

				}
				if (rs["userBasket"].length > 0)
				{
					document.getElementById("user_basket").innerHTML = rs["userBasket"];
					fTabsSelect('user_basket', 'tab_2');
				}
				else
				{
					document.getElementById("user_basket").innerHTML = '';
					BX('tab_2').style.display = "none";
					BX('user_basket').style.display = "none";

					if (BX('tab_1').style.display == "block")
						fTabsSelect('user_recomendet', 'tab_1');
					else if (BX('tab_3').style.display == "block")
						fTabsSelect('buyer_viewed', 'tab_3');

				}
				var profile = document.getElementById("user_profile");
				fChangeProfile(profile);
			}
			else
			{
				BX.closeWait();
			}
		}
		function fGetExecScript(userId, buyerType, profileDefault)
		{
			BX.ajax({
				url: '/bitrix/admin/sale_order_new.php',
				method: 'POST',
				data : '<?=bitrix_sessid_get()?>&ORDER_AJAX=Y&LID=<?=CUtil::JSEscape($LID)?>&userId=' + userId + '&buyerType=' + buyerType + '&profileDefault=' + profileDefault,
				dataType: 'html',
				timeout: 10,
				async: true,
				processData: true,
				scriptsRunFirst: true,
				emulateOnload: true,
				start: true,
				cache: false
			});
			BX.closeWait();
		}
		</script>
	</td>
</tr>
<?
$tabControl->EndCustomField("NEWO_BUYER");

$tabControl->AddSection("BUYER_DELIVERY", GetMessage("SOE_DELIVERY"));

$tabControl->BeginCustomField("DELIVERY_SERVICE", GetMessage("NEWO_DELIVERY_SERVICE"), true);

//select basket product and calc weight
$arBasketItem = array();
$arBasketItemIDs = array();
$useStores = false;
$hasSavedBarcodes = false;
$hasProductsWithMultipleBarcodes = false;
$arProductBarcode = array();

$arElId = array();

if ((isset($_REQUEST["PRODUCT"]) AND is_array($_REQUEST["PRODUCT"]) AND count($_REQUEST["PRODUCT"]) > 0) AND $bVarsFromForm)
{
	foreach ($_REQUEST["PRODUCT"] as $key => $val)
	{
		foreach ($val as $k => $v) // product fields
		{
			if (!is_array($v))
			{
				$val[$k] = htmlspecialcharsbx($v);
			}
			else
			{
				foreach ($v as $kp => $vp)
				{
					foreach ($vp as $kkp => $vvp)
					{
						if (!is_array($vvp))
						{
							$val[$k][$kp][$kkp] = htmlspecialcharsbx($vvp);
						}
						else //barcodes internal arrays
						{
							foreach ($vvp as $kvvp => $vvvp)
							{
								$val[$k][$kp][$kkp][$kvvp] = htmlspecialcharsbx($vvvp);
							}
						}
					}
				}
			}
		}

		$val["ID"] = $key;
		$arBasketItem[$key] = $val;

		//set variables modifying form look
		if ($arBasketItem[$key]["BARCODE_MULTI"] == "Y")
		{
			if (!$hasProductsWithMultipleBarcodes)
				$hasProductsWithMultipleBarcodes = true;
		}

		if (!$useStores && isset($arBasketItem[$key]["STORES"]) && count($arBasketItem[$key]["STORES"]) > 0 && intval($storeCount) > 0)
			$useStores = true;

		if (!$hasSavedBarcodes && $arBasketItem[$key]["HAS_SAVED_QUANTITY"] == "Y")
			$hasSavedBarcodes = true;

		$arElId[] = $val["PRODUCT_ID"];
		$arParent = CCatalogSku::GetProductInfo($val["PRODUCT_ID"]);

		if ($arParent)
			$arElId[] = $arParent["ID"];
	}
}
elseif (isset($ID) AND $ID > 0)
{
	$bXmlId = COption::GetOptionString("sale", "show_order_product_xml_id", "N");

	$dbBasket = CSaleBasket::GetList(
		array("NAME" => "ASC"),
		array("ORDER_ID" => $ID),
		false,
		false,
		array(
			"ID",
			"PRODUCT_ID",
			"PRODUCT_PRICE_ID",
			"PRICE",
			"CURRENCY",
			"WEIGHT",
			"QUANTITY",
			"NAME",
			"MODULE",
			"CALLBACK_FUNC",
			"NOTES",
			"DETAIL_PAGE_URL",
			"DISCOUNT_PRICE",
			"DISCOUNT_VALUE",
			"ORDER_CALLBACK_FUNC",
			"CANCEL_CALLBACK_FUNC",
			"PAY_CALLBACK_FUNC",
			"PRODUCT_PROVIDER_CLASS",
			"CATALOG_XML_ID",
			"PRODUCT_XML_ID",
			"VAT_RATE",
			"BARCODE_MULTI",
			"RESERVED",
			"CUSTOM_PRICE"
		)
	);
	while ($arBasket = $dbBasket->GetNext())
	{
		$arPropsFilter = array("BASKET_ID" => $arBasket["ID"]);

		if ($bXmlId == "N")
			$arPropsFilter["!CODE"] = array("PRODUCT.XML_ID", "CATALOG.XML_ID");

		$arBasket["PROPS"] = Array();
		$dbBasketProps = CSaleBasket::GetPropsList(
				array("SORT" => "ASC", "NAME" => "ASC"),
				$arPropsFilter,
				false,
				false,
				array("ID", "BASKET_ID", "NAME", "VALUE", "CODE", "SORT")
			);
		while ($arBasketProps = $dbBasketProps->GetNext())
			$arBasket["PROPS"][$arBasketProps["ID"]] = $arBasketProps;

		$arBasketItem[$arBasket["ID"]] = $arBasket;

		$arElId[] = $arBasket["PRODUCT_ID"];
		$arBasketItemIDs[] = $arBasket["ID"];
		$arParent = CCatalogSku::GetProductInfo($arBasket["PRODUCT_ID"]);

		if ($arParent)
			$arElId[] = $arParent["ID"];

		if ($arBasketItem[$arBasket["ID"]]["BARCODE_MULTI"] == "Y")
		{
			if (!$hasProductsWithMultipleBarcodes)
				$hasProductsWithMultipleBarcodes = true;
		}

		$arBasketItem[$arBasket["ID"]]["STORES"] = array();

		$arBasketItem[$arBasket["ID"]]["HAS_SAVED_QUANTITY"] = "N";

		/** @var $productProvider IBXSaleProductProvider */
		if ($productProvider = CSaleBasket::GetProductProvider($arBasket))
		{
			$storeCount = $productProvider::GetStoresCount();
			if ($storeCount > 0)
			{
				if ($arProductStore = $productProvider::GetProductStores(array("PRODUCT_ID" => $arBasket["PRODUCT_ID"])))
				{
					$arBasketItem[$arBasket["ID"]]["STORES"] = $arProductStore;
					if (!$useStores)
						$useStores = true;
				}

				// if barcodes/store quantity are already saved for this product,
				// then check if barcodes are still valid and save them to the store array
				$ind = 0;
				$dbres = CSaleStoreBarcode::GetList(
					array(),
					array("BASKET_ID" => $arBasket["ID"]),
					false,
					false,
					array("ID", "BASKET_ID", "BARCODE", "STORE_ID", "ORDER_ID", "QUANTITY")
				);
				while ($arRes = $dbres->GetNext())
				{
					$arCheckBarcodeFields = array(
						"BARCODE"    => $arRes["BARCODE"],
						"PRODUCT_ID" => $arBasket["PRODUCT_ID"],
						"ORDER_ID"   => $ID
					);

					if ($arBasketItem[$arBasket["ID"]]["BARCODE_MULTI"] == "Y")
						$arFields["STORE_ID"] = $arRes["STORE_ID"];

					if ($arRes["BARCODE"] == "")
						$res = true;
					else
						$res = $productProvider::CheckProductBarcode($arCheckBarcodeFields);

					//TODO - not checked anymore - show or hide?
					//saving barcode and quantity info to the specific store array
					// if ($res)
					// {
					foreach ($arBasketItem[$arBasket["ID"]]["STORES"] as $storeId => $arStoreInfo)
					{
						if ($arStoreInfo["STORE_ID"] == $arRes["STORE_ID"])
						{
							$arBasketItem[$arBasket["ID"]]["STORES"][$storeId]["BARCODE"][$arRes["ID"]] = $arRes["BARCODE"];
							$arBasketItem[$arBasket["ID"]]["STORES"][$storeId]["BARCODE_FOUND"][$arRes["ID"]] = ($res) ? "Y" : "N";

							if ($arBasketItem[$arBasket["ID"]]["BARCODE_MULTI"] == "Y")
								$arBasketItem[$arBasket["ID"]]["STORES"][$storeId]["QUANTITY"] += $arRes["QUANTITY"];
							else
								$arBasketItem[$arBasket["ID"]]["STORES"][$storeId]["QUANTITY"] = $arRes["QUANTITY"];
						}
					}

					$arBasketItem[$arBasket["ID"]]["HAS_SAVED_QUANTITY"] = "Y";

					if (!$hasSavedBarcodes)
						$hasSavedBarcodes = true;
					// }
					$ind++;
				}
			}
			// else if ($storeCount == -1) TODO - storeCount = -1 not used at all
			// storeCount = 0 - different logic?
			// }
		}
	}
}

?>
<input type="hidden" name="storeCount" value="<?=$storeCount?>">
<?
CModule::IncludeModule('catalog');

$arCatResult = array();
$res = CIBlockElement::GetList(array(), array("ID" => $arElId), false, false, array('ID', 'IBLOCK_ID', 'IBLOCK_SECTION_ID', 'IBLOCK_TYPE_ID', 'PREVIEW_PICTURE', 'DETAIL_PICTURE'));
while ($arCat = $res->Fetch())
{
	if ($arCat["IBLOCK_ID"] > 0)
		$arCatResult[$arCat["ID"]] = $arCat;
}

foreach ($arBasketItem as $key => $val)
{
	if ($val["MODULE"] == "catalog")
	{
		$arBasketItem[$key]["EDIT_PAGE_URL"] = "/bitrix/admin/iblock_element_edit.php?ID=".$val["PRODUCT_ID"]
				."&type=".$arCatResult[$val["PRODUCT_ID"]]["IBLOCK_TYPE_ID"]."&lang=".LANG."&IBLOCK_ID="
				.$arCatResult[$val["PRODUCT_ID"]]["IBLOCK_ID"]."&find_section_section=".$arCatResult[$val["PRODUCT_ID"]]["IBLOCK_SECTION_ID"];
	}
}

$productWeight = 0;
foreach($arBasketItem as $val)
	$productWeight += ($val["WEIGHT"] * $val["QUANTITY"]);

$arDeliveryOrder = fGetDelivery($locationID, $locationZipID, $productWeight, ($str_PRICE-$str_PRICE_DELIVERY), $str_CURRENCY, $LID, $str_DELIVERY_ID);
?>
	<tr>
		<td class="adm-detail-content-cell-l" width="40%">
			<?=GetMessage("SOE_DELIVERY_COM")?>:
		</td>
		<td width="60%" class="adm-detail-content-cell-r">
			<div id="DELIVERY_SELECT"><?=$arDeliveryOrder["DELIVERY"]; ?></div>
			<div id="DELIVER_ID_DESC"><?=$arDeliveryOrder["DELIVERY_DEFAULT_DESCRIPTION"]?></div>
		</td>
	</tr>
	<tr>
		<td class="adm-detail-content-cell-l">
			<?=GetMessage("SOE_DELIVERY_PRICE")?>:
		</td>
		<td class="adm-detail-content-cell-r">
			<?
				$deliveryPrice = roundEx($str_PRICE_DELIVERY, SALE_VALUE_PRECISION);

				if ($bVarsFromForm)
					$deliveryPrice = roundEx($PRICE_DELIVERY, SALE_VALUE_PRECISION);
			?>
			<input type="text" onChange="fChangeDeliveryPrice();" name="PRICE_DELIVERY" id="DELIVERY_ID_PRICE" size="10" maxlength="20" value="<?=$deliveryPrice;?>" >
			<input type="hidden" name="change_delivery_price" value="N" id="change_delivery_price">
			<script>
				function fChangeDeliveryPrice()
				{
					document.getElementById("change_delivery_price").value = "Y";
					fRecalProduct('', '', 'N', 'N');
				}

				function fChangeDelivery()
				{
					BX('CART_FIX').value= 'N';
					document.getElementById("change_delivery_price").value = "N";
					fRecalProduct('', '', 'N', 'N');
				}
			</script>
		</td>
	</tr>
<?
$tabControl->EndCustomField("DELIVERY_SERVICE");

if(IntVal($ID) > 0)
{
	$tabControl->BeginCustomField("ORDER_ALLOW_DELIVERY", GetMessage("SOE_DELIVERY_ALLOWED"), true);
	?>
	<tr>
		<td width="40%">
			<?= GetMessage("SOE_DELIVERY_ALLOWED") ?>:
		</td>
		<td width="60%">
			<input type="checkbox" name="ALLOW_DELIVERY" id="ALLOW_DELIVERY"<?if (!$bUserCanDeliverOrder) echo " disabled";?> value="Y"<?if ($str_ALLOW_DELIVERY == "Y") echo " checked";?>>&nbsp;<label for="ALLOW_DELIVERY"><?=GetMessage("SO_YES")?></label>
			<?if(strlen($str_DATE_ALLOW_DELIVERY) > 0)
			{
				echo "&nbsp;(".$str_DATE_ALLOW_DELIVERY.")";
			}
			?>
		</td>
	</tr>
	<tr>
		<td width="40%">
			<?= GetMessage("SOE_DEL_VOUCHER_NUM") ?>:
		</td>
		<td width="60%">
			<input type="text" name="DELIVERY_DOC_NUM" value="<?= $str_DELIVERY_DOC_NUM ?>" size="20" maxlength="20">
		</td>
	</tr>
	<tr>
		<td width="40%">
			<?= GetMessage("SOE_DEL_VOUCHER_DATE") ?>:
		</td>
		<td width="60%">
			<?= CalendarDate("DELIVERY_DOC_DATE", $str_DELIVERY_DOC_DATE, "form_order_buyers_form", "10", "class=\"typeinput\""); ?>
		</td>
	</tr>
	<?
	$tabControl->EndCustomField("ORDER_ALLOW_DELIVERY");
}

$tabControl->AddSection("BUYER_PAYMENT", GetMessage("SOE_PAYMENT"));

$tabControl->BeginCustomField("BUYER_PAY_SYSTEM", GetMessage("SOE_PAY_SYSTEM"), true);
?>
<tr>
	<td id="buyer_type_delivery" colspan="2">
		<?=fBuyerDelivery($str_PERSON_TYPE_ID, $str_PAY_SYSTEM_ID);?>
	</td>
</tr>
<?
$tabControl->EndCustomField("BUYER_PAY_SYSTEM");

if(IntVal($ID) > 0)
{
	$tabControl->BeginCustomField("ORDER_PAYED", GetMessage("SOE_ORDER_PAID"), true);
	?>
	<tr>
		<td width="40%" valign="top">
			<?= GetMessage("SOE_ORDER_PAID") ?>:
		</td>
		<td width="60%">
			<input type="checkbox"<?if (!$bUserCanPayOrder) echo " disabled";?> name="PAYED" id="PAYED" value="Y"<?if ($str_PAYED == "Y") echo " checked";?> onchange="BX.show(BX('ORDER_PAYED_MORE'))">&nbsp;<label for="PAYED"><?=GetMessage("SO_YES")?></label>
			<?if(strlen($str_DATE_PAYED) > 0)
			{
				echo "&nbsp;(".$str_DATE_PAYED.")";
			}
			?><div id="ORDER_PAYED_MORE" style="display:none;"><?
			$arPayDefault = fGetPayFromAccount($str_USER_ID, $str_CURRENCY);
			if($str_PAYED == "Y")
			{
				?>
				<input type="checkbox" name="PAY_FROM_ACCOUNT_BACK" id="PAY_FROM_ACCOUNT_BACK" value="Y"/>&nbsp;<label for="PAY_FROM_ACCOUNT_BACK"><?=GetMessage('SOD_PAY_ACCOUNT_BACK')?></label>
				<?
			}
			else
			{
				$buyerCanPay = "none";
				if (DoubleVal($arPayDefault["PAY_BUDGET"]) > 0):
					$buyerCanPay = "block";
				endif;
				?>
				<span id="buyerCanBuy" style="display:<?=$buyerCanPay?>">
					<input type="checkbox" name="PAY_CURRENT_ACCOUNT" id="PAY_CURRENT_ACCOUNT" value="Y" <?if ($PAY_CURRENT_ACCOUNT == "Y") echo " checked";?><?if (!$bUserCanPayOrder) echo " disabled";?>/>&nbsp;<label for="PAY_CURRENT_ACCOUNT"><?=GetMessage("NEWO_CURRENT_ACCOUNT")?> (<span id="PAY_CURRENT_ACCOUNT_DESC"><?=$arPayDefault["PAY_MESSAGE"]?></span>)</label>
				</span>
				<?
			}
			?>
			</div>
		</td>
	</tr>
	<tr>
		<td width="40%">
			<?= GetMessage("SOE_VOUCHER_NUM") ?>:
		</td>
		<td width="60%">
			<input type="text" name="PAY_VOUCHER_NUM" value="<?= $str_PAY_VOUCHER_NUM ?>" size="20" maxlength="20">
		</td>
	</tr>
	<tr>
		<td width="40%">
			<?= GetMessage("SOE_VOUCHER_DATE") ?>:
		</td>
		<td width="60%">
			<?= CalendarDate("PAY_VOUCHER_DATE", $str_PAY_VOUCHER_DATE, "form_order_buyers_form", "10", "class=\"typeinput\"".((!$bUserCanPayOrder) ? " disabled" : "")); ?>
		</td>
	</tr>
	<?
	$tabControl->EndCustomField("ORDER_PAYED");
}

$tabControl->AddSection("NEWO_COMMENTS", GetMessage("NEWO_COMMENTS"));
$tabControl->BeginCustomField("NEWO_COMMENTS_A", GetMessage("NEWO_COMMENTS"), true);
?>
<tr>
	<td width="40%" valign="top"><?=GetMessage("SOE_COMMENT")?>:<br /><small><?=GetMessage("SOE_COMMENT_NOTE")?></small></td>
	<td width="60%">
		<textarea name="COMMENTS" cols="40" rows="5"><?=$str_COMMENTS?></textarea>
	</td>
</tr>
<?if (strlen($str_ADDITIONAL_INFO) > 0):?>
<tr>
	<td width="40%" valign="top"><?=GetMessage("SOE_ADDITIONAL")?>:</td>
	<td width="60%">
		<?=$str_ADDITIONAL_INFO?>
	</td>
</tr>
<?
endif;
$tabControl->EndCustomField("NEWO_COMMENTS_A");

//order marked
if(IntVal($ID) > 0)
{

	$tabControl->AddSection("ORDER_MARKING", GetMessage("SOE_MARK"));

	$tabControl->BeginCustomField("ORDER_MARK", GetMessage("SOE_MARKED"), true);
	?>
	<tr>
		<td width="40%">
			<?= GetMessage("SOE_MARKED") ?>:
		</td>
		<td width="60%">
			<input type="checkbox"<?if (!$bUserCanMarkOrder) echo " disabled";?> onclick="fShowReasonMarkedBlock(this.checked);" name="MARKED" id="MARKED" value="Y"<?if ($str_MARKED == "Y") echo " checked";?>>&nbsp;<label for="MARKED"><?=GetMessage("SO_YES");?></label>
			<?if(strlen($str_DATE_MARKED) > 0 && $str_MARKED == "Y")
			{
				echo "&nbsp;(".$str_DATE_MARKED.")";
			}
			?>
		</td>
	</tr>
	<tr id="reason_marked_block" style="display:<?=(strlen($str_DATE_MARKED) > 0 && ($str_MARKED == "Y")) ? "table-row" : "none"?>">
		<td width="40%" valign="top">
			<?= GetMessage("SOE_MARK_REASON") ?>:
		</td>
		<td width="60%" valign="top">
			<textarea id="REASON_MARKED" name="REASON_MARKED"<?if (!$bUserCanMarkOrder) echo " disabled";?> rows="5" cols="40"><?= $str_REASON_MARKED ?></textarea>
		</td>
	</tr>
	<script type="text/javascript">
		function fShowReasonMarkedBlock(isChecked) {

			var reasonBlock = BX('reason_marked_block');
				reasonTextarea = BX('REASON_MARKED');

			if (isChecked)
			{
				reasonBlock.style.display = 'table-row';
			}
			else
			{
				if (reasonTextarea.value == '')
					reasonBlock.style.display = 'none';
			}
		}
	</script>
	<?
	$tabControl->EndCustomField("ORDER_MARK");
}

if(IntVal($ID) > 0)
{
$tabControl->AddSection("ORDER_DEDUCTION", GetMessage("SOE_DEDUCTION"));

	$tabControl->BeginCustomField("ORDER_DEDUCT", GetMessage("SOE_DEDUCTED"), true);
	?>
	<tr>
		<td width="40%">
			<?
			if ($str_DEDUCTED == "Y")
				echo GetMessage("SOE_DEDUCTED");
			else
				echo GetMessage("SOE_DO_DEDUCT");
			?>
		</td>
		<td width="60%">
			<input name="DEDUCTED" id="DEDUCTED" type="checkbox" <?if(!$bUserCanDeductOrder)echo"disabled";?> value="<?=($str_DEDUCTED == "Y") ? "Y" : "N"?>" <?if($str_DEDUCTED == "Y")echo"checked";?> onclick="toggleStoresView(this, <?=($useStores) ? "true" : "false"?>)">
			<input name="ORDER_DEDUCTED" id="ORDER_DEDUCTED" type="hidden" value="<?=($str_DEDUCTED == "Y") ? "Y" : "N"?>">
			<input name="HAS_PRODUCTS_WITH_BARCODE_MULTI" id="HAS_PRODUCTS_WITH_BARCODE_MULTI" type="hidden" value="<?=($hasProductsWithMultipleBarcodes) ? "Y" : "N"?>" />
			<input name="HAS_SAVED_BARCODES" id="HAS_SAVED_BARCODES" type="hidden" value="<?=($hasSavedBarcodes) ? "Y" : "N"?>" />
			<label for="DEDUCTED"><?=GetMessage("SO_YES")?></label>
			<?
			if (strlen($str_DATE_DEDUCTED) > 0):
				echo "&nbsp;(".$str_DATE_DEDUCTED.")";
			endif;
			?>
		</td>
	</tr>
	<tr id="reason_undo_deducted_area" <?=($str_DEDUCTED == "N" && strlen($str_REASON_UNDO_DEDUCTED) > 0) ? "style=\"display:table-row\"" : "style=\"display:none\""  ?>>
		<td width="40%" valign="top">
			<?= GetMessage("SOE_UNDO_DEDUCT_REASON") ?>:
		</td>
		<td width="60%" valign="top">
			<textarea name="REASON_UNDO_DEDUCTED" <?if (!$bUserCanDeductOrder) echo " disabled"?> rows="2" cols="40"><?= $str_REASON_UNDO_DEDUCTED ?></textarea>
		</td>
	</tr>
	<?
	$tabControl->EndCustomField("ORDER_DEDUCT");
}

$tabControl->BeginCustomField("NEWO_TITLE_ORDER", GetMessage("NEWO_TITLE_ORDER"), true);
?>
<tr>
	<td colspan="2" valign="top">
		<table width="100%" cellspacing="0" cellpadding="0">
			<tr>
				<td width="88%" align="left" class="heading" ><?=GetMessage("NEWO_TITLE_ORDER")?></td>
				<td align="right" nowrap>
					<a title="<?=GetMessage("SOE_ADD_ITEMS")?>" onClick="AddProductSearch(1);" class="adm-btn adm-btn-green adm-btn-add"  style="white-space:nowrap;" href="javascript:void(0);"><?=GetMessage("SOE_ADD_ITEMS")?></a>
				</td>
			</tr>
		</table>
	</td>
</tr>
<?
$tabControl->EndCustomField("NEWO_TITLE_ORDER");

$tabControl->BeginCustomField("BASKET_CONTAINER", GetMessage("NEWO_BASKET_CONTAINER"), true);
?>
<tr>
	<td colspan="2" id="ID_BASKET_CONTAINER">
		<?
		if(!empty($_REQUEST["productDelay"]) || !empty($_REQUEST["productSub"]) || !empty($_REQUEST["productNA"]))
		{
			echo BeginNote();
			echo GetMessage("NEWO_PRODUCTS_MES")."<br />";
			if(!empty($_REQUEST["productSub"]))
			{
				$dbItem = CIBlockElement::GetList(Array(), Array("ID" => $_REQUEST["productSub"]), false, false, Array("ID", "NAME", "IBLOCK_ID", "IBLOCK_SECTION_ID"));
				while($arItem = $dbItem->Fetch())
					echo "<b>"."<a href=\"/bitrix/admin/iblock_element_edit.php?ID=".$arItem["ID"]."&type=catalog&lang=".LANG."&IBLOCK_ID=".$arItem["IBLOCK_ID"]."&find_section_section=".$arItem["IBLOCK_SECTION_ID"]."\">".htmlspecialcharsbx($arItem["NAME"])."</a></b> (".GetMessage("NEWO_PRODUCTS_SUB").")<br />";
			}
			if(!empty($_REQUEST["productDelay"]))
			{
				$dbItem = CIBlockElement::GetList(Array(), Array("ID" => $_REQUEST["productDelay"]), false, false, Array("ID", "NAME", "IBLOCK_ID", "IBLOCK_SECTION_ID"));
				while($arItem = $dbItem->Fetch())
					echo "<b>"."<a href=\"/bitrix/admin/iblock_element_edit.php?ID=".$arItem["ID"]."&type=catalog&lang=".LANG."&IBLOCK_ID=".$arItem["IBLOCK_ID"]."&find_section_section=".$arItem["IBLOCK_SECTION_ID"]."\">".htmlspecialcharsbx($arItem["NAME"])."</a></b> (".GetMessage("NEWO_PRODUCTS_DELAY").")<br />";
			}
			if(!empty($_REQUEST["productNA"]))
			{
				$dbItem = CIBlockElement::GetList(Array(), Array("ID" => $_REQUEST["productNA"]), false, false, Array("ID", "NAME", "IBLOCK_ID", "IBLOCK_SECTION_ID"));
				while($arItem = $dbItem->Fetch())
					echo "<b>"."<a href=\"/bitrix/admin/iblock_element_edit.php?ID=".$arItem["ID"]."&type=catalog&lang=".LANG."&IBLOCK_ID=".$arItem["IBLOCK_ID"]."&find_section_section=".$arItem["IBLOCK_SECTION_ID"]."\">".htmlspecialcharsbx($arItem["NAME"])."</a></b> (".GetMessage("NEWO_PRODUCTS_NA").")<br />";
			}
			echo EndNote();
		}
		?>
		<script language="JavaScript">
			var arProduct = [];
			var arProductEditCountProps = [];
			var countProduct = 0;
		</script>
		<?
		$arCurFormat = CCurrencyLang::GetCurrencyFormat($str_CURRENCY);
		$CURRENCY_FORMAT = trim(str_replace("#", '', $arCurFormat["FORMAT_STRING"]));
		$ORDER_TOTAL_PRICE = 0;
		$ORDER_PRICE_WITH_DISCOUNT = 0;
		$productCountAll = 0;
		$productWeight = 0;
		$arFilterRecomendet = array();
		$WEIGHT_UNIT = htmlspecialcharsbx(COption::GetOptionString('sale', 'weight_unit', "", $LID));
		$WEIGHT_KOEF = htmlspecialcharsbx(COption::GetOptionString('sale', 'weight_koef', 1, $LID));

		$QUANTITY_FACTORIAL = COption::GetOptionString('sale', 'QUANTITY_FACTORIAL', "N");
		if (!isset($QUANTITY_FACTORIAL) OR $QUANTITY_FACTORIAL == "")
			$QUANTITY_FACTORIAL = 'N';

		//edit form props
		$formTemplate = '
					<input id="FORM_BASKET_PRODUCT_ID" name="BASKET_PRODUCT_ID" value="" type="hidden">
					<table class="edit-table" style="background-color:rgb(245, 249, 249); border: 1px solid #B8C1DD; width: 600px;font-size:12px;" >
					<tr style="background-color:rgb(224, 232, 234);color:#525355;font-weight:bold;text-align:center;">
						<td colspan="2" align="center">
						<table width="100%">
						<tr>
							<td align="center">'.GetMessage("SOE_BASKET_EDIT").'</td>
							<td width="10"><a href="javascript:void(0);" onClick="SaleBasketEditTool.PopupHide();" style="color:#525355;float:right;margin-right:5px;font-weight:normal;text-decoration:none;font-size:12px;">&times;<a></td>
						</tr>
						</table>
						</td>
					</tr>
					<tr>
						<td width="40%">&nbsp;</td>
						<td align="left" width="60%">
						<div id="basketError" style="display:none;">
							<table class="message message-error" border="0" cellpadding="0" cellspacing="0" style="border:2px solid #FF0000;color:#FF0000">
								<tr>
									<td>
										<table class="content" border="0" cellpadding="0" cellspacing="0" style="margin:4px;">
											<tr>
												<td valign="top"><div class="icon-error"></div></td>
												<td>
													<span class="message-title" style="font-weight:bold;">'.GetMessage("SOE_BASKET_ERROR").'</span><br>
													<div class="empty" style="height: 5px;"></div><div id="basketErrorText"></div>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							</table>
						</div></td>
					</tr>
					<tr id="FORM_NEWPROD_CODE" class="adm-detail-required-field">
						<td class="field-name" align="right">'.GetMessage("SOE_ITEM_ID").':</td>
						<td><input size="10" id="FORM_PROD_BASKET_ID" name="FORM_PROD_BASKET_ID" type="text" value="" tabindex="1"></td>
					</tr>
					<tr class="adm-detail-required-field">
						<td class="field-name" align="right">'.GetMessage("SOE_ITEM_NAME").':</td>
						<td><input size="40" id="FORM_PROD_BASKET_NAME" name="FORM_PROD_BASKET_NAME" type="text" value="" tabindex="2"></td>
					</tr>
					<tr>
						<td class="field-name" align="right">'.GetMessage("SOE_ITEM_PATH").':</td>
						<td><input id="FORM_PROD_BASKET_DETAIL_URL" name="FORM_BASKET_CATALOG_XML_ID" value="" size="40" type="text" tabindex="3"></td>
					</tr>
					<tr>
						<td class="field-name" align="right">'.GetMessage("SOE_BASKET_CATALOG_XML").':</td>
						<td><input id="FORM_BASKET_CATALOG_XML" name="FORM_BASKET_CATALOG_XML" value="" size="40" type="text" tabindex="4"></td>
					</tr>
					<tr>
						<td class="field-name" align="right">'.GetMessage("SOE_BASKET_PRODUCT_XML").':</td>
						<td><input id="FORM_PROD_BASKET_PRODUCT_XML" name="FORM_PROD_BASKET_PRODUCT_XML" value="" size="40" type="text" tabindex="5"></td>
					</tr>
					<tr>
						<td class="field-name" align="right">'.GetMessage("SOE_ITEM_DESCR").':</td>
						<td><input name="FORM_PROD_BASKET_NOTES" id="FORM_PROD_BASKET_NOTES" size="40" maxlength="250" value="" type="text" tabindex="6"></td>
					</tr>
					<tr>
						<td class="field-name" align="right" valign="top" width="40%">'.GetMessage("SOE_ITEM_PROPS").':</td>
						<td width="60%">
							<table id="BASKET_PROP_TABLE" class="internal" border="0" cellpadding="3" cellspacing="1" style="width: 390px;">
								<tr class="heading" style="border-collapse:collapse;background-color:#E7EAF5;color:#525355;">
									<td align="center">'.GetMessage("SOE_IP_NAME").'</td>
									<td align="center">'.GetMessage("SOE_IP_VALUE").'</td>
									<td align="center">'.GetMessage("SOE_IP_CODE").'</td>
									<td align="center">'.GetMessage("SOE_IP_SORT").'</td>
								</tr>
							</table>

							<input value="'.GetMessage("SOE_PROPERTY_MORE").'" onclick="BasketAddPropSection()" type="button">
						</td>
					</tr>
					<tr>
						<td class="field-name" align="right">'.GetMessage("SALE_F_QUANTITY").':</td>
						<td><input name="FORM_PROD_BASKET_QUANTITY" id="FORM_PROD_BASKET_QUANTITY" size="10" maxlength="20" value="1" type="text" tabindex="7"></td>
					</tr>
					<tr>
						<td class="field-name" align="right">'.GetMessage("SALE_F_PRICE").':</td>
						<td><input name="FORM_PROD_BASKET_PRICE" id="FORM_PROD_BASKET_PRICE" size="10" maxlength="20" value="1" type="text" tabindex="8"> ('.$CURRENCY_FORMAT.')</td>
					</tr>
					<tr>
						<td class="field-name" align="right">'.GetMessage("SOE_WEIGHT").':</td>
						<td><input name="FORM_PROD_BASKET_WEIGHT" id="FORM_PROD_BASKET_WEIGHT" size="10" maxlength="20" value="0" type="text" tabindex="9"> ('.GetMessage("SOE_GRAMM").')</td>
					</tr>
					<tr>
						<td colspan="2" align="center"><br><input name="btn1" value="'.GetMessage("SOE_APPLY").'" onclick="SaveProduct();" type="button"> <input name="btn2" value="'.GetMessage("SALE_CANCEL").'" onclick="SaleBasketEditTool.PopupHide();" type="button"></td>
					</tr>
					<tr>
						<td colspan="2">&nbsp;</td>
					</tr>
					</table>';
		$formTemplate = CUtil::JSEscape($formTemplate);
	?>
	<br>
	<table cellpadding="3" cellspacing="1" border="0" width="100%" class="internal" id="BASKET_TABLE">
		<tr id="heading_with_stores" class="heading" <?=($useStores && ($str_DEDUCTED == "Y" || $hasSavedBarcodes)) ? "style=\"display:table-row\"" : "style=\"display:none\""?>>
			<td></td>
			<td><?echo GetMessage("SALE_F_PHOTO")?></td>
			<td><?echo GetMessage("SALE_F_NAME")?></td>
			<td><?echo GetMessage("SALE_F_QUANTITY")?></td>
			<td><?echo GetMessage("SALE_F_BALANCE")?></td>
			<td><?echo GetMessage("SALE_F_PROPS")?></td>
			<td><?echo GetMessage("SALE_F_STORE")?></td>
			<td><?echo GetMessage("SALE_F_STORE_AMOUNT")?></td>
			<td><?echo GetMessage("SALE_F_STORE_BARCODE")?></td>
			<td><?echo GetMessage("SALE_F_PRICE")?></td>
			<td><?echo GetMessage("SALE_F_SUMMA")?></td>
		</tr>
		<tr id="heading_without_stores" class="heading" <?=($useStores && ($str_DEDUCTED == "Y" || $hasSavedBarcodes)) ? "style=\"display:none\"" : "style=\"display:table-row\""?>>
			<td></td>
			<td><?echo GetMessage("SALE_F_PHOTO")?></td>
			<td><?echo GetMessage("SALE_F_NAME")?></td>
			<td><?echo GetMessage("SALE_F_QUANTITY")?></td>
			<td><?echo GetMessage("SALE_F_BALANCE")?></td>
			<td><?echo GetMessage("SALE_F_PROPS")?></td>
			<td><?echo GetMessage("SALE_F_PRICE")?></td>
			<td><?echo GetMessage("SALE_F_SUMMA")?></td>
		</tr>
		<tr></tr>
	<?
	foreach($arBasketItem as $val)
	{
		$productImg = "";
		$arProductImg = array();
		if (CModule::IncludeModule('iblock'))
		{
			$arProductImg["PREVIEW_PICTURE"] = $arCatResult[$val["PRODUCT_ID"]]["PREVIEW_PICTURE"];
			$arProductImg["DETAIL_PICTURE"] = $arCatResult[$val["PRODUCT_ID"]]["DETAIL_PICTURE"];

			$arParent = CCatalogSku::GetProductInfo($val["PRODUCT_ID"]);
			if ($arParent)
			{
				if (empty($arProductImg["PREVIEW_PICTURE"]))
					$arProductImg["PREVIEW_PICTURE"] = $arCatResult[$arParent["ID"]]["PREVIEW_PICTURE"];

				if (empty($arProductImg["DETAIL_PICTURE"]))
					$arProductImg["DETAIL_PICTURE"] = $arCatResult[$arParent["ID"]]["DETAIL_PICTURE"];
			}

			if($arProductImg["PREVIEW_PICTURE"] != "")
				$productImg = $arProductImg["PREVIEW_PICTURE"];
			elseif($arProductImg["DETAIL_PICTURE"] != "")
				$productImg = $arProductImg["DETAIL_PICTURE"];
		}

		if ($productImg != "")
		{
			$arFile = CFile::GetFileArray($productImg);
			$productImg = CFile::ResizeImageGet($arFile, array('width'=>80, 'height'=>80), BX_RESIZE_IMAGE_PROPORTIONAL, false, false);
			$val["PICTURE"] = $productImg;
		}

		$propsProd = "";
		$countProp = 0;
		if (is_array($val["PROPS"]))
		{
			foreach($val["PROPS"] as $valProd)
			{
				$countProp++;
				$propsProd .= "<input type=\"hidden\" name=\"PRODUCT[".$val["ID"]."][PROPS][".$countProp."][NAME]\" id=\"PRODUCT_PROPS_NAME_".$val["ID"]."_".$countProp."\" value=\"".$valProd["NAME"]."\" />";
				$propsProd .= "<input type=\"hidden\" name=\"PRODUCT[".$val["ID"]."][PROPS][".$countProp."][VALUE]\" id=\"PRODUCT_PROPS_VALUE_".$val["ID"]."_".$countProp."\" value=\"".$valProd["VALUE"]."\" />";
				$propsProd .= "<input type=\"hidden\" name=\"PRODUCT[".$val["ID"]."][PROPS][".$countProp."][CODE]\" id=\"PRODUCT_PROPS_CODE_".$val["ID"]."_".$countProp."\" value=\"".$valProd["CODE"]."\" />";
				$propsProd .= "<input type=\"hidden\" name=\"PRODUCT[".$val["ID"]."][PROPS][".$countProp."][SORT]\" id=\"PRODUCT_PROPS_SORT_".$val["ID"]."_".$countProp."\" value=\"".$valProd["SORT"]."\" />";
			}
		}
		$val["QUANTITY"] = $QUANTITY_FACTORIAL == 'Y' ? FloatVal($val["QUANTITY"]) : IntVal($val["QUANTITY"]);

		$productCountAll += $val["QUANTITY"];
		$productWeight += ($val["WEIGHT"] * $val["QUANTITY"]);
		$ORDER_TOTAL_PRICE += ($val["PRICE"] + $val["DISCOUNT_PRICE"]) * $val["QUANTITY"];
		$ORDER_PRICE_WITH_DISCOUNT += $val["PRICE"] * $val["QUANTITY"];

		$arFilterRecomendet[] = $val["PRODUCT_ID"];
	?>
		<tr id="BASKET_TABLE_ROW_<?=$val["ID"]?>" onmouseover="fMouseOver(this);" onmouseout="fMouseOut(this);">
			<td class="action">
				<?
				$arActions = array();
				$arActions[] = array("ICON"=>"view", "TEXT"=>GetMessage("SOE_JS_EDIT"), "ACTION"=>"ShowProductEdit(".$val["ID"].");", "DEFAULT"=>true);
				$arActions[] = array("ICON"=>"delete", "TEXT"=>GetMessage("SOE_JS_DEL"), "ACTION"=>"DeleteProduct(this, ".$val["ID"].");fEnableSub();");
				?>
				<div class="adm-list-table-popup" onClick="this.blur();BX.adminList.ShowMenu(this, <?=CUtil::PhpToJsObject($arActions)?>);"></div>
			</td>
			<td class="photo">
				<?if (is_array($val["PICTURE"])):?>
					<img src="<?=$val["PICTURE"]["src"]?>" alt="" width="80" border="0" />
				<?else:?>
					<div class="no_foto"><?=GetMessage('NO_FOTO');?></div>
				<?endif?>
			</td>
			<td class="order_name">
				<div id="product_name_<?=$val["ID"]?>">
					<?if (strlen($val["EDIT_PAGE_URL"]) > 0):?>
						<a href="<?echo $val["EDIT_PAGE_URL"]?>" target="_blank">
					<?endif?>
						<?=trim($val["NAME"])?>
					<?if (strlen($val["EDIT_PAGE_URL"]) > 0):?>
						</a>
					<?endif?>
				</div>

				<?if (!isset($val["NEW_PRODUCT"])):?>
					<input type="hidden" name="PRODUCT[<?=$val["ID"]?>][ID]"                 id="BUSKET_<?=$val["ID"]?>" value="<?=$val["ID"]?>" />
					<input type="hidden" name="PRODUCT[<?=$val["ID"]?>][BUSKET_ID]"          id="BUSKET_<?=$val["ID"]?>" value="<?=$val["ID"]?>" />
				<?else:?>
					<input type="hidden" name="PRODUCT[<?=$val["ID"]?>][NEW_PRODUCT]"        id="PRODUCT[<?=$val["ID"]?>][NEW_PRODUCT]" value="NEW_PRODUCT" />
				<?endif;?>

				<input type="hidden" name="PRODUCT[<?=$val["ID"]?>][CURRENCY]"               id="CURRENCY_<?=$val["ID"]?>" value="<?=$val["CURRENCY"]?>" />
				<input type="hidden" name="PRODUCT[<?=$val["ID"]?>][CALLBACK_FUNC]"          id="CALLBACK_FUNC_<?=$val["ID"]?>" value="<?=$val["CALLBACK_FUNC"]?>" />
				<input type="hidden" name="PRODUCT[<?=$val["ID"]?>][ORDER_CALLBACK_FUNC]"    id="ORDER_CALLBACK_FUNC_<?=$val["ID"]?>" value="<?=$val["ORDER_CALLBACK_FUNC"]?>" />
				<input type="hidden" name="PRODUCT[<?=$val["ID"]?>][CANCEL_CALLBACK_FUNC]"   id="CANCEL_CALLBACK_FUNC_<?=$val["ID"]?>" value="<?=$val["CANCEL_CALLBACK_FUNC"]?>" />
				<input type="hidden" name="PRODUCT[<?=$val["ID"]?>][PAY_CALLBACK_FUNC]"      id="PAY_CALLBACK_FUNC_<?=$val["ID"]?>" value="<?=$val["PAY_CALLBACK_FUNC"]?>" />
				<input type="hidden" name="PRODUCT[<?=$val["ID"]?>][PRODUCT_PROVIDER_CLASS]" id="PRODUCT_PROVIDER_CLASS_<?=$val["ID"]?>" value="<?=$val["PRODUCT_PROVIDER_CLASS"]?>" >
				<input type="hidden" name="PRODUCT[<?=$val["ID"]?>][DISCOUNT_PRICE]"         id="PRODUCT[<?=$val["ID"]?>][DISCOUNT_PRICE]" value="<?=$val["DISCOUNT_PRICE"]?>" >
				<input type="hidden" name="PRODUCT[<?=$val["ID"]?>][VAT_RATE]"               id="PRODUCT[<?=$val["ID"]?>][VAT_RATE]" value="<?=$val["VAT_RATE"]?>" >
				<input type="hidden" name="PRODUCT[<?=$val["ID"]?>][WEIGHT]"                 id="PRODUCT[<?=$val["ID"]?>][WEIGHT]" value="<?=$val["WEIGHT"]?>" />
				<input type="hidden" name="PRODUCT[<?=$val["ID"]?>][MODULE]"                 id="PRODUCT[<?=$val["ID"]?>][MODULE]" value="<?=$val["MODULE"]?>" />
				<input type="hidden" name="PRODUCT[<?=$val["ID"]?>][NOTES]"                  id="PRODUCT[<?=$val["ID"]?>][NOTES]" value="<?=$val["NOTES"]?>" />
				<input type="hidden" name="PRODUCT[<?=$val["ID"]?>][CATALOG_XML_ID]"         id="PRODUCT[<?=$val["ID"]?>][CATALOG_XML_ID]" value="<?=$val["CATALOG_XML_ID"]?>" >
				<input type="hidden" name="PRODUCT[<?=$val["ID"]?>][PRODUCT_XML_ID]"         id="PRODUCT[<?=$val["ID"]?>][PRODUCT_XML_ID]" value="<?=$val["PRODUCT_XML_ID"]?>" />
				<input type="hidden" name="PRODUCT[<?=$val["ID"]?>][DETAIL_PAGE_URL]"        id="PRODUCT[<?=$val["ID"]?>][DETAIL_PAGE_URL]" value="<?=$val["DETAIL_PAGE_URL"]?>" />
				<input type="hidden" name="PRODUCT[<?=$val["ID"]?>][NAME]"                   id="PRODUCT[<?=$val["ID"]?>][NAME]" value="<?=$val["NAME"]?>" />
				<input type="hidden" name="PRODUCT[<?=$val["ID"]?>][PRICE_DEFAULT]"          id="PRODUCT[<?=$val["ID"]?>][PRICE_DEFAULT]" value="<?=$val["PRICE"]+$val["DISCOUNT_PRICE"]?>" />
				<input type="hidden" name="PRODUCT[<?=$val["ID"]?>][PRODUCT_ID]"             id="PRODUCT[<?=$val["ID"]?>][PRODUCT_ID]" value="<?=$val["PRODUCT_ID"]?>" />
				<input type="hidden" name="PRODUCT[<?=$val["ID"]?>][BARCODE_MULTI]"          id="PRODUCT[<?=$val["ID"]?>][BARCODE_MULTI]" value="<?=($val["BARCODE_MULTI"] == Y) ? "Y" : "N"?>" />
				<input type="hidden" name="PRODUCT[<?=$val["ID"]?>][HAS_SAVED_QUANTITY]"     id="PRODUCT[<?=$val["ID"]?>][HAS_SAVED_QUANTITY]" value="<?=($val["HAS_SAVED_QUANTITY"] == Y) ? "Y" : "N"?>" />
				<input type="hidden" name="PRODUCT[<?=$val["ID"]?>][CUSTOM_PRICE]"           id="PRODUCT[<?=$val["ID"]?>][CUSTOM_PRICE]" value="<?=($val["CUSTOM_PRICE"] == Y) ? "Y" : "N"?>" />

				<input type="hidden" name="edit_page_url_<?=$val["ID"]?>"                    id="edit_page_url_<?=$val["ID"]?>" value="<?=$val["EDIT_PAGE_URL"]?>" />

				<span id="product_props_<?=$val["ID"]?>"><?=$propsProd?></span>
				<script language="JavaScript">
					arProduct[<?=$val["ID"]?>] = '<?=$val["PRODUCT_ID"]?>';
					arProductEditCountProps[<?=$val["ID"]?>] = <?=$countProp?>;
					countProduct = countProduct + 1;
				</script>
			</td>
			<td id="DIV_QUANTITY_<?=$val["ID"]?>" class="order_count">
				<div>
					<input maxlength="7" onChange="fRecalProduct(<?=$val["ID"]?>, '', 'N', 'N');" type="text" name="PRODUCT[<?=$val["ID"]?>][QUANTITY]" id="PRODUCT[<?=$val["ID"]?>][QUANTITY]" value="<?=$val["QUANTITY"]?>" size="4" >
				</div>
				<span class="warning_balance" id="warning_balance_<?=$val["ID"]?>"></span>
			</td>
			<td class="balance_count">
				<?
				$balance = "0";
				if ($val["MODULE"] == "catalog" && CModule::IncludeModule('catalog'))
				{
					$ar_res = CCatalogProduct::GetByID($val["PRODUCT_ID"]);
					$balance = FloatVal($ar_res["QUANTITY"]);
				}
				?>
				<div id="DIV_BALANCE_<?=$val["ID"]?>"><?=$balance?></div>
			</td>
			<td class="props">
				<div id="PRODUCT_PROPS_USER_<?=$val["ID"]?>">
				<?
				if (is_array($val["PROPS"]))
				{
					foreach($val["PROPS"] as $vv)
					{
						if(strlen($vv["VALUE"]) > 0)
							echo $vv["NAME"].": ".$vv["VALUE"]."<br />";
					}
				}
				?>
				</div>
			</td>

			<!-- store selector -->

			<td id="td_store_block_<?=$val["ID"]?>" class="store" <?=($useStores && ($str_DEDUCTED == "Y" || $hasSavedBarcodes)) ? "" : "style=\"display:none\""?>>
				<div id="store_block_<?=$val["ID"]?>">
					<div id="store_select_block_<?=$val["ID"]?>">
						<?
						$hasValidStores = true;
						if (is_array($arBasketItem[$val["ID"]]["STORES"]) && count($arBasketItem[$val["ID"]]["STORES"]) > 0) //is too strong?
						{
							foreach ($arBasketItem[$val["ID"]]["STORES"] as $storeId => $arStore)
							{
								if (!isset($arStore["STORE_ID"]) || intVal($arStore["STORE_ID"]) < 0 || !isset($arStore["AMOUNT"]) || intVal($arStore["AMOUNT"]) < 0)
								{
									$hasValidStores	= false;
									break;
								}
							}
						}
						else
						{
							$hasValidStores	= false;
						}

						if ($hasValidStores)
						{
							if ($arBasketItem[$val["ID"]]["HAS_SAVED_QUANTITY"] == "Y")
							{
								$ind = 0;
								foreach ($arBasketItem[$val["ID"]]["STORES"] as $storeId => $arStore)
								{
									if (isset($arStore["QUANTITY"]))
									{
										?>
										<div id="store_select_wrapper_<?=$val["ID"]?>_<?=$storeId?>" class="store_select_wrapper <?=($ind>0) ? "store_row_element" : ""?>">

											<div id="store_select_delete_<?=$val["ID"]?>_<?=$storeId?>" class="store_select_delete <?=($ind>0) ? "store_row_element" : ""?>"></div>

											<select id="PRODUCT[<?=$val["ID"]?>][STORES][<?=$storeId?>][STORE_ID]" name="PRODUCT[<?=$val["ID"]?>][STORES][<?=$storeId?>][STORE_ID]" class="store_first_row_element" onChange="fChangeStoreSelector(this, <?=$val["ID"]?>, <?=$storeId?>, <?=CUtil::PhpToJSObject($arBasketItem[$val["ID"]]["STORES"])?>)" class="<?=($ind>0) ? "store_row_element" : ""?>">
											<? foreach($arBasketItem[$val["ID"]]["STORES"] as $storeId2 => $arStore2)
											{
											?>
												<option value="<?=$arStore2["STORE_ID"]?>" <? if ($arStore["STORE_ID"] == $arStore2["STORE_ID"]) echo "selected=\"selected\"" ?>>
												<?=htmlspecialcharsbx($arStore2["STORE_NAME"])." [".$arStore2["STORE_ID"]."]"?>
												</option>
											<?
											}
											?>
											</select>
											<input name="PRODUCT[<?=$val["ID"]?>][STORES][<?=$storeId?>][STORE_NAME]" id="PRODUCT[<?=$val["ID"]?>][STORES][<?=$storeId?>][STORE_NAME]" type="hidden" value="<?=$arStore["STORE_NAME"]?>">
											<input name="PRODUCT[<?=$val["ID"]?>][STORES][<?=$storeId?>][AMOUNT]" id="PRODUCT[<?=$val["ID"]?>][STORES][<?=$storeId?>][AMOUNT]" type="hidden" value="<?=$arStore["AMOUNT"]?>">
										</div>
										<script type="text/javascript"> //store selector change
											BX.bind(BX('PRODUCT[' + <?=$val["ID"]?> + '][STORES][' + <?=$storeId?> + '][STORE_ID]'), 'change', function() {
													return fChangeStoreSelector(this, '<?=$val["ID"]?>', '<?=$ind?>', '<?=CUtil::JSEscape(CUtil::PHPToJsObject($arBasketItem[$val["ID"]]["STORES"]))?>');
												}
											);
										</script>
										<?
										if ($ind > 0)
										{
										?>
										<script type="text/javascript"> //store delete button
											BX.bind(BX('store_select_wrapper_<?=$val["ID"]?>_<?=$storeId?>'), 'mouseover', function() {
													BX.addClass(BX('store_select_delete_<?=$val["ID"]?>_<?=$storeId?>'), "store_select_delete_button");
												}
											);
											BX.bind(BX('store_select_wrapper_<?=$val["ID"]?>_<?=$storeId?>'), 'mouseout', function() {
													BX.removeClass(BX('store_select_delete_<?=$val["ID"]?>_<?=$storeId?>'), "store_select_delete_button");
												}
											);
											BX.bind(BX('store_select_delete_<?=$val["ID"]?>_<?=$storeId?>'), 'click', function() {
													return fDeleteStore('<?=$val["ID"]?>', '<?=$val["ID"]?>_<?=$storeId?>', '<?=count($arBasketItem[$val["ID"]]["STORES"])?>');
												}
											);
										</script>
										<?
										}
									$ind++;
									} //quantity is set
									// $ind++;
								}
								if (count($arBasketItem[$val["ID"]]["STORES"]) > 1)
								{
									?>
									<a id="add_store_link_<?=$val["ID"]?>" <?=(count($arBasketItem[$val["ID"]]["STORES"]) > $ind) ? "" : "style=\"display:none\"" ?> class="add_store" href="javascript:void(0);" onclick="fAddStore(<?=$val["ID"]?>, <?=CUtil::PhpToJSObject($arBasketItem[$val["ID"]]["STORES"])?>, <?=count($arBasketItem[$val["ID"]]["STORES"]) - 1?>, <?=($val["BARCODE_MULTI"] == "Y")? "true" : "false"?>);"><span></span><?=GetMessage("SALE_F_ADD_STORE")?></a>
									<?
								}
							}
							else
							{
							?>
								<div id="store_select_wrapper_<?=$val["ID"]?>_0" class="store_select_wrapper">
									<div id="store_select_delete_<?=$val["ID"]?>_0" class="store_select_delete"></div>
									<select id="PRODUCT[<?=$val["ID"]?>][STORES][0][STORE_ID]" name="PRODUCT[<?=$val["ID"]?>][STORES][0][STORE_ID]" class="store_first_row_element" onChange="fChangeStoreSelector(this, <?=$val["ID"]?>, 0, <?=CUtil::PhpToJSObject($arBasketItem[$val["ID"]]["STORES"])?>)">
										<?
										if (is_array($arBasketItem[$val["ID"]]["STORES"]))
										{
											foreach($arBasketItem[$val["ID"]]["STORES"] as $storeId => $arStore)
											{
												?>
												<option value="<?=$arStore["STORE_ID"]?>"><?=htmlspecialcharsbx($arStore["STORE_NAME"])." [".$arStore["STORE_ID"]."]"?></option>
												<?
											}
										}
										?>
									</select>
									<input name="PRODUCT[<?=$val["ID"]?>][STORES][0][STORE_NAME]" id="PRODUCT[<?=$val["ID"]?>][STORES][0][STORE_NAME]" type="hidden" value="<?=$arBasketItem[$val["ID"]]["STORES"][0]["STORE_NAME"]?>">
									<input name="PRODUCT[<?=$val["ID"]?>][STORES][0][AMOUNT]" id="PRODUCT[<?=$val["ID"]?>][STORES][0][AMOUNT]" type="hidden" value="<?=$arBasketItem[$val["ID"]]["STORES"][0]["AMOUNT"]?>">
									<?
									if (count($arBasketItem[$val["ID"]]["STORES"]) > 1)
									{
										?>
										<a id="add_store_link_<?=$val["ID"]?>" class="add_store" href="javascript:void(0);" onclick="fAddStore(<?=$val["ID"]?>, <?=CUtil::PhpToJSObject($arBasketItem[$val["ID"]]["STORES"])?>, <?=count($arBasketItem[$val["ID"]]["STORES"]) - 1?>, <?=($val["BARCODE_MULTI"] == "Y")? "true" : "false"?>);"><span></span><?=GetMessage("SALE_F_ADD_STORE")?></a>
										<?
									}
									?>
								</div>
							<?
							}
						}
						else //no valid stores to show
						{
							?><div class="store_product_no_stores"><?=GetMessage("NEWO_NO_PRODUCT_STORES")?></div><?
						}
						?>
					</div>
				</div>
			</td>

			<!-- quantity on the store -->

			<td class="store_amount" id="store_amount_block_<?=$val["ID"]?>" nowrap <?=($useStores && ($str_DEDUCTED == "Y" || $hasSavedBarcodes)) ? "" : "style=\"display:none\""?>>
				<?
				if ($hasValidStores)
				{
					if ($arBasketItem[$val["ID"]]["HAS_SAVED_QUANTITY"] == "Y")
					{
						$ind = 0;
						foreach ($arBasketItem[$val["ID"]]["STORES"] as $storeId => $arStore)
						{
							if (isset($arStore["QUANTITY"]))
							{
							?>
							<div id="store_amount_wrapper_<?=$val["ID"]?>_<?=$storeId?>" class="<?=($ind>0) ? "store_row_element" : ""?>">
								<input name="PRODUCT[<?=$val["ID"]?>][STORES][<?=$storeId?>][QUANTITY]" id="PRODUCT[<?=$val["ID"]?>][STORES][<?=$storeId?>][QUANTITY]" value="<?=$arStore['QUANTITY']?>" size="4" maxlength="7" type="text" ><span id="store_max_amount_<?=$val["ID"]?>_<?=$storeId?>">&nbsp;/&nbsp;<?=$arBasketItem[$val["ID"]]["STORES"][$storeId]["AMOUNT"]?></span>
							</div>
							<?
							}
							$ind++;
						}
					}
					else
					{
					?>
						<div id="store_amount_wrapper_<?=$val["ID"]?>_0">
							<input name="PRODUCT[<?=$val["ID"]?>][STORES][0][QUANTITY]" id="PRODUCT[<?=$val["ID"]?>][STORES][0][QUANTITY]" value="" size="4" maxlength="7" type="text" ><span id="store_max_amount_<?=$val["ID"]?>_0">&nbsp;/&nbsp;<?=$arBasketItem[$val["ID"]]["STORES"][0]["AMOUNT"]?></span>
						</div>
					<?
					}
				}
				?>
			</td>

			<!-- barcode (button or input field) -->

			<td class="store_barcode" id="store_barcode_block_<?=$val["ID"]?>" <?=($useStores && ($str_DEDUCTED == "Y" || $hasSavedBarcodes)) ? "" : "style=\"display:none\""?>>
				<?
				if ($hasValidStores)
				{
					if ($arBasketItem[$val["ID"]]["HAS_SAVED_QUANTITY"] == "Y")
					{
						$ind = 0;
						foreach ($arBasketItem[$val["ID"]]["STORES"] as $storeId => $arStore)
						{
							if (isset($arStore["QUANTITY"]) && $val["BARCODE_MULTI"] == "Y")
							{
							?>
							<div id="store_barcode_wrapper_<?=$val["ID"]?>_<?=$storeId?>" class="<?=($ind>0) ? "store_row_element" : ""?>">
								<div align="center">
									<a onclick="enterBarcodes(<?=$val["ID"]?>, <?=$storeId?>);" class="adm-btn adm-btn-barcode"><?=GetMessage("NEWO_STORE_ADD_BARCODES")?></a>
								</div>
								<div id="STORE_BARCODE_MULTI_DIV_<?=$val["ID"]?>_<?=$storeId?>" class="store_barcode_hidden_div">
									<div style="display: block;" class="store_barcode_scroll_div" id="STORE_BARCODE_DIV_SCROLL_<?=$val["ID"]?>_<?=$storeId?>">
										<table id="STORE_BARCODE_TABLE_MULTI_<?=$val["ID"]?>_<?=$storeId?>">
											<tbody>
												<?
												foreach ($arBasketItem[$val["ID"]]["STORES"] as $storeId2 => $arStore2)
												{
													if (isset($arStore2["BARCODE"]) && $arStore2["STORE_ID"] == $arStore["STORE_ID"])
													{
														foreach ($arStore2["BARCODE"] as $barcodeId => $barcodeValue)
														{
															?>
															<tr id="STORE_BARCODE_<?=$val["ID"]?>_<?=$storeId2?>_<?=$barcodeId?>">
																<td>
																	<input
																		id="PRODUCT[<?=$val["ID"]?>][STORES][<?=$storeId2?>][BARCODE][<?=$barcodeId?>]"
																		name="PRODUCT[<?=$val["ID"]?>][STORES][<?=$storeId2?>][BARCODE][<?=$barcodeId?>]"
																		type="text"
																		maxlength="40"
																		size="13"
																		value="<?=$barcodeValue?>"
																		class="<?=setBarcodeClass($arStore2["BARCODE_FOUND"][$barcodeId])?>"
																		onChange="fCheckBarcode(<?=$val["ID"]?>, <?=$storeId2?>, true, <?=$barcodeId?>)"
																		>
																</td>
																<td>
																	<input
																		id="PRODUCT[<?=$val["ID"]?>][STORES][<?=$storeId2?>][BARCODE_FOUND][<?=$barcodeId?>]"
																		name="PRODUCT[<?=$val["ID"]?>][STORES][<?=$storeId2?>][BARCODE_FOUND][<?=$barcodeId?>]"
																		type="hidden"
																		value="<?=$arStore2["BARCODE_FOUND"][$barcodeId]?>"
																		>
																</td>
																<td>
																	<a class="split-delete-item" tabindex="<?=$barcodeId?>" href="javascript:void(0);" onclick="deleteBarCodeValue(<?=$val["ID"]?>, <?=$storeId2?>, <?=$barcodeId?>); " title="<?=GetMessage("NEWO_STORE_DELETE_BARCODE")?>"></a>
																</td>
															</tr>
															<?
														}
													}
												}
												?>
											</tbody>
										</table>
									</div>
								</div>
							</div>
							<?
							$ind++;
							}
						}
						if ($val["BARCODE_MULTI"] == "N")
						{
						?>
							<div id="store_barcode_wrapper_<?=$val["ID"]?>_0">
								<input name="PRODUCT[<?=$val["ID"]?>][STORES][0][BARCODE]" id="PRODUCT[<?=$val["ID"]?>][STORES][0][BARCODE]" onChange="fCheckBarcode(<?=$val["ID"]?>, 0)" type="text" />
							</div>
						<?
						}
					}
					else
					{
						if ($val["BARCODE_MULTI"] == "Y")
						{
						?>
							<div id="store_barcode_wrapper_<?=$val["ID"]?>_0">
								<div align="center">
									<a onclick="enterBarcodes(<?=$val["ID"]?>, 0);" class="adm-btn adm-btn-barcode"><?=GetMessage("NEWO_STORE_ADD_BARCODES")?></a>
								</div>
								<div id="STORE_BARCODE_MULTI_DIV_<?=$val["ID"]?>_0" class="store_barcode_hidden_div">
									<div style="display: block;" class="store_barcode_scroll_div" id="STORE_BARCODE_DIV_SCROLL_<?=$val["ID"]?>_0">
										<table id="STORE_BARCODE_TABLE_MULTI_<?=$val["ID"]?>_0">
											<tbody></tbody>
										</table>
									</div>
								</div>
							</div>
						<?
						}
						else
						{
						?>
							<input name="PRODUCT[<?=$val["ID"]?>][STORES][0][BARCODE]" id="PRODUCT[<?=$val["ID"]?>][STORES][0][BARCODE]" onChange="fCheckBarcode(<?=$val["ID"]?>, 0)" type="text" />
						<?
						}
					}
				}
				?>
			</td>
			<!-- end of store data -->

			<td class="order_price" nowrap>
				<?
				$priceBase = ($val["DISCOUNT_PRICE"] + $val["PRICE"]);
				$priceDiscount = 0;
				$discountPercent = "";
				$priceBaseValue = "";

				if ($priceBase > 0 && $val["DISCOUNT_PRICE"] > 0)
					$priceDiscount = roundEx(($val["DISCOUNT_PRICE"] * 100) / $priceBase, SALE_VALUE_PRECISION);
				?>
				<div id="DIV_PRICE_<?=$val["ID"]?>" class="edit_price">
					<span class="default_price_product" id="default_price_<?=$val["ID"]?>"><span class="formated_price" id="formated_price_<?=$val["ID"]?>" onclick="fEditPrice(<?=$val["ID"]?>, 'on');"><?=CurrencyFormatNumber($val["PRICE"], $str_CURRENCY);?></span></span><span class="edit_price_product" id="edit_price_<?=$val["ID"]?>">
						<input maxlength="9" onchange="fRecalProduct('<?=$val["ID"]?>', 'price', 'N', 'N');" onblur="fEditPrice('<?=$val["ID"]?>', 'exit');" type="text" name="PRODUCT[<?=$val["ID"]?>][PRICE]" id="PRODUCT[<?=$val["ID"]?>][PRICE]" value="<?=FloatVal($val["PRICE"])?>" size="5" >
					</span><span id='currency_price_product' class='currency_price'><?=$CURRENCY_FORMAT?></span>
					<a href="javascript:void(0);" onclick="fEditPrice(<?=$val["ID"]?>, 'on');"><span class="pencil"></span></a>
				</div>
				<div id="DIV_PRICE_OLD_<?=$val["ID"]?>" class="base_price" style="display:none;"><?=CurrencyFormatNumber($val["PRICE"] + $val["DISCOUNT_PRICE"], $str_CURRENCY);?> <span><?=$CURRENCY_FORMAT?></span></div>

				<?
				if ($priceDiscount > 0)
				{
					$discountPercent = "(".GetMessage('NEWO_PRICE_DISCOUNT')." ".$priceDiscount."%)";

				}
				if ($priceBase > 0 && $priceBase != $val["PRICE"])
					$priceBaseValue = CurrencyFormatNumber($priceBase, $str_CURRENCY)." <span>".$CURRENCY_FORMAT."</span>";
				?>
					<div class="base_price" id="DIV_BASE_PRICE_WITH_DISCOUNT_<?=$val["ID"]?>"><?=$priceBaseValue;?></div>
					<div class="discount" id="DIV_DISCOUNT_<?=$val["ID"]?>"><?=$discountPercent?></div>

				<div class="base_price_title" id="base_price_title_<?=$val["ID"]?>"><?=$val["NOTES"];?></div>
			</td>
			<td id="DIV_SUMMA_<?=$val["ID"]?>" class="product_summa" nowrap>
				<div><?=CurrencyFormatNumber(($val["QUANTITY"] * $val["PRICE"]), $str_CURRENCY);?> <span><?=$CURRENCY_FORMAT?></span></div>
			</td>
		</tr>
	<?
	}//end foreach $arBasketItem
	if ($ORDER_TOTAL_PRICE == $ORDER_PRICE_WITH_DISCOUNT)
		$ORDER_PRICE_WITH_DISCOUNT = 0;
	?>
	</table>

	<!--store data -->
	<!--end of store data -->
	</td>
</tr>
<tr>
	<td valign="top" align="left" colspan="2">
		<br>
		<div class="set_cupon">
			<?=GetMessage("NEWO_BASKET_CUPON")?>:
			<input type="text" name="CUPON" id="CUPON" value="<?=htmlspecialcharsbx($CUPON)?>" />
			<a href="javascript:void(0)" onClick="fRecalProduct('', '', 'N', 'Y');"><?=GetMessage("NEWO_CUPON_RECALC")?></a><sup style="color:#000;">1)</sup>
			<div><?=GetMessage("NEWO_CUPON_DESC")?></div>
		</div>

		<div style="float:right">
			<script>
				function fMouseOver(el)
				{
					el.className = 'tr_hover';
				}
				function fMouseOut(el)
				{
					el.className = '';
				}
				function fEditPrice(item, type)
				{
					if (type == 'on')
					{
						BX('DIV_PRICE_' + item).className = 'edit_price edit_enable';
						BX('PRODUCT['+item+'][PRICE]').focus();
					}
					if (type == 'exit')
					{
						BX('DIV_PRICE_' + item).className = 'edit_price';
					}
				}
				function AddProductSearch(index)
				{
					var quantity = 1;
					var BUYER_ID = document.form_order_buyers_form.user_id.value;
					var BUYER_CUPONS = document.getElementById("CUPON").value;

					window.open('/bitrix/admin/sale_product_search.php?lang=<?=LANGUAGE_ID?>&LID=<?=CUtil::JSEscape($LID)?>&addDefault=N&func_name=FillProductFields&index=' + index + '&QUANTITY=' + quantity + '&BUYER_ID=' + BUYER_ID + '&BUYER_COUPONS=' + BUYER_CUPONS, '', 'scrollbars=yes,resizable=yes,width=980,height=550,top='+parseInt((screen.height - 500)/2-14)+',left='+parseInt((screen.width - 840)/2-5));
				}
			</script>
			<?
			$productAddBool = COption::GetOptionString('sale', 'SALE_ADMIN_NEW_PRODUCT', 'N');
			?>
			<?if ($productAddBool == "Y"):?>
				<a title="<?=GetMessage("SOE_NEW_ITEMS")?>" onClick="ShowProductEdit('', 'Y');" class="adm-btn adm-btn-green" href="javascript:void(0);"><?=GetMessage("SOE_NEW_ITEMS")?></a>
			<?endif;?>
			<a title="<?=GetMessage("SOE_ADD_ITEMS")?>" onClick="AddProductSearch(1);" class="adm-btn adm-btn-green adm-btn-add" href="javascript:void(0);"><?=GetMessage("SOE_ADD_ITEMS")?></a>
		</div>

<script language="JavaScript">
	var currencyBase = '<?=CSaleLang::GetLangCurrency($LID);?>';
	var orderWeight = '<?=$productWeight?>';
	var orderPrice = '<?=$str_PRICE?>';

	window.onload = function () {
		<?
		if ($bVarsFromForm)
		{
			echo "fRecalProduct('', '', 'N', 'N');";
		}
		?>
	};
	function fEnableSub()
	{
		if (document.getElementById('tbl_sale_order_edit'))
			document.getElementById('tbl_sale_order_edit').style.zIndex  = 10000;
	}
	function pJCFloatDiv()
	{
		var _this = this;
		this.floatDiv = null;
		this.x = this.y = 0;

		this.Show = function(div, left, top)
		{
			var zIndex = parseInt(div.style.zIndex);
			if(zIndex <= 0 || isNaN(zIndex))
				zIndex = 1100;
			div.style.zIndex = zIndex;
			div.style.left = left + "px";
			div.style.top = top + "px";

			if(jsUtils.IsIE())
			{
				var frame = document.getElementById(div.id+"_frame");
				if(!frame)
				{
					frame = document.createElement("IFRAME");
					frame.src = "javascript:''";
					frame.id = div.id+"_frame";
					frame.style.position = 'absolute';
					frame.style.zIndex = zIndex-1;
					document.body.appendChild(frame);
				}
				frame.style.width = div.offsetWidth + "px";
				frame.style.height = div.offsetHeight + "px";
				frame.style.left = div.style.left;
				frame.style.top = div.style.top;
				frame.style.visibility = 'visible';
			}
		}
		this.Close = function(div)
		{
			if(!div)
				return;
			var frame = document.getElementById(div.id+"_frame");
			if(frame)
				frame.style.visibility = 'hidden';
		}
	}
	var pjsFloatDiv = new pJCFloatDiv();

	function SaleBasketEdit()
	{
		var _this = this;
		this.active = null;

		this.PopupShow = function(div, pos)
		{
			this.PopupHide();
			if(!div)
				return;
			if (typeof(pos) != "object")
				pos = {};

			this.active = div.id;
			div.ondrag = jsUtils.False;

			jsUtils.addEvent(document, "keypress", _this.OnKeyPress);

			div.style.width = div.offsetWidth + 'px';
			div.style.visibility = 'visible';

			var res = jsUtils.GetWindowSize();
			pos['top'] = parseInt(res["scrollTop"] + res["innerHeight"]/2 - div.offsetHeight/2);
			pos['left'] = parseInt(res["scrollLeft"] + res["innerWidth"]/2 - div.offsetWidth/2);
			if(pos['top'] < 5)
				pos['top'] = 5;
			if(pos['left'] < 5)
				pos['left'] = 5;

			pjsFloatDiv.Show(div, pos["left"], pos["top"]);
		}

		this.PopupHide = function()
		{
			var div = document.getElementById(_this.active);
			if(div)
			{
				pjsFloatDiv.Close(div);
				div.parentNode.removeChild(div);
			}
			this.active = null;
			jsUtils.removeEvent(document, "keypress", _this.OnKeyPress);
		}

		this.OnKeyPress = function(e)
		{
			if(!e) e = window.event
			if(!e) return;
			if(e.keyCode == 27)
				_this.PopupHide();
		},

		this.IsVisible = function()
		{
			return (document.getElementById(this.active).style.visibility != 'hidden');
		}
	}

	check_ctrl_enter = function(e)
	{
		if(!e)
			e = window.event;

		if((e.keyCode == 13 || e.keyCode == 10) && e.ctrlKey)
		{
			alert('submit!');
		}
	}
	SaleBasketEditTool = new SaleBasketEdit();

	function ShowProductEdit(id, newElement)
	{
		var div = document.createElement("DIV");
		div.id = "product_edit";
		div.style.visible = 'hidden';
		div.style.position = 'absolute';
		div.innerHTML = '<?=$formTemplate?>';

		document.body.appendChild(div);
		SaleBasketEditTool.PopupShow(div);

		if (id != "")
		{
			document.getElementById('FORM_NEWPROD_CODE').style.display = 'none'
			document.getElementById('FORM_BASKET_PRODUCT_ID').value = id;
			document.getElementById('FORM_PROD_BASKET_ID').value = id;
			document.getElementById('FORM_PROD_BASKET_NAME').value = document.getElementById('PRODUCT[' + id + '][NAME]').value;
			document.getElementById('FORM_PROD_BASKET_DETAIL_URL').value = document.getElementById('PRODUCT[' + id + '][DETAIL_PAGE_URL]').value;
			document.getElementById('FORM_PROD_BASKET_NOTES').value = document.getElementById('PRODUCT[' + id + '][NOTES]').value;
			document.getElementById('FORM_BASKET_CATALOG_XML').value = document.getElementById('PRODUCT[' + id + '][CATALOG_XML_ID]').value;
			document.getElementById('FORM_PROD_BASKET_PRODUCT_XML').value = document.getElementById('PRODUCT[' + id + '][PRODUCT_XML_ID]').value;
			document.getElementById('FORM_PROD_BASKET_PRICE').value = document.getElementById('PRODUCT[' + id + '][PRICE]').value;
			document.getElementById('FORM_PROD_BASKET_WEIGHT').value = document.getElementById('PRODUCT[' + id + '][WEIGHT]').value;
			document.getElementById('FORM_PROD_BASKET_QUANTITY').value = document.getElementById('PRODUCT[' + id + '][QUANTITY]').value;
		}
		if (id != "" && arProductEditCountProps[id])
		{
			propCnt = parseInt(arProductEditCountProps[id]);
			for (i=1; i <= propCnt; i++)
			{
				if(document.getElementById("PRODUCT_PROPS_NAME_" + id + "_" + i))
				{
					nameProp = document.getElementById("PRODUCT_PROPS_NAME_" + id + "_" + i).value;
					codeProp = document.getElementById("PRODUCT_PROPS_CODE_" + id + "_" + i).value;
					valueProp = document.getElementById("PRODUCT_PROPS_VALUE_" + id + "_" + i).value;
					sortProp = document.getElementById("PRODUCT_PROPS_SORT_" + id + "_" + i).value;

					BasketAddPropSection(i, nameProp, codeProp, valueProp, sortProp);
				}
			}
		}
		else if (id != "")
			arProductEditCountProps[id] = 0;
	}

	function SaveProduct()
	{
		var error = '';

		if (BX('FORM_PROD_BASKET_ID').value.length > 0 && BX('FORM_NEWPROD_CODE').style.display == "none")
		{
			prod_id = BX('FORM_PROD_BASKET_ID').value;
			prod_id = parseInt(prod_id);
		}
		else
		{
			prod_id = countProduct;
			prod_id += 1;
		}

		if(prod_id.length <= 0 || isNaN(prod_id))
			error += '<?=GetMessage("SOE_NEW_ERR_PROD_ID")?><br />';
		if(document.getElementById('FORM_PROD_BASKET_NAME').value.length <= 0)
			error += '<?=GetMessage("SOE_NEW_ERR_PROD_NAME")?><br />';

		if(error.length > 0)
		{
			BX('basketError').style.display = 'block';
			BX('basketErrorText').innerHTML = error;
		}
		else
		{
			if (!arProductEditCountProps[prod_id])
				arProductEditCountProps[prod_id] = 1;
			propCnt = parseInt(arProductEditCountProps[prod_id]);

			var propsHTML = "";
			var props = "";
			var arProps = "{";
			if(propCnt > 0)
			{
				for (i=1; i <= propCnt; i++)
				{
					if (document.getElementById('FORM_PROD_PROP_' + prod_id + '_NAME_' + i))
					{
						propName = BX.util.htmlspecialchars(document.getElementById('FORM_PROD_PROP_' + prod_id + '_NAME_' + i).value);
						propCode = BX.util.htmlspecialchars(document.getElementById('FORM_PROD_PROP_' + prod_id + '_CODE_' + i).value);
						propValue = BX.util.htmlspecialchars(document.getElementById('FORM_PROD_PROP_' + prod_id + '_VALUE_' + i).value);
						propSort = BX.util.htmlspecialchars(document.getElementById('FORM_PROD_PROP_' + prod_id + '_SORT_' + i).value);

						if (propName != "" && propValue != "")
						{
							//basket visible props
							if(document.getElementById('FORM_PROD_PROP_' + prod_id + '_NAME_' + i).value.length > 0)
							{
								if(propCode != "CATALOG.XML_ID" && propCode != "PRODUCT.XML_ID")
									propsHTML += propName + ': ' + propValue + '<br />';
							}


							if (arProps != "{")
								arProps += ",";
							arProps += "'"+propName+"':'"+propValue+"'";

							props += "<input type=\"hidden\" name=\"PRODUCT[" + prod_id + "][PROPS]["+i+"][NAME]\" id=\"PRODUCT_PROPS_NAME_" + prod_id + "_" + i + "\" value=\"" + propName + "\" />";
							props += "<input type=\"hidden\" name=\"PRODUCT[" + prod_id + "][PROPS]["+i+"][CODE]\" id=\"PRODUCT_PROPS_CODE_" + prod_id + "_" + i + "\" value=\"" + propCode + "\" />";
							props += "<input type=\"hidden\" name=\"PRODUCT[" + prod_id + "][PROPS]["+i+"][VALUE]\" id=\"PRODUCT_PROPS_VALUE_" + prod_id + "_" + i + "\" value=\"" + propValue + "\" />";
							props += "<input type=\"hidden\" name=\"PRODUCT[" + prod_id + "][PROPS]["+i+"][SORT]\" id=\"PRODUCT_PROPS_SORT_" + prod_id + "_" + i + "\" value=\"" + propSort + "\" />";
						}
						else
						{
							arProductEditCountProps[prod_id] = propCnt - 1;
						}
					}
				}
				arProps += "}";

				if (document.getElementById('PRODUCT_PROPS_USER_' + prod_id))
				{
					document.getElementById('PRODUCT_PROPS_USER_' + prod_id).innerHTML = propsHTML;
					document.getElementById('product_props_' + prod_id).innerHTML = props;
				}
			}

			if (document.getElementById('FORM_BASKET_PRODUCT_ID').value != "")
			{
				document.getElementById('PRODUCT[' + prod_id + '][DETAIL_PAGE_URL]').value = document.getElementById('FORM_PROD_BASKET_DETAIL_URL').value;

				if (BX('edit_page_url_'+prod_id) && BX('edit_page_url_'+prod_id).value.length > 0)
				{
					urlEdit = "<a href=\""+BX('edit_page_url_'+prod_id).value+"\" target=\"_blank\">"+BX.util.htmlspecialchars(document.getElementById('FORM_PROD_BASKET_NAME').value)+"</a>";
				}
				else
					urlEdit = BX.util.htmlspecialchars(document.getElementById('FORM_PROD_BASKET_NAME').value);

				document.getElementById('product_name_' + prod_id).innerHTML = urlEdit;
				document.getElementById('PRODUCT[' + prod_id + '][NAME]').value = document.getElementById('FORM_PROD_BASKET_NAME').value;
				document.getElementById('PRODUCT[' + prod_id + '][NOTES]').value = document.getElementById('FORM_PROD_BASKET_NOTES').value;
				document.getElementById('base_price_title_' + prod_id).innerHTML = document.getElementById('FORM_PROD_BASKET_NOTES').value;
				document.getElementById('PRODUCT[' + prod_id + '][CATALOG_XML_ID]').value = document.getElementById('FORM_BASKET_CATALOG_XML').value;
				document.getElementById('PRODUCT[' + prod_id + '][PRODUCT_XML_ID]').value = document.getElementById('FORM_PROD_BASKET_PRODUCT_XML').value;
				document.getElementById('PRODUCT[' + prod_id + '][QUANTITY]').value = document.getElementById('FORM_PROD_BASKET_QUANTITY').value;

				if (document.getElementById('PRODUCT[' + prod_id + '][PRICE]').value != document.getElementById('FORM_PROD_BASKET_PRICE').value)
				{
					document.getElementById('PRODUCT[' + prod_id + '][PRICE]').value = document.getElementById('FORM_PROD_BASKET_PRICE').value;
					document.getElementById('CALLBACK_FUNC_' + prod_id).value = "Y";
				}

				if (document.getElementById('PRODUCT[' + prod_id + '][WEIGHT]').value != document.getElementById('FORM_PROD_BASKET_WEIGHT').value)
				{
					document.getElementById('PRODUCT[' + prod_id + '][WEIGHT]').value = document.getElementById('FORM_PROD_BASKET_WEIGHT').value;
					document.getElementById('CALLBACK_FUNC_' + prod_id).value = "Y";
				}
			}
			else
			{
				var arParamsTmp = [];
				arParamsTmp['id'] = prod_id;
				arParamsTmp['name'] = document.getElementById('FORM_PROD_BASKET_NAME').value;
				arParamsTmp['price'] = document.getElementById('FORM_PROD_BASKET_PRICE').value;
				arParamsTmp['priceFormated'] = document.getElementById('FORM_PROD_BASKET_PRICE').value;
				arParamsTmp['summaFormated'] = 1;
				arParamsTmp['priceType'] = document.getElementById('FORM_PROD_BASKET_NOTES').value;
				arParamsTmp['priceDiscount'] = 0;
				arParamsTmp['priceBase'] = document.getElementById('FORM_PROD_BASKET_PRICE').value;
				arParamsTmp['priceBaseFormat'] = document.getElementById('FORM_PROD_BASKET_PRICE').value;
				arParamsTmp['quantity'] = document.getElementById('FORM_PROD_BASKET_QUANTITY').value;
				arParamsTmp['url'] = document.getElementById('FORM_PROD_BASKET_DETAIL_URL').value;
				arParamsTmp['urlImg'] = '';
				arParamsTmp['vatRate'] = 0;
				arParamsTmp['weight'] = document.getElementById('FORM_PROD_BASKET_WEIGHT').value;
				arParamsTmp['currency'] = '<?=$str_CURRENCY?>';
				arParamsTmp['module'] = '';
				arParamsTmp['urlEdit'] = '';
				arParamsTmp['callback'] = '';
				arParamsTmp['skuProps'] = arProps;
				arParamsTmp['orderCallback'] = '';
				arParamsTmp['cancelCallback'] = '';
				arParamsTmp['payCallback'] = '';
				arParamsTmp['productProviderClass'] = '';
				arParamsTmp['catalogXmlID'] = document.getElementById('FORM_BASKET_CATALOG_XML').value;
				arParamsTmp['productXmlID'] = document.getElementById('FORM_PROD_BASKET_PRODUCT_XML').value;

				FillProductFields('', arParamsTmp, '');

				if (props.length > 0)
					document.getElementById('product_props_' + prod_id).innerHTML = props;
			}
			fRecalProduct('', '', 'N', 'N');
			SaleBasketEditTool.PopupHide();
		}
	}

	function BasketAddPropSection(id, nameProp, codeProp, valueProp, sortProp)
	{
		var error = '';

		if (!nameProp)
			nameProp = "";
		if (!codeProp)
			codeProp = "";
		if (!valueProp)
			valueProp = "";
		if (!sortProp)
			sortProp = "";
		if (!id)
			id = "";

		if (BX('FORM_PROD_BASKET_ID').value.length > 0 && BX('FORM_NEWPROD_CODE').style.display == "none")
		{
			prod_id = BX('FORM_PROD_BASKET_ID').value;
			prod_id = parseInt(prod_id);
		}
		else
		{
			prod_id = countProduct;
			prod_id += 1;
		}

		if(prod_id.length <= 0 || isNaN(prod_id))
			error += '<?=GetMessage("SOE_NEW_ERR_PROD_ID")?><br />';

		if(error.length > 0)
		{
			document.getElementById('basketError').style.display = 'block';
			document.getElementById('basketErrorText').innerHTML = error;
		}
		else
		{
			if (id == '')
			{
				if (!arProductEditCountProps[prod_id])
					arProductEditCountProps[prod_id] = 0;

				countProp = parseInt(arProductEditCountProps[prod_id]);
				countProp = countProp + 1;
				arProductEditCountProps[prod_id] = countProp;
			}
			else
			{
				countProp = id;
			}

			var oTbl = document.getElementById("BASKET_PROP_TABLE");
			if (!oTbl)
				return;
			var oRow = oTbl.insertRow(-1);
			var oCell = oRow.insertCell(-1);
			oCell.innerHTML = '<input type="text" maxlength="250" size="20" name="FORM_PROD_PROP_' + prod_id + '_NAME_' + countProp + '" id="FORM_PROD_PROP_' + prod_id + '_NAME_' + countProp + '" value="'+BX.util.htmlspecialchars(nameProp)+'" />';
			var oCell = oRow.insertCell(-1);
			oCell.innerHTML = '<input type="text" maxlength="250" size="20" name="FORM_PROD_PROP_' + prod_id + '_VALUE_' + countProp + '" id="FORM_PROD_PROP_' + prod_id + '_VALUE_' + countProp + '" value="'+BX.util.htmlspecialchars(valueProp)+'" />';
			var oCell = oRow.insertCell(-1);
			oCell.innerHTML = '<input type="text" maxlength="250" size="3" name="FORM_PROD_PROP_' + prod_id + '_CODE_' + countProp + '" id="FORM_PROD_PROP_' + prod_id + '_CODE_' + countProp + '" value="'+BX.util.htmlspecialchars(codeProp)+'" />';
			var oCell = oRow.insertCell(-1);
			oCell.innerHTML = '<input type="text" maxlength="10" size="2" name="FORM_PROD_PROP_' + prod_id + '_SORT_' + countProp + '" id="FORM_PROD_PROP_' + prod_id + '_SORT_' + countProp + '" value="'+BX.util.htmlspecialchars(sortProp)+'" />';
		}
	}

	function FillProductFields(index, arParams, iblockID)
	{
		BX('CART_FIX').value= 'N';
		countProduct = countProduct + 1;
		var ID = countProduct;
		var bHideStoreInfo;
		if (BX('DEDUCTED') || BX('HAS_SAVED_BARCODES'))
		{
			bHideStoreInfo = (BX('DEDUCTED').checked || BX('HAS_SAVED_BARCODES').value == "Y") ? false : true;
		}
		else
			bHideStoreInfo = true;

		var oTbl = BX("BASKET_TABLE");
		if (!oTbl)
			return;

		// var rows = oTbl.getElementsByTagName('tr');
		var rows = oTbl.rows,
			lastRow = rows[rows.length - 1],
			oRow = document.createElement('tr');

		lastRow.parentNode.insertBefore(oRow, lastRow.nextSibling ); //insert new row after the last row of the table

		oRow.setAttribute('id','BASKET_TABLE_ROW_' + ID);
		oRow.setAttribute('onmouseout','fMouseOut(this);');
		oRow.setAttribute('onmouseover','fMouseOver(this);');

		var oCellAction = oRow.insertCell(-1);
			oCellAction.setAttribute('class', 'action');
		var oCellPhoto = oRow.insertCell(-1);
			oCellPhoto.setAttribute('class','photo');
		var oCellName = oRow.insertCell(-1);
			oCellName.setAttribute('class','order_name');
		var oCellQuantity = oRow.insertCell(-1);
			oCellQuantity.setAttribute('class','order_count');
			oCellQuantity.setAttribute('id','DIV_QUANTITY_' + ID);
		var oCellBalance = oRow.insertCell(-1);
			oCellBalance.setAttribute('class','balance_count');
		var oCellPROPS = oRow.insertCell(-1);
			oCellPROPS.setAttribute('class','props');
		var oCellPrice = oRow.insertCell(-1);
			oCellPrice.setAttribute('class','order_price');
			oCellPrice.setAttribute('align','center');
			oCellPrice.setAttribute('nowrap','nowrap');
		var oCellSumma = oRow.insertCell(-1);
			oCellSumma.setAttribute('id','DIV_SUMMA_' + ID);
			oCellSumma.setAttribute('class','product_summa');
			oCellSumma.setAttribute('nowrap','nowrap');

		//store info cells
		var oCellStore = oRow.insertCell(6); //index = 6 as currently we have 7 cells in the row by default
			oCellStore.setAttribute('class', 'store');
			oCellStore.setAttribute('id', 'td_store_block_' + ID);
			if (bHideStoreInfo)
				oCellStore.setAttribute('style', 'display:none');
		var oCellStoreQuantity = oRow.insertCell(7);
			oCellStoreQuantity.setAttribute('class', 'store_amount');
			oCellStoreQuantity.setAttribute('id', 'store_amount_block_' + ID);
			if (bHideStoreInfo)
				oCellStoreQuantity.setAttribute('style', 'display:none');
		var oCellBarcode = oRow.insertCell(8);
			oCellBarcode.setAttribute('class', 'store_barcode');
			oCellBarcode.setAttribute('id', 'store_barcode_block_' + ID);
			if (bHideStoreInfo)
				oCellBarcode.setAttribute('style', 'display:none');

		for (key in arParams)
		{
			if (key == "id")
			{
				product_id = arParams[key];
			}
			if (key == "name")
			{
				var name = BX.util.htmlspecialchars(arParams[key]);
			}
			else if (key == "price")
			{
				var price = BX.util.htmlspecialchars(arParams[key]);
			}
			else if (key == "priceFormated")
			{
				var priceFormated = BX.util.htmlspecialchars(arParams[key]);
			}
			else if (key == "priceBase")
			{
				var priceBase = BX.util.htmlspecialchars(arParams[key]);
			}
			else if (key == "priceBaseFormat")
			{
				var priceBaseFormat = BX.util.htmlspecialchars(arParams[key]);
			}
			else if (key == "priceType")
			{
				var priceType = BX.util.htmlspecialchars(arParams[key]);
			}
			else if (key == "currency")
			{
				var currency = arParams[key];
			}
			else if (key == "priceDiscount")
			{
				var priceDiscount = BX.util.htmlspecialchars(arParams[key]);
			}
			else if (key == "quantity")
			{
				var quantity = BX.util.htmlspecialchars(arParams[key]);
			}
			else if (key == "summaFormated")
			{
				var summaFormated = BX.util.htmlspecialchars(arParams[key]);
			}
			else if (key == "weight")
			{
				var weight = BX.util.htmlspecialchars(arParams[key]);
			}
			else if (key == "vatRate")
			{
				var vatRate = arParams[key];
			}
			else if (key == "module")
			{
				var module = arParams[key];
			}
			else if (key == "valutaFormat")
			{
				var valutaFormat = arParams[key];
			}
			else if (key == "catalogXmlID")
			{
				var catalogXmlID = BX.util.htmlspecialchars(arParams[key]);
			}
			else if (key == "productXmlID")
			{
				var productXmlID = BX.util.htmlspecialchars(arParams[key]);
			}
			else if (key == "url")
			{
				var url = BX.util.htmlspecialchars(arParams[key]);
			}
			else if (key == "urlImg")
			{
				var urlImg = arParams[key];
			}
			else if (key == "urlEdit")
			{
				var urlEdit = arParams[key];
			}
			else if (key == "balance")
			{
				var balance = arParams[key];
			}
			else if (key == "priceTotalFormated")
			{
				var priceTotalFormated = arParams[key];
			}
			else if (key == "discountPercent")
			{
				var discountPercent = arParams[key];
			}
			else if (key == "callback")
			{
				var callback = arParams[key];
			}
			else if (key == "orderCallback")
			{
				var orderCallback = arParams[key];
			}
			else if (key == "cancelCallback")
			{
				var cancelCallback = arParams[key];
			}
			else if (key == "payCallback")
			{
				var payCallback = arParams[key];
			}
			else if (key == "productProviderClass")
			{
				var productProviderClass = arParams[key];
			}
			else if (key == "skuProps")
			{
				var skuProps = arParams[key];
				var arSkuProps = eval( '('+skuProps+')' );
			}
			else if (key == "barcodeMulti")
			{
				var barcodeMulti = arParams[key];
			}
			else if (key == "stores")
			{
				var stores = arParams[key];
				var arStores = eval( '('+stores+')' );
			}
		}

		var productProps = "<div id=\"PRODUCT_PROPS_USER_" + ID + "\">";
		var countProps = 1;
		var inputProps = "";
		for(var i in arSkuProps)
		{
			productProps += i+": "+arSkuProps[i]+"<br>";
			inputProps += "<input type=\"hidden\" value=\""+i+"\" name=\"PRODUCT["+ID+"][PROPS]["+countProps+"][NAME]\" id=\"PRODUCT_PROPS_NAME_"+ID+"_"+countProps+"\" >";
			inputProps += "<input type=\"hidden\" value=\""+arSkuProps[i]+"\" name=\"PRODUCT["+ID+"][PROPS]["+countProps+"][VALUE]\" id=\"PRODUCT_PROPS_VALUE_"+ID+"_"+countProps+"\" >";
			inputProps += "<input type=\"hidden\" value=\"\" name=\"PRODUCT["+ID+"][PROPS]["+countProps+"][CODE]\" id=\"PRODUCT_PROPS_CODE_"+ID+"_"+countProps+"\" >";
			inputProps += "<input type=\"hidden\" value=\""+countProps+"\" name=\"PRODUCT["+ID+"][PROPS]["+countProps+"][SORT]\" id=\"PRODUCT_PROPS_SORT_"+ID+"_"+countProps+"\" >";
			countProps++;
		}
		productProps += "</div>";
		arProductEditCountProps[ID] = countProps;
		oCellPROPS.innerHTML = productProps;

		var hiddenField = "<div id=\"product_name_" + ID + "\">";

		if (urlEdit.length > 0)
			hiddenField = hiddenField + "<a href=\""+urlEdit+"\" target=\"_blank\">";
		hiddenField = hiddenField + name;
		if (urlEdit.length > 0)
			hiddenField = hiddenField + "</a>";
		hiddenField = hiddenField + "</div>";

		hiddenField = hiddenField + "<input type=\"hidden\" name=\"PRODUCT[" + ID + "][NEW_PRODUCT]\" id=\"PRODUCT[" + ID + "][NEW_PRODUCT]\" value=\"NEW_PRODUCT\" />\n";
		hiddenField = hiddenField + "<input type=\"hidden\" name=\"PRODUCT[" + ID + "][CALLBACK_FUNC]\" id=\"CALLBACK_FUNC_" + ID + "\" value=\"" + callback + "\" />\n";
		hiddenField = hiddenField + "<input type=\"hidden\" name=\"PRODUCT[" + ID + "][ORDER_CALLBACK_FUNC]\" id=\"ORDER_CALLBACK_FUNC_" + ID + "\" value=\"" + orderCallback + "\" />\n";
		hiddenField = hiddenField + "<input type=\"hidden\" name=\"PRODUCT[" + ID + "][CANCEL_CALLBACK_FUNC]\" id=\"CANCEL_CALLBACK_FUNC_" + ID + "\" value=\"" + cancelCallback + "\" />\n";
		hiddenField = hiddenField + "<input type=\"hidden\" name=\"PRODUCT[" + ID + "][PAY_CALLBACK_FUNC]\" id=\"PAY_CALLBACK_FUNC_" + ID + "\" value=\"" + payCallback + "\" />\n";
		hiddenField = hiddenField + "<input type=\"hidden\" name=\"PRODUCT[" + ID + "][PRODUCT_PROVIDER_CLASS]\" id=\"PRODUCT_PROVIDER_CLASS_" + ID + "\" value=\"" + productProviderClass + "\" />\n";
		hiddenField = hiddenField + "<input type=\"hidden\" name=\"PRODUCT[" + ID + "][CURRENCY]\" id=\"CURRENCY_" + ID + "\" value=\"" + currency + "\" />";
		hiddenField = hiddenField + "<input type=\"hidden\" name=\"PRODUCT[" + ID + "][DISCOUNT_PRICE]\" id=\"PRODUCT[" + ID + "][DISCOUNT_PRICE]\" value=\"" + priceDiscount + "\" />";
		hiddenField = hiddenField + "<input type=\"hidden\" name=\"PRODUCT[" + ID + "][WEIGHT]\" id=\"PRODUCT[" + ID + "][WEIGHT]\" value=\"" + weight + "\" />";
		hiddenField = hiddenField + "<input type=\"hidden\" name=\"PRODUCT[" + ID + "][VAT_RATE]\" id=\"PRODUCT[" + ID + "][VAT_RATE]\" value=\"" + vatRate + "\" />";
		hiddenField = hiddenField + "<input type=\"hidden\" name=\"PRODUCT[" + ID + "][MODULE]\" id=\"PRODUCT[" + ID + "][MODULE]\" value=\"" + module + "\" />";
		hiddenField = hiddenField + "<input type=\"hidden\" name=\"BUSKET_" +  ID + "\" id=\"BUSKET_" + ID + "\" value=\"\" />";
		hiddenField = hiddenField + "<input type=\"hidden\" name=\"PRODUCT[" + ID + "][NOTES]\" id=\"PRODUCT[" + ID + "][NOTES]\" value=\"" + priceType + "\" />";
		hiddenField = hiddenField + "<input type=\"hidden\" name=\"PRODUCT[" + ID + "][CATALOG_XML_ID]\" id=\"PRODUCT[" + ID + "][CATALOG_XML_ID]\" value=\"" + catalogXmlID + "\" />";
		hiddenField = hiddenField + "<input type=\"hidden\" name=\"PRODUCT[" + ID + "][PRODUCT_XML_ID]\" id=\"PRODUCT[" + ID + "][PRODUCT_XML_ID]\" value=\"" + productXmlID + "\" />";
		hiddenField = hiddenField + "<input type=\"hidden\" name=\"PRODUCT[" + ID + "][DETAIL_PAGE_URL]\" id=\"PRODUCT[" + ID + "][DETAIL_PAGE_URL]\" value=\"" + url + "\" />";
		hiddenField = hiddenField + "<input type=\"hidden\" name=\"PRODUCT[" + ID + "][NAME]\" id=\"PRODUCT[" + ID + "][NAME]\" value=\"" + name + "\" />";
		hiddenField = hiddenField + "<input type=\"hidden\" name=\"PRODUCT[" + ID + "][PRICE_DEFAULT]\" id=\"PRODUCT[" + ID + "][PRICE_DEFAULT]\" value=\"" + priceBase + "\" />";
		hiddenField = hiddenField + "<input type=\"hidden\" name=\"PRODUCT[" + ID + "][PRODUCT_ID]\" id=\"PRODUCT[" + ID + "][PRODUCT_ID]\" value=\"" + product_id + "\" />";
		hiddenField = hiddenField + "<input type=\"hidden\" name=\"PRODUCT[" + ID + "][BARCODE_MULTI]\" id=\"PRODUCT[" + ID + "][BARCODE_MULTI]\" value=\"" + barcodeMulti + "\" />";
		hiddenField = hiddenField + "<input type=\"hidden\" name=\"PRODUCT[" + ID + "][CUSTOM_PRICE]\" id=\"PRODUCT[" + ID + "][CUSTOM_PRICE]\" value=\"N\" />";
		hiddenField = hiddenField + "<input type=\"hidden\" name=\"edit_page_url_"+ID+"\" id=\"edit_page_url_"+ID+"\" value=\"" + urlEdit + "\" />";
		hiddenField = hiddenField + "<span id=\"product_props_" + ID + "\">"+inputProps+"</span>";

		var imgSrc = "&nbsp;";
		if (urlImg != "")
			imgSrc = "<img src=\""+urlImg+"\" alt=\"\" width=\"80\" border=\"0\" />";
		else
			imgSrc = "<div class='no_foto'><?=GetMessage('NO_FOTO');?></div>";

		var actonHtml = "<div onclick=\"this.blur();BX.adminList.ShowMenu(this, ";
		actonHtml = actonHtml + "[{'ICON':'view','TEXT':'<?=GetMessage("SOE_JS_EDIT")?>','ACTION':'ShowProductEdit("+ID+");','DEFAULT':true},{'ICON':'delete','TEXT':'<?=GetMessage("SOE_JS_DEL")?>','ACTION':'DeleteProduct(this, "+ID+");fEnableSub();'}]);\" class=\"adm-list-table-popup\"></div>";

		oCellAction.innerHTML = actonHtml;
		oCellPhoto.innerHTML = imgSrc;
		oCellName.innerHTML = hiddenField;
		oCellQuantity.innerHTML = "<div><input maxlength=\"7\" onChange=\"fRecalProduct(" + ID + ", '', 'N', 'N');\" type=\"text\" name=\"PRODUCT[" + ID + "][QUANTITY]\" id=\"PRODUCT[" + ID + "][QUANTITY]\" value=\"" + quantity + "\" size=\"4\"></div><span class=\"warning_balance\" id=\"warning_balance_"+ID+"\"></span>";

		var priceColumn = "";
		if (!valutaFormat) valutaFormat = '<?=$CURRENCY_FORMAT?>';

		priceColumn += "<div id=\"DIV_PRICE_"+ID+"\" class=\"edit_price\">";
		priceColumn += "<span class=\"default_price_product\" id=\"default_price_"+ID+"\">";
		priceColumn += "<span class=\"formated_price\" id=\"formated_price_"+ID+"\" onclick=\"fEditPrice("+ID+", 'on');\">" + priceFormated + "</span>";
		priceColumn += "</span>";
		priceColumn += "<span class=\"edit_price_product\" id=\"edit_price_"+ID+"\">";
		priceColumn += "<input maxlength=\"9\" onblur=\"fEditPrice('" + ID + "', 'exit');\" onclick=\"fEditPrice('" + ID + "', 'on');\" onchange=\"fRecalProduct('" + ID + "', 'price', 'N', 'N');\" type=\"text\" name=\"PRODUCT[" + ID + "][PRICE]\" id=\"PRODUCT[" + ID + "][PRICE]\" value=\"" + price + "\" size=\"5\" >";
		priceColumn += "</span>";
		priceColumn += "<span id='currency_price_product' class='currency_price'>"+valutaFormat+"</span>";
		priceColumn += "<a href=\"javascript:void(0);\" onclick=\"fEditPrice(" + ID + ", 'on');\"><span class=\"pencil\"></span></a>";
		priceColumn += "</div>";
		priceColumn += "<div id=\"DIV_PRICE_OLD_"+ID+"\" class=\"base_price\" style=\"display:none;\">" + priceBaseFormat + " <span>"+valutaFormat+"</span></div>";

		priceColumn += "<div id=\"DIV_BASE_PRICE_WITH_DISCOUNT_"+ID+"\" class=\"base_price\">";

		if (discountPercent > 0)
			priceColumn += priceBaseFormat + "<span>"+valutaFormat+"</span>";

		priceColumn += "</div>";

		priceColumn += "<div id=\"DIV_DISCOUNT_"+ID+"\" class=\"discount\">";
		if (discountPercent > 0)
			priceColumn += "(<?=getMessage('NEWO_PRICE_DISCOUNT')?> "+discountPercent+"%)";
		priceColumn += "</div>";
		priceColumn += "<div class=\"base_price_title\" id=\"base_price_title_"+ID+"\">"+priceType+"</div>";

		oCellPrice.innerHTML = priceColumn;
		oCellSumma.innerHTML = "<div>" + summaFormated + "<span>"+valutaFormat+"</span></div>";

		if (!balance) balance = 0;
		oCellBalance.innerHTML = "<div id=\"DIV_BALANCE_"+ID+"\">" + balance + "</div>";

		if (arStores instanceof Array) //if store control is actually used and array of stores is supplied
		{
			if (arStores.length == 0)
			{
				var newStoreDivBlock = BX.create('div', {
						props: {
							'id': 'store_select_block_' + ID,
						},
						html: '<div class="store_product_no_stores"><?=GetMessage("NEWO_NO_PRODUCT_STORES")?></div>'
					});

				oCellStore.appendChild(newStoreDivBlock);
			}
			else
			{
				// store input fields
				var newStoreDivBlock = BX.create('div', {
						props: {
							'id': 'store_select_block_' + ID,
						},
						children: [
							newStoreDiv = BX.create('div', {
								props: {
									'id': 'store_select_wrapper_' + ID,
									'className': 'store_row_element store_select_wrapper'
								},
								children: [
									newStoreDeleteDiv = BX.create('div', {
										props: {
											'id': 'store_select_delete_' + ID,
											'name': 'store_select_delete_' + ID,
											'className': 'store_row_element store_select_delete',
										},
									}),
									newStoreSelect = BX.create('select', {
										props: {
											'id': 'PRODUCT[' + ID + '][STORES][0][STORE_ID]',
											'name': 'PRODUCT[' + ID + '][STORES][0][STORE_ID]',
											'className': 'store_row_element',
										},
									}),
									newStoreAmountHidden = BX.create('input', {
										props: {
											'id': 'PRODUCT[' + ID + '][STORES][0][AMOUNT]',
											'name': 'PRODUCT[' + ID + '][STORES][0][AMOUNT]',
											'type': 'hidden',
											'value': arStores[0].AMOUNT
										},
									}),
									newStoreNameHidden = BX.create('input', {
										props: {
											'id': 'PRODUCT[' + ID + '][STORES][0][STORE_NAME]',
											'name': 'PRODUCT[' + ID + '][STORES][0][STORE_NAME]',
											'type': 'hidden',
											'value': arStores[0].STORE_NAME
										},
									})
								]
							}),
							addStoresLink = BX.create('a', {
								props: {
									'id': 'add_store_link_' + ID, //TODO - make hidden if arStore.length == 1
									'className': 'add_store',
									'href': 'javascript:void(0);'
								},
								html: '<span></span><?=GetMessage("SALE_F_ADD_STORE")?>'
							})
						]
					}),
					newAmountDiv = BX.create('div', {
						props: {
							'id' : 'store_amount_block_' + ID,
							'className': 'store_row_element'
						},
						children: [
							newAmountInput = BX.create('input', {
								props: {
									'id': 'PRODUCT[' + ID + '][STORES][0][QUANTITY]',
									'name': 'PRODUCT[' + ID + '][STORES][0][QUANTITY]',
									'type': 'text',
									'size': '4',
									'maxlength': '7',
								}
							}),
							newAmountSpan = BX.create('span', {
								props: {
									'id': 'store_max_amount_' + ID + '_0', //TODO
									'type': 'text',
									'size': '4',
									'maxlength': '7',
								},
								html: '&nbsp;/&nbsp;' + arStores[0].AMOUNT // TODO
							})
						]
					});

					if (barcodeMulti == "Y")
					{
						var barcodeButtonData = '<div align="center"><a onClick="enterBarcodes(' + ID + ', 0);" class="adm-btn adm-btn-barcode"><?=GetMessage("NEWO_STORE_ADD_BARCODES")?></a></div>';
						barcodeButtonData += '<div id="STORE_BARCODE_MULTI_DIV_' + ID + '_0" class="store_barcode_hidden_div">';
						barcodeButtonData += '<div style="display: block;" class="store_barcode_scroll_div" id="STORE_BARCODE_DIV_SCROLL_' + ID +  '_0">';
						barcodeButtonData += '<table id="STORE_BARCODE_TABLE_MULTI_' + ID + '_0"><tbody>';
						barcodeButtonData += '</tbody></table>';
						barcodeButtonData += '</div></div>';

						newBarcodeInputDiv = BX.create('div', {
							props: {
								'id' : 'store_barcode_wrapper_' + ID + '_0',
								'className' : 'store_row_element'
							},
							html: barcodeButtonData
						});

						if (BX('HAS_PRODUCTS_WITH_BARCODE_MULTI'))
							BX('HAS_PRODUCTS_WITH_BARCODE_MULTI').value = "Y";
					}
					else
					{
						newBarcodeInputDiv = BX.create('div', { //TODO
							props: {
								'id' : 'store_barcode_wrapper_' + ID + '_0'
							},
							children: [
								newBarcodeInput = BX.create('input', {
									props: {
										'id': 'PRODUCT[' + ID + '][STORES][0][BARCODE]',
										'name': 'PRODUCT[' + ID + '][STORES][0][BARCODE]',
										'type': 'text',
										'className': 'store_row_element'
									}
								}),
							]
						});
					}

				//adding select values
				for (var i = 0; i < arStores.length; i++) {
					newStoreSelect.options[newStoreSelect.options.length] = new Option(
						arStores[i].STORE_NAME + ' [' + arStores[i].STORE_ID + ']',
						arStores[i].STORE_ID
					);
				};

				oCellStore.appendChild(newStoreDivBlock);
				oCellStoreQuantity.appendChild(newAmountDiv);
				oCellBarcode.appendChild(newBarcodeInputDiv);

				BX.bind(BX('add_store_link_' + ID), 'click', function() {
						return fAddStore(ID, arStores, arStores.length, (barcodeMulti == "Y"));
					}
				);

				//store selector change
				BX.bind(BX('PRODUCT[' + ID + '][STORES][0][STORE_ID]'), 'change', function() {
						return fChangeStoreSelector(this, ID, 0, arStores);
					}
				);

				// barcode check
				BX.bind(BX('PRODUCT[' + ID + '][STORES][0][BARCODE]'), 'change', function() {
						return fCheckBarcode(ID, 0, false);
					}
				);
			}
		}

		//array product in basket
		arProduct[ID] = product_id;

		fRecalProduct(BX("PRODUCT[" + ID + "][QUANTITY]"), ID, 'Y', 'N');
	}

	function DeleteProduct(el, id)
	{
		if (confirm('<?=GetMessage('SALE_CONFIRM_DELETE')?>'))
		{
			BX('CART_FIX').value= 'N';
			var trDel = document.getElementById("BASKET_TABLE_ROW_" + id).sectionRowIndex;
			var oTbl = document.getElementById("BASKET_TABLE");
			oTbl.deleteRow(trDel);
			delete arProduct[id];

			fRecalProduct('', '', 'Y', 'N');

			fGetMoreBusket('');
			fGetMoreViewed('');
		}

		return false;
	}

	function enterBarcodes(basketItemId, storeId)
	{
		var formBarcodes,
			uniqId = basketItemId + '_' + storeId,
			oldQuantity = parseInt(BX("PRODUCT[" + basketItemId + "][STORES][" + storeId + "][QUANTITY]").defaultValue),
			newQuantity = parseInt(BX("PRODUCT[" + basketItemId + "][STORES][" + storeId + "][QUANTITY]").value);

		if (isNaN(oldQuantity))
			oldQuantity = 0;

		if (isNaN(newQuantity))
			newQuantity = 0;

		//current number of saved barcode inputs
		var tableMulti = BX("STORE_BARCODE_TABLE_MULTI_" + uniqId),
			rows = tableMulti.getElementsByTagName('tr'),
			barcodeFieldsNumber = rows.length;

		if (barcodeFieldsNumber < newQuantity) // add barcode rows
		{
			var barcodesToAdd = newQuantity - barcodeFieldsNumber,
				addedCount = 0,
				f = 0;

			while (barcodesToAdd != addedCount)
			{
				if (!BX("PRODUCT[" + basketItemId + "][STORES][" + storeId + "][BARCODE][" + f + "]"))
				{
					addBarcodeRow(basketItemId, storeId, f);
					addedCount++;
				}
				else
				{
					f++;
				}
			}
		}
		else if (barcodeFieldsNumber > newQuantity) // delete barcode rows
		{
			var barcodesToDelete = barcodeFieldsNumber - newQuantity,
				k = 0,
				curBarcode,
				arRowsToDelete = [];

			while (row = tableMulti.rows[k++])
			{
				curBarcode = BX.findChild(row, {'tag':'input', 'type': 'text'}, true);
				if (curBarcode.value.length == 0)
					arRowsToDelete.push(row);

				if (arRowsToDelete.length == barcodesToDelete)
					break;
			}

			for (var i = 0; i < arRowsToDelete.length; i++) //actually deleting
			{
				if (BX(arRowsToDelete[i]))
				{
					var trDel = BX(arRowsToDelete[i]).sectionRowIndex;
					tableMulti.deleteRow(trDel);
				}

			};
		}

		formBarcodes = BX.PopupWindowManager.create("sale-popup-barcodes-" + uniqId, BX("product_name_" + uniqId), {
			offsetTop : 0,
			offsetLeft : 0,
			autoHide : false,
			closeByEsc : true,
			closeIcon : false,
			titleBar : true,
			draggable: {restrict:true},
			titleBar: {content: BX.create("span", {html: '<?=GetMessageJS('NEWO_STORE_FORM_ADD_BARCODES')?>', 'props': {'className': 'store-doc-title'}})},
			content : BX("STORE_BARCODE_DIV_SCROLL_" + uniqId),
			events : {
				onPopupFirstShow : BX.proxy(
					function(popupWindow)
					{
						if (BX("STORE_BARCODE_TABLE_MULTI_" + uniqId))
						{
							//TODO
							var multiTable = BX("STORE_BARCODE_TABLE_MULTI_" + uniqId);
							// var rows = multiTable.getElementsByTagName('tr');

							// for (var i = 0, row; row = multiTable.rows[i]; i++)
							// {
							// 	for (var j = 0, col; col = row.cells[j]; j++)
							// 	{
							// 		// var barcodeValues = BX.findChildren(BX(col.id), {'tag':'input', 'type': 'text'}, false);
							// 		// var barcodeFoundValues = BX.findChildren(BX(col.id), {'tag':'input', 'type': 'hidden'}, false);

							// 	}
							// }
						}
					},
					this
				),
			}
		});
		formBarcodes.setButtons([
			new BX.PopupWindowButton({
				text : "<?=GetMessage('SOE_APPLY')?>",
				className : "",
				events : {
					click : function()
					{
						BX('STORE_BARCODE_MULTI_DIV_' + uniqId).appendChild(BX("STORE_BARCODE_DIV_SCROLL_" + uniqId));
						formBarcodes.close();
					}
				}
			}),
			new BX.PopupWindowButton({
				text : "<?=GetMessage('SALE_CANCEL')?>",
				className : "",
				events : {
					click : function()
					{
						BX('STORE_BARCODE_MULTI_DIV_' + uniqId).appendChild(BX("STORE_BARCODE_DIV_SCROLL_" + uniqId));
						formBarcodes.close();
					}
				}
			})
		]);

		if (BX("sale-popup-barcodes-" + uniqId).getElementsByClassName("popup-window-content")[0].children.length <= 0)
			BX("sale-popup-barcodes-" + uniqId).getElementsByClassName("popup-window-content")[0].appendChild(BX("STORE_BARCODE_DIV_SCROLL_" + uniqId));

		formBarcodes.show();
		if (BX('PRODUCT[" + basketItemId + "][STORES][" + storeId + "][BARCODE][0]'))
			BX('PRODUCT[" + basketItemId + "][STORES][" + storeId + "][BARCODE][0]').focus();
	}

	function addBarcodeRow(basketItemId, storeId, barcodeId)
	{
		tableMulti = BX("STORE_BARCODE_TABLE_MULTI_" + basketItemId + "_" + storeId),
		oRow = tableMulti.insertRow(barcodeId);
		oRow.setAttribute('id', "STORE_BARCODE_" + basketItemId + "_" + storeId + "_" + barcodeId);
		oCell = oRow.insertCell(-1);
		oCell.innerHTML = "<input maxlength=\"40\" type=\"text\" size=\"13\" name=\"PRODUCT[" + basketItemId + "][STORES][" + storeId + "][BARCODE][" + barcodeId + "]\" id=\"PRODUCT[" + basketItemId + "][STORES][" + storeId + "][BARCODE][" + barcodeId + "]\">";

		oCellDel = oRow.insertCell(-1);
		oCellDel.innerHTML = "<a class=\"split-delete-item\"  tabIndex=\"-1\" href=\"javascript:void(0);\" onclick=\"deleteBarCodeValue(" + basketItemId + ", " + storeId + ", " + barcodeId + "); \" title=<?=GetMessage('NEWO_STORE_DELETE_BARCODE')?>></a>";

		oCellHidden = oRow.insertCell(-1);
		oCellHidden.innerHTML = "<input type=\"hidden\" value=\"N\" name=\"PRODUCT[" + basketItemId + "][STORES][" + storeId + "][BARCODE_FOUND][" + barcodeId + "]\" id=\"PRODUCT[" + basketItemId + "][STORES][" + storeId + "][BARCODE_FOUND][" + barcodeId + "]\">";

		// barcode check
		BX.bind(BX("PRODUCT[" + basketItemId + "][STORES][" + storeId + "][BARCODE][" + barcodeId + "]"), 'change', function() {
				return fCheckBarcode(basketItemId, storeId, true, barcodeId);
			}
		);
	}

	function deleteBarCodeValue(basketItemId, storeId, barcodeId)
	{
		BX("PRODUCT[" + basketItemId + "][STORES][" + storeId + "][BARCODE][" + barcodeId + "]").value = '';
		fCheckBarcode(basketItemId, storeId, true, barcodeId);
	}


	function fRecalProduct(id, type, recommendet, recalcAll)
	{
		var location = '';
		var locationZip = '';
		var paySystemId = '';
		var deliveryId = '';
		var buyerTypeId = '';
		var cupon = '';
		var user_id = 0;
		if (BX('user_id'))
			user_id = BX('user_id').value;

		var productData = "{";
		var j = 0;

		if (type != "" && type == "price")
			BX('CALLBACK_FUNC_' + id).value = "Y";

		for(var i in arProduct)
		{
			if (j > 0)
				productData = productData + ",";

			discount = '';
			if (BX('PRODUCT[' + i + '][DISCOUNT_PRICE]'))
				discount = BX('PRODUCT[' + i + '][DISCOUNT_PRICE]').value;

			var taxOrder = '<?=$str_TAX_VALUE?>';

			var pr = BX('PRODUCT[' + i + '][PRICE]').value.replace(',', '.');
			pr = parseFloat(pr)
			prOld = parseFloat(BX('PRODUCT[' + i + '][PRICE_DEFAULT]').value)

			if (isNaN(pr) || pr <= 0)
				BX('PRODUCT[' + i + '][PRICE]').value = BX('PRODUCT[' + i + '][PRICE_DEFAULT]').value;

			var recalcCallback = "";
			var recalcOrder = "N";
			if (BX('BUSKET_' + i) && BX('BUSKET_' + i).value.length <= 0 || recalcAll == "Y")
			{
				recalcCallback = BX('CALLBACK_FUNC_' + i).value;
				recalcOrder = "Y";
				BX('RECALC_ORDER').value = recalcOrder;
			}

			if (BX('CALLBACK_FUNC_' + i).value == "Y")
			{
				recalcCallback = 'Y';
				BX('PRODUCT[' + i + '][CUSTOM_PRICE]').value = "Y";
			}

			productData = productData + "'" + i + "':{";

			if (BX('BUSKET_' + i))
				productData = productData + "'BUSKET_ID':'" + BX('BUSKET_' + i).value + "',";

			productData = productData + "'CALLBACK_FUNC':'" + recalcCallback + "',";
			productData = productData + "'ORDER_CALLBACK_FUNC':'" + BX('ORDER_CALLBACK_FUNC_' + i).value + "',\n\
				'CANCEL_CALLBACK_FUNC':'" + BX('CANCEL_CALLBACK_FUNC_' + i).value + "',\n\
				'PAY_CALLBACK_FUNC':'" + BX('PAY_CALLBACK_FUNC_' + i).value + "',\n\
				'PRODUCT_PROVIDER_CLASS':'" + BX('PRODUCT_PROVIDER_CLASS_' + i).value + "',\n\
				'QUANTITY':'" + BX('PRODUCT[' + i + '][QUANTITY]').value + "',\n\
				'PRODUCT_ID':'" + BX('PRODUCT[' + i + '][PRODUCT_ID]').value + "',\n\
				'CURRENCY':'" + BX('CURRENCY_' + i).value + "',\n\
				'PRICE':'" + BX('PRODUCT[' + i + '][PRICE]').value + "',\n\
				'PRICE_DEFAULT':'" + BX('PRODUCT[' + i + '][PRICE_DEFAULT]').value + "',\n\
				'WEIGHT':'" + BX('PRODUCT[' + i + '][WEIGHT]').value + "',\n\
				'MODULE':'" + BX('PRODUCT[' + i + '][MODULE]').value + "',\n\
				'VAT_RATE':'" + BX('PRODUCT[' + i + '][VAT_RATE]').value + "',\n\
				'TAX_VALUE':'" + taxOrder + "',\n\
				'DISCOUNT_PRICE':'" + discount + "'}";
			j++;
		}
		productData = productData + "}";

		if (BX('CITY_ORDER_PROP_' + locationID))
		{
			var selectedIndex = BX('CITY_ORDER_PROP_' + locationID).selectedIndex;
			var selectedOption = BX('CITY_ORDER_PROP_' + locationID).options;
		}
		else if (BX('ORDER_PROP_' + locationID))
		{
			var selectedIndex = BX('ORDER_PROP_' + locationID).selectedIndex;
			var selectedOption = BX('ORDER_PROP_' + locationID).options;
		}

		if (locationID > 0 && selectedIndex > 0)
			location = selectedOption[selectedIndex].value;

		if (BX('ORDER_PROP_' + locationZipID))
			locationZip = BX('ORDER_PROP_' + locationZipID).value;

		deliveryId = document.getElementById('DELIVERY_ID').value;
		deliveryPrice = parseFloat(document.getElementById('DELIVERY_ID_PRICE').value);
		if(isNaN(deliveryPrice))
			deliveryPrice = 0;

		paySystemId = document.getElementById('PAY_SYSTEM_ID').value;
		buyerTypeId = document.getElementById('buyer_type_id').value;
		cupon = document.getElementById('CUPON').value;

		var deliveryPriceChange = document.getElementById("change_delivery_price").value;
		var recomMore = document.getElementById('recom_more').value;

		var cartFix = BX('CART_FIX').value;

		dateURL = '<?=bitrix_sessid_get()?>&ORDER_AJAX=Y&id=<?=$ID?>&LID=<?=CUtil::JSEscape($LID)?>&recalcOrder='+recalcOrder+'&cartFix='+cartFix+'&recomMore='+recomMore+'&recommendet='+recommendet+'&delpricechange='+deliveryPriceChange+'&user_id=' + user_id + '&cupon=' + cupon + '&currency=' + currencyBase + '&deliveryId=' + deliveryId + '&paySystemId=' + paySystemId + '&deliveryPrice=' + deliveryPrice + '&buyerTypeId=' + buyerTypeId + '&locationID=' + locationID + '&location=' + location + '&locationZipID=' + locationZipID + '&locationZip=' + locationZip + '&product=' + productData;

		BX.showWait();
		BX.ajax.post('/bitrix/admin/sale_order_new.php', dateURL, fRecalProductResult);
	}

	function fRecalProductResult(result)
	{
		BX.closeWait();
		if (result.length > 0)
		{
			var res = eval( '('+result+')' );

			var changePriceProduct = "N";
			for(var i in res)
			{
				if (i > 0)
				{
					BX('PRODUCT[' + i + '][PRICE]').value = res[i]["PRICE"];
					BX('formated_price_' + i).innerHTML = res[i]["PRICE_DISPLAY"];
					if (res[i]["NOTES"].length > 0)
						BX('base_price_title_' + i).innerHTML = res[i]["NOTES"];

					if (res[i]["DISCOUNT_REPCENT"] > 0)
					{
						BX('DIV_DISCOUNT_' + i).innerHTML = '(<?=GetMessage('NEWO_PRICE_DISCOUNT')?> '+res[i]["DISCOUNT_REPCENT"]+'%)';
						BX('DIV_BASE_PRICE_WITH_DISCOUNT_' + i).innerHTML = res[i]["PRICE_BASE"]+" <span>"+res[0]["CURRENCY_FORMAT"]+"</span>";
					}
					else
					{
						prOld = parseFloat(BX('PRODUCT[' + i + '][PRICE_DEFAULT]').value);

						if (res[i]["PRICE"] == prOld)
						{
							if (BX('DIV_BASE_PRICE_WITH_DISCOUNT_' + i))
								BX('DIV_BASE_PRICE_WITH_DISCOUNT_' + i).innerHTML = '';
						}
						else
						{
							changePriceProduct = "Y";
							BX.show(BX('DIV_PRICE_OLD_'+i));
							if(BX('DIV_BASE_PRICE_WITH_DISCOUNT_'+i))
								BX.hide(BX('DIV_BASE_PRICE_WITH_DISCOUNT_'+i));
						}

						if (BX('DIV_DISCOUNT_' + i))
							BX('DIV_DISCOUNT_' + i).innerHTML = '';
					}

					BX('DIV_SUMMA_' + i).innerHTML = "<div>" + res[i]["SUMMA_DISPLAY"] + " <span>"+res[0]["CURRENCY_FORMAT"]+"</span></div>";

					BX('PRODUCT[' + i + '][QUANTITY]').value = res[i]["QUANTITY"];

					BX('warning_balance_' + i).innerHTML = '';
					if (res[i]["WARNING_BALANCE"] && res[i]["WARNING_BALANCE"] == "Y")
					{
						BX('warning_balance_' + i).innerHTML = '<?=GetMEssage("NEWO_WARNING_BALANCE")?>';
					}


					BX('DIV_BALANCE_' + i).value = res[i]["BALANCE"];
					BX('currency_price_product').innerHTML = res[0]["CURRENCY_FORMAT"];
					BX('PRODUCT[' + i + '][DISCOUNT_PRICE]').value = res[i]["DISCOUNT_PRICE"];
					BX('CURRENCY_' + i).value = res[i]["CURRENCY"];
				}
			}

			BX('DELIVER_ID_DESC').innerHTML = res[0]["DELIVERY_DESCRIPTION"];
			BX('DELIVERY_ID_PRICE').value = res[0]["DELIVERY_PRICE"];
			if (res[0]["DELIVERY"].length > 0)
				BX('DELIVERY_SELECT').innerHTML = res[0]["DELIVERY"];

			if (res[0]["ORDER_ERROR"] == "N")
			{
				if (BX('town_location_'+res[0]["LOCATION_TOWN_ID"]))
				{
					if (res[0]["LOCATION_TOWN_ENABLE"] == 'Y')
						BX('town_location_'+res[0]["LOCATION_TOWN_ID"]).style.display = 'table-row';
					else
						BX('town_location_'+res[0]["LOCATION_TOWN_ID"]).style.display = 'none';
				}

				BX('ORDER_TOTAL_PRICE').innerHTML = res[0]["PRICE_TOTAL"];

				if (res[0]["DISCOUNT_PRODUCT_VALUE"] > 0)
				{
					BX('ORDER_PRICE_WITH_DISCOUNT_DESC_VISIBLE').style.display = 'table-row';
					BX('ORDER_PRICE_WITH_DISCOUNT').innerHTML = res[0]["PRICE_WITH_DISCOUNT_FORMAT"];
				}
				else
				{
					if (changePriceProduct == 'N')
						BX('ORDER_PRICE_WITH_DISCOUNT_DESC_VISIBLE').style.display = 'none';
					else
					{
						BX('ORDER_PRICE_WITH_DISCOUNT_DESC_VISIBLE').style.display = 'table-row';
						BX('ORDER_PRICE_WITH_DISCOUNT').innerHTML = res[0]["PRICE_WITH_DISCOUNT_FORMAT"];
					}
				}

				if (parseInt(res[0]["ORDER_ID"]) > 0)
				{
					if (parseFloat(res[0]["PAY_ACCOUNT_DEFAULT"]) >= parseFloat(res[0]["PRICE_TO_PAY_DEFAULT"]))
					{
						BX('PAY_CURRENT_ACCOUNT_DESC').innerHTML = res[0]["PAY_ACCOUNT"];
						BX('buyerCanBuy').style.display = 'block';
					}
					else
					{
						if (BX('buyerCanBuy'))
							BX('buyerCanBuy').style.display = 'none';
					}
				}

				BX('ORDER_DELIVERY_PRICE').innerHTML = res[0]["DELIVERY_PRICE_FORMAT"];
				BX('ORDER_TAX_PRICE').innerHTML = res[0]["PRICE_TAX"];
				BX('ORDER_WAIGHT').innerHTML = res[0]["PRICE_WEIGHT_FORMAT"];
				BX('ORDER_PRICE_ALL').innerHTML = res[0]["PRICE_TO_PAY"];
				BX('ORDER_DISCOUNT_PRICE_VALUE_VALUE').innerHTML = res[0]["DISCOUNT_VALUE_FORMATED"];

				if (parseFloat(res[0]["DISCOUNT_VALUE"]) > 0)
					BX('ORDER_DISCOUNT_PRICE_VALUE').style.display = "table-row";

				if (res[0]["RECOMMENDET_CALC"] == "Y")
				{
					if (res[0]["RECOMMENDET_PRODUCT"].length == 0)
					{
						BX('tab_1').style.display = "none";
						BX('user_recomendet').style.display = "none";

						if (BX('user_basket').style.display == "block")
							fTabsSelect('user_basket', 'tab_2');
						else if (BX('buyer_viewed').style.display == "block")
							fTabsSelect('buyer_viewed', 'tab_3');
						else if (BX('tab_2').style.display == "block")
							fTabsSelect('user_basket', 'tab_2');
						else if (BX('tab_3').style.display == "block")
							fTabsSelect('buyer_viewed', 'tab_3');
					}
					else
					{
						BX('user_recomendet').innerHTML = res[0]["RECOMMENDET_PRODUCT"];
						if (BX('user_basket').style.display != "block" && BX('buyer_viewed').style.display != "block")
							fTabsSelect('user_recomendet', 'tab_1');
						else
							BX('tab_1').style.display = "block";
					}
				}

				orderWeight = res[0]["PRICE_WEIGHT"];
				orderPrice = res[0]["PRICE_WITH_DISCOUNT"];

				fGetMoreBusket('');
				fGetMoreViewed('');
			}
		}
	}

	/*
	* click on recommended More
	*/
	function fGetMoreRecom()
	{
		BX('recom_more').value = "Y";
		fRecalProduct('', '', 'Y', 'N');
	}

	/*
	* click on basket more
	*/
	function fGetMoreBusket(showAll)
	{
		recalcViewed = showAll;

		if (showAll == "Y")
			BX('recom_more_busket').value = "Y";

		showAll = BX('recom_more_busket').value;
		var userId = BX('user_id').value;
		var productData = "{";
		for(var i in arProduct)
			productData = productData + "'"+i+"':'"+arProduct[i]+"',";
		productData = productData + "}";

		BX.ajax.post('/bitrix/admin/sale_order_new.php', '<?=bitrix_sessid_get()?>&ORDER_AJAX=Y&showAll='+showAll+'&arProduct='+productData+'&getmorebasket=Y&CURRENCY=<?=$str_CURRENCY?>&LID=<?=CUtil::JSEscape($LID)?>&userId=' + userId, fGetMoreBusketResult);
	}
	function fGetMoreBusketResult(res)
	{
		if (res.length > 0)
			BX('user_basket').innerHTML = res;
		else
		{
			BX('tab_2').style.display = "none";
			BX('user_basket').style.display = "none";

			if (BX('tab_1').style.display == "block")
				fTabsSelect('user_recomendet', 'tab_1');
			else if (BX('tab_3').style.display == "block")
				fTabsSelect('buyer_viewed', 'tab_3');
		}

		if (recalcViewed != "R")
			fGetMoreViewed('R');
	}

	/*
	* click on basket more
	*/
	function fGetMoreViewed(showAll) {
		recalcBasket = showAll;

		if (showAll == "Y")
			BX('recom_more_viewed').value = "Y";

		showAll = BX('recom_more_viewed').value;
		var userId = BX('user_id').value;
		var productData = "{";
		for(var i in arProduct)
			productData = productData + "'"+i+"':'"+arProduct[i]+"',";
		productData = productData + "}";

		BX.ajax.post('/bitrix/admin/sale_order_new.php', '<?=bitrix_sessid_get()?>&ORDER_AJAX=Y&showAll='+showAll+'&arProduct='+productData+'&getmoreviewed=Y&CURRENCY=<?=$str_CURRENCY?>&LID=<?=CUtil::JSEscape($LID)?>&userId=' + userId, fGetMoreViewedResult);
	}

	function fGetMoreViewedResult(res)
	{
		if (res.length > 0)
			BX('buyer_viewed').innerHTML = res;
		else
		{
			BX('tab_3').style.display = "none";
			BX('buyer_viewed').style.display = "none";

			if (BX('tab_1').style.display == "block")
				fTabsSelect('user_recomendet', 'tab_1');
			else if (BX('tab_2').style.display == "block")
				fTabsSelect('user_basket', 'tab_2');
		}

		if (recalcBasket != "R")
			fGetMoreBusket('R');
	}

	/*
	* add to order from recommended & basket
	*/
	function fAddToBusketMoreProduct(type, params)
	{
		FillProductFields(0, params, 0);

		if (type == 'busket')
			fGetMoreBusket('');
		if (type == 'viewed')
			fGetMoreViewed('');

		return false;
	}

	function fAddStore(id, arStores, maxSelectNumber, isMultiBarcode)
	{
		//TODO - when adding - iterate over all existing selectors from the first to the maxSelectNumber
		//the first found will be the new index

		var storeSelectors = BX.findChildren(BX('store_select_block_' + id), {'tag':'div', 'className': 'store_select_wrapper'}, false),
			uniqId = id + '_' + storeSelectors.length,
			newStoreId = storeSelectors.length,
			countStoreSelectors = storeSelectors.length + 1,
			newStoreDiv = BX.create('div', {
				props: {
					'id': 'store_select_wrapper_' + uniqId,
					'className': 'store_row_element store_select_wrapper'
				},
				children: [
					newStoreDeleteDiv = BX.create('div', {
						props: {
							'id': 'store_select_delete_' + uniqId,
							'name': 'store_select_delete_' + uniqId,
							'className': 'store_row_element store_select_delete',
						},
					}),
					newStoreSelect = BX.create('select', {
						props: {
							'id': 'PRODUCT[' + id + '][STORES][' + newStoreId + '][STORE_ID]',
							'name': 'PRODUCT[' + id + '][STORES][' + newStoreId + '][STORE_ID]',
							'className': 'store_row_element',
						},
					}),
					newStoreAmountHidden = BX.create('input', {
						props: {
							'id': 'PRODUCT[' + id + '][STORES][' + newStoreId + '][AMOUNT]',
							'name': 'PRODUCT[' + id + '][STORES][' + newStoreId + '][AMOUNT]',
							'type': 'hidden',
							'value': arStores[0].AMOUNT //TODO
						},
					}),
					newStoreNameHidden = BX.create('input', {
						props: {
							'id': 'PRODUCT[' + id + '][STORES][' + newStoreId + '][STORE_NAME]',
							'name': 'PRODUCT[' + id + '][STORES][' + newStoreId + '][STORE_NAME]',
							'type': 'hidden',
							'value': arStores[0].STORE_NAME //TODO
						},
					})
				]
			}),
			newAmountDiv = BX.create('div', {
				props: {
					'id' : 'store_amount_wrapper_' + uniqId,
					'className': 'store_row_element'
				},
				children: [
					newAmountInput = BX.create('input', {
						props: {
							'id': 'PRODUCT[' + id + '][STORES][' + newStoreId + '][QUANTITY]',
							'name': 'PRODUCT[' + id + '][STORES][' + newStoreId + '][QUANTITY]',
							'type': 'text',
							'size': '4',
							'maxlength': '7',
						}
					}),
					newAmountSpan = BX.create('span', {
						props: {
							'id': 'store_max_amount_' + uniqId,
							'type': 'text',
							'size': '4',
							'maxlength': '7',
						},
						html: '&nbsp;/&nbsp;' + arStores[0].AMOUNT
					})
				]
			});

		if (isMultiBarcode)
		{
			//change to DOM later
			var barcodeButtonData = '<div align="center"><a onClick="enterBarcodes(' + id + ', ' + newStoreId + ');" class="adm-btn adm-btn-barcode"><?=GetMessage("NEWO_STORE_ADD_BARCODES")?></a></div>';
			barcodeButtonData += '<div id="STORE_BARCODE_MULTI_DIV_' + id + '_' + newStoreId + '" class="store_barcode_hidden_div">';
			barcodeButtonData += '<div style="display: block;" class="store_barcode_scroll_div" id="STORE_BARCODE_DIV_SCROLL_' + id +  '_' + newStoreId + '">';
			barcodeButtonData += '<table id="STORE_BARCODE_TABLE_MULTI_' + id + '_' + newStoreId + '"><tbody>';
			barcodeButtonData += '</tbody></table></div></div>';

			newBarcodeInputDiv = BX.create('div', {
				props: {
					'id' : 'store_barcode_wrapper_' + uniqId,
					'className' : 'store_row_element'
				},
				html: barcodeButtonData
			});

			BX('store_barcode_block_' + id).appendChild(newBarcodeInputDiv);
		}

		//adding select values
		for (var i = 0; i < arStores.length; i++) {
			newStoreSelect.options[newStoreSelect.options.length] = new Option(
				arStores[i].STORE_NAME + ' [' + arStores[i].STORE_ID + ']',
				arStores[i].STORE_ID
			);
		};

		BX('store_select_block_' + id).appendChild(newStoreDiv);
		BX('store_amount_block_' + id).appendChild(newAmountDiv);
		// BX('store_barcode_found_block_' + id).appendChild(newBarcodeFoundCheckbox);

		//store selector change
		BX.bind(BX('PRODUCT[' + id + '][STORES][' + newStoreId + '][STORE_ID]'), 'change', function() {
				return fChangeStoreSelector(this, id, storeSelectors.length, arStores);
			}
		);

		//store delete button
		BX.bind(BX('store_select_wrapper_' + uniqId), 'mouseover', function() {
				BX.addClass(BX('store_select_delete_' + uniqId), "store_select_delete_button");
			}
		);
		BX.bind(BX('store_select_wrapper_' + uniqId), 'mouseout', function() {
				BX.removeClass(BX('store_select_delete_' + uniqId), "store_select_delete_button");
			}
		);
		BX.bind(BX('store_select_delete_' + uniqId), 'click', function() {
				return fDeleteStore(id, uniqId, maxSelectNumber);
			}
		);

		// barcode check
		BX.bind(BX('PRODUCT[' + id + '][STORES][' + newStoreId + '][BARCODE]'), 'change', function() {
				return fCheckBarcode(id, storeSelectors.length, false);
			}
		);

		if (countStoreSelectors >= maxSelectNumber)
			BX('add_store_link_' + id).style.display = "none";
	}

	function fDeleteStore(id, uniqId, maxSelectNumber)
	{
		var isMultiBarcode = (BX("PRODUCT[" + id + "][BARCODE_MULTI]").value == "Y") ? true : false;

		BX.remove(BX('store_select_wrapper_' + uniqId));
		BX.remove(BX('store_amount_wrapper_' + uniqId));

		if (isMultiBarcode) // only product with multi barcode has more than 1 barcode control (button or input field) which should be deleted
			BX.remove(BX('store_barcode_wrapper_' + uniqId));

		var storeSelectors = BX.findChildren(BX('store_select_block_' + id), {'tag':'div', 'className': 'store_select_wrapper'}, false),
			countStoreSelectors = storeSelectors.length + 1;

		//show again link 'Add store'
		if (countStoreSelectors >= maxSelectNumber)
			BX('add_store_link_' + id).style.display = "inline";
	}

	function fChangeStoreSelector(el, basketItemId, selectorIndex, arStores)
	{
		var storeIndex = el.options[el.selectedIndex].value.split("_").pop();

		for (var i = 0; i < arStores.length; i++)
		{
			if (arStores[i].STORE_ID == storeIndex)
			{
				BX('store_max_amount_' + basketItemId + '_' + selectorIndex).innerHTML = '&nbsp;/&nbsp;' + arStores[i].AMOUNT;
			}
		};

		BX('PRODUCT[' + basketItemId + '][STORES][' + selectorIndex + '][AMOUNT]').value = arStores[el.selectedIndex].AMOUNT;
		BX('PRODUCT[' + basketItemId + '][STORES][' + selectorIndex + '][STORE_NAME]').value = arStores[el.selectedIndex].STORE_NAME;

		//TODO
		// var barcodeSelector = BX('PRODUCT[' + basketItemId + '][STORES][' + selectorIndex + '][BARCODE]');
	}

	function fCheckBarcode(basketItemId, storeId, isMultiBarcode, barcodeId)
	{
		var isNewProduct;
		if (BX("PRODUCT[" + basketItemId + "][NEW_PRODUCT]"))
			isNewProduct = true;
		else
			isNewProduct = false;

		if (isNewProduct)
		{
			var productId = BX("PRODUCT[" + basketItemId + "][PRODUCT_ID]").value,
				productProvider = BX("PRODUCT_PROVIDER_CLASS_" + basketItemId).value,
				moduleName = BX("PRODUCT[" + basketItemId + "][MODULE]").value,
				barcodeMulti = BX("PRODUCT[" + basketItemId + "][BARCODE_MULTI]").value;
		}

		if (isMultiBarcode)
		{
			var barcode = BX('PRODUCT[' + basketItemId + '][STORES][' + storeId + '][BARCODE][' + barcodeId + ']'),
				barcodeFound = BX('PRODUCT[' + basketItemId + '][STORES][' + storeId + '][BARCODE_FOUND][' + barcodeId + ']');
		}
		else
		{
			var barcode = BX('PRODUCT[' + basketItemId + '][STORES][' + storeId + '][BARCODE]'),
				barcodeFound = '';
		}

		var realStoreId = BX('PRODUCT[' + basketItemId + '][STORES][' + storeId + '][STORE_ID]').value;

		if (barcode.value.length == 0)
		{
			BX.removeClass(barcode, 'store_barcode_not_found');
			BX.removeClass(barcode, 'store_barcode_found_input');
			barcodeFound.value = "N";
		}
		else
		{
			if (isNewProduct)
			{
				BX.showWait();
				BX.ajax.post(
					'/bitrix/admin/sale_order_new.php',
					'<?=bitrix_sessid_get()?>&ORDER_AJAX=Y&id=<?=$ID?>&LID=<?=CUtil::JSEscape($LID)?>&checkBarcode=Y&productId=' + productId + '&barcode=' + barcode.value + '&storeId=' + realStoreId + '&productProvider=' + productProvider + '&moduleName=' + moduleName + '&barcodeMulti=' + barcodeMulti,
					function (res)
					{
						var result = eval( '('+res+')' );

						BX.closeWait();
						if (result["status"] == "ok")
						{
							BX.removeClass(barcode, 'store_barcode_not_found');
							BX.addClass(barcode, 'store_barcode_found_input');
							barcodeFound.value = "Y";
						}
						else
						{
							if (barcode.value != '')
							{
								BX.removeClass(barcode, 'store_barcode_found_input');
								BX.addClass(barcode, 'store_barcode_not_found');
								barcodeFound.value = "N";
							}
							else
							{
								BX.removeClass(barcode, 'store_barcode_not_found');
								BX.removeClass(barcode, 'store_barcode_found_input');
								barcodeFound.value = "N";
							}
						}
					}
				);
			}
			else
			{
				BX.showWait();
				BX.ajax.post(
					'/bitrix/admin/sale_order_new.php',
					'<?=bitrix_sessid_get()?>&ORDER_AJAX=Y&id=<?=$ID?>&LID=<?=CUtil::JSEscape($LID)?>&checkBarcode=Y&basketItemId=' + basketItemId + '&barcode=' + barcode.value + '&storeId=' + realStoreId,
					function (res)
					{
						var result = eval( '('+res+')' );

						BX.closeWait();
						if (result["status"] == "ok")
						{
							BX.removeClass(barcode, 'store_barcode_not_found');
							BX.addClass(barcode, 'store_barcode_found_input');
							barcodeFound.value = "Y";
						}
						else
						{
							if (barcode.value != '')
							{
								BX.removeClass(barcode, 'store_barcode_found_input');
								BX.addClass(barcode, 'store_barcode_not_found');
								barcodeFound.value = "N";
							}
							else
							{
								BX.removeClass(barcode, 'store_barcode_not_found');
								BX.removeClass(barcode, 'store_barcode_found_input');
								barcodeFound.value = "N";
							}
						}
					}
				);
			}
		}
	}

	function fShowReasonTextarea(showArea)
	{
		if (!showArea)
		{
			BX('reason_undo_deducted_area').style.display = 'table-row';
		}
		else
		{
			BX('reason_undo_deducted_area').style.display = 'none';
		}
	}

	function toggleStoresView(el, useStores)
	{
		var checkboxValue = el.checked;

		el.value = (el.value == "Y") ? "N" : "Y"; //toggle value

		var hasMultipleBarcodes = (BX('HAS_PRODUCTS_WITH_BARCODE_MULTI').value == "Y") ? true : false;

		!fShowReasonTextarea(checkboxValue);

		if (useStores)
		{
			if (checkboxValue)
			{
				BX('heading_with_stores').style.display = 'table-row';
				BX('heading_without_stores').style.display = 'none';

				var store_items = [];
				store_items = store_items.concat(BX.findChildren(BX('BASKET_TABLE'), {className:'store'}, true));
				store_items = store_items.concat(BX.findChildren(BX('BASKET_TABLE'), {className:'store_amount'}, true));
				store_items = store_items.concat(BX.findChildren(BX('BASKET_TABLE'), {className:'store_barcode'}, true));

				if (store_items)
				{
					for(var i=0; i<store_items.length; i++){
						store_items[i].style.display = 'table-cell';
					}
				}
			}
			else
			{
				BX('heading_without_stores').style.display = 'table-row';
				BX('heading_with_stores').style.display = 'none';

				var store_items = [];
				store_items = store_items.concat(BX.findChildren(BX('BASKET_TABLE'), {className:'store'}, true));
				store_items = store_items.concat(BX.findChildren(BX('BASKET_TABLE'), {className:'store_amount'}, true));
				store_items = store_items.concat(BX.findChildren(BX('BASKET_TABLE'), {className:'store_barcode'}, true));

				if (store_items)
				{
					for (var i=0; i<store_items.length; i++)
					{
						store_items[i].style.display = 'none';
					}
				}
			}
		}
	}
</script>
	</td>
</tr>
<tr>
	<td colspan="2"><br>
		<input type="hidden" name="recom_more" id="recom_more" value="N" >
		<input type="hidden" name="recom_more_busket" id="recom_more_busket" value="N" >
		<input type="hidden" name="recom_more_viewed" id="recom_more_viewed" value="N" >
		<table width="100%" class="order_summary">
			<tr>
				<td valign="top" id="itog_tabs" class="load_product">
					<table width="100%" class="itog_header"><tr><td><?=GetMessage('NEWO_SUBTAB_RECOM_REQUEST');?></td></tr></table>
					<br>
					<div id="tabs">
						<?
						$displayNone = "block";
						$displayNoneBasket = "block";
						$displayNoneViewed = "block";

						$arRecomendet = CSaleProduct::GetRecommendetProduct($str_USER_ID, $LID, $arFilterRecomendet);
						$arRecomendetResult = fDeleteDoubleProduct($arRecomendet, $arFilterRecomendet, 'N');
						if (count($arRecomendetResult["ITEMS"]) <= 0)
							$displayNone = "none";

						$arShoppingCart = CSaleBasket::DoGetUserShoppingCart($LID, $str_USER_ID, $FUSER_ID, $arErrors, $arCupon);
						$arShoppingCart = fDeleteDoubleProduct($arShoppingCart, $arFilterRecomendet, 'N');
						if (count($arShoppingCart["ITEMS"]) <= 0)
							$displayNoneBasket = "none";

						$arViewed = array();
						$dbViewsList = CSaleViewedProduct::GetList(
								array("DATE_VISIT"=>"DESC"),
								array("FUSER_ID" => $arFuserItems["ID"], ">PRICE" => 0, "!CURRENCY" => "", "LID" => $str_LID),
								false,
								array('nTopCount' => 10),
								array('ID', 'PRODUCT_ID', 'LID', 'MODULE', 'NAME', 'DETAIL_PAGE_URL', 'PRICE', 'CURRENCY', 'PREVIEW_PICTURE', 'DETAIL_PICTURE')
							);
						while ($arViews = $dbViewsList->Fetch())
							$arViewed[] = $arViews;

						$arViewedResult = fDeleteDoubleProduct($arViewed, $arFilterRecomendet, 'N');
						if (count($arViewedResult["ITEMS"]) <= 0)
							$displayNoneViewed = "none";

						$tabBasket = "tabs";
						$tabViewed = "tabs";

						if ($displayNoneBasket == 'none' && $displayNone == 'none' && $displayNoneViewed == 'block')
							$tabViewed .= " active";
						if ($displayNoneBasket == 'block' && $displayNone == 'none')
							$tabBasket .= " active";
						?>
						<div id="tab_1" style="display:<?=$displayNone?>" class="tabs active" onClick="fTabsSelect('user_recomendet', this);"><?=GetMessage('NEWO_SUBTAB_RECOMENET')?></div>
						<div id="tab_2" style="display:<?=$displayNoneBasket?>" class="<?=$tabBasket?>" onClick="fTabsSelect('user_basket', this);"><?=GetMessage('NEWO_SUBTAB_BUSKET')?></div>
						<div id="tab_3" style="display:<?=$displayNoneViewed?>" class="<?=$tabViewed?>" onClick="fTabsSelect('buyer_viewed', this);"><?=GetMessage('NEWO_SUBTAB_LOOKED')?></div>

						<?
						if ($displayNone == 'block')
						{
							$displayNoneBasket = 'none';
							$displayNoneViewed = 'none';
						}
						if ($displayNoneBasket == 'block')
						{
							$displayNone = 'none';
							$displayNoneViewed = 'none';
						}
						if ($displayNoneViewed == 'block')
						{
							$displayNone = 'none';
							$displayNoneBasket = 'none';
						}
						?>
						<div id="user_recomendet" class="tabstext active" style="display:<?=$displayNone?>">
							<? echo fGetFormatedProduct($str_USER_ID, $LID, $arRecomendetResult, $str_CURRENCY, 'recom');?>
						</div>

						<div id="user_basket" class="tabstext active" style="display:<?=$displayNoneBasket?>">
						<?
							if (count($arShoppingCart["ITEMS"]) > 0)
								echo fGetFormatedProduct($str_USER_ID, $LID, $arShoppingCart, $str_CURRENCY, 'busket');
						?>
						</div>

						<div id="buyer_viewed" class="tabstext active" style="display:<?=$displayNoneViewed?>">
						<?
							if (count($arViewedResult["ITEMS"]) > 0)
								echo fGetFormatedProduct($str_USER_ID, $LID, $arViewedResult, $str_CURRENCY, 'viewed');
						?>

						</div>
					</div>
					<script>
					function fTabsSelect(tabText, el)
					{
						BX('tab_1').className = "tabs";
						BX('tab_2').className = "tabs";
						BX('tab_3').className = "tabs";

						BX(el).className = "tabs active";
						BX(el).style.display = 'block';

						BX('user_recomendet').className = "tabstext";
						BX('user_basket').className = "tabstext";
						BX('buyer_viewed').className = "tabstext";
						BX('user_recomendet').style.display = 'none';
						BX('user_basket').style.display = 'none';
						BX('buyer_viewed').style.display = 'none';

						BX(tabText).style.display = 'block';
						BX(tabText).className = "tabstext active";
					}
					</script>
				</td>

				<td valign="top" class="summary">
					<div class="order-itog">
					<table width="100%">
					<tr>
					<td class="title">
						<?echo GetMessage("NEWO_TOTAL_PRICE")?>
					</td>
					<td nowrap class="title">
						<div id="ORDER_TOTAL_PRICE" style="white-space:nowrap;">
							<?=SaleFormatCurrency($ORDER_TOTAL_PRICE, $str_CURRENCY);?>
						</div>
					</td>
					</tr>
					<tr class="price" style="display:<?echo (($ORDER_PRICE_WITH_DISCOUNT > 0) ? 'table-row' : 'none');?>" id="ORDER_PRICE_WITH_DISCOUNT_DESC_VISIBLE">
						<td id="ORDER_PRICE_WITH_DISCOUNT_DESC" class="title" >
							<div><?echo GetMessage("NEWO_TOTAL_PRICE_WITH_DISCOUNT_MARGIN")?></div>
						</td>
						<td nowrap>
							<div id="ORDER_PRICE_WITH_DISCOUNT">
									<?=SaleFormatCurrency($ORDER_PRICE_WITH_DISCOUNT, $str_CURRENCY);?>
							</div>
						</td>
					</tr>
					<tr>
					<td class="title">
						<?echo GetMessage("NEWO_TOTAL_DELIVERY")?>
					</td>
					<td nowrap>
						<div id="ORDER_DELIVERY_PRICE" style="white-space:nowrap;">
							<?=SaleFormatCurrency($deliveryPrice, $str_CURRENCY);?>
						</div>
					</td>
					</tr>
					<tr>
					<td class="title">
						<?echo GetMessage("NEWO_TOTAL_TAX")?>
					</td>
					<td nowrap>
						<div id="ORDER_TAX_PRICE" style="white-space:nowrap;">
							<?=SaleFormatCurrency($str_TAX_VALUE, $str_CURRENCY);?>
						</div>
					</td>
					</tr>
					<tr>
					<td class="title">
						<?echo GetMessage("NEWO_TOTAL_WEIGHT")?>
					</td>
					<td nowrap>
						<div id="ORDER_WAIGHT" style="white-space:nowrap;">
							<?=roundEx(DoubleVal($productWeight/$WEIGHT_KOEF), SALE_VALUE_PRECISION)." ".$WEIGHT_UNIT;?>
						</div>
					</td>
					</tr>
					<tr>
					<td class="title">
						<?echo GetMessage("NEWO_TOTAL_PAY_ACCOUNT2")?>
					</td>
					<td nowrap>
						<div id="ORDER_PAY_FROM_ACCOUNT" style="white-space:nowrap;">
							<?=SaleFormatCurrency(roundEx($str_SUM_PAID, SALE_VALUE_PRECISION), $str_CURRENCY);?>
						</div>
					</td>
					</tr>
					<tr class="price" style="display:<?echo (($str_DISCOUNT_VALUE > 0) ? 'table-row' : 'none');?>" id="ORDER_DISCOUNT_PRICE_VALUE">
						<td class="title" >
							<?echo GetMessage("NEWO_TOTAL_DISCOUNT_PRICE_VALUE")?>
						</td>
						<td nowrap>
							<div id="ORDER_DISCOUNT_PRICE_VALUE_VALUE" style="white-space:nowrap;">
									<?=SaleFormatCurrency($str_DISCOUNT_VALUE, $str_CURRENCY);?>
							</div>
						</td>
					</tr>
					<tr class="itog">
					<td class='ileft'>
						<div><?echo GetMessage("NEWO_TOTAL_TOTAL")?></div>
					</td>
					<td class='iright' nowrap>
						<div id="ORDER_PRICE_ALL" style="white-space:nowrap;">
							<?=SaleFormatCurrency($str_PRICE, $str_CURRENCY);?>
						</div>
					</td>
					</tr>
					</table>
					</div>
				</td>
			</tr>
		</table>
	</td>
</tr>
<?
$tabControl->EndCustomField("BASKET_CONTAINER");


if (!defined('BX_PUBLIC_MODE') || BX_PUBLIC_MODE != 1)
{
	$tabControl->Buttons(
		array("back_url" => "/bitrix/admin/sale_order.php?lang=".LANG."&LID=".CUtil::JSEscape($LID).GetFilterParams("filter_"))
	);
}

$tabControl->Show();

//order basket user by manager
if (isset($_GET["user_id"]) && isset($_GET["LID"]) && !$bVarsFromForm)
{
	$str_USER_ID = IntVal($_GET["user_id"]);
	$LID = trim($_GET["LID"]);

	$arParams = array();
	echo "<script>";
	echo "window.onload = function () {";
	echo "fUserGetProfile(BX(\"user_id\"));\n";

	if (CModule::IncludeModule('catalog')
			&& CModule::IncludeModule('iblock')
			&& isset($_GET["product"])
			&& count($_GET["product"]) > 0)
	{
		$bXmlId = COption::GetOptionString("sale", "show_order_product_xml_id", "N");
		$arProductId = array();
		$arBuyerGroups = CUser::GetUserGroup($str_USER_ID);
		$arGetProduct = array();

		$arSkuParentChildren = array();
		$arSkuParentId = array();
		$arSkuParent = array();

		foreach ($_GET["product"] as $key => $val)
		{
			$key = IntVal($key);
			if ($key > 0)
			{
				$arProductId[] = $key;
				$arGetProduct[$key] = (floatval($val) > 0) ? floatval($val) : 1;

				$arParent = CCatalogSku::GetProductInfo($key);
				if ($arParent)
				{
					$arSkuParentChildren[$key] = $arParent["ID"];
					$arSkuParentId[$arParent["ID"]] = $arParent["ID"];
				}
			}
		}

		$res = CIBlockElement::GetList(array(), array("ID" => $arSkuParentId), false, false, array("ID", "IBLOCK_ID", "IBLOCK_SECTION_ID", "PREVIEW_PICTURE", "DETAIL_PICTURE", "NAME", "DETAIL_PAGE_URL"));
		while ($arItems = $res->GetNext())
			$arSkuParent[$arItems["ID"]] = $arItems;

		$arOrder["SORT"] = "ASC";
		if (count($arGetProduct) > 0)
		{
			$res = CIBlockElement::GetList(array(), array("ID" => $arProductId), false, false, array("ID", "IBLOCK_ID", "IBLOCK_SECTION_ID", "PREVIEW_PICTURE", "DETAIL_PICTURE", "NAME", "DETAIL_PAGE_URL"));
			while ($arItems = $res->GetNext())
			{
				$productImg = "";
				$arSkuProps = array();

				//select props from basket
				$dbBasket = CSaleBasket::GetList(
					array("ID" => "DESC"),
					array("ORDER_ID" => "NULL", "USER_ID" => $str_USER_ID, "LID" => $LID, "PRODUCT_ID" => $arItems["ID"]),
					false,
					false,
					array("ID")
				);
				$arBasket = $dbBasket->Fetch();
				if ($arBasket && count($arBasket) > 0)
				{
					$arBasket["PROPS"] = Array();

					$arBasketFilter = array("BASKET_ID" => $arBasket["ID"]);
					if ($bXmlId == "N")
						$arBasketFilter["!CODE"] = array("PRODUCT.XML_ID", "CATALOG.XML_ID");

					$dbBasketProps = CSaleBasket::GetPropsList(
							array("SORT" => "ASC", "NAME" => "ASC"),
							$arBasketFilter,
							false,
							false,
							array("ID", "BASKET_ID", "NAME", "VALUE", "CODE", "SORT")
						);
					while ($arBasketProps = $dbBasketProps->GetNext())
						$arSkuProps[$arBasketProps["NAME"]] = $arBasketProps["VALUE"];
				}

				//select props from product sku
				if (count($arSkuProps) <= 0)
					$arSkuProps = CSaleProduct::GetProductSkuProps($arItems["ID"], $arItems["IBLOCK_ID"]);

				if ($arItems["PREVIEW_PICTURE"] == "" && $arItems["DETAIL_PICTURE"] == "" && is_set($arSkuParentChildren[$arItems["ID"]]))
				{
					$idTmp = $arSkuParentChildren[$arItems["ID"]];
					$arItems["DETAIL_PICTURE"] = $arSkuParent[$idTmp]["DETAIL_PICTURE"];
					$arItems["PREVIEW_PICTURE"] = $arSkuParent[$idTmp]["PREVIEW_PICTURE"];
				}

				if($arItems["PREVIEW_PICTURE"] != "")
					$productImg = $arItems["PREVIEW_PICTURE"];
				elseif($arItems["DETAIL_PICTURE"] != "")
					$productImg = $arItems["DETAIL_PICTURE"];

				$ImgUrl = "";
				if ($productImg != "")
				{
					$arFile = CFile::GetFileArray($productImg);
					$productImg = CFile::ResizeImageGet($arFile, array('width'=>80, 'height'=>80), BX_RESIZE_IMAGE_PROPORTIONAL, false, false);
					$ImgUrl = $productImg["src"];
				}

				$arPrice = CCatalogProduct::GetOptimalPrice($arItems["ID"], 1, $arBuyerGroups, "N", array(), $LID);
				$arCurFormat = CCurrencyLang::GetCurrencyFormat($arPrice["PRICE"]["CURRENCY"]);
				$priceValutaFormat = str_replace("#", '', $arCurFormat["FORMAT_STRING"]);

				if (!is_array($arPrice["DISCOUNT"]) || count($arPrice["DISCOUNT"]) <= 0)
				{
					$arPrice["DISCOUNT_PRICE"] = 0;
					$price = $arPrice["PRICE"]["PRICE"];
				}
				else
					$price = $arPrice["DISCOUNT_PRICE"];

				$summaFormated = CurrencyFormatNumber($price, $arPrice["PRICE"]["CURRENCY"]);
				$currentTotalPriceFormat = CurrencyFormatNumber($price, $arPrice["PRICE"]["CURRENCY"]);

				$balance = 0;
				$weight = 0;

				if($ar_res = CCatalogProduct::GetByID($arItems["ID"]))
				{
					$balance = FloatVal($ar_res["QUANTITY"]);
					$weight = FloatVal($ar_res["WEIGHT"]);
				}

				$discountPercent = 0;
				if ($arPrice["DISCOUNT_PRICE"] > 0)
				{
					$discountPercent = (($arPrice["PRICE"]["PRICE"]-$arPrice["DISCOUNT_PRICE"]) * 100) / $arPrice["PRICE"]["PRICE"];
					$discountPercent = roundEx($discountPercent, SALE_VALUE_PRECISION);
					$priceDiscount = $arPrice["PRICE"]["PRICE"] - $arPrice["DISCOUNT_PRICE"];
				}

				$urlEdit = "/bitrix/admin/iblock_element_edit.php?ID=".$arItems["ID"]."&type=".$arItems["IBLOCK_TYPE_ID"]."&lang=".LANG."&IBLOCK_ID=".$arItems["IBLOCK_ID"]."&find_section_section=".IntVal($arItems["IBLOCK_SECTION_ID"]);

				$arParams = array(
					'id' => $arItems["ID"],
					'name' => CUtil::JSEscape($arItems["NAME"]),
					'url' => CUtil::JSEscape($arItems["DETAIL_PAGE_URL"]),
					'urlImg' => CUtil::JSEscape($ImgUrl),
					'urlEdit' => CUtil::JSEscape($urlEdit),
					'price' => CUtil::JSEscape($price),
					'priceFormated' => CUtil::JSEscape($price),
					'priceBase' => CUtil::JSEscape($arPrice["PRICE"]["PRICE"]),
					'priceBaseFormat' => CUtil::JSEscape($arPrice["PRICE"]["PRICE"]),
					'valutaFormat' => CUtil::JSEscape($priceValutaFormat),
					'priceDiscount' => CUtil::JSEscape($priceDiscount),
					'summaFormated' => CUtil::JSEscape($summaFormated),
					'priceTotalFormated' => CUtil::JSEscape($currentTotalPriceFormat),
					'discountPercent' => CUtil::JSEscape($discountPercent),
					'balance'  => CUtil::JSEscape($balance),
					'quantity' => floatval($arGetProduct[$arItems["ID"]]),
					'module' => 'catalog',
					'currency' => CUtil::JSEscape($arPrice["PRICE"]["CURRENCY"]),
					'weight' => $weight,
					'vatRate' => DoubleVal('0'),
					'priceType' => '',
					'catalogXmlID' => '',
					'productXmlID' => '',
					'skuProps' => CUtil::PhpToJSObject($arSkuProps),
					'productProviderClass' => 'CCatalogProductProvider'
				);
				$arParams = CUtil::PhpToJSObject($arParams);

				echo "FillProductFields(0, ".$arParams.", 0);\n";
			}//end while
		}//end if
	}//end if
	if ($str_USER_ID > 0)
		echo "fButtonCurrent('btnBuyerExistRemote');";
	echo "};";
	echo "</script>";
}
echo "</div>";//end div for form
?>

<div class="sale_popup_form" id="popup_form_sku_order" style="display:none;">
	<table width="100%">
		<tr><td></td></tr>
		<tr>
			<td><small><span id="listItemPrice"></span>&nbsp;<span id="listItemOldPrice"></span></small></td>
		</tr>
		<tr>
			<td><hr></td>
		</tr>
	</table>

	<table width="100%" id="sku_selectors_list">
		<tr>
			<td colspan="2"></td>
		</tr>
	</table>

	<span id="prod_order_button"></span>
	<input type="hidden" value="" name="popup-params-product" id="popup-params-product" >
	<input type="hidden" value="" name="popup-params-type" id="popup-params-type" >
</div>
	<script>
			var wind = new BX.PopupWindow('popup_sku', this, {
				offsetTop : 10,
				offsetLeft : 0,
				autoHide : true,
				closeByEsc : true,
				closeIcon : true,
				titleBar : true,
				draggable: {restrict:true},
				titleBar: {content: BX.create("span", {html: '', 'props': {'className': 'sale-popup-title-bar'}})},
				content : document.getElementById("popup_form_sku_order"),

				buttons: [
					new BX.PopupWindowButton({
						text : '<?=GetMessageJS('NEWO_POPUP_CAN_BUY_NOT');?>',
						id : "popup_sku_save",
						events : {
							click : function() {
								if (BX('popup-params-product').value.length > 0)
								{
									if (BX('popup-params-type').value == 'neworder')
									{
										window.location = BX('popup-params-product').value;
									}
									else
									{
										var res = eval( '('+BX('popup-params-product').value+')' );
										FillProductFields(0, res, 0);
									}

									wind.close();
								}
							}
						}
					}),
					new BX.PopupWindowButton({
						text : '<?=GetMessageJS('NEWO_POPUP_CLOSE');?>',
						id : "popup_sku_cancel",
						events : {
							click : function() {
								wind.close();
							}
						}
					})
				]
			});
			function fAddToBusketMoreProductSku(arSKU, arProperties, type, message)
			{
				BX.message(message);
				wind.show();
				buildSelect("sku_selectors_list", 0, arSKU, arProperties, type);
				var properties_num = arProperties.length;
				var lastPropCode = arProperties[properties_num-1].CODE;
				addHtml(lastPropCode, arSKU, type);
			}
			function buildSelect(cont_name, prop_num, arSKU, arProperties, type)
			{
				var properties_num = arProperties.length;
				var lastPropCode = arProperties[properties_num-1].CODE;

				for (var i = prop_num; i < properties_num; i++)
				{
					var q = BX('prop_' + i);
					if (q)
						q.parentNode.removeChild(q);
				}

				var select = BX.create('SELECT', {
					props: {
						name: arProperties[prop_num].CODE,
						id :  arProperties[prop_num].CODE
					},
					events: {
						change: (prop_num < properties_num-1)
							? function() {
								buildSelect(cont_name, prop_num + 1, arSKU, arProperties, type);
								if (this.value != "null")
									BX(arProperties[prop_num+1].CODE).disabled = false;
								addHtml(lastPropCode, arSKU, type);
							}
							: function() {
								if (this.value != "null")
									addHtml(lastPropCode, arSKU, type)
							}
					}
				});
				if (prop_num != 0) select.disabled = true;

				var ar = [];
				select.add(new Option(arProperties[prop_num].NAME, 'null'));

				for (var i = 0; i < arSKU.length; i++)
				{
					if (checkSKU(arSKU[i], prop_num, arProperties) && !BX.util.in_array(arSKU[i][prop_num], ar))
					{
						select.add(new Option(
								arSKU[i][prop_num],
								prop_num < properties_num-1 ? arSKU[i][prop_num] : arSKU[i]["ID"]
						));
						ar.push(arSKU[i][prop_num]);
					}
				}

				var cont = BX.create('tr', {
					props: {id: 'prop_' + prop_num},
					children:[
						BX.create('td', {html: arProperties[prop_num].NAME + ': '}),
						BX.create('td', { children:[
							select
						]}),
					]
				});

				var tmp = BX.findChild(BX(cont_name), {tagName:'tbody'}, false, false);

				tmp.appendChild(cont);

				if (prop_num < properties_num-1)
					buildSelect(cont_name, prop_num + 1, arSKU, arProperties, type);
			}

			function checkSKU(SKU, prop_num, arProperties)
			{
				for (var i = 0; i < prop_num; i++)
				{
					code = BX.findChild(BX('popup_sku'), {'attr': {name: arProperties[i].CODE}}, true, false).value;
					if (SKU[i] != code)
						return false;
				}
				return true;
			}
			function addHtml(lastPropCode, arSKU, type)
			{
				var selectedSkuId = BX(lastPropCode).value;
				var btnText = '';

				BX('popup-window-titlebar-popup_sku').innerHTML = '<span class="sale-popup-title-bar">'+arSKU[0]["PRODUCT_NAME"]+'</span>';
				BX("listItemPrice").innerHTML = BX.message('PRODUCT_PRICE_FROM')+" "+arSKU[0]["MIN_PRICE"];
				BX("listItemOldPrice").innerHTML = '';

				for (var i = 0; i < arSKU.length; i++)
				{
					if (arSKU[i]["ID"] == selectedSkuId)
					{
						BX('popup-window-titlebar-popup_sku').innerHTML = '<span class="sale-popup-title-bar">'+arSKU[i]["NAME"]+'</span>';

						if (arSKU[i]["DISCOUNT_PRICE"] != "")
						{
							BX("listItemPrice").innerHTML = arSKU[i]["DISCOUNT_PRICE_FORMATED"]+" "+arSKU[i]["VALUTA_FORMAT"];
							BX("listItemOldPrice").innerHTML = arSKU[i]["PRICE_FORMATED"]+" "+arSKU[i]["VALUTA_FORMAT"];
							summaFormated = arSKU[i]["DISCOUNT_PRICE_FORMATED"];
							price = arSKU[i]["DISCOUNT_PRICE"];
							priceFormated = arSKU[i]["DISCOUNT_PRICE_FORMATED"];
							priceDiscount = arSKU[i]["PRICE"] - arSKU[i]["DISCOUNT_PRICE"];
						}
						else
						{
							BX("listItemPrice").innerHTML = arSKU[i]["PRICE_FORMATED"]+" "+arSKU[i]["VALUTA_FORMAT"];
							BX("listItemOldPrice").innerHTML = "";
							summaFormated = arSKU[i]["PRICE_FORMATED"];
							price = arSKU[i]["PRICE"];
							priceFormated = arSKU[i]["PRICE_FORMATED"];
							priceDiscount = 0;
						}

						if (arSKU[i]["CAN_BUY"] == "Y")
						{
							var arParams = "{'id' : '"+arSKU[i]["ID"]+"',\n\
							'name' : '"+arSKU[i]["NAME"]+"',\n\
							'url' : '',\n\
							'urlEdit' : '"+arSKU[i]["URL_EDIT"]+"',\n\
							'urlImg' : '"+arSKU[i]["ImageUrl"]+"',\n\
							'price' : '"+price+"',\n\
							'priceFormated' : '"+priceFormated+"',\n\
							'valutaFormat' : '"+arSKU[i]["VALUTA_FORMAT"]+"',\n\
							'priceDiscount' : '"+priceDiscount+"',\n\
							'priceBase' : '"+arSKU[i]["PRICE"]+"',\n\
							'priceBaseFormat' : '"+arSKU[i]["PRICE_FORMATED"]+"',\n\
							'priceTotalFormated' : '"+arSKU[i]["DISCOUNT_PRICE"]+"',\n\
							'discountPercent' : '"+arSKU[i]["DISCOUNT_PERCENT"]+"',\n\
							'summaFormated' : '"+summaFormated+"',\n\
							'quantity' : '1','module' : 'catalog',\n\
							'currency' : '"+arSKU[i]["CURRENCY"]+"',\n\
							'skuProps' : \""+arSKU[i]["SKU_PROPS"]+"\",\n\
							'weight' : '0','vatRate' : '0','priceType' : '',\n\
							'balance' : '"+arSKU[i]["BALANCE"]+"',\n\
							'catalogXmlID' : '','productXmlID' : '',\n\
							'callback' : 'CatalogBasketCallback','orderCallback' : 'CatalogBasketOrderCallback','cancelCallback' : 'CatalogBasketCancelCallback','payCallback' : 'CatalogPayOrderCallback', 'productProviderClass' : 'CCatalogProductProvider'}";

							BX('popup-params-type').value = type;

							if (type != 'neworder')
							{
								message = BX.message('PRODUCT_ADD');
								BX('popup-params-product').value = arParams;
							}
							else
							{
								message = BX.message('PRODUCT_ORDER');
								BX('popup-params-product').value = "/bitrix/admin/sale_order_new.php?lang=<?=LANG?>&user_id="+arSKU[i]["USER_ID"]+"&LID="+arSKU[i]["LID"]+"&product["+arSKU[i]["ID"]+"]=1";
							}
						}
						else
						{
							BX('popup-params-product').value = '';
							message = BX.message('PRODUCT_NOT_ADD');
						}

						BX.findChild(BX('popup_sku_save'), {'attr': {class: 'popup-window-button-text'}}, true, false).innerHTML = message;
					}

					if (arSKU[i]["ID"] == selectedSkuId)
						break;
				}
			}
	</script>

<br>

<?echo BeginNote();?>
1) - <?echo GetMessage("NEWO_ORDER_RECOUNT_HINT")?><br>
<?
echo EndNote();

require($DOCUMENT_ROOT."/bitrix/modules/main/include/epilog_admin.php");
?>
