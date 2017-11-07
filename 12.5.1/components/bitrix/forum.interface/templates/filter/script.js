/* Filter */
var ForumFilter = {
	ShowFilter : function(switcher, id)
	{
		var container = document.getElementById('container_' + id);
		if (!container)
			return false;
		pos = jsUtils.GetRealPos(switcher);
		if (typeof (fFilter) != "object")
		{
			window.fFilter = new ForumPopupMenu();
			window.bRemoveElement = false;
		}
		fFilter.PopupShow({"left" : 0, "top" : 0}, container, pos);
	},
	
	CheckFilter : function(id, name)
	{
		var switcher = document.getElementById('forum_filter_' + id + '_' + name);
		var form = document.getElementById('forum_form_' + id);
		var form = switcher.form;
		if (!switcher || !form)
			return false;

		switcher.checked = !switcher.checked;
		
		var items = [switcher];
		var TID = CPHttpRequest.InitThread();

		if (switcher.value == 'all')
		{
			items = form.elements['forum_filter[]'];
			if (!items || !items.length)
				items = [];
		}
		
		for (i = 0; i < items.length; i++)
		{
			if ((items[i].type == "checkbox") && (items[i].value != "all"))
			{
				items[i].checked = switcher.checked;
				action = (items[i].checked ? 'show' : 'hide');
				
				this.ShowHide(id, items[i].value, action);
				
				CPHttpRequest.SetAction(TID, function(data){})
				CPHttpRequest.Send(TID, '/bitrix/components/bitrix/forum.interface/user_settings.php', {"action" : "set_filter", "filter_name":items[i].value, "filter_show":action, "sessid":form.sessid.value});
			}
		}
		return true;
	},
	
	ShowHide : function(id, name, action)
	{
		var action = (action == 'show' ? 'show' : 'hide');
		var row = document.getElementById("row_" + id + "_" + name);
		if (!row)
			return false;

		if (action == 'show')
		{
			try{row.style.display = 'table-row';}
			catch(e){row.style.display = 'block';}
		}
		else
		{
			row.style.display = 'none';
		}
		return true;
	}
}
/* Filter */