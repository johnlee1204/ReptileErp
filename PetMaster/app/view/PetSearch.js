/*
 * File: app/view/PetSearch.js
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

Ext.define('PetMaster.view.PetSearch', {
	extend: 'Ext.window.Window',
	alias: 'widget.petsearch',

	requires: [
		'PetMaster.view.PetSearchViewModel',
		'Ext.grid.Panel',
		'Ext.toolbar.Toolbar',
		'Ext.grid.column.Date',
		'Ext.view.Table'
	],

	viewModel: {
		type: 'petsearch'
	},
	height: 471,
	width: 737,
	closeAction: 'hide',
	title: 'Pet Search',
	defaultListenerScope: true,

	layout: {
		type: 'vbox',
		align: 'stretch'
	},
	items: [
		{
			xtype: 'gridpanel',
			flex: 1,
			itemId: 'petSearchGrid',
			bind: {
				store: '{PetSearchStore}'
			},
			dockedItems: [
				{
					xtype: 'toolbar',
					dock: 'top',
					itemId: 'petSearchToolbar'
				}
			],
			columns: [
				{
					xtype: 'gridcolumn',
					dataIndex: 'name',
					text: 'Name'
				},
				{
					xtype: 'gridcolumn',
					dataIndex: 'type',
					text: 'Type'
				},
				{
					xtype: 'datecolumn',
					dataIndex: 'receiveDate',
					text: 'Receive Date'
				},
				{
					xtype: 'datecolumn',
					dataIndex: 'sellDate',
					text: 'Sell Date'
				}
			]
		}
	],
	listeners: {
		afterrender: 'onWindowAfterRender'
	},

	onWindowAfterRender: function(component, eOpts) {
		Ext.create('NiceGridMenu', {
			menuItems:[{text:'Select Pet', action:'selectPet', icon:'/inc/img/silk_icons/accept.png', disabled:true}],
			callbackHandler:function(action, data) {
				switch(action) {
					case 'selectPet':
						this.fireEvent('petselected', data.petId);
						this.close();
						break;
				}
			},
			grid:this.queryById('petSearchGrid'),
			toolbar:this.queryById('petSearchToolbar'),
			filterField:true,
			scope:this
		});
	},

	searchPets: function(params) {
		AERP.Ajax.request({
			url:'/PetMaster/searchPets',
			jsonData:params,
			success:function(reply) {
				this.getViewModel().getStore('PetSearchStore').loadData(reply.data);
			},
			scope:this,
			mask:this
		});
	}

});