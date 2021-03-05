
/*
	A Nice little library to generate and manage menu buttons for a toolbar, as well as a context menu on a grid.
	( Basically ContextManager 2.0 - "It makes more sense now" )

	Look for a kitchen sink example!

	Simple Example:
		new NiceGridMenu({
			menuItems:[{action:'loadJob',text:'View Job',icon:'/inc/img/silk_icons/application_go.png',disabled:true}],
			grid:this.queryById('grid'),
			toolbar:this.queryById('toolbar'),
			callbackHandler:function(action,data){
				switch(action){
					case 'loadJob':
						this.viewJob(data.job,data.currentWorkcenterId);
						break;
				}
			},
			doubleClick:'loadJob',
			scope:this
		});

	Advanced Params:
		onSelectionChange - a function that gets btns,selection when the selection changes. Good for changing menus on the fly.
			you can change the buttons right from this function, and it will apply to both the context menu and toolbar.

		filterField - boolean - adds a field to the toolbar which will locally filter grid to all columns
		fieldsToFilter - array - Specifies the field names of the fields you want the filter field to filter by
 */

Ext.define('NiceGridMenu',{
	niceGridMenuAppLinks:{
		job:{appId:'job', icon:'/inc/img/silk_icons/wrench.png', text:'Job', appLinkField:'job'}
	},
	constructor:function(userConfig){
		this.config = userConfig;
		Ext.each(this.config.menuItems,function(menuItem){
			if(menuItem.hasOwnProperty('appLink')) {

				menuItem.icon = this.niceGridMenuAppLinks[menuItem.appLink].icon;
				menuItem.disabled = true;
				if(!menuItem.hasOwnProperty('text')) {
					menuItem.text = this.niceGridMenuAppLinks[menuItem.appLink].text;
				}
				if(!menuItem.hasOwnProperty('appLinkField')) {
					menuItem.appLinkField = this.niceGridMenuAppLinks[menuItem.appLink].appLinkField;
				}
				menuItem.listeners = {
					click:this.appLinkClickHandler,
					scope:this
				}
			}
			if(menuItem.hasOwnProperty('menu')) {
				menuItem.recordData = this.config.recordData;
				menuItem.menu.listeners = {
					click:this.menuClickHandler,
					scope:this
				}
			}
		},this);

		this.config.filterButtons = true

		let dockedItems = this.config.grid.getDockedItems();

		for(let i in dockedItems) {
			if(dockedItems[i].xtype === 'pagingtoolbar') {
				this.config.filterButtons = false;
			}
		}

		if(!this.config.toolbar.ownerCt || !this.config.menuItems) {
			this.config.filterButtons = false;
		}

		if(this.config.filterButtons) {
			this.config.menuItems.push(
				{text:'Filter By Cell', action:'filterField', icon:'/inc/img/silk_icons/hourglass.png', recordData:this.config.recordData, listeners:{click:this.filterClickHandler, scope:this}, hidden:true}
			);
			this.config.filterToolbar = Ext.create("Ext.toolbar.Toolbar", {
				itemId:'filterToolbar',
				//height:27,
				defaultButtonUI:'default',
				items:[
					Ext.create('Ext.container.Container', {html:"Filter: ", margin:'0 0 0 10'})
				]
			});
			this.config.toolbar.ownerCt.addDocked(this.config.filterToolbar)
		}

		this.menu = Ext.create({
			xtype:'menu',
			items:this.config.menuItems,
			listeners:{
				click:this.menuClickHandler,
				scope:this
			}
		});

		this.toolbar = this.config.toolbar;

		//this.toolbar.setHeight(26);

		this.toolbar.defaultButtonUI = 'default';
		this.toolbar.defaults = {
			'margin':'0 0 0 8'
		};

		var insertButtonsAt = 0;
		if(userConfig.hasOwnProperty('insertToolbarBtnsIndex')){
			insertButtonsAt = userConfig.insertToolbarBtnsIndex
		}
		this.toolbar.insert(insertButtonsAt,this.generateToolbarButtons(this.config.menuItems));


		if(userConfig.hasOwnProperty('filterField') && userConfig.filterField === true) {
			this.filterField = Ext.create({
				xtype:'textfield',
				itemId:'niceGridMenuFilterField',
				fieldLabel:'Search',
				labelWidth:50,
				width:135,
				labelAlign:'right',
				listeners:{
					scope:this,
					change:this.filterGrid
				}
			});

			if(userConfig.hasOwnProperty('insertToolbarFilterFieldIndex')) {
				this.toolbar.insert(userConfig.insertToolbarFilterFieldIndex,this.filterField);
			} else {
				this.toolbar.add(this.filterField);
			}

			if(userConfig.hasOwnProperty('fieldsToFilter')) {
				this.fieldsToFilter = userConfig.fieldsToFilter;
			}
		}

		var store = this.config.grid.getStore();
		if(!store){
			console.error("no store found");
		}else{
			if(store.storeId === "ext-empty-store" ){
				if(this.config.grid.config.bind && this.config.grid.config.bind.store){
					var viewModel = this.config.grid.lookupViewModel();
					if(viewModel){
						var storeBind = this.config.grid.config.bind.store.substr(1,this.config.grid.config.bind.store.length-2);
						viewModel.getStore(storeBind).on('datachanged',function(store,eOpts){
							this.config.grid.getSelectionModel().deselectAll();
						},this);
					}else{
						console.error("cannot bind to store - no ViewModel found");
					}
				}else{
					console.error("cannot bind to store - empty store and no bindings found");
				}
			}else{
				store.on('datachanged',function(store,eOpts){
					this.config.grid.getSelectionModel().deselectAll();
				},this);
			}
		}

		this.config.grid.addListener('selectionchange',function(model,selected,eOpts){

			if(this.config.filterButtons) {
				this.getButtons().filterField.hide();
				this.updateContextMenu();
			}

			if(this.config.hasOwnProperty('onSelectionChange')){
				this.runSelectionChange(selected);
			}else{
				if(selected.length == 1){
					this.menu.recordData = selected[0].getData();

					this.toolbar.items.each(function(item){
						if(item.hasOwnProperty('action')){
							item.enable();
						}
					});
				}else{
					this.toolbar.items.each(function(item){
						if(item.hasOwnProperty('action')){
							item.disable();
						}
					});
				}
				this.updateContextMenu();
			}

			this.currentSelection = selected;

			this.toolbar.items.each(this.setItemState, this);
			this.menu.items.each(this.setItemState, this);

		},this);

		if(this.config.hasOwnProperty('doubleClick')){
			var action = this.config.doubleClick;
			this.config.grid.addListener('rowdblclick',function(tableview, record, tr, rowIndex, e, eOpts){
				if(this.menu.items.length > 0){
					this.menu.items.each(function(item){
						if(action === item.action && !item.disabled && !item.hidden){
							this.config.callbackHandler.call(this.config.scope,item.action,this.menu.recordData);
							return false;
						} else if(action === item.appLink && !item.disabled && !item.hidden) {
							this.appLinkClickHandler(item);
							return false;
						}
					},this);
				}
			},this);
		}

		this.config.grid.addListener('rowcontextmenu',function(tableview, record, tr, rowIndex, e, eOpts){

			if(this.config.filterButtons && e.position.column) {
				let cellValue = record.data[e.position.column.dataIndex] + "";
				if(cellValue) {
					e.position.column.text = e.position.column.text.replace('<br>', ' ');
					e.position.column.text = e.position.column.text.replace('<BR>', ' ');
					e.position.column.text = e.position.column.text.replace("\r\n", '');
					cellValue = cellValue.replace('<br>', ' ');
					cellValue = cellValue.replace('<BR>', ' ');
					cellValue = cellValue.replace("\r\n", '');

					this.menu.recordData.filterField = e.position.column.dataIndex;
					this.menu.recordData.filterFieldValue = cellValue;
					this.menu.recordData.filterFieldColumn = e.position.column.text;
					this.getButtons().filterField.show();
					this.getButtons().filterField.setText(e.position.column.text + " = " + cellValue);
					this.updateContextMenu();
				} else {
					this.getButtons().filterField.hide();
					this.updateContextMenu();
				}
			}

			if(this.menu.items.length > 0){
				this.menu.showAt(e.getXY());
			}
			e.stopEvent();
		},this);


	},
	runSelectionChange:function(selected){
		if(!selected){
			selected = [];
		}

		var btns = this.getButtons();

		this.config.onSelectionChange.call(this.config.scope,btns,selected);
		if(selected.length > 0){
			this.menu.recordData = selected[0].getData();
		}

		this.updateContextMenu();
	},
	updateContextMenu:function(){
		var btns = this.getButtons();
		//update update all configs of menu items based on menu buttons (which were probably just changed).
		this.menu.items.each(function(menuItem){
			if(!menuItem.hasOwnProperty('action')) {
				return true;
			}
			menuItem.setIcon(btns[menuItem.action].icon);
			menuItem.setText(btns[menuItem.action].text);
			menuItem.setDisabled(btns[menuItem.action].disabled);
			menuItem.setHidden(btns[menuItem.action].hidden);
		});
	},
	getButtons:function(){
		var btns = {};
		this.toolbar.items.each(function(item){
			if(item.hasOwnProperty('action')) {
				btns[item.action] = item;
			}
		});
		return btns;
	},
	generateToolbarButtons:function(items){
		let buttons = [];

		Ext.each(items,function(menuItem){
			if(menuItem.hasOwnProperty('appLink') && typeof AppWindowManager !== "undefined") {
				buttonListener = this.appLinkClickHandler;
			} else {
				buttonListener = this.toolbarButtonClickHandler;
			}

			buttons.push(Ext.apply({
				xtype: "button",
				listeners: {
					click:buttonListener,
					scope:this
				}
			}, menuItem));
		},this);

		return buttons;
	},
	menuClickHandler:function(menu,item,e,eOps){
		if(!item){
			return;
		}

		this.config.callbackHandler.call(this.config.scope,item.action,this.menu.recordData);
	},
	toolbarButtonClickHandler:function(button,e,eOps){
		this.config.callbackHandler.call(this.config.scope,button.action,this.menu.recordData);
	},
	appLinkClickHandler:function(button,e,eOps) {
		let record = this.menu.recordData;
		if(!record) {
			Ext.Msg.alert("","No Record Selected!");
			return;
		}

		AppWindowManager.appLink(button.appLink, {dataKey:record[button.appLinkField]});
	},
	filterClickHandler: function(button, e, eops) {
		let record = this.menu.recordData;
		if(!record) {
			Ext.Msg.alert("","No Record Selected!");
			return;
		}
		let niceGridMenuFilter = new Ext.util.Filter({
			property: record.filterField,
			value: record.filterFieldValue,
			exactMatch:true
		});
		this.config.filterToolbar.add(Ext.create('Ext.button.Button', {
			text:record.filterFieldColumn + ' = ' + record.filterFieldValue,
			icon:'/inc/img/silk_icons/cross.png',
			niceGridMenuFilter:niceGridMenuFilter,
			margin:'0 0 0 5',
			height:22,
			listeners:{
				scope:this,
				click:function(button) {
					this.config.filterToolbar.remove(button);
					this.config.grid.getStore().removeFilter(button.niceGridMenuFilter);
				}
			}
		}))
		this.config.grid.getStore().filter(niceGridMenuFilter);
	},
	filterGrid: function() {
		let store = this.config.grid.getStore();
		let filters = [];
		let included = [];
		let newValue = this.filterField.getValue();
		newValue = newValue.trim();

		if (newValue.length > 0) {
			let regex = new RegExp(newValue, 'gi');
			var anyMatch = false;
			var f = new Ext.util.Filter({
				filterFn: function(item) {

					for(var i in item.data) {
						if(i === 'id') {
							continue;
						}

						if(this.fieldsToFilter && this.fieldsToFilter.indexOf(i) === -1) {
							continue;
						}
						if( (item.data[i]+"").match(regex) !== null ){
							anyMatch = true;
							return anyMatch;
						}
					}
				}.bind(this)});
			filters.push(f);
		}

		if(this.filters) {
			for(let i in this.filters) {
				store.removeFilter(this.filters[i]);
			}
		}
		this.filters = filters;
		store.filter(filters);
	},
	setItemState: function(item) {
		if(item.hasOwnProperty('appLink')){
			if(this.currentSelection.length > 0) {
				if(this.currentSelection[0].data[item.appLinkField]) {
					item.enable();
				} else {
					item.disable();
				}
			} else {
				item.disable();
			}
		}
	}
});