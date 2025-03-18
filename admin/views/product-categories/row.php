<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$childrenHtml = $childrenHtml ?? '';
$catCode = $row['prodcat_code'];
$keyword = $keyword ?? '';
?>
<li id="<?php echo $row['prodcat_id']; ?>" data-parent-cat-code="<?php echo $catCode; ?>" class="liJs <?php echo $allOpen ? 'sortableListsOpen' : 'sortableListsClosed'; ?> <?php if (isset($row['subcategory_count']) && $row['subcategory_count'] == 0) { ?>no-children<?php } ?>">
    <div>
        <div class="sorting-bar ">
            <div class="sorting-title">
                <a href="javascript:void(0);" class="link-dotted clickable" onclick="goToProducts(<?php echo $row['prodcat_id']; ?>)">
                    <?php echo !empty($keyword) ? str_ireplace($keyword, '<mark>' . $keyword . '</mark>', $row['prodcat_name']) : $row['prodcat_name']; ?>
                </a>
                <?php if (isset($row['category_products'])) { ?>
                    <span class="count badge badge-success " title="<?php echo  Labels::getLabel('LBL_Category_Products', $siteLangId); ?>"><?php echo CommonHelper::displayBadgeCount($row['category_products']); ?></span>
                <?php } ?>
            </div>
            <div class="clickable">
                <div class="sorting-actions clickable">
                    <?php
                    $statusAct = ($canEdit) ? 'updateStatus(event, this, ' . $row['prodcat_id'] . ', ' . ((int) !$row['prodcat_active']) . ')' : 'return false;';
                    $statusClass = ($canEdit) ? 'statusEleJs statusEle-' . $row['prodcat_id'] : 'disabled';
                    $checked = applicationConstants::ACTIVE == $row['prodcat_active'] ? 'checked' : '';
                    ?>
                    <label class="switch switch-sm switch-icon">
                        <input type="checkbox" data-parent-cat-code="<?php echo $catCode; ?>" data-old-status="<?php echo $row['prodcat_active']; ?>" value="<?php echo $row['prodcat_id']; ?>" <?php echo $checked; ?> onclick="<?php echo $statusAct; ?>" class="<?php echo $statusClass; ?>">
                        <span class="input-helper clickable"></span>
                    </label>

                    <?php if ($canEdit) { ?>
                        <button onclick="editRecord('<?php echo $row['prodcat_id']; ?>')" title="<?php echo  Labels::getLabel('LBL_Edit', $siteLangId); ?>" class="btn btn-clean btn-sm clickable">
                            <svg class="svg clickable" width="18" height="18">
                                <use class="clickable" xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#edit">
                                </use>
                            </svg>
                        </button>
                        <button title="<?php echo  Labels::getLabel('LBL_Delete', $siteLangId); ?>" onclick="deleteRecord(<?php echo $row['prodcat_id']; ?>)" class="btn btn-clean btn-sm clickable">
                            <svg class="svg clickable" width="18" height="18">
                                <use class="clickable" xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#delete">
                                </use>
                            </svg>
                        </button>
                    <?php } ?>
                </div>
            </div>
        </div>
        <?php if (isset($row['subcategory_count']) && $row['subcategory_count'] > 0) { ?>
            <span class="sortableListsOpener clickable">
                <i class="fa fa-plus clickable sort-icon cat<?php echo $row['prodcat_id']; ?>-js" onclick="displaySubCategories(this)"></i>
            </span>
        <?php } ?>

        <?php if ($allOpen && !empty($childrenHtml)) { ?>
            <span class="sortableListsOpener clickable">
                <i class="fa fa-minus clickable sort-icon cat<?php echo $row['prodcat_id']; ?>-js" onclick="hideItems(this)"></i>
            </span>
        <?php } ?>
    </div>
    <?php if (!empty($childrenHtml)) {
        echo $childrenHtml;
    } ?>
</li>