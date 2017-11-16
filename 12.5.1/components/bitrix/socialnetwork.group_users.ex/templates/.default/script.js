var waitDiv = null;
var waitPopup = null;
var waitTimeout = null;
var waitTime = 500;

function __GUEtoggleCheckbox(ev, block, user_code)
{
	ev = ev || window.event;

	if (user_code == 'undefined' || !user_code)
		return false;

	var type = user_code.substr(0, 1);
	var user_id_tmp = parseInt(user_code.substr(1));

	switch (type) {
		case 'M':
			if (BX.util.in_array(user_id_tmp, actionUsers['Moderators']))
				actionUsers['Moderators'].splice(BX.util.array_search(user_id_tmp, actionUsers['Moderators']), 1);
			else
				actionUsers['Moderators'][actionUsers['Moderators'].length] = user_id_tmp;
			break;
		case 'U':
			if (BX.util.in_array(user_id_tmp, actionUsers['Users']))
				actionUsers['Users'].splice(BX.util.array_search(user_id_tmp, actionUsers['Users']), 1);
			else
				actionUsers['Users'][actionUsers['Users'].length] = user_id_tmp;
			break;
		case 'B':
			if (BX.message("GUEUseBan") == "Y")
			{
				if (BX.util.in_array(user_id_tmp, actionUsers['Banned']))
					actionUsers['Banned'].splice(BX.util.array_search(user_id_tmp, actionUsers['Banned']), 1);
				else
					actionUsers['Banned'][actionUsers['Banned'].length] = user_id_tmp;
			}
			break;
		default:
			return false;
	}

	var check_box = BX.findChild(block, { tagName: 'input' }, true, false);

	if(ev.target == check_box || ev.srcElement == check_box){
		BX.toggleClass(block.parentNode, 'sonet-members-member-block-active');
		return false;
	}
	else{
		BX.toggleClass(block.parentNode, 'sonet-members-member-block-active');
		check_box.checked = check_box.checked == true ? false : true;
	}

	BX.PreventDefault(ev);
}

function __GUEShowMenu(bindElement, type)
{
	if (!type)
		type = 'users';

	var arItems = [];

	if (type == 'users')
	{
		if (BX.message("GUEUserCanInitiate"))
			arItems[arItems.length] = { text : BX.message('GUEAddToUsersTitle'), className : "menu-popup-no-icon", onclick : function(e) {__GUEAddToUsers(this.popupWindow, e); return BX.PreventDefault(e); } };

		if (BX.message("GUEUserCanModifyGroup"))
		{
			arItems[arItems.length] = { text : BX.message('GUEAddToModeratorsTitle'), className : "menu-popup-no-icon", onclick : function(e) {__GUEAddToModerators(this.popupWindow); return BX.PreventDefault(e); } };
			arItems[arItems.length] = { text : BX.message('GUEExcludeFromGroupTitle'), className : "menu-popup-no-icon", onclick : function(e) { __GUEExcludeFromGroup(this.popupWindow); return BX.PreventDefault(e); } };
		}

		if (BX.message("GUEUserCanModifyGroup"))
			arItems[arItems.length] = { text : BX.message('GUESetGroupOwnerTitle'), className : "menu-popup-no-icon", onclick : function(e) { __GUESetGroupOwner(this.popupWindow); return BX.PreventDefault(e); } };
	}
	else if (type == 'moderators')
	{
		if (BX.message("GUEUserCanModifyGroup"))
		{
			arItems[arItems.length] = { text : BX.message('GUEExcludeFromModeratorsTitle'), className : "menu-popup-no-icon", onclick : function(e) { __GUEExcludeFromModerators(this.popupWindow); return BX.PreventDefault(e); } };
			arItems[arItems.length] = { text : BX.message('GUEExcludeFromGroupTitle'), className : "menu-popup-no-icon", onclick : function(e) { __GUEExcludeFromGroup(this.popupWindow); return BX.PreventDefault(e); } };
		}

		if (BX.message("GUEUserCanModifyGroup"))
			arItems[arItems.length] = { text : BX.message('GUESetGroupOwnerTitle'), className : "menu-popup-no-icon", onclick : function(e) { __GUESetGroupOwner(this.popupWindow); return BX.PreventDefault(e); } };
	}
	else if (type == 'ban')
	{
		if (BX.message("GUEUserCanModerateGroup"))
			arItems[arItems.length] = { text : BX.message('GUEUnBanFromGroupTitle'), className : "menu-popup-no-icon", onclick : function(e) { __GUEUnBanFromGroup(this.popupWindow); return BX.PreventDefault(e); } };
	}

	if (arItems.length > 0)
	{
		if (BX.message('GUEIsB24') == "Y")
			var arParams = {
				offsetLeft: -32,
				offsetTop: 4,
				lightShadow: false,
				angle: {position: 'top', offset : 93}
			};
		else
			var arParams = {
				offsetLeft: -32,
				offsetTop: 4,
				lightShadow: false
			};

		BX.PopupMenu.show("gue-menu-" + type, bindElement, arItems, arParams);
	}

}

function __GUEAddToModerators(popup)
{
	if (actionUsers['Users'].length > 0)
	{
		__GUEShowWait();
		BX.ajax({
			url: '/bitrix/components/bitrix/socialnetwork.group_users.ex/ajax.php',
			method: 'POST',
			dataType: 'json',
			data: {'ACTION': 'U2M', 'GROUP_ID': parseInt(BX.message('GUEGroupId')), 'USER_ID' : actionUsers['Users'], 'sessid': BX.bitrix_sessid(), 'site': BX.util.urlencode(BX.message('GUESiteId'))},
			onsuccess: function(data) { __GUEProcessAJAXResponse(data, popup); }
		});
	}
	else
		__GUEShowError(BX.message('GUEErrorUserIDNotDefined'));
}

function __GUEExcludeFromModerators(popup)
{
	if (actionUsers['Moderators'].length > 0)
	{
		__GUEShowWait();
		BX.ajax({
			url: '/bitrix/components/bitrix/socialnetwork.group_users.ex/ajax.php',
			method: 'POST',
			dataType: 'json',
			data: {'ACTION': 'M2U', 'GROUP_ID': parseInt(BX.message('GUEGroupId')), 'USER_ID' : actionUsers['Moderators'], 'sessid': BX.bitrix_sessid(), 'site': BX.util.urlencode(BX.message('GUESiteId'))},
			onsuccess: function(data) { __GUEProcessAJAXResponse(data, popup); }
		});
	}
	else
		__GUEShowError(BX.message('GUEErrorUserIDNotDefined'));
}

function __GUEExcludeFromGroup(popup)
{
	if(confirm(BX.message('GUEExcludeFromGroupConfirmTitle')))
	{
		if (actionUsers['Moderators'].length > 0 || actionUsers['Users'].length > 0)
		{
			var arTmp = BX.util.array_merge(actionUsers['Moderators'], actionUsers['Users']);
			__GUEShowWait();
			BX.ajax({
				url: '/bitrix/components/bitrix/socialnetwork.group_users.ex/ajax.php',
				method: 'POST',
				dataType: 'json',
				data: {'ACTION': 'EX', 'GROUP_ID': parseInt(BX.message('GUEGroupId')), 'USER_ID' : arTmp, 'sessid': BX.bitrix_sessid(), 'site': BX.util.urlencode(BX.message('GUESiteId'))},
				onsuccess: function(data) { __GUEProcessAJAXResponse(data, popup); }
			});
		}
		else
			__GUEShowError(BX.message('GUEErrorUserIDNotDefined'));
	}
}

function __GUEBanFromGroup(popup)
{
	if (actionUsers['Moderators'].length > 0 || actionUsers['Users'].length > 0)
	{
		var arTmp = BX.util.array_merge(actionUsers['Moderators'], actionUsers['Users']);
		__GUEShowWait();
		BX.ajax({
			url: '/bitrix/components/bitrix/socialnetwork.group_users.ex/ajax.php',
			method: 'POST',
			dataType: 'json',
			data: {'ACTION': 'BAN', 'GROUP_ID': parseInt(BX.message('GUEGroupId')), 'USER_ID' : arTmp, 'sessid': BX.bitrix_sessid(), 'site': BX.util.urlencode(BX.message('GUESiteId'))},
			onsuccess: function(data) { __GUEProcessAJAXResponse(data, popup); }
		});
	}
	else
		__GUEShowError(BX.message('GUEErrorUserIDNotDefined'));
}

function __GUEUnBanFromGroup(popup)
{
	if (actionUsers['Banned'].length > 0)
	{
		__GUEShowWait();
		BX.ajax({
			url: '/bitrix/components/bitrix/socialnetwork.group_users.ex/ajax.php',
			method: 'POST',
			dataType: 'json',
			data: {'ACTION': 'UNBAN', 'GROUP_ID': parseInt(BX.message('GUEGroupId')), 'USER_ID' : actionUsers['Banned'], 'sessid': BX.bitrix_sessid(), 'site': BX.util.urlencode(BX.message('GUESiteId'))},
			onsuccess: function(data) { __GUEProcessAJAXResponse(data, popup); }
		});
	}
	else
		__GUEShowError(BX.message('GUEErrorUserIDNotDefined'));
}

function __GUESetGroupOwner(popup)
{
	if (
		(actionUsers['Moderators'].length === 1 || actionUsers['Users'].length === 1)
		&& !(actionUsers['Moderators'].length === 1 && actionUsers['Users'].length === 1)
	)
	{
		if(confirm(BX.message('GUESetGroupOwnerConfirmTitle')))
		{
			__GUEShowWait();
			BX.ajax({
				url: '/bitrix/components/bitrix/socialnetwork.group_users.ex/ajax.php',
				method: 'POST',
				dataType: 'json',
				data: {'ACTION': 'SETOWNER', 'GROUP_ID': parseInt(BX.message('GUEGroupId')), 'USER_ID' : (actionUsers['Moderators'].length === 1) ? actionUsers['Moderators'] : actionUsers['Users'], 'sessid': BX.bitrix_sessid(), 'site': BX.util.urlencode(BX.message('GUESiteId'))},
				onsuccess: function(data) { __GUEProcessAJAXResponse(data, popup); }
			});
		}
	}
	else
		__GUEShowError(BX.message('GUEErrorUserIDIncorrect'));
}

function __GUEProcessAJAXResponse(data, popup)
{
	if (popup == 'undefined' || popup == null || !popup.isShown())
		return false;

	if (data["SUCCESS"] != "undefined" && data["SUCCESS"] == "Y")
	{
		popup.close();
		BX.reload();
	}
	else if (data["ERROR"] != "undefined" && data["ERROR"].length > 0)
	{
		if (data["ERROR"].indexOf("USER_ACTION_FAILED", 0) === 0)
		{
			__GUEShowError(BX.message('GUEErrorActionFailedPattern').replace("#ERROR#", data["ERROR"].substr(20)));
			return false;
		}
		else if (data["ERROR"].indexOf("SESSION_ERROR", 0) === 0)
		{
			__GUEShowError(BX.message('GUEErrorSessionWrong'));
			BX.reload();
		}
		else if (data["ERROR"].indexOf("USER_GROUP_NO_PERMS", 0) === 0)
		{
			__GUEShowError(BX.message('GUEErrorNoPerms'));
			return false;
		}
		else if (data["ERROR"].indexOf("USER_ID_NOT_DEFINED", 0) === 0)
		{
			__GUEShowError(BX.message('GUEErrorUserIDNotDefined'));
			return false;
		}
		else if (data["ERROR"].indexOf("GROUP_ID_NOT_DEFINED", 0) === 0)
		{
			__GUEShowError(BX.message('GUEErrorGroupIDNotDefined'));
			return false;
		}
		else if (data["ERROR"].indexOf("CURRENT_USER_NOT_AUTH", 0) === 0)
		{
			__GUEShowError(BX.message('GUEErrorCurrentUserNotAuthorized'));
			return false;
		}
		else if (data["ERROR"].indexOf("SONET_MODULE_NOT_INSTALLED", 0) === 0)
		{
			__GUEShowError(BX.message('GUEErrorModuleNotInstalled'));
			return false;
		}
		else if (data["ERROR"].indexOf("SONET_GUE_T_OWNER_CANT_EXCLUDE_HIMSELF", 0) === 0)
		{
			__GUEShowError(BX.message('GUEErrorOwnerCantExcludeHimself'));
			return false;
		}
		else
		{
			__GUEShowError(data["ERROR"]);
			return false;
		}
	}
}

function __GUEShowError(errorText)
{
	__GUECloseWait();

	var errorPopup = new BX.PopupWindow('gue-error' + Math.random(), window, {
		autoHide: true,
		lightShadow: false,
		zIndex: 2,
		content: BX.create('DIV', {props: {'className': 'sonet-members-error-text-block'}, html: errorText}),
		closeByEsc: true,
		closeIcon: true
	});
	errorPopup.show();

}

function __GUEShowWait(timeout)
{
	if (timeout !== 0)
	{
		return (waitTimeout = setTimeout(function(){
			__GUEShowWait(0)
		}, 50));
	}

	if (!waitPopup)
	{
		waitPopup = new BX.PopupWindow('gue_wait', window, {
			autoHide: true,
			lightShadow: true,
			zIndex: 2,
			content: BX.create('DIV', {
				props: {
					className: 'sonet-members-wait-cont'
				},
				children: [
					BX.create('DIV', {
						props: {
							className: 'sonet-members-wait-icon'
						}
					}),
					BX.create('DIV', {
						props: {
							className: 'sonet-members-wait-text'
						},
						html: BX.message('GUEWaitTitle')
					})
				]
			})
		});
	}
	else
		waitPopup.setBindElement(window);

	waitPopup.show();
}

function __GUECloseWait()
{
	if (waitTimeout)
	{
		clearTimeout(waitTimeout);
		waitTimeout = null;
	}

	if (waitPopup)
		waitPopup.close();
}

function __GUEAddToUsers(popup, e)
{
	if(!e) e = window.event;

	if (isLeftClick(e))
	{
		sonetGroupIFramePopup.Invite(BX.message("GUEGroupId"), BX.message("GUEGroupName"));
		popup.close();
		return BX.PreventDefault(e);
	}
}

function isLeftClick(event)
{
	if (!event.which && event.button !== undefined)
	{
		if (event.button & 1)
			event.which = 1;
		else if (event.button & 4)
			event.which = 2;
		else if (event.button & 2)
			event.which = 3;
		else
			event.which = 0;
	}

	return event.which == 1 || (event.which == 0 && BX.browser.IsIE());
};