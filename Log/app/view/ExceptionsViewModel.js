/*
 * File: app/view/ExceptionsViewModel.js
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

Ext.define('Log.view.ExceptionsViewModel', {
    extend: 'Ext.app.ViewModel',
    alias: 'viewmodel.exceptions',

    requires: [
        'Ext.data.Store',
        'Ext.data.proxy.Ajax',
        'Ext.data.reader.Array',
        'Ext.util.Sorter',
        'Ext.data.field.Date'
    ],

    stores: {
        ExceptionComboStore: {
            proxy: {
                type: 'ajax',
                noCache: false,
                reader: {
                    type: 'array'
                }
            },
            fields: [
                {
                    name: 'appName'
                },
                {
                    name: 'appValue'
                }
            ]
        },
        ExceptionGridStore: {
            pageSize: 100,
            remoteFilter: true,
            remoteSort: true,
            sorters: {
                direction: 'DESC',
                property: 'date'
            },
            proxy: {
                type: 'ajax',
                noCache: false,
                simpleSortMode: true,
                url: 'readExceptionLogs',
                actionMethods: {
                    create: 'POST',
                    read: 'POST',
                    update: 'POST',
                    destroy: 'POST'
                },
                reader: {
                    type: 'array',
                    rootProperty: 'data',
                    totalProperty: 'totalRows'
                }
            },
            fields: [
                {
                    type: 'date',
                    name: 'date',
                    dateFormat: 'F j, Y h:ia',
                    dateReadFormat: 'Y-m-d H:i:s'
                },
                {
                    name: 'ip'
                },
                {
                    name: 'uri'
                },
                {
                    name: 'class'
                },
                {
                    name: 'method'
                },
                {
                    name: 'referrer'
                },
                {
                    name: 'httpType'
                },
                {
                    name: 'query'
                },
                {
                    name: 'loggedIn'
                },
                {
                    name: 'userId'
                },
                {
                    name: 'userName'
                },
                {
                    name: 'get'
                },
                {
                    name: 'post'
                },
                {
                    name: 'json'
                },
                {
                    name: 'errorMessage'
                },
                {
                    name: 'errorLineNumber'
                },
                {
                    name: 'errorFile'
                },
                {
                    name: 'errorStackTrace'
                },
                {
                    name: 'errorExtraData'
                },
                {
                    name: 'errorType'
                }
            ]
        },
        ExceptionColumnStore: {
            proxy: {
                type: 'ajax',
                reader: {
                    type: 'array'
                }
            },
            fields: [
                {
                    name: 'columnName'
                }
            ]
        },
        OperationStore: {
            data: [
                {
                    comparison: 'Greater Than (>)',
                    value: '>'
                },
                {
                    comparison: 'Less Than (<)',
                    value: '<'
                },
                {
                    comparison: 'Equal To (=)',
                    value: '='
                },
                {
                    comparison: 'Like (%%)',
                    value: 'like'
                },
                {
                    comparison: 'Not Equal To (≠)',
                    value: '<>'
                },
                {
                    comparison: 'Greater Than or Equal To (>=)',
                    value: '>='
                },
                {
                    comparison: 'Less Than or Equal To (<=)',
                    value: '<='
                }
            ],
            fields: [
                {
                    name: 'comparison'
                },
                {
                    name: 'value'
                }
            ]
        }
    }

});