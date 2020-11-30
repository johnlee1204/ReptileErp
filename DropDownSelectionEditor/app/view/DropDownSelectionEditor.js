/*
 * File: app/view/DropDownSelectionEditor.js
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

Ext.define('DropDownSelectionEditor.view.DropDownSelectionEditor', {
	extend: 'Ext.panel.Panel',
	alias: 'widget.dropdownselectioneditor',

	requires: [
		'DropDownSelectionEditor.view.DropDownSelectionEditorViewModel',
		'DropDownSelectionEditor.view.DropDownSelectionEditorForm',
		'Ext.grid.Panel',
		'Ext.grid.column.Column',
		'Ext.view.Table'
	],

	viewModel: {
		type: 'dropdownselectioneditor'
	},
	minHeight: 500,
	minWidth: 500,
	width: 700,
	bodyStyle: 'background:none',
	defaultListenerScope: true,

	layout: {
		type: 'vbox',
		align: 'stretch'
	},
	items: [
		{
			xtype: 'gridpanel',
			flex: 1,
			itemId: 'DropDownSelectionEditorGrid',
			bind: {
				store: '{DropDownSelectionEditorStore}'
			},
			columns: [
				{
					xtype: 'gridcolumn',
					width: 55,
					dataIndex: 'displayOrder',
					text: 'Order'
				},
				{
					xtype: 'gridcolumn',
					width: 635,
					dataIndex: 'selection',
					text: 'Selection'
				}
			],
			viewConfig: {
				enableTextSelection: true
			},
			listeners: {
				selectionchange: 'onDropDownSelectionEditorGridSelectionChange'
			}
		},
		{
			xtype: 'dropdownselectioneditorform',
			itemId: 'selectionEditorForm',
			listeners: {
				selectionchanged: 'onPanelSelectionChangeD'
			}
		}
	],
	listeners: {
		afterrender: 'onPanelAfterRender'
	},

	onDropDownSelectionEditorGridSelectionChange: function(model, selected, eOpts) {
		if(!selected || selected.length !== 1) {
			return;
		}

		selected = selected[0];

		this.queryById('selectionEditorForm').readSelection(selected.data.dropDownSelectionId);
	},

	onPanelSelectionChangeD: function(panel) {
		this.readSelections(this.selectionKey);
		this.fireEvent('selectionchanged');
	},

	onPanelAfterRender: function(component, eOpts) {
		this.fireEvent("appdataloaded");
	},

	readSelections: function(selectionKey) {
		AERP.Ajax.request({
			url:'/DropDownSelectionEditor/readSelections',
			jsonData:{selectionKey:selectionKey},
			success:function(reply) {
				this.getViewModel().getStore('DropDownSelectionEditorStore').loadData(reply.data);
				this.selectionKey = selectionKey;
				this.queryById('selectionEditorForm').selectionKey = selectionKey;
			},
			scope:this,
			mask:this
		});
	}

});