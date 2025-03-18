<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>

<div class="card-body">
    <ul class="my-addresses">
        <?php if (!isset($addressData) || empty($addressData)) { ?>
            <li class="my-addresses-item my-addresses-add">
                <button class="btn btn-add-address" type="button" onclick="returnAddressForm()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="svg mb-2" width="38" height="38" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M2 13.5V7h1v6.5a.5.5 0 0 0 .5.5h9a.5.5 0 0 0 .5-.5V7h1v6.5a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 13.5zm11-11V6l-2-2V2.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5z"></path>
                        <path fill-rule="evenodd" d="M7.293 1.5a1 1 0 0 1 1.414 0l6.647 6.646a.5.5 0 0 1-.708.708L8 2.207 1.354 8.854a.5.5 0 1 1-.708-.708L7.293 1.5z"></path>
                    </svg>
                    <?php echo Labels::getLabel('LBL_ADD_NEW_ADDRESS', $siteLangId); ?>
                </button>
            </li>
        <?php } else { ?>
            <li class="my-addresses-item">
                <div class="my-addresses__body">
                    <address class="address delivery-address">
                        <h5><?php echo $addressData['ura_name']; ?></h5>
                        <p>
                            <?php echo $addressData['ura_address_line_1'] . '<br>'; ?>
                            <?php echo (!empty($addressData['ura_address_line_2'])) ? $addressData['ura_address_line_2'] . '<br>' : ''; ?>
                            <?php echo (!empty($addressData['ura_city'])) ? $addressData['ura_city'] . ',' : ''; ?>
                            <?php echo (!empty($addressData['state_name'])) ? $addressData['state_name'] . '<br>' : ''; ?>
                            <?php echo (!empty($addressData['country_name'])) ? $addressData['country_name'] . '<br>' : ''; ?>
                            <?php echo (!empty($addressData['ura_zip'])) ? Labels::getLabel('LBL_Zip:', $siteLangId) . $addressData['ura_zip'] . '<br>' : ''; ?>
                        </p>
                        <ul class="phone-list">
                            <li class="phone-list-item phone-txt">
                                <svg class="svg" width="20" height="20">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#mobile-alt">
                                    </use>
                                </svg>
                                <?php
                                if (!empty($addressData['ura_phone'])) {
                                   $addrPhone = '<span class="default-ltr">' . ValidateElement::formatDialCode($addressData['ura_phone_dcode']) . $addressData['ura_phone'] . '</span>';
                                    echo Labels::getLabel('LBL_Phone:', $siteLangId) . $addrPhone . '<br>';
                                }
                                ?>
                            </li>
                        </ul>
                    </address>
                </div>
                <div class="my-addresses__footer">
                    <div class="actions">
                        <a href="javascript:void(0)" onclick="returnAddressForm()">
                            <?php echo Labels::getLabel('LBL_Edit', $siteLangId); ?>
                        </a>
                        <a href="javascript:void(0)" onclick="deleteReturnAddress()">
                            <?php echo Labels::getLabel('LBL_Delete', $siteLangId); ?>
                        </a>
                    </div>
                </div>
            </li>
        <?php } ?>
    </ul>
</div>