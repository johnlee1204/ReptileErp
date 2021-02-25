/*
 * File: app/view/ApplicationLogPanel.js
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

Ext.define('Log.view.ApplicationLogPanel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.applicationlogpanel',

    requires: [
        'Log.view.ApplicationLogPanelViewModel',
        'Log.view.ApplicationLogs',
        'Ext.container.Container'
    ],

    viewModel: {
        type: 'applicationlogpanel'
    },
    height: 626,
    layout: 'fit',
    bodyBorder: true,
    bodyStyle: 'background:none;',
    icon: '/inc/img/silk_icons/application_view_tile.png',
    title: 'Application Log',

    items: [
        {
            xtype: 'applicationlogs'
        }
    ]

});