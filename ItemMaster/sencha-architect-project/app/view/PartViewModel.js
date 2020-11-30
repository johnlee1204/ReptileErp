/*
 * File: app/view/PartViewModel.js
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

Ext.define('ItemMaster.view.PartViewModel', {
	extend: 'Ext.app.ViewModel',
	alias: 'viewmodel.part',

	requires: [
		'Ext.data.Store',
		'Ext.data.field.Field'
	],

	stores: {
		SourceStore: {
			data: [
				{
					source: 'Make'
				},
				{
					source: 'Stock'
				},
				{
					source: 'Phantom'
				},
				{
					source: 'Buy'
				}
			],
			fields: [
				{
					name: 'source'
				}
			]
		}
	}

});