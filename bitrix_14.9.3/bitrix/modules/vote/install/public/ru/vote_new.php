<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
$APPLICATION->SetTitle("�����");
$APPLICATION->AddChainItem("����� �������", "vote_list.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_after.php");
?>
<?
$VOTE_ID = $_REQUEST["VOTE_ID"]; // ����� ID ������ �� ���������� ��������

// ������� ������������� �������� ������� ������ �������
/*
if (CModule::IncludeModule("vote"))
{
	$bIsUserVoted = IsUserVoted($VOTE_ID)	// ��������� ��������� �� ��� ������ ���������� (���������� true ���� false)
	$VOTE_ID = GetCurrentVote("ANKETA");	// ���������� ID �������� ������ ������ ANKETA
	$VOTE_ID = GetPrevVote("ANKETA");		// ���������� ID ����������� ������ ������ ANKETA
	$VOTE_ID = GetAnyAccessibleVote();		// ���������� ID ������ ���������� ��� ����������� ������
}
*/
?>

<?$APPLICATION->IncludeComponent("bitrix:voting.form", ".default", Array(
	"VOTE_ID"	=>	$_REQUEST["VOTE_ID"],
	"VOTE_RESULT_TEMPLATE"	=>	"vote_result.php?VOTE_ID=#VOTE_ID#"
	)
);?>

<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog.php");?>
