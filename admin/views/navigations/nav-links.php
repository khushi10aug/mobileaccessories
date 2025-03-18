<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$includeWrapper = $includeWrapper ?? true;

if ($includeWrapper) { ?>
    <ul id="childrens-<?php echo $navId; ?>" class="append-ul childrensJs">
    <?php } ?>

    <?php foreach ($arrListing as $sn => $row) {  ?>
        <li id="<?php echo $row['nlink_id']; ?>" data-nav-id="<?php echo $row['nlink_nav_id']; ?>" class="sortableListsClosed children-<?php echo $row['nlink_nav_id'] . '-' . $row['nlink_id']; ?>">
            <div>
                <div class="sorting-bar ">
                    <div class="sorting-title">
                        <span class="clickable">
                            <?php echo $row['nlink_caption']; ?>
                        </span>
                    </div>
                    <div class="clickable">
                        <div class="sorting-actions">
                            <?php if ($canEdit) { ?>
                                <button onclick="addNewLinkForm(<?php echo $row['nlink_nav_id']; ?>, <?php echo $row['nlink_id']; ?>)" title="<?php echo  Labels::getLabel('LBL_EDIT', $siteLangId); ?>" class="btn btn-clean btn-sm clickable" data-bs-toggle="tooltip" data-placement="top">
                                    <svg class="svg clickable" width="18" height="18">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#edit">
                                        </use>
                                    </svg>
                                </button>
                                <button onclick="deleteLink(<?php echo $row['nlink_nav_id']; ?>, <?php echo $row['nlink_id']; ?>)" title="<?php echo  Labels::getLabel('LBL_DELETE_RECORD', $siteLangId); ?>" class="btn btn-clean btn-sm clickable" data-bs-toggle="tooltip" data-placement="top">
                                    <svg class="svg clickable" width="18" height="18">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#delete">
                                        </use>
                                    </svg>
                                </button>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <span class="sortableListsOpener ">
                    <div class="handleJs">
                        <i class="clickable sort-icon" data-nav-id="<?php echo $row['nlink_nav_id']; ?>" data-nlink-id="<?php echo $row['nlink_id']; ?>">
                            <svg class="svg" width="18" height="18">
                                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#drag">
                                </use>
                            </svg>
                        </i>
                    </div>
                </span>
            </div>
        </li>
    <?php } ?>

    <?php if ($includeWrapper) { ?>
    </ul>
<?php } ?>