<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("������");
?>
<?
$WEB_FORM_NAME = $_REQUEST["WEB_FORM_NAME"];
if (strlen($WEB_FORM_NAME)<=0) $WEB_FORM_NAME = "ANKETA";
?>
<?
$APPLICATION->IncludeFile("form/result_list/default.php", array(
	"WEB_FORM_ID"			=> $_REQUEST["WEB_FORM_ID"],	// ID ���-����� 
	"WEB_FORM_NAME"			=> $WEB_FORM_NAME,				// ���������� ��� ���-�����
	"VIEW_URL"				=> "result_view.php",			// �������� ��������� �����������
	"EDIT_URL"				=> "result_edit.php",			// �������� �������������� �����������
	"NEW_URL"				=> "result_new.php",			// �������� �������� ������ ����������
	"SHOW_ADDITIONAL"		=> "N",							// �������� �������������� ���� ���-����� � ������� ����������� ?
	"SHOW_ANSWER_VALUE"		=> "N",							// �������� �������� ANSWER_VALUE � ������� ����������� ?
	"SHOW_STATUS"			=> "Y",							// �������� ������ ������� ���������� � ������� ����������� ?
	"NOT_SHOW_FILTER"		=> "",							// ���� ����� ������� ������ ���������� � ������� (����� �������)
	"NOT_SHOW_TABLE"		=> ""							// ���� ����� ������� ������ ���������� � ������� (����� �������)
	));
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>