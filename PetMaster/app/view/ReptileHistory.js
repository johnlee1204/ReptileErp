/*
 * File: app/view/ReptileHistory.js
 *
 * This file was generated by Sencha Architect
 * http://www.sencha.com/products/architect/
 *
 * This file requires use of the Ext JS 6.6.x Classic library, under independent license.
 * License of Sencha Architect does not include license for Ext JS 6.6.x Classic. For more
 * details see http://www.sencha.com/license or contact license@sencha.com.
 *
 * This file will be auto-generated each and everytime you save your project.
 *
 * Do NOT hand edit this file.
 */

Ext.define('PetMaster.view.ReptileHistory', {
	extend: 'Ext.panel.Panel',
	alias: 'widget.reptilehistory',

	requires: [
		'PetMaster.view.ReptileHistoryViewModel'
	],

	viewModel: {
		type: 'reptilehistory'
	},
	title: 'History',
	defaultListenerScope: true,

	layout: {
		type: 'vbox',
		align: 'stretch'
	},
	listeners: {
		afterrender: 'onPanelAfterRender'
	},

	onPanelAfterRender: function(component, eOpts) {
		Ext.Loader.setPath('Log', '/Log/app');
		this.ApplicationLogs = Ext.create('Log.view.ApplicationLogs', {flex:1});
		this.ApplicationLogs.singleApp = 'PetMaster';
		this.ApplicationLogs.hideFilters = true;
		this.ApplicationLogs.hideData = true;
		this.add(this.ApplicationLogs);
	},

	filterPet: function(petId) {
		if(!petId){
		    return;
		}

		this.ApplicationLogs.manualFilters = {
		    searchColumn:'petId',
		    searchOperation:'=',
		    searchTerm:petId
		};

		//If the log has it's metadata loaded and the grid exists, load the data, else wait for the logMetadataLoaded event, once.
		if(this.ApplicationLogs.logMetadataLoaded){
		    this.ApplicationLogs.loadAppLog();
		}else{
		    this.ApplicationLogs.on('logMetadataLoaded',function(){
		        this.ApplicationLogs.loadAppLog();
		    },this,{single:true});
		}
	}

});