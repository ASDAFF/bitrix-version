<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
IncludeTemplateLangFile(__FILE__);
?>
					</div> <!-- // .workarea -->
				<?if ($wizTemplateId == "eshop_horizontal"):?>
					<div class="sidebar pright">
						<?$APPLICATION->ShowViewContent("right_sidebar")?>
						<?$APPLICATION->IncludeComponent(
							"bitrix:main.include",
							"",
							Array(
								"AREA_FILE_SHOW" => "sect",
								"AREA_FILE_SUFFIX" => "sidebar",
								"AREA_FILE_RECURSIVE" => "N",
								"EDIT_MODE" => "html",
							),
							false,
							Array('HIDE_ICONS' => 'Y')
						);?>
					</div>
				<?endif?>
				</div> <!-- // .centralarea fll -->
			</div><!-- // .content_box -->
	</div><!-- // .body -->

	<div class="blog_box">
		<table>
			<tr>
				<td class="about">
					<h4><?$APPLICATION->IncludeComponent("bitrix:main.include", "", array("AREA_FILE_SHOW" => "file", "PATH" => SITE_DIR."include/company_name.php"), false);?></h4>
					<p><?$APPLICATION->IncludeComponent("bitrix:main.include", "", array("AREA_FILE_SHOW" => "file", "PATH" => SITE_DIR."include/company_about.php"), false);?></p>
					<br/><br/>
					<a href="<?=SITE_DIR?>about/"><?=GetMessage("FOOTER_COMPANY_ABOUT")?></a>
				</td>
				<td class="news">
					<?$APPLICATION->IncludeComponent(
						"bitrix:main.include",
						"",
						Array(
							"AREA_FILE_SHOW" => "file",
							"PATH" => SITE_DIR."include/news.php",
							"AREA_FILE_RECURSIVE" => "N",
							"EDIT_MODE" => "html",
						),
						false,
						Array('HIDE_ICONS' => 'Y')
					);?>
				</td>
				<?if ($shopFacebook = COption::GetOptionString("eshop", "shopFacebook", "", SITE_ID)):?>
				<td class="facebook">
					<div id="fb-root"></div>
					<script>(function(d, s, id) {
						var js, fjs = d.getElementsByTagName(s)[0];
						if (d.getElementById(id)) return;
						js = d.createElement(s); js.id = id;
						js.src = "//connect.facebook.net/<?=GetMessage("FOOTER_FACEBOOK_PLUGIN_SET")?>/all.js#xfbml=1"; 
						fjs.parentNode.insertBefore(js, fjs);
					}(document, 'script', 'facebook-jssdk'));</script>
					<h4><?=GetMessage("FOOTER_SOCNET")?></h4>
					<div class="fb-like-box" data-href="<?=$shopFacebook?>" data-width="230" data-show-faces="true" data-stream="false" data-header="true"></div>
				</td>
				<?endif?>
			</tr>
		</table>
	</div>

	<div class="footer">
		<table>
			<tr>
				<td rowspan="2" class="about">
					<h4><?=GetMessage("FOOTER_ABOUT")?></h4>
					<?$APPLICATION->IncludeComponent("bitrix:menu", "bottom", array(
							"ROOT_MENU_TYPE" => "bottom",
							"MAX_LEVEL" => "1",
							"MENU_CACHE_TYPE" => "A",
							"MENU_CACHE_TIME" => "36000000",
							"MENU_CACHE_USE_GROUPS" => "Y",
							"MENU_CACHE_GET_VARS" => array(
							),
						),
						false
					);?>
				</td>
				<td rowspan="2" class="cat_link">
					<h4><?=GetMessage("FOOTER_CATALOG")?></h4>
					<?$APPLICATION->IncludeComponent("bitrix:menu", "bottom", array(
						"ROOT_MENU_TYPE" => "left",
						"MENU_CACHE_TYPE" => "A",
						"MENU_CACHE_TIME" => "36000000",
						"MENU_CACHE_USE_GROUPS" => "Y",
						"MENU_CACHE_GET_VARS" => array(
						),
						"MAX_LEVEL" => "1",
						"USE_EXT" => "Y",
						"DELAY" => "N",
						"ALLOW_MULTI_SELECT" => "N"
					),
					false
					);?>
				</td>
				<td class="contact">
					<div class="social">
						<?if ($shopFacebook):?>
						<a href="<?=$shopFacebook?>" class="fb"></a>
						<?endif?>
						<?if ($shopTwitter = COption::GetOptionString("eshop", "shopTwitter", "", SITE_ID)):?>
						<a href="<?=$shopTwitter?>" class="tw"></a>
						<?endif?>
						<?if ($shopGooglePlus = COption::GetOptionString("eshop", "shopGooglePlus", "", SITE_ID)):?>
						<a href="<?=$shopGooglePlus?>" class="gp"></a>
						<?endif?>
						<?if (LANGUAGE_ID=="ru" && $shopVk = COption::GetOptionString("eshop", "shopVk", "", SITE_ID)):?>
						<a href="<?=$shopVk?>" class="vk"></a>
						<?endif?>
					</div>
					<?=GetMessage("FOOTER_CONTACT")?> <br/>
					<span><?$APPLICATION->IncludeComponent("bitrix:main.include", "", array("AREA_FILE_SHOW" => "file", "PATH" => SITE_DIR."include/telephone.php"), false);?></span>
				</td>
			</tr>
			<tr>
				<td class="copyright"><?$APPLICATION->IncludeComponent("bitrix:main.include", "", array("AREA_FILE_SHOW" => "file", "PATH" => SITE_DIR."include/copyright.php"), false);?></td>
			</tr>
		</table>
	</div>
</div><!-- wrap -->

<?
$arFilter = Array("TYPE"=>"catalog", "SITE_ID"=>SITE_ID);
$obCache = new CPHPCache;
if($obCache->InitCache(36000, serialize($arFilter), "/iblock/catalog/active"))
{
	$arIBlocks = $obCache->GetVars();
}
elseif(CModule::IncludeModule("iblock") && $obCache->StartDataCache())
{

	$arIBlocks = array();
	$dbRes = CIBlock::GetList(Array(), $arFilter);
	$dbRes = new CIBlockResult($dbRes);

	if(defined("BX_COMP_MANAGED_CACHE"))
	{
		global $CACHE_MANAGER;
		$CACHE_MANAGER->StartTagCache("/iblock/catalog/active");

		while($arIBlock = $dbRes->GetNext())
		{
			$CACHE_MANAGER->RegisterTag("iblock_id_".$arIBlock["ID"]);

			if($arIBlock["ACTIVE"] == "Y")
				$arIBlocks[$arIBlock["ID"]] = $arIBlock;
		}

		$CACHE_MANAGER->RegisterTag("iblock_id_new");
		$CACHE_MANAGER->EndTagCache();
	}
	else
	{
		while($arIBlock = $dbRes->GetNext())
		{
			if($arIBlock["ACTIVE"] == "Y")
				$arIBlocks[$arIBlock["ID"]] = $arIBlock;
		}
	}

	$obCache->EndDataCache($arIBlocks);
}
else
{
	$arIBlocks = array();
}

if(count($arIBlocks) == 1)
{

	foreach($arIBlocks as $iblock_id => $arIBlock)
	{
		if ($APPLICATION->GetProperty("CATALOG_COMPARE_LIST", false) == false && IsModuleInstalled('iblock'))
		{
			$APPLICATION->IncludeComponent(
				"bitrix:catalog.compare.list",
				"eshop",
				Array(
					"IBLOCK_ID" => $iblock_id,
					"COMPARE_URL" => $arIBlock["LIST_PAGE_URL"]."compare/"
				),
				false,
				Array("HIDE_ICONS" => "Y")
			);
		}
	}
}
?>
<!-- Additional Params -->
<div class="modal" id="addItemInCompare">
	<h4><?=GetMessage("FOOTER_COMPARE_ADD")?></h4>
	<div class="item_img"><img src="" alt=""/></div>
	<div class="item_title"></div>
	<br>
	<a href="<?=$arIBlock["LIST_PAGE_URL"]."compare/"?>" class="bt3"><?=GetMessage("FOOTER_COMPARE_LIST")?></a>
	<a href="javascript:void(0)" class="close" style="margin-left: 10px;font-size: 14px;color: #888;"><?=GetMessage("FOOTER_GOTO_BACK")?></a>
	<div class="close button"></div>
</div>
<div class="modal" id="addItemInCart">
	<h4><?=GetMessage("FOOTER_ADD_TO_BASKET")?></h4>
	<div class="item_img"><img src="" alt=""/></div>
	<div class="item_title"></div>
	<br/>
	<a href="<?=SITE_DIR."personal/cart/"?>" class="bt3"><?=GetMessage("FOOTER_GOTO_ORDER")?></a>
	<a href="javascript:void(0)" class="close" style="margin-left: 10px;font-size: 14px;color: #888;"><?=GetMessage("FOOTER_GOTO_BACK")?></a>
	<div class="close button"></div>
</div>
<div class="modal" id="addItemInSubscribe">
	<h4><?=GetMessage("FOOTER_SUBSCRIBE_ADD")?></h4>
	<div class="item_img"><img src="" alt=""/></div>
	<div class="item_title"></div>
	<br>
	<a href="javascript:void(0)" class="close" style="margin-left: 10px;font-size: 14px;color: #888;"><?=GetMessage("FOOTER_GOTO_BACK")?></a>
	<div class="close button"></div>
</div>
<!-- SKU-->
<div class="modal" id="addItemInCartOptions">
	<table>
		<tr>
			<td class="item_img" rowspan="2"><img src="" alt=""/></td>
			<td class="item_title tal"></td>
			<td rowspan="2" class="vat" style="padding-top: 15px;">
				<span class="item_price tar fwb" id="listItemPrice"></span>
			</td>
		</tr>	
	</table>
	<hr/>
	<form name="buy_form_list">
		<div class="choosePropsTitle"><?=GetMessage("FOOTER_SKU_PROPS")?></div>
		<table class="options" id="sku_selectors_list">
			<tbody></tbody>
		</table>
	</form>
	<br/>
	<span id="element_buy_button"></span>
	<a href="javascript:void(0)" onclick="$('#addItemInCartOptions').css({'display':'none'});" class="bt2"><?=GetMessage("FOOTER_CANCEL")?></a>
	<div class="close button"></div>
</div>
<!-- SUBSCRIBE -->
<?if (!$USER->IsAuthorized()):?>
	<div  class="modal login_window" id="popupFormSubscribe"style="display: none;">
		<input type="hidden" value="" name="popup_notify_url" id="popup_notify_url">
		<div id="popup_n_error" style="color:red;"></div>
		<div id="notify_user_email">
			<p style="color:#a3a3a3;font-size: 16px;padding: 20px;border-bottom: 1px solid #d7d7d7;margin-bottom: 21px;width:250px;display:block;margin:0 30px;"><?=GetMessage('NOTIFY_TITLE');?></p>
			<p style="color:#000;font-size:16px;font-weight: bold;"><?=GetMessage('NOTIFY_POPUP_MAIL');?></p>
			<input type="text" value="" name="popup_user_email" id="popup_user_email" class="input_text_style"><br><br>

			<?if($arResult["CAPTCHA_CODE"]):?>
			<tr>
				<td></td>
				<td><input type="hidden" name="popup_captcha_sid" id="popup_captcha_sid" value="<?echo $arResult["CAPTCHA_CODE"]?>" />
					<span id="popup_captcha_img">
						<img src="/bitrix/tools/captcha.php?captcha_sid=<?echo $arResult["CAPTCHA_CODE"]?>" width="180" height="40" alt="CAPTCHA" /></td>
				</span>
			</tr>
			<tr>
				<td class="bx-auth-label"><?=GetMessage('NOTIFY_POPUP_CAPTHA');?></td>
				<td><input class="bx-auth-input" type="text" name="popup_captcha_word" class="input_text_style" id="popup_captcha_word" maxlength="50" value="" style="width:250px"/></td>
			</tr>
			<?endif;?>
			<a href="javascript:void(0)" onClick="showAuthForm();"><?=GetMessage('NOTIFY_POPUP_AUTH');?></a>
		</div>
		<div id="notify_auth_form" style="display: none;">
			<?$APPLICATION->IncludeComponent("bitrix:system.auth.authorize", "subscribe", array(),false);?>
		</div>
		<input id="notify_user_auth" type="hidden" name="notify_user_auth" value="N" >

		
		<hr style="border-bottom: 1px solid #d7d7d7"/>		<br>
		<a href="javascript:void(0)" class="bt3" onclick="var error = 'N';
				var useCaptha = 'N';
				BX('popup_n_error').innerHTML = '';
				var data = 'sessid='+BX.bitrix_sessid()+'&ajax=Y';

				if (BX('notify_user_auth').value == 'Y')
				{
					data = data + '&user_auth=Y&user_login='+BX('notify_user_login').value+'&user_password='+BX('notify_user_password').value;
				}
				else
				{
					var reg = /@/i;
					if(BX('popup_user_email').value.length == 0 || !reg.test(BX('popup_user_email').value))
					{
						BX('popup_n_error').innerHTML = '<?=GetMessage("NOTIFY_POPUP_MAIL_ERR");?>';
						error = 'Y';
						$('#popup_n_error').css('display', 'inline-block');
					}
					else
					{
					data = data + '&user_mail='+BX('popup_user_email').value;

					if (BX('popup_captcha_sid') && BX('popup_captcha_word'))
					{
					data = data + '&captcha_sid='+BX('popup_captcha_sid').value;
					data = data + '&captcha_word='+BX('popup_captcha_word').value;
					useCaptha = 'Y';
					}
					}
				}// end if notify_user_auth

				if (error == 'N')
				{
				BX.showWait();

				BX.ajax.post('/bitrix/components/bitrix/sale.notice.product/ajax.php', data, function(res) {
					BX.closeWait();

					var rs = eval( '('+res+')' );

					if (rs['ERRORS'].length > 0)
					{
						$('#popup_n_error').css('display', 'inline-block');
						if (rs['ERRORS'] == 'NOTIFY_ERR_LOGIN')
						BX('popup_n_error').innerHTML = '<?=GetMessage('NOTIFY_ERR_LOGIN')?>';
						else if (rs['ERRORS'] == 'NOTIFY_ERR_MAIL')
						BX('popup_n_error').innerHTML = '<?=GetMessage('NOTIFY_ERR_MAIL')?>';
						else if (rs['ERRORS'] == 'NOTIFY_ERR_CAPTHA')
						BX('popup_n_error').innerHTML = '<?=GetMessage('NOTIFY_ERR_CAPTHA')?>';
						else if (rs['ERRORS'] == 'NOTIFY_ERR_MAIL_EXIST')
						BX('popup_n_error').innerHTML = '<?=GetMessage('NOTIFY_ERR_MAIL_EXIST')?>';
						else if (rs['ERRORS'] == 'NOTIFY_ERR_REG')
						BX('popup_n_error').innerHTML = '<?=GetMessage('NOTIFY_ERR_REG')?>';
						else
						BX('popup_n_error').innerHTML = rs['ERRORS'];

						if (useCaptha == 'Y')
						{
						BX.ajax.get('/bitrix/components/bitrix/sale.notice.product/ajax.php?reloadcaptha=Y', '', function(res) {
						BX('popup_captcha_sid').value = res;
						BX('popup_captcha_img').innerHTML = '<img src=\'/bitrix/tools/captcha.php?captcha_sid='+res+'\' width=\'180\' height=\'40\' alt=\'CAPTCHA\' />';
						});
						}
					}
					else if (rs['STATUS'] == 'Y')
					{
						addProductToSubscribe(window.button, BX('popup_notify_url').value, window.subId);
						authPopup.close();
					}
				});
				}
		"><?=GetMessage("NOTIFY_POPUP_OK")?></a>
		<a href="javascript:void(0)" id="subscribeCancelButton" onclick="$('#popupFormSubscribe').css({'display':'none'});" class="bt2"><?=GetMessage("FOOTER_CANCEL")?></a>
		<a href="javascript:void(0)" id="subscribeBackButton" onClick="showUserEmail();" style="margin-left: 10px;font-size: 14px;color: #888; display: none;"><?=GetMessage('NOTIFY_BACK_BUTTON');?></a>
		<div class="close button"></div>
	</div>
	<?$APPLICATION->IncludeComponent("bitrix:system.auth.authorize", "eshop", array(),false);?>
<?endif?>
<div id="bgmod" class="close"></div>
<?$APPLICATION->IncludeComponent("bitrix:menu", "tree_horizontal_hidden", array(
		"ROOT_MENU_TYPE" => "left",
		"MENU_CACHE_TYPE" => "A",
		"MENU_CACHE_TIME" => "36000000",
		"MENU_CACHE_USE_GROUPS" => "Y",
		"MENU_CACHE_GET_VARS" => array(
		),
		"MAX_LEVEL" => "3",
		"USE_EXT" => "Y",
		"DELAY" => "N",
		"ALLOW_MULTI_SELECT" => "N"
	),
	false
);?>

<script type="text/javascript">
	$(function(){
		var startSlide = 1;
		if (window.location.hash) {
			startSlide = window.location.hash.replace('#','');
		}
		$('#slides').slides({
			preload: true,
			preloadImage: 'img/loading.gif',
			generatePagination: true,
			play: 5000, //~!!!
			pause: 2500, //~!!!
			hoverPause: true,
			start: startSlide,
			//animationComplete: function(current){
			//window.location.hash = '#' + current;
			//	}
		});
	});
</script>
</body>
</html>