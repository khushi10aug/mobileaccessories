<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->addFormTagAttribute('data-onclear', 'collectionForm(' . $collection_type . ', ' . $collection_layout_type . ', ' . $recordId . ');');

$collectionNameFld = $frm->getField('collection_name');
$collectionNameFld->developerTags['colWidthValues'] = [null, '6', null, null];

$fld = $frm->getField('blocation_promotion_cost');
if (null != $fld) {
    $fld->developerTags['colWidthValues'] = [null, '6', null, null];
} else {
    $collectionLayoutTypeFld = $frm->getField('collection_layout_type');
    $collectionLayoutTypeFld->developerTags['colWidthValues'] = [null, '6', null, null];
}

$fld = $frm->getField('collection_for_web');
if (null != $fld) {
    HtmlHelper::configureSwitchForCheckbox($fld);
    $fld->developerTags['colWidthValues'] = [null, '6', null, null];
}

$fld = $frm->getField('collection_for_app');
if (null != $fld) {
    HtmlHelper::configureSwitchForCheckbox($fld);
    $fld->developerTags['colWidthValues'] = [null, '6', null, null];
    if (in_array($collection_layout_type, Collections::COLLECTIONS_FOR_APP_ONLY)) {
        $fld->setFieldTagAttribute('disabled', 'disabled');
    }
}

$fld = $frm->getField('collection_full_width');
if (null != $fld) {
    HtmlHelper::configureSwitchForCheckbox($fld);
    $fld->developerTags['noCaptionTag'] = true;
    $fld->developerTags['colWidthValues'] = [null, '6', null, null];
}

$fld = $frm->getField('auto_update_other_langs_data');
if (null != $fld) {
    $fld->developerTags['colWidthValues'] = [null, '6', null, null];
}

$fld = $frm->getField('collection_primary_records');
if (null != $fld) {
    $fld->developerTags['colWidthValues'] = [null, '6', null, null];
}

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

if ((!in_array($collection_type, Collections::COLLECTION_WITHOUT_MEDIA) && !in_array($collection_layout_type, Collections::COLLECTIONS_FOR_WEB_ONLY)) || in_array($collection_layout_type, Collections::COLLECTION_WITH_MEDIA)) {
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

require_once(CONF_THEME_PATH . '_partial/listing/form.php');
