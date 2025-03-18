<?php defined('SYSTEM_INIT') or die('Invalid Usage.');


?>


<main class="main">
    <div class="container">
        <?php $this->includeTemplate('_partial/header/header-breadcrumb.php', [], false); ?>
        <div class="row">
            <div class="col-md-9">
                <div class="card">
                    <div class="card-head">
                        <div class="card-head-label">
                            <h3 class="card-head-title">
                                <a class="btn-back" href="<?php echo UrlHelper::generateUrl('GiftCardOrders'); ?>">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#back">
                                        </use>
                                    </svg>
                                </a>
                                <?php
                                $str = Labels::getLabel('LBL_ORDER_#{ORDER-NUMBER}', $siteLangId);
                                echo CommonHelper::replaceStringData($str, ['{ORDER-NUMBER}' => $order['order_number']])
                                ?>
                            </h3>
                        </div>
                    </div>

                </div>
                <?php
                $paymentFormCond = (!$order["order_payment_status"] && $canEdit && 'CashOnDelivery' != $order['plugin_code']);
                $paymentHistory = (!empty($order['payments']));
                if (!$order['order_deleted'] && ($paymentFormCond || $paymentHistory)) { ?>
                    <div class="card">
                        <div class="card-head">
                            <div class="card-head-label">
                                <h3 class="card-head-title"><?php echo Labels::getLabel('LBL_ORDER_PAYMENTS', $siteLangId); ?></h3>
                            </div>
                        </div>

                        <div class="card-table">
                            <?php require_once(CONF_THEME_PATH . 'gift-card-orders/payment-history.php'); ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
            <div class="col-md-3">

                <?php if (!empty($order['buyer_user_name']) || !empty($order['buyer_email'])) { ?>
                    <div class="card">
                        <div class="card-head">
                            <div class="card-head-label">
                                <h3 class="card-head-title">
                                    <?php echo Labels::getLabel('LBL_CONTACT_INFORMATION', $siteLangId); ?>
                                </h3>
                            </div>

                        </div>
                        <div class="card-body">
                            <ul class="list-stats">
                                <?php if (!empty($order['buyer_user_name'])) { ?>
                                    <li class="list-stats-item">
                                        <span class="lable"><?php echo Labels::getLabel('LBL_Customer_Name', $siteLangId); ?>:</span>
                                        <span class="value"><?php echo $order['buyer_user_name']; ?></span>
                                    </li>
                                <?php } ?>
                                <?php if (!empty($order['buyer_email'])) { ?>
                                    <li class="list-stats-item">
                                        <span class="lable"><?php echo Labels::getLabel('LBL_EMAIL', $siteLangId); ?>:</span>
                                        <span class="value"><?php echo $order['buyer_email']; ?></span>
                                    </li>
                                <?php } ?>
                                <?php if (!empty($order['buyer_phone'])) { ?>
                                    <li class="list-stats-item">
                                        <span class="lable"><?php echo Labels::getLabel('LBL_Phone', $siteLangId); ?>:</span>
                                        <span class="value"><?php echo ValidateElement::formatDialCode($order['buyer_phone_dcode']) . $order['buyer_phone']; ?></span>
                                    </li>
                                <?php } ?>
                            </ul>

                        </div>
                    </div>
                <?php } ?>
                <div class="card">
                    <div class="card-head">
                        <div class="card-head-label">
                            <h3 class="card-head-title"><i class="fas fa-credit-card"></i>
                                <?php echo Labels::getLabel('LBL_PAYMENT_INFORMATION', $siteLangId); ?>
                            </h3>
                        </div>

                    </div>
                    <div class="card-body">
                        <ul class="list-stats">
                            <li class="list-stats-item">
                                <span class="lable"><?php echo Labels::getLabel('LBL_PAYMENT_STATUS', $siteLangId); ?>:</span>
                                <span class="value"><?php echo Orders::getPaymentStatusHtml($siteLangId, $order['order_payment_status']);; ?></span>
                            </li>
                            <li class="list-stats-item">
                                <span class="lable"><?php echo Labels::getLabel('LBL_PAYMENT_MODE', $siteLangId); ?>:</span>
                                <span class="value"><?php echo $paymentMethodName; ?></span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card">
                    <div class="card-head">
                        <div class="card-head-label">
                            <h3 class="card-head-title">
                                <?php echo Labels::getLabel('LBL_RECEIVER_INFORMATION', $siteLangId); ?>
                            </h3>
                        </div>

                    </div>
                    <div class="card-body">
                        <ul class="list-stats">
                            <li class="list-stats-item">
                                <span class="lable"><?php echo Labels::getLabel('LBL_RECEIVER_NAME', $siteLangId); ?>:</span>
                                <span class="value"><?php echo $order["ogcards_receiver_name"]; ?></span>
                            </li>
                            <li class="list-stats-item">
                                <span class="lable"><?php echo Labels::getLabel('LBL_RECEIVER_EMAIL', $siteLangId); ?>:</span>
                                <span class="value"><?php echo $order["ogcards_receiver_email"]; ?></span>
                            </li>
                            <li class="list-stats-item">
                                <span class="lable"><?php echo Labels::getLabel('LBL_GIFT_CARD_USED', $siteLangId); ?>:</span>
                                <span class="value"><?php echo (GiftCards::STATUS_USED == $order["ogcards_status"]) ? Labels::getLabel('LBL_YES', $siteLangId) : Labels::getLabel('LBL_NO', $siteLangId); ?></span>
                            </li>


                        </ul>
                    </div>
                </div>


            </div>
        </div>
    </div>
</main>