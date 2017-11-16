<?
/*
##############################################
# Bitrix: SiteManager                        #
# Copyright (c) 2004 Bitrix                  #
# http://www.bitrix.ru                       #
# mailto:admin@bitrix.ru                     #
##############################################
*/
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/advertising/prolog.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/advertising/include.php");

ClearVars();

$isDemo = CAdvContract::IsDemo();
$isManager = CAdvContract::IsManager();
$isAdvertiser = CAdvContract::IsAdvertiser();
$isAdmin = CAdvContract::IsAdmin();

if(!$isAdmin && !$isDemo && !$isManager && !$isAdvertiser) $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

IncludeModuleLangFile(__FILE__);
$err_mess = "FILE: ".__FILE__."<br>LINE: ";
define("HELP_FILE","adv_banner_list.php");

$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("AD_TAB_BANNER"), "ICON"=>"ad_banner_edit", "TITLE"=> GetMessage("AD_TAB_TITLE_BANNER")),
	array("DIV" => "edit2", "TAB" => GetMessage("AD_TAB_LIMIT"), "ICON"=>"ad_banner_edit", "TITLE"=> GetMessage("AD_WHEN")),
	array("DIV" => "edit3", "TAB" => GetMessage("AD_TAB_TARGET"), "ICON"=>"ad_banner_edit", "TITLE"=> GetMessage("AD_WHERE")));
if ($isAdmin || ($isDemo && !$isOwner))
	$aTabs[] = array("DIV" => "edit4", "TAB" => GetMessage("AD_TAB_STAT"), "ICON"=>"ad_banner_edit", "TITLE"=> GetMessage("AD_STAT"));
$aTabs[] = array("DIV" => "edit5", "TAB" => GetMessage("AD_TAB_COMMENT"), "ICON"=>"ad_banner_edit", "TITLE"=> GetMessage("AD_COMMENTS"));

$tabControl = new CAdminTabControl("tabControl", $aTabs);
/***************************************************************************
						Обработка GET | POST
****************************************************************************/

$strError = '';
$ID = intval($ID);
$bCopy = ($action == "copy");
$CONTRACT_ID = intval($CONTRACT_ID);
$isEditMode = true;
if ($ID>0 && $CONTRACT_ID<=0)
{
	$rsBanner = CAdvBanner::GetByID($ID);
	if($arBanner = $rsBanner->Fetch())
		$CONTRACT_ID = $arBanner["CONTRACT_ID"];
}
if($CONTRACT_ID<=0)
	$CONTRACT_ID=1;

$rsContract = CAdvContract::GetByID($CONTRACT_ID, "N");
if (!$rsContract || !$arContract = $rsContract->Fetch())
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
	CAdminMessage::ShowMessage(GetMessage("AD_ERROR_INCORRECT_CONTRACT_ID"));
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
	die();
}
else
{
	$arrPERM = CAdvContract::GetUserPermissions($CONTRACT_ID);
	$arrPERM = is_array($arrPERM[$CONTRACT_ID]) ? $arrPERM[$CONTRACT_ID] : array();
	if (!$isDemo)
	{
		if (count($arrPERM)<=0) 
			$APPLICATION->AuthForm(GetMessage("AD_ERROR_NOT_ENOUGH_PERMISSIONS_CONTRACT"));
		if (!in_array("ADD", $arrPERM)) 
			$isEditMode = false;
	}
	if ($action=="view") 
		$isEditMode = false;

	$arrCONTRACT_TYPE = CAdvContract::GetTypeArray($CONTRACT_ID);
	$isOwner = CAdvContract::IsOwner($CONTRACT_ID);
}

if ((strlen($save)>0 || strlen($apply)>0) && $REQUEST_METHOD=="POST" && check_bitrix_sessid())
{
	InitBVar($SEND_EMAIL);
	$arrIMAGE_ID = $HTTP_POST_FILES["IMAGE_ID"];
	$arrIMAGE["MODULE_ID"] = "advertising";
	$arrIMAGE_ID["del"] = ${"IMAGE_ID_del"};
	$arrFlashIMAGE_ID = $HTTP_POST_FILES["FLASH_IMAGE"];
	$arrFlashIMAGE["MODULE_ID"] = "advertising";
	$arrFlashIMAGE_ID["del"] = ${"FLASH_IMAGE_del"};
	$arrWEEKDAY = array(
		"SUNDAY"	=> $arrSUNDAY,
		"MONDAY"	=> $arrMONDAY,
		"TUESDAY"	=> $arrTUESDAY,
		"WEDNESDAY"	=> $arrWEDNESDAY,
		"THURSDAY"	=> $arrTHURSDAY,
		"FRIDAY"	=> $arrFRIDAY,
		"SATURDAY"	=> $arrSATURDAY
		);
	if (!$isEditMode && ($isManager || $isAdmin))
	{
		$arFields = array(
			"STATUS_SID"		=> $STATUS_SID,
			"STATUS_COMMENTS"	=> $STATUS_COMMENTS
			);
	}
	else
	{
		InitBVar($ACTIVE);
		InitBVar($FIX_CLICK);
		InitBVar($FIX_SHOW);

		$arFields = array(
			"CONTRACT_ID"			=> $CONTRACT_ID,
			"TYPE_SID"			=> $TYPE_SID,
			"STATUS_SID"			=> $STATUS_SID,
			"STATUS_COMMENTS"		=> $STATUS_COMMENTS,
			"NAME"				=> $NAME,
			"GROUP_SID"			=> $GROUP_SID,
			"ACTIVE"				=> ($ACTIVE=="Y" ? "Y" : "N"),
			"WEIGHT"				=> $WEIGHT,
			"MAX_VISITOR_COUNT"		=> $MAX_VISITOR_COUNT,
			"RESET_VISITOR_COUNT"	=> $RESET_VISITOR_COUNT,
			"SHOWS_FOR_VISITOR"		=> $SHOWS_FOR_VISITOR,
			"MAX_SHOW_COUNT"		=> $MAX_SHOW_COUNT,
			"RESET_SHOW_COUNT"		=> $RESET_SHOW_COUNT,
			"FIX_CLICK"			=> $FIX_CLICK,
			"FIX_SHOW"		=> $FIX_SHOW,
			"FLYUNIFORM"	=> ($FLYUNIFORM=="Y" ? "Y" : "N"),
			"MAX_CLICK_COUNT"		=> $MAX_CLICK_COUNT,
			"RESET_CLICK_COUNT"		=> $RESET_CLICK_COUNT,
			"DATE_SHOW_FROM"		=> $DATE_SHOW_FROM,
			"DATE_SHOW_TO"			=> $DATE_SHOW_TO,
			"arrIMAGE_ID"			=> $arrIMAGE_ID,
			"IMAGE_ALT"			=> $IMAGE_ALT,
			"URL"				=> $_POST["URL"],
			"URL_TARGET"			=> $URL_TARGET,
			"NO_URL_IN_FLASH"		=> ($NO_URL_IN_FLASH=="Y"? "Y" : "N"),
			"CODE"				=> $CODE,
			"CODE_TYPE"			=> $CODE_TYPE,
			"STAT_EVENT_1"			=> $STAT_EVENT_1,
			"STAT_EVENT_2"			=> $STAT_EVENT_2,
			"STAT_EVENT_3"			=> $STAT_EVENT_3,
			"FOR_NEW_GUEST"		=> $FOR_NEW_GUEST,
			"COMMENTS"			=> $COMMENTS,
			"SHOW_USER_GROUP"		=> $SHOW_USER_GROUP,
			"arrSHOW_PAGE"			=> preg_split('/[\n\r]+/', $SHOW_PAGE),
			"arrNOT_SHOW_PAGE"		=> preg_split('/[\n\r]+/', $NOT_SHOW_PAGE),
			"arrSTAT_ADV"			=> $arrSTAT_ADV,
			"arrWEEKDAY"			=> $arrWEEKDAY,
			"arrSITE"				=> $arrSITE,
			"arrUSERGROUP"			=> $arrUSERGROUP,
			"KEYWORDS"			=> $KEYWORDS,
			"SEND_EMAIL"			=> $SEND_EMAIL,
			"AD_TYPE"				=> $AD_TYPE,
			"FLASH_TRANSPARENT" => $FLASH_TRANSPARENT,
			"arrFlashIMAGE_ID" => $arrFlashIMAGE_ID,
			"FLASH_JS" => ($FLASH_JS=="Y" ? "Y" : "N"),
			"FLASH_VER" => $FLASH_VER,
		);

		$arFields["arrCOUNTRY"] = array();
		if($_POST["STAT_TYPE"] === "CITY")
		{
			$arFields["STAT_TYPE"] = "CITY";
			$arrCITY = explode(",", $_POST["ALL_STAT_TYPE_VALUES"]);
			$arFilter = array();
			foreach($arrCITY as $CITY_ID)
					$arFilter[] = intval($CITY_ID);
			if(count($arFilter) > 0)
			{
				$rs = CCity::GetList("CITY", array("=CITY_ID" => $arFilter));
				while($ar = $rs->GetNext())
					$arFields["arrCOUNTRY"][] = array(
						"COUNTRY_ID" => $ar["COUNTRY_ID"],
						"REGION" => $ar["REGION_NAME"],
						"CITY_ID" => $ar["CITY_ID"],
					);
			}
		}
		elseif($_POST["STAT_TYPE"] === "REGION")
		{
			$arFields["STAT_TYPE"] = "REGION";
			$arrREGION = explode(",", $_POST["ALL_STAT_TYPE_VALUES"]);
			foreach($arrREGION as $reg)
			{
				$ar = explode("|", $reg, 2);
					$arFields["arrCOUNTRY"][] = array(
						"COUNTRY_ID" => $ar[0],
						"REGION" => $ar[1],
						"CITY_ID" => false,
					);
			}
		}
		else
		{
			$arFields["STAT_TYPE"] = "COUNTRY";
			$arFields["arrCOUNTRY"] = explode(",", $_POST["ALL_STAT_TYPE_VALUES"]);
		}

		if (!$arBanner and $ID>0)
		{
			$rsBanner = CAdvBanner::GetByID($ID);
			if($arBanner = $rsBanner->Fetch())
			{
				if ($DATE_SHOW_FROM != $arBanner["DATE_SHOW_FROM"] or
					$DATE_SHOW_TO != $arBanner["DATE_SHOW_TO"] or
					$RESET_SHOW_COUNT == "Y")
				{
					$arFields["DATE_SHOW_FIRST"] = "null";
				}
			}
		}
	}

	if ($ID = CAdvBanner::Set($arFields, $ID))
	{
		// test if Set finished secsesfully.
		if (strlen($strError)<=0)
		{
			if (strlen($save)>0)
				LocalRedirect("adv_banner_list.php?lang=".LANGUAGE_ID);
			else
				LocalRedirect("adv_banner_edit.php?ID=".$ID."&CONTRACT_ID=".$CONTRACT_ID."&lang=".LANGUAGE_ID."&action=".$action."&".$tabControl->ActiveTabParam());
		}
	}
	$DB->PrepareFields("b_adv_banner");
}

$arrSites = array();
$rs = CSite::GetList(($by="sort"), ($order="asc"));
while ($ar = $rs->Fetch())
	$arrSites[$ar["ID"]] = $ar;

$rsBanner = CAdvBanner::GetByID($ID);

if (!$rsBanner || !$banner = $rsBanner->ExtractFields())
{
	if (!$isEditMode)
	{
		require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
		CAdminMessage::ShowMessage(GetMessage("AD_ERROR_INCORRECT_BANNER_ID"));
		require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
		die();
	}
	$ID=0;
	$str_AD_TYPE = 'image';
	$str_WEIGHT = 100;
	$str_ACTIVE = "Y";
	$str_FIX_CLICK = "Y";
	$str_FIX_SHOW = "N";
	$str_FLYUNIFORM = "N";
	$str_DATE_SHOW_FROM = $arContract["DATE_SHOW_FROM"];
	$str_DATE_SHOW_TO = $arContract["DATE_SHOW_TO"];
	$str_CODE_TYPE = "html";
	//if ($isAdmin || $isManager) $str_STATUS_SID = "PUBLISHED";
	$str_STAT_EVENT_1 = "banner";
	$str_STAT_EVENT_2 = "click";
	$str_STAT_EVENT_3 = "#CONTRACT_ID# / [#BANNER_ID#] [#TYPE_SID#] #BANNER_NAME#";
	$str_MAX_SHOW_COUNT = $arContract["MAX_SHOW_COUNT"];
	$str_MAX_CLICK_COUNT = $arContract["MAX_CLICK_COUNT"];
	$arrSITE = array_keys($arrSites);
	$str_CONTRACT_ID = $CONTRACT_ID;
	$str_STAT_TYPE = "COUNTRY";
	
	$str_TYPE_SID = isset($TYPE_SID) && strlen($TYPE_SID) > 0 ? $TYPE_SID : "";
}
else
{
	if (strlen($strError)<=0)
	{
		if (strlen($str_KEYWORDS)>0)
		{
			$arrKEYWORDS = preg_split('/[\n\r]+/',$str_KEYWORDS);
			TrimArr($arrKEYWORDS);
		}
		$arrSITE = CAdvBanner::GetSiteArray($ID);
		$arrSHOW_PAGE = CAdvBanner::GetPageArray($ID, "SHOW");
		$str_SHOW_PAGE = implode("\n", $arrSHOW_PAGE);
		$arrNOT_SHOW_PAGE = CAdvBanner::GetPageArray($ID, "NOT_SHOW");
		$str_NOT_SHOW_PAGE = implode("\n", $arrNOT_SHOW_PAGE);
		if($str_STAT_TYPE !== "CITY" && $str_STAT_TYPE != "REGION")
			$str_STAT_TYPE = "COUNTRY";
		$arrSTAT_TYPE_VALUES = CAdvBanner::GetCountryArray($ID, $str_STAT_TYPE);
		$arrWEEKDAY = CAdvBanner::GetWeekdayArray($ID);
		while (list($key, $value)=each($arrWEEKDAY)) ${"arr".$key} = $value;
		$arrSTAT_ADV = CAdvBanner::GetStatAdvArray($ID);
		$arrUSERGROUP = CAdvBanner::GetGroupArray($ID);
	}
}
if (strlen($strError)>0)
{
	$DB->InitTableVarsForEdit("b_adv_banner", "", "str_");
	$str_SHOW_PAGE = htmlspecialcharsbx($SHOW_PAGE);
	$str_NOT_SHOW_PAGE = htmlspecialcharsbx($NOT_SHOW_PAGE);
	$str_IMAGE_ID = 0;
	$str_FLASH_IMAGE = 0;
}
if (strlen($SEND_EMAIL)<=0) $SEND_EMAIL = "Y";

$sDocTitle = ($ID>0 && !$bCopy) ? GetMessage("AD_EDIT_RECORD", array("#ID#" => $ID)) : GetMessage("AD_NEW_RECORD");
$APPLICATION->SetTitle($sDocTitle);

/***************************************************************************
				HTML form
****************************************************************************/
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$aMenu = array(
	array(
		"TEXT"	=> GetMessage("AD_BACK_TO_BANNER_LIST"),
		"TITLE"	=> GetMessage("AD_BACK_TO_BANNER_LIST_TITLE"),
		"LINK"	=> "adv_banner_list.php?lang=".LANGUAGE_ID,
		"ICON"	=> "btn_list"
	)
);

if(intval($ID)>0 && !$bCopy)
{
	$aMenu[] = array("SEPARATOR"=>"Y");

	$aMenu[] = array(
		"TEXT"	=> GetMessage("AD_BANNER_STATISTICS"),
		"TITLE"	=> GetMessage("AD_BANNER_STATISTICS_TITLE"),
		"LINK"	=> "adv_banner_graph.php?find_banner_id[]=".$ID."&find_what_show[]=ctr&set_filter=Y&lang=".LANGUAGE_ID,
		"ICON"	=> "btn_adv_graph",

	);

	if ($isEditMode)
	{
		$aMenu[] = array(
			"TEXT"	=> GetMessage("AD_BANNER_VIEW_SETTINGS"),
			"TITLE"	=> GetMessage("AD_BANNER_VIEW_SETTINGS_TITLE"),
			"LINK"	=> "adv_banner_edit.php?ID=".$ID."&lang=".LANGUAGE_ID."&action=view&CONTRACT_ID=".$CONTRACT_ID,
			"ICON"	=> "btn_adv_view"
		);
	}
	elseif (in_array("ADD", $arrPERM))
	{
		$aMenu[] = array(
			"TEXT"	=> GetMessage("AD_BANNER_EDIT"),
			"TITLE"	=> GetMessage("AD_BANNER_EDIT_TITLE"),
			"LINK"	=> "adv_banner_edit.php?ID=".$ID."&lang=".LANGUAGE_ID."&CONTRACT_ID=".$CONTRACT_ID,
			"ICON"	=> "btn_adv_edit"
		);
	}

	if ($isAdmin || ($isDemo && !$isOwner) || $isManager || ($isAdvertiser && in_array("ADD", $arrPERM)))
	{
		$aMenu[] = Array("NEWBAR"=>"Y");
		$aMenu[] = array(
			"TEXT"	=> GetMessage("AD_ADD_NEW_BANNER"),
			"TITLE"	=> GetMessage("AD_ADD_NEW_BANNER_TITLE"),
			"LINK"	=> "adv_banner_edit.php?lang=".LANGUAGE_ID."&CONTRACT_ID=".$CONTRACT_ID,
			"ICON"	=> "btn_new"
		);
		$aMenu[] = array(
			"TEXT"	=> GetMessage("AD_BANNER_COPY"),
			"TITLE"	=> GetMessage("AD_BANNER_COPY_TITLE"),
			"LINK"	=> "adv_banner_edit.php?ID=".$ID."&lang=".LANGUAGE_ID."&action=copy",
			"ICON"	=> "btn_copy"
		);
		$aMenu[] = array(
			"TEXT"	=> GetMessage("AD_DELETE_BANNER"),
			"TITLE"	=> GetMessage("AD_DELETE_BANNER_TITLE"),
			"LINK"	=> "javascript:if(confirm('".GetMessage("AD_DELETE_BANNER_CONFIRM")."'))window.location='adv_banner_list.php?ID=".$ID."&lang=".LANGUAGE_ID."&sessid=".bitrix_sessid()."&action=delete';",
			"ICON"	=> "btn_delete"
		);
	}
}
$context = new CAdminContextMenu($aMenu);
$context->Show();
?>
<?if(strlen($strError)>0)
	CAdminMessage::ShowMessage(Array("MESSAGE"=>$strError, "HTML" => true, "TYPE" => "ERROR"));?>

<form name="bx_adv_edit_form" method="POST" action="<?=$APPLICATION->GetCurPage()?>" enctype="multipart/form-data">
<?=bitrix_sessid_post()?>
<input type="hidden" name="lang" value="<?=LANGUAGE_ID?>">
<input type="hidden" name="CONTRACT_ID" value="<?=htmlspecialcharsbx($arContract["ID"])?>">
<?if(!$bCopy):?>
	<input type="hidden" name="action" value="<?=htmlspecialcharsbx($action)?>">
	<input type="hidden" name="ID" value="<?=$ID?>">
<?endif;?>

<?
$tabControl->Begin();

$tabControl->BeginNextTab();
?>

	<?
	if ($ID>0) :
	if ($str_LAMP=='green') $lamp_alt = GetMessage("AD_GREEN_ALT");
	if ($str_LAMP=='red') $lamp_alt = GetMessage("AD_RED_ALT");
	$lamp = '<div class="lamp-'.$str_LAMP.'" title="'.$lamp_alt.'" style="float:left;"></div>';
	?>
	<tr valign="top">
		<td width="40%"><?=GetMessage("AD_BANNER_STATUS")?>:</td>
		<td width="60%"><?=$lamp?><?=$lamp_alt?></td>
	</tr>
	<?endif;?>

	<?if ($ID>0):?>
	<?if (strlen($str_DATE_CREATE)>0):?>
	<tr valign="top">
		<td width="40%"><?=GetMessage("AD_CREATED")?></td>
		<td width="60%"><?=$str_DATE_CREATE?><?
		if (intval($str_CREATED_BY)>0) :
			$rsUser = CUser::GetByID($str_CREATED_BY);
			$arUser = $rsUser->Fetch();
			echo "&nbsp;&nbsp;[<a href='/bitrix/admin/user_edit.php?ID=".$str_CREATED_BY."&lang=".LANGUAGE_ID."' title='".GetMessage("AD_USER_ALT")."'>".$str_CREATED_BY."</a>]&nbsp;(".htmlspecialcharsbx($arUser["LOGIN"]).") ".htmlspecialcharsbx($arUser["NAME"])." ".htmlspecialcharsbx($arUser["LAST_NAME"]);
		endif;
		?></td>
	</tr>
	<?endif;?>
	<?if (strlen($str_DATE_MODIFY)>0):?>
	<tr valign="top">
		<td><?=GetMessage("AD_MODIFIED")?></td>
		<td><?=$str_DATE_MODIFY?><?
		if (intval($str_MODIFIED_BY)>0) :
			$rsUser = CUser::GetByID($str_MODIFIED_BY);
			$arUser = $rsUser->Fetch();
			echo "&nbsp;&nbsp;[<a href='/bitrix/admin/user_edit.php?ID=".$str_MODIFIED_BY."&lang=".LANGUAGE_ID."' title='".GetMessage("AD_USER_ALT")."'>".$str_MODIFIED_BY."</a>]&nbsp;(".htmlspecialcharsbx($arUser["LOGIN"]).") ".htmlspecialcharsbx($arUser["NAME"])." ".htmlspecialcharsbx($arUser["LAST_NAME"]);
		endif;
		?></td>
	</tr>
	<?endif;?>
	<?endif;?>

	<tr valign="top">
		<td width="40%"><?=GetMessage("AD_CONTRACT")?></td>
		<td width="60%">[<a title="<?=GetMessage("AD_CONTRACT_SETTINGS")?>" href="adv_contract_edit.php?ID=<?=$arContract["ID"]?>&action=view&lang=<?=LANGUAGE_ID?>"><?=$arContract["ID"]?></a>] <?=htmlspecialcharsbx($arContract["NAME"])?></td>
	</tr>

	<tr>
		<td width="40%"><label for="ACTIVE" ><?=GetMessage("AD_ACTIVE")?></label></td>
		<td width="60%"><?
			if ($isEditMode) :
				echo InputType("checkbox", "ACTIVE", "Y", $str_ACTIVE, false, "", 'id="ACTIVE"');
			else:
				?><?echo ($str_ACTIVE=="Y" ? GetMessage("AD_YES") : GetMessage("AD_NO"))?><?
			endif;
			?></td>
	</tr>
	<tr>
		<td><?=GetMessage("AD_SHOW_INTERVAL").":"?></td>
		<td><?
		if ($isEditMode) :
			echo CalendarPeriod("DATE_SHOW_FROM", $str_DATE_SHOW_FROM, "DATE_SHOW_TO", $str_DATE_SHOW_TO, "bx_adv_edit_form");
		else :
			if (strlen($str_DATE_SHOW_FROM)>0) :
				echo GetMessage("AD_FROM")?>&nbsp;<b><?=$str_DATE_SHOW_FROM?></b>&nbsp;<?
			endif;
			if (strlen($str_DATE_SHOW_TO)>0) :
				echo GetMessage("AD_TILL")?>&nbsp;<b><?=$str_DATE_SHOW_TO?></b><?
			endif;
			if (strlen($str_DATE_SHOW_TO)<=0 && strlen($str_DATE_SHOW_FROM)<=0)
				echo GetMessage("ADV_NOT_SET");
		endif;
		?></td>
	</tr>
	<tr>
		<td><?=GetMessage("AD_NAME")?></td>
		<td><?
			if ($isEditMode) :
				?><input type="text" maxlength="255" name="NAME" size="50" value="<?=$str_NAME?>"><?
			else :
				?><?=$str_NAME?><?
			endif;
			?></td>
	</tr>

	<tr>
		<td><?=GetMessage("AD_GROUP")?></td>
		<td><?
			if ($isEditMode) :

				$ref = array();
				$ref_id = array();
				$rsBann = CAdvBanner::GetList($v1="s_group_sid", $v2="asc", array(), $v3);
				while ($arBann = $rsBann->Fetch())
				{
					if (!in_array($arBann["GROUP_SID"], $ref_id) && strlen($arBann["GROUP_SID"])>0)
					{
						$ref[] = $arBann["GROUP_SID"];
						$ref_id[] = $arBann["GROUP_SID"];
					}
				}
			?>
				<input type="text" maxlength="255" name="GROUP_SID" size="30" value="<?=$str_GROUP_SID?>">&nbsp;<?
				if (count($ref_id)>0) :
					?>
					<script language="javascript">
					<!--
					function SelectGroup()
					{
						var obj;
						obj = document.bx_adv_edit_form.SELECT_GROUP;
						document.bx_adv_edit_form.GROUP_SID.value = obj[obj.selectedIndex].value;
					}
					//-->
					</script>
					<?
					echo SelectBoxFromArray("SELECT_GROUP", array("reference" => $ref, "reference_id" => $ref_id), "", " ", " OnChange = SelectGroup()");
				endif;

			else :
				if(strlen($str_GROUP_SID)>0)
					echo $str_GROUP_SID;
				else
					echo GetMessage("ADV_NOT_SET");
			endif;
			?></td>
	</tr>

	<tr>
		<td><?=GetMessage("AD_TYPE")?><?if ($isEditMode):?><span class="required"><sup>1</sup></span><?endif;?></td>
		<td><?
			if ($isEditMode) :

				$ref = array();
				$ref_id = array();
				$arFilter = array();
				$arrCONTRACT_TYPE_SID = array_keys($arrCONTRACT_TYPE);
				if (!in_array("ALL", $arrCONTRACT_TYPE_SID))
				{
					$arFilter = array(
						"SID"				=> implode(" | ", $arrCONTRACT_TYPE_SID),
						"SID_EXACT_MATCH"	=> "Y"
						);
				}
				$rsTypies = CAdvType::GetList($v1, $v2, $arFilter, $v3);
				while ($arType = $rsTypies->Fetch())
				{
					$ref[] = "[".$arType["SID"]."] ".htmlspecialcharsbx($arType["NAME"]);
					$ref_id[] = $arType["SID"];
				}

				echo SelectBoxFromArray("TYPE_SID", array("reference" => $ref, "reference_id" => $ref_id), $str_TYPE_SID, "");

			else :
				echo "[<a href='adv_type_edit.php?SID=".urlencode($str_TYPE_SID)."&lang=".LANGUAGE_ID."&action=view' title='".GetMessage("ADV_TYPE_VIEW")."'>".htmlspecialcharsbx($str_TYPE_SID)."</a>] ".$str_TYPE_NAME;
			endif;
			?></td>
	</tr>

	<tr>
		<td width="40%"><?=GetMessage("AD_WEIGHT")?></td>
		<td width="60%"><?
		if ($isEditMode) :
			?><input type="text" name="WEIGHT" value="<?echo $str_WEIGHT;?>" size="10"><?
		else :
			echo $str_WEIGHT;
		endif;
		?></td>
	</tr>

<?
/***************************************************************
						Что показывать
***************************************************************/
?>

	<tr class="heading">
		<td colspan="2"><b><?=GetMessage("AD_WHAT")?></b></td>
	</tr>

	<tr<? if (!$isEditMode) echo ' style="display: none;"'; ?> valign="top">
		<td>
			<?=GetMessage("AD_TYPE")?>
		</td>
		<td align="left">
			<input type="radio" onclick="changeType('image');" id="AD_TYPE_IMAGE" name="AD_TYPE" value="image"<?if (((($ID && $str_AD_TYPE=='image')|| !$ID) && !isset($AD_TYPE)) || (isset($AD_TYPE) && ($AD_TYPE == 'image'))): ?> checked="checked"<? endif; ?>>
			<label for="AD_TYPE_IMAGE"><?=GetMessage("ADV_BANNER_IMAGE")?></label><br>
			<input type="radio" onclick="changeType('flash');" id="AD_TYPE_FLASH" name="AD_TYPE" value="flash"<?if (($ID && $str_AD_TYPE=='flash') || (isset($AD_TYPE) && ($AD_TYPE == 'flash'))): ?> checked="checked"<? endif; ?>>
			<label for="AD_TYPE_FLASH"><?=GetMessage("ADV_BANNER_FLASH")?></label><br>
			<input type="radio" onclick="changeType('html');" id="AD_TYPE_HTML" name="AD_TYPE" value="html"<?if (($ID && $str_AD_TYPE=='html') || (isset($AD_TYPE) && ($AD_TYPE == 'html'))): ?> checked="checked"<? endif; ?>>
			<label for="AD_TYPE_HTML"><?=GetMessage("ADV_BANNER_HTML")?></label>
		<script type="text/javascript">
			function SwitchRows(elements, on)
			{
				for(var i=0; i<elements.length; i++)
				{
					var el = document.getElementById(elements[i]);
					if (el)
						el.style.display = (on? '':'none');
				}
			}

			var changeType = function(type)
			{
				if (!type)
					type = 'image';

				if(type == 'image')
				{
					SwitchRows(['eAltImage','eFlashUrl','eFlashFileLoaded','eFlashTrans','eFlashJs','eFlashVer'], false);
					SwitchRows(['eFile','eFileLoaded','eUrl','eImageAlt','eUrlTarget', 'eCodeHeader'], true);
				}
				else if(type == 'flash')
				{
					SwitchRows(['eCodeHeader','eFile','eFileLoaded','eFlashUrl','eFlashJs','eFlashTrans', 'eUrl', 'eUrlTarget', 'eImageAlt'], true);
					SwitchRows(['eAltImage', 'eFlashFileLoaded', 'eFlashVer'], document.getElementById('FLASH_JS').checked);
				}
				else if(type == 'html')
				{
					SwitchRows(['eFlashUrl','eFile','eFileLoaded','eFlashJs','eFlashFileLoaded','eFlashTrans',
						'eAltImage','eImageAlt','eUrl','eUrlTarget','eCodeHeader','eFlashVer'], false);
					SwitchRows(['eCode'], true);
				}
			}
		</script>
		</td>
	</tr>

	<?if ($isEditMode || intval($str_IMAGE_ID)>0):?>
	<?if ($isEditMode):?>
	<tr valign="top" id="eFile" style="display: none;">
		<td><?=GetMessage("ADV_BANNER_FILE")?><span class="required"><sup>1</sup></span></td>
		<td><?echo CFile::InputFile("IMAGE_ID", 25, $str_IMAGE_ID);?>&nbsp;</td>
	</tr>
	<?endif;?>

	<?
	if(intval($str_IMAGE_ID)>0) :
	?>
	<tr valign="top" id="eFileLoaded" style="display: none;">
		<td align="center" colspan="2"><?
echo CAdvBanner_all::GetHTML(array(
	"IMAGE_ID" => $str_IMAGE_ID,
	"FLASH_JS" => $str_FLASH_JS,
	"FLASH_IMAGE" => $str_FLASH_IMAGE,
	"FLASH_TRANSPARENT" => $str_FLASH_TRANSPARENT,
	"FLASH_VER" => $str_FLASH_VER,
));?></td>
	</tr>
	<?endif;?>

	<tr id="eFlashTrans" style="display: none;">
		<td><?=GetMessage('AD_FLASH_TRANSPARENT')?></td>
		<td>
			<select id="FLASH_TRANSPARENT" name="FLASH_TRANSPARENT">
				<option value="transparent"<? if ($str_FLASH_TRANSPARENT == 'transparent'): ?> selected="selected"<? endif; ?>>transparent</option>
				<option value="opaque"<? if ($str_FLASH_TRANSPARENT == 'opaque'): ?> selected="selected"<? endif; ?>>opaque</option>
				<option value="window"<? if ($str_FLASH_TRANSPARENT == 'window'): ?> selected="selected"<? endif; ?>>window</option>
			</select>
		</td>
	</tr>
	<tr id="eFlashJs" style="display: none;">
		<td><?=GetMessage('AD_FLASH_JS')?> <?=GetMessage('AD_FLASH_JS_DESCRIPTION')?></td>
		<td>
			<input type="checkbox" id="FLASH_JS" onclick="SwitchRows(['eAltImage', 'eFlashFileLoaded', 'eFlashVer'], this.checked);" name="FLASH_JS" value="Y"<? if($str_FLASH_JS == 'Y') echo ' checked="checked"'; ?> />
		</td>
	</tr>

	<tr id="eFlashVer" style="display: none;">
		<td><?=GetMessage('ADV_FLASH_VERSION')?></td>
		<td>
			<input type="text" name="FLASH_VER" maxlength="20" size="20"  value="<?=$str_FLASH_VER?>">
		</td>
	</tr>

	<tr valign="top" id="eAltImage" style="display: none;">
		<td><?=GetMessage("ADV_FLASH_IMAGE")?></td>
		<td><?echo CFile::InputFile("FLASH_IMAGE", 25, $str_FLASH_IMAGE);?></td>
	</tr>
	<?
	if(intval($str_FLASH_IMAGE)>0) :
	?>
	<tr valign="top" id="eFlashFileLoaded" style="display: none;">
		<td align="center" colspan="2"><?echo CFile::ShowImage($str_FLASH_IMAGE, 600, 600, "border=0", "", true)?></td>
	</tr>
	<?endif;?>
	<tr id="eFlashUrl" style="display: none;">
		<td><?=GetMessage("ADV_BANNER_NO_LINK")?>:<?if ($isEditMode):?><span class="required"><sup>1</sup></span><?endif;?></td>
		<td><input type="checkbox" id="NO_URL_IN_FLASH" name="NO_URL_IN_FLASH" value="Y"<?if($str_NO_URL_IN_FLASH=="Y") echo " checked";?><?if(!$isEditMode) echo ' disabled="true"';?> id="NO_URL_IN_FLASH"></td>
	</tr>
	<tr id="eUrl" style="display: none;">
		<td valign="top"><?=GetMessage("AD_URL");?><?if ($isEditMode):?><span class="required"><sup>1</sup></span><?endif;?></td>
		<td><?
		if ($isEditMode) :
			?><input id="iUrl" type="text" size="50" name="URL" value="<?=$str_URL?>"><?
		else :
			if(strlen($str_URL)>0)
				echo $str_URL;
			else
				echo GetMessage("ADV_NOT_SET");
		endif;

		if ($isEditMode):?>
		<script type="text/javascript">
		function PutEventGID(str)
		{
			document.bx_adv_edit_form.URL.value += str;
		}
		</script>
		<br /><?=str_replace("#EVENT_GID#", "<a href=\"javascript:PutEventGID('#EVENT_GID#')\"  title='".GetMessage("AD_INS_TEMPL")."'>#EVENT_GID#</a>", GetMessage("AD_CAN_USE_EVENT_GID"))?>
		<?
		endif;
		?></td>
	</tr>
	<tr valign="top" id="eUrlTarget" style="display: none;">
		<td><?=GetMessage("AD_URL_TARGET");?><?if ($isEditMode):?><span class="required"><sup>1</sup></span><?endif;?></td>
		<td><?
		$ref = array(
			GetMessage("AD_SELF_WINDOW"),
			GetMessage("AD_BLANK_WINDOW"),
			GetMessage("AD_PARENT_WINDOW"),
			GetMessage("AD_TOP_WINDOW"),
			);
		$ref_id = array(
			"_self",
			"_blank",
			"_parent",
			"_top"
			);

		if ($isEditMode) :
			?>
			<script language="javascript">
			<!--
			function SelectUrlTarget()
			{
				var obj;
				obj = document.bx_adv_edit_form.SELECT_URL_TARGET;
				document.bx_adv_edit_form.URL_TARGET.value = obj[obj.selectedIndex].value;
			}
			//-->
			</script>
			<input type="text" id="iURL_TARGET" maxlength="255" name="URL_TARGET" size="30" value="<?=$str_URL_TARGET?>"> <?
			echo SelectBoxFromArray("SELECT_URL_TARGET", array("reference" => $ref, "reference_id" => $ref_id), "", " ", " OnChange = SelectUrlTarget()");
		else :
			$key = array_search($str_URL_TARGET, $ref_id);
			if (strlen($ref[$key])>0) echo $ref[$key]; else echo $str_URL_TARGET;
		endif;
		?></td>
	</tr>
	<tr valign="top" id="eImageAlt" style="display: none;">
		<td><?=GetMessage("AD_IMAGE_ALT")?><?if ($isEditMode):?><span class="required"><sup>1</sup></span><?endif;?></td>
		<td><?
		if ($isEditMode) :
			?><input type="text" name="IMAGE_ALT" maxlength="255" size="50" value="<?=$str_IMAGE_ALT?>"><?
		else :
			if(strlen($str_IMAGE_ALT)>0)
				echo $str_IMAGE_ALT;
			else
				echo GetMessage("ADV_NOT_SET");
		endif;
		?></td>
	</tr>
	<? if ($isEditMode): ?>
	<tr valign="top" style="display: none;" id="eCodeHeader">
		<td colspan="2" align="center"><a href="javascript:void(0)" onclick="SwitchRows(['eCode'], document.getElementById('eCode').style.display == 'none')"><b><?=GetMessage("AD_OR");?></b></a></td>
	</tr>
	<? endif; ?>
	<?endif;?>
	<script type="text/javascript">
	var t=null;
	function PutRandom(str)
	{
		document.bx_adv_edit_form.CODE.value += str;
		BX.fireEvent(document.bx_adv_edit_form.CODE, 'change');
	}
	</script>
	<tr valign="top" id="eCode" style="display:<?if($str_AD_TYPE <> 'html'):?>none<?endif?>;">
		<td align="center" colspan="2">
			<table width="95%" cellspacing="0" border="0" cellpadding="0">
			<?if ($isEditMode):
				if(COption::GetOptionString("advertising", "USE_HTML_EDIT", "Y")=="Y" && CModule::IncludeModule("fileman")):?>
				<tr valign="top">
					<td align="center" colspan="2"><?
					if (defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1)
						CFileMan::AddHTMLEditorFrame("CODE", $str_CODE, "CODE_TYPE", $str_CODE_TYPE, array('height' => 450, 'width' => '100%'), "N", 0, "", "onfocus=\"t=this\"");
					else
						CFileMan::AddHTMLEditorFrame("CODE", $str_CODE, "CODE_TYPE", $str_CODE_TYPE, 300, "N", 0, "", "onfocus=\"t=this\"");
				?></td>
				</tr>
				<?else:?>
					<tr valign="top">
						<td align="center" colspan="2"><? echo InputType("radio", "CODE_TYPE","text",$str_CODE_TYPE,false)?><?echo GetMessage("AD_TEXT")?>/&nbsp;<? echo InputType("radio","CODE_TYPE","html",$str_CODE_TYPE,false)?>&nbsp;HTML&nbsp;</td>
					</tr>
					<tr>
						<td align="center"><textarea style="width:100%" rows="30" name="CODE" onfocus="t=this"><?echo $str_CODE?></textarea></td>
					</tr>
				<?endif;
			else:?>
				<?if(strlen($str_CODE)>0):?>
					<tr valign="top">
						<td align="center" colspan="2"><? echo InputType("radio", "CODE_TYPE","text",$str_CODE_TYPE,false, "", " disabled")?><?echo GetMessage("AD_TEXT")?>/&nbsp;<? echo InputType("radio","CODE_TYPE","html",$str_CODE_TYPE,false, "", " disabled")?>&nbsp;HTML&nbsp;</td>
					</tr>
					<tr>
						<td align="center"><?echo ($str_CODE_TYPE == "text")? $str_CODE : htmlspecialcharsback($str_CODE)?></td>
					</tr>
				<?endif;?>
			<?endif;?>
				<?if ($isEditMode):?>
				<tr>
					<td><?=GetMessage("AD_HTML_ALT")?>&nbsp;<a href="javascript:PutRandom('#RANDOM1#')" title="<?=GetMessage("AD_INS_TEMPL")?>">#RANDOM1#</a>,
					<a href="javascript:PutRandom('#RANDOM2#')" title="<?=GetMessage("AD_INS_TEMPL")?>">#RANDOM2#</a>,
					<a href="javascript:PutRandom('#RANDOM3#')" title="<?=GetMessage("AD_INS_TEMPL")?>">#RANDOM3#</a>,
					<a href="javascript:PutRandom('#RANDOM4#')" title="<?=GetMessage("AD_INS_TEMPL")?>">#RANDOM4#</a>,
					<a href="javascript:PutRandom('#RANDOM5#')" title="<?=GetMessage("AD_INS_TEMPL")?>">#RANDOM5#</a>
					</td>
				</tr>
				<?endif;?>
			</table></td>
	</tr>
<?
/***************************************************************
						Статус баннера
***************************************************************/
?>
	<tr class="heading">
		<td colspan="2"><b><?=GetMessage("AD_BANNER_STATUS")?></b></td>
	</tr>
	<?if ($isAdmin || ($isDemo && !$isOwner) || $isManager) :?>
		<tr>
			<td><?=GetMessage("AD_STATUS")?></td>
			<td><?
				$arrStatus = CAdvBanner::GetStatusList();
				if ($ID == 0)
				{
					$arrDefStatus = CAdvContract::GetByID($str_CONTRACT_ID);
					if($defStatus = $arrDefStatus->Fetch())
					{
						$str_STATUS_SID = $defStatus['DEFAULT_STATUS_SID'];
					}

				}
				echo SelectBoxFromArray("STATUS_SID", $arrStatus, $str_STATUS_SID, " ");
				?></td>
		</tr>
	<?elseif ($ID>0):?>
		<tr>
			<td><?=GetMessage("AD_STATUS")?></td>
			<td><?
				$arrStatus = CAdvBanner::GetStatusList();
				$key = array_search($str_STATUS_SID, $arrStatus["reference_id"]);
				if ($key!==false) echo $arrStatus["reference"][$key];
				?></td>
		</tr>
	<?endif;?>

	<?if ($isAdmin || ($isDemo && !$isOwner) || $isManager) :?>
		<tr valign="top">
			<td><?=GetMessage("AD_STATUS_COMMENTS")?></td>
			<td><textarea cols="35" name="STATUS_COMMENTS" rows="3" wrap="VIRTUAL"><?=$str_STATUS_COMMENTS?></textarea></td>
		</tr>
	<?elseif (strlen($str_STATUS_COMMENTS)>0):?>
		<tr valign="top">
			<td><?=GetMessage("AD_STATUS_COMMENTS")?></td>
			<td><?echo TxtToHtml($str_STATUS_COMMENTS)?></td>
		</tr>
	<?endif;?>

	<?if ($isAdmin || ($isDemo && !$isOwner) || $isManager) :?>
		<tr>
			<td><label for="SEND_EMAIL"><?=GetMessage("AD_SEND_EMAIL")?></label></td>
			<td><?echo InputType("checkbox", "SEND_EMAIL", "Y", $SEND_EMAIL, false, "", 'id="SEND_EMAIL"');?></td>
		</tr>
	<?endif;?>
<?
/***************************************************************
				Когда и как часто показывать
***************************************************************/
$tabControl->BeginNextTab();
?>

	<?if ($isAdmin || ($isDemo && !$isOwner) || $isManager):?>
	<script language="javascript">
	function DisableFixShow(check)
	{
		document.getElementById("MAX_VISITOR_COUNT").disabled =
		document.getElementById("SHOWS_FOR_VISITOR").disabled =
		document.getElementById("MAX_SHOW_COUNT").disabled  = !check;

		if (document.getElementById("RESET_VISITOR_COUNT"))
			document.getElementById("RESET_VISITOR_COUNT").disabled = !check;

		if (document.getElementById("RESET_SHOW_COUNT"))
			document.getElementById("RESET_SHOW_COUNT").disabled = !check;
	}
	</script>
	<?$disableFixShow = ($str_FIX_SHOW != "Y" ? " disabled" : "");?>
	<tr valign="top">
		<td width="40%"><label for="FIX_SHOW"><?=GetMessage("AD_FIX_SHOW")?></label></td>
		<td width="60%"><?
			if ($isEditMode):
				echo InputType("checkbox", "FIX_SHOW", "Y", $str_FIX_SHOW, false, "", 'id="FIX_SHOW" OnClick="DisableFixShow(this.checked);"');
			else:
				?><?echo ($str_FIX_SHOW=="Y" ? GetMessage("AD_YES") : GetMessage("AD_NO"))?><?
			endif;
			?></td>
	</tr>
<?if(COption::GetOptionString('advertising', 'DONT_FIX_BANNER_SHOWS') == "Y"):?>
	<tr><td colspan="2"><?=BeginNote()?><?echo GetMessage("AD_EDIT_NOT_FIX")?><?=EndNote()?></td></tr>
<?endif?>
	<?endif;?>

	<tr valign="top">
		<td width="40%"><label for="FLYUNIFORM"><?=GetMessage("AD_UNIFORM")?></label><span class="required"><sup>2</sup></span></td>
		<td width="60%"><?
			if ($isEditMode):
				echo InputType("checkbox", "FLYUNIFORM", "Y", $str_FLYUNIFORM, false, "", 'id="FLYUNIFORM"');
			else:
				?><?echo ($str_FLYUNIFORM=="Y" ? GetMessage("AD_YES") : GetMessage("AD_NO"))?><?
			endif;
			?></td>
	</tr>

	<?if (!$isEditMode):?>
	<tr valign="top">
		<td><?=GetMessage("AD_VISITOR_COUNT_2")?></td>
		<td><b><?=intval($str_VISITOR_COUNT)?></b>&nbsp;/&nbsp;<?=$str_MAX_VISITOR_COUNT?></td>
	</tr>
	<?else:?>
	<tr>
		<td><?=GetMessage("AD_VISITOR_COUNT")?></td>
		<td><input type="text" name="MAX_VISITOR_COUNT" id="MAX_VISITOR_COUNT" size="10" value = "<?=$str_MAX_VISITOR_COUNT?>"<?=$disableFixShow?>><?
		if ($ID>0) :
			?>&nbsp;<?=GetMessage("AD_VISITORS")?>&nbsp;<b><?=$str_VISITOR_COUNT?></b>&nbsp;&nbsp;<?
			if ($isAdmin || ($isDemo && !$isOwner) || $isManager)
			{
				echo '<label for="RESET_VISITOR_COUNT">'.GetMessage("AD_RESET_COUNTER")."</label>";
				?>&nbsp;<input type="checkbox" name="RESET_VISITOR_COUNT" value="Y" id="RESET_VISITOR_COUNT"<?=$disableFixShow?>><?
			}
		endif;
		?></td>
	</tr>
	<?endif;?>

	<tr>
		<td><?=GetMessage("AD_SHOWS_FOR_VISITOR")?></td>
		<td><?
		if ($isEditMode) :
			?><input type="text" name="SHOWS_FOR_VISITOR" id="SHOWS_FOR_VISITOR" value="<?=$str_SHOWS_FOR_VISITOR?>" size="10"<?=$disableFixShow?>><?
		else :
			if(IntVal($str_SHOWS_FOR_VISITOR)>0)
				echo $str_SHOWS_FOR_VISITOR;
			else
				echo GetMessage("ADV_NO_LIMITS");
		endif;
		?></td>
	</tr>

	<?if (!$isEditMode):?>
	<tr valign="top">
		<td><?=GetMessage("AD_SHOW_COUNT_2")?></td>
		<td><b><?=intval($str_SHOW_COUNT)?></b>&nbsp;/&nbsp;<?=intval($str_MAX_SHOW_COUNT)?></td>
	</tr>
	<?else:?>
	<tr>
		<td><?=GetMessage("AD_SHOW_COUNT")?></td>
		<td><input type="text" name="MAX_SHOW_COUNT" id="MAX_SHOW_COUNT" size="10" value = "<?=$str_MAX_SHOW_COUNT?>"<?=$disableFixShow?>><?
		if ($ID>0) :
			?>&nbsp;<?=GetMessage("AD_SHOWN")?>&nbsp;<b><?echo $str_SHOW_COUNT?></b>&nbsp;&nbsp;<?
			if ($isAdmin || ($isDemo && !$isOwner) || $isManager)
			{
				echo '<label for="RESET_SHOW_COUNT">'.GetMessage("AD_RESET_COUNTER").'</label>';
				?>&nbsp;<input type="checkbox" name="RESET_SHOW_COUNT" value="Y" id="RESET_SHOW_COUNT"<?=$disableFixShow?>><?
			}
		endif;
		?></td>
	</tr>
	<?endif;?>

	<?if ($isAdmin || ($isDemo && !$isOwner) || $isManager):?>
	<script language="javascript">
	function ObjDisableR( ObjName, v )
	{
		ObjT = document.getElementById(ObjName);
		if( ObjT != null ) ObjT.disabled = v;
	}
	function DisableFixClick()
	{
		if (!document.getElementById("FIX_CLICK").checked)
		{
			ObjDisableR( "MAX_CLICK_COUNT", true );
			ObjDisableR( "RESET_CLICK_COUNT", true );
		}
		else
		{
			ObjDisableR( "MAX_CLICK_COUNT", false );
			ObjDisableR( "RESET_CLICK_COUNT", false );
		}

	}
	</script>
	<?if($str_FIX_CLICK != "Y")
		$disableFixClick = " disabled";?>
	<tr valign="top">
		<td width="40%"><label for="FIX_CLICK"><?=GetMessage("AD_FIX_CLICK")?></label></td>
		<td width="60%"><?
			if ($isEditMode):
				echo InputType("checkbox", "FIX_CLICK", "Y", $str_FIX_CLICK, false, "", 'id="FIX_CLICK" OnClick="DisableFixClick();"');
			else:
				?><?echo ($str_FIX_CLICK=="Y" ? GetMessage("AD_YES") : GetMessage("AD_NO"))?><?
			endif;
			?></td>
	</tr>
	<?endif;?>

	<?if (!$isEditMode):?>
	<tr valign="top">
		<td><?=GetMessage("AD_CLICK_COUNT_2")?></td>
		<td><b><?=intval($str_CLICK_COUNT)?></b>&nbsp;/&nbsp;<?=$str_MAX_CLICK_COUNT?></td>
	</tr>
	<?else:?>
	<tr>
		<td><?=GetMessage("AD_CLICK_COUNT")?></td>
		<td><input type="text" name="MAX_CLICK_COUNT" id="MAX_CLICK_COUNT" size="10" value = "<?=$str_MAX_CLICK_COUNT?>"<?=$disableFixClick?>><?
		if ($ID>0) :
			?>&nbsp;<?=GetMessage("AD_CLICKED")?>&nbsp;<b><?echo $str_CLICK_COUNT?></b>&nbsp;&nbsp;&nbsp;<?
			if ($isAdmin || ($isDemo && !$isOwner) || $isManager)
			{
				echo '<label for="RESET_CLICK_COUNT">'.GetMessage("AD_RESET_COUNTER").'</label>';
				?>&nbsp;<input type="checkbox" name="RESET_CLICK_COUNT" value="Y" id="RESET_CLICK_COUNT"<?=$disableFixClick?>><?
			}
		endif;
		?></td>
	</tr>
	<?endif;?>

	<?if ($ID>0):?>
	<tr valign="top">
		<td><?=GetMessage("AD_CTR")?></td>
		<td><b><?=$str_CTR?></b></td>
	</tr>
	<?endif;?>



<?
/***************************************************************
					Где и кому показывать
***************************************************************/
$tabControl->BeginNextTab();
?>
	<tr valign="top">
		<td width="40%"><?=GetMessage("AD_SITE")?></td>
		<td width="60%"><?

			$arrContractSite =  CAdvContract::GetSiteArray($str_CONTRACT_ID);

			if (is_array($arrContractSite)):

				if ($isEditMode) :

					reset($arrSites);
					while(list($sid, $arrS) = each($arrSites)):
						if (in_array($sid, $arrContractSite)) :
							$checked = (in_array($sid, $arrSITE)) ? "checked" : "";
							/*<?=$disabled?>*/
							?>
							<input type="checkbox" name="arrSITE[]" value="<?=htmlspecialcharsbx($sid)?>" style="vertical-align:baseline; border-spacing: 0px; margin: 0px; padding: 0px;" id="site_<?=htmlspecialcharsbx($sid)?>" <?=$checked?>>
							<?echo '<span style="vertical-align:baseline; margin: 0px; padding-top:0px;" >[<a href="/bitrix/admin/site_edit.php?LID='.urlencode($sid).'&lang='.LANGUAGE_ID.'" title="'.GetMessage("AD_SITE_ALT").'">'.htmlspecialcharsex($sid).'</a>]&nbsp;<label for="site_'.htmlspecialcharsbx($sid).'">'.htmlspecialcharsex($arrS["NAME"])?></label></span>
							<br>
							<?
						endif;
					endwhile;

				else:

					reset($arrSITE);
					if (is_array($arrSITE)):
						foreach($arrSITE as $sid):
							if (in_array($sid, $arrContractSite)) :
								$arS = $arrSites[$sid];
								echo '[<a href="/bitrix/admin/site_edit.php?LID='.urlencode($arS["ID"]).'&lang='.LANGUAGE_ID.'" title="'.GetMessage("AD_SITE_ALT").'">'.htmlspecialcharsex($arS["ID"]).'</a>] '.htmlspecialcharsex($arS["NAME"]).'<br>';
							endif;
						endforeach;
					endif;

				endif;

			endif;
			?></td>
	</tr>

	<tr valign="top">
		<td><?=GetMessage("AD_SHOW_PAGES");?></td>
		<td><?
		if ($isEditMode) :
			?><textarea name="SHOW_PAGE" cols="45" rows="6" wrap="OFF"><?=$str_SHOW_PAGE?></textarea><br><?=GetMessage("AD_PAGES_ALT")?><?
		else :
			$arr = $arrSHOW_PAGE;
			if (is_array($arr) && count($arr)>0)
			{
				foreach($arr as $page)
					echo htmlspecialcharsbx($page).'<br>';
			}
			else
			{
				echo GetMessage("ADV_NO_LIMITS");
			}
		endif;
		?></td>
	</tr>
	<tr valign="top">
		<td><?=GetMessage("AD_NOT_SHOW_PAGES");?></td>
		<td><?
		if ($isEditMode) :
			?><textarea name="NOT_SHOW_PAGE" cols="45" rows="6" wrap="OFF"><?=$str_NOT_SHOW_PAGE?></textarea><br><?=GetMessage("AD_PAGES_ALT")?><?
		else :
			$arr = $arrNOT_SHOW_PAGE;
			if (is_array($arr) && count($arr)>0)
			{
				foreach($arr as $page)
					echo htmlspecialcharsbx($page).'<br>';
			}
			else
			{
				echo GetMessage("ADV_NO_LIMITS");
			}
		endif;
		?></td>
	</tr>

	<?if ($isEditMode):
		$rUserGroups = CGroup::GetList($by = "name", $order = "asc", array("ANONYMOUS"=>"N"));
		while ($arUserGroups = $rUserGroups->Fetch())
		{
			$ug_id[] = $arUserGroups["ID"];
			$ug[] = $arUserGroups["NAME"]." [".$arUserGroups["ID"]."]";
		}
	?>
		<tr valign="top">
			<td><?=GetMessage("AD_USER_GROUPS");?><br><img src="/bitrix/images/advertising/mouse.gif" width="44" height="21" border=0 alt=""><br><?=GetMessage("AD_SELECT_WHAT_YOU_NEED")?></td>
			<td>
				<input type="radio" id="SHOW_USER_LABEL_Y" name="SHOW_USER_GROUP" value="Y" <?if($str_SHOW_USER_GROUP=="Y") echo "checked";?>><label for="SHOW_USER_LABEL_Y"><?=GetMessage("AD_USER_GROUP_Y");?></label> <br>
				<input type="radio" id="SHOW_USER_LABEL_N" name="SHOW_USER_GROUP" value="N" <?if($str_SHOW_USER_GROUP!="Y") echo "checked";?>><label for="SHOW_USER_LABEL_N"><?=GetMessage("AD_USER_GROUP_N");?></label><br>
			<?=SelectBoxMFromArray("arrUSERGROUP[]", array("REFERENCE" => $ug, "REFERENCE_ID" => $ug_id), $arrUSERGROUP, "", false, 10);?></td>
		</tr>
	<?else:
		$rUserGroups = CGroup::GetList($by = "name", $order = "asc", Array("ID"=>implode(" | ",$arrUSERGROUP), "ANONYMOUS"=>"N"));
		while ($arUserGroups = $rUserGroups->Fetch())
		{
			$ug .= $arUserGroups["NAME"].' [<a href="group_edit.php?ID='.$arUserGroups["ID"].'&lang='.LANGUAGE_ID.'" title="'.GetMessage("ADV_VIEW_UGROUP").'">'.$arUserGroups["ID"].'</a>]<br>';
		}
		?>
		<tr valign="top">
			<?if(strlen($ug)>0 && !empty($arrUSERGROUP)):?>
				<td><?=GetMessage("AD_USER_GROUP_".$str_SHOW_USER_GROUP);?>:</td>
				<td><?=$ug?></td>
			<?else:?>
				<td><?=GetMessage("AD_USER_GROUP_Y");?>:</td>
				<td><?=GetMessage("AD_ALL_1");?></td>
			<?endif;?>
		</tr>
	<?endif;?>


	<?if ($isAdmin || $isManager || ($isDemo && !$isOwner)):?>
	<tr valign="top">
		<td><?=GetMessage("AD_KEYWORDS");?></td>
		<td><?
		if ($isEditMode) :
			?><textarea name="KEYWORDS" cols="45" rows="6" wrap="OFF"><?=$str_KEYWORDS?></textarea><br><?=GetMessage("AD_KEYWORDS_ALT")?><?
		else :
			if (is_array($arrKEYWORDS))
				echo implode("<br>", $arrKEYWORDS);
			else
				echo GetMessage("ADV_NOT_SET");
		endif;
		?></td>
	</tr>
	<?endif;?>

	<?
	if (CModule::IncludeModule("statistic")):
		$arDisplay = array();
		if($str_STAT_TYPE === "CITY")
		{
			if(is_array($arrSTAT_TYPE_VALUES) && (count($arrSTAT_TYPE_VALUES) > 0))
			{
				$arFilter = array();
				foreach($arrSTAT_TYPE_VALUES as $ar)
					$arFilter[] = $ar["CITY_ID"];
				$rs = CCity::GetList("CITY", array("=CITY_ID" => $arFilter));
				while($ar = $rs->GetNext())
					$arDisplay[$ar["CITY_ID"]] = "[".$ar["COUNTRY_ID"]."] [".$ar["REGION_NAME"]."] ".$ar["CITY_NAME"];
			}
		}
		elseif($str_STAT_TYPE === "REGION")
		{
			if(is_array($arrSTAT_TYPE_VALUES))
			{
				foreach($arrSTAT_TYPE_VALUES as $ar)
					$arDisplay[$ar["COUNTRY_ID"]."|".$ar["REGION"]] = "[".$ar["COUNTRY_ID"]."] ".$ar["REGION"];
			}
		}
		else
		{
			if(is_array($arrSTAT_TYPE_VALUES) && (count($arrSTAT_TYPE_VALUES) > 0))
			{
				$arr = array_flip($arrSTAT_TYPE_VALUES);
				$v1 = "s_id";
				$rs = CStatCountry::GetList($v1, $v2, array(), $v3);
				while($ar = $rs->GetNext())
					if(array_key_exists($ar["REFERENCE_ID"], $arr))
						$arDisplay[$ar["REFERENCE_ID"]] = $ar["REFERENCE"];
			}
		}
	?>
	<tr valign="top">
		<td><?echo GetMessage("ADV_STAT_WHAT_QUESTION")?>:</td>
		<td>
			<label><input type="radio" name="STAT_TYPE" value="COUNTRY" OnClick="stat_type_changed(this);" <?echo $str_STAT_TYPE!=="CITY" && $str_STAT_TYPE!=="REGION"? "checked" : ""?><?if(!$isEditMode) echo ' disabled'?>><?echo GetMessage("ADV_STAT_WHAT_COUNTRY")?></label><br>
			<label><input type="radio" name="STAT_TYPE" value="REGION" OnClick="stat_type_changed(this);" <?echo $str_STAT_TYPE==="REGION"? "checked" : ""?><?if(!$isEditMode) echo ' disabled'?>><?echo GetMessage("ADV_STAT_WHAT_REGION")?></label><br>
			<label><input type="radio" name="STAT_TYPE" value="CITY" OnClick="stat_type_changed(this);" <?echo $str_STAT_TYPE==="CITY"? "checked" : ""?><?if(!$isEditMode) echo ' disabled'?>><?echo GetMessage("ADV_STAT_WHAT_CITY")?></label><br>
			<select style="width:100%" size="10" id="STAT_TYPE_VALUES[]" name="STAT_TYPE_VALUES[]" multiple OnChange="stat_type_values_change()"<?if(!$isEditMode) echo ' disabled'?>>
				<?foreach($arDisplay as $key => $value):?>
					<option value="<?echo $key?>"><?echo $value?></option>
				<?endforeach;?>
			</select>
		<?if($isEditMode):?>
			<script>
			var V_STAT_TYPE = <?echo CUtil::PHPToJsObject($str_STAT_TYPE);?>;
			var V_STAT_TYPE_VALUES = <?echo CUtil::PHPToJsObject(array(
				"COUNTRY"=>array(),
				"REGION"=>array(),
				"CITY"=>array(),
			))?>;

			function stat_type_values_change()
			{
				var oSelect = document.getElementById('STAT_TYPE_VALUES[]');
				if(oSelect)
				{
					var v = '';
					var n = oSelect.length;
					for(var i=0; i<n; i++)
						if(v.length)
							v += ','+oSelect[i].value;
						else
							v = oSelect[i].value;
					document.getElementById('ALL_STAT_TYPE_VALUES').value = v;
				}
			}

			function stat_type_changed(target)
			{
				var oSelect = document.getElementById('STAT_TYPE_VALUES[]');
				if(oSelect)
				{
					//Save
					V_STAT_TYPE_VALUES[V_STAT_TYPE] = new Array();
					var n = oSelect.length;
					for(var i=0; i<n; i++)
						V_STAT_TYPE_VALUES[V_STAT_TYPE][oSelect[i].value] = oSelect[i].text;
					//Clear
					jsSelectUtils.selectAllOptions('STAT_TYPE_VALUES[]');
					jsSelectUtils.deleteSelectedOptions('STAT_TYPE_VALUES[]');
					//Restore
					for(var val in V_STAT_TYPE_VALUES[target.value])
						jsSelectUtils.addNewOption('STAT_TYPE_VALUES[]', val, V_STAT_TYPE_VALUES[target.value][val]);

					V_STAT_TYPE = target.value;
					stat_type_values_change();
				}
			}
			function stat_type_popup()
			{
				if(V_STAT_TYPE == 'CITY')
					jsUtils.OpenWindow('/bitrix/admin/stat_city_multiselect.php?lang=<?echo LANGUAGE_ID?>&form=bx_adv_edit_form&field=STAT_TYPE_VALUES[]', 600, 600);
				else if (V_STAT_TYPE == 'REGION')
					jsUtils.OpenWindow('/bitrix/admin/stat_region_multiselect.php?lang=<?echo LANGUAGE_ID?>&form=bx_adv_edit_form&field=STAT_TYPE_VALUES[]', 600, 600);
				else
					jsUtils.OpenWindow('/bitrix/admin/stat_country_multiselect.php?lang=<?echo LANGUAGE_ID?>&form=bx_adv_edit_form&field=STAT_TYPE_VALUES[]', 600, 600);
			}
			</script>
			<input type="hidden" id="ALL_STAT_TYPE_VALUES" name="ALL_STAT_TYPE_VALUES" value="<?echo implode(",", array_keys($arDisplay))?>">
			<input type="button" value="<?echo GetMessage("ADV_STAT_WHAT_ADD")?>" OnClick="stat_type_popup();">&nbsp;&nbsp;<input type="button" value="<?echo GetMessage("ADV_STAT_WHAT_DELETE")?>" OnClick="jsSelectUtils.deleteSelectedOptions('STAT_TYPE_VALUES[]');stat_type_values_change();">
		<?endif;?>
		</td>
	</tr>
	<?
	if ($isAdmin || ($isDemo && !$isOwner)):
		$ref = array();
		$ref_id = array();
		$rsAdv = CAdv::GetDropDownList("ORDER BY REFERER1, REFERER2");
		while ($arAdv = $rsAdv->Fetch())
		{
			$ref[] = $arAdv["REFERENCE"];
			$ref_id[] = $arAdv["REFERENCE_ID"];
		}
	if ($isEditMode):
	?>
	<tr valign="top">
		<td><?=GetMessage("AD_STAT_ADV")?><br><img src="/bitrix/images/advertising/mouse.gif" width="44" height="21" border=0 alt=""><br><?=GetMessage("AD_SELECT_WHAT_YOU_NEED")?></td>
		<td><?echo SelectBoxMFromArray("arrSTAT_ADV[]", array("REFERENCE" => $ref, "REFERENCE_ID" => $ref_id), $arrSTAT_ADV, "", true, 10);?></td>
	</tr>
	<?else:?>
	<tr valign="top">
		<td><?=GetMessage("AD_STAT_ADV")?></td>
		<td><?
			if (is_array($arrSTAT_ADV) && count($arrSTAT_ADV)>0)
			{
				foreach ($arrSTAT_ADV as $aid)
				{
					$key = array_search($aid, $ref_id);
					echo htmlspecialcharsbx($ref[$key])."<br>";
				}
			}
			else
				echo GetMessage("ADV_NOT_SET");
		?></td>
	</tr>
	<?endif;?>
	<?endif;?>

	<tr valign="top">
		<td><?=GetMessage("AD_VISITORS_TYPE")?></td>
		<td><?
			if ($isEditMode) :
				$arr = array(
					"reference" => array(
						GetMessage("AD_NEW_VISITORS_ONLY"),
						GetMessage("AD_RETURNED_VISITORS_ONLY")
						),
					"reference_id" => array(
						"Y",
						"N")
					);
				echo SelectBoxFromArray("FOR_NEW_GUEST", $arr, $str_FOR_NEW_GUEST, GetMessage("AD_ALL_VISITORS"));
			else :
				if ($str_FOR_NEW_GUEST=="Y")
					echo GetMessage("AD_NEW_VISITORS_ONLY");
				elseif ($str_FOR_NEW_GUEST=="Y")
					echo GetMessage("AD_RETURNED_VISITORS_ONLY");
				else
					echo GetMessage("AD_ALL_VISITORS");
			endif;
			?></td>
	</tr>

	<?endif;?>


	<tr valign="top">
		<td><?=GetMessage("AD_WEEKDAY");?></td>
		<td>
		<script language="javascript">
		<!--
		function OnSelectAll(all_checked, name, vert)
		{
			if(vert)
			{
				for(i=0;i<=23;i++)
				{
					name1 = "arr"+name+"_"+i+"[]";
					if(document.getElementById(name1).disabled == false)
						document.getElementById(name1).checked = all_checked;
				}
			}
			else
			{
				ar = Array("MONDAY", "TUESDAY", "WEDNESDAY", "THURSDAY", "FRIDAY", "SATURDAY", "SUNDAY");
				for(i=0;i<7;i++)
				{
					name2 = ar[i];
					name1 = "arr"+name2+"_"+name+"[]";
					if(document.getElementById(name1).disabled == false)
						document.getElementById(name1).checked = all_checked;
				}

			}
		}
		//-->
		</script>
		<table cellspacing=1 cellpadding=0 border=0>
			<tr>
				<td>&nbsp;</td>
				<?
				$disabled = (!$isEditMode) ? "disabled" : "";
				$arrWDAY = array(
					"MONDAY"	=> GetMessage("AD_MONDAY"),
					"TUESDAY"	=> GetMessage("AD_TUESDAY"),
					"WEDNESDAY"	=> GetMessage("AD_WEDNESDAY"),
					"THURSDAY"	=> GetMessage("AD_THURSDAY"),
					"FRIDAY"	=> GetMessage("AD_FRIDAY"),
					"SATURDAY"	=> GetMessage("AD_SATURDAY"),
					"SUNDAY"	=> GetMessage("AD_SUNDAY")
					);
				while(list($key,$value)=each($arrWDAY)) :
				?>
				<td><label for="<?=$key?>"><?=$value?></label><br><input <?=$disabled?> type="checkbox" onclick="OnSelectAll(this.checked, '<?=$key?>', true)" id="<?=$key?>"></td>
				<?
				endwhile;
				?>
				<td>&nbsp;</td>
			</tr>
			<?
			$arrCONTRACT_WEEKDAY = CAdvContract::GetWeekdayArray($arContract["ID"]);
			for($i=0;$i<=23;$i++):
			?>
			<tr>
				<td><label for="<?=$i?>"><?echo $i."&nbsp;-&nbsp;".($i+1)?></label></td>
				<?
				reset($arrWDAY);
				while(list($key,$value)=each($arrWDAY)) :
					$checked = "";
					$disabled = "";
					$disabled = (!is_array($arrCONTRACT_WEEKDAY[$key]) || !in_array($i, $arrCONTRACT_WEEKDAY[$key]) || !$isEditMode) ? "disabled" : "";

					if ($ID<=0 && $disabled!="disabled" && strlen($strError)<=0) $checked = "checked";
					if (is_array(${"arr".$key}) && in_array($i,${"arr".$key}) && $disable!="disabled") $checked = "checked";
					?>
					<td><input <?=$disabled?> id="arr<?=$key?>_<?=$i?>[]" name="arr<?=$key?>[]" type="checkbox" value="<?=$i?>" <?=$checked?>></td>
					<?
				endwhile;
				$disabled = (!$isEditMode) ? "disabled" : "";
				?>
				<td><input <?=$disabled?> type="checkbox" onclick="OnSelectAll(this.checked, '<?=$i?>', false)" id="<?=$i?>"></td>
			</tr>
			<?
			endfor;
			?>
		<script language="javascript">
			ar = Array("MONDAY", "TUESDAY", "WEDNESDAY", "THURSDAY", "FRIDAY", "SATURDAY", "SUNDAY");
			for(j=0;j<7;j++)
			{
				valu = true;
				name = ar[j];
				for(i=0;i<=23;i++)
				{
					name1 = "arr"+name+"_"+i+"[]";
					if(document.getElementById(name1).checked == false)
					{
						valu = false;
						break;
					}
				}
				document.getElementById(name).checked = valu;
			}
			for(j=0;j<=23;j++)
			{
				valu = true;
				for(i=0;i<7;i++)
				{
					name = ar[i];
					name1 = "arr"+name+"_"+j+"[]";
					if(document.getElementById(name1).checked == false)
					{
						valu = false;
						break;
					}
				}
				document.getElementById(j).checked = valu;
			}
		</script>
		</table></td>
	</tr>

<?
if ($isAdmin || ($isDemo && !$isOwner)) :
/***************************************************************
		Как регистрировать нажатия в модуле статистики
***************************************************************/
$tabControl->BeginNextTab();
?>

	<?if ($isEditMode):?>
	<script language="javascript">
	<!--
	function PutEvent(str)
	{
		if(!t) return;
		if(t.name=="STAT_EVENT_1" || t.name=="STAT_EVENT_2" || t.name=="STAT_EVENT_3" || t.name=="CODE")
		{
			t.value+=str;
			BX.fireEvent(t, 'change');
		}
	}
	//-->
	function DisableClick()
	{
		if (!document.getElementById("FIX_STAT").checked)
		{
			document.getElementById("STAT_EVENT_1").disabled = true;
			document.getElementById("STAT_EVENT_2").disabled = true;
			document.getElementById("STAT_EVENT_3").disabled = true;
		}
		else
		{
			document.getElementById("STAT_EVENT_1").disabled = false;
			document.getElementById("STAT_EVENT_2").disabled = false;
			document.getElementById("STAT_EVENT_3").disabled = false;
		}
	}
	</script>
	<?endif;?>
	<?
	if(strlen($str_STAT_EVENT_1)>0 || strlen($str_STAT_EVENT_2)>0 || strlen($str_STAT_EVENT_3)>0)
		$FIX_STAT="Y";
	else
		$FIX_STAT="N";
	?>
	<tr>
		<td width="40%"><label for="FIX_STAT"><?=GetMessage("AD_FIX_STAT")?></label></td>
		<td width="60%">
			<?if ($isEditMode):?>
				<input type="checkbox" name="FIX_STAT" id="FIX_STAT" value="Y" OnClick="DisableClick()" <? if($FIX_STAT=="Y") echo "checked";?>>
			<?else:?>
				<?=GetMessage("ADV_".$FIX_STAT)?>
			<?endif;?>
		</td>
	</tr>
	<?
	if ($isEditMode):
			?>
		<tr>
			<td>event1:</td>
			<td><input type="text" name="STAT_EVENT_1" id="STAT_EVENT_1" maxlength="255" size="30" value="<?=$str_STAT_EVENT_1?>" onfocus="t=this" <?if($FIX_STAT!="Y") echo "disabled";?>></td>
		</tr><?
	else :
		if(strlen($str_STAT_EVENT_1)>0):
			?>
			<tr valign="top">
				<td>event1:</td>
				<td><?=$str_STAT_EVENT_1;?></td>
			</tr>
		<?endif;
	endif;
	?>
	<?
	if ($isEditMode):?>
		<tr>
			<td>event2:</td>
			<td><input type="text" name="STAT_EVENT_2" id="STAT_EVENT_2" maxlength="255" size="30" value="<?=$str_STAT_EVENT_2?>" onfocus="t=this" <?if($FIX_STAT!="Y") echo "disabled";?>>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td><?=GetMessage("AD_EVENT12")?></td>
		</tr>
		<?
	else :
		if(strlen($str_STAT_EVENT_2)>0):
		?>
		<tr valign="top">
			<td>event2:</td>
			<td><?=$str_STAT_EVENT_2;?></td>
		</tr>
		<?endif;
	endif;
	if ($isEditMode):
		?>
		<tr>
			<td>event3:</td>
			<td><input type="text" name="STAT_EVENT_3" id="STAT_EVENT_3" maxlength="255"  value="<?=$str_STAT_EVENT_3?>" onfocus="t=this" style="width:80%;" <?if($FIX_STAT!="Y") echo "disabled";?>></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td><?=GetMessage("AD_EVENT3")?>
			<a href="javascript:PutEvent('#BANNER_NAME#')" title="<?=GetMessage("AD_INS_TEMPL")?>">#BANNER_NAME#</a> - <?=GetMessage("AD_BANNER_NAME")?>,
			<a href="javascript:PutEvent('#BANNER_ID#')" title="<?=GetMessage("AD_INS_TEMPL")?>">#BANNER_ID#</a> - <?=GetMessage("AD_BANNER_ID")?>,
			<a href="javascript:PutEvent('#CONTRACT_ID#')" title="<?=GetMessage("AD_INS_TEMPL")?>">#CONTRACT_ID#</a> - <?=GetMessage("AD_CONTRACT_ID")?>,
			<a href="javascript:PutEvent('#TYPE_SID#')" title="<?=GetMessage("AD_INS_TEMPL")?>">#TYPE_SID#</a> - <?=GetMessage("AD_TYPE_SID")?></td>
		</tr><?
	else :
		if(strlen($str_STAT_EVENT_3)>0):
		?>
		<tr valign="top">
			<td>event3:</td>
			<td><?=$str_STAT_EVENT_3;?></td>
		</tr>
		<?endif;
	endif;
	?></td>
</tr>
<?endif;?>

<?
/***************************************************************
					Служебный комментарий
***************************************************************/

$tabControl->BeginNextTab();
?>
<tr>
	<td colspan="2" <?if ($isEditMode):?>align="center"<?endif;?>><?
		if ($isEditMode):
			?><textarea style="width:85%" name="COMMENTS" rows="7"
			wrap="VIRTUAL"><?=$str_COMMENTS?></textarea><?
		else :
			echo TxtToHtml($str_COMMENTS);
		endif;
		?></td>
</tr>
<?
$disable = true;
if($isManager || $isAdmin || ($isDemo && !$isOwner) || $isEditMode)
	$disable = false;

$tabControl->Buttons(array("disabled" => $disable, "back_url"=>"adv_banner_list.php?lang=".LANGUAGE_ID));
$tabControl->End();
?>
</form>
<script type="text/javascript">
<?
if(strlen($str_COMMENTS)<=0 && !$isEditMode):
?>
tabControl.DisableTab("edit5");
<?
endif;
?>
changeType('<?echo $str_AD_TYPE?>');
</script>
<?
if ($isEditMode && (!defined('BX_PUBLIC_MODE') || BX_PUBLIC_MODE != 1)) :
?>
<?echo BeginNote();?>
<span class="required"><sup>1</sup></span>&nbsp;<?=GetMessage("AD_CONFIRMED_FIELDS")?><br><br>
<span class="required"><sup>2</sup></span>&nbsp;<?echo GetMessage("AD_NOTE_2")?>
<?echo EndNote();?>
</p>
<?endif;?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>
