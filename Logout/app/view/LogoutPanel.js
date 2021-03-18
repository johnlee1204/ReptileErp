/*
 * File: app/view/LogoutPanel.js
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

Ext.define('Logout.view.LogoutPanel', {
	extend: 'Ext.form.Panel',
	alias: 'widget.logoutpanel',

	requires: [
		'Logout.view.LogoutPanelViewModel',
		'Ext.container.Container',
		'Ext.button.Button'
	],

	viewModel: {
		type: 'logoutpanel'
	},
	border: false,
	margin: 5,
	width: 250,
	bodyPadding: 10,
	bodyStyle: {
		background: 'none'
	},
	title: '',
	defaultListenerScope: true,

	items: [
		{
			xtype: 'container',
			cls: 'logo',
			height: 75,
			itemId: 'logo'
		},
		{
			xtype: 'container',
			html: 'Successfully Logged Out',
			itemId: 'pleaseLogin',
			style: {
				'font-family': 'Arial',
				'font-size': '18px',
				'text-decoration': 'italic',
				'font-weight': 'bolder',
				'margin-bottom': '10px',
				'text-align': 'center'
			}
		},
		{
			xtype: 'container',
			layout: {
				type: 'hbox',
				align: 'stretch'
			},
			items: [
				{
					xtype: 'container',
					flex: 1
				},
				{
					xtype: 'button',
					padding: '10 30 10 30',
					icon: '/inc/img/silk_icons/key.png',
					text: 'Login',
					listeners: {
						click: 'onButtonClick'
					}
				},
				{
					xtype: 'container',
					flex: 1
				}
			]
		}
	],

	onButtonClick: function(button, e, eOpts) {
		var redirectStr = '';
		var searchQuery = Ext.Object.fromQueryString(window.location.search);
		if(searchQuery.hasOwnProperty('redirect')){
			 redirectStr = '?redirect='+searchQuery.redirect;
		}
		window.location = '/Login'+redirectStr;
	}

});