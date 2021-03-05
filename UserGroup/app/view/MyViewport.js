/*
 * File: app/view/MyViewport.js
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

Ext.define('GroupManager.view.MyViewport', {
	extend: 'Ext.container.Viewport',
	alias: 'widget.myviewport',

	requires: [
		'GroupManager.view.MyViewportViewModel',
		'GroupManager.view.GroupManagerPanel',
		'Ext.toolbar.Toolbar',
		'Ext.panel.Panel'
	],

	viewModel: {
		type: 'myviewport'
	},
	height: 250,
	width: 400,

	layout: {
		type: 'vbox',
		align: 'stretch'
	},
	items: [
		{
			xtype: 'usertoolbar'
		},
		{
			xtype: 'container',
			flex: 1,
			padding: 20,
			items: [
				{
					xtype: 'groupmanagerpanel'
				}
			]
		}
	]

});