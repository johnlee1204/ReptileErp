/*
 * File: app/view/Products.js
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

Ext.define('AgileInventory.view.Products', {
	extend: 'Ext.panel.Panel',
	alias: 'widget.products',

	requires: [
		'AgileInventory.view.ProductsViewModel',
		'AgileInventory.view.ProductForm',
		'Ext.grid.Panel',
		'Ext.toolbar.Toolbar',
		'Ext.button.Button',
		'Ext.grid.column.Column',
		'Ext.view.Table'
	],

	viewModel: {
		type: 'products'
	},
	bodyStyle: 'background:none',
	title: 'Products',
	defaultListenerScope: true,

	layout: {
		type: 'hbox',
		align: 'stretch'
	},
	items: [
		{
			xtype: 'gridpanel',
			flex: 1,
			bind: {
				store: '{ProductStore}'
			},
			dockedItems: [
				{
					xtype: 'toolbar',
					dock: 'top',
					items: [
						{
							xtype: 'button',
							icon: '/inc/img/silk_icons/arrow_refresh.png',
							text: 'Refresh',
							listeners: {
								click: 'onButtonClick'
							}
						}
					]
				}
			],
			columns: [
				{
					xtype: 'gridcolumn',
					width: 197,
					dataIndex: 'productName',
					text: 'Product'
				},
				{
					xtype: 'gridcolumn',
					width: 234,
					dataIndex: 'productDescription',
					text: 'Description'
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
			xtype: 'productform',
			itemId: 'productForm',
			flex: 1,
			listeners: {
				productchanged: 'onPanelProductChangeD'
			}
		}
	],
	listeners: {
		afterrender: 'onPanelAfterRender'
	},

	onButtonClick: function(button, e, eOpts) {
		this.readProducts();
	},

	onGridpanelSelectionChange: function(model, selected, eOpts) {
		if(!selected || selected.length !== 1) {
			return;
		}

		selected = selected[0];

		this.queryById("productForm").readProduct(selected.data.productId);
	},

	onPanelProductChangeD: function(panel) {
		this.readProducts();
	},

	onPanelAfterRender: function(component, eOpts) {
		this.readProducts();
	},

	readProducts: function() {
		AERP.Ajax.request({
			url:"/AgileInventory/readProducts",
			success:function(reply) {
				this.getViewModel().getStore("ProductStore").loadData(reply.data);
			},
			scope:this,
			mask:this
		});
	}

});