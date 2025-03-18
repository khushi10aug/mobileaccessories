<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
?>
<div class="card-body">
    <ul class="my-addresses">
        <?php if ($canEdit) { ?>
            <li class="my-addresses-item my-addresses-add">
                <button class="btn btn-add-address" type="button" onclick="pickupAddressForm(0)">
                    <svg xmlns="http://www.w3.org/2000/svg" class="svg mb-2" width="38" height="38" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M2 13.5V7h1v6.5a.5.5 0 0 0 .5.5h9a.5.5 0 0 0 .5-.5V7h1v6.5a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 13.5zm11-11V6l-2-2V2.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5z"></path>
                        <path fill-rule="evenodd" d="M7.293 1.5a1 1 0 0 1 1.414 0l6.647 6.646a.5.5 0 0 1-.708.708L8 2.207 1.354 8.854a.5.5 0 1 1-.708-.708L7.293 1.5z"></path>
                    </svg>
                    <?php echo Labels::getLabel('LBL_ADD_NEW_ADDRESS', $siteLangId); ?>
                </button>
            </li>
        <?php } ?>

        <?php if (isset($addresses) && !empty($addresses)) { ?>
            <?php
            if (count($addresses) == 1 && $addresses[0]['addr_is_default'] != 1) {
                $addresses[0]['addr_is_default'] = 1;
            }

            foreach ($addresses as $address) {
                $address['addr_title'] = ($address['addr_title'] == '') ? '&nbsp;' : $address['addr_title']; ?>

                <li class="my-addresses-item <?php echo ($address['addr_is_default'] == 1) ? 'is-active' : ''; ?>">
                    <div class="my-addresses__body">
                        <address class="address delivery-address">
                            <h5><?php echo $address['addr_name']; ?></h5>
                            <span class="tag"><?php echo $address['addr_title']; ?></span>
                            <p>
                                <?php echo $address['addr_address1'] . '<br>'; ?>
                                <?php echo (strlen((string)$address['addr_address2']) > 0) ? $address['addr_address2'] . '<br>' : ''; ?>
                                <?php echo (strlen((string)$address['addr_city']) > 0) ? $address['addr_city'] . ',' : ''; ?>
                                <?php echo (strlen((string)$address['state_name']) > 0) ? $address['state_name'] . '<br>' : ''; ?>
                                <?php echo (strlen((string)$address['country_name']) > 0) ? $address['country_name'] . '<br>' : ''; ?>
                                <?php echo (strlen((string)$address['addr_zip']) > 0) ? Labels::getLabel('LBL_Zip:', $siteLangId) . $address['addr_zip'] . '<br>' : ''; ?>
                            </p>
                            <ul class="phone-list">
                                <li class="phone-list-item phone-txt">
                                    <svg class="svg" width="20" height="20">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#mobile-alt">
                                        </use>
                                    </svg>
                                    <?php
                                    if (strlen((string)$address['addr_phone']) > 0) {
                                        $addrPhone = '<span class="default-ltr">' . ValidateElement::formatDialCode($address['addr_phone_dcode']) . $address['addr_phone'] . '</span>';
                                        echo Labels::getLabel('LBL_Phone:', $siteLangId) . $addrPhone . '<br>';
                                    }
                                    ?>
                                </li>
                            </ul>
                        </address>
                    </div>
                    <div class="my-addresses__footer">
                        <div class="actions">
                            <a href="javascript:void(0)" onclick="pickupAddressForm(<?php echo $address['addr_id']; ?>, <?php echo $address['addr_lang_id']; ?>)">
                                <?php echo Labels::getLabel('LBL_Edit', $siteLangId); ?>
                            </a>
                            <a href="javascript:void(0)" onclick="removeAddress(<?php echo $address['addr_id']; ?>, <?php echo Address::TYPE_SHOP_PICKUP; ?>)">
                                <?php echo Labels::getLabel('LBL_Delete', $siteLangId); ?>
                            </a>
                        </div>
                    </div>
                </li>
        <?php }
        } ?>
    </ul>
</div>