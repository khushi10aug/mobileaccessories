<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

$languages = $languages ?? [];
unset($languages[CommonHelper::getDefaultFormLangId()]);

$langFrm->setFormTagAttribute('class', 'modal-body form form-edit modalFormJs');
$langFrm->setFormTagAttribute('onsubmit', 'setupLangRate($("#' . $langFrm->getFormTagAttribute('id') . '")[0]); return(false);');
$langFrm->setFormTagAttribute('data-onclear', 'editRateLangForm(' . $zoneId . ', ' . $rateId . ', ' . array_key_first($languages) . ');');

HtmlHelper::formatFormFields($langFrm);

if (CommonHelper::getLayoutDirection() != $formLayout) {
    $langFrm->addFormTagAttribute('class', "layout--" . $formLayout);
    $langFrm->setFormTagAttribute('dir', $formLayout);
}

$formTitle = Labels::getLabel('LBL_SHIPPING_RATES_SETUP', $siteLangId);

$langFld = $langFrm->getField('lang_id');
if (null != $langFld) {
    $langFld->setFieldTagAttribute('onChange', 'editRateLangForm(' . $zoneId . ', ' . $rateId . ', this.value);');
    if (!isset($langFld->htmlAfterField) || empty($langFld->htmlAfterField)) {
        $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
        if (!empty($translatorSubscriptionKey) && $langId != CommonHelper::getDefaultFormLangId()) {
            $langFld->developerTags['fldWidthValues'] = ['d-flex', '', '', ''];
            $langFld->htmlAfterField = '<a href="javascript:void(0);" onclick="editRateLangForm(' . $zoneId . ', ' . $rateId . ', ' . array_key_first($languages) . ', 1)" class="btn" title="' .  Labels::getLabel('BTN_AUTOFILL_LANGUAGE_DATA', $siteLangId) . '">
                                            <svg class="svg" width="18" height="18">
                                                <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite.yokart.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#icon-translate">
                                                </use>
                                            </svg>
                                        </a>';
        }
    }
}
$generalTab = [
    'attr' => [
        'href' => 'javascript:void(0);',
        'onclick' => 'addEditShipRates(' . $zoneId . ', ' . $rateId . ')'
    ],
    'label' => Labels::getLabel('LBL_GENERAL', $siteLangId),
    'isActive' => false
];

$displayLangTab = false;

$otherButtons = [
    [
        'attr' => [
            'href' => 'javascript:void(0)',
            'onclick' => 'editRateLangForm(' . $zoneId . ', ' . $rateId . ', ' . array_key_first($languages) . ');',
        ],
        'label' => Labels::getLabel('LBL_LANGUAGE_DATA', $siteLangId),
        'isActive' => true
    ]
];


require_once(CONF_THEME_PATH . '_partial/listing/form-head.php'); ?>
    <div class="form-edit-body loaderContainerJs">
        <?php echo $langFrm->getFormHtml(); ?>
    </div>
    <?php require_once(CONF_THEME_PATH . '_partial/listing/form-edit-foot.php'); ?>
</div> <!-- Close </div> This must be placed. Opening tag is inside form-head.php file. -->