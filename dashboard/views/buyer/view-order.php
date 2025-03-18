<?php defined('SYSTEM_INIT') or die('Invalid Usage . ');
$canCancelOrder = true;
$canReturnRefund = true;
$canReviewOrders = false;
$canSubmitFeedback = false;
$selProdTotalSpecialPrice = 0;

if (true == $primaryOrder) {
    $canReturnRefund = (in_array($childOrderDetail["op_status_id"], (array) Orders::getBuyerAllowedOrderReturnStatuses($childOrderDetail['op_product_type'])));
    if ($childOrderDetail['op_product_type'] == Product::PRODUCT_TYPE_DIGITAL) {
        $canCancelOrder = (in_array($childOrderDetail["op_status_id"], (array) Orders::getBuyerAllowedOrderCancellationStatuses(true)));
    } else {
        $canCancelOrder = (in_array($childOrderDetail["op_status_id"], (array) Orders::getBuyerAllowedOrderCancellationStatuses()));
        $datediff = time() - strtotime($childOrderDetail['order_date_added']);
        $daysSpent = $datediff / (60 * 60 * 24);
        $returnAge = $childOrderDetail['op_selprod_return_age'];
        $canReturnRefund = $canReturnRefund && $returnAge > $daysSpent;
        $canCancelOrder = $canCancelOrder && $childOrderDetail['op_selprod_cancellation_age'] > $daysSpent;
    }

    if (in_array($childOrderDetail["op_status_id"], SelProdReview::getBuyerAllowedOrderReviewStatuses())) {
        $canReviewOrders = true;
    }

    $canSubmitFeedback = Orders::canSubmitFeedback($childOrderDetail['order_user_id'], $childOrderDetail['order_id'], $childOrderDetail['op_selprod_id']);
    $selProdTotalSpecialPrice += $childOrderDetail['op_special_price'] * $childOrderDetail["op_qty"];

    $cartTotal = CommonHelper::orderProductAmount($childOrderDetail, 'CART_TOTAL');
    $disc = CommonHelper::orderProductAmount($childOrderDetail, 'DISCOUNT');
    $volumeDiscount = CommonHelper::orderProductAmount($childOrderDetail, 'VOLUME_DISCOUNT');
    $totalSaving = $selProdTotalSpecialPrice + abs($disc) + abs($volumeDiscount);
} else {
    $firstOrderInfo = current($childOrderDetail);
    $cartTotal = 0;
    foreach ($childOrderDetail as $childOrder) {
        $selProdTotalSpecialPrice += $childOrder['op_special_price'] * $childOrder["op_qty"];
        $cartTotal += $childOrder["op_unit_price"] * $childOrder["op_qty"];
    }
    $totalSaving = $selProdTotalSpecialPrice + $firstOrderInfo['order_discount_total'] + $firstOrderInfo['order_volume_discount_total'];
}

$opshippingDate = $timeSlotFrom = $timeSlotTo = "";
if (true == $primaryOrder) {
    $opshippingDate = isset($childOrderDetail['opshipping_date']) ? $childOrderDetail['opshipping_date'] . ' ' : '';
    $timeSlotFrom = isset($childOrderDetail['opshipping_time_slot_from']) ? date('H:i', strtotime($childOrderDetail['opshipping_time_slot_from'])) . ' - ' : '';
    $timeSlotTo = isset($childOrderDetail['opshipping_time_slot_to']) ? date('H:i', strtotime($childOrderDetail['opshipping_time_slot_to'])) : '';
}

if (!$print) {
    $this->includeTemplate('_partial/dashboardNavigation.php');
} ?>

<div class="content-wrapper content-space">
    <?php if (!$print) {
        $data = [
            'headingLabel' => Labels::getLabel('LBL_Order_Details', $siteLangId),
            'siteLangId' => $siteLangId,
            'headingBackButton' => [
                'href' => UrlHelper::generateUrl('Buyer', 'Orders'),
            ]
        ];

        if (true == $primaryOrder && !$print) {
            if ($canCancelOrder) {
                $data['otherButtons'][] = [
                    'attr' => [
                        'href' => UrlHelper::generateUrl('Buyer', 'orderCancellationRequest', array($childOrderDetail['op_id'])),
                        'title' => Labels::getLabel('LBL_CANCEL_ORDER', $siteLangId)
                    ],
                    'icon' => '<svg class="svg btn-icon-start" width="18" height="18">
                        <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#close">
                        </use>
                    </svg>',
                    'label' => Labels::getLabel('LBL_CANCEL_ORDER', $siteLangId)
                ];
            }

            if (FatApp::getConfig("CONF_ALLOW_REVIEWS", FatUtility::VAR_INT, 0) && $canReviewOrders && $canSubmitFeedback) {
                $data['otherButtons'][] = [
                    'attr' => [
                        'href' => UrlHelper::generateUrl('Buyer', 'orderFeedback', array($childOrderDetail['op_id'])),
                        'title' => Labels::getLabel('LBL_Feedback', $siteLangId)
                    ],
                    'label' => Labels::getLabel('LBL_Feedback', $siteLangId)
                ];
            }

            if ($canReturnRefund) {
                $data['otherButtons'][] = [
                    'attr' => [
                        'href' => UrlHelper::generateUrl('Buyer', 'orderReturnRequest', array($childOrderDetail['op_id'])),
                        'title' => Labels::getLabel('LBL_Refund', $siteLangId)
                    ],
                    'label' => Labels::getLabel('LBL_Refund', $siteLangId)
                ];
            }
        }
        $this->includeTemplate('_partial/header/content-header.php', $data, false);
    } ?>
    <div class="content-body">
        <div class="row">
            <?php $this->includeTemplate('_partial/order/left-side-block.php', $this->variables + ['isSellerDashboardView' => false], false);
            $data = $this->variables + [
                'canViewShippingCharges' => true,
                'canViewTaxCharges' => true,
                'isSellerDashboardView' => false
            ];
            $this->includeTemplate('_partial/order/right-side-block.php', $data, false); ?>
        </div>

    </div>
</div>

<script>
    $(document).ready(function () {
        setTimeout(function () {
            $('.printBtn-js').fadeIn();
        }, 500);
        $(document).on('click', '.printBtn-js', function () {
            $('.printFrame-js').show();
            setTimeout(function () {
                frames['frame'].print();
                $('.printFrame-js').hide();
            }, 500);
        });
    });

    function increaseDownloadedCount(linkId, opId) {
        fcom.ajax(fcom.makeUrl('buyer', 'downloadDigitalProductFromLink', [linkId, opId]), '', function (t) {
            var ans = $.parseJSON(t);
            if (ans.status == 0) {
                fcom.displayErrorMessage(ans.msg);
                return false;
            }
            location.reload();
            return true;
        });
    }

    trackOrder = function (trackingNumber, courier, orderNumber) {
        fcom.displayProcessing();
        fcom.ajax(fcom.makeUrl('Buyer', 'orderTrackingInfo', [trackingNumber, courier, orderNumber]), '', function (res) {
            $.ykmsg.close();
            $.ykmodal(res);
        });
    };
</script>