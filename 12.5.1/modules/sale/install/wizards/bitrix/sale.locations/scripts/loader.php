<?
define("STOP_STATISTICS", true);
define('DLSERVER', 'www.1c-bitrix.ru');
define('DLPORT', 80);
define('DLPATH', '/download/files/locations/');
define('UPPATH', '/bitrix/wizards/bitrix/sale.locations/upload/');
define('DLMETHOD', 'GET');
define('DLZIPFILE', 'zip_ussr.csv');

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/wizard.php");

set_time_limit(600);

$wizard =  new CWizard("bitrix:sale.locations");
$wizard->IncludeWizardLang("scripts/loader.php");

$saleModulePermissions = $APPLICATION->GetGroupRight("sale");
if ($saleModulePermissions < "W")
{
	echo GetMessage('WSL_LOADER_ERROR_ACCESS_DENIED');
	require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
	die();
}

$STEP = intval($_REQUEST['STEP']);
$CSVFILE = $_REQUEST["CSVFILE"];
$LOADZIP = $_REQUEST["LOADZIP"];

if (strlen($CSVFILE) > 0 && !in_array($CSVFILE, array('loc_ussr.csv', 'loc_usa.csv', 'loc_cntr.csv', 'locations.csv')))
{
	echo GetMessage('WSL_LOADER_ERROR_FILES');
}
else
{
	if ($STEP == 1 && (strlen($CSVFILE) <= 0 || $CSVFILE == 'locations.csv')) 
	{
		if ($LOADZIP == 'Y') $STEP = 2;
		else $STEP = 3;
	}

	switch($STEP)
	{
		case 0:
			echo GetMessage('WSL_LOADER_LOADING');
			echo "<script>Run(1)</script>";
		break;

		case 1:
			$file_url = DLPATH.$CSVFILE;
			
			$data = QueryGetData(
				DLSERVER, 
				DLPORT,
				$file_url,
				'',
				$error_number = 0,
				$error_text = "",
				DLMETHOD
			);
			
			if (strlen($data) > 0)
			{
				CheckDirPath($_SERVER['DOCUMENT_ROOT'].UPPATH);
				$fp = fopen($_SERVER['DOCUMENT_ROOT'].UPPATH.$CSVFILE, 'w');
				fwrite($fp, $APPLICATION->ConvertCharset($data, 'windows-1251', LANG_CHARSET));
				fclose($fp);

				echo GetMessage('WSL_LOADER_FILE_LOADED').' '.$CSVFILE;
				echo '<script>Run('.($LOADZIP == "Y" ? 2 : 3).')</script>';
			}
			else
			{
				echo GetMessage('WSL_LOADER_FILE_ERROR').' '.$CSVFILE;
				echo '<script>RunError()</script>';
			}
		
		break;
		
		case 2:
			$file_url = DLPATH.DLZIPFILE;
			
			$data = QueryGetData(
				DLSERVER, 
				DLPORT,
				$file_url,
				'',
				$error_number = 0,
				$error_text = "",
				DLMETHOD
			);
			
			if (strlen($data) > 0)
			{
				CheckDirPath($_SERVER['DOCUMENT_ROOT'].UPPATH);
				$fp = fopen($_SERVER['DOCUMENT_ROOT'].UPPATH.DLZIPFILE, 'w');
				fwrite($fp, $APPLICATION->ConvertCharset($data, 'windows-1251', LANG_CHARSET));
				fclose($fp);

				echo GetMessage('WSL_LOADER_FILE_LOADED').' '.DLZIPFILE;
				echo '<script>Run(3)</script>';
			}
			else
			{
				echo GetMessage('WSL_LOADER_FILE_ERROR').' '.DLZIPFILE;
				echo '<script>RunError()</script>';
			}
			
		break;

		case 3:
			echo GetMessage('WSL_LOADER_ALL_LOADED');
			echo '<script>EnableButton();</script>';
		break;
	}
}

require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
?>