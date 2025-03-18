<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$rewardsAmtCanBeUsed = 0;
$rewardsCurrAmtCanBeUsed = 0;
if ($rewardPointBalance > 0) {
    $cartTotal = $cartSummary['cartTotal'] ?? 0;
    $cartDiscounts = $cartSummary['cartDiscounts']["coupon_discount_total"] ?? 0;
    $rewardsAmtCanBeUsed = min(min($rewardPointBalance, CommonHelper::convertCurrencyToRewardPoint($cartTotal - $cartSummary['cartVolumeDiscount'] - $cartDiscounts)), FatApp::getConfig('CONF_MAX_REWARD_POINT', FatUtility::VAR_INT, 0));
    $rewardsCurrAmtCanBeUsed = CommonHelper::convertRewardPointToCurrency($rewardsAmtCanBeUsed);
    $inCurrency = CommonHelper::displayMoneyFormat($rewardsCurrAmtCanBeUsed);
}

$canUseWalletOrRewards = (0 < count($paymentMethods) || (($rewardsCurrAmtCanBeUsed + $userWalletBalance) >= $cartSummary['orderNetAmount']));
$noPaymentMethod = (1 > count($paymentMethods) && (!$canUseWalletForPayment || (1 > $userWalletBalance && $cartSummary['orderNetAmount'] > 0)));

if ($noPaymentMethod && $rewardsCurrAmtCanBeUsed < $cartSummary['orderNetAmount']) { ?>
    <div class="text-center">
        <img class="block__img" src="<?php echo CONF_WEBROOT_URL; ?>images/retina/no-payment-methods.svg" alt="<?php echo Labels::getLabel('LBL_NO_PAYMENT_METHOD_FOUND', $siteLangId); ?>">
        <h3><?php echo Labels::getLabel('ERR_PAYMENT_METHOD_IS_NOT_AVAILABLE.', $siteLangId); ?></h3>
        <p><?php echo Labels::getLabel('ERR_PLEASE_CONTACT_YOUR_ADMINISTRATOR.', $siteLangId); ?></p>
    </div>
<?php } else { ?>
    <div class="step">
        <span class="d-none">
            <?php if (isset($opComments) && !empty($opComments)) { 
                foreach ($opComments as $opSelProdId => $comment) { ?>
                    <textarea maxlength="250" class="d-none opCommentsJs" name="op_comments[<?php echo $opSelProdId; ?>]"><?php echo $comment; ?></textarea>
                <?php } ?>
            <?php }?>
        </span>
        <?php if ($fulfillmentType == Shipping::FULFILMENT_SHIP && $shippingAddressId == $billingAddressId) { ?>
            <div class="step_section">
                <div class="step_head">
                    <p><?php echo Labels::getLabel('LBL_MY_BILLING_IS_SAME_AS_SHIPPING_ADDRESS', $siteLangId); ?></p>
                    <button class="btn btn-outline-gray" type="button" onClick="loadAddressDiv(1);"><?php echo Labels::getLabel('LBL_CHANGE_ADDRESS_?') ?></button>
                </div>
            </div>
        <?php } else { ?>
            <ul class="review-block">
                <li class="review-block-item">
                    <div class="review-block-head">
                        <h5 class="h5">
                            <?php echo Labels::getLabel('LBL_Billing_to:', $siteLangId); ?>
                        </h5>

                        <div class="review-block-action" role="cell">
                            <button class="link-underline" onClick="loadAddressDiv(1)">
                                <span>
                                    <?php echo Labels::getLabel('LBL_Edit', $siteLangId); ?>
                                </span>
                            </button>
                        </div>
                    </div>
                    <div class="review-block-body" role="cell">
                        <address class="address delivery-address">
                            <p><?php echo $billingAddressArr['addr_name'] . ', ' . $billingAddressArr['addr_address1']; ?>
                                <?php if (strlen((string)$billingAddressArr['addr_address2']) > 0) {
                                    echo ", " . $billingAddressArr['addr_address2']; ?>
                                <?php } ?>
                            </p>
                            <p><?php echo $billingAddressArr['addr_city'] . ", " . $billingAddressArr['state_name'] . ", " . $billingAddressArr['country_name'] . ", " . $billingAddressArr['addr_zip']; ?>
                            </p>

                            <?php if (strlen((string)$billingAddressArr['addr_phone']) > 0) {
                                $addrPhone = ValidateElement::formatDialCode($billingAddressArr['addr_phone_dcode']) . $billingAddressArr['addr_phone'];
                            ?>
                                <ul class="phone-list">
                                    <li class="phone-list-item phone-txt">
                                        <svg class="svg" width="20" height="20">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#mobile-alt">
                                            </use>
                                        </svg>
                                        <span class="default-ltr"><?php echo $addrPhone; ?></span>
                                    </li>
                                </ul>
                            <?php } ?>
                        </address>
                    </div>
                </li>
            </ul>
        <?php } ?>

        <?php if ($rewardPointBalance > 0 || 0 < $cartSummary['cartRewardPoints']) { ?>
            <div class="step_section">
                <div class="step_head">
                    <h5 class="step_title"><?php echo Labels::getLabel('LBL_REWARD_POINTS', $siteLangId); ?></h5>
                </div>
                <div class="step_body">
                    <?php
                    if (empty($cartSummary['cartRewardPoints'])) {
                        $redeemRewardFrm->setFormTagAttribute('class', 'form form-apply');
                        $redeemRewardFrm->setFormTagAttribute('onsubmit', 'useRewardPoints(this); return false;');
                        $redeemRewardFrm->setJsErrorDisplay('afterfield');
                        $fld = $redeemRewardFrm->getField('redeem_rewards');
                        if (false === $canUseWalletOrRewards) {
                            $fld->setFieldTagAttribute('disabled', 'disabled');
                        }
                        $fld->setFieldTagAttribute('class', 'form-control');
                        $fld->setFieldTagAttribute('placeholder', Labels::getLabel('LBL_Use_Reward_Point', $siteLangId));

                        echo $redeemRewardFrm->getFormTag(); ?>
                        <?php echo $redeemRewardFrm->getFieldHtml('redeem_rewards'); ?>
                        <?php echo $redeemRewardFrm->getFieldHtml('btn_submit'); ?>
                        </form>
                        <?php echo $redeemRewardFrm->getExternalJs(); ?>

                        <p class="txt-sm">
                            <?php $str = Labels::getLabel('LBL_MAXIMUM_{REWARDS}_({REWARD-CURRENCY-AMOUNT})_OUT_OF_{AVAILABLE-REWARDS}_REWARD_POINTS_CAN_BE_REDEEMED_FOR_THIS_ORDER.', $siteLangId);
                            echo CommonHelper::replaceStringData($str, ['{REWARDS}' => '<b>' . $rewardsAmtCanBeUsed . '</b>', '{REWARD-CURRENCY-AMOUNT}' => $inCurrency, '{AVAILABLE-REWARDS}' => '<b>' . $rewardPointBalance . '</b>']); ?>
                        </p>
                    <?php } else { ?>
                        <div class="info">
                            <span>
                                <svg class="svg">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#reward-points">
                                    </use>
                                </svg> <?php echo Labels::getLabel('LBL_REWARD_POINTS', $siteLangId); ?>
                                <strong><?php echo $cartSummary['cartRewardPoints']; ?>
                                    (<?php echo CommonHelper::displayMoneyFormat(CommonHelper::convertRewardPointToCurrency($cartSummary['cartRewardPoints']), true, false, true, false, true); ?>)</strong>
                                <?php echo Labels::getLabel('LBL_SUCCESSFULLY_USED', $siteLangId); ?>
                            </span>
                            <button type="button" class="btn-close text-reset" onclick="removeRewardPoints()" aria-label="Close"></button>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>

        <div class="step_section">
            <div class="step_head">
                <h5 class="step_title"><?php echo Labels::getLabel('LBL_PAYMENT_SUMMARY', $siteLangId); ?></h5>
            </div>
            <div class="step_body">
                <?php if ($noPaymentMethod) { ?>
                    <div class="text-center">
                        <img class="block__img" src="<?php echo CONF_WEBROOT_URL; ?>images/retina/no-payment-methods.svg" alt="<?php echo Labels::getLabel('LBL_NO_PAYMENT_METHOD_FOUND', $siteLangId); ?>">
                        <h3><?php echo Labels::getLabel('ERR_PAYMENT_METHOD_IS_NOT_AVAILABLE.', $siteLangId); ?></h3>
                        <p><?php echo Labels::getLabel('ERR_PLEASE_CONTACT_YOUR_ADMINISTRATOR.', $siteLangId); ?></p>
                    </div>
                <?php } else { ?>
                    <div id="payment">
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
                                    $btnSubmitFld = $confirmForm->getField('btn_submit');
                                    $btnSubmitFld->addFieldTagAttribute('class', 'btn btn-brand');
                                    $btnSubmitFld->developerTags['noCaptionTag'] = true;
                                    $confirmForm->developerTags['colClassPrefix'] = 'col-md-';
                                    $confirmForm->developerTags['fld_default_col'] = 12;
                                    echo $confirmForm->getFormHtml(); ?>
                                </div>
                            <?php } ?>

                            <?php if ($userWalletBalance > 0 && $cartSummary['orderNetAmount'] > 0 && $canUseWalletForPayment) { ?>
                                <div class="wallet-payment">
                                    <div>
                                        <label class="checkbox wallet-credits">
                                            <?php if ($cartSummary["cartWalletSelected"] || $canUseWalletOrRewards) { ?>
                                                <input onchange="walletSelection(this)" type="checkbox" <?php echo ($cartSummary["cartWalletSelected"]) ? 'checked="checked"' : ''; ?> name="pay_from_wallet" id="pay_from_wallet" value="1">
                                            <?php } ?>
                                            <?php echo Labels::getLabel('LBL_WALLET_CREDITS:', $siteLangId); ?>&nbsp;
                                            <strong><?php echo CommonHelper::displayMoneyFormat($userWalletBalance, true, false, true, false, true); ?></strong>
                                        </label>
                                        <p class="wallet-payment-txt">
                                            <?php if ($canUseWalletOrRewards) {
                                                echo Labels::getLabel('LBL_USE_MY_WALLET_BALANCE_TO_PAY_FOR_MY_ORDER', $siteLangId);
                                            } else {
                                                echo HtmlHelper::getErrorMessageHtml(Labels::getLabel('LBL_PAYMENT_CANNOT_BE_MADE_DUE_TO_A_LOW_BALANCE', $siteLangId));
                                            } ?>
                                        </p>
                                    </div>
                                    <?php if ($cartSummary["cartWalletSelected"] && $userWalletBalance >= $cartSummary['orderNetAmount']) {
                                        $btnSubmitFld = $walletPaymentForm->getField('btn_submit');
                                        $btnSubmitFld->addFieldTagAttribute('class', 'btn btn-brand btn-wide');
                                        $btnSubmitFld->value = Labels::getLabel('LBL_PAY', $siteLangId) . ' ' . CommonHelper::displayMoneyFormat($cartSummary['orderNetAmount'], true, false, true, false, false);
                                        $walletPaymentForm->developerTags['colClassPrefix'] = 'col-md-';
                                        $walletPaymentForm->developerTags['fld_default_col'] = 12;

                                        echo $walletPaymentForm->getFormTag();
                                        echo $walletPaymentForm->getFieldHTML('order_id');
                                        echo $walletPaymentForm->getFieldHTML('btn_submit');
                                        echo $walletPaymentForm->getExternalJS();
                                        echo '</form>';
                                    ?>
                                        <script>
                                            function confirmOrder(frm) {
                                                var data = fcom.frmData(frm);
                                                var action = $(frm).attr('action');
                                                $(frm.btn_submit).attr({
                                                    'disabled': 'disabled'
                                                });
                                                fcom.updateWithAjax(fcom.makeUrl('Checkout', 'confirmOrder'), data, function(ans) {
                                                    $(location).attr("href", action);
                                                    fcom.removeLoader();
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
                                    $i = 0;
                                    foreach ($paymentMethods as $key => $val) {
                                        $pmethodCode = $val['plugin_code'];
                                        if ($cartHasDigitalProduct && isset($pmethodCode) && in_array(strtolower($pmethodCode), ['cashondelivery', 'payatstore'])) {
                                            continue;
                                        }
                                        $pmethodId = $val['plugin_id'];
                                        $pmethodName = $val['plugin_name'];

                                        if (in_array($pmethodCode, $excludePaymentGatewaysArr[applicationConstants::CHECKOUT_PRODUCT])) {
                                            continue;
                                        }

                                        if (0 == $i && 0 < $cartSummary['orderPaymentGatewayCharges']) {
                                            $showFirstElement = 'show';
                                            $i++;
                                        } ?>
                                        <li class="payments-nav-item">
                                            <a class="payments-nav-link" aria-selected="true" href="<?php echo UrlHelper::generateUrl('Checkout', 'PaymentTab', array($orderId, $pmethodId)); ?>" data-order-type="<?php echo $orderType;?>" data-paymentmethod="<?php echo $pmethodCode; ?>" data-bs-toggle="collapse" data-bs-target="#<?php echo $pmethodCode; ?>-section" aria-expanded="true" aria-controls="<?php echo $pmethodCode; ?>-section">
                                                <?php echo $pmethodName; ?>
                                            </a>

                                            <?php if (0 < $cartSummary['orderPaymentGatewayCharges']) { ?>
                                                <div class="accordion-collapse <?php echo $showFirstElement; ?> collapse payment-block paymentBlockJs <?php echo $pmethodCode . '-js'; ?>" id="<?php echo $pmethodCode; ?>-section" aria-labelledby="headingOne" data-bs-parent="#payment_methods_tab"></div>
                                            <?php } ?>
                                        </li>
                                    <?php
                                        $showFirstElement = '';
                                    } ?>
                                </ul>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <script>
        setTimeout(() => {
            fcom.removeLoader();
        }, 2000);
        var enableGcaptcha = false;
    </script>
    <?php
    $siteKey = FatApp::getConfig('CONF_RECAPTCHA_SITEKEY', FatUtility::VAR_STRING, '');
    $secretKey = FatApp::getConfig('CONF_RECAPTCHA_SECRETKEY', FatUtility::VAR_STRING, '');
    $paymentMethods = new PaymentMethods();
    if (!empty($siteKey) && !empty($secretKey) && true === $paymentMethods->cashOnDeliveryIsActive()) { ?>
        <script src='https://www.google.com/recaptcha/api.js?onload=googleCaptcha&render=<?php echo $siteKey; ?>'></script>
        <script>
            var enableGcaptcha = true;
        </script>
    <?php } ?>

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
                fcom.ajax(tabObj.attr('href'), 'order_type=' + tabObj.data('orderType'), function(res) {
                    if ('undefined' != res.status && 0 == res.status) {
                        paymentMethodSection.html(res.msg);
                        fcom.displayErrorMessage(res.msg);
                        return;
                    }

                    if ('paypal' != paymentMethod.toLowerCase() && 0 < $("#paypal-buttons").length) {
                        $("#paypal-buttons").html("");
                    }
                    if ('cashondelivery' == paymentMethod.toLowerCase() || 'payatstore' == paymentMethod.toLowerCase()) {
                        fcom.removeLoader();
                        paymentMethodSection.html(res.html);
                        if (true == enableGcaptcha) {
                            googleCaptcha();
                        }
                        $.ykmsg.close();
                    } else {
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
                    }
                }, { fOutMode: 'json' });
            }
        </script>
<?php }
}
