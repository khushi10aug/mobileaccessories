<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

HtmlHelper::formatFormFields($langFrm);

$langFrm->setFormTagAttribute('data-onclear', 'labelsForm(' . $recordId . ', ' . $labelType . ');');
$langFrm->setFormTagAttribute('class', 'modal-body modalFormJs form form-edit layout--' . $formLayout);
$langFrm->setFormTagAttribute('dir', $formLayout);
$langFrm->setFormTagAttribute('onsubmit', 'saveLangData(this); return(false);');

$fld = $langFrm->getField('key');
$fld->setFieldTagAttribute('disabled', 'disabled');
if (!isset($fld->htmlAfterField) || empty($fld->htmlAfterField)) {
    $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
    if (!empty($translatorSubscriptionKey) && 1 < count($languages)) {
        $fld->developerTags['fldWidthValues'] = ['d-flex', '', '', ''];
        $fld->htmlAfterField = '<a href="javascript:void(0);" onclick="labelsForm(' . $recordId . ', ' . $labelType . ', 1)" class="btn" title="' .  Labels::getLabel('BTN_AUTOFILL_LANGUAGE_DATA', $siteLangId) . '">
                            <svg class="svg" width="18" height="18">
                                <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite.yokart.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#icon-translate">
                                </use>
                            </svg>
                        </a>';
    }
}
?>

<div class="modal-header">
    <h5 class="modal-title">
        <?php echo Labels::getLabel('LBL_MANAGE_LABELS', $siteLangId); ?>
    </h5>
</div>
<div class="modal-body form-edit">
    <div class="form-edit-body loaderContainerJs">
        <?php echo $langFrm->getFormHtml(); ?>
    </div>

    <?php require_once(CONF_THEME_PATH . '_partial/listing/form-edit-foot.php'); ?>
</div>