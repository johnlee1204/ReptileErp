/*
 * File: app/store/ScheduleCalandarStore.js
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

Ext.define('Schedule.store.ScheduleCalandarStore', {
	extend: 'Ext.calendar.store.Calendars',

	requires: [
		'Schedule.model.ScheduleCalendarModel',
		'Ext.data.proxy.Ajax'
	],

	constructor: function(cfg) {
		var me = this;
		cfg = cfg || {};
		me.callParent([Ext.apply({
			storeId: 'ScheduleCalandarStore',
			autoLoad: true,
			model: 'Schedule.model.ScheduleCalendarModel',
			proxy: {
				type: 'ajax',
				url: '/Schedule/resources/calandars.json'
			}
		}, cfg)]);
	}
});