<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
if (!empty($addresses)) { ?>
    <div class="modal-header">
        <h5 class="modal-title"><?php echo Labels::getLabel('LBL_Pick_Up', $siteLangId); ?></h5>
    </div>
    <div class="modal-body form-edit">
        <div class="loaderContainerJs">
            <div class="pick-section">
                <div class="pickup-option">
                    <ul class="pickup-option__list">
                        <?php foreach ($addresses as $key => $address) {
                            $addr = $address['addr_name'] . ', ' . $address['addr_address1'];
                            $addr .= !empty($address['addr_address2']) ? ', ' . $address['addr_address2'] : '';
                            $addr .= !empty($address['addr_city']) ? ', ' . $address['addr_city'] : '';
                            $addr .= !empty($address['state_name']) ? ', ' . $address['state_name'] : '';
                            $addr .= !empty($address['country_name']) ? ', ' . $address['country_name'] : '';
                            $addr .= !empty($address['addr_zip']) ? ', ' . $address['addr_zip'] : '';
                        ?>
                            <li class="addrListJs <?php echo (($key == 0 && $addrId == 0) || $addrId == $address['addr_id']) ? 'is-active' : ''; ?>">
                                <label class="radio" for="<?php echo 'p-' . $address['addr_id'] ?>">
                                    <input name="pickup_address" <?php echo (($key == 0 && $addrId == 0) || $addrId == $address['addr_id']) ? 'checked=checked' : ''; ?> onclick="displayCalendar();" type="radio" value="<?php echo $address['addr_id']; ?>" id="<?php echo 'p-' . $address['addr_id'] ?>">

                                    <address class="address lb-txt js-addr">
                                        <p><?php echo $addr ?></p>
                                        <?php if (strlen((string)$address['addr_phone']) > 0) {
                                            $addrPhone = ValidateElement::formatDialCode($address['addr_phone_dcode']) . $address['addr_phone'];
                                        ?>
                                            <ul class="phone-list">
                                                <li class="phone-list-item phone-txt">
                                                    <svg class="svg" width="20" height="20">
                                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#mobile-alt">
                                                        </use>
                                                    </svg>
                                                    <span class="default-ltr"><?php echo $addrPhone; ?></span>
                                                </li>
                                            </ul>
                                        <?php } ?>
                                    </address>
                                </label>
                            </li>
                        <?php } ?>
                    </ul>
                    <div class="pickup-time">
                        <div class="calendar">
                            <div class="js-datepicker calendar-pickup"></div>
                        </div>
                        <ul class="time-slot js-time-slots">
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php } else { ?>
    <div class="modal-header">
        <h5 class="modal-title step-title"><?php echo Labels::getLabel('LBL_No_Pick_Up_address_added', $siteLangId); ?></h5>
    </div>
<?php }
$displayDateformat = FatDate::convertDateFormatFromPhp(
    FatApp::getConfig('CONF_DATE_FORMAT', FatUtility::VAR_STRING, 'Y-m-d'),
    FatDate::FORMAT_JQUERY_UI
);
?>

<script>
    var needToSeeDaysOfWeek = new Array();
    var calendarSelectedDate = '';
    $(document).ready(function() {
        $('.js-datepicker').datepicker({
            isRTL: false,
            minDate: new Date(),
            dateFormat: 'yy-mm-dd',
            beforeShowDay: availableDates,
            onSelect: function() {
                calendarSelectedDate = $.datepicker.formatDate('<?php echo $displayDateformat; ?>', $(this).datepicker('getDate'));
                displayDateSlots(false);
            }
        });

        displayCalendar();
    });

    displayCalendar = function() {
        var checkedAddrId = $('input[name="pickup_address"]:checked').val();
        fcom.ajax(fcom.makeUrl('Addresses', 'slotDaysByAddr', [checkedAddrId], siteConstants.webroot_dashboard), '', function(rslt) {
            var rsp = JSON.parse(rslt);
            if (rsp.status == 1) {
                needToSeeDaysOfWeek.splice(0, needToSeeDaysOfWeek.length);
                $.each(rsp.slotDays, function(index, value) {
                    needToSeeDaysOfWeek.push(value);
                });
                $('.js-datepicker').datepicker('refresh');

                var pickUpAddrId = <?php echo $addrId; ?>;
                if (checkedAddrId == pickUpAddrId) {
                    $('.js-datepicker').datepicker("setDate", new Date("<?php echo $slotDate; ?>"));
                    displayDateSlots(true);
                } else {
                    if (rsp.activeDate == '') {
                        $(".js-time-slots").html('');
                        $('.js-datepicker').datepicker("setDate", null);
                    } else {
                        $('.js-datepicker').datepicker('option', 'minDate', rsp.activeDate);
                        $('.js-datepicker').datepicker("setDate", new Date(rsp.activeDate));
                        displayDateSlots(false);
                    }
                }
            }
        });
    }

    displayDateSlots = function(displaySlotSelected) {
        $('input[name="timeSlot"]').prop("checked", displaySlotSelected);
        var selectedDate = $('.js-datepicker').val();
        var addressId = $('input[name="pickup_address"]:checked').val();
        var pickUpBy = <?php echo $pickUpBy; ?>;
        if (addressId != 'undefined' && selectedDate != '') {
            var data = 'addressId=' + addressId + '&selectedDate=' + selectedDate + '&pickUpBy=' + pickUpBy;
            if (displaySlotSelected == true) {
                data = data + '&selectedSlot=<?php echo $slotId; ?>';
            }
            fcom.ajax(fcom.makeUrl('Addresses', 'getTimeSlotsByAddressAndDate', [], siteConstants.webroot_dashboard), data, function(rsp) {
                $(".js-time-slots").html(rsp);
            });
        }
    }

    availableDates = function(date) {
        var day = date.getDay();
        for (var i = 0; i < needToSeeDaysOfWeek.length; i++) {
            if (day == needToSeeDaysOfWeek[i]) {
                return [true];
            }
        }
        return [false];
    }

    selectTimeSlot = function(ele, pickUpBy) {
        var slot_id = $(ele).attr('id');
        var slot_date = $('.js-datepicker').val();
        var addr_id = $("input[name='pickup_address']:checked").val();
        $("input[name='slot_id[" + pickUpBy + "]']").val(slot_id);
        $("input[name='slot_date[" + pickUpBy + "]']").val(slot_date);
        $(".js-slot-addr-" + pickUpBy).attr('data-addr-id', addr_id);

        var slot_time = $(ele).next().children('.time').html();
        var addrHtml = $("input[name='pickup_address']:checked").next('.js-addr').html();
        var html = '<address class="address shop-address">' + addrHtml + '<ul class="phone-list"><li class="phone-list-item time-txt"><svg class="svg" width="20" height="20"><use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#calendar-day"></use></svg>' + calendarSelectedDate + ' ' + slot_time + '</li></ul></address>';
        $(".pickupAddressBtn-" + pickUpBy + "-js").text(langLbl.changePickup);
        $(".js-slot-addr_" + pickUpBy).html(html);
        $.ykmodal.close();
    }
</script>