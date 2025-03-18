<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

$langFrm->setFormTagAttribute('data-onclear', 'linkLangForm(' . $nav_id . ', ' . $nlink_id . ', ' . $nav_lang_id . ');');
$langFrm->setFormTagAttribute('onsubmit', 'setupLinksLang(this); return(false);');
$langFrm->setFormTagAttribute('class', 'modal-body form form-edit modalFormJs layout--' . $formLayout);
$langFrm->setFormTagAttribute('dir', $formLayout);

$langFld = $langFrm->getField('lang_id');
$langFld->setfieldTagAttribute('onChange', "linkLangForm(" . $nav_id . "," . $nlink_id . ", this.value);");

$generalTab = [
    'attr' => [
        'href' => 'javascript:void(0);',
        'onclick' => "addNewLinkForm(" . $nav_id . ", " . $nlink_id . ");",
        'title' => Labels::getLabel('LBL_GENERAL', $siteLangId)
    ],
    'label' => Labels::getLabel('LBL_GENERAL', $siteLangId),
    'isActive' => false
];

$otherButtons = [
    [
        'attr' => [
            'href' => 'javascript:void(0)',
            'onclick' => 'linkLangForm(' . $nav_id . ', ' . $nlink_id . ', ' . $nav_lang_id . ')',
            'title' => Labels::getLabel('LBL_LANGUAGE_DATA', $siteLangId),
        ],
        'label' => Labels::getLabel('LBL_LANGUAGE_DATA', $siteLangId),
        'isActive' => true
    ]
];
$displayLangTab = false;

HtmlHelper::formatFormFields($langFrm);

$translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
if (!empty($translatorSubscriptionKey) && $nav_lang_id != CommonHelper::getDefaultFormLangId()) {
    $langFld->developerTags['fldWidthValues'] = ['d-flex', '', '', ''];
    $langFld->htmlAfterField = '<a href="javascript:void(0);" onclick="linkLangForm(' . $nav_id . ', ' . $nlink_id . ', ' . $nav_lang_id . ', 1)" class="btn" title="' .  Labels::getLabel('BTN_AUTOFILL_LANGUAGE_DATA', $siteLangId) . '">
                                <svg class="svg" width="18" height="18">
                                    <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite.yokart.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#icon-translate">
                                    </use>
                                </svg>
                            </a>';
}
require_once(CONF_THEME_PATH . '_partial/listing/form-head.php'); ?>
    <div class="form-edit-body loaderContainerJs">
        <?php echo $langFrm->getFormHtml(); ?>
    </div>
    <?php require_once(CONF_THEME_PATH . '_partial/listing/form-edit-foot.php'); ?>
</div> <!-- Close </div> This must be placed. Opening tag is inside form-head.php file. -->