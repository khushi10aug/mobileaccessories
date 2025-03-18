<!-- Start Navigation Bar -->
<div class="navigation-wrapper">
    <ul class="navigation">
        <?php if ($isMegaMenuEnabled == Navigations::LAYOUT_MEGA_MENU) { ?>
            <li>
                <button class="hamburger-categories dropdown-toggle-custom" type="button" data-bs-toggle="offcanvas"
                    data-bs-target="#categories-menu" aria-controls="categories-menu" onclick="openMobileMenu();">
                    <svg class="svg" width="16" height="16">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-header.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#hamburger-menu">
                        </use>
                    </svg>
                    <span class="text-nowrap"><?php echo Labels::getLabel('NAV_ALL_CATEGORIES', $siteLangId); ?></span>
                    <i class="dropdown-toggle-custom-arrow"></i>
                </button>
            </li>
            <?php
        }
        if (count($headerNavigation)) {
            foreach ($headerNavigation as $navKey => $nav) {
                if ($nav['pages']) {
                    $mainNavigation = array_slice($nav['pages'], 0, $navLinkCount);
                    foreach ($mainNavigation as $mainNavKey => $link) {
                        $catThumb = AttachedFile::getAttachment(AttachedFile::FILETYPE_CATEGORY_THUMB, $link['nlink_category_id'], 0, $siteLangId, false, 0);
                        $uploadedTime = AttachedFile::setTimeParam($catThumb['afile_updated_at']);
                        $navUrl = CommonHelper::getnavigationUrl($link['nlink_type'], $link['nlink_url'], $link['nlink_cpage_id'], $link['nlink_category_id']);
                        $OrgnavUrl = CommonHelper::getnavigationUrl($link['nlink_type'], $link['nlink_url'], $link['nlink_cpage_id'], $link['nlink_category_id'], $getOrgUrl);
                        $rootLinkUrl = UrlHelper::generateUrl('category', 'view', array($link['nlink_category_id']));
                        $href = $navUrl;
                        $navchild = '';
                        $target = $link['nlink_target'];
                        if (0 < count($link['children'])) {
                            $navchild = 'navchild';
                            $target = '_self';
                        } ?>
                        <li class="navigation-item <?php echo $navchild; ?>">
                            <a class="navigation-link" target="<?php echo $target; ?>" data-org-url="<?php echo $OrgnavUrl; ?>"
                                href="<?php echo $href; ?>"><?php echo $link['nlink_caption']; ?></a>
                            <?php if (isset($link['children']) && count($link['children']) > 0) { ?>
                                <span class="link__mobilenav"></span>
                                <div class="subnav">
                                    <div class="subnav-inner">
                                        <div class="container categories-container">
                                            <div class="categories-block">
                                                <?php $subyChild = 0;
                                                foreach ($link['children'] as $children) {
                                                    $subCatUrl = UrlHelper::generateUrl('category', 'view', array($children['prodcat_id']));
                                                    $subCatOrgUrl = UrlHelper::generateUrl('category', 'view', array($children['prodcat_id']), '', null, false, $getOrgUrl);
                                                    ?>
                                                    <div class="categories-cols">
                                                        <ul class="categories-list">
                                                            <li class="categories-list-item">
                                                                <a class="categories-list-link categories-list-head"
                                                                    data-org-url="<?php echo $subCatOrgUrl; ?>"
                                                                    href="<?php echo $subCatUrl; ?>"><?php echo $children['prodcat_name']; ?></a>
                                                            </li>
                                                            <?php $subChild = 0;
                                                            foreach ($children['children'] as $childCat) {
                                                                $catUrl = UrlHelper::generateUrl('category', 'view', array($childCat['prodcat_id']));
                                                                $catOrgUrl = UrlHelper::generateUrl('category', 'view', array($children['prodcat_id']), '', null, false, $getOrgUrl);
                                                                ?>

                                                                <li class="categories-list-item">
                                                                    <a class="categories-list-link" data-org-url="<?php echo $catOrgUrl; ?>"
                                                                        href="<?php echo $catUrl; ?>">
                                                                        <?php echo $childCat['prodcat_name']; ?></a>
                                                                </li>
                                                                <?php
                                                                if ($subChild++ == 7) {
                                                                    break;
                                                                }
                                                            }

                                                            ?>
                                                        </ul>
                                                    </div>
                                                    <?php
                                                    if ($subyChild++ == 8) {
                                                        break;
                                                    }
                                                } ?>
                                            </div>

                                            <a href="<?php echo $rootLinkUrl; ?>">
                                                <figure class="category-media">
                                                    <img src="<?php echo UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Category', 'thumb', array($link['nlink_category_id'], $siteLangId, ImageDimension::VIEW_ICON, 0), CONF_WEBROOT_FRONT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg'); ?>"
                                                        alt="<?php echo $link['nlink_caption']; ?>" />
                                                    <figcaption><?php echo $link['nlink_caption']; ?></figcaption>
                                                </figure>
                                            </a>


                                        </div>

                                    </div>
                                </div>
                            <?php } ?>
                        </li> <?php
                    }
                }
            }
        } ?>

        <?php
        foreach ($headerNavigation as $nav) {
            $subMoreNavigation = (count($nav['pages']) > $navLinkCount) ? array_slice($nav['pages'], $navLinkCount) : array();
            if (count($subMoreNavigation)) { ?>
                <li class="navigation-item navchild seemore">
                    <a class="navigation-link"><?php echo Labels::getLabel('NAV_MORE', $siteLangId); ?></a>
                    <span class="link__mobilenav"></span>
                    <div class="subnav">
                        <div class="subnav-inner">
                            <div class="container categories-container">
                                <div class="categories-block">
                                    <?php
                                    foreach ($subMoreNavigation as $index => $link) {
                                        $url = CommonHelper::getnavigationUrl($link['nlink_type'], $link['nlink_url'], $link['nlink_cpage_id'], $link['nlink_category_id']);
                                        $OrgUrl = CommonHelper::getnavigationUrl($link['nlink_type'], $link['nlink_url'], $link['nlink_cpage_id'], $link['nlink_category_id'], $getOrgUrl);
                                        ?>
                                        <div class="categories-cols">
                                            <ul class="categories-list">
                                                <li class="categories-list-item">
                                                    <a class="categories-list-link categories-list-head"
                                                        data-org-url="<?php echo $OrgUrl; ?>"
                                                        href="<?php echo $url; ?>"><?php echo $link['nlink_caption']; ?></a>
                                                </li>
                                                <?php $subChild = 0;
                                                foreach ($link['children'] as $childCat) {
                                                    $catUrl = UrlHelper::generateUrl('category', 'view', array($childCat['prodcat_id']));
                                                    $catOrgUrl = UrlHelper::generateUrl('category', 'view', array($childCat['prodcat_id']), '', null, false, $getOrgUrl);
                                                    ?>

                                                    <li class="categories-list-item">
                                                        <a class="categories-list-link" data-org-url="<?php echo $catOrgUrl; ?>"
                                                            href="<?php echo $catUrl; ?>">
                                                            <?php echo $childCat['prodcat_name']; ?></a>
                                                    </li>
                                                    <?php
                                                    if ($subChild++ == 7) {
                                                        break;
                                                    }
                                                }

                                                ?>
                                            </ul>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
            <?php }
        } ?>
    </ul>
</div>
<!-- End Navigation Bar -->