<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="offcanvas-header">
    <h5 class="offcanvas-title"><?php echo Labels::getLabel('LBL_SHOP_BY_CATEGORY', $siteLangId); ?></h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="close"></button>
</div>
<div class="offcanvas-body">
    <ul class="grouping grouping-level grouping-level-1 sidebarNavLinksJs" id="sidebarNavLinks">
        <?php if (isset($headerNavigation) && count($headerNavigation)) {
            foreach ($headerNavigation as $nav) {
                if ($nav['pages']) {
                    foreach ($nav['pages'] as $link) {
                        $catThumb = AttachedFile::getAttachment(AttachedFile::FILETYPE_CATEGORY_THUMB, $link['nlink_category_id'], 0, $siteLangId, false, 0);
                        $uploadedTime = AttachedFile::setTimeParam($catThumb['afile_updated_at']);
                        $navUrl = CommonHelper::getnavigationUrl($link['nlink_type'], $link['nlink_url'], $link['nlink_cpage_id'], $link['nlink_category_id']);
                        $OrgnavUrl = CommonHelper::getnavigationUrl($link['nlink_type'], $link['nlink_url'], $link['nlink_cpage_id'], $link['nlink_category_id'], $getOrgUrl);
                        $rootLinkUrl = UrlHelper::generateUrl('category', 'view', array($link['nlink_category_id']));
                        $href = $navUrl;
                        $target = $link['nlink_target'];
                        if (0 < count($link['children'])) {
                            $href = '#';
                            $target = '_self';
                        }
                        require 'mobile-nav-item-link.php';
                    }
                }
            }
        }

        $headerCategories = CacheHelper::get('headerCategories_' . $siteLangId, CONF_HOME_PAGE_CACHE_TIME, '.txt');
        if ($headerCategories) {
            $headerCategories = unserialize($headerCategories);
        } else {
            $headerCategories = ProductCategory::getArray($siteLangId, 0, false, true, false, CONF_USE_FAT_CACHE);
            CacheHelper::create('headerCategories_' . $siteLangId, serialize($headerCategories), CacheHelper::TYPE_NAVIGATION);
        }
        
        if ($isMegaMenuEnabled == Navigations::LAYOUT_MEGA_MENU && !empty($headerCategories)) {
            $catCount = 0;
         
            $headerCategories = ProductCategory::sortCategoriesByNameAsc($headerCategories);
            foreach ($headerCategories as $link) {
                $href = UrlHelper::generateUrl('category', 'view', array($link['prodcat_id']));
                $OrgnavUrl = UrlHelper::generateUrl('category', 'view', array($link['prodcat_id']), '', false);
                if (0 < count($link['children'])) {
                    $href = '#';
                }

                require 'mobile-nav-item-cat.php';

                $catCount++;
            }
        } ?>
    </ul>
</div>