<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

$canUseWallet = (0 < count($paymentMethods) || ($userWalletBalance >= $cartSummary['orderNetAmount']));
$noPaymentMethod = (1 > count($paymentMethods) && (!$canUseWalletForPayment || (1 > $userWalletBalance && $cartSummary['orderNetAmount'] > 0)));
?>
<div class="checkout-page">
    <main class="checkout-page_main">
        <div class="step">
            <div class="step_section">
                <div class="step_head">
                    <h5 class="step_title">
                        <a class="btn btn-back" href="<?php echo UrlHelper::generateUrl('seller', 'packages'); ?>">
                            <svg class="svg" width="24" height="24">
                                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#back">
                                </use>
                            </svg>
                        </a>
                        <?php echo Labels::getLabel('LBL_PAYMENT_SUMMARY', $siteLangId); ?>
                    </h5>
                </div>
                <div class="step_body">
                    <?php if (1 > count($paymentMethods)) { ?>
                        <div class="text-center">
                            <img class="block__img" src="<?php echo CONF_WEBROOT_URL; ?>images/retina/no-payment-methods.svg" alt="<?php echo Labels::getLabel('LBL_NO_PAYMENT_METHOD_FOUND', $siteLangId); ?>">
                            <h3><?php echo Labels::getLabel('ERR_PAYMENT_METHOD_IS_NOT_AVAILABLE.', $siteLangId); ?></h3>
                            <p><?php echo Labels::getLabel('ERR_PLEASE_CONTACT_YOUR_ADMINISTRATOR.', $siteLangId); ?></p>
                        </div>
                    <?php } else { ?>
                        <div id="payment">
                            <?php
                            $gatewayCount = 0;
                            foreach ($paymentMethods as $key => $val) {
                                if (in_array($val['plugin_code'], $excludePaymentGatewaysArr[applicationConstants::CHECKOUT_SUBSCRIPTION])) {
                                    unset($paymentMethods[$key]);
                                    continue;
                                }
                                $gatewayCount++;
                            } ?>
                            <div class="payment-area" <?php echo ($cartSummary['orderPaymentGatewayCharges'] <= 0) ? 'is--disabled' : ''; ?>>
                                <?php if ($cartSummary['orderNetAmount'] <= 0) { ?>
                                    <div class="wallet-payment confirm-payment" id="wallet">
                                        <h4 class="h4">
                                            <?php echo Labels::getLabel('LBL_PAYMENT_TO_BE_MADE', $siteLangId); ?>:&nbsp;
                                            <strong><span class="currency-value" dir="ltr">
                                                    <?php echo strip_tags(CommonHelper::displayMoneyFormat($cartSummary['orderNetAmount'], true, false, true, false, true)); ?>
                                            </strong>
                                        </h4>
                                        <?php
                                        $label = Labels::getLabel('LBL_PAYMENT_TO_BE_MADE', $siteLangId) . ' ' . strip_tags(CommonHelper::displayMoneyFormat($cartSummary['orderNetAmount'], true, false, true, false, true));
                                        $btnSubmitFld = $confirmPaymentFrm->getField('btn_submit');
                                        $btnSubmitFld->addFieldTagAttribute('class', 'btn btn-brand');
                                        $btnSubmitFld->developerTags['noCaptionTag'] = true;
                                        $confirmPaymentFrm->developerTags['colClassPrefix'] = 'col-md-';
                                        $confirmPaymentFrm->developerTags['fld_default_col'] = 12;
                                        echo $confirmPaymentFrm->getFormHtml(); ?>
                                    </div>
                                <?php } ?>

                                <?php if ($userWalletBalance > 0 && $cartSummary['orderNetAmount'] > 0 && $canUseWalletForPayment) { ?>
                                    <div class="wallet-payment">
                                        <div>
                                            <label class="checkbox wallet-credits">
                                                <?php if ($canUseWallet) { ?>
                                                    <input onchange="walletSelection(this)" type="checkbox" <?php echo ($cartSummary["cartWalletSelected"]) ? 'checked="checked"' : ''; ?> name="pay_from_wallet" id="pay_from_wallet" value="1">
                                                <?php } ?>
                                                <?php echo Labels::getLabel('LBL_WALLET_CREDITS:', $siteLangId); ?>&nbsp;
                                                <strong><?php echo CommonHelper::displayMoneyFormat($userWalletBalance, true, false, true, false, true); ?></strong>
                                            </label>
                                            <p class="wallet-payment-txt">
                                                <?php if ($canUseWallet) {
                                                    echo Labels::getLabel('LBL_USE_MY_WALLET_BALANCE_TO_PAY_FOR_MY_ORDER', $siteLangId);
                                                } else {
                                                    echo HtmlHelper::getErrorMessageHtml(Labels::getLabel('LBL_PAYMENT_CANNOT_BE_MADE_DUE_TO_A_LOW_BALANCE', $siteLangId));
                                                } ?>
                                            </p>
                                            <?php if ($subscriptionType == SellerPackages::PAID_TYPE) { ?>
                                                <p class="note">
                                                    <?php echo Labels::getLabel('LBL_NOTE_PLEASE_MAINTAIN_WALLET_BALANCE_FOR_FURTHER_AUTO_RENEWAL_PAYMENTS', $siteLangId); ?>
                                                </p>
                                            <?php } ?>
                                        </div>
                                        <?php if ($cartSummary["cartWalletSelected"] && $userWalletBalance >= $cartSummary['orderNetAmount']) {
                                            $btnSubmitFld = $walletPaymentForm->getField('btn_submit');
                                            $btnSubmitFld->addFieldTagAttribute('class', 'btn btn-brand btn-block');
                                            $btnSubmitFld->value = Labels::getLabel('LBL_PAY', $siteLangId) . ' ' . CommonHelper::displayMoneyFormat($cartSummary['orderNetAmount'], true, false, true, false, false);
                                            $walletPaymentForm->developerTags['colClassPrefix'] = 'col-md-';
                                            $walletPaymentForm->developerTags['fld_default_col'] = 12;
                                            echo $walletPaymentForm->getFormTag();
                                            echo $walletPaymentForm->getFieldHTML('order_id');
                                            echo $walletPaymentForm->getFieldHTML('btn_submit');
                                            echo $walletPaymentForm->getExternalJS();
                                            echo '</form>';
                                        ?>
                                            <script type="text/javascript">
                                                function confirmOrder(frm) {
                                                    var data = fcom.frmData(frm);
                                                    var action = $(frm).attr('action');
                                                    fcom.updateWithAjax(fcom.makeUrl('SubscriptionCheckout', 'confirmOrder'), data, function(ans) {
                                                        $(location).attr("href", action);
                                                    });
                                                }
                                            </script>
                                        <?php } ?>
                                    </div>
                                <?php } ?>

                                <?php if (0 < count($paymentMethods)) { ?>
                                    <ul class="payments-nav" id="payment_methods_tab">
                                        <?php
                                        $showFirstElement = '';
                                        foreach ($paymentMethods as $key => $val) {
                                            if (in_array($val['plugin_code'], $excludePaymentGatewaysArr[applicationConstants::CHECKOUT_SUBSCRIPTION])) {
                                                continue;
                                            }
                                            $pmethodCode = $val['plugin_code'];
                                            $pmethodId = $val['plugin_id'];
                                            $pmethodName = (isset($val['plugin_name']) && !empty($val['plugin_name'])) ? $val['plugin_name'] : $val['plugin_identifier'];
                                            if (in_array($pmethodCode, $excludePaymentGatewaysArr[applicationConstants::CHECKOUT_PRODUCT])) {
                                                continue;
                                            }
                                            $showFirstElement = empty($showFirstElement) && 0 < $cartSummary['orderPaymentGatewayCharges'] ? 'show' : ''; ?>
                                            <li class="payments-nav-item">
                                                <a class="payments-nav-link" aria-selected="true" href="<?php echo UrlHelper::generateUrl('SubscriptionCheckout', 'PaymentTab', array($orderInfo['order_id'], $pmethodId)); ?>" data-paymentmethod="<?php echo $pmethodCode; ?>" data-bs-toggle="collapse" data-bs-target="#<?php echo $pmethodCode; ?>-section" aria-expanded="true" aria-controls="<?php echo $pmethodCode; ?>-section">
                                                    <?php echo $pmethodName; ?>
                                                </a>

                                                <?php if (0 < $cartSummary['orderPaymentGatewayCharges']) { ?>
                                                    <div class="accordion-collapse <?php echo $showFirstElement; ?> collapse payment-block paymentBlockJs <?php echo $pmethodCode . '-js'; ?>" id="<?php echo $pmethodCode; ?>-section" aria-labelledby="headingOne" data-bs-parent="#payment_methods_tab"></div>
                                                <?php } ?>
                                            </li>
                                        <?php
                                        } ?>
                                    </ul>
                                <?php } else {
                                    echo Labels::getLabel("LBL_PAYMENT_METHOD_IS_NOT_AVAILABLE._PLEASE_CONTACT_YOUR_ADMINISTRATOR.", $siteLangId);
                                } ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </main>

    <aside class="checkout-page_aside">
        <div class="sticky-summary">
            <div id="order-summary" class="cart-total order-summary summary-listing-js">
                <?php include(CONF_INSTALLATION_PATH . 'application/views/cart/_partial/summary-skeleton.php'); ?>
            </div>
        </div>
    </aside>
</div>

<?php if ($cartSummary['orderPaymentGatewayCharges']) { ?>
    <script type="text/javascript">
        var tabsId = '#payment_methods_tab';
        $(function() {
            $(tabsId + " li:first a").addClass('active');
            if ($(tabsId + ' li a.active').length > 0) {
                loadTab($(tabsId + ' li a.active'));
            }
            $(tabsId + ' a').on('click', function() {
                if ($(this).hasClass('active')) {
                    return false;
                }
                $(tabsId + ' li a.active').removeClass('active');
                $(this).addClass('active');
                loadTab($(this));
                return false;
            });
        });

        function loadTab(tabObj) {
            if (!tabObj || !tabObj.length) {
                return;
            }
            var paymentMethod = tabObj.data('paymentmethod');
            var paymentMethodSection = $('.' + paymentMethod + '-js');
            paymentMethodSection.prepend(fcom.getLoader());
            fcom.updateWithAjax(tabObj.attr('href'), '', function(res) {
                if ('paypal' != paymentMethod.toLowerCase() && 0 < $("#paypal-buttons").length) {
                    $("#paypal-buttons").html("");
                }

                if (0 < paymentMethodSection.find('.paymentFormSection-js').length && paymentMethodSection.find('.paymentFormSection-js').hasClass('d-none')) {
                    paymentMethodSection.replaceWith(res.html);
                } else {
                    paymentMethodSection.html(res.html);
                }
                var form = '.' + paymentMethod + '-js .paymentFormSection-js form';
                if (0 < $(form).length) {
                    paymentMethodSection.prepend(fcom.getLoader());
                    if (0 < $(form + " input[type='submit']").length) {
                        $(form + " input[type='submit']").val(langLbl.requestProcessing);
                    }
                    setTimeout(function() {
                        $(form).submit()
                    }, 100);
                }
            });
        }
    </script>
<?php }
