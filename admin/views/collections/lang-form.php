<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

$generalTab['attr']['onclick'] = 'collectionForm(' . $collection_type . ', ' . $collection_layout_type . ', ' . $recordId . ');';

if (!in_array($collection_type, Collections::COLLECTION_WITHOUT_RECORDS)) {
    $otherButtons[] = [
        'attr' => [
            'href' => 'javascript:void(0)',
            'onclick' => 'recordForm(' . $recordId . ',' . $collection_type . ')',
            'title' => Labels::getLabel('LBL_LINK_RECORDS', $siteLangId),
        ],
        'label' => Labels::getLabel('LBL_LINK_RECORDS', $siteLangId),
        'isActive' => false
    ];
}

if ($collection_type == Collections::COLLECTION_TYPE_BANNER) {
    $otherButtons[] = [
        'attr' => [
            'href' => 'javascript:void(0)',
            'onclick' => 'banners(' . $recordId . ')',
            'title' => Labels::getLabel('LBL_BANNERS', $siteLangId),
        ],
        'label' => Labels::getLabel('LBL_BANNERS', $siteLangId),
        'isActive' => false
    ];
}

if (!in_array($collection_type, Collections::COLLECTION_WITHOUT_MEDIA) && !in_array($collection_layout_type, Collections::COLLECTIONS_FOR_WEB_ONLY) || in_array($collection_layout_type, Collections::COLLECTION_WITH_MEDIA)) {
    $otherButtons[] = [
        'attr' => [
            'href' => 'javascript:void(0)',
            'onclick' => 'collectionMediaForm(' . $recordId . ',' . $collection_type . ')',
            'title' => Labels::getLabel('LBL_MEDIA', $siteLangId),
        ],
        'label' => Labels::getLabel('LBL_MEDIA', $siteLangId),
        'isActive' => false
    ];
}

$includeTabs = ($collection_layout_type != Collections::TYPE_PENDING_REVIEWS1);

require_once(CONF_THEME_PATH . '_partial/listing/lang-form.php'); 