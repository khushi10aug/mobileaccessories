<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

HtmlHelper::formatFormFields($frm);
$fld = $frm->getField('use_shop_policy');
$fld->setFieldTagAttribute('class', "fieldsVisibilityJs");

if ($productOptions) {
    $colVal = 12;
    if ((count($productOptions) % 2) != 0) {
        end($productOptions);
        $colVal = 6;
    }

    foreach ($productOptions as $key => $option) {
        $option_name = ($option['option_name'] != '') ? $option['option_name'] : $option['option_identifier'];
        $fld = $frm->getField('selprodoption_optionvalue_id[' . $option['option_id'] . ']');
        $fld->developerTags['colWidthValues'] = [null, 6, null, null];
    }

    $fld = $frm->getField('selprod_user_shop_name');
    $fld->developerTags['colWidthValues'] = [null, $colVal, null, null];
}


$fld = $frm->getField('selprod_threshold_stock_level');
if (null != $fld) {
    $fld->developerTags['colWidthValues'] = [null, '6', null, null];
    HtmlHelper::addFieldLabelInfo($frm, 'selprod_threshold_stock_level', Labels::getLabel('MSG_ALERT_STOCK_LEVEL_HINT_INFO', $siteLangId), ['id' => 'selprod_threshold_stock_level']);
}

$fld = $frm->getField('selprod_cost');
$fld->developerTags['colWidthValues'] = [null, '6', null, null];

$fld = $frm->getField('selprod_price');

$selPriceTitle = (FatApp::getConfig("CONF_PRODUCT_INCLUSIVE_TAX", FatUtility::VAR_INT, 0)) ? Labels::getLabel('LBL_THIS_PRICE_IS_INCLUDING_THE_TAX_RATES.', $siteLangId) : Labels::getLabel('LBL_THIS_PRICE_IS_EXCLUDING_THE_TAX_RATES.', $siteLangId);
$selPriceTitle .= ' ' . Labels::getLabel('LBL_MIN_SELLING_PRICE', $siteLangId) . ' ' . CommonHelper::displayMoneyFormat($productMinSellingPrice, true, true);
$fld->developerTags['colWidthValues'] = [null, '6', null, null];
HtmlHelper::addFieldLabelInfo($frm, 'selprod_price', $selPriceTitle);

$fld = $frm->getField('selprod_stock');
if (null != $fld) {
    $fld->developerTags['colWidthValues'] = [null, '6', null, null];
}

$fld = $frm->getField('selprod_sku');
if (null != $fld) {
    $fld->developerTags['colWidthValues'] = [null, '6', null, null];
    HtmlHelper::addFieldLabelInfo($frm, 'selprod_sku', Labels::getLabel('LBL_STOCK_KEEPING_UNIT', $siteLangId));
}

$fld = $frm->getField('selprod_available_from');
$fld->developerTags['colWidthValues'] = [null, '6', null, null];

$fld = $frm->getField('selprod_active');
HtmlHelper::configureSwitchForCheckbox($fld);
if (null != $fld) {
    $fld->developerTags['noCaptionTag'] = true;
    $fld->developerTags['colWidthValues'] = [null, '12', null, null];
}

$fld = $frm->getField('selprod_min_order_qty');
if (null != $fld) {
    $fld->developerTags['colWidthValues'] = [null, '6', null, null];
}
$fld = $frm->getField('selprod_fulfillment_type');
if (null != $fld) {
    $fld->developerTags['colWidthValues'] = [null, '6', null, null];
}

$fld = $frm->getField('selprod_return_age');
if (null != $fld) {
    $fld->developerTags['colWidthValues'] = [null, '6', null, null];
}

$fld = $frm->getField('selprod_cancellation_age');
if (null != $fld) {
    $fld->developerTags['colWidthValues'] = [null, '6', null, null];
}

$fld = $frm->getField('selprod_max_download_times');
if (null != $fld) {
    $fld->developerTags['colWidthValues'] = [null, '6', null, null];
}

$fld = $frm->getField('selprod_download_validity_in_days');
if (null != $fld) {
    $fld->developerTags['colWidthValues'] = [null, '6', null, null];
}

$fld = $frm->getField('selprod_condition');
if (null != $fld) {
    $fld->developerTags['colWidthValues'] = [null, '6', null, null];
}

$fld = $frm->getField('use_shop_policy');
if (null != $fld) {
    HtmlHelper::configureSwitchForCheckbox($fld);
    $fld->developerTags['noCaptionTag'] = true;
    $fld->developerTags['colWidthValues'] = [null, '12', null, null];
}

$fld = $frm->getField('selprod_cod_enabled');
if (null != $fld) {
    $fld->developerTags['colWidthValues'] = [null, '6', null, null];
    $fld->setWrapperAttribute('class', 'selprod_cod_enabled_fld');
}

$fld = $frm->getField('selprod_fulfillment_type');
if (null != $fld) {
    $fld->developerTags['colWidthValues'] = [null, '6', null, null];
}

$fld = $frm->getField('selprod_cart_type');
if (null != $fld) {
    $fld->developerTags['colWidthValues'] = [null, '6', null, null];
}

$fld = $frm->getField('selprod_hide_price');
if (null != $fld) {
    HtmlHelper::configureSwitchForCheckbox($fld);
}

$fld = $frm->getField('selprod_subtract_stock');
if (null != $fld) {
    HtmlHelper::configureSwitchForCheckbox($fld);
    $fld->developerTags['noCaptionTag'] = true;
    $fld->developerTags['colWidthValues'] = [null, '12', null, null];
    $fld->developerTags['noCaptionTag'] = true;
}

$fld = $frm->getField('selprod_track_inventory');
if (null != $fld) {
    HtmlHelper::configureSwitchForCheckbox($fld);
    $fld->developerTags['noCaptionTag'] = true;
    $fld->developerTags['colWidthValues'] = [null, '12', null, null];
    $fld->addFieldtagAttribute('id', 'selprod_track_inventory');
}

$fld = $frm->getField('selprod_url_keyword');
if (null != $fld) {
    $fld->setFieldTagAttribute('id', "urlrewrite_custom");
    $fld->htmlAfterField = '<span class="form-text text-muted">' . HtmlHelper::seoFriendlyUrl(UrlHelper::generateFullUrl('Products', 'View', array($recordId), CONF_WEBROOT_FRONT_URL)) . '</span>';
    $fld->setFieldTagAttribute('onkeyup', "getUniqueSlugUrl(this,this.value,$recordId)");
}

$formTitle = Labels::getLabel('LBL_SELLER_INVENTORY_SETUP', $siteLangId);

if (!empty($showSellerProductMetaTab)) {
    $frm->setFormTagAttribute('data-onclear', 'editSellerProductInventory(' . (int) $recordId . ')');
    $generalTab = [
        'attr' => [
            'href' => 'javascript:void(0);',
            'onclick' => 'editSellerProductInventory(' . (int) $recordId . ');',
            'title' => Labels::getLabel('LBL_GENERAL', $siteLangId),
        ],
        'label' => Labels::getLabel('LBL_GENERAL', $siteLangId),
        'isActive' => true,
    ];
    $defaultFormLangId = CommonHelper::getDefaultFormLangId();
    $otherButtons = [
        [
            'attr' => [
                'href' => 'javascript:void(0)',
                'onclick' => 'selprodInventoryMetaLangForm(' . (int) $metaTagIdForSellerProduct . ', ' . (int) $defaultFormLangId . ', ' . (int) $recordId . ')',
                'title' => Labels::getLabel('LBL_META_TAG_SETUP', $siteLangId),
            ],
            'label' => Labels::getLabel('LBL_META_TAG_SETUP', $siteLangId),
            'isActive' => false,
        ],
    ];
}

require_once(CONF_THEME_PATH . '_partial/listing/form.php');
?>

<script type="text/javascript">
    $("document").ready(function() {
        <?php if ($shippedBySeller == 0) { ?>
            $(".selprod_cod_enabled_fld").hide();
        <?php } ?>
        $("#selprod_track_inventory").trigger('change');
        var includeEditor = false;
    });
</script>