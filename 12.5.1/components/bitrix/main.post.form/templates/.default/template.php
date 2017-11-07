<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$array = (((!empty($arParams["DESTINATION"]) || in_array("MentionUser", $arParams["BUTTONS"])) && IsModuleInstalled("socialnetwork")) ?
	array('socnetlogdest') : array());
CUtil::InitJSCore($array);
$arButtonsHTML = array();

foreach($arParams["BUTTONS"] as $val)
{
	switch($val)
	{
		case "CreateLink":
			$arButtonsHTML[] = '<span class="feed-add-post-form-but feed-add-link" id="bx-b-link"></span>';
			break;
		case "UploadImage":
			$arButtonsHTML[] = '<span class="feed-add-post-form-but feed-add-img" id="bx-b-uploadimage"></span>';
			break;
		case "UploadFile":
			$arButtonsHTML[] = '<span class="feed-add-post-form-but feed-add-file" id="bx-b-uploadfile" '.
					'title="'.GetMessage('MPF_FILE_TITLE').'"></span>';
			break;
		case "InputVideo":
			$arButtonsHTML[] = '<span class="feed-add-post-form-but feed-add-video" id="bx-b-video"></span>';
			break;
		case "InputTag":
			$arButtonsHTML[] = '<span class="feed-add-post-form-but feed-add-tag" id="bx-b-tag-input" '.
				'title="'.GetMessage("MPF_TAG_TITLE").'"></span>';
			break;
		case "MentionUser":
			$arButtonsHTML[] = '<span class="feed-add-post-form-but feed-add-mention" id="bx-b-mention" '.
				'title="'.GetMessage("MPF_MENTION_TITLE").'"></span>';
			break;
		case "Quote":
			$arButtonsHTML[] = '<span class="feed-add-post-form-but feed-add-quote" id="bx-b-quote"></span>';
			break;
		default:
			if (array_key_exists($val, $arParams["BUTTONS_HTML"]))
				$arButtonsHTML[] = $arParams["BUTTONS_HTML"][$val];
			break;
	}
}
?>
<div class="feed-add-post">
<?=$arParams["~HTML_BEFORE_TEXTAREA"]?>
	<div class="feed-add-post-form feed-add-post-edit-form">
		<div class="feed-add-post-text">
			<div class="feed-add-close-icon" onclick="window['PlEditor<?=$arParams["FORM_ID"]?>'].showPanelEditor(false);" id="bx-panel-close"></div>
			<?

if (IsModuleInstalled("fileman"))
	include($_SERVER["DOCUMENT_ROOT"].$templateFolder."/lhe.php");
?>
<script type="text/javascript">
	BX.message({
		'BX_FPD_LINK_1':'<?=GetMessageJS("MPF_DESTINATION_1")?>',
		'BX_FPD_LINK_2':'<?=GetMessageJS("MPF_DESTINATION_2")?>',
		'TAG_ADD': '<?=GetMessageJS("MPF_ADD_TAG1")?>',
		'MPF_IMAGE': '<?=GetMessageJS("MPF_IMAGE_TITLE")?>',
		'MPF_FILE': '<?=GetMessageJS("MPF_INSERT_FILE")?>',
		'MPF_NAME_TEMPLATE' : '<?=urlencode($arParams['NAME_TEMPLATE'])?>'
	});

	if(window.BX)
	{
		BX.ready(function()
		{
			window['PlEditor<?=$arParams["FORM_ID"]?>'] = new LHEPostForm(
				'<?=$arParams["FORM_ID"]?>',
			<?=CUtil::PhpToJSObject(
				array(
					"sNewFilePostfix" => $arParams["FILES"]["POSTFIX"],
					"eID" => $arParams["LHE"]["jsObjName"],
					"LHEID" => $arParams["LHE"]["id"],
					"arSize" => $arParams["UPLOAD_FILE_PARAMS"],
					"WDLoadFormController" => !empty($arParams["UPLOAD_WEBDAV_ELEMENT"]),
					"BFileDLoadFormController" => !empty($arParams["UPLOAD_FILE"]),
					"arFiles" => $arParams["FILES"]["VALUE_JS"],
					"arActions" => $arParams["BUTTONS"]
				));?>);
			BX.addCustomEvent('WDSelectFileDialogLoaded', function(wdFD) {
				window['PlEditor<?=$arParams["FORM_ID"]?>'].OnWDSelectFileDialogLoaded(wdFD);
			});
		});
	}
</script>

		<div style="display:none;"><input type="text" tabindex="<?=($arParams["TEXT"]["TABINDEX"]++)?>" onFocus="window['<?=$arParams["LHE"]["jsObjName"]?>'].SetFocus()" name="hidden_focus" /></div>
		</div>
		<div class="feed-add-post-form-but-wrap" id="post-buttons-bottom"><?=implode("", $arButtonsHTML);
	if(!empty($arParams["ADDITIONAL"]))
	{
		?><div class="feed-add-post-form-but-more" onclick="BX.PopupMenu.show('menu-more<?=$arParams["FORM_ID"]?>', this, [<?=implode(", ", $arParams["ADDITIONAL"])?>], {offsetLeft: 42, offsetTop: 3, lightShadow: false, angle: top, events : {onPopupClose : function(popupWindow) {BX.removeClass(this.bindElement, 'feed-add-post-form-but-more-act');}}}); BX.addClass(this, 'feed-add-post-form-but-more-act');"><?=GetMessage("MPF_MORE")?><div class="feed-add-post-form-but-arrow"></div></div><?
	}
	?></div>
</div>
<?=$arParams["~HTML_AFTER_TEXTAREA"]?><?

if ($arParams["DESTINATION_SHOW"] == "Y" || in_array("MentionUser", $arParams["BUTTONS"]))
{
?>
<script>
	var lastUsers = <?=(empty($arParams["DESTINATION"]['LAST']['USERS'])? '{}': CUtil::PhpToJSObject($arParams["DESTINATION"]['LAST']['USERS']))?>;
	var users = <?=(empty($arParams["DESTINATION"]['USERS'])? '{}': CUtil::PhpToJSObject($arParams["DESTINATION"]['USERS']))?>;
	var department = <?=(empty($arParams["DESTINATION"]['DEPARTMENT'])? '{}': CUtil::PhpToJSObject($arParams["DESTINATION"]['DEPARTMENT']))?>;
	<?if(empty($arParams["DESTINATION"]['DEPARTMENT_RELATION']))
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
		?>var departmentRelation = <?=CUtil::PhpToJSObject($arParams["DESTINATION"]['DEPARTMENT_RELATION'])?>;<?
	}
	?>
</script>
<?
}

if($arParams["DESTINATION_SHOW"] == "Y")
{
	?>
<div class="feed-add-post-destination-block">
	<div class="feed-add-post-destination-title"><?=GetMessage("MPF_DESTINATION")?></div>
	<div class="feed-add-post-destination-wrap" id="feed-add-post-destination-container">
		<span id="feed-add-post-destination-item"></span>
		<span class="feed-add-destination-input-box" id="feed-add-post-destination-input-box">
			<input type="text" value="" class="feed-add-destination-inp" id="feed-add-post-destination-input">
		</span>
		<a href="#" class="feed-add-destination-link" id="bx-destination-tag"></a>
		<script type="text/javascript">
			BXSocNetLogDestinationFormName = '<?=randString(6)?>';
			BXSocNetLogDestinationDisableBackspace = null;
			BX.SocNetLogDestination.init({
				'name' : BXSocNetLogDestinationFormName,
				'searchInput' : BX('feed-add-post-destination-input'),
				'extranetUser' :  <?=($arParams["DESTINATION"]["EXTRANET_USER"] == 'Y'? 'true': 'false')?>,
				'bindMainPopup' : { 'node' : BX('feed-add-post-destination-container'), 'offsetTop' : '5px', 'offsetLeft': '15px'},
				'bindSearchPopup' : { 'node' : BX('feed-add-post-destination-container'), 'offsetTop' : '5px', 'offsetLeft': '15px'},
				'callback' : {
					'select' : BXfpdSelectCallback,
					'unSelect' : BXfpdUnSelectCallback,
					'openDialog' : BXfpdOpenDialogCallback,
					'closeDialog' : BXfpdCloseDialogCallback,
					'openSearch' : BXfpdOpenDialogCallback,
					'closeSearch' : BXfpdCloseSearchCallback
				},
				'items' : {
					'users' : users,
					'groups' : <?=($arParams["DESTINATION"]["EXTRANET_USER"] == 'Y'? '{}': "{'UA' : {'id':'UA','name': '".(!empty($arParams["DESTINATION"]['DEPARTMENT']) ? GetMessageJS("MPF_DESTINATION_3"): GetMessageJS("MPF_DESTINATION_4"))."'}}")?>,
					'sonetgroups' : <?=(empty($arParams["DESTINATION"]['SONETGROUPS'])? '{}': CUtil::PhpToJSObject($arParams["DESTINATION"]['SONETGROUPS']))?>,
					'department' : department,
					'departmentRelation' : departmentRelation
				},
				'itemsLast' : {
					'users' : lastUsers,
					'sonetgroups' : <?=(empty($arParams["DESTINATION"]['LAST']['SONETGROUPS'])? '{}': CUtil::PhpToJSObject($arParams["DESTINATION"]['LAST']['SONETGROUPS']))?>,
					'department' : <?=(empty($arParams["DESTINATION"]['LAST']['DEPARTMENT'])? '{}': CUtil::PhpToJSObject($arParams["DESTINATION"]['LAST']['DEPARTMENT']))?>,
					'groups' : <?=($arParams["DESTINATION"]["EXTRANET_USER"] == 'Y'? '{}': "{'UA':true}")?>
				},
				'itemsSelected' : <?=(empty($arParams["DESTINATION"]['SELECTED'])? '{}': CUtil::PhpToJSObject($arParams["DESTINATION"]['SELECTED']))?>
			});
			BX.bind(BX('feed-add-post-destination-input'), 'keyup', BXfpdSearch);
			BX.bind(BX('feed-add-post-destination-input'), 'keydown', BXfpdSearchBefore);
			BX.bind(BX('bx-destination-tag'), 'click', function(e){BX.SocNetLogDestination.openDialog(BXSocNetLogDestinationFormName); BX.PreventDefault(e); });
			BX.bind(BX('feed-add-post-destination-container'), 'click', function(e){BX.SocNetLogDestination.openDialog(BXSocNetLogDestinationFormName); BX.PreventDefault(e); });
		</script>
	</div>
</div>
<?
}

if (in_array("MentionUser", $arParams["BUTTONS"]))
{
?>
<script type="text/javascript">
	window['bMentListen'] = false;
	window['bPlus'] = false;
	function BXfpdSelectCallbackMent<?=$arParams["FORM_ID"]?>(item, type, search)
	{
		BXfpdSelectCallbackMent(item, type, search, '<?=$arParams["FORM_ID"]?>', '<?=$arParams["LHE"]["jsObjName"]?>');
	}

	function BXfpdStopMent<?=$arParams["FORM_ID"]?>()
	{
		window['bMentListen'] = false;
		clearTimeout(BX.SocNetLogDestination.searchTimeout);
		BX.SocNetLogDestination.closeDialog();
		BX.SocNetLogDestination.closeSearch();
		if(window['<?=$arParams["LHE"]["jsObjName"]?>'])
			window['<?=$arParams["LHE"]["jsObjName"]?>'].SetFocus();
	}

	BXSocNetLogDestinationFormNameMent<?=$arParams["FORM_ID"]?> = '<?=randString(6)?>';
	BXSocNetLogDestinationDisableBackspace = null;
	var bxBMent = BX.findChild(BX('<?=$arParams["FORM_ID"]?>'), {'attr': {id: 'bx-b-mention'}}, true, false);
	BX.SocNetLogDestination.init({
		'name' : BXSocNetLogDestinationFormNameMent<?=$arParams["FORM_ID"]?>,
		'searchInput' : bxBMent,
		'extranetUser' :  <?=($arParams["DESTINATION"]["EXTRANET_USER"] == 'Y'? 'true': 'false')?>,
		'bindMainPopup' :  { 'node' : bxBMent, 'offsetTop' : '1px', 'offsetLeft': '12px'},
		'bindSearchPopup' : { 'node' : bxBMent, 'offsetTop' : '1px', 'offsetLeft': '12px'},
		'callback' : {'select' : BXfpdSelectCallbackMent<?=$arParams["FORM_ID"]?>},
		'items' : {
			'users' : users,
			'groups' : {},
			'sonetgroups' : {},
			'department' : department,
			'departmentRelation' : departmentRelation

		},
		'itemsLast' : {
			'users' : lastUsers,
			'sonetgroups' : {},
			'department' : {},
			'groups' : {}
		},
		'itemsSelected' : <?=(empty($arParams["DESTINATION"]['SELECTED'])? '{}': CUtil::PhpToJSObject($arParams["DESTINATION"]['SELECTED']))?>,
		'departmentSelectDisable' : true,
		'obWindowClass' : 'bx-lm-mention',
		'obWindowCloseIcon' : false
	});

	if(window.BX)
	{
		BX.ready(
			function()
			{
				if(/MSIE 8/.test(navigator.userAgent))
				{
					var ment = BX.findChild(BX('<?=$arParams["FORM_ID"]?>'), {'attr': {id: 'bx-b-mention'}}, true, false);
					ment.style.width = '1px';
					ment.style.marginRight = '0';
				}
				else
				{
					BX.addCustomEvent(
						BX.findChild(BX('<?=$arParams["FORM_ID"]?>'), {'attr': {id: 'bx-b-mention'}}, true, false),
						'mentionClick',
						function(e){
						setTimeout(function()
						{
							if(!BX.SocNetLogDestination.isOpenDialog())
								BX.SocNetLogDestination.openDialog(BXSocNetLogDestinationFormNameMent<?=$arParams["FORM_ID"]?>);
							bPlus = false;
							window['bMentListen'] = true;
							window["mentionText"] = '';
							window['<?=$arParams["LHE"]["jsObjName"]?>'].SetFocus();

							if(BX.browser.IsIE())
							{
								r = window['<?=$arParams["LHE"]["jsObjName"]?>'].GetSelectionRange();
								win = window['<?=$arParams["LHE"]["jsObjName"]?>'].pEditorWindow;
								if(win.document.selection) // IE8 and below
								{
									r = BXfixIERangeObject(r, win);
									if (r && r.endContainer)
									{
										txt = r.endContainer.nodeValue;
										if(txt && window['rngEndOffset'] > txt.length)
											window['rngEndOffset'] = txt.length;

										var rng = window['<?=$arParams["LHE"]["jsObjName"]?>'].pEditorDocument.createRange();
										rng.setStart(r.endContainer, window['rngEndOffset']);
										rng.setEnd(r.endContainer, window['rngEndOffset']);
										window['<?=$arParams["LHE"]["jsObjName"]?>'].SelectRange(rng);
										window['<?=$arParams["LHE"]["jsObjName"]?>'].SetFocus();
									}
								}
							}


						}, 100);
						}
					);

					//mousedown for IE, that lost focus on button click
					BX.bind(
						BX.findChild(BX('<?=$arParams["FORM_ID"]?>'), {'attr': {id: 'bx-b-mention'}}, true, false),
						"mousedown",
						function(e)
						{
							if(window['bMentListen'] !== true)
							{
								if(window['<?=$arParams["LHE"]["jsObjName"]?>'].sEditorMode == 'html') // WYSIWYG
								{
									window['<?=$arParams["LHE"]["jsObjName"]?>'].InsertHTML('@');
									window['bMentListen'] = true;
									window["mentionText"] = '';
									bPlus = false;

									if(BX.browser.IsIE())
									{
										r = window['<?=$arParams["LHE"]["jsObjName"]?>'].GetSelectionRange();

										win = window['<?=$arParams["LHE"]["jsObjName"]?>'].pEditorWindow;
										if(win.document.selection) // IE8 and below
										{
											r = BXfixIERangeObject(r, win);
											window['rngEndOffset'] = r.endOffset;
										}
									}
								}

								BX.onCustomEvent(BX.findChild(BX('<?=$arParams["FORM_ID"]?>'), {'attr': {id: 'bx-b-mention'}}, true, false), 'mentionClick');
							}
						}
					);
				}
			}
		);
	}
</script>
<?
}
if (!empty($arParams["TAGS"]))
{
?>
<div class="feed-add-post-tags-block">
	<div class="feed-add-post-tags-title"><?=GetMessage("MPF_TAGS")?></div>
	<div class="feed-add-post-tags-wrap" id="post-tags-container">
		<?
		$bHasTags = false;
		foreach($arParams["TAGS"]["VALUE"] as $val)
		{
			$val = trim($val);
			if(strlen($val) > 0)
			{
				$bHasTags = true;
				?><span class="feed-add-post-tags"><?
				?><?=htmlspecialcharsbx($val)?><span class="feed-add-post-del-but" onclick="deleteTag('<?=CUtil::JSEscape($val)?>', this.parentNode)"></span><?
				?></span><?
			}
		}
		?><span class="feed-add-post-tags-add" id="bx-post-tag"><?=GetMessage("MPF_ADD_TAG")?></span>
		<input type="hidden" name="<?=$arParams["TAGS"]["NAME"]?>" id="tags-hidden" value="<?=implode(",", $arParams["TAGS"]["VALUE"])?>,">
	</div>
</div>
<div id="post-tags-input" style="display:none;">
	<?if($arParams["TAGS"]["USE_SEARCH"] == "Y" && IsModuleInstalled("search"))
{
	$APPLICATION->IncludeComponent(
		"bitrix:search.tags.input",
		".default",
		Array(
			"NAME"	=>	$arParams["TAGS"]["NAME"],
			"VALUE"	=>	"",
			"arrFILTER"	=>	$arParams["TAGS"]["FILTER"],
			"PAGE_ELEMENTS"	=>	"10",
			"SORT_BY_CNT"	=>	"Y",
			"TEXT" => 'size="30" tabindex="'.($arParams["TEXT"]["TABINDEX"]++).'"',
			"ID" => "TAGS"
		));
}
else
{
	?><input type="text" tabindex="<?=($arParams["TEXT"]["TABINDEX"]++)?>" name="<?=$arParams["TAGS"]["NAME"]?>" size="30" value=""><?
}?>
</div>
<script type="text/javascript">
	if(window.BX)
	{
		BX.ready(function()
		{
			BX.bind(BX("bx-post-tag"), "click", function(e) {
				if(!e) e = window.event;
				popupTag.setAngle({position:'top'});
				popupTag.show();
				BX(BX.findChild(BX('post-tags-input'), {'tag': 'input' })).focus();
				BX.PreventDefault(e);
			});
			BX.bind(BX("bx-b-tag-input"), "click", function(e) {
				if(!e) e = window.event;

				var el = BX.findChild(BX('<?=$arParams["FORM_ID"]?>'), {'className': /feed-add-post-tags-block/ }, true, false);
				BX.show(el);

				popupTag.setAngle({position:'top'});
				popupTag.show();
				BX(BX.findChild(BX('post-tags-input'), {'tag': 'input' })).focus();
				BX.PreventDefault(e);
			});
			BX.bind(BX("bx-post-tag-button"), "click", function(e) {
				if(!e) e = window.event;
				popupTag.setAngle({position:'top'});
				popupTag.show();
				BX(BX.findChild(BX('post-tags-input'), {'tag': 'input' })).focus();
				BX.PreventDefault(e);
			});

			var popupBindElement = BX('bx-post-tag');
			popupTag = new BX.PopupWindow('bx-post-tag-popup', popupBindElement, {
				lightShadow : false,
				offsetTop: 8,
				offsetLeft: 10,
				autoHide: true,
				closeByEsc: true,
				zIndex: -910,
				bindOptions: {position: "bottom"},
				buttons: [
					new BX.PopupWindowButton({
						text : BX.message('TAG_ADD'),
						events : {
							click : function() {
								addTag();
							}
						}
					})
				]
			});

			popupTag.setContent(BX('post-tags-input'));
			tagInput = BX.findChild(BX('post-tags-input'), {'tag': 'input' });
			BX.bind(tagInput, "keydown", BX.proxy(__onKeyTags, this ));
			BX.bind(tagInput, "keyup", BX.proxy(__onKeyTags, this ));
			<?
			if(!empty($arParams["TAGS"]) && $bHasTags)
			{
				?>
				var el = BX.findChild(BX('<?=$arParams["FORM_ID"]?>'), {'className': /feed-add-post-tags-block/ }, true, false);
				BX.show(el);<?
			}
			?>
		});
	}
</script>
<?
}

/***************** Upload files ************************************/
if (!empty($arParams["UPLOAD_FILE"]))
{
	if (array_key_exists("USER_TYPE_ID", $arParams["UPLOAD_FILE"]) &&
		$arParams["UPLOAD_FILE"]["USER_TYPE_ID"] == "file")
	{
		if (!function_exists("__main_post_form_replace_template"))
		{
			function __main_post_form_replace_template($arResult, $arParams)
			{
				$CID = $GLOBALS["APPLICATION"]->IncludeComponent(
					'bitrix:main.file.input',
					'drag_n_drop',
					array(
						'INPUT_NAME' => $arParams["arUserField"]["FIELD_NAME"],
						'INPUT_NAME_UNSAVED' => 'FILE_NEW_TMP',
						'INPUT_VALUE' => $arResult["VALUE"],
						'MAX_FILE_SIZE' => intval($arParams['arUserField']['SETTINGS']['MAX_ALLOWED_SIZE']),
						'MULTIPLE' => $arParams['arUserField']['MULTIPLE'],
						'MODULE_ID' => 'uf',
						'ALLOW_UPLOAD' => 'A',
					)
				);
				return true;
			}
		}
		$eventHandlerID = AddEventHandler('main', 'system.field.edit.file', "__main_post_form_replace_template");

		$APPLICATION->IncludeComponent(
			"bitrix:system.field.edit",
			"file",
			array("arUserField" => $arParams["UPLOAD_FILE"]),
			null,
			array("HIDE_ICONS" => "Y")
		);

		RemoveEventHandler('main', 'system.field.edit.file', $eventHandlerID);
	}
	elseif (!empty($arParams["UPLOAD_FILE"]["INPUT_NAME"]))
	{
		$CID = $GLOBALS["APPLICATION"]->IncludeComponent(
			'bitrix:main.file.input',
			'drag_n_drop',
			array(
				'INPUT_NAME' => $arParams["UPLOAD_FILE"]["INPUT_NAME"],
				'INPUT_NAME_UNSAVED' => 'FILE_NEW_TMP',
				'INPUT_VALUE' => $arParams["UPLOAD_FILE"]["INPUT_VALUE"],
				'MAX_FILE_SIZE' => $arParams["UPLOAD_FILE"]["MAX_FILE_SIZE"],
				'MULTIPLE' => $arParams["UPLOAD_FILE"]["MULTIPLE"],
				'MODULE_ID' => $arParams["UPLOAD_FILE"]["MODULE_ID"],
				'ALLOW_UPLOAD' => $arParams["UPLOAD_FILE"]["ALLOW_UPLOAD"],
				'ALLOW_UPLOAD_EXT' => $arParams["UPLOAD_FILE"]["ALLOW_UPLOAD_EXT"],
				'INPUT_CAPTION' => $arParams["UPLOAD_FILE"]["INPUT_CAPTION"]
			)
		);
	}
}

if (!empty($arParams["UPLOAD_WEBDAV_ELEMENT"]))
{
	$APPLICATION->IncludeComponent(
		"bitrix:system.field.edit",
		"webdav_element",
		array("arUserField" => $arParams["UPLOAD_WEBDAV_ELEMENT"]),
		null,
		array("HIDE_ICONS" => "Y")
	);
}
if (!empty($arParams["FILES"]["VALUE"]) && $arParams["FILES"]["SHOW"] != "N")
{
?>
<div class="feed-add-post-files-block">
	<div class="feed-add-post-files-title feed-add-post-p"><?=GetMessage("MPF_FILES")?></div>
	<div class="feed-add-post-files-list-wrap">
		<div class="feed-add-photo-block-wrap" id="post-form-files">
			<?=implode($arParams["FILES"]["VALUE_HTML"])?>
		</div>
	</div>
</div>
<?
}
?>
</div>