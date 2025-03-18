<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

$frm->setFormTagAttribute('class', 'form modalFormJs');
HtmlHelper::formatFormFields($frm);
$fld = $frm->getField('download_type');
if (null != $fld) {
    $fld->addFieldTagAttribute('class', 'downloadTypeJs');
}

$includeTabs = false;
require_once(CONF_THEME_PATH . '_partial/listing/form-head.php'); ?>
<div class="form-edit-body loaderContainerJs">
    <div class="row">
        <?php echo $frm->getFormHtml(); ?>
        <div class="col-md-12" class="dd-list">
            <?php if (!empty($digitalDownloads)) { ?>
                <div class="js-scrollable table-wrap downloadTypeSectionJs downloadType-<?php echo applicationConstants::DIGITAL_DOWNLOAD_FILE; ?>">
                    <table class="table table-justified table-orders">
                        <tbody>
                            <tr class="">
                                <th><?php echo Labels::getLabel('LBL_#', $siteLangId); ?></th>
                                <th><?php echo Labels::getLabel('LBL_File', $siteLangId); ?></th>
                                <th><?php echo Labels::getLabel('LBL_Language', $siteLangId); ?></th>
                                <th><?php echo Labels::getLabel('LBL_Download_times', $siteLangId); ?></th>
                                <th><?php echo Labels::getLabel('LBL_Downloaded_count', $siteLangId); ?></th>
                                <th><?php echo Labels::getLabel('LBL_Expired_on', $siteLangId); ?></th>
                                <th><?php echo Labels::getLabel('LBL_ACTION', $siteLangId); ?></th>
                            </tr>
                            <?php $sr_no = 1;
                            foreach ($digitalDownloads as $key => $row) {
                                $lang_name = Labels::getLabel('LBL_All', $siteLangId);
                                if ($row['afile_lang_id'] > 0) {
                                    $lang_name = $languages[$row['afile_lang_id']];
                                }

                                $fileName = '<a href="' . UrlHelper::generateUrl('Orders', 'downloadOpAttachment', array($row['afile_id'], $row['afile_record_id'], AttachedFile::FILETYPE_ORDER_PRODUCT_DIGITAL_DOWNLOAD)) . '">' . $row['afile_name'] . '</a>';
                                $downloads = '<li><a href="' . UrlHelper::generateUrl('Orders', 'downloadOpAttachment', array($row['afile_id'], $row['afile_record_id'], AttachedFile::FILETYPE_ORDER_PRODUCT_DIGITAL_DOWNLOAD)) . '"><svg class="svg" width="18" height="18">
                                        <use
                                            xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#download">
                                        </use>
                                    </svg></a></li>';

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
                                    <td><?php echo $fileName; ?></td>
                                    <td><?php echo $lang_name; ?></td>
                                    <td><?php echo $downloadableCount; ?></td>
                                    <td><?php echo $row['afile_downloaded_times']; ?></td>
                                    <td><?php echo $expiry; ?></td>
                                    <td>
                                        <ul class="actions"><?php echo $downloads; ?></ul>
                                    </td>
                                </tr>
                            <?php $sr_no++;
                            } ?>
                        </tbody>
                    </table>
                </div>
            <?php } ?>
            <?php if (!empty($digitalDownloadLinks)) { ?>
                <div class="js-scrollable table-wrap downloadTypeSectionJs downloadType-<?php echo applicationConstants::DIGITAL_DOWNLOAD_LINK; ?>" <?php echo (!empty($digitalDownloads)) ? 'style="display:none"' : ''; ?>>
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
        </div>
    </div>
</div> <!-- Close </div> This must be placed. Opening tag is inside form-head.php file. -->