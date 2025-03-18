<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

$primaryOrder = $primaryOrder ?? true;
$cancelOrder = $cancelOrder ?? false;;
?>
<div class="col-md-8">
    <div class="card">
        <div class="card-head">
            <h5 class="card-title">
                <div class="order-number">
                    <small class="sm-txt"><?php echo Labels::getLabel('LBL_ORDER', $siteLangId); ?></small>
                    <span class="numbers">
                        <?php echo '#' . ((true == $primaryOrder) ? $childOrderDetail['op_invoice_number'] : $orderDetail['order_number']); ?>
                        <?php
                        if (true == $primaryOrder && FatApp::getConfig("CONF_DEFAULT_CANCEL_ORDER_STATUS") == $childOrderDetail['orderstatus_id']) {
                            $statusName = isset($childOrderDetail['orderstatus_name']) ? $childOrderDetail['orderstatus_name'] : $childOrderDetail['orderstatus_identifier']; ?>
                            <span class="badge badge-danger ms-2">
                                <?php echo $statusName; ?>
                            </span>
                        <?php } ?>
                    </span>
                </div>
            </h5>
            <?php if (false === $cancelOrder) { ?>
                <div class="dropdown">
                    <button class="btn btn-icon btn-outline-gray btn-sm" type="button" id="dropdownMenuButton1"
                        data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                        <svg class="svg" width="20" height="20">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#three-dots">
                            </use>
                        </svg>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                        <?php if ($isSellerDashboardView) { ?>
                            <li class="dropdown-menu-item">
                                <a class="dropdown-menu-link no-print" target="_blank"
                                    href="<?php echo UrlHelper::generateUrl('Seller', 'viewInvoice', [$orderDetail['op_id']]); ?>"
                                    title="
                                <?php echo Labels::getLabel('LBL_ORDER_RECEIPT', $siteLangId); ?>">
                                    <span><?php echo Labels::getLabel('LBL_ORDER_RECEIPT', $siteLangId); ?></span>
                                </a>
                            </li>
                            <li class="dropdown-menu-item">
                                <a class="dropdown-menu-link no-print" target="_blank"
                                    href="<?php echo UrlHelper::generateUrl('Account', 'viewBuyerOrderInvoice', [$orderDetail['order_id'], $orderDetail['op_id']]); ?>"
                                    title="<?php echo Labels::getLabel('LBL_BUYER_ORDER_RECEIPT', $siteLangId); ?>">
                                    <span><?php echo Labels::getLabel('LBL_BUYER_ORDER_RECEIPT', $siteLangId); ?></span>
                                </a>
                            </li>
                            <?php
                            if (!in_array($orderDetail['op_status_id'], unserialize(FatApp::getConfig("CONF_COMPLETED_ORDER_STATUS"))) && $orderDetail['opshipping_fulfillment_type'] == Shipping::FULFILMENT_SHIP && $shippedBySeller && is_object($shippingApiObj) && ('CashOnDelivery' == $orderDetail['plugin_code'] || Orders::ORDER_PAYMENT_PAID == $orderDetail['order_payment_status']) && false === OrderCancelRequest::getCancelRequestById($orderDetail['op_id'])) {

                                $opId = $orderDetail['op_id'];
                                if (0 < $orderDetail['opshipping_rate_id'] && (empty($orderDetail['opshipping_plugin_id']) || ($shippingApiObj->getKey('plugin_id') != $orderDetail['opshipping_plugin_id'] && empty($orderDetail['opr_response'])))) {
                            ?>
                                    <li class="dropdown-menu-item">
                                        <a class="dropdown-menu-link no-print" href="javascript:void(0)"
                                            onclick="shippingRatesForm(<?php echo $opId; ?>)"
                                            title="<?php echo Labels::getLabel('LBL_FETCH_SHIPPING_RATES', $siteLangId); ?>">
                                            <span><?php echo Labels::getLabel('LBL_FETCH_SHIPPING_RATES', $siteLangId); ?></span>
                                        </a>
                                    </li>

                                    <?php
                                } else {
                                    if ($shippingApiObj->getKey('plugin_id') == $orderDetail['opshipping_plugin_id']) {
                                        if (empty($orderDetail['opr_response']) && empty($orderDetail['opship_tracking_number']) && true === $shippingApiObj->canGenerateLabelSeparately()) {
                                            $orderId = $orderDetail['order_id'];
                                    ?>
                                            <li class="dropdown-menu-item">
                                                <a class="dropdown-menu-link no-print" href="javascript:void(0)"
                                                    onclick='generateLabel(<?php echo $opId; ?>)'
                                                    title="<?php echo Labels::getLabel('LBL_GENERATE_LABEL', $siteLangId); ?>">
                                                    <span><?php echo Labels::getLabel('LBL_GENERATE_LABEL', $siteLangId); ?></span>
                                                </a>
                                            </li>
                                            <?php
                                        } elseif (!empty($orderDetail['opr_response'])) {
                                            if (FatApp::getConfig("CONF_RETURN_REQUEST_APPROVED_ORDER_STATUS") == $orderDetail["op_status_id"]) {
                                            ?>
                                                <li class="dropdown-menu-item">
                                                    <a class="dropdown-menu-link no-print" target="_blank"
                                                        href="<?php echo UrlHelper::generateUrl("ShippingServices", 'previewReturnLabel', [$orderDetail['op_id']]); ?>"
                                                        title="<?php echo Labels::getLabel('LBL_PREVIEW_RETURN_LABEL', $siteLangId); ?>">
                                                        <span><?php echo Labels::getLabel('LBL_PREVIEW_RETURN_LABEL', $siteLangId); ?></span>
                                                    </a>
                                                </li>
                                            <?php } else { ?>
                                                <li class="dropdown-menu-item">
                                                    <a class="dropdown-menu-link no-print" target="_blank"
                                                        href="<?php echo UrlHelper::generateUrl("ShippingServices", 'previewLabel', [$orderDetail['op_id']]); ?>"
                                                        title="<?php echo Labels::getLabel('LBL_PREVIEW_LABEL', $siteLangId); ?>">
                                                        <span><?php echo Labels::getLabel('LBL_PREVIEW_LABEL', $siteLangId); ?></span>
                                                    </a>
                                                </li>
                                            <?php } ?>
                                        <?php
                                        }
                                        if ((!empty($orderStatus) && 'awaiting_shipment' == $orderStatus && !empty($orderDetail['opr_response']) || ($shippingApiObj->canGenerateLabelFromShipment() || !empty($orderDetail['opship_tracking_number']))) && empty($orderDetail['opship_order_number'])) {
                                            if (true === $shippingApiObj->canGenerateLabelFromShipment()) {
                                                $label = Labels::getLabel('LBL_BUY_SHIPMENT_&_GENERATE_LABEL', $siteLangId);
                                            } else {
                                                $label = Labels::getLabel('LBL_BUY_SHIPMENT', $siteLangId);
                                            }
                                        ?>
                                            <li class="dropdown-menu-item">
                                                <a class="dropdown-menu-link no-print" href="javascript:void(0)"
                                                    onclick="proceedToShipment(<?php echo $opId; ?>)" title="<?php echo $label; ?>">
                                                    <span><?php echo $label; ?></span>
                                                </a>
                                            </li>
                                        <?php
                                        }

                                        if ($orderDetail['orderstatus_id'] == FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS") && true === $shippingApiObj->canCreatePickup()) {
                                        ?>
                                            <?php
                                            $pickUpDetails = OrderProduct::getPickUpShedule($opId);
                                            if (!$pickUpDetails || 1 > $pickUpDetails['opsp_scheduled']) {
                                            ?>
                                                <li class="dropdown-menu-item">
                                                    <a class="dropdown-menu-link no-print" href="javascript:void(0)"
                                                        onclick="getPickupForm(<?php echo $opId; ?>)"
                                                        title="<?php echo Labels::getLabel('LBL_CREATE_PICKUP', $siteLangId); ?>">
                                                        <span><?php echo Labels::getLabel('LBL_CREATE_PICKUP', $siteLangId); ?></span>
                                                    </a>
                                                </li>
                                            <?php } else { ?>
                                                <li>
                                                    <a class="dropdown-menu-link no-print" href="javascript:void(0)"
                                                        onclick="cancelPickup(<?php echo $opId; ?>)"
                                                        title="<?php echo Labels::getLabel('LBL_CANCEL_PICKUP', $siteLangId); ?>">
                                                        <span><?php echo Labels::getLabel('LBL_CANCEL_PICKUP', $siteLangId); ?></span>
                                                    </a>
                                                </li>
                                            <?php } ?>
                                <?php }
                                    }
                                }
                            }
                        } else {
                            if (1 > FatApp::getConfig('CONF_HIDE_PRICES', FatUtility::VAR_INT, 0)) { ?>
                                <li class="dropdown-menu-item">
                                    <a class="dropdown-menu-link no-print" href="javascript:void(0)" onclick="return addItemsToCart('<?php echo $orderDetail['order_id']; ?>');"><?php echo Labels::getLabel('LBL_Buy_Again', $siteLangId); ?></a>
                                </li>
                            <?php } ?>
                            <li class="dropdown-menu-item">
                                <a class="dropdown-menu-link no-print"
                                    href="<?php echo (0 < $opId) ? UrlHelper::generateUrl('Account', 'viewBuyerOrderInvoice', [$orderDetail['order_id'], $opId]) : UrlHelper::generateUrl('Account', 'viewBuyerOrderInvoice', [$orderDetail['order_id']]); ?>"
                                    title="<?php echo Labels::getLabel('LBL_PRINT_BUYER_INVOICE', $siteLangId); ?>"><?php echo Labels::getLabel('LBL_ORDER_RECEIPT', $siteLangId); ?></a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            <?php } ?>
        </div>
        <div class="card-table">
            <div class="js-scrollable table-wrap">
                <table class="table table-justified table-orders">
                    <thead>
                        <tr>
                            <th><?php echo Labels::getLabel('LBL_ITEMS_SUMMARY', $siteLangId); ?></th>
                            <th><?php echo Labels::getLabel('LBL_PRICE', $siteLangId); ?></th>
                            <th><?php echo Labels::getLabel('LBL_ORDERED_QUANTITY', $siteLangId); ?></th>
                            <th><?php echo Labels::getLabel('LBL_TOTAL', $siteLangId); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($arr as $childOrder) {
                            $shippingCost = CommonHelper::orderProductAmount($childOrder, 'SHIPPING', false, ($isSellerDashboardView ? User::USER_TYPE_SELLER : User::USER_TYPE_BUYER));
                            $volumeDiscount = CommonHelper::orderProductAmount($childOrder, 'VOLUME_DISCOUNT');
                            $rewardPoint = CommonHelper::orderProductAmount($childOrder, 'REWARDPOINT');
                            $discount = CommonHelper::orderProductAmount($childOrder, 'DISCOUNT');
                            $tax = CommonHelper::orderProductAmount($childOrder, 'TAX', false, ($isSellerDashboardView ? User::USER_TYPE_SELLER : User::USER_TYPE_BUYER));
                            $taxableAmount = CommonHelper::orderProductAmount($childOrder, 'TAXABLE_AMOUNT', false, ($isSellerDashboardView ? User::USER_TYPE_SELLER : User::USER_TYPE_BUYER));
                        ?>
                            <tr>
                                <td>
                                    <?php $this->includeTemplate('_partial/product/product-info-html.php', $this->variables + ['order' => $childOrder], false); ?>
                                </td>
                                <td>
                                    <?php if ($childOrder['op_selprod_price'] > $childOrder['op_unit_price']) { ?>
                                        <strong>
                                        <?php
                                    }
                                    echo CommonHelper::displayMoneyFormat($childOrder['op_unit_price'], true, false, true, false, true); ?>
                                        <?php
                                        if (isset($childOrder['op_special_price']) && 0 < $childOrder['op_special_price'] && $childOrder['op_selprod_price'] > $childOrder['op_unit_price']) { ?>
                                        </strong>
                                        <br />
                                        <del>
                                            <?php echo CommonHelper::displayMoneyFormat($childOrder['op_selprod_price'], true, false, true, false, true); ?>
                                        </del>
                                    <?php
                                        } ?>
                                </td>
                                <td><?php echo $childOrder['op_qty']; ?></td>
                                <td>
                                    <span class="d-inline-block link-dotted" tabindex="0" data-bs-toggle="popover"
                                        data-bs-placement="left" data-bs-trigger="hover focus"
                                        data-popover-html="#price-<?php echo $childOrder['op_id']; ?>">
                                        <?php echo CommonHelper::displayMoneyFormat(CommonHelper::orderProductAmount($childOrder, 'NETAMOUNT', false, ($isSellerDashboardView ? User::USER_TYPE_SELLER : User::USER_TYPE_BUYER)), true, false, true, false, true); ?>
                                    </span>
                                    <div class="hidden" id="price-<?php echo $childOrder['op_id']; ?>">
                                        <ul class="list-stats list-stats-popover">
                                            <li class="list-stats-item">
                                                <span class="lable"><?php echo Labels::getLabel('LBL_UNIT_PRICE:'); ?>
                                                </span>
                                                <span
                                                    class="value"><?php echo CommonHelper::displayMoneyFormat($childOrder['op_unit_price'], true, false, true, false, true); ?>
                                                    (* <?php echo $childOrder['op_qty']; ?>)</span>
                                            </li>
                                            <?php if (!empty($volumeDiscount)) { ?>
                                                <li class="list-stats-item">
                                                    <span
                                                        class="lable"><?php echo Labels::getLabel('LBL_VOLUME_DISCOUNT:'); ?></span>
                                                    <span
                                                        class="value"><?php echo CommonHelper::displayMoneyFormat($volumeDiscount, true, false, true, false, true); ?></span>
                                                </li>
                                            <?php }
                                            if (false === $isSellerDashboardView && $childOrder['op_tax_after_discount']) { ?>
                                                <?php if (!empty($discount)) { ?>
                                                    <li class="list-stats-item">
                                                        <span class="lable"><?php echo Labels::getLabel('LBL_DISCOUNT:'); ?></span>
                                                        <span
                                                            class="value"><?php echo CommonHelper::displayMoneyFormat($discount, true, false, true, false, true); ?></span>
                                                    </li>
                                                <?php } ?>
                                                <?php if (!empty($rewardPoint)) { ?>
                                                    <li class="list-stats-item">
                                                        <span
                                                            class="lable"><?php echo Labels::getLabel('LBL_REWARD_POINTS_DISCOUNT:'); ?></span>
                                                        <span
                                                            class="value"><?php echo CommonHelper::displayMoneyFormat($rewardPoint, true, false, true, false, true); ?></span>
                                                    </li>
                                                <?php } ?>
                                            <?php } ?>
                                            <?php if (0 < $tax) { ?>
                                                <li class="list-stats-item">
                                                    <span
                                                        class="lable"><?php echo Labels::getLabel('LBL_TAXABLE_AMOUNT:'); ?></span>
                                                    <span
                                                        class="value"><?php echo CommonHelper::displayMoneyFormat($taxableAmount, true, false, true, false, true); ?></span>
                                                </li>

                                                <li class="list-stats-item">
                                                    <span class="lable"><?php echo Labels::getLabel('LBL_TAX:'); ?></span>
                                                    <span
                                                        class="value"><?php echo CommonHelper::displayMoneyFormat($tax, true, false, true, false, true); ?></span>
                                                </li>
                                            <?php } ?>
                                            <?php if (false === $isSellerDashboardView && !$childOrder['op_tax_after_discount']) { ?>
                                                <?php if (!empty($discount)) { ?>
                                                    <li class="list-stats-item">
                                                        <span class="lable"><?php echo Labels::getLabel('LBL_DISCOUNT:'); ?></span>
                                                        <span
                                                            class="value"><?php echo CommonHelper::displayMoneyFormat($discount, true, false, true, false, true); ?></span>
                                                    </li>
                                                <?php } ?>
                                                <?php if (!empty($rewardPoint)) { ?>
                                                    <li class="list-stats-item">
                                                        <span
                                                            class="lable"><?php echo Labels::getLabel('LBL_REWARD_POINTS_DISCOUNT:'); ?></span>
                                                        <span
                                                            class="value"><?php echo CommonHelper::displayMoneyFormat($rewardPoint, true, false, true, false, true); ?></span>
                                                    </li>
                                                <?php } ?>
                                            <?php } ?>
                                            <?php if (0 < $shippingCost) { ?>
                                                <li class="list-stats-item">
                                                    <span class="lable"><?php echo Labels::getLabel('LBL_SHIPPING:'); ?></span>
                                                    <span
                                                        class="value"><?php echo CommonHelper::displayMoneyFormat($shippingCost, true, false, true, false, true); ?></span>
                                                </li>
                                            <?php } ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php
    if (true == $primaryOrder) { ?>
        <div class="card">
            <div class="card-head">
                <div class="card-head-label">
                    <h5 class="card-title"><?php echo Labels::getLabel('MSG_ORDER_TIMELINE', $siteLangId); ?></h5>
                </div>
            </div>
            <div class="card-body">
                <div class="timelines-wrap">
                    <?php $this->includeTemplate('_partial/order/timeline.php', $this->variables, false); ?>
                </div>
            </div>
        </div>
    <?php } ?>
    <?php
    if ($isSellerDashboardView) {
        if (isset($cancelForm)) {
            include CONF_THEME_PATH . 'seller/_partial/order-cancel-form.php';
        } else {
            include CONF_THEME_PATH . 'seller/_partial/order-update-form.php';
        }
    } else {
        include CONF_THEME_PATH . 'buyer/partial-view-order.php';
    }
    ?>
</div>