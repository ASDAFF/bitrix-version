<?php
if (!defined("STOP_STATISTICS"))
	define("STOP_STATISTICS", true);
if (!defined("NO_AGENT_STATISTIC"))
	define("NO_AGENT_STATISTIC","Y");
if (!defined("NO_AGENT_CHECK"))
	define("NO_AGENT_CHECK", true);
if (!defined("NO_KEEP_STATISTIC"))
	define("NO_KEEP_STATISTIC", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");


$cid = trim($_REQUEST['cid']);
use \Bitrix\Main\UI\FileInputUtility;
use \Bitrix\Main\Security\Sign\Signer;
/**
 * Bitrix vars
 *
 * @global CMain $APPLICATION
 */
if ($cid && preg_match('/^[a-f01-9]{32}$/', $cid) && check_bitrix_sessid())
{
	$fid = intval($_GET["fileID"]);

	if ($fid > 0 && FileInputUtility::instance()->checkFile($cid, $fid))
	{
		$arFile = \CFile::GetFileArray($fid);
		if ($arFile)
		{
			$APPLICATION->RestartBuffer();
			while(ob_end_clean()); // hack!

			$useContentType = false;
			if (!empty($_REQUEST["s"]))
			{
				$sign = new Signer;
				$useContentType = (($res = $sign->unsign($_REQUEST["s"], "main.file.input")) && $res == $cid);
			}
			if ($useContentType)
				CFile::ViewByUser($arFile, array("content_type" => $arFile["CONTENT_TYPE"]));
			else
				CFile::ViewByUser($arFile, array("force_download" => true));
		}
	}
}

die();
?>