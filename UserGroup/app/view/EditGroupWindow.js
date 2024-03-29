/*
 * File: app/view/EditGroupWindow.js
 *
 * This file was generated by Sencha Architect version 3.0.4.
 * http://www.sencha.com/products/architect/
 *
 * This file requires use of the Ext JS 4.2.x library, under independent license.
 * License of Sencha Architect does not include license for Ext JS 4.2.x. For more
 * details see http://www.sencha.com/license or contact license@sencha.com.
 *
 * This file will be auto-generated each and everytime you save your project.
 *
 * Do NOT hand edit this file.
 */

Ext.define('GroupManager.view.EditGroupWindow', {
    extend: 'Ext.window.Window',
    alias: 'widget.editgroupwindow',

    requires: [
        'Ext.form.Panel',
        'Ext.toolbar.Toolbar',
        'Ext.button.Button',
        'Ext.toolbar.Spacer',
        'Ext.form.field.Display',
        'Ext.form.field.Text',
        'Ext.grid.Panel',
        'Ext.grid.View',
        'Ext.grid.column.CheckColumn'
    ],

    height: 376,
    width: 408,
    layout: 'fit',
    closable: false,
    closeAction: 'hide',
    title: 'Edit Group',
    modal: true,

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [
                {
                    xtype: 'form',
                    itemId: 'editGroupFormPanel',
                    bodyPadding: 10,
                    title: '',
                    layout: {
                        type: 'vbox',
                        align: 'stretch'
                    },
                    dockedItems: [
                        {
                            xtype: 'toolbar',
                            flex: 1,
                            dock: 'top',
                            items: [
                                {
                                    xtype: 'button',
                                    disabled: true,
                                    itemId: 'saveGroupBtn',
                                    icon: '/inc/img/silk_icons/disk.png',
                                    text: 'Save',
                                    listeners: {
                                        click: {
                                            fn: me.onSaveGroupBtnClick,
                                            scope: me
                                        }
                                    }
                                },
                                {
                                    xtype: 'tbspacer',
                                    flex: 1
                                },
                                {
                                    xtype: 'button',
                                    icon: '/inc/img/silk_icons/cancel.png',
                                    text: 'Cancel',
                                    listeners: {
                                        click: {
                                            fn: me.onButtonClick1,
                                            scope: me
                                        }
                                    }
                                }
                            ]
                        }
                    ],
                    items: [
                        {
                            xtype: 'displayfield',
                            itemId: 'editGroupIdField',
                            fieldLabel: 'Group Id',
                            labelWidth: 80,
                            name: 'editGroupIdField',
                            submitValue: true
                        },
                        {
                            xtype: 'textfield',
                            validator: function(value) {
                                //Match 3 or more alpha numeric. I think sencha has prebuilt validators, but I'm already thinking regex, so whatever.
                                //Sencha's editor reads regex? coooool!
                                if(/^[a-z,A-Z,0-9]{3,50}$/.test(value) === false) {
                                return "Must be at 3-50 alpha-numeric characters.";
                            }
                            return true;
                            },
                            itemId: 'editGroupNameField',
                            fieldLabel: 'Group Name',
                            labelWidth: 80,
                            name: 'editGroupNameField'
                        },
                        {
                            xtype: 'gridpanel',
                            flex: 1,
                            itemId: 'groupPermissionsGrid',
                            title: 'Group Permissions',
                            forceFit: true,
                            store: 'GroupPermissionsStore',
                            columns: [
                                {
                                    xtype: 'gridcolumn',
                                    dataIndex: 'appName',
                                    text: 'App Name',
                                    flex: 1
                                },
                                {
                                    xtype: 'checkcolumn',
                                    width: 50,
                                    dataIndex: 'permissionCreate',
                                    text: 'Create'
                                },
                                {
                                    xtype: 'checkcolumn',
                                    width: 50,
                                    dataIndex: 'permissionRead',
                                    text: 'Read'
                                },
                                {
                                    xtype: 'checkcolumn',
                                    width: 50,
                                    dataIndex: 'permissionUpdate',
                                    text: 'Update'
                                },
                                {
                                    xtype: 'checkcolumn',
                                    width: 50,
                                    dataIndex: 'permissionDelete',
                                    text: 'Delete'
                                }
                            ]
                        }
                    ],
                    listeners: {
                        validitychange: {
                            fn: me.onNewGroupFormPanelValidityChange,
                            scope: me
                        }
                    }
                }
            ],
            listeners: {
                render: {
                    fn: me.onWindowRender,
                    scope: me
                }
            }
        });

        me.callParent(arguments);
    },

    onSaveGroupBtnClick: function(button, e, eOpts) {
        var groupPanel = this.queryById("editGroupFormPanel");
        var groupPanelForm = groupPanel.getForm();

        this.setLoading("Saving...");
        AERP.Ajax.request({
            url:AERP.SystemRoot+'GroupManager/editGroup',
            params:{groupInfo:JSON.stringify(groupPanelForm.getValues()),groupPermissions:''},
            success:function(reply){
                this.setLoading(false);
                if(!this.groupWindow){
                    this.groupWindow = Ext.create('widget.groupmanagerpanel');
                }
                this.groupWindow.queryById("groupGrid").getStore().loadData(reply.groups);
                this.hide();
            },
            failure:function(){
                this.setLoading(false);
            },
            scope:this
        });


    },

    onButtonClick1: function(button, e, eOpts) {
        this.hide();
    },

    onNewGroupFormPanelValidityChange: function(basic, valid, eOpts) {
        var saveGroupBtn = this.queryById("saveGroupBtn");
        if(valid){
            saveGroupBtn.enable();
        }else{
            saveGroupBtn.disable();
        }
    },

    onWindowRender: function(component, eOpts) {
        this.queryById("editGroupNameField").focus();
    },

    loadPermissionsGridForGroup: function(groupId) {
        AERP.Ajax.request({
            url:AERP.SystemRoot+'GroupManager/getGroupPermissions',
            params:{groupId:groupId},
            success:function(reply){
                console.log(reply);
                this.queryById('groupPermissionsGrid').getStore().loadData(reply.groupPermissions);
            },
            failure:function(){

            },
            scope:this
        });
    }

});