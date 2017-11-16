function __GRESubmitForm(type, action) 
{
	var form_name = (type == "out") ? "form_requests_out" : "form_requests";
	if (BX("requests_action_" + type))
		BX("requests_action_" + type).value = action;
	BX.submit(BX(form_name));
	return false;
}

function __GRECheckedAll(input) 
{
	var input_list = BX.findChildren(input.parentNode.parentNode.parentNode, { tag: 'input' }, true);
	if (!input.checked)
	{
		for(var i=1; i<input_list.length; i++)
		{
			input_list[i].checked = false;
			BX.removeClass(input_list[i].parentNode.parentNode.parentNode, 'invite-list-active')
		}
	}
	else
	{
		for(var i=1; i<input_list.length; i++)
		{
			input_list[i].checked = true;
			BX.addClass(input_list[i].parentNode.parentNode.parentNode, 'invite-list-active')
		}
	}
}

function __GREAddToUsers(e)
{
	e = e || window.event;

	if (__GREIsLeftClick(e))
	{
		sonetGroupIFramePopup.Invite(BX.message("GUEGroupId"), BX.message("GUEGroupName"));
		return BX.PreventDefault(e);
	}
}

function __GREIsLeftClick(event)
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