<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("�������������� ������");
?>
<?
$APPLICATION->IncludeFile("form/result_edit/default.php", array(
	"RESULT_ID"			=> $_REQUEST["RESULT_ID"],			// ID ����������
	"EDIT_ADDITIONAL"	=> "N",								// �������� �� �������������� �������������� ���� ���-����� ?
	"EDIT_STATUS"		=> "Y",								// �������� ����� ����� ������� ?
	"LIST_URL"			=> "result_list.php",				// �������� �� ������� �����������
	"VIEW_URL"			=> "result_view.php",				// �������� ��������� ����������
	"CHAIN_ITEM_TEXT"	=> "������ �����",					// �������������� ����� � ������������� �������
	"CHAIN_ITEM_LINK"	=> "result_list.php?WEB_FORM_ID=".$_REQUEST["WEB_FORM_ID"], // ������ �� ���. ������ � ������������� �������
	));
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>