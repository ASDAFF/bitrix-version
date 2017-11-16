function jsAccTypeChanged(form_id, element_id, flag)
{
	var _form = document.getElementById(form_id);
	var _element = document.getElementById(element_id);
	if(_form && _element)
	{
		if(flag)
			_element.style.display = 'none';
		else
			_element.style.display = 'block';
	}
}

function addNewTableRow(tableID, col_count, regexp, rindex)
{
	var tbl = document.getElementById(tableID);
	var cnt = tbl.rows.length;
	var oRow = tbl.insertRow(cnt-1);

	for(var i=0;i<col_count;i++)
	{
		var oCell = oRow.insertCell(i);
		var html = tbl.rows[cnt-2].cells[i].innerHTML;
		oCell.innerHTML = html.replace(regexp,
			function(html)
			{
				return html.replace('[n'+arguments[rindex]+']', '[n'+(1+parseInt(arguments[rindex]))+']');
			}
		);
	}
}

function jsDelete(form_id, message)
{
	var _form = document.getElementById(form_id);
	var _flag = document.getElementById('action');
	if(_form && _flag)
	{
		if(confirm(message))
		{
			_flag.value = 'delete';
			_form.submit();
		}
	}
}
