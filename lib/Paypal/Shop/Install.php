<?php
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

namespace Paypal\Shop;

use CoreShop\Plugin;
use CoreShop\Model\Plugin\InstallPlugin;
use CoreShop\Plugin\Install as Installer;

class Install implements InstallPlugin
{
    public function attachEvents()
    {
        Plugin::getEventManager()->attach("install.post", array($this, "installPost"));
        Plugin::getEventManager()->attach("uninstall.pre", array($this, "uninstallPre"));
    }

    /**
     * Post installation of CoreShop
     *
     * @param $e
     */
    public function installPost($e)
    {
        $shopInstaller = $e->getParam("installer");

        $this->install($shopInstaller);
    }

    /**
     * Pre Installation of CoreShop
     *
     * @param $e
     */
    public function uninstallPre($e)
    {
        $shopInstaller = $e->getParam("installer");

        $this->uninstall($shopInstaller);
    }

    /**
     * Install Paypal CoreShop addon
     *
     * @param Installer $installer
     */
    public function install(Installer $installer)
    {
        $installer->createObjectBrick("CoreShopPaymentPaypal", PIMCORE_PLUGINS_PATH . "/Paypal/install/objectbrick-CoreShopPaymentPaypal.json");
    }

    /**
     * Uninstall Paypal CoreShop addon
     *
     * @param Installer $installer
     */
    public function uninstall(Installer $installer)
    {
        $installer->removeObjectBrick("CoreShopPaymentPaypal");
    }
}
