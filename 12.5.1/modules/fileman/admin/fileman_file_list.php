<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/fileman/prolog.php");

if (!$USER->CanDoOperation('fileman_view_file_structure'))
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/fileman/include.php");

$site = CFileMan::__CheckSite($site);
$DOC_ROOT = CSite::GetSiteDocRoot($site);

$strWarning="";
$path = Rel2Abs("/", $path);
$arParsedPath = CFileMan::ParsePath($path);
$arPath = Array($site, $path);

if($type == "flash")
	$ext = "swf,fla";
elseif($type == "image")
	$ext = "gif,jpg,jpeg,bmp,png";
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");

if(!$USER->CanDoFileOperation('fm_download_file', $arPath) || HasScriptExtension($path)):
	ShowError($arParsedPath["HTML"].'<br><br><img src="/bitrix/images/fileman/deny.gif" width="28" height="28" border="0" align="left" alt="">'.GetMessage("ACCESS_DENIED"));
else:
	CFileMan::GetDirList(Array($site, $path), $arDirs, $arFiles, Array("EXTENSIONS"=>$ext, "MIN_PERMISSION"=>"R"), Array("name"=>"asc"));
?>
<script>
<!--
function DoEvent(str)
{
	try
	{
		eval("parent."+this.name+"_"+str);
	}
	catch(e){}
}

DoEvent("OnLoad('<?=AddSlashes(htmlspecialcharsex($path));?>')");

function OpenFile(fileencode, path)
{
	DoEvent("OnFileSelect('" + path + '/' + fileencode + "')");
}

function okfilename_OnClick()
{
	fileencode = document.fform.actfile_name.value;
	OpenFile(fileencode, '<?=AddSlashes(htmlspecialcharsex($path))?>');
}
//-->
</script>
<?
$arDirContent = array_merge($arDirs, $arFiles);
$db_DirContent = new CDBResult;
$db_DirContent->InitFromArray($arDirContent);
$db_DirContent->sSessInitAdd = $path;
$db_DirContent->NavStart(50);
if($db_DirContent->IsNavPrint())
{
	$db_DirContent->NavPrint(GetMessage("FILEMAN_T_FILES"));
	echo "<br>&nbsp;";
}
?>
<table width="100%" border="0" cellpadding="1" cellspacing="1">
	<tr>
		<td class="tablehead1" align="center"><font class="tableheadtext"><?=GetMessage('FILEMAN_FILE_NAME')?></font></td>
		<td class="tablehead2" align="center"><font class="tableheadtext"><?=GetMessage('FILEMAN_FILE_SIZE')?></font></td>
		<td class="tablehead3" align="center"><font class="tableheadtext"><?=GetMessage('FILEMAN_FILE_TIMESTAMP')?></font></td>
	</tr>
	<?if(strlen($path) > 0):?>
	<tr>
		<td class="tablebody1" align="left">
			<table cellpadding="0" cellspacing="0" border="0">
			<tr>
				<td align="left"><font class="tablebodytext"><a href="fileman_file_list.php?lang=<?=LANG?>&site=<?=Urlencode($site)?>&path=<?=UrlEncode($arParsedPath["PREV"])?>&type=<?=urlencode($type)?>"><IMG SRC="/bitrix/images/fileman/folder.gif" WIDTH="17" HEIGHT="15" BORDER=0 ALT=""></a></font></td>
				<td align="left" nowrap><font class="tablebodytext"><a href="fileman_file_list.php?lang=<?=LANG?>&site=<?=Urlencode($site)?>&path=<?=UrlEncode($arParsedPath["PREV"])?>&type=<?=urlencode($type)?>">..</a></font></td>
			</tr>
			</table>
		</td>
		<td class="tablebody2" align="left">&nbsp;</td>
		<td class="tablebody3" align="left">&nbsp;</td>
	</tr>
	<?endif;?>
	<?
	$i=0;
	while($arDirElement = $db_DirContent->Fetch()):
		$i++;
		if($arDirElement["TYPE"]=="D"):
			$Dir = $arDirElement;
	?>
	<tr valign="top">
		<td class="tablebody1">
			<table cellpadding="0" cellspacing="0" border="0">
			<tr>
				<td align="left"><font class="tablebodytext"><a href="fileman_file_list.php?lang=<?=LANG?>&site=<?=Urlencode($site)?>&path=<?=UrlEncode(htmlspecialcharsex($path)."/".$Dir["NAME"])?>&type=<?=urlencode($type)?>"><IMG SRC="/bitrix/images/fileman/folder.gif" WIDTH="17" HEIGHT="15" BORDER=0 ALT=""></a></font></td>
				<td align="left" nowrap>
					<font class="tablebodytext"><a href="fileman_file_list.php?lang=<?=LANG?>&site=<?=Urlencode($site)?>&path=<?=UrlEncode(htmlspecialcharsex($path."/".$Dir["NAME"]))?>&type=<?=urlencode($type)?>"><?=htmlspecialcharsex($Dir["NAME"])?></a></font>
				</td>
			</tr>
			</table>
		</td>
		<td align="right" class="tablebody2" nowrap>
			<font class="tablebodytext">&nbsp;</font>
		</td>
		<td align="center" class="tablebody3" nowrap>
			<font class="tablebodytext"><?=htmlspecialcharsex($Dir["DATE"])?></font>
		</td>
	</tr>
	<?
	else:
		$File = $arDirElement;
	?>
	<tr valign="top">
		<td class="tablebody1">
			<table cellpadding="0" cellspacing="0" border="0">
			<tr>
				<td align="left"><a onclick="OpenFile('<?=AddSlashes(htmlspecialcharsex($File["NAME"]))?>','<?=AddSlashes(htmlspecialcharsex($path))?>'); return false;" href="javascript:void(0)"><IMG SRC="/bitrix/images/fileman/file.gif" width="15" height="18"></a></td>
				<td align="left"><font class="tablebodytext"><a onclick="OpenFile('<?=AddSlashes(htmlspecialcharsex($File["NAME"]))?>','<?=AddSlashes(htmlspecialcharsex($path))?>'); return false;" href="javascript:void(0)"><?=htmlspecialcharsex($File["NAME"])?></a></font></td>
			</tr>
			</table>
		</td>
		<td align="right" class="tablebody2" nowrap>
			<font class="tablebodytext"><?=htmlspecialcharsex($File["SIZE"])?></font>
		</td>
		<td align="center" class="tablebody3" nowrap>
			<font class="tablebodytext"><?=htmlspecialcharsex($File["DATE"])?></font>
		</td>
	</tr>
	<?endif?>
	<?endwhile;?>
</table>
<?endif?>
<?
if($db_DirContent->IsNavPrint())
{
	echo "<br>&nbsp;";
	$db_DirContent->NavPrint(GetMessage("FILEMAN_T_FILES"));
}
?>
</body>
</html>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php")
?>
