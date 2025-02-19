<?php defined('SYSTEM_INIT') or die('Invalid Usage . '); ?>
<style type="text/css">
    * {
        padding: 0;
        margin: 0;
    }

    body {
        margin: 0;
        padding: 0;
    }

    table {
        color: #000;
        font-size: 10px;
        line-height: 1.4;
        padding: 0;
        margin: 0;


    }

    table th,
    table td {
        padding: 15px;
    }

    table tbody {
        padding: 0;
        margin: 0;
    }

    table tr td {
        margin: 0;
    }

    .tbl-border {
        border: solid 1px #000;


    }

    .tbl-border td,
    .tbl-border th {
        border: solid 1px #ddd;
    }

    .padding10 {
        padding: 10px;
    }
</style>
<?php

$billingAddress = Labels::getLabel('LBL_NOT_AVAILABLE', $siteLangId);
if (!empty($orderDetail['billingAddress'])) {
    $billingAddress = $orderDetail['billingAddress']['oua_name'] . '<br/>';
    if ($orderDetail['billingAddress']['oua_address1'] != '') {
        $billingAddress .= $orderDetail['billingAddress']['oua_address1'] . '<br/>';
    }

    if ($orderDetail['billingAddress']['oua_address2'] != '') {
        $billingAddress .= $orderDetail['billingAddress']['oua_address2'] . '<br/>';
    }

    if ($orderDetail['billingAddress']['oua_city'] != '') {
        $billingAddress .= $orderDetail['billingAddress']['oua_city'] . ', ';
    }

    if ($orderDetail['billingAddress']['oua_state'] != '') {
        $billingAddress .= $orderDetail['billingAddress']['oua_state'] . ', ';
    }

    if ($orderDetail['billingAddress']['oua_country'] != '') {
        $billingAddress .= $orderDetail['billingAddress']['oua_country'];
    }

    if ($orderDetail['billingAddress']['oua_zip'] != '') {
        $billingAddress  .= '-' . $orderDetail['billingAddress']['oua_zip'];
    }

    if ($orderDetail['billingAddress']['oua_phone'] != '') {
        $billingAddress  .= '<br><span class="default-ltr">' . ValidateElement::formatDialCode($orderDetail['billingAddress']['oua_phone_dcode']) . $orderDetail['billingAddress']['oua_phone'] . '</span>';
    }

    if (!is_null($buyerGstNumber) && !empty(trim($buyerGstNumber))) {
        $billingAddress  .= '<br><span class="default-ltr">' . Labels::getLabel("LBL_GST_number", $siteLangId) . ': ' . $buyerGstNumber . '</span>';
    }
}

if ($orderDetail['op_product_type'] != Product::PRODUCT_TYPE_DIGITAL && !empty($orderDetail['shippingAddress'])) {
    $shippingAddress = $orderDetail['shippingAddress']['oua_name'] . '<br/>';
    if ($orderDetail['shippingAddress']['oua_address1'] != '') {
        $shippingAddress .= $orderDetail['shippingAddress']['oua_address1'] . '<br/>';
    }
    if ($orderDetail['shippingAddress']['oua_address2'] != '') {
        $shippingAddress .= $orderDetail['shippingAddress']['oua_address2'] . '<br/>';
    }
    if ($orderDetail['shippingAddress']['oua_city'] != '') {
        $shippingAddress .= $orderDetail['shippingAddress']['oua_city'] . ',';
    }

    if ($orderDetail['shippingAddress']['oua_state'] != '') {
        $shippingAddress .= $orderDetail['shippingAddress']['oua_state'] . ', ';
    }

    if ($orderDetail['shippingAddress']['oua_country'] != '') {
        $shippingAddress .= $orderDetail['shippingAddress']['oua_country'];
    }

    if ($orderDetail['shippingAddress']['oua_zip'] != '') {
        $shippingAddress .= '-' . $orderDetail['shippingAddress']['oua_zip'];
    }

    if ($orderDetail['shippingAddress']['oua_phone'] != '') {
        $shippingAddress .= '<br><span class="default-ltr">' . ValidateElement::formatDialCode($orderDetail['shippingAddress']['oua_phone_dcode']) . $orderDetail['shippingAddress']['oua_phone'] . '</span>';
    }
}

if (isset($orderDetail['pickupAddress']) && !empty($orderDetail['pickupAddress'])) {
    $pickUpAddress = $orderDetail['pickupAddress']['oua_name'] . '<br/>';
    if ($orderDetail['pickupAddress']['oua_address1'] != '') {
        $pickUpAddress .= $orderDetail['pickupAddress']['oua_address1'] . '<br/>';
    }
    if ($orderDetail['pickupAddress']['oua_address2'] != '') {
        $pickUpAddress .= $orderDetail['pickupAddress']['oua_address2'] . '<br/>';
    }
    if ($orderDetail['pickupAddress']['oua_city'] != '') {
        $pickUpAddress .= $orderDetail['pickupAddress']['oua_city'] . ',';
    }
    if ($orderDetail['pickupAddress']['oua_zip'] != '') {
        $pickUpAddress .= $orderDetail['pickupAddress']['oua_state'];
    }
    if ($orderDetail['pickupAddress']['oua_zip'] != '') {
        $pickUpAddress .= '-' . $orderDetail['pickupAddress']['oua_zip'];
    }
    if ($orderDetail['pickupAddress']['oua_phone'] != '') {
        $pickUpAddress .= '<br><span class="default-ltr">' . ValidateElement::formatDialCode($orderDetail['pickupAddress']['oua_phone_dcode']) . $orderDetail['pickupAddress']['oua_phone'] . '</span>';
    }
}

$tax = CommonHelper::orderProductAmount($orderDetail, 'TAX');
$taxableAmount = CommonHelper::orderProductAmount($orderDetail, 'TAXABLE_AMOUNT', false, User::USER_TYPE_SELLER);
$col6 = ($orderDetail['op_tax_collected_by_seller']) && $tax > 0;

$paymentMethodName = empty($orderDetail['plugin_name']) ? $orderDetail['plugin_identifier'] : $orderDetail['plugin_name'];
if (!empty($paymentMethodName) && $orderDetail['order_pmethod_id'] > 0 && $orderDetail['order_is_wallet_selected'] > 0) {
    $paymentMethodName  .= ' + ';
}
if ($orderDetail['order_is_wallet_selected'] > 0) {
    $paymentMethodName .= Labels::getLabel("LBL_Wallet", $siteLangId);
}

$item = ($orderDetail['op_selprod_title'] != '') ? $orderDetail['op_selprod_title'] : $orderDetail['op_product_name'];
$item .= '<br>';
$item .= Labels::getLabel('Lbl_Brand', $siteLangId) . ' : ';
$item .= CommonHelper::displayNotApplicable($siteLangId, $orderDetail['op_brand_name']);
$item .= '<br>';
if ($orderDetail['op_selprod_options'] != '') {
    $item .= $orderDetail['op_selprod_options'] . '<br>';
}
$item .= Labels::getLabel('LBL_Sold_By', $siteLangId) . ': ' . $orderDetail['op_shop_name'];
if ($orderDetail['op_shipping_duration_name'] != '') {
    $item .= '<br>';
    $item .= Labels::getLabel('LBL_Shipping_Method', $siteLangId) . ' : ';
    $item .= $orderDetail['op_shipping_durations'] . '-' . $orderDetail['op_shipping_duration_name'];
}
if (!is_null($orderDetail['product_hsn_code']) && !empty(trim($orderDetail['product_hsn_code']))) {
    $item .= '<br>';
    $item .= Labels::getLabel('LBL_HSN_Code', $siteLangId) . ': ' . $orderDetail['product_hsn_code'];
}
?>
<table width="100%" border="0" cellpadding="10" cellspacing="0" class="tbl-border">
    <tbody>
        <tr>
            <td style="border-bottom: solid 1px #000; text-align:center; line-height:1.5; ">
                <strong style="padding:10px;display:block;font-size: 20px;"><?php echo Labels::getlabel('LBL_ORDER_INVOICE', $siteLangId); ?></strong>
            </td>
        </tr>
        <tr>
            <td style="border-bottom: solid 1px #000; "><br>
                <strong style=" padding-bottom:10px; "><?php echo Labels::getLabel('LBL_Sold_By', $siteLangId); ?>: <?php echo $orderDetail['op_shop_name']; ?></strong>
                <br>
                <?php echo Labels::getLabel('LBL_Shop_Address', $siteLangId); ?>: <?php echo $orderDetail['shop_city'] . ', ' . $orderDetail['shop_state_name'] . ', ' . $orderDetail['shop_country_name'] . ' - ' . $orderDetail['shop_postalcode']; ?>
                <?php if (!is_null($sellerGstNumber) && !empty(trim($sellerGstNumber))) { ?>
                    <br>
                    <?php echo Labels::getLabel('LBL_GST_Number', $siteLangId); ?>: <?php echo $sellerGstNumber; ?>
                <?php } ?>
            </td>
        </tr>
        <tr>
            <td>
                <table width="100%" border="0" cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td><strong><?php echo Labels::getLabel('LBL_Bill_to', $siteLangId); ?></strong>: <br><?php echo $billingAddress; ?></td>
                            <?php if ($orderDetail['op_product_type'] != Product::PRODUCT_TYPE_DIGITAL && !empty($orderDetail['shippingAddress'])) { ?>
                                <td><strong><?php echo Labels::getLabel('LBL_Ship_to', $siteLangId); ?></strong>:<br><?php echo $shippingAddress; ?></td>
                            <?php } ?>
                            <?php if (!empty($orderDetail['pickupAddress'])) { ?>
                                <td><strong><?php echo Labels::getLabel('LBL_Pickup_Details', $siteLangId); ?></strong><br> <br><?php echo $pickUpAddress; ?></td>
                            <?php } ?>
                        </tr>
                    </tbody>
                </table>

            </td>
        </tr>
        <tr>
            <td style="border-top: solid 1px #000;">

                <table width="100%" border="0" cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td><strong><?php echo Labels::getLabel('LBL_Order', $siteLangId); ?>:</strong> <?php echo $orderDetail['order_number']; ?><br><strong><?php echo Labels::getLabel('LBL_Invoice_Number', $siteLangId); ?>:</strong> <?php echo $orderDetail['op_invoice_number']; ?><br><strong><?php echo Labels::getLabel('LBL_Payment_Method', $siteLangId); ?>:</strong> <?php echo $paymentMethodName; ?>
                            </td>
                            <td><strong><?php echo Labels::getLabel('LBL_Order_Date', $siteLangId); ?>:</strong> <?php echo FatDate::format($orderDetail['order_date_added']); ?><br>
                                <?php if (!empty($orderDetail['opship_tracking_number'])) { ?>
                                    <strong><?php echo Labels::getLabel('LBL_Tracking_ID', $siteLangId); ?>:</strong><?php echo $orderDetail['opship_tracking_number']; ?>
                                <?php } ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <table class="tbl-border" width="100%" border="0" cellpadding="10" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="<?php echo ($col6) ? '35%' : '55%'; ?>" style="padding:10px; ;text-align: <?php echo CommonHelper::getLayoutDirection() == 'rtl' ? 'right' : 'left'; ?>; border-bottom:1px solid #ddd; background-color:#ddd; "><?php echo Labels::getLabel('LBL_Item', $siteLangId); ?></th>
                            <th width="15%" style="padding:10px; ;text-align: <?php echo CommonHelper::getLayoutDirection() == 'rtl' ? 'right' : 'left'; ?>; border-bottom:1px solid #ddd; background-color:#ddd; "><?php echo Labels::getLabel('LBL_Price', $siteLangId); ?></th>
                            <th width="10%" style="padding:10px; ;text-align: <?php echo CommonHelper::getLayoutDirection() == 'rtl' ? 'right' : 'left'; ?>; border-bottom:1px solid #ddd; background-color:#ddd; "><?php echo Labels::getLabel('LBL_Qty', $siteLangId); ?></th>
                            <?php if ($col6) { ?>
                                <th width="15%" style="padding:10px; ;text-align: <?php echo CommonHelper::getLayoutDirection() == 'rtl' ? 'right' : 'left'; ?>; border-bottom:1px solid #ddd; background-color:#ddd; ">
                                    <?php if (FatApp::getConfig('CONF_TAX_CATEGORIES_CODE', FatUtility::VAR_INT, 1)) {
                                        echo ($orderDetail['op_tax_code'] != '') ? $orderDetail['op_tax_code'] . ' (' . Labels::getLabel('LBL_Tax', $siteLangId) . ')' : Labels::getLabel('LBL_Tax', $siteLangId); ?>
                                    <?php } else {
                                        echo Labels::getLabel('LBL_Tax', $siteLangId);
                                    } ?>
                                </th>
                            <?php } ?>
                            <th width="<?php echo ($col6) ? '25%' : '20%'; ?>" style="padding:10px; ;text-align: right; border-bottom:1px solid #ddd; background-color:#ddd; "><?php echo Labels::getLabel('LBL_Total_Amount', $siteLangId); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <?php $volumeDiscount = CommonHelper::orderProductAmount($orderDetail, 'VOLUME_DISCOUNT'); ?>
                            <td style="padding:10px; ;text-align: <?php echo CommonHelper::getLayoutDirection() == 'rtl' ? 'right' : 'left'; ?>;"><?php echo $item; ?></td>
                            <td style="padding:10px; ;text-align: <?php echo CommonHelper::getLayoutDirection() == 'rtl' ? 'right' : 'left'; ?>;"><?php echo CommonHelper::displayMoneyFormat($orderDetail['op_unit_price'], true, false, true, false, true); ?></td>
                            <td style="padding:10px; ;text-align: <?php echo CommonHelper::getLayoutDirection() == 'rtl' ? 'right' : 'left'; ?>;"><?php echo $orderDetail['op_qty']; ?></td>
                            <?php if ($col6) { ?>
                                <td style="padding:10px; ;text-align: <?php echo CommonHelper::getLayoutDirection() == 'rtl' ? 'right' : 'left'; ?>;"><?php echo CommonHelper::displayMoneyFormat($tax, true, false, true, false, true); ?></td>
                            <?php } ?>
                            <td style="padding:10px; ;text-align: right;"><?php echo CommonHelper::displayMoneyFormat(CommonHelper::orderProductAmount($orderDetail, 'CART_TOTAL'), true, false, true, false, true); ?></td>
                        </tr>
                        <tr>
                            <td style="padding:10px; ;text-align: <?php echo CommonHelper::getLayoutDirection() == 'rtl' ? 'right' : 'left'; ?>;font-weight:700;background-color: #ddd;"><?php echo Labels::getLabel('Lbl_Summary', $siteLangId) ?> </td>
                            <td style="padding:10px; ;text-align: <?php echo CommonHelper::getLayoutDirection() == 'rtl' ? 'right' : 'left'; ?>;background-color: #ddd;"><strong><?php echo CommonHelper::displayMoneyFormat($orderDetail['op_unit_price'], true, false, true, false, true); ?></strong></td>
                            <td style="padding:10px; ;text-align: <?php echo CommonHelper::getLayoutDirection() == 'rtl' ? 'right' : 'left'; ?>;background-color: #ddd;"><strong><?php echo $orderDetail['op_qty']; ?></strong></td>
                            <?php if ($col6) { ?>
                                <td style="padding:10px; ;text-align: <?php echo CommonHelper::getLayoutDirection() == 'rtl' ? 'right' : 'left'; ?>;background-color: #ddd;"><strong><?php echo CommonHelper::displayMoneyFormat($tax, true, false, true, false, true); ?></strong></td>
                            <?php } ?>
                            <td style="padding:10px; ;text-align: right;background-color: #ddd;"><strong><?php echo CommonHelper::displayMoneyFormat(CommonHelper::orderProductAmount($orderDetail, 'CART_TOTAL'), true, false, true, false, true); ?></strong></td>
                        </tr>
                        <tr>
                            <td style="padding:15px 15px;font-size:14px;text-align: <?php echo CommonHelper::getLayoutDirection() == 'rtl' ? 'right' : 'left'; ?>;font-weight:700; vertical-align: top;" colspan="<?php echo ($col6) ? '2' : '1'; ?>" rowspan="10"></td>
                            <td style="padding:10px; ;text-align: <?php echo CommonHelper::getLayoutDirection() == 'rtl' ? 'right' : 'left'; ?>;border-top:1px solid #ddd;border-left:1px solid #ddd;" colspan="2"><?php echo Labels::getLabel('Lbl_Cart_Total', $siteLangId) ?></td>
                            <td style="padding:10px; ;text-align: right;border-top:1px solid #ddd;border-left:1px solid #ddd;" colspan="1"><?php echo CommonHelper::displayMoneyFormat(CommonHelper::orderProductAmount($orderDetail, 'cart_total'), true, false, true, false, true); ?></td>
                        </tr>
                        <?php $volumeDiscount = CommonHelper::orderProductAmount($orderDetail, 'VOLUME_DISCOUNT');
                        if ($volumeDiscount != 0) { ?>
                            <tr>
                                <td style="padding:10px; ;text-align: <?php echo CommonHelper::getLayoutDirection() == 'rtl' ? 'right' : 'left'; ?>;border-top:1px solid #ddd;border-left:1px solid #ddd;" colspan="2"><?php echo Labels::getLabel('LBL_Volume_Discount', $siteLangId) ?></td>
                                <td style="padding:10px; ;text-align: right;border-top:1px solid #ddd;border-left:1px solid #ddd;" colspan="1"><?php echo CommonHelper::displayMoneyFormat($volumeDiscount, true, false, true, false, true); ?></td>
                            </tr>
                        <?php } ?>
                        <?php if ($col6) { ?>
                            <tr>
                                <td style="padding:10px; ;text-align: <?php echo CommonHelper::getLayoutDirection() == 'rtl' ? 'right' : 'left'; ?>;border-top:1px solid #ddd;border-left:1px solid #ddd;" colspan="2"><?php echo Labels::getLabel('LBL_TAXABLE_AMOUNT', $siteLangId) ?></td>
                                <td style="padding:10px; ;text-align: right;border-top:1px solid #ddd;border-left:1px solid #ddd;" colspan="1"><?php echo CommonHelper::displayMoneyFormat($taxableAmount, true, false, true, false, true); ?></td>
                            </tr>
                            <tr>
                                <td style="padding:10px; ;text-align: <?php echo CommonHelper::getLayoutDirection() == 'rtl' ? 'right' : 'left'; ?>;border-top:1px solid #ddd;border-left:1px solid #ddd;" colspan="2"><?php echo Labels::getLabel('LBL_Total_Tax', $siteLangId) ?></td>
                                <td style="padding:10px; ;text-align: right;border-top:1px solid #ddd;border-left:1px solid #ddd;" colspan="1"><?php echo CommonHelper::displayMoneyFormat($tax, true, false, true, false, true); ?></td>
                            </tr>
                        <?php } ?>
                        <?php if ($shippedBySeller && $orderDetail['op_product_type'] != Product::PRODUCT_TYPE_DIGITAL && 0 < CommonHelper::orderProductAmount($orderDetail, 'shipping')) { ?>
                            <tr>
                                <td style="padding:10px; ;text-align: <?php echo CommonHelper::getLayoutDirection() == 'rtl' ? 'right' : 'left'; ?>;border-top:1px solid #ddd;border-left:1px solid #ddd;" colspan="2"><?php echo Labels::getLabel('LBL_SHIPPING_CHARGES', $siteLangId) ?></td>
                                <td style="padding:10px; ;text-align: right;border-top:1px solid #ddd;border-left:1px solid #ddd;" colspan="1"><?php echo CommonHelper::displayMoneyFormat(CommonHelper::orderProductAmount($orderDetail, 'shipping'), true, false, true, false, true); ?></td>
                            </tr>
                        <?php } ?>
                        <?php if (array_key_exists('order_rounding_off', $orderDetail) && 0 != $orderDetail['order_rounding_off']) { ?>
                            <tr>
                                <td style="padding:10px; ;text-align: <?php echo CommonHelper::getLayoutDirection() == 'rtl' ? 'right' : 'left'; ?>;border-top:1px solid #ddd;border-left:1px solid #ddd;" colspan="2"><?php echo (0 < $orderDetail['order_rounding_off']) ? Labels::getLabel('LBL_Rounding_Up', $siteLangId) : Labels::getLabel('LBL_Rounding_Down', $siteLangId); ?></td>
                                <td style="padding:10px; ;text-align: right;border-top:1px solid #ddd;border-left:1px solid #ddd;" colspan="1"> <?php echo CommonHelper::displayMoneyFormat($orderDetail['order_rounding_off'], true, false, true, false, true); ?></td>
                            </tr>
                        <?php } ?>
                        <tr>
                            <td style="padding:10px; ;text-align: <?php echo CommonHelper::getLayoutDirection() == 'rtl' ? 'right' : 'left'; ?>;font-weight:700;border-top:1px solid #ddd;border-left:1px solid #ddd;" colspan="2"><strong><?php echo Labels::getLabel('LBL_Grand_Total', $siteLangId) ?></strong> </td>
                            <td style="padding:10px; ;text-align: right;font-weight:700;border-top:1px solid #ddd;border-left:1px solid #ddd;" colspan="1"><strong><?php echo CommonHelper::displayMoneyFormat(CommonHelper::orderProductAmount($orderDetail, 'netamount', false, User::USER_TYPE_SELLER), true, false, true, false, true); ?></strong></td>
                        </tr>
                    </tbody>
                </table>

            </td>
        </tr>
        <tr>
            <td>
                <table width="100%" border="0" cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td height="60" style="padding:15px;vertical-align: top;"><strong><?php echo $orderDetail['op_shop_name']; ?></strong><br><span><?php echo Labels::getLabel('LBL_Authorized_Signatory', $siteLangId); ?> </span>
                            </td>
                            <?php if ($col6) { ?>
                                <td style="background-color: #ddd;">
                                    <table width="100%" border="0" cellpadding="10" cellspacing="0">
                                        <tbody>
                                            <tr>
                                                <th style="padding:15px;" colspan="2"><?php echo Labels::getLabel('LBL_Tax_break-up', $siteLangId); ?></th>
                                            </tr>
                                            <?php if (!empty($orderDetail['taxOptions'])) {
                                                foreach ($orderDetail['taxOptions'] as $key => $val) { ?>
                                                    <tr>
                                                        <td><?php echo CommonHelper::displayTaxPercantage($val, true) ?></td>
                                                        <td><?php echo CommonHelper::displayMoneyFormat($val['value'], true, false, true, false, true) ?></td>
                                                    </tr>
                                            <?php }
                                            } ?>
                                            <tr>
                                                <td style="padding:10px; ;text-align: <?php echo CommonHelper::getLayoutDirection() == 'rtl' ? 'right' : 'left'; ?>; " colspan="2">*<?php echo Labels::getLabel('LBL_Appropriated_product-wise_and_Rate_applicable_thereunder', $siteLangId); ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            <?php } ?>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <table width="100%" border="0" cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td style="padding:20px 15px;">
                                <strong><?php echo Labels::getLabel('LBL_Regd._office', $siteLangId); ?>: </strong><?php echo nl2br(FatApp::getConfig('CONF_ADDRESS_' . $siteLangId, FatUtility::VAR_STRING, '')); ?>

                                <p>
                                    <?php $site_conatct = FatApp::getConfig('CONF_SITE_PHONE', FatUtility::VAR_INT, '');
                                    $email_id = FatApp::getConfig('CONF_CONTACT_EMAIL', FatUtility::VAR_STRING, '');
                                    if ($site_conatct || $email_id) { ?>
                                        <strong><?php echo Labels::getLabel('LBL_Contact', $siteLangId) ?>:</strong>
                                        <?php if ($site_conatct) {
                                            echo $site_conatct;
                                        } ?>
                                        <?php if ($email_id) {
                                            echo '|| ' . $email_id;
                                        } ?>

                                    <?php } ?>
                                    <?php if (!empty(FatApp::getConfig('CONF_ADMIN_GST_NUMBER'))) { ?>
                                        <br>
                                        <strong><?php echo strtoupper(Labels::getLabel('LBL_GST', $siteLangId)) ?> <?php echo Labels::getLabel('LBL_NUMBER', $siteLangId); ?>:</strong>
                                        <?php echo trim(FatApp::getConfig('CONF_ADMIN_GST_NUMBER')); ?>
                                    <?php } ?>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <table width="100%" border="0" cellpadding="0" cellspacing="0">
                    <?php $shopCodes = $orderDetail['shop_invoice_codes'];
                    $codesArr = explode("\n", $shopCodes); ?>
                    <tbody>
                        <?php $count = 1; ?>
                        <tr>
                            <?php foreach ($codesArr as $code) { ?>
                                <td style="<?php echo ($count % 2 == 0) ? 'text-align: right;' : ''; ?> font-weight: 700;"><?php echo $code; ?></td>
                            <?php
                                if ($count % 2 == 0) {
                                    echo '</tr><tr>';
                                }
                                $count++;
                            } ?>

                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>