<div class="container shop checkout checkout-step-5">
    <?=$this->partial("coreshop/helper/order-steps.php", array("step" => 5));?>

    <p>
        <?php
        echo sprintf($this->translate("Your Order has been submitted. You have choosen to by with Paypal. Your order is now preparing. Your Order Number is %s"), $this->order->getOrderNumber())
        ?>
    </p>
</div>