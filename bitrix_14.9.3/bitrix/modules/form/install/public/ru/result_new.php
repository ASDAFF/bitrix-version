<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("���������� ������");
?>
<?
$APPLICATION->IncludeFile("form/result_new/default.php", array(
	"WEB_FORM_ID"		=> $_REQUEST["WEB_FORM_ID"],		// ID ���-�����
	"LIST_URL"			=> "result_list.php",				// �������� ������ �����������
	"EDIT_URL"			=> "result_edit.php",				// �������� �������������� ����������
	"CHAIN_ITEM_TEXT"	=> "������ �����",					// �������������� ����� � ������������� �������
	"CHAIN_ITEM_LINK"	=> "result_list.php?WEB_FORM_ID=".$_REQUEST["WEB_FORM_ID"], // ������ �� ���. ������ � ������������� �������
	));
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>