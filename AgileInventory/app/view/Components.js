/*
 * File: app/view/Components.js
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

Ext.define('AgileInventory.view.Components', {
	extend: 'Ext.panel.Panel',
	alias: 'widget.components',

	requires: [
		'AgileInventory.view.ComponentsViewModel',
		'AgileInventory.view.ComponentForm',
		'Ext.grid.Panel',
		'Ext.toolbar.Toolbar',
		'Ext.grid.column.Column',
		'Ext.view.Table'
	],

	viewModel: {
		type: 'components'
	},
	bodyStyle: 'background:none',
	title: 'Components',
	defaultListenerScope: true,

	layout: {
		type: 'vbox',
		align: 'stretch'
	},
	items: [
		{
			xtype: 'gridpanel',
			flex: 1,
			itemId: 'componentsGrid',
			bind: {
				store: '{ComponentStore}'
			},
			dockedItems: [
				{
					xtype: 'toolbar',
					dock: 'top',
					itemId: 'componentsToolbar'
				}
			],
			columns: [
				{
					xtype: 'gridcolumn',
					width: 156,
					dataIndex: 'productName',
					text: 'Part'
				},
				{
					xtype: 'gridcolumn',
					dataIndex: 'quantity',
					text: 'Quantity'
				}
			],
			viewConfig: {
				enableTextSelection: true
			},
			listeners: {
				selectionchange: 'onComponentsGridSelectionChange'
			}
		},
		{
			xtype: 'componentform',
			flex: 1,
			itemId: 'componentForm',
			listeners: {
				componentchanged: 'onPanelComponentChangeD'
			}
		}
	],
	listeners: {
		afterrender: 'onPanelAfterRender'
	},

	onComponentsGridSelectionChange: function(model, selected, eOpts) {
		if(!selected || selected.length !== 1) {
			return;
		}

		selected = selected[0];

		this.queryById('componentForm').readComponent(selected.data.componentId);
	},

	onPanelAfterRender: function(component, eOpts) {

	},

	onPanelComponentChangeD: function(panel) {
		this.readComponents(this.productId);
	},

	readComponents: function(productId) {
		AERP.Ajax.request({
			url:"/AgileInventory/readComponents",
			jsonData:{productId:productId},
			success:function(reply) {
				this.productId = productId;
				this.getViewModel().getStore('ComponentStore').loadData(reply.data);
				this.queryById('componentForm').parentProductId = productId;
			},
			scope:this,
			mask:this
		});
	}

});