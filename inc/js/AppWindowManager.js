Ext.define('AppWindowManagerClass', {
	extend: 'Ext.mixin.Observable', //This gives us our event functions

	/*
		windows:{
			activity:{
				window:<Ext.Window>,
				app:<Ext.app> (Most likely docForm)
			},
			quote:{
				window:<Ext.Window>,
				app:<Ext.app> (Most likely docForm)
			},
			...
		}
	*/
	windows:{},
	windowDefaults:{
		constrainHeader:true,
		resizable: true,
		layout: 'fit',
		closeAction: 'hide',
		liveDrag:true,
		minWidth:200,
		minHeight:100
	},

	appHandlers: {},
	pendingListeners: {},
	app:{
		'dropDownSelectionEditor':{
			loaderClass:'DropDownSelectionEditor',
			loaderPath:'/DropDownSelectionEditor/app',
			appClass: 'DropDownSelectionEditor.view.DropDownSelectionEditor',
			dataLoader: 'readSelections',
			windowConfig:{
				title:'Selection Editor'
			}
		},
		'job':{
			loaderClass:'Job',
			loaderPath:'/Job/app',
			appClass: 'Job.view.Job',
			dataLoader: 'readJob',
			windowConfig:{
				title:'Job'
			}
		}
	},
	appLink:function(appId, appConfig){

		if(!this.fireEvent('appLink'+appId, appConfig) ){//lets app handle opening the window(ex: changing tab)
			return;
		}

		if(this.app[appId].appLinkHandler){
			this.app[appId].appLinkHandler(appConfig);
			return;
		}

		AppWindowManager.showAppWindow(appId, appConfig);
	},
	//deprecated - dont use
	show:function(appId,appConfig){
		this.appLink(appId,appConfig);
	},
	showAppWindow:function(appId,appConfig){

		if(!this.app.hasOwnProperty(appId)){
			console.error('undefined app with appId '+appId);
			return;
		}
		let config = Ext.Object.merge({}, this.app[appId]);
		Ext.Object.merge(config, appConfig || {});

		let newAppWin;
		if(config.multiWindow){
			newAppWin = this.createWindow(appId, config);
		}else{
			if(!this.windows.hasOwnProperty(appId)){
				newAppWin = this.createWindow(appId, config);
			}else{
				newAppWin = this.windows[appId][0];
			}
		}


		//only shows window if not showing main parent(form)
		//if (this.windows[appId].window !== null) {
		//}

		//focuses on new window or main parent panel
		//(this.windows[appId].window || this.windows[appId].app).focus();

		newAppWin.appShowCallbackCalled = false;

		if(config.multiWindow){
			newAppWin.window.show(false, function(){
				if(newAppWin.appDataLoaded && !newAppWin.appShowCallbackCalled){
					newAppWin.appShowCallbackCalled = true;
					this.appShowCallback(newAppWin, config);
				}
			}, this);
		}else{
			if(newAppWin.window.isVisible()){
				if(newAppWin.appDataLoaded && !newAppWin.appShowCallbackCalled){
					newAppWin.appShowCallbackCalled = true;
					this.appShowCallback(newAppWin, config);
				}
				newAppWin.window.focus();
			}else{
				newAppWin.window.show(false, function(){
					if(newAppWin.appDataLoaded && !newAppWin.appShowCallbackCalled){
						newAppWin.appShowCallbackCalled = true;
						this.appShowCallback(newAppWin, config);
					}
				}, this);
			}
		}

	},

	appShowCallback:function(appWin, config){
		if(config.hasOwnProperty('dataKey')){
			if(config.hasOwnProperty('dataLoader')){
				appWin.app[config.dataLoader].call(appWin.app, config.dataKey);
			}else{
				console.error("dataKey sent without dataLoader defined for "+appWin.appId);
			}
		}

		if(config.hasOwnProperty('callback')){
			var scope = config.hasOwnProperty('callbackScope') ? config.callbackScope : this;
			config.callback.call(scope, appWin);
		}
	},

	createWindow:function(appId, config) {
		Ext.Loader.setPath(config.loaderClass, config.loaderPath);

		var currentApp = {
			appDataLoaded: false,
			appId: appId,
			window: null
		};

		var listeners = [];
		if(config.hasOwnProperty('listeners')) {
			listeners = [config.listeners];
		}
		if(this.pendingListeners.hasOwnProperty(appId)) {
			listeners = Ext.Array.merge(listeners, this.pendingListeners[appId]);
		}

		listeners.push({
			changetitle: {
				scope:this,
				fn:function(title){
					currentApp.window.setTitle(title);
					return false;
				}
			},
			windowclose: {
				scope:this,
				fn:function(){
					currentApp.window.hide();
				}
			},
			appdataloaded: {
				single: true,
				scope: this,
				fn: function () {
					currentApp.appDataLoaded = true;
					currentApp.appShowCallbackCalled = true;
					this.appShowCallback(currentApp, config);
				}
			}
		});

		var appConfig = {listeners:listeners};
		Ext.apply(appConfig, config.appConfig || {});

		var form = Ext.create(config.appClass, appConfig);

		var windowConfig = {listeners:{}, items:form};
		Ext.apply(windowConfig, config.windowConfig || {}, this.windowDefaults);

		if(windowConfig.title) {
			form.title = "";
		}

		if(config.docForm){
			windowConfig.listeners.beforeclose = function(window){
				//before closing window, check docform for unsaved changes
				if(window.safeToClose){
					window.safeToClose = false;
					return true;
				}
				window.windowApp.docFormUnsavedChangesConfirmContinue(function(){
					window.safeToClose = true;
					window.windowApp.docFormReset();
					window.close();
				});
				//Stop the window close event for now.
				//The unsaved changes function will close the window.
				return false;
			};
		}
		windowConfig.itemId = appId+'AppWin';

		currentApp.window = Ext.create('Ext.window.Window', windowConfig);
		currentApp.window.windowApp = form; //save reference to app


		currentApp.app = form;

		if(!this.windows[appId]){
			this.windows[appId] = [];
		}
		this.windows[appId].push(currentApp);

		return currentApp;

	},

	appOn:function(appId, listeners) {
		if(this.windows.hasOwnProperty(appId)){
			Ext.each(this.windows[appId],function(win){
				win.app.on(listeners);
			});
		} else {
			if(this.pendingListeners.hasOwnProperty(appId)) {
				this.pendingListeners[appId].push(listeners);
			} else {
				this.pendingListeners[appId] = [listeners];
			}
		}
	}

});

AppWindowManager = new AppWindowManagerClass();