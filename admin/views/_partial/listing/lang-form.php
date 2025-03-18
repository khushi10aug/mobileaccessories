<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

HtmlHelper::formatFormFields($langFrm);
if (!$langFrm->getFormTagAttribute('id')) {
    $langFrm->setFormTagAttribute('id', 'frmLangJs');
}

$langTabExtraClass = $langTabExtraClass ?? '';
$langExtraCls = !empty($langTabExtraClass) ? ', "' . $langTabExtraClass . '"' : '';

if (!$langFrm->getFormTagAttribute('data-onclear')) {
    $langFrm->setFormTagAttribute('data-onclear', 'editLangData(' . $recordId . ',' . $lang_id . ', 0' . $langExtraCls . ')');
}

$langFrm->setFormTagAttribute('class', 'modal-body form form-edit modalFormJs');
if (CommonHelper::getLayoutDirection() != $formLayout) {
    $langFrm->addFormTagAttribute('class', "layout--" . $formLayout);
    $langFrm->setFormTagAttribute('dir', $formLayout);
}

if (!$langFrm->getFormTagAttribute('onsubmit')) {
    $langFrm->setFormTagAttribute('onsubmit', 'saveLangData($("#' . $langFrm->getFormTagAttribute('id') . '")[0]); return(false);');
}

$langFld = $langFrm->getField('lang_id');
if (null != $langFld) {
    if (!$langFld->getfieldTagAttribute('onChange')) {
        $langFld->setfieldTagAttribute('onChange', "editLangData(" . $recordId . ", this.value, 0" . $langExtraCls . ");");
    }

    if (!isset($langFld->htmlAfterField) || empty($langFld->htmlAfterField)) {
        $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
        if (!empty($translatorSubscriptionKey) && $lang_id != CommonHelper::getDefaultFormLangId()) {
            $langFld->developerTags['fldWidthValues'] = ['d-flex', '', '', ''];
            $langFld->htmlAfterField = '<a href="javascript:void(0);" onclick="editLangData(' . $recordId . ', ' . $lang_id . ', 1)" class="btn" title="' .  Labels::getLabel('BTN_AUTOFILL_LANGUAGE_DATA', $siteLangId) . '">
                                            <svg class="svg" width="18" height="18">
                                                <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite.yokart.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#icon-translate">
                                                </use>
                                            </svg>
                                        </a>';
        }
    }
}

$activeLangtab = true;
require_once(CONF_THEME_PATH . '_partial/listing/form-head.php'); ?>
<div class="form-edit-body loaderContainerJs">
    <?php echo $langFrm->getFormHtml(); ?>
</div>
<?php require_once(CONF_THEME_PATH . '_partial/listing/form-edit-foot.php'); ?>
</div> <!-- Close </div> This must be placed. Opening tag is inside form-head.php file. -->