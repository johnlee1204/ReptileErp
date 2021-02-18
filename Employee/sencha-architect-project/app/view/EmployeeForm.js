/*
 * File: app/view/EmployeeForm.js
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

Ext.define('Employee.view.EmployeeForm', {
	extend: 'Ext.panel.Panel',
	alias: 'widget.employeeform',

	mixins: [
		'DocForm'
	],
	requires: [
		'Employee.view.EmployeeFormViewModel',
		'Ext.toolbar.Toolbar',
		'Ext.form.field.Date'
	],

	viewModel: {
		type: 'employeeform'
	},
	layout: 'vbox',
	bodyPadding: 10,
	bodyStyle: 'background:none',
	defaultListenerScope: true,

	dockedItems: [
		{
			xtype: 'toolbar',
			flex: 1,
			dock: 'top',
			itemId: 'employeeFormToolbar'
		}
	],
	items: [
		{
			xtype: 'textfield',
			itemId: 'employeeNumber',
			fieldLabel: 'Employee No'
		},
		{
			xtype: 'textfield',
			itemId: 'userName',
			fieldLabel: 'User Name'
		},
		{
			xtype: 'textfield',
			itemId: 'password',
			fieldLabel: 'Password'
		},
		{
			xtype: 'textfield',
			itemId: 'firstName',
			fieldLabel: 'First Name'
		},
		{
			xtype: 'textfield',
			itemId: 'lastName',
			fieldLabel: 'Last Name'
		},
		{
			xtype: 'textfield',
			itemId: 'email',
			fieldLabel: 'Email'
		},
		{
			xtype: 'datefield',
			itemId: 'hireDate',
			fieldLabel: 'Hire Date',
			submitFormat: 'Y-m-d'
		},
		{
			xtype: 'datefield',
			itemId: 'terminationDate',
			fieldLabel: 'Termination Date',
			submitFormat: 'Y-m-d'
		},
		{
			xtype: 'textfield',
			itemId: 'payRate',
			fieldLabel: 'Pay Rate'
		}
	],
	listeners: {
		afterrender: 'onPanelAfterRender'
	},

	onPanelAfterRender: function(component, eOpts) {
		this.docFormInit({
			toolbarId:'employeeFormToolbar',
			addFn:'createEmployee',
			saveFn:'updateEmployee',
			deleteFn:'deleteEmployee'
		});
	},

	readEmployee: function(employeeId) {
		AERP.Ajax.request({
			url:'/Employee/readEmployee',
			jsonData:{employeeId:employeeId},
			success:function(reply) {
				this.employeeId = employeeId;
				this.docFormLoadFormData(reply);
			},
			scope:this,
			mask:this
		});
	},

	createEmployee: function() {
		AERP.Ajax.request({
			url:'/Employee/createEmployee',
			jsonData:this.docFormGetAllFieldValues(),
			success:function(reply) {
				this.readEmployee(reply.data);
				this.fireEvent('employeechanged');
			},
			scope:this,
			mask:this
		});
	},

	updateEmployee: function() {
		let jsonData = this.docFormGetAllFieldValues();
		jsonData.employeeId = this.employeeId;

		AERP.Ajax.request({
			url:'/Employee/updateEmployee',
			jsonData:jsonData,
			success:function(reply) {
				this.readEmployee(this.employeeId);
				this.fireEvent('employeechanged');
			},
			scope:this,
			mask:this
		});
	},

	deleteEmployee: function() {
		AERP.Ajax.request({
			url:'/Employee/deleteEmployee',
			jsonData:{employeeId:this.employeeId},
			success:function(reply) {
				this.employeeId = null;
				this.docFormReset();
				this.fireEvent('employeechanged');
			},
			scope:this,
			mask:this
		});
	}

});