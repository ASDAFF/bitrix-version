<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("������ ����������");
?><p>� ���� ������� �� ������ ����� ������ �� ������ �������, ���������� ������ ������ �����. ���� �� �� ����� ������������ ��� ����������, �� ������ ��������� ��� ������ � ������� <a href="../contacts/">����� �������� �����</a>.</p>
 <?$APPLICATION->IncludeComponent("bitrix:support.faq.element.list", ".default", array(
		"IBLOCK_TYPE" => "services",
		"IBLOCK_ID" => "#FAQ_IBLOCK_ID#",
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "36000000",
		"CACHE_GROUPS" => "Y",
		"AJAX_MODE" => "N",
		"SECTION_ID" => "#FAQ_SECTION_ID#",
		"AJAX_OPTION_SHADOW" => "Y",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "Y",
		"AJAX_OPTION_HISTORY" => "N"
	)
);?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php")?>