/*
 * File: app/view/Habitat.js
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

Ext.define('Habitat.view.Habitat', {
	extend: 'Ext.panel.Panel',
	alias: 'widget.habitat',

	requires: [
		'Habitat.view.HabitatViewModel',
		'Habitat.view.HabitatForm',
		'Ext.grid.Panel',
		'Ext.grid.column.Column',
		'Ext.view.Table'
	],

	viewModel: {
		type: 'habitat'
	},
	frame: true,
	minHeight: 500,
	minWidth: 500,
	title: 'Habitat',
	defaultListenerScope: true,

	layout: {
		type: 'hbox',
		align: 'stretch'
	},
	items: [
		{
			xtype: 'gridpanel',
			width: 260,
			bind: {
				store: '{HabitatStore}'
			},
			columns: [
				{
					xtype: 'gridcolumn',
					width: 248,
					dataIndex: 'habitatName',
					text: 'Habitat'
				}
			],
			listeners: {
				selectionchange: 'onGridpanelSelectionChange'
			}
		},
		{
			xtype: 'habitatform',
			flex: 1,
			itemId: 'habitatForm',
			listeners: {
				habitatchanged: 'onPanelHabitatChangeD'
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

		this.queryById('habitatForm').readHabitat(selected.data.habitatId);
	},

	onPanelAfterRender: function(component, eOpts) {
		this.readHabitats();
	},

	onPanelHabitatChangeD: function(panel) {
		this.readHabitats();
	},

	readHabitats: function() {
		AERP.Ajax.request({
			url:'/Habitat/readHabitats',
			success:function(reply) {
				this.getViewModel().getStore('HabitatStore').loadData(reply.data);
			},
			scope:this,
			mask:this
		});
	}

});