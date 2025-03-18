<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$row['child_count'] = count($row['children']); 

$catCode = $parentCatCode . $row['bpcategory_id'] . '_'; ?>

<li id="<?php echo $row['bpcategory_id']; ?>" data-parent-cat-code="<?php echo $catCode; ?>" class="liJs sortableListsClosed <?php if ($row['child_count'] == 0) { ?>no-children<?php } ?>">
    <div>
        <div class="sorting-bar">
            <div class="sorting-title">
                <span class="clickable">
                    <?php echo !empty($row['bpcategory_name']) ? $row['bpcategory_name'] : $row['bpcategory_identifier']; ?>
                </span>
                <a onclick="goToBlog(<?php echo $row['bpcategory_id']; ?>)" href="javascript:void(0)" class="count badge badge-success clickable" title="<?php echo  Labels::getLabel('LBL_CATEGORY_BLOGS', $siteLangId); ?>">
                    <?php echo CommonHelper::displayBadgeCount($row['countChildBlogPosts']); ?>
                </a>
            </div>
            <div class="sorting-actions">
                <?php
                $checked = "";
                $changeStatus = applicationConstants::ACTIVE;
                if (applicationConstants::ACTIVE == $row['bpcategory_active']) {
                    $checked = 'checked';
                    $changeStatus = applicationConstants::INACTIVE;
                }

                $statusAct = ($canEdit === true) ? 'updateStatus(event,this,' . $row['bpcategory_id'] . ', ' . $changeStatus . ')' : 'return false;';
                $statusClass = ($canEdit === false) ? 'disabled' : 'statusEleJs statusEle-' . $row['bpcategory_id'];
                ?>
                <label class="switch switch-sm switch-icon">
                    <input type="checkbox" data-parent-cat-code="<?php echo $catCode; ?>" data-old-status="<?php echo $row['bpcategory_active']; ?>" value="<?php echo $row['bpcategory_id']; ?>" <?php echo $checked; ?> onclick="<?php echo $statusAct; ?>" class="<?php echo $statusClass; ?>">
                    <span class="input-helper clickable"></span>
                </label>

                <?php if ($canEdit) { ?>
                    <button onclick="editRecord(<?php echo $row['bpcategory_id']; ?>)" title="<?php echo  Labels::getLabel('LBL_Edit', $siteLangId); ?>" class="btn btn-clean btn-sm clickable">
                        <svg class="svg clickable" width="18" height="18">
                            <use class="clickable" xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#edit">
                            </use>
                        </svg>
                    </button>
                    <button title="<?php echo  Labels::getLabel('LBL_DELETE', $siteLangId); ?>" onclick="deleteRecord(<?php echo $row['bpcategory_id']; ?>)" class="btn btn-clean btn-sm clickable">
                        <svg class="svg clickable" width="18" height="18">
                            <use class="clickable" xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#delete">
                            </use>
                        </svg>
                    </button>
                <?php } ?>
            </div>
        </div>
        <?php if ($row['child_count'] > 0) { ?>
            <span class="sortableListsOpener clickable"><i class="fa fa-plus clickable sort-icon cat<?php echo $row['bpcategory_id']; ?>-js" onclick="displaySubCategories(this)"></i></span>
        <?php } ?>
    </div>
</li>