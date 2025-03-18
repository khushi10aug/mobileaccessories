<?php defined('SYSTEM_INIT') or die('Invalid Usage.');  ?>

<div class="accordion-categories">
    <?php if (count($arrListing) > 0) { ?>
        <ul class="sorting-categories navigationsJs">
            <?php foreach ($arrListing as $sn => $row) {
                $subRecordCount = $row['nlink_count'];
            ?>
                <li id="parent-<?php echo $row['nav_id']; ?>" class="sortableListsClosed">
                    <div>
                        <div class="sorting-bar ">
                            <div class="sorting-title">
                                <span class="clickable">
                                    <?php echo $row['nav_name']; ?>
                                </span>
                                <span class="count badge badge-success clickable subRecordsCountJs-<?php echo $row['nav_id']; ?>" title="<?php echo  Labels::getLabel('LBL_NAV_LINKS_COUNT', $siteLangId); ?>" data-bs-toggle="tooltip" data-placement="top">
                                    <?php echo CommonHelper::displayBadgeCount($subRecordCount); ?>
                                </span>
                            </div>
                            <div class="clickable">
                                <div class="sorting-actions">
                                    <?php echo HtmlHelper::addStatusBtnHtml($canEdit, $row['nav_id'], $row['nav_active']); ?>
                                    <?php if ($canEdit) { ?>
                                        <button onclick="editRecord('<?php echo $row['nav_id']; ?>')" title="<?php echo  Labels::getLabel('LBL_Edit', $siteLangId); ?>" class="btn btn-clean btn-sm clickable" data-bs-toggle="tooltip" data-placement="top">
                                            <svg class="svg clickable" width="18" height="18">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#edit">
                                                </use>
                                            </svg>
                                        </button>
                                        <button onclick="addNewLinkForm(<?php echo $row['nav_id']; ?>)" title="<?php echo  Labels::getLabel('LBL_ADD_NEW_LINK', $siteLangId); ?>" class="btn btn-clean btn-sm clickable" data-bs-toggle="tooltip" data-placement="top">
                                            <svg class="svg clickable" width="18" height="18">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#add">
                                                </use>
                                            </svg>
                                        </button>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                        <?php $display = ($subRecordCount > 0) ? '' : 'display:none'; ?>
                        <span class="sortableListsOpener">
                            <i class="fas fa-caret-right clickable sort-icon openerJs" onclick="displaySubRows(this, 1)" data-record-id="<?php echo $row['nav_id']; ?>" style="<?php echo $display; ?>"></i>
                        </span>
                    </div>
                </li>
            <?php } ?>
        </ul>
    <?php } else {
        $this->includeTemplate('_partial/no-record-found.php');
    } ?>
</div>