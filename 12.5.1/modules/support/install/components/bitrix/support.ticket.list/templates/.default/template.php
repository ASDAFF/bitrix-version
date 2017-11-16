<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$bDemo = (CTicket::IsDemo()) ? "Y" : "N";
$bAdmin = (CTicket::IsAdmin()) ? "Y" : "N";
$bSupportTeam = (CTicket::IsSupportTeam()) ? "Y" : "N";
$bADS = $bDemo == 'Y' || $bAdmin == 'Y' || $bSupportTeam == 'Y';

?>

<form method="post" action="<?=$arResult["NEW_TICKET_PAGE"]?>">
	<input type="submit" name="add" value="<?=GetMessage("SUP_ASK")?>">
</form>

<br />

<form action="<?=$arResult["CURRENT_PAGE"]?>" method="get">
<table cellspacing="0" class="support-ticket-filter data-table">
	<tr>
		<th colspan="2"><?=GetMessage("SUP_F_FILTER")?></th>
	</tr>
	<tr>
		<td><?=GetMessage("SUP_F_ID")?>:</td>
		<td>
			<input type="text" name="find_id" size="20" value="<?=htmlspecialcharsbx($_REQUEST["find_id"])?>" />
			<input type="checkbox" name="find_id_exact_match" value="Y" title="<?=GetMessage("SUP_EXACT_MATCH")?>" <?
				if(isset($_REQUEST["find_id_exact_match"]) && $_REQUEST["find_id_exact_match"] == "Y"):?>checked="checked" <?endif?>/>
		</td>
	</tr>
	<tr>
		<td><?=GetMessage("SUP_F_LAMP")?>:</td>
		<td>
		<?
		$arLamp = Array(
			"red" => GetMessage("SUP_RED"), 
			"green" => GetMessage("SUP_GREEN"),
			"grey" => GetMessage("SUP_GREY")
		);
		?>
		<select multiple="multiple" name="find_lamp[]" id="find_lamp" size="3">
		<?foreach ($arLamp as $value => $option):?>
				<option value="<?=$value?>" <?if(is_array($_REQUEST["find_lamp"]) && in_array($value, $_REQUEST["find_lamp"])):?>selected="selected"<?endif?>><?=$option?></option>
		<?endforeach?>
		</select>
		</td>
	</tr>
	<tr>
		<td><?=GetMessage("SUP_F_CLOSE")?>:</td>
		<td>
			<?
				$arOpenClose= Array(
					"Y" => GetMessage("SUP_CLOSED"),
					"N" => GetMessage("SUP_OPENED"), 
				);
			?>
			<select name="find_close" id="find_close">
				<option value=""><?=GetMessage("SUP_ALL")?></option>
			<?foreach ($arOpenClose as $value => $option):?>
				<option value="<?=$value?>" <?if(isset($_REQUEST["find_close"]) && $_REQUEST["find_close"] == $value):?>selected="selected"<?endif?>><?=$option?></option>
			<?endforeach?>
			</select>
		</td>
	</tr>
	<tr>
		<td><?=GetMessage("SUP_TITLE")?>:</td>
		<td>
		<input type="text" name="find_title" size="40" value="<?=htmlspecialcharsbx($_REQUEST["find_title"])?>" />
		<input type="checkbox" name="find_title_exact_match" value="Y" title="<?=GetMessage("SUP_EXACT_MATCH")?>" <?
				if(isset($_REQUEST["find_title_exact_match"]) && $_REQUEST["find_title_exact_match"] == "Y"):?>checked="checked" <?endif?>/>
		</td>
	</tr>
	<tr>
		<td><?=GetMessage("SUP_F_MESSAGE")?>:</td>
		<td>
			<input type="text" name="find_message" size="40" value="<?=htmlspecialcharsbx($_REQUEST["find_message"])?>" />
			<input type="checkbox" name="find_message_exact_match" value="Y" title="<?=GetMessage("SUP_EXACT_MATCH")?>" <?
				if(isset($_REQUEST["find_message_exact_match"]) && $_REQUEST["find_message_exact_match"] == "Y"):?>checked="checked" <?endif?>/>
		</td>
	</tr>
	<tr>
		<th colspan="2">
			<input name="set_filter" value="<?=GetMessage("SUP_F_SET_FILTER")?>" type="submit" />&nbsp;&nbsp;
			<input name="del_filter" value="<?=GetMessage("SUP_F_DEL_FILTER")?>" type="submit" />
			<input name="set_filter" value="Y" type="hidden" />
		</th>
	</tr>
</table>
</form>

<br />

<?if (strlen($arResult["NAV_STRING"]) > 0):?>
	<?=$arResult["NAV_STRING"]?><br /><br />
<?endif?>

<table cellspacing="0" class="support-ticket-list data-table">
	
	<tr>
		<th>
			<?=GetMessage("SUP_ID")?><?=SortingEx("s_id")?><br />
			<?=GetMessage("SUP_LAMP")?><?=SortingEx("s_lamp")?><br />
		</th>
		<th>
			<?=GetMessage("SUP_TITLE")?>
		</th>
		<th>
			<?=GetMessage("SUP_TIMESTAMP")?><?=SortingEx("s_timestamp")?><br />
			<?=GetMessage("SUP_MODIFIED_BY")?><br />
		</th>
		<th>
			<?=GetMessage("SUP_MESSAGES")?>
		</th>
		<th>
			<?=GetMessage("SUP_STATUS")?><br />
		</th>
		
		<?
		$ColAdd = 0;
		if( isset( $arParams["SET_SHOW_USER_FIELD_T"] ) )
		{
			foreach( $arParams["SET_SHOW_USER_FIELD_T"] as $k => $v )
			{
				echo "<th>" . htmlspecialcharsbx( $v["NAME_C"] ) . "<br /></th>";
				$ColAdd++;
			}
		}
		?>
		
	</tr>

	<?foreach ($arResult["TICKETS"] as $arTicket):?>
	<tr>
		
		<td width="10%" align="center">
			<?=$arTicket["ID"]?><br />
			<div class="support-lamp-<?=str_replace("_","-",$arTicket["LAMP"])?>" title="<?=GetMessage("SUP_".strtoupper($arTicket["LAMP"]).($bADS ? "_ALT_SUP" : "_ALT"))?>"></div>
			[&nbsp;<a href="<?=$arTicket["TICKET_EDIT_URL"]?>" title="<?=GetMessage("SUP_EDIT_TICKET")?>"><?=GetMessage("SUP_EDIT")?></a>&nbsp;]
		</td>

		
		<td>
			<a href="<?=$arTicket["TICKET_EDIT_URL"]?>" title="<?=GetMessage("SUP_VIEW_TICKET")?>"><?=$arTicket["TITLE"]?></a>
		</td>

		<td>
			<?=FormatDate($DB->DateFormatToPHP(CSite::GetDateFormat('FULL')), MakeTimeStamp($arTicket["TIMESTAMP_X"]))?><br />

			<?if (strlen($arTicket["MODIFIED_MODULE_NAME"])<=0 || $arTicket["MODIFIED_MODULE_NAME"]=="support"):?>
				[<?=$arTicket["MODIFIED_USER_ID"]?>] (<?=$arTicket["MODIFIED_LOGIN"]?>) <?=$arTicket["MODIFIED_NAME"]?>
			<?else:?>
				<?=$arTicket["MODIFIED_MODULE_NAME"]?>
			<?endif?>

		</td>

		<td>
			<?=$arTicket["MESSAGES"]?>
		</td>

		
		<td>

		<?if (strlen($arTicket["STATUS_NAME"])>0):?>
			<?=$arTicket["STATUS_NAME"]?>
		<? endif; ?>
		
		</td>
		
		<?
		if( isset( $arParams["SET_SHOW_USER_FIELD_T"] ) )
		{
			foreach( $arParams["SET_SHOW_USER_FIELD_T"] as $k => $v )
			{
				echo "<td>" . htmlspecialcharsbx( $arTicket[$k] ) . "</td>";
			}
		}
		?>
		
	</tr>
	<?endforeach?>


	
	<tr>
		<th colspan="<? echo (5 + $ColAdd ) ; ?>"><?=GetMessage("SUP_TOTAL")?>: <?=$arResult["TICKETS_COUNT"]?></th>
	</tr>
</table>

<?if (strlen($arResult["NAV_STRING"]) > 0):?>
	<br /><?=$arResult["NAV_STRING"]?><br />
<?endif?>

<br />
<table class="support-ticket-hint">
	<tr>
		<td><div class="support-lamp-red"></div></td>
		<td> - <?=$bADS ? GetMessage("SUP_RED_ALT_SUP") : GetMessage("SUP_RED_ALT_2")?></td>
	</tr>
	<?if ($bADS):?>
	<tr>
		<td><div class="support-lamp-yellow"></div></td>
		<td> - <?=GetMessage("SUP_YELLOW_ALT_SUP")?></td>
	</tr>
	<?endif;?>
	<tr>
		<td><div class="support-lamp-green"></div></td>
		<td> - <?=GetMessage("SUP_GREEN_ALT")?></td>
	</tr>
	<?if ($bADS):?>
	<tr>
		<td><div class="support-lamp-green-s"></div></td>
		<td> - <?=GetMessage("SUP_GREEN_S_ALT_SUP")?></td>
	</tr>
	<?endif;?>
	<tr>
		<td><div class="support-lamp-grey"></div></td>
		<td> - <?=GetMessage("SUP_GREY_ALT")?></td>
	</tr>
</table>