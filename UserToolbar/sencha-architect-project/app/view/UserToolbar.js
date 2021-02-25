/*
 * File: app/view/UserToolbar.js
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

Ext.define('UserToolbar.view.UserToolbar', {
	extend: 'Ext.toolbar.Toolbar',
	alias: 'widget.usertoolbar',

	requires: [
		'UserToolbar.view.UserToolbarViewModel',
		'Ext.container.Container',
		'Ext.button.Button',
		'Ext.toolbar.Spacer'
	],

	viewModel: {
		type: 'usertoolbar'
	},
	dock: 'top',
	defaultButtonUI: 'default',
	defaultListenerScope: true,

	items: [
		{
			xtype: 'container',
			itemId: 'accountInformation',
			margin: '0 20 0 10'
		},
		{
			xtype: 'button',
			itemId: 'loginButton',
			icon: '/inc/img/silk_icons/key.png',
			text: 'Log In',
			listeners: {
				click: 'onLoginButtonClick'
			}
		},
		{
			xtype: 'button',
			itemId: 'logoutButton',
			icon: '/inc/img/silk_icons/lock.png',
			text: 'Log Out',
			listeners: {
				click: 'onLogoutButtonClick'
			}
		},
		{
			xtype: 'tbspacer',
			flex: 1
		},
		{
			xtype: 'button',
			itemId: 'setupButton',
			icon: '/inc/img/silk_icons/cog.png',
			listeners: {
				click: 'onButtonClick'
			}
		}
	],
	listeners: {
		afterrender: 'onToolbarAfterRender'
	},

	onLoginButtonClick: function(button, e, eOpts) {
		window.location.href = "/Login?redirect=" + window.location.pathname.replace("/", "");
	},

	onLogoutButtonClick: function(button, e, eOpts) {
		window.location.href = "/Logout?redirect=" + window.location.pathname.replace(/\//g, "");
	},

	onButtonClick: function(button, e, eOpts) {
		if(!this.userId) {
			Ext.Msg.alert("", "Log in to view Setup");
			return;
		}

		AppWindowManager.appLink('userToolbarSetup', {dataKey:this.userId});
	},

	onToolbarAfterRender: function(component, eOpts) {
		this.linkButtons = [];

		this.readUserInfo();

		AppWindowManager.appOn('userToolbarSetup', {
			scope:this,
			userlinkchange:function(appWin) {
				this.readUserInfo();
			}
		});
	},

	generateLinkButtons: function(buttons) {
		for(let i in this.linkButtons) {
			this.remove(this.linkButtons[i]);
		}
		this.linkButtons = [];
		let toolbarWidth = this.getEl().dom.scrollWidth;
		let totalButtonWidth = 0;

		let tooManyLinksWarning = "";

		for(let i in buttons) {
			let button = Ext.create('Ext.button.Button', {
				text:buttons[i].linkName,
				margin:'0 0 0 5',
				listeners:{
					scope:this,
					click:function() {
						//window.open(buttons[i].linkPath);
						window.location.href = buttons[i].linkPath;
					}
				}
			});
			if(buttons[i].iconPath) {
				button.icon = buttons[i].iconPath;
			}

			let testButton = Ext.create('Ext.button.Button', {
				text:buttons[i].linkName,
				margin:'0 0 0 5',
				listeners:{
					scope:this,
					click:function() {
						window.open(buttons[i].linkPath);
					}
				}
			});
			if(buttons[i].iconPath) {
				testButton.icon = buttons[i].iconPath;
			}

			this.add(testButton);
			let buttonWidth = testButton.getWidth();//This is width
			this.remove(testButton);

			if(totalButtonWidth + buttonWidth < toolbarWidth - 400) {
				totalButtonWidth += buttonWidth + 5;
				this.linkButtons.push(button);
			} else {
				let buttonsCutOff = buttons.length - this.linkButtons.length;

				if(buttonsCutOff === 1) {
					tooManyLinksWarning =  buttonsCutOff + " Button Cut Off";
				} else {
					tooManyLinksWarning =  buttonsCutOff + " Buttons Cut Off";
				}
				break;
			}
		}
		if(tooManyLinksWarning !== "") {
			this.linkButtons.push(Ext.create('Ext.container.Container', {
				html:tooManyLinksWarning,
				margin:'0 0 0 5'
			}));
		}

		this.insert(4, this.linkButtons);
	},

	readUserInfo: function() {
		AERP.Ajax.request({
			url:'/UserToolbar/readLoggedInInformation',
			success:function(reply) {
				let accountInformation = this.queryById('accountInformation');
				let loginButton = this.queryById('loginButton');
				let logoutButton = this.queryById('logoutButton');
				let setupButton = this.queryById('setupButton');
				this.generateAllAppsButton(reply.allApps);
				if(reply.userData === null) {
					accountInformation.setHtml("You are not Logged In!");
					loginButton.show();
					logoutButton.hide();
					setupButton.hide();
					return;
				}
				this.show();
				loginButton.hide();
				logoutButton.show();
				setupButton.show();
				accountInformation.setHtml(reply.userData.firstName + " " + reply.userData.lastName);
				this.userId = reply.userData.userId;
				this.generateLinkButtons(reply.userButtons);
			},
			scope:this,
		});
	},

	generateAllAppsButton: function(allApps) {
		this.remove(this.allAppsMenu);

		let menu =  Ext.create('Ext.menu.Menu', {
			text:"All Apps"
		});

		for(let i in allApps) {
			let subMenu = Ext.create('Ext.menu.Item', {
				'text':i
			});
			subMenu.menu = Ext.create('Ext.menu.Menu');

			for(let j in allApps[i]) {
				let menuItem = {
					xtype:'menuitem',
					text:allApps[i][j].linkName,
					listeners:{scope:this, click:function() {
						//window.open(allApps[i][j].linkPath);
						window.location.href = allApps[i][j].linkPath;
					}}};

				if(allApps[i][j].iconPath) {
					menuItem.icon = allApps[i][j].iconPath;
				}

				subMenu.menu.add(Ext.create(menuItem));
			}

			menu.add(subMenu);
		}

		this.allAppsMenu = Ext.create('Ext.button.Button', {
			text:"All Apps",
			menu:menu,
			icon:'/inc/img/silk_icons/world.png',
			margin:'0 0 0 5'
		});

		this.insert(3,this.allAppsMenu);
	}

});