/*
 * File: app/view/Reptiles.js
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

Ext.define('PetMaster.view.Reptiles', {
	extend: 'Ext.panel.Panel',
	alias: 'widget.reptiles',

	requires: [
		'PetMaster.view.ReptilesViewModel',
		'PetMaster.view.PetMaster',
		'Ext.grid.Panel',
		'Ext.grid.column.Date',
		'Ext.view.Table'
	],

	viewModel: {
		type: 'reptiles'
	},
	scrollable: true,
	bodyStyle: 'background:none',
	title: 'Reptile Database',
	defaultListenerScope: true,

	layout: {
		type: 'hbox',
		align: 'stretch'
	},
	items: [
		{
			xtype: 'gridpanel',
			resizable: true,
			width: 520,
			collapseDirection: 'left',
			collapsible: true,
			bind: {
				store: '{ReptileStore}'
			},
			columns: [
				{
					xtype: 'gridcolumn',
					dataIndex: 'serial',
					text: 'Serial'
				},
				{
					xtype: 'gridcolumn',
					width: 134,
					dataIndex: 'type',
					text: 'Type'
				},
				{
					xtype: 'gridcolumn',
					renderer: function(value, metaData, record, rowIndex, colIndex, store, view) {
						if(value === "Male") {
							return "<img src='/inc/img/silk_icons/male.png'>";
						} else {
							return "<img src='/inc/img/silk_icons/female.png'>";
						}
					},
					width: 59,
					dataIndex: 'sex',
					text: 'Sex'
				},
				{
					xtype: 'datecolumn',
					width: 119,
					dataIndex: 'receiveDate',
					text: 'Receive Date'
				},
				{
					xtype: 'datecolumn',
					dataIndex: 'sellDate',
					text: 'Sell Date'
				}
			],
			viewConfig: {
				enableTextSelection: true
			},
			listeners: {
				selectionchange: 'onGridpanelSelectionChange'
			}
		},
		{
			xtype: 'petmaster',
			flex: 1,
			itemId: 'reptileForm',
			listeners: {
				reptilechanged: 'onPanelReptileChangeD'
			}
		}
	],
	listeners: {
		afterrender: 'onPanelAfterRender'
	},

	onGridpanelSelectionChange: function(model, selected, eOpts) {
		if(!selected || selected.length !== 1) {
			return;
		}

		selected = selected[0];

		this.queryById('reptileForm').readPet(selected.data.reptileId);
	},

	onPanelReptileChangeD: function(panel) {
		this.readReptiles();
	},

	onPanelAfterRender: function(component, eOpts) {
		this.readReptiles();
	},

	readReptiles: function() {
		AERP.Ajax.request({
			url:'/PetMaster/readReptiles',
			success:function(reply) {
				this.getViewModel().getStore('ReptileStore').loadData(reply.data);
			},
			scope:this,
			mask:this
		});
	}

});