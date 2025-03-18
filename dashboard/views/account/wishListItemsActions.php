<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$uwlist_id = $uwlist_id ?? 0;
if (isset($products) && 0 < count($products)) {
    $function = (true == $isWishList) ? 'removeSelectedFromWishlist(' . $uwlist_id . ', event)' : 'removeSelectedFromFavtlist(event)';
    ?>
    <ul class="wishlist-toolbars">
        <li title="<?php echo Labels::getLabel('LBL_SELECT_ALL_ITEMS', $siteLangId); ?>" data-bs-toggle="tooltip"
            data-bs-placement="top">
            <label class="btn btn-outline-gray checkbox checkbox-inline select-all">
                <input class="select-all selectAll-js" type="checkbox" onclick="selectAll($(this));">
                <span> <?php echo Labels::getLabel('LBL_SELECT_ALL', $siteLangId); ?></span>
            </label>
        </li>

        <?php if (true == $isWishList) { ?>
            <li title="<?php echo Labels::getLabel('LBL_MOVE_TO_OTHER_WISHLIST', $siteLangId); ?>" data-bs-toggle="tooltip"
                data-bs-placement="top">
                <button class="btn btn-outline-gray btn-icon formActionBtn-js disabled"
                    onclick="viewWishList(0, this, event, <?php echo $uwlist_id; ?>);">
                    <svg class="svg btn-icon-start" width="18" height="18">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#move">
                        </use>
                    </svg>
                    <span class="btn-txt"><?php echo Labels::getLabel('LBL_MOVE', $siteLangId); ?></span>
                </button>
            </li>
        <?php } ?>
        <?php if (1 > FatApp::getConfig('CONF_HIDE_PRICES', FatUtility::VAR_INT, 0)) { ?>
            <li title='<?php echo Labels::getLabel('LBL_MOVE_TO_CART', $siteLangId); ?>' data-bs-toggle="tooltip"
                data-placement="top">
                <button class="btn btn-outline-gray btn-icon formActionBtn-js disabled"
                    onclick="addSelectedToCart(event, <?php echo ($isWishList ? 1 : 0); ?>);">
                    <svg class="svg btn-icon-start" width="18" height="18">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#cart">
                        </use>
                    </svg>
                    <span class="btn-txt"><?php echo Labels::getLabel('LBL_CART', $siteLangId); ?></span>
                </button>
            </li>
        <?php } ?>

        <li title='<?php echo Labels::getLabel('LBL_REMOVE_FROM_LIST', $siteLangId); ?>' data-bs-toggle="tooltip"
            data-placement="top">
            <button class="btn btn-outline-gray btn-icon formActionBtn-js disabled" onclick="<?php echo $function; ?>">
                <svg class="svg btn-icon-start" width="18" height="18">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#delete">
                    </use>
                </svg>
                <span class="btn-txt"><?php echo Labels::getLabel('LBL_REMOVE', $siteLangId); ?></span></button>
        </li>

        <?php if (true == $isWishList) { ?>
            <li title="<?php echo Labels::getLabel('LBL_Back', $siteLangId); ?>" data-bs-toggle="tooltip" data-placement="top">
                <button class="btn btn-outline-gray btn-icon" onclick="searchWishList()">
                    <svg class="svg btn-icon-start" width="18" height="18">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#back">
                        </use>
                    </svg>
                    <span class="txt"><?php echo Labels::getLabel('LBL_Back', $siteLangId); ?></span></button>
            </li>
        <?php } ?>
    </ul>
<?php } ?>