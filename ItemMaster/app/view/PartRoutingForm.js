/*
 * File: app/view/PartRoutingForm.js
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

Ext.define('ItemMaster.view.PartRoutingForm', {
	extend: 'Ext.panel.Panel',
	alias: 'widget.partroutingform',

	mixins: [
		'DocForm'
	],
	requires: [
		'ItemMaster.view.PartRoutingFormViewModel',
		'Ext.toolbar.Toolbar',
		'Ext.form.field.ComboBox'
	],

	viewModel: {
		type: 'partroutingform'
	},
	bodyPadding: 10,
	bodyStyle: 'background:none',
	title: 'Part Routing',
	defaultListenerScope: true,

	dockedItems: [
		{
			xtype: 'toolbar',
			dock: 'top',
			itemId: 'partRoutingFormToolbar'
		}
	],
	items: [
		{
			xtype: 'combobox',
			itemId: 'workcenter',
			fieldLabel: 'Work Center',
			displayField: 'workcenterName',
			forceSelection: true,
			queryMode: 'local',
			valueField: 'workcenterId',
			bind: {
				store: '{WorkcenterStore}'
			}
		},
		{
			xtype: 'textfield',
			itemId: 'partsPerMinute',
			fieldLabel: 'Parts Per Minute'
		},
		{
			xtype: 'textfield',
			itemId: 'energy',
			fieldLabel: 'Energy'
		}
	],
	listeners: {
		afterrender: 'onPanelAfterRender',
		docformstatechanged: 'onPanelDocformStateChangeD'
	},

	onPanelAfterRender: function(component, eOpts) {
		this.docFormInit({
			toolbarId:'partRoutingFormToolbar',
			addFn: 'createRouting',
			saveFn: 'updateRouting',
			deleteFn: 'deleteRouting'
		});

		AERP.Ajax.request({
			url:'/Workcenter/readWorkcenters',
			success:function(reply) {
				this.getViewModel().getStore('WorkcenterStore').loadData(reply.data);
			},
			scope:this,
			mask:this
		});
	},

	onPanelDocformStateChangeD: function(panel) {
		let fields = ['partsPerMinute', 'energy'];

		for(let i in fields) {
			let field = this.queryById(fields[i]);

			field.addCls('docFormReadOnly');
			field.setReadOnly(true);
		}
	},

	readRouting: function(routingId) {
		AERP.Ajax.request({
			url:'/ItemMaster/readRouting',
			jsonData:{routingId:routingId},
			success:function(reply) {
				this.routingId = routingId;
				this.docFormLoadFormData(reply);
			},
			scope:this,
			mask:this
		});
	},

	createRouting: function() {
		let jsonData = this.docFormGetAllFieldValues();
		jsonData.partId = this.partId;

		AERP.Ajax.request({
			url:'/ItemMaster/createRouting',
			jsonData:jsonData,
			success:function(reply) {
				this.fireEvent('routingchanged');
				this.readRouting(reply.data);
			},
			scope:this,
			mask:this
		});
	},

	updateRouting: function() {
		let jsonData = this.docFormGetAllFieldValues();
		jsonData.routingId = this.routingId;
		jsonData.partId = this.partId;

		AERP.Ajax.request({
			url:'/ItemMaster/updateRouting',
			jsonData:jsonData,
			success:function(reply) {
				this.fireEvent('routingchanged');
				this.readRouting(this.routingId);
			},
			scope:this,
			mask:this
		});
	},

	deleteRouting: function() {
		AERP.Ajax.request({
			url:'/ItemMaster/deleteRouting',
			jsonData:{routingId:this.routingId},
			success:function(reply) {
				this.fireEvent('routingchanged');
				this.docFormReset();
			},
			scope:this,
			mask:this
		});
	}

});