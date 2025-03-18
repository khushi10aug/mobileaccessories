<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="card-body p-0 itemSummaryJs">
    <div class="table-responsive table-scrollable js-scrollable listingTableJs">
        <table class="table table-orders">
            <thead class="tableHeadJs">
                <tr>
                    <th><?php echo Labels::getLabel('LBL_ORDER_INVOICE_ID', $siteLangId); ?></th>
                    <th><?php echo Labels::getLabel('LBL_STATUS', $siteLangId); ?></th>
                    <th><?php echo Labels::getLabel('LBL_SUBSCRIPTION_DETAILS', $siteLangId); ?></th>
                    <th><?php echo Labels::getLabel('LBL_SUBSCRIPTION_PERIOD', $siteLangId); ?></th>
                    <th class="align-right"><?php echo Labels::getLabel('LBL_ACTION_BUTTONS', $siteLangId); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($order['items'] as $op) {
                    $op['order_id'] = $order['order_id']; ?>
                    <tr>
                        <td>
                            <?php echo $op['ossubs_invoice_number']; ?>
                        </td>
                        <td>
                            <?php
                            $orderStatus = "";
                            if ($op['ossubs_status_id'] == FatApp::getConfig('CONF_DEFAULT_SUBSCRIPTION_PAID_ORDER_STATUS') && $op['ossubs_till_date'] < date("Y-m-d")) {
                                $orderStatus = Labels::getLabel('LBL_EXPIRED', $siteLangId);
                            } else {
                                $orderStatus = isset($orderStatuses[$op['ossubs_status_id']]) ? $orderStatuses[$op['ossubs_status_id']] :  '';
                            }

                            echo OrderProduct::getStatusHtml((int)$op["orderstatus_color_class"], $orderStatus); ?>
                        </td>
                        <td>
                            <?php echo OrderSubscription::getSubscriptionTitle($op, $siteLangId); ?>
                        </td>
                        <td>
                            <?php if(SellerPackagePlans::SUBSCRIPTION_PERIOD_UNLIMITED == $op['ossubs_frequency']) {
                                echo $subcriptionPeriodArr[$op['ossubs_frequency']];
                            } else { 
                                if ($op['ossubs_from_date'] == 0 || $op['ossubs_till_date'] == 0) {
                                    echo Labels::getLabel("LBL_N/A", $siteLangId);
                                } else {
                                    echo FatDate::format($op['ossubs_from_date']) . " - " . FatDate::format($op['ossubs_till_date']);
                                } 
                            }?>
                        </td>
                        <td class="align-right">
                            <?php
                            $data = ['siteLangId' => $siteLangId];
                            $data['otherButtons'] = [
                                [
                                    'attr' => [
                                        'href' => 'javascript:void(0)',
                                        'onclick' => 'getItem(' . $op['order_id'] . ',' . $op['ossubs_id'] . ')',
                                        'title' => Labels::getLabel('MSG_VIEW_DETAIL', $siteLangId),
                                    ],
                                    'label' => '<svg class="svg" width="18" height="18">
                                                    <use
                                                        xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#view">
                                                    </use>
                                                </svg>',
                                ]
                            ];


                            $this->includeTemplate('_partial/listing/listing-action-buttons.php', $data, false);
                            ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>