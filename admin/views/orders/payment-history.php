<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$commentModalsText = '';
if (!empty($order['payments'])) { ?>
    <div class="table-responsive table-scrollable js-scrollable listingTableJs">
        <table class="table">
            <thead class="tableHeadJs">
                <tr>
                    <th><?php echo Labels::getLabel('LBL_DATE_ADDED', $siteLangId); ?></th>
                    <th><?php echo Labels::getLabel('LBL_TXN_ID', $siteLangId); ?></th>
                    <th><?php echo Labels::getLabel('LBL_PAYMENT_METHOD', $siteLangId); ?></th>
                    <th><?php echo Labels::getLabel('LBL_AMOUNT', $siteLangId); ?></th>
                    <th>
                        <?php echo Labels::getLabel('LBL_RESPONSE', $siteLangId); ?>
                        <i class="fas fa-info-circle" data-bs-toggle="tooltip" data-placement="top" title="<?php echo Labels::getLabel('LBL_GATEWAY_RESPONSE', $siteLangId); ?>"></i>
                    </th>
                    <th><?php echo Labels::getLabel('LBL_STATUS', $siteLangId); ?></th>
                    <th class="align-right"><?php echo Labels::getLabel('LBL_ACTION_BUTTONS', $siteLangId); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order["payments"] as $key => $row) { ?>
                    <tr>
                        <td><?php echo HtmlHelper::formatDateTime($row['opayment_date']); ?></td>
                        <td><?php echo $row['opayment_gateway_txn_id']; ?></td>
                        <td><?php echo $row['opayment_method']; ?></td>
                        <td><?php echo CommonHelper::displayMoneyFormat($row['opayment_amount'], true, true); ?></td>
                        <td>
                            <?php if (!empty($row['opayment_gateway_response'])) { ?>
                                <div class="break-me">
                                    <a class="link-dotted" href="javascript:void(0);" onclick="viewPaymemntGatewayResponse('<?php echo $row['opayment_order_id']; ?>')">
                                        <?php echo Labels::getLabel('LBL_VIEW', $siteLangId); ?>
                                    </a>
                                </div>
                            <?php } else {
                                echo Labels::getLabel("LBL_N/A", $siteLangId);
                            } ?>
                        </td>
                        <td>
                            <?php
                            $cls = $msg = '';
                            switch ($row['opayment_txn_status']) {
                                case Orders::ORDER_PAYMENT_PENDING:
                                    $cls = 'badge-info';
                                    $msg = Labels::getLabel("LBL_PENDING", $siteLangId);
                                    break;
                                case Orders::ORDER_PAYMENT_PAID:
                                    $cls = 'badge-success';
                                    $msg = Labels::getLabel("LBL_APPROVED", $siteLangId);
                                    break;
                                case Orders::ORDER_PAYMENT_CANCELLED:
                                    $cls = 'badge-danger';
                                    $msg = Labels::getLabel("LBL_REJECTED", $siteLangId);
                                    break;
                            }
                            ?>
                            <span class="badge <?php echo $cls; ?>"><?php echo $msg; ?></span>
                        </td>
                        <td class="align-right">
                            <?php $commentModalsText .=  HtmlHelper::getModalStructure("modal" . $key, Labels::getLabel('LBL_COMMENT', $siteLangId), nl2br($row['opayment_comments'])); ?>
                            <ul class="actions">
                                <li data-bs-toggle="tooltip" data-placement="top" title="<?php echo Labels::getLabel('MSG_CLICK_TO_VIEW_COMMENTS', $siteLangId); ?>">
                                    <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#modal<?php echo $key; ?>">
                                        <svg class="svg" width="18" height="18">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#comment">
                                            </use>
                                        </svg>
                                    </a>
                                </li>
                                <?php if (0 == FatUtility::int($row['opayment_txn_status'])) { ?>
                                    <li title='<?php echo Labels::getLabel("LBL_APPROVE", $siteLangId); ?>' data-bs-toggle="tooltip" data-placement="top">
                                        <a href="javascript:void(0)" onclick="approve('<?php echo $row['opayment_id']; ?>')">
                                            <svg class="svg" width="18" height="18">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.yokart.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#tick">
                                                </use>
                                            </svg>
                                        </a>
                                    </li>
                                    <li title='<?php echo Labels::getLabel("LBL_REJECT", $siteLangId); ?>' data-bs-toggle="tooltip" data-placement="top">
                                        <a href="javascript:void(0)" onclick="reject('<?php echo $row['opayment_id']; ?>')">
                                            <svg class="svg" width="18" height="18">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.yokart.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#not-allowed">
                                                </use>
                                            </svg>
                                        </a>
                                    </li>
                                <?php } ?>
                            </ul>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <?php echo $commentModalsText; ?>
<?php } ?>