/////////////////////////////////////////////////////////////////////////////////////
// WhileActivity
/////////////////////////////////////////////////////////////////////////////////////
WhileActivity = function()
{
	var ob = new BizProcActivity();
	ob.Type = 'WhileActivity';

	ob.BizProcActivityDraw = ob.Draw;
	ob.Draw = function (container)
	{
		if(ob.childActivities.length == 0 )
		{
			ob.childActivities = [new SequenceActivity()];
			ob.childActivities[0].parentActivity = ob;
		}

		ob.container = container.appendChild(document.createElement('DIV'));
		if(!jsUtils.IsIE())
			ob.container.className = 'parallelcontainer';

		ob.BizProcActivityDraw(ob.container);
		ob.activityContent = null;

		//ob.div.className = 'parallelcontainer';
		ob.div.style.position = 'relative';
		ob.div.style.top = '12px';

		ob.childsContainer = ob.container.appendChild(_crt(1, 3));
		ob.childsContainer.rows[0].cells[0].width = '15%';
		ob.childsContainer.rows[0].cells[1].width = '70%';
		ob.childsContainer.rows[0].cells[2].width = '15%';
		ob.childsContainer.rows[0].cells[1].style.border = '2px #dfdfdf dashed';
		ob.childsContainer.id = ob.Name;
		//ob.childsContainer.style.background = '#FFFFFF';

		ob.childsContainer.rows[0].cells[1].style.padding = '10px';

		ob.childActivities[0].Draw(ob.childsContainer.rows[0].cells[1]);
	}


	ob.CheckFields = function ()
	{
		return true;
	}

	return ob;
}
