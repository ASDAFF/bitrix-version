<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
if ($arResult["NEED_AUTH"] == "Y")
	$APPLICATION->AuthForm("");
elseif (strlen($arResult["FatalError"]) > 0)
{
	?><span class='errortext'><?=$arResult["FatalError"]?></span><br /><br /><?
}
else
{
	if(strlen($arResult["ErrorMessage"])>0)
	{
		?><span class='errortext'><?=$arResult["ErrorMessage"]?></span><br /><br /><?
	}

	if ($arResult["ShowForm"] == "Input")
	{
		?>
		<form method="post" name="form1" id="sonet-features-form" action="<?=POST_FORM_ACTION_URI?>" enctype="multipart/form-data">
				<div class="settings-group-main-wrap">
					<table class="settings-group" cellspacing="0">
						<tr>

							<td id="settings-items" class="settings-left-column">
								<?
								$hasActiveFeatures = false;
								$firstRun = true;
								foreach ($arResult["Features"] as $feature => $arFeature): 
									if ($arFeature["Active"] || $arResult["ENTITY_TYPE"] == "U"):
									?>
									<a href="#" class="settings-item-block <?=($firstRun) ? 'settings-item-active' : ''?>">
										<?=array_key_exists("title", $GLOBALS["arSocNetFeaturesSettings"][$feature]) && strlen($GLOBALS["arSocNetFeaturesSettings"][$feature]["title"]) > 0 ? $GLOBALS["arSocNetFeaturesSettings"][$feature]["title"] : GetMessage("SONET_FEATURES_".$feature)?>
										<span class="settings-item-left"></span>
										<span class="settings-item-right"></span>
									</a>
									<?
									$hasActiveFeatures = true;
									$firstRun = false;
									endif;
								endforeach;
								?>
							</td>

							<td id="settings-blocks" class="settings-right-column">
								<?
								$firstRun = true;
								foreach ($arResult["Features"] as $feature => $arFeature):

									if ($arFeature["Active"] || $arResult["ENTITY_TYPE"] == "U"):
									?>
										<div class="settings-blocks-enable-wrap" id="<?= $feature ?>_checkbox" style="<?=($firstRun) ? '' : 'display:none'?>">
											<?
											if ($arResult["ENTITY_TYPE"] == "U" && !($feature == "blog" && $arParams["PAGE_ID"] != "group_features")):
											?>
												<script type="text/javascript">
													<!--
													BX.message({
														sonetF_<?=$feature?>_on: '<?=CUtil::JSEscape(str_replace("#NAME#", (array_key_exists("title", $GLOBALS["arSocNetFeaturesSettings"][$feature]) && strlen($GLOBALS["arSocNetFeaturesSettings"][$feature]["title"]) > 0 ? $GLOBALS["arSocNetFeaturesSettings"][$feature]["title"] : GetMessage("SONET_FEATURES_".$feature)) , GetMessage("SONET_C4_FUNC_TITLE_ON")))?>',
														sonetF_<?=$feature?>_off: '<?=CUtil::JSEscape(str_replace("#NAME#", (array_key_exists("title", $GLOBALS["arSocNetFeaturesSettings"][$feature]) && strlen($GLOBALS["arSocNetFeaturesSettings"][$feature]["title"]) > 0 ? $GLOBALS["arSocNetFeaturesSettings"][$feature]["title"] : GetMessage("SONET_FEATURES_".$feature)) , GetMessage("SONET_C4_FUNC_TITLE_OFF")))?>'
													});	
													//-->
												</script>
												<div class="settings-block-enable">
													<div class="settings-right-enable-label-wrap">
														<label for="<?= $feature ?>_active_id" style="width:100%" id="<?= $feature ?>_lbl"><?= str_replace("#NAME#", (array_key_exists("title", $GLOBALS["arSocNetFeaturesSettings"][$feature]) && strlen($GLOBALS["arSocNetFeaturesSettings"][$feature]["title"]) > 0 ? $GLOBALS["arSocNetFeaturesSettings"][$feature]["title"] : GetMessage("SONET_FEATURES_".$feature)) , GetMessage("SONET_C4_FUNC_TITLE_".($arFeature["Active"] ? "ON" : "OFF"))) ?></label>:
													</div>
													<div class="settings-block-enable-checkbox-wrap">
														<input type="checkbox" id="<?= $feature ?>_active_id" name="<?= $feature ?>_active" value="Y"<?= ($arFeature["Active"] ? " checked" : "") ?> onclick="toggleInternalBlock(this.checked, '<?= $feature ?>')">
													</div>
												</div>
											<?
											else:
											?>
												<input type="hidden" name="<?= $feature ?>_active" value="Y" />
											<?
											endif;
											?>
										</div>

										<div id="<?= $feature ?>_block" class="settings-blocks-wrap" style="<?=($firstRun && $arFeature["Active"]) ? '' : 'display:none'?>">
											<?
											if ($firstRun && $arFeature["Active"])
												$firstRun = false;
											?>
											<div class="settings-right-block">
												<div class="settings-right-block-text">
													<?=GetMessage("SONET_FEATURES_NAME")?>:
												</div>
												<input type="text" name="<?= $feature ?>_name" value="<?= $arFeature["FeatureName"] ?>">
											</div>

											<? if (isset($arFeature["note"])): ?>
												<div class="settings-blocks-note">
													<?=htmlspecialcharsbx($arFeature['note'])?>
												</div>
											<? endif;

											if (!array_key_exists("hide_operations_settings", $GLOBALS["arSocNetFeaturesSettings"][$feature])
												|| !$GLOBALS["arSocNetFeaturesSettings"][$feature]["hide_operations_settings"])
											{
												foreach ($arFeature["Operations"] as $operation => $perm):
												?>
													<div class="settings-right-block">
														<?
														if ($feature == "tasks" && ($operation == "modify_folders" || $operation === 'modify_common_views' ) && COption::GetOptionString("intranet", "use_tasks_2_0", "N") == "Y"):
														?>
															<input type="hidden" name="<?= $feature ?>_<?= $operation ?>_perm" value="<?=$perm?>">
														<?
														else:
															$title = (array_key_exists("operation_titles", $GLOBALS["arSocNetFeaturesSettings"][$feature]) && array_key_exists($operation, $GLOBALS["arSocNetFeaturesSettings"][$feature]["operation_titles"]) && strlen($GLOBALS["arSocNetFeaturesSettings"][$feature]["operation_titles"][$operation]) > 0 ? $GLOBALS["arSocNetFeaturesSettings"][$feature]["operation_titles"][$operation] : GetMessage("SONET_FEATURES_".$feature."_".$operation));
														?>
															<div class="settings-right-block-text">
																<?=$title?>:
															</div>
															<select name="<?= $feature ?>_<?= $operation ?>_perm">
																<?foreach ($arResult["PermsVar"] as $key => $value):
																	if (
																		!array_key_exists("restricted", $GLOBALS["arSocNetFeaturesSettings"][$feature]["operations"][$operation]) 
																		|| !in_array($key, $GLOBALS["arSocNetFeaturesSettings"][$feature]["operations"][$operation]["restricted"][$arResult["ENTITY_TYPE"]])
																	):
																		?><option value="<?= $key ?>"<?= ($key == $perm) ? " selected" : "" ?>><?= $value ?></option><?
																	endif;
																endforeach;?>
															</select>
														<?
														endif;
														?>
													</div>
												<?
												endforeach;
											}
											?>
										</div>
									<?
									endif;
								endforeach;
								?>
							</td>
						</tr>

						<? if ($hasActiveFeatures): ?>
						<tr>
							<td colspan="2" class="settings-footer-buttons">
								<div class="settings-footer-buttons-block">
									<a id="sonet_group_create_popup_form_button_submit" class="webform-small-button webform-small-button-accept" href="">
										<span class="webform-small-button-left"></span><span class="webform-small-button-text"><?= GetMessage("SONET_C4_SUBMIT") ?></span><span class="webform-small-button-right"></span>
									</a>
								</div>
							</td>
						</tr>
						<? else: ?>
							<?=GetMessage("SONET_C4_NO_FEATURES")?>
						<? endif; ?>
					</table>

				</div>
				<br><br>
			<input type="hidden" name="SONET_USER_ID" value="<?= $arParams["USER_ID"] ?>">
			<input type="hidden" name="SONET_GROUP_ID" value="<?= $arParams["GROUP_ID"] ?>">
			<?=bitrix_sessid_post()?>
		</form>
		<script type="text/javascript">
		<!--
			var items_length = BX.findChildren(BX('settings-items'), {className:'settings-item-block'}, true);

			if (items_length){

				for(var i=0; i<items_length.length; i++){
					BX.bind(items_length[i], 'click', toggleSettingsBlock)
				}

				BX.bind(BX("sonet_group_create_popup_form_button_submit"), "click", BXSFSubmitForm);
			}

			function toggleSettingsBlock(){

				var  blocks = BX.findChildren(BX('settings-blocks'), {className:'settings-blocks-wrap'}, true);
				var  checkboxes = BX.findChildren(BX('settings-blocks'), {className:'settings-blocks-enable-wrap'}, true);

				for(var i=0; i < items_length.length; i++)
				{
					BX.hide(blocks[i]);
					BX.hide(checkboxes[i]);

					if (items_length[i] == this)
					{
						BX.show(checkboxes[i]);

						var featureId = checkboxes[i].id.split("_")[0];
						var cb = BX(featureId + '_active_id');

						if ((cb && cb.checked) || cb == null)
							BX.show(blocks[i]);
					}

					BX.removeClass(items_length[i], 'settings-item-active');
				}

				BX.addClass(this, 'settings-item-active');
			}

			function toggleInternalBlock(chk, type){

				var el = BX(type + "_body");
				if (el)
					BX.toggle(el);

				var controlsBlock = BX(type + '_block');
				if (controlsBlock)
					BX.toggle(controlsBlock);
				
				var el = BX(type + "_lbl");

				if (el){
					if (chk)
						el.innerHTML = BX.message('sonetF_' + type + '_on');
					else
						el.innerHTML = BX.message('sonetF_' + type + '_off');
				}
			}
		//-->
		</script>
		<?
	}
	else
	{
		if ($arParams["PAGE_ID"] == "group_features"):?>
			<?= GetMessage("SONET_C4_GR_SUCCESS") ?>
			<br><br>
			<a href="<?= $arResult["Urls"]["Group"] ?>"><?= $arResult["Group"]["NAME"]; ?></a><?
		else:
			?><?= GetMessage("SONET_C4_US_SUCCESS") ?>
			<br><br>
			<a href="<?= $arResult["Urls"]["User"] ?>"><?= $arResult["User"]["NAME_FORMATTED"]; ?></a><?
		endif;
	}
}
?>