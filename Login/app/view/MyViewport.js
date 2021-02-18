/*
 * File: app/view/MyViewport.js
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

Ext.define('Login.view.MyViewport', {
	extend: 'Ext.container.Viewport',
	alias: 'widget.myviewport',

	requires: [
		'Login.view.MyViewportViewModel'
	],

	viewModel: {
		type: 'myviewport'
	},
	defaultListenerScope: true,

	listeners: {
		afterrender: 'onViewportAfterRender',
		resize: 'onViewportResize'
	},

	onViewportAfterRender: function(component, eOpts) {
		var form = Ext.create('widget.loginform');

		this.loginWin = Ext.create('Ext.window.Window', {
			header: false,
			layout: 'fit',
			closable:false,
			resizable:false,
			bodyPadding:5,
			items:[form]
		});
		this.loginWin.show();
	},

	onViewportResize: function(component, width, height, oldWidth, oldHeight, eOpts) {
		this.loginWin.center();
	}

});