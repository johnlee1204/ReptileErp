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
		'Ext.tab.Panel',
		'Ext.tab.Tab',
		'Ext.grid.Panel',
		'Ext.grid.column.Date',
		'Ext.view.Table',
		'Ext.form.field.ComboBox'
	],

	viewModel: {
		type: 'schedule'
	},
	frame: true,
	minHeight: 500,
	minWidth: 500,
	title: 'Schedule',
	defaultListenerScope: true,

	layout: {
		type: 'vbox',
		align: 'stretch'
	},
	items: [
		{
			xtype: 'tabpanel',
			flex: 1,
			bodyStyle: 'background:none',
			activeTab: 0,
			items: [
				{
					xtype: 'panel',
					bodyStyle: 'background:none',
					title: 'Schedule',
					layout: {
						type: 'vbox',
						align: 'stretch'
					},
					items: [
						{
							xtype: 'gridpanel',
							flex: 1,
							itemId: 'employeeScheduleGrid',
							bind: {
								store: '{EmployeeScheduleStore}'
							},
							columns: [
								{
									xtype: 'gridcolumn',
									dataIndex: 'employeeNumber',
									text: 'Employee No'
								},
								{
									xtype: 'gridcolumn',
									dataIndex: 'firstName',
									text: 'First Name'
								},
								{
									xtype: 'gridcolumn',
									dataIndex: 'lastName',
									text: 'Last Name'
								},
								{
									xtype: 'datecolumn',
									dataIndex: 'currentClockIn',
									text: 'Current Clock In',
									format: 'h:ia'
								}
							],
							listeners: {
								selectionchange: 'onEmployeeScheduleGridSelectionChange'
							}
						},
						{
							xtype: 'gridpanel',
							flex: 1,
							title: 'Labor History',
							bind: {
								store: '{LaborHistoryStore}'
							},
							columns: [
								{
									xtype: 'datecolumn',
									dataIndex: 'startTime',
									width: 160,
									text: 'Start Time',
									format: 'F j, Y g:i a'
								},
								{
									xtype: 'datecolumn',
									width: 160,
									dataIndex: 'endTime',
									text: 'End Time',
									format: 'F j, Y g:i a'
								},
								{
									xtype: 'gridcolumn',
									dataIndex: 'hoursWorked',
									text: 'Hours Worked'
								}
							]
						}
					]
				},
				{
					xtype: 'panel',
					layout: 'vbox',
					bodyPadding: 10,
					bodyStyle: 'background:none',
					title: 'Clock In',
					items: [
						{
							xtype: 'combobox',
							itemId: 'employee',
							fieldLabel: 'Employee',
							displayField: 'name',
							forceSelection: true,
							queryMode: 'local',
							valueField: 'employeeId',
							bind: {
								store: '{EmployeeStore}'
							},
							listeners: {
								select: 'onEmployeeSelect'
							}
						},
						{
							xtype: 'container',
							height: 20,
							itemId: 'clockOnDetails'
						},
						{
							xtype: 'container',
							margin: '30 0 0 0',
							layout: {
								type: 'hbox',
								align: 'stretch'
							},
							items: [
								{
									xtype: 'button',
									flex: 1,
									height: 44,
									width: 90,
									text: 'Clock On',
									listeners: {
										click: 'onButtonClick'
									}
								},
								{
									xtype: 'button',
									flex: 1,
									margin: '0 0 0 25',
									width: 90,
									text: 'Clock Off',
									listeners: {
										click: 'onButtonClick1'
									}
								}
							]
						}
					]
				}
			]
		}
	],
	listeners: {
		afterrender: 'onPanelAfterRender'
	},

	onEmployeeScheduleGridSelectionChange: function(model, selected, eOpts) {
		if(!selected || selected.length !== 1) {
			return;
		}

		selected = selected[0];

		this.readEmployeeLaborHistory(selected.data.employeeId);
	},

	onEmployeeSelect: function(combo, record, eOpts) {
		this.readClockInDetails();
	},

	onButtonClick: function(button, e, eOpts) {
		this.clockOn();
	},

	onButtonClick1: function(button, e, eOpts) {
		this.clockOff();
	},

	onPanelAfterRender: function(component, eOpts) {
		AERP.Ajax.request({
			url:'/Schedule/readAppInitData',
			success:function(reply) {
				this.getViewModel().getStore('EmployeeStore').loadData(reply.employees);
			},
			scope:this,
			mask:this
		});

		this.readEmployeeSchedule();
	},

	readEmployeeSchedule: function() {
		AERP.Ajax.request({
			url:'/Schedule/readEmployeeSchedule',
			success:function(reply) {
				this.getViewModel().getStore('EmployeeScheduleStore').loadData(reply.data);
			},
			scope:this,
			mask:this
		});
	},

	readEmployeeLaborHistory: function(employeeId) {
		AERP.Ajax.request({
			url:'/Schedule/readEmployeeLaborHistory',
			jsonData:{employeeId:employeeId},
			success:function(reply) {
				this.getViewModel().getStore('LaborHistoryStore').loadData(reply.data);
			},
			scope:this,
			mask:this
		});
	},

	readClockInDetails: function() {
		let employee = this.queryById('employee').getValue();

		if(!employee) {
			Ext.Msg.alert("Error", "Select an Employee!");
			return;
		}

		AERP.Ajax.request({
			url:'/Schedule/readClockOnDetails',
			jsonData:{employeeId:employee},
			success:function(reply) {
				this.queryById('clockOnDetails').setHtml(reply.data);
			},
			scope:this,
			mask:this
		});
	},

	clockOn: function() {
		let employee = this.queryById('employee').getValue();

		if(!employee) {
			Ext.Msg.alert("Error", "Select an Employee!");
			return;
		}

		AERP.Ajax.request({
			url:'/Schedule/clockOn',
			jsonData:{employeeId:employee},
			success:function(reply) {
				this.readClockInDetails();
				Ext.Msg.alert("Success", "You are now Clocked On!");
			},
			scope:this,
			mask:this
		});
	},

	clockOff: function() {
		let employee = this.queryById('employee').getValue();

		if(!employee) {
			Ext.Msg.alert("Error", "Select an Employee!");
			return;
		}

		AERP.Ajax.request({
			url:'/Schedule/clockOff',
			jsonData:{employeeId:employee},
			success:function(reply) {
				this.readClockInDetails();
				Ext.Msg.alert("Success", "You are now Clocked Off!");
			},
			scope:this,
			mask:this
		});
	}

});