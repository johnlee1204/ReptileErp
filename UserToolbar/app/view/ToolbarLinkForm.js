/*
 * File: app/view/ToolbarLinkForm.js
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

Ext.define('UserToolbar.view.ToolbarLinkForm', {
	extend: 'Ext.panel.Panel',
	alias: 'widget.toolbarlinkform',

	mixins: [
		'DocForm'
	],
	requires: [
		'UserToolbar.view.ToolbarLinkFormViewModel',
		'Ext.toolbar.Toolbar',
		'Ext.form.field.ComboBox'
	],

	viewModel: {
		type: 'toolbarlinkform'
	},
	bodyPadding: 10,
	bodyStyle: 'background:none',
	defaultListenerScope: true,

	dockedItems: [
		{
			xtype: 'toolbar',
			dock: 'top',
			itemId: 'toolbarLinkToolbar'
		}
	],
	items: [
		{
			xtype: 'textfield',
			itemId: 'linkName',
			fieldLabel: 'Link Name'
		},
		{
			xtype: 'textfield',
			itemId: 'linkPath',
			fieldLabel: 'Link Path'
		},
		{
			xtype: 'textfield',
			itemId: 'iconPath',
			fieldLabel: 'Icon Path',
			listeners: {
				afterrender: 'onIconPathAfterRender'
			}
		},
		{
			xtype: 'combobox',
			itemId: 'linkCategory',
			fieldLabel: 'Link Category',
			displayField: 'category',
			queryMode: 'local',
			valueField: 'category',
			bind: {
				store: '{CategoryStore}'
			}
		}
	],
	listeners: {
		afterrender: 'onPanelAfterRender'
	},

	onIconPathAfterRender: function(component, eOpts) {
		component.getEl().on('dblclick', function() {
			component.setValue("/inc/img/silk_icons/.png");
		});
	},

	onPanelAfterRender: function(component, eOpts) {
		AERP.Ajax.request({
			url:'/UserToolbar/readCategories',
			success:function(reply) {
				this.getViewModel().getStore('CategoryStore').loadData(reply.data);
			},
			scope:this,
			mask:this
		});

		this.docFormInit({
			toolbarId:'toolbarLinkToolbar',
			addFn:'createToolbarLink',
			saveFn:'updateToolbarLink',
			deleteFn:'deleteToolbarLink'
		});
	},

	readToolbarLink: function(toolbarLinkId) {
		AERP.Ajax.request({
			url:'/UserToolbar/readToolbarLink',
			jsonData:{toolbarLinkId:toolbarLinkId},
			success:function(reply) {
				this.toolbarLinkId = toolbarLinkId;
				this.docFormLoadFormData(reply);
			},
			scope:this,
			mask:this
		});
	},

	createToolbarLink: function() {
		AERP.Ajax.request({
			url:'/UserToolbar/createToolbarLink',
			jsonData:this.docFormGetAllFieldValues(),
			success:function(reply) {
				this.readToolbarLink(reply.data);
				this.fireEvent('toolbarlinkchange');
			},
			scope:this,
			mask:this
		});
	},

	updateToolbarLink: function() {
		let jsonData = this.docFormGetAllFieldValues();
		jsonData.toolbarLinkId = this.toolbarLinkId;

		AERP.Ajax.request({
			url:'/UserToolbar/updateToolbarLink',
			jsonData:jsonData,
			success:function(reply) {
				this.readToolbarLink(this.toolbarLinkId);
				this.fireEvent('toolbarlinkchange');
			},
			scope:this,
			mask:this
		});
	},

	deleteToolbarLink: function() {
		AERP.Ajax.request({
			url:'/UserToolbar/deleteToolbarLink',
			jsonData:{toolbarLinkId:this.toolbarLinkId},
			success:function(reply) {
				this.toolbarLinkId = null;
				this.docFormReset();
				this.fireEvent('toolbarlinkchange');
			},
			scope:this,
			mask:this
		});
	}

});