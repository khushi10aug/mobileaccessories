<?php defined('SYSTEM_INIT') or die('Invalid Usage');
$frm->developerTags['fld_default_col'] = 12;
$btn = $frm->getField('btn_submit');
if (null != $btn) {
    $btn->developerTags['noCaptionTag'] = true;
    $btn->setFieldTagAttribute('class', "btn btn-brand btn-wide");
    $btn->setFieldTagAttribute('onclick', "razorpaySubmit(this);");
}
if (!isset($error)) { ?>
    <div class="text-center">
        <p><?php echo Labels::getLabel('LBL_PROCEED_TO_PAYMENT_?', $siteLangId); ?></p>
        <?php echo $frm->getFormHtml(); ?>
    </div>
<?php
} else { ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php }

if (!FatUtility::isAjaxCall()) { ?>
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
            // Get form and payment ID field BEFORE modifying DOM
            var paymentIdField = document.getElementById('razorpay_payment_id');
            var form = document.getElementById('razorpay-form');
            
            if (!paymentIdField || !form) {
                console.error('Razorpay form elements not found');
                return;
            }
            
            // Set payment ID value
            paymentIdField.value = transaction.razorpay_payment_id;
            
            // Show loader and processing message (hide form, show loader)
            var formContainer = document.querySelector('.text-center');
            if (formContainer) {
                // Hide the form but keep it in DOM
                var formElement = formContainer.querySelector('form');
                if (formElement) {
                    formElement.style.display = 'none';
                }
                
                // Add loader overlay
                var loaderHtml = '<div class="razorpay-loader-overlay" style="padding: 40px; position: relative; z-index: 10;"><div class="spinner spinner--sm spinner--brand" style="margin: 0 auto 20px;"></div><p style="font-size: 16px; color: #333;">' + (typeof langLbl !== 'undefined' && langLbl.waitingForResponse ? langLbl.waitingForResponse : 'Processing your payment. Please wait...') + '</p><p style="font-size: 14px; color: #666; margin-top: 10px;">' + (typeof langLbl !== 'undefined' && langLbl.dontReloadPageWhilePayment ? langLbl.dontReloadPageWhilePayment : 'Do not reload or close this page.') + '</p></div>';
                formContainer.insertAdjacentHTML('afterbegin', loaderHtml);
            }
            
            // Disable form submission button if exists
            var submitBtn = document.querySelector('input[type="submit"], button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
            }
            
            // Submit the form
            form.submit();
        }
    };
    var razorpay_submit_btn, razorpay_instance;

    function razorpaySubmit(el) {
        if (typeof Razorpay == 'undefined') {
            setTimeout(razorpaySubmit, 200);
            if (!razorpay_submit_btn && el) {
                razorpay_submit_btn = el;
                el.disabled = true;
                el.value = 'Please wait...';
            }
        } else {
            if (!razorpay_instance) {
                razorpay_instance = new Razorpay(razorpay_options);
                if (razorpay_submit_btn) {
                    razorpay_submit_btn.disabled = false;
                    razorpay_submit_btn.value = $(el).data('value');
                }
            }
            razorpay_instance.open();
        }
    }
</script>