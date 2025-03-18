<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>

<div id="shipping-summary" class="step">
    <ul class="review-block">
        <li class="review-block-item">
            <div class="review-block-head">
                <h5 class="h5"> <?php echo Labels::getLabel('LBL_Billing_to:', $siteLangId); ?>
                </h5>
                <div class="review-block-action">
                    <button class="link-underline" type="button" onClick="showAddressList()"><span><?php echo Labels::getLabel('LBL_Edit', $siteLangId); ?></span></button>
                </div>

            </div>
            <div class="review-block-body">
                <address class="address">
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
                    echo Labels::getLabel('LBL_PICKUP_SUMMARY', $siteLangId);
                } else {
                    echo Labels::getLabel('LBL_REVIEW_CHECKOUT', $siteLangId);
                }
                ?>
            </h5>
        </div>
        <div class="step_body">
            <?php
            ksort($shippingRates);
            foreach ($shippingRates as $pickUpBy => $levelItems) {
                /*  Physical Products */
                if (isset($levelItems['products']) && 0 < count($levelItems['products'])) { ?>
                    <ul class="list-cart list-cart-page list-shippings">
                        <?php $seletedSlotId = $seletedSlotDate = $seletedAddrId = '';
                        $productData = current($levelItems['products']);
                        $shopId = 0 < $pickUpBy ? $pickUpBy : 0;

                        if (!empty($levelItems['pickup_address'])) {
                            $address = $levelItems['pickup_address'];
                            $seletedSlotId = $address['time_slot_id'];
                            $seletedSlotDate = $address['time_slot_date'];
                            $seletedAddrId = $address['addr_id'];
                        } ?>

                        <li class="list-cart-item list-shippings-head">
                            <div class="shop-detail pickup-select">
                                <h6 class="shop-title">
                                    <?php echo 0 < $pickUpBy ? $productData['shop_name'] : FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId, null, ''); ?>
                                </h6>
                                <div class="shipping-method js-slot-addr-<?php echo $pickUpBy; ?>" data-addr-id="<?php echo $seletedAddrId; ?>">
                                    <input type="hidden" name="slot_id[<?php echo $pickUpBy; ?>]" class="js-slot-id" data-level="<?php echo $pickUpBy; ?>" value="<?php echo $seletedSlotId; ?>">
                                    <input type="hidden" name="slot_date[<?php echo $pickUpBy; ?>]" class="js-slot-date" data-level="<?php echo $pickUpBy; ?>" value="<?php echo isset($seletedSlotDate) ? $seletedSlotDate : ''; ?>">
                                    <?php if (count($levelItems['pickup_options']) > 0) { ?>
                                        <button class="link-underline pickupAddressBtn-<?php echo $pickUpBy; ?>-js" href="javascript:void(0); return false;" onclick="displayPickupAddress(<?php echo $pickUpBy; ?>, <?php echo $shopId; ?>)">
                                            <?php
                                            if (!empty($levelItems['pickup_address'])) {
                                                echo Labels::getLabel('LBL_CHANGE_PICKUP', $siteLangId);
                                            } else {
                                                echo Labels::getLabel('LBL_SELECT_PICKUP', $siteLangId);
                                            }
                                            ?>
                                        </button>
                                    <?php } else {
                                        echo Labels::getLabel('MSG_NO_PICKUP_ADDRESS_CONFIGURED', $siteLangId);
                                    } ?>
                                </div>
                            </div>
                            <div class="shop-selected js-slot-addr_<?php echo $pickUpBy; ?>">
                                <?php if (!empty($levelItems['pickup_address'])) {
                                    $fromTime = date('H:i', strtotime($address["time_slot_from"]));
                                    $toTime = date('H:i', strtotime($address["time_slot_to"]));
                                ?>
                                    <address class="address shop-address">
                                        <p><?php echo $address['addr_name'] . ', ' . $address['addr_address1']; ?>
                                            <?php if (strlen((string)$address['addr_address2']) > 0) {
                                                echo ", " . $address['addr_address2']; ?>
                                            <?php } ?>
                                        </p>
                                        <p><?php echo $address['addr_city'] . ", " . $address['state_name']; ?></p>
                                        <p><?php echo $address['country_name'] . ", " . $address['addr_zip']; ?></p>
                                        <ul class="phone-list">
                                            <?php if (strlen((string)$address['addr_phone']) > 0) {
                                                $addrPhone = ValidateElement::formatDialCode($address['addr_phone_dcode']) . $address['addr_phone'];
                                            ?>
                                                <li class="phone-list-item phone-txt">
                                                    <svg class="svg" width="20" height="20">
                                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#mobile-alt">
                                                        </use>
                                                    </svg>
                                                    <span class="default-ltr"><?php echo $addrPhone; ?></span>
                                                </li>
                                            <?php } ?>
                                            <li class="phone-list-item time-txt">
                                                <svg class="svg" width="20" height="20">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#calendar-day">
                                                    </use>
                                                </svg>
                                                <?php echo FatDate::format($address["time_slot_date"]) . ' ' . $fromTime . ' - ' . $toTime; ?>
                                            </li>
                                        </ul>
                                    </address>
                                <?php } ?>
                            </div>
                        </li>
                        <!-- Header -->

                        <!-- Items Body-->
                        <?php foreach ($levelItems['products'] as $product) {
                            $uploadedTime = AttachedFile::setTimeParam($product['product_updated_on']);
                            $productUrl = !$isAppUser ? UrlHelper::generateUrl('Products', 'View', array($product['selprod_id'])) : 'javascript:void(0)';
                            $shopUrl = !$isAppUser ? UrlHelper::generateUrl('Shops', 'View', array($product['shop_id'])) : 'javascript:void(0)';
                            $imageUrl = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], ImageDimension::VIEW_THUMB, $product['selprod_id'], 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                            $imageWebpUrl = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], 'WEBP' . ImageDimension::VIEW_THUMB, $product['selprod_id'], 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.webp'); ?>
                            <li class="list-cart-item block-cart">
                                <div class="block-cart-img">
                                    <div class="product-profile">
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
                                </div>
                                <div class="block-cart-detail">
                                    <div class="block-cart-detail-top">
                                        <div class="product-profile">
                                            <div class="product-profile-data">
                                                <a class="title" href="<?php echo $productUrl; ?>">
                                                    <?php echo ($product['selprod_title']) ? $product['selprod_title'] : $product['product_name']; ?>
                                                </a>
                                                <?php require(CONF_THEME_PATH . '_partial/collection/product-price.php'); ?>
                                                <div class="options">
                                                    <?php if (isset($product['options']) && count($product['options'])) {
                                                        foreach ($product['options'] as $key => $option) {
                                                            if (0 < $key) {
                                                                echo ' | ';
                                                            }
                                                            echo $option['option_name'] . ':'; ?> <span class="text-muted"><?php echo $option['optionvalue_name']; ?></span>
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
                                            <button class="btn-dropdown dropdown-toggle-custom collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $product['selprod_id']; ?>" aria-expanded="false" aria-controls="collapse<?php echo $product['selprod_id']; ?>">
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
                        <?php } ?>
                        <!-- Items Body-->
                    </ul>
                <?php }
                /*  Physical Products */

                /*  Digital Products */
                if (isset($levelItems['digital_products']) && count($levelItems['digital_products']) > 0) {
                    $digiProductData = current($levelItems['digital_products']); ?>
                    <ul class="list-cart list-cart-page list-shippings">
                        <!-- Header Shop Name-->
                        <li class="list-cart-item list-shippings-head">
                            <div class="shop-detail pickup-select">
                                <h6 class="shop-title">
                                    <?php echo (0 < $pickUpBy) ? $digiProductData['shop_name'] : FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId, null, ''); ?>
                                </h6>
                            </div>
                        </li>
                        <!-- Header -->

                        <!-- Items Body-->
                        <?php foreach ($levelItems['digital_products'] as $pickUpBy => $product) {
                            $productUrl = !$isAppUser ? UrlHelper::generateUrl('Products', 'View', array($product['selprod_id'])) : 'javascript:void(0)';
                            $shopUrl = !$isAppUser ? UrlHelper::generateUrl('Shops', 'View', array($product['shop_id'])) : 'javascript:void(0)';
                            $imageUrl = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], ImageDimension::VIEW_THUMB, $product['selprod_id'], 0, $siteLangId)), CONF_IMG_CACHE_TIME, '.jpg');
                        ?>
                            <li class="list-cart-item block-cart">
                                <div class="block-cart-img">
                                    <div class="product-profile">
                                        <div class="product-profile-thumbnail">
                                            <a href="<?php echo $productUrl; ?>">
                                                <img class="img-fluid" data-ratio="3:4" src="<?php echo $imageUrl; ?>" alt="<?php echo $product['product_name']; ?>" title="<?php echo $product['product_name']; ?>">
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="block-cart-detail">
                                    <div class="block-cart-detail-top">
                                        <div class="product-profile">
                                            <div class="product-profile-data">
                                                <a class="title" href="<?php echo $productUrl; ?>"><?php echo ($product['selprod_title']) ? $product['selprod_title'] : $product['product_name']; ?></a>
                                                <div class="products-price">
                                                    <?php echo CommonHelper::displayMoneyFormat($product['theprice'] * $product['quantity'], true, false, true, false, false, true); ?>
                                                    <?php if ($product['selprod_price'] > $product['theprice']) { ?>
                                                        <span class="products-price-off">
                                                            <?php echo CommonHelper::showProductDiscountedText($product, $siteLangId); ?></span>
                                                    <?php } ?>
                                                </div>
                                                <div class="options">
                                                    <?php if (isset($product['options']) && count($product['options'])) {
                                                        foreach ($product['options'] as $key => $option) {
                                                            if (0 < $key) {
                                                                echo ' | ';
                                                            }
                                                            echo $option['option_name'] . ':'; ?> <span class="text-muted"><?php echo $option['optionvalue_name']; ?></span>
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
                        <?php } ?>
                        <!-- Items Body-->
                    </ul>
            <?php }
                /*  Digital Products */
            } ?>
        </div>

    </div>
</div>
</div>