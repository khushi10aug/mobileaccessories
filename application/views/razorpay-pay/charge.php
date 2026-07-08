<?php defined('SYSTEM_INIT') or die('Invalid Usage'); ?>
<section class="payment-section">
    <div class="payable-amount">

        <div class="payable-amount-head">
            <div class="payable-amount-logo">
                <?php $this->includeTemplate('_partial/paymentPageLogo.php', array('siteLangId' => $siteLangId)); ?>
            </div>
            <div class="payable-amount-total">
                <p> <span class="label"> <?php echo Labels::getLabel('LBL_Total_Payable', $siteLangId); ?>:</span>
                    <span class="value"> <?php echo CommonHelper::displayMoneyFormat($paymentAmount) ?> </span>
                </p>
                <p> <span class="label"> <?php echo Labels::getLabel('LBL_Order_Invoice', $siteLangId); ?>: </span>
                    <span class="value"><?php echo $orderInfo["invoice"]; ?></span>
                </p>
            </div>
        </div>
        <div class="payable-amount-body from-payment">
            <?php
            if (!isset($error)) :
            ?>
                <?php echo $frm->getFormTag(); ?>
                <div class="payable-form-body">
                    <div class="row">
                        <div class="col-md-12">
                            <p><?php echo Labels::getLabel('MSG_CONFIRM_TO_PROCEED_FOR_PAYMENT_?', $siteLangId); ?></p>
                            <?php echo $frm->getFieldHtml('razorpay_payment_id'); ?>
                            <?php echo $frm->getFieldHtml('merchant_order_id'); ?>
                        </div>
                        <div class="gap"></div>
                    </div>
                </div>
                <div class="payable-form-footer">
                    <div class="row">
                        <div class="col-6">
                            <?php if (FatUtility::isAjaxCall()) { ?>
                                <a href="javascript:void(0);" onclick="loadPaymentSummary()" class="btn btn-outline-brand  btn-block">
                                    <?php echo Labels::getLabel('LBL_Cancel', $siteLangId); ?>
                                </a>
                            <?php } else { ?>
                                <a href="<?php echo $cancelBtnUrl; ?>" class="btn btn-outline-gray  btn-block"><?php echo Labels::getLabel('LBL_Cancel', $siteLangId); ?></a>
                            <?php } ?>
                        </div>
                        <div class="col-6">
                            <?php
                            $btn = $frm->getField('btn_submit');
                            $btn->addFieldTagAttribute('onclick', 'razorpaySubmit(this)');
                            $btn->addFieldTagAttribute('class', 'btn btn-brand  btn-block');
                            $btn->addFieldTagAttribute('data-processing-text', Labels::getLabel('LBL_PLEASE_WAIT..', $siteLangId));
                            echo $frm->getFieldHtml('btn_submit');
                            ?>
                        </div>
                    </div>
                </div>
                </form>
            <?php else : ?>
                <div class="alert alert-danger"><?php echo $error ?></div>
            <?php endif; ?>
            <?php if (CommonHelper::getCurrencyId() != FatApp::getConfig('CONF_CURRENCY', FatUtility::VAR_INT, 1)) { ?>
                <p class="form-text text-muted mt-4"><?php echo CommonHelper::currencyDisclaimer($siteLangId, $paymentAmount); ?> </p>
            <?php } ?>
        </div>
    </div>
</section>
<?php include(CONF_THEME_PATH . '_partial/footer-part/fonts.php'); ?>
<?php if (!FatUtility::isAjaxCall()) { ?>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<?php } ?>
<script>
    var razorpay_options = {
        key: "<?php echo $paymentSettings['merchant_key_id']; ?>",
        amount: "<?php echo $paymentAmount * 100; ?>",
        name: "<?php echo $orderInfo["site_system_name"]; ?>",
        description: "<?php echo sprintf(Labels::getLabel('MSG_Order_Payment_Gateway_Description', $siteLangId), $orderInfo["site_system_name"], $orderInfo['invoice']) ?>",
        netbanking: true,
        currency: "<?php echo $systemCurrencyCode; ?>",
        prefill: {
            name: "<?php echo $orderInfo["customer_name"]; ?>",
            email: "<?php echo $orderInfo["customer_email"]; ?>",
            contact: "<?php echo $orderInfo["customer_phone"]; ?>"
        },
        notes: {
            system_order_id: "<?php echo $orderInfo["id"]; ?>"
        },
        handler: function(transaction) {
            var paymentId = transaction.razorpay_payment_id;
            var orderId = "<?php echo (int) $orderInfo['id']; ?>";
            var successUrl = <?php echo json_encode($paymentSuccessUrl); ?>;
            var callbackUrl = <?php echo json_encode($paymentCallbackUrl); ?>;

            /* Fire-and-forget server processing so user is not stuck on "Waiting..." */
            try {
                var body = new FormData();
                body.append('razorpay_payment_id', paymentId);
                body.append('merchant_order_id', orderId);
                body.append('async_process', '1');
                if (navigator.sendBeacon) {
                    navigator.sendBeacon(callbackUrl, body);
                } else {
                    fetch(callbackUrl, {
                        method: 'POST',
                        body: body,
                        credentials: 'same-origin',
                        keepalive: true
                    }).catch(function() {});
                }
            } catch (e) {
                /* If beacon fails, fall back to classic form submit */
                var paymentIdField = document.getElementById('razorpay_payment_id');
                var form = document.getElementById('razorpay-form');
                if (paymentIdField && form) {
                    paymentIdField.value = paymentId;
                    form.submit();
                    return;
                }
            }

            window.location.href = successUrl;
        }
    };
    var razorpay_submit_btn, razorpay_instance;

    function razorpaySubmit(el) {
        if (typeof Razorpay == 'undefined') {
            setTimeout(razorpaySubmit, 200);
            if (razorpay_submit_btn == 'undefined' && el) {
                razorpay_submit_btn = el;
                el.disabled = true;
                $(el).data('value', $(el).val());
                el.value = $(el).data('processing-text');

            }
        } else {
            if (!razorpay_instance) {
                razorpay_instance = new Razorpay(razorpay_options);
                if (razorpay_submit_btn) {
                    razorpay_submit_btn.disabled = false;
                    razorpay_submit_btn.value = $(el).data('value')
                }
            }
            razorpay_instance.open();
        }
    }
</script>