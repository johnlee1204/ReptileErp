/*
 * File: app/view/PartBomForm.js
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

Ext.define('ItemMaster.view.PartBomForm', {
	extend: 'Ext.panel.Panel',
	alias: 'widget.partbomform',

	mixins: [
		'DocForm'
	],
	requires: [
		'ItemMaster.view.PartBomFormViewModel',
		'Ext.toolbar.Toolbar',
		'Ext.form.field.ComboBox'
	],

	viewModel: {
		type: 'partbomform'
	},
	bodyPadding: 10,
	bodyStyle: 'background:none',
	defaultListenerScope: true,

	dockedItems: [
		{
			xtype: 'toolbar',
			dock: 'top',
			itemId: 'partBomFormToolbar'
		}
	],
	items: [
		{
			xtype: 'combobox',
			itemId: 'parentPart',
			fieldLabel: 'Parent Part',
			displayField: 'partName',
			forceSelection: true,
			queryMode: 'local',
			valueField: 'partId',
			bind: {
				store: '{ParentPartStore}'
			}
		},
		{
			xtype: 'combobox',
			itemId: 'bomPart',
			width: 337,
			fieldLabel: 'Part',
			emptyText: 'BEGIN TYPING',
			hideTrigger: true,
			displayField: 'partName',
			forceSelection: true,
			minChars: 1,
			queryParam: 'partName',
			typeAhead: true,
			typeAheadDelay: 150,
			valueField: 'partId',
			bind: {
				store: '{PartStore}'
			}
		},
		{
			xtype: 'textfield',
			itemId: 'quantity',
			fieldLabel: 'Quantity'
		}
	],
	listeners: {
		afterrender: 'onPanelAfterRender',
		docformstatechanged: 'onPanelDocformStateChangeD',
		docformbeforenew: 'onPanelDocformBeforeNew'
	},

	onPanelAfterRender: function(component, eOpts) {
		this.docFormInit({
			toolbarId:'partBomFormToolbar',
			addFn: 'createBomRecord',
			saveFn: 'updateBomRecord',
			deleteFn: 'deleteBomRecord'
		});
	},

	onPanelDocformStateChangeD: function(panel) {
		let field = this.queryById('parentPart');

		field.addCls('docFormReadOnly');
		field.setReadOnly(true);
	},

	onPanelDocformBeforeNew: function(newData) {
		let partId = this.queryById('bomPart').getValue();

		if(this.bomId && partId) {
			newData.data.parentPart = partId;
			newData.comboData.parentPart = this.partStoreRecords;
		} else {
			newData.data.parentPart = this.queryById('parentPart').getValue();
		}

		if(!newData.data.parentPart) {
			Ext.Msg.alert("Warning", "Select a Part to add to BOM!");
			return false;
		}
	},

	setParentPart: function(parentPartId, parentPartName) {
		this.getViewModel().getStore('ParentPartStore').loadData([[parentPartId, parentPartName]]);
		this.docFormLoadFormData({data:{parentPart:parentPartId}});
	},

	readBomRecord: function(bomId) {
		AERP.Ajax.request({
			url:'/ItemMaster/readPartBomRecord',
			jsonData:{bomId: bomId},
			success:function(reply) {
				this.bomId = bomId;
				this.getViewModel().getStore('ParentPartStore').loadData([[reply.data.parentPart, reply.data.parentPartName]]);
				this.partStoreRecords = [[reply.data.bomPart, reply.data.bomPartName]];
				this.getViewModel().getStore('PartStore').loadData(this.partStoreRecords);

				this.docFormLoadFormData(reply);
			},
			scope:this,
			mask:this
		});
	},

	createBomRecord: function() {
		let jsonData = this.docFormGetAllFieldValues();

		if(!jsonData.parentPart) {
			Ext.Msg.alert("Warning", "Please Select a Parent Part!");
		}
		AERP.Ajax.request({
			url:'/ItemMaster/createBomRecord',
			jsonData:jsonData,
			success:function(reply) {
				this.fireEvent('bomchanged');
				this.readBomRecord(reply.data);
			},
			scope:this,
			mask:this
		});
	},

	updateBomRecord: function() {
		let jsonData = this.docFormGetAllFieldValues();
		jsonData.bomId = this.bomId;

		AERP.Ajax.request({
			url:'/ItemMaster/updateBomRecord',
			jsonData:jsonData,
			success:function(reply) {
				this.fireEvent('bomchanged');
				this.readBomRecord(this.bomId);
			},
			scope:this,
			mask:this
		});
	},

	deleteBomRecord: function() {
		AERP.Ajax.request({
			url:'/ItemMaster/deleteBomRecord',
			jsonData:{bomId: this.bomId},
			success:function(reply) {
				this.fireEvent('bomchanged');
				this.docFormReset();
			},
			scope:this,
			mask:this
		});
	}

});