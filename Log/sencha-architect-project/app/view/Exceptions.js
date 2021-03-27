/*
 * File: app/view/Exceptions.js
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

Ext.define('Log.view.Exceptions', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.exceptions',

    requires: [
        'Log.view.ExceptionsViewModel',
        'Ext.form.Panel',
        'Ext.button.Button',
        'Ext.form.field.Date',
        'Ext.form.field.Time',
        'Ext.form.CheckboxGroup',
        'Ext.form.field.Checkbox',
        'Ext.grid.Panel',
        'Ext.grid.column.Column',
        'Ext.view.Table',
        'Ext.toolbar.Paging',
        'Ext.form.field.TextArea'
    ],

    viewModel: {
        type: 'exceptions'
    },
    itemId: 'Exceptions',
    bodyBorder: true,
    bodyStyle: 'background:none;',
    icon: '/inc/img/silk_icons/script_error.png',
    title: 'Exceptions',
    defaultListenerScope: true,

    layout: {
        type: 'vbox',
        align: 'stretch'
    },
    items: [
        {
            xtype: 'form',
            border: false,
            frame: true,
            itemId: 'exceptionSelectPanel',
            minHeight: 120,
            ui: 'default-framed',
            layout: 'vbox',
            frameHeader: false,
            items: [
                {
                    xtype: 'container',
                    padding: 10,
                    items: [
                        {
                            xtype: 'container',
                            cls: 'floatBox',
                            margin: '5 10 0 0',
                            width: 650,
                            layout: {
                                type: 'vbox',
                                align: 'stretch'
                            },
                            items: [
                                {
                                    xtype: 'container',
                                    flex: 1,
                                    margin: '0 0 5 0',
                                    layout: {
                                        type: 'hbox',
                                        align: 'stretch'
                                    },
                                    items: [
                                        {
                                            xtype: 'combobox',
                                            flex: 1,
                                            itemId: 'appSelection',
                                            margin: '0 0 0 10',
                                            maxWidth: 300,
                                            fieldLabel: 'App',
                                            labelWidth: 60,
                                            name: 'appName',
                                            value: '[ ALL ]',
                                            displayField: 'appName',
                                            queryDelay: 75,
                                            queryMode: 'local',
                                            typeAhead: true,
                                            valueField: 'appValue',
                                            bind: {
                                                store: '{ExceptionComboStore}'
                                            },
                                            listeners: {
                                                select: 'onCombobox1Select'
                                            }
                                        },
                                        {
                                            xtype: 'combobox',
                                            flex: 1,
                                            itemId: 'searchColumnException',
                                            margin: '0 0 0 15',
                                            maxWidth: 300,
                                            fieldLabel: 'Column',
                                            labelWidth: 60,
                                            name: 'searchColumn',
                                            displayField: 'columnName',
                                            queryMode: 'local',
                                            bind: {
                                                store: '{ExceptionColumnStore}'
                                            },
                                            listeners: {
                                                select: 'onComboboxSelect'
                                            }
                                        }
                                    ]
                                },
                                {
                                    xtype: 'container',
                                    margin: '0 10 0 0',
                                    layout: {
                                        type: 'hbox',
                                        align: 'stretch'
                                    },
                                    items: [
                                        {
                                            xtype: 'combobox',
                                            flex: 1,
                                            disabled: true,
                                            itemId: 'searchOperationException',
                                            margin: '0 0 0 10',
                                            maxWidth: 210,
                                            fieldLabel: 'Operation',
                                            labelWidth: 60,
                                            name: 'searchOperation',
                                            editable: false,
                                            matchFieldWidth: false,
                                            displayField: 'comparison',
                                            valueField: 'value',
                                            bind: {
                                                store: '{OperationStore}'
                                            }
                                        },
                                        {
                                            xtype: 'textfield',
                                            disabled: true,
                                            itemId: 'searchTermException',
                                            margin: '0 10 0 15',
                                            maxHeight: 25,
                                            width: 300,
                                            fieldLabel: 'Term',
                                            labelWidth: 40,
                                            name: 'searchTerm',
                                            enableKeyEvents: true,
                                            listeners: {
                                                keypress: 'onTextfieldKeypress',
                                                change: 'onSearchTermChange'
                                            }
                                        },
                                        {
                                            xtype: 'button',
                                            flex: 1,
                                            disabled: true,
                                            itemId: 'exceptionFilterButton',
                                            margin: '0 0 0 10',
                                            maxWidth: 70,
                                            icon: '/inc/img/silk_icons/find.png',
                                            text: 'Filter',
                                            listeners: {
                                                click: 'onExceptionFilterButtonClick'
                                            }
                                        }
                                    ]
                                }
                            ]
                        },
                        {
                            xtype: 'container',
                            cls: 'floatBox',
                            margin: '5 0 0 0',
                            width: 440,
                            layout: {
                                type: 'vbox',
                                align: 'stretch'
                            },
                            items: [
                                {
                                    xtype: 'container',
                                    flex: 1,
                                    margin: '0 10 0 0',
                                    layout: 'hbox',
                                    items: [
                                        {
                                            xtype: 'datefield',
                                            itemId: 'dateFromException',
                                            width: 200,
                                            fieldLabel: 'Date From',
                                            labelWidth: 75,
                                            name: 'dateFrom',
                                            value: new Date(),
                                            submitFormat: 'Y-m-d',
                                            listeners: {
                                                change: 'onDateFromExceptionChange'
                                            }
                                        },
                                        {
                                            xtype: 'datefield',
                                            itemId: 'dateToException',
                                            margin: '0 0 0 25',
                                            width: 200,
                                            fieldLabel: 'Date To',
                                            labelWidth: 65,
                                            name: 'dateTo',
                                            value: new Date(),
                                            submitFormat: 'Y-m-d',
                                            listeners: {
                                                change: 'onDateToExceptionChange'
                                            }
                                        }
                                    ]
                                },
                                {
                                    xtype: 'container',
                                    margin: '0 10 0 0',
                                    width: 420,
                                    layout: 'hbox',
                                    items: [
                                        {
                                            xtype: 'timefield',
                                            itemId: 'timeFromException',
                                            margin: '5 0 0 0',
                                            width: 200,
                                            fieldLabel: 'Time From',
                                            labelWidth: 75,
                                            name: 'timeFrom',
                                            value: '0:00',
                                            format: 'G:i',
                                            listeners: {
                                                change: 'onTimeFromExceptionChange'
                                            }
                                        },
                                        {
                                            xtype: 'timefield',
                                            itemId: 'timeToException',
                                            margin: '5 0 0 25',
                                            width: 200,
                                            fieldLabel: 'Time To',
                                            labelWidth: 65,
                                            name: 'timeTo',
                                            value: '23:59',
                                            format: 'G:i',
                                            listeners: {
                                                change: 'onTimeToExceptionChange'
                                            }
                                        }
                                    ]
                                }
                            ]
                        }
                    ]
                },
                {
                    xtype: 'checkboxgroup',
                    height: 35,
                    margin: '5 10 0 10',
                    minHeight: 35,
                    width: 500,
                    fieldLabel: 'Error Types',
                    labelWidth: 85,
                    layout: {
                        type: 'hbox',
                        align: 'stretch'
                    },
                    items: [
                        {
                            xtype: 'checkboxfield',
                            width: 100,
                            name: 'error',
                            boxLabel: 'Errors',
                            checked: true,
                            inputValue: '1',
                            uncheckedValue: '0'
                        },
                        {
                            xtype: 'checkboxfield',
                            width: 100,
                            name: 'exception',
                            boxLabel: 'Exceptions',
                            checked: true,
                            inputValue: '1',
                            uncheckedValue: '0'
                        },
                        {
                            xtype: 'checkboxfield',
                            width: 100,
                            name: 'shutdown',
                            boxLabel: 'Shutdowns',
                            checked: true,
                            inputValue: '1',
                            uncheckedValue: '0'
                        },
                        {
                            xtype: 'checkboxfield',
                            width: 125,
                            name: 'usermsg',
                            boxLabel: 'User Messages',
                            inputValue: '1',
                            uncheckedValue: '0'
                        }
                    ],
                    listeners: {
                        change: 'onCheckboxgroupChange'
                    }
                }
            ],
            listeners: {
                resize: 'onExceptionSelectPanelResize'
            }
        },
        {
            xtype: 'container',
            flex: 1,
            layout: {
                type: 'vbox',
                align: 'stretch'
            },
            items: [
                {
                    xtype: 'gridpanel',
                    flex: 1,
                    itemId: 'exceptionGrid',
                    bind: {
                        store: '{ExceptionGridStore}'
                    },
                    columns: [
                        {
                            xtype: 'gridcolumn',
                            itemId: 'date',
                            width: 150,
                            dataIndex: 'date',
                            text: 'Date'
                        },
                        {
                            xtype: 'gridcolumn',
                            itemId: 'ip',
                            dataIndex: 'ip',
                            text: 'IP'
                        },
                        {
                            xtype: 'gridcolumn',
                            hidden: true,
                            itemId: 'uri',
                            width: 200,
                            dataIndex: 'uri',
                            text: 'URI'
                        },
                        {
                            xtype: 'gridcolumn',
                            itemId: 'class',
                            width: 150,
                            dataIndex: 'class',
                            text: 'Class'
                        },
                        {
                            xtype: 'gridcolumn',
                            itemId: 'method',
                            width: 150,
                            dataIndex: 'method',
                            text: 'Method'
                        },
                        {
                            xtype: 'gridcolumn',
                            itemId: 'referrer',
                            width: 150,
                            dataIndex: 'referrer',
                            text: 'Referrer'
                        },
                        {
                            xtype: 'gridcolumn',
                            itemId: 'httpType',
                            width: 93,
                            dataIndex: 'httpType',
                            text: 'HTTP Type'
                        },
                        {
                            xtype: 'gridcolumn',
                            itemId: 'query',
                            dataIndex: 'query',
                            text: 'Query'
                        },
                        {
                            xtype: 'gridcolumn',
                            itemId: 'loggedIn',
                            dataIndex: 'loggedIn',
                            text: 'Logged In'
                        },
                        {
                            xtype: 'gridcolumn',
                            hidden: true,
                            itemId: 'userId',
                            dataIndex: 'userId',
                            text: 'User Id'
                        },
                        {
                            xtype: 'gridcolumn',
                            itemId: 'userName',
                            dataIndex: 'userName',
                            text: 'User Name'
                        },
                        {
                            xtype: 'gridcolumn',
                            hidden: true,
                            itemId: 'message',
                            dataIndex: 'errorMessage',
                            text: 'Message'
                        },
                        {
                            xtype: 'gridcolumn',
                            itemId: 'lineNumber',
                            width: 104,
                            dataIndex: 'errorLineNumber',
                            text: 'Line Number'
                        },
                        {
                            xtype: 'gridcolumn',
                            itemId: 'file',
                            width: 300,
                            dataIndex: 'errorFile',
                            text: 'File'
                        },
                        {
                            xtype: 'gridcolumn',
                            hidden: true,
                            itemId: 'stackTrace',
                            dataIndex: 'errorStackTrace',
                            text: 'Stack Trace'
                        },
                        {
                            xtype: 'gridcolumn',
                            itemId: 'type',
                            width: 90,
                            dataIndex: 'errorType',
                            text: 'Type'
                        },
                        {
                            xtype: 'gridcolumn',
                            itemId: 'exceptionData',
                            width: 120,
                            dataIndex: 'errorExtraData',
                            text: 'Exception Data'
                        }
                    ],
                    viewConfig: {
                        enableTextSelection: true
                    },
                    dockedItems: [
                        {
                            xtype: 'pagingtoolbar',
                            dock: 'top',
                            width: 360,
                            displayInfo: true,
                            items: [
                                {
                                    xtype: 'button',
                                    icon: '/inc/img/silk_icons/page_white_excel.png',
                                    text: 'Download',
                                    listeners: {
                                        click: 'onButtonClick1'
                                    }
                                }
                            ]
                        }
                    ],
                    listeners: {
                        selectionchange: 'onExceptionGridSelectionChange',
                        celldblclick: 'onExceptionGridCellDblClick',
                        cellcontextmenu: 'onExceptionGridCellContextMenu'
                    }
                },
                {
                    xtype: 'container',
                    height: 200,
                    minHeight: 180,
                    resizable: {
                        pinned: true
                    },
                    resizeHandles: 'n',
                    layout: {
                        type: 'hbox',
                        align: 'stretch'
                    },
                    items: [
                        {
                            xtype: 'container',
                            flex: 1,
                            layout: {
                                type: 'vbox',
                                align: 'stretch'
                            },
                            items: [
                                {
                                    xtype: 'textareafield',
                                    flex: 1,
                                    itemId: 'exception',
                                    padding: '5 0 0 0',
                                    width: '35%',
                                    fieldLabel: '&nbsp;Exception Details',
                                    labelAlign: 'top',
                                    editable: false,
                                    listeners: {
                                        render: 'onExceptionRender'
                                    }
                                },
                                {
                                    xtype: 'container',
                                    flex: 1,
                                    layout: {
                                        type: 'hbox',
                                        align: 'stretch'
                                    },
                                    items: [
                                        {
                                            xtype: 'textareafield',
                                            flex: 1,
                                            itemId: 'getException',
                                            padding: '5 0 0 0',
                                            width: '15%',
                                            fieldLabel: '&nbsp;GET Request Data',
                                            labelAlign: 'top',
                                            editable: false,
                                            listeners: {
                                                render: 'onGetExceptionRender'
                                            }
                                        },
                                        {
                                            xtype: 'textareafield',
                                            flex: 1,
                                            itemId: 'postException',
                                            padding: '5 0 0 0',
                                            width: '15%',
                                            fieldLabel: 'POST Request Data',
                                            labelAlign: 'top',
                                            editable: false,
                                            listeners: {
                                                render: 'onPostExceptionRender'
                                            }
                                        },
                                        {
                                            xtype: 'textareafield',
                                            flex: 1,
                                            itemId: 'json',
                                            padding: '5 0 0 0',
                                            width: '15%',
                                            fieldLabel: 'JSON Data',
                                            labelAlign: 'top',
                                            editable: false,
                                            listeners: {
                                                render: 'onPostExceptionRender1'
                                            }
                                        }
                                    ]
                                }
                            ]
                        },
                        {
                            xtype: 'container',
                            flex: 1,
                            layout: {
                                type: 'hbox',
                                align: 'stretch'
                            },
                            items: [
                                {
                                    xtype: 'textareafield',
                                    flex: 1,
                                    itemId: 'stackTraceArea',
                                    padding: '5 0 0 0',
                                    fieldLabel: 'Stack Trace',
                                    labelAlign: 'top',
                                    editable: false,
                                    listeners: {
                                        render: 'onStackTraceRender'
                                    }
                                }
                            ]
                        }
                    ]
                }
            ]
        }
    ],

    onCombobox1Select: function(combo, record, eOpts) {
        this.loadExceptions();
    },

    onComboboxSelect: function(combo, record, eOpts) {
        this.queryById('searchTermException').setDisabled(false);
        this.queryById('exceptionFilterButton').setDisabled(false);
        this.queryById('searchOperationException').setDisabled(false);
        if(record.data.columnName !== this.exceptionFilterColumn){
            this.setFilterButtonState('filter','exceptionFilterButton');
        }
    },

    onTextfieldKeypress: function(textfield, e, eOpts) {
        var searchColumn = this.queryById('searchColumnException');
        var searchTerm = this.queryById('searchTermException');
        if(e.getKey() === e.ENTER){
            this.loadExceptions();
            this.exceptionFilterColumn = searchColumn.getValue();
            this.exceptionFilterTerm = searchTerm.getValue();
            this.setFilterButtonState('clear','exceptionFilterButton');
        }
    },

    onSearchTermChange: function(field, newValue, oldValue, eOpts) {
        if(newValue !== this.exceptionFilterColumn){
            this.setFilterButtonState('filter','exceptionFilterButton');
        }
    },

    onExceptionFilterButtonClick: function(button, e, eOpts) {
        var searchColumn = this.queryById('searchColumnException');
        var searchTerm = this.queryById('searchTermException');
        var searchOperation = this.queryById('searchOperationException');
        if(this.queryById('exceptionFilterButton').buttonState === 'filter'){
            this.exceptionFilterColumn = searchColumn.getValue();
            this.exceptionFilterOperation = searchOperation.getValue();
            this.exceptionFilterTerm = searchTerm.getValue();
            if(this.exceptionFilterColumn !== ""){
                this.loadExceptions();
                this.setFilterButtonState('clear','exceptionFilterButton');
            }else {
               Ext.Msg.alert("Error","No Filter Set");
               return false;
            }
        } else {
            this.exceptionFilterColumn = null;
            this.exceptionFilterTerm = null;
            searchOperation.reset();
            searchColumn.reset();
            searchTerm.reset();
            searchTerm.setDisabled(true);
            searchOperation.setDisabled(true);
            this.setFilterButtonState('filter','exceptionFilterButton');
            this.loadExceptions();
        }
    },

    onDateFromExceptionChange: function(field, newValue, oldValue, eOpts) {
        this.loadExceptions();
    },

    onDateToExceptionChange: function(field, newValue, oldValue, eOpts) {
        this.loadExceptions();
    },

    onTimeFromExceptionChange: function(field, newValue, oldValue, eOpts) {
        this.loadExceptions();
    },

    onTimeToExceptionChange: function(field, newValue, oldValue, eOpts) {
        this.loadExceptions();
    },

    onExceptionSelectPanelResize: function(component, width, height, oldWidth, oldHeight, eOpts) {
        // if(width >= 1450){
        //     component.setHeight(35);
        // }else if(width >= 940 ){
        //     component.setHeight(70);
        // }else{
        //     component.setHeight(105);
        // }
    },

    onCheckboxgroupChange: function(field, newValue, oldValue, eOpts) {
        this.loadExceptions();
    },

    onButtonClick1: function(button, e, eOpts) {
        if(this.queryById('appSelection').getValue() === null){
            return false;
        }

        this.exportToCSV();
    },

    onExceptionGridSelectionChange: function(model, selected, eOpts) {
        if(selected.length < 1){
            return false;
        }

        this.queryById('exception').setValue(selected[0].data.errorMessage);
        this.queryById('stackTraceArea').setValue(selected[0].data.errorStackTrace);
        this.queryById('getException').setValue(selected[0].data.get);
        this.queryById('postException').setValue(selected[0].data.post);
        this.queryById('json').setValue(selected[0].data.json);
    },

    onExceptionGridCellDblClick: function(tableview, td, cellIndex, record, tr, rowIndex, e, eOpts) {
        this.showTextDetailWindow(record.get(e.position.column.dataIndex));
    },

    onExceptionGridCellContextMenu: function(tableview, td, cellIndex, record, tr, rowIndex, e, eOpts) {
        e.preventDefault();
        this.exceptionColumnName = e.position.column.dataIndex;
        this.exceptionColumnValue = record.getData()[this.exceptionColumnName];
        //this.queryById('tabPanel').queryById('contextMenu').showAt(e.getXY());
        this.contextMenu.showAt(e.getXY());
    },

    onExceptionRender: function(component, eOpts) {
        component.el.on('dblclick',this.dblClickField,this,component);
    },

    onGetExceptionRender: function(component, eOpts) {
        component.el.on('dblclick',this.dblClickField,this,component);
    },

    onPostExceptionRender: function(component, eOpts) {
        component.el.on('dblclick',this.dblClickField,this,component);
    },

    onPostExceptionRender1: function(component, eOpts) {
        component.el.on('dblclick',this.dblClickField,this,component);
    },

    onStackTraceRender: function(component, eOpts) {
        component.el.on('dblclick',this.dblClickField,this,component);
    },

    loadExceptions: function() {
        this.queryById('exception').setValue("");
        this.queryById('stackTraceArea').setValue("");
        this.queryById('getException').setValue("");
        this.queryById('postException').setValue("");

        var submitData = this.queryById('exceptionSelectPanel').getValues();

        if(!submitData.appName){
            return false;
        }

        this.currentAppName = submitData.appName;

        var params =

            {
                appName:submitData.appName,
                error:submitData.error,
                exception:submitData.exception,
                shutdown:submitData.shutdown,
                usermsg:submitData.usermsg
            };

        if(submitData.dateFrom){
            params.dateFrom = submitData.dateFrom;
        }

        if(submitData.dateTo){
            params.dateTo = submitData.dateTo;
        }

        if(submitData.timeTo){
            params.timeTo = submitData.timeTo;
        }

        if(submitData.timeFrom){
            params.timeFrom = submitData.timeFrom;
        }

        if(submitData.searchColumn){
            params.searchColumn = submitData.searchColumn;
        }

        if(submitData.searchOperation){
            params.searchOperation = submitData.searchOperation;
        }

        if(submitData.searchTerm){
            params.searchTerm = submitData.searchTerm;
        }

        var exceptionGridStore = this.getViewModel().getStore('ExceptionGridStore');

        exceptionGridStore.getProxy().setExtraParams(params);
        exceptionGridStore.loadPage(1);
    },

    setFilterButtonState: function(state, itemId) {
        var button = this.queryById(itemId);
        if(state === 'clear'){
            button.buttonState = 'clear';
            button.setText('Clear');
            button.setIcon('/inc/img/silk_icons/cancel.png');
        } else {
            button.buttonState = 'filter';
            button.setText('Filter');
            button.setIcon('/inc/img/silk_icons/find.png');
        }
    },

    showTextDetailWindow: function(text) {
        if(!this.textWindow){
            this.textWindow = Ext.create('widget.Textdetailwindow');
        }
        this.textWindow.setText(text);
    },

    dblClickField: function(event, el, component) {
        this.showTextDetailWindow(component.getValue());
    },

    exportToCSV: function() {
        var submitData = this.queryById('exceptionSelectPanel').getValues();

        if(!submitData.appName){
            return false;
        }

        var params =

            {
                tab:'exception',
                appName:submitData.appName,
                exception:submitData.exception,
                shutdown:submitData.shutdown,
                usermsg:submitData.usermsg
            };

        if(submitData.dateFrom){
            params.dateFrom = submitData.dateFrom;
        }

        if(submitData.dateTo){
            params.dateTo = submitData.dateTo;
        }

        window.location.href = "exportToCSV?"+ Ext.Object.toQueryString(params);
    },

    maskFilters: function() {
        this.queryById('exceptionSelectPanel').mask('Loading...');
    },

    unmaskFilters: function() {
        this.queryById('exceptionSelectPanel').unmask();
    }

});