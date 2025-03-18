<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
if ($canEdit && $displayForm && !$print) { ?>
    <div class="mt-5 no-print">
        <h5><?php echo Labels::getLabel('LBL_Comments_on_order', $siteLangId); ?></h5>
        <?php
        $frm->setFormTagAttribute('onsubmit', 'updateStatus(this); return(false);');
        $frm->setFormTagAttribute('class', 'form markAsShipped-js');
        $frm->developerTags['colClassPrefix'] = 'col-md-';
        $frm->developerTags['fld_default_col'] = 12;

        $manualFld = $frm->getField('manual_shipping');

        $fld = $frm->getField('op_status_id');
        if (null != $fld) {
            $fld->developerTags['col'] = (null != $manualFld) ? 4 : 6;
        }

        $statusFld = $frm->getField('op_status_id');
        $statusFld->setFieldTagAttribute('class', 'status-js fieldsVisibilityJs');

        $fld1 = $frm->getField('customer_notified');
        $fld1->setFieldTagAttribute('class', 'notifyCustomer-js');
        $fld1->developerTags['col'] = (null != $manualFld) ? 4 : 6;


        if (null != $manualFld) {
            $manualFld->setFieldTagAttribute('class', 'manualShippingJs fieldsVisibilityJs');
            $manualFld->developerTags['col'] = 4;
            HtmlHelper::configureSwitchForCheckbox($manualFld);
            $manualFld->developerTags['noCaptionTag'] = false;

            $fld = $frm->getField('tracking_number');
            $fld->developerTags['col'] = 4;

            $fld = $frm->getField('opship_tracking_url');
            $courierFld = $frm->getField('oshistory_courier');
            if (null != $fld) {
                $fld->developerTags['col'] = 4;
                $fld->setWrapperAttribute('class', 'trackingUrlBlk--js');
                $fld->setFieldTagAttribute('class', 'trackingUrlFld--js');
                if (null != $courierFld) {
                    $fld->htmlAfterField = '<a href="javascript:void(0)" onclick="courierFld()" class="link"><small>' . Labels::getLabel(
                        'LBL_OR_SELECT_COURIER_?',
                        $siteLangId
                    ) . '</small></a>';
                }
            }

            if (null != $courierFld) {
                $courierFld->developerTags['col'] = 4;
                $courierFld->setWrapperAttribute('class', 'courierBlk--js d-none');
                $courierFld->setFieldTagAttribute('class', 'courierFld--js');
                $courierFld->htmlAfterField = '<a href="javascript:void(0)" onclick="trackingUrlFld()" class="link"><small>' . Labels::getLabel(
                    'LBL_OR_TRACK_THROUGH_URL_?',
                    $siteLangId
                ) . '</small></a>';
            }
        }

        $fldBtn = $frm->getField('btn_submit');
        $fldBtn->setFieldTagAttribute('class', 'btn btn-brand');
        $fldBtn->developerTags['col'] = 6;
        echo $frm->getFormHtml(); ?>
    </div>
<?php } ?>

<?php if (true === $canAttachMoreFiles) { ?>

    <div class="mt-5 no-print">
        <h5><?php echo Labels::getLabel('LBL_Add_more_attachments', $siteLangId); ?></h5>
        <?php
        $moreAttachmentsFrm->setFormTagAttribute('class', 'form');
        $moreAttachmentsFrm->setFormTagAttribute('id', 'additional_attachments');
        $moreAttachmentsFrm->developerTags['colClassPrefix'] = 'col-md-';
        $moreAttachmentsFrm->developerTags['fld_default_col'] = 8;
        $fld = $moreAttachmentsFrm->getField('downloadable_file');
        $fld->setFieldTagAttribute('onchange', 'uploadAdditionalAttachment(this); return false;');        
        echo $moreAttachmentsFrm->getFormHtml();
        ?>
    </div>
<?php } ?>

<?php if (!empty($digitalDownloads)) { ?>
    <div class="mt-5 section--repeated js-scrollable table-wrap">
        <h5><?php echo Labels::getLabel('LBL_Downloads', $siteLangId); ?></h5>
        <table class="table table-justified table-orders">
            <tbody>
                <tr class="">
                    <th><?php echo Labels::getLabel('LBL_#', $siteLangId); ?></th>
                    <th><?php echo Labels::getLabel('LBL_File', $siteLangId); ?></th>
                    <th><?php echo Labels::getLabel('LBL_Language', $siteLangId); ?></th>
                    <th><?php echo Labels::getLabel('LBL_Download_times', $siteLangId); ?></th>
                    <th><?php echo Labels::getLabel('LBL_Downloaded_count', $siteLangId); ?></th>
                    <th><?php echo Labels::getLabel('LBL_Expired_on', $siteLangId); ?></th>
                    <?php if ($canEdit) { ?>
                        <th></th>
                    <?php } ?>
                </tr>
                <?php $sr_no = 1;
                foreach ($digitalDownloads as $key => $row) {
                    $lang_name = Labels::getLabel('LBL_All', $siteLangId);
                    if ($row['afile_lang_id'] > 0) {
                        $lang_name = $languages[$row['afile_lang_id']];
                    }
                    $fileName = $row['afile_name'];
                    $downloads = '';
                    if ($canDownload) {
                        $fileName = '<a href="' . UrlHelper::generateUrl('Seller', 'downloadOpAttachment', array($row['afile_id'], $row['afile_record_id'], AttachedFile::FILETYPE_ORDER_PRODUCT_DIGITAL_DOWNLOAD)) . '">' . $row['afile_name'] . '</a>';
                        $downloads = '<li><a href="' . UrlHelper::generateUrl('Seller', 'downloadOpAttachment', array($row['afile_id'], $row['afile_record_id'], AttachedFile::FILETYPE_ORDER_PRODUCT_DIGITAL_DOWNLOAD)) . '"><svg class="svg" width="18" height="18">
                                        <use
                                            xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#download">
                                        </use>
                                    </svg></a></li>';
                    }
                    $expiry = Labels::getLabel('LBL_N/A', $siteLangId);
                    if ($row['expiry_date'] != '') {
                        $expiry = FatDate::Format($row['expiry_date']);
                    }

                    $downloadableCount = Labels::getLabel('LBL_N/A', $siteLangId);
                    if ($row['downloadable_count'] != -1) {
                        $downloadableCount = $row['downloadable_count'];
                    } ?>
                    <tr>
                        <td><?php echo $sr_no; ?></td>
                        <td><?php echo '<div class="text-break">' . $fileName . '</div>'; ?></td>
                        <td><?php echo $lang_name; ?></td>
                        <td><?php echo $downloadableCount; ?></td>
                        <td><?php echo $row['afile_downloaded_times']; ?></td>
                        <td><?php echo $expiry; ?></td>
                        <td>
                            <ul class="actions"><?php echo ($canEdit) ? $downloads : ''; ?></ul>
                        </td>
                    </tr>
                <?php $sr_no++;
                } ?>
            </tbody>
        </table>
    </div>
<?php } ?>
<?php if (!empty($digitalDownloadLinks)) { ?>
    <div class="mt-5 section--repeated js-scrollable table-wrap">
        <h5><?php echo Labels::getLabel('LBL_Downloads', $siteLangId); ?></h5>
        <table class="table table-orders">
            <tbody>
                <tr class="">
                    <th><?php echo Labels::getLabel('LBL_#', $siteLangId); ?></th>
                    <th><?php echo Labels::getLabel('LBL_Link', $siteLangId); ?></th>
                    <th><?php echo Labels::getLabel('LBL_Download_times', $siteLangId); ?></th>
                    <th><?php echo Labels::getLabel('LBL_Downloaded_count', $siteLangId); ?></th>
                    <th><?php echo Labels::getLabel('LBL_Expired_on', $siteLangId); ?></th>
                </tr>
                <?php $sr_no = 1;
                foreach ($digitalDownloadLinks as $key => $row) {
                    $expiry = Labels::getLabel('LBL_N/A', $siteLangId);
                    if ($row['expiry_date'] != '') {
                        $expiry = FatDate::Format($row['expiry_date']);
                    }

                    $downloadableCount = Labels::getLabel('LBL_N/A', $siteLangId);
                    if ($row['downloadable_count'] != -1) {
                        $downloadableCount = $row['downloadable_count'];
                    } ?>
                    <tr>
                        <td><?php echo $sr_no; ?></td>
                        <td>
                            <div class="text-break"><a target="_blank" href="<?php echo $row['opddl_downloadable_link']; ?>" title="<?php echo Labels::getLabel('LBL_Click_to_download', $siteLangId); ?>"><?php echo $row['opddl_downloadable_link']; ?></a></div>
                        </td>
                        <td><?php echo $downloadableCount; ?></td>
                        <td><?php echo $row['opddl_downloaded_times']; ?></td>
                        <td><?php echo $expiry; ?></td>
                    </tr>
                <?php $sr_no++;
                } ?>
            </tbody>
        </table>
    </div>
<?php } ?>