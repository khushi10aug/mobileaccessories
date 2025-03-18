<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
if (!empty($orderPickUpData)) {
?>
    <div class="modal-header">
        <h5 class="modal-title"><?php echo Labels::getLabel('LBL_Pick_Up', $siteLangId); ?></h5>
    </div>
    <div class="modal-body">
        <ul class="review-block">
            <?php foreach ($orderPickUpData as $address) { ?>
                <li class="review-block-item">
                    <div class="review-block-head">
                        <h5 class="h5">
                            <?php echo ($address['opshipping_by_seller_user_id'] > 0) ? $address['op_shop_name'] : FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId, null, ''); ?>
                        </h5>
                        <div class="review-block-action">
                            <?php
                            $fromTime = date('H:i', strtotime($address["opshipping_time_slot_from"]));
                            $toTime = date('H:i', strtotime($address["opshipping_time_slot_to"]));
                            ?>
                            <ul class="phone-list">
                                <li class="phone-list-item time-txt">
                                    <svg class="svg" width="20" height="20">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#calendar-day">
                                        </use>
                                    </svg>
                                    <?php echo FatDate::format($address["opshipping_date"]) . ' ' . $fromTime . ' - ' . $toTime; ?>
                                </li>
                            </ul>

                        </div>
                    </div>
                    <div class="review-block-body">
                        <address class="address delivery-address">
                            <?php echo $address['oua_name']; ?>
                            <p><?php echo $address['oua_address1']; ?>
                                <?php if (strlen((string)$address['oua_address2']) > 0) {
                                    echo ", " . $address['oua_address2']; ?>
                                <?php } ?>
                            </p>
                            <p><?php echo $address['oua_city'] . ", " . $address['oua_state']; ?></p>
                            <p><?php echo $address['oua_country'] . ", " . $address['oua_zip']; ?></p>
                            <?php if (strlen((string)$address['oua_phone']) > 0) { ?>
                                <ul class="phone-list">
                                    <li class="phone-list-item phone-txt">
                                        <svg class="svg" width="20" height="20">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#mobile-alt">
                                            </use>
                                        </svg>
                                        <span class="default-ltr"><?php echo ValidateElement::formatDialCode($address['oua_phone_dcode']) . $address['oua_phone']; ?></span>
                                    </li>
                                </ul>
                            <?php } ?>
                        </address>
                    </div>

                </li>
            <?php } ?>
        </ul>
    </div>
    <div class="modal-footer">
        <div class="d-flex">
            <button class="btn btn-outline-brand btn-sm mleft-auto" type="button" onClick="ShippingSummaryData();"><?php echo Labels::getLabel('LBL_Edit', $siteLangId); ?></button>
        </div>
    </div>

<?php } else { ?>
    <div class="modal-header">
        <h5 class="modal-title"><?php echo Labels::getLabel('LBL_No_Pick_Up_address_added', $siteLangId); ?></h5>
    </div>
<?php } ?>

<script>
    ShippingSummaryData = function() {
        $.facebox.close();
        loadShippingSummaryDiv();
    }
</script>