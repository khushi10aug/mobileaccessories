<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
foreach ($images as $image) {
    $uploadedTime = AttachedFile::setTimeParam($image['afile_updated_at']);
    $imgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($image['afile_record_id'], ImageDimension::VIEW_THUMB, 0, $image['afile_id'], $image['afile_lang_id'], $image['afile_type']), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
    if ($isDefaultLayout  == applicationConstants::YES) {
?>
        <li class="abc" id="<?php echo $image['afile_id']; ?>">
            <div class="uploaded-stocks-item" data-ratio="1:1">
                <img class="uploaded-stocks-img" <?php echo HtmlHelper::getImgDimParm(ImageDimension::TYPE_PRODUCTS, ImageDimension::VIEW_THUMB); ?> data-bs-toggle="tooltip" data-placement="top" src="<?php echo $imgUrl; ?>" title="<?php echo $image['afile_name']; ?>" alt="<?php echo $image['afile_name']; ?>">
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

if ($isDefaultLayout  == applicationConstants::NO && count($images)) {
    ?>
    <script type="text/javascript">
        $(function() {
            $("#productImagesJs").sortable({
                helper: fixWidthHelper,
                start: fixPlaceholderStyle,
                stop: function() {
                    var mysortarr = new Array();
                    $(this).find('li').each(function() {
                        mysortarr.push($(this).attr("id"));
                    });

                    var sort = mysortarr.join('-');
                    var lang_id = $('.language-js').val();
                    var record_id = $('#image_record_id').val();
                    var option_id = $('#image_option_id').val();
                    var option_id = $('#image_option_id').val();
                    var file_type = $('#image_file_type').val();
                    fcom.updateWithAjax(fcom.makeUrl('products', 'setImageOrder'), {
                        record_id,
                        file_type,
                        ids: sort
                    }, function (t) {fcom.displaySuccessMessage(t.msg)});
                }
            }).disableSelection();
        });
    </script>

<?php } elseif ($isDefaultLayout  == applicationConstants::YES && count($images)) {
?>
    <script type="text/javascript">
        $(function() {
            $("#productDefaultImagesJs").sortable({
                helper: fixWidthHelper,
                start: fixPlaceholderStyle,
                items: "li:not(.unsortableJs)",
                stop: function() {
                    var mysortarr = new Array();
                    $(this).find('li').each(function() {
                        mysortarr.push($(this).attr("id"));
                    });

                    var sort = mysortarr.join('-');
                    var lang_id = $('.language-js').val();
                    var record_id = $('#hiddenMediaFrmJs').find('[name="record_id"]').val();
                    var option_id = 0;
                    var file_type = $('#hiddenMediaFrmJs').find('[name="file_type"]').val();
                    fcom.updateWithAjax(fcom.makeUrl('products', 'setImageOrder'), {
                        record_id,
                        file_type,
                        ids: sort
                    }, function (t) {fcom.displaySuccessMessage(t.msg)});
                }
            }).disableSelection();
        });
    </script>


<?php
} ?>