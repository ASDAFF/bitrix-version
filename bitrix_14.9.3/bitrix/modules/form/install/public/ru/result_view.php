<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("�������� ������");
?>
<?
$APPLICATION->IncludeFile("form/result_view/default.php", array(
	"RESULT_ID"				=> $_REQUEST["RESULT_ID"],	// ID ����������
	"SHOW_ADDITIONAL"		=> "N",						// �������� �������������� ���� ���-����� ?
	"SHOW_ANSWER_VALUE"		=> "N",						// �������� �������� ��������� ANSWER_VALUE ?
	"SHOW_STATUS"			=> "Y",						// �������� ������� ������ ���������� ?
	"EDIT_URL"				=> "result_edit.php",		// �������� �������������� ����������
	"CHAIN_ITEM_TEXT"		=> "������ �����",			// �������������� ����� � ������������� �������
	"CHAIN_ITEM_LINK"		=> "result_list.php?WEB_FORM_ID=".$_REQUEST["WEB_FORM_ID"], // ������ �� ���. ������ � ������������� �������
	));
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>