/*
 * File: app/view/HabitatForm.js
 *
 * This file was generated by Sencha Architect
 * http://www.sencha.com/products/architect/
 *
 * This file requires use of the Ext JS 7.3.x Classic library, under independent license.
 * License of Sencha Architect does not include license for Ext JS 7.3.x Classic. For more
 * details see http://www.sencha.com/license or contact license@sencha.com.
 *
 * This file will be auto-generated each and everytime you save your project.
 *
 * Do NOT hand edit this file.
 */

Ext.define('Habitat.view.HabitatForm', {
	extend: 'Ext.panel.Panel',
	alias: 'widget.habitatform',

	mixins: [
		'DocForm'
	],
	requires: [
		'Habitat.view.HabitatFormViewModel',
		'Ext.toolbar.Toolbar',
		'Ext.form.field.Text',
		'Ext.grid.Panel',
		'Ext.grid.column.Column',
		'Ext.view.Table'
	],

	viewModel: {
		type: 'habitatform'
	},
	layout: 'vbox',
	bodyPadding: 10,
	bodyStyle: 'background:none',
	defaultListenerScope: true,

	dockedItems: [
		{
			xtype: 'toolbar',
			dock: 'top',
			itemId: 'habitatFormToolbar'
		}
	],
	items: [
		{
			xtype: 'textfield',
			itemId: 'habitatName',
			fieldLabel: 'Habitat Name',
			labelAlign: 'right'
		},
		{
			xtype: 'textfield',
			itemId: 'rack',
			fieldLabel: 'Rack',
			labelAlign: 'right'
		},
		{
			xtype: 'gridpanel',
			flex: 1,
			width: 400,
			title: 'Designated Reptiles',
			bind: {
				store: '{DesignatedReptileStore}'
			},
			columns: [
				{
					xtype: 'gridcolumn',
					width: 180,
					dataIndex: 'serial',
					text: 'Serial'
				}
			],
			viewConfig: {
				enableTextSelection: true
			}
		}
	],
	listeners: {
		afterrender: 'onPanelAfterRender'
	},

	onPanelAfterRender: function(component, eOpts) {
		this.docFormInit({
			toolbarId:'habitatFormToolbar',
			addFn:'createHabitat',
			saveFn:'updateHabitat',
			deleteFn:'deleteHabitat'
		});
	},

	readHabitat: function(habitatId) {
		AERP.Ajax.request({
			url:'/Habitat/readHabitat',
			jsonData:{habitatId:habitatId},
			success:function(reply) {
				this.habitatId = habitatId;
				this.docFormLoadFormData(reply);
				this.readDesignatedReptiles(habitatId);
			},
			scope:this,
			mask:this
		});

	},

	readDesignatedReptiles: function(habitatId) {
		AERP.Ajax.request({
			url:'/Habitat/readDesignatedReptiles',
			jsonData:{habitatId:habitatId},
			success:function(reply){
				this.getViewModel().getStore('DesignatedReptileStore').loadData(reply.data);
			},
			scope:this,
			mask:this
		});
	},

	createHabitat: function() {
		AERP.Ajax.request({
			url:'/Habitat/createHabitat',
			jsonData:this.docFormGetAllFieldValues(),
			success:function(reply) {
				this.readHabitat(reply.data);
				this.fireEvent('habitatchanged');
			},
			scope:this,
			mask:this
		});
	},

	updateHabitat: function() {
		let jsonData = this.docFormGetAllFieldValues();
		jsonData.habitatId = this.habitatId;

		AERP.Ajax.request({
			url:'/Habitat/updateHabitat',
			jsonData:jsonData,
			success:function(reply) {
				this.readHabitat(this.habitatId);
				this.fireEvent('habitatchanged');
			},
			scope:this,
			mask:this
		});
	},

	deleteHabitat: function() {
		AERP.Ajax.request({
			url:'/Habitat/deleteHabitat',
			jsonData:{habitatId:this.habitatId},
			success:function(reply) {
				this.habitatId = null;
				this.docFormReset();
				this.fireEvent('habitatchanged');
			},
			scope:this,
			mask:this
		});
	}

});