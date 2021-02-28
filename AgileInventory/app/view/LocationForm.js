/*
 * File: app/view/LocationForm.js
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

Ext.define('AgileInventory.view.LocationForm', {
	extend: 'Ext.panel.Panel',
	alias: 'widget.locationform',

	mixins: [
		'DocForm'
	],
	requires: [
		'AgileInventory.view.LocationFormViewModel',
		'Ext.toolbar.Toolbar',
		'Ext.form.field.Text'
	],

	viewModel: {
		type: 'locationform'
	},
	flex: 1,
	bodyPadding: 10,
	bodyStyle: 'background:none',
	defaultListenerScope: true,

	dockedItems: [
		{
			xtype: 'toolbar',
			dock: 'top',
			itemId: 'locationFormToolbar'
		}
	],
	items: [
		{
			xtype: 'textfield',
			itemId: 'locationName',
			fieldLabel: 'Name'
		},
		{
			xtype: 'textfield',
			itemId: 'locationDescription',
			fieldLabel: 'Description'
		}
	],
	listeners: {
		afterrender: 'onPanelAfterRender'
	},

	onPanelAfterRender: function(component, eOpts) {
		this.docFormInit({
			toolbarId:"locationFormToolbar",
			addFn:"createLocation",
			saveFn:"updateLocation",
			deleteFn:"deleteLocation"
		});
	},

	readLocation: function(locationId) {
		AERP.Ajax.request({
			url:"/AgileInventory/readLocation",
			jsonData:{locationId:locationId},
			success:function(reply) {
				this.locationId = locationId;
				this.docFormLoadFormData(reply);
			},
			scope:this,
			mask:this
		});
	},

	createLocation: function() {
		AERP.Ajax.request({
			url:"/AgileInventory/createLocation",
			jsonData:this.docFormGetAllFieldValues(),
			success:function(reply) {
				this.readLocation(reply.data);
				this.fireEvent('locationchanged');
			},
			scope:this,
			mask:this
		});
	},

	updateLocation: function() {
		let jsonData = this.docFormGetAllFieldValues();
		jsonData.locationId = this.locationId;

		AERP.Ajax.request({
			url:"/AgileInventory/updateLocation",
			jsonData:jsonData,
			success:function(reply) {
				this.readLocation(this.locationId);
				this.fireEvent('locationchanged');
			},
			scope:this,
			mask:this
		});
	},

	deleteLocation: function() {
		AERP.Ajax.request({
			url:"/AgileInventory/deleteLocation",
			jsonData:{locationId:this.locationId},
			success:function(reply) {
				this.locationId = null;
				this.docFormReset();
				this.fireEvent('locationchanged');
			},
			scope:this,
			mask:this
		});
	}

});