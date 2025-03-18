<?php defined('SYSTEM_INIT') or die('Invalid Usage');
$displayDigitalDownloadAddBtn = false;
$displayDigitalDownloadList = false;
if (0 < $recordId) {
    $displayDigitalDownloadAddBtn = ($productData['product_type'] == Product::PRODUCT_TYPE_DIGITAL && $frm->getField('product_type')->value == Product::PRODUCT_TYPE_DIGITAL && 0 < $productData['product_seller_id']);
    $displayDigitalDownloadList = $displayDigitalDownloadAddBtn && 1 > $productData['product_attachements_with_inventory'];
}
if (0 < FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
    $fld = $frm->getField('selprod_url_keyword');
    $fld->setFieldTagAttribute('id', "urlrewrite_custom");
    $fld->setFieldTagAttribute('onkeyup', "getUniqueSlugUrl(this,this.value,$selProdId)");
    $fld->htmlAfterField = "<span class='form-text text-muted'>" . UrlHelper::generateFullUrl('Products', 'View', array($selProdId), CONF_WEBROOT_FRONTEND) . '</span>';
}
?>
<div class="content-wrapper content-space mainJs" <?php echo CommonHelper::getLayoutDirection() != $formLayout ? 'dir="' . $formLayout . '"' : ''; ?>>
    <?php
    $frm->setFormTagAttribute('class', 'form');
    $frm->setFormTagAttribute('id', 'addProductfrm');
    $frm->setFormTagAttribute('onsubmit', 'setup($(\'#addProductfrm\'));return false;');
    echo $frm->getFormTag(); ?>
    <div class="content-header">
        <div class="content-header-title">
            <h2>
                <a class="btn btn-back" href="<?php echo UrlHelper::generateUrl('sellerRequests'); ?>">
                    <svg class="svg" width="24" height="24">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#back">
                        </use>
                    </svg>
                </a>
                <?php echo $recordId > 0 ? Labels::getLabel('FRM_EDIT_CUSTOM_PRODUCT_REQUEST', $langId) : Labels::getLabel('FRM_ADD_CUSTOM_PRODUCT_REQUEST', $langId); ?>
            </h2>
            <?php $this->includeTemplate('_partial/header/header-breadcrumb.php', $this->variables, false); ?>
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
                                                                    <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#icon-translate">
                                                                    </use>
                                                                </svg>
                                                            </a>
                                                        </div>';
            }
        }
        ?>
        <div class="content-header-toolbar">
            <div class="input-group">
                <?php
                echo $langFld->getHtml();
                ?>
            </div>
        </div>

    </div>
    <div class="content-body">
        <div class="add-stock" id="addStock">
            <div class="add-stock-column column-main">
                <div class="card" id="basic-details">
                    <div class="card-head">
                        <div class="card-head-label">
                            <h3 class="card-head-title"><?php echo Labels::getLabel('NAV_BASIC_DETAILS', $langId); ?>
                            </h3>
                            <span
                                class="text-muted"><?php echo Labels::getLabel('MSG_MANAGE_PRODUCT_BASIC_INFORMATIONS', $langId); ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php
                            echo HtmlHelper::getFieldHtml($frm, 'product_type', 12, ['onchange' => 'productType(this)', 'class' => 'productTypeJs']);
                            echo HtmlHelper::getFieldHtml($frm, 'product_identifier', 12, [], Labels::getLabel('MSG_A_UNIQUE_IDENTIFIER_ASSOCIATED_FOR_PRODUCT_NAME', $langId));
                            echo HtmlHelper::getFieldHtml($frm, 'product_name', 12, [], Labels::getLabel('MSG_A_NAME_OF_THE_PRODUCT_TO_BE_LISTED', $langId));
                            echo HtmlHelper::getFieldHtml($frm, 'selprod_url_keyword', 12);
                            echo HtmlHelper::getFieldHtml($frm, 'product_brand_id', 6, ['id' => 'product_brand_id'], '', '', ['label' => FatApp::getConfig('CONF_BRAND_REQUEST_APPROVAL', FatUtility::VAR_INT, 0) ? Labels::getLabel('FRM_REQUEST_FOR_BRAND', $langId) : Labels::getLabel('FRM_ADD_BRAND', $langId), 'attr' => ['href' => 'javascript:void(0)', 'onclick' => 'addBrandReqForm(0)', 'class' => 'link']]);
                            echo HtmlHelper::getFieldHtml($frm, 'ptc_prodcat_id', 6, ['id' => 'ptc_prodcat_id'], '', '', ['label' => FatApp::getConfig('CONF_PRODUCT_CATEGORY_REQUEST_APPROVAL', FatUtility::VAR_INT, 0) ? Labels::getLabel('FRM_REQUEST_FOR_CATEGORY', $langId) : Labels::getLabel('FRM_ADD_CATEGORY', $langId), 'attr' => ['href' => 'javascript:void(0)', 'onclick' => 'addCategoryReqForm(0)', 'class' => 'link']]);
                            echo HtmlHelper::getFieldHtml($frm, 'product_model', 6);
                            $fld = $frm->getField('product_warranty');
                            if (null !== $fld) {
                            ?>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <?php
                                        $warrantTypes = Product::getWarrantyUnits($langId);
                                        ?>
                                        <label class="form-label"><?php echo $fld->getCaption(); ?></label>
                                        <div class="input-group">
                                            <?php echo $fld->getHtml(); ?>
                                            <div class="input-group-append">
                                                <button type="button"
                                                    class="btn btn-input btn-outline-gray dropdown-toggle warrantyTypeButtonJs"
                                                    data-bs-toggle="dropdown" data-bs-auto-close="outside"
                                                    aria-haspopup="true" aria-expanded="true">
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
                        <div class="card-head dropdown-toggle-custom show" data-bs-toggle="collapse"
                            data-bs-target="#inventory-block1" aria-expanded="false" aria-controls="inventory-block1">
                            <div class="card-head-label">
                                <h3 class="card-head-title"><?php echo Labels::getLabel('NAV_INVENTORY', $langId); ?>
                                </h3>
                                <span
                                    class="text-muted"><?php echo Labels::getLabel('MSG_SET_UP_NEW_INVENTORY', $langId); ?></span>
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
                                        <?php echo HtmlHelper::getFieldHtml($frm, 'selprod_comments', 12); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } else { ?>
                    <div class="card card-toggle" id="variants-options">
                        <div class="card-head dropdown-toggle-custom collapsed" data-bs-toggle="collapse"
                            data-bs-target="#stock-block1" aria-expanded="false" aria-controls="stock-block1">
                            <div class="card-head-label">
                                <h3 class="card-head-title">
                                    <?php echo Labels::getLabel('NAV_VARIANTS_&_OPTIONS', $langId); ?>
                                </h3>
                                <span
                                    class="text-muted"><?php echo Labels::getLabel('MSG_CUSTOMIZE_PRODUCT_VARIENTS_INCLUDING_SIZE_COLOR_ETC', $langId); ?></span>
                            </div>
                            <div class="card-toolbar"> <i class="dropdown-toggle-custom-arrow"></i></div>
                        </div>
                        <div class="card-body collapse" id="stock-block1">
                            <div class="js-scrollable table-wrap table-responsive">
                                <table class="table listingTableJs" id="variantsJs" data-autoColumnWidth="0">
                                    <thead class="tableHeadJs">
                                        <tr>
                                            <th width="40%"><?php echo Labels::getLabel('FRM_OPTIONS', $langId) ?></th>
                                            <th width="45%"><?php echo Labels::getLabel('FRM_OPTION_VALUES', $langId) ?>
                                            </th>
                                            <th class="align-right" width="15%">
                                                <?php echo Labels::getLabel('LBL_ACTION_BUTTONS', $langId) ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $optionCount = count($productOptions);
                                        for ($i = 0; $i <= (1 > $optionCount ? 0 : $optionCount - 1); $i++) {
                                            echo getVariantUiTr($langId, $i, ($productOptions[$i] ?? []));
                                        }
                                        ?>
                                    </tbody>
                                </table>
                                <div class="separator separator-dashed my-4"></div>
                                <div class="form-group px-4">
                                    <div class="row">
                                        <div class="col">
                                            <label
                                                class="label"><?php echo Labels::getLabel('LBL_PRODUCT_HAS_SAME_EAN/UPC_CODE_FOR_ALL_VARIENTS', $langId); ?></label>
                                        </div>
                                        <div class="col-auto">
                                            <?php
                                            $fld = $frm->getField('upc_type');
                                            HtmlHelper::configureSwitchForRadio($fld);
                                            $fld->addOptionListTagAttribute('class', 'list-radio');
                                            $fld->addFieldTagAttribute('onchange', 'upcType()');
                                            $fld->addFieldTagAttribute('class', 'upc_type');
                                            echo $fld->getHtml();
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                <div id="variantsListJs"></div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                <div class="card card-toggle" id="media">
                    <div class="card-head dropdown-toggle-custom collapsed" data-bs-toggle="collapse"
                        data-bs-target="#stock-block2" aria-expanded="false" aria-controls="stock-block2">
                        <div class="card-head-label">
                            <h3 class="card-head-title"><?php echo Labels::getLabel('NAV_MEDIA', $langId); ?></h3>
                            <span
                                class="text-muted"><?php echo Labels::getLabel('MSG_MANAGE_YOUR_PRODUCT_IMAGES_GALLERY', $langId); ?></span>
                        </div>
                        <div class="card-toolbar">
                            <i class="dropdown-toggle-custom-arrow"></i>
                        </div>
                    </div>
                    <div class="collapse" id="stock-block2">
                        <div class="card-body">
                            <div>
                                <div class="d-flex justify-content-between mb-3">
                                    <h6 class="h6 "><?php echo Labels::getLabel('LBL_UPLOADED_MEDIA', $langId); ?></h6>
                                    <a href="javascript:void(0)" onclick="imageForm();"
                                        class="link"><?php echo Labels::getLabel('LBL_ADVANCED_MEDIA', $langId); ?></a>
                                </div>
                                <ul class="uploaded-stocks" id="productDefaultImagesJs">
                                    <li class="browse unsortableJs"><button type="button" class="browse-button"
                                            onclick="$('#hiddenMediaFrmFileJs').click();">
                                            <strong>
                                                <?php echo Labels::getLabel('LBL_UPLOAD_IMAGES(S)', $langId); ?></strong>
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
                </div>
                <div class="card card-toggle" id="specifications">
                    <div class="card-head dropdown-toggle-custom collapsed" data-bs-toggle="collapse"
                        data-bs-target="#specifications-block" aria-expanded="false"
                        aria-controls="specifications-block">
                        <div class="card-head-label">
                            <h3 class="card-head-title"><?php echo Labels::getLabel('NAV_SPECIFICATIONS', $langId); ?>
                            </h3>
                            <span
                                class="text-muted"><?php echo Labels::getLabel('MSG_MANAGE_PRODUCT_RELATED_SPECIFICATIONS', $langId); ?></span>
                        </div>
                        <div class="card-toolbar"> <i class="dropdown-toggle-custom-arrow"></i></div>
                    </div>
                    <div class="collapse" id="specifications-block">
                        <div class="card-body">
                            <div id="specificationsFormJs">
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
                                            <button type="button" id="btnClearSpecJs"
                                                class="btn btn-outline-brand btn-wide" onclick="clearProdSpecForm()"
                                                data-updateLbl="<?php echo Labels::getLabel('BTN_UPDATE', $langId); ?>"
                                                data-addLbl="<?php echo Labels::getLabel('BTN_ADD', $langId); ?>">
                                                <?php echo Labels::getLabel('BTN_CLEAR', $langId); ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="specificationsListSeprJs" class="separator separator-dashed my-4 hidden"></div>
                            <div id="specificationsListJs"></div>
                        </div>
                    </div>
                </div>
                <div class="card card-toggle" id="tax-shipping">
                    <div class="card-head dropdown-toggle-custom" data-bs-toggle="collapse"
                        data-bs-target="#stock-block4" aria-expanded="true" aria-controls="stock-block4">
                        <div class="card-head-label">
                            <h3 class="card-head-title">
                                <?php echo Labels::getLabel('NAV_TAX_AND_SHIPPING', $siteLangId); ?>
                            </h3>
                            <span
                                class="text-muted"><?php echo Labels::getLabel('MSG_SETUP_TAX_AND_SHIPPING_INFORMATION_OF_THE_PRODUCT', $siteLangId); ?></span>
                        </div>
                        <div class="card-toolbar"> <i class="dropdown-toggle-custom-arrow"></i></div>
                    </div>
                    <div class="collapse show" id="stock-block4">
                        <div class="card-body">
                            <div class="row">
                                <?php
                                echo HtmlHelper::getFieldHtml($frm, 'ptt_taxcat_id', 12, ['id' => 'ptt_taxcat_id']);
                                echo HtmlHelper::getFieldHtml($frm, 'product_fulfillment_type', 6, ['id' => 'product_fulfillment_type']);
                                echo HtmlHelper::getFieldHtml($frm, 'product_ship_package', 6);
                                echo HtmlHelper::getFieldHtml($frm, 'product_weight', 6);
                                echo HtmlHelper::getFieldHtml($frm, 'product_weight_unit', 6);
                                echo HtmlHelper::getFieldHtml($frm, 'ps_from_country_id', 6, ['id' => 'ps_from_country_id']);
                                echo HtmlHelper::getFieldHtml($frm, 'shipping_profile', 6, ['id' => 'shipping_profile']);
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card card-toggle digitalDownloadSectionJS hidden" id="digital-files">
                    <div class="card-head dropdown-toggle-custom collapsed" data-bs-toggle="collapse"
                        data-bs-target="#digital-file-block" aria-expanded="false" aria-controls="stock-block2">
                        <div class="card-head-label">
                            <h3 class="card-head-title">
                                <?php echo Labels::getLabel('NAV_DIGITAL_FILES', $siteLangId); ?></h3>
                            <span
                                class="text-muted"><?php echo Labels::getLabel('MSG_MANAGE_PRODUCT_DIGITIAL_FILES', $siteLangId); ?></span>
                        </div>
                        <?php if ($displayDigitalDownloadList) { ?>
                            <div class="card-toolbar"> <i class="dropdown-toggle-custom-arrow"></i></div>
                        <?php } ?>
                    </div>

                    <?php if ($displayDigitalDownloadList) { ?>
                        <div class="collapse" id="digital-file-block">
                            <div class="card-table">
                                <div id="digitalFilesDefaultListJs">
                                </div>
                            </div>
                        </div>
                        <?php if ($displayDigitalDownloadAddBtn) { ?>
                            <div class="card-foot">
                                <div class="row justify-content-end">
                                    <div class="col-auto">
                                        <button class="btn btn-icon btn-outline-gray btn-sm" type="button"
                                            onclick="digitalDownloadsForm(<?php echo applicationConstants::DIGITAL_DOWNLOAD_FILE; ?>);">
                                            <svg class="svg btn-icon-start" width="18" height="18">
                                                <use
                                                    xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#add">
                                                </use>
                                            </svg>
                                            <span><?php echo Labels::getLabel('BTN_DIGITAL_FILES', $langId); ?></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    <?php } else { ?>
                        <div class="card-body collapsed show">
                            <?php echo HtmlHelper::getErrorMessageHtml(Labels::getLabel('ERR_YOU_CAN_UPLOAD_DIGITAL_FILES_AFTER_SETUP.')); ?>
                        </div>
                    <?php } ?>
                </div>
                <div class="card card-toggle digitalDownloadSectionJS hidden" id="digital-links">
                    <div class="card-head dropdown-toggle-custom collapsed" data-bs-toggle="collapse"
                        data-bs-target="#digital-link-block" aria-expanded="false" aria-controls="stock-block2">
                        <div class="card-head-label">
                            <h3 class="card-head-title">
                                <?php echo Labels::getLabel('NAV_DIGITAL_LINKS', $siteLangId); ?></h3>
                            <span
                                class="text-muted"><?php echo Labels::getLabel('MSG_MANAGE_PRODUCT_DIGITIAL_LINKS', $siteLangId); ?></span>
                        </div>
                        <?php if ($displayDigitalDownloadList) { ?>
                            <div class="card-toolbar"> <i class="dropdown-toggle-custom-arrow"></i></div>
                        <?php } ?>
                    </div>

                    <?php if ($displayDigitalDownloadList) { ?>
                        <div class="collapse" id="digital-link-block">
                            <div class="card-table">
                                <div id="digitalLinksDefaultListJs">
                                </div>
                            </div>
                        </div>
                        <?php if ($displayDigitalDownloadAddBtn) { ?>
                            <div class="card-foot">
                                <div class="row justify-content-end">
                                    <div class="col-auto">
                                        <button class="btn btn-icon btn-outline-gray btn-sm" type="button"
                                            onclick="digitalDownloadsForm(<?php echo applicationConstants::DIGITAL_DOWNLOAD_LINK; ?>);">
                                            <svg class="svg btn-icon-start" width="18" height="18">
                                                <use
                                                    xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#add">
                                                </use>
                                            </svg>
                                            <span><?php echo Labels::getLabel('BTN_DIGITAL_LINKS', $langId); ?></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    <?php } else { ?>
                        <div class="card-body collapsed show">
                            <?php echo HtmlHelper::getErrorMessageHtml(Labels::getLabel('ERR_YOU_CAN_ADD_DIGITAL_LINKS_AFTER_SETUP.')); ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <div class="add-stock-column column-actions">
                <div class="sticky-top">
                    <div class="card">
                        <div class="card-body">
                            <button type="submit"
                                class="btn btn-brand btn-block"><?php echo Labels::getLabel('FRM_SAVE', $langId); ?></button>
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
                    <?php if (0 < $recordId && isset($productData['preq_submitted_for_approval']) && 1 > $productData['preq_submitted_for_approval']) { ?>
                        <div class="card">
                            <div class="card-body">
                                <a href="<?php echo UrlHelper::generateUrl('CustomProducts', 'submitForApproval', [$recordId]); ?>"
                                    class="btn btn-brand btn-block"><?php echo Labels::getLabel('FRM_SUBMIT_FOR_APPROVAL', $langId); ?></a>
                            </div>
                        </div>
                    <?php } ?>
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
                                    HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel('FRM_MARK_THIS_PRODUCT_AS_FEATURED_INFO', $langId));
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
                                    <h3 class="card-head-title">Tags</h3>
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
        </div>
    </div>
    </form>
    <table class="hidden" id="variantCloneJs">
        <?php echo getVariantUiTr($langId, -1); ?>
    </table>
    <?php echo $frm->getExternalJS();
    $imgFrm->setFormTagAttribute('class', 'hidden');
    $imgFrm->setFormTagAttribute('name', 'hiddenMediaFrm');
    $imgFrm->setFormTagAttribute('id', 'hiddenMediaFrmJs');
    $fld = $imgFrm->getField('prod_image');
    $fld->addFieldTagAttribute('onChange', "loadCropper(this)");
    $fld->addFieldTagAttribute('id', "hiddenMediaFrmFileJs");
    $fld->addFieldTagAttribute('accept', "image/*");
    $fld->addFieldTagAttribute('data-name', Labels::getLabel("FRM_PRODUCT_IMAGE", $siteLangId));
    echo $imgFrm->getFormHtml();
    ?>
</div>

<script>
    var canEditTags = <?php echo $canEditTags ? 1 : 0; ?>;
    var tagsEditErr = '<?php echo Labels::getLabel('ERR_NOT_AUTHORIZED_TO_ADD_TAGS', $langId); ?>';
    var tagifyObjs = {};
    var productOptions = <?php echo json_encode($productOptions); ?>;
    var forAllOptionsLbl = '<?php echo Labels::getLabel('FRM_FOR_ALL_OPTIONS', $langId); ?>';
    var tempImageType = '<?php echo AttachedFile::FILETYPE_CUSTOM_PRODUCT_IMAGE_TEMP; ?>';
    var typeDigitalFile = '<?php echo applicationConstants::DIGITAL_DOWNLOAD_FILE; ?>';
    var typeDigitalLink = '<?php echo applicationConstants::DIGITAL_DOWNLOAD_LINK; ?>';
    var fulfilmentTypePickup = '<?php echo Shipping::FULFILMENT_PICKUP; ?>';
    var prodTypeDigital = '<?php echo Product::PRODUCT_TYPE_DIGITAL; ?>';

    $(function() {
        $('body').addClass('isLoading');
        $('#addStock').prepend(fcom.getLoader());
        prodSpecifications();
        tagifyProducts();
        productDefaultImages();
        var langId = getCurrentFrmLangId();
        select2('product_brand_id', fcom.makeUrl('Brands', 'autoComplete', [], siteConstants.webrootfront), {
            brand_active: 1,
            langId: langId
        });
        select2('ptc_prodcat_id', fcom.makeUrl('Products', 'linksAutocomplete', [], siteConstants.webrootfront), {
            langId
        });

        select2('ptt_taxcat_id', fcom.makeUrl('products', 'autoCompleteTaxCategories', [], siteConstants.webrootfront), {
            langId
        });

        $('#addProductfrm .optionsJs').each(function(index) {
            var selectedOptionData = [];
            if (index in productOptions) {
                let optionName = productOptions[index]['option_name'];
                if (productOptions[index]['option_name'] != productOptions[index]['option_identifier']) {
                    optionName += '(' + productOptions[index]['option_identifier'] + ')';
                }
                selectedOptionData = [{
                    selected: true,
                    id: productOptions[index]['option_id'],
                    text: optionName,
                    option_is_separate_images: productOptions[index]['option_is_separate_images'],
                }]
            }
            select2($(this).attr('id'), fcom.makeUrl('Seller', 'autoCompleteOptions'), optionDataCallback,
                resetOptionValuesTag,
                resetOptionValuesTag,
                '',
                selectedOptionData
            );
            $(this).data("select2").$container.addClass("w-100");

        });


        $('#addProductfrm .optionValuesJs').each(function(index) {
            tagifyOptionValue("#" + $(this).attr('id'));
        });


        <?php if (0 < $recordId && $displayDigitalDownloadList) { ?>
            getDigitalDownloads(<?php echo applicationConstants::DIGITAL_DOWNLOAD_FILE; ?>, <?php echo $recordId; ?>);
            getDigitalDownloads(<?php echo applicationConstants::DIGITAL_DOWNLOAD_LINK; ?>, <?php echo $recordId; ?>);
        <?php } ?>
        upcType();
        document.getElementById('stock-block1').addEventListener('shown.bs.collapse', function() {
            fixTableColumnWidth();
        })
    });

    $(document).ready(function() {
        if (prodTypeDigital == $('.productTypeJs:checked').val() && 0 == $('.attachmentWithInventoryJs:checked').val()) {
            $('.digitalDownloadSectionJS').removeClass('hidden');
        } else if (!$('.digitalDownloadSectionJS').hasClass('hidden')) {
            $('.digitalDownloadSectionJS').addClass('hidden');
        }
    });

    $(document).on('change', '.attachmentWithInventoryJs', function() {
        if (prodTypeDigital == $('.productTypeJs:checked').val()) {
            if (1 == $(this).val()) {
                $('.digitalDownloadSectionJS').addClass('hidden');
            } else {
                $('.digitalDownloadSectionJS').removeClass('hidden');
            }
        }
    });
</script>


<?php
function getVariantUiTr($langId, $i, $productOption = [])
{
    $deleteClass = $i == 0 ? 'hidden' : '';
    $optionLabel = Labels::getLabel('FRM_SELECT_OPTION', $langId);
    $confWebUrl = CONF_WEBROOT_URL;

    $tagData = [];
    if (!empty($productOption)) {
        foreach ($productOption['optionValues'] as $key => $name) {
            $tagData[] = ['id' => $key, 'value' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8')];
        }
    }
    $tagData = json_encode($tagData);

    return <<<HTML
    <tr class="rowJs">
        <td width="30%">
            <select class="optionsJs" id="options$i" name="options[]" class="form-control" placeholder="$optionLabel"> 
            </select>
        </td>
        <td width="50%">
            <input class="form-tagify optionValuesJs" id="optionValues$i" data-index="$i" name="optionValues[]" value='$tagData'>
        </td>
        <td class="align-right" width="20%">
            <ul class="actions">
                <li class="$deleteClass optionsDeleteJs">
                    <a href="javascript:void(0)" class="">
                        <svg class="svg" width="18" height="18">
                            <use xlink:href="{$confWebUrl}images/retina/sprite-actions.svg#delete">
                            </use>
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="javascript:void(0)" class="optionsAddJs">
                        <svg class="svg" width="18" height="18">
                            <use xlink:href="{$confWebUrl}images/retina/sprite-actions.svg#add">
                            </use>
                        </svg>
                    </a>
                </li>
            </ul>
        </td> 
    </tr>
    HTML;
}
?>