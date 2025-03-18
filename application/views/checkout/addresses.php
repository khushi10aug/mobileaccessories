<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="modal-header">
    <h5 class="modal-title">
        <?php if ($fulfillmentType == Shipping::FULFILMENT_PICKUP || $addressType == Address::ADDRESS_TYPE_BILLING || !$cartHasPhysicalProduct) {
            echo Labels::getLabel('LBL_Billing_Address', $siteLangId);
        } else {
            echo Labels::getLabel('LBL_Delivery_Address', $siteLangId);
        }
        ?>
    </h5>
</div>

<div class="modal-body form-edit">
    <div class="form-edit-body loaderContainerJs">
        <form class="form">
            <div class="row">
                <div class="col-md-12">
                    <ul class="list-addresses">
                        <?php if ($addresses) { ?>
                            <?php foreach ($addresses as $address) {
                                $selected_shipping_address_id = (!$selected_shipping_address_id && $address['addr_is_default']) ? $address['addr_id'] : $selected_shipping_address_id; ?>
                                <?php $checked = false;
                                if ($addressType == 0 && $selected_shipping_address_id == $address['addr_id']) {
                                    $checked = true;
                                }
                                if ($addressType == Address::ADDRESS_TYPE_BILLING && $selected_billing_address_id == $address['addr_id']) {
                                    $checked = true;
                                }
                                ?>
                                <li class="list-addresses-item addrListJs s-<?php echo $address['addr_id']; ?> <?php echo ($checked == true) ? 'is-active' : '' ?>">
                                    <label class="addresses" for="s-<?php echo $address['addr_id']; ?>">
                                        <span class="radio addresses-checkbox">
                                            <input <?php echo ($checked == true) ? 'checked="checked"' : ''; ?> name="shipping_address_id" value="<?php echo $address['addr_id']; ?>" type="radio" id="s-<?php echo $address['addr_id']; ?>">
                                        </span>
                                        <div class="addresses-detail">
                                            <h5 class="h5">
                                                <?php echo $address['addr_name']; ?><span class="tag"><?php echo ($address['addr_title'] != '') ? $address['addr_title'] : $address['addr_name']; ?></span>
                                            </h5>
                                            <p>
                                                <?php echo $address['addr_address1']; ?>
                                                <?php if (strlen((string)$address['addr_address2']) > 0) {
                                                    echo ", " . $address['addr_address2']; ?>
                                                <?php } ?>
                                            </p>
                                            <p>
                                                <?php echo $address['addr_city'] . ", " . $address['state_name'] . ", " . $address['country_name'] . ", " . $address['addr_zip']; ?>
                                            </p>
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
                                        </div>
                                        <?php if (!CommonHelper::isAppUser()) { ?>
                                            <div class="addresses-actions">
                                                <button class="btn btn-icon btn-addresses" type="button" onClick="editAddress('<?php echo $address['addr_id']; ?>', '<?php echo $addressType; ?>')">
                                                    <?php echo Labels::getLabel('LBL_Edit', $siteLangId); ?>
                                                </button>
                                                <?php if ($selected_billing_address_id != $address['addr_id']) { ?>
                                                    <button class="btn btn-icon btn-addresses" type="button" onclick="removeAddress('<?php echo $address['addr_id']; ?>', '<?php echo $addressType; ?>')">
                                                        <?php echo Labels::getLabel('LBL_Remove', $siteLangId); ?>
                                                    </button>
                                                <?php } ?>
                                            </div>
                                        <?php } ?>
                                    </label>
                                </li>
                            <?php } ?>
                        <?php } ?>

                        <li class=" addrListJs">
                            <button class="btn btn-add-address" type="button" onclick="showAddressFormDiv(<?php echo $addressType; ?>);">
                                <svg xmlns="http://www.w3.org/2000/svg" class="svg" width="20" height="20" fill="currentColor" class="bi bi-house" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M2 13.5V7h1v6.5a.5.5 0 0 0 .5.5h9a.5.5 0 0 0 .5-.5V7h1v6.5a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 13.5zm11-11V6l-2-2V2.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5z" />
                                    <path fill-rule="evenodd" d="M7.293 1.5a1 1 0 0 1 1.414 0l6.647 6.646a.5.5 0 0 1-.708.708L8 2.207 1.354 8.854a.5.5 0 1 1-.708-.708L7.293 1.5z" />
                                </svg>
                                <?php echo Labels::getLabel('LBL_ADD_NEW_ADDRESS', $siteLangId); ?>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </form>
    </div>
    <div class="form-edit-foot">
        <div class="row">
            <?php
            $resetJsFunc = 'showAddressList();';
            $contiJsFunc = 'setUpAddressSelection();';
            if ($addressType == Address::ADDRESS_TYPE_BILLING) {
                $resetJsFunc = 'loadAddressDiv(1);';
                $contiJsFunc = 'setUpBillingAddressSelection(this);';
            }
            ?>
            <div class="col">
                <button type="button" class="btn btn-outline-gray btn-wide" onclick="<?php echo $resetJsFunc; ?>">
                    <?php echo Labels::getLabel('LBL_RESET', $siteLangId); ?>
                </button>
            </div>
            <div class="col-auto">
                <button type="button" class="btn btn-brand btn-wide" id="btn-continue-js" onclick="<?php echo $contiJsFunc; ?>" title="<?php echo Labels::getLabel('LBL_SET_SELECTED_ADDRESS_AS_SHIPPING_ADDRESS', $siteLangId); ?>" data-bs-toggle="tooltip">
                    <?php echo Labels::getLabel('LBL_SAVE', $siteLangId); ?>
                </button>
            </div>
        </div>
    </div>

</div>