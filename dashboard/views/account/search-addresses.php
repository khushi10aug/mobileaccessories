<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
if (!empty($addresses)) {
    if (count($addresses) == 1 && $addresses[0]['addr_is_default'] != 1) {
        $addresses[0]['addr_is_default'] = 1;
    } ?>
    <ul class="my-addresses">
        <li class="my-addresses-item my-addresses-add">
            <button class="btn btn-add-address" type="button" onclick="addAddressForm(0)">
                <svg xmlns="http://www.w3.org/2000/svg" class="svg mb-2" width="38" height="38" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M2 13.5V7h1v6.5a.5.5 0 0 0 .5.5h9a.5.5 0 0 0 .5-.5V7h1v6.5a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 13.5zm11-11V6l-2-2V2.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5z"></path>
                    <path fill-rule="evenodd" d="M7.293 1.5a1 1 0 0 1 1.414 0l6.647 6.646a.5.5 0 0 1-.708.708L8 2.207 1.354 8.854a.5.5 0 1 1-.708-.708L7.293 1.5z"></path>
                </svg>
                <?php echo Labels::getLabel('LBL_ADD_NEW_ADDRESS', $siteLangId); ?>
            </button>
        </li>
        <?php foreach ($addresses as $address) {
            $address['addr_title'] = ($address['addr_title'] == '') ? '&nbsp;' : $address['addr_title']; ?>
            <li class="<?php echo ($address['addr_is_default'] == 1) ? 'is-active' : ''; ?>">
                <label class="my-addresses__body">
                    <span class="radio">
                        <?php
                        $action = "setDefaultAddress(" . $address['addr_id'] . ", event)";
                        if (1 == $address['addr_is_default']) {
                            $action = 'return false';
                        }
                        ?>
                        <input type="radio" <?php echo ($address['addr_is_default'] == 1) ? 'checked=""' : ''; ?> name="1" onclick="<?php echo $action; ?>">
                    </span>
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
                                $addrPhone = (strlen((string)$address['addr_phone']) > 0) ? $address['addr_phone'] : '';
                                if (!empty($addrPhone) && array_key_exists('addr_phone_dcode', $address)) {
                                    $addrPhone = '<span class="default-ltr">' . ValidateElement::formatDialCode($address['addr_phone_dcode']) . $addrPhone . '</span>';
                                }
                                echo (!empty($addrPhone)) ? Labels::getLabel('LBL_Phone:', $siteLangId) . $addrPhone . '<br>' : ''; ?>
                            </li>
                        </ul>
                    </address>
                </label>
                <div class="my-addresses__footer">
                    <div class="actions">
                        <a href="javascript:void(0)" onclick="addAddressForm(<?php echo $address['addr_id']; ?>, <?php echo $address['addr_lang_id']; ?>)">
                            <?php echo Labels::getLabel('LBL_Edit', $siteLangId); ?>
                        </a>
                        <a href="javascript:void(0)" onclick="removeAddress(<?php echo $address['addr_id']; ?>)">
                            <?php echo Labels::getLabel('LBL_Delete', $siteLangId); ?>
                        </a>
                    </div>
                </div>
            </li>
        <?php } ?>
    </ul>
<?php } elseif (isset($noRecordsHtml)) { ?>
    <ul class="my-addresses">
        <li class="my-addresses-item my-addresses-add">
            <button class="btn btn-add-address" type="button" onclick="addAddressForm(0)">
                <svg xmlns="http://www.w3.org/2000/svg" class="svg mb-2" width="38" height="38" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M2 13.5V7h1v6.5a.5.5 0 0 0 .5.5h9a.5.5 0 0 0 .5-.5V7h1v6.5a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 13.5zm11-11V6l-2-2V2.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5z"></path>
                    <path fill-rule="evenodd" d="M7.293 1.5a1 1 0 0 1 1.414 0l6.647 6.646a.5.5 0 0 1-.708.708L8 2.207 1.354 8.854a.5.5 0 1 1-.708-.708L7.293 1.5z"></path>
                </svg>
                <?php echo Labels::getLabel('LBL_ADD_NEW_ADDRESS', $siteLangId); ?>
            </button>
        </li>
        <li><?php echo FatUtility::decodeHtmlEntities($noRecordsHtml); ?></li>
    </ul>

<?php } ?>