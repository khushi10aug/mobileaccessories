<?php defined('SYSTEM_INIT') or die('Invalid Usage . ');
if (!empty($digitalDownloads)) { ?>
    <div class="mt-5">
        <h6>
            <?php echo Labels::getLabel('LBL_Downloads', $siteLangId); ?>
        </h6>
        <div class="js-scrollable table-wrap table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>
                            <?php echo Labels::getLabel('LBL_#', $siteLangId); ?>
                        </th>
                        <th>
                            <?php echo Labels::getLabel('LBL_File', $siteLangId); ?>
                        </th>
                        <th>
                            <?php echo Labels::getLabel('LBL_Language', $siteLangId); ?>
                        </th>
                        <th>
                            <?php echo Labels::getLabel('LBL_Download_times', $siteLangId); ?>
                        </th>
                        <th>
                            <?php echo Labels::getLabel('LBL_Downloaded_count', $siteLangId); ?>
                        </th>
                        <th>
                            <?php echo Labels::getLabel('LBL_Expired_on', $siteLangId); ?>
                        </th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $sr_no = 1;
                    foreach ($digitalDownloads as $key => $row) {
                        $lang_name = Labels::getLabel('LBL_All', $siteLangId);
                        if ($row['afile_lang_id'] > 0) {
                            $lang_name = $languages[$row['afile_lang_id']];
                        }

                        if ($row['downloadable']) {
                            $fileName = '<a class="file-name" title="' . $row['afile_name'] . '" data-bs-toggle="tooltip" href="' . UrlHelper::generateUrl('Buyer', 'downloadDigitalFile', array($row['afile_id'], $row['afile_record_id'])) . '">' . $row['afile_name'] . '</a>';
                        } else {
                            $fileName = '<div class="file-name" title="' . $row['afile_name'] . '" data-bs-toggle="tooltip">' . $row['afile_name'] . '</div>';
                        }
                        $downloads = '<li>
                                                <a href="' . UrlHelper::generateUrl('Buyer', 'downloadDigitalFile', array($row['afile_id'], $row['afile_record_id'])) . '">
                                                    <svg class="svg" width="18" height="18">
                                                        <use
                                                            xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#download">
                                                        </use>
                                                    </svg>
                                                </a>
                                            </li>';

                        $expiry = Labels::getLabel('LBL_N/A', $siteLangId);
                        if ($row['expiry_date'] != '') {
                            $expiry = FatDate::Format($row['expiry_date']);
                        }

                        $downloadableCount = Labels::getLabel('LBL_N/A', $siteLangId);
                        if ($row['downloadable_count'] != -1) {
                            $downloadableCount = $row['downloadable_count'];
                        } ?>
                        <tr>
                            <td>
                                <?php echo $sr_no; ?>
                            </td>
                            <td>
                                <?php echo '<div class="actions-downloads">' . $fileName . '</div>'; ?>
                            </td>
                            <td>
                                <?php echo $lang_name; ?>
                            </td>
                            <td>
                                <?php echo $downloadableCount; ?>
                            </td>
                            <td>
                                <?php echo $row['afile_downloaded_times']; ?>
                            </td>
                            <td>
                                <?php echo $expiry; ?>
                            </td>
                            <td>
                                <?php if ($row['downloadable']) { ?>
                                    <ul class="actions">
                                        <?php echo $downloads; ?>
                                    </ul>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php $sr_no++;
                    } ?>
                </tbody>
            </table>
        </div>
    </div>
<?php } ?>

<?php if (!empty($digitalDownloadLinks)) { ?>
    <div class="mt-5">
        <h6>
            <?php echo Labels::getLabel('LBL_DOWNLOAD_LINKS', $siteLangId); ?>
        </h6>
        <div class="js-scrollable table-wrap table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>
                            <?php echo Labels::getLabel('LBL_#', $siteLangId); ?>
                        </th>
                        <th>
                            <?php echo Labels::getLabel('LBL_Link', $siteLangId); ?>
                        </th>
                        <th>
                            <?php echo Labels::getLabel('LBL_Download_times', $siteLangId); ?>
                        </th>
                        <th>
                            <?php echo Labels::getLabel('LBL_Downloaded_count', $siteLangId); ?>
                        </th>
                        <th>
                            <?php echo Labels::getLabel('LBL_Expired_on', $siteLangId); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php $sr_no = 1;
                    foreach ($digitalDownloadLinks as $key => $row) {
                        $expiry = Labels::getLabel('LBL_N/A', $siteLangId);
                        if ($row['expiry_date'] != '') {
                            $expiry = FatDate::Format($row['expiry_date']);
                        }

                        $downloadableCount = Labels::getLabel('LBL_N/A', $siteLangId);
                        if ($row['downloadable_count'] != -1) {
                            $downloadableCount = $row['downloadable_count'];
                        }

                        $link = ($row['downloadable'] != 1) ? Labels::getLabel('LBL_N/A', $siteLangId) : $row['opddl_downloadable_link'];
                        $linkUrl = ($row['downloadable'] != 1) ? 'javascript:void(0)' : $row['opddl_downloadable_link'];
                        $linkOnClick = ($row['downloadable'] != 1) ? '' : 'return increaseDownloadedCount(' . $row['opddl_link_id'] . ',' . $row['op_id'] . '); ';
                        $linkTitle = ($row['downloadable'] != 1) ? '' : Labels::getLabel('LBL_Click_to_download', $siteLangId); ?>
                        <tr>
                            <td>
                                <?php echo $sr_no; ?>
                            </td>
                            <td>
                                <div class="actions-downloads">
                                    <a class="file-name" title="<?php echo $linkUrl; ?>" data-bs-toggle="tooltip" target="_blank" onclick="<?php echo $linkOnClick; ?> " href="<?php echo $linkUrl; ?>" data-link="<?php echo $linkUrl; ?>" title="<?php echo $linkTitle; ?>">
                                        <?php echo $link; ?>
                                    </a>
                                </div>
                            </td>
                            <td>
                                <?php echo $downloadableCount; ?>
                            </td>
                            <td>
                                <?php echo $row['opddl_downloaded_times']; ?>
                            </td>
                            <td>
                                <?php echo $expiry; ?>
                            </td>
                        </tr>
                    <?php $sr_no++;
                    } ?>
                </tbody>
            </table>
        </div>
    </div>
<?php } ?>

<?php
if (!$orderDetail['order_deleted'] && !$primaryOrder && !$orderDetail["order_payment_status"] && 'TransferBank' == $orderDetail['plugin_code']) { ?>
    <div class="mt-5">
        <h6>
            <?php echo Labels::getLabel('LBL_ORDER_PAYMENTS', $siteLangId); ?>
        </h6>
        <div class="info-order">
            <?php
            $frm->setFormTagAttribute('onsubmit', 'updatePayment(this); return(false);');
            $frm->setFormTagAttribute('class', 'form');
            $frm->developerTags['colClassPrefix'] = 'col-md-';
            $frm->developerTags['fld_default_col'] = 12;


            $paymentFld = $frm->getField('opayment_method');
            $paymentFld->developerTags['col'] = 4;

            $gatewayFld = $frm->getField('opayment_gateway_txn_id');
            $gatewayFld->developerTags['col'] = 4;

            $amountFld = $frm->getField('opayment_amount');
            $amountFld->developerTags['col'] = 4;

            $submitFld = $frm->getField('btn_submit');
            $submitFld->developerTags['col'] = 4;
            $submitFld->addFieldTagAttribute('class', 'btn btn-brand');
            $submitFld->value = Labels::getLabel("LBL_SUBMIT_REQUEST", $siteLangId);
            echo $frm->getFormHtml(); ?>
        </div>
    </div>
<?php } ?>