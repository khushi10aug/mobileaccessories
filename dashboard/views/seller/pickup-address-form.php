<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
HtmlHelper::formatFormFields($frm);

$frm->setFormTagAttribute('class', 'form modalFormJs');
if (CommonHelper::getLayoutDirection() != $formLayout) {
    $frm->addFormTagAttribute('class', "layout--" . $formLayout);
    $frm->setFormTagAttribute('dir', $formLayout);
}
$frm->setFormTagAttribute('data-onclear', "pickupAddressForm(" . $addrId . ", " . $langId . ");");
$frm->setFormTagAttribute('id', 'pickupAddressFrm');
$frm->setFormTagAttribute('onsubmit', 'setPickupAddress(this); return(false);');

$langFld = $frm->getField('lang_id');
$langFld->setFieldTagAttribute('onChange', "pickupAddressForm(" . $addrId . ", this.value);");

$addrLabelFld = $frm->getField('addr_title');
$addrLabelFld->setFieldTagAttribute('placeholder', Labels::getLabel('LBL_E.G:_MY_OFFICE_ADDRESS', $langId));
$addrLabelFld->setFieldTagAttribute('maxlength', Address::ADDRESS_TITLE_LENGTH);

$countryFld = $frm->getField('addr_country_id');
$countryFld->setFieldTagAttribute('id', 'addr_country_id');
$countryFld->setFieldTagAttribute('onChange', 'getCountryStates(this.value,' . $stateId . ',\'#addr_state_id\')');

$stateFld = $frm->getField('addr_state_id');
$stateFld->setFieldTagAttribute('id', 'addr_state_id');

$slotTypeFld = $frm->getField('tslot_availability');
$slotTypeFld->setOptionListTagAttribute('class', 'list-inline');
$slotTypeFld->developerTags['rdLabelAttributes'] = array('class' => 'radio');
$slotTypeFld->developerTags['rdHtmlAfterRadio'] = '';
$slotTypeFld->setFieldTagAttribute('onChange', 'displaySlotTimings(this);');
$slotTypeFld->setFieldTagAttribute('class', 'availabilityType-js');

?>
<div class="modal-header">
    <h5 class="modal-title">
        <?php echo Labels::getLabel('LBL_PICKUP_ADDRESSES_SETUP'); ?>
    </h5>
</div>
<div class="modal-body form-edit">
    <div class="form-edit-body loaderContainerJs">
        <div class="row">
            <div class="col-md-12">
                <?php echo $frm->getFormTag(); ?>
                <div class="row">
                    <?php
                    echo HtmlHelper::getFieldHtml($frm, 'addr_id');
                    echo HtmlHelper::getFieldHtml($frm, 'lang_id');
                    echo HtmlHelper::getFieldHtml($frm, 'addr_title');
                    echo HtmlHelper::getFieldHtml($frm, 'addr_name');
                    echo HtmlHelper::getFieldHtml($frm, 'addr_address1');
                    echo HtmlHelper::getFieldHtml($frm, 'addr_address2');
                    echo HtmlHelper::getFieldHtml($frm, 'addr_country_id');
                    echo HtmlHelper::getFieldHtml($frm, 'addr_state_id');
                    echo HtmlHelper::getFieldHtml($frm, 'addr_city');
                    echo HtmlHelper::getFieldHtml($frm, 'addr_zip');
                    echo HtmlHelper::getFieldHtml($frm, 'addr_phone');
                    echo HtmlHelper::getFieldHtml($frm, 'tslot_availability', 12);
                    ?>
                </div>
                <div class="row js-slot-individual">
                    <table class="table table-slots">
                        <thead>
                            <tr>
                                <th width="25%"><?php echo Labels::getLabel('LBL_DAYS', $langId); ?></th>
                                <th width="30%"><?php echo Labels::getLabel('LBL_FROM', $langId); ?></th>
                                <th width="30%"><?php echo Labels::getLabel('LBL_TO', $langId); ?></th>
                                <th width="15%" class="align-right"><?php echo Labels::getLabel('LBL_ACTION_BUTTONS', $langId); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $daysArr = TimeSlot::getDaysArr($langId);
                            $row = 0;
                            for ($i = 0; $i < count($daysArr); $i++) {
                                $dayFld = $frm->getField('tslot_day[' . $i . ']');
                                $dayFld->developerTags['cbLabelAttributes'] = array('class' => 'checkbox checkbox-flex');
                                $dayFld->developerTags['cbHtmlAfterCheckbox'] = '<i class="input-helper"></i>';
                                $dayFld->setFieldTagAttribute('onChange', 'displayFields(' . $i . ', this)');
                                $dayFld->setFieldTagAttribute('class', 'slotDays-js');

                                if (!empty($slotData) && isset($slotData['tslot_day'][$i])) {
                                    $dayFld->setFieldTagAttribute('checked', 'true');
                                    foreach ($slotData['tslot_from_time'][$i] as $key => $time) {
                                        $fromTime = date('H:i', strtotime($time));
                                        $toTime = date('H:i', strtotime($slotData['tslot_to_time'][$i][$key]));

                                        $fromFld = $frm->getField('tslot_from_time[' . $i . '][]');
                                        $fromFld->setFieldTagAttribute('class', 'js-slot-from-' . $i . ' fromTime-js');
                                        $fromFld->setFieldTagAttribute('data-row', $row);
                                        $fromFld->setFieldTagAttribute('onChange', 'displayAddRowValues(' . $i . ', this)');
                                        $fromFld->addFieldTagAttribute('style', 'min-width:70px;');
                                        $fromFld->value = $fromTime;

                                        $toFld = $frm->getField('tslot_to_time[' . $i . '][]');
                                        $toFld->setFieldTagAttribute('class', 'js-slot-to-' . $i. ' toTime-js');
                                        $toFld->addFieldTagAttribute('style', 'min-width:70px;');
                                        $toFld->setFieldTagAttribute('data-row', $row);
                                        $toFld->setFieldTagAttribute('onChange', 'displayAddRowValues(' . $i . ', this)');
                                        $toFld->value = $toTime;
                                        $class = ($key > 0) ? ' js-added-rows-' . $i : ''; ?>
                                        <tr data-count="<?php echo $row; ?>" class="rows timeSlotJs jsDay-<?php echo $i; ?> row-<?php echo $row . $class; ?>">
                                            <td class="align-middle jsWeekDay">
                                                <?php if ($key == 0) { ?>
                                                    <div class="align-middle weekDaysJs">
                                                        <?php echo $frm->getFieldHtml('tslot_day[' . $i . ']'); ?>
                                                        <span class="input-helper"></span>
                                                    </div>
                                                    <div class="allDaysJs d-none"><?php echo Labels::getLabel('LBL_ALL', $langId); ?></div>
                                                <?php }
                                                ?>
                                            </td>
                                            <td>
                                                <?php echo $frm->getFieldHtml('tslot_from_time[' . $i . '][]'); ?>
                                            </td>
                                            <td>
                                                <?php echo $frm->getFieldHtml('tslot_to_time[' . $i . '][]'); ?>
                                            </td>
                                            <td class="align-right">
                                                <ul class="actions">
                                                    <?php if ($key != 0) { ?>
                                                        <li class="btn-remove-row-js" data-day="<?php echo $i; ?>">
                                                            <a href="javascript:void(0)" class="">
                                                                <svg class="svg" width="18" height="18">
                                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#delete">
                                                                    </use>
                                                                </svg>
                                                            </a>
                                                        </li>
                                                    <?php } ?>
                                                    <li class="addRowBtn<?php echo $i; ?>-js d-none">
                                                        <a href="javascript:void(0)" onclick="addRow('<?php echo $i; ?>')" class="">
                                                            <svg class="svg" width="18" height="18">
                                                                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#add">
                                                                </use>
                                                            </svg>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </td>
                                        </tr>
                                    <?php $row++;
                                    }
                                } else {
                                    $fromFld = $frm->getField('tslot_from_time[' . $i . '][]');
                                    $fromFld->setFieldTagAttribute('disabled', 'true');
                                    $fromFld->setFieldTagAttribute('data-row', $row);
                                    $fromFld->setFieldTagAttribute('class', 'js-slot-from-' . $i . ' fromTime-js');
                                    $fromFld->addFieldTagAttribute('style', 'min-width:70px;');
                                    $fromFld->setFieldTagAttribute('onChange', 'displayAddRowValues(' . $i . ', this)');

                                    $toFld = $frm->getField('tslot_to_time[' . $i . '][]');
                                    $toFld->setFieldTagAttribute('disabled', 'true');
                                    $toFld->setFieldTagAttribute('data-row', $row);
                                    $toFld->setFieldTagAttribute('class', 'js-slot-to-' . $i. ' toTime-js');
                                    $toFld->addFieldTagAttribute('style', 'min-width:70px;');
                                    $toFld->setFieldTagAttribute('onChange', 'displayAddRowValues(' . $i . ', this)'); ?>
                                    <tr data-count="<?php echo $i; ?>" class="rows timeSlotJs jsDay-<?php echo $i; ?> row-<?php echo $row; ?>">
                                        <td class="align-middle jsWeekDay">
                                            <div class="weekDaysJs">
                                                <?php echo $frm->getFieldHtml('tslot_day[' . $i . ']'); ?>
                                                <span class="input-helper"></span>
                                            </div>
                                            <div class="allDaysJs d-none"><?php echo Labels::getLabel('LBL_ALL', $langId); ?></div>
                                        </td>
                                        <td>
                                            <?php echo $frm->getFieldHtml('tslot_from_time[' . $i . '][]'); ?>
                                        </td>
                                        <td>
                                            <?php echo $frm->getFieldHtml('tslot_to_time[' . $i . '][]'); ?>
                                        </td>
                                        <td class="align-right">
                                            <ul class="actions">
                                                <li class="addRowBtn<?php echo $i; ?>-js d-none">
                                                    <a href="javascript:void(0)" onclick="addRow('<?php echo $i; ?>')" class=""><svg class="svg" width="18" height="18">
                                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#add">
                                                            </use>
                                                        </svg>
                                                    </a>
                                                </li>
                                            </ul>
                                        </td>
                                    </tr>
                            <?php
                                    $row++;
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                </form>
                <?php echo $frm->getExternalJS(); ?>
            </div>
        </div>
    </div>
    <?php require_once(CONF_THEME_PATH . '_partial/listing/form-edit-foot.php'); ?>
</div>

<script language="javascript">
    var DAY_SUNDAY = <?php echo TimeSlot::DAY_SUNDAY; ?>;
    $(document).ready(function() {
        getCountryStates($("#addr_country_id").val(), <?php echo ($stateId) ? $stateId : 0; ?>, '#addr_state_id');

        $(document).on("click", ".btn-remove-row-js", function() {
            var day = $(this).data('day');
            $(this).closest('.rows').remove();
            $('.jsDay-' + day + ':last').find('.addRowBtn' + day + '-js').removeClass('d-none');
        })

        addRow = function(day) {
            var fromTimeHtml = $(".js-slot-from-" + day).parent().html();
            var toTimeHtml = $(".js-slot-to-" + day).parent().html();
            var count = $(".js-slot-individual .rows").length;
            var toTime = $(".js-slot-to-" + day + ":last").val();

            var rowHtml = '<tr class="rows jsDay-' + day + ' row-' + count + '" data-count=' + count + '><td></td>';
            rowHtml += '<td>' + fromTimeHtml + '</td>';
            rowHtml += '<td>' + toTimeHtml + '</td>';
            rowHtml += '<td class="align-right"><ul class="actions">';
            rowHtml += '<li class="d-none addRowBtn' + day + '-js"><a href="javascript:void(0)" onclick="addRow(' + day + ')" class=""><svg class="svg" width="18" height="18"><use xlink:href="' + siteConstants.webroot + 'images/retina/sprite-actions.svg#add"></use> </svg></a></li>';
            rowHtml += '<li class="btn-remove-row-js" data-day=' + day + '><a href="javascript:void(0)" > <svg class="svg" width="18" height="18"> <use xlink:href="' + siteConstants.webroot + 'images/retina/sprite-actions.svg#delete"> </use></svg></a></li>';
            rowHtml += '</td>';

            var addRowBtn = $('.addRowBtn' + day + '-js');
            if (0 < addRowBtn.length) {
                addRowBtn.addClass('d-none');
            }

            if (0 < $('.addRowBtn' + day + '-js').length) {
                $('.addRowBtn' + day + '-js').addClass('d-none');
            }

            $(".jsDay-" + day).last().after(rowHtml);
            $('.row-' + count + " select").val('').attr('data-row', count);
            $('.row-' + count + " select").find("option").each(function() {
                var toVal = $(this).val();
                if (toVal != '' && toVal <= toTime) {
                    $(this).addClass('d-none');
                    $(this).prop('disabled', true);
                }
            });
        }

        displayFields = function(day, ele) {
            if ($(ele).prop("checked") == true) {
                $(".js-slot-from-" + day).removeAttr('disabled');
                $(".js-slot-to-" + day).removeAttr('disabled');
                displayAddRowValues(day, ele);
            } else {
                $(".js-slot-from-" + day).attr('disabled', 'true');
                $(".js-slot-to-" + day).attr('disabled', 'true');
                $(".js-slot-add-" + day).addClass('d-none');
                $(".jsDay-" + day).find("[name='btn_remove_row']").trigger('click');
            }
        }

        displayAddRowValues = function(day, ele) {
            var index = $(ele).data('row');
            var rowElement = ".js-slot-individual .row-" + index;
            var frmElement = rowElement + " .js-slot-from-" + day;
            var toElement = rowElement + " .js-slot-to-" + day;
            var fromTime = $(frmElement + " option:selected").val();
            var toTime = $(toElement + " option:selected").val();
            var toElementIndex = $(rowElement).index();
            var nextRowElement = ".js-slot-individual .rows:eq(" + (toElementIndex + 1) + ")";
            var nextFrmElement = nextRowElement + " .js-slot-from-" + day;
            if (0 < $(nextFrmElement).length) {
                $(nextFrmElement + " option").removeClass('d-none');
                $(nextFrmElement + " option").prop('disabled', false);
                var nxtFrmSelectedVal = $(nextFrmElement + ' option:selected').val();
                if (nxtFrmSelectedVal <= toTime) {
                    $(".js-slot-from-" + day).each(function() {
                        if (index < $(this).data('row') && $(this).val() <= toTime) {
                            var nxtRow = $(this).data('row');
                            $(this).val("");
                            $(".js-slot-individual .row-" + nxtRow + " .js-slot-to-" + day).val("");
                            $("option", this).each(function() {
                                var optVal = $(this).val();
                                if (optVal != '' && optVal <= toTime) {
                                    $(this).addClass('d-none');
                                    $(this).prop('disabled', true);
                                }
                            });
                        }
                    });
                }
                $(nextFrmElement + " option").each(function() {
                    var nxtFrmVal = $(this).val();
                    if (nxtFrmVal != '' && nxtFrmVal <= toTime) {
                        $(this).addClass('d-none');
                        $(this).prop('disabled', true);
                    }
                });
            }

            if (fromTime == '' && toTime != '') {
                $(toElement).val("");
                fcom.displayErrorMessage(langLbl.invalidFromTime);
                return false;
            }

            if (toTime != '' && toTime <= fromTime) {
                $(toElement).val('').addClass('error');
                var toTime = $(toElement).children("option:selected").val();
            } else {
                $(toElement).removeClass('error');
            }

            $(toElement + " option").removeClass('d-none');
            $(toElement + " option").prop('disabled', false);
            
            $(toElement + " option").each(function() {
                var toVal = $(this).val();
                if (toVal != '' && toVal <= fromTime) {
                    $(this).addClass('d-none');
                    $(this).prop('disabled', true);
                }
            });
            var toTimeLastOpt = $(toElement + " option:last").val();
            var lastCount = $('.jsDay-' + day).last().data('count');
            if (index < lastCount) {
                $(rowElement).find(".addRowBtn" + day + '-js').addClass('d-none');
            } else if (fromTime != '' && toTime != '' && toTime < toTimeLastOpt) {
                $(rowElement + " .addRowBtn" + day + '-js').removeClass('d-none');
            } else {
                $(rowElement + " .addRowBtn" + day + '-js').addClass('d-none');
            }
        }

        displaySlotTimings = function(ele) {
            var selectedVal = $(ele).val();
            var sundaySelector = '.js-slot-individual .jsDay-' + DAY_SUNDAY;
            if (selectedVal == 2) {
                $('.js-slot-individual .timeSlotJs').addClass('d-none');
                $(sundaySelector + ' .jsWeekDay .weekDaysJs').addClass('d-none');
                $(sundaySelector + ' .jsWeekDay .allDaysJs').removeClass('d-none');
                $(sundaySelector).removeClass('d-none');
                $(sundaySelector + ' .jsWeekDay input').prop("checked", true).trigger('change');
            } else {
                $('.js-slot-individual .timeSlotJs').removeClass('d-none');
                $(sundaySelector + ' .jsWeekDay .weekDaysJs').removeClass('d-none');
                $(sundaySelector + ' .jsWeekDay .allDaysJs').addClass('d-none');
            }
        }

        $('.availabilityType-js:checked').trigger('change');
    });
</script>