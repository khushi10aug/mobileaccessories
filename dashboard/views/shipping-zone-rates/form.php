<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

HtmlHelper::formatFormFields($frm);
$frm->setFormTagAttribute('class', 'form modalFormJs');
$frm->setFormTagAttribute('data-onclear', "addEditShipRates(" . $zoneId . ", " . $rateId . ")");
$frm->setFormTagAttribute('onsubmit', 'setupRate(this); return(false);');

$nameFld = $frm->getField('shiprate_name');
$nameFld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("LBL_Customers_will_see_this_at_checkout.", $siteLangId) . "</span>";

$costFld = $frm->getField('shiprate_cost');
$extraClass = 'd-none';
if (!empty($rateData) && $rateData['shiprate_condition_type'] > 0) {
    $extraClass = '';
}

$languages = $languages ?? [];
unset($languages[CommonHelper::getDefaultFormLangId()]);

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
$cndFld->developerTags['rdLabelAttributes'] = ['class' => 'radio'];
$cndFld->setWrapperAttribute('class', 'condition-field--js ' . $extraClass);

$minFld = $frm->getField('shiprate_min_val');
$minFld->setWrapperAttribute('class', 'condition-field--js ' . $extraClass);

$maxFld = $frm->getField('shiprate_max_val');
$maxFld->setWrapperAttribute('class', 'condition-field--js ' . $extraClass); ?>
<div class="modal-header">
    <h5 class="modal-title"><?php echo Labels::getLabel('LBL_SHIPPING_RATES_SETUP', $siteLangId); ?></h5>
</div>
<div class="modal-body form-edit">
    <?php if(count($languages)){ ?>
        <div class="form-edit-head">
            <nav class="nav nav-tabs navTabsJs">
                <a class="nav-link active" href="javascript:void(0)" onclick="addEditShipRates(<?php echo $zoneId ?>, <?php echo $rateId ?>);"><?php echo Labels::getLabel('LBL_General', $siteLangId); ?></a>
                <a class="nav-link <?php echo (0 == $rateId) ? 'fat-inactive' : ''; ?>" href="javascript:void(0);" <?php echo (0 < $rateId) ? "onclick='editRateLangForm(" . $zoneId . "," . $rateId . ", " . array_key_first($languages) . ");'" : ""; ?>>
                    <?php echo Labels::getLabel('LBL_LANGUAGE_DATA', $siteLangId); ?>
                </a>
            </nav>
        </div>
    <?php } ?>
    <div class="form-edit-body loaderContainerJs" id="selectedTabContentJs">
        <?php echo $frm->getFormHtml(); ?>
    </div>
    <?php require_once(CONF_THEME_PATH . '_partial/listing/form-edit-foot.php'); ?>
</div>
<?php
if (!empty($rateData) && $rateData['shiprate_condition_type'] > 0) { ?>
    <script>
        $(document).ready(function() {
            $('.add-condition--js').hide();
            $('.remove-condition--js').show();
        });
    </script>
<?php }
