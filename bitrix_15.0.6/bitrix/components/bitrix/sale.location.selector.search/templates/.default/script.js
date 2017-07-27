(function(){

	if(typeof BX.autoCompleteSLS == 'undefined'){

		BX.autoCompleteSLS = function(opts, nf){

			this.parentConstruct(BX.autoCompleteSLS, opts);

			BX.merge(this, {
				opts: {

					usePagingOnScroll: 		true,
					pageSize: 				10,
					//scrollThrottleTimeout: 	100,
					arrowScrollAdditional: 	2,
					pageUpWardOffset: 		3,

					bindEvents: {

						'after-input-value-modify': function(){

							this.ctrls.fullRoute.value = '';
							
						},
						'after-select-item': function(itemId){

							var so = this.opts;

							//this.setEditLink();

							var cItem = this.vars.cache.nodes[itemId];

							var path = cItem.DISPLAY;
							if(typeof cItem.PATH == 'object'){
								for(var i = 0; i < cItem.PATH.length; i++){
									path += ', '+this.vars.cache.path[cItem.PATH[i]];
								}
							}

							this.ctrls.inputs.fake.setAttribute('title', path);
							this.ctrls.fullRoute.value = path;

							if(typeof this.opts.callback == 'string' && this.opts.callback.length > 0 && this.opts.callback in window)
								window[this.opts.callback].apply(this, [itemId, this]);
						},
						'after-deselect-item': function(){
							this.ctrls.fullRoute.value = '';
							this.ctrls.inputs.fake.setAttribute('title', '');
						},
						'before-render-variant': function(itemData){

							if(itemData.PATH.length > 0){
								var path = '';
								for(var i = 0; i < itemData.PATH.length; i++)
									path += ', '+this.vars.cache.path[itemData.PATH[i]];

								itemData.PATH = path;
							}else
								itemData.PATH = '';
						}
					}
				},
				vars: {
					cache: {
						path: {}
					}
				},
				sys: {
					code: 'sls'
				}
			});
			
			this.handleInitStack(nf, BX.autoCompleteSLS, opts);
		}
		BX.extend(BX.autoCompleteSLS, BX.ui.autoComplete);
		BX.merge(BX.autoCompleteSLS.prototype, {

			// member of stack of initializers, must be defined even if do nothing
			init: function(){

				// process options
				if(typeof this.opts.pathNames == 'object')
					BX.merge(this.vars.cache.path, this.opts.pathNames);

				this.pushFuncStack('buildUpDOM', BX.autoCompleteSLS);
				this.pushFuncStack('bindEvents', BX.autoCompleteSLS);
			},

			buildUpDOM: function(){

				var sc = this.ctrls,
					so = this.opts,
					sv = this.vars,
					ctx = this,
					code = this.sys.code;
				
				// full route node
				sc.fullRoute = BX.create('input', {
					props: {
						className: 'bx-ui-'+code+'-route'
					},
					attrs: {
						type: 'text',
						disabled: 'disabled',
						autocomplete: 'off'
					}
				});

				// todo: use metrics instead!
				BX.style(sc.fullRoute, 'paddingTop', BX.style(sc.inputs.fake, 'paddingTop'));
				BX.style(sc.fullRoute, 'paddingLeft', BX.style(sc.inputs.fake, 'paddingLeft'));
				BX.style(sc.fullRoute, 'paddingRight', '0px');
				BX.style(sc.fullRoute, 'paddingBottom', '0px');

				BX.style(sc.fullRoute, 'marginTop', BX.style(sc.inputs.fake, 'marginTop'));
				BX.style(sc.fullRoute, 'marginLeft', BX.style(sc.inputs.fake, 'marginLeft'));
				BX.style(sc.fullRoute, 'marginRight', '0px');
				BX.style(sc.fullRoute, 'marginBottom', '0px');

				if(BX.style(sc.inputs.fake, 'borderTopStyle') != 'none'){
					BX.style(sc.fullRoute, 'borderTopStyle', 'solid');
					BX.style(sc.fullRoute, 'borderTopColor', 'transparent');
					BX.style(sc.fullRoute, 'borderTopWidth', BX.style(sc.inputs.fake, 'borderTopWidth'));
				}

				if(BX.style(sc.inputs.fake, 'borderLeftStyle') != 'none'){
					BX.style(sc.fullRoute, 'borderLeftStyle', 'solid');
					BX.style(sc.fullRoute, 'borderLeftColor', 'transparent');
					BX.style(sc.fullRoute, 'borderLeftWidth', BX.style(sc.inputs.fake, 'borderLeftWidth'));
				}

				BX.prepend(sc.fullRoute, sc.container);

				sc.inputBlock = this.getControl('input-block');
				sc.loader = this.getControl('loader');
			},

			bindEvents: function(){

				var ctx = this;

				// quick links
				BX.bindDelegate(this.getControl('quick-locations', true), 'click', {tag: 'a'}, function(){
					ctx.setValueByLocationId(BX.data(this, 'id'));
				});

				this.vars.outSideClickScope = this.ctrls.inputBlock;
			},

			refineRequest: function(request){

				var filter = {};
				var exact = 0;
				if(typeof request['QUERY'] != 'undefined')
					filter['QUERY'] = request.QUERY;

				if(typeof request['VALUE'] != 'undefined'){
					filter['QUERY'] = request.VALUE;
					exact = 1;
				}

				return {
					FILTER: BX.merge(filter, this.opts.query.FILTER),
					
					BEHAVIOUR: BX.merge({
						EXPECT_EXACT: exact
					}, this.opts.query.BEHAVIOUR),

					SHOW: {
						PATH: 1,
						TYPE_ID: 1
					}
				}
			},

			refineResponce: function(responce, request){

				if(typeof responce.ETC.PATH_NAMES != 'undefined')
					this.vars.cache.path = BX.merge(this.vars.cache.path, responce.ETC.PATH_NAMES);

				return this.refineItems(responce.ITEMS);
			},

			refineItems: function(items){
				for(var k in items){
					items[k].DISPLAY = items[k].NAME;
					items[k].VALUE = items[k].ID;
				}

				return items;
			},

			/*
			fillCache: function(items, key){

				items = this.refineItems(items);
				BX.autoCompleteSLS.superclass.fillCache.apply(this, [items, false]);
			},
			*/

			// custom value getter
			getSelectorValue: function(value){

				if(this.opts.provideLinkBy == 'id')
					return value;

				if(typeof this.vars.cache.nodes[value] != 'undefined')
					return this.vars.cache.nodes[value].CODE;
				else
					return '';
			},

			whenLoaderToggle: function(way){
				BX[way ? 'show' : 'hide'](this.ctrls.loader);
			},

			// location id is just a value in terms of autocomplete
			setValueByLocationId: function(id, autoSelect){
				this.setValue(id, autoSelect);
			},

			setValueByLocationCode: function(code, autoSelect){
				// not implemented
			}

		});
	}

})();