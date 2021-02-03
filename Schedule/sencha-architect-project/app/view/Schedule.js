/*
 * File: app/view/Schedule.js
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

Ext.define('Schedule.view.Schedule', {
	extend: 'Ext.panel.Panel',
	alias: 'widget.schedule',

	requires: [
		'Schedule.view.ScheduleViewModel',
		'Ext.calendar.panel.Panel'
	],

	viewModel: {
		type: 'schedule'
	},
	frame: true,
	minHeight: 500,
	minWidth: 500,
	title: 'Schedule',

	layout: {
		type: 'vbox',
		align: 'stretch'
	},
	items: [
		{
			xtype: 'calendar',
			flex: 1
		}
	]

});