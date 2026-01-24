<?php defined('SYSTEM_INIT') or die('Invalid Usage');
$frm->setFormTagAttribute('class', 'form');
$displayDigitalDownloadAddBtn = false;
$displayDigitalDownloadList = false;
$shippingProfileId = $frm->getField('shipping_profile') ? $frm->getField('shipping_profile')->value : 0;
$prodFulfilementType = $productData['product_fulfillment_type'] ?? 0;

if (0 < $recordId) {
    $displayDigitalDownloadAddBtn = $productData['product_type'] == Product::PRODUCT_TYPE_DIGITAL && $frm->getField('product_type')->value == Product::PRODUCT_TYPE_DIGITAL  && 1 > $productData['product_seller_id'];
    $displayDigitalDownloadList = $displayDigitalDownloadAddBtn && 1 > $productData['product_attachements_with_inventory'];
}

?>
<main class="main mainJs" <?php echo CommonHelper::getLayoutDirection() != $formLayout ? 'dir="' . $formLayout . '"' : ''; ?>>
    <div class="container">

    <a style="float:left;" id="backBtn" class="btn-back" href="javascript:void(0);">
        <svg class="svg" width="24" height="24">
            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg#back">
            </use>
        </svg>
    </a>

        <?php
        $this->includeTemplate('_partial/header/header-breadcrumb.php', [], false);
        $frm->setFormTagAttribute('id', 'addProductfrm');
        $frm->setFormTagAttribute('onsubmit', 'setup($(\'#addProductfrm\'));return false;');
        echo $frm->getFormTag(); ?>
        <?php if (1 > $tourStep) { ?>
            <div class="add-stock" id="productWrapper">
                <?php require_once(CONF_THEME_PATH . 'products/form-left.php'); ?>
                <?php require_once(CONF_THEME_PATH . 'products/form-right.php'); ?>
            </div>
        <?php } else { ?>
            <div class="onboarding">
                <?php require_once(CONF_THEME_PATH . 'getting-started/left-nav.php'); ?>
                <div class="onboarding-main">
                    <?php require_once(CONF_THEME_PATH . 'products/form-right.php'); ?>
                </div>
            </div>
        <?php } ?>

        </form>
    </div>
    <table id="variantCloneJs" class="hide">
        <?php $this->includeTemplate('products/get-variant-row.php', ['langId' => $langId, 'index' => -1, 'hasInventory' => $hasInventory]); ?>
    </table>
    <?php echo $frm->getExternalJS();
    $imgFrm->setFormTagAttribute('class', 'hide');
    $imgFrm->setFormTagAttribute('name', 'hiddenMediaFrm');
    $imgFrm->setFormTagAttribute('id', 'hiddenMediaFrmJs');
    $fld = $imgFrm->getField('prod_image');
    $fld->addFieldTagAttribute('onChange', "loadCropper(this)");
    $fld->addFieldTagAttribute('id', "hiddenMediaFrmFileJs");
    $fld->addFieldTagAttribute('accept', "image/*");
    $fld->addFieldTagAttribute('data-name', Labels::getLabel("FRM_PRODUCT_IMAGE", $siteLangId));
    echo $imgFrm->getFormHtml();

    ?>
    <script type="text/javascript">
        var canEditTags = <?php echo $canEditTags ? 1 : 0; ?>;
        var tagsEditErr = '<?php echo Labels::getLabel('ERR_NOT_AUTHORIZED_TO_ADD_TAGS', $langId); ?>';
        var tagifyObjs = {};
        var productOptions = <?php echo json_encode($productOptions); ?>;
        var forAllOptionsLbl = '<?php echo Labels::getLabel('FRM_FOR_ALL_OPTIONS', $langId); ?>';
        var tempImageType = '<?php echo AttachedFile::FILETYPE_PRODUCT_IMAGE_TEMP; ?>';
        var typeDigitalFile = '<?php echo applicationConstants::DIGITAL_DOWNLOAD_FILE; ?>';
        var typeDigitalLink = '<?php echo applicationConstants::DIGITAL_DOWNLOAD_LINK; ?>';
        var fulfilmentTypePickup = '<?php echo Shipping::FULFILMENT_PICKUP; ?>';
        var shippingProfileId = '<?php echo $shippingProfileId; ?>';
        var prodFulfilementType = '<?php echo $prodFulfilementType; ?>';
        var prodTypeDigital = '<?php echo Product::PRODUCT_TYPE_DIGITAL; ?>';

        $(function() {
            $('.mainJs').addClass('isLoading');
            $('#productWrapper').prepend(fcom.getLoader());
            prodSpecifications();
            tagifyProducts();
            productDefaultImages();
            var langId = getCurrentFrmLangId();
            select2('product_brand_id', fcom.makeUrl('Brands', 'autoComplete'), {
                brand_active: 1,
                langId: langId
            });
            select2('product_cbrand_id', fcom.makeUrl('CompatibleBrands', 'autoComplete'), {
                brand_active: 1,
                langId: langId
            });            
            $('#ptc_prodcat_id').data('closeOnSelect', false);
            select2('ptc_prodcat_id', fcom.makeUrl('ProductCategories', 'autoComplete'), {
                langId
            });
            select2('ptt_taxcat_id', fcom.makeUrl('TaxCategories', 'autoComplete'), {
                langId
            });
            select2('product_ship_package', fcom.makeUrl('shippingPackages', 'autoComplete'), {
                langId
            });
            select2('ps_from_country_id', fcom.makeUrl('Countries', 'autoComplete'), {
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
                select2($(this).attr('id'), fcom.makeUrl('Options', 'autoComplete'), optionDataCallback,
                    resetOptionValuesTag,
                    resetOptionValuesTag,
                    '',
                    selectedOptionData
                );
                $(this).data("select2").$container.addClass("custom-select2-width");

            });


            $('#addProductfrm .optionValuesJs').each(function(index) {
                tagifyOptionValue("#" + $(this).attr('id'));
            });

            getShippingProfileOptions(<?php echo $frm->getField('product_seller_id')->value; ?>);

            <?php if ($isProductAddedByAdmin  && !$isSelProdCreatedBySeller) { ?>
                select2('product_seller_id', fcom.makeUrl('Users', 'autoComplete'), {
                    joinShop: 1,
                    user_is_supplier: 1,
                    langId
                }, function(e) {
                    getShippingProfileOptions(e.params.args.data.id)
                }, function () {
                    getShippingProfileOptions(0)
                });
            <?php } else { ?>
                $('select[name=\'product_seller_id\']').attr('disabled', true);
            <?php } ?>
            <?php if (0 < $recordId && $displayDigitalDownloadList) { ?>
                getDigitalDownloads(<?php echo applicationConstants::DIGITAL_DOWNLOAD_FILE; ?>, <?php echo $recordId; ?>);
                getDigitalDownloads(<?php echo applicationConstants::DIGITAL_DOWNLOAD_LINK; ?>, <?php echo $recordId; ?>);
            <?php } ?>
            upcType();
            fixTableColumnWidth();
        });

        $(document).ready(function() {
            var attachmentWithInventory = $('.attachmentWithInventoryJs:checked').val();
            <?php if (FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) { ?>
                var attachmentWithInventory = $('.attachmentWithInventoryJs').val();
            <?php } ?>
            if (prodTypeDigital == $('.productTypeJs').find(":selected").val() && 0 == attachmentWithInventory) {
                $('.digitalDownloadSectionJS').removeClass('hide');
            } else if (!$('.digitalDownloadSectionJS').hasClass('hide')) {
                $('.digitalDownloadSectionJS').addClass('hide');
            }
        });

        $(document).on('change', '.attachmentWithInventoryJs', function() {
            if (prodTypeDigital == $('.productTypeJs').find(":selected").val()) {
                if (1 == $(this).val()) {
                    $('.digitalDownloadSectionJS').addClass('hide');
                } else {
                    $('.digitalDownloadSectionJS').removeClass('hide');
                }
            }
        });

        $(document).ready(function() {
            $(".datePickerJs").datepicker("option", {
                beforeShow: function(input, inst) {                   
                    $(input).css({    
                        "position": "relative",                   
                        "z-index": 1020
                    });
                },
                onClose: function () { $(".datePickerJs").css({ 'z-index': 0 } ); }
            });
        });
    </script>
</main>