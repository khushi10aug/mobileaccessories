<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

HtmlHelper::formatFormFields($tempFrm);
$tempFrm->setFormTagAttribute('class', 'modal-body form form-edit modalFormJs layout--' . $formLayout);
$tempFrm->setFormTagAttribute('onsubmit', 'setup(this); return(false);');
$tempFrm->setFormTagAttribute('dir', $formLayout);
$tempFrm->setFormTagAttribute('data-onclear', "editStplData('" . $stplCode . "', " . $siteLangId . ");");
$tempFrm->setFormTagAttribute('id', 'frmLangJs');

$fld = $tempFrm->getField('lang_id');
$fld->setfieldTagAttribute('onChange', "editStplData('" . $stplCode . "', this.value);");
if (!isset($fld->htmlAfterField) || empty($fld->htmlAfterField)) {
    $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
    if (!empty($translatorSubscriptionKey) && $lang_id != CommonHelper::getDefaultFormLangId()) {
        $fld->developerTags['fldWidthValues'] = ['d-flex', '', '', ''];
        $fld->htmlAfterField = '<a href="javascript:void(0);" onclick="editStplData(\'' . $stplCode . '\', ' . $lang_id . ', 1)" class="btn" title="' .  Labels::getLabel('BTN_AUTOFILL_LANGUAGE_DATA', $siteLangId) . '">
                            <svg class="svg" width="18" height="18">
                                <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite.yokart.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#icon-translate">
                                </use>
                            </svg>
                        </a>';
    }
}

$fld = $tempFrm->getField("stpl_body");
$fld->setFieldTagAttribute('class', 'stplBodyJs');
$fld->setfieldTagAttribute('maxlength', applicationConstants::SMS_CHARACTER_LENGTH);
$fld->htmlAfterField = '<br/><small>' . Labels::getLabel('LBL_MAXIMUM_OF_160_CHARACTERS_ALLOWED', $siteLangId) . ' </small>';

$fld = $tempFrm->getField('auto_update_other_langs_data');
if ($fld != null) {
    HtmlHelper::configureSwitchForCheckbox($fld);
    $fld->developerTags['noCaptionTag'] = true;
    $fld->developerTags['colWidthValues'] = [null, '12', null, null];
}

$fld = $tempFrm->getField('stpl_replacements');
$repVarArr = !empty($fld->value) && LibHelper::isJson($fld->value) ? json_decode($fld->value, true) : [];
$repVarArr = is_array($repVarArr) ? $repVarArr : [];
if (!empty($repVarArr)) {
    $repVarHtml = '<ul class="click-to-copy">';
    $found = false;
    foreach ($repVarArr as $val) {
        if (!isset($val['variable'])) {
            continue;
        }
        $found = true;
        $repVarHtml .= '<li title="' . Labels::getLabel('LBL_CLICK_TO_COPY', $siteLangId) . '" onclick="copyText(this, true);" data-bs-toggle="tooltip" data-placement="top">
            <div class="text">
                <span>' . $val['title'] . '</span>
                <span class="badge badge-info">
                ' . ($val['variable'] ?? '') . '
                </span>
            </div>
        </li>';
    }
    if (false == $found) {
        $repVarHtml .= '<li>' . Labels::getLabel('LBL_N/A') . '</li>';
    }
    $repVarHtml .= '</ul>';
    $fld->value = $repVarHtml;
}

?>

<div class="modal-header">
    <h5 class="modal-title">
        <?php echo Labels::getLabel('LBL_SMS_TEMPLATE_SETUP', $siteLangId); ?>
    </h5>
</div>
<div class="modal-body form-edit">
    <div class="form-edit-body loaderContainerJs">
        <?php echo $tempFrm->getFormHtml(); ?>
    </div>

    <?php require_once(CONF_THEME_PATH . '_partial/listing/form-edit-foot.php'); ?>
</div>