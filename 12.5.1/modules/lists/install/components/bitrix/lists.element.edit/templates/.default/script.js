function addNewTableRow(tableID, col_count, regexp, rindex)
{
	var tbl = document.getElementById(tableID);
	var cnt = tbl.rows.length;
	var oRow = tbl.insertRow(cnt);

	for(var i=0;i<col_count;i++)
	{
		var oCell = oRow.insertCell(i);
		var html = tbl.rows[cnt-1].cells[i].innerHTML;
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

function jsStopBP(form_id, bp_id)
{
	var _form = document.getElementById(form_id);
	var _flag = document.getElementById('action');
	var _stop = document.getElementById('stop_bizproc');
	if(_form && _flag && _stop)
	{
		_flag.value = 'stop_bizproc';
		_stop.value = bp_id;
		_form.submit();
	}
}
