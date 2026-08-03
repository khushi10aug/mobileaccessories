<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

$title = $optionData['inv_option_name'];
$index = $optionData['inv_option_index'];
$optionId = $optionData['inv_option_id'];
$productId = FatUtility::int($product_id ?? ($optionData['product_id'] ?? 0));

$costFieldName = 'selprod_cost' . $optionId;
$sellPriceFieldName = 'selprod_price' . $optionId;
$stockFieldName = 'selprod_stock' . $optionId;
$skuFieldName = 'selprod_sku' . $optionId;

?>

<tr id="<?php echo $optionId; ?>">
    <td><?php echo $title; ?></td>
    <td data-val="<?php echo $optionData[$costFieldName]; ?>">
        <?php echo CommonHelper::displayMoneyFormat($optionData[$costFieldName], true, true); ?>
    </td>
    <td data-val="<?php echo $optionData[$sellPriceFieldName]; ?>">
        <?php echo CommonHelper::displayMoneyFormat($optionData[$sellPriceFieldName], true, true); ?>
    </td>
    <td data-val="<?php echo $optionData[$stockFieldName]; ?>">
        <?php echo $optionData[$stockFieldName]; ?>
    </td>
    <td data-val="<?php echo $optionData[$skuFieldName]; ?>">
        <?php echo $optionData[$skuFieldName]; ?>
    </td>
    <td>
        <ul class="actions">
            <?php if (0 < $productId && '' != $optionId) { ?>
                <li>
                    <a href="javascript:void(0)" onclick="optionImageForm(<?php echo $productId; ?>, '<?php echo $optionId; ?>')" title="<?php echo Labels::getLabel('LBL_IMAGES', $siteLangId); ?>">
                        <svg class="svg" width="18" height="18">
                            <use
                                xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#images">
                            </use>
                        </svg>
                    </a>
                </li>
            <?php } ?>
            <li>
                <a href="javascript:void(0)" onclick="copyRowData(this)" title="<?php echo Labels::getLabel('LBL_COPY_TO_FORM', $siteLangId); ?>">
                    <svg class="svg" width="18" height="18">
                        <use
                            xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#copy">
                        </use>
                    </svg>
                </a>
            </li>
            <?php if (0 < $selprod_id) { ?>
                <li>
                    <a href="javascript:void(0)" onclick="copyRowData(this, <?php echo $selprod_id; ?>)" title="<?php echo Labels::getLabel('LBL_EDIT', $siteLangId); ?>">
                        <svg class="svg" width="18" height="18">
                            <use
                                xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#edit">
                            </use>
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="javascript:void(0)" onclick="sellerProductDelete(this, <?php echo $selprod_id; ?>)" title="<?php echo Labels::getLabel('LBL_DELETE', $siteLangId); ?>">
                        <svg class="svg" width="18" height="18">
                            <use
                                xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#delete">
                            </use>
                        </svg>
                    </a>
                </li>
            <?php } ?>
        </ul>
    </td>
</tr>