<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$aMenuLinks = Array(
	Array(
		"��� ������", 
		"about/howto/", 
		Array(), 
		Array(), 
		"" 
	),
	Array(
		"��������", 
		"about/delivery/", 
		Array(), 
		Array(), 
		"" 
	),
	Array(
		"� ��������", 
		"about/", 
		Array(), 
		Array(), 
		"" 
	),	
	Array(
		"��������", 
		"about/guaranty/", 
		Array(), 
		Array(), 
		"" 
	),
	Array(
		"��������",
		"about/contacts/",
		Array(),
		Array(),
		""
	),
	Array(
		"���� ����?", 
		"about/idea/", 
		Array(), 
		Array(), 
		"IsModuleInstalled('idea') && COption::GetOptionString('eshop', 'useIdea', 'Y','".SITE_ID."') == 'Y'" 
	),
	Array(
		"��� �������",
		"personal/",
		Array(),
		Array(),
		"CUser::IsAuthorized()"
	),
);
?>