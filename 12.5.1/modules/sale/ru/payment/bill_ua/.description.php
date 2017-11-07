<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?><?
include(GetLangFileName(dirname(__FILE__)."/", "/bill.php"));

$psTitle = GetMessage("SBLP_DTITLE");
$psDescription = GetMessage("SBLP_DDESCR");

$arPSCorrespondence = array(
		"ORDER_ID" => array(
				"NAME" => GetMessage("SBLP_ORDER_ID"),
				"DESCR" => "",
				"VALUE" => "ID",
				"TYPE" => "ORDER"
			),
		"DATE_INSERT" => array(
				"NAME" => GetMessage("SBLP_DATE"),
				"DESCR" => GetMessage("SBLP_DATE_DESC"),
				"VALUE" => "DATE_INSERT_DATE",
				"TYPE" => "ORDER"
			),
		"SELLER_NAME" => array(
				"NAME" => GetMessage("SBLP_SUPPLI"),
				"DESCR" => GetMessage("SBLP_SUPPLI_DESC"),
				"VALUE" => "",
				"TYPE" => ""
			),
		"SELLER_RS" => array(
				"NAME" => GetMessage("SBLP_ORDER_SUPPLI"),
				"DESCR" => GetMessage("SBLP_ORDER_SUPPLI_DESC"),
				"VALUE" => "",
				"TYPE" => ""
			),
		"SELLER_BANK" => array(
				"NAME" => GetMessage("SBLP_ORDER_BANK"),
				"DESCR" => "",
				"VALUE" => "",
				"TYPE" => ""
			),
		"SELLER_MFO" => array(
				"NAME" => GetMessage("SBLP_ORDER_MFO"),
				"DESCR" => "",
				"VALUE" => "",
				"TYPE" => ""
			),
		"SELLER_ADDRESS" => array(
				"NAME" => GetMessage("SBLP_ADRESS_SUPPLI"),
				"DESCR" => GetMessage("SBLP_ADRESS_SUPPLI_DESC"),
				"VALUE" => "",
				"TYPE" => ""
			),
		"SELLER_PHONE" => array(
				"NAME" => GetMessage("SBLP_PHONE_SUPPLI"),
				"DESCR" => GetMessage("SBLP_PHONE_SUPPLI_DESC"),
				"VALUE" => "",
				"TYPE" => ""
			),
		"SELLER_EDRPOY" => array(
				"NAME" => GetMessage("SBLP_EDRPOY_SUPPLI"),
				"DESCR" => GetMessage("SBLP_EDRPOY_SUPPLI_DESC"),
				"VALUE" => "",
				"TYPE" => ""
			),
		"SELLER_IPN" => array(
				"NAME" => GetMessage("SBLP_IPN_SUPPLI"),
				"DESCR" => GetMessage("SBLP_IPN_SUPPLI_DESC"),
				"VALUE" => "",
				"TYPE" => ""
			),
		"SELLER_PDV" => array(
				"NAME" => GetMessage("SBLP_PDV_SUPPLI"),
				"DESCR" => GetMessage("SBLP_PDV_SUPPLI_DESC"),
				"VALUE" => "",
				"TYPE" => ""
			),
		"SELLER_SYS" => array(
				"NAME" => GetMessage("SBLP_SYS_SUPPLI"),
				"DESCR" => GetMessage("SBLP_SYS_SUPPLI_DESC"),
				"VALUE" => "",
				"TYPE" => ""
			),
		"BUYER_NAME" => array(
				"NAME" => GetMessage("SBLP_CUSTOMER"),
				"DESCR" => GetMessage("SBLP_CUSTOMER_DESC"),
				"VALUE" => "COMPANY_NAME",
				"TYPE" => "PROPERTY"
			),

		"BUYER_ADDRESS" => array(
				"NAME" => GetMessage("SBLP_CUSTOMER_ADRES"),
				"DESCR" => GetMessage("SBLP_CUSTOMER_ADRES_DESC"),
				"VALUE" => "ADDRESS",
				"TYPE" => "PROPERTY"
			),
		"BUYER_FAX" => array(
				"NAME" => GetMessage("SBLP_CUSTOMER_FAX"),
				"DESCR" => GetMessage("SBLP_CUSTOMER_FAX_DESC"),
				"VALUE" => "",
				"TYPE" => ""
			),


		"BUYER_PHONE" => array(
				"NAME" => GetMessage("SBLP_CUSTOMER_PHONE"),
				"DESCR" => GetMessage("SBLP_CUSTOMER_PHONE_DESC"),
				"VALUE" => "PHONE",
				"TYPE" => "PROPERTY"
			),
		"BUYER_DOGOVOR" => array(
				"NAME" => GetMessage("SBLP_CUSTOMER_DOGOVOR"),
				"DESCR" => GetMessage("SBLP_CUSTOMER_DOGOVOR"),
				"VALUE" => "",
				"TYPE" => ""
			),
		"PATH_TO_STAMP" => array(
				"NAME" => GetMessage("SBLP_PRINT"),
				"DESCR" => GetMessage("SBLP_PRINT_DESC"),
				"VALUE" => "",
				"TYPE" => ""
			)
	);
?>