<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<script>
    var productData = [];
</script>
<?php
$products = $orderInfo['orderProducts'];
?>
<div id="body" class="body">
    <section class="section"  data-section="section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-9">
                    <div class="order-completed">
                        <div class="thanks-screen text-center">
                            <!-- Icon -->
                            <div class="success-animation">
                                <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                                    <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"></circle>
                                    <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"></path>
                                </svg>
                            </div>
                            <h2><?php echo Labels::getLabel('LBL_THANK_YOU!', $siteLangId); ?></h2>
                            <h3>
                                <?php
                                if (Orders::ORDER_PRODUCT == $orderInfo['order_type']) {
                                    $msg = Labels::getLabel('LBL_YOUR_ORDER_{ORDER-ID}_HAS_BEEN_PLACED!', $siteLangId);
                                    $orderDetailUrl = UrlHelper::generateUrl('Buyer', 'viewOrder', array($orderInfo['order_id']), CONF_WEBROOT_DASHBOARD, null, false, false, false);
                                    $orderDetailLinkHtml = '<a href="' . $orderDetailUrl . '" class="link-underline">#' . $orderInfo['order_number'] . '</a>';
                                } else {
                                    $msg = Labels::getLabel('LBL_ORDER_#{ORDER-ID}_TRANSACTION_COMPLETED!', $siteLangId);
                                    $orderDetailLinkHtml = $orderInfo['order_number'];
                                    if (array_key_exists('orderProducts', $orderInfo) && !empty($orderInfo['orderProducts'])) {
                                        $orderProducts = current($orderInfo['orderProducts']);
                                        $orderDetailUrl = UrlHelper::generateUrl('Seller', 'viewSubscriptionOrder', array($orderProducts['ossubs_id']), CONF_WEBROOT_DASHBOARD, null, false, false, false);
                                        if (isset($orderProducts['ossubs_id'])) {
                                            $orderDetailLinkHtml = '<a href="' . $orderDetailUrl . '">' . $orderInfo['order_number'] . '</a>';
                                        }
                                    }
                                }
                                $msg = CommonHelper::replaceStringData($msg, ['{ORDER-ID}' => $orderDetailLinkHtml]);
                                echo $msg;
                                ?>
                            </h3>
                            <?php if (!CommonHelper::isAppUser()) { ?>
                                <p><?php echo CommonHelper::renderHtml($textMessage); ?></p>
                            <?php } ?>
                            <?php if ($orderInfo['order_type'] != Orders::ORDER_WALLET_RECHARGE && $orderInfo['order_type'] != Orders::ORDER_GIFT_CARD) { ?>
                                <p>
                                    <svg class="svg" width="22px" height="22px">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#TimePlaced" href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#TimePlaced">
                                        </use>
                                    </svg>
                                    <?php
                                    $replace = [
                                        '{TIME-PLACED}' => '<strong>' . Labels::getLabel('LBL_TIME_PLACED', $siteLangId) . '</strong>',
                                        '{DATE-TIME}' => $orderInfo['order_date_added'],
                                    ];
                                    $msg = Labels::getLabel('LBL_{TIME-PLACED}:_{DATE-TIME}', $siteLangId);
                                    $msg = CommonHelper::replaceStringData($msg, $replace);
                                    echo $msg;
                                    ?>
                                    &nbsp;&nbsp;&nbsp;
                                    <span class="no-print">
                                        <a class="btn btn-link btn-icon" onclick="window.print();" href="javascript:void(0)">
                                            <svg class="svg" width="16" height="16">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#print">
                                                </use>
                                            </svg>
                                            <?php echo Labels::getLabel("LBL_PRINT", $siteLangId); ?></a>
                                    </span>
                                </p>
                            <?php } ?>
                        </div>
                        <?php if (true === $showOrderDetails) {   ?>
                            <ul class="completed-detail">
                                <?php if (!empty($orderInfo['shippingAddress'])) {
                                    $shippingAddress = $orderInfo['shippingAddress']; ?>
                                    <li class="completed-detail-item">
                                        <h4>

                                            <?php echo Labels::getLabel("LBL_SHIPPING_ADDRESS", $siteLangId); ?>
                                        </h4>
                                        <p>
                                            <strong><?php echo $shippingAddress['oua_name']; ?></strong>
                                            <br>
                                            <?php
                                            echo $shippingAddress['oua_address1'];
                                            if (!empty($shippingAddress['oua_address2'])) {
                                                echo ', ' . $shippingAddress['oua_address2'];
                                            }
                                            echo '<br>' . $shippingAddress['oua_city'] . ', ' . $shippingAddress['oua_state'];
                                            echo '<br>' . $shippingAddress['oua_country'] . '(' . $shippingAddress['oua_zip'] . ')';
                                            echo '<br><span class="default-ltr">' . ValidateElement::formatDialCode($shippingAddress['oua_phone_dcode']) . $shippingAddress['oua_phone'] . '</span>';
                                            ?>
                                        </p>
                                    </li>
                                    <?php }
                                if (Orders::ORDER_PRODUCT == $orderInfo['order_type']) {
                                    $shippingMethod = '';
                                    if (Orders::ORDER_PRODUCT == $orderInfo['order_type']) {
                                        foreach ($products as $op) {
                                            $shippingMethod .= !empty($op['opshipping_label']) ? '<li>' . $op['opshipping_label'] . '</li>' : '';
                                        }
                                    }

                                    $fulfillmentType = Shipping::FULFILMENT_SHIP;
                                    array_walk($orderFulFillmentTypeArr, function ($row) use (&$fulfillmentType) {
                                        if (Product::PRODUCT_TYPE_PHYSICAL == $row['op_product_type']) {
                                            $fulfillmentType = $row['opshipping_fulfillment_type'];
                                            return;
                                        }
                                    });
                                    if (!empty($orderFulFillmentTypeArr) && Shipping::FULFILMENT_PICKUP == $fulfillmentType) { ?>
                                        <li class="completed-detail-item">
                                            <h4>
                                                <?php echo Labels::getLabel('LBL_ORDER_PICKUP', $siteLangId); ?>
                                            </h4>

                                            <?php
                                            foreach ($orderFulFillmentTypeArr as $orderAddDet) {
                                                if (empty($orderAddDet['addr_id'])) {
                                                    continue;
                                                }
                                            ?>
                                                <p>
                                                    <strong>
                                                        <?php
                                                        $opshippingDate = isset($orderAddDet['opshipping_date']) ? $orderAddDet['opshipping_date'] . ' ' : '';
                                                        $timeSlotFrom = isset($orderAddDet['opshipping_time_slot_from']) ? $orderAddDet['opshipping_time_slot_from'] . ' - ' : '';
                                                        $timeSlotTo = isset($orderAddDet['opshipping_time_slot_to']) ? $orderAddDet['opshipping_time_slot_to'] : '';
                                                        echo '#' . $orderAddDet['op_invoice_number'] . '<br>' . $opshippingDate . $timeSlotFrom . $timeSlotTo;
                                                        ?>
                                                    </strong><br>
                                                    <?php echo $orderAddDet['addr_name']; ?>,
                                                    <?php
                                                    $address1 = !empty($orderAddDet['addr_address1']) ? $orderAddDet['addr_address1'] : '';
                                                    $address2 = !empty($orderAddDet['addr_address2']) ? ', ' . $orderAddDet['addr_address2'] : '';
                                                    $city = !empty($orderAddDet['addr_city']) ? '<br>' . $orderAddDet['addr_city'] : '';
                                                    $state = !empty($orderAddDet['state_name']) ? ', ' . $orderAddDet['state_name'] : '';
                                                    $country = !empty($orderAddDet['country_name']) ? ', ' . $orderAddDet['country_name'] : '';
                                                    $zip = !empty($orderAddDet['addr_zip']) ? '(' . $orderAddDet['addr_zip'] . ')' : '';
                                                    $phone = !empty($orderAddDet['addr_phone']) ? $orderAddDet['addr_phone'] : '';
                                                    if (!empty($phone) && array_key_exists('addr_phone_dcode', $orderAddDet)) {
                                                        $phone = '<span class="default-ltr">' . ValidateElement::formatDialCode($orderAddDet['addr_phone_dcode']) . $phone . '</span>';
                                                    }
                                                    $phone = '<br>' . $phone;
                                                    echo $address1 . $address2 . $city . $state . $country . $zip . $phone;
                                                    ?>
                                                </p>
                                            <?php } ?>
                                        </li>
                                    <?php } else if (!empty($shippingMethod)) { ?>
                                        <li class="completed-detail-item">
                                            <h4>
                                                <?php echo Labels::getLabel('LBL_SHIPPING_METHOD', $siteLangId); ?>
                                            </h4>
                                            <p><?php echo Labels::getLabel('LBL_PREFERRED_METHOD', $siteLangId); ?>: <br>
                                            <ol class="preferred-shipping-list">
                                                <?php echo $shippingMethod; ?>
                                            </ol>
                                            </p>
                                        </li>
                                    <?php }
                                    if (!empty($orderInfo['billingAddress'])) { ?>
                                        <li class="completed-detail-item">
                                            <?php $billingAddress = $orderInfo['billingAddress']; ?>
                                            <h4>

                                                <?php echo Labels::getLabel("LBL_BILLING_ADDRESS", $siteLangId); ?>
                                            </h4>
                                            <p>
                                                <strong><?php echo $billingAddress['oua_name']; ?></strong><br>
                                                <?php
                                                echo $billingAddress['oua_address1'];
                                                if (!empty($billingAddress['oua_address2'])) {
                                                    echo ', ' . $billingAddress['oua_address2'];
                                                }
                                                echo '<br>' . $billingAddress['oua_city'] . ', ' . $billingAddress['oua_state'];
                                                echo '<br>' . $billingAddress['oua_country'] . '(' . $billingAddress['oua_zip'] . ')';
                                                echo '<br><span class="default-ltr">' . ValidateElement::formatDialCode($billingAddress['oua_phone_dcode']) . $billingAddress['oua_phone'] . '</span>';
                                                ?>
                                            </p>
                                        </li>
                                <?php }
                                } ?>
                            </ul>
                        <?php } ?>
                    </div>
                    <?php if (true === $showOrderDetails) {
                        if ($orderInfo['order_type'] != Orders::ORDER_WALLET_RECHARGE && $orderInfo['order_type'] != Orders::ORDER_GIFT_CARD) { ?>
                            <div class="pagebreak"> </div>
                            <div class="row justify-content-center">
                                <div class="col-md-12">
                                    <div class="completed-cart cart-page">
                                        <div class="cart-page_main">
                                            <div class="cart-page-head">
                                                <h2 class="h2">
                                                    <?php echo Labels::getLabel('LBL_ORDER_DETAIL', $siteLangId); ?>
                                                </h2>
                                            </div>

                                            <ul class="list-cart">
                                                <?php
                                                $shippingCharges = $subTotal = 0;
                                                $selProdTotalSpecialPrice = 0;
                                                if (Orders::ORDER_PRODUCT == $orderInfo['order_type']) {
                                                    foreach ($products as $key => $product) {
                                                        $productUrl = UrlHelper::generateUrl('Products', 'View', array($product['op_selprod_id']));
                                                        $shopUrl = UrlHelper::generateUrl('Shops', 'View', array($product['op_shop_id']));
                                                        $imageUrl = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['selprod_product_id'], ImageDimension::VIEW_MINI, $product['op_selprod_id'], 0, $siteLangId), CONF_WEBROOT_FRONTEND), CONF_IMG_CACHE_TIME, '.jpg');
                                                        $productTitle =  ($product['op_selprod_title']) ? $product['op_selprod_title'] : $product['op_product_name'];
                                                        if (array_key_exists('op_special_price', $product)) {
                                                            $selProdTotalSpecialPrice += $product['op_special_price'] * $product["op_qty"];
                                                        }
                                                ?>
                                                        <li class="list-cart-item block-cart block-cart-sm">
                                                            <div class="block-cart-img">
                                                                <div class="products-img">
                                                                    <a href="<?php echo $productUrl; ?>">
                                                                        <img src="<?php echo $imageUrl; ?>" alt="<?php echo $product['op_product_name']; ?>" title="<?php echo $product['op_product_name']; ?>" <?php echo HtmlHelper::getImgDimParm(ImageDimension::TYPE_PRODUCTS, ImageDimension::VIEW_MINI); ?>>
                                                                    </a>

                                                                </div>
                                                            </div>
                                                            <div class="block-cart-detail">
                                                                <div class="block-cart-detail-top">
                                                                    <div class="product-profile">
                                                                        <div class="product-profile-data">
                                                                            <a class="title" href="<?php echo $productUrl; ?>"><?php echo $productTitle; ?></a>
                                                                            <div class="products-price">
                                                                                <?php
                                                                                $subTotal += $txnAmount = ($product["op_unit_price"] * $product["op_qty"]);
                                                                                echo CommonHelper::displayMoneyFormat($txnAmount);
                                                                                $shippingCharges += $product['op_actual_shipping_charges'];
                                                                                ?>
                                                                            </div>
                                                                            <div class="options">
                                                                                <p class=""> <?php echo $product['op_selprod_options']; ?></p>
                                                                                <span class="product-qty"><?php echo Labels::getLabel("LBL_SOLD_QUANTITY", $siteLangId) . ":" . $product['op_qty']; ?></span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <script type="text/javascript">
                                                                productData.push({
                                                                    item_id: "<?php echo $product['op_selprod_id']; ?>",
                                                                    item_name: "<?php echo $product['op_selprod_title']; ?>",
                                                                    discount: "<?php echo ($product['op_selprod_price'] - ($product["op_unit_price"] * $product["op_qty"])); ?>",
                                                                    index: "<?php echo $product['op_selprod_id']; ?>",
                                                                    item_brand: "<?php echo $product['op_brand_name']; ?>",
                                                                    price: "<?php echo ($product["op_unit_price"] * $product["op_qty"]); ?>",
                                                                    quantity: "<?php echo $product['op_qty']; ?>"
                                                                })
                                                            </script>
                                                        </li>
                                                    <?php } ?>
                                                    <script>
                                                        $(document).ready(function() {
                                                            ykevents.purchase({
                                                                transaction_id: "<?php echo $orderId; ?>",
                                                                value: "<?php echo $orderInfo['order_net_amount']; ?>",
                                                                tax: "<?php echo $orderInfo['order_tax_charged']; ?>",
                                                                shipping: "<?php echo $shippingCharges; ?>",
                                                                currency: "<?php echo $orderInfo['order_currency_code']; ?>",
                                                                items: productData
                                                            });
                                                        });
                                                    </script>
                                                    <?php } else {
                                                    foreach ($products as $subscription) {
                                                        $subTotal += $subscription['ossubs_price'];
                                                    ?>
                                                        <li class="list-cart-item"><?php echo Labels::getLabel("LBL_COMMISION_RATE", $siteLangId); ?> <span><?php echo CommonHelper::displayComissionPercentage($subscription['ossubs_commission']); ?>%</span></li>
                                                        <li class="list-cart-item"><?php echo Labels::getLabel("LBL_ACTIVE_PRODUCTS", $siteLangId); ?> <span><?php echo $subscription['ossubs_products_allowed']; ?></span></li>
                                                        <li class="list-cart-item"><?php echo Labels::getLabel("LBL_PRODUCT_INVENTORY", $siteLangId); ?> <span><?php echo $subscription['ossubs_inventory_allowed']; ?></span></li>
                                                        <li class="list-cart-item"><?php echo Labels::getLabel("LBL_IMAGES_PER_PRODUCT", $siteLangId); ?> <span><?php echo $subscription['ossubs_images_allowed']; ?></span></li>
                                                        <li class="list-cart-item"><?php echo Labels::getLabel("LBL_RFQ_OFFERS", $siteLangId); ?> <span><?php echo $subscription['ossubs_rfq_offers_allowed']; ?></span></li>
                                                <?php }
                                                } ?>
                                            </ul>
                                        </div>
                                        <div class="cart-page_aside">
                                            <div class="sticky-summary">
                                                <div class="cart-total">
                                                    <div class="cart-total-head">
                                                        <h3 class="cart-total-title"><?php echo Labels::getLabel('LBL_ORDER_SUMMARY', $siteLangId); ?></h3>
                                                    </div>
                                                    <div class="cart-total-body">
                                                        <ul class="cart-summary">
                                                            <?php if (0 < $subTotal) { ?>
                                                                <li class="cart-summary-item">
                                                                    <span class="label">
                                                                        <?php echo Labels::getLabel('LBL_Sub_Total', $siteLangId); ?>
                                                                    </span>
                                                                    <span class="value">
                                                                        <?php echo CommonHelper::displayMoneyFormat($subTotal); ?>
                                                                    </span>
                                                                </li>
                                                            <?php }
                                                            if (0 < $orderInfo['order_reward_point_value'] || 0 < $orderInfo['order_discount_total']) {
                                                                $msg = "LBL_REWARD_POINTS";
                                                                $totalDiscount = $orderInfo['order_reward_point_value'];
                                                                if (!empty($orderInfo['order_discount_total']) && 0 < $orderInfo['order_discount_total']) {
                                                                    $msg .= "_&_DISCOUNT";
                                                                    $totalDiscount += $orderInfo['order_discount_total'];
                                                                }
                                                            ?>
                                                                <li class="cart-summary-item">
                                                                    <span class="label"><?php echo Labels::getLabel($msg, $siteLangId); ?></span>
                                                                    <span class="value">- <?php echo CommonHelper::displayMoneyFormat($totalDiscount); ?></span>
                                                                </li>
                                                            <?php }
                                                            if (0 < $orderInfo['order_volume_discount_total']) {
                                                                $msg = 'LBL_Loyalty/Volume_Discount';
                                                                $totalDiscount = $orderInfo['order_volume_discount_total'];
                                                            ?>
                                                                <li class="cart-summary-item">
                                                                    <span class="label"><?php echo Labels::getLabel($msg, $siteLangId); ?></span>
                                                                    <span class="value">- <?php echo CommonHelper::displayMoneyFormat($totalDiscount); ?></span>
                                                                </li>
                                                            <?php }
                                                            if (0 < $orderInfo['order_tax_charged']) { ?>
                                                                <li class="cart-summary-item">
                                                                    <span class="label"><?php echo Labels::getLabel('LBL_TAX', $siteLangId); ?></span>
                                                                    <span class="value"><?php echo CommonHelper::displayMoneyFormat($orderInfo['order_tax_charged']); ?></span>
                                                                </li>
                                                            <?php } ?>
                                                            <?php if (0 < $shippingCharges) { ?>
                                                                <li class="cart-summary-item">
                                                                    <span class="label"><?php echo Labels::getLabel('LBL_SHIPPING_CHARGES', $siteLangId); ?></span>
                                                                    <span class="value"><?php echo CommonHelper::displayMoneyFormat($shippingCharges); ?></span>
                                                                </li>
                                                            <?php  } ?>
                                                            <?php if (array_key_exists('order_rounding_off', $orderInfo) && $orderInfo['order_rounding_off'] != 0) { ?>
                                                                <li class="cart-summary-item">
                                                                    <span class="label"><?php echo (0 < $orderInfo['order_rounding_off']) ? Labels::getLabel('LBL_Rounding_Up', $siteLangId) : Labels::getLabel('LBL_Rounding_Down', $siteLangId); ?></span>
                                                                    <span class="value"><?php echo CommonHelper::displayMoneyFormat($orderInfo['order_rounding_off']); ?></span>
                                                                </li>
                                                            <?php } ?>
                                                            <?php
                                                            if (Orders::ORDER_SUBSCRIPTION == $orderInfo['order_type']) {
                                                                $adjustedAmount = CommonHelper::orderSubscriptionAmount(current($orderInfo['orderProducts']), 'ADJUSTEDAMOUNT');
                                                                if (0 != $adjustedAmount) { ?>
                                                                    <li class="cart-summary-item">
                                                                        <span class="label"><?php echo Labels::getLabel('LBL_ADJUSTED_AMOUNT', $siteLangId); ?></span>
                                                                        <span class="value"><?php echo CommonHelper::displayMoneyFormat($adjustedAmount); ?></span>
                                                                    </li>
                                                            <?php }
                                                            } ?>
                                                            <li class="cart-summary-item highlighted">
                                                                <span class="label"><?php echo Labels::getLabel('LBL_NET_AMOUNT', $siteLangId); ?></span>
                                                                <span class="value"><?php echo CommonHelper::displayMoneyFormat($orderInfo['order_net_amount']); ?></span>
                                                            </li>
                                                            <?php
                                                            $totalSaving =  $selProdTotalSpecialPrice + $orderInfo['order_discount_total'] + $orderInfo['order_volume_discount_total'];
                                                            if (0 < $totalSaving) { ?>
                                                                <li class="cart-summary-item">
                                                                    <span class="label"><?php echo Labels::getLabel('LBL_TOTAL_SAVING', $siteLangId); ?></span>
                                                                    <span class="value text-success"><?php echo CommonHelper::displayMoneyFormat($totalSaving); ?></span>
                                                                </li>
                                                            <?php } ?>

                                                        </ul>
                                                    </div>


                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                    <?php }
                    } ?>

                </div>
            </div>
        </div>

    </section>

</div>