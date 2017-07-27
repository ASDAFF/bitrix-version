<?
define("ADMIN_MODULE_NAME", "sender");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

if(!\Bitrix\Main\Loader::includeModule("sender"))
	ShowError(\Bitrix\Main\Localization\Loc::getMessage("MAIN_MODULE_NOT_INSTALLED"));

IncludeModuleLangFile(__FILE__);

$POST_RIGHT = $APPLICATION->GetGroupRight("sender");
if($POST_RIGHT=="D")
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$sTableID = "tbl_sender_posting_recipient";
$oSort = new CAdminSorting($sTableID, "ID", "desc");
$lAdmin = new CAdminList($sTableID, $oSort);

$MAILING_ID = intval($_REQUEST['MAILING_ID']);
$ID = intval($_REQUEST['ID']);
if($find_mailing_chain_id>0)
	$ID = $find_mailing_chain_id;

if($find_click_show_url != 'Y')
	$find_click_show_url = 'N';

$showClickUrl = $find_click_show_url;;

if($ID <= 0)
{
	$postingDb = \Bitrix\Sender\PostingTable::getList(array(
		'select' => array('MAILING_CHAIN_ID'),
		'filter' => array('MAILING_ID' => $MAILING_ID),
		'order' => array('DATE_SENT' => 'DESC', 'DATE_CREATE' => 'DESC'),
	));
	$arPosting = $postingDb->fetch();
	if($arPosting)
	{
		$ID = intval($arPosting['MAILING_CHAIN_ID']);
	}
}


function CheckFilter()
{
	global $FilterArr, $lAdmin;
	foreach ($FilterArr as $f) global $$f;

	return count($lAdmin->arFilterErrors)==0;
}

$FilterArr = Array(
	"find_email",
	"find_name",
	"find_mailing",
	"find_mailing_chain_id",
	"find_sent",
	"find_read",
	"find_click",
	"find_unsub",
);

$lAdmin->InitFilter($FilterArr);

if (CheckFilter() || $ID>0)
{
	$arFilter = Array(
		"%NAME" => $find_name,
		"%EMAIL" => $find_email,
		"=POSTING.MAILING_ID" => $MAILING_ID,
		"=POSTING.MAILING_CHAIN_ID" => $ID,
	);

	foreach($arFilter as $k => $v) if(empty($v)) unset($arFilter[$k]);


	if($find_sent=='Y')
		$arFilter["=STATUS"] = \Bitrix\Sender\PostingRecipientTable::SEND_RESULT_SUCCESS;
	elseif($find_sent=='N')
		$arFilter["=STATUS"] = \Bitrix\Sender\PostingRecipientTable::SEND_RESULT_NONE;
	elseif($find_sent=='E')
		$arFilter["=STATUS"] = \Bitrix\Sender\PostingRecipientTable::SEND_RESULT_ERROR;

	if($find_read=='Y')
		$arFilter[">READ_CNT"] = 0;
	elseif($find_read=='N')
		$arFilter["=READ_CNT"] = 0;

	if($find_click_show_url == 'Y')
	{
		if($find_click=='Y')
			$arFilter["!CLICK_CNT"] = null;
		elseif($find_click=='N')
		{
			$arFilter["=CLICK_CNT"] = 0;
			$showClickUrl = 'N';
		}
	}
	else
	{
		if($find_click=='Y')
			$arFilter[">CLICK_CNT"] = 0;
		elseif($find_click=='N')
			$arFilter["=CLICK_CNT"] = 0;
	}

	if($find_unsub=='Y')
		$arFilter[">UNSUB_CNT"] = 0;
	elseif($find_unsub=='N')
		$arFilter["=UNSUB_CNT"] = 0;
}

if(isset($order)) $order = ($order=='asc'?'ASC': 'DESC');

$arSelect = array('NAME', 'EMAIL', 'READ_CNT', 'CLICK_CNT', 'UNSUB_CNT');
$arRuntime = array(
	new \Bitrix\Main\Entity\ExpressionField('READ_CNT', 'COUNT(%s)', 'POSTING_READ.ID'),
	new \Bitrix\Main\Entity\ExpressionField('UNSUB_CNT', 'COUNT(%s)', 'POSTING_UNSUB.ID')
);

if($showClickUrl == 'Y')
	$arRuntime[] = new \Bitrix\Main\Entity\ExpressionField('CLICK_CNT', '%s', 'POSTING_CLICK.URL');
else
	$arRuntime[] = new \Bitrix\Main\Entity\ExpressionField('CLICK_CNT', 'COUNT(%s)', 'POSTING_CLICK.ID');

$groupListDb = \Bitrix\Sender\PostingRecipientTable::getList(array(
	'select' => $arSelect,
	'filter' => $arFilter,
	'runtime' => $arRuntime,
	'order' => array($by=>$order)
));

$aContext = array();
$rsData = new CAdminResult($groupListDb, $sTableID);
$rsData->NavStart();
$lAdmin->NavText($rsData->GetNavPrint(GetMessage("rub_nav")));

$lAdmin->AddHeaders(array(
	array(	"id"		=>"NAME",
		"content"	=>GetMessage("rub_name"),
		"sort"		=>"NAME",
		"default"	=>true,
	),
	array(	"id"		=>"EMAIL",
		"content"	=>GetMessage("rub_email"),
		"sort"		=>"EMAIL",
		"default"	=>true,
	),
	array(	"id"		=>"READ_CNT",
		"content"	=>GetMessage("rub_f_read"),
		"sort"		=>"READ_CNT",
		"default"	=>true,
	),
	array(	"id"		=>"CLICK_CNT",
		"content"	=>GetMessage("rub_f_click"),
		"sort"		=>"CLICK_CNT",
		"default"	=>true,
	),
	array(	"id"		=>"UNSUB_CNT",
		"content"	=>GetMessage("rub_f_unsub"),
		"sort"		=>"UNSUB_CNT",
		"default"	=>true,
	),
));

while($arRes = $rsData->NavNext(true, "f_")):
	$row =& $lAdmin->AddRow(false, $arRes);
	$row->AddViewField("NAME", $f_NAME);
	$row->AddViewField("EMAIL", $f_EMAIL);
	$row->AddViewField("READ_CNT", ($f_READ_CNT>0?GetMessage('POST_U_YES').' ('.$f_READ_CNT.')':GetMessage('POST_U_NO')));
	if($showClickUrl == 'Y')
		$row->AddViewField("CLICK_CNT", $f_CLICK_CNT);
	else
		$row->AddViewField("CLICK_CNT", ($f_CLICK_CNT>0?GetMessage('POST_U_YES').' ('.$f_CLICK_CNT.')':GetMessage('POST_U_NO')));

	$row->AddViewField("UNSUB_CNT", ($f_UNSUB_CNT>0?GetMessage('POST_U_YES'):GetMessage('POST_U_NO')));
endwhile;

$lAdmin->AddFooter(
	array(
		array("title"=>GetMessage("MAIN_ADMIN_LIST_SELECTED"), "value"=>$rsData->SelectedRowsCount()),
		array("counter"=>true, "title"=>GetMessage("MAIN_ADMIN_LIST_CHECKED"), "value"=>"0"),
	)
);

$lAdmin->AddAdminContextMenu($aContext);
$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("rub_title"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$oFilter = new CAdminFilter(
	$sTableID."_filter",
	array(
		($MAILING_ID > 0 ? GetMessage("rub_f_mailing_chain") : null),
		GetMessage("rub_f_email"),
		GetMessage("rub_f_name"),
		GetMessage("rub_f_sent"),
		GetMessage("rub_f_read"),
		GetMessage("rub_f_click"),
		GetMessage("rub_f_unsub"),
	)
);
?>
<form name="find_form" method="get" action="<?echo $APPLICATION->GetCurPage();?>">
<?$oFilter->Begin();?>
	<?if($MAILING_ID > 0):?>
		<tr>
			<td><?=GetMessage("rub_f_mailing_chain")?>:</td>
			<td valign="middle">
				<?
				$arr = array();
				$mailingChainDb = \Bitrix\Sender\MailingChainTable::getList(array(
					'select' => array('REFERENCE'=>'SUBJECT','REFERENCE_ID'=>'ID'),
					'filter' => array('MAILING_ID' => $MAILING_ID)
				));
				while($arMailingChain = $mailingChainDb->fetch())
				{
					$arr['reference'][] = $arMailingChain['REFERENCE'];
					$arr['reference_id'][] = $arMailingChain['REFERENCE_ID'];
				}
				echo SelectBoxFromArray("find_mailing_chain_id", $arr, $ID, false, "");
				?>
			</td>
		</tr>
	<?endif;?>
	<tr>
		<td><?=GetMessage("rub_f_email")?>:</td>
		<td>
			<input type="text" name="find_email" size="47" value="<?echo htmlspecialcharsbx($find_email)?>">
		</td>
	</tr>
<tr>
	<td><?=GetMessage("rub_f_name")?>:</td>
	<td>
		<input type="text" name="find_name" size="47" value="<?echo htmlspecialcharsbx($find_name)?>">
</td>
</tr>

<tr>
	<td><?=GetMessage("rub_f_sent")?>:</td>
	<td>
		<?
		$arRecipientStatus = \Bitrix\Sender\PostingRecipientTable::getStatusList();
		$arr = array(
			"reference" => array_values($arRecipientStatus),
			"reference_id" => array_keys($arRecipientStatus)
		);
		echo SelectBoxFromArray("find_sent", $arr, $find_sent, GetMessage("MAIN_ALL"), "");
		?>
	</td>
</tr>
<tr>
	<td><?=GetMessage("rub_f_read")?>:</td>
	<td>
		<?
		$arr = array(
			"reference" => array(
				GetMessage("MAIN_YES"),
				GetMessage("MAIN_NO"),
			),
			"reference_id" => array(
				"Y",
				"N",
			)
		);
		echo SelectBoxFromArray("find_read", $arr, $find_read, GetMessage("MAIN_ALL"), "");
		?>
	</td>
</tr>
<tr>
	<td><?=GetMessage("rub_f_click")?>:</td>
	<td>
		<?
		$arr = array(
			"reference" => array(
				GetMessage("MAIN_YES"),
				GetMessage("MAIN_NO"),
			),
			"reference_id" => array(
				"Y",
				"N",
			)
		);
		echo SelectBoxFromArray("find_click", $arr, $find_click, GetMessage("MAIN_ALL"), "");
		?>
		<input type="checkbox" name="find_click_show_url" value="Y" <?=($find_click_show_url=='Y'?'checked':'')?>>
		<label for="find_click_show_url"><?=GetMessage("rub_f_click_show_url")?></label>
	</td>
</tr>
<tr>
	<td><?=GetMessage("rub_f_unsub")?>:</td>
	<td>
		<?
		$arr = array(
			"reference" => array(
				GetMessage("MAIN_YES"),
				GetMessage("MAIN_NO"),
			),
			"reference_id" => array(
				"Y",
				"N",
			)
		);
		echo SelectBoxFromArray("find_unsub", $arr, $find_unsub, GetMessage("MAIN_ALL"), "");
		?>
	</td>
</tr>

<?
$oFilter->Buttons(array("table_id"=>$sTableID,"url"=>$APPLICATION->GetCurPage()."?MAILING_ID=".$MAILING_ID,"form"=>"find_form"));
$oFilter->End();
?>
</form>

<?$lAdmin->DisplayList();?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>