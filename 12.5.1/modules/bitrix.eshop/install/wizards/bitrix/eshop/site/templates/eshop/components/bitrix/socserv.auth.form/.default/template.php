<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();
?>
<?
if($arParams["POPUP"]):
	//only one float div per page
	if(defined("BX_SOCSERV_POPUP"))
		return;
	define("BX_SOCSERV_POPUP", true);
	?>
<div style="display:none">
<div id="bx_auth_float" class="bx-auth-float">
<?endif?>

<?if(($arParams["~CURRENT_SERVICE"] <> '') && $arParams["~FOR_SPLIT"] != 'Y'):?>
	<script type="text/javascript">
		BX.ready(function(){BxShowAuthService('<?=CUtil::JSEscape($arParams["~CURRENT_SERVICE"])?>', '<?=$arParams["~SUFFIX"]?>')});
	</script>
<?endif?>
<?
if($arParams["~FOR_SPLIT"] == 'Y'):?>
	<div class="bx-auth-serv-icons">
		<?foreach($arParams["~AUTH_SERVICES"] as $service):?>
		<?
		if(($arParams["~FOR_SPLIT"] == 'Y') && (is_array($service["FORM_HTML"])))
			$onClickEvent = $service["FORM_HTML"]["ON_CLICK"];
		else
			$onClickEvent = "onclick=\"BxShowAuthService('".$service['ID']."', '".$arParams['SUFFIX']."')\"";
		?>
		<a title="<?=htmlspecialcharsbx($service["NAME"])?>" href="javascript:void(0)" <?=$onClickEvent?> id="bx_auth_href_<?=$arParams["SUFFIX"]?><?=$service["ID"]?>"><i class="bx-ss-icon <?=htmlspecialcharsbx($service["ICON"])?>"></i></a>
		<?endforeach?>
	</div>
<?endif;?>
	<div class="social">
	<form method="post" name="bx_auth_services<?=$arParams["SUFFIX"]?>" target="_top" action="<?=$arParams["AUTH_URL"]?>">
		<?if($arParams["~FOR_SPLIT"] != 'Y'):?>
			<ul class="lsnn">
			<?
				$countServices = count($arParams["~AUTH_SERVICES"]);
				$curCountServices = 0;
				?>
				<?foreach($arParams["~AUTH_SERVICES"] as $service):?>
					<?if ($curCountServices == 6 && $countServices > 6):?>
						<li class="<?if ($countServices-$curCountServices < 7) echo "not"?>full all"><a href="javascript:void(0)" class=""><span></span></a>
							<ul class="lsnn">
					<?endif?>
					<li>
						<a href="javascript:void(0)"  class="social-eshop" onclick="BxShowAuthService('<?=$service["ID"]?>', '<?=$arParams["SUFFIX"]?>')" id="bx_auth_href_<?=$arParams["SUFFIX"]?><?=$service["ID"]?>"><span class="<?=htmlspecialcharsbx($service["ICON"])?>"></span></a>
					</li>
					<?
					$curCountServices++;
					?>
				<?endforeach?>
				<?if ($curCountServices > 6 ):?>
							</li>
						</ul>
				<?endif?>
			</ul>
		<?endif;?>
		<?if($arParams["~AUTH_LINE"] != 'N'):?>
			<div class="bx-auth-line"></div>
		<?endif;?>
		<div class="bx-auth-service-form" id="bx_auth_serv<?=$arParams["SUFFIX"]?>" style="display:none">
			<?foreach($arParams["~AUTH_SERVICES"] as $service):?>
				<?if(($arParams["~FOR_SPLIT"] != 'Y') || (!is_array($service["FORM_HTML"]))):?>
					<div id="bx_auth_serv_<?=$arParams["SUFFIX"]?><?=$service["ID"]?>" style="display:none"><?=$service["FORM_HTML"]?></div>
				<?endif;?>
			<?endforeach?>
		</div>
		<?foreach($arParams["~POST"] as $key => $value):?>
			<input type="hidden" name="<?=$key?>" value="<?=$value?>" />
		<?endforeach?>
		<input type="hidden" name="auth_service_id" value="" />
		<?if(!$arParams["FORIE"]):?>
	</form>
		<?endif;?>
	</div>

<?if($arParams["POPUP"]):?>
</div>
</div>
<?endif?>