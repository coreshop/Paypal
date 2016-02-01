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

pimcore.registerNS("pimcore.plugin.coreshop.paypal");

pimcore.plugin.coreshop.paypal = Class.create(coreshop.plugin.admin, {

    getClassName: function() {
        return "pimcore.plugin.coreshop.paypal";
    },

    initialize: function() {
        coreshop.plugin.broker.registerPlugin(this);
    },

    uninstall: function() {
        //TODO remove from menu
    },

    coreshopReady: function (coreshop, broker) {
        coreshop.addPluginMenu({
            text: t("coreshop_paypal"),
            iconCls: "coreshop_icon_paypal",
            handler: this.openPaypal
        });
    },

    openPaypal: function()
    {
        try {
            pimcore.globalmanager.get("coreshop_paypal").activate();
        }
        catch (e) {
            //console.log(e);
            pimcore.globalmanager.add("coreshop_paypal", new pimcore.plugin.coreshop.paypal.settings());
        }
    }
});

new pimcore.plugin.coreshop.paypal();