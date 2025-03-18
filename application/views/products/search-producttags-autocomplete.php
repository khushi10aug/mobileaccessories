<?php $recordFound = false; ?>
<?php if (!empty($suggestions['tags'])) {
    $recordFound = true; ?>
    <ul class="text-suggestions">
        <?php foreach ($suggestions['tags'] as $tags) { ?>
            <li class="text-suggestions-item">
                <a class="text-suggestions-link" href="javascript:void(0)" onclick="searchTags(this)" data-txt="<?php echo $tags['tag_name']; ?>"><span class=""><?php echo str_ireplace($keyword, "<b>$keyword</b>", $tags['tag_name']); ?></span></a>
            </li>
        <?php } ?>
    </ul>
<?php } else if (!empty($suggestions['products'])) {
    $recordFound = true; ?>
    <ul class="text-suggestions">
        <?php foreach ($suggestions['products'] as $product) { ?>
            <li class="text-suggestions-item"><a class="text-suggestions-link" href="<?php echo UrlHelper::generateUrl('Products', 'view', [$product['selprod_id']]); ?>"><span class=""><?php echo str_ireplace($keyword, "<b>$keyword</b>", $product['selprod_title']); ?></span></a></li>
        <?php } ?>
    </ul>
<?php } ?>
<?php if (!empty($suggestions['brands']) || !empty($suggestions['categories'])) { ?>
    <div class="matched">
        <?php if (!empty($suggestions['brands'])) {
            $recordFound = true; ?>
            <h6 class="suggestions-title"><?php echo Labels::getLabel('LBL_Matching_Brands', $siteLangId); ?></h6>
            <ul class="text-suggestions matched-brands">
                <?php
                foreach ($suggestions['brands'] as $brandId => $brandName) { ?>
                    <li class="text-suggestions-item">
                        <a class="text-suggestions-link" href="<?php echo UrlHelper::generateUrl('Brands', 'view', [$brandId]); ?>"><span class=""><?php echo str_ireplace($keyword, "<b>$keyword</b>", $brandName); ?></span></a>
                    </li>
                <?php
                } ?>
            </ul>
        <?php } ?>

        <?php if (!empty($suggestions['categories'])) {
            $recordFound = true; ?>
            <h6 class="suggestions-title"><?php echo Labels::getLabel('LBL_Matching_Categories', $siteLangId); ?></h6>
            <ul class="text-suggestions matched-category">
                <?php foreach ($suggestions['categories'] as $catId => $categoryName) { ?>
                    <li class="text-suggestions-item">
                        <a class="text-suggestions-link" href="<?php echo UrlHelper::generateUrl('Category', 'view', [$catId]); ?>">
                            <span class="text-suggestions-span"><?php echo str_ireplace($keyword, "<b>$keyword</b>", $categoryName); ?></span></a>
                    </li>
                <?php  } ?>
            </ul>
        <?php } ?>
    </div>
<?php } ?>
<?php
if (empty($keyword)) {
    if (!empty($recentSearchArr)) { ?>
        <ul class="history-suggestions search-history--js">
            <li class="history-suggestions-item">
                <h6 class="suggestions-title"><?php echo Labels::getLabel('LBL_Recent_Searches', $siteLangId) ?></h6>
                <button class="btn btn-link clear-all clearSearch-js" type="button"><?php echo Labels::getLabel('LBL_Clear_ALL', $siteLangId) ?></button>
            </li>
            <?php foreach ($recentSearchArr as $keyword) { ?>
                <li class="recent-search" data-keyword="<?php echo $keyword; ?>">
                    <div class="recent-search__cross">
                        <a href="javascript:void(0)" onclick="clearSearchKeyword(this)" class="close-layer close-layer-sm" data-keyword="<?php echo $keyword; ?>"></a>
                    </div>

                    <a class="recent-search__link recentSearch-js" href="javascript:void(0)"><span class=""><?php echo $keyword; ?></span></a>
                    <div class="recent-search__arrow recentSearch-js">
                        <svg class="svg">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#arrow-top"></use>
                        </svg>
                    </div>
                </li>
            <?php } ?>
        </ul>
<?php } /* else {
        $message = Labels::getLabel('LBL_No_Records_Found', $siteLangId);
        $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId, 'message' => $message));
    } */
} ?>