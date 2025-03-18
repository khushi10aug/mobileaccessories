<?php defined('SYSTEM_INIT') or die('Invalid Usage');
$deleteClass = $index == 0 ? 'hide' : '';
$optionLabel = Labels::getLabel('FRM_SELECT_OPTION', $langId);
$productOption = $productOption ?? [];
$tagData = [];
if (!empty($productOption)) {
    foreach ($productOption['optionValues'] as $key => $name) {
        $tagData[] = ['id' => $key, 'value' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8')];
    }
}
?>

<tr class="rowJs">
    <td>
        <select class="optionsJs" id="options<?php echo $index; ?>" name="options[]" class="form-control" placeholder="<?php echo $optionLabel; ?>"  <?php echo ($hasInventory ? 'disabled="disabled"' : ''); ?>>
        </select>
    </td>
    <td>
        <input class="form-tagify optionValuesJs" placeholder="<?php echo Labels::getLabel('FRM_TYPE_TO_SEARCH'); ?>" id="optionValues<?php echo $index; ?>" data-index="<?php echo $index; ?>" name="optionValues[]" value="<?php echo htmlspecialchars(json_encode($tagData)); ?>">
    </td>
    <?php if (false === $hasInventory) { ?>
        <td class="align-right">
            <ul class="actions">
                <li class="<?php echo $deleteClass; ?> optionsDeleteJs">
                    <a href="javascript:void(0)" class="">
                        <svg class="svg" width="18" height="18">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#delete">
                            </use>
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="javascript:void(0)" class="optionsAddJs">
                        <svg class="svg" width="18" height="18">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#add">
                            </use>
                        </svg>
                    </a>
                </li>
            </ul>
        </td>
    <?php } ?>
</tr>