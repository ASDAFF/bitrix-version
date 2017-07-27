<?
define("STOP_STATISTICS", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
IncludeModuleLangFile(__FILE__);

if(!CModule::IncludeModule("av.ibprops")) {
	CAdminMessage::ShowMessage(GetMessage("av_error_module"));
	die();
}

if (
	$_SERVER['REQUEST_METHOD'] == 'POST' 
	&& check_bitrix_sessid()
)
{
	// IBLOCK_ID in POST, on first step IBLOCK_ID in GET
	try {
		if(isset($_POST["PARAMS"])) {
			$ob = new C_AV_IBlock_Manage(intval($_POST["PARAMS"]['ibID']), intval($_POST["PARAMS"]['interval']));
			if(strlen($ob->strError))
				throw new MyException($ob->strError);
			
			$ob->setParamsFromArray($_POST["PARAMS"]);
			$ob->setParamsFromFile();
			if(strlen($ob->strError))
				throw new MyException($ob->strError);
				
			$ob->work();

			$ob->showResult();
			if($ob->finish) {
				echo "<script>End()</script>";
			} else {
				$arParams = $ob->saveParams2Array();
				echo '<script> DoNext('.CUtil::PhpToJSObject(array("PARAMS"=>$arParams)).'); </script>';
			}
		}
		elseif(isset($_GET["firststart"])) {
			$ob = new C_AV_IBlock_Manage(intval($_GET['IBLOCK_ID']), intval($_GET['interval']));
			if(strlen($ob->strError))
				throw new MyException($ob->strError);
			
			$ob->getUpdateFields();
			CUtil::JSPostUnescape();
			
			$ob->fillPost();
			$ob->getFilter();
			$ob->getFields2Update();
			if(strlen($ob->strError))
				throw new MyException($ob->strError);
				
			$count = $ob->getElementCount();
			if(strlen($ob->strError))
				throw new MyException($ob->strError);

			// $ob->work();
			
			$ob->showResult();
			$ob->saveParams2File();
			if(strlen($ob->strError))
				throw new MyException($ob->strError);
			$arParams = $ob->saveParams2Array();
			echo '<script> DoNext('.CUtil::PhpToJSObject(array("PARAMS"=>$arParams)).'); </script>';
			die();
		}
	} catch (Exception $e) {
		echo C_AV_IBlock_Manage::showError($e->getMessage());
		die();
	}
}
else
{
	C_AV_IBlock_Manage::showError(GetMessage("av_update_page"));
	
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_after.php");?>