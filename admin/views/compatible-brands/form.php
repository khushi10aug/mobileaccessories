<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form');

$fld = $frm->getField('cbrand_id');
$fld->setFieldTagAttribute('id', "cbrand_id");

$fld = $frm->getField('auto_update_other_langs_data');
if ($fld != null) {
    HtmlHelper::configureSwitchForCheckbox($fld);
    $fld->developerTags['noCaptionTag'] = true;
    $fld->developerTags['colWidthValues'] = [null, '12', null, null];
}

$fld = $frm->getField('cbrand_active');
if ($fld != null) {
    HtmlHelper::configureSwitchForCheckbox($fld);
    $fld->developerTags['noCaptionTag'] = true;
}

//$fld = $frm->getField('urlrewrite_custom');
//$fld->setFieldTagAttribute('id', "urlrewrite_custom");
//$fld->htmlAfterField = '<span class="form-text text-muted">' . HtmlHelper::seoFriendlyUrl(UrlHelper::generateFullUrl('Brands', 'View', array($recordId), CONF_WEBROOT_FRONT_URL)) . '</span>';
//$fld->setFieldTagAttribute('onKeyup', "getSlugUrl(this,this.value)");

/*$otherButtons = [
    [
        'attr' => [
            'href' => 'javascript:void(0)',
            'onclick' => 'mediaForm(' . $recordId . ')',
            'title' => Labels::getLabel('LBL_MEDIA', $siteLangId),
        ],
        'label' => Labels::getLabel('LBL_MEDIA', $siteLangId),
        'isActive' => false
    ]
];*/

$formTitle = Labels::getLabel('LBL_COMPATIBLE_BRAND_SETUP', $siteLangId);
require_once(CONF_THEME_PATH . '_partial/listing/form.php');
