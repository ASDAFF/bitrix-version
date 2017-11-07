<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<script type="text/javascript">
BX.message({
	"MPL_HAVE_WRITTEN" : "<?=GetMessageJS("MPL_HAVE_WRITTEN")?>",
	"B_B_MS_LINK" : "<?=GetMessageJS("B_B_MS_LINK")?>",
	"MPL_MES_HREF" : "<?=GetMessageJS("MPL_MES_HREF")?>",
	"BPC_MES_EDIT" : "<?=GetMessageJS("BPC_MES_EDIT")?>",
	"BPC_MES_HIDE" : "<?=GetMessageJS("BPC_MES_HIDE")?>",
	"BPC_MES_SHOW" : "<?=GetMessageJS("BPC_MES_SHOW")?>",
	"BPC_MES_DELETE" : "<?=GetMessageJS("BPC_MES_DELETE")?>",
	"BPC_MES_DELETE_POST_CONFIRM" : "<?=GetMessageJS("BPC_MES_DELETE_POST_CONFIRM")?>",
	"MPL_RECORD_TEMPLATE" : '<?=CUtil::JSEscape($template)?>',
	"JERROR_NO_MESSAGE" : '<?=GetMessageJS("JERROR_NO_MESSAGE")?>',
	"BLOG_C_HIDE" : '<?=GetMessageJS("BLOG_C_HIDE")?>',
	JQOUTE_AUTHOR_WRITES : '<?=GetMessageJS("JQOUTE_AUTHOR_WRITES")?>',
	FC_ERROR : '<?=GetMessageJS("B_B_PC_COM_ERROR")?>'
	<?
		if (IsModuleInstalled("socialnetwork"))
		{
			?>
			, "MPL_WORKGROUPS_PATH" : '<?=CUtil::JSEscape(COption::GetOptionString("socialnetwork", "workgroups_page", SITE_DIR."workgroups/", SITE_ID))?>'
			<?
		}
	?>
	});
</script>