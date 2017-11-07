<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$aMenuLinks = Array(
	Array(
		"Как купить", 
		"about/howto/", 
		Array(), 
		Array(), 
		"" 
	),
	Array(
		"Доставка", 
		"about/delivery/", 
		Array(), 
		Array(), 
		"" 
	),
	Array(
		"О магазине", 
		"about/", 
		Array(), 
		Array(), 
		"" 
	),	
	Array(
		"Гарантия", 
		"about/guaranty/", 
		Array(), 
		Array(), 
		"" 
	),
	Array(
		"Контакты",
		"about/contacts/",
		Array(),
		Array(),
		""
	),
	Array(
		"Есть идея?", 
		"about/idea/", 
		Array(), 
		Array(), 
		"IsModuleInstalled('idea') && COption::GetOptionString('eshop', 'useIdea', 'Y','".SITE_ID."') == 'Y'" 
	),
	Array(
		"Мой кабинет",
		"personal/",
		Array(),
		Array(),
		"CUser::IsAuthorized()"
	),
);
?>