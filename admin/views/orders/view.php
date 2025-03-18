<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>

<main class="main">
    <div class="container">
        <?php $this->includeTemplate('_partial/header/header-breadcrumb.php', [], false); ?>
        <div class="row">
            <div class="col-md-9">
                <div class="card">
                    <div class="card-head">
                        <div class="card-head-label">
                            <h3 class="card-head-title">
                                <a class="btn-back" href="<?php echo UrlHelper::generateUrl('Orders'); ?>">
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
                        <div class="card-toolbar <?php echo (1 >= count($sellers) ? 'hide' : '') ?>">
                            <select id='allSellerJs' class="form-select" onchange="getOrderParticulars(<?php echo $order['order_id'] ?>, this)">
                                <option value=""><?php echo Labels::getLabel('LBL_ALL_SELLERS', $siteLangId); ?></option>
                                <?php foreach ($sellers as $sellerId => $shopName) { ?>
                                    <option value="<?php echo $sellerId; ?>" <?php echo ($opSellerId == $sellerId ? 'selected="selected"' : ''); ?>><?php echo $shopName; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <?php require_once(CONF_THEME_PATH . 'orders/item-summary.php'); ?>
                </div>
                <?php
                $plugInCode = $order['plugin_code'] ?? ' ';
                $paymentFormCond = (!$order["order_payment_status"] && $canEdit && !in_array($plugInCode, ['CashOnDelivery', 'PayAtStore']));
                $paymentHistory = (!empty($order['payments']));
                if (!$order['order_deleted'] && ($paymentFormCond || $paymentHistory) && !in_array($plugInCode, ['CashOnDelivery', 'PayAtStore'])) { ?>
                    <div class="card">
                        <div class="card-head">
                            <div class="card-head-label">
                                <h3 class="card-head-title"><?php echo Labels::getLabel('LBL_ORDER_PAYMENTS', $siteLangId); ?></h3>
                            </div>
                        </div>
                        <?php if ($paymentFormCond) { ?>
                            <div class="card-body">
                                <?php require_once(CONF_THEME_PATH . 'orders/payment-form.php'); ?>
                            </div>
                            <?php if (!empty($order['payments'])) { ?>
                                <div class="separator separator-dashed my-3"></div>
                            <?php } ?>
                        <?php } ?>
                        <div class="card-table paymentListJs">
                            <?php require_once(CONF_THEME_PATH . 'orders/payment-history.php'); ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
            <div class="col-md-3">
                <?php require_once(CONF_THEME_PATH . 'orders/order-summary.php'); ?>

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
                                        <span class="value"><span class="default-ltr"><?php echo ValidateElement::formatDialCode($order['buyer_phone_dcode']) . $order['buyer_phone']; ?></span></span>
                                    </li>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>
                <?php } ?>
                <?php
                $address = $order['shippingAddress'];
                if (!empty($address)) { ?>
                    <div class="card card-toggle">
                        <div class="card-head dropdown-toggle-custom collapsed" data-bs-toggle="collapse" data-bs-target="#order-block1" aria-expanded="false" aria-controls="order-block1">
                            <div class="card-head-label">
                                <h3 class="card-head-title">
                                    <?php echo Labels::getLabel('LBL_SHIPPING_ADDRESS', $siteLangId); ?>
                                </h3>
                            </div>
                            <i class="dropdown-toggle-custom-arrow"></i>
                        </div>
                        <div class="card-body collapse" id="order-block1">
                            <?php include(CONF_THEME_PATH . 'orders/address.php'); ?>
                        </div>
                    </div>
                <?php } ?>
                <div class="card card-toggle">
                    <div class="card-head dropdown-toggle-custom collapsed" data-bs-toggle="collapse" data-bs-target="#order-block2" aria-expanded="false" aria-controls="order-block2">
                        <div class="card-head-label">
                            <h3 class="card-head-title">
                                <?php echo Labels::getLabel('LBL_BILLING_ADDRESS', $siteLangId); ?>
                            </h3>
                        </div>
                        <i class="dropdown-toggle-custom-arrow"></i>
                    </div>
                    <div class="card-body collapse" id="order-block2">
                        <?php
                        $address = $order['billingAddress'];
                        include(CONF_THEME_PATH . 'orders/address.php');
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    var canShipByPlugin = <?php echo (!empty($shippingApiObj) ? 1 : 0); ?>;
    var orderShippedStatus = <?php echo $shippedOrderStatus; ?>;
</script>