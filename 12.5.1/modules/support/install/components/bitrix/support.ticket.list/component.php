<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

require_once($_SERVER["DOCUMENT_ROOT"].$componentPath."/functions.php");

if (!CModule::IncludeModule("support"))
{
	ShowError(GetMessage("MODULE_NOT_INSTALL"));
	return;
}

//Permissions
if ( !($USER->IsAuthorized() && (CTicket::IsSupportClient() || CTicket::IsAdmin() || CTicket::IsSupportTeam())) )
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

//Sorting
InitSorting();
if (strlen($GLOBALS["by"]) <= 0)
{
	$GLOBALS["by"] = "s_timestamp";
	$GLOBALS["order"] = "s_timestamp";
}

//Filter
$FilterArr = Array(
	"find_id",
	"find_id_exact_match",
	"find_site",
	"find_lamp",
	"find_close",
	"find_title",
	"find_title_exact_match",
	"find_message",
	"find_message_exact_match",
);

_InitFilter($FilterArr);

InitBVar($_REQUEST["find_id_exact_match"]);
InitBVar($_REQUEST["find_title_exact_match"]);
InitBVar($_REQUEST["find_message_exact_match"]);

$arFilter = Array(
	"ID"	=> $_REQUEST["find_id"],
	"ID_EXACT_MATCH"				=> $_REQUEST["find_id_exact_match"],
	"LAMP"							=> $_REQUEST["find_lamp"],
	"CLOSE"							=> $_REQUEST["find_close"],
	"TITLE"							=> $_REQUEST["find_title"],
	"TITLE_EXACT_MATCH"				=> $_REQUEST["find_title_exact_match"],
	"MESSAGE"						=> $_REQUEST["find_message"],
	"MESSAGE_EXACT_MATCH"			=> $_REQUEST["find_message_exact_match"],
);

if (strlen($arParams["SITE_ID"]) > 0)
	$arFilter["LID"] = $arParams["SITE_ID"];

//TICKET_EDIT_TEMPLATE
$arParams["TICKET_EDIT_TEMPLATE"] = trim($arParams["TICKET_EDIT_TEMPLATE"]);
$arParams["TICKET_EDIT_TEMPLATE"] = (strlen($arParams["TICKET_EDIT_TEMPLATE"]) > 0 ? htmlspecialcharsbx($arParams["TICKET_EDIT_TEMPLATE"]) : "ticket_edit.php?ID=#ID#");

//TICKETS_PER_PAGE
$arParams["TICKETS_PER_PAGE"] = (intval($arParams["TICKETS_PER_PAGE"]) <= 0 ? 50 : intval($arParams["TICKETS_PER_PAGE"]));

//Get Tickets
CPageOption::SetOptionString("main", "nav_page_in_session", "N");

$UFA = array();
$UFAT = array();
global $USER_FIELD_MANAGER;
$arrUF = $USER_FIELD_MANAGER->GetUserFields( "SUPPORT", 0, LANGUAGE_ID );
if( isset( $arParams["SET_SHOW_USER_FIELD"] ) )
{
	foreach( $arParams["SET_SHOW_USER_FIELD"] as $k => $v )
	{
		if( strlen( trim( $v ) ) > 0 )
		{
			$UFAT[$v] = array(
							"NAME_C" => $arrUF[$v]["LIST_COLUMN_LABEL"],
							"NAME_F" => $arrUF[$v]["EDIT_FORM_LABEL"],
							"ALL" => $arrUF[$v],
			);
			$UFA[] = $v;
		}
	}
}
$arParams["SET_SHOW_USER_FIELD_T"] = $UFAT;

$rsTickets = CTicket::GetList($GLOBALS["by"], $GLOBALS["order"], $arFilter, $is_filtered, $check_rights = "Y", $get_user_name = "N", $get_dictionary_name = "N", false, array( "SELECT" => $UFA ) );
$rsTickets->NavStart($arParams["TICKETS_PER_PAGE"]);

//Result array
$arResult = Array(
	"TICKETS" => Array(),
	"TICKETS_COUNT" => $rsTickets->SelectedRowsCount(),
	"NAV_STRING" => $rsTickets->GetPageNavString(GetMessage("SUP_PAGES")),
	"CURRENT_PAGE" => htmlspecialcharsbx($APPLICATION->GetCurPage()),
	"NEW_TICKET_PAGE" => htmlspecialcharsbx(CComponentEngine::MakePathFromTemplate($arParams["TICKET_EDIT_TEMPLATE"], Array("ID" => "0"))),
);


//Get Dictionary Array
$arTicketDictionary = CTicketDictionary::GetDropDownArray();

//Dictionary table
$arDictType = Array(
		"C" => "CATEGORY",
		"K" => "CRITICALITY",
		"S" => "STATUS",
		"M" => "MARK",
		"SR" => "SOURCE",
);

while ($arTicket = $rsTickets->GetNext())
{
	$arUsersName =	_GetUserInfo($arTicket["RESPONSIBLE_USER_ID"], "RESPONSIBLE") + 
							_GetUserInfo($arTicket["OWNER_USER_ID"], "OWNER") +
							_GetUserInfo($arTicket["MODIFIED_USER_ID"], "MODIFIED");

	$arDict = Array();
	foreach ($arDictType as $TYPE => $CODE)
		$arDict += _GetDictionaryInfo($arTicket[$CODE."_ID"], $TYPE, $CODE, $arTicketDictionary);


	$url = CComponentEngine::MakePathFromTemplate($arParams["TICKET_EDIT_TEMPLATE"], Array("ID" => $arTicket["ID"]));

	$arResult["TICKETS"][] = ($arTicket + $arDict + $arUsersName + Array("TICKET_EDIT_URL" => $url));
}

//Set Title
$arParams["SET_PAGE_TITLE"] = ($arParams["SET_PAGE_TITLE"] == "N" ? "N" : "Y" );

if ($arParams["SET_PAGE_TITLE"] == "Y")
	$APPLICATION->SetTitle(GetMessage("SUP_DEFAULT_TITLE"));

unset($rsTickets);
unset($arTicketDictionary);

$this->IncludeComponentTemplate();
?>