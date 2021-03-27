/*
 * File: app/view/Locations.js
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

Ext.define('AgileInventory.view.Locations', {
	extend: 'Ext.panel.Panel',
	alias: 'widget.locations',

	requires: [
		'AgileInventory.view.LocationsViewModel',
		'AgileInventory.view.LocationForm',
		'Ext.grid.Panel',
		'Ext.grid.column.Column',
		'Ext.view.Table'
	],

	viewModel: {
		type: 'locations'
	},
	bodyStyle: 'background:none',
	title: 'Locations',
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
				store: '{LocationStore}'
			},
			columns: [
				{
					xtype: 'gridcolumn',
					width: 139,
					dataIndex: 'facility',
					text: 'Facility'
				},
				{
					xtype: 'gridcolumn',
					width: 127,
					dataIndex: 'locationName',
					text: 'Location'
				},
				{
					xtype: 'gridcolumn',
					width: 117,
					dataIndex: 'locationDescription',
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
			xtype: 'locationform',
			itemId: 'locationForm',
			listeners: {
				locationchanged: 'onPanelLocationChangeD'
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

		this.queryById('locationForm').readLocation(selected.data.locationId);
	},

	onPanelLocationChangeD: function(panel) {
		this.readLocations();
	},

	onPanelAfterRender: function(component, eOpts) {
		this.readLocations();
	},

	readLocations: function() {
		AERP.Ajax.request({
			url:'/AgileInventory/readLocations',
			success:function(reply) {
				this.getViewModel().getStore('LocationStore').loadData(reply.data);
			},
			scope:this,
			mask:this
		});
	}

});