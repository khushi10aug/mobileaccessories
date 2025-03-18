<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form');
$frm->developerTags['colClassPrefix'] = 'col-lg-';
$frm->developerTags['fld_default_col'] = 4;
$frm->setFormTagAttribute('id', 'rfqJs');
$frm->setFormTagAttribute('data-onclear', 'requestForQuoteFn(' . $selprodId . ')');
$frm->setFormTagAttribute('onsubmit', 'saveRfq($("#rfqJs")); return(false);');

$fld = $frm->getField('rfq_selprod_id');
$fld->addFieldTagAttribute('class', 'selprodIdJs');

$fld = $frm->getField('rfq_quantity');
$fld->addFieldTagAttribute('class', 'form-control rfqQtyJs');

$fld = $frm->getField('rfq_quantity_unit');
$fld->addFieldTagAttribute('class', 'form-select ');
$fld = $frm->getField('rfq_description');
$fld->addFieldTagAttribute('class', 'form-textarea');
$fld->addFieldTagAttribute('placeholder', Labels::getLabel('LBL_COMMENTS_FOR_SELLER*'));
$fld->htmlAfterField = '<a class="btn btn-attachment attachmentJs" >
                            <input class="attachment-file rfqDocumentJs" type="file" name="document" >
                            <svg class="svg" width="16" height="16">
                                <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#attachment">
                                </use>
                            </svg>' . Labels::getLabel('LBL_Add_ATTACHMENT') . '
                        </a><ul class="uploaded-list rfqFileNameJs"></ul>';
$fld->developerTags['col'] = 12;

$fld = $frm->getField('rfq_addr_id');
$fld->addFieldTagAttribute('class', 'addrIdJs');
// $fld->requirement->setRequired(true);

$fld = $frm->getField('rfq_delivery_date');
$fld->addFieldTagAttribute('placeholder', 'YYYY-MM-DD');
$fld->addFieldTagAttribute('class', 'rfqDeliveryDateJs form-control');

if (!$isUserLogged) {
    $fld = $frm->getField('user_name');
    $fld->addFieldTagAttribute('class', 'form-control');


    $fld = $frm->getField('user_email');
    $fld->addFieldTagAttribute('class', 'form-control');

    $fld = $frm->getField('user_phone');
    $fld->addFieldTagAttribute('class', 'form-control phoneNumberJs');
    $fld->addFieldTagAttribute('data-parent-attrs', json_encode([
        'class' => 'phonenumber--js from-group--phonefield'
    ]));
}

$fld = $frm->getField('rfq_title');
if (1 > $selprodId && null != $fld) {
    $fld->addFieldTagAttribute('class', 'form-input');
    $fld->addFieldTagAttribute('placeholder', Labels::getLabel('LBL_I_am_looking_for...', $siteLangId));
    $fld->addFieldTagAttribute('id', 'rfqItemNameJs');
}

$fld = $frm->getField('rfqts_user_id[]');
if (1 > $selprodId && null != $fld) {
    $fld->addFieldTagAttribute('class', 'form-select');
    $fld->addFieldTagAttribute('multiple', 'multiple');
    $fld->addFieldTagAttribute('style', 'width:100%;');
    $fld->addFieldTagAttribute('id', 'sellerNameJs');
    $fld->addFieldTagAttribute('placeholder', Labels::getLabel('LBL_SELECT_SELLER', $siteLangId));
    $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel('LBL_YOU_CAN_SELECT_SELLER_TO_WHOM_YOU_WANT_CONNECT_WITH.') . '</span>';
}

$fld = $frm->getField('rfq_prodcat_id');
if (1 > $selprodId && null != $fld) {
    $fld->addFieldTagAttribute('class', 'form-select');
    $fld->addFieldTagAttribute('style', 'width:100%;');
    $fld->addFieldTagAttribute('id', 'categoryJs');
    $fld->addFieldTagAttribute('placeholder', Labels::getLabel('LBL_SELECT_CATEGORY', $siteLangId));
    $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel('LBL_RFQ_ITEM_CATEGROY.') . '</span>';
}

$fld = $frm->getField('rfq_seller_linking_type');
if (1 > $selprodId && null != $fld) {
    $fld->addFieldTagAttribute('class', 'sellerLinkingJs');
}

$fld = $frm->getField('rfq_product_type');
if (null != $fld) {
    $fld->addFieldTagAttribute('class', 'form-select custom-w');
    $fld->addFieldTagAttribute('id', 'rfqProductTypeJs');
}
?>
<div class="modal-header">
    <h5 class="modal-title"><?php echo Labels::getLabel('LBL_REQUEST_A_QUOTE'); ?></h5>
</div>
<div class="modal-body">
    <?php
    echo $frm->getFormTag();
    echo $frm->getFieldHtml('rfq_product_id');
    echo $frm->getFieldHtml('rfq_addr_id');
    echo $frm->getFieldHtml('rfq_selprod_id');
    ?>
    <div class="request-quote">
        <?php if (!$isUserLogged) { ?>
            <div class="request-quote-head">
                <div class="row g-2">
                    <div class="col-lg-4">
                        <div class="form-group">
                            <label class="label">
                                <?php echo $frm->getField('user_name')->getCaption(); ?>
                                <span class="spn_must_field">*</span>
                            </label>
                            <?php echo $frm->getFieldHtml('user_name'); ?>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <label class="label">
                                <?php echo $frm->getField('user_email')->getCaption(); ?>
                                <span class="spn_must_field">*</span>
                            </label>
                            <?php echo $frm->getFieldHtml('user_email'); ?>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <label class="label">
                                <?php echo $frm->getField('user_phone')->getCaption(); ?>
                                <span class="spn_must_field">*</span>
                            </label>
                            <?php echo $frm->getFieldHtml('user_phone'); ?>
                        </div>
                    </div>
                </div>
                <div class="alert alert-solid-success alert-bold" role="alert">
                    <div class="alert-icon">
                        <svg class="svg " width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"
                            role="img" aria-label="Warning:">
                            <path
                                d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
                        </svg>
                    </div>
                    <div class="alert-text">
                        <?php echo Labels::getLabel('LBL_PROCEED_WITH_GUEST_INFORMATION_TO_PLACE_RFQ'); ?> </div>
                </div>
            </div>
        <?php
        } ?>
        <div class="request-quote-body">
            <?php if (1 > FatApp::getConfig('CONF_HIDE_SELLER_INFO', FatUtility::VAR_INT, 0) && RequestForQuote::TYPE_INDIVIDUAL == FatApp::getConfig('CONF_RFQ_MODULE_TYPE', FatUtility::VAR_INT, 0) && 0 < $selprodId) { ?>
                <div class="quote-to">
                    <span class="label"><?php echo Labels::getLabel('LBL_TO:'); ?></span>
                    <div class="avatar">
                        <div class="avatar-media">
                            <?php
                            $userImgUpdatedOn = User::getAttributesById($selprodData['shop_user_id'], 'user_updated_on');
                            $uploadedTime = AttachedFile::setTimeParam($userImgUpdatedOn);
                            $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_USER_PROFILE_IMAGE, $selprodData['shop_user_id']);
                            $profileImg = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', 'user', array($selprodData['shop_user_id'], ImageDimension::VIEW_THUMB, true), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                            ?>
                            <img src="<?php echo $profileImg; ?>" alt="<?php echo $selprodData['shop_user_name']; ?>">
                        </div>
                        <div class="avatar-detail">
                            <span class="title"><?php echo $selprodData['shop_user_name']; ?></span>
                        </div>
                    </div>
                    <div class="quote-shop">
                        <div class="shop-name"><?php echo $selprodData['shop_name']; ?></div>
                        <?php if (0 < $shopRating || 0 < $totReviews) { ?>
                            <div class="reviews">
                                <?php if (0 < $shopRating) { ?>
                                    <div class="rating">
                                        <div class="rating-count"><?php echo round($shopRating, 1); ?></div>
                                        <div class="rating-stars">
                                            <svg class="svg svg-star" width="16" height="16">
                                                <use
                                                    xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#star-yellow">
                                                </use>
                                            </svg>
                                        </div>
                                    </div>
                                <?php } ?>
                                <?php if (0 < $totReviews) { ?>
                                    <div class="reviews-count">
                                        <?php echo '(' . $totReviews . ' ' . Labels::getLabel("LBL_REVIEWS", $siteLangId) . ')'; ?>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
            <?php if (0 < $selprodId) { ?>
                <div class="row">
                    <div class="col">
                        <div class="product-profile">
                            <div class="product-profile-thumbnail">
                                <?php
                                $productTitle = $selprodData['selprod_title'];
                                $uploadedTime = AttachedFile::setTimeParam($selprodData['selprod_updated_on']);
                                $prodUrl = UrlHelper::generateUrl('Products', 'view', array($selprodId), CONF_WEBROOT_FRONTEND);
                                $imgSrc = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($selprodData['selprod_product_id'], ImageDimension::VIEW_SMALL, $selprodId, 0, $siteLangId), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');

                                $options = SellerProduct::getSellerProductOptions($selprodId, true, $siteLangId);
                                if (!empty($options)) {
                                    $options = implode(' | ', array_column($options, 'optionvalue_name'));
                                }
                                ?>
                                <a class="" href="<?php echo $prodUrl; ?>">
                                    <img src="<?php echo $imgSrc; ?>"
                                        <?php echo HtmlHelper::getImgDimParm(ImageDimension::TYPE_PRODUCTS, ImageDimension::VIEW_SMALL); ?>
                                        title="<?php echo $productTitle; ?>" alt="<?php echo $productTitle; ?>">
                                </a>
                            </div>

                            <div class="product-profile-data">
                                <a class="title" href="<?php echo $prodUrl; ?>">
                                    <?php echo $productTitle . (!empty($options) ? ' | ' . $options : ''); ?>
                                </a>
                                <div class="product-profile-category">
                                    <?php echo $selprodData['brand_name']; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } else { ?>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <label class="field_label">
                                <?php echo $frm->getField('rfq_title')->getCaption(); ?>
                                <span class="spn_must_field">*</span>
                            </label>
                            <div class="input-group">
                                <?php echo $frm->getFieldHtml('rfq_product_type'); ?>
                                <?php echo $frm->getFieldHtml('rfq_title'); ?>
                            </div>
                            <span class="form-text text-muted">
                                <?php echo Labels::getLabel('LBL_YOU_CAN_SELECT_FROM_THE_SUGGUESTION_LIST_AS_WELL.'); ?>
                            </span>
                        </div>

                    </div>
                </div>
                <div class="row">
                    <?php echo HtmlHelper::getFieldHtml($frm, 'rfq_seller_linking_type', 12); ?>
                    <?php echo HtmlHelper::getFieldHtml($frm, 'rfqts_user_id[]', 12); ?>
                </div>
                <div class="row">
                    <?php echo HtmlHelper::getFieldHtml($frm, 'rfq_prodcat_id', 8); ?>
                </div>
            <?php } ?>
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="quote-for-qty">
                        <div class="qty-wrap input-group">
                            <label class="label">
                                <?php echo Labels::getLabel('LBL_REQUIRED_QUANTITY'); ?>
                                <span class="spn_must_field">*</span>
                            </label>
                            <div class="input-group groupFieldsJs">
                                <?php echo $frm->getFieldHtml('rfq_quantity'); ?>
                                <?php echo $frm->getFieldHtml('rfq_quantity_unit'); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="form-group">
                        <div class="field-set">
                            <div class="caption-wraper d-flex justify-content-between align-items-center">
                                <label class="field_label">
                                    <?php $fld = $frm->getField('rfq_delivery_date');
                                    echo $fld->getCaption(); ?>
                                </label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover">
                                    <?php echo $frm->getFieldHtml('rfq_delivery_date'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12 addressSectionBlockJs">
                    <div class="form-group">
                        <div class="field-set">
                            <div class="caption-wraper d-flex justify-content-between align-items-center">
                                <label class="field_label">
                                    <?php echo Labels::getLabel('LBL_DELIVERY_ADDRESS'); ?><span
                                        class="spn_must_field">*</span>
                                </label>
                                <button class="link-brand link-underline" type="button"
                                    onclick="addAddress(<?php echo $selprodId; ?>);">
                                    <?php echo Labels::getLabel('LBL_ADD_NEW'); ?>
                                </button>
                            </div>
                            <div class="field-wraper">
                                <?php $formId = $frm->getFormTagAttribute('id'); ?>
                                <div class="field_cover addressSectionJs" data-form-id="<?php echo $formId ?>">
                                    <?php if ($addresses) {
                                        require CONF_THEME_PATH . 'addresses/address-element.php';
                                    } else { ?>
                                        <small class="color-light mb-2 mt-2 d-block">
                                            <?php echo Labels::getLabel("LBL_YOU_HAVN'T_ADDED_DELIVERY_ADDRESS_YET", $siteLangId); ?>
                                        </small>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="form-group">
                        <label class="field_label">
                            <?php echo Labels::getLabel('LBL_COMMENTS'); ?><span class="spn_must_field">*</span>
                        </label>
                        <?php echo $frm->getFieldHtml('rfq_description'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php echo '</form>' . $frm->getExternalJs(); ?>
</div>
<div class="modal-footer">
    <div class="buttons-group">
        <button class="btn btn-outline-gray" type="button"
            onclick="$.ykmodal.close();"><?php echo Labels::getLabel('LBL_CLOSE'); ?></button>
        <button class="btn btn-brand btn-wide submitBtnJs" type="submit"
            form="<?php echo $frm->getFormTagAttribute('id'); ?>">
            <?php echo Labels::getLabel('LBL_SUBMIT'); ?>
        </button>
    </div>
</div>
<?php if (1 > $selprodId) { ?>
    <script>
        var SELLER_LINKING_OPEN = '<?php echo RequestForQuote::SELLER_LINKING_OPEN; ?>';
        var SELLER_LINKING_ANY = '<?php echo RequestForQuote::SELLER_LINKING_ANY; ?>';
        var PRODUCT_TYPE_DIGITAL = '<?php echo Product::PRODUCT_TYPE_DIGITAL; ?>';
        $(document).ready(function() {
            var rfqItemNameSelector = $("#rfqItemNameJs");
            if (0 < rfqItemNameSelector.length) {
                rfqItemNameSelector.autocomplete({
                    'classes': {
                        "ui-autocomplete": "custom-ui-autocomplete z-index-9999"
                    },
                    'source': function(request, response) {
                        $.ajax({
                            url: fcom.makeUrl('RequestForQuotes', 'searchItemAutoComplete'),
                            data: {
                                keyword: request['term'],
                                rfq_product_type: $('#rfqProductTypeJs').val(),
                                excludeDuplicateNames: 1,
                                fIsAjax: 1
                            },
                            dataType: 'json',
                            type: 'post',
                            success: function(json) {
                                response($.map(json['results'], function(item) {
                                    return {
                                        label: item['text'],
                                        value: item['text'],
                                        id: item['id']
                                    };
                                }));
                            },
                        });
                    }
                });
            }

            var sellerNameSelector = $("#sellerNameJs");
            $(document).on('change', '.sellerLinkingJs', function() {
                if (SELLER_LINKING_OPEN == $(this).val()) {
                    sellerNameSelector.select2('val', '');
                    sellerNameSelector.val('').attr('disabled', 'disabled');
                    sellerNameSelector.parent().hide();
                } else {
                    sellerNameSelector.removeAttr('disabled');
                    sellerNameSelector.parent().show();
                }

                var reqFld = 'false';
                if (SELLER_LINKING_ANY == $(this).val()) {
                    reqFld = 'true';
                }
                sellerNameSelector.attr('data-fatreq', '{"required":' + reqFld + '}');
            });

            if (0 < sellerNameSelector.length) {
                select2('sellerNameJs', fcom.makeUrl('RequestForQuotes', 'getSellers'), function() {
                    return {
                        rfq_seller_linking_type: $('.sellerLinkingJs:checked').val()
                    };
                });

                var sellerLinkingType = $('.sellerLinkingJs:checked').val();
                if (SELLER_LINKING_OPEN == sellerLinkingType) {
                    sellerNameSelector.select2('val', '');
                    sellerNameSelector.val('').attr('disabled', 'disabled');
                    sellerNameSelector.parent().hide();
                }
            }

            var categorySelector = $("#categoryJs");
            if (0 < categorySelector.length) {
                categorySelector.select2({
                    tags: true,
                    closeOnSelect: true,
                    allowClear: true,
                    dir: langLbl.layoutDirection,
                    placeholder: categorySelector.attr('placeholder'),
                    dropdownParent: categorySelector.closest('form'),
                    ajax: {
                        url: fcom.makeUrl('Products', 'linksAutocomplete'),
                        dataType: 'json',
                        delay: 250,
                        method: 'post',
                        data: function(params) {
                            return {
                                keyword: params.term,
                                langId: <?php echo $siteLangId; ?>
                            };
                        },
                        processResults: function(data, params) {
                            return {
                                results: data.results
                            };
                        },
                        cache: true
                    },
                    minimumInputLength: 0,
                });
            }

            $(document).on('change', '#rfqProductTypeJs', function() {
                $('#rfqItemNameJs').val('');
            });

            $(document).on('change', '#rfqProductTypeJs', function() {
                if (PRODUCT_TYPE_DIGITAL == $(this).val()) {
                    $('.addressSectionBlockJs').hide();
                } else {
                    $('.addressSectionBlockJs').fadeIn();
                }
                $('#rfqItemNameJs').val('');
            });
        });
    </script>
<?php } ?>