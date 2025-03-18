<?php $formId = $formId ?? ''; ?>
<div class="dropdown dropdown-static dropdown-addresses addressMenuJs">
    <a class="btn btn-outline-gray btn-dropdown dropdown-toggle-custom btn-icon selectedAddressJs" href="javascript:void(0)" id="dropdownMenuButton1" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
        <svg class="svg" width="16" height="16">
            <use xlink:href="<?php echo CONF_WEBROOT_FRONT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#location">
            </use>
        </svg>
        <span class="btn-dropdown-content text-break btnDropdownContentJs">
            <strong><?php echo $defaultAddress['addr_name']; ?></strong> -
            <?php echo $defaultAddress['addr_address1']; ?>,
            <?php echo (strlen((string)$defaultAddress['addr_address2']) > 0) ? $defaultAddress['addr_address2'] . ',' : ''; ?>
            <?php echo (strlen((string)$defaultAddress['addr_city']) > 0) ? $defaultAddress['addr_city'] . ',' : ''; ?>
            <?php echo (strlen((string)$defaultAddress['state_name']) > 0) ? $defaultAddress['state_name'] . ',' : ''; ?>
            <?php echo (strlen((string)$defaultAddress['country_name']) > 0) ? $defaultAddress['country_name'] . ',' : ''; ?>
            <?php echo (strlen((string)$defaultAddress['addr_zip']) > 0) ? Labels::getLabel('LBL_Zip:', $siteLangId) . $defaultAddress['addr_zip'] . ',' : ''; ?>
            <?php $dcode = (strlen((string)$defaultAddress['addr_phone_dcode']) > 0) ? ValidateElement::formatDialCode($defaultAddress['addr_phone_dcode']) : ''; ?>
            <?php echo (strlen((string)$defaultAddress['addr_phone']) > 0) ? Labels::getLabel('LBL_Phone:', $siteLangId) . $dcode . $defaultAddress['addr_phone'] . ',' : ''; ?>
        </span><i class="dropdown-toggle-custom-arrow"></i>
    </a>
    <ul class="dropdown-menu addressListingJs dropdown-menu-addresses" data-form="<?php echo $formId; ?>" aria-labelledby="dropdownMenuButton1">
        <?php foreach ($addresses as $address) { ?>
        <li class="dropdown-menu-item addressItemJs <?php echo ($defaultAddress['addr_id'] == $address['addr_id']) ? 'is-active' : ''; ?> " data-id="<?php echo $address['addr_id']; ?>">
            <span class="dropdown-menu-link">
                <span class="dropdown-menu-option text-break addressItemContentJs">
                    <strong><?php echo $address['addr_name']; ?></strong> -
                    <?php echo $address['addr_address1']; ?>,
                    <?php echo (strlen((string)$address['addr_address2']) > 0) ? $address['addr_address2'] . ',' : ''; ?>
                    <?php echo (strlen((string)$address['addr_city']) > 0) ? $address['addr_city'] . ',' : ''; ?>
                    <?php echo (strlen((string)$address['state_name']) > 0) ? $address['state_name'] . ',' : ''; ?>
                    <?php echo (strlen((string)$address['country_name']) > 0) ? $address['country_name'] . ',' : ''; ?>
                    <?php echo (strlen((string)$address['addr_zip']) > 0) ? Labels::getLabel('LBL_Zip:', $siteLangId) . $address['addr_zip'] . ',' : ''; ?>
                    <?php $dcode = (strlen((string)$address['addr_phone_dcode']) > 0) ? ValidateElement::formatDialCode($address['addr_phone_dcode']) : ''; ?>
                    <?php echo (strlen((string)$address['addr_phone']) > 0) ? Labels::getLabel('LBL_Phone:', $siteLangId) . $dcode . $address['addr_phone'] . ',' : ''; ?>
                </span>
            </span>
            </span>
        </li>
        <?php  } ?>
    </ul>
</div>