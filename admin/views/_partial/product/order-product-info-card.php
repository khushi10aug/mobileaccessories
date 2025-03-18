<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

$prodUrl = 'javascript:void(0)';
if (isset($order['op_is_batch']) && $order['op_is_batch']) {
    $prodUrl = UrlHelper::generateUrl('Products', 'batch', array($order['op_selprod_id']), CONF_WEBROOT_FRONTEND);
    $imgSrc = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'BatchProduct', array($order['op_selprod_id'], $siteLangId, ImageDimension::VIEW_SMALL), CONF_WEBROOT_FRONTEND), CONF_IMG_CACHE_TIME, '.jpg');
    $imgOrgSrc = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'BatchProduct', array($order['op_selprod_id'], $siteLangId, ImageDimension::VIEW_ORIGINAL), CONF_WEBROOT_FRONTEND), CONF_IMG_CACHE_TIME, '.jpg');
} else {
    $selprod_product_id = $order['selprod_product_id'] ?? 0;
    if (Product::verifyProductIsValid($order['op_selprod_id']) == true) {
        $prodUrl = UrlHelper::generateUrl('Products', 'view', array($order['op_selprod_id']), CONF_WEBROOT_FRONTEND);
    }

    $imgSrc = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($selprod_product_id, ImageDimension::VIEW_SMALL, $order['op_selprod_id'], 0, $siteLangId), CONF_WEBROOT_FRONTEND), CONF_IMG_CACHE_TIME, '.jpg');
    $imgOrgSrc = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($selprod_product_id, ImageDimension::VIEW_ORIGINAL, $order['op_selprod_id'], 0, $siteLangId), CONF_WEBROOT_FRONTEND), CONF_IMG_CACHE_TIME, '.jpg');
}

$options = $order['op_selprod_options'] ?? '';
$options = !empty($options) ? explode(SellerProduct::MULTIPLE_OPTION_SEPARATOR, $options) : [];

$includeShopName = $includeShopName ?? false;
$shopName = $order['op_shop_name'] ?? '';
if (isset($order['totOrders']) && $order['totOrders'] > 1) {
    $otherInfo = Labels::getLabel('LBL_Part_combined_order', $siteLangId) . ' <a title="' . Labels::getLabel('LBL_View_Order_Detail', $siteLangId) . '" href="' . UrlHelper::generateUrl('Buyer', 'viewOrder', array($order['order_id'])) . '">' . $order['order_number'] . "</a>";
}
$date = isset($showDate) && $order['order_date_added']   ? HtmlHelper::formatDateTime($order['order_date_added']) : '';
$includeInvoiceNo = $includeInvoiceNo ?? true;

?>
<div class="product-profile">
    <div class="product-profile__thumbnail">
        <a href="<?php echo $imgOrgSrc; ?>" data-featherlight="image">
            <img <?php echo HtmlHelper::getImgDimParm(ImageDimension::TYPE_PRODUCTS, ImageDimension::VIEW_SMALL); ?> src="<?php echo $imgSrc; ?>" title="<?php echo $order['op_product_name']; ?>" alt="<?php echo $order['op_product_name']; ?>">
        </a>
    </div>
    <div class="product-profile__data">
        <?php if (true === $includeInvoiceNo) { ?>
            <div class="invoice-number">
                <i class="far fa-file-alt"></i>
                <?php echo $order['op_invoice_number']; ?>
            </div>
        <?php } ?>
        <?php if (!empty($order['op_selprod_title'])) { ?>
            <div class="title">
                <span class="d-inline-block" data-html="true" tabindex="0" data-bs-toggle="popover" data-bs-placement="top" data-bs-trigger="hover focus" data-popover-html="#options-<?php echo $order['op_selprod_id']; ?>">
                    <?php echo CommonHelper::subStringByWords($order['op_selprod_title'], 35, '...'); ?>
                </span>
            </div>
        <?php } ?>
        <?php if (true === $includeShopName) { ?>
            <div class="sold_by">
                <svg class="svg" width="20" height="20">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.yokart.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icon-store">
                    </use>
                </svg> <?php echo $shopName; ?>
            </div>
        <?php }

        if (!empty($options)) {
        ?>
            <div class="brand">
                <ul class="list-options list-options--horizontal">
                    <?php
                    foreach ($options as $option) {
                        $option = explode(SellerProduct::OPTION_NAME_SEPARATOR, $option);
                        if (empty(array_filter($option))) {
                            continue;
                        }
                        echo '<li class="list-options-item">                                   
                                    <span class="value">' . trim($option[1]) . '</span>
                                </li>';
                    }
                    ?>
                </ul>
            </div>
        <?php } ?>
        <div class="hidden" id="options-<?php echo $order['op_selprod_id']; ?>">
            <p><strong><?php echo $order['op_selprod_title']; ?></strong></p>
            <?php if (!empty($options)) {
            ?>
                <ul class="list-stats list-stats-popover">
                    <li class="list-stats-item">
                        <span class="lable"><?php echo Labels::getLabel('LBL_BRAND', $siteLangId); ?>:</span>
                        <span class="value"><?php echo $order['op_brand_name']; ?></span>
                    </li>
                    <?php
                    foreach ($options as $option) {
                        $option = explode(SellerProduct::OPTION_NAME_SEPARATOR, $option);
                        if (empty(array_filter($option))) {
                            continue;
                        }
                        echo '<li class="list-stats-item">
                                <span class="lable">' . trim($option[0]) . ':</span>
                                <span class="value">' . trim($option[1]) . '</span>
                            </li>';
                    }
                    ?>
                </ul>
            <?php } ?>
        </div>
    </div>
</div>