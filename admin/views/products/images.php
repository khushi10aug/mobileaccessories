<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
foreach ($images as $image) {
    $uploadedTime = AttachedFile::setTimeParam($image['afile_updated_at']);      
    $imgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($image['afile_record_id'], ImageDimension::VIEW_THUMB, 0, $image['afile_id'], $image['afile_lang_id'], $image['afile_type']), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
    if ($isDefaultLayout  == applicationConstants::YES) {    ?>
        <li id="<?php echo $image['afile_id']; ?>">
            <div class="uploaded-stocks-item" data-ratio="1:1">
                <img <?php echo HtmlHelper::getImgDimParm(ImageDimension::TYPE_PRODUCTS, ImageDimension::VIEW_THUMB); ?> class="uploaded-stocks-img" data-bs-toggle="tooltip" data-placement="top" src="<?php echo $imgUrl; ?>" title="<?php echo $image['afile_name']; ?>" alt="<?php echo $image['afile_name']; ?>">
                <div class="uploaded-stocks-actions">
                    <?php if ($canEdit) { ?>
                        <ul class="actions">
                            <li>
                                <a href="javascript:void(0)" onclick="deleteImage(<?php echo $image['afile_record_id']; ?>, <?php echo $image['afile_id']; ?>, <?php echo $image['afile_type']; ?>);">
                                    <svg class="svg" width="18" height="18">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#delete">
                                        </use>
                                    </svg>
                                </a>
                            </li>
                        </ul>
                    <?php }  ?>
                </div>
            </div>
        </li>
    <?php } else { ?>
        <li class="upload__list-item" id="<?php echo $image['afile_id']; ?>">
            <div class="media">
                <img class="mr-2 product-profile-img" <?php echo HtmlHelper::getImgDimParm(ImageDimension::TYPE_PRODUCTS, ImageDimension::VIEW_THUMB); ?> src="<?php echo $imgUrl; ?>" title="<?php echo $image['afile_name']; ?>" alt="<?php echo $image['afile_name']; ?>">
            </div>
            <div class="title"><?php echo $image['afile_name'] ?></div>
            <?php if ($canEdit) { ?>
                <div class="action">
                    <a href="javascript:void(0);" class="" title="<?php echo Labels::getLabel('FRM_REMOVE_IMAGE', $siteLangId); ?>" onclick="deleteImage(<?php echo $image['afile_record_id']; ?>, <?php echo $image['afile_id']; ?>, <?php echo $image['afile_type']; ?>);"> </a>
                </div>
            <?php }  ?>
            </div>
        </li>

    <?php }
}

if ($isDefaultLayout  == applicationConstants::YES) {
    for ($i = 0; $i < (4 - count($images)); $i++) {       
    ?>
        <li class="unsortableJs">
            <div class="uploaded-stocks-item" data-ratio="1:1">
                <img class="uploaded-stocks-img" data-bs-toggle="tooltip" data-placement="top" src="<?php echo CONF_WEBROOT_FRONTEND; ?>images/defaults/product_default_image.jpg" title="" alt="">
                <div class="uploaded-stocks-actions">
                </div>
            </div>
        </li>
    <?php
    }
}

