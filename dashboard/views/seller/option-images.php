<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
if (!empty($images)) {
    foreach ($images as $image) {
        $uploadedTime = AttachedFile::setTimeParam($image['afile_updated_at']);
        $imgUrl = UrlHelper::getCachedUrl(
            UrlHelper::generateFileUrl(
                'image',
                'product',
                array($image['afile_record_id'], ImageDimension::VIEW_THUMB, 0, $image['afile_id'], $image['afile_lang_id'], $image['afile_type']),
                CONF_WEBROOT_FRONTEND
            ) . $uploadedTime,
            CONF_IMG_CACHE_TIME,
            '.jpg'
        );
        ?>
        <li class="upload__list-item" id="<?php echo $image['afile_id']; ?>">
            <div class="media">
                <img class="mr-2 product-profile-img" <?php echo HtmlHelper::getImgDimParm(ImageDimension::TYPE_PRODUCTS, ImageDimension::VIEW_THUMB); ?> src="<?php echo $imgUrl; ?>" title="<?php echo $image['afile_name']; ?>" alt="<?php echo $image['afile_name']; ?>">
            </div>
            <div class="title"><?php echo $image['afile_name']; ?></div>
            <div class="action">
                <a href="javascript:void(0);" title="<?php echo Labels::getLabel('FRM_REMOVE_IMAGE', $siteLangId); ?>" onclick="deleteOptionImage(<?php echo $image['afile_record_id']; ?>, <?php echo $image['afile_id']; ?>);">
                    <svg class="svg" width="18" height="18">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#delete"></use>
                    </svg>
                </a>
            </div>
        </li>
        <?php
    }
}
