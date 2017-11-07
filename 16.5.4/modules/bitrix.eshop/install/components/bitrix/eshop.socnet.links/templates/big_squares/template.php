<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

$this->setFrameMode(true);

if (is_array($arResult["SOCSERV"]) && !empty($arResult["SOCSERV"]))
{
?>
<div class="bx-socialfooter">
	<ul class="row">
		<?foreach($arResult["SOCSERV"] as $socserv):?>
		<li class="col-xs-2"><a class="<?=htmlspecialcharsbx($socserv["CLASS"])?> bx-socialfooter-icon" target="_blank" href="<?=htmlspecialcharsbx($socserv["LINK"])?>"></a></li>
		<?endforeach?>
	</ul>
</div>
<?
}
?>