/*
 * File: app/view/PetMasterViewModel.js
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

Ext.define('PetMaster.view.PetMasterViewModel', {
	extend: 'Ext.app.ViewModel',
	alias: 'viewmodel.petmaster',

	requires: [
		'Ext.data.Store',
		'Ext.data.field.Date'
	],

	stores: {
		PetTypeStore: {
			fields: [
				{
					name: 'type'
				}
			]
		},
		FoodTypeStore: {
			fields: [
				{
					name: 'type'
				}
			]
		},
		StatusStore: {
			fields: [
				{
					name: 'status'
				}
			]
		},
		HabitatStore: {
			fields: [
				{
					name: 'habitatId'
				},
				{
					name: 'habitat'
				}
			]
		},
		MorphStore: {
			fields: [
				{
					name: 'morphId'
				},
				{
					name: 'morphName'
				}
			]
		},
		SexStore: {
			data: [
				{
					sex: 'Male'
				},
				{
					sex: 'Female'
				}
			],
			fields: [
				{
					name: 'sex'
				}
			]
		},
		AttachmentStore: {
			fields: [
				{
					name: 'petAttachmentId'
				},
				{
					name: 'fileName'
				},
				{
					name: 'fileLocation'
				},
				{
					type: 'date',
					name: 'photoDate'
				}
			]
		},
		MaleParentStore: {
			fields: [
				{
					name: 'reptileId'
				},
				{
					name: 'serial'
				}
			]
		},
		FemaleParentStore: {
			fields: [
				{
					name: 'reptileId'
				},
				{
					name: 'serial'
				}
			]
		}
	}

});