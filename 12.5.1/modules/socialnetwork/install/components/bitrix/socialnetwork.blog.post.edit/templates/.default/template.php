<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/socialnetwork.log.ex/templates/.default/style.css');
$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/socialnetwork.blog.blog/templates/.default/style.css');

$arParams["FORM_ID"] = "blogPostForm";

if($arResult["delete_blog_post"] == "Y")
{
	$APPLICATION->RestartBuffer();
	if(strlen($arResult["ERROR_MESSAGE"])>0)
	{
		?>
		<script bxrunfirst="yes">
		top.deletePostEr = 'Y';
		</script>
		<div class="feed-add-error">
			<span class="feed-add-info-icon"></span><span class="feed-add-info-text"><?=$arResult["ERROR_MESSAGE"]?></span>
		</div>
		<?
	}	
	if(strlen($arResult["OK_MESSAGE"])>0)
	{
		?>
		<div class="feed-add-successfully" style="margin-left:17px; margin-right:17px;">
			<span class="feed-add-info-text"><span class="feed-add-info-icon"></span><?=$arResult["OK_MESSAGE"]?></span>
		</div>
		<?
	}
	die();
}
else
{
	if (
		IsModuleInstalled("intranet")
		&& array_key_exists("GRATS", $arResult["PostToShow"])
		&& is_array($arResult["PostToShow"]["GRATS"])
		&& count($arResult["PostToShow"]["GRATS"]) > 0
	)
		$bGrat = true;

	if (IsModuleInstalled("vote") && is_array($arResult["POST_PROPERTIES"]["DATA"])
		&& array_key_exists("UF_BLOG_POST_VOTE", $arResult["POST_PROPERTIES"]["DATA"])
	)
		$bVote = true;

	ob_start();

	if ($bGrat || $bVote)
	{
		?><span class="feed-add-post-form-link feed-add-post-form-link-active" id="feed-add-post-form-tab-message" onclick="changePostFormTab('message', <?=($arParams["TOP_TABS_VISIBLE"] == "Y" ? 'true' : 'false')?>);"><?
			?><span class="feed-add-post-form-message-link-icon"></span><?
			?><span><?=GetMessage("BLOG_TAB_POST")?></span><?
		?></span><?
		if (in_array("UF_BLOG_POST_FILE", $arParams["POST_PROPERTY"]))
		{
			?><span class="feed-add-post-form-link" id="feed-add-post-form-tab-file" onclick="changePostFormTab('file', <?=($arParams["TOP_TABS_VISIBLE"] == "Y" ? 'true' : 'false')?>);"><?
				?><span class="feed-add-post-form-file-link-icon"></span><?
				?><span><?=GetMessage("BLOG_TAB_FILE")?></span><?
			?></span><?
		}
		if ($bVote)
		{
			?><span class="feed-add-post-form-link" id="feed-add-post-form-tab-vote" onclick="changePostFormTab('vote', <?=($arParams["TOP_TABS_VISIBLE"] == "Y" ? 'true' : 'false')?>);"><?
				?><span class="feed-add-post-form-polls-link-icon"></span><?
				?><span><?=GetMessage("BLOG_TAB_VOTE")?></span><?
			?></span><?
		}
		if ($bGrat)
		{
			?><span class="feed-add-post-form-link" id="feed-add-post-form-tab-grat" onclick="changePostFormTab('grat', <?=($arParams["TOP_TABS_VISIBLE"] == "Y" ? 'true' : 'false')?>);"><?
				?><span class="feed-add-post-form-grat-link-icon"></span><?
				?><span><?=GetMessage("BLOG_TAB_GRAT")?></span><?
			?></span><?
		}
		?><script>
			BX.addCustomEvent('OnWriteMicroblog', function(val) {
				val = !!val;
				if (val)
				{
					changePostFormTab('message');
				}
			});
		</script><?
	}

	$strGratVote = ob_get_contents();
	ob_end_clean();

	if ($arParams["TOP_TABS_VISIBLE"] == "Y")
	{
		?><div class="microblog-top-tabs-visible"><?
			?><div class="feed-add-post-form-variants" id="feed-add-post-form-tab"><?
				echo $strGratVote;
				$APPLICATION->ShowViewContent("sonet_blog_form");
				if ($bGrat || $bVote)
				{
					?><div id="feed-add-post-form-tab-arrow" class="feed-add-post-form-arrow" style="left: 31px;"></div><?
				}
			?></div><?			
		?></div><?
		?><div id="microblog-link" class="feed-add-post-title" onclick="WriteMicroblog(true)"><?=GetMessage("BLOG_LINK_SHOW_NEW")?></div>
		<div id="microblog-form" style="display:none;"><?
	}

	?><div class="feed-wrap">
	<div class="feed-add-post-block blog-post-edit">
	<?
	if(strlen($arResult["OK_MESSAGE"])>0)
	{
		?>
		<div class="feed-add-successfully">
			<span class="feed-add-info-icon"></span><span class="feed-add-info-text"><?=$arResult["OK_MESSAGE"]?></span>
		</div>
		<?
	}
	if(strlen($arResult["ERROR_MESSAGE"])>0)
	{
		?>
		<div class="feed-add-error">
			<span class="feed-add-info-icon"></span><span class="feed-add-info-text"><?=$arResult["ERROR_MESSAGE"]?></span>
		</div>
		<?
	}
	if(strlen($arResult["FATAL_MESSAGE"])>0)
	{
		?>
		<div class="feed-add-error">
			<span class="feed-add-info-icon"></span><span class="feed-add-info-text"><?=$arResult["FATAL_MESSAGE"]?></span>
		</div>
		<?
	}
	elseif(strlen($arResult["UTIL_MESSAGE"])>0)
	{
		?>
		<div class="feed-add-successfully">
			<span class="feed-add-info-icon"></span><span class="feed-add-info-text"><?=$arResult["UTIL_MESSAGE"]?></span>
		</div>
		<?
	}
	else
	{
		// Frame with file input to ajax uploading in WYSIWYG editor dialog
		if($arResult["imageUploadFrame"] == "Y")
		{
			?>
			<script>
				<?if(!empty($arResult["Image"])):?>
					var imgTable = top.BX('blog-post-image');
					if (imgTable)
					{
						imgTable.innerHTML += '<span class="feed-add-photo-block"><span class="feed-add-img-wrap"><?=$arResult["ImageModified"]?></span><span class="feed-add-img-title"><?=$arResult["Image"]["fileName"]?></span><span class="feed-add-post-del-but" onclick="DeleteImage(\'<?=$arResult["Image"]["ID"]?>\', this)"></span><input type="hidden" id="blgimg-<?=$arResult["Image"]["ID"]?>" value="<?=$arResult["Image"]["source"]["src"]?>"></span>';
						imgTable.parentNode.parentNode.style.display = 'block';
					}

					top.bxPostFileId = '<?=$arResult["Image"]["ID"]?>';
					top.bxPostFileIdSrc = '<?=CUtil::JSEscape($arResult["Image"]["source"]["src"])?>';
					top.bxPostFileIdWidth = '<?=CUtil::JSEscape($arResult["Image"]["source"]["width"])?>';
					<?elseif(strlen($arResult["ERROR_MESSAGE"]) > 0):?>
					window.bxPostFileError = top.bxPostFileError = '<?=CUtil::JSEscape($arResult["ERROR_MESSAGE"])?>';
				<?endif;?>
			</script>
			<?
			die();
		}
		else
		{
			$arSmiles = array();
			if(!empty($arResult["Smiles"]))
			{
				foreach($arResult["Smiles"] as $arSmile)
				{
					$arSmiles[] = array(
						'name' => $arSmile["~LANG_NAME"],
						'path' => "/bitrix/images/blog/smile/".$arSmile["IMAGE"],
						'code' => str_replace("\\\\","\\",$arSmile["TYPE"])
					);
				}
			}

			?><form action="<?=POST_FORM_ACTION_URI?>" id="blogPostForm" name="blogPostForm" method="POST" enctype="multipart/form-data" target="_self" <?
			?>onsubmit="return submitBlogPostForm();">
			<?=bitrix_sessid_post();?>
			
			<div class="feed-add-post-form-wrap"><?

				if ($arParams["TOP_TABS_VISIBLE"] != "Y")
				{
					?><div class="feed-add-post-form-variants" id="feed-add-post-form-tab"><?				
						echo $strGratVote;
						$APPLICATION->ShowViewContent("sonet_blog_form");
						if ($bGrat || $bVote)
						{
							?><div id="feed-add-post-form-tab-arrow" class="feed-add-post-form-arrow" style="left: 31px;"></div><?
						}
					?></div><?			
				}

				?><div id="feed-add-post-content-message">
					<div class="feed-add-post-title" id="blog-title"<?=(COption::GetOptionString("main", "wizard_solution") == "community" ? '' : ' style="display: none;"')?>>
						<input id="POST_TITLE" name="POST_TITLE" class="feed-add-post-inp<?if(!empty($arResult["PostToShow"]["TITLE"])) echo " feed-add-post-inp-active";?>" type="text" value="<?=($arResult["PostToShow"]["MICRO"] == "Y") ? "" : (!empty($arResult["PostToShow"]["TITLE"]) ? $arResult["PostToShow"]["TITLE"] : GetMessage("BLOG_TITLE"))?>" <?
							?>onblur="if (this.value=='') {this.value='<?=GetMessageJS("BLOG_TITLE")?>'; BX.removeClass(this, 'feed-add-post-inp-active');}" <?
							?>onclick="if (this.value=='<?=GetMessageJS("BLOG_TITLE")?>') {this.value=''; BX.addClass(this,'feed-add-post-inp-active')}" />
						<div class="feed-add-close-icon" onclick="showPanelTitle_<?=$arParams["FORM_ID"]?>(false);"></div>
					</div>
					<div id="blog-post-autosave-hidden" style="display:none;"></div><?
					$APPLICATION->IncludeComponent(
						"bitrix:main.post.form",
						"",
						$formParams = Array(
							"FORM_ID" => "blogPostForm",
							"SHOW_MORE" => "Y",
							"PARSER" => Array("Bold", "Italic", "Underline", "Strike", "ForeColor",
								"FontList", "FontSizeList", "RemoveFormat", "Quote", "Code",
								(($arParams["USE_CUT"] == "Y") ? "InsertCut" : ""),
								"CreateLink",
								"Image",
								"Table",
								"Justify",
								"InsertOrderedList",
								"InsertUnorderedList",
								"Source",
								"UploadImage",
								//(in_array("UF_BLOG_POST_FILE", $arParams["POST_PROPERTY"]) || in_array("UF_BLOG_POST_DOC", $arParams["POST_PROPERTY"]) ? "UploadFile" : ""),
								(($arResult["allowVideo"] == "Y") ? "InputVideo" : ""),
								"MentionUser",
							),
							"BUTTONS" => Array(
								(in_array("UF_BLOG_POST_FILE", $arParams["POST_PROPERTY"]) || in_array("UF_BLOG_POST_DOC", $arParams["POST_PROPERTY"]) ? "UploadFile" : ""),
								"CreateLink",
								(($arResult["allowVideo"] == "Y") ? "InputVideo" : ""),
								"Quote",
								"MentionUser",
								"InputTag"
							),
							"ADDITIONAL" => array(
								"{ text : '".GetMessage("BLOG_TITLE")."', onclick : function() {showPanelTitle_".$arParams["FORM_ID"]."(); this.popupWindow.close();}, className: 'blog-post-popup-menu', id: 'bx-title'},"
							),

							"TEXT" => Array(
								"NAME" => "POST_MESSAGE",
								"VALUE" => htmlspecialcharsBack($arResult["PostToShow"]["~DETAIL_TEXT"]),
								"HEIGHT" => "120px"),

							"UPLOAD_FILE" => (!empty($arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"]) ? false : $arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_DOC"]),
							"UPLOAD_WEBDAV_ELEMENT" => $arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"],
							"UPLOAD_FILE_PARAMS" => array('width' => $arParams["IMAGE_MAX_WIDTH"], 'height' => $arParams["IMAGE_MAX_HEIGHT"]),
							"FILES" => Array(
								"VALUE" => $arResult["Images"],
								"POSTFIX" => "file",
							),

							"DESTINATION" => array(
								"VALUE" => $arResult["PostToShow"]["FEED_DESTINATION"],
								"SHOW" => "Y"
							),

							"TAGS" => Array(
								"ID" => "TAGS",
								"NAME" => "TAGS",
								"VALUE" => explode(",", trim($arResult["PostToShow"]["CategoryText"])),
								"USE_SEARCH" => "Y",
								"FILTER" => "blog",
							),
							"SMILES" => $arSmiles,
							"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
							"LHE" => array(
									"documentCSS" => "body {color:#434343;}",
									"ctrlEnterHandler" => "submitBlogPostForm".$arParams["FORM_ID"],
									"jsObjName" => "oPostFormLHE_".$arParams["FORM_ID"],
									"fontFamily" => "'Helvetica Neue', Helvetica, Arial, sans-serif",
									"fontSize" => "14px",
								),
							"IS_BLOG" => true,
							),
							false,
							Array("HIDE_ICONS" => "Y")
						);
				
				?></div><?
			?></div><? //feed-add-post-form-wrap
			if (in_array("UF_BLOG_POST_FILE", $arParams["POST_PROPERTY"]))
			{
				?><div id="feed-add-post-content-file" style="display: none;">
					<div class="feed-add-post">
						<div class="feed-add-post-form feed-add-post-edit-form">
							<div class="feed-add-post-text">
								<table class="feed-add-file-form-light-table">
									<tr>
										<td class="feed-add-file-form-light-cell" onmouseover="BX.addClass(this, 'feed-add-file-form-light-hover')" onmouseout="BX.removeClass(this, 'feed-add-file-form-light-hover')">
											<span class="feed-add-file-form-light">
												<span class="feed-add-file-form-light-text">
													<span class="feed-add-file-form-light-title">
														<input id="UFBPF<?=$arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"]["ID"]?>" name="SourceFile_1" type="file" multiple='multiple' size='1'  />
														<span class="feed-add-file-form-light-title-text"><?=GetMessage("BLOG_UPLOAD")?></span>
													</span>
													<span class="feed-add-file-form-light-descript"><?=GetMessage("BLOG_DRAG")?></span>
												</span>
											</span>
										</td>
										<td class="feed-add-file-form-light-cell">
											<span class="feed-add-file-form-light feed-add-file-from-portal">
												<span class="feed-add-file-form-light-text">
													<span class="feed-add-file-form-light-title">
														<span id="DUFBPF<?=$arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"]["ID"]?>" class="feed-add-file-form-light-title-text"><?=GetMessage("BLOG_DIALOG")?></span>
													</span>
													<span class="feed-add-file-form-light-descript"><?=GetMessage("BLOG_DIALOG_ALT")?></span>
												</span>
											</span>
										</td>
									</tr>
								</table>
<script type="text/javascript">
BX.ready(function(){
	BX.loadScript(['<?=$this->__folder?>/script.js'], function() {
		new WDFileDialogBranch(BX('UFBPF<?=$arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"]["ID"]?>'));
	});
});
</script>
						</div>
						</div>
					</div>
				</div><?
			}

			if ($bVote)
			{
				?><div id="feed-add-post-content-vote" style="display: none;"><?
				if (IsModuleInstalled("vote"))
				{
					$APPLICATION->IncludeComponent(
						"bitrix:system.field.edit",
						"vote",
						array(
							"bVarsFromForm" => (!empty($arResult["ERROR_MESSAGE"])),
							"arUserField" => $arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_VOTE"]),
						null,
						array("HIDE_ICONS" => "Y")
					);
				}
				?></div><?
			}

			if ($bGrat)
			{
				?><div id="feed-add-post-content-grat" style="display: none;"><?
					if (
						array_key_exists("GRAT_CURRENT", $arResult["PostToShow"]) 
						&& is_array($arResult["PostToShow"]["GRAT_CURRENT"]["USERS"])
					)
					{
						$arGratCurrentUsers = array();
						foreach($arResult["PostToShow"]["GRAT_CURRENT"]["USERS"] as $grat_user_id)
							$arGratCurrentUsers["U".$grat_user_id] = 'users';
					}

					?><div class="feed-add-grat-block feed-add-grat-star"><?

						$grat_type = ""; $title_default = "";

						if (
							is_array($arResult["PostToShow"]["GRAT_CURRENT"])
							&& is_array($arResult["PostToShow"]["GRAT_CURRENT"]["TYPE"])
						)
						{
							$grat_type = htmlspecialcharsbx($arResult["PostToShow"]["GRAT_CURRENT"]["TYPE"]["XML_ID"]);
							$class_default = "feed-add-grat-medal-".htmlspecialcharsbx($arResult["PostToShow"]["GRAT_CURRENT"]["TYPE"]["XML_ID"]);
							$title_default = htmlspecialcharsbx($arResult["PostToShow"]["GRAT_CURRENT"]["TYPE"]["VALUE_ENUM"]);
						}
						elseif (is_array($arResult["PostToShow"]["GRATS_DEF"]))
						{
							$grat_type = htmlspecialcharsbx($arResult["PostToShow"]["GRATS_DEF"]["XML_ID"]);
							$class_default = "feed-add-grat-medal-".htmlspecialcharsbx($arResult["PostToShow"]["GRATS_DEF"]["XML_ID"]);
							$title_default = htmlspecialcharsbx($arResult["PostToShow"]["GRATS_DEF"]["VALUE"]);
						}

						?><div id="feed-add-post-grat-type-selected" class="feed-add-grat-medal<?=($class_default ? " ".$class_default : "")?>"<?=($title_default ? ' title="'.$title_default.'"' : '')?>>
							<div id="feed-add-post-grat-others" class="feed-add-grat-medal-other"><?=GetMessage("BLOG_TITLE_GRAT_OTHER")?></div>
							<div class="feed-add-grat-medal-arrow"></div>
						</div>
						<input type="hidden" name="GRAT_TYPE" value="<?=htmlspecialcharsbx($grat_type)?>" id="feed-add-post-grat-type-input">
						<script type="text/javascript">

							var arGrats = [];
							var	BXSocNetLogGratFormName = '<?=randString(6)?>';
							<?
							if (is_array($arResult["PostToShow"]["GRATS"]))
							{
								foreach($arResult["PostToShow"]["GRATS"] as $i => $arGrat)
								{
									?>
									arGrats[<?=CUtil::JSEscape($i)?>] = {
										'title': '<?=CUtil::JSEscape($arGrat["VALUE"])?>',
										'code': '<?=CUtil::JSEscape($arGrat["XML_ID"])?>',
										'style': 'feed-add-grat-medal-<?=CUtil::JSEscape($arGrat["XML_ID"])?>'
									};
									<?
								}
							}
							?>

							BX.SocNetGratSelector.init({
								'name' : BXSocNetLogGratFormName,
								'itemSelectedImageItem' : BX('feed-add-post-grat-type-selected'),
								'itemSelectedInput' : BX('feed-add-post-grat-type-input')
							});
							BX.bind(BX('feed-add-post-grat-type-selected'), 'click', function(e){BX.SocNetGratSelector.openDialog(BXSocNetLogGratFormName); BX.PreventDefault(e); });
						</script>
						<div class="feed-add-grat-right">
							<div class="feed-add-grat-label"><?=GetMessage("BLOG_TITLE_GRAT")?></div>
							<div class="feed-add-grat-form">
								<div class="feed-add-post-grat-wrap feed-add-post-destination-wrap" id="feed-add-post-grat-container">
									<span id="feed-add-post-grat-item"></span>
									<span class="feed-add-grat-input-box" id="feed-add-post-grat-input-box">
										<input type="text" value="" class="feed-add-grat-inp" id="feed-add-post-grat-input">
									</span>
									<a href="#" class="feed-add-grat-link" id="bx-grat-tag"><?
									if (
										!is_array($arResult["PostToShow"]["GRAT_CURRENT"]) 
										|| count($arResult["PostToShow"]["GRAT_CURRENT"]) <= 0
									)
										echo GetMessage("BLOG_GRATMEDAL_1");
									?></a>
									<script type="text/javascript">
									var department = <?=(empty($arResult["PostToShow"]["FEED_DESTINATION"]['DEPARTMENT'])? '{}': CUtil::PhpToJSObject($arResult["PostToShow"]["FEED_DESTINATION"]['DEPARTMENT']))?>;
									<?if(empty($arResult["PostToShow"]["FEED_DESTINATION"]['DEPARTMENT_RELATION']))
									{
										?>
										var relation = {};
										for(var iid in department)
										{
											var p = department[iid]['parent'];
											if (!relation[p])
												relation[p] = [];
											relation[p][relation[p].length] = iid;
										}
										function makeDepartmentTree(id, relation)
										{
											var arRelations = {};
											if (relation[id])
											{
												for (var x in relation[id])
												{
													var relId = relation[id][x];
													var arItems = [];
													if (relation[relId] && relation[relId].length > 0)
														arItems = makeDepartmentTree(relId, relation);

													arRelations[relId] = {
														id: relId,
														type: 'category',
														items: arItems
													};
												}
											}

											return arRelations;
										}
										var departmentRelation = makeDepartmentTree('DR0', relation);
										<?
									}
									else
									{
										?>var departmentRelation = <?=CUtil::PhpToJSObject($arResult["PostToShow"]["FEED_DESTINATION"]['DEPARTMENT_RELATION'])?>;<?
									}
									?>


										BX.message({
											'BX_FPGRATMEDAL_LINK_1': '<?=GetMessageJS("BLOG_GRATMEDAL_1")?>',
											'BX_FPGRATMEDAL_LINK_2': '<?=GetMessageJS("BLOG_GRATMEDAL_2")?>',
											'BLOG_GRAT_POPUP_TITLE': '<?=GetMessageJS("BLOG_GRAT_POPUP_TITLE")?>'
										});

										BX.SocNetLogDestination.init({
											'name' : BXSocNetLogGratFormName,
											'searchInput' : BX('feed-add-post-grat-input'),
											'pathToAjax' : '/bitrix/components/bitrix/socialnetwork.blog.post.edit/post.ajax.php',
											'extranetUser' : false,
											'bindMainPopup' : { 'node' : BX('feed-add-post-grat-container'), 'offsetTop' : '-5px', 'offsetLeft': '15px'},
											'bindSearchPopup' : { 'node' : BX('feed-add-post-grat-container'), 'offsetTop' : '-5px', 'offsetLeft': '15px'},
											'departmentSelectDisable' : true,
											'lastTabDisable' : true,
											'callback' : {
												'select' : BXfpGratSelectCallback,
												'unSelect' : BXfpGratUnSelectCallback,
												'openDialog' : BXfpGratOpenDialogCallback,
												'closeDialog' : BXfpGratCloseDialogCallback,
												'openSearch' : BXfpGratOpenDialogCallback,
												'closeSearch' : BXfpGratCloseSearchCallback
											},
											'items' : {
												'users' : <?=((array_key_exists("GRAT_CURRENT", $arResult["PostToShow"]) && is_array($arResult["PostToShow"]["GRAT_CURRENT"]["USERS_FOR_JS"])) ? CUtil::PhpToJSObject($arResult["PostToShow"]["GRAT_CURRENT"]["USERS_FOR_JS"]) : '{}')?>,
												'groups' : {},
												'sonetgroups' : {},
												'department' : department,
												'departmentRelation' : departmentRelation
											},
											'itemsLast' : {
												'users' : {},
												'sonetgroups' : {},
												'department' : {},
												'groups' : {}
											},
											'itemsSelected' : <?=(($arGratCurrentUsers && is_array($arGratCurrentUsers)) ? CUtil::PhpToJSObject($arGratCurrentUsers) : '{}')?>
										});
										BX.bind(BX('feed-add-post-grat-input'), 'keyup', BXfpGratSearch);
										BX.bind(BX('feed-add-post-grat-input'), 'keydown', BXfpGratSearchBefore);
										BX.bind(BX('bx-grat-tag'), 'click', function(e){BX.SocNetLogDestination.openDialog(BXSocNetLogGratFormName); BX.PreventDefault(e); });
										BX.bind(BX('feed-add-post-grat-container'), 'click', function(e){BX.SocNetLogDestination.openDialog(BXSocNetLogGratFormName); BX.PreventDefault(e); });
									</script>
								</div>
							</div>
						</div>
					</div><?
				?></div><?
			}
			?>

			<script type="text/javascript">
			function showPanelTitle_<?=$arParams["FORM_ID"]?>(show)
			{
				if(BX('blog-title').style.display == "none" || show)
					BX.show(BX('blog-title'));
				else
					BX.hide(BX('blog-title'));
			}
			<?if($arResult["PostToShow"]["MICRO"] != "Y" && strlen($arResult["PostToShow"]["TITLE"]) > 0)
			{
				?>showPanelTitle_<?=$arParams["FORM_ID"]?>(true);<?
			}?>

			var bSubmit = false;
			function submitBlogPostForm()
			{
				if(bSubmit)
					return false;

				if(BX('blog-title').style.display == "none")
					BX('POST_TITLE').value = "";
				bSubmit = true;
				return true;
			}
			// Submit form by ctrl+enter
			function submitBlogPostForm<?=$arParams["FORM_ID"]?>()
			{
				BX.submit(BX('<?=$arParams["FORM_ID"]?>'), 'save');
			};


			if(BX('microblog-form'))
			{
				BX.addCustomEvent(BX('microblog-form'), 'onFormShow', function() {
					mpfReInitLHE<?=$arParams["FORM_ID"]?>();
				});
				BX.addCustomEvent(window, 'onSocNetLogMoveBody', function(p){
					if(p == 'sonet_log_microblog_container')
						mpfReInitLHE<?=$arParams["FORM_ID"]?>();
				});
			}

			bShow<?=$arParams["FORM_ID"]?> = false;
			BX.addCustomEvent(window, 'LHE_OnInit', function(pEditor){
				bShow<?=$arParams["FORM_ID"]?> = true;
				if(el = BX.findChild(BX('<?=$arParams["FORM_ID"]?>'), {'attr': {id: 'lhe_btn_smilelist'}}, true, false))
					BX.remove(BX.findParent(el), true);
			});
			function mpfReInitLHE<?=$arParams["FORM_ID"]?>()
			{
				tmpContent = '<?=CUtil::JSEscape($formParams["TEXT"]["VALUE"])?>';
				if(bShow<?=$arParams["FORM_ID"]?> && window['<?=$formParams["LHE"]["jsObjName"]?>'])
					window['<?=$formParams["LHE"]["jsObjName"]?>'].ReInit(tmpContent);
				else
					setTimeout(str = "mpfReInitLHE<?=$arParams["FORM_ID"]?>();", 50);
			}
			<?
			if (IsModuleInstalled("webdav"))
			{
				?>
				BX.addCustomEvent(
					BX.findChild(BX('blogPostForm'), {'className': 'feed-add-post' }, true, false),
					'BFileDLoadFormController',
					function(){
						setTimeout(function (){
							BX.findChild(BX('blogPostForm'), {'className': 'file-label' }, true, false).innerHTML = '<?=GetMessageJS("BLOG_P_PHOTO")?>';
						}, 500);
					}
				);
				<?
			}
			?></script><?

			$arButtons = Array(
				Array(
					"NAME" => "save",
					"TEXT" => GetMessage("BLOG_BUTTON_SEND"),
				),
			);
			if($arParams["MICROBLOG"] != "Y")
			{
				$arButtons[] = Array(
					"NAME" => "draft",
					"TEXT" => GetMessage("BLOG_BUTTON_DRAFT")
				);
			}
			else
			{
				$arButtons[] = Array(
					"NAME" => "cancel",
					"TEXT" => GetMessage("BLOG_BUTTON_CANCEL"),
					"CLICK" => "WriteMicroblog(false);",
					"CLEAR_CANCEL" => "Y",
				);
			}

			?><div><?
			foreach($arButtons as $val)
			{
				$onclick = $val["CLICK"];
				if(strlen($onclick) <= 0)
					$onclick = "BX.submit(BX('".$arParams["FORM_ID"]."'), '".$val["NAME"]."'); ";

				if($val["CLEAR_CANCEL"] == "Y")
				{
					?><a href="javascript:void(0)" id="blog-submit-button-<?=$val["NAME"]?>" onclick="<?=$onclick?>" class="feed-cancel-com"><?=$val["TEXT"]?></a><?
				}
				else
				{
						?><a href="javascript:void(0)" id="blog-submit-button-<?=$val["NAME"]?>" onclick="<?=$onclick?>" class="feed-add-button<?=" ".$val["ADIT_STYLES"]?>" onmousedown="BX.addClass(this, 'feed-add-button-press')" onmouseup="BX.removeClass(this,'feed-add-button-press')"><span class="feed-add-button-left"></span><span class="feed-add-button-text" onclick="MPFbuttonShowWait(this);"><?=$val["TEXT"]?></span><span class="feed-add-button-right"></span></a><?
				}
			}
			?></div>
			<input type="hidden" name="blog_upload_cid" id="upload-cid" value="">
			</form><?
		}
	}
	?></div>
	</div><?

	if ($arParams["TOP_TABS_VISIBLE"] == "Y")
	{
		?></div><?
	}

	if(strlen($arResult["ERROR_MESSAGE"])>0 || strlen($arResult["OK_MESSAGE"])>0)
	{
		?><script>
			if(BX('microblog-form'))
			{
				function PostFormError_<?=$arParams["FORM_ID"]?>()
				{
					if(window.oPostFormLHE_<?=$arParams["FORM_ID"]?>)
						WriteMicroblog(true);
					else
						setTimeout(str = "PostFormError_<?=$arParams["FORM_ID"]?>()", 100);
				}
				BX.ready(function() {setTimeout(str = "PostFormError_<?=$arParams["FORM_ID"]?>()", 100);});
			}
		</script><?
	}
}
?>