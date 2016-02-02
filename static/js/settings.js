/**
 * Paypal
 *
 * LICENSE
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015 Dominik Pfaffenbauer (http://dominik.pfaffenbauer.at)
 * @license    http://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

pimcore.registerNS("pimcore.plugin.paypal.settings");
pimcore.plugin.paypal.settings = Class.create({

    initialize: function () {
        this.getData();
    },

    getData: function () {
        Ext.Ajax.request({
            url: "/plugin/Paypal/admin/get",
            success: function (response)
            {
                this.data = Ext.decode(response.responseText);

                this.getTabPanel();

            }.bind(this)
        });
    },

    getValue: function (key) {
        var current = null;

        if(this.data.values.hasOwnProperty(key)) {
            current = this.data.values[key];
        }

        if (typeof current != "object" && typeof current != "array" && typeof current != "function") {
            return current;
        }

        return "";
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = Ext.create('Ext.panel.Panel', {
                id: "coreshop_paypal",
                title: t("coreshop_paypal"),
                iconCls: "coreshop_icon_paypal",
                border: false,
                layout: "fit",
                closable:true
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("coreshop_paypal");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("coreshop_paypal");
            }.bind(this));


            this.layout = Ext.create('Ext.form.Panel', {
                bodyStyle:'padding:20px 5px 20px 5px;',
                border: false,
                autoScroll: true,
                forceLayout: true,
                defaultType: 'textfield',
                defaults: {
                    forceLayout: true
                },
                fieldDefaults: {
                    labelWidth: 250
                },
                buttons: [
                    {
                        text: "Save",
                        handler: this.save.bind(this),
                        iconCls: "pimcore_icon_apply"
                    }
                ],
                items: [
                    {
                        fieldLabel: t('coreshop_paypal_clientid'),
                        name: 'PAYPAL.CLIENTID',
                        value: this.getValue("PAYPAL.CLIENTID"),
                        enableKeyEvents: true
                    },
                    {
                        fieldLabel: t('coreshop_paypal_clientsecret'),
                        name: 'PAYPAL.CLIENTSECRET',
                        value: this.getValue("PAYPAL.CLIENTSECRET"),
                        enableKeyEvents: true
                    },
                    {
                        fieldLabel: t('coreshop_paypal_mode'),
                        name: 'PAYPAL.MODE',
                        value: this.getValue("PAYPAL.MODE"),
                        enableKeyEvents: true
                    }
                ]
            });

            this.panel.add(this.layout);

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("coreshop_paypal");
    },

    save: function () {
        var values = this.layout.getForm().getFieldValues();

        Ext.Ajax.request({
            url: "/plugin/Paypal/admin/set",
            method: "post",
            params: {
                data: Ext.encode(values)
            },
            success: function (response) {
                try {
                    var res = Ext.decode(response.responseText);
                    if (res.success) {
                        pimcore.helpers.showNotification(t("success"), t("coreshop_paypal_save_success"), "success");
                    } else {
                        pimcore.helpers.showNotification(t("error"), t("coreshop_paypal_save_error"),
                            "error", t(res.message));
                    }
                } catch(e) {
                    pimcore.helpers.showNotification(t("error"), t("coreshop_paypal_save_error"), "error");
                }
            }
        });
    }
});