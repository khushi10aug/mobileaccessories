<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$this->includeTemplate('_partial/dashboardNavigation.php'); ?>

<div class="content-wrapper content-space">
    <?php
    $redirectToAccount = true;
    $canViewProducts = $userPrivilege->canViewProducts(UserAuthentication::getLoggedUserId(), true);
    $canEditProducts = $userPrivilege->canEditProducts(UserAuthentication::getLoggedUserId(), true);
    $data = [
        'canEdit ' => $canEditProducts,
        'userPrivilege' => $userPrivilege,
        'canViewProducts' => $canViewProducts,
        'canEditProducts' => $canEditProducts,
        'headingLabel' => Labels::getLabel('LBL_DASHBOARD', $siteLangId),
        'action' => 'products',
        'siteLangId' => $siteLangId
    ];

    if (!$isShopActive) {
        $redirectToAccount = false;
        $data['otherButtons'][] = [
            'attr' => [
                'href' => UrlHelper::generateUrl('Seller', 'shop'),
                'class' => 'btn btn-outline-gray btn-icon',
                'title' => Labels::getLabel('LBL_CREATE_SHOP', $siteLangId)
            ],
            'label' => '<svg class="svg btn-icon-start" width="18" height="18">
                            <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#add">
                            </use>
                        </svg>' . Labels::getLabel('LBL_CREATE_SHOP', $siteLangId)
        ];
    }

    if ($canViewProducts && !FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
        $redirectToAccount = false;
        $data['otherButtons'][] = [
            'attr' => [
                'href' => UrlHelper::generateUrl('seller', 'products'),
                'class' => 'btn btn-outline-gray btn-icon',
                'title' => Labels::getLabel('LBL_SHOP_INVENTORY', $siteLangId)
            ],
            'icon' => '<svg class="svg btn-icon-start" width="18" height="18">
            <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#inventories">
            </use>
        </svg>',
            'label' =>  Labels::getLabel('LBL_SHOP_INVENTORY', $siteLangId)
        ];
    }

    $this->includeTemplate('_partial/header/content-header.php', $data, false); ?>
    <div class="content-body">
        <div class="row">
            <div class="col-lg-4 order-lg-2">
                <?php if (
                    $userPrivilege->canViewSales(UserAuthentication::getLoggedUserId(), true) ||
                    $userPrivilege->canViewCancellationRequests(UserAuthentication::getLoggedUserId(), true) ||
                    $userPrivilege->canViewReturnRequests(UserAuthentication::getLoggedUserId(), true)
                ) {
                    $redirectToAccount = false; ?>
                    <div class="widget-scroll">
                        <?php if ($userPrivilege->canViewSales(UserAuthentication::getLoggedUserId(), true)) { ?>
                            <div class="widget widget-stats">
                                <a href="<?php echo UrlHelper::generateUrl('Seller', 'sales'); ?>">
                                    <div class="card card-commerce card-commerce-bg" style="background-image: url(<?php echo CONF_WEBROOT_URL; ?>/images/card-commerce-bg-1.png);">
                                        <div class="card-head border-0">
                                            <h5 class="card-title"><?php echo Labels::getLabel('LBL_My_Sales', $siteLangId); ?></h5>
                                        </div>
                                        <div class="card-body ">
                                            <div class="stats">
                                                <div class="stats-number">
                                                    <ul>
                                                        <li>
                                                            <span class="total"><?php echo Labels::getLabel('LBL_Completed_Sales', $siteLangId); ?></span>
                                                            <span class="total-numbers">
                                                                <?php
                                                                $totalSoldSales = isset($ordersStats['totalSoldSales']) ? $ordersStats['totalSoldSales'] : 0;
                                                                echo CommonHelper::displayMoneyFormat($totalSoldSales);
                                                                ?>
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span class="total"><?php echo Labels::getLabel('LBL_Inprocess_Sales', $siteLangId); ?></span>
                                                            <span class="total-numbers">
                                                                <?php
                                                                $totalInprocessSales = isset($ordersStats['totalInprocessSales']) ? $ordersStats['totalInprocessSales'] : 0;
                                                                echo CommonHelper::displayMoneyFormat($totalInprocessSales);
                                                                ?>
                                                            </span>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php } ?>
                        <?php if ($userParentId == UserAuthentication::getLoggedUserId()) { ?>
                            <div class="widget widget-stats">
                                <a href="<?php echo UrlHelper::generateUrl('Account', 'credits'); ?>">
                                    <div class="card card-commerce card-commerce-bg" style="background-image: url(<?php echo CONF_WEBROOT_URL; ?>/images/card-commerce-bg-2.png);">
                                        <div class="card-head border-0">
                                            <h5 class="card-title"><?php echo Labels::getLabel('LBL_Credits', $siteLangId); ?></h5>

                                        </div>
                                        <div class="card-body ">
                                            <div class="stats">
                                                <div class="stats-number">
                                                    <ul>
                                                        <li>
                                                            <span class="total"><?php echo Labels::getLabel('LBL_AVAILABLE_BALANCE', $siteLangId); ?></span>
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
                        <?php } ?>
                        <?php if ($userPrivilege->canViewSales(UserAuthentication::getLoggedUserId(), true)) { ?>
                            <div class="widget widget-stats">
                                <a onclick="redirectfunc('<?php echo UrlHelper::generateUrl('Seller', 'Sales'); ?>', <?php echo FatApp::getConfig("CONF_DEFAULT_COMPLETED_ORDER_STATUS", null, ''); ?>)" href="javaScript:void(0)">
                                    <div class="card card-commerce card-commerce-bg" style="background-image: url(<?php echo CONF_WEBROOT_URL; ?>/images/card-commerce-bg-3.png);">
                                        <div class="card-head border-0">
                                            <h5 class="card-title"><?php echo Labels::getLabel('LBL_Order', $siteLangId); ?></h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="stats">
                                                <div class="stats-number">
                                                    <ul>
                                                        <li>
                                                            <span class="total"><?php echo Labels::getLabel('LBL_Completed_Orders', $siteLangId); ?></span>
                                                            <span class="total-numbers">
                                                                <?php
                                                                $totalSoldCount = isset($ordersStats['totalSoldCount']) ? $ordersStats['totalSoldCount'] : 0;
                                                                echo FatUtility::int($totalSoldCount);
                                                                ?>
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span class="total"><?php echo Labels::getLabel('LBL_Pending_Orders', $siteLangId); ?></span>
                                                            <span class="total-numbers">
                                                                <?php $pendingOrders = $ordersCount - $totalSoldCount;
                                                                echo $pendingOrders; ?>
                                                            </span>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php } ?>
                        <?php if ($userPrivilege->canViewSubscription(UserAuthentication::getLoggedUserId(), true)) { ?>
                            <?php if (FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE')) { ?>
                                <div class="widget widget-stats">
                                    <a href="<?php echo UrlHelper::generateUrl('Seller', 'subscriptions'); ?>">
                                        <div class="card card-commerce card-commerce-bg" style="background-image: url(<?php echo CONF_WEBROOT_URL; ?>/images/card-commerce-bg-4.png);">
                                            <div class="card-head border-0">
                                                <h5 class="card-title">
                                                    <?php echo Labels::getLabel('LBL_Active_Subscription', $siteLangId); ?></h5>
                                            </div>
                                            <div class="card-body ">
                                                <div class="stats">
                                                    <div class="stats-number">
                                                        <ul>
                                                            <?php if ($pendingDaysForCurrentPlan >= 0) { ?>
                                                                <li>
                                                                    <span class="total"><?php echo Labels::getLabel('LBL_Remaining', $siteLangId); ?></span>
                                                                    <span class="total-numbers"><?php echo $pendingDaysForCurrentPlan; ?>
                                                                        <?php echo Labels::getLabel('LBL_Days', $siteLangId); ?></span>
                                                                </li>
                                                                <li>
                                                                    <span class="total"><?php echo Labels::getLabel('LBL_Allowed_Products', $siteLangId); ?></span>
                                                                    <span class="total-numbers"><?php echo ($remainingAllowedProducts > 0) ? $remainingAllowedProducts : 0; ?></span>
                                                                </li>
                                                            <?php } else { ?>
                                                                <li>
                                                                    <span class="total"><?php echo Labels::getLabel('LBL_Subscription_Name', $siteLangId); ?></span>
                                                                    <span class="total-numbers"><?php echo $subscriptionName; ?></span>
                                                                </li>
                                                                <li>
                                                                    <span class="total"><?php echo Labels::getLabel('LBL_Expires_On', $siteLangId); ?></span>
                                                                    <span class="total-numbers"><?php echo (isset($subscriptionTillDate)) ? $subscriptionTillDate : ''; ?></span>
                                                                </li>
                                                            <?php } ?>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php } ?>
                        <?php } ?>
                        <?php if ($userPrivilege->canViewReturnRequests(UserAuthentication::getLoggedUserId(), true)) { ?>
                            <div class="widget widget-stats">
                                <a href="<?php echo UrlHelper::generateUrl('Seller', 'orderReturnRequests'); ?>">
                                    <div class="card card-commerce card-commerce-bg" style="background-image: url(<?php echo CONF_WEBROOT_URL; ?>/images/card-commerce-bg-2.png);">
                                        <div class="card-head border-0">
                                            <h5 class="card-title"><?php echo Labels::getLabel('LBL_Refund', $siteLangId); ?></h5>

                                        </div>
                                        <div class="card-body ">
                                            <div class="stats">
                                                <div class="stats-number">
                                                    <ul>
                                                        <li>
                                                            <span class="total"><?php echo Labels::getLabel('LBL_Refunded_Orders', $siteLangId); ?></span>
                                                            <span class="total-numbers"><?php echo isset($ordersStats['refundedOrderCount']) ? FatUtility::int($ordersStats['refundedOrderCount']) : 0; ?></span>
                                                        </li>
                                                        <li>
                                                            <span class="total"><?php echo Labels::getLabel('LBL_Refunded_Amount', $siteLangId); ?></span>
                                                            <span class="total-numbers">
                                                                <?php
                                                                $refundedOrderAmount = isset($ordersStats['refundedOrderAmount']) ? $ordersStats['refundedOrderAmount'] : 0;
                                                                echo CommonHelper::displayMoneyFormat($refundedOrderAmount);
                                                                ?>
                                                            </span>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php } ?>
                        <?php if ($userPrivilege->canViewCancellationRequests(UserAuthentication::getLoggedUserId(), true)) { ?>
                            <div class="widget widget-stats">
                                <a onclick="redirectfunc('<?php echo UrlHelper::generateUrl('Seller', 'Sales'); ?>', <?php echo FatApp::getConfig("CONF_DEFAULT_CANCEL_ORDER_STATUS", null, ''); ?>)" href="javaScript:void(0)">
                                    <div class="card card-commerce card-commerce-bg" style="background-image: url(<?php echo CONF_WEBROOT_URL; ?>/images/card-commerce-bg-1.png);">
                                        <div class="card-head border-0">
                                            <h5 class="card-title"><?php echo Labels::getLabel('LBL_Cancellation', $siteLangId); ?>
                                            </h5>

                                        </div>
                                        <div class="card-body">
                                            <div class="stats">
                                                <div class="stats-number">
                                                    <ul>
                                                        <li>
                                                            <span class="total"><?php echo Labels::getLabel('LBL_Cancelled_Orders', $siteLangId); ?></span>
                                                            <span class="total-numbers"><?php echo isset($ordersStats['cancelledOrderCount']) ? FatUtility::int($ordersStats['cancelledOrderCount']) : 0; ?></span>
                                                        </li>
                                                        <li>
                                                            <span class="total"><?php echo Labels::getLabel('LBL_Cancelled_Orders_Amount', $siteLangId); ?></span>
                                                            <span class="total-numbers">
                                                                <?php
                                                                $cancelledOrderAmount = isset($ordersStats['cancelledOrderAmount']) ? $ordersStats['cancelledOrderAmount'] : 0;
                                                                echo CommonHelper::displayMoneyFormat($cancelledOrderAmount);
                                                                ?>
                                                            </span>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
            <div class="col-lg-8">
                <?php if ($userPrivilege->canViewSales(UserAuthentication::getLoggedUserId(), true)) { ?>
                    <div class="card">
                        <div class="card-head border-0">
                            <h5 class="card-title "><?php echo Labels::getLabel('LBL_Sales_Graph', $siteLangId); ?></h5>
                        </div>
                        <div class="card-body graph">
                            <?php $this->includeTemplate('_partial/seller/sellerSalesGraph.php'); ?> </div>
                    </div>
                    <div class="card">
                        <div class="card-head border-0">
                            <h5 class="card-title ">
                                <?php echo Labels::getLabel('LBL_Latest_Orders', $siteLangId); ?>
                            </h5>
                            <?php if (count($orders) > 0) { ?>
                                <div class="action">
                                    <a href="<?php echo UrlHelper::generateUrl('seller', 'sales'); ?>" class="link"><?php echo Labels::getLabel('Lbl_View_All', $siteLangId); ?></a>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="card-table">
                            <div class="js-scrollable table-wrap table-responsive">
                                <table class="table table-justified">
                                    <thead>
                                        <tr class="">
                                            <th width="50%">
                                                <?php echo Labels::getLabel('LBL_Order_Particulars', $siteLangId); ?>
                                            </th>
                                            <th width="15%"><?php echo Labels::getLabel('LBL_Status', $siteLangId); ?>
                                            <th width="15%"><?php echo Labels::getLabel('LBL_Amount', $siteLangId); ?>
                                            </th>
                                            <th width="20%"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($orders) > 0) {
                                            foreach ($orders as $orderId => $row) {
                                                $orderDetailUrl = UrlHelper::generateUrl('seller', 'viewOrder', array($row['op_id']));
                                        ?>
                                                <tr>
                                                    <td>
                                                        <?php echo $this->includeTemplate('_partial/product/product-info-html.php', ['order' => $row, 'siteLangId' => $siteLangId, 'showDate' => true], false, true); ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-inline <?php echo $classArr[$row['orderstatus_color_class']]; ?>">
                                                            <?php echo $row['orderstatus_name']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="item__price">
                                                            <?php echo CommonHelper::displayMoneyFormat(CommonHelper::orderProductAmount($row, 'netamount', false, User::USER_TYPE_SELLER)); ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <ul class="actions">
                                                            <li><a title="<?php echo Labels::getLabel('LBL_View_Order', $siteLangId); ?>" href="<?php echo $orderDetailUrl; ?>"><svg class="svg" width="18" height="18">
                                                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#view">
                                                                        </use>
                                                                    </svg></a></li>
                                                            <?php if (!in_array($row["op_status_id"], $notAllowedStatues)) { ?>
                                                                <li><a href="<?php echo UrlHelper::generateUrl('seller', 'cancelOrder', array($row['op_id'])); ?>" title="<?php echo Labels::getLabel('LBL_Cancel_Order', $siteLangId); ?>"><svg class="svg btn-icon-start" width="18" height="18">
                                                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#close">
                                                                            </use>
                                                                        </svg></a></li>
                                                            <?php } ?>
                                                        </ul>
                                                    </td>
                                                </tr>
                                            <?php }
                                        } else { ?>
                                            <tr>
                                                <td colspan="3">
                                                    <?php $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId), false); ?>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                <?php if ($userParentId == UserAuthentication::getLoggedUserId()) { ?>
                    <div class="card">
                        <div class="card-head border-0">
                            <h5 class="card-title ">
                                <?php echo Labels::getLabel('LBL_Transaction_History', $siteLangId); ?></h5>
                            <?php if (count($transactions) > 0) { ?>
                                <div class="action">
                                    <a href="<?php echo UrlHelper::generateUrl('Account', 'credits'); ?>" class="link"><?php echo Labels::getLabel('Lbl_View_All', $siteLangId); ?></a>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="card-table">
                            <div class="js-scrollable table-wrap table-responsive">
                                <table class="table table-justified">
                                    <thead>
                                        <tr class="">
                                            <th><?php echo Labels::getLabel('LBL_Txn._Id', $siteLangId); ?></th>
                                            <th><?php echo Labels::getLabel('LBL_Date', $siteLangId); ?></th>
                                            <th><?php echo Labels::getLabel('LBL_Credit', $siteLangId); ?></th>
                                            <th><?php echo Labels::getLabel('LBL_Debit', $siteLangId); ?></th>
                                            <th><?php echo Labels::getLabel('LBL_Balance', $siteLangId); ?></th>
                                            <th><?php echo Labels::getLabel('LBL_Comments', $siteLangId); ?></th>
                                            <th><?php echo Labels::getLabel('LBL_Status', $siteLangId); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($transactions) > 0) {
                                            foreach ($transactions as $row) { ?>
                                                <tr>
                                                    <td>
                                                        <div class="txn__id">
                                                            <?php echo Transactions::formatTransactionNumber($row['utxn_id']); ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="txn__date">
                                                            <?php echo FatDate::format($row['utxn_date']); ?> </div>
                                                    </td>
                                                    <td>
                                                        <div class="txn__credit">
                                                            <?php echo CommonHelper::displayMoneyFormat($row['utxn_credit']); ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="txn__debit">
                                                            <?php echo CommonHelper::displayMoneyFormat($row['utxn_debit']); ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="txn__balance">
                                                            <?php echo CommonHelper::displayMoneyFormat($row['balance']); ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="txn__comments"> <?php echo $row['utxn_comments']; ?> </div>
                                                    </td>
                                                    <td>
                                                        <div class="txn__status"><span class="badge badge-inline <?php echo $txnStatusClassArr[$row['utxn_status']] ?>"><?php echo $txnStatusArr[$row['utxn_status']]; ?></span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php }
                                        } else { ?>
                                            <tr>
                                                <td colspan="7">
                                                    <?php $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId), false); ?>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                <?php if ($userPrivilege->canViewReturnRequests(UserAuthentication::getLoggedUserId(), true)) { ?>
                    <div class="card">
                        <div class="card-head border-0">
                            <h5 class="card-title "><?php echo Labels::getLabel('LBL_Return_requests', $siteLangId); ?>
                            </h5>
                            <?php if (count($returnRequests) > 0) { ?>
                                <div class="action">
                                    <a href="<?php echo UrlHelper::generateUrl('seller', 'orderReturnRequests'); ?>" class="link"><?php echo Labels::getLabel('Lbl_View_All', $siteLangId); ?></a>
                                </div>
                            <?php } ?>
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
                                                $orderDetailUrl = UrlHelper::generateUrl('seller', 'viewOrder', array($row['op_id']));
                                                $prodOrBatchUrl = 'javascript:void(0)';
                                                if ($row['op_is_batch']) {
                                                    $prodOrBatchUrl = UrlHelper::generateUrl('Products', 'batch', array($row['op_selprod_id']), CONF_WEBROOT_FRONTEND);
                                                } else {
                                                    if (Product::verifyProductIsValid($row['op_selprod_id']) == true) {
                                                        $prodOrBatchUrl = UrlHelper::generateUrl('Products', 'view', array($row['op_selprod_id']), CONF_WEBROOT_FRONTEND);
                                                    }
                                                } ?>
                                                <tr>
                                                    <td>
                                                        <div class="product-profile__description">
                                                            <div class="request__date">
                                                                <?php echo FatDate::format($row['orrequest_date']); ?></div>
                                                            <div class="product-profile__title">
                                                                <a title="<?php echo Labels::getLabel('LBL_Invoice_number', $siteLangId); ?>" href="<?php echo $orderDetailUrl; ?>"><?php echo $row['op_invoice_number']; ?></a>
                                                            </div>
                                                            <div class="product-profile__sub_title">
                                                                <?php if ($row['op_selprod_title'] != '') { ?>
                                                                    <a title="<?php echo $row['op_selprod_title']; ?>" href="<?php echo $prodOrBatchUrl; ?>">
                                                                        <?php echo $row['op_selprod_title']; ?> </a>
                                                                <?php } else { ?>
                                                                    <a title="<?php echo $row['op_product_name']; ?>" href="<?php echo $prodOrBatchUrl; ?>">
                                                                        <?php echo $row['op_product_name']; ?> </a>
                                                                <?php } ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="request__qty"> <?php echo $row['orrequest_qty']; ?> </div>
                                                    </td>
                                                    <td>
                                                        <div class="request__status"> <span class="badge badge-inline <?php echo $OrderRetReqStatusClassArr[$row['orrequest_status']]; ?>"><?php echo $OrderReturnRequestStatusArr[$row['orrequest_status']]; ?>
                                                            </span></div>
                                                    </td>
                                                    <td> <?php
                                                            $url = UrlHelper::generateUrl('Seller', 'ViewOrderReturnRequest', array($row['orrequest_id'])); ?>
                                                        <ul class="actions">
                                                            <li>
                                                                <a title="<?php echo Labels::getLabel('LBL_View_Request', $siteLangId); ?>" href="<?php echo $url; ?>">
                                                                    <svg class="svg" width="18" height="18">
                                                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#view">
                                                                        </use>
                                                                    </svg>
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </td>
                                                </tr>
                                            <?php }
                                        } else { ?>
                                            <tr>
                                                <td colspan="4">
                                                    <?php $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId), false); ?>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                <?php if ($userPrivilege->canViewCancellationRequests(UserAuthentication::getLoggedUserId(), true)) { ?>
                    <div class="card">
                        <div class="card-head border-0">
                            <h5 class="card-title ">
                                <?php echo Labels::getLabel('LBL_Cancellation_requests', $siteLangId); ?></h5>
                            <?php if (count($cancellationRequests) > 0) { ?>
                                <div class="action">
                                    <a href="<?php echo UrlHelper::generateUrl('seller', 'orderCancellationRequests'); ?>" class="link"><?php echo Labels::getLabel('Lbl_View_All', $siteLangId); ?></a>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="card-table">
                            <div class="js-scrollable table-wrap table-responsive">
                                <table class="table table-justified">
                                    <thead>
                                        <tr class="">
                                            <th width="40%">
                                                <?php echo Labels::getLabel('LBL_Order_Particulars', $siteLangId); ?>
                                            </th>
                                            <th width="50%">
                                                <?php echo Labels::getLabel('LBL_Request_detail', $siteLangId); ?></th>
                                            <th width="10%"><?php echo Labels::getLabel('LBL_Status', $siteLangId); ?>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($cancellationRequests) > 0) {
                                            foreach ($cancellationRequests as $row) {
                                                $orderDetailUrl = UrlHelper::generateUrl('seller', 'viewOrder', array($row['op_id']));
                                                $prodOrBatchUrl = 'javascript:void(0)';
                                                if ($row['op_is_batch']) {
                                                    $prodOrBatchUrl = UrlHelper::generateUrl('Products', 'batch', array($row['op_selprod_id']), CONF_WEBROOT_FRONTEND);
                                                } else {
                                                    if (Product::verifyProductIsValid($row['op_selprod_id']) == true) {
                                                        $prodOrBatchUrl = UrlHelper::generateUrl('Products', 'view', array($row['op_selprod_id']), CONF_WEBROOT_FRONTEND);
                                                    }
                                                } ?>
                                                <tr>
                                                    <td>
                                                        <div class="product-profile__description">
                                                            <div class="request__date">
                                                                <?php echo FatDate::format($row['ocrequest_date']); ?></div>
                                                            <div class="product-profile__title">
                                                                <a title="<?php echo Labels::getLabel('Lbl_Invoice_number', $siteLangId) ?>" href="<?php echo $orderDetailUrl; ?>">
                                                                    <?php echo $row['op_invoice_number']; ?> </a>
                                                            </div>
                                                            <div class="product-profile__sub_title">
                                                                <?php if ($row['op_selprod_title'] != '') { ?>
                                                                    <a title="<?php echo $row['op_selprod_title']; ?>" href="<?php echo $prodOrBatchUrl; ?>">
                                                                        <?php echo $row['op_selprod_title']; ?> </a>
                                                                <?php } else { ?>
                                                                    <a title="<?php echo $row['op_product_name']; ?>" href="<?php echo $prodOrBatchUrl; ?>">
                                                                        <?php echo $row['op_product_name']; ?> </a>
                                                                <?php } ?>
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
                                                </tr>
                                            <?php }
                                        } else { ?>
                                            <tr>
                                                <td colspan="3">
                                                    <?php $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId), false); ?>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<?php if ($redirectToAccount) {
    FatApp::redirectUser(UrlHelper::generateUrl('Account', 'ProfileInfo'));
} ?>

<script>
    /******** for tooltip ****************/
    $('.info--tooltip-js').hover(function() {
        $(this).toggleClass("is-active");
        return false;
    }, function() {
        $(this).toggleClass("is-active");
        return false;
    });

    getCancellationRequestComment = function(recordId) {
        fcom.updateWithAjax(fcom.makeUrl('Seller', "getCancellationRequestComment"), "recordId=" + recordId, function(t) {
            $.ykmodal(t.html, true);
            fcom.removeLoader();
        });
    };
</script>