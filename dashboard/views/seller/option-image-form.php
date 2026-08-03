<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

HtmlHelper::formatFormFields($frm);
$frm->setFormTagAttribute('class', 'form');
$frm->setFormTagAttribute('name', 'optionImageFrm');
$frm->setFormTagAttribute('id', 'optionImageFrm');

$fld = $frm->getField('prod_image');
$fld->addFieldTagAttribute('onChange', 'loadOptionImageCropper(this)');
$fld->addFieldTagAttribute('accept', 'image/*');
$fld->addFieldTagAttribute('data-name', Labels::getLabel('FRM_PRODUCT_IMAGE', $siteLangId));

$fld = $frm->getField('option_id');
$fld->addFieldTagAttribute('id', 'option_image_option_id');

$fld = $frm->getField('file_type');
$fld->addFieldTagAttribute('id', 'option_image_file_type');

$fld = $frm->getField('lang_id');
$fld->addFieldTagAttribute('id', 'option_image_lang_id');
$fld->addFieldTagAttribute('class', 'option-image-lang-js');

$fld = $frm->getField('record_id');
$fld->addFieldTagAttribute('id', 'option_image_record_id');

$fld = $frm->getField('images');
$fld->value = '<div class="upload__files"><ul class="upload__list" id="optionProductImagesJs"></ul></div>';

$displayFooterButtons = false;
$includeTabs = false;
$formTitle = Labels::getLabel('LBL_MEDIA_SETUP', $siteLangId) . ' - ' . $optionLabel;
require_once(CONF_THEME_PATH . '_partial/listing/form.php');
?>
<script type="text/javascript">
    $(function () {
        var recordId = $('#option_image_record_id').val();
        var optionId = $('#option_image_option_id').val();
        var langId = $('#option_image_lang_id').val() || 0;
        loadOptionImages(recordId, optionId, langId);
    });
</script>
