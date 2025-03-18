<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

if (!empty($images)) {
    foreach ($images as $afile_id => $row) {
        $imageBadgeDimensions = ImageDimension::getData(ImageDimension::TYPE_BADGE_ICON, ImageDimension::VIEW_THUMB);
        $uploadedTime = AttachedFile::setTimeParam($row['afile_updated_at']);
        $imgUrl =  UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', 'badgeIcon', array($row['afile_record_id'], $row['afile_lang_id'], ImageDimension::VIEW_THUMB, 0, $row['afile_id']), CONF_WEBROOT_FRONT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
?>
        <div class="dropzone-uploaded dropzoneUploadedJs">
            <img data-aspect-ratio = "<?php echo $imageBadgeDimensions[ImageDimension::VIEW_THUMB]['aspectRatio']; ?>" src="<?php echo $imgUrl; ?>" title="<?php echo $row['afile_name']; ?>" alt="<?php echo $row['afile_name']; ?>">
            <?php if ($canEdit) { ?>
                <div class="dropzone-uploaded-action">
                    <ul class="actions">
                        <li>
                            <a href="javascript:void(0)" onclick="editDropZoneImages(this)" data-bs-toggle="tooltip" data-placement="top" title="<?php echo Labels::getLabel('FRM_CLICK_HERE_TO_EDIT', $siteLangId); ?>">
                                <svg class="svg" width="18" height="18">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#edit">
                                    </use>
                                </svg>
                            </a>
                        </li>
                        <li>
                            <a href="javascript:void(0)" onclick="deleteImage(<?php echo  $recordId; ?>, <?php echo $row['afile_id']; ?>, <?php echo $row['afile_lang_id']; ?>, <?php echo $row['afile_screen']; ?>);" data-bs-toggle="tooltip" data-placement="top" title="<?php echo Labels::getLabel('FRM_CLICK_HERE_TO_REMOVE', $siteLangId); ?>">
                                <svg class="svg" width="18" height="18">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#delete">
                                    </use>
                                </svg>
                            </a>
                        </li>
                    </ul>
                </div>
            <?php } ?>
        </div>
<?php }
} ?>