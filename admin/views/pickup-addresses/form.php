<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
HtmlHelper::formatFormFields($frm);
$frm->setFormTagAttribute('data-onclear', "editRecord(" . $addressId . ", " . $langId . ");");

$frm->setFormTagAttribute('class', 'form modalFormJs layout--' . $formLayout);
$frm->setFormTagAttribute('onsubmit', 'setup($("#' . $frm->getFormTagAttribute('id') . '")[0]); return(false);');
$frm->setFormTagAttribute('dir', $formLayout);

$langFld = $frm->getField('lang_id');
$langFld->setFieldTagAttribute('onChange', "editRecord(" . $addressId . ", this.value);");

$addrLabelFld = $frm->getField('addr_title');
$addrLabelFld->setFieldTagAttribute('placeholder', Labels::getLabel('LBL_E.G:_MY_OFFICE_ADDRESS', $langId));
$addrLabelFld->setFieldTagAttribute('maxlength', Address::ADDRESS_TITLE_LENGTH);


$countryFld = $frm->getField('addr_country_id');
$countryFld->setFieldTagAttribute('id', 'addr_country_id');
$countryFld->setFieldTagAttribute('onChange', 'getCountryStates(this.value,' . $stateId . ',\'#addrStateJs\',' . $langId . ')');

$stateFld = $frm->getField('addr_state_id');
$stateFld->setFieldTagAttribute('id', 'addrStateJs');

$slotTypeFld = $frm->getField('tslot_availability');
$slotTypeFld->setOptionListTagAttribute('class', 'list-radio');
$slotTypeFld->developerTags['rdLabelAttributes'] = array('class' => 'radio');
$slotTypeFld->developerTags['rdHtmlAfterRadio'] = '<i class="input-helper"></i>';
$slotTypeFld->setFieldTagAttribute('onChange', 'displaySlotTimings(this);');
$slotTypeFld->setFieldTagAttribute('class', 'availabilityType-js');
?>
<div class="modal-header">
    <h5 class="modal-title">
        <?php echo Labels::getLabel('LBL_PICKUP_ADDRESSES_SETUP'); ?>
    </h5>
</div>

<div class="modal-body form-edit">
    <div class="form-edit-body loaderContainerJs sectionbody space">
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
                    <div class="col-md-12 js-slot-individual">
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
                                    $dayFld->developerTags['cbLabelAttributes'] = array('class' => 'checkbox');
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
                                            $fromFld->value = $fromTime;

                                            $toFld = $frm->getField('tslot_to_time[' . $i . '][]');
                                            $toFld->setFieldTagAttribute('class', 'js-slot-to-' . $i . ' toTime-js');
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
                                        $fromFld->setFieldTagAttribute('onChange', 'displayAddRowValues(' . $i . ', this)');

                                        $toFld = $frm->getField('tslot_to_time[' . $i . '][]');
                                        $toFld->setFieldTagAttribute('disabled', 'true');
                                        $toFld->setFieldTagAttribute('data-row', $row);
                                        $toFld->setFieldTagAttribute('class', 'js-slot-to-' . $i . ' toTime-js');
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
    <?php if ($addressId > 0) { ?>
        $(document).ready(function() {
            $('.availabilityType-js:checked').trigger('change');
            getCountryStates($("#addr_country_id").val(), <?php echo ($stateId) ? $stateId : 0; ?>, '#addrStateJs', <?php echo $langId; ?>);
        });
    <?php } ?>
</script>