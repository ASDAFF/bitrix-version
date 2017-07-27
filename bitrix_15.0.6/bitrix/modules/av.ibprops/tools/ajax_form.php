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
	$ob = new C_AV_IBlock_Manage(intval($_REQUEST['IBLOCK_ID']));
	if(strlen($ob->strError)) {
		C_AV_IBlock_Manage::showError($ob->strError);
		die();
	}

	$arFields = $ob->getUpdateFields();

	// content for table row with rules
	// #n# is replaced by the counter in javascript
	$strRowRules = '<tr id="r#n#"><td><select id="R#n#_k" name="R#n#[k]" size="1" onChange="updateRulesValueField(this, #n#)">';
	// js object with nonstandard inputs (list)
	$arJSObjInputs = Array();
	foreach($arFields as $k=>$v) {
		$strRowRules .= '<option value="'.$k.'">'.CUtil::JSEscape($v["NAME"]).'</option>';

		// list property needs additional select
		if($v["TYPE"]=="L") {
			$tmpSelectListStr = '<select id="R#n#_v" name="R#n#[v]" size="1">';

			if($v["IS_REQUIRED"]=="N")
				$tmpSelectListStr .= '<option value="empty">'.GetMessage("av_empty_option").'</option>';
			foreach($v["VALUES"] as $lk=>$lv)
				$tmpSelectListStr .= '<option value="'.$lk.'">'.CUtil::JSEscape($lv).'</option>';
			$tmpSelectListStr .= '</select>';
			$arJSObjInputs[] = $k.": '".$tmpSelectListStr."'";
		}

		if($v["TRANSLIT"]=="Y") {
			$tmpSelectListStr = '<input id="R#n#_v" name="R#n#[v]" class="inp-text" /><br /><small>'.GetMessage("av_translit_field").'</small>';
			$arJSObjInputs[] = $k.": '".$tmpSelectListStr."'";
		}

		//
	}
	$strRowRules .= '</select></td>';
	$strRowRules .= '<td><input id="R#n#_v" name="R#n#[v]" class="inp-text" /></td>';
	$strRowRulesJS =  $strRowRules . '<td><div onclick="RowDelete(this)" title="'.GetMessage("av_delete_answer").'" style="width:20px;height:20px;cursor:pointer;background-image: url(/bitrix/themes/.default/images/buttons/delete.gif);"></div></td></tr>';
	$strRowRules .= '<td></td></tr>';
?>
<script>
	var RVlists = {<?=implode(",", $arJSObjInputs)?>, defaultv: '<input id="R#n#_v" name="R#n#[v]" class="inp-text" />' };
	var RVinsert = '<?=$strRowRulesJS?>';
	var n = 1;
	var lastInput = "R1_v"; // use in PutString
	function RowInsert() {
		var row = RVinsert.replace(/#n#/g, n);
		$("'"+row+"'").insertAfter("#tbl_rules tr:last");
		$("#tbl_rules tr:last input.inp-text").focus();
		n = n + 1;
	}
	function RowDelete(obj) {
		$(obj).closest("tr").remove();
	}
	function updateRulesValueField(obj, id) {
		sField = $(obj).val();
		if(RVlists[sField])
			strField = RVlists[sField];
		else
			strField = RVlists.defaultv;
		strField = strField.replace(/#n#/g, id);
		// console.log(strField);
		$("#R"+parseInt(id)+"_v").parent().empty().html(strField);
	}
	function PutString(str) {
		el = $("#"+lastInput);
		if(el) {
			a = el.val();
			el.val(a+str);
		}
	}
	// save last selected input
	$(".av-border").on("focus", ".inp-text", function(event){
		lastInput = $(this).attr("id");
	});
</script>
<div class="av-border">
<h3 class="heading"><?=GetMessage("av_rules_h")?></h3>
<table style="margin:0;"><tr><td>
	<table class="internal" id="tbl_rules">
		<tr class="heading">
			<td><?=GetMessage("av_rules_fields")?></td>
			<td><?=GetMessage("av_rules_value")?></td>
			<td style="width:20px;"></td>
		</tr>
		<?//=str_replace("#n#","1",$strRowRules) // first line?>
		<script>RowInsert()</script>
	</table>
	<table OnClick="RowInsert()" style="cursor:pointer";>
		<tr>
			<td><div title="<?=GetMessage("av_rules_add")?>" id="btn_new" style="width:20;height:20; background-image: url('/bitrix/themes/.default/images/buttons/new.gif');"></div></td>
			<td><?=GetMessage("av_rules_add")?></td>
		</tr>
	</table>
</div>
</td><td style="padding-left:20px;">
	<b><?=GetMessage("av_rules_use_value")?></b><br />
	<?foreach($ob->arStrFieldsUse as $k=>$v):?>
		<a href="javascript:PutString('#<?=$k?>#')">#<?=$k?>#</a> - <?=$v["NAME"]?><br/>
	<?endforeach?>
</td></tr></table>
</div>
<?
	// -------------------------------   PART 2 ----------------------------------
	// content for table row with where inputs
	// #n# is replaced by the counter in javascript
	$strRowWhere = '<tr id="w#n#"><td><select id="W#n#_k" name="W#n#[k]" size="1" onChange="updateWhereValueField(this, #n#)" class="s1">';
	// js object with nonstandard inputs (list)
	$arJSObjInputsWhere = Array();
	// js object with nonstandard compare
	$arJSObjCompareWhere = Array();
	foreach($ob->arStrFieldsWhere as $k=>$v) {
		$strRowWhere .= '<option value="'.$k.'">'.CUtil::JSEscape($v["NAME"]).'</option>';

		// list property needs additional select
		if($v["TYPE"]=="L") {
			$tmpSelectListStr = '<select id="W#n#_v" name="W#n#[v]" size="1">';
			if($v["IS_REQUIRED"]=="N")
				$tmpSelectListStr .= '<option value="empty">'.GetMessage("av_empty_option").'</option>';
			foreach($v["VALUES"] as $lk=>$lv)
				$tmpSelectListStr .= '<option value="'.$lk.'">'.CUtil::JSEscape($lv).'</option>';
			$tmpSelectListStr .= '</select>';
			$arJSObjInputsWhere[] = $k.": '".$tmpSelectListStr."'";
		}

		if(isset($v["DELETE_EQ"])) {
			$tmpCompareListStr = '<select id="W#n#_c" name="W#n#[c]" size="1">';
			foreach($ob->arCompareOptions as $ck=>$cv) {
				if(!in_array($ck, $v["DELETE_EQ"]))
					$tmpCompareListStr .= '<option value="'.$ck.'">'.$cv.'</option>';
			}
			$tmpCompareListStr .= '</select>';

			// custom field for section_id
			if($k == "f_SECTION_ID")
				$tmpCompareListStr .= '<br /><label for="is#n#">'.GetMessage("av_include_subsection").'</label> <input type="checkbox" value="Y" name="is#n#" />';
			// ----

			$arJSObjCompareWhere[] = $k.": '".$tmpCompareListStr."'";
		}

		if($v["TYPE"]=="N") {
			$tmpSelectListStr = '<input id="W#n#_v" name="W#n#[v]" /><br /><small>'.GetMessage("av_filter_num").'</small>';
			$arJSObjInputsWhere[] = $k.": '".$tmpSelectListStr."'";
		}

	}
	$strRowWhere .= '</select></td><td>';

	$strCompare = '<select id="W#n#_c" name="W#n#[c]" size="1">';
	foreach($ob->arCompareOptions as $k=>$v) {
		$strCompare .= '<option value="'.$k.'">'.$v.'</option>';
	}
	$strCompare .= '</select>';
	$strRowWhere .= $strCompare . '</td><td><input id="W#n#_v" name="W#n#[v]" /></td>';
	$strRowWhereJS =  $strRowWhere . '<td><div onclick="RowDelete(this)" title="'.GetMessage("av_delete_answer").'" style="width:20px;height:20px;cursor:pointer;background-image: url(/bitrix/themes/.default/images/buttons/delete.gif);"></div></td></tr>';
	$strRowWhere .= '<td></td></tr>';
?>
<script>
	var WVlists = {<?=implode(",", $arJSObjInputsWhere)?>, defaultv: '<input id="W#n#_v" name="W#n#[v]" />' };
	var WVComparelists = {<?=implode(",", $arJSObjCompareWhere)?>, defaultv: '<?=$strCompare?>' };
	var WVinsert = '<?=$strRowWhereJS?>';
	var n = 2;
	function RowInsertW() {
		var row = WVinsert.replace(/#n#/g, n);
		$("'"+row+"'").insertAfter("#tbl_where tr:last");
		$("#tbl_where tr:last .s1").trigger("change");
		n = n + 1;
		$("input[name=ALLELEMENTS]").prop("checked", false);
	}
	function updateWhereValueField(obj, id) {
		sField = $(obj).val();
		if(WVlists[sField])
			strField = WVlists[sField];
		else
			strField = WVlists.defaultv;
		strField = strField.replace(/#n#/g, id);
		// console.log(strField);
		$("#W"+parseInt(id)+"_v").parent().empty().html(strField);

		if(WVComparelists[sField])
			strField = WVComparelists[sField];
		else
			strField = WVComparelists.defaultv;
		strField = strField.replace(/#n#/g, id);
		// console.log(strField);
		$("#W"+parseInt(id)+"_c").parent().empty().html(strField);
	}
</script>
<div class="av-border">
<h3 class="heading"><?=GetMessage("av_filter_h")?></h3>
<div class="cb"><label><input type="checkbox" name="ALLELEMENTS" value="Y" /> <?=GetMessage("av_filter_all")?></label><br /><br /></div>
<table class="internal" id="tbl_where">
	<tr class="heading">
		<td><?=GetMessage("av_filter_fields")?></td>
		<td><?=GetMessage("av_filter_compare")?></td>
		<td><?=GetMessage("av_filter_value")?></td>
		<td style="width:20px;"></td>
	</tr>
	<?//=str_replace("#n#","1",$strRowWhere) // first line?>
</table>
<table OnClick="RowInsertW()" style="cursor:pointer";>
	<tr>
		<td><div title="<?=GetMessage("av_filter_add")?>" id="btn_new" style="width:20;height:20; background-image: url('/bitrix/themes/.default/images/buttons/new.gif');"></div></td>
		<td><?=GetMessage("av_filter_add")?></td>
	</tr>
</table>
</div>
<?
}
else
{
	C_AV_IBlock_Manage::showError(GetMessage("av_update_page"));
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_after.php");?>
