<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/av.ibprops/prolog.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/iblock.php");
IncludeModuleLangFile(__FILE__);

$rsIBlocks = CIBlock::GetList(array("IBLOCK_TYPE" => "ASC", "NAME" => "ASC"), array("MIN_PERMISSION" => "W"));
if(!$rsIBlocks->Fetch())
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
?>
<?$APPLICATION->SetTitle(GetMessage("IBPROPS_PAGE_TITLE"));
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
CUtil::InitJSCore(array('jquery'));
?>
<style>
h3.heading { background-color: #E0E4F1; color: #525355; font-weight: bold; padding:3px 10px; margin-top:0; }
.av-border { border: 1px solid #E0E4F1; padding: 0 0 10px 0; margin-bottom:25px; overflow:hidden; }
.av-border table, .av-border .cb { margin-left:20px; }
.av-border td { vertical-align:top; }
.cb input[type="checkbox"], .cb label { vertical-align: middle; }
.inp-text { width:300px; }
</style>
<div id="tbl_ibprops_result_div"></div>
<?
$aTabs = array(
	array(
		"DIV" => "edit1",
		"TAB" => GetMessage("IBPROPS_TAB"),
		"ICON" => "main_user_edit",
		"TITLE" => GetMessage("IBPROPS_TAB_TITLE"),
	),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs, true, true);
?>

<script>
var running = false;

function GetFields()
{
	$("#tbl_ibprops_result_div").empty();
	$("#av_note").empty();
	var queryString = '<?echo bitrix_sessid_get()?>';
	queryString+='&IBLOCK_ID='+jsUtils.urlencode(document.getElementById('IBLOCK_ID').value);
	ShowWaitWindow();
	BX.ajax.post(
		'/bitrix/tools/av.ibprops/ajax_form.php?'+queryString,
		false,
		function(result){
			document.getElementById('tbl_ibprops_rules_div').innerHTML = result;
			CloseWaitWindow();
		}
	);
}

function DoNext(PARAMS)
{
	var queryString = '';

	if(!PARAMS)
	{
		queryString = $("#form1").serialize();
		queryString += '&firststart=Y';
	}
	queryString += '&<?echo bitrix_sessid_get()?>';

	if(running)
	{
		ShowWaitWindow();
		BX.ajax.post(
			'/bitrix/tools/av.ibprops/ajax_work.php?'+queryString,
			PARAMS,
			function(result){
				document.getElementById('tbl_ibprops_result_div').innerHTML = result;
				CloseWaitWindow();
			}
		);
	}
}

function Start()
{
	$("#av_note").empty();
	running = document.getElementById('start_button').disabled = true;
	DoNext();
}
function End()
{
	running = document.getElementById('start_button').disabled = false;
}
</script>

<form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?lang=<?echo htmlspecialchars(LANG)?>" name="form1" id="form1">
<?
$tabControl->Begin();
$tabControl->BeginNextTab();
?>
	<tr valign="top">
		<td><?echo GetMessage("IBPROPS_IBLOCK_ID")?>:</td>
		<td>
			<?echo GetIBlockDropDownList($IBLOCK_ID, 'IBLOCK_TYPE_ID', 'IBLOCK_ID');?>
			<input type="button" id="get_fields" value="OK" OnClick="GetFields();">
		</td>
	</tr>
	<tr valign="top">
		<td><?echo GetMessage("IBPROPS_INTERVAL")?>:</td>
		<td>
			<input type="text" name="interval" id="interval" value="25" />
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<div id="tbl_ibprops_rules_div"></div>
		</td>
	</tr>
<?$tabControl->Buttons();?>
	<input type="button" id="start_button" value="<?echo GetMessage("IBPROPS_START")?>" OnClick="Start();">
	<input type="button" id="stop_button" value="<?echo GetMessage("IBPROPS_STOP")?>" OnClick="End();">
<?$tabControl->End();?>
</form>
<div id="av_note">
<?CAdminMessage::ShowMessage(GetMessage("NOTE"));?>
</div>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>
