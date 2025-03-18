<?php
$sellerId = $productData['product_seller_id'] ?? 0;

if (0 < FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
    $fld = $frm->getField('selprod_url_keyword');
    $fld->setFieldTagAttribute('id', "urlrewrite_custom");
    $fld->htmlAfterField = '<span class="form-text text-muted">' . HtmlHelper::seoFriendlyUrl(UrlHelper::generateFullUrl('Products', 'View', array($selProdId), CONF_WEBROOT_FRONT_URL)) . '</span>';
    $fld->setFieldTagAttribute('onkeyup', "getUniqueSlugUrl(this,this.value,$selProdId)");
}

$fld = $frm->getField('selprod_hide_price');
if (null != $fld) {
    HtmlHelper::configureSwitchForCheckbox($fld);
}

$fld = $frm->getField('selprod_cart_type');
if (null != $fld) {
    $fld->setFieldTagAttribute('class', "cartTypeJs");
}

?>
<div class="add-stock-column column-main">
    <div class="add-stock-column-head">
        <div class="add-stock-column-head-label">
            <h2 class="h2">
                <?php echo $recordId > 0 ? Labels::getLabel('FRM_EDIT_PRODUCT', $langId) : Labels::getLabel('FRM_ADD_PRODUCT', $langId); ?>
            </h2>
            <span class="text-muted"> <span
                    class="required"></span><?php echo CommonHelper::replaceStringData(Labels::getLabel('FRM_FIELDS_WITH_{*}_ARE_MANDATORY', $langId), ['{*}' => '(<span class="spn_must_field">*</span>)']); ?>
            </span>
        </div>
        <?php
        $langFld = $frm->getField('lang_id');
        if (0 < $recordId) {
            $langFld->setfieldTagAttribute('class', 'form-control form-select select-language');
            $langFld->setfieldTagAttribute('onchange', 'langForm()');
            $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
            if (!empty($translatorSubscriptionKey) && $langId != CommonHelper::getDefaultFormLangId()) {
                $langFld->developerTags['fldWidthValues'] = ['d-flex', '', '', ''];
                $langFld->htmlAfterField = '<div class="input-group-append">
                                                            <a href="javascript:void(0);"  class="btn btn-brand" onclick="langForm(' . $langId . ',1)" class="btn" title="' . Labels::getLabel('BTN_AUTOFILL_LANGUAGE_DATA', $langId) . '">
                                                                <svg class="svg" width="18" height="18">
                                                                    <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite.yokart.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#icon-translate">
                                                                    </use>
                                                                </svg>
                                                            </a>
                                                        </div>';
            }
        }
        ?>
        <div class="add-stock-column-head-action">
            <div class="input-group">
                <?php
                echo $langFld->getHtml();
                ?>
            </div>
        </div>
    </div>

    <div class="card" id="basic-details">
        <div class="card-head">
            <div class="card-head-label">
                <h3 class="card-head-title"><?php echo Labels::getLabel('NAV_BASIC_DETAILS', $langId); ?></h3>
                <span
                    class="text-muted"><?php echo Labels::getLabel('MSG_MANAGE_PRODUCT_BASIC_INFORMATIONS', $langId); ?></span>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <?php
                echo HtmlHelper::getFieldHtml($frm, 'product_type', 6, ['onchange' => 'productType(this)', 'class' => 'productTypeJs']);
                echo HtmlHelper::getFieldHtml($frm, 'product_seller_id', 6, ['id' => 'product_seller_id', 'placeholder' => Labels::getLabel('FRM_SELECT_USER', $langId)]);
                echo HtmlHelper::getFieldHtml($frm, 'product_identifier', 12, [], Labels::getLabel('MSG_A_UNIQUE_IDENTIFIER_ASSOCIATED_FOR_PRODUCT_NAME', $langId));
                echo HtmlHelper::getFieldHtml($frm, 'product_name', 12, [], Labels::getLabel('MSG_A_NAME_OF_THE_PRODUCT_TO_BE_LISTED', $langId));

                echo HtmlHelper::getFieldHtml($frm, 'selprod_url_keyword', 12);

                echo HtmlHelper::getFieldHtml($frm, 'product_brand_id', 6, ['id' => 'product_brand_id'], '', '', ['label' => Labels::getLabel('FRM_ADD_BRAND', $langId), 'attr' => ['href' => 'javascript:void(0)', 'onclick' => 'addBrand()', 'class' => 'link']]);
                echo HtmlHelper::getFieldHtml($frm, 'ptc_prodcat_id', 6, ['id' => 'ptc_prodcat_id'], '', '', ['label' => Labels::getLabel('FRM_ADD_CATEGORY', $langId), 'attr' => ['href' => 'javascript:void(0)', 'onclick' => 'addCategory()', 'class' => 'link']]);
                echo HtmlHelper::getFieldHtml($frm, 'product_model', 6);

                $fld = $frm->getField('product_warranty');
                if (null !== $fld) {
                ?>
                    <div class="col-md-6">
                        <div class="form-group">
                            <?php
                            $warrantTypes = Product::getWarrantyUnits($langId);
                            ?>
                            <label class="label"><?php echo $fld->getCaption(); ?><span
                                    class="spn_must_field">*</span></label>
                            <div class="input-group">
                                <?php echo $fld->getHtml(); ?>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-input dropdown-toggle warrantyTypeButtonJs"
                                        data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true"
                                        aria-expanded="true">
                                        <?php echo $warrantTypes[$frm->getField('product_warranty_unit')->value] ?? current($warrantTypes); ?>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <?php foreach ($warrantTypes as $type => $name) { ?>
                                            <a class="dropdown-item warrantyTypeJs" href="javascript:void(0)"
                                                data-type="<?php echo $type; ?>"><?php echo $name; ?></a>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php }
                echo HtmlHelper::getFieldHtml($frm, 'selprod_cost', 6);

                echo HtmlHelper::getFieldHtml($frm, 'product_min_selling_price', 6);
                echo HtmlHelper::getFieldHtml($frm, 'product_youtube_video', 12);
                echo HtmlHelper::getFieldHtml($frm, 'product_hsn_code', 6);
                echo HtmlHelper::getFieldHtml($frm, 'product_attachements_with_inventory', 6, ['class' => 'attachmentWithInventoryJs'], Labels::getLabel('FRM_PRODUCT_DOWNLOAD_ATTACHEMENTS_AT_INVENTORY_LEVEL_INFO', $langId));
                echo HtmlHelper::getFieldHtml($frm, 'product_description', 12);
                echo HtmlHelper::getFieldHtml($frm, 'record_id', 6);
                echo HtmlHelper::getFieldHtml($frm, 'selprod_id', 6);
                echo HtmlHelper::getFieldHtml($frm, 'temp_product_id', 6, ['id' => 'temp_product_id']);
                echo HtmlHelper::getFieldHtml($frm, 'product_warranty_unit', 6, ['id' => 'product_warranty_unit']);
                ?>
            </div>
        </div>
    </div>
    <?php if (0 < FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) { ?>
        <div class="card card-toggle" id="inventory">
            <div class="card-head dropdown-toggle-custom show" data-bs-toggle="collapse" data-bs-target="#inventory-block1"
                aria-expanded="false" aria-controls="inventory-block1">
                <div class="card-head-label">
                    <h3 class="card-head-title"><?php echo Labels::getLabel('NAV_INVENTORY', $langId); ?>
                    </h3>
                    <span class="text-muted"><?php echo Labels::getLabel('MSG_SET_UP_NEW_INVENTORY', $langId); ?></span>
                </div>
                <div class="card-toolbar"> <i class="dropdown-toggle-custom-arrow"></i></div>
            </div>
            <div class="show" id="inventory-block1">
                <div class="card-body p-0">
                    <div class="px-4">
                        <div class="row justify-content-between">
                            <?php echo HtmlHelper::getFieldHtml($frm, 'selprod_stock', 4); ?>
                            <?php echo HtmlHelper::getFieldHtml($frm, 'selprod_min_order_qty', 4); ?>
                            <?php echo HtmlHelper::getFieldHtml($frm, 'selprod_available_from', 4); ?>
                            <?php echo HtmlHelper::getFieldHtml($frm, 'selprod_sku', 6, [], Labels::getLabel('LBL_STOCK_KEEPING_UNIT', $siteLangId)); ?>
                            <?php echo HtmlHelper::getFieldHtml($frm, 'selprod_max_download_times', 6); ?>
                            <?php echo HtmlHelper::getFieldHtml($frm, 'selprod_download_validity_in_days', 6); ?>
                            <?php echo HtmlHelper::getFieldHtml($frm, 'selprod_condition', 6); ?>

                            <?php
                            $fld = $frm->getField('selprod_subtract_stock');
                            if (null != $fld) {
                                HtmlHelper::configureSwitchForCheckbox($fld);
                                $fld->developerTags['noCaptionTag'] = true;
                                echo '<div class="col-md-12"><div class="form-group"><div class="setting-block">' . $fld->getHtml() . '</div></div></div>';
                            }
                            ?>
                            <?php
                            $fld = $frm->getField('selprod_track_inventory');
                            if (null != $fld) {
                                HtmlHelper::configureSwitchForCheckbox($fld);
                                $fld->developerTags['noCaptionTag'] = true;
                                $fld->addFieldtagAttribute('id', 'selprod_track_inventory');
                                echo '<div class="col-md-12"><div class="form-group"><div class="setting-block">' . $fld->getHtml() . '</div></div></div>';
                            }
                            ?>
                            <?php echo HtmlHelper::getFieldHtml($frm, 'selprod_threshold_stock_level', 6); ?>
                            <?php
                            $fld = $frm->getField('use_shop_policy');
                            if (null != $fld) {
                                $fld->setFieldTagAttribute('class', "fieldsVisibilityJs");
                                HtmlHelper::configureSwitchForCheckbox($fld);
                                $fld->developerTags['noCaptionTag'] = true;
                                echo '<div class="col-md-12"><div class="form-group"><div class="setting-block">' . $fld->getHtml() . '</div></div></div>';
                            }
                            ?>
                            <?php echo HtmlHelper::getFieldHtml($frm, 'selprod_return_age', 6); ?>
                            <?php echo HtmlHelper::getFieldHtml($frm, 'selprod_cancellation_age', 6); ?>
                            <?php echo HtmlHelper::getFieldHtml($frm, 'selprod_cart_type', 6); ?>

                            <?php
                            $fld = $frm->getField('selprod_hide_price');
                            if (null != $fld) {
                                echo '<div class="col-md-6 selprodHidePriceBlockJs" style="display:none;"><div class="form-group mb-0 mt-3"><div class="setting-block">' . $fld->getHtml() . '</div></div></div>';
                            } ?>
                            <?php echo HtmlHelper::getFieldHtml($frm, 'selprod_comments', 12); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php } else { ?>
        <div class="card card-toggle" id="variants-options">
            <div class="card-head dropdown-toggle-custom show" data-bs-toggle="collapse" data-bs-target="#stock-block1"
                aria-expanded="false" aria-controls="stock-block1">
                <div class="card-head-label">
                    <h3 class="card-head-title"><?php echo Labels::getLabel('NAV_VARIANTS_&_OPTIONS', $langId); ?>
                    </h3>
                    <span
                        class="text-muted"><?php echo Labels::getLabel('MSG_CUSTOMIZE_PRODUCT_VARIENTS_INCLUDING_SIZE_COLOR_ETC', $langId); ?></span>
                </div>
                <div class="card-toolbar"> <i class="dropdown-toggle-custom-arrow"></i></div>
            </div>
            <div class="show" id="stock-block1">
                <div class="card-table p-0 ">
                    <?php
                    if (0 < $recordId) {
                        echo HtmlHelper::getErrorMessageHtml(Labels::getLabel('ERR_IF_INVENTORY_IS_ALREADY_ADDED_THEN_YOU_CANNOT_BIND_FURTHER_OPTIONS.'));
                    } ?>
                    <div class="table-responsive table-scrollable js-scrollable">
                        <table class="table table-variants" id="variantsJs" data-auto-column-width="0">
                            <thead class="tableHeadJs">
                                <tr>
                                    <th width="40%"><?php echo Labels::getLabel('FRM_OPTIONS', $langId) ?></th>
                                    <th width="40%"><?php echo Labels::getLabel('FRM_OPTION_VALUES', $langId) ?></th>
                                    <?php if (false === $hasInventory) { ?>
                                        <th class="align-right" width="20%"></th>
                                    <?php } ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $optionCount = count($productOptions);
                                for ($i = 0; $i <= (1 > $optionCount ? 0 : $optionCount - 1); $i++) {
                                    $prodOption = $productOptions[$i] ?? [];
                                    $this->includeTemplate('products/get-variant-row.php', ['langId' => $langId, 'index' => $i, 'hasInventory' => $hasInventory, 'productOption' => $prodOption]);
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="separator separator-dashed my-4"></div>
                    <div class="px-4">
                        <div class="row justify-content-between">
                            <div class="col">
                                <label
                                    class="label"><?php echo Labels::getLabel('LBL_PRODUCT_HAS_SAME_EAN/UPC_CODE_FOR_ALL_VARIENTS', $langId); ?></label>
                            </div>
                            <div class="col-auto">
                                <?php
                                $fld = $frm->getField('upc_type');
                                HtmlHelper::configureSwitchForRadio($fld);
                                $fld->addOptionListTagAttribute('class', 'list-radio my-2');
                                $fld->addFieldTagAttribute('onchange', 'upcType()');
                                $fld->addFieldTagAttribute('class', 'upc_type');
                                echo $fld->getHtml();
                                ?>
                            </div>
                        </div>
                    </div>
                    <div id="variantsListJs"></div>
                </div>
                <div class="card-foot">
                    <div class="row justify-content-end">
                        <div class="col-auto">
                            <a class="btn btn-icon btn-outline-brand" href="<?php echo UrlHelper::generateUrl('options') ?>"
                                target="_blank">
                                <svg class="svg btn-icon-start" width="18" height="18">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#add">
                                    </use>
                                </svg>
                                <span><?php echo Labels::getLabel('NAV_ADD_PRODUCT_OPTION', $langId); ?></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
    <div class="card card-toggle" id="media">
        <div class="card-head dropdown-toggle-custom show" data-bs-toggle="collapse" data-bs-target="#stock-block2"
            aria-expanded="false" aria-controls="stock-block2">
            <div class="card-head-label">
                <h3 class="card-head-title"><?php echo Labels::getLabel('NAV_MEDIA', $langId); ?> </h3>
                <span
                    class="text-muted"><?php echo Labels::getLabel('MSG_MANAGE_YOUR_PRODUCT_IMAGES_GALLERY', $langId); ?></span>
            </div>
            <div class="card-toolbar">
                <i class="dropdown-toggle-custom-arrow"></i>
            </div>
        </div>
        <div class="card-body show" id="stock-block2">
            <div>
                <div class="d-flex justify-content-between mb-3">
                    <h6 class="h6 "><?php echo Labels::getLabel('LBL_UPLOADED_MEDIA', $langId); ?></h6>
                    <a href="javascript:void(0)" onclick="imageForm();"
                        class="link"><?php echo Labels::getLabel('LBL_ADVANCED_MEDIA', $langId); ?></a>
                </div>
                <!-- <h6 class="h6 mb-3">Uploaded media</h6> -->
                <ul class="uploaded-stocks" id="productDefaultImagesJs">
                    <li class="browse unsortableJs"><button type="button" class="browse-button"
                            onclick="$('#hiddenMediaFrmFileJs').click();">
                            <strong> <?php echo Labels::getLabel('LBL_UPLOAD_IMAGES(S)', $langId); ?></strong>
                            <span
                                class="text-muted form-text"><?php echo Labels::getLabel('MSG_PNG,JPEG_ACCEPTED', $langId); ?></span></button>
                    </li>
                </ul>

                <div class="form-text text-muted pt-2">
                    <?php echo sprintf(Labels::getLabel('LBL_Preferred_Dimensions_%s', $siteLangId), $imgFrm->getField('min_width')->value . ' x ' . $imgFrm->getField('min_height')->value); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="card card-toggle" id="specifications">
        <div class="card-head dropdown-toggle-custom show" data-bs-toggle="collapse"
            data-bs-target="#specifications-block" aria-expanded="false" aria-controls="specifications-block">
            <div class="card-head-label">
                <h3 class="card-head-title"><?php echo Labels::getLabel('NAV_SPECIFICATIONS', $langId); ?>
                </h3>
                <span
                    class="text-muted"><?php echo Labels::getLabel('MSG_MANAGE_PRODUCT_RELATED_SPECIFICATIONS', $langId); ?></span>
            </div>
            <div class="card-toolbar"> <i class="dropdown-toggle-custom-arrow"></i></div>
        </div>
        <div class="card-body p-0 show" id="specifications-block">
            <div class="p-4" id="specificationsFormJs">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="label">
                                <?php echo Labels::getLabel('FRM_SPECIFICATION_NAME', $langId); ?>
                            </label>
                            <input type="text" name="sp_label" id="sp_label" value="" data-required="1">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="label">
                                <?php echo Labels::getLabel('FRM_SPECIFICATION_VALUE', $langId); ?>
                            </label>
                            <input type="text" name="sp_value" id="sp_value" value="" data-required="1">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="label">
                                <?php echo Labels::getLabel('FRM_SPECIFICATION_GROUP', $langId); ?>
                            </label>
                            <input type="text" name="sp_group" id="sp_group" value="" data-required="0">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="label"></label>
                            <input type="hidden" name="sp_id" id="sp_id" value="0" data-required="0">
                            <button type="button" id="btnAddSpecJs" class="btn btn-brand btn-wide"
                                onclick="addSpecification()"
                                data-updateLbl="<?php echo Labels::getLabel('BTN_UPDATE', $langId); ?>"
                                data-addLbl="<?php echo Labels::getLabel('BTN_ADD', $langId); ?>">
                                <?php echo Labels::getLabel('BTN_ADD', $langId); ?>
                            </button>
                            <button type="button" id="btnClearSpecJs" class="btn btn-outline-brand btn-wide"
                                onclick="clearProdSpecForm()"
                                data-updateLbl="<?php echo Labels::getLabel('BTN_UPDATE', $langId); ?>"
                                data-addLbl="<?php echo Labels::getLabel('BTN_ADD', $langId); ?>">
                                <?php echo Labels::getLabel('BTN_CLEAR', $langId); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div id="specificationsListSeprJs" class="separator separator-dashed my-4 hide"></div>
            <div id="specificationsListJs">
            </div>
        </div>
    </div>
    <div class="card card-toggle" id="tax-shipping">
        <div class="card-head dropdown-toggle-custom show" data-bs-toggle="collapse" data-bs-target="#stock-block4"
            aria-expanded="false" aria-controls="stock-block4">
            <div class="card-head-label">
                <h3 class="card-head-title">
                    <?php
                    if (1 > $recordId || (isset($productData['product_type']) && Product::PRODUCT_TYPE_PHYSICAL == $productData['product_type'])) {
                        echo Labels::getLabel('NAV_TAX_AND_SHIPPING', $siteLangId);
                    } else {
                        echo Labels::getLabel('NAV_TAX', $siteLangId);
                    }
                    ?>
                </h3>
                <span class="text-muted">
                    <?php
                    if (1 > $recordId || (isset($productData['product_type']) && Product::PRODUCT_TYPE_PHYSICAL == $productData['product_type'])) {
                        echo Labels::getLabel('MSG_SETUP_TAX_AND_SHIPPING_INFORMATION_OF_THE_PRODUCT', $siteLangId);
                    } else {
                        echo Labels::getLabel('MSG_SETUP_TAX_INFORMATION_OF_THE_PRODUCT', $siteLangId);
                    }
                    ?>
                </span>
            </div>
            <div class="card-toolbar"> <i class="dropdown-toggle-custom-arrow"></i></div>
        </div>
        <div class="card-body show" id="stock-block4">
            <div class="row">
                <?php
                echo HtmlHelper::getFieldHtml($frm, 'ptt_taxcat_id', 12, ['id' => 'ptt_taxcat_id'], '', '', ['label' => Labels::getLabel('FRM_ADD_TAX_CATEGORY', $langId), 'attr' => ['href' => 'javascript:void(0)', 'onclick' => 'addTaxCategory()', 'class' => 'link']]);
                echo HtmlHelper::getFieldHtml($frm, 'product_fulfillment_type', 6, ['id' => 'product_fulfillment_type']);
                echo HtmlHelper::getFieldHtml($frm, 'product_ship_package', 6, ['id' => 'product_ship_package'], '', '', ['label' => Labels::getLabel('FRM_ADD_SHIPPING_PACKAGE', $langId), 'attr' => ['href' => 'javascript:void(0)', 'onclick' => 'addShippingPackage()', 'class' => 'link']]);
                echo HtmlHelper::getFieldHtml($frm, 'product_weight', 6);
                echo HtmlHelper::getFieldHtml($frm, 'product_weight_unit', 6);
                echo HtmlHelper::getFieldHtml($frm, 'ps_from_country_id', 6, ['id' => 'ps_from_country_id']);
                echo HtmlHelper::getFieldHtml($frm, 'shipping_profile', 6, ['id' => 'shipping_profile']);
                ?>
            </div>
        </div>
    </div>

    <div class="card card-toggle digitalDownloadSectionJS hide" id="digital-files">
        <div class="card-head dropdown-toggle-custom show" data-bs-toggle="collapse"
            data-bs-target="#digital-file-block" aria-expanded="false" aria-controls="stock-block2">
            <div class="card-head-label">
                <h3 class="card-head-title"><?php echo Labels::getLabel('NAV_DIGITAL_FILES', $siteLangId); ?></h3>
                <span
                    class="text-muted"><?php echo Labels::getLabel('MSG_MANAGE_PRODUCT_DIGITIAL_FILES', $siteLangId); ?></span>
            </div>
            <?php if ($displayDigitalDownloadList) { ?>
                <div class="card-toolbar"> <i class="dropdown-toggle-custom-arrow"></i></div>
            <?php } ?>
        </div>
        <?php if ($displayDigitalDownloadList) { ?>
            <div class="card-body show" id="digital-file-block">
                <div id="digitalFilesDefaultListJs">
                </div>
            </div>
            <?php if ($displayDigitalDownloadAddBtn) { ?>
                <div class="card-foot">
                    <div class="row justify-content-end">
                        <div class="col-auto">
                            <a class="btn btn-icon btn-outline-brand" href="javascript:void(0)"
                                onclick="digitalDownloadsForm(<?php echo applicationConstants::DIGITAL_DOWNLOAD_FILE; ?>);">
                                <svg class="svg btn-icon-start" width="18" height="18">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#add">
                                    </use>
                                </svg>
                                <span><?php echo Labels::getLabel('BTN_DIGITAL_FILES', $langId); ?></span>
                            </a>
                        </div>
                    </div>
                </div>
            <?php } ?>
        <?php } else { ?>
            <div class="card-body show">
                <?php
                if (1 > $sellerId) {
                    echo HtmlHelper::getErrorMessageHtml(Labels::getLabel('ERR_YOU_CAN_UPLOAD_DIGITAL_FILES_AFTER_SETUP.'));
                } else {
                    echo HtmlHelper::getErrorMessageHtml(Labels::getLabel('ERR_YOU_ARE_NOT_ALLOWED_TO_UPLOAD_AS_THIS_PRODUCT_BELONGS_TO_SELLER.'));
                }
                ?>
            </div>
        <?php } ?>
    </div>
    <div class="card card-toggle digitalDownloadSectionJS hide" id="digital-links">
        <div class="card-head dropdown-toggle-custom show" data-bs-toggle="collapse"
            data-bs-target="#digital-link-block" aria-expanded="false" aria-controls="stock-block2">
            <div class="card-head-label">
                <h3 class="card-head-title"><?php echo Labels::getLabel('NAV_DIGITAL_LINKS', $siteLangId); ?></h3>
                <span
                    class="text-muted"><?php echo Labels::getLabel('MSG_MANAGE_PRODUCT_DIGITIAL_LINKS', $siteLangId); ?>
                </span>
            </div>

            <?php if ($displayDigitalDownloadList) { ?>
                <div class="card-toolbar"> <i class="dropdown-toggle-custom-arrow"></i></div>
            <?php } ?>
        </div>

        <?php if ($displayDigitalDownloadList) { ?>
            <div class="card-body show" id="digital-link-block">
                <div id="digitalLinksDefaultListJs">
                </div>
            </div>
            <?php if ($displayDigitalDownloadAddBtn) { ?>
                <div class="card-foot">
                    <div class="row justify-content-end">
                        <div class="col-auto">
                            <a class="btn btn-icon btn-outline-brand" href="javascript:void(0)"
                                onclick="digitalDownloadsForm(<?php echo applicationConstants::DIGITAL_DOWNLOAD_LINK; ?>);">
                                <svg class="svg btn-icon-start" width="18" height="18">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#add">
                                    </use>
                                </svg>
                                <span><?php echo Labels::getLabel('BTN_DIGITAL_LINKS', $langId); ?></span>
                            </a>
                        </div>
                    </div>
                </div>
            <?php } ?>
        <?php } else { ?>
            <div class="card-body show">
                <?php
                if (1 > $sellerId) {
                    echo HtmlHelper::getErrorMessageHtml(Labels::getLabel('ERR_YOU_CAN_ADD_DIGITAL_LINKS_AFTER_SETUP.'));
                } else {
                    echo HtmlHelper::getErrorMessageHtml(Labels::getLabel('ERR_YOU_ARE_NOT_ALLOWED_TO_ADD_AS_THIS_PRODUCT_BELONGS_TO_SELLER.'));
                }
                ?>
            </div>
        <?php } ?>
    </div>
</div>
<div class="add-stock-column column-actions">
    <div class="sticky-top">
        <div class="card">
            <div class="card-body">
                <button type="submit"
                    class="btn btn-brand btn-block submitBtnJs"><?php echo Labels::getLabel('FRM_SAVE', $langId); ?></button>
                <div class="mt-3">
                    <?php
                    $fld = $frm->getField('product_active');
                    if (null != $fld) {
                        HtmlHelper::configureSwitchForCheckbox($fld);
                        echo '<div class="form-group"><div class="setting-block">' . $fld->getHtml() . '</div></div>';
                    }
                    $fld = $frm->getField('product_approved');
                    $uLangDatafld = $frm->getField('auto_update_other_langs_data');

                    if (null != $fld) {
                        HtmlHelper::configureSwitchForCheckbox($fld);
                        echo null == $uLangDatafld ? '<div class="setting-block">' . $fld->getHtml() . '</div>' : '<div class="form-group"><div class="setting-block">' . $fld->getHtml() . '</div></div>';
                    }

                    if (null != $uLangDatafld) {
                        HtmlHelper::configureSwitchForCheckbox($uLangDatafld);
                        echo '<div class="setting-block">' . $uLangDatafld->getHtml() . '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <ul class="list-featured">
                    <?php
                    $publishInventory = $frm->getField('selprod_active');
                    $fld = $frm->getField('product_featured');
                    $codFld = $frm->getField('product_cod_enabled');

                    if (null != $publishInventory) {
                        HtmlHelper::configureSwitchForCheckbox($publishInventory);
                        echo '<li><div class="form-group"><div class="setting-block">' . $publishInventory->getHtml() . '</div></div></li>';
                    }

                    if (null != $fld) {
                        HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel('FRM_PRODUCT_DISPLAYED_UNDER_FEATURED_ON_STOREFRONT', $langId));
                        echo null != $fld && $codEnabled ? '<li><div class="form-group"><div class="setting-block">' . $fld->getHtml() . '</div></div></li>' : '<li><div class="setting-block">' . $fld->getHtml() . '</div></li>';
                    }

                    if (null != $codFld && $codEnabled) {
                        HtmlHelper::configureSwitchForCheckbox($codFld, Labels::getLabel('FRM_PRODUCT_AVAILABLE_FOR_CASH_ON_DELIVERY', $langId));
                        echo '<li><div class="setting-block">' . $codFld->getHtml() . '</div></li>';
                    }
                    ?>
                </ul>
            </div>
        </div>
        <?php
        $fld = $frm->getField('product_tags');
        if (null != $fld) {
            $fld->addFieldTagAttribute('class', 'form-tagify');
            $fld->addFieldTagAttribute('id', 'product_tags');
        ?>
            <div class="card">
                <div class="card-head">
                    <div class="card-head-label">
                        <h3 class="card-head-title"><?php echo Labels::getLabel('FRM_TAGS', $langId); ?></h3>
                        <span class="text-muted">
                            <?php echo Labels::getLabel('FRM_CREATE_KEYWORD_TAGS_TO_MAKE_IT_EASIER_FOR_BUYERS', $langId); ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <?php echo $fld->getHtml(); ?>
                </div>
            </div>
        <?php } ?>
    </div>
</div>