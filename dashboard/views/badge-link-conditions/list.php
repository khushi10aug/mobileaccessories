<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

$typeArr = Badge::getTypeArr($siteLangId);
$headingLabel = $badgeName . ' ' . Labels::getLabel('LBL_BIND_CONDITIONS', $siteLangId);
$headingLabel .= ' <span class="badge badge-inline label-info rounded-pill">' . $typeArr[$badgeType] . '</span>';

$listingLabel = $badgeName . ' ' . Labels::getLabel('LBL_CONDITIONS_LIST', $siteLangId);

$headingBackButton = [
    'href' => UrlHelper::generateUrl('Badges', 'list', [$badgeType]),
    'onclick' => ''
];

if (Badge::COND_MANUAL == $conditionType && $row[Badge::DB_TBL_PREFIX . 'required_approval'] == Badge::APPROVAL_OPEN) {
    $otherButtons[] = [
        'attr' => [
            'href' => UrlHelper::generateUrl('BadgeLinkConditions', 'conditionForm', [$badgeId, $badgeType]),
            'title' => Labels::getLabel('LBL_BIND_CONDITION', $siteLangId)
        ],
        'icon' => '<svg class="svg btn-icon-start" width="18" height="18">
                            <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#add">
                            </use>
                        </svg>',
        'label' => Labels::getLabel('LBL_NEW', $siteLangId)
    ];
}

if (!empty($frmSearch)) {
    $frmSearch->setFormTagAttribute('class', 'form form-search formSearch--js');
    $frmSearch->developerTags['colClassPrefix'] = 'col-md-';
    $frmSearch->developerTags['fld_default_col'] = 4;
    $fld = $frmSearch->getField('blinkcond_record_type');
    if (null != $fld) {
        $fld->developerTags['noCaptionTag'] = true;
    }

    $fld = $frmSearch->getField('blinkcond_condition_type');
    if (null != $fld) {
        $fld->developerTags['noCaptionTag'] = true;
    }

    $fld = $frmSearch->getField('btn_submit');
    if (null != $fld) {
        $fld->setFieldTagAttribute('class', 'btn btn-brand btn-block');
        $fld->developerTags['col'] = 2;
        $fld->developerTags['noCaptionTag'] = true;
    }

    $fld = $frmSearch->getField('btn_clear');
    if (null != $fld) {
        $fld->setFieldTagAttribute('class', 'btn btn-outline-gray btn-block');
        $fld->setFieldTagAttribute('onclick', 'clearSearch()');
        $fld->developerTags['col'] = 2;
        $fld->developerTags['noCaptionTag'] = true;
    }
}
require_once(CONF_THEME_PATH . '_partial/index-page-common.php');
