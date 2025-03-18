<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>

<div class="modal-header">
    <h5 class="modal-title">
        <?php echo $order['op_selprod_title']; ?>
    </h5>
</div>
<div class="modal-body orrDetailsJs<?php echo $order['orrequest_id']; ?>">
    <div class="form-edit-body loaderContainerJs">
        <ul class="list-stats">
            <li class="list-stats-item">
                <span class="lable"><?php echo Labels::getLabel('LBL_INVOICE_NUMBER', $siteLangId); ?>:</span>
                <span class="value"><?php echo $order['op_invoice_number'] ?></span>
            </li>
            <li class="list-stats-item">
                <span class="lable"><?php echo Labels::getLabel('LBL_REFERENCE_NUMBER', $siteLangId); ?>:</span>
                <span class="value"><?php echo $order['orrequest_reference'] ?></span>
            </li>
            <li class="list-stats-item">
                <span class="lable"><?php echo Labels::getLabel('LBL_REQUESTED_ON', $siteLangId); ?>:</span>
                <span class="value"><?php echo FatDate::format($order['orrequest_date'], true); ?></span>
            </li>
            <li class="list-stats-item">
                <span class="lable"><?php echo Labels::getLabel('LBL_QTY', $siteLangId); ?>:</span>
                <span class="value"><?php echo $order["orrequest_qty"]; ?></span>
            </li>
            <li class="list-stats-item">
                <span class="lable"><?php echo Labels::getLabel('LBL_REFUND_AMOUNT', $siteLangId); ?>:</span>
                <span class="value">
                    <?php
                    $returnDataArr = CommonHelper::getOrderProductRefundAmtArr($order);
                    echo CommonHelper::displayMoneyFormat($returnDataArr['op_refund_amount'], true, true);
                    ?>
                </span>
            </li>
            <li class="list-stats-item">
                <span class="lable"><?php echo Labels::getLabel('LBL_REASON', $siteLangId); ?>:</span>
                <span class="value"><?php echo $order['orreason_title']; ?></span>
            </li>
            <li class="list-stats-item">
                <span class="lable"><?php echo Labels::getLabel('LBL_STATUS', $siteLangId); ?>:</span>
                <span class="value"><?php echo OrderReturnRequest::getStatusHtml($siteLangId, $order['orrequest_status']); ?></span>
            </li>
            <?php if (isset($attachedFile['afile_physical_path']) && !empty($attachedFile['afile_physical_path'])) { ?>
                <li class="list-stats-item">
                    <a href="<?php echo UrlHelper::generateUrl('OrderReturnRequests', 'downloadAttachment', [$order["orrequest_id"]]);  ?>" class="btn btn-icon btn-outline-brand btn-add" title="<?php echo Labels::getLabel('LBL_DOWNLOAD_ATTACHMENT', $siteLangId); ?>" data-bs-toggle='tooltip' data-placement='top'>
                        <svg class="svg btn-icon-start" width="18" height="18">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icon-download">
                            </use>
                        </svg>
                        <span><?php echo Labels::getLabel('LBL_DOWNLOAD', $siteLangId); ?></span>
                    </a>
                </li>
            <?php } ?>
        </ul>
    </div>
</div>