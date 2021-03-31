/*
 * File: app/view/LaborForm.js
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

Ext.define('Schedule.view.LaborForm', {
	extend: 'Ext.panel.Panel',
	alias: 'widget.laborform',

	mixins: [
		'DocForm'
	],
	requires: [
		'Schedule.view.LaborFormViewModel',
		'Ext.toolbar.Toolbar',
		'Ext.form.field.Text'
	],

	viewModel: {
		type: 'laborform'
	},
	layout: 'vbox',
	bodyPadding: 10,
	bodyStyle: 'background:none',
	defaultListenerScope: true,

	dockedItems: [
		{
			xtype: 'toolbar',
			dock: 'top',
			itemId: 'laborFormToolbar'
		}
	],
	items: [
		{
			xtype: 'textfield',
			itemId: 'startTime',
			fieldLabel: 'Start Time'
		},
		{
			xtype: 'textfield',
			itemId: 'endTime',
			fieldLabel: 'End Time'
		},
		{
			xtype: 'textfield',
			itemId: 'hoursWorked',
			fieldLabel: 'Hours Worked'
		}
	],
	listeners: {
		afterrender: 'onPanelAfterRender',
		docFormStateChanged: 'onPanelDocFormStateChangeD'
	},

	onPanelAfterRender: function(component, eOpts) {
		this.docFormInit({
			toolbarId:'laborFormToolbar',
			saveFn:'updateLabor',
			deleteFn:'deleteLabor'
		});
	},

	onPanelDocFormStateChangeD: function(panel) {
		let readOnlyFields = ['hoursWorked'];

		for(let i in readOnlyFields) {
			let field = this.queryById(readOnlyFields[i]);

			field.setReadOnly(true);
			field.addCls('docFormReadOnly');
		}
	},

	readLabor: function(laborId) {
		AERP.Ajax.request({
			url:'/Schedule/readLabor',
			jsonData:{laborId:laborId},
			success:function(reply) {
				this.laborId = laborId;
				this.docFormLoadFormData(reply);
			},
			scope:this,
			mask:this
		});
	},

	updateLabor: function() {
		let jsonData = this.docFormGetAllFieldValues();
		jsonData.laborId = this.laborId;

		AERP.Ajax.request({
			url:'/Schedule/updateLabor',
			jsonData:jsonData,
			success:function(reply) {
				this.readLabor(this.laborId);
				this.fireEvent('laborchanged');
			},
			scope:this,
			mask:this
		});
	},

	deleteLabor: function() {
		AERP.Ajax.request({
			url:'/Schedule/deleteLabor',
			jsonData:{laborId:this.laborId},
			success:function(reply) {
				this.laborId = null;
				this.docFormReset();
				this.fireEvent('laborchanged');
			},
			scope:this,
			mask:this
		});
	}

});