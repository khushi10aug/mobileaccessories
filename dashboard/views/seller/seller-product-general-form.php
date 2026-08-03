<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

if ($selprod_id > 0 || empty($productOptions)) {
    $frmSellerProduct->setFormTagAttribute('onsubmit', 'setUpSellerProduct(this); return(false);');
} else {
    $frmSellerProduct->setFormTagAttribute('onsubmit', 'setUpMultipleSellerProducts(this); return(false);');
}

$frmSellerProduct->setFormTagAttribute('class', 'form form--horizontal inventoryForm-js layout--' . Language::getLayoutDirection($siteLangId));
$frmSellerProduct->setFormTagAttribute('dir', Language::getLayoutDirection($siteLangId));

$autoUpdateFld = $frmSellerProduct->getField('auto_update_other_langs_data');
if (null != $autoUpdateFld) {
    $autoUpdateFld->developerTags['cbLabelAttributes'] = array('class' => 'checkbox');
    $autoUpdateFld->developerTags['cbHtmlAfterCheckbox'] = '';
}

$returnAgeFld = $frmSellerProduct->getField('selprod_return_age');
if (null != $returnAgeFld) {
    $returnAge = FatUtility::int($returnAgeFld->value);
    $returnAgeFld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel('FRM_IN_DAYS', $siteLangId) . ' </span>';
}
$cancellationAgeFld = $frmSellerProduct->getField('selprod_cancellation_age');
if (null != $cancellationAgeFld) {
    $cancellationAgeFld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel('FRM_WARRANTY_IN_DAYS', $siteLangId) . ' </span>';
}

$hidden = '';
if ('' === $returnAgeFld->value || '' === $cancellationAgeFld->value) {
    $hidden = 'hidden';
}

$urlFld = $frmSellerProduct->getField('selprod_url_keyword');
if ($urlFld) {
    $urlFld->setFieldTagAttribute('id', "urlrewrite_custom");
    $urlFld->setFieldTagAttribute('onkeyup', "getUniqueSlugUrl(this,this.value,$selprod_id)");
    $urlFld->htmlAfterField = "<span class='form-text text-muted'>" . UrlHelper::generateFullUrl('Products', 'View', array($selprod_id), CONF_WEBROOT_FRONTEND) . '</span>';
}

if (false === Plugin::isActive('EasyEcom') && $product_type != Product::PRODUCT_TYPE_SERVICE) {
    $fld = $frmSellerProduct->getField('selprod_subtract_stock');
    $fld->developerTags['cbLabelAttributes'] = array('class' => 'checkbox');
    $fld->developerTags['cbHtmlAfterCheckbox'] = '';

    $fld = $frmSellerProduct->getField('selprod_track_inventory');
    $fld->developerTags['cbLabelAttributes'] = array('class' => 'checkbox');
    $fld->developerTags['cbHtmlAfterCheckbox'] = '';
}
$fld = $frmSellerProduct->getField('use_shop_policy');
HtmlHelper::configureSwitchForCheckbox($fld);

$fld = $frmSellerProduct->getField('selprod_hide_price');
if (null !== $fld) {
    $fld->developerTags['cbLabelAttributes'] = array('class' => 'checkbox');
    $fld->developerTags['cbHtmlAfterCheckbox'] = '';
}
$submitBtnFld = $frmSellerProduct->getField('btn_submit');
$submitBtnFld->setFieldTagAttribute('class', 'btn btn-brand');
$submitBtnFld->developerTags['col'] = 12;

$cancelBtnFld = $frmSellerProduct->getField('btn_cancel');
$cancelBtnFld->setFieldTagAttribute('class', 'btn btn-outline-gray js-cancel-inventory');

$fld = $frmSellerProduct->getField('selprod_active');
if (null !== $fld) {
    $fld->developerTags['cbLabelAttributes'] = array('class' => 'checkbox');
    $fld->developerTags['cbHtmlAfterCheckbox'] = '';
}
?>
<div class="row">
    <div class="col-md-12">
        <div class="form__subcontent">
            <?php echo $frmSellerProduct->getFormTag(); ?>
            <div class="row">
                <div class="col-md-<?php echo ($urlFld) ? 6 : 12; ?>">
                    <div class="field-set">
                        <div class="caption-wraper"><label class="field_label"><?php echo $frmSellerProduct->getField('selprod_title' . $siteLangId)->getCaption(); ?><span class="spn_must_field">*</span></label></div>
                        <div class="field-wraper">
                            <div class="field_cover"><?php echo $frmSellerProduct->getFieldHtml('selprod_title' . $siteLangId); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if ($urlFld) { ?>
                    <div class="col-md-6">
                        <div class="field-set">
                            <div class="caption-wraper"><label class="field_label"><?php echo $frmSellerProduct->getField('selprod_url_keyword')->getCaption(); ?><span class="spn_must_field">*</span></label></div>
                            <div class="field-wraper">
                                <div class="field_cover"><?php echo $frmSellerProduct->getFieldHtml('selprod_url_keyword'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
            <?php if ($product_type != Product::PRODUCT_TYPE_SERVICE) { ?>
                <?php if (false === Plugin::isActive('EasyEcom')) { ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="field-set d-flex align-items-center">
                                <div class="field-wraper">
                                    <div class="field_cover"><?php echo $frmSellerProduct->getFieldHtml('selprod_subtract_stock'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="field-set d-flex align-items-center">
                                <div class="field-wraper">
                                    <div class="field_cover"><?php echo $frmSellerProduct->getFieldHtml('selprod_track_inventory'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>

                <div class="row">
                    <div class="selprod_threshold_stock_level_fld col-md-6">
                        <div class="field-set">
                            <div class="caption-wraper"><label class="field_label"><?php echo $frmSellerProduct->getField('selprod_threshold_stock_level')->getCaption(); ?>
                                    <i class="fa fa-info-circle" data-bs-toggle="tooltip" data-placement="right" title="<?php echo Labels::getLabel('LBL_Alert_stock_level_hint_info', $siteLangId); ?>"></i></label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover"><?php echo $frmSellerProduct->getFieldHtml('selprod_threshold_stock_level'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="field-set">
                            <div class="caption-wraper">
                                <label class="field_label">
                                    <?php echo $frmSellerProduct->getField('selprod_min_order_qty')->getCaption(); ?>
                                    <span class="spn_must_field">*</span>
                                </label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover"><?php echo $frmSellerProduct->getFieldHtml('selprod_min_order_qty'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php }
            if ($product_type == Product::PRODUCT_TYPE_DIGITAL) { ?>
                <div class="row">
                    <div class="col-md-6">
                        <div class="field-set">
                            <div class="caption-wraper">
                                <label class="field_label">
                                    <?php echo $frmSellerProduct->getField('selprod_max_download_times')->getCaption(); ?>
                                    <span class="spn_must_field">*</span>
                                </label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover"><?php echo $frmSellerProduct->getFieldHtml('selprod_max_download_times'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="field-set">
                            <div class="caption-wraper">
                                <label class="field_label">
                                    <?php echo $frmSellerProduct->getField('selprod_download_validity_in_days')->getCaption(); ?>
                                    <span class="spn_must_field">*</span>
                                </label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover"><?php echo $frmSellerProduct->getFieldHtml('selprod_download_validity_in_days'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php echo $frmSellerProduct->getFieldHtml('selprod_condition'); ?>
            <?php } ?>
            <?php if (in_array($product_type, [Product::PRODUCT_TYPE_PHYSICAL, Product::PRODUCT_TYPE_SERVICE])) { ?>
                <div class="row">
                    <?php if ($product_type == Product::PRODUCT_TYPE_PHYSICAL) { ?>
                        <div class="col-md-6">
                            <div class="field-set">
                                <div class="caption-wraper"><label class="field_label"><?php echo $frmSellerProduct->getField('selprod_condition')->getCaption(); ?><span class="spn_must_field">*</span></label></div>
                                <div class="field-wraper">
                                    <div class="field_cover"><?php echo $frmSellerProduct->getFieldHtml('selprod_condition'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>

                    <?php if ($product_type == Product::PRODUCT_TYPE_PHYSICAL) { ?>
                        <div class="col-md-<?php echo ($product_type == Product::PRODUCT_TYPE_PHYSICAL) ? 6 : 12; ?>">
                            <div class="form-group">
                                <div class="setting-block">
                                    <?php echo $frmSellerProduct->getFieldHtml('use_shop_policy'); ?>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>
            <?php if ($product_type == Product::PRODUCT_TYPE_PHYSICAL) { ?>
                <div class="row use-shop-policy <?php echo $hidden; ?>">
                    <?php if ($product_type == Product::PRODUCT_TYPE_PHYSICAL) { ?>
                        <div class="col-md-6">
                            <div class="field-set">
                                <div class="caption-wraper"><label class="field_label"><?php echo $frmSellerProduct->getField('selprod_return_age')->getCaption(); ?></label>
                                </div>
                                <div class="field-wraper">
                                    <div class="field_cover"><?php echo $frmSellerProduct->getFieldHtml('selprod_return_age'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                    <div class="col-md-6">
                        <div class="field-set">
                            <div class="caption-wraper"><label class="field_label"><?php echo $frmSellerProduct->getField('selprod_cancellation_age')->getCaption(); ?></label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover"><?php echo $frmSellerProduct->getFieldHtml('selprod_cancellation_age'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
            <div class="row">
                <?php if ($product_type != Product::PRODUCT_TYPE_SERVICE) { ?>
                    <div class="selprod_cod_enabled_fld col-md-6">
                        <div class="field-set">
                            <div class="caption-wraper"><label class="field_label"><?php echo $frmSellerProduct->getField('selprod_cod_enabled')->getCaption(); ?></label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover"><?php echo $frmSellerProduct->getFieldHtml('selprod_cod_enabled'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php }
                if ($product_type == Product::PRODUCT_TYPE_PHYSICAL && !empty($shipBySeller)) { ?>
                    <div class="selprod_fulfillment_type_fld col-md-6">
                        <div class="field-set">
                            <div class="caption-wraper"><label class="field_label"><?php echo $frmSellerProduct->getField('selprod_fulfillment_type')->getCaption(); ?></label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover"><?php echo $frmSellerProduct->getFieldHtml('selprod_fulfillment_type'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="field-set">
                        <div class="caption-wraper"><label class="field_label"><?php echo $frmSellerProduct->getField('selprod_available_from')->getCaption(); ?><span class="spn_must_field">*</span></label></div>
                        <div class="field-wraper">
                            <div class="field_cover"><?php echo $frmSellerProduct->getFieldHtml('selprod_available_from'); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="field-set ">
                        <div class="caption-wraper"><label class="field_label"></label></div>
                        <div class="field-wraper">
                            <div class="field_cover"><?php echo $frmSellerProduct->getFieldHtml('selprod_active'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php if (null != $frmSellerProduct->getField('selprod_cart_type')) { ?>
                <div class="row">
                    <div class="col-md-6">
                        <div class="field-set">
                            <div class="caption-wraper"><label class="field_label"><?php echo $frmSellerProduct->getField('selprod_cart_type')->getCaption(); ?><span class="spn_must_field">*</span></label></div>
                            <div class="field-wraper">
                                <div class="field_cover"><?php echo $frmSellerProduct->getFieldHtml('selprod_cart_type'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="field-set">
                            <div class="caption-wraper"><label class="field_label"></label></div>
                            <div class="field-wraper">
                                <div class="field_cover"><?php echo $frmSellerProduct->getFieldHtml('selprod_hide_price'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-text text-muted my-4">
                        <?php
                        $defaultCurrencyValue = '<span class="form-text text-muted" data-bs-toggle="tooltip" title="' . Labels::getLabel('LBL_SYSTEM_DEFAULT_CURRENCY.') . '">(' . CommonHelper::displayMoneyFormat($productMinSellingPrice, true, true) . ')</span>';
                        $errorMsg = Labels::getLabel('MSG_SELLING_PRICE_CANNOT_BE_LESS_THEN_MINIMUM_SELLING_PRICE_{MINIMUM-SELLING-PRICE}.');
                        echo CommonHelper::replaceStringData($errorMsg, ['{MINIMUM-SELLING-PRICE}' => CommonHelper::displayMoneyFormat($productMinSellingPrice) . $defaultCurrencyValue]);
                        ?>
                    </div>
                    <div class="js-scrollable table-wrap table-responsive">
                        <table id="optionsTable-js" class="table table-justified">
                            <thead>
                                <tr>
                                    <?php if (($selprod_id == 0 && !empty($availableOptions)) || !empty($optionValues)) { ?>
                                        <th style="min-width:100px;"><?php echo Labels::getLabel('LBL_Variant/Option', $siteLangId); ?>
                                        </th>
                                    <?php } ?>
                                    <th style="min-width:100px;"><?php echo Labels::getLabel('LBL_Cost_Price', $siteLangId); ?>
                                    </th>
                                    <?php $selPriceTitle = (FatApp::getConfig("CONF_PRODUCT_INCLUSIVE_TAX", FatUtility::VAR_INT, 0)) ? Labels::getLabel('LBL_This_price_is_including_the_tax_rates.', $siteLangId) : Labels::getLabel('LBL_This_price_is_excluding_the_tax_rates.', $siteLangId);
                                    $selPriceTitle .= ' ' . Labels::getLabel('LBL_Min_Selling_price', $siteLangId) . ' ' . CommonHelper::displayMoneyFormat($productMinSellingPrice, true, true);
                                    ?>
                                    <th style="min-width:100px;"><?php echo Labels::getLabel('LBL_Selling_Price', $siteLangId); ?>
                                        <i class="fa fa-info-circle" data-bs-toggle="tooltip" data-placement="right" title="<?php echo $selPriceTitle; ?>"></i>
                                    </th>
                                    <?php if ($product_type != Product::PRODUCT_TYPE_SERVICE) { ?>
                                        <th style="min-width:100px;">
                                            <?php echo Labels::getLabel('LBL_Quantity', $siteLangId); ?>
                                            <i class="fa fa-info-circle" data-bs-toggle="tooltip" data-placement="right" title="<?php echo CommonHelper::replaceStringData(Labels::getLabel('LBL_MAX_QUANTITY_CAN_BE_SET_UPTO_{MAX-RANGE}.'), ['{MAX-RANGE}' => SellerProduct::MAX_RANGE_OF_AVAILBLE_QTY]); ?>"></i>
                                        </th>

                                        <th style="min-width:100px;"><?php echo Labels::getLabel('LBL_SKU', $siteLangId); ?>
                                            <i class="fa fa-info-circle" data-bs-toggle="tooltip" data-placement="right" title="<?php echo Labels::getLabel('LBL_Stock_Keeping_Unit', $siteLangId) ?>"></i>
                                        </th>
                                    <?php } ?>
                                    <?php if (($selprod_id == 0 && !empty($availableOptions)) || !empty($optionValues) || $selprod_id > 0) { ?>
                                        <th style="min-width:100px;">
                                            <?php echo Labels::getLabel('LBL_ACTION', $siteLangId); ?>
                                        </th>
                                    <?php } ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($selprod_id == 0 && !empty($availableOptions)) {
                                    $i = $j = 0;
                                    foreach ($availableOptions as $optionKey => $optionValue) {
                                        if (SellerProduct::UPDATE_OPTIONS_COUNT < $i) {
                                            $j++;
                                            $i = 0;
                                        } ?>
                                        <tr id="<?php echo $optionKey; ?>">
                                            <td><?php echo str_replace("_", " | ", $optionValue); ?>
                                            </td>
                                            <td class="optionFld-js"><?php echo $frmSellerProduct->getFieldHtml('varients[' . $j . '][selprod_cost' . $optionKey . ']'); ?>
                                            </td>
                                            <td class="optionFld-js"><?php echo $frmSellerProduct->getFieldHtml('varients[' . $j . '][selprod_price' . $optionKey . ']'); ?>
                                            </td>
                                            <?php if ($product_type != Product::PRODUCT_TYPE_SERVICE) { ?>
                                                <td class="optionFld-js"><?php echo $frmSellerProduct->getFieldHtml('varients[' . $j . '][selprod_stock' . $optionKey . ']'); ?>
                                                </td>

                                                <td class="optionFld-js fldSku"><?php echo $frmSellerProduct->getFieldHtml('varients[' . $j . '][selprod_sku' . $optionKey . ']'); ?>
                                                </td>
                                            <?php } ?>
                                            <td>
                                                <ul class="actions">
                                                    <li>
                                                        <a href="javascript:void(0)" onclick="optionImageForm(<?php echo $product_id; ?>, '<?php echo $optionKey; ?>')" title="<?php echo Labels::getLabel('LBL_IMAGES', $siteLangId); ?>">
                                                            <svg class="svg" width="18" height="18">
                                                                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#images"></use>
                                                            </svg>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <button disabled="disabled" onclick="copyRowData(this)" type="button" class="js-copy-btn btn btn-secondary btn-elevate btn-icon" title="<?php echo Labels::getLabel('LBL_Copy_to_clipboard', $siteLangId) ?>">
                                                            <i class="fas fa-paste"></i>
                                                        </button>
                                                    </li>
                                                </ul>
                                            </td>
                                        </tr>
                                    <?php
                                        $i++;
                                    } ?>
                                <?php
                                } else {
                                    $editOptionKey = $editOptionKey ?? '0';
                                    ?>
                                    <tr id="<?php echo $editOptionKey; ?>">
                                        <?php if (!empty($optionValues)) { ?>
                                            <td><?php echo implode(' | ', $optionValues); ?>
                                            </td>
                                        <?php } ?>
                                        <td><?php echo $frmSellerProduct->getFieldHtml('selprod_cost'); ?>
                                        </td>
                                        <td><?php echo $frmSellerProduct->getFieldHtml('selprod_price'); ?>
                                        </td>
                                        <?php if ($product_type != Product::PRODUCT_TYPE_SERVICE) { ?>
                                            <td><?php echo $frmSellerProduct->getFieldHtml('selprod_stock'); ?>
                                            </td>

                                            <td><?php echo $frmSellerProduct->getFieldHtml('selprod_sku'); ?>
                                            </td>
                                        <?php } ?>
                                        <td>
                                            <ul class="actions">
                                                <li>
                                                    <a href="javascript:void(0)" onclick="optionImageForm(<?php echo $product_id; ?>, '<?php echo $editOptionKey; ?>')" title="<?php echo Labels::getLabel('LBL_IMAGES', $siteLangId); ?>">
                                                        <svg class="svg" width="18" height="18">
                                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#images"></use>
                                                        </svg>
                                                    </a>
                                                </li>
                                            </ul>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="field-set">
                        <div class="caption-wraper"><label class="field_label">
                                <?php echo $frmSellerProduct->getField('selprod_comments' . $siteLangId)->getCaption(); ?></label>
                        </div>
                        <div class="field-wraper">
                            <div class="field_cover"><?php echo $frmSellerProduct->getFieldHtml('selprod_comments' . $siteLangId); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            $languages = Language::getAllNames();
            unset($languages[$siteLangId]);
            $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
            if (!empty($translatorSubscriptionKey) && count($languages) > 0) { ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="field-set mb-0">
                            <div class="caption-wraper"></div>
                            <div class="field-wraper">
                                <div class="field_cover">
                                    <?php echo $frmSellerProduct->getFieldHtml('auto_update_other_langs_data'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
            <?php if (count($languages) > 0) { ?>
                <div class="row">
                    <div class="col-md-12">
                        <?php foreach ($languages as $langId => $langName) {
                            $layout = Language::getLayoutDirection($langId); ?>
                            <div class="accordion mt-4" id="specification-accordion">
                                <h6 class="dropdown-toggle" data-bs-toggle="collapse" data-bs-target="#collapseOne<?php echo $langId; ?>" aria-expanded="true" aria-controls="collapseOne<?php echo $langId; ?>" onclick="translateData(this, '<?php echo $siteLangId; ?>', '<?php echo $langId; ?>')">
                                    <?php echo Labels::getLabel('LBL_Inventory_Data_for', $siteLangId) ?>
                                    <?php echo $langName; ?>
                                </h6>
                                <div id="collapseOne<?php echo $langId; ?>" class="collapse collapse-js-<?php echo $langId; ?>" aria-labelledby="headingOne" data-parent="#specification-accordion">
                                    <div class="p-4 mb-4 bg-gray rounded" dir="<?php echo $layout; ?>">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="field-set">
                                                    <div class="caption-wraper"><label class="field_label"><?php echo $frmSellerProduct->getField('selprod_title' . $langId)->getCaption(); ?></label>
                                                    </div>
                                                    <div class="field-wraper">
                                                        <div class="field_cover"><?php echo $frmSellerProduct->getFieldHtml('selprod_title' . $langId); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="field-set">
                                                    <div class="caption-wraper"><label class="field_label"><?php echo $frmSellerProduct->getField('selprod_comments' . $langId)->getCaption(); ?></label>
                                                    </div>
                                                    <div class="field-wraper">
                                                        <div class="field_cover"><?php echo $frmSellerProduct->getFieldHtml('selprod_comments' . $langId); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
            <div class="row">
                <div class="col-6">
                    <div class="field-set">
                        <div class="caption-wraper"><label class="field_label"></label></div>
                        <div class="field-wraper">
                            <div class="field_cover">
                                <?php echo $frmSellerProduct->getFieldHtml('btn_cancel'); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 text-right">
                    <div class="field-set">
                        <div class="caption-wraper"><label class="field_label"></label></div>
                        <div class="field-wraper">
                            <div class="field_cover">
                                <?php echo $frmSellerProduct->getFieldHtml('btn_submit'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php echo $frmSellerProduct->getFieldHtml('selprod_product_id');
            echo $frmSellerProduct->getFieldHtml('selprod_urlrewrite_id');
            echo $frmSellerProduct->getFieldHtml('selprod_id');
            ?>
            </form>
            <?php echo $frmSellerProduct->getExternalJS(); ?>
        </div>
    </div>
</div>
<?php echo FatUtility::createHiddenFormFromData(array('product_id' => $product_id), array('name' => 'frmSearchSellerProducts')); ?>
<script type="text/javascript">
    $('[data-bs-toggle="tooltip"]').tooltip();
    var PERCENTAGE = <?php echo applicationConstants::PERCENTAGE; ?>;
    var FLAT = <?php echo applicationConstants::FLAT; ?>;
    var
        CONF_PRODUCT_SKU_MANDATORY = <?php echo FatApp::getConfig("CONF_PRODUCT_SKU_MANDATORY", FatUtility::VAR_INT, 1); ?>;
    var LBL_MANDATORY_OPTION_FIELDS =
        '<?php echo Labels::getLabel('LBL_Atleast_one_option_needs_to_be_added_before_creating_inventory_for_this_product', $siteLangId); ?>';
    var PRODUCT_TYPE_DIGITAL = <?php echo Product::PRODUCT_TYPE_DIGITAL; ?>;
    var productType = <?php echo $product_type; ?>;
    $("document").ready(function() {
        var INVENTORY_TRACK = <?php echo Product::INVENTORY_TRACK; ?>;
        var
            INVENTORY_NOT_TRACK = <?php echo Product::INVENTORY_NOT_TRACK; ?>;
        var shippedBySeller = <?php echo $shippedBySeller; ?>;
        if (productType == PRODUCT_TYPE_DIGITAL || shippedBySeller == 0) {
            $(".selprod_cod_enabled_fld").hide();
        }

        $("input[name='selprod_track_inventory']").change(function() {
            if ($(this).prop("checked") == false) {
                $("input[name='selprod_threshold_stock_level']").val(0);
                $("input[name='selprod_threshold_stock_level']").attr("disabled", "disabled");
            } else {
                $("input[name='selprod_threshold_stock_level']").removeAttr("disabled");
            }
        });

        $("input[name='selprod_track_inventory']").trigger('change');

        $("input[name='use_shop_policy']").change(function() {
            if ($(this).is(":checked")) {
                $('.use-shop-policy').addClass('hidden');
            } else {
                $('.use-shop-policy').removeClass('hidden');
            }
        });

        $(document).on('keyup', ".optionFld-js input", function() {
            var currentObj = $(this);
            var showCopyBtn = true;
            if (currentObj.val().length > 0) {
                currentObj.parent().parent().find('input').each(function() {
                    if ($(this).parent().hasClass('fldSku') && CONF_PRODUCT_SKU_MANDATORY !=
                        1) {
                        return;
                    }
                    if ($(this).val().length == 0 || $(this).val() == 0) {
                        $(this).attr('class', 'error');
                        showCopyBtn = false;
                    }
                });
                currentObj.removeClass('error');
            } else {
                var allEmpty = true;
                currentObj.parent().parent().find('input').each(function() {
                    if ($(this).val().length > 0) {
                        allEmpty = false;
                    }
                });
                if (allEmpty) {
                    currentObj.parent().parent().find('input').each(function() {
                        $(this).removeClass('error');
                        showCopyBtn = false;
                    });
                } else {
                    currentObj.attr('class', 'error');
                    showCopyBtn = false;
                }
            }

            if (showCopyBtn == true) {
                currentObj.parent().parent().find('button').removeAttr("disabled");;
            } else {
                currentObj.parent().parent().find('button').attr("disabled", "disabled");;
            }

        });

        copyRowData = function(btn) {
            var copiedData = '';
            $(btn).parent().parent().find('input').each(function() {
                copiedData = copiedData + $(this).val() + '\t';
            });

            var copiedField = document.createElement('input');
            copiedField.value = copiedData;
            document.body.appendChild(copiedField)
            copiedField.select();
            document.execCommand("copy", false);
            copiedField.remove();

            $(btn).attr('title', langLbl.copied);
            $(btn).addClass('clicked');
        }

    });

    $(document).on('paste', '.optionFld-js input', function(e) {
        e.preventDefault();
        var pastedData = e.originalEvent.clipboardData.getData('text');
        var pastedDataArr = pastedData.split('\t');
        if (1 < pastedDataArr.length) {
            var count = 0;
            $(this).parent().parent().find('input').each(function() {
                $(this).val('')
                $(this).val(pastedDataArr[count])
                count = parseInt(count) + 1;
            });
            $(this).parent().parent().next().children().children().first().focus();
        } else {
            $(this).val(pastedDataArr)
        }
        $(this).parent().parent().find('button').removeAttr("disabled");
        $('.js-copy-btn').attr('title', langLbl.copyToClipboard);
        $('.js-copy-btn').removeClass('clicked');
    });
</script>