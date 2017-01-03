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

use CoreShop\Controller\Action\Payment;

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
                \CoreShop\Model\Configuration::get('PAYPAL.CLIENTID'),
                \CoreShop\Model\Configuration::get('PAYPAL.CLIENTSECRET')
            )
        );
        // Comment this line out and uncomment the PP_CONFIG_PATH
        // 'define' block if you want to use static file
        // based configuration
        $this->apiContext->setConfig(
            array(
                'mode' => \CoreShop\Model\Configuration::get('PAYPAL.MODE'),
            )
        );
    }

    public function paymentAction()
    {
        $order = NULL;

        try {
            $order = $this->createOrder($this->view->language);

            $params = [
                'newState'      => \CoreShop\Model\Order\State::STATE_PENDING_PAYMENT,
                'newStatus'     => \CoreShop\Model\Order\State::STATUS_PENDING_PAYMENT,
            ];
            \CoreShop\Model\Order\State::changeOrderState($order, $params);
        } catch(\Exception $e) {
            \Pimcore\Logger::error('PayPal Gateway Error: Error on OrderChange: ' . $e->getMessage());
            $this->redirect($this->getModule()->getErrorUrl($e->getMessage()));
        }

        $identifier = 'order_' . $order->getId();

        $payer = new \PayPal\Api\Payer();
        $payer->setPaymentMethod('paypal');

        $itemList = new \PayPal\Api\ItemList();

        foreach ($this->cart->getItems() as $item) {
            $pItem = new \PayPal\Api\Item();
            $pItem->setName($item->getProduct()->getName());
            $pItem->setCurrency(\CoreShop::getTools()->getCurrency()->getIsoCode());
            $pItem->setSku($item->getProduct()->getArticleNumber());
            $pItem->setPrice($item->getProductPrice(false));
            $pItem->setTax($item->getProductTaxAmount());
            $pItem->setQuantity($item->getAmount());

            $itemList->addItem($pItem);
        }

        $details = new \PayPal\Api\Details();
        $details->setShipping($this->cart->getShipping(false));
        $details->setSubtotal($this->cart->getSubtotal(false));
        $details->setTax($this->cart->getTotalTax());

        $amount = new \PayPal\Api\Amount();
        $amount->setCurrency(\CoreShop::getTools()->getCurrency()->getIsoCode());
        $amount->setTotal($this->cart->getTotal());
        $amount->setDetails($details);

        $transaction = new \PayPal\Api\Transaction();
        $transaction->setAmount($amount);
        $transaction->setItemList($itemList);
        $transaction->setInvoiceNumber($identifier);

        $redirectUrls = new \PayPal\Api\RedirectUrls();
        $redirectUrls->setReturnUrl(Pimcore\Tool::getHostUrl() . $this->getModule()->url($this->getModule()->getIdentifier(), 'payment-return'));
        $redirectUrls->setCancelUrl(Pimcore\Tool::getHostUrl() . $this->getModule()->url($this->getModule()->getIdentifier(), 'payment-return-abort'));

        $payment = new \PayPal\Api\Payment();
        $payment->setIntent('sale');
        $payment->setPayer($payer);
        $payment->setRedirectUrls($redirectUrls);
        $payment->setTransactions(array($transaction));

        try {
            $payment->create($this->apiContext);

        } catch (\PayPal\Exception\PayPalConnectionException $ex) {
            \Pimcore\Logger::error('PayPal Gateway Error: Error on Order creation. Message: ' . $ex->getData());
            $this->redirect(Pimcore\Tool::getHostUrl() . $this->getModule()->getErrorUrl($ex->getData()));
        } catch(\Exception $ex) {
            \Pimcore\Logger::error('PayPal Gateway Error: Error on Order creation. Message: ' . $ex->getMessage());
            $this->redirect(Pimcore\Tool::getHostUrl() . $this->getModule()->getErrorUrl($ex->getMessage()));
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

            try {
                $payment->execute($execution, $this->apiContext);
            } catch(\Exception $e) {
                $this->redirect($this->getModule()->getErrorUrl('Paypal execution error: ' . $e->getMessage()));
            }

            $transactions = $payment->getTransactions();

            $orderId = str_replace('order_', '', $transactions[0]->getInvoicenumber());

            $order = \CoreShop\Model\Order::getById($orderId);

            if ($order instanceof \CoreShop\Model\Order) {

                try {
                    $state = \CoreShop\Model\Order\State::STATE_CANCELED;
                    $status = \CoreShop\Model\Order\State::STATUS_CANCELED;
                    $message = '-';
                    $createInvoice = false;

                    switch($payment->getState()) {
                        case 'created':
                            $state = \CoreShop\Model\Order\State::STATE_PENDING_PAYMENT;
                            $status = \CoreShop\Model\Order\State::STATE_PENDING_PAYMENT;
                            break;
                        case 'approved':
                            $state = \CoreShop\Model\Order\State::STATE_PROCESSING;
                            $status = \CoreShop\Model\Order\State::STATUS_PROCESSING;
                            $createInvoice = true;
                            break;
                        case 'failed':
                            $state = \CoreShop\Model\Order\State::STATE_CANCELED;
                            $status = \CoreShop\Model\Order\State::STATUS_CANCELED;
                            $message = $payment->getFailureReason();
                            break;
                    }

                    //get transaction
                    $this->getOrderPayment(
                        $order,
                        $paymentId
                    )->addTransactionNote(
                        $paymentId,
                        $payment->getState(),
                        'State: ' . $payment->getState() . '. Message: ' . $message);

                    $params = [
                        'newState'      => $state,
                        'newStatus'     => $status,
                    ];

                    try {
                        \CoreShop\Model\Order\State::changeOrderState($order, $params);
                    } catch(\Exception $e) {
                        // fail silently.
                    }

                    if ($createInvoice) {
                        try {
                            $order->createInvoiceForAllItems();
                        } catch(\Exception $e) {
                            // fail silently.
                        }
                    }

                    /* @fixme!
                    $payments = $order->getPayments();
                    foreach ($payments as $p) {
                    $dataBrick = new \Pimcore\Model\Object\Objectbrick\Data\CoreShopPaymentPaypal($p);

                    $dataBrick->setTransactionId($payment->getId());
                    $dataBrick->setStatus($payment->getState());

                    $p->save();
                    }
                    */

                    $this->redirect($this->getModule()->getConfirmationUrl($order));
                } catch (Exception $ex) {
                    $this->redirect($this->getModule()->getErrorUrl($ex->getMessage()));
                }

            } else {
                $this->redirect($this->getModule()->getErrorUrl('PayPal Gateway Error: Order with id "' . $orderId . '"" not found.'));
            }

        } catch (\Exception $ex) {
            $this->redirect($this->getModule()->getErrorUrl($ex->getMessage()));
        }

        $this->redirect(\CoreShop::getTools()->url(array('lang' => $this->view->language), 'coreshop_index') );
    }

    public function paymentReturnAbortAction()
    {
        $this->redirect($this->getModule()->getErrorUrl('Paypal payment has been canceled.' ));
    }

    public function confirmationAction()
    {
        $orderId = $this->getParam('order');

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