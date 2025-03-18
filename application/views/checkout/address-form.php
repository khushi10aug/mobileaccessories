<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
HtmlHelper::formatFormFields($addressFrm, 6);
$addressFrm->setFormTagAttribute('class', 'form modalFormJs');
$addressFrm->setFormTagAttribute('data-onclear', 'editAddress(' . $address_id . ', ' . $addressType . ')');
$addressFrm->setFormTagAttribute('onsubmit', 'setUpAddress(this, ' . $addressType . '); return(false);');

$countryFld = $addressFrm->getField('addr_country_id');
$countryFld->setFieldTagAttribute('id', 'addr_country_id');
$countryFld->setFieldTagAttribute('onChange', 'getCountryStates(this.value, 0 ,\'#addr_state_id\')');

$stateFld = $addressFrm->getField('addr_state_id');
$stateFld->setFieldTagAttribute('id', 'addr_state_id');

$fld = $addressFrm->getField('btn_submit');
$addressFrm->removeField($fld);

$fld = $addressFrm->getField('btn_cancel');
$addressFrm->removeField($fld);
?>
<div class="modal-header">
    <h5 class="modal-title">
        <?php if (!isset($_SESSION['offer_checkout'])) { ?>
            <a class="btn-back" href="javascript:void(0);" onclick="showAddressList()">
                <svg class="svg" width="24" height="24">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#back">
                    </use>
                </svg>
            </a>
        <?php } ?>
        <?php echo $labelHeading; ?>
    </h5>
</div>
<div class="modal-body form-edit">
    <div class="form-edit-body loaderContainerJs">
        <?php echo $addressFrm->getFormHtml(); ?>
    </div>
    <?php require_once(CONF_THEME_PATH . '_partial/sidebar/form-edit-foot.php'); ?>
</div>
<script>
    $(document).ready(function() {
        getCountryStates($("#addr_country_id").val(), <?php echo $stateId; ?>, '#addr_state_id');
    });
</script>