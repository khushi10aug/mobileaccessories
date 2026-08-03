<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

if ($selprod_id > 0 || empty($productOptions)) {
    $frmSellerProduct->setFormTagAttribute('onsubmit', 'setUpSellerProduct(this); return(false);');
} else {
    $frmSellerProduct->setFormTagAttribute('onsubmit', 'setUpMultipleSellerProducts(this); return(false);');
}
$siteDefaultLangId = FatApp::getConfig('conf_default_site_lang', FatUtility::VAR_INT, 1);
$frmSellerProduct->setFormTagAttribute('class', 'form form--horizontal inventoryForm-js');
if (CommonHelper::getLayoutDirection() != Language::getLayoutDirection($siteDefaultLangId)) {
    $frmSellerProduct->addFormTagAttribute('class', "layout--" . Language::getLayoutDirection($siteDefaultLangId));
    $frmSellerProduct->setFormTagAttribute('dir', Language::getLayoutDirection($siteDefaultLangId));
}

$autoUpdateFld = $frmSellerProduct->getField('auto_update_other_langs_data');
if (null != $autoUpdateFld) {
    $autoUpdateFld->developerTags['cbLabelAttributes'] = array('class' => 'checkbox');
    $autoUpdateFld->developerTags['cbHtmlAfterCheckbox'] = '';
}

$returnAgeFld = $frmSellerProduct->getField('selprod_return_age');
$cancellationAgeFld = $frmSellerProduct->getField('selprod_cancellation_age');
$returnAge = FatUtility::int($returnAgeFld->value);
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

$subtractFld = $frmSellerProduct->getField('selprod_subtract_stock');
if (null != $subtractFld) {
    $subtractFld->developerTags['cbLabelAttributes'] = array('class' => 'checkbox');
    $subtractFld->developerTags['cbHtmlAfterCheckbox'] = '';
}

$trackInvFld = $frmSellerProduct->getField('selprod_track_inventory');
if (null != $trackInvFld) {
    $trackInvFld->developerTags['cbLabelAttributes'] = array('class' => 'checkbox');
    $trackInvFld->developerTags['cbHtmlAfterCheckbox'] = '';
}

$fld = $frmSellerProduct->getField('use_shop_policy');
$fld->developerTags['cbLabelAttributes'] = array('class' => 'checkbox');
$fld->developerTags['cbHtmlAfterCheckbox'] = '';

$submitBtnFld = $frmSellerProduct->getField('btn_submit');
$submitBtnFld->setFieldTagAttribute('class', 'btn btn-brand');
$submitBtnFld->developerTags['col'] = 12;

$cancelBtnFld = $frmSellerProduct->getField('btn_cancel');
$cancelBtnFld->setFieldTagAttribute('class', 'btn btn-outline-gray js-cancel-inventory');

$inventoryForm->setCustomRendererClass('FormRendererBS');
$inventoryForm->developerTags['colClassAfterWidthDefault'] = 'col-3';
$inventoryForm->developerTags['colWidthClassesDefault'] = [null, null, null, null];
$inventoryForm->developerTags['colWidthValuesDefault'] = [null, null, null, null];
$inventoryForm->developerTags['fldWidthClassesDefault'] = ['field_', 'field_', 'field_', 'field_'];
$inventoryForm->developerTags['fldWidthValuesDefault'] = ['cover', 'cover', 'cover', 'cover'];
$inventoryForm->developerTags['labelWidthClassesDefault'] = ['field_', 'field_', 'field_', 'field_'];
$inventoryForm->developerTags['labelWidthValuesDefault'] = ['label', 'label', 'label', 'label'];
$inventoryForm->developerTags['fieldWrapperRowExtraClassDefault'] = 'form-group';

$inventoryForm->setFormTagAttribute('onsubmit', 'addInvOption(); return(false);');
$inventoryForm->setFormTagAttribute('class', 'form optionForm-js optionFld-js form--horizontal');
if (CommonHelper::getLayoutDirection() != Language::getLayoutDirection($siteDefaultLangId)) {
    $inventoryForm->addFormTagAttribute('class', "layout--" . Language::getLayoutDirection($siteDefaultLangId));
    $inventoryForm->setFormTagAttribute('dir', $formLayout);
}


$fld = $inventoryForm->getField('btn_submit');
$fld->setFieldTagAttribute('class', 'btn btn-brand btn-block');

$fld = $inventoryForm->getField('btn_clear');
$fld->setFieldTagAttribute('class', 'btn btn-outline-gray btn-block clearBtn--js');
$fld->setFieldTagAttribute('onclick', 'clearInvOptionForm()');
?>
<div class="row">
    <div class="col-md-12">
        <div class="form__subcontent">
            <?php echo $frmSellerProduct->getFormTag(); ?>
            <div class="row">
                <div class="col-md-<?php echo ($urlFld) ? 6 : 12; ?>">
                    <div class="field-set">
                        <div class="caption-wraper"><label class="field_label"><?php echo $frmSellerProduct->getField('selprod_title' . $siteDefaultLangId)->getCaption(); ?><span class="spn_must_field">*</span></label></div>
                        <div class="field-wraper">
                            <div class="field_cover"><?php echo $frmSellerProduct->getFieldHtml('selprod_title' . $siteDefaultLangId); ?>
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
            <div class="row">
                <?php if (null != $subtractFld) { ?>
                    <div class="col-md-6">
                        <div class="field-set d-flex align-items-center">
                            <div class="field-wraper">
                                <div class="field_cover"><?php echo $frmSellerProduct->getFieldHtml('selprod_subtract_stock'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                <?php if (null != $trackInvFld) { ?>
                    <div class="col-md-6">
                        <div class="field-set d-flex align-items-center">
                            <div class="field-wraper">
                                <div class="field_cover"><?php echo $frmSellerProduct->getFieldHtml('selprod_track_inventory'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
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
                        <div class="caption-wraper"><label class="field_label"><?php echo $frmSellerProduct->getField('selprod_min_order_qty')->getCaption(); ?><span class="spn_must_field">*</span></label></div>
                        <div class="field-wraper">
                            <div class="field_cover"><?php echo $frmSellerProduct->getFieldHtml('selprod_min_order_qty'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php if ($product_type == Product::PRODUCT_TYPE_DIGITAL) { ?>
                <div class="row">
                    <div class="col-md-6">
                        <div class="field-set">
                            <div class="caption-wraper"><label class="field_label"><?php echo $frmSellerProduct->getField('selprod_max_download_times')->getCaption(); ?></label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover"><?php echo $frmSellerProduct->getFieldHtml('selprod_max_download_times'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="field-set">
                            <div class="caption-wraper"><label class="field_label"><?php echo $frmSellerProduct->getField('selprod_download_validity_in_days')->getCaption(); ?></label>
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
            <div class="row">
                <div class="col-md-6">
                    <div class="field-set">
                        <div class="caption-wraper"><label class="field_label"><?php echo $frmSellerProduct->getField('selprod_active')->getCaption(); ?></label>
                        </div>
                        <div class="field-wraper">
                            <div class="field_cover"><?php echo $frmSellerProduct->getFieldHtml('selprod_active'); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="field-set">
                        <div class="caption-wraper"><label class="field_label"><?php echo $frmSellerProduct->getField('selprod_available_from')->getCaption(); ?><span class="spn_must_field">*</span></label></div>
                        <div class="field-wraper">
                            <div class="field_cover"><?php echo $frmSellerProduct->getFieldHtml('selprod_available_from'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php if ($product_type == Product::PRODUCT_TYPE_PHYSICAL) { ?>
                <div class="row">
                    <div class="col-md-6">
                        <div class="field-set">
                            <div class="caption-wraper"><label class="field_label"><?php echo $frmSellerProduct->getField('selprod_condition')->getCaption(); ?><span class="spn_must_field">*</span></label></div>
                            <div class="field-wraper">
                                <div class="field_cover"><?php echo $frmSellerProduct->getFieldHtml('selprod_condition'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="field-set">
                            <div class="caption-wraper"><label class="field_label"></label></div>
                            <div class="field-wraper">
                                <div class="field_cover"><?php echo $frmSellerProduct->getFieldHtml('use_shop_policy'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row use-shop-policy <?php echo $hidden; ?>">
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
                <?php if ($product_type == Product::PRODUCT_TYPE_PHYSICAL && !empty($shipBySeller)) { ?>
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
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="field-set">
                        <div class="caption-wraper"><label class="field_label">
                                <?php echo $frmSellerProduct->getField('selprod_comments' . $siteDefaultLangId)->getCaption(); ?></label>
                        </div>
                        <div class="field-wraper">
                            <div class="field_cover"><?php echo $frmSellerProduct->getFieldHtml('selprod_comments' . $siteDefaultLangId); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            $languages = Language::getAllNames();
            unset($languages[$siteDefaultLangId]);
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
                                <h6 class="dropdown-toggle" data-bs-toggle="collapse" data-bs-target="#collapseOne<?php echo $langId; ?>" aria-expanded="true" aria-controls="collapseOne<?php echo $langId; ?>"><span onclick="translateData(this, '<?php echo $siteDefaultLangId; ?>', '<?php echo $langId; ?>')">
                                        <?php echo Labels::getLabel('LBL_Inventory_Data_for', $siteLangId) ?>
                                        <?php echo $langName; ?>
                                    </span>
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
                        <?php
                        } ?>
                    </div>
                </div>
            <?php } ?>
            <?php echo $frmSellerProduct->getFieldHtml('selprod_product_id');
            echo $frmSellerProduct->getFieldHtml('selprod_urlrewrite_id');
            echo $frmSellerProduct->getFieldHtml('selprod_id');

            /* Close form if adding new Inventory. */
            if ($selprod_id == 0) { ?>
                </form>
            <?php }
            echo $frmSellerProduct->getExternalJS(); ?>


            <?php if ($selprod_id == 0) { ?>
                <div class="divider my-4"></div>
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <h6>
                            <?php echo Labels::getLabel('LBL_INVENTORY_OPTIONS', $siteLangId); ?>
                        </h6>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <?php echo $inventoryForm->getFormHtml(); ?>
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
                        <table id="optionsTable-js" class="table table-justified <?php echo ($selprod_id == 0) ? 'd-none' : ''; ?>">
                            <thead>
                                <tr>
                                    <?php if ($selprod_id == 0 || !empty($optionValues)) { ?>
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
                                    <th style="min-width:100px;"><?php echo Labels::getLabel('LBL_Quantity', $siteLangId); ?>
                                    </th>
                                    <th style="min-width:100px;"><?php echo Labels::getLabel('LBL_SKU', $siteLangId); ?>
                                        <i class="fa fa-info-circle" data-bs-toggle="tooltip" data-placement="right" title="<?php echo Labels::getLabel('LBL_Stock_Keeping_Unit', $siteLangId) ?>"></i>
                                    </th>
                                    <th style="min-width:100px;">
                                        <?php echo Labels::getLabel('LBL_ACTION', $siteLangId); ?>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (0 < $selprod_id) {
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
                                        <td><?php echo $frmSellerProduct->getFieldHtml('selprod_stock'); ?>
                                        </td>
                                        <td><?php echo $frmSellerProduct->getFieldHtml('selprod_sku'); ?>
                                        </td>
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

            <?php if ($selprod_id > 0) { ?>
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
            <?php } ?>
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

        var productId = $("input[name='selprod_product_id']").val();
        var optionNamesHtm = '<a href="javascript:void(0);" onclick="viewProdOptions(<?php echo $product_id; ?>)" title="<?php echo  Labels::getLabel('LBL_CLICK_TO_VIEW_INVENTORY_OPTIONS', $siteLangId) ?>"><i class="fa fa-info-circle" style="font-size: 15px;"></i></a>';
        var selectedOptions = [];
        bindOptionAutoComplete = function() {
            if (1 > $(".optionname--js").length) {
                return;
            }

            if (0 < $("table#optionsTable-js tbody tr").length) {
                $("table#optionsTable-js").removeClass('d-none');
            }

            $(".optionname--js").select2({
                    dropdownParent: $(".optionname--js").closest('form'),
                    closeOnSelect: true,
                    dir: langLbl.layoutDirection,
                    allowClear: true,
                    placeholder: $(".optionname--js").attr('placeholder'),
                    ajax: {
                        url: fcom.makeUrl('Seller', 'getOptions', [productId]),
                        dataType: 'json',
                        delay: 250,
                        method: 'post',
                        data: function(params) {
                            if ('undefined' != typeof params.term && '' != params.term) {
                                fcom.displayProcessing();
                            }
                            return {
                                keyword: params.term, // search term
                                selectedOptions: JSON.stringify(selectedOptions),
                            };
                        },
                        processResults: function(data, params) {
                            if (0 < (data.options).length) {
                                $.ykmsg.close();
                            }
                            return {
                                results: data.options
                            };
                        },
                        cache: false
                    },
                    minimumInputLength: 0,
                    templateResult: function(result) {
                        return result.name;
                    },
                    templateSelection: function(result) {
                        return result.name || result.text;
                    },
                    language: {
                        noResults: function(params) {
                            return langLbl.typeToSearch + " " + optionNamesHtm;
                        }
                    },
                    escapeMarkup: function(markup) {
                        return markup;
                    }

                }).on('select2:selecting', function(e) {
                    var item = e.params.args.data;
                    if (0 < $('table#optionsTable-js tbody tr#' + item.id).length) {
                        $(".optionname--js").val(null).trigger('change');
                        fcom.displayErrorMessage(langLbl.alreadySelected);
                        return;
                    } else {
                        $('input[name="inv_option_name"]').val(item.name);
                        $('input[name="inv_option_id"]').val(item.id);
                    }

                }).on('select2:unselecting', function(e) {
                    $('input[name="inv_option_id"], input[name="inv_option_name"]').val('');
                }).on('select2:open', function(e) {
                    $(".optionname--js").data("select2").$dropdown.addClass("custom-select2 custom-select2-single");
                })
                .data("select2").$container.addClass("custom-select2-width custom-select2 custom-select2-single");
        }

        addInvOption = function() {
            var productId = $("input[name='selprod_product_id']").val();
            var invOptionName = $('input[name="inv_option_name"]').val();
            var invOptionId = $('input[name="inv_option_id"]').val();
            var invOptionCost = $('input[name="inv_option_cost"]').val();
            var invOptionSell_price = $('input[name="inv_option_sell_price"]').val();
            var invOptionStock = $('input[name="inv_option_stock"]').val();
            var invOptionSku = $('input[name="inv_option_sku"]').val();
            var selprod_id = $('.optionForm-js input[name="inv_option_selprod_id"]').val();

            if ('' == selprod_id &&
                ('' == invOptionName ||
                    '' == invOptionId ||
                    '' == invOptionCost ||
                    '' == invOptionSell_price ||
                    '' == invOptionStock ||
                    '' == invOptionSku)) {
                fcom.displayErrorMessage(langLbl.requiredFields);
                return;
            }

            var invForm = $('.inventoryForm-js');
            if (!$(invForm[0]).validate()) {
                return false;
            }

            var postData = {
                inv_option_index: $("table#optionsTable-js tbody tr").length,
                product_id: productId,
                inv_option_name: invOptionName,
                inv_option_id: invOptionId,
                inv_option_selprod_id: selprod_id
            };

            postData['selprod_cost' + invOptionId] = invOptionCost;
            postData['selprod_price' + invOptionId] = invOptionSell_price;
            postData['selprod_stock' + invOptionId] = invOptionStock;
            postData['selprod_sku' + invOptionId] = invOptionSku;

            var invFormData = invForm.serializeArray().reduce(function(obj, item) {
                obj[item.name] = item.value;
                return obj;
            }, {});

            postData = $.extend(invFormData, postData);
            fcom.displayProcessing();
            $.ajax({
                url: fcom.makeUrl('Seller', 'addInvOption'),
                data: postData,
                dataType: 'json',
                type: 'post',
                success: function(json) {
                    $.ykmsg.close();
                    if (1 > json.status) {
                        fcom.displayErrorMessage(json.msg);
                        return false;
                    }

                    $("table#optionsTable-js tbody").prepend(json.html);

                    if (0 < $("table#optionsTable-js tbody tr").length) {
                        $("table#optionsTable-js").removeClass('d-none');
                    }

                    if (0 < selectedOptions.length) {
                        if (-1 == selectedOptions.indexOf(invOptionId)) {
                            $.merge(selectedOptions, [invOptionId]);
                        }
                    } else {
                        selectedOptions = [invOptionId];
                    }

                    clearInvOptionForm();
                },
            });
        }

        clearInvOptionForm = function() {
            /* Reset inv option form */
            $('.clearBtn--js').show();
            $("input[name^=inv_option]").val('');
            $(".optionForm-js .optionname--js").val(null).trigger('change');
            $('.optionForm-js .optionname--js, .optionForm-js .select2').show();
            $('.optionForm-js .optionname--js').parent().find('.optionName-js').remove();
        }

        <?php if ($selprod_id == 0) { ?>
            bindOptionAutoComplete();
        <?php } ?>

        copyRowData = function(btn, selProdId = 0) {
            if ('' != $('.optionForm-js input[name="inv_option_id"]').val()) {
                fcom.displayErrorMessage(langLbl.savePrefilledValues);
                return false;
            }
            var copiedData = '';
            var tr = $(btn).closest('tr');
            tr.find('[data-val]').each(function() {
                if ('' != $(this).data('val')) {
                    copiedData = copiedData + $(this).data('val') + '\t';
                }
            });
            var copiedField = document.createElement('input');
            copiedField.value = copiedData;
            document.body.appendChild(copiedField)
            copiedField.select();
            document.execCommand("copy", false);
            copiedField.remove();

            $(btn).attr('title', langLbl.copied);
            $(btn).addClass('clicked');

            var optionId = tr.attr('id');
            if (0 < selProdId) {
                $('.clearBtn--js').hide();
                var optionName = $("#" + optionId + " td:first").text();
                $('.optionForm-js input[name="inv_option_id"]').val(optionId);
                $('.optionForm-js input[name="inv_option_name"]').val(optionName);
                $('.optionForm-js input[name="inv_option_selprod_id"]').val(selProdId);
                $('.optionForm-js .optionname--js, .optionForm-js .select2').hide();

                var optionNameHtm = '<input disabled="disabled" name="optname" class="optionName-js" value="' + optionName + '">';
                $('.optionForm-js .optionname--js').parent().append(optionNameHtm);
                tr.remove();
            }

            pasteData(copiedData, '.optionForm-js input:first');
            $('.optionName-js').attr('type', 'text');
        }

        pasteData = function(pastedData, selector) {
            var pastedDataArr = pastedData.split('\t');
            var count = 0;
            $(selector).closest('form').find('input[type="text"]').each(function() {
                $(this).val('');
                $(this).val(pastedDataArr[count]);
                count = parseInt(count) + 1;
            });
            $(selector).parent().parent().find('button').removeAttr("disabled");
            $('.js-copy-btn').attr('title', langLbl.copyToClipboard);
            $('.js-copy-btn').removeClass('clicked');
            $(selector).parent().parent().next().children().children().first().focus();
        }

        viewProdOptions = function(productId) {
            fcom.ajax(fcom.makeUrl('Seller', 'viewProdOptions', [productId]), '', function(t) {
                var res = $.parseJSON(t);
                $.ykmodal(res.html, true);
            });
        };

        sellerProductDelete = function(btn, id) {
            if (!confirm(langLbl.confirmDelete)) {
                return;
            }
            data = 'id=' + id;
            fcom.updateWithAjax(fcom.makeUrl('Seller', 'sellerProductDelete'), data, function(res) {
                var actionTr = $(btn).closest('tr');
                var optionValuePos = selectedOptions.indexOf(actionTr.attr('id'));

                if (-1 < optionValuePos) {
                    selectedOptions.splice(optionValuePos, 1);
                }

                $(btn).closest('tr').remove();
                if (1 > $("table#optionsTable-js tbody tr").length) {
                    $("table#optionsTable-js").addClass('d-none');
                }
            });
        };
    });

    $(document).on('paste', '.optionFld-js input', function(e) {
        e.preventDefault();
        var pastedData = e.originalEvent.clipboardData.getData('text');
        pasteData(pastedData, this);
    });
</script>