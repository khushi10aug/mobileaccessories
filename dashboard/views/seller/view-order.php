<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

if (!$print) {
    $this->includeTemplate('_partial/dashboardNavigation.php'); ?>
<?php
}

$shippingCharges = CommonHelper::orderProductAmount($orderDetail, 'shipping');

$orderStatusLbl = Labels::getLabel('LBL_AWAITING_SHIPMENT', $siteLangId);
$orderStatus = '';
if (isset($orderDetail["thirdPartyorderInfo"]['orderStatus'])) {
    $orderStatus = $orderDetail["thirdPartyorderInfo"]['orderStatus'];
    $orderStatusLbl = strpos($orderStatus, "_") ? str_replace('_', ' ', $orderStatus) : $orderStatus;
}
$pickUpDetails = NULL;

$now = time(); // or your date as well
$orderDate = strtotime($orderDetail['order_date_added']);
$datediff = $now - $orderDate;
$daysSpent = round($datediff / (60 * 60 * 24));

$transferBank = (isset($orderDetail['plugin_code']) && 'TransferBank' == $orderDetail['plugin_code']);
?>

<div class="content-wrapper content-space">
    <?php if (!$print) {
        $orderObj = new Orders();
        $notAllowedCancelStatuses = $orderObj->getNotAllowedOrderCancellationStatuses();
        $canCancelOrder = !in_array($orderDetail['orderstatus_id'], $notAllowedCancelStatuses);

        $data = [
            'headingLabel' => Labels::getLabel('LBL_View_Sale_Order', $siteLangId),
            'siteLangId' => $siteLangId,
            'headingBackButton' => [
                'href' => UrlHelper::generateUrl('Seller', 'sales'),
                'onclick' => '',
            ]
        ];

        if ($canCancelOrder && $canEdit) {
            $data['otherButtons'][] = [
                'attr' => [
                    'href' => UrlHelper::generateUrl('seller', 'cancelOrder', array($orderDetail['op_id'])),
                    'title' => Labels::getLabel('LBL_Cancel_Order', $siteLangId)
                ],
                'icon' => '<svg class="svg btn-icon-start" width="18" height="18">
                                <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#close">
                                </use>
                            </svg>',
                'label' => Labels::getLabel('LBL_Cancel_Order', $siteLangId)
            ];
        }
        $this->includeTemplate('_partial/header/content-header.php', $data, false);
    } ?>
    <div class="content-body">
        <div class="row">
            <?php
            $data = $this->variables + ['childOrderDetail' => $orderDetail, 'isSellerDashboardView' => true];
            $this->includeTemplate('_partial/order/left-side-block.php', $data, false);
            $data =  $data + [
                'canViewShippingCharges' => CommonHelper::canAvailShippingChargesBySeller($orderDetail['op_selprod_user_id'], $orderDetail['opshipping_by_seller_user_id']),
                'canViewTaxCharges' => $orderDetail['op_tax_collected_by_seller'],
                'sellerView' => true                
            ];
            $this->includeTemplate('_partial/order/right-side-block.php', $data, false);
            ?>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        setTimeout(function() {
            $('.printBtn-js').fadeIn();
        }, 500);
        $(document).on('click', '.printBtn-js', function() {
            $('.printFrame-js').show();
            setTimeout(function() {
                frames['frame'].print();
                $('.printFrame-js').hide();
            }, 500);
        });
    });
    var canShipByPlugin = <?php echo (!empty($shippingApiObj) ? 1 : 0); ?>;
    var orderShippedStatus = <?php echo FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS"); ?>;
</script>