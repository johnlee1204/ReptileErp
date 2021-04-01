/*
 * File: app/view/AddGroupWindow.js
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

Ext.define('GroupManager.view.AddGroupWindow', {
    extend: 'Ext.window.Window',
    alias: 'widget.addgroupwindow',

    requires: [
        'Ext.form.Panel',
        'Ext.form.field.Text',
        'Ext.toolbar.Toolbar',
        'Ext.button.Button',
        'Ext.toolbar.Spacer'
    ],

    draggable: false,
    height: 114,
    width: 285,
    resizable: false,
    layout: 'fit',
    closable: false,
    closeAction: 'hide',
    title: '',
    modal: true,

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [
                {
                    xtype: 'form',
                    itemId: 'newGroupFormPanel',
                    bodyPadding: 10,
                    title: '',
                    layout: {
                        type: 'vbox',
                        align: 'stretch'
                    },
                    items: [
                        {
                            xtype: 'textfield',
                            validator: function(value) {
                                //Match 3 or more alpha numeric. I think sencha has prebuilt validators, but I'm already thinking regex, so whatever.
                                //Sencha's editor reads regex? coooool!
                                if(/\w{3,}/.test(value) === false) {
                                return "Must be at least 3 alpha-numeric characters.";
                            }
                            return true;
                            },
                            itemId: 'newGroupNameField',
                            fieldLabel: 'Group Name',
                            labelWidth: 80,
                            name: 'newGroupNameField'
                        }
                    ],
                    dockedItems: [
                        {
                            xtype: 'toolbar',
                            flex: 1,
                            dock: 'top',
                            items: [
                                {
                                    xtype: 'button',
                                    disabled: true,
                                    itemId: 'addGroupBtn',
                                    icon: '/inc/img/silk_icons/add.png',
                                    text: 'Add Group',
                                    listeners: {
                                        click: {
                                            fn: me.onAddGroupBtnClick,
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

    onAddGroupBtnClick: function(button, e, eOpts) {
        var groupPanel = this.queryById("newGroupFormPanel");
        var groupPanelForm = groupPanel.getForm();

        this.setLoading("Adding...");
        AERP.Ajax.request({
            url:AERP.SystemRoot+'GroupManager/addGroup',
            params:groupPanelForm.getValues(),
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
        var addGroupBtn = this.queryById("addGroupBtn");
        if(valid){
            addGroupBtn.enable();
        }else{
            addGroupBtn.disable();
        }
    },

    onWindowRender: function(component, eOpts) {
        this.queryById("newGroupNameField").focus();
    }

});