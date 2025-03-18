<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>

<div id="shipping-summary" class="step">
    <ul class="review-block">
        <li class="review-block-item">
            <div class="review-block-head">
                <h5 class="h5">
                    <?php if ($hasPhysicalProd) {
                        echo Labels::getLabel('LBL_Shipping_to:', $siteLangId);
                    } else {
                        echo Labels::getLabel('LBL_Billing_to:', $siteLangId);
                    } ?>
                </h5>

                <?php if (!isset($_SESSION['offer_checkout'])) { ?>
                    <div class="review-block-action" role="cell">
                        <button class="link-underline" onClick="showAddressList()">
                            <span>
                                <?php echo Labels::getLabel('LBL_Edit', $siteLangId); ?>
                            </span>
                        </button>
                    </div>
                <?php } ?>
            </div>
            <div class="review-block-body" role="cell">
                <address class="address delivery-address">
                    <p><?php echo $addresses['addr_name'] . ', ' . $addresses['addr_address1']; ?>
                        <?php if (strlen((string)$addresses['addr_address2']) > 0) {
                            echo ", " . $addresses['addr_address2']; ?>
                        <?php } ?>
                    </p>
                    <p><?php echo $addresses['addr_city'] . ", " . $addresses['state_name'] . ", " . $addresses['country_name'] . ", " . $addresses['addr_zip']; ?>
                    </p>

                    <?php if (strlen((string)$addresses['addr_phone']) > 0) {
                        $addrPhone = ValidateElement::formatDialCode($addresses['addr_phone_dcode']) . $addresses['addr_phone'];
                    ?>
                        <ul class="phone-list">
                            <li class="phone-list-item phone-txt">
                                <svg class="svg" width="20" height="20">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#mobile-alt">
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

    <div class="step_section">
        <div class="step_head">
            <h5 class="step_title">
                <?php
                $cartObj = new Cart();
                if ($cartObj->hasPhysicalProduct()) {
                    echo Labels::getLabel('LBL_SHIPPING_SUMMARY', $siteLangId);
                } else {
                    echo Labels::getLabel('LBL_REVIEW_CHECKOUT', $siteLangId);
                }
                ?>
            </h5>
        </div>
        <script>
            var productData = [];
        </script>
        <div class="step_body">
            <?php ksort($shippingRates);
            foreach ($shippingRates as $shippedBy => $shippedByItemArr) {
                ksort($shippedByItemArr);
                foreach ($shippedByItemArr as $shipLevel => $items) {
                    switch ($shipLevel) {
                        case Shipping::LEVEL_ORDER:
                        case Shipping::LEVEL_SHOP:
                            if (isset($items['products']) && !empty($items['products'])) {
                                $productData = $items['products'];
                                $productInfo = current($productData);
                                require('shipping-summary-group.php');
                            }
                            break;
                        case Shipping::LEVEL_PRODUCT:
                            if (isset($items['products']) && !empty($items['products'])) {
                                foreach ($items['products'] as $selProdid => $product) {
                                    require('shipping-summary-product.php'); ?>
                                    <script type="text/javascript">
                                        productData.push({
                                            item_id: "<?php echo $product['selprod_id']; ?>",
                                            item_name: "<?php echo $product['selprod_title']; ?>",
                                            discount: "<?php echo ($product['selprod_price'] - $product['theprice']); ?>",
                                            index: "<?php echo $product['selprod_id']; ?>",
                                            item_brand: "<?php echo $product['brand_name']; ?>",
                                            item_category: "<?php echo $product['prodcat_name']; ?>",
                                            price: "<?php echo $product['theprice']; ?>",
                                            quantity: "<?php echo $product['quantity']; ?>"
                                        })
                                    </script>
                                <?php
                                }
                            }
                            if (isset($items['digital_products']) && !empty($items['digital_products'])) {
                                foreach ($items['digital_products'] as $selProdid => $product) {
                                    require('shipping-summary-product.php'); ?>
                                    <script type="text/javascript">
                                        productData.push({
                                            item_id: "<?php echo $product['selprod_id']; ?>",
                                            item_name: "<?php echo $product['selprod_title']; ?>",
                                            discount: "<?php echo ($product['selprod_price'] - $product['theprice']); ?>",
                                            index: "<?php echo $product['selprod_id']; ?>",
                                            item_brand: "<?php echo $product['brand_name']; ?>",
                                            item_category: "<?php echo $product['prodcat_name']; ?>",
                                            price: "<?php echo $product['theprice']; ?>",
                                            quantity: "<?php echo $product['quantity']; ?>"
                                        })
                                    </script>
                            <?php
                                }
                            }
                            break;
                    }
                }
            } ?>
        </div>
    </div>
</div>
<script type="text/javascript">
    ykevents.initiateCheckout({
        currency: currencyCode,
        value: "<?php echo $cartSummary['orderNetAmount']; ?>",
        items: productData
    });
</script>