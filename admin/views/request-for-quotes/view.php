<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="modal-header">
    <h5 class="modal-title">
        <?php echo $rfqData['rfq_number']; ?>
    </h5>
</div>
<div class="modal-body form-edit layoutsJs">
    <div class="form-edit-body loaderContainerJs">
        <ul class="list-stats list-stats-double">
            <?php if (!empty($rfqData['rfq_product_type'])) { ?>
                <li class="list-stats-item">
                    <span class="lable"><?php echo Labels::getLabel('LBL_PRODUCT_TYPE', $siteLangId); ?>:</span>
                    <span class="value"><?php echo Product::getProductTypes($siteLangId)[$rfqData['rfq_product_type']] ?? Labels::getLabel('LBL_N/A', $siteLangId);; ?></span>
                </li>
            <?php } ?>
            <li class="list-stats-item list-stats-item-full">
                <span class="lable"><?php echo Labels::getLabel('LBL_PRODUCT', $siteLangId); ?>:</span>
                <span class="value"><?php echo $rfqData['rfq_title']; ?></span>
            </li>
            <?php if (!empty($rfqData['prodcat_name'])) { ?>
                <li class="list-stats-item">
                    <span class="lable"><?php echo Labels::getLabel('LBL_CATEGORY', $siteLangId); ?>:</span>
                    <span class="value"><?php echo $rfqData['prodcat_name']; ?></span>
                </li>
            <?php } ?>
            <li class="list-stats-item">
                <span class="lable"><?php echo Labels::getLabel('LBL_REQUESTED_QUANTITY', $siteLangId); ?>:</span>
                <span class="value"><?php echo $rfqData['rfq_quantity'] . ' ' . applicationConstants::getWeightUnitName($siteLangId, $rfqData['rfq_quantity_unit'], true); ?></span>
            </li>
            <?php if (!empty($rfqData['rfq_delivery_date']) && 0 < strtotime($rfqData['rfq_delivery_date'])) { ?>
                <li class="list-stats-item">
                    <span class="lable"><?php echo Labels::getLabel('LBL_EXPECTED_DELIVERY_DATE', $siteLangId); ?>:</span>
                    <span class="value"><?php echo  FatDate::format($rfqData['rfq_delivery_date']); ?></span>
                </li>
            <?php } ?>
            <li class="list-stats-item">
                <span class="lable"><?php echo Labels::getLabel('LBL_PROCESSING_STATUS', $siteLangId); ?>:</span>
                <span class="value"><?php echo '<span class="' . RequestForQuote::getBadgeClass($rfqData['rfq_status']) . '">' . $statusArr[$rfqData['rfq_status']] . '</span>'; ?></span>
            </li>
            <li class="list-stats-item">
                <span class="lable"><?php echo Labels::getLabel('LBL_APPROVAL', $siteLangId); ?>:</span>
                <span class="value"><?php echo '<span class="' . RequestForQuote::getBadgeClass($rfqData['rfq_approved']) . '">' . $approvalStatusArr[$rfqData['rfq_approved']] . '</span>'; ?></span>
            </li>

            <li class="list-stats-item">
                <span class="lable"><?php echo Labels::getLabel('LBL_REQUESTED_ON', $siteLangId); ?>:</span>
                <span class="value"><?php echo  FatDate::format($rfqData['rfq_added_on']); ?></span>
            </li>

            <li class="list-stats-item list-stats-item-full">
                <span class="lable"><?php echo Labels::getLabel('LBL_COMMENT', $siteLangId); ?>:</span>
                <span class="value">
                    <span class="lessContentJs">
                        <?php echo 200 < strlen((string)$rfqData['rfq_description']) ? substr($rfqData['rfq_description'], 0, 200) . ' ... <button class="link-underline showMoreJs">' . Labels::getLabel('LBL_SHOW_MORE') . '</button>' : $rfqData['rfq_description']; ?>
                    </span>
                    <span class="moreContentJs" style="display:none">
                        <?php echo $rfqData['rfq_description'] . ' <button class="link-underline showLessJs">' . Labels::getLabel('LBL_SHOW_LESS') . '</button>'; ?>
                    </span>
                </span>
            </li>
            <li class="list-stats-item list-stats-item-full">
                <span class="lable"><?php echo Labels::getLabel('LBL_BUYER_INFO', $siteLangId); ?>:</span>
                <?php
                $onclick = ($canViewUsers ? 'redirectUser(' . $rfqData['user_id'] . ')' : '');
                $this->includeTemplate('_partial/user/user-info-card.php', ['user' => $rfqData, 'siteLangId' => $siteLangId, 'href' => 'javascript:void(0)', 'onclick' => $onclick, 'extraClass' => 'user-profile-sm', 'displayProfileImage' => false]); ?>
            </li>
            <?php $res = AttachedFile::getAttachment(AttachedFile::FILETYPE_RFQ, $recordId, 0, -1);
            if (!empty($res) && 0 < $res['afile_id']) { ?>
                <li class="list-stats-item list-stats-item-full">
                    <span class="lable"><?php echo Labels::getLabel('LBL_ATTACHMENT', $siteLangId); ?>:</span>
                    <span class="value">
                        <a target="blank" title="<?php echo Labels::getLabel('LBL_DOWNLOAD_FILE', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('RequestForQuotes', 'downloadFile', array($recordId)); ?>">
                            <svg class="svg" width="16" height="16">
                                <use xlink:href="<?php echo CONF_WEBROOT_FRONT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icon-download"></use>
                            </svg>
                            <?php echo $res['afile_name']; ?>
                        </a>
                    </span>
                </li>
            <?php } ?>
            <?php if (Product::PRODUCT_TYPE_DIGITAL != $rfqData['rfq_product_type']) { ?>
                <li class="list-stats-item list-stats-item-full">
                    <span class="lable"><?php echo Labels::getLabel('LBL_DELIVERY_ADDRESS', $siteLangId); ?>:</span>
                    <?php if (!empty($rfqData['addr_name'])) { ?>
                        <span class="value">
                            <strong><?php echo $rfqData['addr_name']; ?></strong>,
                            <?php echo $rfqData['addr_address1']; ?>,
                            <?php echo (strlen((string)$rfqData['addr_address2']) > 0) ? $rfqData['addr_address2'] . ',' : ''; ?>
                            <?php echo (strlen((string)$rfqData['addr_city']) > 0) ? $rfqData['addr_city'] . ',' : ''; ?>
                            <?php echo (strlen((string)$rfqData['state_name']) > 0) ? $rfqData['state_name'] . ',' : ''; ?>
                            <?php echo (strlen((string)$rfqData['country_name']) > 0) ? $rfqData['country_name'] . ',' : ''; ?>
                            <?php echo (strlen((string)$rfqData['addr_zip']) > 0) ? Labels::getLabel('LBL_Zip:', $siteLangId) . $rfqData['addr_zip'] . ',' : ''; ?>
                            <?php $dcode = (strlen((string)$rfqData['addr_phone_dcode']) > 0) ? ValidateElement::formatDialCode($rfqData['addr_phone_dcode']) : ''; ?>
                            <?php echo (strlen((string)$rfqData['addr_phone']) > 0) ? Labels::getLabel('LBL_Phone:', $siteLangId) . $dcode . $rfqData['addr_phone'] . ',' : ''; ?>
                        </span>
                    <?php } ?>
                </li>
            <?php } ?>
        </ul>
    </div>
</div>