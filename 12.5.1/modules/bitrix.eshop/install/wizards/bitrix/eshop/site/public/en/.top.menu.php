<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$aMenuLinks = Array(
	Array(
		"Ordering Information", 
		"about/howto/", 
		Array(), 
		Array(), 
		"" 
	),
	Array(
		"Delivery", 
		"about/delivery/", 
		Array(), 
		Array(), 
		"" 
	),
	Array(
		"About Us", 
		"about/", 
		Array(), 
		Array(), 
		"" 
	),
	Array(
		"Contacts",
		"about/contacts/",
		Array(),
		Array(),
		""
	),
	Array(
		"Suggestions",
		"about/idea/",
		Array(),
		Array(),
		"IsModuleInstalled('idea') && COption::GetOptionString('eshop', 'useIdea', 'Y','".SITE_ID."') == 'Y'" 
	),
	Array(
		"My Account",
		"personal/",
		Array(),
		Array(),
		"CUser::IsAuthorized()"
	),
);
?>