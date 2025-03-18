<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
HtmlHelper::formatFormFields($frm);
$frm->setFormTagAttribute('class', 'form modalFormJs');
$frm->setFormTagAttribute('onsubmit', 'setupRate(this); return(false);');
$frm->setFormTagAttribute('data-onclear', 'addEditShipRates(' . $zoneId . ', ' . $rateId . ')');

$formTitle = Labels::getLabel('LBL_SHIPPING_RATES_SETUP', $siteLangId);

$languages = $languages ?? [];
unset($languages[CommonHelper::getDefaultFormLangId()]);
$label = isset($generalTab['label']) ? $generalTab['label'] : '';

$fld = $frm->getField('auto_update_other_langs_data');
if ($fld != null) {
    HtmlHelper::configureSwitchForCheckbox($fld);
    $fld->developerTags['noCaptionTag'] = true;
    $fld->developerTags['colWidthValues'] = [null, '12', null, null];
}

$costFld = $frm->getField('shiprate_cost');
$extraClass = 'hide';
if (!empty($rateData) && $rateData['shiprate_condition_type'] > 0) {
    $extraClass = '';
}
$fld = $frm->getField('add_condition');
$fld->value = '<a href="javascript:void(0)" class="btn btn-icon btn-outline-brand add-condition--js" onclick="modifyRateFields(1)" title="' . Labels::getLabel("LBL_ADD_CONDITION", $siteLangId) . '" data-bs-toggle="tooltip" data-placement="top">
                    <svg class="svg btn-icon-start" width="18" height="18">
                        <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#add">
                        </use>
                    </svg>
                    <span>' . Labels::getLabel("LBL_ADD_CONDITION", $siteLangId) . '</span>
                    </a>
                    <a href="javascript:void(0)" class="btn btn-icon btn-outline-brand remove-condition--js"  style="display : none;" onclick="modifyRateFields(0)" title="' . Labels::getLabel("LBL_REMOVE_CONDITION", $siteLangId) . '" data-bs-toggle="tooltip" data-placement="top">
                    <svg class="svg" width="18" height="18">
                        <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#delete">
                        </use>
                    </svg>
                    <span>' . Labels::getLabel("LBL_REMOVE_CONDITION", $siteLangId) . '</span>
                </a>';

$cndFld = $frm->getField('shiprate_condition_type');
$cndFld->developerTags['noCaptionTag'] = true;
$cndFld->setWrapperAttribute('class', 'condition-field--js ' . $extraClass);
$cndFld->addOptionListTagAttribute('class', 'list-radio');

$minFld = $frm->getField('shiprate_min_val');
$minFld->setWrapperAttribute('class', 'condition-field--js ' . $extraClass);
$minFld->developerTags['colWidthValues'] = [null, '6', null, null];

$maxFld = $frm->getField('shiprate_max_val');
$maxFld->setWrapperAttribute('class', 'condition-field--js ' . $extraClass);
$maxFld->developerTags['colWidthValues'] = [null, '6', null, null];

$generalTab = [
    'attr' => [
        'href' => 'javascript:void(0);',
        'onclick' => 'addEditShipRates(' . $zoneId . ', ' . $rateId . ')'
    ],
    'label' => Labels::getLabel('LBL_GENERAL', $siteLangId),
    'isActive' => true
];

$displayLangTab = false;

$otherButtons = [
    [
        'attr' => [
            'href' => 'javascript:void(0)',
            'onclick' => 'editRateLangForm(' . $zoneId . ', ' . $rateId . ', ' . array_key_first($languages) . ');',
        ],
        'label' => Labels::getLabel('LBL_LANGUAGE_DATA', $siteLangId),
        'isActive' => false
    ]
];

require_once(CONF_THEME_PATH . '_partial/listing/form-head.php'); ?>
    <div class="form-edit-body loaderContainerJs">
        <?php echo $frm->getFormHtml(); ?>
    </div>
    <?php require_once(CONF_THEME_PATH . '_partial/listing/form-edit-foot.php'); ?>
</div> <!-- Close </div> This must be placed. Opening tag is inside form-head.php file. -->


<?php if (!empty($rateData) && $rateData['shiprate_condition_type'] > 0) { ?>
    <script>
        $(document).ready(function() {
            $('.add-condition--js').hide();
            $('.remove-condition--js').show();
        });
    </script>
<?php
}
