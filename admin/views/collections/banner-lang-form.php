<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$langFrm->setFormTagAttribute('onsubmit', 'saveBannerLangData($("#' . $langFrm->getFormTagAttribute('id') . '")[0]); return(false);');
$langFrm->addFormTagAttribute('data-onclear', 'bannerLangForm(' . $collectionId . ',' . $recordId . ',' . $lang_id . ')');

$langFld = $langFrm->getField('bannerlang_lang_id');

$translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
if (!empty($translatorSubscriptionKey) && $lang_id != CommonHelper::getDefaultFormLangId()) {
    $langFld->developerTags['fldWidthValues'] = ['d-flex', '', '', ''];
    $langFld->htmlAfterField = '<a href="javascript:void(0);" onclick="bannerLangForm(' . $collectionId . ', ' . $recordId . ', ' . $lang_id . ', 1)" class="btn" title="' .  Labels::getLabel('BTN_AUTOFILL_LANGUAGE_DATA', $siteLangId) . '">
                                <svg class="svg" width="18" height="18">
                                    <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite.yokart.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#icon-translate">
                                    </use>
                                </svg>
                            </a>';
}

$displayLangTab = false;

$generalTab = [
    'attr' => [
        'href' => 'javascript:void(0)',
        'onclick' => 'banners(' . $collectionId . ')',
        'title' => Labels::getLabel('LBL_BANNERS', $siteLangId),
    ],
    'label' => Labels::getLabel('LBL_BANNERS', $siteLangId),
    'isActive' => false
];

$otherButtons = [
    [
        'attr' => [
            'href' => 'javascript:void(0);',
            'onclick' => 'bannerForm(' . $collectionId . ',' . $recordId . ')',
            'title' => Labels::getLabel('LBL_ADD_BANNER', $siteLangId)
        ],
        'label' => Labels::getLabel('LBL_ADD_BANNER', $siteLangId),
        'isActive' => false
    ],
    [
        'attr' => [
            'href' => 'javascript:void(0)',
            'onclick' => 'bannerLangForm(' . $collectionId . ',' . $recordId . ',' . $lang_id . ')',
            'title' => Labels::getLabel('LBL_BANNER_LANG_DATA', $siteLangId),
        ],
        'label' => Labels::getLabel('LBL_BANNER_LANG_DATA', $siteLangId),
        'isActive' => true
    ],
    [
        'attr' => [
            'href' => 'javascript:void(0)',
            'onclick' => 'bannerMediaForm(' . $collectionId . ',' . $recordId . ')',
            'title' => Labels::getLabel('LBL_BANNER_MEDIA', $siteLangId),
        ],
        'label' => Labels::getLabel('LBL_BANNER_MEDIA', $siteLangId),
        'isActive' => false
    ]
];
$includeTabs = ($collection_layout_type != Collections::TYPE_PENDING_REVIEWS1);

require_once(CONF_THEME_PATH . '_partial/listing/lang-form.php');
