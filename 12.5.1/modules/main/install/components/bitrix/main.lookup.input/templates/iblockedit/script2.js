JCMainLookupAdminSelector = function(arParams)
{
	JCMainLookupAdminSelector.superclass.constructor.apply(this,[arParams]);
};

JCMainLookupAdminSelector.prototype.SetTokenInput = function(arParams, arEventParams)
{
	if (arEventParams.CONTROL_ID != this.arParams.CONTROL_ID)
		return;

	if (null == this.VALUE_CONTAINER) return;
	if (null == arEventParams.TOKEN.DATA || null == arEventParams.TOKEN.DATA.ID) return;

	if (null == arEventParams.TOKEN.INPUT)
	{
		arEventParams.TOKEN.INPUT = document.createElement('INPUT');
		arEventParams.TOKEN.INPUT.type = 'hidden';
		arEventParams.TOKEN.INPUT.name = this.arParams.INPUT_NAME;
		arEventParams.TOKEN.INPUT.value = arEventParams.TOKEN.DATA.ID;
	}

	if (arEventParams.TOKEN.ACTIVE && null == arEventParams.TOKEN.INPUT.parentNode)
	{
		this.VALUE_CONTAINER.appendChild(arEventParams.TOKEN.INPUT);
		jsUtils.onCustomEvent('onLookupInputChange', {'CONTROL_ID': this.arParams.CONTROL_ID, 'ACTION': 'add', 'DATA': arEventParams.TOKEN.DATA});
	}
	else if (!arEventParams.TOKEN.ACTIVE && null != arEventParams.TOKEN.INPUT.parentNode)
	{
		this.VALUE_CONTAINER.removeChild(arEventParams.TOKEN.INPUT);
		jsUtils.onCustomEvent('onLookupInputChange', {'CONTROL_ID': this.arParams.CONTROL_ID, 'ACTION': 'remove', 'DATA': arEventParams.TOKEN.DATA});
	}
};

JCMainLookupAdminSelector.prototype.ClearForm = function()
{
	if ((undefined != this.VALUE_CONTAINER) && (null != this.VALUE_CONTAINER))
	{
		while (this.VALUE_CONTAINER.hasChildNodes())
		{
			var obChild = this.VALUE_CONTAINER.lastChild;
			this.VALUE_CONTAINER.removeChild(obChild);
		}
	}
	if ((undefined != this.VISUAL) && (null != this.VISUAL))
	{
		this.VISUAL.Reset(true, false);
		if(this.VISUAL.TEXT.type.toLowerCase() == "textarea")
		{
			if ((undefined != this.VISUAL.arParams.MIN_HEIGHT) && (null != this.VISUAL.arParams.MIN_HEIGHT))
				this.VISUAL.TEXT.style.height = this.VISUAL.arParams.MIN_HEIGHT + 'px';
		}
	}
};

JCMainLookupAdminSelector.prototype.Clear = function()
{
	JCMainLookupAdminSelector.superclass.Clear.apply(this, arguments);
};