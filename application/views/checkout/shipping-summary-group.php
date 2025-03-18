<ul class="list-cart list-shippings">
    <li class="list-cart-item list-shippings-head">
        <div class="shop-detail shipping-select">
            <h6 class="shop-title">
                <?php
                echo ($shipLevel == Shipping::LEVEL_SHOP) ? $productInfo['shop_name'] : FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId, null, '');
                ?>
            </h6>
            <div class="shipping-method">
                <?php $shippingCharges = [];
                if (isset($shippedByItemArr[$shipLevel]['rates'])) {
                    $shippingCharges = $shippedByItemArr[$shipLevel]['rates'];
                }
                if (count($shippingCharges) > 0) {
                    $dnone = '';
                    if (1 == count($shippingCharges)) {
                        $shippingChrgs = current($shippingCharges);
                        echo '<span class="alert alert-secondary">' . $shippingChrgs['title'] . ' ( ' . CommonHelper::displayMoneyFormat($shippingChrgs['cost']) . ' ) </span>';
                        $dnone = 'd-none';
                    }
                    $name = current($shippingCharges)['code'];
                    echo '<select class="form-control custom-select ' . $dnone . '" name="shipping_services[' . $name . ']">';
                    foreach ($shippingCharges as $key => $shippingcharge) {
                        $selected = '';
                        if (!empty($orderShippingData)) {
                            foreach ($orderShippingData as $shipdata) {
                                if ($shipdata['opshipping_code'] == $name && ($key == $shipdata['opshipping_carrier_code'] . "|" . $shipdata['opshipping_label'] || $key == $shipdata['opshipping_rate_id'])) {
                                    $selected = 'selected=selected';
                                    break;
                                }
                            }
                        }
                        echo '<option ' . $selected . ' value="' . $key . '">' . $shippingcharge['title'] . ' ( ' . CommonHelper::displayMoneyFormat($shippingcharge['cost']) . ' ) </option>';
                    }
                    echo '</select>';
                } else {
                    echo '<div class="alert alert-warning mt-3">' . Labels::getLabel('MSG_Product_is_not_available_for_shipping', $siteLangId) . '</div>';
                } ?>

            </div>
        </div>

    </li>
    <?php
    foreach ($productData as $product) {
        $uploadedTime = AttachedFile::setTimeParam($product['product_updated_on']);
        $productUrl = !$isAppUser ? UrlHelper::generateUrl('Products', 'View', array($product['selprod_id'])) : 'javascript:void(0)';
        $shopUrl = !$isAppUser ? UrlHelper::generateUrl('Shops', 'View', array($product['shop_id'])) : 'javascript:void(0)';
        $imageUrl = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], ImageDimension::VIEW_THUMB, $product['selprod_id'], 0, $siteLangId)), CONF_IMG_CACHE_TIME, '.jpg');
        $imageWebpUrl = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], 'WEBP' . ImageDimension::VIEW_THUMB, $product['selprod_id'], 0, $siteLangId)), CONF_IMG_CACHE_TIME, '.webp');
    ?>
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
        <li class="list-cart-item block-cart">
            <div class="block-cart-img">
                <div class="products-img">
                    <a href="<?php echo $productUrl; ?>">
                        <?php
                        $pictureAttr = [
                            'webpImageUrl' => [ImageDimension::VIEW_DESKTOP => $imageWebpUrl],
                            'jpgImageUrl' => [ImageDimension::VIEW_DESKTOP => $imageUrl],
                            'imageUrl' => $imageUrl,
                            'ratio' => '3:4',
                            'alt' => $product['product_name'],
                            'siteLangId' => $siteLangId,
                        ];
                        $this->includeTemplate('_partial/picture-tag.php', $pictureAttr);
                        ?>
                    </a>
                </div>
            </div>
            <div class="block-cart-detail">
                <div class="block-cart-detail-top">
                    <div class="product-profile">
                        <div class="product-profile-data">
                            <a class="title" href="<?php echo $productUrl; ?>">
                                <?php echo ($product['selprod_title']) ? $product['selprod_title'] : $product['product_name']; ?>
                            </a>
                            <?php if (false === SellerProduct::isPriceHidden($product['selprod_hide_price'], $product['shop_rfq_enabled'])) { ?>
                                <div class="products-price">
                                    <span class="products-price-new">
                                        <?php echo trim(CommonHelper::displayMoneyFormat($product['theprice'])); ?>
                                        <?php if (FatApp::getConfig("CONF_PRODUCT_INCLUSIVE_TAX", FatUtility::VAR_INT, 0)) { ?>
                                            <div class="products-price-off">(<?php echo Labels::getLabel('LBL_WITHOUT_TAXES', $siteLangId); ?>)</div>
                                        <?php } ?>
                                    </span>
                                    <?php if ($product['selprod_price'] > $product['actualPrice']) { ?>
                                        <del class="products-price-old"><?php echo trim(CommonHelper::displayMoneyFormat($product['selprod_price'])); ?></del>
                                        <div class="products-price-off"><?php echo trim(CommonHelper::showProductDiscountedText($product, $siteLangId)); ?></div>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                            <div class="options">
                                <?php if (isset($product['options']) && count($product['options'])) {
                                    foreach ($product['options'] as $key => $option) {
                                        if (0 < $key) {
                                            echo ' | ';
                                        }
                                        echo $option['option_name'] . ':'; ?> <span class="text-muted">
                                            <?php echo $option['optionvalue_name']; ?>
                                        </span>
                                <?php }
                                } ?>
                            </div>
                        </div>
                    </div>
                    <div class="product-quantity">
                        <div class="quantity quantity-sm">
                            <?php if (isset($_SESSION['offer_checkout']) && $_SESSION['offer_checkout']['selprod_id'] == $product['selprod_id']) { ?>
                                <div class="selected-qty">
                                    <strong><?php echo Labels::getLabel('LBL_QTY_:') ?></strong>
                                    <?php echo $product['quantity']; ?>
                                    <?php echo $_SESSION['offer_checkout']['offer_quantity_unit']; ?>
                                </div>
                            <?php } else { ?>
                                <button class="decrease decrease-js <?php echo ($product['quantity'] <= $product['selprod_min_order_qty']) ? 'disabled' : ''; ?>" type="button">
                                    <svg class="svg" width="16" height="16">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#minus">
                                        </use>
                                    </svg>
                                </button>
                                <input class="qty-input no-focus cartQtyTextBox productQty-js" title="<?php echo Labels::getLabel('LBL_Quantity', $siteLangId) ?>" data-page="checkout" type="text" name="qty_<?php echo md5($product['key']); ?>" data-key="<?php echo md5($product['key']); ?>" value="<?php echo $product['quantity']; ?>">
                                <button class="increase increase-js <?php echo ($product['selprod_stock'] <= $product['quantity']) ? 'disabled' : ''; ?>">
                                    <svg class="svg" width="16" height="16">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#plus">
                                        </use>
                                    </svg>
                                </button>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <div class="block-cart-detail-middle">
                    <div class="shipping-comments">
                        <button class="btn-dropdown dropdown-toggle-custom  collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $product['selprod_id']; ?>" aria-expanded="false" aria-controls="collapse<?php echo $product['selprod_id']; ?>">
                            <?php echo Labels::getLabel('LBL_COMMENTS', $siteLangId); ?> <i class="dropdown-toggle-custom-arrow"></i>
                        </button>
                        <div class="collapse form" id="collapse<?php echo $product['selprod_id']; ?>">
                            <textarea maxlength="250" class="form-textarea form-control opCommentsJs" placeholder="<?php echo Labels::getLabel('LBL_WRITE_YOUR_COMMENTS', $siteLangId); ?>" name="op_comments[<?php echo $product['selprod_id']; ?>]" spellcheck="false"></textarea>
                        </div>
                    </div>
                </div>
                <div class="block-cart-detail-bottom">
                    <ul class="cart-action">
                        <li class="cart-action-item">
                            <button class="btn btn-link" type="button" onclick="cart.remove('<?php echo md5($product['key']); ?>','checkout')">
                                <?php echo Labels::getLabel('LBL_Remove', $siteLangId); ?>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </li>
    <?php
    }
    ?>
</ul>