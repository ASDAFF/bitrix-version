(function(){

	if(typeof BX.chainedSelectorsSLS == 'undefined'){

		BX.chainedSelectorsSLS = function(opts, nf){

			this.parentConstruct(BX.chainedSelectorsSLS, opts);

			BX.merge(this, {
				opts: {
					bindEvents: {
						'after-select-item': function(value){

							if(typeof this.opts.callback == 'string' && this.opts.callback.length > 0 && this.opts.callback in window)
								window[this.opts.callback].apply(this, [value, this]);
						}
					},
					disableKeyboardInput: false,
					dontShowNextChoice: false,
					pseudoValues: [] // values that can be only displayed as selected, but not actually selected
				},
				sys: {
					code: 'slst'
				}
			});
			
			this.handleInitStack(nf, BX.chainedSelectorsSLS, opts);
		};
		BX.extend(BX.chainedSelectorsSLS, BX.ui.chainedSelectors);
		BX.merge(BX.chainedSelectorsSLS.prototype, {

			// member of stack of initializers, must be defined even if does nothing
			init: function(){
				this.pushFuncStack('buildUpDOM', BX.chainedSelectorsSLS);
				this.pushFuncStack('bindEvents', BX.chainedSelectorsSLS);
			},

			// add additional controls
			buildUpDOM: function(){},

			bindEvents: function(){

				var ctx = this,
					so = this.opts;

				if(so.disableKeyboardInput){ //toggleDropDown
					this.bindEvent('after-control-placed', function(adapter){

						var control = adapter.getControl();

						BX.unbindAll(control.ctrls.toggle);
						// spike, bad idea to access fields directly
						BX.bind(control.ctrls.scope, 'click', function(e){
							control.toggleDropDown();
						});
					});
				}

				// quick links
				BX.bindDelegate(this.getControl('quick-locations', true), 'click', {tag: 'a'}, function(){
					ctx.setValueById(BX.data(this, 'id'));
				});
			},

			////////// PUBLIC: free to use outside

			setValueById: function(id){
				this.setValue(id);
			},
			setValueByLocationId: function(id){
				this.setValue(id);
			},

			setValueByCode: function(code){
				//todo
			},

			setTargetValue: function(value){
				this.setTargetInputValue(this.opts.provideLinkBy == 'code' ? this.vars.cache.nodes[value].CODE: value);
				this.fireEvent('after-select-item', [value]);
			},

			////////// PRIVATE: forbidden to use outside (for compatibility reasons)

			controlChangeActions: function(stackIndex, value){

				var ctx = this,
					so = this.opts,
					sv = this.vars,
					sc = this.ctrls;

				this.hideError();

				////////////////

				if(value.length == 0){

					ctx.truncateStack(stackIndex);
					ctx.setTargetValue(ctx.getLastValidValue());

					this.fireEvent('after-select-real-value');

				}else if(BX.util.in_array(value, so.pseudoValues)){

					ctx.truncateStack(stackIndex);
					ctx.setTargetValue(ctx.getLastValidValue());
					this.fireEvent('after-select-item', [value]);

					this.fireEvent('after-select-pseudo-value');

				}else{

					var node = sv.cache.nodes[value];

					if(typeof node == 'undefined')
						throw new Error('Selected node not found in the cache');

					// node found

					ctx.truncateStack(stackIndex);

					if(so.dontShowNextChoice){
						if(node.IS_UNCHOOSABLE)
							ctx.appendControl(value);
					}else{
						if(typeof sv.cache.links[value] != 'undefined' || node.IS_PARENT)
							ctx.appendControl(value);
					}

					if(ctx.checkCanSelectItem(value))
						ctx.setTargetValue(value);

					this.fireEvent('after-select-real-value');
				}
			},

			// adapter to ajax page request
			refineRequest: function(request){

				var newRequest = {};

				if(typeof request.PARENT_VALUE != 'undefined'){ // bundle for PARENT_VALUE will be downloaded

					newRequest = {
						FILTER: BX.merge({
							PARENT_ID: request.PARENT_VALUE
						}, this.opts.query.FILTER),
						
						BEHAVIOUR: BX.merge({
							EXPECT_EXACT: 0,
							PREFORMAT: 1
						}, this.opts.query.BEHAVIOUR),

						SHOW: {
							CHILD_EXISTENCE: 1
						}
					};

					// we are already inside linked sub-tree, no deeper check for SITE_ID needed
					if(typeof newRequest.FILTER.SITE_ID != 'undefined' && typeof this.vars.cache.nodes[request.PARENT_VALUE] != 'undefined' && !this.vars.cache.nodes[request.PARENT_VALUE].IS_UNCHOOSABLE)
						delete(newRequest.FILTER.SITE_ID);

				}else if(typeof request.VALUE != 'undefined') // route will be downloaded
					newRequest = {
						FILTER: BX.merge({
							QUERY: request.VALUE
						}, this.opts.query.FILTER),
						
						BEHAVIOUR: BX.merge({
							EXPECT_EXACT: 1,
							PREFORMAT: 1
						}, this.opts.query.BEHAVIOUR),

						SHOW: {
							PATH: 1,
							CHILD_EXISTENCE: 1 // do we need this here?
						}
					};

				return newRequest;
			},

			// adapter to ajax page responce
			refineResponce: function(responce, request){

				if(responce.length == 0)
					return responce;

				if(typeof request.PARENT_VALUE != 'undefined'){ // it was a bundle request

					var r = {};
					r[request.PARENT_VALUE] = responce['ITEMS'];
					responce = r;

				}else if(typeof request.VALUE != 'undefined'){ // it was a route request

					var levels = {};

					if(typeof responce.ITEMS[0]){

						var parentId = 0;
						for(var k = responce.ITEMS[0]['PATH'].length - 1; k >= 0; k--){
							var itemId = responce.ITEMS[0]['PATH'][k];

							var item = responce.ETC.PATH_ITEMS[itemId];
							item.IS_PARENT = true;

							levels[parentId] = [item];

							parentId = item.VALUE;
						}

						// add item itself
						levels[parentId] = [responce.ITEMS[0]];
					}

					responce = levels;
				}

				return responce;
			},

			showError: function(parameters){

				if(parameters.type != 'server-logic')
					parameters.errors = [this.opts.messages.error]; // generic error on js error

				this.setCSSState('error', this.ctrls.scope);
				this.ctrls.errorMessage.innerHTML = '<p><font class="errortext">'+BX.util.htmlspecialchars(parameters.errors.join(', '))+'</font></p>';
			}
		});
	}

})();