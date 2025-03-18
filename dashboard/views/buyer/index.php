<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$this->includeTemplate('_partial/dashboardNavigation.php');
?>
<div class="content-wrapper content-space">
    <?php
    $data = [
        'headingLabel' => Labels::getLabel('LBL_DASHBOARD', $siteLangId),
        'siteLangId' => $siteLangId,
        'otherButtons' => [
            [
                'attr' => [
                    'href' => UrlHelper::generateUrl('Account', 'wishlist'),
                    'title' => Labels::getLabel('LBL_FAVORITES', $siteLangId)
                ],
                'icon' => '<svg class="svg btn-icon-start" width="18" height="18">
                    <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#favourite">
                    </use>
                </svg>',
                'label' => FatApp::getConfig('CONF_ADD_FAVORITES_TO_WISHLIST', FatUtility::VAR_INT, 1) ? Labels::getLabel('NAV_WISHLIST', $siteLangId) : Labels::getLabel('LBL_FAVORITES', $siteLangId)
            ],
            [
                'attr' => [
                    'href' => UrlHelper::generateUrl('Account', 'myAddresses'),
                    'title' => Labels::getLabel('LBL_MANAGE_ADDRESSES', $siteLangId)
                ],
                'icon' => '<svg class="svg btn-icon-start" width="18" height="18">
                <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#addresses">
                </use>
            </svg>',
                'label' => Labels::getLabel('LBL_ADDRESSES', $siteLangId)
            ],
        ]
    ];
    $this->includeTemplate('_partial/header/content-header.php', $data, false); ?>

    <div class="content-body">
        <div class="row">
            <div class="col-lg-4 order-lg-2">
                <div class="widget-scroll">
                    <div class="widget widget-stats">
                        <a href="<?php echo UrlHelper::generateUrl('account', 'credits'); ?>">
                            <div class="card card-commerce card-commerce-bg" style="background-image: url(<?php echo CONF_WEBROOT_URL; ?>/images/card-commerce-bg-1.png);">
                                <div class="card-head border-0">
                                    <h5 class="card-title"><?php echo Labels::getLabel('LBL_Credits', $siteLangId); ?></h5>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="stats">
                                        <div class="stats-number">
                                            <ul>
                                                <li><span class="total"><?php echo Labels::getLabel('LBL_AVAILABLE_BALANCE', $siteLangId); ?></span>
                                                    <span class="total-numbers"><?php echo CommonHelper::displayMoneyFormat($userBalance); ?></span>
                                                </li>
                                                <li>
                                                    <span class="total"><?php echo Labels::getLabel('LBL_CREDITED_TODAY', $siteLangId); ?></span>
                                                    <span class="total-numbers"><?php echo CommonHelper::displayMoneyFormat($txnsSummary['total_earned']); ?></span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="widget widget-stats">
                        <a href="<?php echo UrlHelper::generateUrl('buyer', 'orders'); ?>">
                            <div class="card card-commerce card-commerce-bg" style="background-image: url(<?php echo CONF_WEBROOT_URL; ?>/images/card-commerce-bg-2.png);">
                                <div class="card-head border-0">
                                    <h5 class="card-title"><?php echo Labels::getLabel('LBL_Orders', $siteLangId); ?></h5>

                                </div>
                                <div class="card-body pt-0">
                                    <div class="stats">
                                        <div class="stats-number">
                                            <ul>
                                                <li><span class="total"><?php echo Labels::getLabel('LBL_Total_Orders', $siteLangId); ?></span>
                                                    <span class="total-numbers"><?php echo $ordersCount; ?></span>
                                                </li>
                                                <li><span class="total"><?php echo Labels::getLabel('LBL_Pending_Orders', $siteLangId); ?></span>
                                                    <span class="total-numbers"><?php echo $pendingOrderCount; ?></span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="widget widget-stats">
                        <a href="<?php echo UrlHelper::generateUrl('buyer', 'rewardPoints'); ?>">
                            <div class="card card-commerce card-commerce-bg" style="background-image: url(<?php echo CONF_WEBROOT_URL; ?>/images/card-commerce-bg-3.png);">
                                <div class="card-head border-0">
                                    <h5 class="card-title"><?php echo Labels::getLabel('LBL_Reward_Points', $siteLangId); ?>
                                    </h5>

                                </div>
                                <div class="card-body pt-0">
                                    <div class="stats">
                                        <div class="stats-number">
                                            <ul>
                                                <li><span class="total"><?php echo Labels::getLabel('LBL_Current_Reward_Points', $siteLangId); ?></span>
                                                    <span class="total-numbers"> <?php echo $totalRewardPoints; ?></span>
                                                </li>
                                                <li>
                                                    <span class="total"><?php echo Labels::getLabel('LBL_Currency_Value', $siteLangId); ?></span>
                                                    <span class="total-numbers">
                                                        <?php echo CommonHelper::displayMoneyFormat(CommonHelper::convertRewardPointToCurrency($totalRewardPoints)); ?></span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-head border-0">
                        <h5 class="card-title"><?php echo Labels::getLabel('LBL_Latest_Orders', $siteLangId); ?>
                        </h5>
                        <div class="action">
                            <?php if (count($orders) > 0) { ?>
                                <a href="<?php echo UrlHelper::generateUrl('buyer', 'orders'); ?>" class="link"><?php echo Labels::getLabel('Lbl_View_All', $siteLangId); ?></a>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="card-table">
                        <div class="js-scrollable table-wrap table-responsive">
                            <table class="table table-justified">
                                <thead>
                                    <tr class="">
                                        <th width="50%">
                                            <?php echo Labels::getLabel('LBL_Order_Particulars', $siteLangId); ?>
                                        </th>
                                        <th width="20%">
                                            <?php echo Labels::getLabel('LBL_Amount', $siteLangId); ?>
                                        </th>
                                        <th width="20%">
                                            <?php echo Labels::getLabel('LBL_Status', $siteLangId); ?>
                                        </th>
                                        <th width="10%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($orders) > 0) {
                                        $canCancelOrder = true;
                                        $canReturnRefund = true;
                                       
                                        foreach ($orders as $orderId => $row) {
                                            $orderDetailUrl = UrlHelper::generateUrl('Buyer', 'viewOrder', array($row['order_id'], $row['op_id']));
                                            $canReturnRefund = (in_array($row["op_status_id"], (array)Orders::getBuyerAllowedOrderReturnStatuses($row['op_product_type'])));
                                            if ($row['op_product_type'] == Product::PRODUCT_TYPE_DIGITAL) {
                                                $canCancelOrder = (in_array($row["op_status_id"], (array)Orders::getBuyerAllowedOrderCancellationStatuses(true)));                                                
                                            } else {
                                                $canCancelOrder = (in_array($row["op_status_id"], (array)Orders::getBuyerAllowedOrderCancellationStatuses()));                                                         
                                                $datediff = time() - strtotime($row['order_date_added']);
                                                $daysSpent = $datediff / (60 * 60 * 24);
                                                $returnAge = $row['op_selprod_return_age'];  
                                                $canReturnRefund =  $canReturnRefund && $returnAge > $daysSpent;
                                                $canCancelOrder = $canCancelOrder && $row['op_selprod_cancellation_age'] > $daysSpent;
                                            
                                            }
                                            $isValidForReview = false;
                                            if (in_array($row["op_status_id"], SelProdReview::getBuyerAllowedOrderReviewStatuses())) {
                                                $isValidForReview = true;
                                            }
                                            $canSubmitFeedback = Orders::canSubmitFeedback($row['order_user_id'], $row['order_id'], $row['op_selprod_id']); ?>
                                            <tr>
                                                <td>
                                                    <?php
                                                    echo $this->includeTemplate('_partial/product/product-info-html.php', ['order' => $row, 'siteLangId' => $siteLangId, 'showDate' => true], false, true); ?>
                                                </td>
                                                <td>
                                                    <?php echo CommonHelper::displayMoneyFormat(CommonHelper::orderProductAmount($row)); ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-inline <?php echo $classArr[$row['orderstatus_color_class']]; ?>">
                                                        <?php echo $row['orderstatus_name']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <ul class="actions">
                                                        <li><a title="<?php echo Labels::getLabel('LBL_View_Order', $siteLangId); ?>" href="<?php echo $orderDetailUrl; ?>"><svg class="svg" width="18" height="18">
                                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#view">
                                                                    </use>
                                                                </svg></a></li>
                                                        <?php if ($canCancelOrder) { ?> <li><a href="<?php echo UrlHelper::generateUrl('buyer', 'orderCancellationRequest', array($row['op_id'])); ?>" title="<?php echo Labels::getLabel('LBL_Cancel_Order', $siteLangId); ?>"><svg class="svg btn-icon-start" width="18" height="18">
                                                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#close">
                                                                        </use>
                                                                    </svg></a></li> <?php } ?>
                                                        <?php if ($canSubmitFeedback && $isValidForReview) { ?> <li><a href="<?php echo UrlHelper::generateUrl('Buyer', 'orderFeedback', array($row['op_id'])); ?>" title="<?php echo Labels::getLabel('LBL_Feedback', $siteLangId); ?>">
                                                                    <svg class="svg" width="18" height="18">
                                                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#comment">
                                                                        </use>
                                                                    </svg></a>
                                                            </li> <?php } ?> <?php if ($canReturnRefund) { ?> <li><a href="<?php echo UrlHelper::generateUrl('Buyer', 'orderReturnRequest', array($row['op_id'])); ?>" title="<?php echo Labels::getLabel('LBL_Refund', $siteLangId); ?>"><svg class="svg btn-icon-start" width="18" height="18">
                                                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#order-return">
                                                                        </use>
                                                                    </svg></a></li> <?php } ?>
                                                    </ul>
                                                </td>
                                            </tr> <?php }
                                            } else { ?> <tr>
                                            <td colspan="4">
                                                <?php $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId), false); ?>
                                            </td>
                                        </tr> <?php } ?>
                                </tbody>

                            </table>
                        </div>
                    </div>
                </div>


                <div class="card">
                    <div class="card-head border-0">
                        <h5 class="card-title "><?php echo Labels::getLabel('LBL_Latest_Offers', $siteLangId); ?>
                        </h5>
                        <div class="action"> <?php if (count($offers) > 0) { ?> <a href="<?php echo UrlHelper::generateUrl('buyer', 'offers'); ?>" class="link"><?php echo Labels::getLabel('Lbl_View_All', $siteLangId); ?></a>
                            <?php } ?> </div>
                    </div>
                    <div class="card-table">
                        <div class="js-scrollable table-wrap table-responsive">
                            <table class="table">
                                <thead>
                                    <tr class="">
                                        <th colspan="2" width="60%">
                                            <?php echo Labels::getLabel('LBL_Offer_Particulars', $siteLangId); ?>
                                        </th>
                                        <th width="20%">
                                            <?php echo Labels::getLabel('LBL_Expires_On', $siteLangId); ?></th>
                                        <th width="20%">
                                            <?php echo Labels::getLabel('LBL_Min_order', $siteLangId); ?></th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <?php if (count($offers) > 0) {
                                        foreach ($offers as $row) {
                                            $discountValue = ($row['coupon_discount_in_percent'] == applicationConstants::PERCENTAGE) ? $row['coupon_discount_value'] . ' %' : CommonHelper::displayMoneyFormat($row['coupon_discount_value']);
                                            $title = ($row['coupon_title'] == '') ? $row['coupon_identifier'] : $row['coupon_title'];
                                            $uploadedTime = AttachedFile::setTimeParam($row['coupon_updated_on']);
                                            $imgUrl =  UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', 'coupon', array($row['coupon_id'], $siteLangId, ImageDimension::VIEW_NORMAL), CONF_WEBROOT_FRONT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                                            $imageCouponDimensions = ImageDimension::getData(ImageDimension::TYPE_COUPON, ImageDimension::VIEW_NORMAL);

                                    ?>
                                            <tr>
                                                <td>
                                                    <figure class="product-profile__pic"><img src="<?php echo $imgUrl; ?>" data-aspect-ratio="<?php echo $imageCouponDimensions[ImageDimension::VIEW_NORMAL]['aspectRatio']; ?>" alt="<?php echo $title; ?>">
                                                    </figure>
                                                </td>
                                                <td>
                                                    <div class="product-profile__description">
                                                        <div class="product-profile__title"><?php echo $discountValue; ?>
                                                            <?php echo Labels::getLabel('LBL_OFF', $siteLangId); ?></div>
                                                        <div class="product-profile__title">
                                                            <?php echo ($row['coupon_title'] == '') ? $row['coupon_identifier'] : $row['coupon_title']; ?>
                                                        </div>
                                                        <span class="coupon-code"><?php echo $row['coupon_code']; ?></span>
                                                    </div>
                                                </td>
                                                <td><?php echo FatDate::format($row['coupon_end_date']); ?></td>
                                                <td> <?php echo CommonHelper::displayMoneyFormat($row['coupon_min_order_value']); ?>
                                                </td>
                                            </tr> <?php }
                                            } else { ?> <tr>
                                            <td colspan="4">
                                                <?php $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId), false); ?>
                                            </td>
                                        </tr> <?php } ?>
                                </tbody>

                            </table>
                        </div>
                    </div>
                    <?php // $this->includeTemplate('_partial/userDashboardMessages.php');
                    ?>
                </div>




                <div class="card">
                    <div class="card-head border-0">
                        <h5 class="card-title "><?php echo Labels::getLabel('LBL_Return_requests', $siteLangId); ?>
                        </h5> <?php if (count($returnRequests) > 0) { ?> <div class="action">
                                <a href="<?php echo UrlHelper::generateUrl('buyer', 'orderReturnRequests'); ?>" class="link"><?php echo Labels::getLabel('Lbl_View_All', $siteLangId); ?></a>
                            </div> <?php } ?>
                    </div>
                    <div class="card-table">
                        <div class="js-scrollable table-wrap table-responsive">
                            <table class="table table-justified">
                                <thead>

                                    <tr class="">
                                        <th width="60%">
                                            <?php echo Labels::getLabel('LBL_Order_Particulars', $siteLangId); ?>
                                        </th>
                                        <th width="10%"><?php echo Labels::getLabel('LBL_Qty', $siteLangId); ?></th>
                                        <th width="20%"><?php echo Labels::getLabel('LBL_Status', $siteLangId); ?>
                                        </th>
                                        <th width="10%"></th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php if (count($returnRequests) > 0) {
                                        foreach ($returnRequests as $row) {
                                            $orderDetailUrl = UrlHelper::generateUrl('buyer', 'viewOrder', array($row['order_id'], $row['op_id']));
                                            $prodOrBatchUrl = 'javascript:void(0)';
                                            if ($row['op_is_batch']) {
                                                $prodOrBatchUrl = UrlHelper::generateUrl('Products', 'batch', array($row['op_selprod_id']), CONF_WEBROOT_FRONTEND);
                                            } else {
                                                if (Product::verifyProductIsValid($row['op_selprod_id']) == true) {
                                                    $prodOrBatchUrl = UrlHelper::generateUrl('Products', 'view', array($row['op_selprod_id']), CONF_WEBROOT_FRONTEND);
                                                }
                                            } ?> <tr>
                                                <td>
                                                    <div class="product-profile__description">
                                                        <div class="request__date">
                                                            <?php echo FatDate::format($row['orrequest_date']); ?></div>
                                                        <div class="product-profile__title">
                                                            <a title="<?php echo Labels::getLabel('LBL_Invoice_number', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Buyer', 'viewOrder', array($row['order_id'], $row['op_id'])); ?>" href="<?php echo $orderDetailUrl; ?>"><?php echo $row['op_invoice_number']; ?></a>
                                                        </div>
                                                        <div class="product-profile__sub_title">
                                                            <?php if ($row['op_selprod_title'] != '') { ?> <a title="<?php echo $row['op_selprod_title']; ?>" href="<?php echo $prodOrBatchUrl; ?>">
                                                                    <?php echo $row['op_selprod_title']; ?>
                                                                </a> <?php } else { ?> <a title="<?php echo $row['op_product_name']; ?>" href="<?php echo $prodOrBatchUrl; ?>">
                                                                    <?php echo $row['op_product_name']; ?> </a> <?php } ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="request__qty"> <?php echo $row['orrequest_qty']; ?> </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-inline <?php echo $OrderRetReqStatusClassArr[$row['orrequest_status']]; ?>">
                                                        <?php echo $OrderReturnRequestStatusArr[$row['orrequest_status']]; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <ul class="actions">
                                                        <li>
                                                            <a title="<?php echo Labels::getLabel('LBL_View_Request', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Buyer', 'ViewOrderReturnRequest', array($row['orrequest_id'])); ?>">
                                                                <svg class="svg" width="18" height="18">
                                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#view">
                                                                    </use>
                                                                </svg>
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </td>
                                            </tr> <?php }
                                            } else { ?> <tr>
                                            <td colspan="4">
                                                <?php $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId), false); ?>
                                            </td>
                                        </tr> <?php } ?>
                                </tbody>

                            </table>
                        </div>
                    </div>
                </div>

                <!-- <div class="card">
                        <?php // $this->includeTemplate('_partial/userDashboardMessages.php');
                        ?>
                        </div> -->
                <div class="card">
                    <div class="card-head border-0">
                        <h5 class="card-title">
                            <?php echo Labels::getLabel('LBL_Cancellation_requests', $siteLangId); ?></h5>
                        <?php if (count($cancellationRequests) > 0) { ?> <div class="action">
                                <a href="<?php echo UrlHelper::generateUrl('buyer', 'orderCancellationRequests'); ?>" class="link"><?php echo Labels::getLabel('Lbl_View_All', $siteLangId); ?></a>
                            </div> <?php } ?>
                    </div>
                    <div class="card-table">
                        <div class="js-scrollable table-wrap table-responsive">
                            <table class="table ">
                                <thead>
                                    <tr class="">
                                        <th width="40%">
                                            <?php echo Labels::getLabel('LBL_Order_Particulars', $siteLangId); ?>
                                        </th>
                                        <th width="50%"><?php echo Labels::getLabel('LBL_Details', $siteLangId); ?>
                                        </th>
                                        <th width="10%"><?php echo Labels::getLabel('LBL_Status', $siteLangId); ?>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($cancellationRequests) > 0) {
                                        foreach ($cancellationRequests as $row) {
                                            $orderDetailUrl = UrlHelper::generateUrl('buyer', 'viewOrder', array($row['order_id'], $row['op_id']));
                                            $prodOrBatchUrl = 'javascript:void(0)';
                                            if ($row['op_is_batch']) {
                                                $prodOrBatchUrl = UrlHelper::generateUrl('Products', 'batch', array($row['op_selprod_id']), CONF_WEBROOT_FRONTEND);
                                            } else {
                                                if (Product::verifyProductIsValid($row['op_selprod_id']) == true) {
                                                    $prodOrBatchUrl = UrlHelper::generateUrl('Products', 'view', array($row['op_selprod_id']), CONF_WEBROOT_FRONTEND);
                                                }
                                            } ?> <tr>
                                                <td>
                                                    <div class="product-profile__description">
                                                        <div class="request__date">
                                                            <?php echo FatDate::format($row['ocrequest_date']); ?></div>
                                                        <div class="product-profile__title">
                                                            <a title="<?php echo Labels::getLabel('Lbl_Invoice_number', $siteLangId) ?>" href="<?php echo $orderDetailUrl; ?>">
                                                                <?php echo $row['op_invoice_number']; ?> </a>
                                                        </div>
                                                        <div class="product-profile__sub_title">
                                                            <?php if ($row['op_selprod_title'] != '') { ?> <a title="<?php echo $row['op_selprod_title']; ?>" href="<?php echo $prodOrBatchUrl; ?>">
                                                                    <?php echo $row['op_selprod_title']; ?>
                                                                </a> <?php } else { ?> <a title="<?php echo $row['op_product_name']; ?>" href="<?php echo $prodOrBatchUrl; ?>">
                                                                    <?php echo $row['op_product_name']; ?> </a> <?php } ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="request__reason">
                                                        <?php echo Labels::getLabel('Lbl_Reason', $siteLangId) ?>:
                                                        <?php echo $row['ocreason_title']; ?>
                                                    </div>
                                                    <div class="request__comments">
                                                        <?php echo Labels::getLabel('Lbl_Comments', $siteLangId) ?>:
                                                        <?php
                                                        $comentDetail = $row['ocrequest_message'];
                                                        if (strlen((string)$comentDetail) > 25) {
                                                            echo  $newDetail = strlen((string)$comentDetail) > 25 ? substr($comentDetail, 0, 25) . "..." : $comentDetail;
                                                        ?>
                                                            <button class="btn btn-view" data-bs-toggle="tooltip" data-placement="top" data-bs-original-title="<?php echo Labels::getLabel('LBL_VIEW_MORE', $siteLangId); ?>" onclick='getCancellationRequestComment(<?php echo $row['ocrequest_id']; ?>)'>
                                                                <svg class="svg" width="10" height="10">
                                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#more">
                                                                    </use>
                                                                </svg>
                                                            </button>
                                                        <?php } else {
                                                            echo $row['ocrequest_message'];
                                                        }
                                                        ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-inline <?php echo $cancelReqStatusClassArr[$row['ocrequest_status']]; ?>">
                                                        <?php echo $OrderCancelRequestStatusArr[$row['ocrequest_status']]; ?>
                                                    </span>
                                                </td>
                                            </tr> <?php }
                                            } else { ?> <tr>
                                            <td colspan="3">
                                                <?php $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId), false); ?>
                                            </td>
                                        </tr> <?php } ?>
                                </tbody>

                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>