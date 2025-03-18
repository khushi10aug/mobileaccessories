<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
HtmlHelper::formatFormFields($langFrm);

$langFrm->setFormTagAttribute('class', 'form modalFormJs');
if (CommonHelper::getLayoutDirection() != $formLayout) {
    $langFrm->addFormTagAttribute('class', "layout--" . $formLayout);
    $langFrm->setFormTagAttribute('dir', $formLayout);
}
$langFrm->setFormTagAttribute('data-onclear', "editRateLangForm(" . $zoneId . ", " . $rateId . ", " . $langId . ");");
$langFrm->setFormTagAttribute('onsubmit', 'setupLangRate(this); return(false);');


$langFld = $langFrm->getField('lang_id');
$langFld->setfieldTagAttribute('onChange', "editRateLangForm(" . $zoneId . ", " . $rateId . ", this.value);");
$translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
if (!empty($translatorSubscriptionKey) && $langId != CommonHelper::getDefaultFormLangId()) {
    $langFld->developerTags['fldWidthValues'] = ['d-flex', '', '', ''];
    $langFld->htmlAfterField = '<a href="javascript:void(0);" onclick="editRateLangForm(' . $zoneId . ', ' . $rateId . ', ' . $langId . ', 1)" class="btn" title="' .  Labels::getLabel('BTN_AUTOFILL_LANGUAGE_DATA', $siteLangId) . '">
                                <svg class="svg" width="18" height="18">
                                    <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite.yokart.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#icon-translate">
                                    </use>
                                </svg>
                            </a>';
}
?>

<div class="modal-header">
    <h5 class="modal-title"><?php echo Labels::getLabel('LBL_SHIPPING_RATES_SETUP', $siteLangId); ?></h5>
</div>
<div class="modal-body form-edit">
    <?php if(count($languages)){ ?>
        <div class="form-edit-head">
            <nav class="nav nav-tabs navTabsJs">
                <a class="nav-link" href="javascript:void(0)" onclick="addEditShipRates(<?php echo $zoneId ?>, <?php echo $rateId ?>);"><?php echo Labels::getLabel('LBL_General', $siteLangId); ?></a>
                <a class="nav-link active" href="javascript:void(0);" <?php echo (0 < $rateId) ? "onclick='editRateLangForm(" . $zoneId . "," . $rateId . ", " . array_key_first($languages) . ");'" : ""; ?>>
                    <?php echo Labels::getLabel('LBL_LANGUAGE_DATA', $siteLangId); ?>
                </a>
            </nav>
        </div>
    <?php } ?>
    <div class="form-edit-body loaderContainerJs" id="selectedTabContentJs">
        <?php echo $langFrm->getFormHtml(); ?>
    </div>
    <?php require_once(CONF_THEME_PATH . '_partial/listing/form-edit-foot.php'); ?>
</div>
