<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<header class="section-head">
    <div class="section-heading">
        <h2><?php echo Labels::getLabel('LBL_Review_Order', $siteLangId); ?></h2>
    </div>
</header>
<div class="section-body">
    <div class="box box--white box--radius p-4">
        <div class="review-wrapper">
            <?php if ($cartHasDigitalProduct && $cartHasPhysicalProduct) { ?>
                <div class="">
                    <div class="tabs setactive-js">
                        <ul>
                            <li class="is-active "><a rel="physical_product_tab"
                                    href="javascript:void(0)"><?php echo Labels::getLabel('LBL_Tab_Physical_Product', $siteLangId); ?></a>
                            </li>
                            <li class="digitalProdTab-js"><a rel="digital_product_tab" href="javascript:void(0)"
                                    class=""><?php echo Labels::getLabel('LBL_Tab_Digital_Product', $siteLangId); ?></a></li>
                        </ul>
                    </div>
                </div>
            <?php } ?>
            <div class="short-detail">
                <table class="table cart--full">
                    <tbody>
                        <?php
                        if (count($products)) {
                            foreach ($products as $product) {
                                $productUrl = !$isAppUser ? UrlHelper::generateUrl('Products', 'View', array($product['selprod_id'])) : 'javascript:void(0)';
                                $shopUrl = !$isAppUser ? UrlHelper::generateUrl('Shops', 'View', array($product['shop_id'])) : 'javascript:void(0)';
                                $imageUrl = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], ImageDimension::VIEW_THUMB, $product['selprod_id'], 0, $siteLangId)), CONF_IMG_CACHE_TIME, '.jpg'); ?>
                                <tr
                                    class="<?php echo (!$product['in_stock']) ? 'disabled' : '';
                                            echo ($product['is_digital_product']) ? 'digital_product_tab-js' : 'physical_product_tab-js'; ?>">
                                    <td>
                                        <figure class="item__pic"><a href="<?php echo $productUrl; ?>"><img
                                                    src="<?php echo $imageUrl; ?>"
                                                    <?php echo HtmlHelper::getImgDimParm(ImageDimension::TYPE_PRODUCTS, ImageDimension::VIEW_THUMB); ?>
                                                    alt="<?php echo CommonHelper::renderHtml($product['product_name'], true); ?>"
                                                    title="<?php echo CommonHelper::renderHtml($product['product_name'], true); ?>"></a>
                                        </figure>
                                    </td>
                                    <td>
                                        <div class="product-profile-data">
                                            <div class="item__category"><?php echo Labels::getLabel('LBL_Shop', $siteLangId) ?>:
                                                <span
                                                    class="text--dark"><?php echo CommonHelper::renderHtml($product['shop_name'], true); ?></span>
                                            </div>
                                            <div class="title"><a
                                                    title="<?php echo ($product['selprod_title']) ? CommonHelper::renderHtml($product['selprod_title'], true) : CommonHelper::renderHtml($product['product_name'], true); ?>"
                                                    href="<?php echo $productUrl; ?>"><?php echo ($product['selprod_title']) ? CommonHelper::renderHtml($product['selprod_title'], true) : CommonHelper::renderHtml($product['product_name'], true); ?></a>
                                            </div>
                                            <div class="options">
                                                <?php
                                                if (isset($product['options']) && count($product['options'])) {
                                                    foreach ($product['options'] as $option) { ?>
                                                        <?php echo ' | ' . CommonHelper::renderHtml($option['option_name'], true) . ':'; ?>
                                                        <span
                                                            class="text--dark"><?php echo CommonHelper::renderHtml($option['optionvalue_name'], true); ?></span>
                                                <?php
                                                    }
                                                } ?>
                                                | <?php echo Labels::getLabel('LBL_Quantity', $siteLangId) ?>
                                                <?php echo $product['quantity']; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span
                                            class="item__price"><?php echo CommonHelper::displayMoneyFormat($product['theprice'] * $product['quantity']); ?>
                                        </span>
                                        <?php if ($product['special_price_found'] && $product['selprod_price'] > $product['theprice']) { ?>
                                            <span
                                                class="text--normal text--normal-secondary"><?php echo CommonHelper::showProductDiscountedText($product, $siteLangId); ?></span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <a href="javascript:void(0)"
                                            onclick="cart.remove('<?php echo md5($product['key']); ?>','checkout')"
                                            class="icons-wrapper">
                                            <svg class="svg" width="18" height="18">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#bin"
                                                    href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#bin"></use>
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                        <?php
                            }
                        } else {
                            echo Labels::getLabel('LBL_YOUR_CART_IS_EMPTY', $siteLangId);
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <div class="cartdetail__footer js-scrollable table-wrap">
                <table class="table-justify">
                    <tr>
                        <td><?php echo Labels::getLabel('LBL_Sub_Total', $siteLangId); ?></td>
                        <td><?php echo CommonHelper::displayMoneyFormat($cartSummary['cartTotal']); ?></td>
                    </tr>
                    <?php if ($cartSummary['shippingTotal']) { ?>
                        <tr>
                            <td><?php echo Labels::getLabel('LBL_SHIPPING_CHARGES', $siteLangId); ?></td>
                            <td><?php echo CommonHelper::displayMoneyFormat($cartSummary['shippingTotal']); ?></td>
                        </tr>
                    <?php } ?>
                    <tr>
                        <td><?php echo Labels::getLabel('LBL_Tax', $siteLangId); ?></td>
                        <td><?php echo CommonHelper::displayMoneyFormat($cartSummary['cartTaxTotal']); ?></td>
                    </tr>
                    <?php if (!empty($cartSummary['cartRewardPoints'])) {
                        $appliedRewardPointsDiscount = CommonHelper::convertRewardPointToCurrency($cartSummary['cartRewardPoints']); ?>
                        <tr>
                            <td><?php echo Labels::getLabel('LBL_Reward_point_discount', $siteLangId); ?></td>
                            <td><?php echo CommonHelper::displayMoneyFormat($appliedRewardPointsDiscount); ?></td>
                        </tr>
                    <?php
                    } ?>
                    <?php if (!empty($cartSummary['cartDiscounts'])) { ?>
                        <tr>
                            <td><?php echo Labels::getLabel('LBL_Discount', $siteLangId); ?></td>
                            <td><?php echo CommonHelper::displayMoneyFormat($cartSummary['cartDiscounts']['coupon_discount_total']); ?>
                            </td>
                        </tr>
                    <?php } ?>
                    <tr>
                        <td class="highlighted"><?php echo Labels::getLabel('LBL_Net_Payable', $siteLangId); ?></td>
                        <td class="highlighted">
                            <?php echo CommonHelper::displayMoneyFormat($cartSummary['orderNetAmount']); ?></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td><a href="javascript:void(0)" onClick="loadPaymentSummary();"
                                class="btn btn-outline-brand ripplelink block-on-mobile"><?php echo Labels::getLabel('LBL_PROCEED_TO_PAY', $siteLangId); ?>
                            </a></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        <?php if ($cartHasPhysicalProduct) { ?>
            $('.digital_product_tab-js').hide();
        <?php } ?>
        $(document).on("click", '.setactive-js li a', function() {
            var rel = $(this).attr('rel');
            if (rel == 'digital_product_tab') {
                $('.physical_product_tab-js').hide();
                $('.digital_product_tab-js').show();
            } else {
                $('.digital_product_tab-js').hide();
                $('.physical_product_tab-js').show();
            }
        });
    });
</script>