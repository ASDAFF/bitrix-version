<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("��������� ��������");
?><p>
���� �������� ���������� �� ���������� ����� � 1992 ����. �� ��� ����� ���������� ��������� ������ ������� ���� �� ��������� �������� ����� �� ������ �� ���������� �������������� ��������� ������ � ������.
</p><p>
���������� ��������� ������������ ������������ ������ �� �������������� ������������ � ����������� ����������� ���� ������� �����, ��� ��������� ���������� ������� �������� ����� ���������. ������� ���������������� ������� ��� ��������� � ��������������� ���������, ��� � ����� ������� ��������� ���������� ���������� ������������ ������� � �������������� ������ � � ������.
<h3>���� ���������</h3>
<?$APPLICATION->IncludeComponent("bitrix:furniture.catalog.index", "", array(
	"IBLOCK_TYPE" => "products",
	"IBLOCK_ID" => "#PRODUCTS_IBLOCK_ID#",
	"IBLOCK_BINDING" => "section",
	"CACHE_TYPE" => "A",
	"CACHE_TIME" => "36000000",
	"CACHE_GROUPS" => "N"
	),
	false
);?>
<h3>���� ������</h3>
<?$APPLICATION->IncludeComponent("bitrix:furniture.catalog.index", "", array(
	"IBLOCK_TYPE" => "products",
	"IBLOCK_ID" => "#SERVICES_IBLOCK_ID#",
	"IBLOCK_BINDING" => "element",
	"CACHE_TYPE" => "A",
	"CACHE_TIME" => "36000000",
	"CACHE_GROUPS" => "N"
	),
	false
);?>
</p><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>