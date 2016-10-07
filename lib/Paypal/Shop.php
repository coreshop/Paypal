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
 * @copyright  Copyright (c) 2015-2016 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Paypal;

use CoreShop\Plugin as CorePlugin;
use Paypal\Shop\Install;
use Paypal\Shop\Provider;

/**
 * Class Shop
 * @package Paypal
 */
class Shop
{
    public static $install;

    /**
     * @throws \Zend_EventManager_Exception_InvalidArgumentException
     */
    public function attachEvents()
    {
        self::getInstall()->attachEvents();

        $shopProvider = new Provider();

        \Pimcore::getEventManager()->attach("coreshop.payment.getProvider", function ($e) use ($shopProvider) {
            return $shopProvider;
        });
    }

    /**
     * @return Install
     */
    public static function getInstall()
    {
        if (!self::$install) {
            self::$install = new Install();
        }
        return self::$install;
    }
}