<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
if (count($childCategories) > 0) { ?>
    <?php foreach ($childCategories as $sn => $row) {
        $catCode = $row['prodcat_code']; ?>
        <li id="<?php echo $row['prodcat_id']; ?>" data-parent-cat-code="<?php echo $catCode; ?>" class="liJs sortableListsClosed child-category <?php if ($row['subcategory_count'] == 0) { ?>no-children<?php } ?>">
            <div>
                <div class="sorting-bar">
                    <div class="sorting-title">
                    <a href="javascript:void(0);" class="link-dotted clickable" onclick="goToProducts(<?php echo $row['prodcat_id']; ?>)"><?php echo html_entity_decode($row['prodcat_name']); ?></a>
                        <span class="count badge badge-success" title="<?php echo  Labels::getLabel('LBL_Category_Products', $siteLangId); ?>">
                            <?php echo CommonHelper::displayBadgeCount($row['category_products']); ?>
                        </span>
                    </div>
                    <div class="sorting-actions">
                        <?php
                        $statusAct = ($canEdit) ? 'updateStatus(event, this, ' . $row['prodcat_id'] . ', ' . ((int) !$row['prodcat_active']) . ')' : 'return false;';
                        $statusClass = ($canEdit) ? 'statusEleJs statusEle-' . $row['prodcat_id'] : 'disabled';
                        $checked = applicationConstants::ACTIVE == $row['prodcat_active'] ? 'checked' : '';
                        ?>
                        <span class="switch switch-sm switch-icon clickable">
                            <label>
                                <input type="checkbox" data-parent-cat-code="<?php echo $catCode; ?>" data-old-status="<?php echo $row['prodcat_active']; ?>" value="<?php echo $row['prodcat_id']; ?>" <?php echo $checked; ?> onclick="<?php echo $statusAct; ?>" class="<?php echo $statusClass; ?>">
                                <span class="input-helper clickable"></span>
                            </label>
                        </span>
                        <?php if ($canEdit) { ?>
                            <button onclick="editRecord(<?php echo $row['prodcat_id']; ?>)" title="<?php echo  Labels::getLabel('LBL_Edit', $siteLangId); ?>" class="btn btn-clean btn-sm clickable">
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
                <?php if ($row['subcategory_count'] > 0) { ?>
                    <span class="sortableListsOpener"><i class="fa fa-plus clickable sort-icon cat<?php echo $row['prodcat_id']; ?>-js" onclick="displaySubCategories(this)"></i></span>
                <?php } ?>
            </div>
        </li>
    <?php } ?>
<?php } ?>