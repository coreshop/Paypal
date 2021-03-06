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
 * @copyright  Copyright (c) 2015-2017 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Paypal;

use CoreShop\Model\Cart;
use CoreShop\Model\Order;
use CoreShop\Model\Plugin\Payment as CorePayment;
use CoreShop\Plugin as CorePlugin;
use Paypal\Shop\Install;

/**
 * Class Shop
 * @package Paypal
 */
class Shop extends CorePayment
{
    public static $install;

    /**
     * @throws \Zend_EventManager_Exception_InvalidArgumentException
     */
    public function attachEvents()
    {
        self::getInstall()->attachEvents();

        \Pimcore::getEventManager()->attach("coreshop.payment.getProvider", function ($e) {
            return $this;
        });
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "Paypal";
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return "";
    }

    /**
     * @return string
     */
    public function getImage()
    {
        return "/plugins/Paypal/static/img/paypal.jpg";
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return "Paypal";
    }

    /**
     * Get Payment Fee
     *
     * @param Cart $cart
     * @param boolean $useTaxes Use Taxes?
     * @return float
     */
    public function getPaymentFee(Cart $cart, $useTaxes = true)
    {
        return 0;
    }

    /**
     * Process Validation for Payment
     *
     * @param Cart $cart
     * @return mixed
     */
    public function process(Cart $cart)
    {
        return $this->getProcessValidationUrl();
    }

    /**
     * Get url for confirmation link
     *
     * @param Order $order
     * @return string
     */
    public function getConfirmationUrl($order)
    {
        return $this->url($this->getIdentifier(), 'confirmation', array('order' => $order->getId()));
    }

    /**
     * get url for validation link
     *
     * @return string
     */
    public function getProcessValidationUrl()
    {
        return $this->url($this->getIdentifier(), 'validate');
    }

    /**
     * get url payment link
     *
     * @return string
     */
    public function getPaymentUrl()
    {
        return $this->url($this->getIdentifier(), 'payment');
    }

    /**
     * get error url
     * @param $errorMessage
     *
     * @return string
     */
    public function getErrorUrl($errorMessage = '')
    {
        return $this->url($this->getIdentifier(), 'error', ['error' => $errorMessage]);
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