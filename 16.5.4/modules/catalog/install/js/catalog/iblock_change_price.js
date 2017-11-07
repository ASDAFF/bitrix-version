BX.namespace('BX.Catalog.Admin');
BX.Catalog.Admin.IblockChangePrice = function()
{
	var elements =
	{
		selectEl : {},
		inputEl : {},
		checkboxEl : {},
		spanEl : {},
		radioEl : {},
		labelEl : {}
	};
	var tableId = null;
	var messages =
	{
		onePriceType : "",
		nullValue : ""
	};
	return {
		init : function (params)
		{
			elements.selectEl.changing = document.getElementById("tableActionChangingSelect");
			elements.selectEl.unit = document.getElementById("tableUnitsSelect");
			elements.selectEl.priceTypeInitial = document.getElementById("tablePriceTypeIdSelect");
			elements.selectEl.resultMask = document.getElementById("resultMaskSelect");
			elements.selectEl.priceType = document.getElementById("initialPriceTypeSelect");

			elements.spanEl.resultValue = document.getElementById("resultValueSpan");

			elements.inputEl.example = document.getElementById("exampleSourceValueInput");
			elements.inputEl.difference = document.getElementById("differenceValueInput");
			elements.inputEl.valuePrice = document.getElementById("tableValueChangingPriceInput");

			elements.labelEl.priceType = document.getElementById("initialPriceTypeLabel");
			elements.labelEl.difference = document.getElementById("differenceValueLabel");
			elements.labelEl.resultMask = document.getElementById("resultMaskLabel");

			elements.radioEl.radioButtons = document.getElementsByName("formatResultRadio");

			elements.checkboxEl.priceType = document.getElementById("initialPriceTypeCheckbox");
			elements.checkboxEl.difference = document.getElementById("differenceValueCheckbox");
			elements.checkboxEl.resultMask = document.getElementById("resultMaskCheckbox");

			tableId = params.tableReloadId || null;
			messages = params.alertMessages || "";

			BX.bind(elements.inputEl.valuePrice, 'input', BX.delegate(
				function(event)
				{
					this.inputDigitalMask(event.target);
				}, this)
			);

			BX.bind(elements.inputEl.difference, 'input', BX.delegate(
				function(event)
				{
					this.reloadExample();
					this.inputDigitalMask(event.target);
				}, this)
			);

			BX.bind(elements.inputEl.example, 'input', BX.delegate(
				function(event)
				{
					this.reloadExample();
					this.inputDigitalMask(event.target);
				}, this)
			);

			BX.bind(elements.checkboxEl.difference, 'change', BX.delegate(
				function()
				{
					this.reloadExample();
					this.showHideInitialElement(elements.checkboxEl.difference, elements.labelEl.difference, elements.inputEl.difference);
				}, this)
			);

			BX.bind(elements.checkboxEl.resultMask, 'change', BX.delegate(
				function()
				{
					this.reloadExample();
					this.showHideInitialElement(elements.checkboxEl.resultMask,  elements.labelEl.resultMask, elements.selectEl.resultMask);
				}, this)
			);

			BX.bind(elements.checkboxEl.priceType, 'change', BX.delegate(
				function(event)
				{
					if (elements.selectEl.priceType.length == 1)
					{
						event.target.checked = false;
						window.alert(messages.onePriceType);
						return false;
					}
					this.showHideInitialElement(elements.checkboxEl.priceType, elements.labelEl.priceType, elements.selectEl.priceType );
				}, this)
			);

			BX.bind(elements.selectEl.resultMask, 'change', BX.delegate(this.reloadExample, this));
			BX.bindDelegate(document.getElementById('chp_radioTable'), 'change', { 'name': 'formatResultRadio' }, BX.proxy(this.reloadExample, this));
			BX.bind(elements.radioEl, 'change', BX.delegate(this.reloadExample, this));

			BX.bind(document.getElementById("savebtn"), 'click', BX.delegate(
				function()
				{
					if (elements.inputEl.valuePrice.value !== "" ||  elements.inputEl.valuePrice.value != 0)
					{
						var diffValue = 0;
						var initialPriceId = 0;
						var checkedRadio = document.querySelector('input[name="formatResultRadio"]:checked');
						document.getElementsByName("chprice_format_result")[0].value = checkedRadio.value;
						document.getElementsByName("chprice_result_mask")[0].value = elements.selectEl.resultMask.options[elements.selectEl.resultMask.selectedIndex].value;
						if (elements.checkboxEl.difference.checked)
						{
							diffValue = elements.inputEl.difference.value;
						}
						document.getElementsByName("chprice_difference_value")[0].value = diffValue;

						if (elements.checkboxEl.priceType.checked)
						{
							initialPriceId = elements.selectEl.priceType.options[elements.selectEl.priceType.selectedIndex].value;
						}
						document.getElementsByName("chprice_initial_price_type")[0].value = initialPriceId;

						if(elements.selectEl.changing.value === "add")
						{
							document.getElementsByName("chprice_value_changing_price")[0].value = elements.inputEl.valuePrice.value;
						}
						else
						{
							document.getElementsByName("chprice_value_changing_price")[0].value = (-1)*elements.inputEl.valuePrice.value;
						}
						document.getElementsByName("chprice_units")[0].value = elements.selectEl.unit.options[elements.selectEl.unit.selectedIndex].value;
						document.getElementsByName("chprice_id_price_type")[0].value = elements.selectEl.priceTypeInitial.options[elements.selectEl.priceTypeInitial.selectedIndex].value;
						document.getElementsByName("action_button")[0].value = "change_price";
						BX.submit(top[tableId].FORM, "change_price");
						top.BX.WindowManager.Get().Close();
					}
					else
					{
						window.alert( messages.nullValue );
					}
				}, this)
			);

			this.reloadExample();

			return this;

		},

		showHideInitialElement : function (checkbox, label, input)
		{
			if(checkbox.checked)
			{
				label.classList.remove("inactive-element");
				input.disabled = false;
				input.classList.remove("inactive-element");
			}
			else
			{
				label.classList.add("inactive-element");
				input.disabled = true;
				input.classList.add("inactive-element");
			}
		},

		inputDigitalMask : function (inputElement)
		{
			inputElement.value = inputElement.value.replace(/[^\d,.]*/g, '')
				.replace(/\,/g, '.')
				.replace(/([,.])[,.]+/g, '$1')
				.replace(/^[^\d]*(\d+([.,]\d{0,5})?).*$/g, '$1');
		},

		reloadExample : function ()
		{
			var difference = 0;
			var valueExample = 0;
			var count = 0;
			var inputExample = elements.inputEl.example;
			var inputDifferenceValue =  elements.inputEl.difference;
			var checkboxDifference = elements.checkboxEl.difference;
			var spanResultValue = elements.spanEl.resultValue;
			var maskValue = elements.selectEl.resultMask.options[elements.selectEl.resultMask.selectedIndex].value;
			if (!isNaN(parseFloat(inputExample.value)))
			{
				valueExample = parseFloat(inputExample.value);
			}

			if (checkboxDifference.checked && !isNaN(parseFloat(inputDifferenceValue.value)))
			{
				difference = parseFloat(inputDifferenceValue.value);
			}

			switch (document.querySelector('input[name="formatResultRadio"]:checked').value)
			{
				case "ceil":
					count = Math.ceil((valueExample * maskValue))/maskValue - difference;
					if (count < 0)
					{
						count = 0;
					}
					spanResultValue.innerHTML = count;
					break;
				case "floor":
					count = Math.floor((valueExample * maskValue))/maskValue - difference;
					if (count < 0)
					{
						count = 0;
					}
					spanResultValue.innerHTML = count;
					break;
				case "round":
					count = Math.round((valueExample * maskValue))/maskValue - difference;
					if (count < 0)
					{
						count = 0;
					}
					spanResultValue.innerHTML = count;
			}
		}
	};
};