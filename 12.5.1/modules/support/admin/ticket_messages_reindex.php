<?
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/support/include.php");
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/support/prolog.php");
	IncludeModuleLangFile(__FILE__);
	if( !check_bitrix_sessid( "b_sessid" ) || !isset( $_REQUEST['MY_AJAX'] ) ) die();
	
	if($_REQUEST['MY_AJAX'] == 'reindexMessAJAX' && isset($_REQUEST["reindexMessAJAXData"]) && is_array($_REQUEST["reindexMessAJAXData"]) && count($_REQUEST["reindexMessAJAXData"]) > 0) 
	{
		$periodS = $_REQUEST["reindexMessAJAXData"]["periodS"];
		$firstID = $_REQUEST["reindexMessAJAXData"]["firstID"];
		$lastID = CSupportSearch::reindexMessages( $firstID, $periodS );
		echo json_encode( array( "LAST_ID" => $lastID ) );
	}
	elseif( $_REQUEST['MY_AJAX'] == 'restartAgentsAJAX' )
	{
		CTicketReminder::StartAgent();
		echo json_encode( array( "ALL_OK" => "OK" ) );
	}
	
?>