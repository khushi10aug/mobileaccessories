<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

echo $msgsSrchForm->getFormHtml(); ?>

<main class="main">
    <div class="container">
        <?php $this->includeTemplate('_partial/header/header-breadcrumb.php', [], false); ?>
        <div class="row">
            <div class="col-md-9">
                <div class="card">
                    <div class="card-head">
                        <div class="card-head-label">
                            <h3 class="card-head-title">
                                <a class="btn-back" href="<?php echo UrlHelper::generateUrl('OrderReturnRequests'); ?>">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#back">
                                        </use>
                                    </svg>
                                </a>
                                <?php
                                $str = Labels::getLabel('LBL_REFERENCE_NO_#{REFERENCE-NO}', $siteLangId);
                                echo CommonHelper::replaceStringData($str, ['{REFERENCE-NO}' => $order['orrequest_reference']])
                                ?>
                            </h3>
                        </div>
                        <div class="card-head-toolbar">
                            <small><?php echo FatDate::format($order['orrequest_date'], true); ?></small>
                        </div>
                    </div>
                    <div class="card-table itemSummaryJs">
                        <div class="table-responsive table-scrollable js-scrollable listingTableJs">
                            <table class="table table-orders">
                                <thead class="tableHeadJs">
                                    <tr>
                                        <th><?php echo Labels::getLabel('LBL_PRODUCT', $siteLangId); ?></th>
                                        <th><?php echo Labels::getLabel('LBL_QTY', $siteLangId); ?></th>
                                        <th><?php echo Labels::getLabel('LBL_STATUS', $siteLangId); ?></th>
                                        <th><?php echo Labels::getLabel('LBL_REFUND_AMOUNT', $siteLangId); ?></th>
                                        <th class="align-right"><?php echo Labels::getLabel('LBL_ACTION_BUTTONS', $siteLangId); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><?php $this->includeTemplate('_partial/product/order-product-info-card.php', ['order' => $order, 'siteLangId' => $siteLangId, 'horizontalAlignOptions' => true], false); ?>
                                        </td>
                                        <td><?php echo $order["orrequest_qty"]; ?></td>
                                        <td><?php echo OrderReturnRequest::getStatusHtml($siteLangId, $order['orrequest_status']); ?></td>
                                        <td>
                                            <?php
                                            $returnDataArr = CommonHelper::getOrderProductRefundAmtArr($order);
                                            echo CommonHelper::displayMoneyFormat($returnDataArr['op_refund_amount'], true, true);
                                            ?>
                                        </td>
                                        <td class="align-right">
                                            <?php
                                            $data = ['siteLangId' => $siteLangId];
                                            $data['otherButtons'] = [
                                                [
                                                    'attr' => [
                                                        'href' => 'javascript:void(0)',
                                                        'onclick' => 'getItem(' . $order['orrequest_id'] . ')',
                                                        'title' => Labels::getLabel('MSG_VIEW_DETAIL', $siteLangId),
                                                    ],
                                                    'label' => '<svg class="svg" width="18" height="18">
                                                                    <use
                                                                        xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#view">
                                                                    </use>
                                                                </svg>',
                                                ],
                                            ];
                                            if ($order['orrequest_status'] == OrderReturnRequest::RETURN_REQUEST_STATUS_PENDING || $order['orrequest_status'] == OrderReturnRequest::RETURN_REQUEST_STATUS_ESCALATED) {
                                                $data['otherButtons'][] = [
                                                    'attr' => [
                                                        'href' => 'javascript:void(0)',
                                                        'onclick' => 'requestStatusForm(' . $order['orrequest_id'] . ')',
                                                        'title' => Labels::getLabel('MSG_UPDATE_STATUS', $siteLangId),
                                                    ],
                                                    'label' => '<svg class="svg" width="18" height="18">
                                                                    <use
                                                                        xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#form">
                                                                    </use>
                                                                </svg>',
                                                ];
                                            }
                                            $this->includeTemplate('_partial/listing/listing-action-buttons.php', $data, false); ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>


                <div class="card">
                    <div class="card-head">
                        <div class="card-head-label">
                            <h3 class="card-head-title">
                                <?php echo Labels::getLabel('LBL_MESSAGE_COMMUNICATION', $siteLangId); ?>
                            </h3>
                        </div>
                        <div class="card-toolbar">
                            <a href="javascript:void(0);" class="btn btn-icon btn-outline-brand btn-add" onclick="addNewComment(<?php echo $orrequestId; ?>)" title="<?php echo Labels::getLabel('LBL_NEW_COMMENT', $siteLangId); ?>" data-bs-toggle='tooltip' data-placement='top'>
                                <svg class="svg btn-icon-start" width="18" height="18">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#add">
                                    </use>
                                </svg>
                                <span><?php echo Labels::getLabel('LBL_NEW', $siteLangId); ?></span>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <ul class="timeline appendRowsJs" id="appendRowsJs">
                            <?php require_once('get-rows.php'); ?>
                        </ul>
                        <?php
                        $lastRecord = current(array_reverse($arrListing));
                        $postedData['reference'] = $lastRecord['orrmsg_date'];
                        $postedData['order_id'] = $lastRecord['orrmsg_id'];
                        $data = [
                            'siteLangId' => $siteLangId,
                            'postedData' => $postedData,
                            'page' => $page,
                            'pageCount' => $pageCount,
                        ];
                        $this->includeTemplate('_partial/load-more-pagination.php', $data);
                        ?>

                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <!-- Buyer Information -->
                <?php if (!empty($order['user_name']) || !empty($order['credential_username']) || !empty($order['credential_email']) || !empty($order['buyer_phone'])) { ?>
                    <div class="card">
                        <div class="card-head">
                            <div class="card-head-label">
                                <h3 class="card-head-title">
                                    <?php echo Labels::getLabel('LBL_CUSTOMER_INFORMATION', $siteLangId); ?>
                                </h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <ul class="list-stats">
                                <?php if (!empty($order['user_name'])) { ?>
                                    <li class="list-stats-item">
                                        <span class="lable"><?php echo Labels::getLabel('LBL_CUSTOMER_NAME', $siteLangId); ?>:</span>
                                        <span class="value"><?php echo $order['user_name']; ?></span>
                                    </li>
                                <?php } ?>

                                <?php if (!empty($order['credential_username'])) { ?>
                                    <li class="list-stats-item">
                                        <span class="lable"><?php echo Labels::getLabel('LBL_CUSTOMER_USERNAME', $siteLangId); ?>:</span>
                                        <span class="value"><?php echo $order['credential_username']; ?></span>
                                    </li>
                                <?php } ?>

                                <?php if (!empty($order['credential_email'])) { ?>
                                    <li class="list-stats-item">
                                        <span class="lable"><?php echo Labels::getLabel('LBL_EMAIL_ID', $siteLangId); ?>:</span>
                                        <span class="value"><?php echo $order['credential_email']; ?></span>
                                    </li>
                                <?php } ?>

                                <?php if (!empty($order['user_phone'])) { ?>
                                    <li class="list-stats-item">
                                        <span class="lable"><?php echo Labels::getLabel('LBL_PHONE', $siteLangId); ?>:</span>
                                        <span class="value"><span class="default-ltr"><?php echo ValidateElement::formatDialCode($order['user_phone_dcode']) . $order['user_phone']; ?></span></span>
                                    </li>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>
                <?php } ?>

                <!-- Seller Information -->
                <?php if (!empty($order['op_shop_name']) || !empty($order['seller_name']) || !empty($order['seller_email']) || !empty($order['seller_phone'])) { ?>
                    <div class="card card-toggle">
                        <div class="card-head dropdown-toggle-custom collapsed" data-bs-toggle="collapse" data-bs-target="#order-block1" aria-expanded="false" aria-controls="order-block1">
                            <div class="card-head-label">
                                <h3 class="card-head-title">
                                    <?php echo Labels::getLabel('LBL_SELLER_INFORMATION', $siteLangId); ?>
                                </h3>
                            </div>
                            <i class="dropdown-toggle-custom-arrow"></i>
                        </div>
                        <div class="card-body collapse" id="order-block1">
                            <ul class="list-stats">
                                <?php if (!empty($order['op_shop_name'])) { ?>
                                    <li class="list-stats-item">
                                        <span class="lable"><?php echo Labels::getLabel('LBL_SHOP_NAME', $siteLangId); ?>:</span>
                                        <span class="value"><?php echo $order['op_shop_name']; ?></span>
                                    </li>
                                <?php } ?>

                                <?php if (!empty($order['seller_name'])) { ?>
                                    <li class="list-stats-item">
                                        <span class="lable"><?php echo Labels::getLabel('LBL_SELLER_NAME', $siteLangId); ?>:</span>
                                        <span class="value"><?php echo $order['seller_name']; ?></span>
                                    </li>
                                <?php } ?>

                                <?php if (!empty($order['seller_email'])) { ?>
                                    <li class="list-stats-item">
                                        <span class="lable"><?php echo Labels::getLabel('LBL_EMAIL_ID', $siteLangId); ?>:</span>
                                        <span class="value"><?php echo $order['seller_email']; ?></span>
                                    </li>
                                <?php } ?>

                                <?php if (!empty($order['seller_phone'])) { ?>
                                    <li class="list-stats-item">
                                        <span class="lable"><?php echo Labels::getLabel('LBL_PHONE', $siteLangId); ?>:</span>
                                        <span class="value"><?php echo $order['seller_phone']; ?></span>
                                    </li>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</main>
<script>
    var RETURN_REQUEST_TYPE_REFUND = <?php echo OrderReturnRequest::RETURN_REQUEST_TYPE_REFUND; ?>;
    var RETURN_REQUEST_STATUS_REFUNDED = <?php echo OrderReturnRequest::RETURN_REQUEST_STATUS_REFUNDED; ?>;
    var requestType = <?php echo $order["orrequest_type"]; ?>;
</script>