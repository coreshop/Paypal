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

use CoreShop\Controller\Action\Payment;
use CoreShop\Tool;

/**
 * Class Paypal_PaymentController
 */
class Paypal_PaymentController extends Payment
{
    /**
     * @var \PayPal\Rest\ApiContext
     */
    protected $apiContext = null;

    public function init()
    {
        parent::init();

        $this->apiContext = new \PayPal\Rest\ApiContext(
            new \PayPal\Auth\OAuthTokenCredential(
                \CoreShop\Model\Configuration::get("PAYPAL.CLIENTID"),
                \CoreShop\Model\Configuration::get("PAYPAL.CLIENTSECRET")
            )
        );
        // Comment this line out and uncomment the PP_CONFIG_PATH
        // 'define' block if you want to use static file
        // based configuration
        $this->apiContext->setConfig(
            array(
                'mode' => \CoreShop\Model\Configuration::get("PAYPAL.MODE"),
            )
        );
    }

    public function paymentAction()
    {
        $identifier = uniqid();

        $payer = new \PayPal\Api\Payer();
        $payer->setPaymentMethod("paypal");

        $itemList = new \PayPal\Api\ItemList();

        foreach ($this->cart->getItems() as $item) {
            $pItem = new \PayPal\Api\Item();
            $pItem->setName($item->getProduct()->getName());
            $pItem->setCurrency(Tool::getCurrency()->getIsoCode());
            $pItem->setSku($item->getProduct()->getArticleNumber());

            $pItem->setPrice($item->getProduct()->getPrice(false));
            $pItem->setTax($item->getProduct()->getTaxAmount());
            $pItem->setQuantity($item->getAmount());

            $itemList->addItem($pItem);
        }

        $details = new \PayPal\Api\Details();
        $details->setShipping($this->cart->getShipping(false));
        $details->setSubtotal($this->cart->getSubtotal(false));
        $details->setTax($this->cart->getTotalTax());

        $amount = new \PayPal\Api\Amount();
        $amount->setCurrency(Tool::getCurrency()->getIsoCode());
        $amount->setTotal($this->cart->getTotal());
        $amount->setDetails($details);

        $transaction = new \PayPal\Api\Transaction();
        $transaction->setAmount($amount);
        $transaction->setItemList($itemList);
        $transaction->setInvoiceNumber($identifier);

        $this->cart->setCustomIdentifier($identifier);
        $this->cart->save();

        $redirectUrls = new \PayPal\Api\RedirectUrls();
        $redirectUrls->setReturnUrl(Pimcore\Tool::getHostUrl() . $this->getModule()->url($this->getModule()->getIdentifier(), "payment-return"));
        $redirectUrls->setCancelUrl(Pimcore\Tool::getHostUrl() . $this->getModule()->url($this->getModule()->getIdentifier(), "payment-return-abort"));

        $payment = new \PayPal\Api\Payment();
        $payment->setIntent("sale");
        $payment->setPayer($payer);
        $payment->setRedirectUrls($redirectUrls);
        $payment->setTransactions(array($transaction));

        try {
            $payment->create($this->apiContext);
        } catch (\PayPal\Exception\PayPalConnectionException $ex) {
            echo '<pre>';print_r(json_decode($ex->getData()));exit;
        } catch(\Exception $ex) {
            die($ex);
        }

        $this->redirect($payment->getApprovalLink());
    }

    public function paymentReturnAction()
    {
        $paymentId = $_GET['paymentId'];
        $payment = \PayPal\Api\Payment::get($paymentId, $this->apiContext);

        $execution = new \PayPal\Api\PaymentExecution();
        $execution->setPayerId($_GET['PayerID']);

        try {
            $payment->execute($execution, $this->apiContext);

            try {
                $payment = \PayPal\Api\Payment::get($paymentId, $this->apiContext);
                
                $order = $this->cart->createOrder(\CoreShop\Model\Order\State::getById(\CoreShop\Model\Configuration::get("SYSTEM.ORDERSTATE.PAYMENT")), $this->getModule(), $this->cart->getTotal(), $this->view->language);

                $payments = $order->getPayments();

                foreach ($payments as $p) {
                    $dataBrick = new \Pimcore\Model\Object\Objectbrick\Data\CoreShopPaymentPaypal($p);

                    $dataBrick->setTransactionId($payment->getId());
                    $dataBrick->setStatus($payment->getState());

                    $p->save();
                }

                $this->redirect($this->getModule()->getConfirmationUrl($order));
            } catch (Exception $ex) {
                die($ex);
            }
        } catch (\Exception $ex) {
            die($ex);
        }

        $this->redirect($this->view->url(array(), "coreshop_index"));
    }

    public function paymentReturnAbortAction()
    {
        $this->redirect($this->view->url(array(), "coreshop_index"));
    }

    public function confirmationAction()
    {
        $orderId = $this->getParam("order");

        if ($orderId) {
            $order = \CoreShop\Model\Order::getById($orderId);

            if ($order instanceof \CoreShop\Model\Order) {
                $this->session->order = $order;
            }
        }

        parent::confirmationAction();
    }

    /**
     * @return Paypal\Shop
     */
    public function getModule()
    {
        return parent::getModule();
    }
}
