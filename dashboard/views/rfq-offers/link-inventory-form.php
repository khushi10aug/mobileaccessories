<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
HtmlHelper::formatFormFields($frm);

$frm->setFormTagAttribute('data-onclear', 'linkInventoryForm(' . $rfqId . ')');
$frm->setFormTagAttribute('class', 'form modalFormJs');
$frm->setFormTagAttribute('onsubmit', 'linkInventory(this); return(false);');

$fld = $frm->getField('rfqts_selprod_id');
$fld->addFieldTagAttribute('id', 'rfqSelprodIdJs');
$fld->addFieldTagAttribute('placeholder', Labels::getLabel('FRM_SELECT_INVENTORY', $siteLangId));

$icon = '<svg class="svg" width="18" height="18">
            <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#add">
            </use>
        </svg>';
$lbl = Labels::getLabel('LBL_NOT_FOUND?', $siteLangId);
$productFormUrl = UrlHelper::generateUrl('products', 'form', [0, $productType]);
if (0 < $productId) {
    $productFormUrl = UrlHelper::generateUrl('Seller', 'sellerProductForm', [$productId]);
}
$link = '<a class="btn btn-outline-brand btn-sm" href="' . $productFormUrl .'" >' . $icon . Labels::getLabel('LBL_ADD_INVENTORY', $siteLangId) . '</a>';
$fld->htmlAfterField = '<div class="d-flex justify-content-between mt-3"><h6 class="text-muted">' . $lbl . '</h6>' . $link . '</div>';

$formTitle = Labels::getLabel('LBL_LINK_INVENTORY', $siteLangId);
require_once(CONF_THEME_PATH . '_partial/listing/form.php');