/*
 * File: app/view/EmployeeViewModel.js
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

Ext.define('Employee.view.EmployeeViewModel', {
	extend: 'Ext.app.ViewModel',
	alias: 'viewmodel.employee',

	requires: [
		'Ext.data.Store',
		'Ext.data.field.Field'
	],

	stores: {
		EmployeeStore: {
			fields: [
				{
					name: 'employeeId'
				},
				{
					name: 'employeeNumber'
				},
				{
					name: 'username'
				},
				{
					name: 'firstName'
				},
				{
					name: 'lastName'
				},
				{
					name: 'email'
				},
				{
					name: 'hireDate'
				},
				{
					name: 'terminationDate'
				},
				{
					name: 'payRate'
				}
			]
		}
	}

});