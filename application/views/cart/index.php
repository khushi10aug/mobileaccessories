<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div id="body" class="body bg-gray-darkx">
    <section class="">
        <div class="container">
            <div id="js-cart-listing">
                <?php if ($total > 0) { ?>
                    <div class="cart-page">
                        <main class="cart-page_main">
                            <div class="cart-page-head">
                                <h2 class="h2"><?php echo Labels::getLabel('LBL_YOUR_SHOPPING_BAG'); ?></h2>
                                <?php if ($hasPhysicalProduct) { ?>
                                    <ul class="shiporpickup" id="js-shiporpickup">
                                        <li class="shiporpickup-item" onclick="listCartProducts(<?php echo Shipping::FULFILMENT_SHIP; ?>)">
                                            <label class="control-label radio is-active shippingLblJs">
                                                <input class="control-input" type="radio" id="shipping" name="fulfillment_type" value="<?php echo Shipping::FULFILMENT_SHIP; ?>" <?php echo ($pickUpProductsCount == 0) ? "checked='true'" : ''; ?>>
                                                <svg class="svg" width="18" height="18">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#shipping">
                                                    </use>
                                                </svg><?php echo Labels::getLabel('LBL_SHIP_MY_ORDER', $siteLangId); ?>
                                            </label>
                                        </li>
                                        <li class="shiporpickup-item" onclick="listCartProducts(<?php echo Shipping::FULFILMENT_PICKUP; ?>)">
                                            <label class="control-label radio pickupLblJs">
                                                <input class="control-input" type="radio" id="pickup" name="fulfillment_type" value="<?php echo Shipping::FULFILMENT_PICKUP; ?>" <?php echo $shipProductsCount == 0 ? "checked='true'" : ''; ?>>
                                                <svg class="svg" width="18" height="18">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#store">
                                                    </use>
                                                </svg>
                                                <?php echo Labels::getLabel('LBL_PICKUP_IN_STORE', $siteLangId); ?>
                                            </label>
                                        </li>
                                    </ul>
                                <?php } ?>
                            </div>
                            <div class="processing-wrap" id="cartList">
                                <?php include(CONF_THEME_PATH . 'cart/_partial/items-summary-skeleton.php'); ?>
                            </div>
                        </main>
                        <aside class="cart-page_aside">
                            <div class="sticky-summary" id="sticky-summary">
                                <div class="cart-total">
                                    <div class="cart-total-head">
                                        <h3 class="cart-total-title">
                                            <?php echo Labels::getLabel('LBL_Summary', $siteLangId); ?>
                                        </h3>
                                    </div>
                                    <div class="cart-total-body">
                                        <div id="js-cartFinancialSummary">
                                            <?php include(CONF_THEME_PATH . 'cart/_partial/summary-skeleton.php'); ?>
                                        </div>
                                    </div>
                                    <div class="cart-total-foot">
                                        <div class="cart-action">
                                            <button class="btn btn-brand btn-block" type="button" onclick="goToCheckout()"><?php echo Labels::getLabel('LBL_Checkout', $siteLangId); ?>
                                            </button>
                                            <a class="link-underline" href="<?php echo UrlHelper::generateUrl(); ?>"><?php echo Labels::getLabel('LBL_Continue_Shopping', $siteLangId); ?></a>
                                        </div>
                                        <div class="secure">
                                            <img class="svg" width="32" height="32" src="<?php echo CONF_WEBROOT_URL; ?>images/retina/shield-fill-check.svg" alt="">
                                            <p> <?php echo Labels::getLabel('LBL_Safe_and_Secure_Payments_Easy_returns_100%_Authentic_products', $siteLangId); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </aside>
                    </div>
                <?php } ?>
            </div>
        </div>
    </section>
</div>