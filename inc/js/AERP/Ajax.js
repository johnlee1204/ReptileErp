Ext.define('AERP.Ajax', {
	//requires: ['Ext.Ajax','Ext.Msg'],	
	singleton: true,
	//Allows overruide of default configs. Set any or all of them.
	setDefaultConfig:function(config){
		this.defaultConfig = config;
	},
	request:function(config){
		var requestObj = {
			url:''
			,params:{}
			,method :'POST'
			,errorTitle: 'Error!'
			,errorMessage: 'Fatal Error! Please try again. <BR><BR>If error continues, contact support!'
			,notificationHandler: false
			,mask: false
		};
		Ext.Object.merge(requestObj, this.defaultConfig || {});
		Ext.Object.merge(requestObj, config);
		
		requestObj.userSuccess = config.success || Ext.emptyFn;
		requestObj.userFailure = config.failure || Ext.emptyFn;
		requestObj.userScope = config.scope || Ext.emptyFn;
		
		requestObj.success = this.success;
		requestObj.failure = this.failure;
		requestObj.scope = this;
		if(requestObj.mask) {
			requestObj.mask.mask();
		}
		if(config.jsonp && config.jsonp === true){
			return Ext.data.JsonP.request(requestObj);
		}else{
			return Ext.Ajax.request(requestObj);
		}
		
	},
	success:function(response, options){
		if(options.mask) {
			options.mask.unmask();
		}
		var result;

		try{
			result = Ext.decode(response.responseText);
		}catch(Err){
			this.userNotification(options, options.errorMessage);

			options.userFailure.call(options.userScope);
			return false;
		}

		if(result && result.success && result.success === true){
			options.userSuccess.call(options.userScope, result);
		}else{
			if(result && result.error){
				this.userNotification(options, result.error);
			}else{
				this.userNotification(options, options.errorMessage);
			}
			options.userFailure.call(options.userScope);
		}
	},
	failure:function(response, options){
		if(options.mask) {
			options.mask.unmask();
		}
		if(response.aborted && response.aborted === true){
			options.userFailure.call(options.userScope);
			return true;
		}
		
		var result;

		try{
			result = Ext.decode(response.responseText);
		}catch(Err){
			this.userNotification(options, options.errorMessage);

			options.userFailure.call(options.userScope);
			return false;
		}
		if(result && result.error){
			this.userNotification(options, result.error);
		}else{
			this.userNotification(options, options.errorMessage);
		}
		options.userFailure.call(options.userScope);
	},
	userNotification:function(options, errorMessage){
		if(options.notificationHandler){
			options.notificationHandler.call(options.userScope, errorMessage, options);
		}else{
			Ext.Msg.alert(options.errorTitle, errorMessage);
		}
	}
});

AERP.AjaxRequest = AERP.Ajax.request.bind(AERP.Ajax);