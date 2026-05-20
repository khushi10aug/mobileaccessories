<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
HtmlHelper::formatFormFields($frm);

$frm->setFormTagAttribute('data-onclear', 'openImageAttributeForm(' . $recordId . ', ' . $moduleType . ')');
$frm->setFormTagAttribute('id', 'frmImgAttributeJs');
$frm->setFormTagAttribute('onsubmit', 'saveImageAttributes(this); return(false);');

$formLayout = Language::getLayoutDirection(CommonHelper::getDefaultFormLangId());
$frm->setFormTagAttribute('class', 'form modalFormJs layout--' . $formLayout);

$langFld = $frm->getField('lang_id');
$langFld->addFieldTagAttribute('class', 'languageJs');

$optionIdFld = $frm->getField('option_id');
if ($optionIdFld !== null) {
    $optionIdFld->addFieldTagAttribute('class', 'optionJs');
}

?>
<div class="modal-header">
    <h5 class="modal-title">
        <?php echo $title; ?>
    </h5>
</div>
<div class="modal-body form-edit">
    <div class="form-edit-body loaderContainerJs">
        <?php
        echo $frm->getFormTag();
        HtmlHelper::renderHiddenFields($frm);
        echo $frm->getFieldHtml('module_type');
        echo $frm->getFieldHtml('record_id');
        if (1 == count($languages)) {
            echo $frm->getFieldHtml('lang_id');
        }
        ?>
        <div class="row">
            <?php if ($optionIdFld !== null) { ?>
                <div class="col-md-<?php echo (1 < count($languages)) ? '6' : '12'; ?>">
                    <div class="form-group">
                        <label class="label">
                            <?php echo $optionIdFld->getCaption(); ?>
                        </label>
                        <?php echo $frm->getFieldHtml('option_id'); ?>
                    </div>
                </div>
            <?php }
            if (1 < count($languages)) { ?>
                <div class="col-md-6">
                    <div class="form-group">

                        <label class="label">
                            <?php
                            $fld = $frm->getField('lang_id');
                            echo $fld->getCaption();
                            ?>
                        </label>
                        <?php echo $frm->getFieldHtml('lang_id'); ?>
                    </div>
                </div>
            <?php } ?>
        </div>
        <?php if (!empty($images)) { ?>
            <table class="table table-slots">
                <thead>
                    <tr>
                        <th><?php echo Labels::getLabel('LBL_IMAGE', $siteLangId); ?></th>
                        <th><?php echo Labels::getLabel('LBL_IMAGE_TITLE', $siteLangId); ?></th>
                        <th><?php echo Labels::getLabel('LBL_IMAGE_ALT', $siteLangId); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($images as $afileId => $afileData) {
                        $uploadedTime = AttachedFile::setTimeParam($afileData['afile_updated_at']);
                        $frm->getField('image_title' . $afileId)->value = $afileData['afile_attribute_title'];
                        $frm->getField('image_alt' . $afileId)->value = $afileData['afile_attribute_alt'];
                        switch ($moduleType) {
                            case AttachedFile::FILETYPE_PRODUCT_IMAGE:
                                $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_PRODUCTS, ImageDimension::VIEW_THUMB);
                                $imageUrl = UrlHelper::generateFullUrl('Image', 'Product', array($recordId, ImageDimension::VIEW_THUMB, 0, $afileId, $langId), CONF_WEBROOT_FRONT_URL);
                                break;
                            case AttachedFile::FILETYPE_BRAND_LOGO:
                                $languages = Language::getAllNames();
                                $brandLogo = AttachedFile::getAttachment(AttachedFile::FILETYPE_BRAND_LOGO, $recordId, 0, $langId, (count($languages) > 1) ? false : true);
                                $aspectRatioType = $brandLogo['afile_aspect_ratio'];
                                $aspectRatioType = ($aspectRatioType > 0) ? $aspectRatioType : 1;
                                $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_BRAND_LOGO, ImageDimension::VIEW_THUMB, $aspectRatioType);
                                $imageUrl = UrlHelper::generateFullUrl('Image', 'brand', array($recordId, $langId, ImageDimension::VIEW_THUMB, $afileId), CONF_WEBROOT_FRONT_URL);
                                break;
                            case AttachedFile::FILETYPE_BRAND_IMAGE:
                                $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_BRAND_IMAGE, ImageDimension::VIEW_THUMB);
                                $imageUrl = UrlHelper::generateFullUrl('Image', 'brandImage', array($recordId, $langId, ImageDimension::VIEW_THUMB, $afileId), CONF_WEBROOT_FRONT_URL);
                                break;
                            case AttachedFile::FILETYPE_BLOG_POST_IMAGE:
                                $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_BLOG_POST, ImageDimension::VIEW_THUMB);
                                $imageUrl = UrlHelper::generateFullUrl('Image', 'blogPost', array($recordId, $langId, ImageDimension::VIEW_THUMB, 0, $afileId, false), CONF_WEBROOT_FRONT_URL);
                                break;
                            case AttachedFile::FILETYPE_CATEGORY_IMAGE:
                                $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_CATEGORY_IMAGE, ImageDimension::VIEW_THUMB);
                                $imageUrl = UrlHelper::generateFullUrl('Category', 'image', array($recordId, $langId, ImageDimension::VIEW_THUMB, $afileId), CONF_WEBROOT_FRONT_URL);
                                break;
                            default:
                                $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_CATEGORY_BANNER, ImageDimension::VIEW_THUMB);
                                $imageUrl = UrlHelper::generateFullUrl('Category', 'banner', array($recordId, $langId, ImageDimension::VIEW_THUMB, $afileId), CONF_WEBROOT_FRONT_URL);
                                break;
                        } ?>
                        <tr>
                            <td>
                                <img class="border" data-aspect-ratio="<?php echo $imageDimensions[ImageDimension::VIEW_THUMB]['aspectRatio']; ?>" src="<?php echo UrlHelper::getCachedUrl($imageUrl . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg'); ?>">
                            </td>
                            <td><?php echo $frm->getFieldHtml('image_title' . $afileId); ?></td>
                            <td><?php echo $frm->getFieldHtml('image_alt' . $afileId); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } else {
            echo HtmlHelper::getErrorMessageHtml(Labels::getLabel('LBL_No_Image_Found', $siteLangId));
        }  ?>
        </form>
        <?php echo $frm->getExternalJS(); ?>
    </div>

    <?php require_once(CONF_THEME_PATH . '_partial/listing/form-edit-foot.php'); ?>
</div>